package media

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"io"
	"math/rand"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"time"

	"htweb/internal/auth"
)

var (
	ErrBadType      = errors.New("只支持 mp3 / wav 格式")
	ErrTooLarge     = errors.New("文件超出大小限制")
	ErrRecordLocked = errors.New("录音媒体库不允许上传")
	ErrNotFound     = errors.New("媒体不存在")
	ErrNoPermission = errors.New("无权操作")
)

// 提示音目录：上传到这里的媒体按 16000Hz 转码，其余按 44100Hz（BR-39）。
const tipFolderID = 9

// 系统预置文件夹上限。位于其中的媒体仅超级管理员可删（BR-53）。
const systemFolderMaxID = 9

// UploadResult 是单个文件的处理结果。
type UploadResult struct {
	FileName  string `json:"fileName"`
	Status    string `json:"status"` // created / overwritten / failed
	MediaID   int64  `json:"mediaId,omitempty"`
	SizeKB    int64  `json:"sizeKB,omitempty"`
	ErrorCode int    `json:"errorCode,omitempty"`
	Message   string `json:"message,omitempty"`
}

type Uploader struct {
	db         *sql.DB
	root       string
	ffmpegPath string
	maxBytes   int64
}

func NewUploader(db *sql.DB, mediaRoot, ffmpegPath string, maxUploadMB int64) *Uploader {
	if maxUploadMB <= 0 {
		maxUploadMB = 300
	}
	return &Uploader{db: db, root: mediaRoot, ffmpegPath: ffmpegPath, maxBytes: maxUploadMB << 20}
}

