package main

import (
	"errors"
	"net/http"
	"strconv"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/holiday"
	"htweb/internal/httpx"
	"htweb/internal/notify"
	"htweb/internal/remote"
	"htweb/internal/store"
	"htweb/internal/zone"
)

// 这一份收了四个模块的 handler：终端分区 / 节假日 / 遥控任务 / 时间设置。
// 它们都是「一张表 + 一个页面」的小模块，各拆一个文件反而更难找。

// isUserFacingErr 识别可以原样回给用户的校验错误。
// 与其他模块同一套做法：关键词匹配，宁可漏判成 500，也不把 SQL 细节吐出去。
func isUserFacingErr(err error) bool {
	msg := err.Error()
	// 关键词表宁可漏判成 500，也不把 SQL 细节吐给前端。
	// 加一条校验就要回来看一眼这张表 —— 现网实测漏过两条：
	// 「任务名称不能包含 ? & = 」和「请选择采播源终端」都变成了 500。
	for _, kw := range []string{
		"不能为空", "不能包含", "最多", "必须", "不存在", "过长", "之间", "已存在", "已经被占用",
		"重新选择", "未绑定", "重复", "请先", "请选择", "请至少", "请填写", "只能", "不允许",
		"不能早于", "不合法", "格式不正确",
	} {
		if strings.Contains(msg, kw) {
			return true
		}
	}
	return false
}

func failWith(w http.ResponseWriter, action string, err error, notFound, forbidden error) {
	switch {
	case notFound != nil && errors.Is(err, notFound):
		httpx.Fail(w, httpx.CodeNotFound, err.Error())
	case forbidden != nil && errors.Is(err, forbidden):
		httpx.Fail(w, httpx.CodeForbidden, err.Error())
	default:
		if isUserFacingErr(err) {
			httpx.Fail(w, httpx.CodeBadRequest, err.Error())
			return
		}
		httpx.Internal(w, action, err)
	}
}

func idFromPath(w http.ResponseWriter, r *http.Request) (int64, bool) {
	id, err := strconv.ParseInt(r.PathValue("id"), 10, 64)
	if err != nil || id <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "ID 不合法")
		return 0, false
	}
	return id, true
}

// idsBody 是「按一批 ID 操作」的统一请求体。
type idsBody struct {
	IDs []int64 `json:"ids"`
}

// ==================================================================
//                        终端分区
// ==================================================================

func (a *app) failZone(w http.ResponseWriter, action string, err error) {
	failWith(w, action, err, zone.ErrNotFound, zone.ErrNoPermission)
}

func (a *app) handleZoneList(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))
	res, err := a.zones.List(r.Context(), auth.From(r.Context()), zone.Query{
		Keyword: strings.TrimSpace(q.Get("keyword")),
		OrderBy: q.Get("orderBy"),
		Order:   q.Get("order"),
		Pager:   pager,
	})
	if err != nil {
		a.failZone(w, "查询终端分区", err)
		return
	}
	httpx.OK(w, map[string]interface{}{
		"list": res.Items, "pageNum": pager.PageNum, "pageSize": pager.PageSize,
		"total": res.Total, "scopeNote": res.ScopeNote,
	})
}

func (a *app) handleZoneOptions(w http.ResponseWriter, r *http.Request) {
	opts, err := a.zones.Options(r.Context(), auth.From(r.Context()))
	if err != nil {
		a.failZone(w, "查询分区选项", err)
		return
	}
	httpx.OK(w, opts)
}

func (a *app) handleZoneTerminals(w http.ResponseWriter, r *http.Request) {
	list, err := a.zones.PickTerminals(r.Context(), auth.From(r.Context()),
		r.URL.Query().Get("keyword"))
	if err != nil {
		a.failZone(w, "查询终端", err)
		return
	}
	httpx.OK(w, list)
}

func (a *app) handleZoneGet(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	d, err := a.zones.Get(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		a.failZone(w, "查询终端分区", err)
		return
	}
	httpx.OK(w, d)
}

type zoneReq struct {
	Name        string  `json:"name"`
	Info        string  `json:"info"`
	TerminalIDs []int64 `json:"terminalIds"`
}

func (z zoneReq) toInput() zone.Input {
	return zone.Input{Name: z.Name, Info: z.Info, TerminalIDs: z.TerminalIDs}
}

func (a *app) handleZoneCreate(w http.ResponseWriter, r *http.Request) {
	var in zoneReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	id, err := a.zones.Create(r.Context(), auth.From(r.Context()), in.toInput())
	if err != nil {
		a.failZone(w, "新建终端分区", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleZoneUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	var in zoneReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.zones.Update(r.Context(), auth.From(r.Context()), id, in.toInput()); err != nil {
		a.failZone(w, "修改终端分区", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"updated": true})
}

func (a *app) handleZoneDeletePreview(w http.ResponseWriter, r *http.Request) {
	ids := zone.IDsFromCSV(r.URL.Query().Get("ids"))
	if len(ids) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "请先选择要删除的终端分区")
		return
	}
	res, err := a.zones.PreviewDelete(r.Context(), auth.From(r.Context()), ids)
	if err != nil {
		a.failZone(w, "预览删除终端分区", err)
		return
	}
	httpx.OK(w, res)
}

