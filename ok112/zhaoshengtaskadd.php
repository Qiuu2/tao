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
	
	$getfolderid=$_GET['folderid'];

	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);

	$terminalist = create_zhaoshengtree_str($type);
	
	$userid=$_GET['userid'];
	$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
	if($row = mysqli_fetch_array($results))
	{
		$getlevel=$row['level'];
	}


		$getresults = mysqli_query($con,"SELECT volume,dbvalue FROM soundtask WHERE taskid=0 ORDER BY volume asc ");
		while($getrows = mysqli_fetch_array($getresults)) 
		{
			if($getrows['volume']==0)
			{
				$db_value0=$getrows['dbvalue'];
				$smarty->assign("db_value0",$db_value0);
			}
			if($getrows['volume']==20)
			{
				$db_value1=$getrows['dbvalue'];
				$smarty->assign("db_value1",$db_value1);
			}
			if($getrows['volume']==40)
			{
				$db_value2=$getrows['dbvalue'];
				$smarty->assign("db_value2",$db_value2);
			}
			if($getrows['volume']==60)
			{
				$db_value3=$getrows['dbvalue'];
				$smarty->assign("db_value3",$db_value3);
			}
			if($getrows['volume']==80)
			{
				$db_value4=$getrows['dbvalue'];
				$smarty->assign("db_value4",$db_value4);
			}
			if($getrows['volume']==100)
			{
				$db_value5=$getrows['dbvalue'];
				$smarty->assign("db_value5",$db_value5);
			}
		}

	$smarty->assign("userid",$userid);
	$smarty->assign("getlevel",$getlevel);

    $smarty->assign("getfolderid",$getfolderid);
	$smarty->assign("terminalist",$terminalist);
	
	$filelist = get_filelist($_SESSION['username']);

	$smarty->assign("filelist",$filelist);	

	$smarty->display("zhaoshengtask/AddFileTask_form.html");
}
?>
