package serverparam

import (
	"fmt"
	"os"
	"path/filepath"
	"regexp"
	"strings"
)

// 改完服务器 IP / 网关之后，旧系统里那几个**记着同一个地址**的配置文件也要跟着改。
//
// 依据是旧版保存服务器基本信息时做的那一串 `cmdhost --cmd="sed -i …"`：
//
//	/etc/ha.d/haresources                          第 150 行  <服务器名> <ip>/<前缀>/eth0 ha-post
//	<a9000>/home/heartbeat/haresource              同上
//	<a9000>/home/graylog/config/graylog.conf       第 126 行  http_publish_uri  = http://<ip>:9001/
//	                                               第 140 行  http_external_uri = http://<ip>:9001/
//	<a9000>/html/htweb/ha-post.sh                  第 11 行   route add default gw <网关>
//	                                               改完再 cp 回 /etc/ha.d/
//	<a9000>/html/ok112/swagger-ui/dist/swagger1.json 第 11 行 "host": "<ip>:99",
//
// ⚠ 这两份的目录**不一样**，别顺手统一：
//
//   - ha-post.sh 跟着新前端走，在 `html/htweb/`（与 deploy/install.sh 的 WEB_DIR 一致）。
//     它是主备切换时要执行的脚本，归新版管。
//   - swagger1.json 仍在 `html/ok112/` —— 它是**旧版对外 API 的文档**，
//     由旧系统自己提供，新版只是在改服务器 IP 时把里面的 host 跟着改一下。
//     具体位置可由 config 的 legacy.swagger_file 覆盖。
//
// 这些值不跟着改的后果不是「界面不好看」：haresources 里那一行是主备切换时
// 要接管的虚拟 IP，graylog 那两行是日志系统对外报的地址，swagger 那一行是
// 对外 API 文档告诉第三方去连哪儿。改了机器地址却不改它们，等于留下三处
// 指向旧地址的配置，出问题时没人会想到来这儿看。
//
// # ⚠ 三处做法上的不同，都是为了不把文件改坏
//
//  1. **按内容定位，不按行号。** 旧版全是 `sed -i '150c …'`、`'126c …'` ——
//     文件多一行少一行就改到别的行上去了，而且是**静默**的：sed 照样返回 0。
//     这正是 D-209（按行号改 httpd.conf 把整站改趴）同一个毛病。
//     这里改成认那一行的特征（`http_publish_uri =`、`route add default gw`、
//     `"host":`…），找不到就报「没找到可改的行」，一个字节都不动。
//
//  2. **不经过 shell。** 旧版是 `escapeshellcmd("sed -i '…" . $ip . "…'")` 再
//     `cmdhost --cmd="…"` —— escapeshellcmd 拦不住把值拼进单引号里的写法。
//     这里全程用 Go 读写文件。
//
//  3. **前缀长度算全 32 位。** 旧版那个循环只跑 24 次、只数前三段
//     （`for($m=0;$m<24;$m++)`），所以 255.255.255.128 会被算成 /24 而不是 /25 ——
//     写进 haresources 就是一个错的虚拟 IP 网段。这里用与网卡那边同一个
//     maskToPrefix（标准库判连续性，0~32 全覆盖）。
//
// # 做不到就如实说
//
// 这些文件在纯新版部署的机器上可能根本不存在（没有旧系统），/etc/ha.d 又是
// root 的。所以每个文件单独报结果：改了 / 没找到文件 / 没找到可改的行 / 没权限。
// 一个文件失败不影响其它文件，也**绝不**让整次保存失败 ——
// 数据库那一步已经成了，把这里的失败算成保存失败只会让人以为什么都没做成。

// FileSyncStatus 是单个文件的处理结果。
type FileSyncStatus string

const (
	// SyncUpdated 改成功了。
	SyncUpdated FileSyncStatus = "updated"
	// SyncUnchanged 找到了那一行，但内容本来就对，没动。
	SyncUnchanged FileSyncStatus = "unchanged"
	// SyncMissing 文件不存在 —— 多半是这台机器没装旧系统，不是故障。
	SyncMissing FileSyncStatus = "missing"
	// SyncNoAnchor 文件在，但没找到该改的那一行。**不猜行号**，原样不动。
	SyncNoAnchor FileSyncStatus = "no-anchor"
	// SyncFailed 读写失败（多半是权限）。
	SyncFailed FileSyncStatus = "failed"
)

// FileSync 是一个文件的同步结果，回给界面逐条显示。
type FileSync struct {
	Path   string         `json:"path"`
	What   string         `json:"what"`
	Status FileSyncStatus `json:"status"`
	Detail string         `json:"detail,omitempty"`
}

