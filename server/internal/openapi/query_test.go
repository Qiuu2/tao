package openapi

import (
	"encoding/json"
	"reflect"
	"strconv"
	"strings"
	"testing"

	"htweb/internal/auth"
)

// exemodel 的位序是这套系统里最容易搞反的一件事，而搞反了**不报错**——
// 只是铃在错误的那天响。所以这里把它钉死。
//
// 权威依据是 ok112/Browse_active_task.php 里那条判「今天该不该响」的 SQL：
//
//	SUBSTRING(task.exemodel,
//	  CASE WHEN WEEKDAY(CURDATE())+2 = 8 THEN 1 ELSE WEEKDAY(CURDATE())+2 END, 1)
//
// MySQL 的 WEEKDAY() 是**周一 = 0** … 周日 = 6，SUBSTRING 从 1 数起。代入：
//
//	周一 → 位置 2   周二 → 3   …   周六 → 7   周日 → 8，被 CASE 折回 1
//
// 也就是 **位置 1（下标 0）是周日**，位置 2..7 是周一到周六。
// isoWeekdays 把它翻成外界通行的 1=周一 … 7=周日。
func TestISOWeekdays(t *testing.T) {
	cases := []struct {
		name     string
		exemodel string
		want     []int
	}{
		{"手动（后台永不自动触发）", "0000000", []int{}},
		{"每天", "1111111", []int{1, 2, 3, 4, 5, 6, 7}},
		{"只有周日", "1000000", []int{7}},
		{"只有周一", "0100000", []int{1}},
		{"只有周六", "0000001", []int{6}},
		{"周一到周五（现网 1001 早读预备铃）", "0111110", []int{1, 2, 3, 4, 5}},
		{"周末", "1000001", []int{6, 7}},
		{"长度不足按手动处理", "011", []int{}},
		{"空串按手动处理", "", []int{}},
	}
	for _, c := range cases {
		t.Run(c.name, func(t *testing.T) {
			got := isoWeekdays(c.exemodel)
			if !reflect.DeepEqual(got, c.want) {
				t.Fatalf("isoWeekdays(%q) = %v，期望 %v", c.exemodel, got, c.want)
			}
		})
	}
}

// Ref 是外部合同的一部分：写数字是编号、写字符串是名字。
// 字符串形式的纯数字也当编号收 —— 很多语言的 JSON 序列化会把 int64 写成字符串。
func TestRefUnmarshal(t *testing.T) {
	cases := []struct {
		in       string
		wantID   int64
		wantName string
		wantErr  bool
	}{
		{`12`, 12, "", false},
		{`"12"`, 12, "", false},
		{`"A101教室音箱"`, 0, "A101教室音箱", false},
		{`"  A101  "`, 0, "A101", false},
		{`null`, 0, "", false},
		// 名字**看起来**像负数时不当编号，当名字 —— 库里真有叫「-1号机」的话也能寻址
		{`"-1号机"`, 0, "-1号机", false},
		{`0`, 0, "", true},
		{`-3`, 0, "", true},
		{`{"id":1}`, 0, "", true},
	}
	for _, c := range cases {
		t.Run(c.in, func(t *testing.T) {
			var r Ref
			err := r.UnmarshalJSON([]byte(c.in))
			if c.wantErr {
				if err == nil {
					t.Fatalf("%s 期望报错，实际解析成 %+v", c.in, r)
				}
				return
			}
			if err != nil {
				t.Fatalf("%s 意外报错: %v", c.in, err)
			}
			if r.ID != c.wantID || r.Name != c.wantName {
				t.Fatalf("%s 解析成 {ID:%d Name:%q}，期望 {ID:%d Name:%q}",
					c.in, r.ID, r.Name, c.wantID, c.wantName)
			}
		})
	}
}

