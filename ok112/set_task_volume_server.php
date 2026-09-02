<?php
	if (!session_id()) session_start();
	
	header("content-type:text/html;charset=utf-8");
	
	require_once("inc/config.inc.php");
	
	require_once("inc/socket_conf.php");
	
	$tasktype = "";
	if(isset($_GET['tasktype']))
	{
		$tasktype = trim($_GET['tasktype']);
	}
	
	$volume = "";
	if(isset($_GET['volume']))
	{
		$volume = trim($_GET['volume']);
	}

	$task_id = "";
	if(isset($_GET['task_id']))
	{
		$task_id = trim($_GET['task_id']);
	}

	if($task_id != "")
	{
		switch($tasktype)
		{
			case 1:
			$bell_task_id = "";
			//取方案名称
			$bell_plan_result = mysqli_query($con,"SELECT DISTINCT info FROM task WHERE task.taskid IN ($task_id)") or die(mysqli_error($con));
			while($bell_plan_row = mysqli_fetch_array($bell_plan_result))
			{
				$bell_id_result = mysqli_query($con,"SELECT taskid FROM task WHERE task.info = '".$bell_plan_row['info']."' ");
				while($bell_id_row = mysqli_fetch_array($bell_id_result))
				{
					if($bell_task_id == "")
					{
						$bell_task_id = $bell_id_row['taskid'];
					}
					else
					{
						$bell_task_id.= ",".$bell_id_row['taskid'];
					}
				}
				@mysqli_free_result($bell_id_result);
				unset($bell_id_row);
			
			  $task_sql = "update task set defaultvolume = '$volume',offlinestate=0 where task.info = '$bell_plan_row[info]'";
			  mysqli_query($con,$task_sql) ;
			}
			
			@mysqli_free_result($bell_plan_result);
			unset($bell_plan_row,$task_sql);
			if(mysqli_error($con))
			{
				echo "0";
			}
			else
			{
				$socket	=	new	send_message_to_server($port_conf);	
				$taskarray = explode(",",$bell_task_id);
				for($i=0;$i<count($taskarray);$i++)
				{
				$msg = "task?state=7&id=".$taskarray[$i]."&volume=".$volume."&type=".$tasktype;
				
				$socket->send_data($_SESSION['serverip'],$msg);
				}
				unset($bell_task_id);
			
				echo "1";
			}
			break;
			case 10:
			mysqli_query($con,"UPDATE task SET defaultvolume = '$volume',offlinestate=0 WHERE taskid IN ($task_id) AND tasktype = '$tasktype'");
			if(mysqli_error($con))
			{
				echo "0";
			}
			else
			{
				$socket	=	new	send_message_to_server($port_conf);	
				$taskarray = explode(",",$task_id);
				for($i=0;$i<count($taskarray);$i++)
				{
					$msg = "task?state=7&id=".$taskarray[$i]."&volume=".$volume."&type=".$tasktype;
					$socket->send_data($_SESSION['serverip'],$msg);
				}
				echo "1";
			}
			break;
			case 11:
			mysqli_query($con,"UPDATE task SET defaultvolume = '$volume',offlinestate=0 WHERE taskid IN ($task_id) AND tasktype = '$tasktype'") ;
			if(mysqli_error($con))
			{
				echo "0";
			}
			else
			{
				$socket	=	new	send_message_to_server($port_conf);	
				$taskarray = explode(",",$task_id);
				for($i=0;$i<count($taskarray);$i++)
				{
					$msg = "task?state=7&id=".$taskarray[$i]."&volume=".$volume."&type=".$tasktype;
					$socket->send_data($_SESSION['serverip'],$msg);
				}
				echo "1";
			}
			break;
			case 2:
				

			mysqli_query($con,"UPDATE task SET defaultvolume = '$volume',offlinestate=0 WHERE taskid IN ($task_id)") ;
			if(mysqli_error($con))
			{
				echo "0";
			}
			else
			{
				$socket	=	new	send_message_to_server($port_conf);	
				$taskarray = explode(",",$task_id);
				for($i=0;$i<count($taskarray);$i++)
				{
					$bell_id_results = mysqli_query($con,"SELECT tasktype FROM task WHERE taskid = '$taskarray[$i]'");
					if($bell_id_rows = mysqli_fetch_array($bell_id_results))
					{
						if($bell_id_rows['tasktype']==19)
						{
							$msg = "task?state=7&id=".$taskarray[$i]."&volume=".$volume."&type=19";
						}
						else
							$msg = "task?state=7&id=".$taskarray[$i]."&volume=".$volume."&type=".$tasktype;		
					}
					$socket->send_data($_SESSION['serverip'],$msg);
				}
				echo "1";
			}
			break;
			case 3:
			mysqli_query($con,"UPDATE task SET defaultvolume = '$volume',offlinestate=0 WHERE taskid IN ($task_id) AND tasktype = '$tasktype'") ;
			
			if(mysqli_error($con))
			{
				echo "0";
			}
			else
			{
				$socket	=	new	send_message_to_server($port_conf);	
				$taskarray = explode(",",$task_id);
				for($i=0;$i<count($taskarray);$i++)
				{
				$msg = "task?state=7&id=".$taskarray[$i]."&volume=".$volume."&type=".$tasktype;
				$socket->send_data($_SESSION['serverip'],$msg);
				}
				echo "1";
			}
			break;
			
			case 4:
			mysqli_query($con,"UPDATE task SET defaultvolume = '$volume',offlinestate=0 WHERE taskid IN ($task_id) AND tasktype = '$tasktype'") ;
			if(mysqli_error($con))
			{
				echo "0";
			}
			else
			{
				$socket	=	new	send_message_to_server($port_conf);	
				$taskarray = explode(",",$task_id);
				for($i=0;$i<count($taskarray);$i++)
				{
				$msg = "task?state=7&id=".$taskarray[$i]."&volume=".$volume."&type=".$tasktype;
				
				$socket->send_data($_SESSION['serverip'],$msg);
				}
				echo "1";
			}
			break;
			case 24:
			mysqli_query($con,"UPDATE task SET defaultvolume = '$volume',offlinestate=0 WHERE cmdargs IN ($task_id)") ;
			if(mysqli_error($con))
			{
				echo "0";
			}
			else
			{
				$socket	=	new	send_message_to_server($port_conf);	
				$taskarray = explode(",",$task_id);
				for($i=0;$i<count($taskarray);$i++)
				{
					$msg = "task?state=7&id=".$taskarray[$i]."&volume=".$volume."&type=24";
					$socket->send_data($_SESSION['serverip'],$msg);
					$led_result = mysqli_query($con,"SELECT DISTINCT taskid FROM task WHERE cmdargs IN ($taskarray[$i]) and tasktype='25'") or die(mysqli_error($con));
					while($led_row = mysqli_fetch_array($led_result))
					{
						$msg = "task?state=7&id=".$led_row['taskid']."&volume=".$volume."&type=25";
						$socket->send_data($_SESSION['serverip'],$msg);
					}	
				}
				echo "1";
			}
			break;
			case 25:
			$db_value0 = trim($_GET['db_value0']);
			mysqli_query($con,"UPDATE soundtask SET dbvalue = '$db_value0' WHERE taskid =0 and volume=0") ;
			$db_value1 = trim($_GET['db_value1']);
			mysqli_query($con,"UPDATE soundtask SET dbvalue = '$db_value1' WHERE taskid =0 and volume=20") ;
			$db_value2 = trim($_GET['db_value2']);
			mysqli_query($con,"UPDATE soundtask SET dbvalue = '$db_value2' WHERE taskid =0 and volume=40") ;
			$db_value3 = trim($_GET['db_value3']);
			mysqli_query($con,"UPDATE soundtask SET dbvalue = '$db_value3' WHERE taskid =0 and volume=60") ;
			$db_value4 = trim($_GET['db_value4']);
			mysqli_query($con,"UPDATE soundtask SET dbvalue = '$db_value4' WHERE taskid =0 and volume=80") ;
			$db_value5 = trim($_GET['db_value5']);
			mysqli_query($con,"UPDATE soundtask SET dbvalue = '$db_value5' WHERE taskid =0 and volume=100") ;
			
			if(mysqli_error($con))
			{
				echo "0";
			}
			else
			{
				echo "1";
			}
			break;
			
			
		}
	}
	else
	{
		echo "0";
	}
?>