package typedtask

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strconv"
	"strings"
	"time"

	"htweb/internal/auth"
	"htweb/internal/notify"
	"htweb/internal/store"
)

// 新建 / 修改。四种类别共用同一套 task 行的写入，差异在：
//
//	终端功放  cmd = 0 打开 / 1 关闭，cmdargs = 通道号，无媒体
//	采播管理  cmd = 采播源终端 id，cmdargs = 通道号，无媒体
//	文字语音  合成一条 media（typeid='tts'）+ 若干条 ttssentence
//	LED 播放  合成一条 ledsentence + ledoftask 设备绑定，parentid = 分组
//
// 旧版这四个入口散在 do.php 的一个巨型 switch 里（13600~13980 那一段），
// 每个 case 改几个变量再落到同一条 INSERT。这里把那条 INSERT 的列清单原样保留，
// 只是把「哪些列由类别决定」提到 buildRow 里，免得再出现
// 「改了一个 case、另一个 case 悄悄跟着变」的情况。

type Terminal struct {
	TerminalID int64  `json:"terminalId"`
	Area       string `json:"area"`
	GroupID    int64  `json:"groupId"`
}

type Detail struct {
	Item
	Terminals []DetailTerminal `json:"terminals"`
	// 文字语音专用
	Sentences []Sentence `json:"sentences,omitempty"`
	// LED 专用
	LED *LEDDetail `json:"led,omitempty"`
}

type DetailTerminal struct {
	TerminalID   int64  `json:"terminalId"`
	TerminalName string `json:"terminalname"`
	TypeName     string `json:"typeName"`
	Area         string `json:"area"`
	GroupID      int64  `json:"groupId"`
	NetState     int    `json:"netstate"`
	Deleted      bool   `json:"deleted"`
}

func (s *Service) Get(ctx context.Context, u *auth.User, k Kind, id int64) (*Detail, error) {
	sp, err := s.spec(k)
	if err != nil {
		return nil, err
	}
	d := &Detail{}
	err = s.db.QueryRowContext(ctx, `
		SELECT t.taskid, COALESCE(t.taskname,''), t.tasktype,
		       COALESCE(t.state,0), COALESCE(t.projectstate,0),
		       COALESCE(CAST(t.startdate AS CHAR),''), COALESCE(CAST(t.enddate AS CHAR),''),
		       COALESCE(CAST(t.playtime AS CHAR),''), COALESCE(CAST(t.endtime AS CHAR),''),
		       COALESCE(t.exemodel,''), COALESCE(t.defaultvolume,0),
		       COALESCE(t.timelength,0), COALESCE(t.timelengthtype,0),
		       COALESCE(t.cmd,0), COALESCE(t.cmdargs,''), COALESCE(t.parentid,0),
		       COALESCE(t.task_user_id,0), COALESCE(b.username,''),
		       COALESCE(t.priority,0), COALESCE(t.bandrate,0),
		       COALESCE(t.samplerate,0), COALESCE(t.playfileid,0),
		       COALESCE(t.prepower,0), COALESCE(t.datasendmodel,0)
		FROM task t
		LEFT JOIN book_admin b ON b.id = t.task_user_id
		WHERE t.taskid = ? LIMIT 1`, id).
		Scan(&d.TaskID, &d.TaskName, &d.TaskType, &d.State, &d.ProjectState,
			&d.StartDate, &d.EndDate, &d.PlayTime, &d.EndTime,
			&d.ExeModel, &d.Volume, &d.TimeLength, &d.TimeLengthType,
			&d.Cmd, &d.CmdArgs, &d.ParentID, &d.UserID, &d.UserName,
			&d.Priority, &d.BandRate, &d.SampleRate, &d.PlayFileID,
			&d.Prepower, &d.DataSendModel)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询任务: %w", err)
	}

	inType := false
	for _, t := range sp.Types {
		if t == d.TaskType {
			inType = true
		}
	}
	if !inType {
		return nil, ErrNotFound
	}
	if !u.IsAdmin && d.UserID != u.ID {
		return nil, ErrNoPermission
	}

	d.StateText = stateText(d.State)
	d.ProjectText = projectText(d.ProjectState)
	d.CycleText = cycleText(d.ExeModel)
	d.LengthText = lengthText(d.TimeLengthType, d.TimeLength)
	d.CanModify = true
	if k == KindAmplifier {
		d.SwitchText = switchText(d.Cmd)
	}

	terms, err := s.taskTerminals(ctx, id)
	if err != nil {
		return nil, err
	}
	d.Terminals = terms
	d.TerminalCount = len(terms)

	switch k {
	case KindTTS:
		ss, err := s.taskSentences(ctx, id)
		if err != nil {
			return nil, err
		}
		d.Sentences = ss
		// 文字语音可能挂着一条 LED 字幕子任务，回填给编辑弹窗
		led, err := s.loadLEDSub(ctx, id)
		if err != nil {
			return nil, err
		}
		d.LED = led
	case KindLED:
		led, err := s.taskLED(ctx, id)
		if err != nil {
			return nil, err
		}
		d.LED = led
	}
	// 采播与文字语音的 cmd 都是终端 id：采播是音源终端，文字语音是 TTS 采播终端。
	// 放在 switch 外面，因为 KindTTS 在上面已经占了一个分支。
	if k == KindCollect || k == KindTTS {
		items := []Item{d.Item}
		if err := s.fillCollectSource(ctx, items); err == nil {
			d.SourceName = items[0].SourceName
		}
	}
	return d, nil
}

