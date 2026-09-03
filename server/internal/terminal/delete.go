package terminal

import (
	"context"
	"database/sql"
	"fmt"

	"htweb/internal/auth"
)

// Impact 是单个终端的删除影响面。
type Impact struct {
	Tasks              int      `json:"tasks"`
	TaskNames          []string `json:"taskNames"`
	ShortcutKeys       int      `json:"shortcutKeys"`
	AlarmAreas         int      `json:"alarmAreas"`
	BoundUsers         int      `json:"boundUsers"`
	CallGroups         int      `json:"callGroups"`
	OfflineTasks       int      `json:"offlineTasks"`
	GroupWillBeDeleted bool     `json:"groupWillBeDeleted"`
	GroupName          string   `json:"groupName"`
}

type PreviewItem struct {
	ID           int64  `json:"id"`
	TerminalName string `json:"terminalname"`
	Impact       Impact `json:"impact"`
}

type PreviewResult struct {
	Deletable []PreviewItem `json:"deletable"`
	Skipped   []Skipped     `json:"skipped"`
}

// Preview 列出删除影响面。
//
// 旧版删除终端**完全不检查该终端是否正在被任务使用**（缺陷 D-95）：
// 直接把 terminaloftask 删掉，任务的终端列表悄悄变空，
// 到点了往空终端播放，现场表现为「任务没响」。
// 这里把受影响的任务列出来，交给用户确认。
func (s *Service) Preview(ctx context.Context, u *auth.User, ids []int64) (*PreviewResult, error) {
	ok, skipped, err := s.CheckBound(ctx, u, ids)
	if err != nil {
		return nil, err
	}
	out := &PreviewResult{Deletable: []PreviewItem{}, Skipped: skipped}
	if len(ok) == 0 {
		return out, nil
	}

	names, err := s.terminalNames(ctx, ok)
	if err != nil {
		return nil, err
	}
	// 哪些分区会因为成员被删光而一并消失（BR-151）
	doomed, err := s.doomedGroups(ctx, ok)
	if err != nil {
		return nil, err
	}
	groupOf, err := s.groupOf(ctx, ok)
	if err != nil {
		return nil, err
	}

	for _, id := range ok {
		imp := Impact{TaskNames: []string{}}
		if err := s.fillImpact(ctx, id, &imp); err != nil {
			return nil, err
		}
		if g, has := groupOf[id]; has {
			if name, will := doomed[g]; will {
				imp.GroupWillBeDeleted, imp.GroupName = true, name
			}
		}
		out.Deletable = append(out.Deletable, PreviewItem{
			ID: id, TerminalName: names[id], Impact: imp,
		})
	}
	return out, nil
}

func (s *Service) fillImpact(ctx context.Context, id int64, imp *Impact) error {
	// 参与的任务：terminaloftask 是任务与终端的多对多。
	// 条数单独 COUNT(DISTINCT taskid)，不能拿名字列表的长度充数 ——
	// 名字既可能重名（现网就有两条都叫「报警主机-0」这种），
	// 又被下面的 LIMIT 50 截断，两种情况都会把条数报少。
	rs, err := s.db.QueryContext(ctx, `
		SELECT DISTINCT COALESCE(t.taskname,'')
		FROM terminaloftask tot JOIN task t ON t.taskid = tot.taskid
		WHERE tot.terminalid = ? LIMIT 50`, id)
	if err != nil {
		return fmt.Errorf("查询关联任务: %w", err)
	}
	for rs.Next() {
		var n string
		if err := rs.Scan(&n); err != nil {
			rs.Close()
			return err
		}
		imp.TaskNames = append(imp.TaskNames, n)
	}
	rs.Close()
	if err := rs.Err(); err != nil {
		return err
	}

	counts := []struct {
		q   string
		arg interface{}
		dst *int
	}{
		{`SELECT COUNT(DISTINCT tot.taskid) FROM terminaloftask tot
		  JOIN task t ON t.taskid = tot.taskid WHERE tot.terminalid = ?`, id, &imp.Tasks},
		// terminalkey.terminalid 是 varchar，要按字符串比
		{`SELECT COUNT(*) FROM terminalkey WHERE terminalid = ?`, fmt.Sprintf("%d", id), &imp.ShortcutKeys},
		{`SELECT COUNT(*) FROM terminalofalarmgroup WHERE terminalid = ?`, id, &imp.AlarmAreas},
		{`SELECT COUNT(*) FROM userterminal WHERE terminalid = ?`, id, &imp.BoundUsers},
		{`SELECT COUNT(*) FROM callgroup WHERE terminalid = ?`, id, &imp.CallGroups},
		{`SELECT COUNT(*) FROM offlinetaskofterminal WHERE terminalid = ?`, id, &imp.OfflineTasks},
	}
	for _, c := range counts {
		if err := s.db.QueryRowContext(ctx, c.q, c.arg).Scan(c.dst); err != nil {
			return fmt.Errorf("统计删除影响: %w", err)
		}
	}
	return nil
}

