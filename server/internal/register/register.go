// Package register 实现注册服务（旧版 regist_server.php + do.php 的
// regist_server() / settrydo()）。
//
// # 这一页做什么
//
// 一台服务器出厂后要用注册码激活。页面上给出**机器码**（用户抄下来发给厂家），
// 厂家算出**注册码**回给用户，用户填进来点「注 册」；另有一个「试 用」按钮，
// 可以领一段试用期。
//
//	serverbaseparam.registerflag     0 未注册 / 1 已注册 / 2 试用中 / 3 标准版 / 4 已过期
//	serverbaseparam.registerserial   机器码（页面上只读显示）
//	serverbaseparam.trystartdate     试用起始日（⚠ 旧版页面**没读它**，见下）
//
// # ⚠ 试用天数不是从数据库算的
//
// 旧版 regist_server.php 里读 trystartdate 的那段被整段注释掉了，真正生效的是：
//
//	$files = fopen("serial","r");
//	fgets($files);                       // 跳过第一行 "[system]"
//	$getfile = substr(fgets($files),10); // 第二行 "startdate=YYYY-MM-DD" 去掉前 10 个字符
//	$Days = round((今天 - startdate)/3600/24);
//	if($Days > 5) $Days = 5;
//	$Dayss = 5 - $Days;                  // 页面上显示的「还有 N 天到期」
//
// 也就是**试用期固定 5 天**，起算日在磁盘上的 `serial` 文件里，不在库里。
// 新版照这个算，文件路径做成配置项（旧版写死 /var/www/html/ok112/serial）。
//
// # ⚠ 「试用」按钮里有一段自相矛盾的代码
//
// settrydo() 先 fopen("serialtwo","w") 写入今天的日期，紧接着又
// `cp /var/www/html/ok112/serial /var/www/html/ok112/serialtwo -rf`
// 把刚写的内容**覆盖**成 serial 的内容。所以落到磁盘上的其实是 serial 里的日期，
// 写今天那一步等于没做。新版保留「先写今天、再从 serial 覆盖」这个最终结果
// —— 也就是以 serial 为准，并把这处矛盾记在这里。
//
// # 与旧版的两点差异（都是安全上的，不是功能上的）
//
//  1. 鉴权按注册状态开合。旧版 regist_server.php 的登录校验整段被注释掉，
//     do.php 的 act=regist_server / act=settrydo 也不校验会话 —— 任何人、任何时候
//     都能把字符串拼进 shell 命令。
//     新版：auth.Login 里有一条硬规矩「registerflag 不是 1 或 2 就禁止登录」（BR-71），
//     所以没注册时根本登不进来，注册动作不可能锁在登录之后。折中的做法是
//     **只在这几种登不进来的状态下**放行未登录的注册 / 试用请求
//     （见 main.go 的 regGate），一旦注册成功或进入试用期，这几个接口立刻
//     回到 serverpriv 之后。敞开的窗口收窄到「非如此不可」的那一段。
//  2. 旧版是 `$command = "registerserver ".$license_key;` 直接拼进 shell，
//     注册码里带 `;` 就能执行任意命令。新版用 exec.Command 传参，不经过 shell。
//
// # registerflag 的写入权
//
// serverbaseparam.registerflag 在「服务器参数」那一页是只读列、明令禁止写入。
// 这里是**唯一**的例外，也是旧版的写法：注册成功 / 领到试用之后由本包改它，
// 别的地方一律只读。
package register

import (
	"bufio"
	"context"
	"database/sql"
	"errors"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"time"
)

// TrialDays 是试用期天数。旧版写死 5（`if($Days>5) $Days=5; $Dayss=5-$Days;`）。
const TrialDays = 5

// registerflag 的取值。旧版 regist_server.html 里那串 if/else 就是按这几个分支写的。
const (
	FlagNotRegistered = 0 // 服务器没有注册
	FlagRegistered    = 1 // 服务器已注册
	FlagTrial         = 2 // 服务器在试用期
	FlagStandard      = 3 // 服务器是标准版软件
	FlagExpired       = 4 // 服务器已过期
)

