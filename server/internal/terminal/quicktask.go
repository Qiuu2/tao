package terminal

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/store"
	"htweb/internal/task"
)

// 快捷任务（ok112 的 view_quickplay / setquickplay / modifyquickplay
// 与 do.php 的 set_task_quick_play / modify_task_quick_play / del_quick_task）。
//
// # 它不是「把已有任务绑到键上」
//
// 这一点极易搞错。「快捷任务」是**为这台终端新建一条专属任务**，然后把它绑到
// 某个键上 —— 任务的名字、时长、优先级、音量、媒体、目标终端全在这个表单里填，
// 而不是从现成任务里挑一条。ok112 的列表查询把话说死了：
//
//	WHERE terminalkeymaptask.taskid = task.taskid
//	  AND terminal.id = <本终端>
//	  AND terminal.id = task.cmdargs
//	  AND task.tasktype IN (20, 21, 29)
//
// 只认这三类、且必须是本终端专属（cmdargs 指向自己）的任务。
//
// # 三个 tasktype 的由来
//
//	20  普通快捷任务：按键播放选定的媒体文件
//	21  文字播报：勾了 TTS，内容是一段文字，由 TTS 主机合成
//	29  同 21，但音源选的是「服务器」（typeid = 0）。
//	    ok112 在这种情况下还会把语速 ×10 —— 服务器侧的语速标度与
//	    TTS 主机不是一个量纲，这个倍数是硬编码在旧版里的。
//
// LED 字幕不改 tasktype，它是另外挂一条 LED 子任务（走 task 模块那套）。
//
// # 两个终端 ID，两个列
//
//	task.cmd      音源终端（TTS 主机或服务器），ok112 写的是 $audiosource
//	task.cmdargs  宿主终端（按下键的那台），ok112 写的是 $_GET['terminal_id']
//
// 列名和含义毫无关系，是旧库的字段复用。terminalkeymaptask.terminalid 取的
// 也是宿主终端。delete.go 里两个列都要比，只比一个会漏。
//
// # 键的绑定
//
//	terminalkeymaptask (keyid, terminalid, taskid)   PRIMARY KEY (keyid, terminalid)
//
// ⚠ keyid 存的是**键值本身**，不是 terminalkey.id。名字骗人，
//   当外键去 JOIN terminalkey 一条都查不出来。

// 快捷任务的三个类型。
const (
	QuickTypeMedia  = 20 // 播放媒体文件
	QuickTypeTTS    = 21 // 文字播报（音源是 TTS 主机）
	QuickTypeTTSSrv = 29 // 文字播报（音源是服务器）
	// QuickLEDType 是挂在快捷任务下的 LED 字幕子任务（sec_task_id 指回主任务）
	QuickLEDType = 30
	// QuickLEDSpeedMax 是界面上「Led速度」的上限：0 ~ 5 级。
	// 旧版没有这个输入框，写库时把 speed 写死成 5。
	QuickLEDSpeedMax = 5
)

// serverAudioType 是「服务器」这个音源的终端类型。选它时 tasktype 走 29。
const serverAudioType = 0

// ttsSpeedScaleOnServer 是音源为服务器时的语速倍数。
//
// ok112 的 set_task_quick_play 里写死 `$speed_value = $speed_value * 10`。
// 服务器侧的语速标度和 TTS 主机不是一个量纲，照搬这个倍数，别自作主张改。
const ttsSpeedScaleOnServer = 10

// ttsChunkBytes 是 TTS 文本切块的字节数。
//
// ok112 的 str_split_utf8 按 1000 字节一块切，每块写一条 ttssentence，
// 不足 1000 字节就是一整块。这里按同样的粒度切，并且只在 UTF-8 字符边界上断开。
const ttsChunkBytes = 1000

// QuickTask 是快捷任务列表的一行，列与 ok112 的 view_quickplay_from.html 对齐。
type QuickTask struct {
	TaskID   int64  `json:"taskId"`
	TaskName string `json:"taskName"`
	// Key 是键值（terminalkeymaptask.keyid）。
	Key      int    `json:"key"`
	KeyLabel string `json:"keyLabel"`
	// TimeLength 配合 TimeLengthTy：类型 1 时是秒数，类型 2 时是循环次数。
	TimeLength   int    `json:"timeLength"`
	TimeLengthTy int    `json:"timeLengthType"`
	Priority     int    `json:"priority"`
	Volume       int    `json:"volume"`
	IsRandomPlay int    `json:"isRandomPlay"`
	DataSendMode int    `json:"dataSendMode"`
	TaskType     int    `json:"taskType"`
	TypeText     string `json:"typeText"`
	// TerminalName 是宿主终端名，ok112 的列表里也有这一列。
	TerminalName string `json:"terminalName"`
}

