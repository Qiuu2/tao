// Command htweb 是新版 Web 的服务进程。
//
// 部署形态（手册 §1.6.4）：单个静态编译的二进制跑在宿主机上，
// 同时提供 /api/* 接口与前端静态文件，不新增容器、不改动现网 Apache 与 docker-compose。
//
// 依赖链路全部走 Docker 已发布到宿主的端口：
//   - MySQL(MariaDB) → 127.0.0.1:3306
//   - 后台 C 广播服务 → UDP 127.0.0.1:<serverbaseparam.webport>
//   - 媒体文件 → /opt/apps/a9000/backup/mediadata/（与旧系统同一份）
package main

import (
	"context"
	"errors"
	"flag"
	"io"
	"log"
	"net/http"
	"os"
	"os/signal"
	"path/filepath"
	"strconv"
	"strings"
	"syscall"
	"time"

	"htweb/internal/alarm"
	"htweb/internal/audit"
	"htweb/internal/auth"
	"htweb/internal/backup"
	"htweb/internal/bell"
	"htweb/internal/captcha"
	"htweb/internal/config"
	"htweb/internal/dashboard"
	"htweb/internal/enable"
	"htweb/internal/folder"
	"htweb/internal/holiday"
	"htweb/internal/httpx"
	"htweb/internal/logs"
	"htweb/internal/media"
	"htweb/internal/notify"
	"htweb/internal/offline"
	"htweb/internal/remote"
	"htweb/internal/serverparam"
	"htweb/internal/sound"
	"htweb/internal/store"
	"htweb/internal/task"
	"htweb/internal/terminal"
	"htweb/internal/timeset"
	"htweb/internal/typedtask"
	"htweb/internal/user"
	"htweb/internal/zone"
)

type app struct {
	cfg       *config.Config
	st        *store.Store
	authMgr   *auth.Manager
	cap       *captcha.Store
	folders   *folder.Service
	medias    *media.Service
	uploader  *media.Uploader
	notifier  *notify.Notifier
	users     *user.Service
	terminals *terminal.Service
	tasks     *task.Service
	alarms    *alarm.Service
	bells     *bell.Service
	logs      *logs.Service
	taskLogs  *logs.TaskLogService
	auditor   *audit.Recorder
	backups   *backup.Service
	params    *serverparam.Service
	offline   *offline.Service
	dash      *dashboard.Service
	zones     *zone.Service
	holidays  *holiday.Service
	remotes   *remote.Service
	times     *timeset.Service
	typed     *typedtask.Service
	enables   *enable.Service
	sounds    *sound.Service
}

func main() {
	cfgPath := flag.String("config", "config.yaml", "配置文件路径")
	flag.Parse()

	cfg, err := config.Load(*cfgPath)
	if err != nil {
		log.Fatalf("加载配置失败: %v", err)
	}
	// 启动时打印生效配置，便于排查「配置文件写了但没生效」这类问题。
	// 密码只输出长度，不落盘明文。
	log.Printf("配置生效: db=%s@%s:%d/%s (密码长度 %d) listen=%s static=%s media=%s",
		cfg.Database.User, cfg.Database.Host, cfg.Database.Port, cfg.Database.Name,
		len(cfg.Database.Pass), cfg.Server.Listen, cfg.Server.StaticDir, cfg.Media.Root)

	st, err := store.Open(cfg)
	if err != nil {
		log.Fatalf("数据库不可用: %v", err)
	}
	defer st.Close()

	a := &app{
		cfg:       cfg,
		st:        st,
		authMgr:   auth.NewManager(st.DB(), cfg.Auth.Secret, cfg.Auth.TTL),
		cap:       captcha.NewStore(3 * time.Minute),
		folders:   folder.New(st.DB()),
		medias:    media.New(st.DB(), cfg.Media.Root),
		uploader:  media.NewUploader(st.DB(), cfg.Media.Root, cfg.Media.FFmpeg, cfg.Media.MaxUploadMB),
		notifier:  notify.New(st.DB(), cfg.Notify.Host, cfg.Notify.Port, cfg.Notify.Enabled),
		users:     user.New(st.DB()),
		terminals: terminal.New(st.DB()),
		tasks:     task.New(st.DB()),
		alarms:    alarm.New(st.DB()),
		bells:     bell.New(st.DB()),
		auditor:   audit.New(st.DB()),
		taskLogs:  logs.NewTaskLog(st.DB(), cfg.Logs.TaskDir),
		backups: backup.New(st.DB(), cfg.BackupDir(), cfg.BackupMediaDir(),
			cfg.Database.Name),
		params: serverparam.New(st.DB(), cfg.Legacy.ApacheConf, cfg.Legacy.SwaggerFile,
			cfg.Media.Root),
		offline:  offline.New(st.DB()),
		dash:     dashboard.New(st.DB(), cfg.DashboardFile()),
		zones:    zone.New(st.DB()),
		holidays: holiday.New(st.DB()),
		remotes:  remote.New(st.DB()),
		times:    timeset.New(st.DB()),
		typed:    typedtask.New(st.DB()),
		enables:  enable.New(st.DB()),
		sounds:   sound.New(st.DB()),
	}
	a.logs = logs.New(st.DB(), a.auditor)
	// 删除用户会连带删掉他名下的媒体，物理文件清理与 C 服务通知复用媒体域的实现
	a.users.SetSideEffects(a.medias, a.notifier)

	srv := &http.Server{
		Addr:              cfg.Server.Listen,
		Handler:           a.routes(),
		ReadHeaderTimeout: 10 * time.Second,
		WriteTimeout:      0, // 媒体流式下载可能较久，不设写超时
		IdleTimeout:       60 * time.Second,
	}

	go func() {
		log.Printf("htweb 启动，监听 %s", cfg.Server.Listen)
		log.Printf("静态目录 %s", cfg.Server.StaticDir)
		log.Printf("媒体根目录 %s", cfg.Media.Root)
		if err := srv.ListenAndServe(); err != nil && !errors.Is(err, http.ErrServerClosed) {
			log.Fatalf("监听失败: %v", err)
		}
	}()

	stop := make(chan os.Signal, 1)
	signal.Notify(stop, os.Interrupt, syscall.SIGTERM)
	<-stop

	log.Println("正在关闭…")
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()
	_ = srv.Shutdown(ctx)
}

