<?php
if (!session_id()) session_start();

require_once('inc/smarty.inc.php');

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
	
	$shibiedeviceid="";
	if(isset($_GET['shibiedeviceid']))
	{
	$shibiedeviceid=trim($_GET['shibiedeviceid']);
	$_SESSION['shibiedeviceid'] = $shibiedeviceid;
	}
	else
	{
		if(empty($_GET['shibiedeviceid']))
		{
			$shibiedeviceid = $_SESSION['shibiedeviceid'];

		}
	}


	$sql="";
	if(trim($_GET['keyvalue'])!="")
	{
		if(!empty($_GET['searchkey']) && !empty($_GET['orderby']))
		{
			$sql="SELECT * FROM ai_people WHERE ai_people.peopleidcard LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  ".$_GET['orderby']." DESC ";
		}
		else if(!empty($_GET['searchkey']) && empty($_GET['orderby']))
		{
			$sql="SELECT * FROM ai_people WHERE ai_people.peopleidcard LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  ai_people.id DESC ";
		}
		else if(empty($_GET['searchkey']) && !empty($_GET['orderby']))
		{
			$sql="SELECT * FROM ai_people WHERE ai_people.peopleidcard LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  ".$_GET['orderby']." DESC ";
		}
		else
		{
			$sql="SELECT * FROM ai_people WHERE ai_people.peopleidcard LIKE '%".trim($_GET['keyvalue'])."%' ORDER BY  ai_people.id DESC ";
		}
	}
	else
	{

		if($_SESSION['username']=="admin")
		$sql="SELECT * FROM ai_people WHERE shibiedeviceid='$shibiedeviceid' ORDER BY ai_people.id ASC ";
		else
		$sql="SELECT * FROM ai_people WHERE shibiedeviceid='$shibiedeviceid' ORDER BY ai_people.id ASC ";
		//$sql="SELECT * FROM zhaoshengparam WHERE userid ='$userid' ORDER BY zhaoshengparam.streamid ASC ";
	}
	$result	=	mysqli_query($con,$sql);
	$Num	=	mysqli_num_rows($result);
	$info=array();
	//$result = 	mysqli_query($con,$sql." LIMIT $start,$NumOfPage");	
	while ($row = mysqli_fetch_array($result)) 
	{
		$idsubid=$row['id']."|".$row['shibiedeviceid'];
		$info[] = array("id"=>$row['id'],"shibiedeviceid"=>$row['shibiedeviceid'],"deviceaddr"=>$row['deviceaddr'],"peopleidcard"=>$row['peopleidcard'],"deviceip"=>$row['deviceip'],"boyname1"=>$row['boyname1'],"boyname2"=>$row['boyname2'],"boyname3"=>$row['boyname3'],"peoplename"=>$row['peoplename'],"idsubid"=>$idsubid);
		unset($array1);
	}
	
	$smarty->assign("info", $info);
	@mysqli_free_result($result);
	
	unset($array,$row,$sql);

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
	$smarty->assign("shibiedeviceid",$shibiedeviceid);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("ai_Manager/ai_people.html");
}
?>