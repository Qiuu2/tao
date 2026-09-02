// Package task 实现任务管理（手册业务域七，F-32 ~ F-37），以文件广播为代表。
//
// # 对旧系统的关键修复
//
//   - D-99  排序写成 ORDER BY '$_GET[searchsequence]'：既加了引号变成按常量排序，
//     又从 $_GET 读一个由 $_POST 提交的参数 —— 双重失效 → 白名单排序 + 单一参数来源
//   - D-100 分页整块被注释掉，一次渲染全部任务 → 恢复服务端分页
//   - D-101 严重 N+1：每个任务额外跑 2 条查询取媒体与终端清单，
//     100 个任务就是 201 次查询 → 两条 IN 批量查完在内存里按 taskid 分组
//   - D-102 为渲染一个下拉框把整张 media 表读进内存 → 改为按需搜索的远程下拉
//   - D-104 终端清单里写 $row1['$postion']（变量名写在引号里）→ 永远取不到值
//   - D-105 媒体清单用内连接，媒体删掉后清单项直接消失 → LEFT JOIN + 标记「已删除」
//   - D-106 tasktype IN (2,7) 硬编码散在十几个分支 → 常量化
//
// # 任务与分组树
//
// 任务分组是 filetaskfree 表，**与媒体文件夹树（filefolder）毫无关系**（BR-161）。
// task.parentid 指向 filetaskfree.id，task_user_id 指向 book_admin.id。
//
// # 主任务与子任务
//
// prepower > 0 时旧系统会额外建一条 tasktype = 9 的功放子任务，
// 由**子任务**的 sec_task_id 指回主任务（方向别搞反，现网数据核对过）。
// 子任务的终端清单是主任务的副本，状态随主任务同步变更（BR-173）。
package task

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/store"
)

var (
	ErrNotFound     = errors.New("任务不存在")
	ErrNoPermission = errors.New("无权操作该任务")
	ErrFolderDenied = errors.New("任务分组不存在或无权访问")
)

// 任务类型常量（修 D-106 的硬编码）。
const (
	TypeFile     = 2  // 文件广播
	TypeFileAlt  = 7  // 文件广播的另一种取值，现网无数据但旧查询一直带着
	TypeSchedule = 15 // 作息内的文件播放，旧启动语句把它和 2 一起处理
	TypePowerAmp = 9  // 功放子任务
	// ⚠ LED 子任务是 **30**，不是 24。
	//
	// 现网四条 LED 任务全是 tasktype = 30（70009 sec_task_id=70007「早读」、
	// 70035 sec_task_id=70033、70020 与 70024 是 sec_task_id=0 的独立 LED 任务），
	// tasktype = 24 一条都没有。以前这里写 24，后果是删除文件广播任务时
	// **找不到它的 LED 子任务**，子任务连同 media / ledsentence 一起留成孤儿（N-22）。
	// 24 作为可能存在的旧数据保留在 ledSubTypes 里一并处理。
	TypeLEDSub    = 30
	TypeLEDSubOld = 24
	DefaultFolder = 1 // filetaskfree 的 admin 默认组，禁止删除（BR-183）
)

// ledSubTypes 是查/删 LED 子任务时要覆盖的类型集合。
var ledSubTypes = []int{TypeLEDSub, TypeLEDSubOld}

// fileTypes 是「文件广播任务」的类型集合（BR-159）。
var fileTypes = []int{TypeFile, TypeFileAlt}

// AreaAll 是终端区域掩码的默认值。
//
// 注意：旧 addfileplaytask_msg 里写死的是 16 个 1，而列定义的默认值和
// 现网 terminaloftask 全部 35 行的实际取值都是 **8 个 1**。
// 以现网数据为准 —— 写 16 个 1 会让新建的行和存量数据不一致。
const AreaAll = "11111111"

// projectstate 的两个取值。
//
// ⚠ 与 `audioserver.sql` 里的列注释「1启用，0停用」**相反**，注释是错的。
// 判据见 control.go 的 SetProjectState 文档注释（四条独立证据）。
// 全模块只准通过这两个常量引用它，别再出现裸的 0 / 1。
const (
	StateEnabled  = 0 // 启用
	StateDisabled = 1 // 停用
)

