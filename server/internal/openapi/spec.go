package openapi

import (
	"encoding/json"
	"strings"
)

// 接口目录（给「接口调用平台」和 OpenAPI 规格用）。
//
// # 为什么规格在后端，不在前端写死
//
// 前端写死一份接口清单，第二天后端改了参数，那份清单不会跟着变 ——
// 而它看起来还是对的。调用方照着一份过期的文档调，得到的错误跟文档对不上，
// 排查成本全落在他身上。
//
// 所以目录跟代码放在一起：改接口的人顺手就能看见它，改漏了在这个文件里很显眼。
// 前端只负责渲染。
//
// # 为什么还要吐一份标准 OpenAPI JSON
//
// 我们自己这个界面是给**人**看的（中文、带「为什么」、能直接试）。
// 但集成方的工程师往往想把接口导进 Postman / Apifox / 自己的代码生成器 ——
// 那些工具只认标准 OpenAPI。两份从同一个目录生成，不会分叉。

// Param 是一个 query 或路径参数。
type Param struct {
	Name string `json:"name"`
	// In 取 query / path。
	In       string `json:"in"`
	Type     string `json:"type"`
	Required bool   `json:"required"`
	Desc     string `json:"desc"`
	// Example 是「试一试」表单的预填值。填一个**真能跑通**的值，
	// 而不是 "string" 这种占位 —— 让人点一下就能看见真实返回，
	// 是这个界面比一份静态文档强的唯一理由。
	Example string `json:"example"`
}

// Field 是请求体里的一个字段。
type Field struct {
	Name     string `json:"name"`
	Type     string `json:"type"`
	Required bool   `json:"required"`
	Desc     string `json:"desc"`
}

// Example 是一个具名的场景示例：「我要干这一件事，请求体长这样」。
//
// # 为什么「修改任务」不能只给一段全字段示例
//
// 修改任务的字段**全部可选**，给一段把 30 个字段都写满的 JSON，
// 对接方照抄下来就是把每一项都覆盖一遍 —— 他想改的只是时长。
// 那段示例看起来最全，实际上没法直接用，还很危险。
//
// 所以分场景各给一段**最小**请求体：改时长就那一行，改音量就那一行。
// 照抄能用，才叫示例。
type Example struct {
	Title string `json:"title"`
	// Desc 说这一段**做了什么、以及顺带影响了什么**。
	Desc string `json:"desc"`
	Body string `json:"body"`
}

// Endpoint 是一个接口。
type Endpoint struct {
	ID      string `json:"id"`
	Method  string `json:"method"`
	Path    string `json:"path"`
	Summary string `json:"summary"`
	// Desc 说这个接口**是什么、什么时候用**，不是把参数再念一遍。
	Desc string `json:"desc"`
	// Right 是密钥归属账号需要的权限位。
	Right  string  `json:"right"`
	Params []Param `json:"params"`
	Fields []Field `json:"fields"`
	Body   string  `json:"body"`
	// Examples 是几段分场景的请求体，界面上点一下就填进「试一试」。
	// 只有全字段可选的接口（修改任务）才需要它 —— 见 Example 的注释。
	Examples []Example `json:"examples"`
	Sample   string    `json:"sample"`
	// Returns 逐条解释**响应示例里每个字段是什么**。
	//
	// ⚠ 光贴一段示例 JSON 是不够的：`"priority": 8` 里的 8 是什么意思？
	// 数字大就优先吗（不是，正好反过来）？`"weekdays": [7]` 的 7 是周日
	// 还是周六？集成方猜错了**不会报错**，只会在错误的那天广播。
	// 所以每个字段都要写出来，取值有编码的还要写明对照。
	Returns []Field `json:"returns"`
	// Notes 是「不写清楚就会变成故障」的那些事。
	Notes []string `json:"notes"`
	// Danger 为真时界面上会把「试一试」标红并要求再确认 ——
	// 这些接口会真的让喇叭响、或者真的删东西。
	Danger bool `json:"danger"`
	// Freeform 为真表示**这一条没有逐参数的说明**，平台上给一个可编辑的
	// 完整路径 + 自由 JSON 请求体。
	//
	// ⚠ 这是刻意的诚实，不是偷懒的借口：全功能那 200 多条接口的参数
	// 就是界面在用的那一套，逐条抄一遍势必抄错、也势必跟不上改动，
	// 而一份**看起来完整但有错**的参数说明，比明说「这里没有」更坏。
	// 要参数照着抄，用浏览器开发者工具看一次界面发的请求最准。
	Freeform bool `json:"freeform"`
}

// Group 是一组按**界面功能**归拢的接口。
type Group struct {
	Name string `json:"name"`
	Desc string `json:"desc"`
	// Prefix 是这一组接口的路径前缀。
	//
	// 「常用接口」那几组是 /openapi/v1（带版本号的稳定合同）；
	// 「全部功能接口」那些的 Path 本身就是完整路径（/api/...），Prefix 留空。
	Prefix string `json:"prefix"`
	// Section 把两类接口在界面上分开：curated / full。
	// 它们的定位不同（一个是稳定合同，一个跟着界面走），
	// 混在一起列会让人以为随便挑一个都一样。
	Section   string     `json:"section"`
	Endpoints []Endpoint `json:"endpoints"`
}

// CodeValue 是一个编码取值和它的含义。
type CodeValue struct {
	Value string `json:"value"`
	Means string `json:"means"`
	// Warn 为真时界面上标红 —— 这些是**猜必然猜错**的取值。
	Warn bool `json:"warn"`
}

// CodeTable 是「这个数字到底是什么意思」的对照表。
type CodeTable struct {
	Field string `json:"field"`
	Title string `json:"title"`
	// Desc 说它出现在哪、为什么要特别看一眼。
	Desc   string      `json:"desc"`
	Values []CodeValue `json:"values"`
}

// Spec 是整份目录。
type Spec struct {
	Title   string  `json:"title"`
	Version string  `json:"version"`
	Prefix  string  `json:"prefix"`
	Groups  []Group `json:"groups"`
	// Codes 是全站通用的取值对照表。
	//
	// # 为什么要单独有这么一张表
	//
	// 「全部功能接口」那 213 条返回的是**库里的原始值**：netstate、
	// projectstate、israndomplay、exemodel、tasktype……这些数字对集成方
	// 毫无意义，而其中好几个的取值是**反直觉**的：
	//
	//	projectstate  0 才是启用
	//	israndomplay  0 是随机、1 是顺序
	//	priority      数字**小**的优先级高
	//	exemodel      7 位掩码，**周日打头**
	//
	// 猜错这几个不会报错 —— 只会让广播在错误的时间、以错误的顺序、
	// 在错误的那一天响。所以它们必须摊开在平台上，而不是藏在某份文档的
	// 某一段里。
	Codes []CodeTable `json:"codes"`
}

// Catalog 返回接口目录。
//
// ⚠ 加接口时**这里也要加一条**。漏了的后果不是报错，而是这个接口对外
// 等于不存在 —— 没人知道它能用。
func Catalog() Spec {
	groups := []Group{
		groupQuery(),
		groupTask(),
		groupPlay(),
		groupSchedule(),
	}
	// 这四组都是 /openapi/v1 下的「常用接口」
	for i := range groups {
		groups[i].Prefix = "/openapi/v1"
		groups[i].Section = SectionCurated
	}
	return Spec{
		Title:   "IP数字网络广播系统 · 开发者接口",
		Version: "v1",
		Prefix:  "/openapi/v1",
		Groups:  groups,
		Codes:   codeTables(),
	}
}

