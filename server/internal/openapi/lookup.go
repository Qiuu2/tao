package openapi

import (
	"context"
	"encoding/json"
	"fmt"
	"sort"
	"strconv"
	"strings"

	"htweb/internal/auth"
)

// 寻址层：把请求里写的「A101教室音箱」或 12 变成一个确定的 id。
//
// # 为什么名字优先
//
// 对方系统（教务、门禁、值班表）里存的是**名字**，不是本系统的自增 id。
// 逼他们先调一次列表接口把名字换成 id，等于把我们的实现细节变成他们的工作量，
// 而且 id 一旦因为重建数据变了，对方那边就全线错位。
// 名字是双方都认的东西，所以名字优先；id 也照收，方便已经拿到 id 的调用方。
//
// # 与助手的寻址有一条**关键区别**：这里不做模糊匹配
//
// 助手面对的是人说的话（「A101」要对到「A101教室音箱」），猜错了人当场能看见、
// 能纠正。开发者接口面对的是机器：调用方写死了一个名字，猜错了没有人会看见，
// 错误的广播会一直发下去。所以这里只做**精确匹配**：
//
//	对上一个   → 用它
//	对上多个   → 报错，把候选列出来，让调用方自己说清楚（绝不替他挑一个）
//	一个没对上 → 报错，附上几个相近的名字帮他排查拼写
//
// # 可见范围照旧收敛
//
// 密钥归属哪个账号，就只能寻址到那个账号看得见的对象。管理员的密钥看全部，
// 普通账号的密钥只看绑给它的终端、它自己的媒体和方案 —— 和这个人登录界面
// 看到的一模一样。寻址层不是绕过权限的后门。

// Ref 是一个「名字或 id」的引用。
//
// JSON 里写数字当 id、写字符串当名字：
//
//	"terminals": [12, "A101教室音箱"]
//
// 一个数组里混着两种写法是允许的 —— 对方系统里往往一部分对象存了 id、
// 另一部分只有名字，逼他们统一成一种反而要在他们那边加一层转换。
type Ref struct {
	ID   int64
	Name string
}

func (r *Ref) UnmarshalJSON(b []byte) error {
	b = []byte(strings.TrimSpace(string(b)))
	if len(b) == 0 || string(b) == "null" {
		return nil
	}
	if b[0] == '"' {
		var s string
		if err := json.Unmarshal(b, &s); err != nil {
			return err
		}
		s = strings.TrimSpace(s)
		// 字符串里写的**纯数字**也当 id 收。
		// 理由很实际：很多语言的 JSON 序列化会把 int64 写成字符串（JS 的大整数、
		// PHP 的关联数组），拒收会让调用方莫名其妙地"传了 id 却说找不到名字"。
		if n, err := strconv.ParseInt(s, 10, 64); err == nil && n > 0 {
			r.ID = n
			return nil
		}
		r.Name = s
		return nil
	}
	var n json.Number
	if err := json.Unmarshal(b, &n); err != nil {
		return badf("引用只能写数字（编号）或字符串（名字），收到 %s", string(b))
	}
	v, err := n.Int64()
	if err != nil || v <= 0 {
		return badf("编号要是正整数，收到 %s", string(b))
	}
	r.ID = v
	return nil
}

func (r Ref) String() string {
	if r.Name != "" {
		return r.Name
	}
	return strconv.FormatInt(r.ID, 10)
}

func (r Ref) empty() bool { return r.ID == 0 && r.Name == "" }

// namedRow 是寻址用的一行：一个 id 配一个名字。
type namedRow struct {
	ID   int64
	Name string
}

// lookupSpec 描述「去哪张表按什么范围找」。
//
// 把四类对象（终端/媒体/分区/方案）的差异收进这一个结构，
// 解析逻辑只写一遍 —— 四份几乎相同的解析代码里，迟早有一份的歧义检查会漏掉。
type lookupSpec struct {
	// what 是出现在错误话里的中文名，比如「终端」。
	what string
	// query 返回候选行与查询错误。
	query func(ctx context.Context, u *auth.User) ([]namedRow, error)
}

