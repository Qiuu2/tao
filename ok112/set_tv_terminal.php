<?php
	if (!session_id()) session_start();
	
	header("content-type:text/html;charset=utf-8");
	
	require_once("inc/config.inc.php");
	
	require_once("inc/socket_conf.php");

	$id = "";
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
	}
	$ip = "";
	if(isset($_GET['ip']))
	{
		$ip = trim($_GET['ip']);
	}
	
	
	$setormodify = "";
	if(isset($_GET['setormodify']))
	{
		$setormodify = trim($_GET['setormodify']);
	}

	$sql = mysqli_query($con,"SELECT terminalid FROM cameramap WHERE terminalid='$id'");
	if(mysqli_num_rows($sql) > 0)
	{
		if($setormodify==0)
		{
			echo "1";
		}
		else if($setormodify==1)
		{
			$sqls = "UPDATE cameramap SET ipaddr='$ip' WHERE terminalid ='$id'";
			mysqli_query($con,$sqls) or die(mysqli_error($con));
			echo "2";
		}
		else if($setormodify==2)
		{

			$sqls = "DELETE FROM cameramap WHERE cameramap.terminalid = '$id'";
			mysqli_query($con,$sqls) or die(mysqli_error($con));
			echo "2";
		}	
	}
	else
	{
		$sqls="INSERT INTO cameramap(terminalid,ipaddr)VALUES('$id','$ip')";
		mysqli_query($con,$sqls)or die(mysqli_error($con));
		echo "2";	
	}		
?>