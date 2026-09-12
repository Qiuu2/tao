// Package dbwatch 盯着几张表有没有被改过，好让页面不刷新也能跟着变。
//
// # 为什么不能只靠新版自己发事件
//
// 这个 audioserver 库**不止 htweb 一个程序在写**：
//
//	后台 C 服务（a9000_audioserver）  终端上下线、任务开始/结束/暂停都是它写的
//	旧版 ok112 的 PHP                 现网还在跑，同一个库
//	各种现场脚本
//
// 所以「谁改的谁发通知」这条路走不通 —— 只有**从库里看出来**才靠得住。
//
// # 怎么看出来：两层叠一起
//
//	① serverbaseparam.terminalchange / taskchange / serverchange
//	   旧系统自己的变更计数器，改完数据的程序会把它 +1。
//	   代价是一行三个整数，几乎不要钱。
//	   旧版 ajax.php 就只看这个（每 10 秒轮询一次，然后……把 reload 那行注释掉了，
//	   所以这功能在旧版实际上从来没生效过）。
//
//	② CHECKSUM TABLE terminal, task
//	   内容指纹，兜住**不碰那三个计数器**的写入者。
//	   只靠 ① 的话，哪天有个脚本直接 UPDATE terminal 而没去动计数器，
//	   页面就一直显示旧数据，而且没有任何迹象 —— 这种「静默不同步」
//	   比慢几秒糟得多。
//
// 两层的值混成一个 rev。**rev 不表示版本先后，只表示「一样 / 不一样」** ——
// 它是哈希，比大小没有意义。
//
// # ⚠ rev 对外一律是**十六进制字符串**，绝不能是 JSON 数字
//
// 现网炸过一次，值得写在这儿：rev 内部是 uint64（FNV-64），
// 第一版直接按数字序列化发给浏览器。而 JS 的 Number 是 float64，
// 能精确表示的整数上限只有 9007199254740991：
//
//	服务端发出   13272240285988269346
//	JSON.parse   13272240285988270000   ← 被四舍五入了
//
// 于是浏览器发回来的 rev **永远**对不上服务端的，diff() 每次都判「变了」，
// 长轮询每次立刻返回，页面立刻重查列表、立刻再问一次……
// 实测一个标签页每秒打出 **165 次**列表查询，服务器直接被自己人打垮，
// 表现就是「点开终端管理一堆 500」。
//
// 所以 Revs() / Wait() 对外只认字符串，两头都不做数字解析 —— 没有数字，
// 就没有精度可丢。
//
// # 为什么是长轮询，不是 SSE / WebSocket
//
//   - 前面挡着一层 Apache。SSE 经反向代理默认会被缓冲，表现是「事件攒着不发」，
//     而且要改 httpd.conf 才好使 —— 这台机器上那个配置是旧系统的，动它风险不小。
//   - EventSource 带不了自定义请求头，token 只能塞进 URL（会进访问日志、Referer）。
//     现网已经为「媒体试听 / 下载 / 备份包下载」破过三次例，不想为它再破一次。
//   - 长轮询走的是普通 GET：鉴权、错误、i18n 全走现成那一套，前后端都少一堆代码。
//
// 代价是每个开着页面的浏览器占一个挂起的请求。这是个后台管理系统，
// 同时在线的浏览器是个位数，无所谓。
package dbwatch

import (
	"context"
	"database/sql"
	"fmt"
	"hash/fnv"
	"log"
	"strconv"
	"strings"
	"sync"
	"time"
)

// Topic 是一个可以订阅的主题，目前与表名一一对应。
type Topic = string

const (
	TopicTerminal Topic = "terminal"
	TopicTask     Topic = "task"
)

// Topics 是全部可订阅主题。前端传了别的名字一律忽略。
var Topics = []Topic{TopicTerminal, TopicTask}

// watchedTables 是要算指纹的表。
//
// ⚠ 表名**直接拼进 SQL**（CHECKSUM TABLE 不接受占位符），所以它必须是
// 这里写死的常量，绝不能来自请求参数。
var watchedTables = []string{"terminal", "task"}

// DefaultInterval 是两次查库之间的间隔。
//
// 1.5 秒：人点一下「播放」之后最多等这么久就能看到状态变过来，
// 快到察觉不出是轮询；同时每秒不到一次 CHECKSUM，对这两张表（现网几百到几千行）
// 是毫秒级的开销。
const DefaultInterval = 1500 * time.Millisecond