// syncPaths 是要同步的那几个文件。集中在一处，测试里换成临时目录。
type syncPaths struct {
	HAResources    []string // /etc/ha.d/haresources 与 a9000 下那份
	HACf           string   // 读服务器名用
	GraylogConf    string
	HAPostLive     string // /etc/ha.d/ha-post.sh
	HAPostWork     string // <a9000>/html/htweb/ha-post.sh
	SwaggerFile    string
	DefaultSrvName string // ha.cf 里读不到 node 时退回这个（数据库里的服务器名）
}

func (s *Service) syncPathsFor(srvName string) syncPaths {
	root := strings.TrimSpace(s.a9000Root)
	p := syncPaths{
		HACf:           s.etc("/etc/ha.d/ha.cf"),
		HAPostLive:     s.etc("/etc/ha.d/ha-post.sh"),
		SwaggerFile:    s.swaggerFile,
		DefaultSrvName: srvName,
	}
	p.HAResources = []string{s.etc("/etc/ha.d/haresources")}
	if root != "" {
		// ⚠ 旧版这里写的是单数 haresource（同一段代码注释掉的那一行才是复数）。
		//   两个都试：哪个存在改哪个，都不存在就报 missing。
		p.HAResources = append(p.HAResources,
			filepath.Join(root, "home/heartbeat/haresource"),
			filepath.Join(root, "home/heartbeat/haresources"))
		p.GraylogConf = filepath.Join(root, "home/graylog/config/graylog.conf")
		p.HAPostWork = filepath.Join(root, "html/htweb/ha-post.sh")
	}
	return p
}

var (
	// haresources 那一行：`<服务器名> <ip>/<前缀>/eth0 ha-post`。
	// 认的是结尾的 `ha-post`（资源脚本名），它比服务器名稳定 ——
	// 服务器名这一页上就能改，拿它当锚点等于改完一次就再也找不到了。
	//
	// ⚠ 行尾用 [ \t]* 而不是 \s* —— \s 是**含 \n** 的，多行模式下
	//   `\s*$` 会把结尾那个换行（甚至后面的空行）一起吃掉，替换完两行就粘在一起了。
	//   这是实测踩出来的：haresources 改完最后少了个换行。
	reHAResource = regexp.MustCompile(`(?m)^[^#\n]*\bha-post[ \t]*$`)
	// graylog 的两个 URI
	reGraylogPublish  = regexp.MustCompile(`(?m)^\s*http_publish_uri\s*=.*$`)
	reGraylogExternal = regexp.MustCompile(`(?m)^\s*http_external_uri\s*=.*$`)
	// ha-post.sh 里的默认路由
	reDefaultRoute = regexp.MustCompile(`(?m)^\s*route\s+add\s+default\s+gw\s+.*$`)
	// swagger1.json 的 host（只换 IP，端口原样留着）
	reSwaggerHostLine = regexp.MustCompile(`(?m)^(\s*"host"\s*:\s*")([^"]*)(".*)$`)
	// ha.cf 里的 node 名
	// ⚠ 用 [ \t] 而不是 \s：\s 含 \n，多行模式下 `^\s*` 能跨行吃到下一行去，
	//   `node\s+` 也可能把换行当分隔符。节点名只会和 node 同一行。
	//
	// ⚠ 也不能只找 "node" 这个词：现网 ha.cf 第 210 行是
	//     #       node    nodename ...    -- must match uname -n
	//   一句带 node 的注释。所以必须锚在行首（# 开头的行匹配不上）。
	reHANode = regexp.MustCompile(`(?m)^[ \t]*node[ \t]+(\S+)`)
	// 从 `1.2.3.4:99` 里取端口
	reHostPort = regexp.MustCompile(`^(.*?)(:\d+)?$`)
)

// replaceLine 把文件里第一处匹配 re 的整行换成 line，原子写回。
//
// 返回 (是否真的改了, 状态, 说明)。
func replaceLine(path string, re *regexp.Regexp, line, what string) FileSync {
	out := FileSync{Path: path, What: what}
	if strings.TrimSpace(path) == "" {
		out.Status = SyncMissing
		out.Detail = "没有配置这个文件的路径"
		return out
	}
	raw, err := os.ReadFile(path)
	if os.IsNotExist(err) {
		out.Status = SyncMissing
		out.Detail = "文件不存在（这台机器可能没装旧系统）"
		return out
	}
	if err != nil {
		out.Status = SyncFailed
		out.Detail = "读取失败：" + err.Error()
		return out
	}

	loc := re.FindIndex(raw)
	if loc == nil {
		// ⚠ 到这一步旧版会去改第 150 行 / 第 126 行。这里什么都不做 ——
		//   把一行内容不明的东西覆盖掉，比不改糟得多。
		out.Status = SyncNoAnchor
		out.Detail = "没找到要改的那一行（不按行号猜，已原样保留）"
		return out
	}
	if string(raw[loc[0]:loc[1]]) == line {
		out.Status = SyncUnchanged
		return out
	}

	updated := append([]byte{}, raw[:loc[0]]...)
	updated = append(updated, []byte(line)...)
	updated = append(updated, raw[loc[1]:]...)

	if err := atomicWrite(path, updated); err != nil {
		out.Status = SyncFailed
		out.Detail = err.Error()
		return out
	}
	out.Status = SyncUpdated
	out.Detail = strings.TrimSpace(line)
	return out
}

