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

	/*动态显示页面文本	*/
//	$smarty->assign("Streamebatdit",$Streamebatdit);
	
	//获取参数
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	//flag=1，flag=2,2为带目录
	$flag = 1;
	if(isset($_GET['flag']))
	{
		$flag = trim($_GET['flag']);
	}

	$getterminalid = "";
	if(isset($_GET['getterminalid']))
	{
		$getterminalid = trim($_GET['getterminalid']);
	}
	//=====获取分区中终端id号=====//

	 $sql_stream = "SELECT name FROM callgroup WHERE id = $getid";
	
	 $result_stream = mysqli_query($con,$sql_stream) or die(mysqli_error($con));
	
	 while($row_stream = mysqli_fetch_array($result_stream))
	 {
	 	
		$smarty->assign("groupname",$row_stream['name']);
	 }
	
	 
	 
	 @mysqli_free_result($result_stream);
	
	 unset($terminal_id,$row_stream,$sql_stream);

	//获取分区信息
	$sql="SELECT terminalid,area,groupid FROM terminalofcallgroup WHERE selectgroupid = '$getid'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result))
	{
		$streaminfo[] = array("terminalid"=>$row['terminalid'],"area"=>$row['area'],"groupid"=>$row['groupid']);	
	}
	
	$smarty->assign("streaminfo",$streaminfo);
	@mysqli_free_result($result);
	unset($streaminfo);
		
	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);

	if($flag==1)
	{
		$termianllist = get_terminallistoggroup2($type,$getterminalid);
	}
	else if($flag==2)
	{
	  	$termianllist = get_dirarea($type,$getterminalid);	
		
		$sqls="SELECT terminalid,folderid FROM terminaloffolder ";
		$results = mysqli_query($con,$sqls) or die(mysqli_error($con));
		while($rows = mysqli_fetch_array($results))
		{
			$terminalfolder[] = array("terminalid"=>$rows['terminalid'],"folderid"=>$rows['folderid']);	
		}
		
		$smarty->assign("terminalfolder",$terminalfolder);
		@mysqli_free_result($results);
		unset($terminalfolder);
	}
	
	$smarty->assign("termianllist",$termianllist);		
	$smarty->assign("flag",$flag);		
	$smarty->assign("streamid",trim($_GET['id']));
	
	$smarty->display("TerminalManager/call_group_edit.html");}
?>