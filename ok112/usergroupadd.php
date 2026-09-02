<?php
if (!session_id()) session_start();
header("content-type:text/html; charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	require_once('login.php');	
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("user_group_add",$user_group_add);

	$smarty->assign("Usergroupadd",$Usergroupadd);
	//只有管理员可以操作
	require_once("User_Rights_Manage/verify_user_rights_class.php");

	if(!is_admin($con,$_SESSION['username']))
	{
		echo "<script>alert('权限不够');</script>";
		echo "<script>window.history.back();</script>";
		exit;
	}
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("UserGroupManager/userGroupAdd_form.html");
}
?>