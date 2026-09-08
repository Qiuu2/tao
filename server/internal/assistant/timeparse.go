package assistant

import (
	"regexp"
	"strconv"
	"strings"
	"time"
)

// 中文时间解析。逐条对着原实现抄的：
//
//	engine.py     _resolve_date_base / _parse_time_of_day / _parse_time_point / _contains_date_word
//	api_public.py _contains_explicit_date / _parse_phase1_date / _parse_phase1_time_anchor
//	              _query_time_has_date_scope / _query_time_has_clock_component
//	              _repair_query_time_fragment / _query_task_fuzzy_window_minutes
//	              _resolve_query_task_time_token / _normalize_query_task_time_filters
//
// # 为什么不用现成的中文时间库
//
// 换一个库就是换一套行为。用户说「今天下午」，原来查的是 12:00~18:00，
// 换个库可能变成 12:00~24:00 —— 查询结果悄悄变了，而且没人会发现。
// 这一整块的验收标准只有一条：**同样的话，解析出同样的时间**。
// 所以按行抄，并用跑原版 Python 生成的黄金用例卡住。
//
// # 时间基准可注入
//
// 「明天」「周五」这些都要相对今天算。测试必须能把今天钉死，
// 否则用例活不过一天。所以走 nowFunc 而不是直接 time.Now。

// nowFunc 是这一包取「现在」的唯一入口，测试里替换它把时间钉死。
var nowFunc = time.Now

// ---------- engine.py 那一层 ----------

// 取自 engine.py 的 _DATE_KEYWORDS。顺序无关，用 map 查。
var dateKeywords = []string{
	"今天", "明天", "后天", "大后天", "昨天", "前天", "今日", "明日", "昨日",
	"本周", "下周", "下下周", "这周", "上周",
	"本星期", "下星期", "下下星期", "这星期",
	"本礼拜", "下礼拜", "下下礼拜", "这礼拜",
	"下个周", "下个星期", "下个礼拜",
	"周一", "周二", "周三", "周四", "周五", "周六", "周日",
	"星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期天", "星期日",
	"礼拜一", "礼拜二", "礼拜三", "礼拜四", "礼拜五", "礼拜六", "礼拜天", "礼拜日",
}

var (
	reISODateInText = regexp.MustCompile(`\d{4}[-/]\d{1,2}[-/]\d{1,2}`)
	reCNDateInText  = regexp.MustCompile(`\d{1,2}月\d{1,2}[日号]`)
)

// containsDateWord 取自 engine.py 的 _contains_date_word。
func containsDateWord(text string) bool {
	if text == "" {
		return false
	}
	for _, kw := range dateKeywords {
		if strings.Contains(text, kw) {
			return true
		}
	}
	return reISODateInText.MatchString(text) || reCNDateInText.MatchString(text)
}

var (
	reExplicitISO = regexp.MustCompile(`(\d{4})[-/](\d{1,2})[-/](\d{1,2})`)
	reExplicitCN  = regexp.MustCompile(`(\d{1,2})月(\d{1,2})[日号]?`)
	reWeekdayWord = regexp.MustCompile(`(?:周|星期|礼拜)\s*([一二三四五六日天])`)
)

// weekdayMap 取自 _resolve_date_base 的 weekday_map：周一=0 … 周日=6。
var weekdayMap = map[string]int{"一": 0, "二": 1, "三": 2, "四": 3, "五": 4, "六": 5, "日": 6, "天": 6}

