-- AI 助手的新增表（6 张）
--
-- # 为什么要新建表
--
-- 助手有一批必须持久化、又在旧库里无处可放的状态：会话上下文、指令历史、
-- 任务覆盖、临时播放登记、撤销凭据。原来那套 Python 实现把它们放在
-- backend/data/*.json 里，进程重启或多实例部署就会丢/打架。
--
-- # 红线在哪
--
--   ✅ 可以新建表          （用户 2026-09-08 明确放开）
--   ❌ 不能改动任何现有表   （不加列、不改类型、不加索引 —— 原 R1 红线不变）
--
-- 新表名一律以 assistant_ 开头，与旧库 89 张表零冲突，
-- 后台 C 服务不认识它们，也就不会去扫。
--
-- # 字符集为什么是 utf8 而不是 utf8mb4
--
-- htweb 的连接串写死 charset=utf8（见 config.DSN 的注释，改不得）。
-- 连接是 utf8 时，四字节字符在协议层就已经被截断，列声明成 utf8mb4 也救不回来。
-- 所以新表跟着用 utf8，四字节字符（emoji）在写入前由 Go 侧剔除。
--
-- # 怎么执行
--
--   mysql -uroot audioserver < db/assistant_tables.sql
--
-- 建表要 DDL 权限，而 htweb 的运行账号**有意不给 DDL**。
-- 所以这个脚本由管理员用另一个账号跑一次，不由程序自动执行。

SET NAMES utf8;

-- ────────────────────────────────────────────────────────────
-- 1. assistant_session —— 多轮会话的上下文
--
-- 指代消解（「刚才那个」）要上一轮的 intent/slots；
-- 确认流程（「确定吗」→「是」）要把 pending 跨轮存住；
-- 锁定指令要记住哪些槽位不许被后续轮次覆盖；
-- 失败提示升级要记连续听不懂了几次。
-- 一个前端会话一行，按 session_key 去重。
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `assistant_session` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_key` varchar(64)  NOT NULL COMMENT '前端生成的会话标识',
  `userid`      int(10)      NOT NULL DEFAULT 0 COMMENT 'book_admin.id',
  `last_intent` varchar(64)  NOT NULL DEFAULT '' COMMENT '上一轮意图，指代消解用',
  `last_slots`  text                  COMMENT '上一轮槽位 JSON',
  `locked`      text                  COMMENT '锁定指令 JSON，不许被后续轮次覆盖的槽位',
  `pending`     text                  COMMENT '待确认动作 JSON',
  `fail_count`  int(10)      NOT NULL DEFAULT 0 COMMENT '连续听不懂次数，提示升级用',
  `createtime`  timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatetime`  timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_session_key` (`session_key`),
  KEY `idx_updatetime` (`updatetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='AI助手会话上下文';

-- ────────────────────────────────────────────────────────────
-- 2. assistant_message —— 对话与指令历史
--
-- 前端「指令历史」面板读它（原 assistant_command_logs.json，上限 1000 条）。
-- ⚠ 不要往 log 表里塞：那张是操作日志，混进对话会污染审计。
--    助手执行的写操作照常另外走 tao 的审计中间件。
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `assistant_message` (
  `id`          bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_key` varchar(64)  NOT NULL DEFAULT '',
  `userid`      int(10)      NOT NULL DEFAULT 0,
  `username`    varchar(45)  NOT NULL DEFAULT '',
  `role`        varchar(16)  NOT NULL DEFAULT 'user' COMMENT 'user / assistant',
  `text`        text                  COMMENT '原文',
  `intent`      varchar(64)  NOT NULL DEFAULT '',
  `confidence`  float        NOT NULL DEFAULT 0,
  `slots`       text                  COMMENT '槽位 JSON',
  `action_log`  text                  COMMENT '这一轮实际执行了什么 JSON',
  `status`      varchar(16)  NOT NULL DEFAULT '' COMMENT 'ok / failed / pending',
  `createtime`  timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_time` (`userid`, `createtime`),
  KEY `idx_session` (`session_key`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='AI助手对话与指令历史';

-- ────────────────────────────────────────────────────────────
-- 3. assistant_setting —— 助手设置（键值）
--
-- 原 assistant_settings.json。scope=global 是全局设置，
-- scope=user 时按 userid 分开（每个人可以有自己的偏好）。
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `assistant_setting` (
  `id`         int(10) unsigned NOT NULL AUTO_INCREMENT,
  `scope`      varchar(32)  NOT NULL DEFAULT 'global' COMMENT 'global / user',
  `userid`     int(10)      NOT NULL DEFAULT 0 COMMENT 'scope=user 时有效',
  `name`       varchar(64)  NOT NULL,
  `value`      text,
  `updatetime` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_scope_user_name` (`scope`, `userid`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='AI助手设置';

-- ────────────────────────────────────────────────────────────
-- 4. assistant_task_override —— 任务覆盖（最要紧的一张）
--
-- 「取消春季方案今天的早读」这类**只影响某一天**的操作，旧库表达不了：
-- task 表只有一个 disableday（单个日期），而助手要支持多天、时段、
-- 以及「挪走之后原位置留空、新位置补一条」这种成对操作。
--
-- 原实现的办法是建**影子任务**再记在 task_overrides.json 里，
-- 过期后清理。这张表就是那份 JSON 的落库版：
-- source_task_ids 是被动的原任务，shadow_task_ids 是为实现例外新建的临时任务，
-- payload 存完整快照（原实现里 once_task_specs / rollback_attempts 那十几个数组）。
--
-- cleanup_state=pending 的行由后台定时任务扫，过了 target_date 就清影子任务。
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `assistant_task_override` (
  `id`              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mode`            varchar(16)   NOT NULL DEFAULT 'once' COMMENT 'once 单日 / range 时段',
  `kind`            varchar(32)   NOT NULL DEFAULT '' COMMENT 'cancel / move / shift / swap / enable / disable',
  `schedule_name`   varchar(255)  NOT NULL DEFAULT '' COMMENT '方案名，对应 task.info',
  `target_date`     date                   DEFAULT NULL COMMENT '生效日期',
  `date_end`        date                   DEFAULT NULL COMMENT 'range 模式的结束日',
  `source_task_ids` varchar(1024) NOT NULL DEFAULT '' COMMENT '被操作的原任务 id，逗号分隔',
  `shadow_task_ids` varchar(1024) NOT NULL DEFAULT '' COMMENT '为实现单日例外新建的影子任务 id',
  `payload`         text                   COMMENT '完整快照 JSON',
  `cleanup_state`   varchar(16)   NOT NULL DEFAULT 'pending' COMMENT 'pending / done / failed',
  `cleaned_at`      datetime               DEFAULT NULL,
  `userid`          int(10)       NOT NULL DEFAULT 0,
  `createtime`      timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cleanup` (`cleanup_state`, `target_date`),
  KEY `idx_schedule` (`schedule_name`, `target_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='AI助手任务覆盖与影子任务';

-- ────────────────────────────────────────────────────────────
-- 5. assistant_runtime_play —— 立即播放的临时任务登记
--
-- 「现在播一下儿歌」会在 task 表建一条临时任务并启动。问题是事后要能
-- 找回来停掉、清掉 —— 而 task 表不能加列做标记（红线），
-- 所以另起一张登记表记「哪些 taskid 是助手临时建的」。
--
-- ⚠ 原实现只记在进程内存里（RUNTIME_PLAY_CACHE_SECONDS），
--   进程一重启就再也找不到这些任务了，它自己的注释里也写了这个缺陷。
--   落库正好把这个坑填上。
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `assistant_runtime_play` (
  `id`           bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `taskid`       int(11)       NOT NULL DEFAULT 0 COMMENT 'task.taskid',
  `taskname`     varchar(255)  NOT NULL DEFAULT '',
  `media_ids`    varchar(1024) NOT NULL DEFAULT '' COMMENT '逗号分隔',
  `terminal_ids` varchar(2048) NOT NULL DEFAULT '' COMMENT '逗号分隔',
  `state`        varchar(16)   NOT NULL DEFAULT 'playing' COMMENT 'playing / stopped / cleaned',
  `userid`       int(10)       NOT NULL DEFAULT 0,
  `starttime`    timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `endtime`      datetime               DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_state` (`state`, `starttime`),
  KEY `idx_taskid` (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='AI助手立即播放的临时任务';

-- ────────────────────────────────────────────────────────────
-- 6. assistant_undo —— 撤销凭据
--
-- ChatResponse 里的 undo_token 对应这张表：执行前把改动涉及的行拍个快照，
-- 用户点「撤销」就按快照回滚。有效期到点即失效（默认 10 分钟），
-- used=1 表示已经撤过，不能重复撤。
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `assistant_undo` (
  `token`       varchar(64)  NOT NULL COMMENT '随机串，前端拿它来撤销',
  `id`          bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_key` varchar(64)  NOT NULL DEFAULT '',
  `userid`      int(10)      NOT NULL DEFAULT 0,
  `intent`      varchar(64)  NOT NULL DEFAULT '',
  `summary`     varchar(255) NOT NULL DEFAULT '' COMMENT '给用户看的一句话：撤销什么',
  `snapshot`    mediumtext            COMMENT '变更前快照 JSON',
  `expiretime`  datetime     NOT NULL,
  `used`        tinyint(1)   NOT NULL DEFAULT 0,
  `createtime`  timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`),
  KEY `idx_expire` (`expiretime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='AI助手撤销凭据';