func (a *app) routes() http.Handler {
	rawMux := http.NewServeMux()
	req := a.authMgr.Require

	// 所有路由都经这一层注册，写操作会自动落一行操作日志。
	// 记不记、记成什么名字由 auditLabels 表决定（见 audit_mw.go）——
	// 逐个 handler 里手写 insert_log，四十多个写接口迟早漏几个，
	// 而「漏记」平时完全看不出来，只有出事要追责时才发现。
	mux := &auditMux{m: rawMux, a: a}

	// —— 认证 ——
	mux.HandleFunc("POST /api/login", a.handleLogin)
	mux.HandleFunc("GET /api/captcha", a.handleCaptcha)
	mux.HandleFunc("POST /api/logout", req(a.handleLogout))
	mux.HandleFunc("GET /api/auth/me", req(a.handleMe))

	// Geeker-Admin 基座启动时会拉取菜单与按钮权限
	mux.HandleFunc("GET /api/menu/list", req(a.handleMenu))
	mux.HandleFunc("GET /api/auth/buttons", req(a.handleButtons))

	// —— 文件夹（业务域一）——
	mux.HandleFunc("GET /api/folders/tree", req(a.handleFolderTree))
	// 写操作一律在服务端复检权限与备机模式，不依赖前端置灰（修复旧版 D-06 越权）
	mux.HandleFunc("POST /api/folders", a.authMgr.RequireRight(auth.PrivFolder, a.handleFolderCreate))
	mux.HandleFunc("PUT /api/folders/{id}", a.authMgr.RequireRight(auth.PrivFolder, a.handleFolderUpdate))
	mux.HandleFunc("GET /api/folders/delete-preview", a.authMgr.RequireRight(auth.PrivFolder, a.handleFolderDeletePreview))
	mux.HandleFunc("DELETE /api/folders", a.authMgr.RequireRight(auth.PrivFolder, a.handleFolderDelete))

	// —— 媒体（业务域二）——
	mux.HandleFunc("GET /api/media", req(a.handleMediaList))
	mux.HandleFunc("POST /api/media/upload", a.authMgr.RequireRight(auth.PrivMedia, a.handleMediaUpload))
	mux.HandleFunc("GET /api/media/delete-preview", a.authMgr.RequireRight(auth.PrivMedia, a.handleMediaDeletePreview))
	mux.HandleFunc("DELETE /api/media", a.authMgr.RequireRight(auth.PrivMedia, a.handleMediaDelete))
	mux.HandleFunc("POST /api/folders/{id}/media:clear", a.authMgr.RequireRight(auth.PrivMedia, a.handleMediaClear))
	// 这两个路由额外允许 ?token=，因为 <audio src> 与 window.open 带不上自定义请求头
	mux.HandleFunc("GET /api/media/{id}/stream", a.authMgr.RequireAllowQueryToken(a.handleMediaStream))
	mux.HandleFunc("GET /api/media/{id}/download", a.authMgr.RequireAllowQueryToken(a.handleMediaDownload))

	// —— 用户 / 用户组（业务域四、五）——
	//
	// 全部走 userpriv 复检。旧版这些接口只在渲染层用 JS 隐藏按钮，
	// 直接构造 do.php?action=userdel_msg 就能越权删人（缺陷 D-06）。
	usr := func(h http.HandlerFunc) http.HandlerFunc {
		return a.authMgr.RequireRight(auth.PrivUser, h)
	}
	mux.HandleFunc("GET /api/usergroups", usr(a.handleGroupList))
	// options 也要 userpriv：它只服务于用户编辑表单（本身就在用户模块里），
	// 挂在「仅需登录」级别等于让任何账号都能枚举全部用户组名和级别。
	mux.HandleFunc("GET /api/usergroups/options", usr(a.handleGroupOptions))
	mux.HandleFunc("POST /api/usergroups", usr(a.handleGroupCreate))
	mux.HandleFunc("PUT /api/usergroups/{id}", usr(a.handleGroupUpdate))
	mux.HandleFunc("GET /api/usergroups/{id}/delete-preview", usr(a.handleGroupDeletePreview))
	mux.HandleFunc("DELETE /api/usergroups/{id}", usr(a.handleGroupDelete))

	mux.HandleFunc("GET /api/users", usr(a.handleUserList))
	mux.HandleFunc("GET /api/users/wind-capacity", usr(a.handleWindCapacity))
	mux.HandleFunc("GET /api/users/terminal-options", usr(a.handleTerminalOptions))
	mux.HandleFunc("GET /api/users/delete-preview", usr(a.handleUserDeletePreview))
	mux.HandleFunc("GET /api/users/{id}", usr(a.handleUserGet))
	mux.HandleFunc("POST /api/users", usr(a.handleUserCreate))
	mux.HandleFunc("PUT /api/users/{id}", usr(a.handleUserUpdate))
	mux.HandleFunc("POST /api/users/{id}/enable", usr(a.handleUserEnable))
	mux.HandleFunc("DELETE /api/users", usr(a.handleUserDelete))

	// —— 终端（业务域六，F-25 ~ F-31）——
	//
	// 列表与分区树只要登录即可：可见范围由 userterminal 绑定关系收敛（BR-139），
	// 普通用户看到的本来就只有绑给自己的终端。写操作才要 terminalpriv。
	trm := func(h http.HandlerFunc) http.HandlerFunc {
		return a.authMgr.RequireRight(auth.PrivTerminal, h)
	}
	mux.HandleFunc("GET /api/terminals", req(a.handleTerminalList))
	mux.HandleFunc("GET /api/terminal-groups/tree", req(a.handleTerminalGroupTree))
	mux.HandleFunc("GET /api/terminal-types", req(a.handleTerminalTypes))
	mux.HandleFunc("GET /api/terminals/delete-preview", trm(a.handleTerminalDeletePreview))
	mux.HandleFunc("GET /api/terminals/{id}", trm(a.handleTerminalGet))
	mux.HandleFunc("PUT /api/terminals/{id}", trm(a.handleTerminalUpdate))
	mux.HandleFunc("PUT /api/terminals/start", trm(a.handleTerminalRunning))
	mux.HandleFunc("PUT /api/terminals/stop", trm(a.handleTerminalRunning))
	mux.HandleFunc("PUT /api/terminals/volume", trm(a.handleTerminalVolume))
	mux.HandleFunc("PUT /api/terminals/password", trm(a.handleTerminalPassword))
	mux.HandleFunc("PUT /api/terminals/circuit-check", trm(a.handleTerminalCircuit))
	mux.HandleFunc("PUT /api/terminals/sync-time", trm(a.handleTerminalSyncTime))
	// {toggle} ∈ speech / sponsor / record / backcall / instancy
	mux.HandleFunc("PUT /api/terminals/toggle/{toggle}", trm(a.handleTerminalToggle))
	mux.HandleFunc("DELETE /api/terminals", trm(a.handleTerminalDelete))

	// —— 快捷键寻呼 ——
	//
	// 「在这台终端上按这个键，去寻呼那些终端」。落在 terminalkey + terminalkeymap。
	// 读只要登录（可见范围由归属终端的 userterminal 绑定收敛），写要 terminalpriv。
	// ⚠ 可选键值按终端类型算，且急救（flag=1）与普通快捷键的可选集不同，
	//   详见 terminal/keyspec.go。
	mux.HandleFunc("GET /api/terminals/{id}/shortcut-keys", req(a.handleShortcutKeyList))
	mux.HandleFunc("GET /api/terminals/{id}/shortcut-keys/options", req(a.handleShortcutKeyOptions))
	mux.HandleFunc("POST /api/terminals/{id}/shortcut-keys", trm(a.handleShortcutKeyCreate))
	mux.HandleFunc("PUT /api/shortcut-keys/{keyId}", trm(a.handleShortcutKeyUpdate))
	mux.HandleFunc("DELETE /api/shortcut-keys", trm(a.handleShortcutKeyDelete))

	// 快捷任务（ok112 的 view_quickplay / setquickplay）。
	// 与上面的快捷键寻呼**不是一套结构**：只有 terminalkeymaptask 一张表，
	// 主键 (keyid, terminalid)，keyid 存的是键值本身。详见 terminal/quicktask.go。
	// 读权限与快捷键一致；写要 terminalpriv。
	// 终端替换：换了新硬件后让它接管旧记录的 id。要 terminalpriv。
	// ⚠ 路径用字面量而不是 /terminals/{id}/replace —— 后者会与
	//   PUT /api/terminals/toggle/{toggle} 冲突（两者都能匹配
	//   /api/terminals/toggle/replace，且互不更具体，ServeMux 会 panic）。
	//   与 volume / password / sync-time 这些批量动作保持同一种写法。
	mux.HandleFunc("PUT /api/terminals/replace", trm(a.handleTerminalReplace))

	// 寻呼授权（「授权寻呼」与「授权终端」两个入口共用这一套，
	// 区别只在前端挑终端的方式）。名单为空 = 回到「可寻呼所有在线终端」的默认。
	// ⚠ 写用 POST 不用 PUT：PUT /api/terminals/{id}/... 会与
	//   PUT /api/terminals/toggle/{toggle} 冲突。快捷键那边也是同样的原因
	//   才把更新放到 /api/shortcut-keys/{keyId} 上。
	// 寻呼分区（授权寻呼 / 授权终端）。一台终端可以有多个分区，
	// 所以是一组资源接口：列表 / 候选终端 / 单个分区 / 新增改 / 删除。
	// ⚠ 写操作一律 POST —— `PUT /api/terminals/{id}/...` 的通配位已被
	//   toggle 那组路由占了，再注册 ServeMux 会 panic。
	mux.HandleFunc("GET /api/terminals/{id}/call-groups", req(a.handleCallGroupList))
	mux.HandleFunc("GET /api/terminals/{id}/call-groups/candidates", req(a.handleCallGroupCandidates))
	mux.HandleFunc("POST /api/terminals/{id}/call-groups", trm(a.handleCallGroupSave))
	mux.HandleFunc("DELETE /api/terminals/{id}/call-groups", trm(a.handleCallGroupDelete))
	mux.HandleFunc("GET /api/call-groups/{gid}", req(a.handleCallGroupGet))

	// 授权终端的「目录管理」。目录是每台宿主终端各有一套的（terminalfolder），
	// 只服务于「授权终端」那棵挑终端的树，所以挂在终端下面而不是全局资源。
	mux.HandleFunc("GET /api/terminals/{id}/folders", req(a.handleTerminalFolderTree))
	mux.HandleFunc("POST /api/terminals/{id}/folders", trm(a.handleTerminalFolderSave))
	mux.HandleFunc("DELETE /api/terminals/{id}/folders", trm(a.handleTerminalFolderDelete))
	mux.HandleFunc("GET /api/terminals/{id}/folders/terminals", req(a.handleFolderTerminals))
	mux.HandleFunc("GET /api/terminals/{id}/folders/candidates", req(a.handleFolderCandidates))
	mux.HandleFunc("POST /api/terminals/{id}/folders/terminals", trm(a.handleFolderTerminalsAdd))
	mux.HandleFunc("DELETE /api/terminals/{id}/folders/terminals", trm(a.handleFolderTerminalsRemove))

	// ⚠ 快捷任务是「为这台终端新建一条专属任务并绑到键上」，
	//   不是「把已有任务绑到键上」。三个 tasktype(20/21/29) 的由来、
	//   cmd 与 cmdargs 两个终端 ID 列的含义，见 terminal/quicktask.go。
	//   写用 POST/PUT 不用 PUT /api/terminals/{id}/... 之外的形状：
	//   PUT /api/terminals/{id}/xxx 会与 PUT /api/terminals/toggle/{toggle} 撞路由。
	mux.HandleFunc("GET /api/terminals/{id}/quick-tasks", req(a.handleQuickTaskList))
	mux.HandleFunc("GET /api/terminals/{id}/quick-tasks/detail", req(a.handleQuickTaskDetail))
	mux.HandleFunc("POST /api/terminals/{id}/quick-tasks", trm(a.handleQuickTaskCreate))
	mux.HandleFunc("POST /api/terminals/{id}/quick-tasks/update", trm(a.handleQuickTaskUpdate))
	mux.HandleFunc("DELETE /api/terminals/{id}/quick-tasks", trm(a.handleQuickTaskDelete))
	mux.HandleFunc("GET /api/quick-task-audio-sources", req(a.handleQuickAudioSources))

	// —— 任务（业务域七，F-32 ~ F-37）——
	//
	// 与终端同样的分界：读只要登录（可见范围由 task_user_id 收敛），写要 taskpriv。
	tsk := func(h http.HandlerFunc) http.HandlerFunc {
		return a.authMgr.RequireRight(auth.PrivTask, h)
	}
	mux.HandleFunc("GET /api/tasks", req(a.handleTaskList))
	mux.HandleFunc("GET /api/task-folders/tree", req(a.handleTaskFolderTree))
	// 选择器只要登录：它们自身已按 folder / userterminal 收敛可见范围
	mux.HandleFunc("GET /api/task-options/media", req(a.handleTaskMediaOptions))
	mux.HandleFunc("GET /api/task-options/terminals", req(a.handleTaskTerminalOptions))

	// 固定路径必须先于 /api/tasks/{id} 注册的问题在 net/http 的新 mux 里不存在
	// （它按模式具体程度择优，不是先到先得），但仍按语义分组排列便于阅读。
	mux.HandleFunc("GET /api/tasks/delete-preview", tsk(a.handleTaskDeletePreview))
	mux.HandleFunc("GET /api/tasks/{id}", req(a.handleTaskGet))
	mux.HandleFunc("POST /api/tasks", tsk(a.handleTaskCreate))
	mux.HandleFunc("PUT /api/tasks/{id}", tsk(a.handleTaskUpdate))
	mux.HandleFunc("DELETE /api/tasks", tsk(a.handleTaskDelete))
	// {action} ∈ start / stop / pause / resume
	mux.HandleFunc("PUT /api/tasks/control/{action}", tsk(a.handleTaskControl))
	mux.HandleFunc("PUT /api/tasks/project-state", tsk(a.handleTaskProjectState))
	mux.HandleFunc("GET /api/tasks/emergency", tsk(a.handleTaskEmergency))
	mux.HandleFunc("PUT /api/tasks/emergency", tsk(a.handleTaskSetEmergency))
	mux.HandleFunc("DELETE /api/tasks/emergency", tsk(a.handleTaskCancelEmergency))
	mux.HandleFunc("PUT /api/tasks/volume", tsk(a.handleTaskVolume))
	mux.HandleFunc("POST /api/tasks/{id}/copy", tsk(a.handleTaskCopy))
	mux.HandleFunc("POST /api/tasks/sync-terminals", tsk(a.handleTaskSync))

	mux.HandleFunc("POST /api/task-folders", tsk(a.handleTaskFolderCreate))
	mux.HandleFunc("PUT /api/task-folders/{id}", tsk(a.handleTaskFolderUpdate))
	mux.HandleFunc("DELETE /api/task-folders/{id}", tsk(a.handleTaskFolderDelete))

	// —— 报警（业务域八，F-38 ~ F-42）——
	//
	// 与前两个模块同一分界：读只要登录（可见范围由 alarmarea.userid 收敛），
	// 写要 alarmgrouppriv。旧版整条写入路径一个权限校验都没有（D-134）。
	alm := func(h http.HandlerFunc) http.HandlerFunc {
		return a.authMgr.RequireRight(auth.PrivAlarmGroup, h)
	}
	mux.HandleFunc("GET /api/alarm-mappings", req(a.handleAlarmMappingList))
	mux.HandleFunc("GET /api/alarm-mappings/{id}", req(a.handleAlarmMappingGet))
	mux.HandleFunc("POST /api/alarm-mappings", alm(a.handleAlarmMappingCreate))
	mux.HandleFunc("PUT /api/alarm-mappings/{id}", alm(a.handleAlarmMappingUpdate))
	mux.HandleFunc("DELETE /api/alarm-mappings", alm(a.handleAlarmMappingDelete))

	mux.HandleFunc("GET /api/alarm-areas", req(a.handleAlarmAreaList))
	mux.HandleFunc("GET /api/alarm-areas/delete-preview", alm(a.handleAlarmAreaDeletePreview))
	mux.HandleFunc("GET /api/alarm-areas/{id}", req(a.handleAlarmAreaGet))
	mux.HandleFunc("POST /api/alarm-areas", alm(a.handleAlarmAreaCreate))
	mux.HandleFunc("PUT /api/alarm-areas/{id}", alm(a.handleAlarmAreaUpdate))
	mux.HandleFunc("DELETE /api/alarm-areas", alm(a.handleAlarmAreaDelete))

	// 选择器只要登录：各自已按 userterminal / alarmarea.userid 收敛可见范围
	mux.HandleFunc("GET /api/alarm-options/hosts", req(a.handleAlarmHosts))
	mux.HandleFunc("GET /api/alarm-options/areas", req(a.handleAlarmAreaOptions))
	mux.HandleFunc("GET /api/alarm-options/media", req(a.handleAlarmMediaOptions))
	mux.HandleFunc("GET /api/alarm-options/terminals", req(a.handleAlarmTerminalOptions))

	// —— 作息方案 / 打铃（业务域十，F-48 ~ F-52）——
	//
	// 读只要登录（可见范围由 task.task_user_id 收敛），写要 bellpriv。
	// 方案名走 query / body 而不是路径段，原因见 bell_handlers.go 的文件注释。
	bel := func(h http.HandlerFunc) http.HandlerFunc {
		return a.authMgr.RequireRight(auth.PrivBell, h)
	}
	mux.HandleFunc("GET /api/bell-plans", req(a.handleBellPlanList))
	mux.HandleFunc("GET /api/bell-plans/detail", req(a.handleBellPlanGet))
	mux.HandleFunc("GET /api/bell-plans/delete-preview", bel(a.handleBellPlanDeletePreview))
	mux.HandleFunc("POST /api/bell-plans", bel(a.handleBellPlanCreate))
	mux.HandleFunc("PUT /api/bell-plans", bel(a.handleBellPlanUpdate))
	mux.HandleFunc("DELETE /api/bell-plans", bel(a.handleBellPlanDelete))
	mux.HandleFunc("PUT /api/bell-plans/state", bel(a.handleBellPlanState))
	mux.HandleFunc("PUT /api/bell-plans/volume", bel(a.handleBellPlanVolume))
	mux.HandleFunc("POST /api/bell-plans/copy", bel(a.handleBellPlanCopy))
	mux.HandleFunc("POST /api/bell-plans/items", bel(a.handleBellItemAdd))
	mux.HandleFunc("PUT /api/bell-plans/items/{id}", bel(a.handleBellItemUpdate))
	mux.HandleFunc("DELETE /api/bell-plans/items", bel(a.handleBellItemDelete))

	// —— 日志（业务域十一，F-54 / F-55）——
	//
	// 全部限超级管理员（BR-246）。旧版权限不足时输出硬编码中文 HTML 后 exit（D-204）。
	sup := a.requireSuper
	mux.HandleFunc("GET /api/logs", sup(a.handleLogList))
	mux.HandleFunc("GET /api/logs/stats", sup(a.handleLogStats))
	mux.HandleFunc("DELETE /api/logs", sup(a.handleLogClear))

	mux.HandleFunc("GET /api/task-logs/files", sup(a.handleTaskLogFiles))
	mux.HandleFunc("GET /api/task-logs/files/{name}", sup(a.handleTaskLogRead))
	mux.HandleFunc("GET /api/task-logs/delete-preview", sup(a.handleTaskLogDeletePreview))
	mux.HandleFunc("DELETE /api/task-logs", sup(a.handleTaskLogDelete))

	// —— 备份与恢复（业务域十三，F-58 ~ F-60）——
	//
	// 全部限超级管理员。旧版这三个入口一个权限校验都没有
	// （D-222 备份 / D-229 恢复 / D-238 删除），其中恢复等价于任意 SQL 执行。
	mux.HandleFunc("GET /api/backups", sup(a.handleBackupList))
	mux.HandleFunc("POST /api/backups", sup(a.handleBackupCreate))
	mux.HandleFunc("DELETE /api/backups", sup(a.handleBackupDelete))
	// 上传备份包：把下载下来的、或别的机器上备的包传回来，之后与本机备的包等价
	mux.HandleFunc("POST /api/backups/upload", sup(a.handleBackupUpload))
	mux.HandleFunc("GET /api/backups/{name}/download", a.requireSuperAllowQueryToken(a.handleBackupDownload))
	mux.HandleFunc("GET /api/backups/{name}/restore-precheck", sup(a.handleBackupPrecheck))
	mux.HandleFunc("POST /api/backups/restore", sup(a.handleBackupRestore))

	// —— 看板首页 ——
	//
	// 读只要登录（设备与任务的可见范围各自按 userterminal / task_user_id 收敛）；
	// 三块可配置区域（快捷入口 / 快捷任务 / 紧急广播绑定）是全局共享的界面设置，
	// 收紧到超级管理员，免得任何人都能改掉别人看到的首页。
	mux.HandleFunc("GET /api/dashboard/overview", req(a.handleDashOverview))
	mux.HandleFunc("GET /api/dashboard/perf", req(a.handleDashPerf))
	mux.HandleFunc("GET /api/dashboard/config", req(a.handleDashConfig))
	mux.HandleFunc("GET /api/dashboard/tasks", req(a.handleDashBrowse))
	mux.HandleFunc("PUT /api/dashboard/shortcuts", sup(a.handleDashShortcuts))
	mux.HandleFunc("PUT /api/dashboard/quick-tasks", sup(a.handleDashQuickTasks))
	mux.HandleFunc("PUT /api/dashboard/emergency", sup(a.handleDashEmergency))

	// —— 离线管理（业务域九，F-43 ~ F-47）——
	//
	// 读只要登录（可见范围按 userterminal 收敛）；
	// 媒体下发要 terminalpriv，任务下发要 taskpriv；
	// 清空全部离线数据是 4 条无 WHERE 的 DELETE，收紧到超级管理员。
	mux.HandleFunc("GET /api/offline/states", req(a.handleOfflineStates))
	mux.HandleFunc("GET /api/offline/summary", req(a.handleOfflineSummary))
	mux.HandleFunc("GET /api/offline/media", req(a.handleOfflineMediaStatus))
	mux.HandleFunc("GET /api/offline/tasks", req(a.handleOfflineTaskStatus))
	mux.HandleFunc("POST /api/offline/media", trm(a.handleOfflineMediaDispatch))
	mux.HandleFunc("POST /api/offline/tasks", tsk(a.handleOfflineTaskDispatch))
	mux.HandleFunc("PUT /api/offline/stop", trm(a.handleOfflineStop))
	mux.HandleFunc("POST /api/offline/purge-all", sup(a.handleOfflinePurge))

	// —— 服务器参数（业务域十二，F-56 / F-57）——
	//
	// 读写都要 serverpriv。
	//
	// ⚠ 这一组**不受备机模式限制**（用 RequireRightAllowReadOnly）：
	//    主备模式的开关就在这一页上，跟着全站一起锁死会把自己锁在外面 ——
	//    切成备机后再也没有界面能改回主服务器。其余模块仍然照常只读。
	//    恢复出厂是例外中的例外：破坏力最大，仍然禁止在备机上执行（见 factory.go）。
	srv := func(h http.HandlerFunc) http.HandlerFunc {
		return a.authMgr.RequireRightAllowReadOnly(auth.PrivServer, h)
	}
	mux.HandleFunc("GET /api/server/params", srv(a.handleServerParamGet))
	mux.HandleFunc("PUT /api/server/params", srv(a.handleServerParamSave))
	// 定时重启存在 task 表那条 tasktype=13 的系统任务上，不在 serverbaseparam 里
	mux.HandleFunc("GET /api/server/auto-restart", srv(a.handleAutoRestartGet))
	mux.HandleFunc("PUT /api/server/auto-restart", srv(a.handleAutoRestartSave))
	// 版本设置：读跟着服务器信息页走（srv），换版本只给超管 ——
	// 它会重建 audioserver 容器、中断广播，跟「重启服务器」一个量级。
	mux.HandleFunc("GET /api/server/version", srv(a.handleServerVersionGet))
	mux.HandleFunc("POST /api/server/version", sup(a.handleServerVersionSwitch))
	mux.HandleFunc("POST /api/server/reboot", sup(a.handleServerReboot))
	mux.HandleFunc("GET /api/server/factory-reset/preview", sup(a.handleFactoryPreview))
	mux.HandleFunc("POST /api/server/factory-reset", sup(a.handleFactoryReset))

	// —— 终端分区（旧版 StreamManager）——
	//
	// 读只要登录：终端列表、任务下发都要按分区筛选，权限收太紧那些页面就没法用了；
	// 可见范围在 SQL 里按 userid 收敛（普通用户只看自己建的）。
	// 增删改要 terminalgrouppriv —— 改分区会连带改动 terminal.groupid 与
	// terminaloftask.groupid，直接影响后台下发。
	tgp := func(h http.HandlerFunc) http.HandlerFunc {
		return a.authMgr.RequireRight(auth.PrivTerminalGroup, h)
	}
	mux.HandleFunc("GET /api/zones", req(a.handleZoneList))
	mux.HandleFunc("GET /api/zones/options", req(a.handleZoneOptions))
	mux.HandleFunc("GET /api/zones/terminals", req(a.handleZoneTerminals))
	mux.HandleFunc("GET /api/zones/delete-preview", tgp(a.handleZoneDeletePreview))
	mux.HandleFunc("GET /api/zones/{id}", req(a.handleZoneGet))
	mux.HandleFunc("POST /api/zones", tgp(a.handleZoneCreate))
	mux.HandleFunc("PUT /api/zones/{id}", tgp(a.handleZoneUpdate))
	mux.HandleFunc("DELETE /api/zones", tgp(a.handleZoneDelete))

	// —— 节假日管理 ——
	//
	// 节假日直接决定「放假这天打不打铃」，和作息方案是同一件事的两面，
	// 所以走 bellpriv，与作息方案一致。
	mux.HandleFunc("GET /api/holidays", req(a.handleHolidayList))
	mux.HandleFunc("GET /api/holidays/overlaps", req(a.handleHolidayOverlaps))
	mux.HandleFunc("GET /api/holidays/{id}", req(a.handleHolidayGet))
	mux.HandleFunc("POST /api/holidays", bel(a.handleHolidayCreate))
	mux.HandleFunc("PUT /api/holidays/{id}", bel(a.handleHolidayUpdate))
	mux.HandleFunc("PUT /api/holidays/state", bel(a.handleHolidayState))
	mux.HandleFunc("DELETE /api/holidays", bel(a.handleHolidayDelete))

	// —— 遥控任务（旧版 keytask_mapping）——
	//
	// 绑的是任务，按 taskpriv。
	mux.HandleFunc("GET /api/remote-keys", req(a.handleRemoteList))
	mux.HandleFunc("GET /api/remote-keys/tasks", req(a.handleRemoteTasks))
	mux.HandleFunc("GET /api/remote-keys/{id}", req(a.handleRemoteGet))
	mux.HandleFunc("POST /api/remote-keys", tsk(a.handleRemoteCreate))
	mux.HandleFunc("PUT /api/remote-keys/{id}", tsk(a.handleRemoteUpdate))
	mux.HandleFunc("DELETE /api/remote-keys", tsk(a.handleRemoteDelete))

	// —— 时间设置（旧版 set_server_time）——
	//
	// NTP 与 GPS 校时终端是服务器级配置，按 serverpriv；
	// 给终端下发校时指令是终端操作，按 terminalpriv。
	mux.HandleFunc("GET /api/time", req(a.handleTimeGet))
	mux.HandleFunc("GET /api/time/terminals", req(a.handleTimeTerminals))
	mux.HandleFunc("PUT /api/time/ntp", srv(a.handleTimeSetNTP))
	mux.HandleFunc("PUT /api/time/gps", srv(a.handleTimeSetGPS))
	mux.HandleFunc("POST /api/time/sync", trm(a.handleTimeSync))
	// 设置服务器系统时钟。现网默认没有这个能力（服务账号 sudo 要密码 + NTP 在跑），
	// 接口会如实回报缺什么，见 timeset/clock.go。
	mux.HandleFunc("PUT /api/time/clock", srv(a.handleTimeSetClock))

	// —— 终端功放 / 采播管理 / 文字语音 / LED 播放 ——
	//
	// 四个页面共用一套路由，类别放在路径的 {kind} 上
	// （amplifier / collect / tts / led）。它们都是 task 表的视图，按 taskpriv。
	mux.HandleFunc("GET /api/typed-tasks/terminals", req(a.handleTypedTerminals))
	mux.HandleFunc("GET /api/typed-tasks/{kind}", req(a.handleTypedList))
	mux.HandleFunc("GET /api/typed-tasks/{kind}/{id}", req(a.handleTypedGet))
	mux.HandleFunc("POST /api/typed-tasks/{kind}", tsk(a.handleTypedCreate))
	mux.HandleFunc("PUT /api/typed-tasks/{kind}/{id}", tsk(a.handleTypedUpdate))
	mux.HandleFunc("PUT /api/typed-tasks/{kind}/control/{action}", tsk(a.handleTypedControl))
	mux.HandleFunc("PUT /api/typed-tasks/{kind}/project-state", tsk(a.handleTypedProjectState))
	mux.HandleFunc("DELETE /api/typed-tasks/{kind}", tsk(a.handleTypedDelete))

	// LED 专属：任务分组与 LED 屏设备
	mux.HandleFunc("GET /api/led/folders", req(a.handleLEDFolders))
	mux.HandleFunc("GET /api/led/devices", req(a.handleLEDDeviceList))
	mux.HandleFunc("POST /api/led/devices", tsk(a.handleLEDDeviceCreate))
	mux.HandleFunc("PUT /api/led/devices/{id}", tsk(a.handleLEDDeviceUpdate))
	mux.HandleFunc("DELETE /api/led/devices", tsk(a.handleLEDDeviceDelete))

	// —— 启用管理 ——
	//
	// 定时批量启停任务，本质是任务操作，按 taskpriv。
	mux.HandleFunc("GET /api/enable-plans", req(a.handleEnableList))
	mux.HandleFunc("GET /api/enable-plans/tasks", req(a.handleEnableTasks))
	mux.HandleFunc("GET /api/enable-plans/{id}", req(a.handleEnableGet))
	mux.HandleFunc("POST /api/enable-plans", tsk(a.handleEnableCreate))
	mux.HandleFunc("PUT /api/enable-plans/{id}", tsk(a.handleEnableUpdate))
	mux.HandleFunc("DELETE /api/enable-plans", tsk(a.handleEnableDelete))

	// —— 噪声设备 / 声场分区 ——
	//
	// 声场分区会改动 terminal.soundsgroupid，与终端分区同级，按 terminalgrouppriv。
	mux.HandleFunc("GET /api/sound/devices", req(a.handleSoundDeviceList))
	mux.HandleFunc("GET /api/sound/devices/options", req(a.handleSoundDeviceOptions))
	mux.HandleFunc("GET /api/sound/devices/{id}", req(a.handleSoundDeviceGet))
	mux.HandleFunc("POST /api/sound/devices", tgp(a.handleSoundDeviceCreate))
	mux.HandleFunc("PUT /api/sound/devices/{id}", tgp(a.handleSoundDeviceUpdate))
	mux.HandleFunc("DELETE /api/sound/devices", tgp(a.handleSoundDeviceDelete))

	mux.HandleFunc("GET /api/sound/groups", req(a.handleSoundGroupList))
	mux.HandleFunc("GET /api/sound/groups/options", req(a.handleSoundGroupOptions))
	mux.HandleFunc("GET /api/sound/groups/terminals", req(a.handleSoundGroupTerminals))
	mux.HandleFunc("GET /api/sound/groups/{id}", req(a.handleSoundGroupGet))
	mux.HandleFunc("POST /api/sound/groups", tgp(a.handleSoundGroupCreate))
	mux.HandleFunc("PUT /api/sound/groups/{id}", tgp(a.handleSoundGroupUpdate))
	mux.HandleFunc("DELETE /api/sound/groups", tgp(a.handleSoundGroupDelete))

	// —— 云广播终端 / 任务传送 ——
	//
	// 两个都是只读视图，看的是 /offline 那套表的另外两个切面。
	// 下发动作仍然走 /api/offline/*，这里不重复开写接口。
	mux.HandleFunc("GET /api/cloud/terminals", req(a.handleCloudTerminals))
	mux.HandleFunc("GET /api/cloud/terminals/{id}/inventory", req(a.handleCloudInventory))
	mux.HandleFunc("POST /api/cloud/bulk", req(a.handleCloudBulk))
	mux.HandleFunc("GET /api/transfer/tasks", req(a.handleTransferList))
	mux.HandleFunc("GET /api/transfer/tasks/{id}", req(a.handleTransferDetail))
	mux.HandleFunc("GET /api/transfer/tasks/{id}/media", req(a.handleTransferMedia))
	mux.HandleFunc("POST /api/transfer/bulk", req(a.handleTransferBulk))

	// —— 健康检查（不需要登录，便于运维探活）——
	mux.HandleFunc("GET /api/health", a.handleHealth)

	// —— 前端静态文件 ——
	rawMux.Handle("/", a.staticHandler())

	return logging(rawMux)
}

