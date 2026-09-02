package timeset

import (
	"context"
	"fmt"
	"os/exec"
	"strings"
	"time"
)

// 设置服务器系统时间（:80 时间设置页的「设置服务器时间 / 同步当前时间」）。
//
// # ⚠ 现网默认做不到，这不是偷懒
//
// 服务端复核过两条硬约束：
//
//  1. htweb 以 `tw` 身份运行。该账号的 sudo **需要密码**
//     （`sudo -n -l` 只列出 dmidecode 一条 NOPASSWD），服务手里没有密码，
//     所以调不动 `timedatectl` / `date`。
//  2. 这台机器 `timedatectl` 显示 `NTP service: active` ——
//     就算把时间拨过去，NTP 也会很快把它拨回来。
//
// 所以 CanSet() 会先探一次能力，界面据此把按钮置灰，并把「要加哪条 sudoers、
// 要关掉哪个服务」写在页面上。运维照做之后，这两个按钮立刻就能用，不用改代码。
//
// # 旧版是怎么做的（为什么不照抄）
//
// 旧版是 `@exec("date " . $_POST[...])` —— DOS 语法、参数裸拼，等于命令注入（D-218）。
// 新版只调 `timedatectl set-time`，时间值经过严格解析后按固定格式重新拼出来，
// 不把用户输入的任何字符透传给 shell（用 exec.Command 传参，不经过 shell）。

// clockCmd 是我们唯一愿意调的命令。
// 用绝对路径：服务的 PATH 与登录 shell 不同，靠 PATH 找会时灵时不灵。
const clockCmd = "/usr/bin/timedatectl"

// ClockAbility 说明这台机器能不能通过 Web 改系统时间，不能的话缺什么。
type ClockAbility struct {
	CanSet bool `json:"canSetClock"`
	// Reason 在 CanSet=false 时说明原因，直接展示给运维。
	Reason string `json:"clockBlockReason"`
	// NTPActive 为 true 表示自动校时守护在跑。
	NTPActive bool `json:"ntpActive"`
	// NTPSynced 是 timedatectl 的 NTPSynchronized —— 守护在跑**不等于**真的同步上了。
	// 现网 192.168.2.159 就是 NTPActive=yes 而 NTPSynchronized=no
	// （内网够不着上游），这种机器手工拨的时间实际上是留得住的。
	NTPSynced bool `json:"ntpSynced"`
	// NTPWarning 在 NTPActive 时给一句提醒，界面显示在按钮旁边。
	// 它**不是**阻断原因 —— 关不关自动校时由操作员在界面上明确勾选，见 SetClock。
	NTPWarning string `json:"ntpWarning"`
}

