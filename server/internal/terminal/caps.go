package terminal

// 「这台终端支持哪些批量操作」。
//
// # 判据来源
//
// 下面每一条都是**甲方直接给的条件**（2026-08-27），不是我从 ok112 或 :80 反推的。
// 反推过一版，有三处对不上，以本文件为准：
//
//	对讲      我按 ok112 内联判成了「isdecode 或 isencode」，实际是**两个都要**
//	快捷键    排除表里漏了 24
//	快捷任务  白名单里多了 8
//
// 而且**对讲与发言判据不同**：对讲要 `isdecode=1 && isencode=1`，
// 发言只要「不是两个都为 0」。早前两者共用一个 requireCodec，等于把发言判严了。
//
// # 只写在这里
//
// 规则只在服务端这一处。列表接口把算好的 Caps 随行下发，界面拿去置灰菜单项；
// 界面的置灰只是方便，真正的拦截在 control.go 的 gate 上 ——
// 接口被直接调用时按同一套规则跳过，并在 skipped 里逐台说明原因。
//
// ⚠ in(...) 里的数字都是 `terminaltype.id`，不是终端 id。别凭直觉增删。

// TypeTraits 是 terminaltype 里与「能不能做某事」有关的几列。
type TypeTraits struct {
	TypeID        int
	IsDecode      bool
	IsEncode      bool
	IsLCD         bool
	IsSpeech      bool
	ShortKeyCount int
	SwitchCount   int
}

// Caps 是按上述判据算出来的能力集。
type Caps struct {
	// Speech 启用/停用**对讲**：isdecode=1 且 isencode=1（两个都要）
	Speech bool `json:"speech"`
	// Sponsor 启用/停用**发言**：isdecode 与 isencode 全为 0 才不支持
	Sponsor bool `json:"sponsor"`
	// Shortcut 快捷键（查看 / 删除）
	Shortcut bool `json:"shortcut"`
	// QuickTask 快捷任务
	QuickTask bool `json:"quickTask"`
	// Instancy 设置 / 取消急救：isspeech = 1
	Instancy bool `json:"instancy"`
	// AuthPaging 授权寻呼 / 授权终端（两者判据相同）
	AuthPaging bool `json:"authPaging"`
	// AutoCheck 自动寻检：isdecode 与 isencode 全为 0 才不支持
	AutoCheck bool `json:"autoCheck"`
	// Circuit 线路检测：同上（ok112 里也是判 isdecode 与 isencode 全为 0 才拒绝）。
	// ⚠ 与 Sponsor / AutoCheck 眼下取值相同，但它们是三条各自独立的业务规则，
	//   分开写，将来改其中一条不会误伤另外两条。
	Circuit bool `json:"circuit"`
	// Password 设置终端密码。
	// ⚠ 甲方给的条件里没有这一项，沿用 ok112 set_terminal_password.php 的
	//   get_terminal_type(5) = `isLCD >= 1 AND id NOT IN(28,34,35,36,37)`。
	//   道理也直白：密码要在终端自己的屏上输，没屏的终端设了没用。
	Password bool `json:"password"`
	// Switch 电源开关（ok112 flag 6：switchcount > 1）。目前界面上没有用到。
	Switch bool `json:"switch"`
	// EmergencyPlay 应急播放：typeid = 41。不在批量操作菜单里，留着备用。
	EmergencyPlay bool `json:"emergencyPlay"`
	// EmergencyHost 急救终端本身：typeid = 33 或 34。
	// ⚠ 这是「急救指向哪台终端」用的候选集，不是「哪些终端能被设置急救」。
	//   后者是 Instancy。做急救对话框时才会用到。
	EmergencyHost bool `json:"emergencyHost"`
}

func in(set map[int]bool, id int) bool { return set[id] }

func setOf(ids ...int) map[int]bool {
	m := make(map[int]bool, len(ids))
	for _, v := range ids {
		m[v] = true
	}
	return m
}

var (
	// 快捷键（查看 / 删除）：
	//   SELECT id FROM terminaltype
	//   WHERE id NOT IN(0,1,6,7,9,10,12,13,15,16,19,20,21,22,23,24,27,29,33,36,37,38,39)
	shortcutExclude = setOf(0, 1, 6, 7, 9, 10, 12, 13, 15, 16, 19, 20, 21, 22, 23, 24,
		27, 29, 33, 36, 37, 38, 39)

	// 快捷任务：
	//   SELECT id FROM terminaltype WHERE id IN(1,2,5,11,17,23,24,26,30,38,34,41,44)
	quickTaskAllow = setOf(1, 2, 5, 11, 17, 23, 24, 26, 30, 34, 38, 41, 44)

	// 授权寻呼 / 授权终端（两者判据相同）：
	//   SELECT id FROM terminaltype WHERE isencode='1'
	//   AND id NOT IN(0,1,6,7,8,9,10,11,12,13,15,16,18,19,20,21,22,23,24,25,27,29,31,32,36,37,38,39)
	authPagingExclude = setOf(0, 1, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16, 18, 19, 20, 21,
		22, 23, 24, 25, 27, 29, 31, 32, 36, 37, 38, 39)

	// 设置终端密码的额外排除（在 isLCD>=1 之外再排掉这几个），来自 ok112 flag 5
	passwordExclude = setOf(28, 34, 35, 36, 37)

	// 急救终端本身的类型
	emergencyHostTypes = setOf(33, 34)
)

// emergencyPlayType 应急播放终端的类型。
const emergencyPlayType = 41

// CapsOf 算出一台终端支持哪些操作。
func CapsOf(t TypeTraits) Caps {
	// 「两个都为 0 才不支持」= 至少有一个为 1
	anyCodec := t.IsDecode || t.IsEncode
	return Caps{
		// ⚠ 对讲是**且**，不是或
		Speech:    t.IsDecode && t.IsEncode,
		Sponsor:   anyCodec,
		AutoCheck: anyCodec,
		Circuit:   anyCodec,

		Shortcut: !in(shortcutExclude, t.TypeID),
		// ⚠ 白名单之外还要真有键可用。
		//
		//   快捷任务是「按某个键执行某条任务」，键值可选集由 keyspec.go 按型号算，
		//   要求 isencode = 1 且该型号有按键数。而 ok112 抄来的 quickTaskAllow
		//   里有 6 个型号根本凑不出键：1 / 11 是 isencode = 0，
		//   23 / 24 / 38 是 shortkeycount = 0。
		//
		//   只判白名单的话，这些型号在界面上是「可点」的，点下去必然报错 ——
		//   菜单说支持、后端说不支持，用户看到的是一个内部错误。
		//   这里让 caps 反映真实可用性：凑不出键就是不支持。
		QuickTask: in(quickTaskAllow, t.TypeID) &&
			len(ShortcutKeyOptions(t.TypeID, t.IsEncode, false)) > 0,
		Instancy: t.IsSpeech,

		AuthPaging: t.IsEncode && !in(authPagingExclude, t.TypeID),

		Password: t.IsLCD && !in(passwordExclude, t.TypeID),
		Switch:   t.SwitchCount > 1,

		EmergencyPlay: t.TypeID == emergencyPlayType,
		EmergencyHost: in(emergencyHostTypes, t.TypeID),
	}
}
