// Package openapi 实现开发者接口（业务域十五）。
//
// # 它是什么
//
// 给**第三方程序**调的一层 HTTP 接口：告警平台、门禁、教务系统直接调 htweb
// 下发广播，不经过人、不经过界面。
//
// # 与 /api 的区别，只有三条
//
//	凭据    /api 用登录会话（内存里、8 小时过期、重启即失效）
//	        这里用 api_key（落库、长期有效、能单独吊销）
//	寻址    /api 收内部 id（界面自己先查过了，它知道 mediaId=127）
//	        这里名字优先、id 也认（第三方只知道「上课铃.mp3」）
//	路径    /openapi/v1/…，与 /api 分开
//
// **业务逻辑一行都不重写** —— 全部转身调页面用的那几个 service
// （task / bell / terminal / zone / media）。理由与 AI 助手那一层完全一样：
// 那些 service 里已经把守卫和通知报文做全了，绕过去等于把它们悄悄丢掉。
//
// # 路径为什么要单独分一层
//
// /api 是**界面的**接口，它会跟着界面改：加一个筛选条件、换一种分页、
// 把两个接口合成一个，都是正常演进。第三方接进来之后这些改动就成了破坏性变更。
//
// 分开之后，/openapi/v1 的形状是一份**对外承诺**：只增不改，
// 真要改就出 v2，v1 留着。界面那边照旧随便改。
//
// # 权限不另起一套
//
// 每把密钥挂在一个 book_admin 账号下，调接口时**完全套用那个人的权限位**。
// 想给第三方多大权限，就在用户管理里给那个账号配多大 —— 一处配置两处生效。
//
// 这条是有意的：另起一套「接口权限」意味着两套东西要对齐，
// 而对不齐的那一天，表现是「界面上没权限、接口却能干」。
package openapi

import (
	"database/sql"
	"log"

	"htweb/internal/auth"
	"htweb/internal/bell"
	"htweb/internal/enable"
	"htweb/internal/media"
	"htweb/internal/notify"
	"htweb/internal/task"
	"htweb/internal/terminal"
	"htweb/internal/zone"
)

// Service 是开发者接口的门面。它自己不写业务，只转身调下面这些。
type Service struct {
	db *sql.DB
	// authMgr 只用来把 api_key 归属的账号装配成完整的 User（含 13 个权限位）。
	// 权限规则只有 auth 一处真相，这里不抄第二份。
	authMgr *auth.Manager

	tasks     *task.Service
	bells     *bell.Service
	terminals *terminal.Service
	zones     *zone.Service
	medias    *media.Service
	enables   *enable.Service
	notifier  *notify.Notifier
}

func New(db *sql.DB, authMgr *auth.Manager) *Service {
	return &Service{db: db, authMgr: authMgr}
}

// Attach 把页面用的那几个 service 接进来。
// 与助手那边一样，单独一个方法是因为构造顺序上它们在这之后才建好。
func (s *Service) Attach(t *task.Service, b *bell.Service, term *terminal.Service,
	z *zone.Service, m *media.Service, e *enable.Service, n *notify.Notifier) {
	s.tasks, s.bells, s.terminals = t, b, term
	s.zones, s.medias, s.enables = z, m, e
	s.notifier = n
}

// logf 是这一层的日志。开发者接口出问题时，**给对方的话要少、进日志的要全** ——
// 对着接口试密钥的人不该从错误信息里学到东西，但运维要能查出发生了什么。
func logf(format string, args ...any) {
	log.Printf("openapi: "+format, args...)
}
