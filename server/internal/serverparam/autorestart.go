package serverparam

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"regexp"
	"strings"
)

// 定时重启 / 定时关机（「服务设置」页里的重启设置、重启时间、关机设置、关机时间）。
//
// # 四项全都落在 task 表的**同一条**系统任务上
//
// `serverbaseparam` 里没有任何与重启/关机相关的列（48 列全查过）。
// 它们存在 `task` 表里那条：
//
//	taskid 70000  taskname 'reset'  tasktype 13  playtime 04:00:00
//
// `tasktype = 13` 就是「系统任务」，`audioserver.sql` 的建表脚本里自带这一行。
//
// # 重启和关机是**互斥**的，靠 cmdargs 区分
//
// 依据是旧版 `ok112/do - 副本.php` 第 6006~6046 行，它只写这一条 SQL 的三种形态：
//
//	重启启用          UPDATE task SET projectstate='0', playtime=<重启时间>, cmdargs='0'        WHERE taskid=70000
//	重启停用+关机启用  UPDATE task SET projectstate='0', playtime=<关机时间>, cmdargs='shutdown' WHERE taskid=70000
//	两个都停用        UPDATE task SET projectstate='1'                                          WHERE taskid=70000
//
// 也就是说：
//
//   - 只有一个 `playtime`，重启时间和关机时间**共用**这一列，同时只有一个有值
//   - `cmdargs='0'` 是重启，`cmdargs='shutdown'` 是关机
//   - 两个都开时旧版以**重启**为准（它先判 `$enreboot`）
//
// ⚠ 早前这里只写 projectstate/playtime/exemodel，**从不写 cmdargs**。
//   如果库里那条正好是 `cmdargs='shutdown'`，点「重启设置=启用」出来的
//   其实还是一条关机任务 —— 到点直接关机而不是重启。现在每次都显式写 cmdargs。
//
// ⚠ `cmd` 列在这条任务上存的是旧版的「复杂密码」开关（`fuzamima`），
//   跟重启/关机无关。下面的 UPDATE 一律不碰它。

// RestartMode 是那条系统任务当前的形态。
type RestartMode string

const (
	// ModeOff 两个都停用（projectstate = 1）
	ModeOff RestartMode = "off"
	// ModeReboot 定时重启（cmdargs = '0'）
	ModeReboot RestartMode = "reboot"
	// ModeShutdown 定时关机（cmdargs = 'shutdown'）
	ModeShutdown RestartMode = "shutdown"
)

// AutoRestart 是定时重启 / 定时关机的配置。
type AutoRestart struct {
	// Exists 表示库里有没有那条系统任务。没有的话开启时会新建一条。
	Exists bool  `json:"exists"`
	TaskID int64 `json:"taskId"`
	// Mode 是当前形态：off / reboot / shutdown。三者互斥。
	Mode RestartMode `json:"mode"`
	// Time 是当前生效的那个时间（HH:MM:SS）。
	// ⚠ 重启和关机共用 playtime 这一列，所以只有 Mode 对应的那个时间是真的。
	Time string `json:"time"`
	// ExeModel 是 7 位星期掩码，第 0 位是周一。全 0 表示后台永远不执行。
	ExeModel string `json:"exemodel"`
}

const (
	// 系统任务的类型号与任务名，与 audioserver.sql 自带的那一行一致
	resetTaskType = 13
	resetTaskName = "reset"
	// ⚠ task.projectstate 的口径与列注释相反：0 = 启用、1 = 停用
	taskStateEnabled  = 0
	taskStateDisabled = 1
	// cmdargs 的两种取值，逐字抄自旧版
	cmdArgsReboot   = "0"
	cmdArgsShutdown = "shutdown"
)

var reClock = regexp.MustCompile(`^\d{2}:\d{2}:\d{2}$`)
var reMask7 = regexp.MustCompile(`^[01]{7}$`)

// GetAutoRestart 读配置。库里没有那条系统任务时返回 Exists=false。
func (s *Service) GetAutoRestart(ctx context.Context) (*AutoRestart, error) {
	out := &AutoRestart{Mode: ModeOff, Time: "04:00:00", ExeModel: "0000000"}
	var state int
	var cmdargs string
	err := s.db.QueryRowContext(ctx, `
		SELECT taskid, COALESCE(projectstate,0),
		       COALESCE(CAST(playtime AS CHAR),'04:00:00'), COALESCE(exemodel,'0000000'),
		       COALESCE(cmdargs,'0')
		FROM task WHERE tasktype = ? AND taskname = ? ORDER BY taskid LIMIT 1`,
		resetTaskType, resetTaskName).
		Scan(&out.TaskID, &state, &out.Time, &out.ExeModel, &cmdargs)
	if errors.Is(err, sql.ErrNoRows) {
		return out, nil
	}
	if err != nil {
		return nil, fmt.Errorf("查询定时重启任务: %w", err)
	}
	out.Exists = true

	// 「启用」还要求星期掩码不是全 0 —— 掩码全 0 的任务后台永远不会执行
	if state == taskStateEnabled && out.ExeModel != "0000000" {
		if strings.EqualFold(strings.TrimSpace(cmdargs), cmdArgsShutdown) {
			out.Mode = ModeShutdown
		} else {
			out.Mode = ModeReboot
		}
	}
	return out, nil
}

