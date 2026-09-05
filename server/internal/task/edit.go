package task

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"regexp"
	"strings"
	"unicode/utf8"

	"htweb/internal/auth"
)

// 新增 / 修改任务（F-33）。

// Detail 是编辑弹窗需要的任务数据。
type Detail struct {
	TaskID       int64          `json:"taskid"`
	TaskName     string         `json:"taskname"`
	TaskType     int            `json:"tasktype"`
	FolderID     int64          `json:"folderId"`
	ProjectState int            `json:"projectstate"`
	IsRandomPlay int            `json:"israndomplay"`
	StartDate    string         `json:"startdate"`
	EndDate      string         `json:"enddate"`
	PlayTime     string         `json:"playtime"`
	EndTime      string         `json:"endtime"`
	ExeModel     string         `json:"exemodel"`
	DisableDay   string         `json:"disableday"`
	TimeLengthTy int            `json:"timelengthtype"`
	TimeLength   int            `json:"timelength"`
	IntervalS    int            `json:"interval_s"`
	IntPlayLen   int            `json:"intplaylength"`
	IntPlayLenTy int            `json:"intplaylengthtype"`
	Volume       int            `json:"defaultvolume"`
	Priority     int            `json:"priority"`
	LocalPlay    int            `json:"localplay"`
	PrePower     int            `json:"prepower"`
	DataSendMode int            `json:"datasendmodel"`
	OwnerUserID  int64          `json:"ownerUserId"`
	Media        []MediaItem    `json:"media"`
	Terminals    []TerminalItem `json:"terminals"`
	// LED 是挂在这条任务上的 LED 字幕子任务；没有就是 null。
	LED *LEDSub `json:"led"`
	// PriorityRange 是当前归属用户被允许的优先级区间，供界面直接约束控件。
	PriorityMin int `json:"priorityMin"`
	PriorityMax int `json:"priorityMax"`
}

// MediaRef / TerminalRef 是提交时的清单项。
//
// 旧版用两条平行的逗号串按下标对齐提交（listvalue / terminallistvalue + 分区串），
// 长度不一致时下标越界，groupid 静默写 0（D-107）。改成对象数组后不存在这个问题。
type MediaRef struct {
	MediaID int64 `json:"mediaId"`
	Sort    int   `json:"sort"`
}

type TerminalRef struct {
	TerminalID int64  `json:"terminalId"`
	GroupID    int64  `json:"groupId"`
	Area       string `json:"area"`
}

// Input 是新增 / 修改的入参。
type Input struct {
	TaskName     string
	FolderID     int64
	ProjectState int
	IsRandomPlay int
	StartDate    string
	EndDate      string
	PlayTime     string
	EndTime      string
	ExeModel     string
	DisableDay   string
	TimeLengthTy int
	TimeLength   int
	IntervalS    int
	IntPlayLen   int
	IntPlayLenTy int
	Volume       int
	Priority     int
	LocalPlay    int
	PrePower     int
	DataSendMode int
	Media        []MediaRef
	Terminals    []TerminalRef
	// LED 是 :80 表单里的「led播放 / Led字幕 / Led速度」。
	// 为 nil 或字幕为空 = 不上屏（已有的 LED 子任务会被删掉）。
	LED *LEDSub
}

// LEDSub 是挂在这条任务上的 LED 字幕子任务。
type LEDSub struct {
	// Name 是子任务自己的名字。留空则跟主任务同名 ——
	// 现网两条既有数据一条同名（70035）、一条不同名（70009「任务一」/主任务「早读」），
	// 说明它是可以单独填的。
	Name    string `json:"name"`
	Text    string `json:"text"`
	Speed   int    `json:"speed"`
	LedMode int    `json:"ledmode"`
	// Devices 是字幕要上到哪几块 LED 屏（旧版表单里的「led设备列表」）。
	// 空表示不绑设备 —— 旧版此时也只写 task 与字幕，ledoftask 是空的。
	Devices []LEDDevRef `json:"devices"`
}

// LEDDevRef 是 ledoftask 的一行：一块 LED 屏挂在哪台终端下。
type LEDDevRef struct {
	TerminalID int64 `json:"terminalId"`
	DeviceID   int64 `json:"deviceId"`
}