// auditMux 是 http.ServeMux 的一层薄包装：注册时按路由模式查一次
// auditLabels，命中的接口自动套上操作日志中间件。
type auditMux struct {
	m *http.ServeMux
	a *app
}

func (x *auditMux) HandleFunc(pattern string, h http.HandlerFunc) {
	x.m.HandleFunc(pattern, x.a.withAudit(auditFor(pattern), h))
}

// ---------- 认证 ----------

type loginReq struct {
	Username  string `json:"username"`
	Password  string `json:"password"`
	Captcha   string `json:"captcha"`
	CaptchaID string `json:"captchaId"`
}

func (a *app) handleLogin(w http.ResponseWriter, r *http.Request) {
	var in loginReq
	if !httpx.DecodeJSON(w, r, &in) {
		return
	}

	// 长度上限与旧系统一致（BR-76）
	if len(in.Username) == 0 || len(in.Username) > 20 {
		httpx.Fail(w, httpx.CodeBadRequest, "用户名长度非法")
		return
	}
	if len(in.Password) == 0 || len(in.Password) > 20 {
		httpx.Fail(w, httpx.CodeBadRequest, "密码长度非法")
		return
	}
	if len(in.Captcha) > 10 {
		httpx.Fail(w, httpx.CodeBadRequest, "验证码长度非法")
		return
	}

	// 注意：这里没有、也不会有旧系统的 htjy123 万能验证码分支（BR-78）
	if a.cfg.Auth.CaptchaEnabled && !a.cap.Verify(in.CaptchaID, in.Captcha) {
		httpx.Fail(w, httpx.CodeBadCaptcha, "验证码错误")
		return
	}

	token, u, err := a.authMgr.Login(r.Context(), in.Username, in.Password)
	if err != nil {
		switch {
		case errors.Is(err, auth.ErrBadCredential):
			httpx.Fail(w, httpx.CodeBadCredential, "用户名或密码错误")
		case errors.Is(err, auth.ErrUserDisabled):
			httpx.Fail(w, httpx.CodeUserDisabled, "该用户已被停用")
		case errors.Is(err, auth.ErrNotRegistered):
			httpx.FailData(w, httpx.CodeForbidden, "服务器未注册，无法登录",
				map[string]string{"redirect": "register"})
		default:
			httpx.Internal(w, "登录", err)
		}
		return
	}

	// 登录是唯一一个不能靠中间件记审计的写接口：此刻请求上下文里还没有会话，
	// 用户名只有这里知道。其余写接口一律由 auditMux 统一落库（见 audit_mw.go）。
	a.auditor.Write(r.Context(), in.Username, "用户登录", audit.ClientIP(r))

	// 前端 Login.ResLogin 只取 access_token 字段，其余信息一并返回便于初始化
	httpx.OK(w, map[string]interface{}{
		"access_token": token,
		"user":         u,
		"server": map[string]interface{}{
			"readonly": u.ReadOnly,
		},
	})
}

