package alarm

import (
	"context"
	"fmt"

	"htweb/internal/auth"
	"htweb/internal/store"
)

// 报警映射编辑弹窗用的三个选择器。

// HostOption 是报警主机下拉项。
type HostOption struct {
	ID   int64  `json:"id"`
	Name string `json:"name"`
	IP   string `json:"ip"`
	// Channels 是该主机可配的报警输入路数，界面据此生成 1..Channels 的通道下拉。
	// 取值规则见 effectiveChannels。
	Channels int `json:"channels"`
	// DeviceChannels 是 terminal.channel 的原值，TypeSwitchCount 是终端**类型**
	// 声明的开关路数。两个都带出来，便于排查「为什么是这么多路」。
	DeviceChannels  int `json:"deviceChannels"`
	TypeSwitchCount int `json:"typeSwitchCount"`
	NetState        int `json:"netstate"`
	// GroupID / GroupName 是终端分区，供界面把主机选择器排成树。
	GroupID   int64  `json:"groupId"`
	GroupName string `json:"groupName"`
}

// AlarmHosts 列出全部报警主机。
//
// 旧 setalarmkeymap.php 的写法是 `WHERE terminal.typeid = '7'`，没有可见范围过滤。
// 这里对普通用户按 userterminal 绑定收敛，与终端模块口径一致。
func (s *Service) AlarmHosts(ctx context.Context, u *auth.User) ([]HostOption, error) {
	cond := &store.Cond{}
	cond.Add("t.typeid = ?", TypeAlarmHost)
	if !u.IsAdmin {
		cond.Add("t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)", u.ID)
	}

	// 分区名在 serverplaystream，终端与分区的关系在 terminalofgroup；
	// 一台终端理论上可能有多行，取 id 最小的那条，与 task/picker.go 的口径一致。
	rs, err := s.db.QueryContext(ctx, `
		SELECT t.id, COALESCE(t.terminalname,''), COALESCE(t.ip,''),
		       COALESCE(t.channel,0), COALESCE(tt.switchcount,0), COALESCE(t.netstate,0),
		       COALESCE((SELECT tog.groupid FROM terminalofgroup tog
		                 WHERE tog.terminalid = t.id ORDER BY tog.id LIMIT 1), 0),
		       COALESCE((SELECT sps.name FROM terminalofgroup tog
		                 JOIN serverplaystream sps ON sps.streamid = tog.groupid
		                 WHERE tog.terminalid = t.id ORDER BY tog.id LIMIT 1), '')
		FROM terminal t
		LEFT JOIN terminaltype tt ON tt.id = t.typeid`+cond.Where()+`
		ORDER BY t.id`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询报警主机: %w", err)
	}
	defer rs.Close()

	out := []HostOption{}
	for rs.Next() {
		var h HostOption
		if err := rs.Scan(&h.ID, &h.Name, &h.IP, &h.DeviceChannels,
			&h.TypeSwitchCount, &h.NetState, &h.GroupID, &h.GroupName); err != nil {
			return nil, err
		}
		h.Channels = effectiveChannels(h.DeviceChannels, h.TypeSwitchCount)
		out = append(out, h)
	}
	return out, rs.Err()
}

// effectiveChannels 算一台报警主机到底有几路可配的报警输入。
//
// 两个来源，单看哪一个都会算错：
//
//	terminaltype.switchcount  这个**型号**支持几路（类型 7 声明 16）
//	terminal.channel          这台**设备**自己报的路数
//
// terminal.channel 的麻烦在于它是全表通用的一列，默认值 2 —— 演示库里
// 十三台终端清一色是 2，那是「立体声两个声道」的意思，跟报警输入无关。
// 一台从没报过真值的报警主机就停在 2 上，只照它算，16 路的机器只能配 2 路。
//
// 反过来只认 switchcount 也不行：注释里记着现网四台 7 型主机的 channel 是
// 2/32/32/32，有三台**比类型声明的 16 还多**，按 16 算会少掉一半输入。
//
// 所以取「型号支持的路数」打底，设备报得更多就以设备为准。
//
// ⚠ 与 ok112 不同：setalarmkeymap.php 只读 terminal.channel
//
//	（`SELECT channel FROM terminal WHERE id=? AND typeid='7'`），
//	于是那台 channel=2 的主机在旧界面上就只能配 2 路。这里不照搬这个缺陷。
func effectiveChannels(deviceChannels, typeSwitchCount int) int {
	n := typeSwitchCount
	if deviceChannels > n {
		n = deviceChannels
	}
	if n < 0 {
		return 0
	}
	return n
}

// AreaOption 是报警分区下拉项。
type AreaOption struct {
	ID   int64  `json:"id"`
	Name string `json:"name"`
}

