// Command doctor 对现网环境做一次性只读体检。
//
// 它只做 SELECT 与文件 stat，不写库、不发 UDP、不改任何文件。
// 目的是在写业务代码之前，把《新版Web重构开发手册》§1.6 里那些
// "实测得出" 的结论再用程序确认一遍：
//
//  1. 宿主机能否连上 MySQL（127.0.0.1:3306，Docker 已发布的端口）
//  2. audioserver 库里的关键配置值（webport / model / registerflag）
//  3. 媒体路径映射规则：宿主路径 = /opt/apps/a9000 + media.filename
//  4. 核心表的数据规模，以及是否存在文档里提到的脏数据
package main

import (
	"database/sql"
	"flag"
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"time"

	_ "github.com/go-sql-driver/mysql"
)

// mediaRoot 是宿主机上的应用根目录。
// media.filename 存的是 "/backup/mediadata/xxx.mp3"（容器内视角），
// 宿主真实路径 = mediaRoot + media.filename，详见手册 §1.6.3。
const mediaRoot = "/opt/apps/a9000"

func main() {
	dsn := flag.String("dsn", "root:a9000db#!ht@tcp(127.0.0.1:3306)/audioserver?charset=utf8&parseTime=true&timeout=5s", "MySQL DSN")
	root := flag.String("root", mediaRoot, "宿主机应用根目录")
	flag.Parse()

	fmt.Println("================ 现网环境体检（只读） ================")
	fmt.Printf("时间   : %s\n", time.Now().Format("2006-01-02 15:04:05"))
	fmt.Printf("媒体根 : %s\n\n", *root)

	db, err := sql.Open("mysql", *dsn)
	if err != nil {
		fatal("打开数据库句柄失败", err)
	}
	defer db.Close()

	db.SetConnMaxLifetime(time.Minute)
	if err := db.Ping(); err != nil {
		fatal("连接 MySQL 失败（检查 3306 是否发布到宿主）", err)
	}
	ok("MySQL 连接成功 (127.0.0.1:3306/audioserver)")

	checkCharset(db)
	checkServerParams(db)
	checkScale(db)
	checkMediaFiles(db, *root)
	checkDirtyData(db)

	fmt.Println("\n================ 体检结束 ================")
}

// checkCharset 确认连接字符集。手册契约 C-01 要求固定 utf8，不得升级 utf8mb4。
func checkCharset(db *sql.DB) {
	section("连接字符集（契约 C-01：必须是 utf8，不得用 utf8mb4）")
	rows, err := db.Query("SHOW VARIABLES WHERE Variable_name IN ('character_set_client','character_set_connection','character_set_results','character_set_database')")
	if err != nil {
		warn("查询字符集失败", err)
		return
	}
	defer rows.Close()
	for rows.Next() {
		var k, v string
		if err := rows.Scan(&k, &v); err != nil {
			continue
		}
		mark := "  "
		if strings.HasPrefix(v, "utf8mb4") {
			mark = "⚠ "
		}
		fmt.Printf("  %s%-28s = %s\n", mark, k, v)
	}
}

// checkServerParams 读取 serverbaseparam 里几个决定新版行为的关键列。
func checkServerParams(db *sql.DB) {
	section("serverbaseparam 关键配置")

	var (
		id, webport, model, registerFlag int
		ip, projectName                  sql.NullString
	)
	err := db.QueryRow(`SELECT id, ip, webport, model, registerflag, projectname
	                    FROM serverbaseparam LIMIT 1`).
		Scan(&id, &ip, &webport, &model, &registerFlag, &projectName)
	if err != nil {
		warn("读取 serverbaseparam 失败", err)
		return
	}

	fmt.Printf("  id            = %d\n", id)
	fmt.Printf("  ip            = %s\n", ns(ip))
	fmt.Printf("  projectname   = %s\n", ns(projectName))
	fmt.Printf("  webport       = %d   ← 这是后台服务的 UDP 通知端口，不是 Apache 端口（手册 §1.6.2）\n", webport)

	switch model {
	case 1:
		fmt.Printf("  model         = 1   → 主服务器（可写）\n")
	case 2:
		fmt.Printf("  model         = 2   → ⚠ 备份服务器：按 BR-80，全站应只读\n")
	default:
		fmt.Printf("  model         = %d   → ⚠ 非预期取值\n", model)
	}

	switch registerFlag {
	case 0:
		fmt.Printf("  registerflag  = 0   → ⚠ 未注册：按 BR-71 禁止登录；录音媒体库(id=5)不可见\n")
	case 1:
		fmt.Printf("  registerflag  = 1   → 已注册；录音媒体库(id=5)可见\n")
	case 2:
		fmt.Printf("  registerflag  = 2   → 试用；录音媒体库(id=5)可见\n")
	default:
		fmt.Printf("  registerflag  = %d   → ⚠ 非预期取值\n", registerFlag)
	}

	var soundDetect, fuzaMima int
	if err := db.QueryRow(`SELECT sounddetect, fuzamima FROM serverconfig LIMIT 1`).
		Scan(&soundDetect, &fuzaMima); err == nil {
		fmt.Printf("  serverconfig.fuzamima = %d  → %s\n", fuzaMima,
			map[bool]string{true: "启用复杂密码策略（BR-77）", false: "不启用"}[fuzaMima != 0])
		fmt.Printf("  serverconfig.sounddetect = %d（契约 C-42：与 serverbaseparam.sounddetect 需一致）\n", soundDetect)
	}
}

