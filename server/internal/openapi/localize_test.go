package openapi

import (
	"strings"
	"testing"
	"unicode"

	"htweb/internal/i18n"
)

// 目录里每一句中文都得在字典里有对应的英文。
//
// # 为什么需要这条
//
// Localize 是走结构翻的，新增一个接口**不用改 Localize** —— 好处是不会漏改代码，
// 代价是漏补字典也一样不报错，只会在英文界面上留一句中文，而中文界面看着完全正常。
// 这个测试把「漏补」变成加接口时当场就红的一件事。
func TestSpecStringsAreTranslated(t *testing.T) {
	var missing []string
	check := func(s string) {
		if s == "" || !hasCJK(s) {
			return
		}
		if i18n.T(i18n.EN, s) == s {
			missing = append(missing, s)
		}
	}

	spec := Catalog()
	check(spec.Title)
	for _, g := range spec.Groups {
		check(g.Name)
		check(g.Desc)
		for _, ep := range g.Endpoints {
			check(ep.Summary)
			check(ep.Desc)
			check(ep.Right)
			for _, p := range ep.Params {
				check(p.Desc)
			}
			for _, f := range ep.Fields {
				check(f.Desc)
			}
			for _, f := range ep.Returns {
				check(f.Desc)
			}
			for _, e := range ep.Examples {
				check(e.Title)
				check(e.Desc)
			}
			for _, n := range ep.Notes {
				check(n)
			}
		}
	}
	for _, c := range spec.Codes {
		check(c.Title)
		check(c.Desc)
		for _, v := range c.Values {
			check(v.Means)
		}
	}

	if len(missing) > 0 {
		t.Errorf("目录里这 %d 句中文还没进字典（internal/i18n/dict.go），"+
			"英文界面上会原样显示中文：\n  %s",
			len(missing), strings.Join(missing, "\n  "))
	}
}

func hasCJK(s string) bool {
	for _, r := range s {
		if unicode.Is(unicode.Han, r) {
			return true
		}
	}
	return false
}
