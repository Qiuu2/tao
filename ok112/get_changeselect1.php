<?php
	header("content-type:text/html;charset=utf-8");
	
	require_once('inc/config.inc.php');

	$terminal_id = "";
	
	if(isset($_GET['id']))
	{
		$terminal_id = trim($_GET['id']);
	}
	$sql = "SELECT switchcount FROM terminaltype WHERE terminaltype.id IN";
	$sql.= "(SELECT typeid FROM terminal WHERE terminal.id IN ($terminal_id))";
	
	$media_result = mysqli_query($con,$sql) or die(mysqli_error($con));
	//$media_result = mysqli_query($con,"SELECT typeid FROM terminal WHERE terminal.id = '$terminal_id'");
	if($media_row = mysqli_fetch_array($media_result))
	{

	//	if($media_row['typeid']==3||$media_row['typeid']==0)
	//	{
		  // echo trim($media_row['typeid']);
		//	$get_change = "SELECT switchcount FROM terminaltype WHERE trminaltype.id = '$media_row['typeid']'";
			
			//$get_changeselect = mysqli_query($con,$get_change);
			//if($get_changeselects = mysqli_fetch_array($get_changeselect))
			//{
			
		        echo trim($media_row['switchcount']);
			//}
	//   }
		
		//
		
	}
?>