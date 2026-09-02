
<?php

	header("content-type:text/html;charset=utf-8");
	
	require_once('inc/config.inc.php');

	$terminal_id = "";
	if(isset($_GET['id']))
	{
		$terminal_id = trim($_GET['id']);
	}
	
	$sql = "SELECT terminalname FROM terminal WHERE id='$terminal_id'";
	
	$media_result = mysqli_query($con,$sql) or die(mysqli_error($con));
	//$media_result = mysqli_query($con,"SELECT typeid FROM terminal WHERE terminal.id = '$terminal_id'");
	if($media_row = mysqli_fetch_array($media_result))
	{
		$sql_terminal = "UPDATE terminal SET longitude='0',latitude='0' WHERE id='$terminal_id'";
	
		$terminal_result = mysqli_query($con,$sql_terminal) or die(mysqli_error($con));
	
	}
?>