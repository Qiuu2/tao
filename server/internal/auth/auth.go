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
	// ⚠ 列名叫 telephonepriv，装的是**led播放**的权限位。
	// 旧版这一列管「电话广播」，新 web 没有这一页，列却不能删（表结构不动，R1 红线）。
	// led播放 原本挤在 taskpriv 里，和文件广播共用一把钥匙；用户要求 LED 单独一项权限，
	// 就把这根空着的柱子改挂 LED —— 与 serverpriv 装「遥控管理」是同一类历史包袱。
	// 字段名跟列名走（读写 SQL 时一眼能对上），语义看 PrivLed 这个常量。
	TelephonePriv int `json:"telephonepriv"`
	PowerPlay     int `json:"powerplay"`
	TtsPriv       int `json:"ttspriv"`
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
	// PrivLed 是 led播放 的权限位。列名是旧版留下的 telephonepriv，见 Rights 上的说明。
	PrivLed       = "telephonepriv"
	PrivPowerPlay = "powerplay"
	PrivTts       = "ttspriv"
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
	case PrivLed:
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

	// keyAuth 认一把开发者密钥，返回它归属的账号。
	//
	// ⚠ 用函数字段而不是直接调 openapi 包：openapi 依赖 auth（它要返回
	// *auth.User），反过来 import 就成环了。启动时由 main 装进来；
	// 没装的时候恒为 nil，行为与从前完全一致。
	keyAuth func(ctx context.Context, plain string) (*User, string, error)
}

// SetKeyAuth 装上开发者密钥的认证函数。启动时调用一次。
func (m *Manager) SetKeyAuth(fn func(ctx context.Context, plain string) (*User, string, error)) {
	m.keyAuth = fn
}

// HeaderAPIKey 是开发者密钥的请求头名。
//
// ⚠ 与 openapi.HeaderAPIKey 必须是同一个值，但这里不能 import 那个包
// （会成环）。openapi 包里有一个测试盯着这两个常量是否一致。
const HeaderAPIKey = "X-API-Key"

// viaKeyCtx 标记「这个请求是用密钥认证的」，值是那把密钥的前缀。
type viaKeyCtx struct{}

// ViaAPIKey 报告这个请求是不是用开发者密钥认证的，以及是哪一把（前缀）。
//
// ⚠ 只有在**鉴权中间件跑过之后**才有值。要在中间件之前判断，用 WillUseAPIKey。
func ViaAPIKey(ctx context.Context) (prefix string, ok bool) {
	v, ok := ctx.Value(viaKeyCtx{}).(string)
	return v, ok
}

