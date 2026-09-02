package terminal

import (
	"context"
	"database/sql"
	"errors"
	"fmt"

	"htweb/internal/auth"
)

// 终端替换（ok112 的 getterminalid.php）。
//
// # 它到底在干什么
//
// 现场设备坏了换一台新的，新设备上电后自己注册进来，拿到一个**新的 id**。
// 但原来那台的 id 已经被一堆东西引用着 —— 任务的下发列表、终端分区、
// 快捷键映射、呼叫组成员……逐个改一遍不现实。
//
// 所以「替换」做的是：**让新设备顶替旧记录的 id**，旧 id 上原有的绑定
// 就自动落到新硬件头上，一条都不用重建。
//
// # 旧实现的问题
//
// ok112 只有两句：
//
//	DELETE FROM terminal WHERE id = 目标;
//	UPDATE terminal SET id = 目标 WHERE id = 源;
//
// 主键改了，**引用它的十几张表一行都没动**。后果分两种：
//
//   - 目标不存在时（纯粹改号）：源终端原有的分区归属、任务下发、快捷键
//     全部指向一个不复存在的 id，静默变成孤儿行，界面上表现为「终端还在，
//     但不在任何分区里、任务里也找不到它」。
//   - 目标存在时（真正的替换）：目标的绑定被新硬件继承 —— 这部分是对的，
//     也正是这个功能的目的；但源终端自己原有的绑定同样成了孤儿。
//
// 新版按两种语义分别处理，见下面 Replace 的注释。孤儿行不再产生。
//
// # 为什么不做成「改 id」这么一句
//
// terminal.id 是被广泛引用的业务主键，库里没有外键约束兜底（旧库全是
// MyISAM 时代留下的习惯），只能靠代码把每张表都点到。表清单与 delete.go
// 的 intTables 保持一致 —— 那边删、这边改，漏掉任何一张的后果是一样的。

var (
	// ErrReplaceSame 源和目标是同一个 id。
	ErrReplaceSame = errors.New("源终端与目标 ID 相同")
	// ErrReplaceTypeMismatch 目标已存在但型号不同。
	ErrReplaceTypeMismatch = errors.New("目标终端与源终端型号不同")
	// ErrReplaceTargetOnline 目标已存在且在线。
	ErrReplaceTargetOnline = errors.New("目标终端在线，不能被替换")
)

// ReplaceResult 说明这次替换实际做了什么。
type ReplaceResult struct {
	SourceID int64 `json:"sourceId"`
	TargetID int64 `json:"targetId"`
	// Mode 为 "renumber" 表示目标 id 空闲，只是换了个号；
	// 为 "takeover" 表示目标原本存在，其绑定被源终端接管。
	Mode string `json:"mode"`
	// Moved 是改号时顺带迁移的关联行数（takeover 模式下为清理掉的孤儿行数）。
	Affected int64 `json:"affected"`
	// TargetName 是被顶替掉的那条记录的名字，takeover 模式下才有。
	TargetName string `json:"targetName,omitempty"`
}

// refTables 是所有以整型 terminalid 引用 terminal.id、且新系统确实在用的表。
//
// 前 14 张与 delete.go 的 intTables 同源，改一处要同时改另一处。后两张是
// delete.go 用单独语句处理、因而没进那个清单，但改号同样要跟着走：
//
//	terminaloffolder  终端在终端分区树里的归属
//	soundgroup        终端在声场分区里的归属（噪声检测用，另见 sound/sound.go）
//
// ⚠ 库里还有 7 张带 terminalid 的表没列进来：ai_device、centralctrl、ctrlnet、
//
//	ctrloftermianl、terminalattrbute、terminalgrouplist、terminalofararmgroup。
//	它们在新系统里只出现在 serverparam/factory.go 的「恢复出厂」表清单中，
//	没有任何读写，且其 terminalid 是否真指向 terminal.id 无从确认
//	（ctrlnet 之类看着更像设备地址）。宁可不动，也不能凭猜改别人的数据。
//	真要纳入，先确认语义再加，别直接往这个数组里塞。
var refTables = []string{
	"terminalfolder", "leddevice", "ledoftask", "cameramap",
	"terminalkeymap", "terminalofgroup", "terminaloftask",
	"terminalofcallgroup", "offlinemediaofterminal", "offlinetaskofterminal",
	"camerofterminal", "userterminal", "terminalofalarmgroup",
	"terminalkeymaptask",
	"terminaloffolder", "soundgroup",
}

