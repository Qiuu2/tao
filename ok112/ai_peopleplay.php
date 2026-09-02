<?php
if (!session_id()) session_start();

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
			$sql="SELECT * FROM ai_timetts WHERE ai_timetts.id LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  ".$_GET['orderby']." DESC ";
		}
		else if(!empty($_GET['searchkey']) && empty($_GET['orderby']))
		{
			$sql="SELECT * FROM ai_timetts WHERE ai_timetts.id LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  ai_timetts.id DESC ";
		}
		else if(empty($_GET['searchkey']) && !empty($_GET['orderby']))
		{
			$sql="SELECT * FROM ai_timetts WHERE ai_timetts.id LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  ".$_GET['orderby']." DESC ";
		}
		else
		{
			$sql="SELECT * FROM ai_timetts WHERE ai_timetts.id LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  ai_timetts.id DESC ";
		}
	}
	else
	{
		if($_SESSION['username']=="admin")
		$sql="SELECT * FROM ai_timetts WHERE id ORDER BY ai_timetts.id ASC ";
		else
		$sql="SELECT * FROM ai_timetts WHERE id ORDER BY ai_timetts.id ASC ";
	}
	$result	=	mysqli_query($con,$sql);
	$Num	=	mysqli_num_rows($result);
	
	$info=array();
	while ($row = mysqli_fetch_array($result)) 
	{
		$info[] =array("id"=>$row['id'],"time"=>$row['time'],"demo"=>$row['demo'],"volume"=>$row['volume']);
	}
	$smarty->assign("info", $info);
	@mysqli_free_result($result);
	
	unset($array,$row,$sql);

	$sqls="SELECT DISTINCT(shibiedeviceid),deviceaddr FROM ai_devicedemo ORDER BY ai_devicedemo.id ASC ";
	$results	=	mysqli_query($con,$sqls);
	$aipeople=array();

	while ($rows = mysqli_fetch_array($results)) 
	{
		$aipeople[] = array("shibiedeviceid"=>$rows['shibiedeviceid'],"deviceaddr"=>$rows['deviceaddr']);
	
	}
	$smarty->assign("aipeople", $aipeople);
	@mysqli_free_result($results);


	$sqlstts="SELECT * FROM ai_timetts ORDER BY ai_timetts.id ASC ";
	$resulttts	=	mysqli_query($con,$sqlstts);
	
	$aitts=array();
	
	while ($rowttss = mysqli_fetch_array($resulttts)) 
	{
		$aitts[] = array("id"=>$rowttss['id'],"time"=>$rowttss['time'],"demo"=>$rowttss['demo'],"enable"=>$rowttss['enable'],"volume"=>$rowttss['volume']);
	}
	$smarty->assign("aitts", $aitts);
	@mysqli_free_result($resulttts);

	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	
	$tree_info = create_tree_str($type);
	
	$smarty->assign("tree_info",$tree_info);
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
	$smarty->display("ai_Manager/ai_playmanager.html");
}
?>