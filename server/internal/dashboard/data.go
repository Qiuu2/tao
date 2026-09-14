package dashboard

import (
	"context"
	"database/sql"
	"fmt"
	"strings"
	"time"

	"htweb/internal/auth"
	"htweb/internal/i18n"
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
		// 终端型号是产品词汇（不是用户起的名字），要跟着界面语言走。
		// 分组、排序仍按库里的中文原文做 —— 翻译只发生在最后这一步，
		// 免得英文下把两个原本不同的型号并成一组。
		c.Type = i18n.TC(ctx, c.Type)
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

// EmergencySlot 是返回给界面的一个紧急广播按钮。
//
// 这里**没有 Task 字段** —— 紧急广播不绑任务了，四个按钮直接发 SDK 命令。
// 界面拿这个只是为了拿到名字和 key，槽位本身是固定的四个。
type EmergencySlot struct {
	Key  string `json:"key"`
	Name string `json:"name"`
}

type Config struct {
	Shortcuts  []Shortcut      `json:"shortcuts"`
	QuickTasks []BoundTask     `json:"quickTasks"`
	Emergency  []EmergencySlot `json:"emergency"`
}

// Config 返回首页三块可配置区域的当前内容，并把任务 ID 补成带名字的对象。
func (s *Service) Config(ctx context.Context) (*Config, error) {
	st := s.snapshot()

	known, err := s.loadTasks(ctx, st.QuickTasks)
	if err != nil {
		return nil, err
	}
	// 绑定指向的任务已经被删掉了 —— 把绑定本身也清掉，别留着一条指向空处的记录。
	if s.pruneDeleted(known) {
		st = s.snapshot()
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
		// 槽位名是固定的产品词汇（地震/疏散/警戒/消防），跟着界面语言走。
		// key 不翻 —— 那是前端和接口用的标识，翻了两边就对不上了。
		out.Emergency = append(out.Emergency, EmergencySlot{
			Key:  slot.Key,
			Name: i18n.TC(ctx, slot.Name),
		})
	}
	return out, nil
}

// pruneDeleted 把指向**已删除任务**的绑定从状态里摘掉，并落盘。
// 返回是否真的动了东西。
//
// # 为什么是「读的时候顺手清」，而不是挂在删除任务那一步上
//
// 任务能从很多地方被删掉：任务页、开发者接口、AI 助手、删用户时的级联、
// 删终端时的级联、快捷任务自己的清理……挂钩子就得每一处都挂，
// 而漏掉任何一处的表现是「绑定还在、点了没反应」，没有任何报错。
// 读的时候统一清，则不管任务是怎么没的都能收拾干净。
//
// ⚠ 只在 loadTasks **查成功**之后调用。查库失败时 Config 已经先返回了错误 ——
// 数据库连不上的时候把用户的绑定全清掉，是这段代码最坏的失败方式。
//
// 判定依据是 task 表里有没有这一行，**不带可见范围过滤**（见 loadTasks 的 SQL）：
// 这份状态是全站共用的一份，按「当前这个人看不看得见」来删，
// 会让普通用户一进首页就把管理员配的绑定清掉。
func (s *Service) pruneDeleted(known map[int64]BoundTask) bool {
	s.mu.Lock()
	var dropped []int64
	kept := make([]int64, 0, len(s.state.QuickTasks))
	for _, id := range s.state.QuickTasks {
		if _, ok := known[id]; ok {
			kept = append(kept, id)
		} else {
			dropped = append(dropped, id)
		}
	}
	s.state.QuickTasks = kept
	s.mu.Unlock()

	if len(dropped) == 0 {
		return false
	}
	logf("首页快捷任务里有 %d 条指向已被删除的任务，已清掉（任务号 %v）", len(dropped), dropped)
	if err := s.save(); err != nil {
		// 落盘失败不该让首页打不开：内存里已经清干净了，这一次的返回是对的，
		// 下次进程重启会把旧的读回来，届时再清一次。
		logf("清理失效绑定后落盘失败（下次读取会再清一次）: %v", err)
	}
	return true
}

// pick 兜底：绑定指向的任务不在库里。
//
// 正常情况下走不到这儿 —— pruneDeleted 已经把这类绑定摘掉了。
// 留着它是为了 pruneDeleted 落盘失败、或者将来有别的读取路径时，
// 页面上显示的是「(任务已删除)」而不是一个看不出问题的空白。
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
		t.StateText = i18n.TC(ctx, stateText(t.State))
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
	Index    int    `json:"index"`
	TaskID   int64  `json:"taskId"`
	TaskName string `json:"taskName"`
	// FolderName 是分组/目录名，没有就是空串。文件广播来自 filetaskfree，
	// led播放来自 ledtaskfree；作息方案不按这两张表分组（它按 task.info 归组）。
	FolderName string `json:"folderName"`
	// Module 是这条任务属于「任务管理」下的哪一个模块（作息方案 / 文件广播 / …）。
	Module string `json:"module"`
	// Category 是给人看的那一格。
	//
	// 这一列原来直接显示 filetaskfree.name，对作息方案是错的 ——
	// 作息方案的条目按 task.info（方案名）归组，parentid 指着的那个
	// filetaskfree 行只是个默认值，于是「早读预备铃」的所属分类显示成「admin」，
	// 看的人根本认不出它属于哪个方案。现在是：
	//
	//	作息方案（春季作息）   ← 只有作息方案带括号，里面是方案名（task.info）
	//	文件广播               ← 其余都只写模块名
	//	led播放
	//	终端功放
	//
	// ⚠ 文件广播/led播放**不带**括号：它们那个括号里本来放的是任务分组名，
	// 而这一列问的是「属于哪个功能模块」，分组名另有 folderName 一列管。
	Category string `json:"category"`
	// Weekdays 是播放周期，7 位掩码转成 [1..7]（1 = 周日）
	Weekdays  []int  `json:"weekdays"`
	CycleText string `json:"cycleText"`
	PlayTime  string `json:"playtime"`
	State     int    `json:"state"`
	StateText string `json:"stateText"`
	StartDate string `json:"startdate"`
	EndDate   string `json:"enddate"`
	Terminals int    `json:"terminals"`
	ProjectState int  `json:"projectstate"`
	// OwnerUserID / OwnerName 是这条任务归谁（task.task_user_id → book_admin.username）。
	//
	// 旧版这一页也带着它（Browse_active_task.php:544 `"taskuserid"=>...`），
	// 只是模板里没画出来。管理员看到的是全站任务，不写明归属就分不清
	// 「这条是谁排的」—— 出了问题找不到人。
	//
	// ⚠ 用户被删掉时 username 查不到，回空串，界面画「—」：
	// 内连接会让这一行整个消失，那才是真的查不出问题。
	OwnerUserID int64  `json:"ownerUserId"`
	OwnerName   string `json:"ownerName"`
	// DisableDay 是这条任务被单独停掉的那一天（task.disableday）。
	//
	// 旧版看板上就有这一列（Browse_active_task_form.html:161，表头「当天停用」）：
	// 勾中若干任务点「当天停用」，写的就是这一列 —— 值是哪天，那天这条任务就不响，
	// 点「当天启用」再清回 0000-00-00。它与 projectstate 是两回事：
	// projectstate 是整条任务的长期启停，disableday 只挖掉某一天。
	//
	// ⚠ **原样回库里的值**，0000-00-00 也照回（旧版模板就是 `<{$info[loop].disableday}>`
	// 直接打印）。一度把 0000-00-00 折成空串、界面画「—」，需求方要的是看见字段本身。
	DisableDay string `json:"disableday"`
	// RunStatus 是状态那一列，五个取值：
	//
	//	done     已执行     state = 0 且执行时间已经过了
	//	ready    准备执行   state = 0 且还没到点
	//	running  正在执行   state = 1
	//	paused   暂停       state = 2
	//	playnow  立即执行   state = 3
	//
	// 判据是需求方定的：**先看这一天排不排得上（列表本身已经筛过），
	// 再看 state；只有 state = 0 才拿钟点去分「已执行 / 准备执行」**。
	// 与旧版 Browse_active_task_form.html:110~155 的分支结构一致
	// （它也是只在 state = 0 的分支里写 JS 比时间），只是旧版把
	// 3 / 1 / 2 各画成一句不同的话，这里给了三个稳定的 key。
	//
	// ⚠ 一度把 1 / 2 / 3 合并成一个「执行中」，按需求方要求拆开 ——
	// 暂停和立即执行是两回事，合起来现场分不清该去按哪个按钮。
	//
	// ⚠ 只有**看今天**时 state 才算数：星期选择器可以切到别的日子，
	// 而 state 是此时此刻的值，拿它去说「上周二那条正在执行」是假的。
	//
	// 回传的是 key 不是文案 —— 界面要按它上色。
	RunStatus string `json:"runStatus"`
}

