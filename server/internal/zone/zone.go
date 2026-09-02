// Package zone 实现终端分区（旧版叫 StreamManager / 播放流分区）。
//
// # 分区存在哪
//
//	serverplaystream (streamid, name, info, createtime, userid)  ← 分区定义
//	terminalofgroup  (id, terminalid, groupid)                    ← 成员，groupid = streamid
//
// 注意别被表名骗了：库里另有一张 `terminalgroup` 表，字段看着更像「终端分区」，
// 但旧版全站只在恢复出厂时 `DELETE FROM terminalgroup` 提到过它，从不读写。
// 真正在用的是 `serverplaystream`。
//
// # 一台终端属于哪个分区，库里记了四份
//
//	terminal.groupid                 单值，终端自己记
//	terminalofgroup.groupid          多对多关联
//	terminaloftask.groupid           这台终端在某条任务里的分区号
//	terminalofalarmgroup.groupid     这台终端在某个报警分区里的分区号
//
// 后两者是后台 C 服务下发时实际读的，所以增删分区必须同步它们，
// 否则任务照着一个已经不存在的分区号去播。旧版做到了后两者，
// **却从头到尾没写过 terminal.groupid**（D-215）——
// 而终端列表页读的正是 terminalofgroup，终端编辑页写的又是 terminal.groupid，
// 三方各说各话。新版四处一起维护，与 terminal 包的契约 C-23 对齐。
//
// # 一台终端只能属于一个分区
//
// terminalofgroup 是多对多、没有唯一键，但 terminal.groupid /
// terminaloftask.groupid 都只装得下一个值。把已属于 A 的终端加进 B，
// 旧版会留下两条成员行而实际归属是 B，A 的成员列表从此虚报。
// 新版在写成员前先把这些终端从别的分区摘掉 —— 与报警分区（BR-197）同样的处理。
//
// # 旧版那个改不动的编辑接口
//
// `streamedit_msg` 更新的是 `feed` / `feedfile` / `outputformat` / `AudioCodec`
// 等一堆列 —— 这些列在 serverplaystream 里**根本不存在**（D-216），
// 这个入口一调必然报 Unknown column。真正在用的是 `streambatedit_msg`。
// 新版只实现后者的语义。
package zone

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strconv"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/store"
)

type Service struct {
	db *sql.DB
}

func New(db *sql.DB) *Service { return &Service{db: db} }

var (
	ErrNotFound     = errors.New("终端分区不存在")
	ErrNoPermission = errors.New("没有权限操作该终端分区")
)

// NoZone 是「未分区」。terminal.groupid 与 terminaloftask.groupid 都用 0 表示。
const NoZone = 0

const (
	nameLimit = 255 // serverplaystream.name  varchar(255)
	infoLimit = 255 // serverplaystream.info  varchar(255)
	maxMember = 3000
)

type Zone struct {
	ID            int64  `json:"id"`
	Name          string `json:"name"`
	Info          string `json:"info"`
	CreateTime    string `json:"createTime"`
	UserID        int64  `json:"userId"`
	UserName      string `json:"userName"`
	TerminalCount int    `json:"terminalCount"`
	// TaskCount 是有多少条任务把终端按这个分区号下发。删除前要提示。
	TaskCount int  `json:"taskCount"`
	CanModify bool `json:"canModify"`
}

type ListResult struct {
	Items     []Zone
	Total     int64
	ScopeNote string
}

var orderWhitelist = map[string]string{
	"name":       "s.name",
	"createtime": "s.createtime",
	"id":         "s.streamid",
}

const defaultOrder = "s.createtime DESC, s.streamid DESC"

type Query struct {
	Keyword string
	OrderBy string
	Order   string
	Pager   store.Pager
}

// visibleCond 是终端分区可见范围的唯一定义。
//
// 旧版 streammanager.php 对非管理员按 userid 过滤，但**搜索分支把这个条件丢了**
// （与终端列表 D-78 同一个毛病）。这里提成公共片段，两条路径都用它。
func visibleCond(u *auth.User) (string, []interface{}) {
	if u.IsAdmin {
		return "", nil
	}
	return "s.userid = ?", []interface{}{u.ID}
}

