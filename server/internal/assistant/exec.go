package assistant

import (
	"context"
	"strconv"
	"strings"

	"htweb/internal/auth"
)

// 意图执行的调度层。
//
// 每个意图一个执行器，签名统一。分阶段接入：现在只有只读那几个，
// 写操作的意图会陆续补上。**没接入的意图如实说"还在接入中"**，
// 不假装执行了。

// actionResult 是一个执行器的产出。
type actionResult struct {
	Reply        string
	MissingSlots []string
	ActionLog    []map[string]any
	Choices      []map[string]any
	ConfirmKind  string
	Pending      map[string]any
	// Err 非空表示执行本身出错（查库失败之类），由上层转成给用户的话。
	Err error
}

type executor func(ctx context.Context, u *auth.User, slots map[string][]string) actionResult

// executors 是已经接入的意图。没在这张表里的意图会走"还在接入中"那条路。
func (s *Service) executors() map[Intent]executor {
	return map[Intent]executor{
		IntentQueryTerminal: s.execQueryTerminal,
		IntentCheckTerminal: s.execCheckTerminal,
		// query_task 还没接：它要先有一套中文时间范围解析（「今天」「明天8点到9点」），
		// 那是独立的一块。半搬会让「今天有哪些任务」把"今天"这个条件丢掉 ——
		// 给出一个看着像对、其实范围不对的答案，比明说没做更糟。
	}
}

// splitTerminalSlots 把槽位里的终端指认拆成 id、名字，并记下形式上就不合法的。
//
// 槽位名沿用原实现：terminal_id / terminal_name / terminal / zone_name。
// ⚠ 模型抽出来的 terminal_id 不保证是数字（用户可能说"终端A101"），
// 不是数字就当名字处理，而不是丢掉 —— 丢掉的表现是"我明明说了终端它却说没提供"。
func splitTerminalSlots(slots map[string][]string) (ids []int64, names []string, unresolved map[string][]string) {
	unresolved = map[string][]string{}
	for _, v := range slots["terminal_id"] {
		v = strings.TrimSpace(v)
		if v == "" {
			continue
		}
		if n, err := strconv.ParseInt(v, 10, 64); err == nil && n > 0 {
			ids = append(ids, n)
		} else {
			names = append(names, v)
		}
	}
	for _, key := range []string{"terminal_name", "terminal"} {
		for _, v := range slots[key] {
			if v = strings.TrimSpace(v); v != "" {
				names = append(names, v)
			}
		}
	}
	names = uniqueTexts(names)
	return ids, names, unresolved
}

func itoa(n int) string { return strconv.Itoa(n) }

// rawTextOf 取用户这一整句原话。
//
// 名称解析的第 ② 层要在原话里找候选（「把A101教室音箱停了」里能原样找到
// 库里的名字），所以执行器需要拿到它。chat 那边把原话塞进 slots 的
// __raw__ 这个内部键里传下来 —— 模型不会产出这个名字的槽位，不会撞车。
func rawTextOf(slots map[string][]string) string {
	if v := slots["__raw__"]; len(v) > 0 {
		return v[0]
	}
	return ""
}
