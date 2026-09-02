<?php
/******************************
		修改打铃单独任务
		使用 AJAX同步
	要求：更新界面方案名称不能修改（注意）
******************************/
header("content-type:text/html; charset=utf-8");

require_once('inc/config.inc.php');

require_once("inc/socket_conf.php");
	//$fp = fopen("get_backup.log","a");
//////////////////////////////修改数据
	$get_id=1;
	if(isset($_GET['get_id']))
	{
	  $get_id = trim($_GET['get_id']);
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}

	$get_noid=1;
	if(isset($_GET['get_noid']))
	{
	  $get_noid = trim($_GET['get_noid']);
	  $arr = array(',' =>'');
	  $get_noid =strtr($get_noid,$arr);
	}
	
	  $get_terminal_value=1;
	if(isset($_GET['get_terminal_value']))
	{
	   $get_terminal_value = trim($_GET['get_terminal_value']);
  
	  $arr = array(',' =>'');
	  $get_terminal_value =strtr($get_terminal_value,$arr);
	}

$getonebelltaskid = "";
if(isset($_GET['getonebelltaskid']))
{
	$getonebelltaskid = trim($_GET['getonebelltaskid']);
}

$getschemename = "";
$pagecharacter='utf-8';
if(isset($_GET['getschemename']))
{
	$getschemename=$_GET['getschemename'];
	$code=strtolower(mb_detect_encoding($getschemename, array('GB2312','UTF-8','GBK','ASCII')));

	if(($code=='gb2312' || $code=='utf-8' || $code=='euc-cn') && $code!=$pagecharacter)
	{
		//$getschemename=iconv($code,$pagecharacter,$getschemename);  
		$getschemename = mb_convert_encoding($getschemename,"utf-8", $code);
	}
}

$task_default_volume = "50";
if(isset($_GET['task_default_volume']))
{
	$task_default_volume = trim($_GET['task_default_volume']);
}

$getprepower = "";
if(isset($_GET['getprepower']))
{
	$getprepower = trim($_GET['getprepower']);
}

$sendmode = "";
if(isset($_GET['sendmode']))
{
	$sendmode = trim($_GET['sendmode']);
}

$getstartdate = "";
if(isset($_GET['getstartdate']))
{
	$getstartdate = trim($_GET['getstartdate']);
}

$getenddate = "";
if(isset($_GET['getenddate']))
{
	$getenddate = trim($_GET['getenddate']);
}
$getexemodel = "";
if(isset($_GET['getexemodel']))
{
	$getexemodel = trim($_GET['getexemodel']);
}
$getonelessonname = "";
if(isset($_GET['getonelessonname']))
{
	$getonelessonname=$_GET['getonelessonname'];
	$codes=strtolower(mb_detect_encoding($getonelessonname, array('GB2312','UTF-8','GBK','ASCII')));
	if(($codes=='gb2312' || $codes=='utf-8' || $codes=='euc-cn') && $codes!=$pagecharacter)
	{
		//$getonelessonname=iconv($codes,$pagecharacter,$getonelessonname);  
		$getonelessonname = mb_convert_encoding($_GET['getonelessonname'],"utf-8", $code);
	}
}
$getonebelltime = "";
if(isset($_GET['getonebelltime']))
{
	$getonebelltime = trim($_GET['getonebelltime']);
	if($getprepower>59)
		{
		$getpowertime=$getprepower/60;
		$getonebellfunctiontime = date('H:i:s',strtotime($getonebelltime."-0 hours - ".$getpowertime."minutes -0 seconds"));
		}
		else
		{
		$getpowertime=$getprepower%60;
		$getonebellfunctiontime = date('H:i:s',strtotime($getonebelltime."-0 hours - 0 minutes -".$getpowertime."seconds"));
		}
}
$getonebellname = "";
$tasktype=1;
if(isset($_GET['getonebellname']))
{
	$getonebellname = trim($_GET['getonebellname']);
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
	
	$analysis_tree_group_ids = explode(",",$analysis_tree_group_strings);
}

