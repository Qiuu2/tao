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
	// ⚠ 每一项可以只写名字/编号，也可以写成
	//   { "terminal": "A101教室音箱", "area": "11110000" } 来指定区域掩码。
	Terminals []TerminalPick `json:"terminals"`
	Zones     []Ref          `json:"zones"`

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

	// DisableDay 是「这一天不执行」，YYYY-MM-DD。留空 = 没有例外日。
	// 界面上那一项叫「当天停用」。
	DisableDay string `json:"disableDay"`

	// Seconds 与 LoopTimes 二选一：按时长播，还是把清单循环几遍。
	// 都不给时按媒体自身时长算。
	//
	// ⚠ LoopTimes 填 0 在这套库里是**无限循环**，界面上也这么写；
	//   所以「不填」和「填 0」是两回事，别把 0 当成「不循环」。
	Seconds   int `json:"seconds"`
	LoopTimes int `json:"loopTimes"`

	// Interval 是**间隔播放**：界面「播放模式」选「间隔时间」时的那几项。
	// 不给 = 普通模式，从头播到尾。
	Interval *TaskInterval `json:"interval"`

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

	// Multicast 为真走组播，假走单播。默认单播。界面上叫「发送模式」。
	//
	// 终端多（几百台以上）时组播能省带宽，但要求交换机开了组播；
	// 没把握就别动，用默认的单播。
	Multicast *bool `json:"multicast"`

	// LocalFirst 为真表示**本地优先播放**：终端上已经下发过这个媒体时，
	// 直接放本地的那份，不走网络流。断网也能响，代价是要先把媒体下发到终端
	// （云广播 → 音乐传输）。默认关。
	LocalFirst *bool `json:"localFirst"`

	// LED 是这条任务配套的 LED 字幕。新建时不给 = 不上屏。
	//
	// ⚠ 改任务时**不给就保持原样**，与其它字段一致。要取消字幕，
	//   明确传 { "text": "" } —— 空正文就是「不上屏」。
	//
	//   一开始这里跟界面一样：没传就当「用户取消了勾选」，于是把字幕删掉。
	//   但界面每次提交的是整张表单，而这个接口是**局部更新**的语义 ——
	//   「只改个音量」的请求顺手删掉 LED 字幕，是最难查的一类故障：
	//   调用方没提过 LED，也就不会想到去看它。实测撞出来的，已改。
	LED *TaskLED `json:"led"`

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

// TaskInterval 是间隔播放：每隔一段时间响一次，而不是从头播到尾。
//
// 典型用法是课间循环音乐、施工提示音：总时长 1 小时，每 5 分钟播 30 秒。
//
//	{ "seconds": 3600, "interval": { "everySeconds": 300, "playSeconds": 30 } }
//
// PlaySeconds 与 PlayTimes 二选一：一次播多少秒，还是一次把清单播几遍。
type TaskInterval struct {
	// EverySeconds 是间隔长度（界面上的「间隔长度」）。必须 > 0。
	EverySeconds int `json:"everySeconds"`
	// PlaySeconds 是每次响多久（界面上的「间隔时长」）。
	PlaySeconds int `json:"playSeconds"`
	// PlayTimes 是每次播几遍（界面上的「间隔次数」）。
	PlayTimes int `json:"playTimes"`
}

// TaskLED 是挂在任务上的 LED 字幕。
type TaskLED struct {
	// Text 是字幕正文。空 = 不上屏。
	Text string `json:"text"`
	// Name 是字幕子任务自己的名字。留空跟主任务同名。
	Name string `json:"name"`
	// Speed 是滚动速度 0~10。
	Speed int `json:"speed"`
	// Mode 是显示模式 0~10（滚动/闪烁之类，取值随屏的型号）。
	Mode int `json:"mode"`
	// Devices 是字幕上到哪几块屏。空 = 不绑设备，只存字幕。
	Devices []TaskLEDDevice `json:"devices"`
}

// TaskLEDDevice 是一块 LED 屏：它挂在哪台终端下、是那台终端的第几块屏。
type TaskLEDDevice struct {
	// Terminal 是这块屏挂在哪台终端下，名字或编号。
	Terminal Ref `json:"terminal"`
	// DeviceID 是 LED 屏设备编号（在「led播放 → LED 屏设备」里看）。
	DeviceID int64 `json:"deviceId"`
}

