<?php
	if (!session_id()) session_start();
	header("content-type:text/html;charset=utf-8");
	require_once("inc/config.inc.php");
	require_once('inc/smarty.inc.php');
	require_once('inc/common.php');

	require_once("inc/socket_conf.php");
	$taskmax="";
	$taskmax2="";
	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
	}
	$folderid = "";
	if(isset($_GET['folderid']))
	{
		$folderid = trim($_GET['folderid']);
	}
	
	$gettoterminalid = "";
	if(isset($_GET['gettoterminalid']))
	{
		$gettoterminalid = trim($_GET['gettoterminalid']);
	}
		
	function check_terminalfolder($con,$id,$parentid,$name,$terminalid,$seqnumber,$gettoterminalid,$terminal_id,$newparentid)
	{
		$getnewid=0;
		$sql="INSERT INTO terminalfolder(parentid,name,terminalid,seqnumber)VALUES('$newparentid','$name',$gettoterminalid,'$seqnumber')";
		mysqli_query($con,$sql);
	
		$results = mysqli_query($con,"SELECT MAX(id) FROM terminalfolder WHERE terminalid = $gettoterminalid");
		if($rows = mysqli_fetch_array($results)) 
		{
			$getnewid=$rows[0];
		
		}
		$getterminalresults = mysqli_query($con,"SELECT terminalid,folderid,seqnumber FROM terminaloffolder WHERE folderid ='$id'");
		while ($getterminalrows = mysqli_fetch_array($getterminalresults)) 
		{
			mysqli_query($con,"INSERT INTO terminaloffolder(terminalid,folderid,seqnumber)VALUES('$getterminalrows[0]','$getnewid','$getterminalrows[2]')");
		}
			
		$gettaskids = mysqli_query($con,"SELECT id,parentid,name,terminalid,seqnumber FROM terminalfolder WHERE parentid=$id AND terminalid = $terminal_id ORDER BY id ASC");	
		while($gettaskrows = mysqli_fetch_array($gettaskids))
		{
			check_terminalfolder($con,$gettaskrows['id'],$gettaskrows['parentid'],$gettaskrows['name'],$gettaskrows['terminalid'],$gettaskrows['seqnumber'],$gettoterminalid,$terminal_id,$getnewid);
		}
	}
	mysqli_query($con,"LOCK TABLES terminalfolder write,terminaloffolder write");
	$gettaskid = mysqli_query($con,"SELECT id,parentid,name,terminalid,seqnumber FROM terminalfolder WHERE terminalid = $gettoterminalid ORDER BY id ASC");	
	if(mysqli_num_rows($gettaskid) > 0)
	{
		echo "2";
	}
	else
	{
		$gettaskids = mysqli_query($con,"SELECT id,parentid,name,terminalid,seqnumber FROM terminalfolder WHERE parentid= 0 AND terminalid = $terminal_id ORDER BY id ASC");	
		if($gettaskrows = mysqli_fetch_array($gettaskids))
		{
			check_terminalfolder($con,$gettaskrows['id'],$gettaskrows['parentid'],$gettaskrows['name'],$gettaskrows['terminalid'],$gettaskrows['seqnumber'],$gettoterminalid,$terminal_id,0);
		}
		echo "1";
		
	}
mysqli_query($con,"UNLOCK TABLES");
?>