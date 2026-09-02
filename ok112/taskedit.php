<?php
if (!session_id()) session_start();
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
if(empty($_SESSION['admin_id']))
{
	require_once('login.php');	
}
else
{
	//输出session
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	//读取数据
	$result	=	mysqli_query($con,"SELECT * FROM `task` WHERE taskid='$_GET[id]'");
	if($row = mysqli_fetch_array($result))
	{
		$smarty->assign("taskid",$_GET['taskid']);	
		$smarty->assign("streamid",$row['streamid']);	
		$smarty->assign("taskname",$row['taskname']);	
		$smarty->assign("state",$row['state']);	
		$smarty->assign("startdate",$row['startdate']);		
		$smarty->assign("starttime",$row['starttime']);		
		$smarty->assign("timelength",$row['timelength']);		
		$smarty->assign("tasktype",$row['tasktype']);		
		$smarty->assign("mediaid",$row['mediaid']);		
		{
			$result1	=	mysqli_query($con,"SELECT id, name FROM `media` ");
			while ($row1 = mysqli_fetch_array($result1)) {
				
				$array1[]	=	 array("id"=>$row1['id'],"name"=>$row1['name']);
			}
			
			$smarty->assign("media",$array1);
		}
		
		{
		
			$result2	=	mysqli_query($con,"SELECT streamid,name FROM `serverplaystream` ");
			while ($row2 = mysqli_fetch_array($result2)) 
			{				
				$array2[]	=	 array("streamid"=>$row2['streamid'],"name"=>$row2['name']);
			}
			
			$smarty->assign("stream",$array2);
		}	
		$smarty->display("taskedit.html");
	}else{
		echo "非法参数";
	}
}
?>