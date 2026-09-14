package main

import (
	"encoding/json"
	"errors"
	"net/http"
	"os"
	"strconv"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/mapview"
)

// 资源管理 → 地图。
//
// 读（列底图、看图上有哪些终端、取底图图片）只要登录：与终端列表同口径，
// 可见范围由 userterminal 在 SQL 里收敛，没绑终端的人看到的是一张空图。
//
// 写（传底图、摆终端、拿掉终端）走 terminalpriv：摆终端本质上是终端配置，
// 与「终端管理」那一页同一把钥匙，不另起一套。

// failMap 把 service 的错误翻成合适的 HTTP 语义。
//
// ⚠ ErrTableMissing 单独给一条：建表脚本要管理员单独跑，现场很可能先升了程序
// 忘了跑脚本。那时候界面上该说「去执行 db/map_tables.sql」，
// 而不是甩一句 "Error 1146: Table 'audioserver.map_image' doesn't exist"。
func failMap(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, mapview.ErrTableMissing):
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
	case errors.Is(err, mapview.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, err.Error())
	case errors.Is(err, mapview.ErrNoPermission):
		httpx.Fail(w, httpx.CodeForbidden, err.Error())
	case errors.Is(err, mapview.ErrBadImage):
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
	default:
		httpx.Internal(w, action, err)
	}
}

func mapIDOf(w http.ResponseWriter, r *http.Request) (int64, bool) {
	id, err := strconv.ParseInt(r.PathValue("id"), 10, 64)
	if err != nil || id <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "底图 ID 必须是正整数")
		return 0, false
	}
	return id, true
}

func (a *app) handleMapList(w http.ResponseWriter, r *http.Request) {
	list, err := a.maps.List(r.Context())
	if err != nil {
		failMap(w, "查询底图", err)
		return
	}
	httpx.OK(w, list)
}

type mapNameReq struct {
	Name string `json:"name"`
}

func (a *app) handleMapCreate(w http.ResponseWriter, r *http.Request) {
	var in mapNameReq
	if err := json.NewDecoder(r.Body).Decode(&in); err != nil {
		httpx.Fail(w, httpx.CodeBadRequest, "请求体格式错误")
		return
	}
	id, err := a.maps.Create(r.Context(), auth.From(r.Context()), in.Name)
	if err != nil {
		failMap(w, "新建底图", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleMapRename(w http.ResponseWriter, r *http.Request) {
	id, ok := mapIDOf(w, r)
	if !ok {
		return
	}
	var in mapNameReq
	if err := json.NewDecoder(r.Body).Decode(&in); err != nil {
		httpx.Fail(w, httpx.CodeBadRequest, "请求体格式错误")
		return
	}
	if err := a.maps.Rename(r.Context(), id, in.Name); err != nil {
		failMap(w, "重命名底图", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"updated": true})
}

func (a *app) handleMapDelete(w http.ResponseWriter, r *http.Request) {
	id, ok := mapIDOf(w, r)
	if !ok {
		return
	}
	if err := a.maps.Delete(r.Context(), id); err != nil {
		failMap(w, "删除底图", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"deleted": true})
}

// handleMapImageUpload 换底图。multipart，字段名 file。
func (a *app) handleMapImageUpload(w http.ResponseWriter, r *http.Request) {
	id, ok := mapIDOf(w, r)
	if !ok {
		return
	}
	// 24MB：比 service 里 20MB 的图片上限宽一点，留给 multipart 的边界与表单字段。
	r.Body = http.MaxBytesReader(w, r.Body, 24<<20)
	if err := r.ParseMultipartForm(8 << 20); err != nil {
		httpx.Fail(w, httpx.CodeTooLarge, "上传内容过大或格式错误")
		return
	}
	f, fh, err := r.FormFile("file")
	if err != nil {
		httpx.Fail(w, httpx.CodeBadRequest, "未收到图片")
		return
	}
	defer f.Close()

	if err := a.maps.SaveImage(r.Context(), id, fh.Filename, f, fh.Size); err != nil {
		failMap(w, "保存底图", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"saved": true})
}

// handleMapImage 下发底图图片。
//
// 走 RequireAllowQueryToken：<img src> 带不了自定义请求头，只能把令牌放 query，
// 与媒体试听 / 下载同一条路子。
func (a *app) handleMapImage(w http.ResponseWriter, r *http.Request) {
	id, ok := mapIDOf(w, r)
	if !ok {
		return
	}
	path, err := a.maps.ImagePath(r.Context(), id)
	if err != nil {
		failMap(w, "读取底图", err)
		return
	}
	f, err := os.Open(path)
	if err != nil {
		httpx.Fail(w, httpx.CodeFileMissing, "底图文件无法读取")
		return
	}
	defer f.Close()
	fi, err := f.Stat()
	if err != nil {
		httpx.Internal(w, "读取底图信息", err)
		return
	}
	ct := "image/png"
	if strings.HasSuffix(strings.ToLower(path), ".jpg") {
		ct = "image/jpeg"
	}
	w.Header().Set("Content-Type", ct)
	// 底图换了就是新文件名（时间戳），所以这一份可以放心让浏览器长缓存
	w.Header().Set("Cache-Control", "private, max-age=86400")
	http.ServeContent(w, r, "", fi.ModTime(), f)
}

func (a *app) handleMapTerminals(w http.ResponseWriter, r *http.Request) {
	id, ok := mapIDOf(w, r)
	if !ok {
		return
	}
	list, err := a.maps.Placements(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		failMap(w, "查询图上终端", err)
		return
	}
	httpx.OK(w, list)
}

type mapPlaceReq struct {
	TerminalID int64   `json:"terminalId"`
	X          float64 `json:"x"`
	Y          float64 `json:"y"`
}

// handleMapPlace 摆一台终端上去，或挪动已经在图上的那台。
// 同一个接口干两件事 —— 表上有 (mapid, terminalid) 唯一键，走的是 upsert，
// 界面不用区分「新增」和「移动」，拖一下就是一次调用。
func (a *app) handleMapPlace(w http.ResponseWriter, r *http.Request) {
	id, ok := mapIDOf(w, r)
	if !ok {
		return
	}
	var in mapPlaceReq
	if err := json.NewDecoder(r.Body).Decode(&in); err != nil {
		httpx.Fail(w, httpx.CodeBadRequest, "请求体格式错误")
		return
	}
	if in.TerminalID <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "终端 ID 必须是正整数")
		return
	}
	if err := a.maps.Place(r.Context(), auth.From(r.Context()), id, in.TerminalID, in.X, in.Y); err != nil {
		failMap(w, "摆放终端", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"placed": true})
}

type mapRemoveReq struct {
	TerminalIDs []int64 `json:"terminalIds"`
}

func (a *app) handleMapRemove(w http.ResponseWriter, r *http.Request) {
	id, ok := mapIDOf(w, r)
	if !ok {
		return
	}
	var in mapRemoveReq
	if err := json.NewDecoder(r.Body).Decode(&in); err != nil {
		httpx.Fail(w, httpx.CodeBadRequest, "请求体格式错误")
		return
	}
	n, err := a.maps.Remove(r.Context(), id, in.TerminalIDs)
	if err != nil {
		failMap(w, "移除图上终端", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"removed": n})
}
