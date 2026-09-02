<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

//验证是否失效
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();
	
if(empty($_SESSION['admin_id']))
{
	header("location:login.php");	
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("remote_task",$remote_task);

	$smarty->assign("Filetaskmanager",$Filetaskmanager);

	$smarty->assign("Searchform",$Searchform);

	$smarty->assign("Revise",$Revise);
	//
	require_once("User_Rights_Manage/verify_user_rights_class.php");

	if(have_rights("serverpriv") || is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	$userid=$_SESSION['userid'];
	require('editor.php');

	$smarty->assign('descriptionarea',$descriptionarea);
	$arrays=array();
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
	//
	if(!empty($_GET['searchvalue']))
	{
		if(trim($_GET['searchkey']) =="name")
		{
			if(!empty($_GET['searchsequence']))
			{
				if($_GET['searchvalue']=='文字语音')
				{
				
					$sql="SELECT DISTINCT shortcutkeymap.id,shortcutkeymap.type,ttssentence.name FROM shortcutkeymap,ttssentence WHERE (shortcutkeymap.type='1' AND shortcutkeymap.mediaid IN(SELECT DISTINCT ttssentence.sentenceid FROM ttssentence WHERE ttssentence.sentenceid IN(SELECT mediaid FROM shortcutkeymap WHERE shortcutkeymap.type='1'))) AND shortcutkeymap.mediaid=ttssentence.sentenceid";
				}
				else if($_GET['searchvalue']=='mp3')
				{
					$sql="SELECT shortcutkeymap.id,shortcutkeymap.type,media.name FROM shortcutkeymap,media WHERE shortcutkeymap.type='0'AND media.id=shortcutkeymap.mediaid";
				}
				
			}
			else
			{
				
				if($_GET['searchvalue']=='文字语音')
				{
					$sql="SELECT DISTINCT shortcutkeymap.id,shortcutkeymap.type,ttssentence.name FROM shortcutkeymap,ttssentence WHERE (shortcutkeymap.type='1' AND shortcutkeymap.mediaid IN(SELECT DISTINCT ttssentence.sentenceid FROM ttssentence WHERE ttssentence.sentenceid IN(SELECT mediaid FROM shortcutkeymap WHERE shortcutkeymap.type='1'))) AND shortcutkeymap.mediaid=ttssentence.sentenceid";
					
				}
				else if($_GET['searchvalue']=='mp3')
				{
					$sql="SELECT shortcutkeymap.id,shortcutkeymap.type,media.name FROM shortcutkeymap,media WHERE shortcutkeymap.type='0'AND media.id=shortcutkeymap.mediaid";
				}
			}
		}
		else if(empty($_GET['searchkey']))
		{
			if(!empty($_GET['searchsequence']))
			{
				if($_GET['searchvalue']=='文字语音')
				{
					$sql="SELECT DISTINCT shortcutkeymap.id,shortcutkeymap.type,ttssentence.name FROM shortcutkeymap,ttssentence WHERE (shortcutkeymap.type='1' AND shortcutkeymap.mediaid IN(SELECT DISTINCT ttssentence.sentenceid FROM ttssentence WHERE ttssentence.sentenceid IN(SELECT mediaid FROM shortcutkeymap WHERE shortcutkeymap.type='1'))) AND shortcutkeymap.mediaid=ttssentence.sentenceid";
				}
				else if($_GET['searchvalue']=='mp3')
				{
					$sql="SELECT shortcutkeymap.id,shortcutkeymap.type,media.name FROM shortcutkeymap,media WHERE shortcutkeymap.type='0'AND media.id=shortcutkeymap.mediaid";
				}
			}
			else
			{
				if($_GET['searchvalue']=='文字语音')
				{
				$sql="SELECT DISTINCT shortcutkeymap.id,shortcutkeymap.type,ttssentence.name FROM shortcutkeymap,ttssentence WHERE (shortcutkeymap.type='1' AND shortcutkeymap.mediaid IN(SELECT DISTINCT ttssentence.sentenceid FROM ttssentence WHERE ttssentence.sentenceid IN(SELECT mediaid FROM shortcutkeymap WHERE shortcutkeymap.type='1'))) AND shortcutkeymap.mediaid=ttssentence.sentenceid";
				}
				else if($_GET['searchvalue']=='mp3')
				{
				$sql="SELECT shortcutkeymap.id,shortcutkeymap.type,media.name FROM shortcutkeymap,media WHERE shortcutkeymap.type='0'AND media.id=shortcutkeymap.mediaid";
				}
			}
		}
	}
	else
	{
		$sql="SELECT shortcutkeymap.id,shortcutkeymap.type,media.name FROM shortcutkeymap,media WHERE shortcutkeymap.type='0'AND media.id=shortcutkeymap.mediaid";
		
		$sql2="SELECT DISTINCT shortcutkeymap.id,shortcutkeymap.type,ttssentence.name FROM shortcutkeymap,ttssentence WHERE (shortcutkeymap.type='1' AND shortcutkeymap.mediaid IN(SELECT DISTINCT ttssentence.sentenceid FROM ttssentence WHERE ttssentence.sentenceid IN(SELECT mediaid FROM shortcutkeymap WHERE shortcutkeymap.type='1'))) AND shortcutkeymap.mediaid=ttssentence.sentenceid";
		$result2	=	mysqli_query($con,$sql2) or die("Execute error".mysqli_error($con));
		while ($rows = mysqli_fetch_array($result2)) 
		{
			$arrays[] = array("id"=>$rows['id'],"type"=>$rows['type'],"name"=>$rows['name']);
		}
	}
		$result	=	mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
		$Num	=	mysqli_num_rows($result);
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die("Execute error".mysqli_error($con));

	while ($row = mysqli_fetch_array($result)) 
	{
		$array[] = array("id"=>$row['id'],"type"=>$row['type'],"name"=>$row['name']);
	}
	$cards =array_merge($array,$arrays);
	$smarty->assign("info",$cards);
	
	@mysqli_free_result($result);
	unset($sql,$row,$array);
	//
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

	$smarty->assign("start",$start);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("shortkey_mapping/task_keymapping.html");
}
?>