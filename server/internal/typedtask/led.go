package typedtask

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"net"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/store"
)

// LED 播放。
//
// # 表怎么串（以现网数据为准，2026-08 实测）
//
//	task        tasktype = 30, parentid = ledtaskfree.id（分组）
//	  └ mediaoftask (taskid, mediaid)             ← mediaid 是 **media.id**
//	      └ media (typeid='tts', sample = taskid) ← 一条虚拟媒体，和文字语音同一套路
//	          └ ledsentence (mediaid = media.id, text, speed, type, mediaseq, ledmode)
//	  └ ledoftask (taskid, terminalid, deviceid)  ← 这条任务发到哪些 LED 屏
//	ledtaskfree (id, name, parentid, userid)      ← 任务分组
//	leddevice   (id, terminalid, devid, name, ip, width, height, sendport, ...)
//
// ⚠ 这里踩过一个坑，记下来免得再犯（新版缺陷 N-20）：
//
//	曾经以为 `mediaoftask.mediaid` 存的是 `ledsentence.id`，于是用
//	`ledsentence.id IN (SELECT mediaid FROM mediaoftask ...)` 去取文字。
//	实测不成立 —— 现网 mediaoftask.mediaid 是 59/63/67/68，
//	而 ledsentence.id 只有 1/2/3/4；59~68 是 media 表里那几条 typeid='tts' 的行，
//	`ledsentence.mediaid` 也正是它们。所以关联键是 **ledsentence.mediaid = mediaoftask.mediaid**。
//	用错的后果是 LED 文字永远读不出来，编辑弹窗一片空白，保存还会留下孤儿行。
//
// ⚠ 第二个坑（N-21）：类型号。曾经按 24 查，现网**一条都没有**，
//	这一页因此永远是空的。实际数据全是 tasktype = 30：
//
//	taskid 70009 sec_task_id=70007（挂在「早读」上）
//	taskid 70035 sec_task_id=70033（挂在文字语音任务上）
//	taskid 70020 sec_task_id=0     （独立的 LED 任务）
//	taskid 70024 sec_task_id=0     （独立的 LED 任务）
//
//	也就是说 24 / 30 并不是「独立 / 附属」之分，两者都用 30，
//	**区分独立与附属的是 `sec_task_id` 是不是 0**。
//	本页列 24 与 30 里 sec_task_id = 0 的那些（24 兼容可能存在的旧数据）；
//	sec_task_id ≠ 0 的属于别的任务，在那条任务的表单里改，不在这里单独删。

type LEDDetail struct {
	SentenceID int64     `json:"sentenceId"`
	Text       string    `json:"text"`
	Speed      int       `json:"speed"`
	LedMode    int       `json:"ledmode"`
	ModeText   string    `json:"modeText"`
	Devices    []LEDBind `json:"devices"`
}

type LEDBind struct {
	TerminalID   int64  `json:"terminalId"`
	TerminalName string `json:"terminalname"`
	DeviceID     int64  `json:"deviceId"`
	DeviceName   string `json:"deviceName"`
	Deleted      bool   `json:"deleted"`
}

type LEDInput struct {
	Text    string    `json:"text"`
	Speed   int       `json:"speed"`
	LedMode int       `json:"ledmode"`
	Devices []LEDBind `json:"devices"`
}

const (
	// ledsentence.text 是 varchar(1024)
	ledTextLimit = 1024
	ledSpeedMin  = 0
	ledSpeedMax  = 10
	ledModeMax   = 10
	maxLEDBinds  = 500
	// ledsentence.type：跟 ttssentence 一样的三态，现网四条全是 1。
	// 1 = 约定文字/文本上屏，这里固定写 1，与现网数据一致。
	ledSentenceType = 1
	// ledSubType 是 LED 任务的 tasktype。独立与附属都是 30，靠 sec_task_id 区分。
	ledSubType = 30
)

func ledModeText(v int) string {
	// ledmode 没有列注释，现网全是 0。这里只做「0 = 默认」的最小翻译，
	// 不编造其它取值的含义 —— 猜一个显示出来比不显示更糟。
	if v == 0 {
		return "默认"
	}
	return fmt.Sprintf("模式 %d", v)
}