// pager 不能沿用界面那套「不认识的尺寸静默回落到 18」——
// 对开发者接口那是陷阱：传 30 变成 18，翻页时会漏数据且毫无察觉。
func TestPagerDoesNotSilentlyChangeSize(t *testing.T) {
	cases := []struct{ in, want int }{
		{0, 20},    // 没传 → 默认 20
		{30, 30},   // 界面那套不认识 30，这里必须原样保留
		{1, 1},     //
		{500, 200}, // 上限夹取，防止一次拉走整库
	}
	for _, c := range cases {
		p := ListQuery{PageSize: c.in}.pager()
		if p.PageSize != c.want {
			t.Fatalf("pageSize=%d 归一化成 %d，期望 %d", c.in, p.PageSize, c.want)
		}
	}
}

// hintNear 只给「相近的名字」当提示，绝不替调用方选中其中一个。
func TestHintNear(t *testing.T) {
	rows := []namedRow{{1, "A101教室音箱"}, {2, "A102教室音箱"}, {3, "广播室主话筒"}}
	if got := hintNear("A101", rows); got == "" {
		t.Fatal("「A101」应能提示出 A101教室音箱")
	}
	if got := hintNear("完全不沾边", rows); got != "" {
		t.Fatalf("不沾边的名字不该有提示，得到 %q", got)
	}
}

// 密钥的请求头名在两个包里各有一份常量：openapi.HeaderAPIKey（规格与文档用）
// 和 auth.HeaderAPIKey（中间件用）。不能合并 —— openapi 依赖 auth，
// 反过来 import 就成环了。
//
// 两处一旦不一致，症状很难查：规格和文档里写着一个头名，中间件认的是另一个，
// 集成方照着文档写，收到的却是「缺少 X-API-Key」。所以钉住它。
func TestHeaderNameMatchesAuthPackage(t *testing.T) {
	if HeaderAPIKey != auth.HeaderAPIKey {
		t.Fatalf("openapi.HeaderAPIKey = %q，auth.HeaderAPIKey = %q —— 两处必须一致",
			HeaderAPIKey, auth.HeaderAPIKey)
	}
}

// 每一条**有响应示例**的接口，都必须逐字段解释那个示例。
//
// # 为什么
//
// 光贴一段示例 JSON 是不够的：`"priority": 8` 里的 8 是什么？数字大就优先吗
// （不是，正好反过来）？`"weekdays": [7]` 的 7 是周日还是周六？
// 集成方猜错了**不会报错**，只会在错误的那天广播。
//
// 漏写 Returns 不会有任何症状 —— 平台上少一块，没人会发现。所以要有测试。
func TestEverySampleIsExplained(t *testing.T) {
	for _, g := range Catalog().Groups {
		for _, ep := range g.Endpoints {
			if ep.Sample == "" {
				continue
			}
			if len(ep.Returns) == 0 {
				t.Errorf("%s %s（%s）给了响应示例却没解释里面的字段",
					ep.Method, ep.Path, ep.Summary)
				continue
			}
			for _, f := range ep.Returns {
				if f.Name == "" || f.Desc == "" {
					t.Errorf("%s %s 的响应字段说明有空项：%+v", ep.Method, ep.Path, f)
				}
			}
		}
	}
}

// 取值对照表本身也要完整：每张表都得有字段名、标题、说明和至少两个取值。
//
// 只有一个取值的「对照表」说明没写全 —— 二值字段至少两条，
// 而一个字段如果真的只有一个可能取值，它就不需要对照表。
func TestCodeTablesAreComplete(t *testing.T) {
	tables := Catalog().Codes
	if len(tables) < 8 {
		t.Fatalf("取值对照表只有 %d 张，太少了 —— 反直觉的那几个（projectstate / "+
			"israndomplay / priority / exemodel）是不是漏了？", len(tables))
	}
	seen := map[string]bool{}
	for _, ct := range tables {
		if ct.Field == "" || ct.Title == "" || ct.Desc == "" {
			t.Errorf("对照表 %q 缺字段名/标题/说明", ct.Title)
		}
		if seen[ct.Field] {
			t.Errorf("对照表 %q 重复了", ct.Field)
		}
		seen[ct.Field] = true
		if len(ct.Values) < 2 {
			t.Errorf("对照表 %q 只有 %d 个取值 —— 对照表至少要两个，"+
				"只有一个取值的字段不需要对照表", ct.Field, len(ct.Values))
		}
		for _, v := range ct.Values {
			if v.Value == "" || v.Means == "" {
				t.Errorf("对照表 %q 有空的取值项：%+v", ct.Field, v)
			}
		}
	}
	// 这四个是**猜必然猜错**的，必须在表里，而且必须标红。
	for _, must := range []string{"projectstate", "israndomplay", "priority", "exemodel"} {
		if !seen[must] {
			t.Errorf("%q 不在取值对照表里 —— 它是反直觉的，猜错了不报错、只会播错", must)
		}
	}
	for _, ct := range tables {
		switch ct.Field {
		case "projectstate", "israndomplay", "priority", "exemodel":
			warned := false
			for _, v := range ct.Values {
				if v.Warn {
					warned = true
				}
			}
			if !warned {
				t.Errorf("对照表 %q 一个 Warn 都没标 —— 它正是要提醒人别猜的那种", ct.Field)
			}
		}
	}
}

