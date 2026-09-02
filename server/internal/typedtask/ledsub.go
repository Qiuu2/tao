package typedtask

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"
)

// 挂在**文字语音任务**上的 LED 字幕子任务。
//
// :80 的文字语音表单里也有「led播放 / Led速度 / Led字幕」，与文件广播那一项是同一件事：
// 另建一条 tasktype = 30 的任务，sec_task_id 指回主任务。
// 现网 70035（sec_task_id = 70033）就是这么来的。
//
// 与 task 包里那份（task/ledsub.go）逻辑相同，只是这边的入参结构不一样，
// 没有强行抽公共包 —— 两个模块的 Input 差别不小，硬合并反而更难读。
//
// ⚠ 独立的 LED 播放任务（led 页那一批）走的是另一条路：
//    tasktype 同样是 30，但 sec_task_id = 0，由 KindLED 那套增删改管。

// wantLEDSub 判断这次提交要不要给主任务挂 LED 字幕。
// 只有文字语音会走到这里；字幕留空视为不要。
func wantLEDSub(k Kind, in Input) bool {
	return k == KindTTS && in.LED != nil && strings.TrimSpace(in.LED.Text) != ""
}

// findLEDSub 找主任务已有的 LED 子任务。0 表示没有。
func findLEDSub(ctx context.Context, tx *sql.Tx, mainID int64) (int64, error) {
	var id sql.NullInt64
	err := tx.QueryRowContext(ctx,
		`SELECT taskid FROM task WHERE sec_task_id = ? AND tasktype IN (24,30) LIMIT 1`,
		mainID).Scan(&id)
	if err != nil && !errors.Is(err, sql.ErrNoRows) {
		return 0, fmt.Errorf("查询 LED 子任务: %w", err)
	}
	return id.Int64, nil
}

// dropLEDSub 删掉 LED 子任务连同它的虚拟媒体、字幕与终端绑定。
//
// 顺序要紧：先按 mediaoftask 找到 mediaid 删 ledsentence 与 media，
// 最后才删 mediaoftask —— 反过来就定位不到了。
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

// syncLEDSub 让文字语音任务的 LED 子任务与表单保持一致：
// 从无到有则新建，从有到无则删除，一直有则整条重建。返回子任务 id（0 = 没有）。
func (s *Service) syncLEDSub(ctx context.Context, tx *sql.Tx, k Kind,
	mainID int64, in Input, ownerID int64) (int64, error) {

	existing, err := findLEDSub(ctx, tx, mainID)
	if err != nil {
		return 0, err
	}
	if !wantLEDSub(k, in) {
		if existing > 0 {
			if err := dropLEDSub(ctx, tx, existing); err != nil {
				return 0, err
			}
		}
		return 0, nil
	}
	if err := validateLED(in.LED); err != nil {
		return 0, err
	}
	if existing > 0 {
		if err := dropLEDSub(ctx, tx, existing); err != nil {
			return 0, err
		}
	}

	// 除 tasktype / sec_task_id 外整行照抄主任务，playtime 也与主任务相同。
	// 列清单与 insertTask 保持一致，interval_s / intplaylength / intplaylengthtype
	// 同样显式写 0（理由见 insertTask 的注释 N-17）。
	endTime, err := endTimeOf(in.PlayTime, in.DurationSec)
	if err != nil {
		return 0, err
	}
	res, err := tx.ExecContext(ctx, `
		INSERT INTO task
		  (taskname, israndomplay, projectstate, timelengthtype, timelength, prepower,
		   datasendmodel, state, startdate, enddate, playtime, endtime, exemodel,
		   priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs,
		   playfileid, info, defaultvolume, task_user_id, sec_task_id, parentid,
		   interval_s, intplaylength, intplaylengthtype)
		VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,0,0)`,
		in.TaskName, 1, in.ProjectState, in.TimeLengthType, in.TimeLength, in.Prepower,
		in.DataSendModel, 0, in.StartDate, in.EndDate, in.PlayTime, endTime, in.ExeModel,
		in.Priority, ledSubType, 0, 0, 0, 0, "0",
		0, "", in.Volume, ownerID, mainID, in.FolderID)
	if err != nil {
		return 0, fmt.Errorf("新建 LED 子任务: %w", err)
	}
	ledID, err := res.LastInsertId()
	if err != nil {
		return 0, err
	}
	if err := writeLED(ctx, tx, ledID, in); err != nil {
		return 0, err
	}
	// 终端清单与主任务一致（现网 70033 / 70035 挂的是同一台终端）
	if err := writeTerminals(ctx, tx, ledID, in.Terminals); err != nil {
		return 0, err
	}
	return ledID, nil
}

// loadLEDSub 读主任务的 LED 子任务，供编辑弹窗回填。没有则返回 nil。
func (s *Service) loadLEDSub(ctx context.Context, mainID int64) (*LEDDetail, error) {
	var ledID int64
	err := s.db.QueryRowContext(ctx,
		`SELECT taskid FROM task WHERE sec_task_id = ? AND tasktype IN (24,30) LIMIT 1`,
		mainID).Scan(&ledID)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, nil
	}
	if err != nil {
		return nil, fmt.Errorf("查询 LED 子任务: %w", err)
	}
	return s.taskLED(ctx, ledID)
}