// checkScale 统计核心表规模，用于判断分页与性能策略。
func checkScale(db *sql.DB) {
	section("核心表数据规模")
	for _, t := range []struct{ label, query string }{
		{"filefolder 文件夹", "SELECT COUNT(*) FROM filefolder"},
		{"media 媒体", "SELECT COUNT(*) FROM media"},
		{"media 非TTS", "SELECT COUNT(*) FROM media WHERE typeid<>'tts' AND filename<>'tts'"},
		{"terminal 终端", "SELECT COUNT(*) FROM terminal WHERE typeid<>0"},
		{"task 任务", "SELECT COUNT(*) FROM task"},
		{"作息方案(按info聚合)", "SELECT COUNT(DISTINCT info) FROM task WHERE tasktype IN (1,15) AND info<>'' AND channel=0 AND sec_task_id=0"},
		{"book_admin 用户", "SELECT COUNT(*) FROM book_admin"},
		{"usergroup 用户组", "SELECT COUNT(*) FROM usergroup"},
		{"log 操作日志", "SELECT COUNT(*) FROM log"},
	} {
		var n int64
		if err := db.QueryRow(t.query).Scan(&n); err != nil {
			fmt.Printf("  %-22s 查询失败: %v\n", t.label, err)
			continue
		}
		fmt.Printf("  %-22s %d\n", t.label, n)
	}
}

// checkMediaFiles 验证手册 §1.6.3 的路径映射规则是否成立：
// 宿主真实路径 = root + media.filename
func checkMediaFiles(db *sql.DB, root string) {
	section("媒体路径映射验证（宿主路径 = " + root + " + media.filename）")

	rows, err := db.Query(`SELECT id, name, filename, size FROM media
	                       WHERE filename <> 'tts' AND filename <> 'none'
	                       ORDER BY id LIMIT 20`)
	if err != nil {
		warn("查询 media 失败", err)
		return
	}
	defer rows.Close()

	var total, found, missing int
	for rows.Next() {
		var (
			id       int64
			name     string
			filename string
			sizeKB   sql.NullInt64
		)
		if err := rows.Scan(&id, &name, &filename, &sizeKB); err != nil {
			continue
		}
		total++
		full := filepath.Join(root, filename)
		st, statErr := os.Stat(full)
		if statErr != nil {
			missing++
			fmt.Printf("  ✗ id=%-4d %-24s %s（文件不存在）\n", id, trunc(name, 24), filename)
			continue
		}
		found++
		// media.size 单位是 KB（契约 C-03），与磁盘实际大小对比一下是否吻合
		diskKB := st.Size() / 1024
		note := ""
		if sizeKB.Valid && abs(diskKB-sizeKB.Int64) > 2 {
			note = fmt.Sprintf("  ⚠ 库记录 %dKB / 实际 %dKB", sizeKB.Int64, diskKB)
		}
		fmt.Printf("  ✓ id=%-4d %-24s %dKB%s\n", id, trunc(name, 24), diskKB, note)
	}

	fmt.Printf("\n  抽样 %d 条：命中 %d，缺失 %d\n", total, found, missing)
	if missing > 0 {
		fmt.Printf("  ⚠ 存在缺失文件 —— 可能是孤儿记录，或路径映射规则需要修正\n")
	} else if total > 0 {
		fmt.Printf("  ✓ 路径映射规则成立\n")
	}
}

