package main

import (
	"errors"
	"net/http"
	"strconv"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/notify"
	"htweb/internal/store"
	"htweb/internal/terminal"
)

// failTerminal 把终端业务错误映射成合适的响应码。
func failTerminal(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, terminal.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, err.Error())
	case errors.Is(err, terminal.ErrNoPermission):
		httpx.Fail(w, httpx.CodeForbidden, err.Error())
	default:
		// 校验类错误是用户能看懂的操作错误，不能当 500 报；
		// 其余一律走 Internal，避免把 SQL 细节漏给前端。
		if isTerminalValidationErr(err) {
			httpx.Fail(w, httpx.CodeBadRequest, err.Error())
			return
		}
		httpx.Internal(w, action, err)
	}
}

// isTerminalValidationErr 识别面向用户的校验错误。
// 与 user 模块同样的做法：按关键词匹配，宁可漏判成 500，也不能把 SQL 细节吐出去。
func isTerminalValidationErr(err error) bool {
	msg := err.Error()
	for _, kw := range []string{
		"不能为空", "最多", "必须", "格式不正确", "不存在", "过长",
		"无法表示", "未知的终端开关", "之间",
	} {
		if strings.Contains(msg, kw) {
			return true
		}
	}
	return false
}

// ---------- F-25 列表与分区树 ----------

func (a *app) handleTerminalList(w http.ResponseWriter, r *http.Request) {
	u := auth.From(r.Context())
	q := r.URL.Query()

	// groupId 允许为 0（全部）与 -1（未分区），所以不能用「必须为正整数」的写法
	groupID, err := strconv.ParseInt(q.Get("groupId"), 10, 64)
	if err != nil {
		groupID = terminal.GroupAll
	}

	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 20))
	res, err := a.terminals.List(r.Context(), u, terminal.ListQuery{
		GroupID:   groupID,
		Category:  strings.TrimSpace(q.Get("category")),
		SearchKey: q.Get("searchKey"),
		Keyword:   strings.TrimSpace(q.Get("keyword")),
		OrderBy:   q.Get("orderBy"),
		Order:     q.Get("order"),
		Pager:     pager,
	})
	if err != nil {
		failTerminal(w, "查询终端列表", err)
		return
	}
	httpx.OK(w, map[string]interface{}{
		"list":      res.Items,
		"pageNum":   pager.PageNum,
		"pageSize":  pager.PageSize,
		"total":     res.Total,
		"scopeNote": res.ScopeNote,
	})
}

func (a *app) handleTerminalGroupTree(w http.ResponseWriter, r *http.Request) {
	nodes, err := a.terminals.GroupTree(r.Context(), auth.From(r.Context()))
	if err != nil {
		failTerminal(w, "查询终端分区", err)
		return
	}
	httpx.OK(w, nodes)
}

func (a *app) handleTerminalTypes(w http.ResponseWriter, r *http.Request) {
	opts, err := a.terminals.TypeOptions(r.Context())
	if err != nil {
		failTerminal(w, "查询终端类型", err)
		return
	}
	httpx.OK(w, opts)
}

func (a *app) handleTerminalGet(w http.ResponseWriter, r *http.Request) {
	id, err := strconv.ParseInt(r.PathValue("id"), 10, 64)
	if err != nil || id <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "终端 ID 非法")
		return
	}
	d, err := a.terminals.Get(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		failTerminal(w, "查询终端", err)
		return
	}
	httpx.OK(w, d)
}

// ---------- F-27 修改 ----------

type terminalUpdateReq struct {
	TerminalName string `json:"terminalname"`
	TypeID       int    `json:"typeid"`
	GroupID      int64  `json:"groupId"`
	IP           string `json:"ip"`
	Postion      string `json:"postion"`
	Volume       int    `json:"volume"`
}

func (a *app) handleTerminalUpdate(w http.ResponseWriter, r *http.Request) {
	id, err := strconv.ParseInt(r.PathValue("id"), 10, 64)
	if err != nil || id <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "终端 ID 非法")
		return
	}
	var in terminalUpdateReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	u := auth.From(r.Context())
	if err := a.terminals.Update(r.Context(), u, id, terminal.UpdateInput{
		TerminalName: in.TerminalName, TypeID: in.TypeID, GroupID: in.GroupID,
		IP: in.IP, Postion: in.Postion, Volume: in.Volume,
	}); err != nil {
		failTerminal(w, "修改终端", err)
		return
	}
	// 修复 D-88：旧版改完不通知，后台继续用旧配置
	a.notifier.TerminalChanged(r.Context(), notify.TermStart, id)
	httpx.OK(w, map[string]interface{}{"notified": true})
}

// ---------- F-26 启停 与 F-31 状态开关 ----------

type idsReq struct {
	IDs []int64 `json:"ids"`
}

