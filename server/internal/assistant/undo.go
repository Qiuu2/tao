package assistant

import (
	"context"
	"crypto/rand"
	"database/sql"
	"encoding/hex"
	"errors"
	"fmt"
	"log"
	"time"
)

// 撤销凭据。
//
// # 为什么要有它
//
// 助手执行得很快 —— 一句话就把明天的早读挪走了。说错一句的代价不该是
// 「去六个页面里手工改回来」。所以每个写操作执行前拍一张快照，
// 回话时附一个 undo_token，用户点「撤销」就按快照回滚。
//
// # 为什么有有效期
//
// 广播任务是有时效的：十分钟前取消的那条早读，现在可能已经过了播出时间，
// 「撤销」回去也不会响了 —— 那时候的撤销只会让人以为恢复了。
// 所以凭据到点即失效（默认 10 分钟，config.assistant.undo_ttl）。
//
// # 快照存什么
//
// 由各个意图的执行器自己决定，本层只负责存取。约定是：
// 存**足够把改动还原回去的最小信息**，不是整张表。

// UndoRecord 是一张撤销凭据。
type UndoRecord struct {
	Token      string
	SessionKey string
	UserID     int64
	Intent     Intent
	Summary    string
	Snapshot   map[string]interface{}
	ExpireTime time.Time
	Used       bool
}

// ErrUndoNotFound 凭据不存在、已用过或已过期 —— 三种情况对用户是一回事，
// 不分开报：告诉他「这条撤销已经失效」就够了，细分只会让人追问。
var ErrUndoNotFound = errors.New("撤销凭据已失效")

// NewUndo 记一张凭据，返回 token。
func (s *Service) NewUndo(ctx context.Context, sessionKey string, userID int64,
	intent Intent, summary string, snapshot map[string]interface{}) (string, error) {

	token, err := randomToken()
	if err != nil {
		return "", err
	}
	expire := time.Now().Add(s.cfg.UndoTTL)
	_, err = s.db.ExecContext(ctx, `
		INSERT INTO assistant_undo (token, session_key, userid, intent, summary, snapshot, expiretime)
		VALUES (?,?,?,?,?,?,?)`,
		token, sanitize(sessionKey, 64), userID, string(intent),
		sanitize(summary, 255), toJSON(snapshot), expire)
	if err != nil {
		return "", fmt.Errorf("保存撤销凭据: %w", err)
	}
	return token, nil
}

// TakeUndo 取出一张**还能用**的凭据并当场标记为已用。
//
// ⚠ 标记与取出必须是同一条语句里的原子操作：两个人同时点撤销，
// 分成「先查后更新」会让同一次改动被回滚两次。
// 这里先 UPDATE（带 used=0 与未过期两个条件），只有影响行数为 1 才算抢到。
func (s *Service) TakeUndo(ctx context.Context, token string, userID int64) (*UndoRecord, error) {
	res, err := s.db.ExecContext(ctx, `
		UPDATE assistant_undo SET used = 1
		WHERE token = ? AND userid = ? AND used = 0 AND expiretime > NOW()`, token, userID)
	if err != nil {
		return nil, fmt.Errorf("占用撤销凭据: %w", err)
	}
	if n, _ := res.RowsAffected(); n != 1 {
		return nil, ErrUndoNotFound
	}

	var (
		rec      UndoRecord
		snapshot sql.NullString
		intent   string
	)
	err = s.db.QueryRowContext(ctx, `
		SELECT token, session_key, userid, intent, summary, COALESCE(snapshot,''), expiretime
		FROM assistant_undo WHERE token = ?`, token).
		Scan(&rec.Token, &rec.SessionKey, &rec.UserID, &intent, &rec.Summary, &snapshot, &rec.ExpireTime)
	if err != nil {
		return nil, fmt.Errorf("读取撤销凭据: %w", err)
	}
	rec.Intent = Intent(intent)
	rec.Used = true
	rec.Snapshot = map[string]interface{}{}
	fromJSON(snapshot.String, &rec.Snapshot)
	return &rec, nil
}

// PurgeUndo 清掉过期的凭据。
func (s *Service) PurgeUndo(ctx context.Context) (int64, error) {
	res, err := s.db.ExecContext(ctx, `DELETE FROM assistant_undo WHERE expiretime < NOW()`)
	if err != nil {
		return 0, fmt.Errorf("清理过期撤销凭据: %w", err)
	}
	n, _ := res.RowsAffected()
	return n, nil
}

func randomToken() (string, error) {
	buf := make([]byte, 16)
	if _, err := rand.Read(buf); err != nil {
		return "", fmt.Errorf("生成撤销凭据: %w", err)
	}
	return hex.EncodeToString(buf), nil
}

func logf(format string, args ...interface{}) {
	log.Printf("assistant: "+format, args...)
}
