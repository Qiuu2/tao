package task

import (
	"context"
	"database/sql"
	"errors"
	"fmt"

	"htweb/internal/auth"
	"htweb/internal/notify"
)

// 任务启停（F-34）。
//
// # 旧版实测出的协议（与手册不一致的地方以实测为准）
//
//	动作   数据库              通知报文
//	启动   state = 3           task?state=3&id=X            （不带 type）
//	停止   state = 2           task?state=2&id=X&type=2
//	暂停   不写库              task?state=22&id=X&type=2
//	恢复   不写库              task?state=23&id=X&type=2
//
// 手册说停止写 state = 0，实测旧代码写的是 2；手册也没提暂停/恢复根本不落库。
// 后台 C 服务按上面这套解析，写错就是「点了没反应」。
//
// # 旧版的漏洞
//
//   - D-113 启动语句完全不校验任务归属，构造 URL 就能启动任意人的任务
//   - D-114 不校验 projectstate，停用的方案也能被「立即执行」
//   - D-115 不校验任务有没有媒体和终端，空任务被启动后台空转
//   - D-116 逗号 ID 串直接拼进 IN(...)
//   - D-117 成功分支末尾有一句 exit（源码注释写着「是什么原因呢？？？？？？」），
//     把后面的日志写入整段跳过 —— 启停操作从来没进过 log 表
//
// 还有一处旧版自己就不自洽的地方：启动语句限定 tasktype IN(2,15)，
// 而列表查的是 tasktype IN(2,7) —— 类型 7 的任务在列表里看得见、却永远启动不了。
// 新版启动的类型集合与列表保持一致，见 startableTypes。

// startableTypes 是允许启停的任务类型。
// 与列表口径（fileTypes）对齐，另外收下旧启动语句里的 15（作息内的文件播放）。
var startableTypes = []int{TypeFile, TypeFileAlt, TypeSchedule}

// Action 是启停动作。
type Action string

const (
	ActionStart  Action = "start"
	ActionStop   Action = "stop"
	ActionPause  Action = "pause"
	ActionResume Action = "resume"
)

var validActions = map[Action]bool{
	ActionStart: true, ActionStop: true, ActionPause: true, ActionResume: true,
}

func ParseAction(s string) (Action, bool) {
	a := Action(s)
	return a, validActions[a]
}

// ControlResult 语义是「部分成功」：能做的做掉，做不了的逐条列出原因。
// 旧版是一条 UPDATE 全部照做，既不校验也不反馈。
type ControlResult struct {
	Succeeded []int64   `json:"succeeded"`
	Blocked   []Blocked `json:"blocked"`
	Notified  bool      `json:"notified"`
}

// controlRow 是启停判定用到的字段。
type controlRow struct {
	id           int64
	name         string
	taskType     int
	projectState int
	channel      int
	owner        int64
	mediaCount   int
	termCount    int
}

// loadControlRows 一次查完判定所需的全部信息（含两个 EXISTS 计数），
// 取代旧版「先 UPDATE 再说」的做法，也避免每个任务两条子查询。
func (s *Service) loadControlRows(ctx context.Context, ids []int64) (map[int64]controlRow, error) {
	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx, `
		SELECT t.taskid, COALESCE(t.taskname,''), t.tasktype,
		       COALESCE(t.projectstate,0), COALESCE(t.channel,0), COALESCE(t.task_user_id,0),
		       (SELECT COUNT(*) FROM mediaoftask WHERE taskid = t.taskid),
		       (SELECT COUNT(*) FROM terminaloftask WHERE taskid = t.taskid)
		FROM task t WHERE t.taskid IN (`+ph+`)`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询任务状态: %w", err)
	}
	defer rs.Close()

	out := make(map[int64]controlRow, len(ids))
	for rs.Next() {
		var r controlRow
		if err := rs.Scan(&r.id, &r.name, &r.taskType, &r.projectState,
			&r.channel, &r.owner, &r.mediaCount, &r.termCount); err != nil {
			return nil, err
		}
		out[r.id] = r
	}
	return out, rs.Err()
}