func (s *Service) List(ctx context.Context, u *auth.User, q Query) (*ListResult, error) {
	cond := &store.Cond{}
	if c, args := visibleCond(u); c != "" {
		cond.Add(c, args...)
	}
	if q.Keyword != "" {
		cond.Add(`s.name LIKE ? ESCAPE '\\'`, store.EscapeLike(q.Keyword))
	}
	where := cond.Where()
	args := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM serverplaystream s"+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计终端分区数: %w", err)
	}

	order := store.OrderBy(orderWhitelist, q.OrderBy, q.Order, defaultOrder)
	listSQL := `
		SELECT s.streamid, COALESCE(s.name,''), COALESCE(s.info,''),
		       COALESCE(DATE_FORMAT(s.createtime,'%Y-%m-%d %H:%i:%s'),''),
		       COALESCE(s.userid,0), COALESCE(b.username,'')
		FROM serverplaystream s
		LEFT JOIN book_admin b ON b.id = s.userid` + where +
		" ORDER BY " + order + " LIMIT ? OFFSET ?"

	listArgs := append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, listSQL, listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询终端分区: %w", err)
	}
	defer rs.Close()

	items := make([]Zone, 0, q.Pager.PageSize)
	ids := make([]int64, 0, q.Pager.PageSize)
	for rs.Next() {
		var z Zone
		if err := rs.Scan(&z.ID, &z.Name, &z.Info, &z.CreateTime, &z.UserID, &z.UserName); err != nil {
			return nil, fmt.Errorf("扫描终端分区行: %w", err)
		}
		z.CanModify = u.IsAdmin || z.UserID == u.ID
		items = append(items, z)
		ids = append(ids, z.ID)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}
	if len(ids) > 0 {
		if err := s.fillCounts(ctx, items, ids); err != nil {
			return nil, err
		}
	}

	res := &ListResult{Items: items, Total: total}
	if !u.IsAdmin {
		res.ScopeNote = "仅显示我创建的终端分区"
	}
	return res, nil
}

// fillCounts 两条聚合查询补齐成员数与任务引用数，不做 N+1。
func (s *Service) fillCounts(ctx context.Context, items []Zone, ids []int64) error {
	members, err := s.countBy(ctx, ids,
		`SELECT groupid, COUNT(*) FROM terminalofgroup WHERE groupid IN (%s) GROUP BY groupid`)
	if err != nil {
		return fmt.Errorf("统计分区成员数: %w", err)
	}
	tasks, err := s.countBy(ctx, ids,
		`SELECT groupid, COUNT(DISTINCT taskid) FROM terminaloftask WHERE groupid IN (%s) GROUP BY groupid`)
	if err != nil {
		return fmt.Errorf("统计分区任务引用数: %w", err)
	}
	for i := range items {
		items[i].TerminalCount = members[items[i].ID]
		items[i].TaskCount = tasks[items[i].ID]
	}
	return nil
}

func (s *Service) countBy(ctx context.Context, ids []int64, tpl string) (map[int64]int, error) {
	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx, fmt.Sprintf(tpl, ph), args...)
	if err != nil {
		return nil, err
	}
	defer rs.Close()
	out := map[int64]int{}
	for rs.Next() {
		var k int64
		var n int
		if err := rs.Scan(&k, &n); err != nil {
			return nil, err
		}
		out[k] = n
	}
	return out, rs.Err()
}

func placeholders(ids []int64) (string, []interface{}) {
	if len(ids) == 0 {
		return "NULL", nil
	}
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		args[i] = id
	}
	return strings.TrimSuffix(strings.Repeat("?,", len(ids)), ","), args
}

// ---------- 详情 ----------

type Member struct {
	TerminalID   int64  `json:"terminalId"`
	TerminalName string `json:"terminalname"`
	TypeName     string `json:"typeName"`
	IP           string `json:"ip"`
	NetState     int    `json:"netstate"`
	Deleted      bool   `json:"deleted"`
}

