package main

import (
	"errors"
	"net/http"
	"strconv"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/folder"
	"htweb/internal/httpx"
	"htweb/internal/media"
	"htweb/internal/notify"
)

// ---------- 文件夹写操作 ----------

type folderCreateReq struct {
	Name     string `json:"name"`
	ParentID int64  `json:"parentId"`
	// Shared 必须显式提供。旧版表单把共享开关注释掉了，
	// 导致新建恒共享、改名还会把私有目录静默改成共享（缺陷 I-03）。
	Shared *bool `json:"shared"`
}

func (a *app) handleFolderCreate(w http.ResponseWriter, r *http.Request) {
	var in folderCreateReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if in.Shared == nil {
		httpx.Fail(w, httpx.CodeBadRequest, "必须显式指定 shared（是否共享）")
		return
	}

	u := auth.From(r.Context())
	id, err := a.folders.Create(r.Context(), u, folder.CreateInput{
		Name: in.Name, ParentID: in.ParentID, Shared: *in.Shared,
	})
	if err != nil {
		writeFolderErr(w, err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

type folderUpdateReq struct {
	Name   string `json:"name"`
	Shared *bool  `json:"shared"`
}

func (a *app) handleFolderUpdate(w http.ResponseWriter, r *http.Request) {
	id, err := strconv.ParseInt(r.PathValue("id"), 10, 64)
	if err != nil || id <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "文件夹 ID 非法")
		return
	}
	var in folderUpdateReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if in.Shared == nil {
		httpx.Fail(w, httpx.CodeBadRequest, "必须显式指定 shared（是否共享）")
		return
	}

	u := auth.From(r.Context())
	if err := a.folders.Update(r.Context(), u, id, folder.UpdateInput{
		Name: in.Name, Shared: *in.Shared,
	}); err != nil {
		writeFolderErr(w, err)
		return
	}
	httpx.OK(w, nil)
}

func (a *app) handleFolderDeletePreview(w http.ResponseWriter, r *http.Request) {
	ids, ok := parseIDList(w, r.URL.Query().Get("ids"))
	if !ok {
		return
	}
	pre, err := a.folders.Preview(r.Context(), auth.From(r.Context()), ids)
	if err != nil {
		httpx.Internal(w, "预览删除影响", err)
		return
	}
	httpx.OK(w, pre)
}

type deleteReq struct {
	IDs       []int64 `json:"ids"`
	Confirmed bool    `json:"confirmed"`
}

func (a *app) handleFolderDelete(w http.ResponseWriter, r *http.Request) {
	var in deleteReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !in.Confirmed {
		httpx.Fail(w, httpx.CodeBadRequest, "缺少确认标记")
		return
	}
	if len(in.IDs) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "未选择要删除的文件夹")
		return
	}

	u := auth.From(r.Context())
	res, err := a.folders.Delete(r.Context(), u, in.IDs)
	if err != nil {
		writeFolderErr(w, err)
		return
	}

	// 物理文件在事务提交成功之后才删除。
	// 旧版在事务内就 rm，一旦回滚会出现「记录还在但文件没了」。
	a.medias.RemoveFiles(res.FilesToRemove)

	// 必须通知后台服务重新加载，否则它会继续按旧清单播放（BR-26）
	a.notifier.MediaChangedBatch(r.Context(), notify.StateDeleted, res.AffectedFolderIDs)

	httpx.OK(w, map[string]interface{}{
		"deletedFolders":    res.DeletedFolders,
		"deletedMediaCount": res.DeletedMediaCount,
		"blocked":           res.Blocked,
		"notified":          true,
	})
}

func writeFolderErr(w http.ResponseWriter, err error) {
	switch {
	case errors.Is(err, folder.ErrNameUsed):
		httpx.Fail(w, httpx.CodeNameUsed, "同一目录下已存在同名文件夹")
	case errors.Is(err, folder.ErrDepthLimit):
		httpx.Fail(w, httpx.CodeDepthLimit, "文件夹层级最多 3 层，不能在此目录下继续创建")
	case errors.Is(err, folder.ErrSystemFolder):
		httpx.Fail(w, httpx.CodeSystemObject, "系统预置媒体库不可修改或删除")
	case errors.Is(err, folder.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, "文件夹不存在")
	case errors.Is(err, folder.ErrNoPermission):
		httpx.Fail(w, httpx.CodeForbidden, "无权操作该文件夹")
	default:
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
	}
}

// ---------- 媒体写操作 ----------

func (a *app) handleMediaUpload(w http.ResponseWriter, r *http.Request) {
	u := auth.From(r.Context())

	// 限制整个请求体大小，避免超大 multipart 撑爆内存/磁盘
	r.Body = http.MaxBytesReader(w, r.Body, a.cfg.Media.MaxUploadMB<<20+(8<<20))

	if err := r.ParseMultipartForm(16 << 20); err != nil {
		httpx.Fail(w, httpx.CodeTooLarge, "上传内容过大或格式错误")
		return
	}

	folderID, err := strconv.ParseInt(r.FormValue("folderId"), 10, 64)
	if err != nil || folderID <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "folderId 必须是正整数")
		return
	}

	// 目录闸门排在最前：上传原本完全不校验目标目录的归属，
	// 普通用户可以把文件直接写进别人的私有目录（树里根本看不到那个目录）。
	if err := a.medias.AssertFolderVisible(r.Context(), u, folderID); err != nil {
		writeMediaFolderErr(w, err, "校验上传目录权限")
		return
	}

	// 录音媒体库禁止上传（BR-50）：录音是设备自动产生的归档数据
	locked, err := a.medias.IsRecordLibrary(r.Context(), folderID)
	if err != nil {
		httpx.Internal(w, "判断目录类型", err)
		return
	}
	if locked {
		httpx.Fail(w, httpx.CodeRecordLocked, "录音媒体库不允许上传")
		return
	}

	files := r.MultipartForm.File["file"]
	if len(files) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "未收到文件")
		return
	}

	results := make([]media.UploadResult, 0, len(files))
	created := 0
	for _, fh := range files {
		f, err := fh.Open()
		if err != nil {
			results = append(results, media.UploadResult{
				FileName: fh.Filename, Status: "failed", Message: "无法读取上传文件",
			})
			continue
		}
		res := a.uploader.Upload(r.Context(), u, folderID, fh.Filename, f, fh.Size)
		_ = f.Close()
		results = append(results, res)
		if res.Status == "created" || res.Status == "overwritten" {
			created++
		}
	}

	// 有任何一个成功就通知后台重新加载该目录（BR-49）
	if created > 0 {
		a.notifier.MediaChanged(r.Context(), notify.StateAdded, folderID)
	}

	httpx.OK(w, map[string]interface{}{
		"results":  results,
		"notified": created > 0,
	})
}

