package serverparam

import (
	"os"
	"path/filepath"
	"runtime"
	"strings"
	"testing"
)

// 掩码换前缀。算错一位，机器就落在一个错的网段里 —— 而且它自己不会报错。
func TestMaskToPrefix(t *testing.T) {
	ok := map[string]int{
		"255.255.255.0":   24,
		"255.255.0.0":     16,
		"255.0.0.0":       8,
		"255.255.255.252": 30,
		"255.255.255.255": 32,
		"0.0.0.0":         0,
	}
	for mask, want := range ok {
		got, err := maskToPrefix(mask)
		if err != nil {
			t.Errorf("%s 应当合法，却报错: %v", mask, err)
			continue
		}
		if got != want {
			t.Errorf("%s => %d，期望 %d", mask, got, want)
		}
	}

	// 非连续掩码必须报错，不能像旧版那样数 1 的个数算出一个「看着合理」的值
	for _, bad := range []string{"255.0.255.0", "255.255.1.0", "1.2.3.4", "abc", "", "::1"} {
		if got, err := maskToPrefix(bad); err == nil {
			t.Errorf("%q 不是合法掩码，却算出了 /%d", bad, got)
		}
	}
}

// nmcli -t 的字段里出现冒号时会转义成 \:，按裸冒号切会把一行切错位 ——
// 切错位的后果是拿一个错的连接名去 modify，改了别的网卡。
func TestSplitNmcli(t *testing.T) {
	cases := []struct {
		line string
		want []string
	}{
		{`有线连接 1:802-3-ethernet:activated:eth0`,
			[]string{"有线连接 1", "802-3-ethernet", "activated", "eth0"}},
		{`a\:b:802-3-ethernet:activated:eth0`,
			[]string{"a:b", "802-3-ethernet", "activated", "eth0"}},
		{`Wired connection 1:802-3-ethernet:activated:enp3s0`,
			[]string{"Wired connection 1", "802-3-ethernet", "activated", "enp3s0"}},
	}
	for _, c := range cases {
		got := splitNmcli(c.line)
		if len(got) != len(c.want) {
			t.Errorf("%q 切成 %d 段，期望 %d 段：%q", c.line, len(got), len(c.want), got)
			continue
		}
		for i := range got {
			if got[i] != c.want[i] {
				t.Errorf("%q 第 %d 段 = %q，期望 %q", c.line, i, got[i], c.want[i])
			}
		}
	}
}

// netBefore/netIn 造一对「现网那台机器」的前后状态：
// 虚拟地址 192.168.2.159（eth0:0），真实地址 192.168.1.63 / .64（eth0）。
func netBefore() *Params {
	p := &Params{Network: Network{IP: "192.168.2.159", SubnetMask: "255.255.255.0", Gateway: "192.168.2.1"}}
	p.HA.Model = 1
	p.HA.MasterIP = "192.168.1.63"
	p.HA.SlaveIP = "192.168.1.64"
	return p
}

func netIn(b *Params) Input {
	in := Input{Network: b.Network}
	in.HA.Model = b.HA.Model
	in.HA.MasterIP = b.HA.MasterIP
	in.HA.SlaveIP = b.HA.SlaveIP
	return in
}

// 主/备地址与掩码、网关都没动时不能去碰网卡 —— 保存一次别的设置就把网断一下，
// 是这个功能最容易犯也最难原谅的错。
func TestPlanNetworkSkipsWhenUnchanged(t *testing.T) {
	s := &Service{}
	before := netBefore()
	in := netIn(before)
	in.Ports.Port = 9999 // 只改端口

	if got := s.planNetwork(t.Context(), before, in, "8080"); got != nil {
		t.Fatalf("主/备地址与掩码网关都没变，不该去动网卡，却得到 %+v", got)
	}
}

