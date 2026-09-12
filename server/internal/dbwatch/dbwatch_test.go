package dbwatch

import (
	"bytes"
	"context"
	"database/sql"
	"encoding/json"
	"os"
	"strconv"
	"strings"
	"testing"
	"time"

	_ "github.com/go-sql-driver/mysql"
)

// mix 只要求「值变了它一定变」。
func TestMixChangesWithInput(t *testing.T) {
	base := mix(1, 2, 3)
	for _, c := range [][]uint64{{2, 2, 3}, {1, 3, 3}, {1, 2, 4}} {
		if got := mix(c...); got == base {
			t.Errorf("%v 该算出与 %d 不同的值", c, base)
		}
	}
	if mix(1, 2, 3) != base {
		t.Error("同样的输入必须算出同样的值")
	}
}

// diff 只比「一样 / 不一样」，而且只看前端订阅过的主题。
func TestDiffOnlyWatchesSubscribed(t *testing.T) {
	cur := map[Topic]string{TopicTerminal: "64"}

	if got := diff(map[Topic]string{TopicTerminal: "64"}, cur); len(got) != 0 {
		t.Errorf("都一样时不该报变化：%v", got)
	}
	// 没订阅的主题（以及已经撤掉的 task）传上来一律忽略
	if got := diff(map[Topic]string{"task": "随便什么"}, cur); len(got) != 0 {
		t.Errorf("不在 Topics 里的名字不该被理会：%v", got)
	}
	got := diff(map[Topic]string{TopicTerminal: "63"}, cur)
	if len(got) != 1 || got[0] != TopicTerminal {
		t.Errorf("该报 terminal 变了，得到 %v", got)
	}
	// ⚠ rev 是哈希，比大小没有意义：随便一个不一样的值都算变了
	if got := diff(map[Topic]string{TopicTerminal: "ffffff"}, cur); len(got) != 1 {
		t.Errorf("值不同就是变了，不能按「只增不减」判：%v", got)
	}
}

// 只盯 terminal，不盯 task。
//
// 六个任务页（作息方案 / 文件广播 / 采播管理 / 终端功放 / 文字语音 / led播放）
// 的无感刷新按需求方要求撤掉了，task 表整个不该再出现在这里 ——
// 它是两张表里大得多的那个（现网量级 5 万行 vs 终端 3 千行），
// 而每一拍都是一次全表 CHECKSUM。
func TestOnlyTerminalIsWatched(t *testing.T) {
	if len(Topics) != 1 || Topics[0] != TopicTerminal {
		t.Errorf("只该盯 terminal 一个主题，实际 %v", Topics)
	}
	if len(watchedTables) != 1 || watchedTables[0] != "terminal" {
		t.Errorf("只该对 terminal 算指纹，实际 %v", watchedTables)
	}
	// ⚠ 表名是直接拼进 SQL 的（CHECKSUM TABLE 不接受占位符），
	//   所以它必须来自这里写死的常量，绝不能来自请求参数。
	for _, tb := range watchedTables {
		if strings.ContainsAny(tb, " ;'\"`\\()") {
			t.Errorf("表名里有可疑字符：%q", tb)
		}
	}
}

// ⚠ 这一条是整件事的教训：rev 对外必须是字符串，且必须经得起 JSON 往返。
//
// 内部是 uint64（FNV-64），而 JS 的 Number 是 float64，精确整数上限
// 只有 9007199254740991。按数字发出去，浏览器 JSON.parse 就把它四舍五入了：
//
//	服务端 13272240285988269346 → 浏览器 13272240285988270000
//
// 发回来的永远对不上，于是每次都判「变了」，长轮询立刻返回、页面立刻重查、
// 立刻再问……实测一个标签页每秒 165 次列表查询，服务器被自己人打垮，
// 现网表现就是「点开终端管理一堆 500」。
func TestRevSurvivesJSONRoundTrip(t *testing.T) {
	w := New(nil, time.Hour)
	// 挑一个超过 JS 安全整数上限的值
	const big = uint64(13272240285988269346)
	w.revs[TopicTerminal] = big
	w.ready = true

	revs := w.Revs()
	got := revs[TopicTerminal]
	if _, err := strconv.ParseUint(got, 16, 64); err != nil {
		t.Fatalf("rev 该是十六进制字符串，得到 %q", got)
	}

	// 过一遍 JSON（这正是浏览器拿到的东西）
	blob, err := json.Marshal(map[string]any{"revs": revs})
	if err != nil {
		t.Fatal(err)
	}
	if bytes.Contains(blob, []byte("13272240285988269346")) {
		t.Fatalf("rev 被当成数字序列化了，JS 会把它四舍五入：%s", blob)
	}
	var back struct {
		Revs map[string]string `json:"revs"`
	}
	if err := json.Unmarshal(blob, &back); err != nil {
		t.Fatal(err)
	}
	if back.Revs[TopicTerminal] != got {
		t.Errorf("JSON 往返之后对不上了：%q → %q", got, back.Revs[TopicTerminal])
	}
	// 往返回来的值拿去 diff，必须判「没变」
	if d := diff(map[Topic]string{TopicTerminal: back.Revs[TopicTerminal]}, revs); len(d) != 0 {
		t.Errorf("往返之后被判成变了 —— 这就是那个死循环：%v", d)
	}
}

// ---------- 下面这几条要连真库 ----------

