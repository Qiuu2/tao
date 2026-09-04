package offline

import (
	"context"
	"fmt"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/store"
)

// 云广播终端 与 任务传送 两个页面的查询。
//
// 这两页和已有的「离线传输」页看的是同一批表，只是切入角度不同：
//
//	离线传输   （已有）  按**下发动作**组织：选媒体/任务 → 选终端 → 下发
//	云广播终端 （本文件）按**终端**组织：这台终端里装了什么、还剩多少容量
//	任务传送   （本文件）按**离线任务副本**组织：offlinetask 里有哪些任务、发给了谁
//
// # 「云广播终端」是哪些终端
//
// 旧版 offlinemusicmanager.php 的条件是 `WHERE totalcapacity != 0` ——
// 也就是**有存储容量的终端**才算云广播终端。容量为 0 的设备存不下东西，
// 下发给它没有意义。这个条件是这一页唯一的筛选依据，没有专门的类型标志位。
//
// 顺带说明 `terminaltype.id = 23` 那个叫「离线终端」的型号：现网一台都没有，
// 而且旧版从来不按它筛选。别拿它当判据。
//
// # ⚠ resetcapacity 是「剩余」还是「已用」
//
// 列名 `resetcapacity` 大概率是 `restcapacity`（剩余）的拼写错误。
// 现网所有终端这两列都是 0，无从佐证。这里**原样输出两个数字、不做减法**，
// 也不在界面上算百分比 —— 算错了会让运维以为终端满了或空着。

// CloudTerminal 是云广播终端列表的一行。
type CloudTerminal struct {
	ID           int64  `json:"id"`
	TerminalName string `json:"terminalname"`
	TypeName     string `json:"typeName"`
	IP           string `json:"ip"`
	NetState     int    `json:"netstate"`
	// TaskState / DeviceState 是 :80 云广播终端列表里的「任务状态 / 设备状态」两列。
	TaskState   int    `json:"taskstate"`
	DeviceState int    `json:"devicestate"`
	GroupID     int64  `json:"groupId"`
	GroupName   string `json:"groupName"`
	// TotalCapacity / ResetCapacity 原样来自 terminal 表，不做换算。
	TotalCapacity int64 `json:"totalcapacity"`
	ResetCapacity int64 `json:"resetcapacity"`
	// MediaCount / TaskCount 是这台终端上已经建立下发关系的条数。
	MediaCount int `json:"mediaCount"`
	TaskCount  int `json:"taskCount"`
	// Transferring 表示这台终端上还有处于「传输中」状态的条目。
	Transferring int `json:"transferring"`
}

type CloudQuery struct {
	Keyword string
	Pager   store.Pager
}

type CloudResult struct {
	Items     []CloudTerminal
	Total     int64
	ScopeNote string
}

