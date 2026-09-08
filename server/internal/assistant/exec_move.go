package assistant

import (
	"context"
	"fmt"
	"strings"
	"time"

	"htweb/internal/auth"
	"htweb/internal/enable"
	"htweb/internal/notify"
)

// move_schedule（挪动）与 swap_schedule（对调）。
//
// # 三种说法，原实现的三个场景
//
//	A 周维度   「把春季作息里周一的任务挪到周六」
//	B 日期维度 「把 1月24日 的任务挪到 2月1日」
//	C 时段     「把周五3-7点的任务挪到周六4-5点」
//
// 源和目标必须**同一维度**（都是星期或都是日期），否则"从周三挪到 2 月 1 日"
// 这种说法算不出该改星期还是改日期。原实现直接拒绝，这里一样。
//
// # 永久挪动改的是任务自己
//
//	星期维度：把星期掩码里的源星期换成目标星期；带钟点时再平移 playtime，
//	          平移跨了零点就连星期一起顺移（overflow）
//	日期维度：起止日期整体平移 day_delta 天，带钟点时再平移 playtime
//
// 逐条对着 _apply_move_anchor_to_task 抄的。
//
// # 一次性挪动 = 影子任务 + 原任务当天停一次
//
// 原实现的做法：复制一条只在那一天、那个新时刻执行的"影子任务"，
// 同时给原任务排一次性取消。这里照做 ——
//
//	影子任务  用 task.Copy 复制（媒体清单、终端清单、功放子任务一并带上），
//	          再把它钉到目标日期与新时刻，星期掩码清空（只此一天）
//	原任务    走与 cancel 一次性同一条路：enabletask 排停用 + 恢复
//
// 这样"这一次挪到别的时间"就成了：那天原任务不响，影子任务在新时刻响。
// 过了那天，影子任务因为 startdate=enddate=那天而自然失效，原任务照旧。

// replaceWeekday 取自 _replace_weekday。返回按周一→周日排好的去重列表。
func replaceWeekday(weekdays []string, source, target string) []string {
	ordered := []string{"周一", "周二", "周三", "周四", "周五", "周六", "周日"}
	seen := map[string]bool{}
	for _, d := range weekdays {
		if d == "" {
			continue
		}
		if d == source {
			d = target
		}
		seen[d] = true
	}
	out := make([]string, 0, len(seen))
	for _, d := range ordered {
		if seen[d] {
			out = append(out, d)
		}
	}
	return out
}

// shiftWeekdaysByDelta 取自 _shift_weekdays_by_delta / _rotate_weekdays。
func shiftWeekdaysByDelta(weekdays []string, dayDelta int) []string {
	ordered := []string{"周一", "周二", "周三", "周四", "周五", "周六", "周日"}
	index := map[string]int{}
	for i, d := range ordered {
		index[d] = i
	}
	offset := ((dayDelta % 7) + 7) % 7
	seen := map[string]bool{}
	if offset == 0 {
		for _, d := range weekdays {
			if d != "" {
				seen[d] = true
			}
		}
	} else {
		for _, d := range weekdays {
			i, ok := index[d]
			if !ok {
				continue
			}
			seen[ordered[(i+offset)%7]] = true
		}
	}
	out := make([]string, 0, len(seen))
	for _, d := range ordered {
		if seen[d] {
			out = append(out, d)
		}
	}
	return out
}

// shiftHHMMSSWithDayDelta 取自 _shift_hhmmss_with_day_delta：
// 平移一个 HH:MM:SS，返回新时刻和跨了几天。
func shiftHHMMSSWithDayDelta(value string, deltaMinutes int) (string, int) {
	raw := formatHHMMSS(value)
	parts := strings.Split(raw, ":")
	if len(parts) < 2 {
		return raw, 0
	}
	h, err1 := atoiStrict(parts[0])
	m, err2 := atoiStrict(parts[1])
	sec := 0
	if len(parts) >= 3 {
		if v, err := atoiStrict(parts[2]); err == nil {
			sec = v
		}
	}
	if err1 != nil || err2 != nil {
		return raw, 0
	}
	base := time.Date(2000, 1, 1, h, m, sec, 0, time.UTC)
	shifted := base.Add(time.Duration(deltaMinutes) * time.Minute)
	dayDelta := int(shifted.Truncate(24*time.Hour).Sub(base.Truncate(24*time.Hour)).Hours() / 24)
	return shifted.Format("15:04:05"), dayDelta
}

