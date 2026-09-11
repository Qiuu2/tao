package serverparam

import (
	"fmt"
	"os"
	"path/filepath"
	"regexp"
	"strings"
)

// 主备服务器配置保存后，除了写库还要把这台机器**配成主机还是备机**。
//
// 依据是旧版 setmaster_backup()。它做的事分三类：
//
//	① 改配置里的名字与地址   haresources / ha.cf / /etc/hosts / /etc/hostname
//	                        crsync.sh 的 dstip、timeupdate.sh 的 ntpdate
//	② 按角色换文件          mysql 的 server.cnf、script/apprun.sh、备机的 /etc/crontab
//	③ 备机的复制起点        mysqlstartslave.sql 里的 master_host
//
// # ⚠ 这一页比「服务器基本信息」危险得多
//
// 那一页改错了，最多是 graylog 指着旧地址；这一页改错了，机器可能连不上
// （/etc/hosts、/etc/hostname）、主备同时抢同一个虚拟 IP（ha.cf），
// 或者 MySQL 复制配错（server.cnf）。而旧版全是**按行号** sed：
// `'150c'`、`'9c'`、`'10c'`、`'2c'`、`'211c'`、`'212c'`、`'56c'`、`'71c'`、
// `'91c'`、`'121c'`、`'17c'`、`'11c'`、`'1c'` —— 十三处行号，
// 任何一个文件多一行少一行，写进去的就是**另一行**，而且 sed 照样返回 0。
//
// 所以这里两条铁律：
//
//  1. **全都按内容定位**（`node `、`deadtime `、`ucast `、`127.0.0.1 `、`dstip=` …），
//     找不到锚点就不写，回报 no-anchor。
//  2. **要么全做，要么全不做**。先把所有目标探一遍：存在但写不进去的，
//     整组直接放弃并说明缺什么。改一半的 HA 配置（名字改了、ha.cf 没改）
//     比一点没改糟得多 —— 那是主备双活、两台机器抢同一个 IP 的前提。
//     文件**不存在**不算阻塞（这台机器没装那套东西），跳过即可。
//
// # 旧版做了而这里没做的，见文件末尾 haSkipped()

// haTargets 把这一次要动的所有文件收在一处：先整体探，再整体写。
type haTargets struct {
	// 改内容的
	edits []haEdit
	// 换文件的（把 src 拷成 dst）
	copies []haCopy
}

type haEdit struct {
	path string
	re   *regexp.Regexp
	// line 是要写进去的整行。nil 的 build 表示这一条按 nth 匹配（ha.cf 的两个 node）。
	line string
	what string
	// nth 为 0 表示第一处匹配；1 表示第二处（ha.cf 里 node 有两行）。
	nth int
	// sub 非 0 时只替换这个捕获组，整行的其余部分原样留着。
	// haresources 就靠它：只换行首的服务器名，后面 `<ip>/<前缀>/eth0 ha-post` 不动。
	sub int
}

type haCopy struct {
	src, dst string
	mode     os.FileMode
	what     string
}

var (
	reHANodeLine  = regexp.MustCompile(`(?m)^[ \t]*node[ \t]+.*$`)
	reHADeadtime  = regexp.MustCompile(`(?m)^[ \t]*deadtime[ \t]+.*$`)
	reHAInitdead  = regexp.MustCompile(`(?m)^[ \t]*initdead[ \t]+.*$`)
	reHABcast     = regexp.MustCompile(`(?m)^[ \t]*bcast[ \t]+.*$`)
	reHAUcast     = regexp.MustCompile(`(?m)^[ \t]*ucast[ \t]+.*$`)
	reHostLoop    = regexp.MustCompile(`(?m)^[ \t]*127\.0\.0\.1[ \t]+.*$`)
	reRsyncDstIP  = regexp.MustCompile(`(?m)^[ \t]*dstip=.*$`)
	reNtpdate     = regexp.MustCompile(`(?m)^[ \t]*ntpdate[ \t]+.*$`)
	reMasterHost  = regexp.MustCompile(`master_host\s*=\s*'[^']*'`)
	reHostsEntry  = `(?m)^[ \t]*\d+\.\d+\.\d+\.\d+[ \t]+%s[ \t]*$`
	reHAResHeadRe = regexp.MustCompile(`(?m)^([^\s#]+)(\s.*\bha-post[ \t]*)$`)
)