// taskTerminals LEFT JOIN：终端被删后关联行可能还在，
// 内连接会让这些脏行静默消失，看不出任务里挂着一台不存在的设备。
func (s *Service) taskTerminals(ctx context.Context, taskID int64) ([]DetailTerminal, error) {
	rs, err := s.db.QueryContext(ctx, `
		SELECT ot.terminalid, t.id IS NOT NULL, COALESCE(t.terminalname,''),
		       COALESCE(tt.name,''), COALESCE(ot.area,''), COALESCE(ot.groupid,0),
		       COALESCE(t.netstate,0)
		FROM terminaloftask ot
		LEFT JOIN terminal t ON t.id = ot.terminalid
		LEFT JOIN terminaltype tt ON tt.id = t.typeid
		WHERE ot.taskid = ?
		ORDER BY ot.id`, taskID)
	if err != nil {
		return nil, fmt.Errorf("查询任务终端: %w", err)
	}
	defer rs.Close()
	out := []DetailTerminal{}
	for rs.Next() {
		var t DetailTerminal
		var exists bool
		if err := rs.Scan(&t.TerminalID, &exists, &t.TerminalName,
			&t.TypeName, &t.Area, &t.GroupID, &t.NetState); err != nil {
			return nil, err
		}
		t.Deleted = !exists
		if t.Deleted {
			t.TerminalName = "(终端已删除)"
		}
		out = append(out, t)
	}
	return out, rs.Err()
}

// ---------- 入参 ----------

type Input struct {
	TaskName  string
	StartDate string
	EndDate   string
	PlayTime  string
	// DurationSec 是持续时长（秒）。功放用它算出 endtime；
	// 其它类别用它填 timelength（timelengthtype = 1 时）。
	DurationSec int
	// TimeLengthType 1 = 按时间，2 = 按循环次数
	TimeLengthType int
	TimeLength     int
	ExeModel       string
	Volume         int
	ProjectState   int
	FolderID       int64 // LED 分组

	// 下面三项对应 :80 表单里的「预开电源 / 任务等级 / 发送模式」。
	//
	// ⚠ 终端功放**不能**用 Prepower：功放列表的判据里有 `prepower = 0`
	//    （见 typedtask.go 的 spec.Extra），一旦给功放任务写了非 0 的 prepower，
	//    这条任务立刻从列表里消失 —— 那一列在功放这边是「独立任务 / 电源子任务」
	//    的区分标志，不是业务字段。所以 normPerKind() 会把功放的这三项强制回默认值，
	//    界面上也不给功放显示这三个输入。
	Prepower      int // task.prepower，单位**秒**（列注释写的是分钟，是错的）
	Priority      int // task.priority，任务等级
	DataSendModel int // task.datasendmodel，0 = 单播、1 = 组播

	// 功放：Switch 0=打开 1=关闭；Channel 通道号
	Switch  int
	Channel int
	// 采播：SourceTerminalID 采播源终端
	SourceTerminalID int64

	Terminals []Terminal

	// 文字语音
	Sentences []SentenceInput
	// LED
	LED *LEDInput
}

