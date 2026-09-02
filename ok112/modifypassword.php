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
	$smarty->assign("usr_password_modify",$usr_password_modify);
	
	$smarty->assign("Userpasswordmodify",$Userpasswordmodify);
	$smarty->assign("FUZA_PASS",$FUZA_PASS);
	$smarty->assign("username",urlencode($_SESSION['username']));
	$smarty->display("UserManager/userpasswordmodify.html");
}
?>