// codeTables 是全站通用的取值对照。
//
// ⚠ 每一条都对着源码核过，不是照记忆写的。改这里之前先去看对应的常量：
//
//	task.StateEnabled / StateDisabled     internal/task/task.go
//	任务执行状态 state 的四个取值          internal/task/task.go 的 stateText
//	netstate / devicestate / taskstate    internal/terminal/control.go 的 SetRunning
//	                                      与 web 终端列表页的渲染
//	exemodel 的位序                        ok112/Browse_active_task.php 的 SQL
//	                                      （推导见 query_test.go）
//	holidaytime.projectstate               internal/holiday 的包注释
func codeTables() []CodeTable {
	return []CodeTable{
		{
			Field: "code",
			Title: "业务码（所有响应）",
			Desc:  "HTTP 状态码恒为 200，**看 body 里的 code**。",
			Values: []CodeValue{
				{"200", "成功", false},
				{"40001", "请求填错了。msg 里写了错在哪，照着改 —— 重试没用", false},
				{"401", "密钥无效：写错了 / 被停用 / 已过期 / 归属账号被停用", false},
				{"40301", "权限不够，或这个接口不开放给密钥调", false},
				{"40302", "服务器处于备机模式，全站只读", false},
				{"40401", "找不到这个对象", false},
				{"50001", "服务器出错，可以重试", false},
			},
		},
		{
			Field: "projectstate",
			Title: "任务的启停状态",
			Desc:  "出现在任务、作息方案、分类任务的返回里。⚠ **0 才是启用**，和直觉相反。",
			Values: []CodeValue{
				{"0", "启用（会按排期自动触发）", true},
				{"1", "停用（永远不会自动触发）", true},
			},
		},
		{
			Field: "state",
			Title: "任务的执行状态",
			Desc:  "这一条**此刻**在不在播。与 projectstate 是两件事：停用的任务 state 也可能是历史遗留值。",
			Values: []CodeValue{
				{"0", "准备（没在播）", false},
				{"1", "执行中（正在播）", false},
				{"2", "已停止", false},
				{"3", "立即执行（刚被启动）", false},
			},
		},
		{
			Field: "israndomplay",
			Title: "播放顺序",
			Desc:  "⚠ 取值反直觉：**0 是随机、1 是顺序**。列名里的 random 会让人读反。",
			Values: []CodeValue{
				{"0", "随机播放", true},
				{"1", "顺序播放", true},
			},
		},
		{
			Field: "priority",
			Title: "任务级别（优先级）",
			Desc:  "范围 10~109。⚠ **数字小的优先级高** —— 10 最高、109 最低。两条任务抢同一台终端时，数字小的那条赢。",
			Values: []CodeValue{
				{"10", "最高。立即播放用的就是它", true},
				{"109", "最低。新建任务不填优先级时的默认值", true},
			},
		},
		{
			Field: "exemodel",
			Title: "星期掩码（哪几天执行）",
			Desc:  "7 个字符的 0/1 串。⚠ **周日打头**，不是周一 —— 位序依据是旧系统判「今天该不该响」的那条 SQL。/openapi/v1 那组接口已经替你转成了 1=周一…7=周日 的 weekdays，不必自己解这个串。",
			Values: []CodeValue{
				{"第 1 位", "周日", true},
				{"第 2~7 位", "周一 … 周六", true},
				{`"0000000"`, "手动任务 —— 后台**永远不会**自动触发它", true},
				{`"0111110"`, "周一到周五", false},
				{`"1111111"`, "每天", false},
			},
		},
		{
			Field: "timelengthtype",
			Title: "时长的单位",
			Desc:  "决定同一行里 timelength 那个数字怎么读。⚠ 读错的后果是算出来的结束时间差出十几分钟。",
			Values: []CodeValue{
				{"1", "timelength 是**秒数**", false},
				{"2", "timelength 是**循环次数**（播完清单算一次）", true},
			},
		},
		{
			Field: "tasktype",
			Title: "任务类型",
			Desc:  "同一张 task 表装了好几种任务，靠它区分。",
			Values: []CodeValue{
				{"1", "作息方案里的打铃条目", false},
				{"2", "文件广播任务（最常见的那种）", false},
				{"7", "文件广播的另一种取值，现网无数据但旧查询一直带着", false},
				{"9", "功放子任务（跟着主任务自动建的）", false},
				{"15", "作息方案里的文件播放", false},
				{"30 / 24", "LED 字幕子任务（24 是旧数据）", false},
			},
		},
		{
			Field: "netstate / devicestate / taskstate",
			Title: "终端的三个状态",
			Desc:  "三个是**并列**的三件事，别混。⚠ devicestate 是「启动/停止」这个运行开关（对应界面上的启动终端 / 停止终端），**不是电源**。",
			Values: []CodeValue{
				{"netstate = 1", "网络在线；其它值 = 离线", false},
				{"devicestate = 1", "已启动；0 = 已停止", true},
				{"taskstate = 1", "正在播任务；0 = 空闲", false},
			},
		},
		{
			Field: "holidaytime.projectstate",
			Title: "节假日的启停状态",
			Desc:  "⚠ **和 task 表正好相反**，这是旧系统留下的。改节假日时别照搬任务那套。",
			Values: []CodeValue{
				{"1", "启用（这一天按节假日规则走）", true},
				{"0", "停用", true},
			},
		},
		{
			Field: "enabletask.enstate",
			Title: "启用计划里的动作",
			Desc:  "「到点把这批任务怎么样」。与 task.projectstate 同一套取值。",
			Values: []CodeValue{
				{"0", "到点启用", false},
				{"1", "到点停用", false},
			},
		},
	}
}

// 两类接口的分区标记。
// noteZoneID 解释「为什么请求体里没有分区号」。
//
// ⚠ 这一条必须挂在**每个会写终端清单的接口**上。库里 terminaloftask 除了
// 终端号还存一个分区号，后台下发时读的就是它 —— 对接方看不到这个参数，
// 合理的怀疑是「我漏传了、任务会播错」。不说清楚，他要么白白担心，
// 要么去猜一个字段名硬塞。
const noteZoneID = "接口里**没有分区号这个参数，也不需要你传**。" +
	"库里 terminaloftask 除了终端号还存一台终端的分区号（后台下发时读它），" +
	"这一列由服务端按终端**此刻**所属的分区自己算：分区 2 的终端存 2，没归分区的存 0。" +
	"界面上那张表单会传它，是因为它手里本来就有终端选择器那份数据；" +
	"接口这边由服务端算，少一个能填错的地方 —— 分区改了也不用你跟着改。"

const (
	// SectionCurated 是 /openapi/v1 那一组：编号寻址（名字也认）、参数是人话、
	// 路径带版本号、**只增不改**。集成时优先用它。
	SectionCurated = "curated"
	// SectionFull 是全部功能接口（/api）：界面用什么，它就是什么，
	// 覆盖每一个页面功能，但**跟着界面走**，页面改版时可能变。
	SectionFull = "full"
)