func (a *app) handleZoneDelete(w http.ResponseWriter, r *http.Request) {
	var in idsBody
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if len(in.IDs) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "请先选择要删除的终端分区")
		return
	}
	res, err := a.zones.Delete(r.Context(), auth.From(r.Context()), in.IDs)
	if err != nil {
		a.failZone(w, "删除终端分区", err)
		return
	}
	httpx.OK(w, res)
}

// ==================================================================
//                        节假日管理
// ==================================================================

func (a *app) failHoliday(w http.ResponseWriter, action string, err error) {
	failWith(w, action, err, holiday.ErrNotFound, nil)
}

func (a *app) handleHolidayList(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))
	// state 缺省 -1 表示不筛选；传 0/1 才过滤
	state := atoiDefault(q.Get("state"), -1)
	res, err := a.holidays.List(r.Context(), holiday.Query{
		Keyword: strings.TrimSpace(q.Get("keyword")),
		State:   state,
		OrderBy: q.Get("orderBy"),
		Order:   q.Get("order"),
		Pager:   pager,
	})
	if err != nil {
		a.failHoliday(w, "查询节假日", err)
		return
	}
	httpx.OKPage(w, res.Items, pager.PageNum, pager.PageSize, res.Total)
}

func (a *app) handleHolidayGet(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	it, err := a.holidays.Get(r.Context(), id)
	if err != nil {
		a.failHoliday(w, "查询节假日", err)
		return
	}
	httpx.OK(w, it)
}

type holidayReq struct {
	Name      string `json:"name"`
	StartDate string `json:"startdate"`
	EndDate   string `json:"enddate"`
	// ⚠ holidaytime 的口径：1 = 启用、0 = 停用（与 task 相反）。
	// 用指针是为了区分「没传」和「传了 0」—— 没传时保持「新建即启用」。
	State *int `json:"projectstate"`
}

func (h holidayReq) toInput() holiday.Input {
	in := holiday.Input{Name: h.Name, StartDate: h.StartDate, EndDate: h.EndDate,
		State: holiday.StateEnabled}
	if h.State != nil {
		in.State = *h.State
	}
	return in
}

// handleHolidayOverlaps 查重叠区间。只是给界面一个提示，不阻断保存。
func (a *app) handleHolidayOverlaps(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	start, end := strings.TrimSpace(q.Get("startdate")), strings.TrimSpace(q.Get("enddate"))
	if start == "" || end == "" {
		httpx.Fail(w, httpx.CodeBadRequest, "开始日期与结束日期不能为空")
		return
	}
	items, err := a.holidays.Overlaps(r.Context(), start, end,
		int64(atoiDefault(q.Get("excludeId"), 0)))
	if err != nil {
		a.failHoliday(w, "查询重叠节假日", err)
		return
	}
	httpx.OK(w, items)
}

