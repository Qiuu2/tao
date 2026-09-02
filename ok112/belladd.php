<?php
if (!session_id()) session_start();
header("content-type:text/html; charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require_once('inc/common.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if( empty($_SESSION['admin_id']) )
{
	header('Location:login.php');
}
else
{

	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	
	$smarty->assign("language",$_SESSION['language']);
	
	$smarty->assign("add_bell_scheme",$add_bell_scheme);
	$smarty->assign("Belladdtask",$Belladdtask);

	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	//$terminalist = get_grouped_terminal($type);
	$terminalist = create_tree_str($type);
	$smarty->assign("terminalist",$terminalist);
	//获取打铃文件夹中文件

	if($_SESSION['username']=="admin")
	{
	$result = mysqli_query($con,"SELECT media.id, media.name,timelength FROM media WHERE media.folderid IN(SELECT id FROM filefolder WHERE filefolder.parentid = 2 or filefolder.parentid IN(SELECT id FROM filefolder WHERE filefolder.parentid =2)) or media.folderid = 2 order by id desc");
	}
	else
	{
	$userid=$_SESSION['userid'];
		$result = mysqli_query($con,"SELECT media.id, media.name,timelength FROM media WHERE media.folderid IN(SELECT id FROM filefolder WHERE  userid='$userid'AND filefolder.parentid = 2 or filefolder.parentid IN(SELECT id FROM filefolder WHERE filefolder.parentid =2) ) or media.folderid = 2 order by id desc");
	}
	

	if(mysqli_fetch_array($result))
	{
		@mysqli_data_seek($result, 0);
		$gettemp=0;
		while($row = mysqli_fetch_array($result))
		{
			if($gettemp==0)
				$mediatimelength=$row['timelength'];
			$gettemp++;
			$medialist[] = array("id"=>$row['id'],"name"=>$row['name']);
		}
		
	}
	else
	{
		echo "<script>alert('".$add_bell_scheme['library_no_file_add_file']."');</script>";
		echo "<script>window.history.back();</script>";

		exit;
	}
	$username=$_SESSION['username'];
		
	$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE username ='$username')");
	if($row = mysqli_fetch_array($results))
	{
		$getlevel=$row['level'];
	}

	$smarty->assign("getlevel",$getlevel);
	
	$smarty->assign("mediatimelength",$mediatimelength);
	$smarty->assign("medialist",$medialist);
	mysqli_free_result($result);
	unset($medialist);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("BellManager/addbelltask.html");
}
?>

