// Package bell 实现作息方案（打铃）模块 F-48 ~ F-53。
//
// # 数据模型：作息方案不是一张表
//
// 一个「方案」= `task` 表中**共享同一个 `task.info`** 的一组行，
// 每一行是一次打铃（一节课）。方案本身没有独立的主键行，
// 它的属性全部由这组行聚合出来：
//
//	方案名     = task.info                （业务主键）
//	代表 ID    = MIN(taskid)              （只用于兼容旧的按 taskid 传参）
//	条目数     = COUNT(*)
//	起止日期   = MIN(startdate) / MAX(enddate)
//	状态       = ROUND(AVG(projectstate))  ← 组内多数派
//
// 过滤条件四项缺一不可：tasktype IN (1,15)、info 非空串、channel = 0、
// sec_task_id = 0。少一项就会把普通任务或功放子任务混进来。
//
// 如果按「方案表 + 明细表」建模，会与旧数据完全对不上 —— 这是契约 C-38。
//
// # projectstate
//
// 0 = 启用、1 = 停用，与 `audioserver.sql` 的列注释相反。
// 判据见 task 包 control.go 的 SetProjectState 注释。本包一律使用
// task.StateEnabled / task.StateDisabled，不写裸的 0 / 1。
//
// # 已核对的旧版缺陷（本轮实读旧代码得到，编号沿用手册）
//
//   - D-161 分页被整体注释掉，一次渲染全部方案
//   - D-162 `ORDER BY '$_GET[searchsequence]'` 加了引号 → 常量排序，排序永远不生效
//   - D-163 状态取组内多数派，条目状态不一致时界面毫无提示
//   - D-164 搜索分支用 `tasktype=1`、无搜索分支用 `IN(1,15)`，一搜索就漏
//   - D-165 搜索分支缺「info 非空串」这个条件
//   - D-166 `book_admin` 逗号隐式内连接 → 创建者被删方案整个消失
//   - D-176 启停语句不带 `channel = 0`，与列表口径不一致
//   - D-177 / D-188 启停、删除都不校验方案归属
//   - D-182 删除范围不带 `tasktype` 过滤 → 同名的文件广播任务会被一并删掉
//   - D-184 删除不清理 terminalkeymaptask / offlinetaskofterminal / offlinemediaofterminal
//   - D-189 复制时功放子任务用「新 ID 减 1」硬算 sec_task_id
//   - D-190 复制的字段清单不完整，endtime/disableday/interval_s 等被丢成默认值
//   - D-191 / D-172 用 `SELECT MAX(taskid)` 取自增 ID
//
// # 本轮实读旧代码新发现、手册记错的两处
//
//   - 手册 §1.3 说通知报文是 `project?state=1&info=<方案名>`，
//     实际 `send_socket_schedules` 拼的是 **`&name=`**（features_wrapper_class.php:359）。
//     按实际实现，写错了后台服务解析不到方案名。
//   - 手册 §1.4 说「逐条目发 task?state=6」。旧代码 `belldel_msg` 只对
//     **用户勾选的那几个 taskid** 发通知（`explode(",", $_GET['id'])`），
//     其余被连带删掉的条目一条都不发。新版按实际删掉的每个 taskid 发，
//     这是补漏不是改语义 —— 少发会让后台服务继续持有已删除的任务。
package bell

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/store"
	"htweb/internal/task"
)

var (
	ErrNotFound     = errors.New("作息方案不存在")
	ErrNoPermission = errors.New("只能操作自己创建的作息方案")
)

// 方案条目的任务类型。
//
// 新建一律写 1（旧 addonebellplan.php:121 与 do.php:11916 都是 `$tasktype=1`；
// 15 那行在两处都被注释掉了）。查询/删除范围仍按 IN (1,15) 兼容存量数据。
const (
	ItemType    = 1 // 新建条目写死的类型
	ItemTypeAlt = 15
	PowerType   = 9 // 功放子任务
	// LEDType / LEDTypeOld 是 LED 字幕子任务（与 task 包同一套：30 新、24 旧）
	LEDType    = 30
	LEDTypeOld = 24
)

// planScope 是「哪些 task 行属于作息方案」的唯一定义。
// 列表、启停、删除、复制全部引用它，杜绝旧版那种各分支口径不一的问题（D-164/D-165/D-176/D-182）。
func planScope(alias string) string {
	p := ""
	if alias != "" {
		p = alias + "."
	}
	return fmt.Sprintf(
		"%[1]stasktype IN (%[2]d,%[3]d) AND %[1]sinfo <> '' AND %[1]schannel = 0 AND %[1]ssec_task_id = 0",
		p, ItemType, ItemTypeAlt)
}

