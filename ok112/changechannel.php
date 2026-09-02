<?php
if (!session_id()) session_start();
header("content-type:text/html;charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
$alarmid = "";
if(isset($_GET['alarmid']))
{
	$alarmid = trim($_GET['alarmid']);
}
if(empty($alarmid))
{
	echo "";
	
}
else
{
	$sql = "SELECT 	channel FROM terminal WHERE terminal.id = '$alarmid'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	if($row = mysqli_fetch_array($result))
	{
		echo $row['channel'];
	}
	@mysqli_free_result($result);
	unset($row,$sql);
}























?>