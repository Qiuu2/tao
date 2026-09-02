<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if( empty($_SESSION['admin_id']) )
{
	header('Location:login.php');
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	
	$smarty->assign("language",$_SESSION['language']);
	
	$smarty->assign("add_bell_scheme",$add_bell_scheme);

	$smarty->assign("Belladdtask",$Belladdtask);
	$getid=$_GET['id'];
	$userid=$_SESSION['userid'];

	$info= array();
	$sqls = "SELECT taskid,taskname,projectstate,timelengthtype,timelength,startdate,enddate,playtime,exemodel,info FROM task WHERE tasktype='1' AND info IN(select info from task where taskid='$getid') order by playtime ASC";
	$results	=	mysqli_query($con,$sqls);
	while ($rowss = mysqli_fetch_array($results)) 	
	{
		$sechoname=$rowss['info'].'1';
		$info[]=array(
			"taskid" => $rowss['taskid'],
			"taskname" => $rowss['taskname'],
			"projectstate" => $rowss['projectstate'],
			"timelengthtype" => $rowss['timelengthtype'],
			"timelength" => $rowss['timelength'],
			"startdate" => $rowss['startdate'],
			"enddate" => $rowss['enddate'],
			"playtime" => $rowss['playtime'],
			"exemodel" => $rowss['exemodel'],
			"info" => $rowss['info']
		);	
	}
	@mysqli_free_result($results);
	unset($rowss,$sqls);
	$smarty->assign("sechoname",$sechoname);
	$smarty->assign("info",$info);
	$smarty->assign("is_right",1);
	$smarty->assign("getid",$getid);
	$smarty->display("BellManager/modifybelltime.html");
}
?>

