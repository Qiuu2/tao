package serverparam

import (
	"errors"
	"os"
	"path/filepath"
	"strings"
	"testing"
)

// sudo -n 在没有规则时回的是 "sudo: a password is required" —— 原样摆给运维
// 等于什么都没说。这里要换成「去跑哪个脚本」那一句。
func TestSvcFailDetailTranslatesSudo(t *testing.T) {
	hint := "跑一次 install-sudoers.sh"
	for _, raw := range []string{
		"sudo: a password is required",
		"Sorry, user tw is not allowed to execute '/usr/bin/docker restart x' as root.",
		"User tw may not run sudo on ha51.",
	} {
		if got := svcFailDetail(errors.New("exit status 1"), []byte(raw), hint); got != hint {
			t.Errorf("%q 应当换成提示语，得到 %q", raw, got)
		}
	}

	// 别的错就把命令的真实输出原样回上去 —— 那才是要看的东西
	real := "Error response from daemon: container is restarting"
	if got := svcFailDetail(errors.New("exit status 1"), []byte(real), hint); got != real {
		t.Errorf("真实报错该原样回上去，得到 %q", got)
	}
	// 什么都没输出时退回 err 本身，不能回一个空串
	if got := svcFailDetail(errors.New("signal: killed"), nil, hint); got == "" {
		t.Error("没有输出时也必须说点什么")
	}
}

func TestIsNoSuchContainer(t *testing.T) {
	yes := []string{
		"Error response from daemon: No such container: a9000_audioserver",
		"Error: No such container: a9000_audioserver",
	}
	for _, s := range yes {
		if !isNoSuchContainer([]byte(s)) {
			t.Errorf("%q 该认成「没有这个容器」", s)
		}
	}
	if isNoSuchContainer([]byte("permission denied while trying to connect to the Docker daemon")) {
		t.Error("权限问题不是「没有这个容器」——那是 failed，不是 missing")
	}
}

// 这台机器上没有 heartbeat 时报 missing，不是 failed。
//
// 「没装这一套」和「装了但动不了」要分开：前者不需要任何人做什么，
// 后者是库里已经是新配置、跑着的服务还是旧的，必须拦住人。
//
// ⚠ 但两种都要报给界面 —— 「我没重启 heartbeat」正是运维盯着 ifconfig
// 看不到新地址时要知道的那一句。
func TestHeartbeatMissingIsNotFailure(t *testing.T) {
	s := &Service{}
	got := s.StopHeartbeat(t.Context())
	if got.Name != "heartbeat" {
		t.Fatalf("名字不对：%+v", got)
	}
	// CI / 开发机上既没有 heartbeat.service 也没有 /etc/init.d/heartbeat
	if got.Status != SyncMissing {
		t.Skipf("这台机器上有 heartbeat（%s），跳过", got.Status)
	}
	if got.Detail == "" {
		t.Error("报 missing 也要说清楚为什么")
	}
}

// 探不到 systemd 单元时要退回 /etc/init.d/heartbeat，而不是直接报「没装」。
//
// 这套 a9000 是 ubuntu-autoinstall 装出来的，heartbeat 很可能是 SysV 那一套。
func TestFindHeartbeatFallsBackToInitd(t *testing.T) {
	dir := t.TempDir()
	fake := filepath.Join(dir, "heartbeat")
	if err := os.WriteFile(fake, []byte("#!/bin/sh\nexit 0\n"), 0o755); err != nil {
		t.Fatal(err)
	}
	old := initdHeartbeat
	initdHeartbeat = fake
	t.Cleanup(func() { initdHeartbeat = old })

	got := findHeartbeat(t.Context())
	if got.cmd == "" {
		t.Fatal("有 init.d 脚本时不该报「没装」")
	}
	if got.cmd != fake {
		t.Skipf("这台机器上有 heartbeat.service，走的是 systemd（%s）", got.cmd)
	}
	if a := got.args("stop"); len(a) != 1 || a[0] != "stop" {
		t.Errorf("init.d 那条只传动作，得到 %v", a)
	}
}

// 不可执行的 /etc/init.d/heartbeat 不算数 —— 拿它当退路只会在 sudo 那一步炸。
func TestFindHeartbeatIgnoresNonExecutable(t *testing.T) {
	dir := t.TempDir()
	fake := filepath.Join(dir, "heartbeat")
	if err := os.WriteFile(fake, []byte("x"), 0o644); err != nil {
		t.Fatal(err)
	}
	old := initdHeartbeat
	initdHeartbeat = fake
	t.Cleanup(func() { initdHeartbeat = old })

	if got := findHeartbeat(t.Context()); got.cmd == fake {
		t.Error("不可执行的脚本不该被当成退路")
	}
}

// svcrestart.go 真正会跑的那两条命令，必须和 deploy/htweb.sudoers.in 里
// 放开的那两条**一字不差**。
//
// 两边是分开维护的（一个 Go、一个 sudoers 模板），对不上的表现不是编译错误，
// 而是现网保存完弹一句「没能重启」，然后有人对着 sudoers 找半天 ——
// 所以拿测试把它们钉在一起。与 TestPrivTargetsMatchHelperScript 同一套路。
func TestSudoersCoversRestartCommands(t *testing.T) {
	raw, err := os.ReadFile(filepath.Join("..", "..", "..", "deploy", "htweb.sudoers.in"))
	if err != nil {
		t.Skipf("找不到 deploy/htweb.sudoers.in：%v", err)
	}
	tpl := string(raw)

	// 模板里是占位符，装的时候由 install-sudoers.sh 换成绝对路径。
	//
	// ⚠ heartbeat 是 stop / start 两条，**不是** restart ——
	//   改完 haresources 再 restart 会让旧的虚拟地址没人摘，见 svcrestart.go 开头。
	for _, want := range []string{
		"@SYSTEMCTL@ stop heartbeat",
		"@SYSTEMCTL@ start heartbeat",
		initdHeartbeat + " stop",
		initdHeartbeat + " start",
		"@DOCKER@ restart " + audioContainer,
	} {
		if !strings.Contains(tpl, want) {
			t.Errorf("sudoers 模板里没有 %q —— 现网会重启失败", want)
		}
	}
	if !strings.Contains(tpl, "HTWEB_SVC") {
		t.Error("HTWEB_SVC 没写进那行 NOPASSWD 列表")
	}
	// ⚠ 这几条不许带 *：带了就等于把「动任意服务 / 任意容器」给了 htweb。
	//   规则跨了几行（行尾反斜杠续行），所以从 Cmnd_Alias 那行一路查到不再续行为止。
	lines := strings.Split(tpl, "\n")
	for i, line := range lines {
		if !strings.HasPrefix(line, "Cmnd_Alias HTWEB_SVC") {
			continue
		}
		for j := i; j < len(lines); j++ {
			if strings.Contains(lines[j], "*") {
				t.Errorf("HTWEB_SVC 不许带通配符：%s", lines[j])
			}
			if !strings.HasSuffix(strings.TrimRight(lines[j], " \t"), "\\") {
				break
			}
		}
	}

	// install-sudoers.sh 得认识这两个占位符，否则装出来的规则里留着 @SYSTEMCTL@
	sh, err := os.ReadFile(filepath.Join("..", "..", "..", "deploy", "install-sudoers.sh"))
	if err != nil {
		t.Skipf("找不到 deploy/install-sudoers.sh：%v", err)
	}
	for _, ph := range []string{"@SYSTEMCTL@", "@DOCKER@"} {
		if !strings.Contains(string(sh), ph) {
			t.Errorf("install-sudoers.sh 里没有替换 %s", ph)
		}
	}
}
