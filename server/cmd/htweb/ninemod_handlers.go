package main

import (
	"net/http"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/enable"
	"htweb/internal/httpx"
	"htweb/internal/offline"
	"htweb/internal/sound"
	"htweb/internal/store"
	"htweb/internal/typedtask"
)

// 这一份收了后一批模块的 handler：
// 终端功放 / 采播管理 / 文字语音 / LED 播放（共用 typedtask）、
// 启用管理、噪声设备、声场分区、云广播终端、任务传送。

// ==================================================================
//        终端功放 / 采播管理 / 文字语音 / LED 播放（typedtask）
// ==================================================================

// kindFromPath 取路径里的 {kind}，并校验它是四种类别之一。
func kindFromPath(w http.ResponseWriter, r *http.Request) (typedtask.Kind, bool) {
	k, ok := typedtask.ParseKind(r.PathValue("kind"))
	if !ok {
		httpx.Fail(w, httpx.CodeBadRequest,
			"任务类别只能是 amplifier（终端功放）、collect（采播）、tts（文字语音）、led（LED 播放）")
		return "", false
	}
	return k, true
}

func (a *app) failTyped(w http.ResponseWriter, action string, err error) {
	failWith(w, action, err, typedtask.ErrNotFound, typedtask.ErrNoPermission)
}

func (a *app) handleTypedList(w http.ResponseWriter, r *http.Request) {
	k, ok := kindFromPath(w, r)
	if !ok {
		return
	}
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))
	res, err := a.typed.List(r.Context(), auth.From(r.Context()), k, typedtask.Query{
		Keyword:  strings.TrimSpace(q.Get("keyword")),
		FolderID: int64(atoiDefault(q.Get("folderId"), 0)),
		OrderBy:  q.Get("orderBy"),
		Order:    q.Get("order"),
		Pager:    pager,
	})
	if err != nil {
		a.failTyped(w, "查询任务", err)
		return
	}
	httpx.OK(w, map[string]interface{}{
		"list": res.Items, "pageNum": pager.PageNum, "pageSize": pager.PageSize,
		"total": res.Total, "scopeNote": res.ScopeNote,
	})
}

func (a *app) handleTypedGet(w http.ResponseWriter, r *http.Request) {
	k, ok := kindFromPath(w, r)
	if !ok {
		return
	}
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	d, err := a.typed.Get(r.Context(), auth.From(r.Context()), k, id)
	if err != nil {
		a.failTyped(w, "查询任务", err)
		return
	}
	httpx.OK(w, d)
}

func (a *app) handleTypedTerminals(w http.ResponseWriter, r *http.Request) {
	list, err := a.typed.TerminalOptions(r.Context(), auth.From(r.Context()),
		r.URL.Query().Get("keyword"))
	if err != nil {
		a.failTyped(w, "查询终端", err)
		return
	}
	httpx.OK(w, list)
}

// handleTypedSources 是「采播终端 / tts终端」下拉的候选列表，按终端型号筛。
func (a *app) handleTypedSources(w http.ResponseWriter, r *http.Request) {
	k, ok := kindFromPath(w, r)
	if !ok {
		return
	}
	list, err := a.typed.SourceTerminals(r.Context(), k)
	if err != nil {
		a.failTyped(w, "查询采播终端", err)
		return
	}
	httpx.OK(w, list)
}

// handleTypedPrompts 是文字语音的「提示音」下拉（旧版写死的 9 号媒体目录）。
func (a *app) handleTypedPrompts(w http.ResponseWriter, r *http.Request) {
	list, err := a.typed.PromptMedia(r.Context())
	if err != nil {
		a.failTyped(w, "查询提示音", err)
		return
	}
	httpx.OK(w, list)
}

// handleTypedPriorityRange 是「任务级别」下拉的可选区间（由用户组级别决定）。
func (a *app) handleTypedPriorityRange(w http.ResponseWriter, r *http.Request) {
	lo, hi, err := a.typed.PriorityRange(r.Context(), auth.From(r.Context()))
	if err != nil {
		a.failTyped(w, "查询任务等级区间", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"priorityMin": lo, "priorityMax": hi})
}

