package enable

import (
	"context"
	"database/sql"
	"fmt"
	"os"
	"testing"
	"time"

	_ "github.com/go-sql-driver/mysql"
)

// ---------- 不用连库的那几条 ----------

func TestParseMomentRejectsJunk(t *testing.T) {
	if _, ok := parseMoment("", "08:00:00"); ok {
		t.Error("没有日期就不该算出时刻")
	}
	// 库里存量数据里真有这种
	if _, ok := parseMoment("0000-00-00", "08:00:00"); ok {
		t.Error("0000-00-00 解析不了，该返回 false 而不是一个假时刻")
	}
	got, ok := parseMoment("2026-03-04", "")
	if !ok || got.Hour() != 0 {
		t.Errorf("时间留空该按 00:00:00 算，得到 %v ok=%v", got, ok)
	}
}

// ⚠ tasktype = 15 同时属于作息方案和文字语音，靠 info 分（契约 C-38）。
// 判错的后果是给文字语音发一条 project?name=（方案名是空串），后台收到一条废报文。
func TestIsBellItemSplitsType15ByInfo(t *testing.T) {
	cases := []struct {
		tasktype int
		info     string
		want     bool
	}{
		{1, "春季作息", true},
		{15, "春季作息", true},
		{15, "", false},   // 文字语音
		{15, "  ", false}, // 只有空白也算没有
		{2, "随便", false},  // 文件广播不是作息条目
		{30, "", false},
	}
	for _, c := range cases {
		if got := isBellItem(c.tasktype, c.info); got != c.want {
			t.Errorf("isBellItem(%d, %q) = %v，想要 %v", c.tasktype, c.info, got, c.want)
		}
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
	var n int
	if err := db.QueryRowContext(c,
		`SELECT COUNT(*) FROM information_schema.tables
		  WHERE table_schema = DATABASE() AND table_name = 'enable_run'`).Scan(&n); err != nil || n == 0 {
		_ = db.Close()
		t.Skip("测试库里没有 enable_run 表，先跑 db/enable_tables.sql")
	}
	t.Cleanup(func() { _ = db.Close() })
	return db
}

// fixture 建一条任务 + 一条计划，返回它们的 id，并登记好清理。
func fixture(t *testing.T, db *sql.DB, prevState, wantState int, start, end time.Time) (int64, int64) {
	t.Helper()
	ctx := context.Background()
	const mark = "E2E启用计划测试"
	res, err := db.ExecContext(ctx, `
		INSERT INTO task (taskname, tasktype, playtime, projectstate, info, channel, sec_task_id,
		                  startdate, enddate, exemodel, defaultvolume, priority,
		                  timelengthtype, timelength, datasendmodel, israndomplay, prepower)
		VALUES (?, 2, '08:00:00', ?, '', 0, 0, '2026-01-01', '2026-12-31', '1111111', 80, 10,
		        1, 30, 0, 0, 0)`,
		mark, prevState)
	if err != nil {
		t.Fatalf("建测试任务：%v", err)
	}
	taskID, _ := res.LastInsertId()

	res, err = db.ExecContext(ctx, `
		INSERT INTO enabletask (enstate, startdate, starttime, enddate, endtime, taskid, flag)
		VALUES (?, ?, ?, ?, ?, ?, 0)`,
		fmt.Sprint(wantState), start.Format("2006-01-02"), start.Format("15:04:05"),
		nullIfEmpty(end.Format("2006-01-02")), nullIfEmpty(end.Format("15:04:05")),
		fmt.Sprint(taskID))
	if err != nil {
		t.Fatalf("建测试计划：%v", err)
	}
	planID, _ := res.LastInsertId()

	t.Cleanup(func() {
		_, _ = db.Exec(`DELETE FROM enable_run WHERE plan_id = ?`, planID)
		_, _ = db.Exec(`DELETE FROM enabletask WHERE id = ?`, planID)
		_, _ = db.Exec(`DELETE FROM task WHERE taskid = ?`, taskID)
	})
	return planID, taskID
}

func stateOf(t *testing.T, db *sql.DB, taskID int64) int {
	t.Helper()
	var v int
	if err := db.QueryRowContext(context.Background(),
		`SELECT COALESCE(projectstate,0) FROM task WHERE taskid = ?`, taskID).Scan(&v); err != nil {
		t.Fatalf("读任务状态：%v", err)
	}
	return v
}

// 整条链路：到点前 8 秒置状态 → 结束时刻恢复原状态。
//
// 时钟是注进去的，所以不用真的等 —— 把「现在」拨到各个关键点上跑一轮就行。
func TestSchedulerAppliesThenRestores(t *testing.T) {
	db := testDB(t)
	base := time.Now().Truncate(time.Second)
	start := base.Add(time.Minute)
	end := start.Add(2 * time.Minute)
	// 原来是启用(0)，计划要停用(1)
	planID, taskID := fixture(t, db, ActionEnable, ActionDisable, start, end)

	now := base
	s := NewScheduler(db, nil, WithClock(func() time.Time { return now }))
	ctx := context.Background()

	// ① 还差一分钟，什么都不该动
	if err := s.Run(ctx); err != nil {
		t.Fatalf("跑一轮：%v", err)
	}
	if got := stateOf(t, db, taskID); got != ActionEnable {
		t.Fatalf("时候没到就把状态改了：%d", got)
	}

	// ② 差 11 秒：还差一点点，仍然不该动（提前量是 10 秒）
	now = start.Add(-11 * time.Second)
	if err := s.Run(ctx); err != nil {
		t.Fatalf("跑一轮：%v", err)
	}
	if got := stateOf(t, db, taskID); got != ActionEnable {
		t.Fatalf("提前 11 秒就动手了，提前量应该是 10 秒：%d", got)
	}

	// ③ 差 10 秒：该置成停用了
	now = start.Add(-10 * time.Second)
	if err := s.Run(ctx); err != nil {
		t.Fatalf("跑一轮：%v", err)
	}
	if got := stateOf(t, db, taskID); got != ActionDisable {
		t.Fatalf("提前 10 秒该把状态置好，现在还是 %d", got)
	}
	// 原状态要记下来，不然没法恢复
	var prev int
	if err := db.QueryRow(`SELECT prev_state FROM enable_run WHERE plan_id = ? AND taskid = ?`,
		planID, taskID).Scan(&prev); err != nil {
		t.Fatalf("读 enable_run：%v", err)
	}
	if prev != ActionEnable {
		t.Fatalf("应用前的状态没记对：记成了 %d", prev)
	}

	// ④ 再跑几轮不该有任何变化（幂等）。
	//    这一条很重要：audioserver 可能也在处理同一条计划。
	for i := 0; i < 3; i++ {
		now = now.Add(time.Second)
		if err := s.Run(ctx); err != nil {
			t.Fatalf("跑一轮：%v", err)
		}
	}
	var runs int
	if err := db.QueryRow(`SELECT COUNT(*) FROM enable_run WHERE plan_id = ?`, planID).Scan(&runs); err != nil {
		t.Fatalf("数 enable_run：%v", err)
	}
	if runs != 1 {
		t.Fatalf("同一条计划的同一条任务登记了 %d 次，应该只有 1 次", runs)
	}
	// flag 是「已执行」的标记：执行完必须置 1，否则下一轮还会再看一遍
	var flag int
	if err := db.QueryRow(`SELECT COALESCE(flag,0) FROM enabletask WHERE id = ?`, planID).Scan(&flag); err != nil {
		t.Fatalf("读 flag：%v", err)
	}
	if flag != 1 {
		t.Fatalf("执行完该把 flag 置成 1，现在是 %d", flag)
	}

	// ⑤ 结束时刻没到，不该恢复
	now = end.Add(-time.Second)
	if err := s.Run(ctx); err != nil {
		t.Fatalf("跑一轮：%v", err)
	}
	if got := stateOf(t, db, taskID); got != ActionDisable {
		t.Fatalf("还没到结束时刻就恢复了：%d", got)
	}

	// ⑥ 结束时刻到了：恢复成原来的启用
	now = end
	if err := s.Run(ctx); err != nil {
		t.Fatalf("跑一轮：%v", err)
	}
	if got := stateOf(t, db, taskID); got != ActionEnable {
		t.Fatalf("到结束时刻该恢复成原来的状态(0)，现在是 %d", got)
	}
	// ⑦ 恢复也只做一次：之后人手工改成停用，不该被再按回去
	if _, err := db.Exec(`UPDATE task SET projectstate = 1 WHERE taskid = ?`, taskID); err != nil {
		t.Fatalf("手工改状态：%v", err)
	}
	now = end.Add(time.Minute)
	if err := s.Run(ctx); err != nil {
		t.Fatalf("跑一轮：%v", err)
	}
	if got := stateOf(t, db, taskID); got != ActionDisable {
		t.Fatalf("恢复只该做一次，人手工改过之后又被按回去了：%d", got)
	}
}

// 没填结束日期时间的计划只应用、永不恢复 —— 那是「一直保持这个状态」的意思。
func TestSchedulerKeepsStateWhenNoEnd(t *testing.T) {
	db := testDB(t)
	base := time.Now().Truncate(time.Second)
	start := base.Add(time.Minute)
	planID, taskID := fixture(t, db, ActionEnable, ActionDisable, start, start)
	// 把结束那一对清掉（fixture 总会填）
	if _, err := db.Exec(`UPDATE enabletask SET enddate = NULL, endtime = NULL WHERE id = ?`, planID); err != nil {
		t.Fatalf("清结束时刻：%v", err)
	}

	now := start
	s := NewScheduler(db, nil, WithClock(func() time.Time { return now }))
	if err := s.Run(context.Background()); err != nil {
		t.Fatalf("跑一轮：%v", err)
	}
	if got := stateOf(t, db, taskID); got != ActionDisable {
		t.Fatalf("到点该置成停用，现在是 %d", got)
	}
	now = start.Add(24 * time.Hour)
	if err := s.Run(context.Background()); err != nil {
		t.Fatalf("跑一轮：%v", err)
	}
	if got := stateOf(t, db, taskID); got != ActionDisable {
		t.Fatalf("没填结束时刻的计划不该恢复，状态被改回了 %d", got)
	}
}

// flag = 1 的计划一律不看 —— 现场的约定：执行完置 1，置 1 之后不再判断。
func TestSchedulerSkipsFlaggedPlans(t *testing.T) {
	db := testDB(t)
	base := time.Now().Truncate(time.Second)
	start := base.Add(time.Minute)
	planID, taskID := fixture(t, db, ActionEnable, ActionDisable, start, start.Add(time.Hour))
	if _, err := db.Exec(`UPDATE enabletask SET flag = 1 WHERE id = ?`, planID); err != nil {
		t.Fatalf("置 flag：%v", err)
	}

	s := NewScheduler(db, nil, WithClock(func() time.Time { return start }))
	if err := s.Run(context.Background()); err != nil {
		t.Fatalf("跑一轮：%v", err)
	}
	if got := stateOf(t, db, taskID); got != ActionEnable {
		t.Fatalf("flag = 1 的计划不该再被执行，状态被改成了 %d", got)
	}
}

// 默认不设补跑窗口：flag = 0 就是「还没执行过」，开始时刻过去很久也照样执行 ——
// 那是「该执行还没执行」，不是「重复执行」。
func TestSchedulerRunsOverduePlanByDefault(t *testing.T) {
	db := testDB(t)
	base := time.Now().Truncate(time.Second)
	start := base.Add(-time.Hour) // 一小时前就该执行了，但 flag 还是 0
	_, taskID := fixture(t, db, ActionEnable, ActionDisable, start, base.Add(time.Hour))

	s := NewScheduler(db, nil, WithClock(func() time.Time { return base }))
	if err := s.Run(context.Background()); err != nil {
		t.Fatalf("跑一轮：%v", err)
	}
	if got := stateOf(t, db, taskID); got != ActionDisable {
		t.Fatalf("flag = 0 的过期计划该补执行，状态还是 %d", got)
	}
}

// 配上 catch_up 之后，超出窗口的就不补跑了 —— 给「库里躺着一批陈年 flag=0」的现场用。
func TestSchedulerHonoursCatchUpWindow(t *testing.T) {
	db := testDB(t)
	base := time.Now().Truncate(time.Second)
	start := base.Add(-time.Hour)
	_, taskID := fixture(t, db, ActionEnable, ActionDisable, start, base.Add(time.Hour))

	s := NewScheduler(db, nil,
		WithCatchUp(10*time.Minute),
		WithClock(func() time.Time { return base }))
	if err := s.Run(context.Background()); err != nil {
		t.Fatalf("跑一轮：%v", err)
	}
	if got := stateOf(t, db, taskID); got != ActionEnable {
		t.Fatalf("超出补跑窗口的计划不该执行，状态被改成了 %d", got)
	}
}

// 表还没建时，执行器要认出来并停掉自己 —— 而不是每秒往日志里刷一条错。
func TestMissingTableIsRecognised(t *testing.T) {
	if !missingTable(fmt.Errorf("登记启用计划执行: Error 1146: Table 'audioserver.enable_run' doesn't exist")) {
		t.Error("没认出「enable_run 还没建」这种失败 —— 会每秒刷一条错把日志冲垮")
	}
	if missingTable(fmt.Errorf("查询待执行的启用计划: connection refused")) {
		t.Error("把普通失败当成缺表了 —— 那会让执行器自己停掉，再也不重试")
	}
	if missingTable(nil) {
		t.Error("nil 不是失败")
	}
}
