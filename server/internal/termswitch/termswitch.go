// Package termswitch 说明「一台终端身上那几路开关，哪几路是电源、哪几路是分区」。
//
// # 这是什么
//
// terminaltype.switchcount 只告诉你**有几路**，不告诉你每一路是干什么的。
// 排任务的时候要逐路勾选（勾选结果落进 terminaloftask.area 这个 0/1 掩码），
// 界面上就得给每一路一个名字 —— 而「第 3 路叫什么」取决于终端是哪一类：
//
//	前置（terminaltype.id 4 / 34 / 36 / 39 / 47）
//	    前两路是电源，剩下 switchcount-2 路是分区
//	功放（terminaltype.id 5 / 24 / 37）
//	    全部 switchcount 路都是电源
//
// 其它型号没有这套语义，不给逐路勾选（Of 回一个空 Layout）。
//
// # 为什么型号号码写在这儿，而不是数据库或前端
//
// 数据库里没有这个信息 —— terminaltype 只有 switchcount 一列，没有任何一列
// 说得清「这几路是电源还是分区」，而 R1 红线不许加列。
//
// 前端也不合适：这是硬件事实，任务页、作息铃声页、九类任务页三处界面都要用，
// 开发者接口将来也可能要。抄三份的下场是改一处忘两处。
//
// ⚠ 这个包**不碰数据库、不引任何内部包**，就是一张查得到的表。
// 现场换了新型号要加号码，改这里一处。
package termswitch

// Kind 是终端在「逐路勾选」这件事上的类别。
type Kind string

const (
	// KindNone 没有逐路勾选这回事（大多数型号）。
	KindNone Kind = ""
	// KindPreamp 前置：前两路电源，其余分区。
	KindPreamp Kind = "preamp"
	// KindAmplifier 功放：全部是电源。
	KindAmplifier Kind = "amplifier"
)

// preampPower 是前置固定占掉的电源路数。
const preampPower = 2

// MaxSwitches 是能勾的路数上限 —— terminaloftask.area 是 varchar(16)，
// 一路一个字符，第 17 路存不下。
const MaxSwitches = 16

// 型号号码。查 terminaltype 表得到的：
//
//	 4 网络前置      5 网络功放
//	24 网络音柱/功放 34 网络前置
//	36 网络分区前置  37 网络功放
//	39 网络前置      47 网络前置
//
// ⚠ 14「分控前置」名字里也带前置，但不在这张表里 —— 是现场给的清单里没有它，
// 不是漏了。要加的话在这儿加，别在别处特判。
var (
	preampTypes    = map[int64]bool{4: true, 34: true, 36: true, 39: true, 47: true}
	amplifierTypes = map[int64]bool{5: true, 24: true, 37: true}
)

// Layout 是一台终端那几路开关的排法：前 Power 路是电源，接着 Zone 路是分区。
type Layout struct {
	Kind  Kind `json:"kind"`
	Power int  `json:"power"`
	Zone  int  `json:"zone"`
}

// Total 是这台终端一共能勾几路。
func (l Layout) Total() int { return l.Power + l.Zone }

// Pickable 表示这台终端要不要给出逐路勾选。
//
// ⚠ switchcount 为 0 的型号（现网的 24 / 39 / 47 就是）虽然在名单里，
// 也**不弹** —— 一路都没有的框弹出来是空的，比不弹更让人摸不着头脑。
func (l Layout) Pickable() bool { return l.Kind != KindNone && l.Total() > 0 }

// Of 按型号号码与 switchcount 算出这台终端的开关排法。
func Of(typeID int64, switchCount int) Layout {
	if switchCount < 0 {
		switchCount = 0
	}
	if switchCount > MaxSwitches {
		switchCount = MaxSwitches
	}

	switch {
	case preampTypes[typeID]:
		// switchcount 不到两路时全算电源，不能让 Zone 变成负数。
		power := preampPower
		if switchCount < power {
			power = switchCount
		}
		return Layout{Kind: KindPreamp, Power: power, Zone: switchCount - power}
	case amplifierTypes[typeID]:
		return Layout{Kind: KindAmplifier, Power: switchCount, Zone: 0}
	}
	return Layout{Kind: KindNone}
}
