<?php
if (!session_id()) session_start();

header("Content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

//�ж��Ƿ�ʧЧ

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
	header('location:login.php');	
}
else
{
	//��ʾ������
	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("collect_manager",$collect_manager);

	$smarty->assign("Admmanager",$Admmanager);

	$smarty->assign("Searchform",$Searchform);

	$smarty->assign("Revise",$Revise);

	//��ȡȨ��
	require_once("User_Rights_Manage/verify_user_rights_class.php");

	if(have_rights("admpriv") || is_admin($con,$_SESSION['username']))
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
	}
	else 
	{
	    $page=$_GET['page'];
		
	    $start=($_GET['page']-1)*$NumOfPage;
	}
	//��ȡ�ɲ���Ϣ
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
					if($_SESSION['username']=="admin")
					{
					$sql="SELECT * FROM task WHERE tasktype=10 and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY '$_GET[searchsequence]' desc";
					}
					else
					{
					$sql="SELECT * FROM task WHERE tasktype=10 and task_user_id='$userid' and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY '$_GET[searchsequence]' desc";
					}
				}
				else
				{
					if($_SESSION['username']=="admin")
					{
					$sql="SELECT * FROM task WHERE tasktype=10 and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY taskid desc ";
					}
					else
					{
					$sql="SELECT * FROM task WHERE tasktype=10 and task_user_id='$userid' and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY taskid desc ";
					}
				}
			}
			
			if($_GET['searchkey']=="playtime")
			{
				if($_GET['searchsequence']!="")
				{
					if($_SESSION['username']=="admin")
					{
					$sql="SELECT * FROM task WHERE tasktype=10 and  playtime >= '".trim($_GET['searchvalue'])."' ORDER BY taskid desc ";
					}
					else
					{
					$sql="SELECT * FROM task WHERE tasktype=10 and task_user_id='$userid' and  playtime >= '".trim($_GET['searchvalue'])."' ORDER BY taskid desc ";
					}
				}
				else
				{
					if($_SESSION['username']=="admin")
					{
					$sql="SELECT * FROM task WHERE tasktype=10 and playtime >= '".trim($_GET['searchvalue'])."' ORDER BY taskid desc ";
					}
					else
					{
					$sql="SELECT * FROM task WHERE tasktype=10 and task_user_id='$userid' and playtime >= '".trim($_GET['searchvalue'])."' ORDER BY taskid desc ";
					}
				}
			}
		}	
		else
		{	
			if($_SESSION['username']=="admin")
			{
			$sql="SELECT * FROM task WHERE tasktype=10 and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY taskid desc ";
			}
			else
			{
			$sql="SELECT * FROM task WHERE tasktype=10 and task_user_id='$userid' and taskname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY taskid desc ";
			}
		}
	}
	else
	{
	if($_SESSION['username']=="admin")
		$sql="SELECT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,channel,defaultvolume,cmdargs,bandrate,samplerate,task_user_id,book_admin.username FROM task,book_admin WHERE task_user_id=book_admin.id AND tasktype ='10' ORDER BY playtime DESC";
	else
		$sql="SELECT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,channel,defaultvolume,cmdargs,bandrate,samplerate,task_user_id,book_admin.username FROM task,book_admin WHERE task_user_id=book_admin.id AND tasktype ='10' AND task_user_id='$userid' ORDER BY taskid desc ";
	}
	
	if(!isset($_SESSION['admin_id']))
	{
		$result	=	mysqli_query($con,$sql);
		$Num	=	mysqli_num_rows($result);
		//$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	}
	else
	{
		$result	=	mysqli_query($con,$sql);
		
		$Num	=	mysqli_num_rows($result);
		
		//$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	}
	$termianlArray=array();
	$info=array();
	while ($row = mysqli_fetch_array($result)) 
	{
		$taskid = $row['taskid'];
		
		
		$mediaSql = "SELECT DISTINCT mediaoftask.taskid,media.id,media.name,media.size,media.filename FROM media,mediaoftask ";
		
		$mediaSql.= "WHERE media.id=mediaoftask.mediaid AND mediaoftask.taskid = $taskid ";
		
		$mediaResult = mysqli_query($con,$mediaSql) or die(mysqli_error($con));
		
		while($row0 = mysqli_fetch_array($mediaResult))
		{
			$mediaArray[]=array("mediaid"=>$row0['id'],"medianame"=>$row0['name'],"size"=>$row0['size'],"mediafilename"=>$row0['filename']);
		}
		
		@mysqli_free_result($mediaResult);
		
		unset($mediaSql,$row0);
		//�ն���Ϣ
		$terminalSql="SELECT DISTINCT terminaloftask.taskid,terminal.id,terminal.terminalname, ";
		
		$terminalSql.= "terminaltype.name,terminal.netstate,terminal.devicestate,terminal.taskstate,terminal.ip,";
		
		$terminalSql.= "terminal.postion,terminal.volume FROM terminal,terminaloftask,terminaltype WHERE ";
		
		$terminalSql.= "terminal.typeid=terminaltype.id AND terminal.id=terminaloftask.terminalid AND terminaloftask.taskid =$taskid ";
		
		$terminalResult = mysqli_query($con,$terminalSql) or die(mysqli_error($con));
		
		while($row1=mysqli_fetch_array($terminalResult))
		{
			$termianlArray[]=array(
										"terminalid"=>$row1['id'],"terminalname"=>$row1['terminalname'],
										
										"terminaltypename"=>$row1['name'],"netstate"=>$row1['netstate'],
										
										"taskstate"=>$row1['taskstate'],"terminalip"=>$row1['ip'],
										
										"postion"=>$row1['$postion'],"volume"=>$row1['volume']
									);
		}
		@mysqli_free_result($terminalResult);
		
		unset($terminalSql,$row1);
		
		//ȥ�ɲ�����ɲ��ն�
		$col_terminal_id = "";
		
		$col_terminal_sql = "SELECT terminalid FROM terminaloftask WHERE terminaloftask.taskid = (SELECT taskid FROM task WHERE ";

		$col_terminal_sql.= "task.sec_task_id = '$taskid' AND task.channel !=0 AND task.tasktype = 5 AND task.info = '') ";
		
		$col_terminal_result = mysqli_query($con,$col_terminal_sql) or die(mysqli_error($con));
		
		if($col_terminal_row = mysqli_fetch_array($col_terminal_result))
		{
			$col_terminal_id = $col_terminal_row['terminalid'];
		}
		
		@mysqli_free_result($col_terminal_result);
		
		unset($col_terminal_sql,$col_terminal_row);
		
	
		$info[]=array(
						"taskid"=>$row['taskid'],"taskname"=>$row['taskname'],"israndomplay"=>$row['israndomplay'],
						
						"onlyopenpower"=>$row['onlyopenpower'],"prepower"=>$row['prepower'],"datasendmodel"=>$row['datasendmodel'],
						
						"priority"=>($row['priority']),"cmd"=>$row['cmd'],"cmdargs"=>$row['cmdargs'],"channel"=>$row['channel'],
						
						"bandrate"=>$row['bandrate'],"samplerate"=>$row['samplerate'],"state"=>$row['state'],
						
						"startdate"=>$row['startdate'],"enddate"=>$row['enddate'], "playtime"=>$row['playtime'],
						
						"playmodel"=>$row['playmodel'],"timelength"=>$row['timelength'],
						
						"exemodel"=>$row['exemodel'],"timelengthtype"=>$row['timelengthtype'],
						
						"defaultvolume"=>$row['defaultvolume'],"username"=>$row['username'],"col_terminal_id"=>$col_terminal_id
					);

		unset($mediaArray,$termianlArray);
	}
	
	$smarty->assign("info",$info);
	
	@mysqli_free_result($result);
	
	unset($sql,$row,$array);
	$terminal_info=array();
	//��ȡ���вɲ��ն���Ϣ
	$terminal_sql = "select id,terminalname from terminal where terminal.typeid = '8'";
	
	$terminal_result = mysqli_query($con,$terminal_sql) or die(mysqli_error($con));
	
	while($terminal_row = mysqli_fetch_array($terminal_result))
	{
		$terminal_info[] = array("id"=>$terminal_row['id'],"terminalname"=>$terminal_row['terminalname']);
	}
	
	$smarty->assign("terminal_info",$terminal_info);
	
	@mysqli_free_result($terminal_result);
	
	unset($terminal_sql,$terminal_row,$terminal_info);
	
	$array2=array();
	
		$result2 = mysqli_query($con,"SELECT streamid,name FROM serverplaystream");
		while ($row2 = mysqli_fetch_array($result2)) 
		{
			$array2[] = array("streamid"=>$row2['streamid'],"name"=>$row2['name']);
		}
		$smarty->assign("stream",$array2);
	
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

	$smarty->assign("start",$start);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("WebRadio/WebRadio.html");
}
?>