func (s *Service) terminalNames(ctx context.Context, ids []int64) (map[int64]string, error) {
	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx,
		`SELECT id, COALESCE(terminalname,'') FROM terminal WHERE id IN (`+ph+`)`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询终端名: %w", err)
	}
	defer rs.Close()
	out := make(map[int64]string, len(ids))
	for rs.Next() {
		var id int64
		var n string
		if err := rs.Scan(&id, &n); err != nil {
			return nil, err
		}
		out[id] = n
	}
	return out, rs.Err()
}

// groupOf 取每个终端当前所属分区。
func (s *Service) groupOf(ctx context.Context, ids []int64) (map[int64]int64, error) {
	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx,
		`SELECT terminalid, groupid FROM terminalofgroup WHERE terminalid IN (`+ph+`)`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询终端分区: %w", err)
	}
	defer rs.Close()
	out := make(map[int64]int64, len(ids))
	for rs.Next() {
		var tid, gid int64
		if err := rs.Scan(&tid, &gid); err != nil {
			return nil, err
		}
		out[tid] = gid
	}
	return out, rs.Err()
}

// doomedGroups 找出「删掉这批终端后就一个成员都不剩」的分区。
//
// 旧版在循环里逐个判断，且删 terminalofgroup 时用的是当前行的 terminalid
// 而不是批量（缺陷 D-94）。这里一条 GROUP BY + HAVING 解决。
func (s *Service) doomedGroups(ctx context.Context, ids []int64) (map[int64]string, error) {
	ph, args := placeholders(ids)
	q := `
		SELECT tog.groupid, COALESCE(sps.name,'')
		FROM terminalofgroup tog
		LEFT JOIN serverplaystream sps ON sps.streamid = tog.groupid
		WHERE tog.groupid IN (SELECT groupid FROM terminalofgroup WHERE terminalid IN (` + ph + `))
		GROUP BY tog.groupid, sps.name
		HAVING SUM(CASE WHEN tog.terminalid IN (` + ph + `) THEN 0 ELSE 1 END) = 0`

	both := append(append([]interface{}{}, args...), args...)
	rs, err := s.db.QueryContext(ctx, q, both...)
	if err != nil {
		return nil, fmt.Errorf("判定空分区: %w", err)
	}
	defer rs.Close()
	out := map[int64]string{}
	for rs.Next() {
		var gid int64
		var name string
		if err := rs.Scan(&gid, &name); err != nil {
			return nil, err
		}
		out[gid] = name
	}
	return out, rs.Err()
}

// DeleteResult 是删除结果。
type DeleteResult struct {
	Deleted       []int64   `json:"deleted"`
	DeletedGroups []int64   `json:"deletedGroups"`
	DeletedCall   []int64   `json:"deletedCallGroups"`
	AffectedTasks int       `json:"affectedTasks"`
	Skipped       []Skipped `json:"skipped"`
	Notified      bool      `json:"notified"`
}

