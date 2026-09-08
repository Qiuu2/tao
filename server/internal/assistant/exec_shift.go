package assistant

import (
	"context"
	"database/sql"
	"fmt"
	"htweb/internal/auth"
	"htweb/internal/notify"
	"regexp"
	"strconv"
	"strings"
)

// shift_schedule_later / earlier：把整个方案的任务整体平移，**生成一个新方案**。
//
// # 注意它不改原方案
//
// 「把冬季作息整体后移 30 分钟」不是把冬季作息改掉，而是**照着它生成一个新的**。
// 原方案原封不动。这一点原实现写得很明确（要求必须给出 new_schedule_name），
// 而且是对的：整体位移多半是为了应对某天的特殊安排，改坏了原方案很难还原。
//
// 在这套库里"生成一个新方案"= 新建一个任务分组，把源分组里的任务逐条复制进去
// 再平移时间。复制走 task.Copy，媒体清单、终端清单、功放/LED 子任务都会跟着。
//
// # 平移跨零点时星期要跟着转
//
// 23:50 后移 30 分钟是第二天 00:20 —— 星期掩码要整体后移一天，起止日期也要。
// 取自 _shift_task_by_minutes。不转的话这条任务会提前一天响。

// swap_schedule：把两个时间点的任务互换。
//
// 「把周一和周五的任务对调」「把 8 点和 9 点的任务互换」。
// 与挪动一样要求两个时间同一维度。

var reTimeOffset = regexp.MustCompile(`(-?\d+(?:\.\d+)?)\s*(分钟|分|小时|时|天|日|周|星期|个月|月|年)`)
var reFirstInt = regexp.MustCompile(`-?\d+`)

// parseTimeOffsetMinutes 取自 _parse_time_offset_minutes。
func parseTimeOffsetMinutes(value string) (int, bool) {
	text := strings.TrimSpace(value)
	if text == "" {
		return 0, false
	}
	if strings.Contains(text, "半小时") {
		return 30, true
	}
	m := reTimeOffset.FindStringSubmatch(text)
	if m == nil {
		// 没带单位就当分钟 —— 与原实现回退到 _first_int_from_text 一致
		if d := reFirstInt.FindString(text); d != "" {
			n, err := strconv.Atoi(d)
			if err == nil {
				return n, true
			}
		}
		return 0, false
	}
	n, err := strconv.ParseFloat(m[1], 64)
	if err != nil {
		return 0, false
	}
	switch m[2] {
	case "分钟", "分":
		return roundHalfUp(n), true
	case "小时", "时":
		return roundHalfUp(n * 60), true
	case "天", "日":
		return roundHalfUp(n * 24 * 60), true
	case "周", "星期":
		return roundHalfUp(n * 7 * 24 * 60), true
	case "月", "个月":
		return roundHalfUp(n * 30 * 24 * 60), true
	case "年":
		return roundHalfUp(n * 365 * 24 * 60), true
	}
	return roundHalfUp(n), true
}

// roundHalfUp 复刻 Python 的 int(round(x))。
//
// ⚠ Python 的 round 是**四舍六入五取偶**（banker's rounding），
// 但这里的输入都是分钟数乘一个整数倍率，落到 .5 的情形只可能来自
// "1.5小时" 这类 —— 90.0，正好是整数，不会碰到取偶与否的分歧。
// 所以用普通的四舍五入，行为一致。
func roundHalfUp(v float64) int {
	if v < 0 {
		return -int(-v + 0.5)
	}
	return int(v + 0.5)
}

