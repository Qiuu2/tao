<?php
	header("content-type:text/html;charset=utf-8");
	require_once('inc/config.inc.php');
	$ids = "";
	if(isset($_GET['ids']))
	{
		$ids = trim($_GET['ids']);
	}
	$flag = 0;
	if(isset($_GET['flag']))
	{
		$flag = trim($_GET['flag']);
	}
	$ip = $_SERVER['REMOTE_ADDR'];
	$user = $_SESSION['username'];
	$time = gmdate("Y-m-d H:i:s",time()+8*3600);
	if($flag==0)
	{
		$sql = "SELECT id,progress FROM terminal WHERE id IN($ids)";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		while($row = mysqli_fetch_array($result))
		{
			$array[]=array(
						"progress" => $row['progress'],
						"id" => $row['id'],	
				  );
			
		}
		echo json_encode($array);
	}
	else if($flag==1)
	{
		$enablemac = "";
		if(isset($_GET['enablemac']))
		{
			$enablemac = trim($_GET['enablemac']);
		}
		$mac_addr = "";
		if(isset($_GET['mac_addr']))
		{
			$mac_addr = trim($_GET['mac_addr']);
		}
		$getsn=0;

		$sql = "SELECT sn FROM usersn WHERE sn ='$mac_addr'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		while($row = mysqli_fetch_array($result))
		{
			$getsn=1;
		}
		if($getsn==0)
		{
			$sql_username = "INSERT INTO usersn (sn, userid) VALUES('$mac_addr', '0')";
			mysqli_query($con,$sql_username) or die(mysqli_error($con));
		
			$opt="添加mac地址";
			$log_sql = "INSERT INTO audioserver.log (log.user, log.operate, log.ip, log.time)";
			
			$log_sql.= " VALUES ('$user','$opt','$ip','$time') ";
		
			mysqli_query($con,"START TRANSACTION");
				mysqli_query($con,"lock table log write");
		
			mysqli_query($con,$log_sql) or die(mysqli_error($con));
			
			unset($log_sql);
			
			mysqli_query($con, "UNLOCK TABLES" );



			//$sql_username = "UPDATE serverbaseparam SET ischeckmac='$enables'";
			//$result =mysqli_query($con,$sql_username) or die(mysqli_error($con));
			echo 1;
		}
		
	
	}
	else if($flag==2)
	{
		$mac_addr = "";
		if(isset($_GET['mac_addr']))
		{
			$mac_addr = trim($_GET['mac_addr']);
		}
		$sql_username = "DELETE FROM usersn WHERE sn='$mac_addr'";
		$result =mysqli_query($con,$sql_username) or die(mysqli_error($con));
		$opt="删除mac地址";
		$log_sql = "INSERT INTO audioserver.log (log.user, log.operate, log.ip, log.time)";
		
		$log_sql.= " VALUES ('$user','$opt','$ip','$time') ";
	
		mysqli_query($con,"START TRANSACTION");
			mysqli_query($con,"lock table log write");
	
		mysqli_query($con,$log_sql) or die(mysqli_error($con));
		
		unset($log_sql);
		
		mysqli_query($con, "UNLOCK TABLES" );
		echo 1;
	
		
	}
	else if($flag==3)
	{
		$sql_username = "DELETE FROM usersn ";
		$result =mysqli_query($con,$sql_username) or die(mysqli_error($con));
		echo 1;
		
	}
	else if($flag==4)
	{
		$enables =0;
		if(isset($_GET['enables']))
		{
			$enables = trim($_GET['enables']);
		}
		$mac_addr = "";
		if(isset($_GET['mac_addr']))
		{
			$mac_addr = trim($_GET['mac_addr']);
		}
	//	$sql_username = "UPDATE usersn SET enable='$enables' WHERE sn='$mac_addr'";
	//	$result =mysqli_query($con,$sql_username) or die(mysqli_error($con));
		echo 1;
	}
	else if($flag==5)
	{
		$sql_username = "UPDATE serverbaseparam SET ischeckmac='1'";
		$result =mysqli_query($con,$sql_username) or die(mysqli_error($con));
		$opt="启用mac地址";
		$log_sql = "INSERT INTO audioserver.log (log.user, log.operate, log.ip, log.time)";
		
		$log_sql.= " VALUES ('$user','$opt','$ip','$time') ";
	
		mysqli_query($con,"START TRANSACTION");
			mysqli_query($con,"lock table log write");
	
		mysqli_query($con,$log_sql) or die(mysqli_error($con));
		
		unset($log_sql);
		
		mysqli_query($con, "UNLOCK TABLES" );
		echo 1;
	}
	else if($flag==6)
	{
		$sql_username = "UPDATE serverbaseparam SET ischeckmac='0'";
		$result =mysqli_query($con,$sql_username) or die(mysqli_error($con));
		$opt="停用mac地址";
		$log_sql = "INSERT INTO audioserver.log (log.user, log.operate, log.ip, log.time)";
		
		$log_sql.= " VALUES ('$user','$opt','$ip','$time') ";
	
		mysqli_query($con,"START TRANSACTION");
			mysqli_query($con,"lock table log write");
	
		mysqli_query($con,$log_sql) or die(mysqli_error($con));
		
		unset($log_sql);
		
		mysqli_query($con, "UNLOCK TABLES" );
		echo 1;
	}
	exit;
?>