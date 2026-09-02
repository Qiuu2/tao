package alarm

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

// 报警分区（F-40 列表 / F-41 新建与修改 / F-42 删除）。
//
// # 两套归属数据必须同时维护（BR-197）
//
// 「某终端属于哪个报警分区」在库里记了两份：
//   - `terminalofalarmgroup`（分区 → 终端的多对多）
//   - `terminal.firealarmgroup`（终端上的单值字段）
//
// 旧代码里维护 `terminal.firealarmgroup` 的那几行**全部被注释掉了**
// （D-138 / D-139），只写 terminalofalarmgroup。于是两套数据长期不一致：
// 分区删了，终端还认为自己属于那个分区，后台按脏数据调度。
// 新版两处同时维护，与终端模块处理 terminal.groupid / terminalofgroup 的做法一致。
//
// # alarmgroupid 是 varchar
//
// `terminalofalarmgroup.alarmgroupid` 是 varchar(45) 而 `alarmarea.id` 是 int（D-144）。
// 直接拿整型去比会触发隐式类型转换，索引失效且语义含糊。
// 这里统一：**写入时格式化成纯数字字符串，查询时也绑字符串**。

// Area 是报警分区列表行。
type Area struct {
	ID            int64  `json:"id"`
	Name          string `json:"name"`
	Info          string `json:"info"`
	CreateTime    string `json:"createTime"`
	UserID        int64  `json:"userId"`
	UserName      string `json:"userName"`
	TerminalCount int    `json:"terminalCount"`
	MappingCount  int    `json:"mappingCount"`
	CanModify     bool   `json:"canModify"`
}

type AreaListResult struct {
	Items     []Area
	Total     int64
	ScopeNote string
}

var areaOrderWhitelist = map[string]string{
	"name":       "a.name",
	"createtime": "a.createtime",
	"id":         "a.id",
}

const areaDefaultOrder = "a.createtime DESC, a.id DESC"

type AreaQuery struct {
	Keyword string
	OrderBy string
	Order   string
	Pager   store.Pager
}

// areaVisibleCond 是报警分区可见范围的唯一权威定义（BR-196）。
// 旧版同样只在「无搜索」分支写了这个条件，搜索分支全丢。
func areaVisibleCond(u *auth.User) (string, []interface{}) {
	if u.IsAdmin {
		return "", nil
	}
	return "a.userid = ?", []interface{}{u.ID}
}

func (s *Service) ListAreas(ctx context.Context, u *auth.User, q AreaQuery) (*AreaListResult, error) {
	cond := &store.Cond{}
	if c, args := areaVisibleCond(u); c != "" {
		cond.Add(c, args...)
	}
	if q.Keyword != "" {
		cond.Add(`a.name LIKE ? ESCAPE '\\'`, store.EscapeLike(q.Keyword))
	}
	where := cond.Where()
	args := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM alarmarea a"+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计报警分区数: %w", err)
	}

	order := store.OrderBy(areaOrderWhitelist, q.OrderBy, q.Order, areaDefaultOrder)

	listSQL := `
		SELECT a.id, COALESCE(a.name,''), COALESCE(a.info,''),
		       COALESCE(DATE_FORMAT(a.createtime,'%Y-%m-%d %H:%i:%s'),''),
		       COALESCE(a.userid,0), COALESCE(b.username,'')
		FROM alarmarea a
		LEFT JOIN book_admin b ON b.id = a.userid` + where +
		" ORDER BY " + order + " LIMIT ? OFFSET ?"

	listArgs := append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, listSQL, listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询报警分区: %w", err)
	}
	defer rs.Close()

	items := make([]Area, 0, q.Pager.PageSize)
	ids := make([]int64, 0, q.Pager.PageSize)
	for rs.Next() {
		var a Area
		if err := rs.Scan(&a.ID, &a.Name, &a.Info, &a.CreateTime, &a.UserID, &a.UserName); err != nil {
			return nil, fmt.Errorf("扫描报警分区行: %w", err)
		}
		a.CanModify = u.IsAdmin || a.UserID == u.ID
		items = append(items, a)
		ids = append(ids, a.ID)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}

	// 成员数与映射数各一条聚合查询，不做 N+1
	if len(ids) > 0 {
		if err := s.fillAreaCounts(ctx, items, ids); err != nil {
			return nil, err
		}
	}

	res := &AreaListResult{Items: items, Total: total}
	if !u.IsAdmin {
		res.ScopeNote = "仅显示我创建的报警分区"
	}
	return res, nil
}

