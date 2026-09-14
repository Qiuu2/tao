package main

import (
	"bytes"
	"context"
	"io"
	"net/http/httptest"
	"strings"
	"testing"
)

// 这一组测试守的是一句需求：日志里必须写清「动的是哪个任务、哪台终端、哪个分区」，
// 超过一行用省略号。所以除了功能本身，还得守住两条不能破的规矩：
//   1. 每个配了 auditTargets 的接口，都要有对应的 auditLabels（不然解析出来没人用）；
//   2. 解析 body 绝不能把 body 吃掉（吃掉 = 日志功能把业务功能搞坏了）。

// TestAuditTargetsHaveLabels 防止配了对象、却忘了配动作名。
//
// 反过来不检查：auditLabels 比 auditTargets 多是正常的 ——
// 「创建备份」「修改服务器参数」这类接口本来就没有「哪一个对象」。
func TestAuditTargetsHaveLabels(t *testing.T) {
	for pattern := range auditTargets {
		if auditLabels[pattern] == "" {
			t.Errorf("%s 配了 auditTargets 但没有 auditLabels，解析出来的对象名没人写进日志", pattern)
		}
	}
}

// TestAuditTargetsAreWellFormed 挡住「配了表名却漏了列名」这种一眼看不出来的错。
//
// 漏了的后果是查库时拼出一句 `SELECT , FROM x` —— 不报错，只是日志里
// 那一栏永远是空的，而空了没人会发现。
func TestAuditTargetsAreWellFormed(t *testing.T) {
	for pattern, spec := range auditTargets {
		if spec.Noun == "" {
			t.Errorf("%s 没配 Noun，日志里会写成「删除：某某」", pattern)
		}
		if spec.PathName != "" && !strings.Contains(pattern, "{"+spec.PathName+"}") {
			t.Errorf("%s 配的 PathName=%q 在路由模式里不存在", pattern, spec.PathName)
		}
		if spec.Table == "" {
			continue
		}
		if spec.IDCol == "" || spec.NameCol == "" {
			t.Errorf("%s 配了 Table=%q 却漏了 IDCol/NameCol", pattern, spec.Table)
		}
		// 配了 PathID 的，路由模式里必须真有这个占位符，不然永远取不到 id
		if spec.PathID != "" && !strings.Contains(pattern, "{"+spec.PathID+"}") {
			t.Errorf("%s 配的 PathID=%q 在路由模式里不存在", pattern, spec.PathID)
		}
	}
}

// TestPeekBodyRestoresBody 是这一组里最要紧的一条。
//
// 解析 id 要把 body 读出来；读完不还回去，handler 拿到的就是个空 body。
// 那不是「日志少记了点东西」，而是所有带 body 的写接口集体失效。
func TestPeekBodyRestoresBody(t *testing.T) {
	const raw = `{"ids":[7,8],"name":"下课铃"}`
	r := httptest.NewRequest("DELETE", "/api/tasks", strings.NewReader(raw))
	r.Header.Set("Content-Type", "application/json")

	m := peekBody(r)
	if got := toIDs(m["ids"]); len(got) != 2 || got[0] != 7 || got[1] != 8 {
		t.Fatalf("ids 解析错了：%v", got)
	}
	back, err := io.ReadAll(r.Body)
	if err != nil {
		t.Fatal(err)
	}
	if string(back) != raw {
		t.Fatalf("body 没还回去：想要 %q，拿到 %q", raw, string(back))
	}
}

// TestPeekBodySkipsMultipart：上传接口的 body 可能有几十 MB，
// 不能为了记日志把它整个读进内存。
func TestPeekBodySkipsMultipart(t *testing.T) {
	r := httptest.NewRequest("POST", "/api/media/upload", bytes.NewReader([]byte("--x\r\n")))
	r.Header.Set("Content-Type", "multipart/form-data; boundary=x")
	if m := peekBody(r); m != nil {
		t.Fatalf("multipart 不该被解析，却拿到 %v", m)
	}
}

func TestToIDsAcceptsBothShapes(t *testing.T) {
	cases := []struct {
		in   interface{}
		want int
	}{
		{float64(3), 1},              // "id": 3
		{[]interface{}{1.0, 2.0}, 2}, // "ids": [1,2]
		{"5", 1},                     // 有的地方传字符串
		{float64(0), 0},              // 0 不是有效 id
		{nil, 0},
	}
	for _, c := range cases {
		if got := len(toIDs(c.in)); got != c.want {
			t.Errorf("toIDs(%v) 得到 %d 个，想要 %d 个", c.in, got, c.want)
		}
	}
}

// TestIDsFromPrefersExplicitBodyIDs 守住终端替换那一条。
//
// 替换的 body 里同时有 sourceId 和 targetId。日志要记的是**被换掉的那台**，
// 所以 auditTargets 里显式配了 sourceId；公共兜底清单不能把它盖掉。
func TestIDsFromPrefersExplicitBodyIDs(t *testing.T) {
	spec := auditTargets["PUT /api/terminals/replace"]
	r := httptest.NewRequest("PUT", "/api/terminals/replace", nil)
	ids := spec.idsFrom(r, map[string]interface{}{"sourceId": 11.0, "targetId": 22.0})
	if len(ids) != 1 || ids[0] != 11 {
		t.Fatalf("终端替换该记 sourceId=11，却拿到 %v", ids)
	}
}