func validateLED(in *LEDInput) error {
	if in == nil {
		return fmt.Errorf("请填写 LED 播放内容")
	}
	in.Text = strings.TrimSpace(in.Text)
	if in.Text == "" {
		return fmt.Errorf("LED 显示文字不能为空")
	}
	if len(in.Text) > ledTextLimit {
		return fmt.Errorf("LED 显示文字过长：按 UTF-8 计 %d 字节，上限 %d 字节（约 341 个汉字）",
			len(in.Text), ledTextLimit)
	}
	if in.Speed < ledSpeedMin || in.Speed > ledSpeedMax {
		return fmt.Errorf("滚动速度必须在 %d ~ %d 之间", ledSpeedMin, ledSpeedMax)
	}
	if in.LedMode < 0 || in.LedMode > ledModeMax {
		return fmt.Errorf("显示模式必须在 0 ~ %d 之间", ledModeMax)
	}
	if len(in.Devices) > maxLEDBinds {
		return fmt.Errorf("最多绑定 %d 块 LED 屏", maxLEDBinds)
	}
	seen := map[string]bool{}
	for _, d := range in.Devices {
		if d.TerminalID <= 0 || d.DeviceID <= 0 {
			return fmt.Errorf("LED 屏列表里有非法的绑定项")
		}
		key := fmt.Sprintf("%d-%d", d.TerminalID, d.DeviceID)
		if seen[key] {
			return fmt.Errorf("LED 屏列表里有重复项")
		}
		seen[key] = true
	}
	return nil
}

func (s *Service) taskLED(ctx context.Context, taskID int64) (*LEDDetail, error) {
	d := &LEDDetail{Devices: []LEDBind{}}
	// ⚠ 关联键是 ledsentence.mediaid = mediaoftask.mediaid（两边都是 media.id），
	//    不是 ledsentence.id。理由见文件头 N-20。
	err := s.db.QueryRowContext(ctx, `
		SELECT ls.id, COALESCE(ls.text,''), COALESCE(ls.speed,0), COALESCE(ls.ledmode,0)
		FROM ledsentence ls
		WHERE ls.mediaid IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)
		ORDER BY ls.id LIMIT 1`, taskID).
		Scan(&d.SentenceID, &d.Text, &d.Speed, &d.LedMode)
	if err != nil && !errors.Is(err, sql.ErrNoRows) {
		return nil, fmt.Errorf("查询 LED 文本: %w", err)
	}
	d.ModeText = ledModeText(d.LedMode)

	rs, err := s.db.QueryContext(ctx, `
		SELECT lt.terminalid, COALESCE(t.terminalname,''), lt.deviceid,
		       COALESCE(ld.name,''), ld.id IS NOT NULL
		FROM ledoftask lt
		LEFT JOIN terminal t ON t.id = lt.terminalid
		LEFT JOIN leddevice ld ON ld.id = lt.deviceid
		WHERE lt.taskid = ?
		ORDER BY lt.id`, taskID)
	if err != nil {
		return nil, fmt.Errorf("查询 LED 屏绑定: %w", err)
	}
	defer rs.Close()
	for rs.Next() {
		var b LEDBind
		var exists bool
		if err := rs.Scan(&b.TerminalID, &b.TerminalName, &b.DeviceID, &b.DeviceName, &exists); err != nil {
			return nil, err
		}
		b.Deleted = !exists
		if b.Deleted {
			b.DeviceName = "(LED 设备已删除)"
		}
		d.Devices = append(d.Devices, b)
	}
	return d, rs.Err()
}

func (s *Service) fillLEDText(ctx context.Context, items []Item, ids []int64) error {
	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx, `
		SELECT mt.taskid, COALESCE(ls.text,'')
		FROM mediaoftask mt
		JOIN ledsentence ls ON ls.mediaid = mt.mediaid
		WHERE mt.taskid IN (`+ph+`)
		ORDER BY mt.taskid, ls.id`, args...)
	if err != nil {
		return fmt.Errorf("查询 LED 文本: %w", err)
	}
	defer rs.Close()
	first := map[int64]string{}
	for rs.Next() {
		var id int64
		var t string
		if err := rs.Scan(&id, &t); err != nil {
			return err
		}
		if _, ok := first[id]; !ok {
			first[id] = t
		}
	}
	if err := rs.Err(); err != nil {
		return err
	}
	for i := range items {
		items[i].Text = first[items[i].TaskID]
	}
	return nil
}

