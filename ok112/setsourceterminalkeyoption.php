<?php
	header("content-type:text/html;charset=utf-8");
	require_once('inc/smarty.inc.php');
	require_once('inc/config.inc.php');
	/////////////////////////////////////////��̬��ʾ
	$getid=$_GET['id'];
	if(!empty($getid))
	{
		$slqterminaltype="SELECT DISTINCT serverplaystream.streamid,serverplaystream.name FROM terminal,serverplaystream WHERE terminal.groupid=serverplaystream.streamid AND terminal.typeid='$getid'";
	}
	else if(empty($getid))
	{
			$slqterminaltype="SELECT DISTINCT * FROM serverplaystream";
	}
	//��Ӷ�ȡ�ն����ݲ�д���ļ���2010/4/28	
	$str = "<?xml version='1.0' encoding='UTF-8'?> <tree id=\"0\">";
	$fp = fopen("smarty/templates/BellManager/codebase/tree5.xml","w");
	fwrite($fp,$str);
	fwrite($fp,"\n");
	$streamresult=mysqli_query($con,$slqterminaltype) or die("Execute error".mysqli_error($con));
	while ($streamrow = mysqli_fetch_array($streamresult))
	{			
		$streamid = $streamrow['streamid'];
		//////////////////////////////////////////////////////////////////////////�޷������ն˲���ʾ
		$sql="SELECT terminalname FROM terminal WHERE terminal.groupid=$streamid";
		if(mysqli_num_rows(mysqli_query($con,$sql))<=0)
		{
			continue;
		}
		//////////////////////////////////////////////////////////////////////////
		$str = "<item text=\"".$streamrow['name']."\" id=\"dir_".$streamid."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >";
		fwrite($fp,$str);
		fwrite($fp,"\n");
		
		$terminalresult=mysqli_query($con,"SELECT DISTINCT terminal.id,terminal.terminalname FROM terminal WHERE	terminal.groupid=$streamid") or die("Execute error".mysqli_error($con));
		while ($terminalrow = mysqli_fetch_array($terminalresult)) 
		{	
			$str = "<item text=\"".$terminalrow['terminalname']."\" id=\""."$terminalrow[id]"."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >\n</item>\n"	;
			fwrite($fp,$str);		  
		}					 
	fwrite($fp,"</item>\n");			
	}	
	fwrite($fp,"</tree>\n");		
	$flag=fclose($fp);
	if($flag)
	{
		echo "1";
	}
?>
