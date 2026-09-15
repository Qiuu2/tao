package offline

import (
	"context"
	"database/sql"
	"os"
	"testing"
	"time"

	"htweb/internal/auth"
	"htweb/internal/store"

	_ "github.com/go-sql-driver/mysql"
)

// 任务传送两个页签的库行为。
//
// 这一组都要连真库 —— 这些动作的全部内容就是「哪几张表、哪几行、写成几」，
// 拿假对象测等于把要验的东西自己实现一遍。连不上就 skip。

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

var admin = &auth.User{ID: 1, Username: "admin", IsAdmin: true}

func pager() store.Pager { return store.NewPager(1, 50) }

// scene 是一套完整的测试布景：一条任务 + 两台终端（一台有容量、一台没有）
// + 一个媒体，并把它们按 terminaloftask / mediaoftask 绑好。
//
// 「没容量那台」是这组测试的关键道具：旧版 do_offline_task 明确只发给
// totalcapacity != 0 的终端，少了它就验不出这条筛选还在不在。
type scene struct {
	taskID             int64
	termCap, termNoCap int64
	mediaID            int64
}

func newScene(t *testing.T, db *sql.DB) scene {
	t.Helper()
	ctx := context.Background()
	const mark = "E2E任务传送测试"

	res, err := db.ExecContext(ctx, `
		INSERT INTO task (taskname, tasktype, playtime, projectstate, info, channel, sec_task_id,
		                  startdate, enddate, exemodel, defaultvolume, priority,
		                  timelengthtype, timelength, datasendmodel, israndomplay, prepower, offlinestate)
		VALUES (?, 2, '08:00:00', 0, '', 0, 0, '2026-01-01', '2026-12-31', '1111111', 80, 10,
		        1, 30, 0, 0, 0, 0)`, mark)
	if err != nil {
		t.Fatalf("建测试任务：%v", err)
	}
	s := scene{}
	s.taskID, _ = res.LastInsertId()

	for i, cap := range []int64{4096, 0} {
		res, err := db.ExecContext(ctx,
			`INSERT INTO terminal (terminalname, ip, netstate, totalcapacity, typeid, groupid)
			 VALUES (?, ?, 1, ?, 1, 0)`, mark, "10.9.9."+string(rune('1'+i)), cap)
		if err != nil {
			t.Fatalf("建测试终端：%v", err)
		}
		id, _ := res.LastInsertId()
		if cap != 0 {
			s.termCap = id
		} else {
			s.termNoCap = id
		}
	}

	res, err = db.ExecContext(ctx,
		`INSERT INTO media (name, size, typeid, filename, folderid, timelength)
		 VALUES (?, 1024, 'mp3', 'e2e-transfer.mp3', 1, 30)`, mark)
	if err != nil {
		t.Fatalf("建测试媒体：%v", err)
	}
	s.mediaID, _ = res.LastInsertId()

	for _, term := range []int64{s.termCap, s.termNoCap} {
		if _, err := db.ExecContext(ctx,
			`INSERT INTO terminaloftask (taskid, terminalid, area) VALUES (?, ?, ?)`,
			s.taskID, term, AreaAll); err != nil {
			t.Fatalf("绑任务终端：%v", err)
		}
	}
	if _, err := db.ExecContext(ctx,
		`INSERT INTO mediaoftask (taskid, mediaid, sort) VALUES (?, ?, 1)`,
		s.taskID, s.mediaID); err != nil {
		t.Fatalf("绑任务媒体：%v", err)
	}

	t.Cleanup(func() {
		for _, q := range []string{
			`DELETE FROM offlinemediaofterminal WHERE taskid = ?`,
			`DELETE FROM offlinetaskofterminal WHERE taskid = ?`,
			`DELETE FROM offlinetask WHERE taskid = ?`,
			`DELETE FROM mediaoftask WHERE taskid = ?`,
			`DELETE FROM terminaloftask WHERE taskid = ?`,
			`DELETE FROM task WHERE taskid = ?`,
		} {
			_, _ = db.Exec(q, s.taskID)
		}
		_, _ = db.Exec(`DELETE FROM offlinemediaofterminal WHERE mediaid = ?`, s.mediaID)
		_, _ = db.Exec(`DELETE FROM offlinemedia WHERE id = ?`, s.mediaID)
		_, _ = db.Exec(`DELETE FROM media WHERE id = ?`, s.mediaID)
		_, _ = db.Exec(`DELETE FROM terminal WHERE id IN (?, ?)`, s.termCap, s.termNoCap)
	})
	return s
}

