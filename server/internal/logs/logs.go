// Package logs 实现操作日志模块 F-54。
//
// # 旧版这一页是全站可利用性最高的注入点
//
// bmanager.php 的筛选是这么拼的：
//
//	"... where log.".$_GET['searchkey']." like '%".$_GET['searchvalue']."%'"
//
// **列名和值都来自 URL，都是拼接**（D-199）。列名位置无法用参数绑定绕过，
// 只能走白名单；值位置用参数绑定。排序同样是裸拼（D-200）。
//
// # 清空日志不能用 TRUNCATE
//
// 旧版用 `TRUNCATE TABLE log`（D-201）。TRUNCATE 是 DDL 级语句：
// 隐式提交、不可回滚、重置 AUTO_INCREMENT。在本项目「禁止任何 DDL」的红线下
// 尤其不能用 —— 而且新版的数据库账号 htweb 只有 DML 权限，
// TRUNCATE 会直接被服务器拒掉。改用 DELETE：可回滚、不动自增。
//
// # 清空前先写审计
//
// 旧版清空日志本身不留痕（D-202）：谁清的、什么时候清的、清了多少，全都查不到。
// 新版在同一个事务里**先插一条审计记录**，再删除 id 小于它的行 ——
// 这样这条记录必然是幸存者，且删除条数已经写在它的描述里。
package logs

import (
	"context"
	"database/sql"
	"fmt"
	"strings"
	"time"

	"htweb/internal/audit"
	"htweb/internal/store"
)

type Service struct {
	db  *sql.DB
	rec *audit.Recorder
}

func New(db *sql.DB, rec *audit.Recorder) *Service {
	return &Service{db: db, rec: rec}
}

// Entry 是一行操作日志。
type Entry struct {
	ID      int64  `json:"id"`
	User    string `json:"user"`
	Operate string `json:"operate"`
	IP      string `json:"ip"`
	Time    string `json:"time"`
	Info    string `json:"info"`
	// Source 是这行日志的来源猜测，纯展示用，不落库。
	// log 表是 Web 与后台 C 服务共用的，混在一起看很难分辨。
	Source string `json:"source"`
}

// 筛选列白名单（修 D-199）。键是前端传的 searchKey，值是真实列名。
var searchWhitelist = map[string]string{
	"user":    "user",
	"operate": "operate",
	"ip":      "ip",
	"time":    "time",
}

// 排序白名单（修 D-200）。
var orderWhitelist = map[string]string{
	"id":      "id",
	"time":    "time",
	"user":    "user",
	"operate": "operate",
}

const defaultOrder = "id DESC"

type Query struct {
	SearchKey string
	Keyword   string
	StartTime string
	EndTime   string
	OrderBy   string
	Order     string
	Pager     store.Pager
}

type ListResult struct {
	Items []Entry
	Total int64
}

func (s *Service) List(ctx context.Context, q Query) (*ListResult, error) {
	cond := &store.Cond{}
	// 列名只能来自白名单：这个位置参数绑定用不上，白名单是唯一的正确解法
	if col, ok := searchWhitelist[q.SearchKey]; ok && q.Keyword != "" {
		cond.Add("`"+col+"` LIKE ? ESCAPE '\\\\'", store.EscapeLike(q.Keyword))
	}
	// 时间范围比对 time 做 LIKE 靠谱得多，也能走索引（如果将来有的话）
	if q.StartTime != "" {
		cond.Add("time >= ?", q.StartTime)
	}
	if q.EndTime != "" {
		cond.Add("time <= ?", q.EndTime)
	}
	where := cond.Where()

	out := &ListResult{Items: []Entry{}}
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM log"+where, cond.Args()...).Scan(&out.Total); err != nil {
		return nil, fmt.Errorf("统计操作日志: %w", err)
	}
	if out.Total == 0 {
		return out, nil
	}

	order := store.OrderBy(orderWhitelist, q.OrderBy, q.Order, defaultOrder)
	args := append(append([]interface{}{}, cond.Args()...), q.Pager.PageSize, q.Pager.Offset())
	rows, err := s.db.QueryContext(ctx, `
		SELECT id, COALESCE(user,''), COALESCE(operate,''), COALESCE(ip,''),
		       COALESCE(DATE_FORMAT(time,'%Y-%m-%d %H:%i:%s'),''), COALESCE(info,'')
		FROM log`+where+`
		ORDER BY `+order+`
		LIMIT ? OFFSET ?`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询操作日志: %w", err)
	}
	defer rows.Close()

	for rows.Next() {
		var e Entry
		if err := rows.Scan(&e.ID, &e.User, &e.Operate, &e.IP, &e.Time, &e.Info); err != nil {
			return nil, err
		}
		e.Source = guessSource(e)
		out.Items = append(out.Items, e)
	}
	return out, rows.Err()
}

// guessSource 猜这行日志是谁写的，只为界面上能分辨，不做任何逻辑判断。
//
// 依据现网 156 行的实际分布：
//   - user='server' / '主机:-N' 是后台 C 服务
//   - 有 IP 的是 Web 请求
//   - user 与 ip 都空的旧记录既不是新版写的，也没法归因
func guessSource(e Entry) string {
	switch {
	case e.User == "server" || strings.HasPrefix(e.User, "主机:"):
		return "后台服务"
	case e.IP != "":
		return "Web"
	default:
		return "未知"
	}
}