// resolveRefs 把一组引用解析成 id。顺序与入参一致，重复的会去重。
//
// ⚠ 任何一个引用出错就整体失败，**不做"能解析几个算几个"**：
// 「广播到 A101、A102、A103」里 A102 拼错了，只播两个是最坏的结果 ——
// 调用方以为三个都播了，而漏掉的那个没有任何迹象。宁可一个都不播，报错让他改。
func (s *Service) resolveRefs(ctx context.Context, u *auth.User, spec lookupSpec, refs []Ref) ([]int64, error) {
	if len(refs) == 0 {
		return nil, nil
	}
	rows, err := spec.query(ctx, u)
	if err != nil {
		return nil, err
	}

	byID := make(map[int64]bool, len(rows))
	byName := map[string][]namedRow{}
	for _, row := range rows {
		byID[row.ID] = true
		n := strings.TrimSpace(row.Name)
		if n != "" {
			byName[n] = append(byName[n], row)
		}
	}

	seen := map[int64]bool{}
	out := make([]int64, 0, len(refs))
	for _, ref := range refs {
		if ref.empty() {
			continue
		}
		var id int64
		switch {
		case ref.ID > 0:
			if !byID[ref.ID] {
				// ⚠ 「不存在」和「你看不见」合并成同一句。分开说等于让调用方
				//    拿一把权限很小的密钥，去枚举系统里有哪些终端。
				return nil, badf("找不到编号为 %d 的%s", ref.ID, spec.what)
			}
			id = ref.ID
		default:
			hits := byName[ref.Name]
			switch len(hits) {
			case 1:
				id = hits[0].ID
			case 0:
				return nil, badf("找不到叫「%s」的%s%s", ref.Name, spec.what, hintNear(ref.Name, rows))
			default:
				return nil, badf("有 %d 个%s都叫「%s」，请改用编号指定：%s",
					len(hits), spec.what, ref.Name, joinIDs(hits))
			}
		}
		if !seen[id] {
			seen[id] = true
			out = append(out, id)
		}
	}
	return out, nil
}

// resolveOne 解析单个引用。
func (s *Service) resolveOne(ctx context.Context, u *auth.User, spec lookupSpec, ref Ref) (int64, error) {
	ids, err := s.resolveRefs(ctx, u, spec, []Ref{ref})
	if err != nil {
		return 0, err
	}
	if len(ids) == 0 {
		return 0, badf("请指定%s", spec.what)
	}
	return ids[0], nil
}

// hintNear 在名字对不上时给几个相近的，帮调用方发现是拼写问题。
//
// ⚠ 这是**提示**，不是候选 —— 接口绝不会替他选中其中一个。
// 只取包含关系（互相是子串），不做打分：打分排出来的"相近"会让人以为
// 系统本来能猜到、只是没猜，从而怀疑是接口的问题。
func hintNear(name string, rows []namedRow) string {
	if name == "" {
		return ""
	}
	var near []string
	for _, row := range rows {
		n := strings.TrimSpace(row.Name)
		if n == "" || n == name {
			continue
		}
		if strings.Contains(n, name) || strings.Contains(name, n) {
			near = append(near, n)
		}
		if len(near) >= 5 {
			break
		}
	}
	if len(near) == 0 {
		return ""
	}
	sort.Strings(near)
	return "。名字相近的有：" + strings.Join(near, "、")
}

func joinIDs(rows []namedRow) string {
	ids := make([]string, 0, len(rows))
	for _, row := range rows {
		ids = append(ids, strconv.FormatInt(row.ID, 10))
	}
	return strings.Join(ids, "、")
}

// ---------- 四类对象各自的候选范围 ----------

