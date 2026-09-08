package assistant

import (
	"context"
	"fmt"
	"strings"

	"htweb/internal/auth"
)

// 终端相关的只读意图：query_terminal / check_terminal。
//
// 逐条对着原实现的 _apply_query_terminal_intent / _apply_check_terminal_intent 搬，
// 差别只有一处：**数据来源从 HTTP SDK 换成直接查 audioserver**。
// 原实现拿的是 SDK 的 terminalinfo 快照（带缓存，可能过期），这里直接查库，
// 更新更快也少一层缓存失效的坑。
//
// 话术、计数口径、明细行的措辞与顺序都与原实现一致（reply.go + reply_test.go）。

// 话术模板。⚠ 一个字都不能改 —— 改了哈希选句的结果就变了，
// 同一句话在新旧两版会给出不同回复。
var queryTerminalVariants = []string{
	"{terminal_desc} 的状态已经查到：在线 {online} 个，离线 {offline} 个，未知 {unknown} 个。",
	"小电已经帮您查到 {terminal_desc} 的状态：在线 {online} 个，离线 {offline} 个，未知 {unknown} 个。",
	"终端查询结果已经出来了：{terminal_desc} 在线 {online} 个，离线 {offline} 个，未知 {unknown} 个。",
}

var checkTerminalVariants = []string{
	"终端自检我已经查完了，涉及 {terminal_desc}：在线 {online} 个，离线 {offline} 个，未知 {unknown} 个。",
	"{terminal_desc} 的自检结果已经回来了：在线 {online} 个，离线 {offline} 个，未知 {unknown} 个。",
	"这条我已经帮您查完了，{terminal_desc} 当前状态是：在线 {online} 个，离线 {offline} 个，未知 {unknown} 个。",
}

// TerminalRow 是一台终端的运行时快照，字段名与原实现的 _terminal_runtime_rows 对齐。
type TerminalRow struct {
	TerminalID   int64  `json:"terminal_id"`
	TerminalName string `json:"terminal_name"`
	Zone         string `json:"zone"`
	NetState     string `json:"netstate"`
	DeviceState  int    `json:"devicestate"`
	Status       string `json:"status"`
}

type terminalSnapshot struct {
	Rows         []TerminalRow
	OnlineIDs    []string
	OfflineNames []string
	UnknownNames []string
}

// buildTerminalSnapshot 把查出来的终端分成在线/离线/未知三堆。
//
// ⚠ netstate 的三态判定与原实现完全一致：
//
//	NULL 或空串 → unknown（不是离线！设备从没上报过状态，与"报告说我掉线了"是两回事）
//	"0"        → offline
//	其它       → online
//
// 把 unknown 并进 offline 是很自然的想法，但那会让一台从没连过的新终端
// 在自检报告里被算成"掉线"，运维会去查一台根本没装好的设备。
func buildTerminalSnapshot(rows []TerminalRow) terminalSnapshot {
	out := terminalSnapshot{Rows: rows}
	for _, r := range rows {
		switch {
		case strings.TrimSpace(r.NetState) == "":
			out.UnknownNames = append(out.UnknownNames, r.TerminalName)
		case r.NetState == "0":
			out.OfflineNames = append(out.OfflineNames, r.TerminalName)
		default:
			out.OnlineIDs = append(out.OnlineIDs, fmt.Sprintf("%d", r.TerminalID))
		}
	}
	out.OnlineIDs = uniqueTexts(out.OnlineIDs)
	out.OfflineNames = uniqueTexts(out.OfflineNames)
	out.UnknownNames = uniqueTexts(out.UnknownNames)
	return out
}

// terminalStatusText 复刻 _terminal_runtime_status 的三态文字。
func terminalStatusText(netstate string, devicestate int) string {
	switch {
	case strings.TrimSpace(netstate) == "":
		return "未知"
	case netstate == "0":
		return "离线"
	case devicestate == 1:
		return "播放中"
	default:
		return "在线"
	}
}