// resolveDateBase 取自 engine.py 的 _resolve_date_base。返回当天零点。
func resolveDateBase(text string) (time.Time, bool) {
	now := nowFunc()
	today := time.Date(now.Year(), now.Month(), now.Day(), 0, 0, 0, 0, now.Location())

	switch {
	case strings.Contains(text, "大后天"):
		return today.AddDate(0, 0, 3), true
	case strings.Contains(text, "后天"):
		return today.AddDate(0, 0, 2), true
	case strings.Contains(text, "明天"), strings.Contains(text, "明日"):
		return today.AddDate(0, 0, 1), true
	case strings.Contains(text, "昨天"), strings.Contains(text, "昨日"):
		return today.AddDate(0, 0, -1), true
	case strings.Contains(text, "前天"):
		return today.AddDate(0, 0, -2), true
	case strings.Contains(text, "今天"), strings.Contains(text, "今日"):
		return today, true
	}

	if m := reExplicitISO.FindStringSubmatch(text); m != nil {
		y, _ := strconv.Atoi(m[1])
		mo, _ := strconv.Atoi(m[2])
		d, _ := strconv.Atoi(m[3])
		if t, ok := validDate(y, mo, d, now.Location()); ok {
			return t, true
		}
	}

	if m := reExplicitCN.FindStringSubmatch(text); m != nil {
		mo, _ := strconv.Atoi(m[1])
		d, _ := strconv.Atoi(m[2])
		if t, ok := validDate(now.Year(), mo, d, now.Location()); ok {
			// 只写月日的，落在今天之前就理解成明年 —— 与原实现一致。
			if t.Before(today) {
				if t2, ok2 := validDate(now.Year()+1, mo, d, now.Location()); ok2 {
					return t2, true
				}
			}
			return t, true
		}
	}

	if m := reWeekdayWord.FindStringSubmatch(text); m != nil {
		target, ok := weekdayMap[m[1]]
		if ok {
			// Python 的 weekday()：周一=0；Go 的 Weekday()：周日=0。换算一次。
			todayWd := (int(now.Weekday()) + 6) % 7
			thisMonday := today.AddDate(0, 0, -todayWd)
			base := thisMonday
			switch {
			case containsAny(text, "下下周", "下下星期", "下下礼拜"):
				base = thisMonday.AddDate(0, 0, 14)
			case containsAny(text, "下周", "下星期", "下礼拜", "下个周", "下个星期", "下个礼拜"):
				base = thisMonday.AddDate(0, 0, 7)
			}
			return base.AddDate(0, 0, target), true
		}
	}
	return time.Time{}, false
}

// validDate 挡住 2 月 30 号这类 —— Python 的 datetime() 会抛 ValueError，
// Go 的 time.Date 却会自动进位成 3 月 2 号，行为不一样，必须显式挡。
func validDate(y, m, d int, loc *time.Location) (time.Time, bool) {
	if m < 1 || m > 12 || d < 1 || d > 31 {
		return time.Time{}, false
	}
	t := time.Date(y, time.Month(m), d, 0, 0, 0, 0, loc)
	if t.Year() != y || int(t.Month()) != m || t.Day() != d {
		return time.Time{}, false
	}
	return t, true
}

func containsAny(text string, words ...string) bool {
	for _, w := range words {
		if strings.Contains(text, w) {
			return true
		}
	}
	return false
}

var (
	reClockColon = regexp.MustCompile(`(\d{1,2})[:：](\d{1,2})`)
	reClockCN    = regexp.MustCompile(`(\d{1,2}|[零〇一二两三四五六七八九十]{1,3})点(?:(\d{1,2}|[零〇一二两三四五六七八九十]{1,3})分|半|一刻|三刻)?`)
)

// cnNum 取自 engine.py 的 _CN_NUM。
var cnNum = map[string]int{
	"零": 0, "〇": 0, "一": 1, "两": 2, "二": 2, "三": 3, "四": 4,
	"五": 5, "六": 6, "七": 7, "八": 8, "九": 9, "十": 10,
	"十一": 11, "十二": 12,
}

