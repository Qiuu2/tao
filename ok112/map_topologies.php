<?php
if (!session_id()) session_start();
header("content-type:text/html; charset=utf-8");

require_once("inc/config.php");
require_once('inc/config.inc.php');
require_once('inc/smarty.inc.php');

//��֤�Ƿ�ʧЧ
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();

	//��ʾ������
require_once("language/".$_SESSION['language'].".php");
$smarty->assign("language",$_SESSION['language']);
$smarty->assign("server_manager",$server_manager);
if(empty($_SESSION['admin_id']))
{
	header("location:login.php");	
}
else
{
	$group_terminal=array();
	$smarty->assign("admin_id",$_SESSION['admin_id']);
			$sql_terminal = "SELECT id,terminalname,typeid FROM terminal WHERE longitude ='0' AND latitude='0'";
		
			$result_terminal = mysqli_query($con,$sql_terminal)or die("Execution error".mysqli_error($con));
			
			while($row_terminal = mysqli_fetch_array($result_terminal))
			{
				$group_terminal[] = array("id"=>$row_terminal['id'],"terminalname"=>$row_terminal['terminalname'],"typeid"=>$row_terminal['typeid'],"longitude"=>$row_terminal['longitude'],"latitude"=>$row_terminal['latitude']);
				
			}
			
		$smarty->assign("group_terminal",$group_terminal);
	
	

		$sql_info = "SELECT dealerinfo FROM serverbaseparam ";
		
		$result_info = mysqli_query($con,$sql_info)or die("Execution error".mysqli_error($con));
		
		if($row_info = mysqli_fetch_array($result_info))
		{
			$text_info = $row_info['dealerinfo'];
			
		}
		
	$smarty->assign("text_info",$text_info);

	//ֻ�й���Ա�͹���Ա�����޸���
	$group_info=array();
		$sql_group = "SELECT id,terminalname,typeid,longitude,latitude,netstate,ip FROM terminal WHERE longitude!='0' AND latitude!='0'";
	
		$result_group = mysqli_query($con,$sql_group)or die("Execution error".mysqli_error($con));
		
		while($row_group = mysqli_fetch_array($result_group))
		{
			$group_info[] = array("id"=>$row_group['id'],"terminalname"=>$row_group['terminalname'],"typeid"=>$row_group['typeid'],"longitude"=>$row_group['longitude'],"latitude"=>$row_group['latitude'],"netstate"=>$row_group['netstate'],"ip"=>$row_group['ip']);
			
		}
	
		$smarty->assign("longitude",$longitude);
		$smarty->assign("latitude",$latitude);
		$smarty->assign("group_info",$group_info);
		$smarty->assign("group_info_count",count($group_info));
	
		unset($row_group,$sql_group);
		
	if($_SESSION['language']=='chinese')
		$smarty->display("TerminalManager/displaymap.html");
	elseif($_SESSION['language']=='english')
		$smarty->display("TerminalManager/displaymapen.html");
}
?>