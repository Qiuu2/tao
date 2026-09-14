package dashboard

import (
	"testing"
	"time"
)

// 「看的是哪一天」的换算。列表和「当天启用 / 当天停用」共用这一份 ——
// 界面上写着「看的是 2026-09-16」，点下去停的就得是 9-16 那一天。
//
// weekday 取值 1~7，1 = 周日（与 exemodel 掩码同一套下标）。
func TestViewDateOf(t *testing.T) {
	// 2026-09-14 是周一
	now := time.Date(2026, 9, 14, 12, 0, 0, 0, time.Local)
	if now.Weekday() != time.Monday {
		t.Fatalf("基准日算错了：%v", now.Weekday())
	}
	cases := []struct {
		name     string
		weekday  int
		wantDate string
		wantPos  int
	}{
		{"0 = 不选，就是今天（周一）", 0, "2026-09-14", 2},
		{"1 = 周日，本周已经过去了", 1, "2026-09-13", 1},
		{"2 = 周一，就是今天", 2, "2026-09-14", 2},
		{"4 = 周三", 4, "2026-09-16", 4},
		{"7 = 周六", 7, "2026-09-19", 7},
		{"越界的值当今天", 9, "2026-09-14", 2},
		{"负数同理", -3, "2026-09-14", 2},
	}
	for _, c := range cases {
		d, pos := viewDateOf(now, c.weekday)
		if d != c.wantDate || pos != c.wantPos {
			t.Errorf("%s：viewDateOf(周一, %d) = (%q,%d)，想要 (%q,%d)",
				c.name, c.weekday, d, pos, c.wantDate, c.wantPos)
		}
	}
}

// ZeroDay 必须是字符串 '0000-00-00'，不是空串也不是 NULL。
//
// 现网存量数据就是这个值，旧版写回去的也是它（do.php:28160）。
// 换成别的，旧版页面和后台 C 服务读出来的东西就变样了。
func TestZeroDay(t *testing.T) {
	if ZeroDay != "0000-00-00" {
		t.Errorf("ZeroDay = %q，必须是 0000-00-00", ZeroDay)
	}
}