type Detail struct {
	ID      int64    `json:"id"`
	Name    string   `json:"name"`
	Info    string   `json:"info"`
	UserID  int64    `json:"userId"`
	Members []Member `json:"members"`
}

func (s *Service) Get(ctx context.Context, u *auth.User, id int64) (*Detail, error) {
	d := &Detail{ID: id}
	var userID sql.NullInt64
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(name,''), COALESCE(info,''), userid
		 FROM serverplaystream WHERE streamid = ? LIMIT 1`, id).
		Scan(&d.Name, &d.Info, &userID)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询终端分区: %w", err)
	}
	d.UserID = userID.Int64
	if !u.IsAdmin && d.UserID != u.ID {
		return nil, ErrNoPermission
	}
	ms, err := s.members(ctx, id)
	if err != nil {
		return nil, err
	}
	d.Members = ms
	return d, nil
}

// members 取分区成员。
//
// LEFT JOIN 而不是内连接：终端被删掉后关联行可能还在，
// 内连接会让这些脏行静默消失，页面上看不出分区里挂着一台不存在的设备。
func (s *Service) members(ctx context.Context, zoneID int64) ([]Member, error) {
	rs, err := s.db.QueryContext(ctx, `
		SELECT g.terminalid, t.id IS NOT NULL,
		       COALESCE(t.terminalname,''), COALESCE(tt.name,''),
		       COALESCE(t.ip,''), COALESCE(t.netstate,0)
		FROM terminalofgroup g
		LEFT JOIN terminal t ON t.id = g.terminalid
		LEFT JOIN terminaltype tt ON tt.id = t.typeid
		WHERE g.groupid = ?
		ORDER BY g.id`, zoneID)
	if err != nil {
		return nil, fmt.Errorf("查询分区成员: %w", err)
	}
	defer rs.Close()

	out := []Member{}
	for rs.Next() {
		var m Member
		var exists bool
		if err := rs.Scan(&m.TerminalID, &exists, &m.TerminalName,
			&m.TypeName, &m.IP, &m.NetState); err != nil {
			return nil, err
		}
		m.Deleted = !exists
		if m.Deleted {
			m.TerminalName = "(终端已删除)"
		}
		out = append(out, m)
	}
	return out, rs.Err()
}

// ---------- 新建 / 修改 ----------

type Input struct {
	Name        string
	Info        string
	TerminalIDs []int64
}

func (s *Service) validate(ctx context.Context, u *auth.User, in *Input) error {
	in.Name = strings.TrimSpace(in.Name)
	in.Info = strings.TrimSpace(in.Info)
	if in.Name == "" {
		return fmt.Errorf("分区名称不能为空")
	}
	if len(in.Name) > nameLimit {
		return fmt.Errorf("分区名称过长：按 UTF-8 计 %d 字节，上限 %d 字节", len(in.Name), nameLimit)
	}
	if len(in.Info) > infoLimit {
		return fmt.Errorf("分区描述过长：按 UTF-8 计 %d 字节，上限 %d 字节", len(in.Info), infoLimit)
	}
	if len(in.TerminalIDs) > maxMember {
		return fmt.Errorf("分区成员最多 %d 台", maxMember)
	}
	if len(in.TerminalIDs) == 0 {
		return nil
	}

	seen := map[int64]bool{}
	for _, id := range in.TerminalIDs {
		if id <= 0 {
			return fmt.Errorf("成员列表里有非法的终端 ID")
		}
		if seen[id] {
			return fmt.Errorf("成员列表里有重复的终端")
		}
		seen[id] = true
	}
	ph, args := placeholders(in.TerminalIDs)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM terminal WHERE id IN (`+ph+`)`, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验终端: %w", err)
	}
	if n != len(in.TerminalIDs) {
		return fmt.Errorf("成员列表里有已不存在的终端，请重新选择")
	}
	// 普通用户只能把绑定给自己的终端放进分区（旧版完全不查）
	if !u.IsAdmin {
		var bound int
		bindArgs := append(append([]interface{}{}, args...), u.ID)
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(DISTINCT terminalid) FROM userterminal
			 WHERE terminalid IN (`+ph+`) AND userid = ?`, bindArgs...).Scan(&bound); err != nil {
			return fmt.Errorf("校验终端归属: %w", err)
		}
		if bound != len(in.TerminalIDs) {
			return fmt.Errorf("成员列表里有未绑定给你的终端")
		}
	}
	return nil
}

// checkNameFree 分区名全局唯一。旧版的重名查询同样是全局的（不按用户隔离）。
func (s *Service) checkNameFree(ctx context.Context, name string, excludeID int64) error {
	q := `SELECT streamid FROM serverplaystream WHERE name = ?`
	args := []interface{}{name}
	if excludeID > 0 {
		q += ` AND streamid <> ?`
		args = append(args, excludeID)
	}
	q += ` LIMIT 1`
	var id int64
	err := s.db.QueryRowContext(ctx, q, args...).Scan(&id)
	if err == nil {
		return fmt.Errorf("终端分区名称已存在")
	}
	if errors.Is(err, sql.ErrNoRows) {
		return nil
	}
	return fmt.Errorf("分区重名校验: %w", err)
}

const nameLock = "htweb_zone_name"

func (s *Service) Create(ctx context.Context, u *auth.User, in Input) (int64, error) {
	if err := s.validate(ctx, u, &in); err != nil {
		return 0, err
	}
	// serverplaystream.name 上没有唯一索引（建索引属 DDL，红线禁止），
	// 用命名锁把「查重 + 写入」串起来
	unlock, err := store.Lock(ctx, s.db, nameLock)
	if err != nil {
		return 0, err
	}
	defer unlock()

	if err := s.checkNameFree(ctx, in.Name, 0); err != nil {
		return 0, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// createtime 走列默认值
	res, err := tx.ExecContext(ctx,
		`INSERT INTO serverplaystream (name, info, userid) VALUES (?,?,?)`,
		in.Name, in.Info, u.ID)
	if err != nil {
		return 0, fmt.Errorf("新建终端分区: %w", err)
	}
	// LastInsertId 而不是旧版的 SELECT MAX(streamid)：并发下 MAX 会拿到别人的行
	id, err := res.LastInsertId()
	if err != nil {
		return 0, err
	}
	if err := writeMembers(ctx, tx, id, in.TerminalIDs); err != nil {
		return 0, err
	}
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return id, nil
}

func (s *Service) Update(ctx context.Context, u *auth.User, id int64, in Input) error {
	var owner sql.NullInt64
	err := s.db.QueryRowContext(ctx,
		`SELECT userid FROM serverplaystream WHERE streamid = ? LIMIT 1`, id).Scan(&owner)
	if errors.Is(err, sql.ErrNoRows) {
		return ErrNotFound
	}
	if err != nil {
		return fmt.Errorf("查询终端分区: %w", err)
	}
	if !u.IsAdmin && owner.Int64 != u.ID {
		return ErrNoPermission
	}
	if err := s.validate(ctx, u, &in); err != nil {
		return err
	}

	unlock, err := store.Lock(ctx, s.db, nameLock)
	if err != nil {
		return err
	}
	defer unlock()

	if err := s.checkNameFree(ctx, in.Name, id); err != nil {
		return err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx,
		`UPDATE serverplaystream SET name = ?, info = ? WHERE streamid = ?`,
		in.Name, in.Info, id); err != nil {
		return fmt.Errorf("修改终端分区: %w", err)
	}
	// 成员「全清再写」必须在同一个事务里 —— 旧版没有事务，中途失败分区就空了
	if err := clearMembers(ctx, tx, id); err != nil {
		return err
	}
	if err := writeMembers(ctx, tx, id, in.TerminalIDs); err != nil {
		return err
	}
	return tx.Commit()
}

// writeMembers 写成员，并把四处归属数据同步成一致。
func writeMembers(ctx context.Context, tx *sql.Tx, zoneID int64, ids []int64) error {
	if len(ids) == 0 {
		return nil
	}
	ph, args := placeholders(ids)

	// ① 一台终端只能在一个分区里：从别的分区摘掉。
	//    不做这一步的话，terminalofgroup 会留下两条行，而 terminal.groupid
	//    只指向最后写的那个，成员列表从此虚报。
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminalofgroup WHERE terminalid IN (`+ph+`) AND groupid <> ?`,
		append(append([]interface{}{}, args...), zoneID)...); err != nil {
		return fmt.Errorf("清理终端原有分区: %w", err)
	}

	for _, id := range ids {
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO terminalofgroup (terminalid, groupid) VALUES (?,?)`, id, zoneID); err != nil {
			return fmt.Errorf("写入分区成员: %w", err)
		}
	}

	// ② terminal.groupid —— 旧版从来没写过（D-215）
	if _, err := tx.ExecContext(ctx,
		`UPDATE terminal SET groupid = ? WHERE id IN (`+ph+`)`,
		append([]interface{}{zoneID}, args...)...); err != nil {
		return fmt.Errorf("同步终端分区号: %w", err)
	}

	// ③ terminaloftask.groupid / terminalofalarmgroup.groupid —— 后台 C 服务下发时读的就是它们。
	//
	//    **无条件覆盖**，不加旧版那句 `AND groupid = 0` 的限定。
	//
	//    旧版 streamadd_msg 里是 `... WHERE terminalid=? and groupid='0'`：
	//    终端第一次进分区 A 时值被写成 A，之后再挪到 B，这句 SQL 因为 groupid
	//    已经不是 0 而整个跳过 —— 任务行永远停在 A，后台按 A 下发而终端在 B。
	//    现网实测：把终端 4 从 A 挪到 B 后，A 的成员数 1、任务引用数却还是 23，
	//    那 23 条全是终端 4 的残留。
	//
	//    之所以敢无条件覆盖：这一列**没有独立语义**。全部写入方
	//    （task/edit.go、task/copy.go、bell/edit.go）填进去的都是
	//    「该终端当时所属的分区」，界面上从来没有「给这条任务单独指定一个分区」
	//    这个操作。既然它只是终端分区的快照，分区一变就该整体跟着走。
	if _, err := tx.ExecContext(ctx,
		`UPDATE terminaloftask SET groupid = ? WHERE terminalid IN (`+ph+`)`,
		append([]interface{}{zoneID}, args...)...); err != nil {
		return fmt.Errorf("同步任务终端分区号: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`UPDATE terminalofalarmgroup SET groupid = ? WHERE terminalid IN (`+ph+`)`,
		append([]interface{}{zoneID}, args...)...); err != nil {
		return fmt.Errorf("同步报警分区终端分区号: %w", err)
	}
	return nil
}

// clearMembers 清空成员，并把这些终端的分区号复位成「未分区」。
func clearMembers(ctx context.Context, tx *sql.Tx, zoneID int64) error {
	// 顺序要紧：先按当前成员复位，再删关联行，否则就找不到该复位哪些终端了。
	//
	// terminal.groupid 上多加一层 `= zoneID` 限定：旧库里存在
	// 「成员行还在 A、实际归属已经是 B」的脏数据，不限定会把 B 的归属一起抹掉。
	if _, err := tx.ExecContext(ctx, `
		UPDATE terminal SET groupid = ?
		WHERE groupid = ?
		  AND id IN (SELECT terminalid FROM terminalofgroup WHERE groupid = ?)`,
		NoZone, zoneID, zoneID); err != nil {
		return fmt.Errorf("复位终端分区号: %w", err)
	}
	// 任务与报警里的分区号按**成员身份**复位，不再限定 `groupid = zoneID`：
	// 那个限定会漏掉已经跑偏的脏行（旧库里就有），而这一列只是终端分区的快照，
	// 复位成「未分区」始终是对的。理由同 writeMembers 第③步。
	if _, err := tx.ExecContext(ctx, `
		UPDATE terminaloftask SET groupid = ?
		WHERE terminalid IN (SELECT terminalid FROM terminalofgroup WHERE groupid = ?)`,
		NoZone, zoneID); err != nil {
		return fmt.Errorf("复位任务终端分区号: %w", err)
	}
	if _, err := tx.ExecContext(ctx, `
		UPDATE terminalofalarmgroup SET groupid = ?
		WHERE terminalid IN (SELECT terminalid FROM terminalofgroup WHERE groupid = ?)`,
		NoZone, zoneID); err != nil {
		return fmt.Errorf("复位报警分区终端分区号: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminalofgroup WHERE groupid = ?`, zoneID); err != nil {
		return fmt.Errorf("清理分区成员: %w", err)
	}
	return nil
}

