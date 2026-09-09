package openapi

import (
	"context"
	"fmt"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/store"
	"htweb/internal/task"
	"htweb/internal/terminal"
)

// 查询类接口：任务列表、终端状态、媒体列表、作息方案列表。
//
// # 为什么不直接把界面用的结构体吐出去
//
// 界面那几个 Item 结构体是**跟着页面走的**：加一列、改一个字段名，
// 前后端一起改就行。开发者接口不行 —— 对方的代码在他们那边，我们改一个
// 字段名，他们的程序第二天就崩了，而且我们无从知道有谁在用。
//
// 所以这里另定一套 DTO，字段少、名字自解释、**只增不改**。多写这一层的代价，
// 换的是内部随便重构而外部合同不变。
//
// # 字段为什么要带「文字版」
//
// netstate = 1 对调用方没有意义，他还得回来查文档。既然我们知道它是「在线」，
// 就一并给出去 —— 机器读 netstate，人读 netstateText，排查问题时看日志能直接看懂。

// TaskBrief 是任务在开发者接口里的样子。
type TaskBrief struct {
	ID   int64  `json:"id"`
	Name string `json:"name"`
	// Note 是任务备注（task.info）。
	//
	// ⚠ 别把它当作息方案名 —— 同一列在**作息任务**（tasktype 1/15）上才是方案名，
	//   在文件广播任务上就只是一句备注。这个接口只列文件广播任务，所以叫 Note。
	//   方案里有哪些任务走 /openapi/v1/schedules/{name}。
	Note string `json:"note"`
	// Enabled 是「启用/停用」，Running 是「此刻正在播」。两件事，别混。
	Enabled   bool   `json:"enabled"`
	Running   bool   `json:"running"`
	StateText string `json:"stateText"`
	StartDate string `json:"startDate"`
	EndDate   string `json:"endDate"`
	PlayTime  string `json:"playTime"`
	EndTime   string `json:"endTime"`
	// Weekdays 是 1=周一 … 7=周日。空数组表示手动任务（不自动触发）。
	//
	// ⚠ 库里存的 exemodel 是**周日打头**的 7 位串，这里刻意转成周一打头的数字，
	//   因为「1 是周一」是外部世界的共识（ISO 8601），
	//   把一个只有本系统知道的排列吐给第三方，一定会有人数错一位。
	Weekdays []int `json:"weekdays"`
	Priority int   `json:"priority"`
	Volume   int   `json:"volume"`
	// Media / Terminals 是这条任务播什么、播到哪。
	Media     []NamedItem `json:"media"`
	Terminals []NamedItem `json:"terminals"`
}

// NamedItem 是一个「编号 + 名字」的引用，回给调用方让他下次能直接拿去寻址。
type NamedItem struct {
	ID   int64  `json:"id"`
	Name string `json:"name"`
}

// TerminalBrief 是终端状态。
type TerminalBrief struct {
	ID   int64  `json:"id"`
	Name string `json:"name"`
	Type string `json:"type"`
	Zone string `json:"zone"`
	IP   string `json:"ip"`
	// Online 是网络在线；Playing 是正在播任务。离线的终端 Playing 恒为假。
	Online    bool   `json:"online"`
	Playing   bool   `json:"playing"`
	StateText string `json:"stateText"`
	Volume    int    `json:"volume"`
	// Running 是终端的**运行开关**（界面上的「启动终端 / 停止终端」，
	// 库里 devicestate）。
	//
	// ⚠ 早先这里叫 powerOn / powerStateText（「已开机 / 已关机」）—— 是错的。
	// devicestate 由 PUT /api/terminals/start|stop 控制，是运行状态，不是电源。
	// 叫 powerOn 会让集成方以为能靠它判断设备通没通电，而那是另一回事
	// （断电的终端表现为 netstate 离线）。名字错的字段比没有这个字段更坏。
	Running  bool   `json:"running"`
	RunState string `json:"runStateText"`
}

