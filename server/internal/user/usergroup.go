// Package user 实现用户与用户组管理（手册业务域四、五，F-14 ~ F-24）。
package user

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/store"
)

var (
	ErrNameUsed      = errors.New("名称已被使用")
	ErrSystemGroup   = errors.New("系统用户组受保护")
	ErrSystemUser    = errors.New("系统用户 admin 受保护")
	ErrNotFound      = errors.New("对象不存在")
	ErrNotRegistered = errors.New("服务器未注册，禁止新建或删除用户")
	ErrNoPermission  = errors.New("无权操作")
	ErrWindExhausted = errors.New("分控 ID 已分配完，请先释放或提高授权数")
)

// SystemGroupID 是系统用户组，拥有全部权限，禁止删除（BR-90 / BR-99）。
const SystemGroupID = 1

// SystemUserID 是超级用户 admin，可改不可删（BR-83 / BR-126）。
const SystemUserID = 1

type Service struct {
	db     *sql.DB
	files  FileRemover
	notify FolderNotifier
}

func New(db *sql.DB) *Service { return &Service{db: db} }

// ---------- level 的两位复合编码 ----------
//
// usergroup.level 的列注释写的是「用户组级别 最大值5」，但这是过时的。
// 界面上的下拉是 10~109，且任务优先级换算逻辑用的是 floor(level/10) 与 level%10 ——
// 实际语义是两位复合值：
//
//	十位 = 组级别（1~9）
//	个位 = 组内任务优先级基数（0~9）
//
// 后台 C 服务按此解析任务优先级，**必须原样保持**（契约 C-28）。
// 新版在界面上把它拆成两个控件录入，提交时再合成，存储格式完全不变。

// SplitLevel 把复合值拆成「组级别 / 优先级基数」。
func SplitLevel(level int) (groupLevel, priorityBase int) {
	return level / 10, level % 10
}

// JoinLevel 把两个分量合成复合值。
func JoinLevel(groupLevel, priorityBase int) int {
	return groupLevel*10 + priorityBase
}

// ---------- F-14 用户组列表 ----------

type Group struct {
	ID           int64       `json:"id"`
	Name         string      `json:"name"`
	Info         string      `json:"info"`
	Level        int         `json:"level"`
	GroupLevel   int         `json:"groupLevel"`
	PriorityBase int         `json:"priorityBase"`
	System       bool        `json:"system"`
	UserCount    int64       `json:"userCount"`
	Rights       auth.Rights `json:"rights"`
	CanModify    bool        `json:"canModify"`
	CanDelete    bool        `json:"canDelete"`
}

var groupOrderWhitelist = map[string]string{
	"id":       "usergroup.id",
	"name":     "usergroup.name",
	"level":    "usergroup.level",
	"userpriv": "usergroup.userpriv",
}

type GroupListQuery struct {
	Keyword string
	OrderBy string
	Order   string
	Pager   store.Pager
}

