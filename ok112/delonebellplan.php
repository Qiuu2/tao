<?php
header("content-type:text/html; charset=utf-8");
require_once('inc/config.inc.php');
require_once("inc/socket_conf.php");
//////////////////

$dispatchid = "";
if(isset($_GET['dispatchid']))
{
	$dispatchid = trim($_GET['dispatchid']);
}
$delterminaloftask0 = true;
$delfunction = true;
if($dispatchid != -1)//
{
	mysqli_query($con,"START TRANSACTION");
	$sql = "SELECT 	task.prepower FROM task WHERE task.taskid = '$dispatchid'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
///////////////////////////////////
	if($row = mysqli_fetch_array($result))
	{	
		if($row['prepower'] != 0)
		{	////////////////////////////////
			$sqlbell = "SELECT task.taskname,task.info	FROM task WHERE task.taskid = '$dispatchid'";
			$resultbell = mysqli_query($con,$sqlbell) or die(mysqli_error($con));
			if($rowbell = mysqli_fetch_array($resultbell))
			{
				$taskname = $rowbell['taskname'];
				$info = $rowbell['info'];
			}
			@mysqli_free_result($resultbell);
			unset($rowbell,$sqlbell);
			/////////////////////////////////
			$sqlfunction = "SELECT 	taskid FROM task WHERE task.taskname = '$taskname' AND task.info = '$info' AND task.tasktype = '9'";
			$resultfunction = mysqli_query($con,$sqlfunction) or die(mysqli_error($con));
			if($rowfunction = mysqli_fetch_array($resultfunction))
			{
				$getfunctionid = $rowfunction['taskid'];
			}
			@mysqli_free_result($resultfunction);
			unset($rowfunction,$sqlfunction);
			//////////////////////////////////ɾն
			$sqlterminaloftask = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getfunctionid'";
			$delterminaloftask0 = mysqli_query($con,$sqlterminaloftask) or die(mysqli_error($con));
			unset($sqlterminaloftask);
			/////////////////////////////////ɾ
			$delfunction = $sqlfunction = "DELETE FROM task WHERE task.taskid = '$getfunctionid'";
			mysqli_query($con,$sqlfunction) or die(mysqli_error($con));
			unset($sqlfunction);
		}
	}
	@mysqli_free_result($result);
	unset($row,$sql);
	
	$sql = "DELETE FROM task WHERE task.taskid = '$dispatchid'";
	$delbell = mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
	
	$sql = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$dispatchid'";
	$delterminaloftask = mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
	
	//$sql = "DELETE FROM mediaofterminal WHERE mediaofterminal.taskid = '$dispatchid'";
	//$delterminaloftask = mysqli_query($con,$sql) or die(mysqli_error($con));
	//unset($sql);
	
	$sql = "DELETE FROM mediaoftask WHERE mediaoftask.taskid = '$dispatchid'";
	$delmediaoftask = mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
	
	if($delbell && $delterminaloftask && $delmediaoftask && $delfunction && $delterminaloftask0)
	{
		mysqli_query($con,"COMMIT");
		echo 1;
	}
	if(!($delbell && $delterminaloftask && $delmediaoftask && $delfunction && $delterminaloftask0))
	{
		mysqli_query($con,"ROLLBACK");
		echo 0;
	}
//Ϣ
//$fh=fopen("tianyaluzhang.txt","a"); 
//fwrite($fh,"dispatchid".$dispatchid); 
//fclose($fh); 		
}
?>