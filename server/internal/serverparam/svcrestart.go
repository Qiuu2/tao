package serverparam

import (
	"context"
	"os/exec"
	"strings"
	"sync"
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
// # 两条铁律
//
//  1. **没装就不算失败**。这台机器没有 heartbeat 单元、或者没有那个容器，
//     报 missing 跳过 —— 与文件同步那边同一套口径。
//  2. **重启不了就说清楚**，不猜、不假装成功。缺免密 sudo 时把
//     「跑一次 deploy/install-sudoers.sh」这句原样回给界面。
//
// ⚠ 重启 heartbeat 会触发一次资源交接：主机上那个虚拟地址会短暂落下、
//   甚至切到备机上去。正在播的任务会断。界面上要把这一条说明白。

// ServiceRestart 是一个服务的重启结果，逐条回给界面。
// 复用 FileSyncStatus：updated = 重启了，missing = 这台机器上没有，failed = 没能重启。
type ServiceRestart struct {
	// Name 是服务名，给人核对用（heartbeat / a9000_audioserver）。
	Name   string         `json:"name"`
	What   string         `json:"what"`
	Status FileSyncStatus `json:"status"`
	Detail string         `json:"detail,omitempty"`
}

// 单个服务最多等这么久。docker 默认给容器 10 秒优雅退出再 SIGKILL，
// heartbeat 停起来也差不多 —— 45 秒足够，同时保证保存请求不会挂在这里不动。
const svcRestartTimeout = 45 * time.Second

const audioContainer = "a9000_audioserver"

var (
	systemctlPaths = []string{"/usr/bin/systemctl", "/bin/systemctl"}
	dockerPaths    = []string{"/usr/bin/docker", "/bin/docker", "/usr/local/bin/docker"}
)

func firstExecutable(paths []string) string {
	for _, p := range paths {
		if _, err := exec.LookPath(p); err == nil {
			return p
		}
	}
	return ""
}

// restartServices 并发重启这两个服务。
//
// 并发是因为它们互不相干，而串行的话最坏要等两个超时 —— 保存请求就停在那儿了。
func (s *Service) restartServices(ctx context.Context) []ServiceRestart {
	out := make([]ServiceRestart, 2)
	var wg sync.WaitGroup
	wg.Add(2)
	go func() { defer wg.Done(); out[0] = restartHeartbeat(ctx) }()
	go func() { defer wg.Done(); out[1] = restartAudioserver(ctx) }()
	wg.Wait()
	return out
}

// restartHeartbeat 重启 heartbeat。
//
// 先不带 sudo 探一次这台机器上到底有没有这个单元（`systemctl cat` 普通账号就能跑）——
// 没装的机器直接报 missing，比让它去 sudo 一把再拿一条看不懂的错好。
func restartHeartbeat(ctx context.Context) ServiceRestart {
	out := ServiceRestart{Name: "heartbeat", What: "重新读取 ha.cf / haresources（主备角色与虚拟地址）"}

	cmd := firstExecutable(systemctlPaths)
	if cmd == "" {
		out.Status = SyncMissing
		out.Detail = "这台机器上没有 systemctl"
		return out
	}
	c, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()
	if err := exec.CommandContext(c, cmd, "cat", "heartbeat.service").Run(); err != nil {
		out.Status = SyncMissing
		out.Detail = "这台机器上没有 heartbeat.service（没装 heartbeat？）"
		return out
	}

	c2, cancel2 := context.WithTimeout(ctx, svcRestartTimeout)
	defer cancel2()
	// ⚠ 分参数传，不拼 shell —— 与 nmcli 那边同一条规矩。
	raw, err := exec.CommandContext(c2, "sudo", "-n", cmd, "restart", "heartbeat").CombinedOutput()
	if err != nil {
		out.Status = SyncFailed
		out.Detail = svcFailDetail(err, raw,
			"服务账号没有免密执行 "+cmd+" restart heartbeat 的权限。"+
				"在服务器上跑一次 deploy/install-sudoers.sh 即可开通")
		return out
	}
	out.Status = SyncUpdated
	out.Detail = "已重启"
	return out
}

// restartAudioserver 重启广播引擎那个容器。
//
// 它是 docker 容器不是 systemd 单元（版本设置页那个 update_audioserver.sh
// 就是 `docker stop/rm/run a9000_audioserver`，见 version.go）。
//
// 这边不先探「容器在不在」：探要跑 `docker ps`，而现网 tw 不在 docker 组里，
// 探本身也得 sudo，等于多要一条提权。直接重启，按 docker 回的话分辨是
// 「没有这个容器」还是「没权限」。
func restartAudioserver(ctx context.Context) ServiceRestart {
	out := ServiceRestart{Name: audioContainer, What: "重新读取 serverbaseparam（地址 / 端口）"}

	cmd := firstExecutable(dockerPaths)
	if cmd == "" {
		out.Status = SyncMissing
		out.Detail = "这台机器上没有 docker"
		return out
	}
	c, cancel := context.WithTimeout(ctx, svcRestartTimeout)
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
