package openapi

import (
	"context"
	"fmt"

	"htweb/internal/auth"
	"htweb/internal/bell"
	"htweb/internal/notify"
	"htweb/internal/task"
)

// 作息方案的新建 / 启停 / 删除。
//
// # 方案不是一张表
//
// 「春季作息」不是 schedule 表里的一行 —— 它是一批 task 行共用的
// task.info（见 bell 包）。所以：
//
//	· 方案的「主键」是名字，没有编号
//	· 建一个方案 = 一次性建出它下面的全部打铃条目
//	· 停用一个方案 = 把这批 task 行的 projectstate 一起改掉
//
// 这也是为什么方案的路径是 /schedules/{name} 而不是 /schedules/{id}。
//
// # 一次调用建一整个方案
//
// 界面上「新建作息方案」就是一屏：填方案名、选终端、然后一行一行地
// 填「几点、播什么、播多久」。接口照这个来 —— 一次把条目清单收下。
//
// 分成「先建方案、再逐条加条目」的话，中间失败会留下一个只有一半条目的
// 方案，而它看起来完全正常，只是有几个铃不响。
//
// # 动作全部交给 bell.Service
//
// 建方案要同时写主任务、功放子任务、LED 子任务、终端清单，还要发通知报文，
// 这些规则在 bell 包里。这一层只把「人话」翻译成 bell.PlanInput。

// ScheduleInput 是新建作息方案的入参。
type ScheduleInput struct {
	Name string `json:"name"`
	// Terminals / Zones 是这个方案打到哪。方案里所有条目共用同一份终端清单
	// （界面上也是这样：终端选在方案级，不是每条铃各选一次）。
	Terminals []Ref `json:"terminals"`
	Zones     []Ref `json:"zones"`

	// StartDate / EndDate / Weekdays 是方案级的排期。留空默认今天起一年。
	StartDate string `json:"startDate"`
	EndDate   string `json:"endDate"`
	// Weekdays 1=周一 … 7=周日。**留空 = 周一到周五** ——
	//
	// ⚠ 这一条与 /tasks 的默认值**不一样**（那边留空是「手动」），
	//   是有意的：作息方案天然是「上课日打铃」，建一个永不触发的方案
	//   没有任何意义，调用方几乎肯定是漏填了。而文件广播任务
	//   建来手动触发是常见用法，那边默认成每天才危险。
	Weekdays []int `json:"weekdays"`

	Volume   int `json:"volume"`
	Priority int `json:"priority"`
	PrePower int `json:"prePower"`

	// Items 是这个方案的打铃条目。至少一条。
	Items []ScheduleItemInput `json:"items"`
}

// ScheduleItemInput 是一条打铃：几点、播什么、播多久。
type ScheduleItemInput struct {
	Name     string `json:"name"`
	PlayTime string `json:"playTime"`
	// Media 是这一条播的媒体，按数组顺序播。
	Media []Ref `json:"media"`
	// Seconds 与 LoopTimes 二选一。都不给按媒体自身时长算。
	Seconds   int `json:"seconds"`
	LoopTimes int `json:"loopTimes"`
}

// ScheduleSaved 是建方案的回执。
type ScheduleSaved struct {
	Name    string  `json:"name"`
	Items   int     `json:"itemCount"`
	TaskIDs []int64 `json:"taskIds"`
	// Warnings 是「建成了，但有点事你该知道」——
	// 比如同一时刻排了两条铃。不拦截，但必须说出来。
	Warnings []string `json:"warnings"`
}

// CreateSchedule 新建一个作息方案，连同它下面的全部条目。
func (s *Service) CreateSchedule(ctx context.Context, u *auth.User, in ScheduleInput) (*ScheduleSaved, error) {
	if s.bells == nil || s.notifier == nil {
		return nil, fmt.Errorf("作息方案服务未接入")
	}
	name, err := trimName(in.Name)
	if err != nil {
		return nil, err
	}
	if name == "" {
		return nil, badf("请给作息方案起个名字，比如「春季作息」")
	}
	if len(in.Items) == 0 {
		return nil, badf("一个作息方案至少要有一条打铃，比如 09:50 播下课铃")
	}

	termIDs, err := s.resolveTargets(ctx, u, in.Terminals, in.Zones)
	if err != nil {
		return nil, err
	}
	if len(termIDs) == 0 {
		return nil, badf("请指定这个方案打到哪些终端或分区")
	}

	startDate, endDate, err := dateRange(TaskInput{StartDate: in.StartDate, EndDate: in.EndDate}, nil)
	if err != nil {
		return nil, err
	}

	// 留空默认周一到周五，理由见 ScheduleInput.Weekdays 上的说明。
	days := in.Weekdays
	if len(days) == 0 {
		days = []int{1, 2, 3, 4, 5}
	}
	exeModel, err := exeModelOf(days, nil)
	if err != nil {
		return nil, err
	}

	volume := in.Volume
	if volume == 0 {
		volume = 80
	}
	if volume < 0 || volume > 100 {
		return nil, badf("音量要在 0 ~ 100 之间")
	}
	priority := in.Priority
	if priority == 0 {
		// 与 /tasks 同一个理由：没给就取最低那一档，别去压别人的广播。
		if priority, err = s.lowestPriority(ctx, u); err != nil {
			return nil, err
		}
	}

	items := make([]bell.ItemInput, 0, len(in.Items))
	for i, it := range in.Items {
		item, err := s.toScheduleItem(ctx, u, it, i)
		if err != nil {
			return nil, err
		}
		items = append(items, *item)
	}

	terms := make([]task.TerminalRef, 0, len(termIDs))
	for _, id := range termIDs {
		terms = append(terms, task.TerminalRef{TerminalID: id, Area: task.AreaAll})
	}

	res, err := s.bells.Create(ctx, u, bell.PlanInput{
		PlanName: name,
		Schedule: bell.Schedule{StartDate: startDate, EndDate: endDate, ExeModel: exeModel},
		Playback: bell.Playback{
			Volume:   volume,
			Priority: priority,
			PrePower: in.PrePower,
			// 0 = 随机、1 = 顺序（BR-163，取值反直觉）。打铃固定顺序播 ——
			// 一条铃通常只有一个媒体，随机在这里没有意义。
			IsRandomPlay: 1,
		},
		Terminals: terms,
		Items:     items,
	})
	if err != nil {
		return nil, err
	}
	// 每个新条目一条 state=4 通知，与界面走的是同一条路。
	// 漏掉这一步的表现是：方案在界面上建好了，到点却不打铃。
	for _, id := range res.NotifyTaskIDs {
		s.notifier.TaskSaved(ctx, notify.TaskAdded, id, res.Volume)
	}
	warnings := res.Warnings
	if warnings == nil {
		warnings = []string{}
	}
	return &ScheduleSaved{
		Name:     res.PlanName,
		Items:    res.CreatedItems,
		TaskIDs:  res.TaskIDs,
		Warnings: warnings,
	}, nil
}

