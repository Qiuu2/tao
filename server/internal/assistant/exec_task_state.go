package assistant

import (
	"context"
	"fmt"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/task"
)

// 单任务写操作：播放 / 停止 / 暂停 / 恢复 / 调音量。
//
// # 为什么不自己写 UPDATE
//
// tao 的 task.Service.Control 已经把这套动作做全了：归属校验、方案停用不许启动、
// 没媒体/没终端不许启动、功放子任务同步、以及发给后台 C 服务的通知报文
// （启动是 task?state=3&id=X 不带 type，停止是 state=2 带 type=2，暂停恢复
// 根本不落库 —— 这套协议是实测出来的，写错就是"点了没反应"）。
//
// 助手绕过它自己写 UPDATE 就等于把这些守卫全丢掉，而且是**悄悄丢掉**：
// 用对话启动一条没有终端的任务不会报错，只会让后台空转。所以这里只做
// "把话变成任务 id"，动作本身交给页面用的那个 Service。
//
// 这同时也是「助手要走 tao 现有的权限体系」落到实处的地方：
// 意图层已经查过功能权限位，Control 里再查一次数据归属，两层都不放过。
//
// # 与原实现的对应
//
// 原实现是 _apply_runtime_task_state_change，四个意图共用它，
// 靠 state_value / action_name / action_text 三个参数区分。这里保持同样的形状。
// 回话措辞逐字照搬，包括 action_label_map 那张表。

// taskStateVariants / taskStateScopedVariants 逐字取自原实现。
var (
	taskStateVariants = []string{
		"已为您将任务“{task_name}”设为{action_label}。",
		"任务“{task_name}”现在是{action_label}状态。",
		"“{task_name}”已经调整为{action_label}。",
	}
	taskStateScopedVariants = []string{
		"已将方案“{schedule_name}”里的任务“{task_name}”设为{action_label}。",
		"方案“{schedule_name}”中的“{task_name}”现在是{action_label}状态。",
		"任务“{task_name}”已在方案“{schedule_name}”中调整为{action_label}。",
	}
	adjustVolumeVariants = []string{
		"已把{scope_desc}的音量调整为 {volume}。",
		"{scope_desc}的音量已经设为 {volume} 了。",
		"音量已调整：{scope_desc} 现在是 {volume}。",
	}
)

// actionLabels 逐字取自原实现的 action_label_map。
var actionLabels = map[string]string{
	"play_task":   "开始播放",
	"stop_task":   "停止",
	"task_pause":  "暂停",
	"task_resume": "恢复播放",
}

// actionTexts 是追问与失败句里用的短词，取自四个 _apply_*_intent 的 action_text。
var actionTexts = map[string]string{
	"play_task":   "执行",
	"stop_task":   "停止",
	"task_pause":  "暂停",
	"task_resume": "恢复",
}

// blockedReasonText 把 task.Control 挡下来的理由翻成能对用户说的话。
//
// Control 的 Detail 本来就是给人看的（"方案已停用，请先启用后再启动"），
// 直接用它，不另造一套说法 —— 页面上和对话里说的是同一句，出了问题好对。
func blockedReasonText(b task.Blocked) string {
	if strings.TrimSpace(b.Detail) != "" {
		return b.Detail
	}
	return b.Reason
}

