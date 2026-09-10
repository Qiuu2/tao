package typedtask

// 声场任务（tasktype = 25，旧版 zhaoshentaskmanager.php / zhaoshengtaskadd.php）。
//
// # 它和其它四类的差别
//
// 任务属性、执行时间、音量那几段与文字语音/LED 一模一样，多出来的只有两件事：
//
//  1. **终端从「声场分区」里挑，不是终端分区。** 旧版的树是
//     get_zhaoshenggrouped_terminal() 拼的：一层 soundgroupinfo，
//     底下同时挂着这个分区里的广播终端（soundgroup）和噪声探头（sounddevice）。
//     挑终端和挑探头在同一棵树上，节点 id 分别是
//     `stream_<分区>::<终端>` 与 `sounds_<分区>::<探头>`。
//
//  2. **每个选中的探头带一张「音量 → 噪声值」对照表**，落进 soundtask。
//     六档音量是写死的 0 / 20 / 40 / 60 / 80 / 100，一档一行。
//     意思是：这条任务在这个探头上，音量开到 N% 时环境噪声该是多少 dB ——
//     后台据此把音量调上去或调下来。
//
// # soundtask 里 taskid = 0 那六行是模板，不是任务
//
// 现网 soundtask 里躺着 6 行 `taskid=0 devid=0 volume=0/20/…/100`，
// 那是界面上「设置默认噪声」存的一份默认值：新挑一个探头时先拿它填上，
// 列表上的「应用默认噪声」再把它刷到选中任务的所有探头上。
// **它永远不参与真实任务**，所以本文件里凡是读写任务数据的地方都带上 taskid <> 0。

import (
	"context"
	"database/sql"
	"fmt"
	"strconv"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/i18n"
	"htweb/internal/store"
)

// SoundVolumeSteps 是那六档音量。**写死的**，不是配置 ——
// 旧版从表单到 SQL 到后台服务全都按这六个数排，改一个就对不上了。
var SoundVolumeSteps = [6]int{0, 20, 40, 60, 80, 100}

// soundTemplateTaskID 是模板行用的 taskid（见文件头的说明）。
const soundTemplateTaskID = 0

// SoundDeviceRef 是一个被选中的噪声探头连同它那六档噪声值。
type SoundDeviceRef struct {
	DeviceID int64 `json:"deviceId"`
	// GroupID 是探头所属的声场分区，只用来回显，落库不需要
	// （soundtask 只有 taskid/devid/volume/dbvalue 四列）。
	GroupID int64 `json:"groupId"`
	// DBValues 六个噪声值，下标对应 SoundVolumeSteps。
	DBValues []float64 `json:"dbValues"`
	// DeviceName / Deleted 只在回读时填。
	DeviceName string `json:"deviceName,omitempty"`
	Deleted    bool   `json:"deleted,omitempty"`
}

// normalize 把长度不对的 dbValues 补齐/截断到六个，免得写库时下标越界。
func (r *SoundDeviceRef) normalize(fallback []float64) {
	out := make([]float64, len(SoundVolumeSteps))
	copy(out, fallback)
	copy(out, r.DBValues)
	if len(r.DBValues) > len(out) {
		copy(out, r.DBValues[:len(out)])
	}
	r.DBValues = out
}

// ---------- 落库 ----------

