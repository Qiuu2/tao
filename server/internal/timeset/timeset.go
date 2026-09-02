// Package timeset 实现时间设置（旧版 set_server_time.html）。
//
// 这一页在旧版上做四件事，新版做其中三件：
//
//	① 显示 / 修改服务器系统时间        —— 新版**不做**，理由见下
//	② NTP 服务器地址                    —— serverbaseparam.ntpserver
//	③ GPS 校时终端                      —— serverbaseparam.adjusttime
//	④ 给终端下发校时指令                —— terminal?state=30&id=N
//
// # ① 为什么不做「修改服务器系统时间」
//
// 旧版那段代码是：
//
//	@exec("date $date_value", ...);
//	@exec("time $time_value", ...);
//	@exec("setrtc $year $month $day $hour $minute $second", ...);
//
// 三个问题叠在一起（D-218）：
//
//   - `date MM/DD/YY` + `time HH:MM:SS` 是 DOS/Windows 的命令语法，
//     Linux 上 `date` 根本不接受这种参数，`time` 更是 shell 内建的计时关键字。
//     这段代码在现在的部署形态下**从来没有生效过**。
//   - 它跑在 PHP 容器里。容器与宿主共享同一个内核时钟，
//     没有 CAP_SYS_TIME 的容器改不动时间；真改成功了，改的也是整台宿主机。
//   - 参数是 $_POST 直接拼进 exec，命令注入。
//
// 更根本的是：**这台机器上正跑着按时刻表打铃的广播服务**。
// 把系统时间往前拨会让一批任务瞬间集体触发，往后拨会让今天剩下的铃全部哑掉。
// 这种事不该藏在一个 Web 表单后面。所以新版只**显示**服务器当前时间、
// 时区与运行时长，让运维能一眼看出有没有跑偏；真要改，用 NTP 或到主机上改。
//
// # ③ ⚠ adjusttime 不是开关，是终端 ID
//
// 列名和常见的布尔字段很像，但旧版 setgpsterminal.php 写的是：
//
//	update serverbaseparam set adjusttime = '$gpsselects'
//
// 其中 `$gpsselects` 是**下拉框里选中的那台终端的 id**；0 表示不启用 GPS 校时。
// 把它当 0/1 开关会把一台 id=7 的终端写成 1，指向另一台完全无关的设备。
//
// # ⚠ setgpsterminal.php?gpsselects=-1 会重启整台机器
//
// 那个分支里是 `server?state=1` 加两句 `sudo reboot`（D-219）。
// 一个「取消 GPS 校时」的链接顺手重启整栋楼的广播主机，没有任何确认。
// 新版**不提供**这条路径：取消就是把 adjusttime 写回 0。
package timeset

import (
	"context"
	"database/sql"
	"fmt"
	"net"
	"os"
	"strconv"
	"strings"
	"time"
)

type Service struct {
	db *sql.DB
}

func New(db *sql.DB) *Service { return &Service{db: db} }

// GPSOff 表示未启用 GPS 校时。
const GPSOff = 0

// ntpLimit 是 serverbaseparam.ntpserver 的列宽。
const ntpLimit = 64

// State 是这一页要展示的全部内容。
type State struct {
	// ServerTime 是**服务器**当前时间，用来和运维本地的表对一下。
	ServerTime string `json:"serverTime"`
	Timezone   string `json:"timezone"`
	// TimezoneOffset 是相对 UTC 的分钟数，界面据此说明「东八区」之类。
	TimezoneOffset int `json:"timezoneOffset"`
	// Uptime 是主机已运行时长（秒）。时间被人为拨动过的话，
	// 它和 ServerTime 的关系会很违和 —— 这是最容易看出来的线索。
	Uptime int64 `json:"uptime"`
	// DBTime 是数据库服务器的当前时间。它和 ServerTime 应当一致；
	// 不一致说明 Web 进程和数据库不在同一台机器上，或者时区配置不同。
	DBTime string `json:"dbTime"`
	// DBTimeDiff 是两者相差的秒数（服务器 - 数据库）。
	DBTimeDiff int `json:"dbTimeDiff"`

	NTPServer string `json:"ntpserver"`

	// GPSTerminalID 就是 serverbaseparam.adjusttime。0 = 未启用。
	GPSTerminalID   int64  `json:"gpsTerminalId"`
	GPSTerminalName string `json:"gpsTerminalName"`
	// GPSTerminalMissing 表示 adjusttime 指向的终端已经不存在了。
	GPSTerminalMissing bool `json:"gpsTerminalMissing"`

	// ReadOnly 为 true 表示当前是备机模式，这一页不允许改。
	ReadOnly bool `json:"readOnly"`

	// 能不能通过 Web 改系统时钟，以及不能的话缺什么。详见 clock.go。
	ClockAbility
}