// queryTerminals 从库里取终端快照。
//
// 可见范围：终端表本身在 tao 里对所有登录用户可见（与终端管理页一致），
// 所以这里不额外收敛 —— 收敛比页面更严会让助手查不到页面上看得见的东西。
func (s *Service) queryTerminals(ctx context.Context, ids []int64, names []string) ([]TerminalRow, error) {
	var where []string
	var args []interface{}
	if len(ids) > 0 {
		ph := make([]string, len(ids))
		for i, id := range ids {
			ph[i] = "?"
			args = append(args, id)
		}
		where = append(where, "t.id IN ("+strings.Join(ph, ",")+")")
	}
	for _, n := range names {
		where = append(where, "t.terminalname = ?")
		args = append(args, n)
	}
	cond := ""
	if len(where) > 0 {
		cond = " WHERE " + strings.Join(where, " OR ")
	}
	rows, err := s.db.QueryContext(ctx, `
		SELECT t.id, COALESCE(t.terminalname,''), COALESCE(sp.name,''),
		       COALESCE(CAST(t.netstate AS CHAR),''), COALESCE(t.devicestate,0)
		FROM terminal t
		LEFT JOIN terminalofgroup g ON g.terminalid = t.id
		LEFT JOIN serverplaystream sp ON sp.streamid = g.groupid`+cond+`
		GROUP BY t.id ORDER BY t.id`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询终端: %w", err)
	}
	defer rows.Close()

	out := []TerminalRow{}
	for rows.Next() {
		var r TerminalRow
		if err := rows.Scan(&r.TerminalID, &r.TerminalName, &r.Zone, &r.NetState, &r.DeviceState); err != nil {
			return nil, err
		}
		if r.TerminalName == "" {
			r.TerminalName = fmt.Sprintf("终端%d", r.TerminalID)
		}
		r.Status = terminalStatusText(r.NetState, r.DeviceState)
		out = append(out, r)
	}
	return out, rows.Err()
}

// execQueryTerminal 是 query_terminal 的执行器。
func (s *Service) execQueryTerminal(ctx context.Context, _ *auth.User, slots map[string][]string) actionResult {
	ids, names, unresolved := splitTerminalSlots(slots)
	if len(ids) == 0 && len(names) == 0 {
		return actionResult{
			Reply:        "请至少提供终端ID、终端名称或分区名称。",
			MissingSlots: []string{"terminal_id/terminal_name/zone_name"},
		}
	}
	rows, err := s.queryTerminals(ctx, ids, names)
	if err != nil {
		return actionResult{Err: err}
	}
	// 名字报上来了但库里没有 → 记进"没匹配上"，与原实现一致
	found := map[string]bool{}
	for _, r := range rows {
		found[r.TerminalName] = true
	}
	for _, n := range names {
		if !found[n] {
			unresolved["terminal_name"] = append(unresolved["terminal_name"], n)
		}
	}

	snap := buildTerminalSnapshot(rows)
	desc := previewNames(namesOf(snap.Rows), 3, "个终端")
	if desc == "" {
		desc = fmt.Sprintf("%d个终端", len(snap.Rows))
	}
	reply := queryRuntimeReply("query_terminal", queryTerminalVariants,
		// ⚠ 种子里计数为 0 的那一位是空串，不是 "0"，见 reply.go 的 seedNum
		[]string{desc, seedNum(len(snap.OnlineIDs)), seedNum(len(snap.OfflineNames)), seedNum(len(snap.UnknownNames))},
		map[string]string{
			"terminal_desc": desc,
			"online":        itoa(len(snap.OnlineIDs)),
			"offline":       itoa(len(snap.OfflineNames)),
			"unknown":       itoa(len(snap.UnknownNames)),
		})
	reply = finalizeKeyIntentReply("query_terminal", reply, replyUnresolvedLine(unresolved))

	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "query_terminal", "mode": "query",
			"details": map[string]any{
				"count": len(snap.Rows), "online": len(snap.OnlineIDs),
				"offline": len(snap.OfflineNames), "unknown": len(snap.UnknownNames),
				"online_ids": snap.OnlineIDs, "offline_names": snap.OfflineNames,
				"unknown_names": snap.UnknownNames, "terminals": snap.Rows,
				"unresolved": unresolved,
			},
		}},
	}
}

