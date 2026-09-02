// Package auth 实现登录认证、会话与功能权限判定。
//
// 严格遵循手册 F-11 / F-12 与业务规则 BR-71 ~ BR-83：
//   - 密码沿用无盐 MD5（契约 C-02）。这不是好设计，但新旧系统共用一套库，
//     改哈希会导致旧 Web 无法登录，本期必须保持。
//   - 必须校验 book_admin.enable = 1（BR-72）
//   - usergroupid = 1 即超级管理员（BR-74），统一用它判定，
//     不再像旧代码那样三套标准混用（username=='admin' / admin_id=='administrator'）
//   - serverbaseparam.model = 2（备机）时所有功能权限一律判定为无（BR-80）
//   - 旧系统的万能验证码 htjy123 已彻底移除（BR-78）
package auth

import (
	"context"
	"crypto/hmac"
	"crypto/md5"
	"crypto/rand"
	"crypto/sha256"
	"crypto/subtle"
	"database/sql"
	"encoding/base64"
	"encoding/hex"
	"encoding/json"
	"errors"
	"fmt"
	"net/http"
	"strings"
	"sync"
	"time"
)

// Rights 是 usergroup 表上的 13 个功能权限位。字段名与列名一一对应。
type Rights struct {
	TaskPriv          int `json:"taskpriv"`
	TerminalPriv      int `json:"terminalpriv"`
	MediaPriv         int `json:"mediapriv"`
	UserPriv          int `json:"userpriv"`
	ServerPriv        int `json:"serverpriv"`
	FolderPriv        int `json:"folderpriv"`
	TerminalGroupPriv int `json:"terminalgrouppriv"`
	AlarmGroupPriv    int `json:"alarmgrouppriv"`
	BellPriv          int `json:"bellpriv"`
	AdmPriv           int `json:"admpriv"`
	TelephonePriv     int `json:"telephonepriv"`
	PowerPlay         int `json:"powerplay"`
	TtsPriv           int `json:"ttspriv"`
}

// 权限项名称常量，避免各处硬编码字符串。
const (
	PrivTask          = "taskpriv"
	PrivTerminal      = "terminalpriv"
	PrivMedia         = "mediapriv"
	PrivUser          = "userpriv"
	PrivServer        = "serverpriv"
	PrivFolder        = "folderpriv"
	PrivTerminalGroup = "terminalgrouppriv"
	PrivAlarmGroup    = "alarmgrouppriv"
	PrivBell          = "bellpriv"
	PrivAdm           = "admpriv"
	PrivTelephone     = "telephonepriv"
	PrivPowerPlay     = "powerplay"
	PrivTts           = "ttspriv"
)

func (r Rights) by(name string) int {
	switch name {
	case PrivTask:
		return r.TaskPriv
	case PrivTerminal:
		return r.TerminalPriv
	case PrivMedia:
		return r.MediaPriv
	case PrivUser:
		return r.UserPriv
	case PrivServer:
		return r.ServerPriv
	case PrivFolder:
		return r.FolderPriv
	case PrivTerminalGroup:
		return r.TerminalGroupPriv
	case PrivAlarmGroup:
		return r.AlarmGroupPriv
	case PrivBell:
		return r.BellPriv
	case PrivAdm:
		return r.AdmPriv
	case PrivTelephone:
		return r.TelephonePriv
	case PrivPowerPlay:
		return r.PowerPlay
	case PrivTts:
		return r.TtsPriv
	}
	return 0
}

// User 是登录后的会话主体。
type User struct {
	ID            int64  `json:"id"`
	Username      string `json:"username"`
	Fullname      string `json:"fullname"`
	Info          string `json:"info"`
	UsergroupID   int64  `json:"usergroupId"`
	UsergroupName string `json:"usergroupName"`
	// Level 是 usergroup.level，取值 10~109 的两位复合编码：
	// 十位 = 组级别，个位 = 任务优先级基数（契约 C-28）。
	Level   int    `json:"level"`
	IsAdmin bool   `json:"isAdmin"`
	Rights  Rights `json:"rights"`
	// ReadOnly 反映 serverbaseparam.model == 2（备机模式）。
	ReadOnly bool `json:"readonly"`
}

