// Package offline 实现离线管理（业务域九，F-43 ~ F-47）。
//
// 手册开篇就说这一域是**旧代码质量最差的部分**，五个「幽灵表/幽灵字段」里占了三个，
// 多个功能在当前 schema 下**从未成功执行过**。本轮逐条核实过：
//
// # 已在现网证实的三件事
//
//  1. `terminaloftask` **确实没有 `offlineparam` 这一列**（现网只有
//     id / taskid / terminalid / workstate / groupid / area 六列）。
//     旧 `set_offline_task` 引用它并带 `or die()` —— 也就是说
//     「离线任务下发」这个功能**一次都没成功过**（D-153 / G-03）。
//     新版改用已经存在的 `offlinetaskofterminal.offlinestate` 表达同一语义，
//     **绝不新增字段**。
//
//  2. `offlinetaskofterminal.area` 的列默认值是字面量 `”'11111111”'` ——
//     带着三层单引号的脏默认值。所以插入时**必须显式写 `11111111`**（BR-209）。
//
//  3. `offlinemedia.id` 虽然是 auto_increment，但旧代码是
//     `INSERT INTO offlinemedia (id, ...) VALUES ('$row[id]', ...)` 显式写入 ——
//     后台服务据此把离线副本关联回 `media`。这是一条**未被文档化的强契约**（BR-201），
//     新版原样保持。同理 `offlinetask.taskid` 显式等于 `task.taskid`（BR-207）。
//
// # 12 态状态机
//
// Web 只写「下发意图」：1/2/4/5/11。
// 进行中与完成态（3/6/7/8/9/10/12）归后台服务写（BR-203）。
package offline

import (
	"database/sql"
	"fmt"
	"strings"
)

// State 是 offlinestate 的取值。注释直接抄自
// offlinetaskofterminal.offlinestate 的列注释，那是唯一权威。
type State int

const (
	StateNone       State = 0  // 非离线
	StateIdle       State = 1  // 空闲离线（Web 写）
	StateImmediate  State = 2  // 立即离线（Web 写）
	StateDone       State = 3  // 离线完成（后台写）
	StateDeleteIdle State = 4  // 空闲删除（Web 写）
	StateDeleteNow  State = 5  // 立即删除（Web 写）
	StateDoingIdle  State = 6  // 正在空闲离线（后台写）
	StateDoingNow   State = 7  // 正在立即离线（后台写）
	StateDeleted    State = 8  // 删除完成（后台写）
	StateReadyIdle  State = 9  // 准备空闲传输（后台写）
	StateReadyNow   State = 10 // 准备立即传输（后台写）
	StateStop       State = 11 // 停止传输（Web 写）
	StateStopped    State = 12 // 传输已停止（后台写）
)

// StateText 是 12 态的中文字典（BR-215，前后端共用同一份）。
// 旧版把它写成模板里一长串 if/elseif（D-160）。
var StateText = map[State]string{
	StateNone: "非离线", StateIdle: "空闲离线", StateImmediate: "立即离线",
	StateDone: "离线完成", StateDeleteIdle: "空闲删除", StateDeleteNow: "立即删除",
	StateDoingIdle: "正在空闲离线", StateDoingNow: "正在立即离线", StateDeleted: "删除完成",
	StateReadyIdle: "准备空闲传输", StateReadyNow: "准备立即传输",
	StateStop: "停止传输", StateStopped: "传输已停止",
}

func Text(s int) string {
	if t, ok := StateText[State(s)]; ok {
		return t
	}
	return fmt.Sprintf("未知(%d)", s)
}

// Mode 是接口层的下发意图，映射到 Web 允许写的那 5 个状态。
type Mode string

const (
	ModeIdle       Mode = "idle"       // → 1
	ModeImmediate  Mode = "immediate"  // → 2
	ModeDeleteIdle Mode = "deleteIdle" // → 4
	ModeDeleteNow  Mode = "deleteNow"  // → 5
	ModeStop       Mode = "stop"       // → 11
)

// ParseMode 把接口参数翻成状态值。
// 只接受这 5 个 —— 进行中/完成态由后台服务写，Web 不许伪造（BR-203）。
func ParseMode(m Mode) (State, error) {
	switch m {
	case ModeIdle:
		return StateIdle, nil
	case ModeImmediate:
		return StateImmediate, nil
	case ModeDeleteIdle:
		return StateDeleteIdle, nil
	case ModeDeleteNow:
		return StateDeleteNow, nil
	case ModeStop:
		return StateStop, nil
	default:
		return 0, fmt.Errorf("下发方式只能是 idle / immediate / deleteIdle / deleteNow / stop")
	}
}

// AreaAll 是 offlinetaskofterminal.area 要显式写入的值。
//
// 那一列的默认值是 `”'11111111”'` —— 建表时把引号也写进了默认值里，
// 于是依赖默认值插入会得到一串带引号的脏数据（BR-209）。
const AreaAll = "11111111"

// MediaTaskID 是「单纯的媒体下发」在 offlinemediaofterminal 里用的 taskid。
// 任务级下发才写真实 taskid（BR-202）。复合主键是 (mediaid, terminalid, taskid)，
// 所以这个 0 是主键的一部分，必须显式写，不能靠列默认值（D-150）。
const MediaTaskID = 0

var (
	ErrNotFound = fmt.Errorf("离线记录不存在")
)

type Service struct {
	db *sql.DB
}

func New(db *sql.DB) *Service { return &Service{db: db} }

func placeholders(ids []int64) (string, []interface{}) {
	if len(ids) == 0 {
		return "NULL", nil
	}
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		args[i] = id
	}
	return strings.TrimSuffix(strings.Repeat("?,", len(ids)), ","), args
}

func dedup(ids []int64) []int64 {
	seen := map[int64]bool{}
	out := make([]int64, 0, len(ids))
	for _, id := range ids {
		if id > 0 && !seen[id] {
			seen[id] = true
			out = append(out, id)
		}
	}
	return out
}
