// Package store 是数据访问层。
//
// # 三条铁律
//
//  1. 所有 SQL 一律使用 ? 占位符 + 参数绑定，禁止字符串拼接
//     （旧系统全站 SQL 注入，见缺陷 D-01）
//  2. 排序字段、筛选列名一律走白名单映射，绝不接受用户传入的列名
//     （旧系统日志页把列名直接拼进 SQL，见 D-199）
//  3. 绝不出现任何 DDL 语句。数据库账号本身也已被剥夺 DDL 权限（手册 §1.6.8）
package store

import (
	"context"
	"database/sql"
	"fmt"
	"log"
	"net"
	"strconv"
	"strings"
	"sync"
	"time"

	_ "github.com/go-sql-driver/mysql"

	"htweb/internal/config"
)

type Store struct {
	db        *sql.DB
	mediaRoot string
}

func Open(cfg *config.Config) (*Store, error) {
	dsn := cfg.Database.DSN()

	db, err := sql.Open("mysql", dsn)
	if err != nil {
		return nil, fmt.Errorf("打开数据库: %w", err)
	}

	// 连接复用策略。
	//
	// 现网每建立一条新连接要付约 10 秒（服务端反向 DNS 解析超时，详见 §1.6.9），
	// 而复用已有连接执行查询是 0ms。因此策略是：
	//   · 连接建立后永不因「存活时长」或「空闲时长」被回收
	//   · 空闲连接数与最大连接数取齐，池子建好就一直留着
	//   · 启动时并发预热，把这 10 秒一次性付清且多条并行
	//
	// 这样稳态运行时完全不受影响；即使某条连接意外断开，也只影响该次请求。
	db.SetMaxOpenConns(cfg.Database.MaxOpen)
	db.SetMaxIdleConns(cfg.Database.MaxOpen) // 与 MaxOpen 取齐，避免连接被关掉后重建
	db.SetConnMaxLifetime(0)                 // 不因存活时长回收
	db.SetConnMaxIdleTime(0)                 // 不因空闲时长回收

	// 先做裸 TCP 拨号，把「网络不通」与「协议/认证失败」区分开
	addr := net.JoinHostPort(cfg.Database.Host, strconv.Itoa(cfg.Database.Port))
	conn, dialErr := net.DialTimeout("tcp", addr, 5*time.Second)
	if dialErr != nil {
		return nil, fmt.Errorf("TCP 拨号 %s 失败（检查 3306 是否已发布到宿主）: %w", addr, dialErr)
	}
	_ = conn.Close()

	pingStart := time.Now()
	ctx, cancel := context.WithTimeout(context.Background(), 45*time.Second)
	defer cancel()
	if err := db.PingContext(ctx); err != nil {
		return nil, fmt.Errorf("MySQL 握手失败（耗时 %v）: %w",
			time.Since(pingStart).Round(time.Millisecond), err)
	}
	elapsed := time.Since(pingStart).Round(time.Millisecond)
	log.Printf("数据库连接成功，首连耗时 %v", elapsed)
	if elapsed > 3*time.Second {
		log.Printf("⚠ 建连耗时偏高。现网已知原因：MariaDB 未开启 skip_name_resolve，"+
			"对来源地址 %s 做反向 DNS 解析超时。查询本身不受影响，已启用连接长期复用规避。", addr)
	}

	s := &Store{db: db, mediaRoot: cfg.Media.Root}
	s.warmUp(cfg.Database.MaxOpen)
	s.keepAlive(cfg.Database.MaxOpen)
	return s, nil
}

