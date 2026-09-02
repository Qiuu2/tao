<?php
	if (!session_id()) session_start();
	
	header("content-type:text/html;charset=utf-8");
	
	require_once("inc/config.inc.php");
	
	require_once("inc/socket_conf.php");
	$taskmax="";
	$taskmax2="";
	$taskcopyfrom = "";
	if(isset($_GET['taskcopyfrom']))
	{
		$taskcopyfrom = trim($_GET['taskcopyfrom']);
	}
	$taskcopyto = "";
	if(isset($_GET['taskcopyto']))
	{
		$taskcopyto = trim($_GET['taskcopyto']);
	}
	$getuserid=1;
	$sql = mysqli_query($con,"SELECT userid FROM ledtaskfree WHERE id ='$taskcopyto'");
	if ($getrow = mysqli_fetch_array($sql)) 
	{
	$getuserid=$getrow['userid'];
	}
	$led_taskid=0;
	
	$getresults = mysqli_query($con,"SELECT * FROM task WHERE parentid ='$taskcopyfrom' and cmdargs>7000");
	while ($getrows = mysqli_fetch_array($getresults)) 
	{
			mysqli_query($con,"INSERT INTO task(taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,
startdate,enddate,playtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,
						playfileid,info,defaultvolume,task_user_id,sec_task_id,parentid,offlinestate,createtime,disableday,interval_s,intplaylength,intplaylengthtype)
			VALUES('$getrows[1]','$getrows[2]','$getrows[3]','$getrows[4]','$getrows[5]','$getrows[6]',
						'$getrows[7]','$getrows[8]','$getrows[9]','$getrows[10]','$getrows[11]',
			'$getrows[13]','$getrows[14]','$getrows[15]','$getrows[16]','$getrows[17]','$getrows[18]',
'$getrows[19]','$getrows[20]','$getrows[21]','$getrows[22]','$getrows[23]','$getuserid','$getrows[25]','$taskcopyto','$getrows[27]','$getrows[28]','$getrows[29]','$getrows[30]','$getrows[31]','$getrows[32]')");		
		$gettaskid = mysqli_query($con,"SELECT taskid,tasktype FROM task WHERE taskid = (SELECT MAX(taskid) FROM task)");	
		while($gettaskrows = mysqli_fetch_array($gettaskid))
		{
			if($gettaskrows[1]=='24')
			{
				$led_taskid=$gettaskrows[0];
				$taskmax2=$gettaskrows[0];	
			}
			$led_result = "UPDATE task SET cmdargs = '$led_taskid' WHERE taskid = '$gettaskrows[0]'";
			mysqli_query($con,$led_result) or die(mysqli_error($con));
			
			if($gettaskrows[1]=='25')
			{
				$taskmax2=$gettaskrows[0];	
			}
			
			if($gettaskrows[1]=='9')
			{
				$sqlmedia_result = "UPDATE task SET sec_task_id = '$taskmax2' WHERE taskid = '$gettaskrows[0]'";
				mysqli_query($con,$sqlmedia_result) or die(mysqli_error($con));
			}
			$taskmax=$gettaskrows[0];
		}
					
		$gettaskid = mysqli_query($con,"SELECT taskid FROM task WHERE taskid ='$getrows[0]'");	
		while($gettaskrows = mysqli_fetch_array($gettaskid))
		{
			$getmusicresults = mysqli_query($con,"SELECT * FROM mediaoftask WHERE taskid ='$getrows[0]'");
			while($getmediarows = mysqli_fetch_array($getmusicresults)) 
			{	
				mysqli_query($con,"INSERT INTO mediaoftask(mediaid,taskid,sort) VALUES('$getmediarows[1]','$taskmax','$getmediarows[3]')");
			}
			$getsign=1;

			$getterminalresults = mysqli_query($con,"SELECT * FROM terminaloftask WHERE taskid ='$gettaskrows[0]'");
			while ($getterminalrows = mysqli_fetch_array($getterminalresults)) 
			{
				mysqli_query($con,"INSERT INTO terminaloftask (taskid,terminalid,workstate,groupid,area) VALUES('$taskmax','$getterminalrows[2]','$getterminalrows[3]','$getterminalrows[4]','$getterminalrows[5]')");
			}
		
		}
		
	}
	echo 1;		
?>