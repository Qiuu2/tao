<?php
if (!session_id()) session_start();
header("content-type:text/html;charset=utf-8");
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
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("set_shotcut",$set_shotcut);
	$smarty->assign("Setterminalkey",$Setterminalkey);
	
	//获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(have_rights("terminalpriv") || is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}

	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
		$_SESSION['tran_mid_value'] = $terminal_id;
	}
	
	if($terminal_id == "")
	{
		$terminal_id = $_SESSION['tran_mid_value'];
	}
	
	
	function get_uppatefile($typeid)
	{
		$updatepath = "link/backup/upgradefile";
		if(is_dir($updatepath))
		{
			if( $handle = opendir($updatepath))
			{
				while( ($file = readdir($handle)) != false)
				{
					if($file != "." && $file != "..")
					{
						if(is_file($updatepath."/".$file))
						{
							$path_file=$updatepath."/".$file;
							$arr=explode('.', $file);
							$houzui=end($arr);
							if($typeid==17)
							{
								if($houzui=='apk')
								{
									$shotcut_info[] = array("pathfile"=>$path_file,"filename"=>$file);
								}
							}
						}
					}
				}
			}
		}
		return $shotcut_info;
	}


	//取源终端名称
	$sql = "SELECT typeid FROM terminal WHERE id= '$terminal_id'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	if($row = mysqli_fetch_array($result))
	{
		$shotcut_info=get_uppatefile($row['typeid']);
		$smarty->assign("terminal_info",$shotcut_info);
	}

	$smarty->assign("start",$start);
	$smarty->assign("id",$terminal_id);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("TerminalManager/shenji.html");
}
?>