// ListCloudTerminals 列出云广播终端。
//
// 可见范围与终端模块同一套：管理员看全部，普通用户按 userterminal 收敛。
// 旧版 offlinemusicmanager.php 的非管理员分支是拿**用户名**去绕一层子查询
// （`WHERE book_admin.username = '$user_name'`），这里直接绑会话里的 userId。
func (s *Service) ListCloudTerminals(ctx context.Context, u *auth.User, q CloudQuery) (*CloudResult, error) {
	cond := &store.Cond{}
	// 「有存储容量」是云广播终端的唯一判据，来自旧版写死的 totalcapacity != 0
	cond.Add("COALESCE(t.totalcapacity,0) <> 0")
	if !u.IsAdmin {
		cond.Add(`t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)`, u.ID)
	}
	if q.Keyword != "" {
		kw := store.EscapeLike(q.Keyword)
		cond.Add(`(t.terminalname LIKE ? ESCAPE '\\' OR t.ip LIKE ? ESCAPE '\\')`, kw, kw)
	}
	where := cond.Where()
	args := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM terminal t"+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计云广播终端: %w", err)
	}

	listArgs := append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, `
		SELECT t.id, COALESCE(t.terminalname,''), COALESCE(tt.name,''), COALESCE(t.ip,''),
		       COALESCE(t.netstate,0), COALESCE(t.taskstate,0), COALESCE(t.devicestate,0),
		       COALESCE(t.groupid,0), COALESCE(sp.name,''),
		       COALESCE(t.totalcapacity,0), COALESCE(t.resetcapacity,0),
		       (SELECT COUNT(*) FROM offlinemediaofterminal m WHERE m.terminalid = t.id),
		       (SELECT COUNT(*) FROM offlinetaskofterminal k WHERE k.terminalid = t.id),
		       (SELECT COUNT(*) FROM offlinemediaofterminal m
		         WHERE m.terminalid = t.id AND m.offlinestate IN (6,7,9,10))
		       + (SELECT COUNT(*) FROM offlinetaskofterminal k
		           WHERE k.terminalid = t.id AND k.offlinestate IN (6,7,9,10))
		FROM terminal t
		LEFT JOIN terminaltype tt ON tt.id = t.typeid
		LEFT JOIN serverplaystream sp ON sp.streamid = t.groupid`+where+`
		ORDER BY t.netstate DESC, t.id LIMIT ? OFFSET ?`, listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询云广播终端: %w", err)
	}
	defer rs.Close()

	items := make([]CloudTerminal, 0, q.Pager.PageSize)
	for rs.Next() {
		var c CloudTerminal
		if err := rs.Scan(&c.ID, &c.TerminalName, &c.TypeName, &c.IP,
			&c.NetState, &c.TaskState, &c.DeviceState, &c.GroupID, &c.GroupName,
			&c.TotalCapacity, &c.ResetCapacity,
			&c.MediaCount, &c.TaskCount, &c.Transferring); err != nil {
			return nil, fmt.Errorf("扫描云广播终端行: %w", err)
		}
		items = append(items, c)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}

	res := &CloudResult{Items: items, Total: total}
	if !u.IsAdmin {
		res.ScopeNote = "仅显示绑定给我的终端"
	}
	return res, nil
}

// CloudItem 是某台终端上的一条离线内容（媒体或任务）。
type CloudItem struct {
	Kind      string `json:"kind"` // media | task
	ID        int64  `json:"id"`
	Name      string `json:"name"`
	Size      int64  `json:"size"`
	TypeID    string `json:"typeid"`
	TaskID    int64  `json:"taskId"`
	State     int    `json:"offlinestate"`
	StateText string `json:"stateText"`
	// Missing 表示这条离线记录指向的媒体/任务副本已经不在了。
	Missing bool `json:"missing"`
}

// CloudInventory 列出一台终端上的全部离线内容。
//
// 旧版 displayofflinemedia.php 只查 `taskid = '0'` 的媒体
// （也就是「独立下发的音乐」，不含跟着任务走的那些），
// 这里两类都列出来并用 taskId 区分 —— 运维要排查「这台终端里到底有什么」，
// 藏掉一半没有道理。
func (s *Service) CloudInventory(ctx context.Context, u *auth.User, terminalID int64) ([]CloudItem, error) {
	if err := s.assertTerminalVisible(ctx, u, terminalID); err != nil {
		return nil, err
	}

	out := []CloudItem{}

	// LEFT JOIN：offlinemedia 副本可能已经被清掉，而关系行还在
	mrs, err := s.db.QueryContext(ctx, `
		SELECT m.mediaid, COALESCE(om.name,''), COALESCE(om.size,0), COALESCE(om.typeid,''),
		       COALESCE(m.taskid,0), COALESCE(m.offlinestate,0), om.id IS NOT NULL
		FROM offlinemediaofterminal m
		LEFT JOIN offlinemedia om ON om.id = m.mediaid
		WHERE m.terminalid = ?
		ORDER BY m.taskid, m.sort, m.mediaid`, terminalID)
	if err != nil {
		return nil, fmt.Errorf("查询终端离线媒体: %w", err)
	}
	for mrs.Next() {
		var it CloudItem
		var exists bool
		it.Kind = "media"
		if err := mrs.Scan(&it.ID, &it.Name, &it.Size, &it.TypeID,
			&it.TaskID, &it.State, &exists); err != nil {
			mrs.Close()
			return nil, err
		}
		it.Missing = !exists
		if it.Missing {
			it.Name = "(离线媒体副本已不存在)"
		}
		it.StateText = StateText[State(it.State)]
		out = append(out, it)
	}
	mrs.Close()
	if err := mrs.Err(); err != nil {
		return nil, err
	}

	trs, err := s.db.QueryContext(ctx, `
		SELECT k.taskid, COALESCE(ot.taskname,''), COALESCE(k.offlinestate,0), ot.taskid IS NOT NULL
		FROM offlinetaskofterminal k
		LEFT JOIN offlinetask ot ON ot.taskid = k.taskid
		WHERE k.terminalid = ?
		ORDER BY k.taskid`, terminalID)
	if err != nil {
		return nil, fmt.Errorf("查询终端离线任务: %w", err)
	}
	defer trs.Close()
	for trs.Next() {
		var it CloudItem
		var exists bool
		it.Kind = "task"
		if err := trs.Scan(&it.ID, &it.Name, &it.State, &exists); err != nil {
			return nil, err
		}
		it.TaskID = it.ID
		it.Missing = !exists
		if it.Missing {
			it.Name = "(离线任务副本已不存在)"
		}
		it.StateText = StateText[State(it.State)]
		out = append(out, it)
	}
	return out, trs.Err()
}

