<?php
if (!session_id()) session_start();
header("content-type:text/html;charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();
	
if(empty($_SESSION['admin_id']))
{
	header("location:login.php");
}
else
{
	$getflag = 0;//ĬϹ
	if(isset($_GET['id']))
	{
		$getflag = trim($_GET['id']);
		$_SESSION['tran_task_value'] = $getflag;
	}
	else
	{
		if(empty($_SESSION['tran_task_value']))
		{
			$_SESSION['tran_task_value']=1;
		}
		
		$getflag = $_SESSION['tran_task_value'];
	}

	//现在多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("task_manager",$task_manager);
	$smarty->assign("Filetaskmanager",$Filetaskmanager);
	//获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("user_rights",1);
	}
	else
	{
		$smarty->assign("user_rights",0);
	}
	$userid=$_SESSION['userid'];

	if($getflag==1)
	{
		if($_SESSION['admin_id']!="administrator")
		{
		$sql="SELECT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,defaultvolume,task_user_id,offlinestate,info FROM task WHERE tasktype IN (1,2,7) AND offlinestate=0  AND task_user_id = $userid ORDER BY playtime desc";
		}
		else
		{
		$sql="SELECT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,defaultvolume,task_user_id,offlinestate,info FROM task WHERE tasktype IN (1,2,7) AND offlinestate=0 ORDER BY playtime desc";
		}
		
	$info2=array();
	$result_terminal = mysqli_query($con,$sql);

	while($row = mysqli_fetch_array($result_terminal)) 
	{	
		$info2[]=array("taskid"=>$row['taskid'],"taskname"=>$row['taskname'],"israndomplay"=>$row['israndomplay'],"projectstate"=>$row['projectstate'],"state"=>$row['state'],"startdate"=>$row['startdate'],"enddate"=>$row['enddate'], "playtime"=>$row['playtime'], "timelength"=>$row['timelength'],"exemodel"=>$row['exemodel'],"priority"=>$row['priority'],"tasktype"=>$row['tasktype'],"timelengthtype"=>$row['timelengthtype'],"playfileid"=>$row['playfileid'],"defaultvolume"=>$row['defaultvolume'],"task_user_id"=>$row['task_user_id'],"offlinestate"=>$row['offlinestate'],"info"=>$row['info']);
	}
		
	$smarty->assign("info2",$info2);
	mysqli_free_result($result_terminal);
	unset($row,$sql_terminal);
	//用户与终端绑定
	}

	if($getflag==2)
	{
		if($_SESSION['admin_id']!="administrator")
		{
		$sql="SELECT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,defaultvolume,task_user_id,offlinestate,info FROM offlinetask WHERE tasktype IN (1,2,7)  AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') ORDER BY playtime desc";
		}
		else
		{
		$sql="SELECT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,defaultvolume,task_user_id,offlinestate,info FROM offlinetask WHERE tasktype IN (1,2,7) ORDER BY playtime desc";
		}
	$result_terminal = mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
	$info2=array();
	while($row = mysqli_fetch_array($result_terminal)) 
	{	
		$gettaskid=$row['taskid'];
		$offlinestate=$row['offlinestate'];
		if($offlinestate!=3)
		{
			$getsql="SELECT COUNT(DISTINCT offlinestate),offlinestate FROM offlinemediaofterminal WHERE taskid=$gettaskid";
			$results = mysqli_query($con,$getsql) or die("Execute error".mysqli_error($con));
			while($rows = mysqli_fetch_array($results))
			{
				if($rows[0]==1&&$rows[1]==3)
				{
					mysqli_query($con,"UPDATE offlinetask SET offlinestate = '3' WHERE taskid='$gettaskid'") or die(mysqli_error($con));	
					$offlinestate=3;
				}
			}
		}
		
	$info2[]=array("taskid"=>$row['taskid'],"taskname"=>$row['taskname'],"israndomplay"=>$row['israndomplay'],"projectstate"=>$row['projectstate'],"state"=>$row['state'],"startdate"=>$row['startdate'],"enddate"=>$row['enddate'], "playtime"=>$row['playtime'], "timelength"=>$row['timelength'],"exemodel"=>$row['exemodel'],"priority"=>$row['priority'],"tasktype"=>$row['tasktype'],"timelengthtype"=>$row['timelengthtype'],"playfileid"=>$row['playfileid'],"defaultvolume"=>$row['defaultvolume'],"task_user_id"=>$row['task_user_id'],"offlinestate"=>$offlinestate,"info"=>$row['info']);
	
	}

	$smarty->assign("info2",$info2);
	mysqli_free_result($result_terminal);
	unset($row,$sql_terminal);
	}
	
	$smarty->assign("getflag",$getflag);
	$smarty->assign("chinese_big5_english",$_SESSION['language']);
	

	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("offlinetask/offlinetask.html");
}
?>