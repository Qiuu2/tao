package serverparam

import (
	"context"
	"fmt"
	"log"
	"net"
	"os/exec"
	"strings"
	"time"
)

// 把「服务器信息」页上改的 IP / 掩码 / 网关**真的写到网卡上**。
//
// # 为什么要有这一步
//
// 在这之前这一页只写数据库，网卡照旧 —— 运维改完 IP 还得自己去系统里再改一遍，
// 否则那一行记录和机器实际的地址对不上。旧版是做了这件事的
// （ok112/iprun.sh，nmcli），只是做法上有几处不能照抄，见下面。
//
// # 认哪个地址
//
// 取界面上「服务器IP」那一栏（serverbaseparam.ip）、掩码取 subnetmask、
// 网关取 gateway —— 就是操作员刚刚改的那三个框。
//
// ⚠ 旧版 iprun.sh 取的是**主/备 IP**（按 model 选 masterip 或 slaveip），
//   跟界面上那个 ip 字段没关系。现网这两处的值是不一样的
//   （ip=192.168.2.159 而 masterip=12.12.2.51），照抄会把机器设到一个
//   操作员根本没输入过的地址上。这一条是有意不 1:1 的，已与需求方确认。
//
// # 与旧版的其它差别
//
//   - 旧版把 IP、网关拼进一条 shell 字符串再 exec（`iprun.sh $i $master_ip ...`），
//     参数里有空格或分号就能执行任意命令。这里用 exec.Command 分参数传，
//     全程不经过 shell。
//   - 旧版 `nmcli connection down` 再 `up`。down 那一下会先把网断掉，
//     万一 up 失败（比如地址非法被 NetworkManager 拒了），机器就彻底失联了。
//     这里只 `up`：nmcli 的 up 对已激活的连接就是重新应用配置，
//     失败时旧地址还在，人还能连上来把它改回去。
//
// # 这台机器上做不到时
//
// 不猜、不装作成功：探一次能力，缺什么就把缺的那一条原样报给界面，
// 数据库照常保存（记录该改还是要改），只是网卡那一步跳过并说明原因。
// 与「设置服务器时间」那两个按钮同一套做法，见 timeset/clock.go。

// nmcli 的路径。用绝对路径：服务的 PATH 与登录 shell 不一样，靠 PATH 找会时灵时不灵。
var nmcliPaths = []string{"/usr/bin/nmcli", "/bin/nmcli"}

func nmcliPath() string {
	for _, p := range nmcliPaths {
		if _, err := exec.LookPath(p); err == nil {
			return p
		}
	}
	return ""
}

// NetAbility 说明这台机器能不能通过 Web 改网卡地址，不能的话缺什么。
type NetAbility struct {
	CanSet bool `json:"canSetNetwork"`
	// Reason 在 CanSet=false 时说明原因，直接展示给运维。
	Reason string `json:"networkBlockReason"`
	// Connection / Device 是选中的那个 NetworkManager 连接，写进结果里
	// 让人能核对改的是不是自己想的那块网卡。
	Connection string `json:"nmConnection"`
	Device     string `json:"nmDevice"`
}

// probeNetwork 探一次能力。每一步都带超时，绝不让页面卡在这里。
func (s *Service) probeNetwork(ctx context.Context) NetAbility {
	out := NetAbility{}

	cmd := nmcliPath()
	if cmd == "" {
		out.Reason = "服务器上没有 nmcli（NetworkManager），无法通过 Web 修改网卡地址"
		return out
	}

	conn, dev, err := activeEthernetConn(ctx, cmd)
	if err != nil {
		out.Reason = err.Error()
		return out
	}
	out.Connection, out.Device = conn, dev

	// 能不能免密 sudo 调它。探的是**真正要跑的那个形态**（connection modify 带参数），
	// 不是 `nmcli --version` —— sudoers 里放开的就是 modify 与 up 两条。
	c, cancel := context.WithTimeout(ctx, 3*time.Second)
	defer cancel()
	if err := exec.CommandContext(c, "sudo", "-n", "-l", cmd,
		"connection", "modify", conn, "ipv4.method", "manual").Run(); err != nil {
		out.Reason = "服务账号没有免密执行 " + cmd + " 的权限。" +
			"在服务器上跑一次 deploy/install-sudoers.sh 即可开通"
		return out
	}

	out.CanSet = true
	return out
}

