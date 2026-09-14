package dashboard

import "testing"

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
