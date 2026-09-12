package captcha

import (
	"testing"
	"time"
)

// 滑动凭据：领一张、用一次就作废。
func TestSliderTicketIsOneShot(t *testing.T) {
	s := NewStore(time.Minute)
	id := s.GenerateSlider()
	if id == "" {
		t.Fatal("没发出凭据")
	}
	if !s.VerifyTicket(id) {
		t.Fatal("刚发的凭据该认")
	}
	if s.VerifyTicket(id) {
		t.Error("凭据必须是一次性的，第二次不能再认（否则抓一次包就能重放）")
	}
}

func TestSliderTicketRejectsGarbage(t *testing.T) {
	s := NewStore(time.Minute)
	if s.VerifyTicket("") {
		t.Error("空 id 不能认")
	}
	if s.VerifyTicket("随手编的") {
		t.Error("不是本机发的 id 不能认")
	}
}

func TestSliderTicketExpires(t *testing.T) {
	s := NewStore(time.Millisecond)
	id := s.GenerateSlider()
	time.Sleep(20 * time.Millisecond)
	if s.VerifyTicket(id) {
		t.Error("过期的凭据不能认")
	}
}

// ⚠ 这一条是这个文件里最要紧的：
//
// 滑动凭据没有 code。拿它去走图形验证码那条路（Verify）时，如果不特判，
// 「输入空串」就会和「存的空 code」相等 —— 等于把验证整个绕过去了。
// 现在 Verify 先被 input=="" 挡一道，再被 e.code=="" 挡一道，两道都得在。
func TestSliderTicketCannotPassImageVerify(t *testing.T) {
	s := NewStore(time.Minute)
	for _, in := range []string{"", " ", "0000"} {
		id := s.GenerateSlider()
		if s.Verify(id, in) {
			t.Errorf("滑动凭据不该能通过图形校验（输入 %q）", in)
		}
	}
}

// 反过来：图形验证码的 id 走滑动那条路会被认。
//
// 这不是漏洞而是设计 —— VerifyTicket 本来就只问「是不是本机刚发的、没用过」。
// 但两种模式同一时刻只有一种在用（auth.captcha_mode），钉一下免得以后有人
// 把两条路混在一起用还以为强度不变。
func TestImageIDAlsoPassesTicketCheck(t *testing.T) {
	s := NewStore(time.Minute)
	id, _, err := s.Generate()
	if err != nil {
		t.Fatal(err)
	}
	if !s.VerifyTicket(id) {
		t.Error("VerifyTicket 只看新鲜度与一次性，图形 id 也该认")
	}
}
