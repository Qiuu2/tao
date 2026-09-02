<?php
	require_once("inc/config.inc.php");
	
	$device_id = 0;//auto_increment_index从1开始
	
	if(isset($_GET['device_id']))
	{
		$device_id = trim($_GET['device_id']);
	}	
	
	$remote_key_sql = "SELECT * FROM powertimeqi WHERE terminalid = '$device_id'";

	$remote_key_result = mysqli_query($con,$remote_key_sql) or die(mysqli_error($con));
	$get_key_id = "";

	if($rowttss = mysqli_fetch_array($remote_key_result))
	{
		$get_key_id=$rowttss['terminalid'].",".$rowttss['terminalname'].",".$rowttss['power1'].",".$rowttss['power2'].",".$rowttss['power3'].",".$rowttss['power4'].",".$rowttss['power5'].",".$rowttss['power6'].",".$rowttss['power7'].",".$rowttss['power8'].",".$rowttss['power9'].",".$rowttss['power10'].",".$rowttss['power11'].",".$rowttss['power12'].",".$rowttss['power13'].",".$rowttss['power14'].",".$rowttss['power15'].",".$rowttss['power16'].",";
		$get_key_id .= $rowttss['powername1'].",".$rowttss['powername2'].",".$rowttss['powername3'].",".$rowttss['powername4'].",".$rowttss['powername5'].",".$rowttss['powername6'].",".$rowttss['powername7'].",".$rowttss['powername8'].",".$rowttss['powername9'].",".$rowttss['powername10'].",".$rowttss['powername11'].",".$rowttss['powername12'].",".$rowttss['powername13'].",".$rowttss['powername14'].",".$rowttss['powername15'].",".$rowttss['powername16'];
		
	}

	echo $get_key_id;
?>