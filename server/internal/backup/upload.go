package backup

import (
	"archive/zip"
	"context"
	"fmt"
	"io"
	"os"
	"path/filepath"
	"regexp"
	"strings"
	"time"
)

// 上传备份包。
//
// # 为什么需要
//
// 原来这一页只能「在本机备份 → 在本机还原」：下载下来的包、或者从另一台机器
// 拿来的包，没有任何途径传回去，也就还原不了。换机器、装新服务器、
// 从归档里取一个旧包回滚 —— 这三件事全都做不成。
//
// # 落地在哪
//
// 传上来的文件就落进备份目录（config 里的 backup.dir），落地之后它和
// 「本机自己备的包」完全等价：同一个列表、同一套还原前置检查、同一条恢复路径。
// 不新增任何存储、不碰数据库。
//
// # ⚠ 收下之前先验，别把垃圾丢进备份目录
//
// 顺序是：写临时文件 → 校验 → 原子改名落位。任何一步不过就删临时文件走人。
// 校验做四件事：
//
//	① 大小上限，挡住把磁盘塞满
//	② 必须是能打开的 zip
//	③ 包内必须有 _manifest.json 且解析得出来，formatVersion 认识
//	④ 包内条目路径不许有 .. 或绝对路径（zip slip）
//
// ⚠ **不在这里比对结构指纹**。指纹不一致的包照样收下 —— 它可能是从
//   另一台结构相同的机器来的、也可能确实不兼容。是否兼容由列表里那一列
//   `compatible` 显示，真正的拦截在 Precheck / Restore（BR-272）。
//   上传阶段就按指纹拒收，会让人连「包里到底是什么」都看不到。

// MaxUploadBytes 是备份包上传的大小上限。
//
// 现网整库 1.44MB + 媒体 78MB，一个满包约 80MB；给到 512MB 留足余量，
// 同时挡住「随手传个几个 G 的文件把备份分区塞满」。
const MaxUploadBytes int64 = 512 << 20

// reUploadName 限定上传后落地的文件名。比 reName 更严：这是我们自己拼的，
// 不接受用户提供的任何路径成分。
var reUploadName = regexp.MustCompile(`^[A-Za-z0-9._-]{1,120}$`)

// UploadResult 是上传结果。
type UploadResult struct {
	// Name 是落地后的文件名（可能与上传时的原名不同，见 uniqueName）。
	Name string `json:"name"`
	Size int64  `json:"size"`
	// Renamed 为 true 表示目录里已有同名包，落地时自动加了后缀。
	Renamed bool `json:"renamed"`
	// Item 是落地后这个包在列表里的样子，界面可以直接用，不必重新拉列表。
	Item *Item `json:"item"`
}