// formatHHMMSS 对应 helpers.py 的 format_hhmmss：补齐成 HH:MM:SS。
func formatHHMMSS(value string) string {
	value = strings.TrimSpace(value)
	if value == "" {
		return "00:00:00"
	}
	parts := strings.Split(value, ":")
	for len(parts) < 3 {
		parts = append(parts, "00")
	}
	out := make([]string, 3)
	for i := 0; i < 3; i++ {
		n, err := atoiStrict(parts[i])
		if err != nil {
			return "00:00:00"
		}
		out[i] = fmt.Sprintf("%02d", n)
	}
	return strings.Join(out, ":")
}

// exemodelFromWeekdays 把「周一」…「周日」拼回 7 位掩码。
// ⚠ 周日打头（第 1 位 = 周日），与 weekdaysFromExemodel 是一对，改一个就要改另一个。
func exemodelFromWeekdays(weekdays []string) string {
	order := [7]string{"周日", "周一", "周二", "周三", "周四", "周五", "周六"}
	seen := map[string]bool{}
	for _, d := range weekdays {
		seen[d] = true
	}
	out := []byte("0000000")
	for i, d := range order {
		if seen[d] {
			out[i] = '1'
		}
	}
	return string(out)
}

// movePlan 是一条任务挪动之后该变成什么样。
type movePlan struct {
	Task      TaskRow
	PlayTime  string // 新的 playtime
	EndTime   string // 新的 endtime（按原时长顺移）
	Exemodel  string
	StartDate time.Time
	EndDate   time.Time
	DateMoved bool
}

// planMove 取自 _apply_move_anchor_to_task。返回 false 表示这条任务挪不了
// （比如源星期根本不在它的星期掩码里）。
func planMove(r TaskRow, src, dst TimeAnchor) (movePlan, bool) {
	srcMin, hasSrc := anchorStartMinutes(src)
	dstMin, hasDst := anchorStartMinutes(dst)
	deltaMinutes := 0
	hasDelta := hasSrc && hasDst
	if hasDelta {
		deltaMinutes = dstMin - srcMin
	}

	p := movePlan{
		Task:      r,
		PlayTime:  formatHHMMSS(r.StartText),
		Exemodel:  exemodelFromWeekdays(r.Weekdays),
		StartDate: r.StartDate,
		EndDate:   r.EndDate,
	}

	if src.Kind == "weekday" {
		if !containsString(r.Weekdays, src.Weekday) {
			return movePlan{}, false
		}
		moved := replaceWeekday(r.Weekdays, src.Weekday, dst.Weekday)
		overflow := 0
		if hasDelta {
			p.PlayTime, overflow = shiftHHMMSSWithDayDelta(r.StartText, deltaMinutes)
		}
		if overflow != 0 {
			moved = shiftWeekdaysByDelta(moved, overflow)
			if r.HasDateSpan {
				p.StartDate = r.StartDate.AddDate(0, 0, overflow)
				p.EndDate = r.EndDate.AddDate(0, 0, overflow)
				p.DateMoved = true
			}
		}
		p.Exemodel = exemodelFromWeekdays(moved)
		p.EndTime = shiftEndTime(r, p.PlayTime)
		return p, true
	}

	// 日期维度
	if src.Date.IsZero() || dst.Date.IsZero() {
		return movePlan{}, false
	}
	dayDelta := int(dateOnly(dst.Date).Sub(dateOnly(src.Date)).Hours() / 24)
	if r.HasDateSpan {
		p.StartDate = r.StartDate.AddDate(0, 0, dayDelta)
		p.EndDate = r.EndDate.AddDate(0, 0, dayDelta)
	} else {
		p.StartDate = dateOnly(src.Date).AddDate(0, 0, dayDelta)
		p.EndDate = p.StartDate
	}
	p.DateMoved = true
	// ⚠ 日期维度下时刻平移只在**两边都带钟点**时才做，
	// 与 _apply_time_offset_to_task 一致：只有 time_start_minutes 都在才平移。
	if src.HasRange && dst.HasRange {
		newMinutes := 0
		if v, ok := parseTimeMinutes(r.StartText); ok {
			newMinutes = v + (dst.StartMinutes - src.StartMinutes)
		}
		h := clampInt(newMinutes/60, 0, 23)
		m := clampInt(newMinutes%60, 0, 59)
		p.PlayTime = fmt.Sprintf("%02d:%02d:00", h, m)
	}
	p.EndTime = shiftEndTime(r, p.PlayTime)
	return p, true
}

