package task

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"
	"unicode/utf8"

	"htweb/internal/auth"
)

// 任务分组（filetaskfree，F-36）。
//
// 这棵树和媒体文件夹树（filefolder）没有任何关系（BR-161），
// 两者字段名相近但语义完全不同，混用会把任务挂到媒体目录下去。

// FolderNode 是分组树的一个节点。
type FolderNode struct {
	ID        int64        `json:"id"`
	Name      string       `json:"name"`
	ParentID  int64        `json:"parentId"`
	UserID    int64        `json:"userId"`
	UserName  string       `json:"userName"`
	TaskCount int          `json:"taskCount"`
	CanDelete bool         `json:"canDelete"`
	Children  []FolderNode `json:"children"`
}

// folderVisibleCond 是分组可见范围的唯一权威定义。
// 管理员看全部；普通用户只看自己的（旧版同此语义）。
func folderVisibleCond(u *auth.User) (string, []interface{}) {
	if u.IsAdmin {
		return "", nil
	}
	return "userid = ?", []interface{}{u.ID}
}

// AssertFolderVisible 校验分组对该用户可见。
// 不区分「不存在」与「无权访问」，避免把接口变成分组存在性探针。
func (s *Service) AssertFolderVisible(ctx context.Context, u *auth.User, folderID int64) error {
	q := `SELECT COUNT(*) FROM filetaskfree WHERE id = ?`
	args := []interface{}{folderID}
	if c, a := folderVisibleCond(u); c != "" {
		q += " AND " + c
		args = append(args, a...)
	}
	var n int
	if err := s.db.QueryRowContext(ctx, q, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验任务分组可见性: %w", err)
	}
	if n == 0 {
		return ErrFolderDenied
	}
	return nil
}

// FolderTree 返回该用户可见的分组树。
func (s *Service) FolderTree(ctx context.Context, u *auth.User) ([]FolderNode, error) {
	q := `SELECT f.id, COALESCE(f.name,''), COALESCE(f.parentid,0),
	             COALESCE(f.userid,0), COALESCE(b.username,'')
	      FROM filetaskfree f
	      LEFT JOIN book_admin b ON b.id = f.userid`
	var args []interface{}
	if c, a := folderVisibleCond(u); c != "" {
		q += " WHERE f." + c
		args = a
	}
	q += " ORDER BY f.id"

	rs, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询任务分组: %w", err)
	}
	defer rs.Close()

	var flat []FolderNode
	for rs.Next() {
		var n FolderNode
		if err := rs.Scan(&n.ID, &n.Name, &n.ParentID, &n.UserID, &n.UserName); err != nil {
			return nil, err
		}
		n.CanDelete = n.ID != DefaultFolder
		flat = append(flat, n)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}
	if len(flat) == 0 {
		return []FolderNode{}, nil
	}

	counts, err := s.folderTaskCounts(ctx, u)
	if err != nil {
		return nil, err
	}
	for i := range flat {
		flat[i].TaskCount = counts[flat[i].ID]
	}
	return buildTree(flat), nil
}

// buildTree 把平表拼成树。
//
// 「父节点不在可见集合里」的节点会被提到根上，而不是丢弃 ——
// 普通用户看得到自己的子分组、看不到别人的父分组时就是这种情况，
// 丢弃会让他的分组凭空消失。
func buildTree(flat []FolderNode) []FolderNode {
	byID := make(map[int64]*FolderNode, len(flat))
	for i := range flat {
		flat[i].Children = []FolderNode{}
		byID[flat[i].ID] = &flat[i]
	}
	var roots []FolderNode
	for i := range flat {
		n := &flat[i]
		if p, ok := byID[n.ParentID]; ok && n.ParentID != 0 && p.ID != n.ID {
			continue
		}
		roots = append(roots, *n)
	}
	// 两遍：先定根，再自顶向下挂子节点，避免递归时拷贝到未填充的子树
	var attach func(n *FolderNode)
	attach = func(n *FolderNode) {
		for i := range flat {
			if flat[i].ParentID == n.ID && flat[i].ID != n.ID {
				child := flat[i]
				child.Children = []FolderNode{}
				attach(&child)
				n.Children = append(n.Children, child)
			}
		}
	}
	for i := range roots {
		attach(&roots[i])
	}
	if roots == nil {
		roots = []FolderNode{}
	}
	return roots
}