// toScheduleItem 把一条「人话」的打铃翻成 bell.ItemInput。
func (s *Service) toScheduleItem(ctx context.Context, u *auth.User,
	in ScheduleItemInput, idx int) (*bell.ItemInput, error) {

	playTime, err := normalizeTime(in.PlayTime, fmt.Sprintf("第 %d 条的播放时间", idx+1))
	if err != nil {
		return nil, err
	}
	if playTime == "" {
		return nil, badf("第 %d 条没给播放时间", idx+1)
	}

	mediaIDs, err := s.resolveRefs(ctx, u, s.specMedia(), in.Media)
	if err != nil {
		return nil, err
	}
	if len(mediaIDs) == 0 {
		return nil, badf("第 %d 条（%s）没指定要播什么", idx+1, playTime)
	}

	name, err := trimName(in.Name)
	if err != nil {
		return nil, err
	}
	if name == "" {
		// 没起名就用时刻当名字。界面上这一列是要显示的，
		// 留空的话方案里会是一排无名条目，谁也认不出哪条是哪条。
		name = playTime[:5] + " 打铃"
	}

	lenTy, length, err := lengthOf(ctx, s, TaskInput{Seconds: in.Seconds, LoopTimes: in.LoopTimes}, nil, mediaIDs)
	if err != nil {
		return nil, err
	}

	item := &bell.ItemInput{
		TaskName:     name,
		PlayTime:     playTime,
		TimeLengthTy: lenTy,
		TimeLength:   length,
	}
	for i, id := range mediaIDs {
		item.Media = append(item.Media, task.MediaRef{MediaID: id, Sort: i})
	}
	return item, nil
}

// ScheduleActionResult 是启停 / 删除方案的回执。
type ScheduleActionResult struct {
	Name string `json:"name"`
	// AffectedTasks 是这一下动了几条打铃任务。
	AffectedTasks int `json:"affectedTasks"`
}

// SetScheduleState 启用 / 停用一个方案。
func (s *Service) SetScheduleState(ctx context.Context, u *auth.User,
	name string, enable bool) (*ScheduleActionResult, error) {

	if s.bells == nil || s.notifier == nil {
		return nil, fmt.Errorf("作息方案服务未接入")
	}
	plan, err := s.resolveSchedule(ctx, u, name)
	if err != nil {
		return nil, err
	}
	res, err := s.bells.SetState(ctx, u, plan, enable)
	if err != nil {
		return nil, err
	}
	state := notify.PlanDisabled
	if enable {
		state = notify.PlanEnabled
	}
	s.notifier.PlanChanged(ctx, state, res.PlanName)
	return &ScheduleActionResult{Name: res.PlanName, AffectedTasks: res.AffectedTasks}, nil
}

// DeleteSchedule 删掉一个方案，连同它下面的全部条目。
//
// ⚠ 没有「确认」参数。界面上那个二次确认是给**人**的（点错一下就没了），
// 接口这边加一个 confirmed:true 只是让调用方多写一行、挡不住任何误删 ——
// 真正的防线是权限位和「名字必须精确对上、不猜」。
func (s *Service) DeleteSchedule(ctx context.Context, u *auth.User, name string) (*ScheduleActionResult, error) {
	if s.bells == nil || s.notifier == nil {
		return nil, fmt.Errorf("作息方案服务未接入")
	}
	plan, err := s.resolveSchedule(ctx, u, name)
	if err != nil {
		return nil, err
	}
	res, err := s.bells.Delete(ctx, u, plan)
	if err != nil {
		return nil, err
	}
	// 按**实际删掉的**每个 taskid 发通知
	s.notifier.BellTasksDeleted(ctx, res.DeletedTasks)
	return &ScheduleActionResult{Name: plan, AffectedTasks: len(res.DeletedTasks)}, nil
}
