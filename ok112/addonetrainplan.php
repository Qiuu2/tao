<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

mysqli_query($con,"set names 'utf8'");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once("inc/socket_conf.php");

$getschemename = "";

if(isset($_GET['getschemename']))
{
//	$getschemename=iconv("GB2312","utf-8",$_GET['getschemename']);
	//
	if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE"))
	$getschemename = mb_convert_encoding(trim($_GET['getschemename']),"utf-8","gb2312");
	else
		$getschemename=$_GET['getschemename'];
}

$getprepower = "";

if(isset($_GET['getprepower']))
{
	$getprepower = trim($_GET['getprepower']);
}
//添加声音

$task_default_volume = 50;

if(isset($_GET['task_default_volume']))
{
	$task_default_volume = trim($_GET['task_default_volume']);
}

$getstartdate = "";
if(isset($_GET['getstartdate']))
{
	$getstartdate = trim($_GET['getstartdate']);
}
$getenddate = "";
if(isset($_GET['getenddate']))
{
	$getenddate = $_GET['getenddate'];
}

  $get_terminal_value="";
	if(isset($_GET['get_terminal_value']))
	{
	   $get_terminal_value = trim($_GET['get_terminal_value']);
  
	  $arr = array(',' =>'');
	  $get_terminal_value =strtr($get_terminal_value,$arr);
	}


$getexemodel = "";
if(isset($_GET['getexemodel']))
{
	$getexemodel = trim($_GET['getexemodel']);
}
$getonelessonname = "";
if(isset($_GET['getonelessonname']))
{
	if(strpos($_SERVER["HTTP_USER_AGENT"],"MSIE"))
	 $getonelessonname = mb_convert_encoding($_GET['getonelessonname'],"utf-8", "gb2312");
	else 
	$getonelessonname=$_GET['getonelessonname'];
}
$getonebelltime = "";
if(isset($_GET['getonebelltime']))
{
	$getonebelltime = trim($_GET['getonebelltime']);
	if($getprepower != 0)
	{
	
		if($getprepower>59)
		{
		$getpowertime=$getprepower/60;
		$functiontime = date('H:i:s',strtotime($getonebelltime."-0 hours - ".$getpowertime."minutes -0 seconds"));
		}
		else
		{
		$getpowertime=$getprepower%60;
		$functiontime = date('H:i:s',strtotime($getonebelltime."-0 hours - 0 minutes -".$getpowertime."seconds"));
		}
	}
}
$getonebellname = "";
if(isset($_GET['getonebellname']))
{
	$getonebellname = $_GET['getonebellname'];
}
$getonetimelength = "";
if(isset($_GET['getonetimelength']))
{
	$getonetimelength = trim($_GET['getonetimelength']);
}
$selectnum="";
if(isset($_GET['selectnum']))
{
	$selectnum = trim($_GET['selectnum']);
}

$getterminalid = "";
if(isset($_GET['getterminalid']))
{
	$getterminalid = trim($_GET['getterminalid']);
}

$analysis_tree_group_strings = "";

if(isset($_GET['analysis_tree_group_strings']))
{
	$analysis_tree_group_strings = trim($_GET['analysis_tree_group_strings']);
	
	$analysis_tree_group_strings = explode(",",$analysis_tree_group_strings);
}
$userid=$_SESSION['userid'];
//（方案名+任务名）=唯一性
$sql = "SELECT 	* FROM task WHERE task.taskname = '$getonelessonname' AND task.info = '$getschemename' AND task.tasktype = 1";

$result = mysqli_query($con,$sql) or die(mysqli_error($con));

if(mysqli_num_rows($result) > 0)
{
	@mysqli_free_result($result);
	
	unset($sql);
	
	echo 0;
	
	exit;
}
else
{
	@mysqli_free_result($result);
	
	unset($sql);
}
//获取用户的优先级
$sql="SELECT usergroup.level FROM usergroup WHERE usergroup.id=(SELECT book_admin.usergroupid FROM book_admin WHERE book_admin.username='$_SESSION[username]')";

$result=mysqli_query($con,$sql) or die(mysqli_error($con));

$row=mysqli_fetch_array($result);

//获取任务优先级
$priority = 3;

if(isset($_GET['task_priority_text']))
{
	$priority = trim($_GET['task_priority_text']);
}

$priority = trim($row['level'])*10 + $priority;

@mysqli_free_result($result);

unset($sql,$row);

//获得数据后添加到数据

mysqli_query($con,"LOCK TABLE task WRITE");//锁定此表

$sql = "INSERT INTO task (taskname, israndomplay, projectstate, timelengthtype, timelength, prepower, datasendmodel, state,startdate,enddate, ";
$sql.= " playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, info,defaultvolume,task_user_id ) ";
$sql.= "VALUES('$getonelessonname','0','0','$selectnum','$getonetimelength','$getprepower','0','0', '$getstartdate', '$getenddate', ";
$sql.= "'$getonebelltime', '$getexemodel', '$priority', '1','0','0','0', '0','0','0','$getschemename','$task_default_volume','$userid') ";

