-- 开发者接口的新增表（2 张）
--
-- # 为什么要新建表
--
-- 开发者接口是**机器对机器**：第三方程序（告警平台、门禁、教务系统）
-- 直接调 htweb 下发广播。它拿不了界面那套凭据 ——
--
--   界面的令牌存在进程内存里（auth.Manager.sessions），8 小时过期，
--   htweb 一重启全部失效。人重新登录一下就好，程序不行：
--   服务半夜重启，第二天早上第三方的定时广播就全哑了，而且没人知道。
--
-- 所以要一份**落库的、长期有效的、能单独吊销的**凭据。
--
-- # 红线在哪
--
--   ✅ 可以新建表          （用户 2026-09-08 明确放开）
--   ❌ 不能改动任何现有表   （不加列、不改类型、不加索引 —— 原 R1 红线不变）
--
-- 表名以 api_ 开头，与旧库 89 张表和助手的 6 张表都不冲突。
--
-- # 权限不另起一套
--
-- 每把密钥挂在一个 book_admin 账号下（userid），调接口时**完全套用那个人的权限位**。
-- 这样不会出现「界面上没权限、接口却能干」这种口子 ——
-- 想给第三方多大权限，就在用户管理里给那个账号配多大，一处配置两处生效。
--
-- # 密钥本身不落库
--
-- 存的是 sha256，不是明文。库被拖走也不能拿去调接口。
-- 代价是**新建时那一次是唯一能看到明文的机会**，界面上会说清楚。
--
-- prefix 是明文的前 8 位，不是秘密 —— 它只用来在列表里认出「这是哪一把」，
-- 以及在日志里标记调用来源。没有它，界面上几把密钥长得一模一样，没法管。
--
-- # 字符集为什么是 utf8 而不是 utf8mb4
--
-- 与 assistant_tables.sql 同一个理由：htweb 的连接串写死 charset=utf8
-- （见 config.DSN 的注释，改不得），四字节字符在协议层就被截断了。
--
-- # 怎么执行
--
--   mysql -uroot audioserver < db/openapi_tables.sql
--
-- 建表要 DDL 权限，而 htweb 的运行账号**有意不给 DDL**。

SET NAMES utf8;

CREATE TABLE IF NOT EXISTS `api_key` (
  `id`           int(10) unsigned NOT NULL AUTO_INCREMENT,
  -- 给人看的备注，比如「教务系统」「消防告警平台」。列表里靠它认。
  `name`         varchar(64)      NOT NULL DEFAULT '',
  -- 明文密钥的前 8 位。不是秘密，只为在界面和日志里区分是哪一把。
  `prefix`       varchar(16)      NOT NULL DEFAULT '',
  -- 明文密钥的 sha256（64 位十六进制）。明文不存。
  `secret_hash`  char(64)         NOT NULL DEFAULT '',
  -- 归属账号（book_admin.id）。调接口时套用这个人的权限位。
  `userid`       int(10)          NOT NULL DEFAULT 0,
  -- 0 = 停用。停用比删除好：出了事先停，查清楚再决定删不删。
  `enabled`      tinyint(1)       NOT NULL DEFAULT 1,
  -- 到期时间。NULL = 不过期。
  `expiretime`   datetime                  DEFAULT NULL,
  -- 最后一次调用的时间与来源 IP。用来回答「这把密钥还有人在用吗」。
  `lastusedtime` datetime                  DEFAULT NULL,
  `lastusedip`   varchar(64)      NOT NULL DEFAULT '',
  `createtime`   timestamp        NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  -- prefix 唯一：认证时先按 prefix 定位到唯一一行，再比对 hash。
  -- 没有它就得全表扫着比 hash，密钥一多就慢，而且没法在日志里稳定标识来源。
  UNIQUE KEY `uk_api_key_prefix` (`prefix`),
  KEY `idx_api_key_user` (`userid`),
  KEY `idx_api_key_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='开发者接口密钥（新版新增，旧系统没有）';


-- ────────────────────────────────────────────────────────────
-- 2. api_play —— 立即播放的临时任务登记
--
-- 「现在把国歌播到操场」会在 task 表建一条临时任务并启动它。问题是事后
-- 要能找回来停掉、清掉 —— 而 task 表**不能加列**做标记（R1 红线），
-- 所以另起一张登记表记「哪些 taskid 是开发者接口临时建的」。
--
-- # 为什么不复用 assistant_runtime_play
--
-- 那张表是 AI 助手的，前端「助手时间线」直接读它。把接口建的临时任务
-- 混进去，用户会在助手的时间线里看到自己从没说过的播放记录，
-- 而且「停止播放」会把第三方系统正在放的东西停掉。
-- 两个来源、两份台账，各停各的。
--
-- # 没有登记会怎样
--
-- task 表里堆一串「立即播放_国歌_0908103012」这样的条目，几天后没人
-- 知道它们是什么、能不能删。有了登记，孤儿任务能按 state + starttime 清掉。
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `api_play` (
  `id`           bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  -- 临时任务在 task 表里的 id。停止和清理都按它找。
  `taskid`       int(11)       NOT NULL DEFAULT 0 COMMENT 'task.taskid',
  -- 播的是什么、播到哪 —— 回执和列表接口直接用，不必再去连表查。
  `medianame`    varchar(255)  NOT NULL DEFAULT '',
  `media_ids`    varchar(1024) NOT NULL DEFAULT '' COMMENT '逗号分隔',
  `terminal_ids` varchar(2048) NOT NULL DEFAULT '' COMMENT '逗号分隔',
  -- playing / stopped。stopped 的行保留，是为了能回答
  -- 「昨天下午三点是谁让操场响的」。
  `state`        varchar(16)   NOT NULL DEFAULT 'playing',
  -- 哪个账号、用哪把密钥发起的。追责时缺一不可 ——
  -- 只记账号的话，一个账号发了三把密钥给三个系统就分不清是谁。
  `userid`       int(10)       NOT NULL DEFAULT 0,
  `keyprefix`    varchar(16)   NOT NULL DEFAULT '',
  `starttime`    timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `endtime`      datetime               DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_api_play_state` (`state`, `starttime`),
  KEY `idx_api_play_taskid` (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='开发者接口立即播放的临时任务（新版新增，旧系统没有）';
