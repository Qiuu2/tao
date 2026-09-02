<?php
	header("content-type:text/html;charset=utf-8");
	
	require_once('inc/config.inc.php');

	$get_media = "";
	
	if(isset($_GET['id']))
	{
		$get_media = trim($_GET['id']);
	}
	$sql = "SELECT id FROM filefolder WHERE filefolder.id='$get_media'";
	
	
	$media_result = mysqli_query($con,$sql) or die(mysqli_error($con));
	//$media_result = mysqli_query($con,"SELECT typeid FROM terminal WHERE terminal.id = '$terminal_id'");
	if($media_row = mysqli_fetch_array($media_result))
	{

	if(empty($media_row['id']))
	{
	
	}
	else
	{
	  echo trim($media_row['id']);
	}

		
	}
?>