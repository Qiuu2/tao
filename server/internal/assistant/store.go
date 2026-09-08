package assistant

import (
	"context"
	"database/sql"
	"encoding/json"
	"errors"
	"fmt"
	"strings"
	"time"
	"unicode/utf8"
)

// assistant_* 六张表的读写。
//
// 表结构与建表脚本见 db/assistant_tables.sql，字段说明见《数据库文档》第 8 章。
//
// # ⚠ 四字节字符必须先剔掉
//
// htweb 的连接串写死 charset=utf8（见 config.DSN 的注释，改不得）。
// utf8 连接下四字节字符（emoji、部分生僻字）在协议层就会出问题，
// 表现是整条 INSERT 报 "Incorrect string value"，一句话把整轮对话弄失败。
// 所以**所有入库的用户文本都先过 sanitize**。
//
// 这不是偷懒：连接字符集不能改（改了旧表的读写就与旧 PHP 不一致），
// 列声明成 utf8mb4 也救不回来 —— 字符在到达列之前就已经坏了。

// sanitize 去掉四字节字符与控制字符，并按字节截断到上限。
//
// 截断按**字符边界**做，不能直接切字节 —— 切在一个汉字中间会写进半个字符，
// 那比截断本身更糟（后面读出来是乱码，还查不出原因）。
func sanitize(s string, maxBytes int) string {
	var b strings.Builder
	b.Grow(len(s))
	for _, r := range s {
		switch {
		case r == '\n' || r == '\t':
			b.WriteRune(r)
		case r < 0x20 || r == 0x7f:
			// 其它控制字符直接丢
		case utf8.RuneLen(r) > 3:
			// 四字节字符（emoji 等）：utf8 连接存不下，丢掉而不是让整条写入失败
		default:
			if b.Len()+utf8.RuneLen(r) > maxBytes {
				return b.String()
			}
			b.WriteRune(r)
		}
	}
	return b.String()
}

func toJSON(v interface{}) string {
	if v == nil {
		return ""
	}
	raw, err := json.Marshal(v)
	if err != nil {
		return ""
	}
	return string(raw)
}

func fromJSON(s string, out interface{}) {
	if strings.TrimSpace(s) == "" {
		return
	}
	_ = json.Unmarshal([]byte(s), out)
}

// ---------- 会话上下文 ----------

// Session 是一个前端会话的跨轮状态。
type Session struct {
	SessionKey string
	UserID     int64
	LastIntent Intent
	LastSlots  map[string][]string
	Locked     map[string]string
	Pending    map[string]interface{}
	FailCount  int
	UpdateTime time.Time
}

// LoadSession 读会话；不存在或已过期都返回一个空会话（不是错误）。
//
// 过期的判定放在读这一侧而不是靠定时清理：清理任务挂了会让过期会话
// 无限期生效，「刚才那个」指向一小时前的东西比没有指代更糟。
func (s *Service) LoadSession(ctx context.Context, key string, userID int64) (*Session, error) {
	out := &Session{SessionKey: key, UserID: userID,
		LastSlots: map[string][]string{}, Locked: map[string]string{}}
	if strings.TrimSpace(key) == "" {
		return out, nil
	}
	var (
		lastIntent, lastSlots, locked, pending sql.NullString
		failCount                              int
		updateTime                             time.Time
		uid                                    int64
	)
	err := s.db.QueryRowContext(ctx, `
		SELECT userid, last_intent, last_slots, locked, pending, fail_count, updatetime
		FROM assistant_session WHERE session_key = ?`, key).
		Scan(&uid, &lastIntent, &lastSlots, &locked, &pending, &failCount, &updateTime)
	if errors.Is(err, sql.ErrNoRows) {
		return out, nil
	}
	if err != nil {
		return nil, fmt.Errorf("读取助手会话: %w", err)
	}
	// 会话是按人隔离的：同一个 session_key 落到别人手里也拿不到上下文
	if uid != userID {
		return out, nil
	}
	if s.cfg.SessionTTL > 0 && time.Since(updateTime) > s.cfg.SessionTTL {
		return out, nil
	}
	out.LastIntent = Intent(lastIntent.String)
	out.FailCount = failCount
	out.UpdateTime = updateTime
	fromJSON(lastSlots.String, &out.LastSlots)
	fromJSON(locked.String, &out.Locked)
	if pending.Valid && strings.TrimSpace(pending.String) != "" {
		out.Pending = map[string]interface{}{}
		fromJSON(pending.String, &out.Pending)
	}
	if out.LastSlots == nil {
		out.LastSlots = map[string][]string{}
	}
	if out.Locked == nil {
		out.Locked = map[string]string{}
	}
	return out, nil
}