// wantIDs 解析并校验批量操作的 ID 列表。
func wantIDs(w http.ResponseWriter, ids []int64) bool {
	if len(ids) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "未选择终端")
		return false
	}
	if len(ids) > 500 {
		httpx.Fail(w, httpx.CodeBadRequest, "一次最多处理 500 台终端")
		return false
	}
	return true
}

func (a *app) handleTerminalRunning(w http.ResponseWriter, r *http.Request) {
	start := strings.HasSuffix(r.URL.Path, "/start")
	var in idsReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !wantIDs(w, in.IDs) {
		return
	}
	res, err := a.terminals.SetRunning(r.Context(), auth.From(r.Context()), in.IDs, start)
	if err != nil {
		failTerminal(w, "启停终端", err)
		return
	}
	// 逐个终端发通知（BR-145）——旧代码就是在 foreach 里一台一条
	state := notify.TermStop
	if start {
		state = notify.TermStart
	}
	a.notifier.TerminalChangedBatch(r.Context(), state, res.Succeeded)
	res.Notified = len(res.Succeeded) > 0
	httpx.OK(w, res)
}

type toggleReq struct {
	IDs []int64 `json:"ids"`
	On  *bool   `json:"on"`
}

func (a *app) handleTerminalToggle(w http.ResponseWriter, r *http.Request) {
	t := terminal.Toggle(r.PathValue("toggle"))
	var in toggleReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !wantIDs(w, in.IDs) {
		return
	}
	if in.On == nil {
		httpx.Fail(w, httpx.CodeBadRequest, "必须显式指定 on（开或关）")
		return
	}

	res, err := a.terminals.SetToggle(r.Context(), auth.From(r.Context()), in.IDs, t, *in.On)
	if err != nil {
		failTerminal(w, "设置终端开关", err)
		return
	}
	res.Notified = a.notifyToggle(r, t, *in.On, res.Succeeded)
	httpx.OK(w, res)
}

// notifyToggle 按开关类型下发对应的通知报文。
//
// 报文格式与粒度全部照旧代码的实际调用点复刻：
// 对讲 / 发言走 speech 变体且逐个发，录音走整串 ID 一次发。
// 回呼（isselectcall）旧代码**根本没发通知**——那两处 send_socket_speech
// 被注释掉了，且注释里 start 和 stop 用的是同一个 state=15，明显是复制粘贴留下的。
// 既然拿不到可信的 state 码，这里就保持旧行为只写库不下发，
// 而不是自己编一个码发给正在播音的后台服务。
func (a *app) notifyToggle(r *http.Request, t terminal.Toggle, on bool, ids []int64) bool {
	if len(ids) == 0 {
		return false
	}
	ctx := r.Context()
	switch t {
	case terminal.ToggleSpeech, terminal.ToggleSponsor:
		a.notifier.TerminalSpeech(ctx, on, ids)
		return true
	case terminal.ToggleRecord:
		state := notify.TermRecordOff
		if on {
			state = notify.TermRecordOn
		}
		a.notifier.TerminalIDList(ctx, state, ids)
		return true
	default:
		// backcall / instancy：旧版只写库
		return false
	}
}

// ---------- F-29 音量 ----------

type volumeReq struct {
	IDs    []int64 `json:"ids"`
	Volume *int    `json:"volume"`
}

func (a *app) handleTerminalVolume(w http.ResponseWriter, r *http.Request) {
	var in volumeReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !wantIDs(w, in.IDs) {
		return
	}
	if in.Volume == nil {
		httpx.Fail(w, httpx.CodeBadRequest, "必须指定音量")
		return
	}
	res, err := a.terminals.SetVolume(r.Context(), auth.From(r.Context()), in.IDs, *in.Volume)
	if err != nil {
		failTerminal(w, "设置终端音量", err)
		return
	}
	a.notifier.TerminalVolume(r.Context(), res.Succeeded, *in.Volume)
	res.Notified = len(res.Succeeded) > 0
	httpx.OK(w, res)
}

// ---------- F-30 密码 / 线路检测 / 同步时间 ----------

type passwordReq struct {
	IDs      []int64 `json:"ids"`
	Password string  `json:"password"`
}

func (a *app) handleTerminalPassword(w http.ResponseWriter, r *http.Request) {
	var in passwordReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !wantIDs(w, in.IDs) {
		return
	}
	// 报文是 pwd=<明文>&... 的纯文本 UDP，密码里出现 & 或 = 会把报文截断，
	// 后台解析到的密码就不是用户输的那个了。
	if in.Password == "" || len(in.Password) > 32 {
		httpx.Fail(w, httpx.CodeBadRequest, "终端密码长度必须在 1 ~ 32 之间")
		return
	}
	if strings.ContainsAny(in.Password, "&=?{} \t\r\n") {
		httpx.Fail(w, httpx.CodeBadRequest, "终端密码不能包含空白字符或 & = ? { } 这些会破坏下发报文的字符")
		return
	}

	// ⚠ 用 DispatchPassword 而不是 Dispatch：ok112 的 set_terminal_password.php
	//   限定 `isLCD >= 1`（密码要在终端自己的屏上输），见 terminal/caps.go。
	res, err := a.terminals.DispatchPassword(r.Context(), auth.From(r.Context()), in.IDs)
	if err != nil {
		failTerminal(w, "下发终端密码", err)
		return
	}
	// 密码是一次性把整串 ID 发出去，且 ID 用花括号包裹：id={1,2,3}
	a.notifier.TerminalPassword(r.Context(), res.Succeeded, in.Password)
	res.Notified = len(res.Succeeded) > 0
	httpx.OK(w, res)
}

