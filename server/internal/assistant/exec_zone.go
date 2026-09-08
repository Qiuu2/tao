package assistant

import (
	"context"
	"fmt"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/notify"
	"htweb/internal/terminal"
	"htweb/internal/zone"
)

// 终端与分区意图：启用/停用终端、终端校时、终端音量、分区增删、分区加/移终端。
//
// # 原实现这一块全是"打给远端"，这里全是"直接写库"
//
// 原实现每个动作前都先 `if not _remote_enabled(): return "未配置远端服务…"`，
// 因为它要 POST 到广播服务器的 /terminal/enable 之类。htweb 自己就坐在
// audioserver 上，这句话在这里没有意义 —— 对应的是 tao 的 terminal.Service 与
// zone.Service，它们已经把守卫和通知报文都做好了：
//
//	启用/停用终端  SetRunning  要求在线；停止时连 taskstate 一起清 0（BR-144）
//	终端校时      Dispatch    只下发指令不写库，结果由终端异步上报
//	终端音量      SetVolume   0~100 校验（修 D-97）
//	分区          zone.*      重名用命名锁串起来查重与写入（表上没有唯一索引）
//
// # 原实现"远端没确认成功"那一支怎么对应
//
// 原实现拿 remote_state == 0 判成功，不为 0 就回一句"远端暂未确认成功"。
// 这里对应的是 tao 的 Skipped：终端离线、类型不支持、不是自己的终端，
// 每一条都有具体理由。所以不说"远端暂未确认"这种含糊话，直接说是哪台、为什么。

// 回话措辞逐字取自原实现。
var (
	terminalStateVariants = []string{
		"已为您{state_text}{terminal_desc}。",
		"{terminal_desc} 现在是{state_text}状态。",
		"{terminal_desc} 已调整为{state_text}状态。",
	}
	syncTerminalTimeVariants = []string{
		"已向 {terminal_desc} 发出校时指令，稍后可以再确认结果。",
		"{terminal_desc} 正在同步时间。",
		"{terminal_desc} 的校时指令已经发出。",
	}
	createZoneVariants = []string{
		"分区已经建好：{zone_desc}。",
		"已完成分区创建：{zone_desc}。",
		"新的分区已经准备好了：{zone_desc}。",
	}
	deleteZoneVariants = []string{
		"已删除分区：{zone_desc}。",
		"{zone_desc}分区已经删除完成。",
		"分区删除已完成，涉及：{zone_desc}。",
	}
	addTerminalToZoneVariants = []string{
		"已将{terminal_desc}{action_label}分区{zone_desc}。",
		"{terminal_desc}已经{action_label}到分区{zone_desc}。",
		"分区调整完成：{terminal_desc}已{action_label}到{zone_desc}。",
	}
	removeTerminalFromZoneVariants = []string{
		"已将{terminal_desc}从分区{zone_desc}移出。",
		"{terminal_desc}已经从分区{zone_desc}移除。",
		"分区调整完成：{terminal_desc}已从{zone_desc}移出。",
	}
)

// skippedReasonText 把 terminal.Skipped 翻成能对用户说的话。
func skippedReasonText(sk terminal.Skipped) string {
	if strings.TrimSpace(sk.Detail) != "" {
		return sk.Detail
	}
	return sk.Reason
}

func skippedDetails(list []terminal.Skipped) []map[string]any {
	out := make([]map[string]any, 0, len(list))
	for _, sk := range list {
		out = append(out, map[string]any{
			"terminal_id": sk.ID, "terminal_name": sk.Name,
			"reason": sk.Reason, "detail": sk.Detail,
		})
	}
	return out
}

