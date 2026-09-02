<?php
	header("content-type:text/html;charset=utf-8");
	require_once('inc/config.inc.php');
	$terminal_id = "";
	if(isset($_GET['id']))
	{
		$terminal_id = trim($_GET['id']);
	}
	$key = "";
	if(isset($_GET['key']))
	{
		$key = trim($_GET['key']);
	}
	$key_info = "";
	//当没有值返回0
	$sql_encode_terminal = "SELECT * FROM terminalkey WHERE terminalkey.terminalid = '$terminal_id' AND terminalkey.key = '$key'";
	$result_encode_terminal = mysqli_query($con,$sql_encode_terminal) or die(mysqli_error($con));
	if($row_encode_terminal = mysqli_fetch_array($result_encode_terminal))
	{
		$key_map_id = $row_encode_terminal['id'];
		
		$row_map_id = mysqli_fetch_array(mysqli_query($con,"SELECT terminalid FROM terminalkeymap WHERE terminalkeymap.keyid = '$key_map_id'"));
		
		$key_info = $row_encode_terminal['name'].",".$row_map_id['terminalid'];
		
		echo $key_info;
		
		unset($key_map_id,$row_map_id,$key_info);
	}
	else
	{
		echo 0;
	}
	@mysqli_free_result($result_encode_terminal);
	unset($row_encode_terminal,$sql_encode_terminal);
	//当有值返回1
?>