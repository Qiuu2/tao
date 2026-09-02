<?php
	if (!session_id()) session_start();
	
	header("content-type:text/html;charset=utf-8");
	
	require_once("inc/config.inc.php");
	
	require_once("inc/socket_conf.php");

	$type = "";
	if(isset($_GET['type']))
	{
		$type = trim($_GET['type']);
	}
	if($type==1)
	{
			$getdbflag=0;
			$result_db;
			$sql = mysqli_query($con,"SELECT id,dbvalue FROM sounddevice");
			while ($getrow = mysqli_fetch_array($sql)) 
			{	
				if($getdbflag==0)
				$result_db=$getrow['id']."-".$getrow['dbvalue'];
				else
				$result_db=$result_db."#".$getrow['id']."-".$getrow['dbvalue'];
				$getdbflag++;	
			}
			@mysqli_free_result($sql);
			unset($getrow);
			echo $result_db;	
	}	
?>