<?php
if (!session_id()) session_start();

define("PRE_POWER_TASKTYPE",9);

define("COL_TERMINAL_TASKTYPE",8);

define("URG_BROADCAST_TASKTYPE",7);

#define("",6);

define("CLASS_SCHEDULE",1);

define("FILE_BRODCAST",2);

define("COLLECT_BROADCAST",3);

define("TELEPHONE_BROADCAST",4);

define("TERMINAL_AMPLIFIER",5);

//计算时间
function count_task_time($str_time,$sign,$hour=0,$minute=0,$second=0)
{
	return date("H:i:s",strtotime($str_time." ".$sign." ".$hour." hours ".$sign." ".$minute." minutes ".$sign." ".$second." seconds"));
}
//返回插入语句---仅针对任务操作
function create_insert_sql($task_property_obj)
{
	$insert_sql = "INSERT INTO audioserver.task ";

	$insert_sql.= "(taskname,israndomplay,projectstate,timelengthtype,timelength,";

	$insert_sql.= "prepower,datasendmodel,state,startdate,enddate,playtime,exemodel,";

	$insert_sql.= "priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,";

	$insert_sql.= "playfileid,info,defaultvolume,task_user_id,sec_task_id) ";

	$insert_sql.= "VALUES ";

	$insert_sql.= "('".$task_property_obj->get_taskname()."','".$task_property_obj->get_israndomplay()."',";
	
	$insert_sql.= "'".$task_property_obj->get_projectstate()."'";
	
	$insert_sql.= ",'".$task_property_obj->get_timelengthtype()."','".$task_property_obj->get_timelength()."',";

	$insert_sql.= "'".$task_property_obj->get_prepower()."','".$task_property_obj->get_datasendmodel()."',";
	
	$insert_sql.= "'".$task_property_obj->get_state()."','".$task_property_obj->get_startdate()."','".$task_property_obj->get_enddate()."',";
	
	$insert_sql.= "'".$task_property_obj->get_playtime()."','".$task_property_obj->get_exemodel()."',";

	$insert_sql.= "'".$task_property_obj->get_priority()."','".$task_property_obj->get_tasktype()."',";
	
	$insert_sql.= "'".$task_property_obj->get_channel()."','".$task_property_obj->get_bandrate()."','".$task_property_obj->get_samplerate()."',";
	
	$insert_sql.= "'".$task_property_obj->get_cmd()."','".$task_property_obj->get_cmdargs()."',";

	$insert_sql.= "'".$task_property_obj->get_playfileid()."','".$task_property_obj->get_info()."','".$task_property_obj->get_defaultvolume()."',";
	
	$insert_sql.= "'".$task_property_obj->get_task_user_id()."','".$task_property_obj->get_sec_task_id()."') ";
	
	return $insert_sql;
}
//返回更新语句---仅针对任务操作
function create_update_suffsql($task_property_obj)
{
	$update_sql = "UPDATE audioserver.task SET ";
	
	$update_sql.= "taskname = '".$task_property_obj->get_taskname()."',israndomplay = '".$task_property_obj->get_israndomplay()."',";
	 
	$update_sql.= "projectstate = '".$task_property_obj->get_projectstate()."' ,timelengthtype = '".$task_property_obj->get_timelengthtype()."',";
	 
	$update_sql.= "timelength = '".$task_property_obj->get_timelength()."',prepower = '".$task_property_obj->get_prepower()."',";
	 
	$update_sql.= "datasendmodel = '".$task_property_obj->get_datasendmodel()."',state = '".$task_property_obj->get_state()."' ,"; 
	
	$update_sql.= "startdate = '".$task_property_obj->get_startdate()."',enddate = '".$task_property_obj->get_enddate()."' ,"; 

	$update_sql.= "playtime = '".$task_property_obj->get_playtime()."' ,exemodel = '".$task_property_obj->get_exemodel()."' ,";
	 
	$update_sql.= "priority = '".$task_property_obj->get_priority()."',tasktype = '".$task_property_obj->get_tasktype()."' ,";
	 
	$update_sql.= "channel = '".$task_property_obj->get_channel()."',bandrate = '".$task_property_obj->get_bandrate()."' ,";
	 
	$update_sql.= "samplerate = '".$task_property_obj->get_samplerate()."',cmd = '".$task_property_obj->get_cmd()."' ,";
	 
	$update_sql.= "cmdargs = '".$task_property_obj->get_cmdargs()."',playfileid = '".$task_property_obj->get_playfileid()."' ,";
	 
	$update_sql.= "info = '".$task_property_obj->get_info()."',defaultvolume = '".$task_property_obj->get_defaultvolume()."' ,";
	 
	$update_sql.= "task_user_id = '".$task_property_obj->get_task_user_id()."',sec_task_id = '".$task_property_obj->get_sec_task_id()."'";
	
	return $update_sql;
}
//字符集转换
function characters_convert($str_txt)
{

	return mb_convert_encoding($str_txt,"utf-8","GBK");
}
//计算时间
function count_time($play_time,$prepower)
{
	$pre_time = date('H:i:s',strtotime($playtime."-0 hours - ".$prepower."minutes -0 seconds"));

	return $pre_time;
}
//锁表
function lock_table($string_table)
{
global $con;
	$table_name_arr = explode(",",$string_table);
	
	$table_name = "";
	
	foreach($table_name_arr as $tem_table)
	{
		$table_name.= $tem_table." write ";
	}
	mysqli_query($con,"LOCK TABLE ".$table_name);
}
//解锁
function unlock_table()
{
	global $con;
	mysqli_query($con,"UNLOCK TABLES");
}
//启用事务
function start_trans()
{
	global $con;
	mysqli_query($con,"START TRANSACTION");
}
//回滚事务
function roll_back()
{
global $con;
	mysqli_query($con,"ROLLBACK");
}
//提交事务
function commit_()
{
global $con;
	mysqli_query($con,"COMMIT");
}
//加载配置文件
function require_data_config()
{
	require_once("inc/config.php");

	require_once("inc/config.inc.php");
}
//路径跳转
class forward_ok_error_class
{
	function __construct()
	{
		if(empty($_SESSION['path']))
		{
			$_SESSION['path'] = "./login.php";
		}
	}
	//失败或成功跳转
	function forward_path($flag,$info,$path)
	{
		if($flag == 1)
		{
			$_SESSION['info'] = $info;
		
			$_SESSION['path'] = $path;

			echo "<script>window.location='./success.php'</script>";
		}
		else if($flag == 0)
		{
			$_SESSION['info'] = $info;
		
			$_SESSION['path'] = $path;

			echo "<script>window.location='./error.php'</script>";
		}
	}
	//弹出信息
	function exit_function($pro_info)
	{
		echo "<script>alert('".$pro_info."');</script>";
	}
	//弹出信息并返回
	function exit_back_function($pro_info)
	{
		echo "<script>alert('".$pro_info."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
	}
	//弹出信息并退出
	function exit_pop_function($pro_info)
	{
		echo "<script>alert('".$pro_info."');</script>";
		
		exit;
	}
	//弹出信息并跳转
	function pop_forward_path($pro_info,$forward_path)
	{
		echo "<script>alert('".$pro_info."');</script>";
		
		/*echo "<script>window.history.back();</script>";*/
		
		echo "<script>window.location.href = '".$forward_path."'</script>";
		
		exit;
	}
}
//创建socket并发送数据未完成
class create_socket_class
{
	private $socket = NULL;
	