func (a *app) handleHolidayCreate(w http.ResponseWriter, r *http.Request) {
	var in holidayReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	id, err := a.holidays.Create(r.Context(), in.toInput())
	if err != nil {
		a.failHoliday(w, "新建节假日", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleHolidayUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	var in holidayReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.holidays.Update(r.Context(), id, in.toInput()); err != nil {
		a.failHoliday(w, "修改节假日", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"updated": true})
}

type holidayStateReq struct {
	IDs    []int64 `json:"ids"`
	Enable bool    `json:"enable"`
}

func (a *app) handleHolidayState(w http.ResponseWriter, r *http.Request) {
	var in holidayStateReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	n, err := a.holidays.SetState(r.Context(), in.IDs, in.Enable)
	if err != nil {
		a.failHoliday(w, "修改节假日状态", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"affected": n})
}

func (a *app) handleHolidayDelete(w http.ResponseWriter, r *http.Request) {
	var in idsBody
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	n, err := a.holidays.Delete(r.Context(), in.IDs)
	if err != nil {
		a.failHoliday(w, "删除节假日", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"deleted": n})
}

// ==================================================================
//                        遥控任务
// ==================================================================

func (a *app) failRemote(w http.ResponseWriter, action string, err error) {
	failWith(w, action, err, remote.ErrNotFound, nil)
}

func (a *app) handleRemoteList(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))
	res, err := a.remotes.List(r.Context(), auth.From(r.Context()), remote.Query{
		Keyword: strings.TrimSpace(q.Get("keyword")),
		Pager:   pager,
	})
	if err != nil {
		a.failRemote(w, "查询遥控任务", err)
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

// handleRemoteGet 的路径参数是**遥控键号**，不是自增 id。
// 这一页全程按 keyid 定位，表里那个 id 只是行号，对用户没有意义。
func (a *app) handleRemoteGet(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	it, err := a.remotes.Get(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		a.failRemote(w, "查询遥控任务", err)
		return
	}
	httpx.OK(w, it)
}

func (a *app) handleRemoteTasks(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	list, err := a.remotes.PickTasks(r.Context(), auth.From(r.Context()),
		strings.TrimSpace(q.Get("kind")), strings.TrimSpace(q.Get("keyword")))
	if err != nil {
		a.failRemote(w, "查询可绑任务", err)
		return
	}
	httpx.OK(w, list)
}

type remoteReq struct {
	KeyID   int64   `json:"keyId"`
	KeyName string  `json:"keyName"`
	TaskIDs []int64 `json:"taskIds"`
}

func (m remoteReq) toInput() remote.Input {
	return remote.Input{KeyID: m.KeyID, KeyName: m.KeyName, TaskIDs: m.TaskIDs}
}

func (a *app) handleRemoteCreate(w http.ResponseWriter, r *http.Request) {
	var in remoteReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.remotes.Create(r.Context(), in.toInput()); err != nil {
		a.failRemote(w, "新建遥控任务", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"keyId": in.KeyID})
}

func (a *app) handleRemoteUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := idFromPath(w, r)
	if !ok {
		return
	}
	var in remoteReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.remotes.Update(r.Context(), auth.From(r.Context()), id, in.toInput()); err != nil {
		a.failRemote(w, "修改遥控任务", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"updated": true})
}

func (a *app) handleRemoteDelete(w http.ResponseWriter, r *http.Request) {
	var in idsBody
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	n, err := a.remotes.Delete(r.Context(), auth.From(r.Context()), in.IDs)
	if err != nil {
		a.failRemote(w, "删除遥控任务", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"deleted": n})
}

// ==================================================================
//                        时间设置
// ==================================================================

func (a *app) failTime(w http.ResponseWriter, action string, err error) {
	failWith(w, action, err, nil, nil)
}

func (a *app) handleTimeGet(w http.ResponseWriter, r *http.Request) {
	st, err := a.times.Get(r.Context())
	if err != nil {
		a.failTime(w, "读取时间设置", err)
		return
	}
	httpx.OK(w, st)
}

func (a *app) handleTimeTerminals(w http.ResponseWriter, r *http.Request) {
	list, err := a.times.Terminals(r.Context(), r.URL.Query().Get("keyword"))
	if err != nil {
		a.failTime(w, "查询终端", err)
		return
	}
	httpx.OK(w, list)
}

// handleTimeSetClock 设置服务器系统时间（:80 的「设置服务器时间 / 同步当前时间」）。
//
// ⚠ 服务账号没有免密 sudo 时做不到。这时接口返回一条说明缺什么的错误，
// 而不是假装成功 —— 装 deploy/install-sudoers.sh 即可开通。详见 timeset/clock.go。
//
// stopNTP 对应界面上的「同时关闭自动校时」勾选框。关系统服务这件事
// 只在明确勾选时才做，不由程序替操作员决定。
func (a *app) handleTimeSetClock(w http.ResponseWriter, r *http.Request) {
	var in struct {
		Time    string `json:"time"`
		StopNTP bool   `json:"stopNtp"`
	}
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.times.SetClock(r.Context(), in.Time, in.StopNTP); err != nil {
		a.failTime(w, "设置服务器时间", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"updated": true})
}

type ntpReq struct {
	NTPServer string `json:"ntpserver"`
}

func (a *app) handleTimeSetNTP(w http.ResponseWriter, r *http.Request) {
	var in ntpReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.times.SetNTP(r.Context(), in.NTPServer); err != nil {
		a.failTime(w, "保存 NTP 服务器地址", err)
		return
	}
	httpx.OK(w, map[string]interface{}{
		"updated": true,
		"note":    "只写入了数据库，系统的时间同步配置不会被改动，由后台服务读取生效。",
	})
}

type gpsReq struct {
	TerminalID int64 `json:"terminalId"`
}

func (a *app) handleTimeSetGPS(w http.ResponseWriter, r *http.Request) {
	var in gpsReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.times.SetGPSTerminal(r.Context(), in.TerminalID); err != nil {
		a.failTime(w, "保存校时终端", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"updated": true})
}

// handleTimeSync 给终端下发校时指令（terminal?state=30&id=N）。
//
// 这是旧版这一页里唯一真正在工作的功能 —— 改系统时间那部分从来没生效过，
// 新版没做，理由见 timeset 包的注释。
func (a *app) handleTimeSync(w http.ResponseWriter, r *http.Request) {
	var in idsBody
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	ids, err := a.times.SyncTargets(r.Context(), in.IDs)
	if err != nil {
		a.failTime(w, "校时", err)
		return
	}
	a.notifier.TerminalIDList(r.Context(), notify.TermSyncTime, ids)
	httpx.OK(w, map[string]interface{}{
		"sent": len(ids),
		"note": "校时指令已下发。终端是否真的对上时间取决于设备自身，本页不做回执确认。",
	})
}