// HasRight 判定是否具备某项功能权限。
//
// 两条规则缺一不可（BR-79 / BR-80）：
//   - 超级管理员拥有全部权限
//   - 备机模式下一律无权限，即使是管理员
func (u *User) HasRight(name string) bool {
	if u.ReadOnly {
		return false
	}
	return u.hasRightIgnoringReadOnly(name)
}

// hasRightIgnoringReadOnly 只看权限位，不看备机模式。
// 只给「服务器信息」那一组接口用，理由见 RequireRightAllowReadOnly。
func (u *User) hasRightIgnoringReadOnly(name string) bool {
	if u.IsAdmin {
		return true
	}
	return u.Rights.by(name) == 1
}

// ---------- 会话 ----------

type session struct {
	user      *User
	expiresAt time.Time
}

type Manager struct {
	db     *sql.DB
	secret []byte
	ttl    time.Duration

	mu       sync.RWMutex
	sessions map[string]*session
}

func NewManager(db *sql.DB, secret string, ttl time.Duration) *Manager {
	m := &Manager{
		db:       db,
		secret:   []byte(secret),
		ttl:      ttl,
		sessions: make(map[string]*session),
	}
	go m.gc()
	return m
}

// gc 定期清理过期会话，避免内存无界增长。
func (m *Manager) gc() {
	t := time.NewTicker(10 * time.Minute)
	defer t.Stop()
	for range t.C {
		now := time.Now()
		m.mu.Lock()
		for k, s := range m.sessions {
			if now.After(s.expiresAt) {
				delete(m.sessions, k)
			}
		}
		m.mu.Unlock()
	}
}

var (
	ErrBadCredential = errors.New("用户名或密码错误")
	ErrUserDisabled  = errors.New("该用户已被停用")
	ErrNotRegistered = errors.New("服务器未注册")
)

// MD5Hex 计算无盐 MD5，输出 32 位小写十六进制 —— 与旧系统 PHP md5() 完全一致。
func MD5Hex(s string) string {
	sum := md5.Sum([]byte(s))
	return hex.EncodeToString(sum[:])
}

// Login 校验凭据并建立会话。
func (m *Manager) Login(ctx context.Context, username, password string) (string, *User, error) {
	// 服务器注册状态：未注册禁止登录（BR-71）
	var model, registerFlag int
	err := m.db.QueryRowContext(ctx,
		`SELECT model, registerflag FROM serverbaseparam LIMIT 1`).Scan(&model, &registerFlag)
	if err != nil {
		return "", nil, fmt.Errorf("读取服务器参数: %w", err)
	}
	if registerFlag != 1 && registerFlag != 2 {
		return "", nil, ErrNotRegistered
	}

	u := &User{}
	var pwdHash string
	var enable int
	var fullname, info sql.NullString

	// 注意：这里一次性把 enable 也取出来，以便区分「用户不存在」与「用户被停用」，
	// 给出准确提示。旧系统对这两种情况都提示"用户不存在"。
	err = m.db.QueryRowContext(ctx, `
		SELECT id, username, userpwd, usergroupid, enable, fullname, info
		FROM book_admin WHERE username = ? LIMIT 1`, username).
		Scan(&u.ID, &u.Username, &pwdHash, &u.UsergroupID, &enable, &fullname, &info)
	if errors.Is(err, sql.ErrNoRows) {
		return "", nil, ErrBadCredential
	}
	if err != nil {
		return "", nil, fmt.Errorf("查询用户: %w", err)
	}

	// 恒定时间比较，避免通过响应时间侧信道推断密码
	if subtle.ConstantTimeCompare([]byte(strings.ToLower(pwdHash)), []byte(MD5Hex(password))) != 1 {
		return "", nil, ErrBadCredential
	}
	if enable != 1 {
		return "", nil, ErrUserDisabled
	}

	u.Fullname = fullname.String
	u.Info = info.String
	u.IsAdmin = u.UsergroupID == 1
	u.ReadOnly = model == 2

	if err := m.loadRights(ctx, u); err != nil {
		return "", nil, err
	}

	token, err := m.issue(u)
	if err != nil {
		return "", nil, err
	}
	return token, u, nil
}