// Control 执行启停动作。
func (s *Service) Control(ctx context.Context, u *auth.User, n *notify.Notifier,
	action Action, ids []int64) (*ControlResult, error) {

	out := &ControlResult{Succeeded: []int64{}, Blocked: []Blocked{}}
	if len(ids) == 0 {
		return out, nil
	}

	rows, err := s.loadControlRows(ctx, ids)
	if err != nil {
		return nil, err
	}

	typeOK := map[int]bool{}
	for _, t := range startableTypes {
		typeOK[t] = true
	}

	var doable []int64
	for _, id := range ids {
		r, exists := rows[id]
		switch {
		case !exists:
			out.Blocked = append(out.Blocked, Blocked{ID: id,
				Reason: "NOT_FOUND", Detail: "任务不存在"})
		case !u.IsAdmin && r.owner != u.ID:
			// BR-174，修 D-113
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "NOT_OWNER", Detail: "只能操作自己创建的任务"})
		case !typeOK[r.taskType]:
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "BAD_TYPE", Detail: fmt.Sprintf("任务类型 %d 不支持启停", r.taskType)})
		case r.channel != 0:
			// 旧版所有启停语句都带 channel = 0，保留
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "HAS_CHANNEL", Detail: "编码通道非 0 的任务不从这里启停"})
		case action == ActionStart && r.projectState != StateEnabled:
			// BR-175，修 D-114
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "DISABLED", Detail: "方案已停用，请先启用后再启动"})
		case action == ActionStart && r.mediaCount == 0:
			// BR-176，修 D-115
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "NO_MEDIA", Detail: "任务没有媒体，启动后后台会空转"})
		case action == ActionStart && r.termCount == 0:
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "NO_TERMINAL", Detail: "任务没有终端，启动后没有设备会播放"})
		default:
			doable = append(doable, id)
		}
	}
	if len(doable) == 0 {
		return out, nil
	}

	if err := s.applyState(ctx, action, doable); err != nil {
		return nil, err
	}
	out.Succeeded = doable

	// 通知必须在数据库改完之后发。旧版那句 exit 把日志吞了（D-117），
	// 这里没有任何提前返回，通知与后续处理都跑得到。
	switch action {
	case ActionStart:
		n.TaskStarted(ctx, doable)
	case ActionStop:
		n.TaskChanged(ctx, notify.TaskStop, doable)
	case ActionPause:
		n.TaskChanged(ctx, notify.TaskPause, doable)
	case ActionResume:
		n.TaskChanged(ctx, notify.TaskResume, doable)
	}
	out.Notified = true
	return out, nil
}

