<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");//避免页面乱码
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');

//判断失效
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	header("location:login.php");
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	
	$smarty->assign("display_alarm_terminal",$display_alarm_terminal);
	$smarty->assign('displaymedia',$displaymedia);
	$smarty->assign("Revise",$Revise);
	require('editor.php');
	$smarty->assign('descriptionarea',$descriptionarea);
	if(!isset($_GET['page']))
	{
	    $page=1;
	    $start=0;
	}
	else 
	{
	    $page=$_GET['page'];
	    $start=($_GET['page']-1)*$NumOfPage;
	}	
	
	$id = "";
	
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
		
		$_SESSION['tran_mid_value'] = $id ;
	}
	else
	{
		$id  = $_SESSION['tran_mid_value'];
	}
	
	
	//获取报警分区终端ID
	$terminal_string = "";
	
	$alarm_sql = "SELECT terminalid FROM terminalofalarmgroup WHERE alarmgroupid = '$id'";
	
	$alarm_result = mysqli_query($con,$alarm_sql) or die(mysqli_error($con));
	
	while($alarm_row = mysqli_fetch_array($alarm_result))
	{
		if($terminal_string == "")
		{
			$terminal_string = $alarm_row['terminalid'];
		}
		else
		{
			$terminal_string.= ",".$alarm_row['terminalid']; 
		}
	}
	
	@mysqli_free_result($alarm_result);
	
	unset($alarm_sql,$alarm_row);
	
	if($terminal_string == "")
	{
		$sql = "SELECT 	id, terminalname, netstate, devicestate, taskstate, ip FROM terminal WHERE terminal.id IN ('') ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
	else
	{
		$sql = "SELECT 	id, terminalname, netstate, devicestate, taskstate, ip FROM terminal WHERE terminal.id IN ( $terminal_string ) ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
	
	unset($terminal_string);

	if(!isset($_SESSION['admin_id']))
	{
		$result	=	mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
		
		$Num	=	mysqli_num_rows($result);
		
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
	}
	else
	{
		$result	= mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
		
		$Num = mysqli_num_rows($result);
		
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
	}
	
	while ($row = mysqli_fetch_array($result)) 
	{
		$array[] = array(
							"id"=>$row['id'],"terminalname"=>$row['terminalname'],
							"netstate"=>$row['netstate'],"devicestate"=>$row['devicestate'],"taskstate"=>$row['taskstate'],"ip"=>$row['ip']
						 );
	}
	
	$smarty->assign("info",$array);
	
	@mysqli_free_result($result);
	
	unset($array,$row,$sql);
	//分页
	if($Num != 0)
	{
		require_once("pagination.class.php");
		$p = new pagination;
		$p->Items($Num);
		$p->limit($NumOfPage);
		$p->target("?id=".$_GET['id']."&");
		$p->currentPage($_GET['page']);
		$p->adjacents(3);
		$smarty->assign("pagestr",$p->show());
	}

	$smarty->assign("start",$start);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->assign("alarm_id",$id);
	
	$smarty->display("alarmmanager/displayareaterminal.html");
}
?>