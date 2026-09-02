// Package backup 实现备份与恢复（业务域十三，F-58 ~ F-60）。
//
// # 与旧版最根本的一处不同：备份包里不含任何 DDL
//
// 旧 backup_restore_form.php 的备份逻辑是逐表拼：
//
//	DROP TABLE IF EXISTS <t>;          ← DDL
//	<SHOW CREATE TABLE <t> 的原文>;     ← DDL
//	INSERT INTO <t> VALUES (…);        ← 数据
//
// 恢复时把这坨东西按 `;` 拆开逐条执行。于是「恢复一个结构不一致的旧备份包」
// 就等于**把线上表结构改成备份那一刻的结构** —— 这是 R1 红线上最大的实际风险。
//
// 新版是**纯数据备份**：备份包里只有每张表的行数据，一条 DDL 都没有。
// 恢复 = 在现有表里 `DELETE` + `INSERT`，全程不碰表结构。
//
// 这不是保守，而是这套部署下唯一可行的做法：
// 新版的数据库账号 htweb 的授权是
//
//	GRANT SELECT, INSERT, UPDATE, DELETE ON `audioserver`.* TO `htweb`@`%`
//
// 连 CREATE / DROP 的权限都没有，旧版那种恢复方式在这里根本执行不了。
// 代价是「结构不同的包恢复不了」—— 而这正是手册 BR-272 要求的行为。
//
// # 现网实测支撑这个设计的几个事实
//
//   - 89 张表全是 InnoDB → DELETE + INSERT 可以整体放进一个事务，真正可回滚
//   - 外键 0 个、触发器 0 个、视图 0 个、存储过程 0 个 → 不必考虑删除/插入顺序
//   - 全库 1.44 MB、约 383 行 → 一次性导出导入毫无压力
//   - 没有任何 BLOB/BINARY 列 → 所有值都能按文本导出
//   - 但**有非 utf8 的列**：terminal.postion 与 book_msg.* 是 gbk，
//     TKdeviceinfo / TKstationinfo / enabletask 的一批列是 latin1。
//     连接字符集是 utf8，MySQL 在读写两个方向自动转换，
//     值既然是从这些列里读出来的，写回去就一定表示得了，往返无损。
//
// # 媒体
//
// 物理文件在 /opt/apps/a9000/backup/mediadata（= media.Root + media.filename 的目录）。
// 现网 31 个文件 78MB，另有两个子目录：
//
//	tts/  1 个文件  476K   ← 语音合成产物，属于真实内容，备份
//	tmp/  12 个文件  11M   ← 转码中间产物，随时可再生，**不备份**
package backup

import (
	"archive/zip"
	"context"
	"crypto/sha256"
	"database/sql"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"regexp"
	"sort"
	"strings"
	"time"
)

// FormatVersion 是备份包格式版本。结构变了就加一，恢复时据此拒绝不认识的包。
const FormatVersion = 1

// ManifestName 是包内清单文件名。
const ManifestName = "_manifest.json"

// 包内目录前缀。
const (
	dbPrefix    = "db/"
	mediaPrefix = "media/"
)

// skipMediaDirs 是不参与备份的媒体子目录。
// tmp 是转码中间产物：现网 11MB、全是 root 属主、随时可再生，
// 备进去只会让包变大、恢复时还因为权限写不回去。
var skipMediaDirs = map[string]bool{"tmp": true}

// reName 限定备份包名可用的字符（修 D-224 / D-237 的目录穿越）。
var reName = regexp.MustCompile(`^[A-Za-z0-9._-]{1,120}$`)

var (
	ErrNotFound     = fmt.Errorf("备份包不存在")
	ErrBadName      = fmt.Errorf("备份包名称不合法")
	ErrIncompatible = fmt.Errorf("备份包与当前数据库结构不一致，已阻止恢复")
)

type Service struct {
	db *sql.DB
	// dir 备份包存放目录
	dir string
	// mediaDir 媒体物理目录
	mediaDir string
	// dbName 库名，用来查 information_schema
	dbName string
	loc    *time.Location
}

func New(db *sql.DB, dir, mediaDir, dbName string) *Service {
	return &Service{db: db, dir: dir, mediaDir: mediaDir, dbName: dbName,
		loc: time.FixedZone("CST", 8*3600)}
}

// ---------- 清单 ----------

