package terminal

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"

	"htweb/internal/auth"
)

// 授权寻呼 / 授权终端（ok112 的 view_terminal_call_group.php?flag=1
// 与 dirstreammanager.php?flag=2）。
//
// # 语义
//
// 语言表把它说得很清楚：
//
//	「授权终端可以被寻呼的终端，缺省状态有寻呼功能的终端可以寻呼所有在线的终端」
//
// 也就是说这是一份**白名单**，而且是「不配置即全放开」：
//
//   - 这台终端一个寻呼分区都没有 → 它能寻呼所有在线终端（默认）
//   - 建了分区并挂上成员      → 只能寻呼这些成员
//
// 所以「删光分区」不等于「谁都不能呼」，而是回到默认的全放开状态 ——
// 这一点很容易搞反，删干净反而是权限最大的那种情况。
//
// # 一台终端可以有多个寻呼分区
//
// ⚠ 这里以前收敛成「一台终端一份名单」，是错的。
//   view_terminal_call_group.php 的主查询是
//
//	SELECT * FROM callgroup WHERE terminalid = $terminal_id ORDER BY callgroup.id DESC
//
//   带分页、带按 name 搜索与排序，界面上有「添加分区 / 修改分区 / 删除分区 /
//   浏览终端」四个动作 —— 是一张**列表**，不是一份名单。
//   addcallzone_msg 也是按 (terminalid, name) 去重的：同名覆盖，不同名新增。
//   收敛成一个组的后果是，旧库里一台终端有三个分区时，界面只显示得出一个，
//   保存一次把另外两个的成员全丢了。
//
// # 数据结构
//
//	callgroup            (id, name, terminalid)                        宿主终端的寻呼分区
//	terminalofcallgroup  (selectgroupid, terminalid, area, groupid)     分区成员
//
// # flag=1 与 flag=2 的区别只在挑终端的方式
//
// 「授权寻呼」按分区树选，「授权终端」带目录（terminaloffolder）。
// 落到库里是同一张表、同一份数据，后端只提供一套接口，两个入口共用。

// callGroupMemberExclude 是**成员**（可以被寻呼的那一方）的型号排除表。
//
// 来自 ok112 inc/config.inc.php 的 get_terminal_type(3)：
//
//	SELECT id FROM terminaltype
//	WHERE isdecode = '1'
//	  AND id NOT IN (0,26,2,7,8,9,10,12,15,16,17,21,22,25,28,29,30,31,32,36,37,40,41,42)
//
// ⚠ 与宿主那一侧（caps.go 的 authPagingExclude，来自 get_terminal_type(2)）
//
//	是两张不同的表，判据也不同（成员看 isdecode，宿主看 isencode）。别合并。
var callGroupMemberExclude = setOf(0, 2, 7, 8, 9, 10, 12, 15, 16, 17, 21, 22,
	25, 26, 28, 29, 30, 31, 32, 36, 37, 40, 41, 42)

// CallGroup 是列表里的一行：一台终端的一个寻呼分区。
type CallGroup struct {
	ID          int64  `json:"id"`
	Name        string `json:"name"`
	TerminalID  int64  `json:"terminalId"`
	MemberCount int    `json:"memberCount"`
}

// CallGroupMember 是分区里的一台终端。
//
// 字段按 ok112「浏览终端」页（view_terminal_call.php）的列来配：
// 序号 / 终端名称 / 终端类型 / 网络状态 / 设备状态 / 任务状态 / IP地址 / 音量。
type CallGroupMember struct {
	ID          int64  `json:"id"`
	Name        string `json:"name"`
	TypeID      int    `json:"typeId"`
	TypeName    string `json:"typeName"`
	NetState    int    `json:"netstate"`
	DeviceState int    `json:"devicestate"`
	TaskState   int    `json:"taskstate"`
	IP          string `json:"ip"`
	Volume      int    `json:"volume"`
	GroupID     int64  `json:"groupId"`
	GroupName   string `json:"groupName"`
	// Missing 表示这条成员指向的终端已经不存在了（旧库里有这种脏行）。
	Missing bool `json:"missing"`
}

