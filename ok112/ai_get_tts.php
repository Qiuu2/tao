<?php
	require_once("inc/config.inc.php");
	
	$device_id = 0;//auto_increment_index从1开始
	
	if(isset($_GET['device_id']))
	{
		$device_id = trim($_GET['device_id']);
	}	
	
	$remote_key_sql = "SELECT terminalid,groupid FROM ai_device WHERE shibiedeviceid = '$device_id'";


	$remote_key_result = mysqli_query($con,$remote_key_sql) or die(mysqli_error($con));
	$get_key_id="";
	while($remot_key_row = mysqli_fetch_array($remote_key_result))
	{
		if($get_key_id=="")
		$get_key_id="stream_".$remot_key_row['groupid']."::".$remot_key_row['terminalid'];
		else
		$get_key_id=$get_key_id.","."stream_".$remot_key_row['groupid']."::".$remot_key_row['terminalid'];
		
	}



	echo $get_key_id;
?>