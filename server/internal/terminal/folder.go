package terminal

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"

	"htweb/internal/auth"
)

// 授权终端的「目录」（ok112 的 dirstreammanager.php?flag=2）。
//
// # 授权终端和授权寻呼的关系
//
// 两者写的是同一张 callgroup / terminalofcallgroup 表 —— 都是「这台终端能
// 寻呼谁」的白名单。区别只在**挑终端的方式**：
//
//	授权寻呼 flag=1  按终端分区（serverplaystream）挑，get_terminallistoggroup2
//	授权终端 flag=2  按目录（terminalfolder）挑，get_dirarea
//
// 目录是**每台宿主终端各有一套**的（terminalfolder.terminalid），和全局的
// 终端分区完全无关：同一台被寻呼的终端可以同时在 A 主机的「一楼」目录里、
// 在 B 主机的「教学区」目录里。所以授权终端比授权寻呼多一层「目录管理」，
// 用来维护这套目录以及每个目录里放哪些终端。
//
// # 表
//
//	terminalfolder     (id, parentid, name, terminalid, seqnumber)  宿主终端的目录树
//	terminaloffolder   (folderid, terminalid)                       目录里的终端
//
// # 根目录
//
// ok112 在这台终端第一次创建目录时，会先自动插一条 parentid = 0、名字写死
// 「目录管理」的根目录（do.php 的 dirareaadd_msg），之后所有目录都挂在它下面。
// 删除目录那条 SQL 是 `DELETE FROM terminalfolder WHERE id = ? AND parentid > 0`
// —— 根目录删不掉。这两条都照搬。
//
// # 一个没实现的动作
//
// dirarea_terminal.html 底部有个「复制主目录到主终端」，指向
// do.php?act=dirareacopy_msg —— 但 do.php 里根本没有 dirareacopy_msg
// 这个 case（全文件 0 处匹配），点下去只会跳到一个空动作。
// 死链不搬。

// TerminalFolder 是目录树上的一个节点。
type TerminalFolder struct {
	ID         int64  `json:"id"`
	ParentID   int64  `json:"parentid"`
	Name       string `json:"name"`
	TerminalID int64  `json:"terminalId"`
	SeqNumber  int    `json:"seqnumber"`
	// Count 是这个目录**自身**挂了几台终端，不含子目录。
	Count    int              `json:"count"`
	Children []TerminalFolder `json:"children"`
}

// FolderTerminal 是目录里的一台终端。
//
// 列按 ok112 dirarea_terminal.html：终端名称 / 终端类型 / 任务状态 /
// 网络状态 / IP地址 / 音量。
type FolderTerminal struct {
	ID        int64  `json:"id"`
	Name      string `json:"terminalname"`
	TypeID    int    `json:"typeId"`
	TypeName  string `json:"typeName"`
	NetState  int    `json:"netstate"`
	TaskState int    `json:"taskstate"`
	IP        string `json:"ip"`
	Volume    int    `json:"volume"`
}

var (
	// ErrFolderNameRequired 目录名为空。
	ErrFolderNameRequired = errors.New("请填写目录名称")
	// ErrFolderNameDuplicate 同一台终端下已有同名目录（ok112 dirareamodify_msg 的判重）。
	ErrFolderNameDuplicate = errors.New("同名目录已存在")
	// ErrFolderIsRoot 根目录不能删（ok112 的 `AND parentid > 0`）。
	ErrFolderIsRoot = errors.New("根目录不能删除")
)

// rootFolderName 是根目录的名字。ok112 在 dirareaadd_msg 里写死成这个中文串。
const rootFolderName = "目录管理"

