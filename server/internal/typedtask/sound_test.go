package typedtask

import (
	"context"
	"database/sql"
	"os"
	"testing"
	"time"

	_ "github.com/go-sql-driver/mysql"
)

func soundTestDB(t *testing.T) *sql.DB {
	t.Helper()
	dsn := os.Getenv("HTWEB_TEST_DSN")
	if dsn == "" {
		dsn = "root@unix(/run/mysqld/mysqld.sock)/audioserver?charset=utf8&parseTime=false"
	}
	db, err := sql.Open("mysql", dsn)
	if err != nil {
		t.Skipf("连不上测试库：%v", err)
	}
	ctx, cancel := context.WithTimeout(context.Background(), 3*time.Second)
	defer cancel()
	if err := db.PingContext(ctx); err != nil {
		_ = db.Close()
		t.Skipf("连不上测试库：%v", err)
	}
	t.Cleanup(func() { _ = db.Close() })
	return db
}

// 跑完把 taskid = 0 那一组恢复成装机时的样子（六档各等于自己的音量值）。
func restoreTemplate(t *testing.T, db *sql.DB) {
	t.Helper()
	t.Cleanup(func() {
		_, _ = db.Exec(`DELETE FROM soundtask WHERE taskid = ?`, soundTemplateTaskID)
		for _, v := range SoundVolumeSteps {
			_, _ = db.Exec(`INSERT INTO soundtask (taskid, devid, volume, dbvalue) VALUES (?,0,?,?)`,
				soundTemplateTaskID, v, v)
		}
	})
}

func templateRowCount(t *testing.T, db *sql.DB) int {
	t.Helper()
	var n int
	if err := db.QueryRow(`SELECT COUNT(*) FROM soundtask WHERE taskid = ?`, soundTemplateTaskID).Scan(&n); err != nil {
		t.Fatalf("数模板行：%v", err)
	}
	return n
}

/*
 * ⚠ 这一条盯的是一个真的在现网长了很久的 bug。
 *
 * 原来的写法是「先 UPDATE、RowsAffected 为 0 就 INSERT」。连接串没开
 * clientFoundRows，RowsAffected 数的是**值真的变了的行**：某一档存进去的值和
 * 库里一样时 UPDATE 返回 0，于是被当成「这一行不存在」，再 INSERT 一条。
 *
 * soundtask 没有主键也没有唯一索引，数据库不会拦。于是每点一次「设置默认噪声」
 * 保存，taskid=0 那一组就多出几行，6 → 12 → 18 一直涨。
 * 读那一侧按 volume 往格子里填，重复行互相覆盖，界面上完全看不出来。
 */
func TestSetDBTemplateDoesNotPileUpDuplicateRows(t *testing.T) {
	db := soundTestDB(t)
	restoreTemplate(t, db)
	s := New(db)
	ctx := context.Background()

	want := len(SoundVolumeSteps)
	// 存三次**完全一样**的值 —— 正是触发老 bug 的那种输入
	same := make([]float64, want)
	for i, v := range SoundVolumeSteps {
		same[i] = float64(v)
	}
	for i := 0; i < 3; i++ {
		if err := s.SetDBTemplate(ctx, same); err != nil {
			t.Fatalf("第 %d 次保存：%v", i+1, err)
		}
		if got := templateRowCount(t, db); got != want {
			t.Fatalf("存了 %d 次之后模板有 %d 行，应该恒为 %d —— 重复行又长回来了", i+1, got, want)
		}
	}
}

// 已经攒下重复行的库，存一次就该修好 —— 这是给现网准备的自愈。
func TestSetDBTemplateCleansUpExistingDuplicates(t *testing.T) {
	db := soundTestDB(t)
	restoreTemplate(t, db)
	s := New(db)
	ctx := context.Background()

	// 造出老 bug 攒下的那种脏数据
	for i := 0; i < 4; i++ {
		for _, v := range SoundVolumeSteps {
			if _, err := db.Exec(`INSERT INTO soundtask (taskid, devid, volume, dbvalue) VALUES (?,0,?,?)`,
				soundTemplateTaskID, v, 1); err != nil {
				t.Fatal(err)
			}
		}
	}
	if templateRowCount(t, db) <= len(SoundVolumeSteps) {
		t.Fatal("脏数据没造出来")
	}

	vals := []float64{1, 2, 3, 4, 5, 6}
	if err := s.SetDBTemplate(ctx, vals); err != nil {
		t.Fatalf("保存：%v", err)
	}
	if got := templateRowCount(t, db); got != len(SoundVolumeSteps) {
		t.Errorf("存完还剩 %d 行，应该正好 %d 行", got, len(SoundVolumeSteps))
	}
	got, err := s.DBTemplate(ctx)
	if err != nil {
		t.Fatalf("读回来：%v", err)
	}
	for i := range vals {
		if got[i] != vals[i] {
			t.Errorf("第 %d 档读回 %v，存进去的是 %v", i, got[i], vals[i])
		}
	}
}

// ⚠ 模板用的是 taskid = 0，删这一组不能碰到真实任务（taskid > 0）的行。
func TestSetDBTemplateLeavesRealTaskRowsAlone(t *testing.T) {
	db := soundTestDB(t)
	restoreTemplate(t, db)
	s := New(db)

	const probeTask = 999888
	t.Cleanup(func() { _, _ = db.Exec(`DELETE FROM soundtask WHERE taskid = ?`, probeTask) })
	for _, v := range SoundVolumeSteps {
		if _, err := db.Exec(`INSERT INTO soundtask (taskid, devid, volume, dbvalue) VALUES (?,7,?,?)`,
			probeTask, v, 42); err != nil {
			t.Fatal(err)
		}
	}
	if err := s.SetDBTemplate(context.Background(), []float64{1, 2, 3, 4, 5, 6}); err != nil {
		t.Fatalf("保存：%v", err)
	}
	var n int
	if err := db.QueryRow(`SELECT COUNT(*) FROM soundtask WHERE taskid = ? AND dbvalue = 42`, probeTask).Scan(&n); err != nil {
		t.Fatal(err)
	}
	if n != len(SoundVolumeSteps) {
		t.Errorf("真实任务的行被动了：还剩 %d 行，应该是 %d 行", n, len(SoundVolumeSteps))
	}
}

// 档数不对要在碰库之前就拦住，报一句人话而不是 500。
func TestSetDBTemplateRejectsWrongLength(t *testing.T) {
	db := soundTestDB(t)
	restoreTemplate(t, db)
	s := New(db)
	before := templateRowCount(t, db)
	if err := s.SetDBTemplate(context.Background(), []float64{1, 2}); err == nil {
		t.Error("只给 2 档就该报错")
	}
	if got := templateRowCount(t, db); got != before {
		t.Errorf("校验没过却动了库：%d → %d 行", before, got)
	}
}
