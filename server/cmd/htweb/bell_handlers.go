package main

import (
	"errors"
	"net/http"
	"strconv"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/bell"
	"htweb/internal/httpx"
	"htweb/internal/notify"
	"htweb/internal/store"
	"htweb/internal/task"
)

// 作息方案（打铃）接口 F-48 ~ F-52。
//
// # 为什么方案名走 query / body 而不是路径段
//
// 方案的业务主键是 `task.info` —— 一个用户随手起的中文名字，
// 完全可能包含 `/`。放进路径段会被 net/http 的 mux 当成新的一层，
// 路由直接匹配不上，而且这类问题只在「某个用户恰好这么起名」时才暴露。
// 所以读用 `?plan=`、写放 body，路径里只出现固定字面量和数字 ID。

func failBell(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, bell.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, err.Error())
	case errors.Is(err, bell.ErrNoPermission):
		httpx.Fail(w, httpx.CodeForbidden, err.Error())
	default:
		if isBellValidationErr(err) {
			httpx.Fail(w, httpx.CodeBadRequest, err.Error())
			return
		}
		httpx.Internal(w, action, err)
	}
}

// isBellValidationErr 识别面向用户的校验错误。
// 关键词表包含「只能是」—— task 模块就是漏了它，把六条枚举校验全判成了 500（N-08）。
func isBellValidationErr(err error) bool {
	msg := err.Error()
	for _, kw := range []string{
		"不能为空", "最多", "必须", "格式不正确", "不存在", "过长", "之间", "只能是",
		"已存在", "不允许", "请选择", "请至少", "重复", "重名", "不支持", "重新选择",
		"未绑定", "不能早于", "不能包含", "不能与",
	} {
		if strings.Contains(msg, kw) {
			return true
		}
	}
	return false
}

// planParam 从 query 取方案名。
func planParam(w http.ResponseWriter, r *http.Request) (string, bool) {
	name := strings.TrimSpace(r.URL.Query().Get("plan"))
	if name == "" {
		httpx.Fail(w, httpx.CodeBadRequest, "缺少方案名称")
		return "", false
	}
	return name, true
}

func bellItemID(w http.ResponseWriter, r *http.Request) (int64, bool) {
	id, err := strconv.ParseInt(r.PathValue("id"), 10, 64)
	if err != nil || id <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "条目 ID 不合法")
		return 0, false
	}
	return id, true
}

// ---------- F-48 列表 ----------