func (s *Service) AreaOptions(ctx context.Context, u *auth.User) ([]AreaOption, error) {
	q := `SELECT a.id, COALESCE(a.name,'') FROM alarmarea a`
	var args []interface{}
	if c, a := areaVisibleCond(u); c != "" {
		q += " WHERE " + c
		args = a
	}
	q += " ORDER BY a.id"

	rs, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询报警分区选项: %w", err)
	}
	defer rs.Close()

	out := []AreaOption{}
	for rs.Next() {
		var o AreaOption
		if err := rs.Scan(&o.ID, &o.Name); err != nil {
			return nil, err
		}
		out = append(out, o)
	}
	return out, rs.Err()
}

// MediaOption 是报警媒体下拉项。
type MediaOption struct {
	ID         int64  `json:"id"`
	Name       string `json:"name"`
	TimeLength int    `json:"timelength"`
	FolderID   int64  `json:"folderId"`
	FolderName string `json:"folderName"`
}

// AlarmMediaResult 带一条说明，用来解释「为什么下拉是空的」。
type AlarmMediaResult struct {
	List []MediaOption `json:"list"`
	Note string        `json:"note"`
}

// AlarmMedia 列出报警媒体库子树下的媒体。
//
// 这条限定来自旧 setalarmkeymap.php —— 手册没写，但它是旧系统的实际行为，保留。
// 旧查询把「三层子目录」硬编码成三个嵌套 OR 子查询（与 D-07 的三层写死同型），
// 这里改成真正的递归收集，深度不受限。
//
// 旧版在媒体库为空时直接 alert 一句然后 history.back()，整个页面进不去。
// 新版返回空列表 + 一句说明，让界面能正常打开并告诉用户该去哪儿传文件。
func (s *Service) AlarmMedia(ctx context.Context, keyword string) (*AlarmMediaResult, error) {
	folders, err := s.alarmFolderSubtree(ctx)
	if err != nil {
		return nil, err
	}
	out := &AlarmMediaResult{List: []MediaOption{}}
	if len(folders) == 0 {
		out.Note = "报警媒体库不存在，请联系管理员检查媒体目录结构"
		return out, nil
	}

	ph, args := placeholders(folders)
	cond := &store.Cond{}
	cond.Add("m.folderid IN ("+ph+")", args...)
	cond.Add("m.filename <> 'tts'")
	if keyword != "" {
		cond.Add(`m.name LIKE ? ESCAPE '\\'`, store.EscapeLike(keyword))
	}

	rs, err := s.db.QueryContext(ctx, `
		SELECT m.id, COALESCE(m.name,''), COALESCE(m.timelength,0),
		       COALESCE(m.folderid,0), COALESCE(f.name,'')
		FROM media m
		LEFT JOIN filefolder f ON f.id = m.folderid`+cond.Where()+`
		ORDER BY m.id DESC LIMIT 200`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询报警媒体: %w", err)
	}
	defer rs.Close()

	for rs.Next() {
		var o MediaOption
		if err := rs.Scan(&o.ID, &o.Name, &o.TimeLength, &o.FolderID, &o.FolderName); err != nil {
			return nil, err
		}
		out.List = append(out.List, o)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}
	if len(out.List) == 0 && keyword == "" {
		out.Note = "「报警媒体库」里还没有媒体。请先到媒体管理里把报警音上传到该目录下，再回来配置映射。"
	}
	return out, nil
}

// mediaInAlarmLibrary 判定某个媒体是否位于报警媒体库子树内。
func (s *Service) mediaInAlarmLibrary(ctx context.Context, mediaID int64) (bool, error) {
	folders, err := s.alarmFolderSubtree(ctx)
	if err != nil {
		return false, err
	}
	if len(folders) == 0 {
		return false, nil
	}
	ph, args := placeholders(folders)
	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM media WHERE id = ? AND folderid IN (`+ph+`)`,
		append([]interface{}{mediaID}, args...)...).Scan(&n); err != nil {
		return false, fmt.Errorf("校验报警媒体: %w", err)
	}
	return n > 0, nil
}

// alarmFolderSubtree 收集报警媒体库及其全部子孙目录的 ID。
// 广度优先 + 访问集合，旧数据里 parentid 成环也不会死循环。
func (s *Service) alarmFolderSubtree(ctx context.Context) ([]int64, error) {
	var exists int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM filefolder WHERE id = ?`, AlarmMediaFolder).Scan(&exists); err != nil {
		return nil, fmt.Errorf("查询报警媒体库: %w", err)
	}
	if exists == 0 {
		return nil, nil
	}

	seen := map[int64]bool{AlarmMediaFolder: true}
	out := []int64{AlarmMediaFolder}
	frontier := []int64{AlarmMediaFolder}

	for len(frontier) > 0 {
		ph, args := placeholders(frontier)
		rs, err := s.db.QueryContext(ctx,
			`SELECT id FROM filefolder WHERE parentid IN (`+ph+`)`, args...)
		if err != nil {
			return nil, fmt.Errorf("收集报警媒体库子目录: %w", err)
		}
		next := []int64{}
		for rs.Next() {
			var id int64
			if err := rs.Scan(&id); err != nil {
				rs.Close()
				return nil, err
			}
			if !seen[id] {
				seen[id] = true
				out = append(out, id)
				next = append(next, id)
			}
		}
		rs.Close()
		if err := rs.Err(); err != nil {
			return nil, err
		}
		frontier = next
	}
	return out, nil
}