// specTerminal 是终端。
//
// 可见范围沿用界面那条规矩：普通账号只看绑给自己的（userterminal），
// 且恒排除「服务器」类型的那条伪终端（BR-137，typeid = 1）。
func (s *Service) specTerminal() lookupSpec {
	return lookupSpec{what: "终端", query: func(ctx context.Context, u *auth.User) ([]namedRow, error) {
		// typeid = 0 是「服务器」那条伪终端（terminal.TypeServer），恒排除
		q := `SELECT id, COALESCE(terminalname,'') FROM terminal WHERE typeid <> 0`
		var args []any
		if !u.IsAdmin {
			q += ` AND id IN (SELECT terminalid FROM userterminal WHERE userid = ?)`
			args = append(args, u.ID)
		}
		return s.queryNamed(ctx, q+` ORDER BY id`, args...)
	}}
}

// specMedia 是媒体文件。
func (s *Service) specMedia() lookupSpec {
	return lookupSpec{what: "媒体", query: func(ctx context.Context, u *auth.User) ([]namedRow, error) {
		// ⚠ 媒体名那一列叫 name（medianame 是旧 SDK 的叫法，这张表里没有）
		q := `SELECT id, COALESCE(name,'') FROM media WHERE COALESCE(name,'') <> ''`
		var args []any
		if !u.IsAdmin {
			q += ` AND COALESCE(userid,0) = ?`
			args = append(args, u.ID)
		}
		return s.queryNamed(ctx, q+` ORDER BY id`, args...)
	}}
}

// specZone 是终端分区。
func (s *Service) specZone() lookupSpec {
	return lookupSpec{what: "分区", query: func(ctx context.Context, u *auth.User) ([]namedRow, error) {
		q := `SELECT streamid, COALESCE(name,'') FROM serverplaystream WHERE COALESCE(name,'') <> ''`
		var args []any
		if !u.IsAdmin {
			q += ` AND COALESCE(userid,0) = ?`
			args = append(args, u.ID)
		}
		return s.queryNamed(ctx, q+` ORDER BY streamid`, args...)
	}}
}

// queryNamed 跑一条「id, 名字」两列的查询。
func (s *Service) queryNamed(ctx context.Context, q string, args ...any) ([]namedRow, error) {
	rows, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询候选: %w", err)
	}
	defer rows.Close()
	var out []namedRow
	for rows.Next() {
		var r namedRow
		if err := rows.Scan(&r.ID, &r.Name); err != nil {
			return nil, err
		}
		out = append(out, r)
	}
	return out, rows.Err()
}

// resolveSchedule 把一个方案名对到库里真实的方案名上。
//
// ⚠ 作息方案**没有 id** —— 它就是一批 task 行共用的 task.info（见 bell 包）。
// 所以这一个不走 resolveRefs：返回的是名字本身，拿 id 去用会对不上任何东西。
// 范围与 bell 的 planScope 一致，少一项就会把普通任务或功放子任务混进来。
func (s *Service) resolveSchedule(ctx context.Context, u *auth.User, name string) (string, error) {
	name = strings.TrimSpace(name)
	if name == "" {
		return "", badf("请指定作息方案名")
	}
	q := `SELECT DISTINCT COALESCE(info,'') FROM task
	       WHERE tasktype IN (1,15) AND info <> '' AND channel = 0 AND sec_task_id = 0`
	var args []any
	if !u.IsAdmin {
		q += ` AND COALESCE(task_user_id,0) = ?`
		args = append(args, u.ID)
	}
	rows, err := s.db.QueryContext(ctx, q+` ORDER BY info`, args...)
	if err != nil {
		return "", fmt.Errorf("查询作息方案: %w", err)
	}
	defer rows.Close()
	var all []namedRow
	for rows.Next() {
		var n string
		if err := rows.Scan(&n); err != nil {
			return "", err
		}
		all = append(all, namedRow{Name: n})
	}
	if err := rows.Err(); err != nil {
		return "", err
	}
	for _, row := range all {
		if row.Name == name {
			return row.Name, nil
		}
	}
	return "", badf("找不到叫「%s」的作息方案%s", name, hintNear(name, all))
}