// handleCaptcha 下发图形验证码。
//
// 响应里的 enabled 是「是否需要验证码」的唯一权威来源：auth.captcha_enabled
// 只在服务端配置一次，前端照着 enabled 决定要不要显示输入框、加不加必填校验，
// 不再自行猜测。关闭时不生成图片 —— 省掉一次无意义的绘图，也避免前端拿到
// 一张根本不会被校验的图。
func (a *app) handleCaptcha(w http.ResponseWriter, r *http.Request) {
	if !a.cfg.Auth.CaptchaEnabled {
		httpx.OK(w, map[string]any{"enabled": false})
		return
	}
	id, uri, err := a.cap.Generate()
	if err != nil {
		httpx.Internal(w, "生成验证码", err)
		return
	}
	httpx.OK(w, map[string]any{"enabled": true, "captchaId": id, "image": uri})
}

func (a *app) handleLogout(w http.ResponseWriter, r *http.Request) {
	a.authMgr.Logout(r.Header.Get(auth.HeaderToken))
	httpx.OK(w, nil)
}

func (a *app) handleMe(w http.ResponseWriter, r *http.Request) {
	u := auth.From(r.Context())
	// 每次拉取都重新读一遍权限，用户组权限变更后立即生效（修复旧版需重登的问题）
	if err := a.authMgr.Refresh(r.Context(), u); err != nil {
		log.Printf("刷新权限失败: %v", err)
	}
	httpx.OK(w, map[string]interface{}{"user": u})
}

