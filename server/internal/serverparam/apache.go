package serverparam

import (
	"bufio"
	"os"
	"regexp"
	"strconv"
	"strings"
)

// 这一页上的「web端口」和「sdk端口」**不在数据库里**。
//
// 现网实测三个值：web端口 80、离线端口 8901、sdk端口 99；而 serverbaseparam
// 里的 webport = 8886、rtspport = 8091，对不上 —— 因为这两个字段旧版根本不是从
// 库里取的，是从 Apache 配置里取的：
//
//	httpd.conf   LISTEN 80 / LISTEN 81 / LISTEN 99
//	             ServerName 192.168.2.159:80          ← web端口
//	httpd-vhosts *:80  → /var/www/html/oktw/public    （新版 PHP 前台）
//	             *:81  → /var/www/html/ok112          （旧版 PHP 后台）
//	             *:99  → /var/www/html/lumen/public   （Lumen 写的对外 API）
//	swagger1.json "host": "192.168.2.159:99"          ← sdk端口
//
// 旧版 do.php 保存时也是往这两处写的（`sed -i '241c ServerName ...'`、
// `sed -i '11c "host": ...'`），而 `webport` / `rtspport` 那两列它压根没 UPDATE。
// 旧版 swagger 文档里对这三项的措辞也印证了：
// `offlineport(离线端口) listen(网页端口) sdklistens(sdk端口)`。
//
// 与之相对，「离线端口」确实是数据库列 `offlineport`（现网 8901）：
// 旧版把它写进 `/etc/rsyncd.conf` 的 `port = N`，是主备同步用的 rsync 端口，
// 宿主上 8901 → a9000_rsync 容器 873，对得上。
//
// 新版**只读不写**这两个文件：改 Apache 端口要按行号 sed 配置文件（D-209），
// 一旦行数变化就会写坏 httpd.conf 让整个 Web 起不来，这个险不值得冒。
// 页面上它们置灰展示，真要改由运维直接改配置。

// Apache 是从旧系统配置文件里读出来的端口。读不到时字段为 0，
// 调用方回退到数据库里的同名列，并把 Err 原样带给界面。
type Apache struct {
	WebPort int   `json:"webPort"`
	SDKPort int   `json:"sdkPort"`
	Listens []int `json:"listens"`
	// Source 说明这两个值到底是从哪儿来的，界面直接显示给运维看。
	Source string `json:"source"`
	Err    string `json:"err,omitempty"`
}

var (
	reListen      = regexp.MustCompile(`(?i)^\s*listen\s+(\S+)`)
	reServerName  = regexp.MustCompile(`(?i)^\s*servername\s+(\S+)`)
	reSwaggerHost = regexp.MustCompile(`"host"\s*:\s*"([^"]+)"`)
)

// readApache 解析 httpd.conf 与 swagger1.json。
//
// 两个文件都可能不存在（比如新版被单独部署到没有旧系统的机器上），
// 那不是错误，只是「取不到」——所以这里从不返回 error，
// 只把原因写进 Apache.Err，让界面回退到数据库值并如实说明。
func readApache(confPath, swaggerPath string) Apache {
	a := Apache{Source: "httpd.conf / swagger1.json"}
	var notes []string

	if confPath == "" {
		notes = append(notes, "未配置 legacy.apache_conf")
	} else if f, err := os.Open(confPath); err != nil {
		notes = append(notes, "读 "+confPath+" 失败："+err.Error())
	} else {
		func() {
			defer f.Close()
			sc := bufio.NewScanner(f)
			for sc.Scan() {
				line := sc.Text()
				// 注释行要跳过：httpd.conf 里有 `#Listen 12.34.56.78:80` 这种示例，
				// 不跳过的话 web端口会被解析成 80 —— 恰好和真值一样，
				// 换台机器就悄悄错了。
				if strings.HasPrefix(strings.TrimSpace(line), "#") {
					continue
				}
				if m := reListen.FindStringSubmatch(line); m != nil {
					if p := portOf(m[1]); p > 0 {
						a.Listens = append(a.Listens, p)
					}
					continue
				}
				// ServerName 才是旧版保存 web端口时改的那一行
				if m := reServerName.FindStringSubmatch(line); m != nil && a.WebPort == 0 {
					a.WebPort = portOf(m[1])
				}
			}
		}()
	}

	if swaggerPath == "" {
		notes = append(notes, "未配置 legacy.swagger_file")
	} else if raw, err := os.ReadFile(swaggerPath); err != nil {
		notes = append(notes, "读 "+swaggerPath+" 失败："+err.Error())
	} else if m := reSwaggerHost.FindSubmatch(raw); m != nil {
		a.SDKPort = portOf(string(m[1]))
	}

	// 兜底：ServerName 不带端口时取第一个 LISTEN；swagger 读不到时取最后一个 LISTEN
	// （现网 LISTEN 依次是 80 / 81 / 99，最后一个正是 Lumen API 那个 vhost）。
	if a.WebPort == 0 && len(a.Listens) > 0 {
		a.WebPort = a.Listens[0]
	}
	if a.SDKPort == 0 && len(a.Listens) > 0 {
		a.SDKPort = a.Listens[len(a.Listens)-1]
	}
	a.Err = strings.Join(notes, "；")
	return a
}

// portOf 从 "80" / "192.168.2.159:99" / "12.34.56.78:80" 里取出端口。
func portOf(s string) int {
	if i := strings.LastIndex(s, ":"); i >= 0 {
		s = s[i+1:]
	}
	p, err := strconv.Atoi(strings.TrimSpace(s))
	if err != nil || p < 1 || p > 65535 {
		return 0
	}
	return p
}