func groupQuery() Group {
	return Group{
		Name: "查询",
		Desc: "只读。看得见什么由密钥归属账号的可见范围决定，和那个人登录界面看到的一模一样。",
		Endpoints: []Endpoint{
			{
				ID: "terminals.list", Method: "GET", Path: "/terminals",
				Summary: "终端状态",
				Desc:    "有哪些终端、在不在线、正在播什么、音量多少。集成方最常调的一个 —— 下发广播之前先看看设备在不在。",
				Right:   "有效密钥即可",
				Params: []Param{
					{Name: "zone", In: "query", Type: "int/string", Desc: "只看某个分区的。**分区编号**或分区名都行（分区名不会重复，两种都安全）", Example: ""},
					{Name: "keyword", In: "query", Type: "string", Desc: "按终端名搜", Example: ""},
					{Name: "pageNum", In: "query", Type: "int", Desc: "页码，从 1 开始", Example: "1"},
					{Name: "pageSize", In: "query", Type: "int", Desc: "每页几条，默认 20、最大 200", Example: "20"},
				},
				Sample: `{
  "list": [{
    "id": 2, "name": "A102教室音箱", "type": "一体化音箱", "zone": "教学楼",
    "ip": "192.168.2.12",
    "online": true, "playing": false, "stateText": "在线空闲",
    "volume": 75, "running": true, "runStateText": "已启动"
  }],
  "total": 12, "pageNum": 1, "pageSize": 20
}`,
				Returns: []Field{
					{Name: "list[].id", Type: "int", Desc: "终端编号。下发广播时可以用它，也可以直接用 name"},
					{Name: "list[].name", Type: "string", Desc: "终端名。就是界面上显示的那个，也是寻址时写的名字"},
					{Name: "list[].type", Type: "string", Desc: "设备型号，如「一体化音箱」。只是展示，不参与寻址"},
					{Name: "list[].zone", Type: "string", Desc: "所属分区名。没归分区的是「(未分区)」，不是空串"},
					{Name: "list[].ip", Type: "string", Desc: "终端当前 IP。离线时可能是最后一次的"},
					{Name: "list[].online", Type: "bool", Desc: "网络在线。对应库里 netstate = 1"},
					{Name: "list[].playing", Type: "bool", Desc: "此刻正在播任务。⚠ 与 online 是两件事；离线时恒为 false"},
					{Name: "list[].stateText", Type: "string", Desc: "上面两个的中文合并版：离线 / 播放中 / 在线空闲。给人看的，程序请判 online 和 playing"},
					{Name: "list[].volume", Type: "int", Desc: "当前音量，0~100"},
					{Name: "list[].running", Type: "bool", Desc: "运行开关是不是打开的（界面上的「启动终端 / 停止终端」）。⚠ 不是电源"},
					{Name: "list[].runStateText", Type: "string", Desc: "上一条的中文版：已启动 / 已停止"},
					{Name: "total", Type: "int", Desc: "符合条件的总条数（不是本页条数）。翻页时按它算总页数"},
					{Name: "pageNum", Type: "int", Desc: "当前页码，从 1 开始"},
					{Name: "pageSize", Type: "int", Desc: "本次每页几条。传什么就是什么，不会被静默改掉"},
				},
				Notes: []string{
					"online 是网络在线，playing 是正在播任务 —— 两件事。离线的终端 playing 恒为假。",
					"没归到任何分区的终端，zone 是「(未分区)」。",
				},
			},
			{
				ID: "media.list", Method: "GET", Path: "/media",
				Summary: "媒体列表",
				Desc:    "媒体库里有哪些音频。想按名字播一首歌，先用它确认名字对不对。",
				Right:   "有效密钥即可",
				Params: []Param{
					{Name: "keyword", In: "query", Type: "string", Desc: "按媒体名搜", Example: ""},
					{Name: "folderId", In: "query", Type: "int", Desc: "只看某个目录的", Example: ""},
					{Name: "pageNum", In: "query", Type: "int", Desc: "页码", Example: "1"},
					{Name: "pageSize", In: "query", Type: "int", Desc: "每页几条", Example: "20"},
				},
				Sample: `{
  "list": [{ "id": 125, "name": "10.起床号.mp3", "seconds": 89,
             "sizeKB": 1406, "folderId": 2, "type": "mp3" }],
  "total": 1, "pageNum": 1, "pageSize": 20
}`,
				Returns: []Field{
					{Name: "list[].id", Type: "int", Desc: "媒体编号"},
					{Name: "list[].name", Type: "string", Desc: "媒体名（含扩展名）。就是寻址时写的名字"},
					{Name: "list[].seconds", Type: "int", Desc: "时长，秒。⚠ 0 表示库里没记时长，不是「零秒」——建任务时会退回按兜底值算"},
					{Name: "list[].sizeKB", Type: "int", Desc: "文件大小，KB"},
					{Name: "list[].folderId", Type: "int", Desc: "所在媒体目录的编号"},
					{Name: "list[].type", Type: "string", Desc: "文件类型，如 mp3、wav"},
					{Name: "total / pageNum / pageSize", Type: "int", Desc: "分页信息，含义同终端列表"},
				},
				Notes: []string{"seconds 为 0 表示库里没记时长，不是「零秒」。"},
			},
			{
				ID: "tasks.list", Method: "GET", Path: "/tasks",
				Summary: "任务列表",
				Desc:    "文件广播任务 —— 就是界面「任务管理」那一页列的东西。作息方案里的打铃不在这里，看下面那一组。",
				Right:   "有效密钥即可",
				Params: []Param{
					{Name: "keyword", In: "query", Type: "string", Desc: "按任务名搜", Example: ""},
					{Name: "folderId", In: "query", Type: "int", Desc: "只看某个任务分组的", Example: ""},
					{Name: "pageNum", In: "query", Type: "int", Desc: "页码", Example: "1"},
					{Name: "pageSize", In: "query", Type: "int", Desc: "每页几条", Example: "20"},
				},
				Sample: `{
  "list": [{
    "id": 1008, "name": "升旗仪式-国歌", "note": "每周一",
    "enabled": true, "running": true, "stateText": "执行中",
    "startDate": "2026-01-01", "endDate": "2026-12-31",
    "playTime": "07:50:00", "endTime": "07:53:00",
    "weekdays": [7], "priority": 8, "volume": 90,
    "media": [{ "id": 138, "name": "中华人民共和国国歌.mp3" }],
    "terminals": [{ "id": 8, "name": "操场号角01" }]
  }],
  "total": 6, "pageNum": 1, "pageSize": 20
}`,
				Returns: []Field{
					{Name: "list[].id", Type: "int", Desc: "任务编号"},
					{Name: "list[].name", Type: "string", Desc: "任务名。也是寻址时写的名字"},
					{Name: "list[].note", Type: "string", Desc: "备注（库里 task.info）。⚠ 只读，改不了；作息任务上这一列才是方案名，这里只是备注"},
					{Name: "list[].enabled", Type: "bool", Desc: "启没启用。true 对应库里 projectstate = 0（那一列 0 才是启用）"},
					{Name: "list[].running", Type: "bool", Desc: "此刻正在播。⚠ 与 enabled 是两件事"},
					{Name: "list[].stateText", Type: "string", Desc: "执行状态的中文：准备 / 执行中 / 已停止 / 立即执行"},
					{Name: "list[].startDate", Type: "string", Desc: "生效起始日 YYYY-MM-DD。不在区间内的日子不会触发"},
					{Name: "list[].endDate", Type: "string", Desc: "生效结束日 YYYY-MM-DD"},
					{Name: "list[].playTime", Type: "string", Desc: "每天几点开播 HH:MM:SS"},
					{Name: "list[].endTime", Type: "string", Desc: "每天几点结束 HH:MM:SS"},
					{Name: "list[].weekdays", Type: "数组", Desc: "哪几天播。**1=周一 … 7=周日**。⚠ 空数组 = 手动任务，后台永远不会自动触发它"},
					{Name: "list[].priority", Type: "int", Desc: "任务级别 10~109。⚠ **数字小的优先级高**，抢同一台终端时小的赢"},
					{Name: "list[].volume", Type: "int", Desc: "播放音量 0~100"},
					{Name: "list[].media[]", Type: "数组", Desc: "播放清单，**按数组顺序播**。每项 {id, name}"},
					{Name: "list[].terminals[]", Type: "数组", Desc: "播到哪些终端。每项 {id, name}。⚠ 这里是终端不是分区 —— 分区在建任务时就展开了"},
					{Name: "total / pageNum / pageSize", Type: "int", Desc: "分页信息"},
				},
				Notes: []string{
					"enabled = 这条任务启没启用（停用的永不触发）；running = 此刻正在播。两件事。",
					"weekdays 是 1=周一 … 7=周日。空数组表示手动任务，后台永远不会自动触发它。",
					"note 是只读的备注，改不了。",
				},
			},
			{
				ID: "schedules.list", Method: "GET", Path: "/schedules",
				Summary: "作息方案清单",
				Desc:    "有哪些作息方案，每个里面有几条铃、几条是启用的。",
				Right:   "有效密钥即可",
				Sample:  `[{ "name": "春季作息", "tasks": 7, "enabled": 7 }]`,
				Returns: []Field{
					{Name: "[].name", Type: "string", Desc: "方案名。⚠ 方案**没有编号**，这个名字就是它的身份，寻址只能用它"},
					{Name: "[].tasks", Type: "int", Desc: "方案里一共有几条打铃"},
					{Name: "[].enabled", Type: "int", Desc: "其中处于**启用**状态的有几条。与 tasks 不相等时说明这个方案被部分停用了"},
				},
				Notes: []string{"方案没有编号，名字就是它的身份。"},
			},
			{
				ID: "schedules.get", Method: "GET", Path: "/schedules/{name}",
				Summary: "作息方案详情",
				Desc:    "一个方案里几点打什么铃、打到哪些终端。",
				Right:   "有效密钥即可",
				Params: []Param{
					{Name: "name", In: "path", Type: "string", Required: true,
						Desc: "方案名。⚠ 方案**没有编号**，名字就是它的身份，这里只能写名字。界面会自动做 URL 编码", Example: "春季作息"},
				},
				Sample: `{
  "name": "春季作息", "volume": 66, "priority": 10,
  "terminals": [{ "id": 1, "name": "A101教室音箱" }],
  "items": [{
    "taskId": 1001, "name": "早读预备铃", "playTime": "07:20:00", "enabled": true,
    "seconds": 30, "loopTimes": 0, "weekdays": [1,2,3,4,5],
    "startDate": "2026-01-01", "endDate": "2026-12-31",
    "media": [{ "id": 125, "name": "10.起床号.mp3" }]
  }]
}`,
				Returns: []Field{
					{Name: "name", Type: "string", Desc: "方案名"},
					{Name: "volume", Type: "int", Desc: "**方案级**音量 0~100，方案里所有条目共用一份"},
					{Name: "priority", Type: "int", Desc: "**方案级**任务级别 10~109，数字小的优先级高"},
					{Name: "terminals[]", Type: "数组", Desc: "这个方案打到哪些终端。每项 {id, name}。方案里所有条目共用同一份终端清单"},
					{Name: "items[].taskId", Type: "int", Desc: "这条打铃在 task 表里的编号"},
					{Name: "items[].name", Type: "string", Desc: "条目名，如「早读预备铃」"},
					{Name: "items[].playTime", Type: "string", Desc: "几点打 HH:MM:SS"},
					{Name: "items[].enabled", Type: "bool", Desc: "这一条启没启用。方案可以被部分停用"},
					{Name: "items[].seconds", Type: "int", Desc: "播多少秒。⚠ 与 loopTimes **只有一个非零**"},
					{Name: "items[].loopTimes", Type: "int", Desc: "把清单循环几遍。按次数循环的条目没有确定秒数，所以那时 seconds 给 0"},
					{Name: "items[].weekdays", Type: "数组", Desc: "哪几天打，1=周一 … 7=周日"},
					{Name: "items[].startDate / endDate", Type: "string", Desc: "这一条的生效区间。理论上组内一致，但「智能排课」能按条目改"},
					{Name: "items[].media[]", Type: "数组", Desc: "这一条播什么。每项 {id, name}"},
				},
				Notes: []string{
					"seconds 与 loopTimes 只有一个非零：按次数循环的条目没有确定的秒数，所以 seconds 给 0、次数放在 loopTimes 上。",
				},
			},
			{
				ID: "play.list", Method: "GET", Path: "/play",
				Summary: "还在播的立即播放",
				Desc:    "你们发起过、还没停掉的那些。建议定期调一下做清理 —— 比如自己的程序崩溃重启之后。",
				Right:   "有效密钥即可",
				Sample: `[{
  "playId": 1, "taskId": 70231, "media": "中华人民共和国国歌.mp3",
  "terminals": [{ "id": 8, "name": "操场号角01" }],
  "volume": 90, "seconds": 46, "state": "playing",
  "startTime": "2026-09-08 08:00:52", "endTime": ""
}]`,
				Returns: []Field{
					{Name: "playId", Type: "int", Desc: "这次播放的句柄。⚠ **记下来** —— 停止时要用它"},
					{Name: "taskId", Type: "int", Desc: "为这次播放临时建的任务编号。停止时会连它一起删掉，不用你管"},
					{Name: "media", Type: "string", Desc: "正在播的媒体名"},
					{Name: "terminals[]", Type: "数组", Desc: "播到了哪些终端。每项 {id, name}。分区在这里已经展开成终端了"},
					{Name: "volume", Type: "int", Desc: "音量 0~100"},
					{Name: "seconds", Type: "int", Desc: "最多播多久，秒。没指定时取的是媒体自身时长"},
					{Name: "state", Type: "string", Desc: "playing = 还在播；stopped = 已停止"},
					{Name: "startTime", Type: "string", Desc: "开始时间 YYYY-MM-DD HH:MM:SS"},
					{Name: "endTime", Type: "string", Desc: "停止时间。还在播时是空串"},
				},
			},
		},
	}
}