// CallGroupDetail 是一个寻呼分区的完整内容，「浏览终端」与「修改分区」共用。
type CallGroupDetail struct {
	ID           int64             `json:"id"`
	Name         string            `json:"name"`
	TerminalID   int64             `json:"terminalId"`
	TerminalName string            `json:"terminalName"`
	Members      []CallGroupMember `json:"members"`
}

// CallGroupCandidate 是可以被加进分区的一台终端（树的数据源）。
type CallGroupCandidate struct {
	ID        int64  `json:"id"`
	Name      string `json:"terminalname"`
	TypeID    int    `json:"typeId"`
	TypeName  string `json:"typeName"`
	IP        string `json:"ip"`
	NetState  int    `json:"netstate"`
	GroupID   int64  `json:"groupId"`
	GroupName string `json:"groupName"`
}

// callGroupOrderBy 把前端传来的排序字段映射成列名。
//
// ok112 的下拉里只有「分区名称」一项，这里照办 —— 白名单而不是拼字符串，
// 免得 orderBy 变成注入点（ok112 那边正是直接拼进 SQL 的）。
var callGroupOrderBy = map[string]string{"name": "g.name"}

// ListCallGroups 列出一台终端的寻呼分区。
//
// keyword 按分区名模糊匹配，orderBy 只认 "name"；都为空时按 id 倒序 ——
// 与 ok112 的 ORDER BY callgroup.id DESC 一致。
func (s *Service) ListCallGroups(ctx context.Context, terminalID int64,
	keyword, orderBy string) ([]CallGroup, error) {

	if err := s.assertTerminalExists(ctx, terminalID); err != nil {
		return nil, err
	}

	q := `
		SELECT g.id, COALESCE(g.name,''), g.terminalid,
		       (SELECT COUNT(*) FROM terminalofcallgroup m WHERE m.selectgroupid = g.id)
		FROM callgroup g WHERE g.terminalid = ?`
	args := []interface{}{terminalID}
	if kw := strings.TrimSpace(keyword); kw != "" {
		q += ` AND g.name LIKE ?`
		args = append(args, "%"+kw+"%")
	}
	if col, ok := callGroupOrderBy[strings.TrimSpace(orderBy)]; ok {
		q += ` ORDER BY ` + col + ` ASC`
	} else {
		q += ` ORDER BY g.id DESC`
	}

	rs, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询寻呼分区: %w", err)
	}
	defer rs.Close()

	out := []CallGroup{}
	for rs.Next() {
		var g CallGroup
		if err := rs.Scan(&g.ID, &g.Name, &g.TerminalID, &g.MemberCount); err != nil {
			return nil, fmt.Errorf("读取寻呼分区: %w", err)
		}
		out = append(out, g)
	}
	return out, rs.Err()
}

