package typedtask

import (
	"context"
	"database/sql"
	"fmt"
	"strings"
)

// 文字语音（TTS）。
//
// # 三张表怎么串起来
//
//	task        tasktype IN (15,17,19)
//	  └ mediaoftask (taskid, mediaid)         ← 任务指向一条「媒体」
//	      └ media   (id, typeid='tts')        ← 这条媒体是**合成出来的**，不是上传的文件
//	          └ ttssentence (sentenceid = media.id, mediaseq)  ← 真正要念的句子，可以多条
//
// 也就是说：一条 TTS 任务对应一条 typeid='tts' 的虚拟媒体，
// 这条媒体下面挂若干条 `ttssentence`，每条一句话，按 `mediaseq` 排序。
// 后台 TTS 服务（a9000_tts 容器）读 ttssentence 去合成语音。
//
// 关联键是 `ttssentence.sentenceid` = `media.id` —— 列名叫 sentenceid，
// 存的却是 mediaid。旧版 `DELETE FROM ttssentence WHERE sentenceid IN
// (SELECT mediaid FROM mediaoftask WHERE taskid=?)` 就是这么用的。
// 又一个「列名与内容对不上」的地方（和遥控任务的 mediaid 存 taskid 一模一样）。
//
// # ⚠ media 行是合成的，filename 写死 'tts'
//
// 现网那两条 TTS 媒体 `filename='tts'`、`typeid='tts'`，磁盘上**没有对应文件**。
// 所以媒体模块的「物理文件缺失」检查要放过它们，删除任务时也要连带删掉这条 media
// （见 typedtask.Delete 里那句限定 `typeid='tts'` 的 DELETE）——
// 不删就会在文件管理里留下一条永远找不到文件的孤儿记录。

type Sentence struct {
	ID   int64  `json:"id"`
	Name string `json:"name"`
	// MediaID 只在 type=0（提示音）的那一条上有意义，指向一条真实媒体。
	MediaID  int64  `json:"mediaId"`
	Content  string `json:"content"`
	Seq      int    `json:"mediaseq"`
	Speed    int    `json:"speed"`
	Volume   int    `json:"volume"`
	Male     int    `json:"male"`
	Pitch    int    `json:"pitch"`
	Type     int    `json:"type"`
	TypeText string `json:"typeText"`
}

// ttssentence.type 的取值来自列注释：0:音乐，1:约定文字，2:输入文字。
//
//	type = 2  正文，content 里是要念的文字（旧版的 textarea）
//	type = 0  提示音，content 为空、mediaid 指向一条真实媒体
//	          （旧版只有在「tts终端」选到服务器本机时才出现这一项）
const (
	sentenceTypeMusic = 0
	sentenceTypeInput = 2
)

func sentenceTypeText(v int) string {
	switch v {
	case 0:
		return "音乐"
	case 1:
		return "约定文字"
	case 2:
		return "输入文字"
	}
	return fmt.Sprintf("类型 %d", v)
}

const (
	// ttssentence.content 是 varchar(1400)
	contentLimit = 1400
	// 整个 textarea 的上限：**800 个字**（按字符数算，不是字节）。
	// 旧版没有校验，这里按界面上的 maxlength 定一个明确的边界，两边一致。
	textRuneLimit = 800
	maxSentences  = 20
	// speed / volume 的列注释写的是 -50~100 / -100~100，
	// 旧版表单是 0~100 的滑块（默认 50），现网数据是 5、50（speed）与 80（volume）。
	// 这里按列注释的**并集**放宽，不比旧版更严 —— 旧版一个字都不校验。
	speedMin, speedMax   = -50, 100
	volumeMin, volumeMax = -100, 100
	// musicmode：旧版下拉的四个取值
	musicModeMax = 3
)

// splitTTSText 复刻旧版 str_split_utf8()：把一整段文字切成若干条 ttssentence。
//
// 旧版的规则是「按字节累计，超过 500 字节后遇到标点就断，超过 600 字节直接断」，
// 并且**不在 UTF-8 字符中间断开**。这里用 rune 遍历实现同一套语义 ——
// 一段短文字（现场绝大多数情况）只会切出一条，与旧版结果完全一致。
func splitTTSText(text string) []string {
	const (
		softLimit = 500
		hardLimit = 600
	)
	out := []string{}
	var buf strings.Builder
	flush := func() {
		if c := strings.TrimSpace(buf.String()); c != "" {
			out = append(out, c)
		}
		buf.Reset()
	}
	for _, r := range text {
		buf.WriteRune(r)
		n := buf.Len()
		if n >= hardLimit {
			flush()
			continue
		}
		if n >= softLimit && isBreakRune(r) {
			flush()
		}
	}
	flush()
	return out
}

