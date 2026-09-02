// Package holiday 实现节假日管理。
//
// # 这张表管什么
//
// `holidaytime` 记的是「哪几天算节假日」。后台 C 服务在决定一条作息任务
// 今天要不要响的时候会查它 —— 落在启用的节假日区间里就不打铃。
// 所以这一页看着简单，改错了整栋楼的铃就会在放假那天照响。
//
// # ⚠ projectstate 的取值和 task 表**正好相反**
//
//	holidaytime.projectstate : 1 = 启用, 0 = 停用
//	task.projectstate        : 0 = 启用, 1 = 停用
//
// 这不是笔误，是旧库里两张表各定各的。依据是旧版 do.php 里那两个函数名
// 明明白白的一对：
//
//	function enableholiday()  → UPDATE holidaytime SET projectstate = '1'
//	function disableholiday() → UPDATE holidaytime SET projectstate = '0'
//
// 而 task 表那边是反过来的（见 task 包 §7.2 的更正说明）。
// 列默认值 `DEFAULT 1` 也和「新建即启用」吻合。
// 红线禁止改表，所以这个不一致只能靠常量名和注释挡住 —— 别去猜，读这里。
//
// # 旧版缺的校验
//
// 旧版新建/修改节假日是纯 $_POST 拼接，且**不校验起止日期顺序**（D-214）：
// 把结束日填在开始日之前，记录照存，后台按空区间处理，等于这条配置静默失效。
// 新版拒绝倒挂的区间，并对同名、重叠区间给出提示（不阻断 —— 旧版允许，
// 业务上「元旦」拆成两段也合理）。
package holiday

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"
	"time"

	"htweb/internal/store"
)

type Service struct {
	db *sql.DB
}

func New(db *sql.DB) *Service { return &Service{db: db} }

var (
	ErrNotFound = errors.New("节假日不存在")
)

// 启用 / 停用。**与 task 表相反**，理由见包注释。
const (
	StateEnabled  = 1
	StateDisabled = 0
)

// nameLimit 是 holidaytime.name 的 varchar(32)。超了 MySQL 会静默截断。
const nameLimit = 32

type Item struct {
	ID        int64  `json:"id"`
	Name      string `json:"name"`
	StartDate string `json:"startdate"`
	EndDate   string `json:"enddate"`
	State     int    `json:"projectstate"`
	StateText string `json:"stateText"`
	// Days 是区间天数（含首尾），给界面一个直观的量。
	Days int `json:"days"`
	// Active 表示今天正落在这个区间里且是启用状态 —— 也就是「今天不打铃」。
	Active bool `json:"active"`
}

func stateText(v int) string {
	if v == StateEnabled {
		return "启用"
	}
	return "停用"
}

var orderWhitelist = map[string]string{
	"name":      "h.name",
	"startdate": "h.startdate",
	"enddate":   "h.enddate",
	"state":     "h.projectstate",
	"id":        "h.id",
}

const defaultOrder = "h.startdate DESC, h.id DESC"

type Query struct {
	Keyword string
	// State 为 -1 表示不筛选。
	State   int
	OrderBy string
	Order   string
	Pager   store.Pager
}

type ListResult struct {
	Items []Item
	Total int64
}

