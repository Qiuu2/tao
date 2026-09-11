package serverparam

import (
	"os"
	"path/filepath"
	"strings"
	"testing"
)

func write(t *testing.T, dir, name, body string) string {
	t.Helper()
	p := filepath.Join(dir, name)
	if err := os.MkdirAll(filepath.Dir(p), 0o755); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(p, []byte(body), 0o644); err != nil {
		t.Fatal(err)
	}
	return p
}

func read(t *testing.T, p string) string {
	t.Helper()
	b, err := os.ReadFile(p)
	if err != nil {
		t.Fatal(err)
	}
	return string(b)
}

// graylog.conf：只换那两行，**别的行一个字节都不能动**。
//
// 旧版是 `sed -i '126c …'` 和 `'140c …'`；这个文件在不同版本里行数不一样，
// 改到别的行上去了也不会报错。这里认键名。
func TestSyncGraylog(t *testing.T) {
	dir := t.TempDir()
	body := `# graylog
is_master = true
node_id_file = /etc/graylog/server/node-id
http_bind_address = 0.0.0.0:9001
http_publish_uri = http://192.168.2.159:9001/
rotation_strategy = count
http_external_uri = http://192.168.2.159:9001/
elasticsearch_hosts = http://127.0.0.1:9200
`
	p := write(t, dir, "graylog.conf", body)

	r1 := replaceLine(p, reGraylogPublish, "http_publish_uri = http://10.0.0.5:9001/", "x")
	r2 := replaceLine(p, reGraylogExternal, "http_external_uri = http://10.0.0.5:9001/", "x")
	if r1.Status != SyncUpdated || r2.Status != SyncUpdated {
		t.Fatalf("两行都该改成功：%+v %+v", r1, r2)
	}

	got := read(t, p)
	for _, want := range []string{
		"http_publish_uri = http://10.0.0.5:9001/",
		"http_external_uri = http://10.0.0.5:9001/",
		"http_bind_address = 0.0.0.0:9001", // 名字相近，绝不能被连带改掉
		"elasticsearch_hosts = http://127.0.0.1:9200",
		"rotation_strategy = count",
	} {
		if !strings.Contains(got, want) {
			t.Errorf("结果里应当有 %q\n实际：\n%s", want, got)
		}
	}
	if strings.Contains(got, "192.168.2.159") {
		t.Errorf("旧地址没换干净：\n%s", got)
	}
}

// 找不到该改的那一行时，**一个字节都不能动** —— 这是与旧版按行号 sed 最要紧的区别。
func TestSyncNoAnchorLeavesFileAlone(t *testing.T) {
	dir := t.TempDir()
	body := "这个文件里根本没有 graylog 的那两行\n第二行\n第三行\n"
	p := write(t, dir, "graylog.conf", body)

	r := replaceLine(p, reGraylogPublish, "http_publish_uri = http://10.0.0.5:9001/", "x")
	if r.Status != SyncNoAnchor {
		t.Errorf("应当报 no-anchor，实际 %v", r.Status)
	}
	if got := read(t, p); got != body {
		t.Errorf("文件被改动了！\n原来：%q\n现在：%q", body, got)
	}
}

// 文件不存在不是故障：纯新版部署的机器上就没有旧系统这些文件。
func TestSyncMissingFile(t *testing.T) {
	r := replaceLine(filepath.Join(t.TempDir(), "nope.conf"), reGraylogPublish, "x", "y")
	if r.Status != SyncMissing {
		t.Errorf("应当报 missing，实际 %v（%s）", r.Status, r.Detail)
	}
}

// ha-post.sh 的默认路由。行首有缩进也要认出来。
func TestSyncDefaultRoute(t *testing.T) {
	dir := t.TempDir()
	p := write(t, dir, "ha-post.sh", `#!/bin/sh
### 注释
route add default gw 192.168.2.1
sed -i '0,/-/s/\(-\)\(.*\)/\1 192.168.2.159\/24/' /etc/netplan/00-installer-config.yaml
systemctl start ntp.service
`)
	r := replaceLine(p, reDefaultRoute, "route add default gw 10.0.0.1", "x")
	if r.Status != SyncUpdated {
		t.Fatalf("应当改成功：%+v", r)
	}
	got := read(t, p)
	if !strings.Contains(got, "route add default gw 10.0.0.1") {
		t.Errorf("网关没换：\n%s", got)
	}
	if !strings.Contains(got, "systemctl start ntp.service") {
		t.Errorf("后面的行被吃掉了：\n%s", got)
	}
}

