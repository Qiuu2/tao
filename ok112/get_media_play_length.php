<?php
	header("content-type:text/html;charset=utf-8");
	
	require_once('inc/config.inc.php');

	$media_id = "";
	
	if(isset($_GET['id']))
	{
		$media_id = trim($_GET['id']);
	}
	
	$media_result = mysqli_query($con,"SELECT timelength FROM media WHERE media.id = '$media_id'");
	if($media_row = mysqli_fetch_array($media_result))
	{
		echo trim($media_row['timelength']);
	}
?>