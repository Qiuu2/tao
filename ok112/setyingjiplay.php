<?php
if (!session_id()) session_start();
header("content-type:text/html; charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require_once('inc/common.php');
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
	$smarty->assign("media_task_add",$media_task_add);
	$smarty->assign("Belladdtask",$Belladdtask);
	/*动态显示页面文本内容*/
	$terminal_id = "";
	if(isset($_GET['id']))
	{
		$terminal_id = trim($_GET['id']);
		$_SESSION['setterminalid'] = $terminal_id;
	}
	if($terminal_id == "")
	{
		$terminal_id = $_SESSION['setterminalid'];
	}

	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	$terminalist = get_quick_terminal($type,$terminal_id);

	$userid=$_SESSION['userid'];
	$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
	if($row = mysqli_fetch_array($results))
	{
		$getlevel=$row['level'];
	}
	$smarty->assign("get_result",$get_result);
	$smarty->assign("userid",$userid);
	$smarty->assign("getlevel",$getlevel);
    $smarty->assign("terminal_id",$terminal_id);
	$smarty->assign("terminalist",$terminalist);
	$filelist = get_filelist($_SESSION['username']);
	$smarty->assign("filelist",$filelist);	
	$smarty->display("TerminalManager/set_yingjiplay.html");
	
}
?>
