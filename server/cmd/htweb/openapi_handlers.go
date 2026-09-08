package main

import (
	"errors"
	"net/http"
	"strconv"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/openapi"
	"htweb/internal/task"
)

// 开发者接口（/openapi/v1）的 handler。
//
// # 路径为什么和界面分开
//
// /api 是界面和后端之间的私事：字段随页面改，改完两边一起发版。
// /openapi/v1 是对外的合同，带版本号，只增不改 —— 要破坏性改动就开 v2，
// 让老调用方还能在 v1 上活着。两套路径混在一起的话，
// 某天为了页面顺手改一个字段名，外面的系统就全崩了，而我们看不到。
//
// # 一个接口 = 一件界面上的事
//
// 用户的原话是「按界面功能调用」。所以这里**不是**每张表一组增删改查 ——
// 「新建任务」在界面上是一屏：选媒体、选终端、填任务信息，点一次保存。
// 接口就照这个来：一次调用把三样一起收下，后端负责拆成多张表的写入。
// 让调用方先建任务、再挂媒体、再挂终端，等于把我们的表结构变成他的必修课，
// 而且中间任何一步失败都会留下一条残缺的任务。

func openQuery(r *http.Request) openapi.ListQuery {
	q := r.URL.Query()
	num, _ := strconv.Atoi(q.Get("pageNum"))
	size, _ := strconv.Atoi(q.Get("pageSize"))
	folderID, _ := strconv.ParseInt(q.Get("folderId"), 10, 64)
	out := openapi.ListQuery{
		Keyword:  strings.TrimSpace(q.Get("keyword")),
		PageNum:  num,
		PageSize: size,
		FolderID: folderID,
	}
	// zone 既收名字也收编号，与请求体里的 Ref 是同一套规矩
	if v := strings.TrimSpace(q.Get("zone")); v != "" {
		if n, err := strconv.ParseInt(v, 10, 64); err == nil && n > 0 {
			out.Zone = openapi.Ref{ID: n}
		} else {
			out.Zone = openapi.Ref{Name: v}
		}
	}
	return out
}

func (a *app) handleOpenTaskList(w http.ResponseWriter, r *http.Request) {
	page, err := a.openAPI.ListTasks(r.Context(), auth.From(r.Context()), openQuery(r))
	if err != nil {
		a.failOpen(w, "查询任务列表", err)
		return
	}
	httpx.OK(w, page)
}

func (a *app) handleOpenTerminalList(w http.ResponseWriter, r *http.Request) {
	page, err := a.openAPI.ListTerminals(r.Context(), auth.From(r.Context()), openQuery(r))
	if err != nil {
		a.failOpen(w, "查询终端状态", err)
		return
	}
	httpx.OK(w, page)
}

func (a *app) handleOpenMediaList(w http.ResponseWriter, r *http.Request) {
	page, err := a.openAPI.ListMedia(r.Context(), auth.From(r.Context()), openQuery(r))
	if err != nil {
		a.failOpen(w, "查询媒体列表", err)
		return
	}
	httpx.OK(w, page)
}

func (a *app) handleOpenScheduleList(w http.ResponseWriter, r *http.Request) {
	list, err := a.openAPI.ListSchedules(r.Context(), auth.From(r.Context()))
	if err != nil {
		a.failOpen(w, "查询作息方案", err)
		return
	}
	httpx.OK(w, list)
}

// handleOpenScheduleGet 取一个作息方案的详情。
//
// 路径上的 {name} 是方案名 —— 方案没有编号（它就是一批任务共用的 task.info），
// 所以这里不能像别的资源那样用 /{id}。
func (a *app) handleOpenScheduleGet(w http.ResponseWriter, r *http.Request) {
	name := r.PathValue("name")
	// ServeMux 已经把路径段解码过一次，这里不能再解一次
	// （方案名里带 % 的话会被解成别的字符）。
	d, err := a.openAPI.GetSchedule(r.Context(), auth.From(r.Context()), name)
	if err != nil {
		a.failOpen(w, "查询作息方案详情", err)
		return
	}
	httpx.OK(w, d)
}

