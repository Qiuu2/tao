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
	$sql_task_filead = "SELECT media.id,media.name FROM media WHERE typeid='mp3'";
	else
	$sql_task_filead = "SELECT media.id,media.name FROM media WHERE typeid='mp3'";
	
	$result_task_filead = mysqli_query($con,$sql_task_filead) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result_task_filead) > 0)
	{
		$remote_flag = 1;
	
		$tree.= "<item text=\\\"".$set_remote_map['media_file']."\\\" id=\\\"".$set_remote_map['media_file']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
		
		while($row_task_filead = mysqli_fetch_array($result_task_filead))
		{
			$tree.= "<item text=\\\"0_".$row_task_filead['name']."\\\" id=\\\"0_".$row_task_filead['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
		}
		$tree.= "</item>";
	}
	
	@mysqli_free_result($result_task_filead);
	
	unset($sql_task_filead,$row_task_filead);
if($_SESSION['username']=="admin")
	$sql_task_adm = "SELECT media.id,media.name FROM media WHERE typeid='tts'";
	else
	$sql_task_adm = "SELECT media.id,media.name FROM media WHERE typeid='tts'";
	$result_task_adm = mysqli_query($con,$sql_task_adm) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result_task_adm) > 0)
	{
		$remote_flag = 1;
	
		$tree.= "<item text=\\\"".$set_remote_map['byte_music']."\\\" id=\\\"".$set_remote_map['byte_music']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
	
		while($row_task_adm = mysqli_fetch_array($result_task_adm))
		{
			$tree.= "<item text=\\\"1_".$row_task_adm['name']."\\\" id=\\\"1_".$row_task_adm['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
		}
		
		$tree.= "</item>";
	}

	@mysqli_free_result($result_task_adm);
	
	unset($sql_task_adm,$row_task_adm);
	
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
	
	$smarty->display("shortkey_mapping/set_key_mapping.html");
}
?>