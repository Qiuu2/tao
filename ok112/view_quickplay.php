<?php
if (!session_id()) session_start();
header("content-type:text/html;charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
if(empty($_SESSION['admin_id']))
{
	//require_once('login.php');
	header("location:login.php");
}
else
{
	require_once("verify_user_sessionin_valid.php");
	
	verifysessionvalid();
	
	require_once("language/".$_SESSION['language'].".php");
	
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("terminal_manager",$terminal_manager);
	/*动态显示页面文本*/
	$smarty->assign("Revise",$Revise);
	$smarty->assign("Termianl_manager_Options",$Termianl_manager_Options);
	$smarty->assign("Termianl_manager_Terminal_name",$Termianl_manager_Terminal_name);
	$smarty->assign("Quickplay_task_name",$Quickplay_task_name);
	$smarty->assign("Quickplay_task_id",$Quickplay_task_id);
	$smarty->assign("Quickplay_task_length",$Quickplay_task_length);
	$smarty->assign("Quickplay_task_model",$Quickplay_task_model);
	$smarty->assign("Quickplay_task_ci",$Quickplay_task_ci);
	$smarty->assign("Quickplay_task_hour",$Quickplay_task_hour);
	$smarty->assign("Quickplay_task_min",$Quickplay_task_min);
	$smarty->assign("Quickplay_task_sec",$Quickplay_task_sec);
	
	$smarty->assign("Quickplay_task_priority",$Quickplay_task_priority);
	$smarty->assign("Quickplay_task_volume",$Quickplay_task_volume);
	$smarty->assign("Termianl_manager_Belong_to_the_region",$Termianl_manager_Belong_to_the_region);
	$smarty->assign("Termianl_manager_Terminal_Type",$Termianl_manager_Terminal_Type);
	$smarty->assign("Termianl_manager_Suspend_state",$Termianl_manager_Suspend_state);
	$smarty->assign("Termianl_manager_Connection_Status",$Termianl_manager_Connection_Status);
	$smarty->assign("Termianl_manager_Running_state",$Termianl_manager_Running_state);
	$smarty->assign("Termianl_manager_IP_Address",$Termianl_manager_IP_Address);
	$smarty->assign("Termianl_manager_Volume",$Termianl_manager_Volume);
	
	$smarty->assign("Termianl_manager_Select_All",$Termianl_manager_Select_All);
	$smarty->assign("Termianl_manager_Cancel",$Termianl_manager_Cancel);
	$smarty->assign("Termianl_manager_Start",$Termianl_manager_Start);
	$smarty->assign("Termianl_manager_Stop",$Termianl_manager_Stop);
	$smarty->assign("Termianl_manager_Delete",$Termianl_manager_Delete);
	$smarty->assign("Termianl_manager_Shortcuts_Map",$Termianl_manager_Shortcuts_Map);
	$smarty->assign("Termianl_manager_Change_volume",$Termianl_manager_Change_volume);
	$smarty->assign("Termianl_manager_Confirm_change",$Termianl_manager_Confirm_change);
	
	$smarty->assign("Termianl_manager_Normal",$Termianl_manager_Normal);
	$smarty->assign("Termianl_manager_Suspend",$Termianl_manager_Suspend);
	$smarty->assign("Termianl_manager_Link",$Termianl_manager_Link);
	$smarty->assign("Termianl_manager_Run",$Termianl_manager_Run);
	$smarty->assign("Termianl_manager_Disconnect",$Termianl_manager_Disconnect);
	$smarty->assign("Termianl_manager_Stop",$Termianl_manager_Stop);
	
	$smarty->assign("Termianl_manager_Select_Options",$Termianl_manager_Select_Options);
	$smarty->assign("Termianl_manager_OK_delete",$Termianl_manager_OK_delete);
	
	$smarty->assign("Termianl_manager_Shortcut_Name",$Termianl_manager_Shortcut_Name);
	$smarty->assign("Termianl_manager_Shortcut",$Termianl_manager_Shortcut);
	$smarty->assign("Termianl_manager_Map_Terminal",$Termianl_manager_Map_Terminal);
	$smarty->assign("Termianl_manager_Operate",$Termianl_manager_Operate);
	$smarty->assign("Termianl_manager_View_Terminal",$Termianl_manager_View_Terminal);
	$smarty->assign("Termianl_manager_Delete_Map",$Termianl_manager_Delete_Map);
	$smarty->assign("duanluchufa",$duanluchufa);
	$smarty->assign("Searchform",$Searchform);
	//获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(have_rights("terminalpriv") || is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	//获取分页类
	require('editor.php');
	$smarty->assign('descriptionarea',$descriptionarea);
	if(!isset($_GET['page']))
	{
	    $page=1;
	    $start=0;
	}
	else 
	{
	    $page=$_GET['page'];
	    $start=($_GET['page']-1)*$NumOfPage;
	}
	
	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
		$_SESSION['tran_mid_value'] = $terminal_id;
	}
	
	if($terminal_id == "")
	{
		$terminal_id = $_SESSION['tran_mid_value'];
	}

	get_terminal_type(9,$do_php_prompt['Terminal_not_support'],$terminal_id,1);
	
	//取源终端名称
	$sql = "SELECT typeid FROM terminal WHERE id= '$terminal_id'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	if($row = mysqli_fetch_array($result))
	{
		if($row['typeid']==17)
		{
			$shotcut_sql = "SELECT terminalkeymaptask.taskid,task.taskname,task.timelengthtype,task.timelength,task.israndomplay,task.datasendmodel ,task.priority,task.defaultvolume,terminalkeymaptask.keyid,terminal.terminalname FROM task,terminalkeymaptask,terminal WHERE terminalkeymaptask.taskid = task.taskid AND terminal.typeid='17' AND terminal.id=task.cmdargs and (task.tasktype='20' OR task.tasktype='21' OR task.tasktype='29')";
			$shotcut_result	=	mysqli_query($con,$shotcut_sql) or die("Execute error".mysqli_error($con));
			$Num	=	mysqli_num_rows($shotcut_result);
			$shotcut_result =   mysqli_query($con,$shotcut_sql."LIMIT $start,$NumOfPage")or die("Execute error".mysqli_error($con));
			$shotcut_info=array();
			while($shotcut_row = mysqli_fetch_array($shotcut_result))
			{
				$shotcut_info[] = array("id"=>$shotcut_row['taskid']."/".$shotcut_row['keyid'],"taskname"=>$shotcut_row['taskname'],"timelengthtype"=>$shotcut_row['timelengthtype'],"timelength"=>$shotcut_row['timelength'],"israndomplay"=>$shotcut_row['israndomplay'],"datasendmodel"=>$shotcut_row['datasendmodel'],"priority"=>$shotcut_row['priority'],"defaultvolume"=>$shotcut_row['defaultvolume'],"key"=>$shotcut_row['keyid'],"terminalname"=>$shotcut_row['terminalname'],"taskid"=>$shotcut_row['taskid']
				);
			}

			$smarty->assign("terminal_info",$shotcut_info);
		//	@mysqli_free_result($shotcut_row);
			unset($shotcut_sql,$shotcut_row);
		
		}
		else
		{
			$shotcut_sql = "SELECT terminalkeymaptask.taskid,task.taskname,task.timelengthtype,task.timelength,task.israndomplay,task.datasendmodel ,task.priority,task.defaultvolume,terminalkeymaptask.keyid,terminal.terminalname FROM task,terminalkeymaptask,terminal WHERE terminalkeymaptask.taskid = task.taskid AND terminal.id='$terminal_id' AND terminal.id=task.cmdargs and (task.tasktype='20' OR task.tasktype='21' OR task.tasktype='29')";

			$shotcut_result	=	mysqli_query($con,$shotcut_sql) or die("Execute error".mysqli_error($con));
			$Num	=	mysqli_num_rows($shotcut_result);
			$shotcut_result =   mysqli_query($con,$shotcut_sql."LIMIT $start,$NumOfPage")or die("Execute error".mysqli_error($con));
			$shotcut_info=array();
			while($shotcut_row = mysqli_fetch_array($shotcut_result))
			{
				$shotcut_info[] = array("id"=>$shotcut_row['taskid']."/".$shotcut_row['keyid'],"taskname"=>$shotcut_row['taskname'],"timelengthtype"=>$shotcut_row['timelengthtype'],"timelength"=>$shotcut_row['timelength'],"israndomplay"=>$shotcut_row['israndomplay'],"datasendmodel"=>$shotcut_row['datasendmodel'],"priority"=>$shotcut_row['priority'],"defaultvolume"=>$shotcut_row['defaultvolume'],"key"=>$shotcut_row['keyid'],"terminalname"=>$shotcut_row['terminalname'],"taskid"=>$shotcut_row['taskid']
				);
			}

			$smarty->assign("terminal_info",$shotcut_info);
			//@mysqli_free_result($shotcut_row);
			unset($shotcut_sql,$shotcut_row);
		}
	}

	//分页
	if($Num != 0)
	{
		require_once("pagination.class.php");
		$p = new pagination;
		$p->Items($Num);
		$p->limit($NumOfPage);
		$p->target("?id=".$_GET['id']."&");
		$p->currentPage($_GET['page']);
		$p->adjacents(3);
		$smarty->assign("pagestr",$p->show());
	}

	$smarty->assign("start",$start);
	$smarty->assign("terminal_id",$terminal_id);

	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("TerminalManager/view_quickplay.html");
}
?>