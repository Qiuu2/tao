// Package sound 实现噪声检测：噪声设备与声场分区。
//
// # 三张表
//
//	sounddevice    (id, ip, devaddr, name, groupid, dbvalue, sendport)  ← 噪声探头
//	soundgroupinfo (id, name, userid)                                    ← 声场分区定义
//	soundgroup     (terminalid, groupid)                                 ← 分区里的终端
//
// 一个「声场分区」把**一组终端**和**一组噪声探头**绑在一起：
// 探头量到环境噪声，后台据此把这组终端的音量调上去或调下来。
// 探头归属记在 `sounddevice.groupid`，终端归属记在 `soundgroup` 表
// 外加终端自己的 `terminal.soundsgroupid`。
//
// # ⚠ 终端归属又是记两份
//
// 和终端分区（zone 包）、报警分区（alarm 包）一模一样的老问题：
// `soundgroup` 是多对多关联表，`terminal.soundsgroupid` 是终端上的单值列。
// 旧版删除分区时两处都清（`UPDATE terminal SET soundsgroupid='0' ...`
// 加 `DELETE FROM soundgroup ...`），**但新建和修改分区时只写 soundgroup、
// 从来不写 terminal.soundsgroupid**（D-220）——
// 于是建完分区，终端自己那一列还是 0，后台按它取值就找不到分区。
// 新版两处同时维护。
//
// # ⚠ dbvalue 是探头的实测值，Web 不该写它
//
// `sounddevice.dbvalue` 的列注释是「探头DB值」，由后台采集回写。
// 旧版新增设备时把它写死成 `'0'`，修改设备时**不碰它**（这一点旧版是对的）。
// 新版新增时同样写 0（表示还没采到值），之后一律只读 —— 界面上展示，不提供编辑。
//
// # soundtask 不在本模块内
//
// `soundtask (taskid, devid, volume, dbvalue)` 是「某条任务在某个探头上的音量档位」，
// 属于声场任务（`tasktype = 25`）的配置，不是设备或分区的属性。
// 现网那 6 行 `taskid=0 devid=0` 的数据是音量档位模板（0/20/40/60/80/100），
// 不是真实任务绑定。本模块不读写这张表。
package sound

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"net"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/store"
)

type Service struct {
	db *sql.DB
}

func New(db *sql.DB) *Service { return &Service{db: db} }

var (
	ErrNotFound     = errors.New("记录不存在")
	ErrNoPermission = errors.New("没有权限操作该声场分区")
)

// NoGroup 是「未分区」。sounddevice.groupid 与 terminal.soundsgroupid 都用 0 表示。
const NoGroup = 0

const (
	devNameLimit   = 32 // sounddevice.name    varchar(32)
	groupNameLimit = 64 // soundgroupinfo.name varchar(64)
	maxMember      = 3000
	// devaddr 是 tinyint(3) unsigned —— 0~255。旧版不校验，填 300 会被静默截成 255。
	maxDevAddr = 255
)

// ==================================================================
//                          噪声设备
// ==================================================================

type Device struct {
	ID      int64  `json:"id"`
	Name    string `json:"name"`
	IP      string `json:"ip"`
	DevAddr int    `json:"devaddr"`
	// DBValue 是探头采回来的噪声值，只读。
	DBValue   float64 `json:"dbvalue"`
	SendPort  int     `json:"sendport"`
	GroupID   int64   `json:"groupId"`
	GroupName string  `json:"groupName"`
}

var devOrderWhitelist = map[string]string{
	"name":    "d.name",
	"ip":      "d.ip",
	"devaddr": "d.devaddr",
	"dbvalue": "d.dbvalue",
	"id":      "d.id",
}

const devDefaultOrder = "d.id ASC"

type DeviceQuery struct {
	Keyword string
	GroupID int64 // >0 只看某个声场分区的探头；-1 只看未分区的
	OrderBy string
	Order   string
	Pager   store.Pager
}

type DeviceListResult struct {
	Items []Device
	Total int64
}

