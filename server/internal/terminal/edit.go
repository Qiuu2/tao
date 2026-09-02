package terminal

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"net"
	"strings"
	"unicode/utf8"

	"htweb/internal/auth"
)

// Detail 是编辑弹窗需要的终端数据。
type Detail struct {
	ID           int64  `json:"id"`
	TerminalName string `json:"terminalname"`
	TypeID       int    `json:"typeid"`
	GroupID      int64  `json:"groupId"`
	IP           string `json:"ip"`
	Postion      string `json:"postion"`
	Volume       int    `json:"volume"`
	MAC          string `json:"mac"`
	NetState     int    `json:"netstate"`
}

func (s *Service) Get(ctx context.Context, u *auth.User, id int64) (*Detail, error) {
	ok, _, err := s.CheckBound(ctx, u, []int64{id})
	if err != nil {
		return nil, err
	}
	if len(ok) == 0 {
		// 不区分「不存在」与「没绑定」，避免变成终端存在性探针
		return nil, ErrNoPermission
	}

	d := &Detail{ID: id}
	var postion, mac sql.NullString
	err = s.db.QueryRowContext(ctx, `
		SELECT COALESCE(terminalname,''), typeid, COALESCE(ip,''),
		       postion, COALESCE(volume,0), mac, COALESCE(netstate,0)
		FROM terminal WHERE id = ? LIMIT 1`, id).
		Scan(&d.TerminalName, &d.TypeID, &d.IP, &postion, &d.Volume, &mac, &d.NetState)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrNotFound
	}
	if err != nil {
		return nil, fmt.Errorf("查询终端: %w", err)
	}
	d.Postion, d.MAC = postion.String, mac.String

	// 分区以 terminalofgroup 为准（契约 C-23）
	var gid sql.NullInt64
	if err := s.db.QueryRowContext(ctx,
		`SELECT groupid FROM terminalofgroup WHERE terminalid = ? ORDER BY id LIMIT 1`, id).
		Scan(&gid); err != nil && !errors.Is(err, sql.ErrNoRows) {
		return nil, fmt.Errorf("查询终端分区: %w", err)
	}
	d.GroupID = gid.Int64
	return d, nil
}

// UpdateInput 是修改终端的入参。
type UpdateInput struct {
	TerminalName string
	TypeID       int
	GroupID      int64
	IP           string
	Postion      string
	Volume       int
}