// normPerKind 把「预开电源 / 任务等级 / 发送模式」收敛到各类别允许的取值。
//
// ⚠ 终端功放必须保持 prepower = 0：功放列表的判据里带着这一条
// （spec.Extra 里的 `COALESCE(t.prepower,0) = 0`），写了非 0 值任务就从列表里消失。
// 那一列在功放这边被旧系统当成「独立任务 / 电源子任务」的区分标志用了，
// 不是给人填的业务字段。任务等级同理固定 3、发送模式固定 0，与旧版建功放任务时一致。
func normPerKind(k Kind, in *Input) {
	// 功放只强制 prepower = 0（列表判据）。
	// 任务等级 / 发送模式不强制：现网旧数据里功放任务的 priority 是 13，
	// 界面上不显示这两项，前端回填的就是原值 —— 强制成 3 会把旧数据改掉。
	if k == KindAmplifier {
		in.Prepower = 0
	}
	if in.Prepower < 0 {
		in.Prepower = 0
	}
	if in.Prepower > 86399 {
		in.Prepower = 86399
	}
	// priority 列是 int，旧版新建时一律写 3；这里允许 1~13，越界回落到 3
	if in.Priority < 1 || in.Priority > 13 {
		in.Priority = 3
	}
	// datasendmodel 只有 0（单播）/ 1（组播）两个取值
	if in.DataSendModel != 1 {
		in.DataSendModel = 0
	}
}