func (s *Service) ListDevices(ctx context.Context, q DeviceQuery) (*DeviceListResult, error) {
	cond := &store.Cond{}
	if q.Keyword != "" {
		kw := store.EscapeLike(q.Keyword)
		cond.Add(`(d.name LIKE ? ESCAPE '\\' OR d.ip LIKE ? ESCAPE '\\')`, kw, kw)
	}
	switch {
	case q.GroupID > 0:
		cond.Add(`COALESCE(d.groupid,0) = ?`, q.GroupID)
	case q.GroupID < 0:
		cond.Add(`COALESCE(d.groupid,0) = 0`)
	}
	where := cond.Where()
	args := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM sounddevice d"+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计噪声设备: %w", err)
	}

	order := store.OrderBy(devOrderWhitelist, q.OrderBy, q.Order, devDefaultOrder)
	listArgs := append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, `
		SELECT d.id, COALESCE(d.name,''), COALESCE(d.ip,''), COALESCE(d.devaddr,0),
		       COALESCE(d.dbvalue,0), COALESCE(d.sendport,0),
		       COALESCE(d.groupid,0), COALESCE(g.name,'')
		FROM sounddevice d
		LEFT JOIN soundgroupinfo g ON g.id = d.groupid`+where+
		" ORDER BY "+order+" LIMIT ? OFFSET ?", listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询噪声设备: %w", err)
	}
	defer rs.Close()

	items := make([]Device, 0, q.Pager.PageSize)
	for rs.Next() {
		var d Device
		if err := rs.Scan(&d.ID, &d.Name, &d.IP, &d.DevAddr, &d.DBValue,
			&d.SendPort, &d.GroupID, &d.GroupName); err != nil {
			return nil, fmt.Errorf("扫描噪声设备行: %w", err)
		}
		items = append(items, d)
	}
	return &DeviceListResult{Items: items, Total: total}, rs.Err()
}

func (s *Service) GetDevice(ctx context.Context, id int64) (*Device, error) {
	var d Device
	err := s.db.QueryRowContext(ctx, `
		SELECT d.id, COALESCE(d.name,''), COALESCE(d.ip,''), COALESCE(d.devaddr,0),
		       COALESCE(d.dbvalue,0), COALESCE(d.sendport,0),
		       COALESCE(d.groupid,0), COALESCE(g.name,'')
		FROM sounddevice d
		LEFT JOIN soundgroupinfo g ON g.id = d.groupid
		WHERE d.id = ? LIMIT 1`, id).
		Scan(&d.ID, &d.Name, &d.IP, &d.DevAddr, &d.DBValue, &d.SendPort, &d.GroupID, &d.GroupName)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询噪声设备: %w", err)
	}
	return &d, nil
}

type DeviceInput struct {
	Name     string
	IP       string
	DevAddr  int
	SendPort int
}

func (in *DeviceInput) validate() error {
	in.Name = strings.TrimSpace(in.Name)
	in.IP = strings.TrimSpace(in.IP)
	if in.Name == "" {
		return fmt.Errorf("设备名称不能为空")
	}
	if len(in.Name) > devNameLimit {
		return fmt.Errorf("设备名称过长：按 UTF-8 计 %d 字节，上限 %d 字节（约 10 个汉字）",
			len(in.Name), devNameLimit)
	}
	// 旧版对 ip 一个字符都不校验（与终端模块 D-87 同一个毛病）
	if ip := net.ParseIP(in.IP); ip == nil || ip.To4() == nil {
		return fmt.Errorf("IP 地址格式不正确，必须是 IPv4")
	}
	// devaddr 是 tinyint(3) unsigned，填 300 会被静默截成 255 —— 指向另一台设备
	if in.DevAddr < 0 || in.DevAddr > maxDevAddr {
		return fmt.Errorf("设备地址必须在 0 ~ %d 之间", maxDevAddr)
	}
	if in.SendPort < 0 || in.SendPort > 65535 {
		return fmt.Errorf("发送端口必须在 0 ~ 65535 之间")
	}
	return nil
}

// checkDeviceFree 同一个 IP 上的同一个设备地址只能有一台探头。
//
// 旧版完全不查重，同 IP 同地址可以录入无数遍，后台按地址寻址时行为不确定。
func (s *Service) checkDeviceFree(ctx context.Context, ip string, addr int, excludeID int64) error {
	q := `SELECT id FROM sounddevice WHERE ip = ? AND devaddr = ?`
	args := []interface{}{ip, addr}
	if excludeID > 0 {
		q += ` AND id <> ?`
		args = append(args, excludeID)
	}
	q += ` LIMIT 1`
	var id int64
	err := s.db.QueryRowContext(ctx, q, args...).Scan(&id)
	if err == nil {
		return fmt.Errorf("IP %s 上的设备地址 %d 已经被占用", ip, addr)
	}
	if errors.Is(err, sql.ErrNoRows) {
		return nil
	}
	return fmt.Errorf("噪声设备查重: %w", err)
}

