<?php
if (!session_id()) session_start();
header('Content-Type:text/html;charset=utf-8');
require_once('inc/config.inc.php');
require_once('inc/config.php');
require_once('inc/socket_conf.php');
$output_info=array();
	 move_uploaded_file($_FILES["file"]["tmp_name"],"/backup/upgradefile/".$_FILES["file"]["name"]);
	 $tempinfo="chmod 777 /backup/upgradefile/".$_FILES["file"]["name"];
	 $command = "cmdhost --cmd=\"".$tempinfo."\"";
	 exec($command, $output_info,$last_line);
	 if($_FILES["file"]["name"]=="public.crt")
	 {
	  $tempinfo = "cp -rf /backup/upgradefile/public.crt link/home/apache/public.crt";
	 }
	 else if($_FILES["file"]["name"]=="server.key")
	 {
	 $tempinfo = "cp -rf /backup/upgradefile/server.key link/home/apache/server.key";
	 }
	 else if($_FILES["file"]["name"]=="server_ca.crt")
	 {
	 $tempinfo = "cp -rf /backup/upgradefile/server_ca.crt link/home/apache/server_ca.crt";
	 }

	  $command = "cmdhost --cmd=\"".$tempinfo."\"";
	  exec($command, $output_info,$last_line);
	  $hrefaddr="uploadhttps.php";
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = $hrefaddr;
		echo "<script>window.location='success.php'</script>";
?>
