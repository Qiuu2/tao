package assistant

import (
	"context"
	"database/sql"
	"fmt"
	"sort"
	"strings"
	"time"

	"htweb/internal/auth"
)

// query_task：按名字、分组、时间范围查任务。
//
// # 「作息方案」在这套库里是什么
//
// 是 task 表里**共享同一个 task.info 的一组行**，不是一张独立的表 ——
// 方案名就是 info，每一行是一次打铃。范围四项缺一不可：
// tasktype IN (1,15)、info <> ''、channel = 0、sec_task_id = 0。
// 这套建模是 bell 包定下来的（契约 C-38），本包一律沿用，不另起一套。
//
// ⚠ 别和**任务分组**（filetaskfree）搞混：那是文件广播任务的目录树，
//   与作息方案毫无关系（BR-161）。现网数据里 1001~1007 七条 tasktype=1
//   的任务 info 都是「春季作息」，那才是一个方案；它们的 parentid 都是 1
//   （admin 组），而 admin 只是个目录名。
//
// # 时长为什么不照抄原实现的算法
//
// 原实现按 timelengthtype == 2 判"按分钟"。**这套库里 2 是"按循环次数"**
// （见 typedtask 的说明：1 = 按时间（秒），2 = 按循环次数）。
// 同一个列名、两套含义，照抄的后果是把"放 3 遍"算成"放 3 分钟"，
// 匹配窗口整个错位。这里改用这套库里权威的那个来源：endtime - playtime，
// 它取不到时再退回 timelength（仅当按秒计时）。ok112 的复制功能漏拷 endtime
// 被记为一处数据丢失（"会让整条任务的播放时长语义变掉"），
// 正说明 endtime 在这套库里就是时长的依据。

// 只读的任务查询，权限口径与任务管理页一致：非管理员只看自己的任务。
const taskSelectCols = `t.taskid, t.taskname, COALESCE(t.parentid,0),
	       COALESCE(f.name,''), COALESCE(t.playtime,'00:00:00'),
	       COALESCE(t.endtime,'00:00:00'), COALESCE(t.timelength,0),
	       COALESCE(t.timelengthtype,1), t.startdate, t.enddate,
	       COALESCE(t.exemodel,'0000000'), COALESCE(t.projectstate,0),
	       COALESCE(t.tasktype,0), COALESCE(t.info,'')`

// queryTaskRows 读出可参与匹配的任务。
//
// sec_task_id = 0 把功放/LED 子任务挡在外面 —— 子任务是主任务的附属，
// 单独列出来用户会看到一堆重复的名字。
func (s *Service) queryTaskRows(ctx context.Context, u *auth.User, scheduleName string) ([]TaskRow, error) {
	q := `SELECT ` + taskSelectCols + `
	        FROM task t
	        LEFT JOIN filetaskfree f ON f.id = t.parentid
	       WHERE COALESCE(t.sec_task_id,0) = 0`
	var args []any
	if !u.IsAdmin {
		q += ` AND COALESCE(t.task_user_id,0) = ?`
		args = append(args, u.ID)
	}
	if scheduleName != "" {
		q += ` AND t.info = ?`
		args = append(args, scheduleName)
	}
	q += ` ORDER BY t.playtime, t.taskid`

	rows, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询任务: %w", err)
	}
	defer rows.Close()

	var out []TaskRow
	for rows.Next() {
		var (
			r                  TaskRow
			playtime, endtime  string
			startDate, endDate sql.NullString
			timelength, ltype  int
			exemodel           string
		)
		if err := rows.Scan(&r.ID, &r.Name, &r.FolderID, &r.FolderName, &playtime,
			&endtime, &timelength, &ltype, &startDate, &endDate, &exemodel,
			&r.State, &r.TaskType, &r.Info); err != nil {
			return nil, fmt.Errorf("读取任务: %w", err)
		}
		r.StartText = playtime
		r.StartSeconds, _ = parseTimeSeconds(playtime)
		r.DurationSeconds = taskDurationSeconds(playtime, endtime, timelength, ltype)
		r.Weekdays = weekdaysFromExemodel(exemodel)
		if sd, ok := parseSQLDate(startDate); ok {
			if ed, ok2 := parseSQLDate(endDate); ok2 {
				r.StartDate, r.EndDate, r.HasDateSpan = sd, ed, true
			} else {
				r.StartDate, r.EndDate, r.HasDateSpan = sd, sd, true
			}
		}
		out = append(out, r)
	}
	return out, rows.Err()
}

