<?php
/******************************************
	判断serverbaseparam数据库中数据是否变化
	有就发送1刷新
	没有发送0不刷新
******************************************/
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

require_once($_SERVER['DOCUMENT_ROOT'].'/inc/config.inc.php');
/*
$DB_HOST	=	"127.0.0.1";
$DB_USER	=	"root"; 
$DB_PWD		=	"a9000db#!ht"; 
$DB_NAME	=	"audioserver";
//$con = mysqli_connect($DB_HOST,$DB_USER,$DB_PWD,$DB_NAME);
$conn = mysqli_connect('127.0.0.1','root','a9000db#!ht','audioserver');
*/
$sqlsss="SELECT terminalchange, taskchange, serverchange FROM serverbaseparam ";
$result = mysqli_query($con,$sqlsss) or die("Execute error".mysqli_error($con));
if($row=mysqli_fetch_array($result))
{
	if(!isset($_SESSION['terminalchange']))
	{
		$_SESSION['terminalchange'] = $row['terminalchange'];
		
	}
	else if($_SESSION['terminalchange'] != $row['terminalchange'])
	{
		$_SESSION['terminalchange'] = $row['terminalchange'];
		echo "2";
	}
	if(!isset($_SESSION['serverbaseparam']))
	{
		$_SESSION['serverbaseparam'] = $row['taskchange'];
		
	}
	else if($_SESSION['serverbaseparam'] != $row['taskchange'])
	{
		$_SESSION['serverbaseparam'] = $row['taskchange'];
		echo "1";
	}
	if(!isset($_SESSION['serverchange']))
	{
		$_SESSION['serverchange'] = $row['serverchange'];
		
	}
	if($_SESSION['serverchange'] != $row['serverchange'])
	{
		$_SESSION['serverchange'] = $row['serverchange'];
		echo "3";
	}

	/*if($_SESSION['terminalchange'] != $row['terminalchange'])
	{
		$_SESSION['terminalchange'] = $row['terminalchange'];
		echo "2";
	}
	if($_SESSION['serverbaseparam'] != $row['taskchange'])
	{
		$_SESSION['serverbaseparam'] = $row['taskchange'];
		echo "1";
	}
	if($_SESSION['serverchange'] != $row['serverchange'])
	{
		$_SESSION['serverchange'] = $row['serverchange'];
		echo "3";
	}*/

	mysqli_free_result($result);
	
	//测试
	//$fh=fopen('textajax.txt',"a"); 
	//fwrite($fh,(string)time()."****".$_SESSION['terminalchange']."****".$_SESSION['serverbaseparam']."****".$_SESSION['serverchange']); 
	//fclose($fh); 
}
?>