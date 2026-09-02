<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

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
	$smarty->assign("remote_task",$remote_task);
	$smarty->assign("Filetaskmanager",$Filetaskmanager);
	$smarty->assign("Searchform",$Searchform);
	$smarty->assign("Revise",$Revise);
	require_once("User_Rights_Manage/verify_user_rights_class.php");

	if(is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	$userid=$_SESSION['userid'];
	require('editor.php');


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
	//
	if(!empty($_GET['searchvalue']))
	{
		if(trim($_GET['searchkey']) =="name")
		{

		if($_SESSION['username']=="admin")
		{
			$sql = "SELECT DISTINCT id,camername,camerip FROM camer WHERE camername LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id ASC";
		}
		else
		{
			$sql = "SELECT DISTINCT id,camername,camerip FROM camer WHERE camername LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id ASC";
		}
		}
		else if(empty($_GET['searchkey']))
		{
			$sql = "SELECT DISTINCT id,camername,camerip FROM camer WHERE camername LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id ASC";
		}
	}
	else
	{
		if($_SESSION['username']=="admin")
		{
			$sql = "SELECT DISTINCT id,camername,camerip FROM camer ORDER BY id ASC";
		}
		else
		{
			$sql = "SELECT DISTINCT id,camername,camerip FROM camer ORDER BY id ASC";
		}
	}
	//ѯ
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
	$info=array();
	while ($row = mysqli_fetch_array($result)) 
	{
		$info[] = array("id"=>$row['id'],"camername"=>$row['camername'],"camerip"=>$row['camerip']);	
	}
	$smarty->assign("info",$info);
	
	mysqli_free_result($result);
	unset($sql,$row,$info);
	//
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
	$smarty->display("camer/camer_event.html");
}
?>