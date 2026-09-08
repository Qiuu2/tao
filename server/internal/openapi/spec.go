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
	Sample string  `json:"sample"`
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

// Spec 是整份目录。
type Spec struct {
	Title   string  `json:"title"`
	Version string  `json:"version"`
	Prefix  string  `json:"prefix"`
	Groups  []Group `json:"groups"`
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
	}
}

// 两类接口的分区标记。
const (
	// SectionCurated 是 /openapi/v1 那一组：名字寻址、参数是人话、
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
					{Name: "zone", In: "query", Type: "string", Desc: "只看某个分区的。名字或编号都行", Example: ""},
					{Name: "keyword", In: "query", Type: "string", Desc: "按终端名搜", Example: ""},
					{Name: "pageNum", In: "query", Type: "int", Desc: "页码，从 1 开始", Example: "1"},
					{Name: "pageSize", In: "query", Type: "int", Desc: "每页几条，默认 20、最大 200", Example: "20"},
				},
				Sample: `{
  "list": [{
    "id": 2, "name": "A102教室音箱", "type": "一体化音箱", "zone": "教学楼",
    "ip": "192.168.2.12",
    "online": true, "playing": false, "stateText": "在线空闲",
    "volume": 75, "powerOn": true, "powerStateText": "已开机"
  }],
  "total": 12, "pageNum": 1, "pageSize": 20
}`,
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
				Notes:   []string{"方案没有编号，名字就是它的身份。"},
			},
			{
				ID: "schedules.get", Method: "GET", Path: "/schedules/{name}",
				Summary: "作息方案详情",
				Desc:    "一个方案里几点打什么铃、打到哪些终端。",
				Right:   "有效密钥即可",
				Params: []Param{
					{Name: "name", In: "path", Type: "string", Required: true,
						Desc: "方案名。界面会自动做 URL 编码", Example: "春季作息"},
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
			},
		},
	}
}