func (s *Service) validate(ctx context.Context, u *auth.User, k Kind, in *Input) error {
	sp, _ := s.spec(k)

	normPerKind(k, in)

	in.TaskName = strings.TrimSpace(in.TaskName)
	if in.TaskName == "" {
		return fmt.Errorf("任务名称不能为空")
	}
	if len(in.TaskName) > 255 {
		return fmt.Errorf("任务名称过长：按 UTF-8 计 %d 字节，上限 255 字节", len(in.TaskName))
	}
	// 旧版任务名里带 ? & = 会把后面拼出来的 URL 截断，与作息方案同一个毛病
	if strings.ContainsAny(in.TaskName, "?&=") {
		return fmt.Errorf("任务名称不能包含 ? & = 这三个字符")
	}

	start, err := time.Parse("2006-01-02", strings.TrimSpace(in.StartDate))
	if err != nil {
		return fmt.Errorf("开始日期格式不正确，必须是 YYYY-MM-DD")
	}
	end, err := time.Parse("2006-01-02", strings.TrimSpace(in.EndDate))
	if err != nil {
		return fmt.Errorf("结束日期格式不正确，必须是 YYYY-MM-DD")
	}
	if end.Before(start) {
		return fmt.Errorf("结束日期不能早于开始日期")
	}
	if _, err := time.Parse("15:04:05", strings.TrimSpace(in.PlayTime)); err != nil {
		return fmt.Errorf("执行时间格式不正确，必须是 HH:MM:SS")
	}

	if len(in.ExeModel) != 7 {
		return fmt.Errorf("星期掩码必须是 7 位")
	}
	for i := 0; i < 7; i++ {
		if in.ExeModel[i] != '0' && in.ExeModel[i] != '1' {
			return fmt.Errorf("星期掩码只能由 0 和 1 组成")
		}
	}
	if in.Volume < 0 || in.Volume > 100 {
		return fmt.Errorf("音量必须在 0 ~ 100 之间")
	}
	if in.ProjectState != StateEnabled && in.ProjectState != StateDisabled {
		return fmt.Errorf("方案状态只能是 0（启用）或 1（停用）")
	}
	if in.TimeLengthType != 1 && in.TimeLengthType != 2 {
		return fmt.Errorf("时长类型只能是 1（按时间）或 2（按次数）")
	}
	if in.TimeLength < 0 || in.TimeLength > 86399 {
		return fmt.Errorf("播放时长必须在 0 ~ 86399 之间")
	}
	if in.DurationSec < 0 || in.DurationSec > 86399 {
		return fmt.Errorf("持续时长必须在 0 ~ 86399 秒之间")
	}

	switch k {
	case KindAmplifier:
		if in.Switch != 0 && in.Switch != 1 {
			return fmt.Errorf("功放动作只能是 0（打开）或 1（关闭）")
		}
		// cmdargs 存通道号（列注释：「类型为5是保存通道号」）
		if in.Channel < 0 || in.Channel > 255 {
			return fmt.Errorf("通道号必须在 0 ~ 255 之间")
		}
	case KindCollect:
		if in.SourceTerminalID <= 0 {
			return fmt.Errorf("请选择采播源终端")
		}
		var n int
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM terminal WHERE id = ?`, in.SourceTerminalID).Scan(&n); err != nil {
			return fmt.Errorf("校验采播源终端: %w", err)
		}
		if n == 0 {
			return fmt.Errorf("采播源终端不存在，请重新选择")
		}
		if in.Channel < 0 || in.Channel > 255 {
			return fmt.Errorf("通道号必须在 0 ~ 255 之间")
		}
	case KindTTS:
		if err := validateSentences(in.Sentences); err != nil {
			return err
		}
	case KindLED:
		if err := validateLED(in.LED); err != nil {
			return err
		}
		if in.FolderID <= 0 {
			return fmt.Errorf("请选择 LED 任务分组")
		}
		var n int
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM ledtaskfree WHERE id = ?`, in.FolderID).Scan(&n); err != nil {
			return fmt.Errorf("校验 LED 分组: %w", err)
		}
		if n == 0 {
			return fmt.Errorf("LED 任务分组不存在，请重新选择")
		}
	}
	_ = sp

	// 终端清单
	if len(in.Terminals) > 3000 {
		return fmt.Errorf("任务终端最多 3000 台")
	}
	if len(in.Terminals) > 0 {
		seen := map[int64]bool{}
		ids := make([]int64, 0, len(in.Terminals))
		for i := range in.Terminals {
			t := &in.Terminals[i]
			if t.TerminalID <= 0 {
				return fmt.Errorf("终端列表里有非法的终端 ID")
			}
			if seen[t.TerminalID] {
				return fmt.Errorf("终端列表里有重复的终端")
			}
			seen[t.TerminalID] = true
			ids = append(ids, t.TerminalID)
			if t.Area == "" {
				t.Area = AreaAll
			}
			if len(t.Area) > 16 {
				return fmt.Errorf("终端区域掩码过长")
			}
		}
		ph, args := placeholders(ids)
		var n int
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM terminal WHERE id IN (`+ph+`)`, args...).Scan(&n); err != nil {
			return fmt.Errorf("校验终端: %w", err)
		}
		if n != len(ids) {
			return fmt.Errorf("终端列表里有已不存在的终端，请重新选择")
		}
		if !u.IsAdmin {
			var bound int
			bargs := append(append([]interface{}{}, args...), u.ID)
			if err := s.db.QueryRowContext(ctx,
				`SELECT COUNT(DISTINCT terminalid) FROM userterminal
				 WHERE terminalid IN (`+ph+`) AND userid = ?`, bargs...).Scan(&bound); err != nil {
				return fmt.Errorf("校验终端归属: %w", err)
			}
			if bound != len(ids) {
				return fmt.Errorf("终端列表里有未绑定给你的终端")
			}
		}
	}
	return nil
}

// checkNameFree 同类任务内名称唯一。
//
// 旧版对这四类任务一个重名检查都没有，同名任务可以建无数条，
// 而后台日志与遥控绑定都按名字给人看 —— 重名之后没人分得清是哪一条。
func (s *Service) checkNameFree(ctx context.Context, sp spec, name string, excludeID int64) error {
	c, args := typeCond(sp.Types)
	q := `SELECT taskid FROM task t WHERE ` + c + ` AND t.taskname = ?`
	args = append(args, name)
	if excludeID > 0 {
		q += ` AND t.taskid <> ?`
		args = append(args, excludeID)
	}
	q += ` LIMIT 1`
	var id int64
	err := s.db.QueryRowContext(ctx, q, args...).Scan(&id)
	if err == nil {
		return fmt.Errorf("同名任务已存在")
	}
	if errors.Is(err, sql.ErrNoRows) {
		return nil
	}
	return fmt.Errorf("任务重名校验: %w", err)
}

const nameLock = "htweb_typedtask_name"

// endTimeOf 算结束时间。
//
// 旧版功放分支是
// `date('H:i:s', strtotime($playtime."+".$h." hours +".$m." minutes +".$s." seconds"))`，
// 也就是**在一天之内回绕**（跨零点会绕回 00:xx）。这里用同样的语义：
// 取模 86400，保持与后台既有数据一致。
//
// ⚠ durationSec <= 0 时返回 '00:00:00'，**不是** playtime。
//
// 四个页面里只有终端功放有「持续时长」这个输入，其余三类根本没有结束时间的概念。
// 现网所有可对照的行都是 00:00:00：
//
//	tasktype 15（文字语音）70033 → 00:00:00
//	tasktype 19            70014 → 00:00:00
//	tasktype 30（LED 子任务）4 条 → 全部 00:00:00
//	tasktype 20（遥控）      4 条 → 全部 00:00:00
//
// 早前这里无条件按 playtime+0 算，于是一条 11:00 的语音任务会拿到 endtime=11:00 ——
// 后台若拿 endtime 判断何时收尾，这条任务等于「一开始就该结束」（新版缺陷 N-15）。
func endTimeOf(playtime string, durationSec int) (string, error) {
	if _, err := time.Parse("15:04:05", playtime); err != nil {
		return "", fmt.Errorf("执行时间格式不正确")
	}
	if durationSec <= 0 {
		return "00:00:00", nil
	}
	t, _ := time.Parse("15:04:05", playtime)
	base := t.Hour()*3600 + t.Minute()*60 + t.Second()
	total := (base + durationSec) % 86400
	return fmt.Sprintf("%02d:%02d:%02d", total/3600, (total%3600)/60, total%60), nil
}

func (s *Service) Create(ctx context.Context, u *auth.User, n *notify.Notifier,
	k Kind, in Input) (int64, error) {

	sp, err := s.spec(k)
	if err != nil {
		return 0, err
	}
	if err := s.validate(ctx, u, k, &in); err != nil {
		return 0, err
	}

	unlock, err := store.Lock(ctx, s.db, nameLock)
	if err != nil {
		return 0, err
	}
	defer unlock()
	if err := s.checkNameFree(ctx, sp, in.TaskName, 0); err != nil {
		return 0, err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	taskID, err := insertTask(ctx, tx, sp, k, in, u.ID)
	if err != nil {
		return 0, err
	}
	if err := s.writeExtras(ctx, tx, k, taskID, in); err != nil {
		return 0, err
	}
	if err := writeTerminals(ctx, tx, taskID, in.Terminals); err != nil {
		return 0, err
	}
	// 文字语音可以顺带挂一条 LED 字幕子任务（:80 表单里的「led播放」）
	if _, err := s.syncLEDSub(ctx, tx, k, taskID, in, u.ID); err != nil {
		return 0, err
	}
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}

	n.TaskSaved(ctx, notify.TaskAdded, taskID, in.Volume)
	return taskID, nil
}

func (s *Service) Update(ctx context.Context, u *auth.User, nt *notify.Notifier,
	k Kind, id int64, in Input) error {

	sp, err := s.spec(k)
	if err != nil {
		return err
	}
	// 先确认存在、类型对、归属对
	if _, err := s.Get(ctx, u, k, id); err != nil {
		return err
	}
	if err := s.validate(ctx, u, k, &in); err != nil {
		return err
	}

	unlock, err := store.Lock(ctx, s.db, nameLock)
	if err != nil {
		return err
	}
	defer unlock()
	if err := s.checkNameFree(ctx, sp, in.TaskName, id); err != nil {
		return err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	endTime, err := endTimeOf(in.PlayTime, in.DurationSec)
	if err != nil {
		return err
	}
	cmd, cmdargs := cmdOf(k, in)

	if _, err := tx.ExecContext(ctx, `
		UPDATE task SET taskname = ?, timelengthtype = ?, timelength = ?,
		                startdate = ?, enddate = ?, playtime = ?, endtime = ?,
		                exemodel = ?, defaultvolume = ?, projectstate = ?,
		                prepower = ?, priority = ?, datasendmodel = ?,
		                cmd = ?, cmdargs = ?, parentid = ?
		WHERE taskid = ?`,
		in.TaskName, in.TimeLengthType, in.TimeLength,
		in.StartDate, in.EndDate, in.PlayTime, endTime,
		in.ExeModel, in.Volume, in.ProjectState,
		in.Prepower, in.Priority, in.DataSendModel,
		cmd, cmdargs, in.FolderID, id); err != nil {
		return fmt.Errorf("修改任务: %w", err)
	}

	// 附加数据与终端清单一律「全清再写」，且必须在同一个事务里 ——
	// 旧版没有事务，中途失败任务就变成一个没有终端的空壳。
	if err := s.clearExtras(ctx, tx, k, id); err != nil {
		return err
	}
	if err := s.writeExtras(ctx, tx, k, id, in); err != nil {
		return err
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminaloftask WHERE taskid = ?`, id); err != nil {
		return fmt.Errorf("清理任务终端: %w", err)
	}
	if err := writeTerminals(ctx, tx, id, in.Terminals); err != nil {
		return err
	}
	// LED 字幕子任务：整条重建（内容与终端都跟着主任务走）
	if _, err := s.syncLEDSub(ctx, tx, k, id, in, u.ID); err != nil {
		return err
	}
	if err := tx.Commit(); err != nil {
		return fmt.Errorf("提交事务: %w", err)
	}

	nt.TaskSaved(ctx, notify.TaskUpdated, id, in.Volume)
	return nil
}

