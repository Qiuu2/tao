package folder

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"

	"htweb/internal/auth"
)

var (
	ErrNameUsed     = errors.New("同一目录下已存在同名文件夹")
	ErrDepthLimit   = errors.New("文件夹层级已达上限")
	ErrSystemFolder = errors.New("系统预置文件夹不可修改或删除")
	ErrNotFound     = errors.New("文件夹不存在")
	ErrNoPermission = errors.New("无权操作该文件夹")
)

// ---------- F-02 新建文件夹 ----------

type CreateInput struct {
	Name     string
	ParentID int64
	// Shared 对应 filefolder.priority：true=1 共享，false=0 仅创建者可见。
	//
	// 必须由调用方显式提供。旧版表单把这个复选框注释掉了，导致
	// 新建恒为共享、改名还会把私有目录静默改成共享（缺陷 I-03）。
	Shared bool
}

// Create 新建文件夹。
func (s *Service) Create(ctx context.Context, u *auth.User, in CreateInput) (int64, error) {
	name := strings.TrimSpace(in.Name)
	if err := validateName(name); err != nil {
		return 0, err
	}

	// 层级校验：父级深度已达上限则不允许再建（BR-10，3 层上限已定稿）
	depth, err := s.Depth(ctx, in.ParentID)
	if err != nil {
		return 0, err
	}
	if in.ParentID != 0 && depth == 0 {
		return 0, ErrNotFound
	}
	if depth >= MaxDepth {
		return 0, ErrDepthLimit
	}

	// 顶层目录只允许管理员创建：顶层是 6 个系统媒体库的层级，
	// 普通用户在这里建目录会打乱既有结构
	if in.ParentID == 0 && !u.IsAdmin {
		return 0, ErrNoPermission
	}

	// 应用级命名锁串行化「查重 + 写入」。
	// media/filefolder 都没有唯一索引，且不允许新建索引（R1 红线），
	// 只能用 GET_LOCK 在应用层保证原子性。
	unlock, err := s.lock(ctx, fmt.Sprintf("htweb_folder_create_%d", in.ParentID))
	if err != nil {
		return 0, err
	}
	defer unlock()

	var exists int
	err = s.db.QueryRowContext(ctx,
		`SELECT 1 FROM filefolder WHERE name = ? AND parentid = ? LIMIT 1`,
		name, in.ParentID).Scan(&exists)
	if err == nil {
		return 0, ErrNameUsed
	}
	if !errors.Is(err, sql.ErrNoRows) {
		return 0, fmt.Errorf("重名校验: %w", err)
	}

	priority := 0
	if in.Shared {
		priority = 1
	}

	// createtime 不显式写，交给列默认值 current_timestamp()（契约 C-11）
	// parentid 绑定为整数，不再像旧版那样拼出带尾随空格的 '$folder_id '（契约 C-12）
	res, err := s.db.ExecContext(ctx,
		`INSERT INTO filefolder (name, userid, priority, parentid) VALUES (?, ?, ?, ?)`,
		name, u.ID, priority, in.ParentID)
	if err != nil {
		return 0, fmt.Errorf("新建文件夹: %w", err)
	}
	id, err := res.LastInsertId()
	if err != nil {
		return 0, fmt.Errorf("获取新文件夹 ID: %w", err)
	}
	return id, nil
}

// ---------- F-03 修改文件夹 ----------

type UpdateInput struct {
	Name string
	// Shared 必须显式提供，服务端不设默认值 —— 见 CreateInput.Shared 的说明
	Shared bool
}

