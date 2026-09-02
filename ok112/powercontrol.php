<?php
if (!session_id()) session_start();

require_once('inc/smarty.inc.php');

require_once('inc/common.php');
require_once('inc/config.inc.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	require_once('login.php');	
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	
	$smarty->assign("language",$_SESSION['language']);
	
	$smarty->assign("stream_manager",$stream_manager);
	
	$smarty->assign("Streammanager",$Streammanager);
	
	$smarty->assign("Searchform",$Searchform);
	
	$smarty->assign("Revise",$Revise);
	//获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
		
	require('editor.php');

	$sqlstts="SELECT * FROM powertimeqi ORDER BY terminalid ASC";
	$resulttts	= mysqli_query($con,$sqlstts);
	
	$aitts=array();
	$getpowernum=0;
	while ($rowttss = mysqli_fetch_array($resulttts)) 
	{
		$getpowernum++;
		$aitts[] = array("terminalid"=>$rowttss['terminalid'],"terminalname"=>$rowttss['terminalname'],"power1"=>$rowttss['power1'],
		"power2"=>$rowttss['power2'],"power3"=>$rowttss['power3'],"power4"=>$rowttss['power4'],"power5"=>$rowttss['power5'],"power6"=>$rowttss['power6'],"power7"=>$rowttss['power7']
		,"power8"=>$rowttss['power8'],"power9"=>$rowttss['power9'],"power10"=>$rowttss['power10'],"power11"=>$rowttss['power11'],"power12"=>$rowttss['power12'],"power13"=>$rowttss['power13'],"power14"=>$rowttss['power14'],"power15"=>$rowttss['power15'],"power16"=>$rowttss['power16']
		,"powername1"=>$rowttss['powername1'],"powername2"=>$rowttss['powername2'],"powername3"=>$rowttss['powername3'],"powername4"=>$rowttss['powername4']
		,"powername5"=>$rowttss['powername5'],"powername6"=>$rowttss['powername6'],"powername7"=>$rowttss['powername7'],"powername8"=>$rowttss['powername8']
		,"powername9"=>$rowttss['powername9'],"powername10"=>$rowttss['powername10'],"powername11"=>$rowttss['powername11'],"powername12"=>$rowttss['powername12']
		,"powername13"=>$rowttss['powername13'],"powername14"=>$rowttss['powername14'],"powername15"=>$rowttss['powername15'],"powername16"=>$rowttss['powername16']
		);
	}
	
	$smarty->assign("getpowernum", $getpowernum);
	$smarty->assign("aitts", $aitts);
	@mysqli_free_result($resulttts);

	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("ai_Manager/powertimeqi.html");
}
?>