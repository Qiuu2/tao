package logs

import (
	"context"
	"encoding/json"
	"fmt"
	"htweb/internal/i18n"
	"log"
	"os"
	"path/filepath"
	"strings"
	"sync"
	"time"
)

// 日志保留期。
//
// # 做什么
//
// 操作日志（log 表）只保留最近一段时间，超期的按天滚掉。
//
//	1个月（默认） / 3个月 / 半年 / 1年
//
// 「按天滚动」的意思是：清理任务每天跑一次，每次把**落在保留窗口之外的那一整天**
// 清掉。所以稳态下每天掉一天，日志量维持在一个固定的天数上，不会无限涨，
// 也不会某一天突然删掉一大批。
//
// # 为什么设置存文件不存库
//
// 零 DDL 是这套系统与旧库共存的前提（R1 红线），不能给 log 表加列、也不能建新表；
// 现有的空表又都可能被后台 C 服务扫描，塞进去有触发误广播的风险。
// 所以跟看板状态一样落成一个 JSON 文件（见 config.LogSettingsFile）。
//
// # 清理的边界
//
// 操作日志按 `time` 列删。删之前**先写一行审计**说明这次滚掉了多少条 ——
// 否则「日志少了一截」这件事本身没有痕迹。
//
// ⚠ 这里**只清 log 表**。后台 C 服务写在 datelog/ 下的那些 logYYYY-MM-DD.html
// 不归这里管，也不归新版管：任务日志这一页已经撤掉，随之撤掉的还有这个进程
// 删那批文件的能力 —— 界面上看不见的东西不该在后台被悄悄删掉。

// RetentionOption 是保留期的取值。存文件里的就是这几个字符串，
// 不存天数 —— 将来要调整某一档对应多少天，改代码即可，老配置不用迁移。
type RetentionOption string

const (
	Retain1Month  RetentionOption = "1m"
	Retain3Months RetentionOption = "3m"
	Retain6Months RetentionOption = "6m"
	Retain1Year   RetentionOption = "1y"
)

// DefaultRetention 是没配过时的默认值：保留 1 个月。
const DefaultRetention = Retain1Month

// retentionSpec 是一档保留期的说明。
type retentionSpec struct {
	Option RetentionOption
	Label  string
	// Months 用来做日期减法。用「减 N 个月」而不是「减 N×30 天」——
	// 界面上写的是「1个月」，用户预期的就是自然月，2 月和 8 月不该一样长。
	Months int
}

// retentionSpecs 是全部可选项，顺序即界面上的顺序。
var retentionSpecs = []retentionSpec{
	{Retain1Month, "1 个月", 1},
	{Retain3Months, "3 个月", 3},
	{Retain6Months, "半年", 6},
	{Retain1Year, "1 年", 12},
}

func specOf(opt RetentionOption) retentionSpec {
	for _, s := range retentionSpecs {
		if s.Option == opt {
			return s
		}
	}
	return retentionSpecs[0]
}

// RetentionChoice 是给界面用的一个选项。
type RetentionChoice struct {
	Value RetentionOption `json:"value"`
	Label string          `json:"label"`
}

// RetentionSettings 是这一页读到的完整状态。
type RetentionSettings struct {
	Option RetentionOption `json:"option"`
	Label  string          `json:"label"`
	// CutoffDate 是当前设置下的保留边界（YYYY-MM-DD），这一天**之前**的会被滚掉。
	CutoffDate string            `json:"cutoffDate"`
	Choices    []RetentionChoice `json:"choices"`
	// LastRunAt 上一次滚动清理的时间，空串表示这个进程起来之后还没跑过。
	LastRunAt string `json:"lastRunAt"`
	// LastResult 上一次的结果描述，界面上直接显示。
	LastResult string `json:"lastResult"`
}

// PurgeResult 是一次滚动清理的结果。
type PurgeResult struct {
	// Cutoff 这一天之前（不含这一天）的都被清掉了。
	Cutoff string `json:"cutoff"`
	// OperationRows 删掉的操作日志条数。
	OperationRows int64 `json:"operationRows"`
}

// 存文件里的形状。单独一个结构而不是直接存 RetentionSettings，
// 是因为后者带着一堆算出来的字段，不该落盘。
type retentionFile struct {
	Retention RetentionOption `json:"retention"`
}

