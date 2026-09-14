package dashboard

import "testing"

// 「未执行 / 已执行」这一列的判据。
//
// 旧版 Browse_active_task_form.html:110~155 是拿 playtime 和当前时刻比：
// 没到点写「准备●」，过了点写「已执行」。新版一度把这一列做成了
// 「当天启用 / 当天停用」，按需求方要求改回来。
//
// ⚠ 星期能选任意一天之后，判据要摊到那一天上，不能只会看今天。
func TestExecutedOn(t *testing.T) {
	const today = "2026-09-14"
	const now = "12:00:00"

	cases := []struct {
		name     string
		viewDate string
		playTime string
		want     bool
	}{
		{"看过去的某天：那天早过完了", "2026-09-13", "23:59:59", true},
		{"看将来的某天：还没到", "2026-09-16", "00:00:01", false},
		{"今天，已经过了点", today, "08:00:00", true},
		{"今天，还没到点", today, "18:00:00", false},
		{"今天，正好卡在这一秒 —— 算已执行（到点就该响了）", today, now, true},
		{"没有执行时间的：谈不上已执行", today, "", false},
	}
	for _, c := range cases {
		if got := executedOn(c.viewDate, today, now, c.playTime); got != c.want {
			t.Errorf("%s：executedOn(%q,%q,%q,%q) = %v，想要 %v",
				c.name, c.viewDate, today, now, c.playTime, got, c.want)
		}
	}
}
