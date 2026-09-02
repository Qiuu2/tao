<?php
if (!session_id()) session_start();
header('Content-Type:text/html;charset=utf-8');
require_once('inc/config.inc.php');
require_once('inc/config.php');
require_once('inc/socket_conf.php');
 move_uploaded_file($_FILES["file"]["tmp_name"], "link/backup/upgradefile/" . $_FILES["file"]["name"]);
	
	
echo 1;
	
 		
?>
