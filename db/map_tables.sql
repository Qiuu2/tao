-- 地图功能的新增表（2 张）
--
-- # 这是什么
--
-- 资源管理 → 地图：上传一张校园平面图当底图，把终端按实际位置摆上去，
-- 巡检时一眼看出「哪一栋哪一台掉线了」。列表告诉你「哪台」，地图告诉你「在哪」。
--
-- # 为什么要新建表
--
-- 底图和「终端摆在图上的哪个位置」在旧库 89 张表里都无处可放。
--
-- ⚠ terminal 表里**确实有** longitude / latitude 两列，但那是经纬度，
--   给真实地理坐标用的；我们的底图是一张图片，位置是**图上的百分比**，
--   两者不是一回事。而且那两列是后台 C 服务的地盘，不该由 Web 去写。
--
-- # 红线
--
--   ✅ 可以新建表          （用户 2026-09-08 明确放开）
--   ❌ 不能改动任何现有表   （不加列、不改类型、不加索引）
--
-- 表名以 map_ 开头，与旧库 89 张表、助手的 6 张、开发者接口的 2 张都不冲突。
--
-- # 字符集为什么是 utf8 不是 utf8mb4
--
-- htweb 的连接串写死 charset=utf8（理由见 config.DSN 的注释）。连接是 utf8 时
-- 四字节字符在协议层就被截断了，列声明成 utf8mb4 也救不回来。同助手那 6 张表。
--
-- # 怎么执行
--
-- 由管理员用一个有 DDL 权限的账号执行一次（htweb 的运行账号有意不给 DDL）：
--
--   mysql -uroot audioserver < db/map_tables.sql
--
-- 可重复执行：建表是 IF NOT EXISTS，默认底图那条 INSERT 也做了存在性判断。
--
-- ⚠ 开头那句 SET NAMES utf8 不能删。没有它的时候，mysql 客户端按自己的默认字符集
--   （常见是 latin1）解释这个文件里的中文，「南昌理工学院」会被当成一串 latin1
--   再转存成 utf8 —— 库里存进去的是双重编码，界面上显示成「å—æ˜Œç†å·¥å­¦é™¢」。
--   实测踩过一次。htweb 的连接串是 charset=utf8，导入这一侧也必须对齐。

SET NAMES utf8;

-- ── 底图 ──────────────────────────────────────────────────────────────
--
-- filename 为空串 = 还没上传真实底图，界面画一张占位底图（写着 name）。
-- 占位不是空页面：地图记录已经存在，终端照样能摆上去，
-- 等真图上传进来，坐标是百分比，原样对得上，不用重摆。
CREATE TABLE IF NOT EXISTS map_image (
  id         int(11)      NOT NULL AUTO_INCREMENT,
  name       varchar(64)  NOT NULL DEFAULT ''  COMMENT '底图名称，如「南昌理工学院」',
  filename   varchar(255) NOT NULL DEFAULT ''  COMMENT '/backup/mapdata/<数字>.png；空串表示用占位底图',
  width      int(11)      NOT NULL DEFAULT 0   COMMENT '图片像素宽，仅用于按原始比例显示',
  height     int(11)      NOT NULL DEFAULT 0,
  sort       int(11)      NOT NULL DEFAULT 0   COMMENT '越小越靠前；打开页面默认看排第一的那张',
  userid     int(11)      NOT NULL DEFAULT 0   COMMENT '上传者 book_admin.id',
  createtime datetime              DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ── 终端在底图上的位置 ────────────────────────────────────────────────
--
-- x / y 存的是**百分比**（0~100），不是像素。
-- 底图在界面上会按容器大小缩放，存像素的话换个屏幕分辨率就全偏了；
-- 存百分比，换底图（同一张图换清晰版）也不用重摆。
CREATE TABLE IF NOT EXISTS map_terminal (
  id         int(11) NOT NULL AUTO_INCREMENT,
  mapid      int(11) NOT NULL DEFAULT 0,
  terminalid int(11) NOT NULL DEFAULT 0,
  x          double  NOT NULL DEFAULT 0 COMMENT '距左边的百分比 0~100',
  y          double  NOT NULL DEFAULT 0 COMMENT '距顶部的百分比 0~100',
  createtime datetime         DEFAULT NULL,
  PRIMARY KEY (id),
  -- 同一张图上同一台终端只能出现一次。这是新表，建表时带索引不违反红线。
  UNIQUE KEY uk_map_terminal (mapid, terminalid),
  KEY idx_mapid (mapid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ── 默认底图 ──────────────────────────────────────────────────────────
--
-- filename 留空，界面画占位图。拿到真实校园平面图之后，在界面上点「更换底图」
-- 传上去即可，不用改代码、也不用重摆已经放好的终端。
INSERT INTO map_image (name, filename, width, height, sort, userid, createtime)
SELECT '南昌理工学院', '', 1600, 1000, 0, 0, NOW()
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM map_image);
