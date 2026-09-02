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

// ---------- F-18 用户列表 ----------

type User struct {
	ID            int64  `json:"id"`
	Username      string `json:"username"`
	Enable        int    `json:"enable"`
	EnableText    string `json:"enableText"`
	UsergroupID   int64  `json:"usergroupId"`
	UsergroupName string `json:"usergroupName"`
	Info          string `json:"info"`
	Fullname      string `json:"fullname"`
	CtrlWind      int    `json:"ctrlwind"`
	SubWind       int    `json:"subwind"`
	CameraWind    int    `json:"camerawind"`
	TerminalCount int64  `json:"terminalCount"`
	CanModify     bool   `json:"canModify"`
	CanDelete     bool   `json:"canDelete"`
}

var userOrderWhitelist = map[string]string{
	"id":       "book_admin.id",
	"username": "book_admin.username",
}

type UserListQuery struct {
	Keyword string
	OrderBy string
	Order   string
	Pager   store.Pager
}

// ListUsers 查询用户列表。
//
// 两条可见范围规则（BR-106 / BR-107）：
//   - 列表恒不显示 admin
//   - 只有 book_admin.id = 1 的用户能看到全部用户，其他用户只能看到自己
func (s *Service) ListUsers(ctx context.Context, cur *auth.User, q UserListQuery) ([]User, int64, error) {
	cond := &store.Cond{}
	cond.Add("book_admin.username <> 'admin'")
	if cur.ID != SystemUserID {
		cond.Add("book_admin.id = ?", cur.ID)
	}
	if q.Keyword != "" {
		cond.Add(`book_admin.username LIKE ? ESCAPE '\\'`, store.EscapeLike(q.Keyword))
	}
	where := cond.Where()
	args := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM book_admin"+where, args...).Scan(&total); err != nil {
		return nil, 0, fmt.Errorf("统计用户: %w", err)
	}

	order := store.OrderBy(userOrderWhitelist, q.OrderBy, q.Order, "book_admin.id DESC")

	// 用户组名用 LEFT JOIN 取。
	// 旧版每行单查一次 usergroup（N+1），且用内连接 ——
	// 用户组被删后该用户会整行消失。
	rows, err := s.db.QueryContext(ctx, `
		SELECT book_admin.id, book_admin.username, book_admin.enable, book_admin.usergroupid,
		       COALESCE(usergroup.name,''), COALESCE(book_admin.info,''), COALESCE(book_admin.fullname,''),
		       COALESCE(book_admin.ctrlwind,0), COALESCE(book_admin.subwind,0), COALESCE(book_admin.camerawind,0)
		FROM book_admin
		LEFT JOIN usergroup ON usergroup.id = book_admin.usergroupid`+where+
		" ORDER BY "+order+" LIMIT ? OFFSET ?",
		append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())...)
	if err != nil {
		return nil, 0, fmt.Errorf("查询用户: %w", err)
	}
	defer rows.Close()

	var out []User
	for rows.Next() {
		var u User
		if err := rows.Scan(&u.ID, &u.Username, &u.Enable, &u.UsergroupID,
			&u.UsergroupName, &u.Info, &u.Fullname,
			&u.CtrlWind, &u.SubWind, &u.CameraWind); err != nil {
			return nil, 0, err
		}
		if u.UsergroupName == "" {
			u.UsergroupName = "(用户组已删除)"
		}
		u.EnableText = map[bool]string{true: "启用", false: "停用"}[u.Enable == 1]
		u.CanModify = true
		u.CanDelete = u.ID != SystemUserID
		out = append(out, u)
	}
	if err := rows.Err(); err != nil {
		return nil, 0, err
	}

	counts, err := s.terminalCounts(ctx)
	if err != nil {
		return nil, 0, err
	}
	for i := range out {
		out[i].TerminalCount = counts[out[i].ID]
	}
	return out, total, nil
}