//////////////////////////获取用户的优先级
//$sql = "SELECT usergroup.level FROM usergroup WHERE usergroup.id=";
//$sql.= "(SELECT book_admin.usergroupid FROM book_admin WHERE book_admin.username='$_SESSION[username]')";
$userid=$_SESSION['userid'];
$username=$_SESSION['username'];
$sql = "SELECT book_admin.id, usergroup.level FROM book_admin,usergroup WHERE ";
$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$username' ";

$result = mysqli_query($con,$sql) or die(mysqli_error($con));
$row = mysqli_fetch_array($result);

//获取优先级
$priority = 3;

if(isset($_GET['task_priority_text']))
{
	$priority = trim($_GET['task_priority_text']);
}


	
	$task_user_id = trim($row['id']);

	$key_sql = "SELECT DISTINCT task_user_id FROM task WHERE task.info = '$getschemename' AND task.tasktype IN(1,15)";
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	if($key_row = mysqli_fetch_array($key_result))
	{
		$task_user_id = trim($key_row['task_user_id']);
	}

	$terminallistid = explode(",",$getterminalid);
	for($i=0;$i<count($terminallistid);$i++)
	{
		$temp = (int)$terminallistid[$i];
		$sql = "SELECT id FROM userterminal WHERE userid='$task_user_id' AND terminalid='$temp'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if( mysqli_num_rows($result) <=0 )
		{
			$sqls="INSERT INTO userterminal(userid,terminalid) VALUES('$task_user_id','$temp')";
			mysqli_query($con,$sqls)or die(mysqli_error($con));
		}
	}
//读取任务用户ID比较若相同则修改 不同则不修改
/*		
$task_userid_sql = "SELECT task.priority FROM task WHERE task.task_user_id = '$task_user_id' AND task.taskid = '$getonebelltaskid' ";

$task_userid_result = mysqli_query($con,$task_userid_sql) or die(mysqli_error($con));

if(mysqli_num_rows($task_userid_result) <= 0)
{
	if($getonebelltaskid != -1)
	{
		$original_task_priority_result = mysqli_query($con,"SELECT task.priority FROM task WHERE task.taskid = '$getonebelltaskid'") or die(mysqli_error($con));
		
		$original_task_priority_row = mysqli_fetch_array($original_task_priority_result);
		
		$priority = trim($original_task_priority_row['priority']);
		
		@mysqli_free_result($original_task_priority_result);
		
		@mysqli_free_result($task_userid_result);
		
		unset($original_task_priority_row);
	}
}
else
{
	@mysqli_free_result($task_userid_result);
	
	unset($task_userid_sql);
}

@mysqli_free_result($result);

unset($sql,$row);
*/
//fwrite($fp,$priority);
/////////////////////////更新方案状态

$sql = "UPDATE task SET projectstate = '0' WHERE task.info = '$getschemename' AND task.tasktype IN(1,9,15)";

mysqli_query($con,$sql) or die(mysqli_error($con));

unset($sql);
//////////////////////////插入单个任务

