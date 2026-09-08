package openapi

import (
	"context"
	"fmt"
	"regexp"
	"strings"
	"time"

	"htweb/internal/auth"
	"htweb/internal/notify"
	"htweb/internal/task"
)

// 任务的新建 / 修改 / 删除 / 启停。
//
// # 一次调用 = 界面上的一屏
//
// 界面「新建任务」是一屏：选媒体、选终端、填时间和音量，点一次保存。
// 所以这个接口也是一次调用把三样一起收下，后端负责拆成 task / mediaoftask /
// terminaloftask 三张表的写入，并且**在同一个事务里**（task.Create 的保证）。
//
// 反过来做（先建任务、再挂媒体、再挂终端）的代价不是多几次往返，
// 而是中间任何一步失败都会留下一条残缺的任务 —— 有终端没媒体的任务在界面上
// 看着正常，到点却什么都不播，而没有人知道为什么。
//
// # 参数是「人话」，不是库里的列
//
// 调用方写 weekdays: [1,2,3,4,5]，不写 exemodel: "0111110"；
// 写 seconds: 600，不写 timelengthtype: 1 + timelength: 600；
// 写 media: ["大课间.mp3"]，不写 mediaId: 124。
// 库里那套编码是本系统的内部约定，把它当成对外参数，等于要求对方先读懂我们的表。
//
// # 动作全部交给 task.Service
//
// 这一层只做「把话翻译成 task.Input」。校验、事务、归属检查、通知协议
// 全在 task.Service 里，与界面走的是同一条路 —— 接口不该有第二套规矩，
// 更不该有一条绕过校验的近路。

// TaskInput 是新建 / 修改任务的入参。
type TaskInput struct {
	Name string `json:"name"`
	// Media 是播放清单，按数组顺序播。名字或编号都行。
	Media []Ref `json:"media"`
	// Terminals / Zones 是播到哪。两个可以同时给，最终取并集去重。
	//
	// 分区在这里会**展开成它当前的终端**并落库，而不是存一个「分区」的引用 ——
	// 库里 terminaloftask 存的本来就是终端。这意味着建完任务之后往分区里加终端，
	// 这条任务**不会**自动带上新终端。这一点必须在对外文档里写明白，
	// 否则「我明明把终端加进教学楼了怎么不响」会变成一个查不出原因的故障。
	Terminals []Ref `json:"terminals"`
	Zones     []Ref `json:"zones"`

	// StartDate / EndDate 是生效日期区间，YYYY-MM-DD。留空默认今天起一年。
	StartDate string `json:"startDate"`
	EndDate   string `json:"endDate"`
	// PlayTime 是每天几点播，HH:MM 或 HH:MM:SS。必填。
	PlayTime string `json:"playTime"`
	// EndTime 留空时按 PlayTime + 时长自动算出来。
	EndTime string `json:"endTime"`
	// Weekdays 是 1=周一 … 7=周日。**留空 = 手动任务**（后台永不自动触发），
	// 不是「每天」—— 少写一个字段就变成每天广播，这个默认值太危险。
	Weekdays []int `json:"weekdays"`

	// Seconds 与 LoopTimes 二选一：按时长播，还是把清单循环几遍。
	// 都不给时按媒体自身时长算。
	Seconds   int `json:"seconds"`
	LoopTimes int `json:"loopTimes"`

	Volume   int `json:"volume"`
	Priority int `json:"priority"`
	// PrePower 是提前多少秒开功放电源。
	PrePower int `json:"prePower"`
	// Enabled 是启用还是停用。**指针**：不给 = 新建时默认启用、修改时保持原样。
	// 用普通 bool 的话，「只想改个音量」的请求会顺手把任务停掉。
	Enabled *bool `json:"enabled"`
	// Sequential 为真表示顺序播，假表示随机播。默认顺序。
	//
	// ⚠ 库里那一列（israndomplay）取值反直觉：0 = 随机、1 = 顺序（BR-163）。
	//   对外不暴露这个坑，只给一个语义明确的布尔。
	Sequential *bool `json:"sequential"`

	// Folder 是任务分组，名字或编号都行。
	//
	// ⚠ 分组是**必填**的（库里 task.parentid 指向 filetaskfree）——
	//   不给时自动落到调用方看得见的第一个分组，而不是报错让他先去查一遍：
	//   现网绝大多数装机只有一个分组，为它多一次往返不值得。
	//   真有多个分组而他没指定时，任务会进哪个是确定的（按编号最小），
	//   这一点在对外文档里写明。
	Folder Ref `json:"folder"`
	// ⚠ 这里**没有** note 字段，是刻意的：task.info 那一列在新建任务时
	//   写死空串、修改时完全不碰（见 task/edit.go 的 INSERT）。
	//   给一个「写了不生效」的字段，比不给这个字段坏得多 ——
	//   调用方会以为备注存下来了。列表接口回的 note 是只读的。
}

