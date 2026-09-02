<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');
//��֤�Ƿ�ʧЧ
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	require_once('login.php');	
}
else
{		
	//��ʾ������
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("tel_collect_task_modify",$tel_collect_task_modify);

	$smarty->assign("Belladdtask",$Belladdtask);
	
	$cmdargs=array();
	$webradio_sql = "select id, netradiocount from serverbaseparam order by id desc ";
	$sql_result = mysqli_query($con,$webradio_sql) or die(mysqli_error($con));
	
	while($row_sql = mysqli_fetch_array($sql_result))
	{
	
	$cmdargs[] = array("id"=>$row_sql['id'],"netradiocount"=>$row_sql['netradiocount']);
	}
	$smarty->assign("cmdargs",$cmdargs);
	@mysqli_free_result($sql_result);
   
	unset($array1,$row_sql);

	$taskinfo=array();
	$getid=$_GET['id'];
	$sql = "SELECT taskname,israndomplay,timelengthtype,timelength,prepower,datasendmodel,startdate,enddate,priority,";
	
	$sql.="playtime,exemodel,channel,bandrate,samplerate,cmd,cmdargs,defaultvolume FROM audioserver.task WHERE task.taskid = '$getid'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result))
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
	
	
	
	//ȡѡ����ն�
	//$sql = "select	terminalid from terminaloftask where terminaloftask.taskid = '$_GET[id]'";
	
	//$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	//while($row = mysqli_fetch_array($result))
	//{
		$terminalidlist =  get_current_task_termianl_id($getid);
	//}
	
	$smarty->assign("terminalidlist",$terminalidlist);
	
	

	unset($terminalidlist,$row,$sql);
	
	//$type =  "(1,3,4)";	
 $type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	
 $userid=$_SESSION['userid'];

 $results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
	if($row = mysqli_fetch_array($results))
	{
		$getlevel=$row['level'];
	}
   
	$smarty->assign("getlevel",$getlevel);
	
	//$terminalist = get_grouped_terminal($type);
	
	$terminalist = create_tree_str($type);
	
	//$terminalist = get_terminallist($type, 0);
	
	$smarty->assign("terminalist",$terminalist);
	
	$audiosourcelist = get_audiosource();
	
	$smarty->assign("audiosourcelist",$audiosourcelist);
	
	$smarty->assign("taskid",$_GET['id']);		
	
	$smarty->display("WebRadio/webradiomodify.html");
}
?>