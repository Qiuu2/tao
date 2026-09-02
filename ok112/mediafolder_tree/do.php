<?php
{
	//===============启用会话
	session_start();
	//===============避免显示乱码
	header('Content-Type:text/html;charset=utf-8');
	//===============避免数据库乱码
	mysql_query("set names utf8");
	//===============验证是否失效
	require_once("verify_user_sessionin_valid.php");
	verifysessionvalid();
	//===============显示多语言
	require_once("language/".$_SESSION['language'].".php");
	//===================================================================添加封装模块
	require_once($_SERVER["DOCUMENT_ROOT"]."/features_wrapper_class.php");
	//===============判断是否登录或退出标志段默认为0
	$log_flag = 0;
	$opt = "";
   	//===============获取处理跳转的值
	switch ($_GET['act'])
	{		
		case "login":
			$log_flag = 1;
			$opt= "用户登录";
			login();
			//insert_log();
			break;
		case "logout":
			$log_flag = 1;
			$opt= "用户退出";
			//insert_log();
			logout();
			break;
		case "sh_msg":
			sh_msg();
			break;
		case "dis_sh":
			dis_sh();
			break;
		case "edit_msg":
			edit_msg();
			break;			
		case "fileadd_msg":
		   $opt= "上传媒体文件";
			fileadd_msg();
			break;		
		case "fileedit_msg":
		  $opt= "修改文件";
			fileedit_msg();
			break;
		case "delfiletask_msg":
		  $opt= "删除媒体文件";
			delfiletask_msg();
			break;
		case "folderdel_msg":
		  $opt= "删除文件夹";
			folderdel_msg();
			break;
		case "taskfolderdel_msg":
		  $opt= "删除任务目录";
			taskfolderdel_msg();
			break;			
		case "folderadd_msg":
		  $opt= "添加文件夹";
			folderadd_msg();
			break;
		case "taskfolderadd_msg":
		  $opt= "添加文件夹";
			taskfolderadd_msg();
			break;	
		case "foldermodify_msg":
		  $opt= "修改文件夹";
			foldermodify_msg();
			break;	
		case "taskfoldermodify_msg":
		  $opt= "修改任务文件夹";
			taskfoldermodify_msg();
			break;		
		case "madlistdel_msg":
		 $opt= "删除媒体文件列表";
			madlistdel_msg();
			break;
		case "filedel_msg":
			$opt= "删除媒体文件";
			filedel_msg();
			break;
		case "terminaladd_msg":
		  $opt= "添加终端";
			terminaladd_msg();
			break;
		case "del_terminal_shotcut":
		  $opt= "删除终端快捷键";
			del_terminal_shotcut();
			break;
		case "terminaledit_msg":
		  $opt= "编辑终端信息";
			terminaledit_msg();
			break;
		case "terminaldel_msg":
		  $opt= "删除终端信息";
			terminaldel_msg();
			break;
		case "terminalStart_msg":
		  $opt= "启用终端";
			terminalStart_msg();
			break;
		case "terminalStop_msg":
		  $opt= "停用终端";
			terminalStop_msg();	
			break;
		case "terminalspeech_msg":
		  $opt= "启用终端对讲";
			terminalspeech_msg();
			break;
		case "set_terminal_record":
		  $opt= "启用终端录音";
			set_terminal_record();			
			break;
		case "set_terminal_stoprecord":
		   $opt= "停止终端录音";
			set_terminal_stoprecord();
			break;
		case "set_terminal_backcall":
		  $opt= "启用终端";
			set_terminal_backcall();
			break;
		case "stop_terminal_backcall":
		  $opt= "用户登录";
			stop_terminal_backcall();
			break;
		case "terminalnospeech_msg":
		  $opt= "停用终端对讲";
			terminalnospeech_msg();
			break;			
		case "removealreaterminal":
		  $opt= "注销终端";
			removealreaterminal();
			break;
		case "useradd_msg":
		  $opt= "添加用户";
			useradd_msg();
			break;
		case "useredit_msg":
		  $opt= "修改用户";
			useredit_msg();
			break;
		case "taskcommandstart_msg":
		  $opt= "执行通用指令";
			taskcommandstart_msg();
			break;
		case "taskcommandstop_msg":
			taskcommandstop_msg();
			break;
		case "userpasswordmodify_msg":
		  $opt= "修改用户密码";
			userpasswordmodify_msg();
			break;
		case "userdel_msg":
		  $opt= "删除用户";
			userdel_msg();
			break;
		case "cancel_user_terminal":
		  $opt= "取消用户终端";
			cancel_user_terminal();
			break;
		case "usergroupdel_msg":
		  $opt= "删除用户组";
			usergroupdel_msg();
			break;
		case "usergroupadd_msg":
		  $opt= "添加用户组";
			usergroupadd_msg();
			break;
		case "usergroupmodify_msg":
		  $opt= "修改用户组";
			usergroupmodify_msg();
			break;
		case "taskadd_msg":
		  $opt= "添加任务";
			taskadd_msg();			
			break;
		case "taskedit_msg":
		  $opt= "修改任务";
			taskedit_msg();
			break;
		case "taskdel_msg":
		  $opt= "删除任务";
			taskdel_msg();
			break;
		case "addplaybelltask_msg":
		  $opt= "添加打铃方案";
			addplaybelltask_msg();
			break;
		case "belltaskaloneoperation":
		  $opt= "添加打铃任务";
			belltaskaloneoperation();
			break;
		case "belltaskalonemodify":
		  $opt= "修改打铃任务";
			belltaskalonemodify();
			break;
		case "modifysystem_msg":
		  $opt= "修改系统信息";
			modifysystem_msg();
			break;
		case "modifybelltask_msg":
		  $opt= "修改打铃方案";
			modifybelltask_msg();
			break;
		case "modifywebradio_msg";
		  $opt= "修改网络电台任务";
			modifywebradio_msg();
			break;
		case "bellstart_msg":
		  $opt= "启用打铃方案";
			bellstart_msg();
			break;
		case "bellstop_msg":
		  $opt= "停用打铃方案";
			bellstop_msg();
			break;
		case "belldel_msg":
		  $opt= "删除打铃方案";
			belldel_msg();
			break;
		case "bellcop_msg";
		  $opt= "复制打铃方案";
		   bellcop_msg();
			 break;	
		case "admtaskstart_msg":
		  $opt= "采播任务执行";
			admtaskstart_msg();
			break;
		case "webradiotaskstart_msg";
		  $opt= "执行网络电台任务";
			webradiotaskstart_msg();
			synctask();
			break;
		case "admtaskstop_msg":
		  $opt= "停止采播任务";
			admtaskstop_msg();
			break;
		case "webradiotaskstop_msg";
		  $opt= "停止网络电台认为";
			webradiotaskstop_msg();
			break;
		case "admtaskdel_msg":
		   $opt= "删除采播任务";
			admtaskdel_msg();
			break;
		case "webradiotaskdel_msg";
		  $opt= "删除网络电台任务";
			webradiotaskdel_msg();
			break;			
		case "admmanagervolumemodify_msg":
		  $opt= "修改采播任务音量";
			admmanagervolumemodify_msg();
			break;
		case "webradiotaskmodify_msg";
		  $opt= "修改完了电台任务";
			webradiotaskmodify_msg();
			break;
		case "teltaskstop_msg":
		  $opt= "停用短话采播任务";
			teltaskstop_msg();
			break;
		case "teltaskstart_msg":
		  $opt= "启用电话采播任务";
			teltaskstart_msg();
			break;
		case "teltaskdel_msg":
		  $opt= "删除电话采播任务";
			teltaskdel_msg();
			break;
		case "terfuncplaystart_msg":
		  $opt= "执行终端功放任务";
			terfuncplaystart_msg();
			break;
		case "terfuncplaystop_msg":
		  $opt= "停止终端功放任务";
			terfuncplaystop_msg();
			break;
		case "terfuncplaydel_msg":
		  $opt= "删除终端功放任务";
			terfuncplaydel_msg();
			break;
		case "taskcommanddel_msg":
		  $opt= "删除通用指令任务";
			taskcommanddel_msg();
			break;
		case "addterminal_msg":
		  $opt= "添加终端";
			addterminal_msg();
			break;
		case "modifyterminalvolume_msg":
		  $opt= "批量修改终端音量";
			modifyterminalvolume_msg();
			break;
		case "addshotcutkey_msg":
		  $opt= "添加中端快捷键";
			addshotcutkey_msg();
			break;
		case "logdel_msg":
		  $opt= "删除日志文件";
			logdel_msg();
			break;
		case "tasklogdel_msg":
		  $opt= "上传执行记录";
			tasklogdel_msg();
			break;
		case "addfileplaytask_msg":
		  $opt= "添加文件广播任务";
			addfileplaytask_msg();		
			break;
		case "addwebradiotask_msg";
		  $opt= "添加网络电台任务";
		    addwebradiotask_msg();
			break;
		case "filetaskstart_msg":
		  $opt= "启用文件广播任务";
			filetaskstart_msg();
			synctask();
			break;
		case "filetaskstop_msg":
		  $opt= "停用文件广播任务";
			filetaskstop_msg();
			break;
		//流处理
			case "cancel_terminal_shotcut":
		    $opt= "取消终端快捷键映射";
			cancel_terminal_shotcut();
			break;
		case "cancel_fire_alarm_mapping_msg":
		  $opt= "取消报警映射";
			cancel_fire_alarm_mapping_msg();
			break;
		case "set_task_mapping_msg":
		 $opt= "添加遥控任务映射";
			set_task_mapping_msg();
			break;
		case "set_task_synch":
		  $opt= "任务同步";
			set_task_synch();
			break;
		case "del_task_mapping_msg":
		  $opt= "取消遥控任务映射";
			del_task_mapping_msg();
			break;
		case "alarmstart_msg":
		  $opt= "启用报警";
			alarmstart_msg();
			break;
		case "setalarmmap_msg":
		  $opt= "添加报警映射";
			setalarmmap_msg();
			break;
		case "addcallzone_msg":
		   $opt= "添加终端寻呼分区";
			addcallzone_msg();
			break;
		case "modifycallzone_msg":
		  $opt= "修改终端寻呼分区";
			modifycallzone_msg();
			break;
		case "delcallzone":
		  $opt= "删除终端寻呼分区";
			delcallzone();		
			break;
		case "areaadd_msg":
		  $opt= "添加报警分区";
			areaadd_msg();
			break;
		case "area_modify_msg":
		  $opt= "修改报警分区";
			area_modify_msg();
			break;
		case "del_alarm_area":
		  $opt= "删除报警分区";
			del_alarm_area();
			break;
		case "streamadd_msg":
		  $opt= "添加终端分区";
			streamadd_msg();
			break;
		case "streambatedit_msg":
		 $opt= "编辑终端分区";
			streambatedit_msg();			 
			break;	
		case "streamdel_msg":
		  $opt= "删除终端分区";
			streamdel_msg();		
			break;
			
		case "streamedit_msg":
		  $opt= "编辑分区4";
			streamedit_msg();
			break;	
		case "streambaddterminal_msg":
			$opt= "添加终端";
			streambaddterminal_msg();
			break;		
		case "medialistadd_msg":
		  $opt= "添加媒体文件";
			medialistadd_msg();		
			break;
		case "commandtask_msg":
		  $opt= "通用指令任务";
			commandtask_msg();		
			break;		
		case serveredit_msg:
		  $opt= "编辑服务器配置信息";
			serveredit_msg();
			break;
		case "reply_msg":
		  $opt= "reply_msg";
			reply_msg();
			break;
		case "del_msg":
			del_msg();
			break;
		case "regist_server":
		  $opt= "服务器注册";
			regist_server();
			break;
		case "start_file_task_msg":
		   $opt= "执行文件广播任务";
			start_file_task_msg();
			break;
		case "stop_file_task_msg":
		  $opt= "停止文件广播任务";
			stop_file_task_msg();
			break;	
		case "pwd":
		  $opt= "修改用户密码";
			pwd();
			break;
		case "restart_server_msg":
		  $opt= "重启服务器";
			restart_server_msg();
			break;
		case "stop_curr_tast_state":
		  $opt= "停用3";
			stop_curr_tast_state();
			break;
		case "start_curr_tast_state":
		  $opt= "停用2";
			start_curr_tast_state();
			break;
		case "emergency_setting":
		  $opt= "停用1";
			emergency_setting();
			break;
		case "emergency_canceling":
		  $opt= "停用4";
			emergency_canceling();
			break;
		case "gettimeip":
		  $opt= "停用5";
			gettimeip();
			break;
		default:
			//添加外部变量
			global $do_php_prompt;			
			echo $do_php_prompt['Illegal_operation'];
	}  
	insert_log($opt);
//if($log_flag == 0)
//{
//	insert_log();	
//}
//if($log_flag == 1)
//{
//	$log_flag == 0;
//};	
	
}


//插入日志
function insert_log($opt)
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;	
  //(isset($_GET['act']))
	//{
	//	$opt= trim($_GET['act']);
	//}
	
	$ip = $_SERVER['REMOTE_ADDR'];
	
	if(!empty($_SESSION['username']))
	{
		$user = $_SESSION['username'];
	}
	else
	{
		$user = "Invalid user";
	}
	
	$time = gmdate("Y-m-d H:i:s",time()+8*3600);
	
	$log_sql = "INSERT INTO audioserver.log (log.user, log.operate, log.ip, log.time)";
	
	$log_sql.= " VALUES ('$user','$opt','$ip','$time') ";
//===========================================================添加锁	
	lock_table("log");
	
	mysql_query($log_sql) or die(mysql_error());
	
	unset($log_sql);
	
	unlock_table();
//===========================================================解除锁
}
//插入留言---未被使用
function gettimeip()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//=======================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	$getip = "";
	if(isset($_POST['getip']))
	{
		$getip = trim($_POST['getip']);
	}
		mysql_query("update serverbaseparam set ntpserver='$getip'");
	
		if(mysql_error())
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "set_server_time.html";
			
			echo "<script>window.location='error.php'</script>";
			//=============================================================================
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./login.php");
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "set_server_time.html";
			
			echo "<script>window.location='success.php'</script>";
			//========================================================================================
			//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./servermanager.php");
		}	

}

function login()
{
	session_start();
	
	require_once("inc/config.php");
	
	require_once("inc/config.inc.php");
	
	require_once("getallsessionid.php");
	
	require_once("verify_user_sessionin_valid.php");
	
	verifysessionvalid();
	
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	//=================================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	if(invalid_regist_service()==0)
	{
		//header("location:regist_server.php");
		echo "<script>window.location.href = 'regist_server.php'</script>";
		
		exit;
	}
	//添加外部变量
	global $do_php_prompt;
	
	$newsessionid = trim(session_id());
	
	$username = "";
	if(isset($_POST['username']))
	{
		$username = trim($_POST['username']);
	}
	
	$userpwd = "";
	if(isset($_POST['userpwd']))
	{
		$userpwd = trim($_POST['userpwd']);
		$userpwd = md5($userpwd);
	}
	
	$checknum = "";
	if(isset($_POST['checknum']))
	{
		$checknum = trim($_POST['checknum']);
	}
	
	if(!empty($username))
	{
		$sql = "SELECT 	* FROM book_admin WHERE book_admin.username = '$username'";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if(mysql_num_rows($result) <= 0)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['User_not_exist']);//提示信息
			
			$_SESSION['url'] = "login.php";
			
			echo "<script>window.location='error.php';</script>";
			
			/*echo "<script>top.location.reload();</script>";*/
			
			//=================================================================
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['User_not_exist'],"./login.php");
			
			exit;
		}
	}	
	if(!empty($username) && !empty($userpwd))
	{
		$sql = "SELECT 	* FROM book_admin WHERE book_admin.userpwd = '$userpwd' AND book_admin.username = '$username'";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if(mysql_num_rows($result) <= 0)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Incorrect_pass_word']);//提示信息
			
			$_SESSION['url'] = "login.php";
			
			echo "<script>window.location='error.php'</script>";
			
			/*echo "<script>top.location.reload();</script>";*/
			//===================================================================
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Incorrect_pass_word'],"./login.php");
			
			exit;
		}
	}	
	if($checknum != $_SESSION['code'])
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Incorrect_verification_code']);//提示信息
		
		$_SESSION['url'] = "login.php";
		
		echo "<script>window.location='error.php'</script>";
		
		/*echo "<script>top.location.reload();</script>";*/
		//=========================================================================
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Incorrect_verification_code'],"./login.php");
		
		exit;
	}

	if(!empty($username) && !empty($userpwd))
	{
		$sql = "SELECT 	* FROM book_admin WHERE book_admin.userpwd = '$userpwd' AND book_admin.username = '$username'";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if(mysql_num_rows($result) == 1)
		{
			//search_sessionid($SESSION_PATH,$username,$newsessionid);
			
			$serverip = mysql_query("SELECT ip FROM serverbaseparam") or die(mysql_error());
			
			if($serverrow = mysql_fetch_array($serverip))
			{
				$_SESSION['serverip'] = $serverrow['ip'];
			}
			@mysql_free_result($serverip);
			
			unset($serverrow);
			
			$row = mysql_fetch_array($result);
			
			if($row['usergroupid'] == 1)
			{
				$_SESSION['admin_id'] = "administrator";
				
				$_SESSION['username'] = $username;
				
				$_SESSION['userid'] = $row['id'];
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Login_successful']);//提示信息
				
				get_user_right($_SESSION['username']);//获取用户权限
				
				$_SESSION['url'] = "servermanager.php";
				
				echo "<script>window.parent.frames['topFrame'].location.reload();</script>";
				
				echo "<script>window.parent.frames['main'].location.href='servermanager.php'</script>";	
				
			}
			else if($row['usergroupid'] != 1)
			{
				$_SESSION['admin_id']="user";
				
				$_SESSION['username'] = $username;
				
				$_SESSION['userid'] = $row['id'];
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Login_successful']);//提示信息
				
				get_user_right($_SESSION['username']);//获取用户权限
				
				$_SESSION['url'] = "servermanager.php";
				
				echo "<script>window.parent.frames['topFrame'].location.reload();</script>";
				
				echo "<script>window.parent.frames['main'].location.href='servermanager.php'</script>";
				
			}
		}
		else if(mysql_num_rows($result) != 1)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Incorrect_user_name_password']);//提示信息
			
			$_SESSION['url'] = "login.php";
			
			echo "<script>window.location='error.php'</script>";
			
			//==================================================================
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Incorrect_user_name_password'],"./login.php");
			
			exit;
		}
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Please_entry_username_pass_word']);//提示信息

		$_SESSION['url'] = "login.php";

		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Please_entry_username_pass_word'],"./login.php");

		exit;
	}
}

function logout()
{
	require_once("getallsessionid.php");
	
	//clearDBsessionid($_SESSION['username']);
	
	//添加外部变量
	global $do_php_prompt;	
	//=================================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	@session_unset();
	
	@session_destroy();
	//===================================================================
	/*echo "<script>alert('".$do_php_prompt['Log_Out']."');</script>";//显示信息*/
	
$forward_ok_error_obj->exit_function($do_php_prompt['Log_Out']);
	

}
//====================================此函数没有被使用
function alarmstart_msg()
{
	require_once("inc/config.inc.php");

	//require_once("inc/socket_conf.php");
		
	//添加外部变量
	global $do_php_prompt;
	
	echo "<script>alert('保留使用');</script>";
	
	exit;
}
//终端分区修改
function area_modify_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}
	$areaname = "";
	if(isset($_POST['areaname']))
	{
		$areaname = trim($_POST['areaname']);
	}
	$info = "";
	if(isset($_POST['info']))
	{
		$info = trim($_POST['info']);
	}
	$alarmterminal = "";
	if(isset($_POST['alarmterminal']))
	{
		$alarmterminal = trim($_POST['alarmterminal']);
		
		$terminal_array = explode(",",$alarmterminal);
	}
	$userid=$_SESSION['userid'];
$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	//是否同名
	$sql_area = "SELECT * FROM alarmarea WHERE alarmarea.id !='$get_id' AND alarmarea.name = '$areaname'";
	
	$result_area = mysql_query($sql_area) or die(mysql_error());

	if(mysql_num_rows($result_area) > 0)
	{
		//=========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//显示信息
		
		echo "<script>window.history.back();</script>";
	
		exit;*/
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysql_free_result($result_area);
	
	unset($sql_area);
	
	mysql_query("LOCK TABLES alarmarea write,terminal write,terminalofalarmgroup write");
	
	//mysql_query("UPDATE terminal SET firealarmgroup = '0' WHERE terminal.firealarmgroup = '$get_id'") or die(mysql_error());
	
	mysql_query("UPDATE alarmarea SET NAME = '$areaname', info = '$info' WHERE alarmarea.id = '$get_id' ") or die(mysql_error());
	
	//先删除
	mysql_query("DELETE FROM terminalofalarmgroup WHERE alarmgroupid = '$get_id'") or die(mysql_error());
	
	for($i=0; $i<count($terminal_array); $i++)
	{   
		if(is_numeric($terminal_array[$i]))
		{
		
		$groupid = (int)$analysis_tree_group_ids[$i];
			//mysql_query("UPDATE terminal SET firealarmgroup = '$get_id' WHERE terminal.id = '$terminal_array[$i]'") or die(mysql_error());
			//插入新数据
			mysql_query("INSERT INTO terminalofalarmgroup (alarmgroupid, terminalid,groupid) VALUES('$get_id','$terminal_array[$i]','$groupid')") or die(mysql_error());
		}
	}
	
	mysql_query("UNLOCK TABLES");
		if(!mysql_error())
	{
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "display_alarm_area.php";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./display_alarm_area.php");
	}
	
}
//删除报警分区
function del_alarm_area()
{
	require_once("inc/config.inc.php");
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}
	//启用事务
	mysql_query("START TRANSACTION");
	
	mysql_query("lock table terminal write,alarmarea write,alarmgroupmap write,terminalofalarmgroup write");
	
	//mysql_query("UPDATE terminal SET firealarmgroup = '0' WHERE	terminal.firealarmgroup IN($get_id)") or die(mysql_error());
	
	mysql_query( "DELETE FROM alarmarea WHERE alarmarea.id IN($get_id)" ) or die(mysql_error());
	
	mysql_query( "DELETE FROM alarmgroupmap WHERE alarmgroupmap.firealarmgroupid  IN($get_id)" ) or die(mysql_error());
	
	mysql_query( "DELETE FROM terminalofalarmgroup WHERE terminalofalarmgroup.alarmgroupid IN($get_id)" ) or dir(mysql_error());
	
	mysql_query( "UNLOCK TABLES" );
	
	if(!mysql_error())
	{
		@mysql_query("COMMIT");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "display_alarm_area.php";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./display_alarm_area.php");
	}
	else
	{
		@mysql_query("ROLLBACK");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "display_alarm_area.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./display_alarm_area.php");
	}
}
//删除报警分区
function removealreaterminal()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$id = "";
	
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
	}
	
	$alarm_id = "";
	
	if(isset($_GET['alarm_id']))
	{
		$alarm_id = trim($_GET['alarm_id']);
	}
	
	mysql_query("LOCK TABLE terminalofalarmgroup WRITE");
	
	$sql = "DELETE FROM terminalofalarmgroup WHERE alarmgroupid = '$alarm_id' AND terminalid = '$id' ";
	
	mysql_query($sql) or die(mysql_error());
	
	unset($sql);
	
	mysql_query("UNLOCK TABLES");
	
	if(!mysql_error())
	{
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "displayareaterminal.php";
	
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./alarmmanagement.php");
	}
}
//设置报警映射
function setalarmmap_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$alarmhost = "";
	
	if(isset($_POST['alarmhost']))
	{
		$alarmhost = trim($_POST['alarmhost']);
	} 
	
	$info = "";
	
	if(isset($_POST['info']))
	{
		$info = trim($_POST['info']);
	}
	
	$channel = "";
	
	if(isset($_POST['channel']))
	{
		$channel = trim($_POST['channel']);
	}
	
	$area = "";
	
	if(isset($_POST['area']))
	{
		$area = trim($_POST['area']);
	}
	
	$media = "";
	
	if(isset($_POST['media']))
	{
		$media = trim($_POST['media']);
	}
	
	mysql_query("LOCK TABLE alarmgroupmap WRITE");
	
	$sql = "SELECT 	* FROM alarmgroupmap WHERE alarmgroupmap.alarmterminalid = '$alarmhost' AND alarmgroupmap.alarmchannel = '$channel' ";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	if(mysql_num_rows($result) > 0)
	{
		$updatesql = "UPDATE alarmgroupmap SET info = '$info', firealarmgroupid = '$area', mediaid = '$media' ";
	
		$updatesql.= "WHERE alarmgroupmap.alarmterminalid = '$alarmhost' AND alarmgroupmap.alarmchannel = '$channel'";
	
		mysql_query($updatesql) or die(mysql_error());
		
		//$updatesqlterminal = "UPDATE terminal SET firealarmgroup = '$area' WHERE terminal.id = '$alarmhost' ";
	
		//mysql_query($updatesqlterminal) or die(mysql_error());
	
		unset($updatesql);
	}
	else if(mysql_num_rows($result) <= 0)
	{
		$insertsql = "INSERT INTO alarmgroupmap (info, alarmterminalid, alarmchannel, firealarmgroupid, mediaid) ";
	
		$insertsql.= "VALUES ('$info', '$alarmhost', '$channel', '$area', '$media')";
	
		mysql_query($insertsql) or die(mysql_error());
		
		//$updatesqlterminal = "UPDATE terminal SET firealarmgroup = '$area' WHERE terminal.id = '$alarmhost' ";
	
		//mysql_query($updatesqlterminal) or die(mysql_error());
	
		unset($insertsql);
	}
	
	@mysql_free_result($result);
	
	unset($sql);
	
	mysql_query("UNLOCK TABLES");
	
	if(!mysql_error())
	{
		//==================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "alarmmanagement.php";
	
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./alarmmanagement.php");
	}
}
//创建报警分区
function areaadd_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$areaname = "";
	
	if(isset($_POST['areaname']))
	{
		$areaname = trim($_POST['areaname']);
	}
	
	$info = "";
	
	if(isset($_POST['info']))
	{
		$info = trim($_POST['info']);
	}
	
	$alarmterminal = "";
	
	if(isset($_POST['alarmterminal']))
	{
		$alarmterminal = trim($_POST['alarmterminal']);
		
		$terminalarray = explode(",",$alarmterminal);
	}
	$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	//启用事务
	//mysql_query("START TRANSACTION");
	//加锁
	mysql_query("LOCK TABLE terminal WRITE,alarmarea WRITE,terminalofararmgroup WRITE,terminalofalarmgroup WRITE");
	
	$sql = "SELECT 	* FROM alarmarea WHERE alarmarea.name = '$areaname'";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	if(mysql_num_rows($result) > 0)
	{
		@mysql_free_result($result);
		
		unset($sql);
		//================================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//显示消息
		
		echo "<script>window.history.back();</script>";
		
		exit;*/
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	@mysql_free_result($result);
	
	unset($sql);
	$userid=$_SESSION['userid'];
	$sql = "INSERT INTO alarmarea (name, info,userid)VALUES('$areaname', '$info','$userid')";
	
	mysql_query($sql) or die(mysql_error());
	
	unset($sql);
	
	$sql = "SELECT 	MAX(id)	FROM alarmarea ";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	if($row = mysql_fetch_array($result))
	{
		$getareaid = trim($row[0]);
	}
	
	@mysql_free_result($result);
	
	unset($sql,$row);
	
	if(!empty($alarmterminal))
	{
		for($i=0; $i<count($terminalarray); $i++)
		{
			if(is_numeric($terminalarray[$i]))
			{
				$num = (int)$terminalarray[$i];
				$groupid = (int)$analysis_tree_group_ids[$i];
				
				$sql = "INSERT INTO terminalofalarmgroup(alarmgroupid, terminalid,groupid) VALUES('$getareaid', '$num','$groupid')";
				
				mysql_query($sql) or die(mysql_error());
			}
		}
	}
	if(!mysql_error())
	{
		//====================================================================
		//mysql_query("COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "display_alarm_area.php";
		
		echo "<script>window.location='success.php'</script>";	
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./createalarmarea.php");	
	}

}
//取消报警映射
function cancel_fire_alarm_mapping_msg()
{
	require_once("inc/config.inc.php");
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}
	//加锁
	mysql_query("LOCK TABLE alarmgroupmap WRITE");
	
	mysql_query("delete from alarmgroupmap where alarmgroupmap.id in ($get_id)");
	
	mysql_query("UNLOCK TABLES");
	
	if(mysql_error())
	{
		//================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./alarmmanagement.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./alarmmanagement.php");
	}
	else
	{
		//=================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./alarmmanagement.php";
		
		echo "<script>window.location='success.php'</script>";	
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./alarmmanagement.php");
	}
}
//审核留言---未被使用
function sh_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();

	//判断是否批量
	if(isset($_GET['id']) and $_GET['id'] !="")
	{
		$del_str = "id=".$_GET['id'];
		$del_num=1;
	}
	else
	{
		$del_id = $_POST['del_id'];
		$del_num=count($del_id);
		$del_str = "id=".$del_id[0];
		for($i=1;$i<$del_num;$i++)
		{ 
			$del_str .=" or id=".$del_id[$i];
		}
	}
	if($del_num != 0)
	{
		require_once("inc/config.inc.php");
		
		mysql_query("UPDATE `".$DB_PREFIX."msg` SET `sh`=1 WHERE $del_str");
		
		if(mysql_error())
		{
			//================================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./";
		
			echo "<script>window.location='error.php'</script>";
			
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./");
		}
		else
		{
			//=================================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./";
		
			echo "<script>window.location='success.php'</script>";	
			
			//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./");
		}
	}
	else
	{
		//=================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['No_choice']);//提示信息
		
		$_SESSION['url'] = "./";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['No_choice'],"./");
	}
}
//取消审核---未被使用
function dis_sh()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	mysql_query("UPDATE `".$DB_PREFIX."msg` SET `sh`=0 WHERE id=$_GET[id]");
	
	if(mysql_error())
	{
		//===============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./");
	}
	else
	{
		//================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./";

		echo "<script>window.location='success.php'</script>";	
	
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./");
	}
}
//编辑留言---未被使用
function edit_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	mysql_query("UPDATE `".$DB_PREFIX."msg` SET `title`='$_POST[title]',`type`='$_POST[type]',`content`='$_POST[description]' WHERE id='$_GET[id]'");	
	
	if(mysql_error())
	{
		//===============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./");
	}
	else
	{
		//===============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./");	
	}
}

//添加文件---未被使用
function fileadd_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
//#if 0
	$FILE_PATH = "/usr/data/";
	$result = mysql_query("SELECT * FROM `media` WHERE name='$_POST[filename]' ");
	if(!$row = mysql_fetch_array($result))
	{    
		if (file_exists($FILE_PATH.$newfile_name)) 
		{
			//===============================================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['The_name_has_been_used']);//提示信息
			
			$_SESSION['url'] = "./filemanager.php";
			
			echo "<script>window.location='error.php'</script>";
			
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['The_name_has_been_used'],"./filemanager.php");
		}	
		else
		{
			copy($newfile, $FILE_PATH.$newfile_name);	
			
			mysql_query("INSERT INTO `media` (`name`,`filename`,`folderid`,`size`) VALUES ('$_POST[filename]','$newfile_name','$_POST[folderid]','$newfile_size')");	        
			
			if(mysql_error())
			{
				//==============================================================
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./filemanager.php";
				
				echo "<script>window.location='error.php'</script>";
				
				//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filemanager.php");
			}
			else
			{
				//===============================================================
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = "./filemanager.php";
				
				echo "<script>window.location='success.php'</script>";
				
				//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filemanager.php");
			}
		}
	}
	else
	{
		//===========================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['The_name_has_been_used']);//提示信息
		
		$_SESSION['url'] = "./filemanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['The_name_has_been_used'],"./filemanager.php");
	}
//#else
	$folderid=$_POST['folderid'];

	//注意这里获取到包含所有文件新旧名称的字符串
	$oldName=$_POST['oldNameArr'];
	
	$newName=$_POST['newNameArr'];

	//把字符串拆成数组
	$oldNameArr=explode(",",$oldName);
	
	$newNameArr=explode(",",$newName);
	
	$len=count($oldNameArr);
	
	$error = 0;

	//根据获取到的数组 循环写入数据
	for($i=0;$i<$len;$i++)
	{
		//循环写入数据库  具体根据自己的需要修改
	
		//为了方便测试  我直接以追加的方式写到记事本
		$str=$oldNameArr[$i]."|".$newNameArr[$i]."|".$haha."|".$hehe."\n";
		
		mysql_query("INSERT INTO `media` (`name`,`filename`,`folderid`,`size`) VALUES ('$oldNameArr[$i]','$newNameArr[$i]','$folderid',0)");	        
		if(mysql_error())
		{
			$error=1;
			
			break;
		}
	}
	
	if($error ==1)
	{
		$error=1;
		//============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./filemanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filemanager.php");
	}
	else
	{
		//==============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./filemanager.php";
		
		echo "<script>window.location='success.php'</script>";	
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filemanager.php");
	}
