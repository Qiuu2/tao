package alarm

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"
	"unicode/utf8"

	"htweb/internal/auth"
	"htweb/internal/store"
)

// 报警映射（F-38 列表 / F-39 设置与修改）。

// Mapping 是一条报警映射。
type Mapping struct {
	ID   int64  `json:"id"`
	Info string `json:"info"`

	AlarmTerminalID   int64  `json:"alarmTerminalId"`
	AlarmTerminalName string `json:"alarmTerminalName"`
	TerminalDeleted   bool   `json:"terminalDeleted"`
	// TerminalChannels 是该主机的通道总数（terminal.channel）。
	TerminalChannels int `json:"terminalChannels"`

	AlarmChannel int `json:"alarmChannel"`

	AlarmAreaID   int64  `json:"alarmAreaId"`
	AlarmAreaName string `json:"alarmAreaName"`
	AreaDeleted   bool   `json:"areaDeleted"`
	// AreaTerminalCount 是这个报警分区下挂了多少台终端。
	// :80 的报警映射列表里有「分区终端」这一列，就是它（见 docs/image/oktw/页面规格.txt）。
	AreaTerminalCount int `json:"areaTerminalCount"`

	MediaID      int64  `json:"mediaId"`
	MediaName    string `json:"mediaName"`
	MediaDeleted bool   `json:"mediaDeleted"`

	// Invalid 表示这条映射引用了已被删除的对象。
	// 必须显示出来而不是过滤掉 —— 它仍然在库里、后台仍然会用它（BR-190）。
	Invalid bool `json:"invalid"`
	// InvalidReason 直接给出人能看懂的原因，省得前端再拼一遍
	InvalidReason string `json:"invalidReason"`
	// ChannelOutOfRange 通道号超出了该主机的通道数
	ChannelOutOfRange bool `json:"channelOutOfRange"`
}

type MappingListResult struct {
	Items     []Mapping
	Total     int64
	ScopeNote string
}

// 排序白名单（修 D-128 的注入点）。默认按通道号（BR-188）。
var mappingOrderWhitelist = map[string]string{
	"alarmchannel": "m.alarmchannel",
	"terminalname": "t.terminalname",
	"alarmname":    "a.name",
	"medianame":    "md.name",
	"id":           "m.id",
}

const mappingDefaultOrder = "m.alarmchannel ASC, m.id ASC"

type MappingQuery struct {
	SearchKey string // terminalname | alarmname
	Keyword   string
	OrderBy   string
	Order     string
	Pager     store.Pager
}

// mappingVisibleCond 是报警映射可见范围的唯一权威定义（BR-189）。
//
// 归属由映射所指向的**报警分区**的创建者决定（`alarmarea.userid`）。
// 旧代码只在「没有搜索」的分支里写了这个条件，四个搜索分支一个都没写 ——
// 普通用户平时只看得到自己的映射，一点搜索就能列出全系统的（与 D-78 同型）。
//
// 用 EXISTS 而不是把条件挂在 LEFT JOIN 上：分区已被删除的映射（a.id IS NULL）
// 对普通用户不可见，但对管理员必须可见 —— 那正是需要被清理的异常配置。
func mappingVisibleCond(u *auth.User) (string, []interface{}) {
	if u.IsAdmin {
		return "", nil
	}
	return "EXISTS (SELECT 1 FROM alarmarea a2 WHERE a2.id = m.firealarmgroupid AND a2.userid = ?)",
		[]interface{}{u.ID}
}