func groupTask() Group {
	return Group{
		Name: "任务",
		Desc: "文件广播任务的增删改启停。新建是一次调用带齐媒体、终端、任务信息 —— 与界面「新建任务」那一屏对应。",
		Endpoints: []Endpoint{
			{
				ID: "tasks.create", Method: "POST", Path: "/tasks",
				Summary: "新建任务",
				Desc:    "一次调用把播放清单、终端/分区、时间与音量一起收下。不用先建任务再挂媒体再挂终端 —— 那样中间任何一步失败都会留下一条残缺的任务，它在界面上看着正常，到点却什么都不播。",
				Right:   "任务管理（taskpriv）",
				Fields: []Field{
					{Name: "name", Type: "string", Required: true, Desc: "任务名，最多 45 字"},
					{Name: "media", Type: "数组", Desc: "播放清单，按数组顺序播。写名字或编号都行"},
					{Name: "terminals", Type: "数组", Desc: "播到哪些终端"},
					{Name: "zones", Type: "数组", Desc: "播到哪些分区。与 terminals 取并集去重"},
					{Name: "playTime", Type: "string", Required: true, Desc: "每天几点播。09:50 或 09:50:00"},
					{Name: "endTime", Type: "string", Desc: "不给就按 playTime + 时长自动算"},
					{Name: "weekdays", Type: "数组", Desc: "1=周一 … 7=周日。留空 = 手动任务，不是「每天」"},
					{Name: "startDate", Type: "string", Desc: "生效起始日。不给默认今天"},
					{Name: "endDate", Type: "string", Desc: "生效结束日。不给默认一年后"},
					{Name: "seconds", Type: "int", Desc: "播多久（秒）。与 loopTimes 二选一"},
					{Name: "loopTimes", Type: "int", Desc: "把清单循环几遍。与 seconds 二选一"},
					{Name: "volume", Type: "int", Desc: "0~100，默认 80"},
					{Name: "priority", Type: "int", Desc: "10~109，数字小的优先级高。不给取你能用的最低一档"},
					{Name: "prePower", Type: "int", Desc: "提前多少秒开功放电源"},
					{Name: "enabled", Type: "bool", Desc: "建完是启用还是停用，默认启用"},
					{Name: "sequential", Type: "bool", Desc: "true 顺序播（默认）、false 随机播"},
					{Name: "folder", Type: "string/int", Desc: "任务分组。不给落到你看得见的第一个分组"},
				},
				Body: `{
  "name": "课间音乐",
  "media": ["大课间.mp3"],
  "zones": ["教学楼"],
  "playTime": "09:50",
  "weekdays": [1, 2, 3, 4, 5],
  "seconds": 600,
  "volume": 70
}`,
				Sample: `{ "id": 70230, "mediaCount": 1, "terminalCount": 4 }`,
				Notes: []string{
					"⚠ weekdays 留空 = 手动任务，永远不会自动响。想每天响要写 [1,2,3,4,5,6,7]。默认成「每天」太危险 —— 少写一个字段就变成每天全校广播。",
					"⚠ 分区是在写入那一刻展开成终端的。建完任务再往分区里加终端，这条任务不会自动带上它 —— 加了终端要重新调一次「修改任务」。",
					"不给 priority 时取最低一档，会被别的广播压住。要它优先就自己填一个小数字。",
				},
				Danger: true,
			},
			{
				ID: "tasks.update", Method: "PUT", Path: "/tasks/{ref}",
				Summary: "修改任务",
				Desc:    "只写要改的字段，没给的保持原样。",
				Right:   "任务管理（taskpriv）",
				Params: []Param{
					{Name: "ref", In: "path", Type: "string", Required: true,
						Desc: "任务名或编号", Example: "课间音乐"},
				},
				Body:   `{ "volume": 55 }`,
				Sample: `{ "id": 70230, "mediaCount": 1, "terminalCount": 4 }`,
				Notes: []string{
					"⚠ 清单类字段（media / terminals / zones）给了就是整体替换，不是追加。传 media: [\"A.mp3\"] 会把原来的清单换成只有 A.mp3。不传则保持原样。",
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
				Body: `{ "tasks": ["课间音乐"] }`,
				Sample: `{
  "succeeded": [{ "id": 1008, "name": "升旗仪式-国歌" }],
  "blocked":   [{ "id": 1010, "name": "课间轻音乐", "reason": "只能操作自己创建的任务" }]
}`,
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
					{Name: "ref", In: "path", Type: "string", Required: true,
						Desc: "任务名或编号", Example: ""},
				},
				Sample: `{ "succeeded": [{ "id": 70230, "name": "课间音乐" }], "blocked": [] }`,
				Danger: true,
			},
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
					{Name: "media", Type: "string/int", Required: true, Desc: "播什么。一个媒体"},
					{Name: "terminals", Type: "数组", Desc: "播到哪些终端"},
					{Name: "zones", Type: "数组", Desc: "播到哪些分区。与 terminals 至少给一个"},
					{Name: "volume", Type: "int", Desc: "0~100，默认 80"},
					{Name: "seconds", Type: "int", Desc: "最多播多久。不给按媒体自身时长，还不行兜底 300 秒"},
				},
				Body: `{
  "media": "中华人民共和国国歌.mp3",
  "zones": ["室外操场"],
  "volume": 90
}`,
				Sample: `{
  "playId": 1, "taskId": 70231, "media": "中华人民共和国国歌.mp3",
  "terminals": [{ "id": 8, "name": "操场号角01" }],
  "volume": 90, "seconds": 46, "state": "playing",
  "startTime": "2026-09-08 08:00:52", "endTime": ""
}`,
				Notes: []string{
					"把返回的 playId 记下来，停止时要用。",
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
						Desc: "立即播放返回的 playId", Example: ""},
				},
				Sample: `{ "playId": 1, "state": "stopped", "endTime": "2026-09-08 08:01:24" }`,
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
					{Name: "terminals", Type: "数组", Desc: "打到哪些终端"},
					{Name: "zones", Type: "数组", Desc: "打到哪些分区。与 terminals 至少给一个"},
					{Name: "items", Type: "数组", Required: true, Desc: "打铃条目，至少一条。每条：playTime 必填、media 必填、name/seconds/loopTimes 可选"},
					{Name: "weekdays", Type: "数组", Desc: "留空 = 周一到周五（注意与新建任务不同）"},
					{Name: "startDate", Type: "string", Desc: "生效起始日，默认今天"},
					{Name: "endDate", Type: "string", Desc: "生效结束日，默认一年后"},
					{Name: "volume", Type: "int", Desc: "方案级音量，全部条目共用"},
					{Name: "priority", Type: "int", Desc: "方案级优先级"},
					{Name: "prePower", Type: "int", Desc: "提前多少秒开功放电源"},
				},
				Body: `{
  "name": "夏季作息",
  "zones": ["教学楼"],
  "weekdays": [1, 2, 3, 4, 5],
  "volume": 75,
  "items": [
    { "name": "早读预备",   "playTime": "07:20", "media": ["10.起床号.mp3"], "seconds": 30 },
    { "name": "第一节下课", "playTime": "09:50", "media": ["04.爱的纪念（下课）.mp3"] }
  ]
}`,
				Sample: `{ "name": "夏季作息", "itemCount": 2, "taskIds": [70232, 70233], "warnings": [] }`,
				Notes: []string{
					"⚠ weekdays 留空时这里默认**周一到周五**，而新建任务那边默认「手动」。不是笔误：作息方案天然是上课日打铃，建一个永不触发的方案没有意义；而文件广播任务建来手动触发是常见用法。两边都别漏填。",
					"warnings 是「建成了，但有件事你该知道」，比如同一时刻排了两条铃。不拦截，但要看。",
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
						Desc: "方案名", Example: ""},
				},
				Body:   `{ "enabled": false }`,
				Sample: `{ "name": "夏季作息", "affectedTasks": 2 }`,
				Danger: true,
			},
			{
				ID: "schedules.delete", Method: "DELETE", Path: "/schedules/{name}",
				Summary: "删除方案",
				Desc:    "连同它下面的**全部**打铃条目一起删掉。没有二次确认参数 —— 挡误删的是权限位和「名字必须精确对上」，不是多传一个 confirmed。",
				Right:   "作息方案（bellpriv）",
				Params: []Param{
					{Name: "name", In: "path", Type: "string", Required: true,
						Desc: "方案名", Example: ""},
				},
				Sample: `{ "name": "夏季作息", "affectedTasks": 2 }`,
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
				op["requestBody"] = map[string]any{
					"required": true,
					"content": map[string]any{
						"application/json": map[string]any{
							"schema":  map[string]any{"type": "object"},
							"example": rawJSON(ep.Body),
						},
					},
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

寻址：媒体、终端、分区、任务都可以**直接写名字**，不必先查编号；作息方案只能写名字（它没有编号）。
只精确匹配，绝不猜 —— 对不上会报错并提示相近的名字。

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
