package user

import (
	"context"
	"database/sql"
	"errors"
	"fmt"

	"htweb/internal/auth"
	"htweb/internal/notify"
)

// FileRemover 用于在事务提交**之后**清理媒体物理文件。
// 由 media.Service 提供实现，这里用接口注入避免包间强耦合。
type FileRemover interface {
	RemoveFiles(rels []string)
}

// FolderNotifier 通知后台 C 服务媒体库发生了变化。
type FolderNotifier interface {
	MediaChangedBatch(ctx context.Context, state notify.State, folderIDs []int64)
}

// SetSideEffects 注入删除后的副作用处理器。两者都可为 nil。
func (s *Service) SetSideEffects(fr FileRemover, fn FolderNotifier) {
	s.files = fr
	s.notify = fn
}

// ---------- F-17 删除用户组（超高危） ----------

// PreviewDeleteGroup 计算删除用户组的影响面。
//
// 旧版这个操作只弹一句 "确定删除吗?"，用户完全看不到会连带删掉
// 组内所有用户及其全部文件夹、媒体、任务、分区（BR-100）。
func (s *Service) PreviewDeleteGroup(ctx context.Context, id int64) (*CascadeImpact, error) {
	if id == SystemGroupID {
		return nil, ErrSystemGroup
	}
	var exists int
	err := s.db.QueryRowContext(ctx, `SELECT 1 FROM usergroup WHERE id = ? LIMIT 1`, id).Scan(&exists)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询用户组: %w", err)
	}

	userIDs, err := s.groupUserIDs(ctx, id)
	if err != nil {
		return nil, err
	}
	t, err := s.collectCascade(ctx, userIDs)
	if err != nil {
		return nil, err
	}
	impact := s.impactOf(t)
	impact.TaskFolders = len(userIDs)
	return &impact, nil
}

// DeleteGroup 删除用户组及组内**全部**用户的全部数据。
//
// confirmName 必须与用户组名称完全一致，否则拒绝执行 —— 这是对
// 旧版「一次确认就清空一整个组」的防呆改造。
func (s *Service) DeleteGroup(ctx context.Context, id int64, confirmName string) (*CascadeImpact, error) {
	if id == SystemGroupID {
		return nil, fmt.Errorf("%w：系统用户组不可删除", ErrSystemGroup)
	}

	var name string
	err := s.db.QueryRowContext(ctx, `SELECT name FROM usergroup WHERE id = ? LIMIT 1`, id).Scan(&name)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询用户组: %w", err)
	}
	if confirmName != name {
		return nil, fmt.Errorf("确认文本与用户组名称不一致，操作已取消")
	}

	// groupUserIDs 用 while 语义取出**全部**用户 —— 这正是旧版 D-45 漏掉的地方
	userIDs, err := s.groupUserIDs(ctx, id)
	if err != nil {
		return nil, err
	}
	// admin 永远不会因为删组被带走
	userIDs = excludeSystemUser(userIDs)

	t, err := s.collectCascade(ctx, userIDs)
	if err != nil {
		return nil, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	if err := s.deleteCascade(ctx, tx, t); err != nil {
		return nil, err
	}
	if _, err := tx.ExecContext(ctx, `DELETE FROM usergroup WHERE id = ?`, id); err != nil {
		return nil, fmt.Errorf("删除用户组: %w", err)
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}

	s.afterDelete(ctx, t)
	impact := s.impactOf(t)
	impact.TaskFolders = len(userIDs)
	return &impact, nil
}

// ---------- F-22 删除用户 ----------

func (s *Service) PreviewDeleteUser(ctx context.Context, ids []int64) (*CascadeImpact, error) {
	ids = excludeSystemUser(ids)
	t, err := s.collectCascade(ctx, ids)
	if err != nil {
		return nil, err
	}
	impact := s.impactOf(t)
	impact.TaskFolders = len(ids)
	return &impact, nil
}

