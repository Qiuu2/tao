<?php
/*****************************
	一个终端只能属于一个分区
	已添加分区终端部再显示
*****************************/
if (!session_id()) session_start();

header('Content-Type:text/html;charset=utf-8');

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');

//验证是否是失效
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	$_SESSION['login']="";
	header("location:index.php");	
}
else
{	


	$shibiedeviceid="";
	if(isset($_GET['shibiedeviceid']))
	{
	$shibiedeviceid=trim($_GET['shibiedeviceid']);

	}

	if($shibiedeviceid=="")
	{
		echo "<script>alert('请选择设备地址');history.back();</script>";

	}
	$smarty->assign("shibiedeviceid",$shibiedeviceid);
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("stream_create",$stream_create);

	$smarty->assign("Streamadd",$Streamadd);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("ai_Manager/aipeopleadd.html");
}
?>