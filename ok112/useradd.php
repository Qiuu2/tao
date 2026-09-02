<?php
if (!session_id()) session_start();
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require_once("inc/config.php");
//验证是否失效
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();
$smarty->assign("registerflag",$_SESSION['registerflag']);
if(empty($_SESSION['admin_id']))
{
	require_once('login.php');	
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("user_add",$user_add);

	$smarty->assign("Useradd",$Useradd);
	//获取终端
	require_once("inc/common.php");
		$type=get_terminal_type(7,$do_php_prompt['Terminal_not_support'],0,0);
	//	$type="(".$type.")";

	$terminal_array_id = get_terminallist("$type", 0);
	
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
			
	if(!isset($_SESSION['admin_id']))
	{
		$result	=	mysqli_query($con,"SELECT * FROM `".$DB_PREFIX."admin`  order by id desc");
		$result = mysqli_query($con,"SELECT * FROM `".$DB_PREFIX."admin`  ORDER BY id DESC LIMIT $start,$NumOfPage");
	}else{
		$result	=	mysqli_query($con,"SELECT * FROM `".$DB_PREFIX."admin`");
		$result = mysqli_query($con,"SELECT * FROM `".$DB_PREFIX."admin` ORDER BY id DESC LIMIT $start,$NumOfPage");
	}
	$info=array();
	$Num	=	mysqli_num_rows($result);
	while ($row = mysqli_fetch_array($result)) {
		$groupname ="";	
		$usergroupid=$row['usergroupid'];	
		$result2	=	mysqli_query($con,"SELECT name FROM `usergroup` WHERE id = $usergroupid order by id desc");
		if($row2 = mysqli_fetch_array($result2))
		{	
				$groupname = $row2['name'];
		}
		
		$info[]=	array("id"=>$row['id'],"username"=>$row['username'],"userpwd"=>$row['userpwd'],"usergroupid"=>$row['usergroupid'], "groupname"=>$groupname);
	}
	$smarty->assign("info",$info);
	unset($array);
	{
	   $user_id=$_SESSION['userid'];
	  if($_SESSION['admin_id']=="administrator")
		$sql="SELECT id,name FROM `usergroup` order by id desc";
	  else
	  	$sql= "SELECT id,name FROM `usergroup` where id IN(select usergroupid from book_admin where id='$user_id') order by id desc";
		$result1	=	mysqli_query($con,$sql);
		$group=array();
		while ($row1 = mysqli_fetch_array($result1)) {
			
			$group[]	=	 array("id"=>$row1['id'],"name"=>$row1['name']);
		}
		
		$smarty->assign("group",$group);
		unset($group);
	}
	$adm_type_sql = "SELECT ctrlterminalcount FROM serverbaseparam ";
	
	$adm_type_result = mysqli_query($con,$adm_type_sql) or die(mysqli_error($con));
	
	if($adm_type_row = mysqli_fetch_array($adm_type_result))
	{
		$smarty->assign("ctrlterminalcount",$adm_type_row['ctrlterminalcount']);
	}
	@mysqli_free_result($adm_type_result);
	unset($adm_type_sql,$adm_type_row);
	
	$adm_type_sql = "SELECT ctrlwind,subwind,camerawind FROM book_admin";
	$adm_type_result = mysqli_query($con,$adm_type_sql) or die(mysqli_error($con));
	$ctrlwind=array();
	$subwind=array();
	$camerawind=array();
	while($adm_type_row = mysqli_fetch_array($adm_type_result))
	{
		$ctrlwind[]	=	 array("ctrlwind"=>$adm_type_row['ctrlwind']);
		$subwind[]	=	 array("subwind"=>$adm_type_row['subwind']-1000);
		$camerawind[]	=	 array("camerawind"=>$adm_type_row['camerawind']-2000);
	}

	@mysqli_free_result($adm_type_result);
	unset($adm_type_sql,$adm_type_row);	
	
	$smarty->assign("ctrlwind",$ctrlwind);
	$smarty->assign("subwind",$subwind);
	$smarty->assign("camerawind",$camerawind);
	//状态分页
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
	$smarty->assign("FUZA_PASS",$FUZA_PASS);
	//输出session
	$smarty->assign("terminal_array_id",$terminal_array_id );

	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("UserManager/useradd.html");
}
?>