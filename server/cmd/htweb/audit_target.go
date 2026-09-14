package main

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strconv"
	"strings"
)

// 让操作日志写出**具体动的是谁**。
//
// # 问题
//
// 中间件原来只写 auditLabels 里那个动作名：日志里一行「删除终端」，
// 追责时没有任何用处 —— 删的是哪一台？「修改任务」改的是哪个任务？
// 旧 PHP 也是这个毛病，新版没必要跟着。
//
// # 为什么在中间件里解析，而不是让每个 handler 自己记
//
// 让 handler 自己记要改一百多处，而且新加接口时没人会记得补 ——
// 漏了看不出来，只有出事要追责时才发现。集中在一张表里，
// 新接口漏配的后果是「日志里只有动作名」，而不是「日志里少了一行」。
//
// # 必须在 handler **之前**解析
//
// 删除类接口跑完之后那行记录就没了，事后再查名字只能查到空。
// 所以这一层在调用 handler 之前先把名字取出来，handler 成功了才写进日志。
//
// # body 会被读掉，要还回去
//
// 解析 body 里的 ids 需要把它读出来。读完必须把一份新的 Reader 装回 r.Body，
// 否则 handler 拿到的是个空 body —— 那是「日志功能把业务功能搞坏了」，
// 比不记日志严重得多。multipart 直接跳过：那可能是几十 MB 的上传。

// auditTarget 说明一个接口动的是谁，好让日志写得出对象名。
type auditTarget struct {
	// Noun 是对象的类别名，写进日志的前缀，如「终端」「任务」「终端分区」。
	Noun string
	// Table / IDCol / NameCol 决定去哪张表按 id 查名字。
	// Table 为空表示不查库（新建类接口，对象还不存在，名字直接取 BodyName）。
	Table   string
	IDCol   string
	NameCol string
	// PathID 是路径参数名（如 "id"）。
	PathID string
	// PathName 是「路径参数本身就是名字」的情况。开发者接口按名字寻址
	// （`PUT /openapi/v1/tasks/上午第一节`），路径里那一段就是对象名，
	// 根本不用查库 —— 这时填 PathName，别填 PathID。
	PathName string
	// BodyIDs 是 body 里可能装着 id 的字段，按顺序取第一个有值的。
	// 数字和数组都认（"id": 3 与 "ids": [1,2,3]）。
	BodyIDs []string
	// BodyName 是 body 里的名字字段，新建时用；查库拿不到名字时也用它兜底。
	BodyName []string
}

