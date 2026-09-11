package serverparam

import (
	"os"
	"path/filepath"
	"strings"
	"testing"
)

// 搭一棵能跑完整套主备配置的临时 a9000 目录树。
//
// ⚠ 每个用例都要给 Service 设 etcRoot（一个临时目录），否则 /etc/hosts、
//
//	/etc/hostname 是绝对路径，跑一次单测就把这台机器的真文件改了。
//	这不是假想：第一版没有 etcRoot，跑测试真的把开发机的 /etc/hosts 写了。
func haFixture(t *testing.T) (root string) {
	t.Helper()
	root = t.TempDir()
	write(t, root, "home/heartbeat/haresources", "old-name 192.168.2.159/24/eth0 ha-post\n")
	write(t, root, "home/heartbeat/crontab-slave", "* * * * * root /opt/apps/a9000/script/timeupdate.sh\n")
	write(t, root, "home/rsync/crsync.sh", "#!/bin/sh\ndstip=1.1.1.1\necho sync\n")
	write(t, root, "home/mysql/server.cnf", "# 单机\n")
	write(t, root, "home/mysql/server-master.cnf", "# 主机\nlog-bin=mysql-bin\n")
	write(t, root, "home/mysql/server-slave.cnf", "# 备机\nread_only=1\n")
	write(t, root, "home/mysql/my.cnf.d/server.cnf", "# 旧的\n")
	write(t, root, "script/apprun-master.sh", "#!/bin/sh\n# master\n")
	write(t, root, "script/apprun-slave.sh", "#!/bin/sh\n# slave\n")
	write(t, root, "script/apprun.sh", "#!/bin/sh\n# 旧的\n")
	write(t, root, "script/timeupdate.sh", "#!/bin/sh\n# 十行占位\nntpdate -u 1.1.1.1\n")
	write(t, root, "script/mysqlstartslave.sql",
		"CHANGE MASTER TO master_host='1.1.1.1',master_user='rep',master_password='x';\nSTART SLAVE;\n")
	write(t, root, "script/mysqldel-bdata", "#!/bin/sh\n# 备机用的删库脚本\n")
	write(t, root, "script/mysqldel.sh", "#!/bin/sh\n# 单机用的\n")
	return root
}

func haInput(model int, backup int) Input {
	in := Input{}
	in.HA.Model = model
	in.HA.Backup = backup
	in.HA.Name = "a9000-master"
	in.HA.SlaveName = "a9000-slave"
	in.HA.MasterIP = "192.168.2.51"
	in.HA.SlaveIP = "192.168.2.52"
	return in
}

// haBefore 造一份「保存之前」的库内容 —— 用的是现网那对旧名字。
// /etc/hosts 要靠它才知道该清掉哪几行：改名之后文件里写着的是**旧**名。
func haBefore() *Params {
	p := &Params{}
	p.HA.Model = 1
	p.HA.Name = "ha51"
	p.HA.SlaveName = "ha52"
	p.HA.MasterIP = "12.12.2.51"
	p.HA.SlaveIP = "12.12.2.52"
	return p
}

func statusOf(got []FileSync, what string) *FileSync {
	for i := range got {
		if strings.Contains(got[i].What, what) {
			return &got[i]
		}
	}
	return nil
}

