<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require_once('inc/common.php');
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
	$smarty->assign("set_shotcut",$set_shotcut);
	$smarty->assign("Setterminalkey",$Setterminalkey);
		//读取解码终端

	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	
	$decode_terminal = create_tree_str($type);



	$smarty->assign("decode_terminal",$decode_terminal);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("camer/set_camer_event.html");
}
?>