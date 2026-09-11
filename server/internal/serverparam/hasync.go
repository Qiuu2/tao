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
	// /etc/hosts 是整份重写，不走 edits（理由见 hostsRewrite）
	hosts *hostsRewrite
	// 换文件的（把 src 拷成 dst）
	copies []haCopy
	// 搬文件的（旧版用 mv -f）
	moves []haMove
	// 删文件的（旧版用 rm -rf）
	removes []haRemove
}

type haMove struct {
	src, dst string
	what     string
}

type haRemove struct {
	path string
	what string
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

// hostsRewrite 是 /etc/hosts 的整份重写计划。
//
// # 为什么这一个文件不按锚点改，而是整份重写
//
// 另外那几个文件都能「认一行、换一行」。/etc/hosts 不行，有两个原因：
//
//  1. **改名字的时候没有锚点可认**。旧版是 `sed -i '9c <主机IP>  <主机名>'`、
//     `sed -i '10c <备机IP>  <备机名>'` —— 按行号写，所以名字改成什么都写得进去。
//     换成「找到写着新名字的那一行再改」就不成立了：把 ha51 改名叫 a9000-master
//     时，文件里根本没有哪一行写着 a9000-master，于是一行都不写，
//     库里名字变了、hosts 里还是旧名 —— 主备互相认不出对方。
//     所以要认的是**旧名**，而旧名只有 before 知道。
//
//  2. **不能碰 localhost 那一行**。旧版 `sed -i '2c 127.0.0.1  <本机名>'` 写死第 2 行；
//     在 Debian/Ubuntu/麒麟上第 2 行是 `127.0.1.1 <hostname>`，不是 localhost。
//     而「文件里第一条 127.0.0.1」在绝大多数机器上恰恰**就是** localhost 那一行 ——
//     照这个锚点改下去，`127.0.0.1 localhost` 就没了，这台机器上一大票
//     连本地回环的东西会立刻出问题。Go 的 RE2 没有负向前瞻，
//     「127.0.0.1 开头但不是 localhost 的那一行」写不成一条正则。
//
// 于是改成：**先按主机名清理，再把三行补回去**。
// 清理只针对 purge 里那几个名字（新旧本机名 / 主机名 / 备机名），
// 别人加的条目一律不动，写着 localhost 的行更是永远不碰。
type hostsRewrite struct {
	path string
	// purge 是要从文件里清掉的主机名 —— **新旧都要在内**，
	// 否则改名之后旧名那一行会留在文件里指着同一个地址。
	purge []string
	// entries 按这个顺序补回文件末尾。
	// 本机名那一条排在最前：主机上本机名同时出现在回环行和主机行，
	// 解析取文件里第一条，排前面才能让本机名解到 127.0.0.1（与旧版第 2 行一致）。
	entries []hostsEntry
}

type hostsEntry struct {
	ip, name string
}

var (
	reHANodeLine  = regexp.MustCompile(`(?m)^[ \t]*node[ \t]+.*$`)
	reHADeadtime  = regexp.MustCompile(`(?m)^[ \t]*deadtime[ \t]+.*$`)
	reHAInitdead  = regexp.MustCompile(`(?m)^[ \t]*initdead[ \t]+.*$`)
	reHABcast     = regexp.MustCompile(`(?m)^[ \t]*bcast[ \t]+.*$`)
	reHAUcast     = regexp.MustCompile(`(?m)^[ \t]*ucast[ \t]+.*$`)
	reRsyncDstIP  = regexp.MustCompile(`(?m)^[ \t]*dstip=.*$`)
	reNtpdate     = regexp.MustCompile(`(?m)^[ \t]*ntpdate[ \t]+.*$`)
	reMasterHost  = regexp.MustCompile(`master_host\s*=\s*'[^']*'`)
	reHAResHeadRe = regexp.MustCompile(`(?m)^([^\s#]+)(\s.*\bha-post[ \t]*)$`)
)

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
	MysqlDelSrc string
	MysqlDelDst string
}

// etc 给 /etc 下的绝对路径加上测试前缀（生产里 etcRoot 为空，原样返回）。
func (s *Service) etc(p string) string {
	if strings.TrimSpace(s.etcRoot) == "" {
		return p
	}
	return filepath.Join(s.etcRoot, p)
}

