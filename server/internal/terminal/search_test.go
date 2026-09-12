package terminal

import (
	"strings"
	"testing"
)

// ⚠ 这一条盯的是「给了关键词却当没看见」。
//
// 原来的写法是 `if col, ok := searchWhitelist[q.SearchKey]; ok && kw != ""`，
// searchKey 对不上就整个条件不加 —— 带着 keyword 却没带 searchKey 的请求
// **静默返回全部数据**。现网前端发的正是 ?terminalname=xxx（ProTable 按列名
// 发参数），后端读的却是 searchKey/keyword，两边对不上，所以终端管理那个
// 搜索框一直完全没作用：12 条终端，搜 "A101" 还是回 12 条。
//
// 界面上看着像搜过了，这是最坏的一种失败。
func TestSearchWithoutKeyStillFilters(t *testing.T) {
	c, args := terminalSearchCond("", "A101")
	if c == "" {
		t.Fatal("给了关键词就必须过滤，绝不能静默返回全部")
	}
	if !strings.Contains(c, "t.terminalname") || !strings.Contains(c, "t.ip") {
		t.Errorf("没指定列时该同时查名称和 IP：%s", c)
	}
	if len(args) != 2 {
		t.Errorf("两个 LIKE 该有两个参数，得到 %d", len(args))
	}
	// 认不出来的 key 同样退到「名称或 IP」，不能变成不过滤
	if c2, _ := terminalSearchCond("不认识的列", "A101"); c2 != c {
		t.Errorf("认不出的 key 该和留空一样处理：%s", c2)
	}
	if c3, _ := terminalSearchCond(SearchKeyAny, "A101"); c3 != c {
		t.Errorf("SearchKeyAny 该和留空一样处理：%s", c3)
	}
}

// 明确指定了列就只查那一列 —— openapi 那边按终端名精确找设备靠的是这个。
func TestSearchByExplicitColumn(t *testing.T) {
	c, args := terminalSearchCond("terminalname", "A101")
	if !strings.Contains(c, "t.terminalname") || strings.Contains(c, "t.ip") {
		t.Errorf("指定了 terminalname 就不该带上 ip：%s", c)
	}
	if len(args) != 1 {
		t.Errorf("单列只要一个参数，得到 %d", len(args))
	}
	if c, _ := terminalSearchCond("ip", "192.168"); !strings.Contains(c, "t.ip") ||
		strings.Contains(c, "terminalname") {
		t.Errorf("指定了 ip 就只查 ip：%s", c)
	}
}

// 关键词是空的（或只有空白）就不加条件 —— 那是「没搜」，不是「搜空字符串」。
func TestSearchEmptyKeywordAddsNothing(t *testing.T) {
	for _, kw := range []string{"", "   ", "\t"} {
		if c, args := terminalSearchCond("", kw); c != "" || args != nil {
			t.Errorf("关键词 %q 不该产生条件：%q", kw, c)
		}
	}
}

// LIKE 的元字符要转义，否则搜 "%" 会把整张表捞回来。
func TestSearchEscapesLikeWildcards(t *testing.T) {
	_, args := terminalSearchCond("terminalname", "100%")
	if len(args) != 1 {
		t.Fatal("该有一个参数")
	}
	got, _ := args[0].(string)
	if !strings.Contains(got, `\%`) {
		t.Errorf("百分号没转义：%q —— 不转义的话搜 %% 会返回整张表", got)
	}
}