// atomicWrite 先写同目录下的临时文件再 rename，保持原来的权限位。
//
// 直接 truncate 再写的话，写到一半断电或者权限不够，留下的是**半个**配置文件；
// haresources 半行会让主备切换在最需要它的时候失灵。
func atomicWrite(path string, data []byte) error {
	if err := atomicWriteDirect(path, data); err == nil {
		return nil
	} else if _, _, ok := canPrivWrite(path); !ok {
		// 走不通提权那条路就把原来的错回上去 —— 那才是人要看的原因
		return err
	}
	// /etc 下那几个 root 文件：交给装好的小脚本写（见 privwrite.go）。
	// 内容是上面算好的整份文件，脚本只负责原子落盘 + 体检。
	return privWrite(path, data)
}

func atomicWriteDirect(path string, data []byte) error {
	mode := os.FileMode(0o644)
	if st, err := os.Stat(path); err == nil {
		mode = st.Mode().Perm()
	}
	dir := filepath.Dir(path)
	tmp, err := os.CreateTemp(dir, ".htweb-sync-*")
	if err != nil {
		return fmt.Errorf("没有写权限或目录不可写：%v", err)
	}
	name := tmp.Name()
	defer func() { _ = os.Remove(name) }()

	if _, err := tmp.Write(data); err != nil {
		_ = tmp.Close()
		return fmt.Errorf("写入失败：%v", err)
	}
	if err := tmp.Close(); err != nil {
		return fmt.Errorf("写入失败：%v", err)
	}
	if err := os.Chmod(name, mode); err != nil {
		return fmt.Errorf("设置权限失败：%v", err)
	}
	if err := os.Rename(name, path); err != nil {
		return fmt.Errorf("替换文件失败：%v", err)
	}
	return nil
}

// nodeNameFrom 从 ha.cf 里读 `node <名字>`。
//
// 旧版是「取第 211 行再正则」，这里直接在整个文件里找第一条 node ——
// 行号是最不该依赖的东西，而 node 这个关键字本来就唯一。
// 读不到就用数据库里的服务器名（旧版没有这个退路，读不到就写一行空名字进去）。
func nodeNameFrom(path, fallback string) string {
	raw, err := os.ReadFile(path)
	if err == nil {
		if m := reHANode.FindSubmatch(raw); m != nil {
			if v := strings.TrimSpace(string(m[1])); v != "" {
				return v
			}
		}
	}
	return fallback
}

// syncLegacyFiles 按新的 IP / 网关把旧系统那几个文件刷一遍。
//
// 只在 IP 或网关真的变了时调用（见 Save）。返回逐个文件的结果，
// 任何一个失败都不影响其它文件，也不影响这次保存本身。
func (s *Service) syncLegacyFiles(in Input, srvName string) []FileSync {
	p := s.syncPathsFor(srvName)
	out := []FileSync{}

	// ── haresources：<服务器名> <ip>/<前缀>/eth0 ha-post ──
	prefix, perr := maskToPrefix(in.Network.SubnetMask)
	node := nodeNameFrom(p.HACf, p.DefaultSrvName)
	if perr != nil {
		out = append(out, FileSync{Path: strings.Join(p.HAResources, " / "),
			What: "主备资源行（haresources）", Status: SyncFailed,
			Detail: "掩码算不出前缀长度：" + perr.Error()})
	} else if node == "" {
		out = append(out, FileSync{Path: strings.Join(p.HAResources, " / "),
			What: "主备资源行（haresources）", Status: SyncFailed,
			Detail: "取不到服务器名（ha.cf 里没有 node，数据库里也是空的）"})
	} else {
		line := fmt.Sprintf("%s %s/%d/eth0 ha-post", node, in.Network.IP, prefix)
		for _, f := range p.HAResources {
			r := replaceLine(f, reHAResource, line, "主备资源行（haresources）")
			// a9000 下单数/复数两个名字只会有一个在，另一个 missing 不值得报
			if r.Status == SyncMissing && strings.Contains(f, "heartbeat") {
				continue
			}
			out = append(out, r)
		}
	}

	// ── graylog 的两个对外地址 ──
	if p.GraylogConf != "" {
		out = append(out,
			replaceLine(p.GraylogConf, reGraylogPublish,
				fmt.Sprintf("http_publish_uri = http://%s:9001/", in.Network.IP),
				"graylog 对外地址（http_publish_uri）"),
			replaceLine(p.GraylogConf, reGraylogExternal,
				fmt.Sprintf("http_external_uri = http://%s:9001/", in.Network.IP),
				"graylog 对外地址（http_external_uri）"))
	}

	// ── ha-post.sh 的默认路由 ──
	//
	// 旧版的来回是：先把 /etc/ha.d 里那份拷进工作目录、改、再拷回去。
	// 照做，好处是 html/htweb 下那份始终是线上那份的副本。
	out = append(out, s.syncHAPost(p, in.Network.Gateway)...)

	// ── swagger1.json 的 host：只换 IP，端口原样留着 ──
	if p.SwaggerFile != "" {
		out = append(out, replaceSwaggerHost(p.SwaggerFile, in.Network.IP))
	}
	return out
}