func (s *Service) haPathsFor() haPaths {
	root := strings.TrimSpace(s.a9000Root)
	p := haPaths{
		HACf:     s.etc("/etc/ha.d/ha.cf"),
		Hosts:    s.etc("/etc/hosts"),
		Hostname: s.etc("/etc/hostname"),
		Crontab:  s.etc("/etc/crontab"),
	}
	p.HAResources = []string{s.etc("/etc/ha.d/haresources")}
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
	p.MysqlDelSrc = filepath.Join(root, "script/mysqldel-bdata")
	p.MysqlDelDst = filepath.Join(root, "script/mysqldel.sh")
	return p
}

// buildHATargets 按「这台机器要当主机还是备机」列出全部改动。
//
// isMaster 来自 HA.Model（1 = 主机，2 = 备机）；
// on 来自 HA.Backup（旧版的 serveritem / openorclose：1 = 启用主备，0 = 关掉）。
func (s *Service) buildHATargets(before *Params, in Input) haTargets {
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
	//
	// 对应旧版这三条：
	//
	//	sed -i '2c 127.0.0.1  <本机名>'   /etc/hosts
	//	sed -i '9c <主机IP>  <主机名>'    /etc/hosts
	//	sed -i '10c <备机IP>  <备机名>'   /etc/hosts
	//
	// 这里改成整份重写 —— 按行号写在改名字时会写错地方，理由见 hostsRewrite。
	h := &hostsRewrite{path: p.Hosts}
	h.purge = []string{self, in.HA.Name, in.HA.SlaveName}
	if before != nil {
		// 改名的场合，文件里写着的是**旧**名字 —— 不把旧名一起清掉，
		// 补完新行之后旧名那一条还留在文件里，指着同一个地址。
		oldSelf := before.HA.Name
		if before.HA.Model == 2 {
			oldSelf = before.HA.SlaveName
		}
		h.purge = append(h.purge, oldSelf, before.HA.Name, before.HA.SlaveName)
	}
	// 本机名 → 回环。主机上 self 就是主机名，于是它在下面的主机条目里还会
	// 出现一次；这是旧版的样子（第 2 行 + 第 9 行），保持一致。
	if strings.TrimSpace(self) != "" {
		h.entries = append(h.entries, hostsEntry{"127.0.0.1", self})
	}
	// 地址或名字缺一个就不补这一条 —— 宁可少一行，也不写半行进去。
	if strings.TrimSpace(in.HA.Name) != "" && strings.TrimSpace(in.HA.MasterIP) != "" {
		h.entries = append(h.entries, hostsEntry{in.HA.MasterIP, in.HA.Name})
	}
	if strings.TrimSpace(in.HA.SlaveName) != "" && strings.TrimSpace(in.HA.SlaveIP) != "" {
		h.entries = append(h.entries, hostsEntry{in.HA.SlaveIP, in.HA.SlaveName})
	}
	t.hosts = h

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

	// ── 主机：把系统 crontab 删掉 ──
	//
	// 旧版是 `rm -rf /etc/crontab` —— 主机不跑备机那几条定时任务。
	// ⚠ 删之前先留一份 .htweb.bak（脚本那边做），至少还能退回去：
	//   这台机器上可能有别人加的任务，一删就没了。
	if isMaster {
		t.removes = append(t.removes, haRemove{p.Crontab, "主机上删掉系统 crontab"})
	}

	// ── 只有备机才有的那几项 ──
	if !isMaster {
		if p.CrontabSrc != "" {
			t.copies = append(t.copies, haCopy{p.CrontabSrc, p.Crontab, 0o644, "备机定时任务（/etc/crontab）"})
		}
		if p.TimeUpdate != "" {
			t.edits = append(t.edits, haEdit{path: p.TimeUpdate, re: reNtpdate, line: "ntpdate -u " + in.HA.MasterIP, what: "备机校时目标（timeupdate.sh）", nth: 0})
		}
		if on && p.MysqlDelSrc != "" {
			// 旧版 `mv mysqldel-bdata mysqldel.sh -f`：备机要换一份删库脚本。
			// 照做 mv（不是 cp）—— 与旧版一致；源文件已经不在、目标又在的话
			// 说明上次就搬过了，报 unchanged 而不是报错。
			t.moves = append(t.moves, haMove{p.MysqlDelSrc, p.MysqlDelDst, "备机的 mysqldel.sh"})
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
func (s *Service) syncHAFiles(before *Params, in Input) []FileSync {
	t := s.buildHATargets(before, in)

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
	if t.hosts != nil {
		if why := probeWritable(t.hosts.path); why != "" {
			blocked = append(blocked, fmt.Sprintf("%s（%s）", t.hosts.path, why))
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
	for _, m := range t.moves {
		if _, err := os.Stat(m.src); err != nil {
			continue // 上次已经搬过了，或者这台机器没有这个脚本
		}
		// 搬走要动的是**源文件所在目录**（unlink）和目标目录（创建）
		if why := probeDirWritable(filepath.Dir(m.src)); why != "" {
			blocked = append(blocked, fmt.Sprintf("%s（%s）", m.src, why))
		}
		if why := probeDirWritable(filepath.Dir(m.dst)); why != "" {
			blocked = append(blocked, fmt.Sprintf("%s（%s）", m.dst, why))
		}
	}
	for _, r := range t.removes {
		if _, err := os.Stat(r.path); err != nil {
			continue // 已经不在了
		}
		// 删一个文件要的是**它所在目录**的写权限，不是文件本身的
		if probeDirWritable(filepath.Dir(r.path)) == "" {
			continue
		}
		if _, _, ok := canPrivWrite(r.path); ok {
			continue // /etc/crontab 这种可以走提权脚本
		}
		blocked = append(blocked, fmt.Sprintf("%s（没有删除权限，也没有可用的提权通道）", r.path))
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
	if t.hosts != nil {
		out = append(out, applyHostsRewrite(*t.hosts))
	}
	for _, c := range t.copies {
		out = append(out, copyFileAs(c))
	}
	for _, m := range t.moves {
		out = append(out, moveFile(m))
	}
	for _, r := range t.removes {
		out = append(out, removeFile(r))
	}
	return out
}

// probeDirWritable 探一个目录能不能建/删文件。返回空串表示可以。
func probeDirWritable(dir string) string {
	f, err := os.CreateTemp(dir, ".htweb-probe-*")
	if err != nil {
		return "目录不可写"
	}
	name := f.Name()
	_ = f.Close()
	_ = os.Remove(name)
	return ""
}

// moveFile 对应旧版的 `mv <src> <dst> -f`。
//
// 源文件不在、目标已经在，说明上次就搬过了 —— 报 unchanged，不当成错误。
// 旧版跑第二次是静默失败（mv 报错没人看），这里如实说明状态。
func moveFile(m haMove) FileSync {
	out := FileSync{Path: m.dst, What: m.what}
	if _, err := os.Stat(m.src); err != nil {
		if _, derr := os.Stat(m.dst); derr == nil {
			out.Status = SyncUnchanged
			out.Detail = "上次已经搬过了（源文件 " + filepath.Base(m.src) + " 不在了）"
			return out
		}
		out.Status = SyncMissing
		out.Detail = "源文件不存在：" + m.src
		return out
	}
	if err := os.MkdirAll(filepath.Dir(m.dst), 0o755); err != nil {
		out.Status = SyncFailed
		out.Detail = err.Error()
		return out
	}
	if err := os.Rename(m.src, m.dst); err != nil {
		out.Status = SyncFailed
		out.Detail = "搬动失败：" + err.Error()
		return out
	}
	out.Status = SyncUpdated
	out.Detail = "← " + m.src + "（已搬走，原位置不再保留）"
	return out
}

// removeFile 对应旧版的 `rm -rf <path>`。
//
// ⚠ 删之前先留一份 .htweb.bak。旧版是直接删的，而 /etc/crontab 上可能有
//
//	这台机器上别人加的任务 —— 一删就没了，也没有任何地方能查回来。
func removeFile(r haRemove) FileSync {
	out := FileSync{Path: r.path, What: r.what}
	raw, err := os.ReadFile(r.path)
	if os.IsNotExist(err) {
		out.Status = SyncUnchanged
		out.Detail = "本来就不在"
		return out
	}
	if err != nil {
		out.Status = SyncFailed
		out.Detail = "读取失败：" + err.Error()
		return out
	}
	// 先备份再删。备份失败就不删 —— 不可逆的动作没有退路时不该做。
	if werr := os.WriteFile(r.path+".htweb.bak", raw, 0o600); werr != nil {
		if _, _, ok := canPrivWrite(r.path); !ok {
			out.Status = SyncFailed
			out.Detail = "留不下备份（" + werr.Error() + "），没有退路就不删"
			return out
		}
		// 走提权那条路时，备份由脚本自己做
	}
	if rerr := os.Remove(r.path); rerr != nil {
		if _, _, ok := canPrivWrite(r.path); ok {
			if perr := privRemove(r.path); perr != nil {
				out.Status = SyncFailed
				out.Detail = perr.Error()
				return out
			}
			out.Status = SyncUpdated
			out.Detail = "已删除（旁边留了 .htweb.bak）"
			return out
		}
		out.Status = SyncFailed
		out.Detail = "删除失败：" + rerr.Error()
		return out
	}
	out.Status = SyncUpdated
	out.Detail = "已删除（旁边留了 .htweb.bak）"
	return out
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
		// 直接写不了，再看有没有提权那条路（/etc 下那几个 root 文件）
		if _, _, ok := canPrivWrite(path); ok {
			return ""
		}
		return "没有写权限，也没有可用的提权通道（装一次 deploy/install-sudoers.sh）"
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

// applyHostsRewrite 重写 /etc/hosts：先清掉 purge 里那几个名字的条目，
// 再把 entries 那几行补到末尾。别的行一律原样留着。
func applyHostsRewrite(h hostsRewrite) FileSync {
	out := FileSync{Path: h.path, What: "主机名解析（/etc/hosts）"}
	raw, err := os.ReadFile(h.path)
	if os.IsNotExist(err) {
		out.Status = SyncMissing
		out.Detail = "文件不存在"
		return out
	}
	if err != nil {
		out.Status = SyncFailed
		out.Detail = "读取失败：" + err.Error()
		return out
	}
	if len(h.entries) == 0 {
		// 一条都算不出来（名字和地址都是空的）——那就什么都别动。
		out.Status = SyncUnchanged
		out.Detail = "服务器名称与主备地址都是空的，没有可写的条目"
		return out
	}

	drop := map[string]bool{}
	for _, n := range h.purge {
		if n = strings.TrimSpace(n); n != "" {
			drop[n] = true
		}
	}

	var keep []string
	body := strings.TrimRight(string(raw), "\n")
	if body != "" {
		for _, ln := range strings.Split(body, "\n") {
			if hostsLineDropped(ln, drop) {
				continue
			}
			keep = append(keep, ln)
		}
	}
	for _, e := range h.entries {
		keep = append(keep, e.ip+"  "+e.name)
	}
	updated := strings.Join(keep, "\n") + "\n"
	if updated == string(raw) {
		out.Status = SyncUnchanged
		return out
	}
	if err := atomicWrite(h.path, []byte(updated)); err != nil {
		out.Status = SyncFailed
		out.Detail = err.Error()
		return out
	}
	lines := make([]string, 0, len(h.entries))
	for _, e := range h.entries {
		lines = append(lines, e.ip+"  "+e.name)
	}
	out.Status = SyncUpdated
	out.Detail = strings.Join(lines, "；")
	return out
}

// hostsLineDropped 判断 /etc/hosts 的某一行该不该清掉。
//
// 只清「主机名落在 drop 里」的行。空行、注释行、以及**任何写着 localhost 的行**
// 都留着 —— 后者尤其重要：`127.0.0.1 localhost` 一旦没了，
// 这台机器上一大票连本地回环的东西会立刻出问题，而那时候界面也已经打不开了。
func hostsLineDropped(line string, drop map[string]bool) bool {
	body := line
	if i := strings.IndexByte(body, '#'); i >= 0 {
		body = body[:i]
	}
	f := strings.Fields(body)
	if len(f) < 2 {
		return false // 注释、空行、或者只有一个地址没有名字
	}
	hit := false
	for _, n := range f[1:] {
		if isLocalhostName(n) {
			return false
		}
		if drop[n] {
			hit = true
		}
	}
	return hit
}

// isLocalhostName 认出回环那几个保留名（含 IPv6 那几条和 localhost.localdomain）。
func isLocalhostName(n string) bool {
	l := strings.ToLower(n)
	switch l {
	case "localhost", "localhost.localdomain", "localhost4", "localhost6",
		"localhost4.localdomain4", "localhost6.localdomain6",
		"ip6-localhost", "ip6-loopback", "ip6-localnet",
		"ip6-mcastprefix", "ip6-allnodes", "ip6-allrouters":
		return true
	}
	return strings.HasPrefix(l, "localhost.")
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