func scalar(t *testing.T, db *sql.DB, q string, args ...interface{}) int {
	t.Helper()
	var n int
	if err := db.QueryRow(q, args...).Scan(&n); err != nil {
		if err == sql.ErrNoRows {
			return -1
		}
		t.Fatalf("查 %s：%v", q, err)
	}
	return n
}

// ①「服务器任务」下发：终端是从任务自己的清单里取的，且只取有容量的那台。
func TestServerTransferUsesTaskOwnTerminals(t *testing.T) {
	db := testDB(t)
	s := newScene(t, db)
	svc := New(db)
	ctx := context.Background()

	res, err := svc.ServerTransfer(ctx, admin, []int64{s.taskID}, CloudImmediate)
	if err != nil {
		t.Fatalf("立即离线：%v", err)
	}
	if !res.OfflineChanged {
		t.Error("下发之后要通知后台（旧版 flag=1 时漏发，这是特意补的）")
	}

	// 副本建出来了，状态 = 2
	if got := scalar(t, db, `SELECT COALESCE(offlinestate,0) FROM offlinetask WHERE taskid = ?`, s.taskID); got != 2 {
		t.Errorf("offlinetask.offlinestate = %d，想要 2", got)
	}
	// 源任务也标成 2 —— 它从此归「云广播任务」页签管
	if got := scalar(t, db, `SELECT COALESCE(offlinestate,0) FROM task WHERE taskid = ?`, s.taskID); got != 2 {
		t.Errorf("task.offlinestate = %d，想要 2", got)
	}
	// ⚠ 只发给有容量那台。没容量的那台一行都不该有
	if got := scalar(t, db,
		`SELECT COUNT(*) FROM offlinetaskofterminal WHERE taskid = ? AND terminalid = ?`,
		s.taskID, s.termNoCap); got != 0 {
		t.Errorf("没有存储容量的终端不该收到下发关系，却有 %d 行", got)
	}
	if got := scalar(t, db,
		`SELECT COALESCE(offlinestate,0) FROM offlinetaskofterminal WHERE taskid = ? AND terminalid = ?`,
		s.taskID, s.termCap); got != 2 {
		t.Errorf("有容量终端的下发关系 = %d，想要 2", got)
	}
	// 媒体副本也要补出来，否则终端拿不到文件
	if got := scalar(t, db, `SELECT COUNT(*) FROM offlinemedia WHERE id = ?`, s.mediaID); got != 1 {
		t.Errorf("offlinemedia 副本没补上（%d 行）", got)
	}
	if got := scalar(t, db,
		`SELECT COALESCE(offlinestate,0) FROM offlinemediaofterminal
		  WHERE taskid = ? AND terminalid = ? AND mediaid = ?`,
		s.taskID, s.termCap, s.mediaID); got != 2 {
		t.Errorf("媒体下发关系 = %d，想要 2", got)
	}

	// 下发过之后它就不该再出现在「服务器任务」页签里了
	lr, err := svc.ListServerTasks(ctx, admin, ServerTaskQuery{Pager: pager()})
	if err != nil {
		t.Fatalf("列服务器任务：%v", err)
	}
	for _, it := range lr.Items {
		if it.TaskID == s.taskID {
			t.Error("已经下发过的任务还留在「服务器任务」页签里")
		}
	}
}

