package bell

import (
	"context"
	"database/sql"
	"fmt"

	"htweb/internal/auth"
	"htweb/internal/store"
)

// 复制方案（F-52）。
//
// 旧 bellcop_msg 的三个硬伤：
//
//   - D-189 功放子任务的关联用「新 ID 减 1」硬算：
//     `SET sec_task_id = '$taskid_row[taskid]' - 1`。
//     这假设功放子任务的自增 ID 恰好等于主任务 ID + 1。
//     并发插入、或表里有自增空洞（删过任务就会有），子任务就挂到毫不相干的任务上。
//   - D-190 `INSERT … SELECT` 的字段清单不完整，endtime、disableday、interval_s、
//     intplaylength、intplaylengthtype、localplay、keyid、offlinestate 全丢成默认值。
//   - D-191 用 `SELECT taskid FROM task WHERE taskid = (SELECT MAX(taskid) FROM task)`
//     取刚插入的 ID，并发下会拿到别人的任务。
//
// 新版：显式 33 列的 `INSERT ... SELECT`（列错位会当场报错而不是静默错位），
// 主任务先插拿到 LastInsertId，再用这个真实 ID 写子任务的 sec_task_id，全程一个事务。

// copyCols 是复制时逐列对齐的清单。
//
// task 共 35 列，这里排除 taskid（自增）与 createtime（有 ON UPDATE 默认值），
// 其余 33 列全部复制。info 在语句里被换成新方案名，所以它不在这个清单中，
// 由调用处单独作为第一个 SELECT 项传入。
const copyCols = `taskname, israndomplay, projectstate, timelengthtype, timelength,
	prepower, datasendmodel, state, startdate, enddate, playtime, endtime, exemodel,
	priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid,
	defaultvolume, task_user_id, parentid, offlinestate, disableday, interval_s,
	intplaylength, intplaylengthtype, localplay, keyid`

type CopyResult struct {
	NewPlanName        string           `json:"newPlanName"`
	CopiedItems        int              `json:"copiedItems"`
	CopiedPowerSubs    int              `json:"copiedPowerSubTasks"`
	CopiedMediaRows    int              `json:"copiedMediaRows"`
	CopiedTerminalRows int              `json:"copiedTerminalRows"`
	IDMapping          map[string]int64 `json:"idMapping"`
	NewTaskIDs         []int64          `json:"-"`
	// Volume 是发通知时要带的 &volume=，取源方案的 defaultvolume。
	Volume int `json:"-"`
}

// Copy 以现有方案为模板复制出一个新方案。
func (s *Service) Copy(ctx context.Context, u *auth.User, planName, newName string) (*CopyResult, error) {
	owner, err := s.assertPlan(ctx, u, planName)
	if err != nil {
		return nil, err
	}
	target, err := checkPlanName(newName)
	if err != nil {
		return nil, err
	}
	if target == planName {
		return nil, fmt.Errorf("新方案名称不能与原方案相同")
	}

	unlock, err := store.Lock(ctx, s.db, planLock)
	if err != nil {
		return nil, err
	}
	defer unlock()

	if err := s.planNameFree(ctx, target); err != nil {
		return nil, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	items, err := collectIDs(ctx, tx,
		`SELECT taskid FROM task WHERE info = ? AND `+planScope("")+` ORDER BY taskid`, planName)
	if err != nil {
		return nil, err
	}
	if len(items) == 0 {
		return nil, ErrNotFound
	}

	out := &CopyResult{NewPlanName: target, IDMapping: map[string]int64{}, NewTaskIDs: []int64{}}
	if err := tx.QueryRowContext(ctx,
		`SELECT COALESCE(defaultvolume,80) FROM task WHERE taskid = ?`, items[0]).
		Scan(&out.Volume); err != nil {
		return nil, fmt.Errorf("查询方案音量: %w", err)
	}

	for _, srcID := range items {
		newID, err := copyOneTask(ctx, tx, srcID, target)
		if err != nil {
			return nil, err
		}
		out.CopiedItems++
		out.IDMapping[fmt.Sprint(srcID)] = newID
		out.NewTaskIDs = append(out.NewTaskIDs, newID)

		n, err := copyMedia(ctx, tx, srcID, newID)
		if err != nil {
			return nil, err
		}
		out.CopiedMediaRows += n

		n, err = copyTerminals(ctx, tx, srcID, newID)
		if err != nil {
			return nil, err
		}
		out.CopiedTerminalRows += n

		// 功放子任务：按 sec_task_id 找出源子任务，复制后把 sec_task_id
		// 指向**刚拿到的真实新主任务 ID**（BR-241，修 D-189）
		subs, err := collectIDs(ctx, tx,
			`SELECT taskid FROM task WHERE sec_task_id = ? AND tasktype = ?`, srcID, PowerType)
		if err != nil {
			return nil, err
		}
		for _, subID := range subs {
			newSub, err := copyOneTask(ctx, tx, subID, target)
			if err != nil {
				return nil, err
			}
			if _, err := tx.ExecContext(ctx,
				`UPDATE task SET sec_task_id = ? WHERE taskid = ?`, newID, newSub); err != nil {
				return nil, fmt.Errorf("关联功放子任务: %w", err)
			}
			out.CopiedPowerSubs++
			out.IDMapping[fmt.Sprint(subID)] = newSub

			n, err := copyTerminals(ctx, tx, subID, newSub)
			if err != nil {
				return nil, err
			}
			out.CopiedTerminalRows += n
		}
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	_ = owner
	return out, nil
}

// copyOneTask 复制一行 task，info 换成新方案名，sec_task_id 先置 0 由调用方回填。
func copyOneTask(ctx context.Context, tx *sql.Tx, srcID int64, newInfo string) (int64, error) {
	res, err := tx.ExecContext(ctx,
		`INSERT INTO task (info, sec_task_id, `+copyCols+`)
		 SELECT ?, 0, `+copyCols+` FROM task WHERE taskid = ?`, newInfo, srcID)
	if err != nil {
		return 0, fmt.Errorf("复制任务: %w", err)
	}
	id, err := res.LastInsertId()
	if err != nil {
		return 0, err
	}
	return id, nil
}

func copyMedia(ctx context.Context, tx *sql.Tx, srcID, dstID int64) (int, error) {
	res, err := tx.ExecContext(ctx,
		`INSERT INTO mediaoftask (mediaid, taskid, sort)
		 SELECT mediaid, ?, sort FROM mediaoftask WHERE taskid = ?`, dstID, srcID)
	if err != nil {
		return 0, fmt.Errorf("复制铃声清单: %w", err)
	}
	n, _ := res.RowsAffected()
	return int(n), nil
}

func copyTerminals(ctx context.Context, tx *sql.Tx, srcID, dstID int64) (int, error) {
	res, err := tx.ExecContext(ctx,
		`INSERT INTO terminaloftask (taskid, terminalid, workstate, groupid, area)
		 SELECT ?, terminalid, workstate, groupid, area FROM terminaloftask WHERE taskid = ?`,
		dstID, srcID)
	if err != nil {
		return 0, fmt.Errorf("复制终端清单: %w", err)
	}
	n, _ := res.RowsAffected()
	return int(n), nil
}
