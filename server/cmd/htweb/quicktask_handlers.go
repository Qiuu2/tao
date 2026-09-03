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

// 快捷任务（ok112 的 view_quickplay / setquickplay / modifyquickplay
// 与 do.php 的 set_task_quick_play / modify_task_quick_play / del_quick_task）。
//
// ⚠ 它是「为这台终端新建一条专属任务并绑到键上」，不是「把已有任务绑到键上」。
//   业务规则、三个 tasktype 的由来、两个终端 ID 列的含义，全在
//   terminal/quicktask.go 顶部写着，改这里之前先读那段。

func failQuickTask(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, terminal.ErrQuickTaskNotFound):
		httpx.Fail(w, httpx.CodeNotFound, "快捷任务不存在")
	case errors.Is(err, terminal.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, "终端不存在")
	case errors.Is(err, terminal.ErrNoPermission):
		httpx.Fail(w, httpx.CodeForbidden, "没有这台终端的操作权限")
	// 这几类都是「这台终端做不了」而非服务端出错，原样把话说给用户，
	// 不要压成一句笼统的提示，更不能落成 500。
	case errors.Is(err, terminal.ErrKeyValueBad),
		errors.Is(err, terminal.ErrQuickTaskUnsupported),
		errors.Is(err, terminal.ErrQuickKeyUsed):
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
	default:
		httpx.Internal(w, action, err)
	}
}

// handleQuickTaskList 列出一台终端的快捷任务。
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

// handleQuickTaskDetail 取一条快捷任务的完整内容，供「修改」表单回填。
func (a *app) handleQuickTaskDetail(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	taskID, err := strconv.ParseInt(r.URL.Query().Get("taskId"), 10, 64)
	if err != nil || taskID <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "任务 ID 不合法")
		return
	}
	d, err := a.terminals.GetQuickTask(r.Context(), id, taskID)
	if err != nil {
		failQuickTask(w, "查询快捷任务", err)
		return
	}
	httpx.OK(w, d)
}

// handleQuickAudioSources 列出可选音源（TTS 主机与服务器）。
func (a *app) handleQuickAudioSources(w http.ResponseWriter, r *http.Request) {
	list, err := a.terminals.QuickAudioSources(r.Context())
	if err != nil {
		failQuickTask(w, "查询音源", err)
		return
	}
	httpx.OK(w, list)
}

// quickTaskReq 的字段与 ok112 的 set_task_quickplay 表单一一对应。
type quickTaskReq struct {
	TaskName     string  `json:"taskName"`
	Key          int     `json:"key"`
	IsRandomPlay int     `json:"isRandomPlay"`
	Volume       int     `json:"volume"`
	Priority     int     `json:"priority"`
	TimeLengthTy int     `json:"timeLengthType"`
	TimeLength   int     `json:"timeLength"`
	DataSendMode int     `json:"dataSendMode"`
	MediaIDs     []int64 `json:"mediaIds"`
	TerminalIDs  []int64 `json:"terminalIds"`
	TTS          *struct {
		Text        string `json:"text"`
		Speed       int    `json:"speed"`
		MusicMode   int    `json:"musicMode"`
		AudioSource int64  `json:"audioSource"`
	} `json:"tts"`
	LED *struct {
		Text        string  `json:"text"`
		Speed       int     `json:"speed"`
		LedMode     int     `json:"ledmode"`
		TerminalIDs []int64 `json:"terminalIds"`
	} `json:"led"`
}

func (in quickTaskReq) toForm() terminal.QuickTaskForm {
	f := terminal.QuickTaskForm{
		TaskName: strings.TrimSpace(in.TaskName), Key: in.Key,
		IsRandomPlay: in.IsRandomPlay, Volume: in.Volume, Priority: in.Priority,
		TimeLengthTy: in.TimeLengthTy, TimeLength: in.TimeLength,
		DataSendMode: in.DataSendMode,
		MediaIDs:     in.MediaIDs, TerminalIDs: in.TerminalIDs,
	}
	if in.TTS != nil && strings.TrimSpace(in.TTS.Text) != "" {
		f.TTS = &terminal.QuickTTS{
			Text: in.TTS.Text, Speed: in.TTS.Speed,
			MusicMode: in.TTS.MusicMode, AudioSource: in.TTS.AudioSource,
		}
	}
	if in.LED != nil && strings.TrimSpace(in.LED.Text) != "" {
		f.LED = &terminal.QuickLED{
			Text: in.LED.Text, Speed: in.LED.Speed,
			LedMode: in.LED.LedMode, TerminalIDs: in.LED.TerminalIDs,
		}
	}
	return f
}

// validate 做那些不依赖数据库的基本检查，让错误在进服务层之前就说清楚。
func (in quickTaskReq) validate() string {
	if strings.TrimSpace(in.TaskName) == "" {
		return "请填写任务名称"
	}
	if in.TimeLengthTy != 1 && in.TimeLengthTy != 2 {
		return "播放时长类型只能是「按时长」或「按次数」"
	}
	if in.TimeLength <= 0 {
		return "播放时长必须大于 0"
	}
	isTTS := in.TTS != nil && strings.TrimSpace(in.TTS.Text) != ""
	if !isTTS && len(in.MediaIDs) == 0 {
		return "请选择要播放的媒体文件，或改用文字播报"
	}
	if len(in.TerminalIDs) == 0 {
		return "请选择要播放到哪些终端"
	}
	return ""
}

// handleQuickTaskCreate 新建快捷任务（ok112 的 set_task_quick_play）。
func (a *app) handleQuickTaskCreate(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	var in quickTaskReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if msg := in.validate(); msg != "" {
		httpx.Fail(w, httpx.CodeBadRequest, msg)
		return
	}
	taskID, err := a.terminals.CreateQuickTask(r.Context(), auth.From(r.Context()), id, in.toForm())
	if err != nil {
		failQuickTask(w, "新建快捷任务", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"taskId": taskID})
}

// handleQuickTaskUpdate 修改快捷任务（ok112 的 modify_task_quick_play）。
func (a *app) handleQuickTaskUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	taskID, err := strconv.ParseInt(r.URL.Query().Get("taskId"), 10, 64)
	if err != nil || taskID <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "任务 ID 不合法")
		return
	}
	var in quickTaskReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if msg := in.validate(); msg != "" {
		httpx.Fail(w, httpx.CodeBadRequest, msg)
		return
	}
	if err := a.terminals.UpdateQuickTask(r.Context(), auth.From(r.Context()),
		id, taskID, in.toForm()); err != nil {
		failQuickTask(w, "修改快捷任务", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"updated": true})
}

type quickTaskDeleteReq struct {
	TaskIDs []int64 `json:"taskIds"`
}

// handleQuickTaskDelete 删快捷任务（ok112 的 del_quick_task）。
//
// ok112 的列表是复选框多选后一起删，所以这里收的是数组而不是单个 id。
func (a *app) handleQuickTaskDelete(w http.ResponseWriter, r *http.Request) {
	id, ok := pathNamedID(w, r, "id")
	if !ok {
		return
	}
	var in quickTaskDeleteReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if len(in.TaskIDs) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "未选择快捷任务")
		return
	}
	n, err := a.terminals.DeleteQuickTasks(r.Context(), auth.From(r.Context()), id, in.TaskIDs)
	if err != nil {
		failQuickTask(w, "删除快捷任务", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"deleted": n})
}
