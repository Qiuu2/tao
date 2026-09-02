// Package remote 实现遥控任务（旧版 keytask_mapping）。
//
// # 一句话说清这张表
//
//	shortcutkeytask (id, keyid, mediaid, keyname)
//
// 一个遥控器按键号（keyid）绑一个名字（keyname）和**若干条任务**。
// 一个 keyid 对应多行，每行一条任务。按下遥控器某个键，后台就执行绑在它上面的任务。
//
// # ⚠ mediaid 这一列里装的是 taskid，不是 mediaid
//
// 这是本模块最要命的一点。旧版写入端 `keyset_task_mapping_msg` 的取值是：
//
//	SELECT taskid, taskname FROM task WHERE tasktype IN (2,15) AND info=''   ← 文件广播
//	SELECT taskid, taskname FROM task WHERE tasktype = 3                     ← 采播
//	SELECT taskid, taskname FROM task WHERE tasktype = 5 AND sec_task_id = 0 AND prepower = 0  ← 终端功放
//
// 拿到的是 **taskid**，POST 变量也叫 `task_map_id`，然后原样塞进了名为
// `mediaid` 的列。列名跟内容对不上，但红线禁止改表，只能照旧写。
//
// 真正的问题在读取端：旧版 keytask_mapping.php 的明细查询是
//
//	SELECT media.name, media.typeid FROM shortcutkeytask, media
//	 WHERE shortcutkeytask.mediaid = media.id AND shortcutkeytask.keyid = ?
//
// **拿 taskid 去 join media.id**（D-217）。两张表的 id 各自独立编号，
// join 上了就显示一个毫不相干的媒体名，join 不上就整行消失 ——
// 也就是说旧版这个页面配好之后，明细列里看到的东西是错的。
// 现网 task.taskid 从 70001 起、media.id 是小整数，所以实际表现是「明细永远空白」。
//
// 新版：写入沿用旧语义（存 taskid），读取改成 join `task` 表，明细才对得上。
//
// # keyid 的唯一性
//
// 旧版新增时查 `SELECT * FROM shortcutkeytask WHERE keyid = ?`，非空就拒绝 ——
// 也就是「一个按键号只能配一次」。但这条查重和随后的 INSERT 之间没有任何锁，
// 两个人同时提交同一个键号会双双写进去。表上的主键是
// (id, keyid, mediaid, keyname) 这种四列复合，拦不住。
// 新版用命名锁把查重和写入串起来（建唯一索引属 DDL，红线禁止）。
package remote

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/store"
)

type Service struct {
	db *sql.DB
}

func New(db *sql.DB) *Service { return &Service{db: db} }

var (
	ErrNotFound = errors.New("遥控任务不存在")
)

const (
	// keyname varchar(32)
	nameLimit = 32
	// 一个按键最多绑多少条任务。旧版无限制，但遥控器按一下要顺序执行，
	// 绑几十条既没有意义也会把后台队列塞满。
	maxTasks = 20
	// 遥控器按键号的合理范围。旧版不校验，负数和 0 都能存进去。
	minKey = 1
	maxKey = 999

	nameLock = "htweb_remote_keyid"
)

// 可绑定的任务类型。**与旧版 keyset_task_mapping.php 的三条 SQL 一一对应**，
// 不多不少 —— 多放一类进来，后台按遥控任务去执行会走到没实现的分支。
const (
	KindFile      = "file"      // 文件广播：tasktype IN (2,15) AND info=''
	KindCollect   = "collect"   // 采播：tasktype = 3
	KindAmplifier = "amplifier" // 终端功放：tasktype = 5 AND sec_task_id=0 AND prepower=0
)

// taskScope 返回某一类可绑任务的 WHERE 片段。
// 这是「哪些任务能绑到遥控键上」的唯一定义，列表、校验、选择框都用它。
func taskScope(kind string) (string, bool) {
	switch kind {
	case KindFile:
		return `(t.tasktype IN (2,15) AND COALESCE(t.info,'') = '')`, true
	case KindCollect:
		return `(t.tasktype = 3)`, true
	case KindAmplifier:
		return `(t.tasktype = 5 AND COALESCE(t.sec_task_id,0) = 0 AND COALESCE(t.prepower,0) = 0)`, true
	default:
		return "", false
	}
}

// anyTaskScope 是三类的并集。
func anyTaskScope() string {
	f, _ := taskScope(KindFile)
	c, _ := taskScope(KindCollect)
	a, _ := taskScope(KindAmplifier)
	return "(" + f + " OR " + c + " OR " + a + ")"
}

