package main

import (
	"net/http"
	"strings"

	"htweb/internal/audit"
	"htweb/internal/auth"
)

// 操作日志的落库入口。
//
// # 为什么做成中间件而不是逐个 handler 里写
//
// 旧 PHP 是在 do.php 派发器末尾统一调一次 insert_log($opt, ...)，
// 一个写请求落一行，$opt 就是动作名。新版照搬这个粒度：
// 在路由层包一层，方法为 POST/PUT/DELETE 的接口成功后落一行。
//
// 逐个 handler 里写的话，四十多个写接口迟早会漏掉几个，
// 而「漏记」这种缺陷平时完全看不出来，只有出事要追责时才发现。
//
// # 怎么判断「成功」
//
// httpx.Fail 按 Geeker 拦截器的约定统一回 HTTP 200、业务码放 body，
// 所以不能看 HTTP 状态码。这里包一层 ResponseWriter，
// 只嗅探响应体开头的一小段找 `"code":200` —— 信封总是以 code 开头，
// 嗅前 32 字节就够，不必把整个响应缓存下来。

// auditLabels 把「方法 + 路由模式」映射成人能读懂的操作名。
//
// 键用注册路由时的模式原文，不用实际 URL —— 实际 URL 里带 id，
// 一个接口会散成无数种 operate 值，日志就没法按操作聚合了。
var auditLabels = map[string]string{
	// POST /api/login 不在这里：登录成功那一刻请求上下文里还没有会话，
	// 中间件取不到用户名，由 handleLogin 自己记一行。
	"POST /api/logout": "用户登出",

	"POST /api/folders":                  "新建媒体目录",
	"PUT /api/folders/{id}":              "修改媒体目录",
	"DELETE /api/folders":                "删除媒体目录",
	"POST /api/media/upload":             "上传媒体",
	"DELETE /api/media":                  "删除媒体",
	"POST /api/folders/{id}/media:clear": "清空媒体目录",

	"POST /api/usergroups":        "新建用户组",
	"PUT /api/usergroups/{id}":    "修改用户组",
	"DELETE /api/usergroups/{id}": "删除用户组",
	"POST /api/users":             "新建用户",
	"PUT /api/users/{id}":         "修改用户",
	"POST /api/users/{id}/enable": "启用/停用用户",
	"DELETE /api/users":           "删除用户",

	"PUT /api/terminals/{id}":            "修改终端",
	"PUT /api/terminals/start":           "启动终端",
	"PUT /api/terminals/stop":            "停止终端",
	"PUT /api/terminals/volume":          "下发终端音量",
	"PUT /api/terminals/password":        "下发终端密码",
	"PUT /api/terminals/circuit-check":   "终端线路检测",
	"PUT /api/terminals/sync-time":       "终端同步时间",
	"PUT /api/terminals/toggle/{toggle}": "终端开关设置",
	"DELETE /api/terminals":              "删除终端",

	"POST /api/tasks":                 "新建任务",
	"PUT /api/tasks/{id}":             "修改任务",
	"DELETE /api/tasks":               "删除任务",
	"PUT /api/tasks/control/{action}": "任务启停",
	"PUT /api/tasks/project-state":    "启用/停用方案",
	"PUT /api/tasks/emergency":        "设置紧急任务",
	"DELETE /api/tasks/emergency":     "取消紧急任务",
	"PUT /api/tasks/volume":           "设置任务音量",
	"POST /api/tasks/{id}/copy":       "复制任务",
	"POST /api/tasks/sync-terminals":  "任务批量加终端",
	"POST /api/task-folders":          "新建任务分组",
	"PUT /api/task-folders/{id}":      "修改任务分组",
	"DELETE /api/task-folders/{id}":   "删除任务分组",

	// 四种类别共用一套路由，operate 名里不带类别 —— 路由模式是
	// /api/typed-tasks/{kind}，按模式聚合本来就分不出类别。
	// 要区分是哪一类，看同一时刻的任务记录即可。
	"POST /api/typed-tasks/{kind}":                 "新建任务",
	"PUT /api/typed-tasks/{kind}/{id}":             "修改任务",
	"PUT /api/typed-tasks/{kind}/control/{action}": "任务启停",
	"PUT /api/typed-tasks/{kind}/project-state":    "启用/停用方案",
	"DELETE /api/typed-tasks/{kind}":               "删除任务",

	"POST /api/led/devices":     "新建LED设备",
	"PUT /api/led/devices/{id}": "修改LED设备",
	"DELETE /api/led/devices":   "删除LED设备",

	"POST /api/enable-plans":     "新建启用计划",
	"PUT /api/enable-plans/{id}": "修改启用计划",
	"DELETE /api/enable-plans":   "删除启用计划",

	"POST /api/sound/devices":     "新建噪声设备",
	"PUT /api/sound/devices/{id}": "修改噪声设备",
	"DELETE /api/sound/devices":   "删除噪声设备",
	"POST /api/sound/groups":      "新建声场分区",
	"PUT /api/sound/groups/{id}":  "修改声场分区",
	"DELETE /api/sound/groups":    "删除声场分区",

	"POST /api/zones":     "新建终端分区",
	"PUT /api/zones/{id}": "修改终端分区",
	"DELETE /api/zones":   "删除终端分区",

	"POST /api/holidays":      "新建节假日",
	"PUT /api/holidays/{id}":  "修改节假日",
	"PUT /api/holidays/state": "启用/停用节假日",
	"DELETE /api/holidays":    "删除节假日",

	"POST /api/remote-keys":     "新建遥控任务",
	"PUT /api/remote-keys/{id}": "修改遥控任务",
	"DELETE /api/remote-keys":   "删除遥控任务",

	// 旧版 setgpsterminal.php 是少数几个自己写 log 表的页面之一，
	// 记的是「设置gps」/「取消gps」。新版统一由中间件记，措辞对齐。
	"PUT /api/time/ntp":   "设置NTP服务器",
	"PUT /api/time/gps":   "设置GPS校时终端",
	"POST /api/time/sync": "下发终端校时",

	"POST /api/alarm-mappings":     "新建报警映射",
	"PUT /api/alarm-mappings/{id}": "修改报警映射",
	"DELETE /api/alarm-mappings":   "取消报警映射",
	"POST /api/alarm-areas":        "新建报警分区",
	"PUT /api/alarm-areas/{id}":    "修改报警分区",
	"DELETE /api/alarm-areas":      "删除报警分区",

	"POST /api/bell-plans":           "新建作息方案",
	"PUT /api/bell-plans":            "修改作息方案",
	"DELETE /api/bell-plans":         "删除作息方案",
	"PUT /api/bell-plans/state":      "启停作息方案",
	"PUT /api/bell-plans/volume":     "调整作息方案音量",
	"POST /api/bell-plans/copy":      "复制作息方案",
	"POST /api/bell-plans/items":     "新增打铃条目",
	"PUT /api/bell-plans/items/{id}": "修改打铃条目",
	"DELETE /api/bell-plans/items":   "删除打铃条目",

	"DELETE /api/task-logs": "清理任务日志",

	"POST /api/backups":   "创建备份",
	"DELETE /api/backups": "删除备份包",

	"POST /api/offline/media":     "离线媒体下发",
	"POST /api/offline/tasks":     "离线任务下发",
	"PUT /api/offline/stop":       "停止离线传输",
	"POST /api/offline/purge-all": "清空全部离线数据",

	"PUT /api/server/params":       "修改服务器参数",
	"PUT /api/server/auto-restart": "修改定时重启",
	// POST /api/server/reboot 不在这里：包一发机器就没了，
	// 由 handler 在发包**之前**自己记一行。
	// POST /api/server/factory-reset 不在这里：恢复出厂会把 log 表整个清掉，
	// 由 handler 在执行前后各记一行（前一行随表被清、后一行是清空后的第一条痕迹）。
	// POST /api/backups/restore 不在这里：恢复会把 log 表也换成备份里的内容，
	// 事后再记等于记进一个马上要被覆盖的表，由 handler 在恢复**之前**自己记一行。
	// DELETE /api/logs 不在这里：清空操作日志由 logs.Clear 在同一个事务里
	// 自己写审计记录（必须与删除同进同出），中间件再记一条就重复了。
}

