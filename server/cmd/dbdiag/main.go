// Command dbdiag 诊断数据库连接慢的原因。
//
// 现象：从宿主机连 MariaDB，TCP 拨号瞬时完成，但 MySQL 握手固定耗时约 10 秒。
// 这是 MariaDB 对连接来源做反向 DNS 解析、而该 IP 无 PTR 记录时的典型表现。
//
// 本工具只做 SELECT 与连接计时，不修改任何配置。
package main

import (
	"database/sql"
	"flag"
	"fmt"
	"time"

	_ "github.com/go-sql-driver/mysql"
)

func main() {
	dsn := flag.String("dsn", "root:a9000db#!ht@tcp(127.0.0.1:3306)/audioserver?charset=utf8&timeout=30s", "DSN")
	rounds := flag.Int("rounds", 3, "连续建立多少次新连接以观察耗时")
	flag.Parse()

	fmt.Println("=== 数据库连接诊断 ===")

	// 1) 关键变量
	db, err := sql.Open("mysql", *dsn)
	if err != nil {
		fmt.Printf("打开失败: %v\n", err)
		return
	}
	defer db.Close()

	t0 := time.Now()
	if err := db.Ping(); err != nil {
		fmt.Printf("首次连接失败: %v\n", err)
		return
	}
	fmt.Printf("首次连接耗时: %v\n\n", time.Since(t0).Round(time.Millisecond))

	fmt.Println("--- 与连接建立相关的服务端变量 ---")
	for _, v := range []string{
		"skip_name_resolve", // OFF 时服务端会对来源 IP 做反向 DNS，无 PTR 记录即卡住
		"host_cache_size",   // 0 表示禁用主机名缓存，每次连接都要重新解析
		"max_connections",
		"connect_timeout",
		"version",
	} {
		var name, val string
		err := db.QueryRow("SHOW VARIABLES LIKE ?", v).Scan(&name, &val)
		if err != nil {
			fmt.Printf("  %-20s (读取失败: %v)\n", v, err)
			continue
		}
		mark := "  "
		if name == "skip_name_resolve" && val == "OFF" {
			mark = "⚠ "
		}
		if name == "host_cache_size" && val == "0" {
			mark = "⚠ "
		}
		fmt.Printf("  %s%-20s = %s\n", mark, name, val)
	}

	// 2) 服务端看到的来源地址 —— 决定反向解析的对象
	var user, host string
	if err := db.QueryRow("SELECT CURRENT_USER(), SUBSTRING_INDEX(USER(),'@',-1)").Scan(&user, &host); err == nil {
		fmt.Printf("\n  服务端看到的连接: CURRENT_USER=%s  来源=%s\n", user, host)
	}

	// 3) 连续新建连接，观察是否每次都要付 ~10s
	fmt.Printf("\n--- 连续新建 %d 次连接的耗时 ---\n", *rounds)
	for i := 1; i <= *rounds; i++ {
		one, err := sql.Open("mysql", *dsn)
		if err != nil {
			fmt.Printf("  第%d次: 打开失败 %v\n", i, err)
			continue
		}
		st := time.Now()
		err = one.Ping()
		d := time.Since(st).Round(time.Millisecond)
		if err != nil {
			fmt.Printf("  第%d次: 失败(%v) %v\n", i, d, err)
		} else {
			fmt.Printf("  第%d次: 成功 耗时 %v\n", i, d)
		}
		_ = one.Close()
	}

	// 4) 复用同一连接执行查询的耗时（用于对比：慢在建连而非查询）
	fmt.Println("\n--- 复用已建立连接执行查询 ---")
	for i := 1; i <= 3; i++ {
		st := time.Now()
		var n int64
		if err := db.QueryRow("SELECT COUNT(*) FROM filefolder").Scan(&n); err != nil {
			fmt.Printf("  第%d次: 失败 %v\n", i, err)
			continue
		}
		fmt.Printf("  第%d次: 返回 %d 行，耗时 %v\n", i, n, time.Since(st).Round(time.Millisecond))
	}

	fmt.Println("\n=== 结论提示 ===")
	fmt.Println("若「新建连接」固定约 10s 而「复用连接查询」是毫秒级，")
	fmt.Println("则瓶颈在服务端建连阶段的反向 DNS 解析（skip_name_resolve=OFF）。")
}
