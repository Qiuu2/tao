package offline

import (
	"context"
	"database/sql"
	"fmt"
	"strings"

	"htweb/internal/auth"
)

// 离线任务下发（F-44）。
//
// # 这个功能在旧版里从未成功执行过
//
// `set_offline_task` 引用 `terminaloftask.offlineparam` —— 现网这张表只有
// id / taskid / terminalid / workstate / groupid / area 六列，**根本没有 offlineparam**。
// 后面还跟着 `or die()`，所以一点这个按钮页面就直接中断（D-153 / G-03）。
//
// 新版用已经存在的 `offlinetaskofterminal.offlinestate` 表达同一语义，
// 任务自身的离线状态写 `task.offlinestate`（BR-210）。**绝不新增字段。**
//
// 旧版 `do_offline_task` 还一次 `LOCK TABLES` 锁 9 张表
// （offlinetask, task, offlinemedia, media, terminaloftask, offlinetaskofterminal,
// offlinemediaofterminal, terminal, mediaoftask）—— 期间全站几乎所有功能阻塞（D-154）。
// 新版一个事务，无表锁。

// offlineTaskCols 是 offlinetask 与 task 同构的 27 个字段（不含 taskid，它单独显式写）。
// 现网 offlinetask 共 28 列，除主键 taskid 外全在这里。
//
// 旧版是逐字段拼接，任一字段名写错整条失败且难排查（D-155）。
// 这里用 INSERT ... SELECT，列错位会在执行期立刻报错而不是静默错位。
const offlineTaskCols = `taskname, israndomplay, projectstate, timelengthtype, timelength,
	prepower, datasendmodel, state, startdate, enddate, playtime, endtime, exemodel,
	priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, info,
	defaultvolume, task_user_id, sec_task_id, parentid`

type TaskInput struct {
	TaskIDs     []int64
	TerminalIDs []int64
	Mode        Mode
}

type TaskResult struct {
	TaskCount      int     `json:"taskCount"`
	TerminalCount  int     `json:"terminalCount"`
	TaskCopies     int     `json:"taskCopies"`
	TerminalLinks  int     `json:"terminalLinks"`
	MediaLinks     int     `json:"mediaLinks"`
	State          State   `json:"offlinestate"`
	StateText      string  `json:"offlineStateText"`
	SkippedNoMedia []int64 `json:"skippedNoMedia"`
}

