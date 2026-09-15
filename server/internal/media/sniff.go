package media

import (
	"encoding/binary"
	"errors"
	"fmt"
	"io"
	"os"
)

// 上传媒体的格式识别 —— **认文件头，不认扩展名**。
//
// # 为什么不认扩展名
//
// 扩展名是用户随手改的，文件头是编码器写的。一个被改名成 .mp3 的 zip
// 会一路送进 ffmpeg，用户看到的是
//
//	转码失败: exit status 1: [mp3 @ 0x…] Failed to read frame size: Could not seek to 1059…
//
// 谁也看不懂。先认头就能直接说「这不是音频文件」。
//
// 反过来也一样：现场拿到的录音常常是 .m4a 扩展名、里面其实是 ADTS AAC，
// 或者 .aac 扩展名、里面是 MP4 容器。扩展名只用来做第一道粗筛（挡住 .exe
// 之类），真正决定怎么处理的是这里认出来的那个格式。
//
// # 支持的格式
//
//	mp3   MPEG Audio Layer III（原有）
//	wav   RIFF/WAVE（原有）
//	flac  FLAC，2026-09-15 加
//	m4a   MP4/M4A 容器（里面通常是 AAC），2026-09-15 加
//	aac   裸 ADTS / ADIF AAC 流，2026-09-15 加
//
// 全部一律转成 128kbps 立体声 mp3（提示音目录 16000Hz，其余 44100Hz），
// 尾部再拼 2 秒静音 —— 转码这一段对所有格式是同一条路，见 write.go。

// Format 是认出来的音频格式。
type Format string

const (
	FormatMP3  Format = "mp3"
	FormatWAV  Format = "wav"
	FormatFLAC Format = "flac"
	FormatM4A  Format = "m4a"
	FormatAAC  Format = "aac"
)

// ErrUnknownFormat 文件头不属于任何一种支持的格式。
var ErrUnknownFormat = errors.New("无法识别的音频格式")

// uploadExts 是**扩展名**这一层的白名单（第一道粗筛）。
//
// 真正算数的是文件头，但扩展名这一关仍然留着：
// 它挡掉的是「用户拖错了文件」这种常见事故，而且报错能说得具体
// （「只支持 …」而不是「无法识别的音频格式」）。
//
// mp4 也收：现场的录音笔导出常常叫 .mp4 而里面只有一条音轨。
var uploadExts = map[string]bool{
	"mp3": true, "wav": true, "flac": true, "m4a": true, "aac": true, "mp4": true,
}

// sniffHeadBytes 是认头要读多少字节。
//
// 取 64KB 而不是几十字节：ID3v2 标签可以很大（带封面时几百 KB 也有），
// FLAC 和 ADTS 都允许前面挂一段 ID3v2，得跳过它才看得到真正的头。
// 64KB 覆盖绝大多数带封面的文件；更大的标签在下面按标签长度直接 Seek。
const sniffHeadBytes = 64 << 10

