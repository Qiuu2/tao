<?php
	header('Content-Type:text/html;charset=UTF-8');
	require_once("inc/config.inc.php");
	require_once("inc/common.php");
	$get_foldid = "";
	if(isset($_GET['folderid']))
	{
		$get_foldid = trim($_GET['folderid']);
	}
	if(trim($get_foldid) == "")
	{
		echo 0;
	}	
	else if(trim($get_foldid) != "")
	{
		//判断该组是否有终端权限
		$sql_group = "SELECT * FROM usergroup WHERE usergroup.terminalpriv = 1 AND usergroup.id = '$get_foldid'";
		$result_group = mysqli_query($con,$sql_group) or die(mysqli_error($con));
		if(mysqli_num_rows($result_group) > 0)
		{
			if($_SESSION['admin_id'] =="administrator")
			{
				echo 2;
			}
			else
			{
				echo 1;
			}
		}
		else
		{
			echo 0;
		}
		@mysqli_free_result($result_group);
		unset($sql_group);
	}
?>