// MediaBrief 是媒体文件。
type MediaBrief struct {
	ID   int64  `json:"id"`
	Name string `json:"name"`
	// Seconds 是时长（秒）。0 表示库里没记时长，不是「零秒」。
	Seconds  int64  `json:"seconds"`
	SizeKB   int64  `json:"sizeKB"`
	FolderID int64  `json:"folderId"`
	Type     string `json:"type"`
}

// ScheduleBrief 是作息方案。
//
// ⚠ 方案没有编号 —— 它是一批任务共用的名字（task.info）。
// 所以这里只有 Name，寻址方案时也只能用名字。
type ScheduleBrief struct {
	Name string `json:"name"`
	// Tasks 是方案里有几条任务；Enabled 是其中处于启用状态的条数。
	Tasks   int `json:"tasks"`
	Enabled int `json:"enabled"`
}

// Page 是分页信封。
type Page[T any] struct {
	List     []T   `json:"list"`
	Total    int64 `json:"total"`
	PageNum  int   `json:"pageNum"`
	PageSize int   `json:"pageSize"`
}

// ListQuery 是三个列表接口共用的分页 + 关键字。
type ListQuery struct {
	Keyword  string
	PageNum  int
	PageSize int
	// FolderID / Zone 各自只对一个接口有意义，见下面各方法。
	FolderID int64
	Zone     Ref
}

// pager 把外部传的分页参数归一化。
//
// ⚠ 界面那套 store.NewPager 只认 10/18/20/50/100 这几个尺寸，别的一律回落到 18。
// 对界面这没问题（尺寸是下拉框选的），但对开发者接口是个陷阱：
// 传 pageSize=30 会**静默**变成 18，调用方翻页时会漏数据且毫无察觉。
// 所以这里自己夹取到 [1,200]，传多少就是多少。
func (q ListQuery) pager() store.Pager {
	size := q.PageSize
	if size <= 0 {
		size = 20
	}
	if size > 200 {
		size = 200
	}
	num := q.PageNum
	if num < 1 {
		num = 1
	}
	return store.Pager{PageNum: num, PageSize: size}
}

// ListTasks 列任务。
//
// 范围是**文件广播任务**——就是界面「任务管理」那一页列的东西（task.List 的
// fileTypes，BR-159）。作息方案里的打铃任务不在这里，它们在
// /openapi/v1/schedules 那一组：界面上就是两页，接口也照着分两组。
func (s *Service) ListTasks(ctx context.Context, u *auth.User, q ListQuery) (*Page[TaskBrief], error) {
	if s.tasks == nil {
		return nil, fmt.Errorf("任务服务未接入")
	}
	res, err := s.tasks.List(ctx, u, task.ListQuery{
		FolderID:    q.FolderID,
		SearchKey:   "taskname",
		Keyword:     q.Keyword,
		Pager:       q.pager(),
		WithDetails: true,
	})
	if err != nil {
		return nil, err
	}
	// 备注是 task.info 那一列，界面的 Item 里没带（列表页不显示它）。
	// 单独批量取一次，而不是往 task.Item 上加字段 —— 那个结构体是页面的合同，
	// 为了外部接口去动它，改的是所有人的东西。
	ids := make([]int64, 0, len(res.Items))
	for _, it := range res.Items {
		ids = append(ids, it.TaskID)
	}
	notes, err := s.taskNotes(ctx, ids)
	if err != nil {
		return nil, err
	}
	out := make([]TaskBrief, 0, len(res.Items))
	for _, it := range res.Items {
		b := taskBriefOf(it)
		b.Note = notes[it.TaskID]
		out = append(out, b)
	}
	p := q.pager()
	return &Page[TaskBrief]{List: out, Total: res.Total, PageNum: p.PageNum, PageSize: p.PageSize}, nil
}

