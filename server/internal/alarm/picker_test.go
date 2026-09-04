package alarm

import "testing"

// 报警主机的路数取自两个来源，单看哪一个都会算错。
// 这几条用例把现网见过的组合都钉住，免得哪天有人「简化」成只读一个字段。
func TestEffectiveChannels(t *testing.T) {
	cases := []struct {
		name                          string
		deviceChannels, typeSwitchCnt int
		want                          int
	}{
		{
			// 演示库里的样子：terminal.channel 是全表默认的 2（那是「立体声两个
			// 声道」，跟报警输入无关），型号 7 声明 16 路。只照设备算会只剩 2 路。
			name: "设备没报真值（channel=2），按型号 16 路", deviceChannels: 2, typeSwitchCnt: 16, want: 16,
		},
		{
			// 现网四台 7 型主机里有三台是 32，比类型声明的 16 还多。
			// 只照型号算会少掉一半输入。
			name: "设备报得比型号多（32 > 16），按设备 32 路", deviceChannels: 32, typeSwitchCnt: 16, want: 32,
		},
		{name: "两者相等", deviceChannels: 16, typeSwitchCnt: 16, want: 16},
		{name: "型号没声明，按设备", deviceChannels: 8, typeSwitchCnt: 0, want: 8},
		{name: "两者都没有 → 0，调用方据此报错", deviceChannels: 0, typeSwitchCnt: 0, want: 0},
		{name: "脏数据负值不往下传", deviceChannels: -5, typeSwitchCnt: -1, want: 0},
	}
	for _, c := range cases {
		t.Run(c.name, func(t *testing.T) {
			if got := effectiveChannels(c.deviceChannels, c.typeSwitchCnt); got != c.want {
				t.Errorf("effectiveChannels(%d, %d) = %d，应为 %d",
					c.deviceChannels, c.typeSwitchCnt, got, c.want)
			}
		})
	}
}