// RetentionService 管保留期设置与滚动清理。
type RetentionService struct {
	logs *Service
	file string

	mu      sync.RWMutex
	opt     RetentionOption
	lastAt  time.Time
	lastMsg string
}

func NewRetention(logs *Service, file string) *RetentionService {
	r := &RetentionService{logs: logs, file: file, opt: DefaultRetention}
	r.load()
	return r
}

// load 读设置文件。读不到、内容坏了、取值不认识，一律退回默认的 1 个月 ——
// 这一项决定「删多少」，任何拿不准的情况都该往「留得更多」的方向退。
func (r *RetentionService) load() {
	if strings.TrimSpace(r.file) == "" {
		return
	}
	raw, err := os.ReadFile(r.file)
	if err != nil {
		return
	}
	var f retentionFile
	if err := json.Unmarshal(raw, &f); err != nil {
		log.Printf("日志保留期设置文件解析失败，按默认（%s）处理: %v", DefaultRetention, err)
		return
	}
	for _, s := range retentionSpecs {
		if s.Option == f.Retention {
			r.opt = f.Retention
			return
		}
	}
	if f.Retention != "" {
		log.Printf("日志保留期设置里有不认识的取值 %q，按默认（%s）处理", f.Retention, DefaultRetention)
	}
}

// save 原子写：先写 .part 再 rename，避免断电留下半个文件。
func (r *RetentionService) save() error {
	if strings.TrimSpace(r.file) == "" {
		return fmt.Errorf("未配置日志设置文件路径（logs.settings_file）")
	}
	if err := os.MkdirAll(filepath.Dir(r.file), 0o755); err != nil {
		return fmt.Errorf("创建设置目录: %w", err)
	}
	raw, err := json.MarshalIndent(retentionFile{Retention: r.opt}, "", "  ")
	if err != nil {
		return err
	}
	tmp := r.file + ".part"
	if err := os.WriteFile(tmp, raw, 0o644); err != nil {
		return fmt.Errorf("写入日志设置: %w", err)
	}
	if err := os.Rename(tmp, r.file); err != nil {
		_ = os.Remove(tmp)
		return fmt.Errorf("提交日志设置: %w", err)
	}
	return nil
}

// Get 返回当前设置与可选项。
//
// ctx 只为取界面语言：保留期的标签（1 个月 / 半年 …）是直接显示在下拉里的。
func (r *RetentionService) Get(ctx context.Context) RetentionSettings {
	r.mu.RLock()
	opt, at, msg := r.opt, r.lastAt, r.lastMsg
	r.mu.RUnlock()

	sp := specOf(opt)
	out := RetentionSettings{
		Option:     sp.Option,
		Label:      i18n.TC(ctx, sp.Label),
		CutoffDate: cutoffOf(sp, time.Now()).Format("2006-01-02"),
		LastResult: msg,
	}
	for _, s := range retentionSpecs {
		out.Choices = append(out.Choices, RetentionChoice{Value: s.Option, Label: i18n.TC(ctx, s.Label)})
	}
	if !at.IsZero() {
		out.LastRunAt = at.Format("2006-01-02 15:04:05")
	}
	return out
}

// Set 改保留期并落盘，紧接着**立刻滚一次**。
//
// 界面上就是一个下拉加一个「确定」：选完点确定，新的保留期存下来，
// 超期的当场清掉，不用等到明天那一次定时滚动。所以这两件事绑在一个动作里 ——
// 分开做的话会出现「设置存下来了但清理没跑」的中间态，用户看不出来。
//
// 清理失败不回滚设置：设置已经落盘、每天的定时滚动会接着做这件事，
// 把设置退回去反而更费解。失败原因原样带回去，界面上说清楚。
func (r *RetentionService) Set(ctx context.Context, opt RetentionOption, user, ip string) (RetentionSettings, *PurgeResult, error) {
	valid := false
	for _, s := range retentionSpecs {
		if s.Option == opt {
			valid = true
			break
		}
	}
	if !valid {
		return RetentionSettings{}, nil, fmt.Errorf("保留期只能是 1m / 3m / 6m / 1y 之一")
	}
	r.mu.Lock()
	old := r.opt
	r.opt = opt
	err := r.save()
	if err != nil {
		r.opt = old // 落盘失败就别在内存里留一个和文件不一致的值
	}
	r.mu.Unlock()
	if err != nil {
		return RetentionSettings{}, nil, err
	}

	res, perr := r.Purge(ctx, user, ip)
	if perr != nil {
		return r.Get(ctx), nil, fmt.Errorf("保留期已保存，但立即清理失败（明天的定时滚动会重试）: %w", perr)
	}
	return r.Get(ctx), res, nil
}

