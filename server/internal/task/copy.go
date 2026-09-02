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

// 任务复制与终端同步（F-37）。
//
// # 旧版 copyFileTask 的三处数据丢失
//
//  1. INSERT 的列清单里**漏了 endtime**：源任务的列按 $getrows[2..11] 再跳到
//     [13..32] 取，正好跳过下标 12（endtime）。复制出来的任务结束时间被重置成
//     00:00:00，看上去只是「少填了一格」，实际会让整条任务的播放时长语义变掉。
//  2. 复制媒体清单用的是 if 而不是 while —— **只复制第一条媒体**。
//     多媒体任务复制完就剩一首歌。
//  3. 取新 ID 用 SELECT MAX(taskid)，并发下会拿到别人刚插入的行（D-126）。
//
// 另外它按位置下标 $getrows[N] 读列，任何一次列顺序变化都会让复制悄悄错位。
// 新版改成显式列名的 INSERT ... SELECT，列错位在编译期/执行期就会暴露。
//
// # 旧版不校验目标分组（D-127）
//
// 旧接口连目标分组参数都没有，直接沿用源任务的 parentid。
// 新版要求显式给出 targetFolderId 并走可见性闸门。

// CopyResult 是复制结果。
type CopyResult struct {
	TaskID      int64 `json:"taskid"`
	PowerTaskID int64 `json:"powerTaskId"`
	Volume      int   `json:"-"`
}

// 复制时需要显式列出的任务列。
// 写成常量而不是 SELECT *：列顺序变了这里会报错，而不是静默错位。
const copyCols = `taskname, israndomplay, projectstate, timelengthtype, timelength,
	prepower, datasendmodel, state, startdate, enddate, playtime, endtime,
	exemodel, priority, tasktype, channel, bandrate, samplerate,
	cmd, cmdargs, playfileid, info, defaultvolume, task_user_id,
	sec_task_id, parentid, offlinestate, disableday,
	interval_s, intplaylength, intplaylengthtype, localplay, keyid`

// Copy 复制一个任务（连同媒体、终端清单与功放子任务）到指定分组。
func (s *Service) Copy(ctx context.Context, u *auth.User, srcID int64,
	targetFolderID int64, newName string) (*CopyResult, error) {

	newName, err := checkTaskName(newName)
	if err != nil {
		return nil, err
	}
	if err := s.AssertFolderVisible(ctx, u, targetFolderID); err != nil {
		return nil, err
	}

	var owner int64
	var taskType, volume int
	err = s.db.QueryRowContext(ctx,
		`SELECT COALESCE(task_user_id,0), tasktype, COALESCE(defaultvolume,80)
		 FROM task WHERE taskid = ? LIMIT 1`, srcID).Scan(&owner, &taskType, &volume)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询源任务: %w", err)
	}
	if !u.IsAdmin && owner != u.ID {
		return nil, ErrNoPermission
	}
	if !isDeletableType(taskType) {
		return nil, fmt.Errorf("任务类型 %d 不支持复制", taskType)
	}

	// 复制出来的任务归当前操作者所有，落到他选中的分组下
	newOwner := u.ID

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	newID, err := copyOneTask(ctx, tx, srcID, newName, targetFolderID, newOwner, 0)
	if err != nil {
		return nil, err
	}

	// 媒体清单：全部复制，不是只复制第一条
	if _, err := tx.ExecContext(ctx,
		`INSERT INTO mediaoftask (mediaid, taskid, sort)
		 SELECT mediaid, ?, sort FROM mediaoftask WHERE taskid = ?`,
		newID, srcID); err != nil {
		return nil, fmt.Errorf("复制媒体清单: %w", err)
	}
	if err := copyTerminals(ctx, tx, srcID, newID); err != nil {
		return nil, err
	}

	out := &CopyResult{TaskID: newID, Volume: volume}

	// 功放子任务
	var srcPower sql.NullInt64
	if err := tx.QueryRowContext(ctx,
		`SELECT taskid FROM task WHERE sec_task_id = ? AND tasktype = ? LIMIT 1`,
		srcID, TypePowerAmp).Scan(&srcPower); err != nil && !errors.Is(err, sql.ErrNoRows) {
		return nil, fmt.Errorf("查询源功放子任务: %w", err)
	}
	if srcPower.Valid {
		powerID, err := copyOneTask(ctx, tx, srcPower.Int64, newName, targetFolderID, newOwner, newID)
		if err != nil {
			return nil, err
		}
		if err := copyTerminals(ctx, tx, srcPower.Int64, powerID); err != nil {
			return nil, err
		}
		out.PowerTaskID = powerID
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return out, nil
}

