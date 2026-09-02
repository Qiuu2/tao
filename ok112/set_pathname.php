<?php
header('Content-Type:text/html;charset=utf-8');
require_once('inc/config.inc.php');
require_once('inc/config.php');
	

	$pathname = "";
	if(isset($_GET['pathname']))
	{
		$pathname = $_GET['pathname'];
	} 

	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = $_GET['terminal_id'];
	} 
	 mysqli_query($con,"UPDATE terminal SET upgrade = '$pathname' where  id='$terminal_id'") or die(mysqli_error($con));
	 	 $hrefaddr="view_shengji.php?terminal_id=".$terminal_id;
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = $hrefaddr;
		echo "<script>window.location='success.php'</script>";
?>