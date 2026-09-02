<?php
if (!session_id()) session_start();
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();
$smarty->assign("registerflag",$_SESSION['registerflag']);
if(empty($_SESSION['admin_id']))
{
	require_once('login.php');	
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$language);
	$smarty->assign("user_manager",$user_manager);

	$smarty->assign("Usermanger",$Usermanger);
	$smarty->assign("Searchform",$Searchform);
	$smarty->assign("Revise",$Revise);
	//判断用户管理或超级用户
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(is_admin($con,$_SESSION['username']) || have_rights("userpriv"))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	//引入分页
	require('editor.php');
	$smarty->assign('descriptionarea',$descriptionarea);
	$Num=0;
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
	$searchsequence=0;
	if(!isset($_GET['searchsequence']))
	{
	$searchsequence=$_GET['searchsequence'];
	}
	
	//查询
	$sql="";
	if($_GET['searchvalue']!="")
	{
		if($_GET['searchkey']!="")
		{
			if($_GET['searchkey']=="username")
			{
				if($_GET['searchsequence']!="")
				{
					$sql="SELECT * FROM book_admin WHERE username !='admin' and book_admin.username LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY '$searchsequence' desc";
				}
				else
				{
					$sql="SELECT * FROM book_admin WHERE username !='admin' and book_admin.username LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY book_admin.id desc";
				}
			}
		}	
		else
		{	
			$sql="SELECT * FROM book_admin WHERE username !='admin' and book_admin.username LIKE '%".$_GET['searchvalue']."%' ORDER BY book_admin.id desc";
		}
	}
	else
	{
		$user_id=$_SESSION['userid'];
		
		if($_SESSION['userid']==1)
		$sql="SELECT * FROM book_admin WHERE username !='admin' ORDER BY book_admin.id DESC";
		else
		$sql="SELECT * FROM book_admin where username !='admin' and id='$user_id' ORDER BY book_admin.id DESC";
	}
	if(!isset($_SESSION['admin_id']))
	{
		$result	=	mysqli_query($con,$sql);
		$Num	=	mysqli_num_rows($result);
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	}
	else
	{
		$result	=	mysqli_query($con,$sql);
		$Num	=	mysqli_num_rows($result);
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	}
	$info=array();
	while ($row = mysqli_fetch_array($result)) 
	{
		$groupname ="";	
		$usergroupid=$row['usergroupid'];
		$result2	=	mysqli_query($con,"SELECT name FROM usergroup WHERE id = $usergroupid");
		if($row2 = mysqli_fetch_array($result2))
		{	
				$groupname = $row2['name'];
		}
			
		$info[]=	array("id"=>$row['id'],"username"=>$row['username'],"enable"=>$row['enable'],"userpwd"=>$row['userpwd'],"usergroupid"=>$row['usergroupid'],"fullname"=>$row['fullname'],"info"=>$row['info'], "groupname"=>$groupname);
	}
	$smarty->assign("info",$info);
	
	@mysqli_free_result($result);

	unset($info,$row,$sql);
	$group=array();
	//读取用户组基本信息
	$result1 =	mysqli_query($con,"SELECT id,name FROM usergroup");
	while ($row1 = mysqli_fetch_array($result1)) 
	{
		$array1[]	=array("id"=>$row1['id'],"name"=>$row1['name']);
	}
	$smarty->assign("group",$group);
	@mysqli_free_result($result1);
	unset($row1);
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
	//输出session
	$smarty->assign("start",$start);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("UserManager/usermanager.html");
}
?>