// keepAlive 定期把池里每条连接都 ping 一遍，别让 MySQL 把它们超时关掉。
//
// # 为什么需要
//
// 上面把 ConnMaxLifetime / ConnMaxIdleTime 都设成了 0（永不回收），
// 目的是躲开每次建连约 10 秒的反向 DNS 代价。但「我方永不回收」不等于
// 「对方永不关闭」—— MySQL 的 wait_timeout（默认 8 小时）到点就会把
// 空闲连接单方面掐掉，而 database/sql 并不知情。
//
// 现网实测过后果：服务 2026-08-26 18:25 启动，隔夜无人访问，
// 次日 08:16 第一批请求全部撞上死连接：
//
//	[mysql] packets.go:149 write tcp …->127.0.0.1:3306: write: broken pipe
//	[mysql] connection.go:706 closing bad idle connection: connection reset by peer
//	[500] 登录: 读取服务器参数: context canceled
//
// 也就是「早上第一个登录必失败」。
//
// # 为什么不用 ConnMaxIdleTime 了事
//
// 那等于让连接定期自杀重建，10 秒的建连代价会落在某个倒霉用户的请求上。
// 定期 ping 则是在**后台**把连接续上，用户请求永远命中热连接。
//
// 间隔取 4 分钟：远小于任何常见的 wait_timeout（最短的默认值也有 600 秒），
// 代价是每 4 分钟几条 ping，可以忽略。
func (s *Store) keepAlive(n int) {
	if n <= 0 {
		n = 1
	}
	go func() {
		t := time.NewTicker(4 * time.Minute)
		defer t.Stop()
		for range t.C {
			// 和 warmUp 一样：先把 n 条都攥在手里再一起放，
			// 否则反复拿同一条，其余几条照样会被 MySQL 掐掉。
			var wg sync.WaitGroup
			conns := make([]*sql.Conn, 0, n)
			var mu sync.Mutex
			for i := 0; i < n; i++ {
				wg.Add(1)
				go func() {
					defer wg.Done()
					ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
					defer cancel()
					c, err := s.db.Conn(ctx)
					if err != nil {
						return
					}
					// ping 失败说明这条已经死了；Close 会把它从池里剔除，
					// 下次 warm 的时候自然补上，代价落在后台而不是用户请求上。
					if err := c.PingContext(ctx); err != nil {
						_ = c.Close()
						return
					}
					mu.Lock()
					conns = append(conns, c)
					mu.Unlock()
				}()
			}
			wg.Wait()
			for _, c := range conns {
				_ = c.Close()
			}
		}
	}()
}

// warmUp 并发预热连接池。
//
// 由于单条连接的建立要付约 10 秒，串行预热 8 条就是 80 秒；
// 并发建立则总耗时仍约 10 秒。预热后所有请求都命中已有连接。
func (s *Store) warmUp(n int) {
	if n <= 1 {
		return
	}
	start := time.Now()
	var wg sync.WaitGroup
	conns := make([]*sql.Conn, 0, n)
	var mu sync.Mutex

	for i := 0; i < n; i++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			ctx, cancel := context.WithTimeout(context.Background(), 60*time.Second)
			defer cancel()
			c, err := s.db.Conn(ctx)
			if err != nil {
				return
			}
			mu.Lock()
			conns = append(conns, c)
			mu.Unlock()
		}()
	}
	wg.Wait()

	// 全部拿到手之后再一起归还，确保池里真的留下了 n 条连接
	for _, c := range conns {
		_ = c.Close()
	}
	log.Printf("连接池预热完成: %d/%d 条，耗时 %v",
		len(conns), n, time.Since(start).Round(time.Millisecond))
}

func (s *Store) Close() error      { return s.db.Close() }
func (s *Store) DB() *sql.DB       { return s.db }
func (s *Store) MediaRoot() string { return s.mediaRoot }

// ---------- 分页 ----------

// Pager 承载经过校验的分页参数。
//
// 参数名必须是 pageNum（不是 page），与前端 useTable 发出的字段一致。
// 旧系统的 page=0 会算出 LIMIT -18 导致 SQL 语法错误（D-14），这里做了归一。
type Pager struct {
	PageNum  int
	PageSize int
}

var allowedPageSizes = map[int]bool{10: true, 18: true, 20: true, 50: true, 100: true}

// NewPager 归一化分页参数。默认每页 18 条，与旧系统 $NumOfPage 一致。
func NewPager(pageNum, pageSize int) Pager {
	if pageNum < 1 {
		pageNum = 1
	}
	if !allowedPageSizes[pageSize] {
		pageSize = 18
	}
	return Pager{PageNum: pageNum, PageSize: pageSize}
}

