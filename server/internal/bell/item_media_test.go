package bell

import (
	"errors"
	"strings"
	"testing"

	"htweb/internal/task"
)

// 一个打铃条目只能挂一个铃声。
//
// 这不是界面上的限制，是这套系统本来的模型：旧版三个页面
// （addbelltask.html:447 / modifybell.html:427 / modifybellall.html:2072）
// 那个 `<select name="setbellname">` 都没有 multiple，旧表
// `playbelloftask.bellid` 也是单个 int 列而不是关联表。
//
// 底层 mediaoftask 是能挂多行的（task 模型通用），所以光靠「界面只给一个下拉」
// 拦不住 —— 开发者接口走的是同一个 Service。校验必须在这一层，
// 这几条测试就是钉住这一点。
func TestItemMediaIsAtMostOne(t *testing.T) {
	one := []task.MediaRef{{MediaID: 7, Sort: 0}}
	two := []task.MediaRef{{MediaID: 7, Sort: 0}, {MediaID: 8, Sort: 1}}

	if err := checkItemMedia(nil, 1); err != nil {
		t.Errorf("不挂铃声应当允许（界面上清空那一格就是这种）：%v", err)
	}
	if err := checkItemMedia(one, 1); err != nil {
		t.Errorf("一个铃声应当允许：%v", err)
	}

	err := checkItemMedia(two, 3)
	if err == nil {
		t.Fatal("两个铃声必须被拒绝 —— 放过去的话，界面下次一打开就只剩第一个，而且没人会发现")
	}
	// 报错要说清是哪一条、以及规则是什么，不能只回一句「参数错误」
	if !strings.Contains(err.Error(), "第 3 个条目") {
		t.Errorf("错误里要带条目序号，实际：%q", err)
	}
	if !strings.Contains(err.Error(), "只能有一个铃声") {
		t.Errorf("错误里要说清规则，实际：%q", err)
	}
	// 必须能被 errors.Is 认出来：handler 靠它把这条判成 400。
	// 只靠文案的话会掉进关键词表的缝里 —— 那张表有「只能是」没有「只能有」，
	// 第一版就是这么变成一句「服务器内部错误」的。
	if !errors.Is(err, ErrTooManyItemMedia) {
		t.Error("要能被 errors.Is(ErrTooManyItemMedia) 认出来，否则界面上弹的是「服务器内部错误」")
	}
}

// 重复项与非法 ID 这两条原来就有，顺手钉住，免得重构时跟着丢掉。
func TestItemMediaRejectsBadIDs(t *testing.T) {
	if err := checkItemMedia([]task.MediaRef{{MediaID: 0}}, 1); err == nil {
		t.Error("媒体 ID 为 0 应当被拒绝")
	}
	// 重复项现在只有在上限 > 1 时才可能走到，但判据要留着：
	// maxItemMedia 哪天调大了，这条是唯一拦住「同一个铃声挂两次」的东西
	if err := checkItemMedia([]task.MediaRef{{MediaID: 5}, {MediaID: 5}}, 1); err == nil {
		t.Error("同一个媒体挂两次应当被拒绝")
	}
}