var (
	reOpenDate = regexp.MustCompile(`^\d{4}-\d{2}-\d{2}$`)
	reOpenTime = regexp.MustCompile(`^\d{1,2}:\d{2}(:\d{2})?$`)
)

// TaskSaved 是写入成功后的回执。
type TaskSaved struct {
	ID        int64 `json:"id"`
	Media     int   `json:"mediaCount"`
	Terminals int   `json:"terminalCount"`
}

// CreateTask 新建一条文件广播任务。
func (s *Service) CreateTask(ctx context.Context, u *auth.User, in TaskInput) (*TaskSaved, error) {
	if s.tasks == nil || s.notifier == nil {
		return nil, fmt.Errorf("任务服务未接入")
	}
	spec, err := s.toTaskInput(ctx, u, in, nil)
	if err != nil {
		return nil, err
	}
	res, err := s.tasks.Create(ctx, u, *spec)
	if err != nil {
		return nil, err
	}
	// 新增走 state=4 且必须带 volume —— 后台服务据此加载任务并设初始音量。
	// 漏掉这一步的表现是：任务在界面上建好了，到点却不响。
	s.notifier.TaskSaved(ctx, notify.TaskAdded, res.TaskID, res.Volume)
	return &TaskSaved{ID: res.TaskID, Media: res.MediaCount, Terminals: res.TerminalCount}, nil
}

// UpdateTask 改一条已有任务。
//
// ⚠ 语义是**整体替换**，不是局部合并：没给的字段回到默认值，
// 给了空数组的清单就是清空。这一点必须在文档里写死 —— 调用方以为是
// 「只改音量」而实际上把终端清单清空了，是最难查的一类故障。
// 所以先取回原任务当底稿，再把请求里给了的字段盖上去。
func (s *Service) UpdateTask(ctx context.Context, u *auth.User, ref Ref, in TaskInput) (*TaskSaved, error) {
	if s.tasks == nil || s.notifier == nil {
		return nil, fmt.Errorf("任务服务未接入")
	}
	id, err := s.resolveOne(ctx, u, s.specTask(), ref)
	if err != nil {
		return nil, err
	}
	old, err := s.tasks.Get(ctx, u, id)
	if err != nil {
		return nil, err
	}
	spec, err := s.toTaskInput(ctx, u, in, old)
	if err != nil {
		return nil, err
	}
	res, err := s.tasks.Update(ctx, u, id, *spec)
	if err != nil {
		return nil, err
	}
	s.notifier.TaskSaved(ctx, notify.TaskUpdated, res.TaskID, res.Volume)
	return &TaskSaved{ID: res.TaskID, Media: res.MediaCount, Terminals: res.TerminalCount}, nil
}