if($getonebelltaskid == -1)
{	
	//（方案名+任务名）=唯一性
$sqls = "SELECT * FROM task WHERE task.info = '$getschemename' AND task.tasktype IN(1,15) AND taskname = '$getonelessonname'";

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

	////////////////////////////////////锁定数据库
	mysqli_query($con,"LOCK TABLE task WRITE");	
	//////////////////////////////////当添加新任务时插入数据
	$sql = "INSERT INTO task (taskname, israndomplay, projectstate, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate, ";
	$sql.= "playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, info, defaultvolume,task_user_id) ";
	$sql.= "VALUES('$getonelessonname','0','0','$selectnum','$getonetimelength','$getprepower','$sendmode','0','$getstartdate','$getenddate', ";
	$sql.= "'$getonebelltime','$getexemodel','$priority','$tasktype','0','0','0','0','0','0','$getschemename', '$task_default_volume' ,'$task_user_id') ";	

	mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
	
	$sql = "SELECT MAX(taskid) FROM task";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	if($row = mysqli_fetch_array($result))
	{
		$getbelltaskid = $row[0];
	}
	@mysqli_free_result($result);
	unset($row,$sql);

	mysqli_query($con,"LOCK TABLE mediaoftask WRITE");
	
	$sql = "INSERT INTO mediaoftask (mediaid,taskid) VALUES('$getonebellname','$getbelltaskid')";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	unset($sql);

	if($getprepower > 0)
	{
		mysqli_query($con,"LOCK TABLE task WRITE");
		$sql = "INSERT INTO task (taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate, ";
		$sql.= "playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, info, defaultvolume,task_user_id,sec_task_id) ";
		$sql.= "VALUES('$getonelessonname','0','$selectnum','$getonetimelength','$getprepower','$sendmode','0','$getstartdate','$getenddate', ";
		$sql.= "'$getonebellfunctiontime','$getexemodel','$priority','9','0','0','0','0','0','0','$getschemename', '$task_default_volume','$task_user_id','$getbelltaskid') ";
		mysqli_query($con,$sql) or die(mysqli_error($con));
		unset($sql);
		
		$sql = "SELECT MAX(taskid) FROM task";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if($row = mysqli_fetch_array($result))
		{
			$getfunctiontaskid = $row[0];
		}
		@mysqli_free_result($result);
		unset($row,$sql);
	}
	mysqli_query($con,"UNLOCK TABLES");
	$terminallistid = explode(",",$getterminalid);
	for($i=0;$i<count($terminallistid);$i++)
	{
		if(is_numeric($terminallistid[$i]))
		{
		//	mysqli_query($con,"LOCK TABLE terminaloftask WRITE,mediaofterminal WRITE");
			$temp = (int)$terminallistid[$i];
			//$sql = "INSERT INTO terminaloftask (taskid, terminalid) VALUES('$getbelltaskid','$temp')";
		//	mysqli_query($con,"INSERT INTO mediaofterminal (mediaid,terminalid,taskid) VALUES( '$getonebellname','$temp','$getbelltaskid')");

			$sql = "INSERT INTO terminaloftask(taskid,terminalid,groupid) VALUES('$getbelltaskid','$temp','$analysis_tree_group_ids[$i]')";
			
			mysqli_query($con,$sql) or die(mysqli_error($con));
			unset($sql);
			if($getprepower > 0)
			{
			//	mysqli_query($con,"LOCK TABLE terminaloftask WRITE,mediaofterminal WRITE");
			
				$temp = (int)$terminallistid[$i];
			
				//$sql = "INSERT INTO terminaloftask (taskid, terminalid) VALUES('$getfunctiontaskid','$temp')";	
				$sql = "INSERT INTO terminaloftask(taskid,terminalid,groupid) VALUES('$getfunctiontaskid','$temp','$analysis_tree_group_ids[$i]')";
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);
			}
			
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
											
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getbelltaskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												
												
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getfunctiontaskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												
												}
								}			
											
											
											
											
						 }
			
			
		}
		continue;
	}
		
	$socket	=	new	send_message_to_server($port_conf);	

	$msg = "task?id=".$getbelltaskid."&volume=".$task_default_volume;

	$socket->send_data($_SESSION['serverip'],$msg);	
	
	echo $getbelltaskid;
}
else if($getonebelltaskid != -1)
{
	
	//此要求（方案名称+任务名称+任务类型==唯一）
	$sql = "SELECT 	* FROM task WHERE task.taskname = '$getonelessonname' AND task.info = '$getschemename' AND task.tasktype IN(1,15) AND task.taskid != '$getonebelltaskid'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	if($row = mysqli_fetch_array($result))
	{
		if($row[0] > 0)
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
	}
	
/*		
//（方案名+任务名）=唯一性
$sqls = "SELECT * FROM task WHERE task.info = '$getschemename' AND task.tasktype IN(1,15) AND task_user_id!='$userid' AND task.taskid != '$getonebelltaskid'";
$results = mysqli_query($con,$sqls) or die(mysqli_error($con));

if(mysqli_num_rows($results) > 0)
{
	@mysqli_free_result($results);
	unset($sqls);
	echo 0;
	exit;
}
else
{
	@mysqli_free_result($results);
	unset($sqls);
}
*/	
	
	
	$sql = "SELECT prepower FROM task WHERE task.taskid = '$getonebelltaskid'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
	if($row = mysqli_fetch_array($result))
	{
		if($row[0] == 0 && $getprepower > 0)
		{
		//	mysqli_query($con,"LOCK TABLE task WRITE");
		
			$sql = "INSERT INTO task (taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate, ";
			$sql.= "playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, info, defaultvolume,task_user_id,sec_task_id ) ";
			$sql.= "VALUES('$getonelessonname','0','$selectnum','$getonetimelength','$getprepower','$sendmode','0','$getstartdate','$getenddate', ";
			$sql.= "'$getonebellfunctiontime','$getexemodel','$priority','9','0','0','0','0','0','0','$getschemename','$task_default_volume','$task_user_id','$getonebelltaskid')";
				
			mysqli_query($con,$sql) or die(mysqli_error($con));			
			
			unset($sql);	
		
			$sql = "SELECT MAX(taskid) FROM task";
			
			$result = mysqli_query($con,$sql) or die(mysqli_error($con));
			
			if($row = mysqli_fetch_array($result))
			{
				$getfunctionnewtaskid = $row[0];
			}
			
			@mysqli_free_result($result);
			
			unset($row,$sql);

			$terminallistid = explode(",",$getterminalid);
			
			for($i=0;$i<count($terminallistid);$i++)
			{
				if(is_numeric($terminallistid[$i]))
				{
				//	mysqli_query($con,"LOCK TABLE terminaloftask WRITE");	
					
					$temp = (int)$terminallistid[$i];

					$sql = "INSERT INTO terminaloftask(taskid,terminalid,groupid) VALUES('$getfunctionnewtaskid','$temp','$analysis_tree_group_ids[$i]')";
					
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
											
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getfunctionnewtaskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
	
												}
								}			
					
						 }
					
					
				}
				continue;
			}	
			//echo 1;
		}
		else if($row[0] > 0 && $getprepower > 0)
		{		
			$sql = "SELECT taskname, info	FROM task WHERE task.taskid = '$getonebelltaskid'";
			
			$result = mysqli_query($con,$sql) or die(mysqli_error($con));
			
			unset($sql);
			
			if($row = mysqli_fetch_array($result))
			{
				$sql = "select taskid from task where task.info = '$row[info]' and task.taskname = '$row[taskname]' and task.tasktype = '9'";
			
				$resultfunctionid =mysqli_query($con,$sql) or die(mysqli_error($con));
				if($rowfunctionid = mysqli_fetch_array($resultfunctionid))
				{
					$getfunctiontaskid = $rowfunctionid['taskid'];
				}
			}
			mysqli_free_result($resultfunctionid);
			mysqli_free_result($result);
			unset($sql,$row,$rowfunctionid);
			
		//	mysqli_query($con,"LOCK TABLE task WRITE");
			
			$sql = "update task set taskname = '$getonelessonname',timelengthtype='$selectnum', timelength = '$getonetimelength', prepower = '$getprepower',datasendmodel = '$sendmode', state = '0', ";
			
			$sql.= "startdate = '$getstartdate', enddate = '$getenddate', playtime = '$getonebellfunctiontime' , exemodel = '$getexemodel' , ";
			$sql.= "info = '$getschemename' , defaultvolume = '$task_default_volume' , ";
			
			$sql.= "priority = '$priority',sec_task_id='$getonebelltaskid',task_user_id='$task_user_id',offlinestate='0' where task.taskid = '$getfunctiontaskid'";
			
			mysqli_query($con,$sql) or die(mysqli_error($con));
			
			unset($sql);

		//	mysqli_query($con,"LOCK TABLE terminaloftask WRITE");
			
			//$sql = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getfunctiontaskid'";
			
			//mysqli_query($con,$sql) or die(mysqli_error($con));
						for($c=0;$c<strlen($get_noid);$c++)
						{
						
						if(substr($get_noid,$c,1)=="_")
						{
						$a=substr($get_noid,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noid,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noid,$c-$get_position,$get_position);
					
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getfunctiontaskid' AND groupid ='$getid'";
						  
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
						
				     
						}
						
						}
						for($z=0;$z<strlen($get_id);$z++)
						{
						//alert(z);
						if(substr($get_id,$z,2)=="::")
						{
						
						
						$position=$z+2;
                  
						
						}
						if(substr($get_id,$z,1)=="|")
						{
						$position2=$z;
						$get_position =$position2-$position;
					
						$getid = substr($get_id,$z-$get_position,$get_position);
						$sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getfunctiontaskid' AND terminalid ='$getid'"; 
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
						}
						
						}
			$terminallistid = explode(",",$getterminalid);
			for($i=0;$i<count($terminallistid);$i++)
			{
				if(is_numeric($terminallistid[$i]))
				{
				//	mysqli_query($con,"LOCK TABLE terminaloftask WRITE");
					$getoldfunctionid7="";
					$temp = (int)$terminallistid[$i];
					 $group =(int)$analysis_tree_group_ids[$i];
					//$sql = "INSERT INTO terminaloftask (taskid, terminalid) VALUES('$getfunctiontaskid','$temp')";
					
					 $sql = "SELECT taskid FROM terminaloftask WHERE taskid = '$getfunctiontaskid' AND terminalid='$temp' AND groupid = '$group'";
					
					$result = mysqli_query($con,$sql) or die(mysqli_error($con));
					
					if($row = mysqli_fetch_array($result))
					{
						$getoldfunctionid7 = $row['taskid'];
						
					}
					@mysqli_free_result($result);
					
					unset($sql,$row);
					if($getoldfunctionid7==$getfunctiontaskid)
					{
					
					}
					else
					{
						$sql = "INSERT INTO terminaloftask(taskid,terminalid,groupid) VALUES('$getfunctiontaskid','$temp','$analysis_tree_group_ids[$i]')";
					mysqli_query($con,$sql) or die(mysqli_error($con));
					
					unset($sql);
					}
					
				
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
	
												}
								}							
						 }
				}
				continue;
			}	
			//echo 1;
		}
		else if($row[0] > 0 && $getprepower == 0)
		{
			$sql = "SELECT taskname, info FROM task WHERE task.taskid = '$getonebelltaskid'";
			$result = mysqli_query($con,$sql) or die(mysqli_error($con));
			unset($sql);
			if($row = mysqli_fetch_array($result))
			{
				$sql = "select taskid from task where task.info = '$row[info]' and task.taskname = '$row[taskname]' and task.tasktype = '9'";
				$resultfunctionid =mysqli_query($con,$sql) or die(mysqli_error($con));
				if($rowfunctionid = mysqli_fetch_array($resultfunctionid))
				{
					$getfunctiontaskid = $rowfunctionid['taskid'];
				}
			}
			
			@mysqli_free_result($resultfunctionid);
			
			@mysqli_free_result($result);
			
			unset($sql,$row,$rowfunctionid);
			
		//	mysqli_query($con,"LOCK TABLE task WRITE");
			
			$sql = "DELETE FROM task WHERE	task.taskid = '$getfunctiontaskid'";
			mysqli_query($con,$sql) or die(mysqli_error($con));
			unset($sql);
			
		//	mysqli_query($con,"LOCK TABLE terminaloftask WRITE");
			
			$sql = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getfunctiontaskid'";
			
			mysqli_query($con,$sql) or die(mysqli_error($con));
			
			unset($sql);
			//echo 1;	
		}
	}

