package assistant

import (
	"context"
	"fmt"
	"strings"
	"time"

	"htweb/internal/auth"
	"htweb/internal/enable"
)

// cancel_schedule：取消某个时间段里的任务。
//
// # 两种取消，机制完全不同
//
//	一次性  只让这一段时间不响，过后自动恢复
//	永久    这条任务以后都不放了
//
// 猜错哪一种都是错的，而且错得不明显 —— 用户以为只停一天，实际停到了永远。
// 所以先问一句（见 pending.go），答上来了再动手。
//
// # 一次性取消落在 enabletask 上
//
// 这不是我挑的实现方式，是**原实现就是这么做的**：它的注释写着
// 「一次性取消：通过 enabletask 定时禁用/恢复」，下发两批指令 ——
// 到 time_start 把这些任务置停用（enstate=1），到 time_end 再置回启用（enstate=0）。
//
// 而 enabletask 正是这套库里 tao 的「启用管理」那张表。所以这里不用新造机制，
// 直接建两条启用计划，与页面上手工排的一模一样，运维在「启用管理」页面上
// 看得见、改得动、删得掉 —— 助手排下去的东西不该是只有助手自己知道的黑箱。
//
// ⚠ enstate **0 = 启用、1 = 停用**（与 task.projectstate 一致，与
// holidaytime.projectstate 相反）。搞反的后果是"取消"变成"到点开始播"。
//
// # 永久取消 = 停用那些任务，不是删除
//
// ⚠ 这里**有意偏离**原实现。原实现的永久取消是把任务从方案里**删掉**
// （连同远端任务一起删）。在这套库里，删任务会连带删掉它的媒体清单、
// 终端清单、功放子任务、LED 子任务，而且没有撤销。
//
// 「取消」这个词在中文里不等于「删除」—— 用户说"以后都别放了"，意思是别响，
// 不是"把这条任务从系统里抹掉，以后想恢复得重建一遍"。这套库里恰好有一个
// 精确表达"别响"的东西：projectstate。所以永久取消走停用。
//
// 真要删，用户会说"删除"，那是 delete_schedule 的事，而且那一条会先问一句。

// onceCancelDoneVariants 逐字取自原实现。
var onceCancelDoneVariants = []string{
	"一次性取消帮您安排上啦~ 任务执行完会自动恢复。",
	"好嘞，先帮您临时取消一次，过了这个时段会自动复原哈~",
	"搞定，这次取消已经设好啦，事后会自动恢复~",
}

// composeCancelOnceReply 取自 runtime_reply.py 的 compose_cancel_once_reply。
//
// ⚠ 里面那个逗号是**半角的 ,**，不是中文逗号 —— 原实现就是这么写的。
// 这套库里没有"周期广播"这个独立概念（文件广播就是任务），所以广播那一支
// 现在恒为 0；函数照搬是为了留住措辞，将来真要区分时不用再对一遍。
func composeCancelOnceReply(scheduleGroups, scheduleTasks, broadcastGroups, broadcastTasks int) string {
	clamp := func(n int) int {
		if n < 0 {
			return 0
		}
		return n
	}
	scheduleGroups, scheduleTasks = clamp(scheduleGroups), clamp(scheduleTasks)
	broadcastGroups = clamp(broadcastGroups)
	_ = clamp(broadcastTasks)

	var parts []string
	if scheduleGroups > 0 {
		parts = append(parts, fmt.Sprintf("%d 个启用中的作息方案,共 %d 条任务", scheduleGroups, scheduleTasks))
	}
	if broadcastGroups > 0 {
		parts = append(parts, fmt.Sprintf("%d 条周期广播", broadcastGroups))
	}
	if len(parts) == 0 {
		return "已执行一次性取消。"
	}
	return "已对 " + strings.Join(parts, "、") + " 执行一次性取消。"
}

