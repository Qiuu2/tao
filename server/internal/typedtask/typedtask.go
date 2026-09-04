// Package typedtask 实现四个「按 tasktype 切出来的任务视图」：
// 终端功放、采播管理、文字语音、LED 播放。
//
// 这四个页面在旧版是四个独立的 php，但它们做的是同一件事 ——
// 从 `task` 表里按 `tasktype` 捞出一批任务，配上各自的附加数据。
// 所以这里做成一个包、一套列表/启停/删除逻辑，差异全部收进 spec 表。
//
// # tasktype 的取值依据（全部来自旧版页面里写死的 SQL）
//
//	终端功放  terminalfunctionplay.php  tasktype = 5
//	                                    且 channel=0 AND sec_task_id=0 AND prepower=0
//	采播管理  admmanager.php            tasktype = 3
//	文字语音  displayttsmanager.php     tasktype IN (15,17,19)
//	LED 播放  ledtaskmanager.php        tasktype IN (24) AND sec_task_id = 0
//	                                    且按 parentid = ledtaskfree.id 分组
//
// `task` 表 tasktype 列的注释写的是「1-作息 2-文件 3-采播 4-电话 5-功放」，
// 只覆盖到 5；9/13/15/17/19/23/24/25/27/30 都是后来加的，没有任何注释，
// 只能从各页面写死的 SQL 反推。上面这张表就是反推的结果。
//
// # ⚠ 类型 15 同时出现在两个页面里
//
// `displayttsmanager.php` 把 15 当文字语音列出来，
// 而 `keyset_task_mapping.php`（遥控任务的候选）把 15 和 2 一起当文件广播列出来。
// 两边都不是笔误：类型 15 的任务**是**一条播放文件的任务，
// 只不过它要播的那个文件是 TTS 合成出来的。
// 新版照搬这个重叠 —— 强行二选一会让其中一个页面丢数据。
//
// # 启停与删除为什么不复用 task 包
//
// task 包的 `Control` 要求「启动前必须有媒体」（BR-176）——
// 那是文件广播的正确规则，但**终端功放任务本来就没有媒体**
// （它是一条「几点几分把某个通道的电源打开」的指令），
// 采播任务的音源也不在 mediaoftask 里。套用那条规则会让这两类永远启动不了。
// 所以这里另写一套判定，每种类型各自要求什么写在 spec 里。
package typedtask

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/notify"
	"htweb/internal/store"
)

type Service struct {
	db *sql.DB
}

func New(db *sql.DB) *Service { return &Service{db: db} }

var (
	ErrNotFound     = errors.New("任务不存在")
	ErrNoPermission = errors.New("无权操作该任务")
	ErrBadKind      = errors.New("未知的任务类别")
)

// Kind 是这个包支持的四种任务类别。
type Kind string

const (
	KindAmplifier Kind = "amplifier" // 终端功放
	KindCollect   Kind = "collect"   // 采播管理
	KindTTS       Kind = "tts"       // 文字语音
	KindLED       Kind = "led"       // LED 播放
)

// projectstate 的两个取值。
//
// ⚠ 与 `audioserver.sql` 里的列注释「1启用，0停用」**相反**，注释是错的。
// 与 task 包的 StateEnabled 保持同一套常量语义，别在这里另起炉灶。
const (
	StateEnabled  = 0 // 启用
	StateDisabled = 1 // 停用
)

// AreaAll 是终端区域掩码的默认值。现网 terminaloftask 全部行都是 8 个 1。
const AreaAll = "11111111"

// spec 把四种类别的差异集中在一处。
type spec struct {
	// Types 是这一类在 task.tasktype 上的取值集合。
	Types []int
	// NewType 是新建时写哪一个（Types 里的主类型）。
	NewType int
	// Extra 是列表额外的 WHERE 片段，原样来自旧版页面写死的 SQL。
	Extra string
	// NeedMedia 表示启动前必须有媒体。功放与采播没有媒体，为 false。
	NeedMedia bool
	// NeedTerminal 表示启动前必须有终端。四类都需要 —— 没有终端就没有设备会响。
	NeedTerminal bool
	// ByFolder 表示这一类按 parentid 分组（只有 LED 是）。
	ByFolder bool
	Title    string
}