func (s *Service) ListMappings(ctx context.Context, u *auth.User, q MappingQuery) (*MappingListResult, error) {
	cond := &store.Cond{}
	if c, args := mappingVisibleCond(u); c != "" {
		cond.Add(c, args...)
	}
	// 旧版 alarmmanager.php 的搜索是一个「选择类型…」下拉（报警主机 / 映射分区）
	// 加一个关键字框。这里保留 searchKey 这两个取值，另外补一条默认分支：
	// ⚠ 界面上只有一个搜索框、不带 searchKey 时，旧的写法两个 case 都不命中，
	//   搜索框按下去毫无反应。默认按「报警主机名 或 映射分区名」一起模糊匹配。
	if q.Keyword != "" {
		kw := store.EscapeLike(q.Keyword)
		switch q.SearchKey {
		case "terminalname":
			cond.Add(`t.terminalname LIKE ? ESCAPE '\\'`, kw)
		case "alarmname":
			cond.Add(`a.name LIKE ? ESCAPE '\\'`, kw)
		default:
			// 映射自己的名字在 alarmgroupmap.info 这一列上（表里没有 name 列）
			cond.Add(`(t.terminalname LIKE ? ESCAPE '\\' OR a.name LIKE ? ESCAPE '\\'
			           OR m.info LIKE ? ESCAPE '\\')`, kw, kw, kw)
		}
	}

	// 计数与取数用同一套 FROM / WHERE，避免旧版那种两条 SQL 各写一遍再对不上的问题
	from := `
		FROM alarmgroupmap m
		LEFT JOIN terminal  t  ON t.id  = m.alarmterminalid
		LEFT JOIN alarmarea a  ON a.id  = m.firealarmgroupid
		LEFT JOIN media     md ON md.id = m.mediaid`

	where := cond.Where()
	args := cond.Args()

	var total int64
	if err := s.db.QueryRowContext(ctx, "SELECT COUNT(*)"+from+where, args...).Scan(&total); err != nil {
		return nil, fmt.Errorf("统计报警映射数: %w", err)
	}

	order := store.OrderBy(mappingOrderWhitelist, q.OrderBy, q.Order, mappingDefaultOrder)

	listSQL := `
		SELECT m.id, COALESCE(m.info,''),
		       m.alarmterminalid, t.id IS NOT NULL, COALESCE(t.terminalname,''), COALESCE(t.channel,0),
		       m.alarmchannel,
		       m.firealarmgroupid, a.id IS NOT NULL, COALESCE(a.name,''),
		       (SELECT COUNT(*) FROM terminalofalarmgroup g
		         WHERE g.alarmgroupid = CAST(m.firealarmgroupid AS CHAR)),
		       m.mediaid, md.id IS NOT NULL, COALESCE(md.name,'')` + from + where +
		" ORDER BY " + order + " LIMIT ? OFFSET ?"

	listArgs := append(append([]interface{}{}, args...), q.Pager.PageSize, q.Pager.Offset())
	rs, err := s.db.QueryContext(ctx, listSQL, listArgs...)
	if err != nil {
		return nil, fmt.Errorf("查询报警映射: %w", err)
	}
	defer rs.Close()

	items := make([]Mapping, 0, q.Pager.PageSize)
	for rs.Next() {
		var it Mapping
		var termOK, areaOK, mediaOK bool
		if err := rs.Scan(&it.ID, &it.Info,
			&it.AlarmTerminalID, &termOK, &it.AlarmTerminalName, &it.TerminalChannels,
			&it.AlarmChannel,
			&it.AlarmAreaID, &areaOK, &it.AlarmAreaName, &it.AreaTerminalCount,
			&it.MediaID, &mediaOK, &it.MediaName); err != nil {
			return nil, fmt.Errorf("扫描报警映射行: %w", err)
		}
		it.TerminalDeleted, it.AreaDeleted, it.MediaDeleted = !termOK, !areaOK, !mediaOK
		decorateMapping(&it)
		items = append(items, it)
	}
	if err := rs.Err(); err != nil {
		return nil, err
	}

	res := &MappingListResult{Items: items, Total: total}
	if !u.IsAdmin {
		res.ScopeNote = "仅显示我创建的报警分区下的映射"
	}
	return res, nil
}

