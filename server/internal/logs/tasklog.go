package logs

import (
	"bytes"
	"context"
	"database/sql"
	"encoding/hex"
	"fmt"
	"log"
	"os"
	"path/filepath"
	"regexp"
	"sort"
	"strings"
	"time"
	"unicode/utf8"
)

// 任务日志 F-55。
//
// # 它不在数据库里
//
// 任务日志是后台 C 服务写在 datelog/ 目录下的文件，每天一个
// logYYYY-MM-DD.html（BR-251）。现网 /opt/apps/a9000/html/ok112/datelog
// 有 414 个文件、5.4MB。
//
// schema 里那两张 logtask / logmedialist 表**业务代码从不读写**，
// 只出现在恢复出厂的清空例程里，新版同样不用（契约 C-41）。
//
// # 旧版清空任务日志的三个问题
//
//   - D-205 遍历目录 unlink() 所有文件：不校验会话、不校验权限、无二次确认；
//     不递归子目录；也不判断文件是否正被后台写入 —— 删掉当天的文件，
//     后台服务的文件句柄还在，会继续往一个已被 unlink 的 inode 里写，日志静默丢失。
//   - D-206 路径来自常量但没有 realpath 断言。
//   - D-207 无分页无筛选，日志文件一多页面就卡死。

// TaskLogFile 是一个任务日志文件。
type TaskLogFile struct {
	Name    string `json:"name"`
	Date    string `json:"date"`
	Size    int64  `json:"size"`
	ModTime string `json:"modTime"`
	// Today 表示这是今天的文件，后台服务很可能正在往里写，删除时要跳过（BR-252）。
	Today bool `json:"today"`
	// Writable 表示当前进程有没有希望删掉它。
	//
	// POSIX 下能不能 unlink 取决于**父目录**的写权限，不是文件自身的属主 ——
	// 现网 datelog 里有个别文件是 root 写的，但只要目录我们能写就删得掉
	// （除非目录带 sticky 位）。所以这里填的是「目录可写」这一个判断，
	// 真正的结果以删除时的返回为准，删不掉的会进 Failed 列表。
	Writable bool `json:"writable"`
}

// reTaskLog 限定文件名形态。同时充当路径穿越的第一道闸门：
// 名字里出现 / 或 .. 根本匹配不上这个正则。
var reTaskLog = regexp.MustCompile(`^log(\d{4}-\d{2}-\d{2})\.html$`)

// ErrTaskLogDisabled 表示没有配置任务日志目录。
var ErrTaskLogDisabled = fmt.Errorf("未配置任务日志目录，请在 config.yaml 的 logs.task_dir 里指定")

type TaskLogService struct {
	dir string
	loc *time.Location
	// db 只用来做 GBK → UTF-8 的转码，见 decodeMixed。
	db *sql.DB
}

func NewTaskLog(db *sql.DB, dir string) *TaskLogService {
	return &TaskLogService{dir: dir, loc: time.FixedZone("CST", 8*3600), db: db}
}

func (t *TaskLogService) today() string {
	return time.Now().In(t.loc).Format("2006-01-02")
}

// root 解析并断言目录存在，返回规范化后的绝对路径（修 D-206）。
func (t *TaskLogService) root() (string, error) {
	if strings.TrimSpace(t.dir) == "" {
		return "", ErrTaskLogDisabled
	}
	abs, err := filepath.Abs(t.dir)
	if err != nil {
		return "", fmt.Errorf("解析任务日志目录: %w", err)
	}
	st, err := os.Stat(abs)
	if err != nil {
		return "", fmt.Errorf("任务日志目录不可访问: %w", err)
	}
	if !st.IsDir() {
		return "", fmt.Errorf("任务日志目录不是一个目录: %s", abs)
	}
	return abs, nil
}

// resolve 把一个文件名解析成目录内的绝对路径，并断言它确实落在目录内。
//
// 两道闸门：正则限定文件名形态、解析后再断言前缀。
// 只留一道都不够 —— 正则可能哪天被放宽，前缀断言可能被符号链接绕过。
func (t *TaskLogService) resolve(name string) (string, error) {
	root, err := t.root()
	if err != nil {
		return "", err
	}
	if !reTaskLog.MatchString(name) {
		return "", fmt.Errorf("任务日志文件名不合法")
	}
	p := filepath.Join(root, name)
	real, err := filepath.EvalSymlinks(p)
	if err != nil {
		return "", fmt.Errorf("任务日志文件不存在")
	}
	realRoot, err := filepath.EvalSymlinks(root)
	if err != nil {
		return "", fmt.Errorf("解析任务日志目录: %w", err)
	}
	if real != realRoot && !strings.HasPrefix(real, realRoot+string(os.PathSeparator)) {
		return "", fmt.Errorf("任务日志文件不在允许的目录内")
	}
	return real, nil
}