// ---------- 删除 ----------

type Impact struct {
	Terminals int `json:"terminals"`
	Tasks     int `json:"tasks"`
}

type PreviewItem struct {
	ID     int64  `json:"id"`
	Name   string `json:"name"`
	Impact Impact `json:"impact"`
}

type Blocked struct {
	ID     int64  `json:"id"`
	Name   string `json:"name"`
	Reason string `json:"reason"`
	Detail string `json:"detail"`
}

type PreviewResult struct {
	Deletable []PreviewItem `json:"deletable"`
	Blocked   []Blocked     `json:"blocked"`
}

type DeleteResult struct {
	Deleted        []int64   `json:"deleted"`
	ResetTerminals int       `json:"resetTerminals"`
	ResetTasks     int       `json:"resetTasks"`
	Blocked        []Blocked `json:"blocked"`
}

func (s *Service) gate(ctx context.Context, u *auth.User, ids []int64) ([]PreviewItem, []Blocked, error) {
	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx,
		`SELECT streamid, COALESCE(name,''), COALESCE(userid,0)
		 FROM serverplaystream WHERE streamid IN (`+ph+`)`, args...)
	if err != nil {
		return nil, nil, fmt.Errorf("查询待删分区: %w", err)
	}
	type row struct {
		name  string
		owner int64
	}
	found := map[int64]row{}
	for rs.Next() {
		var id int64
		var r row
		if err := rs.Scan(&id, &r.name, &r.owner); err != nil {
			rs.Close()
			return nil, nil, err
		}
		found[id] = r
	}
	rs.Close()
	if err := rs.Err(); err != nil {
		return nil, nil, err
	}

	var ok []PreviewItem
	var blocked []Blocked
	for _, id := range ids {
		r, exists := found[id]
		if !exists {
			blocked = append(blocked, Blocked{ID: id, Reason: "NOT_FOUND", Detail: "终端分区不存在"})
			continue
		}
		if !u.IsAdmin && r.owner != u.ID {
			blocked = append(blocked, Blocked{ID: id, Name: r.name,
				Reason: "NOT_OWNER", Detail: "只能删除自己创建的终端分区"})
			continue
		}
		ok = append(ok, PreviewItem{ID: id, Name: r.name})
	}
	return ok, blocked, nil
}