// planScopeWithSubs 是启停/删除用的范围：额外把子任务算进来 ——
// 功放子任务（类型 9）与 LED 字幕子任务（类型 30 / 24）。
// 旧版 bellstart_msg / bellstop_msg 用的是 `tasktype IN(1,15,9)`（BR-229），
// 那会儿作息方案还挂不了 LED 字幕；现在能挂了，子任务就得一并算进来，
// 否则删方案会留下孤儿 LED 任务、启停也漏掉它。
// 注意这里**不能**再要求 sec_task_id = 0 —— 子任务的 sec_task_id 指向主任务。
func planScopeWithSubs(alias string) string {
	p := ""
	if alias != "" {
		p = alias + "."
	}
	return fmt.Sprintf(
		"%[1]stasktype IN (%[2]d,%[3]d,%[4]d,%[5]d,%[6]d) AND %[1]sinfo <> '' AND %[1]schannel = 0",
		p, ItemType, ItemTypeAlt, PowerType, LEDType, LEDTypeOld)
}

type Service struct {
	db *sql.DB
}

func New(db *sql.DB) *Service { return &Service{db: db} }

// ---------- F-48 列表 ----------

// Plan 是方案列表的一行。
type Plan struct {
	PlanName string `json:"planName"`
	// RepresentativeTaskID 是 MIN(taskid)，仅用于兼容旧的按 taskid 传参的入口。
	// 方案的业务主键是 PlanName。
	RepresentativeTaskID int64 `json:"representativeTaskId"`
	ItemCount            int   `json:"itemCount"`
	// PowerSubTasks 是该方案下功放子任务的条数，删除预演和列表都用得上。
	PowerSubTasks int    `json:"powerSubTasks"`
	StartDate     string `json:"startdate"`
	EndDate       string `json:"enddate"`
	ProjectState  int    `json:"projectstate"`
	StateText     string `json:"projectStateText"`
	// MixedState 表示组内条目状态不一致。旧版把多数派状态直接显示出来，
	// 用户看到「启用」而其中几节课实际是停用的，界面毫无提示（D-163）。
	MixedState bool `json:"mixedState"`
	// DuplicateTimes 是组内重复的打铃时间。旧版完全不查（D-173），
	// 而现网方案「333」两个条目都排在 08:00:00 —— 确实存在这种数据，
	// 所以这里只做提示，不做拦截。
	DuplicateTimes []string `json:"duplicateTimes"`
	OwnerUserID    int64    `json:"ownerUserId"`
	OwnerUserName  string   `json:"ownerUserName"`
	OwnerDeleted   bool     `json:"ownerDeleted"`
}

type ListResult struct {
	Items     []Plan
	Total     int64
	ScopeNote string
}

// 排序白名单（修 D-162）。聚合列按别名排。
var orderWhitelist = map[string]string{
	"info":         "t.info",
	"planName":     "t.info",
	"startdate":    "MIN(t.startdate)",
	"enddate":      "MAX(t.enddate)",
	"projectstate": "ROUND(AVG(t.projectstate),0)",
	"itemCount":    "COUNT(*)",
}

// 旧 bellmanager.php 是 `ORDER BY projectstate, startdate ASC`。
// 0 = 启用，所以升序把启用中的方案排在前面。
const defaultOrder = "ROUND(AVG(t.projectstate),0) ASC, MIN(t.startdate) ASC"

type ListQuery struct {
	Keyword string
	OrderBy string
	Order   string
	Pager   store.Pager
}

// visibleCond 收敛可见范围（BR-220）。管理员看全部，其他人只看自己创建的。
func visibleCond(u *auth.User) (string, []interface{}) {
	if u.IsAdmin {
		return "", nil
	}
	return "t.task_user_id = ?", []interface{}{u.ID}
}

