// Package serverparam 实现服务器参数（业务域十二，F-56）。
//
// # 旧版这一页的写入语句
//
//	UPDATE serverbaseparam SET dataport='$_POST[dateport]', ip='$_POST[ip]',
//	  gateway='$_POST[gateway]', port='$_POST[port]', udpport='$_POST[udpport]',
//	  maxbandwidth='$_POST[maxbandwidth]', maxhttpconnections='$_POST[maxhttpconnections]',
//	  offlineport='$_POST[offlineport]', backupmode='$servermodes'
//
// 八个字段纯 `$_POST` 拼接、零校验，而且 **UPDATE 不带 WHERE**（D-208）——
// 表只有一行才侥幸没出事。
//
// # 关于「改 Web 端口」这件事，新版有意不做
//
// 旧版改 webport 的做法是 `sed -i '241c ...'` **按行号改 Apache 配置**（D-209），
// 配置文件行数一变就改错行、写坏 httpd.conf，Web 直接起不来；
// 而且 sed 命令是字符串拼 `$ip`、`$listenport`，还带命令注入（D-210）。
//
// 新版 Web 是独立进程监听自己的端口，跟 Apache 没关系，
// 因此**只把值写进数据库，绝不去动任何系统配置文件**。
// 哪些参数改完需要重启什么，在响应里如实列出来，由运维去做。
//
// ⚠ 注意 `webport` 这个列名容易误导：它不是 Apache 端口，
// 而是后台 C 服务的 **UDP 通知端口**（现网 8886，notify 包就是读它）。
// 改了它，新版发通知的目标端口会立刻跟着变。
//
// 界面上那个「web端口」（现网 80）和「sdk端口」（现网 99）跟这一列没有关系，
// 它们在 Apache 的 httpd.conf 和旧版 swagger1.json 里 —— 见 apache.go。
package serverparam

import (
	"context"
	"database/sql"
	"fmt"
	"net"
	"strings"
)

type Service struct {
	db *sql.DB
	// apacheConf / swaggerFile 是旧系统那两个配置文件，**只读**。
	// web端口与 sdk端口存在那里而不是数据库里，原因见 apache.go 开头。
	apacheConf  string
	swaggerFile string
	// a9000Root 是旧系统的安装根目录（现网 /opt/apps/a9000，即 config 里的 media.root）。
	// 「版本设置」页要靠它找到 sounds/audioserver 下的版本包与 script/cmd 下的重建脚本，
	// 见 version.go。留空时版本切换会直接报「找不到 a9000 安装目录」而不是乱猜路径。
	a9000Root string
}

func New(db *sql.DB, apacheConf, swaggerFile, a9000Root string) *Service {
	return &Service{db: db, apacheConf: apacheConf, swaggerFile: swaggerFile, a9000Root: a9000Root}
}

var ErrReadOnly = fmt.Errorf("当前是备机模式，不允许修改服务器参数")

// Network / Ports / Capacity / Multicast / License / HA / Misc 是分组后的参数。
// 分组只为界面好组织，落库还是同一行。
type Network struct {
	IP         string `json:"ip"`
	Gateway    string `json:"gateway"`
	SubnetMask string `json:"subnetmask"`
}

type Ports struct {
	WebPort     int `json:"webport"`
	Port        int `json:"port"`
	HBPort      int `json:"hbport"`
	UDPPort     int `json:"udpport"`
	RTSPPort    int `json:"rtspport"`
	DataPort    int `json:"dataport"`
	OfflinePort int `json:"offlineport"`
}

type Capacity struct {
	MaxHTTPConnections int `json:"maxhttpconnections"`
	MaxBandwidth       int `json:"maxbandwidth"`
}

type Multicast struct {
	IP   string `json:"ip"`
	Port int    `json:"port"`
}

// License 是授权路数。这几个由注册决定，界面上只读展示。
type License struct {
	NetRadioCount     int `json:"netradiocount"`
	SoundCardCount    int `json:"soundcardcount"`
	CtrlTerminalCount int `json:"ctrlterminalcount"`
}