// TestOneLineTruncates：需求原话是「超过一行用...代替」。
func TestOneLineTruncates(t *testing.T) {
	if got := oneLine("删除任务：任务「上午第一节」"); got != "删除任务：任务「上午第一节」" {
		t.Fatalf("没超长的不该动：%q", got)
	}
	long := "删除任务：任务" + strings.Repeat("很长的任务名", 20)
	got := oneLine(long)
	if n := len([]rune(got)); n != maxOperateRunes {
		t.Fatalf("截断后应为 %d 个字，实际 %d", maxOperateRunes, n)
	}
	if !strings.HasSuffix(got, "…") {
		t.Fatalf("截断后要补省略号：%q", got)
	}
	// 截出来还得塞得进 log.operate（varchar(255) 字节）
	if len(got) > 255 {
		t.Fatalf("截断后仍有 %d 字节，超过 operate 列宽", len(got))
	}
	if strings.Contains(oneLine("第一行\n第二行"), "\n") {
		t.Fatal("换行没被收掉，日志列表会显示成半截")
	}
}

// TestAuditDetailFallsBackToBodyName：新建类接口对象还不存在，查不到库，
// 名字只能从 body 里取。取不到也不能报错，退回只写动作名。
func TestAuditDetailFallsBackToBodyName(t *testing.T) {
	a := &app{} // st 为 nil：查库这条路走不通，正好验兜底
	r := httptest.NewRequest("POST", "/api/zones", strings.NewReader(`{"name":"教学楼A"}`))
	r.Header.Set("Content-Type", "application/json")
	if got := a.auditDetail("POST /api/zones", r, false); got != "终端分区「教学楼A」" {
		t.Fatalf("想要 终端分区「教学楼A」，拿到 %q", got)
	}

	r2 := httptest.NewRequest("POST", "/api/zones", strings.NewReader(`{}`))
	r2.Header.Set("Content-Type", "application/json")
	if got := a.auditDetail("POST /api/zones", r2, false); got != "" {
		t.Fatalf("什么都取不到时应返回空串（退回只写动作名），拿到 %q", got)
	}
}

// TestAuditDetailFallsBackToIDs：名字查不到，起码把编号写上 ——
// 「删除终端：终端#12」仍然指得出是哪一台，比一行光秃秃的「删除终端」强。
func TestAuditDetailFallsBackToIDs(t *testing.T) {
	a := &app{}
	r := httptest.NewRequest("DELETE", "/api/terminals", strings.NewReader(`{"ids":[12,13]}`))
	r.Header.Set("Content-Type", "application/json")
	if got := a.auditDetail("DELETE /api/terminals", r, false); got != "终端#12,13" {
		t.Fatalf("想要 终端#12,13，拿到 %q", got)
	}
}

// TestAuditDetailUnknownPatternIsSilent：没配的接口不报错，只是没有对象名。
func TestAuditDetailUnknownPatternIsSilent(t *testing.T) {
	a := &app{}
	const pattern = "PUT /api/server/params" // 全局设置，本来就没有「哪一个」
	if _, configured := auditTargets[pattern]; configured {
		t.Fatalf("%s 现在配了对象，这条测试要换一个没配的路由", pattern)
	}
	r := httptest.NewRequest("PUT", "/api/server/params", nil)
	if got := a.auditDetail(pattern, r, false); got != "" {
		t.Fatalf("没配的接口应返回空串，拿到 %q", got)
	}
}

// TestAuditDetailUsesPathName：开发者接口按名字寻址，路径里那一段就是对象名。
// 这条路**不查库** —— 用密钥调的请求没有会话，查库那条路本来也走不通。
func TestAuditDetailUsesPathName(t *testing.T) {
	a := &app{}
	r := httptest.NewRequest("DELETE", "/openapi/v1/tasks/上午第一节", nil)
	r.SetPathValue("ref", "上午第一节")
	if got := a.auditDetail("DELETE /openapi/v1/tasks/{ref}", r, false); got != "任务「上午第一节」" {
		t.Fatalf("想要 任务「上午第一节」，拿到 %q", got)
	}
}

// TestAuditDetailSkipsDBWhenNotAllowed：没通过身份验证时不查库，
// 但路径/请求体里现成的东西照样用 —— 降级的是查库，不是整条功能。
func TestAuditDetailSkipsDBWhenNotAllowed(t *testing.T) {
	a := &app{} // st 为 nil：真去查库会 panic，正好验「没查」
	r := httptest.NewRequest("DELETE", "/api/terminals", strings.NewReader(`{"ids":[12]}`))
	r.Header.Set("Content-Type", "application/json")
	if got := a.auditDetail("DELETE /api/terminals", r, false); got != "终端#12" {
		t.Fatalf("不查库时该退回写编号，拿到 %q", got)
	}
}

