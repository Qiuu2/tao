<?php
{
	//===============启用会话
  
	//if (!session_id()) session_start();
	//===============避免显示乱码
	header('Content-Type:text/html;charset=utf-8');
	require_once("inc/config.inc.php");
  require_once('inc/config.php');
  $store_dir = "upload/";// 上传文件的储存位置  
	//===============避免数据库乱码
	mysqli_query($con,"set names utf8");
	//===============验证是否失效
  //require_once("verify_user_sessionin_valid.php");	
	//verifysessionvalid();
	//===============显示多语言
  //	require_once("language/".$_SESSION['language'].".php");
	//===================================================================添加封装模块
	//require_once($_SERVER["DOCUMENT_ROOT"]."/features_wrapper_class.php");
	//===============判断是否登录或退出标志段默认为0
	$olduser = "";
	$opt = "invalid user"; 
	
	//添加外部变量
  //	global $do_php_prompt;
	
	//==================================================导入跳转类
	//$forward_ok_error_obj = new forward_ok_error_class();
	
	$userid ="";	
	$id =""	;
	if(isset($_POST['id']))
	{
		$id = trim($_POST['id']);
	}
	if($id >0)
	{
		$folder_id = 3;			
		mysqli_query($con,"DELETE FROM `media` WHERE id='$id'") or die("Execute error".mysqli_error($con));
	//	insert_log($con);
  }
 }
 
 
 
//插入日志
function insert_log($con)
{
	
	$opt = "androiddel";	
	$ip = $_SERVER['REMOTE_ADDR'];	
	$user =  trim($_POST['usr']);;
	
	$time = gmdate("Y-m-d H:i:s",time()+8*3600);
	
	$log_sql = "INSERT INTO audioserver.log (log.user, log.operate, log.ip, log.time)";
	
	$log_sql.= " VALUES ('$user','$opt','$ip','$time') ";
//===========================================================添加锁	

	mysqli_query($con,$log_sql) or die(mysqli_error($con));
}

//===========================================================解除锁

?>