// checkDirtyData 检查手册中反复提到的几类历史脏数据。
func checkDirtyData(db *sql.DB) {
	section("历史脏数据检查（对应手册缺陷清单）")

	// D-07 / D-08：树深度超过 3 层，或 parentid 指向不存在的节点（孤儿目录）
	var orphanFolders int
	if err := db.QueryRow(`SELECT COUNT(*) FROM filefolder f
	                       WHERE f.parentid <> 0
	                         AND NOT EXISTS (SELECT 1 FROM filefolder p WHERE p.id = f.parentid)`).
		Scan(&orphanFolders); err == nil {
		status(orphanFolders == 0,
			fmt.Sprintf("孤儿文件夹（parentid 指向不存在节点）: %d", orphanFolders),
			"→ 新版树构建必须容忍并展示为「游离节点」，见 D-07")
	}

	// 文件夹层级分布：确认是否真的存在第 4 层及以下（旧版树渲染不出来）
	var maxDepth int
	if err := db.QueryRow(`
		SELECT MAX(d) FROM (
			SELECT f1.id,
			       (CASE WHEN f1.parentid=0 THEN 1
			             WHEN f2.parentid=0 THEN 2
			             WHEN f3.parentid=0 THEN 3
			             ELSE 4 END) AS d
			FROM filefolder f1
			LEFT JOIN filefolder f2 ON f2.id=f1.parentid
			LEFT JOIN filefolder f3 ON f3.id=f2.parentid
		) t`).Scan(&maxDepth); err == nil {
		status(maxDepth <= 3,
			fmt.Sprintf("文件夹树最大深度: %d", maxDepth),
			"→ 存在 ≥4 层目录，旧版树渲染不出来（D-07），新版需完整展示")
	}

	// D-22：孤儿媒体（所属文件夹已被删）——旧版内连接会让它在任务清单里消失
	var orphanMedia int
	if err := db.QueryRow(`SELECT COUNT(*) FROM media m
	                       WHERE NOT EXISTS (SELECT 1 FROM filefolder f WHERE f.id = m.folderid)`).
		Scan(&orphanMedia); err == nil {
		status(orphanMedia == 0,
			fmt.Sprintf("孤儿媒体（folderid 指向不存在的文件夹）: %d", orphanMedia),
			"→ 新版任务清单必须用 LEFT JOIN 显示为「目录已删除」，见 D-22")
	}

	// C-36：task.projectstate 的实际取值分布，用于复核 0=启用/1=停用 的约定
	fmt.Println("\n  task.projectstate 取值分布（契约 C-36：0=启用, 1=停用）:")
	if rows, err := db.Query(`SELECT projectstate, COUNT(*) FROM task GROUP BY projectstate ORDER BY projectstate`); err == nil {
		defer rows.Close()
		for rows.Next() {
			var v, n int64
			if rows.Scan(&v, &n) == nil {
				label := "?"
				switch v {
				case 0:
					label = "启用"
				case 1:
					label = "停用"
				}
				fmt.Printf("    projectstate=%d (%s): %d 条\n", v, label, n)
			}
		}
	}

	// D-28：同一文件夹下同名媒体（旧版无唯一索引且查重有竞态）
	var dupMedia int
	if err := db.QueryRow(`SELECT COUNT(*) FROM (
	                         SELECT folderid, name FROM media
	                         GROUP BY folderid, name HAVING COUNT(*) > 1
	                       ) t`).Scan(&dupMedia); err == nil {
		status(dupMedia == 0,
			fmt.Sprintf("同文件夹同名媒体组数: %d", dupMedia),
			"→ 上传竞态留下的重复记录（D-28），读取端需按 id 取最新一条")
	}

	// 系统预置文件夹 1~9 是否完整（BR-21 依赖它们存在）
	var sysFolders int
	if err := db.QueryRow(`SELECT COUNT(*) FROM filefolder WHERE id BETWEEN 1 AND 9`).Scan(&sysFolders); err == nil {
		status(sysFolders == 9,
			fmt.Sprintf("系统预置文件夹(id 1~9)存在数: %d/9", sysFolders),
			"→ 缺失会影响 BR-21 的保护逻辑与树渲染")
	}
}

// ---------- 输出小工具 ----------

func section(title string) { fmt.Printf("\n--- %s ---\n", title) }
func ok(msg string)        { fmt.Printf("✓ %s\n", msg) }

func status(good bool, msg, hint string) {
	if good {
		fmt.Printf("  ✓ %s\n", msg)
		return
	}
	fmt.Printf("  ⚠ %s\n      %s\n", msg, hint)
}

func warn(msg string, err error) { fmt.Printf("  ⚠ %s: %v\n", msg, err) }

func fatal(msg string, err error) {
	fmt.Fprintf(os.Stderr, "✗ %s: %v\n", msg, err)
	os.Exit(1)
}

func ns(s sql.NullString) string {
	if !s.Valid || s.String == "" {
		return "(空)"
	}
	return s.String
}

func trunc(s string, n int) string {
	r := []rune(s)
	if len(r) <= n {
		return s
	}
	return string(r[:n-1]) + "…"
}

func abs(v int64) int64 {
	if v < 0 {
		return -v
	}
	return v
}
