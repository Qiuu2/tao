package user

import (
	"context"
	"database/sql"
	"errors"
	"fmt"

	"htweb/internal/auth"
)

// Detail 是编辑弹窗需要的全部数据。
//
// 旧版编辑页要发 5 次请求分别拉用户、组、序列号、终端、分区，
// 且终端与分区是两条平行的逗号串靠下标对齐（缺陷 D-57）。
// 这里一次返回，且绑定关系是结构化的对象数组。
type Detail struct {
	ID               int64          `json:"id"`
	Username         string         `json:"username"`
	UsergroupID      int64          `json:"usergroupId"`
	Info             string         `json:"info"`
	Fullname         string         `json:"fullname"`
	Enable           int            `json:"enable"`
	CtrlWind         int            `json:"ctrlwind"`
	SubWind          int            `json:"subwind"`
	CameraWind       int            `json:"camerawind"`
	EnableCtrlWind   bool           `json:"enableCtrlwind"`
	EnableSubWind    bool           `json:"enableSubwind"`
	EnableCameraWind bool           `json:"enableCamerawind"`
	Serials          []string       `json:"serials"`
	Terminals        []TerminalBind `json:"terminals"`
	// UsernameLocked / GroupLocked 对应 admin 的字段级保护（BR-118）
	UsernameLocked bool `json:"usernameLocked"`
	GroupLocked    bool `json:"groupLocked"`
}

func (s *Service) GetUser(ctx context.Context, cur *auth.User, id int64) (*Detail, error) {
	// 非超管只能看自己（BR-107）
	if cur.ID != SystemUserID && cur.ID != id {
		return nil, ErrNoPermission
	}

	d := &Detail{ID: id, Serials: []string{"", "", ""}, Terminals: []TerminalBind{}}
	var info, fullname sql.NullString
	var ctrl, sub, cam sql.NullInt64

	err := s.db.QueryRowContext(ctx, `
		SELECT username, usergroupid, info, fullname, enable, ctrlwind, subwind, camerawind
		FROM book_admin WHERE id = ? LIMIT 1`, id).
		Scan(&d.Username, &d.UsergroupID, &info, &fullname, &d.Enable, &ctrl, &sub, &cam)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询用户: %w", err)
	}

	d.Info, d.Fullname = info.String, fullname.String
	d.CtrlWind, d.SubWind, d.CameraWind = int(ctrl.Int64), int(sub.Int64), int(cam.Int64)
	// 分控 ID 落在有效区间内才算「已启用」，0 与 NULL 都是未启用
	d.EnableCtrlWind = d.CtrlWind >= windCtrl.base
	d.EnableSubWind = d.SubWind >= windSub.base
	d.EnableCameraWind = d.CameraWind >= windCamera.base
	d.UsernameLocked = id == SystemUserID
	d.GroupLocked = id == SystemUserID

	// 序列号：id 固定 1/2/3，按位置回填，缺的留空串
	if err := s.collect(ctx, `SELECT id, COALESCE(sn,'') FROM usersn WHERE userid = ?`,
		[]interface{}{id}, func(rows *sql.Rows) error {
			var slot int
			var sn string
			if err := rows.Scan(&slot, &sn); err != nil {
				return err
			}
			if slot >= 1 && slot <= 3 {
				d.Serials[slot-1] = sn
			}
			return nil
		}); err != nil {
		return nil, err
	}

	if err := s.collect(ctx,
		`SELECT terminalid, COALESCE(groupid,0) FROM userterminal WHERE userid = ?`,
		[]interface{}{id}, func(rows *sql.Rows) error {
			var b TerminalBind
			if err := rows.Scan(&b.TerminalID, &b.GroupID); err != nil {
				return err
			}
			d.Terminals = append(d.Terminals, b)
			return nil
		}); err != nil {
		return nil, err
	}
	return d, nil
}

// ---------- F-24 终端绑定选择器 ----------

type TerminalOption struct {
	ID        int64  `json:"id"`
	Name      string `json:"name"`
	GroupID   int64  `json:"groupId"`
	GroupName string `json:"groupName"`
	OwnerID   int64  `json:"ownerId"`
	OwnerName string `json:"ownerName"`
	// Occupied 表示该终端已被**其他**用户绑定。
	// 旧版对此毫无提示，两个用户绑同一台终端时行为不确定。
	Occupied bool `json:"occupied"`
}