// Delete 删除终端并清理其在全系统的关联。
//
// # 相比旧版的修复
//
//   - D-89 整个删除没有事务，中途失败留下半清理状态 → 全程单事务
//   - D-90 不清理 userterminal，用户与已删终端的绑定永久残留
//   - D-91 不清理 terminalofalarmgroup，报警分区里留着已删终端
//   - D-92 只删 terminalkey 不删 terminalkeymap，映射表变孤儿
//   - D-94 分区回收在循环内逐个判断且删错条件 → 批量算一次
func (s *Service) Delete(ctx context.Context, u *auth.User, ids []int64) (*DeleteResult, error) {
	ok, skipped, err := s.CheckBound(ctx, u, ids)
	if err != nil {
		return nil, err
	}
	out := &DeleteResult{Deleted: []int64{}, DeletedGroups: []int64{},
		DeletedCall: []int64{}, Skipped: skipped}
	if len(ok) == 0 {
		return out, nil
	}

	// 先算出会被回收的分区与呼叫组，删完就查不出来了
	doomed, err := s.doomedGroups(ctx, ok)
	if err != nil {
		return nil, err
	}
	doomedCall, err := s.doomedCallGroups(ctx, ok)
	if err != nil {
		return nil, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	ph, args := placeholders(ok)
	strArgs := make([]interface{}, len(ok))
	for i, id := range ok {
		strArgs[i] = fmt.Sprintf("%d", id)
	}

	// 1) 该终端专属的类型 20 任务，连同它的快捷键映射
	affected, err := s.deleteOwnTasks(ctx, tx, ph, args)
	if err != nil {
		return nil, err
	}
	out.AffectedTasks = affected

	// 2) 离线任务状态复位（BR-154）。必须在删 offlinetaskofterminal 之前做，
	//    否则子查询就查不到这些 taskid 了。
	if _, err := tx.ExecContext(ctx, `
		UPDATE task SET offlinestate = 0
		WHERE taskid IN (SELECT taskid FROM offlinetaskofterminal WHERE terminalid IN (`+ph+`))`,
		args...); err != nil {
		return nil, fmt.Errorf("复位离线任务状态: %w", err)
	}

	// 3) 终端目录树：先删叶子再删目录
	if _, err := tx.ExecContext(ctx, `
		DELETE FROM terminaloffolder
		WHERE folderid IN (SELECT id FROM terminalfolder WHERE terminalid IN (`+ph+`))`,
		args...); err != nil {
		return nil, fmt.Errorf("清理终端目录成员: %w", err)
	}

	// 4) 按 terminalid 批量清理的表。
	//    terminalkey 的 terminalid 是 varchar(45)，单独用字符串参数。
	intTables := []string{
		"terminalfolder", "leddevice", "ledoftask", "cameramap",
		"terminalkeymap", "terminalofgroup", "terminaloftask",
		"terminalofcallgroup", "offlinemediaofterminal", "offlinetaskofterminal",
		"camerofterminal", "userterminal", "terminalofalarmgroup",
		// terminalkeymaptask 旧版只按 taskid 删，按 terminalid 那一半漏了，
		// 于是留下指向已删终端的映射行（与 D-92 同类）
		"terminalkeymaptask",
	}
	for _, t := range intTables {
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM `+t+` WHERE terminalid IN (`+ph+`)`, args...); err != nil {
			return nil, fmt.Errorf("清理 %s: %w", t, err)
		}
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminalkey WHERE terminalid IN (`+ph+`)`, strArgs...); err != nil {
		return nil, fmt.Errorf("清理 terminalkey: %w", err)
	}

	// 5) 以该终端为宿主的呼叫组，连同其成员
	if _, err := tx.ExecContext(ctx, `
		DELETE FROM terminalofcallgroup
		WHERE selectgroupid IN (SELECT id FROM callgroup WHERE terminalid IN (`+ph+`))`,
		args...); err != nil {
		return nil, fmt.Errorf("清理呼叫组成员: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM callgroup WHERE terminalid IN (`+ph+`)`, args...); err != nil {
		return nil, fmt.Errorf("清理呼叫组: %w", err)
	}

	// 6) 回收空掉的分区与呼叫组（BR-151 / BR-152）
	for gid := range doomed {
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM serverplaystream WHERE streamid = ?`, gid); err != nil {
			return nil, fmt.Errorf("回收空分区: %w", err)
		}
		out.DeletedGroups = append(out.DeletedGroups, gid)
	}
	for _, cid := range doomedCall {
		if _, err := tx.ExecContext(ctx, `DELETE FROM callgroup WHERE id = ?`, cid); err != nil {
			return nil, fmt.Errorf("回收空呼叫组: %w", err)
		}
		out.DeletedCall = append(out.DeletedCall, cid)
	}

	// 7) 终端本体
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminal WHERE id IN (`+ph+`)`, args...); err != nil {
		return nil, fmt.Errorf("删除终端: %w", err)
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	out.Deleted = ok
	return out, nil
}

// deleteOwnTasks 删掉「该终端专属」的 tasktype=20 任务。
//
// ⚠ 这类任务在 task 表里用**两个**列各存了一个终端 ID，含义不同：
//
//	task.cmd     音源终端（采集源）—— ok112 建表时写的是 $audiosource，
//	             而 $audiosource 本身就是一台采集终端的 id
//	task.cmdargs 宿主终端（按下键触发的那台）—— ok112 写的是 $_GET['terminal_id']，
//	             terminalkeymaptask.terminalid 也取的同一个值
//
// 两个列名都跟含义没关系，是旧库的字段复用。
// 以前这里只比 cmd，于是删掉**宿主**终端时找不到它的专属任务，
// 任务连同它的 terminalkeymaptask / terminaloftask / mediaoftask 全留成孤儿。
// 两个列都要比：音源没了任务放不出声，宿主没了任务没人触发，都该跟着删。
func (s *Service) deleteOwnTasks(ctx context.Context, tx *sql.Tx, ph string, args []interface{}) (int, error) {
	rs, err := tx.QueryContext(ctx,
		`SELECT taskid FROM task WHERE (cmd IN (`+ph+`) OR cmdargs IN (`+ph+`)) AND tasktype = 20`,
		append(append([]interface{}{}, args...), args...)...)
	if err != nil {
		return 0, fmt.Errorf("查询终端专属任务: %w", err)
	}
	var taskIDs []int64
	for rs.Next() {
		var id int64
		if err := rs.Scan(&id); err != nil {
			rs.Close()
			return 0, err
		}
		taskIDs = append(taskIDs, id)
	}
	rs.Close()
	if err := rs.Err(); err != nil {
		return 0, err
	}
	if len(taskIDs) == 0 {
		return 0, nil
	}

	tph, targs := placeholders(taskIDs)
	for _, t := range []string{"terminalkeymaptask", "task"} {
		col := "taskid"
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM `+t+` WHERE `+col+` IN (`+tph+`)`, targs...); err != nil {
			return 0, fmt.Errorf("清理 %s: %w", t, err)
		}
	}
	return len(taskIDs), nil
}

// doomedCallGroups 找出删掉这批终端后就没有成员的呼叫组。
func (s *Service) doomedCallGroups(ctx context.Context, ids []int64) ([]int64, error) {
	ph, args := placeholders(ids)
	q := `
		SELECT tcg.selectgroupid
		FROM terminalofcallgroup tcg
		WHERE tcg.selectgroupid IN (
		        SELECT selectgroupid FROM terminalofcallgroup WHERE terminalid IN (` + ph + `))
		GROUP BY tcg.selectgroupid
		HAVING SUM(CASE WHEN tcg.terminalid IN (` + ph + `) THEN 0 ELSE 1 END) = 0`

	both := append(append([]interface{}{}, args...), args...)
	rs, err := s.db.QueryContext(ctx, q, both...)
	if err != nil {
		return nil, fmt.Errorf("判定空呼叫组: %w", err)
	}
	defer rs.Close()
	var out []int64
	for rs.Next() {
		var id sql.NullInt64
		if err := rs.Scan(&id); err != nil {
			return nil, err
		}
		if id.Valid && id.Int64 > 0 {
			out = append(out, id.Int64)
		}
	}
	return out, rs.Err()
}
