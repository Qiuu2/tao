package serverparam

import (
	"context"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"time"
)

// 版本设置（:80「服务器信息 → 版本设置」页签）。
//
// # 它不写数据库
//
// :80 那一页只有一个下拉和一个「提交」，提交打到 Lumen 的
// `POST server/serverversion`（ServerBase.php ~1435 行起），id 取 1..5。
// 那个函数从头到尾**没有一句 SQL** —— 它做的是三件事：
//
//	1. 把 sounds/audioserver/audioserver-<版本>.tar.gz 就地解开
//	2.（除 v1.6 外）再把 sounds/serverprogram/serverprogram-<版本>.tar.gz 解开
//	3. 清空 sounds/serverprogram/etc/，然后跑 script/cmd/update_audioserver.sh
//	   —— 这个脚本 docker stop/rm/run 重建 a9000_audioserver 容器
//
// 所以「版本」既不是 serverbaseparam.version 的写入口，也不该往那一列写。
// serverbaseparam.version 现网是后台服务自己上报的字符串
// （TW_V1.0.0.0-Jul 20 2026[16:01:54]），跟这五个包名不是一个东西，
// 覆盖它只会把真实版本号弄丢。这里同样一列都不写，零 DDL、零 UPDATE。
//
// # 现网基本跑不动
//
// update_audioserver.sh 里每一条都是 `sudo docker ...`，而 htweb 跑在 tw 账号下、
// tw 的 sudo 要密码。所以 Ability 会探测一遍，探不通就把原因原样回给界面，
// 不去假装成功 —— 与「时间设置」页的 probeClock 是同一套做法。
//
// ⚠ 换版本 = 换掉正在跑的音频引擎并重建容器，广播会中断。
// 因此接口挂在超管路由上，界面还要再确认一次。

// versionSpec 是一个可选版本：下拉里的名字，以及它要解哪两个包。
// 映射逐条抄自 ServerBase.php，顺序也照抄（下拉就是按 id 升序排的）。
type versionSpec struct {
	ID    int
	Name  string
	Audio string // sounds/audioserver/ 下的包名
	// Program 是 sounds/serverprogram/ 下的包名，空串表示这个版本不换 serverprogram。
	// ⚠ v1.6（id=1）在 :80 上确实**不解** serverprogram 包，不是漏写。
	Program string
}

var versionSpecs = []versionSpec{
	{1, "audioserver-v1.6", "audioserver-v1.6.tar.gz", ""},
	{2, "audioserver-v2.2", "audioserver-v2.2.tar.gz", "serverprogram-v2.4.tar.gz"},
	{3, "audioserver-v2.3", "audioserver-v2.3.tar.gz", "serverprogram-v2.4.tar.gz"},
	{4, "audioserver-v2.4", "audioserver-v2.4.tar.gz", "serverprogram-v2.4.tar.gz"},
	{5, "audioserver-tw1.0", "audioserver-tw1.0.tar.gz", "serverprogram-tw1.0.tar.gz"},
}

// VersionOption 是下拉里的一项。
type VersionOption struct {
	ID   int    `json:"id"`
	Name string `json:"name"`
	// Available 表示对应的 tar 包在机器上存在。不存在的项界面置灰 ——
	// 选一个不存在的包，解压会失败，还不如一开始就选不中。
	Available bool `json:"available"`
}

// VersionState 是版本设置页要的全部数据。
type VersionState struct {
	// Current 是后台服务上报的版本号（serverbaseparam.version），只读展示。
	Current string          `json:"current"`
	Options []VersionOption `json:"options"`
	// CanSwitch 为 false 时界面禁掉「提交」，并把 Reason 显示出来。
	CanSwitch bool   `json:"canSwitch"`
	Reason    string `json:"reason"`
}

func (s *Service) audioDir() string {
	return filepath.Join(s.a9000Root, "sounds", "audioserver")
}

func (s *Service) programDir() string {
	return filepath.Join(s.a9000Root, "sounds", "serverprogram")
}

func (s *Service) updateScript() string {
	return filepath.Join(s.a9000Root, "script", "cmd", "update_audioserver.sh")
}

// probeVersionSwitch 判断这台机器上到底换不换得动。
// 缺什么就说缺什么，别让界面上出现一个点了没反应的按钮。
func (s *Service) probeVersionSwitch(ctx context.Context) (bool, string) {
	if s.a9000Root == "" {
		return false, "未配置 media.root，找不到 a9000 安装目录"
	}
	if _, err := exec.LookPath("tar"); err != nil {
		return false, "系统里没有 tar，无法解开版本包"
	}
	sc := s.updateScript()
	if st, err := os.Stat(sc); err != nil || st.IsDir() {
		return false, "缺少 " + sc + "，无法重建 audioserver 容器"
	}
	// update_audioserver.sh 全程 sudo docker，所以必须以 root 跑它。
	// 跑不动就别往下走：半途而废比不做更糟 —— 包解开了容器却没重建，版本就对不上了。
	//
	// ⚠ 探测用 `sudo -n -l <脚本>` 而不是 `sudo -n true`。
	//   deploy/htweb.sudoers.in 只放开了这一个脚本，`true` 并不在放行名单里，
	//   拿 `true` 去探会得到「不能」，把一个装好了的机器误判成没装。
	//   `-l` 只查权限、不执行，正是我们要的。
	c, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()
	if err := exec.CommandContext(c, "sudo", "-n", "-l", sc).Run(); err != nil {
		return false, "服务账号没有免密执行 " + filepath.Base(sc) + " 的权限。" +
			"在服务器上跑一次 deploy/install-sudoers.sh 即可开通"
	}
	return true, ""
}

