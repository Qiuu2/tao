<?php

if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

//require_once('inc/common.php');

//验证是否失效
//require_once("verify_user_sessionin_valid.php");

//verifysessionvalid();

//显示多语言
require_once("language/".$_SESSION['language'].".php");
$smarty->assign("language",$_SESSION['language']);

//导入封装
require_once("./features_wrapper_class.php");
$forward_ok_error_obj = new forward_ok_error_class();//弹出提示信息

$create_socket_obj = new create_socket_class();//创建socket对象

//取临时变量的值
$terminal_id = "";
//所有选择终端ID
$terminal_ids = array();
//所有断开终端ID
$terminal_no_ids = array();
//选取终端数目
$terminal_len = 0;
//判断终端数目
$terminal_temp_len = 0;

if(isset($_GET['terminal_id']))
{
	$terminal_id = trim($_GET['terminal_id']);
	
	$terminal_ids = explode(",",$terminal_id);
	
	$terminal_len = count($terminal_ids);
}
//获取终端密码
$terminal_password = "";

if(isset($_GET['terminal_password']))
{
	$terminal_password = trim($_GET['terminal_password']);
}

//===========================================
//分析终端终端是否断开
//断开提示-----判断
//===========================================
$k=0;
for($i=0;$i<$terminal_len;$i++)
{
	$sql="SELECT terminalname FROM terminal WHERE terminal.id =".$terminal_ids[$i]."";
	$sqlresult = mysqli_query($con,$sql) or die(mysqli_error($con));
	while($sql_row = mysqli_fetch_array($sqlresult))
	{
		$type=get_terminal_type(5,$sql_row['terminalname'].$do_php_prompt['Terminal_not_support'],$terminal_ids[$i],0);
		if($type=="")
		$k++;
	}
}

if($k==$terminal_len)
{
	echo "<script>window.history.back();</script>";
}
else
{
if(!empty($terminal_id))
{
	$terminal_sql = "SELECT terminal.id,terminalname,netstate FROM terminal WHERE terminal.id IN (".$terminal_id.")";
	$terminal_result = mysqli_query($con,$terminal_sql) or die(mysqli_error($con));
	while($terminal_row = mysqli_fetch_array($terminal_result))
	{
		if($terminal_row['netstate'] == 0)
		{
			$forward_ok_error_obj->exit_function($terminal_row['terminalname']."".$do_php_prompt['failure_check_network']."");
			$terminal_temp_len++;
			$terminal_no_ids[] = $terminal_row['id'];
		}
	}

	
	if($terminal_temp_len == $terminal_len)
	{
		$forward_ok_error_obj->exit_back_function("".$do_php_prompt['all_failure_check_network']."");
	}
	else
	{
		try
		{
		$create_socket_obj->send_socket_terminal_password("terminal",7,implode(",",array_diff($terminal_ids,$terminal_no_ids)),$terminal_password);
		}
		catch(Exception $e)
		{
			$forward_ok_error_obj->pop_forward_path($e->getMessage(),"./terminalmanager.php");
		}
		
		$forward_ok_error_obj->pop_forward_path("".$do_php_prompt['data_sent_success_wait']."","./terminalmanager.php");
	}
}
else
{
	$forward_ok_error_obj->exit_back_function("".$do_php_prompt['select_need_terminal']."");
}
}
?>