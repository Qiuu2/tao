package main

import (
	"net/http"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/dashboard"
	"htweb/internal/httpx"
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

type emergencyReq struct {
	Slots map[string]int64 `json:"slots"`
}

func (a *app) handleDashEmergency(w http.ResponseWriter, r *http.Request) {
	var in emergencyReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.dash.SetEmergency(r.Context(), in.Slots); err != nil {
		failDash(w, "绑定紧急广播", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"slots": len(in.Slots)})
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
