// Command setupdb 为新版 Web 创建一个「无 DDL 能力」的数据库账号。
//
// 背景：手册 R1 红线要求「禁止任何 DDL 变更」。仅靠代码自律是不够的 ——
// 本工具在**数据库权限层**再加一道硬保险：新账号只被授予
// SELECT / INSERT / UPDATE / DELETE 四项 DML 权限，
// 刻意不授予 CREATE / ALTER / DROP / INDEX / REFERENCES。
// 这样即使应用代码写错，也物理上无法改动表结构。
//
// 注意：CREATE USER / GRANT 属于 DCL（权限控制），不是 DDL（表结构变更），
// 不违反 R1 红线。
//
// 用法：
//
//	setupdb -root-dsn "root:xxx@tcp(127.0.0.1:3306)/audioserver" -pass "<新密码>"
//	setupdb -verify-only -dsn "htweb:xxx@tcp(127.0.0.1:3306)/audioserver"
package main

import (
	"database/sql"
	"errors"
	"flag"
	"fmt"
	"os"
	"strings"

	"github.com/go-sql-driver/mysql"
)

// 新账号被授予的权限。顺序与 SHOW GRANTS 的输出无关，比对时会做集合比较。
var wantPrivs = []string{"SELECT", "INSERT", "UPDATE", "DELETE"}

// 绝对不能出现在授权列表里的权限。一旦出现说明授权写错了。
var forbiddenPrivs = []string{
	"CREATE", "ALTER", "DROP", "INDEX", "REFERENCES",
	"CREATE VIEW", "CREATE ROUTINE", "ALTER ROUTINE",
	"TRIGGER", "EVENT", "ALL PRIVILEGES", "SUPER",
}

func main() {
	var (
		rootDSN = flag.String("root-dsn", "", "管理员 DSN，用于创建账号")
		dsn     = flag.String("dsn", "", "被检账号 DSN（配合 -verify-only）")
		user    = flag.String("user", "htweb", "要创建的账号名")
		pass    = flag.String("pass", "", "要创建的账号密码")
		dbName  = flag.String("db", "audioserver", "授权范围的库名")
		// 3306 是 Docker 发布端口，连接源地址是 Docker 网关而非 localhost，
		// 因此 host 必须用 % 才能匹配上。
		hostPat    = flag.String("host", "%", "账号的 host 匹配模式")
		verifyOnly = flag.Bool("verify-only", false, "只做权限边界验证，不创建账号")
	)
	flag.Parse()

	if *verifyOnly {
		if *dsn == "" {
			fatal("需要 -dsn")
		}
		db := mustOpen(*dsn)
		defer db.Close()
		if !verify(db, *dbName) {
			os.Exit(1)
		}
		return
	}

	if *rootDSN == "" || *pass == "" {
		fatal("需要 -root-dsn 与 -pass")
	}

	root := mustOpen(*rootDSN)
	defer root.Close()

	fmt.Printf("MySQL 版本: %s\n\n", scalar(root, "SELECT VERSION()"))

	acct := fmt.Sprintf("'%s'@'%s'", *user, *hostPat)
	fmt.Printf("--- 创建账号 %s ---\n", acct)

	// CREATE USER IF NOT EXISTS 在 MySQL 5.7.6+ / MariaDB 10.1.3+ 可用。
	stmts := []string{
		fmt.Sprintf("CREATE USER IF NOT EXISTS %s IDENTIFIED BY %s", acct, quote(*pass)),
		fmt.Sprintf("ALTER USER %s IDENTIFIED BY %s", acct, quote(*pass)),
		// 先收回可能存在的历史授权，确保权限集合是干净的
		fmt.Sprintf("REVOKE ALL PRIVILEGES ON `%s`.* FROM %s", *dbName, acct),
		fmt.Sprintf("GRANT %s ON `%s`.* TO %s", strings.Join(wantPrivs, ", "), *dbName, acct),
		"FLUSH PRIVILEGES",
	}
	for _, s := range stmts {
		if _, err := root.Exec(s); err != nil {
			// REVOKE 在没有任何历史授权时会报 1141，属正常情况，跳过
			var me *mysql.MySQLError
			if errors.As(err, &me) && (me.Number == 1141 || me.Number == 1147) {
				fmt.Printf("  · %s  （无历史授权，跳过）\n", brief(s))
				continue
			}
			fatalf("执行失败 [%s]: %v", brief(s), err)
		}
		fmt.Printf("  ✓ %s\n", brief(s))
	}

	fmt.Printf("\n--- 授权结果 ---\n")
	for _, g := range grantsOf(root, acct) {
		fmt.Printf("  %s\n", g)
	}

	// 用新账号重新连一次，独立验证权限边界
	newDSN := fmt.Sprintf("%s:%s@tcp(127.0.0.1:3306)/%s?charset=utf8&timeout=5s", *user, *pass, *dbName)
	fmt.Printf("\n--- 用新账号验证权限边界 ---\n")
	nd := mustOpen(newDSN)
	defer nd.Close()
	if !verify(nd, *dbName) {
		os.Exit(1)
	}
}

