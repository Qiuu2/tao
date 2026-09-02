<?php

if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

//验证是否失效
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	header('location:login.php');	
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("task_manager",$task_manager);

	$smarty->assign("Filetaskmanager",$Filetaskmanager);
	$smarty->assign("Searchform",$Searchform);
	$smarty->assign("Revise",$Revise);
	//获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	
	if(have_rights("ttspriv") || is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	
	//require('editor.php');
	
	$smarty->assign('descriptionarea',$descriptionarea);
	
	$mediaArray="";//保存媒体文件列表
	
	$termianlArray="";//保存终端列表
	
	$result=0;//获取task结果集
	
	if(!isset($_GET['page']))
	{
	    $page=1;
	
	    $start=0;
	}
	else 
	{
	    $page=$_GET['page'];
	
	    $start=($_GET['page']-1)*$NumOfPage;
	}
	
	$get_task_id = 1;//默认共享
	
	if(isset($_GET['id']))
	{
		$get_task_id = trim($_GET['id']);
		
		$_SESSION['tran_task_value'] = $get_task_id;
	}
	else
	{
		if(empty($_GET['id']) )
		{
			$_SESSION['tran_task_value'] = 1;
		}
		
		$get_task_id = $_SESSION['tran_task_value'];
	}
$userid=$_SESSION['userid'];
	//查询
	$sql="";
	if(!empty($_GET['searchvalue']))
	{
		if($_GET['searchkey']!="")
		{
			if($_GET['searchkey']=="taskname")
			{
				if($_GET['searchsequence']!="")
				{
					if($_SESSION['username']=="admin")
					{
					$sql="SELECT * FROM task WHERE tasktype IN (15,17,19)  and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY '$_GET[searchsequence]' desc ";
					}
					else
					{
						$sql="SELECT * FROM task WHERE tasktype IN (15,17,19) AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') and task_user_id='$userid' and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY '$_GET[searchsequence]' desc ";
					}
				}
				else
				{
					if($_SESSION['username']=="admin")
					{
					$sql="SELECT * FROM task WHERE tasktype IN (15,17,19) and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY playtime desc ";
					}
					else
					{
						$sql="SELECT * FROM task WHERE tasktype IN (15,17,19)  AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') and task_user_id='$userid' and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY playtime desc ";
					}
				}
			}
			
			if($_GET['searchkey']=="playtime")
			{
				if($_GET['searchsequence']!="")
				{
					if($_SESSION['username']=="admin")
					{
					$sql="SELECT * FROM task WHERE tasktype IN (15,17,19)  and  playtime >= '".trim($_GET['searchvalue'])."' ORDER BY playtime desc ";
					}
					else
					{
						$sql="SELECT * FROM task WHERE tasktype IN (15,17,19)  AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') and task_user_id='$userid' and  playtime >= '".trim($_GET['searchvalue'])."' ORDER BY playtime desc ";
					}
				}
				else
				{
					if($_SESSION['username']=="admin")
					{
					$sql="SELECT * FROM task WHERE tasktype IN (15,17,19)   and playtime >= '".trim($_GET['searchvalue'])."' ORDER BY playtime desc ";
					}
					else
					{
					$sql="SELECT * FROM task WHERE tasktype IN (15,17,19) AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') and task_user_id='$userid' and playtime >= '".trim($_GET['searchvalue'])."' ORDER BY playtime desc ";
					}
				}
			}
		}	
		else
		{	
			if($_SESSION['username']=="admin")
			{
			$sql="SELECT * FROM task WHERE tasktype IN (15,17,19) and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY playtime desc ";
			}
			else
			{
				$sql="SELECT * FROM task WHERE tasktype IN (15,17,19)  AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') and task_user_id='$userid' and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY playtime desc ";
			}
		}
	}
	else
	{
		if($_SESSION['username']!="admin")
		{
		$sql="SELECT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,defaultvolume,task_user_id,username FROM task,book_admin WHERE task_user_id=book_admin.id AND tasktype IN (15,17,19)  AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') ORDER BY playtime desc";
		}
		else
		{
		$sql="SELECT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,defaultvolume,task_user_id,book_admin.username FROM task,book_admin WHERE task_user_id=book_admin.id AND tasktype IN (15,17,19)  ORDER BY playtime desc";
		}
	}
	
	//查询
	if(!isset($_SESSION['admin_id']))
	{
		$result	=	mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
		$Num	=	mysqli_num_rows($result);
		//$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
	}
	else
	{
		$result	=	mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
		$Num	=	mysqli_num_rows($result);
	//	$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
	}
	$info=array();
	while ($row = mysqli_fetch_array($result)) 
	{
	
	$info[]=array(
					"taskid"=>$row['taskid'],"taskname"=>$row['taskname'],"israndomplay"=>$row['israndomplay'],"projectstate"=>$row['projectstate'],
					"state"=>$row['state'],"startdate"=>$row['startdate'],"enddate"=>$row['enddate'], "playtime"=>$row['playtime'], 
					"timelength"=>$row['timelength'],
					"exemodel"=>$row['exemodel'],"priority"=>($row['priority']),"tasktype"=>$row['tasktype'],
					"timelengthtype"=>$row['timelengthtype'],"playfileid"=>$row['playfileid'],"defaultvolume"=>$row['defaultvolume'],"task_user_id"=>$row['task_user_id'],"username"=>$row['username']);

	}

	$smarty->assign("info",$info);
	
	@mysqli_free_result($result);
	
	unset($sql,$row,$array);
/*	
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
	*/
	$smarty->assign("get_task_id",$get_task_id);
	$smarty->assign("start",$start);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("Tts/TtsTaskManager.html");
}
?>

