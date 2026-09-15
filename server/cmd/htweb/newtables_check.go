package main

import (
	"context"
	"database/sql"
	"log"
	"strings"
	"time"
)

// 启动时查一遍「新版自己建的那几张表」在不在，缺了就在日志里点名。
//
// # 为什么要有这一段
//
// 这几张表 htweb 自己建不了 —— 运行账号只有 DML、没有任何 DDL 权限
// （见 deploy/README.md，那是这套系统与旧库共存的前提）。所以建表脚本
// 得管理员拿另一个账号单独跑一次。
//
// 于是每次上新服务器都有同一个坑：**程序升了、脚本忘了跑**。
// 表现是那个功能「打不开」——
//
//   - 地图：菜单里有，一进去弹一句红条，几秒钟就没了
//   - AI 助手：面板能开，一说话就报错
//   - 开发者密钥：列表空着，发密钥报错
//
// 三个都不会在日志里留下任何痕迹，只能靠人一个个去点。
// 现场实际发生过一次：地图「不显示」，查了半天才想起脚本没跑。
//
// 现在启动时自己查一遍，缺哪张就在 htweb.log 里写清楚**缺的是哪张、
// 跑哪个脚本能补上**。看日志就能定位，不用再去界面上一个个试。
//
// ⚠ 缺表不是致命错误，**不能因此拒绝启动**：广播、任务、终端这些正事
// 一张新表都不依赖，为了一个附加功能把整套系统停掉是本末倒置。
// 只记日志，其余照常跑。

// newTable 是一张「新版自己建的表」以及它属于哪个脚本。
type newTable struct {
	Name    string
	Script  string
	Feature string
}

// ⚠ 加了新表就往这里补一条 —— 漏了的后果是又回到「功能打不开、日志里没话说」。
var newTables = []newTable{
	{"map_image", "db/map_tables.sql", "地图"},
	{"map_terminal", "db/map_tables.sql", "地图"},

	{"api_key", "db/openapi_tables.sql", "开发者接口"},
	{"api_play", "db/openapi_tables.sql", "开发者接口"},

	{"assistant_session", "db/assistant_tables.sql", "AI 助手"},
	{"assistant_message", "db/assistant_tables.sql", "AI 助手"},
	{"assistant_setting", "db/assistant_tables.sql", "AI 助手"},
	{"assistant_undo", "db/assistant_tables.sql", "AI 助手"},
	{"assistant_task_override", "db/assistant_tables.sql", "AI 助手"},
	{"assistant_runtime_play", "db/assistant_tables.sql", "AI 助手"},

	{"enable_run", "db/enable_tables.sql", "启用计划（到结束时间恢复状态）"},
}

// checkNewTables 查一遍并把缺的按脚本归拢着报出来。
//
// 走 information_schema 一次问完，不去 SELECT 每张表 —— 十次往返没必要，
// 而且真缺表时那十次里每一次都会在 MySQL 的错误日志里留一条 1146。
func checkNewTables(ctx context.Context, db *sql.DB) {
	if db == nil {
		return
	}
	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	names := make([]interface{}, len(newTables))
	ph := make([]string, len(newTables))
	for i, t := range newTables {
		names[i] = t.Name
		ph[i] = "?"
	}
	rows, err := db.QueryContext(ctx,
		`SELECT table_name FROM information_schema.tables
		  WHERE table_schema = DATABASE() AND table_name IN (`+strings.Join(ph, ",")+`)`, names...)
	if err != nil {
		// 查不动就算了。这一段是帮人定位问题的，它自己不该变成一个新问题。
		log.Printf("新表自检跳过（查 information_schema 失败）: %v", err)
		return
	}
	defer rows.Close()

	have := map[string]bool{}
	for rows.Next() {
		var n string
		if err := rows.Scan(&n); err != nil {
			return
		}
		have[strings.ToLower(n)] = true
	}

	// 按脚本归拢：一个脚本缺了报一行，而不是缺六张表刷六行
	missing := map[string][]string{}
	feature := map[string]string{}
	order := []string{}
	for _, t := range newTables {
		if have[strings.ToLower(t.Name)] {
			continue
		}
		if _, seen := missing[t.Script]; !seen {
			order = append(order, t.Script)
		}
		missing[t.Script] = append(missing[t.Script], t.Name)
		feature[t.Script] = t.Feature
	}
	if len(order) == 0 {
		return
	}
	for _, script := range order {
		log.Printf("⚠ %s 用不了：缺表 %s。请用有 DDL 权限的账号执行一次 `mysql -uroot audioserver < %s`",
			feature[script], strings.Join(missing[script], "、"), script)
	}
	log.Printf("⚠ 以上只影响对应功能，广播/任务/终端不受影响，服务照常启动。详见 deploy/README.md")
}
