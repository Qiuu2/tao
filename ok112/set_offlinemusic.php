<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');
//验证是否失效

require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

require_once("User_Rights_Manage/verify_user_rights_class.php");

if(is_admin($con,$_SESSION['username']))
{
	$smarty->assign("user_rights",1);
}
else
{
	$smarty->assign("user_rights",0);
}

if(empty($_SESSION['admin_id']))
{
	header("location:login.php");
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("terminal_manager",$terminal_manager);
	/*动态显示页面文本内容*/
	if(isset($_GET['folderid']))
	{
	$getfolderid=$_GET['folderid'];
	$smarty->assign("getfolderid",$getfolderid);
	}
	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	
	$terminalist = createofflineterminal($type);

	$userid=$_SESSION['userid'];
	$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
	$getlevel;
	if($row = mysqli_fetch_array($results))
	{
		$getlevel=$row['level'];
	}
	if(isset($_SESSION['username']))
	{
	$getusername=$_SESSION['username'];
	}
	$smarty->assign("userid",$userid);
	$smarty->assign("getlevel",$getlevel);
   
	$smarty->assign("terminalist",$terminalist);
	$filelist = get_filelist($getusername);
	$smarty->assign("filelist",$filelist);	
	$smarty->display("offlinetask/set_offlinemusic.html");
}
?>
