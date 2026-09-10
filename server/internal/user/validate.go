package user

// 新建 / 修改用户时的字段校验，逐条照旧版 useradd.html + usermodify.html 的
// checkform()。旧版这些规则**全在浏览器里跑**，服务端一条都不查 ——
// 绕过页面直接发请求，空用户名、一位数密码都能存进去。这里补在服务端。

import (
	"context"
	"errors"
	"fmt"
	"regexp"
	"strings"

	"htweb/internal/auth"
	"htweb/internal/i18n"
)

const (
	// UsernameMaxLen 跟着 book_admin.username 的列宽。
	UsernameMaxLen = 50
	// InfoMaxLen 是「用户描述」的上限。旧版表单写的是 maxlength="30"，
	// 提示语也是「注意：最多30字符」——列宽 255 是够的，30 是界面定的。
	InfoMaxLen = 30
	// SimplePasswordMinLen 是**不开复杂密码**时的下限。
	//
	// ⚠ 这一条与「修改密码」那边不一样：旧版 modifypassword 在简单模式下
	// 不做任何长度要求，而 useradd / usermodify 明确判了 `length < 6`。
	// 两处规则本来就不同，不要合并（见 auth.CheckPasswordStrength 的说明）。
	SimplePasswordMinLen = 6
)

// ErrValidation 是「用户填错了」这一类错误的标记。
//
// # 为什么要一个标记，而不是像别处那样按关键词认
//
// handler 那边原来是拿中文关键词去匹配 err.Error() 判断该回 400 还是 500。
// 带参数的提示（「密码长度必须在 %d~%d 之间」）必须**先翻译格式串再 Sprintf**
// —— 响应边界那一层拿到的已经是成品句子，配不上带 %d 的字典键。
// 于是英文界面下这些错误的 err.Error() 是英文，中文关键词一个都匹配不上，
// 用户看到的是「服务器内部错误」。实测过。
//
// 包一层标记，判断就与文案无关了。
var ErrValidation = errors.New("参数校验未通过")

// invalid 把一句提示包成 ErrValidation。
func invalid(msg string) error { return fmt.Errorf("%w: %s", ErrValidation, msg) }

// nameCharset 是旧版的 isChinaOrNumbOrLett()：`^[0-9a-zA-Z一-龥]+$`。
// 用户名和「简单模式下的密码」都过这一关 —— 也就是说不允许空格、下划线、
// 连字符这些。看着严，但改宽了就和旧系统对不上：同一个库两边都在写。
var nameCharset = regexp.MustCompile(`^[0-9a-zA-Z\x{4e00}-\x{9fa5}]+$`)

// checkUsername 校验用户名。
func checkUsername(ctx context.Context, name string) error {
	if name == "" {
		return invalid(i18n.TC(ctx, "请输入用户名"))
	}
	// 长度按字符数算，与旧版一致：中文用户名在旧版里是允许的
	if len([]rune(name)) > UsernameMaxLen {
		return invalid(fmt.Sprintf(i18n.TC(ctx, "用户名最多 %d 个字符"), UsernameMaxLen))
	}
	if !nameCharset.MatchString(name) {
		return invalid(i18n.TC(ctx, "用户名只能是中文、字母或数字"))
	}
	return nil
}

// checkInfo 校验用户描述。
func checkInfo(ctx context.Context, info string) error {
	if len([]rune(info)) > InfoMaxLen {
		return invalid(fmt.Sprintf(i18n.TC(ctx, "用户描述最多 %d 个字符"), InfoMaxLen))
	}
	return nil
}

// PasswordRule 是新建 / 修改用户这两张表单的密码要求，供界面直接显示。
type PasswordRule struct {
	// Complex 对应 serverconfig.fuzamima != 0。
	Complex   bool `json:"complex"`
	MinLength int  `json:"minLength"`
	MaxLength int  `json:"maxLength"`
}

// PasswordRule 读当前的密码要求。
//
// 与 auth.Policy 同一份设置（serverconfig.fuzamima），但**简单模式的下限不同**：
// 那边是 1（旧版 modifypassword 不查长度），这里是 6（旧版 useradd 判了 length<6）。
func (s *Service) PasswordRule(ctx context.Context) (*PasswordRule, error) {
	p, err := s.authPolicy(ctx)
	if err != nil {
		return nil, err
	}
	return &PasswordRule{Complex: p.Complex, MinLength: p.MinLength, MaxLength: p.MaxLength}, nil
}

// authPolicy 取 auth 那边的策略，再把简单模式的下限换成本页的 6。
func (s *Service) authPolicy(ctx context.Context) (*auth.PasswordPolicy, error) {
	p, err := auth.ReadPolicy(ctx, s.db)
	if err != nil {
		return nil, err
	}
	if !p.Complex {
		p.MinLength = SimplePasswordMinLen
	}
	return p, nil
}

// checkPassword 校验新建 / 修改用户时填的密码。
//
//	复杂模式：数字 + 大写 + 小写 + 符号，8 ~ 16 位（与 auth 那边同一条正则）
//	简单模式：6 ~ 16 位，且只能是中文、字母或数字
func (s *Service) checkPassword(ctx context.Context, pwd, confirm string) error {
	if pwd != confirm {
		return invalid(i18n.TC(ctx, "两次输入的密码不一致"))
	}
	p, err := s.authPolicy(ctx)
	if err != nil {
		return err
	}
	if err := auth.CheckPasswordStrength(ctx, p, pwd); err != nil {
		// auth 那边的提示同样是「先翻译格式串再 Sprintf」出来的，包上标记
		return invalid(err.Error())
	}
	if !p.Complex && !nameCharset.MatchString(pwd) {
		return invalid(i18n.TC(ctx, "密码只能是中文、字母或数字"))
	}
	return nil
}

// checkTerminals 校验授权终端。
//
// ⚠ 旧版明确要求**至少选一台**（checkform 末尾那句 add_user_terminals
// 「请为该用户选择终端」）。一台都不绑的用户登进来什么设备都看不见，
// 页面上一片空白，谁也说不清是权限问题还是没数据。
func checkTerminals(ctx context.Context, binds []TerminalBind) error {
	if len(binds) == 0 {
		return invalid(i18n.TC(ctx, "请为该用户选择可控制的终端"))
	}
	return nil
}

// trimSerials 清掉序列号前后的空白。旧版原样入库，连空格都留着。
func trimSerials(in []string) []string {
	out := make([]string, len(in))
	for i, v := range in {
		out[i] = strings.TrimSpace(v)
	}
	return out
}