// hostsEntryFor 造一个「认主机名」的正则：`<任意IP>  <名字>`。
//
// 旧版是死写第 9 行、第 10 行。认名字稳得多 —— /etc/hosts 上面那几行注释
// 每个发行版都不一样，麒麟上还会多出 IPv6 那几条。
func hostsEntryFor(name string) *regexp.Regexp {
	if strings.TrimSpace(name) == "" {
		return nil
	}
	return regexp.MustCompile(fmt.Sprintf(reHostsEntry, regexp.QuoteMeta(name)))
}

// haPaths 是这一页要碰的全部路径。
type haPaths struct {
	HAResources []string
	HACf        string
	Hosts       string
	Hostname    string
	CrsyncSh    string
	TimeUpdate  string
	MysqlDir    string
	MyCnfTarget string
	ScriptDir   string
	AppRun      string
	Crontab     string
	CrontabSrc  string
	StartSlave  string
}

func (s *Service) haPathsFor() haPaths {
	root := strings.TrimSpace(s.a9000Root)
	p := haPaths{
		HACf:     "/etc/ha.d/ha.cf",
		Hosts:    "/etc/hosts",
		Hostname: "/etc/hostname",
		Crontab:  "/etc/crontab",
	}
	p.HAResources = []string{"/etc/ha.d/haresources"}
	if root == "" {
		return p
	}
	p.HAResources = append(p.HAResources,
		filepath.Join(root, "home/heartbeat/haresources"),
		filepath.Join(root, "home/heartbeat/haresource"))
	p.CrsyncSh = filepath.Join(root, "home/rsync/crsync.sh")
	p.TimeUpdate = filepath.Join(root, "script/timeupdate.sh")
	p.MysqlDir = filepath.Join(root, "home/mysql")
	p.MyCnfTarget = filepath.Join(root, "home/mysql/my.cnf.d/server.cnf")
	p.ScriptDir = filepath.Join(root, "script")
	p.AppRun = filepath.Join(root, "script/apprun.sh")
	p.CrontabSrc = filepath.Join(root, "home/heartbeat/crontab-slave")
	p.StartSlave = filepath.Join(root, "script/mysqlstartslave.sql")
	return p
}

