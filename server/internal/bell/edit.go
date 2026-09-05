package bell

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"regexp"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/store"
	"htweb/internal/task"
)

// 新增 / 修改作息方案（F-49）。
//
// 旧版对应四个模板：addbelltask.html(75KB)、modifybell.html(90KB)、
// modifybellall.html(93KB)、modifybelltime.html(32KB) —— 全系统最大的四个页面，
// 本质上是一张可动态增删行的课表编辑器，逻辑全部内联在模板 JS 里（D-175）。

var (
	reDate     = regexp.MustCompile(`^\d{4}-\d{2}-\d{2}$`)
	reTime     = regexp.MustCompile(`^\d{2}:\d{2}:\d{2}$`)
	reExeModel = regexp.MustCompile(`^[01]{7}$`)
	reArea     = regexp.MustCompile(`^[01]{1,16}$`)
)

// Schedule 是方案级的时间属性，组内每一行都相同（BR-224）。
type Schedule struct {
	StartDate string `json:"startdate"`
	EndDate   string `json:"enddate"`
	ExeModel  string `json:"exemodel"`
}

// Playback 是方案级的播放属性。
type Playback struct {
	Volume       int `json:"defaultvolume"`
	Priority     int `json:"priority"`
	PrePower     int `json:"prepower"`
	DataSendMode int `json:"datasendmodel"`
	// IsRandomPlay 对应 :80「添加方案」里的「播放模式（随机 / 顺序）」。
	// ⚠ 取值反直觉：**0 = 随机、1 = 顺序**（列注释「0表示随机1表示顺序」是对的，
	//    反直觉的是「0 竟然是随机」）。旧版建作息条目时写死 0，
	//    这里放开成可选，默认仍是 0，和旧版建出来的行一致。
	IsRandomPlay int `json:"israndomplay"`
}

// ItemInput 是一个打铃条目。
type ItemInput struct {
	// TaskID > 0 表示改已有条目；= 0 表示新增。
	TaskID       int64           `json:"taskid"`
	TaskName     string          `json:"taskname"`
	PlayTime     string          `json:"playtime"`
	TimeLengthTy int             `json:"timelengthtype"`
	TimeLength   int             `json:"timelength"`
	Media        []task.MediaRef `json:"media"`
}

// LEDConf 是方案级的 LED 字幕设置。
//
// 与文件广播那边的 LED 子任务同构（task/ledsub.go）：不是主任务上的一个开关列，
// 而是给**每个打铃条目**各挂一条 tasktype = 30 的子任务，sec_task_id 指回条目，
// 字幕正文写在 ledsentence 里。方案里所有条目共用同一段字幕与同一个速度 ——
// 界面上它就摆在预开电源、音量这些方案级属性旁边。
//
// Text 为空即表示这个方案不要 LED。
type LEDConf struct {
	Text  string `json:"text"`
	Speed int    `json:"speed"`
}

func (c *LEDConf) wanted() bool { return c != nil && strings.TrimSpace(c.Text) != "" }

// PlanInput 是新建 / 保存整个方案的入参。
type PlanInput struct {
	PlanName  string
	Schedule  Schedule
	Playback  Playback
	Terminals []task.TerminalRef
	Items     []ItemInput
	LED       *LEDConf
}

// Item 是方案详情里的一个条目。
type Item struct {
	TaskID       int64            `json:"taskid"`
	TaskName     string           `json:"taskname"`
	PlayTime     string           `json:"playtime"`
	TimeLengthTy int              `json:"timelengthtype"`
	TimeLength   int              `json:"timelength"`
	ProjectState int              `json:"projectstate"`
	StateText    string           `json:"projectStateText"`
	Media        []task.MediaItem `json:"media"`
	// PowerTaskID 是这一条目配套的功放子任务，0 表示没有。
	PowerTaskID   int64  `json:"powerTaskId"`
	PowerPlayTime string `json:"powerPlayTime"`
	// DuplicateTime 表示同方案里还有别的条目排在同一时刻（只提示，不拦截）。
	DuplicateTime bool `json:"duplicateTime"`
	// 起止日期与星期掩码理论上组内一致（方案级），但可以被「智能排课」按条目改掉，
	// 所以这里逐条目也给一份，界面才好显示各条目当前排在哪个日期段。
	StartDate string `json:"startdate"`
	EndDate   string `json:"enddate"`
	ExeModel  string `json:"exemodel"`
}

// Detail 是方案编辑页需要的全部数据。
type Detail struct {
	PlanName  string              `json:"planName"`
	Schedule  Schedule            `json:"schedule"`
	Playback  Playback            `json:"playback"`
	Terminals []task.TerminalItem `json:"terminals"`
	Items     []Item              `json:"items"`
	OwnerID   int64               `json:"ownerUserId"`
	// LED 是方案的 LED 字幕设置，没挂字幕时为 nil。
	LED *LEDConf `json:"led"`
	// MixedAttrs 列出组内取值不一致的方案级属性。
	// 方案级属性理论上组内一致，但旧版逐行 UPDATE 且无事务（D-169/D-170），
	// 中途失败就会留下一半新一半旧的数据。这里如实报出来而不是随便取一行。
	MixedAttrs  []string `json:"mixedAttrs"`
	PriorityMin int      `json:"priorityMin"`
	PriorityMax int      `json:"priorityMax"`
}

type SaveResult struct {
	PlanName      string   `json:"planName"`
	CreatedItems  int      `json:"createdItems"`
	UpdatedItems  int      `json:"updatedItems"`
	DeletedItems  int      `json:"deletedItems"`
	TaskIDs       []int64  `json:"taskIds"`
	PowerTaskIDs  []int64  `json:"powerTaskIds"`
	LEDTaskIDs    []int64  `json:"ledTaskIds"`
	TerminalRows  int      `json:"terminalRows"`
	Warnings      []string `json:"warnings"`
	NotifyTaskIDs []int64  `json:"-"`
	// Volume 是发通知时要带的 &volume= —— 后台服务据此设置任务初始音量。
	// 不带（或带 0）会让它把音量设成 0，任务照常触发但一点声音都没有。
	Volume int `json:"-"`
}

// ---------- 读 ----------

