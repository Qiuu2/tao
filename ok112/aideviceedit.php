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

	//获取参数
	$shibiedeviceid = "";
	
	if(isset($_GET['shibiedeviceid']))
	{
		$shibiedeviceid = trim($_GET['shibiedeviceid']);
	}
	
	//获取分区信息
	$sql="SELECT * FROM ai_people WHERE ai_people.id = '$getid' and ai_people.shibiedeviceid = '$shibiedeviceid'";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
	
		$streaminfo = array("get_id"=>$row['id'],"shibiedeviceid"=>$row['shibiedeviceid'],"deviceaddr"=>$row['deviceaddr'],"peopleidcard"=>$row['peopleidcard'],"deviceip"=>$row['deviceip'],"boyname1"=>$row['boyname1'],"boyname2"=>$row['boyname2'],"boyname3"=>$row['boyname3'],"peoplename"=>$row['peoplename']);

	}
	
	$smarty->assign("streaminfo",$streaminfo);
	
	@mysqli_free_result($result);
	
	unset($streaminfo,$row,$sql);
	$smarty->assign("streamid",$getid);

	$smarty->assign("shibiedeviceid",trim($_GET['shibiedeviceid']));
	$smarty->display("ai_Manager/aideviceedit.html");}
?>