func (a *app) handleBellPlanList(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))

	res, err := a.bells.List(r.Context(), auth.From(r.Context()), bell.ListQuery{
		Keyword: strings.TrimSpace(q.Get("keyword")),
		OrderBy: q.Get("orderBy"),
		Order:   q.Get("order"),
		Pager:   pager,
	})
	if err != nil {
		failBell(w, "查询作息方案", err)
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

func (a *app) handleBellPlanGet(w http.ResponseWriter, r *http.Request) {
	name, ok := planParam(w, r)
	if !ok {
		return
	}
	d, err := a.bells.Get(r.Context(), auth.From(r.Context()), name)
	if err != nil {
		failBell(w, "查询作息方案", err)
		return
	}
	httpx.OK(w, d)
}

// ---------- F-49 新增 / 修改 ----------

type bellPlanReq struct {
	PlanName    string             `json:"planName"`
	NewPlanName string             `json:"newPlanName"`
	Schedule    bell.Schedule      `json:"schedule"`
	Playback    bell.Playback      `json:"playback"`
	Terminals   []task.TerminalRef `json:"terminals"`
	Items       []bell.ItemInput   `json:"items"`
	// ApplyTerminals 只在修改时有意义：false 表示这次不动终端清单。
	ApplyTerminals bool `json:"applyTerminals"`
}

func (a *app) handleBellPlanCreate(w http.ResponseWriter, r *http.Request) {
	var in bellPlanReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.bells.Create(r.Context(), auth.From(r.Context()), bell.PlanInput{
		PlanName:  in.PlanName,
		Schedule:  in.Schedule,
		Playback:  in.Playback,
		Terminals: in.Terminals,
		Items:     in.Items,
	})
	if err != nil {
		failBell(w, "新建作息方案", err)
		return
	}
	// 每个新条目一条 state=4 通知，与旧 belltaskaloneoperation 一致
	for _, id := range res.NotifyTaskIDs {
		a.notifier.TaskSaved(r.Context(), notify.TaskAdded, id, in.Playback.Volume)
	}
	httpx.OK(w, res)
}

func (a *app) handleBellPlanUpdate(w http.ResponseWriter, r *http.Request) {
	var in bellPlanReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.bells.Update(r.Context(), auth.From(r.Context()), bell.UpdateInput{
		PlanName:       in.PlanName,
		NewPlanName:    in.NewPlanName,
		Schedule:       in.Schedule,
		Playback:       in.Playback,
		Terminals:      in.Terminals,
		ApplyTerminals: in.ApplyTerminals,
	})
	if err != nil {
		failBell(w, "修改作息方案", err)
		return
	}
	for _, id := range res.TaskIDs {
		a.notifier.TaskSaved(r.Context(), notify.TaskUpdated, id, in.Playback.Volume)
	}
	httpx.OK(w, res)
}

type bellItemReq struct {
	PlanName string         `json:"planName"`
	Item     bell.ItemInput `json:"item"`
}

func (a *app) handleBellItemAdd(w http.ResponseWriter, r *http.Request) {
	var in bellItemReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.bells.AddItem(r.Context(), auth.From(r.Context()), in.PlanName, in.Item)
	if err != nil {
		failBell(w, "新增打铃条目", err)
		return
	}
	// volume 取方案自己的 defaultvolume：报文里带 0 会让后台服务把音量设成 0，
	// 任务照常触发但一点声音都没有。
	for _, id := range res.NotifyTaskIDs {
		a.notifier.TaskSaved(r.Context(), notify.TaskAdded, id, res.Volume)
	}
	httpx.OK(w, res)
}

func (a *app) handleBellItemUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := bellItemID(w, r)
	if !ok {
		return
	}
	var in bellItemReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	volume, err := a.bells.UpdateItem(r.Context(), auth.From(r.Context()), in.PlanName, id, in.Item)
	if err != nil {
		failBell(w, "修改打铃条目", err)
		return
	}
	a.notifier.TaskSaved(r.Context(), notify.TaskUpdated, id, volume)
	httpx.OK(w, map[string]interface{}{"taskid": id})
}

type bellItemDeleteReq struct {
	PlanName string  `json:"planName"`
	IDs      []int64 `json:"ids"`
}

func (a *app) handleBellItemDelete(w http.ResponseWriter, r *http.Request) {
	var in bellItemDeleteReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	deleted, planGone, err := a.bells.DeleteItems(r.Context(), auth.From(r.Context()), in.PlanName, in.IDs)
	if err != nil {
		failBell(w, "删除打铃条目", err)
		return
	}
	// 按实际删掉的 id 发通知，含连带删掉的功放子任务
	a.notifier.BellTasksDeleted(r.Context(), deleted)
	httpx.OK(w, map[string]interface{}{
		"deleted": len(deleted), "deletedTasks": deleted, "planRemoved": planGone,
	})
}

type bellItemSchedReq struct {
	PlanName  string  `json:"planName"`
	IDs       []int64 `json:"ids"`
	StartDate string  `json:"startdate"`
	EndDate   string  `json:"enddate"`
	ExeModel  string  `json:"exemodel"`
}