// buildHATargets 按「这台机器要当主机还是备机」列出全部改动。
//
// isMaster 来自 HA.Model（1 = 主机，2 = 备机）；
// on 来自 HA.Backup（旧版的 serveritem / openorclose：1 = 启用主备，0 = 关掉）。
func (s *Service) buildHATargets(in Input) haTargets {
	p := s.haPathsFor()
	isMaster := in.HA.Model != 2
	on := in.HA.Backup == 1

	// self 是**这台机器**的名字，peerIP 是对端的地址 —— ha.cf 的 ucast 要填对端。
	self, peerIP := in.HA.Name, in.HA.SlaveIP
	if !isMaster {
		self, peerIP = in.HA.SlaveName, in.HA.MasterIP
	}

	t := haTargets{}

	// ── haresources：只换行首的服务器名，后面那段 `<ip>/<前缀>/eth0 ha-post` 原样留着 ──
	//
	// 旧版正是这么做的（`strstr($gettext,' ')` 取第一个空格之后的全部），
	// 因为地址那一半归「服务器基本信息」那一页管，这一页只负责名字。
	for _, f := range p.HAResources {
		t.edits = append(t.edits, haEdit{path: f, re: reHAResHeadRe,
			line: in.HA.Name, what: "主备资源行的服务器名（haresources）", sub: 1})
	}

	// ── ha.cf ──
	t.edits = append(t.edits,
		haEdit{path: p.HACf, re: reHANodeLine, line: "node " + in.HA.Name, what: "主机节点名（ha.cf node）"},
		haEdit{path: p.HACf, re: reHANodeLine, line: "node " + in.HA.SlaveName, what: "备机节点名（ha.cf node）", nth: 1},
		haEdit{path: p.HACf, re: reHADeadtime, line: "deadtime 15", what: "ha.cf deadtime"},
		haEdit{path: p.HACf, re: reHAInitdead, line: "initdead 30", what: "ha.cf initdead"},
		haEdit{path: p.HACf, re: reHABcast, line: "bcast  eth0", what: "ha.cf bcast"},
		haEdit{path: p.HACf, re: reHAUcast, line: "ucast eth0 " + peerIP, what: "ha.cf ucast（指向对端）"},
	)

	// ── /etc/hosts：回环那一行写本机名，另外两行分别是主机与备机 ──
	t.edits = append(t.edits,
		haEdit{path: p.Hosts, re: reHostLoop, line: "127.0.0.1  " + self, what: "hosts 回环行（本机名）", nth: 0})
	if re := hostsEntryFor(in.HA.Name); re != nil {
		t.edits = append(t.edits, haEdit{path: p.Hosts, re: re, line: in.HA.MasterIP + "  " + in.HA.Name, what: "hosts 主机条目", nth: 0})
	}
	if re := hostsEntryFor(in.HA.SlaveName); re != nil {
		t.edits = append(t.edits, haEdit{path: p.Hosts, re: re, line: in.HA.SlaveIP + "  " + in.HA.SlaveName, what: "hosts 备机条目", nth: 0})
	}

	// ── /etc/hostname：整个文件就一行 ──
	t.edits = append(t.edits, haEdit{path: p.Hostname, re: regexp.MustCompile(`(?s)\A.*\z`), line: self + "\n", what: "主机名（/etc/hostname）", nth: 0})

	// ── rsync 的同步目标：永远是备机 ──
	if p.CrsyncSh != "" {
		t.edits = append(t.edits, haEdit{path: p.CrsyncSh, re: reRsyncDstIP, line: "dstip=" + in.HA.SlaveIP, what: "同步目标（crsync.sh dstip）", nth: 0})
	}

	// ── 按角色换文件 ──
	if p.MysqlDir != "" {
		src := filepath.Join(p.MysqlDir, "server.cnf") // 关掉主备时用单机那份
		switch {
		case on && isMaster:
			src = filepath.Join(p.MysqlDir, "server-master.cnf")
		case on && !isMaster:
			src = filepath.Join(p.MysqlDir, "server-slave.cnf")
		}
		t.copies = append(t.copies, haCopy{src, p.MyCnfTarget, 0o644, "MySQL 复制配置（server.cnf）"})
	}
	if p.ScriptDir != "" {
		name := "apprun-master.sh"
		if !isMaster {
			name = "apprun-slave.sh"
		}
		t.copies = append(t.copies, haCopy{filepath.Join(p.ScriptDir, name), p.AppRun, 0o755,
			"启动脚本（apprun.sh）"})
	}

	// ── 只有备机才有的那几项 ──
	if !isMaster {
		if p.CrontabSrc != "" {
			t.copies = append(t.copies, haCopy{p.CrontabSrc, p.Crontab, 0o644, "备机定时任务（/etc/crontab）"})
		}
		if p.TimeUpdate != "" {
			t.edits = append(t.edits, haEdit{path: p.TimeUpdate, re: reNtpdate, line: "ntpdate -u " + in.HA.MasterIP, what: "备机校时目标（timeupdate.sh）", nth: 0})
		}
		if on && p.StartSlave != "" {
			// modify_cala_backup()：把复制起点指向主机。
			// 旧版是按字节偏移拼接（strpos+13 / strpos-1），这里认 master_host 这个键。
			t.edits = append(t.edits, haEdit{path: p.StartSlave, re: reMasterHost, line: "master_host='" + in.HA.MasterIP + "'", what: "备机复制起点（mysqlstartslave.sql）", nth: 0})
		}
	}
	return t
}