func kindOf(tasktype int, info string, secTaskID, prepower int) string {
	switch {
	case (tasktype == 2 || tasktype == 15) && info == "":
		return KindFile
	case tasktype == 3:
		return KindCollect
	case tasktype == 5 && secTaskID == 0 && prepower == 0:
		return KindAmplifier
	}
	return ""
}

func kindText(kind string) string {
	switch kind {
	case KindFile:
		return "文件广播"
	case KindCollect:
		return "采播"
	case KindAmplifier:
		return "终端功放"
	}
	return "未知类型"
}

// ---------- 列表 ----------

type Item struct {
	KeyID   int64  `json:"keyId"`
	KeyName string `json:"keyName"`
	// Tasks 是绑在这个键上的任务。列表页也带上，因为一个键通常只绑一两条，
	// 全部塞进列表比让用户逐个点开看更实用。
	Tasks []Task `json:"tasks"`
}

type Task struct {
	TaskID   int64  `json:"taskId"`
	TaskName string `json:"taskName"`
	Kind     string `json:"kind"`
	KindText string `json:"kindText"`
	// Missing 表示这条绑定指向的任务已经不存在了。
	// 旧版删任务时会 DELETE FROM terminalkeymaptask，但**没有清 shortcutkeytask**，
	// 所以现网大概率能看到这种悬空绑定。
	Missing bool `json:"missing"`
}

type ListResult struct {
	Items []Item
	Total int64
}

type Query struct {
	Keyword string
	Pager   store.Pager
}

func (s *Service) List(ctx context.Context, q Query) (*ListResult, error) {
	cond := &store.Cond{}
	if q.Keyword != "" {
		cond.Add(`k.keyname LIKE ? ESCAPE '\\'`, store.EscapeLike(q.Keyword))
	}
	where := cond.Where()
	args := cond.Args()

	// 一个 keyid 有多行，统计要 COUNT(DISTINCT keyid)
	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(DISTINCT k.keyid) FROM shortcutkeytask k"+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计遥控任务: %w", err)
	}

	// 排序固定按键号。这一页的行数天然很少（一个遥控器就那么几个键），
	// 不做多字段排序，省掉一套白名单。
	listArgs := append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, `
		SELECT k.keyid, MIN(k.keyname)
		FROM shortcutkeytask k`+where+`
		GROUP BY k.keyid ORDER BY k.keyid LIMIT ? OFFSET ?`, listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询遥控任务: %w", err)
	}
	defer rs.Close()

	items := make([]Item, 0, q.Pager.PageSize)
	keys := make([]int64, 0, q.Pager.PageSize)
	for rs.Next() {
		var it Item
		if err := rs.Scan(&it.KeyID, &it.KeyName); err != nil {
			return nil, fmt.Errorf("扫描遥控任务行: %w", err)
		}
		it.Tasks = []Task{}
		items = append(items, it)
		keys = append(keys, it.KeyID)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}

	if len(keys) > 0 {
		byKey, err := s.tasksByKey(ctx, keys)
		if err != nil {
			return nil, err
		}
		for i := range items {
			if ts, ok := byKey[items[i].KeyID]; ok {
				items[i].Tasks = ts
			}
		}
	}
	return &ListResult{Items: items, Total: total}, nil
}

// tasksByKey 一次查出这批按键绑的全部任务，不做 N+1。
//
// LEFT JOIN task：绑定指向的任务可能已经被删了，
// 内连接会让这些悬空行静默消失 —— 那正是旧版看不见问题的原因。
func (s *Service) tasksByKey(ctx context.Context, keys []int64) (map[int64][]Task, error) {
	ph, args := placeholders(keys)
	rs, err := s.db.QueryContext(ctx, `
		SELECT k.keyid, k.mediaid, t.taskid IS NOT NULL,
		       COALESCE(t.taskname,''), COALESCE(t.tasktype,0),
		       COALESCE(t.info,''), COALESCE(t.sec_task_id,0), COALESCE(t.prepower,0)
		FROM shortcutkeytask k
		LEFT JOIN task t ON t.taskid = k.mediaid
		WHERE k.keyid IN (`+ph+`)
		ORDER BY k.keyid, k.id`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询遥控任务明细: %w", err)
	}
	defer rs.Close()

	out := map[int64][]Task{}
	for rs.Next() {
		var keyID int64
		var t Task
		var exists bool
		var tasktype, secTaskID, prepower int
		var info string
		if err := rs.Scan(&keyID, &t.TaskID, &exists, &t.TaskName,
			&tasktype, &info, &secTaskID, &prepower); err != nil {
			return nil, err
		}
		t.Missing = !exists
		if t.Missing {
			t.TaskName = "(任务已删除)"
			t.KindText = "—"
		} else {
			t.Kind = kindOf(tasktype, info, secTaskID, prepower)
			t.KindText = kindText(t.Kind)
		}
		out[keyID] = append(out[keyID], t)
	}
	return out, rs.Err()
}

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

