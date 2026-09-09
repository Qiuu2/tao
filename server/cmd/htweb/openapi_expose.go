package main

import (
	"net/http"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/openapi"
)

// 开发者密钥能调哪些接口（开放清单），以及它们在「接口调用平台」上怎么展示。
//
// # 为什么是白名单而不是黑名单
//
// 漏写一条白名单，后果是「这个接口暂时用不了」—— 有人会来问，然后补上。
// 漏写一条黑名单，后果是「恢复出厂能被第三方调用」—— 没有人会来问。
// 两种漏写的代价差着数量级，所以只能是白名单。
//
// ⚠ 新加 /api 路由时，要么在这里加一条，要么在 keyDenied 里写明为什么不开。
//   有个测试盯着这件事（openapi_expose_test.go），漏了会红。
//
// # 为什么直接开放 /api，而不是在 /openapi/v1 下再镜像一套
//
// 镜像一套就要再写一套鉴权与参数处理，两套迟早分叉 ——
// 而分叉的表现是「接口上比界面多一条口子」，没有人会注意到。
// 现在密钥和会话走的是**同一条中间件、同一个 *User**，
// 所以「用密钥能做什么」永远等于「这个账号在界面上能做什么」。
//
// 代价要说清楚，而且必须写进对外文档：**/api 这一组跟着界面走**。
// 页面改版时它可能变。要一份不会变的合同，用 /openapi/v1 那 15 个
// （编号寻址、参数是人话、只增不改）。两组的定位不同，不是重复。

// exposedAPI 是一个开放给密钥调用的接口。
type exposedAPI struct {
	// Pattern 是路由注册时的原文，如 "GET /api/terminals"。
	Pattern string
	// Summary 是给人看的一句话。
	Summary string
	// Right 是需要的权限位说明（与路由上的守卫一致，这里只是写给人看）。
	Right string
	// Danger 为真时平台上会标红并改文案 —— 这些会真的改动系统。
	Danger bool
}

// exposedGroup 按界面功能分组。
type exposedGroup struct {
	Name string
	Desc string
	APIs []exposedAPI
}