func (s *Service) Update(ctx context.Context, u *auth.User, id int64, in UpdateInput) error {
	if id <= SystemMaxID {
		return ErrSystemFolder
	}
	name := strings.TrimSpace(in.Name)
	if err := validateName(name); err != nil {
		return err
	}

	var parentID, ownerID int64
	err := s.db.QueryRowContext(ctx,
		`SELECT parentid, userid FROM filefolder WHERE id = ? LIMIT 1`, id).Scan(&parentID, &ownerID)
	if errors.Is(err, sql.ErrNoRows) {
		return ErrNotFound
	}
	if err != nil {
		return fmt.Errorf("查询文件夹: %w", err)
	}
	if !u.IsAdmin && ownerID != u.ID {
		return ErrNoPermission
	}

	unlock, err := s.lock(ctx, fmt.Sprintf("htweb_folder_create_%d", parentID))
	if err != nil {
		return err
	}
	defer unlock()

	// 重名校验范围是「同一父目录下」，不带创建者条件。
	// 旧版多带了 AND userid = 当前用户，导致不同用户能在同一目录建同名文件夹，
	// 与新建时的校验口径不一致（缺陷 D-... 见手册 F-03 第 2 条）。
	var exists int
	err = s.db.QueryRowContext(ctx,
		`SELECT 1 FROM filefolder WHERE id <> ? AND name = ? AND parentid = ? LIMIT 1`,
		id, name, parentID).Scan(&exists)
	if err == nil {
		return ErrNameUsed
	}
	if !errors.Is(err, sql.ErrNoRows) {
		return fmt.Errorf("重名校验: %w", err)
	}

	priority := 0
	if in.Shared {
		priority = 1
	}
	// 只更新 name 与 priority。userid（创建者）与 parentid（父级）均不可改：
	// parentid 是复合主键的组成部分，改它风险过高（BR-18）
	_, err = s.db.ExecContext(ctx,
		`UPDATE filefolder SET name = ?, priority = ? WHERE id = ?`, name, priority, id)
	if err != nil {
		return fmt.Errorf("更新文件夹: %w", err)
	}
	return nil
}

// ---------- F-04 删除文件夹（递归整棵子树）----------

// BlockedFolder 描述一个不可删除的文件夹及原因。
type BlockedFolder struct {
	ID     int64  `json:"id"`
	Name   string `json:"name"`
	Reason string `json:"reason"`
	Detail string `json:"detail,omitempty"`
}

// DeletePreview 是删除前的影响面预览。
//
// 旧版没有任何预览，点一下就连锁删掉整棵子树（缺陷 D-50 同类问题）。
type DeletePreview struct {
	Deletable []DeletableFolder `json:"deletable"`
	Blocked   []BlockedFolder   `json:"blocked"`
}

type DeletableFolder struct {
	ID                int64  `json:"id"`
	Name              string `json:"name"`
	DescendantFolders int    `json:"descendantFolders"`
	MediaCount        int    `json:"mediaCount"`
}

// mediaRef 是一条媒体引用记录，用于说明为什么不能删。
type mediaRef struct {
	MediaID   int64
	MediaName string
	RefType   string
	RefName   string
}

// Subtree 收集以 rootIDs 为根的整棵子树的全部文件夹 ID（含根本身）。
//
// 这是对旧版最重要的修复之一：旧代码只删了一层子级
// （DELETE FROM filefolder WHERE parentid IN (...)），
// 三层树里删顶层会让「孙级」目录及其媒体永久残留成孤儿（缺陷 D-08）。
//
// 用显式栈迭代 + visited 防环，旧数据里 parentid 成环也不会死循环。
func (s *Service) Subtree(ctx context.Context, rootIDs []int64) ([]int64, error) {
	rows, err := s.db.QueryContext(ctx, `SELECT id, parentid FROM filefolder`)
	if err != nil {
		return nil, fmt.Errorf("查询文件夹全表: %w", err)
	}
	defer rows.Close()

	children := make(map[int64][]int64)
	exists := make(map[int64]bool)
	for rows.Next() {
		var id, parent int64
		if err := rows.Scan(&id, &parent); err != nil {
			return nil, err
		}
		exists[id] = true
		children[parent] = append(children[parent], id)
	}
	if err := rows.Err(); err != nil {
		return nil, err
	}

	visited := make(map[int64]bool)
	var out []int64
	stack := append([]int64{}, rootIDs...)
	for len(stack) > 0 {
		id := stack[len(stack)-1]
		stack = stack[:len(stack)-1]
		if visited[id] || !exists[id] {
			continue
		}
		visited[id] = true
		out = append(out, id)
		stack = append(stack, children[id]...)
	}
	return out, nil
}

