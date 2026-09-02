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
	$userid=$_SESSION['userid'];

	$info= array();
	$sqls = "SELECT distinct(info), projectstate FROM task WHERE sec_task_id='0' AND tasktype='1'";
	$results	=	mysqli_query($con,$sqls);
	while ($rowss = mysqli_fetch_array($results)) 	
	{
		
		$infoss=$rowss['info'];
		$projectstate=$rowss['projectstate'];
		$sechtaskid=0;
		$sql_sech_filead = "SELECT min(taskid) FROM task WHERE info = '$infoss'";
		$result_seche_filead = mysqli_query($con,$sql_sech_filead);
		if($row_sech_filead = mysqli_fetch_array($result_seche_filead))
		{
			$sechtaskid=$row_sech_filead[0];
		}
		@mysqli_free_result($result_seche_filead);

		unset($sql_sech_filead,$row_sech_filead);	
		$info[]=array(
			"taskid" => $sechtaskid,
			"taskname" => $rowss['info'],
			"projectstate" => $rowss['projectstate'],
			"tasktype" => '1'
		);	
	}
	@mysqli_free_result($results);
	unset($rowss,$sqls);

	$sql = "SELECT taskid,taskname,projectstate,tasktype FROM task WHERE sec_task_id='0'  AND tasktype IN(2,3,5,10,13,15,24)";
		$result	=	mysqli_query($con,$sql);
		while ($rows = mysqli_fetch_array($result)) 	
		{
			$info[]=array(
				"taskid" => $rows['taskid'],
				"taskname" => $rows['taskname'],
				"projectstate" => $rows['projectstate'],
				"tasktype" => $rows['tasktype']
			);	
		}
		@mysqli_free_result($result);
		unset($rows,$sql);
	

	$smarty->assign("info",$info);
	$smarty->assign("is_right",1);

$smarty->display("holidaymanager/addmanager.html");
}
?>

