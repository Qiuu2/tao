package timeset

import (
	"os"
	"path/filepath"
	"strings"
	"testing"
)

// systemd 拒绝拨表时那句英文要翻成「把勾选上」——那是运维唯一能做的动作。
//
// 现网表现就是这个：点「设置服务器时间」，时间一点不变。
// 早前前端把 stopNtp 写死成 false，于是每一次都撞在这条上。
func TestSetTimeErrorExplainsNTP(t *testing.T) {
	err := setTimeError("Failed to set time: Automatic time synchronization is enabled",
		[]string{"ntp", "chronyd"})
	if err == nil {
		t.Fatal("该报错")
	}
	msg := err.Error()
	for _, want := range []string{"自动校时", "同时关闭自动校时", "ntp", "chronyd"} {
		if !strings.Contains(msg, want) {
			t.Errorf("报错里缺 %q：%s", want, msg)
		}
	}
}

// 名单为空时也得说得通，不能拼出「因为自动校时（）还在运行」。
func TestSetTimeErrorWithoutUnitNames(t *testing.T) {
	msg := setTimeError("Automatic time synchronization is enabled", nil).Error()
	if strings.Contains(msg, "（）") {
		t.Errorf("名单为空时括号里不能是空的：%s", msg)
	}
}

// 别的报错原样回上去 —— 那才是要看的东西。
func TestSetTimeErrorPassesThrough(t *testing.T) {
	msg := setTimeError("Failed to set time: Invalid argument", nil).Error()
	if !strings.Contains(msg, "Invalid argument") {
		t.Errorf("真实报错该原样回上去：%s", msg)
	}
	// 一个字都没有时也不能回一句空话
	if m := setTimeError("", nil).Error(); strings.HasSuffix(m, "：") {
		t.Errorf("没有输出时也要说点什么：%s", m)
	}
}

// ntpUnits 与 deploy/htweb.sudoers.in 里放开的那几条必须一一对应。
//
// 两边分开维护，漏一个的表现不是编译错误，而是现网那个守护停不掉、
// 时间照样被拨回去 —— 而页面上还显示「已设置」。所以拿测试钉住。
func TestSudoersCoversNTPUnits(t *testing.T) {
	raw, err := os.ReadFile(filepath.Join("..", "..", "..", "deploy", "htweb.sudoers.in"))
	if err != nil {
		t.Skipf("找不到 deploy/htweb.sudoers.in：%v", err)
	}
	tpl := string(raw)
	for _, u := range ntpUnits {
		if !strings.Contains(tpl, "@SYSTEMCTL@ stop "+u) {
			t.Errorf("sudoers 模板里没放开 stop %s —— 它停不掉，时间就留不住", u)
		}
	}
	// ⚠ 这几条不许带 *：带了就等于把「停任意服务」给了 htweb
	lines := strings.Split(tpl, "\n")
	for i, line := range lines {
		if !strings.HasPrefix(line, "Cmnd_Alias HTWEB_CLOCK") {
			continue
		}
		for j := i; j < len(lines); j++ {
			// set-time * / set-ntp * 这两条本来就带星号（时间值是参数），
			// 只盯 systemctl stop 那几条
			if strings.Contains(lines[j], "stop") && strings.Contains(lines[j], "*") {
				t.Errorf("stop 那几条不许带通配符：%s", lines[j])
			}
			if !strings.HasSuffix(strings.TrimRight(lines[j], " \t"), "\\") {
				break
			}
		}
	}
}
