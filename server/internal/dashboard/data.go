package dashboard

import (
	"context"
	"database/sql"
	"fmt"
	"strings"
	"time"

	"htweb/internal/auth"
	"htweb/internal/store"
	"htweb/internal/task"
)

// 设备概况 / 快捷任务 / 紧急广播 / 浏览任务的数据。

// ---------- 设备概况 ----------

type TypeCount struct {
	Type    string `json:"type"`
	Total   int    `json:"total"`
	Online  int    `json:"online"`
	Offline int    `json:"offline"`
}

type Overview struct {
	Total   int         `json:"total"`
	Online  int         `json:"online"`
	Offline int         `json:"offline"`
	ByType  []TypeCount `json:"byType"`
}

// Overview 统计终端总数与在线情况。
//
// LEFT JOIN terminaltype：类型字典里没有的 typeid 也要能统计出来，
// 内连接会让这类终端从总数里凭空消失。
func (s *Service) Overview(ctx context.Context, u *auth.User) (*Overview, error) {
	cond := &store.Cond{}
	if !u.IsAdmin {
		cond.Add("t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)", u.ID)
	}
	where := cond.Where()

	rows, err := s.db.QueryContext(ctx, `
		SELECT COALESCE(NULLIF(tt.name,''), '未知类型') AS tname,
		       COUNT(*) AS n, SUM(t.netstate = 1) AS online
		FROM terminal t
		LEFT JOIN terminaltype tt ON tt.id = t.typeid`+where+`
		GROUP BY tname
		ORDER BY n DESC, tname ASC`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("统计设备概况: %w", err)
	}
	defer rows.Close()

	out := &Overview{ByType: []TypeCount{}}
	for rows.Next() {
		var c TypeCount
		var online sql.NullInt64
		if err := rows.Scan(&c.Type, &c.Total, &online); err != nil {
			return nil, err
		}
		c.Online = int(online.Int64)
		c.Offline = c.Total - c.Online
		out.Total += c.Total
		out.Online += c.Online
		out.ByType = append(out.ByType, c)
	}
	out.Offline = out.Total - out.Online
	return out, rows.Err()
}

// ---------- 快捷任务 / 紧急广播 ----------

// BoundTask 是一个绑定到面板上的任务，带上够界面显示的字段。
type BoundTask struct {
	TaskID    int64  `json:"taskId"`
	TaskName  string `json:"taskName"`
	PlayTime  string `json:"playtime"`
	State     int    `json:"state"`
	StateText string `json:"stateText"`
	// Missing 表示这个 ID 指向的任务已经被删了 —— 绑定还留着，界面要标出来。
	Missing bool `json:"missing"`
}

type EmergencySlot struct {
	Key  string     `json:"key"`
	Name string     `json:"name"`
	Task *BoundTask `json:"task"`
}

type Config struct {
	Shortcuts  []Shortcut      `json:"shortcuts"`
	QuickTasks []BoundTask     `json:"quickTasks"`
	Emergency  []EmergencySlot `json:"emergency"`
}

// Config 返回首页三块可配置区域的当前内容，并把任务 ID 补成带名字的对象。
func (s *Service) Config(ctx context.Context) (*Config, error) {
	st := s.snapshot()

	ids := append([]int64{}, st.QuickTasks...)
	for _, slot := range EmergencySlots {
		if id := st.Emergency[slot.Key]; id > 0 {
			ids = append(ids, id)
		}
	}
	known, err := s.loadTasks(ctx, ids)
	if err != nil {
		return nil, err
	}

	out := &Config{
		Shortcuts:  st.Shortcuts,
		QuickTasks: []BoundTask{},
		Emergency:  []EmergencySlot{},
	}
	for _, id := range st.QuickTasks {
		out.QuickTasks = append(out.QuickTasks, pick(known, id))
	}
	for _, slot := range EmergencySlots {
		e := EmergencySlot{Key: slot.Key, Name: slot.Name}
		if id := st.Emergency[slot.Key]; id > 0 {
			t := pick(known, id)
			e.Task = &t
		}
		out.Emergency = append(out.Emergency, e)
	}
	return out, nil
}

