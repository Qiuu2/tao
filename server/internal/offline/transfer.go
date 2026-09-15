package offline

import (
	"context"
	"database/sql"
	"fmt"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/i18n"
	"htweb/internal/store"
)

// 任务传送 —— :80 的 set_offline.php。
//
// # 这一页在旧版里长什么样
//
// 左边是一棵「任务管理」树，只有两个叶子；右边是同一张表格，换数据源：
//
//	任务管理
//	 ├─ 服务器任务   set_offline.php?id=1   数据源 task       （还没下发过的）
//	 └─ 云广播任务   set_offline.php?id=2   数据源 offlinetask（已经下发过的副本）
//
// 两边的按钮不是一套：
//
//	服务器任务：空闲离线(1)  立即离线(2)
//	云广播任务：空闲离线(14) 立即离线(15) 空闲删除(4) 立即删除(5)
//	            停止离线(11) 离线播放(16) 停止离线播放(17) 删除离线音乐(18)
//
// ⚠ 早前这一页只做了「云广播任务」那一半，而且只有 3 个动作 ——
//   现场一句「服务器任务和云广播任务到哪云了」正是指这个。
//
// # 与旧版刻意不同的三处（都记在这里，别当成漏抄）
//
//  1. 旧版每个动作开头先 `LOCK TABLES … 7~9 张表 WRITE`，期间全站阻塞；
//     这里一律用事务，不锁表。
//  2. 旧版 flag=18 是**先发指令再删库**。这里反过来：先在事务里删干净、
//     提交成功之后才发指令。中途失败时旧版会让终端把文件删了、库里还留着记录，
//     而反过来最坏只是终端上多留一份文件，下一次「删除离线音乐」还能再来一遍。
//  3. 服务器任务那两个动作，旧版拿**逐条 SQL** 在三层循环里插；
//     这里批量 upsert，语义一致（见下面每一步的对照注释）。

// ==================================================================
//                    页签一：服务器任务
// ==================================================================

// ServerTaskQuery 是「服务器任务」页签的查询条件。
type ServerTaskQuery struct {
	Keyword string
	// Kind 与云广播任务页签同一套取值：bell / file / 空（全部）。
	Kind  string
	Pager store.Pager
}

// serverKindCond 与 transferKindCond 同口径，只是表别名不同。
func serverKindCond(kind string) string {
	switch kind {
	case "bell":
		return "COALESCE(t.tasktype,0) = 1"
	case "file":
		return "COALESCE(t.tasktype,0) IN (2,7)"
	default:
		// 旧版这一页写死 tasktype IN (1,2,7)。不限类型时照它来 ——
		// 别的类型（功放/语音/LED）没有媒体清单，下发过去也是空的。
		return "COALESCE(t.tasktype,0) IN (1,2,7)"
	}
}

