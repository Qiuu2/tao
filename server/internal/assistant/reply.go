package assistant

import (
	"crypto/sha1"
	"encoding/hex"
	"fmt"
	"strconv"
	"strings"
)

// 回话措辞。**逐函数复刻原实现的 backend/assistant/runtime_reply.py**。
//
// # 为什么要一字不差
//
// 助手的措辞不是随口生成的，是**按 SHA1 稳定选择**的：
//
//	seed   = "|".join(seed_parts)
//	digest = sha1(intent + "|" + seed)
//	index  = int(digest[:8], 16) % len(variants)
//
// 同样的输入必出同样的回复 —— 这让回话可测试、可复现，也让用户不会觉得
// 助手"这次这么说下次那么说"。所以移植时**哈希算法、取位方式、取模的顺序
// 都不能变**，变一位就是另一套话术。
//
// reply_test.go 里的用例是拿原 Python 实现真跑出来的输出当基准的。

// 默认的结尾陪伴语。取自 runtime_reply.DEFAULT_FOLLOWUP。
const defaultFollowup = "还有别的需要随时叫我哈~"

// 这几个意图算「高价值」，措辞更亲切，并且结尾会补一句陪伴语。
// 取自 _HIGH_PERSONA_FOLLOWUP_INTENTS。
var highPersonaFollowupIntents = map[string]bool{
	"create_schedule": true,
	"play_media":      true,
	"adjust_volume":   true,
	"query_task":      true,
	"query_terminal":  true,
}

// 查询类里用更亲切措辞的那两个。取自 HIGH_PERSONA_QUERY_INTENTS。
var highPersonaQueryIntents = map[string]bool{
	"query_task":     true,
	"query_terminal": true,
}

// 已经带了这些开头的句子不再套壳。取自 _LIGHT_PREFIX_TOKENS。
var lightPrefixTokens = []string{
	"小电已经", "搞定啦", "搞定，", "好嘞", "好哒", "OK啦", "OK，",
	"安排好了", "安排好啦", "嗯嗯，", "可以~",
}

// seedNum 把一个计数变成**种子里该有的样子**。
//
// ⚠ 这是复刻原实现的一个坑，不是我们自己的设计：
// Python 那边组种子写的是 `str(part or "").strip()`，而 0 在 Python 里是 falsy，
// 于是 `0 or ""` 得到的是空串 —— **计数为 0 的那一位在种子里是空的，不是 "0"**。
//
// 看着像 bug，但它已经决定了现网每一句回话选的是哪个模板。改"对"就等于
// 把所有话术换一遍，同一句问话在新旧两版会得到不同回复。所以照搬。
func seedNum(n int) string {
	if n == 0 {
		return ""
	}
	return strconv.Itoa(n)
}

// stableReply 按 (intent, seed) 稳定地从 variants 里挑一句并填模板。
//
// ⚠ 与 Python 版逐位对齐：sha1 十六进制串取**前 8 个字符**当无符号整数再取模。
// 用 uint64 承接，别用 int32 —— 8 位十六进制最大 0xFFFFFFFF 会溢出有符号 32 位。
func stableReply(intent string, variants []string, seedParts []string, kv map[string]string) string {
	if len(variants) == 0 {
		return ""
	}
	parts := make([]string, len(seedParts))
	for i, p := range seedParts {
		parts[i] = strings.TrimSpace(p)
	}
	seed := strings.Join(parts, "|")
	sum := sha1.Sum([]byte(intent + "|" + seed))
	digest := hex.EncodeToString(sum[:])
	n, _ := strconv.ParseUint(digest[:8], 16, 64)
	tpl := variants[n%uint64(len(variants))]
	return formatTemplate(tpl, kv)
}

// formatTemplate 做 Python str.format 里我们用到的那一小部分：{name} 替换。
// 不支持 {} 位置参数与格式说明符 —— 原实现的模板里也没有用到。
func formatTemplate(tpl string, kv map[string]string) string {
	if len(kv) == 0 {
		return tpl
	}
	out := tpl
	for k, v := range kv {
		out = strings.ReplaceAll(out, "{"+k+"}", v)
	}
	return out
}

