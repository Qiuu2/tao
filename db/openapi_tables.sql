-- 开发者接口的新增表（1 张）
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
