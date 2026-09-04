package bell

import (
	"context"
	"fmt"

	"htweb/internal/auth"
	"htweb/internal/task"
)

// 启用 / 停止方案（F-50）。
//
// 旧 bellstart_msg / bellstop_msg 的流程：
//
//  1. 用传入的 taskid 反查 task.info
//  2. 按方案名更新组内所有 `tasktype IN (1,15,9)` 的行 —— 包含功放子任务
//  3. 发 `project?state=1&name=<方案名>`（启用）/ `state=2`（停止）
//
// 修掉的问题：
//
//   - D-176 旧版的 UPDATE **不带 `channel = 0`**，而列表查询是带的。
//     库里若有同名的 channel != 0 任务，会被顺带启停，列表上却看不见。
//   - D-177 完全不校验归属，构造一个 taskid 就能启停别人的方案。
//   - D-179 `if($row = ...)` —— taskid 不存在时函数静默结束，页面无任何反馈。
//
// 保留的行为（是合理的，BR-230）：启停同时把 state 与 offlinestate 复位为 0。
// 但旧版对此在界面上只字未提（D-178），方案若正在离线传输会被静默打断，
// 所以响应里明确回一个 offlineStateReset 让界面能提示。

type ControlResult struct {
	PlanName          string  `json:"planName"`
	Enable            bool    `json:"enable"`
	AffectedTasks     int     `json:"affectedTasks"`
	OfflineStateReset bool    `json:"offlineStateReset"`
	TaskIDs           []int64 `json:"-"`
}

// VolumeResult 回报调音量影响了多少行。
type VolumeResult struct {
	PlanName      string `json:"planName"`
	Volume        int    `json:"volume"`
	AffectedTasks int    `json:"affectedTasks"`
}

// SetVolume 是 :80 作息方案页的「调整音量」按钮：整个方案改一次音量。
//
// 方案不是一张表，而是 task 里共享同一个 info 的一组行，所以这里按 info 批量改。
// 范围用 planScopeWithSubs —— **功放/LED 子任务一起改**，否则会出现
// 「打铃条目 80、配套功放子任务还是 60」这种半拉子状态。
//
// 与「修改方案」里那个音量输入是同一列（task.defaultvolume），
// 只是这个入口不用打开整张表单。
func (s *Service) SetVolume(ctx context.Context, u *auth.User, planName string,
	volume int) (*VolumeResult, error) {

	if volume < 0 || volume > 100 {
		return nil, fmt.Errorf("音量只能是 0 ~ 100")
	}
	if _, err := s.assertPlan(ctx, u, planName); err != nil {
		return nil, err
	}
	res, err := s.db.ExecContext(ctx,
		`UPDATE task SET defaultvolume = ? WHERE info = ? AND `+planScopeWithSubs(""),
		volume, planName)
	if err != nil {
		return nil, fmt.Errorf("调整方案音量: %w", err)
	}
	n, _ := res.RowsAffected()
	return &VolumeResult{PlanName: planName, Volume: volume, AffectedTasks: int(n)}, nil
}

// SetState 按方案整体启停。
func (s *Service) SetState(ctx context.Context, u *auth.User, planName string,
	enable bool) (*ControlResult, error) {

	if _, err := s.assertPlan(ctx, u, planName); err != nil {
		return nil, err
	}

	v := task.StateDisabled
	if enable {
		v = task.StateEnabled
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 先看有没有行正处在离线传输中，改完就看不出来了
	var offline int
	if err := tx.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE info = ? AND `+planScopeWithSubs("")+
			` AND COALESCE(offlinestate,0) <> 0`, planName).Scan(&offline); err != nil {
		return nil, fmt.Errorf("查询离线状态: %w", err)
	}

	res, err := tx.ExecContext(ctx, `
		UPDATE task SET projectstate = ?, state = 0, offlinestate = 0
		WHERE info = ? AND `+planScopeWithSubs(""), v, planName)
	if err != nil {
		return nil, fmt.Errorf("更新方案状态: %w", err)
	}
	n, _ := res.RowsAffected()

	ids, err := collectIDs(ctx, tx,
		`SELECT taskid FROM task WHERE info = ? AND `+planScope(""), planName)
	if err != nil {
		return nil, err
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return &ControlResult{
		PlanName: planName, Enable: enable, AffectedTasks: int(n),
		OfflineStateReset: offline > 0, TaskIDs: ids,
	}, nil
}