var specs = map[Kind]spec{
	KindAmplifier: {
		Types:   []int{5},
		NewType: 5,
		// 旧版 terminalfunctionplay.php 的列表条件，一字不差地搬过来
		Extra:        ` AND COALESCE(t.channel,0) = 0 AND COALESCE(t.sec_task_id,0) = 0 AND COALESCE(t.prepower,0) = 0`,
		NeedMedia:    false,
		NeedTerminal: true,
		Title:        "终端功放",
	},
	KindCollect: {
		Types:        []int{3},
		NewType:      3,
		Extra:        "",
		NeedMedia:    false,
		NeedTerminal: true,
		Title:        "采播管理",
	},
	KindTTS: {
		Types:        []int{15, 17, 19},
		NewType:      15,
		Extra:        ` AND COALESCE(t.sec_task_id,0) = 0`,
		NeedMedia:    true,
		NeedTerminal: true,
		Title:        "文字语音",
	},
	KindLED: {
		// ⚠ 现网的 LED 任务全是 tasktype = 30，一条 24 都没有 ——
		//    24 只作为可能存在的旧数据兼容留着。
		//    「独立 / 附属」不看类型号，看 sec_task_id 是不是 0（见 led.go 的 N-21）。
		Types:        []int{24, 30},
		NewType:      30,
		Extra:        ` AND COALESCE(t.sec_task_id,0) = 0`,
		NeedMedia:    false,
		NeedTerminal: true,
		ByFolder:     true,
		Title:        "LED 播放",
	},
}

func ParseKind(s string) (Kind, bool) {
	k := Kind(s)
	_, ok := specs[k]
	return k, ok
}

func (s *Service) spec(k Kind) (spec, error) {
	sp, ok := specs[k]
	if !ok {
		return spec{}, ErrBadKind
	}
	return sp, nil
}

// ---------- 列表 ----------

type Item struct {
	TaskID         int64  `json:"taskId"`
	TaskName       string `json:"taskName"`
	TaskType       int    `json:"tasktype"`
	State          int    `json:"state"`
	StateText      string `json:"stateText"`
	ProjectState   int    `json:"projectstate"`
	ProjectText    string `json:"projectText"`
	StartDate      string `json:"startdate"`
	EndDate        string `json:"enddate"`
	PlayTime       string `json:"playtime"`
	EndTime        string `json:"endtime"`
	ExeModel       string `json:"exemodel"`
	CycleText      string `json:"cycleText"`
	Volume         int    `json:"defaultvolume"`
	TimeLength     int    `json:"timelength"`
	TimeLengthType int    `json:"timelengthtype"`
	LengthText     string `json:"lengthText"`
	Cmd            int64  `json:"cmd"`
	CmdArgs        string `json:"cmdargs"`
	ParentID       int64  `json:"parentid"`
	UserID         int64  `json:"userId"`
	UserName       string `json:"userName"`
	TerminalCount  int    `json:"terminalCount"`
	CanModify      bool   `json:"canModify"`

	// 以下四列是为了和 :80 的列表逐列对齐才带出来的（见 docs/image/oktw/页面规格.txt）。
	//
	// ⚠ 对这四类任务来说它们基本是常量：
	//   priority   新建时固定写 3
	//   bandrate   / samplerate  写 0（列注释是「对编码任务有效」，这四类都不是编码任务）
	//   playfileid 写 0，由后台服务在播放时回写
	// 也就是说列表上大概率是一片 3 和 0。这是 :80 本身就有的列，不是我们凭空加的。
	Priority   int   `json:"priority"`
	BandRate   int   `json:"bandrate"`
	SampleRate int   `json:"samplerate"`
	PlayFileID int64 `json:"playfileid"`

	// Prepower / DataSendModel 对应 :80 表单里的「预开电源 / 发送模式」。
	// ⚠ 终端功放不用这两项，理由见 edit.go 的 normPerKind()。
	Prepower      int `json:"prepower"`
	DataSendModel int `json:"datasendmodel"`

	// 以下是按类别填的附加信息，用不到的留空。
	// 功放：cmd 0=打开 1=关闭，cmdargs=通道号
	SwitchText string `json:"switchText,omitempty"`
	// 采播：cmd = 采播源终端 id
	SourceName string `json:"sourceName,omitempty"`
	// 文字语音 / LED：要播的文本
	Text string `json:"text,omitempty"`

	// 间隔播放（文字语音 / led播放 的「播放模式」）。旧版列表里有「播放模式」一列，
	// 判据就是 intplaylengthtype：0 = 普通模式，非 0 = 间隔时间。
	IntervalS    int    `json:"interval_s"`
	IntPlayLen   int    `json:"intplaylength"`
	IntPlayLenTy int    `json:"intplaylengthtype"`
	PlayModeText string `json:"playModeText,omitempty"`
	// TypeText 是旧版文字语音列表里的「任务类型」列（tasktype 15/17/19 三选一）
	TypeText string `json:"typeText,omitempty"`
}