// toTaskInput 把「人话」翻译成 task.Input。
//
// old 非空表示改任务：没给的字段沿用原值。新建时 old 为 nil，用默认值。
func (s *Service) toTaskInput(ctx context.Context, u *auth.User, in TaskInput, old *task.Detail) (*task.Input, error) {
	name, err := trimName(in.Name)
	if err != nil {
		return nil, err
	}
	if name == "" && old != nil {
		name = old.TaskName
	}
	if name == "" {
		return nil, badf("请给任务起个名字")
	}
	if len([]rune(name)) > 45 {
		return nil, badf("任务名太长了，最多 45 个字")
	}

	// —— 媒体 ——
	mediaIDs, err := s.resolveRefs(ctx, u, s.specMedia(), in.Media)
	if err != nil {
		return nil, err
	}
	if len(in.Media) == 0 && old != nil {
		for _, m := range old.Media {
			mediaIDs = append(mediaIDs, m.MediaID)
		}
	}

	// —— 终端（分区在这里展开）——
	termIDs, err := s.resolveTargets(ctx, u, in.Terminals, in.Zones)
	if err != nil {
		return nil, err
	}
	if len(in.Terminals) == 0 && len(in.Zones) == 0 && old != nil {
		for _, t := range old.Terminals {
			termIDs = append(termIDs, t.TerminalID)
		}
	}

	// —— 时间 ——
	playTime, err := normalizeTime(in.PlayTime, "播放时间")
	if err != nil {
		return nil, err
	}
	if playTime == "" && old != nil {
		playTime = old.PlayTime
	}
	if playTime == "" {
		return nil, badf("请给出播放时间，例如 09:50")
	}

	startDate, endDate, err := dateRange(in, old)
	if err != nil {
		return nil, err
	}

	exeModel, err := exeModelOf(in.Weekdays, old)
	if err != nil {
		return nil, err
	}

	// —— 时长 ——
	lenTy, length, err := lengthOf(ctx, s, in, old, mediaIDs)
	if err != nil {
		return nil, err
	}

	endTime, err := normalizeTime(in.EndTime, "结束时间")
	if err != nil {
		return nil, err
	}
	if endTime == "" {
		endTime = defaultEndTime(playTime, lenTy, length, old)
	}

	folderID, err := s.pickFolder(ctx, u, in.Folder, old)
	if err != nil {
		return nil, err
	}

	spec := &task.Input{
		TaskName:     name,
		FolderID:     folderID,
		ProjectState: projectStateOf(in.Enabled, old),
		IsRandomPlay: randomPlayOf(in.Sequential, old),
		StartDate:    startDate,
		EndDate:      endDate,
		PlayTime:     playTime,
		EndTime:      endTime,
		ExeModel:     exeModel,
		TimeLengthTy: lenTy,
		TimeLength:   length,
		Volume:       pickInt(in.Volume, old, func(d *task.Detail) int { return d.Volume }, 80),
		Priority:     pickInt(in.Priority, old, func(d *task.Detail) int { return d.Priority }, 0),
		PrePower:     pickInt(in.PrePower, old, func(d *task.Detail) int { return d.PrePower }, 0),
		// 间隔播放这套参数界面上默认就是「1 次、类型 1」，
		// 开发者接口不暴露它 —— 用得上的人本来就得进界面配。
		IntPlayLenTy: 1,
		IntPlayLen:   0,
		IntervalS:    0,
	}
	if old != nil {
		spec.IntPlayLenTy = old.IntPlayLenTy
		spec.IntPlayLen = old.IntPlayLen
		spec.IntervalS = old.IntervalS
		spec.LocalPlay = old.LocalPlay
		spec.DataSendMode = old.DataSendMode
		spec.DisableDay = old.DisableDay
	}
	if spec.IntPlayLenTy != 1 && spec.IntPlayLenTy != 2 {
		spec.IntPlayLenTy = 1
	}
	// 优先级 0 是非法值（范围由用户组级别决定，最低那一档也不是 0），
	// 新建又没给时取「该用户能用的最低优先级」——
	// 默认给最高优先级会让一条接口建的任务压过所有人的广播。
	if spec.Priority == 0 {
		lo, err := s.lowestPriority(ctx, u)
		if err != nil {
			return nil, err
		}
		spec.Priority = lo
	}

	for i, id := range mediaIDs {
		spec.Media = append(spec.Media, task.MediaRef{MediaID: id, Sort: i})
	}
	for _, id := range termIDs {
		spec.Terminals = append(spec.Terminals, task.TerminalRef{TerminalID: id, Area: task.AreaAll})
	}
	return spec, nil
}