// writeSound 写这条任务的媒体与探头噪声值。
//
// ⚠ 与 writeSentences / writeLED 一样是「先清后写」，调用方保证在事务里。
func writeSound(ctx context.Context, tx *sql.Tx, taskID int64, in Input) error {
	// 媒体：旧版这棵树是单选（toncheck 里先把所有节点取消再勾中这一个），
	// 所以正常只有一条；这里不额外限制条数，多传几条也照写。
	for i, id := range in.MediaIDs {
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO mediaoftask (mediaid, taskid, sort) VALUES (?,?,?)`,
			id, taskID, i); err != nil {
			return fmt.Errorf("写入任务媒体: %w", err)
		}
	}
	for _, d := range in.SoundDevices {
		for i, v := range SoundVolumeSteps {
			if _, err := tx.ExecContext(ctx,
				`INSERT INTO soundtask (taskid, devid, volume, dbvalue) VALUES (?,?,?,?)`,
				taskID, d.DeviceID, v, d.DBValues[i]); err != nil {
				return fmt.Errorf("写入噪声值: %w", err)
			}
		}
	}
	return nil
}

func clearSound(ctx context.Context, tx *sql.Tx, taskID int64) error {
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM mediaoftask WHERE taskid = ?`, taskID); err != nil {
		return fmt.Errorf("清理任务媒体: %w", err)
	}
	// ⚠ 带上 taskid <> 0：万一 taskID 传成了 0，这一句会把那六行模板删掉，
	// 而模板一没「设置默认噪声」就永远回不来了。
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM soundtask WHERE taskid = ? AND taskid <> 0`, taskID); err != nil {
		return fmt.Errorf("清理噪声值: %w", err)
	}
	return nil
}

// ---------- 回读 ----------

// soundDevicesOf 读这条任务挂了哪些探头、每档音量的噪声值是多少。
//
// LEFT JOIN sounddevice：探头被删掉之后 soundtask 里的行不会跟着走
// （旧版删探头只删 sounddevice 一张表），内连接会让这些行凭空消失，
// 界面上看不出「这条任务绑的探头没了」。
func (s *Service) soundDevicesOf(ctx context.Context, taskID int64) ([]SoundDeviceRef, error) {
	rows, err := s.db.QueryContext(ctx, `
		SELECT st.devid, COALESCE(sd.groupid, 0), COALESCE(sd.name, ''),
		       sd.id IS NULL, st.volume, COALESCE(st.dbvalue, 0)
		FROM soundtask st
		LEFT JOIN sounddevice sd ON sd.id = st.devid
		WHERE st.taskid = ? AND st.taskid <> 0
		ORDER BY st.devid, st.volume`, taskID)
	if err != nil {
		return nil, fmt.Errorf("查询噪声值: %w", err)
	}
	defer rows.Close()

	byDev := map[int64]*SoundDeviceRef{}
	order := []int64{}
	for rows.Next() {
		var devID, groupID int64
		var name string
		var missing bool
		var volume int
		var db float64
		if err := rows.Scan(&devID, &groupID, &name, &missing, &volume, &db); err != nil {
			return nil, err
		}
		d := byDev[devID]
		if d == nil {
			d = &SoundDeviceRef{
				DeviceID:   devID,
				GroupID:    groupID,
				DeviceName: name,
				Deleted:    missing,
				DBValues:   make([]float64, len(SoundVolumeSteps)),
			}
			byDev[devID] = d
			order = append(order, devID)
		}
		for i, v := range SoundVolumeSteps {
			if v == volume {
				d.DBValues[i] = db
			}
		}
	}
	if err := rows.Err(); err != nil {
		return nil, err
	}

	out := make([]SoundDeviceRef, 0, len(order))
	for _, id := range order {
		d := byDev[id]
		if d.Deleted {
			d.DeviceName = i18n.TC(ctx, "(设备已删除)")
		}
		out = append(out, *d)
	}
	return out, nil
}

// mediaOf 读这条任务的媒体清单。
func (s *Service) mediaOf(ctx context.Context, taskID int64) ([]SoundMedia, error) {
	rows, err := s.db.QueryContext(ctx, `
		SELECT mt.mediaid, COALESCE(m.name, ''), m.id IS NULL
		FROM mediaoftask mt
		LEFT JOIN media m ON m.id = mt.mediaid
		WHERE mt.taskid = ?
		ORDER BY mt.sort, mt.mediaid`, taskID)
	if err != nil {
		return nil, fmt.Errorf("查询任务媒体: %w", err)
	}
	defer rows.Close()
	out := []SoundMedia{}
	for rows.Next() {
		var m SoundMedia
		if err := rows.Scan(&m.MediaID, &m.Name, &m.Deleted); err != nil {
			return nil, err
		}
		if m.Deleted {
			m.Name = i18n.TC(ctx, "(媒体已删除)")
		}
		out = append(out, m)
	}
	return out, rows.Err()
}

// SoundMedia 是回填用的媒体项。
type SoundMedia struct {
	MediaID int64  `json:"mediaId"`
	Name    string `json:"name"`
	Deleted bool   `json:"deleted"`
}

// ---------- 声场分区树 ----------

// SoundTreeDevice 是树上的一个噪声探头。
type SoundTreeDevice struct {
	ID   int64  `json:"id"`
	Name string `json:"name"`
	IP   string `json:"ip"`
}

// SoundTreeTerminal 是树上的一台广播终端。
type SoundTreeTerminal struct {
	ID       int64  `json:"id"`
	Name     string `json:"name"`
	TypeName string `json:"typeName"`
	NetState int    `json:"netstate"`
}

// SoundTreeGroup 是一个声场分区，底下同时挂终端和探头 —— 与旧版那棵树一致。
type SoundTreeGroup struct {
	ID        int64               `json:"id"`
	Name      string              `json:"name"`
	Terminals []SoundTreeTerminal `json:"terminals"`
	Devices   []SoundTreeDevice   `json:"devices"`
}

// SoundTree 返回声场分区树。
//
// 与旧版 get_zhaoshenggrouped_terminal() 同一套取数：
//   - 分区来自 soundgroupinfo（一台设备都没有的分区也要列出来，所以是分区打头）
//   - 终端来自 soundgroup，普通用户还要再交叉 userterminal
//   - 探头来自 sounddevice.groupid
//
// ⚠ 旧版还按 `terminal.typeid IN (可广播的型号)` 筛过一道终端。这里不筛：
// 那份型号清单是 get_terminal_type(3,…) 从 terminaltype 里按能力位算出来的，
// 而分区里本来就只会放广播终端 —— 多筛一道的唯一效果是把现场已经放进分区、
// 型号却不在清单里的终端藏起来，人反而不知道它去哪了。
func (s *Service) SoundTree(ctx context.Context, u *auth.User, keyword string) ([]SoundTreeGroup, error) {
	groups, err := s.soundGroups(ctx)
	if err != nil {
		return nil, err
	}
	idx := map[int64]int{}
	for i := range groups {
		idx[groups[i].ID] = i
	}

	cond := &store.Cond{}
	if !u.IsAdmin {
		cond.Add(`t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)`, u.ID)
	}
	if keyword = strings.TrimSpace(keyword); keyword != "" {
		cond.Add(`t.terminalname LIKE ? ESCAPE '\\'`, store.EscapeLike(keyword))
	}
	rows, err := s.db.QueryContext(ctx, `
		SELECT sg.groupid, t.id, COALESCE(t.terminalname,''),
		       COALESCE(tt.name,''), COALESCE(t.netstate,0)
		FROM soundgroup sg
		JOIN terminal t ON t.id = sg.terminalid
		LEFT JOIN terminaltype tt ON tt.id = t.typeid`+cond.Where()+`
		ORDER BY sg.groupid, t.id`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询声场分区终端: %w", err)
	}
	defer rows.Close()
	for rows.Next() {
		var gid int64
		var t SoundTreeTerminal
		if err := rows.Scan(&gid, &t.ID, &t.Name, &t.TypeName, &t.NetState); err != nil {
			return nil, err
		}
		t.TypeName = i18n.TC(ctx, t.TypeName)
		if i, ok := idx[gid]; ok {
			groups[i].Terminals = append(groups[i].Terminals, t)
		}
	}
	if err := rows.Err(); err != nil {
		return nil, err
	}

	dcond := &store.Cond{}
	if keyword != "" {
		dcond.Add(`sd.name LIKE ? ESCAPE '\\'`, store.EscapeLike(keyword))
	}
	drows, err := s.db.QueryContext(ctx, `
		SELECT COALESCE(sd.groupid,0), sd.id, COALESCE(sd.name,''), COALESCE(sd.ip,'')
		FROM sounddevice sd`+dcond.Where()+`
		ORDER BY sd.groupid, sd.id`, dcond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询噪声设备: %w", err)
	}
	defer drows.Close()
	for drows.Next() {
		var gid int64
		var d SoundTreeDevice
		if err := drows.Scan(&gid, &d.ID, &d.Name, &d.IP); err != nil {
			return nil, err
		}
		if i, ok := idx[gid]; ok {
			groups[i].Devices = append(groups[i].Devices, d)
		}
	}
	return groups, drows.Err()
}

func (s *Service) soundGroups(ctx context.Context) ([]SoundTreeGroup, error) {
	rows, err := s.db.QueryContext(ctx,
		`SELECT id, COALESCE(name,'') FROM soundgroupinfo ORDER BY id`)
	if err != nil {
		return nil, fmt.Errorf("查询声场分区: %w", err)
	}
	defer rows.Close()
	out := []SoundTreeGroup{}
	for rows.Next() {
		g := SoundTreeGroup{Terminals: []SoundTreeTerminal{}, Devices: []SoundTreeDevice{}}
		if err := rows.Scan(&g.ID, &g.Name); err != nil {
			return nil, err
		}
		out = append(out, g)
	}
	return out, rows.Err()
}

// ---------- 默认噪声值（soundtask 里 taskid = 0 那六行）----------

// DBTemplate 返回六档音量的默认噪声值。
//
// 现网那六行是装机时就有的。万一被删了，这里补齐成 0 而不是报错 ——
// 界面上那张表少一格比整页打不开好。
func (s *Service) DBTemplate(ctx context.Context) ([]float64, error) {
	rows, err := s.db.QueryContext(ctx,
		`SELECT volume, COALESCE(dbvalue,0) FROM soundtask WHERE taskid = ? ORDER BY volume`,
		soundTemplateTaskID)
	if err != nil {
		return nil, fmt.Errorf("查询默认噪声值: %w", err)
	}
	defer rows.Close()
	out := make([]float64, len(SoundVolumeSteps))
	for rows.Next() {
		var volume int
		var db float64
		if err := rows.Scan(&volume, &db); err != nil {
			return nil, err
		}
		for i, v := range SoundVolumeSteps {
			if v == volume {
				out[i] = db
			}
		}
	}
	return out, rows.Err()
}

// SetDBTemplate 保存默认噪声值。
//
// 旧版是六条 UPDATE，行不在就什么也不发生（装机时那六行是有的）。
// 这里改成「有就改、没有就补」—— 少一行就永远改不了那一档，这种坏法没人查得出来。
func (s *Service) SetDBTemplate(ctx context.Context, values []float64) error {
	if len(values) != len(SoundVolumeSteps) {
		return fmt.Errorf("默认噪声值要正好 %d 个（对应音量 %s）",
			len(SoundVolumeSteps), stepsText())
	}
	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer tx.Rollback()
	for i, v := range SoundVolumeSteps {
		res, err := tx.ExecContext(ctx,
			`UPDATE soundtask SET dbvalue = ? WHERE taskid = ? AND volume = ?`,
			values[i], soundTemplateTaskID, v)
		if err != nil {
			return fmt.Errorf("保存默认噪声值: %w", err)
		}
		if n, _ := res.RowsAffected(); n == 0 {
			// devid 跟着模板行的既有写法填 0
			if _, err := tx.ExecContext(ctx,
				`INSERT INTO soundtask (taskid, devid, volume, dbvalue) VALUES (?,0,?,?)`,
				soundTemplateTaskID, v, values[i]); err != nil {
				return fmt.Errorf("补写默认噪声值: %w", err)
			}
		}
	}
	return tx.Commit()
}

func stepsText() string {
	parts := make([]string, 0, len(SoundVolumeSteps))
	for _, v := range SoundVolumeSteps {
		parts = append(parts, strconv.Itoa(v))
	}
	return strings.Join(parts, " / ")
}

// ApplyDBTemplate 把默认噪声值刷到选中任务的**所有**探头上，
// 对应旧版列表上的「应用默认噪声」。返回改了多少行。
//
// ⚠ 旧版这一句没有带 taskid <> 0，选中的任务号里要是混进个 0 就会把模板自己也改掉。
// 这里显式排除。
func (s *Service) ApplyDBTemplate(ctx context.Context, u *auth.User, ids []int64) (int64, error) {
	ids = dedupIDs(ids)
	if len(ids) == 0 {
		return 0, fmt.Errorf("请选择要应用的任务")
	}
	tpl, err := s.DBTemplate(ctx)
	if err != nil {
		return 0, err
	}
	visible, err := s.visibleSoundTaskIDs(ctx, u, ids)
	if err != nil {
		return 0, err
	}
	if len(visible) == 0 {
		return 0, fmt.Errorf("选中的任务里没有一条是你能改的声场任务")
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, err
	}
	defer tx.Rollback()

	ph, args := placeholderList(visible)
	var total int64
	for i, v := range SoundVolumeSteps {
		a := append([]interface{}{tpl[i]}, args...)
		a = append(a, v)
		res, err := tx.ExecContext(ctx,
			`UPDATE soundtask SET dbvalue = ? WHERE taskid IN (`+ph+`) AND taskid <> 0 AND volume = ?`, a...)
		if err != nil {
			return 0, fmt.Errorf("应用默认噪声值: %w", err)
		}
		n, _ := res.RowsAffected()
		total += n
	}
	if err := tx.Commit(); err != nil {
		return 0, err
	}
	return total, nil
}

// visibleSoundTaskIDs 从给定 id 里筛出「确实是声场任务、而且当前用户看得见」的那些。
func (s *Service) visibleSoundTaskIDs(ctx context.Context, u *auth.User, ids []int64) ([]int64, error) {
	sp, err := s.spec(KindSound)
	if err != nil {
		return nil, err
	}
	ph, args := placeholderList(ids)
	cond := &store.Cond{}
	cond.Add("t.taskid IN ("+ph+")", args...)
	tc, targs := typeCond(sp.Types)
	cond.Add(tc, targs...)
	if !u.IsAdmin {
		cond.Add("t.task_user_id = ?", u.ID)
	}
	rows, err := s.db.QueryContext(ctx,
		"SELECT t.taskid FROM task t"+cond.Where()+sp.Extra, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("校验声场任务: %w", err)
	}
	defer rows.Close()
	out := []int64{}
	for rows.Next() {
		var id int64
		if err := rows.Scan(&id); err != nil {
			return nil, err
		}
		out = append(out, id)
	}
	return out, rows.Err()
}

func placeholderList(ids []int64) (string, []interface{}) {
	if len(ids) == 0 {
		return "NULL", nil
	}
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		args[i] = id
	}
	return strings.TrimSuffix(strings.Repeat("?,", len(ids)), ","), args
}

func dedupIDs(ids []int64) []int64 {
	seen := map[int64]bool{}
	out := make([]int64, 0, len(ids))
	for _, id := range ids {
		if id > 0 && !seen[id] {
			seen[id] = true
			out = append(out, id)
		}
	}
	return out
}

// validateSound 校验声场任务多出来的那两样：媒体与噪声探头。
func (s *Service) validateSound(ctx context.Context, in *Input) error {
	in.MediaIDs = dedupIDs(in.MediaIDs)
	if len(in.MediaIDs) == 0 {
		return fmt.Errorf("请选择要播放的媒体")
	}
	// 旧版那棵媒体树是单选的（勾一个会把别的都取消），这里跟着限制到一条 ——
	// 放开成多条的话，后台按什么顺序播、播完第一条还播不播，都是没人定义过的行为。
	if len(in.MediaIDs) > 1 {
		return fmt.Errorf("声场任务只能选一个媒体")
	}
	ph, args := placeholderList(in.MediaIDs)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM media WHERE id IN (`+ph+`)`, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验媒体: %w", err)
	}
	if n != len(in.MediaIDs) {
		return fmt.Errorf("选中的媒体已不存在，请重新选择")
	}

	if len(in.SoundDevices) == 0 {
		return fmt.Errorf("请至少选择一个噪声设备")
	}
	if len(in.SoundDevices) > 500 {
		return fmt.Errorf("噪声设备最多 500 个")
	}
	tpl, err := s.DBTemplate(ctx)
	if err != nil {
		return err
	}
	seen := map[int64]bool{}
	ids := make([]int64, 0, len(in.SoundDevices))
	for i := range in.SoundDevices {
		d := &in.SoundDevices[i]
		if d.DeviceID <= 0 {
			return fmt.Errorf("噪声设备列表里有非法的设备号")
		}
		if seen[d.DeviceID] {
			return fmt.Errorf("噪声设备列表里有重复的设备")
		}
		seen[d.DeviceID] = true
		ids = append(ids, d.DeviceID)
		// 没传满六个就拿默认噪声值补上 —— 与旧版一致：
		// 树上勾了但没点开设置的探头，走的就是那份默认值。
		d.normalize(tpl)
	}
	dph, dargs := placeholderList(ids)
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM sounddevice WHERE id IN (`+dph+`)`, dargs...).Scan(&n); err != nil {
		return fmt.Errorf("校验噪声设备: %w", err)
	}
	if n != len(ids) {
		return fmt.Errorf("噪声设备列表里有已不存在的设备，请重新选择")
	}
	return nil
}