// cmdOf 决定 cmd 与 cmdargs 两列写什么。
//
//	功放  cmd = 0 打开 / 1 关闭   cmdargs = 通道号  （列注释就是这么写的）
//	采播  cmd = 采播源终端 id      cmdargs = 通道号
//	其它  两列都写 0
//
// cmdOf 决定 task.cmd / task.cmdargs 两列写什么。
//
// ⚠ 文字语音也用 cmd 存**终端 id**（:80 表单上叫「TTS采播终端」）。
//
//	现网数据：tasktype 15 的 70033 cmd = 2、tasktype 19 的 70014 cmd = 12，
//	都是 terminal.id。以前这里对 tts 返回 0，等于每次修改都把这一列抹成 0
//	（新版缺陷 N-19）—— 后台服务据此决定由哪台 TTS 主机去合成，
//	抹掉之后任务就没有合成方了。
func cmdOf(k Kind, in Input) (int64, string) {
	switch k {
	case KindAmplifier:
		return int64(in.Switch), strconv.Itoa(in.Channel)
	case KindCollect, KindTTS:
		return in.SourceTerminalID, strconv.Itoa(in.Channel)
	}
	return 0, "0"
}

func insertTask(ctx context.Context, tx *sql.Tx, sp spec, k Kind, in Input, userID int64) (int64, error) {
	endTime, err := endTimeOf(in.PlayTime, in.DurationSec)
	if err != nil {
		return 0, err
	}
	cmd, cmdargs := cmdOf(k, in)

	// 列清单与旧版那条共用 INSERT 一致（do.php 13913 起），
	// 只是把 $_POST 拼接换成参数绑定。
	// state 固定写 0（准备）：新建的任务不该一落库就处于「立即执行」。
	//
	// ⚠ interval_s / intplaylength / intplaylengthtype 必须**显式写 0**，不能走列默认值。
	//
	// 这三列是「间隔播放」的配置，四个页面都没有对应的输入。
	// 旧版那条共用 INSERT 把 $intervallength / $allintervallen / $intervaltype 一路带下来，
	// 这几个页面不 POST 它们，PHP 里就是空串，落库被 MySQL 归成 0 —— 即 0/0/0。
	// 而列默认值是 interval_s=0、intplaylength=**1**、intplaylengthtype=**2**，
	// 不写就会得到 0/1/2，和旧版产出的行不一样（新版缺陷 N-17）。
	//
	// 判据是现网数据：这四类任务已有的行里 intplaylength **无一例外是 0**
	// （tasktype 15 / 19 / 30 全是 0），也就是后台服务一直以来见到的就是 0。
	res, err := tx.ExecContext(ctx, `
		INSERT INTO task
		  (taskname, israndomplay, projectstate, timelengthtype, timelength, prepower,
		   datasendmodel, state, startdate, enddate, playtime, endtime, exemodel,
		   priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs,
		   playfileid, info, defaultvolume, task_user_id, sec_task_id, parentid,
		   interval_s, intplaylength, intplaylengthtype)
		VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,0,0)`,
		in.TaskName, 1, in.ProjectState, in.TimeLengthType, in.TimeLength, in.Prepower,
		in.DataSendModel, 0, in.StartDate, in.EndDate, in.PlayTime, endTime, in.ExeModel,
		in.Priority, sp.NewType, 0, 0, 0, cmd, cmdargs,
		0, "", in.Volume, userID, 0, in.FolderID)
	if err != nil {
		return 0, fmt.Errorf("新建任务: %w", err)
	}
	// LastInsertId 而不是旧版的 SELECT MAX(taskid)：并发下 MAX 会拿到别人的行
	return res.LastInsertId()
}

