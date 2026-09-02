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
	
	$smarty->assign("stream_modify",$stream_modify);

	/*动态显示页面文本	*/
	$smarty->assign("Streamebatdit",$Streamebatdit);
	
	//获取参数
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	
	//获取分区信息
	$sql="SELECT sounddevice.ip,sounddevice.name,sounddevice.devaddr,sounddevice.sendport FROM sounddevice WHERE sounddevice.id = '$getid'";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		$streaminfo = array("ip"=>$row['ip'],"name"=>$row['name'],"devaddr"=>$row['devaddr'],"sendport"=>$row['sendport']);
	}
	
	$smarty->assign("streaminfo",$streaminfo);
	
	@mysqli_free_result($result);
	
	unset($streaminfo,$row,$sql);

	$smarty->assign("streamid",trim($_GET['id']));
	
	$smarty->display("zhaoshengManager/soundsdeviceedit.html");}
?>