// execCancelSchedule 取消任务。
func (s *Service) execCancelSchedule(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
	if s.tasks == nil || s.enables == nil {
		return actionResult{Err: fmt.Errorf("任务服务未接入")}
	}
	raw := rawTextOf(slots)
	scheduleName := slotText(slots, "schedule_name", "schedule_id", "SCHEDULE", "SCHEDULE_ID")
	taskName := slotText(slots, "task_name", "TASK", "CONTENT", "task")

	startValue := slotText(slots, "source_time", "time_range_start", "start_time", "start", "time")
	endValue := slotText(slots, "end_time", "time_range_end", "end")

	// 时间段必填 —— 不给时间就取消，范围会大到不可预期
	if startValue == "" && endValue == "" {
		return actionResult{
			Reply: askRuntimeReply("cancel_schedule", "要取消的时间范围",
				"比如今天上午八点到九点，或者周三早读这类说法"),
			MissingSlots: []string{"time_range_start", "time_range_end"},
		}
	}

	f := normalizeQueryTaskTimeFilters(raw, startValue, endValue)
	if f.StartRaw != "" && !f.HasStart && f.EndRaw != "" {
		return actionResult{Reply: "开始时间我还没听明白，您换一种说法我就继续处理。"}
	}
	if f.EndRaw != "" && !f.HasEnd {
		return actionResult{Reply: "结束时间我还没听明白，您换一种说法我就继续处理。"}
	}
	if f.StartRaw != "" && f.EndRaw == "" && !f.HasStart {
		return actionResult{
			Reply: "这个时间点我还没解析清楚。您可以换成“周三”“周五”或者“2月10日”这类说法。",
		}
	}
	if f.HasStart && f.HasEnd && f.End.Before(f.Start) &&
		!(f.FilterEndRaw != "" && containsDateWord(f.FilterEndRaw)) {
		f.End = f.End.AddDate(0, 0, 1)
	}

	// 时段已经过去了就别办了 —— 排一条已经过期的启用计划，后台永远不会执行
	if f.HasEnd && !f.End.After(nowFunc()) && queryTimeHasDateScope(f.FilterEndRaw) {
		return actionResult{Reply: "该时间段已结束，请改说明天或未来的具体日期。"}
	}

	// 找出要取消哪些任务
	resolvedSchedule, res := s.resolveSchedulePlan(ctx, u, raw, scheduleName)
	if res != nil {
		return *res
	}

	rows, err := s.queryTaskRows(ctx, u, resolvedSchedule)
	if err != nil {
		return actionResult{Err: err}
	}
	// 已经停用的任务不必再取消 —— 把它们算进去只会让回话里的条数虚高
	live := rows[:0:0]
	for _, r := range rows {
		if r.State == 0 {
			live = append(live, r)
		}
	}
	rows = live

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

	if len(matched) == 0 {
		timeHint := ""
		if f.StartRaw != "" || f.EndRaw != "" {
			timeHint = f.StartRaw + "至" + f.EndRaw
		}
		taskHint := ""
		if taskName != "" {
			taskHint = "任务“" + taskName + "”"
		}
		if resolvedSchedule != "" {
			return actionResult{
				Reply: fmt.Sprintf("在“%s”中未找到匹配%s%s的任务。", resolvedSchedule, timeHint, taskHint)}
		}
		return actionResult{
			Reply: fmt.Sprintf("在当前启用中的作息方案里未找到匹配%s%s的任务。", timeHint, taskHint)}
	}

	ids := make([]int64, 0, len(matched))
	names := make([]string, 0, len(matched))
	for _, r := range matched {
		ids = append(ids, r.ID)
		names = append(names, fmt.Sprintf("%s(%s)", r.Name, formatHHMM(r.StartText)))
	}

	// 还没确认过 → 把匹配到什么说清楚，再问「这次还是永久」
	mode, confirmed := applyModeOf(slots)
	if !confirmed {
		scope := resolvedSchedule
		if scope == "" {
			scope = "当前启用中的作息方案"
		} else {
			scope = "作息方案“" + scope + "”"
		}
		summary := fmt.Sprintf("%s里匹配到 %d 条任务：%s",
			scope, len(matched), previewNames(names, 5, "条任务"))
		keep := map[string][]string{}
		for k, v := range slots {
			if !strings.HasPrefix(k, "__") {
				keep[k] = v
			}
		}
		return askApplyMode(IntentCancelSchedule, raw, keep, summary)
	}

	if mode == modeOnce {
		return s.cancelOnce(ctx, u, resolvedSchedule, matched, names, f)
	}
	return s.cancelPermanent(ctx, u, resolvedSchedule, ids, names)
}

