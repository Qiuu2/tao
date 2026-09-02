<?php
	header("content-type:text/html;charset=utf-8");
	
	require_once('inc/config.inc.php');

	$task_id = "";
	
	if(isset($_GET['id']))
	{
		$task_id = trim($_GET['id']);
	}
	$task_time = "";
	if(isset($_GET['gettasktime']))
	{
		$task_time = trim($_GET['gettasktime']);
	}
	
	$arrtime=explode(":",$task_time);
	
	$getmintime=$arrtime[0]*3600+$arrtime[1]*60+$arrtime[2];
	$media_result = mysqli_query($con,"SELECT tasktime FROM traindemos WHERE trainid = '$task_id'");
	if($media_row = mysqli_fetch_array($media_result))
	{
		$gettime=$media_row['tasktime']*60;
	
		$functiontime=$getmintime+$gettime;
		
		echo $functiontime;
	}

?>