func (s *Service) Get(ctx context.Context) (*State, error) {
	st := &State{}
	var model int
	err := s.db.QueryRowContext(ctx, `
		SELECT COALESCE(ntpserver,''), COALESCE(adjusttime,0), COALESCE(model,1)
		FROM serverbaseparam WHERE id = 1 LIMIT 1`).
		Scan(&st.NTPServer, &st.GPSTerminalID, &model)
	if err != nil {
		return nil, fmt.Errorf("读取时间设置: %w", err)
	}
	st.ReadOnly = model == 2
	st.ClockAbility = s.probeClock(ctx)

	now := time.Now()
	st.ServerTime = now.Format("2006-01-02 15:04:05")
	zone, offset := now.Zone()
	st.Timezone = zone
	st.TimezoneOffset = offset / 60
	st.Uptime = uptimeSeconds()

	// 数据库那边的当前时间。CAST 成 CHAR 拿字面值，
	// 免得 parseTime=true 把它解析成 time.Time 再格式化时多一层时区换算。
	var dbTime string
	if err := s.db.QueryRowContext(ctx, `SELECT CAST(NOW() AS CHAR)`).Scan(&dbTime); err == nil {
		st.DBTime = dbTime
		if t, perr := time.ParseInLocation("2006-01-02 15:04:05", dbTime, time.Local); perr == nil {
			st.DBTimeDiff = int(now.Sub(t).Round(time.Second).Seconds())
		}
	}

	if st.GPSTerminalID > GPSOff {
		var name string
		err := s.db.QueryRowContext(ctx,
			`SELECT COALESCE(terminalname,'') FROM terminal WHERE id = ? LIMIT 1`,
			st.GPSTerminalID).Scan(&name)
		switch {
		case err == sql.ErrNoRows:
			st.GPSTerminalMissing = true
			st.GPSTerminalName = "(终端已删除)"
		case err != nil:
			return nil, fmt.Errorf("查询校时终端: %w", err)
		default:
			st.GPSTerminalName = name
		}
	}
	return st, nil
}

// uptimeSeconds 读 /proc/uptime。读不到就返回 0（不是错误 —— 换个平台就没有这文件）。
func uptimeSeconds() int64 {
	raw, err := os.ReadFile("/proc/uptime")
	if err != nil {
		return 0
	}
	fields := strings.Fields(string(raw))
	if len(fields) == 0 {
		return 0
	}
	v, err := strconv.ParseFloat(fields[0], 64)
	if err != nil {
		return 0
	}
	return int64(v)
}

// ---------- NTP ----------

// SetNTP 写 serverbaseparam.ntpserver。
//
// 只写数据库，**不去改系统的 /etc/ntp.conf 或 systemd-timesyncd 配置** ——
// 与服务器参数页同一条原则：新版不碰任何系统配置文件。
// 这一列是给后台 C 服务读的，它自己去做对时。
func (s *Service) SetNTP(ctx context.Context, addr string) error {
	addr = strings.TrimSpace(addr)
	if len(addr) > ntpLimit {
		return fmt.Errorf("NTP 服务器地址过长：按 UTF-8 计 %d 字节，上限 %d 字节", len(addr), ntpLimit)
	}
	// 允许清空（等于停用 NTP）。非空时必须是 IPv4 或看得过去的主机名 ——
	// 旧版 gettimeip 一个字符都不校验，把中文粘进去也照存。
	if addr != "" && !validHost(addr) {
		return fmt.Errorf("NTP 服务器地址格式不正确，必须是 IPv4 地址或主机名")
	}
	if err := s.assertWritable(ctx); err != nil {
		return err
	}
	// 旧版这条 UPDATE 不带 WHERE（和 D-208 同一个毛病）
	if _, err := s.db.ExecContext(ctx,
		`UPDATE serverbaseparam SET ntpserver = ? WHERE id = 1`, addr); err != nil {
		return fmt.Errorf("保存 NTP 服务器地址: %w", err)
	}
	return nil
}

