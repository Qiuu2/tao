-- 启用计划调度器的新增表（1 张：enable_run）
--
-- # 这是什么
--
-- 启用管理里一条计划说的是：「到了某年某月某日某时某分某秒，把这一批任务
-- 按各自的安排启用或停用」。现场后来又要求加上**结束**：到了结束日期时间，
-- 把这些任务**恢复成原来的启停状态**。
--
-- 「恢复成原来的」这件事必须先把「原来的是什么」记下来 —— 这张表就是记这个的。
--
-- # 为什么要新建表
--
-- 原来的状态在 enabletask 里无处可放：那张表一行是一条计划，
-- taskid / enstate 是两串逗号分隔值，没有「每条任务应用前是什么状态」的位置。
--
-- ⚠ 红线照旧：
--     ✅ 可以新建表          （用户 2026-09-08 明确放开）
--     ❌ 不能改动任何现有表   （不加列、不改类型、不加索引）
--
--   `enabletask` 上的 enddate / endtime 两列是**现场自己加的**，不是本项目加的。
--
-- # 与 audioserver 的关系（要紧）
--
-- `enabletask.flag` 就是「已执行」的标记，现场确认的约定：
-- **默认 0，执行完置 1，置 1 之后不再判断**。旧版 PHP 也只在新增/修改时写 0、
-- 从不写 1（do.php:2394 / 2475 / 2565 / 2656），把它置 1 的正是后台 audioserver。
--
-- 调度器按同一条约定办：只查 flag = 0 的计划，置好状态、给 audioserver 发完消息
-- 之后才把 flag 置 1。谁先到点谁置 flag，另一边下一轮就看不到这条计划了。
--
-- 这张表上的唯一键是第二道保险；另外真正的 UPDATE 还带着
-- `AND projectstate <> 目标值`，已经是目标值就不写、也不发指令。
--
-- 而「到了结束时间恢复」是 audioserver 一定没有的（enddate / endtime 是新列），
-- 那一半由这里独占，认的是这张表里 restored_at IS NULL 的行，与 flag 无关。
--
-- # 怎么执行
--
-- 由管理员用一个有 DDL 权限的账号执行一次（htweb 的运行账号有意不给 DDL）：
--
--     mysql -u root -p audioserver < db/enable_tables.sql
--
-- 字符集 utf8 而不是 utf8mb4：htweb 的连接串写死 charset=utf8（理由见
-- config.DSN 的注释），连接是 utf8 时四字节字符在协议层就被截断了。
-- 同地图、助手、开发者接口那几张表。

-- ── 一条计划对一条任务的执行记录 ──────────────────────────────────────
--
-- 一条计划里有几条任务，这里就有几行。
--
-- prev_state 是**应用前**这条任务的 projectstate（0 启用 / 1 停用），
-- 到了结束时间原样写回去。没有它就只能瞎猜「恢复成启用」，
-- 而那对本来就是停用的任务是错的。
CREATE TABLE IF NOT EXISTS enable_run (
  id          bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  plan_id     int(11)             NOT NULL DEFAULT 0 COMMENT 'enabletask.id',
  taskid      int(11)             NOT NULL DEFAULT 0 COMMENT 'task.taskid',
  want_state  tinyint(4)          NOT NULL DEFAULT 0 COMMENT '计划要置的状态 0启用 1停用',
  prev_state  tinyint(4)          NOT NULL DEFAULT 0 COMMENT '应用前的 projectstate，用于恢复',
  applied_at  datetime                     DEFAULT NULL COMMENT '什么时候应用的',
  restored_at datetime                     DEFAULT NULL COMMENT '什么时候恢复的；NULL = 还没恢复',
  PRIMARY KEY (id),
  -- 同一条计划的同一条任务只登记一次 —— 幂等就靠它：
  -- 重复的 INSERT 会被这个唯一键挡住，调度器据此知道「这条已经应用过了」。
  UNIQUE KEY uk_enable_run (plan_id, taskid),
  -- 恢复阶段按「还没恢复的」扫，走这个索引
  KEY idx_restore (restored_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
