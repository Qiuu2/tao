package termtype

import (
	"context"
	"database/sql"
	"os"
	"regexp"
	"strconv"
	"strings"
	"testing"
	"time"

	_ "github.com/go-sql-driver/mysql"
)

// idsIn 把条件里 `id NOT IN (…)` 那一串抠出来。
func idsIn(cond string) map[int]bool {
	m := regexp.MustCompile(`id NOT IN \(([^)]*)\)`).FindStringSubmatch(cond)
	out := map[int]bool{}
	if len(m) < 2 {
		return out
	}
	for _, s := range strings.Split(m[1], ",") {
		if n, err := strconv.Atoi(strings.TrimSpace(s)); err == nil {
			out[n] = true
		}
	}
	return out
}

// ⚠ 文字语音（ok112 flag 16）与其余任务页（flag 3）**只差一个型号 18**。
//
// 差这一个是有意的：旧版 taskttsadd.php / taskttsmodify.php 调的是 16，
// belladd.php / taskadd.php / addadmtask.php / terminalfunctionplayadd.php /
// ledtaskadd.php 调的是 3。谁要是觉得「都是任务，合并成一个吧」，这条会红。
func TestTTSDiffersFromBroadcastByExactlyType18(t *testing.T) {
	b := idsIn(Cond("t", KindBroadcast))
	s := idsIn(Cond("t", KindTTS))
	if len(b) == 0 || len(s) == 0 {
		t.Fatal("没抠出黑名单 —— Cond 的形状变了？")
	}
	for id := range b {
		if !s[id] {
			t.Errorf("型号 %d 在 flag 3 的黑名单里，却不在 flag 16 的里", id)
		}
	}
	var extra []int
	for id := range s {
		if !b[id] {
			extra = append(extra, id)
		}
	}
	if len(extra) != 1 || extra[0] != 18 {
		t.Errorf("flag 16 比 flag 3 多排掉的应该正好是 {18}，实际是 %v", extra)
	}
}

// 黑名单要照抄 ok112 那一串，逐个对。
// 不用「长度对上就行」—— 长度对得上而内容错位，是最不容易发现的一种错。
func TestBlacklistsMatchOK112Verbatim(t *testing.T) {
	// ok112 inc/config.inc.php: case 3
	want3 := []int{0, 26, 2, 7, 8, 9, 10, 12, 15, 16, 17, 21, 22, 25, 28, 29, 30, 31, 32, 36, 37, 40, 41, 42}
	// ok112 inc/config.inc.php: case 16
	want16 := []int{0, 26, 2, 7, 8, 9, 10, 12, 15, 16, 17, 18, 21, 22, 25, 28, 29, 30, 31, 32, 36, 37, 40, 41, 42}

	for _, c := range []struct {
		name string
		kind Kind
		want []int
	}{
		{"flag 3（任务终端）", KindBroadcast, want3},
		{"flag 16（文字语音）", KindTTS, want16},
	} {
		got := idsIn(Cond("t", c.kind))
		if len(got) != len(c.want) {
			t.Errorf("%s：黑名单有 %d 个，ok112 里是 %d 个", c.name, len(got), len(c.want))
		}
		for _, id := range c.want {
			if !got[id] {
				t.Errorf("%s：少排了型号 %d", c.name, id)
			}
		}
	}
}

// LED 屏只有型号 42（ok112 flag 14），而且它**不是**「isdecode=1 再排除」那种形状。
func TestLEDScreenIsAPlainTypeList(t *testing.T) {
	c := Cond("t", KindLEDScreen)
	if !strings.Contains(c, "t.typeid IN (42)") {
		t.Errorf("LED 屏的条件应该就是 t.typeid IN (42)，实际是 %s", c)
	}
	if strings.Contains(c, "isdecode") {
		t.Error("LED 屏不该判 isdecode —— 它本来就不是音频设备")
	}
}

