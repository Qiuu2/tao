<?php
	header("content-type:text/html;charset=utf-8");
	require_once('inc/smarty.inc.php');
	require_once('inc/config.inc.php');
	$terminalid = "";
	if(isset($_GET['terminalid']))
	{
		$terminalid = trim($_GET['terminalid']);
	}
	$terminaltype = "";
	if(isset($_GET['terminaltype']))
	{
		$terminaltype = trim($_GET['terminaltype']);
	}
	
	$nostream = "无分区终端";
	
	if(!empty($terminaltype))
	{
	$sql="SELECT DISTINCT serverplaystream.streamid,serverplaystream.name FROM terminal,serverplaystream WHERE terminal.groupid=serverplaystream.streamid AND terminal.typeid='$terminaltype'";
	$str = "<?xml version='1.0' encoding='UTF-8'?> <tree id=\"0\">";
	$fp = fopen("smarty/templates/BellManager/codebase/tree4.xml","w");
	fwrite($fp,$str);
	fwrite($fp,"\n");
	$resultstream=	mysqli_query($con,$sql);
	while ($rowstream = mysqli_fetch_array($resultstream))
	{			
		$streamid = $rowstream['streamid'];
		$str = "<item text=\"".$rowstream['name']."\" id=\"stream_".$streamid."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >";
		fwrite($fp,$str);
		fwrite($fp,"\n");
		$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.groupid=$streamid and terminal.typeid = '$terminaltype' AND terminal.id != '$terminalid' ");
		while ($rowterminal = mysqli_fetch_array($resultterminal)) 
		{	
			$str = "<item text=\"".$rowterminal['terminalname']."\" id=\""."$rowterminal[id]"."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >\n</item>\n";
			fwrite($fp,$str);		  
		}							 
		fwrite($fp,"</item>\n");			
	}
	//无分区
	$str = "<item text=\"".$nostream."\" id=\"".$nostream."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >";
	fwrite($fp,$str);
	fwrite($fp,"\n");
	$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.groupid='0' and terminal.typeid = '$terminaltype' AND terminal.id != '$terminalid' ");
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		$str = "<item text=\"".$rowterminal['terminalname']."\" id=\""."$rowterminal[id]"."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >\n</item>\n";
		fwrite($fp,$str);		  
	}							 
	fwrite($fp,"</item>\n");
	
	fwrite($fp,"</tree>\n");		
	$flag=fclose($fp);
	if($flag)
	{
		echo "2";
	}
}
else if(empty($terminaltype))
{
	$sql="SELECT DISTINCT * FROM serverplaystream";
	
	$str = "<?xml version='1.0' encoding='UTF-8'?> <tree id=\"0\">";
	$fp = fopen("smarty/templates/BellManager/codebase/tree4.xml","w");
	fwrite($fp,$str);
	fwrite($fp,"\n");
	$resultstream=	mysqli_query($con,$sql);
	while ($rowstream = mysqli_fetch_array($resultstream))
	{			
		$streamid = $rowstream['streamid'];
		$str = "<item text=\"".$rowstream['name']."\" id=\"stream_".$streamid."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >";
		fwrite($fp,$str);
		fwrite($fp,"\n");
		$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.groupid=$streamid AND terminal.id != '$terminalid' ");
		while ($rowterminal = mysqli_fetch_array($resultterminal)) 
		{	
			$str = "<item text=\"".$rowterminal['terminalname']."\" id=\""."$rowterminal[id]"."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >\n</item>\n";
			fwrite($fp,$str);		  
		}							 
		fwrite($fp,"</item>\n");			
	}
	//无分区
	$str = "<item text=\"".$nostream."\" id=\"".$nostream."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >";
	fwrite($fp,$str);
	fwrite($fp,"\n");
	$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.groupid='0' AND terminal.id != '$terminalid' ");
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		$str = "<item text=\"".$rowterminal['terminalname']."\" id=\""."$rowterminal[id]"."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >\n</item>\n";
		fwrite($fp,$str);		  
	}							 
	fwrite($fp,"</item>\n");
	
	fwrite($fp,"</tree>\n");		
	$flag=fclose($fp);
	if($flag)
	{
		echo "2";
	}	
}
?>