// Column 是一列的结构描述，参与指纹计算。
type Column struct {
	Name     string `json:"name"`
	Type     string `json:"type"`
	Nullable bool   `json:"nullable"`
	Key      string `json:"key"`
	Charset  string `json:"charset"`
}

// Table 是一张表的结构与行数。
type Table struct {
	Name    string   `json:"name"`
	Columns []Column `json:"columns"`
	Rows    int      `json:"rows"`
}

// MediaEntry 是包内的一个媒体文件。
type MediaEntry struct {
	// Path 是相对媒体根目录的路径，例如 "x.mp3" 或 "tts/y.mp3"。
	Path   string `json:"path"`
	Size   int64  `json:"size"`
	SHA256 string `json:"sha256"`
}

type Manifest struct {
	FormatVersion int    `json:"formatVersion"`
	CreatedAt     string `json:"createdAt"`
	CreatedBy     string `json:"createdBy"`
	Label         string `json:"label"`
	Database      string `json:"database"`
	// SchemaHash 是全部表与列的指纹。恢复前拿它和当前库比对（BR-272）。
	SchemaHash string  `json:"schemaHash"`
	Tables     []Table `json:"tables"`
	TotalRows  int     `json:"totalRows"`
	// SkippedMediaDirs 记下哪些媒体子目录被有意跳过，免得日后有人以为丢了文件。
	SkippedMediaDirs []string     `json:"skippedMediaDirs"`
	Media            []MediaEntry `json:"media"`
	MediaBytes       int64        `json:"mediaBytes"`
}

// loadSchema 从 information_schema 读出当前库的结构。
//
// 用 information_schema 而不是 SHOW CREATE TABLE：后者返回的是一整段 DDL 文本，
// 里面掺着 AUTO_INCREMENT 当前值、表选项这些**会随数据变化**的东西，
// 拿它算指纹会导致「什么都没改，指纹却变了」。
// 按列取字段名/类型/可空/字符集，指纹只反映真正的结构。
func (s *Service) loadSchema(ctx context.Context) ([]Table, error) {
	rows, err := s.db.QueryContext(ctx, `
		SELECT t.table_name,
		       c.column_name, c.column_type, c.is_nullable, c.column_key,
		       COALESCE(c.character_set_name,'')
		FROM information_schema.tables t
		JOIN information_schema.columns c
		  ON c.table_schema = t.table_schema AND c.table_name = t.table_name
		WHERE t.table_schema = ? AND t.table_type = 'BASE TABLE'
		ORDER BY t.table_name, c.ordinal_position`, s.dbName)
	if err != nil {
		return nil, fmt.Errorf("读取表结构: %w", err)
	}
	defer rows.Close()

	var out []Table
	idx := map[string]int{}
	for rows.Next() {
		var tn, cn, ct, nullable, key, charset string
		if err := rows.Scan(&tn, &cn, &ct, &nullable, &key, &charset); err != nil {
			return nil, err
		}
		i, ok := idx[tn]
		if !ok {
			out = append(out, Table{Name: tn})
			i = len(out) - 1
			idx[tn] = i
		}
		out[i].Columns = append(out[i].Columns, Column{
			Name: cn, Type: ct, Nullable: nullable == "YES", Key: key, Charset: charset,
		})
	}
	return out, rows.Err()
}

// schemaHash 计算结构指纹。只吃表名与列描述，不吃行数。
func schemaHash(tables []Table) string {
	h := sha256.New()
	// 表按名字排序，列保持 ordinal_position 的原序 —— 列顺序本身就是结构的一部分
	sorted := make([]Table, len(tables))
	copy(sorted, tables)
	sort.Slice(sorted, func(i, j int) bool { return sorted[i].Name < sorted[j].Name })
	for _, t := range sorted {
		fmt.Fprintf(h, "T:%s\n", t.Name)
		for _, c := range t.Columns {
			fmt.Fprintf(h, "C:%s|%s|%t|%s|%s\n", c.Name, c.Type, c.Nullable, c.Key, c.Charset)
		}
	}
	return hex.EncodeToString(h.Sum(nil))
}

// ---------- 列表 ----------

type Item struct {
	Name      string `json:"name"`
	Size      int64  `json:"size"`
	SizeText  string `json:"sizeText"`
	CreatedAt string `json:"createdAt"`
	// Readable 表示清单能正常解析。解析不了的包（截断、非本系统生成）在列表里就标出来，
	// 不要等到点恢复才报错。
	Readable   bool      `json:"readable"`
	Note       string    `json:"note"`
	Manifest   *Manifest `json:"manifest,omitempty"`
	Compatible bool      `json:"compatible"`
}