func (s *Service) List(ctx context.Context, u *auth.User, q ListQuery) (*ListResult, error) {
	cond := &store.Cond{}
	cond.Add(planScope("t"))
	if c, a := visibleCond(u); c != "" {
		cond.Add(c, a...)
	}
	if q.Keyword != "" {
		cond.Add(`t.info LIKE ? ESCAPE '\\'`, store.EscapeLike(q.Keyword))
	}
	where := cond.Where()

	out := &ListResult{Items: []Plan{}}
	if !u.IsAdmin {
		out.ScopeNote = "只显示你自己创建的作息方案"
	}

	// 总数按 info 去重 —— 一个 info 是一个方案，不是一行任务
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(DISTINCT t.info) FROM task t"+where, cond.Args()...).Scan(&out.Total); err != nil {
		return nil, fmt.Errorf("统计作息方案: %w", err)
	}
	if out.Total == 0 {
		return out, nil
	}

	order := store.OrderBy(orderWhitelist, q.OrderBy, q.Order, defaultOrder)

	args := append(append([]interface{}{}, cond.Args()...), q.Pager.PageSize, q.Pager.Offset())
	rows, err := s.db.QueryContext(ctx, `
		SELECT t.info, MIN(t.taskid), COUNT(*),
		       DATE_FORMAT(MIN(t.startdate),'%Y-%m-%d'),
		       DATE_FORMAT(MAX(t.enddate),'%Y-%m-%d'),
		       ROUND(AVG(t.projectstate),0), MIN(t.projectstate), MAX(t.projectstate),
		       MIN(COALESCE(t.task_user_id,0))
		FROM task t`+where+`
		GROUP BY t.info
		ORDER BY `+order+`
		LIMIT ? OFFSET ?`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询作息方案: %w", err)
	}
	defer rows.Close()

	names := []string{}
	ownerIDs := map[int64]bool{}
	for rows.Next() {
		var p Plan
		var minState, maxState int
		if err := rows.Scan(&p.PlanName, &p.RepresentativeTaskID, &p.ItemCount,
			&p.StartDate, &p.EndDate, &p.ProjectState, &minState, &maxState,
			&p.OwnerUserID); err != nil {
			return nil, err
		}
		p.MixedState = minState != maxState
		p.StateText = stateText(p.ProjectState)
		p.DuplicateTimes = []string{}
		names = append(names, p.PlanName)
		if p.OwnerUserID > 0 {
			ownerIDs[p.OwnerUserID] = true
		}
		out.Items = append(out.Items, p)
	}
	if err := rows.Err(); err != nil {
		return nil, err
	}

	if err := s.fillOwners(ctx, out.Items, ownerIDs); err != nil {
		return nil, err
	}
	if err := s.fillPowerCounts(ctx, out.Items, names); err != nil {
		return nil, err
	}
	if err := s.fillDuplicateTimes(ctx, out.Items, names); err != nil {
		return nil, err
	}
	return out, nil
}

func stateText(v int) string {
	if v == task.StateEnabled {
		return "启用"
	}
	return "停用"
}

// fillOwners 用 LEFT JOIN 的等价做法补创建者名。
// 旧版是 `FROM task, book_admin WHERE book_admin.id = task.task_user_id`
// 这种逗号隐式内连接 —— 创建者一被删，整个方案就从列表里消失（D-166）。
func (s *Service) fillOwners(ctx context.Context, items []Plan, ids map[int64]bool) error {
	names := map[int64]string{}
	if len(ids) > 0 {
		list := make([]int64, 0, len(ids))
		for id := range ids {
			list = append(list, id)
		}
		ph, args := placeholders(list)
		rows, err := s.db.QueryContext(ctx,
			`SELECT id, COALESCE(username,'') FROM book_admin WHERE id IN (`+ph+`)`, args...)
		if err != nil {
			return fmt.Errorf("查询创建者: %w", err)
		}
		for rows.Next() {
			var id int64
			var name string
			if err := rows.Scan(&id, &name); err != nil {
				rows.Close()
				return err
			}
			names[id] = name
		}
		rows.Close()
		if err := rows.Err(); err != nil {
			return err
		}
	}
	for i := range items {
		if n, ok := names[items[i].OwnerUserID]; ok && n != "" {
			items[i].OwnerUserName = n
			continue
		}
		items[i].OwnerUserName = "(用户已删除)"
		items[i].OwnerDeleted = true
	}
	return nil
}

// fillPowerCounts 一次查回本页所有方案的功放子任务条数。
func (s *Service) fillPowerCounts(ctx context.Context, items []Plan, names []string) error {
	if len(names) == 0 {
		return nil
	}
	ph, args := stringPlaceholders(names)
	rows, err := s.db.QueryContext(ctx,
		`SELECT info, COUNT(*) FROM task
		 WHERE info IN (`+ph+`) AND tasktype = ? AND channel = 0
		 GROUP BY info`, append(args, PowerType)...)
	if err != nil {
		return fmt.Errorf("统计功放子任务: %w", err)
	}
	defer rows.Close()

	counts := map[string]int{}
	for rows.Next() {
		var name string
		var n int
		if err := rows.Scan(&name, &n); err != nil {
			return err
		}
		counts[name] = n
	}
	if err := rows.Err(); err != nil {
		return err
	}
	for i := range items {
		items[i].PowerSubTasks = counts[items[i].PlanName]
	}
	return nil
}

