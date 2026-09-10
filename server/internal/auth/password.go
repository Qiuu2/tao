package auth

import (
	"context"
	"crypto/subtle"
	"database/sql"
	"errors"
	"fmt"
	"strings"
	"unicode"

	"htweb/internal/i18n"
)

// 自助修改密码。对应旧版 modifypassword.php + do.php 的 userpasswordmodify_msg。
//
// # 与旧版的三处差别，都是修 bug，不是改需求
//
//  1. **改的是自己的密码**。旧版把 username 从表单塞进 URL 一起提交
//     （`usernames=...&oldpwds=...&newpwds=...` base64 后走 GET），
//     服务端照着这个 username 去改 —— 知道别人旧密码就能改别人的密码。
//     这里的用户名只从会话里取，请求体里根本没有这个字段。
//
//  2. **确认密码真的比一遍**。旧版那个「再输一次」的框只参与复杂度校验，
//     从头到尾没有和新密码比过 —— 两个框填不一样也能改成功，
//     用户会以为自己设的是第二个。
//
//  3. **校验在服务端做**。旧版三条规则全在浏览器里跑（userpasswordmodify.html
//     的 actionforms），服务端一条都不查：直接拿 POST 来的值 md5 了就写库。
//     绕过页面直接发请求，空密码也能设进去。
//
// 另外旧版密码是走 GET 的 query string 传的，会进 access log；这里走 PUT 请求体。
//
// # 复杂度规则照搬旧版
//
// serverconfig.fuzamima 非 0 时要求「数字 + 大写 + 小写 + 符号，8 位以上」
// （旧版正则 `(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[^a-zA-Z0-9]).{8,30}`）；
// 为 0 时旧版不做任何要求。上限取表单上的 maxlength=16，不取正则里的 30 ——
// 旧版界面根本输不进第 17 个字符，30 那个数字是够不着的。
const (
	// PasswordMaxLen 与旧版表单的 maxlength 一致。
	PasswordMaxLen = 16
	// PasswordComplexMinLen 是复杂密码模式下的下限（旧版正则里的 8）。
	PasswordComplexMinLen = 8
)

// ErrOldPasswordWrong 旧密码对不上。措辞照旧版 Old_password_incorrect。
var ErrOldPasswordWrong = errors.New("旧密码输入不正确")

// PasswordPolicy 是界面用来提示要求的那几个数。
type PasswordPolicy struct {
	// Complex 对应 serverconfig.fuzamima != 0。
	Complex   bool `json:"complex"`
	MinLength int  `json:"minLength"`
	MaxLength int  `json:"maxLength"`
}

// Policy 读当前的密码强度要求。
func (m *Manager) Policy(ctx context.Context) (*PasswordPolicy, error) {
	return ReadPolicy(ctx, m.db)
}

// ReadPolicy 与 Policy 同一件事，只是不需要一个 Manager ——
// 用户管理那边也要读这份设置（新建/修改用户的密码要求），
// 而它手上只有 *sql.DB，为了读一列去造一个带密钥和会话时长的 Manager 没有道理。
func ReadPolicy(ctx context.Context, db *sql.DB) (*PasswordPolicy, error) {
	var fuza int
	err := db.QueryRowContext(ctx,
		`SELECT COALESCE(fuzamima,0) FROM serverconfig ORDER BY id LIMIT 1`).Scan(&fuza)
	if err != nil && !errors.Is(err, sql.ErrNoRows) {
		return nil, fmt.Errorf("读取密码强度设置: %w", err)
	}
	p := &PasswordPolicy{Complex: fuza != 0, MinLength: 1, MaxLength: PasswordMaxLen}
	if p.Complex {
		p.MinLength = PasswordComplexMinLen
	}
	return p, nil
}

// CheckPasswordStrength 按当前策略校验一个新密码。
func CheckPasswordStrength(ctx context.Context, p *PasswordPolicy, pwd string) error {
	// 长度按**字符数**算，不按字节 —— 中文密码在旧版里能输进去，
	// 按字节算的话一个汉字顶三格，同一串密码在这里过不了、在旧版能过。
	n := len([]rune(pwd))
	if n < p.MinLength || n > p.MaxLength {
		return fmt.Errorf(i18n.TC(ctx, "密码长度必须在 %d~%d 之间"), p.MinLength, p.MaxLength)
	}
	if !p.Complex {
		return nil
	}
	var digit, upper, lower, symbol bool
	for _, r := range pwd {
		switch {
		case unicode.IsDigit(r):
			digit = true
		case unicode.IsUpper(r):
			upper = true
		case unicode.IsLower(r):
			lower = true
		default:
			// 旧版正则里的 [^a-zA-Z0-9]，非字母数字都算符号
			symbol = true
		}
	}
	if !digit || !upper || !lower || !symbol {
		return errors.New("密码必须同时包含数字、大写字母、小写字母和符号")
	}
	return nil
}

// ChangeOwnPassword 改**当前登录账号**自己的密码。
//
// 成功后不动会话：改自己的密码不该把自己踢下线，旧版也是留在原页面。
// ⚠ 同一个账号在别处登录的会话不会失效 —— 会话表按 token 存，
// 没有「按用户批量作废」这条路。要做的话得给 session 带上 userID，
// 那是另一件事，这里不顺手改。
func (m *Manager) ChangeOwnPassword(ctx context.Context, u *User, oldPwd, newPwd, confirmPwd string) error {
	if strings.TrimSpace(oldPwd) == "" {
		return errors.New("请输入旧密码")
	}
	if newPwd != confirmPwd {
		return errors.New("两次输入的新密码不一致")
	}

	policy, err := m.Policy(ctx)
	if err != nil {
		return err
	}
	if err := CheckPasswordStrength(ctx, policy, newPwd); err != nil {
		return err
	}

	// 旧密码只和**自己这一行**比。用 id 定位，不用请求里的用户名。
	var stored string
	err = m.db.QueryRowContext(ctx,
		`SELECT COALESCE(userpwd,'') FROM book_admin WHERE id = ? LIMIT 1`, u.ID).Scan(&stored)
	if errors.Is(err, sql.ErrNoRows) {
		return errors.New("账号不存在")
	}
	if err != nil {
		return fmt.Errorf("查询用户: %w", err)
	}
	if subtle.ConstantTimeCompare([]byte(strings.ToLower(stored)), []byte(MD5Hex(oldPwd))) != 1 {
		return ErrOldPasswordWrong
	}
	if strings.EqualFold(stored, MD5Hex(newPwd)) {
		return errors.New("新密码不能和旧密码相同")
	}

	// UPDATE 一律带 WHERE id：旧版是按 username 改的，用户名可以重名（book_admin
	// 那一列没有唯一索引），重名时会把两个人的密码一起改掉。
	if _, err := m.db.ExecContext(ctx,
		`UPDATE book_admin SET userpwd = ? WHERE id = ?`, MD5Hex(newPwd), u.ID); err != nil {
		return fmt.Errorf("更新密码: %w", err)
	}
	return nil
}
