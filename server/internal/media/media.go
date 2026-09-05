// Package media 实现媒体资源管理（手册业务域二，F-05 ~ F-10）。
//
// # 对旧系统的关键修复
//
//   - D-12 同一条 SQL 执行两遍（一次取行数、一次带 LIMIT）→ COUNT(*) + LIMIT/OFFSET
//   - D-13 翻页丢失筛选条件 → 全部条件进 URL query
//   - D-14 page=0 算出 LIMIT -18 导致语法错误 → 分页参数归一
//   - D-18 "FROM media" 与 "WHERE" 之间缺空格拼成 FROM mediaWHERE → 查询构造器统一拼接
//   - D-23 目录容量把全部行取回 PHP 累加 → SUM(size)
//   - D-24/D-25 TTS 过滤口径不一致、无筛选分支不过滤 → 统一为公共条件
package media

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"os"
	"path/filepath"

	"htweb/internal/auth"
	"htweb/internal/folder"
	"htweb/internal/store"
)

// ErrFolderDenied 表示目标文件夹不存在，或存在但对当前用户不可见。
//
// 刻意把「不存在」和「无权访问」合并成同一个错误：分开报会变成一个
// 存在性探针，让普通用户可以逐个 id 试出别人建了哪些私有目录。
var ErrFolderDenied = errors.New("文件夹不存在或无权访问")

