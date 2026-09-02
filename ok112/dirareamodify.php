<?php
if (!session_id()) session_start();
header("content-type:text/html; charset=utf-8");
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
	/*显示多语言*/
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("create_folder",$create_folder);
	
	$smarty->assign("Filefoldermodify",$Filefoldermodify);
	
	//====保留系统文件=====//
	$get_folder_id = "";
	
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
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
	
	$sql_folder = "SELECT name FROM terminalfolder WHERE terminalfolder.id = '$get_folder_id' and terminalid='$terminal_id'";
	
	$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
	
	if($row_folder = mysqli_fetch_array($result_folder))
	{
		$folder_info = array("name"=>$row_folder['name']);
	}
	$smarty->assign("folder_info",$folder_info);
	
	@mysqli_free_result($result_folder);
	
	unset($folder_info,$row_folder,$sql_folder);
	
	$smarty->assign("id",$get_folder_id);
	$smarty->assign("terminal_id",$terminal_id);
	$smarty->assign("chinese_big5_english",$_SESSION['language']);

	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("dirstreammanager/dirstreammodify.html");
}
?>
