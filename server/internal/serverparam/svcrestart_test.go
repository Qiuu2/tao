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

// 这台机器上没有 heartbeat 单元时报 missing，不是 failed。
//
// 「没装这一套」和「装了但重启不了」要分开：前者不需要任何人做什么，
// 后者是库里已经是新配置、跑着的服务还是旧的，必须拦住人。
func TestRestartHeartbeatMissingIsNotFailure(t *testing.T) {
	got := restartHeartbeat(t.Context())
	if got.Name != "heartbeat" {
		t.Fatalf("名字不对：%+v", got)
	}
	// CI / 开发机上都没有 heartbeat.service
	if got.Status != SyncMissing {
		t.Skipf("这台机器上有 heartbeat（%s），跳过", got.Status)
	}
	if got.Detail == "" {
		t.Error("报 missing 也要说清楚为什么")
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

	// 模板里是占位符，装的时候由 install-sudoers.sh 换成绝对路径
	for _, want := range []string{
		"@SYSTEMCTL@ restart heartbeat",
		"@DOCKER@ restart " + audioContainer,
	} {
		if !strings.Contains(tpl, want) {
			t.Errorf("sudoers 模板里没有 %q —— 现网会重启失败", want)
		}
	}
	if !strings.Contains(tpl, "HTWEB_SVC") {
		t.Error("HTWEB_SVC 没写进那行 NOPASSWD 列表")
	}
	// ⚠ 这两条不许带 *：带了就等于把「重启任意服务 / 任意容器」给了 htweb
	for _, line := range strings.Split(tpl, "\n") {
		if strings.HasPrefix(line, "Cmnd_Alias HTWEB_SVC") && strings.Contains(line, "*") {
			t.Errorf("HTWEB_SVC 不许带通配符：%s", line)
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