type Service struct {
	db *sql.DB
}

func New(db *sql.DB) *Service { return &Service{db: db} }

// ---------- 列表 ----------

// MediaItem 是任务媒体清单的一项。
type MediaItem struct {
	MediaID int64  `json:"mediaId"`
	Name    string `json:"name"`
	Size    int64  `json:"size"`
	Sort    int    `json:"sort"`
	// Deleted 表示 media 行已不存在。旧版用内连接直接把这一项吞掉（D-105），
	// 结果是任务看起来「少了一首歌」却查不出原因。
	Deleted bool `json:"deleted"`
}

// TerminalItem 是任务终端清单的一项。
type TerminalItem struct {
	TerminalID   int64  `json:"terminalId"`
	TerminalName string `json:"terminalname"`
	TypeName     string `json:"typeName"`
	NetState     int    `json:"netstate"`
	TaskState    int    `json:"taskstate"`
	IP           string `json:"ip"`
	Volume       int    `json:"volume"`
	GroupID      int64  `json:"groupId"`
	Area         string `json:"area"`
	Deleted      bool   `json:"deleted"`
}

// Item 是任务列表行。
type Item struct {
	TaskID   int64  `json:"taskid"`
	TaskName string `json:"taskname"`
	TaskType int    `json:"tasktype"`
	// IsRandomPlay 取值反直觉：0 = 随机，1 = 顺序（BR-163），必须原样保留。
	IsRandomPlay  int    `json:"israndomplay"`
	PlayModeText  string `json:"playModeText"`
	ProjectState  int    `json:"projectstate"`
	State         int    `json:"state"`
	StateText     string `json:"stateText"`
	StartDate     string `json:"startdate"`
	EndDate       string `json:"enddate"`
	PlayTime      string `json:"playtime"`
	EndTime       string `json:"endtime"`
	TimeLengthTyp int    `json:"timelengthtype"`
	TimeLength    int    `json:"timelength"`
	LengthText    string `json:"timelengthText"`
	ExeModel      string `json:"exemodel"`
	Weekdays      []int  `json:"weekdays"`
	Priority      int    `json:"priority"`
	Volume        int    `json:"defaultvolume"`
	PrePower      int    `json:"prepower"`
	FolderID      int64  `json:"folderId"`
	OwnerUserID   int64  `json:"ownerUserId"`
	OwnerUserName string `json:"ownerUserName"`
	// PowerTaskID 是配套的功放子任务 id，0 表示没有。
	PowerTaskID int64          `json:"powerTaskId"`
	Media       []MediaItem    `json:"media"`
	Terminals   []TerminalItem `json:"terminals"`
	// Startable 与 BlockReason 让前端不必自己复刻启动前置条件。
	Startable   bool   `json:"startable"`
	BlockReason string `json:"blockReason"`
}

type ListResult struct {
	Items     []Item
	Total     int64
	ScopeNote string
}

// 排序白名单（修 D-99）。默认「先按方案状态、再按播放时间升序」（BR-162）。
var orderWhitelist = map[string]string{
	"taskname":     "t.taskname",
	"playtime":     "t.playtime",
	"projectstate": "t.projectstate",
	"state":        "t.state",
	"priority":     "t.priority",
	"startdate":    "t.startdate",
	"taskid":       "t.taskid",
}

// 旧 taskmanager.php 是 `ORDER BY projectstate, playtime asc`。
// 因为 0 = 启用（见 StateEnabled），升序恰好把启用的方案排在前面。
const defaultOrder = "t.projectstate ASC, t.playtime ASC"

type ListQuery struct {
	FolderID  int64
	SearchKey string // taskname | playtime
	Keyword   string
	OrderBy   string
	Order     string
	Pager     store.Pager
	// WithDetails 关掉可以省掉两条批量查询，供只要计数的场景用。
	WithDetails bool
}