// assertTerminalVisible 普通用户只能看绑定给自己的终端。
func (s *Service) assertTerminalVisible(ctx context.Context, u *auth.User, terminalID int64) error {
	if u.IsAdmin {
		var n int
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM terminal WHERE id = ?`, terminalID).Scan(&n); err != nil {
			return fmt.Errorf("校验终端: %w", err)
		}
		if n == 0 {
			return fmt.Errorf("终端不存在")
		}
		return nil
	}
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM userterminal WHERE terminalid = ? AND userid = ?`,
		terminalID, u.ID).Scan(&n); err != nil {
		return fmt.Errorf("校验终端归属: %w", err)
	}
	if n == 0 {
		return fmt.Errorf("终端不存在或未绑定给你")
	}
	return nil
}

// ==================================================================
//              云广播终端页上的整批动作（:80 的那排按钮）
// ==================================================================
//
// :80 云广播终端页的按钮是：空闲传输 / 立即传输 / 停止传输 / 全部清除 /
// 同步时间 / 清除空闲媒体。除了「同步时间」是给终端下指令（走终端模块），
// 其余五个都是**对这台终端上已经建立的下发关系改状态**，
// 不需要再挑一遍媒体 —— 这也是这一页与「音乐传输」页的分工：
// 音乐传输负责「发什么给谁」，这一页负责「已经发过的这批，接下来怎么办」。
//
// ⚠ 一律只改 offlinemediaofterminal / offlinetaskofterminal 的 offlinestate，
//    不删行、不动 offlinemedia / offlinetask 副本本身：
//    真正的删除由后台广播服务看到「删除」状态后自己完成，
//    Web 抢着删会让后台永远收不到删除指令，终端上的文件就留成了孤儿。

// CloudAction 是云广播终端页那几个整批动作。
type CloudAction string

const (
	CloudIdle      CloudAction = "idle"           // 空闲传输
	CloudImmediate CloudAction = "immediate"      // 立即传输
	CloudStop      CloudAction = "stop"           // 停止传输
	CloudClearAll  CloudAction = "clearAll"       // 全部清除（立即删除，媒体 + 任务）
	CloudClearIdle CloudAction = "clearIdleMedia" // 清除空闲媒体（空闲删除，只动媒体）
)

// CloudBulkResult 逐项回报改了多少行，界面照实说，不做「成功」二字了事。
type CloudBulkResult struct {
	Action        string `json:"action"`
	ActionText    string `json:"actionText"`
	TerminalCount int    `json:"terminalCount"`
	MediaRows     int64  `json:"mediaRows"`
	TaskRows      int64  `json:"taskRows"`
	StateText     string `json:"stateText"`
}

var cloudActionText = map[CloudAction]string{
	CloudIdle: "空闲传输", CloudImmediate: "立即传输", CloudStop: "停止传输",
	CloudClearAll: "全部清除", CloudClearIdle: "清除空闲媒体",
}

