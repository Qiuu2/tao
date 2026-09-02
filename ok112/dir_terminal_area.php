<?php
if (!session_id()) session_start();

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

//֤ǷʧЧ
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

 if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE"))   
 {
	$IE =1;
	$smarty->assign("IE",$IE);
}
 else if(strpos($_SERVER["HTTP_USER_AGENT"],"Firefox"))   
{
	$IE =1;
	$smarty->assign("IE",$IE);
}
  else
  {
  	$IE =0; 
	$smarty->assign("IE",$IE);
  }
if(empty($_SESSION['admin_id']))
{
	//require_once('login.php');
	header("location:login.php");	
}
else
{	
	/*ʾ*/
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
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	
	if(have_rights("mediapriv") || is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
		
	}
	else
	{
		$smarty->assign("is_right",0);
		
	}

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
	
	
	require('editor.php');
	
	$smarty->assign('descriptionarea',$descriptionarea);

	$page=$_GET['page'];
	
	$start=($_GET['page']-1)*$NumOfPage;
	//ȡǰҳ
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
	//ȡļýļ
	
	$get_folder_id = 0;//ĬϹ
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
		
		$_SESSION['tran_mid_value'] = $get_folder_id;
	}
	else
	{
		if(empty($_GET['id']) )
		{
			$_SESSION['tran_mid_value'] = 0;
		}
		$get_folder_id = $_SESSION['tran_mid_value'];
	}
	
	//$host = isset($_SERVER['HTTP_X_FORWARDED_HOST'])?$_SERVER['HTTP_X_FORWARDED_HOST']:(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'');
	$sql = "";
	
	if(!empty($_POST['searchvalue']))
	{
		if((!empty($_POST['searchkey'])) && (!empty($_POST['orderby'])))
		{
			if($_POST['searchkey'] == "name")
			{
				$sql = "SELECT 	terminal.id, terminal.terminalname, typeid, ip , volume , netstate , taskstate FROM terminal,terminaloffolder ";
				$sql.= "WHERE terminaloffolder.folderid = '$get_folder_id' AND terminaloffolder.terminalid =terminal.id AND terminal.terminalname LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY terminalname DESC ";
			}
			else if($_POST['searchkey'] == "typeid")
			{
				$sql = "SELECT 	terminal.id, terminal.terminalname, typeid, ip , volume , netstate , taskstate FROM terminal,terminaloffolder ";
				$sql.= "WHERE terminaloffolder.folderid = '$get_folder_id' AND terminaloffolder.terminalid =terminal.id AND terminal.typeid LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY terminalname DESC ";
			}
		}
		else if((!empty($_POST['searchkey'])) && empty($_POST['orderby']))
		{
			if($_POST['searchkey'] == "name")
			{
				$sql = "SELECT 	terminal.id, terminal.terminalname, typeid, ip , volume , netstate , taskstate FROM terminal,terminaloffolder ";
				$sql.= "WHERE terminaloffolder.folderid = '$get_folder_id' AND terminaloffolder.terminalid =terminal.id AND terminal.terminalname LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY terminalname DESC ";
			}
			else if($_POST['searchkey'] == "typeid")
			{
				$sql = "SELECT 	terminal.id, terminal.terminalname, typeid, ip , volume , netstate , taskstate FROM terminal,terminaloffolder ";
				$sql.= "WHERE terminaloffolder.folderid = '$get_folder_id' AND terminaloffolder.terminalid =terminal.id AND terminal.typeid LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY terminalname DESC ";
			}
		}
		else
		{
			$sql = "SELECT 	terminal.id, terminal.terminalname, typeid, ip , volume , netstate , taskstate FROM terminal,terminaloffolder ";
			$sql.= "WHERE terminaloffolder.folderid = '$get_folder_id' AND terminaloffolder.terminalid =terminal.id ORDER BY terminalname DESC ";
		}
	}
	else
	{
		$sql = "SELECT 	terminal.id, terminal.terminalname, typeid, ip , volume , netstate , taskstate FROM terminal,terminaloffolder ";
		$sql.= "WHERE terminaloffolder.folderid = '$get_folder_id' AND terminaloffolder.terminalid =terminal.id ORDER BY terminalname DESC ";
	}
	
	$result	=	mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
	
	$Num	=	mysqli_num_rows($result);
	
	$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));
$info=array();
	while ($row = mysqli_fetch_array($result)) 
	{	
		
		$info[] = array(
							"id"=>$row['id'],"terminalname"=>$row['terminalname'],"typeid"=>$row['typeid'],"ip"=>$row['ip'],"volume"=>$row['volume'],
							"netstate"=>$row['netstate'],"taskstate"=>$row['taskstate']
						);
	}
	
	$smarty->assign("info",$info);
	
	@mysqli_free_result($result);
	
	unset($row,$sql,$info);
$sign =1;
$userid=$_SESSION['userid'];
$getsql="SELECT parentid FROM filefolder WHERE (userid=$userid or userid=0) AND id=$get_folder_id";
		$results=mysqli_query($con,$getsql);
		while ($rows = mysqli_fetch_array($results)) 
		{
			if(($rows['parentid']==0)||($rows['parentid']==1)||($rows['parentid']==2)||($rows['parentid']==3)||($rows['parentid']==4)||($rows['parentid']==5))
		 {
		  
				$sign = 1;
		 }
		 else
		 {
		 $sign =0;
		  
		 }
  }
		
	$smarty->assign("sign",$sign);
	@mysqli_free_result($results);
	unset($rows);
	//ҳ
	if($Num != 0)
	{
		require_once("pagination.class.php");
		$p = new pagination;
		$p->Items($Num);
		$p->limit($NumOfPage);
		$p->target("?id=".$get_folder_id."&");
		$p->currentPage($_GET['page']);
		$p->adjacents(3);
		$smarty->assign("pagestr",$p->show());
	}
		$type=get_terminal_type(2,$do_php_prompt['Terminal_not_support'],0,0);
		$getresults = mysqli_query($con,"SELECT id,terminalname FROM terminal WHERE typeid IN($type) ORDER BY id asc ");
		while ($getrows = mysqli_fetch_array($getresults)) 
		{
			$gettreeid[]=array("id"=>$getrows['id'],"terminalname"=>$getrows['terminalname']);
		}
		$smarty->assign("gettreeid",$gettreeid);
	//=====读出所有终端类型信息=====//
	$sql_type = "SELECT id, name, isdecode, isencode,isspeech FROM terminaltype";
	$result_type = mysqli_query($con,$sql_type) or die(mysqli_error($con));
	while($row_type = mysqli_fetch_array($result_type))
	{
		$type_info[] = array("id"=>$row_type['id'],"name"=>$row_type['name'],"isdecode"=>$row_type['isdecode'],"isencode"=>$row_type['isencode'],"isspeech"=>$row_type['isspeech']);
	}
	$smarty->assign("type_info",$type_info);
	@mysqli_free_result($result_type);
	unset($row_type,$sql_type,$type_info);

	//session
	$smarty->assign("get_folder_id",$get_folder_id);
//	$smarty->assign("userinfoid",$userinfoid);
	$smarty->assign("start",$start);
	
	$smarty->assign("terminal_id",$terminal_id);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->assign("model",$_SESSION['servermodel']);
	$smarty->display("dirstreammanager/dirarea_terminal.html");
}
?>