// cutoffOf 算保留边界：今天往前推 N 个自然月，取那一天的零点。
// 早于这一天的（不含这一天）会被滚掉。
func cutoffOf(sp retentionSpec, now time.Time) time.Time {
	d := now.AddDate(0, -sp.Months, 0)
	return time.Date(d.Year(), d.Month(), d.Day(), 0, 0, 0, 0, now.Location())
}

// Purge 跑一次滚动清理。
//
// user / ip 只用于操作日志那一行审计。定时任务调用时传 "系统" 和空 IP。
func (r *RetentionService) Purge(ctx context.Context, user, ip string) (*PurgeResult, error) {
	r.mu.RLock()
	sp := specOf(r.opt)
	r.mu.RUnlock()

	now := time.Now()
	cutoff := cutoffOf(sp, now)
	res := &PurgeResult{Cutoff: cutoff.Format("2006-01-02")}

	n, err := r.purgeOperationLogs(ctx, cutoff, sp, user, ip)
	if err != nil {
		return nil, err
	}
	res.OperationRows = n

	r.mu.Lock()
	r.lastAt = now
	r.lastMsg = fmt.Sprintf("保留 %s（%s 之前的已清理）：操作日志 %d 条",
		sp.Label, res.Cutoff, res.OperationRows)
	r.mu.Unlock()
	return res, nil
}

// purgeOperationLogs 删 log 表里早于 cutoff 的行。
//
// 顺序是「先写审计、再删」，且删的时候排除掉刚写的那一行：
// 否则「这次滚掉了多少条」这件事本身也会被这次删除带走。
// 与 Service.Clear 同一套做法。
func (r *RetentionService) purgeOperationLogs(
	ctx context.Context, cutoff time.Time, sp retentionSpec, user, ip string) (int64, error) {

	day := cutoff.Format("2006-01-02")
	var n int64
	if err := r.logs.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM log WHERE time < ?", day).Scan(&n); err != nil {
		return 0, fmt.Errorf("统计超期操作日志: %w", err)
	}
	if n == 0 {
		return 0, nil
	}

	tx, err := r.logs.db.BeginTx(ctx, nil)
	if err != nil {
		return 0, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	operate := fmt.Sprintf("日志滚动清理（保留 %s，清掉 %s 之前的 %d 条）", sp.Label, day, n)
	auditID, err := r.logs.rec.WriteTx(ctx, tx, user, operate, ip)
	if err != nil {
		return 0, fmt.Errorf("写滚动清理审计记录: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		"DELETE FROM log WHERE time < ? AND id < ?", day, auditID); err != nil {
		return 0, fmt.Errorf("删除超期操作日志: %w", err)
	}
	if err := tx.Commit(); err != nil {
		return 0, fmt.Errorf("提交事务: %w", err)
	}
	return n, nil
}

// StartDaily 起一个每天跑一次的滚动清理。
//
// 进程刚起来时先跑一次（机器关了几天再开机，落下的那几天要补上），
// 之后每 24 小时一次。ctx 结束就退出。
func (r *RetentionService) StartDaily(ctx context.Context) {
	go func() {
		run := func() {
			res, err := r.Purge(ctx, "系统", "")
			if err != nil {
				log.Printf("日志滚动清理失败: %v", err)
				return
			}
			if res.OperationRows > 0 {
				log.Printf("日志滚动清理：%s 之前的操作日志 %d 条已清理", res.Cutoff, res.OperationRows)
			}
		}
		// 起来先等一会儿再跑：让服务先把端口和数据库连接稳住，
		// 清理这种事不急在启动的头一分钟。
		select {
		case <-ctx.Done():
			return
		case <-time.After(time.Minute):
		}
		run()

		t := time.NewTicker(24 * time.Hour)
		defer t.Stop()
		for {
			select {
			case <-ctx.Done():
				return
			case <-t.C:
				run()
			}
		}
	}()
}
