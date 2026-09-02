<?php
if (!session_id()) session_start();
header("content-type:text/html;charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
if(empty($_SESSION['admin_id']))
{
	//require_once('login.php');
	header("location:login.php");
}
else
{
	require_once("verify_user_sessionin_valid.php");
	verifysessionvalid();
	require_once("language/".$_SESSION['language'].".php");	
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("terminal_manager",$terminal_manager);

	//获取权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(have_rights("terminalpriv") || is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	//获取分页类
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
	
	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
		
		$_SESSION['tran_mid_value'] = $terminal_id;
	}
	
	if($terminal_id == "")
	{
		$terminal_id = $_SESSION['tran_mid_value'];
	}

	$ledflag = "";
	if(isset($_GET['ledflag']))
	{
		$ledflag = trim($_GET['ledflag']);
		
		
	}

	//取源终端名称
	if($ledflag==2)
	{
		$shotcut_sql = "SELECT leddevice.id,leddevice.name,leddevice.ip,leddevice.width,leddevice.height,leddevice.subterminalid,leddevice.terminalid,defaulttext FROM leddevice ";
	}
	else
	{
		$shotcut_sql = "SELECT leddevice.id,leddevice.name,leddevice.ip,leddevice.width,leddevice.height,terminal.terminalname,leddevice.subterminalid,leddevice.terminalid,defaulttext FROM leddevice,terminal WHERE terminal.id=leddevice.terminalid and terminal.id='$terminal_id'";

	}
	$shotcut_result	=	mysqli_query($con,$shotcut_sql) or die("Execute error".mysqli_error($con));
	$Num	=	mysqli_num_rows($shotcut_result);
	$shotcut_result =   mysqli_query($con,$shotcut_sql."LIMIT $start,$NumOfPage")or die("Execute error".mysqli_error($con));
	$subterminalname="";
	$shotcut_info=array();
	while($shotcut_row = mysqli_fetch_array($shotcut_result))
	{
		$subterminalid=$shotcut_row['subterminalid'];
		$terminalid=$shotcut_row['terminalid'];
		$parantterminalname="";
		$sqlter = "SELECT id,terminalname FROM terminal WHERE id=	'$terminalid'";
		$resultster	=	mysqli_query($con,$sqlter);
		if($rowster = mysqli_fetch_array($resultster))
		{
			$parantterminalname=$rowster['terminalname'];
		}

		$subterminalname="";

		$sqls = "SELECT id,terminalname FROM terminal WHERE id=	'$subterminalid'";
		$results	=	mysqli_query($con,$sqls);
		if($rows = mysqli_fetch_array($results))
		{
			$subterminalname=$rows['terminalname'];
		}
		if($ledflag==2)
		$terminalname="";
		else
		$terminalname=$shotcut_row['terminalname'];


		$chezhaninfo = explode(",",$shotcut_row['defaulttext']);
		$shotcut_info[] = array(
									"id"=>$shotcut_row['id'],"ledname"=>$shotcut_row['name'],
									"ip"=>$shotcut_row['ip'],"ledwidth"=>$shotcut_row['width'],"ledheight"=>$shotcut_row['height'],"terminalname"=>$terminalname,"subterminalname"=>$subterminalname,"parantterminalname"=>$parantterminalname,
									"chezhannumber"=>$chezhaninfo[0],"checi"=>$chezhaninfo[1]);
	}

	$smarty->assign("terminal_info",$shotcut_info);
	//@mysqli_free_result($shotcut_row);
	unset($shotcut_sql,$shotcut_row);	

	$subterminal_info=array();
	$shotcut_sqls = "SELECT id,terminalname FROM terminal WHERE terminal.typeid NOT IN(0,26,2,7,8,9,10,12,15,16,17,18,21,22,25,28,29,30,31,32,36,37,40,41)";
	$shotcut_results	=	mysqli_query($con,$shotcut_sqls) or die("Execute error".mysqli_error($con));

	while($shotcut_rows = mysqli_fetch_array($shotcut_results))
	{
		$subterminal_info[] = array(
									"id"=>$shotcut_rows['id'],"terminalname"=>$shotcut_rows['terminalname']);
	}
	$smarty->assign("subterminal_info",$subterminal_info);

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
	$smarty->assign("gettype",$gettype);
	$smarty->assign("ledflag",$ledflag);
	$smarty->assign("start",$start);
	$smarty->assign("terminal_id",$terminal_id);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("TerminalManager/led_sousuo.html");
}
?>