func taskBriefOf(it task.Item) TaskBrief {
	b := TaskBrief{
		ID:        it.TaskID,
		Name:      it.TaskName,
		Enabled:   it.ProjectState == 0, // 0 = 启用，1 = 停用（BR：与直觉相反）
		Running:   it.State == 1,
		StateText: it.StateText,
		StartDate: it.StartDate,
		EndDate:   it.EndDate,
		PlayTime:  it.PlayTime,
		EndTime:   it.EndTime,
		Weekdays:  isoWeekdays(it.ExeModel),
		Priority:  it.Priority,
		Volume:    it.Volume,
		Media:     []NamedItem{},
		Terminals: []NamedItem{},
	}
	for _, m := range it.Media {
		b.Media = append(b.Media, NamedItem{ID: m.MediaID, Name: m.Name})
	}
	for _, t := range it.Terminals {
		b.Terminals = append(b.Terminals, NamedItem{ID: t.TerminalID, Name: t.TerminalName})
	}
	return b
}

// taskNotes 批量取「任务 → 备注（task.info）」。
func (s *Service) taskNotes(ctx context.Context, ids []int64) (map[int64]string, error) {
	out := map[int64]string{}
	if len(ids) == 0 {
		return out, nil
	}
	ph := strings.TrimSuffix(strings.Repeat("?,", len(ids)), ",")
	args := make([]any, 0, len(ids))
	for _, id := range ids {
		args = append(args, id)
	}
	rows, err := s.db.QueryContext(ctx,
		`SELECT taskid, COALESCE(info,'') FROM task WHERE taskid IN (`+ph+`)`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询任务备注: %w", err)
	}
	defer rows.Close()
	for rows.Next() {
		var id int64
		var info string
		if err := rows.Scan(&id, &info); err != nil {
			return nil, err
		}
		out[id] = info
	}
	return out, rows.Err()
}

// isoWeekdays 把库里的 exemodel 转成 1=周一 … 7=周日。
//
// ⚠ exemodel 是**周日打头**的 7 位串：位 0 是周日，位 1 是周一 … 位 6 是周六。
// "0000000" 表示手动任务 —— 后台永远不会自动触发它，所以返回空数组而不是
// 「一周都不播」，两者对调用方是同一个意思但空数组更不容易被误读成配置错误。
func isoWeekdays(exemodel string) []int {
	out := []int{}
	if len(exemodel) < 7 {
		return out
	}
	// 先周一到周六（位 1..6），最后周日（位 0）—— 这样输出天然是 1..7 升序
	for i := 1; i <= 6; i++ {
		if exemodel[i] == '1' {
			out = append(out, i)
		}
	}
	if exemodel[0] == '1' {
		out = append(out, 7)
	}
	return out
}

// ListTerminals 列终端状态。
func (s *Service) ListTerminals(ctx context.Context, u *auth.User, q ListQuery) (*Page[TerminalBrief], error) {
	if s.terminals == nil {
		return nil, fmt.Errorf("终端服务未接入")
	}
	var groupID int64
	if !q.Zone.empty() {
		id, err := s.resolveOne(ctx, u, s.specZone(), q.Zone)
		if err != nil {
			return nil, err
		}
		groupID = id
	}
	res, err := s.terminals.List(ctx, u, terminal.ListQuery{
		GroupID:   groupID,
		SearchKey: "terminalname",
		Keyword:   q.Keyword,
		Pager:     q.pager(),
	})
	if err != nil {
		return nil, err
	}
	out := make([]TerminalBrief, 0, len(res.Items))
	for _, it := range res.Items {
		online := it.NetState == 1
		playing := online && it.TaskState == 1
		out = append(out, TerminalBrief{
			ID:        it.ID,
			Name:      it.TerminalName,
			Type:      it.TypeName,
			Zone:      it.GroupName,
			IP:        it.IP,
			Online:    online,
			Playing:   playing,
			StateText: terminalStateText(online, playing),
			Volume:    it.Volume,
			// 措辞与界面终端列表一致（已启动 / 已停止），别自造一套说法
			Running:  it.DeviceState == 1,
			RunState: map[bool]string{true: "已启动", false: "已停止"}[it.DeviceState == 1],
		})
	}
	p := q.pager()
	return &Page[TerminalBrief]{List: out, Total: res.Total, PageNum: p.PageNum, PageSize: p.PageSize}, nil
}