type ListResult struct {
	Items     []Item
	Total     int64
	ScopeNote string
}

var orderWhitelist = map[string]string{
	"taskname":  "t.taskname",
	"playtime":  "t.playtime",
	"startdate": "t.startdate",
	"taskid":    "t.taskid",
}

// 与 task 包一致：projectstate 升序把启用的排前面（0 = 启用）
const defaultOrder = "t.projectstate ASC, t.playtime ASC"

type Query struct {
	Keyword  string
	FolderID int64 // 只对 LED 有意义
	OrderBy  string
	Order    string
	Pager    store.Pager
}

// visibleCond 与 task 包同一套口径：管理员看全部，普通用户只看自己的。
func visibleCond(u *auth.User) (string, []interface{}) {
	if u.IsAdmin {
		return "", nil
	}
	return "t.task_user_id = ?", []interface{}{u.ID}
}

func typeCond(types []int) (string, []interface{}) {
	ph := strings.TrimSuffix(strings.Repeat("?,", len(types)), ",")
	args := make([]interface{}, len(types))
	for i, v := range types {
		args[i] = v
	}
	return "t.tasktype IN (" + ph + ")", args
}

func (s *Service) List(ctx context.Context, u *auth.User, k Kind, q Query) (*ListResult, error) {
	sp, err := s.spec(k)
	if err != nil {
		return nil, err
	}

	cond := &store.Cond{}
	c, args := typeCond(sp.Types)
	cond.Add(c, args...)
	if sp.Extra != "" {
		// Extra 是包内常量，不含任何用户输入
		cond.Add(strings.TrimPrefix(strings.TrimSpace(sp.Extra), "AND "))
	}
	if sp.ByFolder && q.FolderID > 0 {
		cond.Add("t.parentid = ?", q.FolderID)
	}
	if c, args := visibleCond(u); c != "" {
		cond.Add(c, args...)
	}
	if q.Keyword != "" {
		cond.Add(`t.taskname LIKE ? ESCAPE '\\'`, store.EscapeLike(q.Keyword))
	}
	where := cond.Where()
	wargs := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM task t"+where, wargs...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计%s任务: %w", sp.Title, err)
	}

	order := store.OrderBy(orderWhitelist, q.OrderBy, q.Order, defaultOrder)
	// 日期时间列一律 CAST 成 CHAR 取字面值：DSN 带 parseTime=true，
	// 让驱动解析再格式化回去会多一层时区换算的风险。
	listArgs := append(append([]interface{}{}, wargs...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, `
		SELECT t.taskid, COALESCE(t.taskname,''), t.tasktype,
		       COALESCE(t.state,0), COALESCE(t.projectstate,0),
		       COALESCE(CAST(t.startdate AS CHAR),''), COALESCE(CAST(t.enddate AS CHAR),''),
		       COALESCE(CAST(t.playtime AS CHAR),''), COALESCE(CAST(t.endtime AS CHAR),''),
		       COALESCE(t.exemodel,''), COALESCE(t.defaultvolume,0),
		       COALESCE(t.timelength,0), COALESCE(t.timelengthtype,0),
		       COALESCE(t.cmd,0), COALESCE(t.cmdargs,''), COALESCE(t.parentid,0),
		       COALESCE(t.task_user_id,0), COALESCE(b.username,''),
		       (SELECT COUNT(*) FROM terminaloftask ot WHERE ot.taskid = t.taskid),
		       COALESCE(t.priority,0), COALESCE(t.bandrate,0),
		       COALESCE(t.samplerate,0), COALESCE(t.playfileid,0),
		       COALESCE(t.prepower,0), COALESCE(t.datasendmodel,0),
		       COALESCE(t.interval_s,0), COALESCE(t.intplaylength,0),
		       COALESCE(t.intplaylengthtype,0)
		FROM task t
		LEFT JOIN book_admin b ON b.id = t.task_user_id`+where+
		" ORDER BY "+order+" LIMIT ? OFFSET ?", listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询%s任务: %w", sp.Title, err)
	}
	defer rs.Close()

	items := make([]Item, 0, q.Pager.PageSize)
	ids := make([]int64, 0, q.Pager.PageSize)
	for rs.Next() {
		var it Item
		if err := rs.Scan(&it.TaskID, &it.TaskName, &it.TaskType,
			&it.State, &it.ProjectState,
			&it.StartDate, &it.EndDate, &it.PlayTime, &it.EndTime,
			&it.ExeModel, &it.Volume, &it.TimeLength, &it.TimeLengthType,
			&it.Cmd, &it.CmdArgs, &it.ParentID,
			&it.UserID, &it.UserName, &it.TerminalCount,
			&it.Priority, &it.BandRate, &it.SampleRate, &it.PlayFileID,
			&it.Prepower, &it.DataSendModel,
			&it.IntervalS, &it.IntPlayLen, &it.IntPlayLenTy); err != nil {
			return nil, fmt.Errorf("扫描%s任务行: %w", sp.Title, err)
		}
		it.StateText = stateText(it.State)
		it.ProjectText = projectText(it.ProjectState)
		it.CycleText = cycleText(it.ExeModel)
		it.LengthText = lengthText(it.TimeLengthType, it.TimeLength)
		it.CanModify = u.IsAdmin || it.UserID == u.ID
		it.PlayModeText = playModeText(it.IntPlayLenTy)
		it.TypeText = taskTypeText(it.TaskType)
		if k == KindAmplifier {
			it.SwitchText = switchText(it.Cmd)
		}
		items = append(items, it)
		ids = append(ids, it.TaskID)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}

	if len(ids) > 0 {
		if err := s.fillExtras(ctx, k, items, ids); err != nil {
			return nil, err
		}
	}

	res := &ListResult{Items: items, Total: total}
	if !u.IsAdmin {
		res.ScopeNote = "仅显示我创建的任务"
	}
	return res, nil
}