// ② 任务把某台终端移出去之后再下发，那台终端上的旧副本要被标成「立即删除」。
//
// 旧版收尾那两句 `SET offlinestate='5' … AND offlinestate='0'` 就是干这个的。
// 不标的话，终端上会永远留着一份已经不属于这条任务的文件。
func TestServerTransferMarksDroppedTerminals(t *testing.T) {
	db := testDB(t)
	s := newScene(t, db)
	svc := New(db)
	ctx := context.Background()

	// 先把没容量那台改成有容量，发一次，让它拿到副本
	if _, err := db.Exec(`UPDATE terminal SET totalcapacity = 2048 WHERE id = ?`, s.termNoCap); err != nil {
		t.Fatal(err)
	}
	if _, err := svc.ServerTransfer(ctx, admin, []int64{s.taskID}, CloudIdle); err != nil {
		t.Fatalf("首次下发：%v", err)
	}
	if got := scalar(t, db,
		`SELECT COALESCE(offlinestate,0) FROM offlinetaskofterminal WHERE taskid = ? AND terminalid = ?`,
		s.taskID, s.termNoCap); got != 1 {
		t.Fatalf("首次下发后第二台 = %d，想要 1", got)
	}

	// 把第二台从任务里摘掉，再发一次
	if _, err := db.Exec(`DELETE FROM terminaloftask WHERE taskid = ? AND terminalid = ?`,
		s.taskID, s.termNoCap); err != nil {
		t.Fatal(err)
	}
	if _, err := svc.ServerTransfer(ctx, admin, []int64{s.taskID}, CloudIdle); err != nil {
		t.Fatalf("再次下发：%v", err)
	}
	if got := scalar(t, db,
		`SELECT COALESCE(offlinestate,0) FROM offlinetaskofterminal WHERE taskid = ? AND terminalid = ?`,
		s.taskID, s.termNoCap); got != int(StateDeleteNow) {
		t.Errorf("被移出任务的终端 = %d，想要 %d（立即删除）", got, StateDeleteNow)
	}
}

// ③ 云广播任务页签：空闲离线 / 立即离线写的是 1 / 2，不是按钮号 14 / 15。
func TestTransferBulkIdleImmediateWriteOneAndTwo(t *testing.T) {
	db := testDB(t)
	s := newScene(t, db)
	svc := New(db)
	ctx := context.Background()
	if _, err := svc.ServerTransfer(ctx, admin, []int64{s.taskID}, CloudImmediate); err != nil {
		t.Fatalf("准备副本：%v", err)
	}

	for _, c := range []struct {
		action CloudAction
		want   int
	}{{CloudIdle, 1}, {CloudImmediate, 2}} {
		if _, err := svc.TransferBulk(ctx, admin, []int64{s.taskID}, c.action); err != nil {
			t.Fatalf("%s：%v", c.action, err)
		}
		for _, q := range []string{
			`SELECT COALESCE(offlinestate,0) FROM offlinemediaofterminal WHERE taskid = ? AND terminalid = ?`,
			`SELECT COALESCE(offlinestate,0) FROM offlinetaskofterminal WHERE taskid = ? AND terminalid = ?`,
		} {
			if got := scalar(t, db, q, s.taskID, s.termCap); got != c.want {
				t.Errorf("%s 之后 %q = %d，想要 %d", c.action, q, got, c.want)
			}
		}
		if got := scalar(t, db, `SELECT COALESCE(offlinestate,0) FROM offlinetask WHERE taskid = ?`, s.taskID); got != c.want {
			t.Errorf("%s 之后副本 = %d，想要 %d", c.action, got, c.want)
		}
	}
}

