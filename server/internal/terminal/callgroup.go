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
//   - 这台终端没有任何 callgroup → 它能寻呼所有在线终端（默认）
//   - 建了 callgroup 并挂上成员 → 只能寻呼这些成员
//
// 所以「清空授权」不等于「谁都不能呼」，而是回到默认的全放开状态 ——
// 这一点很容易搞反，删干净反而是权限最大的那种情况。
//
// # 数据结构
//
//	callgroup            (id, name, terminalid)      宿主终端的授权组
//	terminalofcallgroup  (selectgroupid, terminalid, area, groupid)  组成员
//
// 一台终端只需要一个组，ok112 的界面也只能建一个。这里因此把它收敛成
// 「读改一台终端的授权名单」这一个操作，不把 callgroup 的 id 暴露给前端 ——
// 暴露了只会让调用方去纠结「该建组还是该改组」，而答案永远是「都行，看有没有」。
//
// # flag=1 与 flag=2 的区别只在界面
//
// 「授权寻呼」按终端列表选，「授权终端」按分区树选，落到库里是同一张表、
// 同一份名单。后端只提供一套接口，两个入口共用。

// CallGroupMember 是授权名单里的一台终端。
type CallGroupMember struct {
	ID      int64  `json:"id"`
	Name    string `json:"name"`
	TypeID  int    `json:"typeId"`
	IP      string `json:"ip"`
	Online  bool   `json:"online"`
	GroupID int64  `json:"groupId"`
	// Missing 表示这条成员指向的终端已经不存在了（旧库里有这种脏行）。
	Missing bool `json:"missing"`
}

// CallGroupInfo 是一台终端的寻呼授权名单。
type CallGroupInfo struct {
	TerminalID   int64  `json:"terminalId"`
	TerminalName string `json:"terminalName"`
	// Configured 为 false 表示没建过授权组 —— 按默认规则可以寻呼所有在线终端。
	Configured bool              `json:"configured"`
	Name       string            `json:"name"`
	Members    []CallGroupMember `json:"members"`
}