// ListServerTasks 列出**还没有下发过**的服务器任务。
//
// 旧版 set_offline.php?id=1 的原句：
//
//	SELECT … FROM task WHERE tasktype IN (1,2,7) AND offlinestate=0 ORDER BY playtime desc
//	（非管理员再加 AND task_user_id = $userid）
//
// `offlinestate = 0` 就是「非离线」——下发过一次之后 task.offlinestate 会被写成
// 1 或 2，这条任务就从这一页消失、改到「云广播任务」页签里去了。
// 所以这两个页签合起来才是全集，互不重叠。
func (s *Service) ListServerTasks(ctx context.Context, u *auth.User, q ServerTaskQuery) (*TransferResult, error) {
	cond := &store.Cond{}
	cond.Add(serverKindCond(q.Kind))
	cond.Add("COALESCE(t.offlinestate,0) = 0")
	if !u.IsAdmin {
		cond.Add("t.task_user_id = ?", u.ID)
	}
	if q.Keyword != "" {
		cond.Add(`t.taskname LIKE ? ESCAPE '\\'`, store.EscapeLike(q.Keyword))
	}
	where := cond.Where()
	args := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM task t"+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计服务器任务: %w", err)
	}

	listArgs := append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, `
		SELECT t.taskid, COALESCE(t.taskname,''), COALESCE(t.tasktype,0), COALESCE(t.info,''),
		       COALESCE(CAST(t.startdate AS CHAR),''), COALESCE(CAST(t.enddate AS CHAR),''),
		       COALESCE(CAST(t.playtime AS CHAR),''), COALESCE(t.exemodel,''),
		       COALESCE(t.timelength,0), COALESCE(t.timelengthtype,0), COALESCE(t.projectstate,0),
		       COALESCE(t.defaultvolume,0), COALESCE(t.offlinestate,0),
		       -- 「能发给几台」= 这条任务自己的终端里**有存储容量**的那些。
		       -- 旧版 do_offline_task 就是拿这个条件去筛的：
		       --   terminalid IN (SELECT id FROM terminal WHERE totalcapacity!='0')
		       -- 容量 0 的终端存不下东西，发过去没有意义，所以这一列显示的是
		       -- 「点下去真正会收到的台数」，不是任务的终端总数。
		       (SELECT COUNT(*) FROM terminaloftask o
		         WHERE o.taskid = t.taskid
		           AND o.terminalid IN (SELECT id FROM terminal WHERE COALESCE(totalcapacity,0) <> 0)),
		       (SELECT COUNT(*) FROM mediaoftask mt WHERE mt.taskid = t.taskid)
		FROM task t`+where+`
		-- 排序照旧版这一页：ORDER BY playtime desc
		ORDER BY t.playtime DESC, t.taskid
		LIMIT ? OFFSET ?`, listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询服务器任务: %w", err)
	}
	defer rs.Close()

	items := make([]TransferTask, 0, q.Pager.PageSize)
	for rs.Next() {
		var t TransferTask
		if err := rs.Scan(&t.TaskID, &t.TaskName, &t.TaskType, &t.Info,
			&t.StartDate, &t.EndDate, &t.PlayTime, &t.ExeModel,
			&t.TimeLength, &t.TimeLengthType, &t.ProjectState,
			&t.Volume, &t.State, &t.TerminalCount, &t.MediaCount); err != nil {
			return nil, fmt.Errorf("扫描服务器任务行: %w", err)
		}
		t.TypeText = typeText(t.TaskType)
		t.StateText = TextCtx(ctx, t.State)
		t.CycleText = i18n.CycleText(ctx, t.ExeModel)
		t.LengthText = lengthText(ctx, t.TimeLengthType, t.TimeLength)
		if t.ProjectState == 0 {
			t.ProjectText = "启用"
		} else {
			t.ProjectText = "停用"
		}
		items = append(items, t)
	}
	return &TransferResult{Items: items, Total: total}, rs.Err()
}