func (a *app) handleMediaDeletePreview(w http.ResponseWriter, r *http.Request) {
	ids, ok := parseIDList(w, r.URL.Query().Get("ids"))
	if !ok {
		return
	}
	// 预览复用删除的校验链，但不真正执行：这里只调用引用检查部分
	res, err := a.medias.PreviewDelete(r.Context(), auth.From(r.Context()), ids)
	if err != nil {
		httpx.Internal(w, "预览媒体删除", err)
		return
	}
	httpx.OK(w, res)
}

func (a *app) handleMediaDelete(w http.ResponseWriter, r *http.Request) {
	var in deleteReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !in.Confirmed {
		httpx.Fail(w, httpx.CodeBadRequest, "缺少确认标记")
		return
	}
	// 未勾选任何媒体时直接拒绝。
	// 旧版此时的行为是「删除当前文件夹全部媒体」，误点即清空整个目录（缺陷 I-01）。
	if len(in.IDs) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "未选择要删除的媒体")
		return
	}

	u := auth.From(r.Context())
	res, err := a.medias.Delete(r.Context(), u, in.IDs)
	if err != nil {
		httpx.Internal(w, "删除媒体", err)
		return
	}

	a.medias.RemoveFiles(res.FilesToRemove)
	a.notifier.MediaChangedBatch(r.Context(), notify.StateDeleted, res.AffectedFolderIDs)

	httpx.OK(w, map[string]interface{}{
		"deleted":      res.Deleted,
		"deletedCount": res.DeletedCount,
		"blocked":      res.Blocked,
		"notified":     true,
	})
}

type clearReq struct {
	// ConfirmFolderName 必须与目标文件夹名完全一致 —— 高危操作的二次确认
	ConfirmFolderName string `json:"confirmFolderName"`
}

func (a *app) handleMediaClear(w http.ResponseWriter, r *http.Request) {
	id, err := strconv.ParseInt(r.PathValue("id"), 10, 64)
	if err != nil || id <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "文件夹 ID 非法")
		return
	}
	var in clearReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}

	u := auth.From(r.Context())
	res, err := a.medias.ClearFolder(r.Context(), u, id, in.ConfirmFolderName)
	if err != nil {
		if errors.Is(err, media.ErrFolderDenied) {
			writeMediaFolderErr(w, err, "清空目录")
			return
		}
		if errors.Is(err, media.ErrNotFound) {
			httpx.Fail(w, httpx.CodeNotFound, "文件夹不存在")
			return
		}
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
		return
	}

	a.medias.RemoveFiles(res.FilesToRemove)
	a.notifier.MediaChangedBatch(r.Context(), notify.StateDeleted, res.AffectedFolderIDs)

	httpx.OK(w, map[string]interface{}{
		"deleted":      res.Deleted,
		"deletedCount": res.DeletedCount,
		"blocked":      res.Blocked,
		"notified":     true,
	})
}

// writeMediaFolderErr 统一处理媒体侧的目录闸门错误。
//
// 用 403 而不是 404：调用方确实是「无权」，而错误文案刻意不区分
// 目录不存在与无权访问，避免变成存在性探针。
// 非闸门错误按 fallback 描述的上下文报 500。
func writeMediaFolderErr(w http.ResponseWriter, err error, fallback string) {
	if errors.Is(err, media.ErrFolderDenied) {
		httpx.Fail(w, httpx.CodeForbidden, media.ErrFolderDenied.Error())
		return
	}
	httpx.Internal(w, fallback, err)
}

// ---------- 工具 ----------

// parseIDList 解析逗号分隔的 ID 串。
//
// 每个元素都单独解析为整数后再绑定，绝不把原串拼进 SQL ——
// 旧系统直接把 $_GET['id'] 拼进 IN (...)，是最典型的注入面（缺陷 D-01）。
func parseIDList(w http.ResponseWriter, raw string) ([]int64, bool) {
	raw = strings.TrimSpace(raw)
	if raw == "" {
		httpx.Fail(w, httpx.CodeBadRequest, "缺少 ids 参数")
		return nil, false
	}
	parts := strings.Split(raw, ",")
	if len(parts) > 500 {
		httpx.Fail(w, httpx.CodeBadRequest, "一次最多处理 500 项")
		return nil, false
	}
	ids := make([]int64, 0, len(parts))
	for _, p := range parts {
		p = strings.TrimSpace(p)
		if p == "" {
			continue
		}
		v, err := strconv.ParseInt(p, 10, 64)
		if err != nil || v <= 0 {
			httpx.Fail(w, httpx.CodeBadRequest, "ids 含非法值: "+p)
			return nil, false
		}
		ids = append(ids, v)
	}
	if len(ids) == 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "ids 为空")
		return nil, false
	}
	return ids, true
}