const deviceLock = "htweb_sound_device"

func (s *Service) CreateDevice(ctx context.Context, in DeviceInput) (int64, error) {
	if err := in.validate(); err != nil {
		return 0, err
	}
	unlock, err := store.Lock(ctx, s.db, deviceLock)
	if err != nil {
		return 0, err
	}
	defer unlock()

	if err := s.checkDeviceFree(ctx, in.IP, in.DevAddr, 0); err != nil {
		return 0, err
	}
	// groupid 与 dbvalue 都写 0：新设备还没归入声场分区，也还没采到噪声值。
	// 与旧版 `VALUES('$device_ip','$device_addr','$devicename','0','0','$sendchanne')` 一致。
	res, err := s.db.ExecContext(ctx,
		`INSERT INTO sounddevice (ip, devaddr, name, groupid, dbvalue, sendport) VALUES (?,?,?,?,?,?)`,
		in.IP, in.DevAddr, in.Name, NoGroup, 0, in.SendPort)
	if err != nil {
		return 0, fmt.Errorf("新建噪声设备: %w", err)
	}
	return res.LastInsertId()
}

func (s *Service) UpdateDevice(ctx context.Context, id int64, in DeviceInput) error {
	if _, err := s.GetDevice(ctx, id); err != nil {
		return err
	}
	if err := in.validate(); err != nil {
		return err
	}
	unlock, err := store.Lock(ctx, s.db, deviceLock)
	if err != nil {
		return err
	}
	defer unlock()

	if err := s.checkDeviceFree(ctx, in.IP, in.DevAddr, id); err != nil {
		return err
	}
	// groupid 与 dbvalue 都不在这条语句里：
	//   groupid 由声场分区页维护（这里改会绕过分区的一致性处理）
	//   dbvalue 是后台采回来的实测值，Web 不写
	// 旧版这条 UPDATE 也正好是这四列，语义一致。
	if _, err := s.db.ExecContext(ctx,
		`UPDATE sounddevice SET name = ?, ip = ?, devaddr = ?, sendport = ? WHERE id = ?`,
		in.Name, in.IP, in.DevAddr, in.SendPort, id); err != nil {
		return fmt.Errorf("修改噪声设备: %w", err)
	}
	return nil
}

func (s *Service) DeleteDevices(ctx context.Context, ids []int64) (int, error) {
	if len(ids) == 0 {
		return 0, fmt.Errorf("请先选择要删除的噪声设备")
	}
	ph, args := placeholders(ids)
	res, err := s.db.ExecContext(ctx, `DELETE FROM sounddevice WHERE id IN (`+ph+`)`, args...)
	if err != nil {
		return 0, fmt.Errorf("删除噪声设备: %w", err)
	}
	n, _ := res.RowsAffected()
	return int(n), nil
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

// ==================================================================
//                          声场分区
// ==================================================================

type Group struct {
	ID            int64  `json:"id"`
	Name          string `json:"name"`
	UserID        int64  `json:"userId"`
	UserName      string `json:"userName"`
	TerminalCount int    `json:"terminalCount"`
	DeviceCount   int    `json:"deviceCount"`
	CanModify     bool   `json:"canModify"`
}

type GroupQuery struct {
	Keyword string
	Pager   store.Pager
}

type GroupListResult struct {
	Items     []Group
	Total     int64
	ScopeNote string
}

// groupVisibleCond 是声场分区可见范围的唯一定义。
// 旧版对非管理员按 userid 过滤，搜索分支同样丢了这个条件，这里提成公共片段。
func groupVisibleCond(u *auth.User) (string, []interface{}) {
	if u.IsAdmin {
		return "", nil
	}
	return "g.userid = ?", []interface{}{u.ID}
}

func (s *Service) ListGroups(ctx context.Context, u *auth.User, q GroupQuery) (*GroupListResult, error) {
	cond := &store.Cond{}
	if c, args := groupVisibleCond(u); c != "" {
		cond.Add(c, args...)
	}
	if q.Keyword != "" {
		cond.Add(`g.name LIKE ? ESCAPE '\\'`, store.EscapeLike(q.Keyword))
	}
	where := cond.Where()
	args := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM soundgroupinfo g"+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计声场分区: %w", err)
	}

	listArgs := append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, `
		SELECT g.id, COALESCE(g.name,''), COALESCE(g.userid,0), COALESCE(b.username,'')
		FROM soundgroupinfo g
		LEFT JOIN book_admin b ON b.id = g.userid`+where+
		" ORDER BY g.id DESC LIMIT ? OFFSET ?", listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询声场分区: %w", err)
	}
	defer rs.Close()

	items := make([]Group, 0, q.Pager.PageSize)
	ids := make([]int64, 0, q.Pager.PageSize)
	for rs.Next() {
		var g Group
		if err := rs.Scan(&g.ID, &g.Name, &g.UserID, &g.UserName); err != nil {
			return nil, fmt.Errorf("扫描声场分区行: %w", err)
		}
		g.CanModify = u.IsAdmin || g.UserID == u.ID
		items = append(items, g)
		ids = append(ids, g.ID)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}
	if len(ids) > 0 {
		if err := s.fillGroupCounts(ctx, items, ids); err != nil {
			return nil, err
		}
	}

	res := &GroupListResult{Items: items, Total: total}
	if !u.IsAdmin {
		res.ScopeNote = "仅显示我创建的声场分区"
	}
	return res, nil
}

