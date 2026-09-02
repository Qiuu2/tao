<?php
	if (!session_id()) session_start();
	
	header("content-type:text/html;charset=utf-8");
	
	require_once("inc/config.inc.php");
	
	require_once("inc/socket_conf.php");
	$getinitid = "";
	if(isset($_GET['getinitid']))
	{
		$getinitid = trim($_GET['getinitid']);
	}

	$getterminalid = "";
	if(isset($_GET['getterminalid']))
	{
		$getterminalid = trim($_GET['getterminalid']);
	}

	$getmediaid = 0;
	if(isset($_GET['getmediaid']))
	{
		$getmediaid = trim($_GET['getmediaid']);
	}

if($getmediaid>0)
{
	$to = mysqli_query($con,"SELECT id FROM media WHERE id ='$getmediaid'");
	if(mysqli_num_rows($to) <= 0)
	{
		mysqli_query($con,"UPDATE media SET id ='$getmediaid' WHERE id='$getinitid'");
		echo "1";
	}
	else
	{
		mysqli_query($con,"DELETE FROM media WHERE id='$getmediaid'");
		mysqli_query($con,"UPDATE media SET id ='$getmediaid' WHERE id='$getinitid'");
		echo "1";
	}
}
else
{
	$to = mysqli_query($con,"SELECT id FROM terminal WHERE id ='$getterminalid'");
	if(mysqli_num_rows($to) <= 0)
	{
			mysqli_query($con,"UPDATE terminal SET id ='$getterminalid' WHERE id='$getinitid'");
			echo "1";
	}
	else
	{
		$from = mysqli_query($con,"SELECT id FROM terminal WHERE id ='$getterminalid' AND typeid IN (SELECT typeid FROM terminal WHERE id='$getinitid')");
			if(mysqli_num_rows($from) <= 0)
			{
			echo "2";
			
			}
			else
			{
				$fromstate = mysqli_query($con,"SELECT id FROM terminal WHERE id ='$getterminalid' AND netstate='0'");
				if(mysqli_num_rows($fromstate) <= 0)
				{
					echo "3";
				}
				else
				{
					mysqli_query($con,"DELETE FROM terminal WHERE id='$getterminalid'");
					mysqli_query($con,"UPDATE terminal SET id ='$getterminalid' WHERE id='$getinitid'");
					echo "4";
				}
			}
	}
}
?>