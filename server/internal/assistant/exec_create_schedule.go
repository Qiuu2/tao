package assistant

import (
	"context"
	"fmt"
	"time"

	"htweb/internal/assistant/templates"
	"htweb/internal/auth"
	"htweb/internal/bell"
	"htweb/internal/task"
)

// create_schedule：按模板新建一个作息方案。
//
// 「新建一个暑假作息」—— 建出来的是一整套打铃条目（起床、预备、第一节上课…），
// 时刻与星期照模板，媒体按名字在本库里找，终端用全部能放音的终端。
//
// # 学校类型与季节必须先设好
//
// 原实现在这里很坚决：`if not schedule_kind: return "请先在AI助手中设置学校类型"`。
// 理由也直白 —— 中学的作息和大学的作息差得远，从聊天内容里猜一个出来，
// 建错了要一条条删。所以宁可让用户先去设置里点两下。
//
// 这两项存在 assistant_setting 里（键名与原实现一致：
// default_schedule_kind / default_schedule_season）。
//
// # 媒体按名字找，不按 id
//
// ⚠ 模板里的 mediaid 是**原库的** id，在这套库里指向完全不同的东西。
// 照抄会让「起床」挂上一首莫名其妙的歌，而且看不出来。所以只用 medianame，
// 走与终端同一套四层名称解析在本库的 media 表里找。找不到就跳过那一条并如实说。
//
// # 重名自动加后缀
//
// 原实现的做法：叫「春季作息」而已经有了，就试「春季作息(1)」「春季作息(2)」。
// 照搬 —— 建方案是个"多来一个也无妨"的动作，为重名把整件事拒掉不划算。

// execCreateSchedule 按模板建作息方案。
func (s *Service) execCreateSchedule(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
	if s.bells == nil || s.terminals == nil {
		return actionResult{Err: fmt.Errorf("作息方案服务未接入")}
	}
	raw := rawTextOf(slots)
	name := slotText(slots, "schedule_name", "new_schedule_name", "name")
	if name == "" {
		return actionResult{
			Reply: askRuntimeReply("create_schedule", "新作息方案名称",
				"比如春季作息、考试周作息或者高三冲刺作息"),
			MissingSlots: []string{"schedule_name"},
		}
	}

	kind, season, err := s.scheduleTemplateSetting(ctx, u)
	if err != nil {
		return actionResult{Err: err}
	}
	if kind == "" {
		return actionResult{Reply: "请先在AI助手中设置学校类型（小学/中学/高中/大学）。"}
	}
	if season == "" {
		return actionResult{Reply: "请先在AI助手中设置作息季节（夏季/冬季）。"}
	}
	tpl, err := templates.Lookup(kind, season)
	if err != nil {
		return actionResult{Reply: err.Error() + "。可以换一个学校类型或季节。"}
	}
	if len(tpl.Items) == 0 {
		return actionResult{Reply: fmt.Sprintf("「%s%s」这套模板是空的，建不出方案。", kind, season)}
	}

	// 终端：全部能放音的。原实现的助手路径同样是 all_playback_terminals。
	terms, err := s.playbackTerminals(ctx, u)
	if err != nil {
		return actionResult{Err: err}
	}
	if len(terms) == 0 {
		return actionResult{
			Reply: failureRuntimeReply("no_terminal_for_new_schedule", "可用的播放终端", nil,
				"", "再说一下要哪些终端，我重新帮您建一遍哈~"),
		}
	}

	// 起止日期：说了就用，没说给一年
	start, end := s.scheduleDateRange(slots, raw)

	// 重名加后缀
	finalName, err := s.freeScheduleName(ctx, u, name)
	if err != nil {
		return actionResult{Err: err}
	}

	items := make([]bell.ItemInput, 0, len(tpl.Items))
	var missingMedia []string
	for _, it := range tpl.Items {
		mediaID := int64(0)
		if it.Media != "" {
			id, _, err := s.resolveMedia(ctx, u, it.Media, it.Media)
			if err != nil {
				return actionResult{Err: err}
			}
			mediaID = id
		}
		if mediaID == 0 {
			// 没有媒体的打铃条目到点不会响，建它没有意义 —— 跳过并如实说
			missingMedia = append(missingMedia, it.Name+"（"+it.Media+"）")
			continue
		}
		items = append(items, bell.ItemInput{
			TaskName:     it.Name,
			PlayTime:     formatHHMMSS(it.Start),
			TimeLengthTy: 1, // 1 = 按时间（秒）
			TimeLength:   maxInt(1, it.Seconds),
			Media:        []task.MediaRef{{MediaID: mediaID, Sort: 0}},
		})
	}
	if len(items) == 0 {
		return actionResult{
			Reply: fmt.Sprintf("「%s%s」模板里的 %d 条铃声在媒体库里一条都没找到，方案没建。"+
				"先把铃声传进媒体库再试一次。", kind, season, len(tpl.Items)),
		}
	}

	// 模板里各条目的星期理论上一致（都是"周一到周五"这类），
	// 取第一条的当方案级掩码 —— bell 的方案级 exemodel 就是这个语义。
	exemodel := exemodelFromWeekdays(tpl.Items[0].Weekdays)
	if exemodel == "0000000" {
		// 全 0 在这套库里是「手动」，作息方案不该是手动的
		exemodel = "0111110" // 周一到周五
	}

	in := bell.PlanInput{
		PlanName: finalName,
		Schedule: bell.Schedule{StartDate: start, EndDate: end, ExeModel: exemodel},
		Playback: bell.Playback{
			Volume:   templateVolume(tpl),
			Priority: 10, // 10 是最高级别，作息该压过普通广播
		},
		Terminals: terms,
		Items:     items,
	}
	if _, err := s.bells.Create(ctx, u, in); err != nil {
		return actionResult{
			Reply: failureRuntimeReply("create_schedule", "新作息方案", []string{finalName},
				err.Error(), "换个方案名或者检查一下模板再试。"),
		}
	}

	reply := successRuntimeReply("create_schedule", createScheduleVariants,
		[]string{finalName, seedNum(len(items))},
		map[string]string{
			"schedule_name": finalName, "count": itoa(len(items)),
			"kind": kind, "season": season,
		})
	var details []string
	if finalName != name {
		details = append(details, fmt.Sprintf("「%s」已经有了，这个叫「%s」。", name, finalName))
	}
	if len(missingMedia) > 0 {
		details = append(details, fmt.Sprintf("有 %d 条铃声在媒体库里没找到，跳过了：%s。",
			len(missingMedia), previewNames(missingMedia, 3, "条")))
	}
	details = append(details, fmt.Sprintf("有效期 %s 至 %s，下发到 %d 个终端。", start, end, len(terms)))
	reply = appendReplyDetails(reply, details...)

	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "create_schedule", "mode": "runtime", "schedule_name": finalName,
			"details": map[string]any{
				"count": len(items), "kind": kind, "season": season,
				"template": tpl.Label, "terminal_count": len(terms),
				"missing_media": missingMedia,
				"startdate":     start, "enddate": end, "exemodel": exemodel,
			},
		}},
	}
}

