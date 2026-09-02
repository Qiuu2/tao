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
	$smarty->assign("user_group_modify",$user_group_modify);

	$smarty->assign("Usergroupmodify",$Usergroupmodify);
	//只有管理员和管理员组能修改组
	if(strcmp($_SESSION['admin_id'],"administrator")==0 || strcmp($_SESSION['username'],"admin")==0)
	{
		$group_id = "";
		if(isset($_GET['id']))
		{
			$group_id = trim($_GET['id']);
		}
		$sql_group = "SELECT * FROM usergroup WHERE usergroup.id = '$group_id'";
	
		$result_group = mysqli_query($con,$sql_group)or die("Execution error".mysqli_error($con));
		
		if($row_group = mysqli_fetch_array($result_group))
		{
			$group_info['name'] =  $row_group['name'];
			$group_info['info'] =  trim($row_group['info']);
			$group_info['taskpriv'] =  $row_group['taskpriv'];
			$group_info['terminalpriv'] =  $row_group['terminalpriv'];
			$group_info['mediapriv'] =  $row_group['mediapriv'];
			$group_info['userpriv'] =  $row_group['userpriv'];
			$group_info['serverpriv'] =  $row_group['serverpriv'];
			$group_info['folderpriv'] =  $row_group['folderpriv'];
			$group_info['terminalgrouppriv'] =  $row_group['terminalgrouppriv'];
			$group_info['alarmgrouppriv'] =  $row_group['alarmgrouppriv'];
			$group_info['bellpriv'] =  $row_group['bellpriv'];
			$group_info['admpriv'] =  $row_group['admpriv'];
			$group_info['telephonepriv'] =  $row_group['telephonepriv'];
			$group_info['powerplay'] =  $row_group['powerplay'];
			$group_info['level'] =  $row_group['level'];
			$group_info['ttspriv'] =  $row_group['ttspriv'];
		}
		$smarty->assign("group_info",$group_info);
		$smarty->assign("id",$group_id);
		@mysqli_free_result($result_group);
		unset($row_group,$sql_group);
	}
	else
	{
		echo "<script>alert('没有权限修改');</script>";
		echo "<script>window.history.back();</script>";
		exit;
	}
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("UserGroupManager/usergroupmodify_form.html");
}
?>