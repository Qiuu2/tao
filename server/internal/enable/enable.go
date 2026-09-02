// Package enable 实现启用管理（旧版 enableManager / displayenablemanager.php）。
//
// # 这张表管什么
//
//	enabletask (id, enstate, startdate, starttime, taskid, flag)
//
// 一条记录 = 「到了某年某月某日某时某分某秒，把这一批任务批量启用或停用」。
// 也就是定时的批量启停计划。后台 C 服务扫这张表，到点执行。
//
// # ⚠ 这张表的三个字段类型都不像它们的用途
//
//	id       int(4) unsigned **zerofill**   → 查出来是 "0001" 这种补零字符串
//	enstate  varchar(1024) DEFAULT '0'      → 只存 "0"/"1"，却给了 1024 字节
//	taskid   varchar(2048)                  → 存的是**逗号分隔的任务 id 列表**
//
// `taskid` 是一列多值 —— 典型的反范式，但红线禁止改表。
// 新版读的时候把它拆开、按 id 去 task 表补名字，写的时候拼回去，
// 并且**拒绝写入任何不能解析成正整数的片段**，免得脏字符串把后台的解析搞崩。
//
// 表上还有一个坑：`UNIQUE KEY (id)` 而**没有 PRIMARY KEY**。
// zerofill + 无主键的组合意味着 `WHERE id = 1` 与 `WHERE id = '0001'` 都能命中，
// 但排序和分页行为不如主键稳定。新版一律按 id 绑整数。
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

// enstate 的两个取值。旧版表单字段叫 enabledisable / get_radio，只发 "0" / "1"。
//
// ⚠ 这里 1 = 启用、0 = 停用，和 task.projectstate（0=启用）**相反**，
// 和 holidaytime.projectstate（1=启用）一致。三张表两种约定，只能逐表记住。
const (
	ActionEnable  = 1 // 到点把这批任务启用
	ActionDisable = 0 // 到点把这批任务停用
)

// taskidLimit 是 enabletask.taskid 的 varchar(2048)。
// 超了 MySQL 会静默截断 —— 截断处很可能落在某个 id 中间，
// 后台解析出一个不存在的任务 id，或者更糟，解析出一个**别的**任务。
const taskidLimit = 2048

const maxTasks = 200

type Task struct {
	TaskID   int64  `json:"taskId"`
	TaskName string `json:"taskName"`
	TaskType int    `json:"tasktype"`
	Info     string `json:"info"`
	// Missing 表示这个 id 在 task 表里已经找不到了。
	// 旧版删任务时不清理 enabletask，所以现网很可能出现悬空 id。
	Missing bool `json:"missing"`
}

type Item struct {
	ID         int64  `json:"id"`
	Action     int    `json:"enstate"`
	ActionText string `json:"actionText"`
	StartDate  string `json:"startdate"`
	StartTime  string `json:"starttime"`
	Tasks      []Task `json:"tasks"`
	// Expired 表示计划时间已经过去。后台执行过之后旧版不会清理，
	// 所以列表里会一直堆着历史记录 —— 标出来让人知道哪些还会生效。
	Expired bool `json:"expired"`
}