func (s *Service) ListGroups(ctx context.Context, q GroupListQuery) ([]Group, int64, error) {
	cond := &store.Cond{}
	if q.Keyword != "" {
		cond.Add(`usergroup.name LIKE ? ESCAPE '\\'`, store.EscapeLike(q.Keyword))
	}
	where := cond.Where()
	args := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM usergroup"+where, args...).Scan(&total); err != nil {
		return nil, 0, fmt.Errorf("统计用户组: %w", err)
	}

	// 旧版排序写成 ORDER BY '$searchsequence' desc —— 加了引号变成按常量排序，
	// 排序下拉永远不生效（缺陷 D-40）。这里走白名单映射。
	order := store.OrderBy(groupOrderWhitelist, q.OrderBy, q.Order, "usergroup.id DESC")

	rows, err := s.db.QueryContext(ctx, `
		SELECT id, name, COALESCE(info,''), taskpriv, terminalpriv, mediapriv, userpriv,
		       serverpriv, folderpriv, terminalgrouppriv, alarmgrouppriv, bellpriv,
		       admpriv, telephonepriv, powerplay, ttspriv, level
		FROM usergroup`+where+" ORDER BY "+order+" LIMIT ? OFFSET ?",
		append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())...)
	if err != nil {
		return nil, 0, fmt.Errorf("查询用户组: %w", err)
	}
	defer rows.Close()

	var out []Group
	for rows.Next() {
		var g Group
		r := &g.Rights
		if err := rows.Scan(&g.ID, &g.Name, &g.Info,
			&r.TaskPriv, &r.TerminalPriv, &r.MediaPriv, &r.UserPriv, &r.ServerPriv,
			&r.FolderPriv, &r.TerminalGroupPriv, &r.AlarmGroupPriv, &r.BellPriv,
			&r.AdmPriv, &r.TelephonePriv, &r.PowerPlay, &r.TtsPriv, &g.Level); err != nil {
			return nil, 0, err
		}
		g.GroupLevel, g.PriorityBase = SplitLevel(g.Level)
		g.System = g.ID == SystemGroupID
		g.CanModify = true // 系统组也可改描述，具体字段级保护在 UpdateGroup 内
		g.CanDelete = !g.System
		out = append(out, g)
	}
	if err := rows.Err(); err != nil {
		return nil, 0, err
	}

	// 用户数一次聚合取出，避免每行单查（旧版是 N+1）
	counts, err := s.groupUserCounts(ctx)
	if err != nil {
		return nil, 0, err
	}
	for i := range out {
		out[i].UserCount = counts[out[i].ID]
	}
	return out, total, nil
}

func (s *Service) groupUserCounts(ctx context.Context) (map[int64]int64, error) {
	rows, err := s.db.QueryContext(ctx,
		`SELECT usergroupid, COUNT(*) FROM book_admin GROUP BY usergroupid`)
	if err != nil {
		return nil, fmt.Errorf("统计组内用户数: %w", err)
	}
	defer rows.Close()
	m := make(map[int64]int64)
	for rows.Next() {
		var id, n int64
		if err := rows.Scan(&id, &n); err != nil {
			return nil, err
		}
		m[id] = n
	}
	return m, rows.Err()
}

// ---------- F-15 新建用户组 ----------

type GroupInput struct {
	Name         string
	Info         string
	GroupLevel   int
	PriorityBase int
	Rights       auth.Rights
}

func (in GroupInput) validate() error {
	name := strings.TrimSpace(in.Name)
	if name == "" {
		return fmt.Errorf("用户组名称不能为空")
	}
	if len(name) > 128 {
		return fmt.Errorf("用户组名称过长")
	}
	if len(in.Info) > 128 {
		return fmt.Errorf("描述过长")
	}
	if in.GroupLevel < 1 || in.GroupLevel > 9 {
		return fmt.Errorf("组级别必须在 1~9 之间")
	}
	if in.PriorityBase < 0 || in.PriorityBase > 9 {
		return fmt.Errorf("优先级基数必须在 0~9 之间")
	}
	return nil
}

func (s *Service) CreateGroup(ctx context.Context, in GroupInput) (int64, error) {
	if err := in.validate(); err != nil {
		return 0, err
	}
	name := strings.TrimSpace(in.Name)

	// usergroup 没有 name 唯一索引，且不允许新建索引（R1 红线），
	// 用应用级命名锁串行化「查重 + 写入」
	unlock, err := s.lock(ctx, "htweb_usergroup_create")
	if err != nil {
		return 0, err
	}
	defer unlock()

	var exists int
	err = s.db.QueryRowContext(ctx,
		`SELECT 1 FROM usergroup WHERE name = ? LIMIT 1`, name).Scan(&exists)
	if err == nil {
		return 0, ErrNameUsed
	}
	if !errors.Is(err, sql.ErrNoRows) {
		return 0, fmt.Errorf("重名校验: %w", err)
	}

	r := in.Rights
	res, err := s.db.ExecContext(ctx, `
		INSERT INTO usergroup (name, info, taskpriv, terminalpriv, mediapriv, userpriv,
		                       serverpriv, folderpriv, terminalgrouppriv, alarmgrouppriv,
		                       bellpriv, admpriv, telephonepriv, powerplay, level, ttspriv)
		VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)`,
		name, in.Info, r.TaskPriv, r.TerminalPriv, r.MediaPriv, r.UserPriv,
		r.ServerPriv, r.FolderPriv, r.TerminalGroupPriv, r.AlarmGroupPriv,
		r.BellPriv, r.AdmPriv, r.TelephonePriv, r.PowerPlay,
		JoinLevel(in.GroupLevel, in.PriorityBase), r.TtsPriv)
	if err != nil {
		return 0, fmt.Errorf("新建用户组: %w", err)
	}
	return res.LastInsertId()
}

