<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');


if(empty($_SESSION['admin_id']))
{
	header("location:login.php");	
}
else
{		
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("terminal_function_add",$terminal_function_add);

	$smarty->assign("Belladdtask",$Belladdtask);
	
	/*动态显示页面信息*/
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
	$type=get_terminal_type(11,$do_php_prompt['Terminal_not_support'],0,0);
	$terminalist = create_tree_str($type);	
	//$terminalist = create_tree_str("1,5,4,11,13,14,15");
	
	$smarty->assign("terminalist",$terminalist);

	$smarty->assign("admin_id",$_SESSION['admin_id']);

	$smarty->display("centercontrol/centerctrladd.html");
}	
?>
