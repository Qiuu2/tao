package media

import (
	"os"
	"path/filepath"
	"testing"
)

// 手工拼一个帧头，方便断言各字段的解析。
//
//	AAAAAAAA AAABBCCD EEEEFFGH IIJJKLMM
func frameHeader(version, layer, bitrateIdx, sampleIdx, padding, channelMode int) []byte {
	b := make([]byte, 4)
	b[0] = 0xFF
	b[1] = 0xE0 | byte(version<<3) | byte(layer<<1) | 1 // 末位 1 = 不带 CRC
	b[2] = byte(bitrateIdx<<4) | byte(sampleIdx<<2) | byte(padding<<1)
	b[3] = byte(channelMode << 6)
	return b
}

func TestParseFrameHeader(t *testing.T) {
	cases := []struct {
		name                             string
		version, layer, brIdx, srIdx, ch int
		wantBitrate, wantRate, wantLen   int
		wantSamples                      int
	}{
		// MPEG1 Layer III 128kbps 44100 立体声 → 144*128000/44100 = 417
		{"MPEG1 L3 128k 44.1k 立体声", 3, 1, 9, 0, 0, 128, 44100, 417, 1152},
		// MPEG1 Layer III 320kbps 44100 → 144*320000/44100 = 1044
		{"MPEG1 L3 320k 44.1k", 3, 1, 14, 0, 0, 320, 44100, 1044, 1152},
		// MPEG2 Layer III 64kbps 22050 单声道 → 72*64000/22050 = 208
		{"MPEG2 L3 64k 22.05k 单声道", 2, 1, 8, 0, 3, 64, 22050, 208, 576},
		// MPEG2.5 Layer III 32kbps 8000 单声道 → 72*32000/8000 = 288
		{"MPEG2.5 L3 32k 8k 单声道", 0, 1, 4, 2, 3, 32, 8000, 288, 576},
		// MPEG1 Layer I 32kbps 44100 → (12*32000/44100)*4 = 32
		{"MPEG1 L1 32k 44.1k", 3, 3, 1, 0, 0, 32, 44100, 32, 384},
		// MPEG1 Layer II 128kbps 48000 → 144*128000/48000 = 384
		{"MPEG1 L2 128k 48k", 3, 2, 8, 1, 0, 128, 48000, 384, 1152},
	}
	for _, c := range cases {
		t.Run(c.name, func(t *testing.T) {
			f, ok := parseFrameHeader(frameHeader(c.version, c.layer, c.brIdx, c.srIdx, 0, c.ch))
			if !ok {
				t.Fatal("应当解析成功")
			}
			if f.bitrate != c.wantBitrate {
				t.Errorf("码率 = %d，应为 %d", f.bitrate, c.wantBitrate)
			}
			if f.sampleRate != c.wantRate {
				t.Errorf("采样率 = %d，应为 %d", f.sampleRate, c.wantRate)
			}
			if f.length != c.wantLen {
				t.Errorf("帧长 = %d，应为 %d", f.length, c.wantLen)
			}
			if f.samples != c.wantSamples {
				t.Errorf("每帧样本数 = %d，应为 %d", f.samples, c.wantSamples)
			}
		})
	}
}

func TestParseFrameHeaderRejects(t *testing.T) {
	cases := []struct {
		name string
		b    []byte
	}{
		{"同步字不全", []byte{0xFF, 0xC0, 0x00, 0x00}},
		{"首字节不是 FF", []byte{0xFE, 0xFB, 0x90, 0x00}},
		{"版本保留值 01", frameHeader(1, 1, 9, 0, 0, 0)},
		{"层保留值 00", frameHeader(3, 0, 9, 0, 0, 0)},
		{"码率索引 0（自由格式）", frameHeader(3, 1, 0, 0, 0, 0)},
		{"码率索引 15（坏值）", frameHeader(3, 1, 15, 0, 0, 0)},
		{"采样率索引 3（保留）", frameHeader(3, 1, 9, 3, 0, 0)},
		{"长度不足", []byte{0xFF, 0xFB}},
	}
	for _, c := range cases {
		t.Run(c.name, func(t *testing.T) {
			if _, ok := parseFrameHeader(c.b); ok {
				t.Error("应当被拒绝")
			}
		})
	}
}

// ⚠ ok112 的解析器把码率索引 1111 映射成了 128kbps 等真实码率
// （get_mp3_info_class.php 里那张表的 "1111" 一行），坏帧会被当成好帧。
func TestBadBitrateIndexNotMappedTo128(t *testing.T) {
	if f, ok := parseFrameHeader(frameHeader(3, 1, 15, 0, 0, 0)); ok {
		t.Fatalf("码率索引 1111 是坏值，不该解析成 %dkbps", f.bitrate)
	}
}

