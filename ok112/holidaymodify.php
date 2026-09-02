<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');

//验证是否失效
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	require_once('login.php');	
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("add_bell_scheme",$add_bell_scheme);

	$smarty->assign("Belladdtask",$Belladdtask);
	$getid=$_GET['id'];
		$termianl_sql = "SELECT * FROM holidaytime where id='$getid'";
	
	$termianl_result = mysqli_query($con,$termianl_sql) or die(mysqli_error($con));
	
	while($termianl_row = mysqli_fetch_array($termianl_result))
	{
		$holiday = array("id"=>$termianl_row['id'],"startdate"=>$termianl_row['startdate'],"enddate"=>$termianl_row['enddate'],"name"=>$termianl_row['name']);
	}
	
	
	$smarty->assign("holiday",$holiday);
	@mysqli_free_result($termianl_result);
	
	unset($termianl_sql,$termianl_row);
	
	
	
	$smarty->assign("taskid",$_GET['id']);
	$smarty->assign("mediatimelength",$mediatimelength);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("holidaymanager/modifyholiday.html");
}
?>