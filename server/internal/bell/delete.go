package bell

import (
	"context"
	"database/sql"
	"fmt"

	"htweb/internal/auth"
)

// 删除方案（F-51）。
//
// 旧 belldel_msg 的删除范围是：
//
//	WHERE task.info IN (SELECT info FROM task WHERE taskid IN (…) AND info<>'' AND channel=0)
//	  AND info<>'' AND channel=0
//
// **完全没有 tasktype 过滤**（D-182）。只要有一个文件广播任务的 info
// 恰好和作息方案同名，删方案就会把它一起删掉，而且没有任何提示。
// 新版补上 `tasktype IN (1,15,9)`，并在预演里如实报出会被删掉什么。

// DeleteImpact 是删除一个方案的影响面。
type DeleteImpact struct {
	PlanName      string `json:"planName"`
	Items         int    `json:"items"`
	PowerSubTasks int    `json:"powerSubTasks"`
	MediaRows     int    `json:"mediaRows"`
	TerminalRows  int    `json:"terminalRows"`
	KeyMapRows    int    `json:"keyMapRows"`
	OfflineTask   int    `json:"offlineTaskRows"`
	OfflineMedia  int    `json:"offlineMediaRows"`
	// SameNameOthers 是**同名但不属于本方案**的其它任务条数。
	// 旧版会把它们一并删掉；新版不删，只在这里提示存在这种撞名。
	SameNameOthers int `json:"sameNameOtherTasks"`
}

// Preview 统计删除影响面（BR-237，修 D-187：旧版点一下就删掉整个方案的 18 节课）。
func (s *Service) Preview(ctx context.Context, u *auth.User, planName string) (*DeleteImpact, error) {
	if _, err := s.assertPlan(ctx, u, planName); err != nil {
		return nil, err
	}
	imp := &DeleteImpact{PlanName: planName}

	ids, err := s.planAllIDs(ctx, planName)
	if err != nil {
		return nil, err
	}
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE info = ? AND `+planScope(""), planName).
		Scan(&imp.Items); err != nil {
		return nil, fmt.Errorf("统计条目数: %w", err)
	}
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE info = ? AND tasktype = ? AND channel = 0`,
		planName, PowerType).Scan(&imp.PowerSubTasks); err != nil {
		return nil, fmt.Errorf("统计功放子任务: %w", err)
	}
	// 同名撞车：info 相同但既不是方案条目也不是它的功放子任务
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE info = ? AND NOT (`+planScopeWithPower("")+`)`,
		planName).Scan(&imp.SameNameOthers); err != nil {
		return nil, fmt.Errorf("统计同名任务: %w", err)
	}

	if len(ids) == 0 {
		return imp, nil
	}
	ph, args := placeholders(ids)
	for _, spec := range []struct {
		q   string
		dst *int
	}{
		{`SELECT COUNT(*) FROM mediaoftask WHERE taskid IN (` + ph + `)`, &imp.MediaRows},
		{`SELECT COUNT(*) FROM terminaloftask WHERE taskid IN (` + ph + `)`, &imp.TerminalRows},
		{`SELECT COUNT(*) FROM terminalkeymaptask WHERE taskid IN (` + ph + `)`, &imp.KeyMapRows},
		{`SELECT COUNT(*) FROM offlinetaskofterminal WHERE taskid IN (` + ph + `)`, &imp.OfflineTask},
		{`SELECT COUNT(*) FROM offlinemediaofterminal WHERE taskid IN (` + ph + `)`, &imp.OfflineMedia},
	} {
		if err := s.db.QueryRowContext(ctx, spec.q, args...).Scan(spec.dst); err != nil {
			return nil, fmt.Errorf("统计删除影响: %w", err)
		}
	}
	return imp, nil
}

// planAllIDs 取方案下全部 taskid（条目 + 功放子任务）。
func (s *Service) planAllIDs(ctx context.Context, planName string) ([]int64, error) {
	rows, err := s.db.QueryContext(ctx,
		`SELECT taskid FROM task WHERE info = ? AND `+planScopeWithPower(""), planName)
	if err != nil {
		return nil, fmt.Errorf("查询方案任务: %w", err)
	}
	defer rows.Close()
	out := []int64{}
	for rows.Next() {
		var id int64
		if err := rows.Scan(&id); err != nil {
			return nil, err
		}
		out = append(out, id)
	}
	return out, rows.Err()
}

type DeleteResult struct {
	PlanName     string  `json:"planName"`
	DeletedTasks []int64 `json:"deletedTasks"`
	Items        int     `json:"items"`
	PowerSubs    int     `json:"powerSubTasks"`
}

// Delete 删除整个方案。
func (s *Service) Delete(ctx context.Context, u *auth.User, planName string) (*DeleteResult, error) {
	if _, err := s.assertPlan(ctx, u, planName); err != nil {
		return nil, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	items, err := collectIDs(ctx, tx,
		`SELECT taskid FROM task WHERE info = ? AND `+planScope(""), planName)
	if err != nil {
		return nil, err
	}
	subs, err := collectIDs(ctx, tx,
		`SELECT taskid FROM task WHERE info = ? AND tasktype = ? AND channel = 0`,
		planName, PowerType)
	if err != nil {
		return nil, err
	}
	all := append(append([]int64{}, items...), subs...)
	if len(all) == 0 {
		return nil, ErrNotFound
	}
	if err := purgeTaskRows(ctx, tx, all); err != nil {
		return nil, err
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return &DeleteResult{PlanName: planName, DeletedTasks: all,
		Items: len(items), PowerSubs: len(subs)}, nil
}

// purgeTaskRows 删掉一批任务及其全部关联行。
//
// 旧版只清 mediaoftask / terminaloftask（D-184），
// terminalkeymaptask、offlinetaskofterminal、offlinemediaofterminal 三张表全留着孤儿行。
// 而且是逐条 DELETE：每条任务 3 条 SQL，18 节课 = 54 次往返（D-185）。
// 这里五张关联表加任务本体，六条批量语句一次做完。
func purgeTaskRows(ctx context.Context, tx *sql.Tx, ids []int64) error {
	if len(ids) == 0 {
		return nil
	}
	ph, args := placeholders(ids)
	for _, q := range []string{
		`DELETE FROM mediaoftask WHERE taskid IN (` + ph + `)`,
		`DELETE FROM terminaloftask WHERE taskid IN (` + ph + `)`,
		`DELETE FROM terminalkeymaptask WHERE taskid IN (` + ph + `)`,
		`DELETE FROM offlinetaskofterminal WHERE taskid IN (` + ph + `)`,
		`DELETE FROM offlinemediaofterminal WHERE taskid IN (` + ph + `)`,
		`DELETE FROM task WHERE taskid IN (` + ph + `)`,
	} {
		if _, err := tx.ExecContext(ctx, q, args...); err != nil {
			return fmt.Errorf("清理任务关联数据: %w", err)
		}
	}
	return nil
}