// haChanged 判断主备那几项有没有动过。没动就一个文件都不碰。
func haChanged(before *Params, in Input) bool {
	return before.HA.Model != in.HA.Model ||
		before.HA.Name != in.HA.Name ||
		before.HA.SlaveName != in.HA.SlaveName ||
		before.HA.MasterIP != in.HA.MasterIP ||
		before.HA.SlaveIP != in.HA.SlaveIP ||
		before.HA.Backup != in.HA.Backup
}

// syncHAFiles 把这台机器按主/备角色配好。**要么全做，要么全不做**。
func (s *Service) syncHAFiles(in Input) []FileSync {
	t := s.buildHATargets(in)

	// ── 先整体探一遍 ──
	//
	// 存在但写不进去的（多半是 /etc 下那几个 root 文件），整组放弃。
	// 不存在的跳过：这台机器没装那套东西，不是故障。
	var blocked []string
	for _, e := range t.edits {
		if why := probeWritable(e.path); why != "" {
			blocked = append(blocked, fmt.Sprintf("%s（%s）", e.path, why))
		}
	}
	for _, c := range t.copies {
		if _, err := os.Stat(c.src); err != nil {
			continue // 源文件没有，这一条本来就做不了，不算阻塞
		}
		if why := probeWritable(c.dst); why != "" {
			blocked = append(blocked, fmt.Sprintf("%s（%s）", c.dst, why))
		}
	}
	if len(blocked) > 0 {
		return []FileSync{{
			What:   "主备角色配置（整组未执行）",
			Status: SyncFailed,
			Detail: "这些文件改不了，为免留下改了一半的 HA 配置，整组都没有动：\n" +
				strings.Join(blocked, "\n"),
		}}
	}

	// ── 再整体写 ──
	out := []FileSync{}
	for _, e := range t.edits {
		out = append(out, applyHAEdit(e))
	}
	for _, c := range t.copies {
		out = append(out, copyFileAs(c))
	}
	return append(out, haSkipped(in)...)
}

// probeWritable 返回空串表示「可以写」或「文件不存在（跳过）」，
// 否则返回说不能写的原因。
//
// 探的办法是真在同目录建一个临时文件 —— 读 mode 位判不出 ACL、只读挂载、
// SELinux 这些情况，而这三样在麒麟上都可能遇到。
func probeWritable(path string) string {
	if strings.TrimSpace(path) == "" {
		return ""
	}
	st, err := os.Stat(path)
	if err != nil {
		return "" // 不存在 = 跳过，不是阻塞
	}
	if st.IsDir() {
		// 这个位置上本该是文件。写不进去，而且真去写会在 rename 那一步才炸，
		// 那时候前面几个文件已经改了 —— 所以在探的阶段就拦住。
		return "这里是一个目录，不是配置文件"
	}
	f, err := os.CreateTemp(filepath.Dir(path), ".htweb-probe-*")
	if err != nil {
		return "没有写权限"
	}
	name := f.Name()
	_ = f.Close()
	_ = os.Remove(name)
	return ""
}

