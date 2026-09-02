<?php
if (!session_id()) session_start();
header("content-type:text/html; charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require_once("inc/config.php");
//验证是否失效
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();
$smarty->assign("registerflag",$_SESSION['registerflag']);
if(empty($_SESSION['admin_id']))
{
	//require_once('login.php');
	header("location:login.php");	
}
else
{	
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("user_modify",$user_modify);
	
	$smarty->assign("Useradd",$Useradd);
	//判断是否有权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(have_rights("userpriv") || is_admin($con,$_SESSION['username']))
	{
		//什么都不做
	}
	else
	{
		quit_out("权限不够");
	}
	//获取终端
	require_once("inc/common.php");
		$type=get_terminal_type(7,$do_php_prompt['Terminal_not_support'],0,0);
	//	$type="(".$type.")";
	$terminal_array_id = get_terminallist("$type", 0);
	//获取用户信息
	$get_userid = "";
	if(isset($_GET['id']))
	{
		$get_userid = trim($_GET['id']);
	}
	$sql_user = "select username, usergroupid, info, userpwd from book_admin where book_admin.id = '$get_userid'";
	$result_user = mysqli_query($con,$sql_user) or die(mysqli_error($con));
	while($row_user = mysqli_fetch_array($result_user))
	{
		$user_info = array(
							"username"=>$row_user['username'],
							"usergroupid"=>$row_user['usergroupid'],
							"info"=>$row_user['info'],
							"userpwd"=>$row_user['userpwd']
						  );
	}
	$smarty->assign("user_info",$user_info);
	
	@mysqli_free_result($result_user);
	unset($row_user,$sql_user);	
	//用户是否有终端权限 有读取终端 无什么都不做

	$sql_user = "select usergroup.terminalpriv from book_admin,usergroup where book_admin.id = '$get_userid' and book_admin.usergroupid = usergroup.id";
	$result_user = mysqli_query($con,$sql_user) or die(mysqli_error($con));
	$row_user = mysqli_fetch_array($result_user);
	if($row_user['terminalpriv'] == 1)
	{
		//读取用户终端的值
		$result_terminal = mysqli_query($con,"SELECT terminalid,groupid FROM userterminal WHERE userterminal.userid = '$get_userid'") or die(mysqli_error($con));
		while($row_terminal = mysqli_fetch_array($result_terminal))
		{
			$terminal_id[] = $row_terminal['terminalid'];
			$group_id[] = $row_terminal['groupid'];
		}
		$smarty->assign("terminal_id",$terminal_id);
		$smarty->assign("group_id",$group_id);	
		
		@mysqli_free_result($result_terminal);
		unset($row_terminal);
		
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	
	@mysqli_free_result($result_user);
	unset($row_user,$sql_user);
	   $user_id=$_SESSION['userid'];
	  if($_SESSION['admin_id']=="administrator")
		$sql="SELECT id,name FROM `usergroup` order by id desc";
	  else
	  	$sql= "SELECT id,name FROM `usergroup` where id IN(select usergroupid from book_admin where id='$user_id') order by id desc";
		$result_group	=	mysqli_query($con,$sql);
	
	while ($row_group = mysqli_fetch_array($result_group)) 
	{
		$group_info[] =	array("id"=>$row_group['id'],"name"=>$row_group['name']);
	}

	$smarty->assign("group_info",$group_info);
	@mysqli_free_result($result_group);
	unset($group_info);
	
	$adm_type_sql = "SELECT ctrlterminalcount FROM serverbaseparam ";	
	$adm_type_result = mysqli_query($con,$adm_type_sql) or die(mysqli_error($con));
	if($adm_type_row = mysqli_fetch_array($adm_type_result))
	{
		$ctrlterminalcount = $adm_type_row['ctrlterminalcount'];
	}

	@mysqli_free_result($adm_type_result);
	unset($adm_type_sql,$adm_type_row);

	$smarty->assign("ctrlterminalcount",$ctrlterminalcount);

	$adm_type_sqls = "SELECT ctrlwind,subwind,camerawind FROM book_admin";
	$adm_type_results = mysqli_query($con,$adm_type_sqls) or die(mysqli_error($con));
	while($adm_type_rows = mysqli_fetch_array($adm_type_results))
	{
		$ctrlwinds[]	=	 array("ctrlwind"=>$adm_type_rows['ctrlwind']);
		$subwinds[]	=	 array("subwind"=>$adm_type_rows['subwind']-1000);
		$camerawinds[]	=	 array("camerawind"=>$adm_type_rows['camerawind']-2000);
	}

	@mysqli_free_result($adm_type_results);
	unset($adm_type_sqls,$adm_type_rows);	
	
	$smarty->assign("ctrlwinds",$ctrlwinds);
	$smarty->assign("subwinds",$subwinds);
	$smarty->assign("camerawinds",$camerawinds);
	
	
	$adm_type_sql = "SELECT ctrlwind,subwind,camerawind FROM book_admin where book_admin.id = '$get_userid'  ";
	$adm_type_result = mysqli_query($con,$adm_type_sql) or die(mysqli_error($con));
	
	if($adm_type_row = mysqli_fetch_array($adm_type_result))
	{
		$smarty->assign("ctrlwind",$adm_type_row['ctrlwind']);
		$smarty->assign("subwind",$adm_type_row['subwind']-1000);
		$smarty->assign("camerawind",$adm_type_row['camerawind']-2000);
	}
	
	@mysqli_free_result($adm_type_result);
	
	unset($adm_type_sql,$adm_type_row);
	
		
	$adm_type_sql = "SELECT id,sn FROM usersn where userid = '$get_userid'";
	$adm_type_result = mysqli_query($con,$adm_type_sql) or die(mysqli_error($con));
	
	while($adm_type_row = mysqli_fetch_array($adm_type_result))
	{
		if($adm_type_row['id']==1)
		$smarty->assign("sn1",$adm_type_row['sn']);
		else if($adm_type_row['id']==2)
		$smarty->assign("sn2",$adm_type_row['sn']);
		else if($adm_type_row['id']==3)
		$smarty->assign("sn3",$adm_type_row['sn']);
	}
	
	@mysqli_free_result($adm_type_result);
	
	unset($adm_type_sql,$adm_type_row);
	
	
	$smarty->assign("userid",$get_userid);

	$smarty->assign("terminal_array_id",$terminal_array_id );
	$smarty->assign("FUZA_PASS",$FUZA_PASS);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("UserManager/usermodify.html");	
}
?>