//#endif
}
//编辑文件---未被使用
function fileedit_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	mysql_query("UPDATE `media` SET `name`='$_POST[name]',`filename`='$_POST[filename]',`typeid`='$_POST[type]',`content`='$_POST[description]' WHERE id='$_GET[id]'");	
	if(mysql_error())
	{
		//===========================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./fileedit.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./fileedit.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./filemanager.php";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filemanager.php");	
	}
}
//删除媒体---未被使用
function filedel_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	mysql_query("DELETE FROM `media` WHERE id='$_GET[id]'") or die("Execute error".mysql_error());
	
	mysql_query("DELETE FROM `medialist` WHERE mediaid='$_GET[id]'");
	
	if(mysql_error())
	{
		//============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./filemanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filemanager.php");	
	}
	else
	{
		//==============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./filemanager.php";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filemanager.php");	
	}
}
//删除媒体文件---先判断是哪个文件夹
function delfiletask_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	//==================================================创建套接字类
	$create_socket_obj = new create_socket_class();
	$getmediaid=$_GET['id'];
	//加锁
	mysql_query("LOCK TABLE media WRITE,filefolder WRITE,mediaoftask WRITE,task WRITE");
	


	//判断是否有权限删除
	$sql = "SELECT filefolder.id, filefolder.name FROM filefolder WHERE filefolder.id IN ";
	
	$sql.= "(SELECT media.folderid FROM media WHERE media.id IN ($_GET[id])) ";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	while($row=mysql_fetch_array($result))
	{
		if( ($row['id']==5 || $row['id'] == 1 || $row['id'] == 2 || $row['id'] == 3 || $row['id'] == 4) && ($_SESSION['admin_id']!="administrator") )
		{
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Authority_not_enough']);
		}
	}
	@mysql_free_result($result);
	
	unset($sql,$row);
	
		//读取媒体任务
	$sql = "SELECT media.name FROM media WHERE id IN(SELECT mediaoftask.mediaid FROM mediaoftask,task WHERE mediaoftask.taskid=task.taskid AND mediaoftask.mediaid in('$getmediaid'))";
	
	$result = mysql_query($sql);
	
	if(mysql_num_rows($result) > 0)
	{
		$forward_ok_error_obj->exit_function($do_php_prompt['using_not_deleted']);		
	}
	else
	{
	
	
	}
	
	@mysql_free_result($result);
	
	unset($sql,$row);
	
	
	//保留可删除的媒体ID并删除响应的文件
	$delete_media_id = "";
	
	$sqls ="SELECT media.id,media.filename,media.name FROM media WHERE id IN(".$_GET['id'].") AND id NOT IN (SELECT mediaid FROM mediaoftask WHERE mediaid IN(".$_GET['id']."))";
	$results = mysql_query($sqls) or die(mysql_error());
	{
		if(mysql_num_rows($results) > 0)
		{
			while ($rows = mysql_fetch_array($results))
			{
				if($delete_media_id == "")
				{
					$delete_media_id = $rows['id'];
				}
				else
				{
					$delete_media_id.= ", ".$rows['id'];
				}
				if(file_exists($rows['filename']))
				{
					unlink($rows["filename"]);
				}
				else
				{
					continue;
				}
			}
		}
	else
		{
				$sql = "SELECT media.id,media.filename FROM media WHERE id IN(".$_GET['id'].") AND id IN(SELECT mediaid FROM mediaoftask WHERE mediaid IN(".$_GET['id'].") ";
			
			$sql.= "AND mediaoftask.taskid NOT IN(SELECT taskid FROM task))";
		
			$result = mysql_query($sql) or die(mysql_error());
			
			if(mysql_num_rows($result) > 0)
			{
				while ($row = mysql_fetch_array($result))
				{
					if($delete_media_id == "")
					{
						$delete_media_id = $row['id'];
					}
					else
					{
						$delete_media_id.= ", ".$row['id'];
					}
					if(file_exists($row['filename']))
					{
						unlink($row["filename"]);
					}
					else
					{
						continue;
					}
				}
			}
		}

	}

	
	@mysql_free_result($result);
	
	unset($sql,$row);
	//判断有没有能够删除的文件

	if($delete_media_id !="")
	{
	
		$sqlfolder = "SELECT folderid FROM media WHERE media.id IN (".$delete_media_id.") GROUP BY folderid ";
		
		$resultfolder = mysql_query($sqlfolder) or die(mysql_error());
		
		while($rowfolder = mysql_fetch_array($resultfolder))
		{
			$strfolder = $rowfolder['folderid'];
			//===============================================================================
			$create_socket_obj->send_socket_media_file("file",0,$strfolder);
		}
		
		mysql_query("DELETE FROM  media WHERE media.id in ($delete_media_id)") or die(mysql_error());
		mysql_query("DELETE FROM  mediaoftask WHERE mediaid in ($delete_media_id)") or die(mysql_error());

		if(mysql_error())
		{
		
			//===========================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./media_file.php";
			
			echo "<script>window.location='error.php'</script>";
			
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./media_file.php");
		}
		else if(!mysql_error())
		{
			//=============================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./media_file.php";
			
			echo "<script>window.location='success.php'</script>";	
			
			//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./media_file.php");
		}
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
		$_SESSION['url'] = "./media_file.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./media_file.php");
	}
	//释放表
	mysql_query("UNLOCK TABLES");
}
//添加文件夹
function folderadd_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$folderName ="";
	
	if(isset($_POST['folderName']))
	{
		$folderName = trim($_POST['folderName']);
	}
	
	if(isset($_GET['folder_id']))
	{
		if(!empty($_GET['folder_id']))//0 '' false null array() array(array())
		{
			$folder_id = trim($_GET['folder_id']);	
		}
	}

	$isOrNoShare =0;
	
	if($_POST[isOrNoShare] != "")
	{
		$isOrNoShare =1;
	}
	//获取用户id
	$sql_user = "SELECT id FROM book_admin WHERE book_admin.username = '".$_SESSION['username']."'";
	
	$result_user = mysql_query($sql_user) or die(mysql_error());
	
	$row_user = mysql_fetch_array($result_user);
	
	$userid = trim($row_user['id']);
	
	@mysql_free_result($result_user);
	
	unset($row_user,$sql_user);
	//是否有同名文件夹
	$folder_sql="SELECT * FROM filefolder WHERE filefolder.name='$folderName' AND filefolder.userid='$userid' AND parentid ='$folder_id'";
	
	$folder_result = mysql_query($folder_sql) or die(mysql_error());
	
	if(mysql_num_rows($folder_result) > 0)
	{
		//=====================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."')</script>";//提示信息
		
		echo "<script>history.back();</script>";
	
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysql_free_result($folder_result);
	
	unset($folder_sql);
	
	mysql_query(" LOCK TABLE filefolder WRITE");
	
	mysql_query("INSERT INTO filefolder (name,userid,priority,parentid) VALUES ('$_POST[folderName]','$userid','$isOrNoShare','$folder_id ')");
	
	mysql_query("UNLOCK TABLES");
	
	if(mysql_error())
	{
		//============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./filefoldermanager.php";
	
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./filefoldermanager.php";
	
		echo "<script>top.location.reload();</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}	
}


function taskfolderadd_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$folderName ="";
	
	if(isset($_POST['folderName']))
	{
		$folderName = trim($_POST['folderName']);
	}
	
	if(isset($_GET['folder_id']))
	{
		if(!empty($_GET['folder_id']))//0 '' false null array() array(array())
		{
			$folder_id = trim($_GET['folder_id']);	
		}
	}
	mysql_query(" LOCK TABLE filetaskfree WRITE");
	//是否有同名文件夹
	$folder_sql="SELECT * FROM filetaskfree WHERE filetaskfree.name='$folderName' AND parentid ='$folder_id'";
	
	$folder_result = mysql_query($folder_sql) or die(mysql_error());
	
	if(mysql_num_rows($folder_result) > 0)
	{
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysql_free_result($folder_result);
	
	unset($folder_sql);
	

	mysql_query("INSERT INTO filetaskfree(name,parentid) VALUES ('$folderName','$folder_id ')");
	
	mysql_query("UNLOCK TABLES");
	
	if(mysql_error())
	{
		//============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./displayfilemanager.php";
	
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./displayfilemanager.php";
	
		echo "<script>top.location.reload();</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}	
}
//========================删除文件夹、判断是否有已被用的媒体======================
function folderdel_msg()
{
	//=====保留系统预留文件夹=====//
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	$get_folder_id = "";
	//=====================创建对象=====================
	$database_operate_obj = new database_operate_class();
	//=====================创建跳转对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//取文件夹
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
		
		$get_folder_id_array = explode(",",$get_folder_id);
	}
	
	foreach($get_folder_id_array as $value)
	{
		if($value == 5 || $value == 1 || $value == 2 || $value == 3 || $value == 4)
		{
			$forward_ok_error_obj->exit_back_function($do_php_prompt['not_delete_system_files']);
		}
	}
	//=================================================不能删除已被使用的媒体、并重新赋值===================================
	$get_folder_id = $database_operate_obj->whether_have_exit($get_folder_id_array,$do_php_prompt['contains_use_folder_failed'],$do_php_prompt['failed_all_selected_folder']);
	
	//=====删除文件夹先删除文件=====//
	@mysql_query("LOCK TABLE media WRITE,filefolder WRITE");
	
	@mysql_query("START TRANSACTION");
	
	$sql_folder = "SELECT filename FROM media WHERE media.folderid IN ($get_folder_id)";
	
	$result_folder = mysql_query($sql_folder) or die(mysql_error());
	
	while($row_folder = mysql_fetch_array($result_folder))
	{
		if(is_file($row_folder['filename']))
		{
			unlink($row_folder['filename']);
		}
	}
	
	@mysql_free_result($result_folder);
	
	unset($row_folder,$sql_folder);
	
	$sql_media = "DELETE FROM media WHERE media.folderid IN ($get_folder_id)";
	
	$del_media = mysql_query($sql_media) or die(mysql_error());
	
	unset($sql_media);
	
	$sql_folder = "DELETE FROM filefolder WHERE filefolder.id IN ($get_folder_id)";
	
	$del_folder = mysql_query($sql_folder) or die(mysql_error());
	
	unset($sql_folder);
	
	if($del_folder && $del_media)
	{
		@mysql_query("COMMIT");
	}
	else
	{
		@mysql_query("ROLLBACK");
	}
	mysql_query("UNLOCK TABLES");
	
	if(mysql_error())
	{
		//===========================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./filefoldermanager.php";
	
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./filemanager.php";
	
		echo "<script>top.location.reload();</script>";	
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}
}

function taskfolderdel_msg()
{
	//=====保留系统预留文件夹=====//
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	$get_folder_id = "";
	//=====================创建对象=====================
	$database_operate_obj = new database_operate_class();
	//=====================创建跳转对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//取文件夹
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
		
	
	}
		if($get_folder_id == 1)
		{
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['not_delete_system_files']);
		}

	//=====删除文件夹先删除文件=====//
	@mysql_query("LOCK TABLE filetaskfree WRITE,mediaoftask WRITE,terminaloftask WRITE,task WRITE");
	
	@mysql_query("START TRANSACTION");
	
	$sql_folder = "SELECT taskid FROM task WHERE task.parentid ='$get_folder_id'";
	
	$result_folder = mysql_query($sql_folder) or die(mysql_error());
	
	while($row_folder = mysql_fetch_array($result_folder))
	{
		$get_id=$row_folder['taskid'];
		$sql_media = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$get_id'";
	
		$del_media = mysql_query($sql_media) or die(mysql_error());
	
		unset($sql_media);
	
		$sql_folder = "DELETE FROM mediaoftask WHERE mediaoftask.taskid ='$get_id'";
	
		$del_folder = mysql_query($sql_folder) or die(mysql_error());
	
		unset($sql_folder);
		$sql_task = "DELETE FROM task WHERE task.taskid ='$get_id'";
	
		$del_task = mysql_query($sql_task) or die(mysql_error());
	
		unset($sql_task);
			
	}
	
	@mysql_free_result($result_folder);
	
	unset($row_folder,$sql_folder);
	$folder = "DELETE FROM filetaskfree WHERE filetaskfree.id ='$get_folder_id'";
	
		$foldertask = mysql_query($folder) or die(mysql_error());
	
		unset($folder);
	mysql_query("UNLOCK TABLES");
	
	if(mysql_error())
	{
		//===========================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./displayfilemanager.php";
	
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./displayfilemanager.php";
	
		echo "<script>top.location.reload();</script>";	
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}
}
//修改目录
function foldermodify_msg()
{
	require_once("inc/config.inc.php");
	//=====================添加外部变量=================
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_folder_id = "";
	
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
	}
	$folderName = "";
	
	if(isset($_POST['folderName']))
	{
		$folderName = trim($_POST['folderName']);
	}
	$isOrNoShare = 1;
	
	if(isset($_POST['isOrNoShare']))
	{
		$isOrNoShare = trim($_POST['isOrNoShare']);
	}
	
	$sql_user = "SELECT id FROM book_admin WHERE book_admin.username = '".trim($_SESSION['username'])."'";

	$result_user = mysql_query($sql_user) or die(mysql_error());

	$row_user = mysql_fetch_array($result_user);

	$user_id = $row_user['id'];
	
	@mysql_free_result($result_user);

	unset($row_user,$sql_user);
	
	//=====检测是否相同=====//

	$sql_folder = "SELECT * FROM filefolder WHERE filefolder.id != '$get_folder_id' AND filefolder.name = '$folderName' AND filefolder.userid = '$user_id' AND parentid IN (SELECT parentid FROM filefolder WHERE filefolder.id='$get_folder_id')";
	
	$result_folder = mysql_query($sql_folder) or die(mysql_error());

	if(mysql_num_rows($result_folder) > 0)
	{
		//===========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";

		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysql_free_result($result_folder);

	unset($sql_folder);

	//=====更新文件夹=====//
	mysql_query("LOCK TABLE filefolder WRITE");

	$sql_folder = "UPDATE filefolder SET NAME = '$folderName' , priority = '$isOrNoShare' WHERE filefolder.id = '$get_folder_id'";

	mysql_query($sql_folder) or die(mysql_error());

	unset($sql_folder);

	mysql_query("UNLOCK TABLES");

	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./filefoldermanager.php";

		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./filefoldermanager.php";

		echo "<script>top.location.reload();</script>";
	}
}
function taskfoldermodify_msg()
{
	require_once("inc/config.inc.php");
	//=====================添加外部变量=================
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_folder_id = "";
	
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
	}
	$folderName = "";
	
	if(isset($_POST['folderName']))
	{
		$folderName = trim($_POST['folderName']);
	}

	
	//=====检测是否相同=====//
	$sql_folder = "SELECT * FROM filetaskfree WHERE filetaskfree.id != '$get_folder_id' AND filetaskfree.name = '$folderName'";
	
	$result_folder = mysql_query($sql_folder) or die(mysql_error());

	if(mysql_num_rows($result_folder) > 0)
	{
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysql_free_result($result_folder);

	unset($sql_folder);

	//=====更新文件夹=====//
	mysql_query("LOCK TABLE filetaskfree WRITE");

	$sql_folder = "UPDATE filetaskfree SET NAME = '$folderName' WHERE filetaskfree.id = '$get_folder_id'";

	mysql_query($sql_folder) or die(mysql_error());

	unset($sql_folder);

	mysql_query("UNLOCK TABLES");

	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./displayfilemanager.php";

		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./displayfilemanager.php";

		echo "<script>top.location.reload();</script>";
	}
}
//取消用户终端
function cancel_user_terminal()
{
	require_once("inc/config.inc.php");	
	
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$userid = "";
	
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	$terminalid = "";
	
	if(isset($_GET['terminalid']))
	{
		$terminalid = trim($_GET['terminalid']);
	}
	
	//判断用户是否为超级用户
	$group_result = mysql_query("SELECT usergroupid FROM book_admin WHERE book_admin.id='$userid'") or die(mysql_error());
	
	if($group_row = mysql_fetch_array($group_result))
	{
		$group_id = $group_row['usergroupid'];
	}
	
	@mysql_free_result($group_result);
	
	unset($group_row);
	
	if($group_id == 1)
	{
		//=============================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['Super_user_not_modified'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
		
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['Super_user_not_modified']);
	}
	else
	{
		$user_terminal_sql = "DELETE FROM userterminal WHERE userterminal.userid = '$userid' ";
		
		$user_terminal_sql.= "AND userterminal.terminalid IN($terminalid)";
		
		mysql_query($user_terminal_sql);
		
		if(!mysql_error())
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "view_user_terminal.php?id=$userid";
			
			echo "<script>window.location='success.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "view_user_terminal.php?id=$userid";
			
			echo "<script>window.location='error.php'</script>";
		}
	}
}
//用户添加
function useradd_msg()
{
	require_once("inc/config.inc.php");	
	
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$username = "";
	if(isset($_POST['username']))
	{
		$username = trim($_POST['username']);
	}
	$info = "";
	if(isset($_POST['info']))
	{
		$info = trim($_POST['info']);
	}

	$usergroup = "";
	if(isset($_POST['usergroup']))
	{
		$usergroup = trim($_POST['usergroup']);
	}
	$newpwd = "";
	if(isset($_POST['newpwd']))
	{
		$newpwd = trim($_POST['newpwd']);
	}
	$ctrlterminalcount = "";
	if(isset($_POST['ctrlterminalcount']))
	{
		$ctrlterminalcount = trim($_POST['ctrlterminalcount']);
	}
	
	
	$confirmpwd = "";
	if(isset($_POST['confirmpwd']))
	{
		$confirmpwd = trim($_POST['confirmpwd']);	
	}
	if($newpwd == $confirmpwd)
	{
		$newpwd = md5($newpwd);
	}
	else
	{
		//========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['Passwords_not_same'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
		
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['Passwords_not_same']);
	}
	
	$terminal_id = "";
		
	if(isset($_POST['terminal_id']))
	{
		$terminal_id = trim($_POST['terminal_id']);
		
		$terminal_array = explode(",",$terminal_id);
	}
	
	mysql_query("LOCK TABLE book_admin WRITE,book_admin READ");
	
	//用户名是否相同
	$sql_username = "select * from book_admin where book_admin.username = '$username'";
	
	$result_username = mysql_query($sql_username) or die(mysql_error());
	
	if(mysql_num_rows($result_username) > 0)
	{
		//============================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
	
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	@mysql_free_result($result_username);
	
	unset($sql_username);
	//判断分控ID是否相同
	if($ctrlterminalcount!=0)
	{
	$sql_username = "select * from book_admin where book_admin.ctrlwind = '$ctrlterminalcount'";
	
	$result_username = mysql_query($sql_username) or die(mysql_error());
	
	if(mysql_num_rows($result_username) > 0)
	{
		//============================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
	
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_ID_has_been_used']);
	}
	@mysql_free_result($result_username);
	
	unset($sql_username);
	}
	//插入用户
	$sql_username = "INSERT INTO book_admin (username, userpwd, usergroupid, info,ctrlwind) VALUES('$username', '$newpwd', '$usergroup', '$info','$ctrlterminalcount')";
	
	mysql_query($sql_username) or die(mysql_error());
	
	unset($sql_username);

	//是否选择终端
	if(!empty($terminal_id))
	{
		$result_max = mysql_query("SELECT MAX(id) FROM book_admin") or die(mysql_error);

		$row_max = mysql_fetch_array($result_max);
	
		for($i=0; $i<count($terminal_array); $i++)
		{
			if(is_numeric($terminal_array[$i]))
			{
		
				mysql_query("insert into userterminal (userid, terminalid) values('$row_max[0]', '$terminal_array[$i]')") or die(mysql_error());
			}
		}
		@mysql_free_result($result_max);
	
		unset($row_max);
	}
	mysql_query("UNLOCK TABLES");
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./usermanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./usermanager.php";
	
		echo "<script>window.location='success.php'</script>";	
	}		
}
//用户修改
function useredit_msg()
{
	require_once("inc/config.inc.php");	
	
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_userid = "";
	if(isset($_GET['id']))
	{
		$get_userid = trim($_GET['id']);
	}
	$username = "";
	if(isset($_POST['username']))
	{
		$username = trim($_POST['username']);
	}
	$info = "";
	if(isset($_POST['info']))
	{
		$info = trim($_POST['info']);
	}
	$usergroup = "";
	if(isset($_POST['usergroup']))
	{
		$usergroup = trim($_POST['usergroup']);
	}
	$ctrlterminalcount = "";
	if(isset($_POST['ctrlterminalcount']))
	{
		$ctrlterminalcount = trim($_POST['ctrlterminalcount']);
	}
	
	$newpwd = "";
	if(isset($_POST['newpwd']))
	{
		$newpwd = trim($_POST['newpwd']);
	}	
	$confirmpwd = "";
	if(isset($_POST['confirmpwd']))
	{
		$confirmpwd = trim($_POST['confirmpwd']);
	}
	if( ($newpwd == $confirmpwd) && (strlen($newpwd)<=16) && (strlen($confirmpwd)<=16) )
	{
		$newpwd = md5($newpwd);
	}
	else if(($newpwd == $confirmpwd) && (strlen($newpwd) > 16) && (strlen($confirmpwd) > 16))
	{
		//什么也不做
	}
	else if($newpwd != $confirmpwd)
	{
		//========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['Passwords_not_same'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
		
		exit;*/
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['Passwords_not_same']);
	}
	//保留预留超级用户
	if($get_userid == 1 && $usergroup != 1)
	{
		//======================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['Illegal_operation'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
		
		exit;*/
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['Illegal_operation']);
	}

	$terminal_id = "";
	
	if(isset($_POST['terminal_id']))
	{
		$terminal_id = trim($_POST['terminal_id']);
	
		$terminal_array = explode(",",$terminal_id);
	}

	mysql_query("LOCK TABLES book_admin READ,book_admin WRITE,userterminal READ,userterminal WRITE,usergroup READ,usergroup WRITE");
	
	mysql_query("START TRANSACTION");
	
	//判断重名
	$sql_user = "SELECT * FROM book_admin WHERE book_admin.id != '$get_userid' AND book_admin.username = '$username'";
	
	$result_user = mysql_query($sql_user) or die(mysql_error());
	
	if(mysql_num_rows($result_user) > 0)
	{
		//============================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
	
		exit;*/
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	@mysql_free_result($result_user);
	
	unset($sql_user);
		//判断分控ID是否相同
	if($ctrlterminalcount!=0)
	{
	$sql_username = "select * from book_admin where book_admin.id != '$get_userid' AND book_admin.ctrlwind = '$ctrlterminalcount'";
	
	$result_username = mysql_query($sql_username) or die(mysql_error());
	
	if(mysql_num_rows($result_username) > 0)
	{
		//============================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
	
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_ID_has_been_used']);
	}
	@mysql_free_result($result_username);
	
	unset($sql_username);
	}
	
	//获取更改后终端权限
	$del_oldterminal = true;
	
	$sql_newright = "select terminalpriv from usergroup where usergroup.id = '$usergroup'";
	
	$result_newright = mysql_query($sql_newright) or die(mysql_error());
	
	if($row_newright = mysql_fetch_array($result_newright))
	{
		$newright = $row_newright['terminalpriv'];
	}
	
	@mysql_free_result($result_newright);
	
	unset($row_newright,$sql_newright);
	//获取用户原有终端权限
	$sql_oldright = "SELECT terminalpriv FROM usergroup WHERE usergroup.id = (SELECT usergroupid FROM book_admin WHERE book_admin.id = '$get_userid')";
	
	$result_oldright = mysql_query($sql_oldright) ;
	
	if($row_oldright = mysql_fetch_array($result_oldright))
	{
		if($row_oldright['terminalpriv'] == 1)
		{
			if($newright == 1)
			{
				//先删后添
				$del_oldterminal = mysql_query("delete from userterminal where	userterminal.userid = '$get_userid'") ;
	
				for($i=0; $i<count($terminal_array); $i++)
				{
					if(is_numeric($terminal_array[$i]))
					{
						$terminal_array[$i] = (int)$terminal_array[$i];
	
						mysql_query("INSERT INTO userterminal (userid, terminalid) VALUES('$get_userid','$terminal_array[$i]')") ;
					}
				}	
			}
			else if($newright == 0)
			{
				//只删
				$del_oldterminal = mysql_query("delete from userterminal where	userterminal.userid = '$get_userid'") ;
			}
		}
		if($row_oldright['terminalpriv'] == 0)
		{
			if($newright == 1)
			{
				//只添
				for($i=0; $i<count($terminal_array); $i++)
				{
					if(is_numeric($terminal_array[$i]))
					{
						$terminal_array[$i] = (int)$terminal_array[$i];
	
						mysql_query("INSERT INTO userterminal (userid, terminalid) VALUES('$get_userid','$terminal_array[$i]')") ;
					}
				}
			}
			else if($newright == 0)
			{
				//什么也不做
			}
		}
	}
	@mysql_free_result($result_oldright);
	
	unset($row_oldright,$sql_oldright);
	
	//更新（对系统预留id为1不能删除且固定在超级用户组）且修改成功后清空sessionid
	if($get_userid == 1)
	{
		$sql_user = "UPDATE book_admin SET username = '$username', userpwd = '$newpwd', usergroupid = '1',";
	
		$sql_user.= "info = '$info',usersessionid = '',ctrlwind='$ctrlterminalcount' WHERE book_admin.id = '$get_userid'";  
	}
	else
	{
		$sql_user = "UPDATE book_admin SET username = '$username', userpwd = '$newpwd', usergroupid = '$usergroup',";
	
		$sql_user.= "info = '$info',usersessionid = '',ctrlwind='$ctrlterminalcount' WHERE book_admin.id = '$get_userid'";
	}
	$modify_user = mysql_query($sql_user) ;
	
	unset($sql_user);
	
	if($modify_user && $del_oldterminal)
	{
		mysql_query("COMMIT");
		//修改成功后是否强制登陆用户退出重新登陆呢
	}
	else
	{
		mysql_query("ROLLBACK");
	}
	mysql_query("UNLOCK TABLES");
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./usermanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./usermanager.php";
	
		echo "<script>window.location='success.php'</script>";	
	}
}