type HA struct {
	Model     int    `json:"model"`
	ModelText string `json:"modelText"`
	// Name 是本机的服务器名称（serverbaseparam.name）。
	//
	// 它放在主备配置里而不是 ReadOnly 里，因为旧版 setmaster_backup 那条
	// `UPDATE serverbaseparam SET name=?, model=?, subnetmask=?, masterip=?, slaveip=?, slavename=?`
	// 就是在主备配置页里保存的 —— 服务器名称在旧版**是可改的**。
	// 早前新版把它当成「由后台服务与注册模块维护」的只读列，等于砍掉了一个功能（N-16）。
	Name      string `json:"name"`
	MasterIP  string `json:"masterip"`
	SlaveIP   string `json:"slaveip"`
	SlaveName string `json:"slavename"`
	Backup    int    `json:"backup"`
}

type Misc struct {
	NTPServer   string `json:"ntpserver"`
	ProjectName string `json:"projectname"`
	Factory     string `json:"factory"`
	DealerInfo  string `json:"dealerinfo"`
	Version     string `json:"version"`
	BackupMode  int    `json:"backupmode"`
	IsCheckMAC  int    `json:"ischeckmac"`
	AdjustTime  int    `json:"adjusttime"`
	SoundDetect int    `json:"sounddetect"`
	FuzaMima    int    `json:"fuzamima"`
}

// ReadOnly 是由后台服务与注册模块维护的列，**新版一律不写**（BR-255）。
type ReadOnly struct {
	Name                string `json:"name"`
	WorkState           int    `json:"workstate"`
	CurrentConnectCount int    `json:"currectconnectcount"`
	CurrentBandwidth    int    `json:"currentbandwidth"`
	TaskCount           int    `json:"taskcount"`
	RegisterFlag        int    `json:"registerflag"`
	RegisterSerial      string `json:"registerserial"`
	TryStartDate        string `json:"trystartdate"`
	TryEndDate          string `json:"tryenddate"`
	NetState            int    `json:"netstate"`
	TerminalChange      int    `json:"terminalchange"`
	TaskChange          int    `json:"taskchange"`
	ServerChange        int    `json:"serverchange"`
}

type Params struct {
	Network   Network   `json:"network"`
	Ports     Ports     `json:"ports"`
	Apache    Apache    `json:"apache"`
	Capacity  Capacity  `json:"capacity"`
	Multicast Multicast `json:"multicast"`
	License   License   `json:"license"`
	HA        HA        `json:"ha"`
	Misc      Misc      `json:"misc"`
	ReadOnly  ReadOnly  `json:"readonly"`
}

func modelText(m int) string {
	switch m {
	case 1:
		return "主服务器"
	case 2:
		return "备机"
	default:
		return fmt.Sprintf("未知(%d)", m)
	}
}

