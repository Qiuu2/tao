// Package notify 负责向后台 C 广播服务发送变更通知。
//
// # 为什么必须有这一环
//
// 后台服务把媒体清单缓存在内存里。Web 端改完数据库后如果不通知它，
// 它会继续按旧清单播放 —— 表现为「明明删掉的媒体还在播」「刚上传的媒体点不出来」。
// 旧系统对此有实现，但多个分支漏发（缺陷 D-49、D-152，以及 delallfiletask_msg 整条路径）。
//
// # 协议
//
// 纯文本 UDP 报文：type?state=X&id=Y
//
//	file?state=1&id=<folderid>   媒体新增
//	file?state=0&id=<folderid>   媒体/文件夹删除
//
// # 目标地址
//
// 旧 PHP 跑在容器内，发往主机名 audioserver；新版跑在宿主机上，
// 走 Docker 已发布到宿主的回环端口。端口取自 serverbaseparam.webport
// （现网为 8886），**不硬编码** —— 现场改了配置我们要跟着走。
package notify

import (
	"context"
	"database/sql"
	"fmt"
	"log"
	"net"
	"strconv"
	"sync/atomic"
	"time"
)

type Notifier struct {
	db      *sql.DB
	host    string
	fixed   int          // 配置里写死的端口；为 0 表示从数据库读
	cached  atomic.Int32 // 缓存从 serverbaseparam 读到的端口
	enabled bool
}

func New(db *sql.DB, host string, port int, enabled bool) *Notifier {
	return &Notifier{db: db, host: host, fixed: port, enabled: enabled}
}

// State 是通知里的 state 取值。
type State int

const (
	// StateDeleted 媒体或文件夹被删除，后台需要重新加载该目录
	StateDeleted State = 0
	// StateAdded 媒体新增
	StateAdded State = 1
)

// 终端类通知的 state 取值。
//
// 全部从旧 do.php 的实际调用点抄出来，不是从文档推的 ——
// 文档里说「逐个终端发」，但录音、线路检测、密码这几条旧代码
// 其实是把整串 ID 一次发出去的，粒度必须原样保留。
const (
	TermStop      State = 0  // 停止终端
	TermStart     State = 1  // 启动终端
	TermDeleted   State = 2  // 终端已删除
	TermSpeechOn  State = 3  // 对讲 / 发言 开（带 &speech=true）
	TermSpeechOff State = 4  // 对讲 / 发言 关（带 &speech=false）
	TermVolume    State = 5  // 音量（带 &volume=）
	TermPassword  State = 7  // 下发终端密码（带 &pwd=，ID 用花括号包裹）
	TermRecordOn  State = 13 // 开始录音
	TermRecordOff State = 14 // 停止录音
	TermCircuit   State = 27 // 线路检测（ID 用花括号包裹）
	TermSyncTime  State = 30 // 同步时间
	TermDelTask   State = 32 // 删除任务终端
)

// 任务类通知的 state 取值。
//
// 同样是从旧 do.php 的实际调用点抄出来的，**不是从手册推的**。
// 手册说停止写 state=0，实际旧代码写的是 2；手册没提暂停/恢复的 22/23，
// 也没提新增/修改要带 &volume=。这些差异都会让后台服务收不到或误解通知。
//
// 报文分三种形态，混用会让后台服务解析失败：
//
//	task?state=3&id=X               启动（send_socket_generate_general）
//	task?state=2&id=X&type=2        停止 / 暂停 / 恢复 / 删除（general2，带 type）
//	task?state=4&id=X&volume=V      新增 / 修改（send_socket_task_volume）
const (
	TaskStop    State = 2  // 停止（&type=2）
	TaskStart   State = 3  // 启动（无 type）
	TaskAdded   State = 4  // 新增（&volume=）
	TaskUpdated State = 5  // 修改（&volume=）
	TaskDeleted State = 6  // 删除（&type=2）
	TaskPause   State = 22 // 暂停（&type=2）
	TaskResume  State = 23 // 恢复（&type=2）
	// TaskOffline 离线数据有变更，报文固定是 task?state=15&id=0 ——
	// id 恒为 0，因为它通知的是「离线清单整体变了」，不针对某个任务。
	TaskOffline State = 15
)

// OfflineChanged 发送 task?state=15&id=0。
//
// 旧版只在 flag=5（立即删除）和 flag=2（立即离线）时发（D-152），
// flag=1（空闲离线）不发 —— 后台就不知道有新的空闲传输任务排进来了。
// 新版所有离线状态变更都发。
func (n *Notifier) OfflineChanged(ctx context.Context) {
	n.send(ctx, fmt.Sprintf("task?state=%d&id=0", TaskOffline))
}

// taskTypeArg 是 general2 报文里的 &type= 取值。
// 文件广播固定为 2，与 task.tasktype 无关 —— 旧代码里 TTS 任务用的是 17。
const taskTypeArg = 2

