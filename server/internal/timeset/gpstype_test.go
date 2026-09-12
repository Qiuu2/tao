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
	want := []int{3, 8}
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