//删除用户
function userdel_msg()
{
	require_once("inc/config.inc.php");

	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();

	//判断用户是否有权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	
	if(is_admin($_SESSION['username']) || have_rights("userpriv"))
	{
		//什么都不做
	}
	else
	{
		quit_out(strtoupper($do_php_prompt['permission_denied']));//提示信息
	}
	
	//系统ID为1用户不能删除---即保留超级管理员
	$get_userid = "";
	
	if(isset($_GET['id']))
	{
		$get_userid = trim($_GET['id']);
	
		$get_userarray = explode(",",$get_userid);
	}
	foreach($get_userarray as $value)
	{
		if($value == 1)
		{
			//===============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['Systems_User_not_deleted'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
	
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Systems_User_not_deleted']);
		}
	}

	//直接删除用户的终端
	mysql_query("START TRANSACTION ");
	
	$del_terminal = mysql_query("DELETE FROM userterminal WHERE userterminal.userid IN ($get_userid)") ;
	
	$del_user = mysql_query("delete from book_admin where book_admin.id in($get_userid)") ;
	
	if($del_terminal && $del_user)
	{
		mysql_query("COMMIT");
	}
	else
	{
		mysql_query("ROLLBACK");
	}
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./usermanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./usermanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//修改用户密码
function userpasswordmodify_msg()
{
	require_once("inc/config.inc.php");

	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$oldpwd = md5(trim($_POST['oldpwd']));
	
	$newpwd = md5(trim($_POST['newpwd']));
	
	$confirmpwd = md5(trim($_POST['confirmpwd']));
	
	$username = trim(urldecode($_GET['username']));
	
	$sql = "SELECT book_admin.userpwd FROM book_admin WHERE book_admin.username='$username'";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	if($row = mysql_fetch_array($result))
	{
		if($row['userpwd'] != $oldpwd)
		{
			//============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['Old_password_incorrect'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Old_password_incorrect']);
		}
		
		if( $newpwd != $confirmpwd)
		{
			//========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['Passwords_not_same'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Passwords_not_same']);
		}
		else
		{
			$sql="UPDATE book_admin SET book_admin.userpwd = '$newpwd' WHERE book_admin.username='$username'";
			
			mysql_query($sql) or die(mysql_error());
			
			if(mysql_error())
			{
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./modifypassword.php";
				
				echo "<script>window.location='error.php'</script>";
			}
			else
			{
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = "./servermanager.php";
				
				echo "<script>window.location='success.php'</script>";	
			}
		}
	}
}
//用户组删除 组与用户是一对多关系
function usergroupdel_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	//判断是否是超级管理员
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	
	if(!is_admin($_SESSION['username']))
	{
		//========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['permission_denied'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
	
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['permission_denied']);
	}
	//验证是否删除系统预留的组
	
	$get_groupid = "";
	
	if(isset($_GET['id']))
	{
		$get_groupid = trim($_GET['id']);
	
		$get_more_groupid = explode(",",$get_groupid); 
	}
	
	foreach($get_more_groupid as $group_id)
	{
		if($group_id == 1)
		{
			//==============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['System_group_not_deleted'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['System_group_not_deleted']);
		}
	}
	unset($group_id);
	//上锁
	mysql_query("LOCK TABLES book_admin READ,usergroup READ,book_admin WRITE,usergroup WRITE,userterminal WRITE,userterminal READ");
	
	//开启事务
	mysql_query("START TRANSACTION");
	
	//删除组时 同时删除用户 并删除该用户的终端
	foreach($get_more_groupid as $group_id)
	{
		//删除用户终端
		mysql_query("DELETE FROM userterminal WHERE userterminal.userid IN 

(SELECT DISTINCT book_admin.id FROM book_admin,usergroup WHERE usergroup.id = '".$group_id."' 

AND book_admin.usergroupid = usergroup.id) ") or die(mysql_error());
		//删除用户
		mysql_query("DELETE FROM book_admin WHERE book_admin.usergroupid = '$group_id'") or die(mysql_error());
	}
	//删除组
	mysql_query("DELETE FROM usergroup WHERE usergroup.id IN ($get_groupid)") or die(mysql_error());
	
	//解锁
	mysql_query("UNLOCK TABLES");
	
	if(mysql_error())
	{
		mysql_query("ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./usergroupmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		mysql_query("COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./usergroupmanager.php";
	
		echo "<script>window.location='success.php'</script>";	
	}
}
//更新用户组
function usergroupmodify_msg()
{
	require_once("inc/config.inc.php");	
	
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$group_id = "";
	if(isset($_GET['id']))
	{
		$group_id = trim($_GET['id']);
	}
	$usergroupname = "";
	if(isset($_POST['usergroupname']))
	{
		$usergroupname = trim($_POST['usergroupname']);
	}
	$UserDes = "";
	if(isset($_POST['UserDes']))
	{
		$UserDes = trim($_POST['UserDes']);
	}
	$level = "";
	if(isset($_POST['level']))
	{
		$level = trim($_POST['level']);
	}
	$taskpriv = 0; 
	if(isset($_POST['taskpriv']))
	{  
		$taskpriv = trim($_POST['taskpriv']);
	}
	$terminalpriv = 0; 
	if(isset($_POST['terminalpriv']))
	{  
		$terminalpriv = trim($_POST['terminalpriv']);
	}
	$mediapriv = 0; 
	if(isset($_POST['mediapriv']))
	{  
		$mediapriv = trim($_POST['mediapriv']);
	}
	$userpriv = 0; 
	if(isset($_POST['userpriv']))
	{  
		$userpriv = trim($_POST['userpriv']);
	}
	$serverpriv = 0; 
	if(isset($_POST['serverpriv']))
	{  
		$serverpriv = trim($_POST['serverpriv']);
	}
	$folderpriv = 0; 
	if(isset($_POST['folderpriv']))
	{  
		$folderpriv = trim($_POST['folderpriv']);
	}
	$terminalgrouppriv = 0; 
	if(isset($_POST['terminalgrouppriv']))
	{  
		$terminalgrouppriv = trim($_POST['terminalgrouppriv']);
	}
	$alarmgrouppriv = 0; 
	if(isset($_POST['alarmgrouppriv']))
	{  
		$alarmgrouppriv = trim($_POST['alarmgrouppriv']);
	}
	$bellpriv = 0; 
	if(isset($_POST['bellpriv']))
	{  
		$bellpriv = trim($_POST['bellpriv']);
	}
	$admpriv = 0; 
	if(isset($_POST['admpriv']))
	{  
		$admpriv = trim($_POST['admpriv']);
	}
	$telephonepriv = 0; 
	if(isset($_POST['telephonepriv']))
	{  
		$telephonepriv = trim($_POST['telephonepriv']);
	}
	$powerplay = 0; 
	if(isset($_POST['powerplay']))
	{  
		$powerplay = trim($_POST['powerplay']);
	}
	if( ($group_id == 1) )
	{
		if( (($taskpriv) == 1) && (($terminalpriv) == 1) && (($mediapriv) == 1) && (($userpriv) == 1) && (($serverpriv) == 1) && (($folderpriv) == 1) && (($terminalgrouppriv) == 1) && (($alarmgrouppriv) == 1) && (($bellpriv) == 1) && (($admpriv) == 1) && (($telephonepriv) == 1) && (($powerplay) == 1) )
		{
			//什么也不做
		}
		else
		{
			//================================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['System_group_not_modified'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['System_group_not_modified']);
		}
	}
	
	//判别是否同名
	$sql_group = "SELECT * FROM usergroup WHERE usergroup.id != '$group_id' AND usergroup.name = '$usergroupname'";
	
	$result_group = mysql_query($sql_group) or die(mysql_error());
	
	if(mysql_num_rows($result_group) > 0)
	{
		//===========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
		
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysql_free_result($result_group);
	
	unset($sql_group);
	//更新
	$sql_group = "UPDATE usergroup SET NAME = '$usergroupname' , info = '$UserDes' , taskpriv = '$taskpriv' , terminalpriv = '$terminalpriv' ,";
	$sql_group.= "mediapriv = '$mediapriv' , userpriv = '$userpriv' , serverpriv = '$serverpriv' , folderpriv = '$folderpriv' , ";
	$sql_group.= "terminalgrouppriv = '$terminalgrouppriv' , alarmgrouppriv = '$alarmgrouppriv' , bellpriv = '$bellpriv' , ";
	$sql_group.= "admpriv = '$admpriv' , telephonepriv = '$telephonepriv' , powerplay = '$powerplay' , level = '$level' WHERE usergroup.id = '$group_id'";
	
	mysql_query($sql_group) or die(mysql_error());

	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./usergroupmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./usergroupmanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}	
}
//添加用户组
function usergroupadd_msg()
{
	require_once("inc/config.inc.php");	
	
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$usergroupname = "";
	if(isset($_POST['usergroupname']))
	{
		$usergroupname = trim($_POST['usergroupname']);
	}
	$taskpriv = 0;
	if(isset($_POST['taskpriv']))
	{  
		$taskpriv =trim($_POST['taskpriv']);
	}
	$terminalpriv = 0;
	if(isset($_POST['terminalpriv']))
	{  
		$terminalpriv = trim($_POST['terminalpriv']); 
	}
	$mediapriv = 0;
	if(isset($_POST['mediapriv']))
	{  
		$mediapriv = trim($_POST['mediapriv']); 
	}
	$userpriv = 0;
	if(isset($_POST['userpriv']))
	{  
		$userpriv = trim($_POST['userpriv']); 
	}
	$serverpriv = 0;
	if(isset($_POST['serverpriv']))
	{
		$serverpriv = trim($_POST['serverpriv']);
	}
	$folderpriv = 0;
	if(isset($_POST['folderpriv']))
	{
		$folderpriv = trim($_POST['folderpriv']);
	}
	$terminalgrouppriv = 0;
	if(isset($_POST['terminalgrouppriv']))
	{
		$terminalgrouppriv = trim($_POST['terminalgrouppriv']);
	}
	$alarmgrouppriv = 0;
	if(isset($_POST['alarmgrouppriv']))
	{
		$alarmgrouppriv = trim($_POST['alarmgrouppriv']);
	}
	$bellpriv = 0;
	if(isset($_POST['bellpriv']))
	{
		$bellpriv = trim($_POST['bellpriv']);
	}
	$admpriv = 0;
	if(isset($_POST['admpriv']))
	{
		$admpriv = trim($_POST['admpriv']);
	}
	$telephonepriv = 0;
	if(isset($_POST['telephonepriv']))
	{
		$telephonepriv = trim($_POST['telephonepriv']);
	}
	$powerplay = 0;
	if(isset($_POST['powerplay']))
	{
		$powerplay = trim($_POST['powerplay']);
	}
	
	$level = 3;
	
	if(isset($_POST['level']))
	{
		$level = trim($_POST['level']);
	}
	
	$UserDes = "NO Description";
	
	if(isset($_POST['UserDes']))
	{
		$UserDes = trim($_POST['UserDes']);
	}
	
	mysql_query("LOCK TABLE usergroup WRITE");
	//不能同名组
	$result_group = mysql_query("SELECT * FROM usergroup WHERE usergroup.name = '$usergroupname'") or die(mysql_error('dddddddddd'));
	if(mysql_num_rows($result_group) > 0)
	{
		//============================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>history.back();</script>";
		
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysql_free_result($result_group);
	//插入数据
	$sql_group = "INSERT INTO audioserver.usergroup ";
	$sql_group.= "(NAME, info, taskpriv, terminalpriv, mediapriv, userpriv, serverpriv, folderpriv, ";
	$sql_group.= "terminalgrouppriv, alarmgrouppriv, bellpriv, admpriv, telephonepriv, powerplay, LEVEL) ";
	$sql_group.= "VALUES ('$usergroupname', '$UserDes', '$taskpriv', '$terminalpriv', '$mediapriv', '$userpriv', '$serverpriv', '$folderpriv', ";
	$sql_group.= "'$terminalgrouppriv', '$alarmgrouppriv', '$bellpriv', '$admpriv', '$telephonepriv', '$powerplay', '$level') ";
	
	mysql_query($sql_group) or die(mysql_error());

	mysql_query("UNLOCK TABLES");
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./usergroupmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./usergroupmanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}		
}
//添加终端---没有被使用（采用自动注册写入到数据库）
function terminaladd_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$terminalsql = "SELECT terminal.terminalname FROM terminal WHERE terminal.terminalname='$_POST[terminalname]' ";
	$terminalsql.= "AND terminal.groupid = '$_POST[streamid]' ";
	
	$terminalresult = mysql_query($terminalsql) or die(mysql_error());
	if(mysql_fetch_array($terminalresult))
	{
		//============================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>history.back();</script>";
		
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	mysql_query("INSERT INTO `terminal` (`groupid`,`terminalname`,`typeid`,`ip`,`postion`,`volume`) VALUES ('$_POST[streamid]','$_POST[terminalname]','$_POST[typeid]','$_POST[ip]','$_POST[postion]','$_POST[volume]')");
		if(mysql_error())
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./terminalmanager.php";
			
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./terminalmanager.php";
			//inputterminaltofile();//更新终端文件信息
			echo "<script>window.location='success.php'</script>";	
		}
}
//启用终端---没什么用的功能（很少用到）
function terminalStart_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象========================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalidarray = explode(",",$getid);
	
	//判读用户与终端及终端状态
	require_once("User_Rights_Manage/user_opr_terminal_right.php");
	
	for($i=0; $i<count($getterminalidarray); $i++)
	{
		$sql = "SELECT 	netstate FROM terminal WHERE terminal.id = '$getterminalidarray[$i]'";
	
		$result = mysql_query($sql) or die(mysql_error());
	
		if($row = mysql_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				//===============================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Disconnect'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
	
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
		}
		control_user_terminal_opr($getterminalidarray[$i]);
	}
	
	mysql_query("UPDATE terminal SET devicestate = '1' WHERE terminal.id IN ($getid)");
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//================================================
			/*$socket	= new send_message_to_server($port_conf);	
	
			$msg = "terminal?state=1&id=".$getid."";						
	
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("terminal",1,$getid);
		}
		echo "<script>window.location='success.php'</script>";	
	}	
}
//停止终端---只是更新数据库状态
function terminalStop_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//====================创建套字节=====================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	
	$getterminalidarray = explode(",",$getid);
	
	//判读用户与终端及终端状态
	require_once("User_Rights_Manage/user_opr_terminal_right.php");
	
	for($i=0; $i<count($getterminalidarray); $i++)
	{
		$sql = "SELECT netstate FROM terminal WHERE terminal.id = '$getterminalidarray[$i]'";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				//================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Disconnect'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
		
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
		}
		control_user_terminal_opr($getterminalidarray[$i]);
	}
	mysql_query("UPDATE terminal SET devicestate = '0', taskstate = '0' WHERE terminal.id IN ($getid)");
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url']="./terminalmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//=================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "terminal?state=0&id=".$getid;		
				
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("terminal",0,$getid);
		}
		echo "<script>window.location='success.php'</script>";	
	}	
}
//更新终端---没有被使用到---实际也改不了
function terminaledit_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	$terminal_sql = "UPDATE `terminal` SET `groupid`='$_POST[streamid]',`terminalname`='$_POST[terminalname]', ";
	
	$terminal_sql.= "`typeid`='$_POST[typeid]',`ip`='$_POST[ip]' ,`postion`='$_POST[postion]',`volume`='$_POST[volume]' WHERE id='$_GET[id]' ";
	
	mysql_query($terminal_sql);
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		//inputterminaltofile();//用来对终端数据重新输入
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//只是删除数据库中的记录---而没有考虑到终端是否连接
function terminaldel_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	//判读用户与终端及终端状态
	require_once("User_Rights_Manage/user_opr_terminal_right.php");
	
	$terminal_id = "";
	
	if(isset($_GET['id']))
	{
		$terminal_id = trim($_GET['id']);
		
		$terminal_array = explode(",",$terminal_id);
		
		foreach($terminal_array as $id)
		{
			control_user_terminal_opr($id);
		}
	}
	
	mysql_query("DELETE FROM terminal WHERE terminal.id IN ($_GET[id])");
	mysql_query("DELETE FROM terminalkey WHERE terminalkey.terminalid IN ($_GET[id])");

	$sqls="SELECT groupid,terminalid FROM terminalofgroup WHERE terminalid IN($_GET[id])";
	$results = mysql_query($sqls) or die(mysql_error());
	while($rows = mysql_fetch_array($results))
		{
			$getgroupid=$rows['groupid'];
			$getterminal_id=$rows['terminalid'];
			mysql_query("DELETE FROM terminalofgroup WHERE terminalofgroup.terminalid = '$getterminal_id'");
		
			$getsqls="SELECT terminalid FROM terminalofgroup WHERE groupid ='$getgroupid'";
			$key_result = mysql_query($getsqls) or die(mysql_error());
			
			if( mysql_num_rows($key_result) <=0 )
			{
			
				mysql_query("DELETE FROM serverplaystream WHERE streamid ='$getgroupid'");
			}
			
		}
	
	
	
	
	
	$sql="SELECT taskid,terminalid FROM terminaloftask WHERE terminalid IN($_GET[id])";
	$result = mysql_query($sql) or die(mysql_error());
	while($row = mysql_fetch_array($result))
		{
			$gettaskid=$row['taskid'];
			$getterminalid=$row['terminalid'];
			mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.terminalid = '$getterminalid'");
			/*
			$sqls="SELECT terminalid FROM terminaloftask WHERE taskid ='$gettaskid'";
			$key_result = mysql_query($sqls) or die(mysql_error());
			if( mysql_num_rows($key_result) <=0 )
			{
				mysql_query("DELETE FROM mediaoftask WHERE taskid ='$gettaskid'");
				mysql_query("DELETE FROM task WHERE taskid ='$gettaskid'");
			}
			*/
		}
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		//inputterminaltofile();//用来对终端数据重新输入
		
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
		
			$create_socket_obj->send_socket_generate_general("terminal",2,$getid);
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
//启用终端对讲---没有使用到---只是更改数据库状态
function terminalspeech_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminaltype.isdecode,terminaltype.isencode FROM terminal,terminaltype ";
		
		$sql.= "WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isdecode'] == 0 && $row['isencode'] == 0)
			{
				//=========================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Terminal_not_support'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
		}
	}
	$sql="UPDATE terminal SET isspeech = '1' WHERE	terminal.id IN ($getid)";
	
	mysql_query($sql) or die(mysql_error());
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "terminal?state=3&id=".$getid."&speech=true";	
					
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_speech("terminal",3,$getid,"true");
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
//停止对讲---没有被使用到---只是更改数据库状态
function terminalnospeech_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);
	
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminaltype.isdecode,terminaltype.isencode FROM terminal,terminaltype ";
		
		$sql.= "WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				//================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Disconnect'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isdecode'] == 0 && $row['isencode'] == 0)
			{
				//=======================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Terminal_not_support'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
		}
	}
	$sql="UPDATE terminal SET isspeech = '0' WHERE	terminal.id IN ($getid) ";
	
	mysql_query($sql) or die(mysql_error());
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "terminal?state=4&id=".$getid."&speech=false";			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_speech("terminal",4,$getid,"false");
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
//启用录音
function set_terminal_record()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminal.typeid,terminaltype.isdecode,terminaltype.isencode FROM ";
		
		$sql.= "terminal,terminaltype WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				//================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Disconnect'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isdecode'] == 0 && $row['isencode'] == 0)
			{
				//=========================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Terminal_not_support'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
			else if($row['typeid'] != 2 && $row['typeid'] != 3)
			{
				//=========================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Terminal_not_support'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
		}
	}
	$sql="UPDATE terminal SET isrecord = '1' WHERE	terminal.id IN ($getid)";
	
	mysql_query($sql) or die(mysql_error());
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "terminal?state=3&id=".$getid."&speech=true";	
					
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_speech("terminal",14,$getid,"true");
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
//停止录音
function set_terminal_stoprecord()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);
	
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminal.typeid,terminaltype.isdecode,terminaltype.isencode FROM ";
		
		$sql.= "terminal,terminaltype WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				//================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Disconnect'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isdecode'] == 0 && $row['isencode'] == 0)
			{

				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
			else if($row['typeid'] != 2 && $row['typeid'] != 3)
			{

				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
		}
	}
	$sql="UPDATE terminal SET isrecord = '0' WHERE	terminal.id IN ($getid) ";
	
	mysql_query($sql) or die(mysql_error());
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "terminal?state=4&id=".$getid."&speech=false";			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_speech("terminal",15,$getid,"false");
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
function set_terminal_backcall()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);
	
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminal.typeid,terminal.isspeech,terminaltype.isdecode,terminaltype.isencode FROM ";
		
		$sql.= "terminal,terminaltype WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
		
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isdecode'] == 0 && $row['isencode'] == 0)
			{

				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
			//else if($row['isspeech'] == 0)
			//{
				//$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			//}
		}
	}
	$sql="UPDATE terminal SET isselectcall = '1' WHERE	terminal.id IN ($getid) ";
	
	mysql_query($sql) or die(mysql_error());
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
	//	$getidlist=explode(",",$_REQUEST['id']);
	/*
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "terminal?state=4&id=".$getid."&speech=false";			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_speech("terminal",15,$getid,"false");
		//}
		echo "<script>window.location='success.php'</script>";	
	}
}
function stop_terminal_backcall()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);
	
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminal.typeid,terminal.isspeech,terminaltype.isdecode,terminaltype.isencode FROM ";
		
		$sql.= "terminal,terminaltype WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
		
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isdecode'] == 0 && $row['isencode'] == 0)
			{

				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
			//else if($row['isspeech'] == 0)
			//{
				//$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			//}
		}
	}
	$sql="UPDATE terminal SET isselectcall = '0' WHERE	terminal.id IN ($getid) ";
	
	mysql_query($sql) or die(mysql_error());
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
	//	$getidlist=explode(",",$_REQUEST['id']);
	/*
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "terminal?state=4&id=".$getid."&speech=false";			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_speech("terminal",15,$getid,"false");
		//}
		echo "<script>window.location='success.php'</script>";	
	}
}
//删除终端快捷映射---仅清除数据库的记录
function del_terminal_shotcut()
{
	require_once("inc/config.inc.php");
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	//$forward_ok_error_obj = new forward_ok_error_class();
	
	$id = "";
	
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
	}
	
	$terminal_id = "";
	
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
	}
	
	mysql_query("START TRANSACTION");
	
	mysql_query("delete from terminalkeymap where terminalkeymap.keyid = '$id'");
	
	mysql_query("DELETE FROM terminalkey WHERE terminalkey.id = '$id'");
	
	if(mysql_error())
	{
		mysql_query("ROLLBACK");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "view_terminal_shotcut_mapping.php?terminal_id=".$terminal_id."";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		mysql_query("COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "view_terminal_shotcut_mapping.php?terminal_id=".$terminal_id."";
	
		echo "<script>window.location='success.php'</script>";	
	}	
}
//删除终端快捷映射---仅仅删除数据库记录
function cancel_terminal_shotcut()
{
	require_once("inc/config.inc.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$terminal_id = "";
	
	mysql_query("LOCK TABLE t WRITE");
	
	mysql_query("START TRANSACTION");
	
	$terminal_id = "";
	
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
	}
	
	$key_sql = "SELECT id FROM terminalkey WHERE terminalkey.terminalid = '$terminal_id'";
	
	$key_result = mysql_query($key_sql) or die(mysql_error());
	
	if( mysql_num_rows($key_result) <=0 )
	{
		//=======================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['Not_setup_support'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
	
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['Not_setup_support']);
	}
	else
	{
		while($key_row = mysql_fetch_array($key_result))
		{
			mysql_query("DELETE FROM terminalkeymap WHERE terminalkeymap.keyid = '".$key_row['id']."'");
	
			if(mysql_error())
			{
				mysql_query('ROLLBACK ');
	
				mysql_query('UNLOCK TABLES');
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "terminalmanager.php";
	
				echo "<script>window.location='error.php'</script>";
			}
		}
		mysql_query("DELETE FROM terminalkey WHERE terminalkey.terminalid = '$terminal_id'");
	
		if(mysql_error())
		{
			mysql_query('ROLLBACK ');
	
			mysql_query('UNLOCK TABLES');
			
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "terminalmanager.php";
	
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			mysql_query('COMMIT');
	
			mysql_query('UNLOCK TABLES');
			
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "terminalmanager.php";
	
			echo "<script>window.location='success.php'</script>";
		}
	}
}
//添加任务时时添加任务与媒体对应记录---没有被使用
function medialistadd_msg()
{
	  require_once("inc/config.inc.php");
	  
	  //require_once("inc/socket_conf.php");
	  
	  //添加外部变量
	  global $do_php_prompt;
	  		
	  mysql_query("INSERT INTO `medialist` (`mediaid`,`taskid`) VALUES ('$_POST[mediaid]', '$_GET[id]')");
	  
	  if(mysql_error())
	  {
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./taskmanager.php";
		
			echo "<script>window.location='error.php'</script>";
	  }
	  else
	  {
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./taskmanager.php";
		
			echo "<script>window.location='success.php'</script>";	
	  }
		
}
//删除任务与媒体对应记录---没有被使用
function madlistdel_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	
	 //添加外部变量
	 global $do_php_prompt;
	
	mysql_query("DELETE FROM `medialist` WHERE id='$_GET[id]'");
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//设置终端同步任务
function set_task_synch()
{
	require_once("inc/config.inc.php"); 
	
	//添加外部变量
	global $do_php_prompt;
	
	$terminalid = "";
	
	if(isset($_GET['terminalid']))
	{
		$terminalid = trim($_GET['terminalid']);
		$terminalid = explode(",",$terminalid);
		
	}
	
	$keyvalue = "";
	
	$task_map_id = "";
	{
		$task_map_id = trim($_POST['task_map_id']);
		$task_map_id = explode(",",$task_map_id);
	}
		mysql_query("LOCK TABLE task WRITE,terminaloftask WRITE");
	for($i=0;$i<count($task_map_id);$i++)
	{
		for($j=0;$j<count($terminalid);$j++)
		{
			$sqlgroup=mysql_query("select groupid from terminalofgroup where terminalid ='$terminalid[$j]'");
			if(mysql_num_rows($sqlgroup)>0)
			{
				while($row_usetask = mysql_fetch_array($sqlgroup))
				{
					$getgroupid=$row_usetask['groupid'];
				}
			}
			else
			{
				$getgroupid=0;
			}
			$sql=mysql_query("select terminalid,taskid from terminaloftask where taskid ='$task_map_id[$i]' AND terminalid ='$terminalid[$j]'");
					if(mysql_num_rows($sql)<=0)
					{
						mysql_query("INSERT INTO terminaloftask (taskid,terminalid,groupid,area) VALUES('$task_map_id[$i]','$terminalid[$j]','$getgroupid','1111111111111111')") or die(mysql_error());
					}
		}
	}
	mysql_query("UNLOCK TABLES");
	if(!mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		echo "<script>window.location='success.php'</script>";	
	}	
}
//添加遥控任务---仅对文件广播、采播管理
function set_task_mapping_msg()
{
	require_once("inc/config.inc.php"); 
	
	//添加外部变量
	global $do_php_prompt;
	
	$map_name = "";
	
	if(isset($_POST['map_name']))
	{
		$map_name = trim($_POST['map_name']);
	}
	
	$keyvalue = "";
	
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}
	
	$task_map_id = "";
	{
		$task_map_id = trim($_POST['task_map_id']);
	}
	
	mysql_query("LOCK TABLE terminalkey WRITE,terminalkey READ,terminalkeymap WRITE,terminalkeymap READ");
	//验证遥控键是否设置
	$sql_taskmap = "SELECT 	* FROM terminalkey WHERE terminalkey.terminalid = '0' AND terminalkey.key = '$keyvalue'";
	
	$result_taskmap = mysql_query($sql_taskmap) or die(mysql_error());
	
	if(mysql_num_rows($result_taskmap) > 0)
	{
		//读取id
		$row_taskmap = mysql_fetch_array($result_taskmap);
		
		$get_map_id = $row_taskmap['id'];
		
		//判断任务是否已分配
		$sql_usedtask = "SELECT id FROM terminalkey WHERE terminalkey.id IN ";
		
		$sql_usedtask.= "(SELECT keyid FROM terminalkeymap WHERE terminalkeymap.terminalid = '$task_map_id') AND terminalkey.terminalid = '0'";
		
		$result_usetask = mysql_query($sql_usedtask) or die(mysql_error());
		
		if($row_usetask = mysql_fetch_array($result_usetask))
		{
			if($row_usetask['id'] == $get_map_id)
			{
				//是自己本身、什么也不做
			}
			else if($row_usetask['id'] != $get_map_id)
			{
				mysql_query("DELETE FROM terminalkeymap WHERE terminalkeymap.keyid = '$row_usetask[id]'") or die(mysql_error());
				
				mysql_query("DELETE FROM terminalkey WHERE terminalkey.id = '$row_usetask[id]'") or die(mysql_error());
			}
		}
		@mysql_free_result($result_usetask);
		
		unset($sql_usedtask,$row_usetask);
		//更新
		mysql_query("UPDATE terminalkey SET terminalkey.name = '$map_name',terminalid = '0',terminalkey.key = '$keyvalue' WHERE id = '$get_map_id' ") or die(mysql_error());
		
		mysql_query("UPDATE terminalkeymap SET  terminalid = '$task_map_id' WHERE terminalkeymap.keyid = '$get_map_id'") or die(mysql_error());
		
		unset($row_taskmap);
	}
	else
	{
		//判断任务是否已分配
		$sql_usedtask = "SELECT id FROM terminalkey WHERE terminalkey.id IN ";
		
		$sql_usedtask.= "(SELECT keyid FROM terminalkeymap WHERE terminalkeymap.terminalid = '$task_map_id') AND terminalkey.terminalid = '0'";
		
		$result_usetask = mysql_query($sql_usedtask) or die(mysql_error());
		
		if($row_usetask = mysql_fetch_array($result_usetask))
		{
			mysql_query("DELETE FROM terminalkeymap WHERE terminalkeymap.keyid = '$row_usetask[id]'") or die(mysql_error());
			
			mysql_query("DELETE FROM terminalkey WHERE terminalkey.id = '$row_usetask[id]'") or die(mysql_error());
		}
		
		@mysql_free_result($result_usetask);
		
		unset($sql_usedtask,$row_usetask);
		//直接插入
		mysql_query("INSERT INTO terminalkey (terminalkey.name,terminalid,terminalkey.key) VALUES('$map_name','0','$keyvalue')") or die(mysql_error());
		//取id号
		$result_max = mysql_query("SELECT MAX(id) FROM terminalkey") or die(mysql_error());
		
		$row_max = mysql_fetch_array($result_max);
		
		mysql_query("INSERT INTO terminalkeymap (keyid,terminalid) VALUES('$row_max[0]','$task_map_id')");
		
		@mysql_free_result($result_max);
		
		unset($row_max);
	}
	@mysql_free_result($result_taskmap);
	
	unset($sql_taskmap);
	
	if(!mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./task_mapping.php";
	
		echo "<script>window.location='success.php'</script>";	
	}	
}
//删除遥控任务映射
function del_task_mapping_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	$id = "";
	
	if(isset($_GET['id']))
	{
		$get_map_id = trim($_GET['id']);
	
		$map_array = explode(",",$get_map_id);
	}
	mysql_query("LOCK TABLE terminalkeymap WRITE, terminalkey WRITE");
	
	mysql_query("START TRANSACTION");
	
	$del_map = mysql_query("DELETE FROM terminalkeymap WHERE terminalkeymap.keyid IN ($get_map_id)") or die(mysql_error());
	
	$del_key = mysql_query("DELETE FROM terminalkey WHERE terminalkey.id IN ($get_map_id)") or die(mysql_error());
	
	if($del_map && $del_key)
	{
		mysql_query("COMMIT");
	
		mysql_query("UNLOCK TABLES");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./task_mapping.php";
	
		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysql_query("ROLLBACK");
	
		mysql_query("UNLOCK TABLES");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./task_mapping.php";
	
		echo "<script>window.location='error.php'</script>";
	}
}
//对作息任务单独处理---添加作息方案（支持批处理添加）
function belltaskaloneoperation()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量==================
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$scheme = "";
	if(isset($_POST['taskname']))
	{
		$scheme = trim($_POST['taskname']);
	}
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = trim($_POST['prepower']);
	}
	//添加声音
	$task_default_volume = 50;
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	 $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	 
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	
	$startdate = "00:00:00";
	if(isset($_POST['startdate']))
	{
		$startdate = trim($_POST['startdate']);
	}
	$enddate = "00:00:00";
	if(isset($_POST['enddate']))
	{
		$enddate = trim($_POST['enddate']);
	}
	$exemodel = 1;
	if(isset($_POST['exemodel']))
	  {
	  	$exemodel = trim($_POST['exemodel']);
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = trim($_POST['hiddenweek']);
			$repl = array(',' => '');
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
		}
	  }
	$hiddencoursename = "";
	if(isset($_POST['hiddencoursename']))
	{	
		$hiddencoursename = trim($_POST['hiddencoursename']);
		$coursenamearray = explode(",",$hiddencoursename);
	}
	$hiddenbelltime = "";
	if(isset($_POST['hiddenbelltime']))
	{	
		$hiddenbelltime = trim($_POST['hiddenbelltime']);
		$belltimearray = explode(",",$hiddenbelltime);
	}
	$hiddenbellname = "";
	if(isset($_POST['hiddenbellname']))
	{	
		$hiddenbellname = trim($_POST['hiddenbellname']);
		$bellnamearray = explode(",",$hiddenbellname);
	}
	$hiddenbelltimelength = "";
	$selectnum = "";
	if(isset($_POST['hiddenbelltimelength']))
	{	
		$hiddenbelltimelength = trim($_POST['hiddenbelltimelength']);
		
		$belltimelengtharray = explode(",",$hiddenbelltimelength);
		
		for($i=0;$i<count($belltimelengtharray);$i++)
		{
			if(strstr($belltimelengtharray[$i],":")!=false)
			{
				$selectnum[$i]=1;
			  $gettimehour=substr($belltimelengtharray[$i],0,2);
			  $gettimeminute=substr($belltimelengtharray[$i],3,2);
			  $gettimesecond=substr($belltimelengtharray[$i],6,2);
			  $belltimelengtharray[$i]=$gettimehour*3600+$gettimeminute*60+$gettimesecond;
			
			}
			else
			{
			$selectnum[$i]=2;
			}
		}
	}
	
	
	$terminallistvalue = "";
	if(isset($_POST['terminallistvalue']))
	{	
		$terminallistvalue = trim($_POST['terminallistvalue']);
		$terminallistarray= explode(",",$terminallistvalue);
	}
	
	$analysis_tree_group_string = "";
	
	if(isset($_POST['analysis_tree_group_string']))
	{
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	}
	
	//方案名称不能同名
	$plan_samename_result = mysql_query("SELECT info FROM task WHERE task.info='$scheme' and task.tasktype='1' and channel=0") or die(mysql_error());
	
	if(mysql_num_rows($plan_samename_result) > 0)
	{
		//===========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
	
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}

	//判断方案中用重名
	for($i=0; $i<count($coursenamearray);$i++)
	{
		$sql = "SELECT * FROM task WHERE task.info='$scheme' AND task.taskname='$coursenamearray[$i]' AND task.tasktype = '1' ";

		$result = mysql_query($sql) or die(mysql_error());
	
		if(mysql_num_rows($result)>0)
		{
			@mysql_free_result($result);
			
			unset($sql);
			//============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}

	@mysql_free_result($result);
	
	unset($sql);
	
	//针对批量----查找方案中是否同名
/*	for($i=0; $i<count($coursenamearray)-1;$i++)
	{
		for($j=$i+1; $j<count($coursenamearray); $j++)
		{
			if(strcmp($coursenamearray[$i],$coursenamearray[$j]) == 0)
			{ */
				/*
				echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*//*
				$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
			}
		}
	}	*/
	
	//取用户优先级
	$sql = "SELECT book_admin.id,usergroup.level FROM book_admin,usergroup WHERE ";

	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	$row = mysql_fetch_array($result);
	
	//获取任务优先级
	$priority = 3;
	
	if(isset($_GET['task_priority_text']))
	{
		$priority = trim($_GET['task_priority_text']);
	}
	
	$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	
	@mysql_free_result($result);

	for($i=0;$i<count($coursenamearray);$i++)
	{

		mysql_query("LOCK TABLES task WRITE");
		//添加作息任务
		$sql = "INSERT INTO audioserver.task (taskname, israndomplay, projectstate, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,";
		
		$sql.= "playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, info, defaultvolume,task_user_id)";
 		
		$sql.= " VALUES( '$coursenamearray[$i]', '0', '0', '$selectnum[$i]', '$belltimelengtharray[$i]', '$prepower', '0', '0', '$startdate', '$enddate', '$belltimearray[$i]', ";
		$sql.= " '$exemodel', '$priority', '1', '0', '0', '0', '0', '0','0','$scheme','$task_default_volume', '$task_user_id') ";

		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
		//取作息任务id
		$sql = "SELECT 	MAX(taskid) FROM task ";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{	
			mysql_query("LOCK TABLES mediaoftask WRITE");
			
			$bellid= $row[0];
			//插入媒体任务
			$sql = "INSERT INTO mediaoftask (mediaid,taskid) VALUES( '$bellnamearray[$i]','$bellid')";
			
			mysql_query($sql) or die(mysql_error());
		}

		@mysql_free_result($result);
		//判断是否有功放
		if($prepower != 0)
		{
			mysql_query("LOCK TABLES task WRITE");
			//插入功放任务
		if($prepower>59)
		{
		$getpowertime=$prepower/60;
		$getfunctintime = date('H:i:s',strtotime($belltimearray[$i]."-0 hours - ".$getpowertime." minutes -0 seconds"));
		}
		else
		{
		$getpowertime=$prepower%60;
		$getfunctintime = date('H:i:s',strtotime($belltimearray[$i]."-0 hours - 0 minutes -".$getpowertime." seconds"));
		}
	
		
			$sql = "INSERT INTO audioserver.task ( taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,";
			$sql.= "playtime, exemodel, priority, tasktype,  channel, bandrate, samplerate, cmd, cmdargs, playfileid, info, defaultvolume,task_user_id,sec_task_id)";
			
			$sql.= " VALUES( '$coursenamearray[$i]', '0', '$selectnum[$i]', '$belltimelengtharray[$i]', '$prepower', '0', '0', '$startdate', '$enddate', '$getfunctintime', ";
			$sql.= " '$exemodel', '$priority', '9',  '0', '0', '0', '0', '0', '0', '$scheme', '$task_default_volume', '$task_user_id','$bellid')";
			
			mysql_query($sql) or die(mysql_error());
			
			$result	=	mysql_query("SELECT MAX(taskid) FROM task") or die("Execute error".mysql_error());
			//取功放任务id
			if($row = mysql_fetch_array($result))
			{
				$powerid = $row[0]; 
			}
			
			@mysql_free_result($result);
		}
		//插入终端任务
		for($j=0;$j<count($terminallistarray);$j++)
		{
			if(is_numeric($terminallistarray[$j]))
			{
				mysql_query("LOCK TABLES terminaloftask WRITE");

				$teriminalid = (int)$terminallistarray[$j];
				
				//$terminalsql="insert into terminaloftask (taskid,terminalid) values('$bellid','$teriminalid')";
				$terminalsql = "INSERT INTO terminaloftask(taskid,terminalid,groupid) VALUES('$bellid','$teriminalid','$analysis_tree_group_ids[$j]')";

				mysql_query($terminalsql) or die(mysql_error());
				
				if($prepower != 0)
				{
					//$terminalsql="insert into terminaloftask(taskid,terminalid) VALUES('$powerid','$teriminalid')";
					$terminalsql = "INSERT INTO terminaloftask(taskid,terminalid,groupid) VALUES('$powerid','$teriminalid','$analysis_tree_group_ids[$j]')";
					
					mysql_query($terminalsql) or die(mysql_error());			
				}
				
		
				for($k=0;$k<strlen($get_terminal);$k++)
				{
				
				if(substr($get_terminal,$k,2)=="::")
									{
									$position=$k+2;
									
									}
						if(substr($get_terminal,$k,1)=="|")
						{
						  $position2 = $k;
						  $position3 = $position2-$position;
									
									$a=substr($get_terminal,$k-$position3,$position3);
									
									if($a==$teriminalid)
										{
									
										$area = substr($get_terminal,$k+1,16);
									
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$bellid' AND terminalid ='$teriminalid'";
										mysql_query($sql) or die(mysql_error());
										unset($sql);
										if(($prepower != 0)||($tasktype==5))
										{
										$area = substr($get_terminal,$k+1,16);
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$powerid' AND terminalid ='$teriminalid'";
										mysql_query($sql) or die(mysql_error());
										unset($sql);
										}
										
										}
						}			
									
									
									
									
				 }
			}
		}
		//=================================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=4&id=".$bellid."&volume=".$task_default_volume;			
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_task_volume("task",4,$bellid,$task_default_volume);
	}
	mysql_query("UNLOCK TABLES");
	
	if(!mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./bellmanager.php";
		
		echo "<script>window.location='success.php'</script>";
	}
}