// typedReq 是四种类别共用的请求体。用不到的字段留空即可。
type typedReq struct {
	TaskName       string `json:"taskName"`
	StartDate      string `json:"startdate"`
	EndDate        string `json:"enddate"`
	PlayTime       string `json:"playtime"`
	DurationSec    int    `json:"durationSec"`
	TimeLengthType int    `json:"timelengthtype"`
	TimeLength     int    `json:"timelength"`
	ExeModel       string `json:"exemodel"`
	Volume         int    `json:"defaultvolume"`
	ProjectState   int    `json:"projectstate"`
	FolderID       int64  `json:"folderId"`

	// :80 表单里的「预开电源 / 任务等级 / 发送模式」。功放不用，服务端会强制归零。
	Prepower      int `json:"prepower"`
	Priority      int `json:"priority"`
	DataSendModel int `json:"datasendmodel"`

	Switch           int   `json:"switch"`
	Channel          int   `json:"channel"`
	SourceTerminalID int64 `json:"sourceTerminalId"`
	// 采播的「音频设置」：采样率 / 比特率
	SampleRate int `json:"samplerate"`
	BandRate   int `json:"bandrate"`

	// 间隔播放（文字语音 / led播放 的「播放模式」）
	IntervalS    int `json:"interval_s"`
	IntPlayLen   int `json:"intplaylength"`
	IntPlayLenTy int `json:"intplaylengthtype"`

	// 文字语音：一个 textarea + 一组全局参数（旧版就是这么一套）
	Text      string `json:"text"`
	MusicMode int    `json:"musicmode"`
	TTSSpeed  int    `json:"ttsSpeed"`
	PromptID  int64  `json:"promptId"`

	Terminals []typedtask.Terminal `json:"terminals"`
	LED       *typedtask.LEDInput  `json:"led"`
}

func (t typedReq) toInput() typedtask.Input {
	return typedtask.Input{
		TaskName: t.TaskName, StartDate: t.StartDate, EndDate: t.EndDate,
		PlayTime: t.PlayTime, DurationSec: t.DurationSec,
		TimeLengthType: t.TimeLengthType, TimeLength: t.TimeLength,
		ExeModel: t.ExeModel, Volume: t.Volume, ProjectState: t.ProjectState,
		FolderID: t.FolderID, Switch: t.Switch, Channel: t.Channel,
		Prepower: t.Prepower, Priority: t.Priority, DataSendModel: t.DataSendModel,
		SourceTerminalID: t.SourceTerminalID,
		SampleRate:       t.SampleRate, BandRate: t.BandRate,
		IntervalS: t.IntervalS, IntPlayLen: t.IntPlayLen, IntPlayLenTy: t.IntPlayLenTy,
		Text: t.Text, MusicMode: t.MusicMode, TTSSpeed: t.TTSSpeed, PromptID: t.PromptID,
		Terminals: t.Terminals, LED: t.LED,
	}
}

func (a *app) handleTypedCreate(w http.ResponseWriter, r *http.Request) {
	k, ok := kindFromPath(w, r)
	if !ok {
		return
	}
	var in typedReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	id, err := a.typed.Create(r.Context(), auth.From(r.Context()), a.notifier, k, in.toInput())
	if err != nil {
		a.failTyped(w, "新建任务", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"taskId": id})
}

func (a *app) handleTypedUpdate(w http.ResponseWriter, r *http.Request) {
	k, ok := kindFromPath(w, r)
	if !ok {
		return
	}
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	var in typedReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.typed.Update(r.Context(), auth.From(r.Context()), a.notifier, k, id, in.toInput()); err != nil {
		a.failTyped(w, "修改任务", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"updated": true})
}

func (a *app) handleTypedControl(w http.ResponseWriter, r *http.Request) {
	k, ok := kindFromPath(w, r)
	if !ok {
		return
	}
	action, ok := typedtask.ParseAction(r.PathValue("action"))
	if !ok {
		httpx.Fail(w, httpx.CodeBadRequest, "动作只能是 start 或 stop")
		return
	}
	var in idsBody
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.typed.Control(r.Context(), auth.From(r.Context()), a.notifier, k, action, in.IDs)
	if err != nil {
		a.failTyped(w, "任务启停", err)
		return
	}
	httpx.OK(w, res)
}