func pick(known map[int64]BoundTask, id int64) BoundTask {
	if t, ok := known[id]; ok {
		return t
	}
	return BoundTask{TaskID: id, TaskName: "(任务已删除)", Missing: true}
}

func (s *Service) loadTasks(ctx context.Context, ids []int64) (map[int64]BoundTask, error) {
	out := map[int64]BoundTask{}
	ids = dedup(ids)
	if len(ids) == 0 {
		return out, nil
	}
	ph, args := placeholders(ids)
	rows, err := s.db.QueryContext(ctx, `
		SELECT taskid, COALESCE(taskname,''), TIME_FORMAT(playtime,'%H:%i:%s'),
		       COALESCE(state,0)
		FROM task WHERE taskid IN (`+ph+`)`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询绑定任务: %w", err)
	}
	defer rows.Close()
	for rows.Next() {
		var t BoundTask
		if err := rows.Scan(&t.TaskID, &t.TaskName, &t.PlayTime, &t.State); err != nil {
			return nil, err
		}
		t.StateText = stateText(t.State)
		out[t.TaskID] = t
	}
	return out, rows.Err()
}

// stateText 与任务模块口径一致：state 归后台 C 服务所有，这里只做展示。
func stateText(v int) string {
	switch v {
	case 0:
		return "准备"
	case 1:
		return "执行中"
	case 2:
		return "已停止"
	case 3:
		return "启动中"
	default:
		return fmt.Sprintf("状态 %d", v)
	}
}

// SetShortcuts 保存顶部快捷入口。
func (s *Service) SetShortcuts(list []Shortcut) error {
	if len(list) > 20 {
		return fmt.Errorf("快捷入口最多 20 个")
	}
	clean := make([]Shortcut, 0, len(list))
	for _, sc := range list {
		sc.Label = strings.TrimSpace(sc.Label)
		sc.Path = strings.TrimSpace(sc.Path)
		if sc.Label == "" || sc.Path == "" {
			return fmt.Errorf("快捷入口的名称与目标页面都不能为空")
		}
		if len([]rune(sc.Label)) > 12 {
			return fmt.Errorf("快捷入口名称最多 12 个字：%s", sc.Label)
		}
		// 只允许站内路径，挡掉 //evil.com 与 javascript: 这类目标
		if !strings.HasPrefix(sc.Path, "/") || strings.HasPrefix(sc.Path, "//") {
			return fmt.Errorf("快捷入口只能指向站内页面（以 / 开头）：%s", sc.Path)
		}
		clean = append(clean, sc)
	}
	s.mu.Lock()
	s.state.Shortcuts = clean
	s.mu.Unlock()
	return s.save()
}

// SetQuickTasks 保存「快捷任务」绑定。只接受文件广播类型的任务。
func (s *Service) SetQuickTasks(ctx context.Context, ids []int64) error {
	ids = dedup(ids)
	if len(ids) > 12 {
		return fmt.Errorf("快捷任务最多绑定 12 个")
	}
	if err := s.assertFileTasks(ctx, ids); err != nil {
		return err
	}
	s.mu.Lock()
	s.state.QuickTasks = ids
	s.mu.Unlock()
	return s.save()
}

// SetEmergency 保存紧急广播四个固定槽位的绑定。
// 传 0 表示解绑该槽位；槽位本身不能增删。
func (s *Service) SetEmergency(ctx context.Context, m map[string]int64) error {
	valid := map[string]bool{}
	for _, slot := range EmergencySlots {
		valid[slot.Key] = true
	}
	ids := []int64{}
	for k, v := range m {
		if !valid[k] {
			return fmt.Errorf("紧急广播只有 quake / evacuate / alert / fire 四个固定槽位，不认识 %q", k)
		}
		if v > 0 {
			ids = append(ids, v)
		}
	}
	if err := s.assertFileTasks(ctx, dedup(ids)); err != nil {
		return err
	}
	s.mu.Lock()
	next := map[string]int64{}
	for k, v := range m {
		if v > 0 {
			next[k] = v
		}
	}
	s.state.Emergency = next
	s.mu.Unlock()
	return s.save()
}