type BrowseQuery struct {
	FolderID int64
	// Weekday 1..7（周日=1，与掩码位次一致），0 表示「今天」
	Weekday int
	// AutoOnly: 1=只看自动任务, 2=只看手动任务, 0=全部
	AutoMode int
	// Module 按「任务管理」下的模块筛。空串或 "all" = 全部，取值见 browseModules。
	Module string
	Pager  store.Pager
}

// browseModules 把「任务管理」菜单下的各个模块映射成 task.tasktype 的取值集合。
//
// 口径逐条对齐各模块自己的列表条件（见 internal/task 与 internal/typedtask），
// 不另起一套 —— 否则看板上数出来的任务数会和点进去之后看到的对不上。
//
// ⚠ tasktype = 15 同时属于「作息方案」和「文字语音」，靠 info 分：
// 作息方案的 info 是方案名（非空），文字语音的 info 是空串（契约 C-38）。
var browseModules = map[string]string{
	"bell":      "(t.tasktype IN (1,15) AND COALESCE(t.info,'') <> '')",
	"file":      "(t.tasktype IN (2,7))",
	"amplifier": "(t.tasktype = 5 AND COALESCE(t.prepower,0) = 0)",
	"collect":   "(t.tasktype = 3)",
	"tts":       "(t.tasktype IN (15,17,19) AND COALESCE(t.info,'') = '')",
	"led":       "(t.tasktype IN (24,30))",
}

