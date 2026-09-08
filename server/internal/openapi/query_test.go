package openapi

import (
	"reflect"
	"testing"
)

// exemodel 的位序是这套系统里最容易搞反的一件事，而搞反了**不报错**——
// 只是铃在错误的那天响。所以这里把它钉死。
//
// 权威依据是 ok112/Browse_active_task.php 里那条判「今天该不该响」的 SQL：
//
//	SUBSTRING(task.exemodel,
//	  CASE WHEN WEEKDAY(CURDATE())+2 = 8 THEN 1 ELSE WEEKDAY(CURDATE())+2 END, 1)
//
// MySQL 的 WEEKDAY() 是**周一 = 0** … 周日 = 6，SUBSTRING 从 1 数起。代入：
//
//	周一 → 位置 2   周二 → 3   …   周六 → 7   周日 → 8，被 CASE 折回 1
//
// 也就是 **位置 1（下标 0）是周日**，位置 2..7 是周一到周六。
// isoWeekdays 把它翻成外界通行的 1=周一 … 7=周日。
func TestISOWeekdays(t *testing.T) {
	cases := []struct {
		name     string
		exemodel string
		want     []int
	}{
		{"手动（后台永不自动触发）", "0000000", []int{}},
		{"每天", "1111111", []int{1, 2, 3, 4, 5, 6, 7}},
		{"只有周日", "1000000", []int{7}},
		{"只有周一", "0100000", []int{1}},
		{"只有周六", "0000001", []int{6}},
		{"周一到周五（现网 1001 早读预备铃）", "0111110", []int{1, 2, 3, 4, 5}},
		{"周末", "1000001", []int{6, 7}},
		{"长度不足按手动处理", "011", []int{}},
		{"空串按手动处理", "", []int{}},
	}
	for _, c := range cases {
		t.Run(c.name, func(t *testing.T) {
			got := isoWeekdays(c.exemodel)
			if !reflect.DeepEqual(got, c.want) {
				t.Fatalf("isoWeekdays(%q) = %v，期望 %v", c.exemodel, got, c.want)
			}
		})
	}
}

// Ref 是外部合同的一部分：写数字是编号、写字符串是名字。
// 字符串形式的纯数字也当编号收 —— 很多语言的 JSON 序列化会把 int64 写成字符串。
func TestRefUnmarshal(t *testing.T) {
	cases := []struct {
		in       string
		wantID   int64
		wantName string
		wantErr  bool
	}{
		{`12`, 12, "", false},
		{`"12"`, 12, "", false},
		{`"A101教室音箱"`, 0, "A101教室音箱", false},
		{`"  A101  "`, 0, "A101", false},
		{`null`, 0, "", false},
		// 名字**看起来**像负数时不当编号，当名字 —— 库里真有叫「-1号机」的话也能寻址
		{`"-1号机"`, 0, "-1号机", false},
		{`0`, 0, "", true},
		{`-3`, 0, "", true},
		{`{"id":1}`, 0, "", true},
	}
	for _, c := range cases {
		t.Run(c.in, func(t *testing.T) {
			var r Ref
			err := r.UnmarshalJSON([]byte(c.in))
			if c.wantErr {
				if err == nil {
					t.Fatalf("%s 期望报错，实际解析成 %+v", c.in, r)
				}
				return
			}
			if err != nil {
				t.Fatalf("%s 意外报错: %v", c.in, err)
			}
			if r.ID != c.wantID || r.Name != c.wantName {
				t.Fatalf("%s 解析成 {ID:%d Name:%q}，期望 {ID:%d Name:%q}",
					c.in, r.ID, r.Name, c.wantID, c.wantName)
			}
		})
	}
}

// pager 不能沿用界面那套「不认识的尺寸静默回落到 18」——
// 对开发者接口那是陷阱：传 30 变成 18，翻页时会漏数据且毫无察觉。
func TestPagerDoesNotSilentlyChangeSize(t *testing.T) {
	cases := []struct{ in, want int }{
		{0, 20},    // 没传 → 默认 20
		{30, 30},   // 界面那套不认识 30，这里必须原样保留
		{1, 1},     //
		{500, 200}, // 上限夹取，防止一次拉走整库
	}
	for _, c := range cases {
		p := ListQuery{PageSize: c.in}.pager()
		if p.PageSize != c.want {
			t.Fatalf("pageSize=%d 归一化成 %d，期望 %d", c.in, p.PageSize, c.want)
		}
	}
}

// hintNear 只给「相近的名字」当提示，绝不替调用方选中其中一个。
func TestHintNear(t *testing.T) {
	rows := []namedRow{{1, "A101教室音箱"}, {2, "A102教室音箱"}, {3, "广播室主话筒"}}
	if got := hintNear("A101", rows); got == "" {
		t.Fatal("「A101」应能提示出 A101教室音箱")
	}
	if got := hintNear("完全不沾边", rows); got != "" {
		t.Fatalf("不沾边的名字不该有提示，得到 %q", got)
	}
}
