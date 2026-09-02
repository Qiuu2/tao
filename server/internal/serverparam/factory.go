package serverparam

import (
	"context"
	"database/sql"
	"fmt"
	"os"
	"path/filepath"
	"sort"
	"strings"
)

// 恢复出厂 / 清空数据（F-57，对应旧 do.php 的 delsqldate()）。
//
// # 这是本系统破坏力最大的一个功能
//
// 旧版的问题（D-218）：触发入口 `init_date_msg` 是**移动一个脚本文件然后重启**，
// 真正的清库由外部脚本完成；Web 侧**没有二次确认、没有权限校验**。
//
// 新版：仅超级管理员 + 逐字输入确认文本 + 操作前后写审计 + 全程一个事务。
// 只用 DELETE，不用 TRUNCATE（BR-262），一条 DDL 都不执行。
//
// # 表清单为什么要做成「三分类 + 未知表拦截」
//
// 手册 D-221 指出旧版把表清单硬编码在代码里，新增表时容易遗漏 —— 遗漏的后果是
// 「以为清空了，其实某张表里还留着上一个项目的数据」。
//
// 这里把 89 张表**穷举**成三类：清空 / 按条件保留 / 有意不动。
// 运行前先拿实际库里的表清单来比对，**只要出现一张不认识的表就拒绝执行**。
// 与其漏清一张表，不如让人来决定它属于哪一类。

// clearTables 是要被完全清空的业务表。
// 清单逐字取自旧 delsqldate() 的 DELETE 序列。
var clearTables = []string{
	"alarmarea", "alarmgroupmap", "audiocodectype", "audioformat", "audioserver",
	"belltask", "book_msg", "book_reply", "callgroup", "camer", "camer_alarm",
	"camer_alarmofmedia", "cameramap", "camerofterminal", "centralctrl", "ctrldevice",
	"ctrldevicetype", "ctrlnet", "ctrloftask", "ctrloftermianl", "ctrltask", "employees",
	"holidaytime", "log", "logmedialist", "logtask", "mediaoftask", "offlinemedia",
	"offlinemediaofterminal", "offlinetask", "offlinetaskofterminal", "playbelloftask",
	"powermgrmap", "serverinputtype", "serverplaystream", "servers", "serverspeech",
	"shortcutkeymap", "shortcutkeytask", "terminal", "terminalattrbute", "terminalfunc",
	"terminalgroup", "terminalgrouplist", "terminalkey", "terminalkeymap",
	"terminalkeymaptask", "terminalmaked", "terminalofalarmgroup", "terminalofararmgroup",
	"terminalofcallgroup", "terminalofgroup", "terminaloftask", "ttssentence",
}

// keepTables 是按条件保留的表：清掉不满足条件的行（BR-261）。
var keepTables = []struct {
	Table string
	// Where 是**保留**条件，删除时取它的反面。
	Where string
	Desc  string
}{
	{"book_admin", "id = 1", "保留 admin"},
	{"filefolder", "id <= 9", "保留 6 个顶层媒体库 + 3 个预置子目录"},
	{"filetaskfree", "id = 1", "保留 admin 默认任务分组"},
	{"media", "id <= 1", "保留内置提示音"},
	{"task", "taskid = 70000", "保留系统 reset 任务"},
}