func (s *Service) fillGroupCounts(ctx context.Context, items []Group, ids []int64) error {
	terms, err := s.countBy(ctx, ids,
		`SELECT groupid, COUNT(*) FROM soundgroup WHERE groupid IN (%s) GROUP BY groupid`)
	if err != nil {
		return fmt.Errorf("统计声场分区终端数: %w", err)
	}
	devs, err := s.countBy(ctx, ids,
		`SELECT groupid, COUNT(*) FROM sounddevice WHERE groupid IN (%s) GROUP BY groupid`)
	if err != nil {
		return fmt.Errorf("统计声场分区设备数: %w", err)
	}
	for i := range items {
		items[i].TerminalCount = terms[items[i].ID]
		items[i].DeviceCount = devs[items[i].ID]
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

type GroupTerminal struct {
	TerminalID   int64  `json:"terminalId"`
	TerminalName string `json:"terminalname"`
	TypeName     string `json:"typeName"`
	IP           string `json:"ip"`
	NetState     int    `json:"netstate"`
	Deleted      bool   `json:"deleted"`
	// GroupID / GroupName 是**终端分区**，供界面把终端选择器排成树。
	// ⚠ 跟声场分区不是一回事：声场分区是这个模块自己的 soundgroupinfo。
	// 只在 TerminalOptions（候选列表）里填，GetGroup 的成员列表不需要。
	GroupID   int64  `json:"groupId"`
	GroupName string `json:"groupName"`
}

type GroupDetail struct {
	ID        int64           `json:"id"`
	Name      string          `json:"name"`
	UserID    int64           `json:"userId"`
	Terminals []GroupTerminal `json:"terminals"`
	Devices   []Device        `json:"devices"`
}

func (s *Service) GetGroup(ctx context.Context, u *auth.User, id int64) (*GroupDetail, error) {
	d := &GroupDetail{ID: id}
	var userID sql.NullInt64
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(name,''), userid FROM soundgroupinfo WHERE id = ? LIMIT 1`, id).
		Scan(&d.Name, &userID)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询声场分区: %w", err)
	}
	d.UserID = userID.Int64
	if !u.IsAdmin && d.UserID != u.ID {
		return nil, ErrNoPermission
	}

	// LEFT JOIN：终端被删后关联行可能还在，内连接会让这些脏行静默消失
	rs, err := s.db.QueryContext(ctx, `
		SELECT sg.terminalid, t.id IS NOT NULL, COALESCE(t.terminalname,''),
		       COALESCE(tt.name,''), COALESCE(t.ip,''), COALESCE(t.netstate,0)
		FROM soundgroup sg
		LEFT JOIN terminal t ON t.id = sg.terminalid
		LEFT JOIN terminaltype tt ON tt.id = t.typeid
		WHERE sg.groupid = ?
		ORDER BY sg.terminalid`, id)
	if err != nil {
		return nil, fmt.Errorf("查询声场分区终端: %w", err)
	}
	d.Terminals = []GroupTerminal{}
	for rs.Next() {
		var t GroupTerminal
		var exists bool
		if err := rs.Scan(&t.TerminalID, &exists, &t.TerminalName,
			&t.TypeName, &t.IP, &t.NetState); err != nil {
			rs.Close()
			return nil, err
		}
		t.Deleted = !exists
		if t.Deleted {
			t.TerminalName = "(终端已删除)"
		}
		d.Terminals = append(d.Terminals, t)
	}
	rs.Close()
	if err := rs.Err(); err != nil {
		return nil, err
	}

	drs, err := s.db.QueryContext(ctx, `
		SELECT id, COALESCE(name,''), COALESCE(ip,''), COALESCE(devaddr,0),
		       COALESCE(dbvalue,0), COALESCE(sendport,0)
		FROM sounddevice WHERE groupid = ? ORDER BY id`, id)
	if err != nil {
		return nil, fmt.Errorf("查询声场分区设备: %w", err)
	}
	defer drs.Close()
	d.Devices = []Device{}
	for drs.Next() {
		var dv Device
		if err := drs.Scan(&dv.ID, &dv.Name, &dv.IP, &dv.DevAddr, &dv.DBValue, &dv.SendPort); err != nil {
			return nil, err
		}
		dv.GroupID = id
		d.Devices = append(d.Devices, dv)
	}
	return d, drs.Err()
}

type GroupInput struct {
	Name        string
	TerminalIDs []int64
	DeviceIDs   []int64
}

func (s *Service) validateGroup(ctx context.Context, u *auth.User, in *GroupInput) error {
	in.Name = strings.TrimSpace(in.Name)
	if in.Name == "" {
		return fmt.Errorf("声场分区名称不能为空")
	}
	if len(in.Name) > groupNameLimit {
		return fmt.Errorf("声场分区名称过长：按 UTF-8 计 %d 字节，上限 %d 字节",
			len(in.Name), groupNameLimit)
	}
	if len(in.TerminalIDs) > maxMember {
		return fmt.Errorf("声场分区终端最多 %d 台", maxMember)
	}
	if len(in.DeviceIDs) > maxMember {
		return fmt.Errorf("声场分区噪声设备最多 %d 台", maxMember)
	}

	if len(in.TerminalIDs) > 0 {
		if err := assertNoDup(in.TerminalIDs, "终端"); err != nil {
			return err
		}
		ph, args := placeholders(in.TerminalIDs)
		var n int
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM terminal WHERE id IN (`+ph+`)`, args...).Scan(&n); err != nil {
			return fmt.Errorf("校验终端: %w", err)
		}
		if n != len(in.TerminalIDs) {
			return fmt.Errorf("终端列表里有已不存在的终端，请重新选择")
		}
		if !u.IsAdmin {
			var bound int
			bindArgs := append(append([]interface{}{}, args...), u.ID)
			if err := s.db.QueryRowContext(ctx,
				`SELECT COUNT(DISTINCT terminalid) FROM userterminal
				 WHERE terminalid IN (`+ph+`) AND userid = ?`, bindArgs...).Scan(&bound); err != nil {
				return fmt.Errorf("校验终端归属: %w", err)
			}
			if bound != len(in.TerminalIDs) {
				return fmt.Errorf("终端列表里有未绑定给你的终端")
			}
		}
	}

	if len(in.DeviceIDs) > 0 {
		if err := assertNoDup(in.DeviceIDs, "噪声设备"); err != nil {
			return err
		}
		ph, args := placeholders(in.DeviceIDs)
		var n int
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM sounddevice WHERE id IN (`+ph+`)`, args...).Scan(&n); err != nil {
			return fmt.Errorf("校验噪声设备: %w", err)
		}
		if n != len(in.DeviceIDs) {
			return fmt.Errorf("设备列表里有已不存在的噪声设备，请重新选择")
		}
	}
	return nil
}

func assertNoDup(ids []int64, what string) error {
	seen := map[int64]bool{}
	for _, id := range ids {
		if id <= 0 {
			return fmt.Errorf("%s列表里有非法的 ID", what)
		}
		if seen[id] {
			return fmt.Errorf("%s列表里有重复项", what)
		}
		seen[id] = true
	}
	return nil
}

func (s *Service) checkGroupNameFree(ctx context.Context, name string, excludeID int64) error {
	q := `SELECT id FROM soundgroupinfo WHERE name = ?`
	args := []interface{}{name}
	if excludeID > 0 {
		q += ` AND id <> ?`
		args = append(args, excludeID)
	}
	q += ` LIMIT 1`
	var id int64
	err := s.db.QueryRowContext(ctx, q, args...).Scan(&id)
	if err == nil {
		return fmt.Errorf("声场分区名称已存在")
	}
	if errors.Is(err, sql.ErrNoRows) {
		return nil
	}
	return fmt.Errorf("声场分区重名校验: %w", err)
}

const groupLock = "htweb_sound_group_name"

func (s *Service) CreateGroup(ctx context.Context, u *auth.User, in GroupInput) (int64, error) {
	if err := s.validateGroup(ctx, u, &in); err != nil {
		return 0, err
	}
	unlock, err := store.Lock(ctx, s.db, groupLock)
	if err != nil {
		return 0, err
	}
	defer unlock()

	if err := s.checkGroupNameFree(ctx, in.Name, 0); err != nil {
		return 0, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	res, err := tx.ExecContext(ctx,
		`INSERT INTO soundgroupinfo (name, userid) VALUES (?,?)`, in.Name, u.ID)
	if err != nil {
		return 0, fmt.Errorf("新建声场分区: %w", err)
	}
	// LastInsertId 而不是旧版的 SELECT MAX(id)：并发下 MAX 会拿到别人的行
	id, err := res.LastInsertId()
	if err != nil {
		return 0, err
	}
	if err := writeGroupMembers(ctx, tx, id, in.TerminalIDs, in.DeviceIDs); err != nil {
		return 0, err
	}
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return id, nil
}

func (s *Service) UpdateGroup(ctx context.Context, u *auth.User, id int64, in GroupInput) error {
	var owner sql.NullInt64
	err := s.db.QueryRowContext(ctx,
		`SELECT userid FROM soundgroupinfo WHERE id = ? LIMIT 1`, id).Scan(&owner)
	if errors.Is(err, sql.ErrNoRows) {
		return ErrNotFound
	}
	if err != nil {
		return fmt.Errorf("查询声场分区: %w", err)
	}
	if !u.IsAdmin && owner.Int64 != u.ID {
		return ErrNoPermission
	}
	if err := s.validateGroup(ctx, u, &in); err != nil {
		return err
	}

	unlock, err := store.Lock(ctx, s.db, groupLock)
	if err != nil {
		return err
	}
	defer unlock()

	if err := s.checkGroupNameFree(ctx, in.Name, id); err != nil {
		return err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx,
		`UPDATE soundgroupinfo SET name = ? WHERE id = ?`, in.Name, id); err != nil {
		return fmt.Errorf("修改声场分区: %w", err)
	}
	// 「全清再写」必须在同一个事务里 —— 旧版没有事务，中途失败分区就空了
	if err := clearGroupMembers(ctx, tx, id); err != nil {
		return err
	}
	if err := writeGroupMembers(ctx, tx, id, in.TerminalIDs, in.DeviceIDs); err != nil {
		return err
	}
	return tx.Commit()
}

// writeGroupMembers 写终端与设备归属，两处终端归属同时维护。
func writeGroupMembers(ctx context.Context, tx *sql.Tx, groupID int64, terms, devs []int64) error {
	if len(terms) > 0 {
		ph, args := placeholders(terms)
		// 一台终端只属于一个声场分区：先从别的分区摘掉。
		// terminal.soundsgroupid 是单值列，装不下第二个归属。
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM soundgroup WHERE terminalid IN (`+ph+`) AND groupid <> ?`,
			append(append([]interface{}{}, args...), groupID)...); err != nil {
			return fmt.Errorf("清理终端原有声场分区: %w", err)
		}
		for _, id := range terms {
			// 表主键是 (terminalid, groupid)，重复写会撞主键；
			// 上面刚清过别的分区，这里再对本分区做一次幂等插入。
			if _, err := tx.ExecContext(ctx,
				`INSERT IGNORE INTO soundgroup (terminalid, groupid) VALUES (?,?)`,
				id, groupID); err != nil {
				return fmt.Errorf("写入声场分区终端: %w", err)
			}
		}
		// terminal.soundsgroupid —— 旧版新建/修改时**从来没写过**（D-220），
		// 只在删除分区时才复位，两套归属长期不一致。
		if _, err := tx.ExecContext(ctx,
			`UPDATE terminal SET soundsgroupid = ? WHERE id IN (`+ph+`)`,
			append([]interface{}{groupID}, args...)...); err != nil {
			return fmt.Errorf("同步终端声场分区号: %w", err)
		}
	}
	if len(devs) > 0 {
		ph, args := placeholders(devs)
		if _, err := tx.ExecContext(ctx,
			`UPDATE sounddevice SET groupid = ? WHERE id IN (`+ph+`)`,
			append([]interface{}{groupID}, args...)...); err != nil {
			return fmt.Errorf("写入声场分区设备: %w", err)
		}
	}
	return nil
}

