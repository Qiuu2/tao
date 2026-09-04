package task

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"
)

// LED 字幕子任务：:80 的文件广播表单里那一项「led播放 / Led字幕 / Led速度」。
//
// # 它到底是什么
//
// 不是一个开关列，而是**再建一条完整的 task 行**，除下面几处外整行照抄主任务：
//
//	tasktype     = 30
//	sec_task_id  = 主任务 taskid
//	taskname     = 用户填的名字（留空则跟主任务同名）
//
// 现网两条既有数据就是这么长的（2026-08 实测）：
//
//	taskid 70009「任务一」 tasktype=30 sec_task_id=70007（主任务「早读」）
//	taskid 70035「防欺凌…」tasktype=30 sec_task_id=70033（主任务是条文字语音）
//
// 起止日期、星期掩码、时长/次数、prepower、priority、datasendmodel、
// defaultvolume、projectstate、israndomplay、parentid 全部与各自的主任务逐列相同，
// 终端清单也与主任务一致（terminaloftask 里两条任务挂的是同一台终端）。
//
// # ⚠ playtime 现网两条数据不一致
//
//	70009 = 主任务 playtime - 15s（= prepower，与功放子任务 70008 同一时刻）
//	70035 = 主任务 playtime **相同**
//
// 两条不同的代码路径留下的。这里统一取「**与主任务相同**」：
// 字幕跟着播放内容走更说得通，而且 70033~70035 那组是更近的数据（08-25 vs 08-17）。
// 这一处属于两种旧行为里挑一种，不是新造语义。
//
// # 内容怎么存
//
// 与文字语音同构：先建一条 typeid='tts' 的虚拟 media（sample = 子任务 id），
// mediaoftask 把它挂到子任务上，再往 ledsentence 写一行，
// **ledsentence.mediaid = media.id**（不是 ledsentence 自己的 id，见 typedtask/led.go 的 N-20）。

const (
	// ledsentence.text 是 varchar(1024)
	ledTextLimit = 1024
	ledSpeedMax  = 10
	ledModeMax   = 10
	// ledsentence.type：现网四条全是 1
	ledSentenceType = 1
)

func (in *LEDSub) normalize(mainName string) error {
	in.Name = strings.TrimSpace(in.Name)
	in.Text = strings.TrimSpace(in.Text)
	if in.Name == "" {
		in.Name = mainName
	}
	if len(in.Name) > 255 {
		return fmt.Errorf("LED 任务名称过长：按 UTF-8 计 %d 字节，上限 255 字节", len(in.Name))
	}
	if strings.ContainsAny(in.Name, "?&=") {
		return fmt.Errorf("LED 任务名称不能包含 ? & = 这三个字符")
	}
	if len(in.Text) > ledTextLimit {
		return fmt.Errorf("Led字幕过长：按 UTF-8 计 %d 字节，上限 %d 字节（约 341 个汉字）",
			len(in.Text), ledTextLimit)
	}
	if in.Speed < 0 || in.Speed > ledSpeedMax {
		return fmt.Errorf("Led速度必须在 0 ~ %d 之间", ledSpeedMax)
	}
	if in.LedMode < 0 || in.LedMode > ledModeMax {
		return fmt.Errorf("Led显示模式必须在 0 ~ %d 之间", ledModeMax)
	}
	return nil
}

// wantLED 判断这次提交要不要有 LED 子任务。字幕为空即视为不要。
func wantLED(in Input) bool {
	return in.LED != nil && strings.TrimSpace(in.LED.Text) != ""
}

// findLEDSub 找主任务已有的 LED 子任务。0 表示没有。
func findLEDSub(ctx context.Context, tx *sql.Tx, mainID int64) (int64, error) {
	ph, targs := placeholders64(ledSubTypes)
	var id sql.NullInt64
	err := tx.QueryRowContext(ctx,
		`SELECT taskid FROM task WHERE sec_task_id = ? AND tasktype IN (`+ph+`) LIMIT 1`,
		append([]interface{}{mainID}, targs...)...).Scan(&id)
	if err != nil && !errors.Is(err, sql.ErrNoRows) {
		return 0, fmt.Errorf("查询 LED 子任务: %w", err)
	}
	return id.Int64, nil
}

// dropLEDSub 删掉 LED 子任务连同它的媒体、字幕、终端绑定。
//
// 顺序要紧：先按 mediaoftask 找到 mediaid 删 ledsentence 与 media，
// 最后才删 mediaoftask —— 反过来就找不到该删哪些了。
func dropLEDSub(ctx context.Context, tx *sql.Tx, ledID int64) error {
	for _, stmt := range []string{
		`DELETE FROM ledsentence WHERE mediaid IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)`,
		`DELETE FROM media WHERE typeid = 'tts' AND id IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)`,
		`DELETE FROM mediaoftask WHERE taskid = ?`,
		`DELETE FROM ledoftask WHERE taskid = ?`,
		`DELETE FROM terminaloftask WHERE taskid = ?`,
		`DELETE FROM task WHERE taskid = ?`,
	} {
		if _, err := tx.ExecContext(ctx, stmt, ledID); err != nil {
			return fmt.Errorf("清理 LED 子任务: %w", err)
		}
	}
	return nil
}

