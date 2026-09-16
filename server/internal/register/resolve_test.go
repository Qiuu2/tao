package register

import (
	"errors"
	"os"
	"path/filepath"
	"strings"
	"testing"
)

/*
 * 这一组盯的是 2026-09-16 现场 192.168.2.159 那个故障：
 *
 *     注册程序无法执行：registerserver
 *     （exec: "registerserver": executable file not found in $PATH）
 *
 * 根因不是代码没调用，也不是 systemd 的 PATH 太短，而是**配置没指到真实路径**：
 * 厂家的 registerserver 装在 <media.root>/bin/ 下，那个目录不在 systemd 给
 * htweb 的 PATH 里，而部署的 config.yaml 里整个 register: 段都没有。
 */

// fakeBin 在临时目录里放一个能执行的空壳，返回它的绝对路径。
func fakeBin(t *testing.T, dir, name string, mode os.FileMode) string {
	t.Helper()
	if err := os.MkdirAll(dir, 0o755); err != nil {
		t.Fatal(err)
	}
	p := filepath.Join(dir, name)
	if err := os.WriteFile(p, []byte("#!/bin/sh\necho failed\n"), mode); err != nil {
		t.Fatal(err)
	}
	return p
}

// 现场那台机器的形状：命令名没配（走默认），二进制在 <media.root>/bin 下，
// PATH 里没有。修好之后应当直接找到它。
func TestFindsCommandInInstallDirWhenNotOnPath(t *testing.T) {
	root := t.TempDir()
	want := fakeBin(t, filepath.Join(root, "bin"), DefaultCommand, 0o755)

	// 把 PATH 清空，坐实「PATH 里绝对没有」这个前提
	t.Setenv("PATH", "")

	s := New(nil, Options{CommandDir: filepath.Join(root, "bin")})
	got, err := s.resolveCommand()
	if err != nil {
		t.Fatalf("装机目录里明明有，却没找到: %v", err)
	}
	if got != want {
		t.Fatalf("找到的是 %s，期望 %s", got, want)
	}
}

// 显式配了绝对路径就以配置为准 —— 哪怕装机目录里也有一个同名的。
//
// 这一条是**安全性**的：配了路径还去别处找，等于偷偷执行另一个二进制。
func TestExplicitPathWinsOverInstallDir(t *testing.T) {
	root := t.TempDir()
	fakeBin(t, filepath.Join(root, "bin"), DefaultCommand, 0o755)
	want := fakeBin(t, filepath.Join(root, "vendor"), DefaultCommand, 0o755)

	s := New(nil, Options{Command: want, CommandDir: filepath.Join(root, "bin")})
	got, err := s.resolveCommand()
	if err != nil {
		t.Fatalf("配了绝对路径反而找不到: %v", err)
	}
	if got != want {
		t.Fatalf("找到的是 %s，期望配置里那个 %s", got, want)
	}
}

// 配了一条不存在的路径：报错要点名是 register.command 这一项，
// 而不是含糊地说「找不到」。
func TestExplicitPathMissingSaysWhichConfigKey(t *testing.T) {
	missing := filepath.Join(t.TempDir(), "nope", "registerserver")
	s := New(nil, Options{Command: missing})

	_, err := s.resolveCommand()
	if err == nil {
		t.Fatal("路径不存在却没报错")
	}
	if !errors.Is(err, ErrCommand) {
		t.Fatalf("没有包成 ErrCommand: %v", err)
	}
	for _, want := range []string{missing, "register.command", "restart htweb"} {
		if !strings.Contains(err.Error(), want) {
			t.Errorf("报错里缺少 %q：%s", want, err)
		}
	}
}

// 文件在、但没有执行位：要说 chmod，别让人以为文件没拷过去。
func TestNoExecuteBitSaysChmod(t *testing.T) {
	root := t.TempDir()
	p := fakeBin(t, filepath.Join(root, "bin"), DefaultCommand, 0o644)
	s := New(nil, Options{Command: p})

	_, err := s.resolveCommand()
	if err == nil {
		t.Fatal("没有执行权限却当成能跑")
	}
	if !strings.Contains(err.Error(), "chmod +x") {
		t.Errorf("报错里没告诉人怎么改：%s", err)
	}
}

/*
 * 哪儿都找不到时，报错必须能照着做。
 *
 * 「executable file not found in $PATH」对现场没有用 —— 它不说该装哪儿、
 * 也不说该改哪个配置。所以这里逐条盯住：找过的地方、配置键名、重启命令。
 */
func TestNotFoundAnywhereListsWhereItLooked(t *testing.T) {
	root := t.TempDir()
	dir := filepath.Join(root, "bin")
	if err := os.MkdirAll(dir, 0o755); err != nil {
		t.Fatal(err)
	}
	t.Setenv("PATH", "")

	s := New(nil, Options{CommandDir: dir})
	_, err := s.resolveCommand()
	if err == nil {
		t.Fatal("哪儿都没有却没报错")
	}
	if !errors.Is(err, ErrCommand) {
		t.Fatalf("没有包成 ErrCommand: %v", err)
	}
	msg := err.Error()
	for _, want := range []string{
		filepath.Join(dir, DefaultCommand), // 找过装机目录，并且说出了具体路径
		"PATH",                             // 也找过 PATH
		"register:",                        // 该往 config.yaml 里加哪一段
		"command:",
		"restart htweb", // 改完要重启
	} {
		if !strings.Contains(msg, want) {
			t.Errorf("报错里缺少 %q：%s", want, msg)
		}
	}
}

// 没配 CommandDir（比如 media.root 也空着）时不能崩，退回纯 PATH 查找。
func TestEmptyInstallDirFallsBackToPath(t *testing.T) {
	root := t.TempDir()
	fakeBin(t, root, DefaultCommand, 0o755)
	t.Setenv("PATH", root)

	s := New(nil, Options{})
	got, err := s.resolveCommand()
	if err != nil {
		t.Fatalf("PATH 里有却没找到: %v", err)
	}
	if got != filepath.Join(root, DefaultCommand) {
		t.Fatalf("找到的是 %s", got)
	}
}