// PreviewDelete 只读地展示删除影响面。
//
// 旧版删分区一声不吭就把成员关系清掉、把任务里的分区号复位成 0，
// 管理员事后才发现「这条任务怎么全终端播了」。这里先把数字摆出来。
func (s *Service) PreviewDelete(ctx context.Context, u *auth.User, ids []int64) (*PreviewResult, error) {
	ok, blocked, err := s.gate(ctx, u, ids)
	if err != nil {
		return nil, err
	}
	out := &PreviewResult{Deletable: []PreviewItem{}, Blocked: blocked}
	if out.Blocked == nil {
		out.Blocked = []Blocked{}
	}
	for _, it := range ok {
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM terminalofgroup WHERE groupid = ?`, it.ID).
			Scan(&it.Impact.Terminals); err != nil {
			return nil, fmt.Errorf("统计分区成员: %w", err)
		}
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(DISTINCT taskid) FROM terminaloftask WHERE groupid = ?`, it.ID).
			Scan(&it.Impact.Tasks); err != nil {
			return nil, fmt.Errorf("统计分区任务引用: %w", err)
		}
		out.Deletable = append(out.Deletable, it)
	}
	return out, nil
}

func (s *Service) Delete(ctx context.Context, u *auth.User, ids []int64) (*DeleteResult, error) {
	ok, blocked, err := s.gate(ctx, u, ids)
	if err != nil {
		return nil, err
	}
	out := &DeleteResult{Deleted: []int64{}, Blocked: blocked}
	if out.Blocked == nil {
		out.Blocked = []Blocked{}
	}
	if len(ok) == 0 {
		return out, nil
	}

	del := make([]int64, 0, len(ok))
	for _, it := range ok {
		del = append(del, it.ID)
	}
	ph, args := placeholders(del)

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 1) 终端自己的分区号复位。两条路径都走：既按关联表里的成员，
	//    也按 terminal.groupid 自己的值 —— 旧数据里这两套本来就不一致（D-215）。
	res, err := tx.ExecContext(ctx, `
		UPDATE terminal SET groupid = ?
		WHERE id IN (SELECT terminalid FROM terminalofgroup WHERE groupid IN (`+ph+`))`,
		append([]interface{}{NoZone}, args...)...)
	if err != nil {
		return nil, fmt.Errorf("复位终端分区号: %w", err)
	}
	n1, _ := res.RowsAffected()
	res, err = tx.ExecContext(ctx,
		`UPDATE terminal SET groupid = ? WHERE groupid IN (`+ph+`)`,
		append([]interface{}{NoZone}, args...)...)
	if err != nil {
		return nil, fmt.Errorf("复位终端分区号(按 groupid): %w", err)
	}
	n2, _ := res.RowsAffected()
	out.ResetTerminals = int(n1 + n2)

	// 2) 任务与报警里引用这个分区号的行复位成 0（旧版也是复位而不是删行）
	res, err = tx.ExecContext(ctx,
		`UPDATE terminaloftask SET groupid = ? WHERE groupid IN (`+ph+`)`,
		append([]interface{}{NoZone}, args...)...)
	if err != nil {
		return nil, fmt.Errorf("复位任务终端分区号: %w", err)
	}
	nt, _ := res.RowsAffected()
	out.ResetTasks = int(nt)

	if _, err := tx.ExecContext(ctx,
		`UPDATE terminalofalarmgroup SET groupid = ? WHERE groupid IN (`+ph+`)`,
		append([]interface{}{NoZone}, args...)...); err != nil {
		return nil, fmt.Errorf("复位报警分区终端分区号: %w", err)
	}

	// 3) 成员关联
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminalofgroup WHERE groupid IN (`+ph+`)`, args...); err != nil {
		return nil, fmt.Errorf("清理分区成员: %w", err)
	}

	// 4) 分区本体
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM serverplaystream WHERE streamid IN (`+ph+`)`, args...); err != nil {
		return nil, fmt.Errorf("删除终端分区: %w", err)
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	out.Deleted = del
	return out, nil
}