// applyHAEdit 执行一条内容替换。
func applyHAEdit(e haEdit) FileSync {
	out := FileSync{Path: e.path, What: e.what}
	raw, err := os.ReadFile(e.path)
	if os.IsNotExist(err) {
		out.Status = SyncMissing
		out.Detail = "文件不存在（这台机器可能没装这一套）"
		return out
	}
	if err != nil {
		out.Status = SyncFailed
		out.Detail = "读取失败：" + err.Error()
		return out
	}

	locs := e.re.FindAllSubmatchIndex(raw, -1)
	if len(locs) <= e.nth {
		// ⚠ 到这一步旧版会去改第 211 行 / 第 9 行。这里什么都不做。
		out.Status = SyncNoAnchor
		out.Detail = fmt.Sprintf("没找到第 %d 处要改的内容（不按行号猜，已原样保留）", e.nth+1)
		return out
	}
	loc := locs[e.nth]

	// sub 非 0 时只换那个捕获组，行里其余部分原样保留。
	//
	// ⚠ 这里刻意**不用** re.Expand 的 `${1}` 写法：要替换进去的是服务器名，
	//   名字里真出现一个 $ 就会被 Expand 当成组引用解释掉。直接按下标切更老实。
	start, end := loc[0], loc[1]
	if e.sub > 0 {
		if 2*e.sub+1 >= len(loc) || loc[2*e.sub] < 0 {
			out.Status = SyncNoAnchor
			out.Detail = "那一行的形态和预期不一样（已原样保留）"
			return out
		}
		start, end = loc[2*e.sub], loc[2*e.sub+1]
	}
	line := e.line
	if string(raw[start:end]) == line {
		out.Status = SyncUnchanged
		return out
	}
	updated := append([]byte{}, raw[:start]...)
	updated = append(updated, []byte(line)...)
	updated = append(updated, raw[end:]...)
	if err := atomicWrite(e.path, updated); err != nil {
		out.Status = SyncFailed
		out.Detail = err.Error()
		return out
	}
	out.Status = SyncUpdated
	out.Detail = strings.TrimSpace(line)
	return out
}

// copyFileAs 把 src 原样拷成 dst 并设好权限。原子替换，理由与 atomicWrite 一致。
func copyFileAs(c haCopy) FileSync {
	out := FileSync{Path: c.dst, What: c.what}
	raw, err := os.ReadFile(c.src)
	if os.IsNotExist(err) {
		out.Status = SyncMissing
		out.Detail = "源文件不存在：" + c.src
		return out
	}
	if err != nil {
		out.Status = SyncFailed
		out.Detail = "读取 " + c.src + " 失败：" + err.Error()
		return out
	}
	if old, rerr := os.ReadFile(c.dst); rerr == nil && string(old) == string(raw) {
		out.Status = SyncUnchanged
		return out
	}
	if err := os.MkdirAll(filepath.Dir(c.dst), 0o755); err != nil {
		out.Status = SyncFailed
		out.Detail = err.Error()
		return out
	}
	if err := atomicWrite(c.dst, raw); err != nil {
		out.Status = SyncFailed
		out.Detail = err.Error()
		return out
	}
	if err := os.Chmod(c.dst, c.mode); err != nil {
		out.Status = SyncFailed
		out.Detail = "设置权限失败：" + err.Error()
		return out
	}
	out.Status = SyncUpdated
	out.Detail = "← " + c.src
	return out
}

// haSkipped 是旧版做了、这里**有意没做**的那几件，作为结果的一部分回给界面 ——
// 让人知道还差什么，而不是以为全套都做完了。
func haSkipped(in Input) []FileSync {
	out := []FileSync{}
	if in.HA.Model != 2 {
		out = append(out, FileSync{
			Path: "/etc/crontab", What: "主机上删掉系统 crontab", Status: SyncNoAnchor,
			Detail: "旧版在这里 `rm -rf /etc/crontab`（主机不跑备机那几条定时任务）。" +
				"删系统 crontab 不可逆、也删掉了这台机器上别人加的任务，没有照做，请人工确认。",
		})
	} else if in.HA.Backup == 1 {
		out = append(out, FileSync{
			Path: "script/mysqldel.sh", What: "备机的 mysqldel.sh", Status: SyncNoAnchor,
			Detail: "旧版在这里 `mv mysqldel-bdata mysqldel.sh -f`（一次性移动，跑第二次就没源文件了）。" +
				"这属于换脚本不是改配置，没有照做，请人工确认。",
		})
	}
	return out
}