// shiftEndTime 让 endtime 跟着 playtime 走，保持原来的时长。
// endtime 在这套库里就是时长的依据（见 exec_task.go），只改开始不改结束
// 会让这条任务的时长悄悄变掉。
func shiftEndTime(r TaskRow, newPlayTime string) string {
	newStart, ok := parseTimeSeconds(newPlayTime)
	if !ok {
		return formatHHMMSS(r.StartText)
	}
	d := r.DurationSeconds
	if d < 1 {
		d = 1
	}
	end := (newStart + d) % (24 * 3600)
	return fmt.Sprintf("%02d:%02d:%02d", end/3600, (end%3600)/60, end%60)
}

func parseTimeMinutes(value string) (int, bool) {
	s, ok := parseTimeSeconds(value)
	if !ok {
		return 0, false
	}
	return s / 60, true
}

func clampInt(v, lo, hi int) int {
	if v < lo {
		return lo
	}
	if v > hi {
		return hi
	}
	return v
}

func containsString(list []string, v string) bool {
	for _, item := range list {
		if item == v {
			return true
		}
	}
	return false
}

// execMoveSchedule 挪动。
func (s *Service) execMoveSchedule(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
	if s.tasks == nil || s.enables == nil {
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
			Reply: askRuntimeReply("move_schedule", "源时间和目标时间",
				"比如把周三早读挪到周五，或者把 8 点的任务改到 9 点"),
			MissingSlots: missing,
		}
	}

	src, okS := ParseTimeAnchor(sourceText)
	dst, okT := ParseTimeAnchor(targetText)
	if !okS || !okT {
		return actionResult{
			Reply: "这两个时间我还没完全听明白。您可以换成“周三”“周五”或者“2月10日”这类说法。",
		}
	}
	if src.Kind != dst.Kind {
		return actionResult{Reply: "源时间和目标时间需要同一维度（都为星期，或都为日期）。"}
	}

	resolvedSchedule, res := s.resolveSchedulePlan(ctx, u, raw, scheduleName)
	if res != nil {
		return *res
	}

	rows, err := s.queryTaskRows(ctx, u, resolvedSchedule)
	if err != nil {
		return actionResult{Err: err}
	}

	var plans []movePlan
	var names []string
	for _, r := range rows {
		if taskName != "" && !strings.Contains(r.Name, taskName) && !strings.Contains(taskName, r.Name) {
			continue
		}
		if src.Kind == "date" && !taskOccursOnDate(r, src.Date) {
			continue
		}
		p, ok := planMove(r, src, dst)
		if !ok {
			continue
		}
		plans = append(plans, p)
		names = append(names, fmt.Sprintf("%s(%s→%s)",
			r.Name, formatHHMM(r.StartText), formatHHMM(p.PlayTime)))
	}
	if len(plans) == 0 {
		return actionResult{Reply: replyNoMatchingTasks("挪动", []string{sourceText, targetText})}
	}

	mode, confirmed := applyModeOf(slots)
	if !confirmed {
		scope := "当前启用中的作息方案"
		if resolvedSchedule != "" {
			scope = "作息方案“" + resolvedSchedule + "”"
		}
		summary := fmt.Sprintf("%s里要从「%s」挪到「%s」的有 %d 条任务：%s",
			scope, sourceText, targetText, len(plans), previewNames(names, 5, "条任务"))
		keep := map[string][]string{}
		for k, v := range slots {
			if !strings.HasPrefix(k, "__") {
				keep[k] = v
			}
		}
		return askApplyMode(IntentMoveSchedule, raw, keep, summary)
	}

	if mode == modeOnce {
		return s.moveOnce(ctx, u, resolvedSchedule, plans, names, src, dst)
	}
	return s.movePermanent(ctx, u, resolvedSchedule, plans, names, sourceText, targetText)
}