var (
	reOpenDate = regexp.MustCompile(`^\d{4}-\d{2}-\d{2}$`)
	reOpenTime = regexp.MustCompile(`^\d{1,2}:\d{2}(:\d{2})?$`)
)

// TaskSaved 是写入成功后的回执。
//
// # 为什么回执里要带上这条任务「现在是什么样」
//
// 这两个接口有一批字段是**服务端替调用方定的**：没给 endTime 就按
// 播放时刻 + 时长算，没给时长就按媒体总长算，没给 priority 就取最低一档，
// 没给分组就落到第一个分组。只回一个 {id, mediaCount, terminalCount}，
// 调用方无从知道服务端替他决定了什么 —— 尤其是**改时长会连带重算结束时刻**
// 这件事，回执不说，他就只能等到某天发现广播比预期多响了五分钟。
//
// 所以把这些「服务端定下来的」值回给他。想看全貌仍然是「任务详情」——
// 这里只列服务端可能自作主张的那几项，不重复一份详情。
type TaskSaved struct {
	ID        int64 `json:"id"`
	Media     int   `json:"mediaCount"`
	Terminals int   `json:"terminalCount"`

	// —— 下面这些是**实际存进去的值**，不是你传的原文 ——
	Name      string `json:"name"`
	FolderID  int64  `json:"folderId"`
	PlayTime  string `json:"playTime"`
	EndTime   string `json:"endTime"`
	Seconds   int    `json:"seconds"`
	LoopTimes int    `json:"loopTimes"`
	Volume    int    `json:"volume"`
	Priority  int    `json:"priority"`
	Enabled   bool   `json:"enabled"`
}

// savedOf 把「实际写进去的那份 task.Input」翻成回执。
//
// 取的是 spec（落库的那一份）而不是调用方传来的 in —— 两者的差正是
// 这个回执存在的理由。
func savedOf(res *task.SaveResult, spec *task.Input) *TaskSaved {
	out := &TaskSaved{
		ID: res.TaskID, Media: res.MediaCount, Terminals: res.TerminalCount,
		Name: spec.TaskName, FolderID: spec.FolderID,
		PlayTime: spec.PlayTime, EndTime: spec.EndTime,
		Volume: spec.Volume, Priority: spec.Priority,
		// projectstate 0 才是启用（BR：与直觉相反），这里翻成正常的布尔
		Enabled: spec.ProjectState == 0,
	}
	// 库里 timelength 一列共用：类型 1 是秒、2 是循环次数。
	// 回执里分开给，免得调用方还要自己看 timelengthtype 才知道 900 是什么。
	if spec.TimeLengthTy == 1 {
		out.Seconds = spec.TimeLength
	} else {
		out.LoopTimes = spec.TimeLength
	}
	return out
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
	return savedOf(res, spec), nil
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
	return savedOf(res, spec), nil
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
	termIDs, areaOf, err := s.resolveTaskTargets(ctx, u, in.Terminals, in.Zones)
	if err != nil {
		return nil, err
	}
	if len(in.Terminals) == 0 && len(in.Zones) == 0 && old != nil {
		// 没提终端就沿用原来的 —— 连同它们各自的区域掩码一起，
		// 否则「只改个音量」会把所有终端的分路悄悄重置成全开。
		for _, t := range old.Terminals {
			termIDs = append(termIDs, t.TerminalID)
			if t.Area != "" {
				areaOf[t.TerminalID] = t.Area
			}
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
		// 调用方给了 seconds 或 loopTimes 就算「动了时长」；
		// 给了 playTime 就算「挪了开播时刻」。两者都会让原来的结束时刻失效。
		lengthChanged := in.Seconds > 0 || in.LoopTimes > 0
		playTimeChanged := in.PlayTime != ""
		endTime = defaultEndTime(playTime, lenTy, length, old, lengthChanged, playTimeChanged)
	}

	folderID, err := s.pickFolder(ctx, u, in.Folder, old)
	if err != nil {
		return nil, err
	}

	disableDay, err := disableDayOf(in.DisableDay, old)
	if err != nil {
		return nil, err
	}

	ivEvery, ivLen, ivLenTy, err := intervalOf(in, old)
	if err != nil {
		return nil, err
	}
	// ⚠ 间隔播放要求总时长按**秒**算：库里 timelength 那一列这时是总秒数，
	//   界面切到「间隔时间」时也强制把 timelengthtype 设回 1。
	//   留成 2（循环次数）的话，后台按次数算总时长，间隔参数全失效。
	if ivEvery > 0 && lenTy != 1 {
		return nil, badf("间隔播放要按时长算总长度，请用 seconds 而不是 loopTimes")
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
		DisableDay:   disableDay,
		LocalPlay:    boolTo01(in.LocalFirst, old, func(d *task.Detail) int { return d.LocalPlay }),
		DataSendMode: boolTo01(in.Multicast, old, func(d *task.Detail) int { return d.DataSendMode }),
		// 间隔播放的三项见 intervalOf
		IntervalS:    ivEvery,
		IntPlayLen:   ivLen,
		IntPlayLenTy: ivLenTy,
	}
	// ⚠ IntPlayLenTy 必须是 1 或 2 —— task.Create 会挡下 0，
	//   而普通模式下这一项本来就没意义，给个合法的默认值即可。
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
		area := areaOf[id]
		if area == "" {
			area = task.AreaAll
		}
		spec.Terminals = append(spec.Terminals, task.TerminalRef{TerminalID: id, Area: area})
	}

	// —— LED 字幕 ——
	led, err := s.ledOf(ctx, u, in.LED, name, old)
	if err != nil {
		return nil, err
	}
	spec.LED = led

	return spec, nil
}