// untouchedTables 是**有意不动**的表。
//
// 旧 delsqldate() 的 DELETE 序列里就没有它们，新版保持一致 ——
// 「和旧系统保持一致」是硬性约束，不能因为「看起来该清」就自作主张扩大范围。
// 但必须让操作者看见这份清单，否则就成了另一种形式的遗漏。
var untouchedTables = map[string]string{
	"serverbaseparam":  "服务器参数，恢复出厂不动它",
	"serverconfig":     "服务器配置，同上",
	"terminaltype":     "终端类型字典（现网 48 行）",
	"terminaltypekey":  "终端类型按键字典",
	"usergroup":        "用户组字典",
	"usertype":         "用户类型字典",
	"usersn":           "用户序列号，旧版清空例程未包含",
	"userterminal":     "用户-终端绑定，旧版清空例程未包含",
	"logincheck":       "登录失败计数，旧版清空例程未包含",
	"enabletask":       "旧版清空例程未包含",
	"audiosource":      "音源字典，旧版清空例程未包含",
	"soundtask":        "旧版清空例程未包含",
	"sounddevice":      "旧版清空例程未包含",
	"soundgroup":       "旧版清空例程未包含",
	"soundgroupinfo":   "旧版清空例程未包含",
	"leddevice":        "旧版清空例程未包含",
	"ledoftask":        "旧版清空例程未包含",
	"ledsentence":      "旧版清空例程未包含",
	"ledtaskfree":      "旧版清空例程未包含",
	"terminalfolder":   "旧版清空例程未包含",
	"terminaloffolder": "旧版清空例程未包含",
	"ttstaskinfo":      "旧版清空例程未包含",
	"ttstext":          "旧版清空例程未包含",
	"tempctrlnet2":     "旧版清空例程未包含",
	"ai_device":        "AI 相关，旧版清空例程未包含",
	"ai_devicedemo":    "AI 相关，旧版清空例程未包含",
	"ai_people":        "AI 相关，旧版清空例程未包含",
	"ai_timetts":       "AI 相关，旧版清空例程未包含",
	"TKdeviceinfo":     "铁路接口表，旧版清空例程未包含",
	"TKstationinfo":    "铁路接口表，旧版清空例程未包含",
}

// FactoryPreview 是恢复出厂的影响面预演，纯只读。
type FactoryPreview struct {
	ClearTables []TableImpact `json:"clearTables"`
	KeepTables  []TableImpact `json:"keepTables"`
	Untouched   []TableImpact `json:"untouchedTables"`
	// UnknownTables 是既不在清空清单、也不在保留/不动清单里的表。
	// 只要它非空，恢复出厂就拒绝执行。
	UnknownTables []string `json:"unknownTables"`
	TotalDelete   int      `json:"totalDeleteRows"`
	MediaFiles    int      `json:"mediaFiles"`
	MediaBytes    int64    `json:"mediaBytes"`
	Executable    bool     `json:"executable"`
	Blocker       string   `json:"blocker"`
}

type TableImpact struct {
	Table  string `json:"table"`
	Rows   int    `json:"rows"`
	Delete int    `json:"deleteRows"`
	Note   string `json:"note"`
}