// execShiftSchedule 是 shift_schedule_later / earlier 的共用实现。
func (s *Service) execShiftSchedule(intent string, later bool) executor {
	return func(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
		if s.tasks == nil || s.bells == nil {
			return actionResult{Err: fmt.Errorf("作息方案服务未接入")}
		}
		raw := rawTextOf(slots)
		scheduleName := slotText(slots, "schedule_name", "schedule_id", "SCHEDULE", "SCHEDULE_ID")
		newName := slotText(slots, "new_schedule_name", "target_schedule_name")
		offsetText := slotText(slots, "time_offset", "duration_offset")

		var missing []string
		if scheduleName == "" {
			missing = append(missing, "schedule_name")
		}
		if offsetText == "" {
			missing = append(missing, "time_offset")
		}
		if newName == "" {
			missing = append(missing, "new_schedule_name")
		}
		if len(missing) > 0 {
			return actionResult{
				Reply: askRuntimeReply("shift_schedule", "源方案、位移量和新方案名称",
					"比如把冬季作息整体后移 30 分钟生成临时方案"),
				MissingSlots: missing,
			}
		}

		offset, ok := parseTimeOffsetMinutes(offsetText)
		if !ok {
			return actionResult{Reply: `time_offset 解析失败,请使用如"30分钟/1小时/1天"格式。`}
		}
		if offset <= 0 {
			return actionResult{Reply: "time_offset 需要大于0。"}
		}
		if !later {
			offset = -offset
		}

		plans, err := s.scheduleCandidates(ctx, u)
		if err != nil {
			return actionResult{Err: err}
		}
		src := s.ResolveName(ctx, scheduleName, raw, plans)
		if src.Matched == "" {
			return actionResult{Reply: replyScheduleNotFound(scheduleName)}
		}
		for _, p := range plans {
			if compactText(p.Name) == compactText(newName) {
				return actionResult{
					Reply: fmt.Sprintf(`目标方案"%s"已存在,请换一个名称。`, newName)}
			}
		}

		rows, err := s.queryTaskRows(ctx, u, src.Matched)
		if err != nil {
			return actionResult{Err: err}
		}
		if len(rows) == 0 {
			return actionResult{
				Reply: fmt.Sprintf("方案“%s”里没有任务，位移出来会是个空方案。", src.Matched)}
		}

		// 整方案复制交给 bell.Copy —— 它一次把条目、功放/LED 子任务、
		// 媒体清单、终端清单全带过去，并且用命名锁挡住重名。
		// 自己逐条 task.Copy 做不到这些（作息条目 tasktype=1 根本不在
		// task.Copy 允许的类型里，实测直接被拒）。
		cp, err := s.bells.Copy(ctx, u, src.Matched, newName)
		if err != nil {
			return actionResult{
				Reply: failureRuntimeReply(intent, "方案位移", nil, err.Error(),
					"换一个方案名再试一次。"),
			}
		}

		// 复制出来的条目按源条目逐条平移
		newRows, err := s.queryTaskRows(ctx, u, newName)
		if err != nil {
			s.rollbackShiftPlan(ctx, u, newName)
			return actionResult{Err: err}
		}
		byName := map[string]TaskRow{}
		for _, r := range rows {
			byName[r.Name] = r
		}
		copied := 0
		for _, nr := range newRows {
			srcRow, ok := byName[nr.Name]
			if !ok {
				// 名字对不上就用它自己当基准 —— 至少不会挪错量
				srcRow = nr
			}
			if err := s.shiftCopiedTask(ctx, nr.ID, srcRow, offset); err != nil {
				s.rollbackShiftPlan(ctx, u, newName)
				return actionResult{Err: err}
			}
			copied++
		}
		_ = cp

		direction := "向后"
		if !later {
			direction = "向前"
		}
		return actionResult{
			// 措辞逐字取自原实现（引号也是 ASCII 的）
			Reply: fmt.Sprintf(`已基于"%s"生成"%s",并将任务整体%s位移 %s。`,
				src.Matched, newName, direction, offsetText),
			ActionLog: []map[string]any{{
				"intent": intent, "mode": "permanent", "schedule_name": newName,
				"details": map[string]any{
					"source_schedule_name": src.Matched, "new_schedule_name": newName,
					"time_offset": offsetText, "count": copied,
				},
			}},
		}
	}
}