// failOpen 把 service 的错误翻成对外的响应。
//
// ⚠ 只有 ValidationError 的原文能出去 —— 那是调用方自己填错的东西，
// 说清楚才能改。别的错误（查库失败、约束冲突）细节只进日志：
// 把 SQL 错误原样吐给第三方，等于把表结构和列名一起送出去。
func (a *app) failOpen(w http.ResponseWriter, where string, err error) {
	var ve *openapi.ValidationError
	switch {
	case errors.As(err, &ve):
		httpx.Fail(w, httpx.CodeBadRequest, ve.Error())
	case errors.Is(err, openapi.ErrKeyNotFound), errors.Is(err, task.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, "对象不存在")
	case errors.Is(err, task.ErrNoPermission), errors.Is(err, task.ErrFolderDenied):
		httpx.Fail(w, httpx.CodeForbidden, err.Error())
	default:
		// 动作是交给 task.Service 做的，它的校验错误也是**调用方填错了**，
		// 得原样说出来。复用界面那边同一个判别器，别在这里另写一套 ——
		// 两套关键词表迟早会分叉，分叉的表现是同一个错误在界面上说得清楚、
		// 在接口上却只回一句「服务器内部错误」。
		if isTaskValidationErr(err) {
			httpx.Fail(w, httpx.CodeBadRequest, err.Error())
			return
		}
		httpx.Internal(w, where, err)
	}
}

// ---------- 任务：新建 / 修改 / 删除 / 启停 ----------

func (a *app) handleOpenTaskCreate(w http.ResponseWriter, r *http.Request) {
	var in openapi.TaskInput
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.openAPI.CreateTask(r.Context(), auth.From(r.Context()), in)
	if err != nil {
		a.failOpen(w, "新建任务", err)
		return
	}
	httpx.OK(w, res)
}

func (a *app) handleOpenTaskUpdate(w http.ResponseWriter, r *http.Request) {
	var in openapi.TaskInput
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.openAPI.UpdateTask(r.Context(), auth.From(r.Context()), pathRef(r), in)
	if err != nil {
		a.failOpen(w, "修改任务", err)
		return
	}
	httpx.OK(w, res)
}

// openRefsReq 是「一批对象」的通用请求体，名字或编号都行。
type openRefsReq struct {
	Tasks []openapi.Ref `json:"tasks"`
}

func (a *app) handleOpenTaskAction(w http.ResponseWriter, r *http.Request) {
	var in openRefsReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	action := openapi.TaskAction(r.PathValue("action"))
	res, err := a.openAPI.ControlTasks(r.Context(), auth.From(r.Context()), action, in.Tasks)
	if err != nil {
		a.failOpen(w, "任务启停", err)
		return
	}
	httpx.OK(w, res)
}

func (a *app) handleOpenTaskDelete(w http.ResponseWriter, r *http.Request) {
	// 删除既支持 DELETE /tasks/{ref} 删一条，也支持 DELETE /tasks 批量。
	// 单条那条路径存在的理由很实际：大多数调用方的 HTTP 客户端
	// 给 DELETE 带请求体很别扭，有的干脆不支持。
	var refs []openapi.Ref
	if ref := pathRef(r); !refEmpty(ref) {
		refs = []openapi.Ref{ref}
	} else {
		var in openRefsReq
		if !httpx.DecodeJSON(w, r, &in) {
			return
		}
		refs = in.Tasks
	}
	res, err := a.openAPI.DeleteTasks(r.Context(), auth.From(r.Context()), refs)
	if err != nil {
		a.failOpen(w, "删除任务", err)
		return
	}
	httpx.OK(w, res)
}

// pathRef 把路径上的 {ref} 读成一个引用：纯数字当编号，其余当名字。
func pathRef(r *http.Request) openapi.Ref {
	v := strings.TrimSpace(r.PathValue("ref"))
	if v == "" {
		return openapi.Ref{}
	}
	if n, err := strconv.ParseInt(v, 10, 64); err == nil && n > 0 {
		return openapi.Ref{ID: n}
	}
	return openapi.Ref{Name: v}
}

func refEmpty(ref openapi.Ref) bool { return ref.ID == 0 && ref.Name == "" }

// ---------- 立即播放 / 停止 ----------

func (a *app) handleOpenPlay(w http.ResponseWriter, r *http.Request) {
	var in openapi.PlayInput
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	h, err := a.openAPI.Play(r.Context(), auth.From(r.Context()), ctxKeyPrefix(r.Context()), in)
	if err != nil {
		a.failOpen(w, "立即播放", err)
		return
	}
	httpx.OK(w, h)
}

func (a *app) handleOpenPlayStop(w http.ResponseWriter, r *http.Request) {
	id, err := strconv.ParseInt(r.PathValue("playId"), 10, 64)
	if err != nil || id <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "playId 不合法")
		return
	}
	h, err := a.openAPI.StopPlay(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		a.failOpen(w, "停止立即播放", err)
		return
	}
	httpx.OK(w, h)
}

func (a *app) handleOpenPlayList(w http.ResponseWriter, r *http.Request) {
	list, err := a.openAPI.ListPlays(r.Context(), auth.From(r.Context()))
	if err != nil {
		a.failOpen(w, "查询立即播放", err)
		return
	}
	httpx.OK(w, list)
}