// 主机：换 server-master.cnf 与 apprun-master.sh，ucast 指向**备机**。
func TestHASyncMaster(t *testing.T) {
	root := haFixture(t)
	s := &Service{a9000Root: root, etcRoot: t.TempDir()}
	got := s.syncHAFiles(haBefore(), haInput(1, 1))

	for _, f := range got {
		if f.Status == SyncFailed {
			t.Errorf("不该失败：%s → %s", f.What, f.Detail)
		}
	}

	if v := read(t, filepath.Join(root, "home/mysql/my.cnf.d/server.cnf")); !strings.Contains(v, "log-bin") {
		t.Errorf("主机该用 server-master.cnf，实际：%q", v)
	}
	if v := read(t, filepath.Join(root, "script/apprun.sh")); !strings.Contains(v, "# master") {
		t.Errorf("主机该用 apprun-master.sh，实际：%q", v)
	}
	// haresources 只换名字，地址那一半原样留着
	if v := read(t, filepath.Join(root, "home/heartbeat/haresources")); v != "a9000-master 192.168.2.159/24/eth0 ha-post\n" {
		t.Errorf("haresources 只该换行首的名字：%q", v)
	}
	// dstip 永远是备机
	if v := read(t, filepath.Join(root, "home/rsync/crsync.sh")); !strings.Contains(v, "dstip=192.168.2.52") {
		t.Errorf("crsync 的 dstip 该是备机地址：%q", v)
	}
	// 主机不该去碰 timeupdate.sh / mysqlstartslave.sql
	if v := read(t, filepath.Join(root, "script/timeupdate.sh")); strings.Contains(v, "192.168.2.51") {
		t.Errorf("主机不该改 timeupdate.sh：%q", v)
	}
	// 主机要真的删掉系统 crontab（删前留 .htweb.bak）
	f := statusOf(got, "删掉系统 crontab")
	if f == nil {
		t.Fatal("主机应当有「删掉系统 crontab」这一条")
	}
	if f.Status != SyncUpdated && f.Status != SyncUnchanged {
		t.Errorf("crontab 那一条状态不对：%s（%s）", f.Status, f.Detail)
	}
}

// 备机：换 server-slave.cnf、apprun-slave.sh、crontab，ucast 指向**主机**，
// 校时目标和复制起点都指向主机。
func TestHASyncSlave(t *testing.T) {
	root := haFixture(t)
	s := &Service{a9000Root: root, etcRoot: t.TempDir()}
	got := s.syncHAFiles(haBefore(), haInput(2, 1))

	for _, f := range got {
		if f.Status == SyncFailed {
			t.Errorf("不该失败：%s → %s", f.What, f.Detail)
		}
	}
	if v := read(t, filepath.Join(root, "home/mysql/my.cnf.d/server.cnf")); !strings.Contains(v, "read_only") {
		t.Errorf("备机该用 server-slave.cnf，实际：%q", v)
	}
	if v := read(t, filepath.Join(root, "script/apprun.sh")); !strings.Contains(v, "# slave") {
		t.Errorf("备机该用 apprun-slave.sh，实际：%q", v)
	}
	if v := read(t, filepath.Join(root, "script/timeupdate.sh")); !strings.Contains(v, "ntpdate -u 192.168.2.51") {
		t.Errorf("备机该向主机校时：%q", v)
	}
	if v := read(t, filepath.Join(root, "script/mysqlstartslave.sql")); !strings.Contains(v, "master_host='192.168.2.51'") {
		t.Errorf("复制起点该指向主机：%q", v)
	}
	// 这一条最容易写反：把 master_user 那截吃掉就等于把复制配置毁了
	if v := read(t, filepath.Join(root, "script/mysqlstartslave.sql")); !strings.Contains(v, "master_user='rep'") {
		t.Errorf("master_host 之后的内容被吃掉了：%q", v)
	}
}

// 关掉主备（backup=0）时退回单机那份 server.cnf。
func TestHASyncBackupOff(t *testing.T) {
	root := haFixture(t)
	s := &Service{a9000Root: root, etcRoot: t.TempDir()}
	s.syncHAFiles(haBefore(), haInput(1, 0))
	if v := read(t, filepath.Join(root, "home/mysql/my.cnf.d/server.cnf")); !strings.Contains(v, "# 单机") {
		t.Errorf("关掉主备该用 server.cnf，实际：%q", v)
	}
}