// copyOneTask 用 INSERT ... SELECT 复制一行任务，并覆盖名称、分组、归属与 sec_task_id。
// 覆盖的四列在 SELECT 里用字面参数替换，其余列原样带过来 —— 包括旧版漏掉的 endtime。
func copyOneTask(ctx context.Context, tx *sql.Tx, srcID int64,
	newName string, folderID, ownerID, secTaskID int64) (int64, error) {

	res, err := tx.ExecContext(ctx, `
		INSERT INTO task (`+copyCols+`)
		SELECT ?, israndomplay, projectstate, timelengthtype, timelength,
		       prepower, datasendmodel, state, startdate, enddate, playtime, endtime,
		       exemodel, priority, tasktype, channel, bandrate, samplerate,
		       cmd, cmdargs, playfileid, info, defaultvolume, ?,
		       ?, ?, offlinestate, disableday,
		       interval_s, intplaylength, intplaylengthtype, localplay, keyid
		FROM task WHERE taskid = ?`,
		newName, ownerID, secTaskID, folderID, srcID)
	if err != nil {
		return 0, fmt.Errorf("复制任务: %w", err)
	}
	n, err := res.RowsAffected()
	if err == nil && n == 0 {
		return 0, ErrNotFound
	}
	return res.LastInsertId()
}

func copyTerminals(ctx context.Context, tx *sql.Tx, srcID, dstID int64) error {
	if _, err := tx.ExecContext(ctx,
		`INSERT INTO terminaloftask (taskid, terminalid, workstate, groupid, area)
		 SELECT ?, terminalid, workstate, groupid, area FROM terminaloftask WHERE taskid = ?`,
		dstID, srcID); err != nil {
		return fmt.Errorf("复制终端清单: %w", err)
	}
	return nil
}

func checkTaskName(name string) (string, error) {
	name = strings.TrimSpace(name)
	if name == "" {
		return "", fmt.Errorf("任务名称不能为空")
	}
	if utf8.RuneCountInString(name) > 85 {
		return "", fmt.Errorf("任务名称最多 85 个字符")
	}
	return name, nil
}

// ---------- 终端同步 ----------

// SyncResult 是终端同步的结果。
type SyncResult struct {
	Added    int       `json:"added"`
	Tasks    []int64   `json:"tasks"`
	Blocked  []Blocked `json:"blocked"`
	Notified bool      `json:"notified"`
}

