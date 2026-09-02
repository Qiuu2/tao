package terminal

import (
	"context"
	"database/sql"
	"errors"
	"fmt"

	"htweb/internal/auth"
	"htweb/internal/store"
)

// 快捷任务（ok112 的 setquickplay / view_quickplay / set_task_quick_play）。
//
// 「在**这台**终端上按**这个键**，执行**那条任务**」。
//
// # ⚠ 它和快捷键寻呼**不是一套结构**
//
// 快捷键寻呼是两张表（terminalkey 定义 + terminalkeymap 目标）；
// 快捷任务只有一张，而且**不引用 terminalkey**：
//
//	terminalkeymaptask (keyid, terminalid, taskid)   PRIMARY KEY (keyid, terminalid)
//
// ⚠ **这里的 `keyid` 存的是「键值」本身，不是 terminalkey.id。**
//   ok112 的 set_task_quick_play 写的是 `VALUES('$gettaskid','$cmdargs','$keyvalue_s')`，
//   `$keyvalue_s` 就是下拉里选的那个键值。名字叫 keyid 很容易让人当成外键去 JOIN
//   terminalkey，那样一条都查不出来。
//
// # 主键决定了语义
//
// `PRIMARY KEY (keyid, terminalid)` ——「一台终端上一个键只能绑一条任务」。
// 所以「改绑」就是覆盖同一行，不需要先删后插。
//
// # 键值可选集与快捷键共用一套
//
// 同样按终端类型算（keyspec.go），且同样要求 isencode = 1。
// 快捷任务用的是**非急救**那一套（emergency = false）。

// QuickTask 是一条快捷任务绑定。
type QuickTask struct {
	// Key 是键值（terminalkeymaptask.keyid）。
	Key      int    `json:"key"`
	KeyLabel string `json:"keyLabel"`
	TaskID   int64  `json:"taskId"`
	TaskName string `json:"taskName"`
	// TaskMissing 绑定指向的任务已经不存在了。
	TaskMissing bool `json:"taskMissing"`
}

var ErrQuickTaskNotFound = errors.New("快捷任务绑定不存在")

// ListQuickTasks 列出一台终端上的快捷任务。
func (s *Service) ListQuickTasks(ctx context.Context, terminalID int64) ([]QuickTask, error) {
	typeID, isEncode, _, err := s.terminalType(ctx, terminalID)
	if err != nil {
		return nil, err
	}

	// LEFT JOIN：任务被删后绑定行可能还在，内连接会让这些脏行静默消失
	rs, err := s.db.QueryContext(ctx, `
		SELECT m.keyid, m.taskid, t.taskid IS NOT NULL, COALESCE(t.taskname,'')
		FROM terminalkeymaptask m
		LEFT JOIN task t ON t.taskid = m.taskid
		WHERE m.terminalid = ?
		ORDER BY m.keyid`, terminalID)
	if err != nil {
		return nil, fmt.Errorf("查询快捷任务: %w", err)
	}
	defer rs.Close()

	out := []QuickTask{}
	for rs.Next() {
		var q QuickTask
		var exists bool
		if err := rs.Scan(&q.Key, &q.TaskID, &exists, &q.TaskName); err != nil {
			return nil, err
		}
		q.TaskMissing = !exists
		if q.TaskMissing {
			q.TaskName = "(任务已删除)"
		}
		q.KeyLabel = labelFor(typeID, isEncode, false, q.Key)
		out = append(out, q)
	}
	return out, rs.Err()
}

const quickTaskLock = "htweb_terminal_quicktask"