func terminalStateText(online, playing bool) string {
	switch {
	case !online:
		return "离线"
	case playing:
		return "播放中"
	default:
		return "在线空闲"
	}
}

// ListMedia 列媒体文件。
//
// ⚠ 这一个**没有**交给 media.Service —— 它是本包里少数几处自己写 SQL 的地方之一，
// 理由要说清楚：media.List 是**按目录**列的（folderid = ?，目录不存在就报错），
// 因为界面上媒体库永远是「先进一个目录再看里面」。开发者接口不是这样用的：
// 对方想按名字找一首歌，他不知道也不该知道它在哪个目录。
//
// 可见范围与页面一致：媒体库是共用素材库，所有登录账号都看得到全部
// （folder.VisibleCond 恒为 1=1，见那边的说明）。能不能删仍然只看归属，
// 而这个接口是只读的，所以这里没有第二套规则。
// TTS 占位记录的排除口径也照抄 media 包，避免两处不一致。
func (s *Service) ListMedia(ctx context.Context, u *auth.User, q ListQuery) (*Page[MediaBrief], error) {
	where := `WHERE media.typeid <> 'tts' AND media.filename <> 'tts'`
	var args []any
	if q.FolderID > 0 {
		where += ` AND media.folderid = ?`
		args = append(args, q.FolderID)
	}
	if kw, err := trimName(q.Keyword); err != nil {
		return nil, err
	} else if kw != "" {
		where += ` AND media.name LIKE ? ESCAPE '\\'`
		args = append(args, store.EscapeLike(kw))
	}

	var total int64
	if err := s.db.QueryRowContext(ctx, `SELECT COUNT(*) FROM media `+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计媒体数: %w", err)
	}

	p := q.pager()
	listArgs := append(append([]any{}, args...), p.PageSize, p.Offset())
	rows, err := s.db.QueryContext(ctx,
		`SELECT media.id, COALESCE(media.name,''), COALESCE(media.timelength,0),
		        COALESCE(media.size,0), COALESCE(media.folderid,0), COALESCE(media.typeid,'')
		   FROM media `+where+` ORDER BY media.id DESC LIMIT ? OFFSET ?`, listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询媒体列表: %w", err)
	}
	defer rows.Close()
	out := []MediaBrief{}
	for rows.Next() {
		var b MediaBrief
		if err := rows.Scan(&b.ID, &b.Name, &b.Seconds, &b.SizeKB, &b.FolderID, &b.Type); err != nil {
			return nil, err
		}
		out = append(out, b)
	}
	if err := rows.Err(); err != nil {
		return nil, err
	}
	return &Page[MediaBrief]{List: out, Total: total, PageNum: p.PageNum, PageSize: p.PageSize}, nil
}

// ListSchedules 列作息方案。
//
// 方案不在任何一张表里，它是 task.info 的去重结果，所以这里直接查库
// 而不是走 bell.Service —— bell 的列表是按「方案里的任务」组织的，
// 转成「方案清单」要在外面再聚合一次，反而绕。范围与 bell 的 planScope 一致。
func (s *Service) ListSchedules(ctx context.Context, u *auth.User) ([]ScheduleBrief, error) {
	q := `SELECT COALESCE(info,'') AS plan,
	             COUNT(*) AS total,
	             SUM(CASE WHEN COALESCE(projectstate,0) = 0 THEN 1 ELSE 0 END) AS enabled
	        FROM task
	       WHERE tasktype IN (1,15) AND info <> '' AND channel = 0 AND sec_task_id = 0`
	var args []any
	if !u.IsAdmin {
		q += ` AND COALESCE(task_user_id,0) = ?`
		args = append(args, u.ID)
	}
	rows, err := s.db.QueryContext(ctx, q+` GROUP BY plan ORDER BY plan`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询作息方案: %w", err)
	}
	defer rows.Close()
	out := []ScheduleBrief{}
	for rows.Next() {
		var b ScheduleBrief
		if err := rows.Scan(&b.Name, &b.Tasks, &b.Enabled); err != nil {
			return nil, err
		}
		out = append(out, b)
	}
	return out, rows.Err()
}

