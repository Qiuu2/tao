package openapi

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strconv"
	"strings"
	"time"

	"htweb/internal/auth"
	"htweb/internal/task"
)

// 立即播放 / 停止。
//
// # 这套库里没有「临时播放」这个一等公民
//
// 想让某个终端**现在**出声，唯一的办法就是界面上那两步：
// 新建一条文件广播任务 → 点启动。所以这里也照做：
//
//	1. 建一条 tasktype=2 的任务，星期掩码全 0（= 手动，后台永不自动触发），
//	   起止日期都是今天
//	2. 走 task.Control 启动它 —— 与界面点「启动」完全同一条路，
//	   发的也是同一份 task?state=3&id=X 报文
//
// 掩码全 0 正合适：这条任务不该明天到点自己再响一次。启动是显式动作，
// 与掩码无关。
//
// # 建出来的任务必须登记
//
// 立即播放会在 task 表里留下一条真实任务。不登记的话，几天后任务列表里
// 就堆着一串没人认识的条目 —— 而 task 表不能加列做标记（R1 红线），
// 所以另起了 api_play 这张登记表（db/openapi_tables.sql）。
//
// 与助手那张 assistant_runtime_play 分开，是因为「停止播放」不该互相波及：
// 用户在助手里说「停」，不该把第三方系统正在放的东西停掉。

// PlayInput 是立即播放的入参。
type PlayInput struct {
	// Media 是要播的东西。名字或编号都行。
	Media Ref `json:"media"`
	// Terminals / Zones 是播到哪，取并集。至少给一个。
	Terminals []Ref `json:"terminals"`
	Zones     []Ref `json:"zones"`
	Volume    int   `json:"volume"`
	// Seconds 是最多播多久。不给就按媒体自己的时长，还不行兜底 300 秒。
	//
	// ⚠ 兜底绝不能是 0：timelength = 0 在这套库里是「不限时长」，
	//   一条立即播放挂着不停，比放错了还糟 —— 没人知道该去哪儿关掉它。
	Seconds int `json:"seconds"`
}

// PlayHandle 是一次立即播放的句柄。停止时把 playId 交回来。
type PlayHandle struct {
	PlayID    int64       `json:"playId"`
	TaskID    int64       `json:"taskId"`
	Media     string      `json:"media"`
	Terminals []NamedItem `json:"terminals"`
	Volume    int         `json:"volume"`
	Seconds   int         `json:"seconds"`
	State     string      `json:"state"`
	StartTime string      `json:"startTime"`
	EndTime   string      `json:"endTime"`
}

// playFallbackSeconds 是算不出时长时的兜底。见 PlayInput.Seconds 上的说明。
const playFallbackSeconds = 300

