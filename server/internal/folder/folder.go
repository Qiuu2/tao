// Package folder 实现媒体文件夹树（手册业务域一，F-01 ~ F-04）。
//
// # 对旧系统的关键修复
//
// 旧版 media_folder_tree.php 与 get_filelist() 都是三重嵌套 while 写死三层，
// 第四层递归代码被注释掉（缺陷 D-07）。后果是：数据库允许任意深度，
// 但第 4 层及以下的文件夹在界面上完全不可见，成为「看不见却存在」的孤儿目录。
//
// 新版做法：一次性把 filefolder 全表取回（该表数据量极小，现网仅 10 行），
// 在内存里按 parentid 建邻接表，用迭代（非递归）构建任意深度的树。
// 数据库层仍然只读 id / parentid 两列，零 schema 变更。
//
// 注意区分两件事：
//   - 渲染能力：支持任意深度，为的是把旧数据里已存在的深层目录完整显示出来
//   - 业务规则：新建时仍强制 3 层上限（BR-10，已定稿）
package folder

import (
	"context"
	"database/sql"
	"fmt"
	"sort"

	"htweb/internal/auth"
)

// 系统预置文件夹 ID。旧代码把这些数字散落在十几个文件里硬编码，
// 这里收敛为单一来源（缺陷 D-05 的修复）。
const (
	IDShared    = 1 // 共享媒体库
	IDBell      = 2 // 铃声媒体库
	IDAOD       = 3 // 点播媒体库
	IDAlarm     = 4 // 报警媒体库
	IDRecord    = 5 // 录音媒体库：可见性受 registerflag 控制
	IDTTS       = 6 // 语音合成媒体库：任务媒体选择树中恒隐藏
	IDDevRecord = 7 // 设备录音媒体
	IDRecord2   = 8 // 录音媒体库（子）
	IDTip       = 9 // 提示音：上传按 16000Hz 转码
)

// SystemMaxID 之内的文件夹均为系统预置，禁止删除（BR-21）。
const SystemMaxID = 9

// MaxDepth 是业务允许的最大层级（BR-10，已定稿保持 3 层）。
const MaxDepth = 3

// Node 是树节点。字段命名对齐手册 §4.3.1 的响应体。
type Node struct {
	ID             int64   `json:"id"`
	Name           string  `json:"name"`
	ParentID       int64   `json:"parentId"`
	UserID         int64   `json:"userId"`
	Shared         bool    `json:"shared"`
	CreateTime     string  `json:"createTime"`
	System         bool    `json:"system"`
	Depth          int     `json:"depth"`
	MediaCount     int64   `json:"mediaCount"`
	CanCreateChild bool    `json:"canCreateChild"`
	CanModify      bool    `json:"canModify"`
	CanDelete      bool    `json:"canDelete"`
	Children       []*Node `json:"children"`
}

// Row 是 filefolder 表的原始行。
type Row struct {
	ID         int64
	Name       string
	UserID     int64
	Priority   int // 共享标记：0=不共享，1=共享。列名叫 priority 但与优先级无关
	ParentID   int64
	CreateTime sql.NullTime
}

type Service struct {
	db *sql.DB
}

func New(db *sql.DB) *Service { return &Service{db: db} }

// Scene 决定树的裁剪规则。
type Scene string

const (
	// SceneManage 媒体管理场景，展示全部可见目录。
	SceneManage Scene = "manage"
	// ScenePicker 任务媒体选择场景，恒排除 TTS 库（BR-04）。
	ScenePicker Scene = "taskPicker"
)

// TreeResult 是构树结果。
type TreeResult struct {
	RootName string  `json:"rootName"`
	Tree     []*Node `json:"tree"`
	// Orphans 是 parentid 指向不存在节点的游离目录。
	// 旧版会让它们彻底消失；新版必须展示出来，否则用户永远无法处理这些数据。
	Orphans []*Node `json:"orphans"`
}

// Tree 构建文件夹树。
func (s *Service) Tree(ctx context.Context, u *auth.User, scene Scene, withCount bool) (*TreeResult, error) {
	rows, err := s.loadVisible(ctx, u)
	if err != nil {
		return nil, err
	}

	registerFlag, err := s.registerFlag(ctx)
	if err != nil {
		return nil, err
	}

	var counts map[int64]int64
	if withCount {
		if counts, err = s.mediaCounts(ctx); err != nil {
			return nil, err
		}
	}

	res := build(rows, counts, u, scene, registerFlag)
	res.RootName = "文件管理"
	return res, nil
}