func groupTask() Group {
	return Group{
		Name: "任务",
		Desc: "文件广播任务的增删改启停。新建是一次调用带齐媒体、终端、任务信息 —— 与界面「新建任务」那一屏对应。媒体、终端、分区、任务本身都**用编号寻址**最稳（编号唯一，名字不一定）。",
		Endpoints: []Endpoint{
			{
				ID: "tasks.create", Method: "POST", Path: "/tasks",
				Summary: "新建任务",
				Desc:    "一次调用把播放清单、终端/分区、时间与音量一起收下。不用先建任务再挂媒体再挂终端 —— 那样中间任何一步失败都会留下一条残缺的任务，它在界面上看着正常，到点却什么都不播。",
				Right:   "任务管理（taskpriv）",
				Fields:  taskFields(),
				Body: `{
  "name": "课间音乐",
  "folder": 1,

  "media": [124, 125],
  "terminals": [2, { "terminal": 1, "area": "11110000" }],
  "zones": [1],

  "playTime": "09:50",
  "endTime": "10:00:00",
  "startDate": "2026-09-01",
  "endDate": "2027-01-31",
  "weekdays": [1, 2, 3, 4, 5],
  "disableDay": "2026-10-01",

  "seconds": 600,
  "interval": { "everySeconds": 300, "playSeconds": 30 },

  "volume": 70,
  "priority": 20,
  "prePower": 10,
  "sequential": true,
  "multicast": false,
  "localFirst": false,
  "enabled": true,

  "led": {
    "text": "课间休息，请到操场活动",
    "speed": 5,
    "mode": 0,
    "devices": [{ "terminal": 1, "deviceId": 1 }]
  }
}`,
				Sample: `{
  "id": 70230, "mediaCount": 2, "terminalCount": 4,
  "name": "课间音乐", "folderId": 1,
  "playTime": "09:50:00", "endTime": "10:05:00",
  "seconds": 900, "loopTimes": 0,
  "volume": 70, "priority": 20, "enabled": true
}`,
				Returns: []Field{
					{Name: "id", Type: "int", Desc: "任务编号。之后改它、启停它、删它都用这个"},
					{Name: "mediaCount", Type: "int", Desc: "实际写进去几条媒体"},
					{Name: "terminalCount", Type: "int", Desc: "实际写进去几台终端。⚠ **对一下这个数** —— 给了分区的话它是展开后的终端数，比你传的条目多是正常的"},
					{Name: "name / folderId", Type: "混合", Desc: "存下来的任务名与所属分组编号。没给 folder 时它是服务端替你挑的那个分组"},
					{Name: "playTime", Type: "string", Desc: "每天几点开播 HH:MM:SS"},
					{Name: "endTime", Type: "string", Desc: "几点结束。⚠ **这一项常常是服务端算的** —— 没传 endTime 时按「开播时刻 + 时长」算；改任务时只要动了时长或挪了开播时刻，它也会跟着重算。对一眼这个值，别等到广播比预期多响五分钟才发现"},
					{Name: "seconds", Type: "int", Desc: "按秒播时的时长。⚠ 与 loopTimes **只有一个非零** —— 库里共用一列，这里替你分开了。都没传时它是按媒体总时长算出来的"},
					{Name: "loopTimes", Type: "int", Desc: "按遍数播时的循环次数"},
					{Name: "volume", Type: "int", Desc: "存下来的音量 0~100。没传时是默认的 80"},
					{Name: "priority", Type: "int", Desc: "存下来的任务级别。⚠ 没传时取的是你能用的**最低**一档（数字最大），会被别的广播压住"},
					{Name: "enabled", Type: "bool", Desc: "存完是启用还是停用。库里 projectstate 0 才是启用，这里已经翻成正常的布尔"},
				},
				Notes: []string{
					"回执里除了编号，还有**服务端替你定下来的那几项**（endTime / seconds / priority / folderId / volume）—— 这些是你没传时它自己决定的值，建完对一眼比事后查库快。要看全貌用「任务详情」。",
					"示例里的数字都是**编号**：folder 1 = 任务分组「admin」，media 124/125 = 两条媒体（在「媒体列表」查），terminal 1/2 = 两台终端（在「终端状态」查），zone 1 = 分区「教学楼」。写名字也认，但见下一条。",
					"⚠ **能用编号就用编号**。终端名和媒体名在库里**没有唯一约束**，是真的可以重名的 —— 重名时接口只能报错让你改用编号，而这个错会在你上线之后才出现。分区名、任务分组名、媒体目录名建的时候就挡了重名，用名字是安全的；作息方案则相反，它根本没有编号，只能用名字。",
					"⚠ weekdays 留空 = 手动任务，永远不会自动响。想每天响要写 [1,2,3,4,5,6,7]。默认成「每天」太危险 —— 少写一个字段就变成每天全校广播。",
					noteZoneID,
					"⚠ 分区是在写入那一刻展开成终端的。建完任务再往分区里加终端，这条任务不会自动带上它 —— 加了终端要重新调一次「修改任务」。",
					"不给 priority 时取最低一档，会被别的广播压住。要它优先就自己填一个小数字。",
				},
				Danger: true,
			},
			{
				ID: "tasks.get", Method: "GET", Path: "/tasks/{ref}",
				Summary: "任务详情",
				Desc:    "一条任务的全貌 —— 建任务时能填的每一项，这里都能读回来。建完之后用它核对「我填的那些真的写进去了吗」；列表接口给的是摘要，间隔播放、LED 字幕、终端区域掩码都不在里面。",
				Right:   "有效密钥即可",
				Params: []Param{
					// 预填一个演示库里**真实存在**的任务：这一条是只读的，
					// 点「试一试」要能看见真返回 —— 那是这个平台比静态文档强的地方。
					{Name: "ref", In: "path", Type: "int/string", Required: true,
						Desc: "**任务编号**（推荐，唯一），也认任务名", Example: "1008"},
				},
				Sample: `{
  "id": 70230, "name": "课间音乐", "note": "", "folderId": 1,
  "media": [{ "id": 124, "name": "大课间.mp3" }],
  "terminals": [{ "id": 1, "name": "A101教室音箱", "area": "11110000", "deleted": false }],
  "startDate": "2026-09-01", "endDate": "2027-01-31",
  "playTime": "09:50:00", "endTime": "10:00:00",
  "weekdays": [1,2,3,4,5], "disableDay": "2026-10-01",
  "seconds": 600, "loopTimes": 0,
  "interval": { "everySeconds": 300, "playSeconds": 30, "playTimes": 0 },
  "volume": 70, "priority": 20, "prePower": 10,
  "enabled": true, "sequential": true, "multicast": false, "localFirst": false,
  "led": { "text": "课间休息", "name": "课间音乐", "speed": 5, "mode": 0,
           "devices": [{ "terminalId": 1, "terminalName": "A101教室音箱", "deviceId": 1 }] }
}`,
				Returns: []Field{
					{Name: "id / name / note / folderId", Type: "混合", Desc: "编号、任务名称、备注（只读）、所属分组编号"},
					{Name: "media[]", Type: "数组", Desc: "媒体清单，**按播放顺序**。每项 {id, name}"},
					{Name: "terminals[].area", Type: "string", Desc: "这台终端的区域掩码。每一位对应一路输出，1 = 出声"},
					{Name: "terminals[].deleted", Type: "bool", Desc: "这台终端已经被删了但任务里还挂着。如实报出来 —— 悄悄跳过的话「为什么少一台」查不出原因"},
					{Name: "startDate / endDate", Type: "string", Desc: "生效日期区间"},
					{Name: "playTime / endTime", Type: "string", Desc: "每天几点开播 / 几点结束"},
					{Name: "weekdays", Type: "数组", Desc: "1=周一 … 7=周日。空数组 = 手动任务"},
					{Name: "disableDay", Type: "string", Desc: "当天停用的那一天。没有就是空串（库里的 0000-00-00 已经替你抹掉了）"},
					{Name: "seconds / loopTimes", Type: "int", Desc: "时长（秒）与循环次数，**只有一个非零** —— 库里共用一列，这里替你分开了"},
					{Name: "interval", Type: "对象", Desc: "间隔播放。**null 表示普通模式**（从头播到尾）"},
					{Name: "volume / priority / prePower", Type: "int", Desc: "音量、任务级别（小的优先）、预开电源秒数"},
					{Name: "enabled", Type: "bool", Desc: "启没启用。库里 projectstate 0 才是启用，这里已经翻成正常的布尔"},
					{Name: "sequential", Type: "bool", Desc: "true 顺序、false 随机。库里 israndomplay 取值是反的，这里已经翻正"},
					{Name: "multicast / localFirst", Type: "bool", Desc: "组播、本地优先播放"},
					{Name: "led", Type: "对象", Desc: "LED 字幕。**null 表示这条任务不上屏**"},
				},
				Notes: []string{
					"⚠ 详情里**没有** running（此刻在不在播）—— 底层详情不带执行状态，硬填一个 false 就是在撒谎。要知道在不在播，查任务列表的 running。",
					"返回的形状与新建任务的入参一致：读回来改两个值再 PUT 回去就行。",
				},
			},
			{
				ID: "tasks.update", Method: "PUT", Path: "/tasks/{ref}",
				Summary: "修改任务",
				Desc: "改一条已经存在的任务。新建时能填的每一项这里都能改：时长、音量、播放时刻、星期、媒体清单、终端、LED 字幕……" +
					"**只写要改的那几个字段**，没写的保持原样 —— 所以「把时长改成 15 分钟」就是一行 {\"seconds\": 900}，不用把整条任务重发一遍。",
				Right: "任务管理（taskpriv）",
				Params: []Param{
					{Name: "ref", In: "path", Type: "int/string", Required: true,
						Desc: "要改哪条任务：**任务编号**（推荐，唯一），也认任务名。编号从新建任务的返回值或「任务列表」里拿",
						// 预填的是「新建任务」示例返回的那个编号，库里并不存在 ——
						// 这一条会真的改动数据，预填一个真实编号等于给误点铺路。
						Example: "70230"},
				},
				Fields:   taskUpdateFields(),
				Body:     `{ "seconds": 900 }`,
				Examples: taskUpdateExamples(),
				Sample: `{
  "id": 70230, "mediaCount": 2, "terminalCount": 4,
  "name": "课间音乐", "folderId": 1,
  "playTime": "09:50:00", "endTime": "10:05:00",
  "seconds": 900, "loopTimes": 0,
  "volume": 70, "priority": 20, "enabled": true
}`,
				Returns: []Field{
					{Name: "id", Type: "int", Desc: "任务编号。就是你刚改的那条"},
					{Name: "mediaCount", Type: "int", Desc: "改完之后这条任务挂着几条媒体。没动 media 时它是原来的条数，不是 0"},
					{Name: "terminalCount", Type: "int", Desc: "改完之后挂着几台终端。⚠ **对一下这个数** —— 给了分区的话它是展开后的终端数，比你传的条目多是正常的"},
					{Name: "name / folderId", Type: "混合", Desc: "改完之后的任务名与所属分组编号"},
					{Name: "playTime", Type: "string", Desc: "改完之后每天几点开播"},
					{Name: "endTime", Type: "string", Desc: "⚠ **改完之后的结束时刻，最该对的一项**。传了 seconds 或 playTime 而没传 endTime 时，它是服务端替你重算的 —— 回执不给出来的话，你要等到广播比预期多响五分钟才会发现"},
					{Name: "seconds", Type: "int", Desc: "改完之后按秒播的时长。⚠ 与 loopTimes 只有一个非零；传了 loopTimes 之后这里会变成 0，那是正常的（换了播法）"},
					{Name: "loopTimes", Type: "int", Desc: "改完之后的循环遍数"},
					{Name: "volume / priority", Type: "int", Desc: "改完之后的音量与任务级别（数字小的优先）"},
					{Name: "enabled", Type: "bool", Desc: "改完之后启没启用"},
				},
				Notes: []string{
					"⚠ 清单类字段（media / terminals / zones）给了就是**整体替换**，不是追加。传 media: [124] 会把原来的清单换成只剩这一条；不传则保持原样。想在原有基础上加一条，先用「任务详情」读出现在有哪些，加上再整体传回来。",
					"⚠ terminals 与 zones 是**同一个位置**：这两个里只要有一个给了值，终端清单就整体重算。只传 zones 会把原来单独挂着的终端也一起换掉。",
					"⚠ 改了时长（seconds / loopTimes）**或**挪了开播时刻（playTime），结束时刻 endTime 都会跟着重算。这是必要的：只挪开播时刻不动结束时刻，一条 09:50→10:05 的任务传了 playTime: \"10:05\" 就变成 10:05 开始、10:05 结束 —— 一条零长度的任务。不想让它自动算就在同一次请求里自己给一个 endTime。两样都没动（比如只改音量）时 endTime 一动不动。",
					"按遍数播（loopTimes）算不出确定的秒数：那时挪开播时刻会把原来的播放窗口**整体平移**，长度不变。另外越过午夜一律截到 23:59:59 —— endTime 是个时刻，没有「第二天」。",
					"⚠ 不传 led 是**保持原样**；要取消字幕得显式传 led: { \"text\": \"\" }。",
					noteZoneID,
					"回执里带的是**改完之后实际存进去的值**，不是你传的原文。传 {\"seconds\": 900} 回来的 endTime 就是服务端算的那个 —— 这是确认「服务端替我改了什么」最快的办法。",
					"改之前先调一次「任务详情」（GET /tasks/{ref}）：它返回的形状和这里的入参一致，读出来改两个值再 PUT 回去最稳。",
				},
				Danger: true,
			},
			{
				ID: "tasks.action", Method: "POST", Path: "/tasks/actions/{action}",
				Summary: "启停任务",
				Desc:    "四个动作分成两组，中文里都能叫「停」：start/stop 是「现在播 / 现在停」，任务本身没变；enable/disable 是「启用 / 停用」，改的是排期状态。想让今天的铃别响是 stop，想让这条任务以后都别响是 disable。",
				Right:   "任务管理（taskpriv）",
				Params: []Param{
					{Name: "action", In: "path", Type: "枚举", Required: true,
						Desc: "start / stop / enable / disable", Example: "stop"},
				},
				Fields: []Field{
					{Name: "tasks", Type: "数组", Required: true,
						Desc: "要操作哪几条任务。每项写**任务编号**（推荐，唯一），也认任务名。一次可以给多条 —— 能做的会做掉，做不了的在 blocked 里逐条给原因"},
				},
				Body: `{ "tasks": [1008, 1010] }`,
				Sample: `{
  "succeeded": [{ "id": 1008, "name": "升旗仪式-国歌" }],
  "blocked":   [{ "id": 1010, "name": "课间轻音乐", "reason": "只能操作自己创建的任务" }]
}`,
				Returns: []Field{
					{Name: "succeeded[]", Type: "数组", Desc: "做成了的。每项 {id, name}"},
					{Name: "blocked[]", Type: "数组", Desc: "没做成的。每项 {id, name, reason}"},
					{Name: "blocked[].reason", Type: "string", Desc: "为什么没做成，中文，可直接透传给你们的运维界面"},
				},
				Notes: []string{
					"⚠ 回执是「部分成功」的语义：能做的做掉、做不了的逐条给原因。**要检查 blocked 是不是空的** —— 一次给二十条，因为其中一条不归你就把另外十九条也拒掉是不合理的。",
				},
				Danger: true,
			},
			{
				ID: "tasks.delete", Method: "DELETE", Path: "/tasks/{ref}",
				Summary: "删除任务",
				Desc:    "连带清掉这条任务的媒体清单与终端清单。也支持 DELETE /tasks 带 {\"tasks\":[...]} 批量删。",
				Right:   "任务管理（taskpriv）",
				Params: []Param{
					{Name: "ref", In: "path", Type: "int/string", Required: true,
						// 预填一个库里没有的编号。删除接口预填真实编号，
						// 迟早有人点了确认才反应过来。
						Desc: "**任务编号**（推荐，唯一），也认任务名", Example: "70230"},
				},
				Sample: `{ "succeeded": [{ "id": 70230, "name": "课间音乐" }], "blocked": [] }`,
				Returns: []Field{
					{Name: "succeeded[]", Type: "数组", Desc: "真的删掉了的。每项 {id, name}，名字是**删之前**取的"},
					{Name: "blocked[]", Type: "数组", Desc: "没删成的。每项 {id, name, reason}。⚠ 要检查它是不是空的"},
				},
				Danger: true,
			},
		},
	}
}