//	mysqli_query($con,"LOCK TABLE mediaoftask WRITE");
	
	$sql = "UPDATE mediaoftask SET  mediaid = '$getonebellname' WHERE mediaoftask.taskid = '$getonebelltaskid' ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));

	unset($sql);

//	mysqli_query($con,"LOCK TABLE task WRITE");
	
	$sql = "update task set taskname = '$getonelessonname',timelengthtype='$selectnum', timelength = '$getonetimelength', prepower = '$getprepower',datasendmodel = '$sendmode', ";
	$sql.= "state = '0', startdate = '$getstartdate', enddate = '$getenddate', playtime = '$getonebelltime' , exemodel = '$getexemodel' ,";
	$sql.= "info = '$getschemename' , defaultvolume = '$task_default_volume' , priority = '$priority',task_user_id='$task_user_id' ,offlinestate='0' where task.taskid = '$getonebelltaskid' ";
	mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
	
//	mysqli_query($con,"LOCK TABLE terminaloftask WRITE,mediaofterminal WRITE,task WRITE");
	
	//$sql = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getonebelltaskid'";
	
	//mysqli_query($con,$sql) or die(mysqli_error($con));
	
//	unset($sql);
					for($c=0;$c<strlen($get_noid);$c++)
						{
						
						if(substr($get_noid,$c,1)=="_")
						{
						$a=substr($get_noid,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noid,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noid,$c-$get_position,$get_position);
					//	mysqli_query($con,"DELETE	FROM mediaofterminal WHERE mediaofterminal.taskid = '$getonebelltaskid'");  
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getonebelltaskid' AND groupid ='$getid'";
						
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
						
				     
						}
						
						}
						for($z=0;$z<strlen($get_id);$z++)
						{
						//alert(z);
						if(substr($get_id,$z,2)=="::")
						{
						
						
						$position=$z+2;
                  
						
						}
						if(substr($get_id,$z,1)=="|")
						{
						$position2=$z;
						$get_position =$position2-$position;
						
						
						$getid = substr($get_id,$z-$get_position,$get_position);
						//  mysqli_query($con,"DELETE FROM mediaofterminal WHERE mediaofterminal.taskid = '$getonebelltaskid'");  
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getonebelltaskid' AND terminalid ='$getid'";
						  
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);

						}
						
						}
	/*
	 $getsqls = "SELECT offlineparam FROM task WHERE taskid = '$getonebelltaskid'";
					
		$getresults = mysqli_query($con,$getsqls) or die(mysqli_error($con));
		
		if($getrows = mysqli_fetch_array($getresults))
		{
			$offlineparam = $getrows['offlineparam'];     
		}
	
	 */
	$terminallistid = explode(",",$getterminalid);
	for($i=0;$i<count($terminallistid);$i++)
	{
	
		if(is_numeric($terminallistid[$i]))
		{
			//mysqli_query($con,"LOCK TABLE terminaloftask WRITE,mediaofterminal WRITE");
			$getoldfunctionid6="";
			$temp = (int)$terminallistid[$i];
	        $group =(int)$analysis_tree_group_ids[$i];
			//$sql = "INSERT INTO terminaloftask (taskid, terminalid) VALUES('$getonebelltaskid','$temp')";
			 $sql = "SELECT taskid FROM terminaloftask WHERE taskid = '$getonebelltaskid' AND terminalid='$temp' AND groupid = '$group'";
					
					$result = mysqli_query($con,$sql) or die(mysqli_error($con));
					
					if($row = mysqli_fetch_array($result))
					{
						$getoldfunctionid6 = $row['taskid'];     
					}
					@mysqli_free_result($result);
					
					unset($sql,$row);
			
			if($getoldfunctionid6 ==$getonebelltaskid)
			{
			
			}
			else
			{
				//	mysqli_query($con,"INSERT INTO mediaofterminal (mediaid,terminalid,taskid,offlineparam) VALUES( '$getonebellname','$temp','$getonebelltaskid','$offlineparam')") or die(mysqli_error($con));
				$sql = "INSERT INTO terminaloftask(taskid,terminalid,groupid) VALUES('$getonebelltaskid','$temp','$analysis_tree_group_ids[$i]')";
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);
			}
			//	mysqli_query($con,"UPDATE mediaofterminal SET mediaid='$getonebellname',offlineparam='$offlineparam' WHERE taskid='$getonebelltaskid' AND terminalid='$temp'") or die(mysqli_error($con));
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
							$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getonebelltaskid' AND terminalid ='$temp'";
							mysqli_query($con,$sql) or die(mysqli_error($con));
							unset($sql);
									
						}
					}			
					
				 }
			
		}
		continue;
	}

//fclose($fp);	
$socket	=	new	send_message_to_server($port_conf);	

$msg = "task?id=".$getonebelltaskid."&volume=".$task_default_volume;

$socket->send_data($_SESSION['serverip'],$msg);	
		
echo 1;
}
mysqli_query($con,"UNLOCK TABLES");
?>