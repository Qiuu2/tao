<?php
if (!session_id()) session_start();

if(is_file('install.php')) 
{
	header("Location: install.php"); 

	exit;
}
require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

if(empty($_SESSION['admin_id']))
{
	//require_once('login.php');

	header("location:login.php");	
}
else
{
	require_once("verify_user_sessionin_valid.php");

	verifysessionvalid();
	
	$folder_id = 0;
	
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
		$userid=$_SESSION['userid'];
	
		$result	=	mysqli_query($con,"SELECT id,name FROM `filefolder` where userid=$userid or userid=0");
		while ($row = mysqli_fetch_array($result)) 
		{			
			$array[]=array("id"=>$row['id'],"name"=>$row['name']);
		}
		$smarty->assign("folder",$array);
		
	$smarty->assign("id",$folder_id);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("FolderManger/filefolderadd.html");
}
?>