// ---------- 详情 ----------

func (s *Service) Get(ctx context.Context, keyID int64) (*Item, error) {
	var it Item
	err := s.db.QueryRowContext(ctx,
		`SELECT keyid, MIN(keyname) FROM shortcutkeytask WHERE keyid = ? GROUP BY keyid`, keyID).
		Scan(&it.KeyID, &it.KeyName)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询遥控任务: %w", err)
	}
	byKey, err := s.tasksByKey(ctx, []int64{keyID})
	if err != nil {
		return nil, err
	}
	it.Tasks = byKey[keyID]
	if it.Tasks == nil {
		it.Tasks = []Task{}
	}
	return &it, nil
}

// ---------- 新建 / 修改 ----------

type Input struct {
	KeyID   int64
	KeyName string
	TaskIDs []int64
}

func (s *Service) validate(ctx context.Context, in *Input) error {
	in.KeyName = strings.TrimSpace(in.KeyName)
	if in.KeyName == "" {
		return fmt.Errorf("遥控任务名称不能为空")
	}
	if len(in.KeyName) > nameLimit {
		return fmt.Errorf("遥控任务名称过长：按 UTF-8 计 %d 字节，上限 %d 字节（约 10 个汉字）",
			len(in.KeyName), nameLimit)
	}
	// 旧版不校验键号范围，0 和负数都能存
	if in.KeyID < minKey || in.KeyID > maxKey {
		return fmt.Errorf("遥控键号必须在 %d ~ %d 之间", minKey, maxKey)
	}
	if len(in.TaskIDs) == 0 {
		return fmt.Errorf("请至少选择一条任务")
	}
	if len(in.TaskIDs) > maxTasks {
		return fmt.Errorf("一个遥控键最多绑定 %d 条任务", maxTasks)
	}
	seen := map[int64]bool{}
	for _, id := range in.TaskIDs {
		if id <= 0 {
			return fmt.Errorf("任务列表里有非法的任务 ID")
		}
		if seen[id] {
			return fmt.Errorf("任务列表里有重复的任务")
		}
		seen[id] = true
	}

	// 逐条确认任务存在**且属于可绑定的三类**。
	// 旧版一条都不查：任务删掉之后绑定还在，遥控器按下去什么也不会发生。
	ph, args := placeholders(in.TaskIDs)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM task t WHERE t.taskid IN (`+ph+`) AND `+anyTaskScope(), args...).
		Scan(&n); err != nil {
		return fmt.Errorf("校验任务: %w", err)
	}
	if n != len(in.TaskIDs) {
		return fmt.Errorf("任务列表里有不存在、或不能绑到遥控键上的任务（只能选文件广播、采播、终端功放）")
	}
	return nil
}

// checkKeyFree 一个键号只能配一次（沿用旧版语义）。
func (s *Service) checkKeyFree(ctx context.Context, keyID int64, exclude int64) error {
	q := `SELECT keyid FROM shortcutkeytask WHERE keyid = ?`
	args := []interface{}{keyID}
	if exclude > 0 {
		q += ` AND keyid <> ?`
		args = append(args, exclude)
	}
	q += ` LIMIT 1`
	var v int64
	err := s.db.QueryRowContext(ctx, q, args...).Scan(&v)
	if err == nil {
		return fmt.Errorf("遥控键号 %d 已经被占用", keyID)
	}
	if errors.Is(err, sql.ErrNoRows) {
		return nil
	}
	return fmt.Errorf("遥控键号查重: %w", err)
}

func (s *Service) Create(ctx context.Context, in Input) error {
	if err := s.validate(ctx, &in); err != nil {
		return err
	}
	// 表上那个 (id,keyid,mediaid,keyname) 复合主键拦不住重复键号，用命名锁串行化
	unlock, err := store.Lock(ctx, s.db, nameLock)
	if err != nil {
		return err
	}
	defer unlock()

	if err := s.checkKeyFree(ctx, in.KeyID, 0); err != nil {
		return err
	}
	return s.writeRows(ctx, in)
}

func (s *Service) Update(ctx context.Context, keyID int64, in Input) error {
	if _, err := s.Get(ctx, keyID); err != nil {
		return err
	}
	if err := s.validate(ctx, &in); err != nil {
		return err
	}

	unlock, err := store.Lock(ctx, s.db, nameLock)
	if err != nil {
		return err
	}
	defer unlock()

	// 允许改键号，但新键号不能撞上别人的
	if in.KeyID != keyID {
		if err := s.checkKeyFree(ctx, in.KeyID, 0); err != nil {
			return err
		}
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 旧版是「先 delete 再逐条 insert」但**没有事务**：
	// delete 成功、insert 中途失败，这个遥控键就凭空消失了。
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM shortcutkeytask WHERE keyid = ?`, keyID); err != nil {
		return fmt.Errorf("清理遥控任务: %w", err)
	}
	if err := insertRows(ctx, tx, in); err != nil {
		return err
	}
	return tx.Commit()
}