// Play 立即播放。
func (s *Service) Play(ctx context.Context, u *auth.User, keyPrefix string, in PlayInput) (*PlayHandle, error) {
	if s.tasks == nil || s.notifier == nil {
		return nil, fmt.Errorf("任务服务未接入")
	}
	if in.Media.empty() {
		return nil, badf("请指定要播的媒体")
	}
	mediaID, err := s.resolveOne(ctx, u, s.specMedia(), in.Media)
	if err != nil {
		return nil, err
	}
	termIDs, err := s.resolveTargets(ctx, u, in.Terminals, in.Zones)
	if err != nil {
		return nil, err
	}
	if len(termIDs) == 0 {
		return nil, badf("请指定播到哪些终端或分区")
	}

	volume := in.Volume
	if volume == 0 {
		volume = 80
	}
	if volume < 0 || volume > 100 {
		return nil, badf("音量要在 0 ~ 100 之间")
	}

	seconds := in.Seconds
	if seconds < 0 || seconds > 86400 {
		return nil, badf("时长要在 0 ~ 86400 秒之间")
	}
	if seconds == 0 {
		seconds = s.mediaSeconds(ctx, []int64{mediaID})
	}
	if seconds <= 0 {
		seconds = playFallbackSeconds
	}

	mediaName := s.nameOfMedia(ctx, u, mediaID)
	now := time.Now()
	today := now.Format("2006-01-02")

	terms := make([]task.TerminalRef, 0, len(termIDs))
	for _, id := range termIDs {
		terms = append(terms, task.TerminalRef{TerminalID: id, Area: task.AreaAll})
	}
	folderID, err := s.pickFolder(ctx, u, Ref{}, nil)
	if err != nil {
		return nil, err
	}

	spec := task.Input{
		TaskName:     playTaskName(mediaName, now),
		FolderID:     folderID,
		ProjectState: 0, // 启用
		IsRandomPlay: 1, // 顺序
		StartDate:    today,
		EndDate:      today,
		PlayTime:     now.Format("15:04:05"),
		EndTime:      now.Add(time.Duration(seconds) * time.Second).Format("15:04:05"),
		ExeModel:     "0000000", // 手动 —— 不该明天到点再响一次
		TimeLengthTy: 1,         // 1 = 按时间（秒）
		TimeLength:   seconds,
		// 间隔播放：不间隔，但**类型必须填** —— task.Create 要求它是 1 或 2，
		// 留 0 会被挡下（现网踩过这个坑）。
		IntervalS:    0,
		IntPlayLen:   1,
		IntPlayLenTy: 2,
		Volume:       volume,
		// 任务级别 10~109，**数字越小级别越高**。
		//
		// ⚠ 这里给最高级（10），与排期任务的「默认最低」是反的，这是有意的：
		//   立即播放是调用方**此刻**要求的，给低了会被排期任务压住 ——
		//   调用方收到 200、以为播了，实际一点声音都没有。
		//   风险由权限位管：能调这个接口的账号本来就有 taskpriv。
		Priority:  10,
		Media:     []task.MediaRef{{MediaID: mediaID, Sort: 0}},
		Terminals: terms,
	}

	saved, err := s.tasks.Create(ctx, u, spec)
	if err != nil {
		return nil, err
	}

	out, err := s.tasks.Control(ctx, u, s.notifier, task.ActionStart, []int64{saved.TaskID})
	if err != nil || len(out.Succeeded) == 0 {
		reason := "启动没成功"
		if err != nil {
			reason = err.Error()
		} else if len(out.Blocked) > 0 {
			reason = out.Blocked[0].Detail
		}
		// 建了任务却没播起来 —— 删掉它，别在任务列表里留一条没人认识的条目
		if _, delErr := s.tasks.Delete(ctx, u, []int64{saved.TaskID}); delErr != nil {
			logf("立即播放回滚失败：任务 %d 没能删掉：%v", saved.TaskID, delErr)
		}
		return nil, badf("没能播起来：%s", reason)
	}

	playID, err := s.recordPlay(ctx, u, keyPrefix, saved.TaskID, mediaName, mediaID, termIDs)
	if err != nil {
		// 登记失败不回滚播放 —— 声音已经出去了，撤不回来。
		// 但要让运维知道有一条没登记的临时任务。
		logf("立即播放登记失败（任务 %d 已在播）: %v", saved.TaskID, err)
	}

	return &PlayHandle{
		PlayID:    playID,
		TaskID:    saved.TaskID,
		Media:     mediaName,
		Terminals: s.namedTerminals(ctx, u, termIDs),
		Volume:    volume,
		Seconds:   seconds,
		State:     "playing",
		StartTime: now.Format("2006-01-02 15:04:05"),
	}, nil
}

