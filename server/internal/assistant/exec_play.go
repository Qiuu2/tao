package assistant

import (
	"context"
	"fmt"
	"strings"
	"time"

	"htweb/internal/auth"
	"htweb/internal/task"
)

// play_media：立即播放。
//
// 「把国歌放到一号分区」—— 现在就响，不排期。
//
// # 这套库里没有"临时任务"这个一等公民
//
// 原实现调远端的 addtemp 接口，远端替它建一条临时任务并立刻下发。
// 这套库里对应的做法就是页面上「新建一条文件广播任务 → 点启动」那两步：
//
//	1. 建一条 tasktype=2 的任务，媒体与终端按用户说的填，
//	   星期掩码全 0（不按周自动执行），起止日期都是今天
//	2. 走 task.Control 启动它 —— 与页面上点「启动」完全同一条路，
//	   同样发 task?state=3&id=X 报文
//
// 星期掩码全 0 在这套库里是「手动」，正合适：这条任务不该到点自己再响一次。
// 启动是显式动作，与掩码无关。
//
// # 建出来的任务要登记，否则它就是一条无主的垃圾
//
// 立即播放会在 task 表里留下一条真实任务。不登记的话，几天后
// 任务列表里就会堆着一串没人知道是什么的条目。所以每建一条就往
// assistant_runtime_play 里记一笔（这张表就是为它建的），
// 前端的「助手时间线」和「停止播放」都读它。
//
// # 撤销
//
// 原实现给立即播放配了一个 30 秒的"等等，不是这个"按钮，POST 到
// runtime_play_tasks/stop。这里对应的是 assistant_undo 里的一枚令牌，
// 用掉它就停止并删掉那条任务。