// handleMenu 返回左侧菜单。
//
// 结构参照 docs/image/1.png：分组折叠式，看板 / 基础配置 / 资源管理 /
// 任务管理 / 云广播管理 / 用户管理，与厂商新版界面同一套组织方式。
//
// 一条原则：**只下发真的有页面的菜单项**。
// 参考图里还有「时间设置 / 节假日管理 / 遥控任务 / 终端功放 / 采播管理 /
// 文字语音 / led播放 / 启用管理 / 云广播终端 / 任务传送 / 噪声设备 / 声场分区」
// 这些条目，本项目尚未实现对应页面 —— 挂上去点了就是 404，
// 比不挂更糟，所以一律不下发。等哪个模块做出来再往对应分组里加一行即可。
func (a *app) handleMenu(w http.ResponseWriter, r *http.Request) {
	u := auth.From(r.Context())

	// menu / group 两个小助手，把重复的 meta 样板收起来，
	// 免得每加一个菜单项就抄一遍 isHide/isFull/isAffix/isKeepAlive。
	menu := func(path, name, component, icon, title string) map[string]interface{} {
		return map[string]interface{}{
			"path": path, "name": name, "component": component,
			"meta": map[string]interface{}{
				"icon": icon, "title": title, "isLink": "",
				"isHide": false, "isFull": false, "isAffix": false, "isKeepAlive": true,
			},
		}
	}
	group := func(path, name, icon, title string, children ...map[string]interface{}) map[string]interface{} {
		redirect := ""
		if len(children) > 0 {
			redirect, _ = children[0]["path"].(string)
		}
		return map[string]interface{}{
			"path": path, "name": name, "redirect": redirect,
			"meta": map[string]interface{}{
				"icon": icon, "title": title, "isLink": "",
				"isHide": false, "isFull": false, "isAffix": false, "isKeepAlive": true,
			},
			"children": children,
		}
	}

	menus := []map[string]interface{}{}

	// —— 看板 ——
	// 首页设成固定页签（isAffix），登录后落在这里，且不能被关掉。
	home := menu("/home", "home", "/home/index", "HomeFilled", "首页")
	home["meta"].(map[string]interface{})["isAffix"] = true
	menus = append(menus, group("/dashboard", "dashboard", "Odometer", "看板", home))

	// —— 基础配置 ——
	// 服务器信息按 serverpriv；备份还原与日志只对超管开放（BR-246），
	// 所以这一组的子项是按权限拼出来的，可能只剩一项。
	base := []map[string]interface{}{}
	if u.IsAdmin || u.Rights.ServerPriv == 1 {
		base = append(base, menu("/server", "server", "/server/index", "Setting", "服务器信息"))
	}
	// 时间设置对任何登录用户可见：这一页大半是只读的（服务器时间、时区、运行时长），
	// 改 NTP / 校时终端的接口自己按 serverpriv 拦，下发校时按 terminalpriv 拦。
	base = append(base, menu("/time", "time", "/time/index", "Clock", "时间设置"))
	if u.ID == 1 {
		base = append(base, menu("/backup", "backup", "/backup/index", "FolderOpened", "备份还原"))
	}
	// 节假日决定放假那天打不打铃，跟作息方案是一件事的两面，按 bellpriv
	if u.IsAdmin || u.Rights.BellPriv == 1 {
		base = append(base, menu("/holiday", "holiday", "/holiday/index", "Calendar", "节假日管理"))
	}
	if u.ID == 1 {
		base = append(base, menu("/log", "log", "/log/index", "Document", "日志"))
	}
	if len(base) > 0 {
		menus = append(menus, group("/config", "config", "Tools", "基础配置", base...))
	}

	// —— 资源管理 ——
	// 终端与文件是资源；报警分区/映射本质上也是「终端资源怎么被联动」，
	// 参考图把它们放在同一组，这里照做。
	res := []map[string]interface{}{
		// 终端列表对任何登录用户开放：可见范围由 userterminal 绑定收敛，
		// 没绑终端的用户看到的是空列表，不是别人的终端。
		menu("/terminal", "terminal", "/terminal/index", "Monitor", "终端管理"),
		// 终端分区列表对任何登录用户开放，可见范围在 SQL 里按 userid 收敛；
		// 增删改由接口按 terminalgrouppriv 拦。
		menu("/zone", "zone", "/zone/index", "Guide", "终端分区"),
	}
	if u.IsAdmin || u.Rights.MediaPriv == 1 || u.Rights.FolderPriv == 1 {
		res = append(res, menu("/media", "media", "/media/index", "Files", "文件管理"))
	}
	res = append(res,
		menu("/alarm/area", "alarmArea", "/alarm/area/index", "Grid", "报警分区"),
		menu("/alarm/mapping", "alarmMapping", "/alarm/mapping/index", "Connection", "报警映射"),
	)
	// 遥控任务：参考图 1.png 把它放在资源管理这一组的末尾
	if u.IsAdmin || u.Rights.TaskPriv == 1 {
		res = append(res, menu("/remote", "remote", "/remote/index", "Pointer", "遥控任务"))
	}
	menus = append(menus, group("/resource", "resource", "Coin", "资源管理", res...))

	// —— 任务管理 ——
	// 作息方案与文件广播是同一张 task 表的两种视图（契约 C-38），
	// 参考图也把它们并列在这一组里。
	// 参考图 1.png 这一组里共 7 项：作息方案 / 文件广播 / 终端功放 /
	// 采播管理 / 文字语音 / led播放 / 启用管理。后五项都要 taskpriv。
	taskMenus := []map[string]interface{}{
		menu("/bell", "bell", "/bell/index", "Clock", "作息方案"),
		menu("/task", "task", "/task/index", "AlarmClock", "文件广播"),
	}
	if u.IsAdmin || u.Rights.TaskPriv == 1 {
		taskMenus = append(taskMenus,
			menu("/amplifier", "amplifier", "/typed/amplifier/index", "Headset", "终端功放"),
			menu("/collect", "collect", "/typed/collect/index", "Microphone", "采播管理"),
			menu("/tts", "tts", "/typed/tts/index", "ChatDotSquare", "文字语音"),
			menu("/led", "led", "/typed/led/index", "Monitor", "led播放"),
			menu("/enable", "enable", "/enable/index", "Switch", "启用管理"),
		)
	}
	menus = append(menus, group("/taskmgr", "taskmgr", "Menu", "任务管理", taskMenus...))

	// —— 云广播管理 ——
	// 参考图这一组里的「音乐传输」「任务传送」就是离线媒体下发与离线任务下发，
	// 本项目做在同一个页面的两个页签里，所以这里只有一项。
	menus = append(menus, group("/cloud", "cloud", "Cloudy", "云广播管理",
		menu("/cloud/terminal", "cloudTerminal", "/cloud/terminal/index", "Monitor", "云广播终端"),
		menu("/offline", "offline", "/offline/index", "Download", "音乐传输"),
		menu("/transfer", "transfer", "/cloud/transfer/index", "Promotion", "任务传送"),
	))

	// —— 噪声检测 ——
	// 参考图里是独立的一组，含噪声设备与声场分区。
	menus = append(menus, group("/noise", "noise", "Odometer", "噪声检测",
		menu("/noise/device", "noiseDevice", "/noise/device/index", "Cpu", "噪声设备"),
		menu("/noise/zone", "noiseZone", "/noise/zone/index", "Grid", "声场分区"),
	))

	// —— 用户管理 ——
	if u.HasRight(auth.PrivUser) {
		menus = append(menus, group("/user", "user", "User", "用户管理",
			menu("/user/list", "userList", "/user/list/index", "UserFilled", "用户"),
			menu("/user/group", "userGroup", "/user/group/index", "Grid", "用户组"),
		))
	}

	httpx.OK(w, menus)
}

