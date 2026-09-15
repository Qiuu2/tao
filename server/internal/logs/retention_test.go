package logs

import (
	"testing"
	"time"
)

// 「半个月」是唯一一档不是整月的，日期减法走的是 Days 不是 Months。
//
// ⚠ 这条盯的是一个具体的坑：把半个月写成「减 1 个月再加 15 天」，
// 3 月 31 日会先落到 2 月 31 日，Go 的 AddDate 把它折算成 3 月 3 日 ——
// 比「半个月前」还晚，结果是该删的没删。
func TestCutoffOfHalfMonthIsFifteenDays(t *testing.T) {
	now := time.Date(2026, 3, 31, 14, 30, 0, 0, time.Local)
	got := cutoffOf(specOf(Retain15Days), now)
	want := time.Date(2026, 3, 16, 0, 0, 0, 0, time.Local)
	if !got.Equal(want) {
		t.Errorf("半个月前 = %s，想要 %s", got.Format("2006-01-02"), want.Format("2006-01-02"))
	}
}

// 整月那几档仍然走自然月：2 月和 8 月不一样长，这是有意的。
func TestCutoffOfWholeMonthsStillUsesCalendarMonths(t *testing.T) {
	now := time.Date(2026, 3, 31, 0, 0, 0, 0, time.Local)
	cases := []struct {
		opt  RetentionOption
		want string
	}{
		// ⚠ 3 月 31 日往前推整月会落到「不存在的那一天」，Go 的 AddDate 会顺延到下个月初。
		//   这是自然月减法的既有行为，不是 bug —— 写在这里免得下次有人「顺手修」。
		{Retain1Month, "2026-03-03"},  // 2026-02-31 → 03-03
		{Retain3Months, "2025-12-31"}, // 2025-12-31，这一天存在
		{Retain6Months, "2025-10-01"}, // 2025-09-31 → 10-01
		{Retain1Year, "2025-03-31"},
	}
	for _, c := range cases {
		got := cutoffOf(specOf(c.opt), now).Format("2006-01-02")
		if got != c.want {
			t.Errorf("%s 的边界 = %s，想要 %s", c.opt, got, c.want)
		}
	}
}

// 默认档是半个月（现场 2026-09-15 定的），而且它必须真的在可选项里 ——
// specOf 找不到时会**静默回落到第一档**，默认值写错了不会报任何错。
func TestDefaultRetentionIsHalfMonthAndIsListed(t *testing.T) {
	if DefaultRetention != Retain15Days {
		t.Errorf("默认档是 %s，现场定的是半个月", DefaultRetention)
	}
	found := false
	for _, s := range retentionSpecs {
		if s.Option == DefaultRetention {
			found = true
		}
	}
	if !found {
		t.Error("默认档不在 retentionSpecs 里 —— specOf 会静默回落到第一档")
	}
}

// 每一档都得能算出一个**早于今天**的边界。漏填 Months/Days 的那一档会算出今天，
// 表现是「选了这一档，今天以前的日志全没了」。
func TestEverySpecMovesTheCutoffBackwards(t *testing.T) {
	now := time.Date(2026, 6, 15, 0, 0, 0, 0, time.Local)
	for _, s := range retentionSpecs {
		if got := cutoffOf(s, now); !got.Before(now) {
			t.Errorf("%s（%s）的边界是 %s，没有往前推 —— Months 和 Days 是不是都填了 0",
				s.Option, s.Label, got.Format("2006-01-02"))
		}
	}
}