// 新建任务的接口必须覆盖 task.Input 里**每一个**业务字段。
//
// # 为什么写这个
//
// 用户的原话是「任务不是有很多字段变量在存吗？为什么我看实例不对呢」——
// 当时接口只开放了 17 个概念，而 task.Input 有 23 个字段：间隔播放、
// 当天停用、发送模式、本地优先、LED 字幕、终端区域掩码全漏了。
//
// 漏掉一个字段**没有任何症状**：接口照常返回 200，任务也建出来了，
// 只是那一项永远是默认值。集成方发现不了，我们也发现不了。
// 所以把「覆盖完整」变成一件测试能查的事。
//
// 这里比对的是**字段说明**（平台和文档都从它生成），而不是 Go 结构体 ——
// 结构体里有了但没写进说明，对集成方而言仍然等于不存在。
func TestCreateTaskCoversEveryBackendField(t *testing.T) {
	// task.Input 里每个业务字段，对应到接口里应当出现的那个名字。
	// 左边是后端字段，右边是接口字段名（用它在说明里搜）。
	want := map[string]string{
		"TaskName":     "name",
		"FolderID":     "folder",
		"Media":        "media",
		"Terminals":    "terminals",
		"StartDate":    "startDate",
		"EndDate":      "endDate",
		"PlayTime":     "playTime",
		"EndTime":      "endTime",
		"ExeModel":     "weekdays",
		"DisableDay":   "disableDay",
		"TimeLength":   "seconds",
		"TimeLengthTy": "loopTimes",
		"IntervalS":    "interval.everySeconds",
		"IntPlayLen":   "interval.playSeconds",
		"IntPlayLenTy": "interval.playTimes",
		"Volume":       "volume",
		"Priority":     "priority",
		"PrePower":     "prePower",
		"ProjectState": "enabled",
		"IsRandomPlay": "sequential",
		"DataSendMode": "multicast",
		"LocalPlay":    "localFirst",
		"LED":          "led",
	}

	var create *Endpoint
	for _, g := range Catalog().Groups {
		for i, ep := range g.Endpoints {
			if ep.ID == "tasks.create" {
				create = &g.Endpoints[i]
			}
		}
	}
	if create == nil {
		t.Fatal("目录里找不到 tasks.create")
	}

	documented := map[string]bool{}
	for _, f := range create.Fields {
		documented[f.Name] = true
	}
	for backend, api := range want {
		if !documented[api] {
			t.Errorf("task.Input.%s 对应的接口字段 %q 没写进「请求体字段」——"+
				"集成方看不到它，等于这个功能不存在", backend, api)
		}
	}

	// 示例里也要把主要字段摆出来。只给三四个字段的「示例」会让人以为
	// 接口就只有那么几项 —— 这正是这轮修改的起因。
	for _, must := range []string{"name", "media", "terminals", "playTime",
		"weekdays", "seconds", "volume", "interval", "led"} {
		if !strings.Contains(create.Body, `"`+must+`"`) {
			t.Errorf("新建任务的请求体示例里没有 %q —— 示例要能代表接口的全貌", must)
		}
	}
}

