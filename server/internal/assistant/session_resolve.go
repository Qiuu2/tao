package assistant

import (
	"strings"
)

// 多轮对话：指代消解、缺槽位续问、锁定项。
//
// # 原实现里这一层在哪
//
// 在 Python 的 src/session_manager.py，由 engine.infer 顺手调用 ——
// 也就是说**会话状态挂在模型那一侧**。
//
// 这套架构里旁挂服务只做推理，不碰状态（它连数据库都不知道），
// 所以这一层整个搬到 Go 这边。搬过来还有个额外的好处：会话落在
// assistant_session 表里，进程重启不丢，多个 htweb 实例也能共享。
//
// # 三件事
//
//	① 指代消解    「把刚才那个停掉」→ 从上一轮回填 task_name
//	② 缺槽位续问  上一轮说"还差时间范围"，这一轮只说「明天」，
//	              要接着上一轮那个意图做，而不是从头理解一句「明天」
//	③ 锁定项      已经确认过的方案名/任务名不许被本轮的模型输出改掉
//
// ② 是这里面最要紧的。没有它，一次追问就是一次死胡同：
// 助手问「要取消哪个时间范围」，用户答「明天」，助手把「明天」当成一句
// 全新的话去理解 —— 什么也做不了。用户会觉得"它刚才问我，我答了它却不认"。

// 指代词。取自 PRONOUN_TRIGGERS。
var pronounTriggers = []string{"刚刚", "刚才", "上一个", "上次", "它", "这个", "那个", "正在播的"}

// 重复播放的说法。取自 REPEAT_TRIGGERS。
var repeatTriggers = []string{
	"再播一次", "再放一次", "重播", "再来一遍", "重复播放", "再播一遍", "再放一遍",
	"再播一回", "再放一回", "播放一遍", "播放一次",
}

// 会把续问打断掉的说法。取自 CANCEL_TRIGGERS。
var askCancelTriggers = []string{"取消", "算了", "不用了", "别了", "先这样", "不用", "没事了"}

// 指代/续问回填时会用到的槽位。只回填这几个 ——
// 时间那几个**故意不回填**：用户说「把刚才那个也停掉」，指的是同一个对象，
// 不是同一个时间；把上一轮的时间也带过来会让范围莫名其妙。
var carryOverSlots = []string{
	"task_name", "task_id", "schedule_name", "terminal_name", "terminal_id",
	"zone_name", "media_name",
}

func containsAnyOf(text string, words []string) bool {
	for _, w := range words {
		if strings.Contains(text, w) {
			return true
		}
	}
	return false
}

// applyCoreference 把「刚才那个」这类说法回填成上一轮的对象。
//
// ⚠ 只在**这一轮自己没说**对象时才回填。用户说「把那个换成A101」，
// terminal_name 已经是 A101 了，回填上一轮的会把他刚说的话覆盖掉。
func applyCoreference(sess *Session, text string, slots map[string][]string) []string {
	if sess == nil || len(sess.LastSlots) == 0 {
		return nil
	}
	if !containsAnyOf(text, pronounTriggers) && !containsAnyOf(text, repeatTriggers) {
		return nil
	}
	var filled []string
	for _, key := range carryOverSlots {
		if len(slots[key]) > 0 {
			continue
		}
		if v := sess.LastSlots[key]; len(v) > 0 {
			slots[key] = v
			filled = append(filled, key)
		}
	}
	return filled
}

// askState 是"上一轮问了、这一轮在答"的那份上下文。
// 存在会话的 Locked 里（那一列本来就是给这类小东西用的）。
const (
	askIntentKey = "__ask_intent__"
	askSlotKey   = "__ask_slots__"
)

// rememberAsk 把这一轮没问全的意图与已知槽位记下来，供下一轮接着做。
func rememberAsk(sess *Session, intent Intent, slots map[string][]string) {
	if sess.Locked == nil {
		sess.Locked = map[string]string{}
	}
	sess.Locked[askIntentKey] = string(intent)
	sess.Locked[askSlotKey] = encodeSlots(slots)
}

// forgetAsk 清掉续问上下文。这一轮做成了、或者用户明确说算了，都要清。
func forgetAsk(sess *Session) {
	if sess.Locked == nil {
		return
	}
	delete(sess.Locked, askIntentKey)
	delete(sess.Locked, askSlotKey)
}

// resumeAsk 判断这一轮是不是在回答上一轮的追问，是就把两轮的槽位并起来。
//
// 返回 (要执行的意图, 并好的槽位, true)。
//
// ⚠ 本轮模型给出了一个**明确的别的意图**时不接管 —— 用户改主意了，
// 该做新的那件事。只有本轮是 none 或与上一轮同一个意图时才续。
func resumeAsk(sess *Session, intent Intent, slots map[string][]string) (Intent, map[string][]string, bool) {
	if sess == nil || sess.Locked == nil {
		return "", nil, false
	}
	last := Intent(sess.Locked[askIntentKey])
	if last == "" {
		return "", nil, false
	}
	if intent != IntentNone && Known(intent) && intent != last {
		return "", nil, false
	}
	merged := decodeSlots(sess.Locked[askSlotKey])
	if merged == nil {
		merged = map[string][]string{}
	}
	// 本轮说的覆盖上一轮 —— 用户是在补充/更正
	for k, v := range slots {
		if len(v) > 0 {
			merged[k] = v
		}
	}
	return last, merged, true
}

// encodeSlots / decodeSlots 把槽位塞进 Locked 那张 map[string]string。
//
// 用 \x1f（单元分隔符）和 \x1e（记录分隔符）而不是 JSON：
// 这两个字符 sanitize 会当控制字符清掉，用户不可能打出来，
// 所以不存在"用户输入里正好有分隔符"这种事。
const (
	slotFieldSep  = "\x1f"
	slotRecordSep = "\x1e"
)

func encodeSlots(slots map[string][]string) string {
	if len(slots) == 0 {
		return ""
	}
	keys := make([]string, 0, len(slots))
	for k := range slots {
		if strings.HasPrefix(k, "__") {
			continue // 内部键（__raw__ / __mode__）不进会话
		}
		keys = append(keys, k)
	}
	sortStrings(keys)
	recs := make([]string, 0, len(keys))
	for _, k := range keys {
		if len(slots[k]) == 0 {
			continue
		}
		recs = append(recs, k+slotFieldSep+strings.Join(slots[k], slotFieldSep))
	}
	return strings.Join(recs, slotRecordSep)
}

func decodeSlots(s string) map[string][]string {
	if s == "" {
		return nil
	}
	out := map[string][]string{}
	for _, rec := range strings.Split(s, slotRecordSep) {
		parts := strings.Split(rec, slotFieldSep)
		if len(parts) < 2 {
			continue
		}
		var values []string
		for _, v := range parts[1:] {
			if v != "" {
				values = append(values, v)
			}
		}
		if len(values) > 0 {
			out[parts[0]] = values
		}
	}
	return out
}
