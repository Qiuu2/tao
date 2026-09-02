<?php
/**********************************************************
	超级用户组、超级用户 拥有所有的权限、系统预留
	（自己对自己权限不能修改---只有超级管理员对其它用户修改）
	
	1、获取登陆用户（区分用户）
	超级用户（设置值）  其它用户	
	2、获取用户权限（优先级不管）
	3、保存用户会话（以数组保存）
	4、对操作判断
	
	5、终端与用户绑定
	若无绑定、只有管理员操作
	6、判断用户是否绑定终端
***********************************************************/
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");
//获取、保存当前用户权限
function get_user_right($con,$username)
{
	mysqli_query($con," LOCK TABLE usergroup WRITE, book_admin WRITE");
	$sql_right = "SELECT id,usergroup.name,taskpriv, terminalpriv, mediapriv, userpriv, serverpriv, folderpriv, terminalgrouppriv, ";
	$sql_right.= "alarmgrouppriv, bellpriv, admpriv, telephonepriv, powerplay, usergroup.level, usergroup.ttspriv FROM usergroup ";
	$sql_right.= "WHERE usergroup.id =(SELECT book_admin.usergroupid FROM book_admin WHERE book_admin.username = '$username') ";
	
	$result_right = mysqli_query($con,$sql_right) or die(mysqli_error($con));
	if($row_right = mysqli_fetch_array($result_right))
	{
		$user_right_array['id'] = $row_right['id'];
		$user_right_array['name'] = $row_right['name'];
		$user_right_array['taskpriv'] = $row_right['taskpriv'];
		$user_right_array['terminalpriv'] = $row_right['terminalpriv'];
		$user_right_array['mediapriv'] = $row_right['mediapriv'];
		$user_right_array['userpriv'] = $row_right['userpriv'];
		$user_right_array['serverpriv'] = $row_right['serverpriv'];
		$user_right_array['folderpriv'] = $row_right['folderpriv'];
		$user_right_array['terminalgrouppriv'] = $row_right['terminalgrouppriv'];
		$user_right_array['alarmgrouppriv'] = $row_right['alarmgrouppriv'];
		$user_right_array['bellpriv'] = $row_right['bellpriv'];
		$user_right_array['admpriv'] = $row_right['admpriv'];
		$user_right_array['telephonepriv'] = $row_right['telephonepriv'];
		$user_right_array['powerplay'] = $row_right['powerplay'];
		$user_right_array['level'] = $row_right['level'];
		$user_right_array['ttspriv'] = $row_right['ttspriv'];
	}
	
	$_SESSION['user_right'] = $user_right_array;
	
	mysqli_query($con,"unlock tables");
	@mysqli_free_result($result_right);
	unset($row_right,$sql_right,$user_right_array);
}
/******************************
	判断用户是否有该操作权限
	taskpriv, 
	terminalpriv, 
	mediapriv, 
	userpriv, 
	serverpriv, 
	folderpriv, 
	terminalgrouppriv, 
	alarmgrouppriv, 
	bellpriv, 
	admpriv, 
	telephonepriv, 
	powerplay, 
******************************/
function have_rights($user_operate)
{
	$user_right = $_SESSION['user_right'];
	$can_do = false;
	switch($user_operate)
	{
		case "taskpriv":
		if($user_right['taskpriv'] == 1)
		{
			$can_do = true;
		}
		break;
		case "terminalpriv":
		if($user_right['terminalpriv'] == 1)
		{
			$can_do = true;
		} 
		break;
		case "mediapriv":
		if($user_right['mediapriv'] == 1)
		{
			$can_do = true;
		} 
		break;
		case "userpriv":
		if($user_right['userpriv'] == 1)
		{
			$can_do = true;
		} 
		break;
		case "serverpriv":
		if($user_right['serverpriv'] == 1)
		{
			$can_do = true;
		} 
		break;
		case "folderpriv":
		if($user_right['folderpriv'] == 1)
		{
			$can_do = true;
		} 
		break;
		case "terminalgrouppriv":
		if($user_right['terminalgrouppriv'] == 1)
		{
			$can_do = true;
		} 
		break;
		case "alarmgrouppriv":
		if($user_right['alarmgrouppriv'] == 1)
		{
			$can_do = true;
		} 
		break;
		case "bellpriv":
		if($user_right['bellpriv'] == 1)
		{
			$can_do = true;
		} 
		break;
		case "admpriv":
		if($user_right['admpriv'] == 1)
		{
			$can_do = true;
		} 
		break;
		case "telephonepriv":
		if($user_right['telephonepriv'] == 1)
		{
			$can_do = true;
		} 
		break;
		case "powerplay":
		if($user_right['powerplay'] == 1)
		{
			$can_do = true;
		} 
		break;
		case "ttspriv":
		if($user_right['ttspriv'] == 1)
		{
			$can_do = true;
		} 
		break;
	}
	if($_SESSION['servermodel']==1)
		return $can_do;
	else
		return false;
}
/**************************************
	若用户和终端没有绑定 默认是超级用户	
	用户与终端绑定条件
	1、有终端权限
	2、绑定
**************************************/
//判断用户是否绑定了终端
function is_useroneterminalbind($username,$terminalid,$con)
{//$terminalid一个终端id
	
	$sql_bind = "SELECT * FROM userterminal WHERE userterminal.userid = ";
	$sql_bind.= "(SELECT id FROM book_admin WHERE book_admin.username = '$username') AND userterminal.terminalid = '$terminalid'";

	$result_bind = mysqli_query($con,$sql_bind) or die(mysqli_error($con));
	if(mysqli_num_rows($result_bind) > 0)
	{
		@mysqli_free_result($result_bind);
		unset($sql_bind);
		return true;
	}

	@mysqli_free_result($result_bind);
	unset($sql_bind);

	$sql_terminal = "SELECT terminalname FROM terminal WHERE terminal.id = '$terminalid'";
	$result_terminal = mysqli_query($con,$sql_terminal) or die(mysqli_error($con));
	$row_terminal = mysqli_fetch_array($result_terminal);
	echo "<script>alert('Failed $row_terminal[terminalname]');</script>";

	@mysqli_free_result($result_terminal);
	unset($row_terminal,$sql_terminal);

return false;
}
/**************************************
	系统保留 
	1、超级用户组 id 号为 1
	2、超级用户 admin 可以修改 但不能删除
	
**************************************/
//判断是否是超级用户