// sniffWriter 只看响应体开头几十个字节，判断业务码是不是 200。
type sniffWriter struct {
	http.ResponseWriter
	head []byte
	ok   bool
	done bool
}

func (s *sniffWriter) Write(b []byte) (int, error) {
	if !s.done {
		s.head = append(s.head, b...)
		if len(s.head) >= 32 {
			s.done = true
			s.ok = strings.Contains(string(s.head[:32]), `"code":200`)
		}
	}
	return s.ResponseWriter.Write(b)
}

func (s *sniffWriter) success() bool {
	if s.done {
		return s.ok
	}
	// 响应短于 32 字节时在这里补判一次
	return strings.Contains(string(s.head), `"code":200`)
}

// withAudit 包一层操作日志。label 为空表示这个接口不记录。
func (a *app) withAudit(label string, h http.HandlerFunc) http.HandlerFunc {
	if label == "" {
		return h
	}
	return func(w http.ResponseWriter, r *http.Request) {
		sw := &sniffWriter{ResponseWriter: w}
		h(sw, r)
		if !sw.success() {
			return
		}
		a.auditor.Write(r.Context(), a.auditUser(r), label, audit.ClientIP(r))
	}
}

// auditUser 取本次请求的用户名。
//
// ⚠ 这里**不能**用 auth.From(r.Context())。
//
// 鉴权中间件是这样把用户放进上下文的：
//
//	next(w, r.WithContext(context.WithValue(r.Context(), userKey, u)))
//
// r.WithContext 返回的是**一个新的 *http.Request**，用户只存在于那个副本里。
// 而 withAudit 包在鉴权中间件外面，手上拿的是原始的 r，
// 它的上下文里永远没有用户 —— 现网实测就是这样落出一行 user='-' 的记录。
//
// 改成直接拿请求头里的 token 去会话表反查，与中间件用的是同一个来源，
// 不依赖上下文能不能传出来。
func (a *app) auditUser(r *http.Request) string {
	if u, ok := a.authMgr.Resolve(r.Header.Get(auth.HeaderToken)); ok && u.Username != "" {
		return u.Username
	}
	return "-"
}

// auditFor 按注册模式查表，找不到就返回空（= 不记录）。
func auditFor(pattern string) string { return auditLabels[pattern] }
