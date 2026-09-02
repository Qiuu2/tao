<?php
if (!session_id()) session_start();
header("content-type:text/html;charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
$alarmid = "";
if(isset($_GET['id']))
{
	$alarmid = trim($_GET['id']);
}

$terminalid = "";
if(isset($_GET['terminalid']))
{
	$terminalid = trim($_GET['terminalid']);
}
if(empty($alarmid))
{
	echo "";
	
}
else
{
	$sql = "SELECT area FROM terminaloftask WHERE taskid =$alarmid and terminalid=$terminalid";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error);
	if($row = mysqli_fetch_array($result))
	{
		echo $row['area'];
	}
	@mysqli_free_result($result);
	unset($row,$sql);
}

?>