// activeEthernetConn 找出当前活动的有线连接。
//
// 口径与旧版 iprun.sh 一致：`nmcli -t -f NAME,TYPE,STATE con show` 里
// 第一条 type 含 ethernet 且 state 为 activated 的。
//
// ⚠ 一台机器可能有好几块网卡。选中哪一个会连同结果一起回给界面，
//
//	让人能核对 —— 猜错网卡和猜错地址一样糟，但至少要让人看得见猜的是哪个。
func activeEthernetConn(ctx context.Context, cmd string) (name, device string, err error) {
	c, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()
	// -t 是 tab 分隔的机器可读格式，字段里的冒号会转义成 \:，所以按 \: 之外的冒号切
	b, e := exec.CommandContext(c, cmd, "-t", "-f", "NAME,TYPE,STATE,DEVICE", "con", "show").Output()
	if e != nil {
		return "", "", fmt.Errorf("读取 NetworkManager 连接列表失败：%v", e)
	}
	for _, line := range strings.Split(string(b), "\n") {
		f := splitNmcli(line)
		if len(f) < 4 {
			continue
		}
		if !strings.Contains(strings.ToLower(f[1]), "ethernet") {
			continue
		}
		if !strings.Contains(strings.ToLower(f[2]), "activated") {
			continue
		}
		return f[0], f[3], nil
	}
	return "", "", fmt.Errorf("没有找到处于活动状态的有线连接（nmcli con show 里没有 activated 的 ethernet）")
}

// splitNmcli 按 nmcli -t 的转义规则切字段：字段里的 : 会被写成 \:，不能当分隔符。
func splitNmcli(line string) []string {
	var out []string
	var cur strings.Builder
	esc := false
	for _, r := range line {
		switch {
		case esc:
			cur.WriteRune(r)
			esc = false
		case r == '\\':
			esc = true
		case r == ':':
			out = append(out, cur.String())
			cur.Reset()
		default:
			cur.WriteRune(r)
		}
	}
	out = append(out, cur.String())
	return out
}

// maskToPrefix 把 255.255.255.0 换成 24。
//
// 非连续掩码（255.0.255.0）在这里报错而不是算出一个看着合理的数 ——
// 旧版 decbin() 数 1 的个数正是这么错的（D-212）。
func maskToPrefix(mask string) (int, error) {
	ip := net.ParseIP(strings.TrimSpace(mask))
	if ip == nil || ip.To4() == nil {
		return 0, fmt.Errorf("子网掩码格式不正确，必须是 IPv4")
	}
	v4 := ip.To4()
	ones, bits := net.IPv4Mask(v4[0], v4[1], v4[2], v4[3]).Size()
	if bits == 0 {
		return 0, fmt.Errorf("子网掩码 %s 不是合法掩码（必须是连续的 1）", mask)
	}
	return ones, nil
}

// NetworkApply 是这一次保存里「网卡那一步」的结果，回给界面。
type NetworkApply struct {
	// Attempted 为 true 表示真的去改网卡了。界面据此弹「连接会断开」那个提示。
	Attempted bool `json:"attempted"`
	// Blocked 非空表示地址变了但没能改网卡，值就是原因（探测结果或校验失败）。
	Blocked string `json:"blocked"`
	// Connection / Device 改的是哪个连接、哪块网卡。
	Connection string `json:"connection"`
	Device     string `json:"device"`
	// Address 是设上去的地址，形如 192.168.1.50/24。
	Address string `json:"address"`
	Gateway string `json:"gateway"`
	// NewURL 是换完地址之后这个页面的新入口，界面上直接给成一个链接。
	NewURL string `json:"newUrl"`
}

