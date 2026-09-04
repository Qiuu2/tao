// Package enable 实现启用管理（旧版 displayenablemanager.php + enableadd.php / enablemodify.php）。
//
// # 这张表管什么
//
//	enabletask (id, enstate, startdate, starttime, taskid, flag)
//
// 一条记录 = 「到了某年某月某日某时某分某秒，把这一批任务按各自的安排启用或停用」。
// 后台 C 服务扫这张表，到点执行。
//
// # ⚠ enstate 和 taskid 是**两串并列的逗号分隔值**
//
// 这一点很容易看错。旧版 addmanager.html 的「提交」按钮走的是 tijiaoselects()：
//
//	for(i=0;i<document.bellform.id.length;i++){
//	    allSel   = allSel   + "," + 这一行的 taskid;
//	    get_radio= get_radio+ "," + (这一行选了「启用」? "0" : "1");
//	}
//	window.location.href = "do.php?act=yesornoenable&get_radio="+get_radio+"&allSel="+allSel+…
//
// 落到 do.php 的 yesornoenable() 就是一条：
//
//	INSERT INTO enabletask(enstate,startdate,starttime,taskid,flag)
//	VALUES('$get_radio','$startdate','$starttime','$allSel','0')
//
// 所以 `enstate` 里存的是 "0,1,0,0,1" 这样与 `taskid` **逐项对应**的一串，
// 不是整行一个值 —— 列类型 varchar(1024) 也正是为此。
//
// # ⚠ 0 = 启用、1 = 停用
//
// 旧版列表 enableManager_form.html 里写着 `<{if $info[loop].enstate ==0}>启用<{else}>停用`，
// 表单里那个（已被注释掉的）下拉也是 `<option value="0">启用</option>`。
// 与 `task.projectstate` 同一套约定，和 `holidaytime.projectstate`（1=启用）相反。
//
// # 表结构上的另外两个坑（不改表，只在读写时绕开）
//
//	taskid 是 varchar(2048)：超长会被静默截断，而截断处很可能落在某个 id 中间 ——
//	       后台就会解析出一个别的任务。所以保存前按字节数挡住。
//	id 是 int(4) unsigned zerofill 且只有 UNIQUE KEY 没有 PRIMARY KEY：
//	       查出来是 "0001" 这种补零字符串，一律 CAST 成整数用。
//
// # flag 是什么
//
// 旧版新增时恒写 `'0'`，全站没有任何地方读它或改它（`grep flag` 只有这一处写）。
// 现网这张表是空的，无从佐证。新版跟着写 0，不做解释也不暴露给界面 ——
// 猜一个语义再显示出来，比不显示更糟。
package enable

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strconv"
	"strings"
	"time"

	"htweb/internal/store"
)

type Service struct {
	db *sql.DB
}

func New(db *sql.DB) *Service { return &Service{db: db} }

var ErrNotFound = errors.New("启用计划不存在")

// enstate 里每一项的取值。
//
// ⚠ 0 = 启用、1 = 停用，与 task.projectstate 一致，
// 与 holidaytime.projectstate（1=启用）相反。三张表两种约定，只能逐表记住。
const (
	ActionEnable  = 0 // 到点把这条任务启用
	ActionDisable = 1 // 到点把这条任务停用
)

// taskidLimit 是 enabletask.taskid 的 varchar(2048)。
// 超了 MySQL 会静默截断 —— 截断处很可能落在某个 id 中间，
// 后台解析出一个不存在的任务 id，或者更糟，解析出一个**别的**任务。
const taskidLimit = 2048

// enstateLimit 是 enabletask.enstate 的 varchar(1024)。
// 它与 taskid 逐项对应，taskid 装得下时它一般也装得下，但还是各查各的。
const enstateLimit = 1024

const maxTasks = 200

func actionText(v int) string {
	if v == ActionEnable {
		return "启用"
	}
	return "停用"
}

