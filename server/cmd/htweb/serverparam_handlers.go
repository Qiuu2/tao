package main

import (
	"errors"
	"fmt"
	"net/http"
	"path/filepath"
	"strings"

	"htweb/internal/audit"
	"htweb/internal/auth"
	"htweb/internal/httpx"
	"htweb/internal/serverparam"
)

// 服务器参数 F-56 / 恢复出厂 F-57。

func failParam(w http.ResponseWriter, action string, err error) {
	switch {
	case errors.Is(err, serverparam.ErrReadOnly):
		httpx.Fail(w, httpx.CodeReadOnly, err.Error())
	default:
		if isParamValidationErr(err) {
			httpx.Fail(w, httpx.CodeBadRequest, err.Error())
			return
		}
		httpx.Internal(w, action, err)
	}
}

func isParamValidationErr(err error) bool {
	msg := err.Error()
	for _, kw := range []string{
		"不能为空", "格式不正确", "必须", "只能是", "之间", "过长", "不能重复",
		"不能为负", "不正确", "不是合法", "请先确认",
	} {
		if strings.Contains(msg, kw) {
			return true
		}
	}
	return false
}

func (a *app) handleServerParamGet(w http.ResponseWriter, r *http.Request) {
	p, err := a.params.Get(r.Context())
	if err != nil {
		failParam(w, "读取服务器参数", err)
		return
	}
	httpx.OK(w, p)
}

type serverParamReq struct {
	Network   serverparam.Network   `json:"network"`
	Ports     serverparam.Ports     `json:"ports"`
	Capacity  serverparam.Capacity  `json:"capacity"`
	Multicast serverparam.Multicast `json:"multicast"`
	HA        serverparam.HA        `json:"ha"`
	Misc      serverparam.Misc      `json:"misc"`
}

func (a *app) handleServerParamSave(w http.ResponseWriter, r *http.Request) {
	var in serverParamReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	res, err := a.params.Save(r.Context(), serverparam.Input{
		Network:   in.Network,
		Ports:     in.Ports,
		Capacity:  in.Capacity,
		Multicast: in.Multicast,
		HA:        in.HA,
		Misc:      in.Misc,
	})
	if err != nil {
		failParam(w, "保存服务器参数", err)
		return
	}
	httpx.OK(w, res)
}

// ---------- 定时重启（:80「服务设置」里的重启设置 / 重启时间）----------
//
// ⚠ 它存在 task 表那条 tasktype = 13 的系统任务上，不是 serverbaseparam 的列。

func (a *app) handleAutoRestartGet(w http.ResponseWriter, r *http.Request) {
	res, err := a.params.GetAutoRestart(r.Context())
	if err != nil {
		failParam(w, "查询定时重启", err)
		return
	}
	httpx.OK(w, res)
}

func (a *app) handleAutoRestartSave(w http.ResponseWriter, r *http.Request) {
	var in serverparam.AutoRestart
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if err := a.params.SaveAutoRestart(r.Context(), in); err != nil {
		failParam(w, "保存定时重启", err)
		return
	}
	res, err := a.params.GetAutoRestart(r.Context())
	if err != nil {
		failParam(w, "查询定时重启", err)
		return
	}
	httpx.OK(w, res)
}

// ---------- 版本设置 ----------

func (a *app) handleServerVersionGet(w http.ResponseWriter, r *http.Request) {
	res, err := a.params.GetVersion(r.Context())
	if err != nil {
		failParam(w, "查询版本设置", err)
		return
	}
	httpx.OK(w, res)
}

type serverVersionReq struct {
	ID int `json:"id"`
}

// handleServerVersionSwitch 换后台音频引擎的版本包。
//
// ⚠ 这不是改一列数据 —— 它解压版本包并重建 a9000_audioserver 容器，
// 期间广播会中断。所以：只给超管、写审计、失败原样回错。
// 换不动的机器（sudo 要密码）在 GET 里就已经把 canSwitch 置 false 了。
func (a *app) handleServerVersionSwitch(w http.ResponseWriter, r *http.Request) {
	var in serverVersionReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if in.ID <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "请选择版本")
		return
	}
	u := auth.From(r.Context())
	// 审计写在动手之前：容器一重建，这个进程能不能活到写日志都不好说
	a.auditor.Write(r.Context(), u.Username,
		fmt.Sprintf("切换服务器版本（id=%d）", in.ID), audit.ClientIP(r))

	// ⚠ 这里不走 failParam：它对非校验类错误一律折成 500「内部错误」，
	// 而这一步失败的原因（sudo 要密码、版本包不存在、tar 报错）恰恰是
	// 操作员必须看到的那句话。原样回给界面。
	if err := a.params.SwitchVersion(r.Context(), in.ID); err != nil {
		httpx.Fail(w, httpx.CodeBadRequest, err.Error())
		return
	}
	httpx.OK(w, map[string]interface{}{
		"switched": true,
		"note":     "版本包已解开，audioserver 容器正在重建，期间广播中断。",
	})
}