// clearGroupMembers 清空归属，并把终端与设备的分区号复位。
func clearGroupMembers(ctx context.Context, tx *sql.Tx, groupID int64) error {
	// 顺序要紧：先按当前成员复位 terminal.soundsgroupid，再删关联行，
	// 否则就找不到该复位哪些终端了。
	if _, err := tx.ExecContext(ctx, `
		UPDATE terminal SET soundsgroupid = ?
		WHERE id IN (SELECT terminalid FROM soundgroup WHERE groupid = ?)`,
		NoGroup, groupID); err != nil {
		return fmt.Errorf("复位终端声场分区号: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM soundgroup WHERE groupid = ?`, groupID); err != nil {
		return fmt.Errorf("清理声场分区终端: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`UPDATE sounddevice SET groupid = ? WHERE groupid = ?`, NoGroup, groupID); err != nil {
		return fmt.Errorf("复位噪声设备分区号: %w", err)
	}
	return nil
}

type GroupBlocked struct {
	ID     int64  `json:"id"`
	Name   string `json:"name"`
	Reason string `json:"reason"`
	Detail string `json:"detail"`
}

type GroupDeleteResult struct {
	Deleted        []int64        `json:"deleted"`
	ResetTerminals int            `json:"resetTerminals"`
	ResetDevices   int            `json:"resetDevices"`
	Blocked        []GroupBlocked `json:"blocked"`
}

func (s *Service) DeleteGroups(ctx context.Context, u *auth.User, ids []int64) (*GroupDeleteResult, error) {
	if len(ids) == 0 {
		return nil, fmt.Errorf("请先选择要删除的声场分区")
	}
	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx,
		`SELECT id, COALESCE(name,''), COALESCE(userid,0) FROM soundgroupinfo WHERE id IN (`+ph+`)`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询待删声场分区: %w", err)
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
			return nil, err
		}
		found[id] = r
	}
	rs.Close()
	if err := rs.Err(); err != nil {
		return nil, err
	}

	out := &GroupDeleteResult{Deleted: []int64{}, Blocked: []GroupBlocked{}}
	del := []int64{}
	for _, id := range ids {
		r, exists := found[id]
		if !exists {
			out.Blocked = append(out.Blocked, GroupBlocked{ID: id, Reason: "NOT_FOUND", Detail: "声场分区不存在"})
			continue
		}
		if !u.IsAdmin && r.owner != u.ID {
			out.Blocked = append(out.Blocked, GroupBlocked{ID: id, Name: r.name,
				Reason: "NOT_OWNER", Detail: "只能删除自己创建的声场分区"})
			continue
		}
		del = append(del, id)
	}
	if len(del) == 0 {
		return out, nil
	}

	dph, dargs := placeholders(del)
	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 终端归属两条路径都复位：既按关联表里的成员，也按 terminal 自己记的值 ——
	// 旧数据里这两套本来就不一致（D-220 的后果）。
	res, err := tx.ExecContext(ctx, `
		UPDATE terminal SET soundsgroupid = ?
		WHERE id IN (SELECT terminalid FROM soundgroup WHERE groupid IN (`+dph+`))`,
		append([]interface{}{NoGroup}, dargs...)...)
	if err != nil {
		return nil, fmt.Errorf("复位终端声场分区号: %w", err)
	}
	n1, _ := res.RowsAffected()
	res, err = tx.ExecContext(ctx,
		`UPDATE terminal SET soundsgroupid = ? WHERE soundsgroupid IN (`+dph+`)`,
		append([]interface{}{NoGroup}, dargs...)...)
	if err != nil {
		return nil, fmt.Errorf("复位终端声场分区号(按 soundsgroupid): %w", err)
	}
	n2, _ := res.RowsAffected()
	out.ResetTerminals = int(n1 + n2)

	if _, err := tx.ExecContext(ctx,
		`DELETE FROM soundgroup WHERE groupid IN (`+dph+`)`, dargs...); err != nil {
		return nil, fmt.Errorf("清理声场分区终端: %w", err)
	}

	res, err = tx.ExecContext(ctx,
		`UPDATE sounddevice SET groupid = ? WHERE groupid IN (`+dph+`)`,
		append([]interface{}{NoGroup}, dargs...)...)
	if err != nil {
		return nil, fmt.Errorf("复位噪声设备分区号: %w", err)
	}
	nd, _ := res.RowsAffected()
	out.ResetDevices = int(nd)

	if _, err := tx.ExecContext(ctx,
		`DELETE FROM soundgroupinfo WHERE id IN (`+dph+`)`, dargs...); err != nil {
		return nil, fmt.Errorf("删除声场分区: %w", err)
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	out.Deleted = del
	return out, nil
}

// ---------- 选择器 ----------

type Option struct {
	ID   int64  `json:"id"`
	Name string `json:"name"`
}

func (s *Service) GroupOptions(ctx context.Context, u *auth.User) ([]Option, error) {
	cond := &store.Cond{}
	if c, args := groupVisibleCond(u); c != "" {
		cond.Add(c, args...)
	}
	rs, err := s.db.QueryContext(ctx,
		`SELECT g.id, COALESCE(g.name,'') FROM soundgroupinfo g`+cond.Where()+
			` ORDER BY g.name`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询声场分区选项: %w", err)
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

// DeviceOptions 列出可加入声场分区的噪声设备，带当前归属。
func (s *Service) DeviceOptions(ctx context.Context, keyword string) ([]Device, error) {
	cond := &store.Cond{}
	if keyword = strings.TrimSpace(keyword); keyword != "" {
		kw := store.EscapeLike(keyword)
		cond.Add(`(d.name LIKE ? ESCAPE '\\' OR d.ip LIKE ? ESCAPE '\\')`, kw, kw)
	}
	rs, err := s.db.QueryContext(ctx, `
		SELECT d.id, COALESCE(d.name,''), COALESCE(d.ip,''), COALESCE(d.devaddr,0),
		       COALESCE(d.dbvalue,0), COALESCE(d.sendport,0),
		       COALESCE(d.groupid,0), COALESCE(g.name,'')
		FROM sounddevice d
		LEFT JOIN soundgroupinfo g ON g.id = d.groupid`+cond.Where()+
		` ORDER BY d.id LIMIT 500`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询噪声设备选项: %w", err)
	}
	defer rs.Close()
	out := []Device{}
	for rs.Next() {
		var d Device
		if err := rs.Scan(&d.ID, &d.Name, &d.IP, &d.DevAddr, &d.DBValue,
			&d.SendPort, &d.GroupID, &d.GroupName); err != nil {
			return nil, err
		}
		out = append(out, d)
	}
	return out, rs.Err()
}

// TerminalOptions 列出可加入声场分区的终端，带当前声场归属。
func (s *Service) TerminalOptions(ctx context.Context, u *auth.User, keyword string) ([]GroupTerminal, error) {
	cond := &store.Cond{}
	if !u.IsAdmin {
		cond.Add(`t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)`, u.ID)
	}
	if keyword = strings.TrimSpace(keyword); keyword != "" {
		cond.Add(`t.terminalname LIKE ? ESCAPE '\\'`, store.EscapeLike(keyword))
	}
	// groupName 是**终端分区**名（不是声场分区）——界面上的终端选择器按它分组成树。
	// 分区名在 serverplaystream，终端与分区的关系在 terminalofgroup；
	// 一台终端理论上可能有多行，取 id 最小的那条，与 task/picker.go 的口径一致。
	rs, err := s.db.QueryContext(ctx, `
		SELECT t.id, COALESCE(t.terminalname,''), COALESCE(tt.name,''),
		       COALESCE(t.ip,''), COALESCE(t.netstate,0),
		       COALESCE((SELECT tog.groupid FROM terminalofgroup tog
		                 WHERE tog.terminalid = t.id ORDER BY tog.id LIMIT 1), 0),
		       COALESCE((SELECT sps.name FROM terminalofgroup tog
		                 JOIN serverplaystream sps ON sps.streamid = tog.groupid
		                 WHERE tog.terminalid = t.id ORDER BY tog.id LIMIT 1), '')
		FROM terminal t
		LEFT JOIN terminaltype tt ON tt.id = t.typeid`+cond.Where()+
		` ORDER BY t.netstate DESC, t.id LIMIT 500`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询终端: %w", err)
	}
	defer rs.Close()
	out := []GroupTerminal{}
	for rs.Next() {
		var t GroupTerminal
		if err := rs.Scan(&t.TerminalID, &t.TerminalName, &t.TypeName,
			&t.IP, &t.NetState, &t.GroupID, &t.GroupName); err != nil {
			return nil, err
		}
		out = append(out, t)
	}
	return out, rs.Err()
}
