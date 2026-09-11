package serverparam

import (
	"os"
	"path/filepath"
	"strings"
	"testing"
)

// 搭一棵能跑完整套主备配置的临时 a9000 目录树。
// /etc 下那几个是绝对路径，测试里碰不到 —— 它们会报 missing，
// 与「没装 heartbeat 的机器」上的真实结果一致。
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
	s := &Service{a9000Root: root}
	got := s.syncHAFiles(haInput(1, 1))

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
	// 主机那条 rm -rf /etc/crontab 必须如实报出来没做
	if f := statusOf(got, "删掉系统 crontab"); f == nil {
		t.Error("主机应当报出「没做 rm -rf /etc/crontab」这一条")
	}
}

// 备机：换 server-slave.cnf、apprun-slave.sh、crontab，ucast 指向**主机**，
// 校时目标和复制起点都指向主机。
func TestHASyncSlave(t *testing.T) {
	root := haFixture(t)
	s := &Service{a9000Root: root}
	got := s.syncHAFiles(haInput(2, 1))

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
	s := &Service{a9000Root: root}
	s.syncHAFiles(haInput(1, 0))
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

	s := &Service{a9000Root: root}
	got := s.syncHAFiles(haInput(1, 1))

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

// /etc/hosts 认主机名，不认行号 —— 各发行版上面那几行注释数量都不一样。
func TestHostsEntryAnchor(t *testing.T) {
	dir := t.TempDir()
	p := write(t, dir, "hosts", `# 注释一
127.0.0.1  localhost
# 注释二
::1  ip6-localhost
192.168.2.51  a9000-master
192.168.2.52  a9000-slave
`)
	re := hostsEntryFor("a9000-slave")
	if r := applyHAEdit(haEdit{path: p, re: re, line: "10.0.0.2  a9000-slave", what: "x"}); r.Status != SyncUpdated {
		t.Fatalf("%+v", r)
	}
	got := read(t, p)
	if !strings.Contains(got, "10.0.0.2  a9000-slave") {
		t.Errorf("备机条目没改对：%q", got)
	}
	if !strings.Contains(got, "127.0.0.1  localhost") || !strings.Contains(got, "::1  ip6-localhost") {
		t.Errorf("别的行被动了：%q", got)
	}
}
