<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once("inc/socket_conf.php");



$getprepower = "";

if(isset($_GET['getprepower']))
{
	$getprepower = trim($_GET['getprepower']);
}
//添加声音
$sendmode="";
if(isset($_GET['sendmode']))
{
	$sendmode = trim($_GET['sendmode']);
}
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
//$fh=fopen("tianyaluzhang.txt","a"); 
if(isset($_GET['getonelessonname']))
{
	$getonelessonname=$_GET['getonelessonname'];

	$codes=strtolower(mb_detect_encoding($getonelessonname, array('GB2312','UTF-8','GBK','ASCII'),true));
	if(($codes=='gb2312' || $codes=='utf-8' || $codes=='euc-cn') && $codes!=$pagecharacter)
	{

		if($codes=='utf-8')
		{
			  
			$getonelessonname = mb_convert_encoding($_GET['getonelessonname'],"utf-8", $code);
		}
		else
		{
			$getonelessonname=iconv($codes,$pagecharacter,$getonelessonname);
		}

		//fwrite($fh,$getonelessonname."|".$_GET['getonelessonname']."|".$codes);
	}
}
//fclose($fh);

$getschemename2 = "";
$pagecharacter='utf-8';
//$fh=fopen("tianyaluzhang.txt","a"); 

if(isset($_GET['getschemename']))
{
	$getschemename2=$_GET['getschemename'];

	$code=strtolower(mb_detect_encoding($getschemename2, array('GB2312','UTF-8','GBK','ASCII')));

	if(($code=='gb2312' || $code=='utf-8' || $code=='euc-cn') && $code!=$pagecharacter)
	{
		$getschemename2=iconv($code,$pagecharacter,$getschemename2);  
		//$getschemename = mb_convert_encoding($getschemename2,"utf-8", $code);
	}
}

//$fh=fopen("tianyaluzhang.txt","a"); 
//fwrite($fh,$getonelessonname."|".$getonetimelength."|".$getprepower."|".$getstartdate."|".$getenddate."|".$getonebelltime."|".$getexemodel."|".$getschemename2."|".$getschemename); 
//fclose($fh); 

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
$tasktype=1;

if(isset($_GET['getonebellname']))
{
	$getonebellname = $_GET['getonebellname'];
	
	if(strstr($getonebellname,"tts")==true)
	{
	//$tasktype=15;
	$getonebellname=substr($getonebellname,4);
	}
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
$sql = "SELECT 	* FROM task WHERE task.taskname = '$getonelessonname' AND task.info = '$getschemename2' AND task.tasktype IN(1,15)";

$result = mysqli_query($con,$sql) or die(mysqli_error($con));

if(mysqli_num_rows($result) > 0)
{
	mysqli_free_result($result);
	
	unset($sql);
	
	echo 0;
	
	exit;
}
else
{
	mysqli_free_result($result);
	
	unset($sql);
}
//（方案名+任务名）=唯一性
$sqls = "SELECT * FROM task WHERE task.info = '$getschemename2' AND task.tasktype IN(1,15) AND task_user_id!= $userid";

$results = mysqli_query($con,$sqls) or die(mysqli_error($con));

if(mysqli_num_rows($results) > 0)
{
	mysqli_free_result($results);
	
	unset($sqls);
	
	echo 0;
	
	exit;
}
else
{
	mysqli_free_result($results);
	unset($sqls);
}

//获取用户的优先级
$sql="SELECT usergroup.level FROM usergroup WHERE usergroup.id=(SELECT book_admin.usergroupid FROM book_admin WHERE book_admin.id=$userid)";

$result=mysqli_query($con,$sql) or die(mysqli_error($con));

$row=mysqli_fetch_array($result);

//获取任务优先级
$priority = 3;

if(isset($_GET['task_priority_text']))
{
	$priority = trim($_GET['task_priority_text']);
}


mysqli_free_result($result);

unset($sql,$row);

//获得数据后添加到数据

mysqli_query($con,"LOCK TABLE task WRITE");//锁定此表

$sql = "INSERT INTO task (taskname, israndomplay, projectstate, timelengthtype, timelength, prepower, datasendmodel, state,startdate,enddate, ";
$sql.= " playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, info,defaultvolume,task_user_id ) ";
$sql.= "VALUES('$getonelessonname','0','0','$selectnum','$getonetimelength','$getprepower','$sendmode','0', '$getstartdate', '$getenddate', ";
$sql.= "'$getonebelltime', '$getexemodel', '$priority', '$tasktype','0','0','0', '0','0','0','$getschemename2','$task_default_volume','$userid') ";

$result = mysqli_query($con,$sql) or die(mysqli_error($con));

if($result)
{

	$sqlss = "SELECT max(taskid) FROM task ";

	$getbelltaskid=0;
	$resultmedia = mysqli_query($con,$sqlss) or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($resultmedia))
	{
		$getbelltaskid = $row[0]; 
	}
	//mysqli_free_result($resultmedia);
	
	//unset($sqlss,$row);
	
	///////////////////////////////////////////当预开电源不为零时 则添加功放 及功放终端及媒体
	if($getprepower != 0)
	{
		//mysqli_query($con,"LOCK TABLE task WRITE");//锁定此表
		$sqla = "INSERT INTO task (taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state,startdate,enddate, ";
		$sqla.= " playtime, exemodel, priority, tasktype,  channel, bandrate, samplerate, cmd, cmdargs, playfileid, info ,defaultvolume,task_user_id,sec_task_id) ";
		$sqla.= "VALUES('$getonelessonname','0','$selectnum','$getonetimelength','$getprepower','$sendmode','0', '$getstartdate', '$getenddate', ";
		$sqla.= "'$functiontime', '$getexemodel', '$priority', '9','0','0','0', '0','0','0','$getschemename2','$task_default_volume','$userid','$getbelltaskid')";
	
		$result = mysqli_query($con,$sqla) or die(mysqli_error($con));
		unset($sqla);
		if($result)
		{
			$sqlb = "SELECT MAX(taskid) FROM task";
			$getfunctiontaskid=0;
			$resultfunction = mysqli_query($con,$sqlb) or die(mysqli_error($con));
			if($row = mysqli_fetch_array($resultfunction))
			{
				$getfunctiontaskid = $row[0];
				//mysqli_free_result($resultfunction);
				//unset($sqlb,$row);
			}
		}
	}
	
		mysqli_query($con,"UNLOCK TABLES");
		//	mysqli_query($con,"LOCK TABLES terminaloftask WRITE,mediaofterminal WRITE");

			$terminallistid = explode(",",$getterminalid);
           
			for($i=0;$i<count($terminallistid);$i++)
			{
				if($terminallistid[$i])
				{
				
					$temp = (int)$terminallistid[$i];

				
					if($getprepower != 0)
					{
					$sql = "INSERT INTO terminaloftask(taskid,terminalid,groupid) VALUES('$getfunctiontaskid','$temp','$analysis_tree_group_strings[$i]')";
					mysqli_query($con,$sql) or die(mysqli_error($con));
					unset($sql);
					}
			
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
												if($getprepower != 0)
												{
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getfunctiontaskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												}
												
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getbelltaskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												
												}
								}							
						 }
					
					
					
					
				}
				continue;
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