// uniqueTexts 去空去重且保持原顺序。取自 unique_texts。
func uniqueTexts(values []string) []string {
	seen := map[string]bool{}
	out := []string{}
	for _, v := range values {
		t := strings.TrimSpace(v)
		if t == "" || seen[t] {
			continue
		}
		seen[t] = true
		out = append(out, t)
	}
	return out
}

// previewNames 把一串名字缩成 "A、B、C等5个终端"。取自 preview_names。
func previewNames(values []string, limit int, noun string) string {
	names := uniqueTexts(values)
	if len(names) == 0 {
		return ""
	}
	if len(names) <= limit {
		return strings.Join(names, "、")
	}
	return fmt.Sprintf("%s等%d%s", strings.Join(names[:limit], "、"), len(names), noun)
}

// 句尾要修掉的标点。与 _trim_terminal_punctuation 的字符集一致。
const trimPunct = "。！？；，、,.!?;:~"

func trimTerminalPunct(s string) string {
	return strings.TrimRight(strings.TrimSpace(s), trimPunct)
}

// finalizeSentence 没有句尾标点就补一个句号。取自 _finalize_sentence。
func finalizeSentence(s string) string {
	t := strings.TrimSpace(s)
	if t == "" {
		return ""
	}
	r := []rune(t)
	switch r[len(r)-1] {
	case '。', '！', '？', '~':
		return t
	}
	return t + "。"
}

// appendReplyDetails 把明细各占一行接在主句后面。取自 append_reply_details。
func appendReplyDetails(main string, details ...string) string {
	lines := []string{strings.TrimSpace(main)}
	for _, d := range details {
		if t := strings.TrimSpace(d); t != "" {
			lines = append(lines, t)
		}
	}
	out := []string{}
	for _, l := range lines {
		if l != "" {
			out = append(out, l)
		}
	}
	return strings.Join(out, "\n")
}

// appendFollowup 接一句陪伴语。取自 append_followup。
func appendFollowup(reply, followup string) string {
	main := finalizeSentence(reply)
	extra := strings.TrimSpace(followup)
	if extra == "" {
		return main
	}
	r := []rune(extra)
	switch r[len(r)-1] {
	case '。', '！', '？', '~':
	default:
		extra += "~"
	}
	if strings.HasSuffix(main, extra) {
		return main
	}
	if main == "" {
		return extra
	}
	return main + " " + extra
}

// lightQueryReply 给查询类的回复套一层更亲切的壳。取自 _light_query_reply。
//
// ⚠ "小电已经" 那个判断在 Python 里是 core[:8] —— 按**字符**取 8 个，不是字节。
// Go 里必须先转 rune 再切，直接切字节会把汉字劈开。
func lightQueryReply(intent, base string, seedParts []string) string {
	core := trimTerminalPunct(base)
	if core == "" {
		return finalizeSentence(base)
	}
	for _, tok := range lightPrefixTokens {
		if strings.HasPrefix(core, tok) {
			return finalizeSentence(base)
		}
	}
	head := []rune(core)
	if len(head) > 8 {
		head = head[:8]
	}
	if strings.Contains(string(head), "小电已经") {
		return finalizeSentence(base)
	}

	var variants []string
	if highPersonaQueryIntents[baseIntentName(intent)] {
		variants = []string{
			"{core}~ 小电已经帮您查到啦。",
			"{core}~ 都帮您整理好了。",
			"{core}，小电这边已经查清楚了哦。",
			"{core}~ 看一下是这些哈。",
			"查到啦，{core}~",
		}
	} else {
		variants = []string{
			"{core}~ 小电这边整理好了。",
			"{core}，看一下哦~",
			"{core}~ 都帮您查到了。",
			"查到啦，{core}~",
			"{core}，结果在这儿啦~",
		}
	}
	seed := append([]string{core}, seedParts...)
	return stableReply(intent+":query:light", variants, seed, map[string]string{"core": core})
}