// assertFolderVisible 是媒体侧的目录可见性闸门。
//
// 目录树按 BR-05 / BR-85 裁剪，但媒体接口原先只拿 folderid 去查 media 行，
// 从没校验这个 folderid 本身对调用者是否可见。实测后果：
//   - 列表：普通用户构造 ?folderId=<别人的私有目录> 能拿到该目录的名称、
//     容量、canUpload 标志（媒体行本身有 userid 过滤，所以只泄露元信息）
//   - 上传：能把文件直接写进别人的私有目录（这条是真正的越权写）
//   - 清空：确认文本校验先于权限校验，报错信息本身就是个目录名猜测预言机
//
// 读写两侧统一走这一个判定，口径与目录树完全一致。
func (s *Service) assertFolderVisible(ctx context.Context, u *auth.User, folderID int64) error {
	cond, args := folder.VisibleCond(u)
	var n int
	err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM filefolder WHERE id = ? AND `+cond,
		append([]interface{}{folderID}, args...)...).Scan(&n)
	if err != nil {
		return fmt.Errorf("校验文件夹可见性: %w", err)
	}
	if n == 0 {
		return ErrFolderDenied
	}
	return nil
}

// AssertFolderVisible 供 HTTP 层在进入具体业务前先做目录闸门（如上传）。
func (s *Service) AssertFolderVisible(ctx context.Context, u *auth.User, folderID int64) error {
	return s.assertFolderVisible(ctx, u, folderID)
}

// Item 是列表行。字段对齐手册 §4.4.1 的响应体。
type Item struct {
	ID          int64  `json:"id"`
	Name        string `json:"name"`
	Size        int64  `json:"size"` // 单位 KB（契约 C-03）
	SizeText    string `json:"sizeText"`
	TypeID      string `json:"typeid"`
	Bitrate     int64  `json:"bitrate"`
	BitrateText string `json:"bitrateText"`
	TimeLength  int64  `json:"timelength"` // 秒
	TimeText    string `json:"timelengthText"`
	FolderID    int64  `json:"folderId"`
	UserID      int64  `json:"userId"`
	StreamURL   string `json:"streamUrl"`
	DownloadURL string `json:"downloadUrl"`
}

// FolderInfo 是列表页顶部展示的当前文件夹信息。
type FolderInfo struct {
	ID              int64  `json:"id"`
	Name            string `json:"name"`
	System          bool   `json:"system"`
	TotalSizeKB     int64  `json:"totalSizeKB"`
	TotalSizeText   string `json:"totalSizeText"`
	CanUpload       bool   `json:"canUpload"`
	CanCreateChild  bool   `json:"canCreateChild"`
	IsRecordLibrary bool   `json:"isRecordLibrary"`
}

type ListResult struct {
	Items     []Item      `json:"-"`
	Total     int64       `json:"-"`
	Folder    *FolderInfo `json:"folder"`
	ScopeNote string      `json:"scopeNote"`
}

type Service struct {
	db   *sql.DB
	root string
}

func New(db *sql.DB, mediaRoot string) *Service {
	return &Service{db: db, root: mediaRoot}
}

// 排序白名单。绝不接受用户传入的列名（修复 D-01 / D-199 的注入面）。
var orderWhitelist = map[string]string{
	"id":   "media.id",
	"size": "media.size",
	"name": "media.name",
}

// 搜索字段白名单。
var searchWhitelist = map[string]string{
	"name":   "media.name",
	"typeid": "media.typeid",
}

// ttsFilter 排除 TTS 占位记录。
//
// 旧系统两处口径不一致：media_file.php 用 filename<>'tts'，
// get_filelist() 用 typeid<>'tts'（缺陷 D-24）；
// 且无筛选分支根本不过滤，一搜索反而消失（D-25）。
// 这里统一为两列同时判定，并作为所有分支的公共条件。
const ttsFilter = "media.typeid <> 'tts' AND media.filename <> 'tts'"

type ListQuery struct {
	FolderID  int64
	SearchKey string
	Keyword   string
	OrderBy   string
	Order     string
	Pager     store.Pager
}

// List 查询指定文件夹下的媒体。
func (s *Service) List(ctx context.Context, u *auth.User, q ListQuery) (*ListResult, error) {
	// 先过目录闸门，再查内容。顺序不能反：folderInfo 会回填目录名与容量，
	// 先查后拦等于已经把要保护的信息算出来了。
	if err := s.assertFolderVisible(ctx, u, q.FolderID); err != nil {
		return nil, err
	}

	cond := &store.Cond{}
	cond.Add("media.folderid = ?", q.FolderID)
	cond.Add(ttsFilter)

	// ⚠ 这里**不按上传者收敛**。全站口径是「管理员看全部，其他人只看自己建的」，
	// 文件管理是唯一的例外：媒体库是共用素材库，谁传的文件别人都要能拿来做任务。
	// 能不能删仍然只看归属（见 write.go 的 Delete）。规则的权威定义在 folder.VisibleCond。

	if col, ok := searchWhitelist[q.SearchKey]; ok && q.Keyword != "" {
		cond.Add(col+" LIKE ? ESCAPE '\\\\'", store.EscapeLike(q.Keyword))
	}

	where := cond.Where()
	args := cond.Args()

	// 先取总数。COUNT 不带 ORDER BY，减少一次排序开销。
	var total int64
	if err := s.db.QueryRowContext(ctx,
		"SELECT COUNT(*) FROM media"+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计媒体数: %w", err)
	}

	order := store.OrderBy(orderWhitelist, q.OrderBy, q.Order, "media.id DESC")

	listSQL := `SELECT media.id, media.name, media.size, media.typeid, media.filename,
	                   media.bitrate, media.timelength, media.folderid, media.userid
	            FROM media` + where + " ORDER BY " + order + " LIMIT ? OFFSET ?"
	listArgs := append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())

	rs, err := s.db.QueryContext(ctx, listSQL, listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询媒体列表: %w", err)
	}
	defer rs.Close()

	items := make([]Item, 0, q.Pager.PageSize)
	for rs.Next() {
		var (
			it       Item
			filename string
			size     sql.NullInt64
			bitrate  sql.NullInt64
			tlen     sql.NullInt64
			userID   sql.NullInt64
		)
		if err := rs.Scan(&it.ID, &it.Name, &size, &it.TypeID, &filename,
			&bitrate, &tlen, &it.FolderID, &userID); err != nil {
			return nil, fmt.Errorf("扫描媒体行: %w", err)
		}
		it.Size = size.Int64
		it.Bitrate = bitrate.Int64
		it.TimeLength = tlen.Int64
		it.UserID = userID.Int64
		it.SizeText = FormatSizeKB(it.Size)
		it.BitrateText = FormatBitrate(it.Bitrate)
		it.TimeText = FormatDuration(it.TimeLength)
		// 物理路径不外泄给前端，只给资源接口地址（修复 D-02 的信息暴露面）
		it.StreamURL = fmt.Sprintf("/api/media/%d/stream", it.ID)
		it.DownloadURL = fmt.Sprintf("/api/media/%d/download", it.ID)
		items = append(items, it)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}

	info, err := s.folderInfo(ctx, u, q.FolderID)
	if err != nil {
		return nil, err
	}

	return &ListResult{Items: items, Total: total, Folder: info}, nil
}

// folderInfo 汇总当前文件夹的展示信息。
func (s *Service) folderInfo(ctx context.Context, u *auth.User, folderID int64) (*FolderInfo, error) {
	info := &FolderInfo{ID: folderID}

	var parentID int64
	err := s.db.QueryRowContext(ctx,
		`SELECT name, parentid FROM filefolder WHERE id = ? LIMIT 1`, folderID).
		Scan(&info.Name, &parentID)
	if err == sql.ErrNoRows {
		info.Name = "(目录不存在)"
		return info, nil
	}
	if err != nil {
		return nil, fmt.Errorf("查询文件夹信息: %w", err)
	}

	info.System = folderID <= 9

	// 目录容量：交给数据库聚合，而不是把所有行取回来在应用里累加（修复 D-23）
	var sum sql.NullInt64
	if err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(SUM(size),0) FROM media WHERE folderid = ?`, folderID).Scan(&sum); err != nil {
		return nil, fmt.Errorf("统计目录容量: %w", err)
	}
	info.TotalSizeKB = sum.Int64
	info.TotalSizeText = FormatSizeKB(sum.Int64)

	// 录音媒体库锁定：从该库进入时禁用上传与目录操作（业务规则见手册 §3.3.7）
	top, err := s.topLibrary(ctx, folderID)
	if err != nil {
		return nil, err
	}
	info.IsRecordLibrary = top == 5

	depth, err := s.depthOf(ctx, folderID)
	if err != nil {
		return nil, err
	}
	info.CanCreateChild = depth > 0 && depth < 3 && !info.IsRecordLibrary
	info.CanUpload = u.HasRight(auth.PrivMedia) && !info.IsRecordLibrary

	return info, nil
}

