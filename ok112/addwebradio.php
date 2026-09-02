<?php
if (!session_id()) session_start();

header("Content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');

//��֤�Ƿ�ʧЧ
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	header("location:login.php");
}
else
{		
	//��ʾ������
	require_once("language/".$_SESSION['language'].".php");
	
	$smarty->assign("language",$_SESSION['language']);
	
	$smarty->assign("collect_task_add",$collect_task_add);

	$smarty->assign("Belladdtask",$Belladdtask);
	//��ȡ����������
	$webradio_sql = "select id, netradiocount from serverbaseparam order by id desc ";
	$sql_result = mysqli_query($con,$webradio_sql) or die(mysqli_error($con));
	while($row_sql = mysqli_fetch_array($sql_result))
	{
	
	$array1[]	=	 array("id"=>$row_sql['id'],"netradiocount"=>$row_sql['netradiocount']);
	}
	$smarty->assign("cmdargs",$array1);
	
   
	unset($array1);
	
	
	//�ɲ��ն�
	$adm_terminal_sql = "select id, terminalname from terminal where terminal.typeid = '3'";
	
	$adm_terminal_result = mysqli_query($con,$adm_terminal_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($adm_terminal_result) > 0)
	{
		while($adm_terminal_row = mysqli_fetch_array($adm_terminal_result))
		{
			$terminal_info[] = array("id"=>$adm_terminal_row['id'],"terminalname"=>$adm_terminal_row['terminalname']);	
		}
		
		$smarty->assign("terminal_info",$terminal_info);
		
		@mysqli_free_result($adm_terminal_result);
		
		unset($adm_terminal_sql,$adm_terminal_row,$terminal_info);
	}
	else
	{
	/*	@mysqli_free_result($adm_terminal_result);
		
		echo "<script>alert('".$collect_task_add['no_collection_terminal_add']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;*/
	}
	//�ɲ��ն�����
	$adm_type_sql = "SELECT switchcount FROM terminaltype WHERE terminaltype.id = '3'";
	
	$adm_type_result = mysqli_query($con,$adm_type_sql) or die(mysqli_error($con));
	
	if($adm_type_row = mysqli_fetch_array($adm_type_result))
	{
		$smarty->assign("adm_channel",$adm_type_row['switchcount']);
	}
	
	@mysqli_free_result($adm_type_result);
	
	unset($adm_type_sql,$adm_type_row);
#if 0
////////////////////////////////////////////////////
	$str = "<?xml version='1.0' encoding='UTF-8'?> <tree id=\"0\">";
	
	
	$resultstream=	mysqli_query($con,"SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream");
	
	while ($rowstream = mysqli_fetch_array($resultstream))
	{			
		$streamid = $rowstream['streamid'];
	
		$str = "<item text=\"".$rowstream['name']."\" id=\"stream_".$streamid."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >";
	
	
		$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.groupid=$streamid");
	
		while ($rowterminal = mysqli_fetch_array($resultterminal)) 
		{	
			$str = "<item text=\"".$rowterminal['terminalname']."\" id=\""."$rowterminal[id]"."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >\n</item>\n";
			  
		}							 		
	}
	
			
	
#endif

 // $type =  "(1,3,4)";

  $type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	
  
  $terminalist = create_tree_str($type);
 $userid=$_SESSION['userid'];
$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
	if($row = mysqli_fetch_array($results))
	{
		$getlevel=$row['level'];
	}
	$smarty->assign("getlevel",$getlevel);
  $smarty->assign("terminalist",$terminalist);
  
  $audiosourcelist = get_audiosource();
  
  $smarty->assign("audiosourcelist",$audiosourcelist);

  $smarty->assign("admin_id",$_SESSION['admin_id']);
  
  $smarty->display("WebRadio/addwebradio.html");
}
?>