func TestID3v2Size(t *testing.T) {
	// syncsafe 长度 0x00 0x00 0x02 0x01 = 2*128 + 1 = 257，加 10 字节头
	head := []byte{'I', 'D', '3', 3, 0, 0, 0x00, 0x00, 0x02, 0x01}
	if got := id3v2Size(head); got != 267 {
		t.Errorf("= %d，应为 267", got)
	}
	// 带 footer 标志再多 10 字节
	head[5] = 0x10
	if got := id3v2Size(head); got != 277 {
		t.Errorf("带 footer 时 = %d，应为 277", got)
	}
	// 不是 ID3 就是 0
	if got := id3v2Size([]byte{'R', 'I', 'F', 'F', 0, 0, 0, 0, 0, 0}); got != 0 {
		t.Errorf("非 ID3 = %d，应为 0", got)
	}
	// 长度字节带最高位不是合法 syncsafe
	if got := id3v2Size([]byte{'I', 'D', '3', 3, 0, 0, 0x80, 0, 0, 0}); got != 0 {
		t.Errorf("非法 syncsafe = %d，应为 0", got)
	}
}

// 拼一个 n 帧的 MPEG1 Layer III 128kbps/44100 立体声文件（帧体填 0）。
func synthMP3(t *testing.T, dir string, n int, prefix []byte) string {
	t.Helper()
	hdr := frameHeader(3, 1, 9, 0, 0, 0)
	f, _ := parseFrameHeader(hdr)
	var out []byte
	out = append(out, prefix...)
	for i := 0; i < n; i++ {
		out = append(out, hdr...)
		out = append(out, make([]byte, f.length-4)...)
	}
	p := filepath.Join(dir, "synth.mp3")
	if err := os.WriteFile(p, out, 0o600); err != nil {
		t.Fatal(err)
	}
	return p
}

func TestReadMP3Info(t *testing.T) {
	dir := t.TempDir()
	p := synthMP3(t, dir, 40, nil)
	info, err := ReadMP3Info(p)
	if err != nil {
		t.Fatal(err)
	}
	if info.Version != 1 || info.Layer != 3 {
		t.Errorf("版本/层 = MPEG%d Layer%d，应为 MPEG1 Layer3", info.Version, info.Layer)
	}
	if info.BitrateKbps != 128 || info.SampleRate != 44100 || info.Channels != 2 {
		t.Errorf("= %dkbps/%dHz/%d声道，应为 128/44100/2",
			info.BitrateKbps, info.SampleRate, info.Channels)
	}
	if info.VBR {
		t.Error("没有 Xing 头，不该判成 VBR")
	}
	// 40 帧 × 1152 样本 / 44100 ≈ 1.045 秒；按字节算会略有出入，给一点余量
	if info.Seconds < 0.9 || info.Seconds > 1.2 {
		t.Errorf("时长 = %.3fs，应在 1 秒上下", info.Seconds)
	}
}

// ID3v2 标签里塞满 0xFF（专辑封面就是这样），解析器必须跳过标签，
// 不能在标签里认出假帧头 —— ok112 从第 0 字节起扫，正会栽在这里。
func TestReadMP3InfoSkipsID3WithFalseSync(t *testing.T) {
	dir := t.TempDir()
	payload := make([]byte, 600)
	for i := range payload {
		payload[i] = 0xFF // 整段假同步
	}
	tag := []byte{'I', 'D', '3', 3, 0, 0, 0x00, 0x00, 0x04, 0x58} // 600 字节
	prefix := append(tag, payload...)

	p := synthMP3(t, dir, 20, prefix)
	info, err := ReadMP3Info(p)
	if err != nil {
		t.Fatalf("应当跳过 ID3v2 找到真帧: %v", err)
	}
	if info.FrameOffset != int64(len(prefix)) {
		t.Errorf("首帧偏移 = %d，应为 %d（ID3 标签之后）", info.FrameOffset, len(prefix))
	}
	if info.BitrateKbps != 128 || info.SampleRate != 44100 {
		t.Errorf("= %dkbps/%dHz，应为 128/44100", info.BitrateKbps, info.SampleRate)
	}
}

func TestReadMP3InfoRejectsNonMP3(t *testing.T) {
	dir := t.TempDir()
	cases := map[string][]byte{
		"纯文本":    []byte("this is not audio at all, just some text"),
		"空文件":    {},
		"只有 ID3": append([]byte{'I', 'D', '3', 3, 0, 0, 0, 0, 2, 0}, make([]byte, 300)...),
		"孤立的假同步": {0xFF, 0xFB, 0x90, 0x00, 0x01, 0x02},
	}
	for name, body := range cases {
		t.Run(name, func(t *testing.T) {
			p := filepath.Join(dir, "x.mp3")
			if err := os.WriteFile(p, body, 0o600); err != nil {
				t.Fatal(err)
			}
			if _, err := ReadMP3Info(p); err == nil {
				t.Error("应当被判为非 MP3")
			}
		})
	}
}

func TestIsWAV(t *testing.T) {
	dir := t.TempDir()
	good := filepath.Join(dir, "a.wav")
	body := append([]byte("RIFF"), 0, 0, 0, 0)
	body = append(body, []byte("WAVEfmt ")...)
	if err := os.WriteFile(good, body, 0o600); err != nil {
		t.Fatal(err)
	}
	if err := IsWAV(good); err != nil {
		t.Errorf("应当认出 WAV: %v", err)
	}

	bad := filepath.Join(dir, "b.wav")
	if err := os.WriteFile(bad, []byte("not a wav file at all"), 0o600); err != nil {
		t.Fatal(err)
	}
	if err := IsWAV(bad); err == nil {
		t.Error("应当被判为非 WAV")
	}
}
