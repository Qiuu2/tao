package backup

import (
	"archive/zip"
	"context"
	"crypto/sha256"
	"database/sql"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"io"
	"os"
	"path/filepath"
	"strings"
	"time"
)

// 创建备份（F-58）。

type CreateResult struct {
	Name       string    `json:"name"`
	Size       int64     `json:"size"`
	SizeText   string    `json:"sizeText"`
	Manifest   *Manifest `json:"manifest"`
	Elapsed    string    `json:"elapsed"`
	SkippedDir []string  `json:"skippedMediaDirs"`
}

// Create 生成一个备份包。
//
// 整个过程只读数据库（SELECT）与媒体目录，不写任何一张表 —— 备份本身零风险。
//
// 先写到同目录下的 .part 临时文件，成功后再 rename：
// 中途失败（磁盘满、进程被杀）不会在列表里留下一个看着正常、实际截断的包。
func (s *Service) Create(ctx context.Context, label, operator string) (*CreateResult, error) {
	started := time.Now()
	if err := os.MkdirAll(s.dir, 0o755); err != nil {
		return nil, fmt.Errorf("创建备份目录: %w", err)
	}

	name := fmt.Sprintf("bk-%s", time.Now().In(s.loc).Format("20060102-150405"))
	if slug := slugify(label); slug != "" {
		name += "-" + slug
	}
	name += ".zip"
	if !reName.MatchString(name) {
		return nil, ErrBadName
	}
	final := filepath.Join(s.dir, name)
	tmp := final + ".part"

	f, err := os.Create(tmp)
	if err != nil {
		return nil, fmt.Errorf("创建备份文件: %w", err)
	}
	// 失败路径上一定要把半成品删掉
	ok := false
	defer func() {
		_ = f.Close()
		if !ok {
			_ = os.Remove(tmp)
		}
	}()

	zw := zip.NewWriter(f)
	m := &Manifest{
		FormatVersion:    FormatVersion,
		CreatedAt:        time.Now().In(s.loc).Format("2006-01-02 15:04:05"),
		CreatedBy:        operator,
		Label:            strings.TrimSpace(label),
		Database:         s.dbName,
		SkippedMediaDirs: []string{},
		Media:            []MediaEntry{},
	}

	tables, err := s.loadSchema(ctx)
	if err != nil {
		return nil, err
	}
	m.SchemaHash = schemaHash(tables)

	for i := range tables {
		n, err := s.dumpTable(ctx, zw, &tables[i])
		if err != nil {
			return nil, err
		}
		tables[i].Rows = n
		m.TotalRows += n
	}
	m.Tables = tables

	if err := s.dumpMedia(zw, m); err != nil {
		return nil, err
	}

	// 清单最后写：它记录的行数与媒体清单要等前面都跑完才准
	mw, err := zw.Create(ManifestName)
	if err != nil {
		return nil, fmt.Errorf("写入清单: %w", err)
	}
	enc := json.NewEncoder(mw)
	enc.SetIndent("", "  ")
	if err := enc.Encode(m); err != nil {
		return nil, fmt.Errorf("写入清单: %w", err)
	}
	if err := zw.Close(); err != nil {
		return nil, fmt.Errorf("收尾备份包: %w", err)
	}
	if err := f.Sync(); err != nil {
		return nil, fmt.Errorf("落盘: %w", err)
	}
	if err := f.Close(); err != nil {
		return nil, fmt.Errorf("关闭备份文件: %w", err)
	}
	if err := os.Rename(tmp, final); err != nil {
		return nil, fmt.Errorf("提交备份包: %w", err)
	}
	ok = true

	st, _ := os.Stat(final)
	res := &CreateResult{
		Name: name, Manifest: m,
		Elapsed:    time.Since(started).Round(time.Millisecond).String(),
		SkippedDir: m.SkippedMediaDirs,
	}
	if st != nil {
		res.Size, res.SizeText = st.Size(), humanSize(st.Size())
	}
	return res, nil
}

// dumpTable 把一张表的数据写成 db/<表名>.json。
//
// 值一律按**字符串或 null** 导出，不做类型推断：
//   - 数值、日期在 MySQL 侧本来就有权威的文本表示，原样带走再原样写回最稳
//   - 尤其是 `0000-00-00` 这种零日期，任何时间类型解析都会把它变成别的东西
//   - null 与空串必须区分，所以用 *string 而不是 string
func (s *Service) dumpTable(ctx context.Context, zw *zip.Writer, t *Table) (int, error) {
	sel := make([]string, len(t.Columns))
	for i, c := range t.Columns {
		sel[i] = selectExpr(c)
	}
	q := "SELECT " + strings.Join(sel, ",") + " FROM " + quoteIdent(t.Name)
	rows, err := s.db.QueryContext(ctx, q)
	if err != nil {
		return 0, fmt.Errorf("导出表 %s: %w", t.Name, err)
	}
	defer rows.Close()

	w, err := zw.Create(dbPrefix + t.Name + ".json")
	if err != nil {
		return 0, fmt.Errorf("写入表 %s: %w", t.Name, err)
	}
	if _, err := io.WriteString(w, "[\n"); err != nil {
		return 0, err
	}

	raw := make([]sql.NullString, len(t.Columns))
	ptrs := make([]interface{}, len(t.Columns))
	for i := range raw {
		ptrs[i] = &raw[i]
	}
	enc := json.NewEncoder(w)

	n := 0
	for rows.Next() {
		if err := rows.Scan(ptrs...); err != nil {
			return 0, fmt.Errorf("读取表 %s: %w", t.Name, err)
		}
		rec := make([]*string, len(raw))
		for i := range raw {
			if raw[i].Valid {
				v := raw[i].String
				rec[i] = &v
			}
		}
		if n > 0 {
			if _, err := io.WriteString(w, ","); err != nil {
				return 0, err
			}
		}
		if err := enc.Encode(rec); err != nil {
			return 0, fmt.Errorf("写入表 %s: %w", t.Name, err)
		}
		n++
	}
	if err := rows.Err(); err != nil {
		return 0, err
	}
	if _, err := io.WriteString(w, "]\n"); err != nil {
		return 0, err
	}
	return n, nil
}