// taskFields 是任务的字段表 —— 新建和修改共用这一份。
//
// # 为什么不分成两份
//
// 新建能填的每一项，修改都能改。分开写两份，改了一边忘了另一边，
// 平台上就会出现「新建里有、修改里没有」的字段 —— 而它其实是能改的。
// 对接方照着那份缺了字段的说明，会以为「这一项建完就定死了」，
// 于是绕远路：删掉重建一条。那会丢掉任务编号，也会在删和建之间留一个
// 什么都不播的空窗。
//
// # 寻址一律**编号优先**
//
// media / terminals / zones / folder 都既认编号也认名字，但说明里一律
// 把编号写在前面 —— 因为 media.name 和 terminal.terminalname 在库里
// **没有唯一索引**（核过 SHOW INDEX），是真的可以重名的。
// 界面上也只有分区名、任务分组名、媒体目录名在新建时挡了重名。
// 名字重了接口只能报错要求改用编号，而这个错往往在上线之后才出现。
func taskFields() []Field {
	return []Field{
		{Name: "name", Type: "string", Required: true, Desc: "任务名称，最多 45 字"},
		{Name: "folder", Type: "int/string", Desc: "所属分组：**分组编号**，也认分组名（分组名建的时候挡了重名，用名字安全）。不给落到你看得见的第一个分组"},
		{Name: "media", Type: "数组", Desc: "媒体清单，**按数组顺序播**。每项写**媒体编号**（推荐，唯一），也认媒体名 —— ⚠ 但媒体名没有唯一约束，可以重名，重了会报错要你改用编号。编号在「媒体列表」里查"},
		{Name: "terminals", Type: "数组", Desc: "播到哪些终端。每项写**终端编号**（推荐，唯一），也认终端名（⚠ 同样可以重名）；要指定区域掩码就写成 { \"terminal\": 1, \"area\": \"11110000\" }。编号在「终端状态」里查。**不用给分区号**，服务端自己填"},
		{Name: "zones", Type: "数组", Desc: "播到哪些分区，会展开成终端。每项写**分区编号**或分区名（分区名不会重复，用名字安全）。与 terminals 取并集去重"},
		{Name: "playTime", Type: "string", Required: true, Desc: "播放时间（每天几点开播）。09:50 或 09:50:00"},
		{Name: "endTime", Type: "string", Desc: "结束时间。不给就按 playTime + 时长自动算"},
		{Name: "startDate", Type: "string", Desc: "开始日期。不给默认今天"},
		{Name: "endDate", Type: "string", Desc: "结束日期。不给默认一年后"},
		{Name: "weekdays", Type: "数组", Desc: "执行模式：1=周一 … 7=周日。**留空 = 手动**，不是「每天」；要每天就写 [1,2,3,4,5,6,7]"},
		{Name: "disableDay", Type: "string", Desc: "当天停用：这一天不执行，YYYY-MM-DD。留空 = 没有例外日"},
		{Name: "seconds", Type: "int", Desc: "时长（秒），最多 86400。与 loopTimes 二选一；都不给按媒体自身总时长算"},
		{Name: "loopTimes", Type: "int", Desc: "循环次数：把整个清单播几遍。与 seconds 二选一。⚠ 填 0 是**无限循环**，不是「不循环」"},
		{Name: "interval", Type: "对象", Desc: "间隔播放（界面「播放模式 → 间隔时间」）。不给 = 普通模式，从头播到尾"},
		{Name: "interval.everySeconds", Type: "int", Desc: "间隔长度：每隔多少秒响一次"},
		{Name: "interval.playSeconds", Type: "int", Desc: "间隔时长：每次响多少秒。与 playTimes 二选一"},
		{Name: "interval.playTimes", Type: "int", Desc: "间隔次数：每次把清单播几遍，最多 99。与 playSeconds 二选一"},
		{Name: "volume", Type: "int", Desc: "任务音量 0~100，默认 80"},
		{Name: "priority", Type: "int", Desc: "任务级别 10~109。⚠ **数字小的优先级高**；不给取你能用的最低一档"},
		{Name: "prePower", Type: "int", Desc: "预开电源：提前多少秒开功放，0~3600"},
		{Name: "sequential", Type: "bool", Desc: "true 顺序播（默认）、false 随机播"},
		{Name: "multicast", Type: "bool", Desc: "发送模式：true 组播、false 单播（默认）。组播要求交换机支持，没把握别动"},
		{Name: "localFirst", Type: "bool", Desc: "本地优先播放：终端上已下发过这个媒体就放本地那份，断网也能响。默认关"},
		{Name: "enabled", Type: "bool", Desc: "建完是启用还是停用，默认启用"},
		{Name: "led", Type: "对象", Desc: "LED 字幕。不给或正文为空 = 不上屏。⚠ 改任务时传 null 会**删掉**原有字幕"},
		{Name: "led.text", Type: "string", Desc: "字幕正文，最多 1024 字"},
		{Name: "led.name", Type: "string", Desc: "字幕子任务名。留空跟主任务同名"},
		{Name: "led.speed", Type: "int", Desc: "Led 速度 0~10"},
		{Name: "led.mode", Type: "int", Desc: "Led 显示模式 0~10"},
		{Name: "led.devices", Type: "数组", Desc: "上到哪几块屏。每项 { terminal, deviceId }；terminal 同上，写**终端编号**最稳；deviceId 在「led播放 → LED 屏设备」里看"},
	}
}