// QuickTaskDetail 是「修改快捷任务」表单的回填数据。
type QuickTaskDetail struct {
	QuickTask
	MediaIDs    []int64      `json:"mediaIds"`
	Media       []QuickMedia `json:"media"`
	TerminalIDs []int64      `json:"terminalIds"`
	TTS         *QuickTTS    `json:"tts,omitempty"`
	LED         *QuickLED    `json:"led,omitempty"`
}

// QuickMedia 是任务里的一条媒体，回填时用来显示名字。
type QuickMedia struct {
	MediaID int64  `json:"mediaId"`
	Name    string `json:"name"`
	Sort    int    `json:"sort"`
}

// QuickTTS 是文字播报部分，对应表单里勾上 TTS 之后那几项。
type QuickTTS struct {
	Text string `json:"text"`
	// Speed 是表单里填的语速，未经服务器倍数换算。
	Speed int `json:"speed"`
	// MusicMode 对应 ttssentence.male（男声 / 女声）。
	MusicMode int `json:"musicMode"`
	// AudioSource 是音源终端 ID，写进 task.cmd。0 表示服务器。
	AudioSource int64 `json:"audioSource"`
}

// QuickLED 是 LED 字幕部分。
type QuickLED struct {
	Text        string  `json:"text"`
	Speed       int     `json:"speed"`
	LedMode     int     `json:"ledmode"`
	TerminalIDs []int64 `json:"terminalIds"`
}

// QuickTaskForm 是新建 / 修改快捷任务的入参，字段与 ok112 的
// set_task_quickplay.html 表单一一对应。
type QuickTaskForm struct {
	TaskName     string
	Key          int
	IsRandomPlay int
	Volume       int
	Priority     int
	// TimeLengthTy 1 = 按时长（秒），2 = 按循环次数
	TimeLengthTy int
	TimeLength   int
	// DataSendMode 0 = 单播，1 = 多播
	DataSendMode int
	MediaIDs     []int64
	TerminalIDs  []int64
	TTS          *QuickTTS
	LED          *QuickLED
}

var (
	ErrQuickTaskNotFound = errors.New("快捷任务不存在")
	// ErrQuickTaskUnsupported 终端型号不支持快捷任务。
	ErrQuickTaskUnsupported = errors.New("该终端型号不支持快捷任务")
	// ErrQuickKeyUsed 这台终端上这个键已经绑了别的快捷任务。
	ErrQuickKeyUsed = errors.New("这台终端上该键已经绑定了快捷任务")
)

const quickTaskLock = "htweb_terminal_quicktask"

// quickTypeText 把 tasktype 翻成界面上的说法。
func quickTypeText(t int) string {
	switch t {
	case QuickTypeTTS:
		return "文字播报"
	case QuickTypeTTSSrv:
		return "文字播报（服务器）"
	default:
		return "媒体播放"
	}
}

// ListQuickTasks 列出一台终端的快捷任务。
//
// 过滤条件照 ok112：只认 tasktype IN (20,21,29) 且 cmdargs 指向本终端的任务。
// 不加这两条的话，任何被绑过的任务都会冒出来 —— 那不是这个功能的语义。
func (s *Service) ListQuickTasks(ctx context.Context, terminalID int64) ([]QuickTask, error) {
	typeID, isEncode, termName, err := s.terminalType(ctx, terminalID)
	if err != nil {
		return nil, err
	}

	rs, err := s.db.QueryContext(ctx, `
		SELECT m.keyid, t.taskid, COALESCE(t.taskname,''),
		       COALESCE(t.timelength,0), COALESCE(t.timelengthtype,1),
		       COALESCE(t.priority,0), COALESCE(t.defaultvolume,0),
		       COALESCE(t.israndomplay,0), COALESCE(t.datasendmodel,0),
		       COALESCE(t.tasktype,0)
		FROM terminalkeymaptask m
		JOIN task t ON t.taskid = m.taskid
		WHERE m.terminalid = ?
		  AND t.cmdargs = ?
		  AND t.tasktype IN (?,?,?)
		ORDER BY m.keyid`,
		terminalID, fmt.Sprint(terminalID),
		QuickTypeMedia, QuickTypeTTS, QuickTypeTTSSrv)
	if err != nil {
		return nil, fmt.Errorf("查询快捷任务: %w", err)
	}
	defer rs.Close()

	out := []QuickTask{}
	for rs.Next() {
		var q QuickTask
		if err := rs.Scan(&q.Key, &q.TaskID, &q.TaskName,
			&q.TimeLength, &q.TimeLengthTy, &q.Priority, &q.Volume,
			&q.IsRandomPlay, &q.DataSendMode, &q.TaskType); err != nil {
			return nil, err
		}
		q.KeyLabel = labelFor(typeID, isEncode, false, q.Key)
		q.TypeText = quickTypeText(q.TaskType)
		q.TerminalName = termName
		out = append(out, q)
	}
	return out, rs.Err()
}

