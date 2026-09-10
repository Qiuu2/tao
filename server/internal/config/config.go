// Package config 负责加载运行配置。
//
// 设计约束（见《新版Web重构开发手册》§1.6）：
//   - 数据库连接字符集固定 utf8，不得升级 utf8mb4（契约 C-01）
//   - 后台服务 UDP 通知端口不硬编码，运行时从 serverbaseparam.webport 读取
//   - 媒体物理路径 = MediaRoot + media.filename（§1.6.3）
package config

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"time"

	"gopkg.in/yaml.v3"
)

type Config struct {
	Server    Server    `yaml:"server"`
	Database  Database  `yaml:"database"`
	Media     Media     `yaml:"media"`
	Notify    Notify    `yaml:"notify"`
	SDK       SDK       `yaml:"sdk"`
	Auth      Auth      `yaml:"auth"`
	Logs      Logs      `yaml:"logs"`
	Backup    Backup    `yaml:"backup"`
	Dashboard Dashboard `yaml:"dashboard"`
	Register  Register  `yaml:"register"`
	Assistant Assistant `yaml:"assistant"`
	Legacy    Legacy    `yaml:"legacy"`
}

// Legacy 指向旧系统仍在使用的配置文件。**全部只读**。
//
// 服务器参数页上的「web端口」「sdk端口」不在数据库里，
// 而在 Apache 的 httpd.conf 与旧版 swagger1.json 里（详见 serverparam/apache.go）。
// 新版只解析、绝不回写：旧版改这两处用的是按行号 sed（D-209），
// 行数一变就会写坏 httpd.conf，整个站点起不来。
type Legacy struct {
	// ApacheConf 旧系统 Apache 主配置，从中取 LISTEN 与 ServerName。
	ApacheConf string `yaml:"apache_conf"`
	// SwaggerFile 旧版对外 API 的 swagger 文档，其 host 字段带着 sdk 端口。
	SwaggerFile string `yaml:"swagger_file"`
}

// Register 是注册服务（旧版 regist_server.php）的配置。
//
// 旧版把这三样写死在代码里：命令 `registerserver`、
// 文件 /var/www/html/ok112/serial 与 .../serialtwo。
// 新版做成配置项，部署到别的路径不用改代码。
type Register struct {
	// Command 注册用的外部命令，默认 registerserver。
	// 取标准输出第一行判定 success / failed / expired。
	Command string `yaml:"command"`
	// SerialFile 记录试用起算日的文件。留空则「注册服务」页算不出剩余天数，
	// 页面会如实说明，而不是猜一个日期。
	SerialFile string `yaml:"serial_file"`
	// TrialFile 领过试用后留下的标记文件。留空则不允许领取试用。
	TrialFile string `yaml:"trial_file"`
}

// Dashboard 是看板首页的配置。
type Dashboard struct {
	// File 存放快捷入口 / 快捷任务 / 紧急广播绑定的 JSON 文件。
	// 留空则取 <备份目录的上级>/dashboard.json。
	File string `yaml:"file"`
}

// BackupMediaDir 返回媒体物理目录，没配就按 media.root 推。
// 现网 media.filename 存的是 "/backup/mediadata/x.mp3"，
// 物理路径 = media.root + filename，所以目录就是 root + "/backup/mediadata"。
func (c *Config) BackupMediaDir() string {
	if d := c.Backup.MediaDir; d != "" {
		return d
	}
	return c.Media.Root + "/backup/mediadata"
}

// BackupDir 返回备份包目录，没配就放在程序目录下的 backups。
func (c *Config) BackupDir() string {
	if d := c.Backup.Dir; d != "" {
		return d
	}
	return "./backups"
}

// DashboardFile 返回看板状态文件路径（快捷入口 / 快捷任务 / 紧急广播绑定）。
//
// 这三样是新增的界面状态，旧库里没有对应的表，而红线禁止建表；
// 现有的空表又都可能被后台 C 服务扫描，塞进去有触发误广播的风险。
// 所以落成一个 JSON 文件，默认放在备份目录旁边。详见 dashboard 包的说明。
func (c *Config) DashboardFile() string {
	if f := c.Dashboard.File; f != "" {
		return f
	}
	return filepath.Join(filepath.Dir(c.BackupDir()), "dashboard.json")
}

