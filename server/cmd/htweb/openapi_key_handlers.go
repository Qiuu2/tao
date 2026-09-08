package main

import (
	"errors"
	"net/http"

	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/openapi"
)

// 开发者密钥的**管理**接口（挂在 /api 上，走界面那套登录会话）。
//
// ⚠ 注意这一组和被管理的对象走的是两条不同的路：
//
//	/api/openapi-keys      管理密钥本身 —— 必须登录，密钥管不了自己
//	/openapi/v1/*          用密钥调的业务接口
//
// 这条界限是刻意的。要是允许拿 A 密钥去发 B 密钥，一次泄露就永远收不回来了：
// 吊销 A 的时候 B 还活着。所以发新密钥必须有人**登录界面**动手。

// keyPriv 是管理密钥所需的权限位。
//
// 用 PrivUser（用户管理）而不是新造一个：一把密钥的实际权限完全等于它归属账号的
// 权限，能发密钥就等于能把某个账号的权限借出去 —— 这和"能改用户权限"是同一件事，
// 挂在同一把钥匙上才不会出现"改不了账号但能把账号借出去"的空子。
const keyPriv = auth.PrivUser

func (a *app) handleAPIKeyList(w http.ResponseWriter, r *http.Request) {
	list, err := a.openAPI.List(r.Context(), auth.From(r.Context()))
	if err != nil {
		httpx.Internal(w, "查询开发者密钥", err)
		return
	}
	httpx.OK(w, list)
}

// handleAPIKeyAccounts 给新建表单的「归属账号」下拉用。
//
// ⚠ 不能用 /api/users —— 那个列表按 BR-106 恒不显示 admin，
// 拿来当候选就永远没法给 admin 发密钥，而很多装机现场只有 admin 一个账号。
func (a *app) handleAPIKeyAccounts(w http.ResponseWriter, r *http.Request) {
	list, err := a.openAPI.Accounts(r.Context(), auth.From(r.Context()))
	if err != nil {
		a.failAPIKey(w, "查询可选账号", err)
		return
	}
	httpx.OK(w, list)
}

func (a *app) handleAPIKeyCreate(w http.ResponseWriter, r *http.Request) {
	var body struct {
		Name       string `json:"name"`
		UserID     int64  `json:"userId"`
		ExpireTime string `json:"expiretime"`
	}
	if !httpx.DecodeJSON(w, r, &body) {
		return
	}
	u := auth.From(r.Context())
	key, err := a.openAPI.Create(r.Context(), u, openapi.CreateInput{
		Name:       body.Name,
		UserID:     body.UserID,
		ExpireTime: body.ExpireTime,
	})
	if err != nil {
		a.failAPIKey(w, "新建开发者密钥", err)
		return
	}
	// ⚠ 这是明文密钥**唯一一次**出现在响应里。前端必须当场让用户抄走，
	//   列表接口再也查不到它 —— 库里只有 sha256。
	httpx.OK(w, key)
}

func (a *app) handleAPIKeyState(w http.ResponseWriter, r *http.Request) {
	id, ok := pathID(w, r)
	if !ok {
		return
	}
	var body struct {
		Enabled bool `json:"enabled"`
	}
	if !httpx.DecodeJSON(w, r, &body) {
		return
	}
	if err := a.openAPI.SetEnabled(r.Context(), auth.From(r.Context()), id, body.Enabled); err != nil {
		a.failAPIKey(w, "启停开发者密钥", err)
		return
	}
	httpx.OK(w, nil)
}

func (a *app) handleAPIKeyDelete(w http.ResponseWriter, r *http.Request) {
	id, ok := pathID(w, r)
	if !ok {
		return
	}
	if err := a.openAPI.Delete(r.Context(), auth.From(r.Context()), id); err != nil {
		a.failAPIKey(w, "删除开发者密钥", err)
		return
	}
	httpx.OK(w, nil)
}

func (a *app) failAPIKey(w http.ResponseWriter, where string, err error) {
	switch {
	case errors.Is(err, openapi.ErrKeyNotFound):
		// ⚠ 「不是你的」也走这一条。告诉调用方"这把密钥存在但不归你"，
		//   等于让他能枚举出别人有几把密钥。
		httpx.Fail(w, httpx.CodeNotFound, "密钥不存在")
	case errors.Is(err, openapi.ErrKeyRejected):
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
	default:
		var ve *openapi.ValidationError
		if errors.As(err, &ve) {
			httpx.Fail(w, httpx.CodeBadRequest, ve.Error())
			return
		}
		httpx.Internal(w, where, err)
	}
}
