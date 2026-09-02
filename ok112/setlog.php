<?php
/**************************************
	?????
	1???
	2?????
***************************************/
if (!session_id()) session_start();
header("content-type:text/html; charset=gb2312");
require_once('inc/smarty.inc.php');

$filename=$_GET['act'];

//???
$filename = "";
	if(isset($_GET['act']))
	{
		$filename = trim($_GET['act']);
	}


$smarty->display("datelog/$filename");


?>