// Upload 收下一个上传的备份包。
//
// src 是上传流，origName 是浏览器给的原始文件名（只用来取一个体面的落地名，
// 其中的路径成分一律丢弃）。
func (s *Service) Upload(ctx context.Context, src io.Reader, origName string) (*UploadResult, error) {
	if err := os.MkdirAll(s.dir, 0o755); err != nil {
		return nil, fmt.Errorf("创建备份目录: %w", err)
	}

	// ⚠ 只取 basename，且把 Windows 的反斜杠也算成分隔符 ——
	//   filepath.Base 在 Linux 上不认 `\`，"..\\x.zip" 会被原样留下。
	base := origName
	if i := strings.LastIndexAny(base, `/\`); i >= 0 {
		base = base[i+1:]
	}
	base = strings.TrimSpace(base)
	if !strings.HasSuffix(strings.ToLower(base), ".zip") {
		return nil, fmt.Errorf("只接受 .zip 备份包")
	}
	if !reUploadName.MatchString(base) {
		return nil, fmt.Errorf("文件名只能包含字母、数字、点、下划线和短横线，且不超过 120 个字符")
	}

	tmp, err := os.CreateTemp(s.dir, ".upload-*.zip")
	if err != nil {
		return nil, fmt.Errorf("创建临时文件: %w", err)
	}
	tmpPath := tmp.Name()
	// 失败路径上一定要把临时文件清掉，否则备份目录里会攒一堆 .upload-xxx
	ok := false
	defer func() {
		tmp.Close()
		if !ok {
			_ = os.Remove(tmpPath)
		}
	}()

	// LimitReader 多给 1 字节：读满了说明超限，而不是刚好卡在上限
	n, err := io.Copy(tmp, io.LimitReader(src, MaxUploadBytes+1))
	if err != nil {
		return nil, fmt.Errorf("接收文件: %w", err)
	}
	if n > MaxUploadBytes {
		return nil, fmt.Errorf("备份包过大：上限 %s", humanSize(MaxUploadBytes))
	}
	if n == 0 {
		return nil, fmt.Errorf("上传的文件是空的")
	}
	if err := tmp.Close(); err != nil {
		return nil, fmt.Errorf("写入临时文件: %w", err)
	}

	if err := s.validatePackage(tmpPath); err != nil {
		return nil, err
	}

	name := s.uniqueName(base)
	dst := filepath.Join(s.dir, name)
	// 同一目录内的 rename 是原子的：列表里要么看不到，要么看到的就是完整的包
	if err := os.Rename(tmpPath, dst); err != nil {
		return nil, fmt.Errorf("保存备份包: %w", err)
	}
	if err := os.Chmod(dst, 0o644); err != nil {
		return nil, fmt.Errorf("设置权限: %w", err)
	}
	ok = true

	res := &UploadResult{Name: name, Size: n, Renamed: name != base}
	// 落地后按列表的口径再读一遍，界面直接拿去用
	if items, lerr := s.List(ctx); lerr == nil {
		for i := range items {
			if items[i].Name == name {
				res.Item = &items[i]
				break
			}
		}
	}
	return res, nil
}

// validatePackage 在收下之前检查这个 zip 到底是不是一个备份包。
func (s *Service) validatePackage(path string) error {
	zr, err := zip.OpenReader(path)
	if err != nil {
		return fmt.Errorf("不是有效的 zip 文件：%v", err)
	}
	defer zr.Close()

	// ⚠ zip slip：包内条目名带 ../ 或绝对路径时，解包会写到目录外。
	//   恢复媒体那一步是按包内路径落盘的，这里先把这种包挡掉。
	for _, f := range zr.File {
		n := f.Name
		if strings.HasPrefix(n, "/") || strings.HasPrefix(n, `\`) ||
			strings.Contains(n, "..") || filepath.IsAbs(n) {
			return fmt.Errorf("备份包内含非法路径 %q，已拒绝", n)
		}
	}

	m, err := s.readManifest(path)
	if err != nil {
		return fmt.Errorf("这不像一个本系统的备份包：%v", err)
	}
	if m.FormatVersion > FormatVersion {
		return fmt.Errorf("备份包格式版本 %d 高于本系统支持的 %d，请升级后再还原",
			m.FormatVersion, FormatVersion)
	}
	return nil
}

// uniqueName 在目录里已有同名文件时换一个名字，绝不覆盖已有的备份包。
//
// 覆盖是不可接受的：上传一个碰巧同名的包会把本机那份备份悄悄弄没。
func (s *Service) uniqueName(base string) string {
	if _, err := os.Stat(filepath.Join(s.dir, base)); os.IsNotExist(err) {
		return base
	}
	ext := filepath.Ext(base)
	stem := strings.TrimSuffix(base, ext)
	stamp := time.Now().In(s.loc).Format("20060102-150405")
	for i := 0; ; i++ {
		cand := fmt.Sprintf("%s-%s%s", stem, stamp, ext)
		if i > 0 {
			cand = fmt.Sprintf("%s-%s-%d%s", stem, stamp, i, ext)
		}
		if len(cand) > 120 {
			// 名字太长就退化成纯时间戳，仍然保证唯一
			cand = fmt.Sprintf("upload-%s-%d%s", stamp, i, ext)
		}
		if !reUploadName.MatchString(cand) {
			cand = fmt.Sprintf("upload-%s-%d%s", stamp, i, ext)
		}
		if _, err := os.Stat(filepath.Join(s.dir, cand)); os.IsNotExist(err) {
			return cand
		}
	}
}
