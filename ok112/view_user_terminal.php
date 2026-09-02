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
	$smarty->assign("user_terminal",$user_terminal);
	
	$smarty->assign("Filetaskmanager",$Filetaskmanager);
	$smarty->assign("Searchform",$Searchform);
	$smarty->assign("Revise",$Revise);
	//获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(have_rights("userpriv") || is_admin($con,$_SESSION['username']))
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
	
	$user_id = "";
	if(isset($_GET['id']))
	{
		$user_id = trim($_GET['id']);
		$_SESSION['tran_mid_value'] = $user_id;
	}
	if($user_id == "")
	{
		$user_id = $_SESSION['tran_mid_value'];
	}

	$group_result = mysqli_query($con,"SELECT usergroupid FROM book_admin WHERE book_admin.id='$user_id'") or die(mysqli_error($con));
	if($group_row = mysqli_fetch_array($group_result))
	{
		$group_id = $group_row['usergroupid'];
	}
	@mysqli_free_result($group_result);
	unset($group_row);
	
	if($group_id == 1)
	{
		$terminal_sql = "SELECT id,groupid,terminalname,typeid,netstate,devicestate,taskstate,ip FROM terminal ";
		
		if( !empty($_GET['searchvalue']) )
		{
			if( !empty($_GET['searchkey']) )
			{
				if( trim($_GET['searchkey'])=="terminalname" )
				{
					if( !empty($_GET['searchsequence']) )
					{
						$terminal_sql = "SELECT id,groupid,terminalname,typeid,netstate,devicestate,taskstate,ip FROM terminal ";
						$terminal_sql.= " where terminal.terminalname like '%".trim($_GET['searchvalue'])."%' order by id desc ";
					}
					else
					{
						$terminal_sql = "SELECT id,groupid,terminalname,typeid,netstate,devicestate,taskstate,ip FROM terminal ";
						$terminal_sql.= " where terminal.terminalname like '%".trim($_GET['searchvalue'])."%' order by id ";
					}
				}
			}
			else
			{	
				if( !empty($_GET['searchsequence']) )
				{
					$terminal_sql = "SELECT id,groupid,terminalname,typeid,netstate,devicestate,taskstate,ip FROM terminal ";
					$terminal_sql.= " where terminal.terminalname like '%".trim($_GET['searchvalue'])."%' order by id desc ";
				}
				else
				{
					$terminal_sql = "SELECT id,groupid,terminalname,typeid,netstate,devicestate,taskstate,ip FROM terminal ";
					$terminal_sql.= " where terminal.terminalname like '%".trim($_GET['searchvalue'])."%' order by id ";
				}
			}
		}
	}
	else
	{
		$terminal_sql = "SELECT id,groupid,terminalname,typeid,netstate,devicestate,taskstate,ip FROM terminal ";
		$terminal_sql.= "WHERE terminal.id IN (SELECT terminalid FROM userterminal WHERE userid='$user_id') ORDER BY CONVERT(terminal.terminalname USING utf8)";
		if( !empty($_GET['searchvalue']) )
		{
			if( !empty($_GET['searchkey']) )
			{
				if( trim($_GET['searchkey'])=="terminalname" )
				{
					if( !empty($_GET['searchsequence']) )
					{
						$terminal_sql = "SELECT id,groupid,terminalname,typeid,netstate,devicestate,taskstate,ip FROM terminal ";
						$terminal_sql.= "WHERE terminal.terminalname like '%".trim($_GET['searchvalue'])."%' and ";
						$terminal_sql.= "terminal.id IN (SELECT terminalid FROM userterminal WHERE userid='$user_id') order by id desc ";
					}
					else
					{
						$terminal_sql = "SELECT id,groupid,terminalname,typeid,netstate,devicestate,taskstate,ip FROM terminal ";
						$terminal_sql.= "WHERE terminal.terminalname like '%".trim($_GET['searchvalue'])."%' and ";
						$terminal_sql.= "terminal.id IN (SELECT terminalid FROM userterminal WHERE userid='$user_id') order by id ";
					}
				}
			}
			else
			{	
				if( !empty($_GET['searchsequence']) )
				{
					$terminal_sql = "SELECT id,groupid,terminalname,typeid,netstate,devicestate,taskstate,ip FROM terminal ";
					$terminal_sql.= "WHERE terminal.terminalname like '%".trim($_GET['searchvalue'])."%' and ";
					$terminal_sql.= "terminal.id IN (SELECT terminalid FROM userterminal WHERE userid='$user_id') order by id desc ";
				}
				else
				{
					$terminal_sql = "SELECT id,groupid,terminalname,typeid,netstate,devicestate,taskstate,ip FROM terminal ";
					$terminal_sql.= "WHERE terminal.terminalname like '%".trim($_GET['searchvalue'])."%' and ";
					$terminal_sql.= "terminal.id IN (SELECT terminalid FROM userterminal WHERE userid='$user_id') order by id ";
				}
			}
		}
	}
	
	if(!isset($_SESSION['admin_id']))
	{
		$result	= mysqli_query($con,$terminal_sql) or die("Execute error".mysqli_error($con));
		$Num	= mysqli_num_rows($result);
		$result = mysqli_query($con,$terminal_sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
	}
	else
	{
		$result	= mysqli_query($con,$terminal_sql) or die("Execute error".mysqli_error($con));
		$Num	= mysqli_num_rows($result);
		$result = mysqli_query($con,$terminal_sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
	}
	
	while ($row = mysqli_fetch_array($result)) 
	{
		$terminal_info[] = array(
									"id"=>$row['id'],
									"groupid"=>$row['groupid'],
									"terminalname"=>$row['terminalname'],
									"typeid"=>$row['typeid'],
									"netstate"=>$row['netstate'],
									"devicestate"=>$row['devicestate'],
									"taskstate"=>$row['taskstate'],
									"ip"=>$row['ip'],
								);
	}
	$smarty->assign("info",$terminal_info);
	
	@mysqli_free_result($result);
	unset($terminal_sql,$row,$terminal_info);
	//分区
	$stream_result = mysqli_query($con,"select streamid, serverplaystream.name from serverplaystream") or die(mysqli_error($con));
	while($stream_row = mysqli_fetch_array($stream_result))
	{
		$stream_info[] = array("streamid"=>$stream_row['streamid'],"name"=>$stream_row['name']);
	}
	$smarty->assign("stream_info",$stream_info);
	
	@mysqli_free_result($stream_result);
	unset($stream_row,$stream_info);
	
	//报警分区(是否要确定终端)
	
	//终端类型
	$terminaltype_result = mysqli_query($con,"SELECT id,terminaltype.name FROM terminaltype") or die(mysqli_error($con));
	while($terminaltype_row = mysqli_fetch_array($terminaltype_result))
	{
		$type_info[] = array("id"=>$terminaltype_row['id'],"name"=>$terminaltype_row['name']);
	}
	$smarty->assign("type_info",$type_info);
	
	@mysqli_free_result($terminaltype_result);
	unset($terminaltype_row,$type_info);

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

	$smarty->assign("user_id",$user_id);
	$smarty->assign("group_id",$group_id);
	$smarty->assign("start",$start);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("UserManager/view_user_terminal.html");
}
?>
