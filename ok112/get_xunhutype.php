
<?php

	header("content-type:text/html;charset=utf-8");
	
	require_once('inc/config.inc.php');

	$terminal_id = "";
	if(isset($_GET['id']))
	{
		$terminal_id = trim($_GET['id']);
	}
	$sql = "SELECT typeid FROM terminal WHERE id='$terminal_id'";
	$media_result = mysqli_query($con,$sql) or die(mysqli_error($con));
	if($media_row = mysqli_fetch_array($media_result))
	{
		echo trim($media_row['typeid']);
	}
?>