$result = mysqli_query($con,$sql) or die(mysqli_error($con));

if($result)
{
	$sql = "SELECT 	MAX(taskid) FROM task ";
	
	$resultmedia = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($resultmedia))
	{
		$getbelltaskid = $row[0]; 
	}
	@mysqli_free_result($resultmedia);
	@mysqli_free_result($result);
	unset($sql,$row);
	///////////////////////////////////////////当预开电源不为零时 则添加功放 及功放终端及媒体
	if($getprepower != 0)
	{
		//mysqli_query($con,"LOCK TABLE task WRITE");//锁定此表
		
		$sql = "INSERT INTO task (taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state,startdate,enddate, ";
		$sql.= " playtime, exemodel, priority, tasktype,  channel, bandrate, samplerate, cmd, cmdargs, playfileid, info ,defaultvolume,task_user_id,sec_task_id) ";
		$sql.= "VALUES('$getonelessonname','0','$selectnum','$getonetimelength','$getprepower','0','0', '$getstartdate', '$getenddate', ";
		$sql.= "'$functiontime', '$getexemodel', '$priority', '9','0','0','0', '0','0','0','$getschemename','$task_default_volume','$userid','$getbelltaskid')";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		unset($sql);
		if($result)
		{
			$sql = "SELECT 	MAX(taskid) FROM task";
			$resultfunction = mysqli_query($con,$sql) or die(mysqli_error($con));
			if($row = mysqli_fetch_array($resultfunction))
			{
				$getfunctiontaskid = $row[0];
				
				@mysqli_free_result($resultfunction);
				
				unset($sql,$row);
			}
//			///////////////////////////////////////////添加媒体
//			mysqli_query($con,"LOCK TABLE mediaoftask WRITE");//锁定此表
//	
//			$sql = "INSERT INTO mediaoftask ( mediaid, taskid)VALUES('$getonebellname', '$getfunctiontaskid')";
//			mysqli_query($con,$sql) or die(mysqli_error($con));
//			unset($sql);
			//////////////////////////////////////////添加终端
			$terminallistid = explode(",",$getterminalid);
            
			for($i=0;$i<count($terminallistid);$i++)
			{
				if(is_numeric($terminallistid[$i]))
				{
					mysqli_query($con,"LOCK TABLE terminaloftask WRITE");//锁定此表
					
					$temp = (int)$terminallistid[$i];
					
					//$sql = "INSERT INTO terminaloftask (taskid, terminalid) VALUES('$getfunctiontaskid', '$temp')";
					
					$sql = "INSERT INTO terminaloftask(taskid,terminalid,groupid) VALUES('$getfunctiontaskid','$temp','$analysis_tree_group_strings[$i]')";
					
					mysqli_query($con,$sql) or die(mysqli_error($con));
					
					unset($sql);
					
					
					$sql = "INSERT INTO terminaloftask(taskid,terminalid,groupid) VALUES('$getbelltaskid','$temp','$analysis_tree_group_strings[$i]')";
			
					mysqli_query($con,$sql) or die(mysqli_error($con));
			
					unset($sql);
					
					for($k=0;$k<strlen($get_terminal_value);$k++)
						{
						
						if(substr($get_terminal_value,$k,2)=="::")
											{
											$position=$k+2;
											
											}
								if(substr($get_terminal_value,$k,1)=="|")
								{
								  $position2 = $k;
								  $position3 = $position2-$position;
											
											$a=substr($get_terminal_value,$k-$position3,$position3);
											
											if($a==$temp)
												{
											
												$area = substr($get_terminal_value,$k+1,16);
											
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getfunctiontaskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												
												
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getbelltaskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												
												}
								}			
											
											
											
											
						 }
					
					
					
					
				}
				continue;
			}
		}
	}
	///////////////////////////////////////////添加媒体
	mysqli_query($con,"LOCK TABLE mediaoftask WRITE");
	
	$sql = "INSERT INTO mediaoftask ( mediaid, taskid)VALUES('$getonebellname', '$getbelltaskid')";

	mysqli_query($con,$sql) or die(mysqli_error($con));

	//////////////////////////////////////////添加终端

	
	
	
	
}
mysqli_query($con,"UNLOCK TABLES");

$socket	=	new	send_message_to_server($port_conf);	

$msg = "task?id=".$getbelltaskid."&volume=".$task_default_volume;

$socket->send_data($_SESSION['serverip'],$msg);

echo 1;


//调试信息
//$fh=fopen("tianyaluzhang.txt","a"); 
//fwrite($fh,$getonelessonname."|".$getonetimelength."|".$getprepower."|".$getstartdate."|".$getenddate."|".$getonebelltime."|".$getexemodel."|".$priority."|".$getschemename); 
//fclose($fh); 
?>