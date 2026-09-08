package assistant

import "htweb/internal/auth"

// 意图清单与权限映射。
//
// # 32 个意图从哪来
//
// 逐条取自原 Python 实现的 PHASE1_INTENTS（api_public.py）与
// PHASE1_INTENT_DISPATCH（assistant/dispatch.py），一个不多一个不少。
// 名字原样保留 —— 模型输出的就是这些字符串，改名等于让训练好的模型对不上号。
//
// ⚠ 有几组是同义别名（pause_task / task_pause），旧版两种都收，这里照收。
//
// # 权限：助手不是后门
//
// 助手只是另一个入口，**走的是与页面完全相同的那套权限位**。
// 一个只有「文件广播」权限的人，不能靠对助手说话去改作息方案。
//
// 每个意图给一个**最小权限位**。有几个意图作用于哪张表要看运行时的对象
// （比如 play_task 既可能是文件广播也可能是打铃条目，adjust_volume 既可能调
// 任务音量也可能调终端音量），这类标了 dynamic：先用最小位放行，
// 执行前拿到真实对象后**再按对象类型复核一次**（见 executor 的 checkObjectPriv）。
// 宁可多卡一道，也不能让助手成为绕过权限的路径。
type Intent string

const (
	// —— 作息方案 ——
	IntentMoveSchedule         Intent = "move_schedule"
	IntentSwapSchedule         Intent = "swap_schedule"
	IntentCancelSchedule       Intent = "cancel_schedule"
	IntentCreateSchedule       Intent = "create_schedule"
	IntentCreateScheme         Intent = "create_scheme"
	IntentEnableSchedule       Intent = "enable_schedule"
	IntentDisableSchedule      Intent = "disable_schedule"
	IntentDeleteSchedule       Intent = "delete_schedule"
	IntentShiftScheduleLater   Intent = "shift_schedule_later"
	IntentShiftScheduleEarlier Intent = "shift_schedule_earlier"
	IntentReplaceMediaInTask   Intent = "replace_media_in_task"
	// —— 任务 ——
	IntentPlayTask           Intent = "play_task"
	IntentStopTask           Intent = "stop_task"
	IntentTaskPause          Intent = "task_pause"
	IntentPauseTask          Intent = "pause_task"
	IntentTaskResume         Intent = "task_resume"
	IntentResumeTask         Intent = "resume_task"
	IntentBroadcastEmergency Intent = "broadcast_emergency"
	IntentQueryTask          Intent = "query_task"
	// —— 媒体 ——
	IntentPlayMedia    Intent = "play_media"
	IntentReplaceMedia Intent = "replace_media"
	// —— 终端 ——
	IntentQueryTerminal          Intent = "query_terminal"
	IntentEnableTerminal         Intent = "enable_terminal"
	IntentDisableTerminal        Intent = "disable_terminal"
	IntentSyncTerminalTime       Intent = "sync_terminal_time"
	IntentCheckTerminal          Intent = "check_terminal"
	IntentAddTerminalToTask      Intent = "add_terminal_to_task"
	IntentRemoveTerminalFromTask Intent = "remove_terminal_from_task"
	// —— 分区 ——
	IntentCreateZone             Intent = "create_zone"
	IntentDeleteZone             Intent = "delete_zone"
	IntentAddTerminalToZone      Intent = "add_terminal_to_zone"
	IntentRemoveTerminalFromZone Intent = "remove_terminal_from_zone"
	// —— 音量 ——
	IntentAdjustVolume Intent = "adjust_volume"

	// IntentNone 是模型没听懂（置信度低于阈值）时的输出。
	IntentNone Intent = "none"
)

// spec 是一个意图的静态属性。
type spec struct {
	// Priv 是执行它至少要有的权限位。空串表示只要登录（纯查询）。
	Priv string
	// Dynamic 为真时，最终权限还要在拿到真实对象后按对象类型复核一次。
	Dynamic bool
	// Write 为真表示这个意图会改数据 —— 决定要不要写审计、要不要给撤销凭据。
	Write bool
	// Title 是人能读懂的动作名，用于审计日志与「已执行」卡片。
	Title string
}

