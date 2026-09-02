package main

import (
	"errors"
	"net/http"
	"strconv"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/store"
	"htweb/internal/user"
)

// failUser 把业务错误映射成合适的响应码。
// 「系统对象受保护」「重名」这类是用户可理解的操作错误，不能当 500 报。
func failUser(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, user.ErrNotFound):
		httpx.Fail(w, httpx.CodeNotFound, err.Error())
	case errors.Is(err, user.ErrNoPermission):
		httpx.Fail(w, httpx.CodeForbidden, err.Error())
	case errors.Is(err, user.ErrSystemGroup),
		errors.Is(err, user.ErrSystemUser),
		errors.Is(err, user.ErrNotRegistered),
		errors.Is(err, user.ErrNameUsed),
		errors.Is(err, user.ErrWindExhausted):
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
	default:
		// 参数校验类错误也走这里，统一按 400 返回原文
		if isValidationErr(err) {
			httpx.Fail(w, httpx.CodeBadRequest, err.Error())
			return
		}
		httpx.Internal(w, action, err)
	}
}

// isValidationErr 判断是否属于「给用户看的校验提示」。
// 这些错误由 user 包用 fmt.Errorf 直接构造，没有哨兵值可比，
// 用前缀关键字识别；识别不出的一律按内部错误处理，不会泄露 SQL 细节。
func isValidationErr(err error) bool {
	msg := err.Error()
	for _, kw := range []string{
		"不能为空", "过长", "长度", "必须", "不一致", "已取消", "不可修改",
		"未选择", "不能删除", "不能停用", "繁忙",
	} {
		if strings.Contains(msg, kw) {
			return true
		}
	}
	return false
}

// ---------- 用户组 ----------

func (a *app) handleGroupList(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	list, total, err := a.users.ListGroups(r.Context(), user.GroupListQuery{
		Keyword: strings.TrimSpace(q.Get("keyword")),
		OrderBy: q.Get("orderBy"),
		Order:   q.Get("order"),
		Pager:   store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18)),
	})
	if err != nil {
		httpx.Internal(w, "查询用户组", err)
		return
	}
	if list == nil {
		list = []user.Group{}
	}
	httpx.OK(w, map[string]interface{}{
		"list": list, "pageNum": atoiDefault(q.Get("pageNum"), 1),
		"pageSize": atoiDefault(q.Get("pageSize"), 18), "total": total,
	})
}

type groupReq struct {
	Name         string      `json:"name"`
	Info         string      `json:"info"`
	GroupLevel   int         `json:"groupLevel"`
	PriorityBase int         `json:"priorityBase"`
	Rights       auth.Rights `json:"rights"`
}

func (in groupReq) toInput() user.GroupInput {
	return user.GroupInput{
		Name: in.Name, Info: in.Info,
		GroupLevel: in.GroupLevel, PriorityBase: in.PriorityBase,
		Rights: in.Rights,
	}
}

func (a *app) handleGroupCreate(w http.ResponseWriter, r *http.Request) {
	var in groupReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	id, err := a.users.CreateGroup(r.Context(), in.toInput())
	if err != nil {
		failUser(w, "新建用户组", err)
		return
	}
	httpx.OK(w, map[string]interface{}{"id": id})
}

func (a *app) handleGroupUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := pathID(w, r)
	if !ok {
		return
	}
	var in groupReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	recalc, err := a.users.UpdateGroup(r.Context(), id, in.toInput())
	if err != nil {
		failUser(w, "修改用户组", err)
		return
	}
	// 把优先级重算结果回给前端，让用户看到「顺带改了 N 个任务」
	httpx.OK(w, map[string]interface{}{"priorityRecalc": recalc})
}

func (a *app) handleGroupDeletePreview(w http.ResponseWriter, r *http.Request) {
	id, ok := pathID(w, r)
	if !ok {
		return
	}
	impact, err := a.users.PreviewDeleteGroup(r.Context(), id)
	if err != nil {
		failUser(w, "预览删除影响", err)
		return
	}
	httpx.OK(w, impact)
}

func (a *app) handleGroupDelete(w http.ResponseWriter, r *http.Request) {
	id, ok := pathID(w, r)
	if !ok {
		return
	}
	var in struct {
		ConfirmName string `json:"confirmName"`
	}
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	impact, err := a.users.DeleteGroup(r.Context(), id, in.ConfirmName)
	if err != nil {
		failUser(w, "删除用户组", err)
		return
	}
	httpx.OK(w, impact)
}

func (a *app) handleGroupOptions(w http.ResponseWriter, r *http.Request) {
	list, err := a.users.GroupOptions(r.Context())
	if err != nil {
		httpx.Internal(w, "查询用户组选项", err)
		return
	}
	httpx.OK(w, list)
}

// ---------- 用户 ----------

func (a *app) handleUserList(w http.ResponseWriter, r *http.Request) {
	u := auth.From(r.Context())
	q := r.URL.Query()
	pageNum := atoiDefault(q.Get("pageNum"), 1)
	pageSize := atoiDefault(q.Get("pageSize"), 18)

	list, total, err := a.users.ListUsers(r.Context(), u, user.UserListQuery{
		Keyword: strings.TrimSpace(q.Get("keyword")),
		OrderBy: q.Get("orderBy"),
		Order:   q.Get("order"),
		Pager:   store.NewPager(pageNum, pageSize),
	})
	if err != nil {
		httpx.Internal(w, "查询用户", err)
		return
	}
	if list == nil {
		list = []user.User{}
	}

	// scopeNote 让普通用户明白「为什么只看到自己一条」（BR-107）
	note := ""
	if u.ID != 1 {
		note = "当前账号只能查看自己的信息"
	}
	httpx.OK(w, map[string]interface{}{
		"list": list, "pageNum": pageNum, "pageSize": pageSize,
		"total": total, "scopeNote": note,
	})
}

