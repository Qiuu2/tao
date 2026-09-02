<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

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
	
	$smarty->assign("media_task_modify",$media_task_modify);

	$smarty->assign("Belladdtask",$Belladdtask);

	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	
	  $terminalist = create_tree_str($type);
	 
	  $smarty->assign("terminalist",$terminalist);
	  
	  $filelist = get_vediolist($_SESSION['username'],0);
	  
	  $smarty->assign("filelist",$filelist);	
	  $get_id=$_GET['id'];
		$sql = "SELECT 	taskname,israndomplay,timelengthtype,timelength,prepower,datasendmodel,startdate,enddate,priority,";
		$sql.= "playtime,exemodel, channel,bandrate, samplerate, defaultvolume,interval_s,intplaylength,intplaylengthtype FROM audioserver.task WHERE task.taskid = '$get_id'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		$taskinfo=array();
		while($row = mysqli_fetch_array($result))
		{
			$taskinfo = array(
									"taskname"=>$row['taskname'],"israndomplay"=>$row['israndomplay'],
									"timelengthtype"=>$row['timelengthtype'],"timelength"=>$row['timelength'],
									"prepower"=>$row['prepower'],"datasendmodel"=>$row['datasendmodel'],
									"startdate"=>$row['startdate'],"enddate"=>$row['enddate'],
									"playtime"=>$row['playtime'],"exemodel"=>$row['exemodel'],
									"channel"=>$row['channel'],"bandrate"=>$row['bandrate'],
									"samplerate"=>$row['samplerate'],"defaultvolume"=>$row['defaultvolume'],"priority"=>$row['priority'],
									"interval_s"=>$row['interval_s'],"intplaylength"=>$row['intplaylength'],"intplaylengthtype"=>$row['intplaylengthtype']
							 ); 
		}	
	
		$smarty->assign("taskinfo",$taskinfo);
	
		mysqli_free_result($result);
	
		unset($taskinfo);
	
		$termianl_sql = "SELECT terminalid,groupid,area FROM terminaloftask WHERE terminaloftask.taskid = '$get_id' ORDER BY groupid ";
	
	$termianl_result = mysqli_query($con,$termianl_sql) or die(mysqli_error($con));
	$area=array();
	while($termianl_row = mysqli_fetch_array($termianl_result))
	{
		$area[] = array("area"=>$termianl_row['area']);
	}
	
	
	$smarty->assign("area",$area);
	mysqli_free_result($termianl_result);
	
	unset($termianl_sql,$termianl_row);
		
		///////////////////////////////////////////////////////////////////////
		
		//$sql = "select	terminalid from terminaloftask where terminaloftask.taskid = '$_GET[id]'";
		//$i = 0;
		//$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		//while($row = mysqli_fetch_array($result))
		//{
			$terminalidlist = get_current_task_termianl_id($get_id);
		//}
		
		$smarty->assign("terminalidlist",$terminalidlist);
		
		//mysqli_free_result($result);
		
		unset($terminalid);
		
		//取媒体文�?
			
		$sql = "select mediaid from mediaoftask where mediaoftask.taskid = '$get_id'";
		
		$i = 0;
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		$fileidlist=array();
		while($row = mysqli_fetch_array($result))
		{
			$fileidlist[] = array("fileid"=>$row['mediaid']); 			
		}
		
		$smarty->assign("fileidlist",$fileidlist);
		
		mysqli_free_result($result);
		
		unset($mediaid);
		
		$userid=$_GET['userid'];
		$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
		$getlevel=array();
		if($row = mysqli_fetch_array($results))
		{
			$getlevel=$row['level'];
		}
		$smarty->assign("userid",$userid);
		
		$smarty->assign("getlevel",$getlevel);
		
		$smarty->assign("taskid",$_GET['id']);
		$smarty->assign("gettask",$_GET['gettask']);
		$smarty->display("videotaskmanager/videoModifyTask_form.html");
}
?>
