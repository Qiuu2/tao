// Package terminal 实现终端管理（手册业务域六，F-25 ~ F-31）。
//
// # 对旧系统的关键修复
//
//   - D-74 分页被整体注释掉，一次性渲染全部终端（上限 3000 行 × 20 列）→ 恢复服务端分页
//   - D-75/D-79 排序在前端 JS 做且服务端又排一次 → 单一排序来源，下沉到 SQL 白名单
//   - D-76 管理员判定用 username == "admin" 字符串比较 → 统一 usergroupid = 1
//   - D-77 非管理员分支拿用户名绕子查询 → 直接绑定会话里的 userId
//   - D-78 搜索分支丢掉可见范围过滤，普通用户一搜索就能看到全系统终端 → 条件提为公共片段
//
// # 两套分区关系并存
//
// terminal.groupid 与 terminalofgroup 表都记录「终端属于哪个分区」，
// 旧代码一部分读前者、一部分读后者。列表查询走 terminalofgroup，
// 而旧的修改逻辑只写 terminal.groupid（缺陷 D-85）——
// 结果就是改完分区，列表里看不到变化。新版读以 terminalofgroup 为准，
// 写的时候两者同时维护（契约 C-23）。
package terminal

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/store"
)

var (
	ErrNotFound     = errors.New("终端不存在")
	ErrNoPermission = errors.New("无权操作该终端")
)

// TypeServer 是「服务器」类型。终端列表恒排除它（BR-137）。
const TypeServer = 0

// 分区树的两个虚拟节点（BR-138）。
const (
	GroupAll        = 0  // 全部终端
	GroupUnassigned = -1 // 未分区终端
)

type Service struct {
	db *sql.DB
}

func New(db *sql.DB) *Service { return &Service{db: db} }

// Item 是终端列表行。
type Item struct {
	ID           int64   `json:"id"`
	TerminalName string  `json:"terminalname"`
	TypeID       int     `json:"typeid"`
	TypeName     string  `json:"typeName"`
	GroupID      int64   `json:"groupId"`
	GroupName    string  `json:"groupName"`
	NetState     int     `json:"netstate"`
	DeviceState  int     `json:"devicestate"`
	TaskState    int     `json:"taskstate"`
	IP           string  `json:"ip"`
	Volume       int     `json:"volume"`
	IsSpeech     int     `json:"isspeech"`
	Instancy     int     `json:"instancy"`
	IsRecord     int     `json:"isrecord"`
	IsSponsor    int     `json:"issponsor"`
	IsSelectCall int     `json:"isselectcall"`
	LOpenCircuit int     `json:"lopencircuit"`
	ROpenCircuit int     `json:"ropencircuit"`
	Temperature  float64 `json:"temperature"`
	Humidity     float64 `json:"humidity"`
	// Postion 列名如此（旧库拼写），列字符集是 gbk。
	// 注意现网这一列存的其实是后台服务写入的固件版本串，不是位置。
	Postion string `json:"postion"`
	// HasShortcutKey 该终端是否配了快捷键。
	// terminalkey.terminalid 是 varchar(45) 而不是 int，比较时要当字符串处理。
	HasShortcutKey bool `json:"hasShortcutKey"`
	// Online / WebURL 供「浏览」按钮用（BR-140）
	Online bool   `json:"online"`
	WebURL string `json:"webUrl"`
	// CanDecode / CanEncode 来自 terminaltype，决定哪些操作对该终端有意义
	CanDecode bool `json:"canDecode"`
	CanEncode bool `json:"canEncode"`
	// Caps 是按 ok112 的 get_terminal_type() 算出来的能力集：
	// 这台终端支持哪几项批量操作。界面据此置灰菜单项，见 caps.go。
	Caps Caps `json:"caps"`
}

type ListResult struct {
	Items     []Item `json:"-"`
	Total     int64  `json:"-"`
	ScopeNote string `json:"scopeNote"`
}

