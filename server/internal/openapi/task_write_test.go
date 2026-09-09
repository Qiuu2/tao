package openapi

import (
	"testing"

	"htweb/internal/task"
)

// 结束时刻的推算规则。
//
// # 为什么值得一个表驱动的测试
//
// 这几条规则每一条都是「猜错了不报错、只会播错」那一类：
//
//   - 改时长不动结束时刻 → 任务自相矛盾（时长 15 分钟、结束时刻停在 10 分钟那会儿）
//   - 挪开播时刻不动结束时刻 → **零长度任务**（10:05 开始、10:05 结束），
//     调用方明明只是想把它推迟一刻钟
//   - 只改音量却把结束时刻也重算了 → 悄悄改了调用方没提的东西
//
// 三条都不会抛错，接口照常回 200。所以钉在这里。
func TestDefaultEndTime(t *testing.T) {
	// 一条 09:50 开播、播 600 秒、10:00 结束的旧任务。
	byTime := &task.Detail{PlayTime: "09:50:00", EndTime: "10:00:00"}
	// 同一时段，但按遍数播 —— 没有确定的秒数。
	byLoop := &task.Detail{PlayTime: "09:50:00", EndTime: "10:00:00"}

	cases := []struct {
		name        string
		playTime    string
		lenTy       int
		length      int
		old         *task.Detail
		lenChanged  bool
		timeChanged bool
		want        string
	}{
		{
			name: "新建：按开播时刻 + 时长算",
			// 新建时没有旧值，两个 changed 都是真
			playTime: "09:50:00", lenTy: 1, length: 600, old: nil,
			lenChanged: true, timeChanged: true, want: "10:00:00",
		},
		{
			name:     "新建：按遍数播算不出秒数，退到 +1 小时",
			playTime: "09:50:00", lenTy: 2, length: 3, old: nil,
			lenChanged: true, timeChanged: true, want: "10:50:00",
		},
		{
			name:     "只改音量：结束时刻一动不动",
			playTime: "09:50:00", lenTy: 1, length: 600, old: byTime,
			lenChanged: false, timeChanged: false, want: "10:00:00",
		},
		{
			name:     "改时长 600→900：结束时刻跟着走",
			playTime: "09:50:00", lenTy: 1, length: 900, old: byTime,
			lenChanged: true, timeChanged: false, want: "10:05:00",
		},
		{
			// 这一条是真出过的问题：只传 {"playTime":"10:05"}，
			// 结束时刻留在 10:05，任务变成零长度。
			name:     "挪开播时刻：结束时刻跟着挪，不能留成零长度",
			playTime: "10:05:00", lenTy: 1, length: 600, old: byTime,
			lenChanged: false, timeChanged: true, want: "10:15:00",
		},
		{
			name:     "按遍数播 + 挪开播时刻：平移原来的窗口长度",
			playTime: "10:05:00", lenTy: 2, length: 3, old: byLoop,
			lenChanged: false, timeChanged: true, want: "10:15:00",
		},
		{
			name:     "按遍数播 + 只改遍数：算不准，平移零 = 保持窗口长度",
			playTime: "09:50:00", lenTy: 2, length: 5, old: byLoop,
			lenChanged: true, timeChanged: false, want: "10:00:00",
		},
		{
			// 老系统也是截断的（ok112 do.php 的 if($getendhour>=24)）。
			// 回绕成 00:10 会让「结束早于开始」，读的人只能靠猜。
			name:     "跨午夜：23:50 播 1200 秒 → 截到 23:59:59，不回绕",
			playTime: "23:50:00", lenTy: 1, length: 1200, old: nil,
			lenChanged: true, timeChanged: true, want: "23:59:59",
		},
		{
			name:     "整整 24 小时：也截到 23:59:59，不能变成开播时刻本身",
			playTime: "09:50:00", lenTy: 1, length: 86400, old: nil,
			lenChanged: true, timeChanged: true, want: "23:59:59",
		},
	}
	for _, c := range cases {
		t.Run(c.name, func(t *testing.T) {
			got := defaultEndTime(c.playTime, c.lenTy, c.length, c.old, c.lenChanged, c.timeChanged)
			if got != c.want {
				t.Errorf("defaultEndTime(%q, lenTy=%d, length=%d, lenChanged=%v, timeChanged=%v)"+
					" = %q，想要 %q", c.playTime, c.lenTy, c.length, c.lenChanged, c.timeChanged, got, c.want)
			}
		})
	}
}

// 结束时刻绝不能落在开播时刻上 —— 那是一条零长度的任务，到点什么都不播。
//
// 上面那张表已经逐条钉了取值；这一条是**性质**：不管怎么组合，
// 只要有个能算的依据，算出来的结束时刻就不该等于开播时刻。
func TestDefaultEndTimeNeverCollapses(t *testing.T) {
	old := &task.Detail{PlayTime: "09:50:00", EndTime: "10:00:00"}
	for _, play := range []string{"09:50:00", "10:05:00", "23:59:00"} {
		for _, lenTy := range []int{1, 2} {
			for _, length := range []int{1, 600, 86400} {
				got := defaultEndTime(play, lenTy, length, old, true, true)
				if got == play {
					t.Errorf("playTime=%s lenTy=%d length=%d 算出的结束时刻和开播时刻一样（%s）"+
						" —— 这是一条零长度的任务", play, lenTy, length, got)
				}
			}
		}
	}
}