type SaveResult struct {
	TaskID        int64 `json:"taskid"`
	PowerTaskID   int64 `json:"powerTaskId"`
	LEDTaskID     int64 `json:"ledTaskId"`
	MediaCount    int   `json:"mediaCount"`
	TerminalCount int   `json:"terminalCount"`
	Volume        int   `json:"-"`
}

var (
	reDate     = regexp.MustCompile(`^\d{4}-\d{2}-\d{2}$`)
	reTime     = regexp.MustCompile(`^\d{2}:\d{2}:\d{2}$`)
	reExeModel = regexp.MustCompile(`^[01]{7}$`)
	reArea     = regexp.MustCompile(`^[01]{1,16}$`)
)

// Get 取任务详情。
func (s *Service) Get(ctx context.Context, u *auth.User, id int64) (*Detail, error) {
	d := &Detail{TaskID: id}
	var startDate, endDate, disableDay sql.NullString
	err := s.db.QueryRowContext(ctx, `
		SELECT COALESCE(taskname,''), tasktype, COALESCE(parentid,0),
		       COALESCE(projectstate,0), COALESCE(israndomplay,0),
		       DATE_FORMAT(startdate,'%Y-%m-%d'), DATE_FORMAT(enddate,'%Y-%m-%d'),
		       COALESCE(playtime,'00:00:00'), COALESCE(endtime,'00:00:00'),
		       COALESCE(exemodel,'0000000'), DATE_FORMAT(disableday,'%Y-%m-%d'),
		       COALESCE(timelengthtype,1), COALESCE(timelength,0),
		       COALESCE(interval_s,0), COALESCE(intplaylength,1), COALESCE(intplaylengthtype,2),
		       COALESCE(defaultvolume,80), COALESCE(priority,0), COALESCE(localplay,0),
		       COALESCE(prepower,0), COALESCE(datasendmodel,0), COALESCE(task_user_id,0)
		FROM task WHERE taskid = ? LIMIT 1`, id).
		Scan(&d.TaskName, &d.TaskType, &d.FolderID, &d.ProjectState, &d.IsRandomPlay,
			&startDate, &endDate, &d.PlayTime, &d.EndTime, &d.ExeModel, &disableDay,
			&d.TimeLengthTy, &d.TimeLength, &d.IntervalS, &d.IntPlayLen, &d.IntPlayLenTy,
			&d.Volume, &d.Priority, &d.LocalPlay, &d.PrePower, &d.DataSendMode, &d.OwnerUserID)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询任务: %w", err)
	}
	if !u.IsAdmin && d.OwnerUserID != u.ID {
		return nil, ErrNoPermission
	}
	d.StartDate, d.EndDate, d.DisableDay = startDate.String, endDate.String, disableDay.String

	items := []Item{{TaskID: id}}
	if err := s.fillMedia(ctx, items, []int64{id}); err != nil {
		return nil, err
	}
	if err := s.fillTerminals(ctx, items, []int64{id}); err != nil {
		return nil, err
	}
	d.Media, d.Terminals = items[0].Media, items[0].Terminals
	if d.Media == nil {
		d.Media = []MediaItem{}
	}
	if d.Terminals == nil {
		d.Terminals = []TerminalItem{}
	}

	// LED 字幕子任务（没有就是 nil，界面上那一项保持关闭）
	led, err := s.loadLEDSub(ctx, id)
	if err != nil {
		return nil, err
	}
	d.LED = led

	lo, hi, err := s.priorityRange(ctx, d.OwnerUserID)
	if err != nil {
		return nil, err
	}
	d.PriorityMin, d.PriorityMax = lo, hi
	return d, nil
}

// 任务级别的取值范围。旧版表单的下拉是 `for(level=$getlevel; level<=109; level++)`，
// 上限 109 是写死的；下限来自 usergroup.level，新版统一不低于 10（见 priorityRange）。
const (
	PriorityFloor = 10
	PriorityCeil  = 109
)

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
		return PriorityFloor, PriorityCeil, nil
	}
	if err != nil {
		return 0, 0, fmt.Errorf("查询用户组级别: %w", err)
	}
	if level < PriorityFloor {
		level = PriorityFloor
	}
	if level > PriorityCeil {
		level = PriorityCeil
	}
	return level, PriorityCeil, nil
}

