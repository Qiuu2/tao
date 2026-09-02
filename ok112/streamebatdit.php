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

	//=====获取分区中终端id号=====//
	
	 $sql_stream = "SELECT terminalid FROM terminalofgroup WHERE groupid = $getid";
		
	 $result_stream = mysqli_query($con,$sql_stream) or die(mysqli_error($con));
	
	 while($row_stream = mysqli_fetch_array($result_stream))
	 {
	 	$terminal_id[] = $row_stream['terminalid'];
	 }
	
	 $smarty->assign("terminal_id",$terminal_id);
	  unset($terminal_id,$row_stream,$sql_stream);
	//mysqli_free_result($result_stream);
	

	$streaminfo=array();
	//获取分区信息
	$sql="SELECT serverplaystream.name,serverplaystream.info FROM serverplaystream WHERE serverplaystream.streamid = '$getid'";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		$streaminfo = array("streamname"=>$row['name'],"info"=>$row['info']);
	}
	
	$smarty->assign("streaminfo",$streaminfo);
	
	//mysqli_free_result($result);
	
	unset($streaminfo,$row,$sql);
	
	//$type =  "(1,3,4,11,13,5,14,15,6,2,7,8,9,10,16,12,17)";
	$type=get_terminal_type(7,$do_php_prompt['Terminal_not_support'],0,0);

	$type="(".$type.")";
		
	$termianllist = get_terminallistoggroup($type,$getid);
	
	$smarty->assign("termianllist",$termianllist);		
	
	$smarty->assign("streamid",trim($_GET['id']));
	
	$smarty->display("StreamManager/streambatedit.html");}
?>