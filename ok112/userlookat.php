<?php
if (!session_id()) session_start();
header("content-type:text/html; charset=utf-8");//ҳ
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	require_once('location:login.php');
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("group_user",$group_user);
	
	$smarty->assign("Displayuser",$Displayuser);
	//导入分页
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
	$groupid = "";
	if(isset($_GET['id']))
	{
		$groupid = trim($_GET['id']);
		$_SESSION['tran_mid_value'] = $groupid;
	}
	else
	{
		$groupid = $_SESSION['tran_mid_value'];
	}
	$sql = "SELECT id,username,info,fullname FROM book_admin WHERE book_admin.usergroupid = '$groupid' ";
	
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
		$array[]=array("id"=>$row['id'],"username"=>$row['username'],"info"=>$row['info'],"fullname"=>$row['fullname']);
	}
	
	$smarty->assign("users",$array);
	@mysqli_free_result($result);
	unset($array,$row,$sql);
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
	$smarty->display("UserGroupManager/displayuser.html");
}
?>