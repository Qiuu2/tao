package main

import (
	"errors"
	"net/http"
	"strconv"
	"strings"

	"htweb/internal/alarm"
	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/store"
)

// failAlarm 把报警业务错误映射成合适的响应码。
func failAlarm(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, alarm.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, err.Error())
	case errors.Is(err, alarm.ErrNoPermission):
		httpx.Fail(w, httpx.CodeForbidden, err.Error())
	case errors.Is(err, alarm.ErrChannelUsed):
		// 通道被占用是「冲突」而不是「参数错」，给前端一个可区分的码
		httpx.Fail(w, httpx.CodeConflict, err.Error())
	default:
		if isAlarmValidationErr(err) {
			httpx.Fail(w, httpx.CodeBadRequest, err.Error())
			return
		}
		httpx.Internal(w, action, err)
	}
}

// isAlarmValidationErr 识别面向用户的校验错误。
// 与其他模块一致：关键词匹配，宁可漏判成 500，也不把 SQL 细节吐给前端。
func isAlarmValidationErr(err error) bool {
	msg := err.Error()
	for _, kw := range []string{
		"不能为空", "最多", "必须", "不存在", "过长", "之间", "已存在",
		"重新选择", "未绑定", "重复", "不是报警主机", "报警媒体库", "请先",
	} {
		if strings.Contains(msg, kw) {
			return true
		}
	}
	return false
}

func alarmIDFromPath(w http.ResponseWriter, r *http.Request) (int64, bool) {
	id, err := strconv.ParseInt(r.PathValue("id"), 10, 64)
	if err != nil || id <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "ID 不合法")
		return 0, false
	}
	return id, true
}

// ---------- F-38 报警映射列表 ----------