// writeLED 与 writeSentences 同构：先建一条 typeid='tts' 的虚拟媒体，
// 再把 LED 文字挂到这条媒体上。
//
// ⚠ 顺序不能反，也不能省掉 media 那一步：
//
//	ledsentence.mediaid 存的是 media.id（现网 59/63/67/68），
//	mediaoftask.mediaid 存的是同一个 media.id。
//	以前这里往 ledsentence.mediaid 写 0、往 mediaoftask.mediaid 写 ledsentence.id，
//	写出来的数据和旧系统不同构，后台按 media.id 去找就找不到（N-20）。
//
// media.sample 同样写 taskID —— 与文字语音那条链路一致，后台靠它反查任务。
func writeLED(ctx context.Context, tx *sql.Tx, taskID int64, in Input) error {
	led := in.LED
	res, err := tx.ExecContext(ctx,
		`INSERT INTO media (name, size, typeid, priority, filename, folderid, timelength, channel, sample, bitrate)
		 VALUES (?,?,?,?,?,?,?,?,?,?)`,
		in.TaskName, 0, "tts", 0, "tts", 0, 0, 0, taskID, 0)
	if err != nil {
		return fmt.Errorf("新建 LED 媒体: %w", err)
	}
	mediaID, err := res.LastInsertId()
	if err != nil {
		return err
	}
	if _, err := tx.ExecContext(ctx,
		`INSERT INTO mediaoftask (mediaid, taskid, sort) VALUES (?,?,?)`,
		mediaID, taskID, 1); err != nil {
		return fmt.Errorf("绑定 LED 媒体: %w", err)
	}
	// mediaseq 写 0，与现网四条既有数据一致
	if _, err := tx.ExecContext(ctx,
		`INSERT INTO ledsentence (text, mediaid, speed, type, mediaseq, ledmode) VALUES (?,?,?,?,?,?)`,
		led.Text, mediaID, led.Speed, ledSentenceType, 0, led.LedMode); err != nil {
		return fmt.Errorf("写入 LED 文本: %w", err)
	}
	for _, d := range led.Devices {
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO ledoftask (taskid, terminalid, deviceid) VALUES (?,?,?)`,
			taskID, d.TerminalID, d.DeviceID); err != nil {
			return fmt.Errorf("绑定 LED 屏: %w", err)
		}
	}
	return nil
}

// clearLED 顺序要紧：先按 mediaoftask 找到 mediaid，删 ledsentence 与 media，
// 最后才删 mediaoftask —— 反过来就找不到该删哪些了（与 clearSentences 同一套路）。
func clearLED(ctx context.Context, tx *sql.Tx, taskID int64) error {
	if _, err := tx.ExecContext(ctx, `
		DELETE FROM ledsentence
		WHERE mediaid IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)`, taskID); err != nil {
		return fmt.Errorf("清理 LED 文本: %w", err)
	}
	if _, err := tx.ExecContext(ctx, `
		DELETE FROM media
		WHERE typeid = 'tts' AND id IN (SELECT mediaid FROM mediaoftask WHERE taskid = ?)`,
		taskID); err != nil {
		return fmt.Errorf("清理 LED 媒体: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM mediaoftask WHERE taskid = ?`, taskID); err != nil {
		return fmt.Errorf("清理 LED 媒体绑定: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM ledoftask WHERE taskid = ?`, taskID); err != nil {
		return fmt.Errorf("清理 LED 屏绑定: %w", err)
	}
	return nil
}

// ---------- LED 任务分组（ledtaskfree）----------

type LEDFolder struct {
	ID        int64  `json:"id"`
	Name      string `json:"name"`
	ParentID  int64  `json:"parentid"`
	UserID    int64  `json:"userId"`
	TaskCount int    `json:"taskCount"`
}