// ---------- 下拉选项 ----------

type Option struct {
	ID   int64  `json:"id"`
	Name string `json:"name"`
}

// Options 给别处（终端编辑、任务下发）用的分区下拉。
func (s *Service) Options(ctx context.Context, u *auth.User) ([]Option, error) {
	cond := &store.Cond{}
	if c, args := visibleCond(u); c != "" {
		cond.Add(c, args...)
	}
	rs, err := s.db.QueryContext(ctx,
		`SELECT s.streamid, COALESCE(s.name,'') FROM serverplaystream s`+cond.Where()+
			` ORDER BY s.name`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询分区选项: %w", err)
	}
	defer rs.Close()
	out := []Option{}
	for rs.Next() {
		var o Option
		if err := rs.Scan(&o.ID, &o.Name); err != nil {
			return nil, err
		}
		out = append(out, o)
	}
	return out, rs.Err()
}

// TerminalOption 是成员选择框里的一项。
type TerminalOption struct {
	ID       int64  `json:"id"`
	Name     string `json:"terminalname"`
	IP       string `json:"ip"`
	TypeName string `json:"typeName"`
	NetState int    `json:"netstate"`
	// CurrentZoneID / CurrentZoneName 让界面能标出「这台已经属于别的分区」——
	// 加进来会把它从原分区移走，得让人看见再点。
	CurrentZoneID   int64  `json:"currentZoneId"`
	CurrentZoneName string `json:"currentZoneName"`
}