function is_admin($con,$username)
{
	$sql_admin = "SELECT * FROM book_admin WHERE book_admin.username = '$username' AND book_admin.usergroupid = '1'";
	$result_admin =  mysqli_query($con,$sql_admin) or die(mysqli_error($con));
	if(mysqli_num_rows($result_admin) > 0)
	{
	
		if($_SESSION['servermodel']==1)
			return true;
		else
			return false;
	}
	
	return false;
}
//$terminalarray终端id数组
function is_usermoreterminalbind($username,$terminalarray)
{
	require_once("inc/config.inc.php");
	foreach($terminalarray as $terminalid)
	{
		if(!is_useroneterminalbind($username,$terminalid))
		{
			return false;
		}
	}
	return true;
}

//弹出退出
function quit_out($message)
{
	echo "<script>alert('".$message."');</script>";
	echo "<script>window.history.back();</script>";
	exit;
}
//显示退出
function text_out()
{
	echo "<div style=\"position:absolute;left:expression((body.clientWidth-300)/2);top:expression((body.clientHeight-200)/2);width:300px;height:50;text-align:center;color:#3366ff;font-size:12px;font-family: Arial, Helvetica, sans-serif, \"宋体\";\">
			<p style=\"font-size:110%;\">Permission dend</p>
			<a href=\"#\" onclick=\"window.history.back();\" onmouseover=\"this.style.color='#ff9933'\" onmouseout=\"this.style.color=''\">back</a>
		</div>";
	exit;
}
/***************************
*	获取登陆用户终端权限
*	1、超级用户拥有所有权限
*	2、非超级用户需要判断
***************************/
function get_login_user_rights($username,$con)
{
	
	$user_rights = 0;
	//超级用户组id为1
	$sql_admin = "SELECT usergroupid FROM book_admin WHERE book_admin.username = '$username'";
	$result_admin = mysqli_query($con,$sql_admin) or die(mysqli_error($con));
	if($row_admin = mysqli_fetch_array($result_admin))
	{
		if($row_admin['usergroupid'] == 1)
		{
			return 1;//拥有所有终端权限
		}
	}
	@mysqli_free_result($result_admin);
	unset($row_admin,$sql_admin);
	//非超级用户权限
	$sql_user = "SELECT terminalpriv FROM usergroup WHERE usergroup.id = (SELECT usergroupid FROM book_admin WHERE book_admin.username = '$username')";
	$result_user = mysqli_query($con,$sql_user) or die(mysqli_error($con));
	if($row_user = mysqli_fetch_array($result_user))
	{
		if($row_user['terminalpriv'] == 1)
		{
			return 2;//需进一步判断
		}
	}
	return 0;//没有权限
}
/*************************************
*	读取普通用户和终端对应关系
*	1、有终端、则可操作
*	2、无终端、则不可操作（即使有权限）
*************************************/
function get_user_of_terminal($username,$con)
{
	require_once("inc/config.inc.php");
	$user_terminal = array();
	$sql_user= "SELECT terminalid FROM userterminal WHERE userterminal.userid = (SELECT	id FROM book_admin WHERE book_admin.username = '$username')";
	$result_user = mysqli_query($con,$sql_user) or die(mysqli_error($con));
	if(mysqli_num_rows($result_user) > 0)
	{
		while($row_user = mysqli_fetch_array($result_user))
		{
			$user_terminal[] = $row_user['terminalid'];
		}
		return $user_terminal;
	}
	else
	{
		return $user_terminal;
	}
}
?>