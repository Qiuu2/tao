<?php
	header("content-type:text/html; charset=utf-8");
	require_once('inc/config.inc.php');
	require_once('inc/config.php');
	mysqli_query($con,"set names 'utf8'");
	//date_default_timezone_set('Asia/Shanghai');
	set_time_limit(0);//����ʱ��Ϊ������
	//error_reporting(E_ALL); 
	$backup_name = "";
	
	if(isset($_GET['backup_name']))
	{
		$backup_name = trim($_GET['backup_name']);
	}
	
	if(empty($backup_name))
	{
		$backup_name = "".date('Y-m-d')."-".(string)time()."";
	}
	$flag=0;
	if(isset($_GET['flag']))
	{
		$flag = trim($_GET['flag']);
	}

	if($flag==1)
	{
		$command = "sudo tar -cvf /backup/backup/".$backup_name.".tar -C /var/lib mysql";
		@exec($command);
	}
	else if($flag==2)
	{
		$command = "sudo tar -xvf /backup/backup/".$backup_name.".tar -C /var/lib";
		@exec($command);
		@exec("sudo reboot");
	}
	
	echo "1";
	return true;
?>