// applyState 写状态。暂停与恢复不落库 —— 与旧版一致，
// 这两个动作纯粹是发给后台服务的实时指令，task.state 由后台自己维护。
func (s *Service) applyState(ctx context.Context, action Action, ids []int64) error {
	var state int
	switch action {
	case ActionStart:
		state = 3
	case ActionStop:
		state = 2
	default:
		return nil
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	ph, args := placeholders(ids)

	// 主任务
	mainArgs := append([]interface{}{state}, args...)
	if _, err := tx.ExecContext(ctx,
		`UPDATE task SET state = ? WHERE taskid IN (`+ph+`) AND channel = 0`,
		mainArgs...); err != nil {
		return fmt.Errorf("更新任务状态: %w", err)
	}

	// 功放子任务随主任务同步（BR-173）。旧版这两条 UPDATE 之间没有事务，
	// 第二条失败就会留下主子状态不一致的任务。
	subArgs := append(append([]interface{}{state}, args...), TypePowerAmp)
	if _, err := tx.ExecContext(ctx,
		`UPDATE task SET state = ? WHERE sec_task_id IN (`+ph+`) AND tasktype = ? AND channel = 0`,
		subArgs...); err != nil {
		return fmt.Errorf("更新功放子任务状态: %w", err)
	}

	return tx.Commit()
}

// SetProjectState 启用 / 停用方案（projectstate）。
//
// 与执行状态（state）是两回事：方案停用后任务不再被调度，也不允许启动。
//
// # ⚠ projectstate 的取值与列注释相反：0 = 启用，1 = 停用
//
// `audioserver.sql` 里这一列的注释写着「方案是否启用，1启用，0停用」，
// **注释是错的**。四条互相独立的证据都指向 0 = 启用：
//
//   - do.php `enableTask()` 写 0，`disableTask()` 写 1
//   - do.php `start_file_task_msg()`（启动任务）也写 0
//   - `Browse_active_task.php`（浏览生效中的任务）过滤 `projectstate = 0`，
//     `androidfilemanage.php` 同样是 `projectstate = 0`
//   - 模板 `BellManager/bellManager_form.html`：`projectstate == 0` 渲染
//     `Enabled`，`== 1` 渲染 `Disenabled`
//
// 旧版新建任务时 INSERT 的也是 `projectstate = '0'`，即建出来就是启用的。
// 因此这两个函数**没有写反**，按列注释去「修正」它反而会把语义搞坏。
//
// 旧版这两个函数的其余行为予以保留：
//   - 同时把 state 与 offlinestate 复位为 0（方案开关等于回到「准备」态）
//   - 主任务按 sec_task_id = 0 限定，子任务按 sec_task_id IN (ids) 一并处理
func (s *Service) SetProjectState(ctx context.Context, u *auth.User, ids []int64, enable bool) (*ControlResult, error) {
	out := &ControlResult{Succeeded: []int64{}, Blocked: []Blocked{}}
	if len(ids) == 0 {
		return out, nil
	}
	rows, err := s.loadControlRows(ctx, ids)
	if err != nil {
		return nil, err
	}
	var doable []int64
	for _, id := range ids {
		r, exists := rows[id]
		switch {
		case !exists:
			out.Blocked = append(out.Blocked, Blocked{ID: id,
				Reason: "NOT_FOUND", Detail: "任务不存在"})
		case !u.IsAdmin && r.owner != u.ID:
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "NOT_OWNER", Detail: "只能操作自己创建的任务"})
		default:
			doable = append(doable, id)
		}
	}
	if len(doable) == 0 {
		return out, nil
	}

	v := StateDisabled
	if enable {
		v = StateEnabled
	}
	ph, args := placeholders(doable)

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 主任务与子任务必须一起改，否则会出现「主任务停用、功放子任务还启用着」的组合
	for _, stmt := range []string{
		`UPDATE task SET projectstate = ?, state = 0, offlinestate = 0
		 WHERE taskid IN (` + ph + `) AND sec_task_id = 0`,
		`UPDATE task SET projectstate = ?, state = 0, offlinestate = 0
		 WHERE sec_task_id IN (` + ph + `)`,
	} {
		if _, err := tx.ExecContext(ctx, stmt, append([]interface{}{v}, args...)...); err != nil {
			return nil, fmt.Errorf("更新方案状态: %w", err)
		}
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	out.Succeeded = doable
	return out, nil
}

// ==================================================================
//
//	紧急任务（:80 的「紧急设置 / 取消紧急设置」）
//
// ==================================================================
//
// 「紧急任务」在这套系统里不是一个开关列，而是**换任务类型**：
//
//	紧急设置    UPDATE task SET tasktype = 7, offlinestate = 0 WHERE taskid = ?
//	取消紧急    UPDATE task SET tasktype = 2, offlinestate = 0 WHERE tasktype = 7
//
// 依据是旧版 do.php 的 emergency_setting() / emergency_canceling()。
// 这也解释了为什么 tasktype 2 和 7 在界面上都叫「文件广播」——
// 7 就是那条被标成紧急的文件广播任务。
//
// ⚠ 全系统同时只能有一条紧急任务。旧版是先 `SELECT * FROM task WHERE tasktype = 7`，
//
//	有就 alert「已有紧急任务,请取消已有紧急任务」并退回。新版把它做成
//	一条带条件的 UPDATE + 明确的错误信息，顺带说清是哪条任务占着。
//
// TypeEmergency 就是 TypeFileAlt（7）—— 换个名字，是为了在这一段里
// 让「7 = 被标成紧急的那条文件广播」这层含义写在脸上。
const TypeEmergency = TypeFileAlt

// EmergencyInfo 说明当前有没有紧急任务、是哪一条。
type EmergencyInfo struct {
	TaskID   int64  `json:"taskId"`
	TaskName string `json:"taskName"`
	Exists   bool   `json:"exists"`
}

// CurrentEmergency 查当前的紧急任务。没有时 Exists = false。
func (s *Service) CurrentEmergency(ctx context.Context) (*EmergencyInfo, error) {
	out := &EmergencyInfo{}
	err := s.db.QueryRowContext(ctx,
		`SELECT taskid, COALESCE(taskname,'') FROM task WHERE tasktype = ? LIMIT 1`,
		TypeEmergency).Scan(&out.TaskID, &out.TaskName)
	if errors.Is(err, sql.ErrNoRows) {
		return out, nil
	}
	if err != nil {
		return nil, fmt.Errorf("查询紧急任务: %w", err)
	}
	out.Exists = true
	return out, nil
}