// GetCallGroup 读一个寻呼分区的名称与成员。
//
// 「浏览终端」直接显示 Members；「修改分区」拿它回填名称和树上的勾选。
func (s *Service) GetCallGroup(ctx context.Context, groupID int64) (*CallGroupDetail, error) {
	out := &CallGroupDetail{ID: groupID, Members: []CallGroupMember{}}

	if err := s.db.QueryRowContext(ctx, `
		SELECT COALESCE(g.name,''), g.terminalid, COALESCE(t.terminalname,'')
		FROM callgroup g LEFT JOIN terminal t ON t.id = g.terminalid
		WHERE g.id = ?`, groupID).
		Scan(&out.Name, &out.TerminalID, &out.TerminalName); err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrNotFound
		}
		return nil, fmt.Errorf("查询寻呼分区: %w", err)
	}

	// LEFT JOIN：成员指向的终端可能已被删掉，内连接会让这些脏行静默消失，
	// 界面上就会出现「名单里明明有 3 台，只显示 2 台」这种说不清的情况。
	rs, err := s.db.QueryContext(ctx, `
		SELECT m.terminalid, COALESCE(t.terminalname,''), COALESCE(t.typeid,0),
		       COALESCE(tt.name,''), COALESCE(t.netstate,0), COALESCE(t.devicestate,0),
		       COALESCE(t.taskstate,0), COALESCE(t.ip,''), COALESCE(t.volume,0),
		       COALESCE(m.groupid,0), COALESCE(sp.name,''), (t.id IS NULL)
		FROM terminalofcallgroup m
		LEFT JOIN terminal t ON t.id = m.terminalid
		LEFT JOIN terminaltype tt ON tt.id = t.typeid
		LEFT JOIN serverplaystream sp ON sp.streamid = m.groupid
		WHERE m.selectgroupid = ?
		ORDER BY m.terminalid`, groupID)
	if err != nil {
		return nil, fmt.Errorf("查询分区成员: %w", err)
	}
	defer rs.Close()
	for rs.Next() {
		var m CallGroupMember
		if err := rs.Scan(&m.ID, &m.Name, &m.TypeID, &m.TypeName, &m.NetState,
			&m.DeviceState, &m.TaskState, &m.IP, &m.Volume,
			&m.GroupID, &m.GroupName, &m.Missing); err != nil {
			return nil, fmt.Errorf("读取分区成员: %w", err)
		}
		out.Members = append(out.Members, m)
	}
	return out, rs.Err()
}

// CallGroupCandidates 列出可以被加进寻呼分区的终端，供树使用。
//
// 范围照 ok112 get_terminallistoggroup2 里的 `terminal.id not in ($streamid)`：
// 排除宿主终端自己 —— 自己寻呼自己没有意义。
func (s *Service) CallGroupCandidates(ctx context.Context, u *auth.User,
	terminalID int64) ([]CallGroupCandidate, error) {

	if err := s.assertTerminalExists(ctx, terminalID); err != nil {
		return nil, err
	}

	q := `
		SELECT t.id, COALESCE(t.terminalname,''), COALESCE(t.typeid,0), COALESCE(tt.name,''),
		       COALESCE(t.ip,''), COALESCE(t.netstate,0),
		       COALESCE(og.groupid,0), COALESCE(sp.name,'')
		FROM terminal t
		JOIN terminaltype tt ON tt.id = t.typeid
		LEFT JOIN terminalofgroup og ON og.terminalid = t.id
		LEFT JOIN serverplaystream sp ON sp.streamid = og.groupid
		WHERE COALESCE(tt.isdecode,0) = 1 AND t.id <> ?`
	args := []interface{}{terminalID}

	// 普通用户只看得到绑给自己的终端（ok112 的 userterminal 子查询）
	if !u.IsAdmin {
		q += ` AND t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)`
		args = append(args, u.ID)
	}
	q += ` ORDER BY t.terminalname`

	rs, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询候选终端: %w", err)
	}
	defer rs.Close()

	out := []CallGroupCandidate{}
	for rs.Next() {
		var c CallGroupCandidate
		if err := rs.Scan(&c.ID, &c.Name, &c.TypeID, &c.TypeName,
			&c.IP, &c.NetState, &c.GroupID, &c.GroupName); err != nil {
			return nil, fmt.Errorf("读取候选终端: %w", err)
		}
		// 型号排除表放在这里过而不是写进 SQL：判据是一张常量表，
		// 拼成 NOT IN (...) 只会让这条本就很长的查询更难读。
		if in(callGroupMemberExclude, c.TypeID) {
			continue
		}
		out = append(out, c)
	}
	return out, rs.Err()
}

// ErrCallGroupNameRequired 分区名为空。
var ErrCallGroupNameRequired = errors.New("请填写寻呼分区名称")

// ErrCallGroupEmpty 分区里一台终端都没有。
//
// ok112 的 checkform() 在提交前拦：`if(isNull(target)) alert(terminal_map)`。
// 空分区没有任何意义 —— 它既不放开也不限制，只是一条查询不到成员的记录。
var ErrCallGroupEmpty = errors.New("请至少选择一台终端")

