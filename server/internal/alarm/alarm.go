// Package alarm 实现报警管理（手册业务域八，F-38 ~ F-42）。
//
// 三级映射关系：报警主机的某个通道 → 报警分区 → 报警时播放的媒体。
//
// # 对旧系统的关键修复
//
//   - D-128 `ORDER BY ".trim($_GET['searchsequence'])." DESC` —— 无引号、无白名单，
//     是全站为数不多的**可直接利用的 SQL 注入点** → 白名单映射
//   - D-129 四表隐式内连接。媒体、分区或终端任一被删，映射行**在列表里直接消失**，
//     但记录仍在库里继续生效 —— 管理员看不到、后台却照样触发 → 全部改 LEFT JOIN
//     并把缺失项标出来（BR-190）
//   - D-130 `$sql."LIMIT $start,$NumOfPage"` 靠各分支结尾**碰巧有空格**才拼得出合法 SQL
//   - D-131 双查分页 / 分页链接丢筛选
//   - D-132 新增校验通道占用、修改**完全不校验** → 可以把 A 通道改成已占用的 B 通道，
//     同一主机同一通道出现两条映射，报警时行为不确定
//   - D-133 不校验 mediaid / firealarmgroupid 是否存在、也不校验主机是不是报警主机类型
//   - D-134 整条写入路径没有权限校验
//   - D-135 用 `LOCK TABLE alarmgroupmap WRITE` 而不是事务
//   - D-136 失败分支没有 else —— 出错时页面一片空白，没有任何提示
//   - 搜索分支整个丢掉了 `alarmarea.userid` 可见范围过滤（与 D-78 同型）：
//     普通用户平时只看得到自己的映射，一点搜索就能列出全系统的
//
// # 关于「变更后通知后台服务」（手册 D-137）
//
// **不发通知，因为旧系统里根本不存在 alarm 这种报文。**
// 把 ok112 全量扫一遍，send_socket 系列函数用到的 msg_type 只有六种：
// config / file / project / server / task / terminal。
// `alarmstart_msg` 是个只写着「保留使用」就 exit 的空壳。
// 手册说「应该发通知」是推测，没有对应的协议。凭空编一个 state 码
// 只会让后台服务收到无法解析的报文，比不发更糟。
// 与终端模块里回呼开关的处理方式一致：宁可留空，也不发明协议。
package alarm

import (
	"database/sql"
	"errors"
)

var (
	ErrNotFound     = errors.New("记录不存在")
	ErrNoPermission = errors.New("无权操作该报警分区")
	ErrChannelUsed  = errors.New("该报警主机的这个通道已经配置过映射了")
)

// TypeAlarmHost 是「报警主机」的终端类型。
//
// 旧代码在 setalarmkeymap.php 里写死 `terminal.typeid = '7'`。
// terminaltype 表里 id=7 的 name 就是「报警主机」，且是全表唯一
// switchcount > 0 的类型（16 路），两处对得上，沿用这个判定。
const TypeAlarmHost = 7

// NoArea 是 terminal.firealarmgroup 的「没有分区」取值。
//
// 取自列注释：`firealarmgroup int(11) DEFAULT '-1' COMMENT '只对有解码功能的终端有效-1表示没有分区'`。
// 现网 16 台终端该列全部是 -1，与注释一致。
//
// 注意旧代码里那行被注释掉的重置语句写的是 `SET firealarmgroup = '0'` ——
// 与列自己的约定不符。因为那行从未生效过，没有既成行为需要迁就，
// 这里按列注释用 -1。
const NoArea = -1

// AlarmMediaFolder 是报警媒体库的根目录 ID。
//
// 旧 setalarmkeymap.php 的媒体下拉限定在 folder 4 及其子孙目录内 ——
// 这条规则手册没写，但它是旧系统的实际行为，保留：报警音就该放在报警媒体库里。
// 旧查询把「三层子目录」硬编码成三个 OR 子查询（与 D-07 同型），
// 新版改成真正的递归收集，深度不受限。
const AlarmMediaFolder = 4

type Service struct {
	db *sql.DB
}

func New(db *sql.DB) *Service { return &Service{db: db} }
