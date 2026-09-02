package task

import (
	"context"
	"database/sql"
	"fmt"

	"htweb/internal/auth"
)

// 删除任务（F-35）。
//
// 旧版最危险的一处：删除任务本体带着 AND info = '' AND channel = 0
// AND (tasktype = 2 or 7 or 15) 三个条件，而清理关联表时**一个条件都不带**：
//
//	DELETE FROM terminaloftask WHERE taskid IN (...)   ← 无条件
//	DELETE FROM mediaoftask   WHERE taskid IN (...)    ← 无条件
//	DELETE FROM task          WHERE taskid IN (...) AND info='' AND ...  ← 有条件
//
// 于是不满足条件的任务（例如 info 非空的「方案」任务）关联数据被删光、
// 任务本体却留着 —— 变成一个既没有媒体也没有终端的空壳，播放时静默失败（D-118）。
// 新版先算出**真正可删的集合**，再只对该集合动手；不可删的原样列进 blocked。

// Blocked 说明某个任务为什么没被删。
type Blocked struct {
	ID     int64  `json:"id"`
	Name   string `json:"name"`
	Reason string `json:"reason"`
	Detail string `json:"detail"`
}

// DeleteImpact 是单个任务的删除影响面。
type DeleteImpact struct {
	Media        int   `json:"media"`
	Terminals    int   `json:"terminals"`
	ShortcutKeys int   `json:"shortcutKeys"`
	OfflineTasks int   `json:"offlineTasks"`
	OfflineMedia int   `json:"offlineMedia"`
	PowerTaskID  int64 `json:"powerTaskId"`
	LEDTaskID    int64 `json:"ledTaskId"`
	// OtherLinked 是「sec_task_id 指向本任务、但类型既不是功放也不是 LED」的任务数。
	//
	// 现网确实存在这种关联：任务 70009（tasktype=30）的 sec_task_id 指向 70007（文件广播）。
	// 旧版和新版都只连带删除类型 9 与 24 两种子任务，其余类型不动 ——
	// 贸然扩大删除范围风险更大。但删完之后这些任务的 sec_task_id 就成了悬空引用，
	// 所以至少要在预览里如实告诉用户有几条会受影响。
	OtherLinked int `json:"otherLinked"`
}

type PreviewItem struct {
	TaskID   int64        `json:"taskid"`
	TaskName string       `json:"taskname"`
	Impact   DeleteImpact `json:"impact"`
}

type PreviewResult struct {
	Deletable []PreviewItem `json:"deletable"`
	Blocked   []Blocked     `json:"blocked"`
}

type DeleteResult struct {
	Deleted     []int64   `json:"deleted"`
	DeletedSubs []int64   `json:"deletedSubTasks"`
	Blocked     []Blocked `json:"blocked"`
	Notified    bool      `json:"notified"`
}

// deletableRow 是可删性判定用到的最小字段集。
type deletableRow struct {
	id       int64
	name     string
	taskType int
	info     string
	channel  int
	owner    int64
}

// gate 把请求的 id 分成「可删」与「被挡」两组。
//
// 判定条件与旧版删除语句保持一致（BR-178），只是提前到了删关联表之前。
func (s *Service) gate(ctx context.Context, u *auth.User, ids []int64) ([]deletableRow, []Blocked, error) {
	if len(ids) == 0 {
		return nil, nil, nil
	}
	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx, `
		SELECT taskid, COALESCE(taskname,''), tasktype, COALESCE(info,''),
		       COALESCE(channel,0), COALESCE(task_user_id,0)
		FROM task WHERE taskid IN (`+ph+`)`, args...)
	if err != nil {
		return nil, nil, fmt.Errorf("查询待删任务: %w", err)
	}
	defer rs.Close()

	found := make(map[int64]deletableRow, len(ids))
	for rs.Next() {
		var r deletableRow
		if err := rs.Scan(&r.id, &r.name, &r.taskType, &r.info, &r.channel, &r.owner); err != nil {
			return nil, nil, err
		}
		found[r.id] = r
	}
	if err := rs.Err(); err != nil {
		return nil, nil, err
	}

	var ok []deletableRow
	var blocked []Blocked
	for _, id := range ids {
		r, exists := found[id]
		if !exists {
			blocked = append(blocked, Blocked{ID: id, Reason: "NOT_FOUND", Detail: "任务不存在"})
			continue
		}
		if !u.IsAdmin && r.owner != u.ID {
			// 与「不存在」用同一粒度的信息，避免变成任务归属探针
			blocked = append(blocked, Blocked{ID: id, Name: r.name,
				Reason: "NOT_OWNER", Detail: "只能删除自己创建的任务"})
			continue
		}
		if !isDeletableType(r.taskType) {
			blocked = append(blocked, Blocked{ID: id, Name: r.name,
				Reason: "NOT_DELETABLE_TYPE",
				Detail: fmt.Sprintf("任务类型 %d 不能从这里删除", r.taskType)})
			continue
		}
		if r.info != "" {
			blocked = append(blocked, Blocked{ID: id, Name: r.name,
				Reason: "IS_SCHEME", Detail: "这是作息方案下的任务，请到作息方案里处理"})
			continue
		}
		if r.channel != 0 {
			blocked = append(blocked, Blocked{ID: id, Name: r.name,
				Reason: "HAS_CHANNEL", Detail: "编码通道非 0 的任务不能从这里删除"})
			continue
		}
		ok = append(ok, r)
	}
	return ok, blocked, nil
}

