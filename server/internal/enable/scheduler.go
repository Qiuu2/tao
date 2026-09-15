package enable

// 启用计划的执行器。
//
// # 它做什么
//
// 启用管理里一条计划说的是「到了某个时刻，把这一批任务按各自的安排启用或停用」。
// 现场要求的完整行为是两段：
//
//	开始时刻的**前 10 秒**  → 把每条任务的 projectstate 置成计划里写的值，并给
//	                          audioserver 发指令
//	结束日期 / 结束时间到   → 把它们**恢复成应用前的状态**，同样发指令
//
// 「提前 10 秒」是现场定的（最早说的是 8 秒，后来改的）：任务是按整秒触发的，
// 状态得在播之前就位，卡在同一秒上改，后台可能已经读过那一行了。
//
// # 为什么要有 enable_run 这张表
//
// 「恢复成原来的」必须先知道原来是什么。enabletask 那一行里没有这个位置
// （taskid / enstate 是两串逗号分隔值），所以每条任务应用时把它当时的
// projectstate 记进 enable_run，结束时原样写回去。
// 没有它就只能瞎猜「恢复成启用」—— 对本来就停用的任务是错的。
//
// # flag 就是「已执行」的标记
//
// 现场确认过这一列的约定：**默认 0，执行完置 1，置 1 之后不再判断**。
// 旧版 PHP 也只在新增/修改时写 0、从不写 1（do.php:2394 / 2475 / 2565 / 2656），
// 把它置 1 的正是后台 audioserver。
//
// 所以这里按同一条约定办：
//
//	查的是 flag = 0 的计划；**置好状态、给 audioserver 发完消息之后**，
//	才 UPDATE enabletask SET flag = 1。
//
// 这也顺带解决了与 audioserver 共存的问题 —— 谁先到点谁置 flag，
// 另一边下一轮就看不到这条计划了。
//
// ⚠ 执行失败**不置 flag**：置了又没执行成，这条计划就永远不会再被看一眼。
//
// 再加一道保险：真正的 UPDATE 带着 `AND projectstate <> 目标值`，
// 已经是目标值就不写、也不发指令。
//
// 「到结束时间恢复」是 audioserver 一定没有的（enddate / endtime 是后加的列），
// 那一半由这里独占，而且它认的是 enable_run 里登记过的行，与 flag 无关 ——
// flag 置 1 之后恢复照样会发生。
//
// 真要完全让给 audioserver，配置里 enable.scheduler: false 可以整个关掉
// （代价是没人做恢复那一半）。
//
// # 补跑窗口（默认不限制）
//
// flag = 0 的计划就是**还没执行过**的，哪怕开始时刻已经过去很久，
// 那也是「该执行还没执行」，不是「重复执行」—— 所以默认不设窗口。
//
// 要是现场库里躺着一批 flag = 0 的陈年计划、又不希望它们在重启时被翻出来跑，
// 把 enable.catch_up 配成一个时长即可（比如 10m），只跑开始时刻在这之内的。

import (
	"context"
	"database/sql"
	"fmt"
	"log"
	"strings"
	"time"

	"htweb/internal/notify"
)

// 默认参数。都留了配置项，改这里等于改默认值。
const (
	// DefaultLead 提前量：开始时刻前这么久就应用。现场定的 10 秒（最早说的是 8 秒）。
	DefaultLead = 10 * time.Second
	// DefaultTick 两次检查之间的间隔。
	//
	// 1 秒是为了「提前 8 秒」这个要求本身 —— 间隔比它大，误差就压不到秒级。
	// 代价可以接受：一次 tick 就是对 enabletask（现网几行）和 enable_run
	// 的两条带索引查询。
	DefaultTick = time.Second
	// DefaultCatchUp 补跑窗口，见文件头。0 = 不限制。
	//
	// 默认不限制：`flag` 就是「已执行」的权威标记（现场确认：默认 0，
	// 执行后置 1，置 1 之后不再判断），flag = 0 的计划就是**还没执行过**的，
	// 哪怕开始时刻已经过去很久，那也是「该执行还没执行」，不是「重复执行」。
	DefaultCatchUp = 0
)

// Scheduler 是这个执行器本体。
type Scheduler struct {
	db     *sql.DB
	notify *notify.Notifier

	lead    time.Duration
	tick    time.Duration
	catchUp time.Duration

	// now 可替换，测试里用来把时间拨到任意一刻。
	now func() time.Time
}

// SchedulerOption 让调用方按需改默认值。
type SchedulerOption func(*Scheduler)