func (s *Service) fillAreaCounts(ctx context.Context, items []Area, ids []int64) error {
	// terminalofalarmgroup.alarmgroupid 是 varchar，绑字符串（D-144）
	strPH, strArgs := stringPlaceholders(ids)
	terms, err := s.countBy(ctx,
		`SELECT alarmgroupid, COUNT(*) FROM terminalofalarmgroup
		 WHERE alarmgroupid IN (`+strPH+`) GROUP BY alarmgroupid`, strArgs, true)
	if err != nil {
		return fmt.Errorf("统计分区成员数: %w", err)
	}
	intPH, intArgs := placeholders(ids)
	maps, err := s.countBy(ctx,
		`SELECT firealarmgroupid, COUNT(*) FROM alarmgroupmap
		 WHERE firealarmgroupid IN (`+intPH+`) GROUP BY firealarmgroupid`, intArgs, false)
	if err != nil {
		return fmt.Errorf("统计分区映射数: %w", err)
	}
	for i := range items {
		items[i].TerminalCount = terms[items[i].ID]
		items[i].MappingCount = maps[items[i].ID]
	}
	return nil
}

// countBy 跑一条「分组键 → 计数」的聚合。keyIsString 时分组键按字符串扫描后再转数字。
func (s *Service) countBy(ctx context.Context, q string, args []interface{}, keyIsString bool) (map[int64]int, error) {
	rs, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, err
	}
	defer rs.Close()
	out := map[int64]int{}
	for rs.Next() {
		var n int
		if keyIsString {
			var k string
			if err := rs.Scan(&k, &n); err != nil {
				return nil, err
			}
			// 脏数据里可能有非数字的 alarmgroupid，跳过而不是报错
			id, convErr := strconv.ParseInt(strings.TrimSpace(k), 10, 64)
			if convErr != nil {
				continue
			}
			out[id] = n
			continue
		}
		var k int64
		if err := rs.Scan(&k, &n); err != nil {
			return nil, err
		}
		out[k] = n
	}
	return out, rs.Err()
}

func stringPlaceholders(ids []int64) (string, []interface{}) {
	if len(ids) == 0 {
		return "NULL", nil
	}
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		args[i] = strconv.FormatInt(id, 10)
	}
	return strings.TrimSuffix(strings.Repeat("?,", len(ids)), ","), args
}

// ---------- F-41 新建 / 修改 ----------

// AreaDetail 是编辑弹窗需要的数据。
type AreaDetail struct {
	ID        int64          `json:"id"`
	Name      string         `json:"name"`
	Info      string         `json:"info"`
	UserID    int64          `json:"userId"`
	Terminals []AreaTerminal `json:"terminals"`
}

// AreaTerminal 是分区成员。
type AreaTerminal struct {
	TerminalID   int64  `json:"terminalId"`
	TerminalName string `json:"terminalname"`
	TypeName     string `json:"typeName"`
	GroupID      int64  `json:"groupId"`
	NetState     int    `json:"netstate"`
	Deleted      bool   `json:"deleted"`
}

// AreaInput 是新建 / 修改的入参。
type AreaInput struct {
	Name      string
	Info      string
	Terminals []AreaTerminalRef
}

type AreaTerminalRef struct {
	TerminalID int64 `json:"terminalId"`
	GroupID    int64 `json:"groupId"`
}

