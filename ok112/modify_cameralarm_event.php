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
	header("location:login.php");
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("set_shotcut",$set_shotcut);
	$smarty->assign("Setterminalkey",$Setterminalkey);
	$getid = "";
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	
	//读取解码终端
 	 $sql_stream = "SELECT eventtype,eventname FROM camer_alarm WHERE id = '$getid'";
	 $result_stream = mysqli_query($con,$sql_stream) or die(mysqli_error($con));
	 while($row_stream = mysqli_fetch_array($result_stream))
	 {
		$smarty->assign("eventtype",$row_stream['eventtype']);
		$smarty->assign("eventname",$row_stream['eventname']);
	 }
	 @mysqli_free_result($result_stream);	
	 unset($row_stream,$sql_stream);
	$userid=$_SESSION['userid'];
	$filelist = get_baojingfilelist($con,$userid);
	$smarty->assign("filelist",$filelist);	
	
	$sql = "select mediaid from camer_alarmofmedia where eventid ='$getid' ORDER BY sort";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	$fileidlist=array();
	while($row = mysqli_fetch_array($result))
	{
		$fileidlist[] = array("fileid"=>$row['mediaid']); 			
	}
	$smarty->assign("fileidlist",$fileidlist);
	@mysqli_free_result($result);
	unset($row,$sql);
	
	$smarty->assign("getid",$_GET['id']);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("camer/modify_cameralarm_event.html");
}
?>