// ScheduleDetail 是一个作息方案的全貌：几点打什么铃、打到哪些终端。
type ScheduleDetail struct {
	Name string `json:"name"`
	// Volume / Priority 是**方案级**属性：整个方案共用一份，不是每条一份。
	Volume    int             `json:"volume"`
	Priority  int             `json:"priority"`
	Terminals []NamedItem     `json:"terminals"`
	Items     []ScheduleEntry `json:"items"`
}

// ScheduleEntry 是方案里的一条：几点、放什么、放多久。
type ScheduleEntry struct {
	TaskID   int64  `json:"taskId"`
	Name     string `json:"name"`
	PlayTime string `json:"playTime"`
	Enabled  bool   `json:"enabled"`
	// Seconds 是播放时长（秒）。
	//
	// ⚠ 库里 timelengthtype 决定 timelength 的单位：1 = 秒，2 = 循环次数。
	//   按次数循环的条目**没有确定的秒数**，所以这里给 0 并在 LoopTimes 上给次数，
	//   而不是把次数当秒数吐出去 —— 那会让调用方算出来的下课时间差出十几分钟。
	Seconds   int         `json:"seconds"`
	LoopTimes int         `json:"loopTimes"`
	Weekdays  []int       `json:"weekdays"`
	StartDate string      `json:"startDate"`
	EndDate   string      `json:"endDate"`
	Media     []NamedItem `json:"media"`
}

// GetSchedule 取一个作息方案的详情。方案名对不上就报错，不猜。
func (s *Service) GetSchedule(ctx context.Context, u *auth.User, name string) (*ScheduleDetail, error) {
	if s.bells == nil {
		return nil, fmt.Errorf("作息方案服务未接入")
	}
	plan, err := s.resolveSchedule(ctx, u, name)
	if err != nil {
		return nil, err
	}
	d, err := s.bells.Get(ctx, u, plan)
	if err != nil {
		return nil, err
	}
	out := &ScheduleDetail{
		Name:      d.PlanName,
		Volume:    d.Playback.Volume,
		Priority:  d.Playback.Priority,
		Terminals: []NamedItem{},
		Items:     []ScheduleEntry{},
	}
	for _, t := range d.Terminals {
		out.Terminals = append(out.Terminals, NamedItem{ID: t.TerminalID, Name: t.TerminalName})
	}
	for _, it := range d.Items {
		e := ScheduleEntry{
			TaskID:    it.TaskID,
			Name:      it.TaskName,
			PlayTime:  it.PlayTime,
			Enabled:   it.ProjectState == 0,
			Weekdays:  isoWeekdays(it.ExeModel),
			StartDate: it.StartDate,
			EndDate:   it.EndDate,
			Media:     []NamedItem{},
		}
		for _, m := range it.Media {
			e.Media = append(e.Media, NamedItem{ID: m.MediaID, Name: m.Name})
		}
		if it.TimeLengthTy == 2 {
			e.LoopTimes = it.TimeLength
		} else {
			e.Seconds = it.TimeLength
		}
		out.Items = append(out.Items, e)
	}
	return out, nil
}

// trimName 收紧调用方传来的名字：去空白，并挡住 4 字节字符。
//
// ⚠ 连接字符集是 utf8（3 字节），emoji 这类 4 字节字符会在**协议层**报错，
// 表现是一句看不懂的 "Incorrect string value"。与其让调用方对着这句话猜，
// 不如在入口就说清楚。这条红线不能改：改成 utf8mb4 要动全库的表定义。
func trimName(v string) (string, error) {
	v = strings.TrimSpace(v)
	for _, r := range v {
		if r > 0xFFFF {
			return "", badf("名字里不能有表情符号等特殊字符：%q", string(r))
		}
	}
	return v, nil
}