function belltaskalonemodify()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$scheme = "";
	if(isset($_POST['taskname']))
	{
		$scheme = trim($_POST['taskname']);
	}
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = trim($_POST['prepower']);
	}
	//添加声音
	$task_default_volume = 50;
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	
	$startdate = "00:00:00";
	if(isset($_POST['startdate']))
	{
		$startdate = trim($_POST['startdate']);
	}
	$enddate = "00:00:00";
	if(isset($_POST['enddate']))
	{
		$enddate = trim($_POST['enddate']);
	}
	$exemodel = 1;
	if(isset($_POST['exemodel']))
	  {
	  	$exemodel = trim($_POST['exemodel']);
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = trim($_POST['hiddenweek']);
			$repl = array(',' => '');
			$exemodel = strtr($exemodel,$repl);
		}
	  }
	$hiddencoursename = "";
	if(isset($_POST['hiddencoursename']))
	{	
		$hiddencoursename = trim($_POST['hiddencoursename']);
		$coursenamearray = explode(",",$hiddencoursename);
	}
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal_value = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal_value =strtr($get_terminal_value,$arr);
	}
	$hiddenbelltime = "";
	if(isset($_POST['hiddenbelltime']))
	{	
		$hiddenbelltime = trim($_POST['hiddenbelltime']);
		$belltimearray = explode(",",$hiddenbelltime);
	}
	$hiddenbellname = "";
	if(isset($_POST['hiddenbellname']))
	{	
		$hiddenbellname = trim($_POST['hiddenbellname']);
		$bellnamearray = explode(",",$hiddenbellname);
	}
	$hiddenbelltimelength = "";
	$selectnum = "";
	if(isset($_POST['hiddenbelltimelength']))
	{	
		$hiddenbelltimelength = trim($_POST['hiddenbelltimelength']);
		$belltimelengtharray = explode(",",$hiddenbelltimelength);
		
		for($i=0;$i<count($belltimelengtharray);$i++)
		{
		
			if(strstr($belltimelengtharray[$i],":")!=false)
			{
				$selectnum[$i]=1;
			  $gettimehour=substr($belltimelengtharray[$i],0,2);
			  $gettimeminute=substr($belltimelengtharray[$i],3,2);
			  $gettimesecond=substr($belltimelengtharray[$i],6,2);
			  $belltimelengtharray[$i]=$gettimehour*3600+$gettimeminute*60+$gettimesecond;
			
			}
			else
			{
			$selectnum[$i]=2;
			}
		}
	}
	

	$terminallistvalue = "";
	if(isset($_POST['terminallistvalue']))
	{	
		$terminallistvalue = trim($_POST['terminallistvalue']);
		$terminallistarray= explode(",",$terminallistvalue);
	}
	$hiddenbelltaskid = "";
	if(isset($_POST['hiddenbelltaskid']))
	{	
		$hiddenbelltaskid = trim($_POST['hiddenbelltaskid']);
		$belltaskidarray = explode(",",$hiddenbelltaskid);
	}
	
	$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);


	//$sql = "SELECT usergroup.level FROM usergroup WHERE usergroup.id=(SELECT book_admin.usergroupid FROM book_admin ";
	
	//$sql.= "WHERE book_admin.username='$_SESSION[username]')";
	
	$sql = "SELECT book_admin.id,usergroup.level FROM book_admin,usergroup WHERE ";

	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	$row = mysql_fetch_array($result);
	
	//获取任务优先级
	$priority = 3;
	
	$priority_value = array();
	
	$original_task_userid = array();
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	
	//读取任务用户ID比较若相同则修改 不同则不修改
	$task_userid_sql = "SELECT task.priority FROM task WHERE task.task_user_id = '$task_user_id' AND task.taskid = '$_GET[taskid]' ";
	
	$task_userid_result = mysql_query($task_userid_sql) or die(mysql_error());
	
	if(mysql_num_rows($task_userid_result) <= 0)
	{
		for($i=0; $i<count($belltaskidarray); $i++)
		{
			if( $belltaskidarray[$i] != -1 )
			{
				$original_task_priority_result = mysql_query("SELECT task.priority,task_user_id FROM task WHERE task.taskid='$belltaskidarray[$i]'");

				
				$original_task_priority_row = mysql_fetch_array($original_task_priority_result);
				
				$priority_value[] = trim($original_task_priority_row['priority']);
				
				$original_task_userid[] = trim($original_task_priority_row['task_user_id']);
				
				@mysql_free_result($original_task_priority_result);

				unset($original_task_priority_row);				
			}
			else
			{
				$priority_value[] = $priority;
				
				$original_task_userid[] = trim($original_task_priority_row['task_user_id']);
				
				@mysql_free_result($task_userid_result);
				
				unset($task_userid_sql);
			}
		}
	}
	else
	{
		@mysql_free_result($task_userid_result);
		
		unset($task_userid_sql);
		
		for($i=0; $i<count($belltaskidarray); $i++)
		{
			$priority_value[] = $priority;
			
			$original_task_userid[] = $task_user_id;
		}
	}
	
	@mysql_free_result($result);
	
	unset($sql,$row);

	for($i=0;$i<count($belltaskidarray);$i++)
	{
		if($belltaskidarray[$i] != -1)
		{
			$sql = "SELECT task.info,task.taskname FROM task WHERE task.taskid = '$belltaskidarray[$i]' AND task.prepower > 0 ";
			
			$result = mysql_query($sql) or die(mysql_error());
			
			if($row = mysql_fetch_array($result))
			{
				$sqlfunction = "SELECT taskid FROM task WHERE task.taskname = '$row[taskname]' AND task.info = '$row[info]' AND task.tasktype = 9 ";
			
				$resultfunction = mysql_query($sqlfunction) or die(mysql_error());
			
				if($rowfunction = mysql_fetch_array($resultfunction))
				{
					$getfunctionid = $rowfunction['taskid'];
					
					$sqlterminaloftask = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getfunctionid'"; 
			
					mysql_query($sqlterminaloftask) or die(mysql_error());
			
					unset($sqlterminaloftask);
				}
				@mysql_free_result($resultfunction);
			
				unset($rowfunction,$sqlfunction);
				
				mysql_query("DELETE FROM task WHERE task.taskid = '$getfunctionid'") or die(mysql_error());
			
				unset($getfunctionid);
			}
			@mysql_free_result($result);
			
			unset($row,$sql);
			
			mysql_query("DELETE FROM task WHERE task.taskid = '$belltaskidarray[$i]'") or die(mysql_error());
			
			mysql_query("DELETE FROM mediaoftask WHERE mediaoftask.taskid = '$belltaskidarray[$i]'");
			
			mysql_query("DELETE	FROM terminaloftask WHERE terminaloftask.taskid = '$belltaskidarray[$i]'");
		}
	}
	
	mysql_query("LOCK TABLE task WRITE");
	
	for($i=0;$i<count($coursenamearray);$i++)
	{
		mysql_query("LOCK TABLE task WRITE");
		
		$sql = "INSERT INTO task (taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,startdate, enddate, ";
		
		$sql.= "playtime, exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,info,defaultvolume,task_user_id) ";
		
		$sql.= "VALUES('$coursenamearray[$i]', '0', '0', '$selectnum[$i]', '$belltimelengtharray[$i]', '$prepower', '0', '0', '$startdate', '$enddate', ";
		
		$sql.= "'$belltimearray[$i]','$exemodel','$priority_value[$i]','1','0','0','0','0','0','0','$scheme','$task_default_volume', '$original_task_userid[$i]') ";
		
		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
		
		$sqlbellid = "SELECT MAX(taskid) FROM task ";
		
		$resultbellid = mysql_query($sqlbellid) or die(mysql_error());
		
		if($rowbellid = mysql_fetch_array($resultbellid))
		{
			$getnewbellid = $rowbellid[0];
		}
		@mysql_free_result($resultbellid);
		
		unset($rowbellid,$sqlbellid);
		
		if($prepower > 0)
		{
			mysql_query("LOCK TABLE task WRITE");
			
		if($prepower>59)
		{
			$getpowertime=$prepower/60;
			$getprefunctiontime = date('H:i:s',strtotime($belltimearray[$i]."-0 hours - ".$getpowertime." minutes -0 seconds"));
		}
		else
		{
		$getpowertime=$prepower%60;
		$getprefunctiontime = date('H:i:s',strtotime($belltimearray[$i]."-0 hours - 0 minutes -".$getpowertime." seconds"));
		}
			$sqlfun = "INSERT INTO task (taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate, ";
			
			$sqlfun.= "playtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,info,defaultvolume,task_user_id,sec_task_id) ";
			
			$sqlfun.= "VALUES('$coursenamearray[$i]', '0', '$selectnum[$i]', '$belltimelengtharray[$i]', '$prepower', '0', '0', '$startdate', '$enddate', ";
			
			$sqlfun.= "'$getprefunctiontime','$exemodel','$priority_value[$i]','9','0','0','0','0','0','0','$scheme','$task_default_volume','$original_task_userid[$i]','$getnewbellid' )";
			
			mysql_query($sqlfun) or die(mysql_error());
			
			unset($sqlfun);
			
			$sqlfunid = "SELECT MAX(taskid) FROM task ";
			
			$resultfunid = mysql_query($sqlfunid) or die(mysql_error());
			
			if($rowfunid = mysql_fetch_array($resultfunid))
			{
				$getnewfunid = $rowfunid[0];
			}
		
			@mysql_free_result($resultfunid);
		
			unset($rowfunid,$sqlfunid);
	
			mysql_query("LOCK TABLES terminaloftask WRITE");
			
			for($j=0;$j<count($terminallistarray);$j++)
			{
				if(is_numeric($terminallistarray[$j]))
				{
					$teriminalid = (int)$terminallistarray[$j];
	                 $group =(int)$analysis_tree_group_ids[$j];
					$terminalsql="insert into terminaloftask (taskid,terminalid,groupid) values('$getnewfunid','$teriminalid','$group')";
	
					mysql_query($terminalsql) or die(mysql_error());
	
					unset($terminalsql);
					for($k=0;$k<strlen($get_terminal_value);$k++)
									{
									
									if(substr($get_terminal_value,$k,2)=="::")
														{
														$position=$k+2;
														
														}
														if(substr($get_terminal_value,$k,1)=="|")
														{
														  $position2 = $k;
														  $position3 = $position2-$position;
																	
																	$a=substr($get_terminal_value,$k-$position3,$position3);
																	
																				if($a==$teriminalid)
																					{
																				
																					$area = substr($get_terminal_value,$k+1,16);
																				
																					$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getnewfunid' AND terminalid ='$teriminalid'";
																					mysql_query($sql) or die(mysql_error());
																					unset($sql);

																					
																					}
														}			
								
														
									 }
				}
			}
			
		}
		mysql_query("LOCK TABLE mediaoftask WRITE");
		
		$sqlmediaoftask = "INSERT INTO mediaoftask (mediaid,taskid) VALUES('$bellnamearray[$i]','$getnewbellid')";
		
		mysql_query($sqlmediaoftask) or die(mysql_error());
	
		unset($sqlmediaoftask);
	
		mysql_query("LOCK TABLES terminaloftask WRITE");
		
		for($k=0;$k<count($terminallistarray);$k++)
		{
			if(is_numeric($terminallistarray[$k]))
			{
				$teriminalid = (int)$terminallistarray[$k];
			      $group =(int)$analysis_tree_group_ids[$k];
				$terminalsql="insert into terminaloftask (taskid,terminalid,groupid) values('$getnewbellid','$teriminalid','$group')";
			
				mysql_query($terminalsql) or die(mysql_error());
			
				unset($terminalsql);
									for($m=0;$m<strlen($get_terminal_value);$m++)
									{
									
									if(substr($get_terminal_value,$m,2)=="::")
														{
														$position=$m+2;
														
														}
														if(substr($get_terminal_value,$m,1)=="|")
														{
														  $position2 = $m;
														  $position3 = $position2-$position;
																	
																	$a=substr($get_terminal_value,$m-$position3,$position3);
																	
																				if($a==$teriminalid)
																					{
																				
																					$area = substr($get_terminal_value,$m+1,16);
																				
																					$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getnewbellid' AND terminalid ='$teriminalid'";
																					mysql_query($sql) or die(mysql_error());
																					unset($sql);
																					
																					
																					
																					
																					}
														}			
								
														
									 }
			}
		}	
	}
	if(!mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./bellmanager.php";
		//=======================================================
		/*$socket	=	new	send_message_to_server($port_conf);
		
		$msg = "task?state=5&id=".$_GET['taskid']."&volume=".$task_default_volume;
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_task_volume("task",5,$_GET['taskid'],$task_default_volume);
		
		echo "<script>window.location='success.php'</script>";
	}
}




//添加2、3、4、5任务
function addfileplaytask_msg()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	
	$taskname = "";
	
	$sec_task_id = 0;
	
	$cmd = 0;
	
	$cmdargs = 0;
	
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay = 0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = trim((int)$_POST['israndomplay']);
	} 
	$getfolderid = 0;
	if(isset($_GET['getfolderid']))
	{
		$getfolderid = trim((int)$_GET['getfolderid']);
	}  
		$starthour="";
	if(isset($_POST['starthour']))
	{
		$starthour = $_POST['starthour'];
	}
	$startmin="";
	if(isset($_POST['startmin']))
	{
		$startmin = $_POST['startmin'];
	}
	$startsenc="";
	if(isset($_POST['startsenc']))
	{
		$startsenc = $_POST['startsenc'];
	}
	$getstarttime=$starthour*3600+$startmin*60+$startsenc;
	$medialist=trim($_POST['listvalue']);
			
	$arrmedia=explode(",",$medialist);

	$timelengthtype = 1;
	$getendtime=0;
	$timelength = 0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
		
		if($timelengthtype == 1)
		{  
			$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
			$getendtime=$timelength+$getstarttime;
		}
		else
		{
			$timelength = trim($_POST['circleTime']);
			for($i=0;$i<count($arrmedia);$i++)
			{
					$getmediaid = "SELECT timelength FROM media where id='$arrmedia[$i]'";//取插入任务id
					$mediaidresult = mysql_query($getmediaid) or die(mysql_error());
					while($row = mysql_fetch_array($mediaidresult))
					{
						$getendtime = $getendtime+($row['timelength']*$timelength);//新添加的任务id
						

					}
			}
			$getendtime=$getendtime+$getstarttime;
		} 
	}
	else
	{
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
		$getendtime=$timelength+$getstarttime;
	}
	$getendhour=$getendtime/3600;
	$getendmin=$getendtime%3600/60;
	$getendsec=$getendtime%3600%60;
	
	$getendtime=(int)$getendhour.":".(int)$getendmin.":".(int)$getendsec;
	if($getendhour>=24)
		$getendtime="23:59:59";
	$datasendmodel = 0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	$state = 0;
	
	$startdate="";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	
	if(empty($_POST['startdate']))
	{
		$startdate = "00-00-00";
	}
	
	$enddate="";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	
	if(empty($_POST['enddate']))
	{
		$enddate = "00-00-00";
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = trim($_POST['playtime']);
	}


	if(empty($_POST['playtime']))
	{
		$playtime = "00:00:00";
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
		
		if($prepower!=0)
		{
			if($prepower>59)
			{
			$getpowertime=$prepower/60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$getpowertime."minutes -0 seconds"));
			}
			else
			{
			$getpowertime=$prepower%60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getpowertime."seconds"));
			}
		}
	}
	//获取声音
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	 
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = trim($_POST['exemodel']);
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = trim($_POST['hiddenweek']);
			
			$repl = array(',' => '');
			
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			
			$playtime = "00:00:00";
		}
	}
	
	if(empty($_POST['exemodel']))
	{
		$exemodel = "1111111";
	}
	//获取任务优先级
	$priority=3;
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$tasktype=0;
	
	$audiosource=0;
	
	if(isset($_POST['audiosource']))
	{
		$audiosource = trim($_POST['audiosource']);
		
		$cmd = $audiosource;
		
		$audiosource = 0;
	}
	
	$channel = 0;
	
	if(isset($_POST['channel']))
	{
		$channel = trim($_POST['channel']);
		
		$cmdargs = $channel;
		
		$channel = 0;
	}
	
	$bandrate = 0;
	
	if(isset($_POST['bandrate']))
	{
		$bandrate = trim($_POST['bandrate']);
	}
	
	$samplerate=0;
	if(isset($_POST['samplerate']))
	{
		$samplerate = trim($_POST['samplerate']);
	}
		$terminallistvalue = trim($_POST['terminallistvalue']);
		
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	
	
	
	$playfileid = 0;
	
	$gototaskmanager = "";
	
	$openpower = 0;
	
	$openpowertaskid = 0;
	
	switch($_POST['taskType'])
	{
		case "belltask":
		
			$tasktype = 1;
		
			$gototaskmanager="./bellmanager.php";
		
			break;
		case "fileplaytask":
		
			$tasktype=2;
		
			$gototaskmanager="./taskmanager.php?id=$getfolderid";
			
			$EmergencyBroadcast = 0;
			
			if(isset($_POST['EmergencyBroadcast']))
			{
				$EmergencyBroadcast = trim($_POST['EmergencyBroadcast']);
			}
			
			if($EmergencyBroadcast == 1)
			{
				$tasktype = 7;
			}
			
			break;
			
		case "admmanagertask":
		
			$tasktype = 3;
			
			$interview_repower = 0;//欲开采播电源
			
			$interview_repower_time = 0;//记录欲开时间
			
			//$cmd = $audiosource;
			
			//$cmdargs = $channel;
			
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
			if($interview_repower>59)
			{
				$getpowertime=$interview_repower/60;
				$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$getpowertime."minutes -0 seconds"));
			}
			else
			{
			$getpowertime=$interview_repower%60;
			$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getpowertime."seconds"));
			}
			
			$gototaskmanager="./admmanager.php";
			
			break;
		case "telmanagertask":
			
			$tasktype=4;
			
			$gototaskmanager="./telBroadManager.php";
			
			break;
		case "terfuncplaytask":
		
			$tasktype = 5;
		
			$cmd = 0;
		
			$gototaskmanager="./terminalfunctionplay.php";
		
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			
		break;
	}
	/*************************
		区分任务类型
		同一任务中不允许同名
	**************************/
	if($tasktype == 5)
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '5' ";
		
		$sql_same_name.= "AND prepower = '0' AND tasktype = 5 AND channel = 0 AND info = '' AND sec_task_id = 0 ";
		
		$result_same_name = mysql_query($sql_same_name) or die(mysql_error());
		
		if(mysql_num_rows($result_same_name) > 0)
		{
			//============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
		
			exit;*/
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	else
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype' ";
		
		$result_same_name = mysql_query($sql_same_name) or die(mysql_error());
		
		if(mysql_num_rows($result_same_name) > 0)
		{
			//===========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			//$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	@mysql_free_result($result_same_name);
	
	unset($sql_same_name);
		
	//获取用户优先级
	
	$sql = "SELECT book_admin.id,usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	$row = mysql_fetch_array($result);
	
	//设置优先级
	$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	
	@mysql_free_result($result);
	
	unset($sql,$row);
	
	//加锁并启用事务
	mysql_query("START TRANSACTION");//获取不到插入的值
	
	mysql_query("LOCK TABLES task WRITE,terminaloftask WRITE,mediaoftask WRITE,task READ,terminaloftask READ,mediaoftask READ");
		
	if($tasktype !=1)
	{
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,playtime,endtime,";
		
		$sql.="exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume,task_user_id, sec_task_id,parentid) ";
		
		$sql.="VALUES('$taskname', '$israndomplay', '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate', '$playtime','$getendtime', '$exemodel', '$priority', '$tasktype', '$channel', ";
		
		$sql.="'$bandrate', '$samplerate', '$cmd', '$cmdargs', '$playfileid', '$task_default_volume', '$task_user_id', $sec_task_id,$getfolderid) ";

		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
		
		if(mysql_error())
		{
			mysql_query("ROLLBACK");
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = $gototaskmanager;
			
			echo "<script>window.location='error.php'</script>";
			
			exit;
		}

		$sql = "SELECT MAX(taskid) FROM task";//取插入任务id
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			$gettaskid = $row[0];//新添加的任务id
		}
		
		@mysql_free_result($result);
		
		unset($sql,$row);
		
		
		if(($prepower != 0)||($tasktype==5))
		{						
			if($tasktype == 5)
			{
			
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
				
				$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
				
				$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id) VALUES('$taskname', '$israndomplay', ";
				
				$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
				
				$sql.="'$preopenpowertime', '$exemodel', '$priority', '5', '0', '$bandrate', '$samplerate', ";
				
				$sql.="'1', '0', '$playfileid', '$task_default_volume','$task_user_id', '$gettaskid') ";
			}
			else
			{
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
				
				$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
				
				$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id,parentid) VALUES('$taskname', '$israndomplay', ";
				
				$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
				
				$sql.="'$preopenpowertime', '$exemodel', '$priority', '9', '0', '$bandrate', '$samplerate', ";
				
				$sql.="'0', '0', '$playfileid', '$task_default_volume','$task_user_id', '$gettaskid','$getfolderid') ";
			}
			mysql_query($sql) or die(mysql_error());
			
			unset($sql);
			
			if(mysql_error())
			{
				mysql_query("ROLLBACK");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
			
			//取得功放任务id $openpowertaskid
			
			$resultpower = mysql_query("SELECT MAX(taskid) FROM task") or die(mysql_error());
			  
			$rowpower2 = mysql_fetch_array($resultpower);	
			  
			$openpowertaskid = $rowpower2[0]; 
			  
			@mysql_free_result($resultpower);
			
			unset($rowpower2);
		}
		
		if($tasktype == 3)
		{
			$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
			
			$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
			
			$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id, sec_task_id) VALUES('$taskname', '$israndomplay', ";
			
			$sql.="'$timelengthtype', '$timelength', '$interview_repower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
			
			$sql.="'$interview_repower_time', '$exemodel', '$priority', '8', '$channel', '$bandrate', '$samplerate', ";
			
			$sql.="'0', '$cmdargs', '$playfileid', '$task_default_volume','$task_user_id','$gettaskid') ";
						
			mysql_query($sql) or die(mysql_error());
			
			unset($sql);
			
			if(mysql_error())
			{
				mysql_query("ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
			//取采播任务id
			$col_repower_task_Id = 0;
			
			$col_repowerId_result = mysql_query("SELECT MAX(taskid) FROM task") or die(mysql_error());
			
			$col_repowerId_row = mysql_fetch_array($col_repowerId_result);	
			  
			$col_repower_task_Id = $col_repowerId_row[0]; 
			  
			@mysql_free_result($col_repowerId_result);
			
			unset($col_repowerId_row);
			//插入采播任务终端
			
			mysql_query("insert into terminaloftask (taskid, terminalid) values('$col_repower_task_Id','$cmd')");
			
			if(mysql_error())
			{
				mysql_query("ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
		}
	
	for($i=0; $i<count($terminallistnum); $i++)
		{
			if(is_numeric($terminallistnum[$i]))
			{
				$temp = (int)$terminallistnum[$i];
				//插入终端任务关联
				//$sql="insert into terminaloftask (taskid,terminalid) values('$gettaskid','$temp')";
	          
				
				$c =strlen($temp);
				 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$gettaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
				
					mysql_query($sql) or die(mysql_error());
					
					if(mysql_error())
					{
						mysql_query("ROLLBACK");
					
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = "./bellmanager.php";
						
						echo "<script>window.location='error.php'</script>";
						
						exit;
					}
					
					if(($prepower != 0)||($tasktype==5))
					{
		
						$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$openpowertaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
						
						mysql_query($sql) or die(mysql_error());	
						
						if(mysql_error())
						{
							mysql_query("ROLLBACK");
							
							$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
							
							$_SESSION['url'] = $gototaskmanager;
						
							echo "<script>window.location='error.php'</script>";
						
							exit;
						}		
					}
	
				for($j=0;$j<strlen($get_terminal);$j++)
				{
				
				if(substr($get_terminal,$j,2)=="::")
									{
									$position=$j+2;
									
									}
						if(substr($get_terminal,$j,1)=="|")
						{
						  $position2 = $j;
						  $position3 = $position2-$position;
									
									$a=substr($get_terminal,$j-$position3,$position3);
									
									if($a==$temp)
										{
									
										$area = substr($get_terminal,$j+1,16);
									
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$gettaskid' AND terminalid ='$temp'";
										mysql_query($sql) or die(mysql_error());
										unset($sql);
										if(($prepower != 0)||($tasktype==5))
										{
										$area = substr($get_terminal,$j+1,16);
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$openpowertaskid' AND terminalid ='$temp'";
										mysql_query($sql) or die(mysql_error());
										unset($sql);
										}
										
										}
						}			
									
									
									
									
				 }
				 
				
		
				 
			
				}
				}

						
		
						
						
		
	}

	if($tasktype==2 || $tasktype==7)
	{
		if(isset($_POST['listvalue']))
		{
			$medialist=trim($_POST['listvalue']);
			
			$arrmedia=explode(",",$medialist);
			
			for($i=0;$i<count($arrmedia);$i++)
			{
				$str =$arrmedia[$i];
			
				if(!is_numeric($str))
				{
					continue;
				}
				
				$number =(int)$str;
			
				$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$number','$gettaskid','$i')";
			
				mysql_query($sql) or die(mysql_error());
				
				if(mysql_error())
				{	
					mysql_query("ROLLBACK");
				
					$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
					
					$_SESSION['url'] = $gototaskmanager;
					
					echo "<script>window.location='error.php'</script>";
					
					exit;
				}			
			}	
		}
	}
	
	mysql_query("UNLOCK TABLES");
	mysql_query("COMMIT");
	if(!mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
		//===================================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=4&id=".$gettaskid."&volume=".$task_default_volume;			
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_task_volume("task",4,$gettaskid,$task_default_volume);
		
		echo "<script>window.location='success.php'</script>";
	}			
}
//添加2、3、4、5,6任务
function addwebradiotask_msg()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	
	$taskname = "";
	
	$sec_task_id = 0;
	
	$cmd = 0;
	
	$cmdargs = 0;
	
	 $get_terminal_value=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal_value = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal_value =strtr($get_terminal_value,$arr);
	  
	}
	
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay = 0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = trim((int)$_POST['israndomplay']);
	}  
	$timelengthtype = 1;
	
	$timelength = 0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
		
		if($timelengthtype == 1)
		{  
			$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
		}
		else
		{
			$timelength = trim($_POST['circleTime']);
		} 
	}
	else
	{
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
	}
	
	$datasendmodel = 0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	$state = 0;
	
	$startdate="";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	
	if(empty($_POST['startdate']))
	{
		$startdate = "00-00-00";
	}
	
	$enddate="";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	
	if(empty($_POST['enddate']))
	{
		$enddate = "00-00-00";
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = trim($_POST['playtime']);
	}
	
	if(empty($_POST['playtime']))
	{
		$playtime = "00:00:00";
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
		
		if($prepower!=0)
		{
			if($prepower>59)
			{
			$getprepowertime=$prepower/60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$getprepowertime."minutes -0 seconds"));
			}
			else
			{
			$getprepowertime=$prepower%60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getprepowertime." seconds"));
			}
		}
	}
	//获取声音
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = trim($_POST['exemodel']);
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = trim($_POST['hiddenweek']);
			
			$repl = array(',' => '');
			
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			
			$playtime = "00:00:00";
		}
	}
	
	if(empty($_POST['exemodel']))
	{
		$exemodel = "1111111";
	}
	//获取任务优先级
	$priority=3;
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$tasktype=0;
	
	$audiosource=0;
	
	if(isset($_POST['audiosource']))
	{
		$audiosource = trim($_POST['audiosource']);
		
		$cmd = $audiosource;
		
		$audiosource = 0;
	}
	
	$channel = 0;
	
	if(isset($_POST['channel']))
	{
		$channel = trim($_POST['channel']);
		
		$cmdargs = $channel;
		
		$channel = 0;
	}
	
	$bandrate = 0;
	
	if(isset($_POST['bandrate']))
	{
		$bandrate = trim($_POST['bandrate']);
	}
	
	

	$samplerate=0;
	if(isset($_POST['samplerate']))
	{
		$samplerate = trim($_POST['samplerate']);
	}
	$cmdargs=0;
	if(isset($_POST['cmdargs']))
	{
		$cmdargs = trim($_POST['cmdargs']);
	}
	$get_qallery=0;
	if(isset($_POST['get_qallery']))
	{
		$get_qallery = trim($_POST['get_qallery']);
	}
	
	
	$playfileid = 0;
	
	$gototaskmanager = "";
	
	$openpower = 0;
	
	$openpowertaskid = 0;
	
	switch($_POST['taskType'])
	{
		case "belltask":
		
			$tasktype = 1;
		
			$gototaskmanager="./bellmanager.php";
		
			break;
		case "fileplaytask":
		
			$tasktype=2;
		
			$gototaskmanager="./taskmanager.php";
			
			$EmergencyBroadcast = 0;
			
			if(isset($_POST['EmergencyBroadcast']))
			{
				$EmergencyBroadcast = trim($_POST['EmergencyBroadcast']);
			}
			
			if($EmergencyBroadcast == 1)
			{
				$tasktype = 7;
			}
			
			break;
			
		case "admmanagertask":
		
			$tasktype = 3;
			
			$interview_repower = 0;//欲开采播电源
			
			$interview_repower_time = 0;//记录欲开时间
			
			//$cmd = $audiosource;
			
			//$cmdargs = $channel;
			
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
			
			$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$interview_repower."minutes -0 seconds"));
			
			$gototaskmanager="./admmanager.php";
			
			break;
		case "telmanagertask":
			
			$tasktype=4;
			
			$gototaskmanager="./telBroadManager.php";
			
			break;
		case "terfuncplaytask":
		
			$tasktype = 5;
		
			$cmd = 0;
		
			$gototaskmanager="./terminalfunctionplay.php";
		
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			
		break;
		case "WebRadiotask":
		
			$tasktype = 10;
			
			$interview_repower = 0;//欲开采播电源
			
			$interview_repower_time = 0;//记录欲开时间
			
			//$cmd = $audiosource;
			
			//$cmdargs = $channel;
			
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
			
			$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$interview_repower."minutes -0 seconds"));
			
			$gototaskmanager="./WebRadio.php";
			
			break;
	}
	/*************************
		区分任务类型
		同一任务中不允许同名
	**************************/
	if($tasktype == 5)
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '5' ";
		
		$sql_same_name.= "AND prepower = '0' AND tasktype = 5 AND channel = 0 AND info = '' AND sec_task_id = 0 ";
		
		$result_same_name = mysql_query($sql_same_name) or die(mysql_error());
		
		if(mysql_num_rows($result_same_name) > 0)
		{
			//============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
		
			exit;*/
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	else
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype' ";
		
		$result_same_name = mysql_query($sql_same_name) or die(mysql_error());
		
		if(mysql_num_rows($result_same_name) > 0)
		{
			//===========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	@mysql_free_result($result_same_name);
	
	unset($sql_same_name);
		
	//获取用户优先级
	
	$sql = "SELECT book_admin.id,usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	$row = mysql_fetch_array($result);
	
	//设置优先级
	$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	
	@mysql_free_result($result);
	
	unset($sql,$row);
	
	//加锁并启用事务
	mysql_query("START TRANSACTION");//获取不到插入的值
	
	mysql_query("LOCK TABLES task WRITE,terminaloftask WRITE,mediaoftask WRITE,task READ,terminaloftask READ,mediaoftask READ");
		
	if($tasktype !=1)
	{
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,playtime, ";
		
		$sql.="exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume,task_user_id, sec_task_id) ";
		
		$sql.="VALUES('$taskname', '$israndomplay', '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate', '$playtime', '$exemodel', '$priority', '$tasktype', '$channel', ";
		
		$sql.="'$bandrate', '$samplerate', '$get_qallery', '$cmdargs', '$playfileid', '$task_default_volume', '$task_user_id', $sec_task_id) ";

		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
		
		if(mysql_error())
		{
			mysql_query("ROLLBACK");
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = $gototaskmanager;
			
			echo "<script>window.location='error.php'</script>";
			
			exit;
		}
		
		$sql = "SELECT MAX(taskid) FROM task";//取插入任务id
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			$gettaskid = $row[0];//新添加的任务id
		}
		
		@mysql_free_result($result);
		
		unset($sql,$row);
		
		if(($prepower != 0)||($tasktype==5))
		{						
			if($tasktype == 5)
			{
			
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
				
				$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
				
				$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id) VALUES('$taskname', '$israndomplay', ";
				
				$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
				
				$sql.="'$preopenpowertime', '$exemodel', '$priority', '5', '0', '$bandrate', '$samplerate', ";
				
				$sql.="'1', '$cmdargs', '$playfileid', '$task_default_volume','$task_user_id', '$gettaskid') ";
			}
			else
			{
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
				
				$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
				
				$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id) VALUES('$taskname', '$israndomplay', ";
				
				$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
				
				$sql.="'$preopenpowertime', '$exemodel', '$priority', '9', '0', '$bandrate', '$samplerate', ";
				
				$sql.="'$get_qallery', '$cmdargs', '$playfileid', '$task_default_volume','$task_user_id', '$gettaskid') ";
			}
			mysql_query($sql) or die(mysql_error());
			
			unset($sql);
			
			if(mysql_error())
			{
				mysql_query("ROLLBACK");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
			
			//取得功放任务id $openpowertaskid
			
			$resultpower = mysql_query("SELECT MAX(taskid) FROM task") or die(mysql_error());
			  
			$rowpower2 = mysql_fetch_array($resultpower);	
			  
			$openpowertaskid = $rowpower2[0]; 
			  
			@mysql_free_result($resultpower);
			
			unset($rowpower2);
		}
		
		if($tasktype == 3)
		{
			$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
			
			$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
			
			$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id, sec_task_id) VALUES('$taskname', '$israndomplay', ";
			
			$sql.="'$timelengthtype', '$timelength', '$interview_repower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
			
			$sql.="'$interview_repower_time', '$exemodel', '$priority', '8', '$channel', '$bandrate', '$samplerate', ";
			
			$sql.="'$get_qallery', '$cmdargs', '$playfileid', '$task_default_volume','$task_user_id','$gettaskid') ";
						
			mysql_query($sql) or die(mysql_error());
			
			unset($sql);
			
			if(mysql_error())
			{
				mysql_query("ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
			//取采播任务id
			$col_repower_task_Id = 0;
			
			$col_repowerId_result = mysql_query("SELECT MAX(taskid) FROM task") or die(mysql_error());
			
			$col_repowerId_row = mysql_fetch_array($col_repowerId_result);	
			  
			$col_repower_task_Id = $col_repowerId_row[0]; 
			  
			@mysql_free_result($col_repowerId_result);
			
			unset($col_repowerId_row);
			//插入采播任务终端
			
			mysql_query("insert into terminaloftask (taskid, terminalid) values('$col_repower_task_Id','$cmd')");
			
			if(mysql_error())
			{
				mysql_query("ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
		}
		
		$terminallistvalue = trim($_POST['terminallistvalue']);
		
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
		
		for($i=0; $i<count($terminallistnum); $i++)
		{
			if(is_numeric($terminallistnum[$i]))
			{
				$temp = (int)$terminallistnum[$i];
				//插入终端任务关联
				//$sql="insert into terminaloftask (taskid,terminalid) values('$gettaskid','$temp')";
				
				$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid)VALUES('$gettaskid','$temp','$analysis_tree_group_ids[$i]')";
				
				mysql_query($sql) or die(mysql_error());
				
				if(mysql_error())
				{
					mysql_query("ROLLBACK");
				
					$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
					
					$_SESSION['url'] = "./bellmanager.php";
					
					echo "<script>window.location='error.php'</script>";
					
					exit;
				}
				for($k=0;$k<strlen($get_terminal_value);$k++)
						{
						
						if(substr($get_terminal_value,$k,2)=="::")
											{
											$position=$k+2;
											
											}
								if(substr($get_terminal_value,$k,1)=="|")
								{
								  $position2 = $k;
								  $position3 = $position2-$position;
											
											$a=substr($get_terminal_value,$k-$position3,$position3);
											
											if($a==$temp)
												{
											
												$area = substr($get_terminal_value,$k+1,16);
											
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$gettaskid' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
					
												}
								}			
											
											
											
											
						 }
				
				if(($prepower != 0)||($tasktype==5))
				{
					//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$openpowertaskid','$temp')";
					
					$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid)VALUES('$openpowertaskid','$temp','$analysis_tree_group_ids[$i]')";
					
					mysql_query($sql) or die(mysql_error());	
					
					if(mysql_error())
					{
						mysql_query("ROLLBACK");
						
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = $gototaskmanager;
					
						echo "<script>window.location='error.php'</script>";
					
						exit;
					}
						for($k=0;$k<strlen($get_terminal_value);$k++)
						{
						
						if(substr($get_terminal_value,$k,2)=="::")
											{
											$position=$k+2;
											
											}
								if(substr($get_terminal_value,$k,1)=="|")
								{
								  $position2 = $k;
								  $position3 = $position2-$position;
											
											$a=substr($get_terminal_value,$k-$position3,$position3);
											
											if($a==$temp)
												{
											
												$area = substr($get_terminal_value,$k+1,16);
											
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$openpowertaskid' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
					
												}
								}			
											
											
											
											
						 }
						
				}
				/*if( $tasktype==3 )
				{
					$sql="insert into terminaloftask(taskid,terminalid) VALUES('$col_repower_task_Id','$temp')";
					
					mysql_query($sql) or die(mysql_error());			
					
					if(mysql_error())
					{
						mysql_query("ROLLBACK");
						
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = $gototaskmanager;
					
						echo "<script>window.location='error.php'</script>";
					
						exit;
					}	
				}*/
			}
		}			
	}

	if($tasktype==2 || $tasktype==7)
	{
		if(isset($_POST['listvalue']))
		{
			$medialist=trim($_POST['listvalue']);
			
			$arrmedia=explode(",",$medialist);
			
			for($i=0;$i<count($arrmedia);$i++)
			{
				$str =$arrmedia[$i];
			
				if(!is_numeric($str))
				{
					continue;
				}
				
				$number =(int)$str;
			
				$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$number','$gettaskid','$i')";
			
				mysql_query($sql) or die(mysql_error());
				
				if(mysql_error())
				{	
					mysql_query("ROLLBACK");
				
					$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
					
					$_SESSION['url'] = $gototaskmanager;
					
					echo "<script>window.location='error.php'</script>";
					
					exit;
				}			
			}	
		}
	}
	
	mysql_query("UNLOCK TABLES");
	
	mysql_query("COMMIT");
	
	if(!mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
		//===================================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=4&id=".$gettaskid."&volume=".$task_default_volume;			
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_task_volume("task",4,$gettaskid,$task_default_volume);
		
		echo "<script>window.location='success.php'</script>";
	}		
}
//修改2、3、4、5,6任务
function modifywebradio_msg()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	
	$sec_task_id = 0;
	
	$cmd = 0;
	
	$cmdargs = 0;
	
	$taskname="";
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay=0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = $_POST['israndomplay'];
	}
	
	$timelengthtype=1;
	
	$timelength=0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
	
		if($timelengthtype == 1)
		{  
			$timelength = ($_POST['lenghtHour'] * 60*60 +$_POST['lenghtMin'])*60 +$_POST['lenghtSenc']*1; 
		}
		else
		{
			$timelength = $_POST['circleTime'];
		} 
	}
	else
	{
		$timelength = ($_POST['lenghtHour'] * 60*60 +$_POST['lenghtMin'])*60 +$_POST['lenghtSenc']*1; 
	}
	
	$datasendmodel=0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	$state=0;
	
	$startdate="0000-00-00";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	
	$enddate="0000-00-00";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = $_POST['playtime'];
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
		echo"";
	
		if($prepower!=0)
		{
			if($prepower>59)
			{
			$getprepower=$prepower/60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$getprepower."minutes -0 seconds"));
			}
			else
			{
			$getprepower=$prepower%60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getprepower." seconds"));
			}
		}
	}
	//获取声音
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = $_POST['exemodel'];
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = $_POST['hiddenweek'];
			$repl = array(',' => '');
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			$playtime = "00:00:00";
		}
	}
	
	//获取任务优先级
	$priority = 3;
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$tasktype = 0;
	
	$audiosource = 0;
	if(isset($_POST['audiosource']))
	{	
		$audiosource = trim($_POST['audiosource']);
		
		$cmd = $audiosource;
		
		$audiosource = 0;
	}
	
	$channel=0;
	if(isset($_POST['channel']))
	{	
		$channel = trim($_POST['channel']);
		
		$cmdargs = $channel;
		
		$channel = 0;
	}
	
	$bandrate=0;
	if(isset($_POST['bandrate']))
	{	
		$bandrate = trim($_POST['bandrate']);
	}
	
	$samplerate=0;
	if(isset($_POST['samplerate']))
	{	
		$samplerate = trim($_POST['samplerate']);
	}
	
	$cmdargs=0;
	if(isset($_POST['cmdargs']))
	{	
		$cmdargs = trim($_POST['cmdargs']);
	}
	
	$terminallistvalue = "";
	if(isset($_POST['terminallistvalue']))
	{	
		$terminallistvalue = trim($_POST['terminallistvalue']);
	 
	 	$terminalidarray = explode(",",$terminallistvalue);
	}
	
	$listvalue = "";
	if(isset($_POST['listvalue']))
	{	
		$listvalue = trim($_POST['listvalue']);
	
		$mediaidarray = explode(",",$listvalue);
	}
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	 $get_terminal_value=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal_value = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal_value =strtr($get_terminal_value,$arr);
	  
	}
	 $get_noid=1;
	if(isset($_POST['get_noid']))
	{
	   $get_noids = trim($_POST['get_noid']);
  
	  $arr = array(',' =>'');
	  $get_noids =strtr($get_noids,$arr);
	  
	}
	$get_qallery=0;
	if(isset($_POST['get_qallery']))
	{
		$get_qallery = trim($_POST['get_qallery']);
	}

	
	$analysis_tree_group_string = "";
	
	if(isset($_POST['analysis_tree_group_string']))
	{
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	}
	
	$playfileid = 0;
	
	$gototaskmanager="";
	  
	switch($_POST['taskType'])
	{
		case "belltask":
		
			$tasktype = 1;
			
			$gototaskmanager="./bellmanager.php";
		
		break;
		
		case "fileplaytask":
		
			$tasktype=2;
			
			$gototaskmanager="./taskmanager.php";
			
			$EmergencyBroadcast = 0;
			
			if(isset($_POST['EmergencyBroadcast']))
			{
				$EmergencyBroadcast = trim($_POST['EmergencyBroadcast']);
			}
			
			if($EmergencyBroadcast == 1)
			{
				$tasktype = 7;
			}
			
		break;
		
		case "admmanagertask":
			
			$tasktype=3;
			
			$interview_repower = 0;//欲开采播电源
			
			$col_term_prepower_id = 0;//欲开采播任务id
				
			$interview_repower_time = 0;//欲开采播电源时间
			
			//$cmd = $audiosource;
			
			//$cmdargs = $channel;
		
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
		
			$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$interview_repower."minutes -0 seconds"));
			
			//取出采播终端欲开电源任务id
			$col_term_prepower_sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 and tasktype = 8 ";
			
			$col_term_prepower_result = mysql_query($col_term_prepower_sql) or die(mysql_error());
			
			if($col_term_prepower_row = mysql_fetch_array($col_term_prepower_result))
			{
				$col_term_prepower_id = trim($col_term_prepower_row['taskid']);
			}
			
			@mysql_free_result($col_term_prepower_result);
			
			unset($col_term_prepower_sql,$col_term_prepower_row);
			
			$gototaskmanager="./admmanager.php";
			
		break;
		
		case "webradiomodifytask":
			
			$tasktype=10;
			
			$interview_repower = 0;//欲开采播电源
			
			$col_term_prepower_id = 0;//欲开采播任务id
				
			$interview_repower_time = 0;//欲开采播电源时间
			
			//$cmd = $audiosource;
			
			//$cmdargs = $channel;
			
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
			
			$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$interview_repower."minutes -0 seconds"));
			
			//取出采播终端欲开电源任务id
			$col_term_prepower_sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 and tasktype = 9 ";
			
			$col_term_prepower_result = mysql_query($col_term_prepower_sql) or die(mysql_error());
			
			if($col_term_prepower_row = mysql_fetch_array($col_term_prepower_result))
			{
				$col_term_prepower_id = trim($col_term_prepower_row['taskid']);
			}
			
			@mysql_free_result($col_term_prepower_result);
			
			unset($col_term_prepower_sql,$col_term_prepower_row);
			
			$gototaskmanager="./WebRadio.php";
			
		break;
		
		
		case "telmanagertask":
		
			$tasktype=4;
			
			$gototaskmanager="./telBroadManager.php";
			
			break;
			case "terfuncplaytask":
			
			$tasktype=5;
			
			$cmd = 0;
			
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			
			$gototaskmanager="./terminalfunctionplay.php";
		break;
	}
	
	if($tasktype==5)
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '5' AND task.prepower = 0 ";
		
		$sql_same_name.= "AND task.channel = 0 AND task.info = '' AND task.taskid != '$_GET[taskid]' and task.sec_task_id = 0 ";
		
		$result_same_name = mysql_query($sql_same_name) or die(mysql_error());
		
		if(mysql_num_rows($result_same_name) > 0)
		{
			//=============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	else
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype' ";
		
		$sql_same_name.= "AND task.taskid != '$_GET[taskid]' ";
		
		$result_same_name = mysql_query($sql_same_name) or die(mysql_error());
		
		if(mysql_num_rows($result_same_name) > 0)
		{
			//===========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	@mysql_free_result($result_same_name);
	
	unset($sql_same_name);
	
	//获取用户优先级
		
	$sql = "SELECT book_admin.id, usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	$row = mysql_fetch_array($result);	
	
	//设置优先级
	$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
		
	//读取任务用户ID比较若相同则修改 不同则不修改
	
	$task_userid_sql = "SELECT task.priority FROM task WHERE task.task_user_id = '$task_user_id' AND task.taskid = '$_GET[taskid]' ";
	
	$task_userid_result = mysql_query($task_userid_sql) or die(mysql_error());
	
	if(mysql_num_rows($task_userid_result) <= 0)
	{
		$original_task_priority_result = mysql_query("SELECT task.priority FROM task WHERE task.taskid = '$_GET[taskid]'") or die(mysql_error());
		
		$original_task_priority_row = mysql_fetch_array($original_task_priority_result);
		
		$priority = trim($original_task_priority_row['priority']);
		
		@mysql_free_result($original_task_priority_result);
		
		@mysql_free_result($task_userid_result);
		
		unset($original_task_priority_row,$task_userid_sql);
	}
	else
	{
		@mysql_free_result($task_userid_result);
		
		unset($task_userid_sql);
	}
	
	@mysql_free_result($result);
	
	unset($sql,$row);
	//获取原来的任务名称、预开电源、用户id	
	$getoldtaskname = "";
	
	$getoldtaskprepower = "";
	
	$getoldtaskuserid = "";
	
	$sql = "SELECT task.taskname, task.prepower, task.task_user_id FROM task WHERE task.taskid = '$_GET[taskid]'";
	
	$result = mysql_query($sql)or die(mysql_error());
	
	if($row = mysql_fetch_array($result))
	{
		$getoldtaskname = $row['taskname'];
	
		$getoldtaskprepower = $row['prepower'];
		
		$getoldtaskuserid = $row['task_user_id'];
	}
	
	@mysql_free_result($result);
	
	unset($row,$sql);
		
	//锁定并事务处理
	mysql_query("START TRANSACTION");
	
	mysql_query("LOCK TABLE task WRITE,terminaloftask WRITE,mediaoftask WRITE,task READ,terminaloftask READ,mediaoftask READ");
	
	if($getoldtaskprepower == 0 && $prepower == 0)
	{
		//什么也不做
	}
		else if($getoldtaskprepower == 0 &&	$prepower != 0)
	{
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, startdate, enddate,";
		
		$sql.="playtime, exemodel, priority, tasktype,  channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume, task_user_id, ";
		
		$sql.="sec_task_id) VALUES('$taskname', '$israndomplay',  '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate','$preopenpowertime', '$exemodel', '$priority', '9', '0', ";
		
		$sql.="'$bandrate', '$samplerate', '$get_qallery', '$cmdargs', '$playfileid', '$task_default_volume', '$getoldtaskuserid', '$_GET[taskid]')";
				
		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
		
		//取终端功放id
		
		$result = mysql_query("select max(taskid) from task ");
		
		if($row = mysql_fetch_array($result))
		{
			$getnewfunctionid = $row[0];
		}
		
		@mysql_free_result($result);
		
		unset($row);
		
		for($i=0;$i<count($terminalidarray);$i++)
		{
			if(is_numeric($terminalidarray[$i]))
			{
				$terminalid = (int)$terminalidarray[$i];
				
		        
				//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$getnewfunctionid','$terminalid')";
				
				$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid)VALUES('$getnewfunctionid','$terminalid','$analysis_tree_group_ids[$i]')";
		
				mysql_query($sql) or die(mysql_error());
		
				unset($sql);			
			}
		}
	}
	else if($getoldtaskprepower != 0 &&	$prepower == 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 AND task.info = '' and task.tasktype = '9' ";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysql_free_result($result);
		
		unset($sql,$row);
		
	mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid'") or die(mysql_error());
		
		mysql_query("DELETE FROM task WHERE task.taskid = '$getoldfunctionid'") or die(mysql_error());
	}
	else if($getoldtaskprepower != 0 &&	$prepower != 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 AND task.info = '' and task.tasktype = '9'";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysql_free_result($result);
		
		unset($sql,$row);
        
	//$sql = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' ";
		
	//mysql_query($sql) or die(mysql_error());
		
	//unset($sql);

		$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";
		
		$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' , ";
		
		$sql.="state = '$state' ,startdate = '$startdate' ,enddate = '$enddate' ,";
		
		$sql.="playtime = '$preopenpowertime' ,exemodel = '$exemodel' , priority = '$priority' ,tasktype = '9' , ";
		
		$sql.="channel = '0' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '$get_qallery' ,cmdargs = '0' , ";
		
		$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume',sec_task_id='$_GET[taskid]' ";
		
		$sql.=" WHERE  task.taskid = '$getoldfunctionid' and task.tasktype = '9' and channel = 0 ";
		
		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
	         	for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND groupid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
				     
						}
						
						}
                      
	                   
						for($z=0;$z<strlen($get_id);$z++)
						{
						//alert(z);
						if(substr($get_id,$z,2)=="::")
						{
	
						$position=$z+2;

						}
						if(substr($get_id,$z,1)=="|")
						{
						$position2=$z;
						$get_position =$position2-$position;
						
						$getid = substr($get_id,$z-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND terminalid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
				     
						}
						
						}
  
						for($j=0; $j<count($terminalidarray); $j++)
						{
							if(is_numeric($terminalidarray[$j]))
							{
							    $temp = (int)$terminalidarray[$j];
								$group = (int)$analysis_tree_group_ids[$j];
							
									$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$getoldfunctionid' AND terminalid='$temp' AND groupid = '$group'";
							    $get_result = mysql_query($get_sql) or die(mysql_error());
							  						  
								if($get_row = mysql_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];	
								$get_group = $get_row['groupid'];
								}
								@mysql_free_result($get_result);
								unset($get_sql,$get_row);
								if($temp==$get_terminals)
								{
								  if($get_group==$group)
								  {
								  	  for($z=0;$z<strlen($get_terminal_value);$z++)
											{
										//alert(z);
											if(substr($get_terminal_value,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal_value,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal_value,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal_value,$z+1,16);
										
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
								
								  }
								  else
								  {
										$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									
									 if(empty($get_terminal_value))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal_value);$z++)
											{
										//alert(z);
											if(substr($get_terminal_value,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal_value,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal_value,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
							
												$area = substr($get_terminal_value,$z+1,16);
				
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 					
								  } 
								}
								else 
								{
								
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal_value))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal_value);$z++)
											{
										//alert(z);
											if(substr($get_terminal_value,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal_value,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal_value,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
					
												$area = substr($get_terminal_value,$z+1,16);
				
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}
							
					

							}
						}
										
						
	
	}
	
	$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

	$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' ,state = '$state' ,startdate = '$startdate' ,";
	
	$sql.="enddate = '$enddate' ,playtime = '$playtime' ,exemodel = '$exemodel' ,priority = '$priority' ,tasktype = '$tasktype' , ";

	$sql.="channel = '$channel' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '$get_qallery' ,cmdargs = '$cmdargs' , ";

	$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume' WHERE taskid = '$_GET[taskid]' ";
	
	mysql_query($sql);
	
	unset($sql);

	for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$_GET[taskid]' AND groupid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
				     
						}
						
						}
	             
                   
					for($z=0;$z<strlen($get_id);$z++)
						{
						//alert(z);
						if(substr($get_id,$z,2)=="::")
						{
						
						
						$position=$z+2;
                  
						
						}
						if(substr($get_id,$z,1)=="|")
						{
						$position2=$z;
						$get_position =$position2-$position;
						
						
						$getid = substr($get_id,$z-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$_GET[taskid]' AND terminalid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
						
				     
						}
						
						}
                          	
						for($j=0; $j<count($terminalidarray); $j++)
						{
							if(is_numeric($terminalidarray[$j]))
							{
							   $temp = (int)$terminalidarray[$j];
							   $group = (int)$analysis_tree_group_ids[$j];
							
							  	$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$_GET[taskid]' AND terminalid='$temp' AND groupid = '$group'";
							    $get_result = mysql_query($get_sql) or die(mysql_error());
							  						  
								if($get_row = mysql_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];
								$get_group = $get_row['groupid'];
								}
								@mysql_free_result($get_result);
								unset($get_sql,$get_row);
								
								if($temp==$get_terminals)
								{
								  if($group==$get_group)
								  {
								  for($z=0;$z<strlen($get_terminal_value);$z++)
												{
											//alert(z);
													if(substr($get_terminal_value,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal_value,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
													$a=substr($get_terminal_value,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal_value,$z+1,16);
											
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															mysql_query($sql) or die(mysql_error());
															unset($sql);
															break;
															}
													}
												}						
								  }
								  else
								  {
										$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal_value))
										  {
										  
										  }
										  else
										  {
											   for($z=0;$z<strlen($get_terminal_value);$z++)
												{
											//alert(z);
													if(substr($get_terminal_value,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal_value,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
													$a=substr($get_terminal_value,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal_value,$z+1,16);
															
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															mysql_query($sql) or die(mysql_error());
															unset($sql);
															break;
															}
													}
												}						
										  } 
												
								  } 
								}
								else 
								{
						
								  
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal_value))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal_value);$z++)
											{
										//alert(z);
											if(substr($get_terminal_value,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal_value,$z,1)=="|")
											{
											  $position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal_value,$z-$position3,$position3);
											if($a==$temp)
												{
												//$c=strpos($get_terminal,$a);
			
												$area = substr($get_terminal_value,$z+1,16);
													
							
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}
								
								//checkterminal($temp,$get_terminal,$get_terminals,$_GET[taskid],$j);
							

							}
						}
	mysql_query("UNLOCK TABLES");
	if(!mysql_error())
	{
		mysql_query("COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
		//=======================================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=5&id=".$_GET['taskid']."&volume=".$task_default_volume;
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_task_volume("task",5,$_GET['taskid'],$task_default_volume);
		
		echo "<script>window.location='success.php'</script>";
	}
	
	if(mysql_error())
	{
		mysql_query("ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
	
		echo "<script>window.location='error.php'</script>";
	
		exit;
	}
	
	
}

function checkterminal($temp,$get_terminal,$get_terminals,$getoldfunctionid,$i)
{
   
	$get_sql= "SELECT terminalid  FROM terminaloftask WHERE taskid = '$getoldfunctionid' AND terminalid='$temp'";
							    $get_result = mysql_query($get_sql) or die(mysql_error());
							  						  
								if($get_row = mysql_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];	
								}
								@mysql_free_result($get_result);
								unset($get_sql,$get_row);
								if($temp==$get_terminals)
								{
								  if(empty($get_terminal))
								  {
								  
								  }
								  else
								  {
								   for($z=0;$z<strlen($get_terminal);$z++)
									{
								//alert(z);
									if(substr($get_terminal,$z,2)=="::")
									{	
									$position=$z+2;
									$a=substr($get_terminal,$z+2,strlen($temp));
									if($a==$temp)
										{
										$c=strpos($get_terminal,$a);
	
										$area = substr($get_terminal,$c+strlen($temp)+1,8);
									//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
										mysql_query($sql) or die(mysql_error());
										unset($sql);
										break;
										}
									}
									}						
								  } 
								}
								else 
								{
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','11111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											$a=substr($get_terminal,$z+2,strlen($temp));
											if($a==$temp)
												{
												$c=strpos($get_terminal,$a);
			
												$area = substr($get_terminal,$c+strlen($temp)+1,8);
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}
								
}


//修改2、3、4、5任务
function modifybelltask_msg()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	
	$sec_task_id = 0;
	
	$cmd = 0;
	
	$cmdargs = 0;
	
	$taskname="";
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay=0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = $_POST['israndomplay'];
	}
	 $get_noid=1;
	if(isset($_POST['get_noid']))
	{
	   $get_noids = trim($_POST['get_noid']);
  
	  $arr = array(',' =>'');
	  $get_noids =strtr($get_noids,$arr);
	  
	}
		$listvalue = "";
	if(isset($_POST['listvalue']))
	{	
		$listvalue = trim($_POST['listvalue']);
	
		$mediaidarray = explode(",",$listvalue);
	}
		$starthour="";
	if(isset($_POST['starthour']))
	{
		$starthour = $_POST['starthour'];
	}
	$startmin="";
	if(isset($_POST['startmin']))
	{
		$startmin = $_POST['startmin'];
	}
	$startsenc="";
	if(isset($_POST['startsenc']))
	{
		$startsenc = $_POST['startsenc'];
	}
	$getstarttime=$starthour*3600+$startmin*60+$startsenc;
	
	$getendtime=0;
	$timelengthtype=1;
	
	$timelength=0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
	
		if($timelengthtype == 1)
		{  
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
		$getendtime=$timelength+$getstarttime;
		}
		else
		{
			$timelength = trim($_POST['circleTime']);
			
			for($i=0;$i<count($mediaidarray);$i++)
			{
					$getmediaid = "SELECT timelength FROM media where id='$mediaidarray[$i]'";//取插入任务id
					$mediaidresult = mysql_query($getmediaid) or die(mysql_error());
					while($row = mysql_fetch_array($mediaidresult))
					{
						$getendtime = $getendtime+($row['timelength']*

$timelength);//新添加的任务id
						
					}
			}
			$getendtime=$getendtime+$getstarttime;
		} 
	}
	else
	{
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
		$getendtime=$timelength+$getstarttime;
	}
	$getendhour=$getendtime/3600;
	$getendmin=$getendtime%3600/60;
	$getendsec=$getendtime%3600%60;
	
	$getendtime=(int)$getendhour.":".(int)$getendmin.":".(int)$getendsec;
	if($getendhour>=24)
		$getendtime="23:59:59";
	$datasendmodel=0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	$state=0;
	
	$startdate="0000-00-00";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	
	$enddate="0000-00-00";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = $_POST['playtime'];
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
	
		if($prepower!=0)
		{
			if($prepower>59)
			{
			$getpowertime=$prepower/60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$getpowertime."minutes -0 seconds"));
			}
			else
			{
			$getpowertime=$prepower%60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getpowertime." seconds"));
			}
		}
	}
	//获取声音
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	$get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	$get_taskid=$_GET['taskid'];

	$get_tasktree=$_GET['gettasktree'];
		$terminallistvalue = trim($_POST['terminallistvalue']);
		
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = $_POST['exemodel'];
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = $_POST['hiddenweek'];
			$repl = array(',' => '');
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			$playtime = "00:00:00";
		}
	}
	
	//获取任务优先级
	$priority = 3;
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}

	$tasktype = 0;
	
	$audiosource = 0;
	if(isset($_POST['audiosource']))
	{	
		$audiosource = trim($_POST['audiosource']);
		
		$cmd = $audiosource;
		
		$audiosource = 0;
	}
	
	$channel=0;
	if(isset($_POST['channel']))
	{	
		$channel = trim($_POST['channel']);
		
		$cmdargs = $channel;
		
		$channel = 0;
	}
	
	$bandrate=0;
	if(isset($_POST['bandrate']))
	{	
		$bandrate = trim($_POST['bandrate']);
	}
	
	$samplerate=0;
	if(isset($_POST['samplerate']))
	{	
		$samplerate = trim($_POST['samplerate']);
	}
	
	$terminallistvalue = "";
	if(isset($_POST['terminallistvalue']))
	{	
		$terminallistvalue = trim($_POST['terminallistvalue']);
	 
	 	$terminalidarray = explode(",",$terminallistvalue);
	}
	

	
	$analysis_tree_group_string = "";
	
	if(isset($_POST['analysis_tree_group_string']))
	{
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	}
	
	$playfileid = 0;
	
	$gototaskmanager="";
	  
	switch($_POST['taskType'])
	{
		case "belltask":
		
			$tasktype = 1;
			
			$gototaskmanager="./bellmanager.php";
		
		break;
		
		case "fileplaytask":
		
			$tasktype=2;
			
			$gototaskmanager="./taskmanager.php?id=$get_tasktree";
			
			$EmergencyBroadcast = 0;
			
			if(isset($_POST['EmergencyBroadcast']))
			{
				$EmergencyBroadcast = trim($_POST['EmergencyBroadcast']);
			}
			
			if($EmergencyBroadcast == 1)
			{
				$tasktype = 7;
			}
			
		break;
		
		case "admmanagertask":
			
			$tasktype=3;
			
			$interview_repower = 0;//欲开采播电源
			
			$col_term_prepower_id = 0;//欲开采播任务id
				
			$interview_repower_time = 0;//欲开采播电源时间
			
			//$cmd = $audiosource;
			
			//$cmdargs = $channel;
			
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
			if($interview_repower>59)
			{
				$getpowertime=$interview_repower/60;
				$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$getpowertime."minutes -0 seconds"));
			}
			else
			{
				$getpowertime=$interview_repower%60;
				$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getpowertime." seconds"));
			
			}
			
			//取出采播终端欲开电源任务id
			$col_term_prepower_sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$get_taskid' AND task.channel = 0 and tasktype = 8 ";
			
			$col_term_prepower_result = mysql_query($col_term_prepower_sql) or die(mysql_error());
			
			if($col_term_prepower_row = mysql_fetch_array($col_term_prepower_result))
			{
				$col_term_prepower_id = trim($col_term_prepower_row['taskid']);
			}
			
			@mysql_free_result($col_term_prepower_result);
			
			unset($col_term_prepower_sql,$col_term_prepower_row);
			
			$gototaskmanager="./admmanager.php";
			
		break;
		
		case "telmanagertask":
		
			$tasktype=4;
			
			$gototaskmanager="./telBroadManager.php";
			
			break;
			case "terfuncplaytask":
			
			$tasktype=5;
			
			$cmd = 0;
			
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			
			$gototaskmanager="./terminalfunctionplay.php";
		break;
	}
	
	if($tasktype==5)
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '5' AND task.prepower = 0 ";
		
		$sql_same_name.= "AND task.channel = 0 AND task.info = '' AND task.taskid != '$get_taskid' and task.sec_task_id = 0 ";
		
		$result_same_name = mysql_query($sql_same_name) or die(mysql_error());
		
		if(mysql_num_rows($result_same_name) > 0)
		{
			//=============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	else
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype' ";
		
		$sql_same_name.= "AND task.taskid != '$get_taskid' ";
		
		$result_same_name = mysql_query($sql_same_name) or die(mysql_error());
		
		if(mysql_num_rows($result_same_name) > 0)
		{
			//===========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			//$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	@mysql_free_result($result_same_name);
	
	unset($sql_same_name);
	
	//获取用户优先级
		
	$sql = "SELECT book_admin.id, usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	$row = mysql_fetch_array($result);	
	
	//设置优先级
	$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);

	//读取任务用户ID比较若相同则修改 不同则不修改
	
	$task_userid_sql = "SELECT task.priority FROM task WHERE task.task_user_id = '$task_user_id' AND task.taskid = '$get_taskid' ";
	
	$task_userid_result = mysql_query($task_userid_sql) or die(mysql_error());
	
	if(mysql_num_rows($task_userid_result) <= 0)
	{
		$original_task_priority_result = mysql_query("SELECT task.priority FROM task WHERE task.taskid = '$get_taskid'") or die(mysql_error());
		
		$original_task_priority_row = mysql_fetch_array($original_task_priority_result);
		
	//	$priority = trim($original_task_priority_row['priority']);
		
		@mysql_free_result($original_task_priority_result);
		
		@mysql_free_result($task_userid_result);
		
		unset($original_task_priority_row,$task_userid_sql);
	}
	else
	{
		@mysql_free_result($task_userid_result);
		
		unset($task_userid_sql);
	}
	
	@mysql_free_result($result);
	
	unset($sql,$row);
	//获取原来的任务名称、预开电源、用户id	
	$getoldtaskname = "";
	
	$getoldtaskprepower = "";
	
	$getoldtaskuserid = "";
	
	$sql = "SELECT task.taskname, task.prepower, task.task_user_id FROM task WHERE task.taskid = '$get_taskid'";
	
	$result = mysql_query($sql)or die(mysql_error());
	
	if($row = mysql_fetch_array($result))
	{
		$getoldtaskname = $row['taskname'];
	
		$getoldtaskprepower = $row['prepower'];
		
		$getoldtaskuserid = $row['task_user_id'];
	}
	
	@mysql_free_result($result);
	
	unset($row,$sql);
	//锁定并事务处理
	
	mysql_query("START TRANSACTION");
	
	mysql_query("LOCK TABLE task WRITE,terminaloftask WRITE,mediaoftask WRITE,task READ,terminaloftask READ,mediaoftask READ");

	if($getoldtaskprepower == 0 && $prepower == 0)
	{
		//什么也不做
	}
	else if($getoldtaskprepower == 0 &&	$prepower != 0)
	{
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, startdate, enddate,";
		
		$sql.="playtime, exemodel, priority, tasktype,  channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume, task_user_id, ";
		
		$sql.="sec_task_id) VALUES('$taskname', '$israndomplay',  '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate','$preopenpowertime', '$exemodel', '$priority', '9', '0', ";
		
		$sql.="'$bandrate', '$samplerate', '0', '0', '$playfileid', '$task_default_volume', '$getoldtaskuserid', '$get_taskid')";
				
		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
		
		//取终端功放id
		
		$result = mysql_query("select max(taskid) from task ");
		
		if($row = mysql_fetch_array($result))
		{
			$getnewfunctionid = $row[0];
		}
		
		@mysql_free_result($result);
		
		unset($row);
		
		for($i=0;$i<count($terminalidarray);$i++)
		{
			if(is_numeric($terminalidarray[$i]))
			{
				$terminalid = (int)$terminalidarray[$i];
				
		        
				//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$getnewfunctionid','$terminalid')";
				
				$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid)VALUES('$getnewfunctionid','$terminalid','$analysis_tree_group_ids[$i]')";
		
				mysql_query($sql) or die(mysql_error());
		
				unset($sql);			
			}
		}
	}
	else if($getoldtaskprepower != 0 &&	$prepower == 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$get_taskid' AND task.channel = 0 AND task.info = '' and task.tasktype = '9' ";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysql_free_result($result);
		
		unset($sql,$row);
		
	mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid'") or die(mysql_error());
		
		mysql_query("DELETE FROM task WHERE task.taskid = '$getoldfunctionid'") or die(mysql_error());
	}
	else if($getoldtaskprepower != 0 &&	$prepower != 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 AND task.info = '' and task.tasktype = '9'";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysql_free_result($result);
		
		unset($sql,$row);
        
	//$sql = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' ";
		
	//mysql_query($sql) or die(mysql_error());
		
	//unset($sql);

		$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";
		
		$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' , ";
		
		$sql.="state = '$state' ,startdate = '$startdate' ,enddate = '$enddate' ,";
		
		$sql.="playtime = '$preopenpowertime' ,exemodel = '$exemodel' , priority = '$priority' ,tasktype = '9' , ";
		
		$sql.="channel = '0' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '0' ,cmdargs = '0' , ";
		
		$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume',sec_task_id='$get_taskid' ";
		
		$sql.=" WHERE  task.taskid = '$getoldfunctionid' and task.tasktype = '9' and channel = 0 ";
		
		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
	         	for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND groupid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
				     
						}
						
						}
                      
	                   
						for($z=0;$z<strlen($get_id);$z++)
						{
						//alert(z);
						if(substr($get_id,$z,2)=="::")
						{
	
						$position=$z+2;

						}
						if(substr($get_id,$z,1)=="|")
						{
						$position2=$z;
						$get_position =$position2-$position;
						
						$getid = substr($get_id,$z-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND terminalid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
				     
						}
						
						}
  
						for($j=0; $j<count($terminallistnum); $j++)
						{
							if(is_numeric($terminallistnum[$j]))
							{
							    $temp = (int)$terminallistnum[$j];
								$group = (int)$analysis_tree_group_ids[$j];
							
									$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$getoldfunctionid' AND terminalid='$temp' AND groupid = '$group'";
							    $get_result = mysql_query($get_sql) or die(mysql_error());
							  						  
								if($get_row = mysql_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];	
								$get_group = $get_row['groupid'];
								}
								@mysql_free_result($get_result);
								unset($get_sql,$get_row);
								if($temp==$get_terminals)
								{
								  if($get_group==$group)
								  {
								  	  for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
										
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
								
								  }
								  else
								  {
										$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 					
								  } 
								}
								else 
								{
								
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}
							
								
							  
								
							//	checkterminal($temp,$get_terminal,$get_terminals,$getoldfunctionid,$j);
							

							}
						}
										
						
	
	}
	
	$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

	$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' ,state = '$state' ,startdate = '$startdate' ,";
	
	$sql.="enddate = '$enddate' ,playtime = '$playtime',endtime='$getendtime' ,exemodel = '$exemodel' ,priority = '$priority' ,tasktype = '$tasktype' , ";

	$sql.="channel = '$channel' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '$cmd' ,cmdargs = '$cmdargs' , ";

	$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume' WHERE taskid = '$get_taskid' ";
	
	mysql_query($sql);
	
	unset($sql);
	
	if($tasktype == 3)
	{
		$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

		$sql.="timelength = '$timelength' ,prepower = '$interview_repower' ,datasendmodel = '$datasendmodel' ,";
		
		$sql.="state = '$state' ,startdate = '$startdate' ,enddate = '$enddate' ,";
		
		$sql.="playtime = '$interview_repower_time' ,exemodel = '$exemodel' ,priority = '$priority' , channel = '0' ,";
	
		$sql.="bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '0' ,cmdargs = '$cmdargs' , ";
	
		$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume',sec_task_id='$get_taskid' WHERE task.sec_task_id = '$_GET[taskid]' and tasktype = '8' ";
			
		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
		
		//修改采集任务终端
		mysql_query("UPDATE terminaloftask SET terminalid = '$cmd' WHERE taskid = '$col_term_prepower_id' ") or die(mysql_error());
	}
		
		//对相同功放任务处理
	if($tasktype == 5)
	{
		//查询相同功放任务
		$second_id = 0;
		
		$sql_play = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.tasktype = '5' ";
		
		$sql_play.= "AND task.prepower = '0' and task.channel = 0 and task.info = '' and task.sec_task_id != 0";
		
		$result_play = mysql_query($sql_play) or die(mysql_error());
		
		if($row_play = mysql_fetch_array($result_play))
		{
			$play_id[] = $row_play['taskid'];
		}
		
		@mysql_free_result($result_play);
		
		unset($row_play,$sql_play);
		
		foreach($play_id as $value)
		{
			if($value != trim($_GET['taskid']))
			{
				$second_id = $value;
				
				break;
			}
		}
		unset($play_id);
		
		//更新附加功放
		if(5 == $tasktype)
		{
			$cmd = 0;
		}
		
		$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

		$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' ,state = '$state' , ";
		
		$sql.="startdate = '$startdate' ,enddate = '$enddate' ,playtime = '$preopenpowertime' , ";
		
		$sql.="exemodel = '$exemodel' ,priority = '$priority' ,tasktype = '$tasktype' ,channel = '0' ,bandrate = '$bandrate' , ";
		
		$sql.="samplerate = '$samplerate' ,cmd = '1' ,cmdargs = '0' ,playfileid = '$playfileid' , ";
		
		$sql.="defaultvolume = '$task_default_volume',sec_task_id='$get_taskid' WHERE taskid = '$second_id' ";
		
		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
		
		//删除终端

		//mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id'") or die(mysql_error());
		for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id' AND groupid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
				     
						}
						
						}
		
		
        
		
		for($z=0;$z<strlen($get_id);$z++)
						{
						//alert(z);
						if(substr($get_id,$z,2)=="::")
						{
	
						$position=$z+2;

						}
						if(substr($get_id,$z,1)=="|")
						{
						$position2=$z;
						$get_position =$position2-$position;
						
						$getid = substr($get_id,$z-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id' AND terminalid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
				     
						}
						
						}
		//添加终端
		for($i=0;$i<count($terminalidarray);$i++)
		{
			if(is_numeric($terminalidarray[$i]))
			{
				$terminalid = (int)$terminalidarray[$i];
				$group = (int)$analysis_tree_group_ids[$i];
				//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$second_id','$terminalid')";
				$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$second_id' AND terminalid='$terminalid' AND groupid='$group'";
							    $get_result = mysql_query($get_sql) or die(mysql_error());
							  						  
								if($get_row = mysql_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];
								$get_group = $get_row['groupid'];	
								}
								@mysql_free_result($get_result);
								unset($get_sql,$get_row);
								if($terminalid==$get_terminals)
								{
								 if($group==$get_group)
								 {
								 
								 }
								 else
								 {
				                    $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$second_id','$terminalid','$analysis_tree_group_ids[$i]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  	  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$terminalid)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$second_id' AND terminalid ='$terminalid'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 
								 
								 }

									}
									else 
								{
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$second_id','$terminalid','$analysis_tree_group_ids[$i]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$terminalid)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$second_id' AND terminalid ='$terminalid'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}	  
										  
				
				
							
			}
		}
	}
	if($tasktype == 2)
	{
		$sqlmediaoftask = "DELETE FROM mediaoftask WHERE mediaoftask.taskid = '$_GET[taskid]' ";
		
		mysql_query($sqlmediaoftask) or die(mysql_error());
		
		unset($sqlmediaoftask);
		
		for($i=0;$i<count($mediaidarray);$i++)
		{
			$getmediaid = $mediaidarray[$i];
		
			if(is_numeric($getmediaid))
			{
				$getmediaid =(int)$getmediaid;
		
				$sql="INSERT INTO mediaoftask (mediaid,taskid,sort) VALUES ('$getmediaid','$_GET[taskid]','$i')";
		
				mysql_query($sql) or die(mysql_error());
		
				unset($sql);
			}
		}
	}
	
	for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$_GET[taskid]' AND groupid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
				     
						}
						
						}
	             
                   
					for($z=0;$z<strlen($get_id);$z++)
						{
						//alert(z);
						if(substr($get_id,$z,2)=="::")
						{
						
						
						$position=$z+2;
                  
						
						}
						if(substr($get_id,$z,1)=="|")
						{
						$position2=$z;
						$get_position =$position2-$position;
						
						
						$getid = substr($get_id,$z-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$_GET[taskid]' AND terminalid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
						
				     
						}
						
						}
                          	
						for($j=0; $j<count($terminallistnum); $j++)
						{
							if(is_numeric($terminallistnum[$j]))
							{
							   $temp = (int)$terminallistnum[$j];
							   $group = (int)$analysis_tree_group_ids[$j];
							
							  	$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$_GET[taskid]' AND terminalid='$temp' AND groupid = '$group'";
							    $get_result = mysql_query($get_sql) or die(mysql_error());
							  						  
								if($get_row = mysql_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];
								$get_group = $get_row['groupid'];
								}
								@mysql_free_result($get_result);
								unset($get_sql,$get_row);
								
								if($temp==$get_terminals)
								{
								  if($group==$get_group)
								  {
								  for($z=0;$z<strlen($get_terminal);$z++)
												{
											//alert(z);
													if(substr($get_terminal,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
													$a=substr($get_terminal,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal,$z+1,16);
											
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															mysql_query($sql) or die(mysql_error());
															unset($sql);
															break;
															}
													}
												}						
								  }
								  else
								  {
										$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
											   for($z=0;$z<strlen($get_terminal);$z++)
												{
											//alert(z);
													if(substr($get_terminal,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
													$a=substr($get_terminal,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal,$z+1,16);
															
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															mysql_query($sql) or die(mysql_error());
															unset($sql);
															break;
															}
													}
												}						
										  } 
												
								  } 
								}
								else 
								{
						
								  
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											  $position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
											if($a==$temp)
												{
												//$c=strpos($get_terminal,$a);
			
												$area = substr($get_terminal,$z+1,16);
													
							
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}
								
								//checkterminal($temp,$get_terminal,$get_terminals,$_GET[taskid],$j);
							

							}
						}

	mysql_query("UNLOCK TABLES");
    	if(!mysql_error())
			{
				mysql_query("COMMIT");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				//=======================================================================
				/*$socket	=	new	send_message_to_server($port_conf);	
				
				$msg = "task?state=5&id=".$_GET['taskid']."&volume=".$task_default_volume;
				
				$socket->send_data($_SESSION['serverip'],$msg);
				*/
				$create_socket_obj->send_socket_task_volume("task",5,$_GET['taskid'],$task_default_volume);
				
				echo "<script>window.location='success.php'</script>";
			}
			


	if(mysql_error())
	{
		mysql_query("ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
	
		echo "<script>window.location='error.php'</script>";
	
		exit;
	}	
}