// Watcher 一个进程一个，所有等待的浏览器共用它这一份查询结果。
//
// ⚠ 关键在于「共用」：每个浏览器各自去查库的话，10 个页面就是 10 倍的查询，
// 而它们要的是同一个答案。
type Watcher struct {
	db       *sql.DB
	interval time.Duration

	mu      sync.RWMutex
	revs    map[Topic]uint64
	ready   bool
	waiters map[chan struct{}]struct{}

	// warned 只让每种查询失败在日志里喊一次，别把日志刷满
	warnedOnce sync.Map
}

func New(db *sql.DB, interval time.Duration) *Watcher {
	if interval <= 0 {
		interval = DefaultInterval
	}
	w := &Watcher{
		db:       db,
		interval: interval,
		revs:     map[Topic]uint64{},
		waiters:  map[chan struct{}]struct{}{},
	}
	for _, t := range Topics {
		w.revs[t] = 0
	}
	return w
}

// Start 起一个后台协程一直轮询，直到 ctx 取消。
func (w *Watcher) Start(ctx context.Context) {
	go func() {
		t := time.NewTicker(w.interval)
		defer t.Stop()
		w.poll(ctx) // 先立刻取一次，别让第一个访问者等一个间隔
		for {
			select {
			case <-ctx.Done():
				return
			case <-t.C:
				w.poll(ctx)
			}
		}
	}()
}

// Revs 返回当前各主题的版本号快照。
//
// ⚠ 值是**十六进制字符串**，不是数字。理由见文件头那段「rev 对外一律是字符串」。
func (w *Watcher) Revs() map[Topic]string {
	w.mu.RLock()
	defer w.mu.RUnlock()
	out := make(map[Topic]string, len(w.revs))
	for k, v := range w.revs {
		out[k] = strconv.FormatUint(v, 16)
	}
	return out
}

// Wait 等到 known 里某个主题的版本和现在不一样，或者超时。
//
// known 里没提到的主题不参与判断 —— 每个页面只订阅自己关心的那几张表，
// 别的表变了不该把它叫醒（叫醒就意味着一次没必要的列表查询）。
//
// 返回当前全部版本号，以及**这一次**变了的主题。超时或 ctx 取消时
// changed 为空，版本号照样返回，调用方据此对齐。
func (w *Watcher) Wait(ctx context.Context, known map[Topic]string, timeout time.Duration) (map[Topic]string, []Topic) {
	if timeout <= 0 {
		timeout = 25 * time.Second
	}
	deadline := time.NewTimer(timeout)
	defer deadline.Stop()

	for {
		cur := w.Revs()
		if changed := diff(known, cur); len(changed) > 0 {
			return cur, changed
		}
		// 订阅要在**再查一次之前**做好，否则两步之间发生的变化会被漏掉，
		// 这个请求就得白等一个超时。
		ch := w.subscribe()
		cur = w.Revs()
		if changed := diff(known, cur); len(changed) > 0 {
			w.unsubscribe(ch)
			return cur, changed
		}
		select {
		case <-ctx.Done():
			w.unsubscribe(ch)
			return cur, nil
		case <-deadline.C:
			w.unsubscribe(ch)
			return cur, nil
		case <-ch:
			w.unsubscribe(ch)
			// 回到循环重新比一次：被叫醒不等于**我关心的**那个主题变了
		}
	}
}

// diff 找出 known 里和 cur 不一致的主题。
//
// ⚠ 比的是「相等 / 不等」，不是大小。rev 是哈希，没有先后可言 ——
// 也正因为如此，它可以、而且必须是字符串。
func diff(known, cur map[Topic]string) []Topic {
	var out []Topic
	for _, t := range Topics {
		k, ok := known[t]
		if !ok {
			continue // 没订阅
		}
		if c, has := cur[t]; has && c != k {
			out = append(out, t)
		}
	}
	return out
}

func (w *Watcher) subscribe() chan struct{} {
	ch := make(chan struct{}, 1)
	w.mu.Lock()
	w.waiters[ch] = struct{}{}
	w.mu.Unlock()
	return ch
}

func (w *Watcher) unsubscribe(ch chan struct{}) {
	w.mu.Lock()
	delete(w.waiters, ch)
	w.mu.Unlock()
}

