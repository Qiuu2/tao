<?php
	if (!session_id()) session_start();
	
	header('Content-Type:text/html;charset=utf-8');

	//===============避免数据库乱码

	require_once("inc/config.inc.php");
$pagecharacter='utf-8';
	$info1 = "";
	if(isset($_GET['info1']))
	{
		$info1 = trim($_GET['info1']);
	}
$code=strtolower(mb_detect_encoding($info1, array('GB2312','UTF-8','GBK','ASCII')));

	if(($code=='gb2312' || $code=='utf-8' || $code=='euc-cn') && $code!=$pagecharacter)
	{
		$info1=iconv($code,$pagecharacter,$info1);  
		
	}
	$info2 = "";
	if(isset($_GET['info2']))
	{
		$info2 = trim($_GET['info2']);
	}
$code2=strtolower(mb_detect_encoding($info2, array('GB2312','UTF-8','GBK','ASCII')));

	if(($code2=='gb2312' || $code2=='utf-8' || $code2=='euc-cn') && $code2!=$pagecharacter)
	{
		$info2=iconv($code2,$pagecharacter,$info2);  
		
	}
	$flag = "";
	if(isset($_GET['flag']))
	{
		$flag = trim($_GET['flag']);
	}

	mysqli_query($con,"set names utf8");
	
	if($flag==0)
	$sql = mysqli_query($con,"UPDATE serverbaseparam SET dealerinfo='$info1' WHERE id=1");
	else if($flag==1)
	$sql = mysqli_query($con,"UPDATE serverbaseparam SET dealerinfo='$info1',factory='$info2' WHERE id=1");
	
	$sql_task_sche = "SELECT dealerinfo,factory FROM serverbaseparam";
	$result_task_sche = mysqli_query($con,$sql_task_sche) or die(mysqli_error($con));
	if($row_task_sche = mysqli_fetch_array($result_task_sche))
	{
		$dealerinfo=trim($row_task_sche['dealerinfo']);
		$factory=trim($row_task_sche['factory']);
		echo $dealerinfo."##".$factory;
	}
	
	
		
?>