// visibleCond 是任务可见范围的唯一权威定义。
//
// 旧版这条件在「有搜索」和「无搜索」两组分支里各写了一遍，
// 管理员分支还带着 task_user_id IN (SELECT id FROM book_admin WHERE id='$userid')，
// 其中 $userid 来自 $_GET —— 于是管理员**不搜索时**只看得到树节点归属者的任务，
// **一搜索**却能看到该分组下所有人的任务（D-103）。两种行为互相矛盾，
// 不存在可以「原样保留」的旧语义。新版统一为：管理员看全部，普通用户只看自己的。
func visibleCond(u *auth.User) (string, []interface{}) {
	if u.IsAdmin {
		return "", nil
	}
	return "t.task_user_id = ?", []interface{}{u.ID}
}

func typeInCond(types []int) (string, []interface{}) {
	ph := strings.TrimSuffix(strings.Repeat("?,", len(types)), ",")
	args := make([]interface{}, len(types))
	for i, v := range types {
		args[i] = v
	}
	return "t.tasktype IN (" + ph + ")", args
}

func (s *Service) List(ctx context.Context, u *auth.User, q ListQuery) (*ListResult, error) {
	// 选中的分组必须先过可见性闸门，否则改一下 folderId 就能列出别人分组里的任务。
	if q.FolderID > 0 {
		if err := s.AssertFolderVisible(ctx, u, q.FolderID); err != nil {
			return nil, err
		}
	}

	cond := &store.Cond{}
	c, args := typeInCond(fileTypes)
	cond.Add(c, args...)
	if q.FolderID > 0 {
		cond.Add("t.parentid = ?", q.FolderID)
	}
	if c, args := visibleCond(u); c != "" {
		cond.Add(c, args...)
	}
	switch q.SearchKey {
	case "taskname":
		if q.Keyword != "" {
			cond.Add(`t.taskname LIKE ? ESCAPE '\\'`, store.EscapeLike(q.Keyword))
		}
	case "playtime":
		// 旧版这里是 playtime >= '值'，保留同样的「下限」语义
		if q.Keyword != "" {
			cond.Add("t.playtime >= ?", q.Keyword)
		}
	}

	where := cond.Where()
	whereArgs := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM task t"+where, whereArgs...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计任务数: %w", err)
	}

	order := store.OrderBy(orderWhitelist, q.OrderBy, q.Order, defaultOrder)

	listSQL := `
		SELECT t.taskid, COALESCE(t.taskname,''), t.tasktype,
		       COALESCE(t.israndomplay,0), COALESCE(t.projectstate,0), COALESCE(t.state,0),
		       COALESCE(DATE_FORMAT(t.startdate,'%Y-%m-%d'),''),
		       COALESCE(DATE_FORMAT(t.enddate,'%Y-%m-%d'),''),
		       COALESCE(t.playtime,'00:00:00'), COALESCE(t.endtime,'00:00:00'),
		       COALESCE(t.timelengthtype,1), COALESCE(t.timelength,0),
		       COALESCE(t.exemodel,'0000000'), COALESCE(t.priority,0),
		       COALESCE(t.defaultvolume,0), COALESCE(t.prepower,0),
		       COALESCE(t.parentid,0), COALESCE(t.task_user_id,0),
		       COALESCE(b.username,'')
		FROM task t
		LEFT JOIN book_admin b ON b.id = t.task_user_id` + where +
		" ORDER BY " + order + " LIMIT ? OFFSET ?"

	listArgs := append(append([]interface{}{}, whereArgs...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, listSQL, listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询任务列表: %w", err)
	}
	defer rs.Close()

	items := make([]Item, 0, q.Pager.PageSize)
	ids := make([]int64, 0, q.Pager.PageSize)
	for rs.Next() {
		var it Item
		if err := rs.Scan(&it.TaskID, &it.TaskName, &it.TaskType,
			&it.IsRandomPlay, &it.ProjectState, &it.State,
			&it.StartDate, &it.EndDate, &it.PlayTime, &it.EndTime,
			&it.TimeLengthTyp, &it.TimeLength, &it.ExeModel, &it.Priority,
			&it.Volume, &it.PrePower, &it.FolderID, &it.OwnerUserID,
			&it.OwnerUserName); err != nil {
			return nil, fmt.Errorf("扫描任务行: %w", err)
		}
		decorate(&it)
		items = append(items, it)
		ids = append(ids, it.TaskID)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}

	if q.WithDetails && len(ids) > 0 {
		// 关键：两条批量查询取代旧版的 2N 条（D-101）
		if err := s.fillMedia(ctx, items, ids); err != nil {
			return nil, err
		}
		if err := s.fillTerminals(ctx, items, ids); err != nil {
			return nil, err
		}
		if err := s.fillPowerTasks(ctx, items, ids); err != nil {
			return nil, err
		}
		for i := range items {
			items[i].Startable, items[i].BlockReason = startable(&items[i])
		}
	}

	res := &ListResult{Items: items, Total: total}
	if !u.IsAdmin {
		res.ScopeNote = "仅显示我创建的任务"
	}
	return res, nil
}