type TaskLogList struct {
	Dir       string        `json:"dir"`
	Files     []TaskLogFile `json:"files"`
	Total     int           `json:"total"`
	TotalSize int64         `json:"totalSize"`
	Today     string        `json:"today"`
	// DirWritable 为 false 时删除接口一定失败，界面据此把按钮禁掉并说明原因。
	DirWritable bool `json:"dirWritable"`
}

// dirWritable 实探目录能不能写：建一个临时文件再删掉。
//
// 比读 mode 位靠谱 —— mode 位要配合 uid/gid 才能判断，
// 还有 ACL、只读挂载、SELinux 这些读 mode 根本看不出来的情况。
func dirWritable(dir string) bool {
	f, err := os.CreateTemp(dir, ".htweb-probe-*")
	if err != nil {
		return false
	}
	name := f.Name()
	_ = f.Close()
	_ = os.Remove(name)
	return true
}

// ListFiles 列出任务日志文件，按日期倒序。
//
// 只列本目录下形如 logYYYY-MM-DD.html 的普通文件：
// 不递归子目录（旧版也不递归，但旧版是「删的时候不递归」造成残留，
// 这里是「列和删口径一致」，不会出现列不到却被删掉的情况）。
func (t *TaskLogService) ListFiles(ctx context.Context, from, to string) (*TaskLogList, error) {
	root, err := t.root()
	if err != nil {
		return nil, err
	}
	ents, err := os.ReadDir(root)
	if err != nil {
		return nil, fmt.Errorf("读取任务日志目录: %w", err)
	}
	writable := dirWritable(root)
	out := &TaskLogList{Dir: root, Files: []TaskLogFile{}, Today: t.today(), DirWritable: writable}
	for _, e := range ents {
		if e.IsDir() {
			continue
		}
		m := reTaskLog.FindStringSubmatch(e.Name())
		if m == nil {
			continue
		}
		date := m[1]
		if from != "" && date < from {
			continue
		}
		if to != "" && date > to {
			continue
		}
		info, err := e.Info()
		if err != nil {
			continue
		}
		f := TaskLogFile{
			Name:     e.Name(),
			Date:     date,
			Size:     info.Size(),
			ModTime:  info.ModTime().In(t.loc).Format("2006-01-02 15:04:05"),
			Today:    date == out.Today,
			Writable: writable,
		}
		out.Files = append(out.Files, f)
		out.TotalSize += f.Size
	}
	sort.Slice(out.Files, func(i, j int) bool { return out.Files[i].Date > out.Files[j].Date })
	out.Total = len(out.Files)
	return out, nil
}

// maxReadBytes 单次读取的上限。现网最大的一个文件 78KB，
// 但没有任何机制保证它不会变大，读之前必须有个上限。
const maxReadBytes = 2 << 20 // 2MB

type TaskLogContent struct {
	Name      string `json:"name"`
	Size      int64  `json:"size"`
	Truncated bool   `json:"truncated"`
	Content   string `json:"content"`
	// GBKLines 是被当成 GBK 转码过的行数，0 表示整个文件都是 UTF-8。
	// 纯诊断用：这个数不为 0 说明后台服务在这个文件里混用了两种编码。
	GBKLines int `json:"gbkLines"`
}

