<?php
if (!session_id()) session_start();

require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	header("location:login.php");	
}
else
{
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("Filemanager",$Filemanager);
	$smarty->display("top.html");
}
?>