// resolveTargets 把终端与分区两个清单合成一份终端 id，去重且保序。
func (s *Service) resolveTargets(ctx context.Context, u *auth.User, terms, zones []Ref) ([]int64, error) {
	ids, err := s.resolveRefs(ctx, u, s.specTerminal(), terms)
	if err != nil {
		return nil, err
	}
	if len(zones) == 0 {
		return ids, nil
	}
	zoneIDs, err := s.resolveRefs(ctx, u, s.specZone(), zones)
	if err != nil {
		return nil, err
	}
	seen := map[int64]bool{}
	for _, id := range ids {
		seen[id] = true
	}
	for _, zid := range zoneIDs {
		members, err := s.zoneTerminals(ctx, u, zid)
		if err != nil {
			return nil, err
		}
		// 空分区不是「没关系」：调用方以为播到了一屋子终端，实际一个都没有。
		if len(members) == 0 {
			return nil, badf("分区（编号 %d）里一个终端都没有", zid)
		}
		for _, id := range members {
			if !seen[id] {
				seen[id] = true
				ids = append(ids, id)
			}
		}
	}
	return ids, nil
}

// zoneTerminals 取分区当前的终端。
//
// ⚠ 只取当前**可见**的那些：普通账号的密钥不能靠「往分区里一指」
// 把没绑给它的终端也拉进任务。
func (s *Service) zoneTerminals(ctx context.Context, u *auth.User, zoneID int64) ([]int64, error) {
	q := `SELECT t.id FROM terminal t
	      JOIN terminalofgroup tog ON tog.terminalid = t.id
	     WHERE tog.groupid = ? AND t.typeid <> 0`
	args := []any{zoneID}
	if !u.IsAdmin {
		q += ` AND t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)`
		args = append(args, u.ID)
	}
	rows, err := s.db.QueryContext(ctx, q+` ORDER BY t.id`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询分区终端: %w", err)
	}
	defer rows.Close()
	var out []int64
	for rows.Next() {
		var id int64
		if err := rows.Scan(&id); err != nil {
			return nil, err
		}
		out = append(out, id)
	}
	return out, rows.Err()
}

// specTask 是任务的寻址范围：文件广播任务，按 task_user_id 收敛。
func (s *Service) specTask() lookupSpec {
	return lookupSpec{what: "任务", query: func(ctx context.Context, u *auth.User) ([]namedRow, error) {
		// 类型集合问 task 包要（BR-159），不在这边写死 ——
		// 写死的话那边哪天加一类，这边会静默地少认一类任务。
		// 作息任务不在这里：它们归 /openapi/v1/schedules 那一组管，
		// 混进来会让「删任务」删掉方案里的一条铃，
		// 而调用方以为自己删的是一条普通广播。
		types := task.FileTypes()
		ph := strings.TrimSuffix(strings.Repeat("?,", len(types)), ",")
		q := `SELECT taskid, COALESCE(taskname,'') FROM task WHERE tasktype IN (` + ph + `)`
		args := make([]any, 0, len(types)+1)
		for _, t := range types {
			args = append(args, t)
		}
		if !u.IsAdmin {
			q += ` AND COALESCE(task_user_id,0) = ?`
			args = append(args, u.ID)
		}
		return s.queryNamed(ctx, q+` ORDER BY taskid`, args...)
	}}
}

// normalizeTime 把 HH:MM 补成 HH:MM:SS。空串原样返回（表示「没给」）。
func normalizeTime(v, what string) (string, error) {
	v = strings.TrimSpace(v)
	if v == "" {
		return "", nil
	}
	if !reOpenTime.MatchString(v) {
		return "", badf("%s格式不对，用 09:50 或 09:50:00", what)
	}
	parts := strings.Split(v, ":")
	if len(parts) == 2 {
		parts = append(parts, "00")
	}
	if len(parts[0]) == 1 {
		parts[0] = "0" + parts[0]
	}
	hh, mm := parts[0], parts[1]
	if hh > "23" || mm > "59" || parts[2] > "59" {
		return "", badf("%s不是一个有效的时刻：%s", what, v)
	}
	return hh + ":" + mm + ":" + parts[2], nil
}

