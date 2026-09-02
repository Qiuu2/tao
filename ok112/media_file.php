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
	$smarty->assign("file_manager",$file_manager);
	$smarty->assign("Filemanager",$Filemanager);
	
	$smarty->assign("Searchform",$Searchform);
	
	$smarty->assign("Revise",$Revise);
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
	
	$get_folder_id = 1;//ĬϹ
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
		$_SESSION['tran_mid_value'] = $get_folder_id;
	}
	else
	{
		if(empty($_GET['id']) )
		{
			$_SESSION['tran_mid_value'] = 1;
		}
		
		$get_folder_id = $_SESSION['tran_mid_value'];
	}
	
	$userinfoid = 0;//ĬϹ
	if(isset($_GET['userinfoid']))
	{
		$userinfoid = trim($_GET['userinfoid']);
	}

	if ((int)$_SERVER['SERVER_PORT'] === 80) {
		$host = $_SERVER['SERVER_NAME'];
} else {
		$host = $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
}
	//$host = isset($_SERVER['HTTP_X_FORWARDED_HOST'])?$_SERVER['HTTP_X_FORWARDED_HOST']:(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'');

	$sql = "";
	$userid=$_SESSION['userid'];
	$username=$_SESSION['username'];
	if($username=="admin")
	{
		if(!empty($_POST['searchvalue']))
		{
			if((!empty($_POST['searchkey'])) && (!empty($_POST['orderby'])))
			{
				if($_POST['searchkey'] == "name")
				{
					$sql = "SELECT 	id, name, size, typeid , filename , bitrate , timelength FROM media ";
					$sql.= "WHERE media.folderid = '$get_folder_id' AND media.filename !='tts' AND media.name LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY size DESC ";
				}
				else if($_POST['searchkey'] == "typeid")
				{
					$sql = "SELECT 	id, name, size, typeid ,filename , bitrate , timelength FROM media ";
					$sql.= "WHERE media.folderid = '$get_folder_id' AND media.filename !='tts' AND media.typeid LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY size DESC ";
				}
			}
			else if((!empty($_POST['searchkey'])) && empty($_POST['orderby']))
			{
				if($_POST['searchkey'] == "name")
				{
					$sql = "SELECT 	id, name, size, typeid ,filename , bitrate , timelength FROM media ";
					$sql.= "WHERE media.folderid = '$get_folder_id' AND media.filename !='tts' AND media.name LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY id DESC ";
				}
				else if($_POST['searchkey'] == "typeid")
				{
					$sql = "SELECT 	id, name, size, typeid ,filename , bitrate , timelength FROM media ";
					$sql.= "WHERE media.folderid = '$get_folder_id' AND media.filename !='tts' AND media.typeid LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY id DESC ";
				}
			}
			else
			{
				$sql = "SELECT 	id, name, size, typeid ,filename , bitrate , timelength FROM media ";
				$sql.= "WHERE media.folderid = '$get_folder_id' AND media.filename !='tts' AND media.name LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY id DESC ";
			}
		}
		else
		{
			$sql = "SELECT media.id, media.name, size, typeid ,filename , bitrate , timelength FROM media WHERE media.folderid =  $get_folder_id  ORDER BY id DESC ";
		}
	}
	else
	{
		if(!empty($_POST['searchvalue']))
		{
			if((!empty($_POST['searchkey'])) && (!empty($_POST['orderby'])))
			{
				if($_POST['searchkey'] == "name")
				{
					$sql = "SELECT 	id, name, size, typeid , filename , bitrate , timelength FROM media ";
					$sql.= "WHERE media.folderid = '$get_folder_id' AND media.filename !='tts' AND userid = '$userid' AND media.name LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY size DESC ";
				}
				else if($_POST['searchkey'] == "typeid")
				{
					$sql = "SELECT 	id, name, size, typeid ,filename , bitrate , timelength FROM media ";
					$sql.= "WHERE media.folderid = '$get_folder_id' AND media.filename !='tts' AND userid = '$userid' AND media.typeid LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY size DESC ";
				}
			}
			else if((!empty($_POST['searchkey'])) && empty($_POST['orderby']))
			{
				if($_POST['searchkey'] == "name")
				{
					$sql = "SELECT 	id, name, size, typeid ,filename , bitrate , timelength FROM media";
					$sql.= "WHERE media.folderid = '$get_folder_id' AND media.filename !='tts' AND userid = '$userid' AND media.name LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY id DESC ";
				}
				else if($_POST['searchkey'] == "typeid")
				{
					$sql = "SELECT 	id, name, size, typeid ,filename , bitrate , timelength FROM media ";
					$sql.= "WHERE media.folderid = '$get_folder_id' AND media.filename !='tts' AND userid = '$userid' AND media.typeid LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY id DESC ";
				}
			}
			else
			{
				$sql = "SELECT 	id, name, size, typeid ,filename , bitrate , timelength FROM media ";
				$sql.= "WHERE media.folderid = '$get_folder_id' AND media.filename !='tts' AND userid = '$userid' AND media.name LIKE '%".trim($_POST['searchvalue'])."%' ORDER BY id DESC ";
			}
		}
		else
		{
			$sql = "SELECT media.id, media.name, size, typeid ,filename , bitrate , timelength FROM media WHERE userid = '$userid' AND media.folderid =  $get_folder_id  ORDER BY id DESC ";
		}

	}
	$result	=	mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
	
	$Num	=	mysqli_num_rows($result);
	
	$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));

	$info=array();
	while ($row = mysqli_fetch_array($result)) 
	{	
	
		$media_path = basename($row['filename']);
		
		$media_to_server_path = "http://".$host."/upload/".$media_path."";
		$info[] = array(
							"id"=>$row['id'],"name"=>$row['name'],"size"=>$row['size'],"typeid"=>$row['typeid'],"filename"=>$row['filename'],
							"bitrate"=>$row['bitrate'],"timelength"=>$row['timelength'],"media_path"=>$media_to_server_path
						);
					
	}
	mysqli_free_result($result);
	
	$smarty->assign("info",$info);
	
	unset($row,$sql);
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
	mysqli_free_result($results);	
	$smarty->assign("sign",$sign);
	
	unset($rows);

	if($Num != 0)
	{
		require_once("pagination.class.php");
		$p = new pagination;
		$p->Items($Num);
		$p->limit($NumOfPage);
		$p->target("?id=".$get_folder_id."&userinfoid=".$userinfoid."&");
		$p->currentPage($_GET['page']);
		$p->adjacents(3);
		$smarty->assign("pagestr",$p->show());
	}
	$getsize=0;
	$getsql="SELECT size FROM media WHERE folderid=$get_folder_id";
	$getresult	=	mysqli_query($con,$getsql) or die("Execute error".mysqli_error($con));
	while ($getrows = mysqli_fetch_array($getresult)) 
	{
	$getsize=$getsize+$getrows['size'];
	}
	$smarty->assign("getsize",$getsize);
	//session
	$smarty->assign("get_folder_id",$get_folder_id);

	$smarty->assign("userinfoid",$userinfoid);
	$smarty->assign("start",$start);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->assign("model",$_SESSION['servermodel']);
	
	$smarty->display("FileManager/group_by_media_folder.html");
}
?>
