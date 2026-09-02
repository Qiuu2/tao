<?php
if (!session_id()) session_start();
header("content-type:text/html; charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
if(empty($_SESSION['admin_id']))
{
	//require_once('login.php');
	header("location:login.php");	
}
else
{
	require_once("verify_user_sessionin_valid.php");
	verifysessionvalid();
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("file_folder_manager",$file_folder_manager);
	
	$smarty->assign("Filefoldermanager",$Filefoldermanager);
	$smarty->assign("Searchform",$Searchform);
	$smarty->assign("Revise",$Revise);
	//获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(have_rights("folderpriv") || is_admin($con,$_SESSION['username']))
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
	//判断用户是否是管理员
	//是就可以操作所有用户 不是则只能看到共享和自己
	$user_name = "";
	$group_id = 1;
	if( !empty($_SESSION['username']) )
	{
		$user_name = trim($_SESSION['username']);
	}
	$user_sql = "SELECT usergroupid FROM book_admin WHERE book_admin.username = '$user_name'";
	$user_result = mysqli_query($con,$user_sql) or die(mysqli_error($con));
	if($user_row = mysqli_fetch_array($user_result))
	{
		$group_id = trim($user_row['usergroupid']);
	}
	if($group_id == 1)
	{
		$sql = "SELECT filefolder.id, filefolder.name, filefolder.priority, filefolder.createtime "; 
		$sql.= "FROM filefolder ORDER BY filefolder.id ";
		
		if(!empty($_GET['searchvalue']))
		{
			if(!empty($_GET['searchkey']))
			{
				if($_GET['searchkey']=="name")
				{
					if(!empty($_GET['orderby']))
					{
						$sql = "SELECT filefolder.id,filefolder.name,filefolder.priority,filefolder.createtime FROM filefolder ";
						$sql.= "WHERE filefolder.name like '%".trim($_GET[searchvalue])."%' ";
						$sql.= "ORDER BY $_GET[orderby] DESC ";
					}
					else
					{
						$sql = "SELECT filefolder.id,filefolder.name,filefolder.priority,filefolder.createtime FROM filefolder ";
						$sql.= "WHERE filefolder.name like '%".trim($_GET[searchvalue])."%' ";
						$sql.= "ORDER BY filefolder.id ";
					}
				}
				else if($_GET['searchkey']!="name")
				{
					if(!empty($_GET['orderby']))
					{
						$sql = "SELECT filefolder.id,filefolder.name,filefolder.priority,filefolder.createtime FROM filefolder ";
						$sql.= "WHERE filefolder.name like '%".trim($_GET[searchvalue])."%' ";
						$sql.= "'ORDER BY $_GET[orderby] DESC ";
					}
					else
					{
						$sql = "SELECT filefolder.id,filefolder.name,filefolder.priority,filefolder.createtime FROM filefolder ";
						$sql.= "WHERE filefolder.name like '%".trim($_GET[searchvalue])."%' ";
						$sql.= "ORDER BY filefolder.id ";
					}
				}
			}
			$sql = "SELECT filefolder.id,filefolder.name,filefolder.priority,filefolder.createtime FROM filefolder ";
			$sql.= "WHERE filefolder.name like '%".trim($_GET[searchvalue])."%' ";
			$sql.= "ORDER BY filefolder.id ";
		}
		else
		{
			$sql="SELECT filefolder.id,filefolder.name,filefolder.priority,filefolder.createtime ";
			$sql.="FROM filefolder ";
			$sql.="ORDER BY filefolder.id ";
		}	
	}
	else
	{
		$sql = "SELECT filefolder.id, filefolder.name, filefolder.priority, filefolder.createtime ";
		$sql.= "FROM filefolder WHERE filefolder.id = '0' OR filefolder.userid = '$group_id' ORDER BY filefolder.id ";
		
		if(!empty($_GET['searchvalue']))
		{
			if(!empty($_GET['searchkey']))
			{
				if($_GET['searchkey']=="name")
				{
					if(!empty($_GET['orderby']))
					{
						$sql="SELECT filefolder.id,filefolder.name,filefolder.priority,filefolder.createtime FROM filefolder ";
						$sql.="WHERE filefolder.name like '%".trim($_GET[searchvalue])."%' and (filefolder.id = '0' OR filefolder.userid = ";
						$sql.="'$group_id' ORDER BY $_GET[orderby] DESC";
					}
					else
					{
						$sql="SELECT filefolder.id,filefolder.name,filefolder.priority,filefolder.createtime FROM filefolder ";
						$sql.="WHERE filefolder.name like '%".trim($_GET[searchvalue])."%' and (filefolder.id = '0' OR filefolder.userid = ";
						$sql.="'$group_id' ORDER BY filefolder.id ";
					}
				}
				else if($_GET['searchkey']!="name")
				{
					if(!empty($_GET['orderby']))
					{
						$sql="SELECT filefolder.id,filefolder.name,filefolder.priority,filefolder.createtime FROM filefolder ";
						$sql.="WHERE filefolder.name like '%".trim($_GET[searchvalue])."%' and (filefolder.id = '0' OR filefolder.userid = ";
						$sql.="'$group_id' ORDER BY $_GET[orderby] DESC ";
					}
					else
					{
						$sql="SELECT filefolder.id,filefolder.name,filefolder.priority,filefolder.createtime FROM filefolder ";
						$sql.="WHERE filefolder.name like '%".trim($_GET[searchvalue])."%' and (filefolder.id = '0' OR filefolder.userid = ";
						$sql.="'$group_id' ORDER BY filefolder.id ";
					}
				}
			}
			$sql="SELECT filefolder.id,filefolder.name,filefolder.priority,filefolder.createtime FROM filefolder ";
			$sql.="WHERE filefolder.name like '%".trim($_GET[searchvalue])."%' and (filefolder.id = '0' OR filefolder.userid = ";
			$sql.="'$group_id' ORDER BY filefolder.id ";
		}
		else
		{
			$sql="SELECT filefolder.id,filefolder.name,filefolder.priority,filefolder.createtime ";
			$sql.="FROM filefolder WHERE filefolder.id = '0' OR filefolder.userid = ";
			$sql.="'$group_id' ORDER BY filefolder.id ";
		}	
	}
	
	@mysqli_free_result($user_result);
	unset($user_sql,$user_row);
	$result	=	mysqli_query($con,$sql);
	$Num	=	mysqli_num_rows($result);
	$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	
	while ($row = mysqli_fetch_array($result)) 
	{		
		$array[] = array("id"=>$row['id'],"foldername"=>$row['name'],"priority"=>$row['priority'],"createtime"=>$row['createtime']);
	}
	
	$smarty->assign("info",$array);
	@mysqli_free_result($result);
	unset($sql,$row,$array);
	//分页
	if($Num != 0){
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
	$smarty->display("FolderManger/filefoldermanager.html");
}
?>