// LEDFolders 列出 LED 任务分组。
//
// `ledtaskfree` 有 parentid 列，理论上是棵树，但旧版
// `ledtaskmanager.php` 只按 `userid` 平铺列出、从不递归。
// 现网也只有一条 parentid=0 的记录。这里保持平铺，把 parentid 原样带出去，
// 将来真要做成树也不用改表。
func (s *Service) LEDFolders(ctx context.Context, u *auth.User) ([]LEDFolder, error) {
	cond := &store.Cond{}
	if !u.IsAdmin {
		cond.Add(`f.userid = ?`, u.ID)
	}
	rs, err := s.db.QueryContext(ctx, `
		SELECT f.id, COALESCE(f.name,''), COALESCE(f.parentid,0), COALESCE(f.userid,0),
		       (SELECT COUNT(*) FROM task t
		         WHERE t.parentid = f.id AND t.tasktype IN (24,30) AND COALESCE(t.sec_task_id,0) = 0)
		FROM ledtaskfree f`+cond.Where()+` ORDER BY f.id`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询 LED 任务分组: %w", err)
	}
	defer rs.Close()
	out := []LEDFolder{}
	for rs.Next() {
		var f LEDFolder
		if err := rs.Scan(&f.ID, &f.Name, &f.ParentID, &f.UserID, &f.TaskCount); err != nil {
			return nil, err
		}
		out = append(out, f)
	}
	return out, rs.Err()
}

// ---------- LED 设备（leddevice）----------

type LEDDevice struct {
	ID           int64  `json:"id"`
	Name         string `json:"name"`
	TerminalID   int64  `json:"terminalId"`
	TerminalName string `json:"terminalname"`
	DevID        int64  `json:"devid"`
	IP           string `json:"ip"`
	Width        int    `json:"width"`
	Height       int    `json:"height"`
	SendPort     int    `json:"sendport"`
	MAC          string `json:"mac"`
	DefaultText  string `json:"defaulttext"`
}

func (s *Service) LEDDevices(ctx context.Context, keyword string) ([]LEDDevice, error) {
	cond := &store.Cond{}
	if keyword = strings.TrimSpace(keyword); keyword != "" {
		kw := store.EscapeLike(keyword)
		cond.Add(`(d.name LIKE ? ESCAPE '\\' OR d.ip LIKE ? ESCAPE '\\')`, kw, kw)
	}
	rs, err := s.db.QueryContext(ctx, `
		SELECT d.id, COALESCE(d.name,''), COALESCE(d.terminalid,0), COALESCE(t.terminalname,''),
		       COALESCE(d.devid,0), COALESCE(d.ip,''), COALESCE(d.width,0), COALESCE(d.height,0),
		       COALESCE(d.sendport,0), COALESCE(d.mac,''), COALESCE(d.defaulttext,'')
		FROM leddevice d
		LEFT JOIN terminal t ON t.id = d.terminalid`+cond.Where()+`
		ORDER BY d.id LIMIT 500`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询 LED 设备: %w", err)
	}
	defer rs.Close()
	out := []LEDDevice{}
	for rs.Next() {
		var d LEDDevice
		if err := rs.Scan(&d.ID, &d.Name, &d.TerminalID, &d.TerminalName,
			&d.DevID, &d.IP, &d.Width, &d.Height, &d.SendPort, &d.MAC, &d.DefaultText); err != nil {
			return nil, err
		}
		out = append(out, d)
	}
	return out, rs.Err()
}

type LEDDeviceInput struct {
	Name        string
	TerminalID  int64
	DevID       int64
	IP          string
	Width       int
	Height      int
	SendPort    int
	MAC         string
	DefaultText string
}