// CloudBulk 对选中终端上**已有的**离线条目整批改状态。
func (s *Service) CloudBulk(ctx context.Context, u *auth.User,
	terminalIDs []int64, action CloudAction) (*CloudBulkResult, error) {

	termIDs := dedup(terminalIDs)
	if len(termIDs) == 0 {
		return nil, fmt.Errorf("请先勾选终端")
	}
	if len(termIDs) > 3000 {
		return nil, fmt.Errorf("单次最多 3000 台终端")
	}
	text, ok := cloudActionText[action]
	if !ok {
		return nil, fmt.Errorf("不认识的动作：%s", action)
	}
	if err := s.assertTerminals(ctx, u, termIDs); err != nil {
		return nil, err
	}

	// 每个动作要写进 offlinestate 的目标值，以及动不动任务那张表
	var mediaState, taskState State
	touchTask := true
	switch action {
	case CloudIdle:
		mediaState, taskState = StateIdle, StateIdle
	case CloudImmediate:
		mediaState, taskState = StateImmediate, StateImmediate
	case CloudStop:
		mediaState, taskState = StateStop, StateStop
	case CloudClearAll:
		mediaState, taskState = StateDeleteNow, StateDeleteNow
	case CloudClearIdle:
		// 「清除空闲媒体」按字面只处理媒体，任务副本不动
		mediaState, touchTask = StateDeleteIdle, false
	}

	tph, targs := placeholders(termIDs)

	out := &CloudBulkResult{
		Action: string(action), ActionText: text,
		TerminalCount: len(termIDs), StateText: Text(int(mediaState)),
	}

	res, err := s.db.ExecContext(ctx,
		`UPDATE offlinemediaofterminal SET offlinestate = ? WHERE terminalid IN (`+tph+`)`,
		append([]interface{}{int(mediaState)}, targs...)...)
	if err != nil {
		return nil, fmt.Errorf("%s（媒体）: %w", text, err)
	}
	out.MediaRows, _ = res.RowsAffected()

	if touchTask {
		res, err := s.db.ExecContext(ctx,
			`UPDATE offlinetaskofterminal SET offlinestate = ? WHERE terminalid IN (`+tph+`)`,
			append([]interface{}{int(taskState)}, targs...)...)
		if err != nil {
			return nil, fmt.Errorf("%s（任务）: %w", text, err)
		}
		out.TaskRows, _ = res.RowsAffected()
	}

	if out.MediaRows == 0 && out.TaskRows == 0 {
		return nil, fmt.Errorf("选中的终端上没有任何离线内容，%s 无事可做", text)
	}
	return out, nil
}

// ==================================================================
//                          任务传送
// ==================================================================

// TransferTask 是「任务传送」列表的一行：一条已经复制到 offlinetask 的任务副本。
type TransferTask struct {
	TaskID    int64  `json:"taskId"`
	TaskName  string `json:"taskName"`
	TaskType  int    `json:"tasktype"`
	TypeText  string `json:"typeText"`
	Info      string `json:"info"`
	StartDate string `json:"startdate"`
	EndDate   string `json:"enddate"`
	PlayTime  string `json:"playtime"`
	ExeModel  string `json:"exemodel"`
	// CycleText 是 exemodel 翻出来的「播放周期」，:80 那一列就是它。
	CycleText string `json:"cycleText"`
	// LengthText 是播放时长的人话。
	TimeLength     int    `json:"timelength"`
	TimeLengthType int    `json:"timelengthtype"`
	LengthText     string `json:"lengthText"`
	// ProjectState / ProjectText 是任务自身的启用状态（⚠ 0 = 启用）。
	ProjectState int    `json:"projectstate"`
	ProjectText  string `json:"projectText"`
	Volume       int    `json:"defaultvolume"`
	State        int    `json:"offlinestate"`
	StateText    string `json:"stateText"`
	// TerminalCount 是这条副本发给了多少台终端。
	TerminalCount int `json:"terminalCount"`
	// DoneCount 是其中已完成的台数。
	DoneCount int `json:"doneCount"`
	// SourceMissing 表示原任务（task 表）已经被删了，只剩离线副本。
	SourceMissing bool `json:"sourceMissing"`
}

// exemodel 是周日打头的 7 位掩码（第 1 位 = 周日），标签顺序要跟它对齐。
var weekNames = [7]string{"日", "一", "二", "三", "四", "五", "六"}

// cycleText / lengthText 与 typedtask 包里那两份同口径，
// 只是离线副本这边独立取一份，免得两个包互相依赖。
func cycleText(mask string) string {
	if len(mask) != 7 {
		return "—"
	}
	switch mask {
	case "0000000":
		return "手动"
	case "1111111":
		return "每天"
	}
	var on []string
	for i := 0; i < 7; i++ {
		if mask[i] == '1' {
			on = append(on, weekNames[i])
		}
	}
	if len(on) == 0 {
		return "手动"
	}
	return "周" + strings.Join(on, "、")
}

