<?php
if (!session_id()) session_start();

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');


	
	if(isset($_GET['id']))
	{
		$folder_id = trim($_GET['id']);
	}

	//显示多语言
	
	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("create_folder",$create_folder);
	
	$smarty->assign("Filefolderadd",$Filefolderadd);
	/*动态显示页面文本*/
	switch($_GET['sign'])
	{
		case "add":		
			$smarty->assign("id",$folder_id);
			$smarty->assign("userid",$userid);
		
			$smarty->display("FileAd/filefolderadd.html");
		break;
		case "modify":
			$sql_folder = "SELECT name FROM filetaskfree WHERE filetaskfree.id = '$folder_id' ";
	
			$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
	
			if($row_folder = mysqli_fetch_array($result_folder))
			{
					$folder_info = array("name"=>$row_folder['name']);
			}
			$smarty->assign("folder_info",$folder_info);
			
			$smarty->assign("chinese_big5_english",$_SESSION['language']);
			$smarty->assign("id",$folder_id);
			$smarty->display("FileAd/filefoldermodify.html");
		break;
		case "ledadd":		
			$smarty->assign("id",$folder_id);
			$smarty->assign("userid",$userid);
			$smarty->display("ledmanager/filefolderadd.html");
		break;
		case "ledmodify":
			$sql_folder = "SELECT name FROM ledtaskfree WHERE ledtaskfree.id = '$folder_id' ";
			$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
			if($row_folder = mysqli_fetch_array($result_folder))
			{
					$folder_info = array("name"=>$row_folder['name']);
			}
			$smarty->assign("folder_info",$folder_info);
			$smarty->assign("chinese_big5_english",$_SESSION['language']);
			$smarty->assign("id",$folder_id);
			$smarty->display("ledmanager/filefoldermodify.html");
		break;
		case "ai_add":		
			$smarty->assign("id",$folder_id);
			$smarty->assign("userid",$userid);
			$smarty->display("ai_Manager/aifolderadd.html");
		break;
		case "ai_modify":
			$sql_folder = "SELECT shibiedeviceid,deviceaddr FROM ai_devicedemo WHERE ai_devicedemo.shibiedeviceid = '$folder_id' ";
			$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
			if($row_folder = mysqli_fetch_array($result_folder))
			{
					$folder_info = array("deviceaddr"=>$row_folder['deviceaddr']);
			}
			$smarty->assign("folder_info",$folder_info);
			$smarty->assign("chinese_big5_english",$_SESSION['language']);
			$smarty->assign("id",$folder_id);
			$smarty->display("ai_Manager/aifoldermodify.html");
		break;
	}	


?>
