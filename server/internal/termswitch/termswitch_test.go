package termswitch

import "testing"

// 现场给的两条规则，逐个型号钉住。
//
// 排错的后果不是「显示难看」：勾选结果是要落进 terminaloftask.area 的，
// 把电源那一路当成分区勾掉，现场就是喇叭有电但没声音（或者反过来，
// 该断电的没断）。这种错在界面上看不出来，只有到现场才发现。
func TestOf(t *testing.T) {
	cases := []struct {
		name        string
		typeID      int64
		switchCount int
		want        Layout
	}{
		// —— 前置：前两路电源，其余分区 ——
		{"4 网络前置 8 路", 4, 8, Layout{KindPreamp, 2, 6}},
		{"34 网络前置 6 路", 34, 6, Layout{KindPreamp, 2, 4}},
		{"36 网络分区前置 8 路", 36, 8, Layout{KindPreamp, 2, 6}},
		{"39 网络前置 0 路", 39, 0, Layout{KindPreamp, 0, 0}},
		{"47 网络前置 0 路", 47, 0, Layout{KindPreamp, 0, 0}},

		// —— 功放：全部电源 ——
		{"5 网络功放 8 路", 5, 8, Layout{KindAmplifier, 8, 0}},
		{"37 网络功放 6 路", 37, 6, Layout{KindAmplifier, 6, 0}},
		{"24 网络音柱/功放 0 路", 24, 0, Layout{KindAmplifier, 0, 0}},

		// —— 名单外的型号：不给逐路勾选，哪怕 switchcount 不小 ——
		{"6 电源管理器 10 路", 6, 10, Layout{KindNone, 0, 0}},
		{"7 报警主机 16 路", 7, 16, Layout{KindNone, 0, 0}},
		{"14 分控前置 8 路（名字像前置，但不在名单里）", 14, 8, Layout{KindNone, 0, 0}},
		{"42 LED设备 8 路", 42, 8, Layout{KindNone, 0, 0}},
		{"11 一体化音箱 0 路", 11, 0, Layout{KindNone, 0, 0}},
	}
	for _, c := range cases {
		if got := Of(c.typeID, c.switchCount); got != c.want {
			t.Errorf("%s: Of(%d,%d) = %+v，应为 %+v", c.name, c.typeID, c.switchCount, got, c.want)
		}
	}
}

// 前置只有一路的话，那一路算电源，分区数不能变成 -1。
// （现网没有这种型号，但 switchcount 是库里的数，改一下就有了。）
func TestOfPreampFewerThanTwoSwitches(t *testing.T) {
	if got := Of(4, 1); got != (Layout{KindPreamp, 1, 0}) {
		t.Errorf("一路的前置应当只有一路电源、零个分区，得到 %+v", got)
	}
	if got := Of(4, 2); got != (Layout{KindPreamp, 2, 0}) {
		t.Errorf("两路的前置应当两路全是电源，得到 %+v", got)
	}
}

// area 是 varchar(16)，第 17 路存不下，多出来的不能给出去勾。
func TestOfClampsToColumnWidth(t *testing.T) {
	if got := Of(5, 20); got.Total() != MaxSwitches {
		t.Errorf("20 路的功放应当截到 %d 路（terminaloftask.area 只有 16 位），得到 %d",
			MaxSwitches, got.Total())
	}
}

// 一路都没有的型号不该弹框 —— 空框比不弹更让人摸不着头脑。
func TestPickable(t *testing.T) {
	if !Of(4, 8).Pickable() {
		t.Error("8 路的前置应该能逐路勾")
	}
	if Of(39, 0).Pickable() {
		t.Error("0 路的前置弹出来是个空框，不该弹")
	}
	if Of(7, 16).Pickable() {
		t.Error("名单外的型号不该给逐路勾选")
	}
}