// taskUpdateSemantics 是**只在「修改任务」下才成立**的那些话。
//
// 新建时「不传就是默认值」，修改时「不传就是保持原样」—— 同一个字段，
// 两种语境下要说的完全不是一回事。改任务的人最想知道的恰恰是这一句：
// 我不写它，它会不会被清掉？
var taskUpdateSemantics = map[string]string{
	"name":       "改任务名，最多 45 字。不传就不改名",
	"seconds":    "改成按时长播，单位秒，最多 86400。**改任务时长就是改这一个字段**：{\"seconds\": 900} = 播 15 分钟。⚠ 结束时刻 endTime 会跟着重算；不想让它动就同时给一个 endTime。与 loopTimes 只能给一个",
	"loopTimes":  "改成按遍数播：把整个清单循环几遍。给了它就把原来的按秒时长换掉。⚠ 0 是**无限循环**，不是「不循环」。与 seconds 只能给一个。⚠ 开着间隔播放的任务只能按秒，用 seconds",
	"media":      "改播放清单，**按数组顺序播**。⚠ 给了就是**整体替换**，不是追加。每项写**媒体编号**（推荐，唯一），也认媒体名 —— 但媒体名可以重名。不传保持原样",
	"terminals":  "改播到哪些终端。⚠ **整体替换**。每项写**终端编号**（推荐，唯一）或终端名；要改区域掩码写成 { \"terminal\": 1, \"area\": \"11110000\" }。terminals 与 zones 有一个给了值，终端清单就整体重算",
	"zones":      "改播到哪些分区，会展开成终端。每项写**分区编号**或分区名。⚠ 与 terminals 是同一个位置：只传 zones 也会把原来单独挂的终端一起换掉",
	"weekdays":   "改哪几天播：1=周一 … 7=周日。⚠ 传空数组 [] 会把它变成**手动任务**，以后永远不会自动响。不传才是保持原样",
	"enabled":    "启用 / 停用这条任务。false = 以后都不响（只想让今天这一次别响，用「启停任务」的 stop）",
	"led":        "改 LED 字幕。⚠ **不传 = 保持原样**；要取消字幕得显式传 { \"text\": \"\" }（或 null）。传了就是整体替换整段字幕设置",
	"interval":   "改间隔播放。传了就整体替换；不传保持原样",
	"folder":     "换到别的任务分组：**分组编号**，也认分组名。不传保持原样",
	"disableDay": "改「当天停用」的那一天，YYYY-MM-DD。传空串清掉它",
	"endTime":    "结束时间。不给且这次改了时长的话，会按「播放时刻 + 新时长」自动重算",
}

// taskUpdateFields 把新建的字段表翻成修改用的：**全部可选**，
// 并把语义不同的那些换成修改语境下的说法。
func taskUpdateFields() []Field {
	src := taskFields()
	out := make([]Field, 0, len(src))
	for _, f := range src {
		f.Required = false // 修改任务没有必填字段：只写要改的
		if d, ok := taskUpdateSemantics[f.Name]; ok {
			f.Desc = d
		}
		out = append(out, f)
	}
	return out
}

