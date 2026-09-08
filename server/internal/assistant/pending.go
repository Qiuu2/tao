package assistant

import (
	"strings"
	"time"
)

// 待确认动作（pending action）。
//
// # 它解决什么
//
// 「取消明天早读」有两种意思：**只取消明天那一次**，还是**以后都不放了**。
// 猜错哪一种都是错的，而且错得不明显 —— 用户以为只停一天，实际停到了永远。
// 所以先问一句，等用户回「就这一次」或「以后都这样」再动手。
//
// # 顺序上必须在 NLU 之前
//
// 上一轮问了「这次还是永久」，这一轮用户回一句「就这一次」。这句话送进模型
// 只会得到一个莫名其妙的意图（它不是一条广播指令）。所以 Chat 里第一件事
// 就是看有没有待确认的动作，有就在这里截住。原实现的 chat.py 也是这个顺序。
//
// # 存哪
//
// assistant_session.pending 一列，跟着会话走。会话过期（默认 30 分钟）
// pending 一起没。另外 pending 自己还有一个更短的超时，见 pendingTTL ——
// 隔了十分钟才回一句「就这一次」，用户多半已经在说别的事了。

// pendingTTL 是待确认动作自己的有效期。取自原实现的 PENDING_EXPIRE_SECONDS。
const pendingTTL = 5 * time.Minute

// pendingPrompt 是问「这次还是永久」的那句话。逐字取自原实现。
const pendingPrompt = "您想这次只执行一次，还是永久这么改呢？告诉我一声哈~"

// 用户放弃时说的词。取自原实现 _handle_pending_action 的那一行。
var pendingAbortWords = []string{"算了", "不用了", "停止", "取消", "没事了"}

// applyMode 是「这次」还是「永久」。
type applyMode string

const (
	modeOnce      applyMode = "once"
	modePermanent applyMode = "permanent"
)

// 判定用词。逐字取自 _detect_apply_mode 的两张表。
var (
	onceWords = []string{
		"一次", "一次性", "仅一次", "只这一次", "临时", "本次", "这次",
		"once", "one-time", "onetime",
	}
	permanentWords = []string{
		"永久", "长期", "一直", "以后都", "持续", "固定生效",
		"permanent", "always", "forever",
	}
)

// detectApplyMode 取自 _detect_apply_mode。
//
// ⚠ 顺序有讲究：先整串找，再去掉空白找，最后小写找英文。
// "once" 这类短词在第一轮就会命中中文分支里的 len(k) > 1 判断 ——
// Python 那边写的是 `if len(k) > 1`，对中文和英文都是按**字符**数，
// 这里的每个词都超过 1 个字符，等价于全都参与，照搬即可。
func detectApplyMode(text string) (applyMode, bool) {
	raw := text
	compact := compactText(raw)

	for _, k := range onceWords {
		if strings.Contains(raw, k) {
			return modeOnce, true
		}
	}
	for _, k := range permanentWords {
		if strings.Contains(raw, k) {
			return modePermanent, true
		}
	}
	for _, k := range onceWords {
		if c := compactText(k); c != "" && strings.Contains(compact, c) {
			return modeOnce, true
		}
	}
	for _, k := range permanentWords {
		if c := compactText(k); c != "" && strings.Contains(compact, c) {
			return modePermanent, true
		}
	}
	lowered := strings.ToLower(raw)
	if strings.Contains(lowered, "once") || strings.Contains(lowered, "one-time") ||
		strings.Contains(lowered, "onetime") {
		return modeOnce, true
	}
	if strings.Contains(lowered, "permanent") || strings.Contains(lowered, "always") ||
		strings.Contains(lowered, "forever") {
		return modePermanent, true
	}
	return "", false
}