func (a *app) handleButtons(w http.ResponseWriter, r *http.Request) {
	u := auth.From(r.Context())
	canUser := u.HasRight(auth.PrivUser)
	canTerminal := u.HasRight(auth.PrivTerminal)
	canTask := u.HasRight(auth.PrivTask)
	canAlarm := u.HasRight(auth.PrivAlarmGroup)
	canBell := u.HasRight(auth.PrivBell)
	// 只有 admin 本人能管理别人的账号（BR-107），其他人即使有 userpriv 也只能改自己
	isSuper := u.ID == 1

	httpx.OK(w, map[string]map[string]bool{
		"media": {
			"upload": u.HasRight(auth.PrivMedia),
			"delete": u.HasRight(auth.PrivMedia),
			"folder": u.HasRight(auth.PrivFolder),
		},
		"user": {
			"add":    canUser && isSuper,
			"edit":   canUser,
			"delete": canUser && isSuper,
			"enable": canUser && isSuper,
		},
		"usergroup": {
			"add":    canUser && isSuper,
			"edit":   canUser && isSuper,
			"delete": canUser && isSuper,
		},
		"terminal": {
			"edit":     canTerminal,
			"control":  canTerminal, // 启停 / 音量 / 各状态开关
			"password": canTerminal,
			"delete":   canTerminal,
		},
		"alarm": {
			"mapping": canAlarm, // 设置 / 修改 / 取消报警映射
			"area":    canAlarm, // 新建 / 修改 / 删除报警分区
		},
		"bell": {
			"add":     canBell,
			"edit":    canBell, // 改方案级属性 / 改名
			"item":    canBell, // 增删改单条打铃条目
			"control": canBell, // 启用 / 停止方案
			"copy":    canBell,
			"delete":  canBell,
		},
		"task": {
			"add":     canTask,
			"edit":    canTask,
			"delete":  canTask,
			"control": canTask, // 启动 / 停止 / 暂停 / 恢复 / 启用停用方案
			"copy":    canTask,
			"folder":  canTask, // 分组增删改
		},
		"zone": {
			// 终端分区的增删改按 terminalgrouppriv
			"edit": u.HasRight(auth.PrivTerminalGroup),
		},
		"holiday": {
			// 节假日与作息方案是一件事的两面，共用 bellpriv
			"edit": canBell,
		},
		"remote": {
			"edit": canTask,
		},
		"time": {
			// 改 NTP / 校时终端是服务器级配置；下发校时是终端操作。
			// 两者权限不同，界面上要分别置灰，所以给两个键。
			"config": u.HasRight(auth.PrivServer),
			"sync":   canTerminal,
		},
	})
}

