// Package termtype 是「这台终端的型号，能不能出现在这个选择器里」的唯一一份判据。
//
// # 来历
//
// 旧版 ok112 把这件事收在 `inc/config.inc.php` 的
//
//	get_terminal_type($flag, $error, $id, $single)
//
// 一个 switch，16 个分支，每个分支一条写死的 SQL。各页面在渲染终端树之前先调它
// 拿到「允许的型号 id 列表」，再拿这个列表去筛终端。
//
// 我们照搬的是**判据**，不是那个函数的形状：这里导出的是 SQL 片段，各模块拼进
// 自己的查询里，不再单独查一次型号表。
//
// # 为什么这些名单没有规律
//
// 别试图从 `terminaltype` 的列里推出这些黑名单 —— 推不出来。它们是现场十几年
// 一个型号一个型号排掉的：服务器不出声、报警主机只收不放、编码器只发不收、
// LED 屏不是音频设备、应急终端有自己的入口……**照抄，不要"整理"**。
//
// 唯一有规律的是那个前置条件：
//
//	isdecode = 1   能解码音频 —— 也就是能出声
//	isencode = 1   能编码音频 —— 也就是能拾音
//
// # ⚠ 写成子查询，不要展开成常量
//
// 黑名单是「排除」而不是「列举」。写成
//
//	typeid IN (SELECT id FROM terminaltype WHERE … AND id NOT IN (…))
//
// 之后，现场装一个新型号，只要它 isdecode=1 且不在黑名单里，就会自动出现在树上。
// 换成把当前允许的型号号码展开成常量的话，新型号会**静默消失**，
// 而没有人会想到来看这个文件。
//
// # ⚠ 加了筛选，就要处理「已经绑着、但型号被筛掉」的终端
//
// 一条老任务里可能绑着现在筛不出来的终端（型号后来被排掉了，或者当初就是
// 绕过界面塞进去的）。编辑那条任务时树上没有它，勾不上也就保存不回去。
// **不能悄悄丢** —— 前端各页在回填之后要对一次账，把树上没有的摘掉并说一句
// 「有 N 台因型号不支持没显示」。TypedTaskPage 的 sound 分支是最早这么做的，
// 别的页照它来。
package termtype

import "fmt"

/*
 * 下面每个常量都标着它在 ok112 里的 flag 号和调用它的页面，
 * 方便下一个人拿着页面名回去对照原版。
 *
 * 已经用上的：
 *
 *   Broadcast  flag 3   作息方案 / 文件广播 / 终端功放 / 采播管理 / led播放 /
 *                       声场任务 / 声场分区 / 音乐传输 / 报警分区 / 寻呼组成员
 *   TTS        flag 16  文字语音（比 flag 3 多排掉一个 18）
 *   LEDScreen  flag 14  LED 屏设备（只有型号 42）
 *
 * 记在这里但**还没接**的（对应的页面目前没有按型号筛，接的时候直接用）：
 *
 *   flag 7   终端分区 / 用户的「可控制终端」
 *            id NOT IN (0,6,7,8,9,16,18,22,25,29,31,32,43,12,16)
 *   flag 2   对讲/寻呼**宿主**那一侧（判 isencode，不是 isdecode）
 *   flag 15  目录分区（比 flag 16 少排掉 28 和 42）
 *   flag 5   isLCD >= 1，下发终端密码时用
 *   flag 4   shortkeycount >= 1，遥控器按键映射
 */

// Broadcast 是 get_terminal_type(3)：**能放广播的终端**。
//
//	SELECT id FROM terminaltype
//	 WHERE isdecode = '1'
//	   AND id NOT IN (0,26,2,7,8,9,10,12,15,16,17,21,22,25,28,29,30,31,32,36,37,40,41,42)
//
// 绝大多数「给任务挑终端」的地方用的都是它。
const broadcastExclude = `0,2,7,8,9,10,12,15,16,17,21,22,25,26,28,29,30,31,32,36,37,40,41,42`

// TTS 是 get_terminal_type(16)：**文字语音**那一页的终端。
//
//	SELECT id FROM terminaltype
//	 WHERE isdecode = '1'
//	   AND id NOT IN (0,26,2,7,8,9,10,12,15,16,17,18,21,22,25,28,29,30,31,32,36,37,40,41,42)
//
// ⚠ 与 Broadcast **只差一个 18**。差这一个是有意的，别合并成一个常量：
// 旧版 taskttsadd.php / taskttsmodify.php 用的就是 16，别的任务页用的是 3。
const ttsExclude = `0,2,7,8,9,10,12,15,16,17,18,21,22,25,26,28,29,30,31,32,36,37,40,41,42`

// ledScreenTypes 是 get_terminal_type(14)：LED 屏设备，只有型号 42。
const ledScreenTypes = `42`

// Kind 是选择器的用途。
type Kind string

const (
	// KindBroadcast 给任务挑播音终端（ok112 flag 3）
	KindBroadcast Kind = "broadcast"
	// KindTTS 文字语音那一页（ok112 flag 16）
	KindTTS Kind = "tts"
	// KindLEDScreen LED 屏设备（ok112 flag 14）
	KindLEDScreen Kind = "ledScreen"
)

// Cond 返回一段可以直接拼进 WHERE 的 SQL：`<alias>.typeid IN (…)`。
//
// alias 是 terminal 表在那条查询里的别名（通常是 "t"）。
// 不认识的 Kind 按 KindBroadcast 处理 —— 少筛一点比多筛一点安全：
// 多筛会让终端凭空消失，少筛只是多列几台。
func Cond(alias string, k Kind) string {
	switch k {
	case KindLEDScreen:
		return fmt.Sprintf(`%s.typeid IN (%s)`, alias, ledScreenTypes)
	case KindTTS:
		return decodeCond(alias, ttsExclude)
	default:
		return decodeCond(alias, broadcastExclude)
	}
}

func decodeCond(alias, exclude string) string {
	return fmt.Sprintf(`%s.typeid IN (
		SELECT id FROM terminaltype
		 WHERE COALESCE(isdecode,0) = 1
		   AND id NOT IN (%s)
	)`, alias, exclude)
}

// KindOfTaskKind 把 typedtask 的 kind（amplifier / collect / tts / led / sound）
// 翻成这里的 Kind。
//
// 只有文字语音是特例（flag 16），其余四类都跟着 flag 3 ——
// 这正是旧版那五个页面各自调的 flag。
func KindOfTaskKind(taskKind string) Kind {
	if taskKind == "tts" {
		return KindTTS
	}
	return KindBroadcast
}