// Get 读取全部参数。
func (s *Service) Get(ctx context.Context) (*Params, error) {
	p := &Params{}
	var trystart, tryend sql.NullString
	err := s.db.QueryRowContext(ctx, `
		SELECT COALESCE(name,''), COALESCE(ip,''), COALESCE(gateway,''), COALESCE(subnetmask,''),
		       COALESCE(webport,0), COALESCE(port,0), COALESCE(hbport,0), COALESCE(udpport,0),
		       COALESCE(rtspport,0), COALESCE(dataport,0), COALESCE(offlineport,0),
		       COALESCE(maxhttpconnections,0), COALESCE(maxbandwidth,0),
		       COALESCE(multicastip,''), COALESCE(multicastport,0),
		       COALESCE(netradiocount,0), COALESCE(soundcardcount,0), COALESCE(ctrlterminalcount,0),
		       COALESCE(model,1), COALESCE(masterip,''), COALESCE(slaveip,''), COALESCE(slavename,''),
		       COALESCE(backup,0), COALESCE(ntpserver,''), COALESCE(projectname,''),
		       COALESCE(factory,''), COALESCE(dealerinfo,''), COALESCE(version,''),
		       COALESCE(backupmode,0), COALESCE(ischeckmac,0), COALESCE(adjusttime,0),
		       COALESCE(sounddetect,0),
		       COALESCE(workstate,0), COALESCE(currectconnectcount,0), COALESCE(currentbandwidth,0),
		       COALESCE(taskcount,0), COALESCE(registerflag,0), COALESCE(registerserial,''),
		       CAST(trystartdate AS CHAR), CAST(tryenddate AS CHAR),
		       COALESCE(netstate,0), COALESCE(terminalchange,0), COALESCE(taskchange,0),
		       COALESCE(serverchange,0)
		FROM serverbaseparam WHERE id = 1 LIMIT 1`).Scan(
		&p.ReadOnly.Name, &p.Network.IP, &p.Network.Gateway, &p.Network.SubnetMask,
		&p.Ports.WebPort, &p.Ports.Port, &p.Ports.HBPort, &p.Ports.UDPPort,
		&p.Ports.RTSPPort, &p.Ports.DataPort, &p.Ports.OfflinePort,
		&p.Capacity.MaxHTTPConnections, &p.Capacity.MaxBandwidth,
		&p.Multicast.IP, &p.Multicast.Port,
		&p.License.NetRadioCount, &p.License.SoundCardCount, &p.License.CtrlTerminalCount,
		&p.HA.Model, &p.HA.MasterIP, &p.HA.SlaveIP, &p.HA.SlaveName,
		&p.HA.Backup, &p.Misc.NTPServer, &p.Misc.ProjectName,
		&p.Misc.Factory, &p.Misc.DealerInfo, &p.Misc.Version,
		&p.Misc.BackupMode, &p.Misc.IsCheckMAC, &p.Misc.AdjustTime,
		&p.Misc.SoundDetect,
		&p.ReadOnly.WorkState, &p.ReadOnly.CurrentConnectCount, &p.ReadOnly.CurrentBandwidth,
		&p.ReadOnly.TaskCount, &p.ReadOnly.RegisterFlag, &p.ReadOnly.RegisterSerial,
		&trystart, &tryend,
		&p.ReadOnly.NetState, &p.ReadOnly.TerminalChange, &p.ReadOnly.TaskChange,
		&p.ReadOnly.ServerChange)
	if err != nil {
		return nil, fmt.Errorf("读取服务器参数: %w", err)
	}
	p.ReadOnly.TryStartDate, p.ReadOnly.TryEndDate = trystart.String, tryend.String
	p.HA.ModelText = modelText(p.HA.Model)
	// name 同时出现在 ReadOnly（版本页展示）和 HA（主备页可改）两处，
	// 是同一列的两个视图，不是两份数据。
	p.HA.Name = p.ReadOnly.Name
	p.Apache = readApache(s.apacheConf, s.swaggerFile)

	// fuzamima 只在 serverconfig 里，与 sounddetect 一起构成那对冗余存储
	_ = s.db.QueryRowContext(ctx,
		`SELECT COALESCE(fuzamima,0) FROM serverconfig ORDER BY id LIMIT 1`).Scan(&p.Misc.FuzaMima)
	return p, nil
}

// Input 是可写参数。License 与 ReadOnly 不在其中 —— 它们由注册与后台服务维护。
type Input struct {
	Network   Network
	Ports     Ports
	Capacity  Capacity
	Multicast Multicast
	HA        HA
	Misc      Misc
}