// ---------- 重启服务器 ----------

type serverRebootReq struct {
	ConfirmText string `json:"confirmText"`
}

// rebootConfirmText 是「重启服务器」必须逐字输入的确认文本。
const rebootConfirmText = "重启服务器"

// handleServerReboot 下发 server?state=1。
//
// ⚠ 这条报文实测会让**整台服务器立刻重启**，不是「重启后台服务」——
// 现网 2026-08-25 16:09:58 发出，16:09:59 systemd 就开始走关机流程。
// 界面上那个橙色按钮就是它，所以这里要求逐字确认，并写审计。
func (a *app) handleServerReboot(w http.ResponseWriter, r *http.Request) {
	var in serverRebootReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	if in.ConfirmText != rebootConfirmText {
		httpx.Fail(w, httpx.CodeBadRequest,
			"确认文本不正确，需要逐字输入「"+rebootConfirmText+"」")
		return
	}
	u := auth.From(r.Context())
	// 审计写在发包之前：包一发机器就没了，之后什么都写不进去
	a.auditor.Write(r.Context(), u.Username, "重启服务器（整机）", audit.ClientIP(r))

	a.notifier.ServerReboot(r.Context())
	httpx.OK(w, map[string]interface{}{
		"sent": true,
		"note": "已下发重启指令。这会重启整台服务器，约 30 秒后服务恢复，期间广播中断。",
	})
}

// ---------- F-57 恢复出厂 ----------

func (a *app) handleFactoryPreview(w http.ResponseWriter, r *http.Request) {
	pv, err := a.params.PreviewFactoryReset(r.Context(), a.cfg.BackupMediaDir())
	if err != nil {
		failParam(w, "预览恢复出厂", err)
		return
	}
	httpx.OK(w, pv)
}

type factoryResetReq struct {
	ConfirmText     string `json:"confirmText"`
	PurgeMediaFiles bool   `json:"purgeMediaFiles"`
}

func (a *app) handleFactoryReset(w http.ResponseWriter, r *http.Request) {
	var in factoryResetReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}
	u := auth.From(r.Context())

	// 确认文本先在这里挡一道，**再**写审计。
	//
	// 审计确实必须写在执行之前（恢复出厂会把 log 表整个清掉，事后写就成了
	// 「清空后的第一行」，追不回是谁按的，BR-265）；但如果把它写在校验之前，
	// 一次被拒绝的尝试也会留下一行「恢复出厂设置（清空业务数据）」，
	// 读日志的人会以为真清过 —— 现网实测就这样误记了一行（N-12）。
	if in.ConfirmText != serverparam.ConfirmText {
		httpx.Fail(w, httpx.CodeBadRequest,
			"确认文本不正确，需要逐字输入「"+serverparam.ConfirmText+"」")
		return
	}
	a.auditor.Write(r.Context(), u.Username, "开始恢复出厂设置（清空业务数据）", audit.ClientIP(r))

	keep, err := a.params.KeepMediaFileName(r.Context())
	if err != nil {
		failParam(w, "恢复出厂", err)
		return
	}
	res, err := a.params.FactoryReset(r.Context(), serverparam.FactoryInput{
		ConfirmText:     in.ConfirmText,
		PurgeMediaFiles: in.PurgeMediaFiles,
		MediaDir:        a.cfg.BackupMediaDir(),
		KeepMediaFile:   filepath.Base(keep),
	})
	if err != nil {
		failParam(w, "恢复出厂", err)
		return
	}
	// 用户表被清到只剩 admin，现有会话可能指向已经不存在的账号
	a.authMgr.InvalidateAll()

	// 清完再补记一行：上面那行随 log 表一起被清掉了，这行是清空后的第一条痕迹
	a.auditor.Write(r.Context(), u.Username,
		fmt.Sprintf("恢复出厂完成，清理 %d 张表、%d 行、%d 个媒体文件",
			res.ClearedTables, res.DeletedRows, res.DeletedMediaFiles), audit.ClientIP(r))

	httpx.OK(w, res)
}