// parseSQLDate 处理这套库里的 '0000-00-00' —— 它表示"没设"，不是一个日期。
func parseSQLDate(v sql.NullString) (time.Time, bool) {
	if !v.Valid {
		return time.Time{}, false
	}
	text := strings.TrimSpace(v.String)
	if text == "" || strings.HasPrefix(text, "0000-00-00") {
		return time.Time{}, false
	}
	if len(text) > 10 {
		text = text[:10]
	}
	t, err := time.ParseInLocation("2006-01-02", text, nowFunc().Location())
	if err != nil {
		return time.Time{}, false
	}
	return t, true
}

// taskDurationSeconds 算一条任务放多久，至少 1 秒。见文件头对这一处偏离的说明。
func taskDurationSeconds(playtime, endtime string, timelength, lengthType int) int {
	start, okS := parseTimeSeconds(playtime)
	end, okE := parseTimeSeconds(endtime)
	if okS && okE && end != start {
		d := end - start
		if d < 0 {
			d += 24 * 3600 // 跨零点
		}
		if d > 0 {
			return d
		}
	}
	// 只有"按时间（秒）"时 timelength 才是秒数；按循环次数时它是次数，不能当秒用
	if lengthType == 1 && timelength > 0 {
		return timelength
	}
	return 1
}

// weekdaysFromExemodel 把 exemodel 翻成「周一」…「周日」。
//
// ⚠ exemodel 是**周日打头**的 7 位串（第 1 位 = 周日），
// 这一点与站内其它模块（dashboard / typedtask / offline）的注释一致。
// 全 0 表示不按星期自动执行，这里返回空 —— 与原实现里 execmode = 0 的处理一致：
// 空表示"不限星期"，参与匹配时哪天都算。
func weekdaysFromExemodel(mask string) []string {
	mask = strings.TrimSpace(mask)
	if len(mask) < 7 {
		return nil
	}
	// 串里第 i 位对应的标签：0=周日 1=周一 … 6=周六
	order := [7]string{"周日", "周一", "周二", "周三", "周四", "周五", "周六"}
	var out []string
	for i := 0; i < 7; i++ {
		if mask[i] == '1' {
			out = append(out, order[i])
		}
	}
	return out
}

// 回话措辞逐字取自原实现的 _apply_query_task_intent。
var (
	queryTaskScopedVariants = []string{
		"{scope_desc}已经查到，共 {count} 条任务。",
		"小电已经帮您查到 {scope_desc}，共 {count} 条任务。",
		"{scope_desc}的任务已经整理好了，共 {count} 条任务。",
	}
	queryTaskPlainVariants = []string{
		"已经查到 {count} 条任务。",
		"小电已经帮您查到 {count} 条任务。",
		"任务列表已经整理好了，共 {count} 条任务。",
	}
)