// validate 校验入参。旧版对这些字段一个都不校验，全是 $_POST 裸拼进 SQL（D-83 同构）。
func (s *Service) validate(ctx context.Context, u *auth.User, in *Input, ownerID int64, oldPriority *int) error {
	in.TaskName = strings.TrimSpace(in.TaskName)
	if in.TaskName == "" {
		return fmt.Errorf("任务名称不能为空")
	}
	if utf8.RuneCountInString(in.TaskName) > 85 {
		return fmt.Errorf("任务名称最多 85 个字符")
	}
	if in.FolderID <= 0 {
		return fmt.Errorf("请选择所属分组")
	}
	if err := s.AssertFolderVisible(ctx, u, in.FolderID); err != nil {
		return err
	}
	// 取值与列注释相反：0 = 启用、1 = 停用，判据见 control.go::SetProjectState
	if in.ProjectState != StateEnabled && in.ProjectState != StateDisabled {
		return fmt.Errorf("方案状态只能是 0（启用）或 1（停用）")
	}
	if in.IsRandomPlay != 0 && in.IsRandomPlay != 1 {
		// BR-163：0 = 随机，1 = 顺序，取值反直觉但必须保持
		return fmt.Errorf("播放方式只能是 0（随机）或 1（顺序）")
	}
	for _, spec := range []struct {
		name string
		val  string
		re   *regexp.Regexp
		hint string
	}{
		{"开始日期", in.StartDate, reDate, "YYYY-MM-DD"},
		{"结束日期", in.EndDate, reDate, "YYYY-MM-DD"},
		{"播放时间", in.PlayTime, reTime, "HH:MM:SS"},
		{"结束时间", in.EndTime, reTime, "HH:MM:SS"},
	} {
		if !spec.re.MatchString(spec.val) {
			return fmt.Errorf("%s格式不正确，应为 %s", spec.name, spec.hint)
		}
	}
	if in.StartDate > in.EndDate {
		return fmt.Errorf("结束日期不能早于开始日期")
	}
	// disableday 允许空；空串落库写 0000-00-00，与旧数据一致
	if in.DisableDay != "" && !reDate.MatchString(in.DisableDay) {
		return fmt.Errorf("当天停用日期格式不正确，应为 YYYY-MM-DD")
	}
	if !reExeModel.MatchString(in.ExeModel) {
		return fmt.Errorf("星期掩码必须是 7 位的 0/1 字符串，例如 1111100")
	}
	if in.TimeLengthTy != 1 && in.TimeLengthTy != 2 {
		return fmt.Errorf("时长类型只能是 1（按秒数）或 2（按循环次数）")
	}
	if in.TimeLength < 0 || in.TimeLength > 86400 {
		return fmt.Errorf("时长/次数必须在 0 ~ 86400 之间")
	}
	if in.Volume < 0 || in.Volume > 100 {
		return fmt.Errorf("音量必须在 0 ~ 100 之间")
	}
	// 单位是秒（见 ShiftTime 的说明）。旧界面下拉最大只给到 300 秒（5 分钟），
	// 这里放宽到 3600 秒：列是 int，存量数据也可能是手工写进去的，
	// 不该比旧版更严到把已有数据卡住，但负数和离谱的大值要挡。
	if in.PrePower < 0 || in.PrePower > 3600 {
		return fmt.Errorf("提前开电源必须在 0 ~ 3600 秒之间")
	}
	if in.DataSendMode != 0 && in.DataSendMode != 1 {
		return fmt.Errorf("发送模式只能是 0（单播）或 1（组播）")
	}
	if in.LocalPlay != 0 && in.LocalPlay != 1 {
		return fmt.Errorf("本地优先播放只能是 0 或 1")
	}
	if in.IntPlayLenTy != 1 && in.IntPlayLenTy != 2 {
		return fmt.Errorf("间隔播放模式只能是 1（时间）或 2（循环）")
	}
	if in.IntervalS < 0 || in.IntPlayLen < 0 {
		return fmt.Errorf("间隔时间与间隔时长不能为负")
	}

	// 优先级：只在值真的变了时校验，避免存量的越界值卡住「只改个名字」的编辑
	if oldPriority == nil || *oldPriority != in.Priority {
		lo, hi, err := s.priorityRange(ctx, ownerID)
		if err != nil {
			return err
		}
		if in.Priority < lo || in.Priority > hi {
			return fmt.Errorf("优先级必须在 %d ~ %d 之间（由所属用户组级别决定）", lo, hi)
		}
	}

	if err := s.validateMedia(ctx, in); err != nil {
		return err
	}
	return s.validateTerminals(ctx, u, in)
}