// SaveCallGroup 新增或修改一个寻呼分区。
//
// groupID = 0 表示新增。此时按 ok112 addcallzone_msg 的做法**按 (terminalid, name)
// 去重**：同名的已存在就覆盖它的成员，不存在才插新行。这不是「顺手做的兼容」，
// 是旧版的实际行为 —— 用户重复提交同一个名字时不会攒出一堆同名分区。
//
// groupID > 0 表示修改已有分区，此时 name 可以改。
func (s *Service) SaveCallGroup(ctx context.Context, u *auth.User,
	terminalID, groupID int64, name string, memberIDs []int64) (int64, error) {

	name = strings.TrimSpace(name)
	if name == "" {
		return 0, ErrCallGroupNameRequired
	}

	ok, skipped, err := s.CheckBound(ctx, u, []int64{terminalID})
	if err != nil {
		return 0, err
	}
	if len(ok) == 0 {
		if len(skipped) > 0 {
			return 0, fmt.Errorf("%w: %s", ErrNoPermission, skipped[0].Detail)
		}
		return 0, ErrNotFound
	}

	if err := s.assertCanAuthPaging(ctx, terminalID); err != nil {
		return 0, err
	}

	// 名单里不该有自己 —— 自己寻呼自己没有意义，旧版靠树上不列出自己来避免，
	// 这里再兜一道，顺便去重。
	clean := make([]int64, 0, len(memberIDs))
	seen := map[int64]bool{terminalID: true}
	for _, id := range memberIDs {
		if id <= 0 || seen[id] {
			continue
		}
		seen[id] = true
		clean = append(clean, id)
	}
	if len(clean) == 0 {
		return 0, ErrCallGroupEmpty
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// ok112 这里是 LOCK TABLES + START TRANSACTION，中间散落着七八处 ROLLBACK，
	// 漏一处就是半条数据。这里整段一个事务，出错整体回滚。
	if groupID == 0 {
		// 新增：先看同名的在不在（ok112 addcallzone_msg 的 key_sql）
		err := tx.QueryRowContext(ctx,
			`SELECT id FROM callgroup WHERE terminalid = ? AND name = ? LIMIT 1`,
			terminalID, name).Scan(&groupID)
		switch {
		case errors.Is(err, sql.ErrNoRows):
			res, err := tx.ExecContext(ctx,
				`INSERT INTO callgroup (name, terminalid) VALUES (?, ?)`, name, terminalID)
			if err != nil {
				return 0, fmt.Errorf("创建寻呼分区: %w", err)
			}
			if groupID, err = res.LastInsertId(); err != nil {
				return 0, fmt.Errorf("读取分区 ID: %w", err)
			}
		case err != nil:
			return 0, fmt.Errorf("查询同名分区: %w", err)
		}
	} else {
		// 修改：分区必须属于这台终端，否则改号就能改到别人的分区上
		var owner int64
		if err := tx.QueryRowContext(ctx,
			`SELECT terminalid FROM callgroup WHERE id = ?`, groupID).Scan(&owner); err != nil {
			if errors.Is(err, sql.ErrNoRows) {
				return 0, ErrNotFound
			}
			return 0, fmt.Errorf("查询寻呼分区: %w", err)
		}
		if owner != terminalID {
			return 0, ErrNotFound
		}
		if _, err := tx.ExecContext(ctx,
			`UPDATE callgroup SET name = ? WHERE id = ?`, name, groupID); err != nil {
			return 0, fmt.Errorf("更新分区名称: %w", err)
		}
	}

	// 覆盖式写：先清后插，与 ok112 一致。成员通常只有几十条，增量对比不值当。
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminalofcallgroup WHERE selectgroupid = ?`, groupID); err != nil {
		return 0, fmt.Errorf("清理旧成员: %w", err)
	}
	// groupid 存成员终端当时所属的分区，与 terminaloftask 同一套写法
	for _, id := range clean {
		if _, err := tx.ExecContext(ctx, `
			INSERT INTO terminalofcallgroup (selectgroupid, terminalid, area, groupid)
			SELECT ?, t.id, ?, COALESCE(og.groupid,0)
			FROM terminal t LEFT JOIN terminalofgroup og ON og.terminalid = t.id
			WHERE t.id = ? LIMIT 1`,
			groupID, DefaultArea, id); err != nil {
			return 0, fmt.Errorf("写入分区成员: %w", err)
		}
	}
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return groupID, nil
}

// DeleteCallGroups 删除若干寻呼分区，连成员一起清掉。
//
// ⚠ 删光一台终端的全部分区 = 回到「可寻呼所有在线终端」的默认状态，
//
//	不是「谁都不能呼」。界面上要把这句说清楚。
func (s *Service) DeleteCallGroups(ctx context.Context, u *auth.User,
	terminalID int64, groupIDs []int64) (int, error) {

	if len(groupIDs) == 0 {
		return 0, nil
	}

	ok, skipped, err := s.CheckBound(ctx, u, []int64{terminalID})
	if err != nil {
		return 0, err
	}
	if len(ok) == 0 {
		if len(skipped) > 0 {
			return 0, fmt.Errorf("%w: %s", ErrNoPermission, skipped[0].Detail)
		}
		return 0, ErrNotFound
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	ph, args := placeholders(groupIDs)
	// terminalid 一并带上：只允许删这台终端自己的分区。
	// ok112 的 delcallzone 直接 DELETE ... WHERE id IN($streamid)，
	// id 从 URL 来、不校验归属，改一下地址栏就能删别人的分区。
	args = append(args, terminalID)

	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminalofcallgroup WHERE selectgroupid IN
		 (SELECT id FROM (SELECT id FROM callgroup WHERE id IN (`+ph+`) AND terminalid = ?) x)`,
		args...); err != nil {
		return 0, fmt.Errorf("清理分区成员: %w", err)
	}
	res, err := tx.ExecContext(ctx,
		`DELETE FROM callgroup WHERE id IN (`+ph+`) AND terminalid = ?`, args...)
	if err != nil {
		return 0, fmt.Errorf("删除寻呼分区: %w", err)
	}
	n, _ := res.RowsAffected()
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return int(n), nil
}