func (s *Service) folderTaskCounts(ctx context.Context, u *auth.User) (map[int64]int, error) {
	q := `SELECT t.parentid, COUNT(*) FROM task t WHERE ` + mustTypeIn()
	args := typeArgs()
	if c, a := visibleCond(u); c != "" {
		q += " AND " + c
		args = append(args, a...)
	}
	q += " GROUP BY t.parentid"

	rs, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("统计分组任务数: %w", err)
	}
	defer rs.Close()

	out := map[int64]int{}
	for rs.Next() {
		var id int64
		var n int
		if err := rs.Scan(&id, &n); err != nil {
			return nil, err
		}
		out[id] = n
	}
	return out, rs.Err()
}

func mustTypeIn() string {
	c, _ := typeInCond(fileTypes)
	return c
}

func typeArgs() []interface{} {
	_, a := typeInCond(fileTypes)
	return a
}

// ---------- 分组增删改 ----------

// checkFolderName 校验分组名。
//
// filetaskfree.name 只有 varchar(16)，而且是 utf8 列 —— 一个汉字 3 字节，
// 超过 5 个字就会被 MySQL 静默截断（D-125）。这里按**字节数**卡，不是字符数。
func checkFolderName(name string) (string, error) {
	name = strings.TrimSpace(name)
	if name == "" {
		return "", fmt.Errorf("分组名称不能为空")
	}
	if len(name) > 16 {
		return "", fmt.Errorf("分组名称过长：按 UTF-8 计 %d 字节，上限 16 字节（约 5 个汉字）", len(name))
	}
	if utf8.RuneCountInString(name) == 0 {
		return "", fmt.Errorf("分组名称不能为空")
	}
	return name, nil
}