// ★ 整组要么全做要么全不做：只要有一个存在的目标写不进去，**一个文件都不能动**。
//
// 改一半的 HA 配置（名字改了、ha.cf 没改）是主备双活的前提 —— 两台机器
// 同时抢一个虚拟 IP，比一点没改糟得多。
func TestHASyncAllOrNothing(t *testing.T) {
	root := haFixture(t)

	// 制造一个「文件在、但写不进去」的目标：把 crsync.sh 换成一个**目录**。
	// 这样 root 跑测试时也能触发（权限位拦不住 root，目录拦得住），
	// 而且它本身就是现实里会遇到的一种坏情况。
	crsync := filepath.Join(root, "home/rsync/crsync.sh")
	if err := os.Remove(crsync); err != nil {
		t.Fatal(err)
	}
	if err := os.Mkdir(crsync, 0o755); err != nil {
		t.Fatal(err)
	}

	before := read(t, filepath.Join(root, "home/heartbeat/haresources"))
	beforeCnf := read(t, filepath.Join(root, "home/mysql/my.cnf.d/server.cnf"))

	s := &Service{a9000Root: root, etcRoot: t.TempDir()}
	got := s.syncHAFiles(haBefore(), haInput(1, 1))

	if len(got) != 1 || got[0].Status != SyncFailed {
		t.Fatalf("应当整组放弃并只回一条说明，实际 %d 条：%+v", len(got), got)
	}
	if !strings.Contains(got[0].Detail, "整组都没有动") {
		t.Errorf("说明里要讲清楚整组都没动：%q", got[0].Detail)
	}
	if read(t, filepath.Join(root, "home/heartbeat/haresources")) != before {
		t.Error("haresources 被改了 —— 整组放弃时一个文件都不该动")
	}
	if read(t, filepath.Join(root, "home/mysql/my.cnf.d/server.cnf")) != beforeCnf {
		t.Error("server.cnf 被换了 —— 整组放弃时一个文件都不该动")
	}
}

// 主备那几项没动时一个文件都不碰。
func TestHAChanged(t *testing.T) {
	before := &Params{}
	before.HA.Model = 1
	before.HA.Name = "m"
	before.HA.SlaveName = "s"
	before.HA.MasterIP = "1.1.1.1"
	before.HA.SlaveIP = "1.1.1.2"
	before.HA.Backup = 1

	same := Input{}
	same.HA = before.HA
	if haChanged(before, same) {
		t.Error("一模一样时不该判成有变化")
	}
	changed := same
	changed.HA.SlaveIP = "1.1.1.9"
	if !haChanged(before, changed) {
		t.Error("备机地址变了应当判成有变化")
	}
}

// ha.cf 里 node 有两行：第一行写主机名、第二行写备机名，**不能都写成同一个**。
func TestHACfTwoNodes(t *testing.T) {
	dir := t.TempDir()
	p := write(t, dir, "ha.cf", "logfile /var/log/ha-log\nnode  old-master\nnode  old-slave\nauto_failback on\n")

	if r := applyHAEdit(haEdit{path: p, re: reHANodeLine, line: "node a9000-master", what: "x"}); r.Status != SyncUpdated {
		t.Fatalf("%+v", r)
	}
	if r := applyHAEdit(haEdit{path: p, re: reHANodeLine, line: "node a9000-slave", what: "x", nth: 1}); r.Status != SyncUpdated {
		t.Fatalf("%+v", r)
	}
	got := read(t, p)
	want := "logfile /var/log/ha-log\nnode a9000-master\nnode a9000-slave\nauto_failback on\n"
	if got != want {
		t.Errorf("两个 node 行没分别写对\n得到 %q\n期望 %q", got, want)
	}
}

// 一份 Ubuntu / 麒麟上常见的 /etc/hosts：第一条 127.0.0.1 就是 localhost，
// 第 2 行是 127.0.1.1（旧版 `sed -i '2c ...'` 打的正是这一行）。
const hostsFixture = `127.0.0.1	localhost
127.0.1.1	ha51
12.12.2.51  ha51
12.12.2.52  ha52
10.0.0.9  nas   # 机房里的存储，别人加的

::1     ip6-localhost ip6-loopback
ff02::1 ip6-allnodes
`