// execTerminalState 是 enable_terminal / disable_terminal 的共用实现。
func (s *Service) execTerminalState(intent string, start bool) executor {
	return func(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
		if s.terminals == nil || s.notifier == nil {
			return actionResult{Err: fmt.Errorf("终端服务未接入")}
		}
		stateText := "停用"
		notifyState := notify.TermStop
		if start {
			stateText = "启用"
			notifyState = notify.TermStart
		}

		ids, desc, res := s.resolveTerminalTargets(ctx, u, slots)
		if res != nil {
			return *res
		}

		out, err := s.terminals.SetRunning(ctx, u, ids, start)
		if err != nil {
			return actionResult{Err: err}
		}
		if len(out.Succeeded) == 0 {
			reason := "没有可执行的终端"
			if len(out.Skipped) > 0 {
				reason = skippedReasonText(out.Skipped[0])
			}
			return actionResult{
				Reply: failureRuntimeReply(intent, stateText+"终端", nil, reason, "稍后可以再试一次。"),
				ActionLog: []map[string]any{{
					"intent": intent, "mode": "runtime",
					"details": map[string]any{"terminal_ids": ids, "count": 0,
						"skipped": skippedDetails(out.Skipped), "reason": reason},
				}},
			}
		}
		// 通知在数据库改完之后发，逐台一条（BR-145），与终端页面完全一致
		s.notifier.TerminalChangedBatch(ctx, notifyState, out.Succeeded)

		reply := successRuntimeReply(intent, terminalStateVariants,
			[]string{desc, stateText},
			map[string]string{"terminal_desc": desc, "state_text": stateText})
		reply = appendReplyDetails(reply, skippedLine(out.Skipped))

		return actionResult{
			Reply: reply,
			ActionLog: []map[string]any{{
				"intent": intent, "mode": "runtime",
				"details": map[string]any{
					"terminal_ids": out.Succeeded, "count": len(out.Succeeded),
					"skipped": skippedDetails(out.Skipped), "notified": true,
				},
			}},
		}
	}
}

// execSyncTerminalTime 下发校时指令。
//
// ⚠ 只下发、不写库 —— 校时结果由终端异步上报，Web 端自己先写一个"已同步"
// 就是在撒谎。tao 的 Dispatch 正是这个语义。
func (s *Service) execSyncTerminalTime(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
	if s.terminals == nil || s.notifier == nil {
		return actionResult{Err: fmt.Errorf("终端服务未接入")}
	}
	ids, desc, res := s.resolveTerminalTargets(ctx, u, slots)
	if res != nil {
		return *res
	}
	out, err := s.terminals.Dispatch(ctx, u, ids, false)
	if err != nil {
		return actionResult{Err: err}
	}
	if len(out.Succeeded) == 0 {
		reason := "没有可执行的终端"
		if len(out.Skipped) > 0 {
			reason = skippedReasonText(out.Skipped[0])
		}
		return actionResult{
			Reply: failureRuntimeReply("sync_terminal_time", "终端校时", nil, reason, "稍后可以再试一次。"),
			ActionLog: []map[string]any{{
				"intent": "sync_terminal_time", "mode": "runtime",
				"details": map[string]any{"terminal_ids": ids, "count": 0,
					"skipped": skippedDetails(out.Skipped), "acknowledged": false, "verified": false},
			}},
		}
	}
	s.notifier.TerminalChangedBatch(ctx, notify.TermSyncTime, out.Succeeded)

	reply := successRuntimeReply("sync_terminal_time", syncTerminalTimeVariants,
		[]string{desc}, map[string]string{"terminal_desc": desc})
	reply = appendReplyDetails(reply, skippedLine(out.Skipped))

	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "sync_terminal_time", "mode": "runtime",
			"details": map[string]any{
				"terminal_ids": out.Succeeded, "count": len(out.Succeeded),
				"skipped": skippedDetails(out.Skipped),
				// acknowledged = 指令发出去了；verified = 终端回报了。
				// 后者永远是 false —— 回报是异步的，这一轮对话里等不到。
				"acknowledged": true, "verified": false,
			},
		}},
	}
}

// skippedLine 把没做成的那几台汇成一句话，接在回复后面。
func skippedLine(list []terminal.Skipped) string {
	if len(list) == 0 {
		return ""
	}
	names := make([]string, 0, len(list))
	for _, sk := range list {
		n := sk.Name
		if strings.TrimSpace(n) == "" {
			n = itoa64(sk.ID)
		}
		names = append(names, n)
	}
	return fmt.Sprintf("另有 %s 没能执行：%s。",
		previewNames(names, 3, "个终端"), skippedReasonText(list[0]))
}

