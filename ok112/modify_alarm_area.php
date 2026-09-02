<?php
if (!session_id()) session_start();

header('content-Type:text/html;charset=utf-8');

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');
//验证失效
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

	$smarty->assign("modify_alarm_zone",$modify_alarm_zone);
	
	$smarty->assign("Streamadd",$Streamadd);
	
	$get_id = "";

	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}

	$sql = "SELECT name,info FROM alarmarea WHERE alarmarea.id = '$get_id'";

	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	$alarm_info=array();
	if($row = mysqli_fetch_array($result))
	{
		$alarm_info = array("name"=>$row['name'],"info"=>$row['info']); 
	}
	
	$smarty->assign("alarm_info",$alarm_info);
	
	mysqli_free_result($result);
	
	unset($sql,$row,$alarm_info);
	
	//获取报警分区终端ID
	//$terminal_string = "";
	
	$alarm_sql = "SELECT terminalid gourpid FROM terminalofalarmgroup WHERE alarmgroupid = '$get_id' ORDER BY groupid";
	
	$alarm_result = mysqli_query($con,$alarm_sql) or die(mysqli_error($con));
	$alarm_terminal=array();
	while($alarm_row = mysqli_fetch_array($alarm_result))
	{

		$alarm_terminal[] = trim($alarm_row['terminalid']);
	}

	$terminalidlist = get_current_task_termianl_id3($con,$get_id);

	$smarty->assign("terminalidlist",$terminalidlist);

	$smarty->assign("alarm_terminal",$alarm_terminal);
	
	mysqli_free_result($alarm_result);
	
	unset($alarm_sql,$alarm_row,$alarm_terminal);
	
	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	
  	$terminalist = get_terminallist5($type, 0);
  		
	$smarty->assign("strarea",$terminalist);
	
	$smarty->assign("id",$get_id);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("alarmmanager/alarmareamodify.html");
}
?>