// fillExtras 按类别补充附加列，每种都只跑一条批量查询，不做 N+1。
func (s *Service) fillExtras(ctx context.Context, k Kind, items []Item, ids []int64) error {
	switch k {
	case KindCollect:
		return s.fillCollectSource(ctx, items)
	case KindTTS:
		// 文字语音的 cmd 也是终端 id（:80 叫「TTS采播终端」），一并把名字带出来
		if err := s.fillCollectSource(ctx, items); err != nil {
			return err
		}
		return s.fillTTSText(ctx, items, ids)
	case KindLED:
		return s.fillLEDText(ctx, items, ids)
	}
	return nil
}

// fillCollectSource 采播任务的 cmd 是**采播源终端的 id**
// （task.cmd 的列注释：「类型为3时：才播终端ID」，「才播」是「采播」的错别字）。
func (s *Service) fillCollectSource(ctx context.Context, items []Item) error {
	want := map[int64]bool{}
	for _, it := range items {
		if it.Cmd > 0 {
			want[it.Cmd] = true
		}
	}
	if len(want) == 0 {
		return nil
	}
	list := make([]int64, 0, len(want))
	for id := range want {
		list = append(list, id)
	}
	ph, args := placeholders(list)
	rs, err := s.db.QueryContext(ctx,
		`SELECT id, COALESCE(terminalname,'') FROM terminal WHERE id IN (`+ph+`)`, args...)
	if err != nil {
		return fmt.Errorf("查询采播源终端: %w", err)
	}
	defer rs.Close()
	names := map[int64]string{}
	for rs.Next() {
		var id int64
		var n string
		if err := rs.Scan(&id, &n); err != nil {
			return err
		}
		names[id] = n
	}
	if err := rs.Err(); err != nil {
		return err
	}
	for i := range items {
		if items[i].Cmd <= 0 {
			continue
		}
		if n, ok := names[items[i].Cmd]; ok {
			items[i].SourceName = n
		} else {
			items[i].SourceName = "(终端已删除)"
		}
	}
	return nil
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

// ---------- 文案 ----------

func stateText(v int) string {
	switch v {
	case 0:
		return "准备"
	case 1:
		return "执行中"
	case 2:
		return "已停止"
	case 3:
		return "立即执行"
	}
	return fmt.Sprintf("状态 %d", v)
}

func projectText(v int) string {
	if v == StateEnabled {
		return "启用"
	}
	return "停用"
}

func switchText(cmd int64) string {
	// task.cmd 列注释：「类型为5时（0：打开，1：关闭）」
	if cmd == 1 {
		return "关闭"
	}
	return "打开"
}

// playModeText 是旧版列表里的「播放模式」一列。
// 判据是 intplaylengthtype：普通模式落库是 0，间隔模式是 1（按时长）或 2（按次数）。
func playModeText(ty int) string {
	if ty == 0 {
		return "普通模式"
	}
	return "间隔时间"
}

// taskTypeText 是旧版文字语音列表里的「任务类型」一列。
// tasktype 的中文名取自旧版 addmanager.html 里那串 if/elseif。
func taskTypeText(t int) string {
	switch t {
	case 1:
		return "作息方案"
	case 2:
		return "文件广播"
	case 3:
		return "采播管理"
	case 4:
		return "电话采播"
	case 5:
		return "终端功放"
	case 10:
		return "网络电台"
	case 15, 17, 19:
		return "文字语音"
	case 24, 30:
		return "led播放"
	}
	return fmt.Sprintf("类型 %d", t)
}

// exemodel 是周日打头的 7 位掩码（第 1 位 = 周日），标签顺序要跟它对齐。
var weekNames = [7]string{"日", "一", "二", "三", "四", "五", "六"}

// cycleText 把 exemodel 的 7 位掩码翻成人话。
// 位序与作息方案一致：第 0 位是周日 …… 第 6 位是周六。
func cycleText(mask string) string {
	if len(mask) != 7 {
		return "—"
	}
	if mask == "0000000" {
		return "手动"
	}
	if mask == "1111111" {
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

// lengthText 把播放时长翻成人话。
// timelengthtype：1 = 按时间（秒），其它 = 按循环次数（列注释写的是 2）。
func lengthText(t, v int) string {
	if t == 1 {
		if v <= 0 {
			return "—"
		}
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
	if v <= 0 {
		return "—"
	}
	return fmt.Sprintf("循环 %d 次", v)
}

// ---------- 启停 ----------

type Blocked struct {
	ID     int64  `json:"id"`
	Name   string `json:"name"`
	Reason string `json:"reason"`
	Detail string `json:"detail"`
}

type ControlResult struct {
	Succeeded []int64   `json:"succeeded"`
	Blocked   []Blocked `json:"blocked"`
	Notified  bool      `json:"notified"`
}

type Action string

const (
	ActionStart  Action = "start"
	ActionStop   Action = "stop"
	ActionPause  Action = "pause"
	ActionResume Action = "resume"
)

func ParseAction(s string) (Action, bool) {
	a := Action(s)
	switch a {
	case ActionStart, ActionStop, ActionPause, ActionResume:
		return a, true
	}
	return a, false
}

type controlRow struct {
	id           int64
	name         string
	taskType     int
	projectState int
	owner        int64
	mediaCount   int
	termCount    int
}

// Control 启停一批任务。
//
// 判定规则按类别走 spec：功放与采播不要求有媒体（它们本来就没有），
// 四类都要求有终端。旧版是一条 UPDATE 全部照做，既不校验也不反馈。
func (s *Service) Control(ctx context.Context, u *auth.User, n *notify.Notifier,
	k Kind, action Action, ids []int64) (*ControlResult, error) {

	sp, err := s.spec(k)
	if err != nil {
		return nil, err
	}
	out := &ControlResult{Succeeded: []int64{}, Blocked: []Blocked{}}
	if len(ids) == 0 {
		return out, nil
	}

	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx, `
		SELECT t.taskid, COALESCE(t.taskname,''), t.tasktype,
		       COALESCE(t.projectstate,0), COALESCE(t.task_user_id,0),
		       (SELECT COUNT(*) FROM mediaoftask WHERE taskid = t.taskid),
		       (SELECT COUNT(*) FROM terminaloftask WHERE taskid = t.taskid)
		FROM task t WHERE t.taskid IN (`+ph+`)`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询任务状态: %w", err)
	}
	rows := map[int64]controlRow{}
	for rs.Next() {
		var r controlRow
		if err := rs.Scan(&r.id, &r.name, &r.taskType, &r.projectState,
			&r.owner, &r.mediaCount, &r.termCount); err != nil {
			rs.Close()
			return nil, err
		}
		rows[r.id] = r
	}
	rs.Close()
	if err := rs.Err(); err != nil {
		return nil, err
	}

	typeOK := map[int]bool{}
	for _, t := range sp.Types {
		typeOK[t] = true
	}

	var doable []int64
	for _, id := range ids {
		r, exists := rows[id]
		switch {
		case !exists:
			out.Blocked = append(out.Blocked, Blocked{ID: id, Reason: "NOT_FOUND", Detail: "任务不存在"})
		case !u.IsAdmin && r.owner != u.ID:
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "NOT_OWNER", Detail: "只能操作自己创建的任务"})
		case !typeOK[r.taskType]:
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "BAD_TYPE",
				Detail: fmt.Sprintf("任务类型 %d 不属于%s，不能从这里启停", r.taskType, sp.Title)})
		case action == ActionStart && r.projectState != StateEnabled:
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "DISABLED", Detail: "方案已停用，请先启用后再启动"})
		case action == ActionStart && sp.NeedMedia && r.mediaCount == 0:
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "NO_MEDIA", Detail: "任务没有媒体，启动后后台会空转"})
		case action == ActionStart && sp.NeedTerminal && r.termCount == 0:
			out.Blocked = append(out.Blocked, Blocked{ID: id, Name: r.name,
				Reason: "NO_TERMINAL", Detail: "任务没有终端，启动后没有设备会播放"})
		default:
			doable = append(doable, id)
		}
	}
	if len(doable) == 0 {
		return out, nil
	}

	// state：3 = 立即执行，2 = 停止。与 task 包 applyState 同一套取值，
	// 也与旧版 `UPDATE task SET state='3'` / `state='2'` 一致。
	//
	// ⚠ 暂停与恢复**不落库** —— 与 task 包和旧版一致，这两个动作纯粹是发给
	// 后台服务的实时指令，task.state 由后台自己维护。
	if action == ActionStart || action == ActionStop {
		state := 3
		if action == ActionStop {
			state = 2
		}
		dph, dargs := placeholders(doable)
		if _, err := s.db.ExecContext(ctx,
			`UPDATE task SET state = ? WHERE taskid IN (`+dph+`)`,
			append([]interface{}{state}, dargs...)...); err != nil {
			return nil, fmt.Errorf("更新任务状态: %w", err)
		}
	}
	out.Succeeded = doable

	// 通知必须在数据库改完之后发
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

