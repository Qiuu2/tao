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
	"htweb/internal/task"
)

// failTask 把任务业务错误映射成合适的响应码。
func failTask(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, task.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, err.Error())
	case errors.Is(err, task.ErrNoPermission), errors.Is(err, task.ErrFolderDenied):
		httpx.Fail(w, httpx.CodeForbidden, err.Error())
	default:
		if isTaskValidationErr(err) {
			httpx.Fail(w, httpx.CodeBadRequest, err.Error())
			return
		}
		httpx.Internal(w, action, err)
	}
}

// isTaskValidationErr 识别面向用户的校验错误。
// 与 user / terminal 模块一致：按关键词匹配，宁可漏判成 500，也不把 SQL 细节吐给前端。
func isTaskValidationErr(err error) bool {
	msg := err.Error()
	for _, kw := range []string{
		"不能为空", "最多", "必须", "格式不正确", "不存在", "过长", "之间",
		"已存在", "不允许", "请选择", "重复", "不支持", "重新选择", "未绑定",
		"不能早于", "不能为负",
		// 「只能是 X 或 Y」这一类枚举校验漏在网外过：现网实测新建任务时
		// 「间隔播放模式只能是 1（时间）或 2（循环）」被判成 500，
		// 用户只看到「服务器内部错误」，真正的原因只写进了服务端日志。
		// 覆盖 6 条消息：本地优先播放 / 播放方式 / 发送模式 / 方案状态 /
		// 间隔播放模式 / 时长类型。
		"只能是",
	} {
		if strings.Contains(msg, kw) {
			return true
		}
	}
	return false
}

func taskIDFromPath(w http.ResponseWriter, r *http.Request) (int64, bool) {
	id, err := strconv.ParseInt(r.PathValue("id"), 10, 64)
	if err != nil || id <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "任务 ID 不合法")
		return 0, false
	}
	return id, true
}

// wantTaskIDs 校验批量操作的 ID 列表。
func wantTaskIDs(w http.ResponseWriter, ids []int64) bool {
	if len(ids) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "未选择任务")
		return false
	}
	if len(ids) > 500 {
		httpx.Fail(w, httpx.CodeBadRequest, "一次最多处理 500 条任务")
		return false
	}
	return true
}

// ---------- F-32 列表与分组树 ----------

