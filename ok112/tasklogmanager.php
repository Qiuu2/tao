<?php
/**************************************
	?????
	1???
	2?????
***************************************/
if (!session_id()) session_start();
header("content-type:text/html; charset=utf-8");
require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');
//???
 $Task_Log =  "datelog/";

if(empty($_SESSION['admin_id']))
{
	header('location:login.php');
}
else
{
/*?*/
require_once("language/".$_SESSION['language'].".php");
$smarty->assign("language",$_SESSION['language']);
$smarty->assign("backup_restore",$backup_restore);
$smarty->assign("get_time",date("Y-m-d H:i:s"));

$smarty->assign("get_ipaddr",$_SERVER['SERVER_NAME']);

function count_size($filesize)
{
	$KB = 1024;
	$B=1024; 
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

function judge_suffixs($file_name)
{
	
	return substr($file_name,strrpos($file_name,".")+1);
}

function trans_times($timestamp)
{
	return date("Y-m-d H:i:s",$timestamp);
}
//??

$get_filesum=0;
//???
	$termianl_sql = "SELECT port FROM serverbaseparam  ";
	
	$termianl_result = mysqli_query($con,$termianl_sql) or die(mysqli_error($con));
	
	if($termianl_row = mysqli_fetch_array($termianl_result))
	{
		$smarty->assign("port",$termianl_row['port']);
	}
	
	
	
//	@mysqli_free_result($termianl_result);
	
	unset($termianl_sql,$termianl_row);
	}
	
$backup_files = array();

if(is_dir($Task_Log))
{
	if($folder_handle = opendir($Task_Log))
	{

		while( ($file = readdir($folder_handle)) !== false)
		{
			if($file != "." && $file != "..")
			{
				if(is_file($Task_Log."/".$file))
				{
			
					if(judge_suffixs($file) == "html")
					{
					
						$get_filesum++;
						$filename = basename($file,".html");
						$filesize = count_size(filesize($Task_Log."/".$file));
						$filetime = filectime($Task_Log."/".$file);
						$filetime = trans_times($filetime);
					//	$filetype = pathinfo($trans_time."/".$file);
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

sort($backup_files);
$smarty->assign("backup_files",$backup_files);
unset($backup_files);
$pagesize = 10;
$page = $_GET['page'];
if($page < 1)
  $page = 1;
$smarty->assign("pages",$get_filesum);
$smarty->assign("page",$page);
$smarty->assign("pagesize",$pagesize);
$smarty->assign("pagesize",$pagesize);
$smarty->assign("admin_id",$_SESSION['admin_id']);
$smarty->display("LogManager/tasklogmanager.html");


?>