// bellTypeArg 是作息方案删除通知里的 &type= 取值。
// 旧 belldel_msg 调的是 send_socket_generate_general2("task", 6, id, **1**)，
// 与文件广播的 2 不是一个值，混用后台服务会按错误的类型去卸载任务。
const bellTypeArg = 1

// 作息方案（打铃）通知的 state 取值。
//
//	project?state=1&name=<方案名>   启用（bellstart_msg）
//	project?state=2&name=<方案名>   停止（bellstop_msg）
//
// ⚠ 参数名是 **name** 不是 info：手册 §1.3 写的是 `&info=`，
// 但 send_socket_schedules 实际拼的是 `&name=`
// （features_wrapper_class.php:359）。写错了后台服务取不到方案名。
const (
	PlanEnabled  State = 1
	PlanDisabled State = 2
)

// PlanChanged 发送 project?state=<1|2>&name=<方案名>。
//
// 标识是**方案名字符串**而不是 ID —— 这是旧协议的既定形态，不能改（BR-233）。
// 代价是方案名里出现 ? & = 会把报文切断，所以写入侧对方案名做了字符校验。
func (n *Notifier) PlanChanged(ctx context.Context, state State, planName string) {
	n.send(ctx, fmt.Sprintf("project?state=%d&name=%s", state, planName))
}

// BellTasksDeleted 发送 task?state=6&id=Y&type=1，每个被删掉的条目一条。
//
// 旧 belldel_msg 只对**用户勾选的那几个 taskid** 发通知
// （`explode(",", $_GET['id'])`），其余被连带删掉的条目一条都不发 ——
// 后台服务会继续持有那些已经不存在的任务。这里按实际删掉的每个 id 发。
func (n *Notifier) BellTasksDeleted(ctx context.Context, taskIDs []int64) {
	for _, id := range taskIDs {
		n.send(ctx, fmt.Sprintf("task?state=%d&id=%d&type=%d", TaskDeleted, id, bellTypeArg))
	}
}

// TaskChanged 发送 task?state=X&id=Y&type=2，逐个任务一条。
// 停止 / 暂停 / 恢复 / 删除走这条。
func (n *Notifier) TaskChanged(ctx context.Context, state State, taskIDs []int64) {
	for _, id := range taskIDs {
		n.send(ctx, fmt.Sprintf("task?state=%d&id=%d&type=%d", state, id, taskTypeArg))
	}
}

// TaskStarted 发送 task?state=3&id=Y。
// 启动用的是不带 type 的老式报文，不能和上面那条混用。
func (n *Notifier) TaskStarted(ctx context.Context, taskIDs []int64) {
	for _, id := range taskIDs {
		n.send(ctx, fmt.Sprintf("task?state=%d&id=%d", TaskStart, id))
	}
}

// TaskSaved 发送 task?state=<4|5>&id=Y&volume=V，用于新增与修改。
func (n *Notifier) TaskSaved(ctx context.Context, state State, taskID int64, volume int) {
	n.send(ctx, fmt.Sprintf("task?state=%d&id=%d&volume=%d", state, taskID, volume))
}

// ServerReboot 发送 server?state=1。
//
// # ⚠⚠ 这条报文会让整台服务器立刻重启，不是「重新加载配置」
//
// 旧版这个封装叫 send_socket_restart，restore_backup_file.php 恢复完成后就发它。
// 我一开始按字面理解成「让后台服务重新加载」，在恢复流程末尾自动调了一次，
// 现网实测的结果是**整机重启**，日志里前后差 1 秒：
//
//	16:09:58 notify -> 127.0.0.1:8886 : server?state=1
//	16:09:59 systemd[1]: Stopping Create final runtime dir for shutdown pivot root...
//
// 函数名里的 restart 说的是**这台机器**。
//
// 因此：
//   - 恢复流程**不再自动调用**它，改成在响应里告诉用户「后台服务需要重启才能生效」，
//     由人来决定什么时候重启（这是一台随时可能要打铃的广播服务器）
//   - 保留这个函数，但名字和注释必须写清楚它的真实杀伤力，
//     免得下一个人再按名字猜一遍
func (n *Notifier) ServerReboot(ctx context.Context) {
	n.send(ctx, "server?state=1")
}

// TerminalChanged 发送 terminal?state=X&id=<id>，每个终端一条。
func (n *Notifier) TerminalChanged(ctx context.Context, state State, terminalID int64) {
	n.send(ctx, fmt.Sprintf("terminal?state=%d&id=%d", state, terminalID))
}

// TerminalChangedBatch 逐个终端发送。
// 启动 / 停止 / 同步时间 走这条：旧代码在 foreach 里逐个发。
func (n *Notifier) TerminalChangedBatch(ctx context.Context, state State, ids []int64) {
	for _, id := range ids {
		n.TerminalChanged(ctx, state, id)
	}
}