func WithLead(d time.Duration) SchedulerOption    { return func(s *Scheduler) { s.lead = d } }
func WithTick(d time.Duration) SchedulerOption    { return func(s *Scheduler) { s.tick = d } }
func WithCatchUp(d time.Duration) SchedulerOption { return func(s *Scheduler) { s.catchUp = d } }
func WithClock(f func() time.Time) SchedulerOption {
	return func(s *Scheduler) { s.now = f }
}

func NewScheduler(db *sql.DB, n *notify.Notifier, opts ...SchedulerOption) *Scheduler {
	s := &Scheduler{
		db: db, notify: n,
		lead: DefaultLead, tick: DefaultTick, catchUp: DefaultCatchUp,
		now: time.Now,
	}
	for _, o := range opts {
		o(s)
	}
	return s
}

// Start 起一个后台协程一直跑，直到 ctx 取消。
func (s *Scheduler) Start(ctx context.Context) {
	if s == nil || s.db == nil {
		return
	}
	go func() {
		t := time.NewTicker(s.tick)
		defer t.Stop()
		for {
			select {
			case <-ctx.Done():
				return
			case <-t.C:
				err := s.Run(ctx)
				if err == nil {
					continue
				}
				// 表还没建：说清楚跑哪个脚本，然后停掉自己。
				// 每秒刷一条错会把日志冲垮，比功能不可用更难查。
				if missingTable(err) {
					log.Printf("启用计划执行器已停止：库里没有 enable_run 表。"+
						"请用有 DDL 权限的账号执行一次 db/enable_tables.sql，再重启 htweb。（%v）", err)
					return
				}
				// 别的失败不该让协程退出：下一秒还会再试。
				log.Printf("启用计划执行器: %v", err)
			}
		}
	}()
}

// Run 跑一轮：先应用到点的，再恢复到结束时间的。
// 导出是为了测试里能一步步驱动，不用等 ticker。
func (s *Scheduler) Run(ctx context.Context) error {
	if err := s.applyDue(ctx); err != nil {
		return err
	}
	return s.restoreDue(ctx)
}

// missingTable 判断这次失败是不是「enable_run 还没建」。
//
// 这张表得管理员拿有 DDL 权限的账号手工跑一次脚本（htweb 的运行账号没有 DDL），
// 所以「程序升了、脚本忘了跑」是必然会发生的。每秒报一次错会把 htweb.log 刷爆，
// 真正有用的日志全被冲走 —— 这比功能不可用更难查。
// 认出来之后说一句人话、把自己停掉，等管理员跑完脚本重启。
func missingTable(err error) bool {
	return err != nil && strings.Contains(err.Error(), "enable_run")
}

// planRow 是一条到点待应用的计划。
type planRow struct {
	id      int64
	start   time.Time
	taskIDs []int64
	actions []int
}

// applyDue 应用「开始时刻已到（含提前量）且还在补跑窗口内」的计划。
func (s *Scheduler) applyDue(ctx context.Context) error {
	now := s.now()
	// 条件写成时间区间而不是 BETWEEN 两个表达式，是为了让 MySQL 能用上
	// startdate 的索引形态；两端都在 Go 这边算好再传进去。
	// 补跑窗口的下沿。catchUp = 0 表示不限制 —— 用一个足够早的时间当下沿，
	// 免得为这一种情况再拼一条 SQL。
	lo := time.Date(1970, 1, 1, 0, 0, 0, 0, time.Local)
	if s.catchUp > 0 {
		lo = now.Add(-s.catchUp)
	}
	hi := now.Add(s.lead) // 提前量：现在 + 8 秒 ≥ 开始时刻，就该动手了

	rows, err := s.db.QueryContext(ctx, `
		SELECT CAST(id AS UNSIGNED), COALESCE(CAST(startdate AS CHAR),''),
		       COALESCE(CAST(starttime AS CHAR),''), COALESCE(taskid,''), COALESCE(enstate,'0')
		FROM enabletask
		WHERE COALESCE(flag,0) = 0
		  AND startdate IS NOT NULL
		  AND TIMESTAMP(startdate, COALESCE(starttime,'00:00:00')) BETWEEN ? AND ?`,
		lo.Format("2006-01-02 15:04:05"), hi.Format("2006-01-02 15:04:05"))
	if err != nil {
		return fmt.Errorf("查询待执行的启用计划: %w", err)
	}
	defer rows.Close()

	var due []planRow
	for rows.Next() {
		var id int64
		var sd, st, taskids, enstate string
		if err := rows.Scan(&id, &sd, &st, &taskids, &enstate); err != nil {
			return err
		}
		start, ok := parseMoment(sd, st)
		if !ok {
			continue
		}
		p := planRow{id: id, start: start}
		for _, pair := range parseRow(taskids, enstate) {
			p.taskIDs = append(p.taskIDs, pair.id)
			p.actions = append(p.actions, pair.action)
		}
		if len(p.taskIDs) > 0 {
			due = append(due, p)
		}
	}
	if err := rows.Err(); err != nil {
		return err
	}
	for _, p := range due {
		// ⚠ 顺序是定死的：置状态 → 给 audioserver 发消息 → 再置 flag。
		//   applyPlan 里把前两件做完才返回，这里才动 flag。
		//   反过来先置 flag 的话，中间任何一步失败，这条计划都永远不会再被看一眼。
		if err := s.applyPlan(ctx, p); err != nil {
			// 执行失败就**不**置 flag：下一秒还会再试。
			log.Printf("启用计划 %d 执行失败: %v", p.id, err)
			continue
		}
		if _, err := s.db.ExecContext(ctx,
			`UPDATE enabletask SET flag = 1 WHERE id = ? AND COALESCE(flag,0) = 0`, p.id); err != nil {
			log.Printf("启用计划 %d 置 flag 失败: %v", p.id, err)
		}
	}
	return nil
}