// execCheckTerminal 是 check_terminal 的执行器。
//
// 与 query_terminal 的差别（照搬原实现）：
//   - 没点名任何终端时**查全部**，而不是要求补槽位 —— 「终端自检」这句话
//     本来就是"把所有终端过一遍"的意思
//   - 但如果用户明确点了名而没匹配上，就报"没找到"，不能悄悄降级成查全部
//   - 回复里多两行明细：离线的是哪几台、状态未知的是哪几台
func (s *Service) execCheckTerminal(ctx context.Context, _ *auth.User, slots map[string][]string) actionResult {
	ids, names, unresolved := splitTerminalSlots(slots)
	hasExplicitTarget := len(ids) > 0 || len(names) > 0 || len(slots["zone_name"]) > 0

	var rows []TerminalRow
	var err error
	if hasExplicitTarget {
		rows, err = s.queryTerminals(ctx, ids, names)
	} else {
		rows, err = s.queryTerminals(ctx, nil, nil) // 全部
	}
	if err != nil {
		return actionResult{Err: err}
	}
	if len(rows) == 0 {
		if hasExplicitTarget {
			return actionResult{Reply: "没有找到匹配的终端或分区，再确认一下名字，或者换种说法描述哈~"}
		}
		return actionResult{Reply: "没有找到可自检的终端，再确认一下范围哈~"}
	}
	found := map[string]bool{}
	for _, r := range rows {
		found[r.TerminalName] = true
	}
	for _, n := range names {
		if !found[n] {
			unresolved["terminal_name"] = append(unresolved["terminal_name"], n)
		}
	}

	snap := buildTerminalSnapshot(rows)
	desc := previewNames(namesOf(snap.Rows), 3, "个终端")
	if desc == "" {
		desc = fmt.Sprintf("%d个终端", len(rows))
	}
	reply := queryRuntimeReply("check_terminal", checkTerminalVariants,
		// ⚠ 种子里计数为 0 的那一位是空串，不是 "0"，见 reply.go 的 seedNum
		[]string{desc, seedNum(len(snap.OnlineIDs)), seedNum(len(snap.OfflineNames)), seedNum(len(snap.UnknownNames))},
		map[string]string{
			"terminal_desc": desc,
			"online":        itoa(len(snap.OnlineIDs)),
			"offline":       itoa(len(snap.OfflineNames)),
			"unknown":       itoa(len(snap.UnknownNames)),
		})

	details := []string{}
	if len(snap.OfflineNames) > 0 {
		details = append(details, "离线终端："+previewNames(snap.OfflineNames, 8, "个终端")+"。")
	}
	if len(snap.UnknownNames) > 0 {
		details = append(details, "状态未知："+previewNames(snap.UnknownNames, 8, "个终端")+"。")
	}
	if line := replyUnresolvedLine(unresolved); line != "" {
		details = append(details, line)
	}
	reply = appendReplyDetails(reply, details...)

	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "check_terminal", "mode": "query",
			"details": map[string]any{
				"count": len(rows), "online_ids": snap.OnlineIDs,
				"offline_names": snap.OfflineNames, "unknown_names": snap.UnknownNames,
				"terminals": snap.Rows, "unresolved": unresolved,
			},
		}},
	}
}

func namesOf(rows []TerminalRow) []string {
	out := make([]string, 0, len(rows))
	for _, r := range rows {
		out = append(out, r.TerminalName)
	}
	return out
}
