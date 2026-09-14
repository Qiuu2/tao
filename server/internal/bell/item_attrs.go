package bell

import (
	"context"
	"database/sql"
	"fmt"

	"htweb/internal/task"
)

// 打铃条目自己的「方案级」属性：起止日期、星期掩码、提前开电源、音量、
// 任务级别、发送模式、播放模式。
//
// # 为什么它们是按条目存的
//
// 这七项名义上属于「方案」，但 `task` 表里每一行各存一份 —— 一个方案就是
// 共享同一个 `task.info` 的一组行，没有独立的方案行可以放它们（契约 C-38）。
// 所以它们完全可以在组内不一致，`Detail.MixedAttrs` 就是用来如实报出这件事的。
//
// 旧版两条路各写各的：
//
//	整表提交    do.php:belltaskalonemodify   一份套全组
//	行内「修改」 modifyonebellplan.php        只写这一条目
//
// 行内那条的 URL 里带着 getprepower / getstartdate / getenddate / getexemodel /
// task_default_volume / task_priority_text / sendmode —— 就是上面那排控件当时的
// 值。配套的 getonetaskterminal.php + getonetaskterminal.js 则在 radio 选中时
// 把这一条目自己的值灌回那排控件，读写是一对的。
//
// 新版一度只做了「整表提交」那条，行内「修改」把整组悄悄丢掉 ——
// 表现就是「任务级别改成 10、点了修改，重新打开还是原来那个数」。

// checkItemAttrs 校验一条目的方案级属性。
//
// 判据与 validatePlanLevel 逐条一致 —— 两处分头写迟早会出现
// 「整表提交拦得住、行内修改拦不住」这种缝。
func (s *Service) checkItemAttrs(ctx context.Context, a *ItemAttrs, ownerID int64, oldPriority *int) error {
	if !reDate.MatchString(a.StartDate) || !reDate.MatchString(a.EndDate) {
		return fmt.Errorf("起止日期格式不正确，应为 YYYY-MM-DD")
	}
	if a.EndDate < a.StartDate {
		return fmt.Errorf("结束日期不能早于开始日期")
	}
	if !reExeModel.MatchString(a.ExeModel) {
		return fmt.Errorf("星期掩码必须是 7 位的 0/1 字符串，例如 1111100")
	}
	if a.Volume < 0 || a.Volume > 100 {
		return fmt.Errorf("音量必须在 0 ~ 100 之间")
	}
	if a.PrePower < 0 || a.PrePower > 3600 {
		return fmt.Errorf("提前开电源必须在 0 ~ 3600 秒之间")
	}
	if a.DataSendMode != 0 && a.DataSendMode != 1 {
		return fmt.Errorf("发送模式只能是 0（单播）或 1（组播）")
	}
	return s.checkPriority(ctx, ownerID, a.Priority, oldPriority)
}

// writeItemAttrs 把这一组写进主条目与它的全部子任务。
//
// ⚠ 子任务（功放 9 / LED 字幕 30、24）必须一起写：它们各是一行 task，
// 各有自己的 startdate / enddate / exemodel / priority。漏掉的话，
// 把课时挪到新日期段之后，功放还留在旧日期上 —— 到了新日期不会提前开电源。
// 功放子任务的 playtime 另算（见 resyncItemPower），这里不动它。
func writeItemAttrs(ctx context.Context, tx *sql.Tx, taskID int64, a *ItemAttrs) error {
	if _, err := tx.ExecContext(ctx, `
		UPDATE task SET startdate = ?, enddate = ?, exemodel = ?,
		                defaultvolume = ?, priority = ?, prepower = ?,
		                datasendmodel = ?, israndomplay = ?
		WHERE taskid = ? OR sec_task_id = ?`,
		a.StartDate, a.EndDate, a.ExeModel, a.Volume, a.Priority,
		a.PrePower, a.DataSendMode, normRandom(a.IsRandomPlay), taskID, taskID); err != nil {
		return fmt.Errorf("修改条目属性: %w", err)
	}
	return nil
}

