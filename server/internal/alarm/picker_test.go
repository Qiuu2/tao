package alarm

import "testing"

// 报警主机能配几路，**以这台设备自己上报的 terminal.channel 为准**。
//
// ⚠ 中间有一版取过 max(terminal.channel, terminaltype.switchcount)，
// 想法是「型号声明 16 路就该给 16 路」。需求方纠正过：报警主机就按它自己报的
// 路数算 —— 一台 channel=2 的主机就是 2 路，界面上摆出 16 路、选得到第 16 路
// 却接不上，比只给 2 路更糟。这几条把这个语义钉住，免得哪天又「修」回去。
func TestEffectiveChannels(t *testing.T) {
	cases := []struct {
		name           string
		deviceChannels int
		want           int
	}{
		{name: "设备报 2 路就是 2 路", deviceChannels: 2, want: 2},
		{name: "设备报 32 路（现网三台 7 型主机就是 32）", deviceChannels: 32, want: 32},
		{name: "设备报 16 路", deviceChannels: 16, want: 16},
		{name: "设备没报 → 0，调用方据此报错", deviceChannels: 0, want: 0},
		{name: "脏数据负值不往下传", deviceChannels: -5, want: 0},
	}
	for _, c := range cases {
		t.Run(c.name, func(t *testing.T) {
			if got := effectiveChannels(c.deviceChannels); got != c.want {
				t.Errorf("effectiveChannels(%d) = %d，应为 %d", c.deviceChannels, got, c.want)
			}
		})
	}
}