// Preview 计算删除影响面，并找出被引用而不可删的文件夹。
func (s *Service) Preview(ctx context.Context, u *auth.User, ids []int64) (*DeletePreview, error) {
	res := &DeletePreview{Deletable: []DeletableFolder{}, Blocked: []BlockedFolder{}}

	for _, id := range ids {
		name, owner, err := s.nameAndOwner(ctx, id)
		if errors.Is(err, sql.ErrNoRows) {
			res.Blocked = append(res.Blocked, BlockedFolder{ID: id, Reason: "NOT_FOUND"})
			continue
		}
		if err != nil {
			return nil, err
		}

		// 系统预置目录 1~9 一律禁止删除（BR-21）
		if id <= SystemMaxID {
			res.Blocked = append(res.Blocked, BlockedFolder{
				ID: id, Name: name, Reason: "SYSTEM_RESERVED",
				Detail: "系统预置媒体库不可删除",
			})
			continue
		}
		if !u.IsAdmin && owner != u.ID {
			res.Blocked = append(res.Blocked, BlockedFolder{
				ID: id, Name: name, Reason: "NO_PERMISSION",
				Detail: "只能删除自己创建的文件夹",
			})
			continue
		}

		sub, err := s.Subtree(ctx, []int64{id})
		if err != nil {
			return nil, err
		}
		refs, err := s.mediaRefsIn(ctx, sub)
		if err != nil {
			return nil, err
		}
		if len(refs) > 0 {
			r := refs[0]
			res.Blocked = append(res.Blocked, BlockedFolder{
				ID: id, Name: name, Reason: "MEDIA_IN_USE",
				Detail: fmt.Sprintf("媒体「%s」正被%s「%s」使用", r.MediaName, refTypeText(r.RefType), r.RefName),
			})
			continue
		}

		cnt, err := s.countMediaIn(ctx, sub)
		if err != nil {
			return nil, err
		}
		res.Deletable = append(res.Deletable, DeletableFolder{
			ID: id, Name: name,
			DescendantFolders: len(sub) - 1,
			MediaCount:        cnt,
		})
	}
	return res, nil
}

// DeleteResult 是删除操作的结果。
type DeleteResult struct {
	DeletedFolders    []int64         `json:"deletedFolders"`
	DeletedMediaCount int             `json:"deletedMediaCount"`
	Blocked           []BlockedFolder `json:"blocked"`
	// AffectedFolderIDs 用于删除完成后向后台服务发通知
	AffectedFolderIDs []int64 `json:"-"`
	// FilesToRemove 是待删除的物理文件绝对路径。
	// 事务提交成功后才真正删除 —— 旧版在事务内就删文件，
	// 一旦回滚，数据还在但文件已经没了（缺陷 D-05 同类）。
	FilesToRemove []string `json:"-"`
}