func decorateMapping(it *Mapping) {
	var bad []string
	if it.TerminalDeleted {
		it.AlarmTerminalName = "(报警主机已删除)"
		bad = append(bad, "报警主机已删除")
	}
	if it.AreaDeleted {
		it.AlarmAreaName = "(报警分区已删除)"
		bad = append(bad, "报警分区已删除")
	}
	if it.MediaDeleted {
		it.MediaName = "(媒体已删除)"
		bad = append(bad, "媒体已删除")
	}
	// 通道数被改小之后，原来配好的高通道号就落在范围外了
	if !it.TerminalDeleted && it.TerminalChannels > 0 &&
		(it.AlarmChannel < 1 || it.AlarmChannel > it.TerminalChannels) {
		it.ChannelOutOfRange = true
		bad = append(bad, fmt.Sprintf("通道 %d 超出该主机的 %d 路范围",
			it.AlarmChannel, it.TerminalChannels))
	}
	it.Invalid = len(bad) > 0
	if it.Invalid {
		it.InvalidReason = strings.Join(bad, "；") + " —— 报警触发时不会正常播放"
	}
}

// ---------- F-39 设置 / 修改 ----------

// MappingInput 是新增 / 修改的入参。
type MappingInput struct {
	Info            string
	AlarmTerminalID int64
	AlarmChannel    int
	AlarmAreaID     int64
	MediaID         int64
}

// validate 做旧版完全没做的那几项校验（D-133）。
//
// excludeID > 0 时表示这是修改，通道唯一性校验要排除自身。
func (s *Service) validate(ctx context.Context, u *auth.User, in *MappingInput) error {
	in.Info = strings.TrimSpace(in.Info)
	// info 是 varchar(45) 且 NOT NULL，超长会被 MySQL 静默截断
	if len(in.Info) > 45 {
		return fmt.Errorf("备注过长：按 UTF-8 计 %d 字节，上限 45 字节（约 15 个汉字）", len(in.Info))
	}
	if utf8.RuneCountInString(in.Info) == 0 && in.Info != "" {
		in.Info = ""
	}

	// 报警主机：必须存在，且必须是报警主机类型
	//
	// ⚠ 路数必须和下拉用的是同一套算法（effectiveChannels），否则会出现
	//   「下拉里能选到第 16 路，提交却说超范围」这种自相矛盾的情况。
	var typeID, deviceChannels, typeSwitchCount int
	err := s.db.QueryRowContext(ctx, `
		SELECT t.typeid, COALESCE(t.channel,0), COALESCE(tt.switchcount,0)
		FROM terminal t LEFT JOIN terminaltype tt ON tt.id = t.typeid
		WHERE t.id = ? LIMIT 1`,
		in.AlarmTerminalID).Scan(&typeID, &deviceChannels, &typeSwitchCount)
	if errors.Is(err, sql.ErrNoRows) {
		return fmt.Errorf("报警主机不存在")
	}
	if err != nil {
		return fmt.Errorf("校验报警主机: %w", err)
	}
	if typeID != TypeAlarmHost {
		return fmt.Errorf("所选终端不是报警主机，不能配置报警映射")
	}
	channels := effectiveChannels(deviceChannels, typeSwitchCount)
	// 通道从 1 开始编号 —— 旧界面的 JS 是 for(i=0;i<channelnum;i++){value = i+1}
	if channels <= 0 {
		return fmt.Errorf("该报警主机的通道数为 0：终端未上报路数，其型号也没有声明开关路数")
	}
	if in.AlarmChannel < 1 || in.AlarmChannel > channels {
		return fmt.Errorf("通道号必须在 1 ~ %d 之间", channels)
	}

	// 报警分区：必须存在；普通用户只能用自己创建的
	var areaOwner sql.NullInt64
	err = s.db.QueryRowContext(ctx,
		`SELECT userid FROM alarmarea WHERE id = ? LIMIT 1`, in.AlarmAreaID).Scan(&areaOwner)
	if errors.Is(err, sql.ErrNoRows) {
		return fmt.Errorf("报警分区不存在")
	}
	if err != nil {
		return fmt.Errorf("校验报警分区: %w", err)
	}
	if !u.IsAdmin && areaOwner.Int64 != u.ID {
		return ErrNoPermission
	}

	// 媒体：必须存在，且必须在报警媒体库子树内（旧界面下拉就是这么限定的）
	ok, err := s.mediaInAlarmLibrary(ctx, in.MediaID)
	if err != nil {
		return err
	}
	if !ok {
		return fmt.Errorf("媒体不存在，或不在「报警媒体库」里 —— 报警音必须放在报警媒体库下")
	}
	return nil
}

