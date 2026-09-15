-- 用户组功能权限：给左侧菜单里 9 项「还没有自己的钥匙」的功能各加一列
--
-- # 这是什么
--
-- `usergroup` 原来 13 个权限位，是从旧版 ok112 原样继承下来的
-- （ok112 的 userGroupAdd_form.html 里也正好是这 13 个 checkbox）。
-- 新 web 的左侧菜单比旧版多出好些页，它们当时都是**借别的权限位**或者
-- **干脆不设门**：
--
--   地图            借 terminalpriv（写），菜单人人可见
--   启用管理        借 ttspriv        —— 给了文字语音就等于给了启用管理
--   云广播终端      **没有门**        —— 只要登录就能点「全部清除」
--   音乐传输        借 terminalpriv（写），菜单人人可见
--   任务传送        **没有门**        —— 只要登录就能点「删除离线音乐」
--   噪声设备        借 terminalgrouppriv（写），菜单人人可见
--   声场分区        借 terminalgrouppriv（写），菜单人人可见
--   声场任务        借 taskpriv（写），菜单人人可见
--   接口调用平台    登录即可
--
-- 现场 2026-09-15：「用户组的功能权限少了。仔细查看web页面左侧列表功能。
-- 如果数据库表 usergroup 中少了字段，在这个表里请加。」—— 一页一把钥匙。
--
-- ⚠ 红线的例外，记在这里免得下一个人当成违规：
--     项目一直有「不能动现有表」这条线。**这一次是现场明确点名放开的**，
--     而且只放开 `usergroup` 这一张表、只做 ADD COLUMN。
--     别的 88 张表照旧不动。
--
-- # 为什么是新列而不是复用
--
-- 复用就是现在这个样子：ttspriv 同时管文字语音和启用管理，
-- 想只给启用管理做不到；serverpriv 同时管服务器信息、遥控任务、注册服务。
-- 每借一次，「这个组能干什么」就离列名远一步，最后只能靠翻代码才说得清。
--
-- # 怎么执行
--
-- 由管理员用一个有 DDL 权限的账号执行一次（htweb 的运行账号有意不给 DDL）：
--
--     mysql -u root -p audioserver < db/usergroup_rights.sql
--
-- 可以重复执行：ALTER 都带 IF NOT EXISTS，底下那段回填只认「刚建出来的列」
-- （见它自己的说明）。

-- ── 1. 加列 ──────────────────────────────────────────────────────
--
-- 类型、默认值、注释格式都照这张表原有那 13 列，别自成一派：
-- int(10) unsigned NOT NULL DEFAULT 0，注释写成「X权限1-有 0-无」。
-- DEFAULT 0 = **新建的用户组默认一项都没有**，要管理员自己勾。

-- ⚠ 这一句必须在 ALTER **之前**：它记下「跑这个脚本时列还不在」，
-- 底下的回填只在第一次跑的时候动手。
SET @fresh := (SELECT COUNT(*) = 0 FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'usergroup' AND column_name = 'apipriv');

ALTER TABLE usergroup
  ADD COLUMN IF NOT EXISTS `mappriv`           int(10) unsigned NOT NULL DEFAULT 0 COMMENT '地图权限1-有 0-无',
  ADD COLUMN IF NOT EXISTS `enablepriv`        int(10) unsigned NOT NULL DEFAULT 0 COMMENT '启用管理权限1-有 0-无',
  ADD COLUMN IF NOT EXISTS `cloudterminalpriv` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '云广播终端权限1-有 0-无',
  ADD COLUMN IF NOT EXISTS `offlinepriv`       int(10) unsigned NOT NULL DEFAULT 0 COMMENT '音乐传输权限1-有 0-无',
  ADD COLUMN IF NOT EXISTS `transferpriv`      int(10) unsigned NOT NULL DEFAULT 0 COMMENT '任务传送权限1-有 0-无',
  ADD COLUMN IF NOT EXISTS `noisedevpriv`      int(10) unsigned NOT NULL DEFAULT 0 COMMENT '噪声设备权限1-有 0-无',
  ADD COLUMN IF NOT EXISTS `soundzonepriv`     int(10) unsigned NOT NULL DEFAULT 0 COMMENT '声场分区权限1-有 0-无',
  ADD COLUMN IF NOT EXISTS `soundtaskpriv`     int(10) unsigned NOT NULL DEFAULT 0 COMMENT '声场任务权限1-有 0-无',
  ADD COLUMN IF NOT EXISTS `apipriv`           int(10) unsigned NOT NULL DEFAULT 0 COMMENT '接口调用平台权限1-有 0-无';

