package assistant

import (
	"context"
	"fmt"
	"strings"

	"htweb/internal/auth"
)

// 作息方案意图：启用 / 停用 / 删除。
//
// # 「作息方案」在这套库里对到什么
//
// 见 exec_task.go 文件头：方案是 task 里共享同一个 info 的一组行。
// tao 的 bell 包已经把这套建模和它的全部动作做好了，本包直接用它：
//
//	启用/停用方案 = bell.SetState  （连功放/LED 子任务一起改，还会发 project 报文）
//	删除方案      = bell.Delete    （连带清 terminalkeymaptask / offlinetaskofterminal
//	                                等旧版漏清的关联表，D-184）
//
// 自己写 UPDATE task SET projectstate ... WHERE info=? 看着一样，实际会漏掉
// 子任务范围、漏掉通知报文、漏掉那些关联表 —— 这些正是旧版的缺陷清单。
//
// # 启用/停用还能只针对一条任务
//
// 原实现里「启用方案X里的任务Y」是合法的说法，走的是改单条任务的状态。
// 这里保持一样。⚠ 只说任务不说方案时原实现是**追问方案名**，不是直接去
// 全库找那条任务 —— 因为不同方案里可能有同名任务，改错方案的后果不可见。
//
// # 删除为什么要问一句
//
// 原实现删方案是不问的。这里加了确认，因为删方案会把底下所有任务连同
// 媒体清单、终端清单一起删掉，而且**没有撤销**。页面上删分组要勾选、
// 点按钮、再确认一次；对话里一句话说错就没了，至少也要问一句。
// 这是本迁移中少数几处有意比原实现更保守的地方之一。

// execScheduleState 是 enable_schedule / disable_schedule 的共用实现。
func (s *Service) execScheduleState(intent string, enable bool) executor {
	return func(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
		if s.tasks == nil || s.bells == nil {
			return actionResult{Err: fmt.Errorf("作息方案服务未接入")}
		}
		raw := rawTextOf(slots)
		scheduleName := slotText(slots, "schedule_name", "schedule_id", "SCHEDULE", "SCHEDULE_ID")
		taskName := slotText(slots, "task_name", "TASK", "CONTENT", "task")
		taskIDText := slotText(slots, "task_id", "TASK_ID")
		stateText := "停用"
		if enable {
			stateText = "启用"
		}

		if scheduleName == "" && taskName == "" {
			return actionResult{
				Reply:        "请提供方案名称或任务名称。",
				MissingSlots: []string{"schedule_name/task_name"},
			}
		}
		if taskName != "" && scheduleName == "" {
			// ⚠ 不去全库找那条任务 —— 不同方案里可能有同名任务，改错方案看不出来
			return actionResult{
				Reply:        askRuntimeReply(intent, "要操作哪个作息方案", "比如冬季作息"),
				MissingSlots: []string{"schedule_name"},
			}
		}

		plans, err := s.scheduleCandidates(ctx, u)
		if err != nil {
			return actionResult{Err: err}
		}
		res := s.ResolveName(ctx, scheduleName, raw, plans)
		if res.Matched == "" {
			return actionResult{Reply: fmt.Sprintf("没有找到作息方案“%s”。", scheduleName)}
		}

		rows, err := s.queryTaskRows(ctx, u, res.Matched)
		if err != nil {
			return actionResult{Err: err}
		}

		// 指名了任务就只改那一条
		if taskName != "" || isNumericID(taskIDText) {
			target, bad := s.pickTaskIn(ctx, rows, raw, taskName, taskIDText, res.Matched)
			if bad != nil {
				return *bad
			}
			out, err := s.tasks.SetProjectState(ctx, u, []int64{target.ID}, enable)
			if err != nil {
				return actionResult{Err: err}
			}
			if len(out.Succeeded) == 0 {
				reason := "没有可执行的任务"
				if len(out.Blocked) > 0 {
					reason = blockedReasonText(out.Blocked[0])
				}
				return actionResult{
					Reply: failureRuntimeReply(intent,
						fmt.Sprintf("任务“%s”的%s", target.Name, stateText), nil, reason, ""),
				}
			}
			return actionResult{
				Reply: fmt.Sprintf("方案“%s”里的任务“%s”已%s。", res.Matched, target.Name, stateText),
				ActionLog: []map[string]any{{
					"intent": intent, "mode": "runtime", "schedule_name": res.Matched,
					"details": map[string]any{"enabled": enable, "scope": "task",
						"task_name": target.Name, "task_id": itoa64(target.ID), "count": 1},
				}},
			}
		}

		// 整个方案 —— 交给 bell.SetState
		if len(rows) == 0 {
			return actionResult{
				Reply: fmt.Sprintf("方案“%s”里没有任务，没有可%s的内容。", res.Matched, stateText),
			}
		}
		out, err := s.bells.SetState(ctx, u, res.Matched, enable)
		if err != nil {
			return actionResult{
				Reply: failureRuntimeReply(intent,
					fmt.Sprintf("方案“%s”的%s", res.Matched, stateText), nil, err.Error(), ""),
			}
		}

		// 措辞逐字取自原实现：'方案“{name}”已{state_text}。'
		reply := fmt.Sprintf("方案“%s”已%s。", res.Matched, stateText)
		if out.OfflineStateReset {
			// 方案里有条目正在离线传输，改完状态就看不出来了 —— 说一声
			reply = appendReplyDetails(reply, "方案里原本有条目在离线传输中，已一并清掉离线状态。")
		}
		return actionResult{
			Reply: reply,
			ActionLog: []map[string]any{{
				"intent": intent, "mode": "runtime", "schedule_name": res.Matched,
				"details": map[string]any{"enabled": enable, "scope": "schedule",
					"count": out.AffectedTasks, "task_ids": out.TaskIDs},
			}},
		}
	}
}