// loadRights 读取用户组的 13 个权限位与 level。
func (m *Manager) loadRights(ctx context.Context, u *User) error {
	var name sql.NullString
	err := m.db.QueryRowContext(ctx, `
		SELECT name, taskpriv, terminalpriv, mediapriv, userpriv, serverpriv, folderpriv,
		       terminalgrouppriv, alarmgrouppriv, bellpriv, admpriv, telephonepriv,
		       powerplay, ttspriv, level
		FROM usergroup WHERE id = ? LIMIT 1`, u.UsergroupID).
		Scan(&name,
			&u.Rights.TaskPriv, &u.Rights.TerminalPriv, &u.Rights.MediaPriv,
			&u.Rights.UserPriv, &u.Rights.ServerPriv, &u.Rights.FolderPriv,
			&u.Rights.TerminalGroupPriv, &u.Rights.AlarmGroupPriv, &u.Rights.BellPriv,
			&u.Rights.AdmPriv, &u.Rights.TelephonePriv, &u.Rights.PowerPlay,
			&u.Rights.TtsPriv, &u.Level)
	if errors.Is(err, sql.ErrNoRows) {
		// 用户组被删但用户还在 —— 旧系统级联删除有缺陷会造成这种情况（D-45）。
		// 这里降级为「无任何权限」，而不是让登录失败。
		u.UsergroupName = "(用户组已删除)"
		return nil
	}
	if err != nil {
		return fmt.Errorf("查询用户组权限: %w", err)
	}
	u.UsergroupName = name.String
	return nil
}

// issue 签发一个不可伪造的会话令牌。
// 格式: base64(random16).hex(hmac_sha256(random16))
func (m *Manager) issue(u *User) (string, error) {
	raw := make([]byte, 16)
	if _, err := rand.Read(raw); err != nil {
		return "", fmt.Errorf("生成令牌: %w", err)
	}
	id := base64.RawURLEncoding.EncodeToString(raw)
	mac := hmac.New(sha256.New, m.secret)
	mac.Write([]byte(id))
	token := id + "." + hex.EncodeToString(mac.Sum(nil))

	m.mu.Lock()
	m.sessions[id] = &session{user: u, expiresAt: time.Now().Add(m.ttl)}
	m.mu.Unlock()
	return token, nil
}

// Resolve 校验令牌并返回会话主体。
func (m *Manager) Resolve(token string) (*User, bool) {
	id, sig, ok := strings.Cut(token, ".")
	if !ok {
		return nil, false
	}
	mac := hmac.New(sha256.New, m.secret)
	mac.Write([]byte(id))
	if !hmac.Equal([]byte(sig), []byte(hex.EncodeToString(mac.Sum(nil)))) {
		return nil, false
	}

	m.mu.RLock()
	s, exists := m.sessions[id]
	m.mu.RUnlock()
	if !exists || time.Now().After(s.expiresAt) {
		return nil, false
	}
	return s.user, true
}

func (m *Manager) Logout(token string) {
	if id, _, ok := strings.Cut(token, "."); ok {
		m.mu.Lock()
		delete(m.sessions, id)
		m.mu.Unlock()
	}
}

// InvalidateAll 让全部会话立即失效，所有人需要重新登录。
//
// 恢复备份之后必须调用（BR-277）：整库数据被换成了备份里的内容，
// 现有会话里缓存的用户与权限可能指向已经不存在的账号 ——
// 旧版恢复完只发一条 server?state=1，会话照旧（D-236）。
func (m *Manager) InvalidateAll() int {
	m.mu.Lock()
	defer m.mu.Unlock()
	n := len(m.sessions)
	m.sessions = make(map[string]*session)
	return n
}

// Refresh 重新从数据库加载权限。
// 旧系统把权限快照存会话，用户组权限改了要重新登录才生效（D-2）。
func (m *Manager) Refresh(ctx context.Context, u *User) error {
	var model int
	if err := m.db.QueryRowContext(ctx, `SELECT model FROM serverbaseparam LIMIT 1`).Scan(&model); err == nil {
		u.ReadOnly = model == 2
	}
	return m.loadRights(ctx, u)
}