// resolveTerminalTargets 把槽位里的终端指认变成 id 列表和一句可读的描述。
func (s *Service) resolveTerminalTargets(ctx context.Context, u *auth.User,
	slots map[string][]string) ([]int64, string, *actionResult) {

	ids, names, unresolved := splitTerminalSlots(slots)
	// 分区名也能指人：「把教学楼分区的终端停掉」
	for _, key := range []string{"zone_name", "ZONE"} {
		for _, v := range slots[key] {
			if v = strings.TrimSpace(v); v == "" {
				continue
			}
			zoneIDs, err := s.zoneMemberIDs(ctx, u, rawTextOf(slots), v)
			if err != nil {
				return nil, "", &actionResult{Err: err}
			}
			if len(zoneIDs) == 0 {
				unresolved["zone_name"] = append(unresolved["zone_name"], v)
				continue
			}
			ids = append(ids, zoneIDs...)
		}
	}
	if len(ids) == 0 && len(names) == 0 {
		return nil, "", &actionResult{
			Reply:        "请至少提供终端ID、终端名称或分区名称。",
			MissingSlots: []string{"terminal_id/terminal_name/zone_name"},
		}
	}
	resolved, err := s.resolveTerminalNames(ctx, rawTextOf(slots), names, unresolved)
	if err != nil {
		return nil, "", &actionResult{Err: err}
	}
	ids = append(ids, resolved...)
	ids = uniqueIDs(ids)
	if len(ids) == 0 {
		return nil, "", &actionResult{
			Reply: finalizeSentence("没有找到匹配的终端。" + replyUnresolvedLine(unresolved)),
			ActionLog: []map[string]any{{
				"mode":    "runtime",
				"details": map[string]any{"count": 0, "unresolved": unresolved},
			}},
		}
	}
	rows, err := s.queryTerminals(ctx, ids, nil)
	if err != nil {
		return nil, "", &actionResult{Err: err}
	}
	desc := previewNames(namesOf(rows), 3, "个终端")
	if desc == "" {
		desc = fmt.Sprintf("%d个终端", len(ids))
	}
	return ids, desc, nil
}

func uniqueIDs(ids []int64) []int64 {
	seen := map[int64]bool{}
	out := make([]int64, 0, len(ids))
	for _, id := range ids {
		if id <= 0 || seen[id] {
			continue
		}
		seen[id] = true
		out = append(out, id)
	}
	return out
}

// ---------- 分区 ----------

// zoneCandidates 给名称解析用的分区名单。
func (s *Service) zoneCandidates(ctx context.Context, u *auth.User) ([]Candidate, error) {
	if s.zones == nil {
		return nil, fmt.Errorf("分区服务未接入")
	}
	opts, err := s.zones.Options(ctx, u)
	if err != nil {
		return nil, err
	}
	out := make([]Candidate, 0, len(opts))
	for _, o := range opts {
		out = append(out, Candidate{ID: o.ID, Name: o.Name})
	}
	return out, nil
}

// zoneMemberIDs 取一个分区（按名字）下的终端 id。
func (s *Service) zoneMemberIDs(ctx context.Context, u *auth.User, raw, name string) ([]int64, error) {
	cands, err := s.zoneCandidates(ctx, u)
	if err != nil {
		return nil, err
	}
	res := s.ResolveName(ctx, name, raw, cands)
	if res.Matched == "" {
		return nil, nil
	}
	d, err := s.zones.Get(ctx, u, res.ID)
	if err != nil {
		return nil, err
	}
	out := make([]int64, 0, len(d.Members))
	for _, m := range d.Members {
		if !m.Deleted {
			out = append(out, m.TerminalID)
		}
	}
	return out, nil
}

// execCreateZone 新建分区。可以一次说好几个。
func (s *Service) execCreateZone(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
	if s.zones == nil {
		return actionResult{Err: fmt.Errorf("分区服务未接入")}
	}
	names := slotValues(slots, "zone_name", "ZONE")
	if len(names) == 0 {
		return actionResult{
			Reply:        askRuntimeReply("create_zone", "分区名称", "比如教学楼分区"),
			MissingSlots: []string{"zone_name"},
		}
	}
	existing, err := s.zoneCandidates(ctx, u)
	if err != nil {
		return actionResult{Err: err}
	}
	have := map[string]bool{}
	for _, c := range existing {
		have[compactText(c.Name)] = true
	}

	var created, skipped []string
	failures := map[string]string{}
	for _, name := range names {
		if have[compactText(name)] {
			// 已经有了就不重复建 —— 与原实现一致，不当成失败
			skipped = append(skipped, name)
			continue
		}
		if _, err := s.zones.Create(ctx, u, zone.Input{Name: name}); err != nil {
			skipped = append(skipped, name)
			failures[name] = err.Error()
			continue
		}
		created = append(created, name)
		have[compactText(name)] = true
	}

	if len(created) == 0 {
		reply := "分区新建失败，远端未接受本次请求。"
		// ⚠ 这句话原样保留了"远端"两个字会误导人 —— 这里根本没有远端。
		// 换成能对上的说法，并把第一条真实原因带出来。
		reply = "分区没有建成。"
		for _, name := range names {
			if msg, bad := failures[name]; bad {
				reply += "\n首个错误：" + name + ": " + msg
				break
			}
		}
		if len(failures) == 0 && len(skipped) > 0 {
			reply = fmt.Sprintf("分区“%s”已经存在，没有重复创建。",
				strings.Join(skipped, "、"))
		}
		return actionResult{
			Reply: reply,
			ActionLog: []map[string]any{{
				"intent": "create_zone", "mode": "runtime",
				"details": map[string]any{"created": created, "skipped": skipped, "failures": failures},
			}},
		}
	}

	zoneDesc := previewNames(created, 3, "个分区")
	reply := stableReply("create_zone", createZoneVariants,
		[]string{zoneDesc}, map[string]string{"zone_desc": zoneDesc})
	var details []string
	if len(skipped) > 0 {
		details = append(details, fmt.Sprintf("以下分区已存在，未重复创建：%s。",
			previewNames(skipped, 3, "个分区")))
	}
	if len(failures) > 0 {
		details = append(details, fmt.Sprintf("另有 %d 个分区创建失败，详见操作日志。", len(failures)))
	}
	reply = appendReplyDetails(reply, details...)

	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "create_zone", "mode": "runtime",
			"details": map[string]any{"created": created, "skipped": skipped, "failures": failures},
		}},
	}
}