// SyncTerminals 把一批终端补加到一批任务上。
//
// 这是旧版 set_task_synch 的真实语义 —— 手册把它描述成「把一个任务的配置同步到
// 其他任务」，与代码完全不符，以代码为准。
//
// 旧版有一处很危险的写法：给主任务插完关联行之后，直接
//
//	$bbb = $bbb + 1;
//	INSERT INTO terminaloftask ... VALUES('$bbb', ...)
//
// 也就是**假定功放子任务的 taskid 恒为主任务 id + 1**。这个假设只在
// 「建完主任务紧接着建子任务、中间没有任何其他插入」时才成立；
// 一旦不成立，就会把终端关联行插到一个毫不相干的任务上，
// 而且没有任何报错。新版按 sec_task_id 查出真正的子任务 id。
func (s *Service) SyncTerminals(ctx context.Context, u *auth.User,
	taskIDs []int64, terminalIDs []int64) (*SyncResult, error) {

	out := &SyncResult{Tasks: []int64{}, Blocked: []Blocked{}}
	if len(taskIDs) == 0 || len(terminalIDs) == 0 {
		return out, nil
	}

	rows, err := s.loadControlRows(ctx, taskIDs)
	if err != nil {
		return nil, err
	}
	var doable []int64
	for _, id := range taskIDs {
		r, exists := rows[id]
		switch {
		case !exists:
			out.Blocked = append(out.Blocked, Blocked{ID: id,
				Reason: "NOT_FOUND", Detail: "任务不存在"})
		case !u.IsAdmin && r.owner != u.ID:
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "NOT_OWNER", Detail: "只能操作自己创建的任务"})
		default:
			doable = append(doable, id)
		}
	}
	if len(doable) == 0 {
		return out, nil
	}

	// 终端必须存在，普通用户还必须已绑定
	refs := make([]TerminalRef, 0, len(terminalIDs))
	for _, id := range terminalIDs {
		refs = append(refs, TerminalRef{TerminalID: id, Area: AreaAll})
	}
	probe := Input{Terminals: refs}
	if err := s.validateTerminals(ctx, u, &probe); err != nil {
		return nil, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 终端当前所属分区，写进 terminaloftask.groupid（旧版同此逻辑）
	groupOf, err := terminalGroups(ctx, tx, terminalIDs)
	if err != nil {
		return nil, err
	}

	for _, taskID := range doable {
		targets := []int64{taskID}
		var sub sql.NullInt64
		if err := tx.QueryRowContext(ctx,
			`SELECT taskid FROM task WHERE sec_task_id = ? AND tasktype = ? LIMIT 1`,
			taskID, TypePowerAmp).Scan(&sub); err != nil && !errors.Is(err, sql.ErrNoRows) {
			return nil, fmt.Errorf("查询功放子任务: %w", err)
		}
		if sub.Valid {
			targets = append(targets, sub.Int64)
		}

		for _, t := range targets {
			existing, err := existingTerminals(ctx, tx, t)
			if err != nil {
				return nil, err
			}
			for _, tid := range terminalIDs {
				if existing[tid] {
					continue
				}
				if _, err := tx.ExecContext(ctx,
					`INSERT INTO terminaloftask (taskid, terminalid, groupid, area) VALUES (?,?,?,?)`,
					t, tid, groupOf[tid], AreaAll); err != nil {
					return nil, fmt.Errorf("写入终端清单: %w", err)
				}
				out.Added++
			}
		}
		// 旧版同样在这里把 offlinestate 复位
		if _, err := tx.ExecContext(ctx,
			`UPDATE task SET offlinestate = 0 WHERE taskid = ?`, taskID); err != nil {
			return nil, fmt.Errorf("复位离线状态: %w", err)
		}
	}

	if err := bindTerminalsToOwner(ctx, tx, u.ID, refs); err != nil {
		return nil, err
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	out.Tasks = doable
	return out, nil
}

func terminalGroups(ctx context.Context, tx *sql.Tx, ids []int64) (map[int64]int64, error) {
	ph, args := placeholders(ids)
	rs, err := tx.QueryContext(ctx,
		`SELECT terminalid, groupid FROM terminalofgroup WHERE terminalid IN (`+ph+`) ORDER BY id`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询终端分区: %w", err)
	}
	defer rs.Close()
	out := map[int64]int64{}
	for rs.Next() {
		var tid, gid int64
		if err := rs.Scan(&tid, &gid); err != nil {
			return nil, err
		}
		if _, seen := out[tid]; !seen {
			out[tid] = gid
		}
	}
	return out, rs.Err()
}

func existingTerminals(ctx context.Context, tx *sql.Tx, taskID int64) (map[int64]bool, error) {
	rs, err := tx.QueryContext(ctx,
		`SELECT terminalid FROM terminaloftask WHERE taskid = ?`, taskID)
	if err != nil {
		return nil, fmt.Errorf("查询已有终端清单: %w", err)
	}
	defer rs.Close()
	out := map[int64]bool{}
	for rs.Next() {
		var id int64
		if err := rs.Scan(&id); err != nil {
			return nil, err
		}
		out[id] = true
	}
	return out, rs.Err()
}