// SetProjectState 批量启用 / 停用方案。
func (s *Service) SetProjectState(ctx context.Context, u *auth.User, n *notify.Notifier,
	k Kind, ids []int64, enable bool) (*ControlResult, error) {

	sp, err := s.spec(k)
	if err != nil {
		return nil, err
	}
	out := &ControlResult{Succeeded: []int64{}, Blocked: []Blocked{}}
	if len(ids) == 0 {
		return out, nil
	}
	ok, blocked, err := s.gate(ctx, u, sp, ids)
	if err != nil {
		return nil, err
	}
	out.Blocked = blocked
	if len(ok) == 0 {
		return out, nil
	}

	// ⚠ 0 = 启用、1 = 停用（列注释是反的）
	v := StateDisabled
	if enable {
		v = StateEnabled
	}
	ph, args := placeholders(ok)
	if _, err := s.db.ExecContext(ctx,
		`UPDATE task SET projectstate = ? WHERE taskid IN (`+ph+`)`,
		append([]interface{}{v}, args...)...); err != nil {
		return nil, fmt.Errorf("修改方案状态: %w", err)
	}
	out.Succeeded = ok
	// 停用时顺带把正在跑的停掉，免得「已停用但还在播」
	if !enable {
		n.TaskChanged(ctx, notify.TaskStop, ok)
		out.Notified = true
	}
	return out, nil
}