// resolveSchedulePlan 把用户说的方案名对到真实的作息方案名。
// 没说方案名时返回空串，表示"不限方案"。
//
// ⚠ 返回的是**名字**不是 id：作息方案没有 id，名字（task.info）就是主键。
func (s *Service) resolveSchedulePlan(ctx context.Context, u *auth.User,
	raw, scheduleName string) (string, *actionResult) {

	if scheduleName == "" {
		return "", nil
	}
	plans, err := s.scheduleCandidates(ctx, u)
	if err != nil {
		return "", &actionResult{Err: err}
	}
	res := s.ResolveName(ctx, scheduleName, raw, plans)
	if res.Matched == "" {
		return "", &actionResult{Reply: replyScheduleNotFound(scheduleName)}
	}
	return res.Matched, nil
}

// movePermanent 直接改任务自己的时间与星期。
func (s *Service) movePermanent(ctx context.Context, u *auth.User, scheduleName string,
	plans []movePlan, names []string, sourceText, targetText string) actionResult {

	ids := make([]int64, 0, len(plans))
	for _, p := range plans {
		ids = append(ids, p.Task.ID)
	}
	// 归属校验借 SetProjectState 那条路走不通（它会顺手改状态），
	// 所以这里自己查一遍 —— 与 task.Control 同一口径。
	if blocked := s.rejectNotOwned(ctx, u, ids); blocked != nil {
		return *blocked
	}

	moved, err := s.applyMovePlans(ctx, plans)
	if err != nil {
		return actionResult{Err: err}
	}
	// 时间变了要通知后台重新装载，与页面上改任务后一样。
	// ⚠ 逐条发，且要带上这条任务自己的音量 —— 报文形态是
	// task?state=5&id=Y&volume=V，缺 volume 后台解析不了。
	s.notifyTasksSaved(ctx, notify.TaskUpdated, ids)

	reply := fmt.Sprintf("已把 %d 条任务从「%s」挪到「%s」：%s。",
		moved, sourceText, targetText, previewNames(names, 5, "条任务"))
	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "move_schedule", "mode": "permanent", "schedule_name": scheduleName,
			"details": map[string]any{
				"count": moved, "task_ids": ids,
				"source_time": sourceText, "target_time": targetText,
			},
		}},
	}
}

// applyMovePlans 把一批挪动落库。一个事务，要么全成要么全不成 ——
// 挪一半的作息表比没挪更难收拾。
func (s *Service) applyMovePlans(ctx context.Context, plans []movePlan) (int, error) {
	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	n := 0
	for _, p := range plans {
		if p.DateMoved && !p.StartDate.IsZero() {
			_, err = tx.ExecContext(ctx,
				`UPDATE task SET playtime=?, endtime=?, exemodel=?, startdate=?, enddate=?
				 WHERE taskid=?`,
				p.PlayTime, p.EndTime, p.Exemodel,
				p.StartDate.Format("2006-01-02"), p.EndDate.Format("2006-01-02"), p.Task.ID)
		} else {
			_, err = tx.ExecContext(ctx,
				`UPDATE task SET playtime=?, endtime=?, exemodel=? WHERE taskid=?`,
				p.PlayTime, p.EndTime, p.Exemodel, p.Task.ID)
		}
		if err != nil {
			return 0, fmt.Errorf("挪动任务 %d: %w", p.Task.ID, err)
		}
		n++
	}
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return n, nil
}

