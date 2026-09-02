<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');
require_once('inc/common.php');
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

$smarty->assign("terminal_manager",$terminal_manager);
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
	$smarty->assign("Termianl_manager_Shortcut",$Termianl_manager_Shortcut);
	$smarty->assign("Termianl_manager_Change_volume",$Termianl_manager_Change_volume);
	$smarty->assign("Termianl_manager_Confirm_change",$Termianl_manager_Confirm_change);
	
	$smarty->assign("Termianl_manager_shortcuthave",$Termianl_manager_shortcuthave);
	$smarty->assign("Termianl_manager_shortcutno",$Termianl_manager_shortcutno);
	
	$smarty->assign("Termianl_manager_Normal",$Termianl_manager_Normal);
	$smarty->assign("Termianl_manager_Suspend",$Termianl_manager_Suspend);
	$smarty->assign("Termianl_manager_Link",$Termianl_manager_Link);
	
	$smarty->assign("Termianl_manager_Disconnect",$Termianl_manager_Disconnect);
	$smarty->assign("Termianl_manager_Stop",$Termianl_manager_Stop);
	$smarty->assign("Termianl_manager_Record",$Termianl_manager_Record);	
	$smarty->assign("Termianl_manager_NInstancy",$Termianl_manager_NInstancy);
	$smarty->assign("Termianl_manager_SInstancy",$Termianl_manager_SInstancy);	
	$smarty->assign("Termianl_manager_SRecord",$Termianl_manager_SRecord);	
	$smarty->assign("Termianl_manager_TRecord",$Termianl_manager_TRecord);	
	$smarty->assign("Termianl_manager_Select_Options",$Termianl_manager_Select_Options);
	$smarty->assign("Termianl_manager_OK_delete",$Termianl_manager_OK_delete);
	$smarty->assign("Searchform",$Searchform);
	//ȡȨ
	require('editor.php');
	
	$folder_id = 0;
	
	if(isset($_GET['folder_id']))
	{
		$folder_id = trim($_GET['folder_id']);
	}

	$smarty->assign("folder_id",$folder_id);
	
	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
		$_SESSION['terminal'] = $terminal_id;
	}
	
	if($terminal_id == "")
	{
		$terminal_id = $_SESSION['terminal'];
	}
	$smarty->assign("terminal_id",$terminal_id);
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
	//======显示报警终端以外所有终端=====
	//
	
	$type=get_terminal_type(15,$do_php_prompt['Terminal_not_support'],0,0);
	$sql_terminal="";
	if(trim($_GET['searchvalue'])!="")
	{
		if(trim($_GET['searchkey'])!="" && trim($_GET['searchsequence'])!="")
		{
			if(trim($_GET['searchkey'])=="terminalname")
			{
				$sql_terminal = "SELECT id,groupid,isselectcall,terminalname, typeid, netstate, devicestate, taskstate, ip, volume, isspeech ,instancy,isrecord,skaddress,skdevtype,totalcapacity,resetcapacity,issponsor,shortcircuit,lopencircuit,ropencircuit,temperature,humidity FROM terminal WHERE typeid in($type) AND terminal.terminalname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id ";
			}
			
			if(trim($_GET['searchkey'])=="ip")
			{
				$sql_terminal = "SELECT id,groupid,isselectcall,terminalname, typeid, netstate, devicestate, taskstate, ip, volume, isspeech ,instancy,isrecord,skaddress,skdevtype,totalcapacity,resetcapacity,issponsor,shortcircuit,lopencircuit,ropencircuit,temperature,humidity FROM terminal WHERE typeid in($type) AND terminal.ip LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id ";
			}
		}
		else if(!empty($_GET['searchkey']) && empty($_GET['searchsequence']))
		{
			if(trim($_GET['searchkey'])=="terminalname")
			{
				$sql_terminal = "SELECT id,groupid,isselectcall,terminalname, typeid, netstate, devicestate, taskstate, ip, volume, isspeech ,instancy,isrecord,skaddress,skdevtype,totalcapacity,resetcapacity,issponsor,shortcircuit,lopencircuit,ropencircuit,temperature,humidity FROM terminal WHERE typeid in($type) AND terminal.terminalname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id DESC ";
			}
			
			if(trim($_GET['searchkey'])=="ip")
			{
				$sql_terminal = "SELECT id,groupid,isselectcall,terminalname, typeid, netstate, devicestate, taskstate, ip, volume, isspeech ,instancy,isrecord,skaddress,skdevtype,totalcapacity,resetcapacity,issponsor,shortcircuit,lopencircuit,ropencircuit,temperature,humidity FROM terminal WHERE typeid in($type) AND terminal.ip LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id DESC ";
			}
		}
		else
		{
			$sql_terminal = "SELECT id,groupid,isselectcall,terminalname,typeid,netstate, devicestate, taskstate, ip, volume, isspeech ,instancy,isrecord,skaddress,skdevtype,totalcapacity,resetcapacity,issponsor,shortcircuit,lopencircuit,ropencircuit,temperature,humidity FROM terminal WHERE typeid in($type) AND terminal.terminalname LIKE '%".trim($_GET['searchvalue'])."%' ORDER BY id DESC ";
		}
	}
	else
	{
		$sql_terminal = "SELECT id,groupid, terminalname,isselectcall,typeid, netstate, devicestate, taskstate, ip, volume, isspeech ,instancy,isrecord,skaddress,skdevtype,totalcapacity,resetcapacity,issponsor,shortcircuit,lopencircuit,ropencircuit,temperature,humidity FROM terminal WHERE typeid in($type) ORDER BY netstate ASC ";
	}
	
	$result_terminal	=	mysqli_query($con,$sql_terminal) or die("Execute error".mysqli_error($con));
	$Num =	mysqli_num_rows($result_terminal);
	//$result_terminal =   mysqli_query($con,$sql_terminal."LIMIT $start,$NumOfPage")or die("Execute error".mysqli_error($con));
	  //$fh=fopen('get_mp3_info.xml',"w");   
	while($row_terminal = mysqli_fetch_array($result_terminal)) 
	{	
		$terminal_info[] = array("id"=>$row_terminal['id'],"groupid"=>$row_terminal['groupid'],"terminalname"=>$row_terminal['terminalname'],"typeid"=>$row_terminal['typeid'],"netstate"=>$row_terminal['netstate'],"devicestate"=>$row_terminal['devicestate'],"taskstate"=>$row_terminal['taskstate'],"ip"=>$row_terminal['ip'],"volume"=>$row_terminal['volume'],"isspeech"=>$row_terminal['isspeech'],"instancy"=>$row_terminal['instancy'],"isrecord"=>$row_terminal['isrecord'],"isselectcall"=>$row_terminal['isselectcall'],"skaddress"=>$row_terminal['skaddress'],"skdevtype"=>$row_terminal['skdevtype'],"totalcapacity"=>$row_terminal['totalcapacity'],"resetcapacity"=>$row_terminal['resetcapacity'],"issponsor"=>$row_terminal['issponsor'],"shortcircuit"=>$row_terminal['shortcircuit'],"lopencircuit"=>$row_terminal['lopencircuit'],"ropencircuit"=>$row_terminal['ropencircuit'],"temperature"=>$row_terminal['temperature'],"humidity"=>$row_terminal['humidity']);
	  //$instancy1 = $row_terminal['ip']."=".$row_type['instancy']."\n";
	  //fwrite($fh,$instancy1); 
	}
	//fclose($fh);
	$smarty->assign("terminal_info",$terminal_info);
	
	@mysqli_free_result($result_terminal);
	unset($row_terminal,$sql_terminal,$terminal_info);
	

	//分页
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
	//用户与终端绑定
	$smarty->assign("pagestr",$Num);

	
	$smarty->assign("chinese_big5_english",$_SESSION['language']);	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("dirstreammanager/dir_area_add.html");
}
?>
