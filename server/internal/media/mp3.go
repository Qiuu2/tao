package media

import (
	"encoding/binary"
	"errors"
	"fmt"
	"io"
	"os"
)

// MP3 / WAV 文件头识别。
//
// # 为什么要自己认头
//
// 上传的媒体码率、采样率、声道各不相同，最终都要统一转成 128kbps 立体声
// （见 write.go 的转码约定）。转码这件事 ffmpeg 会做，但有两件事它做不好：
//
//  1. **转之前**：只按扩展名判类型的话，一个改名成 .mp3 的文本文件会一路
//     送进 ffmpeg，用户看到的是
//     「转码失败: exit status 1: [mp3 @ 0x…] Failed to read frame size:
//     Could not seek to 1059. Error opening input: Invalid argument」
//     —— 一句谁也看不懂的内部报错。先认头就能直接说「不是有效的 MP3 文件」。
//
//  2. **转之后**：没人核对产物到底是不是 128kbps 立体声。ffmpeg 换个版本、
//     或者滤镜链哪天被改坏，产出一个 64kbps 单声道的文件，照样会入库，
//     等到现场播出来不对劲才发现。转完再认一次头，对不上就当失败。
//
// ok112 也自己认头（inc/get_mp3_info_class.php），但那份实现有几处问题，
// 这里都避开了：
//
//   - 它从文件第 0 字节起找 0xFF，**完全不跳 ID3v2 标签**。带专辑封面的
//     MP3，封面 JPEG 里满是 0xFF，很容易认到假帧头上。
//   - 它只看一帧就下结论，不验证下一帧是否接得上，假同步照样通过。
//   - 码率索引 1111（保留值/坏值）被它映射成了 128 等真实码率，不报错。
//   - VBR 只认 Xing，且时长写死按 1152/44100 算（`1152*1/44100*$framelength`），
//     MPEG2/2.5 每帧 576 样本、采样率也不是 44100，算出来是错的。
//   - CBR 时长用整个文件大小除以码率，ID3 标签和封面也算进了音频时长。
//
// # 帧头格式（4 字节）
//
//	AAAAAAAA AAABBCCD EEEEFFGH IIJJKLMM
//	A(11) 同步字，全 1
//	B(2)  版本   00=MPEG2.5 01=保留 10=MPEG2 11=MPEG1
//	C(2)  层     00=保留 01=LayerIII 10=LayerII 11=LayerI
//	D(1)  校验位（0 表示带 CRC）
//	E(4)  码率索引（0000=自由格式 1111=坏值）
//	F(2)  采样率索引（11=保留）
//	G(1)  填充位
//	H(1)  私有位
//	I(2)  声道模式 00=立体声 01=联合立体声 10=双声道 11=单声道
//	J(2)  模式扩展  K(1) 版权  L(1) 原版  M(2) 强调（11=保留）

// ErrNotMP3 文件里找不到有效的 MP3 帧。
var ErrNotMP3 = errors.New("不是有效的 MP3 文件")

// ErrNotWAV 文件不是 RIFF/WAVE。
var ErrNotWAV = errors.New("不是有效的 WAV 文件")

// MP3Info 是从帧头里读出来的音频参数。
type MP3Info struct {
	// Version 是 MPEG 版本：1 / 2 / 25（表示 2.5）。
	Version int `json:"version"`
	// Layer 是 1 / 2 / 3。
	Layer int `json:"layer"`
	// BitrateKbps 是**首帧**的码率。VBR 时它只是第一帧的值，
	// 真实平均码率见 AvgBitrateKbps。
	BitrateKbps int `json:"bitrateKbps"`
	// AvgBitrateKbps 是整段音频的平均码率，由帧数与时长算出；
	// 拿不到 Xing/VBRI 时按 CBR 等于 BitrateKbps。
	AvgBitrateKbps int `json:"avgBitrateKbps"`
	SampleRate     int `json:"sampleRate"`
	// Channels 是声道数：1 或 2。
	Channels int `json:"channels"`
	// Mode 是声道模式的中文说法，用于给用户看。
	Mode string `json:"mode"`
	// VBR 表示带 Xing/Info/VBRI 头（变码率）。
	VBR bool `json:"vbr"`
	// Seconds 是音频时长（秒）。
	Seconds float64 `json:"seconds"`
	// FrameOffset 是第一个有效帧在文件里的偏移，跳过 ID3v2 之后的位置。
	FrameOffset int64 `json:"-"`
}

