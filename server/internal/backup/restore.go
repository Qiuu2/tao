package backup

import (
	"archive/zip"
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"io"
	"os"
	"path"
	"path/filepath"
	"strings"
	"time"
)

// 恢复备份（F-59）。
//
// # 这个功能在旧版里是全系统最高危的漏洞
//
//   - D-229 restore_backup_file.php **完全没有会话与权限校验**，
//     任何人访问 `restore_backup_file.php?file_name=xxx` 即可触发恢复，
//     而恢复等价于执行任意 SQL（含 DROP TABLE）。
//   - D-231 Zip Slip：mp3 条目用 `fopen(zip_entry_name(...), "w ")` 直接写，
//     路径完全来自压缩包内部 → 构造一个包就能往服务器任意位置写文件，
//     例如 ../../../../etc/cron.d/x，等价于远程代码执行。
//   - D-230 `explode(";", $sql)` 拆 SQL，字段值里有分号（「上课铃;备用」这种
//     再常见不过）就把 INSERT 从中间劈开。
//   - D-234 用事务包裹恢复，但 DROP/CREATE 是 DDL 会隐式提交，ROLLBACK 完全无效。
//
// 新版把这些从根上消掉：
//
//   - 只有超级管理员能调，且要输入包名二次确认
//   - 包里没有 SQL 文本，只有结构化数据 → 不存在「拆 SQL」这件事
//   - 包里没有 DDL → 事务是真的有效，89 张表全 InnoDB，整体可回滚
//   - 媒体条目路径经 basename 化 + 根目录断言，Zip Slip 不成立
//   - 恢复前强制比对结构指纹，不一致直接拒绝（BR-272）

// SchemaDiff 是一条结构差异。
type SchemaDiff struct {
	Table  string `json:"table"`
	Column string `json:"column"`
	Issue  string `json:"issue"`
	Detail string `json:"detail"`
}

type Precheck struct {
	Name           string       `json:"name"`
	Compatible     bool         `json:"compatible"`
	FormatOK       bool         `json:"formatOk"`
	SchemaHashSame bool         `json:"schemaHashSame"`
	Diffs          []SchemaDiff `json:"schemaDiff"`
	Manifest       *Manifest    `json:"manifest"`
	Recommendation string       `json:"recommendation"`
	// WillDeleteRows 是恢复会先清掉的当前行数（按表汇总），让用户看清代价。
	WillDeleteRows int `json:"willDeleteRows"`
	WillInsertRows int `json:"willInsertRows"`
	MediaFiles     int `json:"mediaFiles"`
}

// Precheck 在不改动任何数据的前提下判断这个包能不能恢复。
func (s *Service) Precheck(ctx context.Context, name string) (*Precheck, error) {
	p, err := s.Path(name)
	if err != nil {
		return nil, err
	}
	m, err := s.readManifest(p)
	if err != nil {
		return nil, err
	}
	cur, err := s.loadSchema(ctx)
	if err != nil {
		return nil, err
	}

	out := &Precheck{
		Name: name, Manifest: m, Diffs: []SchemaDiff{},
		FormatOK:       m.FormatVersion == FormatVersion,
		SchemaHashSame: m.SchemaHash == schemaHash(cur),
		WillInsertRows: m.TotalRows,
		MediaFiles:     len(m.Media),
	}
	if !out.FormatOK {
		out.Recommendation = fmt.Sprintf(
			"备份包格式版本是 %d，当前程序只认识 %d，已阻止恢复。",
			m.FormatVersion, FormatVersion)
		return out, nil
	}
	out.Diffs = diffSchema(m.Tables, cur)
	out.Compatible = out.SchemaHashSame && len(out.Diffs) == 0

	if out.Compatible {
		n, err := s.countAllRows(ctx, cur)
		if err != nil {
			return nil, err
		}
		out.WillDeleteRows = n
		out.Recommendation = "结构一致，可以恢复。恢复会先清空全部表的现有数据再写入备份数据。"
	} else {
		out.Recommendation = "该备份包的表结构与当前数据库不一致，恢复会导致数据错位。已阻止。"
	}
	return out, nil
}