// isBreakRune 是旧版那串 ord() 判断对应的标点集合
// （33 '!'、44 ','、46 '.'、59 ';'、63 '?'，以及 227/239/250 打头的中日韩标点）。
func isBreakRune(r rune) bool {
	switch r {
	case '!', ',', '.', ';', '?':
		return true
	case '。', '，', '！', '？', '；', '、', '：':
		return true
	}
	return false
}

// normalizeTTSText 复刻旧版落库前的那串 str_replace：
// 去掉 <br/>、换行、顿号和残留的 </b>，反斜杠也一并去掉。
func normalizeTTSText(text string) string {
	rep := strings.NewReplacer(
		"<br/>", "", "<br />", "", "<BR/>", "",
		"\r\n", "", "\r", "", "\n", "",
		"、", "", "</b>", "", "</B>", "", "\\", "",
	)
	return rep.Replace(text)
}

// validateTTS 校验「一个 textarea + 一组全局参数」这套输入。
func validateTTS(in *Input) error {
	in.Text = normalizeTTSText(in.Text)
	if strings.TrimSpace(in.Text) == "" {
		return fmt.Errorf("请输入文字语音内容")
	}
	if n := len([]rune(in.Text)); n > textRuneLimit {
		return fmt.Errorf("文字语音内容过长：%d 个字，上限 %d 个字", n, textRuneLimit)
	}
	segs := splitTTSText(in.Text)
	if len(segs) == 0 {
		return fmt.Errorf("请输入文字语音内容")
	}
	if len(segs) > maxSentences {
		return fmt.Errorf("文字语音内容切分后有 %d 段，超过 %d 段的上限", len(segs), maxSentences)
	}
	for i, c := range segs {
		if len(c) > contentLimit {
			return fmt.Errorf("第 %d 段文字过长：按 UTF-8 计 %d 字节，上限 %d 字节", i+1, len(c), contentLimit)
		}
	}
	if in.TTSSpeed < speedMin || in.TTSSpeed > speedMax {
		return fmt.Errorf("播放速率必须在 %d ~ %d 之间", speedMin, speedMax)
	}
	if in.Volume < volumeMin || in.Volume > volumeMax {
		return fmt.Errorf("任务音量必须在 %d ~ %d 之间", volumeMin, volumeMax)
	}
	if in.MusicMode < 0 || in.MusicMode > musicModeMax {
		return fmt.Errorf("声音模式只能是 0（女声）/ 1（男声）/ 2（英语男声）/ 3（英语女声）")
	}
	return nil
}

func (s *Service) taskSentences(ctx context.Context, taskID int64) ([]Sentence, error) {
	rs, err := s.db.QueryContext(ctx, `
		SELECT ts.id, COALESCE(ts.name,''), COALESCE(ts.mediaid,0), COALESCE(ts.content,''),
		       COALESCE(ts.mediaseq,0), COALESCE(ts.speed,0), COALESCE(ts.volume,0),
		       COALESCE(ts.male,0), COALESCE(ts.pitch,50), COALESCE(ts.type,2)
		FROM ttssentence ts
		WHERE ts.sentenceid IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)
		ORDER BY ts.mediaseq, ts.id`, taskID)
	if err != nil {
		return nil, fmt.Errorf("查询播报文字: %w", err)
	}
	defer rs.Close()
	out := []Sentence{}
	for rs.Next() {
		var s Sentence
		if err := rs.Scan(&s.ID, &s.Name, &s.MediaID, &s.Content, &s.Seq,
			&s.Speed, &s.Volume, &s.Male, &s.Pitch, &s.Type); err != nil {
			return nil, err
		}
		s.TypeText = sentenceTypeText(s.Type)
		out = append(out, s)
	}
	return out, rs.Err()
}

// fillTTSText 给列表补一列「要播的文字」，取第一段拼个摘要。
func (s *Service) fillTTSText(ctx context.Context, items []Item, ids []int64) error {
	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx, `
		SELECT mt.taskid, COALESCE(ts.content,'')
		FROM mediaoftask mt
		JOIN ttssentence ts ON ts.sentenceid = mt.mediaid
		WHERE mt.taskid IN (`+ph+`)
		ORDER BY mt.taskid, ts.mediaseq, ts.id`, args...)
	if err != nil {
		return fmt.Errorf("查询播报文字: %w", err)
	}
	defer rs.Close()
	first := map[int64]string{}
	count := map[int64]int{}
	for rs.Next() {
		var id int64
		var c string
		if err := rs.Scan(&id, &c); err != nil {
			return err
		}
		count[id]++
		if _, ok := first[id]; !ok {
			first[id] = c
		}
	}
	if err := rs.Err(); err != nil {
		return err
	}
	for i := range items {
		id := items[i].TaskID
		t := first[id]
		if n := count[id]; n > 1 {
			t = fmt.Sprintf("%s …（共 %d 段）", t, n)
		}
		items[i].Text = t
	}
	return nil
}