// GetQuickTask 取一条快捷任务的全部内容，供「修改」表单回填。
func (s *Service) GetQuickTask(ctx context.Context, terminalID, taskID int64) (*QuickTaskDetail, error) {
	list, err := s.ListQuickTasks(ctx, terminalID)
	if err != nil {
		return nil, err
	}
	var head *QuickTask
	for i := range list {
		if list[i].TaskID == taskID {
			head = &list[i]
			break
		}
	}
	if head == nil {
		return nil, ErrQuickTaskNotFound
	}

	out := &QuickTaskDetail{QuickTask: *head, MediaIDs: []int64{},
		Media: []QuickMedia{}, TerminalIDs: []int64{}}

	// 目标终端
	trs, err := s.db.QueryContext(ctx,
		`SELECT terminalid FROM terminaloftask WHERE taskid = ? ORDER BY id`, taskID)
	if err != nil {
		return nil, fmt.Errorf("查询任务终端: %w", err)
	}
	for trs.Next() {
		var id int64
		if err := trs.Scan(&id); err != nil {
			trs.Close()
			return nil, err
		}
		out.TerminalIDs = append(out.TerminalIDs, id)
	}
	trs.Close()

	if head.TaskType == QuickTypeMedia {
		// 普通快捷任务：媒体就是用户挑的那些文件
		mrs, err := s.db.QueryContext(ctx, `
			SELECT mt.mediaid, COALESCE(md.name,''), COALESCE(mt.sort,0)
			FROM mediaoftask mt
			LEFT JOIN media md ON md.id = mt.mediaid
			WHERE mt.taskid = ? ORDER BY mt.sort`, taskID)
		if err != nil {
			return nil, fmt.Errorf("查询任务媒体: %w", err)
		}
		defer mrs.Close()
		for mrs.Next() {
			var m QuickMedia
			if err := mrs.Scan(&m.MediaID, &m.Name, &m.Sort); err != nil {
				return nil, err
			}
			out.Media = append(out.Media, m)
			out.MediaIDs = append(out.MediaIDs, m.MediaID)
		}
		if err := mrs.Err(); err != nil {
			return nil, err
		}
		if err := s.fillQuickLED(ctx, out, taskID); err != nil {
			return nil, err
		}
		return out, nil
	}

	// 文字播报：内容在 ttssentence 里，按 mediaseq 拼回一整段
	tts := &QuickTTS{}
	var cmd sql.NullInt64
	if err := s.db.QueryRowContext(ctx,
		`SELECT cmd FROM task WHERE taskid = ?`, taskID).Scan(&cmd); err != nil {
		return nil, fmt.Errorf("查询音源: %w", err)
	}
	tts.AudioSource = cmd.Int64

	srs, err := s.db.QueryContext(ctx, `
		SELECT COALESCE(ts.content,''), COALESCE(ts.speed,5), COALESCE(ts.male,0)
		FROM ttssentence ts
		WHERE ts.sentenceid IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)
		ORDER BY ts.mediaseq`, taskID)
	if err != nil {
		return nil, fmt.Errorf("查询播报文字: %w", err)
	}
	defer srs.Close()
	var parts []string
	for srs.Next() {
		var content string
		var speed, male int
		if err := srs.Scan(&content, &speed, &male); err != nil {
			return nil, err
		}
		parts = append(parts, content)
		tts.Speed, tts.MusicMode = speed, male
	}
	if err := srs.Err(); err != nil {
		return nil, err
	}
	// 服务器音源写库时语速被 ×10，回填要还原成表单里填的数
	if head.TaskType == QuickTypeTTSSrv && ttsSpeedScaleOnServer > 0 {
		tts.Speed /= ttsSpeedScaleOnServer
	}
	tts.Text = strings.Join(parts, "")
	out.TTS = tts
	if err := s.fillQuickLED(ctx, out, taskID); err != nil {
		return nil, err
	}
	return out, nil
}

// fillQuickLED 回填 LED 字幕：修改弹窗要照原样显示字幕与速度。
// 没挂字幕时保持 out.LED = nil。
func (s *Service) fillQuickLED(ctx context.Context, out *QuickTaskDetail, taskID int64) error {
	var ledID int64
	err := s.db.QueryRowContext(ctx,
		`SELECT taskid FROM task WHERE sec_task_id = ? AND tasktype = ? LIMIT 1`,
		taskID, QuickLEDType).Scan(&ledID)
	if errors.Is(err, sql.ErrNoRows) {
		return nil
	}
	if err != nil {
		return fmt.Errorf("查询 LED 子任务: %w", err)
	}
	led := &QuickLED{TerminalIDs: []int64{}}
	err = s.db.QueryRowContext(ctx, `
		SELECT COALESCE(ls.text,''), COALESCE(ls.speed,0), COALESCE(ls.ledmode,0)
		FROM mediaoftask mt JOIN ledsentence ls ON ls.mediaid = mt.mediaid
		WHERE mt.taskid = ? ORDER BY ls.mediaseq, ls.id LIMIT 1`, ledID).
		Scan(&led.Text, &led.Speed, &led.LedMode)
	if err != nil && !errors.Is(err, sql.ErrNoRows) {
		return fmt.Errorf("查询 LED 字幕: %w", err)
	}
	rows, err := s.db.QueryContext(ctx,
		`SELECT terminalid FROM ledoftask WHERE taskid = ?`, ledID)
	if err != nil {
		return fmt.Errorf("查询 LED 终端: %w", err)
	}
	defer rows.Close()
	for rows.Next() {
		var id int64
		if err := rows.Scan(&id); err != nil {
			return err
		}
		led.TerminalIDs = append(led.TerminalIDs, id)
	}
	if err := rows.Err(); err != nil {
		return err
	}
	out.LED = led
	return nil
}

