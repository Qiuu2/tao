// Package audit 负责把「谁在什么时候从哪个 IP 做了什么」写进 log 表。
//
// # 为什么必须有这一环
//
// 旧 PHP 在 do.php 派发器末尾调一次 insert_log($opt, $olduser)，
// 每个写操作请求落一行。新版在本模块之前**一行都不写** ——
// 操作日志页面只能看到旧系统和后台 C 服务留下的记录，
// 新版做的任何操作都查不到，等于审计断档。
//
// # log 表是三方共用的
//
// 现网 156 行里能看出三个写入方：
//
//	user = 'admin'    旧 PHP Web（会话用户名）
//	user = 'server'   后台 C 服务（「回收临时文件0」之类）
//	user = '主机:-1'   后台 C 服务（「播放通道:1, 节目:8」之类）
//
// 所以清空日志会连带清掉 C 服务的记录，而且清完它还会继续往里写。
// 这一点在删除接口的说明里要讲清楚。
//
// # 与旧版一致的两个细节
//
//   - log.time 写**东八区时间字符串**（契约 C-15）。
//     旧版是 gmdate("Y-m-d H:i:s", time()+8*3600)，即无论 PHP 时区如何都强制 UTC+8。
//     这里同样显式转 Asia/Shanghai，不依赖数据库或进程时区 ——
//     现网 MySQL 的 time_zone 是 SYSTEM，换一台机器部署就会偏。
//   - log.user 是 varchar(11)，超长会被 MySQL 静默截断（契约 C-16）。
//     这里主动按字符截断，免得留下半个汉字。
//
// # 与旧版不同的一点
//
// 旧版无论操作成功还是失败都记一行，连 default 分支的「非法操作」也记。
// 新版只在业务码为 200 时记录 —— 失败的请求记进操作日志会让审计噪音大到没法看，
// 真正需要排查失败时看服务端访问日志（那里逐条带状态码）。
package audit

import (
	"context"
	"database/sql"
	"log"
	"net"
	"net/http"
	"strings"
	"time"
)

// userColLimit 是 log.user 的列宽（varchar(11)）。
const userColLimit = 11

// operateColLimit 是 log.operate 的列宽（varchar(255)），按字节算。
const operateColLimit = 255

type Recorder struct {
	db *sql.DB
	// loc 固定东八区。用 FixedZone 而不是 LoadLocation("Asia/Shanghai")：
	// 目标机器是精简安装的 Linux，很可能没有 tzdata，
	// LoadLocation 会失败并悄悄退回 UTC，日志时间就整体差 8 小时。
	loc *time.Location
}

func New(db *sql.DB) *Recorder {
	return &Recorder{db: db, loc: time.FixedZone("CST", 8*3600)}
}

// Write 落一行操作日志。
//
// 失败只记服务端日志，不向上抛：审计写失败不应该让已经成功的业务操作回滚，
// 那会把「记不下来」升级成「做不成」。
func (r *Recorder) Write(ctx context.Context, user, operate, ip string) {
	if r == nil || r.db == nil {
		return
	}
	_, err := r.db.ExecContext(ctx,
		`INSERT INTO log (user, operate, ip, time) VALUES (?,?,?,?)`,
		truncRunes(user, userColLimit), truncBytes(operate, operateColLimit),
		truncBytes(ip, 64), r.Now())
	if err != nil {
		log.Printf("audit: 写操作日志失败: %v (user=%s operate=%s)", err, user, operate)
	}
}

// WriteTx 在调用方的事务里落一行，用于「清空日志」这种必须与删除同进同出的场景。
func (r *Recorder) WriteTx(ctx context.Context, tx *sql.Tx, user, operate, ip string) (int64, error) {
	res, err := tx.ExecContext(ctx,
		`INSERT INTO log (user, operate, ip, time) VALUES (?,?,?,?)`,
		truncRunes(user, userColLimit), truncBytes(operate, operateColLimit),
		truncBytes(ip, 64), r.Now())
	if err != nil {
		return 0, err
	}
	return res.LastInsertId()
}

// Now 返回东八区的 "Y-m-d H:i:s" 字符串。
func (r *Recorder) Now() string {
	return time.Now().In(r.loc).Format("2006-01-02 15:04:05")
}

// truncRunes 按**字符**截断，避免在多字节字符中间切开留下乱码。
func truncRunes(s string, n int) string {
	rs := []rune(s)
	if len(rs) <= n {
		return s
	}
	return string(rs[:n])
}

// truncBytes 按字节截断，切到多字节字符中间时回退到上一个完整字符边界。
func truncBytes(s string, n int) string {
	if len(s) <= n {
		return s
	}
	b := []byte(s)[:n]
	for len(b) > 0 && b[len(b)-1]&0xC0 == 0x80 {
		b = b[:len(b)-1]
	}
	if len(b) > 0 && b[len(b)-1]&0x80 != 0 {
		b = b[:len(b)-1]
	}
	return string(b)
}

// ClientIP 取请求方 IP。
//
// 现网旧 PHP 用的是 $_SERVER['REMOTE_ADDR']，而库里最近那一批记录的 ip 是空的 ——
// 那些行是后台 C 服务写的，它根本没有 IP 概念。Web 侧一律记真实对端地址。
//
// X-Forwarded-For 只在配置了反向代理时才可信。现网新版 Web 是直接监听 8080
// 对外提供服务的，没有代理，因此**不采信** XFF —— 采信它等于让客户端
// 自己声明自己的 IP，审计日志就没有意义了。
func ClientIP(r *http.Request) string {
	host, _, err := net.SplitHostPort(strings.TrimSpace(r.RemoteAddr))
	if err != nil {
		return strings.TrimSpace(r.RemoteAddr)
	}
	return host
}