// keyDenied 是**明确不开放**给密钥的接口，值是理由。
//
// 每一条都要写清楚为什么。写不出理由的，说明它其实该开放。
var keyDenied = map[string]string{
	// —— 会话本身 ——
	"POST /api/login":       "登录是给人用的。程序有密钥，不需要也不应该拿账号密码换会话",
	"POST /api/logout":      "密钥没有会话可以登出，调它没有任何意义",
	"GET /api/captcha":      "验证码是给人看的图片",
	"GET /api/auth/me":      "会话自省。密钥要看自己是谁，用 /openapi/v1 那边的接口",
	"GET /api/menu/list":    "左侧菜单，纯界面的东西",
	"GET /api/auth/buttons": "按钮置灰用的，纯界面的东西",

	// —— 破坏力最大的那几个 ——
	//
	// 这几条不是「权限不够」，而是「不该由程序发起」：
	// 它们都会中断广播、或者让数据不可逆地消失，出事时必须能回答
	// 「是谁点的」—— 而密钥背后是一个程序，答不上来。
	"POST /api/server/factory-reset":        "恢复出厂：清空全库。必须有人在界面上确认",
	"GET /api/server/factory-reset/preview": "恢复出厂的预览，跟着上面一起关",
	"POST /api/backups/restore":             "恢复备份等价于任意 SQL 执行",
	"POST /api/backups/upload":              "上传备份包，与恢复配套",
	"POST /api/server/reboot":               "重启服务器，会中断正在播的广播",
	"POST /api/server/version":              "换版本会重建容器、中断广播",
	"POST /api/offline/purge-all":           "四条无 WHERE 的 DELETE",

	// —— 注册服务 ——
	"GET /api/register":        "服务器注册是装机环节，不是集成环节",
	"POST /api/register":       "注册码会拼进 shell 命令，这条路只留给装机的人",
	"POST /api/register/trial": "领试用期是装机环节的事，程序不该替人领",
	"GET /api/register/status": "注册状态本来就是公开的，不需要密钥也能查",

	// —— 密钥自己 ——
	//
	// ⚠ 这四条是这份清单里最要紧的：允许拿密钥发密钥的话，
	//   一次泄露就永远收不回来 —— 吊销 A 的时候，A 发出去的 B 还活着。
	"GET /api/openapi-keys":            "密钥台账不该被密钥自己读到 —— 那等于泄露一把就能看清全部",
	"GET /api/openapi-keys/accounts":   "能挂到谁名下，只该由登录的人看",
	"POST /api/openapi-keys":           "拿密钥发密钥 = 一次泄露永远收不回来：吊销 A 时 A 发的 B 还活着",
	"PUT /api/openapi-keys/{id}/state": "能启停别的密钥，等于能把自己被吊销这件事撤销掉",
	"DELETE /api/openapi-keys/{id}":    "能删别的密钥，等于能替对手清掉证据",

	// —— AI 助手 ——
	//
	// 助手是给人用的对话框，会话上下文按人组织。程序要下发广播，
	// 直接调对应的接口，不必绕一圈让模型去猜。
	"GET /api/assistant/status":     "助手是给人用的对话框，会话上下文按人组织",
	"POST /api/assistant/chat":      "程序应当直接调对应接口，不必绕一圈让模型去猜意图",
	"GET /api/assistant/history":    "对话记录是某个人的，不该被程序读走",
	"DELETE /api/assistant/history": "清别人的对话记录，程序没有理由做这件事",
	"GET /api/assistant/settings":   "助手设置是按人存的界面偏好",
	"PUT /api/assistant/settings":   "改别人的界面偏好，程序没有理由做这件事",

	// —— 其它纯界面的东西 ——
	"GET /api/health":                  "探活接口，本来就不需要凭据",
	"GET /api/openapi/spec":            "接口目录，给界面渲染用",
	"GET /api/openapi/openapi.json":    "OpenAPI 文档，登录后在界面上下载",
	"GET /api/dashboard/config":        "首页三块可配置区域的当前配置，纯界面的东西",
	"PUT /api/dashboard/shortcuts":     "改的是所有人看到的首页快捷入口，该由人在界面上决定",
	"PUT /api/dashboard/quick-tasks":   "改的是所有人看到的首页快捷任务，该由人在界面上决定",
	"PUT /api/dashboard/emergency":     "首页那个紧急广播按钮绑什么，该由人在界面上决定",
	"GET /api/media/{id}/stream":       "音频流，浏览器 <audio> 用的；下载走 /download",
	"GET /api/backups/{name}/download": "备份包下载，浏览器 window.open 用的",
}