// validate 逐字段校验（旧版一个都没有）。
func (in *Input) validate() error {
	in.Network.IP = strings.TrimSpace(in.Network.IP)
	in.Network.Gateway = strings.TrimSpace(in.Network.Gateway)
	in.Network.SubnetMask = strings.TrimSpace(in.Network.SubnetMask)
	in.Multicast.IP = strings.TrimSpace(in.Multicast.IP)

	for _, spec := range []struct {
		name  string
		value string
		must  bool
	}{
		{"服务器 IP", in.Network.IP, true},
		{"网关", in.Network.Gateway, false},
		{"主机 IP", in.HA.MasterIP, false},
		{"备机 IP", in.HA.SlaveIP, false},
	} {
		if spec.value == "" {
			if spec.must {
				return fmt.Errorf("%s不能为空", spec.name)
			}
			continue
		}
		if !isIPv4(spec.value) {
			return fmt.Errorf("%s格式不正确，必须是 IPv4", spec.name)
		}
	}
	if err := checkMask(in.Network.SubnetMask); err != nil {
		return err
	}
	if in.Multicast.IP != "" {
		ip := net.ParseIP(in.Multicast.IP)
		if ip == nil || ip.To4() == nil {
			return fmt.Errorf("组播 IP 格式不正确，必须是 IPv4")
		}
		if !ip.IsMulticast() {
			return fmt.Errorf("组播 IP 必须落在 224.0.0.0 ~ 239.255.255.255 之间")
		}
	}

	// 端口逐个查范围，再查互相冲突（BR-257，旧版两样都不查，D-211）
	ports := []struct {
		name string
		val  int
	}{
		{"Web 通知端口", in.Ports.WebPort},
		{"服务端口", in.Ports.Port},
		{"心跳端口", in.Ports.HBPort},
		{"UDP 端口", in.Ports.UDPPort},
		{"RTSP 端口", in.Ports.RTSPPort},
		{"数据端口", in.Ports.DataPort},
		{"离线端口", in.Ports.OfflinePort},
		{"组播端口", in.Multicast.Port},
	}
	seen := map[int]string{}
	for _, p := range ports {
		if p.val < 1 || p.val > 65535 {
			return fmt.Errorf("%s必须在 1 ~ 65535 之间", p.name)
		}
		if other, dup := seen[p.val]; dup {
			return fmt.Errorf("%s与%s都填了 %d，端口不能重复", p.name, other, p.val)
		}
		seen[p.val] = p.name
	}

	if in.Capacity.MaxHTTPConnections < 0 || in.Capacity.MaxHTTPConnections > 1000000 {
		return fmt.Errorf("最大连接数必须在 0 ~ 1000000 之间")
	}
	if in.Capacity.MaxBandwidth < 0 {
		return fmt.Errorf("最大带宽不能为负")
	}
	if in.HA.Model != 1 && in.HA.Model != 2 {
		return fmt.Errorf("服务器模式只能是 1（主服务器）或 2（备机）")
	}
	for _, spec := range []struct {
		name string
		val  int
	}{
		{"考试模式", in.Misc.BackupMode},
		{"MAC 限制", in.Misc.IsCheckMAC},
		{"声压检测", in.Misc.SoundDetect},
		{"主从复制", in.HA.Backup},
	} {
		if spec.val != 0 && spec.val != 1 {
			return fmt.Errorf("%s只能是 0 或 1", spec.name)
		}
	}
	// ⚠ adjusttime **不在**上面那组 0/1 开关里。
	//
	// 列名看着像布尔，实际存的是「GPS 校时终端的 terminal.id」，0 表示未启用 ——
	// 依据是旧版 setgpsterminal.php 的 `update serverbaseparam set adjusttime='$gpsselects'`，
	// $gpsselects 就是下拉框里选中的终端 id。
	// 早前这里把它当 0/1 校验（新版缺陷 N-13）：真选了一台 id=7 的终端会被判非法，
	// 而写进 1 又会指向另一台毫不相干的设备。
	// 它现在由「时间设置」页专门维护，这一页不再写它，见 timeset 包。
	if in.Misc.AdjustTime < 0 {
		return fmt.Errorf("校时终端 ID 不合法")
	}
	in.HA.Name = strings.TrimSpace(in.HA.Name)
	if in.HA.Name == "" {
		return fmt.Errorf("服务器名称不能为空")
	}
	for _, spec := range []struct {
		name  string
		val   string
		limit int
	}{
		{"NTP 服务器", in.Misc.NTPServer, 64},
		{"项目名称", in.Misc.ProjectName, 255},
		{"服务器名称", in.HA.Name, 255},
		{"备机名", in.HA.SlaveName, 255},
	} {
		if len(spec.val) > spec.limit {
			return fmt.Errorf("%s过长：按 UTF-8 计 %d 字节，上限 %d 字节",
				spec.name, len(spec.val), spec.limit)
		}
	}
	return nil
}

func isIPv4(s string) bool {
	ip := net.ParseIP(s)
	return ip != nil && ip.To4() != nil && strings.Count(s, ".") == 3
}