// CreateQuickTask 新建一条快捷任务并绑到键上。
//
// 写入顺序照 ok112 的 set_task_quick_play：
//
//	task → terminalkeymaptask → terminaloftask → (TTS: media + mediaoftask +
//	ttssentence | 普通: mediaoftask) → LED 子任务
//
// 整段在一个事务里。ok112 是逐条 query 加零散的 ROLLBACK 判断，中途失败会
// 留下半条任务（任务建了但没绑键、或绑了键却没有媒体），这里不重复那个问题。
func (s *Service) CreateQuickTask(ctx context.Context, u *auth.User,
	terminalID int64, in QuickTaskForm) (int64, error) {

	if err := s.guardQuickTask(ctx, u, terminalID, in.Key); err != nil {
		return 0, err
	}

	unlock, err := store.Lock(ctx, s.db, quickTaskLock)
	if err != nil {
		return 0, err
	}
	defer unlock()

	// 同一台终端同一个键只能有一条快捷任务（主键就是这么定的）。
	// 先查一遍是为了给出「这个键已经用了」这种能看懂的话，
	// 而不是让主键冲突以 500 的形式冒出来。
	var used int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM terminalkeymaptask WHERE terminalid = ? AND keyid = ?`,
		terminalID, in.Key).Scan(&used); err != nil {
		return 0, fmt.Errorf("检查键值占用: %w", err)
	}
	if used > 0 {
		return 0, ErrQuickKeyUsed
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	taskID, err := s.writeQuickTask(ctx, tx, u, terminalID, 0, in)
	if err != nil {
		return 0, err
	}
	if _, err := tx.ExecContext(ctx,
		`INSERT INTO terminalkeymaptask (keyid, terminalid, taskid) VALUES (?,?,?)`,
		in.Key, terminalID, taskID); err != nil {
		return 0, fmt.Errorf("绑定快捷键: %w", err)
	}
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return taskID, nil
}

// UpdateQuickTask 改一条快捷任务。
//
// 键值也可以改：主键是 (keyid, terminalid)，换键就是把绑定行的 keyid 改掉。
func (s *Service) UpdateQuickTask(ctx context.Context, u *auth.User,
	terminalID, taskID int64, in QuickTaskForm) error {

	if err := s.guardQuickTask(ctx, u, terminalID, in.Key); err != nil {
		return err
	}
	// 必须确实是这台终端的快捷任务，不能拿别的 taskid 混进来
	if _, err := s.GetQuickTask(ctx, terminalID, taskID); err != nil {
		return err
	}

	unlock, err := store.Lock(ctx, s.db, quickTaskLock)
	if err != nil {
		return err
	}
	defer unlock()

	var used int
	if err := s.db.QueryRowContext(ctx, `
		SELECT COUNT(*) FROM terminalkeymaptask
		WHERE terminalid = ? AND keyid = ? AND taskid <> ?`,
		terminalID, in.Key, taskID).Scan(&used); err != nil {
		return fmt.Errorf("检查键值占用: %w", err)
	}
	if used > 0 {
		return ErrQuickKeyUsed
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	if err := clearQuickTaskParts(ctx, tx, taskID); err != nil {
		return err
	}
	if _, err := s.writeQuickTask(ctx, tx, u, terminalID, taskID, in); err != nil {
		return err
	}
	if _, err := tx.ExecContext(ctx,
		`UPDATE terminalkeymaptask SET keyid = ? WHERE terminalid = ? AND taskid = ?`,
		in.Key, terminalID, taskID); err != nil {
		return fmt.Errorf("更新快捷键绑定: %w", err)
	}
	return tx.Commit()
}

// DeleteQuickTasks 删掉若干条快捷任务，连同它们建出来的一切。
//
// 顺序照 ok112 的 del_quick_task：LED 子任务 → TTS 媒体与语句 →
// mediaoftask → terminaloftask → task。
//
// ⚠ 多一步：ok112 **不删 terminalkeymaptask**，于是任务没了、键还绑着一个
//
//	不存在的 taskid，界面上就是一条点不动的空记录。这里一并删掉。
func (s *Service) DeleteQuickTasks(ctx context.Context, u *auth.User,
	terminalID int64, taskIDs []int64) (int, error) {

	ok, skipped, err := s.CheckBound(ctx, u, []int64{terminalID})
	if err != nil {
		return 0, err
	}
	if len(ok) == 0 {
		if len(skipped) > 0 {
			return 0, fmt.Errorf("%w: %s", ErrNoPermission, skipped[0].Detail)
		}
		return 0, ErrNotFound
	}

	// 只允许删本终端的快捷任务
	valid := map[int64]bool{}
	list, err := s.ListQuickTasks(ctx, terminalID)
	if err != nil {
		return 0, err
	}
	for _, q := range list {
		valid[q.TaskID] = true
	}
	var doable []int64
	for _, id := range taskIDs {
		if valid[id] {
			doable = append(doable, id)
		}
	}
	if len(doable) == 0 {
		return 0, ErrQuickTaskNotFound
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	for _, id := range doable {
		if err := clearQuickTaskParts(ctx, tx, id); err != nil {
			return 0, err
		}
		if _, err := tx.ExecContext(ctx,
			`DELETE FROM terminalkeymaptask WHERE taskid = ? AND terminalid = ?`,
			id, terminalID); err != nil {
			return 0, fmt.Errorf("解除快捷键绑定: %w", err)
		}
		if _, err := tx.ExecContext(ctx, `DELETE FROM task WHERE taskid = ?`, id); err != nil {
			return 0, fmt.Errorf("删除任务: %w", err)
		}
	}
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return len(doable), nil
}

// guardQuickTask 是新建与修改共用的前置校验。
func (s *Service) guardQuickTask(ctx context.Context, u *auth.User, terminalID int64, key int) error {
	ok, skipped, err := s.CheckBound(ctx, u, []int64{terminalID})
	if err != nil {
		return err
	}
	if len(ok) == 0 {
		if len(skipped) > 0 {
			return fmt.Errorf("%w: %s", ErrNoPermission, skipped[0].Detail)
		}
		return ErrNotFound
	}
	// 型号本身得支持快捷任务。前端会按 caps.quickTask 置灰菜单，
	// 但那只是提示 —— 直接调接口一样能进来，拦截必须在服务端做。
	if err := s.assertQuickTaskSupported(ctx, terminalID); err != nil {
		return err
	}
	// 键值必须是这个型号真有的（快捷任务用非急救那一套）
	return s.assertKeyValue(ctx, terminalID, key, false)
}

// writeQuickTask 写 task 主体与它的媒体 / 终端 / TTS / LED。
// taskID 为 0 表示新建，非 0 表示就地更新（调用方已先清过旧的附属数据）。
func (s *Service) writeQuickTask(ctx context.Context, tx *sql.Tx, u *auth.User,
	terminalID, taskID int64, in QuickTaskForm) (int64, error) {

	tasktype := QuickTypeMedia
	audioSource := int64(0)
	speed := 0
	if in.TTS != nil {
		tasktype = QuickTypeTTS
		audioSource = in.TTS.AudioSource
		speed = in.TTS.Speed
		// 音源是「服务器」时走 29，并把语速按旧版的倍数放大
		var srcType int
		if audioSource > 0 {
			if err := tx.QueryRowContext(ctx,
				`SELECT COALESCE(typeid,0) FROM terminal WHERE id = ?`, audioSource).Scan(&srcType); err != nil {
				if !errors.Is(err, sql.ErrNoRows) {
					return 0, fmt.Errorf("查询音源终端: %w", err)
				}
				srcType = serverAudioType
			}
		}
		if srcType == serverAudioType {
			tasktype = QuickTypeTTSSrv
			speed *= ttsSpeedScaleOnServer
		}
	}

	// 日期、时间、执行模式全是零值 —— 快捷任务由按键触发，不参与排程。
	// 这几列照 ok112 原样写死，别改成 NULL：旧版 C 服务读的时候不判空。
	const cols = `taskname, israndomplay, timelengthtype, timelength, prepower,
	              datasendmodel, state, startdate, enddate, playtime, endtime,
	              exemodel, priority, tasktype, channel, bandrate, samplerate,
	              cmd, cmdargs, playfileid, defaultvolume, task_user_id,
	              sec_task_id, parentid`
	args := []interface{}{
		in.TaskName, in.IsRandomPlay, in.TimeLengthTy, in.TimeLength, 0,
		in.DataSendMode, 0, "0000-00-00", "0000-00-00", "00:00:00", "00:00:00",
		"0000000", in.Priority, tasktype, 0, 0, 0,
		audioSource, fmt.Sprint(terminalID), 0, in.Volume, u.ID,
		0, 0,
	}

	if taskID == 0 {
		res, err := tx.ExecContext(ctx,
			`INSERT INTO task (`+cols+`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)`,
			args...)
		if err != nil {
			return 0, fmt.Errorf("创建任务: %w", err)
		}
		if taskID, err = res.LastInsertId(); err != nil {
			return 0, fmt.Errorf("读取任务 ID: %w", err)
		}
	} else {
		if _, err := tx.ExecContext(ctx, `
			UPDATE task SET taskname=?, israndomplay=?, timelengthtype=?, timelength=?,
			                datasendmodel=?, priority=?, tasktype=?, cmd=?, defaultvolume=?
			WHERE taskid = ?`,
			in.TaskName, in.IsRandomPlay, in.TimeLengthTy, in.TimeLength,
			in.DataSendMode, in.Priority, tasktype, audioSource, in.Volume,
			taskID); err != nil {
			return 0, fmt.Errorf("更新任务: %w", err)
		}
	}

	// 目标终端。groupid 由服务端按终端当前归属带上，不让前端传 ——
	// 旧版是前端把终端串和分区串按下标对齐，长度不一致就静默写 0。
	for _, id := range in.TerminalIDs {
		if _, err := tx.ExecContext(ctx, `
			INSERT INTO terminaloftask (taskid, terminalid, groupid, area)
			VALUES (?, ?, COALESCE((SELECT tog.groupid FROM terminalofgroup tog
			                         WHERE tog.terminalid = ? ORDER BY tog.id LIMIT 1), 0), ?)`,
			taskID, id, id, DefaultArea); err != nil {
			return 0, fmt.Errorf("写入任务终端: %w", err)
		}
	}

	if in.TTS != nil {
		if err := writeQuickTTS(ctx, tx, taskID, tasktype, in, speed); err != nil {
			return 0, err
		}
	} else {
		for i, mid := range in.MediaIDs {
			if _, err := tx.ExecContext(ctx,
				`INSERT INTO mediaoftask (mediaid, taskid, sort) VALUES (?,?,?)`,
				mid, taskID, i); err != nil {
				return 0, fmt.Errorf("写入任务媒体: %w", err)
			}
		}
	}

	if in.LED != nil && strings.TrimSpace(in.LED.Text) != "" {
		if err := writeQuickLED(ctx, tx, u, taskID, terminalID, in); err != nil {
			return 0, err
		}
	}
	return taskID, nil
}

// writeQuickTTS 建一条虚拟的 tts 媒体，再把文字切块写进 ttssentence。
//
// ⚠ media 那几列被旧版复用了：sample 存的是 taskid，bitrate 存的是 tasktype。
//
//	列名与含义无关，照写，别「顺手修正」—— C 服务是按这个约定读的。
func writeQuickTTS(ctx context.Context, tx *sql.Tx, taskID int64, tasktype int,
	in QuickTaskForm, speed int) error {

	res, err := tx.ExecContext(ctx, `
		INSERT INTO media (name, typeid, filename, folderid, timelength, channel, sample, bitrate)
		VALUES (?, 'tts', 'tts', 0, 0, 0, ?, ?)`,
		in.TaskName, taskID, tasktype)
	if err != nil {
		return fmt.Errorf("创建播报媒体: %w", err)
	}
	mediaID, err := res.LastInsertId()
	if err != nil {
		return fmt.Errorf("读取媒体 ID: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`INSERT INTO mediaoftask (mediaid, taskid, sort) VALUES (?,?,0)`,
		mediaID, taskID); err != nil {
		return fmt.Errorf("关联播报媒体: %w", err)
	}

	// ⚠ sentenceid 存的是 mediaid，不是 ttssentence 自己的 id。type = 2 照旧版。
	for i, chunk := range splitTTSText(in.TTS.Text) {
		if _, err := tx.ExecContext(ctx, `
			INSERT INTO ttssentence (name, sentenceid, type, content, mediaseq, speed, volume, male)
			VALUES (?,?,2,?,?,?,?,?)`,
			in.TaskName, mediaID, chunk, i, speed, in.Volume, in.TTS.MusicMode); err != nil {
			return fmt.Errorf("写入播报文字: %w", err)
		}
	}
	return nil
}

// splitTTSText 按 ok112 的粒度把文本切成若干块，并清掉旧版会清的那些字符。
//
// 旧版 str_split_utf8 是按 1000 字节切，且切之前把 <br/>、\r\n、顿号、</b>、
// 反斜杠都替换掉。顿号被删这一条看着莫名其妙，但 TTS 主机对它的处理有问题，
// 旧版就是这么绕过去的，照做。
func splitTTSText(text string) []string {
	r := strings.NewReplacer(
		"<br/>", "", "<br />", "", "<br>", "",
		"\r\n", "", "\n", "", "、", "",
		"</b>", "", "</B>", "", `\`, "",
	)
	cleaned := strings.TrimSpace(r.Replace(text))
	if cleaned == "" {
		return nil
	}
	var out []string
	for len(cleaned) > ttsChunkBytes {
		// 只在 UTF-8 字符边界上断开，别把一个汉字劈成两半
		cut := ttsChunkBytes
		for cut > 0 && !utf8Start(cleaned[cut]) {
			cut--
		}
		if cut == 0 {
			cut = ttsChunkBytes
		}
		out = append(out, cleaned[:cut])
		cleaned = cleaned[cut:]
	}
	if cleaned != "" {
		out = append(out, cleaned)
	}
	return out
}

// utf8Start 判断一个字节是不是 UTF-8 字符的首字节。
func utf8Start(b byte) bool { return b&0xC0 != 0x80 }

// writeQuickLED 建挂在这条快捷任务下的 LED 字幕子任务。
//
// LED 不改主任务的 tasktype，它是**另建一条** tasktype = 30 的子任务，
// 用 sec_task_id 指回主任务 —— 与文件广播那边的 LED 子任务是同一套结构。
func writeQuickLED(ctx context.Context, tx *sql.Tx, u *auth.User,
	taskID, terminalID int64, in QuickTaskForm) error {

	res, err := tx.ExecContext(ctx, `
		INSERT INTO task (taskname, israndomplay, timelengthtype, timelength, prepower,
		                  datasendmodel, state, startdate, enddate, playtime, endtime,
		                  exemodel, priority, tasktype, channel, bandrate, samplerate,
		                  cmd, cmdargs, playfileid, defaultvolume, task_user_id,
		                  sec_task_id, parentid)
		VALUES (?,?,?,?,0,?,0,'0000-00-00','0000-00-00','00:00:00','00:00:00',
		        '0000000',?,30,0,0,0,0,?,0,?,?,?,0)`,
		in.TaskName, in.IsRandomPlay, in.TimeLengthTy, in.TimeLength,
		in.DataSendMode, in.Priority, fmt.Sprint(terminalID), in.Volume, u.ID, taskID)
	if err != nil {
		return fmt.Errorf("创建 LED 子任务: %w", err)
	}
	ledTaskID, err := res.LastInsertId()
	if err != nil {
		return fmt.Errorf("读取 LED 子任务 ID: %w", err)
	}

	mres, err := tx.ExecContext(ctx, `
		INSERT INTO media (name, typeid, filename, folderid, timelength, channel, sample, bitrate)
		VALUES (?, 'led', 'led', 0, 0, 0, ?, 30)`, in.TaskName, ledTaskID)
	if err != nil {
		return fmt.Errorf("创建 LED 媒体: %w", err)
	}
	ledMediaID, err := mres.LastInsertId()
	if err != nil {
		return fmt.Errorf("读取 LED 媒体 ID: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`INSERT INTO mediaoftask (mediaid, taskid, sort) VALUES (?,?,0)`,
		ledMediaID, ledTaskID); err != nil {
		return fmt.Errorf("关联 LED 媒体: %w", err)
	}
	// ⚠ ledsentence 的列是 (id, text, mediaid, speed, type, mediaseq, ledmode)：
	// 没有 name，正文列叫 text 不叫 content。写错列名的话这条 INSERT 直接报
	// 1054 Unknown column，带 LED 字幕的快捷任务一条都存不进去。
	if _, err := tx.ExecContext(ctx, `
		INSERT INTO ledsentence (text, mediaid, speed, type, mediaseq, ledmode)
		VALUES (?,?,?,1,0,?)`,
		task.NormalizeLEDText(in.LED.Text), ledMediaID, in.LED.Speed, in.LED.LedMode); err != nil {
		return fmt.Errorf("写入 LED 字幕: %w", err)
	}
	for _, id := range in.LED.TerminalIDs {
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO ledoftask (taskid, terminalid) VALUES (?,?)`,
			ledTaskID, id); err != nil {
			return fmt.Errorf("写入 LED 终端: %w", err)
		}
	}
	return nil
}

