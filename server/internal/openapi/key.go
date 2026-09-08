package openapi

import (
	"context"
	"crypto/rand"
	"crypto/sha256"
	"crypto/subtle"
	"database/sql"
	"encoding/hex"
	"errors"
	"fmt"
	"strings"
	"time"

	"htweb/internal/auth"
)

// 开发者密钥。
//
// # 明文只出现一次
//
// 库里存的是 sha256，不是明文。库被拖走也不能拿去调接口。
// 代价是**新建时那一次是唯一能看到明文的机会** —— 界面上会把这句话说在明处，
// 而不是让人事后来问"我的密钥呢"。
//
// 这是有意的取舍。可逆加密看着方便（忘了能找回来），但那意味着解密的钥匙
// 也在这台机器上，被拖库时一起被拿走 —— 等于没加密。
//
// # 密钥长什么样
//
//	hb_<8位前缀>_<32位随机>
//
// 前缀是明文的一部分、**不是秘密**：它只用来在列表里认出"这是哪一把"，
// 以及认证时先按它定位到唯一一行。没有前缀就得全表扫着比 hash。
//
// hb_ 这个头也有用：密钥不小心贴进日志、提到公开仓库时，
// 靠这三个字符能被扫描工具认出来。
const (
	keyBrand     = "hb_"
	keyPrefixLen = 8
	keySecretLen = 32
	keyAlphabet  = "abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789"
)

var (
	ErrKeyNotFound = errors.New("密钥不存在")
	// ErrKeyRejected 是认证没过。**故意不区分**是不存在、停用了还是过期了 ——
	// 对着接口试密钥的人不该从错误信息里学到任何东西。
	// 真实原因走服务端日志，给运维看。
	ErrKeyRejected = errors.New("密钥无效")
)

// Key 是一把密钥（不含明文）。
// ValidationError 表示「调用方填错了」，区别于「服务器出问题了」。
//
// 分这两类不是洁癖：填错了要把原因**原样**告诉用户（不然他不知道该改什么），
// 服务器出问题只能回一句通用话、细节进日志（不然内部结构就泄露出去了）。
// 混在一起的表现是二选一都难看 —— 要么用户看不懂，要么日志里的东西上了页面。
type ValidationError struct{ msg string }

func (e *ValidationError) Error() string { return e.msg }

// badf 造一个 ValidationError。
func badf(format string, a ...any) error { return &ValidationError{msg: fmt.Sprintf(format, a...)} }

type Key struct {
	ID           int64  `json:"id"`
	Name         string `json:"name"`
	Prefix       string `json:"prefix"`
	UserID       int64  `json:"userId"`
	UserName     string `json:"userName"`
	Enabled      bool   `json:"enabled"`
	ExpireTime   string `json:"expiretime"`
	LastUsedTime string `json:"lastusedtime"`
	LastUsedIP   string `json:"lastusedip"`
	CreateTime   string `json:"createtime"`
	// Secret 只在**新建那一次**有值，之后永远是空。
	Secret string `json:"secret,omitempty"`
}

// randomString 用密码学随机源取一串字母数字。
//
// ⚠ 字母表刻意去掉了 0/O/1/l/I —— 密钥要靠人抄写/念给对方，
// 这几个字符长得太像，抄错一位的排查成本远大于少几个字符的熵。
// 剩下 55 个字符，32 位约等于 185 bit，够了。
func randomString(n int) (string, error) {
	buf := make([]byte, n)
	if _, err := rand.Read(buf); err != nil {
		return "", fmt.Errorf("取随机数: %w", err)
	}
	out := make([]byte, n)
	for i, b := range buf {
		out[i] = keyAlphabet[int(b)%len(keyAlphabet)]
	}
	return string(out), nil
}

func hashSecret(plain string) string {
	sum := sha256.Sum256([]byte(plain))
	return hex.EncodeToString(sum[:])
}

// CreateInput 是新建一把密钥要给的东西。
type CreateInput struct {
	Name   string
	UserID int64
	// ExpireTime 为空 = 不过期。格式 2006-01-02 或 2006-01-02 15:04:05。
	ExpireTime string
}

