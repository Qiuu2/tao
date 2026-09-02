<?php

if (!session_id()) session_start();

//header("content-type:text/html; charset=utf-8");

function verifysessionvalid()
{
	if(empty($_SESSION))//判断会话失效
	{ 
		@setcookie(session_name(),'',time()-3600);//删除cookie重新生产会话文件
		@session_unset();
		@session_destroy();//有可能会话文件不存在
		//删除cookie
		
	

		exit;
	}
	if(empty($_SESSION['language']))
	{
		$_SESSION['language'] = "chinese";
		echo "<script>top.location.reload();</script>";
	}
}
?>
