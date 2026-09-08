package assistant

import (
	"encoding/json"
	"os"
	"strings"
	"testing"
	"time"
)

// 任务与查询时间范围比对的黄金用例。期望值同样是**把原实现里的函数原样抠出来跑**
// 生成的（scratchpad/gen_match_golden.py）。
//
// 用例里的任务直接给定"放多少秒"，不走 timelength/timelengthtype 换算 ——
// 那一处是**有意偏离**的（这套库里 timelengthtype=2 是"按循环次数"，
// 不是原实现以为的"按分钟"，见 exec_task.go 的说明）。
// 这组用例卡的是比对算法本身，偏离的那一处由 TestTaskDurationSeconds 单独盯。

type matchGolden struct {
	Now   string `json:"now"`
	Tasks []struct {
		Name      string   `json:"name"`
		StartTime string   `json:"starttime"`
		Duration  int      `json:"duration_seconds"`
		Weekdays  []string `json:"weekdays"`
		StartDate string   `json:"startdate"`
		EndDate   string   `json:"enddate"`
	} `json:"tasks"`
	Cases []struct {
		Name       string   `json:"name"`
		Text       string   `json:"text"`
		StartValue string   `json:"start_value"`
		EndValue   string   `json:"end_value"`
		WantHits   []string `json:"want_hits"`
	} `json:"cases"`
}

func TestTaskMatchesQueryTimeMatchesPython(t *testing.T) {
	raw, err := os.ReadFile("testdata/match_golden.json")
	if err != nil {
		t.Fatalf("读黄金用例: %v", err)
	}
	var g matchGolden
	if err := json.Unmarshal(raw, &g); err != nil {
		t.Fatalf("解析黄金用例: %v", err)
	}
	now, err := time.ParseInLocation(goldenLayout, g.Now, time.Local)
	if err != nil {
		t.Fatalf("解析用例基准时间: %v", err)
	}
	freezeNow(t, now)

	tasks := make([]TaskRow, 0, len(g.Tasks))
	for _, spec := range g.Tasks {
		r := TaskRow{
			Name:            spec.Name,
			StartText:       spec.StartTime,
			DurationSeconds: spec.Duration,
			Weekdays:        spec.Weekdays,
		}
		r.StartSeconds, _ = parseTimeSeconds(spec.StartTime)
		if spec.StartDate != "" && spec.EndDate != "" {
			r.StartDate, _ = time.ParseInLocation("2006-01-02", spec.StartDate, time.Local)
			r.EndDate, _ = time.ParseInLocation("2006-01-02", spec.EndDate, time.Local)
			r.HasDateSpan = true
		}
		tasks = append(tasks, r)
	}

	for _, c := range g.Cases {
		t.Run(c.Name, func(t *testing.T) {
			f := normalizeQueryTaskTimeFilters(c.Text, c.StartValue, c.EndValue)
			// _apply_query_task_intent 里的跨夜顺延，执行器里也是这一段
			if f.HasStart && f.HasEnd && f.End.Before(f.Start) &&
				!(f.FilterEndRaw != "" && containsDateWord(f.FilterEndRaw)) {
				f.End = f.End.AddDate(0, 0, 1)
			}
			var hits []string
			for _, task := range tasks {
				if taskMatchesQueryTime(task, f) {
					hits = append(hits, task.Name)
				}
			}
			got := strings.Join(hits, "、")
			want := strings.Join(c.WantHits, "、")
			if got != want {
				t.Fatalf("命中的任务不一致\n期望（Python）: %s\n实际（Go）    : %s", want, got)
			}
		})
	}
}

// 时长推导是**有意与原实现不同**的一处，单独钉住。
//
// 原实现按 timelengthtype == 2 判"按分钟"；这套库里 2 是"按循环次数"。
// 照抄会把"放 3 遍"算成"放 3 分钟"，匹配窗口整个错位。
func TestTaskDurationSeconds(t *testing.T) {
	cases := []struct {
		name                string
		playtime, endtime   string
		timelength, lenType int
		want                int
	}{
		{"endtime 优先", "07:20:00", "07:20:30", 999, 1, 30},
		{"跨零点", "23:50:00", "01:50:00", 0, 1, 2 * 3600},
		{"endtime 没填时退回秒数", "00:00:00", "00:00:00", 30, 1, 30},
		{"按循环次数时 timelength 不是秒", "00:00:00", "00:00:00", 3, 2, 1},
		{"什么都没有兜底 1 秒", "00:00:00", "00:00:00", 0, 1, 1},
	}
	for _, c := range cases {
		t.Run(c.name, func(t *testing.T) {
			got := taskDurationSeconds(c.playtime, c.endtime, c.timelength, c.lenType)
			if got != c.want {
				t.Fatalf("期望 %d 秒，实际 %d 秒", c.want, got)
			}
		})
	}
}

// exemodel 是**周日打头**的 7 位串。搞反的后果是每条任务的星期整体错一天。
func TestWeekdaysFromExemodel(t *testing.T) {
	cases := []struct {
		mask string
		want string
	}{
		{"0111110", "周一、周二、周三、周四、周五"},
		{"1111111", "周日、周一、周二、周三、周四、周五、周六"},
		{"1000000", "周日"},
		{"0000001", "周六"},
		{"0000000", ""},
		{"", ""},
	}
	for _, c := range cases {
		got := strings.Join(weekdaysFromExemodel(c.mask), "、")
		if got != c.want {
			t.Fatalf("exemodel %q：期望 %q，实际 %q", c.mask, c.want, got)
		}
	}
}
