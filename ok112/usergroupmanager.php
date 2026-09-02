<?php
if (!session_id()) session_start();
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
	$smarty->assign("user_group_manager",$user_group_manager);
	
	$smarty->assign("Usergroupmanager",$Usergroupmanager);
	$smarty->assign("Searchform",$Searchform);
	$smarty->assign("Revise",$Revise);	
	//获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	
	require('editor.php');
	$smarty->assign('descriptionarea',$descriptionarea);
	$userarray="";
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
	//查询
	$sql="";
	if($_GET['searchvalue']!="")
	{
		if($_GET['searchkey']!="")
		{
			if($_GET['searchkey']=="name")
			{
				if($_GET['searchsequence']!="")
				{
					$sql="SELECT * FROM usergroup WHERE usergroup.name LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY '$_GET[searchsequence]' desc";
				}
				else
				{
					$sql="SELECT * FROM usergroup WHERE usergroup.name LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY usergroup.id desc";
				}
			}
		}	
		else
		{	
			$sql="SELECT * FROM usergroup WHERE usergroup.name LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY usergroup.id desc";
		}
	}
	else
	{
		$sql="SELECT DISTINCT * FROM usergroup ORDER BY usergroup.id DESC ";
	}
	if(!isset($_SESSION['admin_id']))
	{
	
		$result	=	mysqli_query($con,$sql);
		$Num=mysqli_num_rows($result);
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	}
	else
	{
		$result	=	mysqli_query($con,$sql);
		$Num=mysqli_num_rows($result);
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	}
	//获取组信息
	while ($row1 = mysqli_fetch_array($result)) 
	{
		$array1[]=array("id"=>$row1['id'],"name"=>$row1['name'],"info"=>$row1['info'],"taskpriv"=>$row1['taskpriv'],"terminalpriv"=>$row1['terminalpriv'],"mediapriv"=>$row1['mediapriv'],"userpriv"=>$row1['userpriv'],"serverpriv"=>$row1['serverpriv'],"folderpriv"=>$row1['folderpriv'],"terminalgrouppriv"=>$row1['terminalgrouppriv'], "alarmgrouppriv"=>$row1['alarmgrouppriv'],"bellpriv"=>$row1['bellpriv'],"admpriv"=>$row1['admpriv'],"telephonepriv"=>$row1['telephonepriv'],"powerplay"=>$row1['powerplay'],"level"=>$row1['level'],"ttspriv"=>$row1['ttspriv']);
	}
	
	$smarty->assign("group",$array1);
	@mysqli_free_result($result);
	unset($array1,$row1,$sql);
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
	//传递SESSION值
	$smarty->assign("start",$start);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("UserGroupManager/usergroupmanager.html");
}
?>