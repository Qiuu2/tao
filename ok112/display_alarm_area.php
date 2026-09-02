<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

if(empty($_SESSION['admin_id']))
{
	header('location:login.php');	
}
else
{
	//验证是否失效
	require_once("verify_user_sessionin_valid.php");

	verifysessionvalid();

	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("alarm_zone",$alarm_zone);
	
	$smarty->assign("Filetaskmanager",$Filetaskmanager);
	$smarty->assign("Searchform",$Searchform);
	$smarty->assign("Revise",$Revise);
	
	// 获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(have_rights("alarmgrouppriv") || is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	
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
	//输入查询
	$userid=$_SESSION['userid'];
	if(!empty($_GET['searchvalue']))
	{
		if(!empty($_GET['searchkey']))
		{
			if(trim($_GET['searchkey'])=="name")
			{
				if(!empty($_GET['searchsequence']))
				{
					$sql = "SELECT alarmarea.id, alarmarea.name, info, createtime FROM alarmarea ";
					$sql.= "WHERE alarmarea.name LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY ".trim($_GET['searchsequence'])." DESC "; 
				}
				else
				{
					$sql = "SELECT alarmarea.id, alarmarea.name, info, createtime FROM alarmarea ";
					$sql.= "WHERE alarmarea.name LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY createtime "; 
				}
			}
		}
		if(empty($_GET['searchkey']))
		{
			if(!empty($_GET['searchsequence']))
			{
				$sql = "SELECT alarmarea.id, alarmarea.name, info, createtime FROM alarmarea ";
				$sql.= "WHERE alarmarea.name LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY ".trim($_GET['searchsequence'])." DESC "; 
			}
			else
			{
				$sql = "SELECT alarmarea.id, alarmarea.name, info, createtime FROM alarmarea ";
				$sql.= "WHERE alarmarea.name LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY createtime "; 
			}
		}
	}
	else
	{
	if($_SESSION['username']=="admin")
		$sql = "SELECT alarmarea.id, alarmarea.name, info, createtime FROM alarmarea ORDER BY createtime DESC ";
	else
		$sql = "SELECT alarmarea.id, alarmarea.name, info, createtime FROM alarmarea where userid='$userid' ORDER BY createtime DESC ";
	}

	if(!isset($_SESSION['admin_id']))
	{
		$result	=	mysqli_query($con,$sql) or die(mysqli_error($con));
		
		$Num	=	mysqli_num_rows($result);
		
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die(mysqli_error($con));
	}
	else
	{
		$result	=	mysqli_query($con,$sql) or die(mysqli_error($con));
		
		$Num	=	mysqli_num_rows($result);
		
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die(mysqli_error($con));
	}
	$info=array();
	while ($row = mysqli_fetch_array($result)) 
	{
		$info[] = array("id"=>$row['id'],"name"=>$row['name'],"info"=>$row['info'],"createtime"=>$row['createtime']);
	}
	
	$smarty->assign("info",$info);
	
	mysqli_free_result($result);
	
	unset($sql,$row,$info);
	
	//显示分页
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
	$smarty->display("alarmmanager/display_area.html");
}
?>
