<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

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
	
	$smarty->assign("tel_collect_task_add",$tel_collect_task_add);

	$smarty->assign("Belladdtask",$Belladdtask);
#if 0
	$str = "<?xml version='1.0' encoding='UTF-8'?> <tree id=\"0\">";

	
	$resultstream=	mysqli_query($con,"SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream");
	while ($rowstream = mysqli_fetch_array($resultstream))
	{			
		$streamid = $rowstream['streamid'];
		$str = "<item text=\"".$rowstream['name']."\" id=\"stream_".$streamid."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >";

		$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.groupid=$streamid");
		while ($rowterminal = mysqli_fetch_array($resultterminal)) 
		{	
			$str = "<item text=\"".$rowterminal['terminalname']."\" id=\""."$rowterminal[id]"."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" > \n</item>\n"	;
					  
		}							 
				
	}		
		
#endif

	//$type =  "(1,3,4)";	
	
	//$terminalist = get_terminallist($type, 0);
	
	$type = "1,3,4,5,11,13,14,15,6";
	
	//$terminalist = get_grouped_terminal($type);
	
	$terminalist = create_tree_str($type);
	
	/*$terminalist_tmp = xml_str_analyze($terminalist);
	
	if(empty($terminalist_tmp))
	{
		echo "<script>alert('".$collect_task_add['no_collection_terminal_add']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
	}*/
	
	$smarty->assign("terminalist",$terminalist);
	
	$audiosourcelist = get_audiosource();
	
	$smarty->assign("audiosourcelist",$audiosourcelist);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("TelBroadManager/addtelBroadManager.html");
}
?>