// Upload 接收一个音频文件并登记到媒体库。
//
// # 必须逐条遵守的写入契约（手册 §7.2）
//
//	C-03 media.size 单位是 KB（字节数 ÷ 1024）
//	C-04 media.typeid 上传后固定写 'mp3'
//	C-05 media.filename 固定格式 /backup/mediadata/<数字>.mp3
//	C-06 timelength/channel/sample/bitrate 一律写 0，由后台 C 服务扫描回填
//	     —— 绝不能自作聪明用 ffprobe 填真值，那会与后台算法不一致
//	C-07 media.priority 恒写 0（该列注释标注"未使用"）
//	C-08 media.userid 写真实上传者，否则普通用户在旧 Web 看不到自己上传的媒体
func (u *Uploader) Upload(
	ctx context.Context, user *auth.User, folderID int64,
	origName string, src io.Reader, declaredSize int64,
) UploadResult {
	res := UploadResult{FileName: origName, Status: "failed"}

	ext := strings.ToLower(strings.TrimPrefix(filepath.Ext(origName), "."))
	if ext != "mp3" && ext != "wav" {
		res.Message = ErrBadType.Error()
		return res
	}
	if declaredSize > 0 && declaredSize > u.maxBytes {
		res.Message = fmt.Sprintf("文件超过 %dMB 上限", u.maxBytes>>20)
		return res
	}

	// media.name 取「去掉扩展名」的原始文件名（BR-41）
	baseName := strings.TrimSuffix(filepath.Base(origName), filepath.Ext(origName))
	if baseName == "" {
		res.Message = "文件名非法"
		return res
	}

	mediaDir := filepath.Join(u.root, "backup", "mediadata")
	tmpPath := filepath.Join(mediaDir, fmt.Sprintf(".upload-%s.tmp", randomToken()))

	// 1) 落临时文件，同时强制大小上限
	written, err := writeLimited(tmpPath, src, u.maxBytes)
	if err != nil {
		_ = os.Remove(tmpPath)
		if errors.Is(err, ErrTooLarge) {
			res.Message = fmt.Sprintf("文件超过 %dMB 上限", u.maxBytes>>20)
		} else {
			res.Message = "写入临时文件失败: " + err.Error()
		}
		return res
	}
	defer os.Remove(tmpPath)
	_ = written

	// 2) 统一转码为 mp3
	//
	// 沿用旧系统的转码约定，不能随意改（BR-38 ~ BR-40）：
	//   · 统一 mp3、128k、双声道
	//   · 提示音目录 16000Hz，其余 44100Hz
	//   · 尾部拼接 2 秒静音 —— 这是旧系统的播放防截断约定，改了播放行为会变
	sampleRate := "44100"
	if folderID == tipFolderID {
		sampleRate = "16000"
	}
	targetRel := fmt.Sprintf("/backup/mediadata/%s.mp3", newMediaFileID())
	targetAbs := filepath.Join(u.root, targetRel)

	if err := u.transcode(ctx, tmpPath, targetAbs, sampleRate); err != nil {
		_ = os.Remove(targetAbs)
		res.Message = "转码失败: " + err.Error()
		return res
	}

	// 转码产物必须真实存在且非空，否则会写出指向空文件的脏记录（旧版缺陷 D-8 同类）
	st, err := os.Stat(targetAbs)
	if err != nil || st.Size() == 0 {
		_ = os.Remove(targetAbs)
		res.Message = "转码未产出有效文件"
		return res
	}
	sizeKB := st.Size() / 1024
	res.SizeKB = sizeKB

	// 3) 查重与写库，用命名锁串行化
	//
	// media 表没有 (name, folderid) 唯一索引，且不允许新建索引（R1 红线），
	// 只能用 GET_LOCK 在应用层保证「查重 + 写入」的原子性（缺陷 D-28）。
	unlock, err := u.lock(ctx, fmt.Sprintf("htweb_media_upload_%d", folderID))
	if err != nil {
		_ = os.Remove(targetAbs)
		res.Message = err.Error()
		return res
	}
	defer unlock()

	var existingID int64
	var oldFile sql.NullString
	err = u.db.QueryRowContext(ctx,
		`SELECT id, filename FROM media WHERE name = ? AND folderid = ? ORDER BY id DESC LIMIT 1`,
		baseName, folderID).Scan(&existingID, &oldFile)

	switch {
	case errors.Is(err, sql.ErrNoRows):
		r, insErr := u.db.ExecContext(ctx, `
			INSERT INTO media (name, size, typeid, priority, filename, folderid,
			                   timelength, channel, sample, bitrate, userid)
			VALUES (?, ?, 'mp3', 0, ?, ?, 0, 0, 0, 0, ?)`,
			baseName, sizeKB, targetRel, folderID, user.ID)
		if insErr != nil {
			_ = os.Remove(targetAbs)
			res.Message = "写入媒体记录失败: " + insErr.Error()
			return res
		}
		id, _ := r.LastInsertId()
		res.MediaID = id
		res.Status = "created"

	case err != nil:
		_ = os.Remove(targetAbs)
		res.Message = "查重失败: " + err.Error()
		return res

	default:
		// 同名覆盖：保持原 id 与 userid 不变，只替换文件与需要重算的字段。
		// timelength/sample/bitrate 重置为 0，等后台重新扫描（契约 C-06）。
		_, upErr := u.db.ExecContext(ctx, `
			UPDATE media SET size = ?, filename = ?, timelength = 0, sample = 0, bitrate = 0
			WHERE id = ?`, sizeKB, targetRel, existingID)
		if upErr != nil {
			_ = os.Remove(targetAbs)
			res.Message = "更新媒体记录失败: " + upErr.Error()
			return res
		}
		res.MediaID = existingID
		res.Status = "overwritten"
		// 旧物理文件在库更新成功后才删，且跳过 id=1（BR-25）
		if existingID > 1 && oldFile.Valid && oldFile.String != "" &&
			oldFile.String != targetRel && oldFile.String != "none" && oldFile.String != "tts" {
			if p, e := u.safePath(oldFile.String); e == nil {
				_ = os.Remove(p)
			}
		}
	}

	return res
}

// transcode 调用 ffmpeg 转码。
//
// 所有参数以独立 argv 传入，绝不拼接命令行字符串 ——
// 旧系统用字符串拼 ffmpeg 命令，构成命令注入（缺陷 D-03）。
func (u *Uploader) transcode(ctx context.Context, srcAbs, dstAbs, sampleRate string) error {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Minute)
	defer cancel()

	// 与旧系统等价的滤镜链：源音频后接 2 秒静音再拼接
	filter := fmt.Sprintf("[0:0][1:0]concat=n=2:v=0:a=1[a]")
	args := []string{
		"-hide_banner", "-loglevel", "error",
		"-i", srcAbs,
		"-f", "lavfi", "-t", "2", "-i",
		fmt.Sprintf("anullsrc=r=%s:cl=stereo", sampleRate),
		"-filter_complex", filter,
		"-map", "[a]",
		"-b:a", "128k",
		"-ar", sampleRate,
		"-ac", "2",
		"-y", dstAbs,
	}

	cmd := exec.CommandContext(ctx, u.ffmpegPath, args...)
	var stderr strings.Builder
	cmd.Stderr = &stderr
	if err := cmd.Run(); err != nil {
		msg := strings.TrimSpace(stderr.String())
		if len(msg) > 300 {
			msg = msg[:300]
		}
		return fmt.Errorf("%w: %s", err, msg)
	}
	return nil
}