// SaveSession 落一次会话。用 INSERT ... ON DUPLICATE KEY UPDATE，
// 避免「先查再插」在并发下写重（session_key 上有唯一索引兜底）。
func (s *Service) SaveSession(ctx context.Context, sess *Session) error {
	if sess == nil || strings.TrimSpace(sess.SessionKey) == "" {
		return nil
	}
	_, err := s.db.ExecContext(ctx, `
		INSERT INTO assistant_session
		  (session_key, userid, last_intent, last_slots, locked, pending, fail_count)
		VALUES (?,?,?,?,?,?,?)
		ON DUPLICATE KEY UPDATE
		  userid=VALUES(userid), last_intent=VALUES(last_intent),
		  last_slots=VALUES(last_slots), locked=VALUES(locked),
		  pending=VALUES(pending), fail_count=VALUES(fail_count)`,
		sanitize(sess.SessionKey, 64), sess.UserID, string(sess.LastIntent),
		toJSON(sess.LastSlots), toJSON(sess.Locked), toJSON(sess.Pending), sess.FailCount)
	if err != nil {
		return fmt.Errorf("保存助手会话: %w", err)
	}
	return nil
}

// PurgeSessions 清掉过期会话，随服务里的定时任务跑。
func (s *Service) PurgeSessions(ctx context.Context) (int64, error) {
	if s.cfg.SessionTTL <= 0 {
		return 0, nil
	}
	cutoff := time.Now().Add(-s.cfg.SessionTTL)
	res, err := s.db.ExecContext(ctx,
		`DELETE FROM assistant_session WHERE updatetime < ?`, cutoff)
	if err != nil {
		return 0, fmt.Errorf("清理过期助手会话: %w", err)
	}
	n, _ := res.RowsAffected()
	return n, nil
}

// ---------- 对话与指令历史 ----------

// Message 是历史里的一条。
type Message struct {
	ID         int64               `json:"id"`
	SessionKey string              `json:"sessionKey"`
	UserID     int64               `json:"userId"`
	Username   string              `json:"username"`
	Role       string              `json:"role"`
	Text       string              `json:"text"`
	Intent     string              `json:"intent"`
	Confidence float64             `json:"confidence"`
	Slots      map[string][]string `json:"slots"`
	ActionLog  []map[string]any    `json:"actionLog"`
	Status     string              `json:"status"`
	CreateTime string              `json:"createtime"`
}

// AppendMessage 追加一条历史。
//
// text 上限按列类型 text（65535 字节）留足余量取 8000 ——
// 用户不会正常发这么长的指令，超长多半是误粘贴，截断比写失败好。
func (s *Service) AppendMessage(ctx context.Context, m *Message) error {
	_, err := s.db.ExecContext(ctx, `
		INSERT INTO assistant_message
		  (session_key, userid, username, role, text, intent, confidence, slots, action_log, status)
		VALUES (?,?,?,?,?,?,?,?,?,?)`,
		sanitize(m.SessionKey, 64), m.UserID, sanitize(m.Username, 45),
		m.Role, sanitize(m.Text, 8000), m.Intent, m.Confidence,
		toJSON(m.Slots), toJSON(m.ActionLog), m.Status)
	if err != nil {
		return fmt.Errorf("写入助手历史: %w", err)
	}
	return nil
}