// Get 取方案详情。
func (s *Service) Get(ctx context.Context, u *auth.User, planName string) (*Detail, error) {
	owner, err := s.assertPlan(ctx, u, planName)
	if err != nil {
		return nil, err
	}
	d := &Detail{
		PlanName:   planName,
		OwnerID:    owner,
		Terminals:  []task.TerminalItem{},
		Items:      []Item{},
		MixedAttrs: []string{},
	}

	rows, err := s.db.QueryContext(ctx, `
		SELECT taskid, COALESCE(taskname,''), TIME_FORMAT(playtime,'%H:%i:%s'),
		       COALESCE(timelengthtype,1), COALESCE(timelength,0), COALESCE(projectstate,0),
		       COALESCE(DATE_FORMAT(startdate,'%Y-%m-%d'),''),
		       COALESCE(DATE_FORMAT(enddate,'%Y-%m-%d'),''),
		       COALESCE(exemodel,'0000000'), COALESCE(defaultvolume,80),
		       COALESCE(priority,0), COALESCE(prepower,0), COALESCE(datasendmodel,0),
		       COALESCE(israndomplay,0)
		FROM task WHERE info = ? AND `+planScope("")+`
		ORDER BY playtime, taskid`, planName)
	if err != nil {
		return nil, fmt.Errorf("查询方案条目: %w", err)
	}
	defer rows.Close()

	type attrs struct {
		start, end, exe         string
		vol, pri, pre, snd, rnd int
	}
	var first *attrs
	mixed := map[string]bool{}
	ids := []int64{}
	times := map[string]int{}

	for rows.Next() {
		var it Item
		var a attrs
		if err := rows.Scan(&it.TaskID, &it.TaskName, &it.PlayTime,
			&it.TimeLengthTy, &it.TimeLength, &it.ProjectState,
			&a.start, &a.end, &a.exe, &a.vol, &a.pri, &a.pre, &a.snd, &a.rnd); err != nil {
			return nil, err
		}
		it.StateText = stateText(it.ProjectState)
		it.StartDate, it.EndDate, it.ExeModel = a.start, a.end, a.exe
		it.Media = []task.MediaItem{}
		ids = append(ids, it.TaskID)
		times[it.PlayTime]++

		if first == nil {
			first = &a
			d.Schedule = Schedule{StartDate: a.start, EndDate: a.end, ExeModel: a.exe}
			d.Playback = Playback{Volume: a.vol, Priority: a.pri, PrePower: a.pre,
				DataSendMode: a.snd, IsRandomPlay: a.rnd}
		} else {
			for name, differs := range map[string]bool{
				"起始日期":  a.start != first.start,
				"结束日期":  a.end != first.end,
				"星期":    a.exe != first.exe,
				"音量":    a.vol != first.vol,
				"优先级":   a.pri != first.pri,
				"提前开电源": a.pre != first.pre,
				"发送模式":  a.snd != first.snd,
				"播放模式":  a.rnd != first.rnd,
			} {
				if differs {
					mixed[name] = true
				}
			}
		}
		d.Items = append(d.Items, it)
	}
	if err := rows.Err(); err != nil {
		return nil, err
	}
	for name := range mixed {
		d.MixedAttrs = append(d.MixedAttrs, name)
	}
	for i := range d.Items {
		d.Items[i].DuplicateTime = times[d.Items[i].PlayTime] > 1
	}

	if err := s.fillItemMedia(ctx, d.Items, ids); err != nil {
		return nil, err
	}
	if err := s.fillItemPower(ctx, d.Items, ids); err != nil {
		return nil, err
	}
	// 终端清单方案内每条任务各写一份，取代表条目的那一份即可
	if len(ids) > 0 {
		led, err := s.loadPlanLED(ctx, ids[0])
		if err != nil {
			return nil, err
		}
		d.LED = led
		if err := s.fillPlanTerminals(ctx, d, ids[0]); err != nil {
			return nil, err
		}
	}
	lo, hi, err := s.priorityRange(ctx, owner)
	if err != nil {
		return nil, err
	}
	d.PriorityMin, d.PriorityMax = lo, hi
	return d, nil
}

// fillItemMedia 一条 IN 查询取回所有条目的铃声。
// LEFT JOIN：媒体被删掉之后这一项要仍然显示并标记「已删除」，
// 旧版的内连接会让它凭空消失，用户查不出「为什么这节课不响」。
func (s *Service) fillItemMedia(ctx context.Context, items []Item, ids []int64) error {
	if len(ids) == 0 {
		return nil
	}
	ph, args := placeholders(ids)
	rows, err := s.db.QueryContext(ctx, `
		SELECT mot.taskid, mot.mediaid, COALESCE(mot.sort,0),
		       m.id IS NOT NULL, COALESCE(m.name,''), COALESCE(m.size,0)
		FROM mediaoftask mot
		LEFT JOIN media m ON m.id = mot.mediaid
		WHERE mot.taskid IN (`+ph+`)
		ORDER BY mot.taskid, mot.sort, mot.id`, args...)
	if err != nil {
		return fmt.Errorf("查询条目铃声: %w", err)
	}
	defer rows.Close()

	byTask := map[int64][]task.MediaItem{}
	for rows.Next() {
		var tid int64
		var m task.MediaItem
		var exists bool
		if err := rows.Scan(&tid, &m.MediaID, &m.Sort, &exists, &m.Name, &m.Size); err != nil {
			return err
		}
		if !exists {
			m.Deleted, m.Name = true, "(媒体已删除)"
		}
		byTask[tid] = append(byTask[tid], m)
	}
	if err := rows.Err(); err != nil {
		return err
	}
	for i := range items {
		if list := byTask[items[i].TaskID]; list != nil {
			items[i].Media = list
		}
	}
	return nil
}

// fillItemPower 关联每个条目的功放子任务。
func (s *Service) fillItemPower(ctx context.Context, items []Item, ids []int64) error {
	if len(ids) == 0 {
		return nil
	}
	ph, args := placeholders(ids)
	rows, err := s.db.QueryContext(ctx,
		`SELECT sec_task_id, taskid, TIME_FORMAT(playtime,'%H:%i:%s')
		 FROM task WHERE sec_task_id IN (`+ph+`) AND tasktype = ?`,
		append(args, PowerType)...)
	if err != nil {
		return fmt.Errorf("查询功放子任务: %w", err)
	}
	defer rows.Close()

	type pw struct {
		id int64
		t  string
	}
	byMain := map[int64]pw{}
	for rows.Next() {
		var main int64
		var p pw
		if err := rows.Scan(&main, &p.id, &p.t); err != nil {
			return err
		}
		byMain[main] = p
	}
	if err := rows.Err(); err != nil {
		return err
	}
	for i := range items {
		if p, ok := byMain[items[i].TaskID]; ok {
			items[i].PowerTaskID, items[i].PowerPlayTime = p.id, p.t
		}
	}
	return nil
}

// ledTypeArgs 给 SQL 用的 LED 子任务类型占位符。
func ledTypeArgs() (string, []interface{}) {
	types := task.LEDSubTypes()
	ph := make([]string, len(types))
	args := make([]interface{}, len(types))
	for i, t := range types {
		ph[i], args[i] = "?", t
	}
	return strings.Join(ph, ","), args
}

// loadPlanLED 读方案的 LED 字幕设置。方案里每个条目各挂一条子任务，
// 内容是同一份，所以取样一条即可（拿主任务 sampleTaskID 的那条子任务）。
func (s *Service) loadPlanLED(ctx context.Context, sampleTaskID int64) (*LEDConf, error) {
	ph, args := ledTypeArgs()
	var text string
	var speed int
	err := s.db.QueryRowContext(ctx, `
		SELECT COALESCE(ls.text,''), COALESCE(ls.speed,0)
		FROM task t
		JOIN mediaoftask mt ON mt.taskid = t.taskid
		JOIN ledsentence ls ON ls.mediaid = mt.mediaid
		WHERE t.sec_task_id = ? AND t.tasktype IN (`+ph+`)
		ORDER BY ls.mediaseq, ls.id LIMIT 1`,
		append([]interface{}{sampleTaskID}, args...)...).Scan(&text, &speed)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, nil
	}
	if err != nil {
		return nil, fmt.Errorf("查询 LED 字幕: %w", err)
	}
	return &LEDConf{Text: text, Speed: speed}, nil
}

// planLEDSubIDs 列出整个方案的 LED 子任务。
func planLEDSubIDs(ctx context.Context, tx *sql.Tx, planName string) ([]int64, error) {
	ph, args := ledTypeArgs()
	return collectIDs(ctx, tx,
		`SELECT taskid FROM task WHERE info = ? AND channel = 0 AND tasktype IN (`+ph+`)`,
		append([]interface{}{planName}, args...)...)
}

