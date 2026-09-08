package assistant

import (
	"context"
	"fmt"

	"htweb/internal/auth"
	"htweb/internal/notify"
)

// add_terminal_to_task / remove_terminal_from_task：给一条任务加/减终端。
//
// 「把A101加到早读预备铃里」「把A103从早读里去掉」。
//
// # 增量说法、整表写回
//
// terminaloftask 是任务与终端的关联表。页面上改终端是勾选框的全量提交，
// 助手说的是增量，所以先读出现有的，加上或去掉，再整体写回去。
// 与分区成员那一路（exec_zone.go）是同一个形状。
//
// ⚠ 功放/LED 子任务的终端清单是主任务的副本（BR-173），所以要一起改 ——
// 只改主任务会出现「主任务下发到 5 台、功放子任务还是 3 台」，
// 表现是有两台音箱前面的功放不开电。

// execTaskTerminals 是两个意图的共用实现。
func (s *Service) execTaskTerminals(intent string, add bool) executor {
	return func(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
		if s.tasks == nil || s.notifier == nil {
			return actionResult{Err: fmt.Errorf("任务服务未接入")}
		}
		raw := rawTextOf(slots)
		taskName := slotText(slots, "task_name", "TASK", "CONTENT", "task")
		taskIDText := slotText(slots, "task_id", "TASK_ID")
		scheduleName := slotText(slots, "schedule_name", "schedule_id", "SCHEDULE", "SCHEDULE_ID")
		label := "移除"
		if add {
			label = "加入"
		}

		if taskName == "" && !isNumericID(taskIDText) {
			return actionResult{
				Reply: askRuntimeReply(intent, "要改哪条任务",
					"比如说「把A101加到早读预备铃里」哈~"),
				MissingSlots: []string{"task_name"},
			}
		}
		target, res := s.resolveOneTask(ctx, u, raw, scheduleName, taskName, taskIDText)
		if res != nil {
			return *res
		}

		// ⚠ 这里分区是**来源**（把整个分区的终端加进任务），要展开
		ids, desc, res := s.resolveTerminalTargets(ctx, u, slots)
		if res != nil {
			return *res
		}

		cur, err := s.taskTerminalIDs(ctx, target.ID)
		if err != nil {
			return actionResult{Err: err}
		}
		var next []int64
		if add {
			next = uniqueIDs(append(append([]int64{}, cur...), ids...))
		} else {
			drop := map[int64]bool{}
			for _, id := range ids {
				drop[id] = true
			}
			for _, id := range cur {
				if !drop[id] {
					next = append(next, id)
				}
			}
		}
		// 加了没变 = 本来就在；减了没变 = 本来就不在。
		// 两种都要如实说 —— 回一句"已经移除"而其实什么也没动，
		// 用户会以为这台终端原本在里面。
		if len(next) == len(cur) {
			if add {
				return actionResult{
					Reply: fmt.Sprintf("%s 本来就在任务“%s”里，没有变化。", desc, target.Name),
				}
			}
			return actionResult{
				Reply: fmt.Sprintf("%s 本来就不在任务“%s”里，没有变化。", desc, target.Name),
			}
		}
		if !add && len(next) == 0 {
			// 一个终端都不剩的任务到点没有设备会播 —— 拦住，别让它悄悄变成空转
			return actionResult{
				Reply: fmt.Sprintf("这样任务“%s”就一个终端都没有了，到点不会有设备播放。"+
					"要真想停掉它，直接说「停用%s」。", target.Name, target.Name),
			}
		}

		if err := s.writeTaskTerminals(ctx, target.ID, next); err != nil {
			return actionResult{Err: err}
		}
		s.notifyTasksSaved(ctx, notify.TaskUpdated, []int64{target.ID})

		reply := stableReply(intent, taskTerminalVariants(add),
			[]string{target.Name, desc, label},
			map[string]string{"task_name": target.Name, "terminal_desc": desc, "action_label": label})
		reply = appendReplyDetails(reply,
			fmt.Sprintf("任务“%s”现在有 %d 个终端。", target.Name, len(next)))

		return actionResult{
			Reply: reply,
			ActionLog: []map[string]any{{
				"intent": intent, "mode": "runtime", "schedule_name": target.Info,
				"details": map[string]any{
					"task_id": itoa64(target.ID), "task_name": target.Name,
					"terminal_ids": ids, "count": len(next),
				},
			}},
		}
	}
}

func taskTerminalVariants(add bool) []string {
	if add {
		return []string{
			"已把{terminal_desc}加到任务“{task_name}”里。",
			"{terminal_desc}已经加进任务“{task_name}”了。",
			"任务“{task_name}”的终端已更新：加上了{terminal_desc}。",
		}
	}
	return []string{
		"已把{terminal_desc}从任务“{task_name}”里去掉。",
		"{terminal_desc}已经从任务“{task_name}”移除。",
		"任务“{task_name}”的终端已更新：去掉了{terminal_desc}。",
	}
}

// taskTerminalIDs 读一条任务当前的终端清单。
func (s *Service) taskTerminalIDs(ctx context.Context, taskID int64) ([]int64, error) {
	rows, err := s.db.QueryContext(ctx,
		`SELECT terminalid FROM terminaloftask WHERE taskid = ? ORDER BY terminalid`, taskID)
	if err != nil {
		return nil, fmt.Errorf("查询任务终端: %w", err)
	}
	defer rows.Close()
	var out []int64
	for rows.Next() {
		var id int64
		if err := rows.Scan(&id); err != nil {
			return nil, err
		}
		out = append(out, id)
	}
	return out, rows.Err()
}

// writeTaskTerminals 整表写回主任务与它的子任务。
//
// ⚠ 一个事务：改一半会让主任务与子任务的终端对不上，
// 那种状态从界面上完全看不出来。
func (s *Service) writeTaskTerminals(ctx context.Context, taskID int64, ids []int64) error {
	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 主任务与它的功放/LED 子任务一起换
	targets := []int64{taskID}
	rows, err := tx.QueryContext(ctx,
		`SELECT taskid FROM task WHERE sec_task_id = ?`, taskID)
	if err != nil {
		return fmt.Errorf("查询子任务: %w", err)
	}
	for rows.Next() {
		var id int64
		if err := rows.Scan(&id); err != nil {
			rows.Close()
			return err
		}
		targets = append(targets, id)
	}
	rows.Close()
	if err := rows.Err(); err != nil {
		return err
	}

	for _, id := range targets {
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM terminaloftask WHERE taskid = ?`, id); err != nil {
			return fmt.Errorf("清理任务终端: %w", err)
		}
		for _, tid := range ids {
			if _, err := tx.ExecContext(ctx,
				`INSERT INTO terminaloftask (taskid, terminalid) VALUES (?,?)`, id, tid); err != nil {
				return fmt.Errorf("写入任务终端: %w", err)
			}
		}
	}
	if err := tx.Commit(); err != nil {
		return fmt.Errorf("提交事务: %w", err)
	}
	return nil
}