// diffSchema 逐表逐列比对，给出人能看懂的差异清单。
//
// 指纹不同只能告诉你「有差异」，说不出差在哪。运维需要的是后者。
func diffSchema(backup, current []Table) []SchemaDiff {
	out := []SchemaDiff{}
	curByName := map[string]Table{}
	for _, t := range current {
		curByName[t.Name] = t
	}
	bakByName := map[string]Table{}
	for _, t := range backup {
		bakByName[t.Name] = t
	}

	for _, bt := range backup {
		ct, ok := curByName[bt.Name]
		if !ok {
			out = append(out, SchemaDiff{Table: bt.Name, Issue: "TABLE_NOT_IN_DB",
				Detail: "备份包里有这张表，当前数据库里没有"})
			continue
		}
		curCols := map[string]Column{}
		for _, c := range ct.Columns {
			curCols[c.Name] = c
		}
		for _, bc := range bt.Columns {
			cc, ok := curCols[bc.Name]
			if !ok {
				out = append(out, SchemaDiff{Table: bt.Name, Column: bc.Name,
					Issue: "COLUMN_NOT_IN_DB", Detail: "当前数据库里没有这一列"})
				continue
			}
			if cc.Type != bc.Type {
				out = append(out, SchemaDiff{Table: bt.Name, Column: bc.Name,
					Issue:  "TYPE_CHANGED",
					Detail: fmt.Sprintf("备份 %s，当前 %s", bc.Type, cc.Type)})
			}
			if cc.Charset != bc.Charset {
				out = append(out, SchemaDiff{Table: bt.Name, Column: bc.Name,
					Issue:  "CHARSET_CHANGED",
					Detail: fmt.Sprintf("备份 %s，当前 %s", bc.Charset, cc.Charset)})
			}
		}
		for _, cc := range ct.Columns {
			found := false
			for _, bc := range bt.Columns {
				if bc.Name == cc.Name {
					found = true
					break
				}
			}
			if !found {
				out = append(out, SchemaDiff{Table: bt.Name, Column: cc.Name,
					Issue: "COLUMN_NOT_IN_BACKUP", Detail: "备份包里没有这一列，恢复后它会变成默认值"})
			}
		}
	}
	for _, ct := range current {
		if _, ok := bakByName[ct.Name]; !ok {
			out = append(out, SchemaDiff{Table: ct.Name, Issue: "TABLE_NOT_IN_BACKUP",
				Detail: "当前数据库有这张表，备份包里没有，恢复不会动它"})
		}
	}
	return out
}

func (s *Service) countAllRows(ctx context.Context, tables []Table) (int, error) {
	total := 0
	for _, t := range tables {
		var n int
		if err := s.db.QueryRowContext(ctx,
			"SELECT COUNT(*) FROM "+quoteIdent(t.Name)).Scan(&n); err != nil {
			return 0, fmt.Errorf("统计表 %s: %w", t.Name, err)
		}
		total += n
	}
	return total, nil
}

type RestoreResult struct {
	Name              string   `json:"name"`
	TablesRestored    int      `json:"tablesRestored"`
	RowsDeleted       int64    `json:"rowsDeleted"`
	RowsInserted      int64    `json:"rowsInserted"`
	MediaRestored     int      `json:"mediaRestored"`
	MediaFailed       []string `json:"mediaFailed"`
	SafetyBackup      string   `json:"safetyBackup"`
	Elapsed           string   `json:"elapsed"`
	SessionsInvalided bool     `json:"sessionsInvalidated"`
	// BackendNeedsRestart 提醒调用方：后台 C 服务内存里还是恢复前的数据。
	// 新版**不会**自动去重启它 —— 那条报文（server?state=1）实测是整机重启。
	BackendNeedsRestart bool   `json:"backendNeedsRestart"`
	RestartHint         string `json:"restartHint"`
}

type RestoreInput struct {
	Name string
	// ConfirmText 必须与包名逐字相同，防误点。
	ConfirmText string
	// SafetyBackup 恢复前先自动生成一份当前状态的备份。
	SafetyBackup bool
	// RestoreMedia 是否连媒体文件一起恢复。
	RestoreMedia bool
	Operator     string
}