// resyncLED 让整个方案的 LED 子任务与新设置一致：整批删掉再按需重建。
//
// 逐列比对不划算 —— 一个方案的 LED 子任务顶多和条目一样多，重建代价极小，
// 而且与终端清单、铃声清单「全删重插」的策略一致。
func resyncLED(ctx context.Context, tx *sql.Tx, in PlanInput, ownerID int64) error {
	old, err := planLEDSubIDs(ctx, tx, in.PlanName)
	if err != nil {
		return err
	}
	if err := purgeTaskRows(ctx, tx, old); err != nil {
		return err
	}
	if !in.LED.wanted() {
		return nil
	}
	rows, err := tx.QueryContext(ctx, `
		SELECT taskid, COALESCE(taskname,''), TIME_FORMAT(playtime,'%H:%i:%s'),
		       COALESCE(timelengthtype,1), COALESCE(timelength,0)
		FROM task WHERE info = ? AND `+planScope(""), in.PlanName)
	if err != nil {
		return fmt.Errorf("查询方案条目: %w", err)
	}
	type item struct {
		id int64
		it ItemInput
	}
	list := []item{}
	for rows.Next() {
		var e item
		if err := rows.Scan(&e.id, &e.it.TaskName, &e.it.PlayTime, &e.it.TimeLengthTy, &e.it.TimeLength); err != nil {
			rows.Close()
			return err
		}
		list = append(list, e)
	}
	rows.Close()
	if err := rows.Err(); err != nil {
		return err
	}
	for i := range list {
		ledID, err := insertLEDSub(ctx, tx, in, &list[i].it, list[i].id, ownerID)
		if err != nil {
			return err
		}
		// 终端清单直接照抄主条目：这条路径可能是「只改方案属性、不动终端」，
		// 那时 in.Terminals 是空的，照它写就成了没有终端的字幕任务
		if _, err := tx.ExecContext(ctx, `
			INSERT INTO terminaloftask (taskid, terminalid, groupid, area)
			SELECT ?, terminalid, groupid, area FROM terminaloftask WHERE taskid = ?`,
			ledID, list[i].id); err != nil {
			return fmt.Errorf("写入 LED 子任务终端: %w", err)
		}
	}
	return nil
}

func (s *Service) fillPlanTerminals(ctx context.Context, d *Detail, sampleTaskID int64) error {
	rows, err := s.db.QueryContext(ctx, `
		SELECT ot.terminalid, COALESCE(ot.groupid,0), COALESCE(ot.area,''),
		       tm.id IS NOT NULL, COALESCE(tm.terminalname,''), COALESCE(tt.name,''),
		       COALESCE(tm.netstate,0), COALESCE(tm.taskstate,0),
		       COALESCE(tm.ip,''), COALESCE(tm.volume,0)
		FROM terminaloftask ot
		LEFT JOIN terminal tm ON tm.id = ot.terminalid
		LEFT JOIN terminaltype tt ON tt.id = tm.typeid
		WHERE ot.taskid = ?
		ORDER BY ot.id`, sampleTaskID)
	if err != nil {
		return fmt.Errorf("查询方案终端清单: %w", err)
	}
	defer rows.Close()

	for rows.Next() {
		var t task.TerminalItem
		var exists bool
		if err := rows.Scan(&t.TerminalID, &t.GroupID, &t.Area, &exists,
			&t.TerminalName, &t.TypeName, &t.NetState, &t.TaskState,
			&t.IP, &t.Volume); err != nil {
			return err
		}
		if !exists {
			t.Deleted, t.TerminalName = true, "(终端已删除)"
		}
		d.Terminals = append(d.Terminals, t)
	}
	return rows.Err()
}

// priorityRange 计算某个用户能选的任务级别区间。
//
// 依据是旧版那几张表单里的下拉：
//
//	for(level=$getlevel; level<=109; level++) document.write("<option>"+level)
//
// 其中 $getlevel 直接取 `usergroup.level`（不是十位数，是整数本身），
// 上限恒为 **109**。现网三个用户组的 level 是 10 / 5 / 1，
// 也就是说这个下拉在旧版里是 10~109 / 5~109 / 1~109。
//
// ⚠ 新版把下限统一抬到 10：数字越小级别越高（界面上写着「10 为最高级别」），
// 让权限更低的组反而能选到比管理员更高的级别没有道理。
// 现网三个组按这条规则都是 10~109，与「所有任务的任务级别是 10~109」一致。
func (s *Service) priorityRange(ctx context.Context, userID int64) (int, int, error) {
	var level int
	err := s.db.QueryRowContext(ctx, `
		SELECT COALESCE(g.level,0) FROM book_admin b
		LEFT JOIN usergroup g ON g.id = b.usergroupid
		WHERE b.id = ? LIMIT 1`, userID).Scan(&level)
	if errors.Is(err, sql.ErrNoRows) {
		return task.PriorityFloor, task.PriorityCeil, nil
	}
	if err != nil {
		return 0, 0, fmt.Errorf("查询用户组级别: %w", err)
	}
	if level < task.PriorityFloor {
		level = task.PriorityFloor
	}
	if level > task.PriorityCeil {
		level = task.PriorityCeil
	}
	return level, task.PriorityCeil, nil
}

// ---------- 校验 ----------

func (s *Service) validate(ctx context.Context, u *auth.User, in *PlanInput, ownerID int64, oldPriority *int) error {
	if !reDate.MatchString(in.Schedule.StartDate) || !reDate.MatchString(in.Schedule.EndDate) {
		return fmt.Errorf("起止日期格式不正确，应为 YYYY-MM-DD")
	}
	if in.Schedule.EndDate < in.Schedule.StartDate {
		return fmt.Errorf("结束日期不能早于开始日期")
	}
	if !reExeModel.MatchString(in.Schedule.ExeModel) {
		return fmt.Errorf("星期掩码必须是 7 位的 0/1 字符串，例如 1111100")
	}
	if in.Playback.Volume < 0 || in.Playback.Volume > 100 {
		return fmt.Errorf("音量必须在 0 ~ 100 之间")
	}
	// prepower 单位是秒，与 task 模块同一套判据（见 task.shiftTime 的注释）
	if in.Playback.PrePower < 0 || in.Playback.PrePower > 3600 {
		return fmt.Errorf("提前开电源必须在 0 ~ 3600 秒之间")
	}
	if in.Playback.DataSendMode != 0 && in.Playback.DataSendMode != 1 {
		return fmt.Errorf("发送模式只能是 0（单播）或 1（组播）")
	}
	if err := s.checkPriority(ctx, ownerID, in.Playback.Priority, oldPriority); err != nil {
		return err
	}
	if err := checkLED(in.LED); err != nil {
		return err
	}

	if len(in.Items) == 0 {
		return fmt.Errorf("请至少添加一个打铃条目")
	}
	if len(in.Items) > 500 {
		return fmt.Errorf("打铃条目最多 500 条")
	}
	names := map[string]bool{}
	mediaIDs := []int64{}
	for i := range in.Items {
		it := &in.Items[i]
		it.TaskName = strings.TrimSpace(it.TaskName)
		if it.TaskName == "" {
			return fmt.Errorf("第 %d 个条目的名称不能为空", i+1)
		}
		if len(it.TaskName) > 255 {
			return fmt.Errorf("条目名称过长：%q 按 UTF-8 计 %d 字节，上限 255 字节", it.TaskName, len(it.TaskName))
		}
		// 旧版按 (info, taskname) 定位条目做 upsert（do.php:11848），
		// 同名条目会互相覆盖，所以方案内条目名必须唯一。
		if names[it.TaskName] {
			return fmt.Errorf("方案内有重名的条目：%q", it.TaskName)
		}
		names[it.TaskName] = true

		if !reTime.MatchString(it.PlayTime) {
			return fmt.Errorf("第 %d 个条目的打铃时间格式不正确，应为 HH:MM:SS", i+1)
		}
		if it.TimeLengthTy != 1 && it.TimeLengthTy != 2 {
			return fmt.Errorf("时长类型只能是 1（按秒数）或 2（按循环次数）")
		}
		if it.TimeLength < 0 || it.TimeLength > 86400 {
			return fmt.Errorf("时长/次数必须在 0 ~ 86400 之间")
		}
		seen := map[int64]bool{}
		for _, m := range it.Media {
			if m.MediaID <= 0 {
				return fmt.Errorf("第 %d 个条目的铃声里有非法的媒体 ID", i+1)
			}
			if seen[m.MediaID] {
				return fmt.Errorf("第 %d 个条目的铃声有重复项", i+1)
			}
			seen[m.MediaID] = true
			mediaIDs = append(mediaIDs, m.MediaID)
		}
	}
	if err := s.assertMediaExist(ctx, mediaIDs); err != nil {
		return err
	}
	return s.validateTerminals(ctx, u, in.Terminals)
}

