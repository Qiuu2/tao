<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

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
	$smarty->assign("admin_id",$_SESSION['admin_id']);
} 

$smarty->display("camer/playView.html");

?>