// fillDuplicateTimes 找出组内重复的打铃时间。
// 只作提示（BR-226 在手册里写的是「重复时提示」），不拦截：
// 现网方案「333」的两个条目都排在 08:00:00，一旦拦截连编辑都进不去。
func (s *Service) fillDuplicateTimes(ctx context.Context, items []Plan, names []string) error {
	if len(names) == 0 {
		return nil
	}
	ph, args := stringPlaceholders(names)
	rows, err := s.db.QueryContext(ctx,
		`SELECT info, TIME_FORMAT(playtime,'%H:%i:%s'), COUNT(*) FROM task
		 WHERE info IN (`+ph+`) AND `+planScope("")+`
		 GROUP BY info, playtime HAVING COUNT(*) > 1`, args...)
	if err != nil {
		return fmt.Errorf("检查重复打铃时间: %w", err)
	}
	defer rows.Close()

	dup := map[string][]string{}
	for rows.Next() {
		var name, t string
		var n int
		if err := rows.Scan(&name, &t, &n); err != nil {
			return err
		}
		dup[name] = append(dup[name], t)
	}
	if err := rows.Err(); err != nil {
		return err
	}
	for i := range items {
		if d := dup[items[i].PlanName]; len(d) > 0 {
			items[i].DuplicateTimes = d
		}
	}
	return nil
}

// ---------- 方案名校验 ----------

// checkPlanName 校验方案名（BR-233 / D-174 / D-192）。
//
// 后台通知以**方案名字符串**作为标识（`project?state=1&name=<方案名>`），
// 名字里出现 ? & = 会把报文切断，后台解析到的方案名就不是我们发的那个。
// 旧版对此毫无防护，方案名直接拼进 SQL 也直接拼进报文。
func checkPlanName(name string) (string, error) {
	name = strings.TrimSpace(name)
	if name == "" {
		return "", fmt.Errorf("方案名称不能为空")
	}
	if len(name) > 255 {
		return "", fmt.Errorf("方案名称过长：按 UTF-8 计 %d 字节，上限 255 字节", len(name))
	}
	for _, bad := range []string{"?", "&", "="} {
		if strings.Contains(name, bad) {
			return "", fmt.Errorf("方案名称不能包含 %s —— 它是下发给后台服务的报文分隔符", bad)
		}
	}
	for _, r := range name {
		if r < 0x20 || r == 0x7f {
			return "", fmt.Errorf("方案名称不能包含控制字符")
		}
	}
	return name, nil
}

// planLock 用命名锁串行化「查重 + 写入」。
// 不能靠唯一索引 —— 加索引属于 DDL，被红线禁止；而且 info 本来就允许重复
// （同一方案的每个条目都用同一个 info）。
const planLock = "htweb_bell_plan_name"

// assertPlanExists 确认方案存在并做归属校验，返回方案的 owner。
func (s *Service) assertPlan(ctx context.Context, u *auth.User, name string) (int64, error) {
	var owner sql.NullInt64
	var n int
	err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*), MIN(task_user_id) FROM task WHERE info = ? AND `+planScope(""),
		name).Scan(&n, &owner)
	if err != nil {
		return 0, fmt.Errorf("查询作息方案: %w", err)
	}
	if n == 0 {
		return 0, ErrNotFound
	}
	if !u.IsAdmin && owner.Int64 != u.ID {
		return 0, ErrNoPermission
	}
	return owner.Int64, nil
}

// planNameFree 判断方案名有没有被占用（校验范围同 BR-222）。
func (s *Service) planNameFree(ctx context.Context, name string) error {
	var exists int
	err := s.db.QueryRowContext(ctx,
		`SELECT 1 FROM task WHERE info = ? AND `+planScope("")+` LIMIT 1`, name).Scan(&exists)
	if err == nil {
		return fmt.Errorf("作息方案名称已存在")
	}
	if errors.Is(err, sql.ErrNoRows) {
		return nil
	}
	return fmt.Errorf("方案重名校验: %w", err)
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

func stringPlaceholders(vals []string) (string, []interface{}) {
	if len(vals) == 0 {
		return "NULL", nil
	}
	args := make([]interface{}, len(vals))
	for i, v := range vals {
		args[i] = v
	}
	return strings.TrimSuffix(strings.Repeat("?,", len(vals)), ","), args
}
