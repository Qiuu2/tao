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
	require_once('login.php');	
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("modify_bell_scheme",$modify_bell_scheme);

	$smarty->assign("Belladdtask",$Belladdtask);
#if 1

	$str = "<?xml version='1.0' encoding='UTF-8'?> <tree id=\"0\">";
	
	$resultstream=	mysqli_query($con,"SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream");
	while ($rowstream = mysqli_fetch_array($resultstream))
	{			
		$streamid = $rowstream['streamid'];
		$str = "<item text=\"".$rowstream['name']."\" id=\"stream_".$streamid."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >";

		$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.groupid=$streamid");
		while ($rowterminal = mysqli_fetch_array($resultterminal)) 
		{	
			$str = "<item text=\"".$rowterminal['terminalname']."\" id=\"".$rowterminal['id']."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" > \n </item>\n";
	
		}							 
	}		
	
#else
	//$type =  "(1,3,4)";	
	
	//$terminalist = get_terminallist($type, 0);
	

	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	//$terminalist = get_grouped_terminal($type);
	
	$terminalist = create_tree_str($type);
	
	/*$terminalist_tmp = xml_str_analyze($terminalist);
	
	if(empty($terminalist_tmp))
	{
		echo "<script>alert('".$modify_bell_scheme['not_add_type_terminal']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
	}*/

	$smarty->assign("terminalist",$terminalist);
	$username=$_SESSION['username'];
		
	$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE username ='$username')");
	if($row = mysqli_fetch_array($results))
	{
		$getlevel=$row['level'];
	}

	$smarty->assign("getlevel",$getlevel);
#endif	 
 	  
	$getid=$_GET['id'];

	$sql="SELECT prepower,startdate,enddate,exemodel,info,priority,task_user_id FROM task WHERE task.taskid =$getid AND task.tasktype IN(1,15)";

	$result=mysqli_query($con,$sql)or die(mysqli_error($con));
	$bellinfo=array();
	if($row=mysqli_fetch_array($result))
	{
		$bellinfo = array(
							"prepower"=>$row['prepower'],"startdate"=>$row['startdate'],"enddate"=>$row['enddate'],
							
							"exemodel"=>$row['exemodel'],"info"=>$row['info'],"priority"=>$row['priority'],"task_user_id"=>$row['task_user_id']
						);
	}
	$smarty->assign("bellinfo",$bellinfo);
	
	mysqli_free_result($result);
	
	unset($bellinfo,$sql);

	$sql = "SELECT task.taskid,task.taskname,task.timelengthtype,task.timelength,task.playtime,mediaoftask.mediaid,task.priority,media.timelength ";
	
	$sql.= "FROM task,mediaoftask,media WHERE  (task.info = (SELECT task.info FROM task WHERE taskid = '$_GET[id]' AND task.tasktype IN(1,15))) ";
	
	$sql.= "AND mediaoftask.taskid = task.taskid AND task.tasktype IN(1,15) AND media.id=mediaoftask.mediaid ORDER BY playtime asc";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	$lessoninfo=array();
	while($row = mysqli_fetch_array($result))
	{
		$lessoninfo[]=array("taskid"=>$row['taskid'],"taskname"=>$row['taskname'],"timelengthtype"=>$row['timelengthtype'],"timelength"=>$row[3],"playtime"=>$row['playtime'],"mediaid"=>$row['mediaid'],"priority"=>$row['priority'],"mediatimelength"=>$row[7]);
	}
	
	$smarty->assign("lessoninfo",$lessoninfo);
	
	mysqli_free_result($result);
	
	unset($lessoninfo,$sql);
	
	if($_SESSION['username']=='admin')
	{
		$result = mysqli_query($con,"SELECT media.id, media.name,timelength FROM media WHERE media.folderid IN(SELECT id FROM filefolder WHERE filefolder.parentid = 2 or filefolder.parentid IN(SELECT id FROM filefolder WHERE filefolder.parentid =2)) or media.folderid = 2 order by id desc");
	}
	else
	{
		$userid=$_SESSION['userid'];
		$result = mysqli_query($con,"SELECT media.id, media.name,timelength FROM media WHERE media.folderid IN(SELECT id FROM filefolder WHERE  userid='$userid'AND filefolder.parentid = 2 or filefolder.parentid IN(SELECT id FROM filefolder WHERE filefolder.parentid =2) ) or media.folderid = 2 order by id desc");
	}
	
	
	if(mysqli_fetch_array($result))
	{
		mysqli_data_seek($result, 0);
		$gettemp=0;
		while($row = mysqli_fetch_array($result))
		{
			if($gettemp==0)
				$mediatimelength=$row['timelength'];
			$gettemp++;
			$medialist[] = array("id"=>$row['id'],"name"=>$row['name']);
		}	
	}
/*	$results = mysqli_query($con,"SELECT DISTINCT ttssentence.name,ttssentence.sentenceid FROM ttssentence ");
	while($row = mysqli_fetch_array($results))
		{
			$medialist[] = array("id"=>'tts+'.$row[1],"name"=>$row[0]);
		}*/
		$smarty->assign("medialist",$medialist);
	
		mysqli_free_result($result);
	
		unset($medialist,$sql);
		
		$termianl_sql = "SELECT terminalid,groupid,area FROM terminaloftask WHERE terminaloftask.taskid = $getid ORDER BY groupid ";
	
	$termianl_result = mysqli_query($con,$termianl_sql) or die(mysqli_error($con));
	$area=array();
	while($termianl_row = mysqli_fetch_array($termianl_result))
	{
		$area[] = array("area"=>$termianl_row['area']);
	}
	
	
	$smarty->assign("area",$area);
	mysqli_free_result($termianl_result);
	unset($termianl_sql,$termianl_row);
	$terminalidlist = get_current_task_termianl_id($_GET['id']);
	//}

	$smarty->assign("terminalidlist",$terminalidlist);
			
	unset($terminalid);
		
	$username=$_SESSION['username'];
	$getlevel=0;
	$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE username ='$username')");
	if($row = mysqli_fetch_array($results))
	{
		$getlevel=$row['level'];
	}

	$smarty->assign("getlevel",$getlevel);
	
	$smarty->assign("taskid",$_GET['id']);
	$smarty->assign("mediatimelength",$mediatimelength);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("BellManager/modifybell.html");
}
?>