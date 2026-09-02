<?php
/************************************
	显示该分区已有终端和未分配终端
	即完成添加或移除终端
************************************/
if (!session_id()) session_start();

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
	$smarty->assign("set_shotcut",$set_shotcut);
	$getid = "";
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}

	//=====获取分区中终端id号=====//
	 $sql_stream = "SELECT camername,camerip FROM camer WHERE id = $getid";
	 $result_stream = mysqli_query($con,$sql_stream) or die(mysqli_error($con));
	 while($row_stream = mysqli_fetch_array($result_stream))
	 {
		$smarty->assign("camername",$row_stream['camername']);
		$smarty->assign("camerip",$row_stream['camerip']);
	 }
	 @mysqli_free_result($result_stream);	
	 unset($terminal_id,$row_stream,$sql_stream);

	//获取分区信息
	$sql="SELECT terminalid,area,groupid FROM camerofterminal WHERE camerid = '$getid'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result))
	{	
		$streaminfo[] = array("terminalid"=>$row['terminalid'],"area"=>$row['area'],"groupid"=>$row['groupid']);	
	}
	
	$smarty->assign("streaminfo",$streaminfo);
	@mysqli_free_result($result);
	unset($streaminfo);

	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	$termianllist = create_tree_str($type);
	
	$smarty->assign("termianllist",$termianllist);		
	
	$smarty->assign("streamid",trim($_GET['id']));
	
	$smarty->display("camer/modify_camer_event.html");}
?>