// applyPlan 应用一条计划里的每条任务。
//
// ⚠ 逐条处理而不是一条 UPDATE 批量改：每条任务的目标状态可以不一样
// （同一条计划里有的启用、有的停用），而且每条都要把**它自己**应用前的状态
// 记下来，将来好恢复。
func (s *Scheduler) applyPlan(ctx context.Context, p planRow) error {
	for i, taskID := range p.taskIDs {
		want := p.actions[i]
		// 先占住 enable_run 这一行：唯一键 (plan_id, taskid) 挡住重复应用。
		// 抢不到就说明这条已经处理过了 —— 可能是上一秒的自己，
		// 也可能是另一个 htweb 实例。
		res, err := s.db.ExecContext(ctx, `
			INSERT IGNORE INTO enable_run (plan_id, taskid, want_state, prev_state, applied_at)
			SELECT ?, ?, ?, COALESCE(projectstate,0), NOW() FROM task WHERE taskid = ?`,
			p.id, taskID, want, taskID)
		if err != nil {
			return fmt.Errorf("登记启用计划执行: %w", err)
		}
		n, _ := res.RowsAffected()
		if n == 0 {
			continue // 已经应用过，或者这条任务已经被删了
		}

		// 真正改状态。已经是目标值就不写、也不发指令 ——
		// 多半是 audioserver 先一步做了（见文件头）。
		upd, err := s.db.ExecContext(ctx, `
			UPDATE task SET projectstate = ?
			WHERE (taskid = ? OR sec_task_id = ?) AND COALESCE(projectstate,0) <> ?`,
			want, taskID, taskID, want)
		if err != nil {
			return fmt.Errorf("置任务启停状态: %w", err)
		}
		changed, _ := upd.RowsAffected()
		if changed == 0 {
			continue
		}
		// 状态已经写进库了，接着给 audioserver 发消息。
		// 两件都做完，外层才会把这条计划的 flag 置成 1。
		s.tell(ctx, taskID, want)
		log.Printf("启用计划 %d：任务 %d 置为 %s", p.id, taskID, stateWord(want))
	}
	return nil
}

