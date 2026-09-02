<?php
/*****************************
	添加报警分区
	只显示 普通IP终端 双线寻呼终端
*****************************/
if (!session_id()) session_start();

header('content-Type:text/html;charset=utf-8');

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	header("location:login.php");
}
else
{	
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("create_alarm_zone",$create_alarm_zone);
	
	$smarty->assign("Streamadd",$Streamadd);
	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);

  	$terminalist = get_terminallist5($type, 0);

  	$smarty->assign("strarea",$terminalist);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("alarmmanager/alarmareaadd.html");
}
?>