// TerminalOptions 列出可供绑定的终端，并标注已被他人占用的项。
func (s *Service) TerminalOptions(ctx context.Context, forUserID int64) ([]TerminalOption, error) {
	// 分区取 terminal.groupid → serverplaystream.streamid。
	// 不走 terminalofgroup：那是多对多表，join 上去会把同一台终端拆成多行。
	// LEFT JOIN 保证未分区（groupid=0）的终端也能列出来。
	rows, err := s.db.QueryContext(ctx, `
		SELECT t.id, COALESCE(t.terminalname,''),
		       COALESCE(t.groupid,0), COALESCE(sps.name,'')
		FROM terminal t
		LEFT JOIN serverplaystream sps ON sps.streamid = t.groupid
		ORDER BY t.id ASC`)
	if err != nil {
		return nil, fmt.Errorf("查询终端: %w", err)
	}
	defer rows.Close()

	out := []TerminalOption{}
	for rows.Next() {
		var o TerminalOption
		if err := rows.Scan(&o.ID, &o.Name, &o.GroupID, &o.GroupName); err != nil {
			return nil, err
		}
		if o.GroupName == "" {
			o.GroupName = "(未分区)"
		}
		out = append(out, o)
	}
	if err := rows.Err(); err != nil {
		return nil, err
	}

	// 占用情况单独查一次再回填，避免 join 造成的行膨胀
	owners := make(map[int64]struct {
		id   int64
		name string
	})
	if err := s.collect(ctx, `
		SELECT ut.terminalid, ut.userid, COALESCE(ba.username,'')
		FROM userterminal ut
		LEFT JOIN book_admin ba ON ba.id = ut.userid
		WHERE ut.userid <> ?`, []interface{}{forUserID},
		func(r *sql.Rows) error {
			var tid, uid int64
			var name string
			if err := r.Scan(&tid, &uid, &name); err != nil {
				return err
			}
			if _, seen := owners[tid]; !seen {
				owners[tid] = struct {
					id   int64
					name string
				}{uid, name}
			}
			return nil
		}); err != nil {
		return nil, err
	}
	for i := range out {
		if o, ok := owners[out[i].ID]; ok {
			out[i].OwnerID, out[i].OwnerName, out[i].Occupied = o.id, o.name, true
		}
	}
	return out, nil
}

// GroupOption 是「所属用户组」下拉需要的最小字段集。
//
// 刻意不复用 Group：那个结构带 rights / canModify，
// 而这里的查询根本没取这些列，复用会让接口吐出一堆全 0 的假值。
type GroupOption struct {
	ID         int64  `json:"id"`
	Name       string `json:"name"`
	Level      int    `json:"level"`
	GroupLevel int    `json:"groupLevel"`
	System     bool   `json:"system"`
}

// GroupOptions 供用户表单的「所属用户组」下拉使用。
func (s *Service) GroupOptions(ctx context.Context) ([]GroupOption, error) {
	rows, err := s.db.QueryContext(ctx,
		`SELECT id, name, level FROM usergroup ORDER BY level DESC, id ASC`)
	if err != nil {
		return nil, fmt.Errorf("查询用户组: %w", err)
	}
	defer rows.Close()

	out := []GroupOption{}
	for rows.Next() {
		var g GroupOption
		if err := rows.Scan(&g.ID, &g.Name, &g.Level); err != nil {
			return nil, err
		}
		g.GroupLevel, _ = SplitLevel(g.Level)
		g.System = g.ID == SystemGroupID
		out = append(out, g)
	}
	return out, rows.Err()
}

// WindCapacityInfo 告诉前端三类分控各自还剩多少可分配名额。
type WindCapacityInfo struct {
	Capacity      int  `json:"capacity"`
	CtrlUsed      int  `json:"ctrlUsed"`
	SubUsed       int  `json:"subUsed"`
	CameraUsed    int  `json:"cameraUsed"`
	RegisterFlag  int  `json:"registerFlag"`
	CanCreateUser bool `json:"canCreateUser"`
}

func (s *Service) WindCapacity(ctx context.Context) (*WindCapacityInfo, error) {
	capacity := s.windCapacity(ctx)
	info := &WindCapacityInfo{Capacity: capacity}
	for _, p := range []struct {
		k   windKind
		dst *int
	}{{windCtrl, &info.CtrlUsed}, {windSub, &info.SubUsed}, {windCamera, &info.CameraUsed}} {
		var n int
		if err := s.db.QueryRowContext(ctx, fmt.Sprintf(
			`SELECT COUNT(*) FROM book_admin WHERE %s BETWEEN ? AND ?`, p.k.column),
			p.k.base, p.k.base+capacity-1).Scan(&n); err != nil {
			return nil, fmt.Errorf("统计分控占用: %w", err)
		}
		*p.dst = n
	}
	info.RegisterFlag = s.registerFlag(ctx)
	info.CanCreateUser = info.RegisterFlag == 1 || info.RegisterFlag == 2
	return info, nil
}
