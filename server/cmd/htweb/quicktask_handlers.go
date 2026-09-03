package main

import (
	"errors"
	"net/http"
	"strconv"

	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/terminal"
)

// 快捷任务（ok112 的 view_quickplay / setquickplay / modifyquickplay）。
//
// 「在这台终端上按这个键，执行那条任务」。数据落在 terminalkeymaptask 一张表上，
// 主键 (keyid, terminalid) —— 一台终端上一个键只能绑一条任务，所以「改绑」
// 就是覆盖同一行。业务规则全在 terminal/quicktask.go，这里只做 HTTP 转接。
//
// ⚠ 与快捷键寻呼不是一套结构，别把 keyid 当成 terminalkey.id 去 JOIN，
//   它存的是键值本身。详见 quicktask.go 顶部的注释。

func failQuickTask(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, terminal.ErrQuickTaskNotFound):
		httpx.Fail(w, httpx.CodeNotFound, "快捷任务绑定不存在")
	case errors.Is(err, terminal.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, "终端不存在")
	case errors.Is(err, terminal.ErrNoPermission):
		httpx.Fail(w, httpx.CodeForbidden, "没有这台终端的操作权限")
	// 这两类都是「这台终端做不了」而非服务端出错，原样把话说给用户，
	// 不要压成一句笼统的提示，更不能落成 500。
	case errors.Is(err, terminal.ErrKeyValueBad), errors.Is(err, terminal.ErrQuickTaskUnsupported):
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
	default:
		httpx.Internal(w, action, err)
	}
}

// handleQuickTaskList 列出一台终端上的快捷任务绑定。
func (a *app) handleQuickTaskList(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	list, err := a.terminals.ListQuickTasks(r.Context(), id)
	if err != nil {
		failQuickTask(w, "查询快捷任务", err)
		return
	}
	httpx.OK(w, list)
}

// handleQuickTaskOptions 列出可绑的任务，供下拉选择。
func (a *app) handleQuickTaskOptions(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	opts, err := a.terminals.QuickTaskOptions(r.Context(), auth.From(r.Context()),
		id, r.URL.Query().Get("keyword"))
	if err != nil {
		failQuickTask(w, "查询可绑任务", err)
		return
	}
	httpx.OK(w, opts)
}

type quickTaskReq struct {
	Key    int   `json:"key"`
	TaskID int64 `json:"taskId"`
}

// handleQuickTaskSet 绑定或改绑一条快捷任务。
func (a *app) handleQuickTaskSet(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	var in quickTaskReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if in.TaskID <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "请选择要绑定的任务")
		return
	}
	if err := a.terminals.SetQuickTask(r.Context(), auth.From(r.Context()), id, in.Key, in.TaskID); err != nil {
		failQuickTask(w, "设置快捷任务", err)
		return
	}
	httpx.OK(w, nil)
}

// handleQuickTaskDelete 解除一条绑定。
//
// 键值走查询串而不是路径段：它不是资源 id，而是 (terminalid, key) 复合主键的一半，
// 放进路径会让人误以为存在一个独立的 /quick-tasks/{id} 资源。
func (a *app) handleQuickTaskDelete(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	key, err := strconv.Atoi(r.URL.Query().Get("key"))
	if err != nil {
		httpx.Fail(w, httpx.CodeBadRequest, "键值不合法")
		return
	}
	if err := a.terminals.DeleteQuickTask(r.Context(), auth.From(r.Context()), id, key); err != nil {
		failQuickTask(w, "删除快捷任务", err)
		return
	}
	httpx.OK(w, nil)
}
