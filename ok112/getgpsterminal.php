<?php
	if (!session_id()) session_start();
	
	header("content-type:text/html;charset=utf-8");
	
	require_once("inc/config.inc.php");
	$terminalinfo;
	$gpsflag=0;

	
	$getresult=mysqli_query($con,"SELECT terminalname,terminal.id from terminal,terminaltype where terminal.typeid=terminaltype.id and terminaltype.id in(31,8)") or die(mysqli_error($con));
	while($row=mysqli_fetch_array($getresult))
	{
	
		if($gpsflag==0)
		{
			$terminalinfo=$row['terminalname']."@@".$row['id'];
		}
		else
		{
			$terminalinfo.="%%".$row['terminalname']."@@".$row['id'];
		}
		$gpsflag++;
	}
	$get_result=mysqli_query($con,"SELECT adjusttime from serverbaseparam") or die(mysqli_error($con));
	if($rows=mysqli_fetch_array($get_result))
	{
		$terminalinfo.="&&".$rows['adjusttime'];
	}
	
	echo trim($terminalinfo);
	
?>