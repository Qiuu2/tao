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
	$get_id=$_GET['id'];
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
	
#endif	  	  
	
	$sql="SELECT prepower,startdate,enddate,exemodel,info ";
	
	$sql.="FROM task WHERE task.taskid ='$get_id' AND task.tasktype = '1'";
	
	$result=mysqli_query($con,$sql)or die(mysqli_error($con));
	
	if($row=mysqli_fetch_array($result))
	{
		$bellinfo = array(
							"prepower"=>$row['prepower'],"startdate"=>$row['startdate'],"enddate"=>$row['enddate'],
							
							"exemodel"=>$row['exemodel'],"info"=>$row['info']
						);
	}
	$smarty->assign("bellinfo",$bellinfo);
	
	@mysqli_free_result($result);
	
	unset($bellinfo,$sql);
	
	$sql = "SELECT task.taskid,task.taskname,task.timelengthtype,task.timelength,task.playtime,mediaoftask.mediaid  ";
	
	$sql.= "FROM task,mediaoftask WHERE  (task.info = (SELECT task.info FROM task WHERE taskid = '$get_id' AND task.tasktype = 1)) ";
	
	$sql.= "AND mediaoftask.taskid = task.taskid AND task.tasktype = 1";
	
	$result = mysqli_query($con,$sql) or die("error".$sql.mysqli_error($con));
	
	while($row = mysqli_fetch_array($result))
	{
		$lessoninfo[]=array(
								"taskid"=>$row['taskid'],"taskname"=>$row['taskname'],"timelengthtype"=>$row['timelengthtype'],"timelength"=>$row['timelength'],
								
								"playtime"=>$row['playtime'],"mediaid"=>$row['mediaid']
							);
	}
	
	$smarty->assign("lessoninfo",$lessoninfo);
	
	@mysqli_free_result($result);
	
	unset($lessoninfo,$sql);
	$result = mysqli_query($con,"SELECT media.id, media.name FROM media WHERE media.folderid IN(SELECT id FROM filefolder WHERE filefolder.parentid = 2||filefolder.parentid IN(SELECT id FROM filefolder WHERE filefolder.parentid =2)) || media.folderid = 2 order by id desc");

	if(mysqli_fetch_array($result))
	{
		@mysqli_data_seek($result, 0);
	
		while($row = mysqli_fetch_array($result))
		{
			$medialist[] = array("id"=>$row['id'],"name"=>$row['name']);
		}
	
		$smarty->assign("medialist",$medialist);
	
		mysqli_free_result($result);
	
		unset($medialist,$sql);
	}
	
		
	$termianl_sql = "SELECT terminalid,groupid,area FROM terminaloftask WHERE terminaloftask.taskid = '$get_id' ORDER BY groupid ";
	
	$termianl_result = mysqli_query($con,$termianl_sql) or die(mysqli_error($con));
	
	while($termianl_row = mysqli_fetch_array($termianl_result))
	{
		$area[] = array("area"=>$termianl_row['area']);
	}
	
	
	$smarty->assign("area",$area);
	@mysqli_free_result($termianl_result);
	
	unset($termianl_sql,$termianl_row);
	
		$terminalidlist = get_current_task_termianl_id($get_id);
		//}
		
		$smarty->assign("terminalidlist",$terminalidlist);
		
		@mysqli_free_result($result);
		
		unset($terminalid);
	
	$smarty->assign("taskid",$_GET['id']);

	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("stopmanager/modifystopmanager.html");
}
?>