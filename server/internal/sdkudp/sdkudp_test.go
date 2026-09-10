package sdkudp

import (
	"context"
	"encoding/hex"
	"fmt"
	"net"
	"testing"
	"time"
)

// 这个协议一个字节错位就静默失效 —— UDP 那头不会回任何东西，
// 表现是「点了没反应」，查起来只能抓包。所以逐字节钉死。
func TestEncodeUrgentPlay(t *testing.T) {
	got := encode(cmdUrgentPlay, UrgentPlay{
		ChannelID:  0,    // 地震
		KeyID:      1000, // 地震
		TerminalID: 0,    // 全部终端
		IsStop:     0,    // 执行
	}.encodeArgs())

	// 小端，不是网络序：C 那边是把结构体直接 sendto 出去的。
	want := "" +
		"66ff" + // cmdheader 0xff66
		"c200" + // cmdid 194
		"1000" + // length 16
		"0000" + // state 0
		"00000000" + // serialid 0
		"01000000" + // sessionid 1
		"00000000" + // channelid 0
		"e8030000" + // keyid 1000
		"00000000" + // terminalid 0
		"00000000" + // isstop 0
		"5a" // 结尾哨兵

	if hex.EncodeToString(got) != want {
		t.Errorf("报文不对\n得到 %s\n期望 %s", hex.EncodeToString(got), want)
	}
	if len(got) != 33 {
		t.Errorf("总长应为 16+16+1=33 字节，得到 %d", len(got))
	}
}

// 四个通道的 keyid 各不相同，停止位也要能翻过来。
func TestEncodeUrgentPlayStopFire(t *testing.T) {
	got := encode(cmdUrgentPlay, UrgentPlay{
		ChannelID:  3,    // 消防
		KeyID:      1003, // 消防
		TerminalID: 0,
		IsStop:     1, // 停止
	}.encodeArgs())

	want := "66ff" + "c200" + "1000" + "0000" + "00000000" + "01000000" +
		"03000000" + "eb030000" + "00000000" + "01000000" + "5a"

	if hex.EncodeToString(got) != want {
		t.Errorf("报文不对\n得到 %s\n期望 %s", hex.EncodeToString(got), want)
	}
}

// 真发一包，确认 SendUrgentPlay 到线上的字节和 encode 出来的一致
// （中间没有多包一层、没有被截断）。
func TestSendUrgentPlayOnTheWire(t *testing.T) {
	pc, err := net.ListenPacket("udp", "127.0.0.1:0")
	if err != nil {
		t.Fatal(err)
	}
	defer pc.Close()

	host, portStr, _ := net.SplitHostPort(pc.LocalAddr().String())
	var port int
	if _, err := fmt.Sscan(portStr, &port); err != nil {
		t.Fatal(err)
	}

	s := New(host, port, true)
	if err := s.SendUrgentPlay(context.Background(), "", UrgentPlay{ChannelID: 1, KeyID: 1001}); err != nil {
		t.Fatal(err)
	}

	buf := make([]byte, 128)
	_ = pc.SetReadDeadline(time.Now().Add(2 * time.Second))
	n, _, err := pc.ReadFrom(buf)
	if err != nil {
		t.Fatal(err)
	}
	want := "66ff" + "c200" + "1000" + "0000" + "00000000" + "01000000" +
		"01000000" + "e9030000" + "00000000" + "00000000" + "5a"
	if hex.EncodeToString(buf[:n]) != want {
		t.Errorf("收到的包不对\n得到 %s\n期望 %s", hex.EncodeToString(buf[:n]), want)
	}
}

// enabled=false 时不能真发包 —— 联调期就靠这个开关不让喇叭响。
func TestDisabledDoesNotSend(t *testing.T) {
	pc, err := net.ListenPacket("udp", "127.0.0.1:0")
	if err != nil {
		t.Fatal(err)
	}
	defer pc.Close()

	host, portStr, _ := net.SplitHostPort(pc.LocalAddr().String())
	var port int
	if _, err := fmt.Sscan(portStr, &port); err != nil {
		t.Fatal(err)
	}

	if err := New(host, port, false).SendUrgentPlay(context.Background(), "", UrgentPlay{}); err != nil {
		t.Fatal(err)
	}

	buf := make([]byte, 64)
	_ = pc.SetReadDeadline(time.Now().Add(300 * time.Millisecond))
	if n, _, err := pc.ReadFrom(buf); err == nil {
		t.Errorf("禁用状态下仍收到 %d 字节", n)
	}
}

// 默认（config 里没填 host）时，包要发到**浏览器打开页面的那台机器**。
//
// 这条是这次改动的要害：写死 127.0.0.1 时，从别的机器打开页面点执行，
// 包发去了 Web 进程自己的回环，8885 上没人收 —— 而 UDP 不报错，
// 界面照样说「已下发」，查不出任何线索。
func TestHostFollowsRequest(t *testing.T) {
	cases := []struct {
		name    string
		cfgHost string
		reqHost string
		want    string
	}{
		{"没配 host：跟着页面地址走", "", "192.168.1.50:8080", "192.168.1.50"},
		{"页面地址没带端口", "", "broadcast.lan", "broadcast.lan"},
		{"IPv6 的页面地址", "", "[fe80::1]:8080", "fe80::1"},
		{"localhost 打开的就发回环", "", "localhost:8080", "localhost"},
		{"配了 host：钉死，不看请求", "10.0.0.9", "192.168.1.50:8080", "10.0.0.9"},
		{"两边都没有：退回回环", "", "", "127.0.0.1"},
	}
	for _, c := range cases {
		t.Run(c.name, func(t *testing.T) {
			if got := New(c.cfgHost, 8885, true).Host(c.reqHost); got != c.want {
				t.Errorf("Host(%q) with cfg %q = %q，期望 %q", c.reqHost, c.cfgHost, got, c.want)
			}
		})
	}
}

// 端到端：请求里带哪个地址，包就落到哪个地址上。
//
// 监听 127.0.0.1，然后把请求的 Host 写成 "127.0.0.1:8080"（页面从回环打开）——
// 如果 reqHost 这条路没接上，New("") 的发送器根本不知道该发去哪。
func TestSendGoesToRequestHost(t *testing.T) {
	pc, err := net.ListenPacket("udp", "127.0.0.1:0")
	if err != nil {
		t.Fatal(err)
	}
	defer pc.Close()

	_, portStr, _ := net.SplitHostPort(pc.LocalAddr().String())
	var port int
	if _, err := fmt.Sscan(portStr, &port); err != nil {
		t.Fatal(err)
	}

	// cfg 里的 host 留空 —— 只能靠请求里那个地址
	s := New("", port, true)
	if err := s.SendUrgentPlay(context.Background(), "127.0.0.1:8080", UrgentPlay{ChannelID: 3, KeyID: 1003}); err != nil {
		t.Fatal(err)
	}

	buf := make([]byte, 128)
	_ = pc.SetReadDeadline(time.Now().Add(2 * time.Second))
	n, _, err := pc.ReadFrom(buf)
	if err != nil {
		t.Fatalf("没收到包 —— 请求地址那条路没接上: %v", err)
	}
	want := "66ff" + "c200" + "1000" + "0000" + "00000000" + "01000000" +
		"03000000" + "eb030000" + "00000000" + "00000000" + "5a"
	if hex.EncodeToString(buf[:n]) != want {
		t.Errorf("收到的包不对\n得到 %s\n期望 %s", hex.EncodeToString(buf[:n]), want)
	}
}