// ③b 同一个动作连点两次，第二次不能报「还没有下发到任何终端」。
//
// 连接串没开 clientFoundRows，UPDATE 的 RowsAffected 数的是「值真的变了的行」——
// 第二次一行都没变，拿它当判据就会报出一句假话。回执里的条数改成 SELECT COUNT 数。
func TestTransferBulkTwiceInARowStillReportsRows(t *testing.T) {
	db := testDB(t)
	s := newScene(t, db)
	svc := New(db)
	ctx := context.Background()
	if _, err := svc.ServerTransfer(ctx, admin, []int64{s.taskID}, CloudImmediate); err != nil {
		t.Fatalf("准备副本：%v", err)
	}
	first, err := svc.TransferBulk(ctx, admin, []int64{s.taskID}, CloudIdle)
	if err != nil {
		t.Fatalf("第一次空闲离线：%v", err)
	}
	second, err := svc.TransferBulk(ctx, admin, []int64{s.taskID}, CloudIdle)
	if err != nil {
		t.Fatalf("第二次空闲离线不该报错：%v", err)
	}
	if second.TaskRows != first.TaskRows || second.MediaRows != first.MediaRows {
		t.Errorf("两次回执的条数该一样：第一次 %d/%d，第二次 %d/%d",
			first.MediaRows, first.TaskRows, second.MediaRows, second.TaskRows)
	}
	if second.TaskRows == 0 {
		t.Error("命中数不该是 0 —— 这条任务确实有下发关系")
	}
}

// ④ 停止离线写的是**两个不同的值**：媒体 11、任务与副本 12。
//
// 早前这里三张表一律写 11 —— 12 才是字典里的「传输已停止」，
// 后台据此判断「已经停下来了」，写成 11 它会一直当成「还在停的路上」。
func TestTransferBulkStopSplitsElevenAndTwelve(t *testing.T) {
	db := testDB(t)
	s := newScene(t, db)
	svc := New(db)
	ctx := context.Background()
	if _, err := svc.ServerTransfer(ctx, admin, []int64{s.taskID}, CloudImmediate); err != nil {
		t.Fatalf("准备副本：%v", err)
	}
	if _, err := svc.TransferBulk(ctx, admin, []int64{s.taskID}, CloudStop); err != nil {
		t.Fatalf("停止离线：%v", err)
	}
	if got := scalar(t, db,
		`SELECT COALESCE(offlinestate,0) FROM offlinemediaofterminal WHERE taskid = ? AND terminalid = ?`,
		s.taskID, s.termCap); got != 11 {
		t.Errorf("媒体行 = %d，想要 11", got)
	}
	if got := scalar(t, db,
		`SELECT COALESCE(offlinestate,0) FROM offlinetaskofterminal WHERE taskid = ? AND terminalid = ?`,
		s.taskID, s.termCap); got != 12 {
		t.Errorf("任务行 = %d，想要 12", got)
	}
	if got := scalar(t, db, `SELECT COALESCE(offlinestate,0) FROM offlinetask WHERE taskid = ?`, s.taskID); got != 12 {
		t.Errorf("副本 = %d，想要 12", got)
	}
}

// ⑤ 删除类动作把源任务放回「服务器任务」页签（task.offlinestate 归 0）。
func TestTransferBulkDeleteReleasesSourceTask(t *testing.T) {
	db := testDB(t)
	s := newScene(t, db)
	svc := New(db)
	ctx := context.Background()
	if _, err := svc.ServerTransfer(ctx, admin, []int64{s.taskID}, CloudImmediate); err != nil {
		t.Fatalf("准备副本：%v", err)
	}
	if _, err := svc.TransferBulk(ctx, admin, []int64{s.taskID}, CloudDeleteNow); err != nil {
		t.Fatalf("立即删除：%v", err)
	}
	if got := scalar(t, db, `SELECT COALESCE(offlinestate,0) FROM task WHERE taskid = ?`, s.taskID); got != 0 {
		t.Errorf("task.offlinestate = %d，删除类动作之后该归 0", got)
	}
	// 行还在，只是打了「立即删除」的标 —— 真删由后台做
	if got := scalar(t, db, `SELECT COUNT(*) FROM offlinetaskofterminal WHERE taskid = ?`, s.taskID); got == 0 {
		t.Error("删除类动作只该打标记，不该自己把行删了")
	}
}

