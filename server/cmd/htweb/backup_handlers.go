package main

import (
	"errors"
	"fmt"
	"io"
	"net/http"
	"os"
	"path/filepath"
	"strings"

	"htweb/internal/audit"
	"htweb/internal/auth"
	"htweb/internal/backup"
	"htweb/internal/httpx"
)

// 备份与恢复 F-58 ~ F-60。
//
// 全部限超级管理员：旧版这三个入口
// （backup_restore_form.php / restore_backup_file.php / del_backup_file.php）
// **一个权限校验都没有**（D-222 / D-229 / D-238），
// 其中恢复那个等价于任意 SQL 执行，是全系统最高危的漏洞。

func failBackup(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, backup.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, err.Error())
	case errors.Is(err, backup.ErrBadName):
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
	case errors.Is(err, backup.ErrIncompatible):
		httpx.Fail(w, httpx.CodeConflict, err.Error())
	default:
		if isBackupValidationErr(err) {
			httpx.Fail(w, httpx.CodeBadRequest, err.Error())
			return
		}
		httpx.Internal(w, action, err)
	}
}

func isBackupValidationErr(err error) bool {
	msg := err.Error()
	for _, kw := range []string{
		"不一致", "不合法", "不存在", "已取消", "必须", "只能是", "缺少", "不符",
	} {
		if strings.Contains(msg, kw) {
			return true
		}
	}
	return false
}

func (a *app) handleBackupList(w http.ResponseWriter, r *http.Request) {
	list, err := a.backups.List(r.Context())
	if err != nil {
		failBackup(w, "查询备份列表", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"list": list})
}

type backupCreateReq struct {
	Label string `json:"label"`
}

func (a *app) handleBackupCreate(w http.ResponseWriter, r *http.Request) {
	var in backupCreateReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	u := auth.From(r.Context())
	res, err := a.backups.Create(r.Context(), in.Label, u.Username)
	if err != nil {
		failBackup(w, "创建备份", err)
		return
	}
	httpx.OK(w, res)
}

// handleBackupUpload 收下一个上传的备份包，落进备份目录。
//
// 落地之后它和「本机自己备的包」完全等价：同一个列表、同一套还原前置检查、
// 同一条恢复路径。详见 backup/upload.go。
//
// ⚠ 用 MultipartReader 流式读，**不用 ParseMultipartForm** ——
//
//	后者会先把整个请求体读进内存/临时文件再交给你，一个 80MB 的包
//	就是 80MB 的内存峰值。这里边读边往磁盘写，内存占用恒定。
func (a *app) handleBackupUpload(w http.ResponseWriter, r *http.Request) {
	mr, err := r.MultipartReader()
	if err != nil {
		httpx.Fail(w, httpx.CodeBadRequest, "请求不是 multipart/form-data 上传")
		return
	}
	for {
		part, err := mr.NextPart()
		if err == io.EOF {
			break
		}
		if err != nil {
			httpx.Fail(w, httpx.CodeBadRequest, "读取上传数据失败："+err.Error())
			return
		}
		if part.FormName() != "file" || part.FileName() == "" {
			_ = part.Close()
			continue
		}
		res, err := a.backups.Upload(r.Context(), part, part.FileName())
		_ = part.Close()
		if err != nil {
			// 这里的错误都是要给操作员看的（不是 zip、格式版本太高、太大…），
			// 不能折成 500「内部错误」
			httpx.Fail(w, httpx.CodeBadRequest, err.Error())
			return
		}
		u := auth.From(r.Context())
		a.auditor.Write(r.Context(), u.Username,
			fmt.Sprintf("上传备份包 %s", res.Name), audit.ClientIP(r))
		httpx.OK(w, res)
		return
	}
	httpx.Fail(w, httpx.CodeBadRequest, "没有收到文件，表单字段名必须是 file")
}

func (a *app) handleBackupDownload(w http.ResponseWriter, r *http.Request) {
	p, err := a.backups.Path(r.PathValue("name"))
	if err != nil {
		failBackup(w, "下载备份", err)
		return
	}
	f, err := os.Open(p)
	if err != nil {
		failBackup(w, "下载备份", err)
		return
	}
	defer f.Close()
	st, err := f.Stat()
	if err != nil {
		failBackup(w, "下载备份", err)
		return
	}
	w.Header().Set("Content-Type", "application/zip")
	w.Header().Set("Content-Disposition",
		`attachment; filename="`+filepath.Base(p)+`"`)
	http.ServeContent(w, r, filepath.Base(p), st.ModTime(), f)
}

type backupDeleteReq struct {
	Name      string `json:"name"`
	Confirmed bool   `json:"confirmed"`
}

func (a *app) handleBackupDelete(w http.ResponseWriter, r *http.Request) {
	var in backupDeleteReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !in.Confirmed {
		httpx.Fail(w, httpx.CodeBadRequest, "删除未确认")
		return
	}
	if err := a.backups.Delete(in.Name); err != nil {
		failBackup(w, "删除备份", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"name": in.Name})
}

func (a *app) handleBackupPrecheck(w http.ResponseWriter, r *http.Request) {
	res, err := a.backups.Precheck(r.Context(), r.PathValue("name"))
	if err != nil {
		failBackup(w, "校验备份包", err)
		return
	}
	httpx.OK(w, res)
}

type backupRestoreReq struct {
	Name         string `json:"name"`
	ConfirmText  string `json:"confirmText"`
	SafetyBackup bool   `json:"safetyBackup"`
	RestoreMedia bool   `json:"restoreMedia"`
}

func (a *app) handleBackupRestore(w http.ResponseWriter, r *http.Request) {
	var in backupRestoreReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	u := auth.From(r.Context())

	// 恢复本身要留痕，而且必须在恢复**之前**写 ——
	// 恢复会把 log 表也一起换成备份里的内容，之后再写就没意义了。
	a.auditor.Write(r.Context(), u.Username,
		"恢复备份包："+in.Name, audit.ClientIP(r))

	res, err := a.backups.Restore(r.Context(), backup.RestoreInput{
		Name:         in.Name,
		ConfirmText:  in.ConfirmText,
		SafetyBackup: in.SafetyBackup,
		RestoreMedia: in.RestoreMedia,
		Operator:     u.Username,
	})
	if err != nil {
		failBackup(w, "恢复备份", err)
		return
	}

	// 整库数据被换掉了：当前所有会话指向的可能是已经不存在的用户（BR-277）
	a.authMgr.InvalidateAll()
	res.SessionsInvalided = true

	// ⚠ 这里**故意不发** server?state=1。
	//
	// 旧版恢复完就发它（send_socket_restart），我照做了一次，
	// 现网实测的结果是**整台服务器立刻重启**，日志前后差 1 秒。
	// 这是一台随时可能要打铃的广播服务器，什么时候重启必须由人来定。
	// 改成在响应里如实告知：后台服务仍持有恢复前的内存数据，需要重启才生效。
	res.BackendNeedsRestart = true
	res.RestartHint = "后台广播服务内存里仍是恢复前的任务与媒体清单，" +
		"需要重启后台服务（或整机）才会加载恢复后的数据。请自行选择时机执行。"

	httpx.OK(w, res)
}