// assertTerminalExists 确认终端在。
func (s *Service) assertTerminalExists(ctx context.Context, terminalID int64) error {
	var one int
	err := s.db.QueryRowContext(ctx, `SELECT 1 FROM terminal WHERE id = ?`, terminalID).Scan(&one)
	if errors.Is(err, sql.ErrNoRows) {
		return ErrNotFound
	}
	if err != nil {
		return fmt.Errorf("查询终端: %w", err)
	}
	return nil
}

// assertCanAuthPaging 确认宿主终端型号真的能寻呼，否则这份名单永远不会被用到。
func (s *Service) assertCanAuthPaging(ctx context.Context, terminalID int64) error {
	var tr TypeTraits
	var dec, enc, lcd, spk int
	if err := s.db.QueryRowContext(ctx, `
		SELECT tt.id, COALESCE(tt.isdecode,0), COALESCE(tt.isencode,0),
		       COALESCE(tt.isLCD,0), COALESCE(tt.isspeech,0),
		       COALESCE(tt.shortkeycount,0), COALESCE(tt.switchcount,0)
		FROM terminal t JOIN terminaltype tt ON tt.id = t.typeid
		WHERE t.id = ?`, terminalID).
		Scan(&tr.TypeID, &dec, &enc, &lcd, &spk, &tr.ShortKeyCount, &tr.SwitchCount); err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return ErrNotFound
		}
		return fmt.Errorf("查询终端类型: %w", err)
	}
	tr.IsDecode, tr.IsEncode, tr.IsLCD, tr.IsSpeech = dec == 1, enc == 1, lcd >= 1, spk == 1
	if !CapsOf(tr).AuthPaging {
		return ErrAuthPagingUnsupported
	}
	return nil
}

// ErrAuthPagingUnsupported 宿主终端型号不支持寻呼授权。
var ErrAuthPagingUnsupported = errors.New("该终端型号不支持寻呼授权")
