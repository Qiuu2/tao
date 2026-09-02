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

	$type=get_terminal_type(16,$do_php_prompt['Terminal_not_support'],0,0);

	$terminalist = create_tree_str($type);
	
	 $userid=$_SESSION['userid'];
$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
	if($row = mysqli_fetch_array($results))
	{
		$getlevel=$row['level'];
	}
	$smarty->assign("getlevel",$getlevel);

    $smarty->assign("getfolderid",$getfolderid);
	$smarty->assign("terminalist",$terminalist);
	
	$filelist = get_filelist($_SESSION['username']);

	$result = mysqli_query($con,"SELECT media.id, media.name,timelength FROM media WHERE media.folderid = 9 order by id desc");

		while($row = mysqli_fetch_array($result))
		{
			$medialist[] = array("id"=>$row['id'],"name"=>$row['name']);
		}

	$smarty->assign("medialist",$medialist);


//采播终端
	$adm_terminal_sql = "select id, terminalname,typeid from terminal where terminal.typeid in(0,22,32) ";
	
	$adm_terminal_result = mysqli_query($con,$adm_terminal_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($adm_terminal_result) > 0)
	{
		while($adm_terminal_row = mysqli_fetch_array($adm_terminal_result))
		{
			$terminal_info[] = array("id"=>$adm_terminal_row['id'],"typeid"=>$adm_terminal_row['typeid'],"terminalname"=>$adm_terminal_row['terminalname']);	
		   if($adm_terminal_row['typeid']==0)
		   {
		   $smarty->assign("server_id",$adm_terminal_row['id']);
		   }
		}
		
		$smarty->assign("terminal_info",$terminal_info);
		
		@mysqli_free_result($adm_terminal_result);
		
		unset($adm_terminal_sql,$adm_terminal_row,$terminal_info);
	}
	
	$smarty->assign("filelist",$filelist);	

	$smarty->display("Tts/TtsAddFileTask_form.html");
}
?>
