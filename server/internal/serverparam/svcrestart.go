package serverparam

import (
	"context"
	"os"
	"os/exec"
	"strings"
	"time"
)

// 改完地址或主备角色之后，把两个吃这些配置的服务重启一遍。
//
// # 为什么要有这一步
//
//	heartbeat          启动时读 /etc/ha.d/ha.cf 与 haresources。刚被改掉的
//	                   node 名、ucast 对端、资源行上的虚拟地址，它都要重启才认。
//	a9000_audioserver  广播引擎，启动时读 serverbaseparam 那一行。
//
// 旧版这两件都**没做**：`//$command = "sudo service heartbeat stop";` 在
// do.php 里被注释掉了（24805 / 24926 / 25187 三处），页面只弹一句
// 「The_system_is_restarting」就跳回登录页，实际得运维自己上机器重启。
// 它唯一真发出去的是 `send_socket_restart("server",1)`，也就是 `server?state=1`
// —— 那条报文是**整机重启**，不是重启这两个服务（notify.go 里有实测日志）。
//
// # ⚠ heartbeat 必须「先停 → 再写文件 → 再起」，不能写完再 restart
//
// heartbeat 放下资源时，照的是**当前磁盘上那份 haresources**。
// 先把 haresources 从
//
//	ha51 192.168.2.159/24/eth0 ha-post
//
// 改成 …158 再 `systemctl restart heartbeat`，停的那一半会去释放 `192.168.2.158`
// —— 而那个地址根本没起来过，于是什么都没做、返回成功；
// 真正挂在 eth0:0 上的 192.168.2.159 **没有任何人去摘**，就这么留着了。
// 起的那一半再把 .158 拿起来，最后是两个地址都在，或者（像现网实测那样）
// 看上去「改了地址却一点没变」。
//
// 所以顺序是：
//
//	stopHeartbeat()    ← 这时磁盘上还是旧的 haresources，旧虚拟地址被正确摘下
//	写 haresources / ha.cf / hosts / …
//	startHeartbeat()   ← 读新的 haresources，拿起新虚拟地址
//	docker restart a9000_audioserver
//
// 这也是为什么 Save 那边把 syncHAFiles 拆成了 prepareHAFiles / applyHATargets：
// 探要在停之前做完（探不过就根本不停），写要在停之后做。
//
// # 三条铁律
//
//  1. **没装就不算失败**。这台机器没有 heartbeat、或者没有那个容器，
//     报 missing 跳过 —— 与文件同步那边同一套口径。
//  2. **重启不了就说清楚**，不猜、不假装成功。缺免密 sudo 时把
//     「跑一次 deploy/install-sudoers.sh」这句原样回给界面。
//  3. **missing 也要报给界面**（与文件同步那边不同）。
//     「这台机器上没有 heartbeat，所以我没重启它」正是运维要知道的一句 ——
//     否则他改完地址盯着 ifconfig，只会看到旧地址，完全不知道为什么。
//
// # 找不到 systemd 单元时退回 init.d
//
// 这套 a9000 是拿 ubuntu-autoinstall 装出来的（ha-post.sh 的文件头还留着
// `a9000-autoinstall/ubuntu-autoinstall-generator` 这个路径），heartbeat 很可能
// 是 SysV 那一套，没有 heartbeat.service。所以探不到单元就试 /etc/init.d/heartbeat。
//
// ⚠ 重启 heartbeat 会触发一次资源交接：主机上那个虚拟地址会短暂落下、
//   甚至切到备机上去。正在播的任务会断。界面上要把这一条说明白。
//
// ⚠ 起回来之后虚拟地址**不是立刻**就在：heartbeat 要等 ha.cf 里的 initdead
//   （这套配的是 30 秒）才接管资源。界面上要说清楚，否则运维起来就 ifconfig，
//   看见旧地址还在，以为又没生效。

// ServiceRestart 是一个服务的处理结果，逐条回给界面。
// 复用 FileSyncStatus：updated = 动过了，missing = 这台机器上没有，failed = 没能动。
type ServiceRestart struct {
	// Name 是服务名，给人核对用（heartbeat / a9000_audioserver）。
	Name   string         `json:"name"`
	What   string         `json:"what"`
	Status FileSyncStatus `json:"status"`
	Detail string         `json:"detail,omitempty"`
}

// 单个动作最多等这么久。docker 默认给容器 10 秒优雅退出再 SIGKILL，
// heartbeat 停起来也差不多 —— 45 秒足够，同时保证保存请求不会挂在这里不动。
const svcActTimeout = 45 * time.Second

const audioContainer = "a9000_audioserver"

var (
	systemctlPaths = []string{"/usr/bin/systemctl", "/bin/systemctl"}
	dockerPaths    = []string{"/usr/bin/docker", "/bin/docker", "/usr/local/bin/docker"}
	// SysV 那一套的退路，见文件头。
	initdHeartbeat = "/etc/init.d/heartbeat"
)

func firstExecutable(paths []string) string {
	for _, p := range paths {
		if _, err := exec.LookPath(p); err == nil {
			return p
		}
	}
	return ""
}

// hbRunner 是「怎么驱动 heartbeat」：要么 systemctl，要么 /etc/init.d/heartbeat。
// cmd 为空表示这台机器上根本没有 heartbeat。
type hbRunner struct {
	cmd  string
	args func(action string) []string
	how  string // 给人看的一句，说明用的是哪条路
}

