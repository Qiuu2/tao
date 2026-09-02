<?php
if (!session_id()) session_start();
header("content-type:text/html; charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require_once('inc/config.php');
if(empty($_SESSION['admin_id']))
{
	header("location:login.php");	
}
else
{

	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	
		//获取权限
		require_once("User_Rights_Manage/verify_user_rights_class.php");
		if(is_admin($con,$_SESSION['username']))
		{
			$smarty->assign("is_right",1);
		}
		else
		{
			$smarty->assign("is_right",0);
		}

	$smarty->assign("centercontrol",$centercontrol);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("centercontrol/centercontrol.html");
}
?>
