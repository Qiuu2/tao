package dashboard

import (
	"bufio"
	"fmt"
	"net"
	"os"
	"strconv"
	"strings"
	"syscall"
	"time"
)

// 服务器性能：CPU / 内存 / 磁盘 / 网络流量。
//
// 全部直接读 /proc 与 statfs，不引入任何采集库 ——
// 新版跑在宿主机上，这些文件本来就摆在那儿。
//
// CPU 占用率和网速都是**两次采样之间的增量**，所以服务里存了上一次的样本。
// 第一次调用没有基准，返回 0 并标记 warmingUp，让界面知道「不是真的 0」。

type cpuSample struct {
	busy, total uint64
	at          time.Time
}

type netSample struct {
	rx, tx uint64
	at     time.Time
}

type Perf struct {
	OS   string `json:"os"`
	Host string `json:"host"`

	CPUPercent  float64 `json:"cpuPercent"`
	MemPercent  float64 `json:"memPercent"`
	MemTotal    uint64  `json:"memTotal"`
	MemUsed     uint64  `json:"memUsed"`
	DiskPercent float64 `json:"diskPercent"`
	DiskTotal   uint64  `json:"diskTotal"`
	DiskUsed    uint64  `json:"diskUsed"`

	Iface  string  `json:"iface"`
	RxRate float64 `json:"rxRate"` // 字节/秒
	TxRate float64 `json:"txRate"`

	// WarmingUp 为 true 表示这是第一次采样，速率类指标还没有基准。
	WarmingUp bool `json:"warmingUp"`
}

// Perf 采集一次性能快照。diskPath 传要统计的挂载点。
func (s *Service) Perf(diskPath, serverIP string) *Perf {
	p := &Perf{OS: osLabel(), Host: hostname()}

	s.perfMu.Lock()
	defer s.perfMu.Unlock()

	// ---- CPU ----
	if cur, ok := readCPU(); ok {
		if s.hasCPU {
			dt := cur.total - s.lastCPU.total
			db := cur.busy - s.lastCPU.busy
			if dt > 0 {
				p.CPUPercent = round1(float64(db) / float64(dt) * 100)
			}
		} else {
			p.WarmingUp = true
		}
		s.lastCPU, s.hasCPU = cur, true
	}

	// ---- 内存 ----
	// 用 MemAvailable 而不是 MemFree：Linux 把大量内存拿去做页缓存，
	// MemFree 常年很低，拿它算占用率会永远显示 90%+，毫无参考价值。
	if total, avail, ok := readMem(); ok && total > 0 {
		p.MemTotal, p.MemUsed = total, total-avail
		p.MemPercent = round1(float64(total-avail) / float64(total) * 100)
	}

	// ---- 磁盘 ----
	if diskPath == "" {
		diskPath = "/"
	}
	var st syscall.Statfs_t
	if err := syscall.Statfs(diskPath, &st); err == nil && st.Blocks > 0 {
		bs := uint64(st.Bsize)
		total := st.Blocks * bs
		used := (st.Blocks - st.Bfree) * bs
		avail := st.Bavail * bs
		p.DiskTotal, p.DiskUsed = total, used
		// 百分比按 used/(used+avail) 算，**和 df 的口径一致**。
		// 直接用 used/total 会把保留给 root 的那部分算成「可用」，
		// 结果比 df 少几个点（现网实测 47.2% vs df 的 50%）——
		// 运维一对照控制台就会觉得这页在骗人。
		if used+avail > 0 {
			p.DiskPercent = round1(float64(used) / float64(used+avail) * 100)
		}
	}

	// ---- 网络 ----
	iface := s.pickIface(serverIP)
	p.Iface = iface
	if cur, ok := readNet(iface); ok {
		if s.hasNet {
			dt := cur.at.Sub(s.lastNet.at).Seconds()
			if dt > 0.2 {
				p.RxRate = rate(cur.rx, s.lastNet.rx, dt)
				p.TxRate = rate(cur.tx, s.lastNet.tx, dt)
			}
		} else {
			p.WarmingUp = true
		}
		s.lastNet, s.hasNet = cur, true
	}
	return p
}

// rate 计算速率，顺便挡住计数器回绕/网卡重置导致的负增量。
func rate(cur, prev uint64, dt float64) float64 {
	if cur < prev {
		return 0
	}
	return round1(float64(cur-prev) / dt)
}

func round1(v float64) float64 {
	return float64(int64(v*10+0.5)) / 10
}

