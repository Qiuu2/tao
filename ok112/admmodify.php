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
	header('location:login.php');
}
else
{		
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	
	$smarty->assign("language",$_SESSION['language']);
	
	$smarty->assign("collect_task_modify",$collect_task_modify);

	$smarty->assign("Belladdtask",$Belladdtask);
	$get_id=$_GET['id'];
	//取所有采播终端名称
	$adm_terminal_sql = "select id, terminalname from terminal where terminal.typeid = '8'||terminal.typeid ='0'|| terminal.typeid = '25'|| terminal.typeid = '31'";
	
	$adm_terminal_result = mysqli_query($con,$adm_terminal_sql) or die(mysqli_error($con));
	
	while($adm_terminal_row = mysqli_fetch_array($adm_terminal_result))
	{
		$terminal_info[] = array("id"=>$adm_terminal_row['id'],"terminalname"=>$adm_terminal_row['terminalname']);	
	}
	
	$smarty->assign("terminal_info",$terminal_info);
	
	@mysqli_free_result($adm_terminal_result);
	
	unset($adm_terminal_sql,$adm_terminal_row,$terminal_info);
	//取所有采播终端通道数
	$adm_type_sql = "SELECT switchcount FROM terminaltype WHERE terminaltype.id = '8'||terminaltype.id ='0'|| terminaltype.id = '25'|| terminaltype.id = '31'";
	
	$adm_type_result = mysqli_query($con,$adm_type_sql) or die(mysqli_error($con));
	
	if($adm_type_row = mysqli_fetch_array($adm_type_result))
	{
		$smarty->assign("adm_channel",$adm_type_row['switchcount']);
	}
	
	@mysqli_free_result($adm_type_result);
	
	unset($adm_type_sql,$adm_type_row);

	#if 0
	$str = "<?xml version='1.0' encoding='UTF-8'?> <tree id=\"0\">";

	
	$resultstream=	mysqli_query($con,"SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream");
	
	while($rowstream = mysqli_fetch_array($resultstream))
	{			
		$streamid = $rowstream['streamid'];
		
		$str = "<item text=\"".$rowstream['name']."\" id=\"stream_".$streamid."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >";
		
		$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.groupid=$streamid");
		
		while($rowterminal = mysqli_fetch_array($resultterminal)) 
		{	
			$str = "<item text=\"".$rowterminal['terminalname']."\" id=\"".$rowterminal['id']."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" > \n</item>\n";
					  
		}							 
				
	}		
	
	#endif

$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	
	
	$terminalist = create_tree_str($type);
	
	$smarty->assign("terminalist",$terminalist);
	
	$sql = "SELECT 	taskname,israndomplay,timelengthtype,timelength,prepower,datasendmodel,startdate,enddate,priority,";
	
	$sql.= "playtime,exemodel,channel,bandrate,samplerate,cmd,cmdargs,defaultvolume FROM audioserver.task WHERE task.taskid = '$get_id'";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		$taskinfo = array(
							"taskname"=>$row['taskname'],"israndomplay"=>$row['israndomplay'],
							
							"timelengthtype"=>$row['timelengthtype'],"timelength"=>$row['timelength'],
							
							"prepower"=>$row['prepower'],"datasendmodel"=>$row['datasendmodel'],
							
							"startdate"=>$row['startdate'],"enddate"=>$row['enddate'],"playtime"=>$row['playtime'],
							
							"exemodel"=>$row['exemodel'],"channel"=>$row['channel'],"bandrate"=>$row['bandrate'],
							
							"samplerate"=>$row['samplerate'],"cmd"=>$row['cmd'],"cmdargs"=>$row['cmdargs'],
							
							"defaultvolume"=>$row['defaultvolume'],"priority"=>$row['priority']
						 ); 
	}
	
	$smarty->assign("taskinfo",$taskinfo);
	
	@mysqli_free_result($result);
	
	unset($taskinfo);
	
	//读取采播终端欲开电源
	$coll_repower_len = 0;

	$col_repower_sql = "SELECT prepower FROM task WHERE task.sec_task_id = '$get_id' AND task.channel = 0 AND task.info = '' and tasktype =8 ";
	
	$col_repower_result = mysqli_query($con,$col_repower_sql) or die(mysqli_error($con));
	
	if($col_repower_row = mysqli_fetch_array($col_repower_result))
	{
		$coll_repower_len = $col_repower_row['prepower'];
	}
	
	$smarty->assign("coll_repower",$coll_repower_len);
	
	@mysqli_free_result($col_repower_result);
	
	unset($col_repower_row,$col_repower_sql,$coll_repower_len);
	
	//取采播终端任务
	$col_terminal_id = "";
	
	$col_terminal_sql = "select cmd FROM task WHERE  ";
	
	$col_terminal_sql.= "taskid = '$get_id' and task.tasktype = 3 ";
	
	$col_terminal_result = mysqli_query($con,$col_terminal_sql) or die(mysqli_error($con));
	
	if($col_terminal_row = mysqli_fetch_array($col_terminal_result))
	{
		$col_terminal_id = $col_terminal_row['cmd'];
	}
		
	$smarty->assign("col_terminal_id",$col_terminal_id);
	
	@mysqli_free_result($col_terminal_result);
	
	unset($col_terminal_sql,$col_terminal_row,$col_terminal_id);
#if 0
	$sql = "select	terminalid from terminaloftask where terminaloftask.taskid = '$get_id'";
	$i = 0;
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result))
	{
		$terminalid[$i] = $row['terminalid'];
		$i++;
	}
#endif
	//$terminalidlist = get_selectTerminalid($_GET[id]);
	
	$terminalidlist = get_current_task_termianl_id($get_id);
	
	$smarty->assign("terminalidlist",$terminalidlist);
	
	unset($terminalidlist);
	
	 $userid=$_SESSION['userid'];
	$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
	if($row = mysqli_fetch_array($results))
	{
		$getlevel=$row['level'];
	}
	$smarty->assign("getlevel",$getlevel);
	
	$audiosourcelist = get_audiosource();
	
	$smarty->assign("audiosourcelist",$audiosourcelist);
	
	unset($audiosourcelist);

	$smarty->assign("taskid",$_GET['id']);	
		
	$smarty->display("AdmManger/AdmModify.html");
}
?>