func (s *Service) writeRows(ctx context.Context, in Input) error {
	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()
	if err := insertRows(ctx, tx, in); err != nil {
		return err
	}
	return tx.Commit()
}

// insertRows 每条任务写一行。keyname 在每行都重复一遍 —— 表结构如此，不能改。
func insertRows(ctx context.Context, tx *sql.Tx, in Input) error {
	for _, taskID := range in.TaskIDs {
		// 列名叫 mediaid，存的是 taskid。原因见包注释，别「顺手修正」。
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO shortcutkeytask (keyid, mediaid, keyname) VALUES (?,?,?)`,
			in.KeyID, taskID, in.KeyName); err != nil {
			return fmt.Errorf("写入遥控任务: %w", err)
		}
	}
	return nil
}

// ---------- 删除 ----------

func (s *Service) Delete(ctx context.Context, keyIDs []int64) (int, error) {
	if len(keyIDs) == 0 {
		return 0, fmt.Errorf("请先选择要删除的遥控任务")
	}
	ph, args := placeholders(keyIDs)
	res, err := s.db.ExecContext(ctx,
		`DELETE FROM shortcutkeytask WHERE keyid IN (`+ph+`)`, args...)
	if err != nil {
		return 0, fmt.Errorf("删除遥控任务: %w", err)
	}
	n, _ := res.RowsAffected()
	return int(n), nil
}

// ---------- 可绑任务选择框 ----------

type PickTask struct {
	TaskID   int64  `json:"taskId"`
	TaskName string `json:"taskName"`
	Kind     string `json:"kind"`
	KindText string `json:"kindText"`
	// UsedBy 是已经把这条任务绑走的遥控键号，0 表示没被绑。
	// 旧版允许同一条任务绑到多个键上，这里不阻断，只是标出来。
	UsedBy int64 `json:"usedBy"`
}

// PickTasks 列出可以绑到遥控键上的任务。
//
// kind 为空表示三类都要。普通用户只看自己的任务 —— 与任务模块同一套口径。
func (s *Service) PickTasks(ctx context.Context, u *auth.User, kind, keyword string) ([]PickTask, error) {
	scope := anyTaskScope()
	if kind != "" {
		sc, ok := taskScope(kind)
		if !ok {
			return nil, fmt.Errorf("任务类型只能是 file、collect 或 amplifier")
		}
		scope = sc
	}
	cond := &store.Cond{}
	cond.Add(scope)
	if !u.IsAdmin {
		cond.Add(`COALESCE(t.task_user_id,0) = ?`, u.ID)
	}
	if keyword != "" {
		cond.Add(`t.taskname LIKE ? ESCAPE '\\'`, store.EscapeLike(keyword))
	}

	rs, err := s.db.QueryContext(ctx, `
		SELECT t.taskid, COALESCE(t.taskname,''), COALESCE(t.tasktype,0),
		       COALESCE(t.info,''), COALESCE(t.sec_task_id,0), COALESCE(t.prepower,0),
		       COALESCE((SELECT k.keyid FROM shortcutkeytask k
		                  WHERE k.mediaid = t.taskid ORDER BY k.id LIMIT 1), 0)
		FROM task t`+cond.Where()+`
		ORDER BY t.taskid LIMIT 500`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询可绑任务: %w", err)
	}
	defer rs.Close()

	out := []PickTask{}
	for rs.Next() {
		var p PickTask
		var tasktype, secTaskID, prepower int
		var info string
		if err := rs.Scan(&p.TaskID, &p.TaskName, &tasktype, &info,
			&secTaskID, &prepower, &p.UsedBy); err != nil {
			return nil, err
		}
		p.Kind = kindOf(tasktype, info, secTaskID, prepower)
		p.KindText = kindText(p.Kind)
		out = append(out, p)
	}
	return out, rs.Err()
}
