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

		$smarty->assign("modify_bell_scheme",$modify_bell_scheme);
		$sql = "SELECT id,sn FROM usersn where userid='0'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		while($row = mysqli_fetch_array($result))
		{
			$get_mac[] = array(
								"id"=>$row['id'],"sn"=>$row['sn']	
							 ); 
		}
		
		$smarty->assign("get_mac",$get_mac);
		@mysqli_free_result($result);
		unset($get_mac);
		$smarty->assign("userid",$userid);
		$smarty->display("UserManager/modify_mac.html");
		
}
?>
