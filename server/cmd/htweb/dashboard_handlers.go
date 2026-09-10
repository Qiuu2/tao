package main

import (
	"fmt"
	"net/http"
	"strings"

	"htweb/internal/audit"
	"htweb/internal/auth"
	"htweb/internal/dashboard"
	"htweb/internal/httpx"
	"htweb/internal/i18n"
	"htweb/internal/sdkudp"
	"htweb/internal/store"
)

// 看板首页 —— 排版见 docs/image/2.png。

func failDash(w http.ResponseWriter, action string, err error) {
	msg := err.Error()
	for _, kw := range []string{"不能为空", "最多", "不认识", "重新选择", "只能", "未配置"} {
		if strings.Contains(msg, kw) {
			httpx.Fail(w, httpx.CodeBadRequest, msg)
			return
		}
	}
	httpx.Internal(w, action, err)
}

func (a *app) handleDashOverview(w http.ResponseWriter, r *http.Request) {
	ov, err := a.dash.Overview(r.Context(), auth.From(r.Context()))
	if err != nil {
		failDash(w, "查询设备概况", err)
		return
	}
	httpx.OK(w, ov)
}

func (a *app) handleDashPerf(w http.ResponseWriter, r *http.Request) {
	// 磁盘统计挂载点取媒体根目录 —— 用户关心的是「放媒体的那块盘还剩多少」
	httpx.OK(w, a.dash.Perf(a.cfg.Media.Root, a.serverIP(r)))
}

// serverIP 取 serverbaseparam.ip，用来挑统计流量的网卡。
// 读不到就返回空串，Perf 会退回「第一块非虚拟网卡」。
func (a *app) serverIP(r *http.Request) string {
	p, err := a.params.Get(r.Context())
	if err != nil {
		return ""
	}
	return p.Network.IP
}

func (a *app) handleDashConfig(w http.ResponseWriter, r *http.Request) {
	cfg, err := a.dash.Config(r.Context())
	if err != nil {
		failDash(w, "查询看板配置", err)
		return
	}
	httpx.OK(w, cfg)
}

type shortcutsReq struct {
	Shortcuts []dashboard.Shortcut `json:"shortcuts"`
}

func (a *app) handleDashShortcuts(w http.ResponseWriter, r *http.Request) {
	var in shortcutsReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.dash.SetShortcuts(in.Shortcuts); err != nil {
		failDash(w, "保存快捷入口", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"count": len(in.Shortcuts)})
}

type quickTasksReq struct {
	TaskIDs []int64 `json:"taskIds"`
}

func (a *app) handleDashQuickTasks(w http.ResponseWriter, r *http.Request) {
	var in quickTasksReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.dash.SetQuickTasks(r.Context(), in.TaskIDs); err != nil {
		failDash(w, "绑定快捷任务", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"count": len(in.TaskIDs)})
}

// emergencyAuditLabel 是四路各自的操作日志文案，[0]=下发 [1]=停止。
//
// # 为什么摊成八条写死的字符串，而不是拼出来
//
// 两个原因，缺一不可：
//  1. 日志页会把 operate 按界面语言翻一遍，而翻译是**按整句原文查字典**的。
//     拼出来的句子在字典里找不到，英文界面下这一行就永远是中文。
//  2. 字典的一致性测试（i18n/dict_test.go）会拿每条中文回 Go 源码里找一遍。
//     写死在这儿，改了名字测试立刻就红。
//
// 八条重复得有点笨，但这是整个系统里最需要事后说清「谁在几点放了哪一路」的动作。
var emergencyAuditLabel = map[string][2]string{
	"quake":    {"下发紧急广播（地震）", "停止紧急广播（地震）"},
	"evacuate": {"下发紧急广播（疏散）", "停止紧急广播（疏散）"},
	"alert":    {"下发紧急广播（警戒）", "停止紧急广播（警戒）"},
	"fire":     {"下发紧急广播（消防）", "停止紧急广播（消防）"},
}

type emergencyPlayReq struct {
	// Key 是四个固定槽位之一：quake / evacuate / alert / fire
	Key string `json:"key"`
	// Stop 为 true 表示停止这一路，false 表示开始播。
	Stop bool `json:"stop"`
}

// handleDashEmergencyPlay 触发（或停止）一路紧急广播。
//
// 它**不写任何数据**：一条 SDK 命令发到后台服务的 8885 端口，
// 由后台服务去驱动终端。所以这里没有「绑定」这一步，四个按钮随时可按。
//
// 发到**哪台机器**默认跟着浏览器打开这个页面的地址走（r.Host）——
// 后台服务和 Web 跑在同一台机器上，那台机器就是地址栏里的那个地址。
// 两者不在一起时在 config.yaml 的 sdk.host 里钉死。详见 sdkudp.Sender.Host。
//
// ⚠ UDP 没有回执。这个接口返回成功，只能说明包发出去了 ——
// 后台服务收没收到、终端响没响，这一侧看不见。前端提示因此写「已下发」。
func (a *app) handleDashEmergencyPlay(w http.ResponseWriter, r *http.Request) {
	var in emergencyPlayReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	slot, ok := dashboard.FindSlot(strings.TrimSpace(in.Key))
	if !ok {
		// 拼过字符串的 msg 在响应边界那一层配不上字典，所以在这儿就把原文翻好。
		httpx.Fail(w, httpx.CodeBadRequest, fmt.Sprintf(
			i18n.TC(r.Context(), "紧急广播只有 quake / evacuate / alert / fire 四路，不认识 %s"), in.Key))
		return
	}

	stop := int32(0)
	if in.Stop {
		stop = 1
	}

	// 审计写在动手之前，而且发包失败也留着这一行：
	// 「谁在几点按了哪一路」这件事，比这一包有没有成功发出去更值得记 ——
	// UDP 本来也判断不出对面收没收到，事后追的是**按钮被按过**。
	u := auth.From(r.Context())
	a.auditor.Write(r.Context(), u.Username,
		emergencyAuditLabel[slot.Key][stop], audit.ClientIP(r))

	err := a.sdk.SendUrgentPlay(r.Context(), r.Host, sdkudp.UrgentPlay{
		ChannelID: slot.ChannelID,
		KeyID:     slot.KeyID,
		// 0 = 全部终端。紧急广播按定义就是全场都要听见，
		// 界面上也没有选终端这一步。
		TerminalID: 0,
		IsStop:     stop,
	})
	if err != nil {
		httpx.Internal(w, "下发紧急广播", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"key": slot.Key, "stop": in.Stop})
}

func (a *app) handleDashBrowse(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 20))

	scope := q.Get("scope")
	if scope == "" {
		scope = "enabled"
	}
	res, err := a.dash.Browse(r.Context(), auth.From(r.Context()), dashboard.BrowseQuery{
		FolderID: int64(atoiDefault(q.Get("folderId"), 0)),
		Weekday:  atoiDefault(q.Get("weekday"), 0),
		AutoMode: atoiDefault(q.Get("autoMode"), 0),
		Scope:    scope,
		Pager:    pager,
	})
	if err != nil {
		failDash(w, "浏览任务", err)
		return
	}
	httpx.OK(w, map[string]interface{}{
		"list": res.Items, "total": res.Total,
		"pageNum": pager.PageNum, "pageSize": pager.PageSize,
	})
}