// Stats 是日志清理前给用户看的概览。
type Stats struct {
	Total    int64  `json:"total"`
	Earliest string `json:"earliest"`
	Latest   string `json:"latest"`
	// FromServer 是后台 C 服务写的条数。清空会连它们一起清掉，
	// 这一点必须让用户看见 —— 旧版清空是无差别 TRUNCATE，谁也没提醒过。
	FromServer int64 `json:"fromServer"`
}

func (s *Service) Stats(ctx context.Context) (*Stats, error) {
	st := &Stats{}
	var earliest, latest sql.NullString
	err := s.db.QueryRowContext(ctx, `
		SELECT COUNT(*),
		       DATE_FORMAT(MIN(time),'%Y-%m-%d %H:%i:%s'),
		       DATE_FORMAT(MAX(time),'%Y-%m-%d %H:%i:%s'),
		       SUM(user = 'server' OR user LIKE '主机:%')
		FROM log`).Scan(&st.Total, &earliest, &latest, &st.FromServer)
	if err != nil {
		return nil, fmt.Errorf("统计日志概览: %w", err)
	}
	st.Earliest, st.Latest = earliest.String, latest.String
	return st, nil
}

// ClearMode 是清理方式。
type ClearMode string

const (
	ClearAll        ClearMode = "all"        // 全部清空
	ClearBeforeDate ClearMode = "beforeDate" // 删除某日期之前的
	ClearKeepDays   ClearMode = "keepDays"   // 只保留最近 N 天
)

type ClearInput struct {
	Mode       ClearMode
	BeforeDate string
	KeepDays   int
}

type ClearResult struct {
	Deleted    int64  `json:"deleted"`
	AuditLogID int64  `json:"auditLogId"`
	Kept       int64  `json:"kept"`
	Describe   string `json:"describe"`
}

// Clear 清理操作日志。
//
// 三种模式都走 DELETE（BR-249），并且都先写一条审计记录再删（BR-248）。
// 审计记录用 id 作为分界：删除条件里统一带 `id < :auditID`，
// 保证这条刚写进去的记录不会被自己删掉 —— 比按时间排除稳，
// 因为同一秒内可能有别的写入。
func (s *Service) Clear(ctx context.Context, user, ip string, in ClearInput) (*ClearResult, error) {
	where, args, describe, err := clearCond(in)
	if err != nil {
		return nil, err
	}

	// 先数一遍，好把条数写进审计描述里
	var n int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM log"+where, args...).Scan(&n); err != nil {
		return nil, fmt.Errorf("统计待清理条数: %w", err)
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	operate := fmt.Sprintf("清空操作日志（%s，共 %d 条）", describe, n)
	auditID, err := s.rec.WriteTx(ctx, tx, user, operate, ip)
	if err != nil {
		return nil, fmt.Errorf("写清空审计记录: %w", err)
	}

	// id < auditID：把刚插入的审计记录排除在外
	delWhere := where
	if delWhere == "" {
		delWhere = " WHERE id < ?"
	} else {
		delWhere += " AND id < ?"
	}
	res, err := tx.ExecContext(ctx, "DELETE FROM log"+delWhere, append(args, auditID)...)
	if err != nil {
		return nil, fmt.Errorf("清理操作日志: %w", err)
	}
	deleted, _ := res.RowsAffected()

	var kept int64
	if err := tx.QueryRowContext(ctx, "SELECT COUNT(*) FROM log").Scan(&kept); err != nil {
		return nil, fmt.Errorf("统计剩余条数: %w", err)
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return &ClearResult{Deleted: deleted, AuditLogID: auditID, Kept: kept, Describe: describe}, nil
}

func clearCond(in ClearInput) (where string, args []interface{}, describe string, err error) {
	switch in.Mode {
	case ClearAll:
		return "", nil, "全部", nil
	case ClearBeforeDate:
		if !isDate(in.BeforeDate) {
			return "", nil, "", fmt.Errorf("日期格式不正确，应为 YYYY-MM-DD")
		}
		return " WHERE time < ?", []interface{}{in.BeforeDate + " 00:00:00"},
			in.BeforeDate + " 之前", nil
	case ClearKeepDays:
		if in.KeepDays < 1 || in.KeepDays > 3650 {
			return "", nil, "", fmt.Errorf("保留天数必须在 1 ~ 3650 之间")
		}
		// 东八区当天 00:00 往前推 KeepDays 天
		loc := time.FixedZone("CST", 8*3600)
		cut := time.Now().In(loc).AddDate(0, 0, -in.KeepDays).Format("2006-01-02 15:04:05")
		return " WHERE time < ?", []interface{}{cut},
			fmt.Sprintf("只保留最近 %d 天", in.KeepDays), nil
	default:
		return "", nil, "", fmt.Errorf("清理方式只能是 all / beforeDate / keepDays")
	}
}

func isDate(s string) bool {
	_, err := time.Parse("2006-01-02", s)
	return err == nil
}