// parseTimeOfDay 取自 engine.py 的 _parse_time_of_day。
func parseTimeOfDay(text string) (hour, minute int, ok bool) {
	if m := reClockColon.FindStringSubmatch(text); m != nil {
		h, _ := strconv.Atoi(m[1])
		mi, _ := strconv.Atoi(m[2])
		return h, mi, true
	}
	m := reClockCN.FindStringSubmatch(text)
	if m == nil {
		return 0, 0, false
	}
	rawH := m[1]
	h, found := cnNum[rawH]
	if !found {
		var err error
		h, err = strconv.Atoi(rawH)
		if err != nil {
			return 0, 0, false
		}
	}
	minute = 0
	whole := m[0]
	switch {
	case strings.HasSuffix(whole, "半"):
		minute = 30
	case strings.HasSuffix(whole, "一刻"):
		minute = 15
	case strings.HasSuffix(whole, "三刻"):
		minute = 45
	case m[2] != "":
		if v, found := cnNum[m[2]]; found {
			minute = v
		} else if v, err := strconv.Atoi(m[2]); err == nil {
			minute = v
		}
	}
	// 时段词。注意「中午」那一支多一个 hour != 0 的条件，与原实现一致。
	if containsAny(text, "下午", "晚上", "夜里", "夜间", "傍晚") {
		if h < 12 {
			h += 12
		}
	} else if strings.Contains(text, "中午") {
		if h < 12 && h != 0 {
			h += 12
		}
	}
	return h, minute, true
}

// parseTimePoint 取自 engine.py 的 _parse_time_point（base 恒为 None 的那条路径 ——
// query_task 这一路调用从不传 base）。
func parseTimePoint(text string) (time.Time, bool) {
	text = strings.TrimSpace(text)
	if text == "" {
		return time.Time{}, false
	}
	dateBase, hasDate := resolveDateBase(text)
	hour, minute, hasClock := parseTimeOfDay(text)

	if hasDate && hasClock {
		return time.Date(dateBase.Year(), dateBase.Month(), dateBase.Day(), hour, minute, 0, 0, dateBase.Location()), true
	}
	if hasDate {
		return dateBase, true
	}
	if hasClock {
		now := nowFunc()
		ref := time.Date(now.Year(), now.Month(), now.Day(), 0, 0, 0, 0, now.Location())
		result := time.Date(ref.Year(), ref.Month(), ref.Day(), hour, minute, 0, 0, ref.Location())
		// 只说了钟点且已经过去了，理解成明天 —— 原实现如此。
		if result.Before(now) {
			result = result.AddDate(0, 0, 1)
		}
		return result, true
	}
	if t, ok := parseDateTimeLoose(text); ok {
		return t, true
	}
	return time.Time{}, false
}

// ---------- api_public.py 那一层 ----------

var reExplicitDateWord = regexp.MustCompile(
	`(今天|明天|后天|大后天|昨天|前天|` +
		`下下周|下下星期|下下礼拜|下周|下星期|下礼拜|下个周|下个星期|下个礼拜|` +
		`本周|这周|本星期|这星期|本礼拜|这礼拜|` +
		`\d{4}-\d{1,2}-\d{1,2}|` +
		`\d{1,2}月\d{1,2}日|\d{1,2}号|\d{1,2}日)`)

// containsExplicitDate 取自 _contains_explicit_date。
func containsExplicitDate(text string) bool {
	if text == "" {
		return false
	}
	return reExplicitDateWord.MatchString(text)
}

// parseDateTimeLoose 对应 helpers.py 的 parse_datetime / _parse_iso_date 那一串宽松解析。
func parseDateTimeLoose(text string) (time.Time, bool) {
	text = strings.TrimSpace(strings.ReplaceAll(text, "T", " "))
	if text == "" {
		return time.Time{}, false
	}
	loc := nowFunc().Location()
	for _, layout := range []string{
		"2006-01-02 15:04:05", "2006-01-02 15:04", "2006-01-02",
		"2006/01/02 15:04:05", "2006/01/02",
	} {
		if t, err := time.ParseInLocation(layout, text, loc); err == nil {
			return t, true
		}
	}
	return time.Time{}, false
}