// PreviewFactoryReset 统计恢复出厂会删掉什么。只读，不改任何数据。
func (s *Service) PreviewFactoryReset(ctx context.Context, mediaDir string) (*FactoryPreview, error) {
	live, err := s.liveTables(ctx)
	if err != nil {
		return nil, err
	}
	out := &FactoryPreview{
		ClearTables: []TableImpact{}, KeepTables: []TableImpact{},
		Untouched: []TableImpact{}, UnknownTables: []string{},
	}

	known := map[string]bool{}
	for _, t := range clearTables {
		known[t] = true
	}
	for _, k := range keepTables {
		known[k.Table] = true
	}
	for t := range untouchedTables {
		known[t] = true
	}
	for _, t := range live {
		if !known[t] {
			out.UnknownTables = append(out.UnknownTables, t)
		}
	}
	sort.Strings(out.UnknownTables)

	liveSet := map[string]bool{}
	for _, t := range live {
		liveSet[t] = true
	}

	for _, t := range clearTables {
		if !liveSet[t] {
			continue
		}
		n, err := s.count(ctx, t, "")
		if err != nil {
			return nil, err
		}
		out.ClearTables = append(out.ClearTables, TableImpact{Table: t, Rows: n, Delete: n})
		out.TotalDelete += n
	}
	for _, k := range keepTables {
		if !liveSet[k.Table] {
			continue
		}
		total, err := s.count(ctx, k.Table, "")
		if err != nil {
			return nil, err
		}
		del, err := s.count(ctx, k.Table, "NOT ("+k.Where+")")
		if err != nil {
			return nil, err
		}
		out.KeepTables = append(out.KeepTables, TableImpact{
			Table: k.Table, Rows: total, Delete: del, Note: k.Desc})
		out.TotalDelete += del
	}
	for t, note := range untouchedTables {
		if !liveSet[t] {
			continue
		}
		n, err := s.count(ctx, t, "")
		if err != nil {
			return nil, err
		}
		out.Untouched = append(out.Untouched, TableImpact{Table: t, Rows: n, Note: note})
	}
	sort.Slice(out.Untouched, func(i, j int) bool { return out.Untouched[i].Table < out.Untouched[j].Table })

	// 媒体物理文件（D-217：旧版清空 media 表但几十 GB 的 mp3 全部残留）
	if mediaDir != "" {
		n, size := countMediaFiles(mediaDir)
		out.MediaFiles, out.MediaBytes = n, size
	}

	switch {
	case len(out.UnknownTables) > 0:
		out.Blocker = fmt.Sprintf(
			"数据库里有 %d 张表不在任何一份清单里（%s）。"+
				"这通常意味着数据库升级过而代码没跟上 —— 请先确认它们该清空还是该保留，再执行恢复出厂。",
			len(out.UnknownTables), strings.Join(out.UnknownTables, "、"))
	default:
		out.Executable = true
	}
	return out, nil
}

func (s *Service) liveTables(ctx context.Context) ([]string, error) {
	rows, err := s.db.QueryContext(ctx, `
		SELECT table_name FROM information_schema.tables
		WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
		ORDER BY table_name`)
	if err != nil {
		return nil, fmt.Errorf("读取表清单: %w", err)
	}
	defer rows.Close()
	out := []string{}
	for rows.Next() {
		var t string
		if err := rows.Scan(&t); err != nil {
			return nil, err
		}
		out = append(out, t)
	}
	return out, rows.Err()
}

func (s *Service) count(ctx context.Context, table, where string) (int, error) {
	q := "SELECT COUNT(*) FROM " + quoteIdent(table)
	if where != "" {
		q += " WHERE " + where
	}
	var n int
	if err := s.db.QueryRowContext(ctx, q).Scan(&n); err != nil {
		return 0, fmt.Errorf("统计表 %s: %w", table, err)
	}
	return n, nil
}

func quoteIdent(s string) string { return "`" + strings.ReplaceAll(s, "`", "``") + "`" }

func countMediaFiles(dir string) (int, int64) {
	n, size := 0, int64(0)
	ents, err := os.ReadDir(dir)
	if err != nil {
		return 0, 0
	}
	for _, e := range ents {
		if e.IsDir() {
			continue
		}
		if info, err := e.Info(); err == nil {
			n++
			size += info.Size()
		}
	}
	return n, size
}

// ConfirmText 是执行恢复出厂必须逐字输入的确认文本。
const ConfirmText = "恢复出厂设置"

type FactoryInput struct {
	ConfirmText     string
	PurgeMediaFiles bool
	MediaDir        string
	// KeepMediaFile 是 media.id=1 对应的物理文件名，清理媒体时要留着它（BR-264）。
	KeepMediaFile string
}

type FactoryResult struct {
	ClearedTables     int            `json:"clearedTables"`
	DeletedRows       int64          `json:"deletedRows"`
	DeletedMediaFiles int            `json:"deletedMediaFiles"`
	FailedMediaFiles  []string       `json:"failedMediaFiles"`
	Preserved         map[string]int `json:"preserved"`
	RequiresRestart   bool           `json:"requiresRestart"`
	RestartHint       string         `json:"restartHint"`
}