// specs 是全部 32 个意图。**新增意图必须同时在这里登记**，
// 否则 dispatch 找不到它，会当成「不支持的指令」拒掉 —— 这是有意的：
// 宁可拒绝，也不能让一个没定权限的意图跑起来。
var specs = map[Intent]spec{
	// 作息方案 → bellpriv
	IntentMoveSchedule:         {Priv: auth.PrivBell, Write: true, Title: "挪动作息任务"},
	IntentSwapSchedule:         {Priv: auth.PrivBell, Write: true, Title: "对调作息任务"},
	IntentCancelSchedule:       {Priv: auth.PrivBell, Write: true, Title: "取消作息任务"},
	IntentCreateSchedule:       {Priv: auth.PrivBell, Write: true, Title: "新建作息方案"},
	IntentCreateScheme:         {Priv: auth.PrivBell, Write: true, Title: "新建作息方案"},
	IntentEnableSchedule:       {Priv: auth.PrivBell, Write: true, Title: "启用作息方案"},
	IntentDisableSchedule:      {Priv: auth.PrivBell, Write: true, Title: "停用作息方案"},
	IntentDeleteSchedule:       {Priv: auth.PrivBell, Write: true, Title: "删除作息任务"},
	IntentShiftScheduleLater:   {Priv: auth.PrivBell, Write: true, Title: "作息任务顺延"},
	IntentShiftScheduleEarlier: {Priv: auth.PrivBell, Write: true, Title: "作息任务提前"},
	IntentReplaceMediaInTask:   {Priv: auth.PrivBell, Write: true, Dynamic: true, Title: "替换任务媒体"},

	// 任务 → 最小 taskpriv，执行前按任务的 tasktype 复核
	IntentPlayTask:           {Priv: auth.PrivTask, Write: true, Dynamic: true, Title: "执行任务"},
	IntentStopTask:           {Priv: auth.PrivTask, Write: true, Dynamic: true, Title: "停止任务"},
	IntentTaskPause:          {Priv: auth.PrivTask, Write: true, Dynamic: true, Title: "暂停任务"},
	IntentPauseTask:          {Priv: auth.PrivTask, Write: true, Dynamic: true, Title: "暂停任务"},
	IntentTaskResume:         {Priv: auth.PrivTask, Write: true, Dynamic: true, Title: "恢复任务"},
	IntentResumeTask:         {Priv: auth.PrivTask, Write: true, Dynamic: true, Title: "恢复任务"},
	IntentBroadcastEmergency: {Priv: auth.PrivTask, Write: true, Dynamic: true, Title: "紧急广播"},
	IntentQueryTask:          {Priv: "", Write: false, Title: "查询任务"},

	// 媒体
	IntentPlayMedia:    {Priv: auth.PrivTask, Write: true, Title: "立即播放"},
	IntentReplaceMedia: {Priv: auth.PrivTask, Write: true, Dynamic: true, Title: "替换媒体"},

	// 终端 → terminalpriv
	IntentQueryTerminal:          {Priv: "", Write: false, Title: "查询终端"},
	IntentCheckTerminal:          {Priv: "", Write: false, Title: "检查终端"},
	IntentEnableTerminal:         {Priv: auth.PrivTerminal, Write: true, Title: "启用终端"},
	IntentDisableTerminal:        {Priv: auth.PrivTerminal, Write: true, Title: "停用终端"},
	IntentSyncTerminalTime:       {Priv: auth.PrivTerminal, Write: true, Title: "终端校时"},
	IntentAddTerminalToTask:      {Priv: auth.PrivTask, Write: true, Dynamic: true, Title: "给任务加终端"},
	IntentRemoveTerminalFromTask: {Priv: auth.PrivTask, Write: true, Dynamic: true, Title: "从任务移除终端"},

	// 分区 → terminalgrouppriv
	IntentCreateZone:             {Priv: auth.PrivTerminalGroup, Write: true, Title: "新建终端分区"},
	IntentDeleteZone:             {Priv: auth.PrivTerminalGroup, Write: true, Title: "删除终端分区"},
	IntentAddTerminalToZone:      {Priv: auth.PrivTerminalGroup, Write: true, Title: "给分区加终端"},
	IntentRemoveTerminalFromZone: {Priv: auth.PrivTerminalGroup, Write: true, Title: "从分区移除终端"},

	// 音量：任务音量要 taskpriv，终端音量要 terminalpriv，看对象定
	IntentAdjustVolume: {Priv: auth.PrivTask, Write: true, Dynamic: true, Title: "调整音量"},
}

// Known 判断这个意图是不是我们支持的。
func Known(i Intent) bool {
	_, ok := specs[i]
	return ok
}

// Spec 取意图的静态属性。第二个返回值为 false 表示不支持这个意图。
func Spec(i Intent) (spec, bool) {
	s, ok := specs[i]
	return s, ok
}

// Allowed 判断这个用户能不能执行这个意图。
//
// ⚠ 这是**静态的最小检查**。Dynamic 的意图在拿到真实对象后还要再查一次，
// 别把这里的 true 当成最终放行。
func Allowed(u *auth.User, i Intent) (bool, string) {
	s, ok := specs[i]
	if !ok {
		return false, "不支持的指令"
	}
	if s.Priv == "" {
		return true, ""
	}
	if u == nil {
		return false, "未登录"
	}
	if !u.HasRight(s.Priv) {
		return false, "您没有「" + privTitle(s.Priv) + "」权限，这条指令我执行不了"
	}
	return true, ""
}

// privTitle 把权限位翻译成界面上那个名字，回话时用 ——
// 跟用户说 "您没有 bellpriv 权限" 等于没说。
func privTitle(priv string) string {
	switch priv {
	case auth.PrivTask:
		return "文件广播"
	case auth.PrivBell:
		return "作息方案"
	case auth.PrivTerminal:
		return "终端管理"
	case auth.PrivTerminalGroup:
		return "分区管理"
	case auth.PrivMedia:
		return "文件管理"
	case auth.PrivTts:
		return "文字语音"
	case auth.PrivLed:
		return "led播放"
	case auth.PrivPowerPlay:
		return "终端功放"
	case auth.PrivAdm:
		return "采播管理"
	case auth.PrivServer:
		return "遥控管理"
	case auth.PrivUser:
		return "用户管理"
	case auth.PrivAlarmGroup:
		return "报警管理"
	case auth.PrivFolder:
		return "文件夹管理"
	}
	return priv
}