// ---------- HTTP 中间件 ----------

type ctxKey int

const userKey ctxKey = 1

// HeaderToken 是前端 Geeker-Admin 约定的鉴权头，不可更改。
const HeaderToken = "x-access-token"

// Require 要求已登录。令牌只从请求头读取。
func (m *Manager) Require(next http.HandlerFunc) http.HandlerFunc {
	return m.require(next, false)
}

// RequireAllowQueryToken 除请求头外，额外接受 ?token= 查询参数。
//
// 仅用于媒体流与下载这两个路由：浏览器的 <audio src> 与 window.open
// 无法携带自定义请求头，只能把令牌放在 URL 里。
//
// ⚠️ URL 中的令牌可能出现在访问日志、浏览器历史与 Referer 中，
// 因此绝不对其它接口开放此入口。后续可改为「短时效一次性媒体票据」进一步收敛。
func (m *Manager) RequireAllowQueryToken(next http.HandlerFunc) http.HandlerFunc {
	return m.require(next, true)
}

func (m *Manager) require(next http.HandlerFunc, allowQuery bool) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		token := r.Header.Get(HeaderToken)
		if token == "" && allowQuery {
			token = r.URL.Query().Get("token")
		}
		u, ok := m.Resolve(token)
		if !ok {
			writeJSON(w, map[string]interface{}{
				"code": 401, "msg": "登录已过期，请重新登录", "data": nil,
			})
			return
		}
		next(w, r.WithContext(context.WithValue(r.Context(), userKey, u)))
	}
}

// RequireRight 在已登录基础上再校验功能权限。
//
// 这是对旧系统最重要的安全修复之一：旧版权限只在渲染层用 JS 置灰链接，
// do.php 的动作接口不复检，构造 URL 即可越权（D-06）。
func (m *Manager) RequireRight(priv string, next http.HandlerFunc) http.HandlerFunc {
	return m.Require(func(w http.ResponseWriter, r *http.Request) {
		u := From(r.Context())
		if u.ReadOnly {
			writeJSON(w, map[string]interface{}{
				"code": 40302, "msg": "服务器处于备机模式，系统只读", "data": nil,
			})
			return
		}
		if !u.HasRight(priv) {
			writeJSON(w, map[string]interface{}{
				"code": 40301, "msg": "权限不足", "data": nil,
			})
			return
		}
		next(w, r)
	})
}

// RequireRightAllowReadOnly 与 RequireRight 相同，但**不受备机模式限制**。
//
// ⚠ 只给「服务器信息」那一组接口用，别扩大范围。
//
// 备机模式（serverbaseparam.model = 2）会把全站变成只读，这是对的 ——
// 备机上的业务数据由主机同步过来，在备机上改任务、改终端毫无意义。
//
// 但「服务器信息」本身必须是例外，否则会锁死自己：
// 主备模式这个开关就在这一页上，一旦切到备机，连读都读不到这一页，
// **再也没有任何界面能把它改回主服务器**，只能进数据库手工改。
// 现网就是这个状态，用户也是因此发现的。
//
// 所以：备机模式下这一页照常可读可改，其余模块仍然只读。
func (m *Manager) RequireRightAllowReadOnly(priv string, next http.HandlerFunc) http.HandlerFunc {
	return m.Require(func(w http.ResponseWriter, r *http.Request) {
		u := From(r.Context())
		if !u.hasRightIgnoringReadOnly(priv) {
			writeJSON(w, map[string]interface{}{
				"code": 40301, "msg": "权限不足", "data": nil,
			})
			return
		}
		next(w, r)
	})
}

// From 从请求上下文取出当前用户。仅在 Require 之后调用。
func From(ctx context.Context) *User {
	u, _ := ctx.Value(userKey).(*User)
	return u
}

func writeJSON(w http.ResponseWriter, v interface{}) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.WriteHeader(http.StatusOK)
	_ = json.NewEncoder(w).Encode(v)
}
