package assistant

import (
	"encoding/json"
	"os"
	"strings"
	"testing"
	"time"
)

// 时间锚点与星期/时刻位移的黄金用例。期望值由
// nlu/golden/gen_anchor_golden.py 生成（ast 抠原函数体，exec 后直接跑）。
//
// 这一组盯的是「从周三挪到周五」「从 8 点挪到 9 点」算不算得对。
// 算错的表现是**任务被挪到了别的时间**，而且用户要等到那天没响才发现。

type anchorGolden struct {
	Now     string `json:"now"`
	Anchors []struct {
		Raw                string `json:"raw"`
		Kind               string `json:"kind"`
		Weekday            string `json:"weekday"`
		Date               string `json:"date"`
		HasRange           bool   `json:"has_range"`
		StartMinutes       int    `json:"start_minutes"`
		EndMinutes         int    `json:"end_minutes"`
		AnchorStartMinutes int    `json:"anchor_start_minutes"`
	} `json:"anchors"`
	Ranges []struct {
		Raw   string `json:"raw"`
		OK    bool   `json:"ok"`
		Start int    `json:"start"`
		End   int    `json:"end"`
	} `json:"ranges"`
	ReplaceWeekday []struct {
		Weekdays []string `json:"weekdays"`
		Source   string   `json:"source"`
		Target   string   `json:"target"`
		Want     []string `json:"want"`
	} `json:"replace_weekday"`
	ShiftWeekdays []struct {
		Weekdays []string `json:"weekdays"`
		Delta    int      `json:"delta"`
		Want     []string `json:"want"`
	} `json:"shift_weekdays"`
	ShiftTime []struct {
		Value        string `json:"value"`
		DeltaMinutes int    `json:"delta_minutes"`
		WantTime     string `json:"want_time"`
		WantDayDelta int    `json:"want_day_delta"`
	} `json:"shift_time"`
}

func TestTimeAnchorMatchesPython(t *testing.T) {
	raw, err := os.ReadFile("testdata/anchor_golden.json")
	if err != nil {
		t.Fatalf("读黄金用例: %v", err)
	}
	var g anchorGolden
	if err := json.Unmarshal(raw, &g); err != nil {
		t.Fatalf("解析黄金用例: %v", err)
	}
	now, err := time.ParseInLocation(goldenLayout, g.Now, time.Local)
	if err != nil {
		t.Fatalf("解析用例基准时间: %v", err)
	}
	freezeNow(t, now)

	for _, c := range g.Anchors {
		t.Run("anchor/"+c.Raw, func(t *testing.T) {
			a, ok := ParseTimeAnchor(c.Raw)
			if c.Kind == "" {
				if ok {
					t.Fatalf("期望解析不出来，实际得到 %+v", a)
				}
				return
			}
			if !ok {
				t.Fatalf("期望解析出 kind=%s，实际没解析出来", c.Kind)
			}
			if a.Kind != c.Kind {
				t.Fatalf("kind：期望 %q，实际 %q", c.Kind, a.Kind)
			}
			if a.Weekday != c.Weekday {
				t.Fatalf("weekday：期望 %q，实际 %q", c.Weekday, a.Weekday)
			}
			gotDate := ""
			if !a.Date.IsZero() {
				gotDate = a.Date.Format("2006-01-02")
			}
			if gotDate != c.Date {
				t.Fatalf("date：期望 %q，实际 %q", c.Date, gotDate)
			}
			if a.HasRange != c.HasRange {
				t.Fatalf("has_range：期望 %v，实际 %v", c.HasRange, a.HasRange)
			}
			if c.HasRange && (a.StartMinutes != c.StartMinutes || a.EndMinutes != c.EndMinutes) {
				t.Fatalf("时间范围：期望 (%d,%d)，实际 (%d,%d)",
					c.StartMinutes, c.EndMinutes, a.StartMinutes, a.EndMinutes)
			}
			got, ok := anchorStartMinutes(a)
			want := c.AnchorStartMinutes
			if want < 0 {
				if ok {
					t.Fatalf("anchor_start_minutes：期望取不到，实际 %d", got)
				}
			} else if !ok || got != want {
				t.Fatalf("anchor_start_minutes：期望 %d，实际 %d（取到=%v）", want, got, ok)
			}
		})
	}

	for _, c := range g.Ranges {
		t.Run("range/"+c.Raw, func(t *testing.T) {
			s, e, ok := extractTimeRangeMinutes(c.Raw)
			if ok != c.OK {
				t.Fatalf("期望 ok=%v，实际 %v", c.OK, ok)
			}
			if c.OK && (s != c.Start || e != c.End) {
				t.Fatalf("期望 (%d,%d)，实际 (%d,%d)", c.Start, c.End, s, e)
			}
		})
	}

	for _, c := range g.ReplaceWeekday {
		t.Run("replace/"+strings.Join(c.Weekdays, "")+"/"+c.Source+"→"+c.Target, func(t *testing.T) {
			got := strings.Join(replaceWeekday(c.Weekdays, c.Source, c.Target), "、")
			if want := strings.Join(c.Want, "、"); got != want {
				t.Fatalf("期望 %s，实际 %s", want, got)
			}
		})
	}

	for _, c := range g.ShiftWeekdays {
		t.Run("shiftwd/"+strings.Join(c.Weekdays, "")+"/"+itoa(c.Delta), func(t *testing.T) {
			got := strings.Join(shiftWeekdaysByDelta(c.Weekdays, c.Delta), "、")
			if want := strings.Join(c.Want, "、"); got != want {
				t.Fatalf("期望 %s，实际 %s", want, got)
			}
		})
	}

	for _, c := range g.ShiftTime {
		t.Run("shiftt/"+c.Value+"/"+itoa(c.DeltaMinutes), func(t *testing.T) {
			gotTime, gotDelta := shiftHHMMSSWithDayDelta(c.Value, c.DeltaMinutes)
			if gotTime != c.WantTime || gotDelta != c.WantDayDelta {
				t.Fatalf("期望 (%s,%d)，实际 (%s,%d)",
					c.WantTime, c.WantDayDelta, gotTime, gotDelta)
			}
		})
	}
}

