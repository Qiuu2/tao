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
$get_id=$_GET['id'];
//	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);

	//  $terminalist = create_tree_str($type);
	 // $smarty->assign("terminalist",$terminalist);
	  
	  $ledtype=get_terminal_type(14,$do_php_prompt['Terminal_not_support'],0,0);
	$ledlist = create_led_tree_str($ledtype);
	  
	   $smarty->assign("ledlist",$ledlist); 
	//  $filelist = get_filelist($_SESSION['username']);
	  
	 // $smarty->assign("filelist",$filelist);	
	  
		$sql = "SELECT 	taskname,israndomplay,timelengthtype,timelength,prepower,datasendmodel,startdate,enddate,priority,";
		$sql.= "playtime,exemodel, channel,bandrate, samplerate, defaultvolume,interval_s,intplaylength,intplaylengthtype FROM audioserver.task WHERE task.taskid = '$get_id'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
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
	
		@mysqli_free_result($result);
	
		unset($taskinfo);
		
		$termianl_sql = "SELECT terminalid,groupid,area FROM terminaloftask WHERE terminaloftask.taskid = '$get_id' ORDER BY groupid ";
	
	$termianl_result = mysqli_query($con,$termianl_sql) or die(mysqli_error($con));
	
	while($termianl_row = mysqli_fetch_array($termianl_result))
	{
		$area[] = array("area"=>$termianl_row['area']);
	}
	
	
	$smarty->assign("area",$area);
	@mysqli_free_result($termianl_result);
	
	unset($termianl_sql,$termianl_row);
	
	
	//取媒体文�?
		$sql = "SELECT text FROM ledsentence WHERE mediaid IN(SELECT mediaid FROM mediaoftask,task WHERE task.taskid=mediaoftask.taskid and cmdargs='$get_id' and tasktype='24') ORDER BY mediaseq ASC";
		$i = 0;
		$textarea="";
		$textspeed=0;
		$textvolume=0;
		$textmale=0;
		$kongge='\n';
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		while($row = mysqli_fetch_array($result))
		{
			if($i==0)
			{
				$textarea=$row['text'];
			//	$textspeed=$row['speed'];
			//	$textvolume=$row['volume'];
			//	$textmale=$row['male'];
				
			}
			else
			{
		//	$textspeed=$row['speed'];
		//	$textvolume=$row['volume'];
		//	$textmale=$row['male'];		
		
			$textarea=$textarea.$kongge.$row['text'];
			
			
			}
			$i++;
		}
		$textarea=str_replace('"','\"',$textarea);
		
		@mysqli_free_result($result);
		unset($mediaid);
		$smarty->assign("textarea",$textarea);
		//$smarty->assign("textspeed",$textspeed);
		//$smarty->assign("textvolume",$textvolume);
		//$smarty->assign("textmale",$textmale);
	
	
	
	
	$led_task_sql = "SELECT terminalid,deviceid FROM ledoftask WHERE ledoftask.taskid in(select taskid from task where cmdargs= '$get_id' and tasktype='24') ORDER BY terminalid ";
	
	$led_termianl_result = mysqli_query($con,$led_task_sql) or die(mysqli_error($con));
	
	while($led_termianl_row = mysqli_fetch_array($led_termianl_result))
	{
		$led_termianl[] = array("terminal_id"=>$led_termianl_row['deviceid'],"group_id"=>$led_termianl_row['terminalid']);
	}
	$smarty->assign("led_termianl",$led_termianl);
	@mysqli_free_result($led_termianl_result);
	
	unset($led_task_sql,$led_termianl_row);
		

		///////////////////////////////////////////////////////////////////////
		
		//$sql = "select	terminalid from terminaloftask where terminaloftask.taskid = '$_GET[id]'";
		//$i = 0;
		//$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		//while($row = mysqli_fetch_array($result))
		//{
			$terminalidlist = get_current_task_termianl_id($get_id);
		//}
		
		$smarty->assign("terminalidlist",$terminalidlist);
		
		//@mysqli_free_result($result);
		
		unset($terminalid);
		
		//取媒体文�?
		
		$sql = "select mediaid from mediaoftask where mediaoftask.taskid = '$get_id'";
		
		$i = 0;
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		while($row = mysqli_fetch_array($result))
		{
			$fileidlist[] = array("fileid"=>$row['mediaid']); 			
		}
		
		$smarty->assign("fileidlist",$fileidlist);
		
		@mysqli_free_result($result);
		
		unset($mediaid);
		
		$userid=$_GET['userid'];
		$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
		if($row = mysqli_fetch_array($results))
		{
			$getlevel=$row['level'];
		}
		$smarty->assign("userid",$userid);
		
		$smarty->assign("getlevel",$getlevel);
		
		$smarty->assign("taskid",$_GET['id']);
		$smarty->assign("gettask",$_GET['gettask']);
		$smarty->display("ledmanager/ModifyFileTask_form.html");
}
?>