// pickIface 选一块网卡统计流量。
//
// 优先选**持有服务器 IP 的那块**（现网 eth0 同时挂了 192.168.1.53 与 192.168.2.159）；
// 找不到就退回第一块非回环、非 docker/网桥/虚拟网卡的接口 ——
// 这台机器上有 br-ccd7c4f108dd 这种 Docker 网桥，统计它没有意义。
func (s *Service) pickIface(serverIP string) string {
	if s.ifaceHit != "" {
		return s.ifaceHit
	}
	ifs, err := net.Interfaces()
	if err != nil {
		return ""
	}
	fallback := ""
	for _, ifc := range ifs {
		if ifc.Flags&net.FlagLoopback != 0 || ifc.Flags&net.FlagUp == 0 {
			continue
		}
		n := ifc.Name
		if strings.HasPrefix(n, "docker") || strings.HasPrefix(n, "br-") ||
			strings.HasPrefix(n, "veth") || strings.HasPrefix(n, "virbr") {
			continue
		}
		if fallback == "" {
			fallback = n
		}
		if serverIP == "" {
			continue
		}
		addrs, err := ifc.Addrs()
		if err != nil {
			continue
		}
		for _, a := range addrs {
			ip, _, err := net.ParseCIDR(a.String())
			if err == nil && ip.String() == serverIP {
				s.ifaceHit = n
				return n
			}
		}
	}
	s.ifaceHit = fallback
	return fallback
}

func readCPU() (cpuSample, bool) {
	f, err := os.Open("/proc/stat")
	if err != nil {
		return cpuSample{}, false
	}
	defer f.Close()
	sc := bufio.NewScanner(f)
	for sc.Scan() {
		line := sc.Text()
		if !strings.HasPrefix(line, "cpu ") {
			continue
		}
		fields := strings.Fields(line)[1:]
		var total, idle uint64
		for i, fv := range fields {
			v, err := strconv.ParseUint(fv, 10, 64)
			if err != nil {
				continue
			}
			total += v
			// 第 4、5 项是 idle 与 iowait，都不算「忙」
			if i == 3 || i == 4 {
				idle += v
			}
		}
		if total == 0 {
			return cpuSample{}, false
		}
		return cpuSample{busy: total - idle, total: total, at: time.Now()}, true
	}
	return cpuSample{}, false
}

func readMem() (total, avail uint64, ok bool) {
	f, err := os.Open("/proc/meminfo")
	if err != nil {
		return 0, 0, false
	}
	defer f.Close()
	sc := bufio.NewScanner(f)
	for sc.Scan() {
		fields := strings.Fields(sc.Text())
		if len(fields) < 2 {
			continue
		}
		v, err := strconv.ParseUint(fields[1], 10, 64)
		if err != nil {
			continue
		}
		switch fields[0] {
		case "MemTotal:":
			total = v * 1024
		case "MemAvailable:":
			avail = v * 1024
		}
		if total > 0 && avail > 0 {
			break
		}
	}
	return total, avail, total > 0
}

func readNet(iface string) (netSample, bool) {
	if iface == "" {
		return netSample{}, false
	}
	f, err := os.Open("/proc/net/dev")
	if err != nil {
		return netSample{}, false
	}
	defer f.Close()
	sc := bufio.NewScanner(f)
	prefix := iface + ":"
	for sc.Scan() {
		line := strings.TrimSpace(sc.Text())
		if !strings.HasPrefix(line, prefix) {
			continue
		}
		fields := strings.Fields(strings.TrimPrefix(line, prefix))
		if len(fields) < 9 {
			return netSample{}, false
		}
		rx, err1 := strconv.ParseUint(fields[0], 10, 64)
		tx, err2 := strconv.ParseUint(fields[8], 10, 64)
		if err1 != nil || err2 != nil {
			return netSample{}, false
		}
		return netSample{rx: rx, tx: tx, at: time.Now()}, true
	}
	return netSample{}, false
}

func osLabel() string {
	f, err := os.Open("/etc/os-release")
	if err != nil {
		return "Linux"
	}
	defer f.Close()
	sc := bufio.NewScanner(f)
	name, ver := "", ""
	for sc.Scan() {
		line := sc.Text()
		switch {
		case strings.HasPrefix(line, "NAME="):
			name = strings.Trim(strings.TrimPrefix(line, "NAME="), `"`)
		case strings.HasPrefix(line, "VERSION="):
			ver = strings.Trim(strings.TrimPrefix(line, "VERSION="), `"`)
		}
	}
	if name == "" {
		return "Linux"
	}
	if ver == "" {
		return name
	}
	return fmt.Sprintf("%s %s", name, ver)
}

func hostname() string {
	h, err := os.Hostname()
	if err != nil {
		return ""
	}
	return h
}