var (
	// ErrTried 对应旧版「服务器已试用过」：serialtwo 已经存在。
	ErrTried = errors.New("服务器已试用过")
	// ErrNoTrial 对应旧版试用按钮在这几种状态下只弹一句提示、不请求后台。
	ErrNoTrial = errors.New("当前状态不能领取试用")
	// ErrEmptyKey 对应旧版 checkform() 里那句「请输入注册码」——
	// 旧版在浏览器里拦，服务端不拦；新版两头都拦。
	ErrEmptyKey = errors.New("请输入注册码")
	// ErrKeyTooLong 是新加的上限，旧版没有（它把注册码直接拼进 shell）。
	ErrKeyTooLong = errors.New("注册码过长")
	// ErrCommand 表示注册程序压根没跑起来：没装、路径不对、或者没有执行权限。
	// registerserver 是第三方程序，不随本项目发布 —— 装机时它就得在，
	// 路径由 register.command 指定。这不是用户填错了注册码，得把原因说清楚。
	ErrCommand = errors.New("注册程序无法执行")
)

// Options 是这一页要用到的外部依赖，全部来自配置。
type Options struct {
	// Command 注册用的外部命令。旧版是 `registerserver <注册码>`，
	// 取标准输出第一行判定 success / failed / expired。
	Command string
	// SerialFile 记录试用起算日的文件（旧版 /var/www/html/ok112/serial）。
	SerialFile string
	// TrialFile 领过试用之后留下的标记文件（旧版 .../serialtwo）。
	TrialFile string
}

type Service struct {
	db  *sql.DB
	opt Options
}

func New(db *sql.DB, opt Options) *Service {
	if opt.Command == "" {
		opt.Command = "registerserver"
	}
	return &Service{db: db, opt: opt}
}

// Status 是这一页的全部只读信息。
type Status struct {
	Flag int `json:"registerflag"`
	// StatusText 是页面上那行大字，逐字取自旧版 language/chinese.php 的 regist_server.*
	StatusText string `json:"statusText"`
	// Registered 只在 flag = 1 时为真，页面据此把状态文字显示成蓝色而不是红色。
	Registered bool `json:"registered"`
	// MachineCode 机器码。⚠ 只有 /api/register 会带（未注册时该接口免登录，
	// 已注册后要 serverpriv）；公开的 /api/register/status 里恒为空串。
	MachineCode string `json:"machineCode"`
	// TrialDaysLeft 是旧版的 $getDays：5 - (今天 - serial 里的起算日)，
	// 上限 5，可以是负数（负数表示试用期已经过完）。
	TrialDaysLeft int `json:"trialDaysLeft"`
	// TrialNotice 是 flag=2 且还没过期时那行红字，
	// 「服务器还有 N 天到期，到期后服务器不能使用，为了您的正常使用，请及时注册！」
	TrialNotice string `json:"trialNotice"`
	// TrialUsed 表示试用标记文件已经存在（再点「试用」会被拒）。
	TrialUsed bool `json:"trialUsed"`
	// CanTrial 表示「试用」按钮点下去会真的去后台领试用。
	// 旧版 settrydo(regist) 在 flag 为 1/2/3/4 时只弹一句提示，只有 0 才发请求。
	CanTrial bool `json:"canTrial"`
	// SerialFileMissing 表示 serial 文件读不到 —— 试用天数就无从算起。
	SerialFileMissing bool `json:"serialFileMissing"`
	// LoginBlocked 表示这个状态下**登不进系统**（auth.Login 的 BR-71：
	// registerflag 不是 1 或 2 就禁止登录）。注册页据此知道自己是不是
	// 「登录前」的那一版，路由也据此决定要不要放行未登录请求。
	LoginBlocked bool `json:"loginBlocked"`
}

// statusText 复刻旧版 registed_user() 里那串 if/else。
//
//	1                    服务器已注册
//	2 且剩余天数 >= 0     服务器在试用期
//	2 且剩余天数 <  0     服务器没有注册
//	3                    服务器是标准版软件
//	4                    服务器已过期
//	其它（含 0）          服务器没有注册
func statusText(flag, daysLeft int) string {
	switch {
	case flag == FlagRegistered:
		return "服务器已注册"
	case flag == FlagTrial && daysLeft >= 0:
		return "服务器在试用期"
	case flag == FlagTrial:
		return "服务器没有注册"
	case flag == FlagStandard:
		return "服务器是标准版软件"
	case flag == FlagExpired:
		return "服务器已过期"
	}
	return "服务器没有注册"
}

