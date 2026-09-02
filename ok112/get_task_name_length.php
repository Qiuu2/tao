<?php
	header("content-type:text/html;charset=utf-8");
	
	require_once('inc/config.inc.php');

	$media_id = "";
	
	if(isset($_GET['getname']))
	{
		$getname = trim($_GET['getname']);
	}
	
	$media_result = mysqli_query($con,"SELECT name FROM ttssentence WHERE name = '$getname'");
	if(mysqli_num_rows($media_result) > 0)
	{
		echo 1;
	}
	else
		echo 0;
?>