// SaveAutoRestart 写配置。三种形态与旧版逐条对齐（见文件头的 SQL 对照表）。
//
// 停用时只把 projectstate 置成停用，**不删那条系统任务** ——
// 它是建库脚本自带的行，删了以后想再开就得凭空造一条。
func (s *Service) SaveAutoRestart(ctx context.Context, in AutoRestart) error {
	in.Time = strings.TrimSpace(in.Time)
	in.ExeModel = strings.TrimSpace(in.ExeModel)

	switch in.Mode {
	case ModeOff, ModeReboot, ModeShutdown:
	default:
		return fmt.Errorf("mode 只能是 off / reboot / shutdown")
	}
	if in.Mode != ModeOff && !reClock.MatchString(in.Time) {
		return fmt.Errorf("时间格式不正确，必须是 HH:MM:SS")
	}

	cur, err := s.GetAutoRestart(ctx)
	if err != nil {
		return err
	}

	// ExeModel 允许留空 = 「界面上没有星期这一项，你自己定」。
	//
	// :80 的服务设置页（docs/image/4.png）只有时间、没有星期选择框。
	// 新版界面照它做，于是这一列的取值规则收在这里，前端不再传：
	//
	//   库里已经有掩码  → 原样保留（现网是 0001000，只在周四；
	//                     没有界面能表达这个值，那就别替用户改掉它）
	//   库里是全 0 且要启用 → 补成 1111111，即每天
	if in.ExeModel == "" {
		in.ExeModel = cur.ExeModel
		if in.Mode != ModeOff && (in.ExeModel == "" || in.ExeModel == "0000000") {
			in.ExeModel = "1111111"
		}
		if in.ExeModel == "" {
			in.ExeModel = "0000000"
		}
	}
	if !reMask7.MatchString(in.ExeModel) {
		return fmt.Errorf("星期掩码必须是 7 位的 0/1 字符串")
	}
	if in.Mode != ModeOff && in.ExeModel == "0000000" {
		return fmt.Errorf("启用时请至少选择一个星期")
	}

	if !cur.Exists {
		if in.Mode == ModeOff {
			// 本来就没有，也没要求启用 —— 什么都不用做
			return nil
		}
		args := cmdArgsReboot
		if in.Mode == ModeShutdown {
			args = cmdArgsShutdown
		}
		// 列值照抄 audioserver.sql 自带的那一行，只把时间、星期、cmdargs 换成用户填的
		if _, err := s.db.ExecContext(ctx, `
			INSERT INTO task (taskname, israndomplay, projectstate, timelengthtype, timelength,
			                  prepower, datasendmodel, state, startdate, enddate, playtime, endtime,
			                  exemodel, priority, tasktype, channel, bandrate, samplerate,
			                  cmd, cmdargs, playfileid, info, defaultvolume, task_user_id,
			                  sec_task_id, parentid, offlinestate,
			                  interval_s, intplaylength, intplaylengthtype)
			VALUES (?,0,?,1,0, 2,0,0,'2011-01-01','2060-01-01',?, '00:00:00',
			        ?,10,?,0,0,0, 0,?,0,'',0,1, 0,0,0, 0,0,0)`,
			resetTaskName, taskStateEnabled, in.Time, in.ExeModel, resetTaskType, args); err != nil {
			return fmt.Errorf("新建系统任务: %w", err)
		}
		return nil
	}

	// 停用：只改 projectstate，时间与 cmdargs 原样留着 ——
	// 旧版就是这么做的，下次再启用时上一次的取值还在。
	if in.Mode == ModeOff {
		if _, err := s.db.ExecContext(ctx,
			`UPDATE task SET projectstate = ? WHERE taskid = ?`,
			taskStateDisabled, cur.TaskID); err != nil {
			return fmt.Errorf("停用系统任务: %w", err)
		}
		return nil
	}

	args := cmdArgsReboot
	if in.Mode == ModeShutdown {
		args = cmdArgsShutdown
	}
	// ⚠ cmdargs 必须显式写。不写的话「重启」可能沿用上一次的 'shutdown'，
	//   到点就是关机而不是重启。
	if _, err := s.db.ExecContext(ctx,
		`UPDATE task SET projectstate = ?, playtime = ?, exemodel = ?, cmdargs = ? WHERE taskid = ?`,
		taskStateEnabled, in.Time, in.ExeModel, args, cur.TaskID); err != nil {
		return fmt.Errorf("修改系统任务: %w", err)
	}
	return nil
}
