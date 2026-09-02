package main

import (
	"errors"
	"net/http"
	"strconv"

	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/terminal"
)

// 快捷键寻呼 F-.. （ok112 的 setterminalkeyoption / view_terminal_shotcut_mapping）。
//
// 「在这台终端上按这个键，去寻呼那些终端」。数据落在 terminalkey + terminalkeymap，
// 键值的可选集按终端类型算，详见 terminal/keyspec.go 与 terminal/shortcut.go。

func failShortcut(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, terminal.ErrKeyNotFound):
		httpx.Fail(w, httpx.CodeNotFound, err.Error())
	case errors.Is(err, terminal.ErrKeyValueBad), errors.Is(err, terminal.ErrKeyDuplicate):
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
	default:
		failTerminal(w, action, err)
	}
}

// pathNamedID 取路径里指定名字的数字参数。
//
// ⚠ 不要叫 pathID —— user_handlers.go 里已经有一个同名的（只认 {id}，
//   并且自己写错误响应）。这里的路由用了 {keyId}，需要按名字取。
func pathNamedID(w http.ResponseWriter, r *http.Request, name string) (int64, bool) {
	v, err := strconv.ParseInt(r.PathValue(name), 10, 64)
	if err != nil || v <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, name+" 不合法")
		return 0, false
	}
	return v, true
}

// emergencyFlag 读 ?emergency=1。不带参数时返回 nil（两种都要）。
func emergencyFlag(r *http.Request) *bool {
	raw := r.URL.Query().Get("emergency")
	if raw == "" {
		return nil
	}
	v := raw == "1" || raw == "true"
	return &v
}

// handleShortcutKeyOptions 返回某台终端可选的键值。
//
// ⚠ 急救与普通快捷键的可选集不同（比如类型 44：急救是 1..3，快捷键是 1..9），
// 所以 emergency 必须由调用方明确指定，默认按普通快捷键算。
func (a *app) handleShortcutKeyOptions(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	em := false
	if f := emergencyFlag(r); f != nil {
		em = *f
	}
	opts, err := a.terminals.KeyOptionsFor(r.Context(), id, em)
	if err != nil {
		failShortcut(w, "查询可选键值", err)
		return
	}
	httpx.OK(w, opts)
}

func (a *app) handleShortcutKeyList(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	list, err := a.terminals.ListShortcutKeys(r.Context(), id, emergencyFlag(r))
	if err != nil {
		failShortcut(w, "查询快捷键", err)
		return
	}
	httpx.OK(w, list)
}

type shortcutReq struct {
	Name      string  `json:"name"`
	Key       int     `json:"key"`
	Emergency bool    `json:"emergency"`
	TargetIDs []int64 `json:"targetIds"`
	Area      string  `json:"area"`
}

func (in shortcutReq) toInput() terminal.ShortcutInput {
	return terminal.ShortcutInput{
		Name: in.Name, Key: in.Key, Emergency: in.Emergency,
		TargetIDs: in.TargetIDs, Area: in.Area,
	}
}

func (a *app) handleShortcutKeyCreate(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	var in shortcutReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	keyID, err := a.terminals.CreateShortcutKey(r.Context(), auth.From(r.Context()), id, in.toInput())
	if err != nil {
		failShortcut(w, "新建快捷键", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": keyID})
}

func (a *app) handleShortcutKeyUpdate(w http.ResponseWriter, r *http.Request) {
	keyID, ok := pathNamedID(w, r, "keyId")
	if !ok {
		return
	}
	var in shortcutReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.terminals.UpdateShortcutKey(r.Context(), auth.From(r.Context()), keyID, in.toInput()); err != nil {
		failShortcut(w, "修改快捷键", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"updated": true})
}

type shortcutDeleteReq struct {
	IDs []int64 `json:"ids"`
}

func (a *app) handleShortcutKeyDelete(w http.ResponseWriter, r *http.Request) {
	var in shortcutDeleteReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !wantIDs(w, in.IDs) {
		return
	}
	n, err := a.terminals.DeleteShortcutKeys(r.Context(), auth.From(r.Context()), in.IDs)
	if err != nil {
		failShortcut(w, "删除快捷键", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"deleted": n})
}