func (a *app) handleHealth(w http.ResponseWriter, r *http.Request) {
	ctx, cancel := context.WithTimeout(r.Context(), 3*time.Second)
	defer cancel()
	if err := a.st.DB().PingContext(ctx); err != nil {
		httpx.Fail(w, httpx.CodeInternal, "数据库不可用")
		return
	}
	httpx.OK(w, map[string]string{"status": "ok"})
}

// ---------- 文件夹 ----------

func (a *app) handleFolderTree(w http.ResponseWriter, r *http.Request) {
	u := auth.From(r.Context())
	scene := folder.SceneManage
	if r.URL.Query().Get("scene") == string(folder.ScenePicker) {
		scene = folder.ScenePicker
	}
	withCount := r.URL.Query().Get("includeMediaCount") != "false"

	res, err := a.folders.Tree(r.Context(), u, scene, withCount)
	if err != nil {
		httpx.Internal(w, "构建文件夹树", err)
		return
	}
	httpx.OK(w, res)
}

// ---------- 媒体 ----------

func (a *app) handleMediaList(w http.ResponseWriter, r *http.Request) {
	u := auth.From(r.Context())
	q := r.URL.Query()

	folderID, err := strconv.ParseInt(q.Get("folderId"), 10, 64)
	if err != nil || folderID <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "folderId 必须是正整数")
		return
	}

	pager := store.NewPager(atoiDefault(q.Get("pageNum"), 1), atoiDefault(q.Get("pageSize"), 18))

	res, err := a.medias.List(r.Context(), u, media.ListQuery{
		FolderID:  folderID,
		SearchKey: q.Get("searchKey"),
		Keyword:   strings.TrimSpace(q.Get("keyword")),
		OrderBy:   q.Get("orderBy"),
		Order:     q.Get("order"),
		Pager:     pager,
	})
	if err != nil {
		writeMediaFolderErr(w, err, "查询媒体列表")
		return
	}

	// 分页信封字段必须是 {list,pageNum,pageSize,total}，与前端 ResPage<T> 对齐
	httpx.OK(w, map[string]interface{}{
		"list":      res.Items,
		"pageNum":   pager.PageNum,
		"pageSize":  pager.PageSize,
		"total":     res.Total,
		"folder":    res.Folder,
		"scopeNote": res.ScopeNote,
	})
}