// ListTerminalFolders 读一台终端的目录树。
func (s *Service) ListTerminalFolders(ctx context.Context, terminalID int64) ([]TerminalFolder, error) {
	if err := s.assertTerminalExists(ctx, terminalID); err != nil {
		return nil, err
	}

	rs, err := s.db.QueryContext(ctx, `
		SELECT f.id, f.parentid, COALESCE(f.name,''), f.terminalid, COALESCE(f.seqnumber,0),
		       (SELECT COUNT(*) FROM terminaloffolder o WHERE o.folderid = f.id)
		FROM terminalfolder f WHERE f.terminalid = ?
		ORDER BY f.seqnumber, f.id`, terminalID)
	if err != nil {
		return nil, fmt.Errorf("查询目录: %w", err)
	}
	defer rs.Close()

	flat := []TerminalFolder{}
	for rs.Next() {
		var f TerminalFolder
		f.Children = []TerminalFolder{}
		if err := rs.Scan(&f.ID, &f.ParentID, &f.Name, &f.TerminalID, &f.SeqNumber, &f.Count); err != nil {
			return nil, fmt.Errorf("读取目录: %w", err)
		}
		flat = append(flat, f)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}
	return nestFolders(flat), nil
}

// nestFolders 把平表拼成树。
//
// ⚠ 全程用指针挂，最后一次性转成值。
//
//	先前写成边遍历边把 *child 拷进父节点的 Children，孙子节点是在更后面
//	一轮才挂到儿子身上的，而儿子早已被按值拷进爷爷 —— 拷贝是那一刻的快照，
//	孙子就永远出不来。三层目录只显示两层，就是这么丢的。
//
// ⚠ parentid 指向一条不存在的目录时（旧库里删了父目录、子目录还留着），
//
//	这条记录挂到顶层，而不是被丢掉 —— 丢掉的话界面上就再也删不了它。
func nestFolders(flat []TerminalFolder) []TerminalFolder {
	type node struct {
		self *TerminalFolder
		kids []*node
	}
	byID := make(map[int64]*node, len(flat))
	order := make([]*node, 0, len(flat))
	for i := range flat {
		n := &node{self: &flat[i]}
		byID[flat[i].ID] = n
		order = append(order, n)
	}

	roots := []*node{}
	for _, n := range order {
		p, ok := byID[n.self.ParentID]
		if ok && n.self.ParentID != 0 && p != n {
			p.kids = append(p.kids, n)
			continue
		}
		roots = append(roots, n)
	}

	var build func(n *node) TerminalFolder
	build = func(n *node) TerminalFolder {
		f := *n.self
		f.Children = make([]TerminalFolder, 0, len(n.kids))
		for _, k := range n.kids {
			f.Children = append(f.Children, build(k))
		}
		return f
	}
	out := make([]TerminalFolder, 0, len(roots))
	for _, r := range roots {
		out = append(out, build(r))
	}
	return out
}

// FolderTerminals 列出一个目录里的终端。
//
// keyword 按终端名模糊匹配（ok112 的搜索下拉里还有一项「按终端类型」，
// 那一项拼的是 `terminal.typeid LIKE '%关键字%'` —— 拿类型**编号**去
// 模糊匹配用户输入的类型名，永远搜不到东西，不搬）。
//
// ⚠ 不分页。ok112 这一页是分页的，但一个目录里通常只有几台到几十台终端，
//
//	对话框里的表格直接滚动更顺手，也少一套翻页状态。
func (s *Service) FolderTerminals(ctx context.Context, folderID int64, keyword string) ([]FolderTerminal, error) {
	q := `
		SELECT t.id, COALESCE(t.terminalname,''), COALESCE(t.typeid,0), COALESCE(tt.name,''),
		       COALESCE(t.netstate,0), COALESCE(t.taskstate,0), COALESCE(t.ip,''), COALESCE(t.volume,0)
		FROM terminaloffolder o
		JOIN terminal t ON t.id = o.terminalid
		LEFT JOIN terminaltype tt ON tt.id = t.typeid
		WHERE o.folderid = ?`
	args := []interface{}{folderID}
	if kw := strings.TrimSpace(keyword); kw != "" {
		q += ` AND t.terminalname LIKE ?`
		args = append(args, "%"+kw+"%")
	}
	q += ` ORDER BY t.terminalname`

	rs, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询目录终端: %w", err)
	}
	defer rs.Close()

	out := []FolderTerminal{}
	for rs.Next() {
		var t FolderTerminal
		if err := rs.Scan(&t.ID, &t.Name, &t.TypeID, &t.TypeName,
			&t.NetState, &t.TaskState, &t.IP, &t.Volume); err != nil {
			return nil, fmt.Errorf("读取目录终端: %w", err)
		}
		out = append(out, t)
	}
	return out, rs.Err()
}