// topLibrary 向上回溯到顶层库 ID。
func (s *Service) topLibrary(ctx context.Context, id int64) (int64, error) {
	cur := id
	visited := map[int64]bool{}
	for i := 0; i < 64; i++ {
		if cur == 0 || visited[cur] {
			return 0, nil
		}
		visited[cur] = true
		var parent int64
		err := s.db.QueryRowContext(ctx,
			`SELECT parentid FROM filefolder WHERE id = ? LIMIT 1`, cur).Scan(&parent)
		if err == sql.ErrNoRows {
			return 0, nil
		}
		if err != nil {
			return 0, fmt.Errorf("回溯顶层库: %w", err)
		}
		if parent == 0 {
			return cur, nil
		}
		cur = parent
	}
	return 0, nil
}

func (s *Service) depthOf(ctx context.Context, id int64) (int, error) {
	cur := id
	depth := 0
	visited := map[int64]bool{}
	for cur != 0 && depth < 64 {
		if visited[cur] {
			return 0, nil
		}
		visited[cur] = true
		var parent int64
		err := s.db.QueryRowContext(ctx,
			`SELECT parentid FROM filefolder WHERE id = ? LIMIT 1`, cur).Scan(&parent)
		if err == sql.ErrNoRows {
			return 0, nil
		}
		if err != nil {
			return 0, err
		}
		depth++
		cur = parent
	}
	return depth, nil
}

// Detail 取单条媒体，用于试听与下载。
type Detail struct {
	ID       int64
	Name     string
	FileName string
	TypeID   string
	FolderID int64
	UserID   int64
}

func (s *Service) Get(ctx context.Context, id int64) (*Detail, error) {
	var d Detail
	var userID sql.NullInt64
	err := s.db.QueryRowContext(ctx, `
		SELECT id, name, filename, typeid, folderid, userid
		FROM media WHERE id = ? LIMIT 1`, id).
		Scan(&d.ID, &d.Name, &d.FileName, &d.TypeID, &d.FolderID, &userID)
	if err != nil {
		return nil, err
	}
	d.UserID = userID.Int64
	return &d, nil
}

// PhysicalPath 把库里的 filename 映射为宿主机真实路径，并做越权防护。
//
// 映射规则（手册 §1.6.3，已实测验证）：
//
//	宿主路径 = MediaRoot + media.filename
//	例：/opt/apps/a9000 + /backup/mediadata/xxx.mp3
//
// 旧系统的 download_media_file.php 直接用 URL 参数拼路径且无鉴权，
// 构成任意文件读取漏洞（D-02）。新版只接受 mediaId，路径一律从库里取，
// 并用 filepath.Clean + 前缀断言确保结果仍在媒体根目录内。
func (s *Service) PhysicalPath(filename string) (string, error) {
	if filename == "" || filename == "none" || filename == "tts" {
		return "", fmt.Errorf("媒体没有有效的物理文件")
	}
	root := filepath.Clean(s.root)
	full := filepath.Clean(filepath.Join(root, filename))

	// 断言最终路径没有跳出媒体根目录
	rel, err := filepath.Rel(root, full)
	if err != nil || rel == ".." || len(rel) >= 3 && rel[:3] == ".."+string(filepath.Separator) {
		return "", fmt.Errorf("非法的媒体路径")
	}
	if _, err := os.Stat(full); err != nil {
		return "", fmt.Errorf("媒体文件不存在: %w", err)
	}
	return full, nil
}

// ---------- 展示格式化 ----------

// FormatSizeKB 按旧界面的规则显示大小：≥1024KB 折算为 M。
func FormatSizeKB(kb int64) string {
	if kb >= 1024 {
		return fmt.Sprintf("%.2f M", float64(kb)/1024)
	}
	return fmt.Sprintf("%d K", kb)
}

// FormatBitrate 按旧界面规则：≥1000000 → Mbps，≥1000 → kbps，否则 bps。
func FormatBitrate(bps int64) string {
	switch {
	case bps >= 1000000:
		return fmt.Sprintf("%g Mbps", float64(bps)/1000000)
	case bps >= 1000:
		return fmt.Sprintf("%g kbps", float64(bps)/1000)
	default:
		return fmt.Sprintf("%d bps", bps)
	}
}

// FormatDuration 秒 → "x分y秒"，与旧界面一致。
func FormatDuration(sec int64) string {
	return fmt.Sprintf("%d分%d秒", sec/60, sec%60)
}