func (p Pager) Offset() int { return (p.PageNum - 1) * p.PageSize }

// ---------- 查询构造 ----------

// Cond 累积 WHERE 条件与绑定参数，避免手工拼接括号出错
// （旧系统多处拼出括号不闭合的非法 SQL，见 D-17）。
type Cond struct {
	parts []string
	args  []interface{}
}

func (c *Cond) Add(expr string, args ...interface{}) *Cond {
	c.parts = append(c.parts, expr)
	c.args = append(c.args, args...)
	return c
}

// AddIn 生成 IN (?,?,?) 并绑定参数。传入空切片时条件恒假，
// 避免生成非法的 IN () 语法。
func (c *Cond) AddIn(col string, ids []int64) *Cond {
	if len(ids) == 0 {
		return c.Add("1=0")
	}
	ph := make([]string, len(ids))
	args := make([]interface{}, len(ids))
	for i, v := range ids {
		ph[i] = "?"
		args[i] = v
	}
	return c.Add(fmt.Sprintf("%s IN (%s)", col, strings.Join(ph, ",")), args...)
}

func (c *Cond) Where() string {
	if len(c.parts) == 0 {
		return ""
	}
	return " WHERE " + strings.Join(c.parts, " AND ")
}

func (c *Cond) Args() []interface{} { return c.args }

// ---------- LIKE 转义 ----------

// EscapeLike 转义 LIKE 通配符。
// 旧系统直接把关键字拼进 LIKE，用户输入 % 会匹配全部记录。
func EscapeLike(s string) string {
	r := strings.NewReplacer(`\`, `\\`, `%`, `\%`, `_`, `\_`)
	return "%" + r.Replace(s) + "%"
}

// ---------- 白名单排序 ----------

// OrderBy 按白名单解析排序，杜绝列名注入。
// key 不在白名单时回退到 fallback。
func OrderBy(whitelist map[string]string, key, dir, fallback string) string {
	col, ok := whitelist[key]
	if !ok {
		return fallback
	}
	if strings.EqualFold(dir, "asc") {
		return col + " ASC"
	}
	return col + " DESC"
}

// ---------- 应用级命名锁 ----------

// Lock 获取 MySQL 应用级命名锁，返回释放函数。
//
// 用它替代「给表加唯一索引」——后者属于 DDL，被 R1 红线禁止。
// 典型场景是「先查重、再写入」这种没有唯一索引兜底的原子性需求。
//
// 注意释放用的是 context.Background()：调用方的 ctx 可能已经因为请求结束
// 而取消，那时再用它去执行 RELEASE_LOCK 会直接失败，锁就一直挂到连接归还为止。
func Lock(ctx context.Context, db *sql.DB, name string) (func(), error) {
	var ok sql.NullInt64
	if err := db.QueryRowContext(ctx, `SELECT GET_LOCK(?, 5)`, name).Scan(&ok); err != nil {
		return nil, fmt.Errorf("获取命名锁: %w", err)
	}
	if !ok.Valid || ok.Int64 != 1 {
		return nil, fmt.Errorf("操作繁忙，请稍后重试")
	}
	return func() {
		_, _ = db.ExecContext(context.Background(), `SELECT RELEASE_LOCK(?)`, name)
	}, nil
}

// ---------- 批次 ----------

// ChunkIDs 把 ID 集合切成固定大小的批次。
//
// 旧系统（含后台 C 层）把 ID 递归拼成一条超长 IN 字符串，
// C 层用定长缓冲导致溢出，PHP 层则产生超长 SQL（缺陷 D-09）。
// 这里统一改为分批绑定，批大小取 1000。
func ChunkIDs(ids []int64, size int) [][]int64 {
	if size <= 0 {
		size = 1000
	}
	var out [][]int64
	for len(ids) > size {
		out = append(out, ids[:size])
		ids = ids[size:]
	}
	if len(ids) > 0 {
		out = append(out, ids)
	}
	return out
}
