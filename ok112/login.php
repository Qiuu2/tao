<?php
if (!session_id()) session_start();
//验证是否失效
require_once("verify_user_sessionin_valid.php");

require_once('inc/config.inc.php');
require_once('inc/config.php');
verifysessionvalid();

mysqli_query($con,"LOCK TABLE serverbaseparam WRITE");//锁定此表
	$getresult=mysqli_query($con,"SELECT registerflag FROM serverbaseparam") or die(mysqli_error($con));
	if($row=mysqli_fetch_array($getresult))
	{
		if($row['registerflag']==0)
		{
			//修改此路径---当已登陆后再进入直接进入该页面，若进入./ 默认则进入index.html---出现重页
			echo "<script>window.location='./regist_server.php'</script>";
		}
		
	}
	mysqli_query($con,"UNLOCK TABLES");

$_SESSION['webport']=0;
if(empty($_SESSION['admin_id']))
{
	require_once('inc/smarty.inc.php');
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	
	$smarty->assign("user_login",$user_login);

	$smarty->assign("Login_User",$Login_User);
	$smarty->assign("FUZA_PASS",$FUZA_PASS);
	$smarty->assign("Login_Password",$Login_Password);
	
	$smarty->assign("Login_Verification_number",$Login_Verification_number);
	
	$smarty->assign("Login_Submit",$Login_Submit);
	
	$smarty->assign("Login_Cancel",$Login_Cancel);
	
	$smarty->assign("Login_fill_in_user",$Login_fill_in_user);
	
	$smarty->assign("Login_fill_in_password",$Login_fill_in_password);
	
	$smarty->assign("Login_fill_in_validation_code",$Login_fill_in_validation_code);

	$serial_sql = "SELECT tryenddate FROM audioserver.serverbaseparam LIMIT 0,1";
	
	$serial_result = mysqli_query($con,$serial_sql) or die(mysqli_connect_error());
	
	if($serial_row = mysqli_fetch_array($serial_result))
	{
		$date_info = trim($serial_row['tryenddate']);
	}

	if($date_info=='0000-00-00')
	{
	$Days=0;
	}
	else
	{
	$datetimeinfo = explode("-",$date_info);
	$month=date("m");
	$year=date("Y");
	$day=date("d");
	$date1=mktime(0,0,0,$datetimeinfo[1],$datetimeinfo[2],$datetimeinfo[0]);
	$date2=mktime(0,0,0,$month,$day,$year);		
	$Days=round(($date1-$date2)/3600/24); 
	}
	$smarty->assign("Days",$Days);
	//@mysqli_free_result($serial_result);
	unset($serial_sql,$serial_row);

	$smarty->display("login.html");

}
else
{
	//修改此路径---当已登陆后再进入直接进入该页面，若进入./ 默认则进入index.html---出现重页
	echo "<script>window.location='./servermanager.php'</script>";
}
?>