// Restore 执行恢复。
func (s *Service) Restore(ctx context.Context, in RestoreInput) (*RestoreResult, error) {
	started := time.Now()
	if in.ConfirmText != in.Name {
		return nil, fmt.Errorf("确认文本与备份包名不一致，已取消")
	}
	pre, err := s.Precheck(ctx, in.Name)
	if err != nil {
		return nil, err
	}
	if !pre.Compatible {
		return nil, ErrIncompatible
	}

	out := &RestoreResult{Name: in.Name, MediaFailed: []string{}}

	// 先做安全备份（BR-273）。它失败就不要继续 —— 没有退路的恢复不该开始。
	if in.SafetyBackup {
		sb, err := s.Create(ctx, "auto-before-restore", in.Operator)
		if err != nil {
			return nil, fmt.Errorf("生成安全备份失败，已取消恢复: %w", err)
		}
		out.SafetyBackup = sb.Name
	}

	p, err := s.Path(in.Name)
	if err != nil {
		return nil, err
	}
	zr, err := zip.OpenReader(p)
	if err != nil {
		return nil, fmt.Errorf("打开备份包: %w", err)
	}
	defer zr.Close()

	byName := map[string]*zip.File{}
	for _, f := range zr.File {
		byName[f.Name] = f
	}

	// 数据恢复整体一个事务。包里没有任何 DDL，所以这个事务是真的能回滚的 ——
	// 旧版那个事务因为 DROP/CREATE 隐式提交而形同虚设（D-234）。
	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	for _, t := range pre.Manifest.Tables {
		f, ok := byName[dbPrefix+t.Name+".json"]
		if !ok {
			return nil, fmt.Errorf("备份包里缺少表 %s 的数据文件", t.Name)
		}
		del, ins, err := restoreTable(ctx, tx, t, f)
		if err != nil {
			return nil, err
		}
		out.RowsDeleted += del
		out.RowsInserted += ins
		out.TablesRestored++
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交恢复事务: %w", err)
	}

	// 媒体放在事务之外：文件系统没有事务，硬要绑在一起只会让数据库那边也回滚不了。
	// 顺序上先数据后媒体：数据恢复失败时媒体一个字节都没动。
	if in.RestoreMedia {
		n, failed := s.restoreMedia(zr, pre.Manifest)
		out.MediaRestored, out.MediaFailed = n, failed
	}
	out.Elapsed = time.Since(started).Round(time.Millisecond).String()
	return out, nil
}

// restoreTable 清空一张表再灌入备份数据。
func restoreTable(ctx context.Context, tx *sql.Tx, t Table, f *zip.File) (int64, int64, error) {
	rc, err := f.Open()
	if err != nil {
		return 0, 0, fmt.Errorf("读取表 %s: %w", t.Name, err)
	}
	defer rc.Close()

	var rows [][]*string
	if err := json.NewDecoder(rc).Decode(&rows); err != nil {
		return 0, 0, fmt.Errorf("解析表 %s 的数据: %w", t.Name, err)
	}

	res, err := tx.ExecContext(ctx, "DELETE FROM "+quoteIdent(t.Name))
	if err != nil {
		return 0, 0, fmt.Errorf("清空表 %s: %w", t.Name, err)
	}
	deleted, _ := res.RowsAffected()
	if len(rows) == 0 {
		return deleted, 0, nil
	}

	cols := make([]string, len(t.Columns))
	for i, c := range t.Columns {
		cols[i] = quoteIdent(c.Name)
	}
	one := "(" + strings.TrimSuffix(strings.Repeat("?,", len(cols)), ",") + ")"
	head := "INSERT INTO " + quoteIdent(t.Name) + " (" + strings.Join(cols, ",") + ") VALUES "

	var inserted int64
	const batch = 200
	for start := 0; start < len(rows); start += batch {
		end := start + batch
		if end > len(rows) {
			end = len(rows)
		}
		chunk := rows[start:end]
		args := make([]interface{}, 0, len(chunk)*len(cols))
		for _, r := range chunk {
			if len(r) != len(cols) {
				return 0, 0, fmt.Errorf("表 %s 的数据列数与结构不符：数据 %d 列，结构 %d 列",
					t.Name, len(r), len(cols))
			}
			for _, v := range r {
				if v == nil {
					args = append(args, nil)
				} else {
					args = append(args, *v)
				}
			}
		}
		q := head + strings.TrimSuffix(strings.Repeat(one+",", len(chunk)), ",")
		res, err := tx.ExecContext(ctx, q, args...)
		if err != nil {
			return 0, 0, fmt.Errorf("写入表 %s: %w", t.Name, err)
		}
		n, _ := res.RowsAffected()
		inserted += n
	}
	return deleted, inserted, nil
}