type typedStateReq struct {
	IDs    []int64 `json:"ids"`
	Enable bool    `json:"enable"`
}

func (a *app) handleTypedProjectState(w http.ResponseWriter, r *http.Request) {
	k, ok := kindFromPath(w, r)
	if !ok {
		return
	}
	var in typedStateReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.typed.SetProjectState(r.Context(), auth.From(r.Context()), a.notifier, k, in.IDs, in.Enable)
	if err != nil {
		a.failTyped(w, "启用/停用方案", err)
		return
	}
	httpx.OK(w, res)
}

func (a *app) handleTypedDelete(w http.ResponseWriter, r *http.Request) {
	k, ok := kindFromPath(w, r)
	if !ok {
		return
	}
	var in idsBody
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.typed.Delete(r.Context(), auth.From(r.Context()), k, in.IDs)
	if err != nil {
		a.failTyped(w, "删除任务", err)
		return
	}
	httpx.OK(w, res)
}

// ---------- LED 专属：分组与设备 ----------

func (a *app) handleLEDFolders(w http.ResponseWriter, r *http.Request) {
	list, err := a.typed.LEDFolders(r.Context(), auth.From(r.Context()))
	if err != nil {
		a.failTyped(w, "查询 LED 分组", err)
		return
	}
	httpx.OK(w, list)
}

type ledFolderReq struct {
	Name     string `json:"name"`
	ParentID int64  `json:"parentid"`
}

