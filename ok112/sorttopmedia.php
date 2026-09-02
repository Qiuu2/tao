<?php
header("content-type:text/html;charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
$taskidandmediaid = "";
if(isset($_GET['taskid']))
{
	$taskidandmediaid = trim($_GET['taskid']);
	$taskidandmediaidarray = explode(",",$taskidandmediaid);
}
$gettaskid = $taskidandmediaidarray[0];
$getmediaid = $taskidandmediaidarray[1];

$sql = "select mediaoftask.id,mediaoftask.sort from mediaoftask where mediaoftask.mediaid = '$getmediaid' and mediaoftask.taskid = '$gettaskid' ";
$result = mysqli_query($con,$sql) or die(mysqli_error($con));
if($row = mysqli_fetch_array($result))
{
	$getid = $row['id'];
	$getsort = $row['sort'];
}
@mysqli_free_result($result);
unset($row,$sql);

$sql = "SELECT mediaoftask.id, MIN(sort) AS sort FROM mediaoftask WHERE mediaoftask.taskid = '$gettaskid'";
$result = mysqli_query($con,$sql) or die(mysqli_error($con));
if($row = mysqli_fetch_array($result))
{
	$getminid = $row['id'];
	$getminsort = $row['sort'];
}
@mysqli_free_result($result);
unset($row,$sql);

mysqli_query($con,"LOCK TABLE mediaoftask WRITE");


$currsql = "UPDATE mediaoftask SET sort = '$getminsort' WHERE mediaoftask.id = '$getid'";
mysqli_query($con,$currsql) or die(mysqli_error($con));
unset($currsql);

$maxsql = "UPDATE mediaoftask SET sort = '$getsort' WHERE mediaoftask.id = '$getminid'";
mysqli_query($con,$maxsql) or die(mysqli_error($con));
unset($maxsql);

mysqli_query($con,"UNLOCK TABLES");

if(!mysqli_error($con))
{
	echo 1;
}

?>