type Task struct {
	TaskID   int64  `json:"taskId"`
	TaskName string `json:"taskName"`
	TaskType int    `json:"tasktype"`
	TypeText string `json:"typeText"`
	Info     string `json:"info"`
	// Action 是这条任务到点要做什么：0 启用、1 停用。
	Action     int    `json:"action"`
	ActionText string `json:"actionText"`
	// Missing 表示这个 id 在 task 表里已经找不到了。
	// 旧版删任务时不清理 enabletask，所以现网很可能出现悬空 id。
	Missing bool `json:"missing"`
}

type Item struct {
	ID        int64  `json:"id"`
	StartDate string `json:"startdate"`
	StartTime string `json:"starttime"`
	Tasks     []Task `json:"tasks"`
	// EnableCount / DisableCount 是给列表上那一列做摘要用的
	EnableCount  int `json:"enableCount"`
	DisableCount int `json:"disableCount"`
	// Expired 表示计划时间已经过去。后台执行过之后旧版不会清理，
	// 所以列表里会一直堆着历史记录 —— 标出来让人知道哪些还会生效。
	Expired bool `json:"expired"`
}

type Query struct {
	Keyword string
	Pager   store.Pager
}

type ListResult struct {
	Items []Item
	Total int64
}

func (s *Service) List(ctx context.Context, q Query) (*ListResult, error) {
	cond := &store.Cond{}
	if q.Keyword != "" {
		// 旧版只允许按 startdate / starttime 搜。这两列都是日期时间，
		// 用 LIKE 匹配它们的字符串形式与旧版一致。
		kw := store.EscapeLike(q.Keyword)
		cond.Add(`(CAST(startdate AS CHAR) LIKE ? ESCAPE '\\' OR CAST(starttime AS CHAR) LIKE ? ESCAPE '\\')`, kw, kw)
	}
	where := cond.Where()
	args := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM enabletask"+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计启用计划: %w", err)
	}

	// id 是 zerofill，CAST 成无符号整数再取，免得驱动把 "0001" 当字符串扫进 int64 出错。
	// 日期时间也 CAST 成 CHAR 拿字面值（DSN 带 parseTime=true，否则会多一层时区换算）。
	listArgs := append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, `
		SELECT CAST(id AS UNSIGNED), COALESCE(enstate,'0'),
		       COALESCE(CAST(startdate AS CHAR),''), COALESCE(CAST(starttime AS CHAR),''),
		       COALESCE(taskid,'')
		FROM enabletask`+where+` ORDER BY startdate DESC, starttime DESC, id DESC
		LIMIT ? OFFSET ?`, listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询启用计划: %w", err)
	}
	defer rs.Close()

	items := make([]Item, 0, q.Pager.PageSize)
	allIDs := map[int64]bool{}
	raw := make([][]idAction, 0, q.Pager.PageSize)
	now := time.Now()

	for rs.Next() {
		var it Item
		var enstate, taskids string
		if err := rs.Scan(&it.ID, &enstate, &it.StartDate, &it.StartTime, &taskids); err != nil {
			return nil, fmt.Errorf("扫描启用计划行: %w", err)
		}
		pairs := parseRow(taskids, enstate)
		raw = append(raw, pairs)
		for _, p := range pairs {
			allIDs[p.id] = true
			if p.action == ActionEnable {
				it.EnableCount++
			} else {
				it.DisableCount++
			}
		}
		if t, err := time.ParseInLocation("2006-01-02 15:04:05",
			it.StartDate+" "+it.StartTime, time.Local); err == nil {
			it.Expired = t.Before(now)
		}
		it.Tasks = []Task{}
		items = append(items, it)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}

	names, err := s.taskNames(ctx, allIDs)
	if err != nil {
		return nil, err
	}
	for i := range items {
		items[i].Tasks = fillTasks(raw[i], names)
	}
	return &ListResult{Items: items, Total: total}, nil
}

// idAction 是 taskid / enstate 两串并列值拆开之后的一对。
type idAction struct {
	id     int64
	action int
}