// exemodelFromWeekdays 与 weekdaysFromExemodel 必须是一对。
// 掩码是**周日打头**的；搞反的后果是每条被挪动的任务星期整体错一天。
func TestExemodelRoundTrip(t *testing.T) {
	cases := []struct {
		weekdays []string
		mask     string
	}{
		{[]string{"周一", "周二", "周三", "周四", "周五"}, "0111110"},
		{[]string{"周日"}, "1000000"},
		{[]string{"周六"}, "0000001"},
		{[]string{"周二", "周三", "周四", "周五", "周六"}, "0011111"},
		{nil, "0000000"},
	}
	for _, c := range cases {
		if got := exemodelFromWeekdays(c.weekdays); got != c.mask {
			t.Fatalf("%v → 期望 %q，实际 %q", c.weekdays, c.mask, got)
		}
		back := strings.Join(weekdaysFromExemodel(c.mask), "、")
		want := strings.Join(c.weekdays, "、")
		if back != want {
			t.Fatalf("%q → 期望 %q，实际 %q", c.mask, want, back)
		}
	}
}

// 影子任务必须落在**目标那天的星期位**上。
// 写成全 0 在这套库里等于「手动」，后台不会执行它 —— 影子建了却永远不响。
func TestShadowExemodelIsTargetWeekday(t *testing.T) {
	// 2026-09-14 是周一
	day := time.Date(2026, 9, 14, 0, 0, 0, 0, time.Local)
	if got := weekdayLabelOf(day); got != "周一" {
		t.Fatalf("2026-09-14 应当是周一，实际 %s", got)
	}
	if got := exemodelFromWeekdays([]string{weekdayLabelOf(day)}); got != "0100000" {
		t.Fatalf("周一的掩码应当是 0100000，实际 %s", got)
	}
	if got := exemodelFromWeekdays([]string{weekdayLabelOf(day)}); got == "0000000" {
		t.Fatal("影子任务的掩码不能是全 0 —— 那在这套库里是「手动」，后台不会执行")
	}
}