// GetVersion 读版本设置页要的数据。
func (s *Service) GetVersion(ctx context.Context) (*VersionState, error) {
	out := &VersionState{Options: make([]VersionOption, 0, len(versionSpecs))}

	// 当前版本号：只读一列，不写
	if err := s.db.QueryRowContext(ctx,
		`SELECT COALESCE(version,'') FROM serverbaseparam LIMIT 1`).Scan(&out.Current); err != nil {
		return nil, fmt.Errorf("查询版本号: %w", err)
	}

	dir := s.audioDir()
	for _, v := range versionSpecs {
		ok := false
		if s.a9000Root != "" {
			if st, err := os.Stat(filepath.Join(dir, v.Audio)); err == nil && !st.IsDir() {
				ok = true
			}
		}
		out.Options = append(out.Options, VersionOption{ID: v.ID, Name: v.Name, Available: ok})
	}

	out.CanSwitch, out.Reason = s.probeVersionSwitch(ctx)
	return out, nil
}

// SwitchVersion 换版本。步骤与顺序逐条对齐 ServerBase.php@serverversion。
//
// 中途任何一步失败都立刻返回错误：宁可停在半路让人看见，
// 也好过吞掉错误、让界面报「成功」而机器上的版本根本没变。
func (s *Service) SwitchVersion(ctx context.Context, id int) error {
	var spec *versionSpec
	for i := range versionSpecs {
		if versionSpecs[i].ID == id {
			spec = &versionSpecs[i]
			break
		}
	}
	if spec == nil {
		return fmt.Errorf("未知的版本编号 %d", id)
	}
	if ok, why := s.probeVersionSwitch(ctx); !ok {
		return fmt.Errorf("%s", why)
	}

	audioPkg := filepath.Join(s.audioDir(), spec.Audio)
	if st, err := os.Stat(audioPkg); err != nil || st.IsDir() {
		return fmt.Errorf("版本包不存在：%s", audioPkg)
	}

	// 解压给足时间：包 11MB 左右，但现网磁盘偶尔很慢。
	// label 单独传：命令名可能是 sudo，报「sudo 执行失败」等于没说。
	run := func(label, name string, args ...string) error {
		c, cancel := context.WithTimeout(ctx, 5*time.Minute)
		defer cancel()
		cmd := exec.CommandContext(c, name, args...)
		if out, err := cmd.CombinedOutput(); err != nil {
			return fmt.Errorf("%s失败: %v: %s", label, err, trimOut(out))
		}
		return nil
	}

	if err := run("解开 "+spec.Audio, "tar", "-zxf", audioPkg, "-C", s.audioDir()); err != nil {
		return err
	}
	if spec.Program != "" {
		pkg := filepath.Join(s.programDir(), spec.Program)
		if st, err := os.Stat(pkg); err == nil && !st.IsDir() {
			if err := run("解开 "+spec.Program, "tar", "-zxf", pkg, "-C", s.programDir()); err != nil {
				return err
			}
		}
		// 包不在就跳过：:80 上 tar 失败也只是 exec 返回非零，它照样往下走。
	}

	// 旧版是 `rm -rf .../serverprogram/etc/*`。这里不借 shell 展开通配符，
	// 自己列目录再逐个删 —— 少一层 shell，也就少一次把 * 展成别的东西的机会。
	// 只删 etc 目录里的内容，etc 本身保留。
	if err := clearDirContents(s.programDir(), "etc"); err != nil {
		return err
	}

	// ⚠ 必须 `sudo -n` 跑，不能直接跑。
	//   脚本内部每一条都是 `sudo docker ...`：以服务账号身份执行时，
	//   那些内层 sudo 会去要密码（拿不到就失败）；以 root 执行时，
	//   root 再 sudo 是不需要密码的，整条链才走得通。
	return run("重建 audioserver 容器", "sudo", "-n", s.updateScript())
}

// clearDirContents 清空 parent/child 目录里的内容，保留目录本身。
// 目录不存在就当已经清干净了。
func clearDirContents(parent, child string) error {
	dir := filepath.Join(parent, child)
	ents, err := os.ReadDir(dir)
	if os.IsNotExist(err) {
		return nil
	}
	if err != nil {
		return fmt.Errorf("读取 %s: %w", dir, err)
	}
	for _, e := range ents {
		if err := os.RemoveAll(filepath.Join(dir, e.Name())); err != nil {
			return fmt.Errorf("清空 %s: %w", dir, err)
		}
	}
	return nil
}

// trimOut 把命令输出截短，免得把几百行 tar 输出塞进错误消息。
func trimOut(b []byte) string {
	const max = 300
	if len(b) > max {
		return string(b[:max]) + "…"
	}
	return string(b)
}
