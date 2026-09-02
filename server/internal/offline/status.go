package offline

import (
	"context"
	"fmt"

	"htweb/internal/auth"
	"htweb/internal/store"
)

// 离线状态查询（F-45）与清除（F-47）。

// MediaStatus 是媒体维度的一行下发状态。
type MediaStatus struct {
	MediaID      int64  `json:"mediaId"`
	MediaName    string `json:"mediaName"`
	TerminalID   int64  `json:"terminalId"`
	TerminalName string `json:"terminalName"`
	TaskID       int64  `json:"taskId"`
	TaskName     string `json:"taskName"`
	State        int    `json:"offlinestate"`
	StateText    string `json:"offlineStateText"`
	Sort         int    `json:"sort"`
	// CopyMissing 表示 offlinemedia 里已经没有这条副本了。
	// 旧版用隐式内连接（D-158），副本一被清记录就整条消失 ——
	// 于是「明明下发过，列表里却什么都没有」，也没法把它删掉。
	CopyMissing     bool `json:"copyMissing"`
	TerminalMissing bool `json:"terminalMissing"`
}

type MediaStatusQuery struct {
	TerminalID int64
	MediaID    int64
	TaskID     int64
	// State < 0 表示不按状态筛选。
	State int
	Pager store.Pager
}

type MediaStatusResult struct {
	Items []MediaStatus
	Total int64
}

// MediaStatusList 查询媒体下发状态。
//
// 必须分页（BR-212）：旧版全量渲染，终端数 × 媒体数 可达数万行（D-159）。
func (s *Service) MediaStatusList(ctx context.Context, u *auth.User,
	q MediaStatusQuery) (*MediaStatusResult, error) {

	cond := &store.Cond{}
	if q.TerminalID > 0 {
		cond.Add("o.terminalid = ?", q.TerminalID)
	}
	if q.MediaID > 0 {
		cond.Add("o.mediaid = ?", q.MediaID)
	}
	if q.TaskID > 0 {
		cond.Add("o.taskid = ?", q.TaskID)
	}
	if q.State >= 0 {
		cond.Add("o.offlinestate = ?", q.State)
	}
	if !u.IsAdmin {
		cond.Add("o.terminalid IN (SELECT terminalid FROM userterminal WHERE userid = ?)", u.ID)
	}
	where := cond.Where()

	out := &MediaStatusResult{Items: []MediaStatus{}}
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM offlinemediaofterminal o"+where, cond.Args()...).
		Scan(&out.Total); err != nil {
		return nil, fmt.Errorf("统计离线下发记录: %w", err)
	}
	if out.Total == 0 {
		return out, nil
	}

	args := append(append([]interface{}{}, cond.Args()...), q.Pager.PageSize, q.Pager.Offset())
	rows, err := s.db.QueryContext(ctx, `
		SELECT o.mediaid, o.terminalid, o.taskid, COALESCE(o.offlinestate,0), COALESCE(o.sort,0),
		       om.id IS NOT NULL, COALESCE(om.name,''),
		       t.id IS NOT NULL, COALESCE(t.terminalname,''),
		       COALESCE(tk.taskname,'')
		FROM offlinemediaofterminal o
		LEFT JOIN offlinemedia om ON om.id = o.mediaid
		LEFT JOIN terminal t      ON t.id  = o.terminalid
		LEFT JOIN task tk         ON tk.taskid = o.taskid`+where+`
		ORDER BY o.terminalid, o.taskid, o.sort, o.mediaid
		LIMIT ? OFFSET ?`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询离线下发记录: %w", err)
	}
	defer rows.Close()

	for rows.Next() {
		var m MediaStatus
		var copyOK, termOK bool
		if err := rows.Scan(&m.MediaID, &m.TerminalID, &m.TaskID, &m.State, &m.Sort,
			&copyOK, &m.MediaName, &termOK, &m.TerminalName, &m.TaskName); err != nil {
			return nil, err
		}
		m.StateText = Text(m.State)
		if !copyOK {
			m.CopyMissing, m.MediaName = true, "(离线副本已删除)"
		}
		if !termOK {
			m.TerminalMissing, m.TerminalName = true, "(终端已删除)"
		}
		out.Items = append(out.Items, m)
	}
	return out, rows.Err()
}

// TaskStatus 是任务维度的一行下发状态。
type TaskStatus struct {
	TaskID       int64  `json:"taskId"`
	TaskName     string `json:"taskName"`
	TerminalID   int64  `json:"terminalId"`
	TerminalName string `json:"terminalName"`
	State        int    `json:"offlinestate"`
	StateText    string `json:"offlineStateText"`
	Area         string `json:"area"`
	// CopyMissing 表示 offlinetask 里没有这个任务的副本。
	CopyMissing     bool `json:"copyMissing"`
	TerminalMissing bool `json:"terminalMissing"`
	MediaCount      int  `json:"mediaCount"`
}

type TaskStatusResult struct {
	Items []TaskStatus
	Total int64
}