// gate 把请求的 id 分成「能操作」与「被挡」两组。
func (s *Service) gate(ctx context.Context, u *auth.User, sp spec, ids []int64) ([]int64, []Blocked, error) {
	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx,
		`SELECT taskid, COALESCE(taskname,''), tasktype, COALESCE(task_user_id,0)
		 FROM task WHERE taskid IN (`+ph+`)`, args...)
	if err != nil {
		return nil, nil, fmt.Errorf("查询任务: %w", err)
	}
	type row struct {
		name     string
		taskType int
		owner    int64
	}
	found := map[int64]row{}
	for rs.Next() {
		var id int64
		var r row
		if err := rs.Scan(&id, &r.name, &r.taskType, &r.owner); err != nil {
			rs.Close()
			return nil, nil, err
		}
		found[id] = r
	}
	rs.Close()
	if err := rs.Err(); err != nil {
		return nil, nil, err
	}

	typeOK := map[int]bool{}
	for _, t := range sp.Types {
		typeOK[t] = true
	}
	ok := []int64{}
	blocked := []Blocked{}
	for _, id := range ids {
		r, exists := found[id]
		switch {
		case !exists:
			blocked = append(blocked, Blocked{ID: id, Reason: "NOT_FOUND", Detail: "任务不存在"})
		case !u.IsAdmin && r.owner != u.ID:
			blocked = append(blocked, Blocked{ID: id, Name: r.name,
				Reason: "NOT_OWNER", Detail: "只能操作自己创建的任务"})
		case !typeOK[r.taskType]:
			blocked = append(blocked, Blocked{ID: id, Name: r.name,
				Reason: "BAD_TYPE",
				Detail: fmt.Sprintf("任务类型 %d 不属于%s", r.taskType, sp.Title)})
		default:
			ok = append(ok, id)
		}
	}
	return ok, blocked, nil
}