// LEDSpeedMax 是界面上「Led速度」的上限：0 ~ 5 级。
// 旧版根本没有这个输入框，写库时把 speed 写死成 5（do.php 里六处 INSERT ledsentence
// 都是 '5'），所以 5 既是上限也是默认值。
const LEDSpeedMax = 5

// ledTextLimit 与 ledsentence.text 的列宽一致（varchar(1024)）。
const ledTextLimit = 1024

func checkLED(c *LEDConf) error {
	if !c.wanted() {
		return nil
	}
	if len(strings.TrimSpace(c.Text)) > ledTextLimit {
		return fmt.Errorf("Led字幕过长：按 UTF-8 计 %d 字节，上限 %d 字节（约 341 个汉字）",
			len(strings.TrimSpace(c.Text)), ledTextLimit)
	}
	if c.Speed < 0 || c.Speed > LEDSpeedMax {
		return fmt.Errorf("Led速度必须在 0 ~ %d 之间", LEDSpeedMax)
	}
	return nil
}

func (s *Service) assertMediaExist(ctx context.Context, ids []int64) error {
	if len(ids) == 0 {
		return nil
	}
	uniq := map[int64]bool{}
	list := []int64{}
	for _, id := range ids {
		if !uniq[id] {
			uniq[id] = true
			list = append(list, id)
		}
	}
	ph, args := placeholders(list)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM media WHERE id IN (`+ph+`)`, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验铃声: %w", err)
	}
	if n != len(list) {
		return fmt.Errorf("铃声里有已不存在的媒体，请重新选择")
	}
	return nil
}

func (s *Service) validateTerminals(ctx context.Context, u *auth.User, terms []task.TerminalRef) error {
	if len(terms) == 0 {
		return fmt.Errorf("请至少选择一个终端")
	}
	if len(terms) > 3000 {
		return fmt.Errorf("终端清单最多 3000 条")
	}
	seen := map[int64]bool{}
	ids := make([]int64, 0, len(terms))
	for i := range terms {
		t := &terms[i]
		if t.TerminalID <= 0 {
			return fmt.Errorf("终端清单里有非法的终端 ID")
		}
		if seen[t.TerminalID] {
			return fmt.Errorf("终端清单里有重复项")
		}
		seen[t.TerminalID] = true
		ids = append(ids, t.TerminalID)
		if t.Area == "" {
			t.Area = task.AreaAll
		}
		if !reArea.MatchString(t.Area) {
			return fmt.Errorf("终端区域掩码必须是 1~16 位的 0/1 字符串")
		}
		if t.GroupID < 0 {
			t.GroupID = 0
		}
	}
	ph, args := placeholders(ids)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM terminal WHERE id IN (`+ph+`)`, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验终端: %w", err)
	}
	if n != len(ids) {
		return fmt.Errorf("终端清单里有已不存在的终端，请重新选择")
	}
	if !u.IsAdmin {
		var bound int
		bindArgs := append(append([]interface{}{}, args...), u.ID)
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(DISTINCT terminalid) FROM userterminal
			 WHERE terminalid IN (`+ph+`) AND userid = ?`, bindArgs...).Scan(&bound); err != nil {
			return fmt.Errorf("校验终端归属: %w", err)
		}
		if bound != len(ids) {
			return fmt.Errorf("终端清单里有未绑定给你的终端")
		}
	}
	return nil
}

// ---------- 写 ----------

// Create 新建方案，含首批条目。
func (s *Service) Create(ctx context.Context, u *auth.User, in PlanInput) (*SaveResult, error) {
	name, err := checkPlanName(in.PlanName)
	if err != nil {
		return nil, err
	}
	in.PlanName = name
	if err := s.validate(ctx, u, &in, u.ID, nil); err != nil {
		return nil, err
	}

	unlock, err := store.Lock(ctx, s.db, planLock)
	if err != nil {
		return nil, err
	}
	defer unlock()

	// 旧版只在「同名方案属于别人」时才拒（do.php:11833），同用户同名会被
	// GROUP BY info 聚成一个方案、条目混在一起、启停互相影响（D-168）。
	// 新版全局唯一：想往已有方案里加条目请走「添加条目」入口。
	if err := s.planNameFree(ctx, in.PlanName); err != nil {
		return nil, err
	}
	return s.saveItems(ctx, in, u.ID)
}

// saveItems 把一批条目连同它们的铃声、终端写进方案，全程一个事务。
func (s *Service) saveItems(ctx context.Context, in PlanInput, ownerID int64) (*SaveResult, error) {
	out := &SaveResult{PlanName: in.PlanName, TaskIDs: []int64{}, PowerTaskIDs: []int64{},
		LEDTaskIDs: []int64{}, Warnings: []string{}, NotifyTaskIDs: []int64{}, Volume: in.Playback.Volume}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	for i := range in.Items {
		it := &in.Items[i]
		mainID, powerID, err := insertItem(ctx, tx, in, it, ownerID)
		if err != nil {
			return nil, err
		}
		out.TaskIDs = append(out.TaskIDs, mainID)
		out.NotifyTaskIDs = append(out.NotifyTaskIDs, mainID)
		out.CreatedItems++
		if powerID > 0 {
			out.PowerTaskIDs = append(out.PowerTaskIDs, powerID)
		}

		if err := writeMedia(ctx, tx, mainID, it.Media); err != nil {
			return nil, err
		}
		ledID, err := insertLEDSub(ctx, tx, in, it, mainID, ownerID)
		if err != nil {
			return nil, err
		}
		if ledID > 0 {
			out.LEDTaskIDs = append(out.LEDTaskIDs, ledID)
		}
		rows, err := writeTerminals(ctx, tx, mainID, powerID, in.Terminals)
		if err != nil {
			return nil, err
		}
		if ledID > 0 {
			// 字幕要发到与打铃同一批终端上（与功放子任务同理）
			n, err := writeTerminals(ctx, tx, ledID, 0, in.Terminals)
			if err != nil {
				return nil, err
			}
			rows += n
		}
		out.TerminalRows += rows
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	out.Warnings = append(out.Warnings, duplicateTimeWarnings(in.Items)...)
	return out, nil
}

// duplicateTimeWarnings 提示同一时刻的多个条目。
// 只提示不拦截：现网方案「333」两个条目都排在 08:00:00，拦了连编辑都进不去。
func duplicateTimeWarnings(items []ItemInput) []string {
	times := map[string][]string{}
	for _, it := range items {
		times[it.PlayTime] = append(times[it.PlayTime], it.TaskName)
	}
	out := []string{}
	for t, names := range times {
		if len(names) > 1 {
			out = append(out, fmt.Sprintf("%s 有 %d 个条目同时打铃：%s",
				t, len(names), strings.Join(names, "、")))
		}
	}
	return out
}

// insertItem 写一条打铃条目，prepower > 0 时连同功放子任务一起写。
//
// normRandom 把播放模式收敛到 0/1。⚠ 0 = 随机、1 = 顺序，别看反。
func normRandom(v int) int {
	if v == 1 {
		return 1
	}
	return 0
}

// 旧版这里先 `LOCK TABLE task WRITE` 再 `SELECT MAX(taskid)`（D-172）：
// 锁表拿自增值，既慢又在并发下不可靠。用 LastInsertId 即可。
func insertItem(ctx context.Context, tx *sql.Tx, in PlanInput, it *ItemInput,
	ownerID int64) (int64, int64, error) {

	res, err := tx.ExecContext(ctx, `
		INSERT INTO task (taskname, israndomplay, projectstate, timelengthtype, timelength,
		                  prepower, datasendmodel, state, startdate, enddate,
		                  playtime, endtime, exemodel, priority, tasktype, channel,
		                  bandrate, samplerate, cmd, cmdargs, playfileid, info,
		                  defaultvolume, task_user_id, sec_task_id, parentid, offlinestate)
		VALUES (?,?,?,?,?, ?,?,0,?,?, ?, '00:00:00', ?,?,?,0, 0,0,0,'0',0,?, ?,?,0,0,0)`,
		it.TaskName, normRandom(in.Playback.IsRandomPlay), task.StateEnabled, it.TimeLengthTy, it.TimeLength,
		in.Playback.PrePower, in.Playback.DataSendMode,
		in.Schedule.StartDate, in.Schedule.EndDate,
		it.PlayTime, in.Schedule.ExeModel, in.Playback.Priority, ItemType,
		in.PlanName, in.Playback.Volume, ownerID)
	if err != nil {
		return 0, 0, fmt.Errorf("写入打铃条目: %w", err)
	}
	mainID, err := res.LastInsertId()
	if err != nil {
		return 0, 0, err
	}
	if in.Playback.PrePower <= 0 {
		return mainID, 0, nil
	}

	powerTime, err := task.ShiftTime(it.PlayTime, -in.Playback.PrePower)
	if err != nil {
		return 0, 0, err
	}
	// 功放子任务不写 projectstate（旧版 addonebellplan.php:254 的列清单里就没有它），
	// 走列默认值 0 = 启用，与主任务一致。
	res, err = tx.ExecContext(ctx, `
		INSERT INTO task (taskname, israndomplay, timelengthtype, timelength,
		                  prepower, datasendmodel, state, startdate, enddate,
		                  playtime, endtime, exemodel, priority, tasktype, channel,
		                  bandrate, samplerate, cmd, cmdargs, playfileid, info,
		                  defaultvolume, task_user_id, sec_task_id, parentid, offlinestate)
		VALUES (?,?,?,?, ?,?,0,?,?, ?, '00:00:00', ?,?,?,0, 0,0,0,'0',0,?, ?,?,?,0,0)`,
		it.TaskName, normRandom(in.Playback.IsRandomPlay), it.TimeLengthTy, it.TimeLength,
		in.Playback.PrePower, in.Playback.DataSendMode,
		in.Schedule.StartDate, in.Schedule.EndDate,
		powerTime, in.Schedule.ExeModel, in.Playback.Priority, PowerType,
		in.PlanName, in.Playback.Volume, ownerID, mainID)
	if err != nil {
		return 0, 0, fmt.Errorf("写入功放子任务: %w", err)
	}
	powerID, err := res.LastInsertId()
	if err != nil {
		return 0, 0, err
	}
	return mainID, powerID, nil
}

// insertLEDSub 给一个打铃条目挂 LED 字幕子任务。方案没配字幕时返回 0。
//
// 除 tasktype / sec_task_id 外整行照抄主条目，playtime 也与主条目相同 ——
// 与 task/ledsub.go 的判断一致（字幕跟着播放内容走）。
func insertLEDSub(ctx context.Context, tx *sql.Tx, in PlanInput, it *ItemInput,
	mainID, ownerID int64) (int64, error) {

	if !in.LED.wanted() {
		return 0, nil
	}
	res, err := tx.ExecContext(ctx, `
		INSERT INTO task (taskname, israndomplay, projectstate, timelengthtype, timelength,
		                  prepower, datasendmodel, state, startdate, enddate,
		                  playtime, endtime, exemodel, priority, tasktype, channel,
		                  bandrate, samplerate, cmd, cmdargs, playfileid, info,
		                  defaultvolume, task_user_id, sec_task_id, parentid, offlinestate)
		VALUES (?,?,?,?,?, ?,?,0,?,?, ?, '00:00:00', ?,?,?,0, 0,0,0,'0',0,?, ?,?,?,0,0)`,
		it.TaskName, normRandom(in.Playback.IsRandomPlay), task.StateEnabled, it.TimeLengthTy, it.TimeLength,
		in.Playback.PrePower, in.Playback.DataSendMode,
		in.Schedule.StartDate, in.Schedule.EndDate,
		it.PlayTime, in.Schedule.ExeModel, in.Playback.Priority, LEDType,
		in.PlanName, in.Playback.Volume, ownerID, mainID)
	if err != nil {
		return 0, fmt.Errorf("写入 LED 子任务: %w", err)
	}
	ledID, err := res.LastInsertId()
	if err != nil {
		return 0, err
	}
	sub := &task.LEDSub{Name: it.TaskName, Text: task.NormalizeLEDText(in.LED.Text), Speed: in.LED.Speed}
	if err := task.WriteLEDContent(ctx, tx, ledID, sub); err != nil {
		return 0, err
	}
	return ledID, nil
}

func writeMedia(ctx context.Context, tx *sql.Tx, taskID int64, media []task.MediaRef) error {
	for i, m := range media {
		sort := m.Sort
		if sort == 0 {
			sort = i // sort 是 0 基（BR-167 记的 1 基是错的，现网存量数据也是 0 基）
		}
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO mediaoftask (mediaid, taskid, sort) VALUES (?,?,?)`,
			m.MediaID, taskID, sort); err != nil {
			return fmt.Errorf("写入条目铃声: %w", err)
		}
	}
	return nil
}

