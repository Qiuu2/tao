<?php
if (!session_id()) session_start();
header("content-type:text/html;charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
if(empty($_SESSION['admin_id']))
{
	//require_once('login.php');
	header("location:login.php");
}
else
{

	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("set_shotcut",$set_shotcut);
	$smarty->assign("Setterminalkey",$Setterminalkey);
	//获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(have_rights("terminalpriv") || is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	$terminalnum=0;
	
	if(isset($_GET['terminalnum']))
	{
		$terminalnum = $_GET['terminalnum'];
	}  
	
	$terminalids = "";
	if(isset($_GET['terminalids']))
	{
		$terminalids = $_GET['terminalids'];
	} 
	$terminalidlist = explode(",",$terminalids);
	//取源终端名称
	$sql = "SELECT usersn.id,sn,ischeckmac FROM usersn,serverbaseparam WHERE userid='0' ORDER BY usersn.id asc";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result))
	{
		$terminalinfo[] = array("id"=>$row['id'],"sn"=>trim($row['sn']),"enable"=>trim($row['ischeckmac']),		
								);		
	}
	
	$smarty->assign("terminalids",$terminalids);
	$smarty->assign("terminalnum",$terminalnum);
	$smarty->assign("terminalinfo",$terminalinfo);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("TerminalManager/terminalquanxian.html");
}
?>