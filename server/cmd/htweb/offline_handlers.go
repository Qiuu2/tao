package main

import (
	"errors"
	"net/http"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/offline"
	"htweb/internal/store"
)

// 离线管理 F-43 ~ F-47。

func failOffline(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, offline.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, err.Error())
	default:
		if isOfflineValidationErr(err) {
			httpx.Fail(w, httpx.CodeBadRequest, err.Error())
			return
		}
		httpx.Internal(w, action, err)
	}
}

func isOfflineValidationErr(err error) bool {
	msg := err.Error()
	for _, kw := range []string{
		"请选择", "只能是", "最多", "不存在", "重新选择", "未绑定", "不属于", "不正确", "必须",
	} {
		if strings.Contains(msg, kw) {
			return true
		}
	}
	return false
}

// ---------- F-43 媒体下发 ----------

type offlineMediaReq struct {
	MediaIDs    []int64 `json:"mediaIds"`
	TerminalIDs []int64 `json:"terminalIds"`
	Mode        string  `json:"mode"`
}

func (a *app) handleOfflineMediaDispatch(w http.ResponseWriter, r *http.Request) {
	var in offlineMediaReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.offline.Dispatch(r.Context(), auth.From(r.Context()), offline.MediaInput{
		MediaIDs:    in.MediaIDs,
		TerminalIDs: in.TerminalIDs,
		Mode:        offline.Mode(in.Mode),
	})
	if err != nil {
		failOffline(w, "离线媒体下发", err)
		return
	}
	// 所有状态变更都通知：旧版只在 flag=2/5 时发，flag=1（空闲离线）不发，
	// 后台就不知道有新的空闲传输任务排进来了（D-152）
	a.notifier.OfflineChanged(r.Context())
	httpx.OK(w, res)
}

// ---------- F-44 任务下发 ----------

type offlineTaskReq struct {
	TaskIDs     []int64 `json:"taskIds"`
	TerminalIDs []int64 `json:"terminalIds"`
	Mode        string  `json:"mode"`
}

func (a *app) handleOfflineTaskDispatch(w http.ResponseWriter, r *http.Request) {
	var in offlineTaskReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.offline.DispatchTasks(r.Context(), auth.From(r.Context()), offline.TaskInput{
		TaskIDs:     in.TaskIDs,
		TerminalIDs: in.TerminalIDs,
		Mode:        offline.Mode(in.Mode),
	})
	if err != nil {
		failOffline(w, "离线任务下发", err)
		return
	}
	a.notifier.OfflineChanged(r.Context())
	httpx.OK(w, res)
}

// ---------- F-45 状态查询 ----------

func (a *app) handleOfflineMediaStatus(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 20))

	res, err := a.offline.MediaStatusList(r.Context(), auth.From(r.Context()),
		offline.MediaStatusQuery{
			TerminalID: int64(atoiDefault(q.Get("terminalId"), 0)),
			MediaID:    int64(atoiDefault(q.Get("mediaId"), 0)),
			TaskID:     int64(atoiDefault(q.Get("taskId"), 0)),
			State:      atoiDefault(q.Get("offlinestate"), -1),
			Pager:      pager,
		})
	if err != nil {
		failOffline(w, "查询离线状态", err)
		return
	}
	httpx.OK(w, map[string]interface{}{
		"list": res.Items, "total": res.Total,
		"pageNum": pager.PageNum, "pageSize": pager.PageSize,
	})
}

func (a *app) handleOfflineTaskStatus(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 20))

	res, err := a.offline.TaskStatusList(r.Context(), auth.From(r.Context()),
		offline.MediaStatusQuery{
			TerminalID: int64(atoiDefault(q.Get("terminalId"), 0)),
			TaskID:     int64(atoiDefault(q.Get("taskId"), 0)),
			State:      atoiDefault(q.Get("offlinestate"), -1),
			Pager:      pager,
		})
	if err != nil {
		failOffline(w, "查询离线任务状态", err)
		return
	}
	httpx.OK(w, map[string]interface{}{
		"list": res.Items, "total": res.Total,
		"pageNum": pager.PageNum, "pageSize": pager.PageSize,
	})
}

func (a *app) handleOfflineSummary(w http.ResponseWriter, r *http.Request) {
	su, err := a.offline.Summary(r.Context())
	if err != nil {
		failOffline(w, "统计离线数据", err)
		return
	}
	httpx.OK(w, su)
}

// handleOfflineStates 把 12 态字典下发给前端，保证前后端用同一份（BR-215）。
func (a *app) handleOfflineStates(w http.ResponseWriter, r *http.Request) {
	out := make([]map[string]interface{}, 0, len(offline.StateText))
	for v := 0; v <= 12; v++ {
		if t, ok := offline.StateText[offline.State(v)]; ok {
			out = append(out, map[string]interface{}{
				"value": v, "text": t,
				// writable 表示这个状态允许 Web 写（BR-203）
				"writable": v == 1 || v == 2 || v == 4 || v == 5 || v == 11,
			})
		}
	}
	httpx.OK(w, out)
}

// ---------- F-46 停止传输 ----------

type offlineStopReq struct {
	MediaIDs    []int64 `json:"mediaIds"`
	TerminalIDs []int64 `json:"terminalIds"`
}

func (a *app) handleOfflineStop(w http.ResponseWriter, r *http.Request) {
	var in offlineStopReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	n, err := a.offline.Stop(r.Context(), auth.From(r.Context()), in.MediaIDs, in.TerminalIDs)
	if err != nil {
		failOffline(w, "停止离线传输", err)
		return
	}
	a.notifier.OfflineChanged(r.Context())
	httpx.OK(w, map[string]interface{}{"updated": n})
}

// ---------- F-47 清空 ----------

type offlinePurgeReq struct {
	ConfirmText string `json:"confirmText"`
}

func (a *app) handleOfflinePurge(w http.ResponseWriter, r *http.Request) {
	var in offlinePurgeReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.offline.PurgeAll(r.Context(), in.ConfirmText)
	if err != nil {
		failOffline(w, "清空离线数据", err)
		return
	}
	a.notifier.OfflineChanged(r.Context())
	httpx.OK(w, res)
}