// execQueryTask 是 query_task 的执行器，流程照搬 _apply_query_task_intent。
func (s *Service) execQueryTask(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
	raw := rawTextOf(slots)
	scheduleName := slotText(slots, "schedule_name", "schedule_id", "SCHEDULE", "SCHEDULE_ID")
	taskName := slotText(slots, "task_name", "TASK", "CONTENT", "task")

	startValue := slotText(slots, "source_time", "time_range_start", "start_time", "start", "time")
	endValue := slotText(slots, "end_time", "time_range_end", "end")
	f := normalizeQueryTaskTimeFilters(raw, startValue, endValue)

	// 说了时间却没解析出来 → 如实说没听懂，不要当成"没有时间条件"把全表捞出来
	if f.StartRaw != "" && !f.HasStart {
		return actionResult{Reply: "开始时间范围我还没解析清楚，您换个说法试试。"}
	}
	if f.EndRaw != "" && !f.HasEnd {
		return actionResult{Reply: "结束时间范围我还没解析清楚，您换个说法试试。"}
	}
	// 「晚上10点到早上8点」这种跨夜区间，结束时间要顺延一天
	if f.HasStart && f.HasEnd && f.End.Before(f.Start) &&
		!(f.FilterEndRaw != "" && containsDateWord(f.FilterEndRaw)) {
		f.End = f.End.AddDate(0, 0, 1)
	}

	resolvedScheduleName := ""
	if scheduleName != "" {
		plans, err := s.scheduleCandidates(ctx, u)
		if err != nil {
			return actionResult{Err: err}
		}
		res := s.ResolveName(ctx, scheduleName, raw, plans)
		if res.Matched == "" {
			return actionResult{Reply: fmt.Sprintf("没有找到作息方案“%s”。", scheduleName)}
		}
		resolvedScheduleName = res.Matched
	}
	rows, err := s.queryTaskRows(ctx, u, resolvedScheduleName)
	if err != nil {
		return actionResult{Err: err}
	}

	if taskName != "" {
		kept := rows[:0:0]
		for _, r := range rows {
			if strings.Contains(r.Name, taskName) || strings.Contains(taskName, r.Name) {
				kept = append(kept, r)
			}
		}
		rows = kept
	}

	matched := rows[:0:0]
	for _, r := range rows {
		if taskMatchesQueryTime(r, f) {
			matched = append(matched, r)
		}
	}
	rows = matched

	if len(rows) == 0 {
		return actionResult{
			Reply: "暂时没有查到匹配的任务。您可以换个时间范围、任务名，或者补充作息方案后再试。",
			ActionLog: []map[string]any{{
				"intent": "query_task", "mode": "query",
				"details": map[string]any{
					"count": 0, "schedule_name": resolvedScheduleName, "task_name": taskName,
					"time_range_start": f.StartRaw, "time_range_end": f.EndRaw,
				},
			}},
		}
	}

	sort.SliceStable(rows, func(i, j int) bool {
		return rows[i].StartSeconds/60 < rows[j].StartSeconds/60
	})

	limit := len(rows)
	if limit > 50 {
		limit = 50
	}
	taskItems := make([]map[string]any, 0, limit)
	for _, r := range rows[:limit] {
		taskItems = append(taskItems, map[string]any{
			"task_id":   itoa64(r.ID),
			"task_name": r.Name,
			"time":      formatHHMM(r.StartText),
			"status":    taskStateText(r.State),
			"state":     r.State,
			// ⚠ 这里是**作息方案名**（task.info），不是任务分组名。
			// 不属于任何方案的任务（文件广播等）这一格是空的。
			"schedule_name": r.Info,
		})
	}

	// 只有跨了多个分组时才在预览里带上分组名 —— 同一个分组里带着是噪音
	distinct := map[string]bool{}
	for _, item := range taskItems {
		if n := strings.TrimSpace(item["schedule_name"].(string)); n != "" {
			distinct[n] = true
		}
	}
	includeSchedule := len(distinct) > 1

	previewCount := len(taskItems)
	if previewCount > 5 {
		previewCount = 5
	}
	parts := make([]string, 0, previewCount)
	for _, item := range taskItems[:previewCount] {
		name, _ := item["task_name"].(string)
		if strings.TrimSpace(name) == "" {
			name = "未命名任务"
		}
		at, _ := item["time"].(string)
		if at == "" {
			at = "--:--"
		}
		folder, _ := item["schedule_name"].(string)
		if includeSchedule && strings.TrimSpace(folder) != "" {
			parts = append(parts, fmt.Sprintf("%s/%s(%s)", folder, name, at))
		} else {
			parts = append(parts, fmt.Sprintf("%s(%s)", name, at))
		}
	}
	preview := strings.Join(parts, "、")

	scopeDesc := ""
	switch {
	case resolvedScheduleName != "" || scheduleName != "":
		scopeDesc = fmt.Sprintf("方案“%s”", firstNonEmpty(resolvedScheduleName, scheduleName))
	case taskName != "":
		scopeDesc = fmt.Sprintf("任务“%s”", taskName)
	}

	variants := queryTaskPlainVariants
	if scopeDesc != "" {
		variants = queryTaskScopedVariants
	}
	// ⚠ 种子第一位在没有 scope 时是**条数**，不是空串；条数为 0 时才是空串，
	//   见 reply.go 的 seedNum —— 这一位选错，选出来的措辞就与原版不同
	seedFirst := scopeDesc
	if scopeDesc == "" {
		seedFirst = seedNum(len(rows))
	}
	reply := queryRuntimeReply("query_task", variants,
		[]string{seedFirst, seedNum(len(rows))},
		map[string]string{"scope_desc": scopeDesc, "count": itoa(len(rows))})
	if preview != "" {
		reply = finalizeKeyIntentReply("query_task", reply, "先给您看几条："+preview)
	} else {
		reply = finalizeKeyIntentReply("query_task", reply)
	}

	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "query_task", "mode": "query",
			"schedule_name": firstNonEmpty(resolvedScheduleName, scheduleName),
			"details": map[string]any{
				"count":            len(rows),
				"schedule_name":    firstNonEmpty(resolvedScheduleName, scheduleName),
				"task_name":        taskName,
				"time_range_start": f.StartRaw,
				"time_range_end":   f.EndRaw,
				"tasks":            taskItems,
			},
		}},
	}
}