// queryRuntimeReply 是查询类回话的入口。取自 query_runtime。
func queryRuntimeReply(intent string, variants []string, seedParts []string, kv map[string]string) string {
	base := stableReply(intent, variants, seedParts, kv)
	return lightQueryReply(intent, base, seedParts)
}

func baseIntentName(intent string) string {
	if i := strings.Index(intent, ":"); i >= 0 {
		return intent[:i]
	}
	return intent
}

// appendInlineDetail 把一条明细并进主句（用「，」接，不换行）。
// 取自 _append_inline_reply_detail。
func appendInlineDetail(reply, detail string) string {
	main := strings.TrimSpace(reply)
	extra := strings.Trim(strings.TrimSpace(detail), "。")
	if main == "" || extra == "" {
		if main != "" {
			return main
		}
		return extra
	}
	return strings.TrimRight(main, "。！？；，、,.!?;:") + "，" + extra + "。"
}

func stripKeyIntentFollowup(reply string) string {
	text := strings.TrimSpace(reply)
	followup := strings.TrimSpace(defaultFollowup)
	if text == "" || followup == "" {
		return text
	}
	if strings.HasSuffix(text, " "+followup) {
		return strings.TrimRight(strings.TrimSuffix(text, " "+followup), " ")
	}
	if strings.HasSuffix(text, followup) {
		return strings.TrimRight(strings.TrimSuffix(text, followup), " ")
	}
	return text
}

func normalizeHighPersonaIntent(intent string) string {
	name := strings.TrimSpace(intent)
	if strings.HasPrefix(name, "adjust_volume") {
		return "adjust_volume"
	}
	return name
}

// finalizeKeyIntentReply 收尾：去掉可能已有的陪伴语、把明细并进主句、
// 再按意图决定要不要补陪伴语。取自 _finalize_key_intent_reply。
func finalizeKeyIntentReply(intent, reply string, details ...string) string {
	merged := stripKeyIntentFollowup(reply)
	for _, d := range details {
		if t := strings.TrimSpace(d); t != "" {
			merged = appendInlineDetail(merged, t)
		}
	}
	if !highPersonaFollowupIntents[normalizeHighPersonaIntent(intent)] {
		return merged
	}
	return appendFollowup(merged, defaultFollowup)
}

// replyUnresolvedLine 取自 _reply_unresolved_line。
func replyUnresolvedLine(unresolved map[string][]string) string {
	if len(unresolved) == 0 {
		return ""
	}
	return fmt.Sprintf("以下对象没有匹配上：%s。", formatUnresolved(unresolved))
}

// formatUnresolved 复刻 Python 里 f-string 直接格式化 dict 的样子：
// {'terminal_name': ['A999']} —— 单引号、键值用冒号、逗号加空格分隔。
// 看着别扭，但这是原实现回给用户的原文，1:1 复刻就得连这个一起搬。
func formatUnresolved(m map[string][]string) string {
	if len(m) == 0 {
		return "{}"
	}
	keys := make([]string, 0, len(m))
	for k := range m {
		keys = append(keys, k)
	}
	sortStrings(keys)
	parts := make([]string, 0, len(keys))
	for _, k := range keys {
		vals := make([]string, 0, len(m[k]))
		for _, v := range m[k] {
			vals = append(vals, "'"+v+"'")
		}
		parts = append(parts, "'"+k+"': ["+strings.Join(vals, ", ")+"]")
	}
	return "{" + strings.Join(parts, ", ") + "}"
}

func sortStrings(s []string) {
	for i := 1; i < len(s); i++ {
		for j := i; j > 0 && s[j] < s[j-1]; j-- {
			s[j], s[j-1] = s[j-1], s[j]
		}
	}
}

// ---------- 成功 / 追问 / 失败 三类回话 ----------
//
// 下面三个取自 runtime_reply.py 的 _light_success_reply / success_runtime /
// ask_runtime / failure_runtime。写操作的回话都从这里出。