// ---------- F-07 删除媒体 ----------

// BlockedMedia 说明某条媒体为什么不能删。
type BlockedMedia struct {
	ID      int64  `json:"id"`
	Name    string `json:"name"`
	Reason  string `json:"reason"`
	RefName string `json:"refName,omitempty"`
}

type DeleteResult struct {
	Deleted           []int64        `json:"deleted"`
	DeletedCount      int            `json:"deletedCount"`
	Blocked           []BlockedMedia `json:"blocked"`
	AffectedFolderIDs []int64        `json:"-"`
	FilesToRemove     []string       `json:"-"`
}

// Delete 删除指定媒体。
//
// 校验链四项（BR-51），任一命中即阻断该条：
//
//	mediaoftask / shortcutkeytask / alarmgroupmap / playbelloftask
//
// 相比旧系统的关键修复：
//   - 「清空文件夹」走完全相同的校验链，不再是无校验裸删（缺陷 I-02）
//   - 物理文件在事务提交后才删除，回滚不会留下「记录还在但文件没了」
//   - camer_alarmofmedia 是级联清理而非阻断（BR-52）
func (s *Service) Delete(ctx context.Context, u *auth.User, ids []int64) (*DeleteResult, error) {
	out := &DeleteResult{Deleted: []int64{}, Blocked: []BlockedMedia{}}
	if len(ids) == 0 {
		return out, nil
	}

	type row struct {
		id       int64
		name     string
		filename string
		folderID int64
		userID   int64
	}
	infos := make(map[int64]row, len(ids))

	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx,
		`SELECT id, name, filename, folderid, COALESCE(userid,0) FROM media WHERE id IN (`+ph+`)`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询媒体: %w", err)
	}
	defer rs.Close()
	for rs.Next() {
		var r row
		var fn sql.NullString
		if err := rs.Scan(&r.id, &r.name, &fn, &r.folderID, &r.userID); err != nil {
			return nil, err
		}
		r.filename = fn.String
		infos[r.id] = r
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}

	blockedRefs, err := s.mediaRefs(ctx, ids)
	if err != nil {
		return nil, err
	}

	var deletable []int64
	for _, id := range ids {
		info, ok := infos[id]
		if !ok {
			out.Blocked = append(out.Blocked, BlockedMedia{ID: id, Reason: "NOT_FOUND"})
			continue
		}
		if ref, bad := blockedRefs[id]; bad {
			out.Blocked = append(out.Blocked, BlockedMedia{
				ID: id, Name: info.name, Reason: ref.RefType, RefName: ref.RefName,
			})
			continue
		}
		// 系统预置文件夹内的媒体仅超级管理员可删（BR-53）
		if info.folderID <= systemFolderMaxID && !u.IsAdmin {
			out.Blocked = append(out.Blocked, BlockedMedia{
				ID: id, Name: info.name, Reason: "SYSTEM_FOLDER_NO_PERMISSION",
			})
			continue
		}
		// 普通用户只能删自己上传的媒体，与列表可见范围口径一致（BR-30）
		if !u.IsAdmin && info.userID != u.ID {
			out.Blocked = append(out.Blocked, BlockedMedia{
				ID: id, Name: info.name, Reason: "NO_PERMISSION",
			})
			continue
		}
		deletable = append(deletable, id)
	}

	if len(deletable) == 0 {
		return out, nil
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	dph, dargs := placeholders(deletable)
	// 级联清理（不阻断删除的那些关联）
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM camer_alarmofmedia WHERE mediaid IN (`+dph+`)`, dargs...); err != nil {
		return nil, fmt.Errorf("清理摄像机报警关联: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM media WHERE id IN (`+dph+`)`, dargs...); err != nil {
		return nil, fmt.Errorf("删除媒体记录: %w", err)
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}

	out.Deleted = deletable
	out.DeletedCount = len(deletable)
	for _, id := range deletable {
		info := infos[id]
		out.AffectedFolderIDs = append(out.AffectedFolderIDs, info.folderID)
		// media.id = 1 是系统内置提示音，物理文件永不删除（BR-54）
		if id > 1 && info.filename != "" && info.filename != "none" && info.filename != "tts" {
			out.FilesToRemove = append(out.FilesToRemove, info.filename)
		}
	}
	return out, nil
}