// decorate 填充纯展示派生字段，避免前端各自解释一遍编码。
func decorate(it *Item) {
	// BR-163：0 = 随机，1 = 顺序。写反了播放顺序就错了。
	if it.IsRandomPlay == 1 {
		it.PlayModeText = "顺序"
	} else {
		it.PlayModeText = "随机"
	}
	switch it.State {
	case 0:
		it.StateText = "准备"
	case 1:
		it.StateText = "执行中"
	case 2:
		it.StateText = "已停止"
	case 3:
		it.StateText = "立即执行"
	default:
		it.StateText = fmt.Sprintf("未知(%d)", it.State)
	}
	// BR-164：1 = 按秒数，2 = 按循环次数
	if it.TimeLengthTyp == 1 {
		it.LengthText = fmt.Sprintf("播放 %d 秒", it.TimeLength)
	} else {
		it.LengthText = fmt.Sprintf("循环 %d 次", it.TimeLength)
	}
	it.Weekdays = parseWeekdays(it.ExeModel)
	if it.Media == nil {
		it.Media = []MediaItem{}
	}
	if it.Terminals == nil {
		it.Terminals = []TerminalItem{}
	}
}

// parseWeekdays 把 7 位掩码转成 [1..7]（周一 = 1）。
// BR-165：1111111 = 每天，0000000 = 手动。
func parseWeekdays(mask string) []int {
	out := []int{}
	for i := 0; i < len(mask) && i < 7; i++ {
		if mask[i] == '1' {
			out = append(out, i+1)
		}
	}
	return out
}

// startable 复刻启动前置条件，让列表能提前给出禁用原因（BR-175 / BR-176）。
func startable(it *Item) (bool, string) {
	if it.ProjectState != StateEnabled {
		return false, "方案已停用"
	}
	if len(it.Media) == 0 {
		return false, "没有媒体"
	}
	if len(it.Terminals) == 0 {
		return false, "没有终端"
	}
	return true, ""
}

// fillMedia 一次性取回所有任务的媒体清单。
//
// LEFT JOIN 而不是内连接：媒体被删掉之后这一项必须仍然显示并标记「已删除」（BR-166），
// 旧版的 FROM media, mediaoftask WHERE media.id = mediaoftask.mediaid 会让它凭空消失。
func (s *Service) fillMedia(ctx context.Context, items []Item, ids []int64) error {
	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx, `
		SELECT mot.taskid, mot.mediaid, mot.sort,
		       m.id IS NOT NULL, COALESCE(m.name,''), COALESCE(m.size,0)
		FROM mediaoftask mot
		LEFT JOIN media m ON m.id = mot.mediaid
		WHERE mot.taskid IN (`+ph+`)
		ORDER BY mot.taskid, mot.sort, mot.id`, args...)
	if err != nil {
		return fmt.Errorf("查询任务媒体清单: %w", err)
	}
	defer rs.Close()

	byTask := make(map[int64][]MediaItem, len(ids))
	for rs.Next() {
		var taskID int64
		var mi MediaItem
		var exists bool
		if err := rs.Scan(&taskID, &mi.MediaID, &mi.Sort, &exists, &mi.Name, &mi.Size); err != nil {
			return err
		}
		mi.Deleted = !exists
		if mi.Deleted {
			mi.Name = "(媒体已删除)"
		}
		byTask[taskID] = append(byTask[taskID], mi)
	}
	if err := rs.Err(); err != nil {
		return err
	}
	for i := range items {
		if v, ok := byTask[items[i].TaskID]; ok {
			items[i].Media = v
		}
	}
	return nil
}

