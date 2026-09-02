<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');
require_once('inc/common.php');
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

	$smarty->assign("set_remote_map",$set_remote_map);
	$getid=$_GET['id'];

	$termianl_sql = "SELECT keyid,mediaid,keyname FROM shortcutkeytask WHERE shortcutkeytask.keyid = $getid ORDER BY shortcutkeytask.id ";
	
	$termianl_result = mysqli_query($con,$termianl_sql) or die(mysqli_error($con));
	$keytask=array();
	while($termianl_row = mysqli_fetch_array($termianl_result))
	{
		$keytask = array("keyid"=>$termianl_row['keyid'],"mediaid"=>$termianl_row['mediaid'],"keyname"=>$termianl_row['keyname']);
	}
	
	$smarty->assign("keytask",$keytask);
	mysqli_free_result($termianl_result);
	unset($termianl_sql,$termianl_row);
	
		$termianl_sql = "SELECT mediaid,typeid FROM shortcutkeytask,media WHERE shortcutkeytask.keyid = $getid AND media.id=shortcutkeytask.mediaid ORDER BY shortcutkeytask.id ";
	
	$termianl_result = mysqli_query($con,$termianl_sql) or die(mysqli_error($con));
	$keymedia=array();
	while($termianl_row = mysqli_fetch_array($termianl_result))
	{
		$keymedia[] = array("mediaid"=>$termianl_row['mediaid'],"typeid"=>$termianl_row['typeid']);
	}
	
	$smarty->assign("keymedia",$keymedia);
	mysqli_free_result($termianl_result);
	unset($termianl_sql,$termianl_row);
	
		$userid=$_SESSION['userid'];
    $filelist = get_filettslist($con,$userid); 
	$smarty->assign("tree_info",$filelist);
	unset($tree);
	$smarty->assign("getid",$getid);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("keytask_mapping/keymodify_task_mapping.html");
}
?>