// CreateFolder 新建任务分组。
func (s *Service) CreateFolder(ctx context.Context, u *auth.User, name string, parentID int64) (int64, error) {
	name, err := checkFolderName(name)
	if err != nil {
		return 0, err
	}
	if parentID > 0 {
		if err := s.AssertFolderVisible(ctx, u, parentID); err != nil {
			return 0, err
		}
	}
	// 同父不重名
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM filetaskfree WHERE parentid = ? AND name = ?`,
		parentID, name).Scan(&n); err != nil {
		return 0, fmt.Errorf("分组重名校验: %w", err)
	}
	if n > 0 {
		return 0, fmt.Errorf("同一层级下已存在同名分组")
	}

	res, err := s.db.ExecContext(ctx,
		`INSERT INTO filetaskfree (name, parentid, userid) VALUES (?,?,?)`,
		name, parentID, u.ID)
	if err != nil {
		return 0, fmt.Errorf("新建任务分组: %w", err)
	}
	return res.LastInsertId()
}

// RenameFolder 改分组名。
func (s *Service) RenameFolder(ctx context.Context, u *auth.User, id int64, name string) error {
	name, err := checkFolderName(name)
	if err != nil {
		return err
	}
	if err := s.AssertFolderVisible(ctx, u, id); err != nil {
		return err
	}
	var parentID int64
	if err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(parentid,0) FROM filetaskfree WHERE id = ?`, id).Scan(&parentID); err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return ErrFolderDenied
		}
		return fmt.Errorf("查询任务分组: %w", err)
	}
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM filetaskfree WHERE parentid = ? AND name = ? AND id <> ?`,
		parentID, name, id).Scan(&n); err != nil {
		return fmt.Errorf("分组重名校验: %w", err)
	}
	if n > 0 {
		return fmt.Errorf("同一层级下已存在同名分组")
	}
	if _, err := s.db.ExecContext(ctx,
		`UPDATE filetaskfree SET name = ? WHERE id = ?`, name, id); err != nil {
		return fmt.Errorf("修改任务分组: %w", err)
	}
	return nil
}

// FolderDeleteResult 是删除分组的结果。
type FolderDeleteResult struct {
	DeletedFolders []int64 `json:"deletedFolders"`
	DeletedTasks   []int64 `json:"deletedTasks"`
	DeletedSubs    []int64 `json:"deletedSubTasks"`
}

// DeleteFolder 递归删除分组及其整棵子树下的全部任务。
//
// 旧版的 WHERE id = ? OR parentid = ? 只覆盖一层（D-123）——
// 孙级分组连同它下面的任务全都变成孤儿，界面上再也看不到、也删不掉。
// 这里先在内存里把整棵子树收集完整再统一删。
func (s *Service) DeleteFolder(ctx context.Context, u *auth.User, id int64) (*FolderDeleteResult, error) {
	if id == DefaultFolder {
		return nil, fmt.Errorf("默认分组不允许删除")
	}
	if err := s.AssertFolderVisible(ctx, u, id); err != nil {
		return nil, err
	}

	subtree, err := s.collectSubtree(ctx, u, id)
	if err != nil {
		return nil, err
	}

	// 子树下的任务；这里不加 tasktype 过滤 —— 分组被删掉之后，
	// 任何 parentid 指向它的任务都会变成孤儿，不分类型一并清掉。
	ph, args := placeholders(subtree)
	taskIDs, err := s.queryIDs(ctx, `SELECT taskid FROM task WHERE parentid IN (`+ph+`)`, args)
	if err != nil {
		return nil, fmt.Errorf("查询分组内任务: %w", err)
	}

	out := &FolderDeleteResult{DeletedFolders: subtree, DeletedTasks: taskIDs, DeletedSubs: []int64{}}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	if len(taskIDs) > 0 {
		subs, err := s.purgeTasks(ctx, tx, taskIDs)
		if err != nil {
			return nil, err
		}
		out.DeletedSubs = subs
		// 功放子任务的 parentid 同样指向这个分组，所以它既在 taskIDs 里、
		// 又会出现在 subs 里。两个字段直接回给前端会让「任务 N 条、子任务 M 条」
		// 把同一行数两遍，这里把重复的从主任务列表里剔掉。
		out.DeletedTasks = excludeIDs(taskIDs, subs)
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM filetaskfree WHERE id IN (`+ph+`)`, args...); err != nil {
		return nil, fmt.Errorf("删除任务分组: %w", err)
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return out, nil
}

// collectSubtree 广度优先收集整棵子树的分组 id（含自身）。
// 带访问集合，防止旧数据里 parentid 成环时死循环。
func (s *Service) collectSubtree(ctx context.Context, u *auth.User, root int64) ([]int64, error) {
	seen := map[int64]bool{root: true}
	out := []int64{root}
	frontier := []int64{root}

	for len(frontier) > 0 {
		ph, args := placeholders(frontier)
		q := `SELECT id FROM filetaskfree WHERE parentid IN (` + ph + `)`
		if c, a := folderVisibleCond(u); c != "" {
			q += " AND " + c
			args = append(args, a...)
		}
		next, err := s.queryIDs(ctx, q, args)
		if err != nil {
			return nil, fmt.Errorf("收集子分组: %w", err)
		}
		frontier = frontier[:0]
		for _, id := range next {
			if seen[id] {
				continue
			}
			seen[id] = true
			out = append(out, id)
			frontier = append(frontier, id)
		}
	}
	return out, nil
}

// excludeIDs 返回 ids 中不在 drop 里的元素，保持原有顺序。
func excludeIDs(ids, drop []int64) []int64 {
	if len(drop) == 0 {
		return ids
	}
	skip := make(map[int64]bool, len(drop))
	for _, d := range drop {
		skip[d] = true
	}
	out := make([]int64, 0, len(ids))
	for _, id := range ids {
		if !skip[id] {
			out = append(out, id)
		}
	}
	return out
}

func (s *Service) queryIDs(ctx context.Context, q string, args []interface{}) ([]int64, error) {
	rs, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, err
	}
	defer rs.Close()
	out := []int64{}
	for rs.Next() {
		var id int64
		if err := rs.Scan(&id); err != nil {
			return nil, err
		}
		out = append(out, id)
	}
	return out, rs.Err()
}