func (a *app) handleAlarmMappingList(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))

	res, err := a.alarms.ListMappings(r.Context(), auth.From(r.Context()), alarm.MappingQuery{
		SearchKey: q.Get("searchKey"),
		Keyword:   strings.TrimSpace(q.Get("keyword")),
		OrderBy:   q.Get("orderBy"),
		Order:     q.Get("order"),
		Pager:     pager,
	})
	if err != nil {
		failAlarm(w, "查询报警映射", err)
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

// ---------- F-39 设置 / 修改映射 ----------

type alarmMappingReq struct {
	Info            string `json:"info"`
	AlarmTerminalID int64  `json:"alarmTerminalId"`
	AlarmChannel    int    `json:"alarmChannel"`
	AlarmAreaID     int64  `json:"alarmAreaId"`
	MediaID         int64  `json:"mediaId"`
}

func (m alarmMappingReq) toInput() alarm.MappingInput {
	return alarm.MappingInput{
		Info:            m.Info,
		AlarmTerminalID: m.AlarmTerminalID,
		AlarmChannel:    m.AlarmChannel,
		AlarmAreaID:     m.AlarmAreaID,
		MediaID:         m.MediaID,
	}
}

func (a *app) handleAlarmMappingGet(w http.ResponseWriter, r *http.Request) {
	id, ok := alarmIDFromPath(w, r)
	if !ok {
		return
	}
	m, err := a.alarms.GetMapping(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		failAlarm(w, "查询报警映射", err)
		return
	}
	httpx.OK(w, m)
}

func (a *app) handleAlarmMappingCreate(w http.ResponseWriter, r *http.Request) {
	var in alarmMappingReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	id, err := a.alarms.CreateMapping(r.Context(), auth.From(r.Context()), in.toInput())
	if err != nil {
		failAlarm(w, "新建报警映射", err)
		return
	}
	// 这里刻意不发 UDP 通知：旧系统里根本没有 alarm 这种报文
	// （详见 alarm 包的包注释），凭空编一个 state 码比不发更糟。
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleAlarmMappingUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := alarmIDFromPath(w, r)
	if !ok {
		return
	}
	var in alarmMappingReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.alarms.UpdateMapping(r.Context(), auth.From(r.Context()), id, in.toInput()); err != nil {
		failAlarm(w, "修改报警映射", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleAlarmMappingDelete(w http.ResponseWriter, r *http.Request) {
	var in idsReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if len(in.IDs) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "未选择映射")
		return
	}
	if len(in.IDs) > 500 {
		httpx.Fail(w, httpx.CodeBadRequest, "一次最多处理 500 条")
		return
	}
	res, err := a.alarms.DeleteMappings(r.Context(), auth.From(r.Context()), in.IDs)
	if err != nil {
		failAlarm(w, "取消报警映射", err)
		return
	}
	httpx.OK(w, res)
}

// ---------- F-40 ~ F-42 报警分区 ----------

func (a *app) handleAlarmAreaList(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))

	res, err := a.alarms.ListAreas(r.Context(), auth.From(r.Context()), alarm.AreaQuery{
		Keyword: strings.TrimSpace(q.Get("keyword")),
		OrderBy: q.Get("orderBy"),
		Order:   q.Get("order"),
		Pager:   pager,
	})
	if err != nil {
		failAlarm(w, "查询报警分区", err)
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

type alarmAreaReq struct {
	Name      string                  `json:"name"`
	Info      string                  `json:"info"`
	Terminals []alarm.AreaTerminalRef `json:"terminals"`
}

func (a *app) handleAlarmAreaGet(w http.ResponseWriter, r *http.Request) {
	id, ok := alarmIDFromPath(w, r)
	if !ok {
		return
	}
	d, err := a.alarms.GetArea(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		failAlarm(w, "查询报警分区", err)
		return
	}
	httpx.OK(w, d)
}

func (a *app) handleAlarmAreaCreate(w http.ResponseWriter, r *http.Request) {
	var in alarmAreaReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	id, err := a.alarms.CreateArea(r.Context(), auth.From(r.Context()),
		alarm.AreaInput{Name: in.Name, Info: in.Info, Terminals: in.Terminals})
	if err != nil {
		failAlarm(w, "新建报警分区", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleAlarmAreaUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := alarmIDFromPath(w, r)
	if !ok {
		return
	}
	var in alarmAreaReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.alarms.UpdateArea(r.Context(), auth.From(r.Context()), id,
		alarm.AreaInput{Name: in.Name, Info: in.Info, Terminals: in.Terminals}); err != nil {
		failAlarm(w, "修改报警分区", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleAlarmAreaDeletePreview(w http.ResponseWriter, r *http.Request) {
	ids, ok := parseIDList(w, r.URL.Query().Get("ids"))
	if !ok {
		return
	}
	res, err := a.alarms.PreviewDeleteAreas(r.Context(), auth.From(r.Context()), ids)
	if err != nil {
		failAlarm(w, "预览删除影响", err)
		return
	}
	httpx.OK(w, res)
}

type alarmAreaDeleteReq struct {
	IDs       []int64 `json:"ids"`
	Confirmed bool    `json:"confirmed"`
}

func (a *app) handleAlarmAreaDelete(w http.ResponseWriter, r *http.Request) {
	var in alarmAreaDeleteReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if len(in.IDs) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "未选择报警分区")
		return
	}
	if !in.Confirmed {
		httpx.Fail(w, httpx.CodeBadRequest, "删除未确认")
		return
	}
	res, err := a.alarms.DeleteAreas(r.Context(), auth.From(r.Context()), in.IDs)
	if err != nil {
		failAlarm(w, "删除报警分区", err)
		return
	}
	httpx.OK(w, res)
}

// ---------- 选择器 ----------

func (a *app) handleAlarmHosts(w http.ResponseWriter, r *http.Request) {
	opts, err := a.alarms.AlarmHosts(r.Context(), auth.From(r.Context()))
	if err != nil {
		failAlarm(w, "查询报警主机", err)
		return
	}
	httpx.OK(w, opts)
}

func (a *app) handleAlarmAreaOptions(w http.ResponseWriter, r *http.Request) {
	opts, err := a.alarms.AreaOptions(r.Context(), auth.From(r.Context()))
	if err != nil {
		failAlarm(w, "查询报警分区选项", err)
		return
	}
	httpx.OK(w, opts)
}

func (a *app) handleAlarmMediaOptions(w http.ResponseWriter, r *http.Request) {
	res, err := a.alarms.AlarmMedia(r.Context(), strings.TrimSpace(r.URL.Query().Get("keyword")))
	if err != nil {
		failAlarm(w, "查询报警媒体", err)
		return
	}
	httpx.OK(w, res)
}

func (a *app) handleAlarmTerminalOptions(w http.ResponseWriter, r *http.Request) {
	opts, err := a.alarms.AreaTerminalOptions(r.Context(), auth.From(r.Context()),
		strings.TrimSpace(r.URL.Query().Get("keyword")))
	if err != nil {
		failAlarm(w, "查询终端选项", err)
		return
	}
	httpx.OK(w, opts)
}
