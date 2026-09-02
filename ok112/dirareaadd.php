<?php
if (!session_id()) session_start();

if(is_file('install.php')) 
{
	header("Location: install.php"); 

	exit;
}
require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

if(empty($_SESSION['admin_id']))
{
	//require_once('login.php');

	header("location:login.php");	
}
else
{
	require_once("verify_user_sessionin_valid.php");
	verifysessionvalid();	
	$folder_id = 0;
	
	if(isset($_GET['id']))
	{
		$folder_id = trim($_GET['id']);
	}
	
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
	
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("create_folder",$create_folder);
	
	$smarty->assign("Filefolderadd",$Filefolderadd);

	$smarty->assign("terminal_id",$terminal_id);
	$smarty->assign("folder_id",$folder_id);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("dirstreammanager/dirareaadd.html");
}
?>