// browseAllModules 是「全部」（空串或 "all"，以及任何不认识的值）时的类型范围：
// 上面那几个模块的并集。
//
// ⚠ 不能写成「不加任何 tasktype 条件」—— task 表里还躺着功放子任务(9)、
// LED 子任务、声场任务(25) 之类，全放出来看板会冒出一堆用户在界面上
// 根本找不到的行。
const browseAllModules = `(t.tasktype IN (1,2,3,5,7,15,17,19,24,30))`

type BrowseResult struct {
	Items []BrowseItem
	Total int64
	// ViewDate 是这一次「看的是哪一天」，YYYY-MM-DD。
	//
	// 星期下拉一旦能选「周三」，界面上再写「当天启用」就说不清是哪天了 ——
	// 把这一天原样回给前端，标题里写出来，人一眼知道自己在看什么。
	ViewDate string
}

// Browse 浏览任务：对应参考图下半部分那张表。
//
// # 「看的是哪一天」
//
// 星期下拉决定的不只是掩码上取哪一位，还是**一个具体日期**：选「周三」看的就是
// 本周三那天，起止日期要把那天圈进去才算数。旧版就是这么干的 ——
// `DATE_ADD(CURDATE(), INTERVAL (选的星期 - 今天星期) DAY)`
// （Browse_active_task.php:243/247），偏移量可以是负数（本周已经过去的那几天）。
//
// 新版一度只把星期用在掩码那一位上，日期范围仍然拿 CURDATE() 比 ——
// 结果选「今天」时，列表里会混进起止日期根本不覆盖今天的任务。按需求方要求改回来。
//
// # 「那天启用」的判定
//
// 三条同时成立：
//   - projectstate = 0（启用；注意 0 才是启用，见 task 模块的说明）
//   - 所看那一天落在 startdate ~ enddate 之间
//   - 星期掩码里那一天这一位是 1
//
// 三条都是**硬过滤**，与旧版那条查询逐项对齐
// （Browse_active_task.php:241~267）：
//
//	WHERE task.projectstate = '0'
//	  AND task.startdate <= <那一天> AND task.enddate >= <那一天>
//	  AND SUBSTRING(task.exemodel, <那一天在掩码里的位>, 1)   ← '1' 真、'0' 假
//
// 所以这一页列的是「**你看的那一天真的会响**的任务」，名副其实。
//
// ⚠ 这里一度有一组「当天启用 / 当天停用 / 全部」的单选，让不响的那些也列出来，
// 是**误读**：旧版那两个不是筛选，是**操作** —— 勾几行点下去，写的是
// task.disableday（do.php:enordis_date_task）。现在它们是按钮，见 SetDisableDay。
//
// ⚠ **单独停用日（disableday）不参与过滤**：那天被单独停掉的任务照样列出来，
// 「单独停用日」那一列会写出是哪天。旧版这条查询同样不带它 —— 不列出来的话，
// 人就没地方把它点回「当天启用」了。
// viewDateOf 把「星期下拉选的是第几天」换算成一个具体日期。
//
// weekday 取值 1~7（1 = 周日），其余值一律当「今天」。
// 偏移可正可负 —— 选的日子可能是本周已经过去的那几天
// （旧版 `DATE_ADD(CURDATE(), INTERVAL (选的星期 - 今天星期) DAY)`，
// Browse_active_task.php:243/247）。
//
// 返回 (那一天的日期, 星期掩码里的下标 1~7)。
//
// ⚠ Browse 和 SetDisableDay 必须用同一份算法：界面上写着「看的是 9-16」，
// 点「当天停用」就得停 9-16 那一天，两处各算各的迟早对不上。
func viewDateOf(now time.Time, weekday int) (string, int) {
	idx := int(now.Weekday())
	if weekday >= 1 && weekday <= 7 {
		idx = weekday - 1
	}
	offset := idx - int(now.Weekday())
	// MySQL 的 SUBSTRING 下标从 1 开始
	return now.AddDate(0, 0, offset).Format("2006-01-02"), idx + 1
}