func (a *app) handleMediaStream(w http.ResponseWriter, r *http.Request) {
	a.serveMediaFile(w, r, false)
}

func (a *app) handleMediaDownload(w http.ResponseWriter, r *http.Request) {
	a.serveMediaFile(w, r, true)
}

// serveMediaFile 同时服务试听与下载。
//
// 相比旧版 download_media_file.php 的三点修复：
//   - 只接受 mediaId，物理路径一律从库里取（杜绝目录穿越，D-02）
//   - 强制登录与可见范围校验
//   - 用 http.ServeContent 提供 Range 支持，试听可拖动进度
func (a *app) serveMediaFile(w http.ResponseWriter, r *http.Request, download bool) {
	u := auth.From(r.Context())

	id, err := strconv.ParseInt(r.PathValue("id"), 10, 64)
	if err != nil || id <= 0 {
		httpx.Fail(w, httpx.CodeBadRequest, "媒体 ID 非法")
		return
	}

	d, err := a.medias.Get(r.Context(), id)
	if err != nil {
		httpx.Fail(w, httpx.CodeNotFound, "媒体不存在")
		return
	}
	// 可见范围：普通用户只能访问自己上传的媒体（与列表口径一致，BR-30）
	if !u.IsAdmin && d.UserID != u.ID {
		httpx.Fail(w, httpx.CodeForbidden, "无权访问该媒体")
		return
	}

	path, err := a.medias.PhysicalPath(d.FileName)
	if err != nil {
		httpx.Fail(w, httpx.CodeFileMissing, "媒体文件不存在")
		return
	}

	f, err := os.Open(path)
	if err != nil {
		httpx.Fail(w, httpx.CodeFileMissing, "媒体文件无法读取")
		return
	}
	defer f.Close()

	fi, err := f.Stat()
	if err != nil {
		httpx.Internal(w, "读取媒体文件信息", err)
		return
	}

	if download {
		// 下载文件名 = media.name + "." + media.typeid（BR-63，与旧系统一致）。
		// 旧版用 iconv 硬转 gb2312 会乱码，这里改用 RFC 5987 编码。
		name := d.Name + "." + d.TypeID
		w.Header().Set("Content-Type", "application/octet-stream")
		w.Header().Set("Content-Disposition",
			"attachment; filename*=UTF-8''"+urlEncode(name))
	} else {
		w.Header().Set("Content-Type", "audio/mpeg")
	}
	w.Header().Set("Accept-Ranges", "bytes")

	http.ServeContent(w, r, filepath.Base(path), fi.ModTime(), f)
}

// ---------- 静态文件 ----------

// staticHandler 托管前端 dist。
// 前端用 hash 路由，所以只需把找不到的路径回落到 index.html。
func (a *app) staticHandler() http.Handler {
	dir := a.cfg.Server.StaticDir
	fs := http.FileServer(http.Dir(dir))
	index := filepath.Join(dir, "index.html")

	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		// 未匹配的 /api/* 必须回 JSON 404，绝不能回落到 index.html。
		//
		// 之前就是回落的：前端把 baseURL 和路径里的 /api 叠成了 /api/api/login，
		// 服务端照样回 200 + 一段 HTML。axios 拦截器看 data.code 是 undefined，
		// 当成功处理；access_token 自然也是 undefined，token 被置空，
		// 随后拉菜单 401，catch 里清 token 跳回登录页 —— 全程零报错。
		// 一个纯粹的路径拼写问题，表现成「点登录没反应」，排查成本极高。
		//
		// 注意 httpx.Fail 按 Geeker 拦截器的约定回 HTTP 200 + body 里的业务码，
		// 所以访问日志里这条仍然是 200，单独打一行告警才看得见。
		if strings.HasPrefix(r.URL.Path, "/api/") || r.URL.Path == "/api" {
			log.Printf("⚠ 未注册的接口路径 %s %s —— 检查前端 baseURL 是否与路径里的 /api 重复",
				r.Method, r.URL.Path)
			httpx.Fail(w, httpx.CodeNotFound, "接口不存在: "+r.URL.Path)
			return
		}

		clean := filepath.Clean(r.URL.Path)
		full := filepath.Join(dir, clean)
		if st, err := os.Stat(full); err == nil && !st.IsDir() {
			fs.ServeHTTP(w, r)
			return
		}
		f, err := os.Open(index)
		if err != nil {
			http.Error(w, "前端资源未部署", http.StatusNotFound)
			return
		}
		defer f.Close()
		w.Header().Set("Content-Type", "text/html; charset=utf-8")
		_, _ = io.Copy(w, f)
	})
}

// ---------- 小工具 ----------

// statusRecorder 记下真实的 HTTP 状态码。
// 日志原先只有「方法 路径 耗时」，200 和 404 长得一模一样，
// /api/api/login 连打两天都看不出异常。
type statusRecorder struct {
	http.ResponseWriter
	status int
}

func (s *statusRecorder) WriteHeader(code int) {
	s.status = code
	s.ResponseWriter.WriteHeader(code)
}

func (s *statusRecorder) Write(b []byte) (int, error) {
	if s.status == 0 {
		s.status = http.StatusOK
	}
	return s.ResponseWriter.Write(b)
}

func logging(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		start := time.Now()
		rec := &statusRecorder{ResponseWriter: w}
		next.ServeHTTP(rec, r)
		if strings.HasPrefix(r.URL.Path, "/api/") {
			if rec.status == 0 {
				rec.status = http.StatusOK
			}
			log.Printf("%s %s %d %s", r.Method, r.URL.Path, rec.status,
				time.Since(start).Round(time.Millisecond))
		}
	})
}

func atoiDefault(s string, def int) int {
	if v, err := strconv.Atoi(s); err == nil {
		return v
	}
	return def
}

// urlEncode 按 RFC 3986 编码，用于 Content-Disposition 的 filename*。
func urlEncode(s string) string {
	const hex = "0123456789ABCDEF"
	var b strings.Builder
	for _, c := range []byte(s) {
		if (c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') ||
			(c >= '0' && c <= '9') || strings.IndexByte("-_.~", c) >= 0 {
			b.WriteByte(c)
			continue
		}
		b.WriteByte('%')
		b.WriteByte(hex[c>>4])
		b.WriteByte(hex[c&0x0f])
	}
	return b.String()
}
