<?php
/****************************************************
	本模块未使用、而使用do.php下的restart_server_msg
****************************************************/
	require_once("inc/socket_conf.php");
	
	$socket = new send_message_to_server($port_conf);
	
	$strbuff = "server?state=1";
	
	$socket->send_data($_SESSION['serverip'],$strbuff);
	
	/*echo "<script>alert('系统正在重启、请稍候...');</script>";
	
	echo "<script>window.history.back();</script>";
	
	exit;*/
	
?>