func (in *LEDDeviceInput) validate() error {
	in.Name = strings.TrimSpace(in.Name)
	in.IP = strings.TrimSpace(in.IP)
	in.MAC = strings.TrimSpace(in.MAC)
	in.DefaultText = strings.TrimSpace(in.DefaultText)

	if in.Name == "" {
		return fmt.Errorf("LED 设备名称不能为空")
	}
	if len(in.Name) > 64 {
		return fmt.Errorf("LED 设备名称过长：按 UTF-8 计 %d 字节，上限 64 字节", len(in.Name))
	}
	// leddevice.ip 是 varchar(16) —— 刚好装得下一个点分十进制 IPv4，多一个字符就截断
	if ip := net.ParseIP(in.IP); ip == nil || ip.To4() == nil {
		return fmt.Errorf("IP 地址格式不正确，必须是 IPv4")
	}
	if in.TerminalID <= 0 {
		return fmt.Errorf("请选择 LED 设备挂在哪台终端下")
	}
	if in.Width <= 0 || in.Width > 4096 {
		return fmt.Errorf("屏宽必须在 1 ~ 4096 之间")
	}
	if in.Height <= 0 || in.Height > 4096 {
		return fmt.Errorf("屏高必须在 1 ~ 4096 之间")
	}
	if in.SendPort < 0 || in.SendPort > 65535 {
		return fmt.Errorf("发送端口必须在 0 ~ 65535 之间")
	}
	if len(in.MAC) > 64 {
		return fmt.Errorf("MAC 过长，上限 64 字节")
	}
	if len(in.DefaultText) > 1024 {
		return fmt.Errorf("默认显示文字过长：按 UTF-8 计 %d 字节，上限 1024 字节", len(in.DefaultText))
	}
	return nil
}

func (s *Service) CreateLEDDevice(ctx context.Context, in LEDDeviceInput) (int64, error) {
	if err := in.validate(); err != nil {
		return 0, err
	}
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM terminal WHERE id = ?`, in.TerminalID).Scan(&n); err != nil {
		return 0, fmt.Errorf("校验终端: %w", err)
	}
	if n == 0 {
		return 0, fmt.Errorf("选择的终端不存在，请重新选择")
	}
	res, err := s.db.ExecContext(ctx, `
		INSERT INTO leddevice (terminalid, devid, name, ip, width, height, sendport, mac, subterminalid, defaulttext)
		VALUES (?,?,?,?,?,?,?,?,?,?)`,
		in.TerminalID, in.DevID, in.Name, in.IP, in.Width, in.Height,
		in.SendPort, in.MAC, 0, in.DefaultText)
	if err != nil {
		return 0, fmt.Errorf("新建 LED 设备: %w", err)
	}
	return res.LastInsertId()
}

func (s *Service) UpdateLEDDevice(ctx context.Context, id int64, in LEDDeviceInput) error {
	if err := in.validate(); err != nil {
		return err
	}
	res, err := s.db.ExecContext(ctx, `
		UPDATE leddevice SET terminalid = ?, devid = ?, name = ?, ip = ?,
		                     width = ?, height = ?, sendport = ?, mac = ?, defaulttext = ?
		WHERE id = ?`,
		in.TerminalID, in.DevID, in.Name, in.IP, in.Width, in.Height,
		in.SendPort, in.MAC, in.DefaultText, id)
	if err != nil {
		return fmt.Errorf("修改 LED 设备: %w", err)
	}
	if n, _ := res.RowsAffected(); n == 0 {
		var exists int
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM leddevice WHERE id = ?`, id).Scan(&exists); err == nil && exists == 0 {
			return fmt.Errorf("LED 设备不存在")
		}
	}
	return nil
}

// DeleteLEDDevices 删设备时连带清掉它在任务里的绑定 ——
// 旧版不清，留下一堆指向不存在设备的 ledoftask 行。
func (s *Service) DeleteLEDDevices(ctx context.Context, ids []int64) (int, error) {
	if len(ids) == 0 {
		return 0, fmt.Errorf("请先选择要删除的 LED 设备")
	}
	ph, args := placeholders(ids)
	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx,
		`DELETE FROM ledoftask WHERE deviceid IN (`+ph+`)`, args...); err != nil {
		return 0, fmt.Errorf("清理 LED 屏绑定: %w", err)
	}
	res, err := tx.ExecContext(ctx, `DELETE FROM leddevice WHERE id IN (`+ph+`)`, args...)
	if err != nil {
		return 0, fmt.Errorf("删除 LED 设备: %w", err)
	}
	n, _ := res.RowsAffected()
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return int(n), nil
}
