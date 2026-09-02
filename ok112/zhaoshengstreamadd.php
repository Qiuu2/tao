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
		require_once('login.php');	
}
else
{	
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	
	$smarty->assign("language",$_SESSION['language']);
	
	$smarty->assign("stream_create",$stream_create);

	$smarty->assign("Streamadd",$Streamadd);

	//$type =  "(1,3,4,11,13,5,14,15,6,2,7,8,9,10,16,12,17)";
	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	$type="(".$type.")";
	
	$nogroupterminal = get_soundsnogrouplist($type);

	//////////////////////////添加结束
	$smarty->assign("nogroupterminal",$nogroupterminal);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("zhaoshengManager/streamadd.html");
}
?>