// writeSentences 建一条 typeid='tts' 的虚拟媒体，再把每段文字挂上去。
//
// ⚠ `media.sample` 这一列在 TTS 媒体行里存的是**任务 id**，不是采样率。
//
// 旧版这条语句是：
//
//	INSERT INTO media(name, typeid, filename, folderid, timelength, channel, sample, bitrate)
//	VALUES ('$taskname','tts','tts','0','0','0','$gettaskid','$tasktype')
//
// 第 7 个值 `$gettaskid` 落在 `sample` 上，第 8 个 `$tasktype` 落在 `bitrate` 上。
// 现网 6 条 TTS 媒体行**无一例外**都是 `sample == 它所挂任务的 taskid`：
//
//	media 59 sample=70009 → task 70009      media 67 sample=70024 → task 70024
//	media 61 sample=70014 → task 70014      media 68 sample=70035 → task 70035
//	media 63 sample=70020 → task 70020      media 69 sample=70033 → task 70033
//
// 这是继 `shortcutkeytask.mediaid` 存 taskid、`ttssentence.sentenceid` 存 mediaid、
// LED 的 `mediaoftask.mediaid` 存 ledsentence.id 之后，同一套库里第四处「列名不能按字面理解」。
// 后台 TTS 服务很可能靠它反查任务，所以**必须照写** —— 早前这里写 0，
// 新建出来的语音任务会丢掉这条回指链路（新版缺陷 N-14）。
//
// bitrate 保持 0：现网同样由本页产生的那一行（media 69 / task 70033）就是 0。
// 另外两行的 128000 与 30 来自别的代码路径，不作为本页的依据。
func writeSentences(ctx context.Context, tx *sql.Tx, taskID int64, in Input) error {
	// timelength 写 0 —— 合成之前谁也不知道要念多久，由后台合成后回写。
	res, err := tx.ExecContext(ctx,
		`INSERT INTO media (name, size, typeid, priority, filename, folderid, timelength, channel, sample, bitrate)
		 VALUES (?,?,?,?,?,?,?,?,?,?)`,
		in.TaskName, 0, "tts", 0, "tts", 0, 0, 0, taskID, 0)
	if err != nil {
		return fmt.Errorf("新建语音媒体: %w", err)
	}
	mediaID, err := res.LastInsertId()
	if err != nil {
		return err
	}
	// sort 写 0，与旧版 `INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES (…,'0')` 一致
	if _, err := tx.ExecContext(ctx,
		`INSERT INTO mediaoftask (mediaid, taskid, sort) VALUES (?,?,?)`,
		mediaID, taskID, 0); err != nil {
		return fmt.Errorf("绑定语音媒体: %w", err)
	}

	seq := 0
	// 提示音排在最前面：type=0、content 为空、mediaid 指向那条真实媒体。
	// 旧版只在「tts终端」选到服务器本机时才写这一条。
	if in.PromptID > 0 {
		if _, err := tx.ExecContext(ctx, `
			INSERT INTO ttssentence (name, sentenceid, type, mediaid, content, mediaseq, speed, volume, male)
			VALUES (?,?,?,?,?,?,?,?,?)`,
			in.TaskName, mediaID, sentenceTypeMusic, in.PromptID, "",
			seq, in.TTSSpeed, in.Volume, in.MusicMode); err != nil {
			return fmt.Errorf("写入提示音: %w", err)
		}
		seq++
	}
	// 正文按 ≤600 字节切段，每段的 speed / volume / male 都取表单上那一组全局参数。
	// ⚠ mediaseq 从 0 开始（旧版 $gettempi 初值就是 0）。
	for _, c := range splitTTSText(in.Text) {
		if _, err := tx.ExecContext(ctx, `
			INSERT INTO ttssentence (name, sentenceid, type, mediaid, content, mediaseq, speed, volume, male)
			VALUES (?,?,?,?,?,?,?,?,?)`,
			in.TaskName, mediaID, sentenceTypeInput, 0, c,
			seq, in.TTSSpeed, in.Volume, in.MusicMode); err != nil {
			return fmt.Errorf("写入播报文字: %w", err)
		}
		seq++
	}
	return nil
}

// clearSentences 顺序要紧：先按 mediaoftask 找到 mediaid 删 ttssentence 与 media，
// 最后才删 mediaoftask —— 反过来就找不到该删哪些了。
func clearSentences(ctx context.Context, tx *sql.Tx, taskID int64) error {
	if _, err := tx.ExecContext(ctx, `
		DELETE FROM ttssentence
		WHERE sentenceid IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)`, taskID); err != nil {
		return fmt.Errorf("清理播报文字: %w", err)
	}
	if _, err := tx.ExecContext(ctx, `
		DELETE FROM media
		WHERE typeid = 'tts' AND id IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)`,
		taskID); err != nil {
		return fmt.Errorf("清理语音媒体: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM mediaoftask WHERE taskid = ?`, taskID); err != nil {
		return fmt.Errorf("清理媒体绑定: %w", err)
	}
	return nil
}