// SetEmergency 把一条文件广播任务标成紧急任务。
func (s *Service) SetEmergency(ctx context.Context, u *auth.User, id int64) (*EmergencyInfo, error) {
	rows, err := s.loadControlRows(ctx, []int64{id})
	if err != nil {
		return nil, err
	}
	r, ok := rows[id]
	if !ok {
		return nil, fmt.Errorf("任务不存在")
	}
	if !u.IsAdmin && r.owner != u.ID {
		return nil, fmt.Errorf("只能操作自己创建的任务")
	}

	cur, err := s.CurrentEmergency(ctx)
	if err != nil {
		return nil, err
	}
	if cur.Exists {
		if cur.TaskID == id {
			return cur, nil // 已经是紧急任务，幂等返回
		}
		return nil, fmt.Errorf("已有紧急任务「%s」，请先取消它 —— 全系统同时只能有一条", cur.TaskName)
	}

	// 限定 tasktype = 2：只有普通文件广播能被标成紧急。
	// 作息条目、采播、功放这些换成 7 会让它们从各自的列表里消失，且后台按文件广播去执行。
	res, err := s.db.ExecContext(ctx,
		`UPDATE task SET tasktype = ?, offlinestate = 0 WHERE taskid = ? AND tasktype = ?`,
		TypeEmergency, id, TypeFile)
	if err != nil {
		return nil, fmt.Errorf("设置紧急任务: %w", err)
	}
	if n, _ := res.RowsAffected(); n == 0 {
		return nil, fmt.Errorf("只有文件广播任务能设为紧急任务")
	}
	return &EmergencyInfo{TaskID: id, TaskName: r.name, Exists: true}, nil
}

// CancelEmergency 取消紧急任务，把它变回普通文件广播。
func (s *Service) CancelEmergency(ctx context.Context) (*EmergencyInfo, error) {
	cur, err := s.CurrentEmergency(ctx)
	if err != nil {
		return nil, err
	}
	if !cur.Exists {
		return nil, fmt.Errorf("当前没有紧急任务")
	}
	if _, err := s.db.ExecContext(ctx,
		`UPDATE task SET tasktype = ?, offlinestate = 0 WHERE tasktype = ?`,
		TypeFile, TypeEmergency); err != nil {
		return nil, fmt.Errorf("取消紧急任务: %w", err)
	}
	return &EmergencyInfo{TaskID: cur.TaskID, TaskName: cur.TaskName, Exists: false}, nil
}

// SetVolume 是 :80 的「设置音量」按钮：整批改 task.defaultvolume。
//
// 子任务（功放/LED）也一起改：它们的音量跟主任务是一套，
// 只改主任务会出现「主任务 80、功放子任务还是 60」的组合。
func (s *Service) SetVolume(ctx context.Context, u *auth.User, ids []int64, volume int) (*ControlResult, error) {
	out := &ControlResult{Succeeded: []int64{}, Blocked: []Blocked{}}
	if len(ids) == 0 {
		return nil, fmt.Errorf("请先勾选任务")
	}
	if volume < 0 || volume > 100 {
		return nil, fmt.Errorf("音量只能是 0 ~ 100")
	}
	rows, err := s.loadControlRows(ctx, ids)
	if err != nil {
		return nil, err
	}
	var doable []int64
	for _, id := range ids {
		r, exists := rows[id]
		switch {
		case !exists:
			out.Blocked = append(out.Blocked, Blocked{ID: id,
				Reason: "NOT_FOUND", Detail: "任务不存在"})
		case !u.IsAdmin && r.owner != u.ID:
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "NOT_OWNER", Detail: "只能操作自己创建的任务"})
		default:
			doable = append(doable, id)
		}
	}
	if len(doable) == 0 {
		return out, nil
	}
	ph, args := placeholders(doable)
	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()
	for _, stmt := range []string{
		`UPDATE task SET defaultvolume = ? WHERE taskid IN (` + ph + `) AND sec_task_id = 0`,
		`UPDATE task SET defaultvolume = ? WHERE sec_task_id IN (` + ph + `)`,
	} {
		if _, err := tx.ExecContext(ctx, stmt, append([]interface{}{volume}, args...)...); err != nil {
			return nil, fmt.Errorf("设置任务音量: %w", err)
		}
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	out.Succeeded = doable
	return out, nil
}