// 任务详情要能把新建时填的东西**读回来**，否则集成方没法核对自己填对没有。
func TestTaskDetailMirrorsCreateFields(t *testing.T) {
	var detail *Endpoint
	for _, g := range Catalog().Groups {
		for i, ep := range g.Endpoints {
			if ep.ID == "tasks.get" {
				detail = &g.Endpoints[i]
			}
		}
	}
	if detail == nil {
		t.Fatal("目录里找不到 tasks.get（任务详情）——" +
			"没有它，集成方建完任务只能拿到一个 id，没法确认填的东西写进去没有")
	}
	for _, must := range []string{"interval", "disableDay", "led", "area",
		"multicast", "localFirst", "weekdays"} {
		if !strings.Contains(detail.Sample, must) {
			t.Errorf("任务详情的响应示例里没有 %q —— 新建时能填的，详情就该能读回来", must)
		}
	}
}

// findEndpoint 在目录里按 ID 找一条接口。
func findEndpoint(t *testing.T, id string) Endpoint {
	t.Helper()
	for _, g := range Catalog().Groups {
		for _, ep := range g.Endpoints {
			if ep.ID == id {
				return ep
			}
		}
	}
	t.Fatalf("目录里找不到 %s", id)
	return Endpoint{}
}

// 「修改任务」必须把新建时能填的每一个字段都列出来。
//
// # 为什么写这个
//
// 用户的原话是「还有修改任务这是什么呀？如果我要修改任务时长怎么办」——
// 当时这条接口一个字段说明都没有，只有一段 {"volume": 55}。
// 对接方看到那一段，合理的结论是「这个接口只能改音量」，
// 于是想改时长就只能删掉重建：任务编号丢了，删和建之间还有一段空窗。
//
// 新建能填的，修改都能改。这件事必须由测试保证，因为**漏一个字段没有症状**：
// 接口照常工作，只是平台上看不见它。
func TestUpdateTaskDocumentsEveryCreatableField(t *testing.T) {
	create := findEndpoint(t, "tasks.create")
	update := findEndpoint(t, "tasks.update")

	has := map[string]bool{}
	for _, f := range update.Fields {
		has[f.Name] = true
		if f.Desc == "" {
			t.Errorf("修改任务的字段 %q 没有说明", f.Name)
		}
		if f.Required {
			t.Errorf("修改任务的 %q 标成了必填 —— 改任务是「只写要改的」，"+
				"没有必填字段；标了必填会让人以为每次都得把它带上", f.Name)
		}
	}
	for _, f := range create.Fields {
		if !has[f.Name] {
			t.Errorf("新建任务里有 %q，修改任务里没有 —— 它其实是能改的，"+
				"漏在这儿会让人以为只能删掉重建", f.Name)
		}
	}
}

// 「怎么改时长」必须有一段能照抄的示例。
//
// 这是用户直接问出来的那个问题。答案是一行 {"seconds": 900}，
// 但它得**出现在平台上**，而不是只存在于某个人的脑子里。
func TestUpdateTaskShowsHowToChangeDuration(t *testing.T) {
	update := findEndpoint(t, "tasks.update")
	if len(update.Examples) < 5 {
		t.Fatalf("修改任务只有 %d 段场景示例 —— 全字段可选的接口靠一段示例说不清，"+
			"改时长、改音量、换终端各是一段", len(update.Examples))
	}
	var duration bool
	for _, e := range update.Examples {
		if strings.Contains(e.Body, `"seconds"`) {
			duration = true
		}
		if e.Title == "" || e.Desc == "" || e.Body == "" {
			t.Errorf("场景示例有空项：%+v", e)
		}
	}
	if !duration {
		t.Error(`修改任务没有一段「改时长」的示例 —— 用户问的就是这个`)
	}
	// 改时长会连带重算结束时刻，这件事不写出来就是个坑：
	// 任务会变成「时长 15 分钟、结束时刻还写着 10 分钟那会儿」。
	joined := strings.Join(update.Notes, "\n")
	if !strings.Contains(joined, "endTime") {
		t.Error("修改任务没说清改时长时 endTime 会怎么样")
	}
}