func testDB(t *testing.T) *sql.DB {
	t.Helper()
	dsn := os.Getenv("HTWEB_TEST_DSN")
	if dsn == "" {
		dsn = "root@unix(/run/mysqld/mysqld.sock)/audioserver?charset=utf8&parseTime=false"
	}
	db, err := sql.Open("mysql", dsn)
	if err != nil {
		t.Skipf("连不上测试库：%v", err)
	}
	c, cancel := context.WithTimeout(context.Background(), 2*time.Second)
	defer cancel()
	if err := db.PingContext(c); err != nil {
		_ = db.Close()
		t.Skipf("连不上测试库：%v", err)
	}
	t.Cleanup(func() { _ = db.Close() })
	return db
}

// ⚠ 这一条是整个包的关键：**别的程序**直接改库，我们能不能看出来。
//
// 后台 C 服务、旧版 ok112、现场脚本都在写这个库，它们不会来通知 htweb。
// 所以这里模拟的正是那种写入：一条光秃秃的 UPDATE，不碰任何计数器。
func TestPollSeesForeignUpdate(t *testing.T) {
	db := testDB(t)
	w := New(db, 50*time.Millisecond)
	ctx := context.Background()

	w.poll(ctx)
	before := w.Revs()
	if before[TopicTerminal] == "" || before[TopicTerminal] == "0" {
		t.Fatal("第一次轮询就没拿到指纹，后面比什么都没意义")
	}

	// 翻一下再翻回来，不留痕迹
	flip := func() {
		if _, err := db.ExecContext(ctx,
			`UPDATE terminal SET netstate = IF(netstate=1,0,1) WHERE id = (SELECT * FROM (SELECT MIN(id) FROM terminal) x)`); err != nil {
			t.Fatalf("改测试数据失败：%v", err)
		}
	}
	flip()
	t.Cleanup(flip)

	w.poll(ctx)
	after := w.Revs()
	if after[TopicTerminal] == before[TopicTerminal] {
		t.Error("别的程序改了 terminal 表，rev 必须跟着变 —— 不然页面会一直显示旧数据而且毫无迹象")
	}
}

// Wait 在已经有变化时必须立刻返回，不能傻等一个超时。
func TestWaitReturnsImmediatelyWhenAlreadyChanged(t *testing.T) {
	db := testDB(t)
	w := New(db, time.Hour) // 不让它自己轮询，完全手动
	w.poll(context.Background())

	stale := map[Topic]string{TopicTerminal: "肯定对不上"}
	start := time.Now()
	_, changed := w.Wait(context.Background(), stale, 5*time.Second)
	if len(changed) != 1 || changed[0] != TopicTerminal {
		t.Fatalf("该立刻报 terminal 变了，得到 %v", changed)
	}
	if d := time.Since(start); d > time.Second {
		t.Errorf("已经有变化了还等了 %v", d)
	}
}

// 没变化时挂到超时，然后回一个空的 changed —— 前端据此接着问下一轮。
func TestWaitTimesOutQuietly(t *testing.T) {
	db := testDB(t)
	w := New(db, time.Hour)
	w.poll(context.Background())

	start := time.Now()
	revs, changed := w.Wait(context.Background(), w.Revs(), time.Second)
	if len(changed) != 0 {
		t.Errorf("什么都没变却报了 %v", changed)
	}
	if len(revs) == 0 {
		t.Error("超时也要把当前版本回给前端，让它对齐")
	}
	if d := time.Since(start); d < 900*time.Millisecond {
		t.Errorf("没等满就返回了（%v）—— 那就退化成高频轮询了", d)
	}
}

// 轮询发现变化时要把等着的人叫醒，而不是让他们干等到超时。
func TestWaitWakesOnPoll(t *testing.T) {
	db := testDB(t)
	w := New(db, time.Hour)
	ctx := context.Background()
	w.poll(ctx)
	known := w.Revs()

	flip := func() {
		_, _ = db.ExecContext(ctx,
			`UPDATE terminal SET netstate = IF(netstate=1,0,1) WHERE id = (SELECT * FROM (SELECT MIN(id) FROM terminal) x)`)
	}
	t.Cleanup(flip)

	go func() {
		time.Sleep(200 * time.Millisecond)
		flip()
		w.poll(ctx)
	}()

	start := time.Now()
	_, changed := w.Wait(ctx, known, 10*time.Second)
	if len(changed) == 0 {
		t.Fatal("轮询发现变化之后没把等着的人叫醒")
	}
	if d := time.Since(start); d > 3*time.Second {
		t.Errorf("叫醒晚了 %v —— 说明走的是超时那条路，不是被通知的", d)
	}
}

// ctx 取消时立刻收工，别把协程和连接挂在那儿。
func TestWaitHonoursCancel(t *testing.T) {
	db := testDB(t)
	w := New(db, time.Hour)
	w.poll(context.Background())

	ctx, cancel := context.WithCancel(context.Background())
	go func() { time.Sleep(150 * time.Millisecond); cancel() }()

	start := time.Now()
	if _, changed := w.Wait(ctx, w.Revs(), time.Minute); len(changed) != 0 {
		t.Errorf("取消时不该报变化：%v", changed)
	}
	if d := time.Since(start); d > 2*time.Second {
		t.Errorf("取消之后又等了 %v", d)
	}
	// 等待者必须被摘干净，否则浏览器每断一次就漏一个 channel
	w.mu.RLock()
	n := len(w.waiters)
	w.mu.RUnlock()
	if n != 0 {
		t.Errorf("还剩 %d 个没摘掉的等待者", n)
	}
}