// writeTerminals 为主任务与功放子任务各写一份终端清单（BR-227）。
//
// 旧版是逐条 INSERT：18 节课 × 50 终端 × 2（主 + 功放）= 1800 次往返（D-171）。
// 这里一条语句多组 VALUES，按 500 一批切分。
func writeTerminals(ctx context.Context, tx *sql.Tx, mainID, powerID int64,
	terms []task.TerminalRef) (int, error) {

	targets := []int64{mainID}
	if powerID > 0 {
		targets = append(targets, powerID)
	}
	total := 0
	for _, tid := range targets {
		for start := 0; start < len(terms); start += 500 {
			end := start + 500
			if end > len(terms) {
				end = len(terms)
			}
			batch := terms[start:end]
			var sb strings.Builder
			sb.WriteString(`INSERT INTO terminaloftask (taskid, terminalid, workstate, groupid, area) VALUES `)
			args := make([]interface{}, 0, len(batch)*5)
			for i, t := range batch {
				if i > 0 {
					sb.WriteByte(',')
				}
				sb.WriteString("(?,?,0,?,?)")
				args = append(args, tid, t.TerminalID, t.GroupID, t.Area)
			}
			if _, err := tx.ExecContext(ctx, sb.String(), args...); err != nil {
				return 0, fmt.Errorf("写入终端清单: %w", err)
			}
			total += len(batch)
		}
	}
	return total, nil
}