// PickTerminals 列出可加入分区的终端。
//
// 普通用户只看绑定给自己的终端（userterminal），与终端列表同一套可见范围。
func (s *Service) PickTerminals(ctx context.Context, u *auth.User, keyword string) ([]TerminalOption, error) {
	cond := &store.Cond{}
	if !u.IsAdmin {
		cond.Add(`t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)`, u.ID)
	}
	if keyword = strings.TrimSpace(keyword); keyword != "" {
		cond.Add(`t.terminalname LIKE ? ESCAPE '\\'`, store.EscapeLike(keyword))
	}

	// 当前分区用相关子查询取，不 JOIN：一台终端在旧数据里可能有多条
	// terminalofgroup 行，JOIN 会把它拆成多行。
	rs, err := s.db.QueryContext(ctx, `
		SELECT t.id, COALESCE(t.terminalname,''), COALESCE(t.ip,''),
		       COALESCE(tt.name,''), COALESCE(t.netstate,0),
		       COALESCE((SELECT g.groupid FROM terminalofgroup g
		                  WHERE g.terminalid = t.id ORDER BY g.id LIMIT 1), 0)
		FROM terminal t
		LEFT JOIN terminaltype tt ON tt.id = t.typeid`+cond.Where()+`
		ORDER BY t.netstate DESC, t.id LIMIT 500`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询终端: %w", err)
	}
	defer rs.Close()

	out := []TerminalOption{}
	zoneIDs := map[int64]bool{}
	for rs.Next() {
		var o TerminalOption
		if err := rs.Scan(&o.ID, &o.Name, &o.IP, &o.TypeName, &o.NetState, &o.CurrentZoneID); err != nil {
			return nil, err
		}
		if o.CurrentZoneID > 0 {
			zoneIDs[o.CurrentZoneID] = true
		}
		out = append(out, o)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}

	// 一条查询把用到的分区名全取回来，不做 N+1
	if len(zoneIDs) > 0 {
		ids := make([]int64, 0, len(zoneIDs))
		for id := range zoneIDs {
			ids = append(ids, id)
		}
		ph, args := placeholders(ids)
		nameRS, err := s.db.QueryContext(ctx,
			`SELECT streamid, COALESCE(name,'') FROM serverplaystream WHERE streamid IN (`+ph+`)`, args...)
		if err != nil {
			return nil, fmt.Errorf("查询分区名: %w", err)
		}
		names := map[int64]string{}
		for nameRS.Next() {
			var id int64
			var n string
			if err := nameRS.Scan(&id, &n); err != nil {
				nameRS.Close()
				return nil, err
			}
			names[id] = n
		}
		nameRS.Close()
		for i := range out {
			out[i].CurrentZoneName = names[out[i].CurrentZoneID]
		}
	}
	return out, nil
}

// idsFromCSV 解析 "1,2,3"。给 handler 用。
func idsFromCSV(s string) []int64 {
	parts := strings.Split(s, ",")
	out := make([]int64, 0, len(parts))
	for _, p := range parts {
		v, err := strconv.ParseInt(strings.TrimSpace(p), 10, 64)
		if err == nil && v > 0 {
			out = append(out, v)
		}
	}
	return out
}

// IDsFromCSV 导出给 handler 用。
func IDsFromCSV(s string) []int64 { return idsFromCSV(s) }
