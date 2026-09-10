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
// 而且没有任何报错，谁也不会发现。
//
// 这段逻辑挂在**读**的路径上，平时不声不响，坏了也没人会立刻发现。
func TestPruneDeletedBindings(t *testing.T) {
	file := filepath.Join(t.TempDir(), "dashboard.json")
	s := New(nil, file) // 这段逻辑不碰数据库，known 是调用方查好传进来的

	s.state.QuickTasks = []int64{101, 102, 103}

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
}

// 没有失效绑定时不要瞎写文件。
//
// 每次进首页都重写一遍状态文件，是把一件「偶尔发生」的事变成了每次请求的
// 磁盘写入 —— 而且掩盖了「到底什么时候清理过」。
func TestPruneDeletedNoopWhenAllAlive(t *testing.T) {
	file := filepath.Join(t.TempDir(), "dashboard.json")
	s := New(nil, file)
	s.state.QuickTasks = []int64{101}

	known := map[int64]BoundTask{101: {TaskID: 101, TaskName: "在的"}}
	if s.pruneDeleted(known) {
		t.Error("绑定都还有效，却报告清理过")
	}
	if _, err := os.Stat(file); !os.IsNotExist(err) {
		t.Error("没有东西要清理时不该写状态文件")
	}
}

// 四个槽位的通道号与按键号是**设备固件里定死的**，改一个数字就播错内容 ——
// 真出事的时候按「疏散」放出来的是消防警报。这里钉死。
func TestEmergencySlotIDs(t *testing.T) {
	want := []Slot{
		{Key: "quake", Name: "地震", ChannelID: 0, KeyID: 1000},
		{Key: "evacuate", Name: "疏散", ChannelID: 1, KeyID: 1001},
		{Key: "alert", Name: "警戒", ChannelID: 2, KeyID: 1002},
		{Key: "fire", Name: "消防", ChannelID: 3, KeyID: 1003},
	}
	if len(EmergencySlots) != len(want) {
		t.Fatalf("紧急广播固定四个槽位，现在有 %d 个", len(EmergencySlots))
	}
	for i, w := range want {
		if EmergencySlots[i] != w {
			t.Errorf("第 %d 个槽位是 %+v，应为 %+v", i, EmergencySlots[i], w)
		}
		got, ok := FindSlot(w.Key)
		if !ok || got != w {
			t.Errorf("FindSlot(%q) = %+v, %v", w.Key, got, ok)
		}
	}
	if _, ok := FindSlot("nope"); ok {
		t.Error("不认识的 key 该找不到，不能悄悄回一个零值槽位（那会去播通道 0）")
	}
}