// clearQuickTaskParts 清掉一条快捷任务的全部附属数据（不含 task 行本身）。
//
// 顺序要紧：先顺着 mediaoftask 找到 mediaid 去删 ttssentence / ledsentence / media，
// 最后才删 mediaoftask —— 反过来就找不到该删哪些了。
func clearQuickTaskParts(ctx context.Context, tx *sql.Tx, taskID int64) error {
	// LED 子任务（sec_task_id 指回主任务）
	lrs, err := tx.QueryContext(ctx,
		`SELECT taskid FROM task WHERE sec_task_id = ? AND tasktype IN (30,24)`, taskID)
	if err != nil {
		return fmt.Errorf("查询 LED 子任务: %w", err)
	}
	var ledIDs []int64
	for lrs.Next() {
		var id int64
		if err := lrs.Scan(&id); err != nil {
			lrs.Close()
			return err
		}
		ledIDs = append(ledIDs, id)
	}
	lrs.Close()
	if err := lrs.Err(); err != nil {
		return err
	}
	for _, lid := range ledIDs {
		if _, err := tx.ExecContext(ctx, `
			DELETE FROM ledsentence
			WHERE mediaid IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)`, lid); err != nil {
			return fmt.Errorf("清理 LED 字幕: %w", err)
		}
		if _, err := tx.ExecContext(ctx, `
			DELETE FROM media
			WHERE typeid = 'led' AND id IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)`, lid); err != nil {
			return fmt.Errorf("清理 LED 媒体: %w", err)
		}
		if _, err := tx.ExecContext(ctx, `DELETE FROM ledoftask WHERE taskid = ?`, lid); err != nil {
			return fmt.Errorf("清理 LED 终端: %w", err)
		}
		if _, err := tx.ExecContext(ctx, `DELETE FROM mediaoftask WHERE taskid = ?`, lid); err != nil {
			return fmt.Errorf("清理 LED 媒体关联: %w", err)
		}
		if _, err := tx.ExecContext(ctx, `DELETE FROM task WHERE taskid = ?`, lid); err != nil {
			return fmt.Errorf("删除 LED 子任务: %w", err)
		}
	}

	// TTS：虚拟媒体与语句
	if _, err := tx.ExecContext(ctx, `
		DELETE FROM ttssentence
		WHERE sentenceid IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)`, taskID); err != nil {
		return fmt.Errorf("清理播报文字: %w", err)
	}
	if _, err := tx.ExecContext(ctx, `
		DELETE FROM media
		WHERE typeid = 'tts' AND id IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)`, taskID); err != nil {
		return fmt.Errorf("清理播报媒体: %w", err)
	}
	if _, err := tx.ExecContext(ctx, `DELETE FROM mediaoftask WHERE taskid = ?`, taskID); err != nil {
		return fmt.Errorf("清理任务媒体: %w", err)
	}
	if _, err := tx.ExecContext(ctx, `DELETE FROM terminaloftask WHERE taskid = ?`, taskID); err != nil {
		return fmt.Errorf("清理任务终端: %w", err)
	}
	return nil
}

