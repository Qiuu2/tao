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
	if(have_rights("taskpriv") || is_admin($con,$_SESSION['username']))
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
	}
	else 
	{
	    $page=$_GET['page'];
	    $start=($_GET['page']-1)*$NumOfPage;
	}
	$col=0;
	if(isset($_GET['col']))
	{
	$col=trim($_GET['col']);
	}
	$dataType="";
	if(isset($_GET['dataType']))
	{
	$dataType=trim($_GET['dataType']);
	}

	$userid=$_SESSION['userid'];
	
	$get_task_id = 0;//默认共享
	if(isset($_GET['id']))
	{
		$get_task_id = trim($_GET['id']);
		$_SESSION['tran_task_value'] = $get_task_id;
		$userid = trim($_GET['userid']);
	}
	else
	{
	
		if(empty($_GET['id']))
		{
			if($userid==1)
			{
				$sqls="SELECT id FROM ledtaskfree WHERE userid='$userid' ";
				$results	=	mysqli_query($con,$sqls) or die("Execute error".mysqli_error($con));
				if(mysqli_num_rows($results) > 0)
				{
					if($rows = mysqli_fetch_array($results)) 
					{
						$_SESSION['tran_task_value']= $rows['id'];
						$_GET['id']=$rows['id'];
						
					}
				}
				else
				{
					$_SESSION['tran_task_value']=0;
				}
			
			}
			else
			{
			$_SESSION['tran_task_value']=0;
			}
				
		}
		$get_task_id = $_SESSION['tran_task_value'];
	}

	//查询
	$sql="";
	if(!empty($_POST['searchvalue']))
	{
		if($_POST['searchkey']!="")
		{
			if($_POST['searchkey']=="taskname")
			{
				if($_POST['searchsequence']!="")
				{
					if($_SESSION['username']=="admin")
					{
					
					$sql="SELECT * FROM task WHERE tasktype IN (24) AND sec_task_id=0 and parentid='$get_task_id' and taskname LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY '$_GET[searchsequence]' desc ";
					}
					else
					{
					$sql="SELECT * FROM task WHERE tasktype IN (24) AND sec_task_id=0  AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') and parentid='$get_task_id' and task_user_id='$userid' and taskname LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY '$_GET[searchsequence]' desc ";
					}
				}
				else
				{
					if($_SESSION['username']=="admin")
					{
					$sql="SELECT * FROM task WHERE tasktype IN (24) AND sec_task_id=0 and  parentid='$get_task_id' and taskname LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY playtime desc ";
					}
					else
					{
					$sql="SELECT * FROM task WHERE tasktype IN (24) AND sec_task_id=0  AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') and  parentid='$get_task_id' and task_user_id='$userid' and taskname LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY playtime desc ";
					}
				}
			}
			
			if($_POST['searchkey']=="playtime")
			{
				if($_POST['searchsequence']!="")
				{
					if($_SESSION['username']=="admin")
					{
							
					$sql="SELECT * FROM task WHERE tasktype IN (24) AND sec_task_id=0 and parentid='$get_task_id' and  playtime >= '".trim($_POST['searchvalue'])."' ORDER BY playtime desc ";
					}
					else
					{
					$sql="SELECT * FROM task WHERE tasktype IN (24) AND sec_task_id=0  AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') and parentid='$get_task_id' and task_user_id='$userid' and  playtime >= '".trim($_POST['searchvalue'])."' ORDER BY playtime desc ";
					}
				}
				else
				{
					
					if($_SESSION['username']=="admin")
					{
					$sql="SELECT * FROM task WHERE tasktype IN (24) AND sec_task_id=0  and parentid='$get_task_id' and playtime >= '".trim($_POST['searchvalue'])."' ORDER BY playtime desc ";
					}
					else
					{
						$sql="SELECT * FROM task WHERE tasktype IN (24)  AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') AND sec_task_id=0 and parentid=$get_task_id and task_user_id='$userid' and playtime >= '".trim($_POST['searchvalue'])."' ORDER BY playtime desc ";
					}
				}
			}
		}	
		else
		{	
			
			if($_SESSION['username']=="admin")
			{
				$sql="SELECT * FROM task WHERE tasktype IN (24) AND sec_task_id=0 and parentid='$get_task_id' and taskname LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY playtime desc ";
			}
			else
			{
				$sql="SELECT * FROM task WHERE tasktype IN (24) AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') AND sec_task_id=0 and parentid='$get_task_id' and task_user_id='$userid' and taskname LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY playtime desc ";
			}
		}
	}
	else
	{
		if($_SESSION['admin_id']!="administrator")
		{
		$sql="SELECT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,defaultvolume,task_user_id,username FROM task,book_admin WHERE task_user_id=book_admin.id AND tasktype IN (24) AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') AND sec_task_id=0 AND parentid='$get_task_id' ORDER BY playtime desc";
		}
		else
		{
		$sql="SELECT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,defaultvolume,task_user_id,book_admin.username FROM task,book_admin WHERE task_user_id=book_admin.id AND parentid='$get_task_id' AND sec_task_id=0 AND tasktype IN (24) ORDER BY playtime desc";
		}
	}

	//查询
	if(!isset($_SESSION['admin_id']))
	{
		$result	=	mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
		$Num	=	mysqli_num_rows($result);
	//	$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
	}
	else
	{
		$result	=	mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
		$Num	=	mysqli_num_rows($result);
	//	$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
	}
	
	$info=array();
	$mediaarray=array();
	$termianlarray=array();
	while ($row = mysqli_fetch_array($result)) 
	{

		$taskid = $row['taskid'];
		$mediaSql="SELECT DISTINCT mediaoftask.taskid,media.id,media.name,media.size,media.filename FROM media,mediaoftask WHERE media.id=mediaoftask.mediaid AND mediaoftask.taskid = $taskid";
		$mediaresult=mysqli_query($con,$mediaSql);
		while($row0=mysqli_fetch_array($mediaresult))
		{
		$mediaarray[]=array("mediaid"=>$row0['id'],"medianame"=>$row0['name'],"size"=>$row0['size'],"mediafilename"=>$row0['filename']);
		}
		$terminalSql="SELECT DISTINCT terminaloftask.taskid,terminal.id,terminal.terminalname,terminaltype.name,terminal.netstate,terminal.devicestate,terminal.taskstate,terminal.ip,terminal.postion,terminal.volume FROM terminal,terminaloftask,terminaltype WHERE terminal.typeid=terminaltype.id AND terminal.id=terminaloftask.terminalid AND terminaloftask.taskid =$taskid";
		$terminalresult=mysqli_query($con,$terminalSql);
		while($row1=mysqli_fetch_array($terminalresult))
		{
		$termianlarray[]=array("terminalid"=>$row1['id'],"terminalname"=>$row1['terminalname'],"terminaltypename"=>$row1['name'],"netstate"=>$row1['netstate'],"taskstate"=>$row1['taskstate'],"terminalip"=>$row1['ip'],"postion"=>$row1['$postion'],"volume"=>$row1['volume']);
		}
	$info[]=array(
					"taskid"=>$row['taskid'],"taskname"=>$row['taskname'],"israndomplay"=>$row['israndomplay'],"projectstate"=>$row['projectstate'],
					"state"=>$row['state'],"startdate"=>$row['startdate'],"enddate"=>$row['enddate'], "playtime"=>$row['playtime'], 
					"timelength"=>$row['timelength'],
					"exemodel"=>$row['exemodel'],"priority"=>($row['priority']),"tasktype"=>$row['tasktype'],
					"timelengthtype"=>$row['timelengthtype'],"mediaArray"=>$mediaarray,"termianlArray"=>$termianlarray,
					"playfileid"=>$row['playfileid'],"defaultvolume"=>$row['defaultvolume'],"task_user_id"=>$row['task_user_id'],"username"=>$row['username']);
	unset($mediaarray);
	unset($termianlarray);
	}
	//$arrayinfo=getarray($array,$start,$NumOfPage,$get_task_id,$col,$dataType);

	$smarty->assign("info",$info);
	
	@mysqli_free_result($result);
	
	unset($sql,$row,$array);
	$medialist=array();
	//读取媒体文件
	$sql_media = "SELECT media.id,media.name FROM media";
	$result_media = mysqli_query($con,$sql_media) or die("Execute error".mysqli_error($con));
	while($row_media=mysqli_fetch_array($result_media))
	{
		$medialist[]=array("id"=>$row_media['id'],"name"=>$row_media['name']);
	}
	$smarty->assign("medialist",$medialist);
	
	@mysqli_free_result($result_media);
	unset($sql_media,$row_media,$medialist);
	 
	if($get_task_id==0)
	{
	$sign =200;
	}

	$results = mysqli_query($con,"SELECT parentid,userid FROM ledtaskfree WHERE id=$get_task_id");
		if ($rows = mysqli_fetch_array($results)) 
		{
			if(($rows['parentid']==0))
			{
				$sign = 1;  
			}
			 else
			 {
			 $sign =0;
			  
			 }

			$userid=$rows['userid'];
  		}
		
		$smarty->assign("sign",$sign);
		$gettreeid=array();
		if($_SESSION['admin_id']=="administrator")
		{
			$getresults = mysqli_query($con,"SELECT id,name FROM ledtaskfree ORDER BY id asc ");
		}
		else
		{
			$getresults = mysqli_query($con,"SELECT id,name FROM ledtaskfree WHERE userid='$userid' ORDER BY id asc ");
		}
		
		while ($getrows = mysqli_fetch_array($getresults)) 
		{
			$gettreeid[]=array("id"=>$getrows['id'],"name"=>$getrows['name']);
		}
		$smarty->assign("gettreeid",$gettreeid);

	//分页
/*
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
	$smarty->assign("userid",$userid);
	$smarty->assign("get_task_id",$get_task_id);
	$smarty->assign("start",$page);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("ledmanager/ledTaskManager.html");
}
?>
