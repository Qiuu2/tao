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
	require_once('login.php');	
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("bellcopy",$bellcopy);
	
	$smarty->assign("Bellcopytask",$Bellcopytask);
	
	$task_id = 0;
	
	if(isset($_GET['id']))
	{
		$task_id = trim($_GET['id']);
		
	}
	
	/*动态显示页面文本*/
	{	
		$result	=	mysqli_query($con,"SELECT taskid,info FROM `task` where info=''");
		while ($row = mysqli_fetch_array($result)) 
		{			
			$array[]	=	 array("id"=>$row['taskid'],"info"=>$row['info']);
		}
		
		$smarty->assign("bell",$array);
	}	
	$smarty->assign("taskid",$task_id);

	$smarty->assign("admin_id",$_SESSION['admin_id']);

	$smarty->display("BellManager/bellcopy.html");
}
?>
