package task

import (
	"context"
	"database/sql"
	"fmt"
)

// FillGroupIDs 把每台终端**当前所属的分区号**填进终端清单。
//
// # 这一列是什么、谁在读
//
// terminaloftask.groupid 是后台 C 服务下发时实际读的一列
// （见 internal/zone 的包注释：不同步它，任务就照着一个过时的分区号去播）。
// 它「只是终端分区的快照，没有独立语义」—— 所有写入方填的都该是同一件事：
// **这台终端此刻属于哪个分区**。
//
// # 为什么由服务端算，而不是照抄调用方传来的
//
// 既然所有写入方要填的是同一个值，就没有理由让每个调用方各查一遍、各填一份。
// 实际情况也证明这条路走不通：
//
//	界面                 从终端选择器的 groupId 带过来 —— 对
//	开发者接口 / AI 助手   压根没填，一律落 0 —— 错，而且**不报错**
//
// 落 0 的表现是：任务建出来了、界面上看着正常，后台按分区号 0 下发，
// 播出来的范围是错的。这正是旧版的 D-107（两条平行逗号串按下标对齐，
// 长度对不上就静默写 0），换了套写法之后又从另一个入口漏了进来。
//
// 少填一个字段没有任何症状，所以它不能靠「每个调用方都记得」——
// 要由收口的地方统一算掉。
//
// # 取值口径
//
// 与终端选择器（picker.go）完全一致：terminalofgroup 里 id 最小的那一条，
// 没有就是 0（未分区）。两处不一致的话，界面上显示的分区和任务里存的分区
// 会对不上，而这种对不上没人查得出来。
//
// 无条件覆盖调用方传来的值：那份可能来自一份缓存过的选择器结果，
// 而这一列的定义就是「此刻属于哪个分区」。与终端编辑、分区改成员那两处的
// 同步是同一套处理（BR-146 / 契约 C-23）。
func FillGroupIDs(ctx context.Context, db *sql.DB, terms []TerminalRef) error {
	if len(terms) == 0 {
		return nil
	}
	ids := make([]int64, 0, len(terms))
	seen := make(map[int64]bool, len(terms))
	for _, t := range terms {
		if t.TerminalID > 0 && !seen[t.TerminalID] {
			seen[t.TerminalID] = true
			ids = append(ids, t.TerminalID)
		}
	}
	if len(ids) == 0 {
		return nil
	}
	ph, args := placeholders(ids)
	rs, err := db.QueryContext(ctx, `
		SELECT t.id, COALESCE((SELECT tog.groupid FROM terminalofgroup tog
		                        WHERE tog.terminalid = t.id ORDER BY tog.id LIMIT 1), 0)
		FROM terminal t WHERE t.id IN (`+ph+`)`, args...)
	if err != nil {
		return fmt.Errorf("查询终端所属分区: %w", err)
	}
	defer rs.Close()
	group := make(map[int64]int64, len(ids))
	for rs.Next() {
		var id, gid int64
		if err := rs.Scan(&id, &gid); err != nil {
			return fmt.Errorf("查询终端所属分区: %w", err)
		}
		group[id] = gid
	}
	if err := rs.Err(); err != nil {
		return fmt.Errorf("查询终端所属分区: %w", err)
	}
	for i := range terms {
		terms[i].GroupID = group[terms[i].TerminalID]
	}
	return nil
}