// swagger 的 host：换 IP，**端口保持原样**。
//
// 旧版整行写死成 `"host": "<ip>:99",` —— 谁把 sdk 端口改过就会被改回 99。
func TestReplaceSwaggerHost(t *testing.T) {
	dir := t.TempDir()
	p := write(t, dir, "swagger1.json", `{
    "swagger": "2.0",
    "info": {
        "title": "Swagger Petstore"
    },
    "host": "192.168.2.159:99",
    "basePath": "/api"
}
`)
	r := replaceSwaggerHost(p, "10.0.0.5")
	if r.Status != SyncUpdated {
		t.Fatalf("应当改成功：%+v", r)
	}
	got := read(t, p)
	if !strings.Contains(got, `"host": "10.0.0.5:99"`) {
		t.Errorf("host 不对：\n%s", got)
	}
	if !strings.Contains(got, `"basePath": "/api"`) {
		t.Errorf("别的字段被动了：\n%s", got)
	}

	// 端口被改成别的值时也要留着，不能拽回 99
	p2 := write(t, dir, "swagger2.json", "{\n    \"host\": \"192.168.2.159:8099\"\n}\n")
	if r := replaceSwaggerHost(p2, "10.0.0.5"); r.Status != SyncUpdated {
		t.Fatalf("%+v", r)
	}
	if got := read(t, p2); !strings.Contains(got, `"host": "10.0.0.5:8099"`) {
		t.Errorf("端口被改掉了：\n%s", got)
	}
}

// 服务器名从 ha.cf 的 node 那一行读，不按行号；读不到才退回数据库里的名字。
func TestNodeNameFrom(t *testing.T) {
	dir := t.TempDir()
	p := write(t, dir, "ha.cf", `# heartbeat 配置
logfile /var/log/ha-log
keepalive 2
node    a9000-master
auto_failback on
`)
	if got := nodeNameFrom(p, "从库里来的名字"); got != "a9000-master" {
		t.Errorf("node 名 = %q，期望 a9000-master", got)
	}
	if got := nodeNameFrom(filepath.Join(dir, "nope.cf"), "从库里来的名字"); got != "从库里来的名字" {
		t.Errorf("读不到时应当退回数据库里的名字，实际 %q", got)
	}
}

// haresources 那一行：认结尾的 ha-post，不认服务器名 ——
// 服务器名这一页上就能改，拿它当锚点等于改过一次就再也找不到了。
func TestSyncHAResources(t *testing.T) {
	dir := t.TempDir()
	p := write(t, dir, "haresources", `# 主备资源
#node1 10.0.0.1/24/eth0 ha-post
old-name 192.168.2.159/8/eth0 ha-post
`)
	r := replaceLine(p, reHAResource, "a9000-master 10.0.0.5/24/eth0 ha-post", "x")
	if r.Status != SyncUpdated {
		t.Fatalf("应当改成功：%+v", r)
	}
	got := read(t, p)
	if !strings.Contains(got, "a9000-master 10.0.0.5/24/eth0 ha-post") {
		t.Errorf("没改对：\n%s", got)
	}
	// 注释行必须留着 —— 正则里排除了 # 开头
	if !strings.Contains(got, "#node1 10.0.0.1/24/eth0 ha-post") {
		t.Errorf("注释行被改掉了：\n%s", got)
	}
	if strings.Contains(got, "old-name") {
		t.Errorf("旧行还在：\n%s", got)
	}
}

// 原子写要保住原来的权限位：haresources 是 0600 的话，改完不能变成 0644。
func TestAtomicWriteKeepsMode(t *testing.T) {
	dir := t.TempDir()
	p := write(t, dir, "x.conf", "route add default gw 1.1.1.1\n")
	if err := os.Chmod(p, 0o600); err != nil {
		t.Fatal(err)
	}
	if r := replaceLine(p, reDefaultRoute, "route add default gw 2.2.2.2", "x"); r.Status != SyncUpdated {
		t.Fatalf("%+v", r)
	}
	st, err := os.Stat(p)
	if err != nil {
		t.Fatal(err)
	}
	if st.Mode().Perm() != 0o600 {
		t.Errorf("权限变成了 %o，应当还是 600", st.Mode().Perm())
	}
}

// 内容本来就对时不重写文件 —— 免得每次保存都动一遍 mtime。
func TestSyncUnchanged(t *testing.T) {
	dir := t.TempDir()
	p := write(t, dir, "ha-post.sh", "route add default gw 10.0.0.1\n")
	if r := replaceLine(p, reDefaultRoute, "route add default gw 10.0.0.1", "x"); r.Status != SyncUnchanged {
		t.Errorf("应当报 unchanged，实际 %v", r.Status)
	}
}

