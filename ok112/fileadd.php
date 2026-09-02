<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

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

	$smarty->assign("file_upload_add",$file_upload_add);	
	/*动态显示页面文本内容*/
	$smarty->assign("Fileadd",$Fileadd);

	require('editor.php');
	
	$folder_id = 0;
	
	if(isset($_GET['folder_id']))
	{
		$folder_id = trim($_GET['folder_id']);
	}

	$smarty->assign("folder_id",$folder_id);
		
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("FileManager/fileadd.html");
}
?>