// verify 检查账号确实「能读写数据、不能改结构」。
func verify(db *sql.DB, dbName string) bool {
	pass := true

	// 1. 连通性 + 读权限
	if err := db.Ping(); err != nil {
		fmt.Printf("  ✗ 连接失败: %v\n", err)
		return false
	}
	fmt.Printf("  ✓ 连接成功，当前身份 = %s\n", scalar(db, "SELECT CURRENT_USER()"))

	var n int64
	if err := db.QueryRow("SELECT COUNT(*) FROM filefolder").Scan(&n); err != nil {
		fmt.Printf("  ✗ SELECT 失败（应当成功）: %v\n", err)
		pass = false
	} else {
		fmt.Printf("  ✓ SELECT 可用（filefolder %d 行）\n", n)
	}

	// 2. 授权列表里不得出现任何 DDL 权限
	fmt.Printf("\n  授权明细:\n")
	for _, g := range grantsOf(db, "") {
		fmt.Printf("    %s\n", g)
		up := strings.ToUpper(g)
		// USAGE 是「无权限」的占位符，忽略
		if strings.Contains(up, "ON *.*") && !strings.Contains(up, "USAGE") {
			fmt.Printf("    ⚠ 存在全局授权，超出预期范围\n")
			pass = false
		}
		for _, bad := range forbiddenPrivs {
			// 用逗号/空格边界避免 "CREATE" 误匹配 "CREATE VIEW" 之外的情况
			if containsPriv(up, bad) {
				fmt.Printf("    ✗ 检出禁止的权限: %s\n", bad)
				pass = false
			}
		}
	}

	// 3. DDL 能力探针（零风险）
	//
	// 对一个**不存在的表**执行 DROP TABLE：
	//   · 若无 DROP 权限 → 报 1142 (access denied)        ← 期望结果
	//   · 若有 DROP 权限 → 报 1051 (unknown table)        ← 说明权限过大
	// 两种情况都会报错，且全程不创建、不删除任何东西。
	const probe = "__htweb_privilege_probe_do_not_create__"
	_, err := db.Exec(fmt.Sprintf("DROP TABLE `%s`.`%s`", dbName, probe))
	var me *mysql.MySQLError
	switch {
	case err == nil:
		fmt.Printf("\n  ✗ DROP 探针竟然成功了 —— 权限严重超标\n")
		pass = false
	case errors.As(err, &me) && me.Number == 1142:
		fmt.Printf("\n  ✓ DDL 探针：DROP 被拒绝（error 1142 access denied）—— 符合预期\n")
	case errors.As(err, &me) && me.Number == 1051:
		fmt.Printf("\n  ✗ DDL 探针：账号具备 DROP 权限（error 1051 unknown table）—— 权限过大\n")
		pass = false
	default:
		fmt.Printf("\n  ? DDL 探针返回未预期错误，请人工确认: %v\n", err)
	}

	fmt.Printf("\n  === 权限边界验证：%s ===\n",
		map[bool]string{true: "通过", false: "未通过"}[pass])
	return pass
}

func grantsOf(db *sql.DB, acct string) []string {
	q := "SHOW GRANTS"
	if acct != "" {
		q += " FOR " + acct
	}
	rows, err := db.Query(q)
	if err != nil {
		return []string{fmt.Sprintf("(读取授权失败: %v)", err)}
	}
	defer rows.Close()
	var out []string
	for rows.Next() {
		var g string
		if rows.Scan(&g) == nil {
			out = append(out, g)
		}
	}
	return out
}

// containsPriv 判断授权串里是否出现了某个权限名（做边界判断，避免子串误判）。
func containsPriv(grantUpper, priv string) bool {
	idx := strings.Index(grantUpper, priv)
	for idx >= 0 {
		before := byte(' ')
		if idx > 0 {
			before = grantUpper[idx-1]
		}
		after := byte(' ')
		if idx+len(priv) < len(grantUpper) {
			after = grantUpper[idx+len(priv)]
		}
		okBefore := before == ' ' || before == ',' || before == '\t'
		okAfter := after == ' ' || after == ',' || after == '\t' || after == '\n'
		if okBefore && okAfter {
			// "CREATE" 后面跟着 " VIEW"/" ROUTINE" 时，交给对应的完整项去判定
			rest := grantUpper[idx+len(priv):]
			if priv == "CREATE" && (strings.HasPrefix(rest, " VIEW") ||
				strings.HasPrefix(rest, " ROUTINE") ||
				strings.HasPrefix(rest, " TEMPORARY") ||
				strings.HasPrefix(rest, " USER") ||
				strings.HasPrefix(rest, " TABLESPACE")) {
				// 跳过，继续找下一处
			} else if priv == "ALTER" && strings.HasPrefix(rest, " ROUTINE") {
				// 同上
			} else {
				return true
			}
		}
		next := strings.Index(grantUpper[idx+1:], priv)
		if next < 0 {
			break
		}
		idx = idx + 1 + next
	}
	return false
}

func mustOpen(dsn string) *sql.DB {
	db, err := sql.Open("mysql", dsn)
	if err != nil {
		fatalf("打开连接失败: %v", err)
	}
	if err := db.Ping(); err != nil {
		fatalf("连接失败: %v", err)
	}
	return db
}

func scalar(db *sql.DB, q string) string {
	var s string
	if err := db.QueryRow(q).Scan(&s); err != nil {
		return "(未知)"
	}
	return s
}

// quote 生成 SQL 字符串字面量。密码里可能含 # ! 等字符，必须正确转义。
func quote(s string) string {
	return "'" + strings.NewReplacer(`\`, `\\`, `'`, `\'`).Replace(s) + "'"
}

func brief(s string) string {
	if i := strings.Index(s, "IDENTIFIED BY"); i >= 0 {
		return s[:i] + "IDENTIFIED BY '******'"
	}
	return s
}

func fatal(msg string)                  { fmt.Fprintln(os.Stderr, "✗ "+msg); os.Exit(1) }
func fatalf(f string, a ...interface{}) { fmt.Fprintf(os.Stderr, "✗ "+f+"\n", a...); os.Exit(1) }
