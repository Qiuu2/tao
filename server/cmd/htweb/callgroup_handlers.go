package main

import (
	"errors"
	"net/http"
	"strconv"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/terminal"
)

// 授权寻呼 / 授权终端（ok112 的 view_terminal_call_group.php?flag=1
// 与 dirstreammanager.php?flag=2）。
//
// 一台终端可以有**多个**寻呼分区，界面是「列表 + 添加/修改/删除/浏览」四个动作，
// 所以这里也是一组资源接口，不是一个「读改名单」的开关。
//
// ⚠ 名单是白名单，且**不配置即全放开** —— 一个分区都没有等于「可寻呼所有在线
//   终端」，不是「谁都不能呼」。语义细节见 terminal/callgroup.go。

func failCallGroup(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, terminal.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, "终端或寻呼分区不存在")
	case errors.Is(err, terminal.ErrNoPermission):
		httpx.Fail(w, httpx.CodeForbidden, "没有这台终端的操作权限")
	case errors.Is(err, terminal.ErrAuthPagingUnsupported):
		httpx.Fail(w, httpx.CodeBadRequest, "该终端型号不支持寻呼授权")
	case errors.Is(err, terminal.ErrCallGroupNameRequired),
		errors.Is(err, terminal.ErrCallGroupEmpty):
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
	default:
		httpx.Internal(w, action, err)
	}
}

// handleCallGroupList 列出一台终端的寻呼分区。
func (a *app) handleCallGroupList(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	q := r.URL.Query()
	list, err := a.terminals.ListCallGroups(r.Context(), id,
		strings.TrimSpace(q.Get("keyword")), q.Get("orderBy"))
	if err != nil {
		failCallGroup(w, "查询寻呼分区", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"list": list})
}

// handleCallGroupCandidates 列出可以加进分区的终端，前端拿它建树。
func (a *app) handleCallGroupCandidates(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	list, err := a.terminals.CallGroupCandidates(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		failCallGroup(w, "查询候选终端", err)
		return
	}
	httpx.OK(w, list)
}

// handleCallGroupGet 读一个寻呼分区的名称与成员（「浏览终端」与「修改分区」共用）。
func (a *app) handleCallGroupGet(w http.ResponseWriter, r *http.Request) {
	gid, err := strconv.ParseInt(r.PathValue("gid"), 10, 64)
	if err != nil || gid <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "分区 ID 不合法")
		return
	}
	detail, err := a.terminals.GetCallGroup(r.Context(), gid)
	if err != nil {
		failCallGroup(w, "查询寻呼分区", err)
		return
	}
	httpx.OK(w, detail)
}

type callGroupSaveReq struct {
	// GroupID 为 0 表示新增；> 0 表示修改这个分区。
	GroupID int64  `json:"groupId"`
	Name    string `json:"name"`
	// TerminalIDs 是这个分区里允许被寻呼的终端，至少一台。
	TerminalIDs []int64 `json:"terminalIds"`
}

// handleCallGroupSave 新增或修改一个寻呼分区。
//
// ⚠ 走 POST 而不是 PUT：ServeMux 的 `PUT /api/terminals/{id}/...` 已经被
//
//	toggle 那组路由占了通配位，再加会 panic。子资源的写一律用 POST。
func (a *app) handleCallGroupSave(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	var in callGroupSaveReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	gid, err := a.terminals.SaveCallGroup(r.Context(), auth.From(r.Context()),
		id, in.GroupID, in.Name, in.TerminalIDs)
	if err != nil {
		failCallGroup(w, "保存寻呼分区", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"groupId": gid})
}

type callGroupDelReq struct {
	GroupIDs []int64 `json:"groupIds"`
}

// handleCallGroupDelete 删除若干寻呼分区。
func (a *app) handleCallGroupDelete(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	var in callGroupDelReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if len(in.GroupIDs) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "请选择要删除的分区")
		return
	}
	n, err := a.terminals.DeleteCallGroups(r.Context(), auth.From(r.Context()), id, in.GroupIDs)
	if err != nil {
		failCallGroup(w, "删除寻呼分区", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"deleted": n})
}