func hostsAfter(t *testing.T, etcRoot string) string {
	t.Helper()
	return read(t, filepath.Join(etcRoot, "etc/hosts"))
}

// 改名：库里从 ha51/ha52 改成 a9000-master/a9000-slave。
//
// 这是老实现漏掉的场合 —— 它按**新**名字找锚点，而文件里一行都没写着新名字，
// 于是主机条目和备机条目一条都不写，库里改完了、hosts 还是旧名。
func TestHostsRewriteRename(t *testing.T) {
	root := haFixture(t)
	etcRoot := t.TempDir()
	write(t, etcRoot, "etc/hosts", hostsFixture)
	s := &Service{a9000Root: root, etcRoot: etcRoot}

	got := s.syncHAFiles(haBefore(), haInput(1, 1))
	if f := statusOf(got, "主机名解析"); f == nil || f.Status != SyncUpdated {
		t.Fatalf("hosts 没写：%+v", f)
	}
	after := hostsAfter(t, etcRoot)

	for _, want := range []string{
		"127.0.0.1  a9000-master",    // 本机名 → 回环
		"192.168.2.51  a9000-master", // 主机条目（旧版第 9 行）
		"192.168.2.52  a9000-slave",  // 备机条目（旧版第 10 行）
	} {
		if !strings.Contains(after, want) {
			t.Errorf("缺 %q：\n%s", want, after)
		}
	}
	// 旧名字一条都不该留下 —— 留着就等于两个名字指着同一台机器
	for _, gone := range []string{"ha51", "ha52"} {
		if strings.Contains(after, gone) {
			t.Errorf("旧名 %q 还在：\n%s", gone, after)
		}
	}
	// 别人加的条目、localhost、IPv6 那几条都得原样留着
	for _, keep := range []string{"127.0.0.1\tlocalhost", "10.0.0.9  nas", "ip6-localhost", "ff02::1"} {
		if !strings.Contains(after, keep) {
			t.Errorf("不该动的 %q 没了：\n%s", keep, after)
		}
	}
}

// ⚠ 这一条盯的是最容易把机器弄哑的那个 bug：
// 「文件里第一条 127.0.0.1」在绝大多数机器上就是 `127.0.0.1 localhost`，
// 照着它改就把 localhost 冲掉了。
func TestHostsRewriteKeepsLocalhost(t *testing.T) {
	root := haFixture(t)
	etcRoot := t.TempDir()
	write(t, etcRoot, "etc/hosts", "127.0.0.1 localhost\n::1 localhost ip6-localhost\n")
	s := &Service{a9000Root: root, etcRoot: etcRoot}

	s.syncHAFiles(haBefore(), haInput(1, 1))
	after := hostsAfter(t, etcRoot)
	if !strings.Contains(after, "127.0.0.1 localhost\n") {
		t.Fatalf("localhost 那一行被动了：\n%s", after)
	}
	if !strings.Contains(after, "::1 localhost ip6-localhost") {
		t.Errorf("IPv6 的 localhost 被动了：\n%s", after)
	}
	if !strings.Contains(after, "127.0.0.1  a9000-master") {
		t.Errorf("本机名没补上：\n%s", after)
	}
}

// 主机上本机名同时出现在回环行和主机行（旧版第 2 行 + 第 9 行）。
// 解析取文件里**第一条**，所以回环那一行必须排在主机条目前面。
func TestHostsRewriteSelfBeforeMasterEntry(t *testing.T) {
	root := haFixture(t)
	etcRoot := t.TempDir()
	write(t, etcRoot, "etc/hosts", hostsFixture)
	s := &Service{a9000Root: root, etcRoot: etcRoot}

	s.syncHAFiles(haBefore(), haInput(1, 1))
	after := hostsAfter(t, etcRoot)
	loop := strings.Index(after, "127.0.0.1  a9000-master")
	real := strings.Index(after, "192.168.2.51  a9000-master")
	if loop < 0 || real < 0 || loop > real {
		t.Fatalf("本机名的回环行该排在主机条目前面（loop=%d real=%d）：\n%s", loop, real, after)
	}
}

