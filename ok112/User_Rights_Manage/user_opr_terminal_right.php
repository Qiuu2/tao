<?php

function control_user_terminal_opr($con,$terminal_id)
{
	//判断当前用户是否为超级管理员
	$username = trim($_SESSION['username']);
	$user_id = "";
	if(empty($username))
	{
		header("location:login.php");
	}
	else
	{
		$group_sql = "SELECT id, usergroupid FROM book_admin WHERE book_admin.username = '$username'";
		$group_result = mysqli_query($con,$group_sql) or die(mysqli_error($con));
		if($group_row = mysqli_fetch_array($group_result))
		{
			$group_id = $group_row['usergroupid'];
			$user_id = $group_row['id'];
		}
		@mysqli_free_result($group_result);
		unset($group_sql,$group_row );
		if($group_id == 1)
		{
			//什么也不做
		}
		else if($group_id != 1)
		{
			$user_sql = "SELECT * FROM userterminal WHERE userterminal.userid = '$user_id' AND userterminal.terminalid = '$terminal_id'";
			$user_result = mysqli_query($con,$user_sql) or die(mysqli_error($con));
			if(mysqli_num_rows($user_result) < 1)
			{
				$terminal_result = mysqli_query($con,"select terminalname from terminal WHERE terminal.id='$terminal_id'") or die(mysqli_error($con));
				if($terminal_row = mysqli_fetch_array($terminal_result))
				{
					echo "<script>alert('Permission dend');</script>";
					echo "<script>window.history.back();</script>";
					exit;
				}
			}
		}
	}
}
?>