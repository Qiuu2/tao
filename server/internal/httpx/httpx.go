// Package httpx 提供统一的响应信封与错误码。
//
// 信封格式对齐前端基座 Geeker-Admin（见手册 §4.1.2）：
//
//	{ "code": 200, "msg": "ok", "data": {...} }
//
// 注意三点，写错前端会静默拿不到数据：
//   - 字段是 msg 不是 message
//   - 成功码是 200 不是 0
//   - 分页体字段固定为 {list, pageNum, pageSize, total}（ResPage<T>）
package httpx

import (
	"encoding/json"
	"log"
	"net/http"
)

// 业务错误码。与手册 §4.1.3 保持一致。
const (
	CodeOK = 200

	CodeBadRequest    = 40001
	CodeNameUsed      = 40002
	CodeDepthLimit    = 40003
	CodeUnauthorized  = 401 // 前端拦截器识别此码并跳登录，必须是 401
	CodeBadCredential = 40102
	CodeBadCaptcha    = 40103
	CodeUserDisabled  = 40104
	CodeForbidden     = 40301
	CodeReadOnly      = 40302 // 备机模式
	CodeSystemObject  = 40303
	CodeRecordLocked  = 40304
	CodeNotFound      = 40401
	CodeFileMissing   = 40402
	CodeInUse         = 40901
	CodeConflict      = 40902
	CodeTooLarge      = 41301
	CodeBadType       = 41501
	CodeInternal      = 50001
	CodeTranscode     = 50002
	CodeNotifyFailed  = 50003
)

type Envelope struct {
	Code int         `json:"code"`
	Msg  string      `json:"msg"`
	Data interface{} `json:"data"`
}

// Page 对应前端 ResPage<T>。字段名不可更改。
type Page struct {
	List     interface{} `json:"list"`
	PageNum  int         `json:"pageNum"`
	PageSize int         `json:"pageSize"`
	Total    int64       `json:"total"`
}

func write(w http.ResponseWriter, status int, e Envelope) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.Header().Set("Cache-Control", "no-store")
	w.WriteHeader(status)
	if err := json.NewEncoder(w).Encode(e); err != nil {
		log.Printf("写响应失败: %v", err)
	}
}

func OK(w http.ResponseWriter, data interface{}) {
	write(w, http.StatusOK, Envelope{Code: CodeOK, Msg: "ok", Data: data})
}

// OKPage 返回分页数据。list 为 nil 时会被规整成空数组，
// 避免前端 ProTable 拿到 null 后报错。
func OKPage(w http.ResponseWriter, list interface{}, pageNum, pageSize int, total int64) {
	if list == nil {
		list = []interface{}{}
	}
	OK(w, Page{List: list, PageNum: pageNum, PageSize: pageSize, Total: total})
}

// Fail 返回业务错误。HTTP 状态一律 200，业务码放在 body 里 ——
// 这是 Geeker-Admin 拦截器的工作方式，改成非 200 会被当成网络异常。
// 例外是 401：前端据此跳登录，同样通过 body 的 code 识别。
func Fail(w http.ResponseWriter, code int, msg string) {
	write(w, http.StatusOK, Envelope{Code: code, Msg: msg, Data: nil})
}

// FailData 返回带附加数据的业务错误，例如删除被引用阻断时的冲突明细。
func FailData(w http.ResponseWriter, code int, msg string, data interface{}) {
	write(w, http.StatusOK, Envelope{Code: code, Msg: msg, Data: data})
}

// Internal 记录真实错误到日志，但只向客户端返回笼统信息，避免泄漏内部细节。
func Internal(w http.ResponseWriter, where string, err error) {
	log.Printf("[500] %s: %v", where, err)
	Fail(w, CodeInternal, "服务器内部错误")
}

// DecodeJSON 解析请求体，限制大小防止内存滥用。
func DecodeJSON(w http.ResponseWriter, r *http.Request, dst interface{}) bool {
	r.Body = http.MaxBytesReader(w, r.Body, 1<<20)
	dec := json.NewDecoder(r.Body)
	dec.DisallowUnknownFields()
	if err := dec.Decode(dst); err != nil {
		Fail(w, CodeBadRequest, "请求参数格式错误: "+err.Error())
		return false
	}
	return true
}