// dateRange 定生效区间。都不给时默认「今天起一年」。
func dateRange(in TaskInput, old *task.Detail) (string, string, error) {
	start := strings.TrimSpace(in.StartDate)
	end := strings.TrimSpace(in.EndDate)
	if start == "" && old != nil {
		start = old.StartDate
	}
	if end == "" && old != nil {
		end = old.EndDate
	}
	now := time.Now()
	if start == "" {
		start = now.Format("2006-01-02")
	}
	if end == "" {
		end = now.AddDate(1, 0, 0).Format("2006-01-02")
	}
	for _, spec := range []struct{ what, val string }{{"开始日期", start}, {"结束日期", end}} {
		if !reOpenDate.MatchString(spec.val) {
			return "", "", badf("%s格式不对，用 2026-09-01", spec.what)
		}
	}
	if start > end {
		return "", "", badf("结束日期不能早于开始日期")
	}
	return start, end, nil
}

// exeModelOf 把 ISO 星期（1=周一…7=周日）转成库里那个**周日打头**的 7 位掩码。
//
// 位序的权威依据见 query_test.go 上的推导。留空 = 手动任务（"0000000"），
// 不是「每天」—— 默认成每天广播的代价太大。
func exeModelOf(days []int, old *task.Detail) (string, error) {
	if len(days) == 0 {
		if old != nil {
			return old.ExeModel, nil
		}
		return "0000000", nil
	}
	mask := []byte("0000000")
	for _, d := range days {
		if d < 1 || d > 7 {
			return "", badf("星期只能是 1~7（1=周一，7=周日），收到 %d", d)
		}
		if d == 7 {
			mask[0] = '1' // 周日在第 1 位
		} else {
			mask[d] = '1'
		}
	}
	return string(mask), nil
}

// lengthOf 定播放时长。
func lengthOf(ctx context.Context, s *Service, in TaskInput, old *task.Detail, mediaIDs []int64) (int, int, error) {
	if in.Seconds > 0 && in.LoopTimes > 0 {
		return 0, 0, badf("seconds 与 loopTimes 只能给一个：要么按时长播，要么按遍数播")
	}
	switch {
	case in.Seconds > 0:
		if in.Seconds > 86400 {
			return 0, 0, badf("时长最多 86400 秒（一天）")
		}
		return 1, in.Seconds, nil // 1 = 按时间（秒）
	case in.LoopTimes > 0:
		if in.LoopTimes > 86400 {
			return 0, 0, badf("循环次数最多 86400")
		}
		return 2, in.LoopTimes, nil // 2 = 按循环次数
	case old != nil:
		return old.TimeLengthTy, old.TimeLength, nil
	}
	// 都没给：按媒体自身总时长。算不出来时兜底 60 秒 ——
	// ⚠ 绝不能落 0：timelength = 0 是「不限时长」，任务会一直播下去，
	//   而调用方只是没填一个可选字段。
	total := s.mediaSeconds(ctx, mediaIDs)
	if total <= 0 {
		total = 60
	}
	if total > 86400 {
		total = 86400
	}
	return 1, total, nil
}