func lengthText(t, v int) string {
	if v <= 0 {
		return "—"
	}
	if t == 1 {
		h, m, s := v/3600, (v%3600)/60, v%60
		switch {
		case h > 0:
			return fmt.Sprintf("%d小时%d分%d秒", h, m, s)
		case m > 0:
			return fmt.Sprintf("%d分%d秒", m, s)
		default:
			return fmt.Sprintf("%d秒", s)
		}
	}
	return fmt.Sprintf("循环 %d 次", v)
}

// typeText 与 enable 包里的那份保持同样的取值来源（task.tasktype 列注释 + 各页面反推）。
func typeText(t int) string {
	switch t {
	case 1:
		return "作息条目"
	case 2, 7:
		return "文件广播"
	case 3:
		return "采播"
	case 4:
		return "电话采播"
	case 5:
		return "终端功放"
	case 9:
		return "电源子任务"
	case 13:
		return "系统任务"
	case 15, 17, 19:
		return "文字语音"
	case 24, 30:
		return "LED 播放"
	}
	return fmt.Sprintf("类型 %d", t)
}

type TransferQuery struct {
	Keyword string
	// TerminalID > 0 时只看下发到这台终端的任务副本。
	TerminalID int64
	// Kind 对应 :80 任务传送页的两个页签：
	//   "bell" → 作息方案（tasktype 1）
	//   "file" → 文件广播（tasktype 2、7）
	// 空串表示不筛选（我们比 :80 多给的「全部」）。
	Kind  string
	Pager store.Pager
}

// transferKindCond 把页签翻成 WHERE 片段。
// 用写死的类型号是有意的：这几个号在 task.tasktype 的列注释里就定死了，
// 拿名字猜反而会错（同名类型有好几个号）。
func transferKindCond(kind string) string {
	switch kind {
	case "bell":
		return "COALESCE(ot.tasktype,0) = 1"
	case "file":
		return "COALESCE(ot.tasktype,0) IN (2,7)"
	default:
		return ""
	}
}

type TransferResult struct {
	Items []TransferTask
	Total int64
}

// ListTransferTasks 列出离线任务副本。
//
// 旧版 displayofflinetask.php 限定 `tasktype IN (1,2)` 且必须先选一台终端。
// 这里放开：不选终端就列全部副本，选了就只看那台的。
// 类型也不再限定 —— 限定 (1,2) 会让功放/语音/LED 的离线副本凭空消失，
// 而它们确实能被下发（offlinetask 的列结构和 task 完全一样）。
func (s *Service) ListTransferTasks(ctx context.Context, u *auth.User, q TransferQuery) (*TransferResult, error) {
	cond := &store.Cond{}
	if q.TerminalID > 0 {
		if err := s.assertTerminalVisible(ctx, u, q.TerminalID); err != nil {
			return nil, err
		}
		cond.Add(`ot.taskid IN (SELECT taskid FROM offlinetaskofterminal WHERE terminalid = ?)`, q.TerminalID)
	} else if !u.IsAdmin {
		// 不指定终端时，普通用户只看下发到自己终端上的副本
		cond.Add(`ot.taskid IN (
			SELECT k.taskid FROM offlinetaskofterminal k
			 WHERE k.terminalid IN (SELECT terminalid FROM userterminal WHERE userid = ?))`, u.ID)
	}
	if q.Keyword != "" {
		cond.Add(`ot.taskname LIKE ? ESCAPE '\\'`, store.EscapeLike(q.Keyword))
	}
	if c := transferKindCond(q.Kind); c != "" {
		cond.Add(c)
	}
	where := cond.Where()
	args := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM offlinetask ot"+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计离线任务副本: %w", err)
	}

	listArgs := append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, `
		SELECT ot.taskid, COALESCE(ot.taskname,''), COALESCE(ot.tasktype,0), COALESCE(ot.info,''),
		       COALESCE(CAST(ot.startdate AS CHAR),''), COALESCE(CAST(ot.enddate AS CHAR),''),
		       COALESCE(CAST(ot.playtime AS CHAR),''), COALESCE(ot.exemodel,''),
		       COALESCE(ot.timelength,0), COALESCE(ot.timelengthtype,0), COALESCE(ot.projectstate,0),
		       COALESCE(ot.defaultvolume,0), COALESCE(ot.offlinestate,0),
		       (SELECT COUNT(*) FROM offlinetaskofterminal k WHERE k.taskid = ot.taskid),
		       (SELECT COUNT(*) FROM offlinetaskofterminal k
		         WHERE k.taskid = ot.taskid AND k.offlinestate = 3),
		       (SELECT COUNT(*) FROM task t WHERE t.taskid = ot.taskid) = 0
		FROM offlinetask ot`+where+`
		ORDER BY ot.startdate DESC, ot.playtime DESC, ot.taskid DESC
		LIMIT ? OFFSET ?`, listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询离线任务副本: %w", err)
	}
	defer rs.Close()

	items := make([]TransferTask, 0, q.Pager.PageSize)
	for rs.Next() {
		var t TransferTask
		if err := rs.Scan(&t.TaskID, &t.TaskName, &t.TaskType, &t.Info,
			&t.StartDate, &t.EndDate, &t.PlayTime, &t.ExeModel,
			&t.TimeLength, &t.TimeLengthType, &t.ProjectState,
			&t.Volume, &t.State, &t.TerminalCount, &t.DoneCount, &t.SourceMissing); err != nil {
			return nil, fmt.Errorf("扫描离线任务副本行: %w", err)
		}
		t.TypeText = typeText(t.TaskType)
		t.StateText = StateText[State(t.State)]
		t.CycleText = cycleText(t.ExeModel)
		t.LengthText = lengthText(t.TimeLengthType, t.TimeLength)
		// ⚠ offlinetask.projectstate 与 task 同源：0 = 启用、1 = 停用
		if t.ProjectState == 0 {
			t.ProjectText = "启用"
		} else {
			t.ProjectText = "停用"
		}
		items = append(items, t)
	}
	return &TransferResult{Items: items, Total: total}, rs.Err()
}

