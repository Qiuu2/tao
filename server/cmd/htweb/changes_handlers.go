package main

import (
	"net/http"
	"strconv"
	"strings"
	"time"

	"htweb/internal/dbwatch"
	"htweb/internal/httpx"
)

// handleChanges 长轮询：告诉浏览器 terminal / task 这两张表有没有被别人改过。
//
// 用法（前端 hooks/useDbChanges.ts）：
//
//	GET /api/changes                       第一次，不带版本号 → 立刻返回当前版本，用来对齐
//	GET /api/changes?terminal=123&task=456  之后每次带上手里那份
//	  ↳ 有变化：立刻返回 {"revs":{…},"changed":["terminal"]}
//	  ↳ 没变化：挂到 wait 秒（默认 25）再返回 changed:[]，前端接着问下一轮
//
// ⚠ 这个请求**故意挂着不返回**，最长 25 秒。
//
//	·  http.Server 的 WriteTimeout 是 0（见 main），不会被掐断。
//	·  前端必须用裸 fetch，不要走 axios 那一套：那边有 30 秒超时、
//	   重复请求取消、以及「网络错误就弹一条红条」的拦截器 ——
//	   长轮询每轮都会撞上最后这条，用户会看到一串莫名其妙的报错。
//
// ⚠ 只回版本号，**不回数据**。每个页面的筛选、分页、列都不一样，
//
//	让页面自己去调它原来那个列表接口，比在这里拼一份「通用数据」可靠得多。
func (a *app) handleChanges(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()

	// 只认前端明确带上的主题。没带的不参与比较 ——
	// 终端页不关心 task 变没变，把它叫醒只是白查一次列表。
	//
	// ⚠ rev **原样当字符串收**，不解析成数字。
	//
	//	它内部是 uint64，而 JS 的 Number 是 float64 —— 按数字发出去，
	//	浏览器 JSON.parse 就把它四舍五入了，发回来的永远对不上，
	//	于是每次都判「变了」，长轮询立刻返回、页面立刻重查、立刻再问……
	//	实测一个标签页每秒 165 次列表查询。现网「点开终端管理一堆 500」
	//	就是这么来的。两头都不碰数字，就没有精度可丢。
	known := map[dbwatch.Topic]string{}
	for _, t := range dbwatch.Topics {
		raw := strings.TrimSpace(q.Get(t))
		if raw == "" {
			continue
		}
		// 只挡住离谱的长度，不校验内容 —— 对不上就是「变了」，本来就是合法结果
		if len(raw) > 32 {
			httpx.Fail(w, httpx.CodeBadRequest, "版本号不合法")
			return
		}
		known[t] = raw
	}

	// 一个主题都没带 = 第一次来对齐，立刻回当前版本，不要挂着
	if len(known) == 0 {
		httpx.OK(w, map[string]any{"revs": a.changes.Revs(), "changed": []dbwatch.Topic{}})
		return
	}

	wait := 25 * time.Second
	if v, err := strconv.Atoi(q.Get("wait")); err == nil {
		// 夹在 [1,60] 秒：太短等于退化成高频轮询，太长会撞上中间那层
		// 反向代理/负载均衡自己的空闲超时，表现成莫名其妙的断连。
		wait = time.Duration(min(max(v, 1), 60)) * time.Second
	}

	revs, changed := a.changes.Wait(r.Context(), known, wait)
	if changed == nil {
		changed = []dbwatch.Topic{}
	}
	httpx.OK(w, map[string]any{"revs": revs, "changed": changed})
}
