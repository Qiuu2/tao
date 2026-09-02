<?php
if (!session_id()) session_start();

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	header("location:login.php");	
}
else
{

	//输出session
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("TerminalManager/display_area_and_terminal.html");
}
?>
