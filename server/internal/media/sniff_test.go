package media

import (
	"os"
	"os/exec"
	"path/filepath"
	"testing"
)

// 这一组用 **ffmpeg 真的生成出来的文件** 来测，不手搓文件头。
//
// 手搓的头只能证明「我写的判据认得我自己写的头」，证明不了「它认得现场那些
// 编码器写出来的头」—— 而后者才是这段代码存在的理由。ffmpeg 不在就 skip。
func ffmpegPath(t *testing.T) string {
	t.Helper()
	for _, p := range []string{"/usr/bin/ffmpeg", "/usr/local/bin/ffmpeg", "ffmpeg"} {
		if found, err := exec.LookPath(p); err == nil {
			return found
		}
	}
	t.Skip("没有 ffmpeg，跳过")
	return ""
}

// gen 用 ffmpeg 造一个 1 秒的正弦波，编码成指定格式。
func gen(t *testing.T, ff, path string, extraArgs ...string) {
	t.Helper()
	args := []string{
		"-hide_banner", "-loglevel", "error",
		"-f", "lavfi", "-i", "sine=frequency=440:duration=1",
	}
	args = append(args, extraArgs...)
	args = append(args, "-y", path)
	out, err := exec.Command(ff, args...).CombinedOutput()
	if err != nil {
		t.Skipf("这套 ffmpeg 造不出 %s（少了编码器？）：%v %s", filepath.Base(path), err, out)
	}
}

func TestSniffRecognisesEverySupportedFormat(t *testing.T) {
	ff := ffmpegPath(t)
	dir := t.TempDir()

	cases := []struct {
		name string
		file string
		args []string
		want Format
	}{
		{"mp3", "a.mp3", []string{"-b:a", "128k"}, FormatMP3},
		{"wav", "a.wav", nil, FormatWAV},
		{"flac", "a.flac", nil, FormatFLAC},
		{"m4a（MP4 容器里的 AAC）", "a.m4a", []string{"-c:a", "aac"}, FormatM4A},
		{"裸 ADTS aac", "a.aac", []string{"-c:a", "aac", "-f", "adts"}, FormatAAC},
	}
	for _, c := range cases {
		t.Run(c.name, func(t *testing.T) {
			p := filepath.Join(dir, c.file)
			gen(t, ff, p, c.args...)
			got, err := SniffFormat(p)
			if err != nil {
				t.Fatalf("认不出来：%v", err)
			}
			if got != c.want {
				t.Errorf("认成了 %s，应该是 %s", got, c.want)
			}
		})
	}
}

// ⚠ 这一条盯的是最容易出的那个错：ADTS 的同步字和 MP3 只差 layer 那 2 个 bit。
// 先判 MP3 会把 AAC 认成 MP3，然后要等到「转码产物核对」那一步才炸，
// 报出来的是一句看不懂的帧参数错误。
func TestAdtsIsNotMistakenForMP3(t *testing.T) {
	ff := ffmpegPath(t)
	p := filepath.Join(t.TempDir(), "a.aac")
	gen(t, ff, p, "-c:a", "aac", "-f", "adts")

	if got, _ := SniffFormat(p); got != FormatAAC {
		t.Errorf("裸 AAC 被认成了 %s", got)
	}
	// 反过来：MP3 不能被 isADTS 收走
	raw, err := os.ReadFile(p)
	if err != nil {
		t.Fatal(err)
	}
	if !isADTS(raw) {
		t.Error("isADTS 认不出 ffmpeg 写的 ADTS 帧头")
	}
	mp3 := filepath.Join(t.TempDir(), "a.mp3")
	gen(t, ff, mp3, "-b:a", "128k")
	mraw, err := os.ReadFile(mp3)
	if err != nil {
		t.Fatal(err)
	}
	if isADTS(mraw) {
		t.Error("isADTS 把 MP3 也收进去了 —— layer 那 2 个 bit 没判")
	}
}

// 带 ID3v2 标签的 FLAC / AAC 也要认得出来：标签可以挂在流前面，
// 不跳过它就只看得到 'ID3'。
func TestSniffSkipsID3v2Prefix(t *testing.T) {
	ff := ffmpegPath(t)
	dir := t.TempDir()
	src := filepath.Join(dir, "plain.flac")
	gen(t, ff, src)

	raw, err := os.ReadFile(src)
	if err != nil {
		t.Fatal(err)
	}
	// 手工拼一个 2048 字节的 ID3v2.3 标签（头 10 字节 + 填充）
	const pad = 2048
	tag := make([]byte, 10+pad)
	copy(tag, "ID3")
	tag[3], tag[4] = 3, 0
	// syncsafe 长度：每字节只用低 7 位
	tag[6] = byte(pad >> 21 & 0x7F)
	tag[7] = byte(pad >> 14 & 0x7F)
	tag[8] = byte(pad >> 7 & 0x7F)
	tag[9] = byte(pad & 0x7F)

	withTag := filepath.Join(dir, "tagged.flac")
	if err := os.WriteFile(withTag, append(tag, raw...), 0o644); err != nil {
		t.Fatal(err)
	}
	got, err := SniffFormat(withTag)
	if err != nil {
		t.Fatalf("带 ID3 的 FLAC 认不出来：%v", err)
	}
	if got != FormatFLAC {
		t.Errorf("认成了 %s，应该是 flac", got)
	}
}

// 不是音频的东西必须被挡在 ffmpeg 之前，报一句人话。
func TestSniffRejectsNonAudio(t *testing.T) {
	dir := t.TempDir()
	p := filepath.Join(dir, "fake.mp3")
	// 一段纯文本，长度足够过掉「太短」那一关
	body := make([]byte, 4096)
	for i := range body {
		body[i] = 'A'
	}
	if err := os.WriteFile(p, body, 0o644); err != nil {
		t.Fatal(err)
	}
	if _, err := SniffFormat(p); err == nil {
		t.Error("改名成 .mp3 的文本文件被当成了音频")
	}
}

// 扩展名白名单是第一道粗筛，五种格式加 mp4 都要在里面。
func TestUploadExtsCoversEverySupportedFormat(t *testing.T) {
	for _, e := range []string{"mp3", "wav", "flac", "m4a", "aac", "mp4"} {
		if !uploadExts[e] {
			t.Errorf(".%s 不在扩展名白名单里，界面上选得到、传上来会被挡", e)
		}
	}
	if uploadExts["exe"] {
		t.Error("白名单太宽了")
	}
}
