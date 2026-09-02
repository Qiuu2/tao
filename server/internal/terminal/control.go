package terminal

import (
	"context"
	"fmt"

	"htweb/internal/auth"
)

// Skipped 说明某个终端为什么没被执行。
type Skipped struct {
	ID     int64  `json:"id"`
	Name   string `json:"name"`
	Reason string `json:"reason"`
	Detail string `json:"detail"`
}

// OpResult 是批量操作的结果，语义是「部分成功」。
//
// 旧版只要有一个终端离线就整批中止（exit_back_function），
// 已经通过校验的终端也不执行（缺陷 D-80）。
// 新版对每个终端独立判定：能做的做掉，做不了的列出来。
type OpResult struct {
	Succeeded []int64   `json:"succeeded"`
	Skipped   []Skipped `json:"skipped"`
	Notified  bool      `json:"notified"`
}

// state 是一次批量校验取回的终端状态。
type state struct {
	id       int64
	name     string
	netState int
	typeID   int
	isDecode bool
	isEncode bool
	bound    bool
	// caps 是按 ok112 的 get_terminal_type() 算出来的能力集，
	// 用来判断「这个操作对这台终端有没有意义」。见 caps.go。
	caps Caps
}

// loadStates 一次性把这批终端的状态、类型能力、用户绑定关系全查出来。
//
// 旧版是三重 N 次查询：逐个查 netstate（D-80）、
// 逐个在 control_user_terminal_opr 里查绑定（D-81）、
// 再逐个查 terminaltype 的能力位。这里合成一条。
func (s *Service) loadStates(ctx context.Context, userID int64, isAdmin bool, ids []int64) (map[int64]*state, error) {
	if len(ids) == 0 {
		return map[int64]*state{}, nil
	}
	ph, args := placeholders(ids)

	// 绑定关系用相关子查询求布尔值，避免 JOIN userterminal 造成行膨胀
	// （一个终端可能绑给多个用户）。
	q := `
		SELECT t.id, COALESCE(t.terminalname,''), COALESCE(t.netstate,0), t.typeid,
		       COALESCE(tt.isdecode,0), COALESCE(tt.isencode,0),
		       COALESCE(tt.isLCD,0), COALESCE(tt.isspeech,0),
		       COALESCE(tt.shortkeycount,0), COALESCE(tt.switchcount,0),
		       EXISTS(SELECT 1 FROM userterminal ut
		               WHERE ut.terminalid = t.id AND ut.userid = ?) AS bound
		FROM terminal t
		LEFT JOIN terminaltype tt ON tt.id = t.typeid
		WHERE t.id IN (` + ph + `)`

	rs, err := s.db.QueryContext(ctx, q, append([]interface{}{userID}, args...)...)
	if err != nil {
		return nil, fmt.Errorf("查询终端状态: %w", err)
	}
	defer rs.Close()

	out := make(map[int64]*state, len(ids))
	for rs.Next() {
		st := &state{}
		var d, e, lcd, sp, keys, sw, b int
		if err := rs.Scan(&st.id, &st.name, &st.netState, &st.typeID,
			&d, &e, &lcd, &sp, &keys, &sw, &b); err != nil {
			return nil, err
		}
		st.isDecode, st.isEncode, st.bound = d == 1, e == 1, b == 1
		st.caps = CapsOf(TypeTraits{
			TypeID: st.typeID, IsDecode: st.isDecode, IsEncode: st.isEncode,
			IsLCD: lcd >= 1, IsSpeech: sp == 1, ShortKeyCount: keys, SwitchCount: sw,
		})
		if isAdmin {
			st.bound = true // 超管不受绑定限制
		}
		out[st.id] = st
	}
	return out, rs.Err()
}

// gate 决定某个终端能不能执行本次操作。
type gate struct {
	// requireOnline 网络断开的终端不能操作（BR-142）
	requireOnline bool
	// requireCodec 要求终端至少具备编码或解码能力之一。
	// 旧代码对讲 / 发言 / 线路检测都判了 isdecode==0 && isencode==0 才拒绝。
	requireCodec bool
	// requireCap 要求终端类型支持某项功能，取自 ok112 的 get_terminal_type()。
	// nil 表示不限类型。见 caps.go。
	requireCap func(Caps) bool
	// capHint 是 requireCap 不通过时给操作员看的说明。
	capHint string
}