// ClearFolder 清空某个文件夹下的媒体。
//
// 这是对旧系统最高危缺陷的修复：旧版「删除」按钮在未勾选任何媒体时，
// 行为是删除当前文件夹的全部媒体，且该路径**完全不做引用校验、
// 不做系统库权限校验、不解锁表、不发后台通知**（缺陷 I-01 / I-02）。
//
// 新版把它拆成独立接口，走与逐条删除完全相同的校验链，
// 并要求调用方传入与文件夹名完全一致的确认文本。
func (s *Service) ClearFolder(ctx context.Context, u *auth.User, folderID int64, confirmName string) (*DeleteResult, error) {
	// 权限闸门必须排在确认文本校验之前。
	// 反过来的话，「确认文本与文件夹名称不一致」这句报错本身就是一个
	// 目录名猜测预言机 —— 猜对了才会进入后面的权限判断。
	if err := s.assertFolderVisible(ctx, u, folderID); err != nil {
		return nil, err
	}

	var name string
	err := s.db.QueryRowContext(ctx,
		`SELECT name FROM filefolder WHERE id = ? LIMIT 1`, folderID).Scan(&name)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询文件夹: %w", err)
	}
	if strings.TrimSpace(confirmName) != name {
		return nil, fmt.Errorf("确认文本与文件夹名称不一致")
	}

	rs, err := s.db.QueryContext(ctx, `SELECT id FROM media WHERE folderid = ?`, folderID)
	if err != nil {
		return nil, fmt.Errorf("查询目录内媒体: %w", err)
	}
	defer rs.Close()
	var ids []int64
	for rs.Next() {
		var id int64
		if err := rs.Scan(&id); err != nil {
			return nil, err
		}
		ids = append(ids, id)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}
	return s.Delete(ctx, u, ids)
}

// PreviewDelete 只做校验不做删除，用于删除前向用户展示影响面。
func (s *Service) PreviewDelete(ctx context.Context, u *auth.User, ids []int64) (map[string]interface{}, error) {
	refs, err := s.mediaRefs(ctx, ids)
	if err != nil {
		return nil, err
	}

	ph, args := placeholders(ids)
	rs, err := s.db.QueryContext(ctx,
		`SELECT id, name, folderid, COALESCE(userid,0) FROM media WHERE id IN (`+ph+`)`, args...)
	if err != nil {
		return nil, fmt.Errorf("查询媒体: %w", err)
	}
	defer rs.Close()

	type item struct {
		ID   int64  `json:"id"`
		Name string `json:"name"`
	}
	deletable := []item{}
	blocked := []BlockedMedia{}

	for rs.Next() {
		var id, folderID, userID int64
		var name string
		if err := rs.Scan(&id, &name, &folderID, &userID); err != nil {
			return nil, err
		}
		if ref, bad := refs[id]; bad {
			blocked = append(blocked, BlockedMedia{ID: id, Name: name, Reason: ref.RefType, RefName: ref.RefName})
			continue
		}
		if folderID <= systemFolderMaxID && !u.IsAdmin {
			blocked = append(blocked, BlockedMedia{ID: id, Name: name, Reason: "SYSTEM_FOLDER_NO_PERMISSION"})
			continue
		}
		if !u.IsAdmin && userID != u.ID {
			blocked = append(blocked, BlockedMedia{ID: id, Name: name, Reason: "NO_PERMISSION"})
			continue
		}
		deletable = append(deletable, item{ID: id, Name: name})
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}
	return map[string]interface{}{"deletable": deletable, "blocked": blocked}, nil
}

// IsRecordLibrary 判断目标目录是否隶属于录音媒体库（顶层 id=5）。
// 录音库只允许试听/下载/删除，禁止上传与改目录结构（手册 §3.3.7）。
func (s *Service) IsRecordLibrary(ctx context.Context, folderID int64) (bool, error) {
	top, err := s.topLibrary(ctx, folderID)
	if err != nil {
		return false, err
	}
	return top == 5, nil
}