// 是不是一句肯定的答复。用于 yes_no 那一类确认（删方案这种）。
var (
	// ⚠ 这张表只用在**删东西**的确认上，所以刻意不收「嗯」这类含糊的应答：
	// 「嗯？」是在表示困惑，不是在点头，而这一头点下去东西就没了。
	// 拿不准就走"没听懂"那一支，把问题再问一遍 —— 多问一句的代价远小于删错。
	yesWords = []string{"是", "对", "好", "确定", "确认", "可以", "继续", "删", "yes", "ok"}
	noWords  = []string{"不", "否", "别", "算了", "不用", "取消", "no"}
)

// detectYesNo 判断一句话是「确认」还是「否认」。
//
// ⚠ 先判否定再判肯定。「不用了」里含「用」不含肯定词，但「不确定」里
// 既有「不」也有「确定」—— 先判肯定就会把「不确定」当成确认，
// 那是在没有授权的情况下删东西。宁可判成否定。
func detectYesNo(text string) (bool, bool) {
	for _, w := range noWords {
		if strings.Contains(text, w) {
			return false, true
		}
	}
	for _, w := range yesWords {
		if strings.Contains(text, w) {
			return true, true
		}
	}
	return false, false
}

// pendingAction 是存进会话里的那一份待确认动作。
//
// 用 map 而不是结构体，是因为它要原样进 JSON 列、原样发给前端，
// 而前端那套渲染逻辑是照原实现的 pendingAction 字段写的。
type pendingAction struct {
	Kind      string              // "apply_mode"（这次/永久）或 "yes_no"（确认一下）
	Intent    Intent              // 等确认完要执行的意图
	Text      string              // 用户当初说的那句原话
	Slots     map[string][]string // 当初解析出来的槽位
	Prompt    string              // 问出去的那句话
	Summary   string              // 给用户看的一句话概述（删东西时列出要删什么）
	CreatedAt time.Time
	// Attempts 是"问了没答上来"的次数。见 maxPendingReasks。
	Attempts int
}

// maxPendingReasks 是同一个问题最多重复问几遍。
//
// # 为什么要有上限
//
// 待确认是在 NLU **之前**截住这一轮的，所以只要它还挂着，用户说什么都会被
// 当成"在回答那个问题"。用户改主意去说别的事（「今天有哪些任务」），
// 得到的却是又一遍「确定要删吗？」—— 助手卡住了，而且用户没有办法绕开。
//
// 原实现在周几消歧那一支里就有同一个模式（连续 3 次没进展就断环，
// 提示用户直接说日期）。这里把同样的做法用在所有待确认上：
// 问过 maxPendingReasks 遍还没答上来，就当用户已经在说别的事，
// 放掉这个待确认，让这一轮正常走 NLU。
//
// 取 1 而不是 3：删东西的确认问一遍没答上来，第二句多半就是别的事了。
// 代价只是用户要重说一遍那条删除指令，比卡死强得多。
const maxPendingReasks = 1

func (p *pendingAction) toMap() map[string]interface{} {
	slots := map[string]interface{}{}
	for k, v := range p.Slots {
		slots[k] = v
	}
	return map[string]interface{}{
		"kind":           p.Kind,
		"intent":         string(p.Intent),
		"original_text":  p.Text,
		"slots":          slots,
		"confirm_prompt": p.Prompt,
		"summary":        p.Summary,
		"created_at":     p.CreatedAt.Format("2006-01-02 15:04:05"),
		"attempts":       p.Attempts,
	}
}