// disableDayOf 定「当天停用」那一天。
func disableDayOf(v string, old *task.Detail) (string, error) {
	v = strings.TrimSpace(v)
	if v == "" {
		if old != nil {
			return old.DisableDay, nil
		}
		return "", nil
	}
	if !reOpenDate.MatchString(v) {
		return "", badf("当天停用日期格式不对，用 2026-10-01")
	}
	return v, nil
}

// intervalOf 算间隔播放的三项：间隔长度、每次播多少、按秒还是按遍。
//
// 返回 (0, 0, 1) 表示普通模式 —— interval_s = 0 就是「不间隔」，
// 库里没有单独的「播放模式」列，界面也是靠它反推的。
func intervalOf(in TaskInput, old *task.Detail) (every, length, lengthTy int, err error) {
	iv := in.Interval
	if iv == nil {
		// 没提就沿用原值。改任务时不该把间隔播放悄悄关掉。
		if old != nil {
			ty := old.IntPlayLenTy
			if ty != 1 && ty != 2 {
				ty = 1
			}
			return old.IntervalS, old.IntPlayLen, ty, nil
		}
		return 0, 0, 1, nil
	}
	if iv.EverySeconds <= 0 {
		return 0, 0, 1, badf("间隔播放要给 interval.everySeconds（间隔多少秒响一次）")
	}
	if iv.EverySeconds > 86400 {
		return 0, 0, 1, badf("间隔长度最多 86400 秒（一天）")
	}
	switch {
	case iv.PlaySeconds > 0 && iv.PlayTimes > 0:
		return 0, 0, 1, badf("interval 里 playSeconds 与 playTimes 只能给一个：一次播多少秒，还是一次播几遍")
	case iv.PlaySeconds > 0:
		if iv.PlaySeconds > 86400 {
			return 0, 0, 1, badf("间隔时长最多 86400 秒")
		}
		return iv.EverySeconds, iv.PlaySeconds, 1, nil
	case iv.PlayTimes > 0:
		if iv.PlayTimes > 99 {
			return 0, 0, 1, badf("间隔次数最多 99 次")
		}
		return iv.EverySeconds, iv.PlayTimes, 2, nil
	default:
		return 0, 0, 1, badf("间隔播放还要给 interval.playSeconds（每次播多少秒）或 interval.playTimes（每次播几遍）")
	}
}

// boolTo01 把一个可选布尔翻成库里的 0/1，没给就沿用原值、再没有就 0。
func boolTo01(v *bool, old *task.Detail, from func(*task.Detail) int) int {
	if v != nil {
		if *v {
			return 1
		}
		return 0
	}
	if old != nil {
		return from(old)
	}
	return 0
}