// ---------- 改方案级属性 / 改名 ----------

type UpdateInput struct {
	PlanName    string
	NewPlanName string
	Schedule    Schedule
	Playback    Playback
	Terminals   []task.TerminalRef
	// LED 是方案级的 LED 字幕设置：nil 或正文为空表示这个方案不要 LED，
	// 保存时会把已有的 LED 子任务整批删掉。
	LED *LEDConf
	// ApplyTerminals 为 false 时不动终端清单，只改方案级属性。
	ApplyTerminals bool
}

type UpdateResult struct {
	PlanName     string   `json:"planName"`
	AffectedRows int      `json:"affectedRows"`
	TerminalRows int      `json:"terminalRows"`
	Renamed      bool     `json:"renamed"`
	Warnings     []string `json:"warnings"`
	TaskIDs      []int64  `json:"-"`
}

// Update 改方案级属性，可同时改名。
//
// 改名 = 把组内每一行的 info 一起改。旧版是逐行 UPDATE 且没有事务（D-169），
// 中途失败会把一个方案劈成两半：一部分是新名、一部分是旧名，
// 之后 GROUP BY info 就把它显示成两个方案，再也合不回去。
// 这里一条 UPDATE 覆盖全组，且整体在事务里。
func (s *Service) Update(ctx context.Context, u *auth.User, in UpdateInput) (*UpdateResult, error) {
	owner, err := s.assertPlan(ctx, u, in.PlanName)
	if err != nil {
		return nil, err
	}
	newName := in.PlanName
	if strings.TrimSpace(in.NewPlanName) != "" {
		if newName, err = checkPlanName(in.NewPlanName); err != nil {
			return nil, err
		}
	}

	// 方案现有的任务级别：没改动这一列时不去校验区间，
	// 免得历史数据里 priority < 10 的方案连改名都被挡下。
	oldPri, err := s.planPriority(ctx, in.PlanName)
	if err != nil {
		return nil, err
	}

	// 复用 Create 的校验：条目部分单独校验，这里只校验方案级 + 终端
	probe := PlanInput{Schedule: in.Schedule, Playback: in.Playback, Terminals: in.Terminals, LED: in.LED}
	if err := s.validatePlanLevel(ctx, &probe, owner, oldPri); err != nil {
		return nil, err
	}
	if in.ApplyTerminals {
		if err := s.validateTerminals(ctx, u, in.Terminals); err != nil {
			return nil, err
		}
		in.Terminals = probe.Terminals
	}

	unlock, err := store.Lock(ctx, s.db, planLock)
	if err != nil {
		return nil, err
	}
	defer unlock()

	out := &UpdateResult{PlanName: newName, Warnings: []string{}, TaskIDs: []int64{}}
	if newName != in.PlanName {
		if err := s.planNameFree(ctx, newName); err != nil {
			return nil, err
		}
		out.Renamed = true
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 方案级属性必须应用到组内每一行（BR-224），功放与 LED 子任务也要跟着改。
	// 范围用 planScopeWithSubs：子任务的 sec_task_id 不为 0。
	res, err := tx.ExecContext(ctx, `
		UPDATE task SET info = ?, startdate = ?, enddate = ?, exemodel = ?,
		                defaultvolume = ?, priority = ?, prepower = ?, datasendmodel = ?,
		                israndomplay = ?
		WHERE info = ? AND `+planScopeWithSubs(""),
		newName, in.Schedule.StartDate, in.Schedule.EndDate, in.Schedule.ExeModel,
		in.Playback.Volume, in.Playback.Priority, in.Playback.PrePower,
		in.Playback.DataSendMode, normRandom(in.Playback.IsRandomPlay), in.PlanName)
	if err != nil {
		return nil, fmt.Errorf("修改作息方案: %w", err)
	}
	n, _ := res.RowsAffected()
	out.AffectedRows = int(n)

	// prepower 改了，功放子任务的播放时间要跟着重算
	if err := resyncPowerTimes(ctx, tx, newName, in.Playback.PrePower); err != nil {
		return nil, err
	}
	// LED 字幕：开了就（重）建，关了就整批删掉
	ledIn := PlanInput{PlanName: newName, Schedule: in.Schedule, Playback: in.Playback,
		Terminals: in.Terminals, LED: in.LED}
	if err := resyncLED(ctx, tx, ledIn, owner); err != nil {
		return nil, err
	}

	ids, err := planTaskIDs(ctx, tx, newName)
	if err != nil {
		return nil, err
	}
	out.TaskIDs = ids

	if in.ApplyTerminals {
		rows, err := rewriteTerminals(ctx, tx, newName, in.Terminals)
		if err != nil {
			return nil, err
		}
		out.TerminalRows = rows
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return out, nil
}

// validatePlanLevel 只校验方案级字段与终端格式，不碰条目。
func (s *Service) validatePlanLevel(ctx context.Context, in *PlanInput, ownerID int64, oldPriority *int) error {
	if !reDate.MatchString(in.Schedule.StartDate) || !reDate.MatchString(in.Schedule.EndDate) {
		return fmt.Errorf("起止日期格式不正确，应为 YYYY-MM-DD")
	}
	if in.Schedule.EndDate < in.Schedule.StartDate {
		return fmt.Errorf("结束日期不能早于开始日期")
	}
	if !reExeModel.MatchString(in.Schedule.ExeModel) {
		return fmt.Errorf("星期掩码必须是 7 位的 0/1 字符串，例如 1111100")
	}
	if in.Playback.Volume < 0 || in.Playback.Volume > 100 {
		return fmt.Errorf("音量必须在 0 ~ 100 之间")
	}
	if in.Playback.PrePower < 0 || in.Playback.PrePower > 3600 {
		return fmt.Errorf("提前开电源必须在 0 ~ 3600 秒之间")
	}
	if in.Playback.DataSendMode != 0 && in.Playback.DataSendMode != 1 {
		return fmt.Errorf("发送模式只能是 0（单播）或 1（组播）")
	}
	if err := s.checkPriority(ctx, ownerID, in.Playback.Priority, oldPriority); err != nil {
		return err
	}
	return checkLED(in.LED)
}

// resyncPowerTimes 按新的 prepower 重算功放子任务的播放时间。
// prepower = 0 时把功放子任务连同它的关联行一并删掉。
func resyncPowerTimes(ctx context.Context, tx *sql.Tx, planName string, prepower int) error {
	if prepower <= 0 {
		return dropPowerTasks(ctx, tx, planName)
	}
	rows, err := tx.QueryContext(ctx,
		`SELECT p.taskid, TIME_FORMAT(m.playtime,'%H:%i:%s')
		 FROM task p JOIN task m ON m.taskid = p.sec_task_id
		 WHERE p.info = ? AND p.tasktype = ? AND p.channel = 0`, planName, PowerType)
	if err != nil {
		return fmt.Errorf("查询功放子任务: %w", err)
	}
	type row struct {
		id int64
		t  string
	}
	list := []row{}
	for rows.Next() {
		var r row
		if err := rows.Scan(&r.id, &r.t); err != nil {
			rows.Close()
			return err
		}
		list = append(list, r)
	}
	rows.Close()
	if err := rows.Err(); err != nil {
		return err
	}
	for _, r := range list {
		t, err := task.ShiftTime(r.t, -prepower)
		if err != nil {
			return err
		}
		if _, err := tx.ExecContext(ctx,
			`UPDATE task SET playtime = ? WHERE taskid = ?`, t, r.id); err != nil {
			return fmt.Errorf("更新功放子任务时间: %w", err)
		}
	}
	return nil
}

func dropPowerTasks(ctx context.Context, tx *sql.Tx, planName string) error {
	ids, err := collectIDs(ctx, tx,
		`SELECT taskid FROM task WHERE info = ? AND tasktype = ? AND channel = 0`,
		planName, PowerType)
	if err != nil {
		return err
	}
	if len(ids) == 0 {
		return nil
	}
	return purgeTaskRows(ctx, tx, ids)
}

func planTaskIDs(ctx context.Context, tx *sql.Tx, planName string) ([]int64, error) {
	return collectIDs(ctx, tx,
		`SELECT taskid FROM task WHERE info = ? AND `+planScope(""), planName)
}

// rewriteTerminals 全删重插方案的终端清单。
// 必须在事务里 —— 旧版没有事务边界，中途失败方案就没有终端了（D-170）。
func rewriteTerminals(ctx context.Context, tx *sql.Tx, planName string,
	terms []task.TerminalRef) (int, error) {

	ids, err := collectIDs(ctx, tx,
		`SELECT taskid FROM task WHERE info = ? AND `+planScopeWithSubs(""), planName)
	if err != nil {
		return 0, err
	}
	if len(ids) == 0 {
		return 0, nil
	}
	ph, args := placeholders(ids)
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminaloftask WHERE taskid IN (`+ph+`)`, args...); err != nil {
		return 0, fmt.Errorf("清理终端清单: %w", err)
	}
	total := 0
	for _, id := range ids {
		n, err := writeTerminals(ctx, tx, id, 0, terms)
		if err != nil {
			return 0, err
		}
		total += n
	}
	return total, nil
}

// collectSubTasks 找这些条目挂着的子任务：功放（9）与 LED 字幕（30 / 24）。
// 删条目、改条目日期都要连它们一起处理，漏掉就会留下孤儿任务。
func collectSubTasks(ctx context.Context, tx *sql.Tx, mainIDs []int64) ([]int64, error) {
	if len(mainIDs) == 0 {
		return nil, nil
	}
	ph, args := placeholders(mainIDs)
	ledPH, ledArgs := ledTypeArgs()
	return collectIDs(ctx, tx,
		`SELECT taskid FROM task WHERE sec_task_id IN (`+ph+`) AND tasktype IN (?,`+ledPH+`)`,
		append(append(args, PowerType), ledArgs...)...)
}

func collectIDs(ctx context.Context, tx *sql.Tx, q string, args ...interface{}) ([]int64, error) {
	rows, err := tx.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询任务 ID: %w", err)
	}
	defer rows.Close()
	out := []int64{}
	for rows.Next() {
		var id int64
		if err := rows.Scan(&id); err != nil {
			return nil, err
		}
		out = append(out, id)
	}
	return out, rows.Err()
}

// ---------- 单条条目 ----------

// AddItem 往已有方案里加一个条目，方案级属性沿用组内现有取值。
func (s *Service) AddItem(ctx context.Context, u *auth.User, planName string,
	it ItemInput) (*SaveResult, error) {

	owner, err := s.assertPlan(ctx, u, planName)
	if err != nil {
		return nil, err
	}
	d, err := s.Get(ctx, u, planName)
	if err != nil {
		return nil, err
	}
	in := PlanInput{
		PlanName: planName, Schedule: d.Schedule, Playback: d.Playback,
		Items: []ItemInput{it}, LED: d.LED,
	}
	// 终端沿用方案现有清单。跳过已被删除的终端 ——
	// 它们只是列表里的一个「(终端已删除)」占位，再写回去会被存在性校验挡下，
	// 结果就是「方案里有台终端被删了，从此这个方案加不了新条目」。
	for _, t := range d.Terminals {
		if t.Deleted {
			continue
		}
		in.Terminals = append(in.Terminals, task.TerminalRef{
			TerminalID: t.TerminalID, GroupID: t.GroupID, Area: t.Area})
	}
	// 这条路径是「往已有方案里加条目」，方案级参数是从库里读出来照抄的，
	// 把原值一并传进去，免得历史数据里 priority < 10 的方案连加条目都被挡下。
	oldPri := in.Playback.Priority
	if err := s.validate(ctx, u, &in, owner, &oldPri); err != nil {
		return nil, err
	}
	// 条目名在方案内唯一：旧版按 (info, taskname) 定位条目，重名会互相覆盖
	var dup int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE info = ? AND taskname = ? AND `+planScope(""),
		planName, in.Items[0].TaskName).Scan(&dup); err != nil {
		return nil, fmt.Errorf("条目重名校验: %w", err)
	}
	if dup > 0 {
		return nil, fmt.Errorf("方案内已存在同名条目：%q", in.Items[0].TaskName)
	}
	return s.saveItems(ctx, in, owner)
}

// UpdateItem 改一个条目（对应旧 belltaskalonemodify）。
//
// 返回该条目的 defaultvolume，调用方发通知时要原样带上 &volume=。
func (s *Service) UpdateItem(ctx context.Context, u *auth.User, planName string,
	taskID int64, it ItemInput) (int, error) {

	if _, err := s.assertPlan(ctx, u, planName); err != nil {
		return 0, err
	}
	var curName string
	var volume int
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(taskname,''), COALESCE(defaultvolume,80)
		 FROM task WHERE taskid = ? AND info = ? AND `+planScope(""),
		taskID, planName).Scan(&curName, &volume)
	if errors.Is(err, sql.ErrNoRows) {
		return 0, ErrNotFound
	}
	if err != nil {
		return 0, fmt.Errorf("查询条目: %w", err)
	}

	it.TaskName = strings.TrimSpace(it.TaskName)
	if it.TaskName == "" {
		return 0, fmt.Errorf("条目名称不能为空")
	}
	if !reTime.MatchString(it.PlayTime) {
		return 0, fmt.Errorf("打铃时间格式不正确，应为 HH:MM:SS")
	}
	if it.TimeLengthTy != 1 && it.TimeLengthTy != 2 {
		return 0, fmt.Errorf("时长类型只能是 1（按秒数）或 2（按循环次数）")
	}
	if it.TimeLength < 0 || it.TimeLength > 86400 {
		return 0, fmt.Errorf("时长/次数必须在 0 ~ 86400 之间")
	}
	if it.TaskName != curName {
		var dup int
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM task WHERE info = ? AND taskname = ? AND taskid <> ? AND `+planScope(""),
			planName, it.TaskName, taskID).Scan(&dup); err != nil {
			return 0, fmt.Errorf("条目重名校验: %w", err)
		}
		if dup > 0 {
			return 0, fmt.Errorf("方案内已存在同名条目：%q", it.TaskName)
		}
	}
	mediaIDs := []int64{}
	seen := map[int64]bool{}
	for _, m := range it.Media {
		if m.MediaID <= 0 {
			return 0, fmt.Errorf("铃声里有非法的媒体 ID")
		}
		if seen[m.MediaID] {
			return 0, fmt.Errorf("铃声有重复项")
		}
		seen[m.MediaID] = true
		mediaIDs = append(mediaIDs, m.MediaID)
	}
	if err := s.assertMediaExist(ctx, mediaIDs); err != nil {
		return 0, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx,
		`UPDATE task SET taskname = ?, playtime = ?, timelengthtype = ?, timelength = ?
		 WHERE taskid = ?`,
		it.TaskName, it.PlayTime, it.TimeLengthTy, it.TimeLength, taskID); err != nil {
		return 0, fmt.Errorf("修改条目: %w", err)
	}
	// 功放子任务的名字与时间跟着主条目走
	var prepower int
	if err := tx.QueryRowContext(ctx,
		`SELECT COALESCE(prepower,0) FROM task WHERE taskid = ?`, taskID).Scan(&prepower); err != nil {
		return 0, fmt.Errorf("查询提前量: %w", err)
	}
	if prepower > 0 {
		powerTime, err := task.ShiftTime(it.PlayTime, -prepower)
		if err != nil {
			return 0, err
		}
		if _, err := tx.ExecContext(ctx,
			`UPDATE task SET taskname = ?, playtime = ?, timelengthtype = ?, timelength = ?
			 WHERE sec_task_id = ? AND tasktype = ?`,
			it.TaskName, powerTime, it.TimeLengthTy, it.TimeLength, taskID, PowerType); err != nil {
			return 0, fmt.Errorf("修改功放子任务: %w", err)
		}
	}
	// LED 子任务的名字与时间也跟着主条目走（字幕正文由方案级设置统一管）
	ledPH, ledArgs := ledTypeArgs()
	if _, err := tx.ExecContext(ctx,
		`UPDATE task SET taskname = ?, playtime = ?, timelengthtype = ?, timelength = ?
		 WHERE sec_task_id = ? AND tasktype IN (`+ledPH+`)`,
		append([]interface{}{it.TaskName, it.PlayTime, it.TimeLengthTy, it.TimeLength, taskID}, ledArgs...)...); err != nil {
		return 0, fmt.Errorf("修改 LED 子任务: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM mediaoftask WHERE taskid = ?`, taskID); err != nil {
		return 0, fmt.Errorf("清理条目铃声: %w", err)
	}
	if err := writeMedia(ctx, tx, taskID, it.Media); err != nil {
		return 0, err
	}
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return volume, nil
}

