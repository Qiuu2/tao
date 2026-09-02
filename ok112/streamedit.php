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
	$result	=	mysqli_query($con,"SELECT * FROM `serverplaystream` WHERE streamid='$_GET[id]'");
	if($row = mysqli_fetch_array($result))
	{
		$smarty->assign("streamid",$_GET['streamid']);	
		$smarty->assign("name",$row['name']);	
		$smarty->assign("feedfile",$row['feedfile']);	
		$smarty->assign("feed",$row['feed']);		
		$smarty->assign("outputformat",$row['outputformat']);		
		$smarty->assign("inputformat",$row['inputformat']);		
		$smarty->assign("AudioCodec",$row['AudioCodec']);			
		$smarty->assign("MaxTime",$row['MaxTime']);		
		$smarty->assign("AudioBitRate",$row['AudioBitRate']);		
		$smarty->assign("AudioChannels",$row['AudioChannels']);		
		$smarty->assign("AudioSampleRate",$row['AudioSampleRate']);	
		$smarty->assign("AudioQuality",$row['AudioQuality']);			
		$smarty->display("StreamManager/streamedit.html");
	}else{
		echo "非法参数";
	}
}
?>