// auditTargets 按路由模式配「这个接口动的是谁」。
//
// ⚠ 没配的接口不会报错，只是日志里仍然只有动作名。新加写接口时顺手补一条。
var auditTargets = map[string]auditTarget{
	// —— 媒体与目录 ——
	"POST /api/folders":                  {Noun: "媒体目录"},
	"PUT /api/folders/{id}":              {Noun: "媒体目录", Table: "filefolder", IDCol: "id", NameCol: "name", PathID: "id"},
	"DELETE /api/folders":                {Noun: "媒体目录", Table: "filefolder", IDCol: "id", NameCol: "name"},
	"POST /api/folders/{id}/media:clear": {Noun: "媒体目录", Table: "filefolder", IDCol: "id", NameCol: "name", PathID: "id"},
	"DELETE /api/media":                  {Noun: "媒体", Table: "media", IDCol: "id", NameCol: "name"},

	// —— 地图 ——
	"POST /api/maps":                  {Noun: "底图"},
	"PUT /api/maps/{id}":              {Noun: "底图", Table: "map_image", IDCol: "id", NameCol: "name", PathID: "id"},
	"DELETE /api/maps/{id}":           {Noun: "底图", Table: "map_image", IDCol: "id", NameCol: "name", PathID: "id"},
	"POST /api/maps/{id}/image":       {Noun: "底图", Table: "map_image", IDCol: "id", NameCol: "name", PathID: "id"},
	"POST /api/maps/{id}/terminals":   {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname"},
	"DELETE /api/maps/{id}/terminals": {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname"},

	// —— 用户与用户组 ——
	// book_admin 的登录名列叫 username（不是 user；user 是 log 表里的列）
	"POST /api/usergroups":        {Noun: "用户组"},
	"PUT /api/usergroups/{id}":    {Noun: "用户组", Table: "usergroup", IDCol: "id", NameCol: "name", PathID: "id"},
	"DELETE /api/usergroups/{id}": {Noun: "用户组", Table: "usergroup", IDCol: "id", NameCol: "name", PathID: "id"},
	"POST /api/users":             {Noun: "用户"},
	"PUT /api/users/{id}":         {Noun: "用户", Table: "book_admin", IDCol: "id", NameCol: "username", PathID: "id"},
	"POST /api/users/{id}/enable": {Noun: "用户", Table: "book_admin", IDCol: "id", NameCol: "username", PathID: "id"},
	"DELETE /api/users":           {Noun: "用户", Table: "book_admin", IDCol: "id", NameCol: "username"},

	// —— 终端 ——
	"PUT /api/terminals/{id}":            {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname", PathID: "id"},
	"PUT /api/terminals/start":           {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname"},
	"PUT /api/terminals/stop":            {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname"},
	"PUT /api/terminals/volume":          {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname"},
	"PUT /api/terminals/password":        {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname"},
	"PUT /api/terminals/circuit-check":   {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname"},
	"PUT /api/terminals/sync-time":       {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname"},
	"PUT /api/terminals/toggle/{toggle}": {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname"},
	"DELETE /api/terminals":              {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname"},
	// 替换记的是**被换掉的那台**（sourceId），与界面上「把 A 换成 B」的 A 一致
	"PUT /api/terminals/replace": {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname", BodyIDs: []string{"sourceId"}},

	// 终端下面那几组子资源，记的都是**哪一台终端**的配置被动了
	"POST /api/terminals/{id}/shortcut-keys":       {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname", PathID: "id"},
	"POST /api/terminals/{id}/call-groups":         {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname", PathID: "id"},
	"DELETE /api/terminals/{id}/call-groups":       {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname", PathID: "id"},
	"POST /api/terminals/{id}/folders":             {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname", PathID: "id"},
	"DELETE /api/terminals/{id}/folders":           {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname", PathID: "id"},
	"POST /api/terminals/{id}/folders/terminals":   {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname", PathID: "id"},
	"DELETE /api/terminals/{id}/folders/terminals": {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname", PathID: "id"},
	"POST /api/terminals/{id}/quick-tasks":         {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname", PathID: "id"},
	"POST /api/terminals/{id}/quick-tasks/update":  {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname", PathID: "id"},
	"DELETE /api/terminals/{id}/quick-tasks":       {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname", PathID: "id"},

	"PUT /api/shortcut-keys/{keyId}": {Noun: "快捷键", Table: "terminalkey", IDCol: "id", NameCol: "name", PathID: "keyId"},
	"DELETE /api/shortcut-keys":      {Noun: "快捷键", Table: "terminalkey", IDCol: "id", NameCol: "name"},

	// —— 任务 ——
	"POST /api/tasks":                 {Noun: "任务"},
	"PUT /api/tasks/{id}":             {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname", PathID: "id"},
	"DELETE /api/tasks":               {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname"},
	"PUT /api/tasks/control/{action}": {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname"},
	"PUT /api/tasks/project-state":    {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname"},
	"PUT /api/tasks/emergency":        {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname"},
	"DELETE /api/tasks/emergency":     {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname"},
	"PUT /api/tasks/volume":           {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname"},
	"POST /api/tasks/{id}/copy":       {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname", PathID: "id"},
	"POST /api/tasks/sync-terminals":  {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname", BodyIDs: []string{"taskIds"}},
	// 任务分组存在 filetaskfree（名字是历史遗留，与「文件」无关）
	"POST /api/task-folders":        {Noun: "任务分组"},
	"PUT /api/task-folders/{id}":    {Noun: "任务分组", Table: "filetaskfree", IDCol: "id", NameCol: "name", PathID: "id"},
	"DELETE /api/task-folders/{id}": {Noun: "任务分组", Table: "filetaskfree", IDCol: "id", NameCol: "name", PathID: "id"},

	// 四种类别共用一套路由（终端功放 / 采播 / 文字语音 / LED），对象都在 task 表
	"POST /api/typed-tasks/{kind}":                 {Noun: "任务"},
	"PUT /api/typed-tasks/{kind}/{id}":             {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname", PathID: "id"},
	"PUT /api/typed-tasks/{kind}/control/{action}": {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname"},
	"PUT /api/typed-tasks/{kind}/project-state":    {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname"},
	"DELETE /api/typed-tasks/{kind}":               {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname"},

	// —— LED（目录在 ledtaskfree，设备在 leddevice）——
	"POST /api/led/folders":        {Noun: "LED目录"},
	"PUT /api/led/folders/{id}":    {Noun: "LED目录", Table: "ledtaskfree", IDCol: "id", NameCol: "name", PathID: "id"},
	"DELETE /api/led/folders/{id}": {Noun: "LED目录", Table: "ledtaskfree", IDCol: "id", NameCol: "name", PathID: "id"},
	"POST /api/led/folders:copy":   {Noun: "LED目录", Table: "ledtaskfree", IDCol: "id", NameCol: "name"},
	"POST /api/led/devices":        {Noun: "LED设备"},
	"PUT /api/led/devices/{id}":    {Noun: "LED设备", Table: "leddevice", IDCol: "id", NameCol: "name", PathID: "id"},
	"DELETE /api/led/devices":      {Noun: "LED设备", Table: "leddevice", IDCol: "id", NameCol: "name"},

	// —— 启用计划 ——
	// enabletask 自己没有名字列，一行就是「某个任务几点启用」，
	// 所以名字只能顺着 taskid 去 task 表取 —— 日志里写任务名才有意义。
	"POST /api/enable-plans":     {Noun: "启用计划"},
	"PUT /api/enable-plans/{id}": {Noun: "启用计划", Table: "enabletask e JOIN task t ON t.taskid = e.taskid", IDCol: "e.id", NameCol: "t.taskname", PathID: "id"},
	"DELETE /api/enable-plans":   {Noun: "启用计划", Table: "enabletask e JOIN task t ON t.taskid = e.taskid", IDCol: "e.id", NameCol: "t.taskname"},

	// —— 噪声 ——
	"POST /api/sound/devices":     {Noun: "噪声设备"},
	"PUT /api/sound/devices/{id}": {Noun: "噪声设备", Table: "sounddevice", IDCol: "id", NameCol: "name", PathID: "id"},
	"DELETE /api/sound/devices":   {Noun: "噪声设备", Table: "sounddevice", IDCol: "id", NameCol: "name"},
	"POST /api/sound/groups":      {Noun: "声场分区"},
	"PUT /api/sound/groups/{id}":  {Noun: "声场分区", Table: "soundgroupinfo", IDCol: "id", NameCol: "name", PathID: "id"},
	"DELETE /api/sound/groups":    {Noun: "声场分区", Table: "soundgroupinfo", IDCol: "id", NameCol: "name"},

	// —— 终端分区（serverplaystream，主键叫 streamid）——
	"POST /api/zones":     {Noun: "终端分区"},
	"PUT /api/zones/{id}": {Noun: "终端分区", Table: "serverplaystream", IDCol: "streamid", NameCol: "name", PathID: "id"},
	"DELETE /api/zones":   {Noun: "终端分区", Table: "serverplaystream", IDCol: "streamid", NameCol: "name"},

	// —— 节假日 ——
	"POST /api/holidays":      {Noun: "节假日"},
	"PUT /api/holidays/{id}":  {Noun: "节假日", Table: "holidaytime", IDCol: "id", NameCol: "name", PathID: "id"},
	"PUT /api/holidays/state": {Noun: "节假日", Table: "holidaytime", IDCol: "id", NameCol: "name"},
	"DELETE /api/holidays":    {Noun: "节假日", Table: "holidaytime", IDCol: "id", NameCol: "name"},

	// —— 遥控任务 ——
	// 界面上的「id」就是遥控器按键号 keyid，一个 keyid 有多行（一行一条任务），
	// 所以查名字要去重，见 lookupNames 里的 DISTINCT。
	"POST /api/remote-keys":     {Noun: "遥控按键"},
	"PUT /api/remote-keys/{id}": {Noun: "遥控按键", Table: "shortcutkeytask", IDCol: "keyid", NameCol: "keyname", PathID: "id"},
	"DELETE /api/remote-keys":   {Noun: "遥控按键", Table: "shortcutkeytask", IDCol: "keyid", NameCol: "keyname"},

	// —— 时间 ——
	"POST /api/time/sync": {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname"},
	"PUT /api/time/gps":   {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname"},

	// —— 报警 ——
	// alarmgroupmap 没有名字列，info 是这条映射的说明，界面上显示的就是它
	"POST /api/alarm-mappings":     {Noun: "报警映射"},
	"PUT /api/alarm-mappings/{id}": {Noun: "报警映射", Table: "alarmgroupmap", IDCol: "id", NameCol: "info", PathID: "id"},
	"DELETE /api/alarm-mappings":   {Noun: "报警映射", Table: "alarmgroupmap", IDCol: "id", NameCol: "info"},
	"POST /api/alarm-areas":        {Noun: "报警分区"},
	"PUT /api/alarm-areas/{id}":    {Noun: "报警分区", Table: "alarmarea", IDCol: "id", NameCol: "name", PathID: "id"},
	"DELETE /api/alarm-areas":      {Noun: "报警分区", Table: "alarmarea", IDCol: "id", NameCol: "name"},

	// —— 作息方案 ——
	// ⚠ 作息这一组接口**按名字寻址**，body 里根本没有 id（planName 就是主键）。
	// 所以一律不查库，直接把 planName 写进日志 —— 连打铃条目那几条也记方案名：
	// 「删除打铃条目：作息方案「春季作息」」比「删除打铃条目：条目#37」有用得多。
	"PUT /api/dashboard/tasks/disable-day": {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname"},
	"POST /api/bell-plans":                 {Noun: "作息方案"},
	"PUT /api/bell-plans":                  {Noun: "作息方案"},
	"DELETE /api/bell-plans":               {Noun: "作息方案"},
	"PUT /api/bell-plans/state":            {Noun: "作息方案"},
	"PUT /api/bell-plans/volume":           {Noun: "作息方案"},
	"POST /api/bell-plans/copy":            {Noun: "作息方案"},
	"POST /api/bell-plans/items":           {Noun: "作息方案"},
	"PUT /api/bell-plans/items/{id}":       {Noun: "作息方案"},
	"DELETE /api/bell-plans/items":         {Noun: "作息方案"},
	"PUT /api/bell-plans/items/schedule":   {Noun: "作息方案"},

	// —— 云广播 / 离线传输 ——
	// 这几条都是「对一批东西做同一件事」，日志要答的是「对哪一批」。
	// 云广播那一排按钮选的是终端，任务传送选的是任务，各记各的。
	"POST /api/cloud/bulk":    {Noun: "终端", Table: "terminal", IDCol: "id", NameCol: "terminalname"},
	"POST /api/transfer/bulk": {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname"},
	// 下发类记的是**发的是什么**（媒体 / 任务），发到哪些终端条数太多，列不下
	"POST /api/offline/media": {Noun: "媒体", Table: "media", IDCol: "id", NameCol: "name", BodyIDs: []string{"mediaIds"}},
	"POST /api/offline/tasks": {Noun: "任务", Table: "task", IDCol: "taskid", NameCol: "taskname", BodyIDs: []string{"taskIds"}},
	"PUT /api/offline/stop":   {Noun: "媒体", Table: "media", IDCol: "id", NameCol: "name", BodyIDs: []string{"mediaIds"}},

	// —— 备份 ——
	// 备份包不在库里，是磁盘上的文件，名字直接从请求体取
	"POST /api/backups":   {Noun: "备份包"},
	"DELETE /api/backups": {Noun: "备份包"},

	// —— 开发者接口的任务写操作 ——
	// 这一组按**名字**寻址（`PUT /openapi/v1/tasks/上午第一节`），
	// 路径里那一段就是对象名，不用查库。用密钥调的请求本来也没有会话，
	// 查库那条路走不通，正好用得上 PathName。
	"POST /openapi/v1/tasks":                  {Noun: "任务"},
	"PUT /openapi/v1/tasks/{ref}":             {Noun: "任务", PathName: "ref"},
	"DELETE /openapi/v1/tasks/{ref}":          {Noun: "任务", PathName: "ref"},
	"DELETE /openapi/v1/tasks":                {Noun: "任务"},
	"POST /openapi/v1/tasks/actions/{action}": {Noun: "任务"},

	// —— 开发者密钥 ——
	"POST /api/openapi-keys":           {Noun: "密钥"},
	"PUT /api/openapi-keys/{id}/state": {Noun: "密钥", Table: "api_key", IDCol: "id", NameCol: "name", PathID: "id"},
	"DELETE /api/openapi-keys/{id}":    {Noun: "密钥", Table: "api_key", IDCol: "id", NameCol: "name", PathID: "id"},
}

// defaultBodyIDKeys 是 body 里装 id 的常见字段名，没在 auditTargets 里显式配
// BodyIDs 的接口按这个顺序挨个试。
//
// 之所以给一份公共清单：各页面的请求体写法本来就不统一（有的 "ids": [3]，
// 有的 "id": 3），逐个接口去抄字段名既啰嗦又容易抄错，而抄错的后果
// 是「日志里那一栏悄悄空了」—— 没人会发现。
var defaultBodyIDKeys = []string{"ids", "id", "taskIds", "taskId", "terminalIds", "terminalId", "sourceId"}

// defaultBodyNameKeys 同理，是 body 里装名字的常见字段名。
// 新建类接口对象还不存在，名字只能从这里来。
var defaultBodyNameKeys = []string{"name", "planName", "taskname", "taskName", "username", "keyName", "terminalname", "lessonName", "newName", "info"}

// maxNamesInLog 日志里最多列几个名字，多的收成「等 N 个」。
// 列太多会把 operate 撑爆（列宽 varchar(255)），而且看的人也数不过来。
const maxNamesInLog = 3

// maxOperateRunes 是整条 operate 的字符上限，超了截断并补省略号。
// 60 个字在日志列表那一列里正好一行放得下，再长会被表格自己截掉，
// 那时候看到的是半截句子；主动截并补「…」，至少知道后面还有东西。
const maxOperateRunes = 60

// bodyPeekLimit 解析 body 时最多读多少。
// 正常的写请求 body 都是几百字节到几 KB；这个上限是防着有人往里灌大数组。
const bodyPeekLimit = 1 << 20

// auditDetail 在 handler **之前**算出「这次动的是谁」。
//
// 返回空串表示说不出来（没配、拿不到 id、查不到名字），
// 那就退回只写动作名 —— 少一点信息，但绝不能因此把请求搞坏。
// mayQueryDB 为 false 时不查库，只用路径参数和请求体里现成的东西 ——
// 没通过身份验证的请求走这一条，免得给外面留一条打库的路。
func (a *app) auditDetail(pattern string, r *http.Request, mayQueryDB bool) string {
	spec, ok := auditTargets[pattern]
	if !ok {
		return ""
	}

	// 路径里那一段本身就是名字（开发者接口按名字寻址），最省事，先看这个
	if spec.PathName != "" {
		if v := strings.TrimSpace(r.PathValue(spec.PathName)); v != "" {
			return spec.Noun + "「" + v + "」"
		}
	}

	body := peekBody(r)
	ids := spec.idsFrom(r, body)

	// 先按 id 查名字
	var names []string
	if mayQueryDB && spec.Table != "" && len(ids) > 0 {
		names = a.lookupNames(r, spec, ids)
	}
	// 查不到就用 body 里的名字（新建类接口对象还不存在，本来就只能这么来）
	if len(names) == 0 {
		if nm := firstString(body, spec.BodyName); nm != "" {
			names = []string{nm}
		} else if nm := firstString(body, defaultBodyNameKeys); nm != "" {
			names = []string{nm}
		}
	}
	// 名字一个都没有，但知道动了几个 id —— 起码把编号写上，比什么都不写强
	if len(names) == 0 {
		if len(ids) == 0 {
			return ""
		}
		return fmt.Sprintf("%s#%s", spec.Noun, joinIDs(ids))
	}

	quoted := make([]string, 0, maxNamesInLog)
	for i, n := range names {
		if i >= maxNamesInLog {
			break
		}
		quoted = append(quoted, "「"+n+"」")
	}
	out := spec.Noun + strings.Join(quoted, "")
	if n := len(ids); n > len(quoted) && n > 0 {
		out += fmt.Sprintf("等 %d 个", n)
	}
	return out
}

// idsFrom 按 spec 从路径参数或 body 里取 id。
func (t auditTarget) idsFrom(r *http.Request, body map[string]interface{}) []int64 {
	if t.PathID != "" {
		if v := r.PathValue(t.PathID); v != "" {
			if n, err := strconv.ParseInt(v, 10, 64); err == nil && n > 0 {
				return []int64{n}
			}
		}
	}
	for _, key := range t.BodyIDs {
		if ids := toIDs(body[key]); len(ids) > 0 {
			return ids
		}
	}
	// 显式配的没命中就试公共清单。显式配的优先：像终端替换那种 body 里
	// 同时有 sourceId 和 targetId 的接口，必须由 auditTargets 指定记哪一个。
	if len(t.BodyIDs) == 0 {
		for _, key := range defaultBodyIDKeys {
			if ids := toIDs(body[key]); len(ids) > 0 {
				return ids
			}
		}
	}
	return nil
}

// lookupNames 按 id 查名字，保持 ids 的顺序。
//
// 查不到的 id 直接跳过（可能已经被别的会话删了），不当成错误 ——
// 日志写不出名字是小事，为此让请求失败是大事。
func (a *app) lookupNames(r *http.Request, t auditTarget, ids []int64) []string {
	if a.st == nil || len(ids) == 0 {
		return nil
	}
	// 只查前几个：日志里本来也只列这么多，把一次批量操作的几百个 id 全查回来没意义
	if len(ids) > maxNamesInLog {
		ids = ids[:maxNamesInLog]
	}
	ph := strings.TrimSuffix(strings.Repeat("?,", len(ids)), ",")
	args := make([]interface{}, len(ids))
	for i, id := range ids {
		args[i] = id
	}
	// 表名与列名全部来自本文件里写死的 auditTargets，不是用户输入，可以拼。
	// DISTINCT 不是可有可无的：遥控按键那张表一个 keyid 对应多行（一行一条任务），
	// 不去重的话日志里会出现「遥控按键「7 键」「7 键」「7 键」」。
	rows, err := a.st.DB().QueryContext(r.Context(),
		`SELECT DISTINCT `+t.IDCol+`, COALESCE(`+t.NameCol+`,'') FROM `+t.Table+
			` WHERE `+t.IDCol+` IN (`+ph+`)`, args...)
	if err != nil {
		return nil
	}
	defer rows.Close()

	byID := map[int64]string{}
	for rows.Next() {
		var id int64
		var name string
		if err := rows.Scan(&id, &name); err != nil {
			return nil
		}
		byID[id] = strings.TrimSpace(name)
	}
	out := make([]string, 0, len(ids))
	for _, id := range ids {
		if n := byID[id]; n != "" {
			out = append(out, n)
		}
	}
	return out
}

// peekBody 把 JSON body 读出来解析，并把内容还回 r.Body 供 handler 使用。
//
// ⚠ 还回去这一步不能省。读完不还，handler 拿到的就是个空 body ——
// 那是「加了日志把业务搞坏了」，比不记日志严重得多。
func peekBody(r *http.Request) map[string]interface{} {
	if r.Body == nil {
		return nil
	}
	ct := r.Header.Get("Content-Type")
	// multipart 跳过：那可能是几十 MB 的上传，而且 id 不在 JSON 里
	if strings.HasPrefix(ct, "multipart/") {
		return nil
	}
	raw, err := io.ReadAll(io.LimitReader(r.Body, bodyPeekLimit))
	if err != nil {
		// 读坏了也要把已经读到的还回去，让 handler 自己去报格式错误
		r.Body = io.NopCloser(bytes.NewReader(raw))
		return nil
	}
	r.Body = io.NopCloser(bytes.NewReader(raw))
	if len(raw) == 0 {
		return nil
	}
	var m map[string]interface{}
	if json.Unmarshal(raw, &m) != nil {
		return nil
	}
	return m
}

// toIDs 把 body 里的一个字段读成 id 列表。数字、数字数组、字符串数字都认 ——
// 各页面的请求体写法不统一（有的传 "id": 3，有的传 "ids": [3]）。
func toIDs(v interface{}) []int64 {
	switch x := v.(type) {
	case float64:
		if x > 0 {
			return []int64{int64(x)}
		}
	case string:
		if n, err := strconv.ParseInt(strings.TrimSpace(x), 10, 64); err == nil && n > 0 {
			return []int64{n}
		}
	case []interface{}:
		out := make([]int64, 0, len(x))
		for _, it := range x {
			out = append(out, toIDs(it)...)
		}
		return out
	}
	return nil
}

func firstString(m map[string]interface{}, keys []string) string {
	for _, k := range keys {
		if s, ok := m[k].(string); ok {
			if s = strings.TrimSpace(s); s != "" {
				return s
			}
		}
	}
	return ""
}

func joinIDs(ids []int64) string {
	if len(ids) > maxNamesInLog {
		ids = ids[:maxNamesInLog]
	}
	parts := make([]string, len(ids))
	for i, id := range ids {
		parts[i] = strconv.FormatInt(id, 10)
	}
	return strings.Join(parts, ",")
}

// oneLine 把一条 operate 收成一行：去掉换行，超长截断补省略号。
//
// 日志列表那一列是单行显示（show-overflow-tooltip），超出部分本来就会被表格
// 截掉。主动截并补「…」的好处是：库里存的和界面看到的是同一句，
// 导出日志、直接查库时也一样读得懂，而不是一段被表格藏起来的长文本。
func oneLine(s string) string {
	s = strings.Join(strings.Fields(strings.ReplaceAll(s, "\n", " ")), " ")
	rs := []rune(s)
	if len(rs) <= maxOperateRunes {
		return s
	}
	return string(rs[:maxOperateRunes-1]) + "…"
}

// ---------- handler 回填对象名 ----------

// 有些接口 auditTargets 那套查不出对象名，只有 handler 自己知道：
// 上传媒体就是典型 —— body 是 multipart（中间件按设计跳过，那可能有几十 MB），
// 文件名要等 handler 把 multipart 解开才拿得到。
//
// 所以留一条从 handler 往回传的通道：中间件在调用 handler 之前往 context 里
// 放一个空盒子，handler 想补充就往里写，中间件写日志时优先用盒子里的内容。
//
// ⚠ 用指针盒子、不用 context.WithValue 存字符串，是因为 context 的值只能往下传 ——
// handler 里再 WithValue 一次，中间件手上那个 r 是看不见的（同 auditUser 那个坑）。

type auditNoteKeyT struct{}

var auditNoteKey auditNoteKeyT

type auditNote struct{ text string }

// withAuditNote 往请求上下文里放一个空盒子，返回新请求和盒子本身。
func withAuditNote(r *http.Request) (*http.Request, *auditNote) {
	n := &auditNote{}
	return r.WithContext(context.WithValue(r.Context(), auditNoteKey, n)), n
}

// noteAuditTarget 让 handler 补充/改写本次操作的对象名。
// 不在审计中间件里跑的接口调它是安全的空操作。
func noteAuditTarget(ctx context.Context, text string) {
	if text == "" {
		return
	}
	if n, ok := ctx.Value(auditNoteKey).(*auditNote); ok && n != nil {
		n.text = text
	}
}

// namesDetail 把一组名字拼成日志里的对象串，规则与 auditDetail 一致：
// 最多列 maxNamesInLog 个，多的收成「等 N 个」。
func namesDetail(noun string, names []string) string {
	if len(names) == 0 {
		return ""
	}
	var b strings.Builder
	b.WriteString(noun)
	for i, n := range names {
		if i >= maxNamesInLog {
			break
		}
		b.WriteString("「" + n + "」")
	}
	if len(names) > maxNamesInLog {
		fmt.Fprintf(&b, "等 %d 个", len(names))
	}
	return b.String()
}
