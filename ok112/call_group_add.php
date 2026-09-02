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
	//终端类型
	$type_id = -1;
	//获取终端id
	$get_terminal_id = "";
	
	if(isset($_GET['id']))
	{
		$get_terminal_id = trim($_GET['id']);
	}
	
	//flag=1，flag=2,2为带目录
	$flag = 1;
	if(isset($_GET['flag']))
	{
		$flag = trim($_GET['flag']);
	}

	$get_terminal_id = "";
	if(isset($_GET['id']))
	{
		$get_terminal_id = trim($_GET['id']);
	}

		//读取解码终端
		
		$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
		if($flag==1)
		{
		$decode_terminal = get_terminallistoggroup2($type, $get_terminal_id);
		}
		else if($flag==2)
		{
		$decode_terminal = get_dirarea($type, $get_terminal_id);
		}

	$smarty->assign("get_callback_id",$get_terminal_id);
	$smarty->assign("decode_terminal",$decode_terminal);
	
	$smarty->assign("id",$get_terminal_id);
	$smarty->assign("flag",$flag);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("TerminalManager/call_group_add.html");
}
?>