func (s *Service) GetArea(ctx context.Context, u *auth.User, id int64) (*AreaDetail, error) {
	d := &AreaDetail{ID: id}
	var userID sql.NullInt64
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(name,''), COALESCE(info,''), userid FROM alarmarea WHERE id = ? LIMIT 1`, id).
		Scan(&d.Name, &d.Info, &userID)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询报警分区: %w", err)
	}
	d.UserID = userID.Int64
	if !u.IsAdmin && d.UserID != u.ID {
		return nil, ErrNoPermission
	}
	terms, err := s.areaTerminals(ctx, id)
	if err != nil {
		return nil, err
	}
	d.Terminals = terms
	return d, nil
}

// areaTerminals 取分区成员。LEFT JOIN：终端被删后关联行可能还在，
// 内连接会让这些行静默消失，看不出分区里挂着一台不存在的设备。
func (s *Service) areaTerminals(ctx context.Context, areaID int64) ([]AreaTerminal, error) {
	rs, err := s.db.QueryContext(ctx, `
		SELECT g.terminalid, COALESCE(g.groupid,0),
		       t.id IS NOT NULL, COALESCE(t.terminalname,''), COALESCE(tt.name,''),
		       COALESCE(t.netstate,0)
		FROM terminalofalarmgroup g
		LEFT JOIN terminal t ON t.id = g.terminalid
		LEFT JOIN terminaltype tt ON tt.id = t.typeid
		WHERE g.alarmgroupid = ?
		ORDER BY g.id`, strconv.FormatInt(areaID, 10))
	if err != nil {
		return nil, fmt.Errorf("查询分区成员: %w", err)
	}
	defer rs.Close()

	out := []AreaTerminal{}
	for rs.Next() {
		var t AreaTerminal
		var exists bool
		if err := rs.Scan(&t.TerminalID, &t.GroupID, &exists,
			&t.TerminalName, &t.TypeName, &t.NetState); err != nil {
			return nil, err
		}
		t.Deleted = !exists
		if t.Deleted {
			t.TerminalName = "(终端已删除)"
		}
		out = append(out, t)
	}
	return out, rs.Err()
}

func (s *Service) validateArea(ctx context.Context, u *auth.User, in *AreaInput) error {
	in.Name = strings.TrimSpace(in.Name)
	in.Info = strings.TrimSpace(in.Info)
	if in.Name == "" {
		return fmt.Errorf("分区名称不能为空")
	}
	// name / info 都是 varchar(45)，超长会被 MySQL 静默截断
	if len(in.Name) > 45 {
		return fmt.Errorf("分区名称过长：按 UTF-8 计 %d 字节，上限 45 字节（约 15 个汉字）", len(in.Name))
	}
	if len(in.Info) > 45 {
		return fmt.Errorf("分区描述过长：按 UTF-8 计 %d 字节，上限 45 字节（约 15 个汉字）", len(in.Info))
	}
	if len(in.Terminals) > 3000 {
		return fmt.Errorf("分区成员最多 3000 台")
	}
	if len(in.Terminals) == 0 {
		return nil
	}

	seen := map[int64]bool{}
	ids := make([]int64, 0, len(in.Terminals))
	for i := range in.Terminals {
		t := &in.Terminals[i]
		if t.TerminalID <= 0 {
			return fmt.Errorf("成员列表里有非法的终端 ID")
		}
		if seen[t.TerminalID] {
			return fmt.Errorf("成员列表里有重复的终端")
		}
		seen[t.TerminalID] = true
		ids = append(ids, t.TerminalID)
		if t.GroupID < 0 {
			t.GroupID = 0
		}
	}
	ph, args := placeholders(ids)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM terminal WHERE id IN (`+ph+`)`, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验终端: %w", err)
	}
	if n != len(ids) {
		return fmt.Errorf("成员列表里有已不存在的终端，请重新选择")
	}
	// 普通用户只能把绑定给自己的终端加进分区
	if !u.IsAdmin {
		var bound int
		bindArgs := append(append([]interface{}{}, args...), u.ID)
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(DISTINCT terminalid) FROM userterminal
			 WHERE terminalid IN (`+ph+`) AND userid = ?`, bindArgs...).Scan(&bound); err != nil {
			return fmt.Errorf("校验终端归属: %w", err)
		}
		if bound != len(ids) {
			return fmt.Errorf("成员列表里有未绑定给你的终端")
		}
	}
	return nil
}

// checkAreaNameFree 分区名全局唯一（BR-195）。旧版的重名查询同样是全局的。
func (s *Service) checkAreaNameFree(ctx context.Context, name string, excludeID int64) error {
	q := `SELECT id FROM alarmarea WHERE name = ?`
	args := []interface{}{name}
	if excludeID > 0 {
		q += ` AND id <> ?`
		args = append(args, excludeID)
	}
	q += ` LIMIT 1`
	var id int64
	err := s.db.QueryRowContext(ctx, q, args...).Scan(&id)
	if err == nil {
		return fmt.Errorf("报警分区名称已存在")
	}
	if errors.Is(err, sql.ErrNoRows) {
		return nil
	}
	return fmt.Errorf("分区重名校验: %w", err)
}

func (s *Service) CreateArea(ctx context.Context, u *auth.User, in AreaInput) (int64, error) {
	if err := s.validateArea(ctx, u, &in); err != nil {
		return 0, err
	}
	// alarmarea 没有 name 唯一索引，用命名锁串行化「查重 + 写入」
	unlock, err := store.Lock(ctx, s.db, "htweb_alarm_area_name")
	if err != nil {
		return 0, err
	}
	defer unlock()

	if err := s.checkAreaNameFree(ctx, in.Name, 0); err != nil {
		return 0, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// createtime 走列默认值，不显式写
	res, err := tx.ExecContext(ctx,
		`INSERT INTO alarmarea (name, info, userid) VALUES (?,?,?)`, in.Name, in.Info, u.ID)
	if err != nil {
		return 0, fmt.Errorf("新建报警分区: %w", err)
	}
	// 用 LAST_INSERT_ID 而不是旧版的 SELECT MAX(id)（D-142）
	id, err := res.LastInsertId()
	if err != nil {
		return 0, err
	}
	if err := writeMembers(ctx, tx, id, in.Terminals); err != nil {
		return 0, err
	}
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return id, nil
}

func (s *Service) UpdateArea(ctx context.Context, u *auth.User, id int64, in AreaInput) error {
	var owner sql.NullInt64
	err := s.db.QueryRowContext(ctx,
		`SELECT userid FROM alarmarea WHERE id = ? LIMIT 1`, id).Scan(&owner)
	if errors.Is(err, sql.ErrNoRows) {
		return ErrNotFound
	}
	if err != nil {
		return fmt.Errorf("查询报警分区: %w", err)
	}
	// 旧版修改分区完全不校验归属，谁都能改别人的
	if !u.IsAdmin && owner.Int64 != u.ID {
		return ErrNoPermission
	}
	if err := s.validateArea(ctx, u, &in); err != nil {
		return err
	}

	unlock, err := store.Lock(ctx, s.db, "htweb_alarm_area_name")
	if err != nil {
		return err
	}
	defer unlock()

	if err := s.checkAreaNameFree(ctx, in.Name, id); err != nil {
		return err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx,
		`UPDATE alarmarea SET name = ?, info = ? WHERE id = ?`, in.Name, in.Info, id); err != nil {
		return fmt.Errorf("修改报警分区: %w", err)
	}

	// 成员「全删重插」必须在事务里 —— 旧版没有事务，中途失败分区就变空了（D-140）
	if err := clearMembers(ctx, tx, id); err != nil {
		return err
	}
	if err := writeMembers(ctx, tx, id, in.Terminals); err != nil {
		return err
	}
	return tx.Commit()
}

// writeMembers 写分区成员，并同步 terminal.firealarmgroup（BR-197）。
func writeMembers(ctx context.Context, tx *sql.Tx, areaID int64, terms []AreaTerminalRef) error {
	if len(terms) == 0 {
		return nil
	}
	areaStr := strconv.FormatInt(areaID, 10)
	ids := make([]int64, 0, len(terms))
	for _, t := range terms {
		ids = append(ids, t.TerminalID)
	}

	// 先把这些终端从**别的**分区的成员表里摘掉。
	//
	// 一台终端到底属于哪个报警分区，最终生效的是 terminal.firealarmgroup ——
	// 它是一个 int，天然只装得下一个分区。terminalofalarmgroup 却是多对多，
	// 于是「把已属于 A 的终端加进 B」在旧库里会留下两条成员行、
	// 而 firealarmgroup 只指向 B：A 的成员列表从此虚报一台，
	// 更糟的是之后编辑 A 时 clearMembers 会顺手把这台终端的 firealarmgroup
	// 从 B 复位成 -1，把 B 也搞坏。
	// 按实际生效语义收敛成一对一，两份数据就不会再各说各话。
	evictPH, evictArgs := placeholders(ids)
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminalofalarmgroup WHERE terminalid IN (`+evictPH+`) AND alarmgroupid <> ?`,
		append(evictArgs, areaStr)...); err != nil {
		return fmt.Errorf("清理终端原有报警分区: %w", err)
	}

	for _, t := range terms {
		// alarmgroupid 是 varchar：写纯数字字符串（BR-200）
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO terminalofalarmgroup (alarmgroupid, terminalid, groupid) VALUES (?,?,?)`,
			areaStr, t.TerminalID, t.GroupID); err != nil {
			return fmt.Errorf("写入分区成员: %w", err)
		}
	}
	// 旧版维护这一列的语句被注释掉了（D-139），两套归属数据长期不一致
	ph, args := placeholders(ids)
	if _, err := tx.ExecContext(ctx,
		`UPDATE terminal SET firealarmgroup = ? WHERE id IN (`+ph+`)`,
		append([]interface{}{areaID}, args...)...); err != nil {
		return fmt.Errorf("同步终端报警分区: %w", err)
	}
	return nil
}

// clearMembers 清空分区成员，并把这些终端的 firealarmgroup 复位。
func clearMembers(ctx context.Context, tx *sql.Tx, areaID int64) error {
	areaStr := strconv.FormatInt(areaID, 10)
	// 先按「当前成员」把 firealarmgroup 复位，再删关联行；
	// 顺序反过来就找不到该复位哪些终端了。
	// 加上 firealarmgroup = areaID 这层限定：旧库里可能残留「成员行在 A、
	// 实际归属却已经是 B」的脏数据，不限定就会把 B 的归属一起抹掉。
	if _, err := tx.ExecContext(ctx, `
		UPDATE terminal SET firealarmgroup = ?
		WHERE firealarmgroup = ?
		  AND id IN (SELECT terminalid FROM terminalofalarmgroup WHERE alarmgroupid = ?)`,
		NoArea, areaID, areaStr); err != nil {
		return fmt.Errorf("复位终端报警分区: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminalofalarmgroup WHERE alarmgroupid = ?`, areaStr); err != nil {
		return fmt.Errorf("清理分区成员: %w", err)
	}
	return nil
}