// 备机上回环那一行写的是**备机**名。
func TestHostsRewriteSlaveSelf(t *testing.T) {
	root := haFixture(t)
	etcRoot := t.TempDir()
	write(t, etcRoot, "etc/hosts", hostsFixture)
	s := &Service{a9000Root: root, etcRoot: etcRoot}

	s.syncHAFiles(haBefore(), haInput(2, 1))
	after := hostsAfter(t, etcRoot)
	if !strings.Contains(after, "127.0.0.1  a9000-slave") {
		t.Fatalf("备机的回环行该写备机名：\n%s", after)
	}
	if strings.Contains(after, "127.0.0.1  a9000-master") {
		t.Errorf("备机上不该把主机名写到回环：\n%s", after)
	}
}

// 再存一次不该再动文件。
func TestHostsRewriteIdempotent(t *testing.T) {
	root := haFixture(t)
	etcRoot := t.TempDir()
	write(t, etcRoot, "etc/hosts", hostsFixture)
	s := &Service{a9000Root: root, etcRoot: etcRoot}

	s.syncHAFiles(haBefore(), haInput(1, 1))
	first := hostsAfter(t, etcRoot)

	// 第二次的 before 就是第一次写进去的那套名字
	before := &Params{}
	before.HA.Model, before.HA.Name, before.HA.SlaveName = 1, "a9000-master", "a9000-slave"
	got := s.syncHAFiles(before, haInput(1, 1))
	if f := statusOf(got, "主机名解析"); f == nil || f.Status != SyncUnchanged {
		t.Fatalf("第二次该是 unchanged：%+v", f)
	}
	if second := hostsAfter(t, etcRoot); second != first {
		t.Errorf("第二次把文件改了：\n%s\n---\n%s", first, second)
	}
}

// privTargets 与 deploy/htweb-ha-apply 里那张 case 表必须一一对应。
//
// 两边是分开维护的（一个 Go、一个 shell），漏改一边的表现是：
// htweb 觉得自己能写，脚本却回「不认识的目标名」—— 而那时候前面几个文件
// 可能已经改了。所以拿测试把它们钉在一起。
func TestPrivTargetsMatchHelperScript(t *testing.T) {
	raw, err := os.ReadFile(filepath.Join("..", "..", "..", "deploy", "htweb-ha-apply"))
	if err != nil {
		t.Skipf("找不到 deploy/htweb-ha-apply：%v", err)
	}
	script := string(raw)

	for path, target := range privTargets {
		// 脚本里那一行形如：  hosts)       DEST=/etc/hosts;             MODE=644 ;;
		if !strings.Contains(script, target+")") {
			t.Errorf("privTargets 里有 %q，脚本的 case 表里没有", target)
			continue
		}
		if !strings.Contains(script, "DEST="+path+";") {
			t.Errorf("目标名 %q 在脚本里指向的路径不是 %q", target, path)
		}
	}

	// 反向：脚本里 case 表出现的目标名，Go 这边也得认
	for _, line := range strings.Split(script, "\n") {
		line = strings.TrimSpace(line)
		if !strings.Contains(line, "DEST=/") || !strings.Contains(line, ")") {
			continue
		}
		name := strings.TrimSpace(strings.SplitN(line, ")", 2)[0])
		found := false
		for _, v := range privTargets {
			if v == name {
				found = true
				break
			}
		}
		if !found {
			t.Errorf("脚本里有目标名 %q，privTargets 里没有", name)
		}
	}
}