// pickTaskIn 在一批任务里挑出用户说的那一条。写操作不猜，同名多条就问。
func (s *Service) pickTaskIn(ctx context.Context, rows []TaskRow, raw, taskName,
	taskIDText, scheduleName string) (*TaskRow, *actionResult) {

	if isNumericID(taskIDText) {
		for i := range rows {
			if itoa64(rows[i].ID) == taskIDText {
				return &rows[i], nil
			}
		}
		return nil, &actionResult{Reply: fmt.Sprintf("未找到任务编号 %s 对应的任务。", taskIDText)}
	}
	cands := make([]Candidate, 0, len(rows))
	byID := map[int64]*TaskRow{}
	for i := range rows {
		cands = append(cands, Candidate{ID: rows[i].ID, Name: rows[i].Name})
		byID[rows[i].ID] = &rows[i]
	}
	res := s.ResolveName(ctx, taskName, raw, cands)
	if res.Matched == "" {
		return nil, &actionResult{
			Reply: fmt.Sprintf("在方案“%s”中未找到任务“%s”。", scheduleName, taskName)}
	}
	same := 0
	for _, r := range rows {
		if r.Name == res.Matched {
			same++
		}
	}
	if same > 1 {
		return nil, &actionResult{
			Reply: fmt.Sprintf("方案“%s”里叫“%s”的任务有 %d 条，我不敢替您挑。您把任务编号告诉我。",
				scheduleName, res.Matched, same),
		}
	}
	target, ok := byID[res.ID]
	if !ok {
		return nil, &actionResult{
			Reply: fmt.Sprintf("在方案“%s”中未找到任务“%s”。", scheduleName, taskName)}
	}
	return target, nil
}

