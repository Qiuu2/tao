package timeset

import (
	"context"
	"fmt"
	"os/exec"
	"slices"
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
//  2. 这台机器上有自动校时守护在跑 —— 就算把时间拨过去，它也会拨回来。
//
// 所以 CanSet() 会先探一次能力，界面据此把按钮置灰，并把「要加哪条 sudoers、
// 要关掉哪个服务」写在页面上。运维照做之后，这两个按钮立刻就能用，不用改代码。
//
// # ⚠ 自动校时不关掉，这个按钮就是没用的
//
// systemd 的 timedated 在自动校时开着时**直接拒绝** SetTime：
//
//	Failed to set time: Automatic time synchronization is enabled
//
// 而且这台机器上的自动校时不止一处：
//
//	systemd-timesyncd          `timedatectl set-ntp false` 管得住
//	ntp.service / ntpd         ⚠ ha-post.sh 第 13 行 `systemctl start ntp.service`
//	                           —— 每次 heartbeat 接管资源都会把它拉起来
//	chronyd                    有的装法用这个
//	/etc/crontab 的 timeupdate.sh   备机上按 `ntpdate -u <主机>` 定期拉回去
//
// `timedatectl set-ntp false` 只保证管得住 timesyncd 与在
// `/usr/lib/systemd/ntp-units.d/` 里登记过的单元；ntpd 是不是登记了每个发行版
// 都不一样。所以这里**逐个显式停**（名单写死，见 ntpUnits），
// 停完再拨表，拨完**读回来核对**一次 —— 被拨回去了就如实说，
// 不让人对着一个「设置成功」的绿条去猜为什么时间没变。
//
// 至于 ha-post.sh 和 crontab 那两处：它们属于这套系统自己的设计，
// 不由这个按钮去改，但会在结果里点名，让运维知道下一次接管/下一次定时任务会怎样。
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
	// NTPUnits 是这台机器上**正在跑**的自动校时服务，按名字列出来
	// （systemd-timesyncd / ntp / ntpd / chronyd…）。
	// 勾了「同时关闭自动校时」时停的就是这几个。
	NTPUnits []string `json:"ntpUnits"`
	// NTPWarning 在 NTPActive 时给一句提醒，界面显示在按钮旁边。
	// 它**不是**阻断原因 —— 关不关自动校时由操作员在界面上明确勾选，见 SetClock。
	NTPWarning string `json:"ntpWarning"`
}

// ntpUnits 是允许停的自动校时服务名单。**写死**，不从参数来 ——
// 这条 sudo 能停的就只有这几个，名单同时也是 deploy/htweb.sudoers.in 里那几条。
var ntpUnits = []string{"systemd-timesyncd", "ntp", "ntpd", "chronyd", "chrony"}

// systemctlCmd 与 clockCmd 同理，用绝对路径。
var systemctlPaths = []string{"/usr/bin/systemctl", "/bin/systemctl"}

func systemctlCmd() string {
	for _, p := range systemctlPaths {
		if _, err := exec.LookPath(p); err == nil {
			return p
		}
	}
	return ""
}