// SetQuickTask 绑定（或改绑）一条快捷任务。
//
// 主键是 (keyid, terminalid)，所以同一台终端同一个键再绑就是覆盖，
// 用 INSERT ... ON DUPLICATE KEY UPDATE 一条搞定。
func (s *Service) SetQuickTask(ctx context.Context, u *auth.User, terminalID int64, key int, taskID int64) error {
	if _, _, err := s.CheckBound(ctx, u, []int64{terminalID}); err != nil {
		return err
	}
	// 键值必须是这个型号真有的（快捷任务用非急救那一套）
	if err := s.assertKeyValue(ctx, terminalID, key, false); err != nil {
		return err
	}

	unlock, err := store.Lock(ctx, s.db, quickTaskLock)
	if err != nil {
		return err
	}
	defer unlock()

	// 任务必须存在。旧版不查，绑一个不存在的任务后按键毫无反应，很难查。
	var exists int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE taskid = ?`, taskID).Scan(&exists); err != nil {
		return fmt.Errorf("校验任务: %w", err)
	}
	if exists == 0 {
		return fmt.Errorf("要绑定的任务不存在（taskid = %d）", taskID)
	}

	if _, err := s.db.ExecContext(ctx, `
		INSERT INTO terminalkeymaptask (keyid, terminalid, taskid) VALUES (?,?,?)
		ON DUPLICATE KEY UPDATE taskid = VALUES(taskid)`,
		key, terminalID, taskID); err != nil {
		return fmt.Errorf("保存快捷任务: %w", err)
	}
	return nil
}

// DeleteQuickTask 解除一条绑定。
func (s *Service) DeleteQuickTask(ctx context.Context, u *auth.User, terminalID int64, key int) error {
	if _, _, err := s.CheckBound(ctx, u, []int64{terminalID}); err != nil {
		return err
	}
	res, err := s.db.ExecContext(ctx,
		`DELETE FROM terminalkeymaptask WHERE terminalid = ? AND keyid = ?`, terminalID, key)
	if err != nil {
		return fmt.Errorf("删除快捷任务: %w", err)
	}
	if n, _ := res.RowsAffected(); n == 0 {
		return ErrQuickTaskNotFound
	}
	return nil
}

// QuickTaskOption 是「可绑的任务」下拉项。
type QuickTaskOption struct {
	TaskID   int64  `json:"taskId"`
	TaskName string `json:"taskName"`
	// UsedByKey 这条任务已经绑在本终端的哪个键上，0 表示还没绑。
	UsedByKey int `json:"usedByKey"`
}

// QuickTaskOptions 列出可绑的任务。
//
// 可见范围按 task_user_id 收敛（与任务模块一致），普通用户看不到别人的任务。
func (s *Service) QuickTaskOptions(ctx context.Context, u *auth.User, terminalID int64, keyword string) ([]QuickTaskOption, error) {
	cond := &store.Cond{}
	if !u.IsAdmin {
		cond.Add("COALESCE(t.task_user_id,0) = ?", u.ID)
	}
	if keyword != "" {
		cond.Add(`t.taskname LIKE ? ESCAPE '\\'`, store.EscapeLike(keyword))
	}

	rs, err := s.db.QueryContext(ctx, `
		SELECT t.taskid, COALESCE(t.taskname,''),
		       COALESCE((SELECT m.keyid FROM terminalkeymaptask m
		                  WHERE m.taskid = t.taskid AND m.terminalid = ? LIMIT 1), -1)
		FROM task t`+cond.Where()+`
		ORDER BY t.taskid DESC LIMIT 200`,
		append([]interface{}{terminalID}, cond.Args()...)...)
	if err != nil {
		return nil, fmt.Errorf("查询可绑任务: %w", err)
	}
	defer rs.Close()

	out := []QuickTaskOption{}
	for rs.Next() {
		var o QuickTaskOption
		var used sql.NullInt64
		if err := rs.Scan(&o.TaskID, &o.TaskName, &used); err != nil {
			return nil, err
		}
		// ⚠ 键值 0 是合法的（紧急触发），所以「没绑」不能用 0 表示，
		//   子查询里用 -1，这里再翻译回去。
		if used.Valid && used.Int64 >= 0 {
			o.UsedByKey = int(used.Int64)
		} else {
			o.UsedByKey = -1
		}
		out = append(out, o)
	}
	return out, rs.Err()
}
