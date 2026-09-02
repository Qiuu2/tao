<?php
/************************************
	动态获取任务的信息
	把信息保存在xml中
	再读取xml中数据
************************************/
header("content-type:text/xml;charset=utf-8");

require_once('inc/config.inc.php');

$taskid = "";

$getterminalid = "";

$getgroupid = "";

if(isset($_GET['taskid']))
{
	$taskid = trim($_GET['taskid']);
}
//本地化日期
//date_default_timezone_set('Asia/Shanghai');

$getlocaldate = date('Y-m-d');

if($taskid == -1)
{
	$xmlstr.="0#2#0#".$getlocaldate."#".$getlocaldate."##1111111#0#0#0#0#0#0#0";
	echo $xmlstr;
}
else if($taskid != -1)
{

	//读取 终端信息
	$sql = "SELECT terminal.terminalname,terminaloftask.terminalid,terminaloftask.groupid,terminaloftask.area FROM terminaloftask,terminal ";
	$sql.= "WHERE terminal.id = terminaloftask.terminalid AND terminaloftask.taskid = '$taskid'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	while($row = mysqli_fetch_array($result))
	{
		if( ($getterminalid == "") && ($getgroupid== "") )
		{
			$getterminalid = $row['terminalid'];
			$getgroupid = $row['groupid'];
			$area = $row['area'];
		}
		else
		{
			$getterminalid.=",".$row['terminalid'];	
			$getgroupid.=",".$row['groupid'];
			$area.=",".$row['area'];
		}
	}
	
	@mysqli_free_result($result);
	
	unset($sql);


	$sql = "SELECT taskname,prepower,startdate,enddate,info,exemodel,defaultvolume,priority,timelengthtype,timelength,datasendmodel,mediaoftask.mediaid FROM task,mediaoftask WHERE task.taskid = '$taskid' and mediaoftask.taskid=task.taskid";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		$info = $row['info'];
		$xmlstr =trim($row['taskname']."#".$row['prepower']."#".$row['defaultvolume']."#".$row['startdate']."#".$row['enddate']."#".$row['info']."#".$row['exemodel']."#".$row['priority']."#".$row['datasendmodel']."#".$getterminalid."#".$getgroupid."#".$area."#".$row['timelengthtype']."#".$row['timelength']."#".$row['mediaid']);
	}
	@mysqli_free_result($result);
	
	unset($row,$sql);
	/////////////////////////////////////////////////////////////////////////////
	echo trim($xmlstr);
}
?>