// StopPlay 停掉一次立即播放，并把那条临时任务删掉。
//
// ⚠ 删掉是对的：这条任务的存在只为了「让它现在响」，停了就没有意义了。
// 留着的话任务列表里会越堆越多，而且它们全是掩码 0000000 的手动任务，
// 谁也说不清能不能删。
func (s *Service) StopPlay(ctx context.Context, u *auth.User, playID int64) (*PlayHandle, error) {
	if s.tasks == nil || s.notifier == nil {
		return nil, fmt.Errorf("任务服务未接入")
	}
	h, err := s.getPlay(ctx, u, playID)
	if err != nil {
		return nil, err
	}
	if h.State != "playing" {
		// 幂等：已经停了就直接回当前状态，不报错 ——
		// 调用方重试一次不该收到失败。
		return h, nil
	}

	if _, err := s.tasks.Control(ctx, u, s.notifier, task.ActionStop, []int64{h.TaskID}); err != nil {
		return nil, err
	}
	if _, err := s.tasks.Delete(ctx, u, []int64{h.TaskID}); err != nil {
		// 停住了但没删掉。声音已经停了，这是可接受的降级 ——
		// 报错会让调用方以为没停成而重试，反而更糟。
		logf("立即播放停止后清理失败（任务 %d 仍在列表里）: %v", h.TaskID, err)
	}
	if _, err := s.db.ExecContext(ctx,
		`UPDATE api_play SET state = 'stopped', endtime = NOW() WHERE id = ?`, playID); err != nil {
		logf("立即播放登记更新失败（play %d）: %v", playID, err)
	}
	h.State = "stopped"
	h.EndTime = time.Now().Format("2006-01-02 15:04:05")
	return h, nil
}

// ListPlays 列出还在播的立即播放。
//
// 只列 playing 的：stopped 的行留在库里是为了追溯（谁什么时候让操场响的），
// 但对调用方没有意义 —— 他要的是「现在还有什么在响、我能停掉哪些」。
func (s *Service) ListPlays(ctx context.Context, u *auth.User) ([]PlayHandle, error) {
	// ⚠ 音量与时长**不存在 api_play 里**，是从 task 行上取的。
	//   登记表存一份副本的话，界面上改了任务音量、这里还报旧值 ——
	//   而这种不一致查起来极费劲。任务被删掉时（已停止）取不到，回 0。
	q := `SELECT p.id, p.taskid, COALESCE(p.medianame,''), COALESCE(p.terminal_ids,''),
	             COALESCE(p.state,''),
	             COALESCE(DATE_FORMAT(p.starttime,'%Y-%m-%d %H:%i:%s'),''),
	             COALESCE(DATE_FORMAT(p.endtime,'%Y-%m-%d %H:%i:%s'),''),
	             COALESCE(t.defaultvolume,0), COALESCE(t.timelength,0)
	        FROM api_play p
	        LEFT JOIN task t ON t.taskid = p.taskid
	       WHERE p.state = 'playing'`
	var args []any
	if !u.IsAdmin {
		q += ` AND p.userid = ?`
		args = append(args, u.ID)
	}
	rows, err := s.db.QueryContext(ctx, q+` ORDER BY p.id DESC LIMIT 200`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询立即播放: %w", err)
	}
	defer rows.Close()
	out := []PlayHandle{}
	for rows.Next() {
		var (
			h     PlayHandle
			terms string
		)
		if err := rows.Scan(&h.PlayID, &h.TaskID, &h.Media, &terms,
			&h.State, &h.StartTime, &h.EndTime, &h.Volume, &h.Seconds); err != nil {
			return nil, err
		}
		h.Terminals = s.namedTerminals(ctx, u, parseIDCSV(terms))
		out = append(out, h)
	}
	return out, rows.Err()
}

