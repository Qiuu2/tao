<?php
if (!session_id()) session_start();
	
require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');
//验证是否有效
require_once("verify_user_sessionin_valid.php");

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

	if(have_rights("ttspriv") || is_admin($con,$_SESSION['username']))
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
			if($_GET['searchkey']=="startdate")
			{
				if($_GET['searchsequence']!="")
				{
					$sql = "SELECT id,enstate,startdate,starttime,taskid,flag FROM enabletask WHERE startdate LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY '$_GET[searchsequence]' DESC";
				}
				else
				{
					$sql = "SELECT id,enstate,startdate,starttime,taskid,flag FROM enabletask WHERE startdate LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id DESC";
				}
			}
		}	
		else
		{	
			if($_GET['searchsequence']!="")
			{
				$sql = "SELECT id,enstate,startdate,starttime,taskid,flag FROM enabletask WHERE starttime LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY '$_GET[searchsequence]' DESC";
			}
			else
			{
				$sql = "SELECT id,enstate,startdate,starttime,taskid,flag FROM enabletask WHERE starttime LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id DESC";
			}
		}
	}
	else
	{
	
		if($_SESSION['username']=="admin")
		{
			$sql = "SELECT id,enstate,startdate,starttime,taskid,flag FROM enabletask ORDER BY id DESC";
		}
		else
		{
			$sql = "SELECT id,enstate,startdate,starttime,taskid,flag FROM enabletask ORDER BY id DESC";
		}
	}
	
	//搜索关键字
		$result	=	mysqli_query($con,$sql);
		$Num	=	mysqli_num_rows($result);
	//	$result = 	mysqli_query($con,$sql." LIMIT $start,$NumOfPage");

	$info=array();
	$taskarray="";
	while ($row = mysqli_fetch_array($result)) 
	{
		$taskarray=$row['taskid'];
		$startname="";
		if($taskarray!="")
		{
		$sqls = "SELECT taskid,taskname,info,tasktype FROM task WHERE taskid IN($taskarray)";
		$results	=	mysqli_query($con,$sqls);
		while ($rowss = mysqli_fetch_array($results)) 	
		{
			if($rowss['info']=="")
			{
				if($startname=="")
					$startname=$rowss['taskname'];
				else
					$startname=$startname.",".$rowss['taskname'];
			}
			else
			{
				if($startname=="")
					$startname=$rowss['info'];
				else
					$startname=$startname.",".$rowss['info'];
			}
		}
		@mysqli_free_result($results);
	
		unset($rows,$sqls);
		}
	
		$info[]=array(
						"id" => $row['id'],
						"enstate" => $row['enstate'],
						"startdate" => $row['startdate'],
						"starttime" => $row['starttime'],
						"startname" => $startname,
						"flag" => $row['flag'],
					);
	}
	
	$smarty->assign("info",$info);
	
	@mysqli_free_result($result);
	
	unset($array,$row,$sql);
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
	//输出session
	$smarty->assign("start",$start);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("holidaymanager/enableManager.html");
}
?>
