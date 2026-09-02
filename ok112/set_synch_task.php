<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if(empty($_SESSION['admin_id']))
{	
	header("location:login.php");
}
else
{	

	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("set_remote_map",$set_remote_map);
	
	$terminal_id="";
		if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
		
	}

	$sql = "SELECT isdecode FROM terminaltype,terminal WHERE terminaltype.id=terminal.typeid AND terminal.id='$terminal_id' AND terminal.typeid not in(0,26,2,7,8,9,10,12,15,16,17,18,21,22,25,28,29,30,31,32,36,37,40)";
	
	$result= mysqli_query($con,$sql) or die(mysqli_error($con));
		if(mysqli_num_rows($result) <=0)
		{
			echo "<script>alert('".$set_remote_map['nozhichi']."');</script>";
			echo "<script>window.history.back();</script>";		
			exit;	
		}
	
	

	$smarty->assign("terminal_id",$terminal_id);
	$remote_flag = 0;
	$userid=$_SESSION['userid'];
	
	$tree = "<tree id=\\\"0\\\">";

	if($_SESSION['username']=="admin")
	$sql_task_sche = "SELECT DISTINCT info FROM task WHERE task.tasktype = 1";
	else
	$sql_task_sche = "SELECT DISTINCT info FROM task WHERE task.tasktype = 1 AND task.task_user_id='$userid'";
	
	$result_task_sche = mysqli_query($con,$sql_task_sche) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result_task_sche) > 0)
	{
		$remote_flag = 1;
	
		$tree.= "<item text=\\\"".$set_remote_map['sche']."\\\" id=\\\"".$set_remote_map['sche'].'::'."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
		
		while($row_task_sche = mysqli_fetch_array($result_task_sche))
		{
		$gettasksche=$row_task_sche['info'];
			$tree.= "<item text=\\\"".$row_task_sche['info']."\\\" id=\\\"".$row_task_sche['info'].'::'."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		
			if($_SESSION['username']=="admin")
				$sql_task_subsche = "SELECT taskid,taskname FROM task WHERE task.tasktype = 1 AND info='$gettasksche'";
				else
				$sql_task_subsche = "SELECT taskid,taskname FROM task WHERE task.tasktype = 1 AND info='$gettasksche' AND task.task_user_id='$userid'";
				$result_task_subsche = mysqli_query($con,$sql_task_subsche) or die(mysqli_error($con));
				while($row_task_subsche = mysqli_fetch_array($result_task_subsche))
				{
					$tree.= "<item text=\\\"".$row_task_subsche['taskname']."\\\" id=\\\"".$row_task_subsche['taskid']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
				}
		$tree.= "</item>";
		}
		$tree.= "</item>";
	}
	
	@mysqli_free_result($result_task_sche);
	
	unset($sql_task_sche,$row_task_sche);
	
	if($_SESSION['username']=="admin")
	$sql_task_filead = "SELECT taskid, taskname FROM task WHERE task.tasktype = 2";
	else
	$sql_task_filead = "SELECT taskid, taskname FROM task WHERE task.tasktype = 2 AND task.task_user_id='$userid'";
	
	$result_task_filead = mysqli_query($con,$sql_task_filead) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result_task_filead) > 0)
	{
		$remote_flag = 1;
	
		$tree.= "<item text=\\\"".$set_remote_map['media_broadcast']."\\\" id=\\\"".$set_remote_map['media_broadcast'].'::'."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
		
		while($row_task_filead = mysqli_fetch_array($result_task_filead))
		{
			$tree.= "<item text=\\\"".$row_task_filead['taskname']."\\\" id=\\\"".$row_task_filead['taskid']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
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
	
		$tree.= "<item text=\\\"".$set_remote_map['live_collection']."\\\" id=\\\"".$set_remote_map['live_collection'].'::'."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
	
		while($row_task_adm = mysqli_fetch_array($result_task_adm))
		{
			$tree.= "<item text=\\\"".$row_task_adm['taskname']."\\\" id=\\\"".$row_task_adm['taskid']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
		}
		
		$tree.= "</item>";
	}

	@mysqli_free_result($result_task_adm);
	
	unset($sql_task_adm,$row_task_adm);
	if($_SESSION['username']=="admin")
	$sql_task_webradio = "SELECT taskid, taskname FROM task WHERE task.tasktype = 10";
	else
	$sql_task_webradio = "SELECT taskid, taskname FROM task WHERE task.tasktype = 10 AND task.task_user_id='$userid'";
	$result_task_webradio = mysqli_query($con,$sql_task_webradio) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result_task_webradio) > 0)
	{
		$remote_flag = 1;
	
		$tree.= "<item text=\\\"".$set_remote_map['webradio']."\\\" id=\\\"".$set_remote_map['webradio'].'::'."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
	
		while($row_task_webradio = mysqli_fetch_array($result_task_webradio))
		{
			$tree.= "<item text=\\\"".$row_task_webradio['taskname']."\\\" id=\\\"".$row_task_webradio['taskid']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
		}
		
		$tree.= "</item>";
	}

	@mysqli_free_result($result_task_webradio);
	
	unset($sql_task_webradio,$row_task_webradio);
	
	
	
 if($_SESSION['username']=="admin")
	$sql_task_webradio = "SELECT taskid, taskname FROM task WHERE task.tasktype IN(17,19)";
	else
	$sql_task_webradio = "SELECT taskid, taskname FROM task WHERE IN(17,19) AND task.task_user_id='$userid'";
	$result_task_webradio = mysqli_query($con,$sql_task_webradio) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result_task_webradio) > 0)
	{
		$remote_flag = 1;
	
		$tree.= "<item text=\\\"".$set_remote_map['byte_music']."\\\" id=\\\"".$set_remote_map['byte_music'].'::'."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
	
		while($row_task_webradio = mysqli_fetch_array($result_task_webradio))
		{
			$tree.= "<item text=\\\"".$row_task_webradio['taskname']."\\\" id=\\\"".$row_task_webradio['taskid']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
		}
		
		$tree.= "</item>";
	}

	@mysqli_free_result($result_task_webradio);
	
	unset($sql_task_webradio,$row_task_webradio);
	
	//�ն˹���
	if($_SESSION['username']=="admin")
	$sql_task_ter = "SELECT taskid, taskname FROM task WHERE tasktype=5 AND sec_task_id=0 AND prepower = 0";
	else
	$sql_task_ter = "SELECT taskid, taskname FROM task WHERE tasktype=5 AND sec_task_id=0 AND prepower = 0 AND task.task_user_id='$userid'";
	$result_task_ter = mysqli_query($con,$sql_task_ter)or die(mysqli_error($con));
	
	if(mysqli_num_rows($result_task_ter) > 0)
	{
		$remote_flag = 1;
	
		$tree.= "<item text=\\\"".$set_remote_map['terminal_amplifier']."\\\" id=\\\"".$set_remote_map['terminal_amplifier'].'::'."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
		
		while($row_task_ter = mysqli_fetch_array($result_task_ter))
		{
			$tree.= "<item text=\\\"".$row_task_ter['taskname']."\\\" id=\\\"".$row_task_ter['taskid']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
		}
		$tree.= "</item>";
	}
	if($_SESSION['username']=="admin")
	$sql_task_ter = "SELECT taskid, taskname FROM task WHERE tasktype='17' AND sec_task_id=0 ";
	else
	$sql_task_ter = "SELECT taskid, taskname FROM task WHERE tasktype='17' AND sec_task_id=0  AND task.task_user_id='$userid'";
	$result_task_ter = mysqli_query($con,$sql_task_ter)or die(mysqli_error($con));
	
	if(mysqli_num_rows($result_task_ter) > 0)
	{
		$remote_flag = 1;
	
		$tree.= "<item text=\\\"".$set_remote_map['tts_music']."\\\" id=\\\"".$set_remote_map['tts_music'].'::'."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
		
		while($row_task_ter = mysqli_fetch_array($result_task_ter))
		{
			$tree.= "<item text=\\\"".$row_task_ter['taskname']."\\\" id=\\\"".$row_task_ter['taskid']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
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
	//�����û��Ƿ��¼

	$smarty->assign("tree_info",$tree);

	unset($tree);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("TerminalManager/setsynchtask.html");
}
?>