// ledOf 把「人话」的 LED 字幕翻成 task.LEDSub。
//
// ⚠ in 为 nil（调用方没提 LED）时**沿用原有字幕**，与其它字段的局部更新
// 语义一致。要取消字幕得明确传 { "text": "" }。
//
// 早先这里 nil 就返回 nil，于是「只改个音量」的请求会把 LED 字幕一起删掉 ——
// 调用方没提过 LED，也就不会想到去看它，是最难查的一类故障。实测撞出来的。
func (s *Service) ledOf(ctx context.Context, u *auth.User, in *TaskLED, taskName string,
	old *task.Detail) (*task.LEDSub, error) {

	if in == nil {
		if old != nil {
			return old.LED, nil
		}
		return nil, nil
	}
	text, err := trimName(in.Text)
	if err != nil {
		return nil, err
	}
	if text == "" {
		return nil, nil
	}
	if len([]rune(text)) > 1024 {
		return nil, badf("led 字幕正文太长了，最多 1024 个字")
	}
	if in.Speed < 0 || in.Speed > 10 {
		return nil, badf("led 速度要在 0 ~ 10 之间")
	}
	if in.Mode < 0 || in.Mode > 10 {
		return nil, badf("led 显示模式要在 0 ~ 10 之间")
	}
	name, err := trimName(in.Name)
	if err != nil {
		return nil, err
	}
	if name == "" {
		name = taskName
	}

	out := &task.LEDSub{Name: name, Text: text, Speed: in.Speed, LedMode: in.Mode}
	for i, d := range in.Devices {
		if d.DeviceID <= 0 {
			return nil, badf("第 %d 块 led 屏没给 deviceId", i+1)
		}
		termID, err := s.resolveOne(ctx, u, s.specTerminal(), d.Terminal)
		if err != nil {
			return nil, err
		}
		out.Devices = append(out.Devices, task.LEDDevRef{TerminalID: termID, DeviceID: d.DeviceID})
	}
	return out, nil
}