// checkChannelFree 校验「同一主机同一通道只能有一条映射」（BR-191）。
//
// 旧版只在新增时查了这一条，修改时**一句校验都没有**，直接 UPDATE（D-132）。
// 于是把 A 通道改成已被 B 占用的通道号就会造出两条同主机同通道的映射，
// 报警触发时播哪一条完全看数据库返回顺序。
func (s *Service) checkChannelFree(ctx context.Context, terminalID int64, channel int, excludeID int64) error {
	q := `SELECT id FROM alarmgroupmap WHERE alarmterminalid = ? AND alarmchannel = ?`
	args := []interface{}{terminalID, channel}
	if excludeID > 0 {
		q += ` AND id <> ?`
		args = append(args, excludeID)
	}
	q += ` LIMIT 1`

	var id int64
	err := s.db.QueryRowContext(ctx, q, args...).Scan(&id)
	if err == nil {
		return ErrChannelUsed
	}
	if errors.Is(err, sql.ErrNoRows) {
		return nil
	}
	return fmt.Errorf("校验通道占用: %w", err)
}

// channelLock 串行化「查通道是否被占 + 写入」这一对操作。
//
// alarmgroupmap 没有 (alarmterminalid, alarmchannel) 唯一索引，
// 而建索引属于 DDL，被红线禁止 —— 只能用应用级命名锁保证原子性。
func (s *Service) channelLock(ctx context.Context, terminalID int64, channel int) (func(), error) {
	return store.Lock(ctx, s.db, fmt.Sprintf("htweb_alarm_map_%d_%d", terminalID, channel))
}

func (s *Service) CreateMapping(ctx context.Context, u *auth.User, in MappingInput) (int64, error) {
	if err := s.validate(ctx, u, &in); err != nil {
		return 0, err
	}
	unlock, err := s.channelLock(ctx, in.AlarmTerminalID, in.AlarmChannel)
	if err != nil {
		return 0, err
	}
	defer unlock()

	if err := s.checkChannelFree(ctx, in.AlarmTerminalID, in.AlarmChannel, 0); err != nil {
		return 0, err
	}
	res, err := s.db.ExecContext(ctx, `
		INSERT INTO alarmgroupmap (info, alarmterminalid, alarmchannel, firealarmgroupid, mediaid)
		VALUES (?,?,?,?,?)`,
		in.Info, in.AlarmTerminalID, in.AlarmChannel, in.AlarmAreaID, in.MediaID)
	if err != nil {
		return 0, fmt.Errorf("新建报警映射: %w", err)
	}
	return res.LastInsertId()
}

func (s *Service) UpdateMapping(ctx context.Context, u *auth.User, id int64, in MappingInput) error {
	// 先确认这条映射本身对当前用户可见，否则改别人的映射不会被任何后续校验挡住
	if err := s.assertMappingVisible(ctx, u, id); err != nil {
		return err
	}
	if err := s.validate(ctx, u, &in); err != nil {
		return err
	}
	unlock, err := s.channelLock(ctx, in.AlarmTerminalID, in.AlarmChannel)
	if err != nil {
		return err
	}
	defer unlock()

	// 关键修复：修改同样要查通道占用，且排除自身（D-132）
	if err := s.checkChannelFree(ctx, in.AlarmTerminalID, in.AlarmChannel, id); err != nil {
		return err
	}
	res, err := s.db.ExecContext(ctx, `
		UPDATE alarmgroupmap SET info = ?, alarmterminalid = ?, alarmchannel = ?,
		       firealarmgroupid = ?, mediaid = ?
		WHERE id = ?`,
		in.Info, in.AlarmTerminalID, in.AlarmChannel, in.AlarmAreaID, in.MediaID, id)
	if err != nil {
		return fmt.Errorf("修改报警映射: %w", err)
	}
	if n, err := res.RowsAffected(); err == nil && n == 0 {
		// 行存在但值没变也会是 0，所以这里不能直接报「不存在」。
		// 存在性已在 assertMappingVisible 里确认过，这里静默通过。
		_ = n
	}
	return nil
}