func (s *Service) Browse(ctx context.Context, u *auth.User, q BrowseQuery) (*BrowseResult, error) {
	now := time.Now()
	// exemodel 是周日打头的掩码（旧站 SUBSTRING(exemodel, WEEKDAY()+2 ... ) 里
	// 周日取第 1 位、周一第 2 位）；Go 的 Weekday 也是周日=0，直接对上。
	viewDate, pos := viewDateOf(now, q.Weekday)
	today := now.Format("2006-01-02")
	nowClock := now.Format("15:04:05")

	cond := &store.Cond{}
	if frag, ok := browseModules[q.Module]; ok {
		cond.Add(frag)
	} else {
		cond.Add(browseAllModules)
	}
	cond.Add("t.channel = 0 AND t.sec_task_id = 0")
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
	// 有效期必须圈住所看那一天 —— 与 Scope 无关，见上面的注释
	cond.Add("t.startdate <= ? AND t.enddate >= ?", viewDate, viewDate)
	// ⚠ **停用的任务一条都不出现**（`projectstate` 0 = 启用、1 = 停用，列注释是反的）。
	//
	// 旧版这一页的每条查询都带着 `task.projectstate = 0`
	// （Browse_active_task.php:209/221/241/274/311/360/414/464），
	// 新版一度把它交给那组「当天启用 / 当天停用」单选去筛 ——
	// 于是选「全部」时停用的任务也列出来了，看板上一堆根本不会响的行。
	// 按需求方要求改成硬过滤：停用与否在各自的模块页里管，看板只看在用的。
	cond.Add("t.projectstate = ?", task.StateEnabled)
	// 星期掩码里那一天这一位要是 1 —— 旧版是把 SUBSTRING 的结果直接当布尔用
	// （MySQL 里字符串 '1' 为真、'0' 为假），这里写清楚等于 '1'。
	cond.Add(fmt.Sprintf("SUBSTRING(COALESCE(t.exemodel,'0000000'), %d, 1) = '1'", pos))

	where := cond.Where()

	out := &BrowseResult{Items: []BrowseItem{}, ViewDate: viewDate}
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM task t"+where, cond.Args()...).Scan(&out.Total); err != nil {
		return nil, fmt.Errorf("统计浏览任务: %w", err)
	}
	if out.Total == 0 {
		return out, nil
	}

	args := append(append([]interface{}{}, cond.Args()...), q.Pager.PageSize, q.Pager.Offset())
	rows, err := s.db.QueryContext(ctx, `
		SELECT t.taskid, COALESCE(t.taskname,''),
		       COALESCE(t.tasktype,0), COALESCE(t.info,''),
		       COALESCE(f.name,''), COALESCE(lf.name,''),
		       COALESCE(t.exemodel,'0000000'), TIME_FORMAT(t.playtime,'%H:%i:%s'),
		       COALESCE(t.state,0), COALESCE(t.projectstate,0),
		       COALESCE(DATE_FORMAT(t.startdate,'%Y-%m-%d'),''),
		       COALESCE(DATE_FORMAT(t.enddate,'%Y-%m-%d'),''),
		       (SELECT COUNT(*) FROM terminaloftask ot WHERE ot.taskid = t.taskid),
		       COALESCE(CAST(t.disableday AS CHAR),''),
		       COALESCE(t.task_user_id,0), COALESCE(b.username,'')
		FROM task t
		LEFT JOIN filetaskfree f ON f.id = t.parentid
		LEFT JOIN ledtaskfree lf ON lf.id = t.parentid
		LEFT JOIN book_admin b ON b.id = t.task_user_id`+where+`
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
		var taskType int
		var info, fileFolder, ledFolder string
		if err := rows.Scan(&it.TaskID, &it.TaskName, &taskType, &info, &fileFolder, &ledFolder,
			&mask, &it.PlayTime,
			&it.State, &it.ProjectState, &it.StartDate, &it.EndDate,
			&it.Terminals, &it.DisableDay,
			&it.OwnerUserID, &it.OwnerName); err != nil {
			return nil, err
		}
		it.Module, it.FolderName = categoryOf(taskType, info, fileFolder, ledFolder)
		it.Category = categoryText(ctx, it.Module, it.FolderName)
		it.RunStatus = runStatusOf(it.State, viewDate, today, nowClock, it.PlayTime)
		i++
		it.Index = i
		it.Weekdays = parseWeekdays(mask)
		it.CycleText = i18n.CycleText(ctx, mask)
		it.StateText = i18n.TC(ctx, stateText(it.State))

		out.Items = append(out.Items, it)
	}
	return out, rows.Err()
}

// categoryOf 判断这条任务属于「任务管理」下的哪个模块，以及它在模块里归在哪。
//
// # 为什么不能只看 parentid
//
// 这一列原来直接显示 `filetaskfree.name`。对文件广播是对的，对**作息方案就是错的** ——
// 作息方案的条目按 `task.info`（方案名）归组，`parentid` 指着的那个 filetaskfree 行
// 只是建任务时填的默认值。现网数据里七条作息条目的 parentid 全是 1（admin），
// 于是「早读预备铃」的所属分类显示成「admin」，看的人根本认不出它属于哪个方案。
//
// # tasktype = 15 归谁
//
// 15 同时属于作息方案和文字语音，靠 info 分：作息方案的 info 是方案名（非空），
// 文字语音的是空串（契约 C-38）。这里的判断顺序保证了这一点 —— 先认 info 非空的
// 作息方案，剩下的 15 才落到文字语音。
//
// 返回 (模块名, 归属名)。归属名为空表示这个模块本来就不分组（终端功放/采播/文字语音）。
func categoryOf(taskType int, info, fileFolder, ledFolder string) (string, string) {
	switch {
	case (taskType == 1 || taskType == 15) && info != "":
		// 括号里放方案名 —— 这正是需求方要的「是哪个方案中的」
		return moduleBell, info
	case taskType == 2 || taskType == 7:
		return "文件广播", fileFolder
	case taskType == 5:
		return "终端功放", ""
	case taskType == 3:
		return "采播管理", ""
	case taskType == 15 || taskType == 17 || taskType == 19:
		return "文字语音", ""
	case taskType == 24 || taskType == 30:
		return "led播放", ledFolder
	default:
		// 不认识的 tasktype 不瞎猜，把号码摆出来 —— 比编一个模块名强
		return fmt.Sprintf("任务类型 %d", taskType), fileFolder
	}
}

// categoryText 拼成给人看的那一格。
//
// **只有作息方案带括号**：需求方要的是「是哪个方案中的」。文件广播、led播放
// 只写模块名 —— 它们括号里本来放的是任务分组名，而这一列问的是功能模块，
// 分组名另有 folderName 一列。
//
// 模块名要翻译（界面有中英文），括号里的方案名是用户自己起的，原样保留。
func categoryText(ctx context.Context, module, group string) string {
	m := i18n.TC(ctx, module)
	if module != moduleBell || group == "" {
		return m
	}
	return m + "（" + group + "）"
}

// executedOn 判断「所看那一天」这条任务到没到执行时间。
//
// 旧版 Browse_active_task_form.html:110~155 干的就是这件事：state=0 时把
// playtime 和当前时刻比 —— 没到点写「准备●」，过了点写「已执行」。
// 它只会看今天（加一个星期偏移），这里把同一条判据摊到任意一天上：
//
//	看的是过去的某天 → 那天早过完了，已执行
//	看的是今天       → 拿 playtime 和此刻比
//	看的是将来的某天 → 还没到，未执行
//
// ⚠ 用**服务器时钟**，不是浏览器时钟。广播到点不到点由服务器说了算，
// 客户端的表可能是歪的 —— 让界面跟着歪表走，看到的「已执行」就是假的。
// moduleBell 是「所属分类」里唯一带括号的那个模块，categoryOf / categoryText 共用。
const moduleBell = "作息方案"

// 状态列的五个取值。回给前端的是这些 key，文案与颜色由界面定。
const (
	StatusDone    = "done"    // 已执行
	StatusReady   = "ready"   // 准备执行
	StatusRunning = "running" // 正在执行
	StatusPaused  = "paused"  // 暂停
	StatusPlayNow = "playnow" // 立即执行
)

// runStatusOf 算看板状态列显示哪一个。
//
// 判据与旧版 Browse_active_task_form.html 同构 —— **只有 state = 0 才比时间**：
//
//	state = 1 → 正在执行     state = 2 → 暂停     state = 3 → 立即执行
//	state = 0 → 过了点「已执行」，没到点「准备执行」
//
// ⚠ state 是**此时此刻**的值，只有在看今天时才算数。星期选择器切到别的日子时，
// 拿它去说「上周二那条正在执行」是假的 —— 那时一律回到按日期/钟点判。
//
// ⚠ 不认识的 state 当 0 处理：宁可显示成「已执行 / 准备执行」，
// 也不要编一个界面上根本没有的状态出来。
func runStatusOf(state int, viewDate, today, nowClock, playTime string) string {
	if viewDate == today {
		switch state {
		case 1:
			return StatusRunning
		case 2:
			return StatusPaused
		case 3:
			return StatusPlayNow
		}
	}
	if executedOn(viewDate, today, nowClock, playTime) {
		return StatusDone
	}
	return StatusReady
}

func executedOn(viewDate, today, nowClock, playTime string) bool {
	switch {
	case viewDate < today:
		return true
	case viewDate > today:
		return false
	default:
		// playtime 已经是 HH:MM:SS（查询里 TIME_FORMAT 过），字符串比就够
		return playTime != "" && playTime <= nowClock
	}
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
