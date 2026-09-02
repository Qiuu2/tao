<?php
/******************************
每次登录验证-》用户名+密码+会话id
	
	1、获取用户会话id
	2、获取数据库用户信息
	3、比较
******************************/
//获取服务器端所有会话id
function getserverallsessionid($sessionpath)
{
	$filename = array() ;
	$sessionidarray = array();
	if($handle = opendir($sessionpath)) 
	{
		while (false !== ($file = readdir($handle))) 
		{
			if ($file != "." && $file != "..") 
			{
					$filename[]=$file;
			}
		}
		closedir($handle);
		//处理数组 获取所有会话id
		foreach($filename as $sessionid)
		{
			$sessionidarray[] = substr($sessionid,5);
		}
		
		return $sessionidarray;
	}
}

function search_sessionid($sessionpath,$username,$newsessionid)
{
	require_once("inc/config.inc.php");
	$get_allsessionid = getserverallsessionid($sessionpath);
	//标志段  0 表示都不相等 1表示有相等
	$flag = 0;
	
	mysqli_query($con,"LOCK TABLE book_admin WRITE");
	
	//获取用户最近数据库中会话id
	$sql = "SELECT usersessionid FROM book_admin WHERE book_admin.username = '$username'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	//表示该用户数据库中保存了会话
	if($row = mysqli_fetch_array($result))
	{	
		$oldsessionid = $row['usersessionid'];
		//比较数据库中会话是否有效
		foreach($get_allsessionid as $sessionid)
		{
			if(trim($sessionid) == trim($oldsessionid))
			{	//表示有相同会话id
				$flag = 1;
				echo "<script>alert('该用户已经登录...');</script>";
				echo "<script>window.history.back();</script>";
				exit;
			}
		}
		if($flag == 0)
		{
			//表示没有相同会话
			$sqlupdatesessionid =  "UPDATE book_admin SET usersessionid = '$newsessionid' WHERE book_admin.username = '$username'";
			mysqli_query($con,$sqlupdatesessionid) or die(mysqli_error($con)) ;
			unset($sqlupdatesessionid);
		}
	}
	else
	{	//表示该用户数据库中没有会话
		$sqlsessionid = "UPDATE book_admin SET usersessionid = '$newsessionid' WHERE book_admin.username = '$username'";
		mysqli_query($con,$sqlsessionid) or die(mysqli_error($con));
		unset($sqlsessionid);
	}
}

//判断登录用户是否存在
function judgeloginusereffective($username,$userpassword)
{		
	require_once("inc/config.inc.php");
	$sql = "SELECT 	* FROM book_admin WHERE book_admin.username = '$username' AND book_admin.userpwd = '$userpassword'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result) == 1)
	{
		@mysqli_free_result($result);
		unset($sql);
		return true;
	}
	else if(mysqli_num_rows($result) <= 0)
	{
		@mysqli_free_result($result);
		unset($sql);
		return false;
	}
}
//判断用户及会话id 有则 直接登录 无则 更新用户会话id（条件是：用户登录（用户名+密码 = 是有效的））
function judgeusersessionid($username,$userpassword,$userseesionid)
{
	require_once("inc/config.inc.php");
	mysqli_query($con,"LOCK TABLES book_admin WRITE");
	//获取数据库会话id
	$getusersessionid = "";
	//判断用户是否存在
	if(judgeloginusereffective($username,$userpassword))
	{
		$sql = "SELECT usersessionid FROM book_admin WHERE book_admin.username = '".$username."' AND book_admin.userpwd = '".$userpassword."'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if($row = mysqli_fetch_array($result))
		{
			$getusersessionid = trim($row['usersessionid']);
		}
		@mysqli_free_result($result);
		unset($row,$sql);
		//对用户会话id进行比较
		if(empty($getusersessionid))
		{
			//用户会话id为空表示当前没有登录、插入信息
			$sql = "UPDATE book_admin SET usersessionid = '$userseesionid' WHERE book_admin.username = '".$username."' AND book_admin.userpwd = '".$userpassword."'";
			mysqli_query($con,$sql) or die(mysqli_error($con));	
			unset($sql);
		}
		else if(!empty($getusersessionid))
		{
			//用户会话id不为空表示有效、需比较是否是同一个用户、不是则退出、是继续登录
			if($getusersessionid == $userseesionid)
			{
				//表示同一个用户、继续登录 不做任何处理
			}
			else if($getusersessionid != $userseesionid)
			{
				//表示不是同一用户、退出
				
				echo "<script>alert('该用户正在登录中...');</script>";
				echo "<script>window.history.back();</script>";
				exit;
			}
		}
	}
	mysqli_query($con,"UNLOCK TABLES");
}
//检测数据库中会话id是否过期、过期 则清空 不过期则保留
function refreshusersessionid($sessionpath)
{
	require_once("inc/config.inc.php");
	
	mysqli_query($con,"LOCK TABLES book_admin WRITE");
	
	$recordsessionid = "";//记录数据库中有效会话
	//读取数据中会话id
	$sql = "SELECT usersessionid FROM book_admin where book_admin.usersessionid != ''";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	if(mysqli_num_rows($result) > 0)
	{
		while($row = mysqli_fetch_array($result))
		{
			$getdatebasesessionid[] = $row['usersessionid'];
		}
	}
	else if(mysqli_num_rows($result) <= 0)
	{
		$getdatebasesessionid = NULL;
	}
	@mysqli_free_result($result);
	
	unset($row,$sql);
	//获取服务器中所有会话id
	$getallsessionarray = getserverallsessionid($sessionpath);//只要有用户登录就有会话id	
	//如果数据库会话不为空 则比较后更新
	if(!empty($getdatebasesessionid))
	{
		foreach($getallsessionarray as $sessionid)
		{
			foreach($getdatebasesessionid as $datesessionid)
			{
				if(trim($sessionid) == trim($datesessionid))
				{
					if(empty($recordsessionid))
					{
						$recordsessionid = "'".$datesessionid."'";
					}
					else if(!empty($recordsessionid))
					{
						$recordsessionid .=",'".$datesessionid."'";
					}
				}
			}
		}
		//不为空则清空部分用户会话id
		if(!empty($recordsessionid))
		{
			$sql = "UPDATE book_admin SET usersessionid='' WHERE book_admin.usersessionid NOT IN (".$recordsessionid.")";
						
			mysqli_query($con,$sql) or die(mysqli_error($con));
			unset($sql);
		}
		else if(empty($recordsessionid))
		{	//为空则清空所有用户会话id
			$sql = "UPDATE book_admin SET usersessionid = '' ";			
			mysqli_query($con,$sql) or die(mysqli_error($con));
			unset($sql);
		}
	}
	unset($recordsessionid,$getallsessionarray,$getdatebasesessionid);
	mysqli_query($con,"UNLOCK TABLES");
}
//当用户注销（未关闭页面） 清除数据库中会话id
function clearDBsessionid($username)
{
	require_once("inc/config.inc.php");
	
	mysqli_query($con,"LOCK TABLE book_admin WRITE");
	
	$sql = "UPDATE book_admin SET usersessionid = '' WHERE book_admin.username = '$username' ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	mysqli_query($con,"UNLOCK TABLES");
}
?> 