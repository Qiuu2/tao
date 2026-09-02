<?php
	if (!session_id()) session_start();
	
	header("content-type:text/html;charset=utf-8");
	
	require_once("inc/config.inc.php");
	require_once('inc/socket_conf.php');

	if(isset($_GET['gpsselects']))
	{
		$gpsselects = $_GET['gpsselects'];
	}
	if($gpsselects!=-1)
	{
		if($gpsselects>0)
		{
			$opt="设置gps";
		}
		else
		{
			$opt="取消gps";
		}
		mysqli_query($con,"lock table serverbaseparam write,log write");
		$sqls = "update serverbaseparam set adjusttime='$gpsselects'";
		mysqli_query($con,$sqls) or die(mysqli_error($con));
		unset($sqls);


		$ip = $_SERVER['REMOTE_ADDR'];
		if(!empty($_SESSION['username']))
		{
			$user = $_SESSION['username'];
		}
	

		$time = gmdate("Y-m-d H:i:s",time()+8*3600);

		$log_sql = "INSERT INTO audioserver.log (log.user, log.operate, log.ip, log.time)";
		
		$log_sql.= " VALUES ('$user','$opt','$ip','$time') ";

		mysqli_query($con,"lock table log write");

		mysqli_query($con,$log_sql) or die(mysqli_error($con));
		
		unset($log_sql);
		
		mysqli_query($con, "unlock table" );

		echo '1';
	}
	else if($gpsselects==-1)
	{
		$socket	=	new	send_message_to_server($port_conf);	
		$_SESSION['serverip'] = "audioserver";
		$msg = "server?state=1";
		$socket->send_data($_SESSION['serverip'],$msg);
		 $command="cmdhost -c 'sudo reboot'";
		 system($command);
		 @system("sudo reboot");
		echo '2';
		
	}

?>