// taskUpdateExamples 是「我要干这件事」到请求体的对照。
//
// ⚠ 每一段都是**最小**请求体，照抄就能用。不要在这里放全字段示例 ——
// 对接方照抄一段写满 30 个字段的 JSON，等于把每一项都覆盖一遍，
// 而他只是想改个时长。
func taskUpdateExamples() []Example {
	return []Example{
		{
			Title: "改时长：改成播 15 分钟",
			Desc:  "最常问的一个。时长就是 seconds 这一个字段，单位是秒。结束时刻会跟着重算成 播放时刻 + 900 秒 —— 想自己定就在同一次请求里加一个 \"endTime\"。",
			Body:  `{ "seconds": 900 }`,
		},
		{
			Title: "改时长：改成把清单播 3 遍",
			Desc:  "按遍数播而不是按秒。给了 loopTimes 就把原来的按秒时长换掉，两者只能有一个。⚠ 写 0 是无限循环。⚠ 这条任务如果开着**间隔播放**，只能按秒（用 seconds）—— 间隔播放要拿总时长来排，接口会明确报错告诉你。",
			Body:  `{ "loopTimes": 3 }`,
		},
		{
			Title: "改音量",
			Desc:  "0~100。其它一律不动。",
			Body:  `{ "volume": 55 }`,
		},
		{
			Title: "改播放时刻和星期",
			Desc:  "改成每周一到周五 10:05 播。⚠ weekdays 传空数组会变成手动任务，永远不再自动响。",
			Body:  `{ "playTime": "10:05", "weekdays": [1, 2, 3, 4, 5] }`,
		},
		{
			Title: "换终端",
			Desc:  "⚠ 整体替换：原来挂的终端全部换成这三台，不是再加三台。数字是终端编号，在「终端状态」里查。要保留原有的，先用「任务详情」读出来再连同新的一起传。",
			Body:  `{ "terminals": [1, 2, 8] }`,
		},
		{
			Title: "给某台终端指定区域",
			Desc:  "掩码每一位对应一路输出，1 = 出声。这里是「只走前四路」。同样是整体替换终端清单。",
			Body:  `{ "terminals": [{ "terminal": 1, "area": "11110000" }] }`,
		},
		{
			Title: "换播放的媒体",
			Desc:  "⚠ 整体替换播放清单，按数组顺序播。数字是媒体编号，在「媒体列表」里查。",
			Body:  `{ "media": [124, 125] }`,
		},
		{
			Title: "改成间隔播放",
			Desc:  "每隔 5 分钟响 30 秒，直到任务时长走完。不想要间隔了就把 interval 整个去掉重新传其它字段 —— 传 null 即可。",
			Body:  `{ "interval": { "everySeconds": 300, "playSeconds": 30 } }`,
		},
		{
			Title: "停用这条任务",
			Desc:  "以后都不响，任务还在。只想让今天这一次别响，用「启停任务」的 stop。",
			Body:  `{ "enabled": false }`,
		},
		{
			Title: "取消 LED 字幕",
			Desc:  "⚠ 正文留空才是取消。**不传 led 是保持原样** —— 只改音量不会把字幕带掉。",
			Body:  `{ "led": { "text": "" } }`,
		},
	}
}

func groupPlay() Group {
	return Group{
		Name: "立即播放",
		Desc: "「现在就让操场出声」，不排期。会建一条临时任务并启动它，停止时连任务一起删掉。",
		Endpoints: []Endpoint{
			{
				ID: "play.start", Method: "POST", Path: "/play",
				Summary: "立即播放",
				Desc:    "告警联动最常用的一个：门禁/消防那边一触发，直接让指定区域出声。",
				Right:   "任务管理（taskpriv）",
				Fields: []Field{
					{Name: "media", Type: "int/string", Required: true, Desc: "播什么，一个媒体。写**媒体编号**（推荐，唯一），也认媒体名 —— ⚠ 媒体名可以重名，重了会报错要你改用编号。编号在「媒体列表」里查"},
					{Name: "terminals", Type: "数组", Desc: "播到哪些终端。每项写**终端编号**（推荐，唯一）或终端名。编号在「终端状态」里查"},
					{Name: "zones", Type: "数组", Desc: "播到哪些分区，会展开成终端。每项写**分区编号**或分区名（分区名不会重复）。与 terminals 至少给一个"},
					{Name: "volume", Type: "int", Desc: "0~100，默认 80"},
					{Name: "seconds", Type: "int", Desc: "最多播多久。不给按媒体自身时长，还不行兜底 300 秒"},
				},
				Body: `{
  "media": 138,
  "zones": [3],
  "volume": 90
}`,
				Sample: `{
  "playId": 1, "taskId": 70231, "media": "中华人民共和国国歌.mp3",
  "terminals": [{ "id": 8, "name": "操场号角01" }],
  "volume": 90, "seconds": 46, "state": "playing",
  "startTime": "2026-09-08 08:00:52", "endTime": ""
}`,
				Returns: []Field{
					{Name: "playId", Type: "int", Desc: "这次播放的句柄。⚠ **记下来** —— 停止时要用它"},
					{Name: "taskId", Type: "int", Desc: "为这次播放临时建的任务编号。停止时会连它一起删掉，不用你管"},
					{Name: "media", Type: "string", Desc: "正在播的媒体名"},
					{Name: "terminals[]", Type: "数组", Desc: "播到了哪些终端。每项 {id, name}。分区在这里已经展开成终端了"},
					{Name: "volume", Type: "int", Desc: "音量 0~100"},
					{Name: "seconds", Type: "int", Desc: "最多播多久，秒。没指定时取的是媒体自身时长"},
					{Name: "state", Type: "string", Desc: "playing = 还在播；stopped = 已停止"},
					{Name: "startTime", Type: "string", Desc: "开始时间 YYYY-MM-DD HH:MM:SS"},
					{Name: "endTime", Type: "string", Desc: "停止时间。还在播时是空串"},
				},
				Notes: []string{
					"示例里的 138 是媒体编号（在「媒体列表」查）、3 是分区编号（在「全部功能接口 → 终端分区」查）。写名字也认，但媒体名可以重名 —— 告警联动这种没人盯着的场景，用编号才不会某天突然报「找到多个同名媒体」。",
					"把返回的 playId 记下来，停止时要用。",
					noteZoneID,
					"⚠ 以**最高优先级**下发，会压住正在播的排期任务。这是有意的 —— 它是你此刻主动要求的。上课时间请谨慎。",
					"⚠ 这个真的会让喇叭响。",
				},
				Danger: true,
			},
			{
				ID: "play.stop", Method: "POST", Path: "/play/{playId}/stop",
				Summary: "停止立即播放",
				Desc:    "停掉并把那条临时任务删掉。幂等 —— 已经停了再调一次不会报错。",
				Right:   "任务管理（taskpriv）",
				Params: []Param{
					{Name: "playId", In: "path", Type: "int", Required: true,
						Desc: "立即播放返回的 playId。也能在「还在播的立即播放」里查到", Example: "1"},
				},
				Sample: `{ "playId": 1, "state": "stopped", "endTime": "2026-09-08 08:01:24" }`,
				Returns: []Field{
					{Name: "playId", Type: "int", Desc: "刚停掉的那次播放"},
					{Name: "state", Type: "string", Desc: "stopped。幂等 —— 本来就停了的再调一次也回这个，不报错"},
					{Name: "endTime", Type: "string", Desc: "停止时间"},
				},
				Danger: true,
			},
		},
	}
}

