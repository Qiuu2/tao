<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');



if(empty($_SESSION['admin_id']))
{	
	header("location:login.php");
}
else
{	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("terminal_function_modify",$terminal_function_modify);

	$smarty->assign("Belladdtask",$Belladdtask);
#if 0
	$str = "<?xml version='1.0' encoding='UTF-8'?> <tree id=\"0\">";
	
	$resultstream=	mysqli_query($con,"SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream");
	while ($rowstream = mysqli_fetch_array($resultstream))
	{			
		$streamid = $rowstream['streamid'];
		$str = "<item text=\"".$rowstream['name']."\" id=\"stream_".$streamid."\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\">";
	
		$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.groupid=$streamid");
		while ($rowterminal = mysqli_fetch_array($resultterminal)) 
		{	
			$str = "<item text=\"".$rowterminal['terminalname']."\" id=\""."$rowterminal[id]"."\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\"/>\n";
					  
		}							 
	
	}
	
#endif
$get_id=$_GET['id'];
	//添加读取任务名称
	$sql="SELECT taskname, timelength, startdate, enddate, ";
	$sql.="playtime, exemodel , priority,defaultvolume FROM task WHERE task.taskid = '$get_id'";
	$result=mysqli_query($con,$sql) or die(mysqli_error($con));
	if($row = mysqli_fetch_array($result))
	{
		$taskinfo = array("taskname"=>$row['taskname'],"timelength"=>$row['timelength'],"startdate"=>$row['startdate'],"enddate"=>$row['enddate'],"playtime"=>$row['playtime'],"exemodel"=>$row['exemodel'],"priority"=>$row['priority'],"defaultvolume"=>$row['defaultvolume']);
	}
	$smarty->assign("taskinfo",$taskinfo);
	@mysqli_free_result($result);
	unset($taskinfo);
	
	
	$sqls="SELECT pcstate,projectionstate,systemstate,volstate,volume,projectionscreenstate,dev1,dev2,dev3,dev4,dev5 FROM terminaloftask WHERE taskid=  '$get_id' LIMIT 1";

	$results=mysqli_query($con,$sqls) or die(mysqli_error($con));
	if($rows = mysqli_fetch_array($results))
	{
		$terminalstate = array("pcstate"=>$rows['pcstate'],"projectionstate"=>$rows['projectionstate'],"systemstate"=>$rows['systemstate'],"volstate"=>$rows['volstate'],"volume"=>$rows['volume'],"projectionscreenstate"=>$rows['projectionscreenstate'],"dev1"=>$rows['dev1'],"dev2"=>$rows['dev2'],"dev3"=>$rows['dev3'],"dev4"=>$rows['dev4'],"dev5"=>$rows['dev5']);
	}
	$smarty->assign("terminalstate",$terminalstate);
	@mysqli_free_result($results);
	unset($terminalstate);
	
	
	
	
	
	
	//读取任务相关终端
	//$sql = "SELECT terminalid FROM terminaloftask WHERE terminaloftask.taskid = '$get_id'";
	//$i = 0;
	//$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	//while($row = mysqli_fetch_array($result))
	//{
		$terminalid = get_current_task_termianl_id($get_id);
	//	$i++;
	//}
	
	$smarty->assign("terminalid",$terminalid);
	
	@mysqli_free_result($result);
	
	unset($terminalid);
	
	//判读是否有功放
	$flag = 0;
	$prepower = 0;
	$playtime = "00:00:00";
	$sql = "SELECT prepower, playtime FROM task WHERE taskname =(SELECT taskname FROM task WHERE taskid = '$get_id') AND tasktype !=5 AND prepower != 0";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	if($row = mysqli_fetch_array($result))
	{
		$flag = 1;
		$prepower = $row['prepower'];
		$playtime = $row['playtime'];
	}
	$smarty->assign("flag",$flag);
	$smarty->assign("prepower",$prepower);
	$smarty->assign("playtime",$playtime);
	@mysqli_free_result($result);
	unset($flag,$prepower,$playtime);
	
	$type=get_terminal_type(11,$do_php_prompt['Terminal_not_support'],0,0);
	$terminalist = create_tree_str($type);	
	//$terminalist = create_tree_str("1,5,4,11,13,14,15");
	
	$smarty->assign("terminalist",$terminalist);
	
	$smarty->assign("taskid",$_GET['id']);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("centercontrol/conterctrlmodify.html");
}
?>