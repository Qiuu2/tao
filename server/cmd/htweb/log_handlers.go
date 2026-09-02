package main

import (
	"errors"
	"net/http"
	"strconv"
	"strings"

	"htweb/internal/audit"
	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/logs"
	"htweb/internal/store"
)

// 日志模块 F-54 / F-55。
//
// 访问控制：**仅超级管理员**（BR-246）。
// 旧版权限不足时输出一段硬编码中文 HTML（还用了 IE 专有的 expression() CSS）
// 然后 exit（D-204）；这里回标准的 403 业务码。

func failLog(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, logs.ErrTaskLogDisabled):
		httpx.Fail(w, httpx.CodeNotFound, err.Error())
	default:
		if isLogValidationErr(err) {
			httpx.Fail(w, httpx.CodeBadRequest, err.Error())
			return
		}
		httpx.Internal(w, action, err)
	}
}

func isLogValidationErr(err error) bool {
	msg := err.Error()
	for _, kw := range []string{
		"格式不正确", "只能是", "必须", "之间", "不合法", "不存在", "不在允许",
	} {
		if strings.Contains(msg, kw) {
			return true
		}
	}
	return false
}

// requireSuper 把接口限制给超级管理员（id = 1）。
//
// 旧版用的是 `$_SESSION['username'] == "admin"` 字符串比较（D-167 同型）——
// 把账号改个名就绕过去了。这里按 id 判定。
func (a *app) requireSuper(h http.HandlerFunc) http.HandlerFunc {
	return a.authMgr.Require(a.superGate(h))
}

// requireSuperAllowQueryToken 与上面同权限，但额外接受 ?token=。
// 备份包下载走的是浏览器直接跳转（<a href> / window.open），带不上自定义请求头，
// 与媒体试听那两个路由同一个理由。
func (a *app) requireSuperAllowQueryToken(h http.HandlerFunc) http.HandlerFunc {
	return a.authMgr.RequireAllowQueryToken(a.superGate(h))
}

func (a *app) superGate(h http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		u := auth.From(r.Context())
		if u == nil || u.ID != 1 {
			httpx.Fail(w, httpx.CodeForbidden, "该功能只有超级管理员可以使用")
			return
		}
		h(w, r)
	}
}

// ---------- F-54 操作日志 ----------

func (a *app) handleLogList(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 20))

	res, err := a.logs.List(r.Context(), logs.Query{
		SearchKey: q.Get("searchKey"),
		Keyword:   strings.TrimSpace(q.Get("keyword")),
		StartTime: strings.TrimSpace(q.Get("startTime")),
		EndTime:   strings.TrimSpace(q.Get("endTime")),
		OrderBy:   q.Get("orderBy"),
		Order:     q.Get("order"),
		Pager:     pager,
	})
	if err != nil {
		failLog(w, "查询操作日志", err)
		return
	}
	httpx.OK(w, map[string]interface{}{
		"list":     res.Items,
		"pageNum":  pager.PageNum,
		"pageSize": pager.PageSize,
		"total":    res.Total,
	})
}

func (a *app) handleLogStats(w http.ResponseWriter, r *http.Request) {
	st, err := a.logs.Stats(r.Context())
	if err != nil {
		failLog(w, "统计日志概览", err)
		return
	}
	httpx.OK(w, st)
}

type logClearReq struct {
	Mode       string `json:"mode"`
	BeforeDate string `json:"beforeDate"`
	KeepDays   int    `json:"keepDays"`
	Confirmed  bool   `json:"confirmed"`
}

func (a *app) handleLogClear(w http.ResponseWriter, r *http.Request) {
	var in logClearReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !in.Confirmed {
		httpx.Fail(w, httpx.CodeBadRequest, "清理未确认")
		return
	}
	u := auth.From(r.Context())
	res, err := a.logs.Clear(r.Context(), u.Username, audit.ClientIP(r), logs.ClearInput{
		Mode:       logs.ClearMode(in.Mode),
		BeforeDate: in.BeforeDate,
		KeepDays:   in.KeepDays,
	})
	if err != nil {
		failLog(w, "清理操作日志", err)
		return
	}
	httpx.OK(w, res)
}

// ---------- F-55 任务日志 ----------

func (a *app) handleTaskLogFiles(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	res, err := a.taskLogs.ListFiles(r.Context(),
		strings.TrimSpace(q.Get("from")), strings.TrimSpace(q.Get("to")))
	if err != nil {
		failLog(w, "查询任务日志文件", err)
		return
	}
	httpx.OK(w, res)
}

func (a *app) handleTaskLogRead(w http.ResponseWriter, r *http.Request) {
	name := r.PathValue("name")
	tail, _ := strconv.ParseInt(r.URL.Query().Get("tailBytes"), 10, 64)
	res, err := a.taskLogs.ReadFile(r.Context(), name, tail)
	if err != nil {
		failLog(w, "读取任务日志", err)
		return
	}
	httpx.OK(w, res)
}

type taskLogDeleteReq struct {
	Mode       string `json:"mode"`
	BeforeDate string `json:"beforeDate"`
	KeepDays   int    `json:"keepDays"`
	Confirmed  bool   `json:"confirmed"`
}

func (t taskLogDeleteReq) toInput() logs.TaskLogDeleteInput {
	return logs.TaskLogDeleteInput{
		Mode:       logs.ClearMode(t.Mode),
		BeforeDate: t.BeforeDate,
		KeepDays:   t.KeepDays,
	}
}

func (a *app) handleTaskLogDeletePreview(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	in := taskLogDeleteReq{
		Mode:       q.Get("mode"),
		BeforeDate: q.Get("beforeDate"),
		KeepDays:   atoiDefault(q.Get("keepDays"), 0),
	}
	res, err := a.taskLogs.PreviewDelete(r.Context(), in.toInput())
	if err != nil {
		failLog(w, "预览任务日志清理", err)
		return
	}
	httpx.OK(w, res)
}

func (a *app) handleTaskLogDelete(w http.ResponseWriter, r *http.Request) {
	var in taskLogDeleteReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !in.Confirmed {
		httpx.Fail(w, httpx.CodeBadRequest, "清理未确认")
		return
	}
	res, err := a.taskLogs.Delete(r.Context(), in.toInput())
	if err != nil {
		failLog(w, "清理任务日志", err)
		return
	}
	httpx.OK(w, res)
}