//添加文件广播任务---没有被使用
function taskadd_msg()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php");  
	//===============================添加外部变量
	global $do_php_prompt;
	//===============================创建套字节==============================
	$create_socket_obj = new create_socket_class();
		 
	mysql_query("INSERT INTO `task` (`taskname`,`streamid`,`startdate`,`starttime`,`timelength`,`playmodel`) VALUES ('$_POST[taskname]','$_POST[streamid]','$_POST[startdate]','$_POST[starttime]','$_POST[timelength]','$_POST[playmodel]')"); 
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php";
	
		$result=mysql_query("select max(id) from task")or die("Execute error".mysql_error());
	
		if($row=mysql_fetch_array($result))
		{
			$getid=$row[0];
			//=====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "task?state=4&id=".$getid;			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",4,$getid);
		}
		echo "<script>window.location='success.php'</script>";	
	}		
}
//启用文件广播---状态为3
function filetaskstart_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//===============================创建套字节==============================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	$gettaskid = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettaskid = trim($_GET['gettask']);
	}

	$sql = "update task set state=3 where taskid in (".$getValue.") and task.tasktype=2 and task.info='' and task.channel=0 and sec_task_id=0 ";
	
	mysql_query($sql) or die(mysql_error());
	
	unset($sql);
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php?id=$gettaskid";
		
		echo "<script>window.location='./error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php?id=$gettaskid";
		
		$getidlist = explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "task?state=3&id=".$getid;			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
		
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}

		echo "<script>window.location='./success.php'</script>";	
		
		exit;//是什么原因呢？？？？？？？
	}		
}
//停止文件任务---状态为2
function filetaskstop_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//=======================创建套字节=======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	$gettaskid = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettaskid = trim($_GET['gettask']);
	}
	
	$sql = "update task set state=2 where taskid in (".$getValue.") and task.tasktype=2 and task.info='' and task.channel=0 and sec_task_id=0 ";
	
	mysql_query($sql) or die(mysql_error());
	 
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php?id=$gettaskid";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php?id=$gettaskid";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=2&id=".$getid;
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",2,$getid);
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
//查看当前任务中操作---暂停
function stop_curr_tast_state()
{
	require_once("inc/config.inc.php");
	
	//require_once('inc/socket_conf.php'); 
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建套字节===================
	$create_socket_obj = new create_socket_class();
	
	$task_state = 2;
	
	$curr_task_id = "";
	
	if(isset($_GET['taskid']))
	{
		$curr_task_id = trim($_GET['taskid']);
	}
	//判断是什么类型任务
	$judge_task_sql = "SELECT taskname,tasktype FROM audioserver.task WHERE task.taskid = '$curr_task_id'";
	
	$judge_task_result = mysql_query($judge_task_sql) or die(mysql_error());
	
	if($judge_task_row = mysql_fetch_array($judge_task_result))
	{
		if(5 == $judge_task_row['tasktype'])
		{
			$judge_task_othersql = "select taskid from task where prepower=0 and info='' and ";
	
			$judge_task_othersql.= "tasktype=5 and taskname= '$judge_task_row[taskname]' and taskid != '$curr_task_id'";
	
			$judge_task_otherresult = mysql_query($judge_task_othersql) or die(mysql_error());
	
			if($judge_task_otherrow = mysql_fetch_array($judge_task_otherresult))
			{
				$power_other_taskid = trim($judge_task_otherrow['taskid']);
			}
	
			@mysql_free_result($judge_task_otherresult);
	
			unset($judge_task_othersql,$judge_task_otherrow);
			
			$task_state = 3;
			
			$curr_task_id = $power_other_taskid;
		}
	}
	@mysql_free_result($judge_task_result);
	
	unset($judge_task_row,$judge_task_sql);
	
	$task_sql = "update task set state=".$task_state." where taskid = '$curr_task_id' ";
	
	$task_result = mysql_query($task_sql) or die(mysql_error());
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
		$_SESSION['url'] = "./Browse_active_task.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
		$_SESSION['url'] = "./Browse_active_task.php";
		//===================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=".$task_state."&id=".$curr_task_id;			
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_generate_general("task",$task_state,$curr_task_id);
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//查看当前任务中操作---执行
function start_curr_tast_state()
{
	require_once("inc/config.inc.php");
	
	//require_once('inc/socket_conf.php'); 
	//=======================添加外部变量
	global $do_php_prompt;
	//====================创建套字节===================
	$create_socket_obj = new create_socket_class();
	
	$curr_task_id = "";
	
	if(isset($_GET['taskid']))
	{
		$curr_task_id = trim($_GET['taskid']);
	}
	
	$task_sql = "update task set state=3 where taskid = '$curr_task_id' ";
	
	$task_result = mysql_query($task_sql) or die(mysql_error());
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
		$_SESSION['url'] = "./Browse_active_task.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
		$_SESSION['url'] = "./Browse_active_task.php";
		//=======================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=3&id=".$curr_task_id;			
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_generate_general("task",3,$curr_task_id);
		
		echo "<script>window.location='success.php'</script>";	
	}	
}

