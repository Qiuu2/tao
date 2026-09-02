<?php
if (!session_id()) session_start();
header("content-type:text/html; charset=utf-8");//ҳ
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');

require_once("language/".$_SESSION['language'].".php");//
$smarty->assign("language",$_SESSION['language']);//
if(empty($_SESSION['admin_id']))
{
	require_once('location:login.php');
	exit;
}
else
{
	/*̬ʾҳı*/
	$smarty->assign('displaymedia',$displaymedia);
	$smarty->assign("Revise",$Revise);
	/*̬ʾҳı*/
	
	require('editor.php');
	$smarty->assign('descriptionarea',$descriptionarea);
	
	$array=array();
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
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		$_SESSION['tran_mid_value'] = $taskid;
	}
	else
	{
		$taskid = $_SESSION['tran_mid_value'];
	}


	$sql ="SELECT offlinemedia.id,offlinemedia.name AS medianame,offlinemedia.size,offlinemedia.typeid,offlinemediaofterminal.offlinestate,offlinemediaofterminal.taskid FROM offlinemedia,offlinemediaofterminal WHERE offlinemediaofterminal.mediaid = offlinemedia.id  AND offlinemediaofterminal.terminalid='$taskid' AND offlinemediaofterminal.taskid='0'";
	
	if(!isset($_SESSION['admin_id']))
	{
		$result	=	mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
		$Num	=	mysqli_num_rows($result);
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
	}
	else
	{
		$result	=	mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
		$Num	=	mysqli_num_rows($result);
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
	}
	
	while ($row = mysqli_fetch_array($result)) 
	{
		$mediaid=$row['id'];
		$counttaskid=0;
		$sqls ="SELECT COUNT(taskid) FROM offlinemediaofterminal WHERE mediaid='$mediaid' AND terminalid='$taskid'";
		$results	=	mysqli_query($con,$sqls) or die("Execute error".mysqli_error($con));
		while ($rows = mysqli_fetch_array($results)) 
		{
			$counttaskid=$rows['0'];
		}
		$array[]=array("id"=>$mediaid,"medianame"=>$row['medianame'],"size"=>$row['size'],"typeid"=>$row['typeid'],"offlinestate"=>$row['offlinestate'],"countid"=>$counttaskid);		
	}
	$smarty->assign("info",$array);
	$smarty->assign("terminalid",$taskid);
	@mysqli_free_result($result);
	unset($array,$row,$sql);

	//ҳ
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
		$smarty->assign("set_flag",1);
	$smarty->assign("start",$start);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("displayproperty/displaymedia.html");
}
?>