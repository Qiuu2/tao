package task

import (
	"context"
	"fmt"

	"htweb/internal/auth"
	"htweb/internal/folder"
	"htweb/internal/store"
)

// 媒体与终端选择器（修 D-102）。
//
// 旧版为了渲染一个下拉框执行 SELECT media.id, media.name FROM media —— 把整张
// 媒体表读进 PHP 内存，每次打开任务列表页都来一遍。现网媒体不多所以看不出问题，
// 一旦上量就是几十 MB 的无用内存。这里改成「输入关键字才查、每次最多 50 条」。

const pickerLimit = 50

// MediaOption 是媒体下拉项。
type MediaOption struct {
	ID         int64  `json:"id"`
	Name       string `json:"name"`
	Size       int64  `json:"size"`
	TimeLength int    `json:"timelength"`
	FolderID   int64  `json:"folderId"`
	FolderName string `json:"folderName"`
}

// MediaOptions 按关键字搜索媒体。
//
// 可见范围复用 folder.VisibleCond —— 媒体的可见性由所在文件夹决定，
// 这条规则在 folder 包里只定义一次，媒体列表、上传、清空目录和这里共用同一份。
func (s *Service) MediaOptions(ctx context.Context, u *auth.User, keyword string, folderID int64) ([]MediaOption, error) {
	cond := &store.Cond{}
	// 与媒体模块口径一致：TTS 生成的条目不进任务媒体选择
	cond.Add("m.filename <> 'tts'")
	if keyword != "" {
		cond.Add(`m.name LIKE ? ESCAPE '\\'`, store.EscapeLike(keyword))
	}
	if folderID > 0 {
		cond.Add("m.folderid = ?", folderID)
	}
	// 用带别名的版本：media 自己也有 priority / userid 两个同名列，
	// 裸条件放在 JOIN 之后会绑错表（详见 folder.VisibleCondAlias 的注释）。
	fc, fargs := folder.VisibleCondAlias(u, "f")
	cond.Add(fc, fargs...)

	rs, err := s.db.QueryContext(ctx, `
		SELECT m.id, COALESCE(m.name,''), COALESCE(m.size,0), COALESCE(m.timelength,0),
		       COALESCE(m.folderid,0), COALESCE(f.name,'')
		FROM media m
		JOIN filefolder f ON f.id = m.folderid`+cond.Where()+`
		ORDER BY m.id DESC LIMIT ?`,
		append(cond.Args(), pickerLimit)...)
	if err != nil {
		return nil, fmt.Errorf("查询媒体选项: %w", err)
	}
	defer rs.Close()

	out := []MediaOption{}
	for rs.Next() {
		var o MediaOption
		if err := rs.Scan(&o.ID, &o.Name, &o.Size, &o.TimeLength, &o.FolderID, &o.FolderName); err != nil {
			return nil, err
		}
		out = append(out, o)
	}
	return out, rs.Err()
}

// TerminalOption 是终端下拉项。
type TerminalOption struct {
	ID        int64  `json:"id"`
	Name      string `json:"name"`
	TypeName  string `json:"typeName"`
	GroupID   int64  `json:"groupId"`
	GroupName string `json:"groupName"`
	NetState  int    `json:"netstate"`
}

// TerminalOptions 按关键字搜索可选终端。
//
// 可见范围与终端模块一致：管理员看全部，普通用户只看绑定给自己的。
// groupId 一并带出来，前端提交时直接原样回传 —— 旧版是让前端把
// 终端串和分区串两条平行的逗号串按下标对齐，长度不一致就静默写 0（D-107）。
func (s *Service) TerminalOptions(ctx context.Context, u *auth.User, keyword string, groupID int64) ([]TerminalOption, error) {
	cond := &store.Cond{}
	// 与终端模块一致：恒排除「服务器」类型
	cond.Add("t.typeid <> 0")
	if keyword != "" {
		cond.Add(`t.terminalname LIKE ? ESCAPE '\\'`, store.EscapeLike(keyword))
	}
	if groupID > 0 {
		cond.Add("t.id IN (SELECT terminalid FROM terminalofgroup WHERE groupid = ?)", groupID)
	}
	if !u.IsAdmin {
		cond.Add("t.id IN (SELECT terminalid FROM userterminal WHERE userid = ?)", u.ID)
	}

	rs, err := s.db.QueryContext(ctx, `
		SELECT t.id, COALESCE(t.terminalname,''), COALESCE(tt.name,''),
		       COALESCE((SELECT tog.groupid FROM terminalofgroup tog
		                  WHERE tog.terminalid = t.id ORDER BY tog.id LIMIT 1), 0),
		       COALESCE(t.netstate,0)
		FROM terminal t
		LEFT JOIN terminaltype tt ON tt.id = t.typeid`+cond.Where()+`
		ORDER BY t.netstate DESC, t.id ASC LIMIT ?`,
		append(cond.Args(), pickerLimit)...)
	if err != nil {
		return nil, fmt.Errorf("查询终端选项: %w", err)
	}
	defer rs.Close()

	out := []TerminalOption{}
	groupIDs := map[int64]bool{}
	for rs.Next() {
		var o TerminalOption
		if err := rs.Scan(&o.ID, &o.Name, &o.TypeName, &o.GroupID, &o.NetState); err != nil {
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
	if err := s.fillOptionGroupNames(ctx, out, groupIDs); err != nil {
		return nil, err
	}
	return out, nil
}

func (s *Service) fillOptionGroupNames(ctx context.Context, opts []TerminalOption, ids map[int64]bool) error {
	if len(ids) == 0 {
		for i := range opts {
			opts[i].GroupName = "(未分区)"
		}
		return nil
	}
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
	defer rs.Close()

	names := map[int64]string{}
	for rs.Next() {
		var id int64
		var name string
		if err := rs.Scan(&id, &name); err != nil {
			return err
		}
		names[id] = name
	}
	if err := rs.Err(); err != nil {
		return err
	}
	for i := range opts {
		if opts[i].GroupID == 0 {
			opts[i].GroupName = "(未分区)"
			continue
		}
		if n, ok := names[opts[i].GroupID]; ok {
			opts[i].GroupName = n
		} else {
			opts[i].GroupName = "(分区已删除)"
		}
	}
	return nil
}