func actionText(v int) string {
	if v == ActionEnable {
		return "启用"
	}
	return "停用"
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
	rawTasks := make([][]int64, 0, q.Pager.PageSize)
	now := time.Now()

	for rs.Next() {
		var it Item
		var enstate, taskids string
		if err := rs.Scan(&it.ID, &enstate, &it.StartDate, &it.StartTime, &taskids); err != nil {
			return nil, fmt.Errorf("扫描启用计划行: %w", err)
		}
		// enstate 是 varchar，脏值一律按「停用」处理 —— 宁可少放一次广播，
		// 也不要把一个解析不了的值当成「启用」。
		if strings.TrimSpace(enstate) == "1" {
			it.Action = ActionEnable
		}
		it.ActionText = actionText(it.Action)
		ids := parseIDs(taskids)
		rawTasks = append(rawTasks, ids)
		for _, id := range ids {
			allIDs[id] = true
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
		for _, id := range rawTasks[i] {
			t, ok := names[id]
			if !ok {
				t = Task{TaskID: id, TaskName: "(任务已删除)", Missing: true}
			}
			items[i].Tasks = append(items[i].Tasks, t)
		}
	}
	return &ListResult{Items: items, Total: total}, nil
}

// parseIDs 把 "70001,70002" 拆成 id 列表。
// 解析不了的片段直接丢掉 —— 这一列是 varchar，旧数据里什么都可能有。
func parseIDs(s string) []int64 {
	out := []int64{}
	seen := map[int64]bool{}
	for _, p := range strings.Split(s, ",") {
		v, err := strconv.ParseInt(strings.TrimSpace(p), 10, 64)
		if err != nil || v <= 0 || seen[v] {
			continue
		}
		seen[v] = true
		out = append(out, v)
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
	if strings.TrimSpace(enstate) == "1" {
		it.Action = ActionEnable
	}
	it.ActionText = actionText(it.Action)

	ids := parseIDs(taskids)
	names, err := s.taskNames(ctx, toSet(ids))
	if err != nil {
		return nil, err
	}
	it.Tasks = []Task{}
	for _, tid := range ids {
		t, ok := names[tid]
		if !ok {
			t = Task{TaskID: tid, TaskName: "(任务已删除)", Missing: true}
		}
		it.Tasks = append(it.Tasks, t)
	}
	return &it, nil
}

func toSet(ids []int64) map[int64]bool {
	m := map[int64]bool{}
	for _, id := range ids {
		m[id] = true
	}
	return m
}

// ---------- 新建 / 修改 ----------

type Input struct {
	Action    int
	StartDate string
	StartTime string
	TaskIDs   []int64
}

func (s *Service) validate(ctx context.Context, in *Input) error {
	if in.Action != ActionEnable && in.Action != ActionDisable {
		return fmt.Errorf("操作只能是启用(1)或停用(0)")
	}
	in.StartDate = strings.TrimSpace(in.StartDate)
	in.StartTime = strings.TrimSpace(in.StartTime)

	if _, err := time.Parse("2006-01-02", in.StartDate); err != nil {
		return fmt.Errorf("执行日期格式不正确，必须是 YYYY-MM-DD")
	}
	if _, err := time.Parse("15:04:05", in.StartTime); err != nil {
		return fmt.Errorf("执行时间格式不正确，必须是 HH:MM:SS")
	}
	if len(in.TaskIDs) == 0 {
		return fmt.Errorf("请至少选择一条任务")
	}
	if len(in.TaskIDs) > maxTasks {
		return fmt.Errorf("一个启用计划最多绑定 %d 条任务", maxTasks)
	}
	seen := map[int64]bool{}
	for _, id := range in.TaskIDs {
		if id <= 0 {
			return fmt.Errorf("任务列表里有非法的任务 ID")
		}
		if seen[id] {
			return fmt.Errorf("任务列表里有重复的任务")
		}
		seen[id] = true
	}
	// 逐条确认任务存在。旧版一条都不查：任务删掉之后计划还在，
	// 到点后台去启用一个不存在的任务。
	ph, args := placeholders(in.TaskIDs)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE taskid IN (`+ph+`)`, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验任务: %w", err)
	}
	if n != len(in.TaskIDs) {
		return fmt.Errorf("任务列表里有已不存在的任务，请重新选择")
	}
	// 拼出来的字符串必须装得下 varchar(2048)。
	// 截断会落在某个 id 中间，后台解析出一个**别的**任务 —— 这比报错危险得多。
	if len(joinIDs(in.TaskIDs)) > taskidLimit {
		return fmt.Errorf("选中的任务过多，任务 ID 列表超出 %d 字节上限，请拆成多条计划", taskidLimit)
	}
	return nil
}

func joinIDs(ids []int64) string {
	parts := make([]string, len(ids))
	for i, id := range ids {
		parts[i] = strconv.FormatInt(id, 10)
	}
	return strings.Join(parts, ",")
}

func (s *Service) Create(ctx context.Context, in Input) (int64, error) {
	if err := s.validate(ctx, &in); err != nil {
		return 0, err
	}
	res, err := s.db.ExecContext(ctx,
		`INSERT INTO enabletask (enstate, startdate, starttime, taskid, flag) VALUES (?,?,?,?,?)`,
		strconv.Itoa(in.Action), in.StartDate, in.StartTime, joinIDs(in.TaskIDs), 0)
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
	// flag 不在 UPDATE 里：旧版新增时恒写 0，之后从不修改，这里保持不动。
	if _, err := s.db.ExecContext(ctx,
		`UPDATE enabletask SET enstate = ?, startdate = ?, starttime = ?, taskid = ? WHERE id = ?`,
		strconv.Itoa(in.Action), in.StartDate, in.StartTime, joinIDs(in.TaskIDs), id); err != nil {
		return fmt.Errorf("修改启用计划: %w", err)
	}
	return nil
}

// ---------- 逐条设置启用/停用（:80 的表格式勾选）----------
//
// :80 的「添加启用管理」是一张任务表格，每条任务单独勾「是否启用」，
// 外加「全选启用 / 全选停用」两个按钮。
//
// ⚠ 而 `enabletask.enstate` 是**整行一个值**、`taskid` 是一串 id ——
//    一条记录只能全启用或全停用，表结构如此，红线禁止改表。
//
// 所以这里的做法是：界面照 :80 逐条勾，**保存时按状态拆成最多两条记录**
// （启用的一条、停用的一条），日期时间相同。这不是绕过表结构，
// 而是把「一个时间点要做两件事」如实地写成两行 —— 后台扫表时本来就是逐行执行的。
//
// 修改时同理：以被编辑那条记录的**原日期时间**为一个「时间槽」，
// 槽里最多维护两行（启用一行、停用一行）：某一侧变空就删掉那行，
// 原本没有的一侧就新建。所以反复编辑不会越攒越多。

// SaveInput 是表格式保存的入参：同一时间点，哪些任务要启用、哪些要停用。
type SaveInput struct {
	StartDate string
	StartTime string
	Enable    []int64
	Disable   []int64
}

// SaveResult 如实回报这次动了哪几行，界面照实说。
type SaveResult struct {
	Created []int64 `json:"created"`
	Updated []int64 `json:"updated"`
	Deleted []int64 `json:"deleted"`
}

func (s *Service) validateSave(ctx context.Context, in *SaveInput) error {
	in.StartDate = strings.TrimSpace(in.StartDate)
	in.StartTime = strings.TrimSpace(in.StartTime)
	if _, err := time.Parse("2006-01-02", in.StartDate); err != nil {
		return fmt.Errorf("开始日期格式不正确，必须是 YYYY-MM-DD")
	}
	if _, err := time.Parse("15:04:05", in.StartTime); err != nil {
		return fmt.Errorf("开始时间格式不正确，必须是 HH:MM:SS")
	}
	if len(in.Enable)+len(in.Disable) == 0 {
		return fmt.Errorf("请至少选择一条任务")
	}
	// 同一条任务不能既启用又停用 —— 那是两条互相打架的指令
	inEnable := map[int64]bool{}
	for _, id := range in.Enable {
		inEnable[id] = true
	}
	for _, id := range in.Disable {
		if inEnable[id] {
			return fmt.Errorf("同一条任务不能同时设为启用和停用")
		}
	}
	all := append(append([]int64{}, in.Enable...), in.Disable...)
	for _, group := range [][]int64{in.Enable, in.Disable} {
		if len(group) > maxTasks {
			return fmt.Errorf("同一种操作最多绑定 %d 条任务", maxTasks)
		}
		if len(joinIDs(group)) > taskidLimit {
			return fmt.Errorf("选中的任务过多，任务 ID 列表超出 %d 字节上限，请拆开设置", taskidLimit)
		}
	}
	seen := map[int64]bool{}
	for _, id := range all {
		if id <= 0 {
			return fmt.Errorf("任务列表里有非法的任务 ID")
		}
		if seen[id] {
			return fmt.Errorf("任务列表里有重复的任务")
		}
		seen[id] = true
	}
	ph, args := placeholders(all)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE taskid IN (`+ph+`)`, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验任务: %w", err)
	}
	if n != len(all) {
		return fmt.Errorf("任务列表里有已不存在的任务，请重新选择")
	}
	return nil
}

// slotRow 是时间槽里已有的一行。
type slotRow struct {
	id     int64
	action int
}

// slotRows 找出与给定日期时间相同的已有记录（最多关心两行：启用一行、停用一行）。
func (s *Service) slotRows(ctx context.Context, date, tm string) ([]slotRow, error) {
	rs, err := s.db.QueryContext(ctx, `
		SELECT CAST(id AS UNSIGNED), COALESCE(enstate,'0')
		FROM enabletask
		WHERE CAST(startdate AS CHAR) = ? AND CAST(starttime AS CHAR) = ?
		ORDER BY id`, date, tm)
	if err != nil {
		return nil, fmt.Errorf("查询同一时间点的启用计划: %w", err)
	}
	defer rs.Close()
	out := []slotRow{}
	for rs.Next() {
		var r slotRow
		var st string
		if err := rs.Scan(&r.id, &st); err != nil {
			return nil, err
		}
		if strings.TrimSpace(st) == "1" {
			r.action = ActionEnable
		}
		out = append(out, r)
	}
	return out, rs.Err()
}

// Save 新建或修改一个「时间点」的启停安排。editID = 0 表示新建。
func (s *Service) Save(ctx context.Context, editID int64, in SaveInput) (*SaveResult, error) {
	if err := s.validateSave(ctx, &in); err != nil {
		return nil, err
	}

	out := &SaveResult{Created: []int64{}, Updated: []int64{}, Deleted: []int64{}}

	// 修改时以**原来的**日期时间定位同槽的记录；新建时槽是空的。
	existing := []slotRow{}
	if editID > 0 {
		cur, err := s.Get(ctx, editID)
		if err != nil {
			return nil, err
		}
		rows, err := s.slotRows(ctx, cur.StartDate, cur.StartTime)
		if err != nil {
			return nil, err
		}
		existing = rows
	}
	// 每种状态只认槽里的第一行；同状态的重复行（历史脏数据）不动它们，
	// 免得一次编辑顺手改掉别人建的记录。
	pick := func(action int) int64 {
		for _, r := range existing {
			if r.action == action {
				return r.id
			}
		}
		return 0
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	for _, g := range []struct {
		action int
		ids    []int64
	}{{ActionEnable, in.Enable}, {ActionDisable, in.Disable}} {
		id := pick(g.action)
		switch {
		case len(g.ids) > 0 && id > 0:
			// flag 不在 UPDATE 里：旧版新增时恒写 0，之后从不修改
			if _, err := tx.ExecContext(ctx,
				`UPDATE enabletask SET enstate = ?, startdate = ?, starttime = ?, taskid = ? WHERE id = ?`,
				strconv.Itoa(g.action), in.StartDate, in.StartTime, joinIDs(g.ids), id); err != nil {
				return nil, fmt.Errorf("修改启用计划: %w", err)
			}
			out.Updated = append(out.Updated, id)
		case len(g.ids) > 0:
			res, err := tx.ExecContext(ctx,
				`INSERT INTO enabletask (enstate, startdate, starttime, taskid, flag) VALUES (?,?,?,?,?)`,
				strconv.Itoa(g.action), in.StartDate, in.StartTime, joinIDs(g.ids), 0)
			if err != nil {
				return nil, fmt.Errorf("新建启用计划: %w", err)
			}
			newID, _ := res.LastInsertId()
			out.Created = append(out.Created, newID)
		case id > 0:
			// 这一侧被清空了：把对应的那行删掉，而不是留一条空 taskid 的记录
			if _, err := tx.ExecContext(ctx,
				`DELETE FROM enabletask WHERE id = ?`, id); err != nil {
				return nil, fmt.Errorf("删除启用计划: %w", err)
			}
			out.Deleted = append(out.Deleted, id)
		}
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return out, nil
}

// SlotDetail 是编辑弹窗需要的数据：这个时间点上，哪些任务启用、哪些停用。
type SlotDetail struct {
	ID        int64  `json:"id"`
	StartDate string `json:"startdate"`
	StartTime string `json:"starttime"`
	Enable    []Task `json:"enable"`
	Disable   []Task `json:"disable"`
}

// GetSlot 读一个时间槽的完整安排（把同一时刻的启用行与停用行合起来给界面）。
func (s *Service) GetSlot(ctx context.Context, id int64) (*SlotDetail, error) {
	cur, err := s.Get(ctx, id)
	if err != nil {
		return nil, err
	}
	out := &SlotDetail{ID: id, StartDate: cur.StartDate, StartTime: cur.StartTime,
		Enable: []Task{}, Disable: []Task{}}

	rows, err := s.slotRows(ctx, cur.StartDate, cur.StartTime)
	if err != nil {
		return nil, err
	}
	for _, r := range rows {
		it, err := s.Get(ctx, r.id)
		if err != nil {
			return nil, err
		}
		if r.action == ActionEnable {
			out.Enable = append(out.Enable, it.Tasks...)
		} else {
			out.Disable = append(out.Disable, it.Tasks...)
		}
	}
	return out, nil
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
	// State 是任务当前的启用状态（task.projectstate，⚠ 0 = 启用）。
	State     int    `json:"projectstate"`
	StateText string `json:"stateText"`
}

// typeText 把 tasktype 翻成人话。取值依据是 task 表 tasktype 列的注释
// 「1-作息 2-文件 3-采播 4-电话 5-功放」，加上各页面实测到的扩展类型。
func typeText(t int) string {
	switch t {
	case 1:
		return "作息条目"
	case 2, 7:
		return "文件广播"
	case 3:
		return "采播"
	case 4:
		return "电话采播"
	case 5:
		return "终端功放"
	case 9:
		return "电源子任务"
	case 13:
		return "系统任务"
	case 15, 17, 19:
		return "文字语音"
	case 24, 30:
		return "LED 播放"
	}
	return fmt.Sprintf("类型 %d", t)
}

// PickTasks 列出可以放进启用计划的任务。
//
// 旧版 enableadd.php 的候选是**全部任务**，这里保持一致 ——
// 「哪些任务可以被定时启停」没有业务上的限制。
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
		// ⚠ task.projectstate：0 = 启用、1 = 停用（与 enabletask.enstate 相反）
		if p.State == 0 {
			p.StateText = "启用"
		} else {
			p.StateText = "停用"
		}
		out = append(out, p)
	}
	return out, rs.Err()
}