// ListMessages 取某个用户最近的历史，新的在前。
func (s *Service) ListMessages(ctx context.Context, userID int64, limit int) ([]Message, error) {
	if limit <= 0 || limit > 500 {
		limit = 50
	}
	rows, err := s.db.QueryContext(ctx, `
		SELECT id, session_key, userid, username, role, COALESCE(text,''),
		       intent, confidence, COALESCE(slots,''), COALESCE(action_log,''), status,
		       DATE_FORMAT(createtime,'%Y-%m-%d %H:%i:%s')
		FROM assistant_message WHERE userid = ?
		ORDER BY id DESC LIMIT ?`, userID, limit)
	if err != nil {
		return nil, fmt.Errorf("查询助手历史: %w", err)
	}
	defer rows.Close()

	out := []Message{}
	for rows.Next() {
		var m Message
		var slots, actionLog string
		if err := rows.Scan(&m.ID, &m.SessionKey, &m.UserID, &m.Username, &m.Role,
			&m.Text, &m.Intent, &m.Confidence, &slots, &actionLog, &m.Status, &m.CreateTime); err != nil {
			return nil, err
		}
		fromJSON(slots, &m.Slots)
		fromJSON(actionLog, &m.ActionLog)
		out = append(out, m)
	}
	return out, rows.Err()
}

// TrimHistory 把某个用户的历史裁到上限条数（原实现是 1000 条）。
//
// ⚠ MySQL 不允许在 DELETE 的子查询里引用同一张表，所以先查出保留边界的 id，
// 再按 id 删 —— 不能写成 DELETE ... WHERE id NOT IN (SELECT ... FROM 同表)。
func (s *Service) TrimHistory(ctx context.Context, userID int64) (int64, error) {
	limit := s.cfg.HistoryLimit
	if limit <= 0 {
		return 0, nil
	}
	var boundary sql.NullInt64
	err := s.db.QueryRowContext(ctx, `
		SELECT id FROM assistant_message WHERE userid = ?
		ORDER BY id DESC LIMIT 1 OFFSET ?`, userID, limit).Scan(&boundary)
	if errors.Is(err, sql.ErrNoRows) {
		return 0, nil
	}
	if err != nil {
		return 0, fmt.Errorf("查询助手历史边界: %w", err)
	}
	res, err := s.db.ExecContext(ctx,
		`DELETE FROM assistant_message WHERE userid = ? AND id <= ?`, userID, boundary.Int64)
	if err != nil {
		return 0, fmt.Errorf("裁剪助手历史: %w", err)
	}
	n, _ := res.RowsAffected()
	return n, nil
}

// ClearHistory 清掉某个用户的全部历史。
func (s *Service) ClearHistory(ctx context.Context, userID int64) (int64, error) {
	res, err := s.db.ExecContext(ctx, `DELETE FROM assistant_message WHERE userid = ?`, userID)
	if err != nil {
		return 0, fmt.Errorf("清空助手历史: %w", err)
	}
	n, _ := res.RowsAffected()
	return n, nil
}

// ---------- 设置 ----------

// GetSettings 取设置。scope=user 时按 userid 取，取不到再落回 global。
func (s *Service) GetSettings(ctx context.Context, userID int64) (map[string]string, error) {
	rows, err := s.db.QueryContext(ctx, `
		SELECT scope, name, COALESCE(value,'') FROM assistant_setting
		WHERE scope = 'global' OR (scope = 'user' AND userid = ?)
		ORDER BY FIELD(scope,'global','user')`, userID)
	if err != nil {
		return nil, fmt.Errorf("查询助手设置: %w", err)
	}
	defer rows.Close()
	out := map[string]string{}
	for rows.Next() {
		var scope, name, value string
		if err := rows.Scan(&scope, &name, &value); err != nil {
			return nil, err
		}
		// 排序保证 user 在 global 之后，同名时后写的覆盖前面的
		out[name] = value
	}
	return out, rows.Err()
}

// SetSetting 写一条设置。userID 为 0 表示全局。
func (s *Service) SetSetting(ctx context.Context, userID int64, name, value string) error {
	scope := "global"
	if userID > 0 {
		scope = "user"
	} else {
		userID = 0
	}
	_, err := s.db.ExecContext(ctx, `
		INSERT INTO assistant_setting (scope, userid, name, value) VALUES (?,?,?,?)
		ON DUPLICATE KEY UPDATE value = VALUES(value)`,
		scope, userID, sanitize(name, 64), sanitize(value, 60000))
	if err != nil {
		return fmt.Errorf("保存助手设置: %w", err)
	}
	return nil
}