// LogSettingsFile 返回日志设置文件路径（目前只有「保留期」一项）。
//
// 与 DashboardFile 同一个理由：这是新增的界面设置，旧库里没有对应的表，
// 而红线禁止建表；现有的空表又都可能被后台 C 服务扫描，塞进去有触发误广播的风险。
// 详见 logs/retention.go 的说明。
func (c *Config) LogSettingsFile() string {
	if f := c.Logs.SettingsFile; f != "" {
		return f
	}
	return filepath.Join(filepath.Dir(c.BackupDir()), "logsettings.json")
}

// Backup 是备份恢复模块的配置。
type Backup struct {
	// Dir 备份包存放目录。
	Dir string `yaml:"dir"`
	// MediaDir 媒体物理目录。留空则取 media.root + "/backup/mediadata"。
	MediaDir string `yaml:"media_dir"`
}

// Logs 是日志模块的配置。
type Logs struct {
	// TaskDir 任务日志目录。它**不在数据库里**（BR-251），是后台 C 服务
	// 每天写一个 logYYYY-MM-DD.html 的文件目录，现网在
	// /opt/apps/a9000/html/ok112/datelog。
	// 留空表示禁用任务日志功能（接口直接回「未配置」而不是去猜路径）。
	TaskDir string `yaml:"task_dir"`
	// SettingsFile 存日志保留期这类界面可改的设置。
	// 留空则取 <备份目录的上级>/logsettings.json，与看板状态文件同一个目录。
	SettingsFile string `yaml:"settings_file"`
}

type Server struct {
	// Listen 新版 Web 的监听地址。必须避开现网已占用端口（80/81/99/8000/8090）。
	Listen string `yaml:"listen"`
	// StaticDir 前端 dist 目录。由 Go 进程直接托管，避免改动现网 Apache 配置。
	StaticDir string `yaml:"static_dir"`
}

type Database struct {
	Host string `yaml:"host"`
	Port int    `yaml:"port"`
	User string `yaml:"user"`
	Pass string `yaml:"pass"`
	Name string `yaml:"name"`
	// MaxOpen/MaxIdle 控制连接池。现网 MariaDB 与旧 PHP 共用，不宜开太大。
	MaxOpen int `yaml:"max_open"`
	MaxIdle int `yaml:"max_idle"`
}

// DSN 组装连接串。
//
// charset=utf8 是硬性要求：现网库级默认字符集是 latin1，表级多为 utf8，
// 只有显式声明 utf8 才能与旧 PHP（mysqli_query "set names utf8"）读写一致。
// 绝不能改成 utf8mb4，否则四字节字符写入 utf8 列会截断或报错。
//
// timeout 取 30s 而不是常见的 5~10s，原因见 §1.6.9：
// 现网 MariaDB 的 skip_name_resolve 未开启，而 Docker 网桥网关是 212.218.2.1
// 这个公网段地址，服务端对它做反向 DNS 解析会一直等到超时，
// 导致**每建立一条新连接固定耗时约 10 秒**。查询本身不受影响（0ms）。
// 这里把超时放宽到 30s，配合连接复用策略，把这笔开销限制在启动预热阶段。
func (d Database) DSN() string {
	return fmt.Sprintf("%s:%s@tcp(%s:%d)/%s?charset=utf8&parseTime=true&loc=Local&timeout=30s&readTimeout=30s&writeTimeout=30s",
		d.User, d.Pass, d.Host, d.Port, d.Name)
}

