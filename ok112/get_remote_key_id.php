<?php
	require_once("inc/config.inc.php");
	
	$get_key_id = 0;//auto_increment_index从1开始
	
	if(isset($_GET['key_id']))
	{
		$get_key_id = trim($_GET['key_id']);
	}	
	
	$remote_key_sql = "SELECT DISTINCT terminalkeymap.terminalid FROM terminalkey,terminalkeymap ";

	$remote_key_sql.= "WHERE terminalkey.key = '$get_key_id' AND terminalkey.terminalid = '0' AND terminalkey.id = terminalkeymap.keyid ";
	
	$remote_key_result = mysqli_query($con,$remote_key_sql) or die(mysqli_error($con));
	
	if($remot_key_row = mysqli_fetch_array($remote_key_result))
	{
		$get_key_id = $remot_key_row['terminalid'];
	}
	
	echo $get_key_id;
?>