// WriteLEDContent 建虚拟媒体 + 字幕行。与 typedtask.writeLED 同构。
//
// 导出是给作息方案用的：它的条目也能挂 LED 字幕，结构与文件广播这边完全一样，
// 不该各写一份（两份就会各写各的列，像快捷任务那样写出根本不存在的列都发现不了）。
func WriteLEDContent(ctx context.Context, tx *sql.Tx, ledID int64, led *LEDSub) error {
	res, err := tx.ExecContext(ctx,
		`INSERT INTO media (name, size, typeid, priority, filename, folderid, timelength, channel, sample, bitrate)
		 VALUES (?,?,?,?,?,?,?,?,?,?)`,
		led.Name, 0, "tts", 0, "tts", 0, 0, 0, ledID, 0)
	if err != nil {
		return fmt.Errorf("新建 LED 媒体: %w", err)
	}
	mediaID, err := res.LastInsertId()
	if err != nil {
		return err
	}
	if _, err := tx.ExecContext(ctx,
		`INSERT INTO mediaoftask (mediaid, taskid, sort) VALUES (?,?,?)`,
		mediaID, ledID, 1); err != nil {
		return fmt.Errorf("绑定 LED 媒体: %w", err)
	}
	// ⚠ mediaid 存的是 media.id
	if _, err := tx.ExecContext(ctx,
		`INSERT INTO ledsentence (text, mediaid, speed, type, mediaseq, ledmode) VALUES (?,?,?,?,?,?)`,
		led.Text, mediaID, led.Speed, ledSentenceType, 0, led.LedMode); err != nil {
		return fmt.Errorf("写入 Led字幕: %w", err)
	}
	return nil
}

// syncLEDTask 让 LED 子任务与主任务保持一致：
// 从无到有则新建，从有到无则删除，一直有则整条重写（内容全删重插，与媒体/终端清单同一策略）。
// 返回子任务 id（0 表示不存在）。
func syncLEDTask(ctx context.Context, tx *sql.Tx, mainID int64, in Input, ownerID int64) (int64, error) {
	existing, err := findLEDSub(ctx, tx, mainID)
	if err != nil {
		return 0, err
	}

	if !wantLED(in) {
		if existing > 0 {
			if err := dropLEDSub(ctx, tx, existing); err != nil {
				return 0, err
			}
		}
		return 0, nil
	}

	if err := in.LED.normalize(in.TaskName); err != nil {
		return 0, err
	}

	// 已有就先整条拆掉再建，省得逐列比对；一条子任务的数据量很小，重建代价可以忽略。
	if existing > 0 {
		if err := dropLEDSub(ctx, tx, existing); err != nil {
			return 0, err
		}
	}

	// 除 taskname / tasktype / sec_task_id 外整行照抄主任务，playtime 也与主任务相同
	sub := in
	sub.TaskName = in.LED.Name
	ledID, err := insertTask(ctx, tx, sub, TypeLEDSub, ownerID, mainID, in.PlayTime)
	if err != nil {
		return 0, fmt.Errorf("新建 LED 子任务: %w", err)
	}
	if err := WriteLEDContent(ctx, tx, ledID, in.LED); err != nil {
		return 0, err
	}
	// 终端清单与主任务一致 —— 现网 70007/70009 与 70033/70035 都是这样
	for _, t := range in.Terminals {
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO terminaloftask (taskid, terminalid, groupid, area) VALUES (?,?,?,?)`,
			ledID, t.TerminalID, t.GroupID, t.Area); err != nil {
			return 0, fmt.Errorf("写入 LED 子任务终端: %w", err)
		}
	}
	return ledID, nil
}

// loadLEDSub 读主任务的 LED 子任务，供编辑弹窗回填。没有则返回 nil。
func (s *Service) loadLEDSub(ctx context.Context, mainID int64) (*LEDSub, error) {
	ph, targs := placeholders64(ledSubTypes)
	var ledID int64
	var name string
	err := s.db.QueryRowContext(ctx,
		`SELECT taskid, COALESCE(taskname,'') FROM task
		 WHERE sec_task_id = ? AND tasktype IN (`+ph+`) LIMIT 1`,
		append([]interface{}{mainID}, targs...)...).Scan(&ledID, &name)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, nil
	}
	if err != nil {
		return nil, fmt.Errorf("查询 LED 子任务: %w", err)
	}
	out := &LEDSub{Name: name}
	// ⚠ 关联键是 ledsentence.mediaid = mediaoftask.mediaid（都是 media.id）
	err = s.db.QueryRowContext(ctx, `
		SELECT COALESCE(ls.text,''), COALESCE(ls.speed,0), COALESCE(ls.ledmode,0)
		FROM ledsentence ls
		WHERE ls.mediaid IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)
		ORDER BY ls.id LIMIT 1`, ledID).Scan(&out.Text, &out.Speed, &out.LedMode)
	if err != nil && !errors.Is(err, sql.ErrNoRows) {
		return nil, fmt.Errorf("查询 Led字幕: %w", err)
	}
	return out, nil
}