//编辑任务---没有被使用
function taskedit_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	mysql_query("UPDATE `task` SET `taskname`='$_POST[taskname]',`streamid`='$_POST[streamid]',`startdate`='$_POST[startdate]',`starttime`='$_POST[starttime]', `timelength`='$_POST[timelength]' WHERE taskid='$_GET[id]'");	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//对文件广播任务删除
function taskdel_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建套字节===================
	$create_socket_obj = new create_socket_class();
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		
		$f_taskId_array = explode(",",$taskid);
	}
	$gettask = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettask = trim($_GET['gettask']);
		
		
	}
	//启用事务
	mysql_query("START TRANSACTION");
	
	for($i=0; $i<count($f_taskId_array); $i++)
	{
		//判断该任务功放
		$file_task_sql = "SELECT prepower FROM task WHERE task.taskid = '$f_taskId_array[$i]' AND (task.tasktype = 2 OR task.tasktype = 7) ";
		
		$file_task_sql.= "AND task.info = '' AND task.channel = 0 ";
		
		$file_task_result = mysql_query($file_task_sql);
		
		if($file_task_row = mysql_fetch_array($file_task_result))
		{
			if($file_task_row['prepower'] > 0)
			{
				//查找相关功放
				$file_func_sql = "SELECT taskid FROM task WHERE sec_task_id = '$f_taskId_array[$i]' AND tasktype = 9 AND info = '' AND channel = 0 ";
				
				$file_func_result = mysql_query($file_func_sql);
				
				if($file_func_row = mysql_fetch_array($file_func_result))
				{
					//删除攻防任务
					mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid = '".$file_func_row['taskid']."'");
					
					//删除功放
					mysql_query("DELETE FROM task WHERE taskid = '".$file_func_row['taskid']."' AND info = '' AND tasktype = 9 AND channel = 0 ");
				}
				
				@mysql_free_result($file_func_result);
				
				unset($file_func_row,$file_func_sql);
			}
		}
		@mysql_free_result($file_task_result);
				
		unset($file_task_row,$file_task_sql);
	}
	
	//删除终端任务
	mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid IN(".$taskid.")");
	
	//删除媒体任务
	mysql_query("DELETE FROM mediaoftask WHERE mediaoftask.taskid IN(".$taskid.")");
	
	//删除自己任务
	mysql_query("DELETE FROM task WHERE taskid IN(".$taskid.") AND info = '' AND (tasktype = 2 or tasktype = 7) AND channel = 0 ");
	
	if(!mysql_error())
	{
		mysql_query("COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php?id=$gettask";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	= new send_message_to_server($port_conf);	
			
			$msg = "task?state=6&id=".$getid;		
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}
		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysql_query("ROLLBACK");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php?id=$gettask";
		
		echo "<script>window.location='error.php'</script>";
	}

}
//删除任务日志
function tasklogdel_msg()
{
	require_once("inc/config.inc.php");
	require_once("inc/config.php");
	//添加外部变量
	$get_task_log=0;
	global $do_php_prompt;

	$Task_Logs = array();

	if(is_dir($Task_Log))
	{
		if($folder_handle = opendir($Task_Log))
		{
			while( ($file = readdir($folder_handle)) !== false)
			{
				if($file != "." && $file != "..")
				{
					if(is_file($Task_Log."/".$file))
					{
	
								unlink($Task_Log.$file);
					}
				}
			}
		}
	}

	
	$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息

	$_SESSION['url'] = "./tasklogmanager.php";

	echo "<script>window.location='success.php'</script>";	
}