// execDeleteSchedule 删方案：分组连同它下面的全部任务。
//
// ⚠ 这是本包里唯一不可撤销的动作，所以要先问一句再动手。见文件头的说明。
func (s *Service) execDeleteSchedule(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
	if s.bells == nil {
		return actionResult{Err: fmt.Errorf("作息方案服务未接入")}
	}
	raw := rawTextOf(slots)
	scheduleName := slotText(slots, "schedule_name", "schedule_id", "SCHEDULE", "SCHEDULE_ID")
	if scheduleName == "" {
		return actionResult{
			Reply:        askRuntimeReply("delete_schedule", "要删除的方案名称", "比如删除冬季作息"),
			MissingSlots: []string{"schedule_name"},
		}
	}

	plans, err := s.scheduleCandidates(ctx, u)
	if err != nil {
		return actionResult{Err: err}
	}
	res := s.ResolveName(ctx, scheduleName, raw, plans)
	if res.Matched == "" {
		return actionResult{Reply: replyScheduleNotFound(scheduleName)}
	}
	rows, err := s.queryTaskRows(ctx, u, res.Matched)
	if err != nil {
		return actionResult{Err: err}
	}

	if !confirmedOf(slots) {
		// 把要删掉的东西**先说清楚**再问 —— 只问一句"确定吗"，
		// 用户是在对一个自己没看见的东西点头。
		summary := fmt.Sprintf("删除方案“%s”会连同它下面的 %d 条任务一起删掉，删完没法撤销。",
			res.Matched, len(rows))
		if len(rows) > 0 {
			names := make([]string, 0, len(rows))
			for _, r := range rows {
				names = append(names, r.Name)
			}
			summary += "涉及：" + previewNames(names, 5, "条任务") + "。"
		}
		keep := map[string][]string{"schedule_name": {scheduleName}}
		return askYesNo(IntentDeleteSchedule, raw, keep, summary, "确定要删吗？")
	}

	out, err := s.bells.Delete(ctx, u, res.Matched)
	if err != nil {
		return actionResult{
			Reply: "方案没有删除成功：" + err.Error(),
			ActionLog: []map[string]any{{
				"intent": "delete_schedule", "mode": "runtime", "schedule_name": res.Matched,
				"details": map[string]any{"error": err.Error()},
			}},
		}
	}
	return actionResult{
		// 措辞逐字取自原实现：'已删除方案"{name}"。'
		Reply: fmt.Sprintf("已删除方案“%s”。", res.Matched),
		ActionLog: []map[string]any{{
			"intent": "delete_schedule", "mode": "runtime", "schedule_name": res.Matched,
			"details": map[string]any{
				"count": out.Items, "task_ids": out.DeletedTasks,
				"power_subs": out.PowerSubs, "led_subs": out.LEDSubs,
			},
		}},
	}
}

// replyScheduleNotFound 取自 runtime_reply.py 的 reply_schedule_not_found。
// ⚠ 模板里的引号是 ASCII 的 "，不是中文引号 —— 原实现就是这么写的，
// 换成中文引号会让每一句回话都与现网不同。
func replyScheduleNotFound(name string) string {
	name = strings.TrimSpace(name)
	var variants []string
	if name != "" {
		variants = []string{
			`找不到叫"{name}"的作息方案哎，是不是名字差一点？`,
			`没找到"{name}"这个作息方案呢，再确认一下名字哈~`,
			`"{name}"这个作息方案我没看到呀，您再说一遍？`,
			`咦，"{name}"这个作息方案我这边没匹配上，确认一下名字哈~`,
		}
	} else {
		variants = []string{
			"没找到对应的作息方案哎，再说一下名字哈~",
			"对应的作息方案没匹配到呢，确认一下名字再来一次~",
			"咦，没看到匹配的作息方案，再确认一下哈~",
		}
	}
	return stableReply("schedule_not_found", variants,
		[]string{name}, map[string]string{"name": name})
}

// replyNoMatchingTasks 取自 runtime_reply.py 的 reply_no_matching_tasks。
// ⚠ 意图种子是 "no_matching_tasks_" + label（带上标签），不是固定串。
func replyNoMatchingTasks(actionLabel string, seedParts []string) string {
	label := strings.TrimSpace(actionLabel)
	if label == "" {
		label = "操作"
	}
	variants := []string{
		"哎，没找到可以{label}的任务呢，时间或方案再说细一点哈~",
		"{label}的任务没匹配上呢，再说细一点我接着帮您找~",
		"咦，没看到符合条件的可{label}任务，换个说法或者补充时间哈~",
		"没找到可{label}的任务呀，再描述清楚点我再试一下~",
	}
	seed := append([]string{label}, seedParts...)
	return stableReply("no_matching_tasks_"+label, variants, seed,
		map[string]string{"label": label})
}