// highPersonaSuccessIntents 取自 HIGH_PERSONA_SUCCESS_INTENTS。
// ⚠ 与 highPersonaFollowupIntents 不是同一张表：这张里没有两个 query_*，
// 却多了三个 adjust_volume_* 变体。抄错一张，措辞就整批换掉。
var highPersonaSuccessIntents = map[string]bool{
	"create_schedule":        true,
	"play_media":             true,
	"adjust_volume":          true,
	"adjust_volume_global":   true,
	"adjust_volume_task":     true,
	"adjust_volume_terminal": true,
}

// lightSuccessReply 取自 _light_success_reply。
//
// ⚠ 与 lightQueryReply 有一处不同：这里**没有** core[:8] 里找「小电已经」
// 那一步，只按前缀表判断。看着像遗漏，但它决定了现网的措辞，照搬。
func lightSuccessReply(intent, base string, seedParts []string) string {
	core := trimTerminalPunct(base)
	if core == "" {
		return finalizeSentence(base)
	}
	for _, tok := range lightPrefixTokens {
		if strings.HasPrefix(core, tok) {
			return finalizeSentence(base)
		}
	}
	var variants []string
	if highPersonaSuccessIntents[baseIntentName(intent)] {
		variants = []string{
			"搞定啦，{core}~",
			"好嘞，{core}~ 还有别的随时叫我哈~",
			"{core}，小电已经帮您安排好啦~",
			"OK啦，{core}~",
			"{core}~ 搞定！",
			"安排好啦，{core}~",
		}
	} else {
		variants = []string{
			"搞定，{core}~",
			"好哒，{core}~",
			"{core}，搞定啦~",
			"嗯嗯，{core}~",
			"{core}，安排好啦~",
			"OK，{core}~",
		}
	}
	seed := append([]string{core}, seedParts...)
	return stableReply(intent+":success:light", variants, seed, map[string]string{"core": core})
}

// successRuntimeReply 取自 success_runtime。
func successRuntimeReply(intent string, variants []string, seedParts []string, kv map[string]string) string {
	base := stableReply(intent, variants, seedParts, kv)
	return lightSuccessReply(intent, base, seedParts)
}

// askRuntimeReply 取自 ask_runtime：缺东西时问一句。
func askRuntimeReply(intent, need, example string) string {
	base := stableReply(intent+":ask", []string{
		"还差{need}哦，告诉我一下哈~",
		"{need}还没说呢，补一下哈~",
		"再告诉我一下{need}吧~",
		"嗯，{need}还差点信息，补一下我就帮您安排~",
		"您把{need}补齐我就接着做啦~",
	}, []string{need, example}, map[string]string{"need": need, "example": example})
	if example != "" {
		return appendReplyDetails(base, "举个例子："+example)
	}
	return base
}

// failureRuntimeReply 取自 failure_runtime：没做成时说一句，能说清原因就说原因。
func failureRuntimeReply(intent, topic string, seedParts []string, reason, suggestion string) string {
	var variants []string
	if reason != "" {
		variants = []string{
			"哎，这次{topic}没成功，因为{reason}。",
			"{topic}没搞定呢，原因是{reason}~",
			"唉，{topic}失败了，{reason}。",
			"emm，{topic}没成，{reason}哎。",
			"{topic}这次出了点问题，{reason}。",
		}
	} else {
		variants = []string{
			"哎，这次{topic}没处理好...",
			"{topic}这次没成功呢，再试一下哈~",
			"唉，{topic}没弄成，要不换种方式再来？",
			"emm，{topic}没搞定，能告诉我详细点情况吗？",
			"{topic}这次有点不顺，重试一下看看哈~",
		}
	}
	seed := append([]string{topic, reason}, seedParts...)
	main := stableReply(intent+":failure", variants, seed,
		map[string]string{"topic": topic, "reason": reason})
	if suggestion != "" {
		return appendReplyDetails(main, suggestion)
	}
	return main
}