// TransferMediaItem 是某条离线任务副本里带的一个媒体。
//
// 关系存在 offlinemediaofterminal 里（taskid = 这条任务），
// 同一个媒体会因为发给了多台终端而出现多行，所以这里按 mediaid 归并，
// 顺带统计它发给了几台、其中几台已经完成。
type TransferMediaItem struct {
	MediaID   int64  `json:"mediaId"`
	Name      string `json:"name"`
	Size      int64  `json:"size"`
	TypeID    string `json:"typeid"`
	Sort      int    `json:"sort"`
	Terminals int    `json:"terminals"`
	Done      int    `json:"done"`
	Missing   bool   `json:"missing"`
}

// TransferMedia 列出一条离线任务副本里的媒体清单（:80 行内的「媒体」链接）。
func (s *Service) TransferMedia(ctx context.Context, u *auth.User, taskID int64) ([]TransferMediaItem, error) {
	cond := &store.Cond{}
	cond.Add("m.taskid = ?", taskID)
	if !u.IsAdmin {
		cond.Add(`m.terminalid IN (SELECT terminalid FROM userterminal WHERE userid = ?)`, u.ID)
	}
	// LEFT JOIN：离线媒体副本可能已经被清掉，关系行还在
	rs, err := s.db.QueryContext(ctx, `
		SELECT m.mediaid, COALESCE(om.name,''), COALESCE(om.size,0), COALESCE(om.typeid,''),
		       MIN(COALESCE(m.sort,0)), COUNT(*),
		       SUM(CASE WHEN m.offlinestate IN (3,8) THEN 1 ELSE 0 END),
		       MAX(om.id IS NOT NULL)
		FROM offlinemediaofterminal m
		LEFT JOIN offlinemedia om ON om.id = m.mediaid`+cond.Where()+`
		GROUP BY m.mediaid, om.name, om.size, om.typeid
		ORDER BY MIN(COALESCE(m.sort,0)), m.mediaid`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询离线任务媒体: %w", err)
	}
	defer rs.Close()
	out := []TransferMediaItem{}
	for rs.Next() {
		var it TransferMediaItem
		var exists int
		if err := rs.Scan(&it.MediaID, &it.Name, &it.Size, &it.TypeID,
			&it.Sort, &it.Terminals, &it.Done, &exists); err != nil {
			return nil, err
		}
		it.Missing = exists == 0
		if it.Missing {
			it.Name = "(离线媒体副本已不存在)"
		}
		out = append(out, it)
	}
	return out, rs.Err()
}