// poll 查一次库，有变化就把所有等着的人叫醒。
func (w *Watcher) poll(ctx context.Context) {
	c, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	sums, err := w.checksums(c)
	if err != nil {
		// ⚠ 查失败时**什么都不改**。
		//
		// 少了这一条就会这样：这一拍算出「指纹缺失」的 rev → 所有页面刷一次；
		// 下一拍恢复正常 → 又刷一次。数据库抖一下，全站白刷两轮。
		w.warnOnce("checksum", err)
		return
	}
	counters := w.counters(c) // 拿不到就当作 0，它只是叠加项，不单独决定 rev

	next := make(map[Topic]uint64, len(Topics))
	next[TopicTerminal] = mix(sums["terminal"], counters[0], counters[2])
	next[TopicTask] = mix(sums["task"], counters[1], counters[2])

	w.mu.Lock()
	changed := !w.ready
	for t, v := range next {
		if w.revs[t] != v {
			changed = true
		}
		w.revs[t] = v
	}
	w.ready = true
	if changed {
		for ch := range w.waiters {
			select {
			case ch <- struct{}{}:
			default: // 已经有一个待处理的通知了，不必再塞
			}
		}
	}
	w.mu.Unlock()
}

// counters 读旧系统自己的三个变更计数器。读不到回三个 0。
//
// 它们是**叠加**在内容指纹上的，不单独决定 rev：某些改动（比如后台服务只更新了
// 一个不在指纹列里的列）只体现在计数器上，反过来直接改表的脚本只体现在指纹上。
// 两个都算进去，哪一边动了都能发现。
func (w *Watcher) counters(ctx context.Context) [3]uint64 {
	var a, b, c int64
	err := w.db.QueryRowContext(ctx, `
		SELECT COALESCE(terminalchange,0), COALESCE(taskchange,0), COALESCE(serverchange,0)
		FROM serverbaseparam WHERE id = 1 LIMIT 1`).Scan(&a, &b, &c)
	if err != nil {
		w.warnOnce("counters", err)
		return [3]uint64{}
	}
	return [3]uint64{uint64(a), uint64(b), uint64(c)}
}

// checksums 对每张表算一次内容指纹。
//
// 用 CHECKSUM TABLE 而不是自己拼一串 CONCAT_WS(列…)：
// 列名一旦要手写，就会有哪天加了一列忘了往这儿加，表现是「改了那一列页面不动」——
// 又是一种没有任何症状的漏。CHECKSUM TABLE 覆盖全部列，不需要维护名单。
//
// 代价是一次全表扫描。这两张表现网是几百到几千行 —— 实测演示库
// （13 个终端 + 26 个任务）一次不到 1 毫秒；真要长到让它吃力，
// 把 changes.interval 调大即可。
//
// 顺带：task 表的 createtime 是 `ON UPDATE current_timestamp()`，
// 所以任何一次 UPDATE 都会连带改动它 —— 指纹对 task 的改动格外敏感，
// 这是白捡的，不是设计出来的。terminal 表没有这样的列，全靠 CHECKSUM 本身。
func (w *Watcher) checksums(ctx context.Context) (map[string]uint64, error) {
	// ⚠ 表名来自上面写死的 watchedTables，不是请求参数 —— CHECKSUM TABLE
	//   不接受占位符，这里是唯一能保证不被注入的方式。
	q := "CHECKSUM TABLE " + strings.Join(watchedTables, ", ")
	rs, err := w.db.QueryContext(ctx, q)
	if err != nil {
		return nil, fmt.Errorf("%s: %w", q, err)
	}
	defer rs.Close()

	out := make(map[string]uint64, len(watchedTables))
	for rs.Next() {
		var name string
		var sum sql.NullInt64
		if err := rs.Scan(&name, &sum); err != nil {
			return nil, err
		}
		// 回来的是 `库名.表名`，只留表名
		if i := strings.LastIndexByte(name, '.'); i >= 0 {
			name = name[i+1:]
		}
		out[name] = uint64(sum.Int64) // 表不存在时 Checksum 是 NULL → 0，不算错
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}
	if len(out) != len(watchedTables) {
		return nil, fmt.Errorf("CHECKSUM TABLE 只回了 %d 张表，期望 %d 张", len(out), len(watchedTables))
	}
	return out, nil
}

// mix 把几个数揉成一个 rev。只求「值变了它一定变」，不求可比较。
func mix(vs ...uint64) uint64 {
	h := fnv.New64a()
	var b [8]byte
	for _, v := range vs {
		for i := 0; i < 8; i++ {
			b[i] = byte(v >> (8 * i))
		}
		_, _ = h.Write(b[:])
	}
	return h.Sum64()
}

func (w *Watcher) warnOnce(key string, err error) {
	if _, loaded := w.warnedOnce.LoadOrStore(key, true); loaded {
		return
	}
	log.Printf("dbwatch: %s 查询失败，页面将退回手工刷新: %v", key, err)
}
