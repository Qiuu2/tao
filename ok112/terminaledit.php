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
	$result	=	mysqli_query($con,"SELECT * FROM `terminal` WHERE id='$_GET[id]'");
	if($row = mysqli_fetch_array($result))
	{
		$smarty->assign("id",$row['id']);	
		$smarty->assign("groupid",$row['groupid']);	
		$smarty->assign("terminalname",$row['terminalname']);	
		$smarty->assign("typeid",$row['typeid']);		
		$smarty->assign("ip",$row['ip']);		
		$smarty->assign("postion",$row['postion']);		
		$smarty->assign("volume",$row['volume']);		
			
		{
			$result2	=	mysqli_query($con,"SELECT streamid,name FROM `serverplaystream` ");
			while ($row2 = mysqli_fetch_array($result2)) {
				
				$array2[]	=	 array("streamid"=>$row2['streamid'],"name"=>$row2['name']);
			}
			
			$smarty->assign("stream",$array2);
	  }
	  
	  {	
			$result3	=	mysqli_query($con,"SELECT id,name FROM `terminaltype` ");
			while ($row3 = mysqli_fetch_array($result3)) {
				
				$array3[]	=	 array("id"=>$row3['id'],"name"=>$row3['name']);
			}
			
			$smarty->assign("terminaltype",$array3);
	  }
	
		$smarty->display("terminaledit.html");
	}else{
		echo "非法参数";
	}
}
?>