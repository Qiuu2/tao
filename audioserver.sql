/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

DROP TABLE IF EXISTS `TKdeviceinfo`;

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


/*Table structure for table `TKstationinfo` */

DROP TABLE IF EXISTS `TKstationinfo`;

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

/*Data for the table `TKstationinfo` */


DROP TABLE IF EXISTS `ai_device`;

CREATE TABLE `ai_device` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT COMMENT '人员id',
  `shibiedeviceid` varchar(32) NOT NULL COMMENT '人脸识别机编号',
  `terminalid` int(4) NOT NULL COMMENT '终端设备id',
  `groupid` int(4) DEFAULT 0,
  PRIMARY KEY (`id`,`shibiedeviceid`,`terminalid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ai_device` */

/*Table structure for table `ai_devicedemo` */

DROP TABLE IF EXISTS `ai_devicedemo`;

CREATE TABLE `ai_devicedemo` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `shibiedeviceid` int(8) DEFAULT NULL COMMENT '人脸设备id',
  `deviceaddr` varchar(32) DEFAULT NULL COMMENT '人脸设备名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ai_devicedemo` */

/*Table structure for table `ai_people` */

DROP TABLE IF EXISTS `ai_people`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ai_people` */

/*Table structure for table `ai_timetts` */

DROP TABLE IF EXISTS `ai_timetts`;

CREATE TABLE `ai_timetts` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT COMMENT '时间和播报文字设置id',
  `time` varchar(32) NOT NULL COMMENT '时间设置',
  `demo` varchar(128) NOT NULL COMMENT '播报文字设置',
  `enable` int(4) NOT NULL,
  `volume` int(4) DEFAULT 80,
  PRIMARY KEY (`id`,`time`,`demo`,`enable`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ai_timetts` */

insert  into `ai_timetts`(`id`,`time`,`demo`,`enable`) values (1,'8:0-12:0','小朋友欢迎到校',1),(2,'12:0-14:0','小朋友午休',1),(3,'14:0-21:0','小朋友放学再见',1);

/*Table structure for table `alarmarea` */

DROP TABLE IF EXISTS `alarmarea`;

CREATE TABLE `alarmarea` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '报警主机' COMMENT '报警分区名称',
  `info` varchar(45) NOT NULL DEFAULT '报警主机' COMMENT '报警分区描述',
  `createtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP() COMMENT '报警分区创建的时间',
  `userid` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `alarmarea` */

/*Table structure for table `alarmgroupmap` */

DROP TABLE IF EXISTS `alarmgroupmap`;

CREATE TABLE `alarmgroupmap` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `info` varchar(45) NOT NULL COMMENT '备注',
  `alarmterminalid` int(10) unsigned NOT NULL COMMENT '报警主机的设备ID',
  `alarmchannel` int(10) unsigned NOT NULL COMMENT '报警主机的通道号',
  `firealarmgroupid` int(10) unsigned NOT NULL COMMENT '对应的报警分区',
  `mediaid` int(10) unsigned NOT NULL COMMENT '报警时播放的媒体文件',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `alarmgroupmap` */

/*Table structure for table `audiocodectype` */

DROP TABLE IF EXISTS `audiocodectype`;

CREATE TABLE `audiocodectype` (
  `id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `info` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `audiocodectype` */

/*Table structure for table `audioformat` */

DROP TABLE IF EXISTS `audioformat`;

CREATE TABLE `audioformat` (
  `id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;


/*Data for the table `audioformat` */

/*Table structure for table `audioserver` */

DROP TABLE IF EXISTS `audioserver`;

CREATE TABLE `audioserver` (
  `id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `ip` varchar(255) NOT NULL DEFAULT '',
  `port` int(11) NOT NULL DEFAULT '3333',
  `maxterminal` int(11) NOT NULL DEFAULT '100',
  `maxtask` int(11) DEFAULT '100',
  `workstate` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `audioserver` */

/*Table structure for table `audiosource` */

DROP TABLE IF EXISTS `audiosource`;

CREATE TABLE `audiosource` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '终端声源名称',
  `info` varchar(45) NOT NULL DEFAULT '' COMMENT '终端声源描述',
  `powermgrid` int(10) unsigned NOT NULL COMMENT '终端电源号',
  `powermgrchannel` int(10) unsigned NOT NULL COMMENT '终端电源通道',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `audiosource` */

insert  into `audiosource`(`id`,`name`,`info`,`powermgrid`,`powermgrchannel`) values (1,'VCD','数据来自VCD',1,1),(2,'DVR','数据来自DVD',1,2),(3,'广播','数据来自广播',1,3),(4,'话筒','数据来自话筒',1,4);

/*Table structure for table `belltask` */

DROP TABLE IF EXISTS `belltask`;

CREATE TABLE `belltask` (
  `id` int(20) NOT NULL AUTO_INCREMENT COMMENT '此表没有使用',
  `belltaskname` varchar(255) NOT NULL,
  `prepower` int(10) NOT NULL DEFAULT '0' COMMENT '0立即 非0预等待',
  `startdate` date NOT NULL,
  `enddate` date NOT NULL,
  `exemodel` varchar(255) NOT NULL DEFAULT '' COMMENT '直接用字符串表示星期几,0表示每天',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `belltask` */

/*Table structure for table `book_admin` */

DROP TABLE IF EXISTS `book_admin`;

CREATE TABLE `book_admin` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '登陆用户名称只有字母数字中文合法',
  `userpwd` varchar(100) NOT NULL DEFAULT '' COMMENT '登陆用户密码使用md5加密',
  `usergroupid` int(12) unsigned NOT NULL DEFAULT '0' COMMENT '用户所属组',
  `info` varchar(255) DEFAULT '' COMMENT '用户信息',
  `fullname` varchar(50) DEFAULT '' COMMENT '用户附加信息',
  `usersessionid` varchar(255) DEFAULT '' COMMENT '用户登录会话ID',
  `ctrlwind` int(4) NOT NULL DEFAULT 0 COMMENT '手机分控ID ',
  `enable` int(4) NOT NULL DEFAULT 1,
  `subwind` int(10) DEFAULT 0 COMMENT '分控软件ID',
  `camerawind` int(10) DEFAULT 0 COMMENT '监控软件ID',
  `loginnum` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `book_admin` */

insert  into `book_admin`(`id`,`username`,`userpwd`,`usergroupid`,`info`,`fullname`,`usersessionid`,`ctrlwind`,`enable`,`subwind`,`camerawind`,`loginnum`) values (1,'admin','e10adc3949ba59abbe56e057f20f883e',1,'administrator','administrator','',1,1,1001,2001,0);

/*Table structure for table `book_msg` */

DROP TABLE IF EXISTS `book_msg`;

CREATE TABLE `book_msg` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) CHARACTER SET gbk NOT NULL DEFAULT '',
  `content` text CHARACTER SET gbk NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip` varchar(30) CHARACTER SET gbk NOT NULL DEFAULT '',
  `sh` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `book_msg` */

/*Table structure for table `book_reply` */

DROP TABLE IF EXISTS `book_reply`;

CREATE TABLE `book_reply` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `m_id` int(12) NOT NULL DEFAULT '0',
  `content` text NOT NULL,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `book_reply` */

/*Table structure for table `callgroup` */

DROP TABLE IF EXISTS `callgroup`;

CREATE TABLE `callgroup` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL,
  `terminalid` int(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `callgroup` */

/*Table structure for table `camer` */

DROP TABLE IF EXISTS `camer`;

CREATE TABLE `camer` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `camername` varchar(256) DEFAULT NULL,
  `camerip` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `camer` */


/*Table structure for table `camer_alarm` */

DROP TABLE IF EXISTS `camer_alarm`;

CREATE TABLE `camer_alarm` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `eventtype` int(4) DEFAULT '0',
  `eventname` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `camer_alarm` */


/*Table structure for table `camer_alarmofmedia` */

DROP TABLE IF EXISTS `camer_alarmofmedia`;

CREATE TABLE `camer_alarmofmedia` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `mediaid` int(4) DEFAULT '0',
  `eventid` int(4) DEFAULT '0',
  `sort` int(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `camer_alarmofmedia` */
/*Table structure for table `cameramap` */

DROP TABLE IF EXISTS `cameramap`;

CREATE TABLE `cameramap` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `terminalid` int(4) NOT NULL,
  `ipaddr` varchar(16) NOT NULL,
  `mac` varchar(32) NOT NULL DEFAULT '00.00.00.00.00.00',
  `username` varchar(32) NOT NULL DEFAULT 'admin',
  `password` varchar(32) NOT NULL DEFAULT '12345',
  `port` int(4) NOT NULL DEFAULT '8000',
  `camername` varchar(32) DEFAULT NULL,
  `camerstate` int(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `cameramap` */

/*Table structure for table `camerofterminal` */

DROP TABLE IF EXISTS `camerofterminal`;

CREATE TABLE `camerofterminal` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `camerid` int(4) DEFAULT NULL,
  `terminalid` int(4) DEFAULT NULL,
  `area` varchar(16) DEFAULT '1111111111111111',
  `groupid` int(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `camerofterminal` */


/*Table structure for table `centralctrl` */

DROP TABLE IF EXISTS `centralctrl`;

CREATE TABLE `centralctrl` (
  `terminalid` int(10) NOT NULL COMMENT '终端ID',
  `pcstate` tinyint(1) DEFAULT '0' COMMENT '电脑开关',
  `projectionstate` tinyint(1) DEFAULT '0' COMMENT '投影机开关',
  `systemstate` tinyint(1) DEFAULT '0' COMMENT '系统开关',
  `volstate` tinyint(1) DEFAULT '1' COMMENT '音量开关',
  `volume` tinyint(4) DEFAULT '80' COMMENT '音量',
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
  `netstate` int(4) DEFAULT '1',
  `terminalname` varchar(32) DEFAULT NULL,
  `typeid` int(4) DEFAULT NULL,
  PRIMARY KEY (`terminalid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `centralctrl` */
/*Table structure for table `ctrldevice` */

DROP TABLE IF EXISTS `ctrldevice`;

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
  `netstate` int(4) DEFAULT '0',
  `defaultstate` varchar(32) DEFAULT NULL,
  `deviceimgid` int(4) DEFAULT NULL,
  PRIMARY KEY (`deviceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ctrldevice` */

/*Table structure for table `ctrldevicetype` */

DROP TABLE IF EXISTS `ctrldevicetype`;

CREATE TABLE `ctrldevicetype` (
  `id` int(11) NOT NULL COMMENT '设备类型ID',
  `name` varchar(128) DEFAULT NULL COMMENT '设备类型名称',
  `info` varchar(128) DEFAULT NULL COMMENT '设备类型属性',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ctrldevicetype` */

insert  into `ctrldevicetype`(`id`,`name`,`info`) values (1,'灯',NULL),(2,'窗帘',NULL),(3,'空调',NULL),(4,'插座',NULL),(5,'场景',NULL),(6,'多功能控制器',NULL),(7,'可调光灯',NULL);

/*Table structure for table `ctrlnet` */

DROP TABLE IF EXISTS `ctrlnet`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ctrlnet` */

/*Table structure for table `ctrloftask` */

DROP TABLE IF EXISTS `ctrloftask`;

CREATE TABLE `ctrloftask` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ctrldeviceid` int(11) NOT NULL COMMENT '设备ID',
  `ctrltaskid` int(11) NOT NULL COMMENT '终端ID',
  `state` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ctrloftask` */

/*Table structure for table `ctrloftermianl` */

DROP TABLE IF EXISTS `ctrloftermianl`;

CREATE TABLE `ctrloftermianl` (
  `id` int(4) NOT NULL,
  `terminalID` int(4) NOT NULL COMMENT '终端id',
  `netID` int(4) NOT NULL COMMENT '网络ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ctrloftermianl` */

/*Table structure for table `ctrltask` */

DROP TABLE IF EXISTS `ctrltask`;

CREATE TABLE `ctrltask` (
  `taskid` int(11) NOT NULL AUTO_INCREMENT,
  `taskname` varchar(128) NOT NULL COMMENT '任务名称',
  `cmd` varchar(128) NOT NULL COMMENT '下发指令',
  `value` varchar(128) NOT NULL COMMENT '扩张指令',
  `starttime` time DEFAULT NULL COMMENT '命令执行时间',
  `startdate` date DEFAULT NULL COMMENT '生效日期',
  `enddate` date DEFAULT NULL COMMENT '结束日期',
  PRIMARY KEY (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ctrltask` */

/*Table structure for table `employees` */

DROP TABLE IF EXISTS `employees`;

CREATE TABLE `employees` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `first` varchar(20) DEFAULT NULL,
  `last` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `employees` */

DROP TABLE IF EXISTS `enabletask`;
CREATE TABLE `enabletask` (
  `id` int(4) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `enstate` varchar(1024) DEFAULT '0',
  `startdate` date DEFAULT NULL,
  `starttime` time DEFAULT NULL,
  `taskid` varchar(2048) DEFAULT NULL,
  `flag` int(4) DEFAULT 0,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*Table structure for table `filefolder` */

DROP TABLE IF EXISTS `filefolder`;

CREATE TABLE `filefolder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名称',
  `userid` int(10) NOT NULL COMMENT '该文件夹是谁创建',
  `priority` int(10) NOT NULL DEFAULT 0 COMMENT '文件夹是否共享0-不共享 1-共享',
  `createtime` timestamp NULL DEFAULT current_timestamp() COMMENT '文件夹创建时间',
  `parentid` int(10) NOT NULL DEFAULT 0 COMMENT '父目录id',
  PRIMARY KEY (`id`,`parentid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `filefolder` */

insert  into `filefolder`(`id`,`name`,`userid`,`priority`,`createtime`,`parentid`) values (1,'共享媒体库',1,1,'2011-06-14 20:11:30',0),(2,'铃声媒体库',1,1,'2011-06-14 20:11:44',0),(3,'点播媒体库',1,1,'2011-06-21 19:30:37',0),(4,'报警媒体库',1,1,'2011-06-21 19:30:38',0),(5,'录音媒体库',1,1,'2011-06-21 19:58:02',0),(6,'语音合成媒体库',1,1,'2011-06-21 19:58:02',0),(7,'设备录音媒体',1,0,'2018-05-18 19:21:58',5),(8,'录音媒体库',1,0,'2021-03-30 11:07:22',5),(9,'提示音',1,0,'2021-03-30 11:07:22',1);

/*Table structure for table `filetaskfree` */

DROP TABLE IF EXISTS `filetaskfree`;

CREATE TABLE `filetaskfree` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(16) DEFAULT NULL,
  `parentid` int(4) DEFAULT NULL,
  `userid` int(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `filetaskfree` */

insert  into `filetaskfree`(`id`,`name`,`parentid`,`userid`) values (1,'admin',0,1);

/*Table structure for table `holidaytime` */

DROP TABLE IF EXISTS `holidaytime`;

CREATE TABLE `holidaytime` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `startdate` date DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  `name` varchar(32) DEFAULT NULL,
  `projectstate` int(11) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `holidaytime` */

/*Table structure for table `terminaltypekey` */

DROP TABLE IF EXISTS `terminaltypekey`;

CREATE TABLE `terminaltypekey` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '终端键名称根据terminaltype表shortkeycount确定',
  `terminaltype` int(10) NOT NULL COMMENT '终端类型与terminaltype表中id确定',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `terminaltypekey` */

insert  into `terminaltypekey`(`id`,`name`,`terminaltype`) values (1,'1',1),(2,'2',1),(3,'3',1),(4,'4',1),(5,'5',1),(6,'6',1),(7,'7',1),(8,'8',1),(9,'9',1),(10,'1',2),(11,'2',2),(12,'3',2),(13,'4',2),(14,'5',2),(15,'6',2),(16,'7',2),(17,'8',2),(18,'9',2),(19,'1',3),(20,'2',3),(21,'3',3),(22,'4',3),(23,'5',3),(24,'6',3),(25,'7',3),(26,'8',3),(27,'9',3),(28,'1',4),(29,'2',4),(30,'3',4),(31,'4',4),(32,'5',4),(33,'6',4),(34,'7',4),(35,'8',4),(36,'9',4),(37,'1',5),(38,'2',5),(39,'3',5),(40,'4',5),(41,'5',5),(42,'6',5),(43,'7',5),(44,'8',5),(45,'9',5),(51,'1',14),(52,'2',14),(53,'3',14),(54,'4',14),(55,'5',14),(56,'1',13),(57,'2',13),(58,'3',13),(59,'4',13),(60,'5',13),(61,'6',13),(62,'7',13),(63,'8',13),(64,'9',13),(65,'1',8),(66,'2',8),(67,'3',8),(68,'4',8),(69,'5',8),(70,'6',8),(71,'7',8),(72,'8',8),(73,'9',8),(74,'1',11),(75,'2',11),(76,'3',11),(77,'4',11),(78,'5',11),(79,'6',11),(80,'7',11),(81,'8',11),(82,'9',11),(83,'',0),(84,'',0),(85,'',0),(86,'6',14),(87,'7',14),(88,'8',14),(89,'9',14),(90,'1',15),(91,'2',15),(92,'3',15),(94,'4',15),(95,'5',15),(96,'6',15),(97,'7',15),(98,'8',15),(99,'9',15),(100,'10',8),(101,'0',8);

/*Table structure for table `leddevice` */

DROP TABLE IF EXISTS `leddevice`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `leddevice` */

/*Table structure for table `ledoftask` */

DROP TABLE IF EXISTS `ledoftask`;

CREATE TABLE `ledoftask` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskid` int(11) DEFAULT NULL,
  `terminalid` int(11) DEFAULT NULL,
  `deviceid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ledoftask` */

/*Table structure for table `ledsentence` */

DROP TABLE IF EXISTS `ledsentence`;

CREATE TABLE `ledsentence` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(1024) NOT NULL,
  `mediaid` int(11) NOT NULL,
  `speed` int(11) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `mediaseq` int(11) DEFAULT 0,
  `ledmode` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ledsentence` */

/*Table structure for table `ledtaskfree` */

DROP TABLE IF EXISTS `ledtaskfree`;

CREATE TABLE `ledtaskfree` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(16) DEFAULT NULL,
  `parentid` int(4) DEFAULT NULL,
  `userid` int(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ledtaskfree` */
insert  into `ledtaskfree`(`id`,`name`,`parentid`,`userid`) values (2,'1',0,1);

/*Table structure for table `log` */

DROP TABLE IF EXISTS `log`;

CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(11) NOT NULL DEFAULT '' COMMENT '记录那个用户',
  `operate` varchar(255) NOT NULL DEFAULT '' COMMENT '记录用户做了什么',
  `ip` varchar(64) NOT NULL DEFAULT '' COMMENT '记录用户IP',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '用户动作的时间',
  `info` varchar(255) DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `log` */


/*Table structure for table `logincheck` */

DROP TABLE IF EXISTS `logincheck`;

CREATE TABLE `logincheck` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ip` varchar(32) DEFAULT NULL,
  `loginnum` int(10) DEFAULT NULL,
  `logintime` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `logincheck` */

/*Table structure for table `logmedialist` */

DROP TABLE IF EXISTS `logmedialist`;

CREATE TABLE `logmedialist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mediaid` int(11) NOT NULL DEFAULT '0',
  `taskid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `logmedialist` */

/*Table structure for table `logtask` */

DROP TABLE IF EXISTS `logtask`;

CREATE TABLE `logtask` (
  `taskid` int(11) NOT NULL AUTO_INCREMENT,
  `taskname` varchar(255) NOT NULL DEFAULT '',
  `streamid` int(11) NOT NULL DEFAULT '0',
  `state` int(11) DEFAULT '0',
  `startdate` date NOT NULL DEFAULT '0000-00-00',
  `starttime` time NOT NULL DEFAULT '00:00:00',
  `timelength` time NOT NULL DEFAULT '00:00:00',
  `playmodel` int(11) NOT NULL DEFAULT '0',
  `priority` int(11) DEFAULT '1',
  PRIMARY KEY (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `logtask` */

/*Table structure for table `media` */

DROP TABLE IF EXISTS `media`;

CREATE TABLE `media` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT 'none' COMMENT '媒体文件上传前的名称',
  `size` int(8) unsigned DEFAULT '0' COMMENT '媒体文件大小',
  `typeid` varchar(30) NOT NULL DEFAULT '' COMMENT '媒体文件类型',
  `priority` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '此字段没被使用',
  `filename` varchar(255) NOT NULL DEFAULT 'none' COMMENT '上传后的媒体名称',
  `folderid` int(10) unsigned DEFAULT '0' COMMENT '上传到文件夹',
  `timelength` int(4) unsigned DEFAULT '0' COMMENT '媒体播放长度',
  `channel` int(10) unsigned DEFAULT '0' COMMENT '媒体播放通道',
  `sample` int(10) unsigned DEFAULT '0' COMMENT '媒体采样率',
  `bitrate` int(10) unsigned DEFAULT '128000' COMMENT '媒体比特率',
  `codecid` int(10) unsigned DEFAULT '0',
   `offlinestate` int(10) DEFAULT '0' COMMENT '0=非离线,1=空闲离线,2=立即离线,3=离线完成,4=空闲删除,5=立即删除,6=正在空闲离线,7=正在立即离线',
  `userid` int(4) DEFAULT '0' COMMENT '用户ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `media` */
insert  into `media`(`id`,`name`,`size`,`typeid`,`priority`,`filename`,`folderid`,`timelength`,`channel`,`sample`,`bitrate`,`codecid`,`offlinestate`,`userid`) values (1,'经典提示音',61,'mp3',0,'/backup/mediadata/1617155672594644.mp3',9,5,2,44100,128000,86017,0,0),(124,'大课间.mp3',6018,'mp3',0,'/backup/mediadata/17768378225426810.mp3',2,385,2,44100,128000,0,0,1),(125,'10.起床号.mp3',1406,'mp3',0,'/backup/mediadata/17768378228949191.mp3',2,89,2,44100,128000,0,0,1),(126,'04.爱的纪念（下课）.mp3',350,'mp3',0,'/backup/mediadata/17768378225900880.mp3',2,22,2,44100,128000,0,0,1),(127,'出旗曲.mp3',2630,'mp3',0,'/backup/mediadata/17768378222567017.mp3',2,168,2,44100,128000,0,0,1),(128,'04.爱的纪念（上课）.mp3',345,'mp3',0,'/backup/mediadata/17768378222714337.mp3',2,22,2,44100,128000,0,0,1),(129,'电铃声.mp3',278,'mp3',0,'/backup/mediadata/17768378225143403.mp3',2,17,2,44100,128000,0,0,1),(130,'上课铃.mp3',289,'mp3',0,'/backup/mediadata/17768378227150187.mp3',2,18,2,44100,128000,0,0,1),(131,'放学.mp3',828,'mp3',0,'/backup/mediadata/17768378226406700.mp3',2,42,2,44100,160000,0,0,1),(132,'午休.mp3',1184,'mp3',0,'/backup/mediadata/17768378222430988.mp3',2,60,2,44100,160000,0,0,1),(133,'下课铃.mp3',310,'mp3',0,'/backup/mediadata/17768378229798306.mp3',2,19,2,44100,128000,0,0,1),(134,'眼保健操.mp3',306,'mp3',0,'/backup/mediadata/17768378228339626.mp3',2,19,2,44100,128000,0,0,1),(135,'预备铃.mp3',432,'mp3',0,'/backup/mediadata/17768378238107705.mp3',2,27,2,44100,128000,0,0,1),(136,'运动员进行曲.mp3',2655,'mp3',0,'/backup/mediadata/17768378235315483.mp3',2,169,2,44100,128000,0,0,1),(137,'早读预备铃.mp3',1565,'mp3',0,'/backup/mediadata/17768378237905625.mp3',2,100,2,44100,128000,0,0,1),(138,'中华人民共和国国歌.mp3',723,'mp3',0,'/backup/mediadata/17768378231442225.mp3',2,46,2,44100,128000,0,0,1);

/*Table structure for table `mediaoftask` */

DROP TABLE IF EXISTS `mediaoftask`;

CREATE TABLE `mediaoftask` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mediaid` int(11) NOT NULL DEFAULT '0' COMMENT '添加文件广播和作息方案时添加媒体文件的ID',
  `taskid` int(11) NOT NULL DEFAULT '0' COMMENT '添加与媒体ID相关的任务id',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '媒体添加的顺序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `mediaoftask` */

/*Table structure for table `offlinemedia` */

DROP TABLE IF EXISTS `offlinemedia`;

CREATE TABLE `offlinemedia` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT 'none' COMMENT '媒体文件上传前的名称',
  `size` int(8) unsigned DEFAULT '0' COMMENT '媒体文件大小',
  `typeid` varchar(30) NOT NULL DEFAULT '' COMMENT '媒体文件类型',
  `priority` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '此字段没被使用',
  `filename` varchar(255) NOT NULL DEFAULT 'none' COMMENT '上传后的媒体名称',
  `folderid` int(10) unsigned DEFAULT '0' COMMENT '上传到文件夹',
  `timelength` int(4) unsigned DEFAULT '0' COMMENT '媒体播放长度',
  `channel` int(10) unsigned DEFAULT '0' COMMENT '媒体播放通道',
  `sample` int(10) unsigned DEFAULT '0' COMMENT '媒体采样率',
  `bitrate` int(10) unsigned DEFAULT '128000' COMMENT '媒体比特率',
  `codecid` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `offlinemedia` */

/*Table structure for table `offlinemediaofterminal` */

DROP TABLE IF EXISTS `offlinemediaofterminal`;

CREATE TABLE `offlinemediaofterminal` (
  `mediaid` int(4) NOT NULL,
  `terminalid` int(4) NOT NULL,
  `offlinestate` int(4) DEFAULT '0' COMMENT '0=非离线,1=空闲离线,2=立即离线,3=离线完成,4=空闲删除,5=立即删除,6=正在空闲离线,7=正在立即离线,8=删除完成，9=准备空闲传输，10=准备立即传输，11=停止传输，12=传输已停止',
  `taskid` int(4) NOT NULL DEFAULT '0',
  `sort` int(4) DEFAULT '0',
  PRIMARY KEY (`mediaid`,`terminalid`,`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `offlinemediaofterminal` */

/*Table structure for table `offlinetask` */

DROP TABLE IF EXISTS `offlinetask`;

CREATE TABLE `offlinetask` (
  `taskid` int(11) NOT NULL,
  `taskname` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
  `israndomplay` int(11) NOT NULL DEFAULT '0' COMMENT '0表示随机1表示顺序',
  `projectstate` int(11) NOT NULL DEFAULT '0' COMMENT '方案是否启用，1启用，0停用',
  `timelengthtype` int(11) NOT NULL COMMENT '1为时间，0为循环次数',
  `timelength` int(11) NOT NULL COMMENT '播放时长， 类型为1是是播放秒数，否则为循环次数。',
  `prepower` int(11) NOT NULL COMMENT '与开电源的设备时间，单位分钟',
  `datasendmodel` int(11) NOT NULL COMMENT '数据发送模式，0：单播，1：组播',
  `state` int(11) DEFAULT '0' COMMENT '任务执行状态0-准备 1-执行 2-暂停 3-立即执行',
  `startdate` date NOT NULL DEFAULT '0000-00-00' COMMENT '任务开始执行日期',
  `enddate` date NOT NULL COMMENT '任务结束执行日期',
  `playtime` time NOT NULL DEFAULT '00:00:00' COMMENT '开始执行时间',
  `endtime` time DEFAULT '00:00:00',
  `exemodel` varchar(7) DEFAULT '0000000' COMMENT '1111111表示每天000000表示手动',
  `priority` int(11) NOT NULL DEFAULT '3' COMMENT '对编解码任务有效',
  `tasktype` int(11) NOT NULL COMMENT '任务类型1-作息 2-文件 3-采播 4-电话 5-功放 ',
  `channel` int(11) DEFAULT '0' COMMENT '对编码任务有效，2:双声道,1:单声道',
  `bandrate` int(11) unsigned DEFAULT '0' COMMENT '对编码任务有效',
  `samplerate` int(255) DEFAULT '0' COMMENT '对编码任务有效',
  `cmd` int(10) unsigned DEFAULT '0' COMMENT '要定时执行的命令,类型为5时（0：打开，1：关闭），类型为3时：才播终端ID',
  `cmdargs` char(255) DEFAULT '0' COMMENT '定时执行的命令参数类型为5是保存通道号',
  `playfileid` int(10) unsigned DEFAULT '0' COMMENT '正在播放的设备ID',
  `info` varchar(255) NOT NULL DEFAULT '' COMMENT '只有添加方案时才添加方案名称',
  `defaultvolume` int(10) unsigned DEFAULT '80' COMMENT '任务音量',
  `task_user_id` int(10) DEFAULT '0' COMMENT '记录添加用户的ID',
  `sec_task_id` int(10) unsigned DEFAULT '0' COMMENT '记录任务相关联的ID',
  `parentid` int(10) DEFAULT '0',
  `offlinestate` int(10) DEFAULT '0' COMMENT '0=非离线,1=空闲离线,2=立即离线,3=离线完成,4=空闲删除,5=立即删除,6=正在空闲离线,7=正在立即离线,8=删除完成，9=准备空闲传输，10=准备立即传输，11=停止传输，12=传输已停止',
  PRIMARY KEY (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `offlinetask` */


/*Table structure for table `offlinetaskofterminal` */

DROP TABLE IF EXISTS `offlinetaskofterminal`;

CREATE TABLE `offlinetaskofterminal` (
  `taskid` int(10) NOT NULL,
  `terminalid` int(10) NOT NULL,
  `offlinestate` int(10) DEFAULT '0' COMMENT '0=非离线,1=空闲离线,2=立即离线,3=离线完成,4=空闲删除,5=立即删除,6=正在空闲离线,7=正在立即离线,8=删除完成，9=准备空闲传输，10=准备立即传输，11=停止传输，12=传输已停止',
  `area` varchar(16) DEFAULT '''11111111''',
  PRIMARY KEY (`taskid`,`terminalid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `offlinetaskofterminal` */







/*Table structure for table `playbelloftask` */

DROP TABLE IF EXISTS `playbelloftask`;

CREATE TABLE `playbelloftask` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `lessonname` varchar(255) NOT NULL,
  `belltime` time NOT NULL DEFAULT '00:00:00',
  `bellid` int(20) NOT NULL COMMENT '与媒体表id一致，如果媒体删除则该对该铃声修改',
  `belltimelength` time NOT NULL DEFAULT '00:00:00',
  `belltaskid` int(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `playbelloftask` */

/*Table structure for table `powermgrmap` */

DROP TABLE IF EXISTS `powermgrmap`;

CREATE TABLE `powermgrmap` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `powerterminalid` int(10) unsigned NOT NULL,
  `powerchannel` int(10) unsigned NOT NULL,
  `info` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `powermgrmap` */

/*Table structure for table `serverbaseparam` */

DROP TABLE IF EXISTS `serverbaseparam`;

CREATE TABLE `serverbaseparam` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '设置服务器名称',
  `serverip` varchar(64) DEFAULT NULL,
  `ip` varchar(64) NOT NULL DEFAULT '' COMMENT '设置服务器IP',
  `webport` int(4) NOT NULL DEFAULT '8886' COMMENT 'webport端口号',
  `port` int(4) NOT NULL DEFAULT '8090',
  `hbport` int(4) NOT NULL DEFAULT '8900' COMMENT 'hbport端口号',
  `nodaemon` int(4) NOT NULL DEFAULT '1',
  `udpport` int(4) NOT NULL DEFAULT '8888' COMMENT 'udpport端口号',
  `rtspport` int(4) NOT NULL DEFAULT '8091' COMMENT 'rtspport端口号',
  `maxhttpconnections` int(11) NOT NULL DEFAULT '0' COMMENT '与服务器最多可以连接多少终端数',
  `maxbandwidth` int(11) NOT NULL DEFAULT '0' COMMENT '服务器提供最大的带宽',
  `workstate` int(11) DEFAULT NULL COMMENT '服务器工作状态0-停止 1-运行',
  `currectconnectcount` int(11) DEFAULT '0' COMMENT '当前实际连接数目',
  `currentbandwidth` int(11) DEFAULT '0' COMMENT '当前实际带宽',
  `customlog` varchar(255) NOT NULL DEFAULT '' COMMENT '该字段没使用',
  `mediapath` varchar(255) NOT NULL DEFAULT '/home/' COMMENT '该字段没有使用',
  `terminalchange` int(11) DEFAULT '0' COMMENT '服务器检测到终端变化时修改此值',
  `taskchange` int(11) DEFAULT '0' COMMENT '服务器检测到任务变化时修改此值',
  `serverchange` int(11) DEFAULT '0' COMMENT '服务器变化时修改此值',
  `taskcount` int(11) unsigned DEFAULT '0' COMMENT '活动任务数目',
  `multicastip` char(64) NOT NULL DEFAULT '230.1.1.1' COMMENT '组播IP地址',
  `multicastport` int(4) DEFAULT '34835' COMMENT '组播端口',
  `registerflag` int(4) NOT NULL DEFAULT '0' COMMENT '是否测试成功 0-未注册 1注册,',
  `registerserial` char(128) DEFAULT '""' COMMENT '注册序列号-由服务器端填写',
  `netradiocount` int(4) NOT NULL DEFAULT '2' COMMENT '并行的网络电台数',
  `soundcardcount` int(4) NOT NULL DEFAULT '2' COMMENT '支持的声卡数',
  `ctrlterminalcount` int(4) NOT NULL DEFAULT '1' COMMENT '并发的分控软件数',
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
  `offlineport` int(4) DEFAULT '8901',
  `backupmode` int(4) DEFAULT '0' COMMENT '考试模式',
  `model` int(4) DEFAULT '1' COMMENT '1:主服务器,2:备份服务器',
  `masterip` varchar(64) DEFAULT '' COMMENT '主服务器IP',
  `slaveip` varchar(64) DEFAULT '' COMMENT '备份服务器IP',
  `slavename` varchar(255) DEFAULT 'ha52' COMMENT '备份服务器名称',
  `adjusttime` int(4) DEFAULT '0' COMMENT '校时采集器ID',
  `projectname` varchar(255) DEFAULT '' COMMENT '项目名称',
  `ischeckmac` int(4) DEFAULT 0 COMMENT 'mac限制',
  `sounddetect` tinyint(3) DEFAULT 0,
  `backup` int(4) DEFAULT 0 COMMENT '主从复制',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `serverbaseparam` */

insert  into `serverbaseparam`(`id`,`name`,`serverip`,`ip`,`webport`,`port`,`hbport`,`nodaemon`,`udpport`,`rtspport`,`maxhttpconnections`,`maxbandwidth`,`workstate`,`currectconnectcount`,`currentbandwidth`,`customlog`,`mediapath`,`terminalchange`,`taskchange`,`serverchange`,`taskcount`,`multicastip`,`multicastport`,`registerflag`,`registerserial`,`netradiocount`,`soundcardcount`,`ctrlterminalcount`,`gateway`,`version`,`ntpserver`,`trystartdate`,`subnetmask`,`netstate`,`dataport`,`tryenddate`,`factory`,`dealerinfo`,`offlineport`,`backupmode`,`model`,`masterip`,`slaveip`,`slavename`,`adjusttime`,`projectname`) values (1,'ha51','','192.168.2.159',8886,8090,8900,1,8888,8091,3000,10000000,0,0,0,'','/home/',0,0,0,0,'230.1.1.1',34835,1,'',2,2,4,'192.168.2.1','','','','255.0.0.0',0,32773,'0000-00-00','','',8901,0,1,'12.12.2.51','12.12.2.52','ha52',0,'ht');

-- ----------------------------
-- Table structure for serverconfig
-- ----------------------------
DROP TABLE IF EXISTS `serverconfig`;
CREATE TABLE `serverconfig`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sounddetect` int(4) NOT NULL DEFAULT 0,
  `fuzamima` int(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of serverconfig
-- ----------------------------
INSERT INTO `serverconfig` VALUES (1, 0, 0);


/*Table structure for table `serverinputtype` */

DROP TABLE IF EXISTS `serverinputtype`;

CREATE TABLE `serverinputtype` (
  `id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `info` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `serverinputtype` */

/*Table structure for table `serverplaystream` */

DROP TABLE IF EXISTS `serverplaystream`;

CREATE TABLE `serverplaystream` (
  `streamid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `info` varchar(255) DEFAULT NULL,
  `createtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` int(4) DEFAULT NULL,
  PRIMARY KEY (`streamid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `serverplaystream` */

/*Table structure for table `servers` */

DROP TABLE IF EXISTS `servers`;

CREATE TABLE `servers` (
  `Server_name` char(64) NOT NULL DEFAULT '',
  `Host` char(64) NOT NULL DEFAULT '',
  `Db` char(64) NOT NULL DEFAULT '',
  `Username` char(64) NOT NULL DEFAULT '',
  `Password` char(64) NOT NULL DEFAULT '',
  `Port` int(4) NOT NULL DEFAULT '0',
  `Socket` char(64) NOT NULL DEFAULT '',
  `Wrapper` char(64) NOT NULL DEFAULT '',
  `Owner` char(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`Server_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `servers` */

/*Table structure for table `serverspeech` */

DROP TABLE IF EXISTS `serverspeech`;

CREATE TABLE `serverspeech` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `FileName` varchar(255) NOT NULL DEFAULT '',
  `FileMaxSize` int(11) NOT NULL DEFAULT '32',
  `ACLallow` varchar(255) NOT NULL DEFAULT '0',
  `Launch` varchar(255) DEFAULT NULL,
  `ReadOnlyFile` varchar(255) DEFAULT NULL,
  `isTruncate` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `serverspeech` */

/*Table structure for table `shortcutkeymap` */

DROP TABLE IF EXISTS `shortcutkeymap`;

CREATE TABLE `shortcutkeymap` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(4) DEFAULT NULL,
  `mediaid` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `shortcutkeymap` */

/*Table structure for table `shortcutkeytask` */

DROP TABLE IF EXISTS `shortcutkeytask`;

CREATE TABLE `shortcutkeytask` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `keyid` int(4) NOT NULL,
  `mediaid` int(4) NOT NULL,
  `keyname` varchar(32) NOT NULL,
  PRIMARY KEY (`id`,`keyid`,`mediaid`,`keyname`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `shortcutkeytask` */

/*Table structure for table `sounddevice` */

DROP TABLE IF EXISTS `sounddevice`;

CREATE TABLE `sounddevice` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(16) NOT NULL,
  `devaddr` tinyint(3) unsigned NOT NULL COMMENT '设备地址',
  `name` varchar(32) DEFAULT NULL COMMENT '备注名称',
  `groupid` int(11) DEFAULT NULL COMMENT '声场ID',
  `dbvalue` float DEFAULT NULL COMMENT '探头DB值',
  `sendport` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `sounddevice` */

/*Table structure for table `soundgroup` */

DROP TABLE IF EXISTS `soundgroup`;

CREATE TABLE `soundgroup` (
  `terminalid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL COMMENT '关联soundgroupinfo表ID',
  PRIMARY KEY (`terminalid`,`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `soundgroup` */

/*Table structure for table `soundgroupinfo` */

DROP TABLE IF EXISTS `soundgroupinfo`;

CREATE TABLE `soundgroupinfo` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '分组ID',
  `name` varchar(64) NOT NULL COMMENT '分组名词',
  `userid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `soundgroupinfo` */

/*Table structure for table `soundtask` */

DROP TABLE IF EXISTS `soundtask`;

CREATE TABLE `soundtask` (
  `taskid` int(11) NOT NULL COMMENT '关联task表taskid',
  `devid` int(11) unsigned NOT NULL COMMENT '关联sounddevice表id',
  `volume` tinyint(3) unsigned NOT NULL COMMENT '任务音量',
  `dbvalue` float DEFAULT NULL COMMENT '音量DB值',
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `soundtask` */

insert  into `soundtask`(`taskid`,`devid`,`volume`,`dbvalue`) values (0,0,0,0),(0,0,20,20),(0,0,40,40),(0,0,60,60),(0,0,80,80),(0,0,100,100);

/*Table structure for table `task` */

DROP TABLE IF EXISTS `task`;

CREATE TABLE `task` (
  `taskid` int(11) NOT NULL AUTO_INCREMENT,
  `taskname` varchar(255) NOT NULL DEFAULT '' COMMENT '任务名称',
  `israndomplay` int(11) NOT NULL DEFAULT '0' COMMENT '0表示随机1表示顺序',
  `projectstate` int(11) NOT NULL DEFAULT '0' COMMENT '方案是否启用，1启用，0停用',
  `timelengthtype` int(11) NOT NULL COMMENT '1为时间，2为循环次数',
  `timelength` int(11) NOT NULL COMMENT '播放时长， 类型为1是是播放秒数，否则为循环次数。',
  `prepower` int(11) NOT NULL COMMENT '与开电源的设备时间，单位分钟',
  `datasendmodel` int(11) NOT NULL COMMENT '数据发送模式，0：单播，1：组播',
  `state` int(11) DEFAULT '0' COMMENT '任务执行状态0-准备 1-执行 2-暂停 3-立即执行',
  `startdate` date NOT NULL DEFAULT '0000-00-00' COMMENT '任务开始执行日期',
  `enddate` date NOT NULL COMMENT '任务结束执行日期',
  `playtime` time NOT NULL DEFAULT '00:00:00' COMMENT '开始执行时间',
  `endtime` time DEFAULT '00:00:00',
  `exemodel` varchar(7) NOT NULL DEFAULT '0000000' COMMENT '1111111表示每天000000表示手动',
  `priority` int(11) NOT NULL DEFAULT '3' COMMENT '对编解码任务有效',
  `tasktype` int(11) NOT NULL COMMENT '任务类型1-作息 2-文件 3-采播 4-电话 5-功放 ',
  `channel` int(11) DEFAULT '0' COMMENT '对编码任务有效，2:双声道,1:单声道',
  `bandrate` int(11) DEFAULT '0' COMMENT '对编码任务有效',
  `samplerate` int(255) DEFAULT '0' COMMENT '对编码任务有效',
  `cmd` int(10) unsigned DEFAULT '0' COMMENT '要定时执行的命令,类型为5时（0：打开，1：关闭），类型为3时：才播终端ID',
  `cmdargs` char(255) DEFAULT '0' COMMENT '定时执行的命令参数类型为5是保存通道号',
  `playfileid` int(10) unsigned DEFAULT '0' COMMENT '正在播放的设备ID',
  `info` varchar(255) NOT NULL DEFAULT '' COMMENT '只有添加方案时才添加方案名称',
  `defaultvolume` int(10) unsigned DEFAULT '80' COMMENT '任务音量',
  `task_user_id` int(10) DEFAULT '0' COMMENT '记录添加用户的ID',
  `sec_task_id` int(10) unsigned DEFAULT '0' COMMENT '记录任务相关联的ID',
  `parentid` int(10) DEFAULT '0' COMMENT '父ID',
   `offlinestate` int(10) DEFAULT '0' COMMENT '0=非离线,1=空闲离线,2=立即离线,3=离线完成,4=空闲删除,5=立即删除,6=正在空闲离线,7=正在立即离线',
	 `createtime` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  `disableday` date DEFAULT '0000-00-00' COMMENT '当天停用',
   `interval_s` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '播放间隔时间',
  `intplaylength` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '间隔播放时长',
  `intplaylengthtype` tinyint(10) unsigned NOT NULL DEFAULT '2' COMMENT '间隔播放模式1：时间,2：循环',
  `localplay` tinyint(10) unsigned NOT NULL DEFAULT 0 COMMENT '是否优先本地播放0：网络播放,1：本地播放',
  `keyid` tinyint(10) DEFAULT 0,
  PRIMARY KEY (`taskid`)
) ENGINE=InnoDB AUTO_INCREMENT=70000 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `task` */
insert  into `task`(`taskid`,`taskname`,`israndomplay`,`projectstate`,`timelengthtype`,`timelength`,`prepower`,`datasendmodel`,`state`,`startdate`,`enddate`,`playtime`,`endtime`,`exemodel`,`priority`,`tasktype`,`channel`,`bandrate`,`samplerate`,`cmd`,`playfileid`,`info`,`defaultvolume`,`task_user_id`,`sec_task_id`,`parentid`) values (70000,'reset',0,0,1,0,2,0,0,'2011-01-01','2060-01-01','04:00:00','00:00:00','0001000',10,13,0,0,0,0,0,'',0,1,417,NULL);

DROP TABLE IF EXISTS `tempctrlnet2`;
CREATE TABLE `tempctrlnet2` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `nettypeid` int(11) DEFAULT NULL,
  `filepath` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*Table structure for table `terminal` */

DROP TABLE IF EXISTS `terminal`;

CREATE TABLE `terminal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupid` int(11) DEFAULT '0' COMMENT '只对有解码码功能的设备有效，终端分区ID',
  `terminalname` varchar(255) NOT NULL DEFAULT '' COMMENT '通用字段，所有终端有效',
  `typeid` int(11) NOT NULL DEFAULT '-1' COMMENT '通用字段，所有终端有效',
  `netstate` int(11) DEFAULT '1' COMMENT '通用字段，所有终端有效',
  `devicestate` int(1) DEFAULT '1' COMMENT '通用字段，所有终端有效',
  `taskstate` int(1) DEFAULT '0' COMMENT '通用字段，所有终端有效',
  `ip` varchar(255) NOT NULL DEFAULT '192.168.1.111' COMMENT '通用字段，所有终端有效',
  `postion` varchar(255) CHARACTER SET gbk DEFAULT NULL COMMENT '只对有编解码功能的设备有效',
  `volume` int(11) DEFAULT '50' COMMENT '只对有编解码功能的设备有效',
  `sample` int(11) DEFAULT '44100' COMMENT '只对有编码功能的设备有效',
  `bitrate` int(11) DEFAULT '32000' COMMENT '终端编码是的比特率',
  `channel` int(11) DEFAULT '2' COMMENT '不同终端定义不同，一般指终端支持的电源控制的路数',
  `firealarmgroup` int(11) DEFAULT '-1' COMMENT '只对有解码功能的终端有效-1表示没有分区',
  `audiocodec` int(11) DEFAULT '3' COMMENT '只对有编码功能的设备有效',
  `inputformat` int(11) DEFAULT '3' COMMENT '只对有编码功能的设备有效',
  `outformat` int(11) DEFAULT '3' COMMENT '只对有编码功能的设备有效',
  `isspeech` int(10) unsigned DEFAULT '0' COMMENT '是否可寻呼',
  `priority` int(10) unsigned DEFAULT '0' COMMENT '终端优先级',
  `mac` varchar(45) NOT NULL DEFAULT '00:00:00:00:00:00' COMMENT '终端的网卡地址',
  `isrecord` int(4) DEFAULT '0' COMMENT '是否录音，编码是有效',
  `instancy` int(4) NOT NULL DEFAULT '0' COMMENT '是否是紧急寻呼备用终端或缺省终端',
  `longitude` double DEFAULT '0',
  `latitude` double DEFAULT '0',
  `isselectcall` int(4) NOT NULL DEFAULT '0',
  `onlinetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '上线时间',
  `serverip` varchar(255) NOT NULL DEFAULT '192.168.1.1',
  `taskid` int(4) NOT NULL DEFAULT '0',
  `skaddress` varchar(255) NOT NULL DEFAULT '',
  `skdevtype` int(4) NOT NULL DEFAULT '0',
  `totalcapacity` bigint(20) NOT NULL DEFAULT '0',
  `resetcapacity` bigint(20) NOT NULL DEFAULT '0',
  `issponsor` int(4) DEFAULT '0' COMMENT '是否支持发言',
   `shortcircuit` int(4) DEFAULT '0',
  `lopencircuit` int(4) DEFAULT '1',
  `ropencircuit` int(4) DEFAULT '1',
  `temperature` float DEFAULT '0',
  `humidity` float DEFAULT '0',
  `upgrade` varchar(255) DEFAULT NULL,
  `progress` int(4) DEFAULT 0,
  `soundsgroupid` int(11) unsigned DEFAULT 0 COMMENT '关联soundgroupinfo表id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `terminal` */


/*Table structure for table `terminalattrbute` */

DROP TABLE IF EXISTS `terminalattrbute`;

CREATE TABLE `terminalattrbute` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `volume` int(11) DEFAULT NULL,
  `bitrate` int(11) DEFAULT NULL,
  `sample` int(11) DEFAULT NULL,
  `channel` int(11) DEFAULT NULL,
  `terminalid` int(10) unsigned NOT NULL,
  `inputformat` int(11) DEFAULT '0',
  `outformat` varchar(10) DEFAULT NULL COMMENT 'int改为varchar',
  `audiocodec` varchar(20) DEFAULT NULL COMMENT 'int改为varchar',
  `audioquality` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `terminalattrbute` */

/*Table structure for table `terminalfolder` */

DROP TABLE IF EXISTS `terminalfolder`;

CREATE TABLE `terminalfolder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parentid` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `terminalid` int(10) NOT NULL,
  `seqnumber` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `terminalfolder` */
/*Table structure for table `terminalfunc` */

DROP TABLE IF EXISTS `terminalfunc`;

CREATE TABLE `terminalfunc` (
  `name` char(64) NOT NULL DEFAULT '',
  `ret` tinyint(1) NOT NULL DEFAULT '0',
  `dl` char(128) NOT NULL DEFAULT '',
  `typeid` enum('function','aggregate') NOT NULL DEFAULT 'function',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `terminalfunc` */

/*Table structure for table `terminalgroup` */

DROP TABLE IF EXISTS `terminalgroup`;

CREATE TABLE `terminalgroup` (
  `groupid` int(11) NOT NULL DEFAULT '0',
  `streamid` int(11) NOT NULL DEFAULT '0',
  `groupname` varchar(255) DEFAULT NULL,
  `info` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `terminalgroup` */

/*Table structure for table `terminalgrouplist` */

DROP TABLE IF EXISTS `terminalgrouplist`;

CREATE TABLE `terminalgrouplist` (
  `id` int(10) unsigned NOT NULL,
  `terminalid` int(10) unsigned NOT NULL,
  `terminalgroupid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `terminalgrouplist` */

/*Table structure for table `terminalkey` */

DROP TABLE IF EXISTS `terminalkey`;

CREATE TABLE `terminalkey` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '',
  `terminalid` varchar(45) NOT NULL DEFAULT '',
  `key` int(11) NOT NULL,
  `sendmodul` int(4) DEFAULT '1' COMMENT '单播还是组播',
  `flag` int(4) DEFAULT 0 COMMENT '0=快捷键，1=急救',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `terminalkey` */

/*Table structure for table `terminalkeymap` */

DROP TABLE IF EXISTS `terminalkeymap`;

CREATE TABLE `terminalkeymap` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `keyid` int(10) unsigned DEFAULT NULL,
  `terminalid` int(10) unsigned NOT NULL,
  `area` varchar(16) DEFAULT '1111111111111111',
  `groupid` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `terminalkeymap` */

DROP TABLE IF EXISTS `terminalkeymaptask`;

CREATE TABLE `terminalkeymaptask` (
  `keyid` int(4) NOT NULL,
  `terminalid` int(4) NOT NULL,
  `taskid` int(4) DEFAULT NULL,
  PRIMARY KEY (`keyid`,`terminalid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `terminalmaked` */

DROP TABLE IF EXISTS `terminalmaked`;

CREATE TABLE `terminalmaked` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `mac` varchar(20) NOT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `terminalmaked` */

/*Table structure for table `terminalofalarmgroup` */

DROP TABLE IF EXISTS `terminalofalarmgroup`;

CREATE TABLE `terminalofalarmgroup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alarmgroupid` varchar(45) NOT NULL DEFAULT '' COMMENT '与alarmarea表中id一致',
  `terminalid` int(10) unsigned NOT NULL COMMENT '被添加到报警分区的终端',
  `groupid` int(10) NOT NULL DEFAULT '0' COMMENT '组号',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `terminalofalarmgroup` */

/*Table structure for table `terminalofararmgroup` */

DROP TABLE IF EXISTS `terminalofararmgroup`;

CREATE TABLE `terminalofararmgroup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alarmgroupid` int(10) unsigned NOT NULL COMMENT '终端所属的报警组ID',
  `terminalid` int(10) unsigned NOT NULL COMMENT '终端id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `terminalofararmgroup` */

/*Table structure for table `terminalofcallgroup` */

DROP TABLE IF EXISTS `terminalofcallgroup`;

CREATE TABLE `terminalofcallgroup` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `selectgroupid` int(11) DEFAULT NULL,
  `terminalid` int(11) DEFAULT NULL,
  `area` varchar(16) DEFAULT '11111111',
  `groupid` int(10) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `terminalofcallgroup` */

/*Table structure for table `terminaloffolder` */

DROP TABLE IF EXISTS `terminaloffolder`;

CREATE TABLE `terminaloffolder` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `terminalid` int(10) DEFAULT NULL,
  `folderid` int(10) DEFAULT NULL,
  `seqnumber` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8;

/*Data for the table `terminaloffolder` */

/*Table structure for table `terminalofgroup` */

DROP TABLE IF EXISTS `terminalofgroup`;

CREATE TABLE `terminalofgroup` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `terminalid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `terminalofgroup` */

/*Table structure for table `terminaloftask` */

DROP TABLE IF EXISTS `terminaloftask`;

CREATE TABLE `terminaloftask` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskid` int(11) NOT NULL COMMENT '与task表taskid一致',
  `terminalid` int(11) NOT NULL COMMENT '与任务相关的terminal表id一致',
  `workstate` varchar(255) DEFAULT NULL COMMENT '工作状态',
  `groupid` int(11) NOT NULL DEFAULT '0',
  `area` varchar(16) NOT NULL DEFAULT '11111111',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `terminaloftask` */

/*Table structure for table `terminaltype` */

DROP TABLE IF EXISTS `terminaltype`;

CREATE TABLE `terminaltype` (
  `id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL COMMENT '终端类型名称',
  `info` varchar(255) DEFAULT '' COMMENT '对终端类型描述',
  `isdecode` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '终端是否可解码1-是 0-否,寻呼',
  `isencode` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '终端是否可编码1-是 0-否，对讲',
  `shortkeycount` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '终端快捷键数目',
  `switchcount` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '终端开关数',
  `isLCD` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '终端是否是LCD1-是 0-否',
  `isWeb` int(4) unsigned NOT NULL DEFAULT '1',
  `isheart` int(4) unsigned NOT NULL DEFAULT '1' COMMENT '终端是否播放1-是 0-否',
  `isspeech` int(4) unsigned NOT NULL DEFAULT '0' COMMENT '终端是否对讲1-是 0-否',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `terminaltype` */

insert  into `terminaltype`(`id`,`name`,`info`,`isdecode`,`isencode`,`shortkeycount`,`switchcount`,`isLCD`,`isWeb`,`isheart`,`isspeech`) values (0, '服务器', '', 0, 1, 4, 1, 0, 1, 0, 0),(1, '网络终端', '', 1, 0, 0, 1, 0, 1, 1, 0),(2, '网络话筒', '', 1, 1, 9, 1, 1, 1, 1, 1),(3, '双向寻呼终端', '', 1, 1, 9, 1, 1, 1, 1, 1),(4, '网络前置', '', 1, 1, 9, 8, 1, 1, 1, 0),(5, '网络功放', '', 1, 1, 9, 8, 1, 1, 1, 0),(6, '电源管理器', '', 0, 0, 0, 10, 0, 0, 1, 0),(7, '报警主机', '', 0, 0, 0, 16, 0, 0, 1, 0),(8, '采样终端', '', 0, 1, 9, 4, 1, 1, 1, 0),(9, '电脑', '', 0, 0, 0, 0, 0, 0, 0, 0),(10, 'MP3', '', 0, 0, 0, 0, 0, 1, 1, 0),(11, '一体化音箱', '', 1, 0, 0, 0, 0, 1, 1, 0),(12, '分控软件', '', 1, 1, 0, 0, 0, 0, 1, 1),(13, '一键寻呼终端', '', 1, 1, 0, 0, 0, 1, 1, 1),(14, '分控前置', '', 1, 1, 9, 8, 1, 1, 1, 1),(15, '背景音乐', '', 1, 1, 0, 0, 1, 1, 1, 0),(16, '实话接口', '', 0, 1, 0, 0, 0, 1, 1, 0),(17, '手机终端', '', 1, 1, 200, 0, 0, 1, 1, 1),(18, '分控工作站', '', 0, 0, 0, 0, 0, 1, 1, 0),(19, '透传终端', '', 1, 1, 0, 0, 0, 1, 1, 0),(20, '网络终端', '', 1, 0, 0, 1, 0, 1, 1, 0),(21, '监控主机', '', 1, 1, 0, 0, 0, 1, 1, 1),(22, 'TTS主机', '', 0, 1, 0, 0, 0, 1, 1, 0),(23, '离线终端', '', 1, 1, 0, 0, 0, 1, 1, 0),(24, '网络音柱/功放', '', 1, 1, 0, 0, 0, 1, 1, 0),(25, '编码器', '', 0, 1, 10, 4, 0, 1, 1, 0),(26, '网络调音台', '', 1, 1, 9, 0, 1, 1, 1, 0),(27, '线阵音柱', '', 1, 0, 0, 0, 0, 1, 1, 0),(28, '寻呼话筒', '', 1, 1, 9, 1, 1, 1, 1, 1),(29, '遥控终端', '', 0, 0, 0, 0, 0, 1, 1, 0),(30, '网络调音台', '', 1, 1, 9, 0, 1, 1, 1, 0),(31, '网络音频采集器', '', 0, 1, 10, 4, 0, 1, 1, 0),(32, 'TTS主机', '', 0, 1, 0, 0, 0, 1, 1, 0),(33, '一键寻呼终端', '', 1, 1, 0, 0, 0, 1, 1, 1),(34, '网络前置', '', 1, 1, 9, 6, 1, 1, 1, 0),(35, '双向寻呼终端', '', 1, 1, 9, 1, 1, 1, 1, 1),(36, '网络分区前置', '', 1, 1, 9, 8, 1, 1, 1, 0),(37, '网络功放', '', 1, 1, 9, 6, 1, 1, 1, 0),(38, '一体化音箱', '', 1, 1, 0, 0, 0, 1, 1, 0),(39, '网络前置', '', 1, 1, 0, 0, 0, 1, 1, 0),(40, '寻呼终端', '', 1, 1, 0, 0, 0, 1, 1, 1),(41, '应急终端', '', 1, 1, 200, 0, 0, 1, 1, 1),(42, 'LED设备', '', 1, 1, 9, 8, 0, 1, 1, 1),(43, '小区广播主机', '', 0, 0, 0, 0, 0, 1, 1, 0),(44, '防爆终端', '', 1, 1, 9, 0, 0, 1, 1, 1),(45, '网络话筒', '', 1, 1, 9, 1, 0, 1, 1, 1),(46, '一键寻呼终端', '', 1, 1, 0, 0, 0, 1, 1, 1),(47, '网络前置', '', 1, 1, 0, 0, 0, 1, 1, 0);

DROP TABLE IF EXISTS `terminaltypekey`;
CREATE TABLE `terminaltypekey` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '终端键名称根据terminaltype表shortkeycount确定',
  `terminaltype` int(10) NOT NULL COMMENT '终端类型与terminaltype表中id确定',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*Table structure for table `ttssentence` */

DROP TABLE IF EXISTS `ttssentence`;

CREATE TABLE `ttssentence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL COMMENT '语句名称',
  `sentenceid` int(11) DEFAULT NULL COMMENT '语句id',
  `type` int(11) DEFAULT '1' COMMENT '0:音乐，1:约定文字，2:输入文字',
  `mediaid` int(11) NOT NULL DEFAULT '0' COMMENT '媒体id',
  `content` varchar(1400) DEFAULT '""' COMMENT '输入文字内容',
  `mediaseq` int(4) DEFAULT NULL COMMENT '媒体播放顺序',
  `speed` int(4) DEFAULT '0' COMMENT '-50,100',
  `volume` int(4) DEFAULT '0' COMMENT '-100,100',
  `male` int(4) DEFAULT NULL,
  `pitch` tinyint(3) DEFAULT 50 COMMENT '语调',
  `rdn` tinyint(3) DEFAULT 0 COMMENT '音频数字发音，\r\n0 数值优先,\r\n1 完全数值,\r\n2 完全字符串，\r\n3 字符串优先，',
  `rcn` tinyint(3) DEFAULT 0 COMMENT '1 的中文发音，\r\n0：表示发音为yao\r\n1：表示发音为yi',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ttssentence` */

/*Table structure for table `ttstaskinfo` */

DROP TABLE IF EXISTS `ttstaskinfo`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `ttstaskinfo` */

/*Table structure for table `ttstext` */

DROP TABLE IF EXISTS `ttstext`;

CREATE TABLE `ttstext` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL COMMENT '约定文字名称',
  `seq` int(4) DEFAULT NULL COMMENT '字段序号',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `ttstext` */

/*Table structure for table `usergroup` */

DROP TABLE IF EXISTS `usergroup`;

CREATE TABLE `usergroup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '' COMMENT '用户组名称',
  `info` varchar(128) DEFAULT NULL COMMENT '用户组描述',
  `welcome` varchar(128) DEFAULT NULL COMMENT '用户组附加信息',
  `taskpriv` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文件广播权限1-有 0-无',
  `terminalpriv` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '终端权限1-有 0-无',
  `mediapriv` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上传权限1-有 0-无',
  `userpriv` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户权限1-有 0-无',
  `serverpriv` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '服务器权限1-有 0-无',
  `folderpriv` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '目录权限1-有 0-无',
  `terminalgrouppriv` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分区权限1-有 0-无',
  `alarmgrouppriv` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '报警权限1-有 0-无',
  `bellpriv` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '方案权限1-有 0-无',
  `admpriv` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '采播权限1-有 0-无',
  `telephonepriv` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '电话采播权限1-有 0-无',
  `powerplay` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '电源权限1-有 0-无',
  `level` int(10) unsigned NOT NULL DEFAULT '3' COMMENT '用户组级别 最大值5',
  `ttspriv` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '文字语音权限1-有 0-无',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `usergroup` */

insert  into `usergroup`(`id`,`name`,`info`,`welcome`,`taskpriv`,`terminalpriv`,`mediapriv`,`userpriv`,`serverpriv`,`folderpriv`,`terminalgrouppriv`,`alarmgrouppriv`,`bellpriv`,`admpriv`,`telephonepriv`,`powerplay`,`level`,`ttspriv`) values (1,'system group','','',1,1,1,1,1,1,1,1,1,1,1,1,10,1);


/*Table structure for table `usersn` */

DROP TABLE IF EXISTS `usersn`;

CREATE TABLE `usersn` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `sn` varchar(32) DEFAULT NULL,
  `userid` int(10) NOT NULL,
  `enable` int(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`,`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `usersn` */

/*Table structure for table `userterminal` */

DROP TABLE IF EXISTS `userterminal`;

CREATE TABLE `userterminal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL COMMENT '记录用户与终端关系',
  `terminalid` int(10) unsigned NOT NULL COMMENT '记录用户与终端关系',
  `groupid` int(4) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `userterminal` */


/*Table structure for table `usertype` */

DROP TABLE IF EXISTS `usertype`;

CREATE TABLE `usertype` (
  `id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `info` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Data for the table `usertype` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

