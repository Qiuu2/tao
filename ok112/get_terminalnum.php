
<?php

	header("content-type:text/html;charset=utf-8");
	
	require_once('inc/config.inc.php');

	$terminal_id = "";
	if(isset($_GET['id']))
	{
		$terminal_id = trim($_GET['id']);
	}
	$longitude="";
	if(isset($_GET['longitude']))
	{
		$longitude = trim($_GET['longitude']);
	}
	$latitude="";
	if(isset($_GET['latitude']))
	{
		$latitude = trim($_GET['latitude']);
	}

	$text = "";
	if(isset($_GET['text']))
	{
		$text = trim($_GET['text']);
	}

	if($text !="")
	{
		$sql_terminal = "UPDATE serverbaseparam SET dealerinfo='$text'";
		
		$terminal_result = mysqli_query($con,$sql_terminal) or die(mysqli_error($con));
		echo "1";

	}


	if($terminal_id !="")
	{
		$sql = "SELECT typeid,terminalname,netstate,ip FROM terminal WHERE id='$terminal_id'";
	
		$media_result = mysqli_query($con,$sql) or die(mysqli_error($con));
		//$media_result = mysqli_query($con,"SELECT typeid FROM terminal WHERE terminal.id = '$terminal_id'");
		if($media_row = mysqli_fetch_array($media_result))
		{
			$sql_terminal = "UPDATE terminal SET longitude='$longitude',latitude='$latitude' WHERE id='$terminal_id'";
		
			$terminal_result = mysqli_query($con,$sql_terminal) or die(mysqli_error($con));
				
			echo $media_row['ip']."#".$media_row['typeid']."@".$media_row['terminalname']."|".$media_row['netstate'];
		
		}
	}	
	
?>