func (s *Service) List(ctx context.Context, q Query) (*ListResult, error) {
	cond := &store.Cond{}
	if q.Keyword != "" {
		cond.Add(`h.name LIKE ? ESCAPE '\\'`, store.EscapeLike(q.Keyword))
	}
	if q.State == StateEnabled || q.State == StateDisabled {
		cond.Add(`COALESCE(h.projectstate,1) = ?`, q.State)
	}
	where := cond.Where()
	args := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM holidaytime h"+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计节假日: %w", err)
	}

	order := store.OrderBy(orderWhitelist, q.OrderBy, q.Order, defaultOrder)

	// 日期列一律 CAST 成 CHAR 再取。
	// DSN 里带 parseTime=true，DATE 列会被驱动解析成 time.Time，
	// 再格式化回字符串就多了一层时区换算的风险；这里直接要库里的字面值。
	listSQL := `
		SELECT h.id, COALESCE(h.name,''),
		       COALESCE(CAST(h.startdate AS CHAR),''), COALESCE(CAST(h.enddate AS CHAR),''),
		       COALESCE(h.projectstate,1),
		       COALESCE(DATEDIFF(h.enddate, h.startdate),-1),
		       (COALESCE(h.projectstate,1) = ` + fmt.Sprint(StateEnabled) + `
		        AND h.startdate IS NOT NULL AND h.enddate IS NOT NULL
		        AND CURDATE() BETWEEN h.startdate AND h.enddate)
		FROM holidaytime h` + where + " ORDER BY " + order + " LIMIT ? OFFSET ?"

	listArgs := append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, listSQL, listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询节假日: %w", err)
	}
	defer rs.Close()

	items := make([]Item, 0, q.Pager.PageSize)
	for rs.Next() {
		var it Item
		var diff int
		if err := rs.Scan(&it.ID, &it.Name, &it.StartDate, &it.EndDate,
			&it.State, &diff, &it.Active); err != nil {
			return nil, fmt.Errorf("扫描节假日行: %w", err)
		}
		it.StateText = stateText(it.State)
		if diff >= 0 {
			it.Days = diff + 1 // DATEDIFF 不含首日
		}
		items = append(items, it)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}
	return &ListResult{Items: items, Total: total}, nil
}

func (s *Service) Get(ctx context.Context, id int64) (*Item, error) {
	var it Item
	err := s.db.QueryRowContext(ctx, `
		SELECT id, COALESCE(name,''),
		       COALESCE(CAST(startdate AS CHAR),''), COALESCE(CAST(enddate AS CHAR),''),
		       COALESCE(projectstate,1)
		FROM holidaytime WHERE id = ? LIMIT 1`, id).
		Scan(&it.ID, &it.Name, &it.StartDate, &it.EndDate, &it.State)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询节假日: %w", err)
	}
	it.StateText = stateText(it.State)
	return &it, nil
}

// ---------- 新建 / 修改 ----------

type Input struct {
	Name      string
	StartDate string
	EndDate   string
	// State 是启用/停用。:80 的「添加节假日 / 修改」表单里就带这一项，
	// 所以新建和修改都接受它；列表页的「批量启用/批量禁用」仍然走 SetState。
	// ⚠ 取值是 holidaytime 自己的口径：1 = 启用、0 = 停用（与 task 相反）。
	State int
}

// normState 把外面传进来的状态收敛到 0/1 两个合法值。
// 只认 StateDisabled(0)，其余一律当启用 —— 漏传字段时保持「新建即启用」的旧行为。
func (in *Input) normState() int {
	if in.State == StateDisabled {
		return StateDisabled
	}
	return StateEnabled
}

const dateLayout = "2006-01-02"

func (in *Input) validate() error {
	in.Name = strings.TrimSpace(in.Name)
	in.StartDate = strings.TrimSpace(in.StartDate)
	in.EndDate = strings.TrimSpace(in.EndDate)

	if in.Name == "" {
		return fmt.Errorf("节假日名称不能为空")
	}
	if len(in.Name) > nameLimit {
		return fmt.Errorf("节假日名称过长：按 UTF-8 计 %d 字节，上限 %d 字节（约 10 个汉字）",
			len(in.Name), nameLimit)
	}
	start, err := time.Parse(dateLayout, in.StartDate)
	if err != nil {
		return fmt.Errorf("开始日期格式不正确，必须是 YYYY-MM-DD")
	}
	end, err := time.Parse(dateLayout, in.EndDate)
	if err != nil {
		return fmt.Errorf("结束日期格式不正确，必须是 YYYY-MM-DD")
	}
	// 旧版不查这个，倒挂的区间照存，结果是这条节假日静默失效（D-214）
	if end.Before(start) {
		return fmt.Errorf("结束日期不能早于开始日期")
	}
	return nil
}