// exposedGroups 是开放清单本体，按界面功能分组。
var exposedGroups = []exposedGroup{
	{
		Name: "媒体与目录",
		Desc: "上传、查找、删除音频文件，以及媒体目录树。",
		APIs: []exposedAPI{
			{"GET /api/folders/tree", "媒体目录树", "登录即可", false},
			{"POST /api/folders", "新建媒体目录", "folderpriv", true},
			{"PUT /api/folders/{id}", "修改媒体目录", "folderpriv", true},
			{"GET /api/folders/delete-preview", "删除目录前看影响", "folderpriv", false},
			{"DELETE /api/folders", "删除媒体目录", "folderpriv", true},
			{"GET /api/media", "媒体列表（按目录）", "登录即可", false},
			{"POST /api/media/upload", "上传媒体文件", "mediapriv", true},
			{"GET /api/media/delete-preview", "删除媒体前看影响", "mediapriv", false},
			{"DELETE /api/media", "删除媒体", "mediapriv", true},
			{"POST /api/folders/{id}/media:clear", "清空目录内媒体", "mediapriv", true},
			{"GET /api/media/{id}/download", "下载媒体文件", "登录即可", false},
		},
	},
	{
		Name: "终端",
		Desc: "设备本身：状态、开关机、音量、密码、各种功能位，以及快捷键寻呼。",
		APIs: []exposedAPI{
			{"GET /api/terminals", "终端列表与状态", "登录即可", false},
			{"GET /api/terminals/{id}", "终端详情", "terminalpriv", false},
			{"GET /api/terminal-groups/tree", "终端分组树", "登录即可", false},
			{"GET /api/terminal-types", "终端型号清单", "登录即可", false},
			{"GET /api/terminals/delete-preview", "删除终端前看影响", "terminalpriv", false},
			{"PUT /api/terminals/{id}", "修改终端参数", "terminalpriv", true},
			{"PUT /api/terminals/start", "启动终端", "terminalpriv", true},
			{"PUT /api/terminals/stop", "停止终端", "terminalpriv", true},
			{"PUT /api/terminals/volume", "下发终端音量", "terminalpriv", true},
			{"PUT /api/terminals/password", "下发终端密码", "terminalpriv", true},
			{"PUT /api/terminals/circuit-check", "终端线路检测", "terminalpriv", true},
			{"PUT /api/terminals/sync-time", "给终端下发校时", "terminalpriv", true},
			{"PUT /api/terminals/toggle/{toggle}", "开关终端功能位（对讲/采播/录音/回叫/紧急）", "terminalpriv", true},
			{"DELETE /api/terminals", "删除终端", "terminalpriv", true},
			{"GET /api/terminals/{id}/shortcut-keys", "终端快捷键列表", "登录即可", false},
			{"GET /api/terminals/{id}/shortcut-keys/options", "可选的快捷键值", "登录即可", false},
			{"POST /api/terminals/{id}/shortcut-keys", "新建快捷键寻呼", "terminalpriv", true},
			{"PUT /api/shortcut-keys/{keyId}", "修改快捷键寻呼", "terminalpriv", true},
			{"DELETE /api/shortcut-keys", "删除快捷键寻呼", "terminalpriv", true},
			{"PUT /api/terminals/replace", "终端替换（换一台设备，绑定关系跟着走）", "terminalpriv", true},
			{"GET /api/terminals/{id}/call-groups", "这台终端的寻呼组", "登录即可", false},
			{"GET /api/terminals/{id}/call-groups/candidates", "可加进寻呼组的终端", "登录即可", false},
			{"GET /api/call-groups/{gid}", "寻呼组详情", "登录即可", false},
			{"POST /api/terminals/{id}/call-groups", "保存寻呼组", "terminalpriv", true},
			{"DELETE /api/terminals/{id}/call-groups", "删除寻呼组", "terminalpriv", true},
			{"GET /api/terminals/{id}/folders", "这台终端的授权终端目录树", "登录即可", false},
			{"GET /api/terminals/{id}/folders/terminals", "目录里有哪些终端", "登录即可", false},
			{"GET /api/terminals/{id}/folders/candidates", "可加进目录的终端", "登录即可", false},
			{"POST /api/terminals/{id}/folders", "新建/改名授权终端目录", "terminalpriv", true},
			{"DELETE /api/terminals/{id}/folders", "删除授权终端目录", "terminalpriv", true},
			{"POST /api/terminals/{id}/folders/terminals", "往目录里加终端", "terminalpriv", true},
			{"DELETE /api/terminals/{id}/folders/terminals", "从目录里移出终端", "terminalpriv", true},
			{"GET /api/terminals/{id}/quick-tasks", "这台终端的快捷任务", "登录即可", false},
			{"GET /api/terminals/{id}/quick-tasks/detail", "快捷任务详情", "登录即可", false},
			{"POST /api/terminals/{id}/quick-tasks", "新建快捷任务", "terminalpriv", true},
			{"POST /api/terminals/{id}/quick-tasks/update", "修改快捷任务", "terminalpriv", true},
			{"DELETE /api/terminals/{id}/quick-tasks", "删除快捷任务", "terminalpriv", true},
		},
	},
	{
		Name: "终端分区",
		Desc: "把终端归到分区里，广播时按分区选。",
		APIs: []exposedAPI{
			{"GET /api/zones", "分区列表", "登录即可", false},
			{"GET /api/zones/options", "分区下拉选项", "登录即可", false},
			{"GET /api/zones/terminals", "分区里有哪些终端", "登录即可", false},
			{"GET /api/zones/{id}", "分区详情", "登录即可", false},
			{"GET /api/zones/delete-preview", "删除分区前看影响", "terminalgrouppriv", false},
			{"POST /api/zones", "新建分区", "terminalgrouppriv", true},
			{"PUT /api/zones/{id}", "修改分区", "terminalgrouppriv", true},
			{"DELETE /api/zones", "删除分区", "terminalgrouppriv", true},
		},
	},
	{
		Name: "文件广播任务",
		Desc: "界面「任务管理」那一页。与 /openapi/v1/tasks 是同一批数据，区别是这里按编号寻址、参数是库里的形状。",
		APIs: []exposedAPI{
			{"GET /api/tasks", "任务列表", "登录即可", false},
			{"GET /api/tasks/{id}", "任务详情", "登录即可", false},
			{"POST /api/tasks", "新建任务", "taskpriv", true},
			{"PUT /api/tasks/{id}", "修改任务", "taskpriv", true},
			{"PUT /api/tasks/control/{action}", "启动/停止/暂停/恢复任务", "taskpriv", true},
			{"PUT /api/tasks/project-state", "启用/停用任务", "taskpriv", true},
			{"GET /api/tasks/delete-preview", "删除任务前看影响", "taskpriv", false},
			{"DELETE /api/tasks", "删除任务", "taskpriv", true},
			{"GET /api/task-folders/tree", "任务分组树", "登录即可", false},
			{"POST /api/task-folders", "新建任务分组", "taskpriv", true},
			{"PUT /api/task-folders/{id}", "修改任务分组", "taskpriv", true},
			{"DELETE /api/task-folders/{id}", "删除任务分组", "taskpriv", true},
			{"GET /api/task-options/media", "挑媒体（建任务时用）", "登录即可", false},
			{"GET /api/task-options/terminals", "挑终端（建任务时用）", "登录即可", false},
			{"GET /api/task-options/priority-range", "这个账号能用的任务级别区间", "登录即可", false},
			{"GET /api/quick-task-audio-sources", "快捷任务的音源清单", "登录即可", false},
			{"PUT /api/tasks/volume", "改任务音量（正在播的会立刻生效）", "taskpriv", true},
			{"POST /api/tasks/{id}/copy", "复制任务", "taskpriv", true},
			{"POST /api/tasks/sync-terminals", "把一条任务的终端清单同步给其它任务", "taskpriv", true},
			{"GET /api/tasks/emergency", "当前的紧急广播绑定", "taskpriv", false},
			{"PUT /api/tasks/emergency", "设置紧急广播", "taskpriv", true},
			{"DELETE /api/tasks/emergency", "取消紧急广播", "taskpriv", true},
		},
	},
	{
		Name: "作息方案",
		Desc: "上下课打铃。与 /openapi/v1/schedules 是同一批数据，这里能改到单条打铃条目。",
		APIs: []exposedAPI{
			{"GET /api/bell-plans", "作息方案列表", "登录即可", false},
			{"GET /api/bell-plans/detail", "方案详情（方案名放 query 里）", "登录即可", false},
			{"POST /api/bell-plans", "新建方案", "bellpriv", true},
			{"PUT /api/bell-plans", "修改方案（含改名）", "bellpriv", true},
			{"POST /api/bell-plans/items", "给方案加一条打铃", "bellpriv", true},
			{"PUT /api/bell-plans/items/{id}", "修改一条打铃", "bellpriv", true},
			{"DELETE /api/bell-plans/items", "删除打铃条目", "bellpriv", true},
			{"PUT /api/bell-plans/items/schedule", "智能排课（批量改条目时间）", "bellpriv", true},
			{"PUT /api/bell-plans/state", "启用/停用方案", "bellpriv", true},
			{"PUT /api/bell-plans/volume", "改方案音量", "bellpriv", true},
			{"GET /api/bell-plans/delete-preview", "删除方案前看影响", "bellpriv", false},
			{"DELETE /api/bell-plans", "删除方案", "bellpriv", true},
			{"POST /api/bell-plans/copy", "复制方案", "bellpriv", true},
		},
	},
	{
		Name: "节假日",
		Desc: "放假这天打不打铃。与作息方案是一件事的两面，所以权限也一样。",
		APIs: []exposedAPI{
			{"GET /api/holidays", "节假日列表", "登录即可", false},
			{"GET /api/holidays/overlaps", "看有没有日期重叠", "登录即可", false},
			{"GET /api/holidays/{id}", "节假日详情", "登录即可", false},
			{"POST /api/holidays", "新建节假日", "bellpriv", true},
			{"PUT /api/holidays/{id}", "修改节假日", "bellpriv", true},
			{"PUT /api/holidays/state", "启用/停用节假日", "bellpriv", true},
			{"DELETE /api/holidays", "删除节假日", "bellpriv", true},
		},
	},
	{
		Name: "分类任务",
		Desc: "终端功放 / 采播管理 / 文字语音 / LED 播放。四页共用一套路由，类别放在路径的 {kind} 上（amplifier / collect / tts / led）。⚠ 四种的权限位各不相同。",
		APIs: []exposedAPI{
			{"GET /api/typed-tasks/{kind}", "分类任务列表", "登录即可", false},
			{"GET /api/typed-tasks/{kind}/{id}", "分类任务详情", "登录即可", false},
			{"GET /api/typed-tasks/{kind}/sources", "建这类任务时的可选音源", "登录即可", false},
			{"GET /api/typed-tasks/terminals", "可选终端", "登录即可", false},
			{"GET /api/typed-tasks/prompts", "文字语音的提示音清单", "登录即可", false},
			{"POST /api/typed-tasks/{kind}", "新建分类任务", "按类别（powerplay/admpriv/ttspriv/telephonepriv）", true},
			{"PUT /api/typed-tasks/{kind}/{id}", "修改分类任务", "按类别", true},
			{"PUT /api/typed-tasks/{kind}/control/{action}", "启停分类任务", "按类别", true},
			{"PUT /api/typed-tasks/{kind}/project-state", "启用/停用分类任务", "按类别", true},
			{"DELETE /api/typed-tasks/{kind}", "删除分类任务", "按类别", true},
			{"GET /api/led/folders", "LED 任务目录", "登录即可", false},
			{"POST /api/led/folders", "新建 LED 目录", "telephonepriv", true},
			{"PUT /api/led/folders/{id}", "重命名 LED 目录", "telephonepriv", true},
			{"DELETE /api/led/folders/{id}", "删除 LED 目录", "telephonepriv", true},
			{"POST /api/led/folders:copy", "复制 LED 目录", "telephonepriv", true},
			{"GET /api/led/devices", "LED 屏设备列表", "登录即可", false},
			{"POST /api/led/devices", "新建 LED 屏设备", "telephonepriv", true},
			{"PUT /api/led/devices/{id}", "修改 LED 屏设备", "telephonepriv", true},
			{"DELETE /api/led/devices", "删除 LED 屏设备", "telephonepriv", true},
		},
	},
	{
		Name: "启用管理",
		Desc: "「到了某年某月某日某时，把这一批任务按各自的安排启用或停用」。做学期切换、放假前后的批量启停很好用。",
		APIs: []exposedAPI{
			{"GET /api/enable-plans", "启用计划列表", "登录即可", false},
			{"GET /api/enable-plans/tasks", "可选任务清单", "登录即可", false},
			{"GET /api/enable-plans/{id}", "启用计划详情", "登录即可", false},
			{"POST /api/enable-plans", "新建启用计划", "ttspriv", true},
			{"PUT /api/enable-plans/{id}", "修改启用计划", "ttspriv", true},
			{"DELETE /api/enable-plans", "删除启用计划", "ttspriv", true},
		},
	},
	{
		Name: "报警",
		Desc: "报警分区与报警映射：某个报警源触发时，往哪些终端播什么。",
		APIs: []exposedAPI{
			{"GET /api/alarm-areas", "报警分区列表", "登录即可", false},
			{"GET /api/alarm-areas/{id}", "报警分区详情", "登录即可", false},
			{"GET /api/alarm-areas/delete-preview", "删除前看影响", "alarmgrouppriv", false},
			{"POST /api/alarm-areas", "新建报警分区", "alarmgrouppriv", true},
			{"PUT /api/alarm-areas/{id}", "修改报警分区", "alarmgrouppriv", true},
			{"DELETE /api/alarm-areas", "删除报警分区", "alarmgrouppriv", true},
			{"GET /api/alarm-mappings", "报警映射列表", "登录即可", false},
			{"GET /api/alarm-mappings/{id}", "报警映射详情", "登录即可", false},
			{"POST /api/alarm-mappings", "新建报警映射", "alarmgrouppriv", true},
			{"PUT /api/alarm-mappings/{id}", "修改报警映射", "alarmgrouppriv", true},
			{"DELETE /api/alarm-mappings", "删除报警映射", "alarmgrouppriv", true},
			{"GET /api/alarm-options/hosts", "可选的报警主机", "登录即可", false},
			{"GET /api/alarm-options/media", "可选的报警音", "登录即可", false},
			{"GET /api/alarm-options/terminals", "可选的报警终端", "登录即可", false},
			{"GET /api/alarm-options/areas", "可选的报警分区", "登录即可", false},
		},
	},
	{
		Name: "噪声检测",
		Desc: "噪声设备与声场分区：按环境噪声自动调音量。",
		APIs: []exposedAPI{
			{"GET /api/sound/devices", "噪声设备列表", "登录即可", false},
			{"GET /api/sound/devices/options", "噪声设备下拉选项", "登录即可", false},
			{"GET /api/sound/devices/{id}", "噪声设备详情", "登录即可", false},
			{"POST /api/sound/devices", "新建噪声设备", "terminalgrouppriv", true},
			{"PUT /api/sound/devices/{id}", "修改噪声设备", "terminalgrouppriv", true},
			{"DELETE /api/sound/devices", "删除噪声设备", "terminalgrouppriv", true},
			{"GET /api/sound/groups", "声场分区列表", "登录即可", false},
			{"GET /api/sound/groups/options", "声场分区下拉选项", "登录即可", false},
			{"GET /api/sound/groups/terminals", "声场分区里的终端", "登录即可", false},
			{"GET /api/sound/groups/{id}", "声场分区详情", "登录即可", false},
			{"POST /api/sound/groups", "新建声场分区", "terminalgrouppriv", true},
			{"PUT /api/sound/groups/{id}", "修改声场分区", "terminalgrouppriv", true},
			{"DELETE /api/sound/groups", "删除声场分区", "terminalgrouppriv", true},
		},
	},
	{
		Name: "云广播与离线",
		Desc: "把媒体和任务下发到终端本地，断网也能按时播。",
		APIs: []exposedAPI{
			{"GET /api/offline/states", "离线状态总览", "登录即可", false},
			{"GET /api/offline/summary", "离线数据统计", "登录即可", false},
			{"GET /api/offline/media", "媒体下发状态", "登录即可", false},
			{"GET /api/offline/tasks", "任务下发状态", "登录即可", false},
			{"POST /api/offline/media", "下发媒体到终端", "terminalpriv", true},
			{"POST /api/offline/tasks", "下发任务到终端", "serverpriv", true},
			{"PUT /api/offline/stop", "停止下发", "terminalpriv", true},
			{"GET /api/cloud/terminals", "云广播终端列表", "登录即可", false},
			{"GET /api/cloud/terminals/{id}/inventory", "终端上有哪些媒体/任务", "登录即可", false},
			{"POST /api/cloud/bulk", "批量下发", "登录即可", true},
			{"GET /api/transfer/tasks", "任务传送列表", "登录即可", false},
			{"GET /api/transfer/tasks/{id}", "任务传送详情", "登录即可", false},
			{"GET /api/transfer/tasks/{id}/media", "该任务要传的媒体", "登录即可", false},
			{"POST /api/transfer/bulk", "批量传送任务", "登录即可", true},
		},
	},
	{
		Name: "遥控任务",
		Desc: "遥控器按键绑定到任务。",
		APIs: []exposedAPI{
			{"GET /api/remote-keys", "遥控绑定列表", "登录即可", false},
			{"GET /api/remote-keys/tasks", "可绑定的任务", "登录即可", false},
			{"GET /api/remote-keys/{id}", "遥控绑定详情", "登录即可", false},
			{"POST /api/remote-keys", "新建遥控绑定", "serverpriv", true},
			{"PUT /api/remote-keys/{id}", "修改遥控绑定", "serverpriv", true},
			{"DELETE /api/remote-keys", "删除遥控绑定", "serverpriv", true},
		},
	},
	{
		Name: "时间与服务器",
		Desc: "校时、NTP/GPS、服务器参数。⚠ 重启、换版本、恢复出厂**不开放**给密钥，见文档。",
		APIs: []exposedAPI{
			{"GET /api/time", "服务器时间与时区", "登录即可", false},
			{"GET /api/time/terminals", "可校时的终端", "登录即可", false},
			{"PUT /api/time/ntp", "设置 NTP 校时", "serverpriv", true},
			{"PUT /api/time/gps", "设置 GPS 校时终端", "serverpriv", true},
			{"POST /api/time/sync", "给终端下发校时", "terminalpriv", true},
			{"PUT /api/time/clock", "设置服务器系统时钟", "serverpriv", true},
			{"GET /api/server/params", "服务器参数", "serverpriv", false},
			{"PUT /api/server/params", "修改服务器参数", "serverpriv", true},
			{"GET /api/server/auto-restart", "定时重启设置", "serverpriv", false},
			{"PUT /api/server/auto-restart", "修改定时重启设置", "serverpriv", true},
			{"GET /api/server/version", "当前版本", "serverpriv", false},
		},
	},
	{
		Name: "看板与日志",
		Desc: "总览数据与操作/任务日志。做监控大屏、对账最常用的一组。",
		APIs: []exposedAPI{
			{"GET /api/dashboard/overview", "首页总览数据", "登录即可", false},
			{"GET /api/dashboard/perf", "服务器性能指标", "登录即可", false},
			{"GET /api/dashboard/tasks", "首页任务浏览", "登录即可", false},
			{"GET /api/logs", "操作日志", "超级管理员", false},
			{"GET /api/logs/stats", "操作日志统计", "超级管理员", false},
			{"DELETE /api/logs", "清理操作日志", "超级管理员", true},
			{"GET /api/logs/retention", "日志保留期设置", "超级管理员", false},
			{"PUT /api/logs/retention", "修改日志保留期", "超级管理员", true},
			{"GET /api/task-logs/files", "任务日志文件清单", "超级管理员", false},
			{"GET /api/task-logs/files/{name}", "读一个任务日志文件", "超级管理员", false},
			{"GET /api/task-logs/delete-preview", "清理任务日志前看影响", "超级管理员", false},
			{"DELETE /api/task-logs", "清理任务日志", "超级管理员", true},
		},
	},
	{
		Name: "用户与用户组",
		Desc: "账号与权限。⚠ 开放它意味着密钥能建账号 —— 只在确实需要「从别的系统同步人员」时才给这个权限位。",
		APIs: []exposedAPI{
			{"GET /api/users", "用户列表", "userpriv", false},
			{"GET /api/users/{id}", "用户详情", "userpriv", false},
			{"GET /api/users/wind-capacity", "通道容量", "userpriv", false},
			{"GET /api/users/terminal-options", "可绑定的终端", "userpriv", false},
			{"GET /api/users/delete-preview", "删除用户前看影响", "userpriv", false},
			{"POST /api/users", "新建用户", "userpriv + 超级管理员", true},
			{"PUT /api/users/{id}", "修改用户", "userpriv", true},
			{"POST /api/users/{id}/enable", "启用/停用用户", "userpriv + 超级管理员", true},
			{"DELETE /api/users", "删除用户", "userpriv + 超级管理员", true},
			{"GET /api/usergroups", "用户组列表", "userpriv", false},
			{"GET /api/usergroups/options", "用户组下拉选项", "userpriv", false},
			{"GET /api/usergroups/{id}/delete-preview", "删除用户组前看影响", "userpriv", false},
			{"POST /api/usergroups", "新建用户组", "userpriv + 超级管理员", true},
			{"PUT /api/usergroups/{id}", "修改用户组", "userpriv + 超级管理员", true},
			{"DELETE /api/usergroups/{id}", "删除用户组", "userpriv + 超级管理员", true},
		},
	},
	{
		Name: "备份",
		Desc: "备份包的查看与创建。⚠ **恢复**和**上传**不开放给密钥 —— 恢复等价于任意 SQL 执行。",
		APIs: []exposedAPI{
			{"GET /api/backups", "备份列表", "超级管理员", false},
			{"POST /api/backups", "创建备份", "超级管理员", true},
			{"DELETE /api/backups", "删除备份", "超级管理员", true},
			{"GET /api/backups/{name}/restore-precheck", "恢复前的结构比对", "超级管理员", false},
		},
	},
}