// restoreMedia 把包里的媒体文件写回媒体目录。
//
// 防 Zip Slip（D-231）三步：
//  1. 只接受 media/ 前缀的条目
//  2. 逐段清洗路径：拒绝绝对路径、拒绝任何 ".." 段、只允许一层子目录
//  3. 拼出目标路径后再做一次根目录前缀断言
//
// 旧版是 `fopen(zip_entry_name($h), "w ")` —— 路径直接来自包内，
// 而且模式字符串还多了个空格（D-232）。
func (s *Service) restoreMedia(zr *zip.ReadCloser, m *Manifest) (int, []string) {
	okCount := 0
	failed := []string{}
	if strings.TrimSpace(s.mediaDir) == "" {
		return 0, []string{"(未配置媒体目录，跳过媒体恢复)"}
	}
	root, err := filepath.Abs(s.mediaDir)
	if err != nil {
		return 0, []string{"媒体目录不可用"}
	}

	for _, f := range zr.File {
		if !strings.HasPrefix(f.Name, mediaPrefix) || strings.HasSuffix(f.Name, "/") {
			continue
		}
		rel, ok := safeRel(strings.TrimPrefix(f.Name, mediaPrefix))
		if !ok {
			failed = append(failed, f.Name+"（路径不合法，已拒绝）")
			continue
		}
		dst := filepath.Join(root, filepath.FromSlash(rel))
		if dst != root && !strings.HasPrefix(dst, root+string(os.PathSeparator)) {
			failed = append(failed, f.Name+"（越出媒体目录，已拒绝）")
			continue
		}
		if err := writeZipEntry(f, dst); err != nil {
			failed = append(failed, rel+"（"+err.Error()+"）")
			continue
		}
		okCount++
	}
	return okCount, failed
}

// safeRel 清洗包内相对路径，最多允许一层子目录。
func safeRel(name string) (string, bool) {
	name = strings.ReplaceAll(name, `\`, "/")
	if name == "" || strings.HasPrefix(name, "/") {
		return "", false
	}
	parts := strings.Split(path.Clean(name), "/")
	if len(parts) == 0 || len(parts) > 2 {
		return "", false
	}
	for _, p := range parts {
		if p == "" || p == "." || p == ".." {
			return "", false
		}
	}
	return strings.Join(parts, "/"), true
}

func writeZipEntry(f *zip.File, dst string) error {
	if err := os.MkdirAll(filepath.Dir(dst), 0o775); err != nil {
		return fmt.Errorf("建目录失败")
	}
	rc, err := f.Open()
	if err != nil {
		return fmt.Errorf("读取失败")
	}
	defer rc.Close()

	// 先写临时文件再 rename：中途失败不会留下半个文件顶掉原来的好文件。
	// 另外现网有一批媒体是 root 属主，直接以 O_TRUNC 打开会 EPERM；
	// rename 只要目录可写就能成，正好绕开这个问题。
	tmp := dst + ".part"
	out, err := os.OpenFile(tmp, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0o664)
	if err != nil {
		return fmt.Errorf("无写权限")
	}
	if _, err := io.Copy(out, rc); err != nil {
		_ = out.Close()
		_ = os.Remove(tmp)
		return fmt.Errorf("写入失败")
	}
	if err := out.Close(); err != nil {
		_ = os.Remove(tmp)
		return fmt.Errorf("写入失败")
	}
	if err := os.Rename(tmp, dst); err != nil {
		_ = os.Remove(tmp)
		return fmt.Errorf("替换失败")
	}
	return nil
}
