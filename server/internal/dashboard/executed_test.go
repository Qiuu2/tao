package dashboard

import (
	"context"
	"testing"
)

// 「未执行 / 已执行」这一列的判据。
//
// 旧版 Browse_active_task_form.html:110~155 是拿 playtime 和当前时刻比：
// 没到点写「准备●」，过了点写「已执行」。新版一度把这一列做成了
// 「当天启用 / 当天停用」，按需求方要求改回来。
//
// ⚠ 星期能选任意一天之后，判据要摊到那一天上，不能只会看今天。
func TestExecutedOn(t *testing.T) {
	const today = "2026-09-14"
	const now = "12:00:00"

	cases := []struct {
		name     string
		viewDate string
		playTime string
		want     bool
	}{
		{"看过去的某天：那天早过完了", "2026-09-13", "23:59:59", true},
		{"看将来的某天：还没到", "2026-09-16", "00:00:01", false},
		{"今天，已经过了点", today, "08:00:00", true},
		{"今天，还没到点", today, "18:00:00", false},
		{"今天，正好卡在这一秒 —— 算已执行（到点就该响了）", today, now, true},
		{"没有执行时间的：谈不上已执行", today, "", false},
	}
	for _, c := range cases {
		if got := executedOn(c.viewDate, today, now, c.playTime); got != c.want {
			t.Errorf("%s：executedOn(%q,%q,%q,%q) = %v，想要 %v",
				c.name, c.viewDate, today, now, c.playTime, got, c.want)
		}
	}
}

// 「所属分类」这一格：模块名 +（归属名）。
//
// 这一列原来直接显示 filetaskfree.name，对作息方案是错的 —— 它的条目按
// task.info（方案名）归组，parentid 指的那个 filetaskfree 行只是建任务时的默认值。
// 现网七条作息条目的 parentid 全是 1（admin），显示出来就是「admin」，
// 看的人根本认不出属于哪个方案。
func TestCategoryOf(t *testing.T) {
	cases := []struct {
		name                        string
		taskType                    int
		info, fileFolder, ledFolder string
		wantModule, wantGroup       string
	}{
		{"作息方案：括号里是方案名，不是 parentid 指的那个目录", 1, "春季作息", "admin", "", "作息方案", "春季作息"},
		{"作息方案也可能是 tasktype 15", 15, "秋季作息", "admin", "", "作息方案", "秋季作息"},
		{"文件广播：括号里是任务分组", 2, "每周一", "走廊与操场", "", "文件广播", "走廊与操场"},
		{"文件广播的另一个取值 7", 7, "", "食堂", "", "文件广播", "食堂"},
		{"终端功放没有分组", 5, "", "admin", "", "终端功放", ""},
		{"采播管理没有分组", 3, "", "admin", "", "采播管理", ""},
		{"文字语音：tasktype 15 但 info 是空的", 15, "", "admin", "", "文字语音", ""},
		{"文字语音的另外两个取值", 17, "", "", "", "文字语音", ""},
		{"led播放：括号里是 LED 目录", 30, "", "", "一号楼大屏", "led播放", "一号楼大屏"},
	}
	for _, c := range cases {
		m, g := categoryOf(c.taskType, c.info, c.fileFolder, c.ledFolder)
		if m != c.wantModule || g != c.wantGroup {
			t.Errorf("%s：categoryOf(%d,%q,%q,%q) = (%q,%q)，想要 (%q,%q)",
				c.name, c.taskType, c.info, c.fileFolder, c.ledFolder, m, g, c.wantModule, c.wantGroup)
		}
	}
}

// ⚠ 15 同时属于作息方案和文字语音，靠 info 分。这条单独钉住 ——
// 判断顺序一旦写反，所有作息条目都会变成「文字语音」。
func TestCategoryOfTaskType15SplitsByInfo(t *testing.T) {
	if m, _ := categoryOf(15, "春季作息", "", ""); m != "作息方案" {
		t.Errorf("tasktype=15 且 info 非空 → 作息方案，实际 %q", m)
	}
	if m, _ := categoryOf(15, "", "", ""); m != "文字语音" {
		t.Errorf("tasktype=15 且 info 为空 → 文字语音，实际 %q", m)
	}
}

// 「所属分类」那一格里**只有作息方案带括号**。
//
// 需求方要的是「是哪个方案中的」；文件广播、led播放只写模块名 ——
// 它们括号里本来放的是任务分组名，而这一列问的是功能模块。
func TestCategoryTextOnlyBellCarriesGroup(t *testing.T) {
	ctx := context.Background()
	cases := []struct {
		module, group, want string
	}{
		{"作息方案", "春季作息", "作息方案（春季作息）"},
		{"作息方案", "", "作息方案"},
		{"文件广播", "走廊与操场", "文件广播"},
		{"led播放", "一号楼大屏", "led播放"},
		{"终端功放", "", "终端功放"},
	}
	for _, c := range cases {
		if got := categoryText(ctx, c.module, c.group); got != c.want {
			t.Errorf("categoryText(%q,%q) = %q，想要 %q", c.module, c.group, got, c.want)
		}
	}
}

// 状态列：已执行 / 执行中 / 准备执行。
//
// 旧版 Browse_active_task_form.html 就是这个结构 —— **只有 state = 0 才比时间**。
// 1 执行、2 暂停、3 立即执行都表示后台此刻手里攥着这条任务，一律「执行中」。
//
// ⚠ state 是此时此刻的值，只有看今天时才算数。
func TestRunStatusOf(t *testing.T) {
	const today = "2026-09-14"
	const now = "12:00:00"

	cases := []struct {
		name     string
		state    int
		viewDate string
		playTime string
		want     string
	}{
		{"state=1 执行中", 1, today, "08:00:00", "running"},
		{"state=2 暂停也算后台攥着它，显示执行中", 2, today, "08:00:00", "running"},
		{"state=3 立即执行", 3, today, "18:00:00", "running"},
		{"state=0 且过了点 → 已执行", 0, today, "08:00:00", "done"},
		{"state=0 且没到点 → 准备执行", 0, today, "18:00:00", "ready"},
		{"看过去的某天：那天早过完了", 0, "2026-09-13", "23:59:59", "done"},
		{"看将来的某天：还没到", 0, "2026-09-16", "00:00:01", "ready"},
		{"⚠ 看别的日子时 state 不算数：昨天那条不能说成执行中", 1, "2026-09-13", "08:00:00", "done"},
		{"⚠ 看将来某天同理", 1, "2026-09-16", "08:00:00", "ready"},
	}
	for _, c := range cases {
		if got := runStatusOf(c.state, c.viewDate, today, now, c.playTime); got != c.want {
			t.Errorf("%s：runStatusOf(%d,%q,%q,%q,%q) = %q，想要 %q",
				c.name, c.state, c.viewDate, today, now, c.playTime, got, c.want)
		}
	}
}