// rejectNotOwned 挡住不属于当前用户的任务。与 task.Control 的 NOT_OWNER 同一口径。
func (s *Service) rejectNotOwned(ctx context.Context, u *auth.User, ids []int64) *actionResult {
	if u.IsAdmin || len(ids) == 0 {
		return nil
	}
	for _, id := range ids {
		var owner int64
		err := s.db.QueryRowContext(ctx,
			`SELECT COALESCE(task_user_id,0) FROM task WHERE taskid=? LIMIT 1`, id).Scan(&owner)
		if err != nil {
			return &actionResult{Err: fmt.Errorf("校验任务归属: %w", err)}
		}
		if owner != u.ID {
			return &actionResult{Reply: "这里面有不是您创建的任务，我不能替您改。"}
		}
	}
	return nil
}

// moveOnce 一次性挪动：复制影子任务钉到那天的新时刻，原任务当天停一次。
func (s *Service) moveOnce(ctx context.Context, u *auth.User, scheduleName string,
	plans []movePlan, names []string, src, dst TimeAnchor) actionResult {

	if src.Kind != "date" || dst.Kind != "date" {
		// 星期维度下"就这一次"没有确定的那一天可钉 —— 原实现同样要求具体日期
		return actionResult{Reply: "一次性挪动需要具体日期，请补充日期。"}
	}
	targetDay := dateOnly(dst.Date)
	sourceDay := dateOnly(src.Date)

	var shadowIDs []int64
	var disableTasks, restoreTasks []enable.TaskAction
	var winStart, winEnd time.Time

	for _, p := range plans {
		// 影子任务放回源任务所在的分组（filetaskfree 只是目录，与方案无关）
		folder := p.Task.FolderID
		if folder <= 0 {
			folder = 1 // filetaskfree 的默认组
		}
		shadowName := shadowTaskName(p.Task, targetDay)
		cp, err := s.tasks.Copy(ctx, u, p.Task.ID, folder, shadowName)
		if err != nil {
			s.cleanupShadows(ctx, u, shadowIDs)
			return actionResult{
				Reply: failureRuntimeReply("move_schedule", "一次性挪动", nil, err.Error(),
					"您可以换个说法再试一次。"),
			}
		}
		shadowIDs = append(shadowIDs, cp.TaskID)

		// 把影子钉死在目标那一天、新时刻。
		//
		// ⚠ 星期掩码设成**目标那天的那一位**，不是原实现的全 0。
		//
		// 原实现给影子写 execmode = 0，那是它那套远端的语义。在这套库里
		// exemodel = "0000000" 的意思是「手动」（见 dashboard 的 cycleText：
		// 0 天 = 手动），后台不会自动执行它 —— 影子建了却永远不响，
		// 而用户已经被告知"改到那天执行"了。这是最坏的一种失败：安静地不发生。
		//
		// 起止日期都钉成目标那一天，再把那天的星期位打开：日期范围里只有这一天，
		// 星期又对得上，所以它恰好执行一次。
		if _, err := s.db.ExecContext(ctx,
			`UPDATE task SET playtime=?, endtime=?, exemodel=?,
			        startdate=?, enddate=?, projectstate=0
			 WHERE taskid=? OR sec_task_id=?`,
			p.PlayTime, p.EndTime, exemodelFromWeekdays([]string{weekdayLabelOf(targetDay)}),
			targetDay.Format("2006-01-02"), targetDay.Format("2006-01-02"),
			cp.TaskID, cp.TaskID); err != nil {
			s.cleanupShadows(ctx, u, shadowIDs)
			return actionResult{Err: fmt.Errorf("钉住影子任务: %w", err)}
		}

		// 原任务在源那天停一次
		disableTasks = append(disableTasks, enable.TaskAction{TaskID: p.Task.ID, Action: 1})
		restoreTasks = append(restoreTasks, enable.TaskAction{TaskID: p.Task.ID, Action: 0})
		ts := sourceDay.Add(time.Duration(p.Task.StartSeconds) * time.Second)
		d := p.Task.DurationSeconds
		if d < 1 {
			d = 1
		}
		te := ts.Add(time.Duration(d) * time.Second)
		if winStart.IsZero() || ts.Before(winStart) {
			winStart = ts
		}
		if winEnd.IsZero() || te.After(winEnd) {
			winEnd = te
		}
	}

	now := nowFunc()
	disableAt := winStart.Add(-cancelAnchorLead)
	if !disableAt.After(now) {
		disableAt = now.Add(time.Minute)
	}
	restoreAt := winEnd
	if !restoreAt.After(disableAt) {
		restoreAt = disableAt.Add(time.Minute)
	}

	disableID, err := s.enables.Create(ctx, enable.Input{
		StartDate: disableAt.Format("2006-01-02"), StartTime: disableAt.Format("15:04:05"),
		Tasks: disableTasks,
	})
	if err != nil {
		s.cleanupShadows(ctx, u, shadowIDs)
		return actionResult{
			Reply: failureRuntimeReply("move_schedule", "一次性挪动", nil, err.Error(),
				"您可以换个说法再试一次。"),
		}
	}
	restoreID, err := s.enables.Create(ctx, enable.Input{
		StartDate: restoreAt.Format("2006-01-02"), StartTime: restoreAt.Format("15:04:05"),
		Tasks: restoreTasks,
	})
	if err != nil {
		// 只停不恢复是最坏的结果 —— 把停用计划和影子一起撤掉
		if _, delErr := s.enables.Delete(ctx, []int64{disableID}); delErr != nil {
			logf("一次性挪动回滚失败：停用计划 %d 没能删掉：%v", disableID, delErr)
		}
		s.cleanupShadows(ctx, u, shadowIDs)
		return actionResult{
			Reply: failureRuntimeReply("move_schedule", "一次性挪动", nil, err.Error(),
				"您可以换个说法再试一次。"),
		}
	}
	s.notifyTasksSaved(ctx, notify.TaskAdded, shadowIDs)

	reply := fmt.Sprintf("已为 %s 安排一次性挪动：%s 那天原任务不响，改在 %s 执行。",
		previewNames(names, 3, "条任务"),
		sourceDay.Format("2006-01-02"), targetDay.Format("2006-01-02"))
	reply = appendReplyDetails(reply,
		"只影响这一天，之后照旧。临时任务与两条启用计划都在页面上看得见。")

	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "move_schedule", "mode": "once", "schedule_name": scheduleName,
			"details": map[string]any{
				"count": len(plans), "shadow_task_ids": shadowIDs,
				"disable_plan_id": disableID, "restore_plan_id": restoreID,
				"source_date": sourceDay.Format("2006-01-02"),
				"target_date": targetDay.Format("2006-01-02"),
			},
		}},
	}
}