// Create 发一把新密钥。返回的 Key.Secret 是**唯一一次**能拿到明文的机会。
func (s *Service) Create(ctx context.Context, u *auth.User, in CreateInput) (*Key, error) {
	in.Name = strings.TrimSpace(in.Name)
	if in.Name == "" {
		return nil, badf("请给这把密钥起个名字，比如「教务系统」")
	}
	if len([]rune(in.Name)) > 32 {
		return nil, badf("名字太长了，最多 32 个字")
	}
	if in.UserID <= 0 {
		return nil, badf("请选择这把密钥归属哪个账号")
	}

	// ⚠ 不能把密钥挂到一个**比自己权限还大**的账号上 ——
	// 否则一个只有部分权限的管理员，可以给自己发一把 admin 的密钥来提权。
	if err := s.assertCanGrant(ctx, u, in.UserID); err != nil {
		return nil, err
	}

	var expire any
	if t := strings.TrimSpace(in.ExpireTime); t != "" {
		parsed, err := parseExpire(t)
		if err != nil {
			return nil, err
		}
		if !parsed.After(time.Now()) {
			return nil, badf("到期时间要在将来")
		}
		expire = parsed.Format("2006-01-02 15:04:05")
	}

	// prefix 上有唯一索引。撞了就重试 —— 55^8 的空间，撞第二次基本不可能，
	// 但"基本不可能"不是"不会"，所以还是给三次机会而不是直接报错。
	for attempt := 0; attempt < 3; attempt++ {
		prefix, err := randomString(keyPrefixLen)
		if err != nil {
			return nil, err
		}
		secret, err := randomString(keySecretLen)
		if err != nil {
			return nil, err
		}
		plain := keyBrand + prefix + "_" + secret

		res, err := s.db.ExecContext(ctx, `
			INSERT INTO api_key (name, prefix, secret_hash, userid, enabled, expiretime)
			VALUES (?,?,?,?,1,?)`,
			in.Name, prefix, hashSecret(plain), in.UserID, expire)
		if err != nil {
			if isDuplicate(err) {
				continue
			}
			return nil, fmt.Errorf("新建密钥: %w", err)
		}
		id, _ := res.LastInsertId()
		k, err := s.Get(ctx, id)
		if err != nil {
			return nil, err
		}
		k.Secret = plain
		return k, nil
	}
	return nil, fmt.Errorf("生成密钥时前缀连续重复，请再试一次")
}

// assertCanGrant 挡住越权发放。
//
// 管理员可以给任何账号发；非管理员只能给**自己**发。
// 这样不会出现"我权限小，但我给自己发一把 admin 的密钥"这条提权路径。
func (s *Service) assertCanGrant(ctx context.Context, u *auth.User, target int64) error {
	if u.IsAdmin {
		var n int
		if err := s.db.QueryRowContext(ctx,
			`SELECT COUNT(*) FROM book_admin WHERE id = ?`, target).Scan(&n); err != nil {
			return fmt.Errorf("校验归属账号: %w", err)
		}
		if n == 0 {
			return badf("归属账号不存在")
		}
		return nil
	}
	if target != u.ID {
		return badf("只有管理员能把密钥发给别的账号")
	}
	return nil
}

func parseExpire(v string) (time.Time, error) {
	for _, layout := range []string{"2006-01-02 15:04:05", "2006-01-02 15:04", "2006-01-02"} {
		if t, err := time.ParseInLocation(layout, v, time.Local); err == nil {
			// 只给日期时算到那天结束，不是那天零点 ——
			// 写「到 12 月 31 日」的人意思是那天还能用
			if layout == "2006-01-02" {
				t = t.Add(24*time.Hour - time.Second)
			}
			return t, nil
		}
	}
	return time.Time{}, badf("到期时间格式不对，用 2026-12-31 或 2026-12-31 18:00:00")
}

func isDuplicate(err error) bool {
	return err != nil && strings.Contains(err.Error(), "Duplicate entry")
}