// ---------- F-42 删除 ----------

// AreaImpact 是删除一个报警分区的影响面。
type AreaImpact struct {
	Terminals int `json:"terminals"`
	Mappings  int `json:"alarmMappings"`
}

type AreaPreviewItem struct {
	ID     int64      `json:"id"`
	Name   string     `json:"name"`
	Impact AreaImpact `json:"impact"`
}

type AreaBlocked struct {
	ID     int64  `json:"id"`
	Name   string `json:"name"`
	Reason string `json:"reason"`
	Detail string `json:"detail"`
}

type AreaPreviewResult struct {
	Deletable []AreaPreviewItem `json:"deletable"`
	Blocked   []AreaBlocked     `json:"blocked"`
}

type AreaDeleteResult struct {
	Deleted         []int64       `json:"deleted"`
	DeletedMappings int           `json:"deletedMappings"`
	ResetTerminals  int           `json:"resetTerminals"`
	Blocked         []AreaBlocked `json:"blocked"`
}

// gateAreas 把请求的 id 分成「可删」与「被挡」两组。
func (s *Service) gateAreas(ctx context.Context, u *auth.User, ids []int64) ([]AreaPreviewItem, []AreaBlocked, error) {
	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx,
		`SELECT id, COALESCE(name,''), COALESCE(userid,0) FROM alarmarea WHERE id IN (`+ph+`)`, args...)
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

	var ok []AreaPreviewItem
	var blocked []AreaBlocked
	for _, id := range ids {
		r, exists := found[id]
		if !exists {
			blocked = append(blocked, AreaBlocked{ID: id, Reason: "NOT_FOUND", Detail: "报警分区不存在"})
			continue
		}
		if !u.IsAdmin && r.owner != u.ID {
			blocked = append(blocked, AreaBlocked{ID: id, Name: r.name,
				Reason: "NOT_OWNER", Detail: "只能删除自己创建的报警分区"})
			continue
		}
		ok = append(ok, AreaPreviewItem{ID: id, Name: r.name})
	}
	return ok, blocked, nil
}