// exposedIndex 是开放清单的查表版本，keyGate 用。
var exposedIndex = func() map[string]exposedAPI {
	m := make(map[string]exposedAPI, 160)
	for _, g := range exposedGroups {
		for _, a := range g.APIs {
			m[a.Pattern] = a
		}
	}
	return m
}()

// keyGate 挡住「用密钥调不开放的接口」。
//
// ⚠ 只在**用密钥认证**时才拦。登录用户照常访问所有接口 ——
// 这个清单管的是对外暴露面，不是权限。权限仍然由各路由自己的守卫管。
func keyGate(pattern string, next http.HandlerFunc) http.HandlerFunc {
	// /openapi/v1 那一组是专门给密钥用的，不受这份清单约束
	if strings.Contains(pattern, " /openapi/") {
		return next
	}
	_, allowed := exposedIndex[pattern]
	if allowed {
		return next
	}
	return func(w http.ResponseWriter, r *http.Request) {
		// ⚠ 用 WillUseAPIKey 而不是 ViaAPIKey：这一层跑在鉴权中间件**之前**，
		//   那时上下文里还没有身份。第一版就栽在这儿 —— 挡板装了，
		//   但装在了它要挡的东西前面，恢复出厂、发密钥全都能用密钥调通。
		if auth.WillUseAPIKey(r) {
			// 说清楚是「这个接口不对密钥开放」，而不是「你权限不够」——
			// 后者会让人跑去给账号加权限，加完还是不行。
			httpx.Fail(w, httpx.CodeForbidden,
				"这个接口不开放给开发者密钥调用，请在界面上操作。可调用的接口见「接口调用平台」")
			return
		}
		next(w, r)
	}
}