// Delete 递归删除文件夹及其整棵子树。
func (s *Service) Delete(ctx context.Context, u *auth.User, ids []int64) (*DeleteResult, error) {
	pre, err := s.Preview(ctx, u, ids)
	if err != nil {
		return nil, err
	}
	out := &DeleteResult{Blocked: pre.Blocked, DeletedFolders: []int64{}}
	if len(pre.Deletable) == 0 {
		return out, nil
	}

	var roots []int64
	for _, d := range pre.Deletable {
		roots = append(roots, d.ID)
	}
	subtree, err := s.Subtree(ctx, roots)
	if err != nil {
		return nil, err
	}

	// 先把待删媒体的物理路径与所属目录查出来，供提交后清理与通知使用
	type mediaRow struct {
		id       int64
		filename string
		folderID int64
	}
	var medias []mediaRow
	if err := s.eachChunk(ctx, subtree, func(chunk []int64) error {
		q, args := inClause(`SELECT id, filename, folderid FROM media WHERE folderid IN (%s)`, chunk)
		rows, err := s.db.QueryContext(ctx, q, args...)
		if err != nil {
			return err
		}
		defer rows.Close()
		for rows.Next() {
			var m mediaRow
			var fn sql.NullString
			if err := rows.Scan(&m.id, &fn, &m.folderID); err != nil {
				return err
			}
			m.filename = fn.String
			medias = append(medias, m)
		}
		return rows.Err()
	}); err != nil {
		return nil, fmt.Errorf("查询待删媒体: %w", err)
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	mediaIDs := make([]int64, 0, len(medias))
	for _, m := range medias {
		mediaIDs = append(mediaIDs, m.id)
	}

	// 级联清理媒体的关联记录。
	// 注意 camer_alarmofmedia 是「级联清理」而非「阻断删除」（BR-52）。
	for _, tbl := range []string{
		"DELETE FROM camer_alarmofmedia WHERE mediaid IN (%s)",
		"DELETE FROM shortcutkeytask WHERE mediaid IN (%s)",
	} {
		if err := eachChunkTx(ctx, tx, mediaIDs, tbl); err != nil {
			return nil, fmt.Errorf("清理媒体关联: %w", err)
		}
	}

	// 删媒体记录
	if err := eachChunkTx(ctx, tx, subtree, `DELETE FROM media WHERE folderid IN (%s)`); err != nil {
		return nil, fmt.Errorf("删除媒体记录: %w", err)
	}
	// 删整棵子树的文件夹
	if err := eachChunkTx(ctx, tx, subtree, `DELETE FROM filefolder WHERE id IN (%s)`); err != nil {
		return nil, fmt.Errorf("删除文件夹: %w", err)
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}

	out.DeletedFolders = subtree
	out.DeletedMediaCount = len(medias)
	for _, m := range medias {
		out.AffectedFolderIDs = append(out.AffectedFolderIDs, m.folderID)
		// media.id = 1 是系统内置提示音，物理文件永不删除（BR-25 / BR-54）
		if m.id > 1 && m.filename != "" && m.filename != "none" && m.filename != "tts" {
			out.FilesToRemove = append(out.FilesToRemove, m.filename)
		}
	}
	// 目录本身也要通知一次，让后台重新加载
	out.AffectedFolderIDs = append(out.AffectedFolderIDs, subtree...)
	return out, nil
}

// ---------- 内部工具 ----------

func (s *Service) nameAndOwner(ctx context.Context, id int64) (string, int64, error) {
	var name string
	var owner int64
	err := s.db.QueryRowContext(ctx,
		`SELECT name, userid FROM filefolder WHERE id = ? LIMIT 1`, id).Scan(&name, &owner)
	return name, owner, err
}

func (s *Service) countMediaIn(ctx context.Context, folderIDs []int64) (int, error) {
	total := 0
	err := s.eachChunk(ctx, folderIDs, func(chunk []int64) error {
		q, args := inClause(`SELECT COUNT(*) FROM media WHERE folderid IN (%s)`, chunk)
		var n int
		if err := s.db.QueryRowContext(ctx, q, args...).Scan(&n); err != nil {
			return err
		}
		total += n
		return nil
	})
	return total, err
}

// mediaRefsIn 检查这些文件夹下的媒体是否被引用。
//
// 四项引用来源（BR-23 / BR-51）：
//  1. mediaoftask       任务播放清单（作息方案的铃声也在这里）
//  2. shortcutkeytask   终端快捷键
//  3. alarmgroupmap     报警映射
//  4. playbelloftask    打铃条目（防御性检查，见契约 C-39）
func (s *Service) mediaRefsIn(ctx context.Context, folderIDs []int64) ([]mediaRef, error) {
	queries := []struct{ refType, sql string }{
		{"TASK", `SELECT m.id, m.name, COALESCE(t.taskname,'') FROM media m
		          JOIN mediaoftask mt ON mt.mediaid = m.id
		          LEFT JOIN task t ON t.taskid = mt.taskid
		          WHERE m.folderid IN (%s) LIMIT 5`},
		{"SHORTCUT", `SELECT m.id, m.name, COALESCE(sk.keyname,'') FROM media m
		              JOIN shortcutkeytask sk ON sk.mediaid = m.id
		              WHERE m.folderid IN (%s) LIMIT 5`},
		{"ALARM", `SELECT m.id, m.name, COALESCE(a.info,'') FROM media m
		           JOIN alarmgroupmap a ON a.mediaid = m.id
		           WHERE m.folderid IN (%s) LIMIT 5`},
		{"BELL", `SELECT m.id, m.name, COALESCE(p.lessonname,'') FROM media m
		          JOIN playbelloftask p ON p.bellid = m.id
		          WHERE m.folderid IN (%s) LIMIT 5`},
	}

	var refs []mediaRef
	for _, q := range queries {
		err := s.eachChunk(ctx, folderIDs, func(chunk []int64) error {
			stmt, args := inClause(q.sql, chunk)
			rows, err := s.db.QueryContext(ctx, stmt, args...)
			if err != nil {
				return err
			}
			defer rows.Close()
			for rows.Next() {
				var r mediaRef
				if err := rows.Scan(&r.MediaID, &r.MediaName, &r.RefName); err != nil {
					return err
				}
				r.RefType = q.refType
				refs = append(refs, r)
			}
			return rows.Err()
		})
		if err != nil {
			return nil, err
		}
		if len(refs) > 0 {
			return refs, nil
		}
	}
	return refs, nil
}

// lock 获取 MySQL 应用级命名锁。
//
// 用它替代「给表加唯一索引」——后者属于 DDL，被 R1 红线禁止。
func (s *Service) lock(ctx context.Context, name string) (func(), error) {
	var ok sql.NullInt64
	if err := s.db.QueryRowContext(ctx, `SELECT GET_LOCK(?, 5)`, name).Scan(&ok); err != nil {
		return nil, fmt.Errorf("获取命名锁: %w", err)
	}
	if !ok.Valid || ok.Int64 != 1 {
		return nil, fmt.Errorf("操作繁忙，请稍后重试")
	}
	return func() {
		_, _ = s.db.ExecContext(context.Background(), `SELECT RELEASE_LOCK(?)`, name)
	}, nil
}

const chunkSize = 500

func (s *Service) eachChunk(ctx context.Context, ids []int64, fn func([]int64) error) error {
	for len(ids) > 0 {
		n := chunkSize
		if len(ids) < n {
			n = len(ids)
		}
		if err := fn(ids[:n]); err != nil {
			return err
		}
		ids = ids[n:]
	}
	return nil
}

func eachChunkTx(ctx context.Context, tx *sql.Tx, ids []int64, tmpl string) error {
	for len(ids) > 0 {
		n := chunkSize
		if len(ids) < n {
			n = len(ids)
		}
		q, args := inClause(tmpl, ids[:n])
		if _, err := tx.ExecContext(ctx, q, args...); err != nil {
			return err
		}
		ids = ids[n:]
	}
	return nil
}

// inClause 把 %s 占位替换成 ?,?,? 并返回绑定参数。
// ID 永远以参数形式传入，绝不拼进 SQL 文本（对应缺陷 D-01 / D-09 的修复）。
func inClause(tmpl string, ids []int64) (string, []interface{}) {
	if len(ids) == 0 {
		return strings.Replace(tmpl, "%s", "NULL", 1), nil
	}
	ph := strings.TrimSuffix(strings.Repeat("?,", len(ids)), ",")
	args := make([]interface{}, len(ids))
	for i, v := range ids {
		args[i] = v
	}
	return strings.Replace(tmpl, "%s", ph, 1), args
}

func refTypeText(t string) string {
	switch t {
	case "TASK":
		return "任务"
	case "SHORTCUT":
		return "终端快捷键"
	case "ALARM":
		return "报警映射"
	case "BELL":
		return "打铃条目"
	}
	return t
}

// validateName 校验文件夹名。
//
// 旧版正则是 ^[0-9a-zA-Z一-龥]+$，把空格、括号、连字符全拒了，体验很差（缺陷 I-06）。
// 这里放宽为「非空 + 长度受列容量约束 + 禁止控制字符与路径分隔符」。
func validateName(name string) error {
	if name == "" {
		return fmt.Errorf("文件夹名称不能为空")
	}
	// filefolder.name 是 varchar(255)，utf8 下中文占 3 字节
	if len(name) > 255 {
		return fmt.Errorf("文件夹名称过长")
	}
	if strings.ContainsAny(name, `/\:*?"<>|`) {
		return fmt.Errorf(`文件夹名称不能包含 / \ : * ? " < > | 这些字符`)
	}
	for _, r := range name {
		if r < 0x20 || r == 0x7f {
			return fmt.Errorf("文件夹名称不能包含控制字符")
		}
	}
	return nil
}