func (s *Service) List(ctx context.Context) ([]Item, error) {
	if err := os.MkdirAll(s.dir, 0o755); err != nil {
		return nil, fmt.Errorf("创建备份目录: %w", err)
	}
	ents, err := os.ReadDir(s.dir)
	if err != nil {
		return nil, fmt.Errorf("读取备份目录: %w", err)
	}
	cur, err := s.loadSchema(ctx)
	if err != nil {
		return nil, err
	}
	curHash := schemaHash(cur)

	out := []Item{}
	for _, e := range ents {
		if e.IsDir() || !strings.HasSuffix(e.Name(), ".zip") {
			continue
		}
		info, err := e.Info()
		if err != nil {
			continue
		}
		it := Item{
			Name:      e.Name(),
			Size:      info.Size(),
			SizeText:  humanSize(info.Size()),
			CreatedAt: info.ModTime().In(s.loc).Format("2006-01-02 15:04:05"),
		}
		if m, err := s.readManifest(filepath.Join(s.dir, e.Name())); err == nil {
			it.Readable = true
			it.Manifest = m
			it.Compatible = m.FormatVersion == FormatVersion && m.SchemaHash == curHash
			if !it.Compatible {
				it.Note = "结构与当前数据库不一致，不能恢复"
			}
		} else {
			it.Note = "无法解析备份清单：" + err.Error()
		}
		out = append(out, it)
	}
	sort.Slice(out, func(i, j int) bool { return out[i].CreatedAt > out[j].CreatedAt })
	return out, nil
}

// Path 把备份包名解析成绝对路径，并断言它确实落在备份目录内。
//
// 旧版 `search_foloder_file()` 拿 `basename($file,".tar") == $file_name` 匹配，
// 完全没过滤 $file_name 里的路径分隔符（D-237）；写文件那边同样没过滤（D-224）。
func (s *Service) Path(name string) (string, error) {
	if !reName.MatchString(name) || !strings.HasSuffix(name, ".zip") {
		return "", ErrBadName
	}
	p := filepath.Join(s.dir, name)
	// 名字已经不含分隔符，这里再做一次前缀断言当第二道闸门
	abs, err := filepath.Abs(p)
	if err != nil {
		return "", ErrBadName
	}
	root, err := filepath.Abs(s.dir)
	if err != nil {
		return "", err
	}
	if !strings.HasPrefix(abs, root+string(os.PathSeparator)) {
		return "", ErrBadName
	}
	if st, err := os.Stat(abs); err != nil || st.IsDir() {
		return "", ErrNotFound
	}
	return abs, nil
}

func (s *Service) readManifest(path string) (*Manifest, error) {
	zr, err := zip.OpenReader(path)
	if err != nil {
		return nil, fmt.Errorf("打开备份包: %w", err)
	}
	defer zr.Close()
	for _, f := range zr.File {
		if f.Name != ManifestName {
			continue
		}
		rc, err := f.Open()
		if err != nil {
			return nil, err
		}
		defer rc.Close()
		var m Manifest
		if err := json.NewDecoder(rc).Decode(&m); err != nil {
			return nil, fmt.Errorf("解析清单: %w", err)
		}
		return &m, nil
	}
	return nil, fmt.Errorf("包内没有 %s", ManifestName)
}

func (s *Service) Delete(name string) error {
	p, err := s.Path(name)
	if err != nil {
		return err
	}
	if err := os.Remove(p); err != nil {
		return fmt.Errorf("删除备份包: %w", err)
	}
	return nil
}

func humanSize(n int64) string {
	switch {
	case n < 1024:
		return fmt.Sprintf("%d B", n)
	case n < 1024*1024:
		return fmt.Sprintf("%.1f KB", float64(n)/1024)
	case n < 1024*1024*1024:
		return fmt.Sprintf("%.1f MB", float64(n)/1024/1024)
	default:
		return fmt.Sprintf("%.2f GB", float64(n)/1024/1024/1024)
	}
}

// quoteIdent 给标识符加反引号。
// 表名/列名来自 information_schema，不是用户输入，但拼进 SQL 的东西一律照规矩转义。
func quoteIdent(s string) string {
	return "`" + strings.ReplaceAll(s, "`", "``") + "`"
}