// activeNTPUnits 列出名单里**正在跑**的那几个。`is-active` 不需要 root。
func activeNTPUnits(ctx context.Context) []string {
	cmd := systemctlCmd()
	if cmd == "" {
		return nil
	}
	var out []string
	for _, u := range ntpUnits {
		c, cancel := context.WithTimeout(ctx, 2*time.Second)
		b, err := exec.CommandContext(c, cmd, "is-active", u).Output()
		cancel()
		// is-active 对「在跑」返回 0 且输出 active；对不存在的单元返回非 0
		if err == nil && strings.TrimSpace(string(b)) == "active" {
			out = append(out, u)
		}
	}
	return out
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

	// ⚠ timedatectl 的 NTP 属性只反映它自己管得着的那些单元。
	//   ha-post.sh 直接 `systemctl start ntp.service` 起来的 ntpd，
	//   在有的发行版上 timedatectl 根本看不见 —— 所以再自己数一遍。
	out.NTPUnits = activeNTPUnits(ctx)
	if len(out.NTPUnits) > 0 {
		out.NTPActive = true
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
		who := "系统自动校时（NTP）"
		if len(out.NTPUnits) > 0 {
			who = "自动校时服务（" + strings.Join(out.NTPUnits, "、") + "）"
		}
		// ⚠ 不管同没同步上，只要它在跑，timedated 就会**直接拒绝**拨表
		//   （Automatic time synchronization is enabled）。所以这句话对两种情况
		//   都得说清楚：不关掉它，这个按钮就是按不动的。
		out.NTPWarning = who + "正在运行 —— 不关掉它，系统会拒绝手工设置时间。" +
			"「同时关闭自动校时」默认勾着，保存时会先把它停掉。"
		if out.NTPSynced {
			out.NTPWarning += "（它已经同步上了上游服务器，不关的话拨过去也会被拨回来。）"
		}
	}

	out.CanSet = true
	return out
}

// ClockResult 是一次「设置服务器时间」的结果，回给界面。
//
// 只回 error 是不够的：现网踩到的那个坑是「界面弹了绿条，时间一点没变」——
// 所以停了谁、拨完之后有没有被拨回去、还有什么会来拨它，都要说出来。
type ClockResult struct {
	// Set 为 true 表示 timedatectl set-time 真的执行成功了。
	Set bool `json:"set"`
	// ServerTime 是拨完、核对过之后服务器真正的时间。
	ServerTime string `json:"serverTime"`
	// Stopped 是为了让时间留得住而停掉的那几个自动校时服务。
	Stopped []string `json:"stopped,omitempty"`
	// Drifted 非空表示拨过去之后又被拨回来了，值就是说明。
	Drifted string `json:"drifted,omitempty"`
	// Note 是拨完之后运维还需要知道的事（比如 ha-post.sh 会把 ntp 再拉起来）。
	Note string `json:"note,omitempty"`
}

// SetClock 把系统时间设置成 value。
//
// 入参是 "YYYY-MM-DD HH:MM:SS"，先解析再重新格式化 —— 不把原始字符串交给命令行。
//
// stopNTP=true 时先把在跑的自动校时服务停掉（见 ntpUnits），再 set-ntp false，
// 最后才拨表。**不关的话 systemd 会直接拒绝拨表**，理由见文件头。
func (s *Service) SetClock(ctx context.Context, value string, stopNTP bool) (*ClockResult, error) {
	t, err := time.ParseInLocation("2006-01-02 15:04:05", strings.TrimSpace(value), time.Local)
	if err != nil {
		return nil, fmt.Errorf("时间格式不正确，必须是 YYYY-MM-DD HH:MM:SS")
	}
	// 拦一下明显离谱的值：拨到 1970 或 2100 会让整套调度彻底失常
	if t.Year() < 2000 || t.Year() > 2099 {
		return nil, fmt.Errorf("时间超出允许范围（2000 ~ 2099 年）")
	}

	ab := s.probeClock(ctx)
	if !ab.CanSet {
		return nil, fmt.Errorf("%s", ab.Reason)
	}

	out := &ClockResult{}

	// ── ① 先把自动校时停掉 ──
	//
	// 顺序要紧：先关再拨。反过来的话，两条命令之间那一小段时间里它还在跑，
	// 刚拨好的值可能立刻就被改掉。
	if stopNTP {
		out.Stopped = s.stopNTPServices(ctx, ab.NTPUnits)
	}

	// ── ② 拨表 ──
	c, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()
	// 重新格式化后作为**独立参数**传入，不拼 shell 命令行
	cmd := exec.CommandContext(c, "sudo", "-n", clockCmd, "set-time", t.Format("2006-01-02 15:04:05"))
	if b, err := cmd.CombinedOutput(); err != nil {
		return nil, setTimeError(string(b), ab.NTPUnits)
	}
	out.Set = true

	// ── ③ 读回来核对 ──
	//
	// htweb 就跑在这台机器上，它的 time.Now() 就是系统时钟 —— 不用再 exec 一次。
	// 等一下再读：ntpd 这类守护是在一两秒内把表**跺**回去的，
	// 拨完立刻读只会读到自己刚写进去的值。
	select {
	case <-ctx.Done():
	case <-time.After(2 * time.Second):
	}
	now := time.Now()
	out.ServerTime = now.Format("2006-01-02 15:04:05")
	if d := now.Sub(t); d < -clockDriftTolerance || d > clockDriftTolerance {
		out.Drifted = fmt.Sprintf(
			"时间拨过去了，但两秒后又变成了 %s（差 %s）—— 说明这台机器上还有东西在校时。"+
				"常见的两处：ha-post.sh 第 13 行的 `systemctl start ntp.service`，"+
				"以及备机 /etc/crontab 里 timeupdate.sh 的 `ntpdate -u <主机>`。",
			out.ServerTime, d.Round(time.Second))
	}

	// ── ④ 还要告诉他什么 ──
	if len(out.Stopped) > 0 {
		out.Note = "已停掉：" + strings.Join(out.Stopped, "、") +
			"。⚠ 它们只是**停了**，没有禁用开机自启；heartbeat 下次接管资源时 " +
			"ha-post.sh 还会把 ntp.service 拉起来。要永久关掉请在服务器上 systemctl disable。"
	}
	return out, nil
}

