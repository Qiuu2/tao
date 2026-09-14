package notify

import "testing"

// 停止 / 暂停 / 恢复的报文要带**任务自己的 tasktype**，不是写死的 2。
//
// 旧版三处停止路径都是先 `select tasktype from task where taskid=...`，
// 再 send_socket_generate_general2("task", state, id, tasktype)；
// 其中 tasktype = 17（文字语音）还要把 state 从 2 换成 13
// （do.php:18966 / 21728 / 22108，最后一处就是看板那个「停止」）。
//
// 写死 2 的后果是「库里状态变了、喇叭没反应」—— 看板上点了停止，
// 终端功放还在开着、文字语音还在念。
func TestTypedPayload(t *testing.T) {
	cases := []struct {
		name  string
		state State
		ref   TaskRef
		want  string
	}{
		{"文件广播停止", TaskStop, TaskRef{ID: 7, TaskType: 2}, "task?state=2&id=7&type=2"},
		{"终端功放停止：type 要是 5", TaskStop, TaskRef{ID: 8, TaskType: 5}, "task?state=2&id=8&type=5"},
		{"采播停止", TaskStop, TaskRef{ID: 9, TaskType: 3}, "task?state=2&id=9&type=3"},
		{"led播放停止", TaskStop, TaskRef{ID: 10, TaskType: 30}, "task?state=2&id=10&type=30"},
		{"⚠ 文字语音 17 停止：state 换成 13", TaskStop, TaskRef{ID: 11, TaskType: 17}, "task?state=13&id=11&type=17"},
		{"文字语音的另一个取值 19 不换", TaskStop, TaskRef{ID: 12, TaskType: 19}, "task?state=2&id=12&type=19"},
		{"暂停不换 state，17 也不换", TaskPause, TaskRef{ID: 13, TaskType: 17}, "task?state=22&id=13&type=17"},
		{"恢复同理", TaskResume, TaskRef{ID: 14, TaskType: 17}, "task?state=23&id=14&type=17"},
	}
	for _, c := range cases {
		if got := typedPayload(c.state, c.ref); got != c.want {
			t.Errorf("%s：typedPayload(%d,%+v) = %q，想要 %q", c.name, c.state, c.ref, got, c.want)
		}
	}
}
