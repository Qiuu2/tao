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
	
	$set_flag = "";
	if(isset($_GET['flag']))
	{
		$set_flag = trim($_GET['flag']);
		
	
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

	if($set_flag==2)
	{
		$sql="SELECT DISTINCT offlinemedia.id,offlinemedia.name as medianame,offlinemedia.size,offlinemedia.typeid,offlinemediaofterminal.offlinestate ";
	$sql.="FROM offlinemedia,offlinemediaofterminal ";
	$sql.="WHERE offlinemediaofterminal.mediaid = offlinemedia.id  AND offlinemediaofterminal.taskid='$taskid' ";
	}
	else
	{
	$sql="SELECT mediaoftask.taskid,media.id,media.name as medianame,media.size,media.typeid,filefolder.name as foldername ";
	$sql.="FROM media,mediaoftask,filefolder ";
	$sql.="WHERE mediaoftask.mediaid = media.id AND media.folderid = filefolder.id  AND mediaoftask.taskid='$taskid' ORDER BY sort";
	}
	
	
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
		if($set_flag==2)
		{
		$array[]=array("taskid"=>$row['taskid'],"id"=>$row['id'],"medianame"=>$row['medianame'],"size"=>$row['size'],"typeid"=>$row['typeid'],"offlinestate"=>$row['offlinestate']);
		}
		else
		{
		$array[]=array("taskid"=>$taskid,"id"=>$row['id'],"medianame"=>$row['medianame'],"size"=>$row['size'],"typeid"=>$row['typeid'],"foldername"=>$row['foldername'],"offlinestate"=>0);
		}
	}
	$smarty->assign("info",$array);
	@mysqli_free_result($result);
	unset($array,$row,$sql);
	//ҳ
	if($Num != 0)
	{
		require_once("pagination.class.php");
		$p = new pagination;
		$p->Items($Num);
		$p->limit($NumOfPage);
		$p->target("?id=".$_GET['id']."&flag=".$set_flag."&");
		$p->currentPage($_GET['page']);
		$p->adjacents(3);
		$smarty->assign("pagestr",$p->show());
	}
	
	$smarty->assign("chinese_big5_english",$_SESSION['language']);
	$smarty->assign("start",$start);
	$smarty->assign("set_flag",0);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("displayproperty/displaymedia.html");
}
?>