// checkMask 校验子网掩码必须是连续 1（BR-258）。
//
// 旧版用 decbin() 累加再 substr_count 数 1 的个数（D-212），
// 255.0.255.0 这种非连续掩码会被算出一个「看着合理」的前缀长度，
// 实际是错的。这里直接用标准库判定。
func checkMask(s string) error {
	if s == "" {
		return nil
	}
	ip := net.ParseIP(s)
	if ip == nil || ip.To4() == nil {
		return fmt.Errorf("子网掩码格式不正确，必须是 IPv4")
	}
	v4 := ip.To4()
	// IPMask.Size() 对**非规范**（1 不连续）的掩码返回 (0, 0)，
	// 这正是我们要的判据：255.0.255.0 会被它判掉，而 0.0.0.0 返回 (0, 32) 仍算合法。
	if _, bits := net.IPv4Mask(v4[0], v4[1], v4[2], v4[3]).Size(); bits == 0 {
		return fmt.Errorf("子网掩码 %s 不是合法掩码（必须是连续的 1，例如 255.255.255.0）", s)
	}
	return nil
}

type SaveResult struct {
	Updated bool `json:"updated"`
	// RequiresRestart 与 RestartReasons 告诉用户「改完还得做点什么」。
	// 新版**不会**替用户去重启任何东西（那条报文实测是整机重启）。
	RequiresRestart bool     `json:"requiresRestart"`
	RestartReasons  []string `json:"restartReason"`
}

// Save 写入参数。
//
// 只写可写列，且**显式带 WHERE id = 1**（BR-254，修 D-208 的无 WHERE）。
// sounddetect 在 serverbaseparam 与 serverconfig 两处冗余存储，
// 必须同时写、保持一致（BR-259 / 契约 C-42）——
// 旧版是两段散在不同分支的代码，这里收进一个事务。
func (s *Service) Save(ctx context.Context, in Input) (*SaveResult, error) {
	if err := in.validate(); err != nil {
		return nil, err
	}
	before, err := s.Get(ctx)
	if err != nil {
		return nil, err
	}
	// ⚠ 这里**不再**因为备机模式拒绝保存。
	//
	// 原来的做法（BR-260）是备机模式一律 ErrReadOnly，结果把自己锁死了：
	// 主备模式的开关就在这一页上，一旦切到备机，这一页也不能改，
	// 再也没有界面能把它切回主服务器。
	//
	// 现在的口径：**服务器信息这一页在备机模式下照常可改，其余模块仍然只读**。
	// 备机上改任务、改终端没有意义（数据由主机同步过来），但改本机的网络参数、
	// 把自己切回主服务器，恰恰是备机上最需要能做的两件事。
	// 恢复出厂仍然禁止（factory.go 里保留 ErrReadOnly）——那是清库，不是改配置。
	// before 仍然要用：下面靠它与新值比对，算出哪些改动需要重启才生效。

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx, `
		UPDATE serverbaseparam SET
		  ip = ?, gateway = ?, subnetmask = ?,
		  webport = ?, port = ?, hbport = ?, udpport = ?, rtspport = ?, dataport = ?, offlineport = ?,
		  maxhttpconnections = ?, maxbandwidth = ?,
		  multicastip = ?, multicastport = ?,
		  name = ?, model = ?, masterip = ?, slaveip = ?, slavename = ?, backup = ?,
		  ntpserver = ?, projectname = ?,
		  backupmode = ?, ischeckmac = ?, sounddetect = ?
		WHERE id = 1`,
		in.Network.IP, in.Network.Gateway, in.Network.SubnetMask,
		in.Ports.WebPort, in.Ports.Port, in.Ports.HBPort, in.Ports.UDPPort,
		in.Ports.RTSPPort, in.Ports.DataPort, in.Ports.OfflinePort,
		in.Capacity.MaxHTTPConnections, in.Capacity.MaxBandwidth,
		in.Multicast.IP, in.Multicast.Port,
		in.HA.Name, in.HA.Model, in.HA.MasterIP, in.HA.SlaveIP, in.HA.SlaveName, in.HA.Backup,
		in.Misc.NTPServer, in.Misc.ProjectName,
		// adjusttime 刻意不在这条 UPDATE 里：它是 GPS 校时终端的 id，
		// 由「时间设置」页维护（见 timeset 包）。这一页曾把它当开关写，
		// 一保存就会把选好的终端 id 冲成 0 或 1（N-13）。
		in.Misc.BackupMode, in.Misc.IsCheckMAC, in.Misc.SoundDetect,
	); err != nil {
		return nil, fmt.Errorf("保存服务器参数: %w", err)
	}

	if err := syncServerConfig(ctx, tx, in.Misc.SoundDetect, in.Misc.FuzaMima); err != nil {
		return nil, err
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}

	return &SaveResult{Updated: true,
		RequiresRestart: len(restartReasons(before, in)) > 0,
		RestartReasons:  restartReasons(before, in)}, nil
}