// cancelOnce 排两条启用计划：到点停用，过后恢复。
//
// # 停用/恢复的时刻取自**任务自己的时间窗**，不是用户说的那个时间词
//
// 用户说「取消明天早读」，「明天」只给出一个日期，没有钟点。照它去排
// 就得从 00:00 停到 23:59 —— 那一整天别的任务也一起哑了。
//
// 原实现的 _resolve_cancel_once_time_range 是这么解的：拿匹配到的这些任务
// 在锚点那天的最早开始与最晚结束，围出一个刚好包住它们的窗口。这里照搬。
//
// 停用时刻还要**提前 30 秒**（原实现的 REMOTE_ANCHOR_LEAD_SECONDS）——
// 卡在任务开始的那一秒才停用，任务可能已经响出去了。
const cancelAnchorLead = 30 * time.Second

func (s *Service) cancelOnce(ctx context.Context, u *auth.User, scheduleName string,
	matched []TaskRow, names []string, f queryTimeFilters) actionResult {

	ids := make([]int64, 0, len(matched))
	for _, r := range matched {
		ids = append(ids, r.ID)
	}

	winStart, winEnd, ok := cancelOnceWindow(matched, f)
	if !ok {
		return actionResult{Reply: "一次性取消需要具体时间段，请补充开始和结束时间。"}
	}

	// disable 锚点不能落在过去 —— 后台扫到一条已经过期的计划不会执行它。
	now := nowFunc()
	disableAt := winStart.Add(-cancelAnchorLead)
	if !disableAt.After(now) {
		disableAt = now.Add(time.Minute)
	}
	restoreAt := winEnd
	if !restoreAt.After(disableAt) {
		restoreAt = disableAt.Add(time.Minute)
	}

	disableTasks := make([]enable.TaskAction, 0, len(ids))
	restoreTasks := make([]enable.TaskAction, 0, len(ids))
	for _, id := range ids {
		// ⚠ 0 = 启用、1 = 停用
		disableTasks = append(disableTasks, enable.TaskAction{TaskID: id, Action: 1})
		restoreTasks = append(restoreTasks, enable.TaskAction{TaskID: id, Action: 0})
	}

	disableID, err := s.enables.Create(ctx, enable.Input{
		StartDate: disableAt.Format("2006-01-02"),
		StartTime: disableAt.Format("15:04:05"),
		Tasks:     disableTasks,
	})
	if err != nil {
		return actionResult{
			Reply: failureRuntimeReply("cancel_schedule", "一次性取消", nil, err.Error(),
				"您可以换个时间范围再说一次。"),
		}
	}
	restoreID, err := s.enables.Create(ctx, enable.Input{
		StartDate: restoreAt.Format("2006-01-02"),
		StartTime: restoreAt.Format("15:04:05"),
		Tasks:     restoreTasks,
	})
	if err != nil {
		// ⚠ 停用计划已经排下去了，恢复计划没排上 —— 这些任务会**一直停着**。
		// 把停用那条撤掉，回到什么都没发生的状态，再如实说没做成。
		// 悄悄留下一条只停不恢复的计划，是这一路最坏的失败方式。
		if _, delErr := s.enables.Delete(ctx, []int64{disableID}); delErr != nil {
			logf("一次性取消回滚失败：停用计划 %d 没能删掉：%v", disableID, delErr)
			return actionResult{
				Reply: fmt.Sprintf("一次性取消没做成，而且我留下了一条停用计划（编号 %d）没能撤掉，"+
					"麻烦到「启用管理」里把它删掉。", disableID),
			}
		}
		return actionResult{
			Reply: failureRuntimeReply("cancel_schedule", "一次性取消", nil, err.Error(),
				"您可以换个时间范围再说一次。"),
		}
	}

	// 措辞逐字取自原实现的 once_cancel_done。种子那一位原实现放的是
	// diagnostic_id（一个每次都不同的随机串），这里放两条计划的编号 ——
	// 同样是一次一变，而且它指得到东西：运维照着这个号能在「启用管理」里找到。
	diag := fmt.Sprintf("%d-%d", disableID, restoreID)
	reply := successRuntimeReply("once_cancel_done", onceCancelDoneVariants,
		[]string{diag}, nil)
	reply = appendReplyDetails(reply,
		fmt.Sprintf("停用于 %s，恢复于 %s，涉及 %s。",
			disableAt.Format("2006-01-02 15:04"), restoreAt.Format("2006-01-02 15:04"),
			previewNames(names, 3, "条任务")))

	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "cancel_schedule", "mode": "once", "schedule_name": scheduleName,
			"details": map[string]any{
				"count": len(ids), "task_ids": ids,
				"disable_plan_id": disableID, "restore_plan_id": restoreID,
				"time_start": disableAt.Format("2006-01-02 15:04:05"),
				"time_end":   restoreAt.Format("2006-01-02 15:04:05"),
			},
		}},
	}
}