// FactoryReset 执行恢复出厂。
//
// 全程一个事务，只有 DELETE，一条 DDL 都没有 —— 89 张表全是 InnoDB，
// 中途任何一步失败都能干净回滚（旧版用 START TRANSACTION 包着但
// **没有任何 COMMIT/ROLLBACK 的错误判断**，D-219）。
func (s *Service) FactoryReset(ctx context.Context, in FactoryInput) (*FactoryResult, error) {
	if in.ConfirmText != ConfirmText {
		return nil, fmt.Errorf("确认文本不正确，需要逐字输入「%s」", ConfirmText)
	}
	pv, err := s.PreviewFactoryReset(ctx, in.MediaDir)
	if err != nil {
		return nil, err
	}
	if !pv.Executable {
		return nil, fmt.Errorf("%s", pv.Blocker)
	}
	cur, err := s.Get(ctx)
	if err != nil {
		return nil, err
	}
	if cur.HA.Model == 2 {
		return nil, ErrReadOnly
	}

	out := &FactoryResult{Preserved: map[string]int{}, FailedMediaFiles: []string{}}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	for _, t := range pv.ClearTables {
		res, err := tx.ExecContext(ctx, "DELETE FROM "+quoteIdent(t.Table))
		if err != nil {
			return nil, fmt.Errorf("清空表 %s: %w", t.Table, err)
		}
		n, _ := res.RowsAffected()
		out.DeletedRows += n
		out.ClearedTables++
	}
	for _, k := range keepTables {
		res, err := tx.ExecContext(ctx,
			"DELETE FROM "+quoteIdent(k.Table)+" WHERE NOT ("+k.Where+")")
		if err != nil {
			return nil, fmt.Errorf("清理表 %s: %w", k.Table, err)
		}
		n, _ := res.RowsAffected()
		out.DeletedRows += n
		out.ClearedTables++

		var left int
		if err := tx.QueryRowContext(ctx,
			"SELECT COUNT(*) FROM "+quoteIdent(k.Table)).Scan(&left); err != nil {
			return nil, fmt.Errorf("统计表 %s: %w", k.Table, err)
		}
		out.Preserved[k.Table] = left
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}

	// 媒体物理文件放在事务之外：文件系统没有事务。
	// 先库后文件 —— 库删失败时一个文件都没动。
	if in.PurgeMediaFiles && in.MediaDir != "" {
		out.DeletedMediaFiles, out.FailedMediaFiles = purgeMedia(in.MediaDir, in.KeepMediaFile)
	}

	out.RequiresRestart = true
	out.RestartHint = "后台广播服务内存里仍是清空前的任务与媒体清单，需要重启后台服务才会生效。" +
		"新版不会替你发那条重启报文 —— 实测它会让整台服务器立刻重启。"
	return out, nil
}

// purgeMedia 删掉媒体目录下的物理文件（修 D-217）。
//
// 只删顶层普通文件，不递归；keep 指定的文件留下（BR-264）。
func purgeMedia(dir, keep string) (int, []string) {
	n := 0
	failed := []string{}
	ents, err := os.ReadDir(dir)
	if err != nil {
		return 0, []string{"媒体目录不可访问"}
	}
	keepBase := filepath.Base(keep)
	for _, e := range ents {
		if e.IsDir() {
			continue
		}
		if keepBase != "" && keepBase != "." && e.Name() == keepBase {
			continue
		}
		if err := os.Remove(filepath.Join(dir, e.Name())); err != nil {
			failed = append(failed, e.Name())
			continue
		}
		n++
	}
	return n, failed
}

// KeepMediaFileName 查出 media.id = 1 那一行的物理文件名，供清理时保留。
func (s *Service) KeepMediaFileName(ctx context.Context) (string, error) {
	var fn sql.NullString
	err := s.db.QueryRowContext(ctx,
		`SELECT filename FROM media WHERE id = 1 LIMIT 1`).Scan(&fn)
	if err == sql.ErrNoRows {
		return "", nil
	}
	if err != nil {
		return "", fmt.Errorf("查询保留媒体: %w", err)
	}
	return fn.String, nil
}