func (a *app) handleUserGet(w http.ResponseWriter, r *http.Request) {
	id, ok := pathID(w, r)
	if !ok {
		return
	}
	d, err := a.users.GetUser(r.Context(), auth.From(r.Context()), id)
	if err != nil {
		failUser(w, "查询用户", err)
		return
	}
	httpx.OK(w, d)
}

type userReq struct {
	Username         string              `json:"username"`
	Password         string              `json:"password"`
	ConfirmPassword  string              `json:"confirmPassword"`
	UsergroupID      int64               `json:"usergroupId"`
	Info             string              `json:"info"`
	EnableCtrlWind   bool                `json:"enableCtrlwind"`
	EnableSubWind    bool                `json:"enableSubwind"`
	EnableCameraWind bool                `json:"enableCamerawind"`
	Serials          []string            `json:"serials"`
	Terminals        []user.TerminalBind `json:"terminals"`
}

func (a *app) handleUserCreate(w http.ResponseWriter, r *http.Request) {
	var in userReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.users.CreateUser(r.Context(), user.CreateUserInput{
		Username: in.Username, Password: in.Password, ConfirmPassword: in.ConfirmPassword,
		UsergroupID: in.UsergroupID, Info: in.Info,
		EnableCtrlWind: in.EnableCtrlWind, EnableSubWind: in.EnableSubWind,
		EnableCameraWind: in.EnableCameraWind,
		Serials:          in.Serials, Terminals: in.Terminals,
	})
	if err != nil {
		failUser(w, "新建用户", err)
		return
	}
	httpx.OK(w, res)
}

func (a *app) handleUserUpdate(w http.ResponseWriter, r *http.Request) {
	id, ok := pathID(w, r)
	if !ok {
		return
	}
	cur := auth.From(r.Context())
	// 非超管只能改自己（与列表可见范围口径一致）
	if cur.ID != 1 && cur.ID != id {
		httpx.Fail(w, httpx.CodeForbidden, "无权修改其他用户")
		return
	}

	var in userReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	// password 为空串 = 不改密码。前端表单必须把「未填写」原样传空串，
	// 不能补默认值，否则会退化成旧版 D-58 那个把密码改成 md5("") 的行为。
	recalc, err := a.users.UpdateUser(r.Context(), id, user.UpdateUserInput{
		Username: in.Username, Password: in.Password, ConfirmPassword: in.ConfirmPassword,
		UsergroupID: in.UsergroupID, Info: in.Info,
		EnableCtrlWind: in.EnableCtrlWind, EnableSubWind: in.EnableSubWind,
		EnableCameraWind: in.EnableCameraWind,
		Serials:          in.Serials, Terminals: in.Terminals,
	})
	if err != nil {
		failUser(w, "修改用户", err)
		return
	}
	httpx.OK(w, map[string]interface{}{
		"priorityRecalc":  recalc,
		"passwordChanged": in.Password != "",
	})
}

func (a *app) handleUserEnable(w http.ResponseWriter, r *http.Request) {
	id, ok := pathID(w, r)
	if !ok {
		return
	}
	var in struct {
		Enable bool `json:"enable"`
	}
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	affected, err := a.users.SetEnable(r.Context(), auth.From(r.Context()), id, in.Enable)
	if err != nil {
		failUser(w, "切换用户状态", err)
		return
	}
	httpx.OK(w, map[string]interface{}{
		"enable":        in.Enable,
		"affectedTasks": affected,
	})
}

func (a *app) handleUserDeletePreview(w http.ResponseWriter, r *http.Request) {
	ids, ok := queryIDs(w, r)
	if !ok {
		return
	}
	impact, err := a.users.PreviewDeleteUser(r.Context(), ids)
	if err != nil {
		failUser(w, "预览删除影响", err)
		return
	}
	httpx.OK(w, impact)
}

func (a *app) handleUserDelete(w http.ResponseWriter, r *http.Request) {
	var in struct {
		IDs       []int64 `json:"ids"`
		Confirmed bool    `json:"confirmed"`
	}
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if !in.Confirmed {
		httpx.Fail(w, httpx.CodeBadRequest, "缺少确认标记")
		return
	}
	impact, err := a.users.DeleteUsers(r.Context(), auth.From(r.Context()), in.IDs)
	if err != nil {
		failUser(w, "删除用户", err)
		return
	}
	httpx.OK(w, impact)
}

func (a *app) handleTerminalOptions(w http.ResponseWriter, r *http.Request) {
	forUser, _ := strconv.ParseInt(r.URL.Query().Get("userId"), 10, 64)
	list, err := a.users.TerminalOptions(r.Context(), forUser)
	if err != nil {
		httpx.Internal(w, "查询终端选项", err)
		return
	}
	httpx.OK(w, list)
}

func (a *app) handleWindCapacity(w http.ResponseWriter, r *http.Request) {
	info, err := a.users.WindCapacity(r.Context())
	if err != nil {
		httpx.Internal(w, "查询分控名额", err)
		return
	}
	httpx.OK(w, info)
}

// ---------- 小工具 ----------

func pathID(w http.ResponseWriter, r *http.Request) (int64, bool) {
	id, err := strconv.ParseInt(r.PathValue("id"), 10, 64)
	if err != nil || id <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "ID 非法")
		return 0, false
	}
	return id, true
}

func queryIDs(w http.ResponseWriter, r *http.Request) ([]int64, bool) {
	return parseIDList(w, r.URL.Query().Get("ids"))
}
