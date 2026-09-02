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
	
	$smarty->assign("stream_manager",$stream_manager);
	
	$smarty->assign("Streammanager",$Streammanager);
	
	$smarty->assign("Searchform",$Searchform);
	
	$smarty->assign("Revise",$Revise);
	//获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(have_rights("terminalgrouppriv") || is_admin($con,$_SESSION['username']))
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
	//实现查询
	$userid=$_SESSION['userid'];
	
	$sql="";
	if(trim($_GET['keyvalue'])!="")
	{
		if(!empty($_GET['searchkey']) && !empty($_GET['orderby']))
		{
			$sql="SELECT * FROM serverplaystream WHERE serverplaystream.name LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  ".$_GET['orderby']." DESC ";
		}
		else if(!empty($_GET['searchkey']) && empty($_GET['orderby']))
		{
			$sql="SELECT * FROM serverplaystream WHERE serverplaystream.name LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  serverplaystream.streamid DESC ";
		}
		else if(empty($_GET['searchkey']) && !empty($_GET['orderby']))
		{
			$sql="SELECT * FROM serverplaystream WHERE serverplaystream.name LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  ".$_GET['orderby']." DESC ";
		}
		else
		{
			$sql="SELECT * FROM serverplaystream WHERE serverplaystream.name LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  serverplaystream.streamid DESC ";
		}
	}
	else
	{
	
		if($_SESSION['username']=="admin")
		$sql="SELECT * FROM serverplaystream WHERE streamid ORDER BY serverplaystream.streamid ASC ";
		else
		$sql="SELECT * FROM serverplaystream WHERE userid ='$userid' ORDER BY serverplaystream.streamid ASC ";
	}
	$result	=	mysqli_query($con,$sql);
	$Num	=	mysqli_num_rows($result);
	//$result = 	mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	$info=array();
	$array1=array();
	while ($row = mysqli_fetch_array($result)) 
	{

		$streamid = $row['streamid'];
		
		$result1	=	mysqli_query($con,"SELECT terminalname,ip,postion FROM `terminal` where groupid = $streamid ");
		while ($row1 = mysqli_fetch_array($result1)) 
		{
			$array1[]	=	 array("terminalname"=>$row1['terminalname'],"ip"=>$row1['ip'], "postion"=>$row1['postion']);
		}
			
		$info[]	=	 array(
									"streamid"=>$row['streamid'],"name"=>$row['name'],"streamname"=>$row['streamname'],
									"info"=>$row['info'],
									"createtime"=>$row['createtime'],"terminal"=>$array1
								);
								
						
		unset($array1);
		mysqli_free_result($result1);
		unset($array,$array1,$row1,$row,$sql);
	}
	$smarty->assign("info", $info);	

    mysqli_free_result($result);
	//分页
	/*
	if($Num != 0){
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
	$smarty->display("StreamManager/streammanager.html");
}
?>