func (s *Service) partition(states map[int64]*state, ids []int64, g gate) (ok []int64, skipped []Skipped) {
	skipped = []Skipped{}
	for _, id := range ids {
		st, exists := states[id]
		if !exists {
			skipped = append(skipped, Skipped{ID: id, Reason: "NOT_FOUND", Detail: "终端不存在"})
			continue
		}
		if !st.bound {
			skipped = append(skipped, Skipped{ID: id, Name: st.name,
				Reason: "NOT_BOUND", Detail: "该终端未绑定给当前用户"})
			continue
		}
		if g.requireOnline && st.netState == 0 {
			skipped = append(skipped, Skipped{ID: id, Name: st.name,
				Reason: "OFFLINE", Detail: "网络已断开"})
			continue
		}
		if g.requireCodec && !st.isDecode && !st.isEncode {
			skipped = append(skipped, Skipped{ID: id, Name: st.name,
				Reason: "UNSUPPORTED", Detail: "该终端类型不支持此操作"})
			continue
		}
		// ⚠ 类型能力这一关必须在服务端判。界面上的置灰只是方便，
		//   接口被直接调用时不能靠它。
		if g.requireCap != nil && !g.requireCap(st.caps) {
			detail := g.capHint
			if detail == "" {
				detail = "该终端类型不支持此操作"
			}
			skipped = append(skipped, Skipped{ID: id, Name: st.name,
				Reason: "UNSUPPORTED", Detail: detail})
			continue
		}
		ok = append(ok, id)
	}
	return ok, skipped
}

// prepare 是所有批量操作共用的前置：查状态 → 按闸门分流。
func (s *Service) prepare(ctx context.Context, u *auth.User, ids []int64, g gate) ([]int64, []Skipped, error) {
	states, err := s.loadStates(ctx, u.ID, u.IsAdmin, ids)
	if err != nil {
		return nil, nil, err
	}
	ok, skipped := s.partition(states, ids, g)
	return ok, skipped, nil
}

// exec 对通过闸门的终端执行一条 UPDATE。
func (s *Service) exec(ctx context.Context, ids []int64, setClause string, extra ...interface{}) error {
	if len(ids) == 0 {
		return nil
	}
	ph, args := placeholders(ids)
	q := fmt.Sprintf("UPDATE terminal SET %s WHERE id IN (%s)", setClause, ph)
	if _, err := s.db.ExecContext(ctx, q, append(append([]interface{}{}, extra...), args...)...); err != nil {
		return fmt.Errorf("更新终端状态: %w", err)
	}
	return nil
}

// ---------- F-26 启动 / 停止 ----------

// SetRunning 启动或停止一批终端。
//
// 停止时同时把 taskstate 置 0（BR-144）；启动时**不恢复** taskstate ——
// 那一列由后台服务上报，Web 端擅自写回去会和实际播放状态打架。
func (s *Service) SetRunning(ctx context.Context, u *auth.User, ids []int64, start bool) (*OpResult, error) {
	ok, skipped, err := s.prepare(ctx, u, ids, gate{requireOnline: true})
	if err != nil {
		return nil, err
	}
	setClause := "devicestate = 0, taskstate = 0"
	if start {
		setClause = "devicestate = 1"
	}
	if err := s.exec(ctx, ok, setClause); err != nil {
		return nil, err
	}
	return &OpResult{Succeeded: ok, Skipped: skipped}, nil
}

// ---------- F-31 状态开关 ----------

// Toggle 标识一个可开关的终端属性。
type Toggle string

const (
	ToggleSpeech   Toggle = "speech"   // 对讲     → isspeech
	ToggleSponsor  Toggle = "sponsor"  // 发言     → issponsor
	ToggleRecord   Toggle = "record"   // 录音     → isrecord
	ToggleBackcall Toggle = "backcall" // 回呼     → isselectcall
	ToggleInstancy Toggle = "instancy" // 紧急终端 → instancy
)

