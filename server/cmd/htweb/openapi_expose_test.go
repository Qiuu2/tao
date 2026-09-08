package main

import (
	"os"
	"regexp"
	"sort"
	"strings"
	"testing"
)

// 这个测试盯着一件事：**每一条 /api 路由都必须有人表过态**——
// 要么在开放清单里（密钥能调），要么在 keyDenied 里写明为什么不开。
//
// # 为什么值得写这么一个测试
//
// 开放清单是白名单，漏写一条的后果是「这个接口用不了」，有人会来问。
// 但反过来 —— 加了一条新路由却没人想过它该不该对外 —— 是**没有任何症状**的：
// 界面照常工作，接口平台上少一条没人发现，直到某天需要它时才发现调不通；
// 或者更糟，某条本该关起来的接口被顺手加进了清单而没人复核。
//
// 让「表过态」变成一件编译期就能检查的事，是唯一可靠的办法。
//
// # 为什么去读源码
//
// 路由注册就在 main.go 的 routes() 里，它**就是**这个系统的路由表。
// 想在测试里拿到这张表，要么把 routes() 跑起来（需要数据库、配置、
// 一整个 app），要么读源码。读源码更直接，也不会因为环境缺什么而跑不了。
//
// 代价是它依赖注册语句的写法（mux.HandleFunc("METHOD /path", ...)）。
// 这个写法一旦改了，测试会因为「一条都没扫到」而失败 —— 见下面的兜底断言。
func TestEveryAPIRouteIsExposedOrDenied(t *testing.T) {
	src, err := os.ReadFile("main.go")
	if err != nil {
		t.Fatalf("读不到 main.go: %v", err)
	}

	re := regexp.MustCompile(`mux\.HandleFunc\("((?:GET|POST|PUT|DELETE) /api/[^"]+)"`)
	matches := re.FindAllStringSubmatch(string(src), -1)

	// 兜底：注册语句的写法要是改了，这里会是 0，测试立刻红 ——
	// 而不是「一条都没扫到，所以一条都没问题」这种假通过。
	if len(matches) < 200 {
		t.Fatalf("只从 main.go 扫到 %d 条 /api 路由，与预期（200+）差太多。"+
			"是不是路由注册的写法改了？改了的话要同步改这里的正则", len(matches))
	}

	var unclassified []string
	seen := map[string]bool{}
	for _, m := range matches {
		pattern := m[1]
		if seen[pattern] {
			continue
		}
		seen[pattern] = true
		_, exposed := exposedIndex[pattern]
		_, denied := keyDenied[pattern]
		if !exposed && !denied {
			unclassified = append(unclassified, pattern)
		}
		if exposed && denied {
			t.Errorf("%q 同时出现在开放清单和 keyDenied 里，到底开不开？", pattern)
		}
	}

	if len(unclassified) > 0 {
		sort.Strings(unclassified)
		t.Errorf("下面 %d 条 /api 路由既不在开放清单里、也没在 keyDenied 里写明理由。\n"+
			"新加接口时要表个态：能给第三方程序调的加进 exposedGroups，\n"+
			"不能给的加进 keyDenied 并写清楚为什么。\n  %s",
			len(unclassified), strings.Join(unclassified, "\n  "))
	}
}

// 反过来的一半：开放清单和 keyDenied 里不能有**已经不存在**的路由。
//
// 留着一条指向空气的记录不会报错，但它会出现在接口平台上 ——
// 集成方照着调，得到 404，然后来问我们。
func TestNoStaleEntriesInExposureLists(t *testing.T) {
	src, err := os.ReadFile("main.go")
	if err != nil {
		t.Fatalf("读不到 main.go: %v", err)
	}
	re := regexp.MustCompile(`mux\.HandleFunc\("((?:GET|POST|PUT|DELETE) /(?:api|openapi)/[^"]+)"`)
	registered := map[string]bool{}
	for _, m := range re.FindAllStringSubmatch(string(src), -1) {
		registered[m[1]] = true
	}
	if len(registered) < 200 {
		t.Fatalf("只扫到 %d 条路由，正则可能失效了", len(registered))
	}

	var stale []string
	for p := range exposedIndex {
		if !registered[p] {
			stale = append(stale, "开放清单: "+p)
		}
	}
	for p := range keyDenied {
		if !registered[p] {
			stale = append(stale, "keyDenied: "+p)
		}
	}
	if len(stale) > 0 {
		sort.Strings(stale)
		t.Errorf("下面 %d 条记录指向已经不存在的路由，删掉或改对：\n  %s",
			len(stale), strings.Join(stale, "\n  "))
	}
}

// 开放清单里的每一条都要有一句人话的说明，不能留空。
//
// 空着也能跑，但接口平台上会出现一行只有路径没有说明的条目 ——
// 而这一页存在的全部意义就是「让人看懂这些接口能干什么」。
func TestExposedAPIsHaveSummaries(t *testing.T) {
	for _, g := range exposedGroups {
		if strings.TrimSpace(g.Name) == "" || strings.TrimSpace(g.Desc) == "" {
			t.Errorf("分组 %q 缺名字或说明", g.Name)
		}
		for _, a := range g.APIs {
			if strings.TrimSpace(a.Summary) == "" {
				t.Errorf("%s 没写说明", a.Pattern)
			}
			if strings.TrimSpace(a.Right) == "" {
				t.Errorf("%s 没写需要什么权限", a.Pattern)
			}
			if !strings.HasPrefix(a.Pattern, "GET ") && !strings.HasPrefix(a.Pattern, "POST ") &&
				!strings.HasPrefix(a.Pattern, "PUT ") && !strings.HasPrefix(a.Pattern, "DELETE ") {
				t.Errorf("%q 不是「方法 路径」的形状", a.Pattern)
			}
		}
	}
}

// keyDenied 的每一条都要写清楚理由。
//
// 写不出理由的，多半说明它其实该开放 —— 逼一句话出来，
// 就是逼人真的想一遍。
func TestDenyReasonsAreWritten(t *testing.T) {
	for p, reason := range keyDenied {
		if len([]rune(strings.TrimSpace(reason))) < 6 {
			t.Errorf("%q 的不开放理由太敷衍：%q", p, reason)
		}
	}
}
