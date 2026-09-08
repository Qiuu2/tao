package assistant

import (
	"strconv"
	"strings"
	"time"
)

// 任务与查询时间范围的比对。取自原实现的
// _task_occurs_on_date / _task_matches_dated_query_window / _task_matches_query_time。
//
// # 为什么不是简单地比一下开始时间
//
// 一条任务有时长。用户问「今天上午有什么」，一条 11:50 开始、放 20 分钟的任务
// 算不算？原实现的答案是算 —— 比的是**两个区间有没有重叠**，不是端点相等。
// 跨零点的任务（23:50 放 20 分钟）会被拆成两段分别比，这也是原实现的做法。
//
// # 星期与起止日期
//
// 一条任务是"每周一三五、9 月 1 日到 12 月 31 日之间、每天 8:00 放 3 分钟"。
// 所以判断"某一天会不会响"要三个条件都成立，缺一不可。

// TaskRow 是参与匹配的一条任务。字段都是从 task 表读出来后归一过的，
// 不直接搬数据库里的原始列 —— 比对逻辑不该关心 exemodel 是个什么串。
type TaskRow struct {
	ID   int64
	Name string
	// FolderID / FolderName 是**任务分组**（filetaskfree）——
	// 文件广播任务的目录树，与作息方案无关（BR-161）。
	// 需要方案名时看 Info，不要看这两个。
	FolderID   int64
	FolderName string
	// StartSeconds 是 playtime 换算成的当天秒数。
	StartSeconds int
	// DurationSeconds 至少为 1。
	DurationSeconds int
	StartDate       time.Time
	EndDate         time.Time
	HasDateSpan     bool
	// Weekdays 是「周一」…「周日」。空表示不限星期。
	Weekdays []string
	State    int
	TaskType int
	// Info 是这条任务所属的**作息方案名**。
	// ⚠ 作息方案在这套库里不是一张表，而是 task 里共享同一个 info 的一组行
	//   （见 bell 包的说明）。空串表示它不属于任何作息方案。
	Info string
	// StartText 是原始的 playtime 文本，回话里要按 HH:MM 展示。
	StartText string
}

// 与 helpers.py 的 weekday_label 对齐：周一…周日。
var weekdayLabels = [7]string{"周一", "周二", "周三", "周四", "周五", "周六", "周日"}

func weekdayLabelOf(t time.Time) string {
	// Go 的 Weekday()：周日 = 0；标签数组是周一打头。
	return weekdayLabels[(int(t.Weekday())+6)%7]
}

// taskOccursOnDate 取自 _task_occurs_on_date。
func taskOccursOnDate(task TaskRow, day time.Time) bool {
	if task.HasDateSpan {
		d := dateOnly(day)
		if d.Before(dateOnly(task.StartDate)) || d.After(dateOnly(task.EndDate)) {
			return false
		}
	}
	if len(task.Weekdays) == 0 {
		return true
	}
	label := weekdayLabelOf(day)
	for _, w := range task.Weekdays {
		if w == label {
			return true
		}
	}
	return false
}

func dateOnly(t time.Time) time.Time {
	return time.Date(t.Year(), t.Month(), t.Day(), 0, 0, 0, 0, t.Location())
}

// taskMatchesDatedQueryWindow 取自 _task_matches_dated_query_window。
//
// lookback 那一步不是多余的：一条 23:50 开始、放 2 小时的任务，
// 会响到第二天 1:50。查「今天凌晨」时得回头看昨天有没有这么一条。
func taskMatchesDatedQueryWindow(task TaskRow, queryStart, queryEnd time.Time) bool {
	if !queryEnd.After(queryStart) {
		queryEnd = queryStart.Add(time.Second)
	}
	duration := task.DurationSeconds
	if duration < 1 {
		duration = 1
	}
	lookbackDays := duration/86400 + 1
	if lookbackDays < 1 {
		lookbackDays = 1
	}
	current := dateOnly(queryStart).AddDate(0, 0, -lookbackDays)
	final := dateOnly(queryEnd)
	for !current.After(final) {
		if taskOccursOnDate(task, current) {
			taskStart := current.Add(time.Duration(task.StartSeconds) * time.Second)
			taskEnd := taskStart.Add(time.Duration(duration) * time.Second)
			if taskStart.Before(queryEnd) && queryStart.Before(taskEnd) {
				return true
			}
		}
		current = current.AddDate(0, 0, 1)
	}
	return false
}