// String 给一句人话，用来在界面上显示「从什么转成了什么」。
func (i MP3Info) String() string {
	if i.VBR {
		return fmt.Sprintf("VBR≈%dkbps / %dHz / %s", i.AvgBitrateKbps, i.SampleRate, i.Mode)
	}
	return fmt.Sprintf("%dkbps / %dHz / %s", i.BitrateKbps, i.SampleRate, i.Mode)
}

// 码率表 [层][码率索引]，单位 kbps。索引 0 是自由格式，15 是坏值，都记 0。
var bitrateMPEG1 = [4][16]int{
	3: {0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 384, 416, 448, 0}, // Layer I
	2: {0, 32, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 384, 0},    // Layer II
	1: {0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 0},     // Layer III
}

var bitrateMPEG2 = [4][16]int{
	3: {0, 32, 48, 56, 64, 80, 96, 112, 128, 144, 160, 176, 192, 224, 256, 0}, // Layer I
	2: {0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160, 0},      // Layer II
	1: {0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160, 0},      // Layer III
}

// 采样率表 [版本][索引]。版本下标：3=MPEG1 2=MPEG2 0=MPEG2.5。
var sampleRates = map[int][4]int{
	3: {44100, 48000, 32000, 0},
	2: {22050, 24000, 16000, 0},
	0: {11025, 12000, 8000, 0},
}

var channelModeNames = [4]string{"立体声", "联合立体声", "双声道", "单声道"}

// mp3Frame 是解析出来的一帧。
type mp3Frame struct {
	versionBits int // 3=MPEG1 2=MPEG2 0=MPEG2.5
	layer       int // 1/2/3
	bitrate     int // kbps
	sampleRate  int
	channelMode int
	padding     int
	length      int // 整帧字节数，含头
	samples     int // 每帧样本数
}

// parseFrameHeader 解析 4 字节帧头。不是合法帧头就返回 false。
func parseFrameHeader(b []byte) (mp3Frame, bool) {
	var f mp3Frame
	if len(b) < 4 {
		return f, false
	}
	// 同步字 11 位全 1
	if b[0] != 0xFF || b[1]&0xE0 != 0xE0 {
		return f, false
	}

	f.versionBits = int(b[1]>>3) & 0x03
	if f.versionBits == 1 { // 01 是保留值
		return f, false
	}
	layerBits := int(b[1]>>1) & 0x03
	if layerBits == 0 { // 00 是保留值
		return f, false
	}
	f.layer = 4 - layerBits // 11→1, 10→2, 01→3

	bitrateIdx := int(b[2]>>4) & 0x0F
	if bitrateIdx == 0 || bitrateIdx == 15 { // 自由格式与坏值都不接受
		return f, false
	}
	sampleIdx := int(b[2]>>2) & 0x03
	if sampleIdx == 3 { // 11 是保留值
		return f, false
	}
	f.padding = int(b[2]>>1) & 0x01
	f.channelMode = int(b[3]>>6) & 0x03

	// 强调位 11 是保留值。一并挡掉，能滤掉不少假同步。
	if int(b[3])&0x03 == 2 {
		return f, false
	}

	table := bitrateMPEG2
	if f.versionBits == 3 {
		table = bitrateMPEG1
	}
	f.bitrate = table[layerBits][bitrateIdx]
	if f.bitrate == 0 {
		return f, false
	}
	rates, ok := sampleRates[f.versionBits]
	if !ok {
		return f, false
	}
	f.sampleRate = rates[sampleIdx]
	if f.sampleRate == 0 {
		return f, false
	}

	// 每帧样本数：Layer I 恒 384；Layer II 恒 1152；
	// Layer III 在 MPEG1 下 1152，MPEG2/2.5 下 576。
	switch {
	case f.layer == 1:
		f.samples = 384
	case f.layer == 2:
		f.samples = 1152
	case f.versionBits == 3:
		f.samples = 1152
	default:
		f.samples = 576
	}

	// 帧长。Layer I 以 4 字节为槽，其余按字节。
	if f.layer == 1 {
		f.length = (12*f.bitrate*1000/f.sampleRate + f.padding) * 4
	} else {
		f.length = f.samples/8*f.bitrate*1000/f.sampleRate + f.padding
	}
	if f.length < 4 {
		return f, false
	}
	return f, true
}