func (a *app) handleTaskList(w http.ResponseWriter, r *http.Request) {
	u := auth.From(r.Context())
	q := r.URL.Query()

	folderID, _ := strconv.ParseInt(q.Get("folderId"), 10, 64)
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))

	// withDetails 默认开。关掉可以省两条批量查询，但列表就没有媒体/终端清单了。
	withDetails := q.Get("withDetails") != "false"

	res, err := a.tasks.List(r.Context(), u, task.ListQuery{
		FolderID:    folderID,
		SearchKey:   q.Get("searchKey"),
		Keyword:     strings.TrimSpace(q.Get("keyword")),
		OrderBy:     q.Get("orderBy"),
		Order:       q.Get("order"),
		Pager:       pager,
		WithDetails: withDetails,
	})
	if err != nil {
		failTask(w, "查询任务列表", err)
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

func (a *app) handleTaskFolderTree(w http.ResponseWriter, r *http.Request) {
	nodes, err := a.tasks.FolderTree(r.Context(), auth.From(r.Context()))
	if err != nil {
		failTask(w, "查询任务分组树", err)
		return
	}
	httpx.OK(w, nodes)
}

// ---------- F-36 分组增删改 ----------

type taskFolderReq struct {
	Name     string `json:"name"`
	ParentID int64  `json:"parentId"`
}

func (a *app) handleTaskFolderCreate(w http.ResponseWriter, r *http.Request) {
	var in taskFolderReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	id, err := a.tasks.CreateFolder(r.Context(), auth.From(r.Context()), in.Name, in.ParentID)
	if err != nil {
		failTask(w, "新建任务分组", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleTaskFolderUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := taskIDFromPath(w, r)
	if !ok {
		return
	}
	var in taskFolderReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.tasks.RenameFolder(r.Context(), auth.From(r.Context()), id, in.Name); err != nil {
		failTask(w, "修改任务分组", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleTaskFolderDelete(w http.ResponseWriter, r *http.Request) {
	id, ok := taskIDFromPath(w, r)
	if !ok {
		return
	}
	res, err := a.tasks.DeleteFolder(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		failTask(w, "删除任务分组", err)
		return
	}
	// 分组连带删掉的任务同样要通知后台服务，否则它会继续按旧清单调度
	all := append(append([]int64{}, res.DeletedTasks...), res.DeletedSubs...)
	if len(all) > 0 {
		a.notifier.TaskChanged(r.Context(), notify.TaskDeleted, all)
	}
	httpx.OK(w, res)
}

// ---------- F-33 新增 / 修改 ----------

// taskReq 是新增 / 修改的请求体。
//
// 分成 schedule / playback / power 三段，与界面上的分组一致；
// 媒体和终端是对象数组而不是逗号串（修 D-107）。
type taskReq struct {
	TaskName     string             `json:"taskname"`
	FolderID     int64              `json:"folderId"`
	ProjectState int                `json:"projectstate"`
	IsRandomPlay int                `json:"israndomplay"`
	Media        []task.MediaRef    `json:"media"`
	Terminals    []task.TerminalRef `json:"terminals"`
	Schedule     struct {
		StartDate  string `json:"startdate"`
		EndDate    string `json:"enddate"`
		PlayTime   string `json:"playtime"`
		EndTime    string `json:"endtime"`
		ExeModel   string `json:"exemodel"`
		DisableDay string `json:"disableday"`
	} `json:"schedule"`
	Playback struct {
		TimeLengthTy int `json:"timelengthtype"`
		TimeLength   int `json:"timelength"`
		IntervalS    int `json:"interval_s"`
		IntPlayLen   int `json:"intplaylength"`
		IntPlayLenTy int `json:"intplaylengthtype"`
		Volume       int `json:"defaultvolume"`
		Priority     int `json:"priority"`
		LocalPlay    int `json:"localplay"`
	} `json:"playback"`
	Power struct {
		PrePower     int `json:"prepower"`
		DataSendMode int `json:"datasendmodel"`
	} `json:"power"`
	// LED 是 :80 表单里的「led播放 / Led字幕 / Led速度」。
	// 传 null 或字幕留空 = 不上屏（已有的 LED 子任务会被删掉）。
	LED *task.LEDSub `json:"led"`
}

func (t taskReq) toInput() task.Input {
	return task.Input{
		TaskName:     t.TaskName,
		FolderID:     t.FolderID,
		ProjectState: t.ProjectState,
		IsRandomPlay: t.IsRandomPlay,
		StartDate:    t.Schedule.StartDate,
		EndDate:      t.Schedule.EndDate,
		PlayTime:     t.Schedule.PlayTime,
		EndTime:      t.Schedule.EndTime,
		ExeModel:     t.Schedule.ExeModel,
		DisableDay:   t.Schedule.DisableDay,
		TimeLengthTy: t.Playback.TimeLengthTy,
		TimeLength:   t.Playback.TimeLength,
		IntervalS:    t.Playback.IntervalS,
		IntPlayLen:   t.Playback.IntPlayLen,
		IntPlayLenTy: t.Playback.IntPlayLenTy,
		Volume:       t.Playback.Volume,
		Priority:     t.Playback.Priority,
		LocalPlay:    t.Playback.LocalPlay,
		PrePower:     t.Power.PrePower,
		DataSendMode: t.Power.DataSendMode,
		LED:          t.LED,
		Media:        t.Media,
		Terminals:    t.Terminals,
	}
}

func (a *app) handleTaskGet(w http.ResponseWriter, r *http.Request) {
	id, ok := taskIDFromPath(w, r)
	if !ok {
		return
	}
	d, err := a.tasks.Get(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		failTask(w, "查询任务", err)
		return
	}
	httpx.OK(w, d)
}

func (a *app) handleTaskCreate(w http.ResponseWriter, r *http.Request) {
	var in taskReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.tasks.Create(r.Context(), auth.From(r.Context()), in.toInput())
	if err != nil {
		failTask(w, "新建任务", err)
		return
	}
	// 新增走 state=4 且必须带 volume —— 后台服务据此加载任务并设初始音量
	a.notifier.TaskSaved(r.Context(), notify.TaskAdded, res.TaskID, res.Volume)
	httpx.OK(w, res)
}

func (a *app) handleTaskUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := taskIDFromPath(w, r)
	if !ok {
		return
	}
	var in taskReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.tasks.Update(r.Context(), auth.From(r.Context()), id, in.toInput())
	if err != nil {
		failTask(w, "修改任务", err)
		return
	}
	a.notifier.TaskSaved(r.Context(), notify.TaskUpdated, res.TaskID, res.Volume)
	httpx.OK(w, res)
}

// ---------- F-34 启停 ----------

func (a *app) handleTaskControl(w http.ResponseWriter, r *http.Request) {
	action, ok := task.ParseAction(r.PathValue("action"))
	if !ok {
		httpx.Fail(w, httpx.CodeBadRequest, "不支持的操作")
		return
	}
	var in idsReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !wantTaskIDs(w, in.IDs) {
		return
	}
	res, err := a.tasks.Control(r.Context(), auth.From(r.Context()), a.notifier, action, in.IDs)
	if err != nil {
		failTask(w, "任务启停", err)
		return
	}
	httpx.OK(w, res)
}

type projectStateReq struct {
	IDs    []int64 `json:"ids"`
	Enable bool    `json:"enable"`
}

func (a *app) handleTaskProjectState(w http.ResponseWriter, r *http.Request) {
	var in projectStateReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !wantTaskIDs(w, in.IDs) {
		return
	}
	res, err := a.tasks.SetProjectState(r.Context(), auth.From(r.Context()), in.IDs, in.Enable)
	if err != nil {
		failTask(w, "启用/停用方案", err)
		return
	}
	httpx.OK(w, res)
}

// ---------- 紧急任务 / 设置音量（:80 的两组按钮）----------

// handleTaskEmergency 查当前紧急任务。
func (a *app) handleTaskEmergency(w http.ResponseWriter, r *http.Request) {
	info, err := a.tasks.CurrentEmergency(r.Context())
	if err != nil {
		failTask(w, "查询紧急任务", err)
		return
	}
	httpx.OK(w, info)
}

// handleTaskSetEmergency 把一条文件广播任务设为紧急任务。全系统只能有一条。
func (a *app) handleTaskSetEmergency(w http.ResponseWriter, r *http.Request) {
	var in struct {
		ID int64 `json:"id"`
	}
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	info, err := a.tasks.SetEmergency(r.Context(), auth.From(r.Context()), in.ID)
	if err != nil {
		failTask(w, "设置紧急任务", err)
		return
	}
	httpx.OK(w, info)
}

func (a *app) handleTaskCancelEmergency(w http.ResponseWriter, r *http.Request) {
	info, err := a.tasks.CancelEmergency(r.Context())
	if err != nil {
		failTask(w, "取消紧急任务", err)
		return
	}
	httpx.OK(w, info)
}

func (a *app) handleTaskVolume(w http.ResponseWriter, r *http.Request) {
	var in struct {
		IDs    []int64 `json:"ids"`
		Volume int     `json:"volume"`
	}
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !wantTaskIDs(w, in.IDs) {
		return
	}
	res, err := a.tasks.SetVolume(r.Context(), auth.From(r.Context()), in.IDs, in.Volume)
	if err != nil {
		failTask(w, "设置任务音量", err)
		return
	}
	httpx.OK(w, res)
}

// ---------- F-35 删除 ----------

func (a *app) handleTaskDeletePreview(w http.ResponseWriter, r *http.Request) {
	ids, ok := parseIDList(w, r.URL.Query().Get("ids"))
	if !ok {
		return
	}
	if !wantTaskIDs(w, ids) {
		return
	}
	res, err := a.tasks.Preview(r.Context(), auth.From(r.Context()), ids)
	if err != nil {
		failTask(w, "预览删除影响", err)
		return
	}
	httpx.OK(w, res)
}

type taskDeleteReq struct {
	IDs       []int64 `json:"ids"`
	Confirmed bool    `json:"confirmed"`
}

func (a *app) handleTaskDelete(w http.ResponseWriter, r *http.Request) {
	var in taskDeleteReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !wantTaskIDs(w, in.IDs) {
		return
	}
	if !in.Confirmed {
		httpx.Fail(w, httpx.CodeBadRequest, "删除未确认")
		return
	}
	res, err := a.tasks.Delete(r.Context(), auth.From(r.Context()), in.IDs)
	if err != nil {
		failTask(w, "删除任务", err)
		return
	}
	all := append(append([]int64{}, res.Deleted...), res.DeletedSubs...)
	if len(all) > 0 {
		a.notifier.TaskChanged(r.Context(), notify.TaskDeleted, all)
		res.Notified = true
	}
	httpx.OK(w, res)
}

// ---------- F-37 复制与终端同步 ----------

type taskCopyReq struct {
	TargetFolderID int64  `json:"targetFolderId"`
	NewName        string `json:"newName"`
}

func (a *app) handleTaskCopy(w http.ResponseWriter, r *http.Request) {
	id, ok := taskIDFromPath(w, r)
	if !ok {
		return
	}
	var in taskCopyReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.tasks.Copy(r.Context(), auth.From(r.Context()), id, in.TargetFolderID, in.NewName)
	if err != nil {
		failTask(w, "复制任务", err)
		return
	}
	a.notifier.TaskSaved(r.Context(), notify.TaskAdded, res.TaskID, res.Volume)
	httpx.OK(w, res)
}

type taskSyncReq struct {
	TaskIDs     []int64 `json:"taskIds"`
	TerminalIDs []int64 `json:"terminalIds"`
}

func (a *app) handleTaskSync(w http.ResponseWriter, r *http.Request) {
	var in taskSyncReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !wantTaskIDs(w, in.TaskIDs) {
		return
	}
	if len(in.TerminalIDs) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "未选择终端")
		return
	}
	res, err := a.tasks.SyncTerminals(r.Context(), auth.From(r.Context()), in.TaskIDs, in.TerminalIDs)
	if err != nil {
		failTask(w, "同步任务终端", err)
		return
	}
	if len(res.Tasks) > 0 {
		// 终端清单变了等同于任务被改过，走与修改同一条通知
		for _, id := range res.Tasks {
			a.notifier.TaskSaved(r.Context(), notify.TaskUpdated, id, 0)
		}
		res.Notified = true
	}
	httpx.OK(w, res)
}

// ---------- 选择器 ----------

func (a *app) handleTaskMediaOptions(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	folderID, _ := strconv.ParseInt(q.Get("folderId"), 10, 64)
	opts, err := a.tasks.MediaOptions(r.Context(), auth.From(r.Context()),
		strings.TrimSpace(q.Get("keyword")), folderID)
	if err != nil {
		failTask(w, "查询媒体选项", err)
		return
	}
	httpx.OK(w, opts)
}

func (a *app) handleTaskTerminalOptions(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	groupID, _ := strconv.ParseInt(q.Get("groupId"), 10, 64)
	opts, err := a.tasks.TerminalOptions(r.Context(), auth.From(r.Context()),
		strings.TrimSpace(q.Get("keyword")), groupID)
	if err != nil {
		failTask(w, "查询终端选项", err)
		return
	}
	httpx.OK(w, opts)
}
