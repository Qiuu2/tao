<?php
if (!session_id()) session_start();
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();
	
if(empty($_SESSION['admin_id']))
{
	require_once('login.php');	
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("phone_collect",$phone_collect);

	$smarty->assign("Admmanager",$Admmanager);
	$smarty->assign("Searchform",$Searchform);
	$smarty->assign("Revise",$Revise);
	//验证权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(have_rights("telephonepriv") || is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	
	require('editor.php');
	$smarty->assign('descriptionarea',$descriptionarea);
	$mediaArray="";//保存媒体文件列表
	$termianlArray="";//保存终端列表
	$result=0;//获取task结果集
	if(!isset($_GET['page']))
	{
	    $page=1;
	    $start=0;
	}else {
	    $page=$_GET['page'];
	    $start=($_GET['page']-1)*$NumOfPage;
	}
	//添加被查询变量
	$userid=$_SESSION['userid'];
	$sql="";
	if($_GET['searchvalue']!="")
	{
		if($_GET['searchkey']!="")
		{
			if($_GET['searchkey']=="taskname")
			{
				if($_GET['searchsequence']!="")
				{
					$sql="SELECT * FROM task WHERE tasktype=4 and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY '$_GET[searchsequence]' desc";
				}
				else
				{
					$sql="SELECT * FROM task WHERE tasktype=4 and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY taskid desc";
				}
			}
			
			if($_GET['searchkey']=="playtime")
			{
				if($_GET['searchsequence']!="")
				{
					$sql="SELECT * FROM task WHERE tasktype=4 and  playtime >= '".trim($_GET['searchvalue'])."' ORDER BY taskid";
				}
				else
				{
					$sql="SELECT * FROM task WHERE tasktype=4 and playtime >= '".trim($_GET['searchvalue'])."' ORDER BY taskid desc";
				}
			}
		}	
		else
		{	
			$sql="SELECT * FROM task WHERE tasktype=4 and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY taskid desc";
		}
	}
	else
	{
	   if($_SESSION['username']=="admin")
		$sql="SELECT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,channel,defaultvolume,cmdargs,bandrate,samplerate,task_user_id,book_admin.username FROM task,book_admin WHERE task_user_id=book_admin.id AND tasktype ='4' ORDER BY playtime DESC";
		else
		$sql="SELECT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,channel,defaultvolume,cmdargs,bandrate,samplerate,task_user_id,book_admin.username FROM task,book_admin WHERE task_user_id=book_admin.id AND tasktype ='4' AND task_user_id='$userid' ORDER BY taskid desc ";
	}
	
	
	if(!isset($_SESSION['admin_id']))
	{
		$result	=	mysqli_query($con,$sql);
		$Num	=	mysqli_num_rows($result);
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
//		$result	=	mysqli_query($con,"SELECT * FROM `task` where tasktype=4");
//		$result = mysqli_query($con,"SELECT * FROM `task` where tasktype=4 ORDER BY taskid DESC LIMIT $start,$NumOfPage");
	}
	else
	{
		$result	=	mysqli_query($con,$sql);
		$Num	=	mysqli_num_rows($result);
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
//		$result	=	mysqli_query($con,"SELECT * FROM `task` where tasktype=4");
//		$result = mysqli_query($con,"SELECT * FROM `task` where tasktype =4 ORDER BY taskid DESC LIMIT $start,$NumOfPage");
	}
	
	while ($row = mysqli_fetch_array($result)) 
	{
		$taskid = $row['taskid'];
		$mediaSql="SELECT DISTINCT mediaoftask.taskid,media.id,media.name,media.filename FROM media,mediaoftask WHERE media.id=mediaoftask.mediaid AND mediaoftask.taskid = $taskid";
		$mediaResult=mysqli_query($con,$mediaSql);
		while($row0=mysqli_fetch_array($mediaResult))
		{
		$mediaArray[]=array("mediaid"=>$row0['id'],"medianame"=>$row0['name'],"mediafilename"=>$row0['filename']);
		}
		$terminalSql="SELECT DISTINCT terminaloftask.taskid,terminal.id,terminal.terminalname,terminaltype.name,terminal.netstate,terminal.devicestate,terminal.taskstate,terminal.ip,terminal.postion,terminal.volume FROM terminal,terminaloftask,terminaltype WHERE terminal.typeid=terminaltype.id AND terminal.id=terminaloftask.terminalid AND terminaloftask.taskid =$taskid";
		$terminalResult=mysqli_query($con,$terminalSql);
		while($row1=mysqli_fetch_array($terminalResult))
		{
		$termianlArray[]=array("terminalid"=>$row1['id'],"terminalname"=>$row1['terminalname'],"terminaltypename"=>$row1['name'],"netstate"=>$row1['netstate'],"taskstate"=>$row1['taskstate'],"terminalip"=>$row1['ip'],"postion"=>$row1['$postion'],"volume"=>$row1['volume']);
		}
	$array[]=array(
					"taskid"=>$row['taskid'],"taskname"=>$row['taskname'],"israndomplay"=>$row['israndomplay'],
					"onlyopenpower"=>$row['onlyopenpower'],"prepower"=>$row['prepower'],"datasendmodel"=>$row['datasendmodel'],
					"priority"=>($row['priority']%10),"cmdargs"=>$row['cmdargs'],"channel"=>$row['channel'],
					"bandrate"=>$row['bandrate'],"samplerate"=>$row['samplerate'],"state"=>$row['state'],
					"startdate"=>$row['startdate'],"enddate"=>$row['enddate'], "playtime"=>$row['playtime'],
					"playmodel"=>$row['playmodel'],"timelength"=>$row['timelength'],"exemodel"=>$row['exemodel'],
					"timelengthtype"=>$row['timelengthtype'],"defaultvolume"=>$row['defaultvolume'],"username"=>$row['username'],
					"mediaArray"=>$mediaArray,"termianlArray"=>$termianlArray
					);

	unset($mediaArray);
	unset($termianlArray);
	}

	$smarty->assign("info",$array);
	unset($array);
//	////////////////////////////////////////////////////////////////////////////////////////////////////////////获取用户的权限和优先级2010/6/3
//	if($_SESSION['admin_id']!="administrator")
//	{	
//		$sql="SELECT usergroup.name,usergroup.mediapriv,usergroup.taskpriv,usergroup.terminalpriv,usergroup.userpriv,usergroup.level ";
//		$sql.="FROM usergroup WHERE usergroup.id=(";
//		$sql.="SELECT book_admin.usergroupid FROM book_admin WHERE book_admin.username='$_SESSION[username]')";
//		$result=mysqli_query($con,$sql) or die("执行错误".mysqli_error($con));
//		if($row=mysqli_fetch_array($result))
//		{
//			$userpri=array("groupname"=>$row['name'],"mediapriv"=>$row['mediapriv'],"taskpriv"=>$row['taskpriv'],"terminalpriv"=>$row['terminalpriv'],"userpriv"=>$row['userpriv'],"level"=>$row['level']);	
//			$smarty->assign("userpri",$userpri);
//			unset($userpri);
//		}
//	}
//	////////////////////////////////////////////////////////////////////////////////////////////////////////////获取用户的权限和优先级2010/6/3结束
	///////////////////////////////////////////////////////////////////////////////////////////////添加2010/4/20结束		
	{
		$result2	=	mysqli_query($con,"SELECT streamid,name FROM `serverplaystream` ");
		while ($row2 = mysqli_fetch_array($result2)) 
		{
			$array2[]	=	 array("streamid"=>$row2['streamid'],"name"=>$row2['name']);
		}
		$smarty->assign("stream",$array2);
	}
	//分页
	if($Num != 0)
	{
		require_once("pagination.class.php");
		$p = new pagination;
		$p->Items($Num);
		$p->limit($NumOfPage);
		$p->target("?id=".$_GET['id']."&");
		$p->currentPage($_GET['page']);
		$p->adjacents(3);
		$smarty->assign("pagestr",$p->show());
	}
	
	$smarty->assign("start",$start);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("TelBroadManager/telBroadManager.html");
}
?>
