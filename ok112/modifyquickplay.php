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
	/*动态显示页面文本内容*/
	$terminal_id = "";
	if(isset($_GET['id']))
	{
		$terminal_id = trim($_GET['id']);
		$_SESSION['modifyterminalid'] = $terminal_id;
	}
	if($terminal_id == "")
	{
		$terminal_id = $_SESSION['modifyterminalid'];
	}

	$getid = "";
	if(isset($_GET['getid']))
	{
		$getid = trim($_GET['getid']);
	}

	
	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	
	$tsql = "select typeid from terminal where id = '$terminal_id'";
	$tresult = mysqli_query($con,$tsql) or die(mysqli_error($con));
	if($row = mysqli_fetch_array($tresult))
	{
		$smarty->assign("typeid",$row['typeid']);
	}
	
	
	$terminalist = get_quick_terminal($type,$terminal_id);
	
	$userid=$_SESSION['userid'];
	$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
	if($row = mysqli_fetch_array($results))
	{
		$getlevel=$row['level'];
	}
	
	$temp = explode("/",$getid);
	$keyid=intval($temp[1]);
	$taskid=intval($temp[0]);
	
	$getsql ="SELECT task.taskid,keyid,taskname,israndomplay,timelengthtype,timelength,datasendmodel,priority,defaultvolume,tasktype,cmd FROM terminalkeymaptask,task WHERE terminalkeymaptask.taskid=task.taskid AND keyid=$keyid AND terminalkeymaptask.taskid=$taskid AND tasktype in(20,21,29)";
	
	$sql = mysqli_query($con,$getsql);
	if($row = mysqli_fetch_array($sql))
	{
		$cmdsss=$row['cmd'];
		$taskinfo = array("taskid"=>$row['taskid'],"keyid"=>$row['keyid'],"taskname"=>$row['taskname'],"israndomplay"=>$row['israndomplay'],"timelengthtype"=>$row['timelengthtype']
		,"timelength"=>$row['timelength'],"datasendmodel"=>$row['datasendmodel'],"priority"=>$row['priority'],"defaultvolume"=>$row['defaultvolume'],"tasktype"=>$row['tasktype'],"cmd"=>$row['cmd']);
		
	}
	
	$smarty->assign("taskinfo",$taskinfo);
	@mysqli_free_result($sql);
	unset($taskinfo);


	$smarty->assign("userid",$userid);
	$smarty->assign("getlevel",$getlevel);

    $smarty->assign("terminal_id",$terminal_id);
	$smarty->assign("terminalist",$terminalist);

	$filelist = get_filelist($_SESSION['username']);
	$smarty->assign("filelist",$filelist);	
	
	

	
	
		$sql = "SELECT content,speed,volume,male FROM ttssentence WHERE sentenceid IN(SELECT mediaid FROM mediaoftask WHERE taskid='$taskid') ORDER BY mediaseq ASC";
		$i = 0;
		$ttsplay=0;
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
				$textarea=$row['content'];
				$textspeed=$row['speed'];
				$textmale=$row['male'];
				$ttsplay=1;
			}
			else
			{
			$textspeed=$row['speed'];
			$textvolume=$row['volume'];
			$textmale=$row['male'];		
			$textarea=$textarea.$kongge.$row['content'];
			}
			$i++;
		}
		$textarea=str_replace('"','\"',$textarea);
		
		@mysqli_free_result($result);
		unset($mediaid);
		$smarty->assign("ttsplay",$ttsplay);
		$smarty->assign("textarea",$textarea);
		$adm_terminal_sql = "select id, terminalname,typeid from terminal where terminal.typeid in(0,22,32)";
		$adm_terminal_result = mysqli_query($con,$adm_terminal_sql) or die(mysqli_error($con));
		if(mysqli_num_rows($adm_terminal_result) > 0)
		{
			while($adm_terminal_row = mysqli_fetch_array($adm_terminal_result))
			{
				if($adm_terminal_row['id']==$cmdsss)
				{
				
					if($adm_terminal_row['typeid']==0)
					{
						$textspeed=$textspeed/10;
					}
				}
				$terminal_info[] = array("id"=>$adm_terminal_row['id'],"typeid"=>$adm_terminal_row['typeid'],"terminalname"=>$adm_terminal_row['terminalname']);	
			}
			$smarty->assign("terminal_info",$terminal_info);
			@mysqli_free_result($adm_terminal_result);
			unset($adm_terminal_sql,$adm_terminal_row,$terminal_info);
		}
		else
		{
		
		}
		
		$smarty->assign("textspeed",$textspeed);
		$smarty->assign("textmale",$textmale);
	
	
		$sql = "select mediaid from mediaoftask where mediaoftask.taskid = '$taskid'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		while($row = mysqli_fetch_array($result))
		{
			$fileidlist[] = array("fileid"=>$row['mediaid']); 			
		}
		
		$smarty->assign("fileidlist",$fileidlist);
		@mysqli_free_result($result);
		unset($mediaid);

	$ledtype=get_terminal_type(14,$do_php_prompt['Terminal_not_support'],0,0);
	$ledlist = create_led_tree_str($ledtype);
	$smarty->assign("ledlist",$ledlist);
		
	
	//取媒体文�?
		$sql = "SELECT text FROM ledsentence WHERE mediaid IN(SELECT mediaid FROM mediaoftask,task WHERE task.taskid=mediaoftask.taskid and sec_task_id=$taskid and tasktype=24) ORDER BY mediaseq ASC";
		
		$i = 0;
		$getledtextareas="";
		$textspeed=0;
		$textvolume=0;
		$textmale=0;
		$kongge='\n';

		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		while($row = mysqli_fetch_array($result))
		{
			if($i==0)
			{
				$getledtextareas=$row['text'];	
			}
			else
			{
		
			$getledtextareas=$getledtextareas.$kongge.$row['text'];
			}
			$i++;
		}
		$getledtextareas=str_replace('"','\"',$getledtextareas);
		
		@mysqli_free_result($result);
		unset($mediaid);
	
	$smarty->assign("getledtextareas",$getledtextareas);
	$led_task_sql = "SELECT terminalid,deviceid FROM ledoftask WHERE ledoftask.taskid in(select taskid from task where sec_task_id= $taskid and tasktype=24) ORDER BY terminalid ";
	
	$led_termianl_result = mysqli_query($con,$led_task_sql) or die(mysqli_error($con));
	
	while($led_termianl_row = mysqli_fetch_array($led_termianl_result))
	{
		$led_termianl[] = array("terminal_id"=>$led_termianl_row['deviceid'],"group_id"=>$led_termianl_row['terminalid']);
	}
	$smarty->assign("led_termianl",$led_termianl);
	@mysqli_free_result($led_termianl_result);
	
	unset($led_task_sql,$led_termianl_row);

		$terminalidlist = get_current_task_termianl_id($taskid);

		$smarty->assign("terminalidlist",$terminalidlist);

	$smarty->display("TerminalManager/modify_task_quickplay.html");
}
?>