// PreviewDeleteAreas 只读地展示删除影响面（D-143）。
//
// 旧版删分区会顺手把该分区下的全部报警映射一起删掉，但**完全不提示**，
// 管理员在不知情的情况下就把报警联动配置删没了。
func (s *Service) PreviewDeleteAreas(ctx context.Context, u *auth.User, ids []int64) (*AreaPreviewResult, error) {
	ok, blocked, err := s.gateAreas(ctx, u, ids)
	if err != nil {
		return nil, err
	}
	out := &AreaPreviewResult{Deletable: []AreaPreviewItem{}, Blocked: blocked}
	if out.Blocked == nil {
		out.Blocked = []AreaBlocked{}
	}
	for _, it := range ok {
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM terminalofalarmgroup WHERE alarmgroupid = ?`,
			strconv.FormatInt(it.ID, 10)).Scan(&it.Impact.Terminals); err != nil {
			return nil, fmt.Errorf("统计分区成员: %w", err)
		}
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM alarmgroupmap WHERE firealarmgroupid = ?`,
			it.ID).Scan(&it.Impact.Mappings); err != nil {
			return nil, fmt.Errorf("统计分区映射: %w", err)
		}
		out.Deletable = append(out.Deletable, it)
	}
	return out, nil
}

// DeleteAreas 删除报警分区。
//
// 旧 del_alarm_area 的三个问题一并修掉：
//   - D-138 复位 terminal.firealarmgroup 的那行被注释掉了 → 补上
//   - D-141 LOCK TABLES 写在 START TRANSACTION **之后**（破坏事务），
//     且有一处把 die 写成了 dir —— 函数名拼错，错误被静默吞掉
//   - D-143 不提示会连带删掉多少报警映射 → 见 PreviewDeleteAreas
func (s *Service) DeleteAreas(ctx context.Context, u *auth.User, ids []int64) (*AreaDeleteResult, error) {
	ok, blocked, err := s.gateAreas(ctx, u, ids)
	if err != nil {
		return nil, err
	}
	out := &AreaDeleteResult{Deleted: []int64{}, Blocked: blocked}
	if out.Blocked == nil {
		out.Blocked = []AreaBlocked{}
	}
	if len(ok) == 0 {
		return out, nil
	}

	del := make([]int64, 0, len(ok))
	for _, it := range ok {
		del = append(del, it.ID)
	}
	intPH, intArgs := placeholders(del)
	strPH, strArgs := stringPlaceholders(del)

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 1) 把成员终端的 firealarmgroup 复位为「无分区」。
	//    两条路径都覆盖：既按关联表里的成员，也按 terminal 自己记的值 ——
	//    因为旧数据里这两套本来就不一致（D-139 的后果）。
	resetArgs := append([]interface{}{NoArea}, strArgs...)
	res, err := tx.ExecContext(ctx, `
		UPDATE terminal SET firealarmgroup = ?
		WHERE id IN (SELECT terminalid FROM terminalofalarmgroup WHERE alarmgroupid IN (`+strPH+`))`,
		resetArgs...)
	if err != nil {
		return nil, fmt.Errorf("复位终端报警分区: %w", err)
	}
	n1, _ := res.RowsAffected()

	res, err = tx.ExecContext(ctx,
		`UPDATE terminal SET firealarmgroup = ? WHERE firealarmgroup IN (`+intPH+`)`,
		append([]interface{}{NoArea}, intArgs...)...)
	if err != nil {
		return nil, fmt.Errorf("复位终端报警分区(按 firealarmgroup): %w", err)
	}
	n2, _ := res.RowsAffected()
	out.ResetTerminals = int(n1 + n2)

	// 2) 级联删除该分区下的报警映射
	res, err = tx.ExecContext(ctx,
		`DELETE FROM alarmgroupmap WHERE firealarmgroupid IN (`+intPH+`)`, intArgs...)
	if err != nil {
		return nil, fmt.Errorf("删除报警映射: %w", err)
	}
	nm, _ := res.RowsAffected()
	out.DeletedMappings = int(nm)

	// 3) 成员关联
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminalofalarmgroup WHERE alarmgroupid IN (`+strPH+`)`, strArgs...); err != nil {
		return nil, fmt.Errorf("清理分区成员: %w", err)
	}

	// 4) 分区本体
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM alarmarea WHERE id IN (`+intPH+`)`, intArgs...); err != nil {
		return nil, fmt.Errorf("删除报警分区: %w", err)
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	out.Deleted = del
	return out, nil
}