// WillUseAPIKey 预判这个请求会不会走密钥这条路。
//
// # 为什么需要「预判」这么个东西
//
// 路由层要挡住「用密钥调不开放的接口」，而那一层跑在鉴权中间件**之前**，
// 那时候上下文里还什么都没有。第一版就栽在这儿：keyGate 去问 ViaAPIKey，
// 永远拿到 false，于是恢复出厂、发密钥、助手对话全都能用密钥调通 ——
// 挡板装了，但装在了它要挡的东西前面。
//
// 判断规则必须与 require 里的**完全一致**（有会话令牌就走会话，
// 没有才看密钥），所以两处都从这一个函数出发，不各写各的。
func WillUseAPIKey(r *http.Request) bool {
	if strings.TrimSpace(r.Header.Get(HeaderToken)) != "" {
		return false
	}
	return strings.TrimSpace(r.Header.Get(HeaderAPIKey)) != ""
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

// UserByID 按账号 id 装配一个完整的 User（含 13 个权限位）。
//
// # 给谁用
//
// 开发者接口（internal/openapi）：一把 api_key 挂在某个账号下，
// 认证通过之后要把那个账号的**完整权限**装出来，才能套用同一套权限检查。
//
// # 为什么不在 openapi 里自己查一遍
//
// 权限位有 13 个，还有 IsAdmin / ReadOnly / 用户组被删时降级这些规矩。
// 抄一份到别处，就有了两个真相 —— 而它们对不齐的那天，
// 表现是「界面上没权限、接口却能干」。所以只此一处。
//
// ⚠ 与 Login 的区别只有一条：**不验密码、不签发会话**。
// 停用的账号一样拒掉 —— 停用一个人之后，他名下的密钥就该跟着失效。
func (m *Manager) UserByID(ctx context.Context, id int64) (*User, error) {
	var model int
	if err := m.db.QueryRowContext(ctx,
		`SELECT model FROM serverbaseparam LIMIT 1`).Scan(&model); err != nil {
		return nil, fmt.Errorf("读取服务器参数: %w", err)
	}

	u := &User{}
	var enable int
	var fullname, info sql.NullString
	err := m.db.QueryRowContext(ctx, `
		SELECT id, username, usergroupid, enable, fullname, info
		FROM book_admin WHERE id = ? LIMIT 1`, id).
		Scan(&u.ID, &u.Username, &u.UsergroupID, &enable, &fullname, &info)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrBadCredential
	}
	if err != nil {
		return nil, fmt.Errorf("查询用户: %w", err)
	}
	if enable != 1 {
		return nil, ErrUserDisabled
	}

	u.Fullname = fullname.String
	u.Info = info.String
	u.IsAdmin = u.UsergroupID == 1
	u.ReadOnly = model == 2
	if err := m.loadRights(ctx, u); err != nil {
		return nil, err
	}
	return u, nil
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
		// ⚠ 这里的取舍顺序（先会话、后密钥）就是 WillUseAPIKey 判断的依据，
		//   两处必须一致，改一处要改两处。
		token := r.Header.Get(HeaderToken)
		if token == "" && allowQuery {
			token = r.URL.Query().Get("token")
		}
		if u, ok := m.Resolve(token); ok {
			next(w, r.WithContext(context.WithValue(r.Context(), userKey, u)))
			return
		}

		// 没有有效会话时再看开发者密钥。
		//
		// # 为什么让密钥走同一条中间件，而不是另建一套路由
		//
		// 「用密钥能做什么」必须与「这个账号在界面上能做什么」完全一致。
		// 另建一套路由就意味着另一套鉴权代码，两套迟早会分叉 ——
		// 分叉的表现是**接口上比界面多一条口子**，而没有人会注意到。
		// 同一条中间件、同一个 *User，权限位与可见范围自然就是一致的。
		//
		// 「哪些接口允许密钥调」是另一件事（暴露面），由路由层的开放清单
		// 决定，见 cmd/htweb 的 keyGate。身份归身份，暴露面归暴露面。
		if key := strings.TrimSpace(r.Header.Get(HeaderAPIKey)); key != "" && m.keyAuth != nil {
			u, prefix, err := m.keyAuth(r.Context(), key)
			if err == nil && u != nil {
				ctx := context.WithValue(r.Context(), userKey, u)
				ctx = context.WithValue(ctx, viaKeyCtx{}, prefix)
				next(w, r.WithContext(ctx))
				return
			}
			// ⚠ 对外恒定一句话。真实原因（不存在 / 已停用 / 已过期 /
			// 归属账号被停用）由 keyAuth 写进服务端日志 ——
			// 对着接口试密钥的人不该从错误信息里学到任何东西。
			writeJSON(w, map[string]interface{}{
				"code": 401, "msg": "密钥无效", "data": nil,
			})
			return
		}

		writeJSON(w, map[string]interface{}{
			"code": 401, "msg": "登录已过期，请重新登录", "data": nil,
		})
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

// WithUser 把身份放进请求上下文。
//
// # 给谁用
//
// 开发者接口（X-API-Key）自己做认证，做完之后要把装配好的 User 交给
// 后面的 handler —— 而 handler 一律用 From(ctx) 取身份，不管前面是哪条认证路径。
//
// ⚠ 上下文键 userKey 是**包私有**的，这是有意的：只有 auth 能往里放身份，
// 别处想伪造一个"已登录用户"塞进上下文就得先过这个函数。
// 所以这个入口只开给同一进程里已经完成认证的调用方。
func WithUser(ctx context.Context, u *User) context.Context {
	return context.WithValue(ctx, userKey, u)
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
