<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

//验证失效
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	header("location:login.php");
}
else
{
	//设置语言
	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("alarm_mapping",$alarm_mapping);

	/*动态显示页面文本*/
	$smarty->assign("Revise",$Revise);
	$smarty->assign("Termianl_manager_Options",$Termianl_manager_Options);
	$smarty->assign("Termianl_manager_Terminal_name",$Termianl_manager_Terminal_name);
	$smarty->assign("Termianl_manager_Belong_to_the_region",$Termianl_manager_Belong_to_the_region);
	$smarty->assign("Termianl_manager_Terminal_Type",$Termianl_manager_Terminal_Type);
	$smarty->assign("Termianl_manager_Suspend_state",$Termianl_manager_Suspend_state);
	$smarty->assign("Termianl_manager_Connection_Status",$Termianl_manager_Connection_Status);
	$smarty->assign("Termianl_manager_Running_state",$Termianl_manager_Running_state);
	$smarty->assign("Termianl_manager_IP_Address",$Termianl_manager_IP_Address);
	$smarty->assign("Termianl_manager_Volume",$Termianl_manager_Volume);
	
	$smarty->assign("Termianl_manager_Select_All",$Termianl_manager_Select_All);
	$smarty->assign("Termianl_manager_Cancel",$Termianl_manager_Cancel);
	$smarty->assign("Termianl_manager_Start",$Termianl_manager_Start);
	$smarty->assign("Termianl_manager_Stop",$Termianl_manager_Stop);
	$smarty->assign("Termianl_manager_Delete",$Termianl_manager_Delete);
	$smarty->assign("Termianl_manager_Shortcuts_Map",$Termianl_manager_Shortcuts_Map);
	$smarty->assign("Termianl_manager_Change_volume",$Termianl_manager_Change_volume);
	$smarty->assign("Termianl_manager_Confirm_change",$Termianl_manager_Confirm_change);
	
	
	$smarty->assign("Termianl_manager_Normal",$Termianl_manager_Normal);
	$smarty->assign("Termianl_manager_Suspend",$Termianl_manager_Suspend);
	$smarty->assign("Termianl_manager_Link",$Termianl_manager_Link);
	$smarty->assign("Termianl_manager_Run",$Termianl_manager_Run);
	$smarty->assign("Termianl_manager_Disconnect",$Termianl_manager_Disconnect);
	$smarty->assign("Termianl_manager_Stop",$Termianl_manager_Stop);
	
	$smarty->assign("Termianl_manager_Select_Options",$Termianl_manager_Select_Options);
	$smarty->assign("Termianl_manager_OK_delete",$Termianl_manager_OK_delete);
	$smarty->assign("Searchform",$Searchform);
	//获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
    $userid=$_SESSION['userid'];
	if(have_rights("alarmgrouppriv") || is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}

	//计算分页
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
	
	/*同时读出终端类型和分区，没有分区的终端不读出，即不显示*/
	$sql = "";
	if(trim($_GET['searchvalue'])!="")
	{
		if(trim($_GET['searchkey'])!="" && trim($_GET['searchsequence'])!="")
		{
			if(trim($_GET['searchkey'])=="terminalname")
			{
				$sql = "SELECT alarmgroupmap.id, alarmgroupmap.firealarmgroupid,alarmgroupmap.info,alarmgroupmap.alarmterminalid,terminal.terminalname,alarmgroupmap.alarmchannel, alarmarea.name as alarmname , media.name  ";
				$sql.= "FROM alarmgroupmap, alarmarea, media, terminal ";
				$sql.= "WHERE media.id = alarmgroupmap.mediaid AND alarmgroupmap.firealarmgroupid = alarmarea.id AND alarmgroupmap.alarmterminalid = terminal.id ";
				$sql.= "AND terminal.terminalname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY ".trim($_GET['searchsequence'])." DESC ";
			}
			
			if(trim($_GET['searchkey'])=="alarmname")
			{
				$sql = "SELECT alarmgroupmap.id, alarmgroupmap.firealarmgroupid,alarmgroupmap.info,alarmgroupmap.alarmterminalid,terminal.terminalname,alarmgroupmap.alarmchannel, alarmarea.name as alarmname, media.name  ";
				$sql.= "FROM alarmgroupmap, alarmarea, media, terminal ";
				$sql.= "WHERE media.id = alarmgroupmap.mediaid AND alarmgroupmap.firealarmgroupid = alarmarea.id AND alarmgroupmap.alarmterminalid = terminal.id ";
				$sql.= "AND alarmarea.name LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY ".trim($_GET['searchsequence'])." DESC ";			
			}
		}
		else if(!empty($_GET['searchkey']) && empty($_GET['searchsequence']))
		{
			if($_GET['searchkey']=="terminalname")
			{
				$sql = "SELECT alarmgroupmap.id, alarmgroupmap.firealarmgroupid,alarmgroupmap.info,alarmgroupmap.alarmterminalid,terminal.terminalname,alarmgroupmap.alarmchannel, alarmarea.name as alarmname, media.name  ";
				$sql.= "FROM alarmgroupmap, alarmarea, media, terminal ";
				$sql.= "WHERE media.id = alarmgroupmap.mediaid AND alarmgroupmap.firealarmgroupid = alarmarea.id AND alarmgroupmap.alarmterminalid = terminal.id ";
				$sql.= "AND terminal.terminalname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY alarmgroupmap.alarmchannel DESC ";
			}
			if($_GET['searchkey']=="alarmname")
			{
				$sql = "SELECT alarmgroupmap.id, alarmgroupmap.firealarmgroupid,alarmgroupmap.info,alarmgroupmap.alarmterminalid,terminal.terminalname,alarmgroupmap.alarmchannel, alarmarea.name as alarmname, media.name  ";
				$sql.= "FROM alarmgroupmap, alarmarea, media, terminal ";
				$sql.= "WHERE media.id = alarmgroupmap.mediaid AND alarmgroupmap.firealarmgroupid = alarmarea.id AND alarmgroupmap.alarmterminalid = terminal.id ";
				$sql.= "AND alarmarea.name LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY alarmgroupmap.alarmchannel DESC ";	
			}
		}
		else
		{
			$sql = "SELECT alarmgroupmap.id, alarmgroupmap.firealarmgroupid,alarmgroupmap.info,alarmgroupmap.alarmterminalid,terminal.terminalname,alarmgroupmap.alarmchannel, alarmarea.name as alarmname, media.name  ";
			$sql.= "FROM alarmgroupmap, alarmarea, media, terminal ";
			$sql.= "WHERE media.id = alarmgroupmap.mediaid AND alarmgroupmap.firealarmgroupid = alarmarea.id AND alarmgroupmap.alarmterminalid = terminal.id ";
			$sql.= "AND terminal.terminalname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY alarmgroupmap.alarmchannel DESC ";
		}
	}
	else
	{

		if($_SESSION['username']=="admin")
		{
			$sql = "SELECT alarmgroupmap.id, alarmgroupmap.firealarmgroupid,alarmgroupmap.info,alarmgroupmap.alarmterminalid,terminal.terminalname,alarmgroupmap.alarmchannel, alarmarea.name as  alarmname, media.name  ";
			$sql.= "FROM alarmgroupmap, alarmarea, media, terminal ";
			$sql.= "WHERE media.id = alarmgroupmap.mediaid AND alarmgroupmap.firealarmgroupid = alarmarea.id AND alarmgroupmap.alarmterminalid = terminal.id ";
			$sql.= "ORDER BY alarmgroupmap.alarmchannel ";
			
		}
	else
		{
			$sql = "SELECT alarmgroupmap.id, alarmgroupmap.firealarmgroupid,alarmgroupmap.info,alarmgroupmap.alarmterminalid,terminal.terminalname,alarmgroupmap.alarmchannel, alarmarea.name as  alarmname, media.name  ";
			$sql.= "FROM alarmgroupmap, alarmarea, media, terminal ";
			$sql.= "WHERE media.id = alarmgroupmap.mediaid AND alarmgroupmap.firealarmgroupid = alarmarea.id AND alarmgroupmap.alarmterminalid = terminal.id AND alarmarea.userid='$userid' ";
			$sql.= "ORDER BY alarmgroupmap.alarmchannel ";
		
		}
	}
	
	$result	=	mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
	
	$Num	=	mysqli_num_rows($result);
	
	$result =   mysqli_query($con,$sql."LIMIT $start,$NumOfPage")or die("Execute error".mysqli_error($con));
	$info=array();
	if(mysqli_error($con))
	{
		$_SESSION['info'] = "Execute error".mysqli_error($con);
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		while ($row = mysqli_fetch_array($result)) 
		{	
		$info[] = array(
							"id"=>$row['id'],"firealarmgroupid"=>$row['firealarmgroupid'],
							"info"=>$row['info'],"alarmterminalid"=>$row['alarmterminalid'],"terminalname"=>$row['terminalname'],
							"alarmchannel"=>$row['alarmchannel'],"alarmname"=>$row['alarmname'],"medianame"=>$row['name']
						);
		}
		
		$smarty->assign("info",$info);
		
	

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
		
		$smarty->display("alarmmanager/alarmmanager.html");
	}
}
?>