// ---------- F-16 修改用户组 ----------

// PriorityRecalc 记录改组级别时对任务优先级的重算结果。
type PriorityRecalc struct {
	AffectedUsers int `json:"affectedUsers"`
	AffectedTasks int `json:"affectedTasks"`
}

// UpdateGroup 修改用户组，并联动重算组内用户的任务优先级。
//
// # 重算规则（BR-97）
//
//	新优先级 = 新 level + (旧优先级 % 10)
//	仅对「旧优先级的十位 == 旧 level 的十位」的任务生效
//
// # 旧版的三个缺陷
//
//	D-42 系统组保护只做了一半：只校验权限是否全为 1，
//	     名称、描述、level 仍可被随意改
//	D-43 取组内用户用的是 if 而非 while，**只有第一个用户的任务优先级被重算**
//	D-44 循环内 $get_level 被后续行覆盖，多用户时判断基准漂移
func (s *Service) UpdateGroup(ctx context.Context, id int64, in GroupInput) (*PriorityRecalc, error) {
	if err := in.validate(); err != nil {
		return nil, err
	}
	name := strings.TrimSpace(in.Name)

	var oldLevel int
	var oldName string
	err := s.db.QueryRowContext(ctx,
		`SELECT name, level FROM usergroup WHERE id = ? LIMIT 1`, id).Scan(&oldName, &oldLevel)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询用户组: %w", err)
	}

	newLevel := JoinLevel(in.GroupLevel, in.PriorityBase)

	// 系统组保护：名称、级别、13 项权限全部只读，仅描述可改（BR-96）
	if id == SystemGroupID {
		if name != oldName || newLevel != oldLevel {
			return nil, fmt.Errorf("%w：名称与级别不可修改", ErrSystemGroup)
		}
		if !allRightsGranted(in.Rights) {
			return nil, fmt.Errorf("%w：权限必须保持全部开启", ErrSystemGroup)
		}
	}

	unlock, err := s.lock(ctx, "htweb_usergroup_create")
	if err != nil {
		return nil, err
	}
	defer unlock()

	var exists int
	err = s.db.QueryRowContext(ctx,
		`SELECT 1 FROM usergroup WHERE id <> ? AND name = ? LIMIT 1`, id, name).Scan(&exists)
	if err == nil {
		return nil, ErrNameUsed
	}
	if !errors.Is(err, sql.ErrNoRows) {
		return nil, fmt.Errorf("重名校验: %w", err)
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	recalc := &PriorityRecalc{}
	if newLevel != oldLevel {
		if recalc, err = recalcPriorities(ctx, tx, id, oldLevel, newLevel); err != nil {
			return nil, err
		}
	}

	r := in.Rights
	if _, err := tx.ExecContext(ctx, `
		UPDATE usergroup SET name=?, info=?, taskpriv=?, terminalpriv=?, mediapriv=?,
		       userpriv=?, serverpriv=?, folderpriv=?, terminalgrouppriv=?, alarmgrouppriv=?,
		       bellpriv=?, admpriv=?, telephonepriv=?, powerplay=?, level=?, ttspriv=?
		WHERE id = ?`,
		name, in.Info, r.TaskPriv, r.TerminalPriv, r.MediaPriv, r.UserPriv,
		r.ServerPriv, r.FolderPriv, r.TerminalGroupPriv, r.AlarmGroupPriv,
		r.BellPriv, r.AdmPriv, r.TelephonePriv, r.PowerPlay, newLevel, r.TtsPriv, id); err != nil {
		return nil, fmt.Errorf("更新用户组: %w", err)
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return recalc, nil
}

// recalcPriorities 重算某个组内**全部**用户的任务优先级。
func recalcPriorities(ctx context.Context, tx *sql.Tx, groupID int64, oldLevel, newLevel int) (*PriorityRecalc, error) {
	// 关键：用 while 语义遍历组内每一个用户。
	// 旧代码这里用的是 if，只处理了第一个用户（缺陷 D-43）。
	rows, err := tx.QueryContext(ctx, `SELECT id FROM book_admin WHERE usergroupid = ?`, groupID)
	if err != nil {
		return nil, fmt.Errorf("查询组内用户: %w", err)
	}
	var userIDs []int64
	for rows.Next() {
		var uid int64
		if err := rows.Scan(&uid); err != nil {
			rows.Close()
			return nil, err
		}
		userIDs = append(userIDs, uid)
	}
	rows.Close()
	if err := rows.Err(); err != nil {
		return nil, err
	}

	out := &PriorityRecalc{AffectedUsers: len(userIDs)}
	oldTens := oldLevel / 10

	for _, uid := range userIDs {
		trs, err := tx.QueryContext(ctx,
			`SELECT taskid, priority FROM task WHERE task_user_id = ?`, uid)
		if err != nil {
			return nil, fmt.Errorf("查询用户任务: %w", err)
		}
		type upd struct {
			taskID      int64
			newPriority int
		}
		var updates []upd
		for trs.Next() {
			var taskID int64
			var priority int
			if err := trs.Scan(&taskID, &priority); err != nil {
				trs.Close()
				return nil, err
			}
			// 只重算「优先级十位与旧组级别一致」的任务。
			// 旧代码在循环里复用被覆盖的变量做这个判断，基准会漂移（缺陷 D-44）。
			if priority/10 == oldTens {
				updates = append(updates, upd{taskID, newLevel + priority%10})
			}
		}
		trs.Close()
		if err := trs.Err(); err != nil {
			return nil, err
		}

		for _, u := range updates {
			if _, err := tx.ExecContext(ctx,
				`UPDATE task SET priority = ? WHERE taskid = ?`, u.newPriority, u.taskID); err != nil {
				return nil, fmt.Errorf("更新任务优先级: %w", err)
			}
			out.AffectedTasks++
		}
	}
	return out, nil
}

func allRightsGranted(r auth.Rights) bool {
	return r.TaskPriv == 1 && r.TerminalPriv == 1 && r.MediaPriv == 1 && r.UserPriv == 1 &&
		r.ServerPriv == 1 && r.FolderPriv == 1 && r.TerminalGroupPriv == 1 &&
		r.AlarmGroupPriv == 1 && r.BellPriv == 1 && r.AdmPriv == 1 &&
		r.TelephonePriv == 1 && r.PowerPlay == 1 && r.TtsPriv == 1
}

func (s *Service) lock(ctx context.Context, name string) (func(), error) {
	var ok sql.NullInt64
	if err := s.db.QueryRowContext(ctx, `SELECT GET_LOCK(?, 5)`, name).Scan(&ok); err != nil {
		return nil, fmt.Errorf("获取命名锁: %w", err)
	}
	if !ok.Valid || ok.Int64 != 1 {
		return nil, fmt.Errorf("操作繁忙，请稍后重试")
	}
	return func() {
		_, _ = s.db.ExecContext(context.Background(), `SELECT RELEASE_LOCK(?)`, name)
	}, nil
}