// SetItemSchedule 把选中的条目挪到新的日期时间段，并改它们的执行星期。
//
// 对应旧版「统一播放时间」（sechotime.php）：把方案里的条目按打铃时间列出来、
// 勾中若干条再统一改 —— 旧版那页改的是星期掩码，这里日期段和星期掩码一起改。
// 只动 startdate/enddate/exemodel，条目的名称、打铃时间、铃声一概不碰。
//
// 功放子任务必须跟着主条目一起改 —— 漏掉它，提前开电源的那条就还留在旧日期上，
// 到了新日期功放不会提前打开。
// 返回改动到的 taskid（含功放子任务）与方案音量：调用方发通知时要原样带上 &volume=。
func (s *Service) SetItemSchedule(ctx context.Context, u *auth.User, planName string,
	ids []int64, startDate, endDate, exeModel string) ([]int64, int, error) {

	if _, err := s.assertPlan(ctx, u, planName); err != nil {
		return nil, 0, err
	}
	if len(ids) == 0 {
		return nil, 0, fmt.Errorf("请至少选择一个课时")
	}
	if !reDate.MatchString(startDate) || !reDate.MatchString(endDate) {
		return nil, 0, fmt.Errorf("起止日期格式不正确，应为 YYYY-MM-DD")
	}
	if endDate < startDate {
		return nil, 0, fmt.Errorf("结束日期不能早于开始日期")
	}
	if !reExeModel.MatchString(exeModel) {
		return nil, 0, fmt.Errorf("星期掩码必须是 7 位的 0/1 字符串，例如 1111100")
	}
	ph, args := placeholders(ids)

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 只改属于本方案的条目：传别的方案的 taskid 进来不能生效
	own, err := collectIDs(ctx, tx,
		`SELECT taskid FROM task WHERE taskid IN (`+ph+`) AND info = ? AND `+planScope(""),
		append(args, planName)...)
	if err != nil {
		return nil, 0, err
	}
	if len(own) == 0 {
		return nil, 0, ErrNotFound
	}
	var volume int
	if err := tx.QueryRowContext(ctx,
		`SELECT COALESCE(defaultvolume,80) FROM task WHERE taskid = ?`, own[0]).Scan(&volume); err != nil {
		return nil, 0, fmt.Errorf("查询音量: %w", err)
	}
	subs, err := collectSubTasks(ctx, tx, own)
	if err != nil {
		return nil, 0, err
	}
	all := append(append([]int64{}, own...), subs...)
	allPH, allArgs := placeholders(all)
	if _, err := tx.ExecContext(ctx,
		`UPDATE task SET startdate = ?, enddate = ?, exemodel = ? WHERE taskid IN (`+allPH+`)`,
		append([]interface{}{startDate, endDate, exeModel}, allArgs...)...); err != nil {
		return nil, 0, fmt.Errorf("修改条目排期: %w", err)
	}
	if err := tx.Commit(); err != nil {
		return nil, 0, fmt.Errorf("提交事务: %w", err)
	}
	return all, volume, nil
}