// List 列出密钥。非管理员只看自己的。
func (s *Service) List(ctx context.Context, u *auth.User) ([]Key, error) {
	q := `SELECT k.id, k.name, k.prefix, k.userid, COALESCE(b.username,''),
	             k.enabled,
	             DATE_FORMAT(k.expiretime,'%Y-%m-%d %H:%i:%s'),
	             DATE_FORMAT(k.lastusedtime,'%Y-%m-%d %H:%i:%s'),
	             k.lastusedip,
	             DATE_FORMAT(k.createtime,'%Y-%m-%d %H:%i:%s')
	        FROM api_key k
	        LEFT JOIN book_admin b ON b.id = k.userid`
	var args []any
	if !u.IsAdmin {
		q += ` WHERE k.userid = ?`
		args = append(args, u.ID)
	}
	q += ` ORDER BY k.id DESC`

	rows, err := s.db.QueryContext(ctx, q, args...)
	if err != nil {
		return nil, fmt.Errorf("查询密钥: %w", err)
	}
	defer rows.Close()

	out := []Key{}
	for rows.Next() {
		k, err := scanKey(rows)
		if err != nil {
			return nil, err
		}
		out = append(out, *k)
	}
	return out, rows.Err()
}

// Get 取一把密钥。
func (s *Service) Get(ctx context.Context, id int64) (*Key, error) {
	row := s.db.QueryRowContext(ctx, `
		SELECT k.id, k.name, k.prefix, k.userid, COALESCE(b.username,''),
		       k.enabled,
		       DATE_FORMAT(k.expiretime,'%Y-%m-%d %H:%i:%s'),
		       DATE_FORMAT(k.lastusedtime,'%Y-%m-%d %H:%i:%s'),
		       k.lastusedip,
		       DATE_FORMAT(k.createtime,'%Y-%m-%d %H:%i:%s')
		  FROM api_key k
		  LEFT JOIN book_admin b ON b.id = k.userid
		 WHERE k.id = ? LIMIT 1`, id)
	k, err := scanKey(row)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, ErrKeyNotFound
	}
	return k, err
}

type scanner interface {
	Scan(dest ...any) error
}

// scanKey 读一行密钥。
//
// ⚠ 三个时间列在 SQL 里就 DATE_FORMAT 成字符串了，不是在 Go 这边格式化的：
// 驱动开了 parseTime，直接扫会拿到 time.Time，JSON 出去是 RFC3339（带 T 和 Z），
// 和全站其它接口的「2026-09-08 07:44:43」对不上。前端拿到两种格式要分别处理，
// 而这种不一致平时看不出来，只有某个页面显示成一串带 T 的东西时才发现。
func scanKey(sc scanner) (*Key, error) {
	var (
		k                          Key
		enabled                    int
		expire, lastUsed, createAt sql.NullString
	)
	if err := sc.Scan(&k.ID, &k.Name, &k.Prefix, &k.UserID, &k.UserName,
		&enabled, &expire, &lastUsed, &k.LastUsedIP, &createAt); err != nil {
		return nil, err
	}
	k.Enabled = enabled != 0
	k.ExpireTime = expire.String
	k.LastUsedTime = lastUsed.String
	k.CreateTime = createAt.String
	return &k, nil
}

// SetEnabled 停用 / 启用一把密钥。
//
// 停用比删除好：出了事先停下来，查清楚再决定删不删。
// 删掉之后就再也查不出"这把密钥当时是谁的、什么时候发的"。
func (s *Service) SetEnabled(ctx context.Context, u *auth.User, id int64, on bool) error {
	if err := s.assertOwn(ctx, u, id); err != nil {
		return err
	}
	v := 0
	if on {
		v = 1
	}
	_, err := s.db.ExecContext(ctx, `UPDATE api_key SET enabled = ? WHERE id = ?`, v, id)
	if err != nil {
		return fmt.Errorf("更新密钥状态: %w", err)
	}
	return nil
}

// Delete 删掉一把密钥。
func (s *Service) Delete(ctx context.Context, u *auth.User, id int64) error {
	if err := s.assertOwn(ctx, u, id); err != nil {
		return err
	}
	if _, err := s.db.ExecContext(ctx, `DELETE FROM api_key WHERE id = ?`, id); err != nil {
		return fmt.Errorf("删除密钥: %w", err)
	}
	return nil
}