// parsePhase1Date 取自 _parse_phase1_date。
func parsePhase1Date(text string) (time.Time, bool) {
	text = strings.TrimSpace(text)
	if text == "" {
		return time.Time{}, false
	}
	if t, ok := parseDateTimeLoose(text); ok {
		return t, true
	}
	return parseTimePoint(text)
}

// weekdayLabelFromText 取自 _weekday_label_from_text。
func weekdayLabelFromText(text string) string {
	m := reWeekdayWord.FindStringSubmatch(text)
	if m == nil {
		return ""
	}
	token := m[1]
	if token == "天" {
		token = "日"
	}
	return "周" + token
}

var reDateLiteral = regexp.MustCompile(`\d{4}[-/年]\d{1,2}[-/月]\d{1,2}|(\d{1,2}月\d{1,2}(?:日|号)?)|(\d{1,2}(?:日|号))`)

// timeAnchorKind 取自 _parse_phase1_time_anchor 的 kind 字段，
// query_task 只用得到 kind，别的字段没搬。
func timeAnchorKind(text string) string {
	text = strings.TrimSpace(text)
	if text == "" {
		return ""
	}
	weekday := weekdayLabelFromText(text)
	hasDateLiteral := reDateLiteral.MatchString(text) ||
		containsAny(text, "今天", "明天", "后天", "大后天", "昨天", "前天")

	if weekday != "" && !hasDateLiteral {
		return "weekday"
	}
	if _, ok := parsePhase1Date(text); ok {
		return "date"
	}
	if weekday != "" {
		return "weekday"
	}
	return ""
}

// queryTimeHasDateScope 取自 _query_time_has_date_scope：
// 这段话里有没有「哪一天」的意思。有 → 按日期窗口筛，没有 → 只按一天里的钟点筛。
func queryTimeHasDateScope(value string) bool {
	text := strings.TrimSpace(value)
	if text == "" {
		return false
	}
	if k := timeAnchorKind(text); k == "date" || k == "weekday" {
		return true
	}
	return containsDateWord(text) || containsExplicitDate(text)
}

var reClockComponent = regexp.MustCompile(`(\d{1,2}[:：]\d{1,2}|[零〇一二两三四五六七八九十\d]{1,3}\s*(点|时))`)

// queryTimeHasClockComponent 取自 _query_time_has_clock_component。
func queryTimeHasClockComponent(value string) bool {
	text := strings.TrimSpace(value)
	if text == "" {
		return false
	}
	return reClockComponent.MatchString(text)
}

// 时间片段允许续接的字符。取自 _QUERY_TASK_TIME_FRAGMENT_CHAR_RE。
var reFragmentChar = regexp.MustCompile(`^[0-9A-Za-z一二两三四五六七八九十零〇年月日号点分秒:：\-—–~～到至周星期礼拜上下今明后前昨本这中早晚晨午凌傍间夜个天半]$`)

var reRangeSep = regexp.MustCompile(`(?:到|至|~|～|-|–|—)`)

// repairQueryTimeFragment 取自 _repair_query_time_fragment。
//
// # 这个函数在补什么
//
// 模型抽槽位是按字打标的，抽出来常常缺尾巴：用户说「今天下午」，
// 抽出来是「今天下」。直接拿去解析就是解析不出来，用户看到的是
// "我说了时间它却说没听懂"。这里回原话里把被切掉的尾巴接回来。
func repairQueryTimeFragment(rawValue, originalText string) string {
	raw := strings.TrimSpace(rawValue)
	if raw == "" {
		return ""
	}
	text := strings.TrimSpace(originalText)
	if text == "" {
		return raw
	}
	explicitRepairs := map[string]string{
		"今天下": "今天下午", "今日下": "今日下午", "明天下": "明天下午",
		"后天下": "后天下午", "昨天下": "昨天下午",
	}
	if repaired, ok := explicitRepairs[raw]; ok && strings.Contains(text, repaired) {
		return repaired
	}
	if strings.HasSuffix(raw, "下") {
		if candidate := raw + "午"; strings.Contains(text, candidate) {
			return candidate
		}
	}
	index := strings.Index(text, raw)
	if index < 0 {
		return raw
	}
	// 从片段末尾往后，只要还是"时间字"就继续吃
	end := index + len(raw)
	for end < len(text) {
		r, size := decodeRuneAt(text, end)
		if !reFragmentChar.MatchString(string(r)) {
			break
		}
		end += size
	}
	candidate := strings.TrimSpace(text[index:end])
	if candidate != "" && !reRangeSep.MatchString(raw) {
		// 抽出来的本身不是一个区间，就不要把后面的「到 X」一起吃进来
		if loc := reRangeSep.FindStringIndex(candidate); loc != nil {
			candidate = strings.TrimSpace(candidate[:loc[0]])
		}
	}
	if candidate == "" {
		return raw
	}
	return candidate
}

