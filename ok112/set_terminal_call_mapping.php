<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

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

	$smarty->assign("set_shotcut",$set_shotcut);

	$smarty->assign("Setterminalkey",$Setterminalkey);
	//�ն�����
	$type_id = -1;
	//��ȡ�ն�id
	$get_terminal_id = "";
	
	if(isset($_GET['id']))
	{
		$get_terminal_id = trim($_GET['id']);
	}
	//�ж��ն����ͺ�
	$type_result = mysqli_query($con,"SELECT typeid FROM terminal WHERE terminal.id = '$get_terminal_id'");
	
	if( $type_row = mysqli_fetch_array($type_result) )
	{
		if($type_row['typeid'] == -1)
		{
			echo "<script>alert('".$set_shotcut['Unknown_terminal_t??ype']."');</script>";
			
			echo "<script>window.history.back();</script>";
			
			exit;
		}
		else
		{
			$type_id  = $type_row['typeid'];
		}
	}
	
	@mysqli_free_result($type_result);
	
	unset($type_row);
	
	//�ж��ն����͡���ȡ�ն˿�ݼ���
	$sql_type = "SELECT isdecode, isencode FROM terminaltype WHERE terminaltype.id = '$type_id'";	
	
	$result_type = mysqli_query($con,$sql_type) or die(mysqli_error($con));
	
	$row_type = mysqli_fetch_array($result_type);
	
	if($row_type['isencode'] == 1)//����
	{

		$sql_key = "SELECT name FROM audioserver.terminaltypekey WHERE terminaltypekey.terminaltype = '$type_id'";
	
		$result_key = mysqli_query($con,"$sql_key") or die(mysqli_error($con));
	
		while($row_key = mysqli_fetch_array($result_key))
		{
			$key_num[] = $row_key['name'];
		}
		
		$smarty->assign("key_num",$key_num);
		
		@mysqli_free_result($result_key);
		
		unset($sql_key,$row_key,$key_num);
		
		//��ȡ�����ն�
		$type =  "(2,3,4,5,14,16)";

		$decode_terminal = get_terminallist5($type, $get_terminal_id);
	}
	else if($row_type['isencode'] == 0)
	{
		//���ܱ��롢��������
		echo "<script>alert('".$set_shotcut['does_not_support_shortcut_paging']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
	}
	
	@mysqli_free_result($result_type);
	
	unset($sql_type,$row_type);
	
	$smarty->assign("decode_terminal",$decode_terminal);
	
	$smarty->assign("id",$get_terminal_id);
	
	unset($decode_terminal,$get_terminal_id);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("TerminalManager/set_callterminal.html");
}
?>