func (s *Service) validateMedia(ctx context.Context, in *Input) error {
	if len(in.Media) == 0 {
		return nil // 允许先建空任务，启动时才拦（BR-176）
	}
	if len(in.Media) > 500 {
		return fmt.Errorf("媒体清单最多 500 条")
	}
	seen := map[int64]bool{}
	ids := make([]int64, 0, len(in.Media))
	for i := range in.Media {
		m := &in.Media[i]
		if m.MediaID <= 0 {
			return fmt.Errorf("媒体清单里有非法的媒体 ID")
		}
		if seen[m.MediaID] {
			return fmt.Errorf("媒体清单里有重复项")
		}
		seen[m.MediaID] = true
		ids = append(ids, m.MediaID)
		// sort 由服务端按数组顺序重排，前端传什么都不作数
		m.Sort = i
	}
	ph, args := placeholders(ids)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM media WHERE id IN (`+ph+`)`, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验媒体: %w", err)
	}
	if n != len(ids) {
		return fmt.Errorf("媒体清单里有已不存在的媒体，请重新选择")
	}
	return nil
}

func (s *Service) validateTerminals(ctx context.Context, u *auth.User, in *Input) error {
	if len(in.Terminals) == 0 {
		return nil
	}
	if len(in.Terminals) > 3000 {
		return fmt.Errorf("终端清单最多 3000 条")
	}
	seen := map[int64]bool{}
	ids := make([]int64, 0, len(in.Terminals))
	for i := range in.Terminals {
		t := &in.Terminals[i]
		if t.TerminalID <= 0 {
			return fmt.Errorf("终端清单里有非法的终端 ID")
		}
		if seen[t.TerminalID] {
			return fmt.Errorf("终端清单里有重复项")
		}
		seen[t.TerminalID] = true
		ids = append(ids, t.TerminalID)
		if t.Area == "" {
			t.Area = AreaAll
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
	// 普通用户只能挂自己绑定的终端
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

// Create 新建任务。
//
// 主任务、功放子任务、媒体清单、终端清单**全部在同一事务内**完成（BR-168 / D-110）。
// 旧版是主任务先提交、子任务失败也不回滚，留下没有子任务的孤儿主任务。
func (s *Service) Create(ctx context.Context, u *auth.User, in Input) (*SaveResult, error) {
	if err := s.validate(ctx, u, &in, u.ID, nil); err != nil {
		return nil, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	taskID, err := insertTask(ctx, tx, in, TypeFile, u.ID, 0, in.PlayTime)
	if err != nil {
		return nil, err
	}

	out := &SaveResult{TaskID: taskID, MediaCount: len(in.Media),
		TerminalCount: len(in.Terminals), Volume: in.Volume}

	if in.PrePower > 0 {
		// 功放子任务：类型 9，sec_task_id 指回主任务，播放时间提前 prepower 秒
		powerTime, err := ShiftTime(in.PlayTime, -in.PrePower)
		if err != nil {
			return nil, err
		}
		powerID, err := insertTask(ctx, tx, in, TypePowerAmp, u.ID, taskID, powerTime)
		if err != nil {
			return nil, err
		}
		out.PowerTaskID = powerID
	}

	if err := writeLists(ctx, tx, taskID, out.PowerTaskID, in); err != nil {
		return nil, err
	}
	// LED 字幕子任务（:80 表单里的「led播放」）。放在写清单之后：
	// 它要照抄主任务的终端清单，而清单是上一步才落库的入参。
	ledID, err := syncLEDTask(ctx, tx, taskID, in, u.ID)
	if err != nil {
		return nil, err
	}
	out.LEDTaskID = ledID
	if err := bindTerminalsToOwner(ctx, tx, u.ID, in.Terminals); err != nil {
		return nil, err
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return out, nil
}

// Update 修改任务。
//
// 媒体与终端清单采用「全删重插」，必须事务包裹（BR-170 / D-111）——
// 旧版没有事务，中途失败就把任务变成空清单。
func (s *Service) Update(ctx context.Context, u *auth.User, id int64, in Input) (*SaveResult, error) {
	var owner int64
	var oldPriority, oldPrePower int
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(task_user_id,0), COALESCE(priority,0), COALESCE(prepower,0)
		 FROM task WHERE taskid = ? AND `+mustTypeInPlain()+` LIMIT 1`,
		append([]interface{}{id}, typeArgsPlain()...)...).Scan(&owner, &oldPriority, &oldPrePower)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询任务: %w", err)
	}
	if !u.IsAdmin && owner != u.ID {
		return nil, ErrNoPermission
	}
	if err := s.validate(ctx, u, &in, owner, &oldPriority); err != nil {
		return nil, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 主任务。parentid 也一并更新 —— 旧版的 UPDATE 语句里没有 parentid，
	// 所以任务一旦建好就再也换不了分组。
	if _, err := tx.ExecContext(ctx, `
		UPDATE task SET taskname=?, parentid=?, projectstate=?, israndomplay=?,
		       timelengthtype=?, timelength=?, prepower=?, datasendmodel=?,
		       startdate=?, enddate=?, playtime=?, endtime=?, exemodel=?, disableday=?,
		       priority=?, defaultvolume=?, localplay=?,
		       interval_s=?, intplaylength=?, intplaylengthtype=?, offlinestate=0
		WHERE taskid = ?`,
		in.TaskName, in.FolderID, in.ProjectState, in.IsRandomPlay,
		in.TimeLengthTy, in.TimeLength, in.PrePower, in.DataSendMode,
		in.StartDate, in.EndDate, in.PlayTime, in.EndTime, in.ExeModel, nullDate(in.DisableDay),
		in.Priority, in.Volume, in.LocalPlay,
		in.IntervalS, in.IntPlayLen, in.IntPlayLenTy, id); err != nil {
		return nil, fmt.Errorf("更新任务: %w", err)
	}

	out := &SaveResult{TaskID: id, MediaCount: len(in.Media),
		TerminalCount: len(in.Terminals), Volume: in.Volume}

	powerID, err := syncPowerTask(ctx, tx, id, in, u.ID)
	if err != nil {
		return nil, err
	}
	out.PowerTaskID = powerID

	// 全删重插
	all := []int64{id}
	if powerID > 0 {
		all = append(all, powerID)
	}
	ph, args := placeholders(all)
	for _, stmt := range []string{
		`DELETE FROM mediaoftask WHERE taskid IN (` + ph + `)`,
		`DELETE FROM terminaloftask WHERE taskid IN (` + ph + `)`,
	} {
		if _, err := tx.ExecContext(ctx, stmt, args...); err != nil {
			return nil, fmt.Errorf("清理任务清单: %w", err)
		}
	}
	if err := writeLists(ctx, tx, id, powerID, in); err != nil {
		return nil, err
	}
	// LED 字幕子任务：整条重建（内容与终端都跟着主任务走）
	ledID, err := syncLEDTask(ctx, tx, id, in, owner)
	if err != nil {
		return nil, err
	}
	out.LEDTaskID = ledID
	if err := bindTerminalsToOwner(ctx, tx, owner, in.Terminals); err != nil {
		return nil, err
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return out, nil
}

// syncPowerTask 让功放子任务与主任务的 prepower 保持一致：
// 从无到有则新建，从有到无则删除，一直有则更新。
// 返回子任务 id（0 表示不存在）。
func syncPowerTask(ctx context.Context, tx *sql.Tx, mainID int64, in Input, ownerID int64) (int64, error) {
	var existing sql.NullInt64
	if err := tx.QueryRowContext(ctx,
		`SELECT taskid FROM task WHERE sec_task_id = ? AND tasktype = ? LIMIT 1`,
		mainID, TypePowerAmp).Scan(&existing); err != nil && !errors.Is(err, sql.ErrNoRows) {
		return 0, fmt.Errorf("查询功放子任务: %w", err)
	}

	if in.PrePower <= 0 {
		if existing.Valid {
			// 不依赖 prepower 判断是否该清理（D-120 就是栽在这上面）
			if _, err := tx.ExecContext(ctx,
				`DELETE FROM terminaloftask WHERE taskid = ?`, existing.Int64); err != nil {
				return 0, fmt.Errorf("清理功放子任务终端: %w", err)
			}
			if _, err := tx.ExecContext(ctx,
				`DELETE FROM task WHERE taskid = ?`, existing.Int64); err != nil {
				return 0, fmt.Errorf("删除功放子任务: %w", err)
			}
		}
		return 0, nil
	}

	powerTime, err := ShiftTime(in.PlayTime, -in.PrePower)
	if err != nil {
		return 0, err
	}
	if !existing.Valid {
		return insertTask(ctx, tx, in, TypePowerAmp, ownerID, mainID, powerTime)
	}
	if _, err := tx.ExecContext(ctx, `
		UPDATE task SET taskname=?, parentid=?, projectstate=?, israndomplay=?,
		       timelengthtype=?, timelength=?, prepower=?, datasendmodel=?,
		       startdate=?, enddate=?, playtime=?, exemodel=?, disableday=?,
		       priority=?, defaultvolume=?, localplay=?,
		       interval_s=?, intplaylength=?, intplaylengthtype=?, offlinestate=0
		WHERE taskid = ? AND tasktype = ?`,
		in.TaskName, in.FolderID, in.ProjectState, in.IsRandomPlay,
		in.TimeLengthTy, in.TimeLength, in.PrePower, in.DataSendMode,
		in.StartDate, in.EndDate, powerTime, in.ExeModel, nullDate(in.DisableDay),
		in.Priority, in.Volume, in.LocalPlay,
		in.IntervalS, in.IntPlayLen, in.IntPlayLenTy,
		existing.Int64, TypePowerAmp); err != nil {
		return 0, fmt.Errorf("更新功放子任务: %w", err)
	}
	return existing.Int64, nil
}

// insertTask 插入一条任务并返回其 id。
//
// 用 LAST_INSERT_ID()（database/sql 的 LastInsertId）取新 ID，
// 不用旧版的 SELECT MAX(taskid) —— 那在并发下会取到别人刚插的行（D-108 / D-52）。
func insertTask(ctx context.Context, tx *sql.Tx, in Input, taskType int,
	ownerID, secTaskID int64, playTime string) (int64, error) {

	// state 恒写 0：执行状态由后台调度和启停接口维护，新建时不预设（BR-171）
	res, err := tx.ExecContext(ctx, `
		INSERT INTO task (taskname, israndomplay, projectstate, timelengthtype, timelength,
		                  prepower, datasendmodel, state, startdate, enddate, playtime, endtime,
		                  exemodel, priority, tasktype, channel, bandrate, samplerate,
		                  cmd, cmdargs, playfileid, info, defaultvolume, task_user_id,
		                  sec_task_id, parentid, offlinestate, disableday,
		                  interval_s, intplaylength, intplaylengthtype, localplay)
		VALUES (?,?,?,?,?, ?,?,0,?,?,?,?, ?,?,?,0,0,0, 0,'0',0,'',?,?, ?,?,0,?, ?,?,?,?)`,
		in.TaskName, in.IsRandomPlay, in.ProjectState, in.TimeLengthTy, in.TimeLength,
		in.PrePower, in.DataSendMode, in.StartDate, in.EndDate, playTime, in.EndTime,
		in.ExeModel, in.Priority, taskType, in.Volume, ownerID,
		secTaskID, in.FolderID, nullDate(in.DisableDay),
		in.IntervalS, in.IntPlayLen, in.IntPlayLenTy, in.LocalPlay)
	if err != nil {
		return 0, fmt.Errorf("写入任务: %w", err)
	}
	return res.LastInsertId()
}

// writeLists 写媒体与终端清单。功放子任务共用同一份终端清单。
func writeLists(ctx context.Context, tx *sql.Tx, taskID, powerID int64, in Input) error {
	for _, m := range in.Media {
		// sort 从 0 开始 —— 与旧版写入器一致（$i 从 0 起），
		// 现网 mediaoftask 里的存量数据也是 0 基。手册写的 1 基是错的。
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO mediaoftask (mediaid, taskid, sort) VALUES (?,?,?)`,
			m.MediaID, taskID, m.Sort); err != nil {
			return fmt.Errorf("写入媒体清单: %w", err)
		}
	}
	targets := []int64{taskID}
	if powerID > 0 {
		targets = append(targets, powerID)
	}
	for _, tid := range targets {
		for _, t := range in.Terminals {
			if _, err := tx.ExecContext(ctx,
				`INSERT INTO terminaloftask (taskid, terminalid, groupid, area) VALUES (?,?,?,?)`,
				tid, t.TerminalID, t.GroupID, t.Area); err != nil {
				return fmt.Errorf("写入终端清单: %w", err)
			}
		}
	}
	return nil
}

// bindTerminalsToOwner 把任务用到的终端补绑给任务归属者。
//
// 这是旧版 addfileplaytask_msg 开头就做的事（缺了它，用户下次进编辑页
// 会看不到自己任务里的终端）。语义保留，只是改成批量判重。
func bindTerminalsToOwner(ctx context.Context, tx *sql.Tx, ownerID int64, terms []TerminalRef) error {
	if len(terms) == 0 || ownerID <= 0 {
		return nil
	}
	ids := make([]int64, 0, len(terms))
	for _, t := range terms {
		ids = append(ids, t.TerminalID)
	}
	ph, args := placeholders(ids)
	rs, err := tx.QueryContext(ctx,
		`SELECT terminalid FROM userterminal WHERE userid = ? AND terminalid IN (`+ph+`)`,
		append([]interface{}{ownerID}, args...)...)
	if err != nil {
		return fmt.Errorf("查询终端绑定: %w", err)
	}
	bound := map[int64]bool{}
	for rs.Next() {
		var id int64
		if err := rs.Scan(&id); err != nil {
			rs.Close()
			return err
		}
		bound[id] = true
	}
	rs.Close()
	if err := rs.Err(); err != nil {
		return err
	}
	for _, id := range ids {
		if bound[id] {
			continue
		}
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO userterminal (userid, terminalid) VALUES (?,?)`, ownerID, id); err != nil {
			return fmt.Errorf("补绑终端: %w", err)
		}
		bound[id] = true
	}
	return nil
}