type secondRange struct{ Start, End int }

// buildRanges 取自 _task_matches_query_time 里的 build_ranges：
// 把「某秒开始、放多久」摊成当天的一段或两段（跨零点时拆成两段）。
func buildRanges(startSeconds, spanSeconds int) []secondRange {
	const day = 24 * 3600
	if spanSeconds >= day {
		return []secondRange{{0, day}}
	}
	end := startSeconds + spanSeconds
	if end <= day {
		return []secondRange{{startSeconds, end}}
	}
	return []secondRange{{startSeconds, day}, {0, end % day}}
}

func rangesOverlap(left, right []secondRange) bool {
	for _, l := range left {
		for _, r := range right {
			if l.Start < r.End && r.Start < l.End {
				return true
			}
		}
	}
	return false
}

func secondsOfDay(t time.Time) int {
	return t.Hour()*3600 + t.Minute()*60 + t.Second()
}

// taskMatchesQueryTime 取自 _task_matches_query_time。
//
// 分支依据是「这个时间词里有没有日期的意思」：
//   - 有（「今天8点」）→ 按真实的日期窗口比，要考虑星期与起止日期
//   - 没有（「8点」）  → 只比一天里的钟点，哪天都算
func taskMatchesQueryTime(task TaskRow, f queryTimeFilters) bool {
	if !f.HasStart && !f.HasEnd {
		return true
	}
	duration := task.DurationSeconds
	if duration < 1 {
		duration = 1
	}
	taskRanges := buildRanges(task.StartSeconds, duration)

	switch {
	case f.HasStart && !f.HasEnd:
		if queryTimeHasDateScope(f.FilterStartRaw) {
			if queryTimeHasClockComponent(f.FilterStartRaw) {
				return taskMatchesDatedQueryWindow(task, f.Start, f.Start.Add(time.Minute))
			}
			return taskMatchesDatedQueryWindow(task, f.Start, f.Start.AddDate(0, 0, 1))
		}
		return rangesOverlap(taskRanges, buildRanges(secondsOfDay(f.Start), 60))
	case f.HasEnd && !f.HasStart:
		if queryTimeHasDateScope(f.FilterEndRaw) {
			if queryTimeHasClockComponent(f.FilterEndRaw) {
				return taskMatchesDatedQueryWindow(task, f.End, f.End.Add(time.Minute))
			}
			return taskMatchesDatedQueryWindow(task, f.End, f.End.AddDate(0, 0, 1))
		}
		return rangesOverlap(taskRanges, buildRanges(secondsOfDay(f.End), 60))
	default:
		if queryTimeHasDateScope(f.FilterStartRaw) || queryTimeHasDateScope(f.FilterEndRaw) {
			normalizedEnd := f.End
			if !queryTimeHasClockComponent(f.FilterEndRaw) {
				normalizedEnd = normalizedEnd.AddDate(0, 0, 1)
			}
			return taskMatchesDatedQueryWindow(task, f.Start, normalizedEnd)
		}
		startSeconds := secondsOfDay(f.Start)
		endSeconds := secondsOfDay(f.End)
		span := endSeconds - startSeconds
		if span < 0 {
			span += 24 * 3600
		}
		if span < 1 {
			span = 1
		}
		return rangesOverlap(taskRanges, buildRanges(startSeconds, span))
	}
}

// parseTimeSeconds 取自 _parse_time_seconds：把 "HH:MM:SS" 换成当天秒数。
func parseTimeSeconds(value string) (int, bool) {
	if value == "" {
		return 0, false
	}
	parts := strings.Split(value, ":")
	nums := make([]int, 3)
	for i := 0; i < 3; i++ {
		if i >= len(parts) {
			break
		}
		n, err := atoiStrict(parts[i])
		if err != nil {
			return 0, false
		}
		nums[i] = n
	}
	return nums[0]*3600 + nums[1]*60 + nums[2], true
}

func atoiStrict(s string) (int, error) {
	return strconv.Atoi(strings.TrimSpace(s))
}
