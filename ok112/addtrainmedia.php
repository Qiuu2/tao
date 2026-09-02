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
	
#endif
	//$type =  "(1,3,4)";	
	
	//$terminalist = get_terminallist($type, 0);
	
	//$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	//$terminalist = get_grouped_terminal($type);
	
	//$terminalist = create_tree_str($type);

	/*$terminalist_tmp = xml_str_analyze($terminalist);
	
	if(empty($terminalist_tmp))
	{
		echo "<script>alert('".$add_bell_scheme['not_add_type_terminal']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
	}*/
	//$smarty->assign("terminalist",$terminalist);

	//获取打铃文件夹中文件
//	$user_sql = "SELECT media.id, media.name FROM media WHERE media.folderid= ";
		
		//$user_sql.= "(SELECT parentid FROM filefolder WHERE filefolder.parentid = 2) ";
		
		//$result = mysqli_query($con,$user_sql);
	$mediaresult = mysqli_query($con,"SELECT id,name from media where folderid=6 AND filename!='tts' ");

	if(mysqli_fetch_array($mediaresult))
	{
		@mysqli_data_seek($mediaresult, 0);

		while($row = mysqli_fetch_array($mediaresult))
		{
			$medianame[] = array("id"=>$row['id'],"name"=>$row['name']);
		}

		$smarty->assign("medianame",$medianame);

		unset($medianame);

		@mysqli_free_result($mediaresult);
	}
	
	$result = mysqli_query($con,"SELECT seq,name from ttstext ");

	if(mysqli_fetch_array($result))
	{
		@mysqli_data_seek($result, 0);

		while($row = mysqli_fetch_array($result))
		{
			$medialist[] = array("seq"=>$row['seq'],"name"=>$row['name']);
		}

		$smarty->assign("medialist",$medialist);

		unset($medialist);

		@mysqli_free_result($result);
	}
	

	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("stopmanager/addtrainmedia.html");
}
?>