// SniffFormat 读文件头判断格式。
//
// 返回的错误只有两种：读文件失败，或 ErrUnknownFormat。
func SniffFormat(path string) (Format, error) {
	f, err := os.Open(path)
	if err != nil {
		return "", err
	}
	defer f.Close()

	head := make([]byte, sniffHeadBytes)
	n, err := io.ReadFull(f, head)
	if err != nil && !errors.Is(err, io.ErrUnexpectedEOF) && !errors.Is(err, io.EOF) {
		return "", err
	}
	head = head[:n]
	if len(head) < 12 {
		return "", ErrUnknownFormat
	}

	// ── 容器类：头在第 0 字节，ID3v2 不会出现在它们前面 ──────────────
	//
	// RIFF/WAVE 与 MP4 都是「长度自描述」的容器，规范里没有给 ID3v2 留位置。
	if string(head[0:4]) == "RIFF" && string(head[8:12]) == "WAVE" {
		return FormatWAV, nil
	}
	if isMP4(head) {
		return FormatM4A, nil
	}

	// ── 流类：前面可能挂着 ID3v2，先跳过 ─────────────────────────────
	body := head
	if skip := id3v2Size(head); skip > 0 {
		if skip < int64(len(head)) {
			body = head[skip:]
		} else {
			// 标签比这 64KB 还长（带大封面）—— 直接 Seek 过去再读一段
			if _, err := f.Seek(skip, io.SeekStart); err != nil {
				return "", ErrUnknownFormat
			}
			buf := make([]byte, 4096)
			m, err := io.ReadFull(f, buf)
			if err != nil && !errors.Is(err, io.ErrUnexpectedEOF) && !errors.Is(err, io.EOF) {
				return "", err
			}
			body = buf[:m]
		}
	}
	if len(body) < 4 {
		return "", ErrUnknownFormat
	}

	if string(body[0:4]) == "fLaC" {
		return FormatFLAC, nil
	}
	if string(body[0:4]) == "ADIF" {
		return FormatAAC, nil
	}
	if isADTS(body) {
		return FormatAAC, nil
	}

	// ⚠ MP3 放在最后：它的同步字和 ADTS 只差 2 个 bit（见 isADTS），
	//   先判 MP3 会把 AAC 误认成 MP3，然后在「转码产物核对」那一步才炸，
	//   报出来的是一句看不懂的帧参数错误。
	if _, err := ReadMP3Info(path); err == nil {
		return FormatMP3, nil
	}
	return "", ErrUnknownFormat
}

// isMP4 判断 MP4/M4A 容器：第 4~8 字节是 'ftyp'。
//
// 不校验 major brand。M4A/M4B/mp42/isom/dash 现场都见过，而且 brand 写得
// 不规范的文件多得是；容器认出来就交给 ffmpeg，里面到底有没有音轨由它说了算
// （没有音轨时 ffmpeg 会明确报 "does not contain any stream"）。
func isMP4(head []byte) bool {
	if len(head) < 12 || string(head[4:8]) != "ftyp" {
		return false
	}
	// box 长度至少要能装下 'ftyp' + major brand
	return binary.BigEndian.Uint32(head[0:4]) >= 8
}

// isADTS 判断裸 AAC 流（ADTS 帧头）。
//
// ADTS 帧头前 2 字节：
//
//	FF        12 位同步字的高 8 位
//	F0 那半个 同步字的低 4 位
//	bit 3     MPEG version（0=MPEG-4, 1=MPEG-2）
//	bit 1~2   layer，**ADTS 里恒为 00**
//	bit 0     protection_absent
//
// ⚠ 与 MP3 的区别只在 layer 那 2 个 bit：MPEG Audio 的 layer 00 是保留值、
// 真实的 MP3 一定是 01/10/11。所以 `head[1] & 0x06 == 0` 才是判据，
// 只看 0xFF 开头会把 MP3 一并收进来。
func isADTS(b []byte) bool {
	if len(b) < 7 {
		return false
	}
	if b[0] != 0xFF || b[1]&0xF0 != 0xF0 {
		return false
	}
	if b[1]&0x06 != 0 { // layer 必须是 00
		return false
	}
	// 采样率索引 15 是保留值，真文件里不会出现；用它挡掉碰巧撞上同步字的垃圾数据
	if (b[2]>>2)&0x0F == 0x0F {
		return false
	}
	// 帧长度（13 位）至少要装得下帧头本身
	frameLen := int(b[3]&0x03)<<11 | int(b[4])<<3 | int(b[5])>>5
	return frameLen >= 7
}

// formatLabel 是给人看的格式名，出现在上传回执的「源格式」里。
func formatLabel(f Format) string {
	switch f {
	case FormatMP3:
		return "MP3"
	case FormatWAV:
		return "WAV"
	case FormatFLAC:
		return "FLAC"
	case FormatM4A:
		return "M4A/MP4 (AAC)"
	case FormatAAC:
		return "AAC (ADTS)"
	}
	return string(f)
}

// badHeaderMessage 把「认头失败」翻成用户看得懂的一句话。
func badHeaderMessage(ext string) string {
	return fmt.Sprintf("不是有效的音频文件：扩展名是 .%s，但文件头不属于 "+
		"mp3 / wav / flac / m4a / aac 中的任何一种（文件可能已损坏，或只是被改了扩展名）", ext)
}