type Media struct {
	// Root 宿主机应用根目录。media.filename 存的是 "/backup/mediadata/x.mp3"，
	// 宿主真实路径 = Root + filename。
	Root string `yaml:"root"`
	// MaxUploadMB 单文件上传上限，业务规则 BR-37 规定 300MB。
	MaxUploadMB int64 `yaml:"max_upload_mb"`
	// FFmpeg 转码程序路径。现网自带 /opt/apps/a9000/bin/ffmpeg。
	// 上传必须统一转码为 mp3 并追加 2 秒静音（BR-38 ~ BR-40）。
	FFmpeg string `yaml:"ffmpeg"`
}

type Notify struct {
	// Host 后台 C 广播服务地址。容器内主机名是 audioserver，
	// 但新版跑在宿主上，走 Docker 已发布的回环端口。
	Host string `yaml:"host"`
	// Port 为 0 时表示运行时从 serverbaseparam.webport 动态读取（推荐）。
	Port int `yaml:"port"`
	// Enabled 关闭后只记日志不真正发包，用于联调期避免干扰生产广播。
	Enabled bool `yaml:"enabled"`
}

// SDK 是厂商 SDK 的二进制命令端口（首页那四个紧急广播按钮走这里）。
//
// 与上面的 Notify 是**两套协议、两个端口**，不能合并：
// notify 发文本报文到 serverbaseparam.webport，这里发结构体的内存布局到 8885。
// 详见 internal/sdkudp 的包说明。
type SDK struct {
	// Host 后台广播服务地址。默认走本机回环 —— 厂商示例里写的是 docker 里的
	// 主机名 audioserver，新版跑在宿主上，用已发布的回环端口。
	Host string `yaml:"host"`
	// Port SDK 命令端口，默认 8885。
	Port int `yaml:"port"`
	// Enabled 关掉后只记日志不真发包。联调期用得上 ——
	// 这条命令一发出去，整所学校的喇叭就响了，没有「预演」这一说。
	Enabled bool `yaml:"enabled"`
}

type Auth struct {
	// Secret 用于签发会话令牌的 HMAC 密钥。
	Secret string `yaml:"secret"`
	// TTL 会话有效期。
	TTL time.Duration `yaml:"ttl"`
	// CaptchaEnabled 登录是否强制校验验证码（业务规则 BR-76）。
	CaptchaEnabled bool `yaml:"captcha_enabled"`
}

func Load(path string) (*Config, error) {
	raw, err := os.ReadFile(path)
	if err != nil {
		return nil, fmt.Errorf("读取配置文件 %s: %w", path, err)
	}
	cfg := Default()
	if err := yaml.Unmarshal(raw, cfg); err != nil {
		return nil, fmt.Errorf("解析配置文件 %s: %w", path, err)
	}
	if err := cfg.validate(); err != nil {
		return nil, err
	}
	return cfg, nil
}

func Default() *Config {
	return &Config{
		Server: Server{Listen: ":8080", StaticDir: "./web"},
		Database: Database{
			Host: "127.0.0.1", Port: 3306, Name: "audioserver",
			MaxOpen: 20, MaxIdle: 5,
		},
		Media:  Media{Root: "/opt/apps/a9000", MaxUploadMB: 300, FFmpeg: "/opt/apps/a9000/bin/ffmpeg"},
		Notify: Notify{Host: "127.0.0.1", Port: 0, Enabled: true},
		SDK:    SDK{Host: "127.0.0.1", Port: 8885, Enabled: true},
		Auth:   Auth{TTL: 8 * time.Hour, CaptchaEnabled: true},
		Assistant: Assistant{
			Enabled:      false,
			NLUURL:       "http://127.0.0.1:5013",
			NLUTimeout:   5 * time.Second,
			SessionTTL:   30 * time.Minute,
			UndoTTL:      10 * time.Minute,
			HistoryLimit: 1000,
		},
		Legacy: Legacy{
			ApacheConf:  "/opt/apps/a9000/home/apache/httpd.conf",
			SwaggerFile: "/opt/apps/a9000/html/ok112/swagger-ui/dist/swagger1.json",
		},
	}
}