// Update 修改终端。
//
// 旧版 terminaledit_msg 的注释写着「没有被使用到---实际也改不了」，
// 六个字段全部是 $_POST 裸拼进 SQL（缺陷 D-83），
// 且只写 terminal.groupid、不碰 terminalofgroup（D-85）——
// 而列表查的是 terminalofgroup，所以改完分区列表里根本看不出变化。
func (s *Service) Update(ctx context.Context, u *auth.User, id int64, in UpdateInput) error {
	ok, _, err := s.CheckBound(ctx, u, []int64{id})
	if err != nil {
		return err
	}
	if len(ok) == 0 {
		return ErrNoPermission
	}

	if err := s.validate(ctx, in); err != nil {
		return err
	}

	tx, err := s.db.BeginTx(ctx, nil)
	if err != nil {
		return fmt.Errorf("开启事务: %w", err)
	}
	defer func() { _ = tx.Rollback() }()

	// postion 是 gbk 列，但**不能在这里做 utf8→gbk 转换**：
	// 连接字符集是 utf8，MySQL 会自己在两种字符集之间转换。
	// 实测 '教学楼一层东' 经 utf8 连接写入后，存储字节就是正确的 GBK
	// BDCCD1A7C2A5D2BBB2E3B6AB。再手工转一次会变成双重编码。
	if _, err := tx.ExecContext(ctx, `
		UPDATE terminal SET groupid = ?, terminalname = ?, typeid = ?,
		                    ip = ?, postion = ?, volume = ?
		WHERE id = ?`,
		in.GroupID, in.TerminalName, in.TypeID, in.IP, in.Postion, in.Volume, id); err != nil {
		return fmt.Errorf("更新终端: %w", err)
	}

	// 关联表与 terminal.groupid 同步维护（BR-146 / 契约 C-23）。
	// 先全删再插：旧数据里同一终端可能有多条 terminalofgroup 行。
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM terminalofgroup WHERE terminalid = ?`, id); err != nil {
		return fmt.Errorf("清理终端分区关联: %w", err)
	}
	if in.GroupID > 0 {
		if _, err := tx.ExecContext(ctx,
			`INSERT INTO terminalofgroup (terminalid, groupid) VALUES (?, ?)`,
			id, in.GroupID); err != nil {
			return fmt.Errorf("写入终端分区关联: %w", err)
		}
	}
	// 任务与报警里的分区号跟着走。这一列只是终端分区的快照，没有独立语义
	// （全部写入方填的都是「该终端当时所属的分区」），所以无条件覆盖 ——
	// 与终端分区页 zone.writeMembers 第③步同一套处理，理由见那里。
	// 不同步的话，改完分区后台仍按旧分区下发，播出来的范围是错的。
	if _, err := tx.ExecContext(ctx,
		`UPDATE terminaloftask SET groupid = ? WHERE terminalid = ?`,
		in.GroupID, id); err != nil {
		return fmt.Errorf("同步任务终端分区号: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`UPDATE terminalofalarmgroup SET groupid = ? WHERE terminalid = ?`,
		in.GroupID, id); err != nil {
		return fmt.Errorf("同步报警分区终端分区号: %w", err)
	}
	return tx.Commit()
}

func (s *Service) validate(ctx context.Context, in UpdateInput) error {
	name := strings.TrimSpace(in.TerminalName)
	if name == "" {
		return fmt.Errorf("终端名称不能为空")
	}
	if utf8.RuneCountInString(name) > 85 {
		return fmt.Errorf("终端名称最多 85 个字符")
	}
	if in.Volume < 0 || in.Volume > 100 {
		return fmt.Errorf("音量必须在 0 ~ 100 之间")
	}
	// 修复 D-87：旧版对 ip 没有任何格式校验
	if ip := net.ParseIP(in.IP); ip == nil || ip.To4() == nil {
		return fmt.Errorf("IP 地址格式不正确，必须是 IPv4")
	}
	if err := s.checkGBK(ctx, in.Postion); err != nil {
		return err
	}

	var n int
	if err := s.db.QueryRowContext(ctx,
		`SELECT COUNT(*) FROM terminaltype WHERE id = ?`, in.TypeID).Scan(&n); err != nil {
		return fmt.Errorf("校验终端类型: %w", err)
	}
	if n == 0 {
		return fmt.Errorf("终端类型不存在")
	}
	if in.GroupID > 0 {
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM serverplaystream WHERE streamid = ?`, in.GroupID).Scan(&n); err != nil {
			return fmt.Errorf("校验终端分区: %w", err)
		}
		if n == 0 {
			return fmt.Errorf("终端分区不存在")
		}
	}
	return nil
}

// checkGBK 拦截 GBK 表示不了的字符，并按 GBK 字节数校验长度。
//
// postion 是 gbk 列。MySQL 转换时对无法映射的字符**不报错，直接换成 '?'** ——
// 实测 '测试🔊' 存进去变成 B2E2CAD43F3F3F3F，emoji 悄无声息地没了。
// 这是不可逆的静默数据损坏，必须在写入前拦下来而不是事后发现。
//
// 判定交给 MySQL 自己做往返转换：utf8 → gbk → utf8 后若与原串不等，
// 说明中间丢了字符。这样用的是真实转换表，比在 Go 里按码位范围猜准确得多，
// 也不必为此引入 golang.org/x/text 依赖。
func (s *Service) checkGBK(ctx context.Context, v string) error {
	if v == "" {
		return nil
	}
	var roundTrip string
	var gbkBytes int
	if err := s.db.QueryRowContext(ctx,
		`SELECT CONVERT(CONVERT(? USING gbk) USING utf8), LENGTH(CONVERT(? USING gbk))`,
		v, v).Scan(&roundTrip, &gbkBytes); err != nil {
		return fmt.Errorf("校验位置信息字符集: %w", err)
	}
	if gbkBytes > 255 {
		return fmt.Errorf("位置信息过长：按 GBK 计 %d 字节，上限 255", gbkBytes)
	}
	if roundTrip != v {
		return fmt.Errorf("位置信息含有 GBK 无法表示的字符（该列是 gbk 字符集，这些字符存进去会变成问号）")
	}
	return nil
}