// shiftCopiedTask 把复制出来的那条任务按 offset 平移。取自 _shift_task_by_minutes。
func (s *Service) shiftCopiedTask(ctx context.Context, taskID int64, src TaskRow, offsetMinutes int) error {
	newTime, dayDelta := shiftHHMMSSWithDayDelta(src.StartText, offsetMinutes)
	newEnd := shiftEndTime(src, newTime)

	if dayDelta == 0 {
		_, err := s.db.ExecContext(ctx,
			`UPDATE task SET playtime=?, endtime=? WHERE taskid=? OR sec_task_id=?`,
			newTime, newEnd, taskID, taskID)
		if err != nil {
			return fmt.Errorf("平移任务 %d: %w", taskID, err)
		}
		return nil
	}

	// 跨零点：星期整体转，起止日期也跟着走
	exemodel := exemodelFromWeekdays(shiftWeekdaysByDelta(src.Weekdays, dayDelta))
	if len(src.Weekdays) == 0 {
		exemodel = "0000000"
	}
	var start, end sql.NullString
	if src.HasDateSpan {
		start = sql.NullString{String: src.StartDate.AddDate(0, 0, dayDelta).Format("2006-01-02"), Valid: true}
		end = sql.NullString{String: src.EndDate.AddDate(0, 0, dayDelta).Format("2006-01-02"), Valid: true}
	}
	if start.Valid {
		_, err := s.db.ExecContext(ctx,
			`UPDATE task SET playtime=?, endtime=?, exemodel=?, startdate=?, enddate=?
			 WHERE taskid=? OR sec_task_id=?`,
			newTime, newEnd, exemodel, start.String, end.String, taskID, taskID)
		if err != nil {
			return fmt.Errorf("平移任务 %d: %w", taskID, err)
		}
		return nil
	}
	_, err := s.db.ExecContext(ctx,
		`UPDATE task SET playtime=?, endtime=?, exemodel=? WHERE taskid=? OR sec_task_id=?`,
		newTime, newEnd, exemodel, taskID, taskID)
	if err != nil {
		return fmt.Errorf("平移任务 %d: %w", taskID, err)
	}
	return nil
}

// rollbackShiftPlan 把位移过程中建出来的新方案整个删掉。
func (s *Service) rollbackShiftPlan(ctx context.Context, u *auth.User, planName string) {
	if _, err := s.bells.Delete(ctx, u, planName); err != nil {
		logf("方案位移回滚失败：新方案 %q 没能删掉：%v", planName, err)
	}
}

// ---------- 对调 ----------