// shadowTaskName 给影子任务起名。
//
// ⚠ filetaskfree/task 的名字列很短（taskname varchar(255) 够用，
// 但页面上列宽有限），所以名字要短而且**一眼能看出是临时的**：
// 原名截断 + _once_ + 日期。原实现的命名是
// `{原名}_once_{源任务id}_{时间戳}`，这里保留同样的形态。
func shadowTaskName(r TaskRow, day time.Time) string {
	base := strings.TrimSpace(r.Name)
	if base == "" {
		base = "shadow-task"
	}
	// 按 rune 截断，别把汉字劈开
	runes := []rune(base)
	if len(runes) > 12 {
		base = string(runes[:12])
	}
	return fmt.Sprintf("%s_once_%d_%s", base, r.ID, day.Format("20060102"))
}

// cleanupShadows 把已经建出来的影子任务删掉，用于中途失败时回到原状。
func (s *Service) cleanupShadows(ctx context.Context, u *auth.User, ids []int64) {
	if len(ids) == 0 {
		return
	}
	if _, err := s.tasks.Delete(ctx, u, ids); err != nil {
		logf("一次性挪动回滚：影子任务 %v 没能删掉：%v", ids, err)
	}
}

// notifyTasksSaved 逐条发新增/修改通知，音量取任务自己的 defaultvolume。
func (s *Service) notifyTasksSaved(ctx context.Context, state notify.State, ids []int64) {
	if s.notifier == nil || len(ids) == 0 {
		return
	}
	for _, id := range ids {
		volume := 80
		_ = s.db.QueryRowContext(ctx,
			`SELECT COALESCE(defaultvolume,80) FROM task WHERE taskid=? LIMIT 1`, id).Scan(&volume)
		s.notifier.TaskSaved(ctx, state, id, volume)
	}
}
