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
	require_once('login.php');	
}
else
{		
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("tel_collect_task_modify",$tel_collect_task_modify);

	$smarty->assign("Belladdtask",$Belladdtask);

	//
	$sql = "SELECT taskname,israndomplay,timelengthtype,timelength,prepower,datasendmodel,startdate,enddate,priority,";
	
	$sql.="playtime,exemodel,channel,bandrate,samplerate,cmdargs,defaultvolume FROM audioserver.task WHERE task.taskid = '$_GET[id]' ";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result))
	{
		$taskinfo = array(
							"taskname"=>$row['taskname'],"israndomplay"=>$row['israndomplay'],
							"timelengthtype"=>$row['timelengthtype'],"timelength"=>$row['timelength'],
							"prepower"=>$row['prepower'],"datasendmodel"=>$row['datasendmodel'],
							"startdate"=>$row['startdate'],"enddate"=>$row['enddate'],"playtime"=>$row['playtime'],
							"exemodel"=>$row['exemodel'],"channel"=>$row['channel'],"bandrate"=>$row['bandrate'],
							"samplerate"=>$row['samplerate'],"cmdargs"=>$row['cmdargs'],
							"defaultvolume"=>$row['defaultvolume'],"priority"=>$row['priority']
						); 
	}

	
	$smarty->assign("taskinfo",$taskinfo);
	
	@mysqli_free_result($result);
	
	unset($taskinfo);
	
	//取选择的终端
	//$sql = "select	terminalid from terminaloftask where terminaloftask.taskid = '$_GET[id]'";
	
	//$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	//while($row = mysqli_fetch_array($result))
	//{
		$terminalid =  get_current_task_termianl_id($_GET[id]);
	//}
	
	$smarty->assign("terminalid",$terminalid);
	
	
	@mysqli_free_result($result);
	unset($terminalid,$row,$sql);
	
	//$type =  "(1,3,4)";	
	
	$type = "1,3,4,5,11,13,14,15,6";
	
	//$terminalist = get_grouped_terminal($type);
	
	$terminalist = create_tree_str($type);
	
	//$terminalist = get_terminallist($type, 0);
	
	$smarty->assign("terminalist",$terminalist);
	
	$audiosourcelist = get_audiosource();
	
	$smarty->assign("audiosourcelist",$audiosourcelist);
	
	$smarty->assign("taskid",$_GET['id']);		
	
	$smarty->display("TelBroadManager/modifytelBroadManager.html");
}
?>