// TerminalSpeech 对讲 / 发言开关，报文尾部带 &speech=true|false。
func (n *Notifier) TerminalSpeech(ctx context.Context, on bool, ids []int64) {
	state, flag := TermSpeechOff, "false"
	if on {
		state, flag = TermSpeechOn, "true"
	}
	for _, id := range ids {
		n.send(ctx, fmt.Sprintf("terminal?state=%d&id=%d&speech=%s", state, id, flag))
	}
}

// TerminalVolume 音量下发。
func (n *Notifier) TerminalVolume(ctx context.Context, ids []int64, volume int) {
	for _, id := range ids {
		n.send(ctx, fmt.Sprintf("terminal?state=%d&id=%d&volume=%d", TermVolume, id, volume))
	}
}

// TerminalIDList 发送 ID 逗号串（不加花括号）的通知。
// 录音开关走这条：旧代码里的 foreach 被注释掉了，$getid 仍是整串。
func (n *Notifier) TerminalIDList(ctx context.Context, state State, ids []int64) {
	if len(ids) == 0 {
		return
	}
	n.send(ctx, fmt.Sprintf("terminal?state=%d&id=%s", state, joinIDs(ids)))
}

// TerminalBraced 发送 ID 被花括号包裹的通知：id={1,2,3}。
// 线路检测（state=27）用这个格式，与逗号串那种不是一回事，不能混用。
func (n *Notifier) TerminalBraced(ctx context.Context, state State, ids []int64) {
	if len(ids) == 0 {
		return
	}
	n.send(ctx, fmt.Sprintf("terminal?state=%d&id={%s}", state, joinIDs(ids)))
}

// TerminalPassword 下发终端密码：terminal?state=7&id={1,2}&pwd=xxx
func (n *Notifier) TerminalPassword(ctx context.Context, ids []int64, pwd string) {
	if len(ids) == 0 {
		return
	}
	n.send(ctx, fmt.Sprintf("terminal?state=%d&id={%s}&pwd=%s", TermPassword, joinIDs(ids), pwd))
}

func joinIDs(ids []int64) string {
	b := make([]byte, 0, len(ids)*4)
	for i, id := range ids {
		if i > 0 {
			b = append(b, ',')
		}
		b = strconv.AppendInt(b, id, 10)
	}
	return string(b)
}

// MediaChanged 通知某个文件夹下的媒体发生了变化。
func (n *Notifier) MediaChanged(ctx context.Context, state State, folderID int64) {
	n.send(ctx, fmt.Sprintf("file?state=%d&id=%d", state, folderID))
}

// MediaChangedBatch 对一批文件夹逐个发送通知。
// 旧系统按 folderid 分组后逐个发，这里保持同样的粒度。
func (n *Notifier) MediaChangedBatch(ctx context.Context, state State, folderIDs []int64) {
	seen := make(map[int64]bool, len(folderIDs))
	for _, id := range folderIDs {
		if id == 0 || seen[id] {
			continue
		}
		seen[id] = true
		n.MediaChanged(ctx, state, id)
	}
}

// port 解析通知端口。优先用配置里的固定值，否则读 serverbaseparam.webport。
//
// 注意 webport 这个列名容易误导：它不是 Apache 端口，而是后台服务的 UDP 通知端口。
// 实测现网 TCP 8886 无监听、UDP 8886 有监听，Apache 实际在 80/81/99。
func (n *Notifier) port(ctx context.Context) int {
	if n.fixed > 0 {
		return n.fixed
	}
	if p := n.cached.Load(); p > 0 {
		return int(p)
	}
	var p int
	err := n.db.QueryRowContext(ctx, `SELECT webport FROM serverbaseparam LIMIT 1`).Scan(&p)
	if err != nil || p <= 0 {
		log.Printf("notify: 读取 serverbaseparam.webport 失败(%v)，退回默认 8886", err)
		p = 8886
	}
	n.cached.Store(int32(p))
	return p
}

func (n *Notifier) send(ctx context.Context, payload string) {
	if !n.enabled {
		log.Printf("notify(已禁用，仅记录): %s", payload)
		return
	}
	addr := net.JoinHostPort(n.host, strconv.Itoa(n.port(ctx)))

	// UDP 是无连接的，发送失败通常只反映本地问题（如端口不可达）。
	// 这里不阻塞业务：通知失败只记日志，不让整个操作回滚 ——
	// 数据库已经改完了，回滚反而更糟。运维可据日志排查。
	conn, err := net.DialTimeout("udp", addr, 2*time.Second)
	if err != nil {
		log.Printf("notify: 连接 %s 失败: %v (payload=%s)", addr, err, payload)
		return
	}
	defer conn.Close()

	_ = conn.SetWriteDeadline(time.Now().Add(2 * time.Second))
	if _, err := conn.Write([]byte(payload)); err != nil {
		log.Printf("notify: 发送到 %s 失败: %v (payload=%s)", addr, err, payload)
		return
	}
	log.Printf("notify -> %s : %s", addr, payload)
}