func (c *Config) validate() error {
	if c.Database.User == "" {
		return fmt.Errorf("database.user 不能为空")
	}
	if c.Auth.Secret == "" || len(c.Auth.Secret) < 16 {
		return fmt.Errorf("auth.secret 必须配置且长度不少于 16 字符")
	}
	if c.Media.Root == "" {
		return fmt.Errorf("media.root 不能为空")
	}
	// 端口配成 0 的话包会发到 127.0.0.1:0 上，Dial 那一步就报错了，
	// 但 UDP 没有回执，界面上只会看到「已下发」——给个兜底值。
	if c.SDK.Port <= 0 {
		c.SDK.Port = 8885
	}
	if strings.TrimSpace(c.SDK.Host) == "" {
		c.SDK.Host = "127.0.0.1"
	}
	if c.Auth.TTL <= 0 {
		c.Auth.TTL = 8 * time.Hour
	}
	// 助手的几个时限给兜底值：配错成 0 会让超时立刻触发、会话永远算过期，
	// 表现是「助手时好时坏」，很难查。
	if c.Assistant.NLUTimeout <= 0 {
		c.Assistant.NLUTimeout = 5 * time.Second
	}
	if c.Assistant.SessionTTL <= 0 {
		c.Assistant.SessionTTL = 30 * time.Minute
	}
	if c.Assistant.UndoTTL <= 0 {
		c.Assistant.UndoTTL = 10 * time.Minute
	}
	if c.Assistant.HistoryLimit <= 0 {
		c.Assistant.HistoryLimit = 1000
	}
	if c.Assistant.Enabled && strings.TrimSpace(c.Assistant.NLUURL) == "" {
		return fmt.Errorf("assistant.enabled 为 true 时 assistant.nlu_url 不能为空")
	}
	return nil
}

// Assistant 是 AI 助手（业务域十四）。
//
// # 为什么 NLU 要单开一个进程
//
// 意图识别与槽位抽取用的是一个训练好的中文模型（joint_rbt3，RBT3 三层
// RoBERTa + 意图头 + 槽位 BIO 头），权重 147MB，推理要 PyTorch。
// htweb 是纯 Go 单二进制（直接依赖只有 mysql 驱动和 yaml 两个，这是有意的），
// 把 PyTorch 塞进来既不可能也不划算。
//
// 所以拆成两半，边界卡得很死：
//
//	Python 侧  只做推理：文本进 → {intent, confidence, slots} 出。
//	           **不碰数据库、不做任何写操作、不知道广播系统的存在。**
//	Go 侧      拿到意图槽位之后的一切：消歧、鉴权、执行、审计、回话。
//
// 这样模型效果与原系统一比一保住，而所有写操作都落在 Go + 数据库这一侧。
// 与 htweb 同机部署，走 127.0.0.1，不出网。
type Assistant struct {
	// Enabled 关掉时整个助手不注册路由，前端也拿不到入口。
	Enabled bool `yaml:"enabled"`
	// NLUURL 是本机 NLU 服务的地址，形如 http://127.0.0.1:5013。
	// 留空等于没有 NLU —— 助手会如实回报「识别服务不可用」，而不是假装听懂。
	NLUURL string `yaml:"nlu_url"`
	// NLUTimeout 单次推理超时。模型很小，正常在 100ms 内，
	// 给到 5s 是为了容忍冷启动第一次加载权重。
	NLUTimeout time.Duration `yaml:"nlu_timeout"`
	// SessionTTL 会话上下文多久算过期。过期后指代消解（「刚才那个」）失效，
	// 重新开始一轮对话。
	SessionTTL time.Duration `yaml:"session_ttl"`
	// UndoTTL 撤销凭据的有效期。过了就撤不回来了 ——
	// 广播任务过一会儿可能已经播出去了，撤销的意义随时间迅速衰减。
	UndoTTL time.Duration `yaml:"undo_ttl"`
	// HistoryLimit 指令历史保留多少条（按用户计）。原实现是 1000。
	HistoryLimit int `yaml:"history_limit"`
}
