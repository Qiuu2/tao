<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once("language/".$_SESSION['language'].".php");

$smarty->assign("language",$_SESSION['language']);
/*动态显示页面文本内容*/
$smarty->assign("Bellmanager",$Bellmanager);

$smarty->assign("Searchform",$Searchform);

$smarty->assign("Revise",$Revise);

$medianame = "";

if(isset($_GET['name']))
{
	$medianame = trim($_GET['name']);
	
}


//分页
require('editor.php');
$smarty->assign('descriptionarea',$descriptionarea);

$array=array();
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
//若媒体删除了则出现问题
$sql = "SELECT ttssentence.type,mediaseq FROM ttssentence WHERE ttssentence.name='$medianame' ORDER BY mediaseq ASC";

$result = mysqli_query($con,$sql);

$Num = mysqli_num_rows($result);

$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die(mysqli_error($con));

while($row = mysqli_fetch_array($result))
{
	if($row[0]==0)
	{
	$sql2="SELECT DISTINCT media.name FROM media,ttssentence WHERE media.id IN (SELECT mediaid FROM ttssentence WHERE ttssentence.name='$medianame' AND TYPE='0'AND mediaseq='$row[1]')";	
	}
	else if($row[0]==1)
	{
	$sql2="SELECT DISTINCT ttstext.name FROM ttstext,ttssentence WHERE ttstext.seq IN (SELECT mediaid FROM ttssentence WHERE ttssentence.name='$medianame'AND TYPE='1'AND mediaseq='$row[1]')";
	}
	else
	{
	$sql2="SELECT DISTINCT content FROM ttssentence WHERE  ttssentence.name='$medianame'AND TYPE='2'AND mediaseq='$row[1]'";
	}
	$results	=	mysqli_query($con,$sql2);
	while ($rows = mysqli_fetch_array($results)) 
	{
		$getplantaskinfo[]=array("name" => $rows[0]);
	}
	
	
}

$smarty->assign("getplantaskinfo",$getplantaskinfo);
unset($sql,$row,$getplantaskinfo);

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
$smarty->display("stopmanager/displayplantask.html");
?>