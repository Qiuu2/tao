<?php
if (!session_id()) session_start();

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
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("terminal_function",$terminal_function);

	$smarty->assign("Admmanager",$Admmanager);

	$smarty->assign("Searchform",$Searchform);

	$smarty->assign("Revise",$Revise);
	//判断用户权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");

	if( have_rights("powerplay") || is_admin($con,$_SESSION['username']) )
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	
	require('editor.php');

	$smarty->assign('descriptionarea',$descriptionarea);//导入分页类

	$mediaArray="";//媒体文件列表

	$termianlArray="";//终端列表

	$result=0;//获取功放集

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
	//=====功放特殊性、查询功放=====//
	$userid=$_SESSION['userid'];
	$sql="";
	if(!empty($_GET['searchvalue']))
	{
		if(!empty($_GET['searchkey']))
		{
			if(trim($_GET['searchkey'])=="taskname")
			{
				if(!empty($_GET['searchsequence']))
				{
					if($_SESSION['username']=="admin")
					{
					$sql = "SELECT DISTINCT * FROM task WHERE taskname LIKE '%".trim($_GET['searchvalue'])."%' AND tasktype = 5 ";	
					$sql.= " AND sec_task_id=0 AND channel = 0 AND bandrate=0 ORDER BY startdate, enddate DESC, playtime ASC ";
					}
					else
					{
					$sql = "SELECT DISTINCT * FROM task WHERE taskname LIKE '%".trim($_GET['searchvalue'])."%' AND tasktype = 5 ";	
					$sql.= " and task_user_id='$userid' AND sec_task_id=0 AND channel = 0 AND bandrate=0 ORDER BY startdate, enddate DESC, playtime ASC ";
					}
				}
				else
				{
					if($_SESSION['username']=="admin")
					{
					$sql = "SELECT DISTINCT * FROM task WHERE taskname LIKE '%".trim($_GET['searchvalue'])."%' AND tasktype = 5 ";
					$sql.= " AND sec_task_id=0 AND channel = 0 AND bandrate=0 ORDER BY startdate, enddate DESC, playtime ASC ";
					}
					else
					{
					$sql = "SELECT DISTINCT * FROM task WHERE taskname LIKE '%".trim($_GET['searchvalue'])."%' AND tasktype = 5 ";
					$sql.= " and task_user_id='$userid' AND sec_task_id=0 AND channel = 0 AND bandrate=0 ORDER BY startdate, enddate DESC, playtime ASC ";
					}
				}
			}
			
			if(trim($_GET['searchkey'])=="playtime")
			{
				if($_GET['searchsequence']!="")
				{	
					if($_SESSION['username']=="admin")
					{				
					$sql = "SELECT DISTINCT * FROM task WHERE playtime >= '".trim($_GET['searchvalue'])."' AND tasktype = 5 ";
					$sql.= " AND sec_task_id=0 AND channel = 0 AND bandrate=0 ORDER BY startdate, enddate DESC, playtime ASC ";
					}
					else
					{
					$sql = "SELECT DISTINCT * FROM task WHERE playtime >= '".trim($_GET['searchvalue'])."' AND tasktype = 5 ";
					$sql.= " and task_user_id='$userid' AND sec_task_id=0 AND channel = 0 AND bandrate=0 ORDER BY startdate, enddate DESC, playtime ASC ";
					}
				}
				else
				{	
					if($_SESSION['username']=="admin")
					{				
						$sql = "SELECT DISTINCT * FROM task WHERE playtime >= '".trim($_GET['searchvalue'])."' AND tasktype = 5 ";
						$sql.= " AND sec_task_id=0 AND channel = 0 AND bandrate=0 ORDER BY startdate, enddate DESC, playtime ASC ";
					}
					else
					{
						$sql = "SELECT DISTINCT * FROM task WHERE playtime >= '".trim($_GET['searchvalue'])."' AND tasktype = 5 ";
						$sql.= " and task_user_id='$userid' AND sec_task_id=0 AND channel = 0 AND bandrate=0 ORDER BY startdate, enddate DESC, playtime ASC ";
					}
				}
			}
		}	
		else
		{	
			if($_SESSION['username']=="admin")
			{
			$sql = "SELECT DISTINCT * FROM task WHERE taskname LIKE '%".trim($_GET['searchvalue'])."%' AND tasktype = 5 ";		
			$sql.= " AND sec_task_id=0 AND channel = 0 AND bandrate=0 ORDER BY startdate, enddate DESC, playtime ASC ";
			}
			else
			{
			$sql = "SELECT DISTINCT * FROM task WHERE taskname LIKE '%".trim($_GET['searchvalue'])."%' AND tasktype = 5 ";		
			$sql.= " and task_user_id='$userid' AND sec_task_id=0 AND channel = 0 AND bandrate=0 ORDER BY startdate, enddate DESC, playtime ASC ";
			}
		}
	}
	else
	{	
		if($_SESSION['username']=="admin")
		{
			$sql = "SELECT DISTINCT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,channel,defaultvolume,cmdargs,bandrate,samplerate,task_user_id,book_admin.username FROM task,book_admin WHERE task_user_id=book_admin.id AND tasktype = 5 AND channel = 0 AND sec_task_id = 0 AND prepower=0 ";
			$sql.= "AND bandrate=0 ORDER BY startdate, enddate DESC, playtime ASC ";
		}
		else
		{
			$sql = "SELECT DISTINCT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,channel,defaultvolume,cmdargs,bandrate,samplerate,task_user_id,book_admin.username FROM task,book_admin WHERE task_user_id=book_admin.id AND tasktype = 5 AND channel = 0 AND sec_task_id = 0 AND prepower=0 ";
			$sql.= "AND bandrate=0 AND task_user_id='$userid' ORDER BY startdate, enddate DESC, playtime ASC ";
		}
	}
	//获取功放属性
	if(!isset($_SESSION['admin_id']))
	{
		$result	= mysqli_query($con,$sql);
		$Num	=	mysqli_num_rows($result);
		//$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	}
	else
	{
		$result	= mysqli_query($con,$sql);
		$Num	=	mysqli_num_rows($result);
	//	$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	}
	$info=array();
	while ($row = mysqli_fetch_array($result)) 
	{
		$info[]=array(
						"taskid"=>$row['taskid'],"taskname"=>$row['taskname'],"state"=>$row['state'],"startdate"=>$row['startdate'],
					
						"enddate"=>$row['enddate'], "playtime"=>$row['playtime'], "playmodel"=>$row['playmodel'],
					
						"timelength"=>$row['timelength'],"exemodel"=>$row['exemodel'],"priority"=>($row['priority']%10),"username"=>$row['username'],
					
						"timelengthtype"=>$row['timelengthtype'],"defaultvolume"=>$row['defaultvolume']
					);
	}
	$smarty->assign("info",$info);
	
	@mysqli_free_result($result);
	
	unset($array,$row,$sql);

	$result2 = mysqli_query($con,"SELECT streamid,name FROM serverplaystream") or die(mysqli_error($con));
	$arrar2=array();
	while($row2 = mysqli_fetch_array($result2)) 
	{
		$array2[] = array("streamid"=>$row2['streamid'],"name"=>$row2['name']);
	}
	
	$smarty->assign("stream",$array2);
	@mysqli_free_result($result2);
	unset($row2);
	
/*	//状态分页
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
	//输出session
	$smarty->assign("start",$start);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("terminalfunctionplayerManager/terminalFunctionPlayManager.html");
}
?>
