<?php
if (!session_id()) session_start();
header("content-type:text/html;charset=utf-8");

$sql = "SELECT ntpserver FROM serverbaseparam";
	$serverip = mysqli_query($con,$sql);
	if($serverrow =mysqli_fetch_array($serverip))
	{
	
		echo  $serverrow['ntpserver'];
	}
	@mysqli_free_result($serverip);
	unset($serverrow);


?>