// decodeMixed 把任务日志的原始字节转成 UTF-8。
//
// # 这些文件是混合编码的
//
// 现网实测 log2026-05-08.html：表头那行是 **GBK**
// （「时间」= CA B1 BC E4），而事件行里的「终端上线」是 **UTF-8**。
// 也就是说后台 C 服务在同一个文件里用了两种编码 —— 这是既成事实的数据，
// 不是我们能改的。旧 setlog.php 直接把文件塞进 HTML 页面输出，
// 浏览器按页面 charset 解析，所以那边一样有一半是乱码，只是没人深究。
//
// # 为什么按「行」判断而不是按字节
//
// GBK 的双字节序列可能碰巧构成合法的 UTF-8：上面那个 CA B1 解码成 UTF-8
// 就是 U+02B1（ʱ），一个完全合法但毫无意义的字符。所以「这段字节是不是合法
// UTF-8」在字节粒度上不足以判别编码。
// 按行判断稳得多：一行里只要出现一个非法 UTF-8 字节，整行就按 GBK 解，
// 那个碰巧合法的 CA B1 也就跟着被正确还原成「时」。
//
// # 为什么用 MySQL 转码
//
// 转 GBK 需要一张映射表。引入 golang.org/x/text 意味着给这个锁死依赖的
// 部署环境新增一个模块（构建机也不一定能联网拉取），而 MySQL 本来就带着
// 全套字符集。终端模块的 GBK 校验用的也是同一个办法，口径一致。
func (t *TaskLogService) decodeMixed(ctx context.Context, b []byte) (string, int) {
	lines := bytes.Split(b, []byte("\n"))
	bad := []int{}
	for i, ln := range lines {
		if !utf8.Valid(ln) {
			bad = append(bad, i)
		}
	}
	if len(bad) == 0 || t.db == nil {
		return string(b), 0
	}
	// 分批转，一次查询里塞太多 CONVERT 列没有意义
	const batch = 100
	converted := 0
	for start := 0; start < len(bad); start += batch {
		end := start + batch
		if end > len(bad) {
			end = len(bad)
		}
		idx := bad[start:end]

		// 这串字节必须以**十六进制字面量**的形式进 SQL，不能走占位符。
		//
		// 走占位符踩过两个坑，都是现网实测出来的：
		//   1. `_binary ?` —— _binary 是字面量引导符，贴在占位符前面直接语法报错
		//      （Error 1064）。
		//   2. `CAST(? AS BINARY)` —— 语法过了，但结果全是 ???。
		//      因为连接字符集是 utf8，参数在**进入服务器时**就被当成 utf8 文本，
		//      其中的非法 utf8 字节在那一刻已经被替换成 '?'，
		//      轮不到 CAST 去剥字符集。
		//
		// 十六进制字面量不经过字符集解释，字节原样进到服务器。
		// 拼接在这里也是安全的：hex 编码的输出只有 [0-9a-f]，不可能带出引号或分号。
		cols := make([]string, 0, len(idx))
		use := make([]int, 0, len(idx))
		for _, li := range idx {
			if len(lines[li]) == 0 {
				continue // 0x 不是合法字面量
			}
			cols = append(cols, "CONVERT(_binary 0x"+hex.EncodeToString(lines[li])+" USING gbk)")
			use = append(use, li)
		}
		if len(cols) == 0 {
			continue
		}
		dst := make([]sql.RawBytes, len(cols))
		ptrs := make([]interface{}, len(cols))
		for i := range dst {
			ptrs[i] = &dst[i]
		}
		rows, err := t.db.QueryContext(ctx, "SELECT "+strings.Join(cols, ","))
		if err != nil {
			log.Printf("tasklog: GBK 转码失败: %v", err)
			break
		}
		if rows.Next() {
			if err := rows.Scan(ptrs...); err == nil {
				for i, li := range use {
					lines[li] = append([]byte(nil), dst[i]...)
					converted++
				}
			}
		}
		rows.Close()
	}
	return string(bytes.Join(lines, []byte("\n"))), converted
}

// ReadFile 读一个任务日志文件。
//
// 内容是后台服务写的 HTML 片段，原样返回由前端转义后展示 ——
// 绝不能让前端 v-html 直接渲染：这些字节来自 C 服务，
// 而 C 服务把任务名、终端名一类的用户可控内容直接拼进去，等于存储型 XSS。
func (t *TaskLogService) ReadFile(ctx context.Context, name string, tailBytes int64) (*TaskLogContent, error) {
	p, err := t.resolve(name)
	if err != nil {
		return nil, err
	}
	st, err := os.Stat(p)
	if err != nil {
		return nil, fmt.Errorf("任务日志文件不可访问: %w", err)
	}
	out := &TaskLogContent{Name: name, Size: st.Size()}

	limit := int64(maxReadBytes)
	if tailBytes > 0 && tailBytes < limit {
		limit = tailBytes
	}
	f, err := os.Open(p)
	if err != nil {
		return nil, fmt.Errorf("打开任务日志文件: %w", err)
	}
	defer f.Close()

	offset := int64(0)
	if st.Size() > limit {
		offset = st.Size() - limit
		out.Truncated = true
	}
	buf := make([]byte, limit)
	n, err := f.ReadAt(buf, offset)
	if err != nil && n == 0 {
		return nil, fmt.Errorf("读取任务日志文件: %w", err)
	}
	out.Content, out.GBKLines = t.decodeMixed(ctx, buf[:n])
	return out, nil
}