// id3v2Size 读 ID3v2 标签长度。没有标签返回 0。
//
// 标签头 10 字节：'I','D','3', 主版本, 修订号, 标志, 4 字节 syncsafe 长度
// （每字节只用低 7 位）。标志位 0x10 表示尾部还有 10 字节 footer。
func id3v2Size(head []byte) int64 {
	if len(head) < 10 || head[0] != 'I' || head[1] != 'D' || head[2] != '3' {
		return 0
	}
	// syncsafe：4 个字节各取低 7 位拼成 28 位
	for i := 6; i < 10; i++ {
		if head[i]&0x80 != 0 {
			return 0 // 不是合法的 syncsafe，当作没有标签
		}
	}
	size := int64(head[6])<<21 | int64(head[7])<<14 | int64(head[8])<<7 | int64(head[9])
	total := size + 10
	if head[5]&0x10 != 0 {
		total += 10
	}
	return total
}

// mp3ScanBytes 是找首帧时最多往后扫多少字节。
//
// 正常文件跳过 ID3v2 就正对着首帧。留这么大的余量是为了兼容两种情况：
// ID3v2 长度字段被写坏，以及标签和音频之间垫了一段 0。
const mp3ScanBytes = 1 << 20 // 1MB

// mp3MinConsecutiveFrames 是认定「找到真帧」需要连续对上的帧数。
//
// 只看一帧太容易被封面里的 0xFF 骗到 —— 随便一段 JPEG 数据都有可能
// 凑出一个看着合法的帧头。要求后面接着的两帧也对得上，误判概率就低到可以忽略。
const mp3MinConsecutiveFrames = 3

// ReadMP3Info 认 MP3 头，读出码率、采样率、声道和时长。
func ReadMP3Info(path string) (MP3Info, error) {
	f, err := os.Open(path)
	if err != nil {
		return MP3Info{}, err
	}
	defer f.Close()

	st, err := f.Stat()
	if err != nil {
		return MP3Info{}, err
	}
	fileSize := st.Size()

	head := make([]byte, 10)
	n, _ := io.ReadFull(f, head)
	start := int64(0)
	if n == 10 {
		start = id3v2Size(head)
	}
	if start >= fileSize {
		return MP3Info{}, ErrNotMP3
	}

	// 从 ID3v2 之后开始扫，找连续能对上的若干帧
	scanLen := fileSize - start
	if scanLen > mp3ScanBytes {
		scanLen = mp3ScanBytes
	}
	buf := make([]byte, scanLen)
	if _, err := f.ReadAt(buf, start); err != nil && !errors.Is(err, io.EOF) {
		return MP3Info{}, err
	}

	first, offset, ok := findFirstFrame(buf)
	if !ok {
		return MP3Info{}, ErrNotMP3
	}
	frameStart := start + int64(offset)

	info := MP3Info{
		Layer:          first.layer,
		BitrateKbps:    first.bitrate,
		AvgBitrateKbps: first.bitrate,
		SampleRate:     first.sampleRate,
		Channels:       2,
		Mode:           channelModeNames[first.channelMode],
		FrameOffset:    frameStart,
	}
	switch first.versionBits {
	case 3:
		info.Version = 1
	case 2:
		info.Version = 2
	default:
		info.Version = 25
	}
	if first.channelMode == 3 { // 11 = 单声道
		info.Channels = 1
	}

	// Xing/Info 在首帧的边信息之后，VBRI 固定在首帧偏移 36 处
	frameBuf := buf[offset:]
	frames, hasHeader, isVBR := vbrFrameCount(frameBuf, first)
	info.VBR = isVBR
	if hasHeader && frames > 0 && first.sampleRate > 0 {
		info.Seconds = float64(frames) * float64(first.samples) / float64(first.sampleRate)
	}

	// 音频数据长度：整文件减掉 ID3v2，再减掉尾部的 ID3v1（128 字节，'TAG' 开头）
	audioBytes := fileSize - frameStart
	if fileSize >= 128 {
		tail := make([]byte, 3)
		if _, err := f.ReadAt(tail, fileSize-128); err == nil &&
			tail[0] == 'T' && tail[1] == 'A' && tail[2] == 'G' {
			audioBytes -= 128
		}
	}
	if audioBytes < 0 {
		audioBytes = 0
	}

	if info.Seconds == 0 && info.BitrateKbps > 0 {
		// CBR：按首帧码率算
		info.Seconds = float64(audioBytes) * 8 / float64(info.BitrateKbps*1000)
	}
	if info.Seconds > 0 {
		info.AvgBitrateKbps = int(float64(audioBytes) * 8 / info.Seconds / 1000)
	}
	return info, nil
}

