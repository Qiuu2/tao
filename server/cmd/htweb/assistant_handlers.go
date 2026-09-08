package main

import (
	"net/http"
	"strconv"

	"htweb/internal/assistant"
	"htweb/internal/auth"
	"htweb/internal/httpx"
)

// AI 助手的接口层。
//
// 只做参数解析与出参，业务在 internal/assistant 里 —— 与全站其它模块同一套分层。
//
// ⚠ 权限有两道：
//   路由这一道只要求登录（说话本身不需要权限）；
//   **具体意图要不要放行，在 service 里按意图对应的权限位判**（intent.go）。
//   不能在路由上一刀切，否则要么谁都能改作息方案，要么没有 bellpriv 的人
//   连查询都问不了。

func (a *app) handleAssistantChat(w http.ResponseWriter, r *http.Request) {
	var in assistant.ChatRequest
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	u := auth.From(r.Context())
	res, err := a.assist.Chat(r.Context(), u, in)
	if err != nil {
		httpx.Fail(w, httpx.CodeInternal, "助手处理失败: "+err.Error())
		return
	}
	httpx.OK(w, res)
}

// handleAssistantStatus 报告助手可不可用。
//
// 前端据此决定浮窗要不要出现、出现了要不要置灰 ——
// 不可用时**如实说明缺什么**，而不是让用户对着一个不回话的框子发呆。
func (a *app) handleAssistantStatus(w http.ResponseWriter, r *http.Request) {
	enabled, url, lastErr, lastOK := a.assist.NLUStatus(r.Context())
	out := map[string]any{
		"enabled":  a.assist.Enabled(),
		"nluReady": enabled && lastErr == "",
		"nluUrl":   url,
	}
	if lastErr != "" {
		out["reason"] = lastErr
	}
	if !lastOK.IsZero() {
		out["lastOkAt"] = lastOK.Format("2006-01-02 15:04:05")
	}
	httpx.OK(w, out)
}

// handleAssistantHistory 取当前用户的指令历史。
//
// ⚠ 只能看自己的：历史里有别人说过的话和做过的操作，
// 这是比任务列表更敏感的东西，不按 tao 的「管理员看全部」放宽。
func (a *app) handleAssistantHistory(w http.ResponseWriter, r *http.Request) {
	u := auth.From(r.Context())
	limit, _ := strconv.Atoi(r.URL.Query().Get("limit"))
	list, err := a.assist.ListMessages(r.Context(), u.ID, limit)
	if err != nil {
		httpx.Fail(w, httpx.CodeInternal, "查询助手历史失败: "+err.Error())
		return
	}
	httpx.OK(w, map[string]any{"list": list})
}

func (a *app) handleAssistantHistoryClear(w http.ResponseWriter, r *http.Request) {
	u := auth.From(r.Context())
	n, err := a.assist.ClearHistory(r.Context(), u.ID)
	if err != nil {
		httpx.Fail(w, httpx.CodeInternal, "清空助手历史失败: "+err.Error())
		return
	}
	httpx.OK(w, map[string]any{"deleted": n})
}

func (a *app) handleAssistantSettings(w http.ResponseWriter, r *http.Request) {
	u := auth.From(r.Context())
	m, err := a.assist.GetSettings(r.Context(), u.ID)
	if err != nil {
		httpx.Fail(w, httpx.CodeInternal, "查询助手设置失败: "+err.Error())
		return
	}
	httpx.OK(w, m)
}

func (a *app) handleAssistantSettingsSave(w http.ResponseWriter, r *http.Request) {
	var in struct {
		Global   bool              `json:"global"`
		Settings map[string]string `json:"settings"`
	}
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	u := auth.From(r.Context())
	// 改全局设置是管理员的事：一个人改了会影响所有人
	owner := u.ID
	if in.Global {
		if !u.IsAdmin {
			httpx.Fail(w, httpx.CodeForbidden, "只有管理员能改全局助手设置")
			return
		}
		owner = 0
	}
	for k, v := range in.Settings {
		if err := a.assist.SetSetting(r.Context(), owner, k, v); err != nil {
			httpx.Fail(w, httpx.CodeInternal, "保存助手设置失败: "+err.Error())
			return
		}
	}
	httpx.OK(w, map[string]any{"saved": len(in.Settings)})
}