// cancelOnceWindow 围出刚好包住这些任务的时间窗。取自 _resolve_cancel_once_time_range
// 与 _tasks_window：锚点那天里，最早的开始到最晚的结束。
func cancelOnceWindow(matched []TaskRow, f queryTimeFilters) (time.Time, time.Time, bool) {
	if len(matched) == 0 {
		return time.Time{}, time.Time{}, false
	}
	// 锚点日：用户给了起点就用它那一天，没给就用今天
	anchor := nowFunc()
	if f.HasStart {
		anchor = f.Start
	} else if f.HasEnd {
		anchor = f.End
	}
	day := time.Date(anchor.Year(), anchor.Month(), anchor.Day(), 0, 0, 0, 0, anchor.Location())

	var start, end time.Time
	for _, r := range matched {
		ts := day.Add(time.Duration(r.StartSeconds) * time.Second)
		d := r.DurationSeconds
		if d < 1 {
			d = 1
		}
		te := ts.Add(time.Duration(d) * time.Second)
		if start.IsZero() || ts.Before(start) {
			start = ts
		}
		if end.IsZero() || te.After(end) {
			end = te
		}
	}
	if start.IsZero() || end.IsZero() {
		return time.Time{}, time.Time{}, false
	}
	// 用户自己说了完整区间（「今天8点到10点」）就以他说的为准 ——
	// 他可能想连带盖住那个时段里别的东西。
	if f.HasStart && f.HasEnd && queryTimeHasClockComponent(f.FilterStartRaw) {
		return f.Start, f.End, true
	}
	return start, end, true
}

// cancelPermanent 把这些任务停用。见文件头对"不是删除"的说明。
func (s *Service) cancelPermanent(ctx context.Context, u *auth.User, scheduleName string,
	ids []int64, names []string) actionResult {

	out, err := s.tasks.SetProjectState(ctx, u, ids, false)
	if err != nil {
		return actionResult{Err: err}
	}
	if len(out.Succeeded) == 0 {
		reason := "没有可取消的任务"
		if len(out.Blocked) > 0 {
			reason = blockedReasonText(out.Blocked[0])
		}
		return actionResult{
			Reply: failureRuntimeReply("cancel_schedule", "永久取消", nil, reason, ""),
			ActionLog: []map[string]any{{
				"intent": "cancel_schedule", "mode": "permanent", "schedule_name": scheduleName,
				"details": map[string]any{"count": 0, "blocked": blockedDetails(out.Blocked)},
			}},
		}
	}

	reply := fmt.Sprintf("已停用 %d 条任务：%s。以后都不会再播了。",
		len(out.Succeeded), previewNames(names, 5, "条任务"))
	// 说清楚它是停用不是删除，并且怎么恢复 —— 用户说的是"取消"，
	// 他需要知道东西还在、还找得回来。
	reply = appendReplyDetails(reply, "任务还在，随时可以让我重新启用，或者在页面上改回来。")
	if len(out.Blocked) > 0 {
		reply = appendReplyDetails(reply,
			fmt.Sprintf("另有 %d 条没能停用：%s。", len(out.Blocked), blockedReasonText(out.Blocked[0])))
	}

	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "cancel_schedule", "mode": "permanent", "schedule_name": scheduleName,
			"details": map[string]any{
				"count": len(out.Succeeded), "task_ids": out.Succeeded,
				"blocked": blockedDetails(out.Blocked),
			},
		}},
	}
}
