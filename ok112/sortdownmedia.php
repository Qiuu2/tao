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

$sql = "select id, mediaid, taskid, sort from mediaoftask where mediaoftask.taskid = '$gettaskid' ORDER BY sort";
$result = mysqli_query($con,$sql) or die(mysqli_error($con));
for($i=0; $i<mysqli_num_rows($result)-1; $i++)
{
	mysqli_data_seek($result,$i);
	$getmediataskinfo = mysql_fetch_row($result);
	if(($getmediataskinfo[2] == $gettaskid) && ($getmediataskinfo[1] == $getmediaid))
	{
		$getid = $getmediataskinfo[0];
		$getmedia = $getmediataskinfo[1];
		$gettask = $getmediataskinfo[2];
		$getsort = $getmediataskinfo[3];
		
		mysqli_data_seek($result,$i+1);
		$getnextmediainfo = mysql_fetch_row($result);
		
		$getnextid = $getnextmediainfo[0];
		$getnextmediaid = $getnextmediainfo[1];
		$getnexttaskid = $getnextmediainfo[2];
		$getnextsort = $getnextmediainfo[3];
		//测试...........................
		//echo $getid."...".$getmedia."...".$gettask."...".$getsort."...";
		//echo $getpreid."...".$getpremediaid."...".$getpretaskid."...".$getpresort."...";
		$sqlnext = "UPDATE mediaoftask SET sort = '$getsort' WHERE mediaoftask.id = '$getnextid'";
		mysqli_query($con,$sqlnext) or die(mysqli_error($con));
		$sqlcurr = "UPDATE mediaoftask SET sort = '$getnextsort' WHERE mediaoftask.id = '$getid'";
		mysqli_query($con,$sqlcurr) or die(mysqli_error($con));				
		echo 1;
		break;
	}
}
@mysqli_free_result($result);
unset($sql);
?>