// 每一段示例请求体都必须是**真的 JSON**。
//
// 示例是拿来照抄的。抄下来发不出去的示例比没有示例更坏 ——
// 对接方会先怀疑自己，再怀疑接口，最后才怀疑文档。
func TestEveryExampleBodyIsValidJSON(t *testing.T) {
	for _, g := range Catalog().Groups {
		for _, ep := range g.Endpoints {
			if ep.Body != "" {
				var v any
				if err := json.Unmarshal([]byte(ep.Body), &v); err != nil {
					t.Errorf("%s（%s）的请求体示例不是合法 JSON：%v", ep.ID, ep.Summary, err)
				}
			}
			for _, e := range ep.Examples {
				var v any
				if err := json.Unmarshal([]byte(e.Body), &v); err != nil {
					t.Errorf("%s 的场景示例「%s」不是合法 JSON：%v", ep.ID, e.Title, err)
				}
			}
			if ep.Sample != "" {
				var v any
				if err := json.Unmarshal([]byte(ep.Sample), &v); err != nil {
					t.Errorf("%s（%s）的响应示例不是合法 JSON：%v", ep.ID, ep.Summary, err)
				}
			}
		}
	}
}

// 能用编号寻址的地方，说明里必须**把编号写在前面**。
//
// # 为什么
//
// 用户的原话是「能用id的要用id，id是唯一的」。这不是偏好问题：
// media.name 和 terminal.terminalname 在库里**没有唯一索引**（核过 SHOW INDEX），
// 应用层也只对分区名、任务分组名、媒体目录名做了重名拦截。
// 也就是说终端和媒体真的可以重名，而重名带来的报错会在对接方上线之后
// 才第一次出现 —— 那时候没人记得当初是照着一段用名字的示例抄的。
//
// 作息方案是唯一的反例：它没有编号，只能用名字，这一点也要说出来。
func TestIDAddressingIsRecommended(t *testing.T) {
	// 这些接口的这些字段，说明里必须出现「编号」。
	want := map[string][]string{
		"tasks.create": {"media", "terminals", "zones", "folder"},
		"tasks.update": {"media", "terminals", "zones", "folder"},
		"play.start":   {"media", "terminals", "zones"},
	}
	for id, fields := range want {
		ep := findEndpoint(t, id)
		desc := map[string]string{}
		for _, f := range ep.Fields {
			desc[f.Name] = f.Desc
		}
		for _, name := range fields {
			d, ok := desc[name]
			if !ok {
				t.Errorf("%s 没有字段 %q", id, name)
				continue
			}
			if !strings.Contains(d, "编号") {
				t.Errorf("%s 的 %q 说明里没提编号 —— 名字不唯一，"+
					"照着名字对接的人会在上线之后才撞上重名：%s", id, name, d)
			}
		}
	}

	// 按编号寻址的路径参数，Example 要给一个编号而不是留空或给名字。
	for _, id := range []string{"tasks.get", "tasks.update", "tasks.delete"} {
		ep := findEndpoint(t, id)
		for _, p := range ep.Params {
			if p.Name != "ref" {
				continue
			}
			if !strings.Contains(p.Desc, "编号") {
				t.Errorf("%s 的 ref 说明里没提编号：%s", id, p.Desc)
			}
			if _, err := strconv.Atoi(p.Example); err != nil {
				t.Errorf("%s 的 ref 预填值是 %q —— 应该给一个编号，"+
					"「试一试」里预填什么，对接方就照着写什么", id, p.Example)
			}
		}
	}

	// 作息方案没有编号，这件事必须写在它的路径参数上，
	// 否则「能用 id 就用 id」这条规矩到这儿会让人以为是我们漏了。
	for _, id := range []string{"schedules.get", "schedules.state", "schedules.delete"} {
		ep := findEndpoint(t, id)
		for _, p := range ep.Params {
			if p.Name == "name" && !strings.Contains(p.Desc, "没有编号") {
				t.Errorf("%s 的 name 参数没说清「方案没有编号」", id)
			}
		}
	}
}