-- ── 2. 回填：现网已有的用户组，能干什么保持不变 ──────────────────
--
-- 加完列如果就这么算了，所有列都是 0，**第二天现网每个用户组都少了 9 项功能**。
-- 所以已有的组要按「它今天实际能不能用这一页」回填。
--
-- 逐列的依据就是上面那张表：原来借哪把钥匙，就照那把钥匙的值填；
-- 原来根本没门（谁都能点）的，填 1。
--
--   mappriv           = terminalpriv        地图的增删改本来就要 terminalpriv
--   enablepriv        = ttspriv             启用管理本来就跟着文字语音走
--   cloudterminalpriv = 1                   原来没门
--   offlinepriv       = terminalpriv        下发接口本来就要 terminalpriv
--   transferpriv      = 1                   原来没门
--   noisedevpriv      = terminalgrouppriv   噪声设备的写本来就要分区权限
--   soundzonepriv     = terminalgrouppriv   同上
--   soundtaskpriv     = taskpriv            声场任务的写本来跟着文件广播走
--   apipriv           = 1                   原来登录即可
--
-- ⚠ 这会让一部分用户**看不到**他以前能看（但改不了）的那几页 ——
--   云广播和噪声那几页的菜单原来是人人可见的，只有写操作才要权限。
--   一页一把钥匙的含义就是「钥匙管这一页」，所以菜单跟着写权限走。
--   管理员在用户组页面上勾一下就能放回去。
--
-- ⚠ 只回填**这一次真的新建出来的列**。
--   `@fresh` 在 ALTER 之前就取好了：那时候 apipriv 还不存在 → 1，
--   说明这是第一次跑；重复执行时它已经存在 → 0，底下那句 UPDATE 一行也不改。
--   不这么挡的话，再跑一遍会把管理员后来手工改过的配置冲回老值。
--   （挡的方式特意不新建标记列 —— 这张表是给人看的权限表，
--     多一列内部状态会让下一个人以为那也是一项权限。）
UPDATE usergroup SET
    mappriv           = terminalpriv,
    enablepriv        = ttspriv,
    cloudterminalpriv = 1,
    offlinepriv       = terminalpriv,
    transferpriv      = 1,
    noisedevpriv      = terminalgrouppriv,
    soundzonepriv     = terminalgrouppriv,
    soundtaskpriv     = taskpriv,
    apipriv           = 1
  WHERE @fresh = 1;

-- ── 3. 系统用户组（id = 1）永远全开 ──────────────────────────────
--
-- 代码里 `u.IsAdmin = (usergroupId == 1)`，所有权限判断都先看这个，
-- 所以第 1 组其实不看这些列。这里仍然把它写成全 1，是为了让
-- **界面上显示的和实际生效的一致** —— 用户组那一页会把第 1 组的
-- 复选框全部勾上并置灰，库里如果是 0，下一个人看库会以为界面在骗他。
UPDATE usergroup SET
    taskpriv = 1, terminalpriv = 1, mediapriv = 1, userpriv = 1, serverpriv = 1,
    folderpriv = 1, terminalgrouppriv = 1, alarmgrouppriv = 1, bellpriv = 1,
    admpriv = 1, telephonepriv = 1, powerplay = 1, ttspriv = 1,
    mappriv = 1, enablepriv = 1, cloudterminalpriv = 1, offlinepriv = 1,
    transferpriv = 1, noisedevpriv = 1, soundzonepriv = 1, soundtaskpriv = 1,
    apipriv = 1
  WHERE id = 1;
