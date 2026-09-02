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

$taskid = "";

if(isset($_GET['taskid']))
{
	$taskid = trim($_GET['taskid']);
	
	$_SESSION['tran_mid_value'] = $taskid;
}
else
{
	$taskid = $_SESSION['tran_mid_value'];
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
$sql = "select 	task.taskid, task.taskname, media.name, task.timelength, task.prepower, task.startdate,task.enddate, task.playtime, task.exemodel,task.timelengthtype, task.info, task.defaultvolume ";
$sql.= "from task,mediaoftask,media where task.taskid = mediaoftask.taskid and mediaoftask.mediaid = media.id and  ";
$sql.= "task.info = (SELECT DISTINCT task.info from task where task.taskid = '$taskid' and task.tasktype IN(1,15)) and task.tasktype IN(1,15) ORDER BY task.startdate,task.playtime";

$result = mysqli_query($con,$sql);
$Num = mysqli_num_rows($result);
//$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage") or die(mysqli_error($con));

while($row = mysqli_fetch_array($result))
{
	$getplantaskinfo[] = array(
								"taskid"=>$row['taskid'],"taskname"=>$row['taskname'],"timelength"=>$row['timelength'],"timelengthtype"=>$row['timelengthtype'],
								"prepower"=>$row['prepower'],"startdate"=>$row['startdate'],"enddate"=>$row['enddate'],
								"playtime"=>$row['playtime'],"exemodel"=>$row['exemodel'],"name"=>$row['name'],
								"info"=>$row['info'],"defaultvolume"=>$row['defaultvolume']
							  );
}

$smarty->assign("getplantaskinfo",$getplantaskinfo);
unset($sql,$row,$getplantaskinfo);
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
$smarty->assign("start",$start);
$smarty->assign("admin_id",$_SESSION['admin_id']);
$smarty->display("BellManager/displayplantask.html");
?>