// execSwapSchedule 把两个时间点的任务互换。
func (s *Service) execSwapSchedule(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
	if s.tasks == nil {
		return actionResult{Err: fmt.Errorf("任务服务未接入")}
	}
	raw := rawTextOf(slots)
	scheduleName := slotText(slots, "schedule_name", "schedule_id", "SCHEDULE", "SCHEDULE_ID")
	taskName := slotText(slots, "task_name", "TASK", "CONTENT", "task")
	sourceText := slotText(slots, "source_time", "time_from", "from_time")
	targetText := slotText(slots, "target_time", "end_time", "time_to", "to_time")

	var missing []string
	if sourceText == "" {
		missing = append(missing, "source_time")
	}
	if targetText == "" {
		missing = append(missing, "target_time")
	}
	if len(missing) > 0 {
		return actionResult{
			Reply: askRuntimeReply("swap_schedule", "要对调的两个时间",
				"比如把周一早读和周五早读对调，或者把 8 点和 9 点的任务互换"),
			MissingSlots: missing,
		}
	}

	a, okA := ParseTimeAnchor(sourceText)
	b, okB := ParseTimeAnchor(targetText)
	if !okA || !okB {
		return actionResult{
			Reply: "这两个时间我还没完全听明白。您可以换成“周三”“周五”或者“2月10日”这类说法。",
		}
	}
	if a.Kind != b.Kind {
		return actionResult{Reply: "对调时间需要同一维度（都为星期，或都为日期）。"}
	}

	resolvedSchedule, res := s.resolveSchedulePlan(ctx, u, raw, scheduleName)
	if res != nil {
		return *res
	}
	rows, err := s.queryTaskRows(ctx, u, resolvedSchedule)
	if err != nil {
		return actionResult{Err: err}
	}

	groupA := matchAnchorTasks(rows, a, taskName)
	groupB := matchAnchorTasks(rows, b, taskName)
	if len(groupA) == 0 || len(groupB) == 0 {
		return actionResult{Reply: replyNoMatchingTasks("对调", []string{sourceText, targetText})}
	}

	// 对调是**互换**，两边各自要挪到对方那里，所以两组都得算得出来
	plansA := make([]movePlan, 0, len(groupA))
	plansB := make([]movePlan, 0, len(groupB))
	var names []string
	for _, r := range groupA {
		p, ok := planMove(r, a, b)
		if !ok {
			continue
		}
		plansA = append(plansA, p)
		names = append(names, fmt.Sprintf("%s(%s→%s)", r.Name,
			formatHHMM(r.StartText), formatHHMM(p.PlayTime)))
	}
	for _, r := range groupB {
		p, ok := planMove(r, b, a)
		if !ok {
			continue
		}
		plansB = append(plansB, p)
		names = append(names, fmt.Sprintf("%s(%s→%s)", r.Name,
			formatHHMM(r.StartText), formatHHMM(p.PlayTime)))
	}
	if len(plansA) == 0 || len(plansB) == 0 {
		return actionResult{Reply: replyNoMatchingTasks("对调", []string{sourceText, targetText})}
	}

	mode, confirmed := applyModeOf(slots)
	if !confirmed {
		scope := "当前启用中的作息方案"
		if resolvedSchedule != "" {
			scope = "作息方案“" + resolvedSchedule + "”"
		}
		summary := fmt.Sprintf("%s里要把「%s」和「%s」的任务对调，共 %d 条：%s",
			scope, sourceText, targetText, len(plansA)+len(plansB),
			previewNames(names, 5, "条任务"))
		keep := map[string][]string{}
		for k, v := range slots {
			if !strings.HasPrefix(k, "__") {
				keep[k] = v
			}
		}
		return askApplyMode(IntentSwapSchedule, raw, keep, summary)
	}
	if mode == modeOnce {
		// 一次性对调 = 两边各建一条影子任务、各停一次。目前只做永久，
		// 如实说，不假装做了。
		return actionResult{
			Reply: "一次性对调我还没接上，现在只能永久对调。您要是想永久这么改，" +
				"再说一次「对调，以后都这样」就行。",
		}
	}

	all := append(append([]movePlan{}, plansA...), plansB...)
	ids := make([]int64, 0, len(all))
	for _, p := range all {
		ids = append(ids, p.Task.ID)
	}
	if blocked := s.rejectNotOwned(ctx, u, ids); blocked != nil {
		return *blocked
	}
	moved, err := s.applyMovePlans(ctx, all)
	if err != nil {
		return actionResult{Err: err}
	}
	s.notifyTasksSaved(ctx, notify.TaskUpdated, ids)

	return actionResult{
		Reply: fmt.Sprintf("已把「%s」和「%s」的任务对调，共 %d 条：%s。",
			sourceText, targetText, moved, previewNames(names, 5, "条任务")),
		ActionLog: []map[string]any{{
			"intent": "swap_schedule", "mode": "permanent", "schedule_name": resolvedSchedule,
			"details": map[string]any{
				"count": moved, "task_ids": ids,
				"source_time": sourceText, "target_time": targetText,
			},
		}},
	}
}

// matchAnchorTasks 挑出落在某个锚点上的任务。
func matchAnchorTasks(rows []TaskRow, a TimeAnchor, taskName string) []TaskRow {
	out := make([]TaskRow, 0, len(rows))
	for _, r := range rows {
		if taskName != "" && !strings.Contains(r.Name, taskName) && !strings.Contains(taskName, r.Name) {
			continue
		}
		switch a.Kind {
		case "weekday":
			if !containsString(r.Weekdays, a.Weekday) {
				continue
			}
		case "date":
			if !taskOccursOnDate(r, a.Date) {
				continue
			}
		default:
			continue
		}
		// 锚点带钟点时还要对上时刻，否则「8点和9点对调」会把一整天都算进来
		if a.HasRange {
			mins, ok := parseTimeMinutes(r.StartText)
			if !ok || mins < a.StartMinutes || mins > a.EndMinutes {
				continue
			}
		}
		out = append(out, r)
	}
	return out
}
