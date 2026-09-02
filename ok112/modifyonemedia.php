<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

mysqli_query($con,"set names 'utf8'");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once("inc/socket_conf.php");

$taskname = "";

if(isset($_GET['taskname']))
{
	if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE"))
	$taskname = mb_convert_encoding(trim($_GET['taskname']),"utf-8","gb2312");
	else
		$taskname=$_GET['taskname'];
}

$type = "";

if(isset($_GET['type']))
{
	$type = trim($_GET['type']);
}

$settrainname = "";
if(isset($_GET['settrainname']))
{
		if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE"))
	$settrainname = mb_convert_encoding(trim($_GET['settrainname']),"utf-8","gb2312");
	else
		$settrainname=$_GET['settrainname'];
}

$totalrownum="";
if(isset($_GET['totalrownum']))
{
	$totalrownum = trim($_GET['totalrownum']);
}
$getspeed=0;
if(isset($_GET['getspeed']))
{
	$getspeed = trim($_GET['getspeed']);
}
$getvolume=0;
if(isset($_GET['getvolume']))
{
	$getvolume = trim($_GET['getvolume']);
}
$folderid=6;
if(isset($_GET['folderid']))
{
	$folderid = trim($_GET['folderid']);
}

mysqli_query($con,"LOCK TABLE ttssentence,media WRITE");//锁定此表

$sql2="SELECT id FROM ttssentence WHERE ttssentence.name='$taskname' AND mediaseq='$totalrownum'";

$result=mysqli_query($con,$sql2) or die(mysqli_error($con));
if(mysqli_num_rows($result)>0)
{
	if($type==2)
	mysqli_query($con,"UPDATE ttssentence SET speed='$getspeed',volume='$getvolume',content='$settrainname',type='$type' WHERE name='$taskname' AND mediaseq='$totalrownum'") or die(mysqli_error($con));
	else
	mysqli_query($con,"UPDATE ttssentence SET speed='$getspeed',volume='$getvolume',mediaid='$settrainname',type='$type' WHERE name='$taskname' AND mediaseq='$totalrownum'") or die(mysqli_error($con));
}
else
{
	if($type==2)
	$sql = "INSERT INTO ttssentence ( name, type,content,mediaseq,speed,volume)VALUES('$taskname','$type','$settrainname','$totalrownum','$getspeed','$getvolume')";
	else
	$sql = "INSERT INTO ttssentence ( name, type,mediaid,mediaseq,speed,volume)VALUES('$taskname','$type','$settrainname','$totalrownum','$getspeed','$getvolume')";
	mysqli_query($con,$sql) or die(mysqli_error($con));

}

//获得数据后添加到数据
$getresult=mysqli_query($con,"SELECT * FROM media where media.name='$taskname' AND filename='tts'") or die(mysqli_error($con));
if(mysqli_num_rows($getresult)<=0)
{
mysqli_query($con,"INSERT INTO media(media.name, filename,folderid)VALUES('$taskname','tts','$folderid')") or die(mysqli_error($con));
}

mysqli_query($con,"UNLOCK TABLES");

$sql2="SELECT id FROM media where media.name='$taskname' AND filename='tts'";

$result=mysqli_query($con,$sql2) or die(mysqli_error($con));

if($row=mysqli_fetch_array($result))
{	
	
	$sql = "UPDATE ttssentence SET sentenceid='$row[0]',speed='$getspeed',volume='$getvolume' WHERE name='$taskname'";
	mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
}


/*
$socket	=	new	send_message_to_server($port_conf);	

$msg = "task?id=".$getbelltaskid."&volume=".$task_default_volume;

$socket->send_data($_SESSION['serverip'],$msg);
*/
echo 1;


//调试信息
//$fh=fopen("tianyaluzhang.txt","a"); 
//fwrite($fh,$getonelessonname."|".$getonetimelength."|".$getprepower."|".$getstartdate."|".$getenddate."|".$getonebelltime."|".$getexemodel."|".$priority."|".$getschemename); 
//fclose($fh); 
?>