// assertMappingVisible 校验映射存在且对该用户可见。
// 不区分「不存在」与「无权」，避免把接口变成映射存在性探针。
func (s *Service) assertMappingVisible(ctx context.Context, u *auth.User, id int64) error {
	q := `SELECT COUNT(*) FROM alarmgroupmap m WHERE m.id = ?`
	args := []interface{}{id}
	if c, a := mappingVisibleCond(u); c != "" {
		q += " AND " + c
		args = append(args, a...)
	}
	var n int
	if err := s.db.QueryRowContext(ctx, q, args...).Scan(&n); err != nil {
		return fmt.Errorf("校验映射可见性: %w", err)
	}
	if n == 0 {
		return ErrNotFound
	}
	return nil
}

// GetMapping 取单条映射，供编辑弹窗回填。
func (s *Service) GetMapping(ctx context.Context, u *auth.User, id int64) (*Mapping, error) {
	if err := s.assertMappingVisible(ctx, u, id); err != nil {
		return nil, err
	}
	var it Mapping
	var termOK, areaOK, mediaOK bool
	err := s.db.QueryRowContext(ctx, `
		SELECT m.id, COALESCE(m.info,''),
		       m.alarmterminalid, t.id IS NOT NULL, COALESCE(t.terminalname,''), COALESCE(t.channel,0),
		       m.alarmchannel,
		       m.firealarmgroupid, a.id IS NOT NULL, COALESCE(a.name,''),
		       m.mediaid, md.id IS NOT NULL, COALESCE(md.name,'')
		FROM alarmgroupmap m
		LEFT JOIN terminal  t  ON t.id  = m.alarmterminalid
		LEFT JOIN alarmarea a  ON a.id  = m.firealarmgroupid
		LEFT JOIN media     md ON md.id = m.mediaid
		WHERE m.id = ? LIMIT 1`, id).
		Scan(&it.ID, &it.Info,
			&it.AlarmTerminalID, &termOK, &it.AlarmTerminalName, &it.TerminalChannels,
			&it.AlarmChannel,
			&it.AlarmAreaID, &areaOK, &it.AlarmAreaName,
			&it.MediaID, &mediaOK, &it.MediaName)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询报警映射: %w", err)
	}
	it.TerminalDeleted, it.AreaDeleted, it.MediaDeleted = !termOK, !areaOK, !mediaOK
	decorateMapping(&it)
	return &it, nil
}

// DeleteMappingsResult 是取消映射的结果。
type DeleteMappingsResult struct {
	Deleted []int64 `json:"deleted"`
	Skipped []int64 `json:"skipped"`
}

// DeleteMappings 取消映射。
//
// 旧版 cancel_fire_alarm_mapping_msg 把 $_GET['id'] 原样拼进 IN (...)，
// 而且不做任何归属校验 —— 构造 URL 就能删掉别人的报警联动配置。
func (s *Service) DeleteMappings(ctx context.Context, u *auth.User, ids []int64) (*DeleteMappingsResult, error) {
	out := &DeleteMappingsResult{Deleted: []int64{}, Skipped: []int64{}}
	if len(ids) == 0 {
		return out, nil
	}
	for _, id := range ids {
		if err := s.assertMappingVisible(ctx, u, id); err != nil {
			if errors.Is(err, ErrNotFound) {
				out.Skipped = append(out.Skipped, id)
				continue
			}
			return nil, err
		}
		out.Deleted = append(out.Deleted, id)
	}
	if len(out.Deleted) == 0 {
		return out, nil
	}
	ph, args := placeholders(out.Deleted)
	if _, err := s.db.ExecContext(ctx,
		`DELETE FROM alarmgroupmap WHERE id IN (`+ph+`)`, args...); err != nil {
		return nil, fmt.Errorf("取消报警映射: %w", err)
	}
	return out, nil
}

func placeholders(ids []int64) (string, []interface{}) {
	if len(ids) == 0 {
		return "NULL", nil
	}
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		args[i] = id
	}
	return strings.TrimSuffix(strings.Repeat("?,", len(ids)), ","), args
}