// assertFileTasks 校验这些 ID 都是存在的文件广播任务。
// 类型集合与任务模块的启停闸门一致（2 / 7 / 15），避免绑上一个根本启动不了的任务。
func (s *Service) assertFileTasks(ctx context.Context, ids []int64) error {
	if len(ids) == 0 {
		return nil
	}
	ph, args := placeholders(ids)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task WHERE taskid IN (`+ph+`)
		 AND tasktype IN (2,7,15) AND channel = 0 AND sec_task_id = 0`, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验任务: %w", err)
	}
	if n != len(ids) {
		return fmt.Errorf("绑定的任务里有不存在的、或不是文件广播类型的任务，请重新选择")
	}
	return nil
}

// ---------- 浏览任务 ----------

type BrowseItem struct {
	Index      int    `json:"index"`
	TaskID     int64  `json:"taskId"`
	TaskName   string `json:"taskName"`
	FolderName string `json:"folderName"`
	// Weekdays 是播放周期，7 位掩码转成 [1..7]（1 = 周日）
	Weekdays  []int  `json:"weekdays"`
	CycleText string `json:"cycleText"`
	PlayTime  string `json:"playtime"`
	State     int    `json:"state"`
	StateText string `json:"stateText"`
	StartDate string `json:"startdate"`
	EndDate   string `json:"enddate"`
	Terminals int    `json:"terminals"`
	// EnabledToday 表示今天这条任务是否真的会执行。
	EnabledToday bool `json:"enabledToday"`
	ProjectState int  `json:"projectstate"`
}

type BrowseQuery struct {
	FolderID int64
	// Weekday 1..7（周日=1，与掩码位次一致），0 表示不筛
	Weekday int
	// AutoOnly: 1=只看自动任务, 2=只看手动任务, 0=全部
	AutoMode int
	// Scope: enabled=当天启用, disabled=当天停用, all=全部
	Scope string
	Pager store.Pager
}

type BrowseResult struct {
	Items []BrowseItem
	Total int64
}

// Browse 浏览任务：对应参考图下半部分那张表。
//
// 「当天启用」的判定三条同时成立：
//   - projectstate = 0（启用；注意 0 才是启用，见 task 模块的说明）
//   - 今天落在 startdate ~ enddate 之间
//   - 星期掩码里今天这一位是 1
//
// 旧 Browse_active_task.php 就是这么筛的（`projectstate=0 AND startdate<=CURDATE() ...`），
// 这里保持同一口径。
func (s *Service) Browse(ctx context.Context, u *auth.User, q BrowseQuery) (*BrowseResult, error) {
	now := time.Now()
	// exemodel 是周日打头的掩码（旧站 SUBSTRING(exemodel, WEEKDAY()+2 ... ) 里
	// 周日取第 1 位、周一第 2 位）；Go 的 Weekday 也是周日=0，直接对上。
	idx := int(now.Weekday())
	if q.Weekday >= 1 && q.Weekday <= 7 {
		idx = q.Weekday - 1
	}
	// MySQL 的 SUBSTRING 下标从 1 开始
	pos := idx + 1

	cond := &store.Cond{}
	cond.Add("t.tasktype IN (2,7,15) AND t.channel = 0 AND t.sec_task_id = 0")
	if !u.IsAdmin {
		cond.Add("t.task_user_id = ?", u.ID)
	}
	if q.FolderID > 0 {
		cond.Add("t.parentid = ?", q.FolderID)
	}
	switch q.AutoMode {
	case 1:
		cond.Add("COALESCE(t.exemodel,'0000000') <> '0000000'")
	case 2:
		cond.Add("COALESCE(t.exemodel,'0000000') = '0000000'")
	}

	// 「今天会不会执行」这一串条件复用两次（筛选 + 每行的标记），拼成一个片段
	active := fmt.Sprintf(
		"(t.projectstate = %d AND t.startdate <= CURDATE() AND t.enddate >= CURDATE() "+
			"AND SUBSTRING(COALESCE(t.exemodel,'0000000'), %d, 1) = '1')",
		task.StateEnabled, pos)

	switch q.Scope {
	case "enabled":
		cond.Add(active)
	case "disabled":
		cond.Add("NOT " + active)
	}
	where := cond.Where()

	out := &BrowseResult{Items: []BrowseItem{}}
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM task t"+where, cond.Args()...).Scan(&out.Total); err != nil {
		return nil, fmt.Errorf("统计浏览任务: %w", err)
	}
	if out.Total == 0 {
		return out, nil
	}

	args := append(append([]interface{}{}, cond.Args()...), q.Pager.PageSize, q.Pager.Offset())
	rows, err := s.db.QueryContext(ctx, `
		SELECT t.taskid, COALESCE(t.taskname,''), COALESCE(f.name,'(未分组)'),
		       COALESCE(t.exemodel,'0000000'), TIME_FORMAT(t.playtime,'%H:%i:%s'),
		       COALESCE(t.state,0), COALESCE(t.projectstate,0),
		       COALESCE(DATE_FORMAT(t.startdate,'%Y-%m-%d'),''),
		       COALESCE(DATE_FORMAT(t.enddate,'%Y-%m-%d'),''),
		       (SELECT COUNT(*) FROM terminaloftask ot WHERE ot.taskid = t.taskid),
		       `+active+`
		FROM task t
		LEFT JOIN filetaskfree f ON f.id = t.parentid`+where+`
		ORDER BY t.playtime ASC, t.taskid ASC
		LIMIT ? OFFSET ?`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询浏览任务: %w", err)
	}
	defer rows.Close()

	i := q.Pager.Offset()
	for rows.Next() {
		var it BrowseItem
		var mask string
		if err := rows.Scan(&it.TaskID, &it.TaskName, &it.FolderName, &mask, &it.PlayTime,
			&it.State, &it.ProjectState, &it.StartDate, &it.EndDate,
			&it.Terminals, &it.EnabledToday); err != nil {
			return nil, err
		}
		i++
		it.Index = i
		it.Weekdays = parseWeekdays(mask)
		it.CycleText = cycleText(mask)
		it.StateText = stateText(it.State)
		out.Items = append(out.Items, it)
	}
	return out, rows.Err()
}

func parseWeekdays(mask string) []int {
	out := []int{}
	for i := 0; i < len(mask) && i < 7; i++ {
		if mask[i] == '1' {
			out = append(out, i+1)
		}
	}
	return out
}

// exemodel 是周日打头的 7 位掩码（第 1 位 = 周日），标签顺序要跟它对齐。
var weekNames = [7]string{"日", "一", "二", "三", "四", "五", "六"}

func cycleText(mask string) string {
	days := parseWeekdays(mask)
	switch len(days) {
	case 0:
		return "手动"
	case 7:
		return "每天"
	}
	parts := make([]string, 0, len(days))
	for _, d := range days {
		parts = append(parts, weekNames[d-1])
	}
	return "周" + strings.Join(parts, "、")
}

// ---------- 小工具 ----------

func placeholders(ids []int64) (string, []interface{}) {
	if len(ids) == 0 {
		return "NULL", nil
	}
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		args[i] = id
	}
	return strings.TrimSuffix(strings.Repeat("?,", len(ids)), ","), args
}

func dedup(ids []int64) []int64 {
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
