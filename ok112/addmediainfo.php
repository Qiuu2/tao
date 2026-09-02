<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

mysqli_query($con,"set names 'utf8'");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once("inc/socket_conf.php");

$getmedianame = "";
if(isset($_GET['getmedianame']))
{
	if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE"))
	$getmedianame = mb_convert_encoding(trim($_GET['getmedianame']),"utf-8","gb2312");
	else
		$getmedianame=$_GET['getmedianame'];
}
$flag = "";
if(isset($_GET['flag']))
{
	
		$flag=trim($_GET['flag']);
}
$userid=$_SESSION['userid'];
$id = "";
if(isset($_GET['id']))
{
	
		$id=trim($_GET['id']);
}
//获取用户的优先级
if($flag==1)
{
	$sql = "SELECT count(mediaseq) FROM ttssentence WHERE ttssentence.sentenceid='$id' ORDER BY mediaseq ASC";
		
	$result = mysqli_query($con,$sql);
	
	while($row = mysqli_fetch_array($result))
	{
		echo $row[0];
	}
}
else if($flag==2)
{

$i = "";
if(isset($_GET['i']))
{
	
		$i=trim($_GET['i']);
}


$sql = "SELECT ttssentence.type,mediaseq,mediaid FROM ttssentence WHERE ttssentence.sentenceid='$id' AND ttssentence.mediaseq='$i'";
	
$result = mysqli_query($con,$sql);

while($row = mysqli_fetch_array($result))
{
	
	$row1=$row[1];
	if($row[0]==0)
	{
	$sql2="SELECT DISTINCT media.name FROM media,ttssentence WHERE media.id IN (SELECT mediaid FROM ttssentence WHERE ttssentence.sentenceid='$id' AND TYPE='0'AND mediaseq='$row1')";	
	}
	else if($row[0]==1)
	{
	$sql2="SELECT DISTINCT ttstext.name FROM ttstext,ttssentence WHERE ttstext.seq IN (SELECT mediaid FROM ttssentence WHERE ttssentence.sentenceid='$id'AND TYPE='1'AND mediaseq='$row1')";
	}
	else
	{
	$sql2="SELECT DISTINCT content FROM ttssentence WHERE  ttssentence.sentenceid='$id'AND TYPE='2'AND mediaseq='$row1'";
	
	}
	$results = mysqli_query($con,$sql2) or die(mysqli_error($con));
	
	while($getrows = mysqli_fetch_array($results)) 
	{
	
		//echo mb_convert_encoding(trim($getrows[0]),"utf-8","gb2312");
		echo $row[2]."#".$row[0]."$".$getrows[0];
		
	}

}
}
else if($flag==3)
{
	$sql = "SELECT seq FROM ttstext WHERE id='$id'";	
	$result = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($result))
	{
		echo $row[0];
	}
}
else if($flag==4)
{
	$sql = "SELECT filename FROM media WHERE id='$id'";	
	$result = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($result))
	{
		if($row[0]=='tts')
		echo "tts+".$id;
		else
		echo $id;
	}
}
else if($flag==5)
{
	$sql = "SELECT speed,volume FROM ttssentence WHERE ttssentence.name='$getmedianame' AND mediaseq='$id'";	
	$result = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($result))
	{
		
		echo $row[0]."|".$row[1];
		
	}
}
else if($flag==6)
{
	$sqls = "SELECT count(mediaseq) FROM ttssentence WHERE ttssentence.name='$getmedianame'";	
	$results = mysqli_query($con,$sqls);
	if($rows = mysqli_fetch_array($results))
	{
		if($rows[0]>1)
		{
			$sql = "DELETE FROM ttssentence WHERE ttssentence.name='$getmedianame' AND mediaseq='$id'";	
			mysqli_query($con,$sql);
		
			$sql2 = "SELECT mediaseq,id FROM ttssentence WHERE ttssentence.name='$getmedianame' AND mediaseq>'$id'ORDER BY mediaseq asc";	
			$result2=mysqli_query($con,$sql2);
			while($row2 = mysqli_fetch_array($result2))
			{	
				$getrow=$row2[0]-1;
				mysqli_query($con,"UPDATE ttssentence SET mediaseq='$getrow' WHERE ttssentence.id='$row2[1]'");
			}
		
		}
		else if($rows[0]==1)
		{
			$sql = "DELETE FROM ttssentence WHERE ttssentence.name='$getmedianame' AND mediaseq='$id'";	
			mysqli_query($con,$sql);
			$sqla = "DELETE FROM media WHERE media.name='$getmedianame'";	
			mysqli_query($con,$sqla);
		}
	}
	echo 1;
}
//获得数据后添加到数据

//调试信息
//$fh=fopen("tianyaluzhang.txt","a"); 
//fwrite($fh,$getonelessonname."|".$getonetimelength."|".$getprepower."|".$getstartdate."|".$getenddate."|".$getonebelltime."|".$getexemodel."|".$priority."|".$getschemename); 
//fclose($fh); 
?>