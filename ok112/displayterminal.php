<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");//避免页面乱码

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

//验证是否失效
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
	
	$smarty->assign("displayterminal",$displayterminal);
	
	$smarty->assign("Revise",$Revise);
	
	//require('editor.php');

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
	
	$get_id = "";
	
	if(isset($_GET['term_id']))
	{
		$get_id = trim($_GET['term_id']);
		
		$_SESSION['tran_mid_value'] = $get_id;
		
		$_SESSION['tran_shotcut_key'] = "";
	}
	
	$set_flag = "";
	
	if(isset($_GET['flag']))
	{
		$set_flag = trim($_GET['flag']);
		
	
	}
	
	$key_id = "";
	
	if(isset($_GET['key_id']))
	{
		$key_id = trim($_GET['key_id']);
		
		$_SESSION['tran_shotcut_key'] = $key_id;
		
		$_SESSION['tran_mid_value'] = "";
	}
	
	$get_id = $_SESSION['tran_mid_value'];
	$key_id = $_SESSION['tran_shotcut_key'];
	
	if($get_id != "")
	{
		if($set_flag==2)
		$sql = "SELECT terminalid FROM offlinetaskofterminal WHERE offlinetaskofterminal.taskid = $get_id ";
		else
		$sql = "SELECT terminalid FROM terminaloftask WHERE terminaloftask.taskid = $get_id ";
	}
	if($key_id != "")
	{
		$sql = "SELECT terminalid FROM terminalkeymap WHERE terminalkeymap.keyid = '$key_id'";
	}
	
	if(!isset($_SESSION['admin_id']))
	{
		$result	= mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
		
		//	$Num	= mysqli_num_rows($result);
		
		//$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
	}
	else
	{
		$result	=	mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
		
	//	$Num	=	mysqli_num_rows($result);
		
		//$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
	}
	
	$temp_array = array();
	
	while ($row = mysqli_fetch_array($result)) 
	{
		$temp_array[] = $row['terminalid'];
	}
	
	$terminal_str_id = implode(",",$temp_array);
	
	mysqli_free_result($result);
	
	unset($sql,$row,$temp_array);
	
	if( $terminal_str_id == "" )
	{
		$terminal_sql = "select id,groupid,terminalname,typeid,netstate,devicestate,taskstate,ip,volume ";
		$terminal_sql.= "from terminal where terminal.id in ('') ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
	else
	{
		$terminal_sql = "select id,groupid,terminalname,typeid,netstate,devicestate,taskstate,ip,volume ";
		$terminal_sql.= "from terminal where terminal.id in ($terminal_str_id) ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}

	$result_terminal = mysqli_query($con,$terminal_sql) or die(mysqli_error($con));
	$Num	= mysqli_num_rows($result_terminal);
	$info=array();	
	$result_terminal = mysqli_query($con,$terminal_sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
	
	
	while($row_terminal = mysqli_fetch_array($result_terminal))
	{
		$info[] = array("id"=>$row_terminal['id'],"groupid"=>$row_terminal['groupid'],"terminalname"=>$row_terminal['terminalname'],"typeid"=>$row_terminal['typeid'],"netstate"=>$row_terminal['netstate'],"devicestate"=>$row_terminal['devicestate'],"taskstate"=>$row_terminal['taskstate'],"ip"=>$row_terminal['ip'],"volume"=>$row_terminal['volume']);
	}
	
	$smarty->assign("info",$info);
	
	mysqli_free_result($result_terminal);
	
	unset($terminal_sql,$row_terminal,$termianl_info);
	
	//终端类型
	$result_terminaltype = mysqli_query($con,"SELECT id,terminaltype.name FROM terminaltype ") or die(mysqli_error($con));
	$terminaltype_info=array();	
	
	while($row_terminaltype = mysqli_fetch_array($result_terminaltype))
	{
		$terminaltype_info[] = array("id"=>$row_terminaltype['id'],"name"=>$row_terminaltype['name']);
	}

	$smarty->assign("terminaltype_info",$terminaltype_info);
	
	@mysqli_free_result($result_terminaltype);
	unset($row_terminaltype);
	
	//终端分区
	
	$result_stream = mysqli_query($con,"SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream") or die(mysqli_error($con));
	$stream_info=array();	
	while($row_stream = mysqli_fetch_array($result_stream))
	{
		$stream_info[] = array("id"=>$row_stream['streamid'],"name"=>$row_stream['name']);
	}

	$smarty->assign("stream_info",$stream_info);
	
	mysqli_free_result($result_stream);
	
	unset($row_stream);
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
	$smarty->assign("chinese_big5_english",$_SESSION['language']);
	
	$smarty->assign("start",$start);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("displayproperty/displayterminal.html");
}
?>