	private $msg_info = NULL;

	function __construct()
	{
		require_once("inc/socket_conf.php");
		
		$this->socket = new send_message_to_server($port_conf);	
		
		if(empty($_SESSION['serverip']))
		{
			$_SESSION['serverip'] = "audioserver";
		}
	}
	//发送一个数据
	function send_socket_msg($msg_type,$msg_state,$msg_id,$msg_volume=50)
	{
		switch ($msg_state)
		{
			case 1:
			
			case 2:
			
			case 3:
			
			case 4:
			
			case 5:
			
			case 6:
			
			$this->msg_info = $msg_type."?state=".$msg_state."&id=".$msg_id;
			
			break;
			
			case 7:
			
			$this->msg_info = $msg_type."?state=".$msg_state."&id=".$msg_id."&volume=".$msg_volume;
			
			break;
		}
		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	//发送终端密码
	function send_socket_terminal_password($msg_type,$msg_state,$terminal_ids,$terminal_password)
	{
		$this->msg_info = $msg_type."?state=".$msg_state."&id={".$terminal_ids."}&pwd=".$terminal_password;

		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	//发送媒体消息
	function send_socket_media_file($msg_type,$msg_state,$msg_id)
	{
		$this->msg_info = $msg_type."?state=".$msg_state."&id=".$msg_id;

		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	//发送通用消息
	function send_socket_generate_general($msg_type,$msg_state,$msg_id)
	{
		$this->msg_info = $msg_type."?state=".$msg_state."&id=".$msg_id;

		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	//发送v2.0版本通用消息
	function send_socket_generate_general2($msg_type,$msg_state,$msg_id,$type)
	{
		$this->msg_info = $msg_type."?state=".$msg_state."&id=".$msg_id."&type=".$type;
		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}

	//发送视频通用消息
	function send_socket_vediotask($msg_title,$medialist,$task_default_volume,$timelengthtype,$timelength,$priority,$terminalnum,$terminalids)
	{
		$medianame=	$this->socket->toStr($this->socket->getallBytes($medialist,256));
		$task_volume=$this->socket->toStr($this->socket->integerToBytes($task_default_volume));
		$timetype=$this->socket->toStr($this->socket->integerToBytes($timelengthtype));
		$timelengths=$this->socket->toStr($this->socket->integerToBytes($timelength));
		$prioritys=$this->socket->toStr($this->socket->integerToBytes($priority));

		$terminalcount=$this->socket->toStr($this->socket->integerToBytes($terminalnum));

		$terminallistid = explode(",",$terminalids);
		for($i=0;$i<256;$i++)
		{
			if($i<$terminalnum)
			{
				if($i==0)
				{
					$terminalid=$this->socket->toStr($this->socket->integerToBytes($terminallistid[$i]));
				
				}
				else
				{
				 $terminalid=$terminalid.$this->socket->toStr($this->socket->integerToBytes($terminallistid[$i]));
				
				}
			}
			else
			{
			 $terminalid=$terminalid.$this->socket->toStr($this->socket->integerToBytes(0));
			}
		}
		$this->msg_info = $task_volume.$timetype.$timelengths.$prioritys.$medianame.$terminalcount.$terminalid;
		$this->socket->send_datatosdk($_SESSION['serverip'],$this->msg_info,1,1300,0);
	}


	//发送对讲消息
	function send_socket_speech($msg_type,$msg_state,$msg_id,$yesOrno)
	{
		$this->msg_info = $msg_type."?state=".$msg_state."&id=".$msg_id."&speech=".$yesOrno;
		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	//发送任务和声音
	function send_socket_task_volume($msg_type,$msg_state,$msg_id,$volume)
	{
		$this->msg_info = $msg_type."?state=".$msg_state."&id=".$msg_id."&volume=".$volume;
		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	
	function send_socket_recode($msg_type,$msg_state,$msg_id,$volume)
	{
		$this->msg_info = $msg_type."?state=".$msg_state."&id={".$msg_id."}";
		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	
	//发送服务器消息
	function send_socket_server($msg_type,$ip,$port,$rtspport,$maxbandwidth,$maxhttpconnections)
	{
		$this->msg_info = $msg_type."?ip=".$ip."&port=".$port."&rtspport=".$rtspport."&maxbandwidth=".$maxbandwidth."&maxhttpconnections=".$maxhttpconnections;

		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	//发送作息消息
	function send_socket_schedules($msg_type,$msg_state,$msg_name)
	{
		$this->msg_info = "".$msg_type."?state=".$msg_state."&name=".$msg_name."";

		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	
	//发送删除噪声分区消息
	function send_socket_zhaosheng_del_general($msg_type,$msg_state,$msg_id)
	{
		$this->msg_info = $msg_type."?state=".$msg_state."&group=".$msg_id;

		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	
		//发送噪声分区消息
	function send_socket_zhaosheng_general($msg_type,$msg_state,$msg_id,$terminalid)
	{
		$this->msg_info = $msg_type."?state=".$msg_state."&group=".$msg_id."&id={".$terminalid."}";
	
		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	
	//发送作息消息
	function send_socket_circuit($msg_type,$msg_state,$msg_name)
	{
		$this->msg_info = "".$msg_type."?state=".$msg_state."&id={".$msg_name."}";

		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	
	//发送system消息
	function send_system_info($msg_buffer)
	{
		$this->socket->send_systemdata($msg_buffer);
	}
	
		//发送system命令消息
		function send_system_commanid($msg_buffer)
		{
			$this->socket->send_datacommandid($_SESSION['serverip'],$msg_buffer);
		}
	
	//发送作息消息
	function send_socket_taskterminal($msg_type,$msg_state,$taskid,$terminalid)
	{
		$this->msg_info = "".$msg_type."?state=".$msg_state."&id=".$terminalid."&taskid=".$taskid."";

		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	
	//发送作息消息
	function send_socket_terminalmedia($msg_type,$msg_state,$terminalid,$mediaid)
	{
		$this->msg_info = "".$msg_type."?state=".$msg_state."&id=".$terminalid."&mediaid=".$mediaid."";

		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	
	//发送重启服务器
	function send_socket_restart($msg_type,$msg_state)
	{
		$this->msg_info = $msg_type."?state=".$msg_state;
		
		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	//发送快捷寻呼消息
	function send_socket_shotcut($msg_type,$source,$msg_shotcut)
	{
		$this->msg_info = $msg_type."?source=".$source."&shotcut=".$msg_shotcut;

		$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
	}
	//发送一组数据
	function send_socket_msgs($msg_type,$msg_state,$msg_ids,$msg_volume=50)
	{
		$id_array = explode(",",$msg_ids);
		
		foreach($id_array as $msg_id)
		{
			switch ($msg_state)
			{
				case 1:
				
				case 2:
				
				case 3:
				
				case 4:
				
				case 5:
				
				case 6:
				
				$this->msg_info = $msg_type."?state=".$msg_state."&id=".$msg_id;
				
				break;
				
				case 7:
				
				$this->msg_info = $msg_type."?state=".$msg_state."&id=".$msg_id."&volume=".$msg_volume;
				
				break;
			}
			$this->socket->send_data($_SESSION['serverip'],$this->msg_info);
		}
	}
}
//对数据操作
class database_operate_class
{
	public $result_one = "";

	public $result_some = "";
	
	public $result_two_some = "";
	//加载配置文件
	function __construct()
	{
		 require_data_config();
	}
	//只读取数据库一个值
	function select_one($sql)
	{
		global $con;
		$result = mysqli_query($con,$sql);
		
		if($row = mysqli_fetch_array($result))
		{
			return $this->result_one = $row[0];
		}
		return -1;
	}
	//读取数据库第一项的值
	function select_some($sql)
	{
		global $con;
		$result = mysqli_query($con,$sql);
		
		if(mysqli_num_rows($result) > 0)
		{	
			while($row = mysqli_fetch_array($result))
			{
				$result_some[] = $row[0];
			}
			
			return $result_some;
		}
		
		return -1;
	}
	//查找数据库两项的值
	function select_two_some($sql)
	{
		global $con;
		$result = mysqli_query($con,$sql);
		
		if(mysqli_num_rows($result) > 0)
		{	
			while($row = mysqli_fetch_array($result))
			{
				$result_two_some[] = array($row[0],$row[1]);
			}	
			return $result_two_some;
		}
		return -1;
	}
	//判断是否存在----存在则退出----
	function select_ornot_exit($sql,$pop_info)
	{
		global $con;
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if(mysqli_num_rows($result) > 0)
		{
			echo "<script>alert('".$pop_info."');</script>";
			
			echo "<script>window.history.back();</script>";
			
			exit;
		}
	}
	//对文件夹删除处理
	function whether_have_exit($folder_ids_array,$pop_info,$pop_info_)
	{
		global $con;
		$folder_len = 0;
		
		$folder_ids_temp = array();
		
		$folder_len_temp = 0;
		
		$folder_ids_str = "";
		
		foreach($folder_ids_array as $folder_key=>$folder_value)
		{
			$folder_sql = "SELECT DISTINCT filefolder.id,filefolder.name FROM media,mediaoftask,filefolder WHERE ";

			$folder_sql.= "mediaoftask.mediaid=media.id AND filefolder.id=media.folderid AND filefolder.id=".$folder_value." LIMIT 1 ";
			
			$folder_result = mysqli_query($con,$folder_sql) or die(mysqli_error($con));
			
			if($folder_row = mysqli_fetch_array($folder_result))
			{
			
				
				$folder_len_temp ++;
				
				$folder_ids_temp[] = $folder_row['id'];
			}
			else
			{
				//什么也不做
			}
			$folder_len++;
		}
		
		array_push($folder_ids_temp,1,2,3,4,5);
		
		if($folder_len_temp == $folder_len)
		{
			echo "<script>alert('".$pop_info_."');</script>";
			
			echo "<script>window.history.back();</script>";
			
			exit;
		}
		else
		{
			$folder_ids_str = implode(",",array_diff($folder_ids_array,$folder_ids_temp));
			
			if($folder_ids_str == "")
			{
				echo "<script>alert('".$pop_info."');</script>";
			
				echo "<script>window.history.back();</script>";
				
				exit;
			}
			else
			{
				return $folder_ids_str;
			}
		}
	}
	//插入数据
	function insert_sql($sql)
	{
		global $con;
		$result_sql = mysqli_query($con,$sql);
		
		if($result_sql == FALSE)
		{
			return -1;
		}
			
		return 1;
	}
	//删除数据
	function delete_sql($sql)
	{
		global $con;
		$result_sql = mysqli_query($con,$sql);
		
		if($result_sql == FALSE)
		{
			return -1;
		}
		return 1;
	}
	//更新数据
	function update_sql($sql)
	{
		global $con;
		$result_sql = mysqli_query($con,$sql);
		
		if($result_sql == FALSE)
		{
			return -1;
		}
			
		return 1;
	}
	//查找是否有相同
	function query_same_name($sql)
	{
		global $con;
		$result = mysqli_query($con,$sql);
		
		if(mysqli_num_rows($result) > 0)
		{
			return 1;
		}
		return -1;
	}
	//取当前最大值
	function current_max_id($sql)
	{
		global $con;
		$result_max = mysqli_query($con,$sql) or die(mysqli_error($con));

		if($row_max = mysqli_fetch_array($result_max))
		{
			return $row_max[0];
		}
		return -1;
	}
	//=================================================================对任务单独处理
	//读取数据库任务最大ID值
	function select_task_max_id()
	{
		global $con;
		$task_sql = "SELECT MAX(taskid) AS taskid FROM audioserver.task ";
		
		$task_result = mysqli_query($con,$task_sql) or die(mysqli_error($con));
		
		if($task_row = mysqli_fetch_array($task_result))
		{
			return $task_row['taskid'];
		}
		return -1;
	}
	//判断插入任务是否同名
	function insert_task_same_name($entire_task_property_obj)
	{
		global $con;
		$task_sql = "SELECT * FROM audioserver.task WHERE task.taskname='".$entire_task_property_obj->get_taskname()."' AND ";
		
		$task_sql.= "task.tasktype='".$entire_task_property_obj->get_tasktype()."' AND task.sec_task_id=0 AND task.info='' ";
		
		$task_result = mysqli_query($con,$task_sql) or die(mysqli_error($con));
		
		if(mysqli_num_rows($task_result) > 0)
		{
			return 1;
		}
		return -1;
	}
	//判断修改任务是否同名
	function modiry_task_same_name($entire_task_property_obj,$task_id)
	{
		global $con;
		$task_sql = "SELECT * FROM audioserver.task WHERE task.taskname='".$entire_task_property_obj->get_taskname()."' ";
		
		$task_sql.= "AND task.tasktype='".$entire_task_property_obj->get_tasktype()."' AND task.sec_task_id=0 ";
		
		$task_sql.= "AND task.info='' AND task.taskid !='".$task_id."' ";
		
		$task_result = mysqli_query($con,$task_sql) or die(mysqli_error($con));
		
		if(mysqli_num_rows($task_result) > 0)
		{
			return 1;
		}
		return -1;
	}
	//读取关联欲开电源任务ID和终端功放相关联的ID
	function get_relationship_task_id($entire_task_property_obj,$curr_task_id)
	{
		global $con;
		//TERMINAL_AMPLIFIER--只针对终端功放
		$relation_task_id = 0;
		
		if($entire_task_property_obj->get_tasktype() != TERMINAL_AMPLIFIER)
		{
			$task_sql = "SELECT task.taskid FROM audioserver.task WHERE task.tasktype='".PRE_POWER_TASKTYPE."' ";
		
			$task_sql.= "AND task.prepower>0 AND task.sec_task_id='".$curr_task_id."' AND task.info='' ";
		}
		else if ($entire_task_property_obj->get_tasktype() == TERMINAL_AMPLIFIER)
		{
			$task_sql = "SELECT task.taskid FROM audioserver.task WHERE task.tasktype='".TERMINAL_AMPLIFIER."' ";
		
			$task_sql.= "AND task.sec_task_id='".$curr_task_id."' AND task.info='' ";
		}
		$task_result = mysqli_query($con,$task_sql) or die(mysqli_error($con));
		
		if($task_row = mysqli_fetch_array($task_result))
		{
			$relation_task_id = $task_row['taskid'];
		}
		return $relation_task_id;
	}
	//删除任务相关终端
	function delete_relationship_terminal($task_id)
	{
		global $con;
		$insert_result = TRUE;
	
		$insert_result = mysqli_query($con,"DELETE FROM audioserver.terminaloftask WHERE terminaloftask.taskid = '".$task_id."'") ;
		
		if($insert_result = FALSE)
		{
			return $insert_result;
		}
		return $insert_result;
	}
	//删除任务相关媒体
	function delete_relationship_media($task_id)
	{
		global $con;
		$insert_result = TRUE;
		
		$insert_result = mysqli_query($con,"DELETE FROM audioserver.mediaoftask WHERE mediaoftask.taskid = '".$task_id."'") ;
		
		if($insert_result == FALSE)
		{
			return $insert_result;
		}
		return $insert_result;
	}
	//添加任务相关终端
	function add_relationship_terminal($task_id,$entire_task_property_obj)
	{
		global $con;
		$insert_result = TRUE;
	
		$task_terminal_ids = $entire_task_property_obj->get_task_terminal_ids();
		
		$task_group_ids = $entire_task_property_obj->get_task_group_ids();
		
		foreach($task_terminal_ids as $terminal_key=>$terminal_value)
		{
			$task_sql = "INSERT INTO audioserver.terminaloftask (taskid,terminalid,groupid) ";
			
			$task_sql.= "VALUES(".$task_id.",".$task_terminal_ids[$terminal_key].",".$task_group_ids[$terminal_key].") ";
			
			$insert_result = mysqli_query($con,$task_sql) ;
			
			if($insert_result == FALSE)
			{
				return $insert_result;
			}
		}
		return $insert_result;
	}
	//添加任务相关媒体
	function add_relationship_media($task_id,$entire_task_property_obj)
	{
		global $con;
		$insert_result = TRUE;
	
		$task_media_ids = $entire_task_property_obj->get_task_media_ids();
		
		foreach($task_media_ids as $media_key=>$media_value)
		{
			$task_sql = "INSERT INTO audioserver.mediaoftask (mediaid,taskid) ";
			
			$task_sql.= "VALUES(".$task_media_ids[$media_key].",".$task_id.") ";
			
			$insert_result = mysqli_query($con,$task_sql);
			
			if($insert_result == FALSE)
			{
				return $insert_result;
			}
		}
		return $insert_result;
	}
	//添加采播任务相关终端任务
	function add_relationship_coll_terminal($task_id,$terminal_id)
	{
		global $con;
		$insert_result = TRUE;
	
		$task_sql = "INSERT INTO audioserver.terminaloftask (taskid,terminalid) ";
		
		$task_sql.= "VALUES(".$task_id.",".$terminal_id.") ";
		
		$insert_result = mysqli_query($con,$task_sql) or die(mysqli_error($con));
		
		if($insert_result == FALSE)
		{
			return $insert_result;
		}
		
		return $insert_result;
	}
	//读取采播任务相关采播任务为8
	function get_relation_collect_task_id($task_id)
	{
		global $con;
		$relation_collect_task_id = 0;
		
		$task_sql = "SELECT task.taskid FROM audioserver.task WHERE ";
		
		$task_sql.= "task.tasktype=".COL_TERMINAL_TASKTYPE." AND task.sec_task_id=".$task_id." AND task.info='' ";
		
		$task_result = mysqli_query($con,$task_sql) or die(mysqli_error($con));
		
		if($task_row = mysqli_fetch_array($task_result))
		{
			$relation_collect_task_id = $task_row['taskid'];
			
			return $relation_collect_task_id;
		}
		return -1;
	}
	//修改任务时是否是同一个用户---决定是否修改任务优先级
	function judge_task_user_id($task_id)
	{
		global $con;
		$task_user_id = -1;
		
		$task_sql = "SELECT task.task_user_id FROM audioserver.task WHERE task.taskid=".$task_id."";
		
		$task_result = mysqli_query($con,$task_sql) or die(mysqli_error($con));
		
		if($task_row = mysqli_fetch_array($task_result))
		{
			$task_user_id = $task_row['task_user_id'];
		}
		
		$user_id = 0;
		
		$user_sql = "SELECT book_admin.id FROM audioserver.book_admin WHERE book_admin.username=".$_SESSION['username']."";
		
		$user_result = mysqli_query($con,$user_sql) or die(mysqli_error($con));
		
		if($user_row = mysqli_fetch_array($user_result))
		{
			$user_id = $user_row['id'];
		}
		
		if($task_user_id != $user_id)
		{
			return -1;
		}
		return 1;
	}
	//判断所有任务是否已启用或停止
	function judge_all_task_start_or_stop($task_id,$task_type,$task_info)
	{
		global $con;
		$task_sql = "";
		
		//针对作息方案
		if($task_type == 1)
		{
			$task_sql = "SELECT task.taskname,task.projectstate,task.info FROM task ";

			$task_sql.= "WHERE task.tasktype='".$task_type."' AND task.taskid='".$task_id."' AND ";
	
			$task_sql.= "task.sec_task_id=0 AND task.task_user_id=0 AND task.info !='' ";
			
		}
		else//针对非作息方案
		{
			$task_sql = "SELECT task.state,task.prepower,task.sec_task_id FROM task ";

			$task_sql.= "WHERE task.info='' AND task.sec_task_id=0 ";

			$task_sql.= "AND task.tasktype='".$task_type."' AND task.taskid='".$task_id."' ";
		}
		switch($task_type)
		{
			case "1":
			
			break;
			
			case "2":
			
			break;
			
			case "3":
			
			break;
			
			case "4":
			
			break;
			
			case "5":
			
			break;
		}
	}
}
//对获取字符串处理、返回数组
class string_operate_class
{
	private $get_string = "";
	
	function __construct($get_string)
	{
		$this->get_string = $get_string;
	}
	
	function string_operate()
	{
		$string_array = explode(",",$this->get_string);
		
		$string_result = array();
		
		foreach($string_array as $str_one)
		{
			if(is_numeric($str_one))
			{
				$string_result[] = $str_one;
			}
		}
		return $string_result;
	}
}
//设置、获取任务属性值
class entire_task_property_class
{
	private $taskname = ""; 
	private $israndomplay = 0; 
	private $projectstate = 0;
	private $timelengthtype = 1; 
	private $timelength = 0;
	private $prepower = 0; 
	private $datasendmodel = 0; 
	private $state = 0; 
	private $startdate = "0000-00-00"; 
	private $enddate = "0000-00-00";
	private $playtime = "00:00:00"; 
	private $exemodel = "1111111"; 
	private $priority = 0; 
	private $tasktype = 0; 
	private $channel = 0; 
	private $bandrate = 0; 
	private $samplerate = 0; 
	private $cmd = 0; 
	private $cmdargs = 0; 
	private $playfileid = 0; 
	private $info = ""; 
	private $defaultvolume = 80; 
	private $task_user_id = 0; 
	private $sec_task_id = 0;
	//=======================================================添加计算功放时间
	private $power_amplifier_time = "00:00:00";
	
	private $last_power_amplifier_time = "00:00:00";
	//=======================================================
	//=======================================================获取任务终端、终端组、任务媒体
	private $task_terminal_ids = "";
	
	private $task_group_ids = "";
	
	private $task_media_ids = "";
	//=======================================================
	//=======================================================添加采播终端时间
	private $interview_repower = "";
	
	private $interview_repower_time = "";
	
	private $audiosource = "";//添加采播终端
	
	function __construct()
	{
		require_data_config();
	}
	//获取值
	function get_taskname()
	{
		return $this->taskname;
	} 
	function get_israndomplay()
	{
		return $this->israndomplay;
	}
	function get_projectstate()
	{
		return $this->projectstate;
	}
	function get_timelengthtype()
	{
		return $this->timelengthtype;
	} 
	function get_timelength()
	{
		return $this->timelength;
	}
	function get_prepower()
	{
		return $this->prepower;
	} 
	function get_datasendmodel()
	{
		return $this->datasendmodel;
	} 
	function get_state()
	{
		return $this->state;
	} 
	function get_startdate()
	{
		return $this->startdate;
	} 
	function get_enddate()
	{
		return $this->enddate;
	}
	function get_playtime()
	{
		return $this->playtime;
	} 
	function get_exemodel()
	{
		return $this->exemodel;
	} 
	function get_priority()
	{
		return $this->priority;
	} 
	function get_tasktype()
	{
		return $this->tasktype;
	} 
	function get_channel()
	{
		return $this->channel;
	} 
	function get_bandrate()
	{
		return $this->bandrate;
	} 
	function get_samplerate()
	{
		return $this->samplerate;
	} 
	function get_cmd()
	{
		return $this->cmd;
	} 
	function get_cmdargs()
	{
		return $this->cmdargs;
	} 
	function get_playfileid()
	{
		return $this->playfileid;
	} 
	function get_info()
	{
		return $this->info;
	} 
	function get_defaultvolume()
	{
		return $this->defaultvolume;
	} 
	function get_task_user_id()
	{
		return $this->task_user_id;
	} 
	function get_sec_task_id()
	{
		return $this->sec_task_id;
	}
	function get_power_amplifier_time()
	{
		return $this->power_amplifier_time;
	}
	function get_last_power_amplifier_time()
	{
		return $this->last_power_amplifier_time;
	}
	function get_task_terminal_ids()
	{
		return $this->task_terminal_ids;
	}
	function get_task_group_ids()
	{
		return $this->task_group_ids;
	}
	function get_task_media_ids()
	{
		return $this->task_media_ids;
	}
	function get_interview_repower()
	{
		return $this->interview_repower;
	}
	function get_interview_repower_time()
	{
		return $this->interview_repower_time;
	}
	function get_audiosource()
	{
		return $this->audiosource;
	}
	//设置任务属性
	function set_taskname($taskname)
	{
		$this->taskname = $taskname;
	} 
	function set_israndomplay($israndomplay)
	{
		$this->israndomplay = $israndomplay;
	}
	function set_projectstate($projectstate)
	{
		$this->projectstate = $projectstate;
	}
	function set_timelengthtype($timelengthtype)
	{
		$this->timelengthtype = $timelengthtype;
	} 
	function set_timelength($timelength)
	{
		$this->set_timelength = $timelength;
	}
	function set_prepower($prepower)
	{
		$this->prepower = $prepower;
	} 
	function set_datasendmodel($datasendmodel)
	{
		$this->datasendmodel = $datasendmodel;
	} 
	function set_state($state)
	{
		$this->state = $state;
	} 
	function set_startdate()
	{
		$this->startdate = $startdate;
	} 
	function set_enddate($enddate)
	{
		$this->enddate = $enddate;
	}
	function set_playtime($playtime)
	{
		$this->playtime = $playtime;
	} 
	function set_exemodel($exemodel)
	{
		$this->exemodel = $exemodel;
	} 
	function set_priority($priority)
	{
		$this->priority = $priority;
	} 
	function set_tasktype($tasktype)
	{
		$this->tasktype = $tasktype;
	} 
	function set_channel($channel)
	{
		$this->channel = $channel;
	} 
	function set_bandrate($bandrate)
	{
		$this->bandrate = $bandrate;
	} 
	function set_samplerate($samplerate)
	{
		$this->samplerate = $samplerate;
	} 
	function set_cmd($cmd)
	{
		$this->cmd = $cmd;
	} 
	function set_cmdargs($cmdargs)
	{
		$this->cmdargs = $cmdargs;
	} 
	function set_playfileid($playfileid)
	{
		$this->playfileid = $playfileid;
	} 
	function set_info($info)
	{
		$this->info = $info;
	} 
	function set_defaultvolume($defaultvolume)
	{
		$this->defaultvolume = $defaultvolume;
	} 
	function set_task_user_id($task_user_id)
	{
		$this->task_user_id = $task_user_id;
	} 
	function set_sec_task_id($sec_task_id)
	{
		$this->sec_task_id = $sec_task_id;
	}
	function set_audiosource($audiosource)
	{
		$this->audiosource = $audiosource;
	}
	//注意：设置欲开电源功放时间
	function set_power_amplifier_time()
	{
		$str_time = $this->get_playtime();
		
		$pre_time = $this->get_prepower();
	
		if($pre_time > 0)
		{
			$this->power_amplifier_time = count_task_time($str_time,"-",0,$pre_time,0);
		}
	}
	//注意：设置终端功放后的时间
	function set_last_power_amplifier_time()
	{
		$str_time = $this->get_playtime();
		
		$pre_time = $this->get_prepower();
	
		if($pre_time > 0)
		{
			$this->last_power_amplifier_time = count_task_time($str_time,"+",0,$pre_time,0);
		}
	}
	//注意：设置采播欲开电源时长
	function set_interview_repower($interview_repower)
	{
		$this->interview_repower = $interview_repower;
	}
	//注意：设置采播欲开电源时间
	function set_interview_repower_time()
	{
		$str_time = $this->get_playtime();
		
		$pre_time = $this->get_interview_repower();
	
		if($pre_time > 0)
		{
			$this->interview_repower_time = count_task_time($str_time,"-",0,$pre_time,0);
		}
	}
	//对终端处理
	function set_task_terminal_ids($task_terminal_ids)
	{
		$string_operate_obj = new string_operate_class($task_terminal_ids);
		
		$this->task_terminal_ids = $string_operate_obj->string_operate();
	}
	//对终端组处理
	function set_task_group_ids($task_group_ids)
	{
		$string_operate_obj = new string_operate_class($task_group_ids);
		
		$this->task_group_ids = $string_operate_obj->string_operate();
	}
	//对媒体处理
	function set_task_media_ids($task_media_ids)
	{
		$string_operate_obj = new string_operate_class($task_media_ids);
		
		$this->task_media_ids = $string_operate_obj->string_operate();
	}
}

//返回读取任务终端信息SQL语句
function get_task_terminal_sql($task_id)
{	

	$task_sql = "SELECT DISTINCT terminal.id,terminal.terminalname,terminaltype.name, ";

	$task_sql.= "terminal.netstate,terminal.devicestate,terminal.taskstate,terminal.ip, ";

	$task_sql.= "terminal.volume, ";

	$task_sql.= "IF(terminaloftask.groupid = 0,\"...\",serverplaystream.name) AS 'groupname' ";

	$task_sql.= "FROM terminaloftask,serverplaystream,terminal,terminaltype ";

	$task_sql.= "WHERE (terminaloftask.groupid=serverplaystream.streamid OR terminaloftask.groupid=0) ";

	$task_sql.= "AND terminal.id = terminaloftask.terminalid AND terminaltype.id = terminal.typeid ";

	$task_sql.= "AND terminaloftask.taskid = '$task_id' ";
	
	return $task_sql;
}
//读取任务媒体ID
function get_task_media_info($task_id)
{
	global $con;
	$media_info = array();

	$task_sql = "SELECT mediaid FROM audioserver.mediaoftask WHERE mediaoftask.taskid = '$task_id'";
	
	$task_result = mysqli_query($con,$task_sql) or die(mysqli_error($con));
	
	while($task_row = mysqli_fetch_array($task_result))
	{
		$media_info[] = $task_row['mediaid'];
	}
	
	@mysqli_free_result($task_result);
	
	unset($task_sql,$task_row);
}
//读取任务终端ID、组ID、返回数组
function get_task_terminal_info($task_id)
{
	global $con;
	$terminal_info = array();
	
	$terminal_sql = "SELECT terminalid,groupid FROM terminaloftask WHERE terminaloftask.taskid = '$task_id'";
	
	$terminal_result = mysqli_query($con,$terminal_sql) or die(mysqli_error($con));
	
	while($terminal_row = mysqli_fetch_array($terminal_result))
	{
		$terminal_info[] = array("terminalid"=>$terminal_row['terminalid'],"groupid"=>$terminal_row['groupid']);
	}
	
	@mysqli_free_result($terminal_result);
	
	unset($terminal_result,$terminal_row);
	
	return $terminal_info;
}
//=========================================================对2、3、4、5任务单独处理
class alone_operate_task_class
{
	//对任务单独处理---文件广播

	//插入---文件广播
	function file_broadcast_add($entire_task_property_obj,$database_operate_obj,$forward_ok_error_obj,$pop_same_name,$pop_info_ok,$pop_info_fail,$path)
	{
		$new_task_id = 0;
		
		$new_pre_task_id = 0;
		//判断同名任务
		if( $database_operate_obj->insert_task_same_name($entire_task_property_obj) == 1)
		{
			$forward_ok_error_obj->exit_back_function($pop_same_name);
		}
		lock_table("task,mediaoftask,terminaloftask");

		start_trans();
		//插入当前任务
		if( $database_operate_obj->insert_sql( create_insert_sql($entire_task_property_obj) ) == -1 )
		{
			roll_back();
			
			unlock_table();

			$forward_ok_error_obj->exit_back_function($pop_info_fail);
		}
		$new_task_id = $database_operate_obj->select_task_max_id();
		//是否欲开电源
		if($entire_task_property_obj->get_prepower() > 0 )
		{
			//设置欲开电源时间
			$entire_task_property_obj->set_power_amplifier_time();
			
			$entire_task_property_obj->set_playtime($entire_task_property_obj->get_power_amplifier_time());
			
			$entire_task_property_obj->set_tasktype(PRE_POWER_TASKTYPE);
			
			$entire_task_property_obj->set_sec_task_id($new_task_id);
			//插入欲开电源
			if( $database_operate_obj->insert_sql( create_insert_sql($entire_task_property_obj) ) == -1 )
			{
				roll_back();
				
				unlock_table();
				
				$forward_ok_error_obj->exit_back_function($pop_info_fail);	
			}
			//取欲开电源任务最大ID
			$new_pre_task_id = $database_operate_obj->select_task_max_id();
			//插入欲开电源的终端
			if($database_operate_obj->add_relationship_terminal($new_pre_task_id,$entire_task_property_obj) == FALSE)
			{
				roll_back();
				
				unlock_table();
				
				$forward_ok_error_obj->exit_back_function($pop_info_fail);	
			}
		}
		//插入终端和媒体
		if($database_operate_obj->add_relationship_terminal($new_task_id,$entire_task_property_obj) == FALSE)
		{
			roll_back();
			
			unlock_table();
			
			$forward_ok_error_obj->exit_back_function($pop_info_fail);	
		}
		if($database_operate_obj->add_relationship_media($new_task_id,$entire_task_property_obj) == FALSE)
		{
			roll_back();
			
			unlock_table();
			
			$forward_ok_error_obj->exit_back_function($pop_info_fail);	
		}
		commit_();
		
		unlock_table();
		//路径跳转
		$forward_ok_error_obj->forward_path(1,$pop_info_ok,$path);//有问题的-------？？？
	}
	//更新---文件广播
	function file_broadcast_update($task_property_obj,$task_id)
	{
		//
	}
	//删除---文件广播
	function file_broadcast_delete($task_property_obj,$task_id)
	{
		//
	}
	//======================================================对任务单独处理---采播管理
	//插入---采播管理
	function acquisition_broadcast_add($entire_task_property_obj,$database_operate_obj,$forward_ok_error_obj,$pop_same_name,$pop_info_ok,$pop_info_fail,$path)
	{
		$new_task_id = 0;
		
		$new_pre_task_id = 0;
		
		$new_col_task_id = 0;
		
		$play_time_tmp = $entire_task_property_obj->get_playtime();//保留
		
		$cmd_tmp = $entire_task_property_obj->get_cmd();//保留
		
		$cmdargs_tmp = $entire_task_property_obj->get_cmdargs();//保留
		
		$prepower_tmp = $entire_task_property_obj->get_prepower();//保留
		//判断同名任务
		if( $database_operate_obj->insert_task_same_name($entire_task_property_obj) == 1)
		{
			$forward_ok_error_obj->exit_back_function($pop_same_name);
		}

		lock_table("task,mediaoftask,terminaloftask");

		start_trans();
		//插入当前任务
		$entire_task_property_obj->set_cmd($entire_task_property_obj->get_audiosource());
		
		$entire_task_property_obj->set_cmdargs($entire_task_property_obj->get_channel());
		
		if( $database_operate_obj->insert_sql( create_insert_sql($entire_task_property_obj) ) == -1 )
		{
			roll_back();
			
			unlock_table();

			$forward_ok_error_obj->exit_back_function($pop_info_fail);
		}
		$new_task_id = $database_operate_obj->select_task_max_id();

		//=======================================================插入采播终端类型为8任务
		$entire_task_property_obj->set_cmd(0);
		
		$entire_task_property_obj->set_tasktype(COL_TERMINAL_TASKTYPE);
		
		$entire_task_property_obj->set_interview_repower_time();
		
		$entire_task_property_obj->set_playtime($entire_task_property_obj->get_interview_repower_time());
		
		$entire_task_property_obj->set_prepower($entire_task_property_obj->get_interview_repower());
		
		$entire_task_property_obj->set_sec_task_id($new_task_id);
		
		if( $database_operate_obj->insert_sql( create_insert_sql($entire_task_property_obj) ) == -1 )
		{
			roll_back();
			
			unlock_table();

			$forward_ok_error_obj->exit_back_function($pop_info_fail);
		}
		$new_col_task_id = $database_operate_obj->select_task_max_id();

		if($database_operate_obj->add_relationship_coll_terminal($new_col_task_id,$entire_task_property_obj->get_audiosource()) == FALSE)
		{
			roll_back();
			
			unlock_table();

			$forward_ok_error_obj->exit_back_function($pop_info_fail);
		}
		//================================================是否欲开电源
		$entire_task_property_obj->set_prepower($prepower_tmp);

		if($entire_task_property_obj->get_prepower() > 0 )
		{
			//设置欲开电源时间
			$entire_task_property_obj->set_cmdargs(0);
			
			$entire_task_property_obj->set_playtime($play_time_tmp);
			
			$entire_task_property_obj->set_power_amplifier_time();
			
			$entire_task_property_obj->set_playtime($entire_task_property_obj->get_power_amplifier_time());
			
			$entire_task_property_obj->set_tasktype(PRE_POWER_TASKTYPE);
			
			$entire_task_property_obj->set_sec_task_id($new_task_id);
			//插入欲开电源
			if( $database_operate_obj->insert_sql( create_insert_sql($entire_task_property_obj) ) == -1 )
			{
				roll_back();
				
				unlock_table();
				
				$forward_ok_error_obj->exit_back_function($pop_info_fail);	
			}
			//取欲开电源任务最大ID
			$new_pre_task_id = $database_operate_obj->select_task_max_id();
			//插入欲开电源的终端
			if($database_operate_obj->add_relationship_terminal($new_pre_task_id,$entire_task_property_obj) == FALSE)
			{
				roll_back();
				
				unlock_table();
				
				$forward_ok_error_obj->exit_back_function($pop_info_fail);	
			}
		}		
		//插入终端
		if($database_operate_obj->add_relationship_terminal($new_task_id,$entire_task_property_obj) == FALSE)
		{
			roll_back();
			
			unlock_table();
			
			$forward_ok_error_obj->exit_back_function($pop_info_fail);	
		}
		commit_();
		
		unlock_table();
		//路径跳转
		$forward_ok_error_obj->forward_path(1,$pop_info_ok,$path);
	}
	//更新---采播管理
	function acquisition_broadcast_update($task_property_obj,$task_id)
	{
		//
	}
	//删除---采播管理
	function acquisition_broadcast_delete($task_property_obj,$task_id)
	{
		//
	}
	//=============================================================对任务单独处理---电话采播广播
	//========================添加电话采播广播
	function telephone_broadcast_add($entire_task_property_obj,$database_operate_obj,$forward_ok_error_obj,$pop_same_name,$pop_info_ok,$pop_info_fail,$path)
	{
		$new_task_id = 0;
		
		$new_pre_task_id = 0;
		
		$new_col_task_id = 0;
		
		$play_time_tmp = $entire_task_property_obj->get_playtime();//保留
		
		$cmd_tmp = $entire_task_property_obj->get_cmd();//保留
		
		$cmdargs_tmp = $entire_task_property_obj->get_cmdargs();//保留
		
		$prepower_tmp = $entire_task_property_obj->get_prepower();//保留
		//判断同名任务
		if( $database_operate_obj->insert_task_same_name($entire_task_property_obj) == 1)
		{
			$forward_ok_error_obj->exit_back_function($pop_same_name);
		}

		lock_table("task,mediaoftask,terminaloftask");

		start_trans();
		//插入当前任务
		$entire_task_property_obj->set_cmd($entire_task_property_obj->get_audiosource());
		
		$entire_task_property_obj->set_cmdargs($entire_task_property_obj->get_channel());
		
		if( $database_operate_obj->insert_sql( create_insert_sql($entire_task_property_obj) ) == -1 )
		{
			roll_back();
			
			unlock_table();

			$forward_ok_error_obj->exit_back_function($pop_info_fail);
		}
		$new_task_id = $database_operate_obj->select_task_max_id();

		//=======================================================插入电话采播采播终端类型为8任务
		$entire_task_property_obj->set_cmd(0);
		
		$entire_task_property_obj->set_tasktype(COL_TERMINAL_TASKTYPE);
		
		$entire_task_property_obj->set_interview_repower_time();
		
		$entire_task_property_obj->set_playtime($entire_task_property_obj->get_interview_repower_time());
		
		$entire_task_property_obj->set_prepower($entire_task_property_obj->get_interview_repower());
		
		$entire_task_property_obj->set_sec_task_id($new_task_id);
		
		if( $database_operate_obj->insert_sql( create_insert_sql($entire_task_property_obj) ) == -1 )
		{
			roll_back();
			
			unlock_table();

			$forward_ok_error_obj->exit_back_function($pop_info_fail);
		}
		$new_col_task_id = $database_operate_obj->select_task_max_id();

		if($database_operate_obj->add_relationship_coll_terminal($new_col_task_id,$entire_task_property_obj->get_audiosource()) == FALSE)
		{
			roll_back();
			
			unlock_table();

			$forward_ok_error_obj->exit_back_function($pop_info_fail);
		}
		//================================================是否欲开电源
		$entire_task_property_obj->set_prepower($prepower_tmp);

		if($entire_task_property_obj->get_prepower() > 0 )
		{
			//设置欲开电源时间
			$entire_task_property_obj->set_cmdargs(0);
			
			$entire_task_property_obj->set_playtime($play_time_tmp);
			
			$entire_task_property_obj->set_power_amplifier_time();
			
			$entire_task_property_obj->set_playtime($entire_task_property_obj->get_power_amplifier_time());
			
			$entire_task_property_obj->set_tasktype(PRE_POWER_TASKTYPE);
			
			$entire_task_property_obj->set_sec_task_id($new_task_id);
			//插入欲开电源
			if( $database_operate_obj->insert_sql( create_insert_sql($entire_task_property_obj) ) == -1 )
			{
				roll_back();
				
				unlock_table();
				
				$forward_ok_error_obj->exit_back_function($pop_info_fail);	
			}
			//取欲开电源任务最大ID
			$new_pre_task_id = $database_operate_obj->select_task_max_id();
			//插入欲开电源的终端
			if($database_operate_obj->add_relationship_terminal($new_pre_task_id,$entire_task_property_obj) == FALSE)
			{
				roll_back();
				
				unlock_table();
				
				$forward_ok_error_obj->exit_back_function($pop_info_fail);	
			}
		}		
		//插入终端
		if($database_operate_obj->add_relationship_terminal($new_task_id,$entire_task_property_obj) == FALSE)
		{
			roll_back();
			
			unlock_table();
			
			$forward_ok_error_obj->exit_back_function($pop_info_fail);	
		}
		commit_();
		
		unlock_table();
		//路径跳转
		$forward_ok_error_obj->forward_path(1,$pop_info_ok,$path);
	}
	//更新---电话采播广播
	function telephone_broadcast_update($task_property_obj,$task_id)
	{
		//
	}
	//删除---电话采播广播
	function telephone_broadcast_delete($task_property_obj,$task_id)
	{
		//
	}
	//==============================================================对任务单独处理---终端功放
	//============================添加终端功放
	function terminal_amplifier_add($entire_task_property_obj,$database_operate_obj,$forward_ok_error_obj,$pop_same_name,$pop_info_ok,$pop_info_fail,$path)
	{
		$new_task_id = 0;
		
		$new_pre_task_id = 0;
		//判断同名任务
		if( $database_operate_obj->insert_task_same_name($entire_task_property_obj) == 1)
		{
			$forward_ok_error_obj->exit_back_function($pop_same_name);
		}
		lock_table("task,mediaoftask,terminaloftask");

		start_trans();
		//插入当前任务
		if( $database_operate_obj->insert_sql( create_insert_sql($entire_task_property_obj) ) == -1 )
		{
			roll_back();
			
			unlock_table();

			$forward_ok_error_obj->exit_back_function($pop_info_fail);
		}
		$new_task_id = $database_operate_obj->select_task_max_id();
		//插入结束终端功放任务
		
		//插入终端和媒体
		if($database_operate_obj->add_relationship_terminal($new_task_id,$entire_task_property_obj) == FALSE)
		{
			roll_back();
			
			unlock_table();
			
			$forward_ok_error_obj->exit_back_function($pop_info_fail);	
		}
		
		commit_();
		
		unlock_table();
		//路径跳转
		$forward_ok_error_obj->forward_path(1,$pop_info_ok,$path);//有问题的-------？？？
	}
	//更新---终端功放
	function terminal_amplifier_update($task_property_obj,$task_id)
	{
		//
	}
	//删除---终端功放
	function terminal_amplifier_delete($task_property_obj,$task_id)
	{
		//
	}
}
?>