// ⚠ 这一条是这个文件里最要紧的：
// 「服务器地址」那一栏是 heartbeat 的虚拟地址（现网 eth0:0 上的 192.168.2.159），
// 改它**绝不能**去动网卡 —— 动了就把 eth0 上的真实地址 192.168.1.63 冲掉，
// 而 heartbeat 那边还要往 eth0:0 放同一个地址，这台机器会直接从网上消失。
func TestPlanNetworkIgnoresVirtualIP(t *testing.T) {
	s := &Service{}
	before := netBefore()
	in := netIn(before)
	in.Network.IP = "192.168.2.200" // 只改虚拟地址

	if got := s.planNetwork(t.Context(), before, in, "8080"); got != nil {
		t.Fatalf("改的是虚拟地址，网卡一步都不该走，却得到 %+v", got)
	}
}

// 网卡地址取的是主/备那一对，不是「服务器地址」。主机取 masterip。
func TestPlanNetworkUsesMasterIP(t *testing.T) {
	s := &Service{}
	before := netBefore()
	in := netIn(before)
	in.HA.MasterIP = "192.168.1.70"

	got := s.planNetwork(t.Context(), before, in, "8886")
	if got == nil {
		t.Fatal("主机地址变了，应当返回一个结果")
	}
	if got.Address != "192.168.1.70/24" {
		t.Errorf("网卡地址该取 masterip，得到 %q", got.Address)
	}
	if strings.Contains(got.Address, "192.168.2.") || strings.Contains(got.NewURL, "192.168.2.") {
		t.Errorf("虚拟地址漏到网卡那一步了：%+v", got)
	}
}

// 切成备机时取 slaveip —— model 本身变了也要重新设网卡。
func TestPlanNetworkUsesSlaveIPWhenSlave(t *testing.T) {
	s := &Service{}
	before := netBefore()
	in := netIn(before)
	in.HA.Model = 2

	got := s.planNetwork(t.Context(), before, in, "8886")
	if got == nil {
		t.Fatal("从主机切成备机，网卡地址该跟着换")
	}
	if got.Address != "192.168.1.64/24" {
		t.Errorf("备机该取 slaveip，得到 %q", got.Address)
	}
}

// 掩码或网关变了也要重新设一次（旧版两个页面保存时都调 setiprun）——
// 地址仍然取主/备那一对。
func TestPlanNetworkReappliesOnMaskOrGateway(t *testing.T) {
	s := &Service{}
	for _, c := range []struct {
		name  string
		tweak func(*Input)
		want  string
	}{
		{"掩码变了", func(in *Input) { in.Network.SubnetMask = "255.255.0.0" }, "192.168.1.63/16"},
		{"网关变了", func(in *Input) { in.Network.Gateway = "192.168.1.254" }, "192.168.1.63/24"},
	} {
		t.Run(c.name, func(t *testing.T) {
			before := netBefore()
			in := netIn(before)
			c.tweak(&in)
			got := s.planNetwork(t.Context(), before, in, "8886")
			if got == nil {
				t.Fatal("该重新设一次网卡")
			}
			if got.Address != c.want {
				t.Errorf("地址 = %q，期望 %q", got.Address, c.want)
			}
		})
	}
}

// 地址非法时不能动网卡，而且要说清楚为什么。
func TestPlanNetworkRejectsBadInput(t *testing.T) {
	s := &Service{}

	for _, c := range []struct{ name, master, mask string }{
		{"掩码不连续", "192.168.1.70", "255.0.255.0"},
		{"主机地址不是 IPv4", "not-an-ip", "255.255.255.0"},
		{"主机地址是空的", "", "255.255.255.0"},
	} {
		t.Run(c.name, func(t *testing.T) {
			before := netBefore()
			in := netIn(before)
			in.HA.MasterIP, in.Network.SubnetMask = c.master, c.mask
			got := s.planNetwork(t.Context(), before, in, "8080")
			if got == nil {
				t.Fatal("地址变了，应当返回一个结果说明情况")
			}
			if got.Attempted {
				t.Errorf("输入不合法，绝不能去动网卡：%+v", got)
			}
			if got.Blocked == "" {
				t.Error("没动网卡就必须说明原因，Blocked 不能为空")
			}
		})
	}
}