// mediaSeconds 把一组媒体的时长加起来。查不到就当 0。
func (s *Service) mediaSeconds(ctx context.Context, ids []int64) int {
	if len(ids) == 0 {
		return 0
	}
	ph := strings.TrimSuffix(strings.Repeat("?,", len(ids)), ",")
	args := make([]any, 0, len(ids))
	for _, id := range ids {
		args = append(args, id)
	}
	var total int
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(SUM(COALESCE(timelength,0)),0) FROM media WHERE id IN (`+ph+`)`,
		args...).Scan(&total)
	if err != nil {
		logf("统计媒体时长失败（按兜底值处理）: %v", err)
		return 0
	}
	return total
}

// defaultEndTime 按开始时刻 + 时长推出结束时刻。
//
// 只有「按秒数」能算得准；按循环次数播时算不出来，退回原值或开始时刻 + 1 小时。
func defaultEndTime(playTime string, lenTy, length int, old *task.Detail) string {
	if old != nil && old.EndTime != "" {
		return old.EndTime
	}
	base, err := time.Parse("15:04:05", playTime)
	if err != nil {
		return playTime
	}
	add := time.Hour
	if lenTy == 1 && length > 0 {
		add = time.Duration(length) * time.Second
	}
	return base.Add(add).Format("15:04:05")
}

// projectStateOf：0 = 启用、1 = 停用（BR：与直觉相反）。
func projectStateOf(enabled *bool, old *task.Detail) int {
	if enabled != nil {
		if *enabled {
			return 0
		}
		return 1
	}
	if old != nil {
		return old.ProjectState
	}
	return 0
}

// randomPlayOf：库里 0 = 随机、1 = 顺序（BR-163，取值反直觉）。默认顺序。
func randomPlayOf(sequential *bool, old *task.Detail) int {
	if sequential != nil {
		if *sequential {
			return 1
		}
		return 0
	}
	if old != nil {
		return old.IsRandomPlay
	}
	return 1
}

func pickInt(v int, old *task.Detail, from func(*task.Detail) int, def int) int {
	if v != 0 {
		return v
	}
	if old != nil {
		return from(old)
	}
	return def
}

// pickFolder 定任务分组。
//
// 给了就按名字/编号解析；没给且是改任务就沿用原分组；
// 都不是就落到调用方看得见的第一个分组。
func (s *Service) pickFolder(ctx context.Context, u *auth.User, ref Ref, old *task.Detail) (int64, error) {
	if !ref.empty() {
		return s.resolveOne(ctx, u, s.specTaskFolder(), ref)
	}
	if old != nil && old.FolderID > 0 {
		return old.FolderID, nil
	}
	rows, err := s.specTaskFolder().query(ctx, u)
	if err != nil {
		return 0, err
	}
	if len(rows) == 0 {
		return 0, badf("这个账号名下还没有任务分组，请先在界面「任务管理」里建一个")
	}
	return rows[0].ID, nil
}

// specTaskFolder 是任务分组的寻址范围（filetaskfree）。
func (s *Service) specTaskFolder() lookupSpec {
	return lookupSpec{what: "任务分组", query: func(ctx context.Context, u *auth.User) ([]namedRow, error) {
		q := `SELECT id, COALESCE(name,'') FROM filetaskfree`
		var args []any
		if !u.IsAdmin {
			q += ` WHERE COALESCE(userid,0) = ?`
			args = append(args, u.ID)
		}
		return s.queryNamed(ctx, q+` ORDER BY id`, args...)
	}}
}

// lowestPriority 取这个账号能用的最低优先级（数字最大的那一档）。
//
// ⚠ 优先级是「小的赢」：10 最高、109 最低。没给优先级时取最低那一档，
// 是因为反过来的代价不对称 —— 默认成最高，一条接口建的任务会压过
// 消防疏散广播；默认成最低，最坏结果只是它被别的广播压住。
//
// 区间问 task.Service 要，不在这边算：那套规则（按用户组级别定上下限）
// 只有一个权威定义，抄一份出来迟早和它对不上。
func (s *Service) lowestPriority(ctx context.Context, u *auth.User) (int, error) {
	_, lowest, err := s.tasks.PriorityRange(ctx, u)
	if err != nil {
		return 0, err
	}
	return lowest, nil
}

// TaskAction 是对任务的一个动作。
type TaskAction string

const (
	// ActionStart / ActionStop 是「现在开始播 / 现在停」——**不改任务本身**，
	// 停掉的任务明天到点照样会响。
	ActionStart TaskAction = "start"
	ActionStop  TaskAction = "stop"
	// ActionEnable / ActionDisable 是「启用 / 停用」——改的是任务的排期状态，
	// 停用之后就再也不会自动触发了。
	//
	// ⚠ 这两组是**完全不同的两件事**，中文里都能叫「停」。
	//   接口用两组词分开，就是为了不让调用方靠猜：
	//   想让今天的铃别响是 stop，想让这条任务以后都别响是 disable。
	ActionEnable  TaskAction = "enable"
	ActionDisable TaskAction = "disable"
)

// TaskActionResult 是一次批量动作的回执。
//
// 语义是「部分成功」：能做的做掉，做不了的逐条给原因。
// 全有或全无在这里不合适 —— 一次给二十条任务，因为其中一条不归你
// 就把另外十九条也拒掉，调用方除了逐条重试没有别的办法。
type TaskActionResult struct {
	Succeeded []NamedItem   `json:"succeeded"`
	Blocked   []BlockedItem `json:"blocked"`
}

// BlockedItem 说明某一条为什么没做成。
type BlockedItem struct {
	ID     int64  `json:"id"`
	Name   string `json:"name"`
	Reason string `json:"reason"`
}

// ControlTasks 对一批任务执行动作。
func (s *Service) ControlTasks(ctx context.Context, u *auth.User,
	action TaskAction, refs []Ref) (*TaskActionResult, error) {

	if s.tasks == nil || s.notifier == nil {
		return nil, fmt.Errorf("任务服务未接入")
	}
	ids, err := s.resolveRefs(ctx, u, s.specTask(), refs)
	if err != nil {
		return nil, err
	}
	if len(ids) == 0 {
		return nil, badf("请指定要操作的任务")
	}

	var res *task.ControlResult
	switch action {
	case ActionStart, ActionStop:
		res, err = s.tasks.Control(ctx, u, s.notifier, task.Action(action), ids)
	case ActionEnable:
		res, err = s.tasks.SetProjectState(ctx, u, ids, true)
	case ActionDisable:
		res, err = s.tasks.SetProjectState(ctx, u, ids, false)
	default:
		return nil, badf("不支持的动作 %q，可用：start / stop / enable / disable", string(action))
	}
	if err != nil {
		return nil, err
	}
	return s.toActionResult(ctx, u, res), nil
}

// DeleteTasks 删任务。
func (s *Service) DeleteTasks(ctx context.Context, u *auth.User, refs []Ref) (*TaskActionResult, error) {
	if s.tasks == nil {
		return nil, fmt.Errorf("任务服务未接入")
	}
	ids, err := s.resolveRefs(ctx, u, s.specTask(), refs)
	if err != nil {
		return nil, err
	}
	if len(ids) == 0 {
		return nil, badf("请指定要删除的任务")
	}
	// ⚠ 名字要在**删之前**取。删完再查是查不到的，回执里只剩一串编号，
	//   而调用方多半是拿名字来调的 —— 他没法确认自己删掉的是不是想删的那条。
	names := s.namesOf(ctx, u, ids)

	res, err := s.tasks.Delete(ctx, u, ids)
	if err != nil {
		return nil, err
	}
	out := &TaskActionResult{Succeeded: []NamedItem{}, Blocked: []BlockedItem{}}
	for _, id := range res.Deleted {
		out.Succeeded = append(out.Succeeded, NamedItem{ID: id, Name: names[id]})
	}
	for _, b := range res.Blocked {
		out.Blocked = append(out.Blocked, BlockedItem{ID: b.ID, Name: b.Name, Reason: b.Detail})
	}
	return out, nil
}

func (s *Service) toActionResult(ctx context.Context, u *auth.User, res *task.ControlResult) *TaskActionResult {
	names := s.namesOf(ctx, u, res.Succeeded)
	out := &TaskActionResult{Succeeded: []NamedItem{}, Blocked: []BlockedItem{}}
	for _, id := range res.Succeeded {
		out.Succeeded = append(out.Succeeded, NamedItem{ID: id, Name: names[id]})
	}
	for _, b := range res.Blocked {
		out.Blocked = append(out.Blocked, BlockedItem{ID: b.ID, Name: b.Name, Reason: b.Detail})
	}
	return out
}

// namesOf 回填任务名。
//
// 回执里带上名字是有意的：调用方多半是拿名字来调的，回一串纯数字
// 等于让他自己再查一遍才能确认「刚才停的到底是哪几条」。
// 查不到名字不算错（任务可能刚被删掉），留空即可。
func (s *Service) namesOf(ctx context.Context, u *auth.User, ids []int64) map[int64]string {
	out := map[int64]string{}
	if len(ids) == 0 {
		return out
	}
	rows, err := s.specTask().query(ctx, u)
	if err != nil {
		logf("回填任务名失败（回执里只给编号）: %v", err)
		return out
	}
	want := map[int64]bool{}
	for _, id := range ids {
		want[id] = true
	}
	for _, r := range rows {
		if want[r.ID] {
			out[r.ID] = r.Name
		}
	}
	return out
}