// 写任务的回执必须把**服务端替调用方定下来的**那些值回出来。
//
// # 为什么
//
// 用户的原话是「修改任务的响应示例是什么」——他在平台上找不到，
// 而找到了也看不出什么：原来的回执只有 {id, mediaCount, terminalCount}。
//
// 这几个接口有一批字段是服务端自己决定的：没给 endTime 就按开播时刻 + 时长算，
// 没给时长就按媒体总长算，没给 priority 就取最低一档。其中最要命的是
// **改时长会连带重算结束时刻** —— 回执不说，调用方要等到某天发现广播
// 比预期多响了五分钟才知道。
//
// 所以回执里必须有它们，示例里也必须看得见。
func TestTaskWriteReceiptShowsServerDecisions(t *testing.T) {
	// 服务端可能自作主张的那几项。
	must := []string{"endTime", "seconds", "priority", "volume", "folderId"}
	for _, id := range []string{"tasks.create", "tasks.update"} {
		ep := findEndpoint(t, id)
		for _, f := range must {
			if !strings.Contains(ep.Sample, `"`+f+`"`) {
				t.Errorf("%s 的响应示例里没有 %q —— 它是服务端替调用方定的值，"+
					"回执不给出来，对方没有别的办法知道", id, f)
			}
		}
		// 逐字段说明也要跟上，而且 endTime 那一条要说清它可能是算出来的。
		desc := map[string]string{}
		for _, r := range ep.Returns {
			for _, name := range strings.Split(r.Name, " / ") {
				desc[strings.TrimSpace(name)] = r.Desc
			}
		}
		for _, f := range must {
			if _, ok := desc[f]; !ok {
				t.Errorf("%s 的响应示例里有 %q，却没有对应的字段说明 ——"+
					"贴一段 JSON 不解释，等于让人猜", id, f)
			}
		}
		if !strings.Contains(desc["endTime"], "算") {
			t.Errorf("%s 的 endTime 说明没提它可能是服务端算出来的：%s", id, desc["endTime"])
		}
	}
}

// 每个会写终端清单的接口，都得说清「分区号不用你传」。
//
// # 为什么
//
// 用户的原话是「我看接口调用平台的新建任务没有传 groupid 呀」。
// 他是对的：请求体里确实没有这个参数。而库里 terminaloftask 存着一个分区号，
// 后台下发时读的就是它 —— 一个知道这件事的人看到请求体里没有它，
// 合理的怀疑就是「我漏传了，任务会播错」。
//
// 这个参数不存在是**有意的**（服务端按终端此刻的分区自己算，见
// task.FillGroupIDs）。但「有意省掉」和「忘了写」在页面上长得一模一样，
// 所以必须写出来。
func TestTerminalWritingEndpointsExplainZoneID(t *testing.T) {
	// 会往 terminaloftask 写行的接口。加了新的也要挂上这条说明。
	want := []string{"tasks.create", "tasks.update", "play.start", "schedules.create"}
	for _, id := range want {
		ep := findEndpoint(t, id)
		var found bool
		for _, n := range ep.Notes {
			if strings.Contains(n, "分区号") {
				found = true
			}
		}
		if !found {
			t.Errorf("%s（%s）会写终端清单，却没说清分区号不用调用方传 —— "+
				"看到请求体里没有它的人，只会以为自己漏传了", id, ep.Summary)
		}
	}

	// 反过来：只读的接口不该挂这条，否则满页都是同一句话，真正要看的反而被淹掉。
	for _, id := range []string{"tasks.list", "terminals.list", "tasks.get"} {
		ep := findEndpoint(t, id)
		for _, n := range ep.Notes {
			if strings.Contains(n, "没有分区号这个参数") {
				t.Errorf("%s 是只读接口，不该挂「分区号不用传」这条说明", id)
			}
		}
	}
}