// 真正跑出去的那串参数。少一个 ipv4.method manual 地址就设不上；
// 连接名拼错就改了别的网卡 —— 而这两种错都不会报错，只会「没生效」或者「改错了」。
func TestModifyArgs(t *testing.T) {
	got := modifyArgs("/usr/bin/nmcli", "有线连接 1", "192.168.1.50/24", "192.168.1.1")
	want := []string{"-n", "/usr/bin/nmcli", "connection", "modify", "有线连接 1",
		"ipv4.method", "manual", "ipv4.addresses", "192.168.1.50/24",
		"ipv4.gateway", "192.168.1.1"}
	assertArgs(t, got, want)

	// 没填网关时不能塞一个空的 ipv4.gateway —— nmcli 会把网关清掉
	got = modifyArgs("/usr/bin/nmcli", "eth0", "10.0.0.2/8", "")
	want = []string{"-n", "/usr/bin/nmcli", "connection", "modify", "eth0",
		"ipv4.method", "manual", "ipv4.addresses", "10.0.0.2/8"}
	assertArgs(t, got, want)

	// 连接名带空格必须原样是**一个**参数，不能被切开。
	// 麒麟 V10 上默认就叫「有线连接 1」，这一条不是假设出来的边界。
	got = modifyArgs("/usr/bin/nmcli", "有线连接 1", "1.2.3.4/24", "")
	if got[4] != "有线连接 1" {
		t.Errorf("连接名被拆开了：%q", got)
	}
}

func assertArgs(t *testing.T, got, want []string) {
	t.Helper()
	if len(got) != len(want) {
		t.Fatalf("参数个数 %d，期望 %d\n得到 %q\n期望 %q", len(got), len(want), got, want)
	}
	for i := range got {
		if got[i] != want[i] {
			t.Errorf("第 %d 个参数 = %q，期望 %q", i, got[i], want[i])
		}
	}
}

// 从 nmcli 的真实输出里挑出活动的有线连接。
//
// 这里用一个临时目录里的假 nmcli 跑真正的 exec 路径 —— 不动这台机器的网络，
// 但把「解析输出」这一段完整走一遍。挑错连接就是给错网卡改地址。
func TestActiveEthernetConn(t *testing.T) {
	if runtime.GOOS != "linux" {
		t.Skip("只在 Linux 上跑")
	}
	dir := t.TempDir()
	fake := filepath.Join(dir, "nmcli")
	script := `#!/bin/sh
# 仿麒麟 V10 上的输出：回环在前，活动的有线连接在中间，未连接的在后面
printf '%s\n' 'lo:loopback:activated:lo' '有线连接 1:802-3-ethernet:activated:eth0' 'Wired connection 2:802-3-ethernet:disconnected:eth1'
`
	if err := os.WriteFile(fake, []byte(script), 0o755); err != nil {
		t.Fatal(err)
	}

	name, dev, err := activeEthernetConn(t.Context(), fake)
	if err != nil {
		t.Fatalf("应当找到活动的有线连接: %v", err)
	}
	if name != "有线连接 1" {
		t.Errorf("连接名 = %q，期望「有线连接 1」—— 回环和未连接的都不能选中", name)
	}
	if dev != "eth0" {
		t.Errorf("网卡 = %q，期望 eth0", dev)
	}
}

// 一条活动的有线连接都没有时要报错，不能退而求其次挑一个 —— 那会改错网卡。
func TestActiveEthernetConnNone(t *testing.T) {
	if runtime.GOOS != "linux" {
		t.Skip("只在 Linux 上跑")
	}
	dir := t.TempDir()
	fake := filepath.Join(dir, "nmcli")
	script := "#!/bin/sh\nprintf '%s\\n' 'lo:loopback:activated:lo' 'Wired connection 1:802-3-ethernet:disconnected:eth0'\n"
	if err := os.WriteFile(fake, []byte(script), 0o755); err != nil {
		t.Fatal(err)
	}
	if name, _, err := activeEthernetConn(t.Context(), fake); err == nil {
		t.Errorf("没有活动的有线连接时应当报错，却选中了 %q", name)
	}
}
