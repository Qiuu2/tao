<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();
$id = "";
if(isset($_GET['id']))
{
	$id = trim($_GET['id']);
}

if( empty($_SESSION['admin_id']) )
{
	header('Location:login.php');
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	
	$smarty->assign("language",$_SESSION['language']);
	
	$smarty->assign("add_bell_scheme",$add_bell_scheme);

	$smarty->assign("Belladdtask",$Belladdtask);
	
#if 0
	
#endif
	//$type =  "(1,3,4)";	
	
	//$terminalist = get_terminallist($type, 0);
	
	//$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	//$terminalist = get_grouped_terminal($type);
	
	//$terminalist = create_tree_str($type);

	/*$terminalist_tmp = xml_str_analyze($terminalist);
	
	if(empty($terminalist_tmp))
	{
		echo "<script>alert('".$add_bell_scheme['not_add_type_terminal']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
	}*/
	//$smarty->assign("terminalist",$terminalist);

	//获取打铃文件夹中文件
//	$user_sql = "SELECT media.id, media.name FROM media WHERE media.folderid= ";
		
		//$user_sql.= "(SELECT parentid FROM filefolder WHERE filefolder.parentid = 2) ";
		
		//$result = mysqli_query($con,$user_sql);

		
		$sql="SELECT ttssentence.type,ttssentence.mediaseq FROM ttssentence WHERE ttssentence.sentenceid='$id'ORDER BY mediaseq ASC";
		$mediasql = mysqli_query($con,$sql);
		while($getrow=mysqli_fetch_array($mediasql))
		{
			
			if($getrow[0]==2)
			{
			$getsql2="SELECT content,speed,volume,ttssentence.sentenceid FROM ttssentence WHERE mediaseq='$getrow[1]' AND ttssentence.sentenceid='$id'";
			
			}
			else if($getrow[0]==0)
			{
			$getsql2="SELECT ttssentence.mediaid,ttssentence.speed,ttssentence.volume,media.name FROM media,ttssentence WHERE ttssentence.sentenceid='$id' AND ttssentence.mediaseq='$getrow[1]' AND media.id IN (SELECT sentenceid FROM ttssentence WHERE ttssentence.sentenceid='$id' AND mediaseq='$getrow[1]')";
			}
			
			$mediasql2 = mysqli_query($con,$getsql2);

			
			
			while($getrow2= mysqli_fetch_array($mediasql2))
			{
			
				$getmediacontent[]= array("type"=>$getrow[0],"mediaseq"=>$getrow[1],"content"=>addslashes($getrow2[0]),"speed"=>$getrow2[1],"volume"=>$getrow2[2],"sentenceid"=>$getrow2[3]);
			}
		
			
		}
		$smarty->assign("lessoninfo",$getmediacontent);
		unset($getmediacontent);
		@mysqli_free_result($mediasql2);
		
	$mediaresult = mysqli_query($con,"SELECT id,name from media where folderid=6 AND filename!='tts' ");

	if(mysqli_fetch_array($mediaresult))
	{
		@mysqli_data_seek($mediaresult, 0);

		while($row = mysqli_fetch_array($mediaresult))
		{
			$medianame[] = array("id"=>$row['id'],"name"=>$row['name']);
		}

		$smarty->assign("medianame",$medianame);

		unset($medianame);

		@mysqli_free_result($mediaresult);
	}
	
	$result = mysqli_query($con,"SELECT seq,name from ttstext ");

	if(mysqli_fetch_array($result))
	{
		@mysqli_data_seek($result, 0);

		while($row = mysqli_fetch_array($result))
		{
			$medialist[] = array("seq"=>$row['seq'],"name"=>$row['name']);
		}

		$smarty->assign("medialist",$medialist);

		unset($medialist);

		@mysqli_free_result($result);
	}
	$mediaresult = mysqli_query($con,"SELECT DISTINCT speed,volume,name FROM ttssentence WHERE ttssentence.sentenceid='$id'");
	while($row = mysqli_fetch_array($mediaresult))
	{
		$smarty->assign("speed",$row[0]);
		$smarty->assign("volume",$row[1]);
		$smarty->assign("taskname",$row[2]);
	}

	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("stopmanager/modifytrainmedia.html");
}
?>

