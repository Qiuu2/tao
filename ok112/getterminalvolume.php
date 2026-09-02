<?php
	if (!session_id()) session_start();
	
	header("content-type:text/html;charset=utf-8");
	
	require_once("inc/config.inc.php");
	
	require_once("inc/socket_conf.php");

//$fh=fopen("get_backup.log","a"); 
	$volume = "";
	if(isset($_GET['volume']))
	{
		$volume = trim($_GET['volume']);
	}
	$task_id = "";
	if(isset($_GET['task_id']))
	{
		$task_id = trim($_GET['task_id']);
	}
	
	if($task_id != "")
	{
		$sql = "update terminal set volume='$volume' where id in (".$task_id.")";
		mysqli_query($con,$sql) or die(mysqli_error($con));
		unset($sql);

		$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "terminal?state=5&id={".$task_id."}&volume=".$volume;
		//fwrite($fh,$msg);
		$socket->send_data($_SESSION['serverip'],$msg);
		
		echo "1";
	}
	else
	{
		echo "0";
	}
//fclose($fh); 
?>