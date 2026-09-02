<?php
if (!session_id()) session_start();
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
$result	=	mysqli_query($con,"SELECT * FROM `".$DB_PREFIX."admin` WHERE id='$_SESSION[admin_id]'");
if($row = mysqli_fetch_array($result))
{
	$smarty->assign("id",$_SESSION['admin_id']);	
	$smarty->assign("username",$row['username']);	
}
//输出session
$smarty->assign("admin_id",$_SESSION['admin_id']);
$smarty->display("pwd.html");
?>