// findHeartbeat 探这台机器上怎么驱动 heartbeat。
//
// 两步都**不带 sudo** —— `systemctl cat` 和 `test -x` 普通账号就能做。
// 先探再动，是为了把「没装」和「装了但动不了」分开：前者不需要任何人做什么，
// 后者是库里已经是新配置、跑着的还是旧的，必须拦住人。
func findHeartbeat(ctx context.Context) hbRunner {
	if cmd := firstExecutable(systemctlPaths); cmd != "" {
		c, cancel := context.WithTimeout(ctx, 5*time.Second)
		defer cancel()
		if err := exec.CommandContext(c, cmd, "cat", "heartbeat.service").Run(); err == nil {
			return hbRunner{cmd: cmd,
				args: func(a string) []string { return []string{a, "heartbeat"} },
				how:  "systemctl " + "<动作> heartbeat"}
		}
	}
	if st, err := os.Stat(initdHeartbeat); err == nil && !st.IsDir() && st.Mode().Perm()&0o111 != 0 {
		return hbRunner{cmd: initdHeartbeat,
			args: func(a string) []string { return []string{a} },
			how:  initdHeartbeat + " <动作>"}
	}
	return hbRunner{}
}

// run 执行一个动作（stop / start），回一个可以直接展示的结果。
func (h hbRunner) run(ctx context.Context, action, what string) ServiceRestart {
	out := ServiceRestart{Name: "heartbeat", What: what}
	if h.cmd == "" {
		out.Status = SyncMissing
		out.Detail = "这台机器上既没有 heartbeat.service，也没有 " + initdHeartbeat +
			" —— 虚拟地址不会自己换，需要手工处理"
		return out
	}
	c, cancel := context.WithTimeout(ctx, svcActTimeout)
	defer cancel()
	// ⚠ 分参数传，不拼 shell —— 与 nmcli 那边同一条规矩。
	raw, err := exec.CommandContext(c, "sudo", append([]string{"-n", h.cmd}, h.args(action)...)...).CombinedOutput()
	if err != nil {
		out.Status = SyncFailed
		out.Detail = svcFailDetail(err, raw,
			"服务账号没有免密执行 "+h.how+" 的权限。"+
				"在服务器上跑一次 deploy/install-sudoers.sh 即可开通")
		return out
	}
	out.Status = SyncUpdated
	return out
}

// StopHeartbeat 在改 haresources / ha.cf **之前**把 heartbeat 停掉，
// 让它照旧配置把旧的虚拟地址摘干净。理由见文件头。
func (s *Service) StopHeartbeat(ctx context.Context) ServiceRestart {
	r := findHeartbeat(ctx).run(ctx, "stop", "先停下来，让它照**旧**的 haresources 摘掉旧虚拟地址")
	if r.Status == SyncUpdated {
		r.Detail = "已停止（旧虚拟地址已由它自己摘下）"
	}
	return r
}

// StartHeartbeat 在文件都写完之后把它起回来，这次读到的是新的 haresources。
func (s *Service) StartHeartbeat(ctx context.Context) ServiceRestart {
	r := findHeartbeat(ctx).run(ctx, "start", "读新的 ha.cf / haresources，接管新的虚拟地址")
	if r.Status == SyncUpdated {
		r.Detail = "已启动。⚠ 虚拟地址要等 ha.cf 里的 initdead（本套是 30 秒）之后才会出现在 eth0:0 上，" +
			"这段时间 ifconfig 看不到它是正常的"
	}
	return r
}

// RestartAudioserver 重启广播引擎那个容器。
//
// 它是 docker 容器不是 systemd 单元（版本设置页那个 update_audioserver.sh
// 就是 `docker stop/rm/run a9000_audioserver`，见 version.go）。
//
// 这边不先探「容器在不在」：探要跑 `docker ps`，而现网 tw 不在 docker 组里，
// 探本身也得 sudo，等于多要一条提权。直接重启，按 docker 回的话分辨是
// 「没有这个容器」还是「没权限」。
func (s *Service) RestartAudioserver(ctx context.Context) ServiceRestart {
	out := ServiceRestart{Name: audioContainer, What: "重新读取 serverbaseparam（地址 / 端口）"}

	cmd := firstExecutable(dockerPaths)
	if cmd == "" {
		out.Status = SyncMissing
		out.Detail = "这台机器上没有 docker"
		return out
	}
	c, cancel := context.WithTimeout(ctx, svcActTimeout)
	defer cancel()
	raw, err := exec.CommandContext(c, "sudo", "-n", cmd, "restart", audioContainer).CombinedOutput()
	if err != nil {
		if isNoSuchContainer(raw) {
			out.Status = SyncMissing
			out.Detail = "这台机器上没有 " + audioContainer + " 这个容器"
			return out
		}
		out.Status = SyncFailed
		out.Detail = svcFailDetail(err, raw,
			"服务账号没有免密执行 "+cmd+" restart "+audioContainer+" 的权限。"+
				"在服务器上跑一次 deploy/install-sudoers.sh 即可开通")
		return out
	}
	out.Status = SyncUpdated
	out.Detail = "已重启"
	return out
}

func isNoSuchContainer(raw []byte) bool {
	s := strings.ToLower(string(raw))
	return strings.Contains(s, "no such container") || strings.Contains(s, "is not known")
}

// svcFailDetail 把命令的真实输出回给界面，缺 sudo 时换成能照着做的那一句。
//
// sudo -n 在没有规则时回的是 "sudo: a password is required"，
// 原样摆给运维等于什么都没说 —— 要告诉他去跑哪个脚本。
func svcFailDetail(err error, raw []byte, sudoHint string) string {
	msg := strings.TrimSpace(string(raw))
	low := strings.ToLower(msg)
	if strings.Contains(low, "password is required") || strings.Contains(low, "may not run") ||
		strings.Contains(low, "sorry, user") {
		return sudoHint
	}
	if msg == "" {
		return err.Error()
	}
	return msg
}
