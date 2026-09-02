<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if( empty($_SESSION['admin_id']) )
{
	header('Location:login.php');
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	
	$smarty->assign("language",$_SESSION['language']);
	
	$smarty->assign("add_bell_scheme",$add_bell_scheme);

	$smarty->assign("Belladdtask",$Belladdtask);

#if 0
	$str = "<?xml version='1.0' encoding='UTF-8'?> <tree id=\"0\">";
	
	$resultstream=	mysqli_query($con,"SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream");
	while ($rowstream = mysqli_fetch_array($resultstream))
	{			
		$streamid = $rowstream['streamid'];
		$str = "<item text=\"".$rowstream['name']."\" id=\"stream_".$streamid."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >";
	
		$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname,terminal.typeid FROM terminal WHERE terminal.groupid=$streamid");
		while ($rowterminal = mysqli_fetch_array($resultterminal)) 
		{	
			$str = "<item  type=\"".$rowterminal['typeid']."\" text=\"".$rowterminal['terminalname']."\" id=\""."$rowterminal[id]"."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" > \n        </item>\n"	;
		  
		}							 
	
	}		

#endif
	//$type =  "(1,3,4)";	
	
	//$terminalist = get_terminallist($type, 0);
	
	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	//$terminalist = get_grouped_terminal($type);
	
	$terminalist = create_tree_str($type);

	/*$terminalist_tmp = xml_str_analyze($terminalist);
	
	if(empty($terminalist_tmp))
	{
		echo "<script>alert('".$add_bell_scheme['not_add_type_terminal']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
	}*/
	$smarty->assign("terminalist",$terminalist);

	//获取打铃文件夹中文件
//	$user_sql = "SELECT media.id, media.name FROM media WHERE media.folderid= ";
		
		//$user_sql.= "(SELECT parentid FROM filefolder WHERE filefolder.parentid = 2) ";
		
		//$result = mysqli_query($con,$user_sql);
	
	
	$result = mysqli_query($con,"SELECT trainid,taskname,taskdemo,tasktime from traindemos ");

	if(mysqli_fetch_array($result))
	{
		@mysqli_data_seek($result, 0);

		while($row = mysqli_fetch_array($result))
		{
			$medialist[] = array("id"=>$row['trainid'],"name"=>$row['taskname'],"taskdemo"=>$row['taskdemo'],"tasktime"=>$row['tasktime']);
		}

		$smarty->assign("medialist",$medialist);

		unset($medialist);

		@mysqli_free_result($result);
	}

	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("stopmanager/addstopmanager.html");
}
?>