// execTaskState 是 play_task / stop_task / task_pause / task_resume 的共用实现。
func (s *Service) execTaskState(intent string, action task.Action) executor {
	return func(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
		if s.tasks == nil || s.notifier == nil {
			return actionResult{Err: fmt.Errorf("任务服务未接入")}
		}
		actionText := actionTexts[intent]
		raw := rawTextOf(slots)
		taskName := slotText(slots, "task_name", "TASK", "CONTENT", "task")
		taskIDText := slotText(slots, "task_id", "TASK_ID")
		scheduleName := slotText(slots, "schedule_name", "schedule_id", "SCHEDULE", "SCHEDULE_ID")

		if taskName == "" && !isNumericID(taskIDText) {
			// 没指名任务、又刚让我放过东西 → 说的多半是"把刚才那个停掉"。
			// execPlayMedia 的回话里明写着"不想放了随时叫我停"，
			// 这里不接住就是说话不算数。
			if action == task.ActionStop {
				if playTaskID, playName, ok := s.activeRuntimePlay(ctx, u); ok {
					if err := s.stopRuntimePlay(ctx, u, playTaskID); err != nil {
						return actionResult{Err: err}
					}
					return actionResult{
						Reply: successRuntimeReply("stop_task", taskStateVariants,
							[]string{playName},
							map[string]string{"task_name": playName, "action_label": "停止"}),
						ActionLog: []map[string]any{{
							"intent": "stop_task", "mode": "runtime",
							"details": map[string]any{
								"runtime_scope": "temp_task",
								"task_id":       itoa64(playTaskID), "count": 1,
							},
						}},
					}
				}
			}
			return actionResult{
				Reply:        askRuntimeReply(intent, "要"+actionText+"的任务名称", "比如说出任务的名字哈~"),
				MissingSlots: []string{"task_name"},
			}
		}

		target, res := s.resolveOneTask(ctx, u, raw, scheduleName, taskName, taskIDText)
		if res != nil {
			return *res
		}

		out, err := s.tasks.Control(ctx, u, s.notifier, action, []int64{target.ID})
		if err != nil {
			return actionResult{Err: err}
		}
		if len(out.Succeeded) == 0 {
			reason := "没有找到可执行目标"
			if len(out.Blocked) > 0 {
				reason = blockedReasonText(out.Blocked[0])
			}
			return actionResult{
				Reply: failureRuntimeReply(intent,
					fmt.Sprintf("任务“%s”的%s", target.Name, actionText), nil,
					reason, "您可以换个说法再试，或者补充更明确的任务名。"),
				ActionLog: []map[string]any{{
					"intent": intent, "mode": "runtime",
					"details": map[string]any{
						"task_name": target.Name, "task_id": itoa64(target.ID),
						"schedule_name": target.Info, "count": 1,
						"blocked": blockedDetails(out.Blocked), "reason": reason,
					},
				}},
			}
		}

		label := actionLabels[intent]
		var reply string
		if target.Info != "" && scheduleName != "" {
			reply = successRuntimeReply(intent, taskStateScopedVariants,
				[]string{target.Info, target.Name},
				map[string]string{"schedule_name": target.Info,
					"task_name": target.Name, "action_label": label})
		} else {
			reply = successRuntimeReply(intent, taskStateVariants,
				[]string{target.Name},
				map[string]string{"task_name": target.Name, "action_label": label})
		}

		return actionResult{
			Reply: reply,
			ActionLog: []map[string]any{{
				"intent": intent, "mode": "runtime",
				"schedule_name": target.Info,
				"details": map[string]any{
					"task_name": target.Name, "task_id": itoa64(target.ID),
					"schedule_name": target.Info, "count": 1,
					"notified": out.Notified,
				},
			}},
		}
	}
}