// VisibleCond 返回「哪些文件夹对该用户可见」的 SQL 条件与绑定参数。
//
// 可见范围规则（BR-05 / BR-85，对应旧缺陷 D-19 / D-20 的修复）：
//   - 管理员：全部
//   - 普通用户：共享的（priority=1）或自己创建的（userid=本人）
//
// 旧代码这里有两个错误：
//  1. filefoldermanager.php 拿 usergroupid 去比 filefolder.userid（字段语义完全不同）
//  2. 写了 `filefolder.id = '0'` 这个恒不成立的死条件（id 是自增主键，不可能为 0）
//
// 这里是该规则的唯一权威定义。目录树、媒体列表、上传、清空目录都必须引用它：
// 早期只有目录树按此裁剪，媒体侧几条路径各自漏掉了这道闸门，
// 于是「树里看不见」和「按 id 直接访问不到」这两件事对不上。
func VisibleCond(u *auth.User) (string, []interface{}) {
	return VisibleCondAlias(u, "")
}

// VisibleCondAlias 是同一条规则的带表限定名版本。
//
// 必须有这个版本：media 表**也有** priority 和 userid 两个同名列。
// 把裸条件塞进 `m.folderid IN (SELECT id FROM filefolder WHERE priority = 1 ...)`
// 时，SQL 的作用域规则会先绑到内层的 filefolder，结果碰巧是对的；
// 但同一条件一旦出现在 JOIN 的 ON 子句里，就会静默绑到 media 上，
// 变成「按媒体自己的 priority 判断可见性」—— 而 media.priority 的注释
// 明写着「此字段没被使用」，恒为 0，于是所有媒体对普通用户全部不可见。
// 这类错误不报错、只是结果不对，所以宁可多一个参数也要把限定名写死。
//
// alias 传空串时退化为裸列名，供单表查询使用。
func VisibleCondAlias(u *auth.User, alias string) (string, []interface{}) {
	if u.IsAdmin {
		return "1=1", nil
	}
	p := ""
	if alias != "" {
		p = alias + "."
	}
	return "(" + p + "priority = 1 OR " + p + "userid = ?)", []interface{}{u.ID}
}

// loadVisible 取出当前用户可见的全部文件夹。
func (s *Service) loadVisible(ctx context.Context, u *auth.User) ([]Row, error) {
	cond, args := VisibleCond(u)
	q := `SELECT id, name, userid, priority, parentid, createtime FROM filefolder WHERE ` + cond

	rs, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询文件夹: %w", err)
	}
	defer rs.Close()

	var out []Row
	for rs.Next() {
		var r Row
		if err := rs.Scan(&r.ID, &r.Name, &r.UserID, &r.Priority, &r.ParentID, &r.CreateTime); err != nil {
			return nil, fmt.Errorf("扫描文件夹行: %w", err)
		}
		out = append(out, r)
	}
	return out, rs.Err()
}

// mediaCounts 一次聚合出每个文件夹的媒体数。
//
// 旧版是每个节点单独查一次 count（N+1，缺陷 D-101 同类问题），
// 这里改成单条 GROUP BY。
// TTS 占位记录的过滤口径统一为「两列同时判定」（修复 D-24 的口径不一致）。
func (s *Service) mediaCounts(ctx context.Context) (map[int64]int64, error) {
	rs, err := s.db.QueryContext(ctx, `
		SELECT folderid, COUNT(*) FROM media
		WHERE typeid <> 'tts' AND filename <> 'tts'
		GROUP BY folderid`)
	if err != nil {
		return nil, fmt.Errorf("统计媒体数: %w", err)
	}
	defer rs.Close()

	m := make(map[int64]int64)
	for rs.Next() {
		var id, n int64
		if err := rs.Scan(&id, &n); err != nil {
			return nil, err
		}
		m[id] = n
	}
	return m, rs.Err()
}

func (s *Service) registerFlag(ctx context.Context) (int, error) {
	var f int
	err := s.db.QueryRowContext(ctx, `SELECT registerflag FROM serverbaseparam LIMIT 1`).Scan(&f)
	if err != nil && err != sql.ErrNoRows {
		return 0, fmt.Errorf("读取注册状态: %w", err)
	}
	return f, nil
}