// trialAlert 是旧版 settrydo() 里那几句 alert：这些状态下按钮不发请求，只提示。
func trialAlert(flag int) string {
	switch flag {
	case FlagRegistered:
		return "服务器已注册"
	case FlagTrial:
		return "服务器在试用期"
	case FlagStandard:
		return "服务器是标准版软件"
	case FlagExpired:
		return "服务器已过期"
	}
	return ""
}

// Status 读注册状态。withMachineCode = false 时不返回机器码（登录页用的公开接口）。
func (s *Service) Status(ctx context.Context, withMachineCode bool) (*Status, error) {
	out := &Status{}
	var serial sql.NullString
	err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(registerflag,0), registerserial FROM serverbaseparam ORDER BY id LIMIT 1`).
		Scan(&out.Flag, &serial)
	if err != nil && !errors.Is(err, sql.ErrNoRows) {
		return nil, fmt.Errorf("查询注册状态: %w", err)
	}
	if withMachineCode {
		out.MachineCode = strings.TrimSpace(serial.String)
	}

	days, missing := s.trialDaysLeft()
	out.TrialDaysLeft = days
	out.SerialFileMissing = missing
	out.StatusText = statusText(out.Flag, days)
	out.Registered = out.Flag == FlagRegistered
	if out.Flag == FlagTrial && days >= 0 {
		out.TrialNotice = fmt.Sprintf("服务器还有%d天到期，到期后服务器不能使用，为了您的正常使用，请及时注册！", days)
	}
	if s.opt.TrialFile != "" {
		if _, err := os.Stat(s.opt.TrialFile); err == nil {
			out.TrialUsed = true
		}
	}
	out.CanTrial = trialAlert(out.Flag) == ""
	// 与 auth.Login 里那条判断保持一致：只有 1（已注册）和 2（试用中）能登录
	out.LoginBlocked = out.Flag != FlagRegistered && out.Flag != FlagTrial
	return out, nil
}

// trialDaysLeft 复刻旧版那段读文件算天数的逻辑。
//
//	跳过第一行 "[system]"，第二行形如 "startdate=2016-08-31"，
//	去掉前 10 个字符就是日期；天数 = 5 - (今天 - 起算日)，其中 (今天-起算日) 上限 5。
//
// 读不到文件时返回 (0, true) —— 旧版这里会 PHP 报错，新版把它当成「算不出来」。
func (s *Service) trialDaysLeft() (int, bool) {
	if s.opt.SerialFile == "" {
		return 0, true
	}
	f, err := os.Open(s.opt.SerialFile)
	if err != nil {
		return 0, true
	}
	defer f.Close()

	sc := bufio.NewScanner(f)
	if !sc.Scan() { // 第一行 "[system]"
		return 0, true
	}
	if !sc.Scan() { // 第二行 "startdate=YYYY-MM-DD"
		return 0, true
	}
	line := strings.TrimSpace(sc.Text())
	// 旧版是 substr(..., 10)，也就是硬去掉 "startdate=" 这 10 个字符
	if len(line) <= 10 {
		return 0, true
	}
	start, err := time.ParseInLocation("2006-01-02", strings.TrimSpace(line[10:]), time.Local)
	if err != nil {
		return 0, true
	}
	now := time.Now()
	today := time.Date(now.Year(), now.Month(), now.Day(), 0, 0, 0, 0, time.Local)
	used := int(today.Sub(start).Hours() / 24)
	if used > TrialDays {
		used = TrialDays
	}
	return TrialDays - used, false
}

// RegisterResult 是「注 册」的结果。
type RegisterResult struct {
	// Outcome 是外部命令标准输出的第一行：success / failed / expired，
	// 其它内容按 failed 处理（旧版三个分支都不匹配时什么也不做，等于卡住）。
	Outcome string `json:"outcome"`
	OK      bool   `json:"ok"`
	Message string `json:"message"`
}

// Register 执行注册。
//
// 旧版：
//
//	$command = "registerserver ".$license_key;
//	@exec($command, $output_info, $last_line);
//	... 判定 $output_info[0] 是 failed / expired / success
//	success 时 UPDATE serverbaseparam SET registerflag='1' WHERE id='1'，并让服务器重启
//
// ⚠ 旧版在判定之前**先无条件**弹「发送成功,服务器重启！」并发了一次重启命令，
// 注册失败也会重启一次。那是调试残留，新版只在成功时重启。
func (s *Service) Register(ctx context.Context, licenseKey string) (*RegisterResult, error) {
	licenseKey = strings.TrimSpace(licenseKey)
	if licenseKey == "" {
		return nil, ErrEmptyKey
	}
	if len(licenseKey) > 128 {
		return nil, fmt.Errorf("%w：%d 字符，上限 128", ErrKeyTooLong, len(licenseKey))
	}

	// ⚠ 用 exec.Command 传参，不经过 shell —— 旧版是字符串拼接，
	//   注册码里带 `;` 就能执行任意命令。
	cmd := exec.CommandContext(ctx, s.opt.Command, licenseKey)
	raw, err := cmd.Output()
	if err != nil {
		var ee *exec.ExitError
		if errors.As(err, &ee) {
			// 命令跑起来了但退出码非 0：仍按它的输出判定，与旧版 @exec 的行为一致
			raw = ee.Stderr
		} else {
			// 没跑起来（ENOENT / EACCES 之类），与「跑起来了但说 failed」是两回事
			return nil, fmt.Errorf("%w：%s（%v）", ErrCommand, s.opt.Command, err)
		}
	}
	outcome := firstLine(string(raw))

	res := &RegisterResult{Outcome: outcome}
	switch outcome {
	case "success":
		if _, err := s.db.ExecContext(ctx,
			`UPDATE serverbaseparam SET registerflag = ? WHERE id = 1`, FlagRegistered); err != nil {
			return nil, fmt.Errorf("写入注册状态: %w", err)
		}
		res.OK = true
		res.Message = "注册成功、服务器已重启"
	case "expired":
		res.Message = "已过期，请重新获取注册码"
	default:
		// 旧版只认 failed，其它输出（包括空）它一个分支都不进、页面停在原地。
		// 新版一律按失败处理，并把实际输出带回去，免得「点了没反应」。
		res.Message = "注册错误、请确认注册码、再重新输入"
		if outcome != "" && outcome != "failed" {
			res.Message += fmt.Sprintf("（注册程序返回：%s）", outcome)
		}
	}
	return res, nil
}

func firstLine(s string) string {
	s = strings.ReplaceAll(s, "\r\n", "\n")
	if i := strings.IndexByte(s, '\n'); i >= 0 {
		s = s[:i]
	}
	return strings.TrimSpace(s)
}

// Trial 领取试用期。
//
// 旧版 settrydo()：
//
//	serialtwo 已存在 → 「服务器已试用过」，不做任何事
//	否则 → 写 serialtwo（[system] + startdate=今天）、chmod 777、
//	       再 `cp serial serialtwo -rf`（把刚写的覆盖掉）、chmod 777、重启
//
// 新版保留最终效果：以 serial 的内容为准写出 serialtwo。
// serial 读不到时退回「写今天」，至少让试用期能起算。
// 不做 chmod 777 —— 那是旧版为了让别的进程能改它，新版没有这个需要。
func (s *Service) Trial(ctx context.Context) (*Status, error) {
	if s.opt.TrialFile == "" {
		return nil, fmt.Errorf("未配置试用标记文件路径（register.trial_file）")
	}
	if _, err := os.Stat(s.opt.TrialFile); err == nil {
		return nil, ErrTried
	}
	cur, err := s.Status(ctx, false)
	if err != nil {
		return nil, err
	}
	if !cur.CanTrial {
		return nil, fmt.Errorf("%w：%s", ErrNoTrial, trialAlert(cur.Flag))
	}

	body, err := os.ReadFile(s.opt.SerialFile)
	if err != nil {
		// serial 读不到就按今天起算 —— 旧版那一步写的正是这个内容
		body = []byte(fmt.Sprintf("[system]\nstartdate=%s\n", time.Now().Format("2006-01-02")))
	}
	if dir := filepath.Dir(s.opt.TrialFile); dir != "" && dir != "." {
		if err := os.MkdirAll(dir, 0o755); err != nil {
			return nil, fmt.Errorf("创建试用标记目录: %w", err)
		}
	}
	if err := os.WriteFile(s.opt.TrialFile, body, 0o644); err != nil {
		return nil, fmt.Errorf("写入试用标记文件: %w", err)
	}
	// 试用期开始 = registerflag 置 2，页面上才会显示「服务器在试用期」
	if _, err := s.db.ExecContext(ctx,
		`UPDATE serverbaseparam SET registerflag = ? WHERE id = 1`, FlagTrial); err != nil {
		return nil, fmt.Errorf("写入试用状态: %w", err)
	}
	return s.Status(ctx, false)
}