// resolveTaskTargets 解析终端与分区，并带回每台终端的区域掩码。
//
// 与 resolveTargets 的区别只有掩码这一件事：分区展开出来的终端没有掩码
// （用默认全开），只有 terminals 里显式写了对象形式的才有。
func (s *Service) resolveTaskTargets(ctx context.Context, u *auth.User,
	picks []TerminalPick, zones []Ref) ([]int64, map[int64]string, error) {

	areaOf := map[int64]string{}
	refs := make([]Ref, 0, len(picks))
	for _, p := range picks {
		if err := p.checkArea(); err != nil {
			return nil, nil, err
		}
		refs = append(refs, p.Ref)
	}

	// ⚠ 用 Aligned 版本：去重之后就没法把「第几个引用」对回「第几个掩码」了
	aligned, err := s.resolveRefsAligned(ctx, u, s.specTerminal(), refs)
	if err != nil {
		return nil, nil, err
	}
	seen := map[int64]bool{}
	ids := make([]int64, 0, len(aligned))
	for i, id := range aligned {
		if id <= 0 {
			continue
		}
		if a := picks[i].Area; a != "" {
			areaOf[id] = a
		}
		if !seen[id] {
			seen[id] = true
			ids = append(ids, id)
		}
	}

	zoneIDs, err := s.resolveRefs(ctx, u, s.specZone(), zones)
	if err != nil {
		return nil, nil, err
	}
	for _, zid := range zoneIDs {
		members, err := s.zoneTerminals(ctx, u, zid)
		if err != nil {
			return nil, nil, err
		}
		if len(members) == 0 {
			return nil, nil, badf("分区（编号 %d）里一个终端都没有", zid)
		}
		for _, id := range members {
			if !seen[id] {
				seen[id] = true
				ids = append(ids, id)
			}
		}
	}
	return ids, areaOf, nil
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

// defaultEndTime 在调用方没给 endTime 时推一个出来。
//
// # 改任务时的规矩：动了时长或挪了开播时刻就重算，都没动就保持原样
//
// 「把时长从 10 分钟改成 15 分钟」如果只改 timelength、不动 endtime，
// 就会留下一条自相矛盾的任务：时长说 15 分钟，结束时刻还写着 10 分钟那会儿。
//
// 挪开播时刻更糟。一条 09:50→10:05 的任务，只传 {"playTime": "10:05"}，
// 如果结束时刻不跟着走，就变成 10:05 开始、10:05 结束 —— 一条**零长度**的任务。
// 调用方明明只是想把它推迟一刻钟。
//
// 所以这两件事任何一件发生了，结束时刻都要跟着算；都没发生（只改了音量之类）
// 就保持原值，免得把结束时刻悄悄挪了。
//
// # 按遍数循环时算不出确定的秒数
//
// 那时退而求其次：如果只是挪了开播时刻，就把原来的结束时刻**平移同样的差**，
// 播放窗口的长度保持不变 —— 这比留一个早于开播时刻的结束时刻强得多。
// 实在没有可依据的旧值，才落到「开播时刻 + 1 小时」。
//
// # 越过午夜一律截到 23:59:59，不回绕
//
// endtime 是一个**时刻**（HH:MM:SS），没有「第二天」这个概念。
// 09:50 播 24 小时，回绕出来是 09:50 —— 读到的人只会以为这是条零长度任务；
// 23:50 播 20 分钟回绕成 00:10，看起来就是结束早于开始。
// 老系统在这里也是截断的（ok112 的 do.php：`if($getendhour>=24) $getendtime="23:59:59"`），
// 底层排期读的是同一列，所以照它来。
func defaultEndTime(playTime string, lenTy, length int, old *task.Detail,
	lengthChanged, playTimeChanged bool) string {

	if old != nil && old.EndTime != "" && !lengthChanged && !playTimeChanged {
		return old.EndTime
	}
	base, ok := secondsOfDay(playTime)
	if !ok {
		return playTime
	}
	// 按秒数播：结束时刻就是开播时刻 + 时长，算得准。
	if lenTy == 1 && length > 0 {
		return clockOfDay(base + length)
	}
	// 按遍数播：算不准。只挪了开播时刻的话，把旧的结束时刻平移同样的差，
	// 播放窗口的长度保持不变。
	if old != nil && old.EndTime != "" {
		oldStart, ok1 := secondsOfDay(old.PlayTime)
		oldEnd, ok2 := secondsOfDay(old.EndTime)
		if ok1 && ok2 && oldEnd > oldStart {
			return clockOfDay(base + (oldEnd - oldStart))
		}
		return old.EndTime
	}
	return clockOfDay(base + 3600)
}

// secondsOfDay 把 HH:MM:SS 变成当天的第几秒。
func secondsOfDay(v string) (int, bool) {
	t, err := time.Parse("15:04:05", v)
	if err != nil {
		return 0, false
	}
	return t.Hour()*3600 + t.Minute()*60 + t.Second(), true
}

// clockOfDay 把「当天的第几秒」写回 HH:MM:SS，越过午夜截到 23:59:59。
func clockOfDay(sec int) string {
	if sec >= 24*3600 {
		return "23:59:59"
	}
	return fmt.Sprintf("%02d:%02d:%02d", sec/3600, sec%3600/60, sec%60)
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

// TaskDetail 是一条任务的**全貌** —— 建任务时能填的每一项，这里都能读回来。
//
// # 为什么必须有这个接口
//
// 没有它的话，集成方建完任务只能拿到一个 id，没法确认「我填的那些真的写进去了吗」。
// 列表接口给的是摘要（为了列表性能，间隔播放、LED、区域掩码这些都不在里面），
// 想核对就只能去界面上人眼看 —— 而这恰恰是集成要避免的事。
//
// 字段与 TaskInput 一一对应，形状也一样：读回来改两个值再 PUT 回去就行。
type TaskDetail struct {
	ID     int64  `json:"id"`
	Name   string `json:"name"`
	Note   string `json:"note"`
	Folder int64  `json:"folderId"`

	Media     []NamedItem        `json:"media"`
	Terminals []TaskTerminalInfo `json:"terminals"`

	StartDate  string `json:"startDate"`
	EndDate    string `json:"endDate"`
	PlayTime   string `json:"playTime"`
	EndTime    string `json:"endTime"`
	Weekdays   []int  `json:"weekdays"`
	DisableDay string `json:"disableDay"`

	Seconds   int           `json:"seconds"`
	LoopTimes int           `json:"loopTimes"`
	Interval  *TaskInterval `json:"interval"`

	Volume   int  `json:"volume"`
	Priority int  `json:"priority"`
	PrePower int  `json:"prePower"`
	Enabled  bool `json:"enabled"`
	// ⚠ 详情里**没有** running（此刻在不在播）—— task.Detail 不带执行状态，
	//   硬填一个 false 就是在撒谎。要知道在不在播，查任务列表的 running。
	Sequential bool `json:"sequential"`
	Multicast  bool `json:"multicast"`
	LocalFirst bool `json:"localFirst"`

	LED *TaskLEDOut `json:"led"`
}

// TaskTerminalInfo 是任务里的一台终端，带上它的区域掩码。
type TaskTerminalInfo struct {
	ID   int64  `json:"id"`
	Name string `json:"name"`
	Area string `json:"area"`
	// Deleted 表示这台终端已经被删了，但任务里还挂着它。
	// 如实报出来 —— 悄悄跳过的话，「为什么少一台」永远查不出原因。
	Deleted bool `json:"deleted"`
}

// TaskLEDOut 是读回来的 LED 字幕。形状与写入时的 TaskLED 一致。
type TaskLEDOut struct {
	Text    string          `json:"text"`
	Name    string          `json:"name"`
	Speed   int             `json:"speed"`
	Mode    int             `json:"mode"`
	Devices []TaskLEDDevOut `json:"devices"`
}

// TaskLEDDevOut 是一块 LED 屏。
type TaskLEDDevOut struct {
	TerminalID   int64  `json:"terminalId"`
	TerminalName string `json:"terminalName"`
	DeviceID     int64  `json:"deviceId"`
}

// GetTask 取一条任务的全貌。
func (s *Service) GetTask(ctx context.Context, u *auth.User, ref Ref) (*TaskDetail, error) {
	if s.tasks == nil {
		return nil, fmt.Errorf("任务服务未接入")
	}
	id, err := s.resolveOne(ctx, u, s.specTask(), ref)
	if err != nil {
		return nil, err
	}
	d, err := s.tasks.Get(ctx, u, id)
	if err != nil {
		return nil, err
	}

	out := &TaskDetail{
		ID:         d.TaskID,
		Name:       d.TaskName,
		Folder:     d.FolderID,
		StartDate:  d.StartDate,
		EndDate:    d.EndDate,
		PlayTime:   d.PlayTime,
		EndTime:    d.EndTime,
		Weekdays:   isoWeekdays(d.ExeModel),
		Volume:     d.Volume,
		Priority:   d.Priority,
		PrePower:   d.PrePower,
		Enabled:    d.ProjectState == 0, // 0 才是启用
		Sequential: d.IsRandomPlay == 1, // 1 才是顺序
		Multicast:  d.DataSendMode == 1,
		LocalFirst: d.LocalPlay == 1,
		Media:      []NamedItem{},
		Terminals:  []TaskTerminalInfo{},
	}
	// disableday 空着时库里是 0000-00-00，对调用方没有意义
	if d.DisableDay != "" && d.DisableDay != "0000-00-00" {
		out.DisableDay = d.DisableDay
	}
	// 时长按 timelengthtype 分到两个字段上，别让调用方自己解那一位
	if d.TimeLengthTy == 2 {
		out.LoopTimes = d.TimeLength
	} else {
		out.Seconds = d.TimeLength
	}
	// interval_s > 0 才是间隔播放 —— 库里没有单独的「播放模式」列，
	// 界面也是靠这一条反推的
	if d.IntervalS > 0 {
		iv := &TaskInterval{EverySeconds: d.IntervalS}
		if d.IntPlayLenTy == 2 {
			iv.PlayTimes = d.IntPlayLen
		} else {
			iv.PlaySeconds = d.IntPlayLen
		}
		out.Interval = iv
	}
	for _, m := range d.Media {
		out.Media = append(out.Media, NamedItem{ID: m.MediaID, Name: m.Name})
	}
	for _, t := range d.Terminals {
		out.Terminals = append(out.Terminals, TaskTerminalInfo{
			ID: t.TerminalID, Name: t.TerminalName, Area: t.Area, Deleted: t.Deleted,
		})
	}
	if d.LED != nil {
		led := &TaskLEDOut{
			Text: d.LED.Text, Name: d.LED.Name,
			Speed: d.LED.Speed, Mode: d.LED.LedMode,
			Devices: []TaskLEDDevOut{},
		}
		names := s.namedTerminals(ctx, u, ledTerminalIDs(d.LED.Devices))
		nameOf := map[int64]string{}
		for _, n := range names {
			nameOf[n.ID] = n.Name
		}
		for _, dev := range d.LED.Devices {
			led.Devices = append(led.Devices, TaskLEDDevOut{
				TerminalID: dev.TerminalID, TerminalName: nameOf[dev.TerminalID], DeviceID: dev.DeviceID,
			})
		}
		out.LED = led
	}

	// note 是 task.info，详情结构里没带，单独取一次
	if notes, err := s.taskNotes(ctx, []int64{d.TaskID}); err == nil {
		out.Note = notes[d.TaskID]
	}
	return out, nil
}

func ledTerminalIDs(devs []task.LEDDevRef) []int64 {
	out := make([]int64, 0, len(devs))
	for _, d := range devs {
		out = append(out, d.TerminalID)
	}
	return out
}
