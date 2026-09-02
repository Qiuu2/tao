<?php
if (!session_id()) session_start();
header("content-type:text/html; charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require_once("inc/config.php");
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
	
	$smarty->assign("server_manager",$server_manager);
	$smarty->assign("media_task_add",$media_task_add);

	$result	=	mysqli_query($con,"SELECT projectstate,playtime,endtime,cmdargs,cmd FROM `task` WHERE taskid='70000'");
	if($row = mysqli_fetch_array($result))
	{
		$smarty->assign("projectstate",$row['projectstate']);	
		$smarty->assign("playtime",$row['playtime']);	
		$smarty->assign("endtime",$row['playtime']);
		$smarty->assign("cmdargs",$row['cmdargs']);

	}
	
	$result	=	mysqli_query($con,"SELECT sounddetect,fuzamima FROM `serverconfig`");
	if($row = mysqli_fetch_array($result))
	{
		$smarty->assign("sounddetect",$row['sounddetect']);	
		$smarty->assign("fuza_mima",$row['fuzamima']);		
	}

	$result	=	mysqli_query($con,"SELECT backup FROM `serverbaseparam`");
	if($row = mysqli_fetch_array($result))
	{
		$smarty->assign("backup",$row['backup']);	
		
	}

	$smarty->assign("username",urlencode($_SESSION['username']));
	$smarty->display("UserManager/serversetting.html");
}
?>