// applyNetwork 真正去改网卡。**同步执行，但由调用方放到响应之后**。
//
// ⚠ 改完这一下，当前这条 HTTP 连接就断了 —— 浏览器还连在旧地址上。
//
//	所以 Save 那边是先把响应写回去、再延迟一点点执行这里，
//	否则操作员只会看到「请求失败」，完全不知道到底改没改成。
//
// modifyArgs 拼出 `sudo -n nmcli connection modify …` 的完整 argv。
//
// 单拎出来是为了能断言到每一个参数：这串东西里少一个 ipv4.method manual，
// 地址就设不上去；多一个空格拼错，改的可能是别的连接。而它跑起来的后果
// （机器换地址）在测试里是不能真做的，所以只能盯着 argv 看。
func modifyArgs(cmd, conn, addr, gateway string) []string {
	args := []string{"-n", cmd, "connection", "modify", conn,
		"ipv4.method", "manual", "ipv4.addresses", addr}
	if gateway != "" {
		args = append(args, "ipv4.gateway", gateway)
	}
	return args
}

func applyNetwork(ctx context.Context, cmd, conn, addr, gateway string) error {
	c, cancel := context.WithTimeout(ctx, 20*time.Second)
	defer cancel()
	// ⚠ exec.Command 分参数传，不拼 shell 字符串 —— 旧版那条 iprun.sh 是拼的，
	//   地址里带个分号就能执行任意命令。
	if out, err := exec.CommandContext(c, "sudo",
		modifyArgs(cmd, conn, addr, gateway)...).CombinedOutput(); err != nil {
		return fmt.Errorf("nmcli connection modify 失败: %v: %s", err, strings.TrimSpace(string(out)))
	}

	// 只 up，不 down。理由见文件头：down 之后 up 失败就彻底失联了。
	c2, cancel2 := context.WithTimeout(ctx, 30*time.Second)
	defer cancel2()
	if out, err := exec.CommandContext(c2, "sudo", "-n", cmd,
		"connection", "up", conn).CombinedOutput(); err != nil {
		return fmt.Errorf("nmcli connection up 失败: %v: %s", err, strings.TrimSpace(string(out)))
	}
	return nil
}

// planNetwork 算出这次保存要不要动网卡、动的话动成什么样。
//
// webPort 是浏览器打开这个页面用的端口，只为拼 NewURL —— 换完地址之后
// 人要用新地址重新打开，端口不会变。
func (s *Service) planNetwork(ctx context.Context, before *Params, in Input, webPort string) *NetworkApply {
	changed := before.Network.IP != in.Network.IP ||
		before.Network.SubnetMask != in.Network.SubnetMask ||
		before.Network.Gateway != in.Network.Gateway
	if !changed {
		return nil
	}

	out := &NetworkApply{Gateway: in.Network.Gateway}

	prefix, err := maskToPrefix(in.Network.SubnetMask)
	if err != nil {
		out.Blocked = err.Error()
		return out
	}
	if !isIPv4(in.Network.IP) {
		out.Blocked = "服务器 IP 不是合法的 IPv4 地址，没有去动网卡"
		return out
	}
	out.Address = fmt.Sprintf("%s/%d", in.Network.IP, prefix)

	ab := s.probeNetwork(ctx)
	out.Connection, out.Device = ab.Connection, ab.Device
	if !ab.CanSet {
		out.Blocked = ab.Reason
		return out
	}

	out.Attempted = true
	out.NewURL = "http://" + in.Network.IP
	if webPort != "" {
		out.NewURL = "http://" + net.JoinHostPort(in.Network.IP, webPort)
	}
	return out
}

// runNetworkLater 在响应发出去之后再动网卡。
//
// 用 context.Background()：请求的 ctx 在响应写完那一刻就取消了，
// 拿它去跑 nmcli 会被立刻掐断 —— 那样数据库改了、网卡没改，
// 而界面上还显示「正在切换」，是最难查的一种不一致。
func runNetworkLater(cmd, conn, addr, gateway string) {
	go func() {
		// 留一点时间让响应真正走完这条即将被切断的连接
		time.Sleep(1500 * time.Millisecond)
		if err := applyNetwork(context.Background(), cmd, conn, addr, gateway); err != nil {
			log.Printf("修改网卡地址失败（连接 %q → %s）: %v", conn, addr, err)
			return
		}
		log.Printf("网卡地址已改为 %s（连接 %q，网关 %q）", addr, conn, gateway)
	}()
}
