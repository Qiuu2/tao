package dashboard

import (
	"reflect"
	"regexp"
	"sort"
	"strconv"
	"testing"

	"htweb/internal/task"
)

// 看板列得出来的每一种任务，都必须点得动「执行 / 停止」。
//
// 原来 task.Control 只认 {2,7,15}（旧版启动语句 `tasktype IN(2,15)` 的范围），
// 于是看板上点终端功放、采播、文字语音、led播放、作息条目的执行，
// 一律弹「任务类型 N 不支持启停」—— 列表里明明有这一行。
//
// 这条测试把两处口径钉在一起：browseAllModules（列什么）与
// task.StartableTypes（能启停什么）必须是同一套 tasktype。
// 以后任何一边加了类型、另一边忘了跟，这里就红。
func TestEveryBrowsableTypeIsStartable(t *testing.T) {
	listed := typesIn(browseAllModules)
	startable := task.StartableTypes()
	if !reflect.DeepEqual(listed, startable) {
		t.Errorf("看板列出的类型 %v 与能启停的类型 %v 对不上 ——\n"+
			"两边必须一致，否则列表里有的行点了会说「不支持启停」", listed, startable)
	}
	// 各模块自己的片段也不能列出 browseAllModules 之外的类型
	all := map[int]bool{}
	for _, v := range listed {
		all[v] = true
	}
	for name, frag := range browseModules {
		for _, v := range typesIn(frag) {
			if !all[v] {
				t.Errorf("模块 %q 里的 tasktype %d 不在「全部」的范围里", name, v)
			}
		}
	}
}

var reNum = regexp.MustCompile(`\d+`)

// typesIn 从 SQL 片段里把 tasktype 的取值抠出来。
//
// ⚠ 只认 `tasktype ...` 后面那一段：片段里还有 prepower、info 之类的比较，
// 整段抓数字会把 0 也算成一个任务类型。
func typesIn(frag string) []int {
	out := []int{}
	seen := map[int]bool{}
	for _, seg := range splitAfter(frag, "tasktype") {
		// 到下一个 AND / 右括号为止，再往后是别的条件了
		end := len(seg)
		for _, stop := range []string{" AND ", ")"} {
			if i := indexOf(seg, stop); i >= 0 && i < end {
				end = i
			}
		}
		for _, m := range reNum.FindAllString(seg[:end], -1) {
			n, err := strconv.Atoi(m)
			if err != nil || seen[n] {
				continue
			}
			seen[n] = true
			out = append(out, n)
		}
	}
	sort.Ints(out)
	return out
}

func splitAfter(s, sep string) []string {
	out := []string{}
	for {
		i := indexOf(s, sep)
		if i < 0 {
			return out
		}
		s = s[i+len(sep):]
		out = append(out, s)
	}
}

func indexOf(s, sub string) int {
	for i := 0; i+len(sub) <= len(s); i++ {
		if s[i:i+len(sub)] == sub {
			return i
		}
	}
	return -1
}