func (a *app) handleTerminalCircuit(w http.ResponseWriter, r *http.Request) {
	var in idsReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !wantIDs(w, in.IDs) {
		return
	}
	res, err := a.terminals.Dispatch(r.Context(), auth.From(r.Context()), in.IDs, true)
	if err != nil {
		failTerminal(w, "线路检测", err)
		return
	}
	// 线路检测同样是花括号包裹的整串 ID
	a.notifier.TerminalBraced(r.Context(), notify.TermCircuit, res.Succeeded)
	res.Notified = len(res.Succeeded) > 0
	httpx.OK(w, res)
}

func (a *app) handleTerminalSyncTime(w http.ResponseWriter, r *http.Request) {
	var in idsReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !wantIDs(w, in.IDs) {
		return
	}
	res, err := a.terminals.Dispatch(r.Context(), auth.From(r.Context()), in.IDs, false)
	if err != nil {
		failTerminal(w, "同步终端时间", err)
		return
	}
	a.notifier.TerminalChangedBatch(r.Context(), notify.TermSyncTime, res.Succeeded)
	res.Notified = len(res.Succeeded) > 0
	httpx.OK(w, res)
}

// ---------- F-28 删除 ----------

func (a *app) handleTerminalDeletePreview(w http.ResponseWriter, r *http.Request) {
	ids, ok := parseIDList(w, r.URL.Query().Get("ids"))
	if !ok {
		return
	}
	pre, err := a.terminals.Preview(r.Context(), auth.From(r.Context()), ids)
	if err != nil {
		failTerminal(w, "预览终端删除影响", err)
		return
	}
	httpx.OK(w, pre)
}

func (a *app) handleTerminalDelete(w http.ResponseWriter, r *http.Request) {
	var in deleteReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	// 旧版是前端连弹两次确认框，服务端毫无标记（BR-150）
	if !in.Confirmed {
		httpx.Fail(w, httpx.CodeBadRequest, "缺少确认标记")
		return
	}
	if !wantIDs(w, in.IDs) {
		return
	}

	res, err := a.terminals.Delete(r.Context(), auth.From(r.Context()), in.IDs)
	if err != nil {
		failTerminal(w, "删除终端", err)
		return
	}
	// 通知必须在事务提交之后发：先发的话事务一回滚，
	// 后台已经把终端摘掉了，数据库里却还在
	a.notifier.TerminalChangedBatch(r.Context(), notify.TermDeleted, res.Deleted)
	res.Notified = len(res.Deleted) > 0
	httpx.OK(w, res)
}

type replaceReq struct {
	// SourceID 是被替换的那台终端（选中的那台）。
	SourceID int64 `json:"sourceId"`
	// TargetID 是要顶替的那个 id。前端从「终端替换」对话框里手填，
	// 与 ok112 的 copytask 弹层一致。
	TargetID int64 `json:"targetId"`
}

// handleTerminalReplace 终端替换（ok112 的 getterminalid.php）。
//
// 换了新硬件后让它接管旧记录的 id，旧 id 上的任务 / 分区 / 快捷键绑定
// 因此原样生效。业务规则与关联表迁移见 terminal/replace.go。
func (a *app) handleTerminalReplace(w http.ResponseWriter, r *http.Request) {
	var in replaceReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if in.SourceID <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "请选择要替换的终端")
		return
	}
	res, err := a.terminals.Replace(r.Context(), auth.From(r.Context()), in.SourceID, in.TargetID)
	if err != nil {
		switch {
		case errors.Is(err, terminal.ErrReplaceSame):
			httpx.Fail(w, httpx.CodeBadRequest, "目标 ID 与源终端相同")
		case errors.Is(err, terminal.ErrReplaceTypeMismatch):
			httpx.Fail(w, httpx.CodeBadRequest, "目标终端与源终端型号不同，不能替换")
		case errors.Is(err, terminal.ErrReplaceTargetOnline):
			httpx.Fail(w, httpx.CodeBadRequest, "目标终端在线，不能被替换")
		default:
			failTerminal(w, "终端替换", err)
		}
		return
	}
	httpx.OK(w, res)
}