// DeleteItems 删若干条目（对应旧 delonebellplan）。
// 方案的最后一个条目被删掉时方案本身也就没了 —— 明确提示而不是静默变空。
//
// 返回**实际删掉的** taskid，通知就按这一份发；照请求里的 ids 发会给后台服务
// 推送一堆根本不存在的删除。
func (s *Service) DeleteItems(ctx context.Context, u *auth.User, planName string,
	ids []int64) ([]int64, bool, error) {

	if _, err := s.assertPlan(ctx, u, planName); err != nil {
		return nil, false, err
	}
	if len(ids) == 0 {
		return nil, false, fmt.Errorf("未选择条目")
	}
	ph, args := placeholders(ids)

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, false, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 只删属于本方案的条目：传别的方案的 taskid 进来不能生效
	own, err := collectIDs(ctx, tx,
		`SELECT taskid FROM task WHERE taskid IN (`+ph+`) AND info = ? AND `+planScope(""),
		append(args, planName)...)
	if err != nil {
		return nil, false, err
	}
	if len(own) == 0 {
		return nil, false, ErrNotFound
	}
	subs, err := collectSubTasks(ctx, tx, own)
	if err != nil {
		return nil, false, err
	}
	all := append(append([]int64{}, own...), subs...)
	if err := purgeTaskRows(ctx, tx, all); err != nil {
		return nil, false, err
	}

	var left int
	if err := tx.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE info = ? AND `+planScope(""), planName).Scan(&left); err != nil {
		return nil, false, fmt.Errorf("统计剩余条目: %w", err)
	}
	if err := tx.Commit(); err != nil {
		return nil, false, fmt.Errorf("提交事务: %w", err)
	}
	return all, left == 0, nil
}

// checkPriority 校验任务级别。
//
// ⚠ 只在**本次提交改动了这一列**时才校验：现网旧数据的 priority 是 3~9，
// 落在允许区间（10~109）之外，一律校验会让这些方案连改都改不了。
// 与 task / typedtask 两个包的做法一致。
func (s *Service) checkPriority(ctx context.Context, ownerID int64, val int, old *int) error {
	if old != nil && *old == val {
		return nil
	}
	lo, hi, err := s.priorityRange(ctx, ownerID)
	if err != nil {
		return err
	}
	if val < lo || val > hi {
		return fmt.Errorf("任务级别必须在 %d ~ %d 之间", lo, hi)
	}
	return nil
}

// planPriority 读一个方案当前的任务级别。方案里各条目共用同一套方案级参数，
// 取第一条即可；方案不存在或读不到时返回 nil，表示「没有旧值」，按新值严格校验。
func (s *Service) planPriority(ctx context.Context, planName string) (*int, error) {
	var v int
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(priority,0) FROM task WHERE info = ? AND `+planScope("")+` ORDER BY taskid LIMIT 1`,
		planName).Scan(&v)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, nil
	}
	if err != nil {
		return nil, fmt.Errorf("查询方案任务级别: %w", err)
	}
	return &v, nil
}
