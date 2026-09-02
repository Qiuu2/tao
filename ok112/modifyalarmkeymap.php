<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
if(empty($_SESSION['admin_id']))
{
	header("location:login.php");	
}
else
{
	//验证失效
	require_once("verify_user_sessionin_valid.php");

	verifysessionvalid();

	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("alarm_setting",$alarm_setting);
	$smarty->assign("Setterminalkey",$Setterminalkey);
	//
	$id = "";
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
	}
	
	$sqls = "SELECT info,alarmterminalid,alarmchannel,firealarmgroupid,mediaid,terminal.channel FROM alarmgroupmap,terminal WHERE terminal.id=alarmgroupmap.id and alarmgroupmap.id = '$id'";	
	$results = mysqli_query($con,$sqls) or die(mysqli_error($con));	
	if(mysqli_num_rows($results) > 0)
	{
		if($rows = mysqli_fetch_array($results))
		{
			$alarmgroupmap = array("info"=>$rows['info'],"alarmterminalid"=>$rows['alarmterminalid'],"alarmchannel"=>$rows['alarmchannel'],"firealarmgroupid"=>$rows['firealarmgroupid'],"mediaid"=>$rows['mediaid'],"channel"=>$rows['channel']);
		}
		$smarty->assign("alarmgroupmap",$alarmgroupmap);
	}
	
	
	//
	$sql = "SELECT 	id, terminalname FROM terminal WHERE terminal.typeid = '7'";	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));	
	if(mysqli_num_rows($result) > 0)
	{
		while($row = mysqli_fetch_array($result))
		{
			$alarmterminal[] = array("alarmid"=>$row['id'],"terminalname"=>$row['terminalname']);
		}
		$smarty->assign("alarmterminal",$alarmterminal);
	}
	else if(mysqli_num_rows($result) <= 0)
	{
		echo "<script>alert('".$alarm_setting['No_Alarm_Host']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
	}
	
	@mysqli_free_result($result);
	
	unset($sql,$row,$alarmterminal);
	//
	
	$sql = "SELECT channel	FROM terminal WHERE terminal.typeid = '7'";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		$channel = $row['channel'];
	}
	
	$smarty->assign("channel",$channel);
	
	@mysqli_free_result($result);
	
	unset($row,$sql,$channel);
	
	$userid=$_SESSION['userid'];
	//
	if($_SESSION['username']=="admin")
	$sql = "SELECT id, name FROM alarmarea ";
	else
	$sql = "SELECT id, name FROM alarmarea WHERE userid='$userid'";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	while($row = mysqli_fetch_array($result))
	{
		$areainfo[] = array("areaid"=>$row['id'],"areaname"=>$row['name']);
	}
	
	$smarty->assign("areainfo",$areainfo);
	
	@mysqli_free_result($result);
	
	unset($row,$sql,$areainfo);
	//
	$sql = "SELECT 	media.id, media.name FROM media WHERE media.folderid = '4'OR folderid IN(SELECT id FROM filefolder WHERE parentid='4') OR folderid IN (SELECT id FROM filefolder WHERE parentid IN(SELECT id FROM filefolder WHERE parentid='4'))";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result) <= 0)
	{
		echo "<script>alert('".$alarm_setting['No_Alarm_Media']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
	}
	
	while($row = mysqli_fetch_array($result))
	{
		$mediainfo[] = array("mediaid"=>$row['id'],"medianame"=>$row['name']);
	}
	$smarty->assign("mediainfo",$mediainfo);
	@mysqli_free_result($result);
	unset($row,$sql,$mediainfo);
	$smarty->assign("id",$id);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("alarmmanager/modifyalarmmap.html");
}
?>