// getPlay 取一条登记。看不见别人的。
func (s *Service) getPlay(ctx context.Context, u *auth.User, playID int64) (*PlayHandle, error) {
	q := `SELECT p.id, p.taskid, COALESCE(p.medianame,''), COALESCE(p.terminal_ids,''),
	             COALESCE(p.state,''),
	             COALESCE(DATE_FORMAT(p.starttime,'%Y-%m-%d %H:%i:%s'),''),
	             COALESCE(DATE_FORMAT(p.endtime,'%Y-%m-%d %H:%i:%s'),''),
	             COALESCE(t.defaultvolume,0), COALESCE(t.timelength,0)
	        FROM api_play p
	        LEFT JOIN task t ON t.taskid = p.taskid
	       WHERE p.id = ?`
	args := []any{playID}
	if !u.IsAdmin {
		q += ` AND p.userid = ?`
		args = append(args, u.ID)
	}
	var (
		h     PlayHandle
		terms string
	)
	err := s.db.QueryRowContext(ctx, q+` LIMIT 1`, args...).Scan(&h.PlayID, &h.TaskID,
		&h.Media, &terms, &h.State, &h.StartTime, &h.EndTime, &h.Volume, &h.Seconds)
	if errors.Is(err, sql.ErrNoRows) {
		// ⚠ 「不是你的」也走这一条，理由同别处：分开说等于让调用方
		//   能试出别人发起过几次播放。
		return nil, badf("找不到这次播放（playId=%d）", playID)
	}
	if err != nil {
		return nil, fmt.Errorf("查询立即播放: %w", err)
	}
	h.Terminals = s.namedTerminals(ctx, u, parseIDCSV(terms))
	return &h, nil
}

func (s *Service) recordPlay(ctx context.Context, u *auth.User, keyPrefix string,
	taskID int64, mediaName string, mediaID int64, termIDs []int64) (int64, error) {

	res, err := s.db.ExecContext(ctx, `
		INSERT INTO api_play (taskid, medianame, media_ids, terminal_ids, state, userid, keyprefix)
		VALUES (?,?,?,?, 'playing', ?, ?)`,
		taskID, mediaName, strconv.FormatInt(mediaID, 10), joinIDCSV(termIDs), u.ID, keyPrefix)
	if err != nil {
		return 0, fmt.Errorf("登记立即播放: %w", err)
	}
	return res.LastInsertId()
}

// nameOfMedia 取媒体名。查不到就用编号占位 —— 名字只用于展示与登记，
// 取不到不该让整次播放失败。
func (s *Service) nameOfMedia(ctx context.Context, u *auth.User, id int64) string {
	rows, err := s.specMedia().query(ctx, u)
	if err != nil {
		logf("取媒体名失败（登记里用编号占位）: %v", err)
		return "媒体#" + strconv.FormatInt(id, 10)
	}
	for _, r := range rows {
		if r.ID == id {
			return r.Name
		}
	}
	return "媒体#" + strconv.FormatInt(id, 10)
}

// namedTerminals 把终端 id 配上名字。
func (s *Service) namedTerminals(ctx context.Context, u *auth.User, ids []int64) []NamedItem {
	out := make([]NamedItem, 0, len(ids))
	if len(ids) == 0 {
		return out
	}
	rows, err := s.specTerminal().query(ctx, u)
	if err != nil {
		logf("回填终端名失败（只给编号）: %v", err)
		for _, id := range ids {
			out = append(out, NamedItem{ID: id})
		}
		return out
	}
	byID := map[int64]string{}
	for _, r := range rows {
		byID[r.ID] = r.Name
	}
	for _, id := range ids {
		out = append(out, NamedItem{ID: id, Name: byID[id]})
	}
	return out
}

// playTaskName 给临时任务起名。
//
// 名字里带「接口」两个字是有意的：运维在任务列表里看到它，要能一眼判断
// 这是第三方系统建的、不是谁手工加的，也不是助手建的。
func playTaskName(mediaName string, at time.Time) string {
	base := strings.TrimSpace(mediaName)
	if runes := []rune(base); len(runes) > 16 {
		base = string(runes[:16])
	}
	if base == "" {
		base = "即时播放"
	}
	return fmt.Sprintf("接口即时播放_%s_%s", base, at.Format("0102150405"))
}

func joinIDCSV(ids []int64) string {
	parts := make([]string, 0, len(ids))
	for _, id := range ids {
		parts = append(parts, strconv.FormatInt(id, 10))
	}
	return strings.Join(parts, ",")
}

func parseIDCSV(v string) []int64 {
	var out []int64
	for _, part := range strings.Split(v, ",") {
		if part = strings.TrimSpace(part); part == "" {
			continue
		}
		if n, err := strconv.ParseInt(part, 10, 64); err == nil {
			out = append(out, n)
		}
	}
	return out
}
