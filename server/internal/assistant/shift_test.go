package assistant

import "testing"

// 位移量解析。取自 _parse_time_offset_minutes。
// 解析错的后果是**位移了错误的量** —— 用户说后移半小时，方案却挪了 30 天。
func TestParseTimeOffsetMinutes(t *testing.T) {
	cases := []struct {
		in   string
		want int
		ok   bool
	}{
		{"30分钟", 30, true},
		{"30分", 30, true},
		{"半小时", 30, true},
		{"1小时", 60, true},
		{"1.5小时", 90, true},
		{"2时", 120, true},
		{"1天", 1440, true},
		{"1日", 1440, true},
		{"1周", 10080, true},
		{"1星期", 10080, true},
		{"1个月", 43200, true},
		{"1年", 525600, true},
		{"45", 45, true}, // 没带单位当分钟
		{"", 0, false},
		{"随便说说", 0, false},
	}
	for _, c := range cases {
		got, ok := parseTimeOffsetMinutes(c.in)
		if ok != c.ok || (c.ok && got != c.want) {
			t.Fatalf("%q：期望 (%d,%v)，实际 (%d,%v)", c.in, c.want, c.ok, got, ok)
		}
	}
}