// TerminalOption 是分区成员选择用的终端下拉项。
type TerminalOption struct {
	ID        int64  `json:"id"`
	Name      string `json:"name"`
	TypeName  string `json:"typeName"`
	GroupID   int64  `json:"groupId"`
	GroupName string `json:"groupName"`
	NetState  int    `json:"netstate"`
	// CurrentAreaID 是该终端当前所属的报警分区（terminal.firealarmgroup），
	// -1 表示没有分区。界面据此提示「该终端已属于别的报警分区」。
	CurrentAreaID   int64  `json:"currentAreaId"`
	CurrentAreaName string `json:"currentAreaName"`
}

// AreaTerminalOptions 列出可加入报警分区的终端。
//
// 只列有解码能力的终端：firealarmgroup 的列注释写着「只对有解码功能的终端有效」。
func (s *Service) AreaTerminalOptions(ctx context.Context, u *auth.User, keyword string) ([]TerminalOption, error) {
	cond := &store.Cond{}
	cond.Add("t.typeid <> 0")
	cond.Add("COALESCE(tt.isdecode,0) = 1")
	if keyword != "" {
		cond.Add(`t.terminalname LIKE ? ESCAPE '\\'`, store.EscapeLike(keyword))
	}
	if !u.IsAdmin {
		cond.Add("t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)", u.ID)
	}

	rs, err := s.db.QueryContext(ctx, `
		SELECT t.id, COALESCE(t.terminalname,''), COALESCE(tt.name,''),
		       COALESCE((SELECT tog.groupid FROM terminalofgroup tog
		                  WHERE tog.terminalid = t.id ORDER BY tog.id LIMIT 1), 0),
		       COALESCE(t.netstate,0), COALESCE(t.firealarmgroup,-1),
		       COALESCE((SELECT a.name FROM alarmarea a WHERE a.id = t.firealarmgroup), '')
		FROM terminal t
		LEFT JOIN terminaltype tt ON tt.id = t.typeid`+cond.Where()+`
		ORDER BY t.netstate DESC, t.id ASC LIMIT 200`, cond.Args()...)
	if err != nil {
		return nil, fmt.Errorf("查询终端选项: %w", err)
	}
	defer rs.Close()

	out := []TerminalOption{}
	groupIDs := map[int64]bool{}
	for rs.Next() {
		var o TerminalOption
		if err := rs.Scan(&o.ID, &o.Name, &o.TypeName, &o.GroupID,
			&o.NetState, &o.CurrentAreaID, &o.CurrentAreaName); err != nil {
			return nil, err
		}
		if o.GroupID > 0 {
			groupIDs[o.GroupID] = true
		}
		out = append(out, o)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}
	if err := s.fillGroupNames(ctx, out, groupIDs); err != nil {
		return nil, err
	}
	return out, nil
}

func (s *Service) fillGroupNames(ctx context.Context, opts []TerminalOption, ids map[int64]bool) error {
	names := map[int64]string{}
	if len(ids) > 0 {
		list := make([]int64, 0, len(ids))
		for id := range ids {
			list = append(list, id)
		}
		ph, args := placeholders(list)
		rs, err := s.db.QueryContext(ctx,
			`SELECT streamid, COALESCE(name,'') FROM serverplaystream WHERE streamid IN (`+ph+`)`, args...)
		if err != nil {
			return fmt.Errorf("查询分区名: %w", err)
		}
		for rs.Next() {
			var id int64
			var name string
			if err := rs.Scan(&id, &name); err != nil {
				rs.Close()
				return err
			}
			names[id] = name
		}
		rs.Close()
		if err := rs.Err(); err != nil {
			return err
		}
	}
	for i := range opts {
		switch {
		case opts[i].GroupID == 0:
			opts[i].GroupName = "(未分区)"
		case names[opts[i].GroupID] != "":
			opts[i].GroupName = names[opts[i].GroupID]
		default:
			opts[i].GroupName = "(分区已删除)"
		}
	}
	return nil
}