// ⑥ 离线播放 / 停止离线播放：一行库都不写，只出一条 task 报文。
func TestTransferBulkPlayWritesNothing(t *testing.T) {
	db := testDB(t)
	s := newScene(t, db)
	svc := New(db)
	ctx := context.Background()
	if _, err := svc.ServerTransfer(ctx, admin, []int64{s.taskID}, CloudImmediate); err != nil {
		t.Fatalf("准备副本：%v", err)
	}
	before := scalar(t, db, `SELECT COALESCE(offlinestate,0) FROM offlinetask WHERE taskid = ?`, s.taskID)

	res, err := svc.TransferBulk(ctx, admin, []int64{s.taskID}, CloudOfflinePlay)
	if err != nil {
		t.Fatalf("离线播放：%v", err)
	}
	if after := scalar(t, db, `SELECT COALESCE(offlinestate,0) FROM offlinetask WHERE taskid = ?`, s.taskID); after != before {
		t.Errorf("离线播放改了库：%d → %d", before, after)
	}
	if res.OfflineChanged {
		t.Error("离线播放不该发 task?state=15（旧版也没发）")
	}
	if len(res.Notices) != 1 || res.Notices[0].Kind != "task" || res.Notices[0].State != 16 {
		t.Errorf("报文不对：%+v", res.Notices)
	}

	res, err = svc.TransferBulk(ctx, admin, []int64{s.taskID}, CloudOfflinePlayStop)
	if err != nil {
		t.Fatalf("停止离线播放：%v", err)
	}
	if len(res.Notices) != 1 || res.Notices[0].State != 17 {
		t.Errorf("停止离线播放报文不对：%+v", res.Notices)
	}
}

// ⑦ 删除离线音乐：三张表真删，源任务归 0，报文带着终端串。
func TestTransferBulkDeleteMusicReallyDeletes(t *testing.T) {
	db := testDB(t)
	s := newScene(t, db)
	svc := New(db)
	ctx := context.Background()
	if _, err := svc.ServerTransfer(ctx, admin, []int64{s.taskID}, CloudImmediate); err != nil {
		t.Fatalf("准备副本：%v", err)
	}

	res, err := svc.TransferBulk(ctx, admin, []int64{s.taskID}, CloudDeleteMusic)
	if err != nil {
		t.Fatalf("删除离线音乐：%v", err)
	}
	for _, q := range []string{
		`SELECT COUNT(*) FROM offlinetask WHERE taskid = ?`,
		`SELECT COUNT(*) FROM offlinetaskofterminal WHERE taskid = ?`,
		`SELECT COUNT(*) FROM offlinemediaofterminal WHERE taskid = ?`,
	} {
		if got := scalar(t, db, q, s.taskID); got != 0 {
			t.Errorf("%q 还剩 %d 行", q, got)
		}
	}
	if got := scalar(t, db, `SELECT COALESCE(offlinestate,0) FROM task WHERE taskid = ?`, s.taskID); got != 0 {
		t.Errorf("task.offlinestate = %d，该归 0", got)
	}
	// offlinemedia 是按 mediaid 存的、跨任务共用，不能跟着删
	if got := scalar(t, db, `SELECT COUNT(*) FROM offlinemedia WHERE id = ?`, s.mediaID); got != 1 {
		t.Error("offlinemedia 副本被误删了 —— 它是跨任务共用的")
	}
	if len(res.Notices) != 1 {
		t.Fatalf("该有一条报文，得到 %+v", res.Notices)
	}
	n := res.Notices[0]
	if n.Kind != "terminal" || n.State != 18 || n.TaskID != s.taskID ||
		len(n.TerminalIDs) != 1 || n.TerminalIDs[0] != s.termCap {
		t.Errorf("报文不对：%+v", n)
	}
}