func groupSchedule() Group {
	return Group{
		Name: "作息方案",
		Desc: "上下课打铃。方案不是一条记录，而是一批打铃任务共用的一个名字 —— 所以它没有编号，寻址只能用名字。",
		Endpoints: []Endpoint{
			{
				ID: "schedules.create", Method: "POST", Path: "/schedules",
				Summary: "新建作息方案",
				Desc:    "一次调用建出整个方案：方案名、打到哪些终端、以及一行一行的「几点播什么播多久」。",
				Right:   "作息方案（bellpriv）",
				Fields: []Field{
					{Name: "name", Type: "string", Required: true, Desc: "方案名"},
					{Name: "terminals", Type: "数组", Desc: "打到哪些终端。每项写**终端编号**（推荐，唯一）或终端名"},
					{Name: "zones", Type: "数组", Desc: "打到哪些分区，会展开成终端。每项写**分区编号**或分区名。与 terminals 至少给一个"},
					{Name: "items", Type: "数组", Required: true, Desc: "打铃条目，至少一条。每条：playTime 必填、media 必填、name/seconds/loopTimes 可选"},
					{Name: "items[].media", Type: "数组", Desc: "这一条播什么。每项写**媒体编号**（推荐，唯一）或媒体名 —— ⚠ 媒体名可以重名"},
					{Name: "weekdays", Type: "数组", Desc: "留空 = 周一到周五（注意与新建任务不同）"},
					{Name: "startDate", Type: "string", Desc: "生效起始日，默认今天"},
					{Name: "endDate", Type: "string", Desc: "生效结束日，默认一年后"},
					{Name: "volume", Type: "int", Desc: "方案级音量，全部条目共用"},
					{Name: "priority", Type: "int", Desc: "方案级优先级"},
					{Name: "prePower", Type: "int", Desc: "提前多少秒开功放电源"},
				},
				Body: `{
  "name": "夏季作息",
  "zones": [1],
  "weekdays": [1, 2, 3, 4, 5],
  "volume": 75,
  "items": [
    { "name": "早读预备",   "playTime": "07:20", "media": [125], "seconds": 30 },
    { "name": "第一节下课", "playTime": "09:50", "media": [126] }
  ]
}`,
				Sample: `{ "name": "夏季作息", "itemCount": 2, "taskIds": [70232, 70233], "warnings": [] }`,
				Returns: []Field{
					{Name: "name", Type: "string", Desc: "建出来的方案名。之后启停、删除都用它寻址"},
					{Name: "itemCount", Type: "int", Desc: "实际建出几条打铃"},
					{Name: "taskIds", Type: "数组", Desc: "每条打铃在 task 表里的编号，按 items 的顺序"},
					{Name: "warnings", Type: "数组", Desc: "⚠ **要看一眼**。「建成了，但有件事你该知道」，比如同一时刻排了两条铃。不拦截，但空数组才是完全干净"},
				},
				Notes: []string{
					"示例里的 1 是分区编号（教学楼）、125 / 126 是媒体编号。方案本身是个例外：它**没有编号**，name 就是它的身份，之后启停、删除、查详情都只能用这个名字。",
					"⚠ weekdays 留空时这里默认**周一到周五**，而新建任务那边默认「手动」。不是笔误：作息方案天然是上课日打铃，建一个永不触发的方案没有意义；而文件广播任务建来手动触发是常见用法。两边都别漏填。",
					"warnings 是「建成了，但有件事你该知道」，比如同一时刻排了两条铃。不拦截，但要看。",
					noteZoneID,
				},
				Danger: true,
			},
			{
				ID: "schedules.state", Method: "PUT", Path: "/schedules/{name}/state",
				Summary: "启用 / 停用方案",
				Desc:    "把方案下面所有打铃一起启停。放假停课时最常用的一个。",
				Right:   "作息方案（bellpriv）",
				Params: []Param{
					{Name: "name", In: "path", Type: "string", Required: true,
						Desc: "方案名。⚠ 方案**没有编号**，名字就是它的身份，这里只能写名字", Example: ""},
				},
				Body:   `{ "enabled": false }`,
				Sample: `{ "name": "夏季作息", "affectedTasks": 2 }`,
				Returns: []Field{
					{Name: "name", Type: "string", Desc: "被操作的方案名（服务端核实过的那个，不是你传的原文）"},
					{Name: "affectedTasks", Type: "int", Desc: "这一下动了几条打铃。方案里有几条就是几条 —— 与预期对不上说明方案内容和你以为的不一样"},
				},
				Danger: true,
			},
			{
				ID: "schedules.delete", Method: "DELETE", Path: "/schedules/{name}",
				Summary: "删除方案",
				Desc:    "连同它下面的**全部**打铃条目一起删掉。没有二次确认参数 —— 挡误删的是权限位和「名字必须精确对上」，不是多传一个 confirmed。",
				Right:   "作息方案（bellpriv）",
				Params: []Param{
					{Name: "name", In: "path", Type: "string", Required: true,
						Desc: "方案名。⚠ 方案**没有编号**，名字就是它的身份，这里只能写名字", Example: ""},
				},
				Sample: `{ "name": "夏季作息", "affectedTasks": 2 }`,
				Returns: []Field{
					{Name: "name", Type: "string", Desc: "被操作的方案名（服务端核实过的那个，不是你传的原文）"},
					{Name: "affectedTasks", Type: "int", Desc: "这一下动了几条打铃。方案里有几条就是几条 —— 与预期对不上说明方案内容和你以为的不一样"},
				},
				Danger: true,
			},
		},
	}
}

// OpenAPIJSON 把目录渲染成标准 OpenAPI 3.0 文档。
//
// 给集成方导进 Postman / Apifox / 代码生成器用。我们自己那个界面是给人看的
// （中文、带「为什么」、能直接试），这一份是给工具看的。
// 两份同一个来源，不会分叉。
func OpenAPIJSON(baseURL string) ([]byte, error) {
	spec := Catalog()

	paths := map[string]map[string]any{}
	for _, g := range spec.Groups {
		for _, ep := range g.Endpoints {
			full := spec.Prefix + ep.Path
			if paths[full] == nil {
				paths[full] = map[string]any{}
			}

			params := []map[string]any{}
			for _, p := range ep.Params {
				params = append(params, map[string]any{
					"name": p.Name, "in": p.In, "required": p.In == "path" || p.Required,
					"description": p.Desc,
					"schema":      map[string]any{"type": openAPIType(p.Type)},
				})
			}

			op := map[string]any{
				"operationId": ep.ID,
				"summary":     ep.Summary,
				"description": descWithNotes(ep),
				"tags":        []string{g.Name},
				"parameters":  params,
				"responses": map[string]any{
					"200": map[string]any{
						"description": "成功。业务码在 body 的 code 里，200 表示成功",
						"content": map[string]any{
							"application/json": map[string]any{
								"schema": map[string]any{
									"type": "object",
									"properties": map[string]any{
										"code": map[string]any{"type": "integer"},
										"msg":  map[string]any{"type": "string"},
										"data": map[string]any{},
									},
								},
								"example": envelopeExample(ep.Sample),
							},
						},
					},
				},
			}
			if ep.Body != "" {
				media := map[string]any{
					"schema":  map[string]any{"type": "object"},
					"example": rawJSON(ep.Body),
				}
				// 分场景示例也带进标准文档里 —— Postman / Apifox 会把它们
				// 渲染成一个下拉，跟我们自己界面上那排场景是同一份东西。
				// 只在自己界面上有、导出去就没了的话，两边会各写各的。
				if len(ep.Examples) > 0 {
					exs := map[string]any{}
					for _, e := range ep.Examples {
						exs[e.Title] = map[string]any{
							"summary": e.Title, "description": e.Desc,
							"value": rawJSON(e.Body),
						}
					}
					media["examples"] = exs
				}
				op["requestBody"] = map[string]any{
					"required": true,
					"content":  map[string]any{"application/json": media},
				}
			}
			paths[full][strings.ToLower(ep.Method)] = op
		}
	}

	tags := []map[string]any{}
	for _, g := range spec.Groups {
		tags = append(tags, map[string]any{"name": g.Name, "description": g.Desc})
	}

	doc := map[string]any{
		"openapi": "3.0.3",
		"info": map[string]any{
			"title":       spec.Title,
			"version":     spec.Version,
			"description": openAPIIntro,
		},
		"servers": []map[string]any{{"url": baseURL}},
		"tags":    tags,
		"paths":   paths,
		"components": map[string]any{
			"securitySchemes": map[string]any{
				"ApiKeyAuth": map[string]any{
					"type": "apiKey", "in": "header", "name": HeaderAPIKey,
					"description": "在广播系统界面「用户管理 → 开发者密钥」里发放。形如 hb_前缀_密钥正文。",
				},
			},
		},
		"security": []map[string]any{{"ApiKeyAuth": []string{}}},
	}
	return json.MarshalIndent(doc, "", "  ")
}

// HeaderAPIKey 是开发者接口的凭据头。
//
// ⚠ **这里是唯一定义**，中间件从这儿取。写成两份的话，规格里写错了头名，
// 集成方会照着错的写，而他收到的错误是「缺少 X-API-Key」——
// 跟规格对不上，排查全靠猜。
//
// 不复用界面的 x-access-token：两种凭据的生命周期、吊销方式、
// 泄露后的处置都不一样，混在一个头里，日志和排查时分不清是谁在调。
const HeaderAPIKey = "X-API-Key"

const openAPIIntro = `广播系统的对外接口。

认证：请求头 ` + "`" + HeaderAPIKey + "`" + `，值是在界面「用户管理 → 开发者密钥」发放的密钥。
密钥的权限完全等于它归属账号的权限。

寻址：**能用编号就用编号** —— 编号是唯一的，名字不一定。
媒体名和终端名在库里没有唯一约束，是真的可以重名的；分区名、任务分组名、媒体目录名在新建时挡了重名，用名字安全。
媒体、终端、分区、任务、分组都同时认编号和名字，编号从对应的查询接口里拿。
作息方案是唯一的例外：它根本没有编号，只能用名字。
名字只精确匹配，绝不猜 —— 对不上会报错并提示相近的名字。

返回：HTTP 状态码恒为 200，看 body 里的 code。
200 成功 / 40001 请求填错了 / 401 密钥无效 / 40301 权限不够 / 40401 找不到对象 / 50001 服务器出错。`

func openAPIType(t string) string {
	switch {
	case strings.Contains(t, "int"):
		return "integer"
	case strings.Contains(t, "bool"):
		return "boolean"
	case strings.Contains(t, "数组"):
		return "array"
	default:
		return "string"
	}
}

func descWithNotes(ep Endpoint) string {
	var b strings.Builder
	b.WriteString(ep.Desc)
	if ep.Right != "" {
		b.WriteString("\n\n**需要权限**：" + ep.Right)
	}
	if len(ep.Fields) > 0 {
		b.WriteString("\n\n**请求体字段**\n")
		for _, f := range ep.Fields {
			req := ""
			if f.Required {
				req = "（必填）"
			}
			b.WriteString("\n- `" + f.Name + "` " + f.Type + req + " — " + f.Desc)
		}
	}
	for _, n := range ep.Notes {
		b.WriteString("\n\n" + n)
	}
	return b.String()
}

// rawJSON 把示例字符串解析回结构，让它在 OpenAPI 文档里是**真的 JSON**
// 而不是一坨字符串 —— 后者导进 Postman 之后没法直接发。
func rawJSON(s string) any {
	var v any
	if err := json.Unmarshal([]byte(s), &v); err != nil {
		return s
	}
	return v
}

func envelopeExample(sample string) any {
	return map[string]any{"code": 200, "msg": "ok", "data": rawJSON(sample)}
}
