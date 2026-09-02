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
	$getid = 0;
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	
	//=====获取分区中终端id号=====//
	 $sql_stream = "SELECT terminalid,soundgroup.groupid FROM soundgroup WHERE soundgroup.groupid =$getid";
	 $result_stream = mysqli_query($con,$sql_stream) or die(mysqli_error($con));	
	 $terminal_id=array();
	 while($row_stream = mysqli_fetch_array($result_stream))
	 {
	 	$terminal_id[] = $row_stream['terminalid'];
	 }
	  $smarty->assign("terminal_id",$terminal_id);
	 @mysqli_free_result($result_stream);
	 unset($terminal_id,$row_stream,$sql_stream);

 $sql_device = "SELECT id FROM sounddevice WHERE sounddevice.groupid =$getid";
	 $result_device = mysqli_query($con,$sql_device) or die(mysqli_error($con));	
	 $device_id=array();
	 while($row_device = mysqli_fetch_array($result_device))
	 {
	 	$device_id[] = $row_device['id'];
	 }
	  $smarty->assign("device_id",$device_id);
	 @mysqli_free_result($result_device);
	 unset($device_id,$row_device,$sql_device);

	
	//获取分区信息
	$sql="SELECT soundgroupinfo.name,soundgroupinfo.id FROM soundgroupinfo WHERE soundgroupinfo.id = $getid";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	$streaminfo=array();
	if($row = mysqli_fetch_array($result))
	{
		$streaminfo = array("streamname"=>$row['name'],"id"=>$row['id']);
	}
	
	$smarty->assign("streaminfo",$streaminfo);
	
	@mysqli_free_result($result);
	
	unset($streaminfo,$row,$sql);
	
	//$type =  "(1,3,4,11,13,5,14,15,6,2,7,8,9,10,16,12,17)";
		$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
		$type="(".$type.")";

	$termianllist = update_soundsnogrouplist($type,$getid);
	
	$smarty->assign("termianllist",$termianllist);		
	
	$smarty->assign("streamid",trim($_GET['id']));
	$smarty->display("zhaoshengManager/streambatedit.html");}
?>