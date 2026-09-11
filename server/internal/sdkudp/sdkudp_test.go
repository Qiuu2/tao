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
		"0400" + // cmdid 4
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

	want := "66ff" + "0400" + "1000" + "0000" + "00000000" + "01000000" +
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
	if err := s.SendUrgentPlay(context.Background(), UrgentPlay{ChannelID: 1, KeyID: 1001}); err != nil {
		t.Fatal(err)
	}

	buf := make([]byte, 128)
	_ = pc.SetReadDeadline(time.Now().Add(2 * time.Second))
	n, _, err := pc.ReadFrom(buf)
	if err != nil {
		t.Fatal(err)
	}
	want := "66ff" + "0400" + "1000" + "0000" + "00000000" + "01000000" +
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

	if err := New(host, port, false).SendUrgentPlay(context.Background(), UrgentPlay{}); err != nil {
		t.Fatal(err)
	}

	buf := make([]byte, 64)
	_ = pc.SetReadDeadline(time.Now().Add(300 * time.Millisecond))
	if n, _, err := pc.ReadFrom(buf); err == nil {
		t.Errorf("禁用状态下仍收到 %d 字节", n)
	}
}