func (s *Service) terminalCounts(ctx context.Context) (map[int64]int64, error) {
	rows, err := s.db.QueryContext(ctx,
		`SELECT userid, COUNT(*) FROM userterminal GROUP BY userid`)
	if err != nil {
		return nil, fmt.Errorf("统计用户终端数: %w", err)
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

// ---------- 分控 ID 自动分配 ----------
//
// 三类分控各有固定的取值区间（BR-112 / 契约 C-29），分控软件按区间识别自身身份：
//
//	ctrlwind    手机分控    1    ~ N
//	subwind     分控软件    1001 ~ 1000+N
//	camerawind  监控软件    2001 ~ 2000+N
//
// N = serverbaseparam.ctrlterminalcount（并发分控授权数）。
//
// 旧版实现有三个问题：
//   - D-53 查空位与写入两步之间无并发保护，两人同时建用户会分到同一个 ID
//   - D-54 用 for 循环逐个 SELECT，最多 N 次查询
//   - D-62 subwind 分支就地覆盖了 $get_count，导致 camerawind 的基数算错

type windKind struct {
	column string
	base   int // 区间起点
}

var (
	windCtrl   = windKind{"ctrlwind", 1}
	windSub    = windKind{"subwind", 1001}
	windCamera = windKind{"camerawind", 2001}
)

// allocWind 在区间内找第一个未占用的编号。
// 一次性把已占用的值全查出来在内存里比对，避免 N 次查询。
func (s *Service) allocWind(ctx context.Context, k windKind, capacity int, excludeUserID int64) (int, error) {
	if capacity <= 0 {
		capacity = 1
	}
	end := k.base + capacity - 1

	q := fmt.Sprintf(`SELECT %s FROM book_admin WHERE %s BETWEEN ? AND ?`, k.column, k.column)
	args := []interface{}{k.base, end}
	if excludeUserID > 0 {
		q += " AND id <> ?"
		args = append(args, excludeUserID)
	}

	used := make(map[int]bool)
	if err := s.collect(ctx, q, args, func(rows *sql.Rows) error {
		var v sql.NullInt64
		if err := rows.Scan(&v); err != nil {
			return err
		}
		if v.Valid {
			used[int(v.Int64)] = true
		}
		return nil
	}); err != nil {
		return 0, err
	}

	for v := k.base; v <= end; v++ {
		if !used[v] {
			return v, nil
		}
	}
	return 0, ErrWindExhausted
}

func (s *Service) windCapacity(ctx context.Context) int {
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(ctrlterminalcount,0) FROM serverbaseparam LIMIT 1`).Scan(&n); err != nil || n <= 0 {
		return 4 // 现网默认值
	}
	return n
}

func (s *Service) registerFlag(ctx context.Context) int {
	var f int
	_ = s.db.QueryRowContext(ctx, `SELECT registerflag FROM serverbaseparam LIMIT 1`).Scan(&f)
	return f
}

// ---------- F-19 新建用户 ----------

type TerminalBind struct {
	TerminalID int64 `json:"terminalId"`
	GroupID    int64 `json:"groupId"`
}

type CreateUserInput struct {
	Username         string
	Password         string
	ConfirmPassword  string
	UsergroupID      int64
	Info             string
	EnableCtrlWind   bool
	EnableSubWind    bool
	EnableCameraWind bool
	Serials          []string
	Terminals        []TerminalBind
}

type CreateUserResult struct {
	ID             int64 `json:"id"`
	CtrlWind       int   `json:"ctrlwind"`
	SubWind        int   `json:"subwind"`
	CameraWind     int   `json:"camerawind"`
	TaskFolderID   int64 `json:"taskFolderId"`
	BoundTerminals int   `json:"boundTerminals"`
}

func (s *Service) CreateUser(ctx context.Context, in CreateUserInput) (*CreateUserResult, error) {
	// 未注册状态禁止新建用户（BR-108）。旧版只靠前端 JS 隐藏按钮，服务端不拦。
	if f := s.registerFlag(ctx); f != 1 && f != 2 {
		return nil, ErrNotRegistered
	}

	username := strings.TrimSpace(in.Username)
	if username == "" || len(username) > 50 {
		return nil, fmt.Errorf("用户名长度非法")
	}
	if in.Password == "" || len(in.Password) > 20 {
		return nil, fmt.Errorf("密码长度必须在 1~20 之间")
	}
	if in.Password != in.ConfirmPassword {
		return nil, fmt.Errorf("两次输入的密码不一致")
	}
	if in.UsergroupID <= 0 {
		return nil, fmt.Errorf("必须指定用户组")
	}

	unlock, err := s.lock(ctx, "htweb_user_create")
	if err != nil {
		return nil, err
	}
	defer unlock()

	var exists int
	err = s.db.QueryRowContext(ctx,
		`SELECT 1 FROM book_admin WHERE username = ? LIMIT 1`, username).Scan(&exists)
	if err == nil {
		return nil, ErrNameUsed
	}
	if !errors.Is(err, sql.ErrNoRows) {
		return nil, fmt.Errorf("重名校验: %w", err)
	}

	capacity := s.windCapacity(ctx)
	ctrl, sub, cam := 0, 0, 0
	if in.EnableCtrlWind {
		if ctrl, err = s.allocWind(ctx, windCtrl, capacity, 0); err != nil {
			return nil, fmt.Errorf("分配手机分控 ID: %w", err)
		}
	}
	if in.EnableSubWind {
		if sub, err = s.allocWind(ctx, windSub, capacity, 0); err != nil {
			return nil, fmt.Errorf("分配分控软件 ID: %w", err)
		}
	}
	if in.EnableCameraWind {
		if cam, err = s.allocWind(ctx, windCamera, capacity, 0); err != nil {
			return nil, fmt.Errorf("分配监控软件 ID: %w", err)
		}
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 密码存 32 位小写 MD5，与旧系统一致（契约 C-02）
	res, err := tx.ExecContext(ctx, `
		INSERT INTO book_admin (username, userpwd, usergroupid, info, ctrlwind, subwind, camerawind)
		VALUES (?,?,?,?,?,?,?)`,
		username, auth.MD5Hex(in.Password), in.UsergroupID, in.Info, ctrl, sub, cam)
	if err != nil {
		return nil, fmt.Errorf("新建用户: %w", err)
	}
	// 用 LastInsertId 而不是 SELECT MAX(id)：
	// 旧版用 MAX(id) 取刚插入的用户，并发下会拿到别人的 ID（缺陷 D-52）
	newID, err := res.LastInsertId()
	if err != nil {
		return nil, fmt.Errorf("获取新用户 ID: %w", err)
	}

	out := &CreateUserResult{ID: newID, CtrlWind: ctrl, SubWind: sub, CameraWind: cam}

	// 必须为新用户创建同名任务分组，否则文件广播页打不开（BR-114）。
	// 旧版把这段包在 if(!empty($terminal_id)) 里，不选终端时就不建（缺陷 D-56）。
	// filetaskfree.name 只有 varchar(16)，必须截断（契约 C-30）
	folderName := truncateBytes(username, 16)
	fres, err := tx.ExecContext(ctx,
		`INSERT INTO filetaskfree (name, parentid, userid) VALUES (?, 0, ?)`, folderName, newID)
	if err != nil {
		return nil, fmt.Errorf("创建用户任务分组: %w", err)
	}
	out.TaskFolderID, _ = fres.LastInsertId()

	// 序列号最多 3 条，usersn.id 固定 1/2/3（BR-115）
	for i, sn := range in.Serials {
		if i >= 3 || strings.TrimSpace(sn) == "" {
			continue
		}
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO usersn (id, sn, userid) VALUES (?,?,?)`, i+1, strings.TrimSpace(sn), newID); err != nil {
			return nil, fmt.Errorf("写入序列号: %w", err)
		}
	}

	// 终端绑定。改成对象数组传入，不再像旧版那样靠两条逗号串按下标对齐（缺陷 D-57）
	for _, b := range in.Terminals {
		if b.TerminalID <= 0 {
			continue
		}
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO userterminal (userid, terminalid, groupid) VALUES (?,?,?)`,
			newID, b.TerminalID, b.GroupID); err != nil {
			return nil, fmt.Errorf("绑定终端: %w", err)
		}
		out.BoundTerminals++
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return out, nil
}

// ---------- F-20 修改用户 ----------

type UpdateUserInput struct {
	Username string
	// Password 为空字符串表示**不修改密码**。
	//
	// 这是对旧版一个会锁死账号的缺陷的修复（D-58）：
	// 旧代码 if($newpwd == $confirmpwd && strlen<=16) { $newpwd = md5($newpwd); }
	// 两个空串相等且长度 0 ≤ 16，于是把密码改成了 md5("") ——
	// 只要管理员改个描述没填密码，该用户就再也登不进去了。
	Password         string
	ConfirmPassword  string
	UsergroupID      int64
	Info             string
	EnableCtrlWind   bool
	EnableSubWind    bool
	EnableCameraWind bool
	Serials          []string
	Terminals        []TerminalBind
}

func (s *Service) UpdateUser(ctx context.Context, id int64, in UpdateUserInput) (*PriorityRecalc, error) {
	username := strings.TrimSpace(in.Username)

	var oldGroupID int64
	var oldName string
	err := s.db.QueryRowContext(ctx,
		`SELECT username, usergroupid FROM book_admin WHERE id = ? LIMIT 1`, id).Scan(&oldName, &oldGroupID)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询用户: %w", err)
	}

	// admin 的用户名与所属组不可修改，组恒为 1（BR-118）
	isAdminUser := id == SystemUserID
	if isAdminUser {
		username = oldName
		in.UsergroupID = SystemGroupID
	}
	if username == "" || len(username) > 50 {
		return nil, fmt.Errorf("用户名长度非法")
	}

	changePassword := in.Password != ""
	if changePassword {
		if len(in.Password) > 20 {
			return nil, fmt.Errorf("密码长度不能超过 20")
		}
		if in.Password != in.ConfirmPassword {
			return nil, fmt.Errorf("两次输入的密码不一致")
		}
	}

	unlock, err := s.lock(ctx, "htweb_user_create")
	if err != nil {
		return nil, err
	}
	defer unlock()

	var exists int
	err = s.db.QueryRowContext(ctx,
		`SELECT 1 FROM book_admin WHERE id <> ? AND username = ? LIMIT 1`, id, username).Scan(&exists)
	if err == nil {
		return nil, ErrNameUsed
	}
	if !errors.Is(err, sql.ErrNoRows) {
		return nil, fmt.Errorf("重名校验: %w", err)
	}

	capacity := s.windCapacity(ctx)
	ctrl, sub, cam := 0, 0, 0
	if in.EnableCtrlWind {
		if ctrl, err = s.allocWind(ctx, windCtrl, capacity, id); err != nil {
			return nil, fmt.Errorf("分配手机分控 ID: %w", err)
		}
	}
	if in.EnableSubWind {
		if sub, err = s.allocWind(ctx, windSub, capacity, id); err != nil {
			return nil, fmt.Errorf("分配分控软件 ID: %w", err)
		}
	}
	if in.EnableCameraWind {
		if cam, err = s.allocWind(ctx, windCamera, capacity, id); err != nil {
			return nil, fmt.Errorf("分配监控软件 ID: %w", err)
		}
	}

	// 换组时要按新组的 level 重算该用户的任务优先级
	var newLevel, oldLevel int
	_ = s.db.QueryRowContext(ctx, `SELECT level FROM usergroup WHERE id = ?`, in.UsergroupID).Scan(&newLevel)
	_ = s.db.QueryRowContext(ctx, `SELECT level FROM usergroup WHERE id = ?`, oldGroupID).Scan(&oldLevel)

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// 密码为空时 SQL 里根本不包含 userpwd 字段
	setParts := []string{"username = ?", "usergroupid = ?", "info = ?",
		"ctrlwind = ?", "subwind = ?", "camerawind = ?"}
	args := []interface{}{username, in.UsergroupID, in.Info, ctrl, sub, cam}
	if changePassword {
		setParts = append(setParts, "userpwd = ?")
		args = append(args, auth.MD5Hex(in.Password))
	}
	args = append(args, id)

	if _, err := tx.ExecContext(ctx,
		`UPDATE book_admin SET `+strings.Join(setParts, ", ")+` WHERE id = ?`, args...); err != nil {
		return nil, fmt.Errorf("更新用户: %w", err)
	}

	if err := s.syncTerminalBinds(ctx, tx, id, oldGroupID, in.UsergroupID, in.Terminals); err != nil {
		return nil, err
	}
	if err := s.syncSerials(ctx, tx, id, in.Serials); err != nil {
		return nil, err
	}

	recalc := &PriorityRecalc{}
	if newLevel != oldLevel && newLevel > 0 {
		if recalc, err = recalcUserPriorities(ctx, tx, id, oldLevel, newLevel); err != nil {
			return nil, err
		}
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return recalc, nil
}

// syncTerminalBinds 按「四象限规则」维护用户与终端的绑定（BR-119）。
//
//	旧组有终端权限 + 新组有 → 先全删再全插
//	旧组有         + 新组无 → 只删
//	旧组无         + 新组有 → 只插
//	旧组无         + 新组无 → 什么也不做
func (s *Service) syncTerminalBinds(ctx context.Context, tx *sql.Tx, userID, oldGroupID, newGroupID int64, binds []TerminalBind) error {
	var oldPriv, newPriv int
	_ = tx.QueryRowContext(ctx, `SELECT terminalpriv FROM usergroup WHERE id = ?`, oldGroupID).Scan(&oldPriv)
	_ = tx.QueryRowContext(ctx, `SELECT terminalpriv FROM usergroup WHERE id = ?`, newGroupID).Scan(&newPriv)

	shouldDelete := oldPriv == 1
	shouldInsert := newPriv == 1

	if shouldDelete {
		if _, err := tx.ExecContext(ctx, `DELETE FROM userterminal WHERE userid = ?`, userID); err != nil {
			return fmt.Errorf("清理终端绑定: %w", err)
		}
	}
	if shouldInsert {
		for _, b := range binds {
			if b.TerminalID <= 0 {
				continue
			}
			if _, err := tx.ExecContext(ctx,
				`INSERT INTO userterminal (userid, terminalid, groupid) VALUES (?,?,?)`,
				userID, b.TerminalID, b.GroupID); err != nil {
				return fmt.Errorf("绑定终端: %w", err)
			}
		}
	}
	return nil
}

func (s *Service) syncSerials(ctx context.Context, tx *sql.Tx, userID int64, serials []string) error {
	for i := 0; i < 3; i++ {
		sn := ""
		if i < len(serials) {
			sn = strings.TrimSpace(serials[i])
		}
		if sn == "" {
			if _, err := tx.ExecContext(ctx,
				`DELETE FROM usersn WHERE id = ? AND userid = ?`, i+1, userID); err != nil {
				return fmt.Errorf("清理序列号: %w", err)
			}
			continue
		}
		var exists int
		err := tx.QueryRowContext(ctx,
			`SELECT 1 FROM usersn WHERE id = ? AND userid = ? LIMIT 1`, i+1, userID).Scan(&exists)
		if errors.Is(err, sql.ErrNoRows) {
			if _, err := tx.ExecContext(ctx,
				`INSERT INTO usersn (id, sn, userid) VALUES (?,?,?)`, i+1, sn, userID); err != nil {
				return fmt.Errorf("写入序列号: %w", err)
			}
		} else if err == nil {
			if _, err := tx.ExecContext(ctx,
				`UPDATE usersn SET sn = ? WHERE id = ? AND userid = ?`, sn, i+1, userID); err != nil {
				return fmt.Errorf("更新序列号: %w", err)
			}
		} else {
			return fmt.Errorf("查询序列号: %w", err)
		}
	}
	return nil
}

func recalcUserPriorities(ctx context.Context, tx *sql.Tx, userID int64, oldLevel, newLevel int) (*PriorityRecalc, error) {
	out := &PriorityRecalc{AffectedUsers: 1}
	rows, err := tx.QueryContext(ctx, `SELECT taskid, priority FROM task WHERE task_user_id = ?`, userID)
	if err != nil {
		return nil, fmt.Errorf("查询用户任务: %w", err)
	}
	type upd struct {
		taskID      int64
		newPriority int
	}
	var updates []upd
	oldTens := oldLevel / 10
	for rows.Next() {
		var taskID int64
		var priority int
		if err := rows.Scan(&taskID, &priority); err != nil {
			rows.Close()
			return nil, err
		}
		if priority/10 == oldTens {
			updates = append(updates, upd{taskID, newLevel + priority%10})
		}
	}
	rows.Close()
	if err := rows.Err(); err != nil {
		return nil, err
	}
	for _, u := range updates {
		if _, err := tx.ExecContext(ctx,
			`UPDATE task SET priority = ? WHERE taskid = ?`, u.newPriority, u.taskID); err != nil {
			return nil, fmt.Errorf("更新任务优先级: %w", err)
		}
		out.AffectedTasks++
	}
	return out, nil
}

// truncateBytes 按字节截断，且不切断多字节字符。
func truncateBytes(s string, max int) string {
	if len(s) <= max {
		return s
	}
	b := []byte(s)[:max]
	// 回退到完整字符边界
	for len(b) > 0 && b[len(b)-1]&0xC0 == 0x80 {
		b = b[:len(b)-1]
	}
	if len(b) > 0 && b[len(b)-1]&0x80 != 0 {
		b = b[:len(b)-1]
	}
	return string(b)
}