// probe 探一次能力。每次外部调用都带超时，绝不让页面卡在这里。
func (s *Service) probeClock(ctx context.Context) ClockAbility {
	out := ClockAbility{}

	// timedatectl 本身在不在
	if _, err := exec.LookPath(clockCmd); err != nil {
		out.Reason = "服务器上没有 " + clockCmd + "，无法通过 Web 设置系统时间"
		return out
	}

	// 自动校时的状态。这两个值都不需要 root，直接读。
	c, cancel := context.WithTimeout(ctx, 3*time.Second)
	defer cancel()
	if b, err := exec.CommandContext(c, clockCmd,
		"show", "-p", "NTP", "-p", "NTPSynchronized", "--value").Output(); err == nil {
		lines := strings.Fields(string(b))
		if len(lines) > 0 {
			out.NTPActive = lines[0] == "yes"
		}
		if len(lines) > 1 {
			out.NTPSynced = lines[1] == "yes"
		}
	}

	// 能不能免密 sudo 调它。
	//
	// ⚠ 这里探的是「set-time 带一个参数」这个**具体形态**，不是 `timedatectl status`。
	//   deploy/htweb.sudoers.in 放开的是 `timedatectl set-time *` 和 `set-ntp *`，
	//   `status` 并不在放行名单里 —— 拿 status 去探会把装好了的机器误判成没装。
	//   `-l` 只查权限、不执行，参数形态与真正要跑的那条一致。
	c2, cancel2 := context.WithTimeout(ctx, 3*time.Second)
	defer cancel2()
	if err := exec.CommandContext(c2, "sudo", "-n", "-l",
		clockCmd, "set-time", "2000-01-01 00:00:00").Run(); err != nil {
		out.Reason = "服务账号没有免密执行 " + clockCmd + " 的权限。" +
			"在服务器上跑一次 deploy/install-sudoers.sh 即可开通"
		return out
	}

	// ⚠ NTP 在跑**不再**作为阻断条件。
	//
	// 原来的写法是「NTPActive → CanSet=false」，结果把现网这台机器挡死了：
	// 它 NTPActive=yes 但 NTPSynchronized=no，上游根本够不着，
	// 自动校时形同虚设，手工拨的时间反而是留得住的。
	// 现在改成给一句提醒；真要关掉自动校时，由操作员在界面上明确勾选，
	// 程序不替他做这个决定（那是动系统服务，不该由一个按钮顺手完成）。
	if out.NTPActive {
		if out.NTPSynced {
			out.NTPWarning = "系统自动校时（NTP）正在生效，手工设置的时间稍后会被拨回去。" +
				"要让手工设置留住，请勾选「同时关闭自动校时」。"
		} else {
			out.NTPWarning = "系统自动校时（NTP）已启用但尚未同步成功（够不着上游服务器），" +
				"手工设置的时间可以留住。"
		}
	}

	out.CanSet = true
	return out
}

// SetClock 把系统时间设置成 value。
//
// 入参是 "YYYY-MM-DD HH:MM:SS"，先解析再重新格式化 —— 不把原始字符串交给命令行。
//
// stopNTP=true 时先关掉自动校时（timedatectl set-ntp false），否则拨过去的时间
// 可能很快被拨回来。这是**动系统服务**，所以只在界面上明确勾选时才做，
// 不由程序替操作员决定。
func (s *Service) SetClock(ctx context.Context, value string, stopNTP bool) error {
	t, err := time.ParseInLocation("2006-01-02 15:04:05", strings.TrimSpace(value), time.Local)
	if err != nil {
		return fmt.Errorf("时间格式不正确，必须是 YYYY-MM-DD HH:MM:SS")
	}
	// 拦一下明显离谱的值：拨到 1970 或 2100 会让整套调度彻底失常
	if t.Year() < 2000 || t.Year() > 2099 {
		return fmt.Errorf("时间超出允许范围（2000 ~ 2099 年）")
	}

	ab := s.probeClock(ctx)
	if !ab.CanSet {
		return fmt.Errorf("%s", ab.Reason)
	}

	// 顺序要紧：先关自动校时再拨表。反过来的话，两条命令之间那一小段时间里
	// NTP 还在跑，刚拨好的值可能立刻就被改掉。
	if stopNTP && ab.NTPActive {
		c0, cancel0 := context.WithTimeout(ctx, 10*time.Second)
		defer cancel0()
		cmd := exec.CommandContext(c0, "sudo", "-n", clockCmd, "set-ntp", "false")
		if b, err := cmd.CombinedOutput(); err != nil {
			return fmt.Errorf("关闭自动校时失败：%s", strings.TrimSpace(string(b)))
		}
	}

	c, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()
	// 重新格式化后作为**独立参数**传入，不拼 shell 命令行
	cmd := exec.CommandContext(c, "sudo", "-n", clockCmd, "set-time", t.Format("2006-01-02 15:04:05"))
	if b, err := cmd.CombinedOutput(); err != nil {
		return fmt.Errorf("设置系统时间失败：%s", strings.TrimSpace(string(b)))
	}
	return nil
}
