<?php
if (!session_id()) session_start();

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');
//��֤�Ƿ�ʧЧ
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	header("location:login.php");	
}
else
{
	//��ʾ������
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("Filemanager",$Filemanager);

	$smarty->display("LogManager/display_log.html");
}
?>