// ListCallGroup 读一台终端的寻呼授权名单。
func (s *Service) ListCallGroup(ctx context.Context, terminalID int64) (*CallGroupInfo, error) {
	out := &CallGroupInfo{TerminalID: terminalID, Members: []CallGroupMember{}}

	if err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(terminalname,'') FROM terminal WHERE id = ?`,
		terminalID).Scan(&out.TerminalName); err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrNotFound
		}
		return nil, fmt.Errorf("查询终端: %w", err)
	}

	var groupID int64
	err := s.db.QueryRowContext(ctx,
		`SELECT id, COALESCE(name,'') FROM callgroup WHERE terminalid = ? ORDER BY id LIMIT 1`,
		terminalID).Scan(&groupID, &out.Name)
	if errors.Is(err, sql.ErrNoRows) {
		return out, nil // 没配过 —— Configured 保持 false
	}
	if err != nil {
		return nil, fmt.Errorf("查询授权组: %w", err)
	}
	out.Configured = true

	// LEFT JOIN：成员指向的终端可能已被删掉，内连接会让这些脏行静默消失，
	// 界面上就会出现「名单里明明有 3 台，只显示 2 台」这种说不清的情况。
	rs, err := s.db.QueryContext(ctx, `
		SELECT g.terminalid, COALESCE(t.terminalname,''), COALESCE(t.typeid,0),
		       COALESCE(t.ip,''), COALESCE(t.netstate,0), COALESCE(g.groupid,0),
		       (t.id IS NULL)
		FROM terminalofcallgroup g
		LEFT JOIN terminal t ON t.id = g.terminalid
		WHERE g.selectgroupid = ?
		ORDER BY g.terminalid`, groupID)
	if err != nil {
		return nil, fmt.Errorf("查询授权成员: %w", err)
	}
	defer rs.Close()
	for rs.Next() {
		var m CallGroupMember
		var net int
		if err := rs.Scan(&m.ID, &m.Name, &m.TypeID, &m.IP, &net, &m.GroupID, &m.Missing); err != nil {
			return nil, fmt.Errorf("读取授权成员: %w", err)
		}
		m.Online = net == 1
		out.Members = append(out.Members, m)
	}
	return out, rs.Err()
}

// SetCallGroup 覆盖式地写一台终端的寻呼授权名单。
//
// memberIDs 为空表示**取消授权**：删掉整个组，回到「可寻呼所有在线终端」
// 的默认状态。这与 ok112 「停止寻呼」的效果一致。
func (s *Service) SetCallGroup(ctx context.Context, u *auth.User,
	terminalID int64, name string, memberIDs []int64) error {

	ok, skipped, err := s.CheckBound(ctx, u, []int64{terminalID})
	if err != nil {
		return err
	}
	if len(ok) == 0 {
		if len(skipped) > 0 {
			return fmt.Errorf("%w: %s", ErrNoPermission, skipped[0].Detail)
		}
		return ErrNotFound
	}

	// 宿主终端本身必须有寻呼能力，否则这份名单永远不会被用到
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

	// 名单里不该有自己 —— 自己寻呼自己没有意义，旧版也没拦，
	// 留着只会在界面上多出一行看不懂的记录。
	clean := make([]int64, 0, len(memberIDs))
	seen := map[int64]bool{terminalID: true}
	for _, id := range memberIDs {
		if id <= 0 || seen[id] {
			continue
		}
		seen[id] = true
		clean = append(clean, id)
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	var groupID int64
	err = tx.QueryRowContext(ctx,
		`SELECT id FROM callgroup WHERE terminalid = ? ORDER BY id LIMIT 1`,
		terminalID).Scan(&groupID)
	switch {
	case errors.Is(err, sql.ErrNoRows):
		groupID = 0
	case err != nil:
		return fmt.Errorf("查询授权组: %w", err)
	}

	// 清空名单 = 取消授权 = 删组。注意这是「放开到默认」，不是「全部禁止」。
	if len(clean) == 0 {
		if groupID == 0 {
			return nil
		}
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM terminalofcallgroup WHERE selectgroupid = ?`, groupID); err != nil {
			return fmt.Errorf("清理授权成员: %w", err)
		}
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM callgroup WHERE id = ?`, groupID); err != nil {
			return fmt.Errorf("删除授权组: %w", err)
		}
		return tx.Commit()
	}

	if name = strings.TrimSpace(name); name == "" {
		name = "寻呼授权"
	}
	if groupID == 0 {
		res, err := tx.ExecContext(ctx,
			`INSERT INTO callgroup (name, terminalid) VALUES (?, ?)`, name, terminalID)
		if err != nil {
			return fmt.Errorf("创建授权组: %w", err)
		}
		if groupID, err = res.LastInsertId(); err != nil {
			return fmt.Errorf("读取授权组 ID: %w", err)
		}
	} else if _, err := tx.ExecContext(ctx,
		`UPDATE callgroup SET name = ? WHERE id = ?`, name, groupID); err != nil {
		return fmt.Errorf("更新授权组: %w", err)
	}

	// 覆盖式写：先清后插。名单通常只有几十条，增量对比不值当，
	// 而且整段在一个事务里，中途失败会整体回滚。
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminalofcallgroup WHERE selectgroupid = ?`, groupID); err != nil {
		return fmt.Errorf("清理旧成员: %w", err)
	}
	// groupid 存的是成员终端当时所属的分区，与 terminaloftask 同一套写法
	for _, id := range clean {
		if _, err := tx.ExecContext(ctx, `
			INSERT INTO terminalofcallgroup (selectgroupid, terminalid, area, groupid)
			SELECT ?, id, ?, COALESCE(groupid,0) FROM terminal WHERE id = ?`,
			groupID, DefaultArea, id); err != nil {
			return fmt.Errorf("写入授权成员: %w", err)
		}
	}
	return tx.Commit()
}

// ErrAuthPagingUnsupported 宿主终端型号不支持寻呼授权。
var ErrAuthPagingUnsupported = errors.New("该终端型号不支持寻呼授权")