// 端到端走一遍 syncLegacyFiles：造一棵临时的 a9000 目录树，
// 确认四个文件都按新地址改好了，而且逐条结果都对得上。
//
// /etc/ha.d 下那两个是绝对路径，测试里碰不到 —— 它们会报 missing，
// 这恰好也是「纯新版部署、没装旧系统」那台机器上的真实结果。
func TestSyncLegacyFilesEndToEnd(t *testing.T) {
	root := t.TempDir()
	write(t, root, "home/heartbeat/haresource", "a9000 192.168.2.159/8/eth0 ha-post\n")
	write(t, root, "home/graylog/config/graylog.conf",
		"http_publish_uri = http://192.168.2.159:9001/\nhttp_external_uri = http://192.168.2.159:9001/\n")
	write(t, root, "html/ok112/ha-post.sh", "#!/bin/sh\nroute add default gw 192.168.2.1\n")
	swagger := write(t, root, "swagger1.json", "{\n    \"host\": \"192.168.2.159:99\"\n}\n")

	s := &Service{a9000Root: root, swaggerFile: swagger}
	in := Input{Network: Network{IP: "10.0.0.5", SubnetMask: "255.255.255.0", Gateway: "10.0.0.1"}}
	in.HA.Name = "a9000-master"

	got := s.syncLegacyFiles(in, in.HA.Name)

	byStatus := map[FileSyncStatus]int{}
	for _, f := range got {
		byStatus[f.Status]++
		if f.Status == SyncFailed || f.Status == SyncNoAnchor {
			t.Errorf("不该失败：%s %s → %s（%s）", f.What, f.Path, f.Status, f.Detail)
		}
	}
	if byStatus[SyncUpdated] < 4 {
		t.Errorf("至少该有四个文件被改（haresource / graylog×2 / ha-post / swagger），实际 %d\n%+v",
			byStatus[SyncUpdated], got)
	}

	// 逐个文件核对内容
	if v := read(t, filepath.Join(root, "home/heartbeat/haresource")); !strings.Contains(v, "a9000-master 10.0.0.5/24/eth0 ha-post") {
		t.Errorf("haresource 不对：%q", v)
	}
	if v := read(t, filepath.Join(root, "home/graylog/config/graylog.conf")); strings.Contains(v, "192.168.2.159") {
		t.Errorf("graylog 里还有旧地址：%q", v)
	}
	if v := read(t, filepath.Join(root, "html/ok112/ha-post.sh")); !strings.Contains(v, "route add default gw 10.0.0.1") {
		t.Errorf("ha-post.sh 网关不对：%q", v)
	}
	if v := read(t, swagger); !strings.Contains(v, `"host": "10.0.0.5:99"`) {
		t.Errorf("swagger host 不对：%q", v)
	}
}

// 掩码 /25 这种：旧版那个只跑 24 次的循环会算成 /24，写进 haresources 就是个错网段。
func TestSyncUsesFullPrefix(t *testing.T) {
	root := t.TempDir()
	write(t, root, "home/heartbeat/haresource", "old 1.1.1.1/8/eth0 ha-post\n")

	s := &Service{a9000Root: root}
	in := Input{Network: Network{IP: "10.0.0.5", SubnetMask: "255.255.255.128", Gateway: "10.0.0.1"}}
	in.HA.Name = "srv"
	s.syncLegacyFiles(in, in.HA.Name)

	v := read(t, filepath.Join(root, "home/heartbeat/haresource"))
	if !strings.Contains(v, "10.0.0.5/25/eth0") {
		t.Errorf("255.255.255.128 应当是 /25（旧版会算成 /24），实际：%q", v)
	}
}

// 替换不能吃掉行尾的换行 —— \s*$ 在多行模式下会把 \n 甚至后面的空行一起吞了，
// 结果是替换完两行粘在一起。实测踩过一次。
func TestReplaceKeepsTrailingNewline(t *testing.T) {
	dir := t.TempDir()
	p := write(t, dir, "haresources", "old 1.1.1.1/8/eth0 ha-post\n\n# 后面还有内容\n")
	if r := replaceLine(p, reHAResource, "new 2.2.2.2/24/eth0 ha-post", "x"); r.Status != SyncUpdated {
		t.Fatalf("%+v", r)
	}
	got := read(t, p)
	want := "new 2.2.2.2/24/eth0 ha-post\n\n# 后面还有内容\n"
	if got != want {
		t.Errorf("换行被吃掉了\n得到 %q\n期望 %q", got, want)
	}
}