// Replace 把 srcID 这台终端的 id 改成 dstID。
//
// 两种语义：
//
//   - dstID 空闲 —— 纯改号。源终端连同它的全部关联行一起迁到新 id，
//     绑定关系原样保留。
//   - dstID 已被占用 —— 真正的替换。要求两台同型号、且目标离线
//     （在线说明设备还活着，不该被顶掉，与 ok112 判据一致）。
//     目标那条 terminal 记录被删除，但**它的关联行保留下来**，
//     由改号后的源终端接管；源终端自己原有的关联行则被清掉，
//     否则同一个 id 上会出现两套重复绑定。
func (s *Service) Replace(ctx context.Context, u *auth.User, srcID, dstID int64) (*ReplaceResult, error) {
	if srcID == dstID {
		return nil, ErrReplaceSame
	}
	if dstID <= 0 {
		return nil, fmt.Errorf("目标 ID 必须是正整数")
	}
	// 源终端必须存在，并且当前用户有权操作它。
	// ⚠ CheckBound 把「不存在 / 没绑定」放进 skipped 而不是返回 error，
	//   所以要看 ok 里到底有没有它，只判 err 会把越权当成通过。
	ok, skipped, err := s.CheckBound(ctx, u, []int64{srcID})
	if err != nil {
		return nil, err
	}
	if len(ok) == 0 {
		if len(skipped) > 0 {
			return nil, fmt.Errorf("%w: %s", ErrNoPermission, skipped[0].Detail)
		}
		return nil, ErrNotFound
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	var srcType int
	if err := tx.QueryRowContext(ctx,
		`SELECT typeid FROM terminal WHERE id = ?`, srcID).Scan(&srcType); err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrNotFound
		}
		return nil, fmt.Errorf("查询源终端: %w", err)
	}

	out := &ReplaceResult{SourceID: srcID, TargetID: dstID}

	var dstType, dstNet int
	var dstName string
	err = tx.QueryRowContext(ctx,
		`SELECT typeid, COALESCE(netstate,0), COALESCE(terminalname,'') FROM terminal WHERE id = ?`,
		dstID).Scan(&dstType, &dstNet, &dstName)
	switch {
	case errors.Is(err, sql.ErrNoRows):
		out.Mode = "renumber"
	case err != nil:
		return nil, fmt.Errorf("查询目标终端: %w", err)
	default:
		if dstType != srcType {
			return nil, ErrReplaceTypeMismatch
		}
		if dstNet != 0 {
			return nil, ErrReplaceTargetOnline
		}
		out.Mode = "takeover"
		out.TargetName = dstName
	}

	if out.Mode == "takeover" {
		// 目标的绑定要留给新硬件继承，所以这里删的是**源**自己的关联行。
		n, err := deleteRefs(ctx, tx, srcID)
		if err != nil {
			return nil, err
		}
		out.Affected = n
		// 以源终端为宿主的呼叫组也一并清掉（它绑的是源的旧身份）
		if err := dropCallGroups(ctx, tx, srcID); err != nil {
			return nil, err
		}
		if _, err := tx.ExecContext(ctx, `DELETE FROM terminal WHERE id = ?`, dstID); err != nil {
			return nil, fmt.Errorf("删除被替换的终端: %w", err)
		}
	} else {
		// 纯改号：关联行跟着走。
		n, err := moveRefs(ctx, tx, srcID, dstID)
		if err != nil {
			return nil, err
		}
		out.Affected = n
	}

	if _, err := tx.ExecContext(ctx,
		`UPDATE terminal SET id = ? WHERE id = ?`, dstID, srcID); err != nil {
		return nil, fmt.Errorf("变更终端 ID: %w", err)
	}
	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("提交事务: %w", err)
	}
	return out, nil
}

// moveRefs 把所有引用 src 的关联行改指向 dst，返回改动行数。
func moveRefs(ctx context.Context, tx *sql.Tx, src, dst int64) (int64, error) {
	var total int64
	for _, t := range refTables {
		res, err := tx.ExecContext(ctx,
			`UPDATE `+t+` SET terminalid = ? WHERE terminalid = ?`, dst, src)
		if err != nil {
			return 0, fmt.Errorf("迁移 %s: %w", t, err)
		}
		n, _ := res.RowsAffected()
		total += n
	}
	// terminalkey.terminalid 是 varchar(45)，用字符串比对
	res, err := tx.ExecContext(ctx,
		`UPDATE terminalkey SET terminalid = ? WHERE terminalid = ?`,
		fmt.Sprint(dst), fmt.Sprint(src))
	if err != nil {
		return 0, fmt.Errorf("迁移 terminalkey: %w", err)
	}
	n, _ := res.RowsAffected()
	total += n
	// 以它为宿主的呼叫组
	res, err = tx.ExecContext(ctx,
		`UPDATE callgroup SET terminalid = ? WHERE terminalid = ?`, dst, src)
	if err != nil {
		return 0, fmt.Errorf("迁移呼叫组: %w", err)
	}
	n, _ = res.RowsAffected()
	return total + n, nil
}

// deleteRefs 删掉所有引用 id 的关联行，返回删除行数。
func deleteRefs(ctx context.Context, tx *sql.Tx, id int64) (int64, error) {
	var total int64
	for _, t := range refTables {
		res, err := tx.ExecContext(ctx,
			`DELETE FROM `+t+` WHERE terminalid = ?`, id)
		if err != nil {
			return 0, fmt.Errorf("清理 %s: %w", t, err)
		}
		n, _ := res.RowsAffected()
		total += n
	}
	res, err := tx.ExecContext(ctx,
		`DELETE FROM terminalkey WHERE terminalid = ?`, fmt.Sprint(id))
	if err != nil {
		return 0, fmt.Errorf("清理 terminalkey: %w", err)
	}
	n, _ := res.RowsAffected()
	return total + n, nil
}

// dropCallGroups 删掉以 id 为宿主的呼叫组连同其成员。
func dropCallGroups(ctx context.Context, tx *sql.Tx, id int64) error {
	if _, err := tx.ExecContext(ctx, `
		DELETE FROM terminalofcallgroup
		WHERE selectgroupid IN (SELECT id FROM callgroup WHERE terminalid = ?)`, id); err != nil {
		return fmt.Errorf("清理呼叫组成员: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM callgroup WHERE terminalid = ?`, id); err != nil {
		return fmt.Errorf("清理呼叫组: %w", err)
	}
	return nil
}