// 排序白名单。默认按网络状态（在线优先，BR-141）。
var orderWhitelist = map[string]string{
	"netstate":     "t.netstate",
	"terminalname": "t.terminalname",
	"typeid":       "t.typeid",
	"taskstate":    "t.taskstate",
	"devicestate":  "t.devicestate",
	"ip":           "t.ip",
	"volume":       "t.volume",
	"id":           "t.id",
}

var searchWhitelist = map[string]string{
	"terminalname": "t.terminalname",
	"ip":           "t.ip",
}

type ListQuery struct {
	GroupID   int64
	Category  string
	SearchKey string
	Keyword   string
	OrderBy   string
	Order     string
	Pager     store.Pager
}

// categoryCond 是终端列表页签（全部 / 解码终端 / 采集终端 / …）的筛选条件。
//
// ⚠ 这几个页签的分类**不在数据库里**：terminaltype 表没有「类别」列，
// 旧版 PHP 也没有这套页签（它是 :80 那套界面上的）。所以这里按
// terminaltype 的能力位来划分——这是库里唯一能说明「这台设备能干什么」的依据：
//
//	解码终端  isdecode = 1    能收流播音的（网络终端、功放、音箱……）
//	采集终端  isencode = 1    能出流的（话筒、采样终端、编码器……）
//	话筒      名字里带「话筒 / 寻呼」的型号
//	遥控终端  名字里带「遥控」的型号（现网是 typeid = 29）
//	报警终端  名字里带「报警」的型号（现网是 typeid = 7 报警主机）
//	对讲终端  isspeech = 1
//	扩展设备  isencode = 0 且 isdecode = 0 —— 既不收也不发的附属设备
//	          （电源管理器、报警主机、小区广播主机、电脑、MP3……）
//
// 按名字匹配看着糙，但 terminaltype 里同名型号有好几个 id
// （「网络前置」占了 4、34、39、47 四个 id），写死 id 清单反而更容易漏。
// 这套划分只影响页签怎么分，不影响任何写库行为。
func categoryCond(cat string) string {
	switch cat {
	case "decode":
		return "COALESCE(tt.isdecode,0) = 1"
	case "encode":
		return "COALESCE(tt.isencode,0) = 1"
	case "mic":
		return "(tt.name LIKE '%话筒%' OR tt.name LIKE '%寻呼%')"
	case "remote":
		return "tt.name LIKE '%遥控%'"
	case "alarm":
		return "tt.name LIKE '%报警%'"
	case "speech":
		return "COALESCE(tt.isspeech,0) = 1"
	case "ext":
		return "COALESCE(tt.isencode,0) = 0 AND COALESCE(tt.isdecode,0) = 0"
	default: // "" / "all" / 任何不认识的值一律不过滤
		return ""
	}
}

// visibleCond 是终端可见范围的唯一权威定义（BR-139）。
//
// 旧代码把这个条件散在「按分区」和「按搜索」两个分支里各写一遍，
// 搜索分支忘了写（缺陷 D-78）—— 普通用户平时只看得到自己的终端，
// 一点搜索就能列出全系统所有终端。这里提成一处，所有分支强制附加。
func visibleCond(u *auth.User) (string, []interface{}) {
	if u.IsAdmin {
		return "", nil
	}
	return "t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)", []interface{}{u.ID}
}

// groupCond 把分区选择翻译成 WHERE 片段（BR-138）。
func groupCond(groupID int64) (string, []interface{}) {
	switch groupID {
	case GroupAll:
		return "", nil
	case GroupUnassigned:
		return "t.id NOT IN (SELECT terminalid FROM terminalofgroup)", nil
	default:
		return "t.id IN (SELECT terminalid FROM terminalofgroup WHERE groupid = ?)", []interface{}{groupID}
	}
}

