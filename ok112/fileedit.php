<?php
if (!session_id()) session_start();
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
if(empty($_SESSION['admin_id']))
{
	require_once('login.php');	
}
else
{
	//输出session
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	//读取数据
	$result	=mysqli_query($con,"SELECT * FROM `media` WHERE id='$_GET[id]'");
	if($row = mysqli_fetch_array($result))
	{
		$smarty->assign("id",$_GET['id']);	
		$smarty->assign("name",$row['name']);	
		$smarty->assign("type",$row['typeid']);	
		$smarty->assign("filename",$row['filename']);	
		
		$description = $row['content'];
		require('editor.php');
		$smarty->assign('descriptionarea',$descriptionarea);
		$smarty->display("FileManager/fileedit.html");
	}else
	{
		echo "非法参数";
	}
}
?>