func writeTerminals(ctx context.Context, tx *sql.Tx, taskID int64, terms []Terminal) error {
	for _, t := range terms {
		area := t.Area
		if area == "" {
			area = AreaAll
		}
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO terminaloftask (taskid, terminalid, groupid, area) VALUES (?,?,?,?)`,
			taskID, t.TerminalID, t.GroupID, area); err != nil {
			return fmt.Errorf("写入任务终端: %w", err)
		}
	}
	return nil
}

// writeExtras / clearExtras 是每种类别的附加数据。
func (s *Service) writeExtras(ctx context.Context, tx *sql.Tx, k Kind, taskID int64, in Input) error {
	switch k {
	case KindTTS:
		return writeSentences(ctx, tx, taskID, in)
	case KindLED:
		return writeLED(ctx, tx, taskID, in)
	}
	return nil
}

func (s *Service) clearExtras(ctx context.Context, tx *sql.Tx, k Kind, taskID int64) error {
	switch k {
	case KindTTS:
		return clearSentences(ctx, tx, taskID)
	case KindLED:
		return clearLED(ctx, tx, taskID)
	}
	return nil
}

// ---------- 选择器 ----------

type TerminalOption struct {
	ID       int64  `json:"id"`
	Name     string `json:"terminalname"`
	TypeID   int    `json:"typeid"`
	TypeName string `json:"typeName"`
	IP       string `json:"ip"`
	NetState int    `json:"netstate"`
	GroupID  int64  `json:"groupId"`
	// GroupName 是终端分区名，供界面把终端选择器排成树。
	// ⚠ 早前只有 groupId 没有名字，界面就只能列成一个扁平下拉。
	GroupName string `json:"groupName"`
}

// TerminalOptions 列出可加入任务的终端。
//
// 不按终端型号过滤 —— 旧版这四个页面的终端选择器都是全量列表
// （`terminalfunctionplayadd.php` 甚至是按分区列的）。哪些型号真的支持
// 功放控制/采播/LED 由现场设备决定，库里没有可靠的标志位可以筛。
func (s *Service) TerminalOptions(ctx context.Context, u *auth.User, keyword string) ([]TerminalOption, error) {
	cond := &store.Cond{}
	if !u.IsAdmin {
		cond.Add(`t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)`, u.ID)
	}
	if keyword = strings.TrimSpace(keyword); keyword != "" {
		cond.Add(`t.terminalname LIKE ? ESCAPE '\\'`, store.EscapeLike(keyword))
	}
	// 分区名在 serverplaystream，终端与分区的关系在 terminalofgroup；
	// 一台终端理论上可能有多行，取 id 最小的那条，与 task/picker.go 的口径一致。
	rs, err := s.db.QueryContext(ctx, `
		SELECT t.id, COALESCE(t.terminalname,''), COALESCE(t.typeid,0), COALESCE(tt.name,''),
		       COALESCE(t.ip,''), COALESCE(t.netstate,0), COALESCE(t.groupid,0),
		       COALESCE((SELECT sps.name FROM terminalofgroup tog
		                 JOIN serverplaystream sps ON sps.streamid = tog.groupid
		                 WHERE tog.terminalid = t.id ORDER BY tog.id LIMIT 1), '')
		FROM terminal t
		LEFT JOIN terminaltype tt ON tt.id = t.typeid`+cond.Where()+`
		ORDER BY t.netstate DESC, t.id LIMIT 500`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询终端: %w", err)
	}
	defer rs.Close()
	out := []TerminalOption{}
	for rs.Next() {
		var o TerminalOption
		if err := rs.Scan(&o.ID, &o.Name, &o.TypeID, &o.TypeName,
			&o.IP, &o.NetState, &o.GroupID, &o.GroupName); err != nil {
			return nil, err
		}
		out = append(out, o)
	}
	return out, rs.Err()
}