// toggleSpec 把开关映射到列名与闸门要求。
var toggleSpec = map[Toggle]struct {
	column string
	gate   gate
}{
	// ⚠ 对讲与发言的判据**不一样**，早前两者共用 requireCodec（或的关系），
	//   等于把对讲判松了、发言判得刚好。现在各用各的：
	//     对讲  isdecode = 1 且 isencode = 1   —— 要能收也能发才谈得上对讲
	//     发言  isdecode 与 isencode 全为 0 才不支持
	ToggleSpeech: {"isspeech", gate{
		requireOnline: true,
		requireCap:    func(c Caps) bool { return c.Speech },
		capHint:       "对讲要求终端同时具备解码与编码能力（isdecode 与 isencode 都为 1）",
	}},
	ToggleSponsor: {"issponsor", gate{
		requireOnline: true,
		requireCap:    func(c Caps) bool { return c.Sponsor },
		capHint:       "该终端类型既不能解码也不能编码，不支持发言",
	}},
	// 录音：旧代码把在线与能力校验整段注释掉了，这里保持不强制，
	// 否则现场那些常年离线但需要预置录音标志的终端会突然改不了。
	ToggleRecord:   {"isrecord", gate{}},
	ToggleBackcall: {"isselectcall", gate{}},
	// 急救不下发指令、也不要求在线，但**限终端类型**：isspeech = 1。
	// 早前这里是 gate{}，等于对任何终端都放行。
	//
	// ⚠ 别和「急救终端」混了：Instancy 判的是「哪些终端**能被设置**急救」，
	//   而 Caps.EmergencyHost（typeid 33/34）是「急救指向哪台终端」的候选集，
	//   那个要等急救对话框做出来才会用到。
	ToggleInstancy: {"instancy", gate{
		requireCap: func(c Caps) bool { return c.Instancy },
		capHint:    "急救只能设置在支持对讲的终端上（终端类型 isspeech = 1）",
	}},
}

func (s *Service) SetToggle(ctx context.Context, u *auth.User, ids []int64, t Toggle, on bool) (*OpResult, error) {
	spec, known := toggleSpec[t]
	if !known {
		return nil, fmt.Errorf("未知的终端开关: %s", t)
	}
	ok, skipped, err := s.prepare(ctx, u, ids, spec.gate)
	if err != nil {
		return nil, err
	}
	v := 0
	if on {
		v = 1
	}
	if err := s.exec(ctx, ok, spec.column+" = ?", v); err != nil {
		return nil, err
	}
	return &OpResult{Succeeded: ok, Skipped: skipped}, nil
}

// ---------- F-29 音量 ----------

// SetVolume 批量改音量。取值 0~100（BR-148，修复 D-97 的无校验）。
func (s *Service) SetVolume(ctx context.Context, u *auth.User, ids []int64, volume int) (*OpResult, error) {
	if volume < 0 || volume > 100 {
		return nil, fmt.Errorf("音量必须在 0 ~ 100 之间")
	}
	ok, skipped, err := s.prepare(ctx, u, ids, gate{requireOnline: true})
	if err != nil {
		return nil, err
	}
	if err := s.exec(ctx, ok, "volume = ?", volume); err != nil {
		return nil, err
	}
	return &OpResult{Succeeded: ok, Skipped: skipped}, nil
}

// ---------- F-30 终端密码 / 线路检测 / 同步时间 ----------

// Dispatch 是那些「只下发指令、不写数据库」的操作。
//
// 线路检测（state=27）、同步时间（state=30）、下发密码（state=7）都属于这一类：
// 结果由终端异步上报回 terminal.lopencircuit / ropencircuit 等列，
// Web 端不能自己先把结果写进去。
func (s *Service) Dispatch(ctx context.Context, u *auth.User, ids []int64, requireCodec bool) (*OpResult, error) {
	ok, skipped, err := s.prepare(ctx, u, ids,
		gate{requireOnline: true, requireCodec: requireCodec})
	if err != nil {
		return nil, err
	}
	return &OpResult{Succeeded: ok, Skipped: skipped}, nil
}

// DispatchPassword 是「设置终端密码」专用的前置。
//
// ⚠ 它比 Dispatch 多一道类型闸：ok112 的 set_terminal_password.php 用的是
// get_terminal_type(5) = `isLCD >= 1 AND id NOT IN(28,34,35,36,37)`。
// 道理也直白 —— 密码是要在终端**自己的屏上**输的，没有屏的终端设了也没用。
// 早前这里和普通 Dispatch 走同一条路，等于对任何终端都放行。
func (s *Service) DispatchPassword(ctx context.Context, u *auth.User, ids []int64) (*OpResult, error) {
	ok, skipped, err := s.prepare(ctx, u, ids, gate{
		requireOnline: true,
		requireCap:    func(c Caps) bool { return c.Password },
		capHint:       "该终端没有可输入密码的屏（终端类型 isLCD = 0）",
	})
	if err != nil {
		return nil, err
	}
	return &OpResult{Succeeded: ok, Skipped: skipped}, nil
}

// CheckBound 校验一批终端是否都可以被当前用户操作，用于删除等入口。
func (s *Service) CheckBound(ctx context.Context, u *auth.User, ids []int64) ([]int64, []Skipped, error) {
	return s.prepare(ctx, u, ids, gate{})
}
