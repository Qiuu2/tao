<?php
/**************************************
	读取服务器端备份文件夹中的备份文件
	1、只能是超级管理员操作
	2、显示选项、文件名、文件大小、备份时间
***************************************/
if (!session_id()) session_start();
header("content-type:text/html; charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require_once('inc/config.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	header('location:login.php');
}
//本地化时间
//date_default_timezone_set('Asia/Shanghai');

/*显示多语言*/
require_once("language/".$_SESSION['language'].".php");
$smarty->assign("language",$_SESSION['language']);
$smarty->assign("backup_restore",$backup_restore);

//获取权限
require_once("User_Rights_Manage/verify_user_rights_class.php");
if(is_admin($con,$_SESSION['username']))
{
	$smarty->assign("is_right",1);
}
else
{
	$smarty->assign("is_right",0);
}

//读取备份文件夹下的文件
$backup_files = array();

if(is_dir($Backup_Path))
{
	if($folder_handle = opendir($Backup_Path))
	{
		while( ($file = readdir($folder_handle)) !== false)
		{
			if($file != "." && $file != "..")
			{
				if(is_file($Backup_Path."/".$file))
				{
					if(judges_suffix($file) == "tar"&&$file!="mediabackup.tar")
					{
						$filename = basename($file,".tar");
						$filesize = count_size(filesize($Backup_Path.$file));
						$rootpath=$Backup_Path.$file;
						
						$filetime = filemtime("$rootpath");
						
						$filetime = transs_time($filetime);
					
						$filetype = pathinfo($Backup_Path."/".$file);
						$filetype = $filetype['extension'];
						$backup_files[] = array("filename"=>$filename,"filesize"=>$filesize,"filetime"=>$filetime,"filetype"=>$filetype);
					}
				}
			}
		}
	}
	else
	{
		$smarty->assign("backup_files",$backup_files = array());
	}
	if(empty($backup_files))
	{
		$smarty->assign("backup_files",$backup_files = array());
	}
}
else
{
	$smarty->assign("backup_files",$backup_files = array());
}

$smarty->assign("backup_files",$backup_files);
unset($backup_files);

$smarty->assign("admin_id",$_SESSION['admin_id']);
$smarty->display("backup_restore/backup_restore.html");

function count_size($filesize)
{
	$KB = 1024;
	$MB = $KB*1024;
	if($filesize > $MB)
	{
		return round($filesize/$MB,2)."MB";
	}
	else if($filesize > $KB)
	{
		return round($filesize/$KB,2)."KB";
	}
	else
	{
		return round($filesize/$B,2)."B";
	}
}
function judges_suffix($file_name)
{
	return substr($file_name,strrpos($file_name,".")+1);
}
function transs_time($timestamp)
{
	return date("Y-m-d H:i:s",$timestamp);
}
?>