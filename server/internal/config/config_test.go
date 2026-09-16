package config

import "testing"

/*
 * 注册那三条路径的默认值。
 *
 * 现网 config.yaml 里整个 register: 段可以没有（192.168.2.159 就没有），
 * 那时候这三样必须仍然指到装机时的真实位置，而不是一个走 PATH 的裸命令名 ——
 * systemd 给 htweb 的 PATH 里没有 <media.root>/bin。
 */
func TestRegisterPathsDefaultToMediaRoot(t *testing.T) {
	c := &Config{}
	c.Media.Root = "/opt/apps/a9000"

	cases := []struct {
		name string
		got  string
		want string
	}{
		{"注册程序目录", c.RegisterCommandDir(), "/opt/apps/a9000/bin"},
		{"试用起算日文件", c.RegisterSerialFile(), "/opt/apps/a9000/html/ok112/serial"},
		{"试用标记文件", c.RegisterTrialFile(), "/opt/apps/a9000/html/ok112/serialtwo"},
	}
	for _, tc := range cases {
		if tc.got != tc.want {
			t.Errorf("%s = %q，期望 %q", tc.name, tc.got, tc.want)
		}
	}
}

// 配置里写了就以配置为准，推导只是兜底。
func TestRegisterPathsPreferExplicitConfig(t *testing.T) {
	c := &Config{}
	c.Media.Root = "/opt/apps/a9000"
	c.Register.SerialFile = "/srv/serial"
	c.Register.TrialFile = "/srv/serialtwo"

	if got := c.RegisterSerialFile(); got != "/srv/serial" {
		t.Errorf("试用起算日文件 = %q，配置里写的没生效", got)
	}
	if got := c.RegisterTrialFile(); got != "/srv/serialtwo" {
		t.Errorf("试用标记文件 = %q，配置里写的没生效", got)
	}
}

/*
 * media.root 也空着时不能推出 "/bin" 这种根目录下的路径 —— 那比空串危险：
 * 空串会让 register 退回纯 PATH 查找并如实报错，而 "/bin/registerserver"
 * 是一个**真实存在的系统目录**，万一那儿碰巧有个同名文件就会被执行。
 */
func TestRegisterPathsEmptyWhenMediaRootUnset(t *testing.T) {
	c := &Config{}
	for name, got := range map[string]string{
		"注册程序目录":  c.RegisterCommandDir(),
		"试用起算日文件": c.RegisterSerialFile(),
		"试用标记文件":  c.RegisterTrialFile(),
	} {
		if got != "" {
			t.Errorf("%s = %q，media.root 没配时应当是空串", name, got)
		}
	}
}
