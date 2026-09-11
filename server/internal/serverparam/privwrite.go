package serverparam

import (
	"bytes"
	"context"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"time"
)

// 写 /etc 下那几个 root 文件的唯一一条路：一个装好的小脚本 + 一条免密 sudo。
//
// 「主备服务器配置」要改 /etc/hosts、/etc/hostname、/etc/ha.d/*、/etc/crontab，
// 而 htweb 跑在普通账号下（现网 tw），直接写会 permission denied。
//
// # 为什么不是「给 htweb 一条能写任意文件的 sudo」
//
// 那等于把 root 给了 htweb。这里的口子收到最窄：
// htweb **只传一个目标名**（hosts / hostname / ha.cf / …），
// 路径写死在脚本里；内容走 stdin，脚本还要做一遍体检
// （hosts 必须有 127.0.0.1 那一行、hostname 只能一行…）才肯落盘。
// 见 deploy/htweb-ha-apply。
//
// # 改哪一行仍然在 Go 这边算
//
// 脚本只负责「把这段内容原子地放到那个位置」，不碰 sed。
// 锚点定位、要么全做要么全不做这些判断都在 hasync.go 里 ——
// 旧版那套按行号 sed 的毛病不能换个地方重演。

// privHelperName 是安装后的脚本名，与 htweb 二进制同目录。
const privHelperName = "htweb-ha-apply"

// privTargets 把绝对路径映射成脚本认的目标名。
// **这张表要和 deploy/htweb-ha-apply 里的 case 一一对应**，改一边就要改另一边。
var privTargets = map[string]string{
	"/etc/hosts":            "hosts",
	"/etc/hostname":         "hostname",
	"/etc/ha.d/ha.cf":       "ha.cf",
	"/etc/ha.d/haresources": "haresources",
	"/etc/ha.d/ha-post.sh":  "ha-post",
	"/etc/crontab":          "crontab",
}

// privHelperPath 找那个脚本。它和 htweb 二进制装在一起（APP_DIR）。
//
// 用 os.Executable 而不是写死路径：换个目录部署也能找到，
// 而且不必为它再加一项配置。
func privHelperPath() string {
	exe, err := os.Executable()
	if err != nil {
		return ""
	}
	p := filepath.Join(filepath.Dir(exe), privHelperName)
	if st, err := os.Stat(p); err == nil && !st.IsDir() {
		return p
	}
	return ""
}

// canPrivWrite 判断这条路能不能走通：路径在白名单里、脚本装了、sudo 免密。
//
// 探的是**真正要跑的那个形态**（带一个目标名），与 timeset/clock.go 同一套做法。
func canPrivWrite(path string) (helper, target string, ok bool) {
	target, inList := privTargets[filepath.Clean(path)]
	if !inList {
		return "", "", false
	}
	helper = privHelperPath()
	if helper == "" {
		return "", "", false
	}
	ctx, cancel := context.WithTimeout(context.Background(), 3*time.Second)
	defer cancel()
	if err := exec.CommandContext(ctx, "sudo", "-n", "-l", helper, target).Run(); err != nil {
		return "", "", false
	}
	return helper, target, true
}

// privWrite 把 data 交给脚本落盘。
func privWrite(path string, data []byte) error {
	helper, target, ok := canPrivWrite(path)
	if !ok {
		return fmt.Errorf("没有可用的提权写入通道")
	}
	ctx, cancel := context.WithTimeout(context.Background(), 15*time.Second)
	defer cancel()

	cmd := exec.CommandContext(ctx, "sudo", "-n", helper, target)
	cmd.Stdin = bytes.NewReader(data)
	out, err := cmd.CombinedOutput()
	if err != nil {
		return fmt.Errorf("%s：%v", strings.TrimSpace(string(out)), err)
	}
	return nil
}

// privRemove 让脚本去删那个文件（目前只有 /etc/crontab 会走到）。
//
// 目标名后面加一个 remove —— 脚本那边同样只认白名单里的名字，
// 不接受路径，所以这条路删不了别的东西。
func privRemove(path string) error {
	helper, target, ok := canPrivWrite(path)
	if !ok {
		return fmt.Errorf("没有可用的提权删除通道")
	}
	ctx, cancel := context.WithTimeout(context.Background(), 15*time.Second)
	defer cancel()

	out, err := exec.CommandContext(ctx, "sudo", "-n", helper, target, "remove").CombinedOutput()
	if err != nil {
		return fmt.Errorf("%s：%v", strings.TrimSpace(string(out)), err)
	}
	return nil
}