func (a *app) handleLEDFolderCreate(w http.ResponseWriter, r *http.Request) {
	var in ledFolderReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	id, err := a.typed.CreateLEDFolder(r.Context(), auth.From(r.Context()), in.Name, in.ParentID)
	if err != nil {
		a.failTyped(w, "创建 LED 目录", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleLEDFolderRename(w http.ResponseWriter, r *http.Request) {
	id, ok := pathID(w, r)
	if !ok {
		return
	}
	var in ledFolderReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.typed.RenameLEDFolder(r.Context(), auth.From(r.Context()), id, in.Name); err != nil {
		a.failTyped(w, "修改 LED 目录", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleLEDFolderDelete(w http.ResponseWriter, r *http.Request) {
	id, ok := pathID(w, r)
	if !ok {
		return
	}
	res, err := a.typed.DeleteLEDFolder(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		a.failTyped(w, "删除 LED 目录", err)
		return
	}
	httpx.OK(w, res)
}

type ledFolderCopyReq struct {
	FromID int64 `json:"fromId"`
	ToID   int64 `json:"toId"`
}

func (a *app) handleLEDFolderCopy(w http.ResponseWriter, r *http.Request) {
	var in ledFolderCopyReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	n, err := a.typed.CopyLEDFolder(r.Context(), auth.From(r.Context()), a.notifier, in.FromID, in.ToID)
	if err != nil {
		a.failTyped(w, "复制 LED 目录", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"copied": n})
}

func (a *app) handleLEDDeviceList(w http.ResponseWriter, r *http.Request) {
	list, err := a.typed.LEDDevices(r.Context(), r.URL.Query().Get("keyword"))
	if err != nil {
		a.failTyped(w, "查询 LED 设备", err)
		return
	}
	httpx.OK(w, list)
}

type ledDeviceReq struct {
	Name        string `json:"name"`
	TerminalID  int64  `json:"terminalId"`
	DevID       int64  `json:"devid"`
	IP          string `json:"ip"`
	Width       int    `json:"width"`
	Height      int    `json:"height"`
	SendPort    int    `json:"sendport"`
	MAC         string `json:"mac"`
	DefaultText string `json:"defaulttext"`
}

func (d ledDeviceReq) toInput() typedtask.LEDDeviceInput {
	return typedtask.LEDDeviceInput{
		Name: d.Name, TerminalID: d.TerminalID, DevID: d.DevID, IP: d.IP,
		Width: d.Width, Height: d.Height, SendPort: d.SendPort,
		MAC: d.MAC, DefaultText: d.DefaultText,
	}
}

func (a *app) handleLEDDeviceCreate(w http.ResponseWriter, r *http.Request) {
	var in ledDeviceReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	id, err := a.typed.CreateLEDDevice(r.Context(), in.toInput())
	if err != nil {
		a.failTyped(w, "新建 LED 设备", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleLEDDeviceUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	var in ledDeviceReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.typed.UpdateLEDDevice(r.Context(), id, in.toInput()); err != nil {
		a.failTyped(w, "修改 LED 设备", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"updated": true})
}

func (a *app) handleLEDDeviceDelete(w http.ResponseWriter, r *http.Request) {
	var in idsBody
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	n, err := a.typed.DeleteLEDDevices(r.Context(), in.IDs)
	if err != nil {
		a.failTyped(w, "删除 LED 设备", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"deleted": n})
}

// ==================================================================
//                          启用管理
// ==================================================================

func (a *app) failEnable(w http.ResponseWriter, action string, err error) {
	failWith(w, action, err, enable.ErrNotFound, nil)
}

func (a *app) handleEnableList(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))
	res, err := a.enables.List(r.Context(), enable.Query{
		Keyword: strings.TrimSpace(q.Get("keyword")),
		Pager:   pager,
	})
	if err != nil {
		a.failEnable(w, "查询启用计划", err)
		return
	}
	httpx.OKPage(w, res.Items, pager.PageNum, pager.PageSize, res.Total)
}

func (a *app) handleEnableGet(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	// 返回整个「时间槽」：同一时刻的启用行与停用行合起来给界面，
	// 这样编辑弹窗才能像 :80 那样逐条显示是启用还是停用。
	it, err := a.enables.GetSlot(r.Context(), id)
	if err != nil {
		a.failEnable(w, "查询启用计划", err)
		return
	}
	httpx.OK(w, it)
}

func (a *app) handleEnableTasks(w http.ResponseWriter, r *http.Request) {
	u := auth.From(r.Context())
	list, err := a.enables.PickTasks(r.Context(), u.IsAdmin, u.ID, r.URL.Query().Get("keyword"))
	if err != nil {
		a.failEnable(w, "查询可选任务", err)
		return
	}
	httpx.OK(w, list)
}

// enableReq 是表格式提交：同一时间点，哪些任务启用、哪些停用。
//
// ⚠ 落库时按状态拆成最多两条 enabletask 记录 —— enstate 是整行一个值，
//
//	一条记录装不下两种意图。详见 enable 包里 Save() 的注释。
type enableReq struct {
	StartDate string  `json:"startdate"`
	StartTime string  `json:"starttime"`
	Enable    []int64 `json:"enable"`
	Disable   []int64 `json:"disable"`
}

func (e enableReq) toInput() enable.SaveInput {
	return enable.SaveInput{StartDate: e.StartDate, StartTime: e.StartTime,
		Enable: e.Enable, Disable: e.Disable}
}

func (a *app) handleEnableCreate(w http.ResponseWriter, r *http.Request) {
	var in enableReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.enables.Save(r.Context(), 0, in.toInput())
	if err != nil {
		a.failEnable(w, "新建启用计划", err)
		return
	}
	httpx.OK(w, res)
}

func (a *app) handleEnableUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	var in enableReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.enables.Save(r.Context(), id, in.toInput())
	if err != nil {
		a.failEnable(w, "修改启用计划", err)
		return
	}
	httpx.OK(w, res)
}

func (a *app) handleEnableDelete(w http.ResponseWriter, r *http.Request) {
	var in idsBody
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	n, err := a.enables.Delete(r.Context(), in.IDs)
	if err != nil {
		a.failEnable(w, "删除启用计划", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"deleted": n})
}

// ==================================================================
//                     噪声设备 / 声场分区
// ==================================================================

func (a *app) failSound(w http.ResponseWriter, action string, err error) {
	failWith(w, action, err, sound.ErrNotFound, sound.ErrNoPermission)
}

func (a *app) handleSoundDeviceList(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))
	res, err := a.sounds.ListDevices(r.Context(), sound.DeviceQuery{
		Keyword: strings.TrimSpace(q.Get("keyword")),
		GroupID: int64(atoiDefault(q.Get("groupId"), 0)),
		OrderBy: q.Get("orderBy"),
		Order:   q.Get("order"),
		Pager:   pager,
	})
	if err != nil {
		a.failSound(w, "查询噪声设备", err)
		return
	}
	httpx.OKPage(w, res.Items, pager.PageNum, pager.PageSize, res.Total)
}

func (a *app) handleSoundDeviceGet(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	d, err := a.sounds.GetDevice(r.Context(), id)
	if err != nil {
		a.failSound(w, "查询噪声设备", err)
		return
	}
	httpx.OK(w, d)
}

type soundDeviceReq struct {
	Name     string `json:"name"`
	IP       string `json:"ip"`
	DevAddr  int    `json:"devaddr"`
	SendPort int    `json:"sendport"`
}

func (d soundDeviceReq) toInput() sound.DeviceInput {
	return sound.DeviceInput{Name: d.Name, IP: d.IP, DevAddr: d.DevAddr, SendPort: d.SendPort}
}

func (a *app) handleSoundDeviceCreate(w http.ResponseWriter, r *http.Request) {
	var in soundDeviceReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	id, err := a.sounds.CreateDevice(r.Context(), in.toInput())
	if err != nil {
		a.failSound(w, "新建噪声设备", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleSoundDeviceUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	var in soundDeviceReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.sounds.UpdateDevice(r.Context(), id, in.toInput()); err != nil {
		a.failSound(w, "修改噪声设备", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"updated": true})
}

func (a *app) handleSoundDeviceDelete(w http.ResponseWriter, r *http.Request) {
	var in idsBody
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	n, err := a.sounds.DeleteDevices(r.Context(), in.IDs)
	if err != nil {
		a.failSound(w, "删除噪声设备", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"deleted": n})
}

func (a *app) handleSoundDeviceOptions(w http.ResponseWriter, r *http.Request) {
	list, err := a.sounds.DeviceOptions(r.Context(), r.URL.Query().Get("keyword"))
	if err != nil {
		a.failSound(w, "查询噪声设备选项", err)
		return
	}
	httpx.OK(w, list)
}

func (a *app) handleSoundGroupList(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))
	res, err := a.sounds.ListGroups(r.Context(), auth.From(r.Context()), sound.GroupQuery{
		Keyword: strings.TrimSpace(q.Get("keyword")),
		Pager:   pager,
	})
	if err != nil {
		a.failSound(w, "查询声场分区", err)
		return
	}
	httpx.OK(w, map[string]interface{}{
		"list": res.Items, "pageNum": pager.PageNum, "pageSize": pager.PageSize,
		"total": res.Total, "scopeNote": res.ScopeNote,
	})
}

func (a *app) handleSoundGroupGet(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	d, err := a.sounds.GetGroup(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		a.failSound(w, "查询声场分区", err)
		return
	}
	httpx.OK(w, d)
}

func (a *app) handleSoundGroupOptions(w http.ResponseWriter, r *http.Request) {
	list, err := a.sounds.GroupOptions(r.Context(), auth.From(r.Context()))
	if err != nil {
		a.failSound(w, "查询声场分区选项", err)
		return
	}
	httpx.OK(w, list)
}

func (a *app) handleSoundGroupTerminals(w http.ResponseWriter, r *http.Request) {
	list, err := a.sounds.TerminalOptions(r.Context(), auth.From(r.Context()),
		r.URL.Query().Get("keyword"))
	if err != nil {
		a.failSound(w, "查询终端", err)
		return
	}
	httpx.OK(w, list)
}

type soundGroupReq struct {
	Name        string  `json:"name"`
	TerminalIDs []int64 `json:"terminalIds"`
	DeviceIDs   []int64 `json:"deviceIds"`
}

func (g soundGroupReq) toInput() sound.GroupInput {
	return sound.GroupInput{Name: g.Name, TerminalIDs: g.TerminalIDs, DeviceIDs: g.DeviceIDs}
}

func (a *app) handleSoundGroupCreate(w http.ResponseWriter, r *http.Request) {
	var in soundGroupReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	id, err := a.sounds.CreateGroup(r.Context(), auth.From(r.Context()), in.toInput())
	if err != nil {
		a.failSound(w, "新建声场分区", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleSoundGroupUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	var in soundGroupReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.sounds.UpdateGroup(r.Context(), auth.From(r.Context()), id, in.toInput()); err != nil {
		a.failSound(w, "修改声场分区", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"updated": true})
}

func (a *app) handleSoundGroupDelete(w http.ResponseWriter, r *http.Request) {
	var in idsBody
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.sounds.DeleteGroups(r.Context(), auth.From(r.Context()), in.IDs)
	if err != nil {
		a.failSound(w, "删除声场分区", err)
		return
	}
	httpx.OK(w, res)
}

// ==================================================================
//                  云广播终端 / 任务传送
// ==================================================================

func (a *app) handleCloudTerminals(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))
	res, err := a.offline.ListCloudTerminals(r.Context(), auth.From(r.Context()), offline.CloudQuery{
		Keyword: strings.TrimSpace(q.Get("keyword")),
		Pager:   pager,
	})
	if err != nil {
		failOffline(w, "查询云广播终端", err)
		return
	}
	httpx.OK(w, map[string]interface{}{
		"list": res.Items, "pageNum": pager.PageNum, "pageSize": pager.PageSize,
		"total": res.Total, "scopeNote": res.ScopeNote,
	})
}

func (a *app) handleCloudInventory(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	list, err := a.offline.CloudInventory(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		failOffline(w, "查询终端离线内容", err)
		return
	}
	httpx.OK(w, list)
}

// handleCloudBulk 是云广播终端页那排按钮（空闲传输/立即传输/停止传输/全部清除/清除空闲媒体）。
//
// 「同步时间」不在这里 —— 那是给终端下指令，走 /api/terminals/sync-time，
// 前端直接调终端模块那个接口，不在离线模块里再包一层。
func (a *app) handleCloudBulk(w http.ResponseWriter, r *http.Request) {
	var in struct {
		IDs    []int64 `json:"ids"`
		Action string  `json:"action"`
	}
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.offline.CloudBulk(r.Context(), auth.From(r.Context()),
		in.IDs, offline.CloudAction(in.Action))
	if err != nil {
		failOffline(w, "云广播终端批量操作", err)
		return
	}
	httpx.OK(w, res)
}

func (a *app) handleTransferList(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))
	res, err := a.offline.ListTransferTasks(r.Context(), auth.From(r.Context()), offline.TransferQuery{
		Keyword:    strings.TrimSpace(q.Get("keyword")),
		TerminalID: int64(atoiDefault(q.Get("terminalId"), 0)),
		Kind:       strings.TrimSpace(q.Get("kind")),
		Pager:      pager,
	})
	if err != nil {
		failOffline(w, "查询任务传送", err)
		return
	}
	httpx.OKPage(w, res.Items, pager.PageNum, pager.PageSize, res.Total)
}

func (a *app) handleTransferDetail(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	list, err := a.offline.TransferDetail(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		failOffline(w, "查询任务传送对象", err)
		return
	}
	httpx.OK(w, list)
}

// handleTransferMedia 是任务传送行内的「媒体」链接。
func (a *app) handleTransferMedia(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	list, err := a.offline.TransferMedia(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		failOffline(w, "查询任务传送媒体", err)
		return
	}
	httpx.OK(w, list)
}

// handleTransferBulk 是任务传送页的「空闲传输 / 立即传输 / 停止传输」。
func (a *app) handleTransferBulk(w http.ResponseWriter, r *http.Request) {
	var in struct {
		IDs    []int64 `json:"ids"`
		Action string  `json:"action"`
	}
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.offline.TransferBulk(r.Context(), auth.From(r.Context()),
		in.IDs, offline.CloudAction(in.Action))
	if err != nil {
		failOffline(w, "任务传送批量操作", err)
		return
	}
	httpx.OK(w, res)
}
