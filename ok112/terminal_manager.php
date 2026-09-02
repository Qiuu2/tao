<?php
if (!session_id()) session_start();
header("content-type:text/html;charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
//��֤�Ƿ�ʧЧ
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	header("location:login.php");
}
else
{
	//���ڶ�����
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("terminal_manager",$terminal_manager);

	/*��̬��ʾҳ���ı�*/
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
	$smarty->assign("Termianl_manager_Shortcut",$Termianl_manager_Shortcut);
	$smarty->assign("Termianl_manager_Change_volume",$Termianl_manager_Change_volume);
	$smarty->assign("Termianl_manager_Confirm_change",$Termianl_manager_Confirm_change);
	
	$smarty->assign("Termianl_manager_shortcuthave",$Termianl_manager_shortcuthave);
	$smarty->assign("Termianl_manager_shortcutno",$Termianl_manager_shortcutno);
	
	$smarty->assign("Termianl_manager_Normal",$Termianl_manager_Normal);
	$smarty->assign("Termianl_manager_Suspend",$Termianl_manager_Suspend);
	$smarty->assign("Termianl_manager_Link",$Termianl_manager_Link);
	$smarty->assign("Termianl_manager_Run",$Termianl_manager_Run);
	$smarty->assign("Termianl_manager_Disconnect",$Termianl_manager_Disconnect);
	$smarty->assign("Termianl_manager_Stop",$Termianl_manager_Stop);
	
	$smarty->assign("Termianl_manager_NInstancy",$Termianl_manager_NInstancy);
	$smarty->assign("Termianl_manager_SInstancy",$Termianl_manager_SInstancy);	
	
	$smarty->assign("Termianl_manager_Select_Options",$Termianl_manager_Select_Options);
	$smarty->assign("Termianl_manager_OK_delete",$Termianl_manager_OK_delete);
	$smarty->assign("Searchform",$Searchform);
	//��ȡȨ��
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(have_rights("terminalpriv") || is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	//��ȡ��ҳ��
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
	//======��ʾ�����ն����������ն�=====
	//
	$sql_terminal="";
	if(trim($_GET['searchvalue'])!="")
	{
		if(trim($_GET['searchkey'])!="" && trim($_GET['searchsequence'])!="")
		{
			if(trim($_GET['searchkey'])=="terminalname")
			{
				$sql_terminal = "SELECT id,groupid, terminalname, typeid, netstate, devicestate, taskstate, ip, volume, isspeech ,instancy";
				$sql_terminal .= "FROM terminal WHERE terminal.terminalname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id ";
			}
			
			if(trim($_GET['searchkey'])=="ip")
			{
				$sql_terminal = "SELECT id,groupid, terminalname, typeid, netstate, devicestate, taskstate, ip, volume, isspeech ,instancy ";
				$sql_terminal .= "FROM terminal WHERE terminal.ip LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id ";
			}
			
		}
		else if(!empty($_GET['searchkey']) && empty($_GET['searchsequence']))
		{
			if(trim($_GET['searchkey'])=="terminalname")
			{
				$sql_terminal = "SELECT id,groupid, terminalname, typeid, netstate, devicestate, taskstate, ip, volume, isspeech ,instancy";
				$sql_terminal .= "FROM terminal WHERE terminal.terminalname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id DESC ";
			}
			
			if(trim($_GET['searchkey'])=="ip")
			{
				$sql_terminal = "SELECT id,groupid, terminalname, typeid, netstate, devicestate, taskstate, ip, volume, isspeech ,instancy";
				$sql_terminal .= "FROM terminal WHERE terminal.ip LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id DESC ";
			}
		}
		else
		{
			$sql_terminal = "SELECT id,groupid, terminalname, typeid, netstate, devicestate, taskstate, ip, volume, isspeech ,instancy";
			$sql_terminal .= "FROM terminal WHERE terminal.terminalname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id DESC ";
		}
	}
	else if((trim($_GET['searchvalue'])=="") && empty($_GET['order']))
	{
		$sql_terminal = "SELECT id,groupid, terminalname, typeid, netstate, devicestate, taskstate, ip, volume, isspeech ,instancy FROM terminal ORDER BY id ";
	}
	else if((trim($_GET['searchvalue'])=="") && ($_GET['order']==1))
	{
	$sql_terminal = "SELECT id,groupid, terminalname, typeid, netstate, devicestate, taskstate, ip, volume, isspeech ,instancy FROM terminal ORDER BY terminalname ASC ";
	}
	else if((trim($_GET['searchvalue'])=="") && ($_GET['order']==3))
	{
	$sql_terminal = "SELECT id,groupid, terminalname, typeid, netstate, devicestate, taskstate, ip, volume, isspeech ,instancy FROM terminal ORDER BY IP ASC ";
	}
	
	$result_terminal	=	mysqli_query($con,$sql_terminal) or die("Execute error".mysqli_error($con));

	  //$fh=fopen('get_mp3_info.xml',"w");   
	while($row_terminal = mysqli_fetch_array($result_terminal)) 
	{	
		
		$terminal_info[] = array("id"=>$row_terminal['id'],"groupid"=>$row_terminal['groupid'],"terminalname"=>$row_terminal['terminalname'],"typeid"=>$row_terminal['typeid'],"netstate"=>$row_terminal['netstate'],"devicestate"=>$row_terminal['devicestate'],"taskstate"=>$row_terminal['taskstate'],"ip"=>$row_terminal['ip'],"volume"=>$row_terminal['volume'],"isspeech"=>$row_terminal['isspeech'],"instancy"=>$row_terminal['instancy']);
	  //$instancy1 = $row_terminal['ip']."=".$row_type['instancy']."\n";
	  //fwrite($fh,$instancy1); 
	}
	//fclose($fh);
	$smarty->assign("terminal_info",$terminal_info);
	
	@mysqli_free_result($result_terminal);
	unset($row_terminal,$sql_terminal,$terminal_info);
	
	//=====��������������Ϣ=====//
	$sql_stream = "SELECT streamid, name FROM serverplaystream ";
	$result_stream = mysqli_query($con,$sql_stream) or die(mysqli_error($con));
	while($row_stream = mysqli_fetch_array($result_stream))
	{
		$stream_info[] = array("id"=>$row_stream['streamid'],"name"=>$row_stream['name']);
	}
	
	$smarty->assign("stream_info",$stream_info);
	@mysqli_free_result($result_stream);
	unset($row_stream,$sql_stream,$stream_info);
	//=====���������ն�������Ϣ=====//
	$sql_type = "SELECT id, name, isdecode, isencode FROM terminaltype ";
	$result_type = mysqli_query($con,$sql_type) or die(mysqli_error($con));
	while($row_type = mysqli_fetch_array($result_type))
	{
		$type_info[] = array("id"=>$row_type['id'],"name"=>$row_type['name'],"isdecode"=>$row_type['isdecode'],"isencode"=>$row_type['isencode']);
	}
	
	$smarty->assign("type_info",$type_info);
	@mysqli_free_result($result_type);
	unset($row_type,$sql_type,$type_info);
	
	
	$get_terminalsql="SELECT terminalid FROM terminalkey ";
	$get_terminalsqls = mysqli_query($con,$get_terminalsql) or die(mysqli_error($con));
	while($get_terminals = mysqli_fetch_array($get_terminalsqls))
	{
		$get_terminal[] = array("terminalid"=>$get_terminals['terminalid']);
	}
	
	$smarty->assign("get_terminal",$get_terminal);
	@mysqli_free_result($get_terminalsqls);
	unset($get_terminals,$get_terminalsql,$get_terminal);
	//��ҳ

	require_once("User_Rights_Manage/verify_user_rights_class.php");
	$user_rights = get_login_user_rights($_SESSION['username'],$con);
	$user_terminal = get_user_of_terminal($_SESSION['username'],$con);
	
	
	$smarty->assign("user_rights",$user_rights);
	$smarty->assign("user_terminal",$user_terminal);
	
	unset($user_rights,$user_terminal);
	
	$smarty->assign("chinese_big5_english",$_SESSION['language']);
	
	$smarty->assign("start",$start);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("TerminalManager/terminal_manager.html");
}
?>