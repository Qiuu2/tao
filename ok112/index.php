<?php
/*****************************
	该页面没有用�?
*****************************/
if (!session_id()) session_start();

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

if(empty($_SESSION['admin_id']))
{
	
	//require_once('login.php');	
	$username = "";
	if(isset($_GET['username']))
	{
		$username = trim($_POST['username']);
	
	}
	else
	{
		header("location:login.php");
	}
	
	$userpwd = "";
	if(isset($_GET['userpwd']))
	{
		$userpwd = trim($_POST['userpwd']);
		$userpwd = md5($userpwd);
		$getaction="user_name=".$username."&userpwd=".$userpwd."&checknum=Htjy123";
	//	getaction2="do.php?act=aaa&abc=haC"+str_encode(getaction);
		header("location:do.php?act=aaa&abc=haC".base64_encode($getaction));

	}
	else
	{
		header("location:login.php");	
	}

}
else
{
	$username = "";
	if(isset($_GET['username']))
	{
		$username = trim($_POST['username']);
	}
	
	$userpwd = "";
	if(isset($_GET['userpwd']))
	{
		$userpwd = trim($_POST['userpwd']);
		$userpwd = md5($userpwd);
	}
	$getaction="user_name=".$username."&userpwd=".$userpwd."&checknum=Htjy123";
	//	getaction2="do.php?act=aaa&abc=haC"+str_encode(getaction);
		header("location:do.php?act=aaa&abc=haC".base64_encode($getaction));
}



?>
