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
	$result1	=	mysqli_query($con,"SELECT * FROM `".$DB_PREFIX."reply` WHERE m_id='$_GET[id]'");
	$row1 = mysqli_fetch_array($result1);
	$description  = $row1['content'];
	require('editor.php');
	$smarty->assign('descriptionarea',$descriptionarea);
	$smarty->display("reply.html");
}else{
	echo "非法参数";
}
?>