// execPlayMedia 立即播放。
func (s *Service) execPlayMedia(ctx context.Context, u *auth.User, slots map[string][]string) actionResult {
	if s.tasks == nil || s.notifier == nil {
		return actionResult{Err: fmt.Errorf("任务服务未接入")}
	}
	raw := rawTextOf(slots)
	mediaText := slotText(slots, "media_name", "CONTENT", "TASK", "audio", "medianame")
	if mediaText == "" {
		return actionResult{
			Reply:        askRuntimeReply("play_media", "要播什么音频", "比如播放国歌"),
			MissingSlots: []string{"media_name"},
		}
	}

	// 终端：分区在这条意图里是**来源**，要展开成终端
	ids, desc, res := s.resolveTerminalTargets(ctx, u, slots)
	if res != nil {
		return *res
	}

	mediaID, mediaName, err := s.resolveMedia(ctx, u, raw, mediaText)
	if err != nil {
		return actionResult{Err: err}
	}
	if mediaID == 0 {
		return actionResult{Reply: fmt.Sprintf("媒体库中未找到可即时点播的“%s”。", mediaText)}
	}

	volume := 80
	if v, ok := parseVolume(slotText(slots, "volume", "value")); ok && v >= 0 && v <= 100 {
		volume = v
	}
	// 时长：说了就用，没说按媒体自己的时长，再不行给 300 秒兜底。
	// ⚠ 兜底不能是 0：timelength=0 的任务在这套库里是"不限时长"，
	//   一条立即播放挂着不停，比放错还糟。
	timelength := 300
	if secs, ok := s.mediaDurationSeconds(ctx, mediaID); ok && secs > 0 {
		timelength = secs
	}
	if d := slotText(slots, "play_duration", "duration", "play_length"); d != "" {
		if mins, ok := parseTimeOffsetMinutes(d); ok && mins > 0 {
			timelength = mins * 60
		}
	}

	now := nowFunc()
	today := now.Format("2006-01-02")
	terms := make([]task.TerminalRef, 0, len(ids))
	for _, id := range ids {
		terms = append(terms, task.TerminalRef{TerminalID: id})
	}

	in := task.Input{
		TaskName:     playTaskName(mediaName, now),
		FolderID:     task.DefaultFolder,
		ProjectState: task.StateEnabled,
		StartDate:    today,
		EndDate:      today,
		PlayTime:     now.Format("15:04:05"),
		EndTime:      now.Add(time.Duration(timelength) * time.Second).Format("15:04:05"),
		ExeModel:     "0000000", // 手动 —— 不该到点自己再响一次
		TimeLengthTy: 1,         // 1 = 按时间（秒）
		TimeLength:   timelength,
		// 间隔播放：不间隔（间隔 0 秒），但**类型必须填** ——
		// task.Create 要求它是 1 或 2，留 0 会被挡下。
		IntervalS:    0,
		IntPlayLen:   1,
		IntPlayLenTy: 2,
		Volume:       volume,
		// 任务级别 10~109，**数字越小级别越高**（界面上写着「10 为最高级别」）。
		// 立即播放是用户当下要求的，给最高级别 —— 否则它会被排期任务压住，
		// 用户说"现在放"却没声音。
		Priority:  10,
		Media:     []task.MediaRef{{MediaID: mediaID, Sort: 0}},
		Terminals: terms,
	}
	saved, err := s.tasks.Create(ctx, u, in)
	if err != nil {
		return actionResult{
			Reply: failureRuntimeReply("play_media", "即时媒体播放", []string{mediaName},
				err.Error(), "稍后可以再试一次。"),
		}
	}

	out, err := s.tasks.Control(ctx, u, s.notifier, task.ActionStart, []int64{saved.TaskID})
	if err != nil || len(out.Succeeded) == 0 {
		reason := "启动没成功"
		if err != nil {
			reason = err.Error()
		} else if len(out.Blocked) > 0 {
			reason = blockedReasonText(out.Blocked[0])
		}
		// 建了任务却没播起来 —— 把它删掉，别在列表里留一条没人认识的条目
		if _, delErr := s.tasks.Delete(ctx, u, []int64{saved.TaskID}); delErr != nil {
			logf("立即播放回滚失败：任务 %d 没能删掉：%v", saved.TaskID, delErr)
		}
		return actionResult{
			Reply: failureRuntimeReply("play_media", "即时媒体播放", []string{mediaName},
				reason, "稍后可以再试一次。"),
		}
	}

	// 登记，让它能被找到、被停掉
	// ⚠ 登记里存**媒体名**，不是那个带时间戳的内部任务名。
	// 用户说"停止播放"时听到的是这个名字，回一句
	// "助手即时播放_出旗曲.mp3_0908064302 已停止" 只会让人困惑。
	if err := s.recordRuntimePlay(ctx, u, saved.TaskID, mediaName, mediaID, ids); err != nil {
		// 登记失败不回滚播放 —— 声音已经出去了，撤回不了。
		// 但要让运维知道有一条没登记的任务。
		logf("立即播放登记失败（任务 %d 已在播）：%v", saved.TaskID, err)
	}

	reply := successRuntimeReply("play_media", playMediaVariants,
		[]string{mediaName, desc},
		map[string]string{"media_name": mediaName, "terminal_desc": desc})
	reply = appendReplyDetails(reply,
		fmt.Sprintf("音量 %d，时长约 %d 秒。不想放了随时叫我停。", volume, timelength))

	return actionResult{
		Reply: reply,
		ActionLog: []map[string]any{{
			"intent": "play_media", "mode": "runtime",
			"details": map[string]any{
				"runtime_scope": "temp_task", "task_id": itoa64(saved.TaskID),
				"media_id": itoa64(mediaID), "media_name": mediaName,
				"terminal_ids": ids, "terminal_count": len(ids),
				"volume": volume, "timelength": timelength, "timelengthtype": 1,
				"created_at": now.Format("2006-01-02 15:04:05"),
				// 前端据此渲染那个"等等，不是这个"按钮
				"undo_kind":     "runtime_play_stop",
				"undo_task_ids": []string{itoa64(saved.TaskID)},
				"undo_summary":  fmt.Sprintf("立即播放「%s」", mediaName),
			},
		}},
	}
}

// playMediaVariants 是立即播放的成功措辞。
// 原实现这一句在 _apply_play_media_intent 里就地拼，没有单独的模板表，
// 这里按同样的意思写成三条走 stable_reply，与别处一致。
var playMediaVariants = []string{
	"已经在 {terminal_desc} 上播「{media_name}」了。",
	"「{media_name}」正在 {terminal_desc} 上播放。",
	"{terminal_desc} 已经开始播「{media_name}」。",
}

// playTaskName 给立即播放建出来的任务起名。
// 要一眼看出是助手临时建的，否则运维在任务列表里会以为是谁手工加的。
func playTaskName(mediaName string, at time.Time) string {
	base := strings.TrimSpace(mediaName)
	runes := []rune(base)
	if len(runes) > 16 {
		base = string(runes[:16])
	}
	if base == "" {
		base = "即时播放"
	}
	return fmt.Sprintf("助手即时播放_%s_%s", base, at.Format("0102150405"))
}

