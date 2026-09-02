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
	$smarty->assign("media_task_add",$media_task_add);
	$smarty->assign("Belladdtask",$Belladdtask);
	$type=get_terminal_type(16,$do_php_prompt['Terminal_not_support'],0,0);
	$terminalist = create_tree_str($type);
	  
	$smarty->assign("terminalist",$terminalist);
	  $get_id=$_GET['id'];
	  $filelist = get_filelist($_SESSION['username']);
	  
	  $smarty->assign("filelist",$filelist);	
	  
	  $result = mysqli_query($con,"SELECT media.id, media.name,timelength FROM media WHERE media.folderid = 9 order by id desc");
		$medialist=array();
		while($row = mysqli_fetch_array($result))
		{
			$medialist[] = array("id"=>$row['id'],"name"=>$row['name']);
		}

		$smarty->assign("medialist",$medialist);

		$taskinfo=array();
		$sql = "SELECT 	taskname,israndomplay,timelengthtype,timelength,prepower,datasendmodel,startdate,enddate,priority,";
		$sql.= "playtime,exemodel, channel,bandrate, samplerate, defaultvolume,cmd,interval_s,intplaylength,intplaylengthtype FROM audioserver.task WHERE task.taskid = '$get_id'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		while($row = mysqli_fetch_array($result))
		{
			$cmd=$row['cmd'];
			$taskinfo = array(
								"taskname"=>$row['taskname'],"israndomplay"=>$row['israndomplay'],
								"timelengthtype"=>$row['timelengthtype'],"timelength"=>$row['timelength'],
								"prepower"=>$row['prepower'],"datasendmodel"=>$row['datasendmodel'],
								"startdate"=>$row['startdate'],"enddate"=>$row['enddate'],
								"playtime"=>$row['playtime'],"exemodel"=>$row['exemodel'],
								"channel"=>$row['channel'],"bandrate"=>$row['bandrate'],
								"samplerate"=>$row['samplerate'],"defaultvolume"=>$row['defaultvolume'],"cmd"=>$row['cmd'],"priority"=>$row['priority'],"interval_s"=>$row['interval_s'],"intplaylength"=>$row['intplaylength'],"intplaylengthtype"=>$row['intplaylengthtype']
							 ); 
		}	
	
		$smarty->assign("taskinfo",$taskinfo);
	
		@mysqli_free_result($result);
	
		unset($taskinfo);
		
		$termianl_sql = "SELECT terminalid,groupid,area FROM terminaloftask WHERE terminaloftask.taskid = '$get_id' ORDER BY groupid ";
	
	$termianl_result = mysqli_query($con,$termianl_sql) or die(mysqli_error($con));
	$area=array();
	while($termianl_row = mysqli_fetch_array($termianl_result))
	{
		$area[] = array("area"=>$termianl_row['area']);
	}
	
	
	$smarty->assign("area",$area);
	@mysqli_free_result($termianl_result);
	
	unset($termianl_sql,$termianl_row);
	$terminal_info=array();
	//采播终端
	$adm_terminal_sql = "select id, terminalname,typeid from terminal where terminal.typeid in(0,22,32)";
	
	$adm_terminal_result = mysqli_query($con,$adm_terminal_sql) or die(mysqli_error($con));
	$server_id=0;
	if(mysqli_num_rows($adm_terminal_result) > 0)
	{
		while($adm_terminal_row = mysqli_fetch_array($adm_terminal_result))
		{
			$terminal_info[] = array("id"=>$adm_terminal_row['id'],"typeid"=>$adm_terminal_row['typeid'],"terminalname"=>$adm_terminal_row['terminalname']);	
			if($adm_terminal_row['typeid']==0)
		   {
		   	$server_id=$adm_terminal_row['id'];
		   	  $smarty->assign("server_id",$adm_terminal_row['id']);
		   }
		}
		
		$smarty->assign("terminal_info",$terminal_info);
		
		@mysqli_free_result($adm_terminal_result);
		
		unset($adm_terminal_sql,$adm_terminal_row,$terminal_info);
	}
	else
	{
	
	}
		///////////////////////////////////////////////////////////////////////
		
		//$sql = "select	terminalid from terminaloftask where terminaloftask.taskid = '$_GET[id]'";
		//$i = 0;
		//$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		//while($row = mysqli_fetch_array($result))
		//{
			$terminalidlist = get_current_task_termianl_id($_GET['id']);
		//}
		
		$smarty->assign("terminalidlist",$terminalidlist);
		
		unset($terminalid);
		
		//取媒体文�?
		$sql = "SELECT mediaid,content,speed,volume,male FROM ttssentence WHERE sentenceid IN(SELECT mediaid FROM mediaoftask WHERE taskid='$get_id') ORDER BY mediaseq ASC";
		$i = 0;
		$textarea="";
		$textspeed=0;
		$textspeedflag=0;
		$media_id=0;
		$textvolume=0;
		$textmale=0;
		$kongge='\n';
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		while($row = mysqli_fetch_array($result))
		{
			if($i==0)
			{
				$textarea=$row['content'];
				$textspeed=$row['speed'];
				$textvolume=$row['volume'];
				$textmale=$row['male'];
				if($row['mediaid']>0)
				{
					$media_id=$row['mediaid'];
					$textspeedflag=1;
				}
			
				
			}
			else
			{
			//$textspeed=$row['speed'];
			$textvolume=$row['volume'];
			$textmale=$row['male'];	
			$textspeed=$row['speed'];
			if($row['mediaid']>0)
			{
				$media_id=$row['mediaid'];
				$textspeedflag=1;
			}
			if($textarea==NULL||$textarea=="")
			{
			$textarea=$row['content'];
			}
			else	
			$textarea=$textarea.$kongge.$row['content'];
			
			
			}
			$i++;
		}
		$textarea=str_replace('"','\"',$textarea);
		@mysqli_free_result($result);
		if($textspeedflag==1)
		{
		
		}
		else
		{
			if($server_id!=$cmd)
			{
				$textspeed=$textspeed*10;
			}
		
		}
	
		$smarty->assign("media_id",$media_id);
		$smarty->assign("textarea",$textarea);
		$smarty->assign("textspeed",$textspeed);
		$smarty->assign("textvolume",$textvolume);
		$smarty->assign("textmale",$textmale);
		 $userid=$_SESSION['userid'];
		$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
			if($row = mysqli_fetch_array($results))
			{
				$getlevel=$row['level'];
			}
			$smarty->assign("getlevel",$getlevel);
			
		
		$smarty->assign("taskid",$_GET['id']);
		$smarty->display("Tts/TtsmodifyFileTask_form.html");
}
?>
