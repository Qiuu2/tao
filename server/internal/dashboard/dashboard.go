// Package dashboard 提供看板首页的数据（设备概况 / 服务器性能 / 快捷入口 /
// 快捷任务 / 紧急广播 / 浏览任务）。
//
// # 「快捷入口、快捷任务」存在哪里
//
// 这两样都是**新增的界面状态**，旧库里没有对应的表。而 R1 红线禁止任何 DDL，
// 所以不能建表。挨个看了现有的空表：
//
//	shortcutkeymap    (id, type, mediaid)          只有两个 int，放不下路由与标签
//	shortcutkeytask   (id, keyid, mediaid, keyname) 字段够，但语义是「终端快捷键→媒体」
//	ctrltask          (taskid, taskname, cmd, value…) 字段最够用
//
// 后两张虽然现在是 0 行，但都是**后台 C 服务可能扫描的业务表** ——
// 往里塞界面状态，万一 C 服务把它们当真任务执行，就会在一所正在上课的学校里
// 放出一段没人预期的广播。这个风险不值得冒。
//
// 紧急广播那四个按钮**不在这份状态里** —— 它不存任何东西，
// 点一下就往 SDK 端口发一条命令，见 internal/sdkudp。
//
// 因此改存**一个 JSON 文件**（默认 <备份目录同级>/dashboard.json）：
// 不动数据库、不可能被 C 服务误读、重启后仍在、所有用户共享同一份。
// 代价是它不在数据库备份包里 —— 已在 backup 模块的说明里记了这一点。
package dashboard

import (
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"os"
	"path/filepath"
	"sync"
)

// Slot 是紧急广播的一个固定槽位。
//
// ChannelID / KeyID 是厂商 SDK 那条命令（SDKUrgentPlay_t）的两个参数，
// 取值是**设备固件里定死的**，不是这边能随便编的号 —— 见 internal/sdkudp。
type Slot struct {
	// Key 是存进 URL 与前端的标识，**不翻译**。
	Key string
	// Name 是界面上的字，产品词汇，跟着界面语言走。
	Name string
	// ChannelID SDK 的通道号 0~3。
	ChannelID int32
	// KeyID SDK 的按键号 1000~1003。
	KeyID int32
}

// EmergencySlots 是紧急广播的四个**固定**槽位，顺序与界面一致。
// 固定意味着：不能增删 —— 这四个号在设备固件里，加一个第五种也没东西会响应。
var EmergencySlots = []Slot{
	{Key: "quake", Name: "地震", ChannelID: 0, KeyID: 1000},
	{Key: "evacuate", Name: "疏散", ChannelID: 1, KeyID: 1001},
	{Key: "alert", Name: "警戒", ChannelID: 2, KeyID: 1002},
	{Key: "fire", Name: "消防", ChannelID: 3, KeyID: 1003},
}

// FindSlot 按 key 找槽位。找不到就是前端传了个不认识的 key。
func FindSlot(key string) (Slot, bool) {
	for _, s := range EmergencySlots {
		if s.Key == key {
			return s, true
		}
	}
	return Slot{}, false
}

// State 是落在 dashboard.json 里的全部界面状态。
// logf 统一给日志加包名前缀，出问题时一眼能看出是首页这块在说话。
func logf(format string, args ...any) {
	log.Printf("dashboard: "+format, args...)
}

type State struct {
	// Shortcuts 是顶部的快捷入口：点一下直接进对应页面。
	Shortcuts []Shortcut `json:"shortcuts"`
	// QuickTasks 是绑定到「快捷任务」面板的文件广播任务 ID。
	QuickTasks []int64 `json:"quickTasks"`

	// 这里曾经有一个 Emergency map[string]int64，存四个紧急槽位各绑了哪个任务。
	// 紧急广播改走 SDK 命令之后就不需要绑定了（见 EmergencySlots 上的说明），
	// 字段已删。老的 dashboard.json 里那个键会被 json 解码直接忽略，
	// 下一次落盘时消失，不用手工清理。
}

type Shortcut struct {
	Label string `json:"label"`
	Path  string `json:"path"`
	Icon  string `json:"icon"`
}

type Service struct {
	db   *sql.DB
	file string

	mu    sync.Mutex
	state State

	// perf 采样需要「上一次」才能算出速率，见 perf.go
	perfMu   sync.Mutex
	lastCPU  cpuSample
	lastNet  netSample
	hasCPU   bool
	hasNet   bool
	ifaceHit string
}

func New(db *sql.DB, file string) *Service {
	s := &Service{db: db, file: file}
	s.load()
	return s
}

// load 读状态文件。读不到就用空状态 —— 首页是只读展示为主，
// 状态文件缺失不该让整个页面挂掉。
func (s *Service) load() {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.state = State{Shortcuts: []Shortcut{}, QuickTasks: []int64{}}
	if s.file == "" {
		return
	}
	raw, err := os.ReadFile(s.file)
	if err != nil {
		return
	}
	var st State
	if err := json.Unmarshal(raw, &st); err != nil {
		return
	}
	if st.Shortcuts == nil {
		st.Shortcuts = []Shortcut{}
	}
	if st.QuickTasks == nil {
		st.QuickTasks = []int64{}
	}
	s.state = st
}

// save 落盘。先写 .part 再 rename，避免写一半被读到半个 JSON。
func (s *Service) save() error {
	if s.file == "" {
		return fmt.Errorf("未配置看板状态文件（dashboard.file）")
	}
	if err := os.MkdirAll(filepath.Dir(s.file), 0o755); err != nil {
		return fmt.Errorf("创建状态目录: %w", err)
	}
	raw, err := json.MarshalIndent(s.state, "", "  ")
	if err != nil {
		return err
	}
	tmp := s.file + ".part"
	if err := os.WriteFile(tmp, raw, 0o644); err != nil {
		return fmt.Errorf("写入看板状态: %w", err)
	}
	if err := os.Rename(tmp, s.file); err != nil {
		_ = os.Remove(tmp)
		return fmt.Errorf("提交看板状态: %w", err)
	}
	return nil
}

func (s *Service) snapshot() State {
	s.mu.Lock()
	defer s.mu.Unlock()
	return State{
		Shortcuts:  append([]Shortcut{}, s.state.Shortcuts...),
		QuickTasks: append([]int64{}, s.state.QuickTasks...),
	}
}