// ServerTransfer 是「服务器任务」页签的空闲离线 / 立即离线。
//
// 对应旧版 do.php?act=do_offline_task&flag=1|2。**只勾任务、不勾终端** ——
// 终端是从任务自己的清单（terminaloftask）里取的，这是它和「音乐传输」页
// 那个「下发任务」按钮最大的区别，后者要人再挑一遍终端。
func (s *Service) ServerTransfer(ctx context.Context, u *auth.User,
	taskIDs []int64, action CloudAction) (*CloudBulkResult, error) {

	ids := dedup(taskIDs)
	if len(ids) == 0 {
		return nil, fmt.Errorf("请先勾选任务")
	}
	if len(ids) > 200 {
		return nil, fmt.Errorf("单次最多 200 条任务")
	}
	var state State
	switch action {
	case CloudIdle:
		state = StateIdle
	case CloudImmediate:
		state = StateImmediate
	default:
		return nil, fmt.Errorf("服务器任务只支持空闲离线 / 立即离线")
	}
	if err := s.assertTasks(ctx, u, ids); err != nil {
		return nil, err
	}

	// 每条任务发给哪些终端 —— 旧版那句带 totalcapacity 过滤的 SELECT。
	// area 也一并取出来：offlinetaskofterminal.area 的列默认值是带引号的脏值（BR-209），
	// 必须显式写。
	type link struct {
		task, term int64
		area       string
	}
	tph, targs := placeholders(ids)
	rs, err := s.db.QueryContext(ctx, `
		SELECT o.taskid, o.terminalid, COALESCE(o.area,'')
		  FROM terminaloftask o
		 WHERE o.taskid IN (`+tph+`)
		   AND o.terminalid IN (SELECT id FROM terminal WHERE COALESCE(totalcapacity,0) <> 0)`, targs...)
	if err != nil {
		return nil, fmt.Errorf("查询任务的云广播终端: %w", err)
	}
	var links []link
	perTask := map[int64][]int64{}
	for rs.Next() {
		var l link
		if err := rs.Scan(&l.task, &l.term, &l.area); err != nil {
			rs.Close()
			return nil, err
		}
		l.area = strings.Trim(l.area, "'")
		if l.area == "" {
			l.area = AreaAll
		}
		links = append(links, l)
		perTask[l.task] = append(perTask[l.task], l.term)
	}
	rs.Close()
	if err := rs.Err(); err != nil {
		return nil, err
	}
	if len(links) == 0 {
		return nil, fmt.Errorf(i18n.TC(ctx, "选中的任务下面没有带存储容量的终端，%s 无事可做"),
			i18n.TC(ctx, cloudActionText[action]))
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// ① task.offlinestate —— 任务从此归「云广播任务」页签管
	if _, err := tx.ExecContext(ctx,
		`UPDATE task SET offlinestate = ? WHERE taskid IN (`+tph+`)`,
		append([]interface{}{int(state)}, targs...)...); err != nil {
		return nil, fmt.Errorf("更新任务离线状态: %w", err)
	}

	// ② 重建任务副本（旧版是 DELETE 再 INSERT … SELECT，照做）
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
	copies, _ := res.RowsAffected()

	// ③ 旧关系先清零。这一步是「这次没再选上的终端」的标记位：
	//    下面只把这次选上的写成 state，剩下仍是 0 的，最后统一写成 5（立即删除）——
	//    旧版最后两句 `SET offlinestate='5' … AND offlinestate='0'` 就是干这个的，
	//    意思是「这台终端上原来有、现在任务里没有了，让它删掉」。
	for _, q := range []string{
		`UPDATE offlinemediaofterminal SET offlinestate = 0 WHERE taskid IN (` + tph + `)`,
		`UPDATE offlinetaskofterminal  SET offlinestate = 0 WHERE taskid IN (` + tph + `)`,
	} {
		if _, err := tx.ExecContext(ctx, q, targs...); err != nil {
			return nil, fmt.Errorf("重置旧的下发关系: %w", err)
		}
	}

	// ④ 任务-终端关系
	for start := 0; start < len(links); start += 500 {
		end := start + 500
		if end > len(links) {
			end = len(links)
		}
		chunk := links[start:end]
		var sb strings.Builder
		sb.WriteString(`INSERT INTO offlinetaskofterminal (taskid, terminalid, offlinestate, area) VALUES `)
		args := make([]interface{}, 0, len(chunk)*4)
		for i, l := range chunk {
			if i > 0 {
				sb.WriteByte(',')
			}
			sb.WriteString("(?,?,?,?)")
			args = append(args, l.task, l.term, int(state), l.area)
		}
		sb.WriteString(" ON DUPLICATE KEY UPDATE offlinestate = VALUES(offlinestate), area = VALUES(area)")
		if _, err := tx.ExecContext(ctx, sb.String(), args...); err != nil {
			return nil, fmt.Errorf("写入任务离线下发关系: %w", err)
		}
	}

	// ⑤ 媒体清单
	mediaRows, err := s.dispatchMediaPerTask(ctx, tx, perTask, state)
	if err != nil {
		return nil, err
	}

	// ⑥ 收尾：还留在 0 的是「任务里已经没有这台终端了」，写 5 让后台去删
	for _, q := range []string{
		`UPDATE offlinetaskofterminal  SET offlinestate = ? WHERE taskid IN (` + tph + `) AND COALESCE(offlinestate,0) = 0`,
		`UPDATE offlinemediaofterminal SET offlinestate = ? WHERE taskid IN (` + tph + `) AND COALESCE(offlinestate,0) = 0`,
	} {
		if _, err := tx.ExecContext(ctx, q,
			append([]interface{}{int(StateDeleteNow)}, targs...)...); err != nil {
			return nil, fmt.Errorf("标记已移出任务的终端: %w", err)
		}
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return &CloudBulkResult{
		Action: string(action), ActionText: i18n.TC(ctx, cloudActionText[action]),
		TerminalCount: len(perTask), TaskRows: copies, MediaRows: int64(mediaRows),
		StateText: TextCtx(ctx, int(state)),
		// 旧版只在 flag=2 时发，flag=1 不发（D-152）——后台就不知道有新的空闲传输排进来了。
		// 两个都发。
		OfflineChanged: true,
	}, nil
}

// dispatchMediaPerTask 是 dispatchTaskMedia 的「每条任务各有各的终端」版本。
//
// 「音乐传输」那边一次下发是「这批任务 × 这批终端」，终端是人挑的，全任务共用一份；
// 这一页的终端是从每条任务自己的清单里取的，两条任务的终端可以完全不同，
// 所以不能复用那一个。
func (s *Service) dispatchMediaPerTask(ctx context.Context, tx *sql.Tx,
	perTask map[int64][]int64, state State) (int, error) {

	taskIDs := make([]int64, 0, len(perTask))
	for id := range perTask {
		taskIDs = append(taskIDs, id)
	}
	if len(taskIDs) == 0 {
		return 0, nil
	}
	tph, targs := placeholders(taskIDs)
	rows, err := tx.QueryContext(ctx,
		`SELECT taskid, mediaid, COALESCE(sort,0) FROM mediaoftask
		  WHERE taskid IN (`+tph+`) ORDER BY taskid, sort, id`, targs...)
	if err != nil {
		return 0, fmt.Errorf("查询任务媒体清单: %w", err)
	}
	type item struct {
		task, media int64
		sort        int
	}
	var items []item
	mediaSet := map[int64]bool{}
	for rows.Next() {
		var it item
		if err := rows.Scan(&it.task, &it.media, &it.sort); err != nil {
			rows.Close()
			return 0, err
		}
		items = append(items, it)
		mediaSet[it.media] = true
	}
	rows.Close()
	if err := rows.Err(); err != nil {
		return 0, err
	}
	if len(items) == 0 {
		return 0, nil
	}

	// 先补齐 offlinemedia 副本 —— 没有副本终端拿不到文件
	mediaIDs := make([]int64, 0, len(mediaSet))
	for id := range mediaSet {
		mediaIDs = append(mediaIDs, id)
	}
	src, err := s.loadMedia(ctx, mediaIDs)
	if err != nil {
		return 0, err
	}
	if _, _, err := syncCopies(ctx, tx, src); err != nil {
		return 0, err
	}

	type row struct {
		media, term, task int64
		sort              int
	}
	all := make([]row, 0, len(items)*4)
	for _, it := range items {
		for _, term := range perTask[it.task] {
			all = append(all, row{it.media, term, it.task, it.sort})
		}
	}
	total := 0
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
			return 0, fmt.Errorf("写入任务媒体离线关系: %w", err)
		}
		total += len(chunk)
	}
	return total, nil
}