// findFirstFrame 在 buf 里找第一个「后面还能连着对上」的帧。
func findFirstFrame(buf []byte) (mp3Frame, int, bool) {
	for i := 0; i+4 <= len(buf); i++ {
		if buf[i] != 0xFF {
			continue
		}
		first, ok := parseFrameHeader(buf[i:])
		if !ok {
			continue
		}
		// 顺着帧长往后走，看后续几帧是不是也对得上
		pos, matched := i, 1
		cur := first
		for matched < mp3MinConsecutiveFrames {
			next := pos + cur.length
			if next+4 > len(buf) {
				// 文件到头了。已经连上过一帧以上就认，否则当假同步。
				// （很短的提示音只有两三帧，不能因为凑不满就拒收。）
				if matched >= 2 {
					return first, i, true
				}
				break
			}
			nf, ok := parseFrameHeader(buf[next:])
			// 同一个文件里版本 / 层 / 采样率不该变，变了说明认错了
			if !ok || nf.versionBits != cur.versionBits ||
				nf.layer != cur.layer || nf.sampleRate != cur.sampleRate {
				break
			}
			pos, cur, matched = next, nf, matched+1
		}
		if matched >= mp3MinConsecutiveFrames {
			return first, i, true
		}
	}
	return mp3Frame{}, 0, false
}

// vbrFrameCount 从 Xing / Info / VBRI 头里读总帧数，并判断是不是变码率。
//
// Xing/Info 紧跟在首帧的边信息之后，边信息长度看版本和声道：
//
//	MPEG1    单声道 17，其余 32
//	MPEG2/2.5 单声道 9，其余 17
//
// VBRI 是 Fraunhofer 的写法，固定在首帧起始偏移 36 处。
//
// ⚠ 「Xing」才是变码率，「Info」是同一个结构的**定码率**版本 —— LAME 编 CBR
//
//	时也会写这个头。两个都当 VBR 的话，一个规规矩矩的 128kbps 定码率文件会被
//	报成「VBR≈128kbps」。帧数两种都读（比按文件大小估的时长准），
//	但只有 Xing 置 VBR。
func vbrFrameCount(frame []byte, f mp3Frame) (frames int, hasHeader, vbr bool) {
	sideInfo := 32
	switch {
	case f.versionBits == 3 && f.channelMode == 3:
		sideInfo = 17
	case f.versionBits != 3 && f.channelMode == 3:
		sideInfo = 9
	case f.versionBits != 3:
		sideInfo = 17
	}

	if off := 4 + sideInfo; off+12 <= len(frame) {
		tag := string(frame[off : off+4])
		if tag == "Xing" || tag == "Info" {
			n := 0
			flags := binary.BigEndian.Uint32(frame[off+4 : off+8])
			if flags&0x01 != 0 { // 带帧数字段
				n = int(binary.BigEndian.Uint32(frame[off+8 : off+12]))
			}
			return n, true, tag == "Xing"
		}
	}
	if len(frame) >= 36+26 && string(frame[36:40]) == "VBRI" {
		return int(binary.BigEndian.Uint32(frame[36+14 : 36+18])), true, true
	}
	return 0, false, false
}

// IsWAV 确认文件是 RIFF/WAVE。
//
// 只认容器，不解析 fmt 块 —— WAV 一律要过 ffmpeg 转成 mp3，
// 具体是多少位深、什么采样率，转完就都不重要了。
func IsWAV(path string) error {
	f, err := os.Open(path)
	if err != nil {
		return err
	}
	defer f.Close()

	head := make([]byte, 12)
	if _, err := io.ReadFull(f, head); err != nil {
		return ErrNotWAV
	}
	if string(head[0:4]) != "RIFF" || string(head[8:12]) != "WAVE" {
		return ErrNotWAV
	}
	return nil
}