// syncServerConfig 维护 serverconfig 那一行。
// 旧版先查有没有行再决定 INSERT 还是 UPDATE（D-216），这里保留同样的语义。
func syncServerConfig(ctx context.Context, tx *sql.Tx, soundDetect, fuzaMima int) error {
	var id int
	err := tx.QueryRowContext(ctx, `SELECT id FROM serverconfig ORDER BY id LIMIT 1`).Scan(&id)
	switch {
	case err == sql.ErrNoRows:
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO serverconfig (sounddetect, fuzamima) VALUES (?,?)`,
			soundDetect, fuzaMima); err != nil {
			return fmt.Errorf("写入 serverconfig: %w", err)
		}
	case err != nil:
		return fmt.Errorf("查询 serverconfig: %w", err)
	default:
		if _, err := tx.ExecContext(ctx,
			`UPDATE serverconfig SET sounddetect = ?, fuzamima = ? WHERE id = ?`,
			soundDetect, fuzaMima, id); err != nil {
			return fmt.Errorf("更新 serverconfig: %w", err)
		}
	}
	return nil
}

// restartReasons 列出哪些改动需要人工干预才能真正生效。
//
// 新版只写数据库，不去动系统配置、不去重启任何服务 ——
// 旧版那套 `sed -i '行号c ...'` 改 Apache 配置的做法（D-209）已彻底放弃。
func restartReasons(before *Params, in Input) []string {
	var out []string
	if before.Ports.WebPort != in.Ports.WebPort {
		out = append(out, fmt.Sprintf(
			"通知端口 webport 由 %d 改为 %d：新版 Web 会立刻按新端口发 UDP 通知，"+
				"后台 C 服务需要重启后才会在新端口上监听",
			before.Ports.WebPort, in.Ports.WebPort))
	}
	for _, c := range []struct {
		name     string
		from, to int
	}{
		{"服务端口 port", before.Ports.Port, in.Ports.Port},
		{"心跳端口 hbport", before.Ports.HBPort, in.Ports.HBPort},
		{"UDP 端口 udpport", before.Ports.UDPPort, in.Ports.UDPPort},
		{"RTSP 端口 rtspport", before.Ports.RTSPPort, in.Ports.RTSPPort},
		{"数据端口 dataport", before.Ports.DataPort, in.Ports.DataPort},
		{"离线端口 offlineport", before.Ports.OfflinePort, in.Ports.OfflinePort},
		{"组播端口", before.Multicast.Port, in.Multicast.Port},
	} {
		if c.from != c.to {
			out = append(out, fmt.Sprintf("%s 由 %d 改为 %d：需要重启后台服务", c.name, c.from, c.to))
		}
	}
	if before.Network.IP != in.Network.IP {
		out = append(out, fmt.Sprintf(
			"服务器 IP 由 %s 改为 %s：这里只改了数据库里的记录，"+
				"系统网卡配置需要另行修改（新版不会去动系统配置文件）",
			before.Network.IP, in.Network.IP))
	}
	if before.HA.Model != in.HA.Model {
		out = append(out, fmt.Sprintf("服务器模式由「%s」改为「%s」：会立刻影响所有人的只读判定",
			modelText(before.HA.Model), modelText(in.HA.Model)))
	}
	if before.Multicast.IP != in.Multicast.IP {
		out = append(out, "组播 IP 变更：需要重启后台服务")
	}
	return out
}