// syncHAPost 改 ha-post.sh 里的默认路由，并保持两份副本一致。
func (s *Service) syncHAPost(p syncPaths, gateway string) []FileSync {
	what := "默认路由（ha-post.sh）"
	line := "route add default gw " + gateway

	// 工作目录下没有副本时，直接改线上那份
	if p.HAPostWork == "" {
		return []FileSync{replaceLine(p.HAPostLive, reDefaultRoute, line, what)}
	}

	// 先把线上那份取过来当基准（取不到就用工作目录下现有的那份）
	if raw, err := os.ReadFile(p.HAPostLive); err == nil {
		_ = atomicWrite(p.HAPostWork, raw)
	}

	r := replaceLine(p.HAPostWork, reDefaultRoute, line, what)
	if r.Status != SyncUpdated && r.Status != SyncUnchanged {
		return []FileSync{r}
	}

	// 改好的那份放回 /etc/ha.d
	raw, err := os.ReadFile(p.HAPostWork)
	if err != nil {
		return []FileSync{r}
	}
	back := FileSync{Path: p.HAPostLive, What: what}
	// ⚠ 线上那份不存在就**不创建**：我们是在同步已有的配置，不是在装 heartbeat。
	//   没装旧系统的机器上 /etc/ha.d 整个目录都没有，那时候报「写不进去」
	//   会让人以为坏了，其实只是这台机器没这东西。
	if _, serr := os.Stat(p.HAPostLive); serr != nil {
		back.Status = SyncMissing
		back.Detail = "文件不存在（这台机器可能没装旧系统）"
		return []FileSync{r, back}
	}
	if werr := atomicWrite(p.HAPostLive, raw); werr != nil {
		back.Status = SyncFailed
		back.Detail = werr.Error()
	} else {
		back.Status = SyncUpdated
		back.Detail = line
	}
	return []FileSync{r, back}
}

// replaceSwaggerHost 把 `"host": "1.2.3.4:99"` 里的 IP 换掉，**端口原样留着**。
//
// 旧版是整行写死成 `"host": "<ip>:99",` —— 端口被硬编码成 99，
// 谁把 sdk 端口改过就会被这一下改回去。
func replaceSwaggerHost(path, ip string) FileSync {
	out := FileSync{Path: path, What: "对外 API 文档里的地址（swagger1.json）"}
	raw, err := os.ReadFile(path)
	if os.IsNotExist(err) {
		out.Status = SyncMissing
		out.Detail = "文件不存在（这台机器可能没装旧系统）"
		return out
	}
	if err != nil {
		out.Status = SyncFailed
		out.Detail = "读取失败：" + err.Error()
		return out
	}
	m := reSwaggerHostLine.FindSubmatchIndex(raw)
	if m == nil {
		out.Status = SyncNoAnchor
		out.Detail = `没找到 "host" 这一行（不按行号猜，已原样保留）`
		return out
	}
	old := string(raw[m[4]:m[5]])
	port := ""
	if mm := reHostPort.FindStringSubmatch(old); mm != nil {
		port = mm[2]
	}
	newHost := ip + port
	if newHost == old {
		out.Status = SyncUnchanged
		return out
	}
	updated := append([]byte{}, raw[:m[4]]...)
	updated = append(updated, []byte(newHost)...)
	updated = append(updated, raw[m[5]:]...)
	if err := atomicWrite(path, updated); err != nil {
		out.Status = SyncFailed
		out.Detail = err.Error()
		return out
	}
	out.Status = SyncUpdated
	out.Detail = `"host": "` + newHost + `"`
	return out
}
