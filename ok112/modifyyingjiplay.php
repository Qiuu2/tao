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
	$terminalist = get_quick_terminal($type,$terminal_id);
	
	$userid=$_SESSION['userid'];
	$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
	if($row = mysqli_fetch_array($results))
	{
		$getlevel=$row['level'];
	}
	
	$temp = explode("/",$getid);
	$keyid=$temp[1];
	$taskid=$temp[0];
	
	$getsql="SELECT terminalkeymaptask.taskid,terminalkeymaptask.keyid,taskname,israndomplay,timelengthtype,timelength,datasendmodel,priority,defaultvolume,tasktype,cmd FROM terminalkeymaptask,task WHERE terminalkeymaptask.taskid=task.taskid AND terminalkeymaptask.keyid='$keyid' and terminalkeymaptask.taskid='$taskid' ";
	
	$sql = mysqli_query($con,$getsql);
	while($row = mysqli_fetch_array($sql))
	{
		$taskinfo = array("taskid"=>$row['taskid'],"keyid"=>$row['keyid'],"taskname"=>$row['taskname'],"defaultvolume"=>$row['defaultvolume']);
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

	$sql = "select mediaid from mediaoftask where mediaoftask.taskid = '$taskid'";

	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result))
	{
		$fileidlist[] = array("fileid"=>$row['mediaid']); 			
	}
	
	$smarty->assign("fileidlist",$fileidlist);
	@mysqli_free_result($result);
	
	unset($mediaid);

	$terminalidlist = get_current_task_termianl_id($taskid);

	$smarty->assign("terminalidlist",$terminalidlist);

	$smarty->display("TerminalManager/modify_yingjiplay.html");
}
?>
