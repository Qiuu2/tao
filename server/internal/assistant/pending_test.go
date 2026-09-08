package assistant

import (
	"testing"
	"time"
)

// detectApplyMode 决定「取消明天早读」是停一天还是停到永远。判错就是停错了范围。
func TestDetectApplyMode(t *testing.T) {
	cases := []struct {
		in   string
		want applyMode
		ok   bool
	}{
		{"就这一次", modeOnce, true},
		{"只这一次", modeOnce, true},
		{"临时改一下", modeOnce, true},
		{"本次", modeOnce, true},
		{"once", modeOnce, true},
		{"永久", modePermanent, true},
		{"以后都这样", modePermanent, true},
		{"一直这样", modePermanent, true},
		{"forever", modePermanent, true},
		{"嗯", "", false},
		{"什么", "", false},
		{"", "", false},
	}
	for _, c := range cases {
		got, ok := detectApplyMode(c.in)
		if ok != c.ok || got != c.want {
			t.Fatalf("%q：期望 (%v,%v)，实际 (%v,%v)", c.in, c.want, c.ok, got, ok)
		}
	}
}

// ⚠「不确定」里既有「不」也有「确定」。先判肯定就会把它当成点头，
// 那是在没有授权的情况下删东西。
func TestDetectYesNoPrefersNo(t *testing.T) {
	cases := []struct {
		in       string
		wantYes  bool
		wantSeen bool
	}{
		{"确认", true, true},
		{"确定", true, true},
		{"好的", true, true},
		{"删", true, true},
		{"不确定", false, true},
		{"不用了", false, true},
		{"算了", false, true},
		{"别删", false, true},
		// 「嗯？」是困惑，不是点头 —— 走"没听懂"，把问题再问一遍
		{"嗯？", false, false},
		{"", false, false},
	}
	for _, c := range cases {
		yes, seen := detectYesNo(c.in)
		if yes != c.wantYes || seen != c.wantSeen {
			t.Fatalf("%q：期望 (yes=%v,seen=%v)，实际 (yes=%v,seen=%v)",
				c.in, c.wantYes, c.wantSeen, yes, seen)
		}
	}
}

// 待确认动作要能原样存进 JSON 列再读回来 —— 读不回来就等于用户点头点了个寂寞。
func TestPendingRoundTrip(t *testing.T) {
	now := time.Date(2026, 9, 8, 10, 30, 0, 0, time.Local)
	freezeNow(t, now)

	p := &pendingAction{
		Kind: "apply_mode", Intent: IntentCancelSchedule, Text: "取消明天早读",
		Slots:     map[string][]string{"task_name": {"早读预备铃"}, "time": {"明天"}},
		Prompt:    pendingPrompt,
		Summary:   "要取消的是明天的早读预备铃",
		CreatedAt: now,
	}
	back := pendingFromMap(p.toMap())
	if back == nil {
		t.Fatal("读不回来")
	}
	if back.Kind != p.Kind || back.Intent != p.Intent || back.Text != p.Text ||
		back.Prompt != p.Prompt || back.Summary != p.Summary {
		t.Fatalf("字段对不上：%+v", back)
	}
	if len(back.Slots["task_name"]) != 1 || back.Slots["task_name"][0] != "早读预备铃" {
		t.Fatalf("槽位对不上：%v", back.Slots)
	}
	if back.expired() {
		t.Fatal("刚建出来就算过期了")
	}

	// 超时之后必须算过期 —— 隔了半天才回一句「就这一次」，
	// 用户多半已经在说别的事了。
	nowFunc = func() time.Time { return now.Add(pendingTTL + time.Second) }
	if !back.expired() {
		t.Fatal("超过 TTL 之后应当算过期")
	}

	// 时间戳读不出来时按过期处理，宁可让用户重说一遍
	broken := pendingFromMap(map[string]interface{}{"kind": "apply_mode", "intent": "cancel_schedule"})
	if !broken.expired() {
		t.Fatal("没有时间戳的待确认应当算过期")
	}
}
