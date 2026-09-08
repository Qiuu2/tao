package main

import (
	"net/http"

	"htweb/internal/httpx"
	"htweb/internal/openapi"
)

// 接口调用平台（「像 Swagger 那样」的那一页）的后端。
//
// 两个接口：
//
//	GET /api/openapi/spec          接口目录，给我们自己的界面渲染用（要登录）
//	GET /api/openapi/openapi.json  标准 OpenAPI 3.0，给集成方导进 Postman 用
//
// # 为什么规格要登录才能看
//
// 它把整个接口面（有哪些接口、什么参数）摊开了。对没有密钥的人来说，
// 这是一份免费的攻击面清单。要登录不影响正常使用 —— 看这一页的人本来就是
// 广播系统的管理员，他要把接口信息转给集成方。
//
// # 「试一试」为什么不在这儿
//
// 界面上点「试一试」是**浏览器直接打 /openapi/v1**，不经过这里代理。
// 这样点出来的结果和集成方在他自己机器上得到的完全一样 ——
// 同一条认证路径、同一套权限、同一份错误。
// 走后端代理的话，代理是用登录身份还是用密钥就成了一个说不清的问题，
// 而「在平台上能跑、在你那儿跑不通」是最难查的一类问题。

func (a *app) handleOpenAPISpec(w http.ResponseWriter, r *http.Request) {
	httpx.OK(w, openapi.Catalog())
}

// handleOpenAPIJSON 吐标准 OpenAPI 文档。
//
// ⚠ 直接写 JSON，不套 {code,msg,data} 那个信封 ——
// Postman / Apifox 只认裸的 OpenAPI 文档，套了信封它们会说「不是有效的规格」。
func (a *app) handleOpenAPIJSON(w http.ResponseWriter, r *http.Request) {
	doc, err := openapi.OpenAPIJSON(baseURLOf(r))
	if err != nil {
		httpx.Internal(w, "生成接口规格", err)
		return
	}
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	// 让浏览器直接下载成文件，省得用户自己另存为
	w.Header().Set("Content-Disposition", `attachment; filename="broadcast-openapi.json"`)
	_, _ = w.Write(doc)
}

// baseURLOf 猜出这台服务器对外的地址。
//
// ⚠ 用 Host 头而不是配置里的监听地址：监听的往往是 0.0.0.0:8080，
// 写进规格里集成方拿去是连不上的。Host 是浏览器实际访问的那个地址，
// 反向代理后面也对。协议同理看 X-Forwarded-Proto。
func baseURLOf(r *http.Request) string {
	scheme := "http"
	if p := r.Header.Get("X-Forwarded-Proto"); p != "" {
		scheme = p
	} else if r.TLS != nil {
		scheme = "https"
	}
	return scheme + "://" + r.Host
}
