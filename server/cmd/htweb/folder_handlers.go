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

// 授权终端的「目录管理」（ok112 的 dirstreammanager.php?flag=2）。
//
// 目录是每台宿主终端各有一套的（terminalfolder.terminalid），用来给
// 「授权终端」那棵挑终端的树分组。语义见 terminal/folder.go。

func failFolder(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, terminal.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, "终端或目录不存在")
	case errors.Is(err, terminal.ErrNoPermission):
		httpx.Fail(w, httpx.CodeForbidden, "没有这台终端的操作权限")
	case errors.Is(err, terminal.ErrAuthPagingUnsupported):
		httpx.Fail(w, httpx.CodeBadRequest, "该终端型号不支持寻呼授权")
	case errors.Is(err, terminal.ErrFolderNameRequired),
		errors.Is(err, terminal.ErrFolderNameDuplicate),
		errors.Is(err, terminal.ErrFolderIsRoot):
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
	default:
		httpx.Internal(w, action, err)
	}
}

// handleTerminalFolderTree 读一台终端的目录树。
func (a *app) handleTerminalFolderTree(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	tree, err := a.terminals.ListTerminalFolders(r.Context(), id)
	if err != nil {
		failFolder(w, "查询目录", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"tree": tree})
}

// handleFolderTerminals 列出一个目录里的终端。
func (a *app) handleFolderTerminals(w http.ResponseWriter, r *http.Request) {
	if _, ok := pathNamedID(w, r, "id"); !ok {
		return
	}
	q := r.URL.Query()
	folderID, err := strconv.ParseInt(q.Get("folderId"), 10, 64)
	if err != nil || folderID <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "请先选择一个目录")
		return
	}
	list, err := a.terminals.FolderTerminals(r.Context(), folderID, strings.TrimSpace(q.Get("keyword")))
	if err != nil {
		failFolder(w, "查询目录终端", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"list": list})
}

// handleFolderCandidates 列出还能加进这个目录的终端。
func (a *app) handleFolderCandidates(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	q := r.URL.Query()
	folderID, err := strconv.ParseInt(q.Get("folderId"), 10, 64)
	if err != nil || folderID <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "请先选择一个目录")
		return
	}
	list, err := a.terminals.FolderCandidates(r.Context(), auth.From(r.Context()),
		id, folderID, strings.TrimSpace(q.Get("keyword")))
	if err != nil {
		failFolder(w, "查询候选终端", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"list": list})
}

type folderSaveReq struct {
	// FolderID 为 0 表示新建；> 0 表示给这个目录改名。
	FolderID int64  `json:"folderId"`
	ParentID int64  `json:"parentId"`
	Name     string `json:"name"`
}

// handleTerminalFolderSave 新建目录或给目录改名。
func (a *app) handleTerminalFolderSave(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	var in folderSaveReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	u := auth.From(r.Context())
	if in.FolderID > 0 {
		if err := a.terminals.RenameTerminalFolder(r.Context(), u, id, in.FolderID, in.Name); err != nil {
			failFolder(w, "修改目录", err)
			return
		}
		httpx.OK(w, map[string]interface{}{"folderId": in.FolderID})
		return
	}
	fid, err := a.terminals.CreateTerminalFolder(r.Context(), u, id, in.ParentID, in.Name)
	if err != nil {
		failFolder(w, "创建目录", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"folderId": fid})
}

type folderDelReq struct {
	FolderID int64 `json:"folderId"`
}

// handleTerminalFolderDelete 删一个目录（连子目录与目录里的终端关联）。
func (a *app) handleTerminalFolderDelete(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	var in folderDelReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	n, err := a.terminals.DeleteTerminalFolder(r.Context(), auth.From(r.Context()), id, in.FolderID)
	if err != nil {
		failFolder(w, "删除目录", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"deleted": n})
}

type folderTerminalsReq struct {
	FolderID    int64   `json:"folderId"`
	TerminalIDs []int64 `json:"terminalIds"`
}

// handleFolderTerminalsAdd 把若干终端放进目录。
func (a *app) handleFolderTerminalsAdd(w http.ResponseWriter, r *http.Request) {
	a.folderTerminals(w, r, true)
}

// handleFolderTerminalsRemove 把若干终端移出目录。
func (a *app) handleFolderTerminalsRemove(w http.ResponseWriter, r *http.Request) {
	a.folderTerminals(w, r, false)
}

func (a *app) folderTerminals(w http.ResponseWriter, r *http.Request, add bool) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	var in folderTerminalsReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if in.FolderID <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "请先选择一个目录")
		return
	}
	if len(in.TerminalIDs) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "请选择终端")
		return
	}
	n, err := a.terminals.SetFolderTerminals(r.Context(), auth.From(r.Context()),
		id, in.FolderID, in.TerminalIDs, add)
	if err != nil {
		action := "移出目录"
		if add {
			action = "加入目录"
		}
		failFolder(w, action, err)
		return
	}
	httpx.OK(w, map[string]interface{}{"affected": n})
}