// ⑧ 按终端圈范围的三个清除动作，不许从「按任务」这一页调进来。
func TestTransferBulkRejectsTerminalScopedActions(t *testing.T) {
	db := testDB(t)
	s := newScene(t, db)
	svc := New(db)
	ctx := context.Background()
	if _, err := svc.ServerTransfer(ctx, admin, []int64{s.taskID}, CloudImmediate); err != nil {
		t.Fatalf("准备副本：%v", err)
	}
	for _, a := range []CloudAction{CloudClearAll, CloudClearTermMedia, CloudClearIdle} {
		if _, err := svc.TransferBulk(ctx, admin, []int64{s.taskID}, a); err == nil {
			t.Errorf("%s 不该被任务传送页接受", a)
		}
	}
	// 反过来也一样：这一页独有的三个不许从云广播终端页调
	for _, a := range []CloudAction{CloudOfflinePlay, CloudOfflinePlayStop, CloudDeleteMusic} {
		if _, err := svc.CloudBulk(ctx, admin, []int64{s.termCap}, a); err == nil {
			t.Errorf("%s 不该被云广播终端页接受", a)
		}
	}
}

// ⑨ 所有媒体行都完成了，副本要自己补成「离线完成」。
//
// 后台服务只写 offlinemediaofterminal，不回写 offlinetask ——
// 不补这一下，界面上会一直停在「正在立即离线」，看着像卡住了。
func TestListTransferHealsFinishedCopies(t *testing.T) {
	db := testDB(t)
	s := newScene(t, db)
	svc := New(db)
	ctx := context.Background()
	if _, err := svc.ServerTransfer(ctx, admin, []int64{s.taskID}, CloudImmediate); err != nil {
		t.Fatalf("准备副本：%v", err)
	}
	// 装成后台把媒体都传完了
	if _, err := db.Exec(
		`UPDATE offlinemediaofterminal SET offlinestate = 3 WHERE taskid = ?`, s.taskID); err != nil {
		t.Fatal(err)
	}

	res, err := svc.ListTransferTasks(ctx, admin, TransferQuery{Pager: pager()})
	if err != nil {
		t.Fatalf("列云广播任务：%v", err)
	}
	if got := scalar(t, db, `SELECT COALESCE(offlinestate,0) FROM offlinetask WHERE taskid = ?`, s.taskID); got != 3 {
		t.Errorf("副本没被补成 3，还是 %d", got)
	}
	// 本次响应里也要已经是 3，否则界面得刷两次才对
	for _, it := range res.Items {
		if it.TaskID == s.taskID && it.State != 3 {
			t.Errorf("响应里还是 %d（%s），该是 3", it.State, it.StateText)
		}
	}
}

// ⑩ 服务器任务行内那两个链接读的是源表，不是离线表。
func TestServerTaskDetailReadsSourceTables(t *testing.T) {
	db := testDB(t)
	s := newScene(t, db)
	svc := New(db)
	ctx := context.Background()

	terms, err := svc.ServerTaskTerminals(ctx, admin, s.taskID)
	if err != nil {
		t.Fatalf("查终端清单：%v", err)
	}
	if len(terms) != 2 {
		t.Fatalf("该有 2 台（含没容量那台），得到 %d", len(terms))
	}
	var capable, skipped int
	for _, x := range terms {
		if x.Capable {
			capable++
		} else {
			skipped++
		}
	}
	if capable != 1 || skipped != 1 {
		t.Errorf("能存 / 会跳过 = %d / %d，想要 1 / 1", capable, skipped)
	}

	media, err := svc.ServerTaskMedia(ctx, admin, s.taskID)
	if err != nil {
		t.Fatalf("查媒体清单：%v", err)
	}
	if len(media) != 1 || media[0].MediaID != s.mediaID {
		t.Errorf("媒体清单不对：%+v", media)
	}
}
