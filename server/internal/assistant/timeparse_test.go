package assistant

import (
	"encoding/json"
	"os"
	"testing"
	"time"
)

// 中文时间解析的黄金用例。期望值是**把原实现里的函数原样抠出来跑**生成的
// （见 scratchpad/gen_time_golden.py：用 ast 按源码位置切函数体，exec 进干净的
// 命名空间，一个字没改写），不是我照着代码推的。
//
// 「现在」在用例文件里钉死成 2026-09-08 10:30（周二），
// 否则「明天」「下周一」这类用例活不过一天。

type timeGolden struct {
	Now   string `json:"now"`
	Cases []struct {
		Name               string `json:"name"`
		Text               string `json:"text"`
		StartValue         string `json:"start_value"`
		EndValue           string `json:"end_value"`
		WantStartRaw       string `json:"want_start_raw"`
		WantEndRaw         string `json:"want_end_raw"`
		WantFilterStartRaw string `json:"want_filter_start_raw"`
		WantFilterEndRaw   string `json:"want_filter_end_raw"`
		WantStartDT        string `json:"want_start_dt"`
		WantEndDT          string `json:"want_end_dt"`
	} `json:"cases"`
}

const goldenLayout = "2006-01-02 15:04:05"

// freezeNow 把 nowFunc 钉死，返回恢复函数。
func freezeNow(t *testing.T, at time.Time) {
	t.Helper()
	prev := nowFunc
	nowFunc = func() time.Time { return at }
	t.Cleanup(func() { nowFunc = prev })
}

func TestNormalizeQueryTaskTimeFiltersMatchesPython(t *testing.T) {
	raw, err := os.ReadFile("testdata/time_golden.json")
	if err != nil {
		t.Fatalf("读黄金用例: %v", err)
	}
	var g timeGolden
	if err := json.Unmarshal(raw, &g); err != nil {
		t.Fatalf("解析黄金用例: %v", err)
	}
	now, err := time.ParseInLocation(goldenLayout, g.Now, time.Local)
	if err != nil {
		t.Fatalf("解析用例基准时间: %v", err)
	}
	if len(g.Cases) == 0 {
		t.Fatal("黄金用例是空的")
	}
	freezeNow(t, now)

	fmtDT := func(v time.Time, ok bool) string {
		if !ok {
			return ""
		}
		return v.Format(goldenLayout)
	}

	for _, c := range g.Cases {
		t.Run(c.Name, func(t *testing.T) {
			got := normalizeQueryTaskTimeFilters(c.Text, c.StartValue, c.EndValue)
			check := func(what, want, actual string) {
				t.Helper()
				if want != actual {
					t.Errorf("%s 不一致：期望 %q（Python），实际 %q（Go）", what, want, actual)
				}
			}
			check("start_raw", c.WantStartRaw, got.StartRaw)
			check("end_raw", c.WantEndRaw, got.EndRaw)
			check("filter_start_raw", c.WantFilterStartRaw, got.FilterStartRaw)
			check("filter_end_raw", c.WantFilterEndRaw, got.FilterEndRaw)
			check("start_dt", c.WantStartDT, fmtDT(got.Start, got.HasStart))
			check("end_dt", c.WantEndDT, fmtDT(got.End, got.HasEnd))
		})
	}
}

// 「2月30日」这种日子 Python 会抛 ValueError 从而跳过，
// Go 的 time.Date 却会自动进位成 3 月 2 日。不挡住就是**查错日期**。
func TestInvalidDateIsRejectedNotRolledOver(t *testing.T) {
	freezeNow(t, time.Date(2026, 9, 8, 10, 30, 0, 0, time.Local))
	if _, ok := validDate(2026, 2, 30, time.Local); ok {
		t.Fatal("2026-02-30 不该被当成合法日期")
	}
	if _, ok := validDate(2026, 13, 1, time.Local); ok {
		t.Fatal("13 月不该被当成合法月份")
	}
	if _, ok := validDate(2024, 2, 29, time.Local); !ok {
		t.Fatal("2024-02-29 是闰年，应当合法")
	}
}
