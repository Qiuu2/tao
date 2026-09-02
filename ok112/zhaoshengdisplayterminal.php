<?php
/********************************
	显示分区的终端
********************************/
if (!session_id()) session_start();

//error_reporting(E_ALL);

header("content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

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
	$smarty->assign("stream_terminal",$stream_terminal);

	$smarty->assign("Streamdisplayterminal",$Streamdisplayterminal);
	//导入分页
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
	//获取分区id
	$streamid = "";
	if(isset($_GET['id']))
	{
		$streamid = trim($_GET['id']);
		$_SESSION['tran_mid_value'] = $streamid;
	}
	else
	{
		$streamid = $_SESSION['tran_mid_value'];
	}
	
	$sql = "SELECT 	terminal.id, terminal.terminalname, terminaltype.name, terminal.netstate, ";
	
	$sql.= "terminal.devicestate, terminal.taskstate,terminal.ip, terminal.volume ";
	
	$sql.= "FROM terminal, terminaltype WHERE terminal.typeid = terminaltype.id ";
	
	$sql.= "AND terminal.id IN(SELECT terminalid FROM soundgroup WHERE groupid = '$streamid') ORDER BY CONVERT(terminal.terminalname USING utf8)";
 
	$result=mysqli_query($con,$sql) or die(mysqli_error($con));
	$Num	=	mysqli_num_rows($result);
	//$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	while($row=mysqli_fetch_array($result))
	{
		$terminalinfo[] = array(
									"id"=>$row['id'],"terminalname"=>$row['terminalname'],"typename"=>$row['name'],
									"netstate"=>$row['netstate'],"devicestate"=>$row['devicestate'],
									"taskstate"=>$row['taskstate'],"ip"=>$row['ip'],"volume"=>$row['volume']
							   );
	}
	
	$smarty->assign("info",$terminalinfo);
	
	@mysqli_free_result($result);
	unset($terminalinfo,$row,$sql);
	
	$sqls = "SELECT sounddevice.id, sounddevice.name, sounddevice.ip, sounddevice.devaddr,sounddevice.dbvalue from sounddevice where groupid= '$streamid'";

	$results=mysqli_query($con,$sqls) or die(mysqli_error($con));
	$Num	=	mysqli_num_rows($results);
	//$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	while($row=mysqli_fetch_array($results))
	{
		$deviceinfo[] = array(
									"id"=>$row['id'],"name"=>$row['name'],"ip"=>$row['ip'],
									"devaddr"=>$row['devaddr'],"dbvalue"=>$row['dbvalue']
									
							   );
	}
	
	$smarty->assign("deviceinfo",$deviceinfo);
	
	@mysqli_free_result($results);
	unset($deviceinfo,$row,$sqls);
	
	
	/*
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
*/
	$smarty->assign("pagestr",$Num);
	$smarty->assign("start",$start);	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("zhaoshengManager/streamdisplayterminal.html");
}
?>