// execDeleteZone 删分区。
func (s *Service) execDeleteZone(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
	if s.zones == nil {
		return actionResult{Err: fmt.Errorf("分区服务未接入")}
	}
	names := slotValues(slots, "zone_name", "ZONE")
	if len(names) == 0 {
		return actionResult{
			Reply:        askRuntimeReply("delete_zone", "分区名称", "比如教学楼分区"),
			MissingSlots: []string{"zone_name"},
		}
	}
	ids, matched, missing, res := s.resolveZoneNames(ctx, u, rawTextOf(slots), names)
	if res != nil {
		return *res
	}
	if len(ids) == 0 {
		return actionResult{
			Reply: failureRuntimeReply("no_zone_to_delete", "可删除的分区", nil,
				"", "确认一下分区名再说一次哈~"),
		}
	}

	out, err := s.zones.Delete(ctx, u, ids)
	if err != nil {
		return actionResult{
			Reply: "分区删除没有完成，稍后再试。",
			ActionLog: []map[string]any{{
				"intent": "delete_zone", "mode": "runtime",
				"details": map[string]any{"zone_ids": ids, "zone_names": matched,
					"missing": missing, "error": err.Error()},
			}},
		}
	}

	zoneDesc := previewNames(matched, 3, "个分区")
	if zoneDesc == "" {
		zoneDesc = fmt.Sprintf("%d个分区", len(ids))
	}
	reply := stableReply("delete_zone", deleteZoneVariants,
		[]string{zoneDesc}, map[string]string{"zone_desc": zoneDesc})
	missingLine := ""
	if len(missing) > 0 {
		missingLine = fmt.Sprintf("未匹配分区：%s。", strings.Join(missing, "、"))
	}
	reply = appendReplyDetails(reply,
		fmt.Sprintf("删除数量：%d 个。", len(out.Deleted)), missingLine)

	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "delete_zone", "mode": "runtime",
			"details": map[string]any{"zone_ids": ids, "zone_names": matched,
				"deleted": out.Deleted, "missing": missing},
		}},
	}
}