// assertOwn 非管理员只能动自己名下的密钥。
func (s *Service) assertOwn(ctx context.Context, u *auth.User, id int64) error {
	var owner int64
	err := s.db.QueryRowContext(ctx, `SELECT userid FROM api_key WHERE id = ? LIMIT 1`, id).Scan(&owner)
	if errors.Is(err, sql.ErrNoRows) {
		return ErrKeyNotFound
	}
	if err != nil {
		return fmt.Errorf("查询密钥: %w", err)
	}
	if !u.IsAdmin && owner != u.ID {
		// 不说"这把不是你的" —— 那等于确认了这个 id 存在
		return ErrKeyNotFound
	}
	return nil
}

// ---------- 认证 ----------

// Authenticate 把一个明文密钥换成它归属账号的身份。
//
// 失败一律返回 ErrKeyRejected，**不区分**是不存在、停用了还是过期了：
// 对着接口试密钥的人不该从错误信息里学到任何东西。真实原因由调用方记日志。
func (s *Service) Authenticate(ctx context.Context, plain string) (*auth.User, string, error) {
	plain = strings.TrimSpace(plain)
	if !strings.HasPrefix(plain, keyBrand) {
		return nil, "格式不对（不是 hb_ 开头）", ErrKeyRejected
	}
	body := strings.TrimPrefix(plain, keyBrand)
	prefix, _, ok := strings.Cut(body, "_")
	if !ok || len(prefix) != keyPrefixLen {
		return nil, "格式不对（前缀长度不对）", ErrKeyRejected
	}

	var (
		id      int64
		hash    string
		userID  int64
		enabled int
		expire  sql.NullString
	)
	err := s.db.QueryRowContext(ctx, `
		SELECT id, secret_hash, userid, enabled, expiretime
		  FROM api_key WHERE prefix = ? LIMIT 1`, prefix).
		Scan(&id, &hash, &userID, &enabled, &expire)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, "前缀 " + prefix + " 没有对应的密钥", ErrKeyRejected
	}
	if err != nil {
		return nil, "", fmt.Errorf("查询密钥: %w", err)
	}

	// ⚠ 定长比较，不能用 == ：字符串比较会在第一个不同的字节上提前返回，
	// 攻击者可以按响应时间一位一位地把 hash 试出来。
	if subtle.ConstantTimeCompare([]byte(hash), []byte(hashSecret(plain))) != 1 {
		return nil, "密钥 " + prefix + " 校验不通过", ErrKeyRejected
	}
	if enabled == 0 {
		return nil, "密钥 " + prefix + " 已停用", ErrKeyRejected
	}
	if expire.Valid && expire.String != "" {
		if t, err := time.ParseInLocation("2006-01-02 15:04:05", expire.String, time.Local); err == nil {
			if time.Now().After(t) {
				return nil, "密钥 " + prefix + " 已于 " + expire.String + " 到期", ErrKeyRejected
			}
		}
	}

	u, err := s.authMgr.UserByID(ctx, userID)
	if err != nil {
		return nil, "密钥 " + prefix + " 的归属账号有问题: " + err.Error(), ErrKeyRejected
	}
	return u, prefix, nil
}

// TouchUsed 记一次调用的时间与来源。
//
// ⚠ **不是每次调用都写**。第三方一秒调十次的话，这一条 UPDATE 会成为
// 整个接口最热的写操作，而它的价值只是"这把密钥还有人在用吗"——
// 精确到分钟完全够。所以只在上次记录已经超过一分钟时才写。
func (s *Service) TouchUsed(ctx context.Context, prefix, ip string) {
	if prefix == "" {
		return
	}
	_, err := s.db.ExecContext(ctx, `
		UPDATE api_key SET lastusedtime = NOW(), lastusedip = ?
		 WHERE prefix = ?
		   AND (lastusedtime IS NULL OR lastusedtime < DATE_SUB(NOW(), INTERVAL 1 MINUTE))`,
		trimIP(ip), prefix)
	if err != nil {
		// 记不上不影响这次调用成不成 —— 它只是台账
		logf("记录密钥使用时间失败 (%s): %v", prefix, err)
	}
}

func trimIP(ip string) string {
	if len(ip) > 64 {
		return ip[:64]
	}
	return ip
}