// Overlaps 返回与给定区间重叠的其它节假日，用于**提示**而不是阻断。
//
// 旧版允许重叠，业务上也确实可能（同一段假期被拆成两条录入），
// 所以这里只把冲突项报给界面，由人来判断。
func (s *Service) Overlaps(ctx context.Context, start, end string, excludeID int64) ([]Item, error) {
	q := `SELECT id, COALESCE(name,''),
	             COALESCE(CAST(startdate AS CHAR),''), COALESCE(CAST(enddate AS CHAR),''),
	             COALESCE(projectstate,1)
	      FROM holidaytime
	      WHERE startdate IS NOT NULL AND enddate IS NOT NULL
	        AND startdate <= ? AND enddate >= ?`
	args := []interface{}{end, start}
	if excludeID > 0 {
		q += ` AND id <> ?`
		args = append(args, excludeID)
	}
	q += ` ORDER BY startdate LIMIT 20`

	rs, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询重叠节假日: %w", err)
	}
	defer rs.Close()
	out := []Item{}
	for rs.Next() {
		var it Item
		if err := rs.Scan(&it.ID, &it.Name, &it.StartDate, &it.EndDate, &it.State); err != nil {
			return nil, err
		}
		it.StateText = stateText(it.State)
		out = append(out, it)
	}
	return out, rs.Err()
}

func (s *Service) Create(ctx context.Context, in Input) (int64, error) {
	if err := in.validate(); err != nil {
		return 0, err
	}
	// 旧版 INSERT 不写 projectstate，靠列默认值 DEFAULT 1 落成「启用」。
	// 新版显式写：默认值哪天被人改了，语义也不会跟着漂。
	res, err := s.db.ExecContext(ctx,
		`INSERT INTO holidaytime (name, startdate, enddate, projectstate) VALUES (?,?,?,?)`,
		in.Name, in.StartDate, in.EndDate, in.normState())
	if err != nil {
		return 0, fmt.Errorf("新建节假日: %w", err)
	}
	return res.LastInsertId()
}

func (s *Service) Update(ctx context.Context, id int64, in Input) error {
	if err := in.validate(); err != nil {
		return err
	}
	// UPDATE 一律带 WHERE —— 旧版服务器参数那条 UPDATE 就是漏了 WHERE（D-208），
	// 这里不重蹈覆辙。
	res, err := s.db.ExecContext(ctx,
		`UPDATE holidaytime SET name = ?, startdate = ?, enddate = ?, projectstate = ? WHERE id = ?`,
		in.Name, in.StartDate, in.EndDate, in.normState(), id)
	if err != nil {
		return fmt.Errorf("修改节假日: %w", err)
	}
	n, _ := res.RowsAffected()
	if n == 0 {
		// 值没变时 RowsAffected 也是 0，所以再确认一次是否真的不存在
		if _, err := s.Get(ctx, id); err != nil {
			return err
		}
	}
	return nil
}

// SetState 批量启用 / 停用。
func (s *Service) SetState(ctx context.Context, ids []int64, enable bool) (int, error) {
	if len(ids) == 0 {
		return 0, fmt.Errorf("请先选择要操作的节假日")
	}
	v := StateDisabled
	if enable {
		v = StateEnabled
	}
	cond := &store.Cond{}
	cond.AddIn("id", ids)
	res, err := s.db.ExecContext(ctx,
		`UPDATE holidaytime SET projectstate = ?`+cond.Where(),
		append([]interface{}{v}, cond.Args()...)...)
	if err != nil {
		return 0, fmt.Errorf("修改节假日状态: %w", err)
	}
	n, _ := res.RowsAffected()
	return int(n), nil
}

func (s *Service) Delete(ctx context.Context, ids []int64) (int, error) {
	if len(ids) == 0 {
		return 0, fmt.Errorf("请先选择要删除的节假日")
	}
	cond := &store.Cond{}
	cond.AddIn("id", ids)
	res, err := s.db.ExecContext(ctx, `DELETE FROM holidaytime`+cond.Where(), cond.Args()...)
	if err != nil {
		return 0, fmt.Errorf("删除节假日: %w", err)
	}
	n, _ := res.RowsAffected()
	return int(n), nil
}
