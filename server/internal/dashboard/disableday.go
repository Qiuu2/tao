package dashboard

import (
	"context"
	"fmt"
	"time"

	"htweb/internal/auth"
)

// 看板上的「当天启用 / 当天停用」（旧版 do.php:enordis_date_task）。
//
// # 这两个不是筛选，是操作
//
// 新版一度把它们做成了一组筛选单选（当天启用 / 当天停用 / 全部），是误读。
// 旧版 Browse_active_task.html:130 的 enordis_week_date_task() 干的是：
// 把勾中的 taskid 拼成一串，带着**当前看的那一天**的日期发给 do.php，
//
//	en_dis = 0 → UPDATE task SET disableday = '<那一天>' WHERE taskid IN(...)
//	en_dis = 1 → UPDATE task SET disableday = '0000-00-00' WHERE taskid IN(...)
//
// 也就是「今天这一条别响」/「取消，照常响」。它与 projectstate 是两回事：
// projectstate 是整条任务的长期启停，disableday 只挖掉某一天。
//
// # 子任务必须跟着改
//
// 旧版紧跟着还有一句 `WHERE sec_task_id IN(...)` —— 功放（9）与 LED 字幕
// （30/24）子任务各是一行 task，各有自己的 disableday。漏掉它，那天主任务
// 不响、功放却照样提前开电源，LED 照样滚字幕。
//
// # 日期由服务端算
//
// 旧版是把日期当 URL 参数传的（`&getdate=`），谁都能塞一个任意日期进去。
// 这里只收「星期下拉选的是第几天」，日期用 viewDateOf 当场算 ——
// 与 Browse 同一份算法，界面上写着「看的是 9-16」，点下去停的就是 9-16。

// DisableDayInput 是一次「当天启用 / 当天停用」。
type DisableDayInput struct {
	IDs []int64
	// Weekday 是星期下拉的取值（1~7，1 = 周日），其余值当「今天」。
	Weekday int
	// Disable 为 true 是「当天停用」，false 是「当天启用」。
	Disable bool
}

// DisableDayResult 报告实际改了什么。
type DisableDayResult struct {
	// Date 是写进去的那一天；「当天启用」时是 ZeroDay。
	Date string `json:"date"`
	// ViewDate 是这次操作针对的那一天，两个动作都有值（界面要拿它写提示）。
	ViewDate string `json:"viewDate"`
	// Tasks 是实际改到的主任务条数，Subs 是跟着改的子任务条数。
	Tasks int64 `json:"tasks"`
	Subs  int64 `json:"subs"`
	// Skipped 是请求里但没改到的 id：不存在，或者不是自己的任务。
	Skipped []int64 `json:"skipped"`
}

// ZeroDay 是 disableday 的「没有停用」值。
//
// ⚠ 是字符串 '0000-00-00' 不是 NULL —— 现网存量数据就是这个值，
// 旧版写回去的也是它（do.php:28160）。改成 NULL 会让旧版页面和后台 C 服务
// 读出来的东西变样。
const ZeroDay = "0000-00-00"

// SetDisableDay 给勾中的任务写 / 清 disableday。
func (s *Service) SetDisableDay(ctx context.Context, u *auth.User, in DisableDayInput) (*DisableDayResult, error) {
	ids := dedup(in.IDs)
	if len(ids) == 0 {
		return nil, fmt.Errorf("请先勾选任务")
	}
	viewDate, _ := viewDateOf(time.Now(), in.Weekday)
	day := ZeroDay
	if in.Disable {
		day = viewDate
	}
	out := &DisableDayResult{Date: day, ViewDate: viewDate, Skipped: []int64{}}

	// 只能动自己的任务（与 task.Control 的 BR-174 同一条规矩）。
	// 先查出能动的，再只改这些 —— 直接把 task_user_id 塞进 UPDATE 的 WHERE
	// 也行，但那样分不清「没改到」是因为不存在还是因为不是自己的。
	ph, args := placeholders(ids)
	q := `SELECT taskid FROM task WHERE taskid IN (` + ph + `)`
	if !u.IsAdmin {
		q += ` AND COALESCE(task_user_id,0) = ?`
		args = append(args, u.ID)
	}
	rows, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询任务: %w", err)
	}
	mine := map[int64]bool{}
	var okIDs []int64
	for rows.Next() {
		var id int64
		if err := rows.Scan(&id); err != nil {
			rows.Close()
			return nil, err
		}
		mine[id] = true
		okIDs = append(okIDs, id)
	}
	rows.Close()
	if err := rows.Err(); err != nil {
		return nil, err
	}
	for _, id := range ids {
		if !mine[id] {
			out.Skipped = append(out.Skipped, id)
		}
	}
	if len(okIDs) == 0 {
		return out, nil
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	oph, oargs := placeholders(okIDs)
	res, err := tx.ExecContext(ctx,
		`UPDATE task SET disableday = ? WHERE taskid IN (`+oph+`)`,
		append([]interface{}{day}, oargs...)...)
	if err != nil {
		return nil, fmt.Errorf("修改单独停用日: %w", err)
	}
	out.Tasks, _ = res.RowsAffected()

	// 功放与 LED 字幕子任务跟着一起改（旧版 do.php:28156/28161 也有这一句）
	sub, err := tx.ExecContext(ctx,
		`UPDATE task SET disableday = ? WHERE sec_task_id IN (`+oph+`)`,
		append([]interface{}{day}, oargs...)...)
	if err != nil {
		return nil, fmt.Errorf("修改子任务的单独停用日: %w", err)
	}
	out.Subs, _ = sub.RowsAffected()

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return out, nil
}
