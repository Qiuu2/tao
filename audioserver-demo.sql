
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `TKdeviceinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `TKdeviceinfo` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `bureauCode` varchar(32) DEFAULT NULL,
  `stationCode` varchar(32) DEFAULT NULL,
  `deviceid` varchar(32) DEFAULT NULL,
  `deviceName` varchar(32) DEFAULT NULL,
  `largeSort` varchar(32) DEFAULT NULL,
  `smallSort` varchar(32) DEFAULT NULL,
  `location` varchar(32) DEFAULT NULL,
  `detailLocation` varchar(32) DEFAULT NULL,
  `manufacturer` varchar(32) DEFAULT NULL,
  `productDate` varchar(32) DEFAULT NULL,
  `useDate` varchar(32) DEFAULT NULL,
  `installationDate` varchar(32) DEFAULT NULL,
  `useLimit` varchar(32) DEFAULT NULL,
  `useState` varchar(32) DEFAULT NULL,
  `flag` int(11) DEFAULT 0,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `TKdeviceinfo` WRITE;
/*!40000 ALTER TABLE `TKdeviceinfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `TKdeviceinfo` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `TKstationinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `TKstationinfo` (
  `bureauCode` varchar(32) DEFAULT NULL,
  `stationCode` varchar(32) DEFAULT NULL,
  `deviceId` varchar(32) DEFAULT NULL,
  `largeSort` varchar(32) DEFAULT NULL,
  `serverIP` varchar(32) DEFAULT NULL,
  `serverPort` varchar(32) DEFAULT NULL,
  `user` varchar(32) DEFAULT NULL,
  `password` varchar(256) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `TKstationinfo` WRITE;
/*!40000 ALTER TABLE `TKstationinfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `TKstationinfo` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ai_device`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_device` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT COMMENT '人员id',
  `shibiedeviceid` varchar(32) NOT NULL COMMENT '人脸识别机编号',
  `terminalid` int(4) NOT NULL COMMENT '终端设备id',
  `groupid` int(4) DEFAULT 0,
  PRIMARY KEY (`id`,`shibiedeviceid`,`terminalid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ai_device` WRITE;
/*!40000 ALTER TABLE `ai_device` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_device` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ai_devicedemo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_devicedemo` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `shibiedeviceid` int(8) DEFAULT NULL COMMENT '人脸设备id',
  `deviceaddr` varchar(32) DEFAULT NULL COMMENT '人脸设备名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ai_devicedemo` WRITE;
/*!40000 ALTER TABLE `ai_devicedemo` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_devicedemo` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ai_people`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_people` (
  `id` int(4) NOT NULL AUTO_INCREMENT COMMENT '人员id',
  `shibiedeviceid` int(8) DEFAULT NULL COMMENT '人脸设备id',
  `deviceaddr` varchar(128) DEFAULT NULL COMMENT '人脸设备地址',
  `peopleidcard` varchar(32) DEFAULT NULL COMMENT '人员身份证号',
  `deviceip` varchar(32) DEFAULT NULL COMMENT '人脸设备ip',
  `boyname1` varchar(32) DEFAULT NULL COMMENT '人员子女1',
  `boyname2` varchar(32) DEFAULT NULL COMMENT '人员子女2',
  `boyname3` varchar(32) DEFAULT NULL COMMENT '人员子女3',
  `peoplename` varchar(32) DEFAULT NULL COMMENT '人员名单',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ai_people` WRITE;
/*!40000 ALTER TABLE `ai_people` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_people` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ai_timetts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_timetts` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT COMMENT '时间和播报文字设置id',
  `time` varchar(32) NOT NULL COMMENT '时间设置',
  `demo` varchar(128) NOT NULL COMMENT '播报文字设置',
  `enable` int(4) NOT NULL,
  `volume` int(4) DEFAULT 80,
  PRIMARY KEY (`id`,`time`,`demo`,`enable`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ai_timetts` WRITE;
/*!40000 ALTER TABLE `ai_timetts` DISABLE KEYS */;
INSERT INTO `ai_timetts` (`id`, `time`, `demo`, `enable`, `volume`) VALUES (1,'8:0-12:0','小朋友欢迎到校',1,80),
(2,'12:0-14:0','小朋友午休',1,80),
(3,'14:0-21:0','小朋友放学再见',1,80);
/*!40000 ALTER TABLE `ai_timetts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `alarmarea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alarmarea` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '报警主机' COMMENT '报警分区名称',
  `info` varchar(45) NOT NULL DEFAULT '报警主机' COMMENT '报警分区描述',
  `createtime` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '报警分区创建的时间',
  `userid` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `alarmarea` WRITE;
/*!40000 ALTER TABLE `alarmarea` DISABLE KEYS */;
INSERT INTO `alarmarea` (`id`, `name`, `info`, `createtime`, `userid`) VALUES (1,'教学楼报警区','演示','2026-09-04 01:23:54',1);
/*!40000 ALTER TABLE `alarmarea` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `alarmgroupmap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alarmgroupmap` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `info` varchar(45) NOT NULL COMMENT '备注',
  `alarmterminalid` int(10) unsigned NOT NULL COMMENT '报警主机的设备ID',
  `alarmchannel` int(10) unsigned NOT NULL COMMENT '报警主机的通道号',
  `firealarmgroupid` int(10) unsigned NOT NULL COMMENT '对应的报警分区',
  `mediaid` int(10) unsigned NOT NULL COMMENT '报警时播放的媒体文件',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `alarmgroupmap` WRITE;
/*!40000 ALTER TABLE `alarmgroupmap` DISABLE KEYS */;
INSERT INTO `alarmgroupmap` (`id`, `info`, `alarmterminalid`, `alarmchannel`, `firealarmgroupid`, `mediaid`) VALUES (1,'火警一路',20,1,1,159);
/*!40000 ALTER TABLE `alarmgroupmap` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `audiocodectype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audiocodectype` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `info` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `audiocodectype` WRITE;
/*!40000 ALTER TABLE `audiocodectype` DISABLE KEYS */;
/*!40000 ALTER TABLE `audiocodectype` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `audioformat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audioformat` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `audioformat` WRITE;
/*!40000 ALTER TABLE `audioformat` DISABLE KEYS */;
/*!40000 ALTER TABLE `audioformat` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `audioserver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audioserver` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) DEFAULT NULL,
  `ip` varchar(255) NOT NULL DEFAULT '',
  `port` int(11) NOT NULL DEFAULT 3333,
  `maxterminal` int(11) NOT NULL DEFAULT 100,
  `maxtask` int(11) DEFAULT 100,
  `workstate` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `audioserver` WRITE;
/*!40000 ALTER TABLE `audioserver` DISABLE KEYS */;
/*!40000 ALTER TABLE `audioserver` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `audiosource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audiosource` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '终端声源名称',
  `info` varchar(45) NOT NULL DEFAULT '' COMMENT '终端声源描述',
  `powermgrid` int(10) unsigned NOT NULL COMMENT '终端电源号',
  `powermgrchannel` int(10) unsigned NOT NULL COMMENT '终端电源通道',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `audiosource` WRITE;
/*!40000 ALTER TABLE `audiosource` DISABLE KEYS */;
INSERT INTO `audiosource` (`id`, `name`, `info`, `powermgrid`, `powermgrchannel`) VALUES (1,'VCD','数据来自VCD',1,1),
(2,'DVR','数据来自DVD',1,2),
(3,'广播','数据来自广播',1,3),
(4,'话筒','数据来自话筒',1,4);
/*!40000 ALTER TABLE `audiosource` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `belltask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `belltask` (
  `id` int(20) NOT NULL AUTO_INCREMENT COMMENT '此表没有使用',
  `belltaskname` varchar(255) NOT NULL,
  `prepower` int(10) NOT NULL DEFAULT 0 COMMENT '0立即 非0预等待',
  `startdate` date NOT NULL,
  `enddate` date NOT NULL,
  `exemodel` varchar(255) NOT NULL DEFAULT '' COMMENT '直接用字符串表示星期几,0表示每天',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `belltask` WRITE;
/*!40000 ALTER TABLE `belltask` DISABLE KEYS */;
/*!40000 ALTER TABLE `belltask` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `book_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_admin` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '登陆用户名称只有字母数字中文合法',
  `userpwd` varchar(100) NOT NULL DEFAULT '' COMMENT '登陆用户密码使用md5加密',
  `usergroupid` int(12) unsigned NOT NULL DEFAULT 0 COMMENT '用户所属组',
  `info` varchar(255) DEFAULT '' COMMENT '用户信息',
  `fullname` varchar(50) DEFAULT '' COMMENT '用户附加信息',
  `usersessionid` varchar(255) DEFAULT '' COMMENT '用户登录会话ID',
  `ctrlwind` int(4) NOT NULL DEFAULT 0 COMMENT '手机分控ID ',
  `enable` int(4) NOT NULL DEFAULT 1,
  `subwind` int(10) DEFAULT 0 COMMENT '分控软件ID',
  `camerawind` int(10) DEFAULT 0 COMMENT '监控软件ID',
  `loginnum` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `book_admin` WRITE;
/*!40000 ALTER TABLE `book_admin` DISABLE KEYS */;
INSERT INTO `book_admin` (`id`, `username`, `userpwd`, `usergroupid`, `info`, `fullname`, `usersessionid`, `ctrlwind`, `enable`, `subwind`, `camerawind`, `loginnum`) VALUES (1,'admin','e10adc3949ba59abbe56e057f20f883e',1,'administrator','administrator','',1,1,1001,2001,0),
(2,'operator','e10adc3949ba59abbe56e057f20f883e',2,'广播室日常操作','张老师','',0,1,0,0,NULL),
(3,'viewer','e10adc3949ba59abbe56e057f20f883e',3,'仅查看','李主任','',0,1,0,0,NULL),
(4,'night','e10adc3949ba59abbe56e057f20f883e',2,'已停用','夜间值班','',0,0,0,0,NULL);
/*!40000 ALTER TABLE `book_admin` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `book_msg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_msg` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) CHARACTER SET gbk COLLATE gbk_chinese_ci NOT NULL DEFAULT '',
  `content` text CHARACTER SET gbk COLLATE gbk_chinese_ci NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 0,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip` varchar(30) CHARACTER SET gbk COLLATE gbk_chinese_ci NOT NULL DEFAULT '',
  `sh` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `book_msg` WRITE;
/*!40000 ALTER TABLE `book_msg` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_msg` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `book_reply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_reply` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `m_id` int(12) NOT NULL DEFAULT 0,
  `content` text NOT NULL,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `book_reply` WRITE;
/*!40000 ALTER TABLE `book_reply` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_reply` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `callgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `callgroup` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL,
  `terminalid` int(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `callgroup` WRITE;
/*!40000 ALTER TABLE `callgroup` DISABLE KEYS */;
INSERT INTO `callgroup` (`id`, `name`, `terminalid`) VALUES (3,'广播室可呼范围',6);
/*!40000 ALTER TABLE `callgroup` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `camer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camer` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `camername` varchar(256) DEFAULT NULL,
  `camerip` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `camer` WRITE;
/*!40000 ALTER TABLE `camer` DISABLE KEYS */;
/*!40000 ALTER TABLE `camer` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `camer_alarm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camer_alarm` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `eventtype` int(4) DEFAULT 0,
  `eventname` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `camer_alarm` WRITE;
/*!40000 ALTER TABLE `camer_alarm` DISABLE KEYS */;
/*!40000 ALTER TABLE `camer_alarm` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `camer_alarmofmedia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camer_alarmofmedia` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `mediaid` int(4) DEFAULT 0,
  `eventid` int(4) DEFAULT 0,
  `sort` int(4) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `camer_alarmofmedia` WRITE;
/*!40000 ALTER TABLE `camer_alarmofmedia` DISABLE KEYS */;
/*!40000 ALTER TABLE `camer_alarmofmedia` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cameramap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cameramap` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `terminalid` int(4) NOT NULL,
  `ipaddr` varchar(16) NOT NULL,
  `mac` varchar(32) NOT NULL DEFAULT '00.00.00.00.00.00',
  `username` varchar(32) NOT NULL DEFAULT 'admin',
  `password` varchar(32) NOT NULL DEFAULT '12345',
  `port` int(4) NOT NULL DEFAULT 8000,
  `camername` varchar(32) DEFAULT NULL,
  `camerstate` int(4) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cameramap` WRITE;
/*!40000 ALTER TABLE `cameramap` DISABLE KEYS */;
/*!40000 ALTER TABLE `cameramap` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `camerofterminal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camerofterminal` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `camerid` int(4) DEFAULT NULL,
  `terminalid` int(4) DEFAULT NULL,
  `area` varchar(16) DEFAULT '1111111111111111',
  `groupid` int(4) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `camerofterminal` WRITE;
/*!40000 ALTER TABLE `camerofterminal` DISABLE KEYS */;
/*!40000 ALTER TABLE `camerofterminal` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `centralctrl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `centralctrl` (
  `terminalid` int(10) NOT NULL COMMENT '终端ID',
  `pcstate` tinyint(1) DEFAULT 0 COMMENT '电脑开关',
  `projectionstate` tinyint(1) DEFAULT 0 COMMENT '投影机开关',
  `systemstate` tinyint(1) DEFAULT 0 COMMENT '系统开关',
  `volstate` tinyint(1) DEFAULT 1 COMMENT '音量开关',
  `volume` tinyint(4) DEFAULT 80 COMMENT '音量',
  `projectionscreenstate` tinyint(1) DEFAULT 0 COMMENT '投影幕升降',
  `mix_preced` tinyint(1) DEFAULT 0 COMMENT '混音/优先',
  `mic_vol` tinyint(1) DEFAULT 80 COMMENT '话筒音量',
  `net_vol` tinyint(1) DEFAULT 80 COMMENT '网络音量',
  `dormancy` tinyint(1) DEFAULT 0 COMMENT '休眠',
  `showcase` tinyint(1) DEFAULT 0 COMMENT '展台',
  `notebook` tinyint(1) DEFAULT 0 COMMENT '笔记本',
  `computer` tinyint(1) DEFAULT 0 COMMENT '电脑',
  `hdmi` tinyint(1) DEFAULT 0 COMMENT '高清',
  `power1` tinyint(1) DEFAULT 0 COMMENT '外控电源开/关',
  `power2` tinyint(1) DEFAULT 0 COMMENT '外控电源2开/关',
  `netstate` int(4) DEFAULT 1,
  `terminalname` varchar(32) DEFAULT NULL,
  `typeid` int(4) DEFAULT NULL,
  PRIMARY KEY (`terminalid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `centralctrl` WRITE;
/*!40000 ALTER TABLE `centralctrl` DISABLE KEYS */;
/*!40000 ALTER TABLE `centralctrl` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ctrldevice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ctrldevice` (
  `deviceid` int(4) NOT NULL AUTO_INCREMENT COMMENT '设备ID',
  `netid` int(4) DEFAULT NULL COMMENT '设备所属的网络ID',
  `name` varchar(128) NOT NULL COMMENT '设备名',
  `typeid` int(4) NOT NULL,
  `state` varchar(32) NOT NULL COMMENT '设备当前状态',
  `laststate` varchar(32) NOT NULL COMMENT '设备上次状态',
  `lastchangetime` datetime DEFAULT NULL COMMENT '设备状态更新时间',
  `postionx` float DEFAULT NULL COMMENT '设备在地图上的纵坐标位置',
  `postiony` float DEFAULT NULL COMMENT '设备在地图上的横坐标位置',
  `ext1` int(11) DEFAULT NULL COMMENT '特定设备的扩张属性',
  `devcode` varchar(6) DEFAULT NULL COMMENT '设备所属终端ID',
  `netstate` int(4) DEFAULT 0,
  `defaultstate` varchar(32) DEFAULT NULL,
  `deviceimgid` int(4) DEFAULT NULL,
  PRIMARY KEY (`deviceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ctrldevice` WRITE;
/*!40000 ALTER TABLE `ctrldevice` DISABLE KEYS */;
/*!40000 ALTER TABLE `ctrldevice` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ctrldevicetype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ctrldevicetype` (
  `id` int(11) NOT NULL COMMENT '设备类型ID',
  `name` varchar(128) DEFAULT NULL COMMENT '设备类型名称',
  `info` varchar(128) DEFAULT NULL COMMENT '设备类型属性',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ctrldevicetype` WRITE;
/*!40000 ALTER TABLE `ctrldevicetype` DISABLE KEYS */;
INSERT INTO `ctrldevicetype` (`id`, `name`, `info`) VALUES (1,'灯',NULL),
(2,'窗帘',NULL),
(3,'空调',NULL),
(4,'插座',NULL),
(5,'场景',NULL),
(6,'多功能控制器',NULL),
(7,'可调光灯',NULL);
/*!40000 ALTER TABLE `ctrldevicetype` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ctrlnet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ctrlnet` (
  `netid` int(4) NOT NULL AUTO_INCREMENT COMMENT '终端网络ID',
  `name` varchar(128) DEFAULT NULL COMMENT '终端网络名称',
  `latitude` int(11) DEFAULT NULL,
  `longitude` int(11) DEFAULT NULL,
  `nettypeid` int(11) DEFAULT NULL,
  `filepath` varchar(128) DEFAULT NULL,
  `terminalid` int(4) DEFAULT NULL,
  `mac` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`netid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ctrlnet` WRITE;
/*!40000 ALTER TABLE `ctrlnet` DISABLE KEYS */;
/*!40000 ALTER TABLE `ctrlnet` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ctrloftask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ctrloftask` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ctrldeviceid` int(11) NOT NULL COMMENT '设备ID',
  `ctrltaskid` int(11) NOT NULL COMMENT '终端ID',
  `state` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ctrloftask` WRITE;
/*!40000 ALTER TABLE `ctrloftask` DISABLE KEYS */;
/*!40000 ALTER TABLE `ctrloftask` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ctrloftermianl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ctrloftermianl` (
  `id` int(4) NOT NULL,
  `terminalID` int(4) NOT NULL COMMENT '终端id',
  `netID` int(4) NOT NULL COMMENT '网络ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ctrloftermianl` WRITE;
/*!40000 ALTER TABLE `ctrloftermianl` DISABLE KEYS */;
/*!40000 ALTER TABLE `ctrloftermianl` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ctrltask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ctrltask` (
  `taskid` int(11) NOT NULL AUTO_INCREMENT,
  `taskname` varchar(128) NOT NULL COMMENT '任务名称',
  `cmd` varchar(128) NOT NULL COMMENT '下发指令',
  `value` varchar(128) NOT NULL COMMENT '扩张指令',
  `starttime` time DEFAULT NULL COMMENT '命令执行时间',
  `startdate` date DEFAULT NULL COMMENT '生效日期',
  `enddate` date DEFAULT NULL COMMENT '结束日期',
  PRIMARY KEY (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ctrltask` WRITE;
/*!40000 ALTER TABLE `ctrltask` DISABLE KEYS */;
/*!40000 ALTER TABLE `ctrltask` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `first` varchar(20) DEFAULT NULL,
  `last` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `enabletask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `enabletask` (
  `id` int(4) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `enstate` varchar(1024) DEFAULT '0',
  `startdate` date DEFAULT NULL,
  `starttime` time DEFAULT NULL,
  `taskid` varchar(2048) DEFAULT NULL,
  `flag` int(4) DEFAULT 0,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `enabletask` WRITE;
/*!40000 ALTER TABLE `enabletask` DISABLE KEYS */;
/*!40000 ALTER TABLE `enabletask` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `filefolder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `filefolder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名称',
  `userid` int(10) NOT NULL COMMENT '该文件夹是谁创建',
  `priority` int(10) NOT NULL DEFAULT 0 COMMENT '文件夹是否共享0-不共享 1-共享',
  `createtime` timestamp NULL DEFAULT current_timestamp() COMMENT '文件夹创建时间',
  `parentid` int(10) NOT NULL DEFAULT 0 COMMENT '父目录id',
  PRIMARY KEY (`id`,`parentid`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `filefolder` WRITE;
/*!40000 ALTER TABLE `filefolder` DISABLE KEYS */;
INSERT INTO `filefolder` (`id`, `name`, `userid`, `priority`, `createtime`, `parentid`) VALUES (1,'共享媒体库',1,1,'2011-06-14 20:11:30',0),
(2,'铃声媒体库',1,1,'2011-06-14 20:11:44',0),
(3,'点播媒体库',1,1,'2011-06-21 19:30:37',0),
(4,'报警媒体库',1,1,'2011-06-21 19:30:38',0),
(5,'录音媒体库',1,1,'2011-06-21 19:58:02',0),
(6,'语音合成媒体库',1,1,'2011-06-21 19:58:02',0),
(7,'设备录音媒体',1,0,'2018-05-18 19:21:58',5),
(8,'录音媒体库',1,0,'2021-03-30 11:07:22',5),
(9,'提示音',1,0,'2021-03-30 11:07:22',1);
/*!40000 ALTER TABLE `filefolder` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `filetaskfree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `filetaskfree` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(16) DEFAULT NULL,
  `parentid` int(4) DEFAULT NULL,
  `userid` int(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `filetaskfree` WRITE;
/*!40000 ALTER TABLE `filetaskfree` DISABLE KEYS */;
INSERT INTO `filetaskfree` (`id`, `name`, `parentid`, `userid`) VALUES (1,'admin',0,1);
/*!40000 ALTER TABLE `filetaskfree` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `holidaytime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidaytime` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `startdate` date DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  `name` varchar(32) DEFAULT NULL,
  `projectstate` int(11) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `holidaytime` WRITE;
/*!40000 ALTER TABLE `holidaytime` DISABLE KEYS */;
/*!40000 ALTER TABLE `holidaytime` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `leddevice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leddevice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `terminalid` int(11) NOT NULL,
  `devid` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `ip` varchar(16) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `sendport` int(4) DEFAULT NULL,
  `mac` varchar(64) DEFAULT NULL,
  `subterminalid` int(10) DEFAULT 0,
  `defaulttext` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `leddevice` WRITE;
/*!40000 ALTER TABLE `leddevice` DISABLE KEYS */;
INSERT INTO `leddevice` (`id`, `terminalid`, `devid`, `name`, `ip`, `width`, `height`, `sendport`, `mac`, `subterminalid`, `defaulttext`) VALUES (1,6,1,'教学楼LED屏','192.168.1.60',128,32,5200,'',0,'');
/*!40000 ALTER TABLE `leddevice` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ledoftask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledoftask` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskid` int(11) DEFAULT NULL,
  `terminalid` int(11) DEFAULT NULL,
  `deviceid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ledoftask` WRITE;
/*!40000 ALTER TABLE `ledoftask` DISABLE KEYS */;
/*!40000 ALTER TABLE `ledoftask` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ledsentence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledsentence` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(1024) NOT NULL,
  `mediaid` int(11) NOT NULL,
  `speed` int(11) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `mediaseq` int(11) DEFAULT 0,
  `ledmode` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ledsentence` WRITE;
/*!40000 ALTER TABLE `ledsentence` DISABLE KEYS */;
INSERT INTO `ledsentence` (`id`, `text`, `mediaid`, `speed`, `type`, `mediaseq`, `ledmode`) VALUES (1,'欢迎莅临指导',146,5,1,0,1),
(2,'诚信考试 从我做起',147,5,1,0,1);
/*!40000 ALTER TABLE `ledsentence` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ledtaskfree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledtaskfree` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(16) DEFAULT NULL,
  `parentid` int(4) DEFAULT NULL,
  `userid` int(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ledtaskfree` WRITE;
/*!40000 ALTER TABLE `ledtaskfree` DISABLE KEYS */;
INSERT INTO `ledtaskfree` (`id`, `name`, `parentid`, `userid`) VALUES (2,'1',0,1);
/*!40000 ALTER TABLE `ledtaskfree` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(11) NOT NULL DEFAULT '' COMMENT '记录那个用户',
  `operate` varchar(255) NOT NULL DEFAULT '' COMMENT '记录用户做了什么',
  `ip` varchar(64) NOT NULL DEFAULT '' COMMENT '记录用户IP',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '用户动作的时间',
  `info` varchar(255) DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=629 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
INSERT INTO `log` (`id`, `user`, `operate`, `ip`, `time`, `info`) VALUES (1,'admin','用户登录','127.0.0.1','2026-09-02 17:21:04',''),
(2,'admin','用户登录','127.0.0.1','2026-09-02 17:21:26',''),
(3,'admin','用户登录','127.0.0.1','2026-09-02 17:21:43',''),
(4,'admin','用户登录','127.0.0.1','2026-09-02 17:37:09',''),
(5,'admin','用户登录','127.0.0.1','2026-09-02 17:40:23',''),
(6,'admin','用户登录','127.0.0.1','2026-09-02 18:06:45',''),
(7,'admin','用户登录','127.0.0.1','2026-09-02 18:09:00',''),
(8,'admin','用户登录','127.0.0.1','2026-09-02 18:17:25',''),
(9,'admin','用户登录','127.0.0.1','2026-09-02 18:17:59',''),
(10,'admin','用户登录','127.0.0.1','2026-09-02 18:18:59',''),
(11,'admin','用户登录','127.0.0.1','2026-09-02 18:19:17',''),
(12,'admin','用户登录','127.0.0.1','2026-09-02 18:20:19',''),
(13,'admin','用户登录','127.0.0.1','2026-09-02 18:21:21',''),
(14,'admin','用户登录','127.0.0.1','2026-09-02 18:23:11',''),
(15,'admin','用户登录','127.0.0.1','2026-09-02 18:25:27',''),
(16,'admin','任务批量加终端','127.0.0.1','2026-09-02 18:25:50',''),
(17,'admin','用户登录','127.0.0.1','2026-09-02 18:26:45',''),
(18,'admin','用户登录','127.0.0.1','2026-09-02 18:27:26',''),
(19,'admin','用户登录','127.0.0.1','2026-09-02 18:28:11',''),
(20,'admin','任务批量加终端','127.0.0.1','2026-09-02 18:28:25',''),
(21,'admin','用户登录','127.0.0.1','2026-09-02 18:28:31',''),
(22,'admin','用户登录','127.0.0.1','2026-09-02 18:29:24',''),
(23,'admin','用户登录','127.0.0.1','2026-09-03 11:10:54',''),
(24,'admin','修改终端','127.0.0.1','2026-09-03 11:11:26',''),
(25,'admin','用户登录','127.0.0.1','2026-09-03 11:14:33',''),
(26,'admin','用户登录','127.0.0.1','2026-09-03 11:14:52',''),
(27,'admin','用户登录','127.0.0.1','2026-09-03 11:16:12',''),
(28,'admin','用户登录','127.0.0.1','2026-09-03 11:17:55',''),
(29,'admin','用户登录','127.0.0.1','2026-09-03 11:20:27',''),
(30,'admin','用户登录','127.0.0.1','2026-09-03 11:28:44',''),
(31,'admin','用户登录','127.0.0.1','2026-09-03 11:30:27',''),
(32,'admin','用户登录','127.0.0.1','2026-09-03 11:31:11',''),
(33,'admin','用户登录','127.0.0.1','2026-09-03 13:20:02',''),
(34,'admin','用户登录','127.0.0.1','2026-09-03 13:20:25',''),
(35,'admin','用户登录','127.0.0.1','2026-09-03 13:20:53',''),
(36,'admin','用户登录','127.0.0.1','2026-09-03 13:21:13',''),
(37,'admin','用户登录','127.0.0.1','2026-09-03 13:21:37',''),
(38,'admin','用户登录','127.0.0.1','2026-09-03 13:22:15',''),
(39,'admin','用户登录','127.0.0.1','2026-09-03 13:30:54',''),
(40,'admin','用户登录','127.0.0.1','2026-09-03 13:31:47',''),
(41,'admin','用户登录','127.0.0.1','2026-09-03 13:56:11',''),
(42,'admin','用户登录','127.0.0.1','2026-09-03 14:02:21',''),
(43,'admin','用户登录','127.0.0.1','2026-09-03 14:02:46',''),
(44,'admin','用户登录','127.0.0.1','2026-09-03 14:03:17',''),
(45,'admin','用户登录','127.0.0.1','2026-09-03 14:03:42',''),
(46,'admin','用户登录','127.0.0.1','2026-09-03 14:04:14',''),
(47,'admin','用户登录','127.0.0.1','2026-09-03 14:07:17',''),
(48,'admin','用户登录','127.0.0.1','2026-09-03 14:22:35',''),
(49,'admin','用户登录','127.0.0.1','2026-09-03 14:27:10',''),
(50,'admin','用户登录','127.0.0.1','2026-09-03 14:28:02',''),
(51,'admin','用户登录','127.0.0.1','2026-09-03 14:28:43',''),
(52,'admin','用户登录','127.0.0.1','2026-09-03 14:29:50',''),
(53,'admin','用户登录','127.0.0.1','2026-09-03 14:32:04',''),
(54,'admin','用户登录','127.0.0.1','2026-09-03 14:42:35',''),
(55,'admin','用户登录','127.0.0.1','2026-09-03 14:43:39',''),
(56,'admin','用户登录','127.0.0.1','2026-09-03 14:47:24',''),
(57,'admin','任务批量加终端','127.0.0.1','2026-09-03 14:47:38',''),
(58,'admin','用户登录','127.0.0.1','2026-09-03 14:47:59',''),
(59,'admin','任务批量加终端','127.0.0.1','2026-09-03 14:48:13',''),
(60,'admin','用户登录','127.0.0.1','2026-09-03 14:53:32',''),
(61,'admin','用户登录','127.0.0.1','2026-09-03 14:55:41',''),
(62,'admin','用户登录','127.0.0.1','2026-09-03 14:58:25',''),
(63,'admin','用户登录','127.0.0.1','2026-09-03 14:58:30',''),
(64,'admin','用户登录','127.0.0.1','2026-09-03 14:58:38',''),
(65,'admin','用户登录','127.0.0.1','2026-09-03 14:58:48',''),
(66,'admin','用户登录','127.0.0.1','2026-09-03 15:12:18',''),
(67,'admin','新建任务','127.0.0.1','2026-09-03 15:12:50',''),
(68,'admin','新建任务','127.0.0.1','2026-09-03 15:12:50',''),
(69,'admin','新建任务','127.0.0.1','2026-09-03 15:12:50',''),
(70,'admin','新建任务','127.0.0.1','2026-09-03 15:12:50',''),
(71,'admin','新建任务','127.0.0.1','2026-09-03 15:12:50',''),
(72,'admin','新建任务','127.0.0.1','2026-09-03 15:12:50',''),
(73,'admin','新建任务','127.0.0.1','2026-09-03 15:13:03',''),
(74,'admin','新建任务','127.0.0.1','2026-09-03 15:13:03',''),
(75,'admin','用户登录','127.0.0.1','2026-09-03 15:13:47',''),
(76,'admin','任务批量加终端','127.0.0.1','2026-09-03 15:14:09',''),
(77,'admin','用户登录','127.0.0.1','2026-09-03 15:15:01',''),
(78,'admin','用户登录','127.0.0.1','2026-09-03 15:16:14',''),
(79,'admin','用户登录','127.0.0.1','2026-09-03 15:17:04',''),
(80,'admin','任务批量加终端','127.0.0.1','2026-09-03 15:17:26',''),
(81,'admin','用户登录','127.0.0.1','2026-09-03 15:17:52',''),
(82,'admin','任务批量加终端','127.0.0.1','2026-09-03 15:18:14',''),
(83,'admin','用户登录','127.0.0.1','2026-09-03 15:19:04',''),
(84,'admin','用户登录','127.0.0.1','2026-09-03 15:21:16',''),
(85,'admin','用户登录','127.0.0.1','2026-09-03 15:30:28',''),
(86,'admin','用户登录','127.0.0.1','2026-09-03 15:36:17',''),
(87,'admin','用户登录','127.0.0.1','2026-09-03 15:38:19',''),
(88,'admin','用户登录','127.0.0.1','2026-09-03 15:40:50',''),
(89,'admin','用户登录','127.0.0.1','2026-09-03 15:44:09',''),
(90,'admin','用户登录','127.0.0.1','2026-09-03 15:46:16',''),
(91,'admin','用户登录','127.0.0.1','2026-09-03 15:48:40',''),
(92,'admin','用户登录','127.0.0.1','2026-09-03 16:01:32',''),
(93,'admin','用户登录','127.0.0.1','2026-09-03 16:02:35',''),
(94,'admin','用户登录','127.0.0.1','2026-09-03 16:03:26',''),
(95,'admin','用户登录','127.0.0.1','2026-09-03 16:07:10',''),
(96,'admin','用户登录','127.0.0.1','2026-09-03 16:09:01',''),
(97,'admin','用户登录','127.0.0.1','2026-09-03 16:11:41',''),
(98,'admin','用户登录','127.0.0.1','2026-09-03 16:12:49',''),
(99,'admin','用户登录','127.0.0.1','2026-09-03 16:13:57',''),
(100,'admin','用户登录','127.0.0.1','2026-09-03 16:15:38',''),
(101,'admin','用户登录','127.0.0.1','2026-09-03 16:18:55',''),
(102,'admin','用户登录','127.0.0.1','2026-09-03 16:21:32',''),
(103,'admin','用户登录','127.0.0.1','2026-09-03 16:37:45',''),
(104,'admin','用户登录','127.0.0.1','2026-09-03 16:38:55',''),
(105,'admin','用户登录','127.0.0.1','2026-09-03 16:41:29',''),
(106,'admin','用户登录','127.0.0.1','2026-09-03 16:43:58',''),
(107,'admin','用户登录','127.0.0.1','2026-09-03 17:10:14',''),
(108,'admin','上传媒体','127.0.0.1','2026-09-03 17:10:31',''),
(109,'admin','上传媒体','127.0.0.1','2026-09-03 17:10:31',''),
(110,'admin','上传媒体','127.0.0.1','2026-09-03 17:10:31',''),
(111,'admin','上传媒体','127.0.0.1','2026-09-03 17:10:31',''),
(112,'admin','上传媒体','127.0.0.1','2026-09-03 17:10:31',''),
(113,'admin','上传媒体','127.0.0.1','2026-09-03 17:10:31',''),
(114,'admin','上传媒体','127.0.0.1','2026-09-03 17:10:31',''),
(115,'admin','上传媒体','127.0.0.1','2026-09-03 17:10:31',''),
(116,'admin','上传媒体','127.0.0.1','2026-09-03 17:10:44',''),
(117,'admin','用户登录','127.0.0.1','2026-09-03 17:12:41',''),
(118,'admin','用户登录','127.0.0.1','2026-09-03 17:13:07',''),
(119,'admin','上传媒体','127.0.0.1','2026-09-03 17:13:20',''),
(120,'admin','删除媒体','127.0.0.1','2026-09-03 17:13:50',''),
(121,'admin','用户登录','127.0.0.1','2026-09-03 17:15:10',''),
(122,'admin','用户登录','127.0.0.1','2026-09-03 17:17:38',''),
(123,'admin','用户登录','127.0.0.1','2026-09-03 18:01:01',''),
(124,'admin','上传媒体','127.0.0.1','2026-09-03 18:01:29',''),
(125,'admin','用户登录','127.0.0.1','2026-09-03 18:02:11',''),
(126,'admin','上传媒体','127.0.0.1','2026-09-03 18:02:37',''),
(127,'admin','用户登录','127.0.0.1','2026-09-03 18:02:52',''),
(128,'admin','删除媒体','127.0.0.1','2026-09-03 18:02:52',''),
(129,'admin','用户登录','127.0.0.1','2026-09-03 18:03:03',''),
(130,'admin','用户登录','127.0.0.1','2026-09-03 18:04:27',''),
(131,'admin','用户登录','127.0.0.1','2026-09-03 18:06:45',''),
(132,'admin','用户登录','127.0.0.1','2026-09-03 18:08:58',''),
(133,'admin','用户登录','127.0.0.1','2026-09-04 09:09:12',''),
(134,'admin','上传媒体','127.0.0.1','2026-09-04 09:09:25',''),
(135,'admin','上传媒体','127.0.0.1','2026-09-04 09:09:25',''),
(136,'admin','上传媒体','127.0.0.1','2026-09-04 09:09:25',''),
(137,'admin','上传媒体','127.0.0.1','2026-09-04 09:09:25',''),
(138,'admin','上传媒体','127.0.0.1','2026-09-04 09:09:26',''),
(139,'admin','删除媒体','127.0.0.1','2026-09-04 09:09:49',''),
(140,'admin','用户登录','127.0.0.1','2026-09-04 09:22:35',''),
(141,'admin','上传媒体','127.0.0.1','2026-09-04 09:23:10',''),
(142,'admin','上传媒体','127.0.0.1','2026-09-04 09:23:10',''),
(143,'admin','新建报警分区','127.0.0.1','2026-09-04 09:23:54',''),
(144,'admin','新建报警映射','127.0.0.1','2026-09-04 09:24:09',''),
(145,'admin','用户登录','127.0.0.1','2026-09-04 09:25:49',''),
(146,'admin','用户登录','127.0.0.1','2026-09-04 09:26:48',''),
(147,'admin','新建媒体目录','127.0.0.1','2026-09-04 09:26:58',''),
(148,'admin','删除媒体目录','127.0.0.1','2026-09-04 09:28:19',''),
(149,'admin','用户登录','127.0.0.1','2026-09-04 09:28:54',''),
(150,'admin','用户登录','127.0.0.1','2026-09-04 09:31:34',''),
(151,'admin','用户登录','127.0.0.1','2026-09-04 09:38:32',''),
(152,'admin','新建报警映射','127.0.0.1','2026-09-04 09:38:32',''),
(153,'admin','用户登录','127.0.0.1','2026-09-04 09:39:01',''),
(154,'admin','用户登录','127.0.0.1','2026-09-04 09:39:56',''),
(155,'admin','用户登录','127.0.0.1','2026-09-04 09:40:54',''),
(156,'admin','用户登录','127.0.0.1','2026-09-04 09:41:52',''),
(157,'admin','取消报警映射','127.0.0.1','2026-09-04 09:42:16',''),
(158,'admin','用户登录','127.0.0.1','2026-09-04 09:43:04',''),
(159,'admin','用户登录','127.0.0.1','2026-09-04 09:45:23',''),
(160,'admin','用户登录','127.0.0.1','2026-09-04 09:59:08',''),
(161,'admin','新建遥控任务','127.0.0.1','2026-09-04 09:59:21',''),
(162,'admin','新建遥控任务','127.0.0.1','2026-09-04 09:59:21',''),
(163,'admin','用户登录','127.0.0.1','2026-09-04 10:00:41',''),
(164,'admin','用户登录','127.0.0.1','2026-09-04 10:01:40',''),
(165,'admin','删除遥控任务','127.0.0.1','2026-09-04 10:02:12',''),
(166,'admin','用户登录','127.0.0.1','2026-09-04 10:02:16',''),
(167,'admin','新建遥控任务','127.0.0.1','2026-09-04 10:02:31',''),
(168,'admin','删除遥控任务','127.0.0.1','2026-09-04 10:02:57',''),
(169,'admin','用户登录','127.0.0.1','2026-09-04 10:03:04',''),
(170,'admin','新建遥控任务','127.0.0.1','2026-09-04 10:03:18',''),
(171,'admin','用户登录','127.0.0.1','2026-09-04 10:04:52',''),
(172,'admin','用户登录','127.0.0.1','2026-09-04 10:07:27',''),
(173,'admin','用户登录','127.0.0.1','2026-09-04 10:49:04',''),
(174,'admin','用户登录','127.0.0.1','2026-09-04 10:49:56',''),
(175,'admin','用户登录','127.0.0.1','2026-09-04 10:50:27',''),
(176,'admin','用户登录','127.0.0.1','2026-09-04 10:52:08',''),
(177,'admin','用户登录','127.0.0.1','2026-09-04 10:54:31',''),
(178,'admin','用户登录','127.0.0.1','2026-09-04 13:30:59',''),
(179,'admin','用户登录','127.0.0.1','2026-09-04 13:31:48',''),
(180,'admin','新建作息方案','127.0.0.1','2026-09-04 13:31:57',''),
(181,'admin','新增打铃条目','127.0.0.1','2026-09-04 13:32:03',''),
(182,'admin','新增打铃条目','127.0.0.1','2026-09-04 13:32:08',''),
(183,'admin','修改作息方案','127.0.0.1','2026-09-04 13:32:08',''),
(184,'admin','用户登录','127.0.0.1','2026-09-04 13:32:51',''),
(185,'admin','修改打铃条目','127.0.0.1','2026-09-04 13:33:00',''),
(186,'admin','删除打铃条目','127.0.0.1','2026-09-04 13:33:06',''),
(187,'admin','新增打铃条目','127.0.0.1','2026-09-04 13:33:11',''),
(188,'admin','修改作息方案','127.0.0.1','2026-09-04 13:33:11',''),
(189,'admin','用户登录','127.0.0.1','2026-09-04 13:35:34',''),
(190,'admin','删除打铃条目','127.0.0.1','2026-09-04 13:35:45',''),
(191,'admin','用户登录','127.0.0.1','2026-09-04 13:39:12',''),
(192,'admin','新建作息方案','127.0.0.1','2026-09-04 13:39:21',''),
(193,'admin','新增打铃条目','127.0.0.1','2026-09-04 13:39:27',''),
(194,'admin','新增打铃条目','127.0.0.1','2026-09-04 13:39:32',''),
(195,'admin','修改作息方案','127.0.0.1','2026-09-04 13:39:32',''),
(196,'admin','用户登录','127.0.0.1','2026-09-04 13:39:41',''),
(197,'admin','修改打铃条目','127.0.0.1','2026-09-04 13:39:50',''),
(198,'admin','删除打铃条目','127.0.0.1','2026-09-04 13:39:56',''),
(199,'admin','新增打铃条目','127.0.0.1','2026-09-04 13:40:02',''),
(200,'admin','修改作息方案','127.0.0.1','2026-09-04 13:40:02',''),
(201,'admin','用户登录','127.0.0.1','2026-09-04 13:40:07',''),
(202,'admin','删除打铃条目','127.0.0.1','2026-09-04 13:40:19',''),
(203,'admin','用户登录','127.0.0.1','2026-09-04 14:01:38',''),
(204,'admin','新建作息方案','127.0.0.1','2026-09-04 14:01:46',''),
(205,'admin','新增打铃条目','127.0.0.1','2026-09-04 14:01:55',''),
(206,'admin','新增打铃条目','127.0.0.1','2026-09-04 14:01:57',''),
(207,'admin','用户登录','127.0.0.1','2026-09-04 14:04:20',''),
(208,'admin','新建作息方案','127.0.0.1','2026-09-04 14:04:28',''),
(209,'admin','用户登录','127.0.0.1','2026-09-04 14:05:04',''),
(210,'admin','用户登录','127.0.0.1','2026-09-04 14:06:07',''),
(211,'admin','新建作息方案','127.0.0.1','2026-09-04 14:06:15',''),
(212,'admin','新增打铃条目','127.0.0.1','2026-09-04 14:06:23',''),
(213,'admin','新增打铃条目','127.0.0.1','2026-09-04 14:06:26',''),
(214,'admin','用户登录','127.0.0.1','2026-09-04 14:07:49',''),
(215,'admin','用户登录','127.0.0.1','2026-09-04 14:09:21',''),
(216,'admin','新建作息方案','127.0.0.1','2026-09-04 14:09:29',''),
(217,'admin','新增打铃条目','127.0.0.1','2026-09-04 14:09:37',''),
(218,'admin','新增打铃条目','127.0.0.1','2026-09-04 14:09:39',''),
(219,'admin','修改打铃条目','127.0.0.1','2026-09-04 14:09:53',''),
(220,'admin','修改打铃条目','127.0.0.1','2026-09-04 14:09:53',''),
(221,'admin','修改打铃条目','127.0.0.1','2026-09-04 14:09:53',''),
(222,'admin','用户登录','127.0.0.1','2026-09-04 14:10:28',''),
(223,'admin','用户登录','127.0.0.1','2026-09-04 14:12:13',''),
(224,'admin','用户登录','127.0.0.1','2026-09-04 14:12:39',''),
(225,'admin','修改作息方案','127.0.0.1','2026-09-04 14:12:51',''),
(226,'admin','用户登录','127.0.0.1','2026-09-04 14:56:42',''),
(227,'admin','修改打铃条目日期','127.0.0.1','2026-09-04 14:56:53',''),
(228,'admin','用户登录','127.0.0.1','2026-09-04 14:57:14',''),
(229,'admin','修改打铃条目日期','127.0.0.1','2026-09-04 14:57:25',''),
(230,'admin','用户登录','127.0.0.1','2026-09-04 14:58:00',''),
(231,'admin','新建作息方案','127.0.0.1','2026-09-04 14:58:10',''),
(232,'admin','新增打铃条目','127.0.0.1','2026-09-04 14:58:15',''),
(233,'admin','用户登录','127.0.0.1','2026-09-04 14:58:53',''),
(234,'admin','修改打铃条目日期','127.0.0.1','2026-09-04 14:59:03',''),
(235,'admin','用户登录','127.0.0.1','2026-09-04 15:00:21',''),
(236,'admin','修改打铃条目日期','127.0.0.1','2026-09-04 15:00:31',''),
(237,'admin','用户登录','127.0.0.1','2026-09-04 15:01:41',''),
(238,'admin','修改打铃条目日期','127.0.0.1','2026-09-04 15:01:52',''),
(239,'admin','用户登录','127.0.0.1','2026-09-04 15:02:27',''),
(240,'admin','用户登录','127.0.0.1','2026-09-04 15:02:41',''),
(241,'admin','用户登录','127.0.0.1','2026-09-04 15:13:30',''),
(242,'admin','修改打铃条目排期','127.0.0.1','2026-09-04 15:13:42',''),
(243,'admin','用户登录','127.0.0.1','2026-09-04 15:15:10',''),
(244,'admin','修改打铃条目排期','127.0.0.1','2026-09-04 15:15:22',''),
(245,'admin','修改打铃条目排期','127.0.0.1','2026-09-04 15:15:25',''),
(246,'admin','用户登录','127.0.0.1','2026-09-04 15:15:52',''),
(247,'admin','修改打铃条目排期','127.0.0.1','2026-09-04 15:16:08',''),
(248,'admin','用户登录','127.0.0.1','2026-09-04 15:28:02',''),
(249,'admin','用户登录','127.0.0.1','2026-09-04 15:36:44',''),
(250,'admin','新建作息方案','127.0.0.1','2026-09-04 15:37:04',''),
(251,'admin','修改作息方案','127.0.0.1','2026-09-04 15:37:16',''),
(252,'admin','修改作息方案','127.0.0.1','2026-09-04 15:37:16',''),
(253,'admin','修改作息方案','127.0.0.1','2026-09-04 15:37:31',''),
(254,'admin','新增打铃条目','127.0.0.1','2026-09-04 15:37:31',''),
(255,'admin','复制作息方案','127.0.0.1','2026-09-04 15:37:31',''),
(256,'admin','删除打铃条目','127.0.0.1','2026-09-04 15:37:43',''),
(257,'admin','删除作息方案','127.0.0.1','2026-09-04 15:37:43',''),
(258,'admin','删除作息方案','127.0.0.1','2026-09-04 15:37:43',''),
(259,'admin','用户登录','127.0.0.1','2026-09-04 15:38:24',''),
(260,'admin','新建作息方案','127.0.0.1','2026-09-04 15:38:24',''),
(261,'admin','删除作息方案','127.0.0.1','2026-09-04 15:38:24',''),
(262,'admin','用户登录','127.0.0.1','2026-09-04 15:41:19',''),
(263,'admin','新建作息方案','127.0.0.1','2026-09-04 15:41:30',''),
(264,'admin','用户登录','127.0.0.1','2026-09-04 15:42:36',''),
(265,'admin','用户登录','127.0.0.1','2026-09-04 15:43:10',''),
(266,'admin','用户登录','127.0.0.1','2026-09-04 15:43:41',''),
(267,'admin','用户登录','127.0.0.1','2026-09-04 15:44:11',''),
(268,'admin','用户登录','127.0.0.1','2026-09-04 15:44:52',''),
(269,'admin','删除作息方案','127.0.0.1','2026-09-04 15:45:44',''),
(270,'admin','用户登录','127.0.0.1','2026-09-04 15:59:20',''),
(271,'admin','新建作息方案','127.0.0.1','2026-09-04 15:59:20',''),
(272,'admin','删除作息方案','127.0.0.1','2026-09-04 15:59:20',''),
(273,'admin','用户登录','127.0.0.1','2026-09-04 15:59:33',''),
(274,'admin','用户登录','127.0.0.1','2026-09-04 16:00:00',''),
(275,'admin','用户登录','127.0.0.1','2026-09-04 16:12:08',''),
(276,'admin','新建LED设备','127.0.0.1','2026-09-04 16:12:08',''),
(277,'admin','用户登录','127.0.0.1','2026-09-04 16:12:37',''),
(278,'admin','用户登录','127.0.0.1','2026-09-04 16:12:59',''),
(279,'admin','用户登录','127.0.0.1','2026-09-04 16:14:52',''),
(280,'admin','用户登录','127.0.0.1','2026-09-04 16:15:27',''),
(281,'admin','用户登录','127.0.0.1','2026-09-04 16:16:03',''),
(282,'admin','新建任务','127.0.0.1','2026-09-04 16:16:19',''),
(283,'admin','用户登录','127.0.0.1','2026-09-04 16:17:25',''),
(284,'admin','用户登录','127.0.0.1','2026-09-04 16:18:13',''),
(285,'admin','修改任务','127.0.0.1','2026-09-04 16:18:24',''),
(286,'admin','用户登录','127.0.0.1','2026-09-04 16:20:10',''),
(287,'admin','修改任务','127.0.0.1','2026-09-04 16:20:20',''),
(288,'admin','用户登录','127.0.0.1','2026-09-04 16:20:50',''),
(289,'admin','新建任务','127.0.0.1','2026-09-04 16:21:05',''),
(290,'admin','删除任务','127.0.0.1','2026-09-04 16:21:30',''),
(291,'admin','删除任务','127.0.0.1','2026-09-04 16:21:30',''),
(292,'admin','用户登录','127.0.0.1','2026-09-04 16:43:14',''),
(293,'admin','用户登录','127.0.0.1','2026-09-04 16:44:13',''),
(294,'admin','用户登录','127.0.0.1','2026-09-04 16:44:37',''),
(295,'admin','用户登录','127.0.0.1','2026-09-04 16:45:59',''),
(296,'admin','用户登录','127.0.0.1','2026-09-04 17:14:03',''),
(297,'admin','用户登录','127.0.0.1','2026-09-04 17:16:24',''),
(298,'admin','用户登录','127.0.0.1','2026-09-04 17:20:09',''),
(299,'admin','用户登录','127.0.0.1','2026-09-04 17:20:38',''),
(300,'admin','用户登录','127.0.0.1','2026-09-04 17:21:21',''),
(301,'admin','用户登录','127.0.0.1','2026-09-04 17:21:48',''),
(302,'admin','用户登录','127.0.0.1','2026-09-04 17:22:44',''),
(303,'admin','用户登录','127.0.0.1','2026-09-04 17:34:59',''),
(304,'admin','用户登录','127.0.0.1','2026-09-04 17:35:16',''),
(305,'admin','用户登录','127.0.0.1','2026-09-04 17:42:26',''),
(306,'admin','用户登录','127.0.0.1','2026-09-04 17:43:07',''),
(307,'admin','用户登录','127.0.0.1','2026-09-04 18:23:32',''),
(308,'admin','用户登录','127.0.0.1','2026-09-04 18:24:08',''),
(309,'admin','用户登录','127.0.0.1','2026-09-04 18:25:52',''),
(310,'admin','新建任务','127.0.0.1','2026-09-04 18:25:52',''),
(311,'admin','新建任务','127.0.0.1','2026-09-04 18:26:07',''),
(312,'admin','新建LED目录','127.0.0.1','2026-09-04 18:26:07',''),
(313,'admin','新建任务','127.0.0.1','2026-09-04 18:26:32',''),
(314,'admin','复制LED目录','127.0.0.1','2026-09-04 18:26:51',''),
(315,'admin','删除LED目录','127.0.0.1','2026-09-04 18:26:59',''),
(316,'admin','删除任务','127.0.0.1','2026-09-04 18:26:59',''),
(317,'admin','删除任务','127.0.0.1','2026-09-04 18:26:59',''),
(318,'admin','删除任务','127.0.0.1','2026-09-04 18:26:59',''),
(319,'admin','用户登录','127.0.0.1','2026-09-04 18:34:44',''),
(320,'admin','新建启用计划','127.0.0.1','2026-09-04 18:34:44',''),
(321,'admin','修改启用计划','127.0.0.1','2026-09-04 18:34:54',''),
(322,'admin','删除启用计划','127.0.0.1','2026-09-04 18:34:54',''),
(323,'admin','用户登录','127.0.0.1','2026-09-04 18:36:50',''),
(324,'admin','新建启用计划','127.0.0.1','2026-09-04 18:36:59',''),
(325,'admin','用户登录','127.0.0.1','2026-09-04 18:40:56',''),
(326,'admin','新建噪声设备','127.0.0.1','2026-09-04 18:41:22',''),
(327,'admin','新建声场分区','127.0.0.1','2026-09-04 18:41:22',''),
(328,'admin','用户登录','127.0.0.1','2026-09-04 18:42:06',''),
(329,'admin','删除声场分区','127.0.0.1','2026-09-04 18:42:32',''),
(330,'admin','删除噪声设备','127.0.0.1','2026-09-04 18:42:32',''),
(331,'admin','用户登录','127.0.0.1','2026-09-04 18:48:17',''),
(332,'admin','用户登录','127.0.0.1','2026-09-04 18:48:37',''),
(333,'admin','用户登录','127.0.0.1','2026-09-04 18:49:00',''),
(334,'admin','用户登录','127.0.0.1','2026-09-04 18:50:06',''),
(335,'admin','用户登录','127.0.0.1','2026-09-04 18:51:39',''),
(336,'admin','用户登录','127.0.0.1','2026-09-05 08:38:04',''),
(337,'admin','新建任务','127.0.0.1','2026-09-05 08:38:45',''),
(338,'admin','用户登录','127.0.0.1','2026-09-05 08:39:11',''),
(339,'admin','用户登录','127.0.0.1','2026-09-05 08:40:22',''),
(340,'admin','修改任务','127.0.0.1','2026-09-05 08:40:35',''),
(341,'admin','用户登录','127.0.0.1','2026-09-05 08:40:55',''),
(342,'admin','删除任务','127.0.0.1','2026-09-05 08:41:52',''),
(343,'admin','用户登录','127.0.0.1','2026-09-05 08:42:40',''),
(344,'admin','用户登录','127.0.0.1','2026-09-05 08:59:08',''),
(345,'admin','修改任务','127.0.0.1','2026-09-05 08:59:31',''),
(346,'admin','修改任务','127.0.0.1','2026-09-05 08:59:31',''),
(347,'admin','用户登录','127.0.0.1','2026-09-05 09:00:14',''),
(348,'admin','用户登录','127.0.0.1','2026-09-05 09:01:47',''),
(349,'admin','用户登录','127.0.0.1','2026-09-05 09:02:49',''),
(350,'admin','用户登录','127.0.0.1','2026-09-05 09:16:04',''),
(351,'admin','用户登录','127.0.0.1','2026-09-05 09:19:24',''),
(352,'admin','新建任务','127.0.0.1','2026-09-05 09:19:54',''),
(353,'admin','用户登录','127.0.0.1','2026-09-05 09:20:14',''),
(354,'admin','用户登录','127.0.0.1','2026-09-05 09:20:45',''),
(355,'admin','删除任务','127.0.0.1','2026-09-05 09:21:13',''),
(356,'admin','用户登录','127.0.0.1','2026-09-05 09:21:49',''),
(357,'admin','用户登录','127.0.0.1','2026-09-05 09:48:25',''),
(358,'admin','用户登录','127.0.0.1','2026-09-05 09:50:17',''),
(359,'admin','用户登录','127.0.0.1','2026-09-05 09:52:51',''),
(360,'admin','用户登录','127.0.0.1','2026-09-05 09:53:13',''),
(361,'admin','新建任务','127.0.0.1','2026-09-05 09:53:13',''),
(362,'admin','删除任务','127.0.0.1','2026-09-05 09:53:22',''),
(363,'admin','用户登录','127.0.0.1','2026-09-05 09:53:59',''),
(364,'admin','用户登录','127.0.0.1','2026-09-05 10:27:16',''),
(365,'admin','用户登录','127.0.0.1','2026-09-05 10:43:55',''),
(366,'admin','用户登录','127.0.0.1','2026-09-05 10:47:14',''),
(367,'admin','用户登录','127.0.0.1','2026-09-05 10:51:49',''),
(370,'admin','用户登录','127.0.0.1','2026-09-05 10:54:44',''),
(371,'admin','用户登录','127.0.0.1','2026-09-05 11:21:20',''),
(372,'operator','用户登录','127.0.0.1','2026-09-05 11:21:37',''),
(373,'operator','用户登录','127.0.0.1','2026-09-05 11:21:54',''),
(374,'operator','删除任务','127.0.0.1','2026-09-05 11:21:54',''),
(375,'operator','删除任务','127.0.0.1','2026-09-05 11:21:54',''),
(376,'operator','删除任务','127.0.0.1','2026-09-05 11:21:54',''),
(377,'operator','删除启用计划','127.0.0.1','2026-09-05 11:21:54',''),
(378,'viewer','用户登录','127.0.0.1','2026-09-05 11:22:05',''),
(379,'viewer','删除任务','127.0.0.1','2026-09-05 11:22:05',''),
(380,'admin','用户登录','127.0.0.1','2026-09-05 11:22:57',''),
(381,'admin','新建用户组','127.0.0.1','2026-09-05 11:23:06',''),
(382,'admin','修改用户组','127.0.0.1','2026-09-05 11:23:10',''),
(383,'admin','用户登录','127.0.0.1','2026-09-05 11:24:43',''),
(384,'operator','用户登录','127.0.0.1','2026-09-05 11:25:00',''),
(385,'admin','用户登录','127.0.0.1','2026-09-05 11:25:59',''),
(386,'admin','用户登录','127.0.0.1','2026-09-05 11:45:13',''),
(387,'operator','用户登录','127.0.0.1','2026-09-05 11:45:13',''),
(388,'viewer','用户登录','127.0.0.1','2026-09-05 11:45:14',''),
(389,'viewer','用户登录','127.0.0.1','2026-09-05 11:45:38',''),
(390,'viewer','删除媒体','127.0.0.1','2026-09-05 11:45:38',''),
(391,'viewer','用户登录','127.0.0.1','2026-09-05 11:46:09',''),
(392,'viewer','删除遥控任务','127.0.0.1','2026-09-05 11:46:09',''),
(393,'admin','用户登录','127.0.0.1','2026-09-05 11:46:09',''),
(394,'admin','删除遥控任务','127.0.0.1','2026-09-05 11:46:09',''),
(395,'admin','用户登录','127.0.0.1','2026-09-05 11:46:30',''),
(396,'admin','用户登录','127.0.0.1','2026-09-05 11:46:56',''),
(397,'admin','修改用户组','127.0.0.1','2026-09-05 11:46:56',''),
(398,'admin','新建用户组','127.0.0.1','2026-09-05 11:46:56',''),
(399,'admin','用户登录','127.0.0.1','2026-09-05 11:49:28',''),
(400,'admin','修改用户组','127.0.0.1','2026-09-05 11:49:37',''),
(401,'admin','用户登录','127.0.0.1','2026-09-05 11:50:07',''),
(402,'admin','修改用户组','127.0.0.1','2026-09-05 11:50:15',''),
(403,'admin','用户登录','127.0.0.1','2026-09-05 11:51:35',''),
(404,'operator','用户登录','127.0.0.1','2026-09-05 11:51:36',''),
(405,'viewer','用户登录','127.0.0.1','2026-09-05 11:51:36',''),
(406,'admin','用户登录','127.0.0.1','2026-09-05 11:51:58',''),
(407,'operator','用户登录','127.0.0.1','2026-09-05 11:52:15',''),
(408,'admin','用户登录','127.0.0.1','2026-09-05 11:53:11',''),
(409,'admin','用户登录','127.0.0.1','2026-09-05 12:06:07',''),
(410,'admin','用户登录','127.0.0.1','2026-09-05 12:07:22',''),
(411,'admin','用户登录','127.0.0.1','2026-09-05 12:10:12',''),
(412,'admin','用户登录','127.0.0.1','2026-09-05 12:12:05',''),
(413,'admin','用户登录','127.0.0.1','2026-09-05 12:13:00',''),
(414,'admin','用户登录','127.0.0.1','2026-09-05 12:13:59',''),
(415,'admin','用户登录','127.0.0.1','2026-09-05 12:14:12',''),
(416,'admin','用户登录','127.0.0.1','2026-09-05 12:14:42',''),
(417,'admin','用户登录','127.0.0.1','2026-09-05 12:14:51',''),
(418,'admin','用户登录','127.0.0.1','2026-09-05 12:15:14',''),
(419,'admin','用户登录','127.0.0.1','2026-09-05 12:15:24',''),
(420,'admin','用户登录','127.0.0.1','2026-09-05 12:15:47',''),
(421,'admin','用户登录','127.0.0.1','2026-09-05 12:16:30',''),
(422,'admin','用户登录','127.0.0.1','2026-09-05 12:17:31',''),
(423,'admin','用户登录','127.0.0.1','2026-09-05 12:17:50',''),
(424,'admin','用户登录','127.0.0.1','2026-09-05 12:18:01',''),
(425,'admin','用户登录','127.0.0.1','2026-09-05 12:20:08',''),
(426,'admin','用户登录','127.0.0.1','2026-09-05 12:25:12',''),
(427,'admin','用户登录','127.0.0.1','2026-09-05 12:27:02',''),
(428,'admin','用户登录','127.0.0.1','2026-09-05 12:30:56',''),
(429,'admin','新建终端分区','127.0.0.1','2026-09-05 12:31:07',''),
(430,'admin','新建报警分区','127.0.0.1','2026-09-05 12:31:14',''),
(431,'admin','用户登录','127.0.0.1','2026-09-05 12:32:09',''),
(432,'admin','新建终端分区','127.0.0.1','2026-09-05 12:32:20',''),
(433,'admin','新建报警分区','127.0.0.1','2026-09-05 12:32:30',''),
(434,'admin','删除报警分区','127.0.0.1','2026-09-05 12:32:34',''),
(435,'admin','用户登录','127.0.0.1','2026-09-05 12:33:18',''),
(436,'admin','删除终端分区','127.0.0.1','2026-09-05 12:35:01',''),
(437,'admin','删除节假日','127.0.0.1','2026-09-05 12:35:01',''),
(438,'admin','删除遥控任务','127.0.0.1','2026-09-05 12:35:01',''),
(439,'admin','删除任务','127.0.0.1','2026-09-05 12:35:01',''),
(440,'admin','删除LED设备','127.0.0.1','2026-09-05 12:35:01',''),
(441,'admin','删除启用计划','127.0.0.1','2026-09-05 12:35:01',''),
(442,'admin','删除噪声设备','127.0.0.1','2026-09-05 12:35:01',''),
(443,'admin','删除声场分区','127.0.0.1','2026-09-05 12:35:01',''),
(444,'admin','用户登录','127.0.0.1','2026-09-05 12:35:09',''),
(445,'admin','新建报警分区','127.0.0.1','2026-09-05 12:35:55',''),
(446,'admin','删除报警分区','127.0.0.1','2026-09-05 12:35:59',''),
(447,'admin','用户登录','127.0.0.1','2026-09-05 12:36:25',''),
(448,'admin','新建报警分区','127.0.0.1','2026-09-05 12:36:33',''),
(449,'admin','删除报警分区','127.0.0.1','2026-09-05 12:36:37',''),
(450,'admin','用户登录','127.0.0.1','2026-09-05 12:37:26',''),
(451,'admin','新建报警分区','127.0.0.1','2026-09-05 12:37:33',''),
(452,'admin','删除报警分区','127.0.0.1','2026-09-05 12:37:38',''),
(453,'admin','用户登录','127.0.0.1','2026-09-05 12:38:17',''),
(454,'admin','新建报警分区','127.0.0.1','2026-09-05 12:38:24',''),
(455,'admin','删除报警分区','127.0.0.1','2026-09-05 12:38:29',''),
(456,'admin','用户登录','127.0.0.1','2026-09-05 12:38:58',''),
(457,'admin','新建报警分区','127.0.0.1','2026-09-05 12:39:06',''),
(458,'admin','删除报警分区','127.0.0.1','2026-09-05 12:39:11',''),
(459,'admin','用户登录','127.0.0.1','2026-09-05 12:39:55',''),
(460,'admin','新建终端分区','127.0.0.1','2026-09-05 12:40:03',''),
(461,'admin','删除终端分区','127.0.0.1','2026-09-05 12:40:07',''),
(462,'admin','新建报警分区','127.0.0.1','2026-09-05 12:40:13',''),
(463,'admin','删除报警分区','127.0.0.1','2026-09-05 12:40:16',''),
(464,'admin','新建节假日','127.0.0.1','2026-09-05 12:40:23',''),
(465,'admin','删除节假日','127.0.0.1','2026-09-05 12:40:27',''),
(466,'admin','新建声场分区','127.0.0.1','2026-09-05 12:40:39',''),
(467,'admin','删除声场分区','127.0.0.1','2026-09-05 12:40:43',''),
(468,'admin','用户登录','127.0.0.1','2026-09-05 12:41:04',''),
(469,'admin','新建终端分区','127.0.0.1','2026-09-05 12:41:12',''),
(470,'admin','删除终端分区','127.0.0.1','2026-09-05 12:41:16',''),
(471,'admin','新建报警分区','127.0.0.1','2026-09-05 12:41:22',''),
(472,'admin','删除报警分区','127.0.0.1','2026-09-05 12:41:26',''),
(473,'admin','新建节假日','127.0.0.1','2026-09-05 12:41:33',''),
(474,'admin','删除节假日','127.0.0.1','2026-09-05 12:41:37',''),
(475,'admin','新建噪声设备','127.0.0.1','2026-09-05 12:41:43',''),
(476,'admin','新建声场分区','127.0.0.1','2026-09-05 12:41:49',''),
(477,'admin','删除声场分区','127.0.0.1','2026-09-05 12:41:53',''),
(478,'admin','用户登录','127.0.0.1','2026-09-05 12:42:26',''),
(479,'admin','新建终端分区','127.0.0.1','2026-09-05 12:42:34',''),
(480,'admin','删除终端分区','127.0.0.1','2026-09-05 12:42:38',''),
(481,'admin','新建报警分区','127.0.0.1','2026-09-05 12:42:44',''),
(482,'admin','删除报警分区','127.0.0.1','2026-09-05 12:42:48',''),
(483,'admin','新建节假日','127.0.0.1','2026-09-05 12:42:54',''),
(484,'admin','删除节假日','127.0.0.1','2026-09-05 12:42:59',''),
(485,'admin','新建噪声设备','127.0.0.1','2026-09-05 12:43:05',''),
(486,'admin','删除噪声设备','127.0.0.1','2026-09-05 12:43:09',''),
(487,'admin','新建声场分区','127.0.0.1','2026-09-05 12:43:15',''),
(488,'admin','删除声场分区','127.0.0.1','2026-09-05 12:43:19',''),
(489,'admin','用户登录','127.0.0.1','2026-09-05 12:44:01',''),
(490,'admin','新建任务','127.0.0.1','2026-09-05 12:44:10',''),
(491,'admin','删除任务','127.0.0.1','2026-09-05 12:44:14',''),
(492,'admin','新建启用计划','127.0.0.1','2026-09-05 12:44:22',''),
(493,'admin','删除启用计划','127.0.0.1','2026-09-05 12:44:26',''),
(494,'admin','新建用户','127.0.0.1','2026-09-05 12:45:09',''),
(495,'admin','删除用户','127.0.0.1','2026-09-05 12:45:13',''),
(496,'admin','用户登录','127.0.0.1','2026-09-05 12:45:33',''),
(497,'admin','新建任务','127.0.0.1','2026-09-05 12:45:42',''),
(498,'admin','删除任务','127.0.0.1','2026-09-05 12:45:47',''),
(499,'admin','新建启用计划','127.0.0.1','2026-09-05 12:45:55',''),
(500,'admin','删除启用计划','127.0.0.1','2026-09-05 12:45:59',''),
(501,'admin','新建用户','127.0.0.1','2026-09-05 12:46:15',''),
(502,'admin','删除用户','127.0.0.1','2026-09-05 12:46:20',''),
(503,'admin','用户登录','127.0.0.1','2026-09-05 12:46:42',''),
(504,'admin','用户登录','127.0.0.1','2026-09-05 12:47:24',''),
(505,'admin','新建任务','127.0.0.1','2026-09-05 12:47:33',''),
(506,'admin','删除任务','127.0.0.1','2026-09-05 12:47:37',''),
(507,'admin','新建启用计划','127.0.0.1','2026-09-05 12:47:45',''),
(508,'admin','删除启用计划','127.0.0.1','2026-09-05 12:47:49',''),
(509,'admin','新建用户','127.0.0.1','2026-09-05 12:48:06',''),
(510,'admin','删除用户','127.0.0.1','2026-09-05 12:48:10',''),
(511,'admin','用户登录','127.0.0.1','2026-09-05 12:48:25',''),
(512,'admin','新建任务','127.0.0.1','2026-09-05 12:48:34',''),
(513,'admin','删除任务','127.0.0.1','2026-09-05 12:48:38',''),
(514,'admin','新建启用计划','127.0.0.1','2026-09-05 12:48:46',''),
(515,'admin','删除启用计划','127.0.0.1','2026-09-05 12:48:51',''),
(516,'admin','新建遥控任务','127.0.0.1','2026-09-05 12:48:59',''),
(517,'admin','删除遥控任务','127.0.0.1','2026-09-05 12:49:04',''),
(518,'admin','新建用户','127.0.0.1','2026-09-05 12:49:12',''),
(519,'admin','删除用户','127.0.0.1','2026-09-05 12:49:17',''),
(520,'admin','用户登录','127.0.0.1','2026-09-05 12:49:57',''),
(521,'admin','用户登录','127.0.0.1','2026-09-05 12:53:21',''),
(522,'admin','用户登录','127.0.0.1','2026-09-05 12:57:12',''),
(523,'admin','新建任务','127.0.0.1','2026-09-05 12:58:02',''),
(524,'admin','删除任务','127.0.0.1','2026-09-05 12:58:07',''),
(525,'admin','用户登录','127.0.0.1','2026-09-05 12:58:29',''),
(526,'admin','用户登录','127.0.0.1','2026-09-05 12:59:15',''),
(527,'admin','用户登录','127.0.0.1','2026-09-05 12:59:46',''),
(528,'admin','用户登录','127.0.0.1','2026-09-05 13:00:40',''),
(529,'admin','新建任务','127.0.0.1','2026-09-05 13:01:33',''),
(530,'admin','删除任务','127.0.0.1','2026-09-05 13:01:38',''),
(531,'admin','用户登录','127.0.0.1','2026-09-05 13:02:06',''),
(532,'admin','新建任务','127.0.0.1','2026-09-05 13:03:11',''),
(533,'admin','修改任务','127.0.0.1','2026-09-05 13:03:22',''),
(534,'admin','删除任务','127.0.0.1','2026-09-05 13:03:22',''),
(535,'admin','新建作息方案','127.0.0.1','2026-09-05 13:03:39',''),
(536,'admin','删除作息方案','127.0.0.1','2026-09-05 13:03:39',''),
(537,'admin','用户登录','127.0.0.1','2026-09-05 13:07:15',''),
(538,'admin','用户登录','127.0.0.1','2026-09-05 13:09:00',''),
(539,'admin','用户登录','127.0.0.1','2026-09-05 13:10:00',''),
(540,'admin','用户登录','127.0.0.1','2026-09-07 08:58:15',''),
(541,'admin','用户登录','127.0.0.1','2026-09-07 08:58:26',''),
(542,'admin','用户登录','127.0.0.1','2026-09-07 08:59:52',''),
(543,'admin','用户登录','127.0.0.1','2026-09-07 09:16:59',''),
(551,'admin','用户登录','127.0.0.1','2026-09-07 09:21:12',''),
(556,'admin','用户登录','127.0.0.1','2026-09-07 09:21:38',''),
(560,'admin','用户登录','127.0.0.1','2026-09-07 09:24:21',''),
(565,'admin','用户登录','127.0.0.1','2026-09-07 09:26:40',''),
(567,'admin','用户登录','127.0.0.1','2026-09-07 09:27:33',''),
(568,'admin','用户登录','127.0.0.1','2026-09-07 09:37:27',''),
(569,'admin','用户登录','127.0.0.1','2026-09-07 09:37:27',''),
(570,'-','用户登出','127.0.0.1','2026-09-07 09:37:27',''),
(571,'admin','用户登录','127.0.0.1','2026-09-07 09:38:17',''),
(586,'admin','用户登出','127.0.0.1','2026-09-07 09:39:40',''),
(587,'admin','用户登录','127.0.0.1','2026-09-07 09:40:16',''),
(588,'admin','用户登录','127.0.0.1','2026-09-07 09:41:48',''),
(592,'admin','用户登录','127.0.0.1','2026-09-07 09:42:16',''),
(595,'admin','用户登录','127.0.0.1','2026-09-07 09:42:44',''),
(596,'admin','用户登录','127.0.0.1','2026-09-07 09:43:34',''),
(597,'admin','用户登录','127.0.0.1','2026-09-07 10:07:13',''),
(598,'admin','用户登录','127.0.0.1','2026-09-07 10:07:18',''),
(599,'admin','用户登录','127.0.0.1','2026-09-07 10:07:24',''),
(600,'admin','新建用户组','127.0.0.1','2026-09-07 10:07:48',''),
(601,'admin','修改用户组','127.0.0.1','2026-09-07 10:08:01',''),
(602,'admin','修改用户组','127.0.0.1','2026-09-07 10:08:01',''),
(603,'admin','新建用户','127.0.0.1','2026-09-07 10:08:15',''),
(604,'zzled','用户登录','127.0.0.1','2026-09-07 10:08:16',''),
(605,'admin','修改用户组','127.0.0.1','2026-09-07 10:08:41',''),
(606,'zzled','用户登录','127.0.0.1','2026-09-07 10:08:41',''),
(607,'admin','新建任务','127.0.0.1','2026-09-07 10:09:09',''),
(608,'admin','修改任务','127.0.0.1','2026-09-07 10:09:20',''),
(609,'admin','修改任务','127.0.0.1','2026-09-07 10:09:21',''),
(610,'admin','新建任务','127.0.0.1','2026-09-07 10:09:33',''),
(611,'admin','修改任务','127.0.0.1','2026-09-07 10:09:50',''),
(612,'admin','修改任务','127.0.0.1','2026-09-07 10:09:50',''),
(613,'admin','修改任务','127.0.0.1','2026-09-07 10:10:05',''),
(614,'admin','修改任务','127.0.0.1','2026-09-07 10:10:05',''),
(615,'admin','新建任务','127.0.0.1','2026-09-07 10:10:54',''),
(616,'admin','修改任务','127.0.0.1','2026-09-07 10:11:05',''),
(617,'admin','用户登录','127.0.0.1','2026-09-07 10:12:41',''),
(618,'admin','用户登录','127.0.0.1','2026-09-07 10:13:39',''),
(619,'admin','新建任务','127.0.0.1','2026-09-07 10:13:49',''),
(620,'admin','用户登录','127.0.0.1','2026-09-07 10:14:15',''),
(621,'admin','修改任务','127.0.0.1','2026-09-07 10:14:24',''),
(622,'admin','删除任务','127.0.0.1','2026-09-07 10:14:38',''),
(623,'admin','删除任务','127.0.0.1','2026-09-07 10:14:38',''),
(624,'admin','删除任务','127.0.0.1','2026-09-07 10:14:38',''),
(625,'admin','删除用户','127.0.0.1','2026-09-07 10:14:38',''),
(626,'admin','删除用户组','127.0.0.1','2026-09-07 10:14:38',''),
(627,'admin','用户登录','127.0.0.1','2026-09-07 10:15:57',''),
(628,'admin','用户登录','127.0.0.1','2026-09-07 10:22:14','');
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `logincheck`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logincheck` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ip` varchar(32) DEFAULT NULL,
  `loginnum` int(10) DEFAULT NULL,
  `logintime` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `logincheck` WRITE;
/*!40000 ALTER TABLE `logincheck` DISABLE KEYS */;
/*!40000 ALTER TABLE `logincheck` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `logmedialist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logmedialist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mediaid` int(11) NOT NULL DEFAULT 0,
  `taskid` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `logmedialist` WRITE;
/*!40000 ALTER TABLE `logmedialist` DISABLE KEYS */;
/*!40000 ALTER TABLE `logmedialist` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `logtask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logtask` (
  `taskid` int(11) NOT NULL AUTO_INCREMENT,
  `taskname` varchar(255) NOT NULL DEFAULT '',
  `streamid` int(11) NOT NULL DEFAULT 0,
  `state` int(11) DEFAULT 0,
  `startdate` date NOT NULL DEFAULT '0000-00-00',
  `starttime` time NOT NULL DEFAULT '00:00:00',
  `timelength` time NOT NULL DEFAULT '00:00:00',
  `playmodel` int(11) NOT NULL DEFAULT 0,
  `priority` int(11) DEFAULT 1,
  PRIMARY KEY (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `logtask` WRITE;
/*!40000 ALTER TABLE `logtask` DISABLE KEYS */;
/*!40000 ALTER TABLE `logtask` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT 'none' COMMENT '媒体文件上传前的名称',
  `size` int(8) unsigned DEFAULT 0 COMMENT '媒体文件大小',
  `typeid` varchar(30) NOT NULL DEFAULT '' COMMENT '媒体文件类型',
  `priority` int(8) unsigned NOT NULL DEFAULT 0 COMMENT '此字段没被使用',
  `filename` varchar(255) NOT NULL DEFAULT 'none' COMMENT '上传后的媒体名称',
  `folderid` int(10) unsigned DEFAULT 0 COMMENT '上传到文件夹',
  `timelength` int(4) unsigned DEFAULT 0 COMMENT '媒体播放长度',
  `channel` int(10) unsigned DEFAULT 0 COMMENT '媒体播放通道',
  `sample` int(10) unsigned DEFAULT 0 COMMENT '媒体采样率',
  `bitrate` int(10) unsigned DEFAULT 128000 COMMENT '媒体比特率',
  `codecid` int(10) unsigned DEFAULT 0,
  `offlinestate` int(10) DEFAULT 0 COMMENT '0=非离线,1=空闲离线,2=立即离线,3=离线完成,4=空闲删除,5=立即删除,6=正在空闲离线,7=正在立即离线',
  `userid` int(4) DEFAULT 0 COMMENT '用户ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=204 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
INSERT INTO `media` (`id`, `name`, `size`, `typeid`, `priority`, `filename`, `folderid`, `timelength`, `channel`, `sample`, `bitrate`, `codecid`, `offlinestate`, `userid`) VALUES (1,'经典提示音',61,'mp3',0,'/backup/mediadata/1617155672594644.mp3',9,5,2,44100,128000,86017,0,0),
(124,'大课间.mp3',6018,'mp3',0,'/backup/mediadata/17768378225426810.mp3',2,385,2,44100,128000,0,0,1),
(125,'10.起床号.mp3',1406,'mp3',0,'/backup/mediadata/17768378228949191.mp3',2,89,2,44100,128000,0,0,1),
(126,'04.爱的纪念（下课）.mp3',350,'mp3',0,'/backup/mediadata/17768378225900880.mp3',2,22,2,44100,128000,0,0,1),
(127,'出旗曲.mp3',2630,'mp3',0,'/backup/mediadata/17768378222567017.mp3',2,168,2,44100,128000,0,0,1),
(128,'04.爱的纪念（上课）.mp3',345,'mp3',0,'/backup/mediadata/17768378222714337.mp3',2,22,2,44100,128000,0,0,1),
(129,'电铃声.mp3',278,'mp3',0,'/backup/mediadata/17768378225143403.mp3',2,17,2,44100,128000,0,0,1),
(130,'上课铃.mp3',289,'mp3',0,'/backup/mediadata/17768378227150187.mp3',2,18,2,44100,128000,0,0,1),
(131,'放学.mp3',828,'mp3',0,'/backup/mediadata/17768378226406700.mp3',2,42,2,44100,160000,0,0,1),
(132,'午休.mp3',1184,'mp3',0,'/backup/mediadata/17768378222430988.mp3',2,60,2,44100,160000,0,0,1),
(133,'下课铃.mp3',310,'mp3',0,'/backup/mediadata/17768378229798306.mp3',2,19,2,44100,128000,0,0,1),
(134,'眼保健操.mp3',306,'mp3',0,'/backup/mediadata/17768378228339626.mp3',2,19,2,44100,128000,0,0,1),
(135,'预备铃.mp3',432,'mp3',0,'/backup/mediadata/17768378238107705.mp3',2,27,2,44100,128000,0,0,1),
(136,'运动员进行曲.mp3',2655,'mp3',0,'/backup/mediadata/17768378235315483.mp3',2,169,2,44100,128000,0,0,1),
(137,'早读预备铃.mp3',1565,'mp3',0,'/backup/mediadata/17768378237905625.mp3',2,100,2,44100,128000,0,0,1),
(138,'中华人民共和国国歌.mp3',723,'mp3',0,'/backup/mediadata/17768378231442225.mp3',2,46,2,44100,128000,0,0,1),
(143,'课间提醒',0,'tts',0,'tts',0,0,0,70007,21,0,0,0),
(144,'放学安全提示',0,'tts',0,'tts',0,0,0,70012,0,0,0,0),
(145,'防欺凌广播',0,'tts',0,'tts',0,0,0,70013,0,0,0,0),
(146,'欢迎标语',0,'tts',0,'tts',0,0,0,70016,0,0,0,0),
(147,'考试须知',0,'tts',0,'tts',0,0,0,70017,0,0,0,0),
(159,'火警疏散提示',79,'mp3',0,'/backup/mediadata/1788484989796608.mp3',4,0,2,44100,128000,0,0,1),
(160,'防空警报',79,'mp3',0,'/backup/mediadata/1788484990313412.mp3',4,0,2,44100,128000,0,0,1);
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `mediaoftask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mediaoftask` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mediaid` int(11) NOT NULL DEFAULT 0 COMMENT '添加文件广播和作息方案时添加媒体文件的ID',
  `taskid` int(11) NOT NULL DEFAULT 0 COMMENT '添加与媒体ID相关的任务id',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '媒体添加的顺序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `mediaoftask` WRITE;
/*!40000 ALTER TABLE `mediaoftask` DISABLE KEYS */;
INSERT INTO `mediaoftask` (`id`, `mediaid`, `taskid`, `sort`) VALUES (1,137,1001,1),
(2,130,1002,1),
(3,133,1003,1),
(4,134,1004,1),
(5,124,1005,1),
(6,132,1006,1),
(7,131,1007,1),
(9,129,1009,1),
(10,126,1010,1),
(11,124,1011,1),
(12,135,1012,1),
(13,136,1013,1),
(22,130,70006,0),
(23,143,70007,0),
(24,124,70008,0),
(25,126,70008,1),
(28,144,70012,1),
(29,145,70013,1),
(30,146,70016,1),
(31,147,70017,1),
(77,138,1008,0);
/*!40000 ALTER TABLE `mediaoftask` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `offlinemedia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offlinemedia` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT 'none' COMMENT '媒体文件上传前的名称',
  `size` int(8) unsigned DEFAULT 0 COMMENT '媒体文件大小',
  `typeid` varchar(30) NOT NULL DEFAULT '' COMMENT '媒体文件类型',
  `priority` int(8) unsigned NOT NULL DEFAULT 0 COMMENT '此字段没被使用',
  `filename` varchar(255) NOT NULL DEFAULT 'none' COMMENT '上传后的媒体名称',
  `folderid` int(10) unsigned DEFAULT 0 COMMENT '上传到文件夹',
  `timelength` int(4) unsigned DEFAULT 0 COMMENT '媒体播放长度',
  `channel` int(10) unsigned DEFAULT 0 COMMENT '媒体播放通道',
  `sample` int(10) unsigned DEFAULT 0 COMMENT '媒体采样率',
  `bitrate` int(10) unsigned DEFAULT 128000 COMMENT '媒体比特率',
  `codecid` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9003 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `offlinemedia` WRITE;
/*!40000 ALTER TABLE `offlinemedia` DISABLE KEYS */;
/*!40000 ALTER TABLE `offlinemedia` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `offlinemediaofterminal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offlinemediaofterminal` (
  `mediaid` int(4) NOT NULL,
  `terminalid` int(4) NOT NULL,
  `offlinestate` int(4) DEFAULT 0 COMMENT '0=非离线,1=空闲离线,2=立即离线,3=离线完成,4=空闲删除,5=立即删除,6=正在空闲离线,7=正在立即离线,8=删除完成，9=准备空闲传输，10=准备立即传输，11=停止传输，12=传输已停止',
  `taskid` int(4) NOT NULL DEFAULT 0,
  `sort` int(4) DEFAULT 0,
  PRIMARY KEY (`mediaid`,`terminalid`,`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `offlinemediaofterminal` WRITE;
/*!40000 ALTER TABLE `offlinemediaofterminal` DISABLE KEYS */;
/*!40000 ALTER TABLE `offlinemediaofterminal` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `offlinetask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offlinetask` (
  `taskid` int(11) NOT NULL,
  `taskname` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
  `israndomplay` int(11) NOT NULL DEFAULT 0 COMMENT '0表示随机1表示顺序',
  `projectstate` int(11) NOT NULL DEFAULT 0 COMMENT '方案是否启用，1启用，0停用',
  `timelengthtype` int(11) NOT NULL COMMENT '1为时间，0为循环次数',
  `timelength` int(11) NOT NULL COMMENT '播放时长， 类型为1是是播放秒数，否则为循环次数。',
  `prepower` int(11) NOT NULL COMMENT '与开电源的设备时间，单位分钟',
  `datasendmodel` int(11) NOT NULL COMMENT '数据发送模式，0：单播，1：组播',
  `state` int(11) DEFAULT 0 COMMENT '任务执行状态0-准备 1-执行 2-暂停 3-立即执行',
  `startdate` date NOT NULL DEFAULT '0000-00-00' COMMENT '任务开始执行日期',
  `enddate` date NOT NULL COMMENT '任务结束执行日期',
  `playtime` time NOT NULL DEFAULT '00:00:00' COMMENT '开始执行时间',
  `endtime` time DEFAULT '00:00:00',
  `exemodel` varchar(7) DEFAULT '0000000' COMMENT '1111111表示每天000000表示手动',
  `priority` int(11) NOT NULL DEFAULT 3 COMMENT '对编解码任务有效',
  `tasktype` int(11) NOT NULL COMMENT '任务类型1-作息 2-文件 3-采播 4-电话 5-功放 ',
  `channel` int(11) DEFAULT 0 COMMENT '对编码任务有效，2:双声道,1:单声道',
  `bandrate` int(11) unsigned DEFAULT 0 COMMENT '对编码任务有效',
  `samplerate` int(255) DEFAULT 0 COMMENT '对编码任务有效',
  `cmd` int(10) unsigned DEFAULT 0 COMMENT '要定时执行的命令,类型为5时（0：打开，1：关闭），类型为3时：才播终端ID',
  `cmdargs` char(255) DEFAULT '0' COMMENT '定时执行的命令参数类型为5是保存通道号',
  `playfileid` int(10) unsigned DEFAULT 0 COMMENT '正在播放的设备ID',
  `info` varchar(255) NOT NULL DEFAULT '' COMMENT '只有添加方案时才添加方案名称',
  `defaultvolume` int(10) unsigned DEFAULT 80 COMMENT '任务音量',
  `task_user_id` int(10) DEFAULT 0 COMMENT '记录添加用户的ID',
  `sec_task_id` int(10) unsigned DEFAULT 0 COMMENT '记录任务相关联的ID',
  `parentid` int(10) DEFAULT 0,
  `offlinestate` int(10) DEFAULT 0 COMMENT '0=非离线,1=空闲离线,2=立即离线,3=离线完成,4=空闲删除,5=立即删除,6=正在空闲离线,7=正在立即离线,8=删除完成，9=准备空闲传输，10=准备立即传输，11=停止传输，12=传输已停止',
  PRIMARY KEY (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `offlinetask` WRITE;
/*!40000 ALTER TABLE `offlinetask` DISABLE KEYS */;
/*!40000 ALTER TABLE `offlinetask` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `offlinetaskofterminal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offlinetaskofterminal` (
  `taskid` int(10) NOT NULL,
  `terminalid` int(10) NOT NULL,
  `offlinestate` int(10) DEFAULT 0 COMMENT '0=非离线,1=空闲离线,2=立即离线,3=离线完成,4=空闲删除,5=立即删除,6=正在空闲离线,7=正在立即离线,8=删除完成，9=准备空闲传输，10=准备立即传输，11=停止传输，12=传输已停止',
  `area` varchar(16) DEFAULT '''11111111''',
  PRIMARY KEY (`taskid`,`terminalid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `offlinetaskofterminal` WRITE;
/*!40000 ALTER TABLE `offlinetaskofterminal` DISABLE KEYS */;
/*!40000 ALTER TABLE `offlinetaskofterminal` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `playbelloftask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `playbelloftask` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `lessonname` varchar(255) NOT NULL,
  `belltime` time NOT NULL DEFAULT '00:00:00',
  `bellid` int(20) NOT NULL COMMENT '与媒体表id一致，如果媒体删除则该对该铃声修改',
  `belltimelength` time NOT NULL DEFAULT '00:00:00',
  `belltaskid` int(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `playbelloftask` WRITE;
/*!40000 ALTER TABLE `playbelloftask` DISABLE KEYS */;
/*!40000 ALTER TABLE `playbelloftask` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `powermgrmap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `powermgrmap` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `powerterminalid` int(10) unsigned NOT NULL,
  `powerchannel` int(10) unsigned NOT NULL,
  `info` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `powermgrmap` WRITE;
/*!40000 ALTER TABLE `powermgrmap` DISABLE KEYS */;
/*!40000 ALTER TABLE `powermgrmap` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `serverbaseparam`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serverbaseparam` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '设置服务器名称',
  `serverip` varchar(64) DEFAULT NULL,
  `ip` varchar(64) NOT NULL DEFAULT '' COMMENT '设置服务器IP',
  `webport` int(4) NOT NULL DEFAULT 8886 COMMENT 'webport端口号',
  `port` int(4) NOT NULL DEFAULT 8090,
  `hbport` int(4) NOT NULL DEFAULT 8900 COMMENT 'hbport端口号',
  `nodaemon` int(4) NOT NULL DEFAULT 1,
  `udpport` int(4) NOT NULL DEFAULT 8888 COMMENT 'udpport端口号',
  `rtspport` int(4) NOT NULL DEFAULT 8091 COMMENT 'rtspport端口号',
  `maxhttpconnections` int(11) NOT NULL DEFAULT 0 COMMENT '与服务器最多可以连接多少终端数',
  `maxbandwidth` int(11) NOT NULL DEFAULT 0 COMMENT '服务器提供最大的带宽',
  `workstate` int(11) DEFAULT NULL COMMENT '服务器工作状态0-停止 1-运行',
  `currectconnectcount` int(11) DEFAULT 0 COMMENT '当前实际连接数目',
  `currentbandwidth` int(11) DEFAULT 0 COMMENT '当前实际带宽',
  `customlog` varchar(255) NOT NULL DEFAULT '' COMMENT '该字段没使用',
  `mediapath` varchar(255) NOT NULL DEFAULT '/home/' COMMENT '该字段没有使用',
  `terminalchange` int(11) DEFAULT 0 COMMENT '服务器检测到终端变化时修改此值',
  `taskchange` int(11) DEFAULT 0 COMMENT '服务器检测到任务变化时修改此值',
  `serverchange` int(11) DEFAULT 0 COMMENT '服务器变化时修改此值',
  `taskcount` int(11) unsigned DEFAULT 0 COMMENT '活动任务数目',
  `multicastip` char(64) NOT NULL DEFAULT '230.1.1.1' COMMENT '组播IP地址',
  `multicastport` int(4) DEFAULT 34835 COMMENT '组播端口',
  `registerflag` int(4) NOT NULL DEFAULT 0 COMMENT '是否测试成功 0-未注册 1注册,',
  `registerserial` char(128) DEFAULT '""' COMMENT '注册序列号-由服务器端填写',
  `netradiocount` int(4) NOT NULL DEFAULT 2 COMMENT '并行的网络电台数',
  `soundcardcount` int(4) NOT NULL DEFAULT 2 COMMENT '支持的声卡数',
  `ctrlterminalcount` int(4) NOT NULL DEFAULT 1 COMMENT '并发的分控软件数',
  `gateway` varchar(64) DEFAULT '',
  `version` varchar(64) DEFAULT '',
  `ntpserver` varchar(64) DEFAULT '',
  `trystartdate` date DEFAULT '0000-00-00',
  `subnetmask` varchar(64) DEFAULT NULL COMMENT '子网掩码',
  `netstate` int(4) DEFAULT 0,
  `dataport` int(4) DEFAULT 32773,
  `tryenddate` date DEFAULT '0000-00-00',
  `factory` varchar(64) DEFAULT '',
  `dealerinfo` varchar(64) DEFAULT '',
  `offlineport` int(4) DEFAULT 8901,
  `backupmode` int(4) DEFAULT 0 COMMENT '考试模式',
  `model` int(4) DEFAULT 1 COMMENT '1:主服务器,2:备份服务器',
  `masterip` varchar(64) DEFAULT '' COMMENT '主服务器IP',
  `slaveip` varchar(64) DEFAULT '' COMMENT '备份服务器IP',
  `slavename` varchar(255) DEFAULT 'ha52' COMMENT '备份服务器名称',
  `adjusttime` int(4) DEFAULT 0 COMMENT '校时采集器ID',
  `projectname` varchar(255) DEFAULT '' COMMENT '项目名称',
  `ischeckmac` int(4) DEFAULT 0 COMMENT 'mac限制',
  `sounddetect` tinyint(3) DEFAULT 0,
  `backup` int(4) DEFAULT 0 COMMENT '主从复制',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `serverbaseparam` WRITE;
/*!40000 ALTER TABLE `serverbaseparam` DISABLE KEYS */;
INSERT INTO `serverbaseparam` (`id`, `name`, `serverip`, `ip`, `webport`, `port`, `hbport`, `nodaemon`, `udpport`, `rtspport`, `maxhttpconnections`, `maxbandwidth`, `workstate`, `currectconnectcount`, `currentbandwidth`, `customlog`, `mediapath`, `terminalchange`, `taskchange`, `serverchange`, `taskcount`, `multicastip`, `multicastport`, `registerflag`, `registerserial`, `netradiocount`, `soundcardcount`, `ctrlterminalcount`, `gateway`, `version`, `ntpserver`, `trystartdate`, `subnetmask`, `netstate`, `dataport`, `tryenddate`, `factory`, `dealerinfo`, `offlineport`, `backupmode`, `model`, `masterip`, `slaveip`, `slavename`, `adjusttime`, `projectname`, `ischeckmac`, `sounddetect`, `backup`) VALUES (1,'ha51','','192.168.2.159',8886,8090,8900,1,8888,8091,3000,10000000,0,0,0,'','/home/',0,0,0,0,'230.1.1.1',34835,1,'',2,2,4,'192.168.2.1','','','0000-00-00','255.0.0.0',0,32773,'0000-00-00','','',8901,0,1,'12.12.2.51','12.12.2.52','ha52',0,'ht',0,0,0);
/*!40000 ALTER TABLE `serverbaseparam` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `serverconfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serverconfig` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounddetect` int(4) NOT NULL DEFAULT 0,
  `fuzamima` int(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `serverconfig` WRITE;
/*!40000 ALTER TABLE `serverconfig` DISABLE KEYS */;
INSERT INTO `serverconfig` (`id`, `sounddetect`, `fuzamima`) VALUES (1,0,0);
/*!40000 ALTER TABLE `serverconfig` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `serverinputtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serverinputtype` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `info` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `serverinputtype` WRITE;
/*!40000 ALTER TABLE `serverinputtype` DISABLE KEYS */;
/*!40000 ALTER TABLE `serverinputtype` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `serverplaystream`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serverplaystream` (
  `streamid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `info` varchar(255) DEFAULT NULL,
  `createtime` timestamp NOT NULL DEFAULT current_timestamp(),
  `userid` int(4) DEFAULT NULL,
  PRIMARY KEY (`streamid`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `serverplaystream` WRITE;
/*!40000 ALTER TABLE `serverplaystream` DISABLE KEYS */;
INSERT INTO `serverplaystream` (`streamid`, `name`, `info`, `createtime`, `userid`) VALUES (1,'教学楼','A栋教室终端','2026-09-03 03:12:56',1),
(2,'办公区','行政楼','2026-09-03 03:12:56',1),
(3,'室外操场','看台号角','2026-09-03 03:12:56',1),
(4,'宿舍楼','门厅寻呼','2026-09-03 03:12:56',1);
/*!40000 ALTER TABLE `serverplaystream` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `servers` (
  `Server_name` char(64) NOT NULL DEFAULT '',
  `Host` char(64) NOT NULL DEFAULT '',
  `Db` char(64) NOT NULL DEFAULT '',
  `Username` char(64) NOT NULL DEFAULT '',
  `Password` char(64) NOT NULL DEFAULT '',
  `Port` int(4) NOT NULL DEFAULT 0,
  `Socket` char(64) NOT NULL DEFAULT '',
  `Wrapper` char(64) NOT NULL DEFAULT '',
  `Owner` char(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`Server_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `servers` WRITE;
/*!40000 ALTER TABLE `servers` DISABLE KEYS */;
/*!40000 ALTER TABLE `servers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `serverspeech`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serverspeech` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `FileName` varchar(255) NOT NULL DEFAULT '',
  `FileMaxSize` int(11) NOT NULL DEFAULT 32,
  `ACLallow` varchar(255) NOT NULL DEFAULT '0',
  `Launch` varchar(255) DEFAULT NULL,
  `ReadOnlyFile` varchar(255) DEFAULT NULL,
  `isTruncate` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `serverspeech` WRITE;
/*!40000 ALTER TABLE `serverspeech` DISABLE KEYS */;
/*!40000 ALTER TABLE `serverspeech` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `shortcutkeymap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shortcutkeymap` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(4) DEFAULT NULL,
  `mediaid` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `shortcutkeymap` WRITE;
/*!40000 ALTER TABLE `shortcutkeymap` DISABLE KEYS */;
/*!40000 ALTER TABLE `shortcutkeymap` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `shortcutkeytask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shortcutkeytask` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `keyid` int(4) NOT NULL,
  `mediaid` int(4) NOT NULL,
  `keyname` varchar(32) NOT NULL,
  PRIMARY KEY (`id`,`keyid`,`mediaid`,`keyname`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `shortcutkeytask` WRITE;
/*!40000 ALTER TABLE `shortcutkeytask` DISABLE KEYS */;
INSERT INTO `shortcutkeytask` (`id`, `keyid`, `mediaid`, `keyname`) VALUES (4,1,70013,'防欺凌一键播');
/*!40000 ALTER TABLE `shortcutkeytask` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sounddevice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sounddevice` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(16) NOT NULL,
  `devaddr` tinyint(3) unsigned NOT NULL COMMENT '设备地址',
  `name` varchar(32) DEFAULT NULL COMMENT '备注名称',
  `groupid` int(11) DEFAULT NULL COMMENT '声场ID',
  `dbvalue` float DEFAULT NULL COMMENT '探头DB值',
  `sendport` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sounddevice` WRITE;
/*!40000 ALTER TABLE `sounddevice` DISABLE KEYS */;
/*!40000 ALTER TABLE `sounddevice` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `soundgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `soundgroup` (
  `terminalid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL COMMENT '关联soundgroupinfo表ID',
  PRIMARY KEY (`terminalid`,`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `soundgroup` WRITE;
/*!40000 ALTER TABLE `soundgroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `soundgroup` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `soundgroupinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `soundgroupinfo` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '分组ID',
  `name` varchar(64) NOT NULL COMMENT '分组名词',
  `userid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `soundgroupinfo` WRITE;
/*!40000 ALTER TABLE `soundgroupinfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `soundgroupinfo` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `soundtask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `soundtask` (
  `taskid` int(11) NOT NULL COMMENT '关联task表taskid',
  `devid` int(11) unsigned NOT NULL COMMENT '关联sounddevice表id',
  `volume` tinyint(3) unsigned NOT NULL COMMENT '任务音量',
  `dbvalue` float DEFAULT NULL COMMENT '音量DB值'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `soundtask` WRITE;
/*!40000 ALTER TABLE `soundtask` DISABLE KEYS */;
INSERT INTO `soundtask` (`taskid`, `devid`, `volume`, `dbvalue`) VALUES (0,0,0,0),
(0,0,20,20),
(0,0,40,40),
(0,0,60,60),
(0,0,80,80),
(0,0,100,100);
/*!40000 ALTER TABLE `soundtask` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task` (
  `taskid` int(11) NOT NULL AUTO_INCREMENT,
  `taskname` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
  `israndomplay` int(11) NOT NULL DEFAULT 0 COMMENT '0表示随机1表示顺序',
  `projectstate` int(11) NOT NULL DEFAULT 0 COMMENT '方案是否启用，1启用，0停用',
  `timelengthtype` int(11) NOT NULL COMMENT '1为时间，2为循环次数',
  `timelength` int(11) NOT NULL COMMENT '播放时长， 类型为1是是播放秒数，否则为循环次数。',
  `prepower` int(11) NOT NULL COMMENT '与开电源的设备时间，单位分钟',
  `datasendmodel` int(11) NOT NULL COMMENT '数据发送模式，0：单播，1：组播',
  `state` int(11) DEFAULT 0 COMMENT '任务执行状态0-准备 1-执行 2-暂停 3-立即执行',
  `startdate` date NOT NULL DEFAULT '0000-00-00' COMMENT '任务开始执行日期',
  `enddate` date NOT NULL COMMENT '任务结束执行日期',
  `playtime` time NOT NULL DEFAULT '00:00:00' COMMENT '开始执行时间',
  `endtime` time DEFAULT '00:00:00',
  `exemodel` varchar(7) NOT NULL DEFAULT '0000000' COMMENT '1111111表示每天000000表示手动',
  `priority` int(11) NOT NULL DEFAULT 3 COMMENT '对编解码任务有效',
  `tasktype` int(11) NOT NULL COMMENT '任务类型1-作息 2-文件 3-采播 4-电话 5-功放 ',
  `channel` int(11) DEFAULT 0 COMMENT '对编码任务有效，2:双声道,1:单声道',
  `bandrate` int(11) DEFAULT 0 COMMENT '对编码任务有效',
  `samplerate` int(255) DEFAULT 0 COMMENT '对编码任务有效',
  `cmd` int(10) unsigned DEFAULT 0 COMMENT '要定时执行的命令,类型为5时（0：打开，1：关闭），类型为3时：才播终端ID',
  `cmdargs` char(255) DEFAULT '0' COMMENT '定时执行的命令参数类型为5是保存通道号',
  `playfileid` int(10) unsigned DEFAULT 0 COMMENT '正在播放的设备ID',
  `info` varchar(255) NOT NULL DEFAULT '' COMMENT '只有添加方案时才添加方案名称',
  `defaultvolume` int(10) unsigned DEFAULT 80 COMMENT '任务音量',
  `task_user_id` int(10) DEFAULT 0 COMMENT '记录添加用户的ID',
  `sec_task_id` int(10) unsigned DEFAULT 0 COMMENT '记录任务相关联的ID',
  `parentid` int(10) DEFAULT 0 COMMENT '父ID',
  `offlinestate` int(10) DEFAULT 0 COMMENT '0=非离线,1=空闲离线,2=立即离线,3=离线完成,4=空闲删除,5=立即删除,6=正在空闲离线,7=正在立即离线',
  `createtime` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '创建时间',
  `disableday` date DEFAULT '0000-00-00' COMMENT '当天停用',
  `interval_s` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '播放间隔时间',
  `intplaylength` int(10) unsigned NOT NULL DEFAULT 1 COMMENT '间隔播放时长',
  `intplaylengthtype` tinyint(10) unsigned NOT NULL DEFAULT 2 COMMENT '间隔播放模式1：时间,2：循环',
  `localplay` tinyint(10) unsigned NOT NULL DEFAULT 0 COMMENT '是否优先本地播放0：网络播放,1：本地播放',
  `keyid` tinyint(10) DEFAULT 0,
  PRIMARY KEY (`taskid`)
) ENGINE=InnoDB AUTO_INCREMENT=70132 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `task` WRITE;
/*!40000 ALTER TABLE `task` DISABLE KEYS */;
INSERT INTO `task` (`taskid`, `taskname`, `israndomplay`, `projectstate`, `timelengthtype`, `timelength`, `prepower`, `datasendmodel`, `state`, `startdate`, `enddate`, `playtime`, `endtime`, `exemodel`, `priority`, `tasktype`, `channel`, `bandrate`, `samplerate`, `cmd`, `cmdargs`, `playfileid`, `info`, `defaultvolume`, `task_user_id`, `sec_task_id`, `parentid`, `offlinestate`, `createtime`, `disableday`, `interval_s`, `intplaylength`, `intplaylengthtype`, `localplay`, `keyid`) VALUES (1001,'早读预备铃',0,0,0,30,1,0,1,'2026-01-01','2026-12-31','07:20:00','07:20:30','0111110',10,1,0,0,0,0,'0',0,'春季作息',66,1,0,1,0,'2026-09-04 07:16:31','0000-00-00',0,1,2,0,0),
(1002,'上午第一节上课',0,0,0,30,1,0,1,'2026-01-01','2026-12-31','08:00:00','08:00:30','0111110',10,1,0,0,0,0,'0',0,'春季作息',66,1,0,1,0,'2026-09-04 07:16:31','0000-00-00',0,1,2,0,0),
(1003,'第一节下课',0,0,0,30,1,0,1,'2026-01-01','2026-12-31','08:45:00','08:45:30','0111110',10,1,0,0,0,0,'0',0,'春季作息',66,1,0,1,0,'2026-09-04 06:12:51','0000-00-00',0,1,2,0,0),
(1004,'课间眼保健操',0,0,0,210,1,0,1,'2026-01-01','2026-12-31','09:40:00','09:43:30','0111110',10,1,0,0,0,0,'0',0,'春季作息',66,1,0,1,0,'2026-09-04 06:12:51','0000-00-00',0,1,2,0,0),
(1005,'大课间活动',0,0,0,1500,1,0,1,'2026-01-01','2026-12-31','10:00:00','10:25:00','0111110',10,1,0,0,0,0,'0',0,'春季作息',66,1,0,1,0,'2026-09-04 06:12:51','0000-00-00',0,1,2,0,0),
(1006,'午休提示',0,0,0,40,1,0,1,'2026-01-01','2026-12-31','12:30:00','12:30:40','0111110',10,1,0,0,0,0,'0',0,'春季作息',66,1,0,1,0,'2026-09-04 06:12:51','0000-00-00',0,1,2,0,0),
(1007,'放学铃',0,0,0,45,1,0,1,'2026-01-01','2026-12-31','17:30:00','17:30:45','0111110',10,1,0,0,0,0,'0',0,'春季作息',66,1,0,1,0,'2026-09-04 06:12:51','0000-00-00',0,1,2,0,0),
(1008,'升旗仪式-国歌',0,0,0,180,1,0,1,'2026-01-01','2026-12-31','07:50:00','07:53:00','1000000',8,2,0,0,0,0,'0',0,'每周一',90,1,0,1,0,'2026-09-05 00:59:47','0000-00-00',0,1,2,0,0),
(1009,'消防疏散演练',0,0,0,120,1,0,0,'2026-01-01','2026-12-31','15:00:00','15:02:00','0000000',10,2,0,0,0,0,'0',0,'手动触发',100,1,0,1,0,'2026-09-02 09:39:14','0000-00-00',0,1,2,0,0),
(1010,'课间轻音乐',0,0,0,900,1,0,1,'2026-01-01','2026-12-31','09:45:00','10:00:00','1111100',3,2,0,0,0,0,'0',0,'走廊与操场',65,1,0,1,0,'2026-09-02 09:39:14','0000-00-00',0,1,2,0,0),
(1011,'午间食堂背景music',0,0,0,2400,1,0,1,'2026-01-01','2026-12-31','11:40:00','12:20:00','1111100',3,2,0,0,0,0,'0',0,'食堂',55,1,0,1,0,'2026-09-02 09:39:14','0000-00-00',0,1,2,0,0),
(1012,'晚自习预备',0,0,0,30,1,0,1,'2026-01-01','2026-12-31','18:50:00','18:50:30','1111100',5,2,0,0,0,0,'0',0,'教学楼',80,1,0,1,0,'2026-09-02 09:39:14','0000-00-00',0,1,2,0,0),
(1013,'运动会入场式',0,0,0,900,1,0,0,'2026-01-01','2026-12-31','08:30:00','08:45:00','0000000',7,2,0,0,0,0,'0',0,'操场·临时',95,1,0,1,0,'2026-09-02 09:39:14','0000-00-00',0,1,2,0,0),
(70000,'reset',0,0,1,0,2,0,0,'2011-01-01','2060-01-01','04:00:00','00:00:00','0001000',10,13,0,0,0,0,'0',0,'',0,1,417,NULL,0,'2026-09-02 09:17:16','0000-00-00',0,1,2,0,0),
(70006,'呼叫保安',0,0,1,30,0,0,0,'0000-00-00','0000-00-00','00:00:00','00:00:00','0000000',9,20,0,0,0,0,'6',0,'',90,1,0,0,0,'2026-09-03 06:04:56','0000-00-00',0,1,2,0,0),
(70007,'课间提醒',0,0,1,20,0,0,0,'0000-00-00','0000-00-00','00:00:00','00:00:00','0000000',6,21,0,0,0,11,'6',0,'',80,1,0,0,0,'2026-09-03 06:04:56','0000-00-00',0,1,2,0,0),
(70008,'午间音乐',1,0,2,3,0,1,0,'0000-00-00','0000-00-00','00:00:00','00:00:00','0000000',3,20,0,0,0,0,'7',0,'',60,1,0,0,0,'2026-09-03 06:05:28','0000-00-00',0,1,2,0,0),
(70010,'课间采播',1,0,1,60,0,0,0,'2026-01-01','2026-12-31','09:40:00','09:41:00','1111111',3,3,0,0,0,6,'0',0,'',60,1,0,0,0,'2026-09-03 07:12:50','0000-00-00',0,0,0,0,0),
(70011,'升旗现场采播',1,0,1,60,0,0,0,'2026-01-01','2026-12-31','07:40:00','07:41:00','1111111',3,3,0,0,0,6,'0',0,'',60,1,0,0,0,'2026-09-03 07:12:50','0000-00-00',0,0,0,0,0),
(70012,'放学安全提示',1,0,1,60,0,0,0,'2026-01-01','2026-12-31','17:25:00','17:26:00','1111111',3,15,0,0,0,0,'0',0,'',60,1,0,0,0,'2026-09-03 07:12:50','0000-00-00',0,0,0,0,0),
(70013,'防欺凌广播',1,0,1,60,0,0,0,'2026-01-01','2026-12-31','12:10:00','12:11:00','1111111',3,15,0,0,0,0,'0',0,'',60,1,0,0,0,'2026-09-03 07:12:50','0000-00-00',0,0,0,0,0),
(70014,'晨间开功放',1,0,1,60,0,0,0,'2026-01-01','2026-12-31','06:50:00','06:51:00','1111111',3,5,0,0,0,1,'0',0,'',60,1,0,0,0,'2026-09-03 07:12:50','0000-00-00',0,0,0,0,0),
(70015,'晚间关功放',1,0,1,60,0,0,0,'2026-01-01','2026-12-31','22:10:00','22:11:00','1111111',3,5,0,0,0,0,'0',0,'',60,1,0,0,0,'2026-09-03 07:12:50','0000-00-00',0,0,0,0,0),
(70016,'欢迎标语',1,0,1,60,0,0,0,'2026-01-01','2026-12-31','08:00:00','08:01:00','1111111',3,30,0,0,0,0,'0',0,'',60,1,0,2,0,'2026-09-03 07:13:03','0000-00-00',0,0,0,0,0),
(70017,'考试须知',1,0,1,60,0,0,0,'2026-01-01','2026-12-31','08:30:00','08:31:00','1111111',3,30,0,0,0,0,'0',0,'',60,1,0,2,0,'2026-09-03 07:13:03','0000-00-00',0,0,0,0,0),
(70107,'升旗仪式-国歌',0,0,0,180,1,0,0,'2026-01-01','2026-12-31','07:49:59','07:53:00','1000000',8,9,0,0,0,0,'0',0,'',90,1,1008,1,0,'2026-09-05 00:59:47','0000-00-00',0,1,2,0,0);
/*!40000 ALTER TABLE `task` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `tempctrlnet2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tempctrlnet2` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `nettypeid` int(11) DEFAULT NULL,
  `filepath` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `tempctrlnet2` WRITE;
/*!40000 ALTER TABLE `tempctrlnet2` DISABLE KEYS */;
/*!40000 ALTER TABLE `tempctrlnet2` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` int(11) DEFAULT 0 COMMENT '只对有解码码功能的设备有效，终端分区ID',
  `terminalname` varchar(255) NOT NULL DEFAULT '' COMMENT '通用字段，所有终端有效',
  `typeid` int(11) NOT NULL DEFAULT -1 COMMENT '通用字段，所有终端有效',
  `netstate` int(11) DEFAULT 1 COMMENT '通用字段，所有终端有效',
  `devicestate` int(1) DEFAULT 1 COMMENT '通用字段，所有终端有效',
  `taskstate` int(1) DEFAULT 0 COMMENT '通用字段，所有终端有效',
  `ip` varchar(255) NOT NULL DEFAULT '192.168.1.111' COMMENT '通用字段，所有终端有效',
  `postion` varchar(255) CHARACTER SET gbk COLLATE gbk_chinese_ci DEFAULT NULL COMMENT '只对有编解码功能的设备有效',
  `volume` int(11) DEFAULT 50 COMMENT '只对有编解码功能的设备有效',
  `sample` int(11) DEFAULT 44100 COMMENT '只对有编码功能的设备有效',
  `bitrate` int(11) DEFAULT 32000 COMMENT '终端编码是的比特率',
  `channel` int(11) DEFAULT 2 COMMENT '不同终端定义不同，一般指终端支持的电源控制的路数',
  `firealarmgroup` int(11) DEFAULT -1 COMMENT '只对有解码功能的终端有效-1表示没有分区',
  `audiocodec` int(11) DEFAULT 3 COMMENT '只对有编码功能的设备有效',
  `inputformat` int(11) DEFAULT 3 COMMENT '只对有编码功能的设备有效',
  `outformat` int(11) DEFAULT 3 COMMENT '只对有编码功能的设备有效',
  `isspeech` int(10) unsigned DEFAULT 0 COMMENT '是否可寻呼',
  `priority` int(10) unsigned DEFAULT 0 COMMENT '终端优先级',
  `mac` varchar(45) NOT NULL DEFAULT '00:00:00:00:00:00' COMMENT '终端的网卡地址',
  `isrecord` int(4) DEFAULT 0 COMMENT '是否录音，编码是有效',
  `instancy` int(4) NOT NULL DEFAULT 0 COMMENT '是否是紧急寻呼备用终端或缺省终端',
  `longitude` double DEFAULT 0,
  `latitude` double DEFAULT 0,
  `isselectcall` int(4) NOT NULL DEFAULT 0,
  `onlinetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '上线时间',
  `serverip` varchar(255) NOT NULL DEFAULT '192.168.1.1',
  `taskid` int(4) NOT NULL DEFAULT 0,
  `skaddress` varchar(255) NOT NULL DEFAULT '',
  `skdevtype` int(4) NOT NULL DEFAULT 0,
  `totalcapacity` bigint(20) NOT NULL DEFAULT 0,
  `resetcapacity` bigint(20) NOT NULL DEFAULT 0,
  `issponsor` int(4) DEFAULT 0 COMMENT '是否支持发言',
  `shortcircuit` int(4) DEFAULT 0,
  `lopencircuit` int(4) DEFAULT 1,
  `ropencircuit` int(4) DEFAULT 1,
  `temperature` float DEFAULT 0,
  `humidity` float DEFAULT 0,
  `upgrade` varchar(255) DEFAULT NULL,
  `progress` int(4) DEFAULT 0,
  `soundsgroupid` int(11) unsigned DEFAULT 0 COMMENT '关联soundgroupinfo表id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=904 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminal` WRITE;
/*!40000 ALTER TABLE `terminal` DISABLE KEYS */;
INSERT INTO `terminal` (`id`, `groupid`, `terminalname`, `typeid`, `netstate`, `devicestate`, `taskstate`, `ip`, `postion`, `volume`, `sample`, `bitrate`, `channel`, `firealarmgroup`, `audiocodec`, `inputformat`, `outformat`, `isspeech`, `priority`, `mac`, `isrecord`, `instancy`, `longitude`, `latitude`, `isselectcall`, `onlinetime`, `serverip`, `taskid`, `skaddress`, `skdevtype`, `totalcapacity`, `resetcapacity`, `issponsor`, `shortcircuit`, `lopencircuit`, `ropencircuit`, `temperature`, `humidity`, `upgrade`, `progress`, `soundsgroupid`) VALUES (1,1,'A101教室音箱',11,1,1,1,'192.168.2.11','教学楼A栋一层101室',80,44100,32000,2,1,3,3,3,0,5,'00:1B:44:11:3A:01',0,0,0,0,1,'2026-09-02 09:34:06','192.168.2.159',0,'',0,0,0,0,0,1,1,26.5,48.2,NULL,0,0),
(2,1,'A102教室音箱',11,1,1,0,'192.168.2.12','A栋102',75,44100,32000,2,1,3,3,3,0,5,'00:1B:44:11:3A:02',0,0,0,0,1,'2026-09-02 09:34:06','192.168.2.159',0,'',0,0,0,0,0,1,1,27.1,47,NULL,0,0),
(3,1,'A103教室音箱',11,0,0,0,'192.168.2.13','A栋103',75,44100,32000,2,-1,3,3,3,0,5,'00:1B:44:11:3A:03',0,0,0,0,1,'2026-09-02 09:34:06','192.168.2.159',0,'',0,0,0,0,0,1,1,NULL,NULL,NULL,0,0),
(4,1,'A201教室音箱',11,1,1,1,'192.168.2.21','A栋201',80,44100,32000,2,1,3,3,3,0,5,'00:1B:44:11:3A:04',0,0,0,0,1,'2026-09-02 09:34:06','192.168.2.159',0,'',0,0,0,0,0,1,1,26.8,49.1,NULL,0,0),
(5,1,'A202教室音箱',11,1,1,0,'192.168.2.22','A栋202',80,44100,32000,2,-1,3,3,3,0,5,'00:1B:44:11:3A:05',0,0,0,0,1,'2026-09-02 09:34:06','192.168.2.159',0,'',0,0,0,0,0,1,1,26.2,48.8,NULL,0,0),
(6,2,'广播室主话筒',2,1,1,0,'192.168.2.31','行政楼203',90,44100,32000,2,-1,3,3,3,0,9,'00:1B:44:11:3A:06',0,1,0,0,1,'2026-09-02 09:34:06','192.168.2.159',0,'',0,0,0,0,0,1,1,NULL,NULL,NULL,0,0),
(7,2,'办公区功放',5,1,1,1,'192.168.2.32','行政楼一层',70,44100,32000,2,-1,3,3,3,0,7,'00:1B:44:11:3A:07',0,0,0,0,1,'2026-09-02 09:34:06','192.168.2.159',0,'',0,0,0,0,0,1,1,31.4,45,NULL,0,0),
(8,3,'操场号角01',11,1,1,1,'192.168.2.41','东侧看台',95,44100,32000,2,-1,3,3,3,0,6,'00:1B:44:11:3A:08',0,0,0,0,1,'2026-09-02 09:34:06','192.168.2.159',0,'',0,0,0,0,0,1,1,33.9,52.3,NULL,0,0),
(9,3,'操场号角02',11,0,0,0,'192.168.2.42',NULL,95,44100,32000,2,-1,3,3,3,0,6,'00:1B:44:11:3A:09',0,0,0,0,1,'2026-09-02 10:09:44','192.168.2.159',0,'',0,0,0,0,0,1,1,0,0,NULL,0,0),
(10,4,'宿舍楼一键寻呼',13,1,1,0,'192.168.2.51','宿舍楼门厅',85,44100,32000,2,-1,3,3,3,0,10,'00:1B:44:11:3A:10',0,1,0,0,1,'2026-09-02 09:34:06','192.168.2.159',0,'',0,0,0,0,0,1,1,NULL,NULL,NULL,0,0),
(11,2,'TTS主机',22,1,1,0,'192.168.2.60',NULL,80,44100,32000,2,-1,3,3,3,0,5,'00:1B:44:11:3A:11',0,0,0,0,1,'2026-09-03 05:56:28','192.168.2.159',0,'',0,0,0,0,0,1,1,0,0,NULL,0,0),
(20,2,'消防报警主机',7,1,1,0,'192.168.2.90',NULL,80,44100,32000,2,-1,3,3,3,0,0,'00:1B:44:11:3A:20',0,0,0,0,0,'0000-00-00 00:00:00','192.168.1.1',0,'',0,0,0,0,0,1,1,0,0,NULL,0,0),
(902,0,'服务器',0,1,1,0,'192.168.2.159',NULL,80,44100,32000,2,-1,3,3,3,0,5,'00:1B:44:11:3A:00',0,0,0,0,1,'2026-09-03 05:56:28','192.168.2.159',0,'',0,0,0,0,0,1,1,0,0,NULL,0,0);
/*!40000 ALTER TABLE `terminal` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminalattrbute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalattrbute` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `volume` int(11) DEFAULT NULL,
  `bitrate` int(11) DEFAULT NULL,
  `sample` int(11) DEFAULT NULL,
  `channel` int(11) DEFAULT NULL,
  `terminalid` int(10) unsigned NOT NULL,
  `inputformat` int(11) DEFAULT 0,
  `outformat` varchar(10) DEFAULT NULL COMMENT 'int改为varchar',
  `audiocodec` varchar(20) DEFAULT NULL COMMENT 'int改为varchar',
  `audioquality` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminalattrbute` WRITE;
/*!40000 ALTER TABLE `terminalattrbute` DISABLE KEYS */;
/*!40000 ALTER TABLE `terminalattrbute` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminalfolder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalfolder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parentid` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `terminalid` int(10) NOT NULL,
  `seqnumber` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminalfolder` WRITE;
/*!40000 ALTER TABLE `terminalfolder` DISABLE KEYS */;
INSERT INTO `terminalfolder` (`id`, `parentid`, `name`, `terminalid`, `seqnumber`) VALUES (7,0,'目录管理',6,0),
(9,7,'办公与后勤',6,2),
(15,7,'教学区',6,3),
(16,15,'一层教室',6,4);
/*!40000 ALTER TABLE `terminalfolder` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminalfunc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalfunc` (
  `name` char(64) NOT NULL DEFAULT '',
  `ret` tinyint(1) NOT NULL DEFAULT 0,
  `dl` char(128) NOT NULL DEFAULT '',
  `typeid` enum('function','aggregate') NOT NULL DEFAULT 'function',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminalfunc` WRITE;
/*!40000 ALTER TABLE `terminalfunc` DISABLE KEYS */;
/*!40000 ALTER TABLE `terminalfunc` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminalgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalgroup` (
  `groupid` int(11) NOT NULL DEFAULT 0,
  `streamid` int(11) NOT NULL DEFAULT 0,
  `groupname` varchar(255) DEFAULT NULL,
  `info` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminalgroup` WRITE;
/*!40000 ALTER TABLE `terminalgroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `terminalgroup` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminalgrouplist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalgrouplist` (
  `id` int(10) unsigned NOT NULL,
  `terminalid` int(10) unsigned NOT NULL,
  `terminalgroupid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminalgrouplist` WRITE;
/*!40000 ALTER TABLE `terminalgrouplist` DISABLE KEYS */;
/*!40000 ALTER TABLE `terminalgrouplist` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminalkey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalkey` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '',
  `terminalid` varchar(45) NOT NULL DEFAULT '',
  `key` int(11) NOT NULL,
  `sendmodul` int(4) DEFAULT 1 COMMENT '单播还是组播',
  `flag` int(4) DEFAULT 0 COMMENT '0=快捷键，1=急救',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminalkey` WRITE;
/*!40000 ALTER TABLE `terminalkey` DISABLE KEYS */;
INSERT INTO `terminalkey` (`id`, `name`, `terminalid`, `key`, `sendmodul`, `flag`) VALUES (8,'呼叫A栋一层','6',2,1,0),
(9,'呼叫A栋二层','6',3,1,0),
(10,'呼叫操场','6',4,1,0);
/*!40000 ALTER TABLE `terminalkey` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminalkeymap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalkeymap` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `keyid` int(10) unsigned DEFAULT NULL,
  `terminalid` int(10) unsigned NOT NULL,
  `area` varchar(16) DEFAULT '1111111111111111',
  `groupid` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminalkeymap` WRITE;
/*!40000 ALTER TABLE `terminalkeymap` DISABLE KEYS */;
INSERT INTO `terminalkeymap` (`id`, `keyid`, `terminalid`, `area`, `groupid`) VALUES (18,8,1,'1111111111111111',1),
(19,8,2,'1111111111111111',1),
(20,8,3,'1111111111111111',1),
(21,9,4,'1111111111111111',1),
(22,9,5,'1111111111111111',1),
(23,10,8,'1111111111111111',3),
(24,10,9,'1111111111111111',3);
/*!40000 ALTER TABLE `terminalkeymap` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminalkeymaptask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalkeymaptask` (
  `keyid` int(4) NOT NULL,
  `terminalid` int(4) NOT NULL,
  `taskid` int(4) DEFAULT NULL,
  PRIMARY KEY (`keyid`,`terminalid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminalkeymaptask` WRITE;
/*!40000 ALTER TABLE `terminalkeymaptask` DISABLE KEYS */;
INSERT INTO `terminalkeymaptask` (`keyid`, `terminalid`, `taskid`) VALUES (1,7,70008),
(5,6,70006),
(6,6,70007);
/*!40000 ALTER TABLE `terminalkeymaptask` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminalmaked`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalmaked` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `mac` varchar(20) NOT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminalmaked` WRITE;
/*!40000 ALTER TABLE `terminalmaked` DISABLE KEYS */;
/*!40000 ALTER TABLE `terminalmaked` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminalofalarmgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalofalarmgroup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alarmgroupid` varchar(45) NOT NULL DEFAULT '' COMMENT '与alarmarea表中id一致',
  `terminalid` int(10) unsigned NOT NULL COMMENT '被添加到报警分区的终端',
  `groupid` int(10) NOT NULL DEFAULT 0 COMMENT '组号',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminalofalarmgroup` WRITE;
/*!40000 ALTER TABLE `terminalofalarmgroup` DISABLE KEYS */;
INSERT INTO `terminalofalarmgroup` (`id`, `alarmgroupid`, `terminalid`, `groupid`) VALUES (1,'1',1,0),
(2,'1',2,0),
(3,'1',4,0);
/*!40000 ALTER TABLE `terminalofalarmgroup` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminalofararmgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalofararmgroup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alarmgroupid` int(10) unsigned NOT NULL COMMENT '终端所属的报警组ID',
  `terminalid` int(10) unsigned NOT NULL COMMENT '终端id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminalofararmgroup` WRITE;
/*!40000 ALTER TABLE `terminalofararmgroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `terminalofararmgroup` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminalofcallgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalofcallgroup` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `selectgroupid` int(11) DEFAULT NULL,
  `terminalid` int(11) DEFAULT NULL,
  `area` varchar(16) DEFAULT '11111111',
  `groupid` int(10) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminalofcallgroup` WRITE;
/*!40000 ALTER TABLE `terminalofcallgroup` DISABLE KEYS */;
INSERT INTO `terminalofcallgroup` (`id`, `selectgroupid`, `terminalid`, `area`, `groupid`) VALUES (8,3,1,'1111111111111111',1),
(9,3,2,'1111111111111111',1),
(10,3,3,'1111111111111111',1),
(11,3,4,'1111111111111111',1),
(12,3,5,'1111111111111111',1),
(13,3,7,'1111111111111111',2);
/*!40000 ALTER TABLE `terminalofcallgroup` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminaloffolder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminaloffolder` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `terminalid` int(10) DEFAULT NULL,
  `folderid` int(10) DEFAULT NULL,
  `seqnumber` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminaloffolder` WRITE;
/*!40000 ALTER TABLE `terminaloffolder` DISABLE KEYS */;
INSERT INTO `terminaloffolder` (`id`, `terminalid`, `folderid`, `seqnumber`) VALUES (114,7,9,NULL),
(124,1,15,NULL),
(125,2,15,NULL),
(126,4,15,NULL),
(127,5,16,NULL),
(128,3,16,NULL),
(129,8,9,NULL),
(130,9,9,NULL);
/*!40000 ALTER TABLE `terminaloffolder` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminalofgroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminalofgroup` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `terminalid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminalofgroup` WRITE;
/*!40000 ALTER TABLE `terminalofgroup` DISABLE KEYS */;
INSERT INTO `terminalofgroup` (`id`, `terminalid`, `groupid`) VALUES (1,1,1),
(2,2,1),
(3,3,1),
(4,4,1),
(5,5,1),
(6,6,2),
(7,7,2),
(8,8,3),
(9,9,3),
(10,10,4),
(11,11,2),
(12,20,2);
/*!40000 ALTER TABLE `terminalofgroup` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminaloftask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminaloftask` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskid` int(11) NOT NULL COMMENT '与task表taskid一致',
  `terminalid` int(11) NOT NULL COMMENT '与任务相关的terminal表id一致',
  `workstate` varchar(255) DEFAULT NULL COMMENT '工作状态',
  `groupid` int(11) NOT NULL DEFAULT 0,
  `area` varchar(16) NOT NULL DEFAULT '11111111',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=324 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminaloftask` WRITE;
/*!40000 ALTER TABLE `terminaloftask` DISABLE KEYS */;
INSERT INTO `terminaloftask` (`id`, `taskid`, `terminalid`, `workstate`, `groupid`, `area`) VALUES (27,1009,1,'0',0,''),
(28,1009,4,'0',0,''),
(29,1009,7,'0',0,''),
(30,1009,8,'0',0,''),
(31,1009,10,'0',0,''),
(32,1010,1,'0',0,''),
(33,1010,8,'0',0,''),
(34,1011,7,'0',0,''),
(35,1012,1,'0',0,''),
(36,1012,2,'0',0,''),
(37,1012,4,'0',0,''),
(38,1012,5,'0',0,''),
(39,1013,8,'0',0,''),
(40,1013,9,'0',0,''),
(59,70006,1,NULL,1,'1111111111111111'),
(60,70006,2,NULL,1,'1111111111111111'),
(61,70006,4,NULL,1,'1111111111111111'),
(62,70006,5,NULL,1,'1111111111111111'),
(63,70007,1,NULL,1,'1111111111111111'),
(64,70007,2,NULL,1,'1111111111111111'),
(65,70007,4,NULL,1,'1111111111111111'),
(66,70008,7,NULL,2,'1111111111111111'),
(72,70010,1,NULL,1,'11111111'),
(73,70011,1,NULL,1,'11111111'),
(74,70012,1,NULL,1,'11111111'),
(75,70013,1,NULL,1,'11111111'),
(76,70014,7,NULL,2,'11111111'),
(77,70015,7,NULL,2,'11111111'),
(78,70016,1,NULL,1,'11111111'),
(79,70017,1,NULL,1,'11111111'),
(80,70010,11,NULL,2,'11111111'),
(81,70012,11,NULL,2,'11111111'),
(82,70014,11,NULL,2,'11111111'),
(83,70016,11,NULL,2,'11111111'),
(86,70010,3,NULL,1,'11111111'),
(87,70012,3,NULL,1,'11111111'),
(88,70014,3,NULL,1,'11111111'),
(89,70016,3,NULL,1,'11111111'),
(150,1001,1,'0',1,'11111111'),
(151,1001,2,'0',1,'11111111'),
(152,1001,4,'0',1,'11111111'),
(153,1001,5,'0',1,'11111111'),
(154,1001,11,'0',2,'11111111'),
(155,1001,3,'0',1,'11111111'),
(156,1002,1,'0',1,'11111111'),
(157,1002,2,'0',1,'11111111'),
(158,1002,4,'0',1,'11111111'),
(159,1002,5,'0',1,'11111111'),
(160,1002,11,'0',2,'11111111'),
(161,1002,3,'0',1,'11111111'),
(162,1003,1,'0',1,'11111111'),
(163,1003,2,'0',1,'11111111'),
(164,1003,4,'0',1,'11111111'),
(165,1003,5,'0',1,'11111111'),
(166,1003,11,'0',2,'11111111'),
(167,1003,3,'0',1,'11111111'),
(168,1004,1,'0',1,'11111111'),
(169,1004,2,'0',1,'11111111'),
(170,1004,4,'0',1,'11111111'),
(171,1004,5,'0',1,'11111111'),
(172,1004,11,'0',2,'11111111'),
(173,1004,3,'0',1,'11111111'),
(174,1005,1,'0',1,'11111111'),
(175,1005,2,'0',1,'11111111'),
(176,1005,4,'0',1,'11111111'),
(177,1005,5,'0',1,'11111111'),
(178,1005,11,'0',2,'11111111'),
(179,1005,3,'0',1,'11111111'),
(180,1006,1,'0',1,'11111111'),
(181,1006,2,'0',1,'11111111'),
(182,1006,4,'0',1,'11111111'),
(183,1006,5,'0',1,'11111111'),
(184,1006,11,'0',2,'11111111'),
(185,1006,3,'0',1,'11111111'),
(186,1007,1,'0',1,'11111111'),
(187,1007,2,'0',1,'11111111'),
(188,1007,4,'0',1,'11111111'),
(189,1007,5,'0',1,'11111111'),
(190,1007,11,'0',2,'11111111'),
(191,1007,3,'0',1,'11111111'),
(265,1008,8,NULL,0,'11111111'),
(266,70107,8,NULL,0,'11111111');
/*!40000 ALTER TABLE `terminaloftask` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminaltype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminaltype` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) DEFAULT NULL COMMENT '终端类型名称',
  `info` varchar(255) DEFAULT '' COMMENT '对终端类型描述',
  `isdecode` int(4) unsigned NOT NULL DEFAULT 0 COMMENT '终端是否可解码1-是 0-否,寻呼',
  `isencode` int(4) unsigned NOT NULL DEFAULT 0 COMMENT '终端是否可编码1-是 0-否，对讲',
  `shortkeycount` int(4) unsigned NOT NULL DEFAULT 0 COMMENT '终端快捷键数目',
  `switchcount` int(4) unsigned NOT NULL DEFAULT 0 COMMENT '终端开关数',
  `isLCD` int(4) unsigned NOT NULL DEFAULT 0 COMMENT '终端是否是LCD1-是 0-否',
  `isWeb` int(4) unsigned NOT NULL DEFAULT 1,
  `isheart` int(4) unsigned NOT NULL DEFAULT 1 COMMENT '终端是否播放1-是 0-否',
  `isspeech` int(4) unsigned NOT NULL DEFAULT 0 COMMENT '终端是否对讲1-是 0-否',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminaltype` WRITE;
/*!40000 ALTER TABLE `terminaltype` DISABLE KEYS */;
INSERT INTO `terminaltype` (`id`, `name`, `info`, `isdecode`, `isencode`, `shortkeycount`, `switchcount`, `isLCD`, `isWeb`, `isheart`, `isspeech`) VALUES (0,'服务器','',0,1,4,1,0,1,0,0),
(1,'网络终端','',1,0,0,1,0,1,1,0),
(2,'网络话筒','',1,1,9,1,1,1,1,1),
(3,'双向寻呼终端','',1,1,9,1,1,1,1,1),
(4,'网络前置','',1,1,9,8,1,1,1,0),
(5,'网络功放','',1,1,9,8,1,1,1,0),
(6,'电源管理器','',0,0,0,10,0,0,1,0),
(7,'报警主机','',0,0,0,16,0,0,1,0),
(8,'采样终端','',0,1,9,4,1,1,1,0),
(9,'电脑','',0,0,0,0,0,0,0,0),
(10,'MP3','',0,0,0,0,0,1,1,0),
(11,'一体化音箱','',1,0,0,0,0,1,1,0),
(12,'分控软件','',1,1,0,0,0,0,1,1),
(13,'一键寻呼终端','',1,1,0,0,0,1,1,1),
(14,'分控前置','',1,1,9,8,1,1,1,1),
(15,'背景音乐','',1,1,0,0,1,1,1,0),
(16,'实话接口','',0,1,0,0,0,1,1,0),
(17,'手机终端','',1,1,200,0,0,1,1,1),
(18,'分控工作站','',0,0,0,0,0,1,1,0),
(19,'透传终端','',1,1,0,0,0,1,1,0),
(20,'网络终端','',1,0,0,1,0,1,1,0),
(21,'监控主机','',1,1,0,0,0,1,1,1),
(22,'TTS主机','',0,1,0,0,0,1,1,0),
(23,'离线终端','',1,1,0,0,0,1,1,0),
(24,'网络音柱/功放','',1,1,0,0,0,1,1,0),
(25,'编码器','',0,1,10,4,0,1,1,0),
(26,'网络调音台','',1,1,9,0,1,1,1,0),
(27,'线阵音柱','',1,0,0,0,0,1,1,0),
(28,'寻呼话筒','',1,1,9,1,1,1,1,1),
(29,'遥控终端','',0,0,0,0,0,1,1,0),
(30,'网络调音台','',1,1,9,0,1,1,1,0),
(31,'网络音频采集器','',0,1,10,4,0,1,1,0),
(32,'TTS主机','',0,1,0,0,0,1,1,0),
(33,'一键寻呼终端','',1,1,0,0,0,1,1,1),
(34,'网络前置','',1,1,9,6,1,1,1,0),
(35,'双向寻呼终端','',1,1,9,1,1,1,1,1),
(36,'网络分区前置','',1,1,9,8,1,1,1,0),
(37,'网络功放','',1,1,9,6,1,1,1,0),
(38,'一体化音箱','',1,1,0,0,0,1,1,0),
(39,'网络前置','',1,1,0,0,0,1,1,0),
(40,'寻呼终端','',1,1,0,0,0,1,1,1),
(41,'应急终端','',1,1,200,0,0,1,1,1),
(42,'LED设备','',1,1,9,8,0,1,1,1),
(43,'小区广播主机','',0,0,0,0,0,1,1,0),
(44,'防爆终端','',1,1,9,0,0,1,1,1),
(45,'网络话筒','',1,1,9,1,0,1,1,1),
(46,'一键寻呼终端','',1,1,0,0,0,1,1,1),
(47,'网络前置','',1,1,0,0,0,1,1,0);
/*!40000 ALTER TABLE `terminaltype` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `terminaltypekey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terminaltypekey` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '终端键名称根据terminaltype表shortkeycount确定',
  `terminaltype` int(10) NOT NULL COMMENT '终端类型与terminaltype表中id确定',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `terminaltypekey` WRITE;
/*!40000 ALTER TABLE `terminaltypekey` DISABLE KEYS */;
INSERT INTO `terminaltypekey` (`id`, `name`, `terminaltype`) VALUES (1,'1',1),
(2,'2',1),
(3,'3',1),
(4,'4',1),
(5,'5',1),
(6,'6',1),
(7,'7',1),
(8,'8',1),
(9,'9',1),
(10,'1',2),
(11,'2',2),
(12,'3',2),
(13,'4',2),
(14,'5',2),
(15,'6',2),
(16,'7',2),
(17,'8',2),
(18,'9',2),
(19,'1',3),
(20,'2',3),
(21,'3',3),
(22,'4',3),
(23,'5',3),
(24,'6',3),
(25,'7',3),
(26,'8',3),
(27,'9',3),
(28,'1',4),
(29,'2',4),
(30,'3',4),
(31,'4',4),
(32,'5',4),
(33,'6',4),
(34,'7',4),
(35,'8',4),
(36,'9',4),
(37,'1',5),
(38,'2',5),
(39,'3',5),
(40,'4',5),
(41,'5',5),
(42,'6',5),
(43,'7',5),
(44,'8',5),
(45,'9',5),
(51,'1',14),
(52,'2',14),
(53,'3',14),
(54,'4',14),
(55,'5',14),
(56,'1',13),
(57,'2',13),
(58,'3',13),
(59,'4',13),
(60,'5',13),
(61,'6',13),
(62,'7',13),
(63,'8',13),
(64,'9',13),
(65,'1',8),
(66,'2',8),
(67,'3',8),
(68,'4',8),
(69,'5',8),
(70,'6',8),
(71,'7',8),
(72,'8',8),
(73,'9',8),
(74,'1',11),
(75,'2',11),
(76,'3',11),
(77,'4',11),
(78,'5',11),
(79,'6',11),
(80,'7',11),
(81,'8',11),
(82,'9',11),
(83,'',0),
(84,'',0),
(85,'',0),
(86,'6',14),
(87,'7',14),
(88,'8',14),
(89,'9',14),
(90,'1',15),
(91,'2',15),
(92,'3',15),
(94,'4',15),
(95,'5',15),
(96,'6',15),
(97,'7',15),
(98,'8',15),
(99,'9',15),
(100,'10',8),
(101,'0',8);
/*!40000 ALTER TABLE `terminaltypekey` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ttssentence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ttssentence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL COMMENT '语句名称',
  `sentenceid` int(11) DEFAULT NULL COMMENT '语句id',
  `type` int(11) DEFAULT 1 COMMENT '0:音乐，1:约定文字，2:输入文字',
  `mediaid` int(11) NOT NULL DEFAULT 0 COMMENT '媒体id',
  `content` varchar(1400) DEFAULT '""' COMMENT '输入文字内容',
  `mediaseq` int(4) DEFAULT NULL COMMENT '媒体播放顺序',
  `speed` int(4) DEFAULT 0 COMMENT '-50,100',
  `volume` int(4) DEFAULT 0 COMMENT '-100,100',
  `male` int(4) DEFAULT NULL,
  `pitch` tinyint(3) DEFAULT 50 COMMENT '语调',
  `rdn` tinyint(3) DEFAULT 0 COMMENT '音频数字发音，\r\n0 数值优先,\r\n1 完全数值,\r\n2 完全字符串，\r\n3 字符串优先，',
  `rcn` tinyint(3) DEFAULT 0 COMMENT '1 的中文发音，\r\n0：表示发音为yao\r\n1：表示发音为yi',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ttssentence` WRITE;
/*!40000 ALTER TABLE `ttssentence` DISABLE KEYS */;
INSERT INTO `ttssentence` (`id`, `name`, `sentenceid`, `type`, `mediaid`, `content`, `mediaseq`, `speed`, `volume`, `male`, `pitch`, `rdn`, `rcn`) VALUES (5,'课间提醒',143,2,0,'请同学们有序下楼活动，注意安全。',0,5,80,0,50,0,0),
(6,'放学安全提示',144,2,0,'放学请注意交通安全，排队有序离校。',1,5,60,0,5,0,0),
(7,'防欺凌广播',145,2,0,'同学之间要互相尊重，遇到欺凌请及时向老师报告。',1,5,60,1,5,0,0);
/*!40000 ALTER TABLE `ttssentence` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ttstaskinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ttstaskinfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskname` varchar(255) DEFAULT NULL COMMENT '方案名称',
  `idcode` varchar(32) DEFAULT NULL COMMENT 'tts识别码',
  `starttrain` varchar(32) DEFAULT NULL COMMENT '始发站',
  `endtrain` varchar(32) DEFAULT NULL COMMENT '终点站',
  `train` varchar(32) DEFAULT NULL COMMENT '列车站',
  `lane` varchar(32) DEFAULT NULL COMMENT '车道',
  `stationmaster` varchar(32) DEFAULT NULL COMMENT '站长',
  `station` varchar(32) DEFAULT NULL COMMENT '站台',
  `outchannel` varchar(32) DEFAULT NULL COMMENT '出站通道',
  `route` int(11) DEFAULT NULL COMMENT '行程',
  `delaytime` varchar(255) DEFAULT '00:00:00' COMMENT '延时',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ttstaskinfo` WRITE;
/*!40000 ALTER TABLE `ttstaskinfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `ttstaskinfo` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `ttstext`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ttstext` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL COMMENT '约定文字名称',
  `seq` int(4) DEFAULT NULL COMMENT '字段序号',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `ttstext` WRITE;
/*!40000 ALTER TABLE `ttstext` DISABLE KEYS */;
/*!40000 ALTER TABLE `ttstext` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `usergroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usergroup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '' COMMENT '用户组名称',
  `info` varchar(128) DEFAULT NULL COMMENT '用户组描述',
  `welcome` varchar(128) DEFAULT NULL COMMENT '用户组附加信息',
  `taskpriv` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '文件广播权限1-有 0-无',
  `terminalpriv` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '终端权限1-有 0-无',
  `mediapriv` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '上传权限1-有 0-无',
  `userpriv` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户权限1-有 0-无',
  `serverpriv` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '服务器权限1-有 0-无',
  `folderpriv` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '目录权限1-有 0-无',
  `terminalgrouppriv` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '分区权限1-有 0-无',
  `alarmgrouppriv` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '报警权限1-有 0-无',
  `bellpriv` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '方案权限1-有 0-无',
  `admpriv` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '采播权限1-有 0-无',
  `telephonepriv` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '电话采播权限1-有 0-无',
  `powerplay` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '电源权限1-有 0-无',
  `level` int(10) unsigned NOT NULL DEFAULT 3 COMMENT '用户组级别 最大值5',
  `ttspriv` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '文字语音权限1-有 0-无',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `usergroup` WRITE;
/*!40000 ALTER TABLE `usergroup` DISABLE KEYS */;
INSERT INTO `usergroup` (`id`, `name`, `info`, `welcome`, `taskpriv`, `terminalpriv`, `mediapriv`, `userpriv`, `serverpriv`, `folderpriv`, `terminalgrouppriv`, `alarmgrouppriv`, `bellpriv`, `admpriv`, `telephonepriv`, `powerplay`, `level`, `ttspriv`) VALUES (1,'system group','','',1,1,1,1,1,1,1,1,1,1,1,1,10,1),
(2,'操作员',NULL,NULL,1,1,1,0,0,1,1,1,1,0,1,1,5,1),
(3,'只读查看',NULL,NULL,0,0,0,0,0,0,0,0,0,0,0,0,1,0);
/*!40000 ALTER TABLE `usergroup` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `usersn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usersn` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `sn` varchar(32) DEFAULT NULL,
  `userid` int(10) NOT NULL,
  `enable` int(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`,`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `usersn` WRITE;
/*!40000 ALTER TABLE `usersn` DISABLE KEYS */;
/*!40000 ALTER TABLE `usersn` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `userterminal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `userterminal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL COMMENT '记录用户与终端关系',
  `terminalid` int(10) unsigned NOT NULL COMMENT '记录用户与终端关系',
  `groupid` int(4) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `userterminal` WRITE;
/*!40000 ALTER TABLE `userterminal` DISABLE KEYS */;
INSERT INTO `userterminal` (`id`, `userid`, `terminalid`, `groupid`) VALUES (1,1,6,0),
(2,1,1,0),
(3,1,11,0),
(4,1,3,0),
(5,1,10,0),
(6,1,7,0),
(7,1,8,0);
/*!40000 ALTER TABLE `userterminal` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `usertype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usertype` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `info` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `usertype` WRITE;
/*!40000 ALTER TABLE `usertype` DISABLE KEYS */;
/*!40000 ALTER TABLE `usertype` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