// TaskLogDeletePreview 是删除前的影响面。
type TaskLogDeletePreview struct {
	Files []TaskLogFile `json:"files"`
	Count int           `json:"count"`
	Size  int64         `json:"size"`
	// SkippedToday 是被跳过的当天文件；SkippedReadonly 是当前进程删不掉的文件。
	SkippedToday    []string `json:"skippedToday"`
	SkippedReadonly []string `json:"skippedReadonly"`
}

// PreviewDelete 算出「按这个条件删会删掉哪些文件」，纯只读。
func (t *TaskLogService) PreviewDelete(ctx context.Context, in TaskLogDeleteInput) (*TaskLogDeletePreview, error) {
	all, err := t.ListFiles(ctx, "", "")
	if err != nil {
		return nil, err
	}
	out := &TaskLogDeletePreview{Files: []TaskLogFile{},
		SkippedToday: []string{}, SkippedReadonly: []string{}}

	for _, f := range all.Files {
		hit, err := in.matches(f, all.Today)
		if err != nil {
			return nil, err
		}
		if !hit {
			continue
		}
		// 当天的文件后台服务很可能正开着句柄在写，删了会静默丢日志（BR-252）
		if f.Today {
			out.SkippedToday = append(out.SkippedToday, f.Name)
			continue
		}
		if !f.Writable {
			out.SkippedReadonly = append(out.SkippedReadonly, f.Name)
			continue
		}
		out.Files = append(out.Files, f)
		out.Size += f.Size
	}
	out.Count = len(out.Files)
	return out, nil
}

type TaskLogDeleteInput struct {
	Mode       ClearMode
	BeforeDate string
	KeepDays   int
}

func (in TaskLogDeleteInput) matches(f TaskLogFile, today string) (bool, error) {
	switch in.Mode {
	case ClearAll:
		return true, nil
	case ClearBeforeDate:
		if !isDate(in.BeforeDate) {
			return false, fmt.Errorf("日期格式不正确，应为 YYYY-MM-DD")
		}
		return f.Date < in.BeforeDate, nil
	case ClearKeepDays:
		if in.KeepDays < 1 || in.KeepDays > 3650 {
			return false, fmt.Errorf("保留天数必须在 1 ~ 3650 之间")
		}
		t0, err := time.Parse("2006-01-02", today)
		if err != nil {
			return false, fmt.Errorf("解析当前日期: %w", err)
		}
		return f.Date < t0.AddDate(0, 0, -in.KeepDays).Format("2006-01-02"), nil
	default:
		return false, fmt.Errorf("清理方式只能是 all / beforeDate / keepDays")
	}
}

type TaskLogDeleteResult struct {
	Deleted         []string `json:"deleted"`
	FreedBytes      int64    `json:"freedBytes"`
	SkippedToday    []string `json:"skippedToday"`
	SkippedReadonly []string `json:"skippedReadonly"`
	Failed          []string `json:"failed"`
}

// Delete 按条件删除任务日志文件。
//
// 单个文件删除失败不中断整体：进 Failed 列表如实回报。
// 旧版是遍历目录直接 unlink，失败与否根本不看。
func (t *TaskLogService) Delete(ctx context.Context, in TaskLogDeleteInput) (*TaskLogDeleteResult, error) {
	pv, err := t.PreviewDelete(ctx, in)
	if err != nil {
		return nil, err
	}
	out := &TaskLogDeleteResult{
		Deleted: []string{}, Failed: []string{},
		SkippedToday: pv.SkippedToday, SkippedReadonly: pv.SkippedReadonly,
	}
	for _, f := range pv.Files {
		p, err := t.resolve(f.Name)
		if err != nil {
			out.Failed = append(out.Failed, f.Name)
			continue
		}
		if err := os.Remove(p); err != nil {
			out.Failed = append(out.Failed, f.Name)
			continue
		}
		out.Deleted = append(out.Deleted, f.Name)
		out.FreedBytes += f.Size
	}
	return out, nil
}