// ---------- 删除 ----------

type DeleteResult struct {
	Deleted []int64   `json:"deleted"`
	Blocked []Blocked `json:"blocked"`
}

// Delete 删除任务，并清掉它在各张关联表里的行。
//
// 关联表清单来自旧版各 del 函数的并集：
//
//	terminaloftask         终端清单（四类都有）
//	mediaoftask            媒体清单（文字语音有；功放/采播没有）
//	ttssentence            文字语音的语句，按 sentenceid = mediaoftask.mediaid 关联
//	ledsentence / ledoftask LED 的文本与设备绑定
//	terminalkeymaptask     遥控键绑定（旧版删任务时会清）
//	shortcutkeytask        遥控任务绑定 —— **旧版漏了这张**（D-221），
//	                       删完任务遥控键上还挂着一个不存在的 taskid
//	offlinetaskofterminal / offlinemediaofterminal  离线下发关系
func (s *Service) Delete(ctx context.Context, u *auth.User, k Kind, ids []int64) (*DeleteResult, error) {
	sp, err := s.spec(k)
	if err != nil {
		return nil, err
	}
	out := &DeleteResult{Deleted: []int64{}, Blocked: []Blocked{}}
	if len(ids) == 0 {
		return out, nil
	}
	ok, blocked, err := s.gate(ctx, u, sp, ids)
	if err != nil {
		return nil, err
	}
	out.Blocked = blocked
	if len(ok) == 0 {
		return out, nil
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 文字语音可能挂着 LED 字幕子任务，删主任务时要把它一起带走，
	// 否则子任务连同它的 media / ledsentence 会留成孤儿（N-22 的同类问题）。
	victims := append([]int64{}, ok...)
	for _, id := range ok {
		sub, err := findLEDSub(ctx, tx, id)
		if err != nil {
			return nil, err
		}
		if sub > 0 {
			victims = append(victims, sub)
		}
	}
	ph, args := placeholders(victims)

	// ttssentence / ledsentence / 虚拟媒体都要在 mediaoftask 被删之前清 ——
	// 它们只能通过 mediaoftask 定位，先删了关系就再也找不到。
	//
	// ⚠ ledsentence 的关联键是 **mediaid**（= media.id），不是它自己的 id，
	//   写成 id 的话一行都删不掉（N-20）。
	//
	// 三类任务统一处理：文字语音与 LED 都会造 typeid='tts' 的虚拟媒体，
	// 功放与采播没有媒体，这几条语句对它们是空操作，不必分支。
	for _, stmt := range []string{
		`DELETE FROM ttssentence
		   WHERE sentenceid IN (SELECT mediaid FROM mediaoftask WHERE taskid IN (` + ph + `))`,
		`DELETE FROM ledsentence
		   WHERE mediaid IN (SELECT mediaid FROM mediaoftask WHERE taskid IN (` + ph + `))`,
		// 合成出来的虚拟媒体跟着任务一起删；普通媒体不能删 —— 它可能还被别的任务用着。
		`DELETE FROM media WHERE typeid = 'tts'
		   AND id IN (SELECT mediaid FROM mediaoftask WHERE taskid IN (` + ph + `))`,
		`DELETE FROM ledoftask WHERE taskid IN (` + ph + `)`,
	} {
		if _, err := tx.ExecContext(ctx, stmt, args...); err != nil {
			return nil, fmt.Errorf("清理任务附加数据: %w", err)
		}
	}

	for _, stmt := range []string{
		`DELETE FROM terminaloftask WHERE taskid IN (` + ph + `)`,
		`DELETE FROM mediaoftask WHERE taskid IN (` + ph + `)`,
		`DELETE FROM terminalkeymaptask WHERE taskid IN (` + ph + `)`,
		// 旧版各 del 函数都漏了这张（D-221）
		`DELETE FROM shortcutkeytask WHERE mediaid IN (` + ph + `)`,
		`DELETE FROM offlinetaskofterminal WHERE taskid IN (` + ph + `)`,
		`DELETE FROM offlinemediaofterminal WHERE taskid IN (` + ph + `)`,
		`DELETE FROM offlinetask WHERE taskid IN (` + ph + `)`,
		// 子任务（电源/LED 附属任务）通过 sec_task_id 指回来
		`DELETE FROM terminaloftask WHERE taskid IN (SELECT taskid FROM (SELECT taskid FROM task WHERE sec_task_id IN (` + ph + `)) x)`,
		`DELETE FROM task WHERE sec_task_id IN (` + ph + `)`,
		`DELETE FROM task WHERE taskid IN (` + ph + `)`,
	} {
		if _, err := tx.ExecContext(ctx, stmt, args...); err != nil {
			return nil, fmt.Errorf("删除任务关联数据: %w", err)
		}
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	out.Deleted = ok
	return out, nil
}