// dumpMedia 把媒体物理文件打进包里。
//
// 只走一层子目录：现网就 tts/ 与 tmp/ 两个，其中 tmp/ 明确跳过（见 skipMediaDirs）。
// 不做无限递归 —— 媒体目录不是任意文件树，深度失控只会把包撑爆。
func (s *Service) dumpMedia(zw *zip.Writer, m *Manifest) error {
	if strings.TrimSpace(s.mediaDir) == "" {
		return nil
	}
	if st, err := os.Stat(s.mediaDir); err != nil || !st.IsDir() {
		// 媒体目录不存在不算错：可能是纯数据库部署
		m.SkippedMediaDirs = append(m.SkippedMediaDirs, "(媒体目录不可访问，本次未备份媒体)")
		return nil
	}
	ents, err := os.ReadDir(s.mediaDir)
	if err != nil {
		return fmt.Errorf("读取媒体目录: %w", err)
	}
	for _, e := range ents {
		if e.IsDir() {
			if skipMediaDirs[e.Name()] {
				m.SkippedMediaDirs = append(m.SkippedMediaDirs, e.Name()+"/（转码中间产物，可再生）")
				continue
			}
			sub, err := os.ReadDir(filepath.Join(s.mediaDir, e.Name()))
			if err != nil {
				continue
			}
			for _, se := range sub {
				if se.IsDir() {
					continue
				}
				if err := s.addMediaFile(zw, m, e.Name()+"/"+se.Name()); err != nil {
					return err
				}
			}
			continue
		}
		if err := s.addMediaFile(zw, m, e.Name()); err != nil {
			return err
		}
	}
	return nil
}

func (s *Service) addMediaFile(zw *zip.Writer, m *Manifest, rel string) error {
	p := filepath.Join(s.mediaDir, filepath.FromSlash(rel))
	src, err := os.Open(p)
	if err != nil {
		// 单个文件读不了不该让整次备份失败，如实记进清单
		m.SkippedMediaDirs = append(m.SkippedMediaDirs, rel+"（读取失败，已跳过）")
		return nil
	}
	defer src.Close()

	if _, err := src.Stat(); err != nil {
		m.SkippedMediaDirs = append(m.SkippedMediaDirs, rel+"（无法取文件信息，已跳过）")
		return nil
	}
	w, err := zw.Create(mediaPrefix + rel)
	if err != nil {
		return fmt.Errorf("写入媒体 %s: %w", rel, err)
	}
	h := sha256.New()
	n, err := io.Copy(io.MultiWriter(w, h), src)
	if err != nil {
		return fmt.Errorf("复制媒体 %s: %w", rel, err)
	}
	m.Media = append(m.Media, MediaEntry{
		Path: rel, Size: n, SHA256: hex.EncodeToString(h.Sum(nil)),
	})
	m.MediaBytes += n
	return nil
}

// selectExpr 决定一列怎么取。
//
// # 时间类型必须在 SQL 侧转成字符串
//
// 连接串里带了 `parseTime=true`（media / task 等模块依赖它），
// 于是驱动会把 DATE/DATETIME/TIMESTAMP/TIME 列解析成 time.Time。
// 再扫进 sql.NullString 时，database/sql 用 RFC3339Nano 格式化，
// 得到 `2011-06-14T20:11:30+08:00` —— 这个字符串写回 MySQL 直接报
// `Error 1292 Incorrect datetime value`（现网实测，恢复整体回滚）。
//
// 用 CAST(... AS CHAR) 让 MySQL 自己给出规范文本，绕开驱动的时间解析。
// 顺带把 `0000-00-00` 这种零日期也原样保住 —— 它进不了 time.Time，
// 靠驱动解析这一条路走不通。
func selectExpr(c Column) string {
	id := quoteIdent(c.Name)
	base := c.Type
	if i := strings.IndexAny(base, "( "); i >= 0 {
		base = base[:i]
	}
	switch strings.ToLower(base) {
	case "date", "datetime", "timestamp", "time", "year":
		return "CAST(" + id + " AS CHAR)"
	default:
		return id
	}
}

// slugify 把用户填的备注压成文件名能用的片段。
func slugify(s string) string {
	s = strings.TrimSpace(s)
	if s == "" {
		return ""
	}
	var b strings.Builder
	for _, r := range s {
		switch {
		case r >= 'A' && r <= 'Z', r >= 'a' && r <= 'z', r >= '0' && r <= '9':
			b.WriteRune(r)
		case r == '-' || r == '_':
			b.WriteRune(r)
		}
		if b.Len() >= 40 {
			break
		}
	}
	return b.String()
}
