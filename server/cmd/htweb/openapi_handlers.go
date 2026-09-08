package main

import (
	"errors"
	"net/http"
	"strconv"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/openapi"
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
	case errors.Is(err, openapi.ErrKeyNotFound):
		httpx.Fail(w, httpx.CodeNotFound, "对象不存在")
	default:
		httpx.Internal(w, where, err)
	}
}