// 不认识的 kind 按 flag 3 走。
// 少筛一点比多筛一点安全：多筛会让终端凭空消失，少筛只是多列几台。
func TestUnknownKindFallsBackToBroadcast(t *testing.T) {
	if Cond("t", Kind("没见过的")) != Cond("t", KindBroadcast) {
		t.Error("不认识的 kind 应该回落到 flag 3")
	}
}

// KindOfTaskKind：五类任务里只有 tts 是 flag 16。
func TestKindOfTaskKindOnlySplitsTTS(t *testing.T) {
	for _, k := range []string{"amplifier", "collect", "led", "sound", ""} {
		if got := KindOfTaskKind(k); got != KindBroadcast {
			t.Errorf("KindOfTaskKind(%q) = %s，想要 broadcast", k, got)
		}
	}
	if got := KindOfTaskKind("tts"); got != KindTTS {
		t.Errorf("KindOfTaskKind(\"tts\") = %s，想要 tts", got)
	}
}

// 拼出来的 SQL 得真能跑，而且真的把不出声的型号筛掉。
//
// 只验「语法没错」不够：条件写反了（比如 IN 写成 NOT IN）语法照样对，
// 结果却是「只剩不能放广播的那些」。所以这里对着真库数一遍。
func TestCondActuallyFiltersAgainstRealDB(t *testing.T) {
	dsn := os.Getenv("HTWEB_TEST_DSN")
	if dsn == "" {
		dsn = "root@unix(/run/mysqld/mysqld.sock)/audioserver?charset=utf8&parseTime=false"
	}
	db, err := sql.Open("mysql", dsn)
	if err != nil {
		t.Skipf("连不上测试库：%v", err)
	}
	defer db.Close()
	ctx, cancel := context.WithTimeout(context.Background(), 3*time.Second)
	defer cancel()
	if err := db.PingContext(ctx); err != nil {
		t.Skipf("连不上测试库：%v", err)
	}

	count := func(where string) int {
		var n int
		q := `SELECT COUNT(*) FROM terminal t`
		if where != "" {
			q += " WHERE " + where
		}
		if err := db.QueryRowContext(ctx, q).Scan(&n); err != nil {
			t.Fatalf("查 %s：%v", q, err)
		}
		return n
	}

	all := count("")
	if all == 0 {
		t.Skip("测试库里一台终端都没有")
	}
	kept := count(Cond("t", KindBroadcast))
	if kept > all {
		t.Fatalf("筛完 %d 台比总数 %d 还多 —— 条件写反了？", kept, all)
	}

	// 被筛掉的那些，必须每一台都说得出理由：要么 isdecode=0，要么在黑名单里。
	rows, err := db.QueryContext(ctx, `
		SELECT COALESCE(t.typeid,0), COALESCE(tt.isdecode,0), COUNT(*)
		  FROM terminal t LEFT JOIN terminaltype tt ON tt.id = t.typeid
		 WHERE NOT (`+Cond("t", KindBroadcast)+`)
		 GROUP BY t.typeid, tt.isdecode`)
	if err != nil {
		t.Fatalf("查被筛掉的终端：%v", err)
	}
	defer rows.Close()
	black := idsIn(Cond("t", KindBroadcast))
	dropped := 0
	for rows.Next() {
		var typeID, isDecode, n int
		if err := rows.Scan(&typeID, &isDecode, &n); err != nil {
			t.Fatal(err)
		}
		dropped += n
		if isDecode == 1 && !black[typeID] {
			t.Errorf("型号 %d 既能解码、又不在黑名单里，不该被筛掉（%d 台）", typeID, n)
		}
	}
	if err := rows.Err(); err != nil {
		t.Fatal(err)
	}
	if kept+dropped != all {
		t.Errorf("留下 %d + 筛掉 %d ≠ 总数 %d", kept, dropped, all)
	}
	t.Logf("测试库：共 %d 台，能放广播的 %d 台，筛掉 %d 台", all, kept, dropped)
}