func isDeletableType(t int) bool {
	return t == TypeFile || t == TypeFileAlt || t == TypeSchedule
}

// Preview 只读地展示删除影响面。
func (s *Service) Preview(ctx context.Context, u *auth.User, ids []int64) (*PreviewResult, error) {
	ok, blocked, err := s.gate(ctx, u, ids)
	if err != nil {
		return nil, err
	}
	out := &PreviewResult{Deletable: []PreviewItem{}, Blocked: blocked}
	if out.Blocked == nil {
		out.Blocked = []Blocked{}
	}
	for _, r := range ok {
		it := PreviewItem{TaskID: r.id, TaskName: r.name}
		if err := s.fillImpact(ctx, r.id, &it.Impact); err != nil {
			return nil, err
		}
		out.Deletable = append(out.Deletable, it)
	}
	return out, nil
}

func (s *Service) fillImpact(ctx context.Context, id int64, imp *DeleteImpact) error {
	counts := []struct {
		q   string
		dst *int
	}{
		{`SELECT COUNT(*) FROM mediaoftask WHERE taskid = ?`, &imp.Media},
		{`SELECT COUNT(*) FROM terminaloftask WHERE taskid = ?`, &imp.Terminals},
		{`SELECT COUNT(*) FROM terminalkeymaptask WHERE taskid = ?`, &imp.ShortcutKeys},
		{`SELECT COUNT(*) FROM offlinetaskofterminal WHERE taskid = ?`, &imp.OfflineTasks},
		{`SELECT COUNT(*) FROM offlinemediaofterminal WHERE taskid = ?`, &imp.OfflineMedia},
	}
	for _, c := range counts {
		if err := s.db.QueryRowContext(ctx, c.q, id).Scan(c.dst); err != nil {
			return fmt.Errorf("统计删除影响: %w", err)
		}
	}
	// 子任务：功放（9）与 LED（30，兼容 24），都由子任务的 sec_task_id 指回主任务
	for _, spec := range []struct {
		types []int
		dst   *int64
	}{{[]int{TypePowerAmp}, &imp.PowerTaskID}, {ledSubTypes, &imp.LEDTaskID}} {
		ph, targs := placeholders64(spec.types)
		var sub sql.NullInt64
		if err := s.db.QueryRowContext(ctx,
			`SELECT taskid FROM task WHERE sec_task_id = ? AND tasktype IN (`+ph+`) LIMIT 1`,
			append([]interface{}{id}, targs...)...).Scan(&sub); err != nil && err != sql.ErrNoRows {
			return fmt.Errorf("查询子任务: %w", err)
		}
		*spec.dst = sub.Int64
	}
	// 其余类型的关联任务：不删，但要如实计数
	subPH, subArgs := placeholders64(append([]int{TypePowerAmp}, ledSubTypes...))
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE sec_task_id = ? AND tasktype NOT IN (`+subPH+`)`,
		append([]interface{}{id}, subArgs...)...).Scan(&imp.OtherLinked); err != nil {
		return fmt.Errorf("统计其他关联任务: %w", err)
	}
	return nil
}

// Delete 删除任务。整个过程在单个事务内完成。
func (s *Service) Delete(ctx context.Context, u *auth.User, ids []int64) (*DeleteResult, error) {
	ok, blocked, err := s.gate(ctx, u, ids)
	if err != nil {
		return nil, err
	}
	out := &DeleteResult{Deleted: []int64{}, DeletedSubs: []int64{}, Blocked: blocked}
	if out.Blocked == nil {
		out.Blocked = []Blocked{}
	}
	if len(ok) == 0 {
		return out, nil
	}

	main := make([]int64, 0, len(ok))
	for _, r := range ok {
		main = append(main, r.id)
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	subs, err := s.purgeTasks(ctx, tx, main)
	if err != nil {
		return nil, err
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}

	out.Deleted = main
	out.DeletedSubs = subs
	return out, nil
}

// purgeTasks 在事务内彻底删除给定任务及其子任务的全部痕迹。
//
// 相对旧版补齐的部分：
//   - D-119 旧版 DELETE FROM terminalkey WHERE id IN (SELECT keyid FROM terminalkeymap
//     WHERE terminalid IN ($taskid)) —— **把 taskid 当 terminalid 用**，
//     会误删无关终端的快捷键。这里改走 terminalkeymaptask.taskid，语义才对得上。
//   - D-120 旧版只在 prepower > 0 时清功放子任务；主任务的 prepower 被改成 0 之后再删，
//     子任务就永久残留。这里直接按 sec_task_id + tasktype 找，不依赖 prepower。
//   - D-121 旧版完全不清 offlinetaskofterminal / offlinemediaofterminal。
//   - D-122 旧版用 if(!mysqli_error($con)) 判断是否 COMMIT，而 mysqli_error 只反映
//     **最后一条**语句 —— 中间失败照样提交。这里靠 error 传播 + defer Rollback。
//
// 返回被连带删除的子任务 id。
func (s *Service) purgeTasks(ctx context.Context, tx *sql.Tx, main []int64) ([]int64, error) {
	if len(main) == 0 {
		return []int64{}, nil
	}
	mainPH, mainArgs := placeholders(main)

	// 1) 找出功放（9）与 LED（30，兼容 24）子任务
	typePH, typeArgs := placeholders64(append([]int{TypePowerAmp}, ledSubTypes...))
	subArgs := append(append([]interface{}{}, mainArgs...), typeArgs...)
	rs, err := tx.QueryContext(ctx,
		`SELECT taskid FROM task WHERE sec_task_id IN (`+mainPH+`) AND tasktype IN (`+typePH+`)`, subArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询子任务: %w", err)
	}
	subs := []int64{}
	for rs.Next() {
		var id int64
		if err := rs.Scan(&id); err != nil {
			rs.Close()
			return nil, err
		}
		subs = append(subs, id)
	}
	rs.Close()
	if err := rs.Err(); err != nil {
		return nil, err
	}

	all := append(append([]int64{}, main...), subs...)
	allPH, allArgs := placeholders(all)

	// 2) 虚拟媒体与字幕行，必须**在删 mediaoftask 之前**清 ——
	//    它们只能通过 mediaoftask 找到，先删了关系就再也定位不到（N-22）。
	//
	//    ⚠ 只清 typeid='tts' 且 sample 指回这批任务的行。
	//      普通文件广播的 mediaoftask 指向的是真实的 mp3 媒体，
	//      那些是媒体库里的资产，删任务绝不能顺手删掉它们。
	//      typeid='tts' 的行是建任务时顺带造出来的虚拟行（文字语音与 LED 字幕都用它），
	//      每条的 sample 就是它所属的任务 id，跟着任务一起消失才对。
	for _, stmt := range []string{
		`DELETE FROM ledsentence WHERE mediaid IN (
		   SELECT id FROM (SELECT id FROM media
		     WHERE typeid = 'tts' AND sample IN (` + allPH + `)) AS m)`,
		`DELETE FROM ttssentence WHERE sentenceid IN (
		   SELECT id FROM (SELECT id FROM media
		     WHERE typeid = 'tts' AND sample IN (` + allPH + `)) AS m)`,
		`DELETE FROM media WHERE typeid = 'tts' AND sample IN (` + allPH + `)`,
	} {
		if _, err := tx.ExecContext(ctx, stmt, allArgs...); err != nil {
			return nil, fmt.Errorf("清理任务的虚拟媒体: %w", err)
		}
	}

	// 3) 关联表。主任务与子任务共享 terminaloftask / mediaoftask，一并清。
	for _, stmt := range []string{
		`DELETE FROM terminaloftask WHERE taskid IN (` + allPH + `)`,
		`DELETE FROM mediaoftask WHERE taskid IN (` + allPH + `)`,
		`DELETE FROM ledoftask WHERE taskid IN (` + allPH + `)`,
		`DELETE FROM terminalkeymaptask WHERE taskid IN (` + allPH + `)`,
		`DELETE FROM offlinetaskofterminal WHERE taskid IN (` + allPH + `)`,
		`DELETE FROM offlinemediaofterminal WHERE taskid IN (` + allPH + `)`,
	} {
		if _, err := tx.ExecContext(ctx, stmt, allArgs...); err != nil {
			return nil, fmt.Errorf("清理任务关联数据: %w", err)
		}
	}

	// 4) 任务本体。可删性已在 gate 里判过，这里不再重复带条件 ——
	//    再带一次条件正是旧版关联数据与本体不同步的根源。
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM task WHERE taskid IN (`+allPH+`)`, allArgs...); err != nil {
		return nil, fmt.Errorf("删除任务: %w", err)
	}
	return subs, nil
}
