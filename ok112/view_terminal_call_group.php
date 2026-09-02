<?php
if (!session_id()) session_start();

require_once('inc/smarty.inc.php');
header("content-type:text/html; charset=utf-8");
require_once('inc/config.inc.php');
//��֤�Ƿ�ʧЧ
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	require_once('login.php');	
}
else
{
	
	require_once("language/".$_SESSION['language'].".php");
	
	$smarty->assign("language",$_SESSION['language']);
	
	$smarty->assign("stream_manager",$stream_manager);
	
	$smarty->assign("Streammanager",$Streammanager);
	
	$smarty->assign("Searchform",$Searchform);
	
	$smarty->assign("Revise",$Revise);

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
	//ʵ�ֲ�ѯ
	get_terminal_type(2,$do_php_prompt['Terminal_not_support'],$terminal_id,1);
	
	$flag = 1;
	
	if(isset($_GET['flag']))
	{
		$flag = trim($_GET['flag']);
	}
	
	$sql="";
	if(trim($_GET['keyvalue'])!="")
	{
		if(!empty($_GET['searchkey']) && !empty($_GET['orderby']))
		{
		
			$sql="SELECT * FROM callgroup where terminalid=$terminal_id  LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  ".$_GET['orderby']." DESC ";
		}
		else if(!empty($_GET['searchkey']) && empty($_GET['orderby']))
		{
			$sql="SELECT * FROM callgroup where terminalid=$terminal_id  LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  callgroup.id DESC ";
		}
		else if(empty($_GET['searchkey']) && !empty($_GET['orderby']))
		{
			$sql="SELECT * FROM callgroup where terminalid=$terminal_id  LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  ".$_GET['orderby']." DESC ";
		}
		else
		{
			$sql="SELECT * FROM callgroup where terminalid=$terminal_id  LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  callgroup.id DESC ";
		}
	}
	else
	{
		$sql="SELECT * FROM callgroup where terminalid=$terminal_id ORDER BY callgroup.id DESC ";
	}
	$result	=	mysqli_query($con,$sql);
	$Num	=	mysqli_num_rows($result);
	$result = 	mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
		$info=array();
	while ($row = mysqli_fetch_array($result)) 
	{
		$info[]	=	 array(
									"id"=>$row['id'],"name"=>$row['name'],"terminalid"=>$row['terminalid'],	
							  );
	
	}
	
	$smarty->assign("info", $info);
//	@mysqli_free_result($result);
//	@mysqli_free_result($result1);
	unset($info,$row1,$row,$sql);

	//��ҳ
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

	$smarty->assign("terminal_id",$terminal_id);
	$smarty->assign("start",$start);
	$smarty->assign("flag",$flag);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("TerminalManager/call_group.html");
}
?>