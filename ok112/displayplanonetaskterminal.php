<?php
if (!session_id()) session_start();
header("content-type:text/html;charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require_once('editor.php');
require_once("language/".$_SESSION['language'].".php");
$smarty->assign("language",$_SESSION['language']);


if(empty($_SESSION['admin_id']))
{
	header("location:login.php");	
}
else
{
	/*动态显示页面文本内容*/
	
	$smarty->assign("Bellmanager",$Bellmanager);
	
	$smarty->assign("Searchform",$Searchform);
	
	$smarty->assign("Revise",$Revise);
	/*动态显示页面文本内容*/
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
	$taskid = "";
	$task_id=$_SESSION['tran_mid_value'];
	if(isset($_GET['taskid']))
	{
		$taskid = trim($_GET['taskid']);
		$_SESSION['tran_mid_value']=$taskid;
	
	}
	else
	{
		$taskid = $task_id;
	
	}
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	$sql_terminal = "SELECT terminal.id,terminaloftask.groupid, terminal.terminalname, ";

	$sql_terminal.= "terminal.typeid, terminal.netstate, terminal.devicestate, ";

	$sql_terminal.= "terminal.taskstate, terminal.ip, terminal.volume ";

	$sql_terminal.= "FROM terminal,terminaloftask ";

	$sql_terminal.= "WHERE terminal.id = terminaloftask.terminalid ";

	$sql_terminal.= "AND terminaloftask.taskid = '$taskid' ORDER BY CONVERT(terminal.terminalname USING utf8)";
	
	//$sql_terminal = "SELECT id, groupid, terminalname, typeid, netstate, devicestate, taskstate, ip, volume ";
	//$sql_terminal.= "FROM terminal WHERE terminal.id IN ";
	//$sql_terminal.= "(SELECT terminaloftask.terminalid FROM terminaloftask WHERE terminaloftask.taskid = '$taskid')";

	if(!isset($_SESSION['admin_id']))
	{
		$result_terminal = mysqli_query($con,$sql_terminal)or die("Execute error".mysqli_error($con));
		$Num = mysqli_num_rows($result_terminal);
		$result_terminal = mysqli_query($con,$sql_terminal." LIMIT $start,$NumOfPage")or die("Execute error".mysqli_error($con));
	}
	else
	{
		$result_terminal = mysqli_query($con,$sql_terminal)or die("Execute error".mysqli_error($con));
		$Num = mysqli_num_rows($result_terminal);
		$result_terminal = mysqli_query($con,$sql_terminal." LIMIT $start,$NumOfPage")or die("Execute error".mysqli_error($con));
	}
	while($row_terminal = mysqli_fetch_array($result_terminal))
	{
		$terminal_info[] = array("id"=>$row_terminal['id'],"groupid"=>$row_terminal['groupid'],"terminalname"=>$row_terminal['terminalname'],"typeid"=>$row_terminal['typeid'],"netstate"=>$row_terminal['netstate'],"devicestate"=>$row_terminal['devicestate'],"taskstate"=>$row_terminal['taskstate'],"ip"=>$row_terminal['ip'],"volume"=>$row_terminal['volume']);
	
	}
	
	$smarty->assign("terminal_info",$terminal_info);
	@mysqli_free_result($result_terminal);
	unset($terminal_info,$row_terminal,$sql_terminal);
	
	$sql_type = "select id, name from terminaltype";
	$result_type = mysqli_query($con,$sql_type) or die();
	while($row_type = mysqli_fetch_array($result_type))
	{
		$type_info[] = array("id"=>$row_type['id'],"name"=>$row_type['name']);
	}
	
	$smarty->assign("type_info",$type_info);
	@mysqli_free_result($result_type);
	unset($type_info,$row_type,$sql_type);
	//////////////////////////////////////////////////////////////////
	$sql_stream = "SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream";

	//$sql_stream = "SELECT streamid, name FROM serverplaystream";

	$result_stream = mysqli_query($con,$sql_stream) or die(mysqli_error($con));

	while($row_stream = mysqli_fetch_array($result_stream))
	{
		$stream_info[] = array("id"=>$row_stream['streamid'],"name"=>$row_stream['name']);
	}
	$smarty->assign("stream_info",$stream_info);
	
	@mysqli_free_result($result_stream);
	unset($stream_info,$row_stream,$sql_stream);

	//分页
	if($Num != 0)
	{
		require_once("pagination.class.php");
		$p = new pagination;
		$p->Items($Num);
		$p->limit($NumOfPage);
		$p->target("?id=".$_GET['taskid']."&");
		$p->currentPage($_GET['page']);
		$p->adjacents(3); 
		$smarty->assign("pagestr",$p->show());
	}
	//输出session
  
	$smarty->assign("chinese_big5_english",$_SESSION['language']);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("BellManager/displayonetaskterminal.html");
}
?>