// DispatchTasks 把一批任务连同它们的媒体清单下发到一批终端。
func (s *Service) DispatchTasks(ctx context.Context, u *auth.User, in TaskInput) (*TaskResult, error) {
	state, err := ParseMode(in.Mode)
	if err != nil {
		return nil, err
	}
	taskIDs := dedup(in.TaskIDs)
	termIDs := dedup(in.TerminalIDs)
	if len(taskIDs) == 0 {
		return nil, fmt.Errorf("请选择要下发的任务")
	}
	if len(termIDs) == 0 {
		return nil, fmt.Errorf("请选择目标终端")
	}
	if len(taskIDs) > 200 || len(termIDs) > 3000 {
		return nil, fmt.Errorf("单次最多 200 个任务 × 3000 个终端")
	}
	if err := s.assertTasks(ctx, u, taskIDs); err != nil {
		return nil, err
	}
	if err := s.assertTerminals(ctx, u, termIDs); err != nil {
		return nil, err
	}

	out := &TaskResult{
		TaskCount: len(taskIDs), TerminalCount: len(termIDs),
		State: state, StateText: Text(int(state)), SkippedNoMedia: []int64{},
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	tph, targs := placeholders(taskIDs)

	// 1) 任务副本。offlinetask.taskid 显式等于 task.taskid（BR-207），
	//    重复下发时先删旧副本再插，避免主键冲突。
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM offlinetask WHERE taskid IN (`+tph+`)`, targs...); err != nil {
		return nil, fmt.Errorf("清理旧的离线任务副本: %w", err)
	}
	res, err := tx.ExecContext(ctx,
		`INSERT INTO offlinetask (taskid, `+offlineTaskCols+`, offlinestate)
		 SELECT taskid, `+offlineTaskCols+`, ? FROM task WHERE taskid IN (`+tph+`)`,
		append([]interface{}{int(state)}, targs...)...)
	if err != nil {
		return nil, fmt.Errorf("写入离线任务副本: %w", err)
	}
	n, _ := res.RowsAffected()
	out.TaskCopies = int(n)

	// 2) 任务-终端下发关系。area 显式写值，不用那个带引号的脏默认值（BR-209）
	links := 0
	for start := 0; start < len(taskIDs); start++ {
		tid := taskIDs[start]
		for i := 0; i < len(termIDs); i += 500 {
			end := i + 500
			if end > len(termIDs) {
				end = len(termIDs)
			}
			chunk := termIDs[i:end]
			var sb strings.Builder
			sb.WriteString(`INSERT INTO offlinetaskofterminal (taskid, terminalid, offlinestate, area) VALUES `)
			args := make([]interface{}, 0, len(chunk)*4)
			for j, term := range chunk {
				if j > 0 {
					sb.WriteByte(',')
				}
				sb.WriteString("(?,?,?,?)")
				args = append(args, tid, term, int(state), AreaAll)
			}
			sb.WriteString(" ON DUPLICATE KEY UPDATE offlinestate = VALUES(offlinestate), area = VALUES(area)")
			if _, err := tx.ExecContext(ctx, sb.String(), args...); err != nil {
				return nil, fmt.Errorf("写入任务离线下发关系: %w", err)
			}
			links += len(chunk)
		}
	}
	out.TerminalLinks = links

	// 3) 任务的媒体清单也要下发，taskid 写**真实任务 ID**（BR-208，
	//    区别于单纯媒体下发时的 0）
	mediaLinks, skipped, err := s.dispatchTaskMedia(ctx, tx, taskIDs, termIDs, state)
	if err != nil {
		return nil, err
	}
	out.MediaLinks, out.SkippedNoMedia = mediaLinks, skipped

	// 4) 任务自身的离线状态 —— 写 task.offlinestate，不是那个幽灵字段（BR-210）
	if _, err := tx.ExecContext(ctx,
		`UPDATE task SET offlinestate = ? WHERE taskid IN (`+tph+`)`,
		append([]interface{}{int(state)}, targs...)...); err != nil {
		return nil, fmt.Errorf("更新任务离线状态: %w", err)
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return out, nil
}

// dispatchTaskMedia 把每个任务的媒体清单写进 offlinemediaofterminal。
//
// 顺带保证这些媒体在 offlinemedia 里有副本 —— 没有副本终端就拿不到文件，
// 旧版三层嵌套循环（任务 × 终端 × 媒体）逐条 SQL 干这件事（D-156）。
func (s *Service) dispatchTaskMedia(ctx context.Context, tx *sql.Tx, taskIDs, termIDs []int64,
	state State) (int, []int64, error) {

	tph, targs := placeholders(taskIDs)
	rows, err := tx.QueryContext(ctx,
		`SELECT taskid, mediaid, COALESCE(sort,0) FROM mediaoftask
		 WHERE taskid IN (`+tph+`) ORDER BY taskid, sort, id`, targs...)
	if err != nil {
		return 0, nil, fmt.Errorf("查询任务媒体清单: %w", err)
	}
	type item struct {
		task, media int64
		sort        int
	}
	var items []item
	hasMedia := map[int64]bool{}
	mediaSet := map[int64]bool{}
	for rows.Next() {
		var it item
		if err := rows.Scan(&it.task, &it.media, &it.sort); err != nil {
			rows.Close()
			return 0, nil, err
		}
		items = append(items, it)
		hasMedia[it.task] = true
		mediaSet[it.media] = true
	}
	rows.Close()
	if err := rows.Err(); err != nil {
		return 0, nil, err
	}

	skipped := []int64{}
	for _, id := range taskIDs {
		if !hasMedia[id] {
			skipped = append(skipped, id)
		}
	}
	if len(items) == 0 {
		return 0, skipped, nil
	}

	// 先补齐 offlinemedia 副本
	mediaIDs := make([]int64, 0, len(mediaSet))
	for id := range mediaSet {
		mediaIDs = append(mediaIDs, id)
	}
	src, err := s.loadMedia(ctx, mediaIDs)
	if err != nil {
		return 0, nil, err
	}
	if _, _, err := syncCopies(ctx, tx, src); err != nil {
		return 0, nil, err
	}

	// 再写下发关系。taskid 写真实任务 ID（BR-208）
	total := 0
	type row struct {
		media, term, task int64
		sort              int
	}
	all := make([]row, 0, len(items)*len(termIDs))
	for _, it := range items {
		for _, term := range termIDs {
			all = append(all, row{it.media, term, it.task, it.sort})
		}
	}
	for start := 0; start < len(all); start += 500 {
		end := start + 500
		if end > len(all) {
			end = len(all)
		}
		chunk := all[start:end]
		var sb strings.Builder
		sb.WriteString(`INSERT INTO offlinemediaofterminal (mediaid, terminalid, offlinestate, taskid, sort) VALUES `)
		args := make([]interface{}, 0, len(chunk)*5)
		for i, r := range chunk {
			if i > 0 {
				sb.WriteByte(',')
			}
			sb.WriteString("(?,?,?,?,?)")
			args = append(args, r.media, r.term, int(state), r.task, r.sort)
		}
		sb.WriteString(" ON DUPLICATE KEY UPDATE offlinestate = VALUES(offlinestate), sort = VALUES(sort)")
		if _, err := tx.ExecContext(ctx, sb.String(), args...); err != nil {
			return 0, nil, fmt.Errorf("写入任务媒体离线关系: %w", err)
		}
		total += len(chunk)
	}
	return total, skipped, nil
}

func (s *Service) assertTasks(ctx context.Context, u *auth.User, ids []int64) error {
	ph, args := placeholders(ids)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE taskid IN (`+ph+`)`, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验任务: %w", err)
	}
	if n != len(ids) {
		return fmt.Errorf("任务清单里有已不存在的任务，请重新选择")
	}
	if u.IsAdmin {
		return nil
	}
	var own int
	oargs := append(append([]interface{}{}, args...), u.ID)
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE taskid IN (`+ph+`) AND task_user_id = ?`,
		oargs...).Scan(&own); err != nil {
		return fmt.Errorf("校验任务归属: %w", err)
	}
	if own != len(ids) {
		return fmt.Errorf("任务清单里有不属于你的任务")
	}
	return nil
}
