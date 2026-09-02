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
	header("location:login.php");
}
else
{	
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("set_remote_map",$set_remote_map);
	
	$remote_flag = 0;
	$userid=$_SESSION['userid'];
	//获取任务信息树
	$tree = "<tree id=\\\"0\\\">";
	if($_SESSION['username']=="admin")
	$sql_task_filead = "SELECT taskid, taskname FROM task WHERE task.tasktype IN (2,15) AND info=''";
	else
	$sql_task_filead = "SELECT taskid, taskname FROM task WHERE task.tasktype IN(2,15) AND info='' AND task.task_user_id='$userid'";
	
	$result_task_filead = mysqli_query($con,$sql_task_filead) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result_task_filead) > 0)
	{
		$remote_flag = 1;
	
		$tree.= "<item text=\\\"".$set_remote_map['media_broadcast']."\\\" id=\\\"".$set_remote_map['media_broadcast']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
		
		while($row_task_filead = mysqli_fetch_array($result_task_filead))
		{
			$tree.= "<item text=\\\"mapping_".$row_task_filead['taskname']."\\\" id=\\\"".$row_task_filead['taskid']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
		}
		$tree.= "</item>";
	}
	
	@mysqli_free_result($result_task_filead);
	
	unset($sql_task_filead,$row_task_filead);
if($_SESSION['username']=="admin")
	$sql_task_adm = "SELECT taskid, taskname FROM task WHERE task.tasktype = 3";
	else
	$sql_task_adm = "SELECT taskid, taskname FROM task WHERE task.tasktype = 3 AND task.task_user_id='$userid'";
	$result_task_adm = mysqli_query($con,$sql_task_adm) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result_task_adm) > 0)
	{
		$remote_flag = 1;
	
		$tree.= "<item text=\\\"".$set_remote_map['live_collection']."\\\" id=\\\"".$set_remote_map['live_collection']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
	
		while($row_task_adm = mysqli_fetch_array($result_task_adm))
		{
			$tree.= "<item text=\\\"mapping_".$row_task_adm['taskname']."\\\" id=\\\"".$row_task_adm['taskid']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
		}
		
		$tree.= "</item>";
	}

	@mysqli_free_result($result_task_adm);
	
	unset($sql_task_adm,$row_task_adm);
	
	//终端功放
	if($_SESSION['username']=="admin")
	$sql_task_ter = "SELECT taskid, taskname FROM task WHERE tasktype=5 AND sec_task_id=0 AND prepower = 0";
	else
	$sql_task_ter = "SELECT taskid, taskname FROM task WHERE tasktype=5 AND sec_task_id=0 AND prepower = 0 AND task.task_user_id='$userid'";
	$result_task_ter = mysqli_query($con,$sql_task_ter)or die(mysqli_error($con));
	
	if(mysqli_num_rows($result_task_ter) > 0)
	{
		$remote_flag = 1;
	
		$tree.= "<item text=\\\"".$set_remote_map['terminal_amplifier']."\\\" id=\\\"".$set_remote_map['terminal_amplifier']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
		
		while($row_task_ter = mysqli_fetch_array($result_task_ter))
		{
			$tree.= "<item text=\\\"mapping_".$row_task_ter['taskname']."\\\" id=\\\"".$row_task_ter['taskid']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
		}
		$tree.= "</item>";
	}
	$tree.= "</tree>";
	
	@mysqli_free_result($result_task_ter);
	
	unset($row_task_ter,$sql_task_ter);
	
	if($remote_flag == 0)
	{
		echo "<script>alert('".$set_remote_map['not_remote_tasks']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
	}
	//保存用户是否登录
	$smarty->assign("tree_info",$tree);
	
	unset($tree);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("task_mapping/set_task_mapping.html");
}
?>