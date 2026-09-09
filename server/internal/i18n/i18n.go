// Package i18n 让后端按界面语言返回文案。
//
// # 要翻的是哪些字
//
// 三类，都会原样显示在界面上：
//
//	① 信封里的 msg      "终端不存在" / "保存成功" —— 前端直接弹出来
//	② data 里的展示文案  终端型号、紧急广播槽位名、"在线"/"播放中" 这类状态
//	③ 接口平台的目录     开发者接口那一页的说明（另见 openapi 包）
//
// **不翻**用户自己起的名字：终端名、媒体名、任务名、分区名。
// 那是他们的数据，翻了对不上他们嘴里说的东西。
//
// # 字典为什么用中文原文当键
//
// 后端有三千多条中文提示散在各处。给每条编一个 ID，再去改三千个调用点，
// 是一次纯粹的机械劳动，而且**改漏一处不会报错** —— 那条提示只是永远中文。
//
// 用中文原文当键就没有这个问题：调用点一个字都不用动，
// 字典里配了的就翻，没配的原样输出。补翻译 = 往字典里加一行，
// 不需要碰业务代码。代价是中文原文改了要同步改字典，
// 这一点由 dict_test.go 盯着（字典里配的每条都得真的在代码里出现过）。
package i18n

import (
	"context"
	"net/http"
	"strings"
)

// Lang 是界面语言。只有两种 —— 多一种就得多维护一份字典，
// 而这个产品目前只承诺中英。
type Lang string

const (
	ZH Lang = "zh"
	EN Lang = "en"
)

type ctxKey struct{}

// FromRequest 从 Accept-Language 头判断语言。
//
// 前端在 axios 拦截器里按当前界面语言设这个头（见 web/src/api/index.ts）。
// 判不出来一律按中文 —— 这是个中文产品，默认值错了要比默认中文更刺眼。
//
// ⚠ 不做 q 值加权那套完整协商：头是我们自己发的，值就是 "zh" 或 "en"。
// 为一个自己控制两端的字段实现一遍 RFC 4647，是给以后的人多一处要读的代码。
func FromRequest(r *http.Request) Lang {
	if r == nil {
		return ZH
	}
	v := strings.ToLower(strings.TrimSpace(r.Header.Get("Accept-Language")))
	if strings.HasPrefix(v, "en") {
		return EN
	}
	return ZH
}

// With 把语言放进 context，供**生成 data 里展示文案**的地方取用
// （终端型号、状态文字这些拼在业务层，到不了响应边界）。
func With(ctx context.Context, l Lang) context.Context {
	return context.WithValue(ctx, ctxKey{}, l)
}

// From 取当前请求的语言。取不到按中文 —— 后台任务、定时器这些没有请求上下文，
// 它们写的是日志不是界面，中文正合适。
func From(ctx context.Context) Lang {
	if ctx == nil {
		return ZH
	}
	if l, ok := ctx.Value(ctxKey{}).(Lang); ok {
		return l
	}
	return ZH
}

// T 按语言翻一条文案。字典里没有就原样返回。
//
// 原样返回而不是报错、也不是返回键名：漏配一条的后果是英文界面上出现
// 一句中文（看得见、能改），而返回键名会变成 "terminal.notFound" 这种
// 用户完全看不懂的东西。
func T(l Lang, zh string) string {
	if l != EN || zh == "" {
		return zh
	}
	if en, ok := dict[zh]; ok {
		return en
	}
	return zh
}

// TC 是 T 的 context 版，业务层用这个。
func TC(ctx context.Context, zh string) string {
	return T(From(ctx), zh)
}

// Writer 是「知道自己该用哪种语言」的 ResponseWriter。
//
// # 为什么走 ResponseWriter，而不是给 httpx.Fail 加一个 r 参数
//
// httpx.Fail(w, code, msg) 有 170 个调用点。加一个参数是一次纯机械的
// 大范围改动，把每个 handler 都搅一遍，而收益只是把一个值传过去。
//
// 包一层 ResponseWriter 就够了：中间件包上，httpx 在写响应前问它一句。
// 这正是标准库 http.ResponseController 用的那套 Unwrap 约定，
// 所以外面再包几层（比如审计用的 sniffWriter）也能一路问下去 ——
// 前提是那些包装器实现了 Unwrap，这一点由 httpx 那边的 langOf 兜底：
// 问不到就按中文，不会因为少一层实现而出错。
type Writer struct {
	http.ResponseWriter
	Lang Lang
}

// Language / Translate 让 httpx 不用 import 本包就能用上翻译
// （否则 i18n 想用 httpx 的错误码就成了循环依赖）。
func (w *Writer) Language() string { return string(w.Lang) }

// Translate 把一句中文换成当前语言的说法。
func (w *Writer) Translate(zh string) string { return T(w.Lang, zh) }

// Unwrap 让外层包装器能一路找到底下的 ResponseWriter。
func (w *Writer) Unwrap() http.ResponseWriter { return w.ResponseWriter }

// Middleware 解析语言，包好 ResponseWriter，并塞进 context。
func Middleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		l := FromRequest(r)
		next.ServeHTTP(&Writer{ResponseWriter: w, Lang: l}, r.WithContext(With(r.Context(), l)))
	})
}

// weekNames 是周几的短标签，**周日打头** —— 与 exemodel 掩码的位序一致
// （掩码第 1 位就是周日，依据见 ok112 的 SUBSTRING(exemodel, WEEKDAY()+2 …)）。
var weekNames = map[Lang][7]string{
	ZH: {"日", "一", "二", "三", "四", "五", "六"},
	EN: {"Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"},
}

// WeekdaysText 把「周几播」拼成一句人话。days 是 1..7，**1 = 周日**。
//
// # 为什么这个不能靠字典
//
// 中文是「周」+「日、一、二」这种拼法 —— 一个「周」字带着后面一串。
// 英文没有这个结构，得是 "Sun, Mon, Tue"。拿拼好的成品去查字典，
// 等于要为 2^7 种组合各配一条。所以拼装本身必须知道语言。
//
// 三个包（dashboard / typedtask / offline）原来各抄了一份一模一样的
// cycleText，现在都改成调这里 —— 抄三份的必然结果是改一处漏两处，
// 而漏掉的表现是「同一条任务在不同页面上显示的播放周期不一样」。
func WeekdaysText(ctx context.Context, days []int) string {
	l := From(ctx)
	switch len(days) {
	case 0:
		return T(l, "手动")
	case 7:
		return T(l, "每天")
	}
	names := weekNames[l]
	parts := make([]string, 0, len(days))
	for _, d := range days {
		if d >= 1 && d <= 7 {
			parts = append(parts, names[d-1])
		}
	}
	if l == EN {
		return strings.Join(parts, ", ")
	}
	return "周" + strings.Join(parts, "、")
}

// CycleText 直接从 exemodel 的 7 位掩码拼出「周几播」。
//
// 掩码是**周日打头**的 "0111110" 这种串，第 i 位为 '1' 表示那天播。
// 解析很短，和拼装放在一起 —— 分开放的结果就是三个包各写一份解析，
// 而这正是它们原来的样子。
func CycleText(ctx context.Context, mask string) string {
	days := make([]int, 0, 7)
	for i, c := range mask {
		if i >= 7 {
			break
		}
		if c == '1' {
			days = append(days, i+1) // 1 = 周日
		}
	}
	return WeekdaysText(ctx, days)
}
