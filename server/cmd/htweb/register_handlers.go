package main

import (
	"errors"
	"log"
	"net/http"

	"htweb/internal/audit"
	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/register"
)

// 注册服务（旧版 regist_server.php + do.php 的 regist_server() / settrydo()）。
//
// 路由分两档（挂法见 main.go 的 regGate）：
//
//	GET  /api/register/status   始终公开 —— 登录页靠它判断服务器有没有注册，不含机器码
//	GET  /api/register          比上面多一个机器码
//	POST /api/register          执行注册命令
//	POST /api/register/trial    领取试用
//
// 后三个的鉴权**随注册状态开合**：flag 不是 1/2 时（这几种状态下 auth.Login 直接
// 拒绝登录，BR-71）放行未登录请求，否则要求登录 + serverpriv。不这么做的话，
// 一台没注册的服务器既登不进来、也就永远注册不了。
//
// ⚠ 旧版这两个动作**任何状态下都不校验会话**（regist_server.php 里那段 session 判断被
// 整段注释掉了），而注册码是直接拼进 shell 命令的。新版把敞开的窗口收窄到
// 「非如此不可」的那一段，命令也改成不经 shell 传参 —— 这是与旧版仅有的两处差异，
// 都在 register 包的注释里写明了。

func (a *app) failRegister(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, register.ErrTried), errors.Is(err, register.ErrNoTrial),
		errors.Is(err, register.ErrEmptyKey), errors.Is(err, register.ErrKeyTooLong):
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
	case errors.Is(err, register.ErrCommand):
		// 装机没装 registerserver / 路径配错了。是服务器的问题，但原因必须说出来，
		// 否则页面上只剩一句「服务器内部错误」，没人知道该去哪儿看。
		log.Printf("注册服务器: %v", err)
		httpx.Fail(w, httpx.CodeInternal, err.Error())
	default:
		if isValidationErr(err) {
			httpx.Fail(w, httpx.CodeBadRequest, err.Error())
			return
		}
		httpx.Internal(w, action, err)
	}
}

// handleRegisterStatus 是公开的状态查询，**不含机器码**。
func (a *app) handleRegisterStatus(w http.ResponseWriter, r *http.Request) {
	st, err := a.registers.Status(r.Context(), false)
	if err != nil {
		a.failRegister(w, "查询注册状态", err)
		return
	}
	httpx.OK(w, st)
}

// handleRegisterGet 是注册服务页的数据，比公开接口多一个机器码。
func (a *app) handleRegisterGet(w http.ResponseWriter, r *http.Request) {
	st, err := a.registers.Status(r.Context(), true)
	if err != nil {
		a.failRegister(w, "查询注册状态", err)
		return
	}
	httpx.OK(w, st)
}

type registerReq struct {
	LicenseKey string `json:"licenseKey"`
}

func (a *app) handleRegisterSubmit(w http.ResponseWriter, r *http.Request) {
	var in registerReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.registers.Register(r.Context(), in.LicenseKey)
	if err != nil {
		a.failRegister(w, "注册服务器", err)
		return
	}
	// 旧版在判定之前就无条件发了一次重启；新版只在注册成功后发。
	if res.OK {
		// 审计写在发包之前 —— 包一发机器就重启了，之后什么都写不进去
		a.auditRegister(r, "注册服务器")
		a.notifier.ServerReboot(r.Context())
	}
	httpx.OK(w, res)
}

func (a *app) handleRegisterTrial(w http.ResponseWriter, r *http.Request) {
	st, err := a.registers.Trial(r.Context())
	if err != nil {
		a.failRegister(w, "领取试用", err)
		return
	}
	// 旧版领完试用会 reboot，让新的试用期生效。同样先记审计再发包。
	a.auditRegister(r, "领取试用期")
	a.notifier.ServerReboot(r.Context())
	httpx.OK(w, st)
}

// auditRegister 记一行操作日志。
//
// ⚠ 这两个动作不走 audit_mw 的路径表，理由有两条：
//  1. 成功之后紧接着就发重启包，机器一重启中间件那一步再也执行不到（与
//     POST /api/server/reboot 同理），所以要在发包**之前**自己记。
//  2. 服务器没注册时这两个接口是免登录的（main.go 的 regGate），
//     上下文里根本没有用户 —— 中间件那套「取当前用户」的写法在这儿不成立。
func (a *app) auditRegister(r *http.Request, action string) {
	who := "未登录（服务器未注册）"
	if u := auth.From(r.Context()); u != nil && u.Username != "" {
		who = u.Username
	}
	a.auditor.Write(r.Context(), who, action, audit.ClientIP(r))
}