// execZoneMembership 是 add_terminal_to_zone / remove_terminal_from_zone 的共用实现。
//
// tao 的 zone.Update 是**整表替换**成员（页面上就是勾选框的全量提交）。
// 助手这边说的是增量（"把 A101 加进教学楼分区"），所以要先读出现有成员，
// 加上或去掉，再整体写回去。中间不能有别人改这个分区 —— 这一点由 Update
// 自身的事务保证，这里不额外加锁：分区成员不是高并发的东西，
// 而且真撞上了页面那边也是同样的行为。
func (s *Service) execZoneMembership(intent string, add bool) executor {
	return func(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
		if s.zones == nil {
			return actionResult{Err: fmt.Errorf("分区服务未接入")}
		}
		zoneNames := slotValues(slots, "zone_name", "ZONE")
		if len(zoneNames) == 0 {
			return actionResult{
				Reply:        askRuntimeReply(intent, "分区名称", "比如教学楼分区"),
				MissingSlots: []string{"zone_name"},
			}
		}
		zoneIDs, matchedZones, missingZones, res := s.resolveZoneNames(ctx, u, rawTextOf(slots), zoneNames)
		if res != nil {
			return *res
		}
		if len(zoneIDs) == 0 {
			return actionResult{
				Reply: failureRuntimeReply("no_zone_to_operate", "可操作的分区", nil,
					"", "确认一下分区名再说一次哈~"),
			}
		}

		// ⚠ 这里**不**把分区展开成终端：分区在这条意图里是目标，不是来源。
		// 原实现同样是 expand_zones=False。
		ids, names, unresolved := splitTerminalSlots(slots)
		if len(ids) == 0 && len(names) == 0 {
			return actionResult{
				Reply:        "请至少提供终端ID或终端名称。",
				MissingSlots: []string{"terminal_id/terminal_name"},
			}
		}
		resolvedIDs, err := s.resolveTerminalNames(ctx, rawTextOf(slots), names, unresolved)
		if err != nil {
			return actionResult{Err: err}
		}
		termIDs := uniqueIDs(append(ids, resolvedIDs...))
		if len(termIDs) == 0 {
			return actionResult{
				Reply: finalizeSentence("没有找到匹配的终端。" + replyUnresolvedLine(unresolved)),
			}
		}

		for _, zid := range zoneIDs {
			d, err := s.zones.Get(ctx, u, zid)
			if err != nil {
				return actionResult{Err: err}
			}
			cur := make([]int64, 0, len(d.Members))
			for _, m := range d.Members {
				if !m.Deleted {
					cur = append(cur, m.TerminalID)
				}
			}
			var next []int64
			if add {
				next = uniqueIDs(append(cur, termIDs...))
			} else {
				drop := map[int64]bool{}
				for _, id := range termIDs {
					drop[id] = true
				}
				for _, id := range cur {
					if !drop[id] {
						next = append(next, id)
					}
				}
			}
			if err := s.zones.Update(ctx, u, zid, zone.Input{
				Name: d.Name, Info: d.Info, TerminalIDs: next,
			}); err != nil {
				return actionResult{
					Reply: "分区终端调整没有完成，稍后再试。",
					ActionLog: []map[string]any{{
						"intent": intent, "mode": "runtime",
						"details": map[string]any{"zone_ids": zoneIDs, "zone_names": matchedZones,
							"terminal_ids": termIDs, "error": err.Error()},
					}},
				}
			}
		}

		rows, err := s.queryTerminals(ctx, termIDs, nil)
		if err != nil {
			return actionResult{Err: err}
		}
		termDesc := previewNames(namesOf(rows), 3, "个终端")
		if termDesc == "" {
			termDesc = fmt.Sprintf("%d个终端", len(termIDs))
		}
		zoneDesc := previewNames(matchedZones, 3, "个分区")
		if zoneDesc == "" {
			zoneDesc = fmt.Sprintf("%d个分区", len(zoneIDs))
		}
		label := "移出"
		variants := removeTerminalFromZoneVariants
		if add {
			label = "加入"
			variants = addTerminalToZoneVariants
		}
		reply := stableReply(intent, variants,
			[]string{zoneDesc, termDesc, label},
			map[string]string{"zone_desc": zoneDesc, "terminal_desc": termDesc, "action_label": label})
		leftover := map[string][]string{}
		for k, v := range unresolved {
			leftover[k] = v
		}
		if len(missingZones) > 0 {
			leftover["zone_name"] = missingZones
		}
		reply = appendReplyDetails(reply, replyUnresolvedLine(leftover))

		return actionResult{
			Reply: reply,
			ActionLog: []map[string]any{{
				"intent": intent, "mode": "runtime",
				"details": map[string]any{
					"zone_ids": zoneIDs, "zone_names": matchedZones,
					"terminal_ids": termIDs, "missing_zone": missingZones,
					"unresolved": unresolved,
				},
			}},
		}
	}
}

// resolveZoneNames 把一串分区名对到真实分区上，返回命中的 id、命中的名字、没对上的名字。
func (s *Service) resolveZoneNames(ctx context.Context, u *auth.User, raw string,
	names []string) (ids []int64, matched, missing []string, res *actionResult) {

	cands, err := s.zoneCandidates(ctx, u)
	if err != nil {
		return nil, nil, nil, &actionResult{Err: err}
	}
	for _, n := range names {
		r := s.ResolveName(ctx, n, raw, cands)
		if r.Matched == "" {
			missing = append(missing, n)
			continue
		}
		ids = append(ids, r.ID)
		matched = append(matched, r.Matched)
	}
	return uniqueIDs(ids), uniqueTexts(matched), uniqueTexts(missing), nil
}

// slotValues 取一个槽位下的全部值（不像 slotText 那样连成一串）。
func slotValues(slots map[string][]string, keys ...string) []string {
	var out []string
	for _, k := range keys {
		for _, v := range slots[k] {
			if v = strings.TrimSpace(v); v != "" {
				out = append(out, v)
			}
		}
	}
	return uniqueTexts(out)
}

// compactText 取自 helpers.py 的 compact_text：去掉空白后比较，
// 「一号 分区」和「一号分区」算同一个。
func compactText(s string) string {
	return strings.Join(strings.Fields(s), "")
}