// setTimeError 把 timedatectl 的报错翻成运维能照着做的一句话。
//
// systemd 拒绝拨表时回的是
//
//	Failed to set time: Automatic time synchronization is enabled
//
// 原样摆出来，运维只知道「有个什么东西开着」，不知道下一步该干嘛 ——
// 而他能做的动作只有一个：把那个勾选上。
func setTimeError(raw string, units []string) error {
	msg := strings.TrimSpace(raw)
	if strings.Contains(strings.ToLower(msg), "automatic time synchronization is enabled") {
		who := strings.Join(units, "、")
		if who == "" {
			who = "系统自动校时"
		}
		return fmt.Errorf("系统拒绝手工设置时间，因为自动校时（%s）还在运行。"+
			"请勾选「同时关闭自动校时」后重试", who)
	}
	if msg == "" {
		msg = "命令没有任何输出"
	}
	return fmt.Errorf("设置系统时间失败：%s", msg)
}

// clockDriftTolerance 是「拨完两秒后还算没被拨回去」的容差。
//
// 取 10 秒：正常情况下这两秒里的误差是毫秒级；而被 ntpd 跺回去时，
// 差的是操作员刚刚改动的那个量级（分钟、小时甚至天），不会落在 10 秒以内。
// 如果他本来就只改了几秒，那分不出来也无所谓 —— 反正结果是对的。
const clockDriftTolerance = 10 * time.Second

// stopNTPServices 把在跑的自动校时停掉，回停成功的那几个。
//
// 两步都做：
//
//  1. 逐个 `systemctl stop` —— ha-post.sh 直接起的 ntp.service 只有这样才停得住
//  2. `timedatectl set-ntp false` —— 这一步让 timedated 不再拒绝 SetTime
//
// 停不掉的不报错、不中断：真正决定成败的是下一步的 set-time，
// 它失败时的报错才是运维要看的那一条。
func (s *Service) stopNTPServices(ctx context.Context, units []string) []string {
	var stopped []string
	if cmd := systemctlCmd(); cmd != "" {
		for _, u := range units {
			c, cancel := context.WithTimeout(ctx, 10*time.Second)
			err := exec.CommandContext(c, "sudo", "-n", cmd, "stop", u).Run()
			cancel()
			if err == nil {
				stopped = append(stopped, u)
			}
		}
	}
	c, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()
	if err := exec.CommandContext(c, "sudo", "-n", clockCmd, "set-ntp", "false").Run(); err == nil {
		if !slices.Contains(stopped, "systemd-timesyncd") {
			stopped = append(stopped, "systemd-timesyncd")
		}
	}
	return stopped
}
