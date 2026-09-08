package openapi

import (
	"reflect"
	"testing"

	"htweb/internal/auth"
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

// 密钥的请求头名在两个包里各有一份常量：openapi.HeaderAPIKey（规格与文档用）
// 和 auth.HeaderAPIKey（中间件用）。不能合并 —— openapi 依赖 auth，
// 反过来 import 就成环了。
//
// 两处一旦不一致，症状很难查：规格和文档里写着一个头名，中间件认的是另一个，
// 集成方照着文档写，收到的却是「缺少 X-API-Key」。所以钉住它。
func TestHeaderNameMatchesAuthPackage(t *testing.T) {
	if HeaderAPIKey != auth.HeaderAPIKey {
		t.Fatalf("openapi.HeaderAPIKey = %q，auth.HeaderAPIKey = %q —— 两处必须一致",
			HeaderAPIKey, auth.HeaderAPIKey)
	}
}

// 每一条**有响应示例**的接口，都必须逐字段解释那个示例。
//
// # 为什么
//
// 光贴一段示例 JSON 是不够的：`"priority": 8` 里的 8 是什么？数字大就优先吗
// （不是，正好反过来）？`"weekdays": [7]` 的 7 是周日还是周六？
// 集成方猜错了**不会报错**，只会在错误的那天广播。
//
// 漏写 Returns 不会有任何症状 —— 平台上少一块，没人会发现。所以要有测试。
func TestEverySampleIsExplained(t *testing.T) {
	for _, g := range Catalog().Groups {
		for _, ep := range g.Endpoints {
			if ep.Sample == "" {
				continue
			}
			if len(ep.Returns) == 0 {
				t.Errorf("%s %s（%s）给了响应示例却没解释里面的字段",
					ep.Method, ep.Path, ep.Summary)
				continue
			}
			for _, f := range ep.Returns {
				if f.Name == "" || f.Desc == "" {
					t.Errorf("%s %s 的响应字段说明有空项：%+v", ep.Method, ep.Path, f)
				}
			}
		}
	}
}

// 取值对照表本身也要完整：每张表都得有字段名、标题、说明和至少两个取值。
//
// 只有一个取值的「对照表」说明没写全 —— 二值字段至少两条，
// 而一个字段如果真的只有一个可能取值，它就不需要对照表。
func TestCodeTablesAreComplete(t *testing.T) {
	tables := Catalog().Codes
	if len(tables) < 8 {
		t.Fatalf("取值对照表只有 %d 张，太少了 —— 反直觉的那几个（projectstate / "+
			"israndomplay / priority / exemodel）是不是漏了？", len(tables))
	}
	seen := map[string]bool{}
	for _, ct := range tables {
		if ct.Field == "" || ct.Title == "" || ct.Desc == "" {
			t.Errorf("对照表 %q 缺字段名/标题/说明", ct.Title)
		}
		if seen[ct.Field] {
			t.Errorf("对照表 %q 重复了", ct.Field)
		}
		seen[ct.Field] = true
		if len(ct.Values) < 2 {
			t.Errorf("对照表 %q 只有 %d 个取值 —— 对照表至少要两个，"+
				"只有一个取值的字段不需要对照表", ct.Field, len(ct.Values))
		}
		for _, v := range ct.Values {
			if v.Value == "" || v.Means == "" {
				t.Errorf("对照表 %q 有空的取值项：%+v", ct.Field, v)
			}
		}
	}
	// 这四个是**猜必然猜错**的，必须在表里，而且必须标红。
	for _, must := range []string{"projectstate", "israndomplay", "priority", "exemodel"} {
		if !seen[must] {
			t.Errorf("%q 不在取值对照表里 —— 它是反直觉的，猜错了不报错、只会播错", must)
		}
	}
	for _, ct := range tables {
		switch ct.Field {
		case "projectstate", "israndomplay", "priority", "exemodel":
			warned := false
			for _, v := range ct.Values {
				if v.Warn {
					warned = true
				}
			}
			if !warned {
				t.Errorf("对照表 %q 一个 Warn 都没标 —— 它正是要提醒人别猜的那种", ct.Field)
			}
		}
	}
}
