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
	
	require_once("language/".$_SESSION['language'].".php");
	
	$smarty->assign("language",$_SESSION['language']);

	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
		
		$_SESSION['terminal'] = $terminal_id;
	}

	if($terminal_id == "")
	{
		$terminal_id = $_SESSION['terminal'];
	}
	get_terminal_type(2,$do_php_prompt['Terminal_not_support'],$terminal_id,1);
	//输出session
	$smarty->assign("terminal_id",$terminal_id);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("dirstreammanager/dirstreammanager.html");
}
?>