func (s *Service) DeleteUsers(ctx context.Context, cur *auth.User, ids []int64) (*CascadeImpact, error) {
	if f := s.registerFlag(ctx); f != 1 && f != 2 {
		return nil, ErrNotRegistered
	}
	for _, id := range ids {
		if id == SystemUserID {
			return nil, fmt.Errorf("%w：不可删除", ErrSystemUser)
		}
		if id == cur.ID {
			return nil, fmt.Errorf("不能删除当前登录的账号")
		}
	}
	if len(ids) == 0 {
		return nil, fmt.Errorf("未选择要删除的用户")
	}

	t, err := s.collectCascade(ctx, ids)
	if err != nil {
		return nil, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	if err := s.deleteCascade(ctx, tx, t); err != nil {
		return nil, err
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}

	s.afterDelete(ctx, t)
	impact := s.impactOf(t)
	impact.TaskFolders = len(ids)
	return &impact, nil
}

// afterDelete 处理事务提交后的副作用：删物理文件、通知 C 服务。
//
// 顺序很重要 —— 必须先提交数据库再删文件。
// 反过来的话事务一旦回滚，文件已经没了，数据库里却还挂着记录。
func (s *Service) afterDelete(ctx context.Context, t *cascadeTargets) {
	if s.files != nil && len(t.mediaFiles) > 0 {
		s.files.RemoveFiles(t.mediaFiles)
	}
	if s.notify != nil && len(t.mediaFolders) > 0 {
		s.notify.MediaChangedBatch(ctx, notify.StateDeleted, dedupe(t.mediaFolders))
	}
}

// ---------- F-21 启用 / 停用用户 ----------

// SetEnable 切换用户启用状态，并联动该用户名下所有任务的运行状态。
//
// ⚠️ task.projectstate 的取值约定与列注释相反（契约 C-36）：
//
//	0 = 启用   1 = 停用
//
// 依据：模板 <{if $projectstate == 0}> 渲染的是「已启用」，
// bellstart_msg 写 0 的同时向 C 服务发 state=1（启动），
// 且列表 ORDER BY projectstate ASC 把启用中的任务排在前面。
// 旧代码在这里是**正确的**，新版必须原样保持，否则会误停整批广播任务。
func (s *Service) SetEnable(ctx context.Context, cur *auth.User, id int64, enable bool) (int64, error) {
	if id == SystemUserID {
		return 0, fmt.Errorf("%w：不可停用", ErrSystemUser)
	}
	if id == cur.ID {
		return 0, fmt.Errorf("不能停用当前登录的账号")
	}

	var exists int
	err := s.db.QueryRowContext(ctx, `SELECT 1 FROM book_admin WHERE id = ? LIMIT 1`, id).Scan(&exists)
	if errors.Is(err, sql.ErrNoRows) {
		return 0, ErrNotFound
	}
	if err != nil {
		return 0, fmt.Errorf("查询用户: %w", err)
	}

	enableVal, projectState := 0, 1 // 停用
	if enable {
		enableVal, projectState = 1, 0 // 启用
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx,
		`UPDATE book_admin SET enable = ? WHERE id = ?`, enableVal, id); err != nil {
		return 0, fmt.Errorf("更新用户状态: %w", err)
	}
	res, err := tx.ExecContext(ctx,
		`UPDATE task SET projectstate = ? WHERE task_user_id = ?`, projectState, id)
	if err != nil {
		return 0, fmt.Errorf("联动任务状态: %w", err)
	}
	affected, _ := res.RowsAffected()

	// 刻意不清空 book_admin.usersessionid（BR-121）。
	// 该字段是旧版 Web 的会话标识，新旧系统共用一套库，
	// 清掉会把还在用旧界面的人一起踢下线。
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return affected, nil
}

// ---------- 小工具 ----------

func (s *Service) groupUserIDs(ctx context.Context, groupID int64) ([]int64, error) {
	var ids []int64
	err := s.collectInt64(ctx,
		`SELECT id FROM book_admin WHERE usergroupid = ?`, []interface{}{groupID}, &ids)
	if err != nil {
		return nil, fmt.Errorf("查询组内用户: %w", err)
	}
	return ids, nil
}

func excludeSystemUser(ids []int64) []int64 {
	out := ids[:0:0]
	for _, id := range ids {
		if id != SystemUserID {
			out = append(out, id)
		}
	}
	return out
}

func dedupe(ids []int64) []int64 {
	seen := make(map[int64]bool, len(ids))
	out := make([]int64, 0, len(ids))
	for _, id := range ids {
		if !seen[id] {
			seen[id] = true
			out = append(out, id)
		}
	}
	return out
}
