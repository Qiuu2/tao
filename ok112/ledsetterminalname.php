<?php
	if (!session_id()) session_start();
	
	header("content-type:text/html;charset=utf-8");
	require_once("inc/config.inc.php");
	require_once("inc/socket_conf.php");
	require_once('inc/socket_conf.php');
	$id = "";
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
	}
	$subterminalid = "";
	if(isset($_GET['subterminalid']))
	{
		$subterminalid = trim($_GET['subterminalid']);
	}
	$devicename = "";
	$pagecharacter='utf-8';

	$chezhannumber = "";
	if(isset($_GET['chezhannumber']))
	{
		$chezhannumber = trim($_GET['chezhannumber']);
	}

	$checi = "";
	if(isset($_GET['checi']))
	{
		$checi = trim($_GET['checi']);
	}

	if($chezhannumber=="" && $checi =="")
	{
		if(isset($_GET['devicename']))
		{
			$devicename = trim($_GET['devicename']);
			$code=strtolower(mb_detect_encoding($devicename, array('GB2312','UTF-8','GBK','ASCII')));

			if(($code=='gb2312' || $code=='utf-8' || $code=='euc-cn') && $code!=$pagecharacter)
			{
				$devicename=iconv($code,$pagecharacter,$devicename);  
				//$getschemename = mb_convert_encoding($getschemename,"utf-8", $code);
			}
			$task_sql = "update leddevice set name = '$devicename',subterminalid='$subterminalid' where leddevice.id = '$id'";
			mysqli_query($con,$task_sql) ;
			echo 1;	
		}
			
	}
	else
	{
		if($chezhannumber!="" && $checi !="")
		{
			$defaulttext=$chezhannumber.",".$checi;
			$task_sql = "update leddevice set defaulttext = '$defaulttext' where leddevice.id = '$id'";
			mysqli_query($con,$task_sql);

			$_SESSION['serverip'] = "audioserver";
			$socket	=	new	send_message_to_server($port_conf);	
			$msg = "terminal?state=31&id=".$id;
			$socket->send_data($_SESSION['serverip'],$msg);
			echo 1;	
		}
		else
		{
			echo 3;				
		}

	}

?>