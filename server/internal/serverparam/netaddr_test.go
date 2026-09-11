package serverparam

import (
	"os"
	"path/filepath"
	"runtime"
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

// 网络那三个框没动时不能去碰网卡 —— 保存一次别的设置就把网断一下，
// 是这个功能最容易犯也最难原谅的错。
func TestPlanNetworkSkipsWhenUnchanged(t *testing.T) {
	s := &Service{}
	before := &Params{Network: Network{IP: "192.168.1.10", SubnetMask: "255.255.255.0", Gateway: "192.168.1.1"}}
	in := Input{Network: Network{IP: "192.168.1.10", SubnetMask: "255.255.255.0", Gateway: "192.168.1.1"}}
	in.Ports.Port = 9999 // 只改端口

	if got := s.planNetwork(t.Context(), before, in, "8080"); got != nil {
		t.Fatalf("网络三项没变，不该去动网卡，却得到 %+v", got)
	}
}

// 地址非法时不能动网卡，而且要说清楚为什么。
func TestPlanNetworkRejectsBadInput(t *testing.T) {
	s := &Service{}
	before := &Params{Network: Network{IP: "192.168.1.10", SubnetMask: "255.255.255.0", Gateway: "192.168.1.1"}}

	for _, c := range []struct{ name, ip, mask string }{
		{"掩码不连续", "192.168.1.20", "255.0.255.0"},
		{"IP 不是 IPv4", "not-an-ip", "255.255.255.0"},
	} {
		t.Run(c.name, func(t *testing.T) {
			in := Input{Network: Network{IP: c.ip, SubnetMask: c.mask, Gateway: "192.168.1.1"}}
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