// QuickAudioSource 是「音源」下拉里的一项。
type QuickAudioSource struct {
	ID     int64  `json:"id"`
	Name   string `json:"name"`
	TypeID int    `json:"typeId"`
	// IsServer 为 true 时任务类型走 29，且语速要按倍数放大。
	IsServer bool `json:"isServer"`
}

// QuickAudioSources 列出可选音源。
//
// 候选集照 ok112 的 setquickplay.php：typeid IN (22, 32, 0)，
// 也就是两种 TTS 主机加上「服务器」本身。
func (s *Service) QuickAudioSources(ctx context.Context) ([]QuickAudioSource, error) {
	rs, err := s.db.QueryContext(ctx, `
		SELECT id, COALESCE(terminalname,''), COALESCE(typeid,0)
		FROM terminal WHERE typeid IN (22, 32, 0) ORDER BY typeid, id`)
	if err != nil {
		return nil, fmt.Errorf("查询音源终端: %w", err)
	}
	defer rs.Close()
	out := []QuickAudioSource{}
	for rs.Next() {
		var a QuickAudioSource
		if err := rs.Scan(&a.ID, &a.Name, &a.TypeID); err != nil {
			return nil, err
		}
		a.IsServer = a.TypeID == serverAudioType
		out = append(out, a)
	}
	return out, rs.Err()
}