// resyncItemPower 按新的 prepower 处理这一条目的功放子任务：建 / 删 / 改时间。
//
// 三种情况都要覆盖，少一种就会留下一个对不上的状态：
//
//	prepower 由 0 变正   → 原来没有子任务，要**新建**一条
//	prepower 由正变 0    → 子任务连同它的关联行一起删掉
//	prepower 正 → 另一个正 → 只改播放时间（主条目时间 - prepower）
//
// 旧版行内修改这一条走的是「先删后建」（modifyonebellplan.php 把整条重写），
// 这里不删主条目、只重建子任务，效果一样而 taskid 更稳定 ——
// terminalkeymaptask 之类按 taskid 关联的表不会跟着断。
func resyncItemPower(ctx context.Context, tx *sql.Tx, planName string, ownerID, taskID int64,
	it *ItemInput, a *ItemAttrs) error {

	ids, err := collectIDs(ctx, tx,
		`SELECT taskid FROM task WHERE sec_task_id = ? AND tasktype = ?`, taskID, PowerType)
	if err != nil {
		return err
	}
	if a.PrePower <= 0 {
		if len(ids) == 0 {
			return nil
		}
		return purgeTaskRows(ctx, tx, ids)
	}

	powerTime, err := task.ShiftTime(it.PlayTime, -a.PrePower)
	if err != nil {
		return err
	}
	if len(ids) > 0 {
		ph, args := placeholders(ids)
		if _, err := tx.ExecContext(ctx,
			`UPDATE task SET playtime = ? WHERE taskid IN (`+ph+`)`,
			append([]interface{}{powerTime}, args...)...); err != nil {
			return fmt.Errorf("重算功放子任务时间: %w", err)
		}
		return nil
	}

	// 原来没有功放子任务，现在要有。列清单与 saveItems 里那条 INSERT 一致 ——
	// 少写一列就是一个 NOT NULL 且无默认值的坑（timelengthtype 就是一个）。
	res, err := tx.ExecContext(ctx, `
		INSERT INTO task (taskname, israndomplay, timelengthtype, timelength,
		                  prepower, datasendmodel, state, startdate, enddate,
		                  playtime, endtime, exemodel, priority, tasktype, channel,
		                  bandrate, samplerate, cmd, cmdargs, playfileid, info,
		                  defaultvolume, task_user_id, sec_task_id, parentid, offlinestate)
		VALUES (?,?,?,?, ?,?,0,?,?, ?, '00:00:00', ?,?,?,0, 0,0,0,'0',0,?, ?,?,?,0,0)`,
		it.TaskName, normRandom(a.IsRandomPlay), it.TimeLengthTy, it.TimeLength,
		a.PrePower, a.DataSendMode, a.StartDate, a.EndDate,
		powerTime, a.ExeModel, a.Priority, PowerType,
		planName, a.Volume, ownerID, taskID)
	if err != nil {
		return fmt.Errorf("补建功放子任务: %w", err)
	}
	powerID, err := res.LastInsertId()
	if err != nil {
		return err
	}
	// 终端清单照抄主条目 —— 没有终端的功放子任务到点了什么都开不了。
	//
	// ⚠ 这次如果**同时**在改终端（ApplyTerminals），那一步会在后面按
	// 「主条目 + 全部子任务」重写一遍，这里抄的会被覆盖掉，不冲突。
	if _, err := tx.ExecContext(ctx, `
		INSERT INTO terminaloftask (taskid, terminalid, workstate, groupid, area)
		SELECT ?, terminalid, workstate, groupid, area FROM terminaloftask WHERE taskid = ?`,
		powerID, taskID); err != nil {
		return fmt.Errorf("写入功放子任务终端: %w", err)
	}
	return nil
}