// handleBellItemSchedule 把勾中的条目挪到新的日期时间段并改执行星期（列表页的「智能排课」）。
func (a *app) handleBellItemSchedule(w http.ResponseWriter, r *http.Request) {
	var in bellItemSchedReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	changed, volume, err := a.bells.SetItemSchedule(
		r.Context(), auth.From(r.Context()), in.PlanName, in.IDs, in.StartDate, in.EndDate, in.ExeModel)
	if err != nil {
		failBell(w, "修改条目排期", err)
		return
	}
	// 日期变了也要通知后台服务重新装载，否则它还按旧日期跑
	for _, id := range changed {
		a.notifier.TaskSaved(r.Context(), notify.TaskUpdated, id, volume)
	}
	httpx.OK(w, map[string]interface{}{
		"planName": in.PlanName, "changed": len(in.IDs), "changedTasks": changed,
		"startdate": in.StartDate, "enddate": in.EndDate, "exemodel": in.ExeModel,
	})
}

// ---------- F-50 启停 ----------

type bellStateReq struct {
	PlanName string `json:"planName"`
	Enable   bool   `json:"enable"`
}

func (a *app) handleBellPlanState(w http.ResponseWriter, r *http.Request) {
	var in bellStateReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.bells.SetState(r.Context(), auth.From(r.Context()), in.PlanName, in.Enable)
	if err != nil {
		failBell(w, "启停作息方案", err)
		return
	}
	state := notify.PlanDisabled
	if in.Enable {
		state = notify.PlanEnabled
	}
	a.notifier.PlanChanged(r.Context(), state, res.PlanName)
	httpx.OK(w, map[string]interface{}{
		"planName":          res.PlanName,
		"enable":            res.Enable,
		"affectedTasks":     res.AffectedTasks,
		"offlineStateReset": res.OfflineStateReset,
		"notified":          true,
	})
}

// ---------- 调整音量（:80 作息方案页的同名按钮）----------

type bellVolumeReq struct {
	PlanName string `json:"planName"`
	Volume   int    `json:"volume"`
}

func (a *app) handleBellPlanVolume(w http.ResponseWriter, r *http.Request) {
	var in bellVolumeReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.bells.SetVolume(r.Context(), auth.From(r.Context()), in.PlanName, in.Volume)
	if err != nil {
		failBell(w, "调整方案音量", err)
		return
	}
	httpx.OK(w, res)
}

// ---------- F-51 删除 ----------

func (a *app) handleBellPlanDeletePreview(w http.ResponseWriter, r *http.Request) {
	name, ok := planParam(w, r)
	if !ok {
		return
	}
	imp, err := a.bells.Preview(r.Context(), auth.From(r.Context()), name)
	if err != nil {
		failBell(w, "预览删除影响", err)
		return
	}
	httpx.OK(w, imp)
}

type bellDeleteReq struct {
	PlanName  string `json:"planName"`
	Confirmed bool   `json:"confirmed"`
}

func (a *app) handleBellPlanDelete(w http.ResponseWriter, r *http.Request) {
	var in bellDeleteReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !in.Confirmed {
		httpx.Fail(w, httpx.CodeBadRequest, "删除未确认")
		return
	}
	res, err := a.bells.Delete(r.Context(), auth.From(r.Context()), in.PlanName)
	if err != nil {
		failBell(w, "删除作息方案", err)
		return
	}
	// 按**实际删掉的**每个 taskid 发，而不是像旧版那样只发用户勾选的那几个
	a.notifier.BellTasksDeleted(r.Context(), res.DeletedTasks)
	httpx.OK(w, res)
}

// ---------- F-52 复制 ----------

type bellCopyReq struct {
	PlanName    string `json:"planName"`
	NewPlanName string `json:"newPlanName"`
}

func (a *app) handleBellPlanCopy(w http.ResponseWriter, r *http.Request) {
	var in bellCopyReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.bells.Copy(r.Context(), auth.From(r.Context()), in.PlanName, in.NewPlanName)
	if err != nil {
		failBell(w, "复制作息方案", err)
		return
	}
	for _, id := range res.NewTaskIDs {
		a.notifier.TaskSaved(r.Context(), notify.TaskAdded, id, res.Volume)
	}
	httpx.OK(w, res)
}