// TestNoteAuditTargetRoundTrip 守住那条从 handler 往回传的通道。
//
// 用指针盒子而不是 context 里存字符串，是因为 context 的值只能往下传；
// 哪天有人「简化」成 WithValue(ctx, key, "名字")，中间件就永远读不到了，
// 而且不报错，只是日志里那一栏悄悄空掉。
func TestNoteAuditTargetRoundTrip(t *testing.T) {
	r := httptest.NewRequest("POST", "/api/media/upload", nil)
	r, note := withAuditNote(r)

	// 模拟 handler：它拿到的是再包过一层 context 的请求（鉴权中间件就这么干）
	inner := r.WithContext(context.WithValue(r.Context(), struct{ k int }{1}, "x"))
	noteAuditTarget(inner.Context(), namesDetail("媒体", []string{"上课铃.mp3"}))

	if note.text != "媒体「上课铃.mp3」" {
		t.Fatalf("handler 回填的对象名没传回中间件：%q", note.text)
	}
}

// TestNoteAuditTargetOutsideMiddleware：不走审计中间件的接口调它不该崩。
func TestNoteAuditTargetOutsideMiddleware(t *testing.T) {
	noteAuditTarget(context.Background(), "媒体「x.mp3」") // 不 panic 即通过
}

func TestNamesDetail(t *testing.T) {
	if got := namesDetail("媒体", nil); got != "" {
		t.Fatalf("一个名字都没有时该返回空串，拿到 %q", got)
	}
	if got := namesDetail("媒体", []string{"a.mp3"}); got != "媒体「a.mp3」" {
		t.Fatalf("拿到 %q", got)
	}
	got := namesDetail("媒体", []string{"a", "b", "c", "d", "e"})
	if got != "媒体「a」「b」「c」等 5 个" {
		t.Fatalf("超过 %d 个要收成「等 N 个」，拿到 %q", maxNamesInLog, got)
	}
}

// auditNoTargetOK 是**允许**日志里没有对象名的写接口，每条都写清为什么。
//
// 需求是「所有操作内容需要写明具体哪个任务、哪个终端、哪个分区」。这份清单是
// 那句话的例外，而例外必须是一条一条想过的 —— 所以新加写接口时，要么在
// auditTargets 里配上对象，要么在这里写明为什么它没有对象。
// 两样都不做，TestEveryWriteRouteNamesItsTarget 就会红。
var auditNoTargetOK = map[string]string{
	"POST /api/media/upload":         "body 是 multipart，中间件按设计不解（可能几十 MB）；文件名由 handler 用 noteAuditTarget 回填",
	"PUT /api/logs/retention":        "改的是一个全局值不是某个对象；改成了多久由 handler 用 noteAuditTarget 回填",
	"PUT /api/time/ntp":              "改的是一个 NTP 服务器地址，不是某个对象；地址由 handler 用 noteAuditTarget 回填",
	"PUT /api/account/password":      "改的是自己的密码，操作人就是对象，log 表的 user 列已经写了",
	"DELETE /api/assistant/history":  "清的是自己的指令历史，操作人就是对象",
	"POST /api/offline/purge-all":    "清的是全部离线数据，本来就没有「哪一个」",
	"PUT /api/assistant/settings":    "全局设置，没有「哪一个对象」",
	"PUT /api/dashboard/shortcuts":   "全局的界面设置，改的是一整块配置",
	"PUT /api/dashboard/quick-tasks": "全局的看板快捷任务，改的是一整块配置",
	"PUT /api/server/params":         "服务器参数是一整份配置，不是某一个对象",
	"PUT /api/server/auto-restart":   "定时重启是一个全局开关加时间",
	"PUT /api/time/clock":            "设的是服务器时钟，没有「哪一个对象」",
}

// TestEveryWriteRouteNamesItsTarget 守住需求那句话：
// 每个会记日志的写接口，要么日志里写得出动的是谁，要么在 auditNoTargetOK 里
// 写明为什么写不出。漏配一个不会报错，只会让那一条日志悄悄退回「只有动作名」，
// 而那正是这次要修的毛病 —— 所以用测试挡住。
func TestEveryWriteRouteNamesItsTarget(t *testing.T) {
	for pattern := range auditLabels {
		if _, ok := auditTargets[pattern]; ok {
			continue
		}
		reason, ok := auditNoTargetOK[pattern]
		if !ok {
			t.Errorf("%s 会记日志但写不出动的是谁：在 auditTargets 里配一条，"+
				"或者在 auditNoTargetOK 里写明为什么不用配", pattern)
			continue
		}
		if len([]rune(reason)) < 10 {
			t.Errorf("%s 的豁免理由太短（%q）—— 写清楚，不然下一个人没法判断该不该照做",
				pattern, reason)
		}
	}
	// 反过来：清单里不该留下已经不存在或已经配了对象的路由
	for pattern := range auditNoTargetOK {
		if _, ok := auditLabels[pattern]; !ok {
			t.Errorf("auditNoTargetOK 里的 %s 已经不是会记日志的路由了，删掉", pattern)
		}
		if _, ok := auditTargets[pattern]; ok {
			t.Errorf("%s 已经在 auditTargets 里配了对象，从 auditNoTargetOK 里删掉", pattern)
		}
	}
}