// APICatalog 把开放清单渲染成接口平台要的结构。
//
// 与 openapi.Catalog() 那 15 个**合并**成一份目录给前端：
// 前面是「常用接口」（编号寻址、参数是人话、路径带版本号、只增不改），
// 后面是「全部功能接口」（跟着界面走）。两组的定位不同，不是重复。
func APICatalog() []openapi.Group {
	groups := make([]openapi.Group, 0, len(exposedGroups))
	for _, g := range exposedGroups {
		eps := make([]openapi.Endpoint, 0, len(g.APIs))
		for _, a := range g.APIs {
			method, path, _ := strings.Cut(a.Pattern, " ")
			eps = append(eps, openapi.Endpoint{
				ID:      strings.ToLower(method) + ":" + path,
				Method:  method,
				Path:    path,
				Summary: a.Summary,
				Right:   a.Right,
				Danger:  a.Danger,
				// 这一组没有逐参数说明，平台上给可编辑的路径 + 自由请求体。
				// 理由见 openapi.Endpoint.Freeform 上的注释。
				Freeform: true,
			})
		}
		groups = append(groups, openapi.Group{
			Name: g.Name, Desc: g.Desc,
			// Path 本身就是完整路径（/api/...），不需要再拼前缀
			Prefix:    "",
			Section:   openapi.SectionFull,
			Endpoints: eps,
		})
	}
	return groups
}
