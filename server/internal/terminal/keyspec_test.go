package terminal

import (
	"reflect"
	"testing"
)

// 把每种终端类型的键值序列钉死。
//
// 这些数字是逐条对着 ok112 的 setterminalkeyoption.php:90~145 抄的，
// 又与甲方口述的规格核过。改动本文件之前先想清楚：
// 键值存错不会报错，只会让现场某个按键触发了别的东西。
//
// ⚠ 类型编号是 terminaltype.id。

func values(opts []KeyOption) []int {
	out := make([]int, 0, len(opts))
	for _, o := range opts {
		out = append(out, o.Value)
	}
	return out
}

func TestShortcutKeyOptions(t *testing.T) {
	seq := func(from, to int) []int {
		out := []int{}
		for i := from; i <= to; i++ {
			out = append(out, i)
		}
		return out
	}

	cases := []struct {
		name      string
		typeID    int
		emergency bool
		want      []int
	}{
		// 键值 = 下标：0 起
		{"类型8 采样终端", 8, false, seq(0, 10)},
		{"类型25 编码器", 25, false, seq(0, 10)},
		{"类型31 网络音频采集器", 31, false, seq(0, 10)},
		{"类型32 TTS主机", 32, false, seq(0, 10)},

		// ⚠ 甲方口述是 0-10，ok112 是 gg=10 → 0..9。按 ok112。
		{"类型26 网络调音台", 26, false, seq(0, 9)},

		{"类型2 网络话筒", 2, false, seq(0, 30)},

		// ⚠ 甲方未提，ok112 给 0..8
		{"类型22 TTS主机", 22, false, seq(0, 8)},

		// 第 10 个键存成 30（短路触发）
		{"类型5 网络功放", 5, false, append(seq(1, 9), KeyShortCircuit)},
		{"类型11 一体化音箱", 11, false, append(seq(1, 9), KeyShortCircuit)},
		{"类型28 寻呼话筒", 28, false, append(seq(1, 9), KeyShortCircuit)},
		{"类型30 网络调音台", 30, false, append(seq(1, 9), KeyShortCircuit)},

		// 只有两个：0 紧急触发 / 30 短路触发
		{"类型40 寻呼终端", 40, false, []int{KeyEmergency, KeyShortCircuit}},

		// 急救：1,2 是急救1/急救2，第 3 个存成 30
		{"类型33 一键寻呼终端", 33, false, []int{1, 2, KeyShortCircuit}},
		{"类型33 急救模式", 33, true, []int{1, 2, KeyShortCircuit}},

		// ⚠ 类型 44 只有急救模式才是 1..3，普通快捷键模式走默认 1..9
		{"类型44 防爆终端-快捷键", 44, false, seq(1, 9)},
		{"类型44 防爆终端-急救", 44, true, seq(1, 3)},

		// 其它一律 1..9
		{"类型1 网络终端", 1, false, seq(1, 9)},
		{"类型45 网络话筒", 45, false, seq(1, 9)},
	}

	for _, c := range cases {
		t.Run(c.name, func(t *testing.T) {
			got := values(ShortcutKeyOptions(c.typeID, true, c.emergency))
			if !reflect.DeepEqual(got, c.want) {
				t.Fatalf("类型 %d（急救=%v）键值序列不对\n  实际 %v\n  期望 %v",
					c.typeID, c.emergency, got, c.want)
			}
		})
	}
}

// 不能编码的终端不给设快捷键 —— ok112 整段逻辑都包在 isencode==1 里。
func TestShortcutKeyOptionsNeedsEncode(t *testing.T) {
	for _, typeID := range []int{2, 8, 33, 40, 44, 11} {
		if got := ShortcutKeyOptions(typeID, false, false); len(got) != 0 {
			t.Fatalf("类型 %d 在 isencode=0 时不该有可选键值，却返回了 %v", typeID, values(got))
		}
	}
}

// 特殊值必须带出含义：现场只看到光秃秃的 0 / 30 是分不清紧急触发和短路触发的。
func TestKeyLabelsExplainSpecialValues(t *testing.T) {
	// 类型 40 恰好两个特殊值都有
	opts := ShortcutKeyOptions(40, true, false)
	if len(opts) != 2 {
		t.Fatalf("类型 40 应有 2 个选项，实际 %d", len(opts))
	}
	if opts[0].Label != "0（紧急触发）" {
		t.Errorf("紧急触发的标签不对: %q", opts[0].Label)
	}
	if opts[1].Label != "30（短路触发）" {
		t.Errorf("短路触发的标签不对: %q", opts[1].Label)
	}

	// 类型 33 的急救模式要标出「急救1 / 急救2」
	em := ShortcutKeyOptions(33, true, true)
	if em[0].Label != "1（急救1）" || em[1].Label != "2（急救2）" {
		t.Errorf("急救键标签不对: %q %q", em[0].Label, em[1].Label)
	}
}
