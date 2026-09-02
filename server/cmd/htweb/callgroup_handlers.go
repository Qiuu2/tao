package main

import (
	"errors"
	"net/http"

	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/terminal"
)

// 授权寻呼 / 授权终端（ok112 的 view_terminal_call_group.php?flag=1
// 与 dirstreammanager.php?flag=2）。
//
// 两个菜单项落到库里是同一份名单，区别只在挑终端的界面：
// 「授权寻呼」按终端列表挑，「授权终端」按分区树挑。所以这里只有一套接口。
//
// ⚠ 名单是白名单，且**不配置即全放开** —— 空名单等于「可寻呼所有在线终端」，
//   不是「谁都不能呼」。语义细节见 terminal/callgroup.go。

func failCallGroup(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, terminal.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, "终端不存在")
	case errors.Is(err, terminal.ErrNoPermission):
		httpx.Fail(w, httpx.CodeForbidden, "没有这台终端的操作权限")
	case errors.Is(err, terminal.ErrAuthPagingUnsupported):
		httpx.Fail(w, httpx.CodeBadRequest, "该终端型号不支持寻呼授权")
	default:
		httpx.Internal(w, action, err)
	}
}

// handleCallGroupGet 读一台终端的寻呼授权名单。
func (a *app) handleCallGroupGet(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	info, err := a.terminals.ListCallGroup(r.Context(), id)
	if err != nil {
		failCallGroup(w, "查询寻呼授权", err)
		return
	}
	httpx.OK(w, info)
}

type callGroupReq struct {
	Name string `json:"name"`
	// TerminalIDs 是被授权可以寻呼的终端。传空数组表示取消授权，
	// 回到「可寻呼所有在线终端」的默认状态。
	TerminalIDs []int64 `json:"terminalIds"`
}

// handleCallGroupSet 覆盖式地写一台终端的寻呼授权名单。
func (a *app) handleCallGroupSet(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	var in callGroupReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.terminals.SetCallGroup(r.Context(), auth.From(r.Context()),
		id, in.Name, in.TerminalIDs); err != nil {
		failCallGroup(w, "设置寻呼授权", err)
		return
	}
	httpx.OK(w, nil)
}
