package assistant

import (
	"strings"
	"testing"
)

// 指代消解只在**这一轮自己没说**对象时才回填。
// 覆盖用户刚说的话是最难查的一类 bug：他说了 A，系统去动了 B。
func TestCoreferenceOnlyFillsMissing(t *testing.T) {
	sess := &Session{LastSlots: map[string][]string{
		"task_name":     {"早读预备铃"},
		"terminal_name": {"A101教室音箱"},
		"source_time":   {"明天"},
	}}

	// ① 有指代词、本轮没说对象 → 回填
	slots := map[string][]string{}
	filled := applyCoreference(sess, "把刚才那个停掉", slots)
	if len(filled) == 0 {
		t.Fatal("应当回填")
	}
	if got := strings.Join(slots["task_name"], ""); got != "早读预备铃" {
		t.Fatalf("task_name 期望回填成 早读预备铃，实际 %q", got)
	}

	// ② 本轮自己说了对象 → 不许覆盖
	slots = map[string][]string{"terminal_name": {"A102教室音箱"}}
	applyCoreference(sess, "把那个换成A102教室音箱", slots)
	if got := strings.Join(slots["terminal_name"], ""); got != "A102教室音箱" {
		t.Fatalf("本轮说的对象被覆盖了：%q", got)
	}

	// ③ 时间**故意不回填**：说「把刚才那个也停掉」指的是同一个对象，
	//    不是同一个时间。带过来会让范围莫名其妙。
	slots = map[string][]string{}
	applyCoreference(sess, "刚才那个", slots)
	if len(slots["source_time"]) != 0 {
		t.Fatalf("时间不该被回填，实际 %v", slots["source_time"])
	}

	// ④ 没有指代词 → 什么都不做
	slots = map[string][]string{}
	if filled := applyCoreference(sess, "今天有哪些任务", slots); len(filled) != 0 {
		t.Fatalf("没有指代词时不该回填，实际 %v", filled)
	}
}

// 缺槽位续问：助手问了、用户答了，就得接着上一轮做。
func TestResumeAsk(t *testing.T) {
	sess := &Session{}
	rememberAsk(sess, IntentCancelSchedule, map[string][]string{"task_name": {"早读预备铃"}})

	// ① 本轮是 none（一句「明天」模型给不出意图）→ 接管
	intent, merged, ok := resumeAsk(sess, IntentNone, map[string][]string{"source_time": {"明天"}})
	if !ok || intent != IntentCancelSchedule {
		t.Fatalf("应当续上 cancel_schedule，实际 %v/%v", intent, ok)
	}
	if strings.Join(merged["task_name"], "") != "早读预备铃" {
		t.Fatalf("上一轮的槽位没带过来：%v", merged)
	}
	if strings.Join(merged["source_time"], "") != "明天" {
		t.Fatalf("本轮的槽位没并进去：%v", merged)
	}

	// ② 本轮是同一个意图 → 也接管，本轮的值覆盖上一轮
	_, merged, ok = resumeAsk(sess, IntentCancelSchedule, map[string][]string{"task_name": {"放学铃"}})
	if !ok {
		t.Fatal("同一个意图应当续上")
	}
	if strings.Join(merged["task_name"], "") != "放学铃" {
		t.Fatalf("本轮的值应当覆盖上一轮：%v", merged)
	}

	// ③ 本轮是**别的**意图 → 不接管，用户改主意了
	if _, _, ok = resumeAsk(sess, IntentQueryTask, map[string][]string{}); ok {
		t.Fatal("换了意图不该续上 —— 用户改主意了，该做新的那件事")
	}

	// ④ 清掉之后不再接管
	forgetAsk(sess)
	if _, _, ok = resumeAsk(sess, IntentNone, map[string][]string{}); ok {
		t.Fatal("清掉之后不该再续")
	}
}

// 槽位要能原样存进 Locked 那张 map[string]string 再读回来。
func TestSlotCodecRoundTrip(t *testing.T) {
	in := map[string][]string{
		"task_name":   {"早读预备铃", "放学铃"},
		"source_time": {"明天"},
		// 内部键不进会话
		"__raw__":  {"把刚才那个停掉"},
		"__mode__": {"once"},
	}
	back := decodeSlots(encodeSlots(in))
	if len(back["task_name"]) != 2 || back["task_name"][1] != "放学铃" {
		t.Fatalf("多值槽位没还原：%v", back["task_name"])
	}
	if _, bad := back["__raw__"]; bad {
		t.Fatal("内部键不该进会话")
	}
	if _, bad := back["__mode__"]; bad {
		t.Fatal("内部键不该进会话")
	}
	if encodeSlots(nil) != "" || decodeSlots("") != nil {
		t.Fatal("空值应当原样往返")
	}
}