func validHost(s string) bool {
	if ip := net.ParseIP(s); ip != nil {
		return ip.To4() != nil
	}
	if len(s) > 253 {
		return false
	}
	for _, label := range strings.Split(s, ".") {
		if label == "" || len(label) > 63 {
			return false
		}
		for i := 0; i < len(label); i++ {
			c := label[i]
			ok := (c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') ||
				(c >= '0' && c <= '9') || c == '-'
			if !ok {
				return false
			}
		}
		if label[0] == '-' || label[len(label)-1] == '-' {
			return false
		}
	}
	return true
}

// ---------- GPS 校时终端 ----------

// SetGPSTerminal 设置 GPS 校时终端。terminalID 传 0 表示停用。
//
// 注意这里写的是**终端 id**，不是 0/1 开关 —— 见包注释。
func (s *Service) SetGPSTerminal(ctx context.Context, terminalID int64) error {
	if terminalID < 0 {
		return fmt.Errorf("校时终端 ID 不合法")
	}
	if err := s.assertWritable(ctx); err != nil {
		return err
	}
	if terminalID > GPSOff {
		var n int
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM terminal WHERE id = ?`, terminalID).Scan(&n); err != nil {
			return fmt.Errorf("校验校时终端: %w", err)
		}
		if n == 0 {
			return fmt.Errorf("选择的校时终端不存在，请重新选择")
		}
	}
	if _, err := s.db.ExecContext(ctx,
		`UPDATE serverbaseparam SET adjusttime = ? WHERE id = 1`, terminalID); err != nil {
		return fmt.Errorf("保存校时终端: %w", err)
	}
	return nil
}

// assertWritable 备机模式下这一页只读，与服务器参数页同一条规则（BR-260）。
func (s *Service) assertWritable(ctx context.Context) error {
	var model int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(model,1) FROM serverbaseparam WHERE id = 1 LIMIT 1`).Scan(&model); err != nil {
		return fmt.Errorf("读取服务器模式: %w", err)
	}
	if model == 2 {
		return fmt.Errorf("当前是备机模式，不允许修改时间设置")
	}
	return nil
}

// ---------- 可选的校时终端 ----------

type TerminalOption struct {
	ID       int64  `json:"id"`
	Name     string `json:"terminalname"`
	IP       string `json:"ip"`
	TypeName string `json:"typeName"`
	NetState int    `json:"netstate"`
	// GroupID / GroupName 是终端分区，供界面把终端选择器排成树。
	// ⚠ 树按 id 归组而不是按名字：serverplaystream.name 上没有唯一索引
	//   （建索引属 DDL，红线禁止），重名的两个分区按名字归会被并成一个。
	GroupID   int64  `json:"groupId"`
	GroupName string `json:"groupName"`
}

// Terminals 列出可以当校时终端的设备。
//
// 旧版下拉里放的是全部终端，这里保持一致 —— 哪种型号支持 GPS 授时
// 由现场设备决定，库里没有可靠的标志位可以筛。
func (s *Service) Terminals(ctx context.Context, keyword string) ([]TerminalOption, error) {
	// 分区名在 serverplaystream，终端与分区的关系在 terminalofgroup；
	// 一台终端理论上可能有多行，取 id 最小的那条，与 task/picker.go 的口径一致。
	q := `SELECT t.id, COALESCE(t.terminalname,''), COALESCE(t.ip,''),
	             COALESCE(tt.name,''), COALESCE(t.netstate,0),
	             COALESCE((SELECT tog.groupid FROM terminalofgroup tog
	                       WHERE tog.terminalid = t.id ORDER BY tog.id LIMIT 1), 0),
	             COALESCE((SELECT sps.name FROM terminalofgroup tog
	                       JOIN serverplaystream sps ON sps.streamid = tog.groupid
	                       WHERE tog.terminalid = t.id ORDER BY tog.id LIMIT 1), '')
	      FROM terminal t
	      LEFT JOIN terminaltype tt ON tt.id = t.typeid`
	var args []interface{}
	if keyword = strings.TrimSpace(keyword); keyword != "" {
		q += ` WHERE t.terminalname LIKE ? ESCAPE '\\'`
		r := strings.NewReplacer(`\`, `\\`, `%`, `\%`, `_`, `\_`)
		args = append(args, "%"+r.Replace(keyword)+"%")
	}
	q += ` ORDER BY t.netstate DESC, t.id LIMIT 500`

	rs, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询终端: %w", err)
	}
	defer rs.Close()
	out := []TerminalOption{}
	for rs.Next() {
		var o TerminalOption
		if err := rs.Scan(&o.ID, &o.Name, &o.IP, &o.TypeName, &o.NetState,
			&o.GroupID, &o.GroupName); err != nil {
			return nil, err
		}
		out = append(out, o)
	}
	return out, rs.Err()
}

// ---------- 终端校时 ----------

// SyncTargets 校验一批要校时的终端，返回其中真实存在的那些。
//
// 真正发报文由调用方用 notify.TermSyncTime 完成 —— 这个包不持有 Notifier，
// 免得又多一份「谁来发通知」的分歧。
func (s *Service) SyncTargets(ctx context.Context, ids []int64) ([]int64, error) {
	if len(ids) == 0 {
		return nil, fmt.Errorf("请先选择要校时的终端")
	}
	if len(ids) > 3000 {
		return nil, fmt.Errorf("一次最多给 3000 台终端校时")
	}
	ph := strings.TrimSuffix(strings.Repeat("?,", len(ids)), ",")
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		args[i] = id
	}
	rs, err := s.db.QueryContext(ctx,
		`SELECT id FROM terminal WHERE id IN (`+ph+`)`, args...)
	if err != nil {
		return nil, fmt.Errorf("校验终端: %w", err)
	}
	defer rs.Close()
	out := []int64{}
	for rs.Next() {
		var id int64
		if err := rs.Scan(&id); err != nil {
			return nil, err
		}
		out = append(out, id)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}
	if len(out) == 0 {
		return nil, fmt.Errorf("选择的终端都已不存在，请刷新后重试")
	}
	return out, nil
}