// fillTerminals 一次性取回所有任务的终端清单。
//
// 同样用 LEFT JOIN：终端删掉后关联行可能还在（旧版删终端时漏清 terminaloftask
// 的分支不止一处），内连接会让这些行静默消失，看不出任务其实指着一台不存在的设备。
// postion 这里不再取 —— 旧版写成 $row1['$postion'] 本来就永远是空（D-104），
// 而现网这一列存的是固件版本串，放在任务清单里没有意义。
func (s *Service) fillTerminals(ctx context.Context, items []Item, ids []int64) error {
	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx, `
		SELECT ot.taskid, ot.terminalid, COALESCE(ot.groupid,0), COALESCE(ot.area,''),
		       tm.id IS NOT NULL, COALESCE(tm.terminalname,''), COALESCE(tt.name,''),
		       COALESCE(tm.netstate,0), COALESCE(tm.taskstate,0),
		       COALESCE(tm.ip,''), COALESCE(tm.volume,0)
		FROM terminaloftask ot
		LEFT JOIN terminal tm ON tm.id = ot.terminalid
		LEFT JOIN terminaltype tt ON tt.id = tm.typeid
		WHERE ot.taskid IN (`+ph+`)
		ORDER BY ot.taskid, ot.id`, args...)
	if err != nil {
		return fmt.Errorf("查询任务终端清单: %w", err)
	}
	defer rs.Close()

	byTask := make(map[int64][]TerminalItem, len(ids))
	for rs.Next() {
		var taskID int64
		var ti TerminalItem
		var exists bool
		if err := rs.Scan(&taskID, &ti.TerminalID, &ti.GroupID, &ti.Area,
			&exists, &ti.TerminalName, &ti.TypeName,
			&ti.NetState, &ti.TaskState, &ti.IP, &ti.Volume); err != nil {
			return err
		}
		ti.Deleted = !exists
		if ti.Deleted {
			ti.TerminalName = "(终端已删除)"
		}
		byTask[taskID] = append(byTask[taskID], ti)
	}
	if err := rs.Err(); err != nil {
		return err
	}
	for i := range items {
		if v, ok := byTask[items[i].TaskID]; ok {
			items[i].Terminals = v
		}
	}
	return nil
}

// fillPowerTasks 回填功放子任务 id。
// 关系方向是「子任务的 sec_task_id 指向主任务」，现网数据核对过。
func (s *Service) fillPowerTasks(ctx context.Context, items []Item, ids []int64) error {
	ph, args := placeholders(ids)
	args = append(args, TypePowerAmp)
	rs, err := s.db.QueryContext(ctx,
		`SELECT sec_task_id, taskid FROM task WHERE sec_task_id IN (`+ph+`) AND tasktype = ?`, args...)
	if err != nil {
		return fmt.Errorf("查询功放子任务: %w", err)
	}
	defer rs.Close()

	m := make(map[int64]int64, len(ids))
	for rs.Next() {
		var main, sub int64
		if err := rs.Scan(&main, &sub); err != nil {
			return err
		}
		m[main] = sub
	}
	if err := rs.Err(); err != nil {
		return err
	}
	for i := range items {
		items[i].PowerTaskID = m[items[i].TaskID]
	}
	return nil
}

// placeholders64 同 placeholders，只是入参是 []int（类型号那类小整数集合）。
func placeholders64(vals []int) (string, []interface{}) {
	if len(vals) == 0 {
		return "NULL", nil
	}
	args := make([]interface{}, len(vals))
	for i, v := range vals {
		args[i] = v
	}
	return strings.TrimSuffix(strings.Repeat("?,", len(vals)), ","), args
}

func placeholders(ids []int64) (string, []interface{}) {
	if len(ids) == 0 {
		return "NULL", nil
	}
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		args[i] = id
	}
	return strings.TrimSuffix(strings.Repeat("?,", len(ids)), ","), args
}
