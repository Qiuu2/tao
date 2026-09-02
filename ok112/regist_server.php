<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once("inc/config.php");


//显示多语言
if($_SESSION['language']=="")
require_once("language/chinese.php");
else
require_once("language/".$_SESSION['language'].".php");

$smarty->assign("regist_server",$regist_server);
//验证是否失效
//require_once("verify_user_sessionin_valid.php");
/*if(empty($_SESSION['admin_id']))
{
	header("location:login.php");
}
else
{*/
//判断用户是否已注册
$user_registed = 0;

$regist_sql = "SELECT registerflag FROM serverbaseparam";

$regist_result = mysqli_query($con,$regist_sql) or die(mysqli_error($con));

if($regist_row = mysqli_fetch_array($regist_result))
{
	$user_registed = $regist_row['registerflag'];
}

@mysqli_free_result($regist_result);

unset($regist_sql,$regist_row);
//取序列号
$serial_sql = "SELECT registerserial,trystartdate FROM audioserver.serverbaseparam LIMIT 0,1";

$serial_result = mysqli_query($con,$serial_sql) or die(mysqli_error($con));

if($serial_row = mysqli_fetch_array($serial_result))
{
	$output_info = trim($serial_row['registerserial']);
	$date_info = trim($serial_row['trystartdate']);
}
/*
$datetimeinfo = explode("-",$date_info);
$month=date("m");
$year=date("Y");
$day=date("d");
$date1=mktime(0,0,0,$datetimeinfo[1],$datetimeinfo[2],$datetimeinfo[0]);

$date2=mktime(0,0,0,$month,$day,$year);	
	
$Days=round(($date1-$date2)/3600/24); 
if($Days>15)
	$Days=15;
*/
$files = fopen("serial","r");

fgets($files);	
$getfile=substr(fgets($files),10);	
fclose($files);
$month=date("m");
$year=date("Y");
$day=date("d");
$array = explode("-",$getfile);

$date1=mktime(0,0,0,$array[1],$array[2],$array[0]);
$date2=mktime(0,0,0,$month,$day,$year);	
$Days=round(($date2-$date1)/3600/24); 
if($Days>5)
	$Days=5;
	
$Dayss=5-$Days;

@mysqli_free_result($serial_result);
unset($serial_sql,$serial_row);

$smarty->assign("output_info",$output_info);
$smarty->assign("getDays",$Dayss);
$smarty->assign("user_registed",$user_registed);
$smarty->display("regist_server.html");
//}
?>