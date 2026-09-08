package templates

import "testing"

// 八套模板都要在，而且都要有内容。
// 少一套的表现是"我设了高中冬季，它说没有模板" —— 用户无从下手。
func TestAllTemplatesPresent(t *testing.T) {
	for _, kind := range []string{"小学", "中学", "高中", "大学"} {
		for _, season := range []string{"夏季", "冬季"} {
			tpl, err := Lookup(kind, season)
			if err != nil {
				t.Fatalf("%s%s：%v", kind, season, err)
			}
			if len(tpl.Items) == 0 {
				t.Fatalf("%s%s 的模板是空的", kind, season)
			}
			for i, it := range tpl.Items {
				if it.Name == "" || it.Start == "" {
					t.Fatalf("%s%s 第 %d 条缺名字或时刻：%+v", kind, season, i, it)
				}
				if it.Seconds < 1 {
					t.Fatalf("%s%s 的「%s」时长是 %d 秒", kind, season, it.Name, it.Seconds)
				}
			}
		}
	}
}

// 别名要认。用户说「初中」「暑期」是常事。
func TestAliases(t *testing.T) {
	cases := []struct{ in, want string }{
		{"初中", "中学"}, {"中学生", "中学"}, {"高中部", "高中"},
		{"大学生", "大学"}, {"小学部", "小学"}, {"中学", "中学"},
	}
	for _, c := range cases {
		if got := NormalizeKind(c.in); got != c.want {
			t.Fatalf("学校类型 %q：期望 %q，实际 %q", c.in, c.want, got)
		}
	}
	for _, c := range []struct{ in, want string }{
		{"夏", "夏季"}, {"暑期", "夏季"}, {"冬", "冬季"}, {"寒期", "冬季"}, {"夏季", "夏季"},
	} {
		if got := NormalizeSeason(c.in); got != c.want {
			t.Fatalf("季节 %q：期望 %q，实际 %q", c.in, c.want, got)
		}
	}
}

// 条目按时刻排好序 —— 界面上乱序的作息表没法看。
func TestItemsSorted(t *testing.T) {
	tpl, err := Lookup("中学", "夏季")
	if err != nil {
		t.Fatal(err)
	}
	for i := 1; i < len(tpl.Items); i++ {
		if tpl.Items[i].Start < tpl.Items[i-1].Start {
			t.Fatalf("第 %d 条 %s 排在 %s 前面了", i, tpl.Items[i].Start, tpl.Items[i-1].Start)
		}
	}
}

func TestKindsAndSeasons(t *testing.T) {
	if len(Kinds()) != 4 {
		t.Fatalf("学校类型应当有 4 种，实际 %v", Kinds())
	}
	if len(Seasons()) != 2 {
		t.Fatalf("季节应当有 2 种，实际 %v", Seasons())
	}
}
