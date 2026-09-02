package auth

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"
)

// 备机模式（serverbaseparam.model = 2）下的权限边界。
//
// 这是回归测试，防的是一个真出过的问题：
// 原来 RequireRight 对备机模式一律 40302，而「服务器信息」页也走这条路 ——
// 于是一旦在这一页上把主备模式切成「备份服务器」，这一页自己也被锁死，
// 再没有任何界面能把它切回主服务器，只能进数据库手工改。
//
// 现在的口径：**服务器信息这一组接口不受备机模式限制，其余模块仍然只读**。

// newTestManager 造一个不连数据库的 Manager —— 这几个用例只走会话与中间件，
// 不碰 db。NewManager 会起一个 gc goroutine，这里直接构造结构体绕开它。
func newTestManager() *Manager {
	return &Manager{
		secret:   []byte("test-secret"),
		ttl:      time.Minute,
		sessions: make(map[string]*session),
	}
}

// call 用给定用户的令牌打一次请求，返回响应体里的 code。
func call(t *testing.T, m *Manager, h http.HandlerFunc, u *User) int {
	t.Helper()
	tok, err := m.issue(u)
	if err != nil {
		t.Fatalf("签发令牌: %v", err)
	}
	req := httptest.NewRequest(http.MethodGet, "/x", nil)
	req.Header.Set(HeaderToken, tok)
	rec := httptest.NewRecorder()
	h(rec, req)

	var body struct {
		Code int `json:"code"`
	}
	if err := json.Unmarshal(rec.Body.Bytes(), &body); err != nil {
		t.Fatalf("解析响应: %v (body=%s)", err, rec.Body.String())
	}
	return body.Code
}

// okHandler 走到业务处理器就回 200，用来区分「放行」与「被中间件拦下」。
func okHandler(w http.ResponseWriter, _ *http.Request) {
	writeJSON(w, map[string]interface{}{"code": 200, "msg": "ok", "data": nil})
}

func TestReadOnlyModeBlocksNormalModules(t *testing.T) {
	m := newTestManager()
	admin := &User{ID: 1, UsergroupID: 1, IsAdmin: true, ReadOnly: true}

	got := call(t, m, m.RequireRight(PrivTask, okHandler), admin)
	if got != 40302 {
		t.Fatalf("备机模式下普通模块应被拦下（40302），实际 %d", got)
	}
}

func TestReadOnlyModeAllowsServerInfo(t *testing.T) {
	m := newTestManager()
	admin := &User{ID: 1, UsergroupID: 1, IsAdmin: true, ReadOnly: true}

	got := call(t, m, m.RequireRightAllowReadOnly(PrivServer, okHandler), admin)
	if got != 200 {
		t.Fatalf("备机模式下服务器信息应放行（200），实际 %d —— 这正是把自己锁死的那个 bug", got)
	}
}

// 放开备机限制不等于放开权限：没有 serverpriv 的普通用户照样进不去。
func TestAllowReadOnlyStillChecksPrivilege(t *testing.T) {
	m := newTestManager()
	plain := &User{ID: 9, UsergroupID: 3, IsAdmin: false, ReadOnly: true}

	got := call(t, m, m.RequireRightAllowReadOnly(PrivServer, okHandler), plain)
	if got != 40301 {
		t.Fatalf("无 serverpriv 的用户应是权限不足（40301），实际 %d", got)
	}
}

// 主机模式下两条中间件行为应当一致，都放行。
func TestNormalModeAllowsBoth(t *testing.T) {
	m := newTestManager()
	admin := &User{ID: 1, UsergroupID: 1, IsAdmin: true, ReadOnly: false}

	if got := call(t, m, m.RequireRight(PrivServer, okHandler), admin); got != 200 {
		t.Fatalf("主机模式 RequireRight 应放行，实际 %d", got)
	}
	if got := call(t, m, m.RequireRightAllowReadOnly(PrivServer, okHandler), admin); got != 200 {
		t.Fatalf("主机模式 RequireRightAllowReadOnly 应放行，实际 %d", got)
	}
}

// HasRight 与 hasRightIgnoringReadOnly 的分工：前者受备机模式影响，后者不受。
func TestHasRightVsIgnoringReadOnly(t *testing.T) {
	admin := &User{ID: 1, UsergroupID: 1, IsAdmin: true, ReadOnly: true}

	if admin.HasRight(PrivServer) {
		t.Fatal("备机模式下 HasRight 应为 false")
	}
	if !admin.hasRightIgnoringReadOnly(PrivServer) {
		t.Fatal("hasRightIgnoringReadOnly 不应受备机模式影响")
	}
}
