package dashboard

import (
	"encoding/json"
	"os"
	"path/filepath"
	"testing"
)

// 绑定指向的任务被删掉之后，绑定本身也要清掉 —— 而且要落盘。
//
// # 为什么值得一个测试
//
// 用户的原话是「看板首页如果快捷任务中任务已删除，则绑定任务的数据也需要删除」。
// 留着一条指向空处的绑定，症状是「首页上有个按钮，点了没反应」——
// 紧急广播那四个槽位尤其要命：真出事的时候按下去才发现它绑的任务早没了。
//
// 这段逻辑挂在**读**的路径上，平时不声不响，坏了也没人会立刻发现。
func TestPruneDeletedBindings(t *testing.T) {
	file := filepath.Join(t.TempDir(), "dashboard.json")
	s := New(nil, file) // 这段逻辑不碰数据库，known 是调用方查好传进来的

	s.state.QuickTasks = []int64{101, 102, 103}
	s.state.Emergency = map[string]int64{"quake": 101, "fire": 200, "alert": 0}

	// 库里只剩 102 和 200
	known := map[int64]BoundTask{
		102: {TaskID: 102, TaskName: "还在"},
		200: {TaskID: 200, TaskName: "也还在"},
	}
	if !s.pruneDeleted(known) {
		t.Fatal("有失效绑定却报告「没动过」")
	}

	got := s.snapshot()
	if len(got.QuickTasks) != 1 || got.QuickTasks[0] != 102 {
		t.Errorf("快捷任务清理后是 %v，只该剩下 102", got.QuickTasks)
	}
	if _, ok := got.Emergency["quake"]; ok {
		t.Error("quake 槽位绑的 101 已被删除，槽位该空出来")
	}
	if got.Emergency["fire"] != 200 {
		t.Errorf("fire 槽位绑的 200 还在库里，不该被清掉，现在是 %v", got.Emergency["fire"])
	}

	// 必须落盘：只清内存的话，进程一重启失效绑定又回来了
	raw, err := os.ReadFile(file)
	if err != nil {
		t.Fatalf("清理后没写状态文件: %v", err)
	}
	var onDisk State
	if err := json.Unmarshal(raw, &onDisk); err != nil {
		t.Fatalf("写出去的状态文件不是合法 JSON: %v", err)
	}
	if len(onDisk.QuickTasks) != 1 || onDisk.QuickTasks[0] != 102 {
		t.Errorf("文件里的快捷任务是 %v，只该剩下 102", onDisk.QuickTasks)
	}
	if _, ok := onDisk.Emergency["quake"]; ok {
		t.Error("文件里 quake 槽位还绑着已删除的任务")
	}
}

// 没有失效绑定时不要瞎写文件。
//
// 每次进首页都重写一遍状态文件，是把一件「偶尔发生」的事变成了每次请求的
// 磁盘写入 —— 而且掩盖了「到底什么时候清理过」。
func TestPruneDeletedNoopWhenAllAlive(t *testing.T) {
	file := filepath.Join(t.TempDir(), "dashboard.json")
	s := New(nil, file)
	s.state.QuickTasks = []int64{101}
	s.state.Emergency = map[string]int64{"quake": 101}

	known := map[int64]BoundTask{101: {TaskID: 101, TaskName: "在的"}}
	if s.pruneDeleted(known) {
		t.Error("绑定都还有效，却报告清理过")
	}
	if _, err := os.Stat(file); !os.IsNotExist(err) {
		t.Error("没有东西要清理时不该写状态文件")
	}
}

// 槽位里的 0 表示「没绑」，不是「绑了一个不存在的任务」，不该被当成失效绑定。
func TestPruneDeletedKeepsUnboundSlots(t *testing.T) {
	s := New(nil, filepath.Join(t.TempDir(), "dashboard.json"))
	s.state.QuickTasks = []int64{}
	s.state.Emergency = map[string]int64{"quake": 0}
	if s.pruneDeleted(map[int64]BoundTask{}) {
		t.Error("只有一个空槽位，不该算作「清理过」—— 那会每次进首页都写一次盘")
	}
}