// execAdjustVolume 调任务音量。原实现的 _apply_adjust_volume_intent 还能调
// 全局与终端音量，那两条在这套库里是别的表（serverbaseparam / terminal），
// 留到终端那一阶段一起做 —— 现在只认"某条任务的音量"，认不出来就如实说。
func (s *Service) execAdjustVolume(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
	if s.tasks == nil {
		return actionResult{Err: fmt.Errorf("任务服务未接入")}
	}
	raw := rawTextOf(slots)
	volumeText := slotText(slots, "value", "volume", "VALUE", "VOLUME")
	volume, ok := parseVolume(volumeText)
	if !ok {
		return actionResult{
			Reply:        askRuntimeReply("adjust_volume", "要调整到的音量值", "比如说“把音量调到 80”哈~"),
			MissingSlots: []string{"value"},
		}
	}
	if volume < 0 || volume > 100 {
		return actionResult{
			Reply: failureRuntimeReply("adjust_volume", "音量调整", nil,
				"音量只能是 0 ~ 100", ""),
		}
	}

	taskName := slotText(slots, "task_name", "TASK", "CONTENT", "task")
	taskIDText := slotText(slots, "task_id", "TASK_ID")
	scheduleName := slotText(slots, "schedule_name", "schedule_id", "SCHEDULE", "SCHEDULE_ID")

	// 没说任务、却说了终端或分区 → 调的是终端音量。
	// 这两条在原实现里是 adjust_volume_terminal，措辞种子也用那个意图名。
	if taskName == "" && !isNumericID(taskIDText) && mentionsTerminal(slots) {
		return s.adjustTerminalVolume(ctx, u, slots, volume)
	}

	if taskName == "" && !isNumericID(taskIDText) {
		return actionResult{
			// 全局音量（serverbaseparam）还没接，说清楚要调哪个，不要含糊地"已调整"
			Reply:        askRuntimeReply("adjust_volume", "要调整音量的任务或终端", "比如说“把早读预备铃的音量调到 80”哈~"),
			MissingSlots: []string{"task_name"},
		}
	}

	target, res := s.resolveOneTask(ctx, u, raw, scheduleName, taskName, taskIDText)
	if res != nil {
		return *res
	}

	out, err := s.tasks.SetVolume(ctx, u, []int64{target.ID}, volume)
	if err != nil {
		return actionResult{
			Reply: failureRuntimeReply("adjust_volume",
				fmt.Sprintf("任务“%s”的音量调整", target.Name), nil, err.Error(), ""),
		}
	}
	if len(out.Succeeded) == 0 {
		reason := "没有找到可执行目标"
		if len(out.Blocked) > 0 {
			reason = blockedReasonText(out.Blocked[0])
		}
		return actionResult{
			Reply: failureRuntimeReply("adjust_volume",
				fmt.Sprintf("任务“%s”的音量调整", target.Name), nil, reason, ""),
			ActionLog: []map[string]any{{
				"intent": "adjust_volume", "mode": "runtime",
				"details": map[string]any{"task_name": target.Name,
					"task_id": itoa64(target.ID), "volume": volume,
					"blocked": blockedDetails(out.Blocked), "reason": reason},
			}},
		}
	}

	scopeDesc := fmt.Sprintf("任务“%s”", target.Name)
	reply := successRuntimeReply("adjust_volume", adjustVolumeVariants,
		[]string{scopeDesc, seedNum(volume)},
		map[string]string{"scope_desc": scopeDesc, "volume": itoa(volume)})

	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "adjust_volume", "mode": "runtime",
			"schedule_name": target.Info,
			"details": map[string]any{
				"task_name": target.Name, "task_id": itoa64(target.ID),
				"schedule_name": target.Info, "volume": volume, "count": 1,
			},
		}},
	}
}

// resolveOneTask 把"用户说的那条任务"落到唯一一条真实任务上。
//
// 写操作**必须唯一**。查询时给出多条只是多看几行，写操作作用到多条上就是
// 改错了对象。所以这里宁可回一句"有好几条，说具体点"，也不挑一条动手。
// 返回值里第二项非 nil 表示该直接把它当结果返回。
func (s *Service) resolveOneTask(ctx context.Context, u *auth.User, raw,
	scheduleName, taskName, taskIDText string) (*TaskRow, *actionResult) {

	rows, err := s.queryTaskRows(ctx, u, "")
	if err != nil {
		return nil, &actionResult{Err: err}
	}

	// ① 明确给了数字 id 就按 id 走，不再猜名字
	if isNumericID(taskIDText) {
		for i := range rows {
			if itoa64(rows[i].ID) == taskIDText {
				return &rows[i], nil
			}
		}
		return nil, &actionResult{Reply: fmt.Sprintf("未找到任务编号 %s 对应的任务。", taskIDText)}
	}

	// ② 限定了作息方案就先缩到那个方案里
	if scheduleName != "" {
		plans, err := s.scheduleCandidates(ctx, u)
		if err != nil {
			return nil, &actionResult{Err: err}
		}
		res := s.ResolveName(ctx, scheduleName, raw, plans)
		if res.Matched == "" {
			return nil, &actionResult{Reply: fmt.Sprintf("没有找到作息方案“%s”。", scheduleName)}
		}
		kept := rows[:0:0]
		for _, r := range rows {
			if r.Info == res.Matched {
				kept = append(kept, r)
			}
		}
		rows = kept
	}

	// ③ 名字走与终端同一套四层解析：精确 → 原话里出现 → 模糊 → 数字安全
	candidates := make([]Candidate, 0, len(rows))
	byID := map[int64]*TaskRow{}
	for i := range rows {
		candidates = append(candidates, Candidate{ID: rows[i].ID, Name: rows[i].Name})
		byID[rows[i].ID] = &rows[i]
	}
	res := s.ResolveName(ctx, taskName, raw, candidates)
	if res.Matched == "" {
		if scheduleName != "" {
			return nil, &actionResult{
				Reply: fmt.Sprintf("在方案“%s”中未找到任务“%s”。", scheduleName, taskName)}
		}
		return nil, &actionResult{Reply: fmt.Sprintf("未找到文件广播任务“%s”。", taskName)}
	}

	// ④ 同名多条：写操作不猜
	same := 0
	for _, r := range rows {
		if r.Name == res.Matched {
			same++
		}
	}
	if same > 1 {
		return nil, &actionResult{
			Reply: fmt.Sprintf("叫“%s”的任务有 %d 条，我不敢替您挑。您把任务编号告诉我，或者说明是哪个分组的。",
				res.Matched, same),
		}
	}
	target, ok := byID[res.ID]
	if !ok {
		return nil, &actionResult{Reply: fmt.Sprintf("未找到文件广播任务“%s”。", taskName)}
	}
	return target, nil
}