// restoreDue 把结束时刻已到的计划恢复成应用前的状态。
func (s *Scheduler) restoreDue(ctx context.Context) error {
	now := s.now().Format("2006-01-02 15:04:05")
	// 只认 enable_run 里真的登记过、还没恢复、且所属计划已经有结束时刻的行。
	// 没填结束日期时间的计划永远不恢复 —— 那是「一直保持这个状态」的意思。
	rows, err := s.db.QueryContext(ctx, `
		SELECT r.id, r.plan_id, r.taskid, r.prev_state
		FROM enable_run r
		JOIN enabletask e ON CAST(e.id AS UNSIGNED) = r.plan_id
		WHERE r.restored_at IS NULL
		  AND e.enddate IS NOT NULL AND e.endtime IS NOT NULL
		  AND TIMESTAMP(e.enddate, e.endtime) <= ?`, now)
	if err != nil {
		return fmt.Errorf("查询待恢复的启用计划: %w", err)
	}
	defer rows.Close()

	type todo struct {
		runID, planID, taskID int64
		prev                  int
	}
	var list []todo
	for rows.Next() {
		var t todo
		if err := rows.Scan(&t.runID, &t.planID, &t.taskID, &t.prev); err != nil {
			return err
		}
		list = append(list, t)
	}
	if err := rows.Err(); err != nil {
		return err
	}

	for _, t := range list {
		// 先标记再改状态：标记失败就整条跳过，宁可不恢复也不要反复恢复
		// （反复恢复会把人手工改过的状态一遍遍按回去）。
		res, err := s.db.ExecContext(ctx,
			`UPDATE enable_run SET restored_at = NOW() WHERE id = ? AND restored_at IS NULL`, t.runID)
		if err != nil {
			return fmt.Errorf("标记启用计划已恢复: %w", err)
		}
		if n, _ := res.RowsAffected(); n == 0 {
			continue
		}
		upd, err := s.db.ExecContext(ctx, `
			UPDATE task SET projectstate = ?
			WHERE (taskid = ? OR sec_task_id = ?) AND COALESCE(projectstate,0) <> ?`,
			t.prev, t.taskID, t.taskID, t.prev)
		if err != nil {
			return fmt.Errorf("恢复任务启停状态: %w", err)
		}
		if changed, _ := upd.RowsAffected(); changed == 0 {
			continue
		}
		s.tell(ctx, t.taskID, t.prev)
		log.Printf("启用计划 %d：任务 %d 恢复为 %s", t.planID, t.taskID, stateWord(t.prev))
	}
	return nil
}

// tell 给 audioserver 发指令。
//
// ⚠ 这套协议里**没有**「某条任务被启用了」这个独立指令，只有：
//
//	project?state=1|2&name=<方案名>   作息方案启用 / 停用（界面上的启停走的就是它）
//	task?state=5&id=&volume=          任务被修改了，让后台重读这一行
//	task?state=2&id=&type=            停止正在播的那条
//
// 所以这里按任务类型分开发，与界面上手工启停发的是同一批报文：
//
//   - 作息条目（tasktype 1/15 且 info 非空）→ project?state=1|2&name=<info>
//   - 其它任务 → task?state=5（让它重读 projectstate）；停用时再补一条
//     task?state=2 把正在播的停掉，免得「已停用但还在响」
func (s *Scheduler) tell(ctx context.Context, taskID int64, want int) {
	if s.notify == nil {
		return
	}
	var tasktype int
	var info string
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(tasktype,0), COALESCE(info,'') FROM task WHERE taskid = ?`, taskID).
		Scan(&tasktype, &info)
	if err != nil {
		log.Printf("启用计划：查任务 %d 类型失败: %v", taskID, err)
		return
	}
	if isBellItem(tasktype, info) {
		st := notify.PlanEnabled
		if want != ActionEnable {
			st = notify.PlanDisabled
		}
		s.notify.PlanChanged(ctx, st, info)
		return
	}
	refs := []notify.TaskRef{{ID: taskID, TaskType: tasktype}}
	// 先让后台重读这一行（projectstate 变了）
	s.notify.TaskSaved(ctx, notify.TaskUpdated, taskID, taskVolume(ctx, s.db, taskID))
	if want != ActionEnable {
		// 停用：正在播的要停下来，与界面上手工停用同一条
		s.notify.TaskChangedTyped(ctx, notify.TaskStop, refs)
	}
}

// isBellItem 判断这条任务是不是作息方案里的一节课。
//
// ⚠ tasktype = 15 同时属于「作息方案」和「文字语音」，靠 info 分：
// 作息条目的 info 是方案名（非空），文字语音的是空串（契约 C-38）。
func isBellItem(tasktype int, info string) bool {
	return (tasktype == 1 || tasktype == 15) && strings.TrimSpace(info) != ""
}

func taskVolume(ctx context.Context, db *sql.DB, taskID int64) int {
	var v int
	if err := db.QueryRowContext(ctx,
		`SELECT COALESCE(defaultvolume,80) FROM task WHERE taskid = ?`, taskID).Scan(&v); err != nil {
		return 80
	}
	return v
}

// parseMoment 把 "2026-01-02" + "08:00:00" 拼成本地时间。
// 任一段解析不了就返回 false —— 库里存量数据里有 0000-00-00 这种。
func parseMoment(date, clock string) (time.Time, bool) {
	date = strings.TrimSpace(date)
	clock = strings.TrimSpace(clock)
	if date == "" {
		return time.Time{}, false
	}
	if clock == "" {
		clock = "00:00:00"
	}
	t, err := time.ParseInLocation("2006-01-02 15:04:05", date+" "+clock, time.Local)
	if err != nil {
		return time.Time{}, false
	}
	return t, true
}

func stateWord(v int) string {
	if v == ActionEnable {
		return "启用"
	}
	return "停用"
}