// assertQuickTaskSupported 校验终端型号是否在快捷任务的白名单里。
//
// 判据是 caps.QuickTask（terminal/caps.go 的 quickTaskAllow，抄自 ok112 的
// get_terminal_type）。前端拿 caps 置灰菜单，这里是同一套规则的服务端复检 ——
// 少了它，绕过界面直接调接口就能给不支持的型号建快捷任务，
// 建完还查不出来：按键在设备上根本没有响应，界面里却明明白白列着一条。
func (s *Service) assertQuickTaskSupported(ctx context.Context, terminalID int64) error {
	var tr TypeTraits
	var dec, enc, lcd, spk int
	var name string
	if err := s.db.QueryRowContext(ctx, `
		SELECT tt.id, COALESCE(tt.isdecode,0), COALESCE(tt.isencode,0),
		       COALESCE(tt.isLCD,0), COALESCE(tt.isspeech,0),
		       COALESCE(tt.shortkeycount,0), COALESCE(tt.switchcount,0),
		       COALESCE(t.terminalname,'')
		FROM terminal t JOIN terminaltype tt ON tt.id = t.typeid
		WHERE t.id = ?`, terminalID).
		Scan(&tr.TypeID, &dec, &enc, &lcd, &spk, &tr.ShortKeyCount, &tr.SwitchCount, &name); err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return ErrNotFound
		}
		return fmt.Errorf("查询终端类型: %w", err)
	}
	tr.IsDecode, tr.IsEncode, tr.IsLCD, tr.IsSpeech = dec == 1, enc == 1, lcd >= 1, spk == 1
	if !CapsOf(tr).QuickTask {
		return fmt.Errorf("%w：终端「%s」（类型 %d）", ErrQuickTaskUnsupported, name, tr.TypeID)
	}
	return nil
}
