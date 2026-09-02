package terminal

import "fmt"

// 快捷键 / 急救键的**可选键值**，按终端类型分。
//
// ⚠ 本文件里所有「类型」「typeID」都是 `terminaltype.id`，
//   也就是 `terminal.typeid` 指向的那个 id ——**不是终端自己的 id**。
//   下面表格里的 2、5、8、26… 全是型号编号（2=网络话筒，5=网络功放，11=一体化音箱…）。
//
// # 为什么单独一个文件
//
// 这里的规则有一个特别容易出错的地方：**下拉里选的数字和存进库的数字不是一回事**。
// 比如类型 5/11/28/30，人看到的是「第 10 个键」，存进去的却是 30（短路触发）。
// 写错了不会报错，只会让某个键按下去触发了别的东西。所以规格集中在这里，
// 并且有 keyspec_test.go 把每种类型的序列钉死。
//
// # 前提：isencode = 1
//
// ok112 的 setterminalkeyoption.php 整段逻辑都包在 `if($row_type['isencode'] == 1)` 里 ——
// 不能编码的终端根本不给设快捷键。
//
// # 规则（逐条对照 ok112 setterminalkeyoption.php:90~145）
//
//	类型                gg   生成                      实际键值序列
//	-----------------  ---  ------------------------  --------------------
//	8, 25, 31, 32       11  k                         0,1,…,10
//	26                  10  k                         0,1,…,9
//	2                   31  k                         0,1,…,30
//	22                   9  k                         0,1,…,8
//	5, 11, 28, 30       10  k==9 → 30，否则 k+1        1,…,9,30
//	40                   2  k==0 → 0，否则 30          0,30
//	33                   3  k==2 → 30，否则 k+1        1,2,30
//	44（仅急救）          3  k+1                       1,2,3
//	其它                 9  k+1                       1,…,9
//
// 特殊值的含义：**0 = 紧急触发，30 = 短路触发**。
//
// # ⚠ 与甲方口述规格的两处出入（2026-08-27）
//
//  1. **类型 26**：甲方说 0-10，ok112 是 `gg=10` → 0..9（十个键）。
//     这里按 ok112 实现。若确实要 0..10，改 keyCounts[26] = 11 即可。
//  2. **类型 22**：甲方没提，ok112 给的是 0..8。这里保留 ok112 的行为。
//
// 另外类型 44 只有在**急救**模式下才是 1..3（ok112 的 `$type_id==44 && $getact==1`）；
// 普通快捷键模式下它走 else 分支，是 1..9。类型 33 则两种模式都是 1,2,30。

// KeyOption 是键值下拉里的一项。
type KeyOption struct {
	// Value 是**存进库**的值。
	Value int `json:"value"`
	// Label 是给人看的。特殊值会带上含义，免得只看到一个光秃秃的 30。
	Label string `json:"label"`
}

const (
	// KeyEmergency 紧急触发
	KeyEmergency = 0
	// KeyShortCircuit 短路触发
	KeyShortCircuit = 30
)

// keyCounts 是各类型的选项个数（ok112 里的 $gg）。没列出的走默认 9。
var keyCounts = map[int]int{
	8: 11, 25: 11, 31: 11, 32: 11,
	26: 10,
	2:  31,
	5:  10, 11: 10, 28: 10, 30: 10,
	33: 3,
	40: 2,
}

// zeroBasedTypes 是「键值就是下标」的那几个类型（ok112 循环里的第一个分支）。
var zeroBasedTypes = setOf(2, 8, 22, 25, 26, 31, 32)

// tenToThirtyTypes 是「第 10 个键存成 30」的类型。
var tenToThirtyTypes = setOf(5, 11, 28, 30)

// ShortcutKeyOptions 返回某类型终端可选的键值。
//
// emergency 为 true 表示这是「急救键值设置」——只影响类型 44。
// isEncode 为 false 时返回空：不能编码的终端不给设快捷键。
func ShortcutKeyOptions(typeID int, isEncode, emergency bool) []KeyOption {
	if !isEncode {
		return []KeyOption{}
	}

	n, ok := keyCounts[typeID]
	if !ok {
		n = 9
	}
	// 类型 44 只有急救模式下才是 3 个键；普通快捷键模式走默认的 9
	if typeID == 44 && emergency {
		n = 3
	}

	out := make([]KeyOption, 0, n)
	for k := 0; k < n; k++ {
		var v int
		switch {
		case in(zeroBasedTypes, typeID):
			v = k
		case in(tenToThirtyTypes, typeID):
			if k == 9 {
				v = KeyShortCircuit
			} else {
				v = k + 1
			}
		case typeID == 40:
			if k == 0 {
				v = KeyEmergency
			} else {
				v = KeyShortCircuit
			}
		case typeID == 33:
			if k == 2 {
				v = KeyShortCircuit
			} else {
				v = k + 1
			}
		default:
			v = k + 1
		}
		out = append(out, KeyOption{Value: v, Label: keyLabel(v, typeID, emergency)})
	}
	return out
}

// keyLabel 给键值配一句人话。
//
// 光显示「0」「30」看不出是紧急触发还是短路触发 —— ok112 的模板就是直接把数字
// 打出来的，现场只能靠记。这里把含义写在标签里。
func keyLabel(v, typeID int, emergency bool) string {
	switch {
	case v == KeyShortCircuit:
		return "30（短路触发）"
	case v == KeyEmergency && in(zeroBasedTypes, typeID):
		return "0（紧急触发）"
	case v == KeyEmergency && typeID == 40:
		return "0（紧急触发）"
	case emergency && typeID == 33 && (v == 1 || v == 2):
		// 甲方原话：「键1=急救1，2=急救2」
		return fmt.Sprintf("%d（急救%d）", v, v)
	default:
		return fmt.Sprintf("%d", v)
	}
}