func blockedDetails(blocked []task.Blocked) []map[string]any {
	out := make([]map[string]any, 0, len(blocked))
	for _, b := range blocked {
		out = append(out, map[string]any{
			"task_id": b.ID, "task_name": b.Name,
			"reason": b.Reason, "detail": b.Detail,
		})
	}
	return out
}

// isNumericID 对应 _strict_numeric_task_id_text：只认纯数字，别的一律当没给。
func isNumericID(s string) bool {
	s = strings.TrimSpace(s)
	if s == "" {
		return false
	}
	for _, r := range s {
		if r < '0' || r > '9' {
			return false
		}
	}
	return true
}

// parseVolume 从槽位里取音量。模型可能给「80」也可能给「80%」。
func parseVolume(s string) (int, bool) {
	s = strings.TrimSpace(strings.TrimSuffix(strings.TrimSpace(s), "%"))
	if s == "" {
		return 0, false
	}
	n, err := atoiStrict(s)
	if err != nil {
		return 0, false
	}
	return n, true
}

// mentionsTerminal 判断这一句说的是不是终端/分区。
func mentionsTerminal(slots map[string][]string) bool {
	for _, k := range []string{"terminal_id", "terminal_name", "terminal", "zone_name", "ZONE"} {
		for _, v := range slots[k] {
			if strings.TrimSpace(v) != "" {
				return true
			}
		}
	}
	return false
}

// adjustTerminalVolume 调终端音量。走 terminal.SetVolume ——
// 它要求终端在线，并且校验 0~100（修的是旧版 D-97 的无校验）。
func (s *Service) adjustTerminalVolume(ctx context.Context, u *auth.User,
	slots map[string][]string, volume int) actionResult {

	if s.terminals == nil || s.notifier == nil {
		return actionResult{Err: fmt.Errorf("终端服务未接入")}
	}
	ids, desc, res := s.resolveTerminalTargets(ctx, u, slots)
	if res != nil {
		return *res
	}
	out, err := s.terminals.SetVolume(ctx, u, ids, volume)
	if err != nil {
		return actionResult{
			Reply: failureRuntimeReply("adjust_volume_terminal",
				desc+"的音量调整", nil, err.Error(), ""),
		}
	}
	if len(out.Succeeded) == 0 {
		reason := "没有可执行的终端"
		if len(out.Skipped) > 0 {
			reason = skippedReasonText(out.Skipped[0])
		}
		return actionResult{
			Reply: failureRuntimeReply("adjust_volume_terminal",
				desc+"的音量调整", nil, reason, "稍后可以再试一次。"),
			ActionLog: []map[string]any{{
				"intent": "adjust_volume_terminal", "mode": "runtime",
				"details": map[string]any{"terminal_ids": ids, "volume": volume,
					"count": 0, "skipped": skippedDetails(out.Skipped), "reason": reason},
			}},
		}
	}
	s.notifier.TerminalVolume(ctx, out.Succeeded, volume)

	scopeDesc := desc
	if len(out.Succeeded) == 1 {
		scopeDesc = "终端“" + desc + "”"
	}
	reply := successRuntimeReply("adjust_volume_terminal", adjustVolumeVariants,
		[]string{scopeDesc, seedNum(volume)},
		map[string]string{"scope_desc": scopeDesc, "volume": itoa(volume)})
	reply = appendReplyDetails(reply, skippedLine(out.Skipped))

	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "adjust_volume_terminal", "mode": "runtime",
			"details": map[string]any{
				"terminal_ids": out.Succeeded, "count": len(out.Succeeded),
				"volume": volume, "skipped": skippedDetails(out.Skipped), "notified": true,
			},
		}},
	}
}
