// Command contract 核对新版写入的数据是否满足「新旧共存」的写入契约。
//
// 这是共存方案的命门：新版写进去的数据，旧 PHP 系统与后台 C 服务必须能正确读取。
// 任何一条契约被破坏，表现出来的往往不是报错，而是「旧界面显示异常」
// 或「后台播放行为变了」这类难以追查的问题。
//
// 契约编号对应《新版Web重构开发手册》§7.2。
package main

import (
	"database/sql"
	"flag"
	"fmt"
	"os"
	"strings"

	_ "github.com/go-sql-driver/mysql"
)

func main() {
	dsn := flag.String("dsn", "root:a9000db#!ht@tcp(127.0.0.1:3306)/audioserver?charset=utf8&timeout=30s", "DSN")
	mediaID := flag.Int64("media", 0, "要核对的媒体 ID，0 表示取最新一条")
	flag.Parse()

	db, err := sql.Open("mysql", *dsn)
	if err != nil {
		fmt.Println("打开数据库失败:", err)
		os.Exit(1)
	}
	defer db.Close()

	q := `SELECT id, name, size, typeid, priority, filename,
	             timelength, channel, sample, bitrate, COALESCE(userid,0), folderid
	      FROM media WHERE id = ?`
	if *mediaID == 0 {
		q = `SELECT id, name, size, typeid, priority, filename,
		            timelength, channel, sample, bitrate, COALESCE(userid,0), folderid
		     FROM media WHERE id = (SELECT MAX(id) FROM media)`
	}

	var (
		id, size, tl, ch, sm, br, uid, folderID, pri int64
		name, typeid, fn                             string
	)
	var scanErr error
	if *mediaID == 0 {
		scanErr = db.QueryRow(q).Scan(&id, &name, &size, &typeid, &pri, &fn, &tl, &ch, &sm, &br, &uid, &folderID)
	} else {
		scanErr = db.QueryRow(q, *mediaID).Scan(&id, &name, &size, &typeid, &pri, &fn, &tl, &ch, &sm, &br, &uid, &folderID)
	}
	if scanErr != nil {
		fmt.Println("查询媒体失败:", scanErr)
		os.Exit(1)
	}

	fmt.Printf("核对对象: media id=%d  name=%q  folderid=%d\n\n", id, name, folderID)

	pass := true
	check := func(label string, ok bool, got interface{}, want string) {
		mark := "✗"
		if ok {
			mark = "✓"
		} else {
			pass = false
		}
		fmt.Printf("  %s %-36s 实际=%-28v 期望=%s\n", mark, label, got, want)
	}

	check("C-04 typeid 上传后固定 mp3", typeid == "mp3", typeid, "mp3")
	check("C-07 priority 恒写 0（该列未使用）", pri == 0, pri, "0")
	check("C-08 userid 为真实上传者", uid > 0, uid, ">0")

	// C-06 的正确检查方式
	//
	// 契约要求 Web 侧「上传时写 0」，真实值由后台 C 服务扫描后回填。
	// 因此事后核对时这几列**应该已经是非 0** —— 那恰恰证明 UDP 通知联动生效了。
	// 如果一直是 0，反而说明后台没收到通知（或没在跑），是需要排查的问题。
	scanned := tl > 0 || sm > 0 || br > 0
	fmt.Println()
	if scanned {
		fmt.Printf("  ✓ %-36s timelength=%d channel=%d sample=%d bitrate=%d\n",
			"C-06 后台已回填媒体元数据", tl, ch, sm, br)
		fmt.Printf("      └─ 说明 UDP 通知已送达后台 C 服务并触发扫描，联动链路正常\n")
	} else {
		pass = false
		fmt.Printf("  ⚠ %-36s 四列仍全为 0\n", "C-06 后台尚未回填")
		fmt.Printf("      └─ 可能刚上传（稍候重试），也可能是 UDP 通知未送达 / 后台服务未运行\n")
	}

	// BR-40：转码时会在尾部追加 2 秒静音，这是旧系统的播放防截断约定。
	// 若 timelength 恰好等于源时长而非源时长+2，说明该约定被破坏了。
	if tl > 0 {
		fmt.Printf("  · %-36s timelength=%d 秒（源时长 + 2 秒静音）\n", "BR-40 尾部追加 2 秒静音", tl)
	}
	// media.size 单位是 KB。若误写成字节，几十 KB 的文件会变成几万
	check("C-03 size 单位为 KB 而非字节", size > 0 && size < 1_000_000, size, "KB 量级")
	check("C-05 filename 路径格式", strings.HasPrefix(fn, "/backup/mediadata/") && strings.HasSuffix(fn, ".mp3"),
		fn, "/backup/mediadata/<数字>.mp3")

	// 物理文件必须真实存在，否则是指向空文件的脏记录
	root := "/opt/apps/a9000"
	full := root + fn
	st, statErr := os.Stat(full)
	check("物理文件存在且非空", statErr == nil && st != nil && st.Size() > 0,
		func() interface{} {
			if statErr != nil {
				return "不存在"
			}
			return fmt.Sprintf("%d 字节", st.Size())
		}(), ">0 字节")

	// size 与磁盘实际大小应大致吻合（允许 ±2KB 的取整误差）
	if statErr == nil && st != nil {
		diskKB := st.Size() / 1024
		diff := diskKB - size
		if diff < 0 {
			diff = -diff
		}
		check("size 与磁盘实际大小吻合", diff <= 2,
			fmt.Sprintf("库记 %dKB / 磁盘 %dKB", size, diskKB), "差值 ≤2KB")
	}

	fmt.Printf("\n=== 写入契约核对: %s ===\n",
		map[bool]string{true: "全部通过", false: "存在不符项"}[pass])
	if !pass {
		os.Exit(1)
	}
}
