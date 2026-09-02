<?php
if (!session_id()) session_start();
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
//输出session
$smarty->assign("admin_id",$_SESSION['admin_id']);
//读取数据
$result	=	mysqli_query($con,"SELECT * FROM `".$DB_PREFIX."msg` WHERE id='$_GET[id]'");
if($row = mysqli_fetch_array($result))
{
	$smarty->assign("id",$_GET['id']);	
	$smarty->assign("title",$row['title']);	
	$smarty->assign("type",$row['type']);	
	$description = $row['content'];
	require('editor.php');
	$smarty->assign('descriptionarea',$descriptionarea);
	$smarty->display("edit.html");
}else{
	echo "error";
}
?>