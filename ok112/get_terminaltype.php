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
if(empty($alarmid))
{
	echo "";
	
}
else
{
	$sql = "SELECT switchcount FROM terminaltype WHERE terminaltype.id IN";
	$sql.= "(SELECT typeid FROM terminal WHERE terminal.id IN ($alarmid))";
	$result = mysqli_query($con,$sql) or die(mysqli_error);
	if($row = mysqli_fetch_array($result))
	{
		echo $row['switchcount'];
	}
	@mysqli_free_result($result);
	unset($row,$sql);
}

?>