// createScheduleVariants 是建方案的成功措辞。
// 原实现这一句在函数里就地拼，这里按同样的意思写成三条走 stable_reply。
var createScheduleVariants = []string{
	"新作息方案「{schedule_name}」建好啦，一共 {count} 条铃声。",
	"「{schedule_name}」已经建好了，{count} 条铃声都排上了。",
	"作息方案「{schedule_name}」准备好了，共 {count} 条铃声。",
}

// scheduleTemplateSetting 读「学校类型 / 作息季节」。
// 键名与原实现一致。先看自己的设置，没有再看全局的。
func (s *Service) scheduleTemplateSetting(ctx context.Context, u *auth.User) (string, string, error) {
	m, err := s.GetSettings(ctx, u.ID)
	if err != nil {
		return "", "", err
	}
	kind := templates.NormalizeKind(m["default_schedule_kind"])
	season := templates.NormalizeSeason(m["default_schedule_season"])
	return kind, season, nil
}

// playbackTerminals 取全部能放音的终端。
//
// 判据用 terminal 包已有的能力位：能解码就能放音。
// 离线的也算进来 —— 作息是排期的东西，建的时候某台离线不代表到点也离线。
func (s *Service) playbackTerminals(ctx context.Context, u *auth.User) ([]task.TerminalRef, error) {
	q := `SELECT t.id FROM terminal t
	      LEFT JOIN terminaltype tt ON tt.id = t.typeid
	      WHERE COALESCE(tt.isdecode,1) = 1`
	var args []any
	if !u.IsAdmin {
		q += ` AND t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)`
		args = append(args, u.ID)
	}
	q += ` ORDER BY t.id`
	rows, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询播放终端: %w", err)
	}
	defer rows.Close()
	var out []task.TerminalRef
	for rows.Next() {
		var id int64
		if err := rows.Scan(&id); err != nil {
			return nil, err
		}
		out = append(out, task.TerminalRef{TerminalID: id})
	}
	return out, rows.Err()
}

// scheduleDateRange 定方案的有效期。说了就用，没说给一年 ——
// 不给的话 bell.Create 会拿到空日期而报错，而"建个作息"本来不该要求
// 用户先想清楚它管到哪天。
func (s *Service) scheduleDateRange(slots map[string][]string, raw string) (string, string) {
	now := nowFunc()
	start := now
	end := now.AddDate(1, 0, 0)

	if v := slotText(slots, "source_time", "time_range_start", "start_date", "start"); v != "" {
		if d, ok := parsePhase1Date(v); ok {
			start = d
			end = d.AddDate(1, 0, 0)
		}
	}
	if v := slotText(slots, "end_time", "time_range_end", "end_date", "end"); v != "" {
		if d, ok := parsePhase1Date(v); ok && !d.Before(start) {
			end = d
		}
	} else if v := slotText(slots, "time_offset", "duration_offset"); v != "" {
		if mins, ok := parseTimeOffsetMinutes(v); ok && mins > 0 {
			end = start.Add(time.Duration(mins) * time.Minute)
		}
	}
	return start.Format("2006-01-02"), end.Format("2006-01-02")
}

// freeScheduleName 找一个没被占用的方案名：重名就加 (1)(2)…
func (s *Service) freeScheduleName(ctx context.Context, u *auth.User, want string) (string, error) {
	plans, err := s.scheduleCandidates(ctx, u)
	if err != nil {
		return "", err
	}
	taken := map[string]bool{}
	for _, p := range plans {
		taken[compactText(p.Name)] = true
	}
	if !taken[compactText(want)] {
		return want, nil
	}
	for i := 1; i < 100; i++ {
		candidate := fmt.Sprintf("%s(%d)", want, i)
		if !taken[compactText(candidate)] {
			return candidate, nil
		}
	}
	return "", fmt.Errorf("叫「%s」的方案太多了，换个名字吧", want)
}

// templateVolume 取模板里最常见的那个音量。
func templateVolume(t templates.Template) int {
	count := map[int]int{}
	for _, it := range t.Items {
		if it.Volume >= 0 && it.Volume <= 100 {
			count[it.Volume]++
		}
	}
	best, bestN := 80, 0
	for v, n := range count {
		if n > bestN || (n == bestN && v < best) {
			best, bestN = v, n
		}
	}
	return best
}

func maxInt(a, b int) int {
	if a > b {
		return a
	}
	return b
}