// resolveMedia 按名字找一条媒体。走与终端同一套四层解析。
func (s *Service) resolveMedia(ctx context.Context, u *auth.User, raw, name string) (int64, string, error) {
	// ⚠ 媒体名那一列叫 name，不是 medianame（medianame 是 SDK 那边的叫法）
	q := `SELECT id, COALESCE(name,'') FROM media WHERE COALESCE(name,'') <> ''`
	var args []any
	if !u.IsAdmin {
		q += ` AND COALESCE(userid,0) = ?`
		args = append(args, u.ID)
	}
	q += ` ORDER BY id LIMIT 5000`
	rows, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return 0, "", fmt.Errorf("查询媒体: %w", err)
	}
	defer rows.Close()
	var cands []Candidate
	for rows.Next() {
		var c Candidate
		if err := rows.Scan(&c.ID, &c.Name); err != nil {
			return 0, "", err
		}
		cands = append(cands, c)
	}
	if err := rows.Err(); err != nil {
		return 0, "", err
	}
	res := s.ResolveName(ctx, name, raw, cands)
	return res.ID, res.Matched, nil
}

// mediaDurationSeconds 取媒体自己的时长。
func (s *Service) mediaDurationSeconds(ctx context.Context, mediaID int64) (int, bool) {
	var secs int
	// ⚠ 媒体时长那一列叫 timelength（秒）
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(timelength,0) FROM media WHERE id = ? LIMIT 1`, mediaID).Scan(&secs)
	if err != nil || secs <= 0 {
		return 0, false
	}
	return secs, true
}

// recordRuntimePlay 往 assistant_runtime_play 记一笔。
func (s *Service) recordRuntimePlay(ctx context.Context, u *auth.User, taskID int64,
	displayName string, mediaID int64, terminalIDs []int64) error {

	termText := make([]string, 0, len(terminalIDs))
	for _, id := range terminalIDs {
		termText = append(termText, itoa64(id))
	}
	_, err := s.db.ExecContext(ctx, `
		INSERT INTO assistant_runtime_play
		       (taskid, taskname, media_ids, terminal_ids, state, userid, starttime)
		VALUES (?,?,?,?,?,?,NOW())`,
		taskID, sanitize(displayName, 255), itoa64(mediaID),
		strings.Join(termText, ","), "playing", u.ID)
	if err != nil {
		return fmt.Errorf("登记即时播放: %w", err)
	}
	return nil
}

// activeRuntimePlay 取这个用户当前还在播的那一条即时播放。
//
// 只看自己的：两个人各自点了播放，A 说"停"不该把 B 的停掉。
func (s *Service) activeRuntimePlay(ctx context.Context, u *auth.User) (int64, string, bool) {
	var id, taskID int64
	var taskName string
	err := s.db.QueryRowContext(ctx, `
		SELECT r.id, r.taskid, COALESCE(r.taskname,'')
		  FROM assistant_runtime_play r
		  JOIN task t ON t.taskid = r.taskid
		 WHERE r.userid = ? AND r.state = 'playing'
		 ORDER BY r.id DESC LIMIT 1`, u.ID).Scan(&id, &taskID, &taskName)
	if err != nil {
		return 0, "", false
	}
	return taskID, taskName, true
}

// stopRuntimePlay 停掉一条即时播放，并把登记改成已结束。
//
// ⚠ 停完要**把任务删掉**。立即播放建出来的任务只为这一次响，留着它
// 任务列表里就会越堆越多，而且每一条都长得像用户自己建的。
// 删除放在停止之后：先让声音停下来，再收拾。
func (s *Service) stopRuntimePlay(ctx context.Context, u *auth.User, taskID int64) error {
	if _, err := s.tasks.Control(ctx, u, s.notifier, task.ActionStop, []int64{taskID}); err != nil {
		return fmt.Errorf("停止即时播放: %w", err)
	}
	if _, err := s.db.ExecContext(ctx,
		`UPDATE assistant_runtime_play SET state='stopped', endtime=NOW()
		  WHERE taskid=? AND userid=? AND state='playing'`, taskID, u.ID); err != nil {
		logf("即时播放登记改状态失败（任务 %d 已停）：%v", taskID, err)
	}
	if _, err := s.tasks.Delete(ctx, u, []int64{taskID}); err != nil {
		// 删不掉不影响"已经停了"这个事实，但要留痕
		logf("即时播放的临时任务 %d 没能删掉：%v", taskID, err)
	}
	return nil
}