//删除日志
function logdel_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//mysql_query("DELETE FROM `log` WHERE id in $_GET[id]");	
	mysql_query("TRUNCATE TABLE log");
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./logmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./logmanager.php";
	
		echo "<script>window.location='success.php'</script>";	
	}
}
//创建分区---没有被使用
function streamadd22_msg()
{
	require_once("inc/config.inc.php");	
	
	//添加外部变量
	global $do_php_prompt;
			
	mysql_query("INSERT INTO `serverplaystream` (`name`,`feedfile`,`feed`,`outputformat`,`inputformat`,`AudioCodec`,`MaxTime`,`AudioBitRate`,`AudioChannels`,`AudioSampleRate`,`AudioQuality`) 	VALUES ('$_POST[name]','$_POST[feedfile]','$_POST[feed]','$_POST[outputformat]','$_POST[inputformat]','$_POST[AudioCodec]','$_POST[MaxTime]','$_POST[AudioBitRate]','$_POST[AudioChannels]','$_POST[AudioSampleRate]','$_POST[AudioQuality]')");
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}	
}
//创建分区---问题必须添加终端---问题（要不在任务编辑时显示分区有问题---没有对空分区处理）
function streamadd_msg()
{
	require_once("inc/config.inc.php");
	
	require_once("inc/terminal_group_operate.php");
	$userid=$_SESSION['userid'];
	//添加外部变量
	global $do_php_prompt;
	//====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$streamname = "";
	if(isset($_POST['streamname']))
	{
		$streamname = trim($_POST['streamname']);
	}
	$discription = "";
	if(isset($_POST['discription']))
	{
		$discription = trim($_POST['discription']);
	}
	$nostreamterminal = "";
	if(isset($_POST['nostreamterminal']))
	{
		$nostreamterminal = trim($_POST['nostreamterminal']);
		
		$nostreamarray = explode(",",$nostreamterminal);
	}
	//保证分区名称唯一
	 $sql = "select * from serverplaystream where serverplaystream.name = '$streamname'";
	 
	 $result = mysql_query($sql) or die(mysql_error());
	 
	 if(mysql_num_rows($result) > 0)
	 {
	 	@mysql_free_result($result);
	
		unset($sql);
		//============================================================================================
	 	/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
	
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	 }
	 else
	 {
	 	@mysql_free_result($result);
	
		unset($sql);
	 }
	mysql_query("LOCK TABLE serverplaystream WRITE,terminalofgroup WRITE");
	
	$sql = "INSERT INTO serverplaystream (NAME, info,userid) VALUES('$streamname', '$discription','$userid') ";

	mysql_query($sql) or die(mysql_error());
	
	unset($sql);
	
	if(!empty($nostreamterminal))
	{
		$result = mysql_query("SELECT MAX(streamid) FROM serverplaystream") or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			$getnewstreamid = $row[0];
		}
		@mysql_free_result($result);
		
		unset($row,$sql);
			
		for($i=0; $i<count($nostreamarray); $i++)
		{
			if(is_numeric($nostreamarray[$i]))
			{
				$terminalid = (int)$nostreamarray[$i];
				
		//$sql = "UPDATE terminal SET  groupid = '$getnewstreamid' WHERE	terminal.id = '$terminalid' ";

			//	mysql_query($sql) or die(mysql_error());
				
				$sql = "INSERT INTO audioserver.terminalofgroup(terminalid,groupid) VALUES('$terminalid','$getnewstreamid')";
				
				insert_group($sql);	
				
				unset($sql);		
			}
		}
	}
	mysql_query("UNLOCK TABLES");
	
	if(!mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}	
}
//修改分区---没有使用到
function streamedit_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	mysql_query("UPDATE `serverplaystream` SET `name`='$_POST[name]',`feed`='$_POST[feed]',`feedfile`='$_POST[feedfile]' ,`outputformat`='$_POST[outputformat]',`inputformat`='$_POST[inputformat]',`AudioCodec`='$_POST[AudioCodec]',`MaxTime`='$_POST[MaxTime]',`AudioBitRate`='$_POST[AudioBitRate]',`AudioChannels`='$_POST[AudioChannels]',`AudioSampleRate`='$_POST[AudioSampleRate]',`AudioQuality`='$_POST[AudioQuality]' where streamid = '$_GET[id]'");	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		//inputterminaltofile();//修改终端文件数据
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//修改终端分区
function streambatedit_msg()
{
	require_once("inc/config.inc.php");
	
	require_once("inc/terminal_group_operate.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$streamname = "";
	if(isset($_POST['streamname']))
	{
		$streamname = trim($_POST['streamname']);
	}
	$description = "";
	if(isset($_POST['description']))
	{
		$description = trim($_POST['description']);
	}
	$selectedterminal = "";
	if(isset($_POST['selectedterminal']))
	{
		$selectedterminal = trim($_POST['selectedterminal']);
		$getterminalarray = explode(",",$selectedterminal);
	}
	$streamid = "";
	if(isset($_GET['id']))
	{
		$streamid = trim($_GET['id']);
	}

	$sql = "SELECT 	* FROM serverplaystream WHERE serverplaystream.name = '$streamname' AND serverplaystream.streamid != '$streamid'";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	if(mysql_num_rows($result) > 0)
	{
		@mysql_free_result($result);
		unset($sql);
		//===========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
		
		exit;
		*/
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	else
	{
		@mysql_free_result($result);
		
		unset($sql);
	}
	
	mysql_query("LOCK TABLES serverplaystream WRITE,terminalofgroup WRITE");
	
	$sql = "UPDATE serverplaystream SET serverplaystream.name = '$streamname', info = '$description' WHERE serverplaystream.streamid = '$streamid' ";
	
	update_group($sql);
	
	unset($sql);
	
	$sql = "DELETE FROM audioserver.terminalofgroup WHERE terminalofgroup.groupid in('$streamid')";
	
	delet_group($sql);
	
	unset($sql);
	
	foreach($getterminalarray as $terminal_id)
	{
		if(is_numeric($terminal_id))
		{
			$sql = "INSERT INTO audioserver.terminalofgroup (terminalid,groupid) VALUES('$terminal_id','$streamid')";
			
			insert_group($sql);
			
			unset($sql);
		}
	}
	mysql_query("UNLOCK TABLES");
	
	if(!mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		echo "<script>window.location='success.php'</script>";	
	}
}
//创建终端分区---没有被使用到
function streambaddterminal_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	$getterminal=$_POST['selectedterminal'];
	
	$getstream=$_GET['id'];
	
	$sql="UPDATE terminal SET terminal.groupid = '$getstream' WHERE terminal.id IN ($getterminal) ";
	
	mysql_query($sql) or die(mysql_error());
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";

		inputterminaltofile();

		echo "<script>window.location='success.php'</script>";	
	}
}
//删除寻呼分区
function delcallzone()
{
	require_once("inc/config.inc.php");
	
	require_once("inc/terminal_group_operate.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	$streamid = "";
	
	if(isset($_GET['id']))
	{
		$streamid = trim($_GET['id']);
	}
	
	mysql_query("LOCK TABLES terminalofcallgroup WRITE,callgroup WRITE");
	
	$sql = "DELETE FROM callgroup WHERE callgroup.id IN($streamid)";
	
	delet_group($sql);
	
	unset($sql);
	
	$sql = "DELETE FROM terminalofcallgroup WHERE terminalofcallgroup.selectgroupid IN($streamid)";

	delet_group($sql);
	
	unset($sql);
	
	mysql_query("UNLOCK TABLES");
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./view_terminal_call_group.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./view_terminal_call_group.php";
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//删除终端分区
function streamdel_msg()
{
	require_once("inc/config.inc.php");
	
	require_once("inc/terminal_group_operate.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	$streamid = "";
	
	if(isset($_GET['id']))
	{
		$streamid = trim($_GET['id']);
	}
	
	mysql_query("LOCK TABLES terminalofgroup WRITE,serverplaystream WRITE");
	
	$sql = "DELETE FROM audioserver.terminalofgroup WHERE terminalofgroup.groupid IN($streamid)";
	
	delet_group($sql);
	
	unset($sql);
	
	$sql = "DELETE FROM serverplaystream WHERE serverplaystream.streamid IN($streamid)";

	delet_group($sql);
	
	unset($sql);
	
	mysql_query("UNLOCK TABLES");
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//修改服务器参数
function serveredit_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建套字节=====================
	$create_socket_obj = new create_socket_class();
	
	mysql_query("UPDATE `serverbaseparam` SET `name`='$_POST[name]',serverip='$_POST[slave_serverip]',`ip`='$_POST[ip]',`gateway`='$_POST[gateway]',`port`='$_POST[port]',`udpport`='$_POST[udpport]',`maxbandwidth`='$_POST[maxbandwidth]',`maxhttpconnections`='$_POST[maxhttpconnections]' WHERE id='$_GET[id]'");	
	/*
	$fp=fopen("/opt/mediaupdate.sh","r");
	$data="";
	while(!feof($fp))
	{
		$data.=fread($fp,sizeof($fp));
	}
	$position=strpos($data,"@");
	$position2=strpos($data,"::");
	$serverip=$_POST[slave_serverip];
	$fd=substr_replace($data,$serverip,$position+1,$position2-1-$position);
	
	fclose($fs);

	$fs =fopen("/opt/mediaupdate.sh","w");
	
	fwrite($fs,$fd);
	
	fclose($fs);
*/
	
	if(mysql_error())
	{
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./servermanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']."---".$do_php_prompt['The_system_is_restarting']);//提示信息
		
		$_SESSION['url'] = "./servermanager.php";
		//==========================================================================
	
	
		$create_socket_obj->send_socket_server("server",$_POST['ip'],$_POST['port'],$_POST['udpport'],$_POST['maxbandwidth'],$_POST['maxhttpconnections'],$_POST['gateway']);
		
		echo "<script>window.location='success.php'</script>";	
	}

}
//启用方案
function bellstart_msg()
{
	require_once("inc/config.inc.php"); 

	//require_once("inc/socket_conf.php"); 
	
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue=$_GET['id'];
	
	/*$sql = "SELECT * FROM task WHERE task.tasktype = '1' AND task.projectstate = '0' GROUP BY task.info";

	$result = mysql_query($sql) or die(mysql_error());

	if($row = mysql_fetch_array($result))
	{
		if($row[0] >= 1)
		{
			@mysql_free_result($result);
			
			unset($sql);
			//========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['Has_scheme_running'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Has_scheme_running']);
		}
	}

	@mysql_free_result($result);

	unset($sql);
	*/

	$sql = "SELECT task.info from task where task.taskid = '$getValue' and task.tasktype = 1";

	$result = mysql_query($sql) or die(mysql_error());

	if($row = mysql_fetch_array($result))
	{
		mysql_query("UPDATE task SET projectstate = '0',state = '0' WHERE task.info = '$row[info]' AND task.tasktype = 1");
		
		if(mysql_error())
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./bellmanager.php";

			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./bellmanager.php";
			
			//======================================================
			/*$socket	=	new	send_message_to_server($port_conf);	

			$msg = "project?state=1&name=".$row[info];			

			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_schedules("project",1,$row['info']);

			echo "<script>window.location='success.php'</script>";	
		}		  	
	}
}
//停止方案
function bellstop_msg()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php"); 
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = trim($_GET['id']);
	
	$sql = "SELECT task.info from task where task.taskid = '$getValue' and task.tasktype = 1 ";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	if($row = mysql_fetch_array($result))
	{
		mysql_query("UPDATE task SET projectstate = '1',state = '0' WHERE task.info = '$row[info]' AND task.tasktype = 1");
		

		if(mysql_error())
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./bellmanager.php";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./bellmanager.php";
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "project?state=2&name=".$row[info];			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_schedules("project",2,$row['info']);
		
			echo "<script>window.location='success.php'</script>";	
		}		  	
	}	
}
//删除方案
function belldel_msg()
{
require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
		
		mysql_query("START TRANSACTION");
		//找到同组作息方案任务
		$sql = "SELECT task.taskid FROM task WHERE task.info IN(SELECT info FROM task WHERE task.taskid IN($getid) AND ";
		
		$sql.= "info!='' and channel=0) and info!='' and channel=0 ";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		while($row = mysql_fetch_array($result))
		{
			$sqlmedia = "DELETE FROM mediaoftask WHERE mediaoftask.taskid = '$row[taskid]'";
		
			mysql_query($sqlmedia) or die(mysql_error());
		
			if(mysql_error())
			{
				@mysql_free_result($result);
				
				unset($row,$sql);
				
				mysql_query("ROLLBACK");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./bellmanager.php";
				
				echo "<script>window.location = 'error.php'</script>";
				
				exit;
			}
			$sqlterminal = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$row[taskid]'";
			
			mysql_query($sqlterminal) or die(mysql_error());
			
			if(mysql_error())
			{
				@mysql_free_result($result);
				
				unset($row,$sql);
				
				mysql_query("ROLLBACK");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./bellmanager.php";
				
				echo "<script>window.location = 'error.php'</script>";
				
				exit;
			}
			$sqltask = "DELETE FROM task WHERE task.taskid = '$row[taskid]'";
			
			mysql_query($sqltask);
			
			if(mysql_error())
			{
				@mysql_free_result($result);
			
				unset($row,$sql);
			
				mysql_query("ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./bellmanager.php";
			
				echo "<script>window.location = 'error.php'</script>";
			
				exit;
			}
		}
		
		@mysql_free_result($result);
		
		unset($row,$sql);
		
		mysql_query("COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./bellmanager.php";
	
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
			//======================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=6&id=".$getid;			
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}
		echo "<script>window.location.href = 'success.php'</script>";
	}
}

//复制方案
function bellcop_msg()
{
	require_once("inc/config.inc.php");


	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;	
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$bellname ="";
	
	if(isset($_POST['bellName']))
	{
		$bellName = trim($_POST['bellName']);
		
	}
	
	if(isset($_GET['id']))
	{ 
		if(!empty($_GET['id']))//0 '' false null array() array(array())
		{
			
		$getid = trim($_GET['id']);
		
		
		
		mysql_query("START TRANSACTION");
		$sqls = "SELECT task.taskid FROM task WHERE task.info='$bellName' AND task.info IN(SELECT info FROM task WHERE task.taskid IN($getid))";
		$results = mysql_query($sqls) or die(mysql_error());
	 if(mysql_num_rows($results) > 0)
	 {
	 	@mysql_free_result($results);
	
		unset($sqls);
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	 }
	 else
	 {
	 	@mysql_free_result($results);
	
		unset($sqls);
	 }
		
		
		
		//找到同组作息方案任
		$sql = "SELECT task.taskid FROM task WHERE task.info IN(SELECT info FROM task WHERE task.taskid IN($getid) OR ";
		
		$sql.= "task.sec_task_id IN($getid) and info!='' and channel=0) and info!='' and channel=0 ";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		while($row = mysql_fetch_array($result))
		{   	
		
			
			$sqltask = "INSERT INTO task(taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,startdate,enddate,playtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,defaultvolume,task_user_id,sec_task_id,parentid) (SELECT taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,startdate,enddate,playtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,defaultvolume,task_user_id,sec_task_id,parentid FROM task WHERE task.taskid = '$row[taskid]')";
			
			mysql_query($sqltask);
			
			if(mysql_error())
			{
				@mysql_free_result($result);
			
				unset($row,$sql);
			
				mysql_query("ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./bellmanager.php";
			
				echo "<script>window.location = 'error.php'</script>";
			
				exit;
			}

			$task_max = "SELECT taskid,sec_task_id FROM  task WHERE taskid = (SELECT MAX(taskid) FROM task)";
			
			$task_max_id = mysql_query($task_max) or die(mysql_error());
			$getmaxid=0;

			while($taskid_row = mysql_fetch_array($task_max_id))
			{	
			$getmaxid=$taskid_row[taskid];
				if($taskid_row[sec_task_id]!=0)
			    $task_info = "UPDATE task SET info = '$bellName',sec_task_id='$taskid_row[taskid]'-1 WHERE taskid = '$taskid_row[taskid]'";
				else
				{
				$task_mediatask = "SELECT mediaid,sort FROM  mediaoftask WHERE taskid = '$row[taskid]'";
				$task_mediaoftask = mysql_query($task_mediatask) or die(mysql_error());
				while($taskmedia_row = mysql_fetch_array($task_mediaoftask))
				{
					mysql_query("INSERT INTO mediaoftask (mediaid,taskid,sort) VALUES('$taskmedia_row[mediaid]','$taskid_row[taskid]','$taskmedia_row[sort]')");
				}
				  $task_info = "UPDATE task SET info = '$bellName' WHERE taskid = '$taskid_row[taskid]'";
				}
				mysql_query($task_info) or die(mysql_error());

			}
		
			$sqltermin = "SELECT terminalid,workstate,groupid,area FROM terminaloftask WHERE terminaloftask.taskid = '$row[taskid]' ";
			
			$sqltermin_id = mysql_query($sqltermin) or die(mysql_error());
			
			while($terminal_row1 = mysql_fetch_array($sqltermin_id))
		   	{
			mysql_query("INSERT INTO terminaloftask(taskid,terminalid,workstate,groupid,area) VALUES('$getmaxid','$terminal_row1[terminalid]','$terminal_row1[workstate]','$terminal_row1[groupid]','$terminal_row1[area]')") or die(mysql_error());
			
			}
		}		
		@mysql_free_result($result);
		
		unset($row,$sql);
		
		mysql_query("COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./bellmanager.php";
	
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
			//======================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=6&id=".$getid;			
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}
		echo "<script>window.location.href = 'success.php'</script>";
			
			
		
				
		}
	}

}
//网络电台任务启用
function webradiotaskstart_msg()
{
		require_once("inc/config.inc.php");
	
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//===============================创建套字节==============================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	
	$sql3 = "update task set state=3 where taskid in (".$getValue.") and task.tasktype=10 and task.info='' and task.channel=0 and sec_task_id=0 ";
	
	mysql_query($sql3) or die(mysql_error());
	
	unset($sql3);
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./WebRadio.php";
		
		echo "<script>window.location='./error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./WebRadio.php";
		
		$getidlist = explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "task?state=3&id=".$getid;			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}

		echo "<script>window.location='./success.php'</script>";	
		
		exit;
	}		
}

//采播任务启用
function admtaskstart_msg()
{
	require_once("inc/config.inc.php");  
	
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	
	$get_sql = "UPDATE task SET state=3 WHERE taskid IN ($getValue) AND info = '' AND tasktype = 3 and sec_task_id = 0 and channel = 0 ";
	
	mysql_query($get_sql) or die(mysql_error()); 
	
	unset($get_sql);
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./admmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./admmanager.php";
		
		$getidlist=explode(",",$_GET['id']);
	
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=3&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
//启用文件广播
function start_file_task_msg()
{
	require_once("inc/config.inc.php");
	
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	global $do_php_prompt;
	
	$parentid = 0;
	$getid = trim($_GET['id']);
		$parentid = trim($_GET['taskid']);
	if($getid!=""||$getid!=NULL)
		$result = mysql_query("UPDATE audioserver.task SET projectstate = '0',state = '0' WHERE taskid IN($getid) AND info='' AND sec_task_id=0") ;
     else if($parentid!=""||$parentid!=NULL)
		$result = mysql_query("UPDATE audioserver.task SET projectstate = '0',state = '0' WHERE parentid ='$parentid' AND info='' AND sec_task_id=0");
		
		if($result == FALSE)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$parentid";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			//foreach($task_ids as $task_value)
			//{
			//	$create_socket_obj->send_socket_generate_general("task",1,$task_value);
			//}
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$parentid";
		
			echo "<script>window.location='success.php'</script>";
		}
	
}
//停用文件广播
function stop_file_task_msg()
{
	require_once("inc/config.inc.php");
	
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	global $do_php_prompt;
	
	$parentid = 0;
		$getid = trim($_GET['id']);
		$parentid = trim($_GET['taskid']);
	if($getid!=""||$getid!=NULL)
		$result = mysql_query("UPDATE audioserver.task SET projectstate = '1',state = '0' WHERE taskid IN($getid) AND info='' AND sec_task_id=0") ;
	else if($parentid!=""||$parentid!=NULL)
		$result = mysql_query("UPDATE audioserver.task SET projectstate = '1',state = '0' WHERE parentid ='$parentid' AND info='' AND sec_task_id=0");

		if($result == FALSE)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$parentid";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$parentid";
		
			echo "<script>window.location='success.php'</script>";
		}

}

//采播任务暂停
function admtaskstop_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");  
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	
	$sql = "UPDATE task SET state = 2 where taskid in ($getValue) AND info = '' AND tasktype = 3 and sec_task_id = 0 and channel = 0 ";
	
	mysql_query($sql) or die(mysql_error());
	
	unset($sql);
	 
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./admmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./admmanager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=2&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",2,$getid);
		}
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//网络电台任务暂停
function webradiotaskstop_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");  
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	
	$sql = "UPDATE task SET state = 2 where taskid in ($getValue) AND info = '' AND tasktype = 10 and sec_task_id = 0 and channel = 0 ";
	
	mysql_query($sql) or die(mysql_error());
	
	unset($sql);
	 
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./WebRadio.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./WebRadio.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=2&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",2,$getid);
		}
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//网络电台任务删除
function WebRadiotaskdel_msg()
{
	require_once("inc/config.inc.php");  
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		
		$adm_taskId_array = explode(",",$taskid);
	}
	//添加事务
	mysql_query("START TRANSACTION"); 
	
	for($i=0; $i<count($adm_taskId_array); $i++)
	{	
		//判断是否有功放
		$col_task_sql = "SELECT prepower FROM task WHERE task.taskid='$adm_taskId_array[$i]' AND tasktype=10 AND info='' AND sec_task_id=0 ";
		
		$col_task_result = mysql_query($col_task_sql) or die(mysql_error());
		
		if($col_task_row = mysql_fetch_array($col_task_result))
		{
			if($col_task_row['prepower'] > 0)
			{
				//取采播功放id
				$col_func_sql = "SELECT taskid FROM task WHERE sec_task_id='$adm_taskId_array[$i]' AND tasktype=9 AND info='' AND channel = 0 ";
				
				$col_func_result = mysql_query($col_func_sql) or die(mysql_error());
				
				if($col_func_row = mysql_fetch_array($col_func_result))
				{
					//删除功放任务
					mysql_query("DELETE FROM terminaloftask WHERE taskid = '".$col_func_row[taskid]."'") or die(mysql_error());
					
					//删除功放
					mysql_query("DELETE FROM audioserver.task WHERE taskid = '".$col_func_row[taskid]."'") or die(mysql_error());
				}
				
				@mysql_free_result($col_func_result);
				
				unset($col_func_row,$col_func_sql);
			}
		}
		
		@mysql_free_result($col_task_result);
				
		unset($col_task_row,$col_task_sql);
		
		//删除采播终端
		$col_func1_id = 0;
		//查询采播终端任务
		$col_func1_sql = "SELECT taskid FROM task WHERE sec_task_id = '$adm_taskId_array[$i]' AND tasktype = 9 AND channel = 0 AND info = ''";
		
		$col_func1_result = mysql_query($col_func1_sql) or die(mysql_error());
		
		if($col_func1_row = mysql_fetch_array($col_func1_result))
		{
			$col_func1_id = $col_func1_row['taskid'];
			
			mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$col_func1_id' ") or die(mysql_error());
			
			mysql_query("DELETE FROM audioserver.task WHERE taskid = '$col_func1_id'") or die(mysql_error());
		}
		
		@mysql_free_result($col_func1_result);
				
		unset($col_func1_row,$col_func1_sql,$col_func1_id);
	}

	//删除自己
	mysql_query("DELETE FROM audioserver.task WHERE taskid IN(".$taskid.")") or die(mysql_error());
	//删除终端任务
	mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid IN(".$taskid.")") or die(mysql_error());
	if(!mysql_error())
	{
		mysql_query("COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./WebRadio.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=6&id=".$getid;			
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}

		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysql_query("ROLLBACK");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./WebRadio.php";
		
		echo "<script>window.location='error.php'</script>";
	}	
}
//采播任务删除
function admtaskdel_msg()
{
	require_once("inc/config.inc.php");  
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		
		$adm_taskId_array = explode(",",$taskid);
	}
	//添加事务
	mysql_query("START TRANSACTION"); 
	
	for($i=0; $i<count($adm_taskId_array); $i++)
	{	
		//判断是否有功放
		$col_task_sql = "SELECT prepower FROM task WHERE task.taskid='$adm_taskId_array[$i]' AND tasktype=3 AND info='' AND sec_task_id=0 ";
		
		$col_task_result = mysql_query($col_task_sql) or die(mysql_error());
		
		if($col_task_row = mysql_fetch_array($col_task_result))
		{
			if($col_task_row['prepower'] > 0)
			{
				//取采播功放id
				$col_func_sql = "SELECT taskid FROM task WHERE sec_task_id='$adm_taskId_array[$i]' AND tasktype=9 AND info='' AND channel = 0 ";
				
				$col_func_result = mysql_query($col_func_sql) or die(mysql_error());
				
				if($col_func_row = mysql_fetch_array($col_func_result))
				{
					//删除功放任务
					mysql_query("DELETE FROM terminaloftask WHERE taskid = '".$col_func_row[taskid]."'") or die(mysql_error());
					
					//删除功放
					mysql_query("DELETE FROM audioserver.task WHERE taskid = '".$col_func_row[taskid]."'") or die(mysql_error());
				}
				
				@mysql_free_result($col_func_result);
				
				unset($col_func_row,$col_func_sql);
			}
		}
		
		@mysql_free_result($col_task_result);
				
		unset($col_task_row,$col_task_sql);
		
		//删除采播终端
		$col_func1_id = 0;
		//查询采播终端任务
		$col_func1_sql = "SELECT taskid FROM task WHERE sec_task_id = '$adm_taskId_array[$i]' AND tasktype = 8 AND channel = 0 AND info = ''";
		
		$col_func1_result = mysql_query($col_func1_sql) or die(mysql_error());
		
		if($col_func1_row = mysql_fetch_array($col_func1_result))
		{
			$col_func1_id = $col_func1_row['taskid'];
			
			mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$col_func1_id' ") or die(mysql_error());
			
			mysql_query("DELETE FROM audioserver.task WHERE taskid = '$col_func1_id'") or die(mysql_error());
		}
		
		@mysql_free_result($col_func1_result);
				
		unset($col_func1_row,$col_func1_sql,$col_func1_id);
	}
	
	//删除自己
	mysql_query("DELETE FROM audioserver.task WHERE taskid IN(".$taskid.")") or die(mysql_error());
	//删除终端任务
	mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid IN(".$taskid.")") or die(mysql_error());
	
	if(!mysql_error())
	{
		mysql_query("COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./admmanager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=6&id=".$getid;			
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}

		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysql_query("ROLLBACK");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./admmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
}
//采播音量修改
function admmanagervolumemodify_msg()
{
	require_once("inc/config.inc.php");  
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue=$_GET['id'];
	
	$getVolume=$_GET['volume'];
	
	$tasktype=$_GET['tasktype'];
	
	$taskid=0;
	
	$gotomanagerpage="";
	
	$getID="";
	
	switch($_GET['tasktype'])
	{
		case "admtype":
		$gotomanagerpage="./admmanager.php";
		$taskid=3;
		break;
		case "teltype":
		$gotomanagerpage="./telBroadManager.php";
		$taskid=4;
		break;
		case "termfuncplaytype":
		$gotomanagerpage="./terminalfunctionplay.php";
		$taskid=5;
		break;
	}
	
	if($getValue!="")
	{
		$sql="SELECT DISTINCT terminal.id AS terminalID FROM terminaloftask,terminal,task ";
		
		$sql.="WHERE terminaloftask.terminalid=terminal.id AND task.taskid=terminaloftask.taskid AND task.taskid IN ($getValue)";
		
		$resultID=mysql_query($sql);
		
		while ($row = mysql_fetch_array($resultID,MYSQL_ASSOC)) 
		{
			if($getID=="")
			{
				$getID=$row["terminalID"];
			}
			else
			{
				$getID=$getID.",".$row["terminalID"];
			}
		}
		if($getID=="")
		{
			$sqlmax = "SELECT terminal.id FROM terminal,task,terminaloftask WHERE task.taskid=terminaloftask.taskid AND ";
			
			$sqlmax.= "terminaloftask.terminalid=terminal.id AND task.taskid IN (SELECT MAX(task.taskid) FROM task WHERE task.tasktype='$taskid')";
			
			$result=mysql_query($sqlmax);
			
			$row=mysql_fetch_array($result);
			
			$getID=$row[0];
		}
		
		$sqlVolume="UPDATE terminal SET volume ='$getVolume' WHERE id IN ($getID)";
		
		mysql_query($sqlVolume) or die(mysql_error());
		
		if(mysql_error())
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = $gotomanagerpage;
			
			echo "<script>window.location='error.php'</script>";
		}
	}
	if(!mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = $gotomanagerpage;
	
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=5&id=".$getid."&volume=".$getVolume;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_task_volume("task",5,$getid,$getVolume);
		}
		
		echo "<script>window.location='success.php'</script>";
	}
}
//暂停电话采播任务
function teltaskstop_msg()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php"); 
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	mysql_query("START TRANSACTION");

	$sql = "UPDATE task SET state=2 WHERE taskid IN($getValue) AND task.tasktype = 4 AND task.info = '' AND task.channel = 0 AND sec_task_id=0 ";
	
	mysql_query($sql) or die(mysql_error()); 
	
	if(mysql_error())
	{
		mysql_query("ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./telBroadManager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		mysql_query("COMMIT");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./telBroadManager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=2&id=".$getid;	
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",2,$getid);
		}
	
		echo "<script>window.location='success.php'</script>";	
	}	
}
//执行电话采播任务
function teltaskstart_msg()
{
	require_once("inc/config.inc.php"); 

	//require_once("inc/socket_conf.php");	
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = "";
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	mysql_query("START TRANSACTION");
	
	$sql = "update task set state=3 where taskid in ($getValue) and task.tasktype = 4 and task.info = '' and task.channel = 0 and sec_task_id=0 ";
	
	mysql_query($sql) or die(mysql_error());
	 
	if(mysql_error())
	{
		mysql_query("ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./telBroadManager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		mysql_query("COMMIT ");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./telBroadManager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=3&id=".$getid;			
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}	
		echo "<script>window.location='success.php'</script>";	
	}	
}
//删除电话采播任务
function teltaskdel_msg()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php"); 
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		
		$task_id_array = explode(",",$taskid);
	}
	//启用事务
	mysql_query("START TRANSACTION");
	
	for($i=0; $i<count($task_id_array[$i]); $i++)
	{	
		//判断是否功放
		$tel_task_sql = "SELECT prepower FROM task WHERE taskid='$task_id_array[$i]' AND tasktype=4 AND info='' and sec_task_id=0 ";
		
		$tel_task_result = mysql_query($tel_task_sql) or die(mysql_error());
		
		if($tel_task_row = mysql_fetch_array($tel_task_result))
		{
			if($tel_task_row['prepower'] > 0)
			{
				//查找相关功放
				$tel_func_sql = "SELECT taskid FROM task WHERE task.sec_task_id='$task_id_array[$i]' AND tasktype=9 AND info='' AND channel=0 ";
				
				$tel_func_result = mysql_query($tel_func_sql) or die(mysql_error());
				
				if($tel_func_row = mysql_fetch_array($tel_func_result))
				{
					//删除功放任务
					mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid='".$tel_func_row['taskid']."'") or die(mysql_error());
					//删除功放
					mysql_query("DELETE FROM task WHERE taskid = '".$tel_func_row['taskid']."' AND info='' AND tasktype=9 AND channel=0") or die(mysql_error());
				}
				@mysql_free_result($tel_func_result);
				
				unset($tel_func_sql,$tel_func_row);
			}
		}
	}
	@mysql_free_result($tel_task_result);
				
	unset($tel_task_sql,$tel_task_row);
	//删除自己
	mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid IN (".$taskid.")") or die(mysql_error());
	
	mysql_query("DELETE FROM task WHERE taskid IN(".$taskid.") AND info='' AND tasktype=4 AND channel=0") or die(mysql_error());
	
	if(!mysql_error())
	{
		mysql_query("COMMIT");

		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$getidlist=explode(",",$_REQUEST['id']);

		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=6&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}

		$_SESSION['url'] = "./telBroadManager.php";

		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysql_query("ROLLBACK");

		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./telBroadManager.php";

		echo "<script>window.location='error.php'</script>";
	}
}
//执行终端功放
function terfuncplaystart_msg()
{
	require_once("inc/config.inc.php");  
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}

	mysql_query("START TRANSACTION");

	$sql_task = "UPDATE task SET state = '1' WHERE taskid IN($get_id) AND tasktype=5 AND info=''";
	
	mysql_query($sql_task) or die(mysql_error());
	
	unset($sql_task);
	$sql_task2 = "UPDATE task SET state = '1' WHERE sec_task_id IN($get_id) AND tasktype=5 AND info=''";
	
	mysql_query($sql_task2) or die(mysql_error());
	
	unset($sql_task2);
	
/*	$sql_task = "UPDATE task SET state = '3' WHERE sec_task_id IN($get_id) AND tasktype=5 AND info='' AND channel=0 AND prepower=0 AND bandrate=0 ";
	
	mysql_query($sql_task) or die(mysql_error());
	
	unset($sql_task);*/
	
	if(!mysql_error())
	{
		mysql_query("COMMIT");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=3&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}
		
		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysql_query("ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		echo "<script>window.location='error.php'</script>";
	}	
}
function taskcommandstart_msg()
{
	require_once("inc/config.inc.php");  
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}

	mysql_query("START TRANSACTION");

	$sql_task = "UPDATE task SET state = '3' WHERE taskid IN($get_id) AND tasktype=5 AND info='' AND channel=0 AND prepower=0 AND bandrate=0 ";
	
	mysql_query($sql_task) or die(mysql_error());
	
	unset($sql_task);
	
/*	$sql_task = "UPDATE task SET state = '3' WHERE sec_task_id IN($get_id) AND tasktype=5 AND info='' AND channel=0 AND prepower=0 AND bandrate=0 ";
	
	mysql_query($sql_task) or die(mysql_error());
	
	unset($sql_task);*/
	
	if(!mysql_error())
	{
		mysql_query("COMMIT");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./Browse_system_task.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=3&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}
		
		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysql_query("ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		echo "<script>window.location='error.php'</script>";
	}	
}
//暂停终端功放
function terfuncplaystop_msg()
{
	require_once("inc/config.inc.php"); 
	 
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}
	
	$term_sec_id = "";
	
	mysql_query("START TRANSACTION");

	/*$sql_task = "SELECT taskid FROM audioserver.task WHERE task.sec_task_id IN ($get_id) AND task.tasktype=5 AND task.channel=0";
	
	$result_task = mysql_query($sql_task) or die(mysql_error());
	
	while($row_task = mysql_fetch_array($result_task))
	{
		$term_sec_id[] = $row_task['taskid'];
	}
	@mysql_free_result($result_task);
	
	unset($sql_task,$row_task);*/
	
	$sql_task = "UPDATE task SET state = '0' WHERE sec_task_id IN($get_id) AND tasktype=5 AND info=''";
	
	mysql_query($sql_task) or die(mysql_error());
	
	unset($sql_task);
	$sql_task2 = "UPDATE task SET state = '0' WHERE sec_task_id IN($get_id) AND tasktype=5 AND info=''";
	
	mysql_query($sql_task2) or die(mysql_error());
	
	unset($sql_task2);
	
	if(!mysql_error())
	{
		mysql_query("COMMIT");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";

		$power_off_sql = "SELECT taskid FROM task WHERE sec_task_id IN($get_id) AND tasktype=5 AND info='' AND channel=0 AND prepower=0 AND bandrate=0 ";
		
		$poer_off_result = mysql_query($power_off_sql) or die(mysql_error());
		
		while($power_off_row = mysql_fetch_array($poer_off_result))
		{
			//==================================================
			/*$socket	= new send_message_to_server($port_conf);	
			
			$msg = "task?state=3&id=".$power_off_row['taskid'];			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$power_off_row['taskid']);
		}
		mysql_free_result($poer_off_result);
		
		unset($power_off_sql,$power_off_row);

		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysql_query("ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		echo "<script>window.location='error.php'</script>";
	}	
}
function taskcommandstop_msg()
{
	require_once("inc/config.inc.php"); 
	 
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}
	
	$term_sec_id = "";
	
	mysql_query("START TRANSACTION");

	/*$sql_task = "SELECT taskid FROM audioserver.task WHERE task.sec_task_id IN ($get_id) AND task.tasktype=5 AND task.channel=0";
	
	$result_task = mysql_query($sql_task) or die(mysql_error());
	
	while($row_task = mysql_fetch_array($result_task))
	{
		$term_sec_id[] = $row_task['taskid'];
	}
	@mysql_free_result($result_task);
	
	unset($sql_task,$row_task);*/
	
	$sql_task = "UPDATE task SET state = '3' WHERE sec_task_id IN($get_id) AND tasktype=5 AND info='' AND channel=0 AND prepower=0 AND bandrate=0 ";
	
	mysql_query($sql_task) or die(mysql_error());
	
	unset($sql_task);
	
	if(!mysql_error())
	{
		mysql_query("COMMIT");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./Browse_system_task.php";

		$power_off_sql = "SELECT taskid FROM task WHERE sec_task_id IN($get_id) AND tasktype=5 AND info='' AND channel=0 AND prepower=0 AND bandrate=0 ";
		
		$poer_off_result = mysql_query($power_off_sql) or die(mysql_error());
		
		while($power_off_row = mysql_fetch_array($poer_off_result))
		{
			//==================================================
			/*$socket	= new send_message_to_server($port_conf);	
			
			$msg = "task?state=3&id=".$power_off_row['taskid'];			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$power_off_row['taskid']);
		}
		mysql_free_result($poer_off_result);
		
		unset($power_off_sql,$power_off_row);

		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysql_query("ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		echo "<script>window.location='error.php'</script>";
	}	
}
//重启服务器
function restart_server_msg()
{
	require_once("inc/config.inc.php"); 

	//require_once("inc/socket_conf.php");
	//====================导入外部数据
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	//===================================================
	/*$socket = new send_message_to_server($port_conf);
	
	$strbuff = "server?state=1";
	
	$socket->send_data($_SESSION['serverip'],$strbuff);
	*/
	$create_socket_obj->send_socket_restart("server",1);
	
	echo "<script>alert('".$do_php_prompt['The_system_is_restarting']."');</script>";
	
	echo "<script>window.history.back();</script>";

	//exit;
}
//删除终端功放
function terfuncplaydel_msg()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
		
		$get_id_array = explode(",",$get_id);
	}
	mysql_query("START TRANSACTION");
	
	for($i=0; $i < count($get_id_array); $i++)
	{
		$func_task_sql = "SELECT taskid FROM task WHERE tasktype = 5 AND info = '' AND sec_task_id='".$get_id_array[$i]."' AND channel = 0 AND bandrate=0";
		
		$func_task_result = mysql_query($func_task_sql) or die($func_task_sql);
		
		if($func_task_row = mysql_fetch_array($func_task_result))
		{
			//删除功放终端任务
			mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid='".$func_task_row['taskid']."'") or die(mysql_error());
			
			//删除次要功放
			mysql_query("DELETE FROM task WHERE task.taskid = '".$func_task_row['taskid']."'") or die(mysql_error());
		}
	}
	
	@mysql_free_result($func_task_result);
		
	unset($func_task_sql,$func_task_row);
	
	mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid IN ($get_id)") or die(mysql_error());
	
	mysql_query("DELETE FROM task WHERE task.taskid IN($get_id)") or die(mysql_error());
	
	if(!mysql_error())
	{
		mysql_query("COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=6&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}
		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysql_query("ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		echo "<script>window.location='error.php'</script>";
	}
}
function taskcommanddel_msg()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
		
		$get_id_array = explode(",",$get_id);
	}
	mysql_query("START TRANSACTION");
	
	for($i=0; $i < count($get_id_array); $i++)
	{
		$func_task_sql = "SELECT taskid FROM task WHERE tasktype = 5 AND info = '' AND sec_task_id='".$get_id_array[$i]."' AND channel = 0 AND bandrate=0";
		
		$func_task_result = mysql_query($func_task_sql) or die($func_task_sql);
		
		if($func_task_row = mysql_fetch_array($func_task_result))
		{
			//删除功放终端任务
			mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid='".$func_task_row['taskid']."'") or die(mysql_error());
			
			//删除次要功放
			mysql_query("DELETE FROM task WHERE task.taskid = '".$func_task_row['taskid']."'") or die(mysql_error());
		}
	}
	
	@mysql_free_result($func_task_result);
		
	unset($func_task_sql,$func_task_row);
	
	mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid IN ($get_id)") or die(mysql_error());
	
	mysql_query("DELETE FROM task WHERE task.taskid IN($get_id)") or die(mysql_error());
	
	if(!mysql_error())
	{
		mysql_query("COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./Browse_system_task.php";
		
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=6&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}
		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysql_query("ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		echo "<script>window.location='error.php'</script>";
	}
}
//添加终端---没有被使用到（采用终端自动添加）
function addterminal_msg()
{
	require_once("inc/config.inc.php");  	
	//添加外部变量
	global $do_php_prompt;
	
	$outputformat=$_POST['outputformat'];
	$audioCodec=$_POST['AudioCodec'];
	$audioChannels=$_POST['AudioChannels'];
	$audioQuality=$_POST['AudioQuality'];
	$audioBitRate=$_POST['AudioBitRate'];
	$audioSampleRate=$_POST['AudioSampleRate'];
	$terminalgetid=$_POST['terminalgetid'];
	$sql="UPDATE terminal SET sample = '$audioSampleRate' , bitrate = '$audioBitRate' ,channel = '$audioChannels' , audioquality = '$audioQuality' ,";
	$sql.=" audiocodec = '$audioCodec' , outformat = '$outputformat' WHERE id IN ($terminalgetid)";
	$result=mysql_query($sql);
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		echo "<script>window.location='success.php'</script>";	
	}
}
//紧急呼叫设置
function emergency_setting()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
	}
	$gettask = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettask = trim($_GET['gettask']);
	}
	//查找数据库
	$emg_result = mysql_query("SELECT * FROM task WHERE task.tasktype = 7");
	
	if( mysql_num_rows($emg_result) > 0)
	{
		@mysql_free_result($emg_result);
		
		echo "<script>alert('".$do_php_prompt['Existing_Tasks_Cancel']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
	}
	else
	{
		mysql_query("UPDATE audioserver.task SET tasktype = '7' WHERE taskid = '$taskid'");	
		
		@mysql_free_result($emg_result);
		
		if(!mysql_error())
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$gettask";
			
			echo "<script>window.location='success.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$gettask";
			
			echo "<script>window.location='error.php'</script>";
		}
	}
}
//紧急呼叫取消
function emergency_canceling()
{
	require_once("inc/config.inc.php");
	//添加外部变量
	global $do_php_prompt;
	$gettask = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettask = trim($_GET['gettask']);
	}
	//===========================创建对象===================================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$emg_result = mysql_query("SELECT * FROM task WHERE task.tasktype = 7");
	
	if(mysql_num_rows($emg_result) > 0)
	{
		mysql_query("UPDATE audioserver.task SET tasktype = '2' WHERE task.tasktype = '7'");
		
		@mysql_free_result($emg_result);
		
		if(mysql_error())
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$gettask";
			
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$gettask";
			
			echo "<script>window.location='success.php'</script>";
		}
	}
	else
	{
		@mysql_free_result($emg_result);
		//=========================================================================
		/*echo "<script>alert('".$do_php_prompt['No_Emergency_Task']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['No_Emergency_Task']);
	}
}
//修改终端声音---没有被使用到
function modifyterminalvolume_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = $_GET['id'];
	
	$volume = $_GET['volume'];
	
	$sql = "UPDATE terminal SET volume='$volume' WHERE id IN ($getid)";
	
	mysql_query($sql) or die(mysql_error());
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息

		$_SESSION['url'] = "./terminalmanager.php";

		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "terminal?state=5&id=".$getid."&volume=".$volume;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_task_volume("terminal",5,$getid,$volume);
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
//产生xml形成树---没使用到
function inputterminaltofile()
{
	
	$str = "<?xml version='1.0' encoding='UTF-8'?> <tree id=\"0\">";
	$fp = fopen("smarty/templates/BellManager/codebase/tree4.xml","w");
	fwrite($fp,$str);
	fwrite($fp,"\n");
	
	$streamresult=mysql_query("SELECT DISTINCT serverplaystream.streamid,serverplaystream.name FROM serverplaystream");
	while ($streamrow = mysql_fetch_array($streamresult))
	{			
		$streamid = $streamrow['streamid'];
		$str = "<item text=\"".$streamrow['name']."\" id=\"dir_".$streamid."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >";
		fwrite($fp,$str);
		fwrite($fp,"\n");
		
		$terminalresult=mysql_query("SELECT DISTINCT terminal.id,terminal.terminalname FROM terminal WHERE	terminal.groupid=$streamid");
	while ($terminalrow = mysql_fetch_array($terminalresult)) 
		{	
			$str = "<item text=\"".$terminalrow['terminalname']."\" id=\""."$terminalrow[id]"."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >\n</item>\n"	;
			fwrite($fp,$str);		  
		}							 
	fwrite($fp,"</item>\n");			
	}		
	fwrite($fp,"</tree>\n");		
	
	fclose($fp);
}
//注册服务器
function regist_server()
{
	require_once("inc/config.inc.php");	
	$create_socket_obj = new create_socket_class();
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;

	$command = "";

	$output_info = array();
	//取注册码
	$license_key = "";
	
	if(isset($_POST['license_key']))
	{
		$license_key = trim($_POST['license_key']);
	}
	//取机器码
	$machine_code = "";
	
	if(isset($_POST['machine_code']))
	{
		$machine_code = trim($_POST['machine_code']);
	}
	//执行命令

	$command = "sudo /bin/registerserver ".$license_key."";
	
	//注册服务器
	@exec($command,$output_info);
		//$create_socket_obj->send_socket_generate_general("register",1,$command);
	if(trim($output_info[0]) != "SUCCESS")
	{	
		echo "<script>alert('".$do_php_prompt['reg_fail_p_check']."');</script>";
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./regist_server.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else if(trim($output_info[0]) == "SUCCESS")
	{
		echo "<script>alert('".$do_php_prompt['reg_succ_re_server']."');</script>";

		//修改数据
		mysql_query("UPDATE audioserver.serverbaseparam SET registerflag = '1' WHERE id = '1'") or die(mysql_error());
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息

		$_SESSION['url'] = "./login.php";
		
		echo "<script>window.location='success.php'</script>";	
	}	
		
}
function modifycallzone_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();

	$terminalid = "";
	if(isset($_GET['terminalid']))
	{
		$terminalid = trim($_GET['terminalid']);
	}
		$callbackid = "";
	if(isset($_GET['callbackid']))
	{
		$callbackid = trim($_GET['callbackid']);
	}
	$shotcutname = "";
	if(isset($_POST['shotcutname']))
	{
		$shotcutname = trim($_POST['shotcutname']);
	}

	$keyvalue = "";
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}
	$target = "";
	if(isset($_POST['target']))
	{
		$target = trim($_POST['target']);
	}

	  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}

		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
		$get_noids=1;
	if(isset($_POST['get_noid']))
	{
	  $get_noids = trim($_POST['get_noid']);
  
	  $arr = array(',' =>'');
	  $get_noids =strtr($get_noids,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}

	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	
	$targetArr=explode(",",$target);
	
	for($i=0;$i<count($targetArr);$i++)
	{
		if(is_numeric($targetArr[$i]))
		{
			$newtarget[]=(int)$targetArr[$i];
		}
		continue;
	}
	mysql_query("LOCK TABLE callgroup WRITE,terminalofcallgroup WRITE");
	
	mysql_query("START TRANSACTION");
	//查找是否有相同的设置
		$key_sql = "SELECT callgroup.id,callgroup.terminalid,callgroup.name FROM callgroup WHERE callgroup.id='$callbackid' ";
	
	$key_result = mysql_query($key_sql) or die(mysql_error());
	
	if(mysql_num_rows($key_result) > 0)
	{
		//先删除再添加
		$key_row = mysql_fetch_array($key_result);
		
		$key_id = $key_row['id'];
		$key_terminalid = $key_row['terminalid'];
	
			mysql_query("UPDATE callgroup SET callgroup.name = '$shotcutname',terminalid='$key_terminalid' WHERE callgroup.id = '$key_id'");
			for($i=0;$i<count($newtarget);$i++)
			{
			$key_sqlssub = "SELECT id FROM terminalofcallgroup WHERE terminalofcallgroup.selectgroupid='$key_id' AND terminalid='$newtarget[$i]'";
					$key_resultssub = mysql_query($key_sqlssub) or die(mysql_error());
					if(mysql_num_rows($key_resultssub) <= 0)
					{
						mysql_query("INSERT INTO terminalofcallgroup (selectgroupid,terminalid) VALUES('$key_id','".$newtarget[$i]."')") or die(mysql_error());
					
					}	
			}
			for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						$getids=substr($getid,3);
						
						mysql_query("DELETE FROM terminalofcallgroup WHERE terminalofcallgroup.selectgroupid = 
'$key_id' AND terminalid='$getids'") or die(mysql_error());
						}
						
						}	

	
		if(mysql_error())
				{
					mysql_query("ROLLBACK");
				
					$forward_ok_error_obj->exit_back_function($do_php_prompt

['Failed']);
				}	
	
	
		
	}
		for($k=0;$k<strlen($get_terminal);$k++)
		{
				if(substr($get_terminal,$k,2)=="::")
									{
									$position=$k+2;
									
									}
						if(substr($get_terminal,$k,1)=="|")
						{
						  $position2 = $k;
						  $position3 = $position2-$position;
									
									$a=substr($get_terminal,$k-

$position3,$position3);
									for($i=0; $i<count

($newtarget); $i++)
									{
										if($a==$newtarget[$i])
										{
											$area = 

substr($get_terminal,$k+1,16);
											$sql = "UPDATE 

terminalofcallgroup SET area='$area' WHERE selectgroupid ='$key_id' AND terminalid ='$newtarget[$i]'";
											mysql_query

($sql) or die(mysql_error());
											unset($sql);
										}
									}
								
						}		
			
		}
		
			if(!mysql_error())
			{
				mysql_query("COMMIT");
				
				mysql_query("UNLOCK TABLES");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = "./view_terminal_call_group.php";
			
				$create_socket_obj->send_socket_shotcut("terminal",$terminalid,

$keyvalue);
				
				echo "<script>window.location='success.php'</script>";
			}	
}
function addcallzone_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();

	$terminalid = "";
	if(isset($_GET['terminalid']))
	{
		$terminalid = trim($_GET['terminalid']);
	}
		$callbackid = "";
	if(isset($_GET['callbackid']))
	{
		$callbackid = trim($_GET['callbackid']);
	}
	$shotcutname = "";
	if(isset($_POST['shotcutname']))
	{
		$shotcutname = trim($_POST['shotcutname']);
	}
	
	$keyvalue = "";
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}
	$target = "";
	if(isset($_POST['target']))
	{
		$target = trim($_POST['target']);
	}

	  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	 
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}

		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}

	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	

	$targetArr=explode(",",$target);
	
	for($i=0;$i<count($targetArr);$i++)
	{

		if(is_numeric($targetArr[$i]))
		{
			
			$newtarget[]=(int)$targetArr[$i];
		}
		continue;
	}
	
	mysql_query("LOCK TABLE callgroup WRITE,terminalofcallgroup WRITE");
	
	mysql_query("START TRANSACTION");
	//查找是否有相同的设置
		$key_sql = "SELECT callgroup.id FROM callgroup WHERE callgroup.terminalid='$callbackid' AND callgroup.name='$shotcutname'";
	
	$key_result = mysql_query($key_sql) or die(mysql_error());
	
	if(mysql_num_rows($key_result) <= 0)
	{
		//直接插入
		$sql_key = "INSERT INTO callgroup (callgroup.name, terminalid)VALUES

('$shotcutname','$callbackid')";

		mysql_query($sql_key) or die(mysql_error());
		
		if(mysql_error())
		{
			mysql_query("ROLLBACK");
			//===========================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt

['Failed'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
		}
		unset($sql_key);
		
		$sql_result = mysql_query("SELECT MAX(id) FROM callgroup ") or die(mysql_error());
		
		if($sql_row = mysql_fetch_array($sql_result))
		{
			$key_id = $sql_row[0];
		}
		@mysql_free_result($sql_result);
		
		unset($sql_row);
		
		for($i=0; $i<count($newtarget); $i++)
		{
			mysql_query("INSERT INTO terminalofcallgroup (selectgroupid, terminalid) 

VALUES('$key_id','".$newtarget[$i]."')") or die(mysql_error());
			
			if(mysql_error())
			{
				mysql_query("ROLLBACK");
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
			}
		}
	}
	else
	{
		//先删除再添加
		$key_row = mysql_fetch_array($key_result);
		
		$key_id = $key_row['id'];
		
		mysql_query("DELETE FROM terminalofcallgroup WHERE terminalofcallgroup.selectgroupid = 

'$key_id'") or die(mysql_error());
		
		if(mysql_error())
		{
			mysql_query("ROLLBACK");
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
		}
		else
		{
			mysql_query("UPDATE callgroup SET callgroup.name = '$shotcutname' WHERE 

callgroup.id = '$key_id'");
		
			for($i=0; $i<count($newtarget); $i++)
			{
				mysql_query("INSERT INTO terminalofcallgroup (selectgroupid, 

terminalid) VALUES('$key_id','".$newtarget[$i]."')") or die(mysql_error());
		
				if(mysql_error())
				{
					mysql_query("ROLLBACK");
				
					$forward_ok_error_obj->exit_back_function($do_php_prompt

['Failed']);
				}
			}
	
		}
	}
		for($k=0;$k<strlen($get_terminal);$k++)
		{
				if(substr($get_terminal,$k,2)=="::")
									{
									$position=$k+2;
									
									}
						if(substr($get_terminal,$k,1)=="|")
						{
						  $position2 = $k;
						  $position3 = $position2-$position;
									
									$a=substr($get_terminal,$k-

$position3,$position3);
									for($i=0; $i<count

($newtarget); $i++)
									{
										if($a==$newtarget[$i])
										{
											$area = 

substr($get_terminal,$k+1,16);
											$sql = "UPDATE 

terminalofcallgroup SET area='$area' WHERE selectgroupid ='$key_id' AND terminalid ='$newtarget[$i]'";
											mysql_query

($sql) or die(mysql_error());
											unset($sql);
										}
									}
								
						}		
			
		}
		
			if(!mysql_error())
			{
				mysql_query("COMMIT");
				
				mysql_query("UNLOCK TABLES");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = "./view_terminal_call_group.php";
			
				$create_socket_obj->send_socket_shotcut("terminal",$terminalid,

$keyvalue);
				
				echo "<script>window.location='success.php'</script>";
			}
}

//添加快捷键---快捷寻呼
function addshotcutkey_msg()
{
	require_once("inc/config.inc.php");
	
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$terminalid = "";
	if(isset($_GET['terminalid']))
	{
		$terminalid = trim($_GET['terminalid']);
	}
	
	$shotcutname = "";
	if(isset($_POST['shotcutname']))
	{
		$shotcutname = trim($_POST['shotcutname']);
	}
	
	$keyvalue = "";
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}
	$target = "";
	if(isset($_POST['target']))
	{
		$target = trim($_POST['target']);
	}

	  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	 
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}

		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}

	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	
	$targetArr=explode(",",$target);
	
	for($i=0;$i<count($targetArr);$i++)
	{
		if(is_numeric($targetArr[$i]))
		{
			$newtarget[]=(int)$targetArr[$i];
		}
		continue;
	}
	
	mysql_query("LOCK TABLE terminalkey WRITE,terminalkeymap WRITE");
	
	mysql_query("START TRANSACTION");
	//查找是否有相同的设置
	$key_sql = "SELECT terminalkey.id FROM terminalkey WHERE terminalkey.terminalid='$terminalid' AND terminalkey.key = '$keyvalue'";
	
	$key_result = mysql_query($key_sql) or die(mysql_error());
	
	if(mysql_num_rows($key_result) <= 0)
	{
		//直接插入
		$sql_key = "INSERT INTO terminalkey (terminalkey.name, terminalid, terminalkey.key)VALUES('$shotcutname','$terminalid','$keyvalue')";

		mysql_query($sql_key) or die(mysql_error());
		
		if(mysql_error())
		{
			mysql_query("ROLLBACK");
			//===========================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['Failed'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
		}
		unset($sql_key);
		
		$sql_result = mysql_query("SELECT MAX(id) FROM terminalkey ") or die(mysql_error());
		
		if($sql_row = mysql_fetch_array($sql_result))
		{
			$key_id = $sql_row[0];
		}
		@mysql_free_result($sql_result);
		
		unset($sql_row);
		
		for($i=0; $i<count($newtarget); $i++)
		{
			mysql_query("INSERT INTO terminalkeymap (keyid, terminalid) VALUES('$key_id','".$newtarget[$i]."')") or die(mysql_error());
			
			if(mysql_error())
			{
				mysql_query("ROLLBACK");
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
			}
		}
	}
	else
	{
		//先删除再添加
		$key_row = mysql_fetch_array($key_result);
		
		$key_id = $key_row['id'];
		
		mysql_query("DELETE FROM terminalkeymap WHERE terminalkeymap.keyid = '$key_id'") or die(mysql_error());
		
		if(mysql_error())
		{
			mysql_query("ROLLBACK");
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
		}
		else
		{
			mysql_query("UPDATE terminalkey SET terminalkey.name = '$shotcutname' WHERE terminalkey.id = '$key_id'");
		
			for($i=0; $i<count($newtarget); $i++)
			{
				mysql_query("INSERT INTO terminalkeymap (keyid, terminalid) VALUES('$key_id','".$newtarget[$i]."')") or die(mysql_error());
		
				if(mysql_error())
				{
					mysql_query("ROLLBACK");
				
					$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
				}
			}
	
		}
	}
		for($k=0;$k<strlen($get_terminal);$k++)
		{
				if(substr($get_terminal,$k,2)=="::")
									{
									$position=$k+2;
									
									}
						if(substr($get_terminal,$k,1)=="|")
						{
						  $position2 = $k;
						  $position3 = $position2-$position;
									
									$a=substr($get_terminal,$k-$position3,$position3);
									for($i=0; $i<count($newtarget); $i++)
									{
										if($a==$newtarget[$i])
										{
											$area = substr($get_terminal,$k+1,16);
											$sql = "UPDATE terminalkeymap SET area='$area' WHERE keyid ='$key_id' AND terminalid ='$newtarget[$i]'";
											mysql_query($sql) or die(mysql_error());
											unset($sql);
										}
									}
								
						}		
			
		}
		
			if(!mysql_error())
			{
				mysql_query("COMMIT");
				
				mysql_query("UNLOCK TABLES");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = "./terminalmanager.php";
			
				$create_socket_obj->send_socket_shotcut("terminal",$terminalid,$keyvalue);
				
				echo "<script>window.location='success.php'</script>";
			}
}
//回复留言---没有被使用到
function reply_msg()
{
	require_once("inc/config.inc.php");
	//=======================添加外部变量
	global $do_php_prompt;
	
	$time = gmdate("Y-m-d H:i:s",time()+8*3600);
	
	$result = mysql_query("SELECT * FROM `".$DB_PREFIX."reply` WHERE m_id='$_GET[id]' ");
	if($row = mysql_fetch_array($result))
	{
		mysql_query("UPDATE  `".$DB_PREFIX."reply` SET `content`='$_POST[description]',`time`='$time' WHERE m_id='$_GET[id]'");	
	}
	else
	{
		mysql_query("INSERT INTO `".$DB_PREFIX."reply` (`m_id`,`content`,`time`) VALUES ('$_GET[id]','$_POST[description]','$time')");	
	}
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./";
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//删除留言和回复---没有被使用到
function del_msg()
{
	require_once("inc/config.inc.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	mysql_query("DELETE FROM `".$DB_PREFIX."msg` WHERE id='$_GET[id]'");
	
	@mysql_query("DELETE FROM `".$DB_PREFIX."reply` WHERE m_id='$_GET[id]'");
	
	if(mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./";
	
		echo "<script>window.location='success.php'</script>";	
	}
}
//修改用户密码---没有被使用到
function pwd()
{
	require_once("inc/config.inc.php");
	//添加外部变量
	global $do_php_prompt;
	
	$userpwd = md5($_POST['userpwd']);
	
	$result = mysql_query("SELECT * FROM `".$DB_PREFIX."admin` WHERE userpwd='$userpwd'");
	
	if($row = mysql_fetch_array($result))
	{
		$newpwd = md5($_POST['newpwd']);
		
		mysql_query("UPDATE `book_admin` SET `userpwd`='$newpwd' WHERE id=$_GET[id]");
		
		if(mysql_error())
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "pwd.php";
			
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			echo "<script>alert('".strtoupper($do_php_prompt['relogin_modified_successfully'])."');</script>";	//提示信息
			
			echo "<script>window.location='do.php?act=logout'</script>";
		}
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "pwd.php";
		
		echo "<script>window.location='error.php'</script>";
	}
}



////////////////////////////////////////////////////////////////////////////////////
/*
function modify_col_term_prepower(coll_prepower,coll_taskid)
{
	$col_prepower_sql = "UPDATE audioserver.task SET taskname = 'taskname' , timelength = 'timelength' , prepower = 'prepower' ,"; 

	$col_prepower_sql.= "datasendmodel = 'datasendmodel' , state = '0' ,startdate = 'startdate' , enddate = 'enddate' ,";

	$col_prepower_sql.= "playtime = 'playtime' , exemodel = 'exemodel' , channel = 'channel' ,"; 

	$col_prepower_sql.= "bandrate = 'bandrate' , samplerate = 'samplerate' ,"; 

	$col_prepower_sql.= "cmd = 'cmd' , cmdargs = '10' , defaultvolume = 'defaultvolume' , WHERE taskid = 'taskid' ";
	
	
}
*/
//非法注册服务
function invalid_regist_service()
{
	require_once("inc/config.inc.php");
	//添加外部变量
	global $do_php_prompt;
	
	$flag_var = 0;
	
	$regist_sql = "SELECT registerflag FROM audioserver.serverbaseparam WHERE registerflag = 1 or registerflag=2";
	
	$regist_result = mysql_query($regist_sql) or die(mysql_error());
	
	if($regist_row = mysql_fetch_array($regist_result))
	{
		$flag_var = 1;
	}
	else
	{
		$flag_var = 0;
		
		echo "<script>alert('".$do_php_prompt['server_not_registered']."');</script>";
	}
	@mysql_free_result($regist_result);
	
	unset($regist_sql,$regist_row);
	
	ob_flush();
	
	return $flag_var;
}
function commandtask_msg()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	
	$taskname = "";
	
	$sec_task_id = 0;
	
	$cmd = 0;
	
	$cmdargs = 0;
	
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay = 0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = trim((int)$_POST['israndomplay']);
	}  
	$timelengthtype = 1;
	
	$timelength = 0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
		
		if($timelengthtype == 1)
		{  
			$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
		}
		else
		{
			$timelength = trim($_POST['circleTime']);
		} 
	}
	else
	{
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
	}
	
	$datasendmodel = 0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	$state = 0;
	
	$startdate="";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	
	if(empty($_POST['startdate']))
	{
		$startdate = "00-00-00";
	}
	
	$enddate="";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	
	if(empty($_POST['enddate']))
	{
		$enddate = "00-00-00";
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = trim($_POST['playtime']);
	}
	
	if(empty($_POST['playtime']))
	{
		$playtime = "00:00:00";
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
		
		if($prepower!=0)
		{
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$prepower."minutes -0 seconds"));
		}
	}
	//获取声音
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	 
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = trim($_POST['exemodel']);
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = trim($_POST['hiddenweek']);
			
			$repl = array(',' => '');
			
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			
			$playtime = "00:00:00";
		}
	}
	
	if(empty($_POST['exemodel']))
	{
		$exemodel = "1111111";
	}
	$system_task=0;
	$system_command=0;
	$system_param=0;
	if(isset($_POST['systemcommand']))
	{
		$system_task=trim($_POST['systemcommand']);
		if($system_task == 12)
		{
			$system_command = trim($_POST['taskcommand']);
		}
		else if($system_task == 13)
		{
			$system_param = trim($_POST['parameters']);	
		}
	}

	//获取任务优先级
	$priority=3;
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$tasktype=0;
	
	$audiosource=0;
	
	if(isset($_POST['audiosource']))
	{
		$audiosource = trim($_POST['audiosource']);
		
		$cmd = $audiosource;
		
		$audiosource = 0;
	}
	
	$channel = 0;
	
	if(isset($_POST['channel']))
	{
		$channel = trim($_POST['channel']);
		
		$cmdargs = $channel;
		
		$channel = 0;
	}
	
	$bandrate = 0;
	
	if(isset($_POST['bandrate']))
	{
		$bandrate = trim($_POST['bandrate']);
	}
	
	$samplerate=0;
	if(isset($_POST['samplerate']))
	{
		$samplerate = trim($_POST['samplerate']);
	}
		$terminallistvalue = trim($_POST['terminallistvalue']);
		
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	
	
	
	$playfileid = 0;
	
	$gototaskmanager = "";
	
	$openpower = 0;
	
	$openpowertaskid = 0;
	
	switch($_POST['taskType'])
	{
		
		case "systemtaskcommand":
		
			$tasktype = 5;
		
			$cmd = 0;
		
			$gototaskmanager="./Browse_system_task.php";
		
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			
		break;
	}
	/*************************
		区分任务类型
		同一任务中不允许同名
	**************************/
	if($tasktype == 5)
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '5' ";
		
		$sql_same_name.= "AND prepower = '0' AND tasktype = 5 AND channel = 0 AND info = '' AND sec_task_id = 0 ";
		
		$result_same_name = mysql_query($sql_same_name) or die(mysql_error());
		
		if(mysql_num_rows($result_same_name) > 0)
		{
			//============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
		
			exit;*/
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	else
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype' ";
		
		$result_same_name = mysql_query($sql_same_name) or die(mysql_error());
		
		if(mysql_num_rows($result_same_name) > 0)
		{
			//===========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	@mysql_free_result($result_same_name);
	
	unset($sql_same_name);
		
	//获取用户优先级
	
	$sql = "SELECT book_admin.id,usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	$row = mysql_fetch_array($result);
	
	//设置优先级
	$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	
	@mysql_free_result($result);
	
	unset($sql,$row);
	
	//加锁并启用事务
	mysql_query("START TRANSACTION");//获取不到插入的值
	
	mysql_query("LOCK TABLES task WRITE,terminaloftask WRITE,mediaoftask WRITE,task READ,terminaloftask READ,mediaoftask READ");
		
	if($tasktype !=1)
	{
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,playtime, ";
		
		$sql.="exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume,task_user_id, sec_task_id) ";
		
		$sql.="VALUES('$taskname', '$israndomplay', '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate', '$playtime', '$exemodel', '$priority', '$system_task', '$channel', ";
		
		$sql.="'$bandrate', '$samplerate', '$system_command', '$system_param', '$playfileid', '$task_default_volume', '$task_user_id', $sec_task_id) ";

		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
		
		if(mysql_error())
		{
			mysql_query("ROLLBACK");
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = $gototaskmanager;
			
			echo "<script>window.location='error.php'</script>";
			
			exit;
		}

		$sql = "SELECT MAX(taskid) FROM task";//取插入任务id
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			$gettaskid = $row[0];//新添加的任务id
		}
		
		@mysql_free_result($result);
		
		unset($sql,$row);
		
		
		if(($prepower != 0)||($tasktype==5))
		{						
			if($tasktype == 5)
			{
			
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
				
				$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
				
				$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id) VALUES('$taskname', '$israndomplay', ";
				
				$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
				
				$sql.="'$preopenpowertime', '$exemodel', '$priority', '5', '0', '$bandrate', '$samplerate', ";
				
				$sql.="'$system_command', '$system_param', '$playfileid', '$task_default_volume','$task_user_id', '$gettaskid') ";
			}
			else
			{
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
				
				$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
				
				$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id) VALUES('$taskname', '$israndomplay', ";
				
				$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
				
				$sql.="'$preopenpowertime', '$exemodel', '$priority', '9', '0', '$bandrate', '$samplerate', ";
				
				$sql.="'$system_command', '$system_param', '$playfileid', '$task_default_volume','$task_user_id', '$gettaskid') ";
			}
			mysql_query($sql) or die(mysql_error());
			
			unset($sql);
			
			if(mysql_error())
			{
				mysql_query("ROLLBACK");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
			
			//取得功放任务id $openpowertaskid
			
			$resultpower = mysql_query("SELECT MAX(taskid) FROM task") or die(mysql_error());
			  
			$rowpower2 = mysql_fetch_array($resultpower);	
			  
			$openpowertaskid = $rowpower2[0]; 
			  
			@mysql_free_result($resultpower);
			
			unset($rowpower2);
		}

	//for($i=0; $i<count($terminallistnum); $i++)
	//	{
			//if(is_numeric($terminallistnum[$i]))
		//	{
				//$temp = (int)$terminallistnum[$i];
				//插入终端任务关联
				//$sql="insert into terminaloftask (taskid,terminalid) values('$gettaskid','$temp')";
	  
				
	for($i=0; $i<count($terminallistnum); $i++)
		{
			if(is_numeric($terminallistnum[$i]))
			{
				$temp = (int)$terminallistnum[$i];
				//插入终端任务关联
				//$sql="insert into terminaloftask (taskid,terminalid) values('$gettaskid','$temp')";
	          
				
				$c =strlen($temp);
				 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$gettaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
				
					mysql_query($sql) or die(mysql_error());
					
					if(mysql_error())
					{
						mysql_query("ROLLBACK");
					
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = "./bellmanager.php";
						
						echo "<script>window.location='error.php'</script>";
						
						exit;
					}
					
					if(($prepower != 0)||($tasktype==5))
					{
						//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$openpowertaskid','$temp')";
						
						$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$openpowertaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
						
						mysql_query($sql) or die(mysql_error());	
						
						if(mysql_error())
						{
							mysql_query("ROLLBACK");
							
							$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
							
							$_SESSION['url'] = $gototaskmanager;
						
							echo "<script>window.location='error.php'</script>";
						
							exit;
						}		
					}
			  
				//echo "$b";
				//echo"$c";
				//echo($a);
				
				
				
				
				for($j=0;$j<strlen($get_terminal);$j++)
				{
				
				if(substr($get_terminal,$j,2)=="::")
									{
									$position=$j+2;
									
									}
						if(substr($get_terminal,$j,1)=="|")
						{
						  $position2 = $j;
						  $position3 = $position2-$position;
									
									$a=substr($get_terminal,$j-$position3,$position3);
									
									if($a==$temp)
										{
									
										$area = substr($get_terminal,$j+1,16);
									
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$gettaskid' AND terminalid ='$temp'";
										mysql_query($sql) or die(mysql_error());
										unset($sql);
										if(($prepower != 0)||($tasktype==5))
										{
										$area = substr($get_terminal,$j+1,16);
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$openpowertaskid' AND terminalid ='$temp'";
										mysql_query($sql) or die(mysql_error());
										unset($sql);
										}
										
										}
						}			
									
									
									
									
				 }

				}
				}

						
		
						
						
		
	}

	if($tasktype==2 || $tasktype==7)
	{
		if(isset($_POST['listvalue']))
		{
			$medialist=trim($_POST['listvalue']);
			
			$arrmedia=explode(",",$medialist);
			
			for($i=0;$i<count($arrmedia);$i++)
			{
				$str =$arrmedia[$i];
			
				if(!is_numeric($str))
				{
					continue;
				}
				
				$number =(int)$str;
			
				$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$number','$gettaskid','$i')";
			
				mysql_query($sql) or die(mysql_error());
				
				if(mysql_error())
				{	
					mysql_query("ROLLBACK");
				
					$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
					
					$_SESSION['url'] = $gototaskmanager;
					
					echo "<script>window.location='error.php'</script>";
					
					exit;
				}			
			}	
		}
	}
	
	mysql_query("UNLOCK TABLES");
	mysql_query("COMMIT");
	if(!mysql_error())
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
		//===================================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=4&id=".$gettaskid."&volume=".$task_default_volume;			
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_task_volume("task",4,$gettaskid,$task_default_volume);
		
		echo "<script>window.location='success.php'</script>";
	}		

}
function modifysystem_msg()
{
	require_once("inc/config.inc.php"); 
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	
	$sec_task_id = 0;
	
	$cmd = 0;
	
	$cmdargs = 0;
	
	$taskname="";
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay=0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = $_POST['israndomplay'];
	}
	 $get_noid=1;
	if(isset($_POST['get_noid']))
	{
	   $get_noids = trim($_POST['get_noid']);
  
	  $arr = array(',' =>'');
	  $get_noids =strtr($get_noids,$arr);
	  
	}
	
	$timelengthtype=1;
	
	$timelength=0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
	
		if($timelengthtype == 1)
		{  
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
		}
		else
		{
			$timelength = trim($_POST['circleTime']);
		} 
	}
	else
	{
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
	}
	
	$datasendmodel=0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	$state=0;
	
	$startdate="0000-00-00";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	
	$enddate="0000-00-00";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = $_POST['playtime'];
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
	
		if($prepower!=0)
		{
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$prepower."minutes -0 seconds"));
		}
	}
	//获取声音
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	$get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}


		$terminallistvalue = trim($_POST['terminallistvalue']);
		
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = $_POST['exemodel'];
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = $_POST['hiddenweek'];
			$repl = array(',' => '');
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			$playtime = "00:00:00";
		}
	}
	$system_task=0;
	$system_command=0;
	$system_param=0;
	if(isset($_POST['systemcommand']))
	{
		$system_task=trim($_POST['systemcommand']);
		if($system_task == 12)
		{
			$system_command = trim($_POST['taskcommand']);
		}
		else if($system_task == 13)
		{
			$system_param = trim($_POST['parameters']);	
		}
	}

	//获取任务优先级
	$priority = 3;
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$tasktype = 0;
	
	$audiosource = 0;
	if(isset($_POST['audiosource']))
	{	
		$audiosource = trim($_POST['audiosource']);
		
		$cmd = $audiosource;
		
		$audiosource = 0;
	}
	
	$channel=0;
	if(isset($_POST['channel']))
	{	
		$channel = trim($_POST['channel']);
		
		$cmdargs = $channel;
		
		$channel = 0;
	}
	
	$bandrate=0;
	if(isset($_POST['bandrate']))
	{	
		$bandrate = trim($_POST['bandrate']);
	}
	
	$samplerate=0;
	if(isset($_POST['samplerate']))
	{	
		$samplerate = trim($_POST['samplerate']);
	}
	
	$terminallistvalue = "";
	if(isset($_POST['terminallistvalue']))
	{	
		$terminallistvalue = trim($_POST['terminallistvalue']);
	 
	 	$terminalidarray = explode(",",$terminallistvalue);
	}
	
	$listvalue = "";
	if(isset($_POST['listvalue']))
	{	
		$listvalue = trim($_POST['listvalue']);
	
		$mediaidarray = explode(",",$listvalue);
	}
	
	$analysis_tree_group_string = "";
	
	if(isset($_POST['analysis_tree_group_string']))
	{
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	}
	
	$playfileid = 0;
	
	$gototaskmanager="";
	  
	switch($_POST['taskType'])
	{
			case "taskcommand":
			
			$tasktype=5;
			
			$cmd = 0;
			
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			
			$gototaskmanager="./Browse_system_task.php";
		break;
	}
	
	if($tasktype==5)
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '5' AND task.prepower = 0 ";
		
		$sql_same_name.= "AND task.channel = 0 AND task.info = '' AND task.taskid != '$_GET[taskid]' and task.sec_task_id = 0 ";
		
		$result_same_name = mysql_query($sql_same_name) or die(mysql_error());
		
		if(mysql_num_rows($result_same_name) > 0)
		{
			//=============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	else
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype' ";
		
		$sql_same_name.= "AND task.taskid != '$_GET[taskid]' ";
		
		$result_same_name = mysql_query($sql_same_name) or die(mysql_error());
		
		if(mysql_num_rows($result_same_name) > 0)
		{
			//===========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	@mysql_free_result($result_same_name);
	
	unset($sql_same_name);
	
	//获取用户优先级
		
	$sql = "SELECT book_admin.id, usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysql_query($sql) or die(mysql_error());
	
	$row = mysql_fetch_array($result);	
	
	//设置优先级
	$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
		
	//读取任务用户ID比较若相同则修改 不同则不修改
	
	$task_userid_sql = "SELECT task.priority FROM task WHERE task.task_user_id = '$task_user_id' AND task.taskid = '$_GET[taskid]' ";
	
	$task_userid_result = mysql_query($task_userid_sql) or die(mysql_error());
	
	if(mysql_num_rows($task_userid_result) <= 0)
	{
		$original_task_priority_result = mysql_query("SELECT task.priority FROM task WHERE task.taskid = '$_GET[taskid]'") or die(mysql_error());
		
		$original_task_priority_row = mysql_fetch_array($original_task_priority_result);
		
		$priority = trim($original_task_priority_row['priority']);
		
		@mysql_free_result($original_task_priority_result);
		
		@mysql_free_result($task_userid_result);
		
		unset($original_task_priority_row,$task_userid_sql);
	}
	else
	{
		@mysql_free_result($task_userid_result);
		
		unset($task_userid_sql);
	}
	
	@mysql_free_result($result);
	
	unset($sql,$row);
	//获取原来的任务名称、预开电源、用户id	
	$getoldtaskname = "";
	
	$getoldtaskprepower = "";
	
	$getoldtaskuserid = "";
	
	$sql = "SELECT task.taskname, task.prepower, task.task_user_id FROM task WHERE task.taskid = '$_GET[taskid]'";
	
	$result = mysql_query($sql)or die(mysql_error());
	
	if($row = mysql_fetch_array($result))
	{
		$getoldtaskname = $row['taskname'];
	
		$getoldtaskprepower = $row['prepower'];
		
		$getoldtaskuserid = $row['task_user_id'];
	}
	
	@mysql_free_result($result);
	
	unset($row,$sql);
	//锁定并事务处理
	mysql_query("START TRANSACTION");
	
	mysql_query("LOCK TABLE task WRITE,terminaloftask WRITE,mediaoftask WRITE,task READ,terminaloftask READ,mediaoftask READ");

	if($getoldtaskprepower == 0 && $prepower == 0)
	{
		//什么也不做
	}
	else if($getoldtaskprepower == 0 &&	$prepower != 0)
	{
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, startdate, enddate,";
		
		$sql.="playtime, exemodel, priority, tasktype,  channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume, task_user_id, ";
		
		$sql.="sec_task_id) VALUES('$taskname', '$israndomplay',  '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate','$preopenpowertime', '$exemodel', '$priority', '$system_task', '0', ";
		
		$sql.="'$bandrate', '$samplerate', '$system_command', '$system_param', '$playfileid', '$task_default_volume', '$getoldtaskuserid', '$_GET[taskid]')";
				
		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
		
		//取终端功放id
		
		$result = mysql_query("select max(taskid) from task ");
		
		if($row = mysql_fetch_array($result))
		{
			$getnewfunctionid = $row[0];
		}
		
		@mysql_free_result($result);
		
		unset($row);
		
		for($i=0;$i<count($terminalidarray);$i++)
		{
			if(is_numeric($terminalidarray[$i]))
			{
				$terminalid = (int)$terminalidarray[$i];
				
		        
				//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$getnewfunctionid','$terminalid')";
				
				$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid)VALUES('$getnewfunctionid','$terminalid','$analysis_tree_group_ids[$i]')";
		
				mysql_query($sql) or die(mysql_error());
		
				unset($sql);			
			}
		}
	}
	else if($getoldtaskprepower != 0 &&	$prepower == 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 AND task.info = '' and task.tasktype = '9' ";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysql_free_result($result);
		
		unset($sql,$row);
		
	mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid'") or die(mysql_error());
		
		mysql_query("DELETE FROM task WHERE task.taskid = '$getoldfunctionid'") or die(mysql_error());
	}
	else if($getoldtaskprepower != 0 &&	$prepower != 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 AND task.info = '' and task.tasktype = '9'";
		
		$result = mysql_query($sql) or die(mysql_error());
		
		if($row = mysql_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysql_free_result($result);
		
		unset($sql,$row);
        
	//$sql = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' ";
		
	//mysql_query($sql) or die(mysql_error());
		
	//unset($sql);

		$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";
		
		$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' , ";
		
		$sql.="state = '$state' ,startdate = '$startdate' ,enddate = '$enddate' ,";
		
		$sql.="playtime = '$preopenpowertime' ,exemodel = '$exemodel' , priority = '$priority' ,tasktype = '$system_task' , ";
		
		$sql.="channel = '0' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '$system_command' ,cmdargs = '$system_param' , ";
		
		$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume' ";
		
		$sql.=" WHERE  task.taskid = '$getoldfunctionid' and task.tasktype = '9' and channel = 0 ";
		
		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
	         	for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND groupid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
				     
						}
						
						}
                      
	                   
						for($z=0;$z<strlen($get_id);$z++)
						{
						//alert(z);
						if(substr($get_id,$z,2)=="::")
						{
	
						$position=$z+2;

						}
						if(substr($get_id,$z,1)=="|")
						{
						$position2=$z;
						$get_position =$position2-$position;
						
						$getid = substr($get_id,$z-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND terminalid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
				     
						}
						
						}
  
						for($j=0; $j<count($terminallistnum); $j++)
						{
							if(is_numeric($terminallistnum[$j]))
							{
							    $temp = (int)$terminallistnum[$j];
								$group = (int)$analysis_tree_group_ids[$j];
							
									$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$getoldfunctionid' AND terminalid='$temp' AND groupid = '$group'";
							    $get_result = mysql_query($get_sql) or die(mysql_error());
							  						  
								if($get_row = mysql_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];	
								$get_group = $get_row['groupid'];
								}
								@mysql_free_result($get_result);
								unset($get_sql,$get_row);
								if($temp==$get_terminals)
								{
								  if($get_group==$group)
								  {
								  	  for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
										
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
								
								  }
								  else
								  {
										$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 					
								  } 
								}
								else 
								{
								
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}
							
								
							  
								
							//	checkterminal($temp,$get_terminal,$get_terminals,$getoldfunctionid,$j);
							

							}
						}
										
						
	
	}
	
	$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

	$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' ,state = '$state' ,startdate = '$startdate' ,";
	
	$sql.="enddate = '$enddate' ,playtime = '$playtime' ,exemodel = '$exemodel' ,priority = '$priority' ,tasktype = '$system_task' , ";

	$sql.="channel = '$channel' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '$system_command' ,cmdargs = '$system_param' , ";

	$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume' WHERE taskid = '$_GET[taskid]' ";
	
	mysql_query($sql);
	
	unset($sql);
		
		//对相同功放任务处理
	if($tasktype == 5)
	{
		//查询相同功放任务
		$second_id = 0;
		
		$sql_play = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.tasktype = '5' ";
		
		$sql_play.= "AND task.prepower = '0' and task.channel = 0 and task.info = '' and task.sec_task_id != 0";
		
		$result_play = mysql_query($sql_play) or die(mysql_error());
		
		if($row_play = mysql_fetch_array($result_play))
		{
			$play_id[] = $row_play['taskid'];
		}
		
		@mysql_free_result($result_play);
		
		unset($row_play,$sql_play);
		
		foreach($play_id as $value)
		{
			if($value != trim($_GET['taskid']))
			{
				$second_id = $value;
				
				break;
			}
		}
		unset($play_id);
		
		//更新附加功放
		if(5 == $tasktype)
		{
			$cmd = 0;
		}
		
		$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

		$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' ,state = '$state' , ";
		
		$sql.="startdate = '$startdate' ,enddate = '$enddate' ,playtime = '$preopenpowertime' , ";
		
		$sql.="exemodel = '$exemodel' ,priority = '$priority' ,tasktype = '$tasktype' ,channel = '0' ,bandrate = '$bandrate' , ";
		
		$sql.="samplerate = '$samplerate' ,cmd = '$system_command' ,cmdargs = '$system_param' ,playfileid = '$playfileid' , ";
		
		$sql.="defaultvolume = '$task_default_volume' WHERE taskid = '$second_id' ";
		
		mysql_query($sql) or die(mysql_error());
		
		unset($sql);
		
		//删除终端

		//mysql_query("DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id'") or die(mysql_error());
		for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id' AND groupid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
				     
						}
						
						}
		
		
        
		
		for($z=0;$z<strlen($get_id);$z++)
						{
						//alert(z);
						if(substr($get_id,$z,2)=="::")
						{
	
						$position=$z+2;

						}
						if(substr($get_id,$z,1)=="|")
						{
						$position2=$z;
						$get_position =$position2-$position;
						
						$getid = substr($get_id,$z-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id' AND terminalid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
				     
						}
						
						}
		//添加终端
		for($i=0;$i<count($terminalidarray);$i++)
		{
			if(is_numeric($terminalidarray[$i]))
			{
				$terminalid = (int)$terminalidarray[$i];
				$group = (int)$analysis_tree_group_ids[$i];
				//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$second_id','$terminalid')";
				$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$second_id' AND terminalid='$terminalid' AND groupid='$group'";
							    $get_result = mysql_query($get_sql) or die(mysql_error());
							  						  
								if($get_row = mysql_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];
								$get_group = $get_row['groupid'];	
								}
								@mysql_free_result($get_result);
								unset($get_sql,$get_row);
								if($terminalid==$get_terminals)
								{
								 if($group==$get_group)
								 {
								 
								 }
								 else
								 {
				                    $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$second_id','$terminalid','$analysis_tree_group_ids[$i]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  	  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$terminalid)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$second_id' AND terminalid ='$terminalid'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 
								 
								 }

									}
									else 
								{
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$second_id','$terminalid','$analysis_tree_group_ids[$i]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$terminalid)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$second_id' AND terminalid ='$terminalid'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}	  
										  
				
				
							
			}
		}
	}

	
	for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$_GET[taskid]' AND groupid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
				     
						}
						
						}
	             
                   
					for($z=0;$z<strlen($get_id);$z++)
						{
						//alert(z);
						if(substr($get_id,$z,2)=="::")
						{
						
						
						$position=$z+2;
                  
						
						}
						if(substr($get_id,$z,1)=="|")
						{
						$position2=$z;
						$get_position =$position2-$position;
						
						
						$getid = substr($get_id,$z-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$_GET[taskid]' AND terminalid ='$getid'";
						  
						mysql_query($sql2) or die(mysql_error());
						unset($sql2);
						
						
				     
						}
						
						}
                          	
						for($j=0; $j<count($terminallistnum); $j++)
						{
							if(is_numeric($terminallistnum[$j]))
							{
							   $temp = (int)$terminallistnum[$j];
							   $group = (int)$analysis_tree_group_ids[$j];
							
							  	$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$_GET[taskid]' AND terminalid='$temp' AND groupid = '$group'";
							    $get_result = mysql_query($get_sql) or die(mysql_error());
							  						  
								if($get_row = mysql_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];
								$get_group = $get_row['groupid'];
								}
								@mysql_free_result($get_result);
								unset($get_sql,$get_row);
								
								if($temp==$get_terminals)
								{
								  if($group==$get_group)
								  {
								  for($z=0;$z<strlen($get_terminal);$z++)
												{
											//alert(z);
													if(substr($get_terminal,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
													$a=substr($get_terminal,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal,$z+1,16);
											
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															mysql_query($sql) or die(mysql_error());
															unset($sql);
															break;
															}
													}
												}						
								  }
								  else
								  {
										$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
											   for($z=0;$z<strlen($get_terminal);$z++)
												{
											//alert(z);
													if(substr($get_terminal,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
													$a=substr($get_terminal,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal,$z+1,16);
															
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															mysql_query($sql) or die(mysql_error());
															unset($sql);
															break;
															}
													}
												}						
										  } 
												
								  } 
								}
								else 
								{
						
								  
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysql_query($sql) or die(mysql_error());
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											  $position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
											if($a==$temp)
												{
												//$c=strpos($get_terminal,$a);
			
												$area = substr($get_terminal,$z+1,16);
													
							
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
												mysql_query($sql) or die(mysql_error());
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}
								
								//checkterminal($temp,$get_terminal,$get_terminals,$_GET[taskid],$j);
							

							}
						}

	mysql_query("UNLOCK TABLES");
    	if(!mysql_error())
			{
				mysql_query("COMMIT");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				//=======================================================================
				/*$socket	=	new	send_message_to_server($port_conf);	
				
				$msg = "task?state=5&id=".$_GET['taskid']."&volume=".$task_default_volume;
				
				$socket->send_data($_SESSION['serverip'],$msg);
				*/
				$create_socket_obj->send_socket_task_volume("task",5,$_GET['taskid'],$task_default_volume);
				
				echo "<script>window.location='success.php'</script>";
			}
			


	if(mysql_error())
	{
		mysql_query("ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
	
		echo "<script>window.location='error.php'</script>";
	
		exit;
	}	
}
?>