func (s *Service) TaskStatusList(ctx context.Context, u *auth.User,
	q MediaStatusQuery) (*TaskStatusResult, error) {

	cond := &store.Cond{}
	if q.TerminalID > 0 {
		cond.Add("o.terminalid = ?", q.TerminalID)
	}
	if q.TaskID > 0 {
		cond.Add("o.taskid = ?", q.TaskID)
	}
	if q.State >= 0 {
		cond.Add("o.offlinestate = ?", q.State)
	}
	if !u.IsAdmin {
		cond.Add("o.terminalid IN (SELECT terminalid FROM userterminal WHERE userid = ?)", u.ID)
	}
	where := cond.Where()

	out := &TaskStatusResult{Items: []TaskStatus{}}
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM offlinetaskofterminal o"+where, cond.Args()...).
		Scan(&out.Total); err != nil {
		return nil, fmt.Errorf("统计离线任务记录: %w", err)
	}
	if out.Total == 0 {
		return out, nil
	}

	args := append(append([]interface{}{}, cond.Args()...), q.Pager.PageSize, q.Pager.Offset())
	rows, err := s.db.QueryContext(ctx, `
		SELECT o.taskid, o.terminalid, COALESCE(o.offlinestate,0), COALESCE(o.area,''),
		       ot.taskid IS NOT NULL, COALESCE(ot.taskname, COALESCE(tk.taskname,'')),
		       t.id IS NOT NULL, COALESCE(t.terminalname,''),
		       (SELECT COUNT(*) FROM offlinemediaofterminal m
		         WHERE m.taskid = o.taskid AND m.terminalid = o.terminalid)
		FROM offlinetaskofterminal o
		LEFT JOIN offlinetask ot ON ot.taskid = o.taskid
		LEFT JOIN task tk        ON tk.taskid = o.taskid
		LEFT JOIN terminal t     ON t.id = o.terminalid`+where+`
		ORDER BY o.taskid, o.terminalid
		LIMIT ? OFFSET ?`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询离线任务记录: %w", err)
	}
	defer rows.Close()

	for rows.Next() {
		var t TaskStatus
		var copyOK, termOK bool
		if err := rows.Scan(&t.TaskID, &t.TerminalID, &t.State, &t.Area,
			&copyOK, &t.TaskName, &termOK, &t.TerminalName, &t.MediaCount); err != nil {
			return nil, err
		}
		t.StateText = Text(t.State)
		if !copyOK {
			t.CopyMissing = true
		}
		if !termOK {
			t.TerminalMissing, t.TerminalName = true, "(终端已删除)"
		}
		out.Items = append(out.Items, t)
	}
	return out, rows.Err()
}

// Summary 是离线数据的总览，清空前给用户看代价。
type Summary struct {
	OfflineMedia          int `json:"offlineMedia"`
	OfflineMediaTerminals int `json:"offlineMediaOfTerminal"`
	OfflineTasks          int `json:"offlineTask"`
	OfflineTaskTerminals  int `json:"offlineTaskOfTerminal"`
	// TasksMarked 是 task.offlinestate 不为 0 的任务数 —— 清空时要一并复位。
	TasksMarked int `json:"tasksMarked"`
}

func (s *Service) Summary(ctx context.Context) (*Summary, error) {
	su := &Summary{}
	err := s.db.QueryRowContext(ctx, `
		SELECT (SELECT COUNT(*) FROM offlinemedia),
		       (SELECT COUNT(*) FROM offlinemediaofterminal),
		       (SELECT COUNT(*) FROM offlinetask),
		       (SELECT COUNT(*) FROM offlinetaskofterminal),
		       (SELECT COUNT(*) FROM task WHERE COALESCE(offlinestate,0) <> 0)`).
		Scan(&su.OfflineMedia, &su.OfflineMediaTerminals, &su.OfflineTasks,
			&su.OfflineTaskTerminals, &su.TasksMarked)
	if err != nil {
		return nil, fmt.Errorf("统计离线数据: %w", err)
	}
	return su, nil
}

// PurgeConfirmText 是清空全部离线数据必须逐字输入的确认文本。
const PurgeConfirmText = "清空全部离线数据"

type PurgeResult struct {
	OfflineMedia          int64 `json:"offlineMedia"`
	OfflineMediaTerminals int64 `json:"offlineMediaOfTerminal"`
	OfflineTasks          int64 `json:"offlineTask"`
	OfflineTaskTerminals  int64 `json:"offlineTaskOfTerminal"`
	TasksReset            int64 `json:"tasksReset"`
}

// PurgeAll 清空全部离线数据（F-47）。
//
// 旧版 `stop_offline_music` 的 flag==14 分支执行 **4 条无 WHERE 的 DELETE**，
// 仅靠前端一个 confirm 保护，服务端没有任何确认或权限校验（D-148 / BR-206）。
//
// 新版：独立接口 + 逐字确认文本 + 超管权限 + 写审计日志 + 一个事务。
// 另外多做一步：把 task.offlinestate 一并复位为 0 ——
// 旧版删完四张表却把 task 上的离线标记留着，任务永远显示「离线中」。
func (s *Service) PurgeAll(ctx context.Context, confirmText string) (*PurgeResult, error) {
	if confirmText != PurgeConfirmText {
		return nil, fmt.Errorf("确认文本不正确，需要逐字输入「%s」", PurgeConfirmText)
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	out := &PurgeResult{}
	for _, spec := range []struct {
		q   string
		dst *int64
	}{
		{"DELETE FROM offlinemediaofterminal", &out.OfflineMediaTerminals},
		{"DELETE FROM offlinetaskofterminal", &out.OfflineTaskTerminals},
		{"DELETE FROM offlinetask", &out.OfflineTasks},
		{"DELETE FROM offlinemedia", &out.OfflineMedia},
	} {
		res, err := tx.ExecContext(ctx, spec.q)
		if err != nil {
			return nil, fmt.Errorf("清空离线数据: %w", err)
		}
		*spec.dst, _ = res.RowsAffected()
	}
	res, err := tx.ExecContext(ctx,
		`UPDATE task SET offlinestate = 0 WHERE COALESCE(offlinestate,0) <> 0`)
	if err != nil {
		return nil, fmt.Errorf("复位任务离线状态: %w", err)
	}
	out.TasksReset, _ = res.RowsAffected()

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return out, nil
}
