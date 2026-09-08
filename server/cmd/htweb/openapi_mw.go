package main

import (
	"context"
	"errors"
	"log"
	"net"
	"net/http"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/openapi"
)

// 开发者接口的认证中间件。
//
// # 与界面那条路的区别
//
// 界面走 x-access-token（内存会话，8 小时）；这里走 X-API-Key（落库，长期）。
// 认证通过之后**注入的是同一个 auth.User**，所以后面的权限检查、
// 可见范围收敛、只读机模式全部照旧生效 —— 不是"另一套接口另一套规矩"。
//
// # 拒绝时说得少
//
// 密钥不存在、停用了、过期了，对外一律是同一句「密钥无效」。
// 对着接口试密钥的人不该从错误信息里学到任何东西 ——
// 「这个前缀存在但停用了」就是在告诉他前缀猜对了。
//
// 真实原因进服务端日志，运维查得到。

// apiKeyHeader 是开发者接口的凭据头。
//
// 不复用界面的 x-access-token：两种凭据的生命周期、吊销方式、
// 泄露后的处置都不一样，混在一个头里，日志和排查时分不清是谁在调。
const apiKeyHeader = "X-API-Key"

// openKey 要求一把有效的开发者密钥。
func (a *app) openKey(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		raw := strings.TrimSpace(r.Header.Get(apiKeyHeader))
		if raw == "" {
			// 这一句可以说清楚 —— 它没泄露任何东西，而且能省掉一轮来回问
			httpx.Fail(w, httpx.CodeUnauthorized,
				"缺少 "+apiKeyHeader+" 请求头。在「开发者接口」页面新建一把密钥。")
			return
		}
		u, why, err := a.openAPI.Authenticate(r.Context(), raw)
		if errors.Is(err, openapi.ErrKeyRejected) {
			// ⚠ 真实原因只进日志。对外恒定一句话。
			log.Printf("openapi: 认证失败 (%s): %s", clientIP(r), why)
			httpx.Fail(w, httpx.CodeUnauthorized, "密钥无效")
			return
		}
		if err != nil {
			httpx.Fail(w, httpx.CodeInternal, "校验密钥失败")
			return
		}
		// 台账：这把密钥还有人在用吗、从哪调的。写入自带节流。
		a.openAPI.TouchUsed(r.Context(), why, clientIP(r))

		// 前缀往下传：立即播放要把「是哪把密钥发起的」记进 api_play。
		// 只记账号是不够的 —— 一个账号发三把密钥给三个系统，
		// 出事时分不清是哪个系统让操场响的。
		ctx := auth.WithUser(r.Context(), u)
		ctx = context.WithValue(ctx, keyPrefixCtxKey{}, why)
		next(w, r.WithContext(ctx))
	}
}

// openRight 在有效密钥之上再要一个功能权限位。
//
// 权限位取自**密钥归属的那个账号** —— 想给第三方多大权限，
// 就在用户管理里给那个账号配多大。一处配置两处生效。
func (a *app) openRight(priv string, next http.HandlerFunc) http.HandlerFunc {
	return a.openKey(func(w http.ResponseWriter, r *http.Request) {
		u := auth.From(r.Context())
		if u.ReadOnly {
			httpx.Fail(w, httpx.CodeForbidden, "服务器处于备机模式，系统只读")
			return
		}
		if !u.HasRight(priv) {
			httpx.Fail(w, httpx.CodeForbidden,
				"这把密钥归属的账号没有对应权限，请在用户管理里调整")
			return
		}
		next(w, r)
	})
}

// clientIP 取调用方地址。
//
// ⚠ 优先信任 X-Forwarded-For 的**第一段**（最初的客户端），
// 但这个头是调用方可伪造的 —— 它只用于台账展示，
// **绝不能拿来做权限判断**。真要按 IP 限制得在反向代理层做。
func clientIP(r *http.Request) string {
	if xff := r.Header.Get("X-Forwarded-For"); xff != "" {
		if first, _, ok := strings.Cut(xff, ","); ok {
			return strings.TrimSpace(first)
		}
		return strings.TrimSpace(xff)
	}
	if ip, _, err := net.SplitHostPort(r.RemoteAddr); err == nil {
		return ip
	}
	return r.RemoteAddr
}

// keyPrefixCtxKey 是密钥前缀在请求上下文里的键。
//
// 用私有的空结构体做键（而不是字符串），是 context 的标准做法：
// 别的包不可能不小心用同名字符串覆盖掉它。
type keyPrefixCtxKey struct{}

// ctxKeyPrefix 取当前请求用的是哪把密钥（前缀）。不是密钥本身，
// 前缀不是秘密 —— 它只用来在台账里标识来源。
func ctxKeyPrefix(ctx context.Context) string {
	v, _ := ctx.Value(keyPrefixCtxKey{}).(string)
	return v
}

// ctxUser 是给需要在 handler 里拿身份的地方用的小助手。
func ctxUser(ctx context.Context) *auth.User { return auth.From(ctx) }