func decodeRuneAt(s string, i int) (rune, int) {
	for j, r := range s[i:] {
		if j == 0 {
			return r, len(string(r))
		}
	}
	return 0, 1
}

// 模糊时段窗口。取自 _QUERY_TASK_FUZZY_WINDOWS，顺序不能动 ——
// 「午后」排在「下午」前面，「傍晚」排在「晚上」前面，命中的是先匹配上的那个。
var fuzzyWindows = []struct {
	Keyword string
	Start   int // 分钟
	End     int
}{
	{"凌晨", 0, 6 * 60},
	{"早上", 6 * 60, 12 * 60},
	{"早晨", 6 * 60, 12 * 60},
	{"上午", 6 * 60, 12 * 60},
	{"中午", 12 * 60, 14 * 60},
	{"午后", 12 * 60, 18 * 60},
	{"下午", 12 * 60, 18 * 60},
	{"傍晚", 17 * 60, 19 * 60},
	{"晚上", 18 * 60, 24 * 60},
	{"夜间", 18 * 60, 24 * 60},
	{"夜里", 18 * 60, 24 * 60},
}

// fuzzyWindowMinutes 取自 _query_task_fuzzy_window_minutes。
// 说了具体钟点（「下午3点」）就不是模糊时段，按点算。
func fuzzyWindowMinutes(value string) (int, int, bool) {
	text := strings.TrimSpace(value)
	if text == "" || queryTimeHasClockComponent(text) {
		return 0, 0, false
	}
	for _, w := range fuzzyWindows {
		if strings.Contains(text, w.Keyword) {
			return w.Start, w.End, true
		}
	}
	return 0, 0, false
}

func formatQueryFilterRaw(t time.Time, includeDate bool) string {
	if includeDate {
		return t.Format("2006-01-02 15:04")
	}
	return t.Format("15:04")
}

// timeToken 是一个时间词解析出来的东西。对应 _resolve_query_task_time_token 的返回。
type timeToken struct {
	DisplayRaw  string
	FilterStart string
	FilterEnd   string
	Point       time.Time
	HasPoint    bool
	WindowStart time.Time
	WindowEnd   time.Time
	HasWindow   bool
}

// resolveQueryTaskTimeToken 取自 _resolve_query_task_time_token。
func resolveQueryTaskTimeToken(rawValue, originalText string) timeToken {
	display := repairQueryTimeFragment(rawValue, originalText)
	tok := timeToken{DisplayRaw: display, FilterStart: display}
	if display == "" {
		return tok
	}
	startMin, endMin, fuzzy := fuzzyWindowMinutes(display)
	if !fuzzy {
		if p, ok := parseQueryDatetime(display); ok {
			tok.Point, tok.HasPoint = p, true
		}
		return tok
	}
	includeDate := queryTimeHasDateScope(display)
	var base time.Time
	var ok bool
	if includeDate {
		base, ok = parsePhase1Date(display)
		if !ok {
			base, ok = parseQueryDatetime(display)
		}
	} else {
		n := nowFunc()
		base = time.Date(n.Year(), n.Month(), n.Day(), 0, 0, 0, 0, n.Location())
		ok = true
	}
	if !ok {
		return tok
	}
	day := time.Date(base.Year(), base.Month(), base.Day(), 0, 0, 0, 0, base.Location())
	tok.WindowStart = day.Add(time.Duration(startMin) * time.Minute)
	tok.WindowEnd = day.Add(time.Duration(endMin) * time.Minute)
	tok.HasWindow = true
	tok.FilterStart = formatQueryFilterRaw(tok.WindowStart, includeDate)
	tok.FilterEnd = formatQueryFilterRaw(tok.WindowEnd, includeDate)
	return tok
}

