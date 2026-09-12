package timeset

import (
	"slices"
	"strings"
	"testing"
)

// 允许当北斗校时终端的类型是一张**白名单**：3（双向寻呼终端）、8（采样终端）。
//
// 钉住它的理由：adjusttime 一旦指向一台没有授时模块的终端，整套系统就再也
// 没有校时来源了，而界面上看不出任何异常 —— 这种错不会有人来报，只会有人
// 在半年后发现所有铃都晚了几分钟。
func TestGPSTerminalTypesIsTheWhitelist(t *testing.T) {
	// 31 = 网络音频采集器，8 = 采样终端。与旧版 getgpsterminal.php 的 in(31,8) 一致。
	// ⚠ 31 不是 3 —— 3 是双向寻呼终端，完全不同的一类设备。
	want := []int{31, 8}
	if !slices.Equal(GPSTerminalTypes, want) {
		t.Fatalf("白名单被改了：%v，期望 %v。\n"+
			"要改请连同 Terminals() 的下拉、SetGPSTerminal() 的校验、"+
			"以及那句面向用户的提示一起对一遍", GPSTerminalTypes, want)
	}
}

// SQL 片段必须从白名单生成，不能在查询里把数字再抄一遍 ——
// 抄一遍就会有一天只改了一处。
func TestGPSTypeInMatchesWhitelist(t *testing.T) {
	frag, args := gpsTypeIn()
	if got := strings.Count(frag, "?"); got != len(GPSTerminalTypes) {
		t.Errorf("占位符 %d 个，白名单 %d 条：%s", got, len(GPSTerminalTypes), frag)
	}
	if len(args) != len(GPSTerminalTypes) {
		t.Fatalf("参数 %d 个，白名单 %d 条", len(args), len(GPSTerminalTypes))
	}
	for i, v := range GPSTerminalTypes {
		if args[i] != v {
			t.Errorf("第 %d 个参数是 %v，期望 %d", i, args[i], v)
		}
	}
	// 必须是 t.typeid 上的条件 —— 写成 tt.id 的话 LEFT JOIN 那一侧为空时行为不一样
	if !strings.HasPrefix(frag, "t.typeid IN (") {
		t.Errorf("条件该落在 t.typeid 上：%s", frag)
	}
}

// 选了北斗校时终端之后，「设置服务器时间」「同步当前时间」必须变灰。
//
// 手工拨过去的值撑不了多久就会被那台终端拨回来 —— 和自动校时守护同一类问题，
// 区别在于北斗校时是运维在这一页上明确选过的配置，程序不该替他撤销，
// 所以是判死 + 指一条出路（先点「不校时」），不是顺手关掉。
func TestGPSBlocksManualClock(t *testing.T) {
	ab := ClockAbility{CanSet: true}
	ab.applyGPSBlock(7)
	if ab.CanSet {
		t.Error("已启用北斗校时时不能让这两个按钮可点")
	}
	if !ab.GPSActive {
		t.Error("gpsActive 该置起来，界面靠它解释按钮为什么是灰的")
	}
	// 光变灰不说原因，就是「点不动而且不知道为什么」——这一页已经栽过一次了
	if ab.Reason == "" {
		t.Fatal("判死必须给原因")
	}
	if !strings.Contains(ab.Reason, "不校时") {
		t.Errorf("原因里要指出那条他一步就能做到的出路：%s", ab.Reason)
	}
}

// 没选校时终端（0）时一个字段都不该动 —— 探出来的能力是什么就是什么。
func TestGPSBlockIsNoOpWhenOff(t *testing.T) {
	for _, id := range []int64{0, -1} {
		ab := ClockAbility{CanSet: true, Reason: ""}
		ab.applyGPSBlock(id)
		if !ab.CanSet || ab.GPSActive || ab.Reason != "" {
			t.Errorf("adjusttime=%d 时不该判死：%+v", id, ab)
		}
	}
}

// Get() 展示的原因和 SetClock() 拦截时报的必须是同一句话。
//
// 界面上写着「因为 X 所以不能点」，绕过界面直接打接口报的却是另一回事 ——
// 那是最让人不信任一个系统的那种不一致。两处都走 applyGPSBlock 就不会分叉。
func TestGPSBlockReasonIsSingleSourced(t *testing.T) {
	ab := ClockAbility{CanSet: true}
	ab.applyGPSBlock(1)
	if ab.Reason != blockedByGPS {
		t.Errorf("原因该直接取 blockedByGPS 这一个常量，得到 %q", ab.Reason)
	}
}