// CreateTerminalFolder 建一个目录。
//
// parentID 传 0 表示「挂在根目录下」。这台终端还没有任何目录时，先按
// ok112 的做法自动补一条名为「目录管理」的根目录，再把新目录挂上去。
func (s *Service) CreateTerminalFolder(ctx context.Context, u *auth.User,
	terminalID, parentID int64, name string) (int64, error) {

	name = strings.TrimSpace(name)
	if name == "" {
		return 0, ErrFolderNameRequired
	}
	if err := s.assertFolderOwner(ctx, u, terminalID); err != nil {
		return 0, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	root, err := ensureRootFolder(ctx, tx, terminalID)
	if err != nil {
		return 0, err
	}
	if parentID == 0 {
		parentID = root
	} else {
		// 父目录必须是这台终端自己的，否则改个 id 就能把目录挂到别人树上
		var owner int64
		if err := tx.QueryRowContext(ctx,
			`SELECT terminalid FROM terminalfolder WHERE id = ?`, parentID).Scan(&owner); err != nil {
			if errors.Is(err, sql.ErrNoRows) {
				return 0, ErrNotFound
			}
			return 0, fmt.Errorf("查询父目录: %w", err)
		}
		if owner != terminalID {
			return 0, ErrNotFound
		}
	}

	if err := assertFolderNameFree(ctx, tx, terminalID, 0, name); err != nil {
		return 0, err
	}

	var seq int
	if err := tx.QueryRowContext(ctx,
		`SELECT COALESCE(MAX(seqnumber),0) + 1 FROM terminalfolder WHERE terminalid = ?`,
		terminalID).Scan(&seq); err != nil {
		return 0, fmt.Errorf("计算排序号: %w", err)
	}

	res, err := tx.ExecContext(ctx,
		`INSERT INTO terminalfolder (parentid, name, terminalid, seqnumber) VALUES (?,?,?,?)`,
		parentID, name, terminalID, seq)
	if err != nil {
		return 0, fmt.Errorf("创建目录: %w", err)
	}
	id, err := res.LastInsertId()
	if err != nil {
		return 0, fmt.Errorf("读取目录 ID: %w", err)
	}
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return id, nil
}

// RenameTerminalFolder 改目录名。
func (s *Service) RenameTerminalFolder(ctx context.Context, u *auth.User,
	terminalID, folderID int64, name string) error {

	name = strings.TrimSpace(name)
	if name == "" {
		return ErrFolderNameRequired
	}
	if err := s.assertFolderOwner(ctx, u, terminalID); err != nil {
		return err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	var owner int64
	if err := tx.QueryRowContext(ctx,
		`SELECT terminalid FROM terminalfolder WHERE id = ?`, folderID).Scan(&owner); err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return ErrNotFound
		}
		return fmt.Errorf("查询目录: %w", err)
	}
	if owner != terminalID {
		return ErrNotFound
	}
	if err := assertFolderNameFree(ctx, tx, terminalID, folderID, name); err != nil {
		return err
	}
	if _, err := tx.ExecContext(ctx,
		`UPDATE terminalfolder SET name = ? WHERE id = ?`, name, folderID); err != nil {
		return fmt.Errorf("修改目录: %w", err)
	}
	return tx.Commit()
}

