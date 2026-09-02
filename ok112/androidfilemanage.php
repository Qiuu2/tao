<?php
if (!session_id()) session_start();

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

//显示多语言
require_once("language/chinese.php");


$mediaArray="";//媒体文件列表

$termianlArray="";//终端列表

$result=0;//获取功放集

	
$sql = "SELECT DISTINCT taskid,taskname,israndomplay,projectstate,state,startdate,enddate,playtime,timelength,exemodel,priority,tasktype,timelengthtype,playfileid,channel,defaultvolume,cmdargs,bandrate,samplerate,task_user_id,book_admin.username FROM task,book_admin WHERE task_user_id=book_admin.id AND tasktype = 1 AND channel = 0 AND projectstate = 0  ";
$sql.= "ORDER BY startdate, enddate DESC, playtime ASC ";

$result	= mysqli_query($con,$sql);

$Num	=	mysqli_num_rows($result);

//$result = mysqli_query($con,$sql);
$smarty->assign("terminal_function",$terminal_function);
	
	while ($row = mysqli_fetch_array($result)) 
	{
		$array[]=array(
						"taskid"=>$row['taskid'],"taskname"=>$row['taskname'],"state"=>$row['state'],"startdate"=>$row[startdate],
					
						"enddate"=>$row['enddate'], "playtime"=>$row['playtime'], "playmodel"=>$row['playmodel'],
					
						"timelength"=>$row['timelength'],"exemodel"=>$row['exemodel'],"priority"=>($row['priority']%10),"username"=>$row['username'],
					
						"timelengthtype"=>$row['timelengthtype'],"defaultvolume"=>$row['defaultvolume']
					);
	}
	$smarty->assign("info",$array);
	
	@mysqli_free_result($result);
	
	unset($array,$row,$sql);
	
		require_once("pagination.class.php");
	
	$p = new pagination;
	$p->Items(1);
	$p->limit(1);
	$p->target("?id=1&");
	$p->currentPage(1);
	$p->adjacents(3);
	$smarty->assign("pagestr",$p->show());	
	$smarty->display("android/androidtaskmanager.html");

?>