// parseRow 把 "70001,70002" + "0,1" 拆成逐条的 (任务, 动作)。
//
// 兼容两种历史写法：
//   - enstate 与 taskid 等长 → 逐项对应（旧版「提交」按钮写出来的）
//   - enstate 只有一项       → 整行一个值，套用到所有任务
//     （旧版那条已被注释掉的表单路径 addenable 写出来的）
//
// 解析不了的片段直接丢掉 —— 这两列都是 varchar，旧数据里什么都可能有。
// 缺失的动作按「停用」处理：宁可少放一次广播，也不要把解析不了的值当成「启用」。
func parseRow(taskids, enstate string) []idAction {
	ids := []int64{}
	seen := map[int64]bool{}
	for _, p := range strings.Split(taskids, ",") {
		v, err := strconv.ParseInt(strings.TrimSpace(p), 10, 64)
		if err != nil || v <= 0 || seen[v] {
			continue
		}
		seen[v] = true
		ids = append(ids, v)
	}
	states := []int{}
	for _, p := range strings.Split(enstate, ",") {
		p = strings.TrimSpace(p)
		if p == "" {
			continue
		}
		if p == "0" {
			states = append(states, ActionEnable)
		} else {
			states = append(states, ActionDisable)
		}
	}
	out := make([]idAction, 0, len(ids))
	for i, id := range ids {
		a := ActionDisable
		switch {
		case len(states) == len(ids):
			a = states[i]
		case len(states) == 1:
			a = states[0]
		case i < len(states):
			a = states[i]
		}
		out = append(out, idAction{id: id, action: a})
	}
	return out
}

func fillTasks(pairs []idAction, names map[int64]Task) []Task {
	out := make([]Task, 0, len(pairs))
	for _, p := range pairs {
		t, ok := names[p.id]
		if !ok {
			t = Task{TaskID: p.id, TaskName: "(任务已删除)", Missing: true}
		}
		t.Action = p.action
		t.ActionText = actionText(p.action)
		out = append(out, t)
	}
	return out
}

// taskNames 一次把用到的任务名全查回来，不做 N+1。
func (s *Service) taskNames(ctx context.Context, ids map[int64]bool) (map[int64]Task, error) {
	out := map[int64]Task{}
	if len(ids) == 0 {
		return out, nil
	}
	list := make([]int64, 0, len(ids))
	for id := range ids {
		list = append(list, id)
	}
	ph, args := placeholders(list)
	rs, err := s.db.QueryContext(ctx,
		`SELECT taskid, COALESCE(taskname,''), COALESCE(tasktype,0), COALESCE(info,'')
		 FROM task WHERE taskid IN (`+ph+`)`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询任务名: %w", err)
	}
	defer rs.Close()
	for rs.Next() {
		var t Task
		if err := rs.Scan(&t.TaskID, &t.TaskName, &t.TaskType, &t.Info); err != nil {
			return nil, err
		}
		t.TypeText = typeText(t.TaskType)
		out[t.TaskID] = t
	}
	return out, rs.Err()
}

func placeholders(ids []int64) (string, []interface{}) {
	if len(ids) == 0 {
		return "NULL", nil
	}
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		args[i] = id
	}
	return strings.TrimSuffix(strings.Repeat("?,", len(ids)), ","), args
}

func (s *Service) Get(ctx context.Context, id int64) (*Item, error) {
	var it Item
	var enstate, taskids string
	err := s.db.QueryRowContext(ctx, `
		SELECT CAST(id AS UNSIGNED), COALESCE(enstate,'0'),
		       COALESCE(CAST(startdate AS CHAR),''), COALESCE(CAST(starttime AS CHAR),''),
		       COALESCE(taskid,'')
		FROM enabletask WHERE id = ? LIMIT 1`, id).
		Scan(&it.ID, &enstate, &it.StartDate, &it.StartTime, &taskids)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询启用计划: %w", err)
	}
	pairs := parseRow(taskids, enstate)
	ids := map[int64]bool{}
	for _, p := range pairs {
		ids[p.id] = true
		if p.action == ActionEnable {
			it.EnableCount++
		} else {
			it.DisableCount++
		}
	}
	names, err := s.taskNames(ctx, ids)
	if err != nil {
		return nil, err
	}
	it.Tasks = fillTasks(pairs, names)
	if t, err := time.ParseInLocation("2006-01-02 15:04:05",
		it.StartDate+" "+it.StartTime, time.Local); err == nil {
		it.Expired = t.Before(time.Now())
	}
	return &it, nil
}