// parseQueryDatetime 对应 _parse_query_datetime。
func parseQueryDatetime(value string) (time.Time, bool) {
	if strings.TrimSpace(value) == "" {
		return time.Time{}, false
	}
	if t, ok := parsePhase1Date(value); ok {
		return t, true
	}
	return parseTimePoint(value)
}

// queryTimeFilters 对应 _normalize_query_task_time_filters 的返回。
type queryTimeFilters struct {
	StartRaw       string
	EndRaw         string
	FilterStartRaw string
	FilterEndRaw   string
	Start          time.Time
	HasStart       bool
	End            time.Time
	HasEnd         bool
}

// normalizeQueryTaskTimeFilters 取自 _normalize_query_task_time_filters。
//
// 只给了起点时的行为值得留意：模糊时段（「今天下午」）会把窗口的两端都填上，
// 于是「下午有哪些任务」查的是 12:00~18:00 这个区间，而不是 12:00 这一个点。
func normalizeQueryTaskTimeFilters(text string, startValue, endValue string) queryTimeFilters {
	startTok := resolveQueryTaskTimeToken(startValue, text)
	endTok := resolveQueryTaskTimeToken(endValue, text)

	out := queryTimeFilters{
		StartRaw: startTok.DisplayRaw,
		EndRaw:   endTok.DisplayRaw,
	}
	out.FilterStartRaw = out.StartRaw
	out.FilterEndRaw = out.EndRaw

	pick := func(tok timeToken) (time.Time, bool) {
		if tok.HasWindow {
			return tok.WindowStart, true
		}
		if tok.HasPoint {
			return tok.Point, true
		}
		return time.Time{}, false
	}

	switch {
	case out.StartRaw != "" && out.EndRaw != "":
		out.Start, out.HasStart = pick(startTok)
		if endTok.HasWindow {
			out.End, out.HasEnd = endTok.WindowEnd, true
		} else if endTok.HasPoint {
			out.End, out.HasEnd = endTok.Point, true
		}
		out.FilterStartRaw = firstNonEmpty(startTok.FilterStart, out.StartRaw)
		out.FilterEndRaw = firstNonEmpty(endTok.FilterEnd, endTok.FilterStart, out.EndRaw)
	case out.StartRaw != "":
		out.Start, out.HasStart = pick(startTok)
		if startTok.HasWindow {
			out.End, out.HasEnd = startTok.WindowEnd, true
		}
		out.FilterStartRaw = firstNonEmpty(startTok.FilterStart, out.StartRaw)
		out.FilterEndRaw = startTok.FilterEnd
	case out.EndRaw != "":
		if endTok.HasWindow {
			out.Start, out.HasStart = endTok.WindowStart, true
			out.End, out.HasEnd = endTok.WindowEnd, true
			out.FilterStartRaw = firstNonEmpty(endTok.FilterStart, out.EndRaw)
			out.FilterEndRaw = firstNonEmpty(endTok.FilterEnd, out.FilterStartRaw)
		} else {
			out.End, out.HasEnd = endTok.Point, endTok.HasPoint
			out.FilterEndRaw = firstNonEmpty(endTok.FilterStart, out.EndRaw)
		}
	}
	return out
}

func firstNonEmpty(values ...string) string {
	for _, v := range values {
		if v != "" {
			return v
		}
	}
	return ""
}