// 主机：真删 /etc/crontab，而且**删之前要留下 .htweb.bak** ——
// 那上面可能有这台机器上别人加的定时任务，旧版一删就没了。
func TestHARemoveCrontabKeepsBackup(t *testing.T) {
	root := haFixture(t)
	etc := t.TempDir()
	if err := os.WriteFile(filepath.Join(etc, "etc-crontab-placeholder"), []byte("x"), 0o644); err != nil {
		t.Fatal(err)
	}
	// 造出 <etcRoot>/etc/crontab
	if err := os.MkdirAll(filepath.Join(etc, "etc"), 0o755); err != nil {
		t.Fatal(err)
	}
	cron := filepath.Join(etc, "etc", "crontab")
	if err := os.WriteFile(cron, []byte("* * * * * root /别人的/任务.sh\n"), 0o644); err != nil {
		t.Fatal(err)
	}

	s := &Service{a9000Root: root, etcRoot: etc}
	got := s.syncHAFiles(haBefore(), haInput(1, 1))

	if _, err := os.Stat(cron); !os.IsNotExist(err) {
		t.Errorf("主机上 /etc/crontab 应当被删掉，err=%v", err)
	}
	bak, err := os.ReadFile(cron + ".htweb.bak")
	if err != nil {
		t.Fatalf("删之前必须留一份 .htweb.bak：%v", err)
	}
	if !strings.Contains(string(bak), "别人的/任务.sh") {
		t.Errorf("备份内容不对：%q", bak)
	}
	if f := statusOf(got, "删掉系统 crontab"); f == nil || f.Status != SyncUpdated {
		t.Errorf("结果里那一条不对：%+v", f)
	}
}

// 备机：mysqldel-bdata 真的搬成 mysqldel.sh，源文件不再保留。
func TestHAMoveMysqldel(t *testing.T) {
	root := haFixture(t)
	s := &Service{a9000Root: root, etcRoot: t.TempDir()}
	got := s.syncHAFiles(haBefore(), haInput(2, 1))

	dst := filepath.Join(root, "script/mysqldel.sh")
	if v := read(t, dst); !strings.Contains(v, "备机用的删库脚本") {
		t.Errorf("mysqldel.sh 该换成备机那份：%q", v)
	}
	if _, err := os.Stat(filepath.Join(root, "script/mysqldel-bdata")); !os.IsNotExist(err) {
		t.Errorf("旧版是 mv，源文件不该还在：err=%v", err)
	}
	if f := statusOf(got, "mysqldel"); f == nil || f.Status != SyncUpdated {
		t.Errorf("结果里那一条不对：%+v", f)
	}

	// 再跑一次：源文件已经没了、目标在，应当报 unchanged 而不是报错
	got2 := s.syncHAFiles(haBefore(), haInput(2, 1))
	if f := statusOf(got2, "mysqldel"); f == nil || f.Status != SyncUnchanged {
		t.Errorf("第二次应当报 unchanged：%+v", f)
	}
}

// 主机不该去搬 mysqldel，备机不该去删 crontab —— 角色搞反了后果都很实在。
func TestHARoleSpecificActions(t *testing.T) {
	root := haFixture(t)
	s := &Service{a9000Root: root, etcRoot: t.TempDir()}
	s.syncHAFiles(haBefore(), haInput(1, 1)) // 主机
	if _, err := os.Stat(filepath.Join(root, "script/mysqldel-bdata")); err != nil {
		t.Errorf("主机不该搬 mysqldel-bdata：err=%v", err)
	}

	root2 := haFixture(t)
	etc2 := t.TempDir()
	if err := os.MkdirAll(filepath.Join(etc2, "etc"), 0o755); err != nil {
		t.Fatal(err)
	}
	cron := filepath.Join(etc2, "etc", "crontab")
	if err := os.WriteFile(cron, []byte("keep\n"), 0o644); err != nil {
		t.Fatal(err)
	}
	s2 := &Service{a9000Root: root2, etcRoot: etc2}
	s2.syncHAFiles(haBefore(), haInput(2, 1)) // 备机
	if _, err := os.Stat(cron); err != nil {
		t.Errorf("备机不该删 /etc/crontab（它要换成 crontab-slave）：err=%v", err)
	}
}