// ---------- 新建 / 修改 ----------

// Input 是「一个时间点 + 一串任务，每条任务各自启用或停用」——
// 与旧版 tijiaoselects() 提交的 (allSel, get_radio) 两串值一一对应。
type Input struct {
	StartDate string
	StartTime string
	Tasks     []TaskAction
}

type TaskAction struct {
	TaskID int64 `json:"taskId"`
	Action int   `json:"action"`
}

func (s *Service) validate(ctx context.Context, in *Input) error {
	in.StartDate = strings.TrimSpace(in.StartDate)
	in.StartTime = strings.TrimSpace(in.StartTime)
	if _, err := time.Parse("2006-01-02", in.StartDate); err != nil {
		return fmt.Errorf("开始日期格式不正确，必须是 YYYY-MM-DD")
	}
	if _, err := time.Parse("15:04:05", in.StartTime); err != nil {
		return fmt.Errorf("开始时间格式不正确，必须是 HH:MM:SS")
	}
	if len(in.Tasks) == 0 {
		return fmt.Errorf("请至少选择一条任务")
	}
	if len(in.Tasks) > maxTasks {
		return fmt.Errorf("一个启用计划最多绑定 %d 条任务", maxTasks)
	}
	seen := map[int64]bool{}
	ids := make([]int64, 0, len(in.Tasks))
	for i := range in.Tasks {
		t := &in.Tasks[i]
		if t.TaskID <= 0 {
			return fmt.Errorf("任务列表里有非法的任务 ID")
		}
		if seen[t.TaskID] {
			return fmt.Errorf("任务列表里有重复的任务")
		}
		seen[t.TaskID] = true
		if t.Action != ActionEnable && t.Action != ActionDisable {
			return fmt.Errorf("每条任务只能设为启用(0)或停用(1)")
		}
		ids = append(ids, t.TaskID)
	}
	// 逐条确认任务存在。旧版一条都不查：任务删掉之后计划还在，
	// 到点后台去启用一个不存在的任务。
	ph, args := placeholders(ids)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE taskid IN (`+ph+`)`, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验任务: %w", err)
	}
	if n != len(ids) {
		return fmt.Errorf("任务列表里有已不存在的任务，请重新选择")
	}
	// 拼出来的两串都必须装得下各自的 varchar。
	// 截断会落在某个 id 中间，后台解析出一个**别的**任务 —— 这比报错危险得多。
	taskCol, stateCol := serialize(in.Tasks)
	if len(taskCol) > taskidLimit {
		return fmt.Errorf("选中的任务过多，任务 ID 列表超出 %d 字节上限，请拆成多条计划", taskidLimit)
	}
	if len(stateCol) > enstateLimit {
		return fmt.Errorf("选中的任务过多，启停标志列表超出 %d 字节上限，请拆成多条计划", enstateLimit)
	}
	return nil
}

// serialize 把逐条的 (任务, 动作) 拼成 taskid / enstate 两串并列值，
// 顺序一一对应 —— 这正是旧版 allSel / get_radio 的写法。
func serialize(list []TaskAction) (string, string) {
	ids := make([]string, len(list))
	states := make([]string, len(list))
	for i, t := range list {
		ids[i] = strconv.FormatInt(t.TaskID, 10)
		states[i] = strconv.Itoa(t.Action)
	}
	return strings.Join(ids, ","), strings.Join(states, ",")
}

func (s *Service) Create(ctx context.Context, in Input) (int64, error) {
	if err := s.validate(ctx, &in); err != nil {
		return 0, err
	}
	taskCol, stateCol := serialize(in.Tasks)
	res, err := s.db.ExecContext(ctx,
		`INSERT INTO enabletask (enstate, startdate, starttime, taskid, flag) VALUES (?,?,?,?,?)`,
		stateCol, in.StartDate, in.StartTime, taskCol, 0)
	if err != nil {
		return 0, fmt.Errorf("新建启用计划: %w", err)
	}
	return res.LastInsertId()
}

func (s *Service) Update(ctx context.Context, id int64, in Input) error {
	if _, err := s.Get(ctx, id); err != nil {
		return err
	}
	if err := s.validate(ctx, &in); err != nil {
		return err
	}
	taskCol, stateCol := serialize(in.Tasks)
	// flag 不在 UPDATE 里：旧版新增时恒写 0，之后从不修改，这里保持不动。
	if _, err := s.db.ExecContext(ctx,
		`UPDATE enabletask SET enstate = ?, startdate = ?, starttime = ?, taskid = ? WHERE id = ?`,
		stateCol, in.StartDate, in.StartTime, taskCol, id); err != nil {
		return fmt.Errorf("修改启用计划: %w", err)
	}
	return nil
}

func (s *Service) Delete(ctx context.Context, ids []int64) (int, error) {
	if len(ids) == 0 {
		return 0, fmt.Errorf("请先选择要删除的启用计划")
	}
	ph, args := placeholders(ids)
	res, err := s.db.ExecContext(ctx, `DELETE FROM enabletask WHERE id IN (`+ph+`)`, args...)
	if err != nil {
		return 0, fmt.Errorf("删除启用计划: %w", err)
	}
	n, _ := res.RowsAffected()
	return int(n), nil
}

// ---------- 可选任务 ----------

type PickTask struct {
	TaskID   int64  `json:"taskId"`
	TaskName string `json:"taskName"`
	TaskType int    `json:"tasktype"`
	TypeText string `json:"typeText"`
	Info     string `json:"info"`
	// State 是任务当前的启用状态（task.projectstate，0 = 启用）。
	// 旧版 addmanager.html 用它给每一行的单选按钮设初值。
	State     int    `json:"projectstate"`
	StateText string `json:"stateText"`
}

// typeText 把 tasktype 翻成人话。取值依据是 task 表 tasktype 列的注释
// 「1-作息 2-文件 3-采播 4-电话 5-功放」，加上旧版 addmanager.html 里那串
// if/elseif 用到的扩展类型。
func typeText(t int) string {
	switch t {
	case 1:
		return "作息方案"
	case 2, 7:
		return "文件广播"
	case 3:
		return "采播管理"
	case 4:
		return "电话采播"
	case 5:
		return "终端功放"
	case 9:
		return "电源子任务"
	case 10:
		return "网络电台"
	case 13:
		return "系统任务"
	case 15, 17, 19:
		return "文字语音"
	case 24, 30:
		return "led播放"
	}
	return fmt.Sprintf("类型 %d", t)
}

// PickTasks 列出可以放进启用计划的任务。
//
// 旧版 enableadd.php 的候选是**全部任务**，而且是整张表一次列出来、
// 每行一个「启用 / 停用」单选 —— 不是先挑再设。这里保持一致：
// 一次把候选全给界面，让它照旧版那样铺成一张表。
// 普通用户只看自己的，与任务模块同一套口径。
func (s *Service) PickTasks(ctx context.Context, isAdmin bool, userID int64, keyword string) ([]PickTask, error) {
	cond := &store.Cond{}
	if !isAdmin {
		cond.Add(`COALESCE(task_user_id,0) = ?`, userID)
	}
	if keyword = strings.TrimSpace(keyword); keyword != "" {
		cond.Add(`taskname LIKE ? ESCAPE '\\'`, store.EscapeLike(keyword))
	}
	rs, err := s.db.QueryContext(ctx, `
		SELECT taskid, COALESCE(taskname,''), COALESCE(tasktype,0),
		       COALESCE(info,''), COALESCE(projectstate,0)
		FROM task`+cond.Where()+` ORDER BY tasktype, taskid LIMIT 500`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询可选任务: %w", err)
	}
	defer rs.Close()
	out := []PickTask{}
	for rs.Next() {
		var p PickTask
		if err := rs.Scan(&p.TaskID, &p.TaskName, &p.TaskType, &p.Info, &p.State); err != nil {
			return nil, err
		}
		p.TypeText = typeText(p.TaskType)
		// task.projectstate：0 = 启用、1 = 停用
		if p.State == 0 {
			p.StateText = "启用"
		} else {
			p.StateText = "停用"
		}
		out = append(out, p)
	}
	return out, rs.Err()
}