// scheduleCandidates 给名称解析用的**作息方案**名单。
//
// 方案没有 id，名字就是主键，所以 Candidate.ID 这里恒为 0 —— 调用方拿
// Matched 那个名字去用，不要拿 ID。范围与 bell 包的 planScope 完全一致，
// 少一项就会把普通任务或功放子任务混进来。
func (s *Service) scheduleCandidates(ctx context.Context, u *auth.User) ([]Candidate, error) {
	q := `SELECT DISTINCT COALESCE(info,'') FROM task
	       WHERE tasktype IN (1,15) AND info <> '' AND channel = 0 AND sec_task_id = 0`
	var args []any
	if !u.IsAdmin {
		q += ` AND COALESCE(task_user_id,0) = ?`
		args = append(args, u.ID)
	}
	q += ` ORDER BY info`
	rows, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询作息方案: %w", err)
	}
	defer rows.Close()
	var out []Candidate
	for rows.Next() {
		var name string
		if err := rows.Scan(&name); err != nil {
			return nil, err
		}
		out = append(out, Candidate{Name: name})
	}
	return out, rows.Err()
}

// slotText 取自 _slot_text：按顺序找第一个有值的槽位，多值用「、」连起来。
func slotText(slots map[string][]string, keys ...string) string {
	for _, k := range keys {
		values := make([]string, 0, len(slots[k]))
		for _, v := range slots[k] {
			if v = strings.TrimSpace(v); v != "" {
				values = append(values, v)
			}
		}
		if len(values) > 0 {
			return strings.Join(values, "、")
		}
	}
	return ""
}

// formatHHMM 取自 helpers.py 的 format_hhmm：截前 5 个字符。
func formatHHMM(value string) string {
	if len(value) >= 5 {
		return value[:5]
	}
	return value
}

// taskStateText 把 projectstate 翻成人话。
// ⚠ 0 = 启用、1 = 停用（列注释是反的，见 task 模块的说明）。
func taskStateText(state int) string {
	if state == 0 {
		return "启用"
	}
	return "停用"
}

func itoa64(v int64) string { return fmt.Sprintf("%d", v) }