// DeleteTerminalFolder 删一个目录，连它的子目录和目录里的终端关联一起清掉。
//
// ⚠ 根目录（parentid = 0）删不掉 —— ok112 的 dirareadel_msg 就带着
//
//	`AND parentid > 0`。根目录没了，这台终端的整棵目录树就没有落脚点。
//
// ⚠ ok112 只删被点的那一个目录，它下面的子目录会变成孤儿（parentid 指向
//
//	一条已经不存在的记录），从树上消失但行还在表里。这里连子目录一起删。
func (s *Service) DeleteTerminalFolder(ctx context.Context, u *auth.User,
	terminalID, folderID int64) (int, error) {

	if err := s.assertFolderOwner(ctx, u, terminalID); err != nil {
		return 0, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	var owner, parent int64
	if err := tx.QueryRowContext(ctx,
		`SELECT terminalid, parentid FROM terminalfolder WHERE id = ?`,
		folderID).Scan(&owner, &parent); err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return 0, ErrNotFound
		}
		return 0, fmt.Errorf("查询目录: %w", err)
	}
	if owner != terminalID {
		return 0, ErrNotFound
	}
	if parent == 0 {
		return 0, ErrFolderIsRoot
	}

	// 收集这一支（自己 + 所有后代）。层数很浅，逐层展开即可。
	ids := []int64{folderID}
	frontier := []int64{folderID}
	for len(frontier) > 0 {
		ph, args := placeholders(frontier)
		args = append(args, terminalID)
		rs, err := tx.QueryContext(ctx,
			`SELECT id FROM terminalfolder WHERE parentid IN (`+ph+`) AND terminalid = ?`, args...)
		if err != nil {
			return 0, fmt.Errorf("查询子目录: %w", err)
		}
		var next []int64
		for rs.Next() {
			var id int64
			if err := rs.Scan(&id); err != nil {
				rs.Close()
				return 0, err
			}
			next = append(next, id)
		}
		rs.Close()
		if err := rs.Err(); err != nil {
			return 0, err
		}
		ids = append(ids, next...)
		frontier = next
	}

	ph, args := placeholders(ids)
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminaloffolder WHERE folderid IN (`+ph+`)`, args...); err != nil {
		return 0, fmt.Errorf("清理目录终端: %w", err)
	}
	args = append(args, terminalID)
	res, err := tx.ExecContext(ctx,
		`DELETE FROM terminalfolder WHERE id IN (`+ph+`) AND terminalid = ?`, args...)
	if err != nil {
		return 0, fmt.Errorf("删除目录: %w", err)
	}
	n, _ := res.RowsAffected()
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return int(n), nil
}

// SetFolderTerminals 往目录里加终端 / 从目录里移除终端。
//
// add = true 时加，false 时移除。ok112 是两个独立动作
// （dir_area_add.php 的提交 与 deldirarea_msg），这里合成一个方向参数。
func (s *Service) SetFolderTerminals(ctx context.Context, u *auth.User,
	terminalID, folderID int64, ids []int64, add bool) (int, error) {

	if len(ids) == 0 {
		return 0, nil
	}
	if err := s.assertFolderOwner(ctx, u, terminalID); err != nil {
		return 0, err
	}

	var owner int64
	if err := s.db.QueryRowContext(ctx,
		`SELECT terminalid FROM terminalfolder WHERE id = ?`, folderID).Scan(&owner); err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return 0, ErrNotFound
		}
		return 0, fmt.Errorf("查询目录: %w", err)
	}
	if owner != terminalID {
		return 0, ErrNotFound
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	n := 0
	if add {
		for _, id := range ids {
			// 已经在这个目录里就跳过，别攒重复行
			res, err := tx.ExecContext(ctx, `
				INSERT INTO terminaloffolder (folderid, terminalid)
				SELECT ?, t.id FROM terminal t
				WHERE t.id = ? AND NOT EXISTS (
					SELECT 1 FROM terminaloffolder o WHERE o.folderid = ? AND o.terminalid = t.id)`,
				folderID, id, folderID)
			if err != nil {
				return 0, fmt.Errorf("加入目录: %w", err)
			}
			c, _ := res.RowsAffected()
			n += int(c)
		}
	} else {
		ph, args := placeholders(ids)
		args = append([]interface{}{folderID}, args...)
		res, err := tx.ExecContext(ctx,
			`DELETE FROM terminaloffolder WHERE folderid = ? AND terminalid IN (`+ph+`)`, args...)
		if err != nil {
			return 0, fmt.Errorf("移出目录: %w", err)
		}
		c, _ := res.RowsAffected()
		n = int(c)
	}
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return n, nil
}

// FolderCandidates 列出还没在这个目录里的、可以加进来的终端。
//
// 型号范围与寻呼分区的成员一致（get_terminal_type(3)），并且不含宿主自己。
func (s *Service) FolderCandidates(ctx context.Context, u *auth.User,
	terminalID, folderID int64) ([]CallGroupCandidate, error) {

	all, err := s.CallGroupCandidates(ctx, u, terminalID)
	if err != nil {
		return nil, err
	}
	rs, err := s.db.QueryContext(ctx,
		`SELECT terminalid FROM terminaloffolder WHERE folderid = ?`, folderID)
	if err != nil {
		return nil, fmt.Errorf("查询目录终端: %w", err)
	}
	defer rs.Close()
	inFolder := map[int64]bool{}
	for rs.Next() {
		var id int64
		if err := rs.Scan(&id); err != nil {
			return nil, err
		}
		inFolder[id] = true
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}

	out := make([]CallGroupCandidate, 0, len(all))
	for _, c := range all {
		if !inFolder[c.ID] {
			out = append(out, c)
		}
	}
	return out, nil
}

// ensureRootFolder 保证这台终端有一条 parentid = 0 的根目录，返回它的 id。
func ensureRootFolder(ctx context.Context, tx *sql.Tx, terminalID int64) (int64, error) {
	var id int64
	err := tx.QueryRowContext(ctx,
		`SELECT id FROM terminalfolder WHERE terminalid = ? AND parentid = 0 ORDER BY id LIMIT 1`,
		terminalID).Scan(&id)
	switch {
	case err == nil:
		return id, nil
	case !errors.Is(err, sql.ErrNoRows):
		return 0, fmt.Errorf("查询根目录: %w", err)
	}
	res, err := tx.ExecContext(ctx,
		`INSERT INTO terminalfolder (parentid, name, terminalid, seqnumber) VALUES (0,?,?,0)`,
		rootFolderName, terminalID)
	if err != nil {
		return 0, fmt.Errorf("创建根目录: %w", err)
	}
	if id, err = res.LastInsertId(); err != nil {
		return 0, fmt.Errorf("读取根目录 ID: %w", err)
	}
	return id, nil
}

// assertFolderNameFree 同一台终端下不允许重名（ok112 dirareamodify_msg 的判重）。
func assertFolderNameFree(ctx context.Context, tx *sql.Tx, terminalID, exceptID int64, name string) error {
	var one int
	err := tx.QueryRowContext(ctx,
		`SELECT 1 FROM terminalfolder WHERE terminalid = ? AND name = ? AND id <> ? LIMIT 1`,
		terminalID, name, exceptID).Scan(&one)
	if errors.Is(err, sql.ErrNoRows) {
		return nil
	}
	if err != nil {
		return fmt.Errorf("检查目录重名: %w", err)
	}
	return ErrFolderNameDuplicate
}

// assertFolderOwner 目录属于宿主终端，改它要有这台终端的权限，
// 并且这台终端型号得真的支持寻呼授权（目录只服务于这件事）。
func (s *Service) assertFolderOwner(ctx context.Context, u *auth.User, terminalID int64) error {
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
	return s.assertCanAuthPaging(ctx, terminalID)
}