// ShiftTime 把 HH:MM:SS 平移 deltaSeconds 秒，结果夹在当天范围内。
// 提前量超过 00:00:00 就夹到 00:00:00 —— 旧版这里会算出负数时间写进 time 列。
//
// # ⚠ prepower 的单位是「秒」，不是分钟
//
// 旧界面的下拉写得很清楚（`FileAd/AddFileTask_form.html:940`）：
// 0/5/10/…/55 逐个标「秒」，然后 60/120/180/240/300 标「1~5 分钟」，
// 默认选中 15（秒）。现网作息方案 333 的 prepower = 15，
// 主任务 08:00:00、功放子任务 07:59:45 —— 正好差 15 **秒**。
//
// 旧代码四处平移点（do.php:1922 / 11960 / 12391 / 13440 与
// addonebellplan.php:106）写法完全一致：
//
//	if ($prepower > 59) { $t = $prepower / 60; ... "- {$t} minutes" }
//	else                { $t = $prepower % 60; ... "- {$t} seconds" }
//
// 也就是「秒」被硬拆成两个分支表达。这个写法只在 prepower 是 60 的整数倍时正确：
// prepower = 90 会算出 "1.5 minutes"，PHP 的 strtotime 解析不了这种小数相对量，
// 返回 false，date('H:i:s', false) 于是给出一个与本意无关的时间。
// 直接按秒平移可以逐值复现它**正确时**的结果，同时把 90 这类值算成它本来的意思。
func ShiftTime(hhmmss string, deltaSeconds int) (string, error) {
	var h, m, sec int
	if _, err := fmt.Sscanf(hhmmss, "%d:%d:%d", &h, &m, &sec); err != nil {
		return "", fmt.Errorf("播放时间格式不正确: %s", hhmmss)
	}
	total := h*3600 + m*60 + sec + deltaSeconds
	if total < 0 {
		total = 0
	}
	if total > 86399 {
		total = 86399
	}
	return fmt.Sprintf("%02d:%02d:%02d", total/3600, total%3600/60, total%60), nil
}

// nullDate 把空串转成旧库里通用的零日期，保持与存量数据一致。
func nullDate(d string) string {
	if d == "" {
		return "0000-00-00"
	}
	return d
}

// mustTypeInPlain / typeArgsPlain 是不带表别名的类型条件，供单表语句用。
func mustTypeInPlain() string {
	return strings.Replace(mustTypeIn(), "t.", "", 1)
}

func typeArgsPlain() []interface{} { return typeArgs() }

// PriorityRange 是当前用户能选的任务级别区间，供各任务表单的下拉直接用。
func (s *Service) PriorityRange(ctx context.Context, u *auth.User) (int, int, error) {
	return s.priorityRange(ctx, u.ID)
}
