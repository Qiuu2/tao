package main

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"htweb/internal/auth"
)

// keyGate 挡的是「用密钥调不开放的接口」。这个测试盯着它**真的挡住了**。
//
// # 为什么专门写它
//
// 第一版 keyGate 是去问 auth.ViaAPIKey(ctx) 的，而它跑在鉴权中间件**之前**，
// 那时上下文里还没有身份 —— 于是永远拿到 false，恢复出厂、发密钥、
// 备份恢复、助手对话全都能用密钥调通。挡板装了，但装在了它要挡的东西前面。
//
// 这种缺陷不报错、不留痕：界面照常工作，被挡的接口照常返回 200，
// 只有真的拿密钥去打一遍才看得见。所以它必须有测试。
func TestKeyGateBlocksUnexposedWhenCallerUsesAPIKey(t *testing.T) {
	reached := false
	inner := func(w http.ResponseWriter, r *http.Request) {
		reached = true
		w.WriteHeader(http.StatusOK)
	}

	// 一条**不在**开放清单里的路由
	const denied = "POST /api/server/reboot"
	if _, exposed := exposedIndex[denied]; exposed {
		t.Fatalf("%s 不该在开放清单里，测试前提变了", denied)
	}
	h := keyGate(denied, inner)

	t.Run("带密钥调不开放的接口→拒绝", func(t *testing.T) {
		reached = false
		req := httptest.NewRequest(http.MethodPost, "/api/server/reboot", nil)
		req.Header.Set(auth.HeaderAPIKey, "hb_xxxxxxxx_yyyyyyyy")
		rec := httptest.NewRecorder()
		h(rec, req)

		if reached {
			t.Fatal("请求穿透到了 handler —— 挡板没起作用")
		}
		var env struct {
			Code int    `json:"code"`
			Msg  string `json:"msg"`
		}
		if err := json.Unmarshal(rec.Body.Bytes(), &env); err != nil {
			t.Fatalf("响应不是 JSON: %s", rec.Body.String())
		}
		if env.Code != 40301 {
			t.Fatalf("期望 40301，得到 %d（%s）", env.Code, env.Msg)
		}
	})

	t.Run("带会话令牌调同一个接口→放行到鉴权层", func(t *testing.T) {
		// 开放清单管的是**对外暴露面**，不是权限。
		// 登录用户该不该调得动，由路由自己的守卫决定，不归这块管。
		reached = false
		req := httptest.NewRequest(http.MethodPost, "/api/server/reboot", nil)
		req.Header.Set(auth.HeaderToken, "some-session-token")
		rec := httptest.NewRecorder()
		h(rec, req)
		if !reached {
			t.Fatal("会话请求被挡了 —— 开放清单不该影响登录用户")
		}
	})

	t.Run("两个头都带时按会话算→放行", func(t *testing.T) {
		// 与 auth.require 的取舍顺序一致：有会话就走会话。
		reached = false
		req := httptest.NewRequest(http.MethodPost, "/api/server/reboot", nil)
		req.Header.Set(auth.HeaderToken, "some-session-token")
		req.Header.Set(auth.HeaderAPIKey, "hb_xxxxxxxx_yyyyyyyy")
		rec := httptest.NewRecorder()
		h(rec, req)
		if !reached {
			t.Fatal("同时带两个头时被挡了，与 auth.require 的优先级不一致")
		}
	})
}

func TestKeyGateLetsExposedThrough(t *testing.T) {
	const allowed = "GET /api/terminals"
	if _, exposed := exposedIndex[allowed]; !exposed {
		t.Fatalf("%s 应该在开放清单里，测试前提变了", allowed)
	}
	reached := false
	h := keyGate(allowed, func(w http.ResponseWriter, r *http.Request) { reached = true })

	req := httptest.NewRequest(http.MethodGet, "/api/terminals", nil)
	req.Header.Set(auth.HeaderAPIKey, "hb_xxxxxxxx_yyyyyyyy")
	h(httptest.NewRecorder(), req)
	if !reached {
		t.Fatal("开放清单里的接口被挡了")
	}
}

// /openapi/v1 那一组本来就是给密钥用的，不受开放清单约束。
func TestKeyGateSkipsOpenAPIRoutes(t *testing.T) {
	reached := false
	h := keyGate("GET /openapi/v1/terminals", func(w http.ResponseWriter, r *http.Request) { reached = true })
	req := httptest.NewRequest(http.MethodGet, "/openapi/v1/terminals", nil)
	req.Header.Set(auth.HeaderAPIKey, "hb_xxxxxxxx_yyyyyyyy")
	h(httptest.NewRecorder(), req)
	if !reached {
		t.Fatal("/openapi/v1 的路由被开放清单挡了")
	}
}