// mediaRefs 批量查出哪些媒体被引用，以及引用来源。
func (s *Service) mediaRefs(ctx context.Context, ids []int64) (map[int64]struct{ RefType, RefName string }, error) {
	out := make(map[int64]struct{ RefType, RefName string })
	ph, args := placeholders(ids)

	queries := []struct{ refType, sql string }{
		{"IN_USE_TASK", `SELECT mt.mediaid, COALESCE(t.taskname,'') FROM mediaoftask mt
		                 LEFT JOIN task t ON t.taskid = mt.taskid WHERE mt.mediaid IN (` + ph + `)`},
		{"IN_USE_SHORTCUT", `SELECT mediaid, COALESCE(keyname,'') FROM shortcutkeytask WHERE mediaid IN (` + ph + `)`},
		{"IN_USE_ALARM", `SELECT mediaid, COALESCE(info,'') FROM alarmgroupmap WHERE mediaid IN (` + ph + `)`},
		{"IN_USE_BELL", `SELECT bellid, COALESCE(lessonname,'') FROM playbelloftask WHERE bellid IN (` + ph + `)`},
	}
	for _, q := range queries {
		rows, err := s.db.QueryContext(ctx, q.sql, args...)
		if err != nil {
			return nil, fmt.Errorf("引用校验(%s): %w", q.refType, err)
		}
		for rows.Next() {
			var id int64
			var refName string
			if err := rows.Scan(&id, &refName); err != nil {
				rows.Close()
				return nil, err
			}
			if _, exists := out[id]; !exists {
				out[id] = struct{ RefType, RefName string }{q.refType, refName}
			}
		}
		rows.Close()
	}
	return out, nil
}

// RemoveFiles 删除物理文件。仅在事务提交成功后调用。
func (s *Service) RemoveFiles(rels []string) {
	for _, rel := range rels {
		p, err := s.PhysicalPath(rel)
		if err != nil {
			continue
		}
		_ = os.Remove(p)
	}
}

// ---------- 工具 ----------

func (u *Uploader) lock(ctx context.Context, name string) (func(), error) {
	var ok sql.NullInt64
	if err := u.db.QueryRowContext(ctx, `SELECT GET_LOCK(?, 5)`, name).Scan(&ok); err != nil {
		return nil, fmt.Errorf("获取上传锁失败: %w", err)
	}
	if !ok.Valid || ok.Int64 != 1 {
		return nil, fmt.Errorf("该目录正有其它上传在进行，请稍后重试")
	}
	return func() {
		_, _ = u.db.ExecContext(context.Background(), `SELECT RELEASE_LOCK(?)`, name)
	}, nil
}

func (u *Uploader) safePath(rel string) (string, error) {
	root := filepath.Clean(u.root)
	full := filepath.Clean(filepath.Join(root, rel))
	if !strings.HasPrefix(full, root+string(filepath.Separator)) {
		return "", fmt.Errorf("非法路径")
	}
	return full, nil
}

// writeLimited 把 src 写入 path，超过 limit 立即中止，防止磁盘被撑满。
func writeLimited(path string, src io.Reader, limit int64) (int64, error) {
	f, err := os.OpenFile(path, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0o640)
	if err != nil {
		return 0, err
	}
	defer f.Close()

	n, err := io.Copy(f, io.LimitReader(src, limit+1))
	if err != nil {
		return n, err
	}
	if n > limit {
		return n, ErrTooLarge
	}
	return n, nil
}

// newMediaFileID 生成与旧系统同样式的文件名主体：unix 秒 + 随机数。
// 旧版 PHP 是 time() . mt_rand(1,1000000)，现网文件名形如 1617155672594644。
func newMediaFileID() string {
	return fmt.Sprintf("%d%d", time.Now().Unix(), rand.Intn(1000000)+1)
}

func randomToken() string {
	return fmt.Sprintf("%d%d", time.Now().UnixNano(), rand.Intn(100000))
}

func placeholders(ids []int64) (string, []interface{}) {
	if len(ids) == 0 {
		return "NULL", nil
	}
	ph := strings.TrimSuffix(strings.Repeat("?,", len(ids)), ",")
	args := make([]interface{}, len(ids))
	for i, v := range ids {
		args[i] = v
	}
	return ph, args
}