func (s *Service) List(ctx context.Context, u *auth.User, q ListQuery) (*ListResult, error) {
	cond := &store.Cond{}
	// 列表恒排除「服务器」类型（BR-137）
	cond.Add("t.typeid <> ?", TypeServer)

	if c, args := groupCond(q.GroupID); c != "" {
		cond.Add(c, args...)
	}
	if c, args := visibleCond(u); c != "" {
		cond.Add(c, args...)
	}
	if c := categoryCond(q.Category); c != "" {
		cond.Add(c)
	}
	if col, ok := searchWhitelist[q.SearchKey]; ok && q.Keyword != "" {
		cond.Add(col+" LIKE ? ESCAPE '\\\\'", store.EscapeLike(q.Keyword))
	}

	where := cond.Where()
	args := cond.Args()

	// COUNT 也带上 terminaltype 的 JOIN —— 页签条件落在 tt 上，
	// 少了这个 JOIN 一切页签都会报 Unknown column。JOIN 是 1:1 的（tt.id 主键），
	// 不会把行数放大。
	var total int64
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM terminal t
		 LEFT JOIN terminaltype tt ON tt.id = t.typeid`+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计终端数: %w", err)
	}

	order := store.OrderBy(orderWhitelist, q.OrderBy, q.Order, "t.netstate DESC, t.id ASC")

	// 分区名走 terminalofgroup → serverplaystream。
	// 用相关子查询而不是 JOIN：一个终端可能挂在多个分区上，
	// JOIN 会把它拆成多行，分页数和 COUNT 立刻对不上。
	listSQL := `
		SELECT t.id, COALESCE(t.terminalname,''), t.typeid, COALESCE(tt.name,''),
		       COALESCE((SELECT tog.groupid FROM terminalofgroup tog
		                  WHERE tog.terminalid = t.id ORDER BY tog.id LIMIT 1), 0),
		       COALESCE(t.netstate,0), COALESCE(t.devicestate,0), COALESCE(t.taskstate,0),
		       COALESCE(t.ip,''), COALESCE(t.volume,0),
		       COALESCE(t.isspeech,0), COALESCE(t.instancy,0), COALESCE(t.isrecord,0),
		       COALESCE(t.issponsor,0), COALESCE(t.isselectcall,0),
		       COALESCE(t.lopencircuit,0), COALESCE(t.ropencircuit,0),
		       COALESCE(t.temperature,0), COALESCE(t.humidity,0),
		       COALESCE(t.postion,''),
		       COALESCE(tt.isdecode,0), COALESCE(tt.isencode,0),
		       COALESCE(tt.isLCD,0), COALESCE(tt.isspeech,0),
		       COALESCE(tt.shortkeycount,0), COALESCE(tt.switchcount,0)
		FROM terminal t
		LEFT JOIN terminaltype tt ON tt.id = t.typeid` + where +
		" ORDER BY " + order + " LIMIT ? OFFSET ?"

	listArgs := append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, listSQL, listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询终端列表: %w", err)
	}
	defer rs.Close()

	items := make([]Item, 0, q.Pager.PageSize)
	ids := make([]int64, 0, q.Pager.PageSize)
	groupIDs := map[int64]bool{}
	for rs.Next() {
		var it Item
		var isdecode, isencode, isLCD, isspeech, shortkeys, switches int
		if err := rs.Scan(&it.ID, &it.TerminalName, &it.TypeID, &it.TypeName,
			&it.GroupID, &it.NetState, &it.DeviceState, &it.TaskState,
			&it.IP, &it.Volume, &it.IsSpeech, &it.Instancy, &it.IsRecord,
			&it.IsSponsor, &it.IsSelectCall, &it.LOpenCircuit, &it.ROpenCircuit,
			&it.Temperature, &it.Humidity, &it.Postion,
			&isdecode, &isencode, &isLCD, &isspeech, &shortkeys, &switches); err != nil {
			return nil, fmt.Errorf("扫描终端行: %w", err)
		}
		it.CanDecode, it.CanEncode = isdecode == 1, isencode == 1
		// 按 ok112 的 get_terminal_type() 规则算出这台终端支持哪些批量操作，
		// 界面据此把菜单里不适用的项置灰。规则见 caps.go。
		it.Caps = CapsOf(TypeTraits{
			TypeID:        it.TypeID,
			IsDecode:      it.CanDecode,
			IsEncode:      it.CanEncode,
			IsLCD:         isLCD >= 1,
			IsSpeech:      isspeech == 1,
			ShortKeyCount: shortkeys,
			SwitchCount:   switches,
		})
		it.Online = it.NetState == 1
		if it.Online && it.IP != "" {
			it.WebURL = "http://" + it.IP
		}
		items = append(items, it)
		ids = append(ids, it.ID)
		if it.GroupID > 0 {
			groupIDs[it.GroupID] = true
		}
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}

	if err := s.fillGroupNames(ctx, items, groupIDs); err != nil {
		return nil, err
	}
	if err := s.fillShortcutKeys(ctx, items, ids); err != nil {
		return nil, err
	}

	res := &ListResult{Items: items, Total: total}
	if !u.IsAdmin {
		res.ScopeNote = "仅显示绑定给我的终端"
	}
	return res, nil
}

// fillGroupNames 一次把用到的分区名查出来再回填，避免每行一次查询。
func (s *Service) fillGroupNames(ctx context.Context, items []Item, ids map[int64]bool) error {
	if len(ids) == 0 {
		return nil
	}
	list := make([]int64, 0, len(ids))
	for id := range ids {
		list = append(list, id)
	}
	ph, args := placeholders(list)
	rs, err := s.db.QueryContext(ctx,
		`SELECT streamid, COALESCE(name,'') FROM serverplaystream WHERE streamid IN (`+ph+`)`, args...)
	if err != nil {
		return fmt.Errorf("查询分区名: %w", err)
	}
	defer rs.Close()

	names := make(map[int64]string, len(list))
	for rs.Next() {
		var id int64
		var name string
		if err := rs.Scan(&id, &name); err != nil {
			return err
		}
		names[id] = name
	}
	if err := rs.Err(); err != nil {
		return err
	}
	for i := range items {
		if items[i].GroupID == 0 {
			items[i].GroupName = "(未分区)"
			continue
		}
		if n, ok := names[items[i].GroupID]; ok {
			items[i].GroupName = n
		} else {
			// terminalofgroup 指向了已不存在的分区，旧数据里确实有
			items[i].GroupName = "(分区已删除)"
		}
	}
	return nil
}

// fillShortcutKeys 标记哪些终端配了快捷键。
//
// terminalkey.terminalid 是 varchar(45)，不是 int ——
// 直接拿整型 ID 去 IN 比较会触发隐式类型转换，索引失效且语义含糊，
// 所以这里把 ID 转成字符串再绑定。
func (s *Service) fillShortcutKeys(ctx context.Context, items []Item, ids []int64) error {
	if len(ids) == 0 {
		return nil
	}
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		args[i] = fmt.Sprintf("%d", id)
	}
	ph := strings.TrimSuffix(strings.Repeat("?,", len(ids)), ",")
	rs, err := s.db.QueryContext(ctx,
		`SELECT DISTINCT terminalid FROM terminalkey WHERE terminalid IN (`+ph+`)`, args...)
	if err != nil {
		return fmt.Errorf("查询快捷键: %w", err)
	}
	defer rs.Close()

	has := make(map[string]bool)
	for rs.Next() {
		var tid string
		if err := rs.Scan(&tid); err != nil {
			return err
		}
		has[strings.TrimSpace(tid)] = true
	}
	if err := rs.Err(); err != nil {
		return err
	}
	for i := range items {
		items[i].HasShortcutKey = has[fmt.Sprintf("%d", items[i].ID)]
	}
	return nil
}

// ---------- 分区树 ----------

// GroupNode 是左侧分区树的节点。
type GroupNode struct {
	ID    int64  `json:"id"`
	Name  string `json:"name"`
	Info  string `json:"info"`
	Count int64  `json:"count"`
	// Virtual 标记「全部」「未分区」这两个不对应真实分区记录的节点
	Virtual bool `json:"virtual"`
}

// GroupTree 返回分区列表，头部固定两个虚拟节点（BR-138）。
//
// serverplaystream.userid 是分区归属人，非 admin 只看自己的分区。
func (s *Service) GroupTree(ctx context.Context, u *auth.User) ([]GroupNode, error) {
	counts, err := s.groupCounts(ctx, u)
	if err != nil {
		return nil, err
	}

	q := `SELECT streamid, COALESCE(name,''), COALESCE(info,'') FROM serverplaystream`
	var args []interface{}
	if !u.IsAdmin {
		q += ` WHERE userid = ?`
		args = append(args, u.ID)
	}
	q += ` ORDER BY streamid ASC`

	rs, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询终端分区: %w", err)
	}
	defer rs.Close()

	out := []GroupNode{
		{ID: GroupAll, Name: "全部终端", Virtual: true, Count: counts[GroupAll]},
		{ID: GroupUnassigned, Name: "未分区终端", Virtual: true, Count: counts[GroupUnassigned]},
	}
	for rs.Next() {
		var n GroupNode
		if err := rs.Scan(&n.ID, &n.Name, &n.Info); err != nil {
			return nil, err
		}
		n.Count = counts[n.ID]
		out = append(out, n)
	}
	return out, rs.Err()
}

// groupCounts 一次算出每个分区的终端数，外加「全部」「未分区」两个合计。
func (s *Service) groupCounts(ctx context.Context, u *auth.User) (map[int64]int64, error) {
	vis, visArgs := visibleCond(u)
	base := "t.typeid <> ?"
	args := []interface{}{TypeServer}
	if vis != "" {
		base += " AND " + vis
		args = append(args, visArgs...)
	}

	counts := make(map[int64]int64)

	// 分区维度
	rs, err := s.db.QueryContext(ctx, `
		SELECT tog.groupid, COUNT(DISTINCT t.id)
		FROM terminal t JOIN terminalofgroup tog ON tog.terminalid = t.id
		WHERE `+base+` GROUP BY tog.groupid`, args...)
	if err != nil {
		return nil, fmt.Errorf("统计分区终端数: %w", err)
	}
	defer rs.Close()
	for rs.Next() {
		var gid, n int64
		if err := rs.Scan(&gid, &n); err != nil {
			return nil, err
		}
		counts[gid] = n
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}

	var all, un int64
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM terminal t WHERE `+base, args...).Scan(&all); err != nil {
		return nil, fmt.Errorf("统计全部终端数: %w", err)
	}
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM terminal t WHERE `+base+
			` AND t.id NOT IN (SELECT terminalid FROM terminalofgroup)`, args...).Scan(&un); err != nil {
		return nil, fmt.Errorf("统计未分区终端数: %w", err)
	}
	counts[GroupAll], counts[GroupUnassigned] = all, un
	return counts, nil
}

// ---------- 字典 ----------

// TypeOption 是终端类型下拉项。
type TypeOption struct {
	ID       int    `json:"id"`
	Name     string `json:"name"`
	IsDecode bool   `json:"isDecode"`
	IsEncode bool   `json:"isEncode"`
}

// TypeOptions 返回终端类型字典，排除「服务器」。
func (s *Service) TypeOptions(ctx context.Context) ([]TypeOption, error) {
	rs, err := s.db.QueryContext(ctx, `
		SELECT id, COALESCE(name,''), isdecode, isencode
		FROM terminaltype WHERE id <> ? AND isWeb = 1 ORDER BY id ASC`, TypeServer)
	if err != nil {
		return nil, fmt.Errorf("查询终端类型: %w", err)
	}
	defer rs.Close()

	out := []TypeOption{}
	for rs.Next() {
		var o TypeOption
		var d, e int
		if err := rs.Scan(&o.ID, &o.Name, &d, &e); err != nil {
			return nil, err
		}
		o.IsDecode, o.IsEncode = d == 1, e == 1
		out = append(out, o)
	}
	return out, rs.Err()
}

func placeholders(ids []int64) (string, []interface{}) {
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		args[i] = id
	}
	return strings.TrimSuffix(strings.Repeat("?,", len(ids)), ","), args
}