func pendingFromMap(m map[string]interface{}) *pendingAction {
	if len(m) == 0 {
		return nil
	}
	p := &pendingAction{
		Kind:    asString(m["kind"]),
		Intent:  Intent(asString(m["intent"])),
		Text:    asString(m["original_text"]),
		Prompt:  asString(m["confirm_prompt"]),
		Summary: asString(m["summary"]),
		Slots:   map[string][]string{},
	}
	if raw, ok := m["slots"].(map[string]interface{}); ok {
		for k, v := range raw {
			// ⚠ 两种形态都要认：刚构造出来还没落库时是 []string，
			// 从 JSON 列读回来是 []interface{}。只认一种的后果是
			// 同一进程内传递的那一份槽位悄悄变空 —— 用户点了头，动作却没了目标。
			switch list := v.(type) {
			case []string:
				for _, item := range list {
					if item != "" {
						p.Slots[k] = append(p.Slots[k], item)
					}
				}
			case []interface{}:
				for _, item := range list {
					if s := asString(item); s != "" {
						p.Slots[k] = append(p.Slots[k], s)
					}
				}
			}
		}
	}
	switch v := m["attempts"].(type) {
	case float64: // 从 JSON 读回来是 float64
		p.Attempts = int(v)
	case int:
		p.Attempts = v
	}
	if t, err := time.ParseInLocation("2006-01-02 15:04:05",
		asString(m["created_at"]), nowFunc().Location()); err == nil {
		p.CreatedAt = t
	}
	return p
}

func (p *pendingAction) expired() bool {
	if p.CreatedAt.IsZero() {
		return true // 时间戳读不出来就当过期 —— 与原实现一致，宁可让用户重说一遍
	}
	return nowFunc().Sub(p.CreatedAt) > pendingTTL
}

func asString(v interface{}) string {
	s, _ := v.(string)
	return s
}

// 两类确认各自的按钮。抽成变量是因为"再问一遍"那一支也要用同一组 ——
// 问题重发了按钮却没了，用户就只能靠打字，而他刚才正是打字没打对。
var (
	applyModeChoices = []map[string]any{
		{"label": "只这一次", "value": "只这一次", "hint": ""},
		{"label": "以后都这样", "value": "永久", "hint": ""},
	}
	yesNoChoices = []map[string]any{
		{"label": "确认删除", "value": "确认", "hint": ""},
		{"label": "算了", "value": "算了", "hint": ""},
	}
)

// askApplyMode 生成一个「这次还是永久」的待确认动作。
func askApplyMode(intent Intent, text string, slots map[string][]string, summary string) actionResult {
	p := &pendingAction{
		Kind: "apply_mode", Intent: intent, Text: text, Slots: slots,
		Prompt: pendingPrompt, Summary: summary, CreatedAt: nowFunc(),
	}
	reply := confirmRuntimeReply(string(intent), summary, pendingPrompt)
	return actionResult{
		Reply:       reply,
		Pending:     p.toMap(),
		ConfirmKind: "apply_mode",
		Choices:     applyModeChoices,
	}
}

// askYesNo 生成一个「真的要做吗」的待确认动作。
//
// 原实现没有这一类 —— 它删方案是**不问的**。这里加上，因为删方案会连同
// 底下所有任务一起没，而且没有撤销：对话框里一句话说错就没了。
// 页面上删方案要勾选、点按钮、再确认一次，对话里至少也要问一句。
func askYesNo(intent Intent, text string, slots map[string][]string, summary, prompt string) actionResult {
	p := &pendingAction{
		Kind: "yes_no", Intent: intent, Text: text, Slots: slots,
		Prompt: prompt, Summary: summary, CreatedAt: nowFunc(),
	}
	return actionResult{
		Reply:       appendReplyDetails(summary, prompt),
		Pending:     p.toMap(),
		ConfirmKind: "yes_no",
		Choices:     yesNoChoices,
	}
}

// confirmRuntimeReply 取自 runtime_reply.py 的 confirm_runtime。
func confirmRuntimeReply(intent, summary, question string) string {
	return stableReply(intent+":confirm", []string{
		"{summary}~ {question}",
		"查到这些内容啦：{summary}。{question}",
		"结果在这儿哈：{summary}。{question}",
		"看到啦：{summary}~ {question}",
		"找到这些：{summary}。{question}",
	}, []string{summary, question},
		map[string]string{"summary": summary, "question": question})
}