// assertOfflineTasks 校验一批离线任务副本存在、且非管理员只能动自己的。
//
// 旧版 set_offline.php?id=2 的非管理员分支是
// `task_user_id IN (SELECT id FROM book_admin WHERE id='$userid')`，
// 绕了一层子查询，等价于 `task_user_id = $userid`。
func (s *Service) assertOfflineTasks(ctx context.Context, u *auth.User, ids []int64) error {
	ph, args := placeholders(ids)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM offlinetask WHERE taskid IN (`+ph+`)`, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验离线任务副本: %w", err)
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
		`SELECT COUNT(*) FROM offlinetask WHERE taskid IN (`+ph+`) AND task_user_id = ?`,
		oargs...).Scan(&own); err != nil {
		return fmt.Errorf("校验离线任务归属: %w", err)
	}
	if own != len(ids) {
		return fmt.Errorf("任务清单里有不属于你的任务")
	}
	return nil
}

// healFinishedCopies 把「所有媒体行都已完成」的任务副本补成 3（离线完成）。
//
// 旧版 set_offline.php 渲染每一行时都做一次：
//
//	SELECT COUNT(DISTINCT offlinestate), offlinestate FROM offlinemediaofterminal WHERE taskid=…
//	if(count==1 && value==3) UPDATE offlinetask SET offlinestate='3'
//
// 也就是「这条任务的媒体行只剩一种状态、且那种状态是 3」。后台服务只写
// offlinemediaofterminal，不回写 offlinetask，所以不补这一下，界面上会一直停在
// 「正在立即离线」——现场会以为传输卡住了。
//
// 与旧版的差别只有范围：旧版一行一条 SQL，这里只对**当前这一页**的任务算一次。
func (s *Service) healFinishedCopies(ctx context.Context, items []TransferTask) {
	ids := make([]int64, 0, len(items))
	for _, it := range items {
		if it.State != int(StateDone) {
			ids = append(ids, it.TaskID)
		}
	}
	if len(ids) == 0 {
		return
	}
	ph, args := placeholders(ids)
	done := int(StateDone)
	res, err := s.db.ExecContext(ctx, `
		UPDATE offlinetask ot SET ot.offlinestate = ?
		 WHERE ot.taskid IN (`+ph+`)
		   AND EXISTS     (SELECT 1 FROM offlinemediaofterminal m WHERE m.taskid = ot.taskid)
		   AND NOT EXISTS (SELECT 1 FROM offlinemediaofterminal m
		                    WHERE m.taskid = ot.taskid AND COALESCE(m.offlinestate,0) <> ?)`,
		append(append([]interface{}{done}, args...), done)...)
	// 补不上不是错：这一页是只读视图，查询照常返回，下一次刷新再试。
	if err != nil || res == nil {
		return
	}
	if n, _ := res.RowsAffected(); n == 0 {
		return
	}
	// 刚补过的那些，本次响应里也一并改掉，免得界面要刷两次才对
	for i := range items {
		if items[i].State == int(StateDone) {
			continue
		}
		var still int
		err := s.db.QueryRowContext(ctx,
			`SELECT COALESCE(offlinestate,0) FROM offlinetask WHERE taskid = ?`,
			items[i].TaskID).Scan(&still)
		if err == nil && still != items[i].State {
			items[i].State = still
			items[i].StateText = TextCtx(ctx, still)
		}
	}
}

// ==================================================================
//          服务器任务行内的「终端 / 媒体」两个链接
// ==================================================================
//
// 旧版这两个链接在两个页签下指向同一个页面，只是带的 flag 不同：
//
//	displayterminal.php?flag=1|2&term_id=<taskid>
//	displaymedia.php?flag=1|2&id=<taskid>
//
// flag=2（云广播任务）读的是 offlinetaskofterminal / offlinemediaofterminal ——
// 那两个已经有了，就是 TransferDetail / TransferMedia。
// flag=1（服务器任务）读的是**源表** terminaloftask / mediaoftask，
// 因为这条任务还没下发过，离线表里一行都没有。下面这两个就是它。

// ServerTaskTerminals 列出一条**服务器任务**自己的终端清单。
//
// Capable 标出「这台有没有存储容量」—— 没容量的存不下离线文件，
// 点「空闲离线 / 立即离线」时会被跳过（旧版 do_offline_task 的
// `terminalid IN (SELECT id FROM terminal WHERE totalcapacity!='0')`）。
// 界面上照实标出来，免得人以为发给了全部终端。
func (s *Service) ServerTaskTerminals(ctx context.Context, u *auth.User, taskID int64) ([]TransferTerminal, error) {
	cond := &store.Cond{}
	cond.Add("o.taskid = ?", taskID)
	if !u.IsAdmin {
		cond.Add(`o.terminalid IN (SELECT terminalid FROM userterminal WHERE userid = ?)`, u.ID)
	}
	rs, err := s.db.QueryContext(ctx, `
		SELECT o.terminalid, t.id IS NOT NULL, COALESCE(t.terminalname,''),
		       COALESCE(tt.name,''), COALESCE(t.ip,''), COALESCE(t.netstate,0),
		       COALESCE(o.area,''), COALESCE(t.totalcapacity,0) <> 0
		  FROM terminaloftask o
		  LEFT JOIN terminal t ON t.id = o.terminalid
		  LEFT JOIN terminaltype tt ON tt.id = t.typeid`+cond.Where()+`
		 ORDER BY o.terminalid`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询任务终端清单: %w", err)
	}
	defer rs.Close()
	out := []TransferTerminal{}
	for rs.Next() {
		var t TransferTerminal
		var exists bool
		if err := rs.Scan(&t.TerminalID, &exists, &t.TerminalName, &t.TypeName,
			&t.IP, &t.NetState, &t.Area, &t.Capable); err != nil {
			return nil, err
		}
		t.TypeName = i18n.TC(ctx, t.TypeName)
		t.Deleted = !exists
		if t.Deleted {
			t.TerminalName = i18n.TC(ctx, "(终端已删除)")
		}
		// 还没下发过，所以没有传输状态 —— 显示「非离线」而不是编一个进度出来
		t.State = int(StateNone)
		t.StateText = TextCtx(ctx, t.State)
		t.Area = strings.Trim(t.Area, "'")
		out = append(out, t)
	}
	return out, rs.Err()
}

// ServerTaskMedia 列出一条**服务器任务**自己的媒体清单（mediaoftask → media）。
func (s *Service) ServerTaskMedia(ctx context.Context, u *auth.User, taskID int64) ([]TransferMediaItem, error) {
	rs, err := s.db.QueryContext(ctx, `
		SELECT mt.mediaid, COALESCE(m.name,''), COALESCE(m.size,0), COALESCE(m.typeid,''),
		       COALESCE(mt.sort,0), m.id IS NOT NULL
		  FROM mediaoftask mt
		  LEFT JOIN media m ON m.id = mt.mediaid
		 WHERE mt.taskid = ?
		 ORDER BY mt.sort, mt.id`, taskID)
	if err != nil {
		return nil, fmt.Errorf("查询任务媒体清单: %w", err)
	}
	defer rs.Close()
	out := []TransferMediaItem{}
	for rs.Next() {
		var it TransferMediaItem
		var exists bool
		if err := rs.Scan(&it.MediaID, &it.Name, &it.Size, &it.TypeID, &it.Sort, &exists); err != nil {
			return nil, err
		}
		it.Missing = !exists
		if it.Missing {
			it.Name = i18n.TC(ctx, "(媒体已删除)")
		}
		out = append(out, it)
	}
	return out, rs.Err()
}
