<?php
if (!session_id()) session_start();
	
require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');
//验证是否有效
require_once("verify_user_sessionin_valid.php");
$smarty->assign("registerflag",$_SESSION['registerflag']);
verifysessionvalid();
 if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE"))   
 {
	$IE =1;
	$smarty->assign("IE",$IE);
}
 else if(strpos($_SERVER["HTTP_USER_AGENT"],"Firefox"))   
{
	$IE =1;
	$smarty->assign("IE",$IE);
}
  else
  {
  	$IE =0; 
	$smarty->assign("IE",$IE);
  }

if(empty($_SESSION['admin_id']))
{
	header("location:login.php");
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("bell_manager",$bell_manager);
	
	$smarty->assign("Bellmanager",$Bellmanager);

	$smarty->assign("Searchform",$Searchform);

	$smarty->assign("Revise",$Revise);
	//获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");

	if(have_rights("bellpriv") || is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	
	require('editor.php');

	$smarty->assign('descriptionarea',$descriptionarea);
	
	if(!isset($_GET['page']))
	{
	    $page=1;
	    $start=0;
	}else 
	{
	    $page=$_GET['page'];
	    $start=($_GET['page']-1)*$NumOfPage;
	}
	$userid=$_SESSION['userid'];
	$sql="";
	if($_GET['searchvalue']!="")
	{
		if($_GET['searchkey']!="")
		{
			if($_GET['searchkey']=="info")
			{
				if($_GET['searchsequence']!="")
				{
					if($_SESSION['username']=="admin")
					{
					$sql = "SELECT MIN(taskid) as taskid,task.info,CASE ROUND(AVG(projectstate),0) WHEN 1 THEN 1 WHEN 0 THEN 0 END AS projectstate,book_admin.username,";
					$sql.= "MIN(startdate) AS startdate, MAX(enddate) AS enddate, COUNT(taskname) AS taskname FROM task ,book_admin WHERE book_admin.id=task.task_user_id AND tasktype=1 and task.info ";
		
					$sql.= "LIKE '%".trim($_GET['searchvalue'])."%' AND channel=0 AND sec_task_id=0 GROUP BY task.info ORDER BY '$_GET[searchsequence]' DESC ";
					}
					else
					{
					$sql = "SELECT MIN(taskid) as taskid,task.info,CASE ROUND(AVG(projectstate),0) WHEN 1 THEN 1 WHEN 0 THEN 0 END AS projectstate,book_admin.username,";
					$sql.= "MIN(startdate) AS startdate, MAX(enddate) AS enddate, COUNT(taskname) AS taskname FROM task ,book_admin WHERE book_admin.id=task.task_user_id AND tasktype=1 and task.info ";
		
					$sql.= "LIKE '%".trim($_GET['searchvalue'])."%' AND channel=0 AND sec_task_id=0 AND task_user_id='$userid' GROUP BY task.info ORDER BY '$_GET[searchsequence]' DESC ";
					
					}
				}
				else
				{
					if($_SESSION['username']=="admin")
					{
					$sql = "SELECT MIN(taskid) as taskid,task.info,CASE ROUND(AVG(projectstate),0) WHEN 1 THEN 1 WHEN 0 THEN 0 END AS projectstate,book_admin.username, ";
		
					$sql.= "MIN(startdate) AS startdate, MAX(enddate) AS enddate, COUNT(taskname) AS taskname FROM task,book_admin  WHERE book_admin.id=task.task_user_id AND tasktype=1 ";
		
					$sql.= "and task.info LIKE '%".trim($_GET['searchvalue'])."%' AND channel=0 AND sec_task_id=0 GROUP BY task.info ORDER BY projectstate,startdate ASC ";
					}
					else
					{
					$sql = "SELECT MIN(taskid) as taskid,task.info,CASE ROUND(AVG(projectstate),0) WHEN 1 THEN 1 WHEN 0 THEN 0 END AS projectstate,book_admin.username, ";
		
					$sql.= "MIN(startdate) AS startdate, MAX(enddate) AS enddate, COUNT(taskname) AS taskname FROM task,book_admin  WHERE book_admin.id=task.task_user_id AND tasktype=1 ";
		
					$sql.= "and task.info LIKE '%".trim($_GET['searchvalue'])."%' AND channel=0 AND sec_task_id=0 AND task_user_id='$userid' GROUP BY task.info ORDER BY projectstate,startdate ASC ";
					}
				}
			}
		}	
		else
		{	
			if($_GET['searchsequence']!="")
			{
				if($_SESSION['username']=="admin")
				{
				$sql = "SELECT MIN(taskid) as taskid,task.info,CASE ROUND(AVG(projectstate),0) WHEN 1 THEN 1 WHEN 0 THEN 0 END AS projectstate,book_admin.username, ";
		
				$sql.= "MIN(startdate) AS startdate, MAX(enddate) AS enddate, COUNT(taskname) AS taskname FROM task ,book_admin WHERE book_admin.id=task.task_user_id AND tasktype=1 and task.info ";
		
				$sql.= "LIKE '%".trim($_GET['searchvalue'])."%' AND channel=0 AND sec_task_id=0 GROUP BY task.info ORDER BY '$_GET[searchsequence]' ASC ";
				}
				else
				{
				$sql = "SELECT MIN(taskid) as taskid,task.info,CASE ROUND(AVG(projectstate),0) WHEN 1 THEN 1 WHEN 0 THEN 0 END AS projectstate,book_admin.username, ";
		
				$sql.= "MIN(startdate) AS startdate, MAX(enddate) AS enddate, COUNT(taskname) AS taskname FROM task ,book_admin WHERE book_admin.id=task.task_user_id AND tasktype=1 and task.info ";
		
				$sql.= "LIKE '%".trim($_GET['searchvalue'])."%' AND channel=0 AND sec_task_id=0 AND task_user_id='$userid' GROUP BY task.info ORDER BY '$_GET[searchsequence]' ASC ";
				
				}
			}
			else
			{
				if($_SESSION['username']=="admin")
				{
					$sql = "SELECT MIN(taskid) as taskid,task.info,CASE ROUND(AVG(projectstate),0) WHEN 1 THEN 1 WHEN 0 THEN 0 END AS projectstate,book_admin.username,";
			
					$sql.= "MIN(startdate) AS startdate, MAX(enddate) AS enddate, COUNT(taskname) AS taskname FROM task ,book_admin WHERE book_admin.id=task.task_user_id AND tasktype=1 ";
			
					$sql.= "and task.info LIKE '%".trim($_GET['searchvalue'])."%' AND channel=0 AND sec_task_id=0 GROUP BY task.info ORDER BY projectstate,startdate ASC ";
				}
				else
				{
				$sql = "SELECT MIN(taskid) as taskid,task.info,CASE ROUND(AVG(projectstate),0) WHEN 1 THEN 1 WHEN 0 THEN 0 END AS projectstate,book_admin.username,";
			
					$sql.= "MIN(startdate) AS startdate, MAX(enddate) AS enddate, COUNT(taskname) AS taskname FROM task ,book_admin WHERE book_admin.id=task.task_user_id AND tasktype=1 ";
			
					$sql.= "and task.info LIKE '%".trim($_GET['searchvalue'])."%' AND channel=0 AND sec_task_id=0  AND task_user_id='$userid' GROUP BY task.info ORDER BY projectstate,startdate ASC ";
				}
			}
		}
	}
	else
	{
	
		if($_SESSION['username']=="admin")
		{
			$sql = "SELECT MIN(taskid) as taskid,task.info,task.state,CASE ROUND(AVG(projectstate),0) WHEN 1 THEN 1 WHEN 0 THEN 0 END AS projectstate, book_admin.username,";
			$sql.= "MIN(startdate) AS startdate, MAX(enddate) AS enddate, COUNT(taskname) AS taskname ";
			$sql.= "FROM task,book_admin WHERE book_admin.id=task.task_user_id AND tasktype IN(1,15) AND task.info !='' AND channel=0 AND sec_task_id=0 GROUP BY info ORDER BY projectstate,startdate ASC ";
		}
		else
		{
			$sql = "SELECT MIN(taskid) as taskid,task.info,task.state,CASE ROUND(AVG(projectstate),0) WHEN 1 THEN 1 WHEN 0 THEN 0 END AS projectstate,book_admin.username, ";
			$sql.= "MIN(startdate) AS startdate, MAX(enddate) AS enddate, COUNT(taskname) AS taskname ";
			$sql.= "FROM task,book_admin  WHERE book_admin.id=task.task_user_id AND tasktype IN(1,15) AND task.info !='' AND channel=0 AND sec_task_id=0 AND task_user_id='$userid' GROUP BY info ORDER BY projectstate,startdate ASC ";
		}
	}
	
	//搜索关键字
		$result	=	mysqli_query($con,$sql);
		
		$Num	=	mysqli_num_rows($result);
		
	//	$result = 	mysqli_query($con,$sql." LIMIT $start,$NumOfPage");

	$info=array();
	while ($row = mysqli_fetch_array($result)) 
	{
		$info[]=array(
							"taskid" => $row['taskid'],
							"info" => $row['info'],	
							"taskname" => $row['taskname'],
							"projectstate" => $row['projectstate'],
							"startdate" => $row['startdate'],
							"enddate" => $row['enddate'],
							"username" => $row['username'],
							"state" => $row['state'],
					  );
	}

	$smarty->assign("info",$info);
	
	mysqli_free_result($result);
	
	unset($info,$row,$sql);
	
	//分页
/*	if($Num != 0)
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
	
	$smarty->display("BellManager/bellManager.html");
}
?>