// build 在内存中构树。纯函数，便于单元测试。
func build(rows []Row, counts map[int64]int64, u *auth.User, scene Scene, registerFlag int) *TreeResult {
	// 场景裁剪：任务选择树恒不显示语音合成媒体库（BR-04）
	if scene == ScenePicker {
		rows = filterOut(rows, func(r Row) bool { return r.ID == IDTTS })
	}
	// 未注册（registerflag 不属于 {1,2}）时，整个录音媒体库分支不可见（BR-03）。
	// 注意要连同其子孙一起裁掉，否则子目录会变成游离节点。
	if registerFlag != 1 && registerFlag != 2 {
		rows = pruneSubtree(rows, IDRecord)
	}

	byID := make(map[int64]*Node, len(rows))
	for _, r := range rows {
		ct := ""
		if r.CreateTime.Valid {
			ct = r.CreateTime.Time.Format("2006-01-02 15:04:05")
		}
		byID[r.ID] = &Node{
			ID:         r.ID,
			Name:       r.Name,
			ParentID:   r.ParentID,
			UserID:     r.UserID,
			Shared:     r.Priority == 1,
			CreateTime: ct,
			System:     r.ID <= SystemMaxID,
			MediaCount: counts[r.ID],
			Children:   []*Node{},
		}
	}

	var roots, orphans []*Node
	for _, r := range rows {
		n := byID[r.ID]
		switch {
		case r.ParentID == 0:
			roots = append(roots, n)
		default:
			if p, ok := byID[r.ParentID]; ok {
				p.Children = append(p.Children, n)
			} else {
				// parentid 指向一个不存在（或对本用户不可见）的节点。
				// 现网实测确实存在这类数据，必须展示而不是丢弃。
				orphans = append(orphans, n)
			}
		}
	}

	// 迭代计算深度与权限，并用 visited 防环。
	// 旧数据里的 parentid 可能成环，递归实现会直接栈溢出。
	assign(roots, 1, u)
	assign(orphans, 1, u)

	sortTree(roots)
	sortTree(orphans)

	return &TreeResult{Tree: roots, Orphans: orphans}
}

// assign 用显式栈遍历，逐节点计算 depth 与操作权限。
func assign(roots []*Node, startDepth int, u *auth.User) {
	type item struct {
		n     *Node
		depth int
	}
	visited := make(map[int64]bool)
	stack := make([]item, 0, len(roots))
	for _, r := range roots {
		stack = append(stack, item{r, startDepth})
	}

	for len(stack) > 0 {
		it := stack[len(stack)-1]
		stack = stack[:len(stack)-1]

		if visited[it.n.ID] {
			// 检测到环：断开这条边，避免无限循环
			it.n.Children = nil
			continue
		}
		visited[it.n.ID] = true

		n := it.n
		n.Depth = it.depth
		// 深度已达上限则不允许再建子级（BR-10）
		n.CanCreateChild = it.depth < MaxDepth
		// 系统预置目录禁止改名与删除（BR-16 / BR-21）
		owned := u.IsAdmin || n.UserID == u.ID
		n.CanModify = !n.System && owned
		n.CanDelete = !n.System && owned

		for _, c := range n.Children {
			stack = append(stack, item{c, it.depth + 1})
		}
	}
}

func sortTree(ns []*Node) {
	sort.SliceStable(ns, func(i, j int) bool { return ns[i].ID < ns[j].ID })
	for _, n := range ns {
		sortTree(n.Children)
	}
}

func filterOut(rows []Row, drop func(Row) bool) []Row {
	out := rows[:0:0]
	for _, r := range rows {
		if !drop(r) {
			out = append(out, r)
		}
	}
	return out
}

// pruneSubtree 裁掉以 rootID 为根的整棵子树。
func pruneSubtree(rows []Row, rootID int64) []Row {
	children := make(map[int64][]int64)
	for _, r := range rows {
		children[r.ParentID] = append(children[r.ParentID], r.ID)
	}

	remove := map[int64]bool{rootID: true}
	stack := []int64{rootID}
	visited := map[int64]bool{}
	for len(stack) > 0 {
		id := stack[len(stack)-1]
		stack = stack[:len(stack)-1]
		if visited[id] {
			continue
		}
		visited[id] = true
		for _, c := range children[id] {
			remove[c] = true
			stack = append(stack, c)
		}
	}
	return filterOut(rows, func(r Row) bool { return remove[r.ID] })
}

// Depth 计算某个文件夹的深度，用于新建子目录时的层级校验。
// 返回 0 表示该文件夹不存在。
func (s *Service) Depth(ctx context.Context, id int64) (int, error) {
	if id == 0 {
		return 0, nil // 顶层的父级
	}
	depth := 0
	cur := id
	visited := map[int64]bool{}
	for cur != 0 {
		if visited[cur] {
			return 0, fmt.Errorf("文件夹 %d 的父子关系存在环", id)
		}
		visited[cur] = true

		var parent int64
		err := s.db.QueryRowContext(ctx,
			`SELECT parentid FROM filefolder WHERE id = ? LIMIT 1`, cur).Scan(&parent)
		if err == sql.ErrNoRows {
			return 0, nil
		}
		if err != nil {
			return 0, fmt.Errorf("查询父目录: %w", err)
		}
		depth++
		cur = parent
		if depth > 64 {
			return 0, fmt.Errorf("文件夹 %d 层级异常过深", id)
		}
	}
	return depth, nil
}