// TransferBulk 是任务传送页的「空闲传输 / 立即传输」两个按钮。
//
// 与云广播终端页那组按钮同一个思路：只改 offlinetaskofterminal 的状态，
// 真正的传输由后台广播服务执行。这里按**任务副本**圈定范围，
// 那边按**终端**圈定范围。
func (s *Service) TransferBulk(ctx context.Context, u *auth.User,
	taskIDs []int64, action CloudAction) (*CloudBulkResult, error) {

	ids := dedup(taskIDs)
	if len(ids) == 0 {
		return nil, fmt.Errorf("请先勾选任务")
	}
	if len(ids) > 1000 {
		return nil, fmt.Errorf("单次最多 1000 条任务")
	}
	// 这一页只放开传输相关的动作，清除类动作留在云广播终端页，
	// 免得在「按任务」的视角下误删别的终端上的东西。
	if action != CloudIdle && action != CloudImmediate && action != CloudStop {
		return nil, fmt.Errorf("任务传送页只支持空闲传输 / 立即传输 / 停止传输")
	}
	var state State
	switch action {
	case CloudIdle:
		state = StateIdle
	case CloudImmediate:
		state = StateImmediate
	default:
		state = StateStop
	}

	cond := &store.Cond{}
	cond.AddIn("k.taskid", ids)
	if !u.IsAdmin {
		cond.Add(`k.terminalid IN (SELECT terminalid FROM userterminal WHERE userid = ?)`, u.ID)
	}
	res, err := s.db.ExecContext(ctx,
		`UPDATE offlinetaskofterminal k SET k.offlinestate = ?`+cond.Where(),
		append([]interface{}{int(state)}, cond.Args()...)...)
	if err != nil {
		return nil, fmt.Errorf("%s: %w", cloudActionText[action], err)
	}
	n, _ := res.RowsAffected()
	if n == 0 {
		return nil, fmt.Errorf("选中的任务还没有下发到任何终端，%s 无事可做", cloudActionText[action])
	}
	return &CloudBulkResult{
		Action: string(action), ActionText: cloudActionText[action],
		TerminalCount: 0, TaskRows: n, StateText: Text(int(state)),
	}, nil
}

// TransferTerminal 是某条离线任务副本发给了哪台终端、进度如何。
type TransferTerminal struct {
	TerminalID   int64  `json:"terminalId"`
	TerminalName string `json:"terminalname"`
	TypeName     string `json:"typeName"`
	IP           string `json:"ip"`
	NetState     int    `json:"netstate"`
	State        int    `json:"offlinestate"`
	StateText    string `json:"stateText"`
	Area         string `json:"area"`
	Deleted      bool   `json:"deleted"`
}

// TransferDetail 列出一条离线任务副本的下发对象。
func (s *Service) TransferDetail(ctx context.Context, u *auth.User, taskID int64) ([]TransferTerminal, error) {
	cond := &store.Cond{}
	cond.Add("k.taskid = ?", taskID)
	if !u.IsAdmin {
		cond.Add(`k.terminalid IN (SELECT terminalid FROM userterminal WHERE userid = ?)`, u.ID)
	}
	rs, err := s.db.QueryContext(ctx, `
		SELECT k.terminalid, t.id IS NOT NULL, COALESCE(t.terminalname,''),
		       COALESCE(tt.name,''), COALESCE(t.ip,''), COALESCE(t.netstate,0),
		       COALESCE(k.offlinestate,0), COALESCE(k.area,'')
		FROM offlinetaskofterminal k
		LEFT JOIN terminal t ON t.id = k.terminalid
		LEFT JOIN terminaltype tt ON tt.id = t.typeid`+cond.Where()+`
		ORDER BY k.terminalid`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询离线任务下发对象: %w", err)
	}
	defer rs.Close()
	out := []TransferTerminal{}
	for rs.Next() {
		var t TransferTerminal
		var exists bool
		if err := rs.Scan(&t.TerminalID, &exists, &t.TerminalName, &t.TypeName,
			&t.IP, &t.NetState, &t.State, &t.Area); err != nil {
			return nil, err
		}
		t.Deleted = !exists
		if t.Deleted {
			t.TerminalName = "(终端已删除)"
		}
		t.StateText = StateText[State(t.State)]
		// area 列的默认值带着三层单引号（'''11111111'''），旧数据里可能就是这个样子。
		// 展示时把引号剥掉，免得界面上出现一串莫名其妙的撇号。
		t.Area = strings.Trim(t.Area, "'")
		out = append(out, t)
	}
	return out, rs.Err()
}
