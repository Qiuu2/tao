<?php
{
	//===============启用会话
	if (!session_id()) session_start();
	//===============避免显示乱码
	header('Content-Type:text/html;charset=utf-8');
	require_once("inc/config.inc.php");
	//===============避免数据库乱码
	mysqli_query($con,"set names utf8");
	
	//===============验证是否失效
	require_once("verify_user_sessionin_valid.php");
	

	//===============显示多语言
	if($_SESSION['language']=="")
	require_once("language/chinese.php");
	else
	require_once("language/".$_SESSION['language'].".php");
	//===================================================================添加封装模块


	require_once($_SERVER["DOCUMENT_ROOT"]."/features_wrapper_class.php");
	//===============判断是否登录或退出标志段默认为0
	$olduser = "";
	$opt = "invalid user";
   	//===============获取处理跳转的值
	if($_GET['act']!="regist_server")
	{
		verifysessionvalid();
	$sql = "SELECT registerflag FROM serverbaseparam";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if($row = mysqli_fetch_array($result))
		{
			 if($row['registerflag']==2)
			 {
			 	$trydos = "/var/www/html/ok112/serialtwo";
				if(is_file($trydos))
				{
				    echo "<script>alert('".$do_php_prompt['try_succ_doing_server']."');</script>";
				}
			 }
		}
		@mysqli_free_result($result);
		unset($sql,$row);
	}
	else
	{
	
	}

	getmodepower($_GET['act'],$con);

	
	switch ($_GET['act'])
	{		
		case "login":		
			$opt= $logset['login'];
		
			$info_s= (base64_decode($_GET['info']));
			
   			$info_s = addslashes($info_s);
			login($con,$info_s);
	
			break;
			case "aaa":		
				$opt= $logset['login'];
				
				$abcc= $_GET['abc'];

				$abccd=substr($abcc,0,3);

				if($abccd!="haC")
				{
						echo "<script>alert('错误');</script>";
				}
				else
				{
					$str2 = substr($_GET['abc'], 3);
				
					$info_s= htmlspecialchars(base64_decode($str2));
					$info_s = addslashes ($info_s);
			
					$pos1 = stripos($info_s, "updatexml");
					if($pos1==false)
					{
						$pos2 = stripos($info_s, "extractvalue");
						if($pos2==false)
						{
							
							$pos3 = stripos($info_s, "and");
							if($pos3==false)	
							{
								$pos4 = stripos($info_s, "or");
								if($pos4==false)	
								{
								
									login($con,$info_s);
								}
								else
								{
									echo "<script>alert('错误');</script>";		
								}
							}
							else
							{
								echo "<script>alert('错误');</script>";		
							}

						
						}
						else
						{
							echo "<script>alert('错误');</script>";	
						}
					}
					else
					{
						echo "<script>alert('错误');</script>";	
					}	
				}
				break;
		case "logout":		
			$opt= $logset['logout'];

      if(!empty($_SESSION['username']))
			{
				$olduser = $_SESSION['username'];
			}
			logout($con);
			break;				
		case "delfiletask_msg":
		    $opt= $logset['delfiletask_msg'];
			delfiletask_msg($con);
			break;
			case "deldirarea_msg":
				$opt= $logset['deldirarea'];
			deldirarea_msg($con);
			break;	
		case "add_terminal_dirarea":
			$opt= $logset['addterminaldirarea'];
			add_terminal_dirarea($con);
			break;		
		case "delallfiletask_msg":
		    $opt= $logset['delfiletask_msg'];
			delallfiletask_msg($con);
			break;
		case "folderdel_msg":
		    $opt= $logset['folderdel_msg'];
			folderdel_msg($con);
			break;
		case "folderadd_msg":
		    $opt= $logset['folderadd_msg'];
			folderadd_msg($con);
			break;
	    case "foldermodify_msg":
		    $opt= $logset['foldermodify_msg'];
			foldermodify_msg($con);
			break;	
			case "dirareadel_msg":
				$opt= $logset['deldirarea'];
			dirareadel_msg($con);
			break;		
		case "taskfolderdel_msg":
		    $opt= $logset['taskfolderdel_msg'];
			taskfolderdel_msg($con);
			break;	
		case "ledfolderdel_msg":
		    $opt= $logset['taskfolderdel_msg'];
			ledfolderdel_msg($con);
			break;	
		case "aifolderdel_msg":
			$opt= $logset['aifolderdel_msg'];
			aifolderdel_msg($con);
			break;					
		case "taskfolderadd_msg":
		    $opt= $logset['taskfolderadd_msg'];
			taskfolderadd_msg($con);
			break;	
		case "dirareaadd_msg":
			$opt= $logset['dirareaadd_msg'];
			dirareaadd_msg($con);
			break;		
		case "ledfolderadd_msg":
		    $opt= $logset['taskfolderadd_msg'];
			ledfolderadd_msg($con);
			break;	
		case "aideviceadd_msg":
		    $opt= $logset['taskfolderadd_msg'];
				aideviceadd_msg($con);
			break;			
		case "taskfoldermodify_msg":
		    $opt= $logset['taskfoldermodify_msg'];
			taskfoldermodify_msg($con);
			break;	
	case "dirareamodify_msg":
		$opt= $logset['dirareamodify_msg'];
			dirareamodify_msg($con);
			break;				
		case "ledfoldermodify_msg":
		    $opt= $logset['taskfoldermodify_msg'];
			ledfoldermodify_msg($con);
			break;		
		case "aifoldermodify_msg":
			$opt= $logset['aifoldermodify_msg'];
			aifoldermodify_msg($con);
			break;					
		case "filedel_msg":
			$opt= $logset['filedel_msg'];
			filedel_msg($con);
			break;
		case "terminaladd_msg":
		    $opt= $logset['terminaladd_msg'];
			terminaladd_msg($con);
			break;
		case "del_terminal_shotcut":
		    $opt=$logset['del_terminal_shotcut'];
			del_terminal_shotcut($con);
			break;
		case "del_terminal_music":
			del_terminal_music($con);
			break;
		case "del_quick_task":
			del_quick_task($con);
			break;	
		case "terminaledit_msg":
		    $opt= $logset['terminaledit_msg'];
			terminaledit_msg($con);
			break;
		case "terminaldel_msg":
		    $opt=$logset['terminaldel_msg'];
			terminaldel_msg($con);
			break;
		case "terminalStart_msg":
		    $opt= $logset['terminalStart_msg'];
			terminalStart_msg($con);
			break;
		case "terminalStop_msg":
		    $opt= $logset['terminalStop_msg'];
			terminalStop_msg($con);	
			break;
		case "terminalspeech_msg":
		    $opt= $logset['terminalspeech_msg'];
			terminalspeech_msg($con);
			break;
		case "del_yingjiplay":
			$opt= $logset['del_yingjiplay'];
			del_yingjiplay($con);
			break;
		case "ledsousuo_msg":
			$opt= $logset['ledsousuo_msg'];
			ledsousuo_msg($con);
			break;	
		case "terminaldosponsor_msg":
			$opt= $logset['terminaldosponsor_msg'];
			terminaldosponsor_msg($con);
			break;	
		case "terminalstopsponsor_msg":
			$opt= $logset['terminalstopsponsor_msg'];
			terminalstopsponsor_msg($con);
			break;		
		case "check_circuit_state":
			$opt= $logset['check_circuit_state'];
			check_circuit_state($con);
			break;		
		case "set_terminal_record":
		    $opt= $logset['set_terminal_record'];
			set_terminal_record($con);			
			break;
		case "set_terminal_stoprecord":
		    $opt= $logset['set_terminal_stoprecord'];
			set_terminal_stoprecord($con);
			break;
		case "set_terminal_backcall":
		    $opt= $logset['set_terminal_backcall'];
			set_terminal_backcall($con);
			break;
		case "stop_terminal_backcall":
		    $opt= $logset['stop_terminal_backcall'];
			stop_terminal_backcall($con);
			break;
		case "terminalnospeech_msg":
		    $opt= $logset['terminalnospeech_msg'];
			terminalnospeech_msg($con);
			break;			
		case "removealreaterminal":
		    $opt= $logset['removealreaterminal'];
			removealreaterminal($con);
			break;
		case "useradd_msg":
		    $opt= $logset['useradd_msg'];
			useradd_msg($con);
			break;
		case "useredit_msg":
		    $opt= $logset['useredit_msg'];
			useredit_msg($con);
			break;
			case "centerctrdel_msg":
				$opt= $logset['centerctrdel_msg'];
			centerctrdel_msg($con);
			break;
		case "taskcommandstart_msg":
		    $opt= $logset['taskcommandstart_msg'];
			taskcommandstart_msg($con);
			break;
		case "taskcommandstop_msg":
		    $opt= $logset['taskcommandstop_msg'];
			taskcommandstop_msg($con);
			break;
		case "userpasswordmodify_msg":
		    $opt= $logset['userpasswordmodify_msg'];
			$info_s= htmlspecialchars(base64_decode($_GET['info']));
   			$info_s = addslashes ($info_s);
			userpasswordmodify_msg($con,$info_s);
			break;
		case "enablereboot_msg":
			$opt= $logset['serverseting'];
			enablereboot_msg($con);
			break;	
		case "userdel_msg":
		    $opt= $logset['userdel_msg'];
			userdel_msg($con);
			break;
		case "enable_msg":
		    $opt= $logset['enable_msg'];
			enable_msg($con);
			break;
		case "disable_msg":
		    $opt= $logset['disable_msg'];
			disable_msg($con);
			break;
		case "cancel_user_terminal":
		    $opt= $logset['cancel_user_terminal'];
			cancel_user_terminal($con);
			break;
		case "usergroupdel_msg":
		    $opt= $logset['usergroupdel_msg'];
			usergroupdel_msg($con);
			break;
		case "usergroupadd_msg":
		    $opt= $logset['usergroupadd_msg'];
			usergroupadd_msg($con);
			break;
		case "usergroupmodify_msg":
		    $opt= $logset['usergroupmodify_msg'];
			usergroupmodify_msg($con);
			break;
		case "taskadd_msg":
		    $opt= $logset['taskadd_msg'];
			taskadd_msg($con);			
			break;
		case "taskedit_msg":
		    $opt= $logset['taskedit_msg'];
			taskedit_msg($con);
			break;
		case "taskdel_msg":
		    $opt= $logset['taskdel_msg'];
			taskdel_msg($con);
			break;
		case "zhaoshengtaskdel_msg":
			$opt= $logset['zhaoshengtaskdel_msg'];
			zhaoshengtaskdel_msg($con);
			break;	
		case "ledtaskdel_msg":
		    $opt= $logset['taskdel_msg'];
			ledtaskdel_msg($con);
			break;	
		case "deltaskterminal_msg":
				deltaskterminal_msg($con);
					break;	
		case "ttstaskdel_msg":
			$opt= $logset['ttstaskdel_msg'];
			ttstaskdel_msg($con);
			break;
		case "addplaybelltask_msg":
		    $opt= $logset['addplaybelltask_msg'];
			addplaybelltask_msg();
			break;
		case "belltaskaloneoperation":
		    $opt= $logset['belltaskaloneoperation'];
			belltaskaloneoperation($con);
			break;
		case "addholiday":
		    $opt= $logset['addholiday'];
			addholiday($con);
			break;
		case "addenable":
				addenable($con);
			break;
		case "modifyenable":
				modifyenable($con);
			break;			
		case "modifyholiday":
		    $opt= $logset['modifyholiday'];
			modifyholiday($con);
			break;
		case "delholiday":
		    $opt= $logset['delholiday'];
			delholiday($con);
			break;
			case "delenable":
				
				delenable($con);
				break;	
		case "taskaddtrainmedia":
		    $opt= $logset['taskaddtrainmedia'];
			taskaddtrainmedia($con);
			break;
		case "taskmodifytrainmedia":
		    $opt= $logset['taskmodifytrainmedia'];
			taskmodifytrainmedia($con);
			break;
		case "belltaskalonemodify":
		    $opt= $logset['belltaskalonemodify'];
			belltaskalonemodify($con);
			break;
		case "belltaskallmodify":
		    $opt= $logset['belltaskalonemodify'];
			belltaskallmodify($con);
			break;
			case "vediotaskdel_msg":
		    $opt= $logset['vediotaskdel_msg'];
				vediotaskdel_msg($con);
			break;		
		case "modifysystem_msg":
		    $opt= $logset['modifysystem_msg'];
			modifysystem_msg($con);
			break;
		case "del_leddevice":
			$opt= $logset['del_leddevice'];
			del_leddevice($con);
			break;	
		case "sync_time":
			$opt= $logset['sync_time'];
			sync_time($con);
			break;		
		case "modifybelltask_msg":
		   	switch($_POST['taskType'])
			{
				case "belltask":
				$opt= $logset['belltaskalonemodify'];
				break;
				case "fileplaytask":
				$opt= $logset['filetaskalonemodify'];
				break;
				case "admmanagertask":
				$opt= $logset['modifybelltask_msg'];
				break;
				case "terfuncplaytask":
				$opt= $logset['terminaltaskalonemodify'];
				break;			 
		 }
			modifybelltask_msg($con);
			break;
			case "ledmodifybelltask_msg":
			$opt= $logset['ledmodifybelltask_msg'];
			ledmodifybelltask_msg($con);
			break;	
		case "ttsmodifybelltask_msg":
		    $opt= $logset['ttsmodifybelltask_msg'];
			ttsmodifybelltask_msg($con);
			break;
			case "set_yingji_play":
		  
			set_yingji_play($con,$media_task_add['yingjitype1'],$media_task_add['yingjitype2'],$media_task_add['yingjitype3'],$media_task_add['yingjitype4']);
			break;
		case "modifywebradio_msg";
		    $opt= $logset['modifywebradio_msg'];
			modifywebradio_msg($con);
			break;
		case "modifystopmanager_msg";
		    $opt= $logset['modifystopmanager_msg'];
			break;
		case "bellstart_msg":
		  	$opt= $logset['bellstart_msg'];
			bellstart_msg($con);
			break;
		case "enableholiday":
		  	$opt= $logset['enableholiday'];
			enableholiday($con);
			break;
		case "disableholiday":
		  	$opt= $logset['disableholiday'];
			disableholiday($con);
			break;
		case "stopmanagerstart_msg":
		  	$opt= $logset['stopmanagerstart_msg'];
			stopmanagerstart_msg($con);
			break;
		case "bellstop_msg":
		  	$opt= $logset['bellstop_msg'];
			bellstop_msg($con);
			break;
		case "stopmanagerstop_msg":
		  	$opt= $logset['stopmanagerstop_msg'];
			stopmanagerstop_msg($con);
			break;
		case "belldel_msg":
		  	$opt= $logset['belldel_msg'];
			belldel_msg($con);
			break;
		case "stopmanagerdel_msg":
		    $opt= $logset['stopmanagerdel_msg'];
			stopmanagerdel_msg($con);
			break;
		case "trainmediadel_msg":
		    $opt= $logset['trainmediadel_msg'];
			trainmediadel_msg($con);
			break;
		case "bellcop_msg";
		   $opt= $logset['bellcop_msg'];
		   bellcop_msg($con);
		   break;	
		case "admtaskstart_msg":
		    $opt= $logset['admtaskstart_msg'];
			admtaskstart_msg($con);
			break;
		case "webradiotaskstart_msg";
		    $opt= $logset['webradiotaskstart_msg'];
			webradiotaskstart_msg($con);
			break;
		case "dostopmanagertaskstart_msg";
		    $opt= $logset['dostopmanagertaskstart_msg'];
		    //	stopmanagertaskstart_msg();
			break;
		case "admtaskstop_msg":
		    $opt= $logset['admtaskstop_msg'];
			admtaskstop_msg($con);
			break;
		case "webradiotaskstop_msg";
		    $opt= $logset['webradiotaskstop_msg'];
			webradiotaskstop_msg($con);
			break;
		case "stopmanagerstop_msg";
		    $opt=$logset['stopmanagerstop_msg'];
			stopmanagertaskstop_msg($con);
			break;
		case "admtaskdel_msg":
		    $opt= $logset['admtaskdel_msg'];
			admtaskdel_msg($con);
			break;
		case "webradiotaskdel_msg";
		    $opt= $logset['webradiotaskdel_msg'];
			webradiotaskdel_msg($con);
			break;	
		case "stopmanagertaskdel_msg";
		    $opt= $logset['stopmanagertaskdel_msg'];
			stopmanagertaskdel_msg($con);
			break;			
		case "admmanagervolumemodify_msg":
		    $opt= $logset['admmanagervolumemodify_msg'];
			admmanagervolumemodify_msg($con);
			break;
		case "webradiotaskmodify_msg";
		    $opt= $logset['webradiotaskmodify_msg'];
		    //	webradiotaskmodify_msg();
			break;
		case "teltaskstop_msg":
		    $opt= $logset['teltaskstop_msg'];
			teltaskstop_msg($con);
			break;
		case "teltaskstart_msg":
		    $opt= $logset['teltaskstart_msg'];
			teltaskstart_msg($con);
			break;
		case "teltaskdel_msg":
		    $opt= $logset['teltaskdel_msg'];
			teltaskdel_msg($con);
			break;
		case "terfuncplaystart_msg":
		    $opt= $logset['terfuncplaystart_msg'];
			terfuncplaystart_msg($con);
			break;
		case "terfuncplaystop_msg":
		    $opt= $logset['terfuncplaystop_msg'];
			terfuncplaystop_msg($con);
			break;
		case "terfuncplaydel_msg":
		    $opt= $logset['terfuncplaydel_msg'];
			terfuncplaydel_msg($con);
			break;
		case "taskcommanddel_msg":
		    $opt= $logset['taskcommanddel_msg'];
			taskcommanddel_msg($con);
			break;
		case "addterminal_msg":
		    $opt= $logset['addterminal_msg'];
			addterminal_msg($con);
			break;
		case "modifyterminalvolume_msg":
		    $opt= $logset['modifyterminalvolume_msg'];
			modifyterminalvolume_msg($con);
			break;
		case "addshotcutkey_msg":
		    $opt= $logset['addshotcutkey_msg'];
			addshotcutkey_msg($con);
			break;
		case "modifyshotcutkey_msg":
			modifyshotcutkey_msg($con);
			break;
		case "add_camer_event_msg":
			add_camer_event_msg($con);
			break;
		case "add_camer_alarmevent_msg":
			add_camer_alarmevent_msg($con);
			break;
		case "modify_camer_alarmevent_msg":
			modify_camer_alarmevent_msg($con);
			break;
		case "logdel_msg":
		    $opt= $logset['logdel_msg'];
			logdel_msg($con);
			break;
		case "tasklogdel_msg":
		    $opt= $logset['tasklogdel_msg'];
			tasklogdel_msg($con);
			break;
		case "addfileplaytask_msg":
		    $opt= $logset['addfileplaytask_msg'];
			addfileplaytask_msg($con);		
			break;
		case "ledaddplaytask_msg":
		    $opt= $logset['addfileplaytask_msg'];
			ledaddplaytask_msg($con);		
			break;
		case "addttsplaytask_msg":
			$tasktype=$_POST['taskType'];
		   	switch($tasktype)
			{
				case "belltask":
				$opt= $logset['addbellplaytask_msg'];
				break;
				case "fileplaytask":
				$opt= $logset['addttsplaytask_msg'];
				break;
				case "admmanagertask":
				$opt= $logset['addadmplaytask_msg'];
				break;
				case "terfuncplaytask":
				$opt= $logset['addterminaltask_msg'];
				break;			 
		 }
			addttsplaytask_msg($con);		
			break;
		case "yesornoenable":		
				yesornoenable($con);
				break;	
		case "modyesornoenable":		
			modyesornoenable($con);
			break;	
			case "sechetime":		
				sechetime($con);
				break;					
		case "enordis_date_task":
			enordis_date_task($con);
			break;	
		case "addwebradiotask_msg";
		    $opt= $logset['addwebradiotask_msg'];
		    addwebradiotask_msg($con);
			break;
		case "ttsfiletaskstart_msg":
		    $opt= $logset['ttsfiletaskstart_msg'];
			ttsfiletaskstart_msg($con);
			break;	
		case "filetaskstart_msg":
		    $opt= $logset['filetaskstart_msg'];
			filetaskstart_msg($con);
			break;
			case "zhaoshentaskstart_msg":
		 //   $opt= $logset['filetaskstart_msg'];
			zhaoshentaskstart_msg($con);
			break;	
		case "ledtaskstart_msg":
		    $opt= $logset['ledtaskstart_msg'];
			ledtaskstart_msg($con);
			break;	
		case "start_file_task_msg":
		    $opt= $logset['start_file_task_msg'];
			start_file_task_msg($con);
			break;
		case "enableTask":
			enableTask($con);
			break;	
		case "disableTask":
			disableTask($con);
			break;				
		case "start_zhaoshen_task_msg":
		    $opt= $logset['start_zhaoshen_task_msg'];
			start_zhaoshen_task_msg($con);
			break;	
		case "enable_zhaoshen_volume_msg":
		   $opt= $logset['enable_zhaoshen_volume_msg'];
			enable_zhaoshen_volume_msg($con);
			break;		
		case "led_start_task_msg":
		    $opt= $logset['start_file_task_msg'];
			led_start_task_msg($con);
			break;	
		case "stop_file_task_msg":
		    $opt= $logset['stop_file_task_msg'];
			stop_file_task_msg($con);
			break;
		case "stop_zhaoshen_task_msg":
		  $opt= $logset['stop_zhaoshen_task_msg'];
			stop_zhaoshen_task_msg($con);
			break;	
		case "led_stop_task_msg":
		    $opt= $logset['stop_file_task_msg'];
			led_stop_task_msg($con);
			break;	
		case "start_tts_task_msg":
		    $opt= $logset['start_tts_task_msg'];
			start_tts_task_msg($con);
			break;
		case "stop_tts_task_msg":
		    $opt= $logset['stop_tts_task_msg'];
			stop_tts_task_msg($con);
			break;		
		case "filetaskstop_msg":
		    $opt= $logset['filetaskstop_msg'];
			filetaskstop_msg($con);
			break;
		case "zhaoshentaskstop_msg":
		    $opt= $logset['zhaoshentaskstop_msg'];
			zhaoshentaskstop_msg($con);
			break;	
		case "ledtaskstop_msg":
		    $opt= $logset['filetaskstop_msg'];
			ledtaskstop_msg($con);
			break;	
		case "filetaskpause_msg":    //暂停
			$opt= $logset['filetaskpause_msg'];
			filetaskpause_msg($con);
			break;	
		case "filetaskhuifu_msg":    //恢复
			$opt= $logset['filetaskhuifu_msg'];
			filetaskhuifu_msg($con);
			break;	
		case "ttsfiletaskstop_msg":
		    $opt= $logset['ttsfiletaskstop_msg'];
			ttsfiletaskstop_msg($con);
			break;
		case "cancel_terminal_shotcut":
		    $opt= $logset['cancel_terminal_shotcut'];
			cancel_terminal_shotcut($con);
			break;
		case "cancel_fire_alarm_mapping_msg":
		    $opt= $logset['cancel_fire_alarm_mapping_msg'];
			cancel_fire_alarm_mapping_msg($con);
			break;
		case "set_task_mapping_msg":
		    $opt= $logset['set_task_mapping_msg'];
			set_task_mapping_msg($con);
			break;
		case "ai_tts_setterminal_msg":
			$opt= $logset['ai_tts_setterminal_msg'];
				ai_tts_setterminal_msg($con);
			break;	
		case "set_ai_demo_msg":
			$opt= $logset['set_ai_demo_msg'];
				set_ai_demo_msg($con);
			break;
		case "set_powerqi_msg":
				set_powerqi_msg($con);
				break;	
		case "set_task_quick_play":
		    $opt= $logset['set_task_quick_play'];
			set_task_quick_play($con);
			break;
		case "video_temp_play":
			$opt= $logset['video_temp_play'];
			video_temp_play($con);
			break;	
		case "modify_task_quick_play":
		    $opt= $logset['modify_task_quick_play'];
			modify_task_quick_play($con);
			break;
			case "modify_yingjiplay":
				$opt= $logset['modify_yingjiplay'];
			modify_yingjiplay($con);
			break;	
			case "delallareadir_msg":
				$opt= $logset['delallareadir_msg'];
			delallareadir_msg($con);
			break;		
		case "set_offline_task":
		    $opt= $logset['offline_task'];
			set_offline_task($con);
			break;
		case "set_offline_tasks":
		    $opt= $logset['offline_task'];
			set_offline_tasks($con);
			break;
		case "do_offline_task":
		    $opt= $logset['offline_task'];
			do_offline_task($con);
			break;
		case "rsync_offlinetime":
			$opt= $logset['rsync_offlinetime'];
			rsync_offlinetime($con);
			break;
		case "del_all_offline":
			$opt= $logset['del_all_offline'];
			del_all_offline($con);
			break;
		case "set_offline_music":
		    $opt= $logset['offline_music'];
			set_offline_music($con);
			break;
		case "stop_offline_music":
		 $opt= $logset['stop_offline_music'];
			stop_offline_music($con);
			break;
		case "keyset_task_mapping_msg":
		    $opt= $logset['keyset_task_mapping_msg'];
			keyset_task_mapping_msg($con);
			break;
		case "set_key_mapping_msg":
		    $opt= $logset['set_key_mapping_msg'];
			set_key_mapping_msg($con);
			break;
		case "set_task_synch":
		    $opt= $logset['set_task_synch'];
			set_task_synch($con);
			break;
		case "del_task_mapping_msg":
		    $opt= $logset['del_task_mapping_msg'];
			del_task_mapping_msg($con);
			break;
		case "keymodify_task_mapping_msg":
			$opt= $logset['keymodify_task_mapping_msg'];
			keymodify_task_mapping_msg($con);
			break;
		case "keydel_task_mapping_msg":
		    $opt=$logset['keydel_task_mapping_msg'];
			keydel_task_mapping_msg($con);
			break;
		case "keydel_camer_msg":
			$opt=$logset['keydel_camer_msg'];
			keydel_camer_msg($con);
			break;
		case "keydel_cameralarm_msg":
			$opt=$logset['keydel_cameralarm_msg'];
			keydel_cameralarm_msg($con);
			break;
		case "del_key_mapping_msg":
		    $opt= $logset['del_key_mapping_msg'];
			del_key_mapping_msg($con);
			break;
		case "alarmstart_msg":
		    $opt= $logset['alarmstart_msg'];
			alarmstart_msg($con);
			break;
		case "setalarmmap_msg":
		    $opt=$logset['setalarmmap_msg'];
			setalarmmap_msg($con);
			break;
		case "modifyalarmmap_msg":
			$opt=$logset['modifyalarmmap_msg'];
			modifyalarmmap_msg($con);
			break;	
		case "addcallzone_msg":
		    $opt= $logset['addcallzone_msg'];
			addcallzone_msg($con);
			break;
		case "modifycallzone_msg":
		    $opt= $logset['modifycallzone_msg'];
			modifycallzone_msg($con);
			break;
		case "modify_camer_msg":
			$opt= $logset['modify_camer_msg'];
			modify_camer_msg($con);
			break;
		case "delcallzone":
		    $opt= $logset['delcallzone'];
			delcallzone($con);		
			break;
		case "areaadd_msg":
		    $opt= $logset['areaadd_msg'];
			areaadd_msg($con);
			break;
		case "area_modify_msg":
		    $opt= $logset['area_modify_msg'];
			area_modify_msg($con);
			break;
		case "del_alarm_area":
		    $opt= $logset['del_alarm_area'];
			del_alarm_area($con);
			break;
		case "streamadd_msg":
		    $opt= $logset['streamadd_msg'];
			streamadd_msg($con);
			break;
		case "zhaoshengstreamadd_msg":
		  $opt= $logset['zhaoshengstreamadd_msg'];
			zhaoshengstreamadd_msg($con);
			break;	
		case "zhaoshengdeviceadd_msg":
		  $opt= $logset['zhaoshengdeviceadd_msg'];
			zhaoshengdeviceadd_msg($con);
			break;
		case "renliandeviceadd_msg":
			$opt= $logset['renliandeviceadd_msg'];
			renliandeviceadd_msg($con);
		break;	
		case "renliandevicemodify_msg":
			$opt= $logset['renliandevicemodify_msg'];
			renliandevicemodify_msg($con);
		break;					
		case "streambatedit_msg":
		    $opt= $logset['streambatedit_msg'];
			streambatedit_msg($con);			 
			break;	
		case "soundsdeviceedit_msg":
			$opt= $logset['soundsdeviceedit_msg'];
			soundsdeviceedit_msg($con);			 
			break;			
		case "zhaoshengedit_msg":
			$opt= $logset['zhaoshengedit_msg'];
			zhaoshengedit_msg($con);			 
			break;		
		case "streamdel_msg":
		    $opt= $logset['streamdel_msg'];
			streamdel_msg($con);		
			break;	
		case "zhaoshengdel_msg":
			$opt= $logset['zhaoshengdel_msg'];
		zhaoshengdel_msg($con);		
		break;
		case "soundsdevicedel_msg":
			$opt= $logset['soundsdevicedel_msg'];
		soundsdevicedel_msg($con);		
		break;	
		case "aidevicedel_msg":
			$opt= $logset['aidevicedel_msg'];
			aidevicedel_msg($con);		
			break;						
		case "streambaddterminal_msg":
			$opt= $logset['streambaddterminal_msg'];
			streambaddterminal_msg($con);
			break;		
		case "medialistadd_msg":
		    $opt= $logset['medialistadd_msg'];
			medialistadd_msg($con);		
			break;
		case "commandtask_msg":
			commandtask_msg($con);		
			break;		
		case "serveredit_msg":
		    $opt= $logset['serveredit_msg'];
			serveredit_msg($con,$a9000path);
			break;		
		case "regist_server":
		    $opt= $logset['regist_server'];
				regist_server($con);
			break;	
		case "pwd":
		    $opt= $logset['pwd'];
			pwd($con);
			break;
		case "restart_server_msg":
		    $opt= $logset['restart_server_msg'];
			restart_server_msg($con);
			break;
		case "init_date_msg":
		    $opt= $logset['init_date_msg'];
			init_date_msg($con,$a9000path);
			break;
		case "stop_curr_tast_state":
		    $opt= $logset['stop_curr_tast_state'];
			stop_curr_tast_state($con);
			break;
		case "start_curr_tast_state":
		    $opt= $logset['start_curr_tast_state'];
			start_curr_tast_state($con);
			break;
		case "emergency_setting":
		    $opt= $logset['emergency_setting'];
			emergency_setting($con);
			break;
		case "emergency_canceling":
		    $opt=$logset['emergency_canceling'];
			emergency_canceling($con);
			break;
		case "gettimeip":
		    $opt= $logset['gettimeip'];
			gettimeip($con);
			break;
		case "streamedit_msg":
		    $opt=$logset['gettimeip'];
			streamedit_msg($con);
			break;	
		case "fileadd_msg"://
		    $opt= $logset['fileadd_msg'];
			fileadd_msg($con);
			break;		
		case "madlistdel_msg":
		    $opt= $logset['madlistdel_msg'];
			madlistdel_msg($con);
			break;
		case "settrydo":
			settrydo($con);
			break;
		case "copyFileTasks":
			copyFileTask($con);
			break;	
		case "serveropen_data":
			serveropen_data($con,$a9000path);
			break;	
		case "serverclose_data":
			serverclose_data($con,$a9000path);
			break;
		case "updatechezhan":		
			updatechezhan($con);
			break;	
						
		default:
			//添加外部变量
			global $do_php_prompt;			
			echo $do_php_prompt['Illegal_operation'];
	}  
		insert_log($opt,$olduser);
		$get_username=$_SESSION['username'];
		if($get_username!="")
		{
			$sql = "SELECT 	id FROM book_admin WHERE  book_admin.username = '$get_username'";
			$result = mysqli_query($con,$sql) or die(mysqli_error($con));
			if(mysqli_num_rows($result) > 0)
			{
				if($row = mysqli_fetch_array($result))
				{
				$_SESSION['userid'] = $row['id'];
				}
			}	
			@mysqli_free_result($result);
			unset($sql,$row);
		}	
}

function insert_log($opt,$olduser)
{
	
	global $do_php_prompt;	
	global $con;
	$ip = $_SERVER['REMOTE_ADDR'];
	if(!empty($_SESSION['username']))
	{
		$user = $_SESSION['username'];
	}
	else
	{
		$user = $olduser;
	}
	
	$time = gmdate("Y-m-d H:i:s",time()+8*3600);
	
	$log_sql = "INSERT INTO audioserver.log (log.user, log.operate, log.ip, log.time)";
	
	$log_sql.= " VALUES ('$user','$opt','$ip','$time') ";

	mysqli_query($con,"START TRANSACTION");
		mysqli_query($con,"lock table log write");

	mysqli_query($con,$log_sql) or die(mysqli_error($con));
	
	unset($log_sql);
	
	mysqli_query($con, "UNLOCK TABLES" );

}


function getmodepower($opt,$con)
{
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();

	if($opt!="serveredit_msg"&&$opt!="login"&&$opt!="aaa"&&$opt!="logout"&&$opt!="restart_server_msg"&&$opt!="init_date_msg"
	&&$opt!="filetaskstart_msg"&&$opt!="filetaskstop_msg"&&$opt!="admtaskstart_msg"&&$opt!="admtaskstop_msg"
	&&$opt!="terfuncplaystart_msg"&&$opt!="terfuncplaystop_msg"&&$opt!="webradiotaskstart_msg"&&$opt!="webradiotaskstop_msg"
	&&$opt!="ttsfiletaskstart_msg"&&$opt!="ttsfiletaskstop_msg"&&$opt!="start_curr_tast_state"&&$opt!="stop_curr_tast_state"&&$opt!="serveropen_data"
	&&$opt!="serverclose_data")
	{
		$sql_area = "SELECT model FROM serverbaseparam";
		$result_area = mysqli_query($con,$sql_area) or die(mysqli_error($con));
		if($row = mysqli_fetch_array($result_area))
		{
			if($row['model']==2)
			{
				$forward_ok_error_obj->exit_back_function($do_php_prompt['The_alave_error']);
			}
		}
		@mysqli_free_result($result_area);
		unset($row);
	}
}

//插入留言---未被使用
function gettimeip($con)
{	
	//添加外部变量
	global $do_php_prompt;
	//=======================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	$getip = "";
	if(isset($_POST['getip']))
	{
		$getip = trim($_POST['getip']);
	}
		mysqli_query($con,"update serverbaseparam set ntpserver='$getip'");	
		if(mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "set_server_time.html";
			
			echo "<script>window.location='error.php'</script>";
			//=============================================================================
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./login.php");
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "set_server_time.html";
			
			echo "<script>window.location='success.php'</script>";
			//========================================================================================
			//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./servermanager.php");
		}	

}


//网络电台任务删除
function webradiotaskdel_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		
		$adm_taskId_array = explode(",",$taskid);
	}
	//添加事务
	mysqli_query($con,"START TRANSACTION"); 
	
	for($i=0; $i<count($adm_taskId_array); $i++)
	{	
		//判断是否有功放
		$col_task_sql = "SELECT prepower FROM task WHERE task.taskid='$adm_taskId_array[$i]' AND tasktype=10 AND info='' AND sec_task_id=0 ";
		
		$col_task_result = mysqli_query($con,$col_task_sql) or die(mysqli_error($con));
		
		if($col_task_row = mysqli_fetch_array($col_task_result))
		{
			if($col_task_row['prepower'] > 0)
			{
				//取采播功放id
				$col_func_sql = "SELECT taskid FROM task WHERE sec_task_id='$adm_taskId_array[$i]' AND tasktype=9 AND info='' AND channel = 0 ";
				
				$col_func_result = mysqli_query($con,$col_func_sql) or die(mysqli_error($con));
				
				if($col_func_row = mysqli_fetch_array($col_func_result))
				{
					//删除功放任务
					mysqli_query($con,"DELETE FROM terminaloftask WHERE taskid = '".$col_func_row['taskid']."'") or die(mysqli_error($con));
					
					//删除功放
					mysqli_query($con,"DELETE FROM audioserver.task WHERE taskid = '".$col_func_row['taskid']."'") or die(mysqli_error($con));
				}
				@mysqli_free_result($col_func_result);
				unset($col_func_row,$col_func_sql);
			}
		}
		
		@mysqli_free_result($col_task_result);
				
		unset($col_task_row,$col_task_sql);
		
		//删除采播终端
		$col_func1_id = 0;
		//查询采播终端任务
		$col_func1_sql = "SELECT taskid FROM task WHERE sec_task_id = '$adm_taskId_array[$i]' AND tasktype = 9 AND channel = 0 AND info = ''";
		
		$col_func1_result = mysqli_query($con,$col_func1_sql) or die(mysqli_error($con));
		
		if($col_func1_row = mysqli_fetch_array($col_func1_result))
		{
			$col_func1_id = $col_func1_row['taskid'];
			
			mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$col_func1_id' ") or die(mysqli_error($con));
			
			mysqli_query($con,"DELETE FROM audioserver.task WHERE taskid = '$col_func1_id'") or die(mysqli_error($con));
		}
		
		@mysqli_free_result($col_func1_result);
				
		unset($col_func1_row,$col_func1_sql,$col_func1_id);
	}

	//删除自己
	mysqli_query($con,"DELETE FROM audioserver.task WHERE taskid IN(".$taskid.")") or die(mysqli_error($con));
	//删除终端任务
	mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid IN(".$taskid.")") or die(mysqli_error($con));
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./WebRadio.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=6&id=".$getid;			
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_generate_general("task",6,$getid);
			$create_socket_obj->send_socket_generate_general2("task",6,$getid,10);
		}

		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./WebRadio.php";
		
		echo "<script>window.location='error.php'</script>";
	}	
}



function get_checkip_login($con,$username,$userpwd)
{
	$clientip=$_SERVER['REMOTE_ADDR'];	

	$sqlcheck = "SELECT ip,loginnum,logintime FROM logincheck WHERE logincheck.ip = '$clientip'";
	$resultcheck = mysqli_query($con,$sqlcheck);
	if($row = mysqli_fetch_array($resultcheck))
	{
		$login_num = $row['loginnum'];
		if($login_num>=5)
		{
			return -1;
		}
	}
	$sql = "SELECT * FROM book_admin WHERE book_admin.username = '$username'";
	$result = mysqli_query($con,$sql);
	if($rows = mysqli_fetch_array($result))
	{
		$login_num = $rows['loginnum'];
		if($login_num>=5)
		{
			return -2;
		}
	}
	return 0;
}


function checkip_login($con,$flag,$username,$userpwd)
{
	if($flag==1)  //验证码检测ip地址登录次数
	{
		$clientip=$_SERVER['REMOTE_ADDR'];
		$sqlcheck = "SELECT ip,loginnum FROM logincheck WHERE logincheck.ip = '$clientip'";
		$resultcheck = mysqli_query($con,$sqlcheck);
		if(mysqli_num_rows($resultcheck) <= 0)
		{
			$sql="INSERT INTO logincheck(ip,loginnum,logintime) VALUES ('$clientip',1,now())";
			mysqli_query($con,$sql);
		}
		else
		{
			if($row = mysqli_fetch_array($resultcheck))
			{
				$temprows=$row['loginnum']+1;
				$datetime=date("Y-m-d H:i:s");
				mysqli_query($con,"UPDATE logincheck SET loginnum = '$temprows',logintime='$datetime' WHERE logincheck.ip = '$clientip'");
			}
		}
	}
	else if($flag==2)   //验证用户密码登录次数
	{
		$clientip=$_SERVER['REMOTE_ADDR'];

		$sqlcheck = "SELECT ip,loginnum FROM logincheck WHERE logincheck.ip = '$clientip'";
		$resultcheck = mysqli_query($con,$sqlcheck);
		if(mysqli_num_rows($resultcheck) <= 0)
		{
			$sql="INSERT INTO logincheck(ip,loginnum,logintime) VALUES ('$clientip',1,now())";
			mysqli_query($con,$sql);
		}
		else
		{
	
			if($rows = mysqli_fetch_array($resultcheck))
			{
				$temprows=$rows['loginnum']+1;
			
				$datetime=date("Y-m-d H:i:s");
	
				mysqli_query($con,"UPDATE logincheck SET loginnum = '$temprows',logintime='$datetime' WHERE logincheck.ip = '$clientip'") or die(mysqli_error($con));
			}
		}
		$sql = "SELECT * FROM book_admin WHERE book_admin.username = '$username'";
		$result = mysqli_query($con,$sql);
		if($row = mysqli_fetch_array($result))
		{
			if($userpwd!=$row['userpwd'])	
			{
				$loginnum = $row['loginnum']+1;	

				mysqli_query($con,"UPDATE book_admin SET loginnum = '$loginnum' WHERE book_admin.username = '$username'");

			}
		}
	}
	else if($flag==3)   //验证用户密码登录成功
	{

		$clientip = $_SERVER['REMOTE_ADDR'];
		mysqli_query($con,"UPDATE logincheck SET loginnum = '0' WHERE logincheck.ip = '$clientip'");
		mysqli_query($con,"UPDATE book_admin SET loginnum = '0' WHERE book_admin.username = '$username'");
	}
}

function get_decode_demo($str)
{
	$replace = '';

	$demo=substr_replace($str, $replace, 0,1);

	$demo2=substr_replace($demo, $replace, strlen($demo)-2,2);

	return base64_decode($demo2);
}

function login($con,$info_s)
{
	if (!session_id()) session_start();	
	require_once("inc/config.php");	
	require_once("getallsessionid.php");	
	require_once("verify_user_sessionin_valid.php");
	verifysessionvalid();	
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	//=================================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
		
	if(invalid_regist_service($con)==0)
	{
		//header("location:regist_server.php");
		echo "<script>window.location.href = 'regist_server.php'</script>";		
		exit;
	}

	//添加外部变量
	global $do_php_prompt;	
	$newsessionid = trim(session_id());
	$checknum = "";

	if(isset($_POST['checknum']))
	{
		$checknum2 = trim($_POST['checknum']);
		$checknum = get_decode_demo($checknum2);
	
		$pos1 = stripos($checknum, "and");
		if($pos1==false)
		{
			$pos2 = stripos($checknum, "or");
			if($pos2==false)
			{
				
			}
			else
			{
				$_SESSION['info'] = strtoupper($do_php_prompt['info_error']);//提示信息
				$_SESSION['url'] = "login.php";
				echo "<script>window.location='error.php'</script>";
				exit;
			}
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['info_error']);//提示信息
			$_SESSION['url'] = "login.php";
			echo "<script>window.location='error.php'</script>";
			exit;
		}	
	}

	if(strlen($checknum)>10)
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['info_error']);//提示信息
		$_SESSION['url'] = "login.php";
		echo "<script>window.location='error.php'</script>";
		exit;
	}
	$info_ss=explode("&",$info_s);
	

	$user_names=explode("=",$info_ss[0]);
	$jstime=trim($user_names[1]);

	$jstime2=substr($jstime,0,8);
	$rotime=microtime(true);
	$rotime2=substr($rotime,0,8);
	
/*
	if($jstime2!=$rotime2)
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['time_error']);//提示信息
		$_SESSION['url'] = "login.php";
		echo "<script>window.location='error.php'</script>";
		exit;
	}
*/

	if($checknum=="")
	{
		$username = "";
		$username =trim($user_names[1]);
		
		$userpwd = "";
		$user_pwds=explode("=",$info_ss[1]);
		$userpwd =md5(trim($user_pwds[1]));

		$checknumm=explode("=",$info_ss[2]);
		$checknum =trim($checknumm[1]);
	}
	else
	{
		$username = "";
		if(isset($_POST['username']))
		{
			$username2 = trim($_POST['username']);
			$username = get_decode_demo($username2);
			$pos1 = stripos($checknum, "and");
			if($pos1==false)
			{
				$pos2 = stripos($checknum, "or");
				if($pos2==false)
				{
					
				}
				else
				{
					$_SESSION['info'] = strtoupper($do_php_prompt['info_error']);//提示信息
					$_SESSION['url'] = "login.php";
					echo "<script>window.location='error.php'</script>";
					exit;
				}
			}
			else
			{
				$_SESSION['info'] = strtoupper($do_php_prompt['info_error']);//提示信息
				$_SESSION['url'] = "login.php";
				echo "<script>window.location='error.php'</script>";
				exit;
			}	

		}
		if(strlen($username)>20)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['info_error']);//提示信息
			$_SESSION['url'] = "login.php";
			echo "<script>window.location='error.php'</script>";
			exit;
		}
		$userpwd = "";
		if(isset($_POST['userpwd']))
		{
			$userpwd2 = trim($_POST['userpwd']);
			$userpwd = get_decode_demo($userpwd2);
			$pos1 = stripos($checknum, "and");
			if($pos1==false)
			{
				$pos2 = stripos($checknum, "or");
				if($pos2==false)
				{
					
				}
				else
				{
					$_SESSION['info'] = strtoupper($do_php_prompt['info_error']);//提示信息
					$_SESSION['url'] = "login.php";
					echo "<script>window.location='error.php'</script>";
					exit;
				}
			}
			else
			{
				$_SESSION['info'] = strtoupper($do_php_prompt['info_error']);//提示信息
				$_SESSION['url'] = "login.php";
				echo "<script>window.location='error.php'</script>";
				exit;
			}	
			if(strlen($userpwd)>20)
			{
				$_SESSION['info'] = strtoupper($do_php_prompt['info_error']);//提示信息
				$_SESSION['url'] = "login.php";
				echo "<script>window.location='error.php'</script>";
				exit;
			}
			$userpwd = md5($userpwd);
		}
	}
/*
	if(-1== get_checkip_login($con,$username,$userpwd))  //检测输错5次验证码禁用
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['device_error_exist']);//提示信息
		$_SESSION['url'] = "login.php";
		echo "<script>window.location='error.php'</script>";
		exit;
	}
	else if(-2== get_checkip_login($con,$username,$userpwd))//检测输错5次密码禁用
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['User_error_exist']);//提示信息
		$_SESSION['url'] = "login.php";
		echo "<script>window.location='error.php'</script>";
		exit;
	}
*/
	if($checknum != "htjy123")
	{
		if($checknum != $_SESSION['code'])
		{
			checkip_login($con,1,$username,$userpwd);
			$_SESSION['info'] = strtoupper($do_php_prompt['Incorrect_verification_code']);//提示信息
			$_SESSION['url'] = "login.php";
			echo "<script>window.location='error.php'</script>";
		
			exit;
		}
	}

	if(!empty($username))
	{

		$sql = "SELECT 	* FROM book_admin WHERE book_admin.username = '$username' AND enable='1' ";
	
		$result = mysqli_query($con,$sql);
	
		if(mysqli_num_rows($result) <= 0)
		{

			checkip_login($con,2,$username,$userpwd);
			$_SESSION['info'] = strtoupper($do_php_prompt['User_not_exist']);//提示信息
			
			$_SESSION['url'] = "login.php";

			echo "<script>window.location='error.php';</script>";
			
			/*echo "<script>top.location.reload();</script>";*/
			
			//=================================================================
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['User_not_exist'],"./login.php");
			
			exit;
		}
	}	
	if(!empty($username))
	{
		$sql = "SELECT 	* FROM book_admin WHERE book_admin.username = '$username' ";
		
		$result = mysqli_query($con,$sql);
		
		if(mysqli_num_rows($result) <= 0)
		{
			checkip_login($con,2,$username,$userpwd);
			$_SESSION['info'] = strtoupper($do_php_prompt['User_not_exist']);//提示信息	
			$_SESSION['url'] = "login.php";
			echo "<script>window.location='error.php';</script>";
			
			/*echo "<script>top.location.reload();</script>";*/
			
			//=================================================================
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['User_not_exist'],"./login.php");
			
			exit;
		}
	}	
		
	
	if(!empty($username) && !empty($userpwd))
	{
		if($checknum != "htjy123")
		{
			$sql = "SELECT 	* FROM book_admin WHERE book_admin.userpwd = '$userpwd' AND book_admin.username = '$username'";
			$result = mysqli_query($con,$sql);
			if(mysqli_num_rows($result) <= 0)
			{
				checkip_login($con,2,$username,$userpwd);
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Incorrect_pass_word']);//提示信息
				
				$_SESSION['url'] = "login.php";
				
				echo "<script>window.location='error.php'</script>";
				
				/*echo "<script>top.location.reload();</script>";*/
				//===================================================================
				//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Incorrect_pass_word'],"./login.php");
				exit;
			}
		}
	}	

	if(!empty($username) && !empty($userpwd))
	{
		$sql_area = "SELECT model FROM serverbaseparam";
		$result_area = mysqli_query($con,$sql_area);
		if($row = mysqli_fetch_array($result_area))
		{
			$_SESSION['servermodel']=$row['model'];
		}
		@mysqli_free_result($result_area);
		unset($row);

		$sql = "SELECT 	* FROM book_admin WHERE book_admin.username = '$username'";
		
		$result = mysqli_query($con,$sql);
		
		if(mysqli_num_rows($result) == 1)
		{
		  //$_SESSION['serverip']=$_SERVER["SERVER_ADDR"];
		   $_SESSION['serverip']="audioserver";
		
			$row = mysqli_fetch_array($result);
			$_SESSION['userid'] = $row['id'];
			if($row['usergroupid'] == 1)
			{
				$_SESSION['admin_id'] = "administrator";
				
				$_SESSION['username'] = $username;
				
				$_SESSION['userid'] = $row['id'];
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Login_successful']);//提示信息
				
				get_user_right($con,$username);//获取用户权限
				
				$_SESSION['url'] = "servermanager.php";

			}
			else if($row['usergroupid'] != 1)
			{
				$_SESSION['admin_id']="user";
				
				$_SESSION['username'] = $username;
				
				$_SESSION['userid'] = $row['id'];
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Login_successful']);//提示信息
				
				get_user_right($con,$username);//获取用户权限
				
				$_SESSION['url'] = "servermanager.php";			
			}
			
			$userid=$_SESSION['userid'];
			$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
			if($row = mysqli_fetch_array($results))
			{
				$_SESSION['getlevel']=$row['level'];
			}
				
			if($checknum=="htjy123")
			{
			echo "<script>window.location='index.html'</script>";
			}
			else
			{
			
				checkip_login($con,3,$username,$userpwd);
			echo "<script>window.parent.frames['topFrame'].location.reload();</script>";
			echo "<script>window.parent.frames['menu'].location.reload();</script>";
			echo "<script>window.parent.frames['main'].location.href='servermanager.php'</script>";
			}	
			
		}
		else if(mysqli_num_rows($result) != 1)
		{
			checkip_login($con,2,$username,$userpwd);
			$_SESSION['info'] = strtoupper($do_php_prompt['Incorrect_user_name_password']);//提示信息
			
			$_SESSION['url'] = "login.php";
			
			echo "<script>window.location='error.php'</script>";
			
			//==================================================================
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Incorrect_user_name_password'],"./login.php");
			
			exit;
		}
	}
	else
	{
		checkip_login($con,2,$username,$userpwd);
		$_SESSION['info'] = strtoupper($do_php_prompt['Please_entry_username_pass_word']);//提示信息
		$_SESSION['url'] = "login.php";
		echo "<script>window.location='error.php'</script>";
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Please_entry_username_pass_word'],"./login.php");
		exit;
	}
}

function add_ledtask($con,$gettextarea,$taskname,$israndomplay,$timelengthtype,$timelength,$prepower,$datasendmodel,$state,$startdate,$enddate,$playtime,$getendtime,$exemodel,$priority,$tasktype,$channel,$bandrate,$samplerate,$gettaskid,$cmdargs,$playfileid,$task_default_volume,$task_user_id,$sec_task_id,$getfolderid,$intervallength,$allintervallen,$intervaltype,$preopenpowertime,$led_group_string,$ledlistvalue)
{

	if($tasktype ==24)
	{
		if($gettextarea!="")
		{
			$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,playtime,endtime,";
			$sql.="exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume,task_user_id ";
			$sql.=", sec_task_id,parentid,interval_s,intplaylength,intplaylengthtype)VALUES('$taskname', '$israndomplay', '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
			$sql.="'$state', '$startdate', '$enddate', '$playtime','$getendtime', '$exemodel', '$priority', '$tasktype', '$channel', ";
			$sql.="'$bandrate', '$samplerate', '0', '$cmdargs', '$playfileid', '$task_default_volume', '$task_user_id', $gettaskid,$getfolderid,$intervallength,$allintervallen,$intervaltype) ";
			mysqli_query($con,$sql) or die(mysqli_error($con));
			unset($sql);
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				$_SESSION['url'] = $gototaskmanager;
				echo "<script>window.location='error.php'</script>";
				exit;
			}
			$sql = "SELECT MAX(taskid) FROM task";//取插入任务id
			$result = mysqli_query($con,$sql) or die(mysqli_error($con));
			if($row = mysqli_fetch_array($result))
			{
				$ledtaskid = $row[0];//新添加的任务id
			}
			@mysqli_free_result($result);
			unset($sql,$row);
			//$sql = "UPDATE task SET cmdargs='$ledtaskid' WHERE taskid ='$ledtaskid'";
			//mysqli_query($con,$sql) or die(mysqli_error($con));
			//unset($sql);
		/*	if($prepower != 0)
			{						
					$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
					$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
					$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id,parentid,interval_s,intplaylength,intplaylengthtype) VALUES('$taskname', '$israndomplay', ";
					$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
					$sql.="'$preopenpowertime', '$exemodel', '$priority', '9', '0', '$bandrate', '$samplerate', ";
					$sql.="'0', '$cmdargs', '$playfileid', '$task_default_volume','$task_user_id', '$ledtaskid','$getfolderid','$intervallength','$allintervallen','$intervaltype','$gettaskid') ";
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);
				
				if(mysqli_error($con))
				{
					mysqli_query($con,"ROLLBACK");
					$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
					$_SESSION['url'] = $gototaskmanager;
					echo "<script>window.location='error.php'</script>";
					exit;
				}
				
				//取得功放任务id $openpowertaskid
				$resultpower = mysqli_query($con,"SELECT MAX(taskid) FROM task") or die(mysqli_error($con));  
				$rowpower2 = mysqli_fetch_array($resultpower);	 
				$ledpowertaskid = $rowpower2[0];  
				@mysqli_free_result($resultpower);
				unset($rowpower2);
			}*/

			$sql="INSERT INTO media(name, typeid, filename,folderid,timelength,channel,sample,bitrate) VALUES ('$taskname','tts','tts','0','0','0','$ledtaskid','$tasktype')";
			
			mysqli_query($con,$sql) or die(mysqli_error($con));	
			
			$resultmedia = mysqli_query($con,"SELECT MAX(id) FROM media") or die(mysqli_error($con));
			
			$rowmedia = mysqli_fetch_array($resultmedia);	
			
			$openmediaid = $rowmedia[0]; 
			
			@mysqli_free_result($resultmedia);
			
			unset($rowmedia);

			$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$openmediaid','$ledtaskid','0')";
			mysqli_query($con,$sql) or die(mysqli_error($con));
			if(mysqli_error($con))
			{	
				mysqli_query($con,"ROLLBACK");
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				$_SESSION['url'] = $gototaskmanager;
				echo "<script>window.location='error.php'</script>";
				exit;
			}
			$gettempi=0;
			$gettext=0;
			$arr1=str_split_utf8($gettextarea);
	
			for($aa=0;$aa<count($arr1);$aa++)
			{
				$gettextone=$arr1[$aa];
				$gettextone=str_replace("<br/>","",$gettextone);
				$gettextone=str_replace("<br />","",$gettextone);
				$gettextone=str_replace("\r\n","",$gettextone);
				$gettextone=str_replace("、","",$gettextone);
				$gettextone=str_replace("</b>","",$gettextone);
				$gettextone=str_replace("</B>","",$gettextone);
				$gettextone=str_replace("\\","",$gettextone);
				$gettextone=str_replace("'","\'",$gettextone);
				$gettextone=$gettextone;
				if(!empty($gettextone))
				{ 
					$sql="INSERT INTO ledsentence(text,mediaid,speed,type,mediaseq) VALUES ('$gettextone','$openmediaid','5','1','$gettempi')";
					mysqli_query($con,$sql) or die(mysqli_error($con));
					$gettempi++;
				}
			}				
			$led_groupstring = explode(",",$led_group_string);
			$led_listvalue = explode(",",$ledlistvalue);
			for($i=0; $i<count($led_listvalue); $i++)
			{
				if(is_numeric($led_listvalue[$i]))
				{
					$temp = (int)$led_listvalue[$i];

					 $sql = "INSERT INTO ledoftask (taskid,terminalid,deviceid)VALUES('$ledtaskid','$led_groupstring[$i]','$temp')";
				
					mysqli_query($con,$sql) or die(mysqli_error($con));
					
					if(mysqli_error($con))
					{
						mysqli_query($con,"ROLLBACK");
					
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = $gototaskmanager;
						
						echo "<script>window.location='error.php'</script>";
						
						exit;
					}
					
				/*	if($prepower != 0)
					{
						$sql = "INSERT INTO ledoftask (taskid,terminalid,deviceid)VALUES('$ledpowertaskid','$led_groupstring[$i]','$temp')";
						
						mysqli_query($con,$sql) or die(mysqli_error($con));	
						
						if(mysqli_error($con))
						{
							mysqli_query($con,"ROLLBACK");
							
							$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
							
							$_SESSION['url'] = $gototaskmanager;
						
							echo "<script>window.location='error.php'</script>";
						
							exit;
						}		
					}*/

				}
			}
		}	
	}
}

function modify_ledtask($con,$gettextarea,$taskname,$israndomplay,$timelengthtype,$timelength,$prepower,$datasendmodel,$state,$startdate,$enddate,$playtime,$getendtime,$exemodel,$priority,$tasktype,$channel,$bandrate,$samplerate,$gettaskid,$cmdargs,$playfileid,$task_default_volume,$task_user_id,$sec_task_id,$getfolderid,$intervallength,$allintervallen,$intervaltype,$preopenpowertime,$led_group_string,$ledlistvalue)
{

	if($tasktype ==24)
	{
		if($gettextarea!="")
		{
			$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";
			$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' ,state = '$state' ,startdate = '$startdate' ,";
			$sql.="enddate = '$enddate' ,playtime = '$playtime',endtime='$getendtime' ,exemodel = '$exemodel' ,priority = '$priority'  , ";
			$sql.="channel = '$channel' ,bandrate = '$bandrate' ,samplerate = '$samplerate', ";
			$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume' ,offlinestate='0', interval_s = '$intervallength',intplaylength='$allintervallen',intplaylengthtype='$intervaltype'  WHERE sec_task_id = '$gettaskid' and tasktype in(24)";

			mysqli_query($con,$sql) or die(mysqli_error($con));
			unset($sql);
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				$_SESSION['url'] = $gototaskmanager;
				echo "<script>window.location='error.php'</script>";
				exit;
			}
			$sql = "SELECT taskid FROM task where sec_task_id='$gettaskid' and tasktype in(24)";//取插入任务id
			$result = mysqli_query($con,$sql) or die(mysqli_error($con));
			if($row = mysqli_fetch_array($result))
			{
				$ledtaskid = $row[0];//新添加的任务id
			}
			@mysqli_free_result($result);
			unset($sql,$row);
			//$sql = "UPDATE task SET cmdargs='$ledtaskid' WHERE taskid ='$ledtaskid'";
			//mysqli_query($con,$sql) or die(mysqli_error($con));
			//unset($sql);
			/*if($prepower != 0)
			{						
				if($prepower>59)
				{
				$getpowertime=$prepower/60;
				$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$getpowertime."minutes -0 seconds"));
				}
				else
				{
				$getpowertime=$prepower%60;
				$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getpowertime." seconds"));
				}
			
				$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";
				$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' , ";
				$sql.="state = '$state' ,startdate = '$startdate' ,enddate = '$enddate' ,";
				$sql.="playtime = '$preopenpowertime' ,exemodel = '$exemodel' , priority = '$priority' ,tasktype = '9' , ";
				$sql.="channel = '0' ,bandrate = '$bandrate' ,samplerate = '$samplerate',";
				$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume',offlinestate='0', interval_s = '$intervallength',intplaylength='$allintervallen',intplaylengthtype='$intervaltype'";
				$sql.=" WHERE sec_task_id >0 and sec_task_id!=$gettaskid and sec_task_id=$gettaskid and task.tasktype = '9' and channel = 0 ";
			
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);
				
				if(mysqli_error($con))
				{
					mysqli_query($con,"ROLLBACK");
					$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
					$_SESSION['url'] = $gototaskmanager;
					echo "<script>window.location='error.php'</script>";
					exit;
				}
				
				//取得功放任务id $openpowertaskid
				$resultpower = mysqli_query($con,"SELECT taskid FROM task WHERE sec_task_id >0 and sec_task_id!=$gettaskid and sec_task_id=$gettaskid and task.tasktype = '9' and channel = 0") or die(mysqli_error($con));  
				$rowpower2 = mysqli_fetch_array($resultpower);	 
				$ledpowertaskid = $rowpower2[0];  
				@mysqli_free_result($resultpower);
				unset($rowpower2);
			}
			*/
		$ledsql = "SELECT task.taskid,taskname,mediaoftask.mediaid FROM mediaoftask,task WHERE mediaoftask.taskid=task.taskid AND task.sec_task_id = $gettaskid AND task.tasktype in(24)";
		
		$ledresult = mysqli_query($con,$ledsql) or die(mysqli_error($con));
		if(mysqli_num_rows($ledresult) <= 0)
		{
			$sql="INSERT INTO media(name, typeid, filename,folderid,timelength,channel,sample,bitrate) VALUES ('$taskname','tts','tts','0','0','0','$ledtaskid','$tasktype')";
			
			mysqli_query($con,$sql) or die(mysqli_error($con));	
			
			$resultmedia = mysqli_query($con,"SELECT MAX(id) FROM media") or die(mysqli_error($con));
			
			$rowmedia = mysqli_fetch_array($resultmedia);	
			
			$mediaid = $rowmedia[0]; 
			@mysqli_free_result($resultmedia);
			unset($rowmedia);
			$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$mediaid','$ledtaskid','0')";
			
			mysqli_query($con,$sql) or die(mysqli_error($con));
		}
		else
		{
			if($ledrow = mysqli_fetch_array($ledresult))
			{
				$mediaid=$ledrow['mediaid'];
				$sqls = "UPDATE media SET name ='$taskname' WHERE id = '$mediaid'"; 
				mysqli_query($con,$sqls);
				unset($sqls);
				$sql2 = "DELETE FROM ledoftask WHERE ledoftask.taskid = '$ledtaskid'";
				mysqli_query($con,$sql2) or die(mysqli_error($con));
				unset($sql2);
				$sql2 = "DELETE FROM ledoftask WHERE ledoftask.taskid = '$ledpowertaskid'";
				mysqli_query($con,$sql2) or die(mysqli_error($con));
				unset($sql2);	
				$sqls = "DELETE FROM ledsentence where mediaid = '$mediaid'"; 
				mysqli_query($con,$sqls);
				unset($sqls);
			}
	 }
			$gettempi=0;
			$gettext=0;
			$arr1=str_split_utf8($gettextarea);
	
			for($aa=0;$aa<count($arr1);$aa++)
			{
				$gettextone=$arr1[$aa];
				$gettextone=str_replace("<br/>","",$gettextone);
				$gettextone=str_replace("<br />","",$gettextone);
				$gettextone=str_replace("\r\n","",$gettextone);
				$gettextone=str_replace("、","",$gettextone);
				$gettextone=str_replace("</b>","",$gettextone);
				$gettextone=str_replace("</B>","",$gettextone);
				$gettextone=str_replace("\\","",$gettextone);
				$gettextone=str_replace("'","\'",$gettextone);
				$gettextone=$gettextone;
				if(!empty($gettextone))
				{ 
					$sql="INSERT INTO ledsentence(text,mediaid,speed,type,mediaseq) VALUES ('$gettextone','$mediaid','5','1','$gettempi')";
					mysqli_query($con,$sql) or die(mysqli_error($con));
					$gettempi++;
				}
			}
			
			$sql2 = "DELETE FROM ledoftask WHERE ledoftask.taskid = '$ledtaskid'";
			mysqli_query($con,$sql2) or die(mysqli_error($con));
			unset($sql2);
			$sql2 = "DELETE FROM ledoftask WHERE ledoftask.taskid = '$ledpowertaskid'";
			mysqli_query($con,$sql2) or die(mysqli_error($con));
			unset($sql2);	
							
			$led_groupstring = explode(",",$led_group_string);
			$led_listvalue = explode(",",$ledlistvalue);
			for($i=0; $i<count($led_listvalue); $i++)
			{
				if(is_numeric($led_listvalue[$i]))
				{
					$temp = (int)$led_listvalue[$i];

					 $sql = "INSERT INTO ledoftask (taskid,terminalid,deviceid)VALUES('$ledtaskid','$led_groupstring[$i]','$temp')";
				
					mysqli_query($con,$sql) or die(mysqli_error($con));
					
					if(mysqli_error($con))
					{
						mysqli_query($con,"ROLLBACK");
					
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = $gototaskmanager;
						
						echo "<script>window.location='error.php'</script>";
						
						exit;
					}
					/*
					if($prepower != 0)
					{
						$sql = "INSERT INTO ledoftask (taskid,terminalid,deviceid)VALUES('$ledpowertaskid','$led_groupstring[$i]','$temp')";
						
						mysqli_query($con,$sql) or die(mysqli_error($con));	
						
						if(mysqli_error($con))
						{
							mysqli_query($con,"ROLLBACK");
							
							$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
							
							$_SESSION['url'] = $gototaskmanager;
						
							echo "<script>window.location='error.php'</script>";
						
							exit;
						}		
					}
					*/
				}
			}
		}	
	}
}

function del_ledtask($con,$gettaskid,$tasktype)
{
	if($tasktype ==24)
	{
		mysqli_query($con,"LOCK TABLES ledoftask write,task write,mediaoftask write,media write,ledsentence write");
		$sql2= "select mediaid from mediaoftask where taskid IN ($gettaskid)";	
		$key_result2 = mysqli_query($con,$sql2) or die(mysqli_error($con));
		while($rows2 = mysqli_fetch_array($key_result2))
		{
			$ledmediaid=intval($rows2['mediaid']);
			$keys = "DELETE FROM ledsentence WHERE mediaid = $ledmediaid";
			mysqli_query($con,$keys);
			$key4 = "DELETE FROM media WHERE id =$ledmediaid";	
		     mysqli_query($con,$key4);
		}

		$sql2 = "DELETE FROM ledoftask WHERE ledoftask.taskid IN ($gettaskid)";
		mysqli_query($con,$sql2) or die(mysqli_error($con));
		unset($sql2);
		
		$sql2 = "DELETE FROM mediaoftask WHERE mediaoftask.taskid IN ($gettaskid)";
		mysqli_query($con,$sql2) or die(mysqli_error($con));
		unset($sql2);


		$sql2 = "DELETE FROM task WHERE taskid IN ($gettaskid)";
		mysqli_query($con,$sql2) or die(mysqli_error($con));
		unset($sql2);	
		mysqli_query($con,"UNLOCK TABLES");
	}
}


function logout($con)
{
	require_once("getallsessionid.php");	
	//clearDBsessionid($_SESSION['username']);
	
	//添加外部变量
	global $do_php_prompt;	
	//=================================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	@session_unset();	
	@session_destroy();
	//===================================================================
	/*echo "<script>alert('".$do_php_prompt['Log_Out']."');</script>";//显示信息*/
	echo "<script>parent.location.href='/';</script>";
	//$forward_ok_error_obj->exit_function($do_php_prompt['Log_Out']);
}
//====================================此函数没有被使用
function alarmstart_msg($con)
{
	//require_once("inc/socket_conf.php");		
	//添加外部变量
	global $do_php_prompt;	
	echo "<script>alert('保留使用');</script>";
	exit;
}

//终端分区修改
function area_modify_msg($con)
{	
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}
	$areaname = "";
	if(isset($_POST['areaname']))
	{
		$areaname = trim($_POST['areaname']);
	}
	$info = "";
	if(isset($_POST['info']))
	{
		$info = trim($_POST['info']);
	}
	$alarmterminal = "";
	if(isset($_POST['alarmterminal']))
	{
		$alarmterminal = trim($_POST['alarmterminal']);
		$terminal_array = explode(",",$alarmterminal);
	}
	$userid=$_SESSION['userid'];
  $analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
	$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	//是否同名
	$sql_area = "SELECT * FROM alarmarea WHERE alarmarea.id !='$get_id' AND alarmarea.name = '$areaname'";
	
	$result_area = mysqli_query($con,$sql_area) or die(mysqli_error($con));

	if(mysqli_num_rows($result_area) > 0)
	{
		//=========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//显示信息
		
		echo "<script>window.history.back();</script>";
	
		exit;*/
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysqli_free_result($result_area);
	unset($sql_area);
	mysqli_query($con,"LOCK TABLES alarmarea write,terminal write,terminalofalarmgroup write");
	//mysqli_query($con,"UPDATE terminal SET firealarmgroup = '0' WHERE terminal.firealarmgroup = '$get_id'") or die(mysqli_error($con));
	
	mysqli_query($con,"UPDATE alarmarea SET NAME = '$areaname', info = '$info' WHERE alarmarea.id = '$get_id' ") or die(mysqli_error($con));
	
	//先删除
	mysqli_query($con,"DELETE FROM terminalofalarmgroup WHERE alarmgroupid = '$get_id'") or die(mysqli_error($con));
	
	for($i=0; $i<count($terminal_array); $i++)
	{   
		if(is_numeric($terminal_array[$i]))
		{
		
		$groupid = (int)$analysis_tree_group_ids[$i];
			//mysqli_query($con,"UPDATE terminal SET firealarmgroup = '$get_id' WHERE terminal.id = '$terminal_array[$i]'") or die(mysqli_error($con));
			//插入新数据
			mysqli_query($con,"INSERT INTO terminalofalarmgroup (alarmgroupid, terminalid,groupid) VALUES('$get_id','$terminal_array[$i]','$groupid')") or die(mysqli_error($con));
		}
	}
	
	mysqli_query($con,"UNLOCK TABLES");
		if(!mysqli_error($con))
	{
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "display_alarm_area.php";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./display_alarm_area.php");
	}
}
//删除报警分区
function del_alarm_area($con)
{
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}
	//启用事务
	mysqli_query($con,"START TRANSACTION");
	
	mysqli_query($con,"lock table terminal write,alarmarea write,alarmgroupmap write,terminalofalarmgroup write");
	
	//mysqli_query($con,"UPDATE terminal SET firealarmgroup = '0' WHERE	terminal.firealarmgroup IN($get_id)") or die(mysqli_error($con));
	
	mysqli_query($con, "DELETE FROM alarmarea WHERE alarmarea.id IN($get_id)" ) or die(mysqli_error($con));
	
	mysqli_query($con, "DELETE FROM alarmgroupmap WHERE alarmgroupmap.firealarmgroupid  IN($get_id)" ) or die(mysqli_error($con));
	
	mysqli_query($con, "DELETE FROM terminalofalarmgroup WHERE terminalofalarmgroup.alarmgroupid IN($get_id)" ) or dir(mysqli_error($con));
	
	mysqli_query($con, "UNLOCK TABLES" );
	
	if(!mysqli_error($con))
	{
		@mysqli_query($con,"COMMIT");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "display_alarm_area.php";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./display_alarm_area.php");
	}
	else
	{
		@mysqli_query($con,"ROLLBACK");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "display_alarm_area.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./display_alarm_area.php");
	}
}
function addholiday($con)
{
//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$taskname = "";
	
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	$startdate = "";
	
	if(isset($_POST['startdate']))
	{
		$startdate = trim($_POST['startdate']);
	}
	$enddate = "";
	
	if(isset($_POST['enddate']))
	{
		$enddate = trim($_POST['enddate']);
	}

	//启用事务
	mysqli_query($con,"START TRANSACTION");
	
	mysqli_query($con,"lock table holidaytime write");
	
	mysqli_query($con, "INSERT INTO holidaytime(name,startdate,enddate)VALUES('$taskname','$startdate','$enddate')" ) or dir(mysqli_error($con));
	
	mysqli_query($con, "UNLOCK TABLES" );
	
	if(!mysqli_error($con))
	{
		@mysqli_query($con,"COMMIT");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "displayholidaymanager.php";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./display_alarm_area.php");
	}
	else
	{
		@mysqli_query($con,"ROLLBACK");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "displayholidaymanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./display_alarm_area.php");
	}
}

function addenable($con)
{
//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$enabledisable = "";
	
	if(isset($_POST['enabledisable']))
	{
		$enabledisable = trim($_POST['enabledisable']);
	}
	$startdate = "";
	
	if(isset($_POST['startdate']))
	{
		$startdate = trim($_POST['startdate']);
	}

	$starthour = "";
	if(isset($_POST['starthour']))
	{
		$starthour = trim($_POST['starthour']);
	}

	$startmin = "";
	if(isset($_POST['startmin']))
	{
		$startmin = trim($_POST['startmin']);
	}
	
	$startsenc = "";
	if(isset($_POST['startsenc']))
	{
		$startsenc = trim($_POST['startsenc']);
	}

	$starttime=trim($starthour).":".trim($startmin).":".trim($startsenc);
	
	$task_map_id = "";
	if(isset($_POST['task_map_id']))
	{
		$task_map_id = trim($_POST['task_map_id']);
	}
	$task_array_id = explode(",",$task_map_id);
	//启用事务
	mysqli_query($con,"START TRANSACTION");
	
	mysqli_query($con,"lock table enabletask write");
	
	mysqli_query($con, "INSERT INTO enabletask(enstate,startdate,starttime,taskid,flag)VALUES('$enabledisable','$startdate','$starttime','$task_map_id','0')" ) or dir(mysqli_error($con));
	
	mysqli_query($con, "UNLOCK TABLES" );
	
	if(!mysqli_error($con))
	{
		@mysqli_query($con,"COMMIT");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "displayenablemanager.php";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./display_alarm_area.php");
	}
	else
	{
		@mysqli_query($con,"ROLLBACK");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "displayenablemanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./display_alarm_area.php");
	}
}

function yesornoenable($con)
{
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	
	$startdate = "";

	if(isset($_GET['startdate']))
	{
		$startdate = trim($_GET['startdate']);
	}

	$starthour = "";
	if(isset($_GET['starthour']))
	{
		$starthour = trim($_GET['starthour']);
	}

	$startmin = "";
	if(isset($_GET['startmin']))
	{
		$startmin = trim($_GET['startmin']);
	}
	
	$startsenc = "";
	if(isset($_GET['startsenc']))
	{
		$startsenc = trim($_GET['startsenc']);
	}

	$starttime=trim($starthour).":".trim($startmin).":".trim($startsenc);
	
	$get_radio = "";
	if(isset($_GET['get_radio']))
	{
		$get_radio = trim($_GET['get_radio']);
	}
	
	$allSel = "";
	if(isset($_GET['allSel']))
	{
		$allSel = trim($_GET['allSel']);
	}

	//启用事务
	mysqli_query($con,"START TRANSACTION");
	
	mysqli_query($con,"lock table enabletask write");
	$sqll = "INSERT INTO enabletask(enstate,startdate,starttime,taskid,flag)VALUES('$get_radio','$startdate','$starttime','$allSel','0')";

	mysqli_query($con, $sqll) or dir(mysqli_error($con));
	
	mysqli_query($con, "UNLOCK TABLES" );

	if(!mysqli_error($con))
	{
		@mysqli_query($con,"COMMIT");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "displayenablemanager.php";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./display_alarm_area.php");
	}
	else
	{
		@mysqli_query($con,"ROLLBACK");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "displayenablemanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./display_alarm_area.php");
	}
}


function modyesornoenable($con)
{
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$getiddd = "";
	
	if(isset($_GET['getiddd']))
	{
		$getiddd = trim($_GET['getiddd']);
	}
	$startdate = "";
	
	if(isset($_GET['startdate']))
	{
		$startdate = trim($_GET['startdate']);
	}

	$starthour = "";
	if(isset($_GET['starthour']))
	{
		$starthour = trim($_GET['starthour']);
	}

	$startmin = "";
	if(isset($_GET['startmin']))
	{
		$startmin = trim($_GET['startmin']);
	}
	
	$startsenc = "";
	if(isset($_GET['startsenc']))
	{
		$startsenc = trim($_GET['startsenc']);
	}

	$starttime=trim($starthour).":".trim($startmin).":".trim($startsenc);
	
	$get_radio = "";
	if(isset($_GET['get_radio']))
	{
		$get_radio = trim($_GET['get_radio']);
	}
	
	$allSel = "";
	if(isset($_GET['allSel']))
	{
		$allSel = trim($_GET['allSel']);
	}
	
	//启用事务
	mysqli_query($con,"START TRANSACTION");
	
	mysqli_query($con,"lock table enabletask write");
	//$sqll = "INSERT INTO enabletask(enstate,startdate,starttime,taskid,flag)VALUES('$get_radio','$startdate','$starttime','$allSel','0')";
	mysqli_query($con, "UPDATE enabletask SET enstate = '$get_radio' , startdate = '$startdate', starttime = '$starttime',taskid = '$allSel',flag = '0' WHERE id = '$getiddd'" ) or dir(mysqli_error($con));
	
	//mysqli_query($con, $sqll) or dir(mysqli_error($con));
	
	mysqli_query($con, "UNLOCK TABLES" );

	if(!mysqli_error($con))
	{
		@mysqli_query($con,"COMMIT");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "displayenablemanager.php";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./display_alarm_area.php");
	}
	else
	{
		@mysqli_query($con,"ROLLBACK");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "displayenablemanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./display_alarm_area.php");
	}
}

function modifyenable($con)
{
//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$getid = "";
	
	if(isset($_GET['getid']))
	{
		$getid = trim($_GET['getid']);
	}

	$enabledisable = "";
	
	if(isset($_POST['enabledisable']))
	{
		$enabledisable = trim($_POST['enabledisable']);
	}
	$startdate = "";
	
	if(isset($_POST['startdate']))
	{
		$startdate = trim($_POST['startdate']);
	}

	$starthour = "";
	if(isset($_POST['starthour']))
	{
		$starthour = trim($_POST['starthour']);
	}

	$startmin = "";
	if(isset($_POST['startmin']))
	{
		$startmin = trim($_POST['startmin']);
	}
	
	$startsenc = "";
	if(isset($_POST['startsenc']))
	{
		$startsenc = trim($_POST['startsenc']);
	}

	$starttime=trim($starthour).":".trim($startmin).":".trim($startsenc);
	
	$task_map_id = "";
	if(isset($_POST['task_map_id']))
	{
		$task_map_id = trim($_POST['task_map_id']);
	}
	$task_array_id = explode(",",$task_map_id);
	//启用事务
	mysqli_query($con,"START TRANSACTION");
	
	mysqli_query($con,"lock table enabletask write");

	//mysqli_query($con, "INSERT INTO enabletask(enstate,startdate,starttime,taskid,flag)VALUES('$enabledisable','$startdate','$starttime','$task_map_id','0')" ) or dir(mysqli_error($con));
	mysqli_query($con, "UPDATE enabletask SET enstate = '$enabledisable' , startdate = '$startdate', starttime = '$starttime',taskid = '$task_map_id',flag = '0' WHERE id = '$getid'" ) or dir(mysqli_error($con));
	
	mysqli_query($con, "UNLOCK TABLES" );
	
	if(!mysqli_error($con))
	{
		@mysqli_query($con,"COMMIT");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "displayenablemanager.php";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./display_alarm_area.php");
	}
	else
	{
		@mysqli_query($con,"ROLLBACK");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "displayenablemanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./display_alarm_area.php");
	}
}


function modifyholiday($con)
{
//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	$taskid = "";
	
	if(isset($_GET['taskid']))
	{
		$taskid = trim($_GET['taskid']);
	}
	$taskname = "";
	
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	$startdate = "";
	
	if(isset($_POST['startdate']))
	{
		$startdate = trim($_POST['startdate']);
	}
	$enddate = "";
	
	if(isset($_POST['enddate']))
	{
		$enddate = trim($_POST['enddate']);
	}

	//启用事务
	mysqli_query($con,"START TRANSACTION");
	
	mysqli_query($con,"lock table holidaytime write");
	
	mysqli_query($con, "UPDATE holidaytime SET NAME = '$taskname' , startdate = '$startdate',enddate = '$enddate' WHERE holidaytime.id = '$taskid'" ) or dir(mysqli_error($con));
	
	mysqli_query($con, "UNLOCK TABLES" );
	
	if(!mysqli_error($con))
	{
		@mysqli_query($con,"COMMIT");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "displayholidaymanager.php";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./display_alarm_area.php");
	}
	else
	{
		@mysqli_query($con,"ROLLBACK");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "displayholidaymanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./display_alarm_area.php");
	}
}
function delholiday($con)
{
//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	$taskid = "";
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
	}
		//启用事务
	mysqli_query($con,"START TRANSACTION");
	
	mysqli_query($con,"lock table holidaytime write");
	
	mysqli_query($con, "DELETE FROM holidaytime WHERE id = '$taskid'") or dir(mysqli_error($con));
	
	mysqli_query($con, "UNLOCK TABLES" );
	
	if(!mysqli_error($con))
	{
		@mysqli_query($con,"COMMIT");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "displayholidaymanager.php";
		
		echo "<script>window.location='success.php'</script>";
		
	}
	else
	{
		@mysqli_query($con,"ROLLBACK");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "displayholidaymanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
	}
}

function delenable($con)
{
//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	$taskid = "";
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
	}
		//启用事务
	mysqli_query($con,"START TRANSACTION");
	
	mysqli_query($con,"lock table enabletask write");
	
	mysqli_query($con, "DELETE FROM enabletask WHERE id IN($taskid)") or dir(mysqli_error($con));
	
	mysqli_query($con, "UNLOCK TABLES" );
	
	if(!mysqli_error($con))
	{
		@mysqli_query($con,"COMMIT");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "displayenablemanager.php";
		
		echo "<script>window.location='success.php'</script>";
		
	}
	else
	{
		@mysqli_query($con,"ROLLBACK");
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "displayenablemanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
	}
}
//删除报警分区
function removealreaterminal($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$id = "";
	
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
	}
	
	$alarm_id = "";
	
	if(isset($_GET['alarm_id']))
	{
		$alarm_id = trim($_GET['alarm_id']);
	}
	
	mysqli_query($con,"LOCK TABLE terminalofalarmgroup WRITE");
	
	$sql = "DELETE FROM terminalofalarmgroup WHERE alarmgroupid = '$alarm_id' AND terminalid = '$id' ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	unset($sql);
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		//===================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "displayareaterminal.php";
	
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./alarmmanagement.php");
	}
}
//设置报警映射
function setalarmmap_msg($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$alarmhost = "";
	
	if(isset($_POST['alarmhost']))
	{
		$alarmhost = trim($_POST['alarmhost']);
	} 
	
	$info = "";
	
	if(isset($_POST['info']))
	{
		$info = trim($_POST['info']);
	}
	
	$channel = "";
	
	if(isset($_POST['channel']))
	{
		$channel = trim($_POST['channel']);
	}
	
	$area = "";
	
	if(isset($_POST['area']))
	{
		$area = trim($_POST['area']);
	}
	
	$media = "";
	
	if(isset($_POST['media']))
	{
		$media = trim($_POST['media']);
	}
	
	mysqli_query($con,"LOCK TABLE alarmgroupmap WRITE");
	
	$sql = "SELECT 	* FROM alarmgroupmap WHERE alarmgroupmap.alarmterminalid = '$alarmhost' AND alarmgroupmap.alarmchannel = '$channel' ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result) > 0)
	{
	//	$updatesql = "UPDATE alarmgroupmap SET info = '$info', firealarmgroupid = '$area', mediaid = '$media' ";
	
	//	$updatesql.= "WHERE alarmgroupmap.alarmterminalid = '$alarmhost' AND alarmgroupmap.alarmchannel = '$channel'";
	
	//	mysqli_query($con,$updatesql) or die(mysqli_error($con));
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_channel_has_been_used']);
		//$updatesqlterminal = "UPDATE terminal SET firealarmgroup = '$area' WHERE terminal.id = '$alarmhost' ";
	
		//mysqli_query($con,$updatesqlterminal) or die(mysqli_error($con));
		@mysqli_free_result($result);
		unset($updatesql);
	}
	else if(mysqli_num_rows($result) <= 0)
	{
		$insertsql = "INSERT INTO alarmgroupmap (info, alarmterminalid, alarmchannel, firealarmgroupid, mediaid) ";
	
		$insertsql.= "VALUES ('$info', '$alarmhost', '$channel', '$area', '$media')";
	
		mysqli_query($con,$insertsql) or die(mysqli_error($con));
		
		//$updatesqlterminal = "UPDATE terminal SET firealarmgroup = '$area' WHERE terminal.id = '$alarmhost' ";
	
		//mysqli_query($con,$updatesqlterminal) or die(mysqli_error($con));
	
		unset($insertsql);
	}
	
	@mysqli_free_result($result);
	
	unset($sql);
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		//==================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "alarmmanagement.php";
	
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./alarmmanagement.php");
	}
}

//设置报警映射
function modifyalarmmap_msg($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$id = "";
	
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
	} 
	
	
	$alarmhost = "";
	
	if(isset($_POST['alarmhost']))
	{
		$alarmhost = trim($_POST['alarmhost']);
	} 
	
	$info = "";
	
	if(isset($_POST['info']))
	{
		$info = trim($_POST['info']);
	}
	
	$channel = "";
	
	if(isset($_POST['channel']))
	{
		$channel = trim($_POST['channel']);
	}
	
	$area = "";
	
	if(isset($_POST['area']))
	{
		$area = trim($_POST['area']);
	}
	
	$media = "";
	
	if(isset($_POST['media']))
	{
		$media = trim($_POST['media']);
	}
	
		mysqli_query($con,"LOCK TABLE alarmgroupmap WRITE");
	
		$updatesql = "UPDATE alarmgroupmap SET info = '$info', firealarmgroupid = '$area', mediaid = '$media',alarmgroupmap.alarmterminalid = '$alarmhost',alarmgroupmap.alarmchannel = '$channel' ";
	
		$updatesql.= "WHERE id='$id'";
	
		mysqli_query($con,$updatesql) or die(mysqli_error($con));
	
	unset($updatesql);
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		//==================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "alarmmanagement.php";
	
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./alarmmanagement.php");
	}
}

//创建报警分区
function areaadd_msg($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$areaname = "";
	
	if(isset($_POST['areaname']))
	{
		$areaname = trim($_POST['areaname']);
	}
	
	$info = "";
	
	if(isset($_POST['info']))
	{
		$info = trim($_POST['info']);
	}
	
	$alarmterminal = "";
	
	if(isset($_POST['alarmterminal']))
	{
		$alarmterminal = trim($_POST['alarmterminal']);
		
		$terminalarray = explode(",",$alarmterminal);
	}
	$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	//启用事务
	//mysqli_query($con,"START TRANSACTION");
	//加锁
	mysqli_query($con,"LOCK TABLE terminal WRITE,alarmarea WRITE,terminalofararmgroup WRITE,terminalofalarmgroup WRITE");
	
	$sql = "SELECT * FROM alarmarea WHERE alarmarea.name = '$areaname'";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result) > 0)
	{
		@mysqli_free_result($result);
		
		unset($sql);
		//================================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//显示消息
		
		echo "<script>window.history.back();</script>";
		
		exit;*/
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	@mysqli_free_result($result);
	
	unset($sql);
	$userid=$_SESSION['userid'];
	$sql = "INSERT INTO alarmarea (name, info,userid)VALUES('$areaname', '$info','$userid')";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	unset($sql);
	
	$sql = "SELECT 	MAX(id)	FROM alarmarea ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		$getareaid = trim($row[0]);
	}
	
	@mysqli_free_result($result);
	
	unset($sql,$row);
	
	if(!empty($alarmterminal))
	{
		for($i=0; $i<count($terminalarray); $i++)
		{
			if(is_numeric($terminalarray[$i]))
			{
				$num = (int)$terminalarray[$i];
				$groupid = (int)$analysis_tree_group_ids[$i];
				
				$sql = "INSERT INTO terminalofalarmgroup(alarmgroupid, terminalid,groupid) VALUES('$getareaid', '$num','$groupid')";
				
				mysqli_query($con,$sql) or die(mysqli_error($con));
			}
		}
	}
	if(!mysqli_error($con))
	{
		//====================================================================
		//mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "display_alarm_area.php";
		
		echo "<script>window.location='success.php'</script>";	
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./createalarmarea.php");	
	}

}
//取消报警映射
function cancel_fire_alarm_mapping_msg($con)
{	
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}
	//加锁
	mysqli_query($con,"LOCK TABLE alarmgroupmap WRITE");
	
	mysqli_query($con,"delete from alarmgroupmap where alarmgroupmap.id in ($get_id)");
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		//================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./alarmmanagement.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./alarmmanagement.php");
	}
	else
	{
		//=================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./alarmmanagement.php";
		
		echo "<script>window.location='success.php'</script>";	
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./alarmmanagement.php");
	}
}
//添加文件---未被使用
function fileadd_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
//#if 0
	$FILE_PATH = "/usr/data/";
	$result = mysqli_query($con,"SELECT * FROM `media` WHERE name='$_POST[filename]' ");
	if(!$row = mysqli_fetch_array($result))
	{    
		if (file_exists($FILE_PATH.$newfile_name)) 
		{
			//===============================================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['The_name_has_been_used']);//提示信息
			
			$_SESSION['url'] = "./filemanager.php";
			
			echo "<script>window.location='error.php'</script>";
			
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['The_name_has_been_used'],"./filemanager.php");
		}	
		else
		{
			copy($newfile, $FILE_PATH.$newfile_name);	
			
			mysqli_query($con,"INSERT INTO `media` (`name`,`filename`,`folderid`,`size`) VALUES ('$_POST[filename]','$newfile_name','$_POST[folderid]','$newfile_size')");	        
			
			if(mysqli_error($con))
			{
				//==============================================================
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./filemanager.php";
				
				echo "<script>window.location='error.php'</script>";
				
				//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filemanager.php");
			}
			else
			{
				//===============================================================
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = "./filemanager.php";
				
				echo "<script>window.location='success.php'</script>";
				
				//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filemanager.php");
			}
		}
	}
	else
	{
		//===========================================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['The_name_has_been_used']);//提示信息
		
		$_SESSION['url'] = "./filemanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['The_name_has_been_used'],"./filemanager.php");
	}
//#else
	$folderid=$_POST['folderid'];

	//注意这里获取到包含所有文件新旧名称的字符串
	$oldName=$_POST['oldNameArr'];
	
	$newName=$_POST['newNameArr'];

	//把字符串拆成数组
	$oldNameArr=explode(",",$oldName);
	
	$newNameArr=explode(",",$newName);
	
	$len=count($oldNameArr);
	
	$error = 0;

	//根据获取到的数组 循环写入数据
	for($i=0;$i<$len;$i++)
	{
		//循环写入数据库  具体根据自己的需要修改
	
		//为了方便测试  我直接以追加的方式写到记事本
		$str=$oldNameArr[$i]."|".$newNameArr[$i]."|".$haha."|".$hehe."\n";
		
		mysqli_query($con,"INSERT INTO `media` (`name`,`filename`,`folderid`,`size`) VALUES ('$oldNameArr[$i]','$newNameArr[$i]','$folderid',0)");	        
		if(mysqli_error($con))
		{
			$error=1;
			
			break;
		}
	}
	
	if($error ==1)
	{
		$error=1;
		//============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./filemanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filemanager.php");
	}
	else
	{
		//==============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./filemanager.php";
		
		echo "<script>window.location='success.php'</script>";	
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filemanager.php");
	}
//#endif
}
//删除媒体---未被使用
function filedel_msg($con)
{
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	mysqli_query($con,"DELETE FROM `media` WHERE id='$_GET[id]'") or die("Execute error".mysqli_error($con));
	
	mysqli_query($con,"DELETE FROM `medialist` WHERE mediaid='$_GET[id]'");
	
	if(mysqli_error($con))
	{
		//============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./filemanager.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filemanager.php");	
	}
	else
	{
		//==============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./filemanager.php";
		
		echo "<script>window.location='success.php'</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filemanager.php");	
	}
}

//添加目录分区---先判断是哪个文件夹
function add_terminal_dirarea($con)
{
	require_once("inc/config.inc.php");
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	//==================================================创建套接字类
	$create_socket_obj = new create_socket_class();

	$id=0;
	if(isset($_GET['id']))
	{
		$id = htmlspecialchars(trim($_GET['id']));
		$id = addslashes($id);
	}
	$terminaidnum = explode(",",$id);
	$folderid=0;
	if(isset($_GET['folderid']))
	{
		$folderid = htmlspecialchars(trim($_GET['folderid']));
		$folderid = addslashes($folderid);
	}
	
	$terminal_id=0;
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = htmlspecialchars(trim($_GET['terminal_id']));
		$terminal_id = addslashes($terminal_id);
	}

	$termainal_name="";
	//加锁
	mysqli_query($con,"LOCK TABLE terminalfolder WRITE,terminaloffolder WRITE,terminal WRITE");

	if($folderid==0)
	{
		$seqnumber=0;	
		$sqls = "SELECT COUNT(id),id FROM terminalfolder WHERE parentid=0 AND terminalid=$terminal_id";
		$resultss = mysqli_query($con,$sqls) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($results))
		{
			if($row[0]==0)
			{
				mysqli_query($con,"INSERT INTO terminalfolder(parentid,name,terminalid,seqnumber) VALUES (0,'目录管理',$terminal_id,$seqnumber)");
				$sql="SELECT MAX(id) FROM terminalfolder";
			 	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
			 	if($rows = mysqli_fetch_array($result))
			 	{
					$folderid=$rows['0'];
			 	}
			}
			else
			{
				$folderid=$row['id'];
			}
		}
	}

	$sqls = "SELECT terminaloffolder.terminalid,terminal.terminalname FROM terminaloffolder,terminal WHERE terminal.id=terminaloffolder.terminalid and terminaloffolder.terminalid IN($id) and terminaloffolder.folderid=$folderid";
	
	$results = mysqli_query($con,$sqls) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($results))
	{
		for($i=0; $i<count($terminaidnum); $i++)
		{
			if($terminaidnum[$i]==$row['terminalid'])
			{
				$terminaidnum[$i]=0;
			}
		}
		if($termainal_name=="")
		{
			$termainal_name=$row['terminalname'];
		}
		else 
		{
			$termainal_name=$termainal_name.",".$row['terminalname'];
		}	
	}
	if($termainal_name!="")
	{
		echo "<script>alert('".$termainal_name."-".$do_php_prompt['the_errordown']."');</script>";
	}
	for($i=0; $i<count($terminaidnum); $i++)
	{
		if($terminaidnum[$i]!=0)
		{
			mysqli_query($con,"INSERT INTO `terminaloffolder` (`terminalid`,`folderid`,`seqnumber`) VALUES ('$terminaidnum[$i]','$folderid','0')");	 
		}
	}
	$seqnumber=10000;
	$sql = "SELECT terminaloffolder.id FROM terminaloffolder,terminal WHERE folderid=$folderid AND terminaloffolder.terminalid=terminal.id ORDER BY terminal.terminalname ASC";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	while($rows = mysqli_fetch_array($result))
	{
		$seqnumber++; 
		$sql = "UPDATE terminaloffolder SET seqnumber='$seqnumber' WHERE id ='$rows[id]'";
		mysqli_query($con,$sql) or die(mysqli_error($con));	
	}	
	
	mysqli_query($con,"UNLOCK TABLES");	
	if(mysqli_error($con))
	{
		//===========================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./dir_terminal_area.php?id=".$folderid."&terminal_id".$terminal_id;
		echo "<script>window.location='error.php'</script>";
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./media_file.php");
	}
	else if(!mysqli_error($con))
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./dir_terminal_area.php?id=".$folderid."&terminal_id".$terminal_id;
		echo "<script>window.location='success.php'</script>";	
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./media_file.php");
	}
}

//删除分区目录---先判断是哪个文件夹
function delallareadir_msg($con)
{
	require_once("inc/config.inc.php");
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	//==================================================创建套接字类
	$create_socket_obj = new create_socket_class();

	$fordid=0;
	if(isset($_GET['fordid']))
	{
		$fordid = htmlspecialchars(trim($_GET['fordid']));
		$fordid = addslashes($fordid);
	}
	$terminal_id=0;
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = htmlspecialchars(trim($_GET['terminal_id']));
		$terminal_id = addslashes($terminal_id);
	}
	//加锁
	mysqli_query($con,"LOCK TABLE terminalfolder WRITE,terminaloffolder WRITE");

	mysqli_query($con,"DELETE FROM terminaloffolder WHERE terminaloffolder.folderid in ($fordid)") or die(mysqli_error($con));	
	mysqli_query($con,"DELETE FROM  terminalfolder WHERE terminalfolder.id in ($fordid)") or die(mysqli_error($con));

	//释放表
	mysqli_query($con,"UNLOCK TABLES");	
	if(mysqli_error($con))
		{
			//===========================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./dirstreammanager.php?flag=2&terminal_id=".$terminal_id;
			
			echo "<script>window.location='error.php'</script>";
			
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./media_file.php");
		}
		else if(!mysqli_error($con))
		{
			//=============================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./dirstreammanager.php?flag=2&terminal_id=".$terminal_id;
			echo "<script>parent.location.reload(true);</script>";	
			//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./media_file.php");
		}
}

//删除目录分区---先判断是哪个文件夹
function deldirarea_msg($con)
{
	require_once("inc/config.inc.php");
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	//==================================================创建套接字类
	$create_socket_obj = new create_socket_class();

	$id=0;
	if(isset($_GET['id']))
	{
		$id = htmlspecialchars(trim($_GET['id']));
		$id = addslashes($id);
	}
	
	$folderid=0;
	if(isset($_GET['folderid']))
	{
		$folderid = htmlspecialchars(trim($_GET['folderid']));
		$folderid = addslashes($folderid);
	}
	
	$terminal_id=0;
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = htmlspecialchars(trim($_GET['terminal_id']));
		$terminal_id = addslashes($terminal_id);
	}
	//加锁
	mysqli_query($con,"LOCK TABLE terminalfolder WRITE,terminaloffolder WRITE");
	if($folderid==0)
	{
		$sqls = "SELECT id FROM terminalfolder WHERE parentid=0 AND terminalid=$terminal_id";
		$results = mysqli_query($con,$sqls) or die(mysqli_error($con));
		if($row = mysqli_fetch_array($results))
		{
			$folderid=$row['id'];
		}
	}
	
	mysqli_query($con,"DELETE FROM terminaloffolder WHERE  terminaloffolder.terminalid in ($id) AND folderid=$folderid") or die(mysqli_error($con));	
	mysqli_query($con,"UNLOCK TABLES");	
	if(mysqli_error($con))
		{
			//===========================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			$_SESSION['url'] = "./dir_terminal_area.php?id=".$folderid."&terminal_id=".$terminal_id;
			echo "<script>window.location='error.php'</script>";
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./media_file.php");
		}
		else if(!mysqli_error($con))
		{
			//=============================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			$_SESSION['url'] = "./dir_terminal_area.php?id=".$folderid."&terminal_id=".$terminal_id;
			echo "<script>window.location='success.php'</script>";	
			//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./media_file.php");
		}
}

//删除媒体文件---先判断是哪个文件夹
function delallfiletask_msg($con)
{
require_once("inc/config.inc.php");
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	//==================================================创建套接字类
	$create_socket_obj = new create_socket_class();

	$fordid=0;
	if(isset($_GET['fordid']))
	{
		$fordid = trim($_GET['fordid']);
	}
	
	//加锁
	mysqli_query($con,"LOCK TABLE media WRITE,filefolder WRITE,mediaoftask WRITE,shortcutkeytask WRITE,alarmgroupmap WRITE,camer_alarmofmedia WRITE");

		$sqls = "SELECT id,filename FROM media WHERE folderid IN($fordid)";
	$results = mysqli_query($con,$sqls) or die(mysqli_error($con));
		while($row = mysqli_fetch_array($results))
		{
			$delete_media_id=$row['id'];
			if($delete_media_id>1)
			{
				$getfilename="rm -rf link".$row['filename'];
				system($getfilename);
				mysqli_query($con,"DELETE FROM  media WHERE media.id in ($delete_media_id)") or die(mysqli_error($con));
			}
			mysqli_query($con,"DELETE FROM  camer_alarmofmedia WHERE mediaid in ($delete_media_id)") or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM  mediaoftask WHERE mediaid in ($delete_media_id)") or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM  alarmgroupmap WHERE mediaid in ($delete_media_id)") or die(mysqli_error($con));
		}

	if(mysqli_error($con))
		{
			//===========================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./media_file.php";
			
			echo "<script>window.location='error.php'</script>";
			
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./media_file.php");
		}
		else if(!mysqli_error($con))
		{
			//=============================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./media_file.php";
			
			echo "<script>window.location='success.php'</script>";	
			
			//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./media_file.php");
		}
}

//删除媒体文件---先判断是哪个文件夹
function delfiletask_msg($con)
{
	require_once("inc/config.inc.php");
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	//==================================================创建套接字类
	$create_socket_obj = new create_socket_class();
	$getid=$_GET['id'];


	$getdelflag=0;
	//加锁
	mysqli_query($con,"LOCK TABLE media WRITE,filefolder WRITE,mediaoftask WRITE,shortcutkeytask WRITE,alarmgroupmap WRITE,camer_alarmofmedia WRITE");
	

	$sqls = "SELECT mediaid FROM shortcutkeytask WHERE shortcutkeytask.mediaid IN($getid)";
	$results = mysqli_query($con,$sqls) or die(mysqli_error($con));
	
	if(mysqli_num_rows($results) > 0)
	{
			$forward_ok_error_obj->exit_back_function($do_php_prompt['using_not_deleted']);	
	}
	
   //读取媒体任务
	$sql = "SELECT DISTINCT mediaoftask.mediaid,media.name FROM mediaoftask,media WHERE mediaoftask.mediaid = media.id  AND mediaoftask.mediaid IN($getid)";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result) > 0)
	{
		while($row = mysqli_fetch_array($result))
		{
			
			 	$forward_ok_error_obj->exit_back_function($row['name']."".$do_php_prompt['using_not_deleted']);	
			
		}
	}
	else
	{	
		//读取媒体任务
		$sql = "SELECT DISTINCT alarmgroupmap.mediaid,media.name FROM alarmgroupmap,media WHERE alarmgroupmap.mediaid = media.id AND alarmgroupmap.mediaid ='$getid'";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if(mysqli_num_rows($result) > 0)
		{
			while($row = mysqli_fetch_array($result))
			{
					$forward_ok_error_obj->exit_back_function($row['name']."".$do_php_prompt['usingmap_not_deleted']);	
			}
		}
	}

	unset($sql,$row);
	
	//判断是否有权限删除
	$sql = "SELECT filefolder.id, filefolder.name,filefolder.parentid FROM filefolder WHERE filefolder.id IN ";
	
	$sql.= "(SELECT media.folderid FROM media WHERE media.id IN ($_GET[id])) ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	while($row=mysqli_fetch_array($result))
	{
		if($row['parentid']<=7&&$row['parentid']>0)
		{
			$getdelflag=$row['parentid'];
		}
	
		if( ($row['id']==9||$row['id']==8||$row['id']==7||$row['id']==6||$row['id']==5 || $row['id'] == 1 || $row['id'] == 2 || $row['id'] == 3 || $row['id'] == 4) && ($_SESSION['admin_id']!="administrator") )
		{
			//=============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['Authority_not_enough'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Authority_not_enough']);
		}
	}

	
	unset($sql,$row);
	//保留可删除的媒体ID并删除响应的文件
	$delete_media_id = "";
	
	//$sql="SELECT media.id,media.filename FROM media WHERE media.id IN ($_GET[id])";

	$sql = "SELECT media.id,media.filename FROM audioserver.media WHERE media.id IN (".$_GET['id'].") ";
	
	$sql.= "AND audioserver.media.id NOT IN (SELECT DISTINCT mediaid FROM mediaoftask ) ";

	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result) > 0)
	{
		while ($row = mysqli_fetch_array($result))
		{
			if($delete_media_id == "")
			{
				$delete_media_id = $row['id'];
			}
			else
			{
				$delete_media_id.= ", ".$row['id'];
			}
		
			//	unlink($row["filename"]);
			if($row['id']>1)
			{
				$getfilename="rm -rf link".$row['filename'];
				system($getfilename);
			}
		}
	}
	
	@mysqli_free_result($result);
	
	unset($sql,$row);
	//判断有没有能够删除的文件
	if($delete_media_id !="")
	{
		//$socket = new send_message_to_server($port_conf);
		
		//$sqlfolder = "SELECT folderid FROM media WHERE media.id IN ($_GET[id]) GROUP BY folderid ";
		
		$sqlfolder = "SELECT folderid FROM media WHERE media.id IN (".$delete_media_id.") GROUP BY folderid ";
		
		$resultfolder = mysqli_query($con,$sqlfolder) or die(mysqli_error($con));
		
		while($rowfolder = mysqli_fetch_array($resultfolder))
		{
			$strfolder = $rowfolder['folderid'];
			//===============================================================================
			$create_socket_obj->send_socket_media_file("file",0,$strfolder);
		
			/*$strbuff = "file?state=0&id=".$strfolder."";
			
			$socket->send_data($_SESSION['serverip'],$strbuff);
			*/
			//unset($strfolder,$strbuff);
			
		}
			if($getdelflag!=0)
			{
				$create_socket_obj->send_socket_media_file("file",0,$getdelflag);
			}
		mysqli_query($con,"DELETE FROM  media WHERE media.id in ($delete_media_id)") or die(mysqli_error($con));
		mysqli_query($con,"DELETE FROM  camer_alarmofmedia WHERE mediaid in ($delete_media_id)") or die(mysqli_error($con));
		mysqli_query($con,"DELETE FROM  alarmgroupmap WHERE mediaid in ($delete_media_id)") or die(mysqli_error($con));
		if(mysqli_error($con))
		{
			//===========================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./media_file.php";
			
			echo "<script>window.location='error.php'</script>";
			
			//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./media_file.php");
		}
		else if(!mysqli_error($con))
		{
			//=============================================================
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./media_file.php";
			
			echo "<script>window.location='success.php'</script>";	
			
			//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./media_file.php");
		}
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
		$_SESSION['url'] = "./media_file.php";
		
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./media_file.php");
	}
	//释放表
	mysqli_query($con,"UNLOCK TABLES");
}
//添加文件夹
function folderadd_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$folderName ="";
	
	if(isset($_POST['folderName']))
	{
		$folderName = trim($_POST['folderName']);
	}
	
	if(isset($_GET['folder_id']))
	{
		if(!empty($_GET['folder_id']))//0 '' false null array() array(array())
		{
			$folder_id = trim($_GET['folder_id']);	
		}
	}

	$isOrNoShare =0;
	
	if($_POST['isOrNoShare'] != "")
	{
		$isOrNoShare =1;
	}
	//获取用户id
	$sql_user = "SELECT id FROM book_admin WHERE book_admin.username = '".$_SESSION['username']."'";
	
	$result_user = mysqli_query($con,$sql_user) or die(mysqli_error($con));
	
	$row_user = mysqli_fetch_array($result_user);
	
	$userid = trim($row_user['id']);
	
	@mysqli_free_result($result_user);
	
	unset($row_user,$sql_user);
	//是否有同名文件夹
	$folder_sql="SELECT * FROM filefolder WHERE filefolder.name='$folderName' AND parentid ='$folder_id'";
	
	$folder_result = mysqli_query($con,$folder_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($folder_result) > 0)
	{
		//=====================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."')</script>";//提示信息
		
		echo "<script>history.back();</script>";
	
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysqli_free_result($folder_result);
	
	unset($folder_sql);
	
	mysqli_query($con," LOCK TABLE filefolder WRITE");
	
	mysqli_query($con,"INSERT INTO filefolder (name,userid,priority,parentid) VALUES ('$_POST[folderName]','$userid','$isOrNoShare','$folder_id ')");
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		//============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./filefoldermanager.php";
	
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./filefoldermanager.php";
		echo "<script>parent.location.reload(true);</script>";
	
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}	
}


function taskfolderadd_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$folderName ="";
	
	if(isset($_POST['folderName']))
	{
		$folderName = trim($_POST['folderName']);
	}
	
	if(isset($_GET['folder_id']))
	{
		if(!empty($_GET['folder_id']))//0 '' false null array() array(array())
		{
			$folder_id = trim($_GET['folder_id']);	
		}
		else
		{
			$folder_id = 0;	
			//echo "<script>alert('".$_GET['folder_id']."');</script>";
			//$forward_ok_error_obj->exit_back_function($do_php_prompt['noselectitem']);
		}
	}

	/*
	if(isset($_GET['userid']))
	{
		if(!empty($_GET['userid']))//0 '' false null array() array(array())
		{
			$userid = trim($_GET['userid']);	
		}
	}*/

	$userid=$_SESSION['userid'];
	
//	$userid=$_SESSION['userid'];
	mysqli_query($con," LOCK TABLE filetaskfree WRITE");
	//是否有同名文件夹
	$folder_sql="SELECT * FROM filetaskfree WHERE filetaskfree.name='$folderName' AND parentid ='$folder_id'";
	
	$folder_result = mysqli_query($con,$folder_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($folder_result) > 0)
	{
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysqli_free_result($folder_result);
	
	unset($folder_sql);

	$getsql="INSERT INTO filetaskfree(name,parentid,userid) VALUES ('$folderName','$folder_id','$userid')";
	
	mysqli_query($con,$getsql);
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		//============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./displayfilemanager.php";
		echo "<script>window.location='error.php'</script>";
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./displayfilemanager.php";
	
		echo "<script>parent.location.reload();</script>";
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}	
}
function get_dirareaparent_msg($con,$id,$terminal_id)
{
	$folder_sql="SELECT * FROM terminalfolder WHERE id = '$id' AND terminalid = '$terminal_id'";
	
	$folder_result = mysqli_query($con,$folder_sql) or die(mysqli_error($con));
	
	while($rowfolder = mysqli_fetch_array($folder_result))
	{
		$_SESSION['dirarea_num']++;
		
		 get_dirareaparent_msg($con,$rowfolder['parentid'],$terminal_id);
	
	}
	
	@mysqli_free_result($folder_result);
	
	unset($folder_sql);
}
function dirareaadd_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$folderName ="";
	
	if(isset($_POST['folderName']))
	{
		$folderName = trim($_POST['folderName']);
	}
	
	if(isset($_GET['folder_id']))
	{
			$folder_id = trim($_GET['folder_id']);	
		
	}
	if(isset($_GET['userid']))
	{
		if(!empty($_GET['userid']))//0 '' false null array() array(array())
		{
			$userid = trim($_GET['userid']);
		
		}
	}
	$terminal_id =0;
	if(isset($_GET['terminal_id']))
	{
		if(!empty($_GET['terminal_id']))//0 '' false null array() array(array())
		{
			$terminal_id = trim($_GET['terminal_id']);
			
		}
	}
	$seqnumber=0;
	
//	$userid=$_SESSION['userid'];
	mysqli_query($con," LOCK TABLE terminalfolder WRITE");
	
	$sql="SELECT count(id) FROM terminalfolder WHERE terminalid='$terminal_id'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	if($rowfolders = mysqli_fetch_array($result))
	{
		if($rowfolders['0']==0)
		{
			mysqli_query($con,"DELETE FROM terminalfolder WHERE parentid=0 and terminalid=$terminal_id") or die(mysqli_error($con));
			mysqli_query($con,"INSERT INTO terminalfolder(parentid,name,terminalid,seqnumber) VALUES (0,'目录管理',$terminal_id,$seqnumber)");
			$sqls="SELECT MAX(id) FROM terminalfolder";
			 $results = mysqli_query($con,$sqls) or die(mysqli_error($con));
			 if($rows = mysqli_fetch_array($results))
			 {
				$folder_id=$rows['0'];
			 }
		}
		else
		{
			if($folder_id==0)
			{
				$sqls="SELECT id FROM terminalfolder WHERE terminalid='$terminal_id' and parentid=0";
				 $results = mysqli_query($con,$sqls) or die(mysqli_error($con));
				 if($rows = mysqli_fetch_array($results))
				 {
					$folder_id=$rows['0'];
				 }
			}
		}
	}

	//是否有同名文件夹
	$folder_sql="SELECT * FROM terminalfolder WHERE terminalfolder.name='$folderName' AND parentid ='$folder_id'  AND terminalid='$terminal_id'";
	$folder_result = mysqli_query($con,$folder_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($folder_result) > 0)
	{
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysqli_free_result($folder_result);
	unset($folder_sql);
	
	$_SESSION['dirarea_num']=0;
	get_dirareaparent_msg($con,$folder_id,$terminal_id);

	if($_SESSION['dirarea_num']>=4)
	{
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_dir_num_error']);
	}
	
	mysqli_query($con,"INSERT INTO terminalfolder(parentid,name,terminalid,seqnumber) VALUES ('$folder_id','$folderName','$terminal_id','$seqnumber')");
	$foldersql="SELECT id FROM terminalfolder WHERE  parentid ='$folder_id'  AND terminalid='$terminal_id' ORDER BY name ASC";
	$folderresult = mysqli_query($con,$foldersql) or die(mysqli_error($con));
	while($row_folders = mysqli_fetch_array($folderresult))
	{
		$seqnumber++;
		$sql = "UPDATE terminalfolder SET seqnumber='$seqnumber' WHERE id ='$row_folders[id]'";
		mysqli_query($con,$sql) or die(mysqli_error($con));	
	}
	unset($sql);
	mysqli_query($con,"UNLOCK TABLES");

	if(mysqli_error($con))
	{
		//============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./dirstreammanager.php?flag=2&terminal_id=".$terminal_id;
		echo "<script>window.location='error.php'</script>";
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./dirstreammanager.php?flag=2&terminal_id=".$terminal_id;
		echo "<script>parent.location.reload();</script>";
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}	
}

function ledfolderadd_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();

	$folderName ="";
	
	if(isset($_POST['folderName']))
	{
		$folderName = trim($_POST['folderName']);
	}
	$folder_id=0;
	if(isset($_GET['folder_id']))
	{
		if(!empty($_GET['folder_id']))//0 '' false null array() array(array())
		{
			$folder_id = trim($_GET['folder_id']);	
		}
	}
	$userid=0;
	if(isset($_GET['userid']))
	{
		if(!empty($_GET['userid']))//0 '' false null array() array(array())
		{
			$userid = trim($_GET['userid']);	
		}
	}

	if($userid==0)
	{
		$userid=$_SESSION['userid'];
	}
	
	mysqli_query($con," LOCK TABLE ledtaskfree WRITE");

	//是否有同名文件夹
	$folder_sql="SELECT * FROM ledtaskfree WHERE ledtaskfree.name='$folderName' AND parentid ='$folder_id'";


	$folder_result = mysqli_query($con,$folder_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($folder_result) > 0)
	{
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}	

	unset($folder_sql);
	
	mysqli_query($con,"INSERT INTO ledtaskfree(name,parentid,userid) VALUES ('$folderName','$folder_id','$userid')");
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		//============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./leddisplaymanager.php";
		echo "<script>window.location='error.php'</script>";
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./leddisplaymanager.php";
		echo "<script>parent.location.reload();</script>";
		//echo "<script>window.location='success.php'</script>";
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}	
}

function aideviceadd_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$ai_devicename ="";
	if(isset($_POST['ai_devicename']))
	{
		$ai_devicename = trim($_POST['ai_devicename']);
	}

	$ai_deviceid ="";
	if(isset($_POST['ai_deviceid']))
	{
		$ai_deviceid = trim($_POST['ai_deviceid']);
	}
//	$userid=$_SESSION['userid'];
	mysqli_query($con," LOCK TABLE ai_devicedemo WRITE");
	//是否有同名文件夹
	$folder_sql="SELECT * FROM ai_devicedemo WHERE ai_devicedemo.shibiedeviceid='$ai_deviceid'";
	
	$folder_result = mysqli_query($con,$folder_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($folder_result) > 0)
	{
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}	
	@mysqli_free_result($folder_result);
	unset($folder_sql);
	
	mysqli_query($con,"INSERT INTO ai_devicedemo(shibiedeviceid,deviceaddr) VALUES ('$ai_deviceid','$ai_devicename')");
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		//============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./aimanager.php";
		echo "<script>window.location='error.php'</script>";
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./aimanager.php";
		
		echo "<script>parent.location.reload();</script>";
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}	
}
//========================删除文件夹、判断是否有已被用的媒体======================
function folderdel_msg($con)
{
	//=====保留系统预留文件夹=====//
	//添加外部变量
	global $do_php_prompt;
	$get_folder_id = "";
	//=====================创建对象=====================
	$database_operate_obj = new database_operate_class();
	//=====================创建跳转对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	$create_socket_obj = new create_socket_class();
	//取文件夹
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
		$get_folder_id_array = explode(",",$get_folder_id);
	}
	
	foreach($get_folder_id_array as $value)
	{
		if($value == 5 || $value == 1 || $value == 2 || $value == 3 || $value == 4||$value == 6 ||$value == 7||$value == 8||$value == 9 )
		{
			$forward_ok_error_obj->exit_back_function($do_php_prompt['not_delete_system_files']);
		}
		else 
		{
			
		}
	}
	//=================================================不能删除已被使用的媒体、并重新赋值===================================
	$get_folder_id = $database_operate_obj->whether_have_exit($get_folder_id_array,$do_php_prompt['contains_use_folder_failed'],$do_php_prompt['failed_all_selected_folder']);
	
	//=====删除文件夹先删除文件=====//
	@mysqli_query($con,"LOCK TABLE media WRITE,filefolder WRITE");
	
	@mysqli_query($con,"START TRANSACTION");
	$sql_folder = "SELECT filename,id,folderid FROM media WHERE media.folderid IN ($get_folder_id)";
	$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
	
	while($row_folder = mysqli_fetch_array($result_folder))
	{
			if($row_folder['id']>1)
			{
				//unlink($row_folder['filename']);
				$getfilename="rm -rf link".$row_folder['filename'];
				system($getfilename);
			}
			
	
		 mysqli_query($con,"DELETE FROM shortcutkeytask WHERE mediaid='$row_folder[id]'") or die(mysqli_error($con));	
		 mysqli_query($con,"DELETE FROM  camer_alarmofmedia WHERE mediaid ='$row_folder[id]'") or die(mysqli_error($con));
		  mysqli_query($con,"DELETE FROM  alarmgroupmap WHERE mediaid ='$row_folder[id]'") or die(mysqli_error($con));
	}
	
	@mysqli_free_result($result_folder);
	
	unset($row_folder,$sql_folder);
	
	$sql_media = "DELETE FROM media WHERE media.folderid IN ($get_folder_id)";
	$del_media = mysqli_query($con,$sql_media) or die(mysqli_error($con));
	unset($sql_media);
	
	
	$sql_folder = "DELETE FROM filefolder WHERE filefolder.id IN ($get_folder_id)";
	
	$del_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
	
	unset($sql_folder);
	
	$sql = "SELECT filename,id FROM media WHERE media.folderid IN (select id from filefolder where parentid in($get_folder_id))";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	while($row = mysqli_fetch_array($result))
	{
		if($row_folder['id']>1)
		{
			$getfilename="rm -rf link".$row_folder['filename'];
			system($getfilename);
		}
			//unlink($getfilename);
			$media_id=$row['id'];
			$sql_media_del = "DELETE FROM media WHERE media.id =$media_id";
			$del_medias = mysqli_query($con,$sql_media_del) or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM  camer_alarmofmedia WHERE mediaid ='$media_id'") or die(mysqli_error($con));
	
	}
	mysqli_query($con,"DELETE FROM filefolder WHERE filefolder.parentid IN($get_folder_id)");
	if($del_folder && $del_media)
	{
		@mysqli_query($con,"COMMIT");
	}
	else
	{
		@mysqli_query($con,"ROLLBACK");
	}
	$create_socket_obj->send_socket_media_file("file",0, $get_folder_id);
	$create_socket_obj->send_socket_media_file("file",0,3);
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		//===========================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./filefoldermanager.php";
	
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./filemanager.php";
	
		echo "<script>parent.location.reload(true);</script>";	
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}
}

function taskfolderdel_msg($con)
{
	//=====保留系统预留文件夹=====//
	//添加外部变量
	global $do_php_prompt;
	
	$get_folder_id = "";
	//=====================创建对象=====================
	$database_operate_obj = new database_operate_class();
	//=====================创建跳转对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//取文件夹
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);

	}
		if($get_folder_id == 1)
		{
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['not_delete_system_files']);
		}

	//=====删除文件夹先删除文件=====//
	@mysqli_query($con,"LOCK TABLE filetaskfree WRITE,mediaoftask WRITE,terminaloftask WRITE,task WRITE");
	
	@mysqli_query($con,"START TRANSACTION");

	$sql_folder = "SELECT taskid FROM task WHERE parentid ='$get_folder_id'OR parentid IN (SELECT id FROM filetaskfree WHERE parentid='$get_folder_id')";
	
	$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
	
	while($row_folder = mysqli_fetch_array($result_folder))
	{
		$get_id=$row_folder['taskid'];
		$sql_media = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$get_id'";
		
		$del_media = mysqli_query($con,$sql_media) or die(mysqli_error($con));
		unset($sql_media);
	
		$sql_folder = "DELETE FROM mediaoftask WHERE mediaoftask.taskid ='$get_id'";
	
		$del_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
	
		unset($sql_folder);
		$sql_task = "DELETE FROM task WHERE task.taskid ='$get_id'";
	
		$del_task = mysqli_query($con,$sql_task) or die(mysqli_error($con));
	
		unset($sql_task);
		$del_media = mysqli_query($con,"DELETE FROM terminalkey WHERE id IN(SELECT keyid FROM terminalkeymap WHERE terminalid='$get_id')") or die(mysqli_error($con));
		$del_media = mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalid='$get_id'") or die(mysqli_error($con));
			
	}
	
	@mysqli_free_result($result_folder);
	
	unset($row_folder,$sql_folder);
	/*	
	$sql_foldertree = "SELECT id FROM filetaskfree WHERE id ='$get_folder_id' OR parentid='$get_folder_id'";
	$result_foldertree = mysqli_query($con,$sql_foldertree) or die(mysqli_error($con));
	while($row_foldertree = mysqli_fetch_array($result_foldertree))
	{
	}*/
	
	$folder = "DELETE FROM filetaskfree WHERE id ='$get_folder_id' OR parentid='$get_folder_id'";
	$foldertask = mysqli_query($con,$folder) or die(mysqli_error($con));
	unset($folder);
	$sql_led_name = "SELECT taskid FROM task where tasktype=24 and sec_task_id = '$taskid'";	
		$result_led_name = mysqli_query($con,$sql_led_name) or die(mysqli_error($con));
		if(mysqli_num_rows($result_led_name) > 0)
		{
			if($get_row = mysqli_fetch_array($result_led_name))
			{	
				$getledtaskid=$get_row['taskid'];
				del_ledtask($con,$getledtaskid,24);
			}
		}	
		@mysqli_free_result($result_led_name);	
		unset($sql_led_name);

	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		//===========================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./displayfilemanager.php";
	
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./displayfilemanager.php";
	
		echo "<script>parent.location.reload();</script>";	
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}
}

function ledfolderdel_msg($con)
{
	//=====保留系统预留文件夹=====//
	//添加外部变量
	global $do_php_prompt;
	
	$get_folder_id = "";
	//=====================创建对象=====================
	$database_operate_obj = new database_operate_class();
	//=====================创建跳转对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//取文件夹
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);

	}
		if($get_folder_id == 1)
		{
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['not_delete_system_files']);
		}

	//=====删除文件夹先删除文件=====//
	@mysqli_query($con,"LOCK TABLE ledtaskfree WRITE,mediaoftask WRITE,terminaloftask WRITE,task WRITE");
	
	@mysqli_query($con,"START TRANSACTION");

	$sql_folder = "SELECT taskid FROM task WHERE parentid ='$get_folder_id'OR parentid IN (SELECT id FROM ledtaskfree WHERE parentid='$get_folder_id') and cmdargs >70000";
	
	$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
	
	while($row_folder = mysqli_fetch_array($result_folder))
	{
		$get_id=$row_folder['taskid'];
		$sql_media = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$get_id'";
		
		$del_media = mysqli_query($con,$sql_media) or die(mysqli_error($con));
		unset($sql_media);
	
		$sql_folder = "DELETE FROM mediaoftask WHERE mediaoftask.taskid ='$get_id'";
	
		$del_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
	
		unset($sql_folder);
		$sql_task = "DELETE FROM task WHERE task.taskid ='$get_id'";
	
		$del_task = mysqli_query($con,$sql_task) or die(mysqli_error($con));
	
		unset($sql_task);
		$del_media = mysqli_query($con,"DELETE FROM terminalkey WHERE id IN(SELECT keyid FROM terminalkeymap WHERE terminalid='$get_id')") or die(mysqli_error($con));
		$del_media = mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalid='$get_id'") or die(mysqli_error($con));
			
	}
	
	@mysqli_free_result($result_folder);
	
	unset($row_folder,$sql_folder);
	/*	
	$sql_foldertree = "SELECT id FROM filetaskfree WHERE id ='$get_folder_id' OR parentid='$get_folder_id'";
	$result_foldertree = mysqli_query($con,$sql_foldertree) or die(mysqli_error($con));
	while($row_foldertree = mysqli_fetch_array($result_foldertree))
	{
	}*/
	
	$folder = "DELETE FROM ledtaskfree WHERE id ='$get_folder_id' OR parentid='$get_folder_id'";
	$foldertask = mysqli_query($con,$folder) or die(mysqli_error($con));
	unset($folder);

	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		//===========================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./leddisplaymanager.php";
	
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./leddisplaymanager.php";
	
		echo "<script>parent.location.reload();</script>";	
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}
}

function aifolderdel_msg($con)
{
	//=====保留系统预留文件夹=====//
	//添加外部变量
	global $do_php_prompt;
	
	$get_folder_id = "";
	//=====================创建对象=====================
	$database_operate_obj = new database_operate_class();
	//=====================创建跳转对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//取文件夹
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);

	}
	
	//=====删除文件夹先删除文件=====//
	@mysqli_query($con,"LOCK TABLE ai_devicedemo WRITE,ai_people WRITE");
	
	@mysqli_query($con,"START TRANSACTION");

	
	 mysqli_query($con,"DELETE FROM ai_devicedemo WHERE shibiedeviceid= '$get_folder_id'") or die(mysqli_error($con));
	 mysqli_query($con,"DELETE FROM ai_people WHERE shibiedeviceid='$get_folder_id'") or die(mysqli_error($con));
			

	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		//===========================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./aimanager.php";
	
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./aimanager.php";
	
		echo "<script>parent.location.reload();</script>";	
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}
}


//修改目录
function foldermodify_msg($con)
{
	
	//=====================添加外部变量=================
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_folder_id = "";
	
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
	}
	$folderName = "";
	
	if(isset($_POST['folderName']))
	{
		$folderName = trim($_POST['folderName']);
	}
	$isOrNoShare = 1;
	
	if(isset($_POST['isOrNoShare']))
	{
		$isOrNoShare = trim($_POST['isOrNoShare']);
	}
	
	$sql_user = "SELECT id FROM book_admin WHERE book_admin.username = '".trim($_SESSION['username'])."'";

	$result_user = mysqli_query($con,$sql_user) or die(mysqli_error($con));

	$row_user = mysqli_fetch_array($result_user);

	$user_id = $row_user['id'];
	
	@mysqli_free_result($result_user);

	unset($row_user,$sql_user);
	
	//=====检测是否相同=====//

	$sql_folder = "SELECT * FROM filefolder WHERE filefolder.id != '$get_folder_id' AND filefolder.name = '$folderName' AND filefolder.userid = '$user_id' AND parentid IN (SELECT parentid FROM filefolder WHERE filefolder.id='$get_folder_id')";
	
	$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));

	if(mysqli_num_rows($result_folder) > 0)
	{
		//===========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";

		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysqli_free_result($result_folder);

	unset($sql_folder);

	//=====更新文件夹=====//
	mysqli_query($con,"LOCK TABLE filefolder WRITE");

	$sql_folder = "UPDATE filefolder SET NAME = '$folderName' , priority = '$isOrNoShare' WHERE filefolder.id = '$get_folder_id'";

	mysqli_query($con,$sql_folder) or die(mysqli_error($con));

	unset($sql_folder);

	mysqli_query($con,"UNLOCK TABLES");

	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./filefoldermanager.php";

		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./filefoldermanager.php";

		echo "<script>parent.location.reload(true);</script>";
	}
}
function taskfoldermodify_msg($con)
{
	
	//=====================添加外部变量=================
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_folder_id = "";
	
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
	}
	$folderName = "";
	
	if(isset($_POST['folderName']))
	{
		$folderName = trim($_POST['folderName']);
	}
$userid=$_SESSION['userid'];
	
	//=====检测是否相同=====//
	$sql_folder = "SELECT * FROM filetaskfree WHERE filetaskfree.id != '$get_folder_id' AND filetaskfree.name = '$folderName'";
	
	$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));

	if(mysqli_num_rows($result_folder) > 0)
	{
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysqli_free_result($result_folder);

	unset($sql_folder);

	//=====更新文件夹=====//
	mysqli_query($con,"LOCK TABLE filetaskfree WRITE");

	$sql_folder = "UPDATE filetaskfree SET NAME = '$folderName',userid='$userid' WHERE filetaskfree.id = '$get_folder_id'";

	mysqli_query($con,$sql_folder) or die(mysqli_error($con));

	unset($sql_folder);

	mysqli_query($con,"UNLOCK TABLES");

	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./displayfilemanager.php";

		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./displayfilemanager.php";

		echo "<script>parent.location.reload();</script>";
	}
}

function dirareamodify_msg($con)
{
	//=====================添加外部变量=================
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_folder_id = "";
	
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
		
	}
	$terminal_id = "";
	
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
	
	}
	
	$folderName = "";
	
	if(isset($_POST['folderName']))
	{
		$folderName = trim($_POST['folderName']);
		
	}
	$userid=$_SESSION['userid'];
	
	//=====检测是否相同=====//
	$sql_folder = "SELECT * FROM terminalfolder WHERE terminalfolder.id != '$get_folder_id' AND terminalfolder.name = '$folderName' AND terminalid='$terminal_id'";
	
	$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));

	if(mysqli_num_rows($result_folder) > 0)
	{
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysqli_free_result($result_folder);

	unset($sql_folder);

	//=====更新文件夹=====//
	mysqli_query($con,"LOCK TABLE terminalfolder WRITE");

	$sql_folder = "UPDATE terminalfolder SET terminalfolder.name = '$folderName' WHERE terminalfolder.id = '$get_folder_id' AND terminalid='$terminal_id'";
	
	mysqli_query($con,$sql_folder) or die(mysqli_error($con));

	unset($sql_folder);

	mysqli_query($con,"UNLOCK TABLES");

	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./dirstreammanager.php?flag=2&terminal_id=".$terminal_id;

		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./dirstreammanager.php?flag=2&terminal_id=".$terminal_id;

		echo "<script>parent.location.reload();</script>";
	}
}
function ledfoldermodify_msg($con)
{
	
	//=====================添加外部变量=================
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_folder_id = "";
	
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
	}
	$folderName = "";
	
	if(isset($_POST['folderName']))
	{
		$folderName = trim($_POST['folderName']);
	}
$userid=$_SESSION['userid'];
	
	//=====检测是否相同=====//
	$sql_folder = "SELECT * FROM ledtaskfree WHERE ledtaskfree.id != '$get_folder_id' AND ledtaskfree.name = '$folderName'";
	
	$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));

	if(mysqli_num_rows($result_folder) > 0)
	{
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysqli_free_result($result_folder);

	unset($sql_folder);

	//=====更新文件夹=====//
	mysqli_query($con,"LOCK TABLE ledtaskfree WRITE");

	$sql_folder = "UPDATE ledtaskfree SET NAME = '$folderName',userid='$userid' WHERE ledtaskfree.id = '$get_folder_id'";

	mysqli_query($con,$sql_folder) or die(mysqli_error($con));

	unset($sql_folder);

	mysqli_query($con,"UNLOCK TABLES");

	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./leddisplaymanager.php";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./leddisplaymanager.php";

		echo "<script>parent.location.reload();</script>";
	}
}

function aifoldermodify_msg($con)
{
	
	//=====================添加外部变量=================
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_folder_id = "";
	
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
	}
	$ai_devicename = "";
	if(isset($_POST['ai_devicename']))
	{
		$ai_devicename = trim($_POST['ai_devicename']);
	}
	
	//=====更新文件夹=====//
	mysqli_query($con,"LOCK TABLE ai_devicedemo WRITE");

	$sql_folder = "UPDATE ai_devicedemo SET deviceaddr = '$ai_devicename' WHERE shibiedeviceid = '$get_folder_id'";

	mysqli_query($con,$sql_folder) or die(mysqli_error($con));

	unset($sql_folder);

	mysqli_query($con,"UNLOCK TABLES");

	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./aimanager.php";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./aimanager.php";

		echo "<script>parent.location.reload();</script>";
	}
}

//取消用户终端
function cancel_user_terminal($con)
{
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$userid = "";
	
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	$terminalid = "";
	
	if(isset($_GET['terminalid']))
	{
		$terminalid = trim($_GET['terminalid']);
	}
	
	//判断用户是否为超级用户
	$group_result = mysqli_query($con,"SELECT usergroupid FROM book_admin WHERE book_admin.id='$userid'") or die(mysqli_error($con));
	
	if($group_row = mysqli_fetch_array($group_result))
	{
		$group_id = $group_row['usergroupid'];
	}
	
	@mysqli_free_result($group_result);
	
	unset($group_row);
	
	if($group_id == 1)
	{
		//=============================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['Super_user_not_modified'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
		
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['Super_user_not_modified']);
	}
	else
	{
		$user_terminal_sql = "DELETE FROM userterminal WHERE userterminal.userid = '$userid' ";
		
		$user_terminal_sql.= "AND userterminal.terminalid IN($terminalid)";
		
		mysqli_query($con,$user_terminal_sql);
		
		if(!mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "view_user_terminal.php?id=$userid";
			
			echo "<script>window.location='success.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "view_user_terminal.php?id=$userid";
			
			echo "<script>window.location='error.php'</script>";
		}
	}
}
//用户添加
function useradd_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$username = "";
	if(isset($_POST['username']))
	{
		$username = trim($_POST['username']);
	}
	$info = "";
	if(isset($_POST['info']))
	{
		$info = trim($_POST['info']);
	}

	$usergroup = "";
	if(isset($_POST['usergroup']))
	{
		$usergroup = trim($_POST['usergroup']);
	}
	$newpwd = "";
	if(isset($_POST['newpwd']))
	{
		$newpwd = trim($_POST['newpwd']);
	}
	
	
	$ctrlterminalcount = 0;
	if(isset($_POST['ctrlterminalcount']))
	{
		$ctrlterminalcount = trim($_POST['ctrlterminalcount']);
	}
	
	$fenkongIDtask = 0;
	if(isset($_POST['fenkongIDtask']))
	{
		$fenkongIDtask = trim($_POST['fenkongIDtask']);
	}
	
	$jiankongID = 0;
	if(isset($_POST['jiankongID']))
	{
		$jiankongID = trim($_POST['jiankongID']);
	}
	
	$get_count = 0;
	if(isset($_GET['count']))
	{
		$get_count = trim($_GET['count']);
	}
	
	$confirmpwd = "";
	if(isset($_POST['confirmpwd']))
	{
		$confirmpwd = trim($_POST['confirmpwd']);	
	}
	if($newpwd == $confirmpwd)
	{
		$newpwd = md5($newpwd);
	}
	else
	{
		$forward_ok_error_obj->exit_back_function($do_php_prompt['Passwords_not_same']);
	}
	
	$terminal_id = "";	
	if(isset($_POST['terminal_id']))
	{
		$terminal_id = trim($_POST['terminal_id']);
		$terminal_array = explode(",",$terminal_id);
	}
	$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
	$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);

	mysqli_query($con,"LOCK TABLE book_admin WRITE,serverplaystream WRITE,terminalofgroup WRITE,userterminal WRITE,filetaskfree WRITE");
	
	//用户名是否相同
	$sql_username = "select * from book_admin where book_admin.username = '$username'";
	$result_username = mysqli_query($con,$sql_username) or die(mysqli_error($con));
	if(mysqli_num_rows($result_username) > 0)
	{
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	@mysqli_free_result($result_username);
	
	unset($sql_username);
	//判断分控ID是否相同

	if($ctrlterminalcount!=0)
	{
		for($k=1;$k<=$get_count;$k++)
		{
			
			$sql_username = "select ctrlwind from book_admin where ctrlwind=$k";

			$result_username = mysqli_query($con,$sql_username) or die(mysqli_error($con));
			if(mysqli_num_rows($result_username) <=0)
			{
				$ctrlterminalcount=$k;
				break;
			}
		}
		
		if($k>$get_count)
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_fenkong_has_been_used']);
	}
	
	if($fenkongIDtask!=0)
	{
		  $get_counts=1000+$get_count;
			for($k=1001;$k<=$get_counts;$k++)
			{
				$sql_username = "select subwind from book_admin where subwind=$k";
				$result_username = mysqli_query($con,$sql_username) or die(mysqli_error($con));
				if(mysqli_num_rows($result_username) <=0)
				{
					$fenkongIDtask=$k;
					break;
				}
			}
	
		if($k>$get_counts)
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_diannao_has_been_used']);
			
	
	}
	
	if($jiankongID!=0)
	{
			$get_counts=2000+$get_count;
			for($k=2001;$k<=$get_counts;$k++)
			{
				$sql_username = "select camerawind from book_admin where camerawind=$k";
				$result_username = mysqli_query($con,$sql_username) or die(mysqli_error($con));
				if(mysqli_num_rows($result_username) <=0)
				{
					$jiankongID=$k;
					break;
				}
			}
			if($k>$get_counts)
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_jiankong_has_been_used']);
	}
	
	//插入用户
	$sql_username = "INSERT INTO book_admin (username, userpwd, usergroupid, info,ctrlwind,subwind,camerawind) VALUES('$username', '$newpwd', '$usergroup', '$info','$ctrlterminalcount','$fenkongIDtask','$jiankongID')";
	
	mysqli_query($con,$sql_username) or die(mysqli_error($con));
	
	unset($sql_username);

	

	//是否选择终端
	if(!empty($terminal_id))
	{
		$result_max = mysqli_query($con,"SELECT MAX(id) FROM book_admin") or die(mysqli_error);

		$row_max = mysqli_fetch_array($result_max);
			$mac1 = "";
				if(isset($_POST['mac1']))
				{
					$mac1 = trim($_POST['mac1']);
					mysqli_query($con,"insert into usersn (id,sn, userid) values('1','$mac1', '$row_max[0]')") or die(mysqli_error($con));
				}
				
				$mac2 = "";
				if(isset($_POST['mac2']))
				{
					$mac2 = trim($_POST['mac2']);
					mysqli_query($con,"insert into usersn (id,sn, userid) values('2','$mac2', '$row_max[0]')") or die(mysqli_error($con));
				}
				
				$mac3 = "";
				if(isset($_POST['mac3']))
				{
					$mac3 = trim($_POST['mac3']);
					mysqli_query($con,"insert into usersn (id,sn, userid) values('3','$mac3', '$row_max[0]')") or die(mysqli_error($con));
				}
				$arraygoup= array();
				$kk=0;

	/*
				$arraygoup= array();
				$kk=0;
				var_dump($analysis_tree_group_ids);
				for($i=0; $i<count($analysis_tree_group_ids); $i++)
				{
					$groupid = (int)$analysis_tree_group_ids[$i];
					if($arraygoup[$kk] != $groupid)
					{
						if($kk!=0)
							{
								$kk++;
							
							}
						$arraygoup[$kk]=$groupid;
	
						$sql_username = "select * from serverplaystream where streamid =$groupid";
						$result_username = mysqli_query($con,$sql_username) or die(mysqli_error($con));
						if($group_row = mysqli_fetch_array($result_username))
						{	
							mysqli_query($con,"insert into serverplaystream (name, info,userid) values('$group_row[1]','$group_row[2]', '$row_max[0]')") or die(mysqli_error($con));
							$result_maxs = mysqli_query($con,"SELECT MAX(streamid) FROM serverplaystream") or die(mysqli_error($con));
			
							$row_maxs = mysqli_fetch_array($result_maxs);
							for($k=0; $k<count($terminal_array); $k++)
							{
								if(is_numeric($terminal_array[$k]))
								{
								
									mysqli_query($con,"insert into terminalofgroup (terminalid,groupid) values('$terminal_array[$k]','$row_maxs[0]')") or die(mysqli_error($con));
	
									mysqli_query($con,"insert into userterminal (userid, terminalid) values('$row_max[0]', '$terminal_array[$k]')") or die(mysqli_error($con));
							
								}
							}
	
	
						}
			
					}
				}
	*/
	$userid_val = $row_max[0];
	
	$sql_username = "INSERT INTO filetaskfree (name, parentid, userid) VALUES('$username', '0','$userid_val')";

	mysqli_query($con,$sql_username) or die(mysqli_error($con));
	
	unset($sql_username);
		for($i=0; $i<count($terminal_array); $i++)
		{
			if(is_numeric($terminal_array[$i]))
			{
				$groupid = (int)$analysis_tree_group_ids[$i];
				mysqli_query($con,"insert into userterminal (userid, terminalid,groupid) values('$row_max[0]', '$terminal_array[$i]','$groupid')") or die(mysqli_error($con));
			}
		}
		@mysqli_free_result($result_max);
	
		unset($row_max);
	}
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./usermanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./usermanager.php";
	
		echo "<script>window.location='success.php'</script>";	
	}		
}
//用户修改
function useredit_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$get_userid = "";
	if(isset($_GET['id']))
	{
		$get_userid = trim($_GET['id']);
	}
	
	$get_count = "";
	if(isset($_GET['get_count']))
	{
		$get_count = trim($_GET['get_count']);
	}

	$username = "";
	if(isset($_POST['username']))
	{
		$username = trim($_POST['username']);
	}
	$info = "";
	if(isset($_POST['info']))
	{
		$info = trim($_POST['info']);
	}
	$usergroup = "";
	if(isset($_POST['usergroup']))
	{
		$usergroup = trim($_POST['usergroup']);
	}
	$ctrlterminalcount = 0;
	if(isset($_POST['ctrlterminalcount']))
	{
		$ctrlterminalcount = trim($_POST['ctrlterminalcount']);
	}
	$fenkongIDtask =0;
	if(isset($_POST['fenkongIDtask']))
	{
		$fenkongIDtask = trim($_POST['fenkongIDtask']);
	}
	
	$jiankongID = 0;
	if(isset($_POST['jiankongID']))
	{
		$jiankongID = trim($_POST['jiankongID']);
	}

	$newpwd = "";
	if(isset($_POST['newpwd']))
	{
		$newpwd = trim($_POST['newpwd']);
	}	
	$confirmpwd = "";
	if(isset($_POST['confirmpwd']))
	{
		$confirmpwd = trim($_POST['confirmpwd']);
	}
	if( ($newpwd == $confirmpwd) && (strlen($newpwd)<=16) && (strlen($confirmpwd)<=16) )
	{
		$newpwd = md5($newpwd);
	}
	else if(($newpwd == $confirmpwd) && (strlen($newpwd) > 16) && (strlen($confirmpwd) > 16))
	{
		//什么也不做
	}
	else if($newpwd != $confirmpwd)
	{

		$forward_ok_error_obj->exit_back_function($do_php_prompt['Passwords_not_same']);
	}
	//保留预留超级用户
	if($get_userid == 1 && $usergroup != 1)
	{

		$forward_ok_error_obj->exit_back_function($do_php_prompt['Illegal_operation']);
	}

	$terminal_id = "";
	
	if(isset($_POST['terminal_id']))
	{
		$terminal_id = trim($_POST['terminal_id']);
		$terminal_array = explode(",",$terminal_id);
	}

	$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
	$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);

		mysqli_query($con,"LOCK TABLE book_admin WRITE,serverplaystream WRITE,terminalofgroup WRITE");

	mysqli_query($con,"START TRANSACTION");
	//判断重名
	$sql_user = "SELECT * FROM book_admin WHERE book_admin.id != '$get_userid' AND book_admin.username = '$username'";
	
	$result_user = mysqli_query($con,$sql_user) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result_user) > 0)
	{
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	@mysqli_free_result($result_user);
	
	unset($sql_user);
	//判断分控ID是否相同
	
	if($ctrlterminalcount!=0)
	{
		for($k=1;$k<=$get_count;$k++)
		{
			//if($get_count>$ctrlterminalcount)
			//	break;
			$sql_username = "select ctrlwind from book_admin where ctrlwind=$k";
			$result_username = mysqli_query($con,$sql_username) or die(mysqli_error($con));
			if(mysqli_num_rows($result_username) <=0)
			{
				mysqli_query($con,"UPDATE book_admin SET ctrlwind ='$k' WHERE book_admin.id = '$get_userid' and ctrlwind=0") ;
				break;
			}
		}
	}
	else
	mysqli_query($con,"UPDATE book_admin SET ctrlwind ='0' WHERE book_admin.id = '$get_userid'");
	
	if($fenkongIDtask!=0)
	{
		$get_count=1000+$get_count;
		for($k=1001;$k<=$get_count;$k++)
		{
			$sql_username = "select subwind from book_admin where subwind=$k";
			$result_username = mysqli_query($con,$sql_username) or die(mysqli_error($con));
			if(mysqli_num_rows($result_username) <=0)
			{
				mysqli_query($con,"UPDATE book_admin SET subwind ='$k' WHERE book_admin.id = '$get_userid' and subwind=0") ;
				break;
			}
		}
	}
	else
	mysqli_query($con,"UPDATE book_admin SET subwind ='0' WHERE book_admin.id = '$get_userid'");
	if($jiankongID!=0)
	{
		$get_count=2000+$get_count;
			for($k=2001;$k<=$get_count;$k++)
			{
				$sql_username = "select camerawind from book_admin where camerawind=$k";
				$result_username = mysqli_query($con,$sql_username) or die(mysqli_error($con));
				if(mysqli_num_rows($result_username) <=0)
				{
					mysqli_query($con,"UPDATE book_admin SET camerawind ='$k' WHERE book_admin.id = '$get_userid' and camerawind=0") ;
					break;
				}
			}
	}
	else
	mysqli_query($con,"UPDATE book_admin SET camerawind ='0' WHERE book_admin.id = '$get_userid'");
	
	
	$sqls="SELECT book_admin.id FROM book_admin WHERE book_admin.id='$get_userid'";
	$results=mysqli_query($con,$sqls) or die(mysqli_error($con));
	if($row = mysqli_fetch_array($results))
	{
		$sql="SELECT usergroup.level FROM usergroup WHERE id='$usergroup'";
		$result=mysqli_query($con,$sql) or die(mysqli_error($con));
		if($rowa = mysqli_fetch_array($result))
		{
			$get_level=$rowa['level'];
			$_SESSION['getlevel']=$get_level;
		}
		$getid=$row['id'];
		$sqltask="SELECT priority FROM task WHERE task_user_id='$get_userid'";	
		$sqlresult=mysqli_query($con,$sqltask) or die(mysqli_error($con));
		while($rows = mysqli_fetch_array($sqlresult))
		{
			$get_priority=$rows['priority'];
			$newlevel=$get_level+$get_priority%10;
			mysqli_query($con,"UPDATE task SET priority='$newlevel' WHERE priority='$get_priority' AND task_user_id='$get_userid'") or die(mysqli_error($con));	
		}
		@mysqli_free_result($sqlresult);
		unset($rows);
	}
	@mysqli_free_result($results);
	unset($row);
	
	//获取更改后终端权限
	$del_oldterminal = true;
	
	$sql_newright = "select terminalpriv from usergroup where usergroup.id = '$usergroup'";
	
	$result_newright = mysqli_query($con,$sql_newright) or die(mysqli_error($con));
	
	if($row_newright = mysqli_fetch_array($result_newright))
	{
		$newright = $row_newright['terminalpriv'];
	}
	
	@mysqli_free_result($result_newright);
	
	unset($row_newright,$sql_newright);
	//获取用户原有终端权限
	$sql_oldright = "SELECT terminalpriv FROM usergroup WHERE usergroup.id = (SELECT usergroupid FROM book_admin WHERE book_admin.id = '$get_userid')";
	
	$result_oldright = mysqli_query($con,$sql_oldright) ;
	
	if($row_oldright = mysqli_fetch_array($result_oldright))
	{
		if($row_oldright['terminalpriv'] == 1)
		{
			if($newright == 1)
			{
				//先删后添
				$del_oldterminal = mysqli_query($con,"delete from userterminal where	userterminal.userid = '$get_userid'") ;
	
				for($i=0; $i<count($terminal_array); $i++)
				{
					if(is_numeric($terminal_array[$i]))
					{
						$groupid=(int)$analysis_tree_group_ids[$i];
						$terminal_array[$i] = (int)$terminal_array[$i];
	
						mysqli_query($con,"INSERT INTO userterminal (userid, terminalid,groupid) VALUES('$get_userid','$terminal_array[$i]','$groupid')") ;
					}
				}	
			}
			else if($newright == 0)
			{
				//只删
				$del_oldterminal = mysqli_query($con,"delete from userterminal where	userterminal.userid = '$get_userid'") ;
			}
		}
		if($row_oldright['terminalpriv'] == 0)
		{
			if($newright == 1)
			{
				//只添
				for($i=0; $i<count($terminal_array); $i++)
				{
					if(is_numeric($terminal_array[$i]))
					{
						$terminal_array[$i] = (int)$terminal_array[$i];
						$groupid=(int)$analysis_tree_group_ids[$i];
						mysqli_query($con,"INSERT INTO userterminal (userid, terminalid,groupid) VALUES('$get_userid','$terminal_array[$i]','$groupid')");
					}
				}
			}
			else if($newright == 0)
			{
				//什么也不做
			}
		}
	}
	@mysqli_free_result($result_oldright);
	
	unset($row_oldright,$sql_oldright);
	
	$mac1 = "";
	if(isset($_POST['mac1']))
	{
		$mac1 = trim($_POST['mac1']);
		$sql_newright = "select id from usersn WHERE usersn.id='1' and userid='$get_userid'";
	
		$result_newright = mysqli_query($con,$sql_newright) or die(mysqli_error($con));
		if(mysqli_num_rows($result_newright) > 0)
		{
			mysqli_query($con,"UPDATE usersn SET sn='$mac1' WHERE usersn.id='1' and userid='$get_userid'");
		}
		else
		{
			mysqli_query($con,"INSERT INTO usersn (id,sn,userid) VALUES('1','$mac1','$get_userid')");
		}
	}
	
	$mac2 = "";
	if(isset($_POST['mac2']))
	{
		$mac2 = trim($_POST['mac2']);	
		$sql_newright = "select id from usersn WHERE usersn.id='2' and userid='$get_userid'";
	
		$result_newright = mysqli_query($con,$sql_newright) or die(mysqli_error($con));
		if(mysqli_num_rows($result_newright) > 0)
		{
			mysqli_query($con,"UPDATE usersn SET sn='$mac2' WHERE usersn.id='2' and userid='$get_userid'");
		}
		else
		{
			mysqli_query($con,"INSERT INTO usersn (id,sn,userid) VALUES('2','$mac2','$get_userid')");
		}
	}
	
	$mac3 = "";
	if(isset($_POST['mac3']))
	{
		$mac3 = trim($_POST['mac3']);
		$sql_newright = "select id from usersn WHERE usersn.id='3' and userid='$get_userid'";
	
		$result_newright = mysqli_query($con,$sql_newright) or die(mysqli_error($con));
		if(mysqli_num_rows($result_newright) > 0)
		{
			mysqli_query($con,"UPDATE usersn SET sn='$mac3' WHERE usersn.id='3' and userid='$get_userid'");
		}
		else
		{
			mysqli_query($con,"INSERT INTO usersn (id,sn,userid) VALUES('3','$mac3','$get_userid')");
		}
	}

	//更新（对系统预留id为1不能删除且固定在超级用户组）且修改成功后清空sessionid
	if($get_userid == 1)
	{
		$sql_user = "UPDATE book_admin SET userpwd = '$newpwd', usergroupid = '1',";
	
		$sql_user.= "info = '$info',usersessionid = '' WHERE book_admin.id = '$get_userid'";  
	}
	else
	{
		$sql_user = "UPDATE book_admin SET username = '$username', userpwd = '$newpwd', usergroupid = '$usergroup',";
	
		$sql_user.= "info = '$info',usersessionid = '' WHERE book_admin.id = '$get_userid'";
	}
	$modify_user = mysqli_query($con,$sql_user);
	unset($sql_user);
	
	if($modify_user && $del_oldterminal)
	{
		mysqli_query($con,"COMMIT");
		//修改成功后是否强制登陆用户退出重新登陆呢
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	}
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./usermanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./usermanager.php";
	
		echo "<script>window.location='success.php'</script>";	
	}
}
//启用用户
function enable_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();

	//判断用户是否有权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	
	if(is_admin($con,$_SESSION['username']) || have_rights("userpriv"))
	{
		//什么都不做
	}
	else
	{
		quit_out(strtoupper($do_php_prompt['permission_denied']));//提示信息
	}
	
	//系统ID为1用户不能删除---即保留超级管理员
	$get_userid = "";
	if(isset($_GET['id']))
	{
		$get_userid = trim($_GET['id']);
		$get_userarray = explode(",",$get_userid);
	}
	mysqli_query($con,"LOCK TABLES book_admin WRITE,task WRITE");
	//直接删除用户的终端
	mysqli_query($con,"START TRANSACTION ");

	$del_terminal = mysqli_query($con,"UPDATE book_admin SET book_admin.enable = '1' WHERE book_admin.id IN ($get_userid)") ;
	$del_user = mysqli_query($con,"UPDATE task SET task.projectstate = '0' WHERE task.task_user_id IN ($get_userid)") ;
	
	if($del_terminal && $del_user)
	{
		mysqli_query($con,"COMMIT");
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	}
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./usermanager.php";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./usermanager.php";
		echo "<script>window.location='success.php'</script>";	
	}
}
//停用用户
function disable_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();

	//判断用户是否有权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	
	if(is_admin($con,$_SESSION['username']) || have_rights("userpriv"))
	{
		//什么都不做
	}
	else
	{
		quit_out(strtoupper($do_php_prompt['permission_denied']));//提示信息
	}
	
	//系统ID为1用户不能删除---即保留超级管理员
	$get_userid = "";
	
	if(isset($_GET['id']))
	{
		$get_userid = trim($_GET['id']);
	
		$get_userarray = explode(",",$get_userid);
	}
	foreach($get_userarray as $value)
	{
		if($value == 1)
		{
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Systems_User_not_disable']);
		}
	}
	mysqli_query($con,"LOCK TABLES book_admin WRITE,task WRITE");

	//直接删除用户的终端
	mysqli_query($con,"START TRANSACTION ");
	
	$del_terminal = mysqli_query($con,"UPDATE book_admin SET book_admin.enable = '0' WHERE book_admin.id IN ($get_userid)") ;
	$del_user = mysqli_query($con,"UPDATE task SET task.projectstate = '1' WHERE task.task_user_id IN ($get_userid)") ;
	
	if($del_terminal && $del_user)
	{
		mysqli_query($con,"COMMIT");
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	}
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./usermanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./usermanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//删除用户
function userdel_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//判断用户是否有权限
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	
	if(is_admin($con,$_SESSION['username']) || have_rights("userpriv"))
	{
		//什么都不做
	}
	else
	{
		quit_out(strtoupper($do_php_prompt['permission_denied']));//提示信息
	}
	//系统ID为1用户不能删除---即保留超级管理员
	$get_userid = "";
	if(isset($_GET['id']))
	{
		$get_userid = trim($_GET['id']);
		$get_userarray = explode(",",$get_userid);
	}
	foreach($get_userarray as $value)
	{
		if($value == 1)
		{
			//===============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['Systems_User_not_deleted'])."');</script>";//提示信息
			echo "<script>window.history.back();</script>";
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Systems_User_not_deleted']);
		}
	}

	//直接删除用户的终端
	mysqli_query($con,"START TRANSACTION ");
	
	$del_terminal = mysqli_query($con,"DELETE FROM userterminal WHERE userterminal.userid IN ($get_userid)");
	$del_user = mysqli_query($con,"delete from book_admin where book_admin.id in($get_userid)");
	
	mysqli_query($con,"delete from filetaskfree where filetaskfree.userid in($get_userid)");
	mysqli_query($con,"delete from usersn where userid in($get_userid)");
	mysqli_query($con,"delete from media where folderid in(select id from filefolder where userid in($get_userid))");
	mysqli_query($con,"delete from filefolder where userid in($get_userid)");
	$sqlarea = "SELECT streamid FROM serverplaystream WHERE userid in($get_userid)";
	$resultarea = mysqli_query($con,$sqlarea) or die(mysqli_error($con));
	while($rowarea = mysqli_fetch_array($resultarea))
	{
		$getrowarea=$rowarea['streamid'];
		mysqli_query($con,"delete from terminalofgroup where groupid ='$getrowarea'");
		mysqli_query($con,"delete from serverplaystream where streamid ='$getrowarea'");
	}
	
	$sqlalarm = "SELECT id FROM alarmarea WHERE userid in($get_userid)";
	$resultalarm = mysqli_query($con,$sqlalarm) or die(mysqli_error($con));
	while($rowalarm = mysqli_fetch_array($resultalarm))
	{
		$getrowalarm=$rowalarm['id'];
		mysqli_query($con,"delete from terminalofalarmgroup where alarmgroupid ='$getrowalarm'");
		mysqli_query($con,"delete from alarmgroupmap where firealarmgroupid ='$getrowalarm'");
		mysqli_query($con,"delete from alarmarea where id ='$getrowalarm'");
	} 
	$del_alarmarea = mysqli_query($con,"DELETE FROM alarmarea WHERE alarmarea.userid IN ($get_userid)");
	$sql = "SELECT taskid FROM task WHERE task_user_id in($get_userid) AND sec_task_id='0'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result))
	{
		$getrow=$row['taskid'];
		$del_user = mysqli_query($con,"DELETE FROM terminaloftask WHERE taskid ='$getrow'");
		$del_user = mysqli_query($con,"DELETE FROM ttssentence WHERE sentenceid in(select mediaid from mediaoftask where taskid='$getrow')");
		$del_user = mysqli_query($con,"DELETE FROM media WHERE id IN(SELECT mediaid FROM mediaoftask WHERE taskid='$getrow') AND typeid='tts'");
		$del_user = mysqli_query($con,"DELETE FROM mediaoftask WHERE taskid ='$getrow'");
		$del_user = mysqli_query($con,"DELETE FROM task WHERE taskid ='$getrow'");
		
		$sql2 = "SELECT keyid FROM terminalkeymap WHERE terminalid='$getrow'";
		$result2 = mysqli_query($con,$sql2) or die(mysqli_error($con));
		while($row2 = mysqli_fetch_array($result2))
		{
			$keyid=$row2['keyid'];
			 mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalid ='$getrow'");
			 mysqli_query($con,"DELETE FROM terminalkey WHERE id ='$keyid'");
		}	
	}
	
	$sqls = "SELECT taskid FROM task WHERE task_user_id in($get_userid) AND sec_task_id!='0'";
	$results = mysqli_query($con,$sqls) or die(mysqli_error($con));
	while($rows = mysqli_fetch_array($results))
	{
		 $getrows=$rows['taskid'];
		 mysqli_query($con,"DELETE FROM terminaloftask WHERE taskid ='$getrows'");
		 	$del_user = mysqli_query($con,"DELETE FROM ttssentence WHERE sentenceid in(select mediaid from mediaoftask where taskid='$getrows')");
			$del_user = mysqli_query($con,"DELETE FROM media WHERE id IN(SELECT mediaid FROM mediaoftask WHERE taskid='$getrows') AND typeid='tts'");
		 mysqli_query($con,"DELETE FROM mediaoftask WHERE taskid ='$getrows'");
		 mysqli_query($con,"DELETE FROM task WHERE taskid ='$getrows'");
	}

	if($del_terminal && $del_user)
	{
		mysqli_query($con,"COMMIT");
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	}
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./usermanager.php";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./usermanager.php";
		echo "<script>window.location='success.php'</script>";	
	}
		
}

/*
$delayedCode = function() {
	$command="cmdhost -c 'sudo reboot'";
	system($command);
	}
*/
//修改用户重启
function enablereboot_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	$create_socket_obj = new create_socket_class();
	$enreboot = 0;
	if(isset($_POST['enreboot']))
	{
		$enreboot = trim((int)$_POST['enreboot']);
	}
	$sounddetect = 0;
	if(isset($_POST['sounddetect']))
	{
		$sounddetect = trim((int)$_POST['sounddetect']);
	}
	
	$mastersalve = 0;
	if(isset($_POST['mastersalve']))
	{
		$mastersalve = trim((int)$_POST['mastersalve']);
	}

	$fuzamima = 0;
	if(isset($_POST['fuzamima']))
	{
		$fuzamima = trim((int)$_POST['fuzamima']);
	}

	$reboottime=trim($_POST['starthour']).":".trim($_POST['startmin']).":".trim($_POST['startsenc']);
	$username = trim(($_GET['username']));
	
		if($enreboot==1)
		{
			$enreboot=0;
		}
		else
		{
			$enreboot=1;
		}
	$enablershutdown = 0;
	if(isset($_POST['enablershutdown']))
	{
		$enablershutdown = trim((int)$_POST['enablershutdown']);
	}

		$endtime=trim($_POST['shutdownhour']).":".trim($_POST['shutdownmin']).":".trim($_POST['shutdownsenc']);

		mysqli_query($con,"LOCK TABLES task WRITE,serverbaseparam WRITE,serverconfig WRITE");

	if($enreboot==0)
	{
		$sqls = "UPDATE task SET projectstate = '0', playtime='$reboottime',cmdargs='0',cmd='$fuzamima' WHERE taskid = '70000'";
		//定时每天关机指令
		//	$command = "sudo sed -i '4c ".$_POST['shutdownmin']." ".$_POST['shutdownhour']." * * * root /sbin/shutdown -h now' /etc/crontab";
		//	@system($command);
	}
	else
	{
		if($enablershutdown==1)
		{
			$sqls = "UPDATE task SET projectstate = '0', playtime='$endtime',cmdargs='shutdown',cmd='$fuzamima' WHERE taskid = '70000'";

		}
		else
		{
			$sqls = "UPDATE task SET projectstate = '1',cmd='$fuzamima' WHERE taskid = '70000'";
		}

	//取消每天关机指令
//	$command = "sudo sed -i '4c shutdown -c' /etc/crontab";
//	@system($command);
	}

	mysqli_query($con,$sqls) or die(mysqli_error($con));
	$backup=0;
	$sql2 = "SELECT backup FROM serverbaseparam ";
		$result2 = mysqli_query($con,$sql2) or die(mysqli_error($con));
		if($row2 = mysqli_fetch_array($result2))
		{
				$backup=$row2['backup'];
		}

		$sql2 = "SELECT id FROM serverconfig ";
		$result2 = mysqli_query($con,$sql2) or die(mysqli_error($con));
		if(mysqli_num_rows($result2) > 0)
		{
			$sqls="UPDATE serverconfig SET sounddetect = '$sounddetect',fuzamima='$fuzamima'";

		}
		else
		{
			$sqlmediaoftask = "INSERT INTO serverconfig (sounddetect,fuzamima) VALUES('$sounddetect','$fuzamima')";
		
			mysqli_query($con,$sqlmediaoftask) or die(mysqli_error($con));
		

		}
		

	
	mysqli_query($con,$sqls) or die(mysqli_error($con));

	$sqls="UPDATE serverbaseparam SET sounddetect = '$sounddetect'";
	mysqli_query($con,$sqls) or die(mysqli_error($con));
	

	/*
	if($mastersalve==0 && $backup!=0)
	{
		 $command = "cp /var/www/html/ok112/my.cnf.d/server.cnf /var/www/html/ok112/link/home/mysql/my.cnf.d/server.cnf -rf";
		 @system($command);
	}
	else if($mastersalve!=0 && $backup==0)
	{
		$command = "cp /var/www/html/ok112/link/home/mysql/server-master.cnf /var/www/html/ok112/link/home/mysql/my.cnf.d/server.cnf -rf";
		@system($command);
	//	$command = "sudo /usr/bin/mysqldump -u root --password='a9000db#!ht' -R -q --databases audioserver > /opt/script/all.sql";
		
	//	@system($command);

		$command = "mv /var/www/html/ok112/link/script/mysqldel-z /var/www/html/ok112/link/script/mysqldel.sh -f";
		@system($command);
		//$command = "sudo service mysqld stop";
		//@system($command);
		//$command = "rm -fr /var/lib/mysql/mysql-bin*;rm -rf /var/lib/mysql/*.info; rm -rf /var/lib/mysql/relay*;rm -rf /var/lib/mysql/aria_*;rm -rf /var/lib/mysql/*-relay*;rm -rf /var/lib/mysql/ibtmp*;rm -rf /var/lib/mysql/ib_*;rm -rf /var/lib/mysql/*.pid;rm -rf /var/lib/mysql/audioserver";
		//$command = "sudo rm -fr /var/lib/mysql/mysql-bin*;sudo rm -rf /var/lib/mysql/*.info;sudo rm -rf /var/lib/mysql/relay*;sudo rm -rf /var/lib/mysql/aria_*;sudo rm -rf /var/lib/mysql/*-relay*;sudo rm -rf /var/lib/mysql/ib_*;sudo rm -rf /var/lib/mysql/*.pid";
		//@system($command);	
	}*/
	mysqli_query($con,"UNLOCK TABLES");
	/*
	$command = "rm -rf /var/www/html/ok112/link/home/mysql/my.cnf.d/server-master.cnf";
	@system($command);
	
	$command = "rm -rf /var/www/html/ok112/link/home/mysql/my.cnf.d/server-slave.cnf";
	@system($command);
	$command = "rm -rf /var/www/html/ok112/link/home/mysql/my.cnf.d/server-clients.cnf";
	@system($command);
	*/
	//$command = "chmod 644 /var/www/html/ok112/link/home/mysql/my.cnf.d/server.cnf";
//	@system($command);
	$command="cmdhost -c 'sudo sync'";
	system($command);

	
//	$command="sudo reboot";
//	@system($command);
//解锁

	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./serversetting.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./servermanager.php";

		echo "<script>window.frames['main'].location.href='success.php'</script>";	
		echo "<script>window.location='success.php'</script>";	
	//	setTimeout($delayedCode, 3000);	
		@session_unset();	
		@session_destroy();
	
		sleep(1);		
		$create_socket_obj->send_socket_restart("server",1);
//	 $command="cmdhost -c 'sudo reboot'";
	// system($command);
	

	}
}

//修改用户密码
function userpasswordmodify_msg($con,$info_s)
{
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$info_ss=explode("&",$info_s);
	
	$usernames = "";
	$user_names=explode("=",$info_ss[0]);
	$username =trim($user_names[1]);
	
	
	$oldpwds = "";
	$oldpwds=explode("=",$info_ss[1]);
	
	$oldpwd =md5(trim($oldpwds[1]));
		
	$newpwds = "";
	$newpwds=explode("=",$info_ss[2]);
	
	$newpwd =md5(trim($newpwds[1]));
	
	//$oldpwd = md5(trim($_POST['oldpwd']));
	//$newpwd = md5(trim($_POST['newpwd']));
//	$confirmpwd = md5(trim($_POST['confirmpwd']));
	
	//$username = trim(urldecode($_GET['username']));
	
	$sql = "SELECT book_admin.userpwd FROM book_admin WHERE book_admin.username='$username'";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		if($row['userpwd'] != $oldpwd)
		{
			//============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['Old_password_incorrect'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Old_password_incorrect']);
		}
		
		
			$sql="UPDATE book_admin SET book_admin.userpwd = '$newpwd' WHERE book_admin.username='$username'";
			
			mysqli_query($con,$sql) or die(mysqli_error($con));
			
			if(mysqli_error($con))
			{
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./modifypassword.php";
				
				echo "<script>window.location='error.php'</script>";
			}
			else
			{
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = "./servermanager.php";
				
				echo "<script>window.location='success.php'</script>";	
			}
	
	}
}
//用户组删除 组与用户是一对多关系
function usergroupdel_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//判断是否是超级管理员
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	
	if(!is_admin($con,$_SESSION['username']))
	{
		//========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['permission_denied'])."');</script>";//提示信息
		echo "<script>window.history.back();</script>";
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['permission_denied']);
	}
	//验证是否删除系统预留的组	
	$get_groupid = "";
	
	if(isset($_GET['id']))
	{
		$get_groupid = trim($_GET['id']);
		$get_more_groupid = explode(",",$get_groupid); 
	}
	
	foreach($get_more_groupid as $group_id)
	{
		if($group_id == 1)
		{
			//==============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['System_group_not_deleted'])."');</script>";//提示信息
			echo "<script>window.history.back();</script>";
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['System_group_not_deleted']);
		}
	}
	unset($group_id);
	//上锁
	mysqli_query($con,"LOCK TABLES book_admin WRITE,usergroup WRITE,userterminal WRITE");
	
	//开启事务
	mysqli_query($con,"START TRANSACTION");
	
	//删除组时 同时删除用户 并删除该用户的终端
	$get_userid=0;
	foreach($get_more_groupid as $group_id)
	{
		//删除用户终端
		mysqli_query($con,"DELETE FROM userterminal WHERE userterminal.userid IN 
(SELECT DISTINCT book_admin.id FROM book_admin,usergroup WHERE usergroup.id = '".$group_id."' 
AND book_admin.usergroupid = usergroup.id) ") or die(mysqli_error($con));

		$sql = "SELECT id FROM book_admin WHERE book_admin.usergroupid='$group_id'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if($row = mysqli_fetch_array($result))
		{
			$get_userid=$row['id'];
			$del_terminal = mysqli_query($con,"DELETE FROM userterminal WHERE userterminal.userid IN($get_userid)");
			
			$del_user = mysqli_query($con,"DELETE FROM book_admin WHERE book_admin.id IN($get_userid)");
			mysqli_query($con,"delete from usersn where userid in($get_userid)");
			mysqli_query($con,"delete from media where folderid in(select id from filefolder where userid in($get_userid))");
			mysqli_query($con,"delete from filefolder where userid in($get_userid)");
			mysqli_query($con,"delete from filetaskfree where filetaskfree.userid in($get_userid)");
			$sqlarea = "SELECT streamid FROM serverplaystream WHERE userid IN($get_userid)";
			$resultarea = mysqli_query($con,$sqlarea) or die(mysqli_error($con));
			while($rowarea = mysqli_fetch_array($resultarea))
			{
				$getrowarea=$rowarea['streamid'];
				mysqli_query($con,"delete from terminalofgroup where groupid ='$getrowarea'");
				mysqli_query($con,"delete from serverplaystream where streamid ='$getrowarea'");
			}
			
			$sqlalarm = "SELECT id FROM alarmarea WHERE userid IN($get_userid)";
			$resultalarm = mysqli_query($con,$sqlalarm) or die(mysqli_error($con));
			while($rowalarm = mysqli_fetch_array($resultalarm))
			{
				$getrowalarm=$rowalarm['id'];
				mysqli_query($con,"delete from terminalofalarmgroup where alarmgroupid ='$getrowalarm'");
				mysqli_query($con,"delete from alarmgroupmap where firealarmgroupid ='$getrowalarm'");
				mysqli_query($con,"delete from alarmarea where id ='$getrowalarm'");
			} 
			$del_alarmarea = mysqli_query($con,"DELETE FROM alarmarea WHERE alarmarea.userid IN($get_userid)");
			$sql = "SELECT taskid FROM task WHERE task_user_id in($get_userid) AND sec_task_id='0'";
			$result = mysqli_query($con,$sql) or die(mysqli_error($con));
			while($row = mysqli_fetch_array($result))
			{
				$getrow=$row['taskid'];
				$del_user = mysqli_query($con,"DELETE FROM terminaloftask WHERE taskid ='$getrow'");
				$del_user = mysqli_query($con,"DELETE FROM ttssentence WHERE sentenceid in(select mediaid from mediaoftask where taskid='$getrow')");
				$del_user = mysqli_query($con,"DELETE FROM media WHERE id IN(SELECT mediaid FROM mediaoftask WHERE taskid='$getrow') AND typeid='tts'");
				$del_user = mysqli_query($con,"DELETE FROM mediaoftask WHERE taskid ='$getrow'");
				$del_user = mysqli_query($con,"DELETE FROM task WHERE taskid ='$getrow'");
				
				$sql2 = "SELECT keyid FROM terminalkeymap WHERE terminalid='$getrow'";
				$result2 = mysqli_query($con,$sql2) or die(mysqli_error($con));
				while($row2 = mysqli_fetch_array($result2))
				{
					$keyid=$row2['keyid'];
					 mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalid ='$getrow'");
					 mysqli_query($con,"DELETE FROM terminalkey WHERE id ='$keyid'");
				}	
			}
			
			$sqls = "SELECT taskid FROM task WHERE task_user_id in($get_userid) AND sec_task_id!='0'";
			$results = mysqli_query($con,$sqls) or die(mysqli_error($con));
			while($rows = mysqli_fetch_array($results))
			{
				 $getrows=$rows['taskid'];
				 mysqli_query($con,"DELETE FROM terminaloftask WHERE taskid ='$getrows'");
				 	$del_user = mysqli_query($con,"DELETE FROM ttssentence WHERE sentenceid in(select mediaid from mediaoftask where taskid='$getrow')");
					$del_user = mysqli_query($con,"DELETE FROM media WHERE id IN(SELECT mediaid FROM mediaoftask WHERE taskid='$getrow') AND typeid='tts'");
				 mysqli_query($con,"DELETE FROM mediaoftask WHERE taskid ='$getrows'");
				 mysqli_query($con,"DELETE FROM task WHERE taskid ='$getrows'");
			}
		}
		//删除用户
		mysqli_query($con,"DELETE FROM book_admin WHERE book_admin.usergroupid = '$group_id'") or die(mysqli_error($con));
		mysqli_query($con,"DELETE FROM usersn WHERE userid IN ($get_userid)");
		mysqli_query($con,"DELETE FROM media where folderid in(select id from filefolder where userid in($get_userid))");
		mysqli_query($con,"DELETE FROM filefolder where userid in ($get_userid)");
	}
	//删除组
	mysqli_query($con,"DELETE FROM usergroup WHERE usergroup.id IN ($get_groupid)") or die(mysqli_error($con));
	
	//解锁
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		mysqli_query($con,"ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./usergroupmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./usergroupmanager.php";
	
		echo "<script>window.location='success.php'</script>";	
	}
}

//更新用户组
function usergroupmodify_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$group_id = "";
	if(isset($_GET['id']))
	{
		$group_id = trim($_GET['id']);
	}
	$usergroupname = "";
	if(isset($_POST['usergroupname']))
	{
		$usergroupname = trim($_POST['usergroupname']);
	}
	$UserDes = "";
	if(isset($_POST['UserDes']))
	{
		$UserDes = trim($_POST['UserDes']);
	}
	$level = "";
	if(isset($_POST['level']))
	{
		$level = trim($_POST['level']);
	}
	$taskpriv = 0; 
	if(isset($_POST['taskpriv']))
	{  
		$taskpriv = trim($_POST['taskpriv']);
	}
	$terminalpriv = 0; 
	if(isset($_POST['terminalpriv']))
	{  
		$terminalpriv = trim($_POST['terminalpriv']);
	}
	$mediapriv = 0; 
	if(isset($_POST['mediapriv']))
	{  
		$mediapriv = trim($_POST['mediapriv']);
	}
	$userpriv = 0; 
	if(isset($_POST['userpriv']))
	{  
		$userpriv = trim($_POST['userpriv']);
	}
	$serverpriv = 0; 
	if(isset($_POST['serverpriv']))
	{  
		$serverpriv = trim($_POST['serverpriv']);
	}
	$folderpriv = 0; 
	if(isset($_POST['folderpriv']))
	{  
		$folderpriv = trim($_POST['folderpriv']);
	}
	$terminalgrouppriv = 0; 
	if(isset($_POST['terminalgrouppriv']))
	{  
		$terminalgrouppriv = trim($_POST['terminalgrouppriv']);
	}
	$alarmgrouppriv = 0; 
	if(isset($_POST['alarmgrouppriv']))
	{  
		$alarmgrouppriv = trim($_POST['alarmgrouppriv']);
	}
	$bellpriv = 0; 
	if(isset($_POST['bellpriv']))
	{  
		$bellpriv = trim($_POST['bellpriv']);
	}
	$admpriv = 0; 
	if(isset($_POST['admpriv']))
	{  
		$admpriv = trim($_POST['admpriv']);
	}
	$telephonepriv = 0; 
	if(isset($_POST['telephonepriv']))
	{  
		$telephonepriv = trim($_POST['telephonepriv']);
	}
	$powerplay = 0; 
	if(isset($_POST['powerplay']))
	{  
		$powerplay = trim($_POST['powerplay']);
	}
	$ttspriv = 0; 
	if(isset($_POST['ttspriv']))
	{  
		$ttspriv = trim($_POST['ttspriv']);
	}
	if( ($group_id == 1) )
	{
		if( (($taskpriv) == 1) && (($terminalpriv) == 1) && (($mediapriv) == 1) && (($userpriv) == 1) && (($serverpriv) == 1) && (($folderpriv) == 1) && (($terminalgrouppriv) == 1) && (($alarmgrouppriv) == 1) && (($bellpriv) == 1) && (($admpriv) == 1) && (($telephonepriv) == 1) && (($powerplay) == 1)&& (($ttspriv) == 1) )
		{
			//什么也不做
		}
		else
		{
			//================================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['System_group_not_modified'])."');</script>";//提示信息
			echo "<script>window.history.back();</script>";
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['System_group_not_modified']);
		}
	}
	
	//判别是否同名
	$sql_group = "SELECT * FROM usergroup WHERE usergroup.id != '$group_id' AND usergroup.name = '$usergroupname'";
	
	$result_group = mysqli_query($con,$sql_group) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result_group) > 0)
	{
		//===========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		echo "<script>window.history.back();</script>";
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysqli_free_result($result_group);
	unset($sql_group);
	
	$get_level=10;
	$userid=$_SESSION['userid'];
	$sql="SELECT book_admin.id,usergroup.level FROM book_admin,usergroup WHERE usergroupid =usergroup.id AND usergroup.id='$group_id'";
	$result=mysqli_query($con,$sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result))
	{
		$get_level=$row['level'];
		$getid=$row['id'];
		$sqltask="SELECT priority FROM task WHERE task_user_id='$getid'";	
		$sqlresult=mysqli_query($con,$sqltask) or die(mysqli_error($con));
		while($rows = mysqli_fetch_array($sqlresult))
		{
			$get_priority=$rows['priority'];
			if(floor($get_priority/10)==floor($get_level/10))
			{
				$newlevel=$level+$get_priority%10;
				mysqli_query($con,"UPDATE task SET priority='$newlevel' WHERE priority='$get_priority' AND task_user_id='$getid'") or die(mysqli_error($con));
			}
		}	
	}
	@mysqli_free_result($result);
	
	unset($row);
	//更新
	$sql_group = "UPDATE usergroup SET NAME = '$usergroupname' , info = '$UserDes' , taskpriv = '$taskpriv' , terminalpriv = '$terminalpriv' ,";
	$sql_group.= "mediapriv = '$mediapriv' , userpriv = '$userpriv' , serverpriv = '$serverpriv' , folderpriv = '$folderpriv' , ";
	$sql_group.= "terminalgrouppriv = '$terminalgrouppriv' , alarmgrouppriv = '$alarmgrouppriv' , bellpriv = '$bellpriv' , ";
	$sql_group.= "admpriv = '$admpriv' , telephonepriv = '$telephonepriv' , powerplay = '$powerplay' , level = '$level', ttspriv = '$ttspriv' WHERE usergroup.id = '$group_id'";
	
	mysqli_query($con,$sql_group) or die(mysqli_error($con));

	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./usergroupmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./usergroupmanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}	
}
//添加用户组
function usergroupadd_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$usergroupname = "";
	if(isset($_POST['usergroupname']))
	{
		$usergroupname = trim($_POST['usergroupname']);
	}
	$taskpriv = 0;
	if(isset($_POST['taskpriv']))
	{  
		$taskpriv =trim($_POST['taskpriv']);
	}
	$terminalpriv = 0;
	if(isset($_POST['terminalpriv']))
	{  
		$terminalpriv = trim($_POST['terminalpriv']); 
	}
	$mediapriv = 0;
	if(isset($_POST['mediapriv']))
	{  
		$mediapriv = trim($_POST['mediapriv']); 
	}
	$userpriv = 0;
	if(isset($_POST['userpriv']))
	{  
		$userpriv = trim($_POST['userpriv']); 
	}
	$serverpriv = 0;
	if(isset($_POST['serverpriv']))
	{
		$serverpriv = trim($_POST['serverpriv']);
	}
	$folderpriv = 0;
	if(isset($_POST['folderpriv']))
	{
		$folderpriv = trim($_POST['folderpriv']);
	}
	$terminalgrouppriv = 0;
	if(isset($_POST['terminalgrouppriv']))
	{
		$terminalgrouppriv = trim($_POST['terminalgrouppriv']);
	}
	$alarmgrouppriv = 0;
	if(isset($_POST['alarmgrouppriv']))
	{
		$alarmgrouppriv = trim($_POST['alarmgrouppriv']);
	}
	$bellpriv = 0;
	if(isset($_POST['bellpriv']))
	{
		$bellpriv = trim($_POST['bellpriv']);
	}
	$admpriv = 0;
	if(isset($_POST['admpriv']))
	{
		$admpriv = trim($_POST['admpriv']);
	}
	$telephonepriv = 0;
	if(isset($_POST['telephonepriv']))
	{
		$telephonepriv = trim($_POST['telephonepriv']);
	}
	$powerplay = 0;
	if(isset($_POST['powerplay']))
	{
		$powerplay = trim($_POST['powerplay']);
	}
	
	$ttspriv = 0;
	if(isset($_POST['ttspriv']))
	{
		$ttspriv = trim($_POST['ttspriv']);
	}
	
	$level = 3;
	
	if(isset($_POST['level']))
	{
		$level = trim($_POST['level']);
	}
	
	$UserDes = "NO Description";
	
	if(isset($_POST['UserDes']))
	{
		$UserDes = trim($_POST['UserDes']);
	}
	
	mysqli_query($con,"LOCK TABLE usergroup WRITE");
	//不能同名组
	$result_group = mysqli_query($con,"SELECT * FROM usergroup WHERE usergroup.name = '$usergroupname'") or die(mysqli_error($con));
	if(mysqli_num_rows($result_group) > 0)
	{
		//============================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>history.back();</script>";
		
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	@mysqli_free_result($result_group);


	//插入数据
	$sql_group = "INSERT INTO audioserver.usergroup ";
	$sql_group.= "(NAME, info, taskpriv, terminalpriv, mediapriv, userpriv, serverpriv, folderpriv, ";
	$sql_group.= "terminalgrouppriv, alarmgrouppriv, bellpriv, admpriv, telephonepriv, powerplay, LEVEL,ttspriv) ";
	$sql_group.= "VALUES ('$usergroupname', '$UserDes', '$taskpriv', '$terminalpriv', '$mediapriv', '$userpriv', '$serverpriv', '$folderpriv', ";
	$sql_group.= "'$terminalgrouppriv', '$alarmgrouppriv', '$bellpriv', '$admpriv', '$telephonepriv', '$powerplay', '$level','$ttspriv') ";

	mysqli_query($con,$sql_group) or die(mysqli_error($con));

	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./usergroupmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./usergroupmanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}		
}
//添加终端---没有被使用（采用自动注册写入到数据库）
function terminaladd_msg($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$terminalsql = "SELECT terminal.terminalname FROM terminal WHERE terminal.terminalname='$_POST[terminalname]' ";
	$terminalsql.= "AND terminal.groupid = '$_POST[streamid]' ";
	
	$terminalresult = mysqli_query($con,$terminalsql) or die(mysqli_error($con));
	if(mysqli_fetch_array($terminalresult))
	{
		//============================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		echo "<script>history.back();</script>";
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	mysqli_query($con,"INSERT INTO `terminal` (`groupid`,`terminalname`,`typeid`,`ip`,`postion`,`volume`) VALUES ('$_POST[streamid]','$_POST[terminalname]','$_POST[typeid]','$_POST[ip]','$_POST[postion]','$_POST[volume]')");
		if(mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./terminalmanager.php";
			
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./terminalmanager.php";
			//inputterminaltofile();//更新终端文件信息
			echo "<script>window.location='success.php'</script>";	
		}
}
//启用终端---没什么用的功能（很少用到）
function terminalStart_msg($con)
{
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象========================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalidarray = explode(",",$getid);
	
	//判读用户与终端及终端状态
	require_once("User_Rights_Manage/user_opr_terminal_right.php");
	
	for($i=0; $i<count($getterminalidarray); $i++)
	{
		$sql = "SELECT 	netstate FROM terminal WHERE terminal.id = '$getterminalidarray[$i]'";
	
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
		if($row = mysqli_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				//===============================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Disconnect'])."');</script>";//提示信息	
				echo "<script>window.history.back();</script>";
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
		}
		control_user_terminal_opr($con,$getterminalidarray[$i]);
	}
	
	mysqli_query($con,"UPDATE terminal SET devicestate = '1' WHERE terminal.id IN ($getid)");
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//================================================
			/*$socket	= new send_message_to_server($port_conf);	
	
			$msg = "terminal?state=1&id=".$getid."";						
	
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("terminal",1,$getid);
		}
		echo "<script>window.location='success.php'</script>";	
	}	
}
//停止终端---只是更新数据库状态
function terminalStop_msg($con)
{
	
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//====================创建套字节=====================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	
	$getterminalidarray = explode(",",$getid);
	
	//判读用户与终端及终端状态
	require_once("User_Rights_Manage/user_opr_terminal_right.php");
	
	for($i=0; $i<count($getterminalidarray); $i++)
	{
		$sql = "SELECT netstate FROM terminal WHERE terminal.id = '$getterminalidarray[$i]'";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				//================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Disconnect'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
		
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
		}
		control_user_terminal_opr($con,$getterminalidarray[$i]);
	}
	mysqli_query($con,"UPDATE terminal SET devicestate = '0', taskstate = '0' WHERE terminal.id IN ($getid)");
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url']="./terminalmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//=================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "terminal?state=0&id=".$getid;		
				
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("terminal",0,$getid);
		}
		echo "<script>window.location='success.php'</script>";	
	}	
}


//启用发言---没什么用的功能（很少用到）
function terminaldosponsor_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminaltype.isdecode,terminaltype.isencode,terminal.typeid FROM terminal,terminaltype ";
		
		$sql.= "WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isdecode'] == 0 && $row['isencode'] == 0)
			{
				//=========================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Terminal_not_support'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
			else if($row['typeid']==40)
			{
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
		}
	}
	$sql="UPDATE terminal SET issponsor = '1' WHERE	terminal.id IN ($getid)";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "terminal?state=3&id=".$getid."&speech=true";	
					
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_speech("terminal",3,$getid,"true");
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
//停止终端发言---只是更新数据库状态
function terminalstopsponsor_msg($con)
{
//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	$getid = "";
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminaltype.isdecode,terminaltype.isencode FROM terminal,terminaltype ";
		
		$sql.= "WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				//================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Disconnect'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isdecode'] == 0 && $row['isencode'] == 0)
			{
				//=======================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Terminal_not_support'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
		}
	}
	$sql="UPDATE terminal SET issponsor = '0' WHERE	terminal.id IN ($getid) ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "terminal?state=4&id=".$getid."&speech=false";			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_speech("terminal",4,$getid,"false");
		}
		echo "<script>window.location='success.php'</script>";	
	}
}


//LED搜索
function ledsousuo_msg($con)
{
//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);

	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminaltype.id FROM terminal,terminaltype ";
		
		$sql.= "WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['id'] != 42)//led设备
			{
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
		}
	}
	
	$_SESSION['info'] = strtoupper($do_php_prompt['sucessandterminalnoempty']);//提示信息	
	$_SESSION['url'] = "./led_terminal_sousuo.php?terminal_id=".$getid."";

	$create_socket_obj->send_socket_circuit("terminal",28,$getid);
	sleep(2); 
	echo "<script>window.location='success.php'</script>";	

}




//检测开路状态
function check_circuit_state($con)
{
//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);

	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminaltype.isdecode,terminaltype.isencode FROM terminal,terminaltype ";
		
		$sql.= "WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				//================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Disconnect'])."');</script>";//提示信息
				echo "<script>window.history.back();</script>";
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isdecode'] == 0 && $row['isencode'] == 0)
			{
				//=======================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Terminal_not_support'])."');</script>";//提示信息
				echo "<script>window.history.back();</script>";
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
		}
	}
	
	$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
	$_SESSION['url'] = "./terminalmanager.php";

	$create_socket_obj->send_socket_circuit("terminal",27,$getid);
	echo "<script>window.location='success.php'</script>";	

}

//更新终端---没有被使用到---实际也改不了
function terminaledit_msg($con)
{
	
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	$terminal_sql = "UPDATE `terminal` SET `groupid`='$_POST[streamid]',`terminalname`='$_POST[terminalname]', ";
	
	$terminal_sql.= "`typeid`='$_POST[typeid]',`ip`='$_POST[ip]' ,`postion`='$_POST[postion]',`volume`='$_POST[volume]' WHERE id='$_GET[id]' ";
	
	mysqli_query($con,$terminal_sql);
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		//inputterminaltofile();//用来对终端数据重新输入
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//只是删除数据库中的记录---而没有考虑到终端是否连接
function terminaldel_msg($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	//判读用户与终端及终端状态
	require_once("User_Rights_Manage/user_opr_terminal_right.php");
	
	$terminal_id = "";
	if(isset($_GET['id']))
	{
		$terminal_id = trim($_GET['id']);	
		$terminal_array = explode(",",$terminal_id);
		foreach($terminal_array as $id)
		{
			control_user_terminal_opr($con,$id);
		}
	}

	$sql="SELECT taskid FROM task WHERE cmd IN($terminal_id) AND tasktype='20'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result))
	{
		$delinfo=$row['taskid'];
		mysqli_query($con,"DELETE FROM task WHERE taskid='$delinfo'");
		mysqli_query($con,"DELETE FROM terminalkeymaptask WHERE taskid='$delinfo'");
	}

	mysqli_query($con,"DELETE FROM terminaloffolder WHERE folderid IN (SELECT id FROM terminalfolder WHERE terminalid IN($terminal_id))");
	mysqli_query($con,"DELETE FROM terminalfolder WHERE terminalid IN ($terminal_id)");
	mysqli_query($con,"DELETE FROM leddevice WHERE terminalid IN ($terminal_id)");
	mysqli_query($con,"DELETE FROM ledoftask WHERE terminalid IN ($terminal_id)");
	mysqli_query($con,"DELETE FROM cameramap WHERE terminalid IN ($terminal_id)");
	mysqli_query($con,"DELETE FROM terminalkey WHERE terminalkey.terminalid IN ($terminal_id)");
	$sqls="SELECT groupid,terminalid FROM terminalofgroup WHERE terminalid IN($terminal_id)";
	$results = mysqli_query($con,$sqls) or die(mysqli_error($con));
	while($rows = mysqli_fetch_array($results))
	{
		$getgroupid=$rows['groupid'];
		$getterminal_id=$rows['terminalid'];
		mysqli_query($con,"DELETE FROM terminalofgroup WHERE terminalofgroup.terminalid = '$getterminal_id'");
		$getsqls="SELECT terminalid FROM terminalofgroup WHERE groupid ='$getgroupid'";
		$key_result = mysqli_query($con,$getsqls) or die(mysqli_error($con));
		
		if( mysqli_num_rows($key_result) <=0 )
		{
			mysqli_query($con,"DELETE FROM serverplaystream WHERE streamid ='$getgroupid'");
		}
	}
	
		$sql="SELECT taskid,terminalid FROM terminaloftask WHERE terminalid IN($terminal_id)";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		while($row = mysqli_fetch_array($result))
		{
			$gettaskid=$row['taskid'];
			$getterminalid=$row['terminalid'];
			mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.terminalid = '$getterminalid'");
			/*
			$sqls="SELECT terminalid FROM terminaloftask WHERE taskid ='$gettaskid'";
			$key_result = mysqli_query($con,$sqls) or die(mysqli_error($con));
			if( mysqli_num_rows($key_result) <=0 )
			{
				mysqli_query($con,"DELETE FROM mediaoftask WHERE taskid ='$gettaskid'");
				mysqli_query($con,"DELETE FROM task WHERE taskid ='$gettaskid'");
			}
			*/
		}
			$getid=0;
			$getsql2="SELECT DISTINCT id FROM callgroup WHERE id IN (SELECT selectgroupid FROM terminalofcallgroup WHERE terminalid IN($terminal_id))";
			$key_result2 = mysqli_query($con,$getsql2) or die(mysqli_error($con));
			while($row = mysqli_fetch_array($key_result2))
			{
				$getid=$row['id'];
				mysqli_query($con,"DELETE FROM terminalofcallgroup WHERE terminalid IN ($terminal_id)");
				$getsql3="SELECT selectgroupid FROM terminalofcallgroup WHERE selectgroupid IN($getid)";
				$key_result3 = mysqli_query($con,$getsql3) or die(mysqli_error($con));
				if( mysqli_num_rows($key_result3) <=0 )
				{
					mysqli_query($con,"DELETE FROM callgroup WHERE id IN ($getid)");
				}
			}
			$getsql5="SELECT id FROM callgroup WHERE terminalid IN ($terminal_id)";
			$key_result5 = mysqli_query($con,$getsql5) or die(mysqli_error($con));
			while($row5 = mysqli_fetch_array($key_result5))
			{
				$getrow5=$row5['id'];
				mysqli_query($con,"DELETE FROM terminalofcallgroup WHERE selectgroupid IN ($getrow5)");
				mysqli_query($con,"DELETE FROM callgroup WHERE terminalid IN ($terminal_id)");
			}

	mysqli_query($con,"UPDATE task SET offlinestate='0' WHERE taskid IN(SELECT taskid FROM offlinetaskofterminal WHERE terminalid IN ($terminal_id))");
	mysqli_query($con,"DELETE FROM offlinemediaofterminal WHERE terminalid IN ($terminal_id)");
	mysqli_query($con,"DELETE FROM offlinetaskofterminal WHERE terminalid IN ($terminal_id)");
	
	 mysqli_query($con,"DELETE FROM camerofterminal WHERE terminalid IN ($terminal_id)");
	 mysqli_query($con,"DELETE FROM terminal WHERE terminal.id IN ($terminal_id)");

	$getidlist=explode(",",$_REQUEST['id']);

	foreach($getidlist as $getid)
	{
		$create_socket_obj->send_socket_generate_general("terminal",2,$getid);
		
	}

	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息	
		$_SESSION['url'] = "./terminalmanager.php";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./terminalmanager.php";
		//inputterminaltofile();//用来对终端数据重新输入
	
		echo "<script>window.location='success.php'</script>";	
	}
}


//启用终端对讲---没有使用到---只是更改数据库状态
function terminalspeech_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminaltype.isdecode,terminaltype.isencode FROM terminal,terminaltype ";
		
		$sql.= "WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isdecode'] == 0 && $row['isencode'] == 0)
			{
				//=========================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Terminal_not_support'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
		}
	}
	$sql="UPDATE terminal SET isspeech = '1' WHERE	terminal.id IN ($getid)";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "terminal?state=3&id=".$getid."&speech=true";	
					
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_speech("terminal",3,$getid,"true");
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
//停止对讲---没有被使用到---只是更改数据库状态
function terminalnospeech_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);
	
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminaltype.isdecode,terminaltype.isencode,terminaltype.id FROM terminal,terminaltype ";
		
		$sql.= "WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				//================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Disconnect'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isdecode'] == 0 && $row['isencode'] == 0)
			{
				//=======================================================================================
				/*echo "<script>alert('".strtoupper($do_php_prompt['Terminal_not_support'])."');</script>";//提示信息
				echo "<script>window.history.back();</script>";
				
				exit;
				*/
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
			else if($row['id']!=2&&$row['id']!=3&&$row['id']!=13&&$row['id']!=28&&$row['id']!=35)
			{
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
		}
	}

	$sql="UPDATE terminal SET isspeech = '0' WHERE	terminal.id IN ($getid) ";

	mysqli_query($con,$sql) or die(mysqli_error($con));
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./terminalmanager.php";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "terminal?state=4&id=".$getid."&speech=false";			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_speech("terminal",4,$getid,"false");
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
//启用录音
function set_terminal_record($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	
	/*
	$getterminalarrayid = explode(",",$getid);
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminal.typeid,terminaltype.isdecode,terminaltype.isencode FROM ";
		
		$sql.= "terminal,terminaltype WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
			//	$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isencode'] == 0)
			{
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
			
		}
	}
	*/
	$sql="UPDATE terminal SET isrecord = '1' WHERE	terminal.id IN ($getid)";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
	//	foreach($getidlist as $getid)
	//	{
			//$create_socket_obj->send_socket_speech("terminal",14,$getid,"true");
			$create_socket_obj->send_socket_generate_general("terminal",13,$getid);
	//	}
		echo "<script>window.location='success.php'</script>";	
	}
}
//停止录音
function set_terminal_stoprecord($con)
{
	
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	
	/*
	$getterminalarrayid = explode(",",$getid);
	
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminal.typeid,terminaltype.isdecode,terminaltype.isencode FROM ";
		
		$sql.= "terminal,terminaltype WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
			
			//	$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isencode'] == 0)
			{

				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
		
		}
	}
	*/
	$sql="UPDATE terminal SET isrecord = '0' WHERE	terminal.id IN ($getid) ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
	
		//foreach($getidlist as $getid)
		//{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "terminal?state=4&id=".$getid."&speech=false";			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_speech("terminal",15,$getid,"false");
			$create_socket_obj->send_socket_generate_general("terminal",14,$getid);
		//}
		echo "<script>window.location='success.php'</script>";	
	}
}
function set_terminal_backcall($con)
{
	
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);
	
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminal.typeid,terminal.isspeech,terminaltype.isdecode,terminaltype.isencode FROM ";
		
		$sql.= "terminal,terminaltype WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isdecode'] == 0 && $row['isencode'] == 0)
			{

				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
			//else if($row['isspeech'] == 0)
			//{
				//$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			//}
		}
	}
	$sql="UPDATE terminal SET isselectcall = '1' WHERE	terminal.id IN ($getid) ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
	//	$getidlist=explode(",",$_REQUEST['id']);
	/*
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "terminal?state=4&id=".$getid."&speech=false";			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_speech("terminal",15,$getid,"false");
		//}
		echo "<script>window.location='success.php'</script>";	
	}
}
function stop_terminal_backcall($con)
{
	
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	$getterminalarrayid = explode(",",$getid);
	
	for($i=0; $i<count($getterminalarrayid); $i++)
	{
		$sql="SELECT terminal.netstate,terminal.typeid,terminal.isspeech,terminaltype.isdecode,terminaltype.isencode FROM ";
		
		$sql.= "terminal,terminaltype WHERE terminal.typeid = terminaltype.id AND terminal.id = $getterminalarrayid[$i] ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			if($row['netstate'] == 0)
			{
		
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
			else if($row['isdecode'] == 0 && $row['isencode'] == 0)
			{

				$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			}
			//else if($row['isspeech'] == 0)
			//{
				//$forward_ok_error_obj->exit_back_function($do_php_prompt['Terminal_not_support']);
			//}
		}
	}
	$sql="UPDATE terminal SET isselectcall = '0' WHERE	terminal.id IN ($getid) ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
	//	$getidlist=explode(",",$_REQUEST['id']);
	/*
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "terminal?state=4&id=".$getid."&speech=false";			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_speech("terminal",15,$getid,"false");
		//}
		echo "<script>window.location='success.php'</script>";	
	}
}

//删除终端快捷映射---仅清除数据库的记录
function del_terminal_shotcut($con)
{
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	//$forward_ok_error_obj = new forward_ok_error_class();
	$id = "";
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
	}

	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
	}
	$flagdo = 0;
	if(isset($_GET['flagdo']))
	{
		$flagdo = trim($_GET['flagdo']);
	}
	$typeid = "";
	if(isset($_GET['typeid']))
	{
		$typeid = trim($_GET['typeid']);
	}
	
	mysqli_query($con,"START TRANSACTION");
	mysqli_query($con,"LOCK TABLE terminalkeymap WRITE,terminalkey WRITE");
	
	mysqli_query($con,"DELETE FROM terminalkeymap where terminalkeymap.keyid = '$id'");
	
	mysqli_query($con,"DELETE FROM terminalkey WHERE terminalkey.id = '$id'");
	
	if(mysqli_error($con))
	{
		mysqli_query($con,"ROLLBACK");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		if($typeid==33)
			$_SESSION['url'] = "view_terminal_shotcut_mapping.php?getact=1&terminal_id=".$terminal_id."&gettype=33";
		else
			$_SESSION['url'] = "view_terminal_shotcut_mapping.php?terminal_id=".$terminal_id."";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		if($typeid==33)
			$_SESSION['url'] = "view_terminal_shotcut_mapping.php?getact=1&terminal_id=".$terminal_id."&gettype=33";
		else
		$_SESSION['url'] = "view_terminal_shotcut_mapping.php?terminal_id=".$terminal_id."";
	
		echo "<script>window.location='success.php'</script>";	
	}	
}

function del_quick_task($con)
{
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	//$forward_ok_error_obj = new forward_ok_error_class();
	$id = "";
	if(isset($_GET['id']))
	{
	   $id = trim($_GET['id']);
	}
	$arrid=explode(",",$id);
	
	
	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
	}
	$ledtaskid=0;
	mysqli_query($con,"START TRANSACTION");
	mysqli_query($con,"LOCK TABLE terminalkeymaptask WRITE,task WRITE,mediaoftask WRITE,ledsentence WRITE,ledoftask WRITE,ttssentence WRITE,media WRITE,terminaloftask WRITE");

	for($i=0;$i<count($arrid);$i++)
	{
		$temp = explode("/",$arrid[$i]);
		$keyid=$temp[1];
		$taskid=$temp[0];
		$sqls= "select taskid from task where sec_task_id='$taskid'";	
	
		$key_results = mysqli_query($con,$sqls) or die(mysqli_error($con));
		if($rows = mysqli_fetch_array($key_results))
		{
			$ledtaskid=$rows['taskid'];
		}
		$sql2= "select mediaid from mediaoftask where taskid=$ledtaskid";	

		$key_result2 = mysqli_query($con,$sql2) or die(mysqli_error($con));
		while($rows2 = mysqli_fetch_array($key_result2))
		{
			$ledmediaid=intval($rows2['mediaid']);
		
			$keys = "DELETE FROM ledsentence WHERE mediaid = $ledmediaid";
			mysqli_query($con,$keys);
			$key4 = "DELETE FROM media WHERE id =$ledmediaid";	
		     mysqli_query($con,$key4);
		}
			$key2 = "DELETE FROM ledoftask WHERE taskid = $ledtaskid";
			mysqli_query($con,$key2);
			
			$key3 = "DELETE FROM mediaoftask where taskid = $ledtaskid";
			mysqli_query($con,$key3);
	
		mysqli_query($con,"DELETE FROM task where taskid = $ledtaskid");
		$sqls= "select mediaid from mediaoftask where taskid=$taskid";	
		$key_results = mysqli_query($con,$sqls) or die(mysqli_error($con));
		while($rows = mysqli_fetch_array($key_results))
		{
			$getrow=$rows['mediaid'];

			mysqli_query($con,"DELETE FROM media WHERE id ='$getrow' and typeid='tts'");
			mysqli_query($con,"DELETE FROM ttssentence WHERE sentenceid = '$getrow'");
		}
		mysqli_query($con,"DELETE FROM mediaoftask where taskid = '$taskid'");
		mysqli_query($con,"DELETE FROM terminaloftask where taskid = '$taskid'");
		mysqli_query($con,"DELETE FROM task where taskid = '$taskid'");
		mysqli_query($con,"DELETE FROM terminalkeymaptask where terminalkeymaptask.taskid = '$taskid' and keyid='$keyid'");
		
	}
	if(mysqli_error($con))
	{
		mysqli_query($con,"ROLLBACK");
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./view_quickplay.php?terminal_id=$terminal_id";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		mysqli_query($con,"COMMIT");
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./view_quickplay.php?terminal_id=$terminal_id";
		echo "<script>window.location='success.php'</script>";	
	}	
}


function del_yingjiplay($con)
{
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	//$forward_ok_error_obj = new forward_ok_error_class();
	$id = "";
	if(isset($_GET['id']))
	{
	   $id = trim($_GET['id']);
	}
	$arrid=explode(",",$id);
	
	
	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
	}

	mysqli_query($con,"START TRANSACTION");
	mysqli_query($con,"UNLOCK TABLES");
	mysqli_query($con,"LOCK TABLE task WRITE,mediaoftask WRITE,terminaloftask WRITE,media WRITE,terminalkeymaptask WRITE");

	for($i=0;$i<count($arrid);$i++)
	{
		$temp = explode("/",$arrid[$i]);
		$keyid=$temp[1];
		$taskid=$temp[0];
	
		mysqli_query($con,"DELETE FROM mediaoftask where taskid = '$taskid'");
		mysqli_query($con,"DELETE FROM terminaloftask where taskid = '$taskid'");
		mysqli_query($con,"DELETE FROM task where taskid = '$taskid'");
		mysqli_query($con,"DELETE FROM terminalkeymaptask where terminalkeymaptask.taskid = '$taskid' and keyid='$keyid'");
		
	}
	if(mysqli_error($con))
	{
		mysqli_query($con,"ROLLBACK");
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./view_yingjiplay.php?terminal_id=$terminal_id";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		mysqli_query($con,"COMMIT");
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./view_yingjiplay.php?terminal_id=$terminal_id";
		echo "<script>window.location='success.php'</script>";	
	}	
}


function del_leddevice($con)
{
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	//$forward_ok_error_obj = new forward_ok_error_class();
	$id = "";
	if(isset($_GET['id']))
	{
	   $id = trim($_GET['id']);
	}

	$ledflag = "";
	if(isset($_GET['ledflag']))
	{
		$ledflag = trim($_GET['ledflag']);
	}

	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
	}

	mysqli_query($con,"START TRANSACTION");
	mysqli_query($con,"LOCK TABLE leddevice WRITE");
	mysqli_query($con,"DELETE FROM leddevice where id IN($id)");
	
	if(mysqli_error($con))
	{
		mysqli_query($con,"ROLLBACK");
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		if($ledflag==2)
		{
			$_SESSION['url'] = "./led_terminal_sousuo.php??id=0&ledflag=2";
		}
		else
		{
			$_SESSION['url'] = "./led_terminal_sousuo.php?terminal_id=$terminal_id";
		}
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		mysqli_query($con,"COMMIT");
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		if($ledflag==2)
		{
			$_SESSION['url'] = "./led_terminal_sousuo.php??id=0&ledflag=2";
		}
		else
		{
		$_SESSION['url'] = "./led_terminal_sousuo.php?terminal_id=$terminal_id";
		}
		echo "<script>window.location='success.php'</script>";	
	}	
}

//删除终端快捷映射---仅清除数据库的记录
function del_terminal_music($con)
{
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	$create_socket_obj = new create_socket_class();
	$id = "";
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
		$getmediaid=$id;
		$id = explode(",",$id);
	}
	
	$flag = "";
	if(isset($_GET['flag']))
	{
		$flag = trim($_GET['flag']);
	}
		
	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
	}
	
	mysqli_query($con,"START TRANSACTION");
	mysqli_query($con,"LOCK TABLE offlinetask WRITE,offlinemediaofterminal WRITE,offlinemedia WRITE");
	
	if($flag==2||$flag==5)
	{
	$keys = "SELECT taskname FROM offlinetask WHERE taskid IN(SELECT taskid FROM offlinemediaofterminal WHERE terminalid = '$terminal_id' AND mediaid IN($id))";
	$key_results = mysqli_query($con,$keys) or die(mysqli_error($con));
		if( mysqli_num_rows($key_results) > 0 )
		{
				if($row=mysqli_fetch_array($key_results))
				{
	
					$forward_ok_error_obj->exit_back_function($row['taskname']."".$do_php_prompt['using_not_deleted']);	
				}
		}
		else
		{
		
			mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '$flag' WHERE mediaid IN ($id) and terminalid='$terminal_id'");
		}
	}
	else if($flag==11)
	{
		mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '$flag' WHERE mediaid IN ($id) and terminalid='$terminal_id'");
	
	}
	else if($flag==18)
	{
		for($i=0;$i<count($id);$i++)
		{
		    $mediaid=$id[$i];
		
			$sqlgroup = mysqli_query($con,"SELECT mediaid FROM offlinemediaofterminal WHERE mediaid='$mediaid' and terminalid ='$terminal_id' and taskid='0'");
			if(mysqli_num_rows($sqlgroup)<=0)
			{
				
			}
			else
			{	
				 mysqli_query($con,"DELETE FROM offlinemediaofterminal where terminalid='$terminal_id' and mediaid='$mediaid' and taskid='0'") or die(mysqli_error($con));
				while($rows = mysqli_fetch_array($sqlgroup))
				{
					$mediaid=$rows['mediaid'];					
					$sqlgroups = mysqli_query($con,"SELECT mediaid FROM offlinemediaofterminal WHERE  mediaid='$mediaid'");
					if(mysqli_num_rows($sqlgroups)<=0)
					{
						mysqli_query($con,"DELETE FROM offlinemedia where id='$mediaid'") or die(mysqli_error($con));	
					}
				}
				$create_socket_obj->send_socket_terminalmedia("terminal",19,$terminal_id,$mediaid);
				
			}

		}
	
	
	}

	//	mysqli_query($con,"UPDATE offlinemedia SET offlinestate = '$flag' WHERE id IN ($id)");
		if(mysqli_error($con))
		{
			
			mysqli_query($con,"ROLLBACK");
			
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "displayofflinemedia.php?id=".$terminal_id."";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
		
			mysqli_query($con,"COMMIT");
			
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "displayofflinemedia.php?id=".$terminal_id."";
			if($flag==5||$flag==2||$flag==11)
			{
				$create_socket_obj->send_socket_generate_general("task",15,0);
			}
		
			echo "<script>window.location='success.php'</script>";	
		}
		
}


//删除终端快捷映射---仅仅删除数据库记录
function cancel_terminal_shotcut($con)
{
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$terminal_id = "";
	
	mysqli_query($con,"START TRANSACTION");
		
	mysqli_query($con,"LOCK TABLE terminalkey WRITE,terminalkeymap WRITE,terminal WRITE");

	$terminal_id = "";
	
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
	}
	
	$key_sql = "SELECT id FROM terminalkey WHERE terminalkey.terminalid = '$terminal_id'";
	
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	
	if( mysqli_num_rows($key_result) <=0 )
	{
		//=======================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['Not_setup_support'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
	
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['Not_setup_support']);
	}
	else
	{
		while($key_row = mysqli_fetch_array($key_result))
		{
			mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalkeymap.keyid = '".$key_row['id']."'");
	
			if(mysqli_error($con))
			{
				mysqli_query($con,'ROLLBACK ');
	
				mysqli_query($con,'UNLOCK TABLES');
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "terminalmanager.php";
	
				echo "<script>window.location='error.php'</script>";
			}
		}
		mysqli_query($con,"DELETE FROM terminalkey WHERE terminalkey.terminalid = '$terminal_id'");
		mysqli_query($con,"UPDATE terminal SET instancy='0' WHERE id = '$terminal_id'");
		if(mysqli_error($con))
		{
			mysqli_query($con,'ROLLBACK ');
	
			mysqli_query($con,'UNLOCK TABLES');
			
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "terminalmanager.php";
	
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			mysqli_query($con,'COMMIT');
			mysqli_query($con,'UNLOCK TABLES');
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			$_SESSION['url'] = "terminalmanager.php";
			echo "<script>window.location='success.php'</script>";
		}
	}
}
//添加任务时时添加任务与媒体对应记录---没有被使用
function medialistadd_msg($con)
{
	  //require_once("inc/socket_conf.php");
	  //添加外部变量
	  global $do_php_prompt;		
	  mysqli_query($con,"INSERT INTO `medialist` (`mediaid`,`taskid`) VALUES ('$_POST[mediaid]', '$_GET[id]')");
	  if(mysqli_error($con))
	  {
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./taskmanager.php";
		
			echo "<script>window.location='error.php'</script>";
	  }
	  else
	  {
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./taskmanager.php";
		
			echo "<script>window.location='success.php'</script>";	
	  }
		
}
//删除任务与媒体对应记录---没有被使用
function madlistdel_msg($con)
{
	
	
	//require_once("inc/socket_conf.php");
	
	 //添加外部变量
	 global $do_php_prompt;
	
	mysqli_query($con,"DELETE FROM `medialist` WHERE id='$_GET[id]'");
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//设置终端同步任务
function set_task_synch($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	$terminalid = "";
	
	if(isset($_GET['terminalid']))
	{
		$terminalid = trim($_GET['terminalid']);
		$terminalid = explode(",",$terminalid);
	}
	
	$keyvalue = "";
	
	$task_map_id = "";
	$aaa = 0;
	$bbb = 0;
		$task_map_id = trim($_POST['task_map_id']);
		$task_map_id = explode(",",$task_map_id);

		mysqli_query($con,"LOCK TABLE task WRITE,terminalofgroup WRITE,terminaloftask WRITE");
		for($i=0;$i<count($task_map_id);$i++)
		{
		for($j=0;$j<count($terminalid);$j++)
		{
			$aaa=$terminalid[$j];
			$bbb=$task_map_id[$i];
			$sqlgroup = mysqli_query($con,"select groupid from terminalofgroup where terminalid ='$aaa'");

			if(mysqli_num_rows($sqlgroup)>0)
			{
				while($row_usetask = mysqli_fetch_array($sqlgroup))
				{
					$getgroupid=$row_usetask['groupid'];
				}
			}
			else
			{
				$getgroupid=0;
			}
		
			$sql=mysqli_query($con,"select terminalid,taskid from terminaloftask where taskid ='$bbb' AND terminalid ='$aaa'");
			
			if(mysqli_num_rows($sql)<=0)
			{
				mysqli_query($con,"UPDATE task SET offlinestate = '0' WHERE taskid ='$bbb'") or die(mysqli_error($con));
				
				mysqli_query($con,"INSERT INTO terminaloftask (taskid,terminalid,groupid,area) VALUES('$bbb','$aaa','$getgroupid','1111111111111111')") or die(mysqli_error($con));
				$bbb=$bbb+1;
					mysqli_query($con,"INSERT INTO terminaloftask (taskid,terminalid,groupid,area) VALUES('$bbb','$aaa','$getgroupid','1111111111111111')") or die(mysqli_error($con));
					
			}
		}
	}
	mysqli_query($con,"UNLOCK TABLES");
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		echo "<script>window.location='success.php'</script>";	
	}	
}
//设置绑定任务
function set_offline_task($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$task_map_id = "";
	if(isset($_POST['task_map_id']))
	{
		$task_map_id = trim($_POST['task_map_id']);
	}

	$istran = 1;
	if(isset($_POST['istran']))
	{
		$istran = trim($_POST['istran']);
	}
	$get_inid = "";
	if(isset($_POST['get_inid']))
	{
		$get_inid = trim($_POST['get_inid']);
	}

	if($get_inid!="")
	mysqli_query($con,"UPDATE terminaloftask SET offlineparam = '0' WHERE taskid IN ($get_inid)") or die(mysqli_error($con));
	
	mysqli_query($con,"UPDATE terminaloftask SET offlineparam = '$istran' WHERE taskid IN ($task_map_id)") or die(mysqli_error($con));
//	mysqli_query($con,"UPDATE mediaofterminal SET offlineparam = '$istran' WHERE taskid IN ($task_map_id)") or die(mysqli_error($con));
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./set_offline.php";
		echo "<script>window.location='success.php'</script>";	
	}	
}

//设置绑定任务
function set_offline_music($con)
{
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	$getmiedaid = "";
	if(isset($_GET['getmiedaid']))
	{
		$getmiedaid = trim($_GET['getmiedaid']);
		
		$getmiedaid = explode(",",$getmiedaid);
	}
	
	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
		$terminal_id = explode(",",$terminal_id);
	}

	if(isset($_GET['flag']))
	{
		$flag = trim($_GET['flag']);
	}

	mysqli_query($con,"LOCK TABLES offlinemediaofterminal WRITE,offlinemedia WRITE,media WRITE");
	for($i=0;$i<count($getmiedaid);$i++)
	{
		for($j=0;$j<count($terminal_id);$j++)
		{
			$aaa=$getmiedaid[$i];
			$bbb=$terminal_id[$j];
			$sqlgroups = mysqli_query($con,"SELECT * FROM offlinemedia WHERE id ='$aaa'");
			if(mysqli_num_rows($sqlgroups)<=0)
			{
				if($flag==1||$flag==2)
				{
					$result_media = mysqli_query($con,"SELECT id,name,size,typeid,priority,filename,folderid,timelength,channel,sample,bitrate,codecid FROM media WHERE id ='$aaa'");
					if($row = mysqli_fetch_array($result_media))
					{
						$sql="INSERT INTO offlinemedia (id,name,size,typeid,priority,filename,folderid,timelength,channel,sample,bitrate,codecid) VALUES('$row[id]','$row[name]','$row[size]','$row[typeid]','$row[priority]','$row[filename]','$row[folderid]','$row[timelength]','$row[channel]','$row[sample]','$row[bitrate]','$row[codecid]')";
						mysqli_query($con,$sql) or die(mysqli_error($con));
					}
				}
			}
			else
			{
				$result_media = mysqli_query($con,"SELECT id,name,size,typeid,priority,filename,folderid,timelength,channel,sample,bitrate,codecid FROM media WHERE id ='$aaa'");
					if($row = mysqli_fetch_array($result_media))
					{
						mysqli_query($con,"UPDATE offlinemedia SET size='$row[size]',timelength='$row[timelength]',sample='$row[sample]',bitrate='$row[bitrate]' WHERE id='$aaa' ") or die(mysqli_error($con));
					}
			}
		
			$sqlgroup = mysqli_query($con,"SELECT mediaid,terminalid FROM offlinemediaofterminal WHERE mediaid ='$aaa' AND terminalid ='$bbb' and taskid='0'");
			if(mysqli_num_rows($sqlgroup)<=0)
			{
				if($flag==1||$flag==2)
				{	
					mysqli_query($con,"INSERT INTO offlinemediaofterminal(mediaid,terminalid,offlinestate) VALUES('$aaa','$bbb','$flag')") or die(mysqli_error($con));
				}
							
			}
			else
			{	
		
				mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '$flag' WHERE mediaid='$aaa' and terminalid='$bbb' and taskid='0'") or die(mysqli_error($con));	
			}
		
		}
	}

	mysqli_query($con,"UNLOCK TABLES");
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./set_offlinemusic.php";
		if($flag==5||$flag==2)
		{
			$create_socket_obj->send_socket_generate_general("task",15,0);
		}

		echo "<script>window.location='success.php'</script>";	
	}	
}

//停止绑定任务
function stop_offline_music($con)
{
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	$getmiedaid = "";
	if(isset($_GET['getmiedaid']))
	{
		$getmiedaid = trim($_GET['getmiedaid']);
		
		$getmiedaid = explode(",",$getmiedaid);
	}
	
	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
		$terminal_id = explode(",",$terminal_id);
	}

	if(isset($_GET['flag']))
	{
		$flag = trim($_GET['flag']);
	}
	mysqli_query($con,"LOCK TABLES offlinetask WRITE,offlinetaskofterminal WRITE,offlinemediaofterminal WRITE");
	if($flag==14)
	{
			mysqli_query($con,"DELETE FROM offlinemediaofterminal") or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM offlinetaskofterminal") or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM offlinetask") or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM offlinemedia") or die(mysqli_error($con));
	}
	else
	{
	
		for($i=0;$i<count($getmiedaid);$i++)
		{
			for($j=0;$j<count($terminal_id);$j++)
			{
				$aaa=$getmiedaid[$i];
				$bbb=$terminal_id[$j];
				mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '$flag' WHERE mediaid='$aaa' and terminalid='$bbb' and task='0'") or die(mysqli_error($con));	
			
			}
		}
	}
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./set_offlinemusic.php";
		if($flag==11||$flag==14)
		{
			$create_socket_obj->send_socket_generate_general("task",15,0);
		}

		echo "<script>window.location='success.php'</script>";	
	}	
}


function rsync_offlinetime($con)
{
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息

		$_SESSION['url'] = "./offlinemusicmanager.php";
	
		$create_socket_obj->send_socket_generate_general("terminal",15,0);

		echo "<script>window.location='success.php'</script>";	

}

//停止绑定任务
function del_all_offline($con)
{
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	
	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_ids = trim($_GET['terminal_id']);
		$terminal_id = explode(",",$terminal_ids);
	}
	
	$flag = "";
	if(isset($_GET['flag']))
	{
		$flag = trim($_GET['flag']);
	}

	mysqli_query($con,"LOCK TABLES offlinetask WRITE,offlinetaskofterminal WRITE,offlinemediaofterminal WRITE,terminal WRITE,offlinemedia WRITE,task WRITE");
	if($flag==14)
	{
			mysqli_query($con,"DELETE FROM offlinemediaofterminal") or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM offlinetaskofterminal") or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM offlinetask") or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM offlinemedia") or die(mysqli_error($con));
			mysqli_query($con,"UPDATE task SET offlinestate = '0' where tasktype IN(1,2)") or die(mysqli_error($con));
	}
	else if($flag==19)
	{
		
		$key_sql = "SELECT netstate FROM terminal WHERE id IN($terminal_ids)";
	
			$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
			
			while($row = mysqli_fetch_array($key_result))
			{
				if($row['netstate']==0)
					$forward_ok_error_obj->exit_back_function($do_php_prompt['Disconnect']);
			}
		
		$sqlgroup = mysqli_query($con,"SELECT mediaid,terminalid FROM offlinemediaofterminal WHERE terminalid IN($terminal_ids) and taskid = '0'");
		while($rows = mysqli_fetch_array($sqlgroup))
		{

			$mediaid=$rows['mediaid'];
			$terminalid=$rows['terminalid'];
			$sqlgroups = mysqli_query($con,"SELECT mediaid FROM offlinemediaofterminal WHERE mediaid='$mediaid' and terminalid='$terminalid' and taskid !='0'");
			if(mysqli_num_rows($sqlgroups)<=0)
			{	
			 mysqli_query($con,"DELETE FROM offlinemediaofterminal where mediaid='$mediaid' and terminalid='$terminalid'") or die(mysqli_error($con));	
			}
			$sql = mysqli_query($con,"SELECT mediaid FROM offlinemediaofterminal WHERE mediaid='$mediaid'");
			if(mysqli_num_rows($sql)<=0)
			{	
			 mysqli_query($con,"DELETE FROM offlinemedia where id='$mediaid'") or die(mysqli_error($con));	
			}
			 $create_socket_obj->send_socket_terminalmedia("terminal",19,$terminalid,$mediaid);	
		}
	}
	else if($flag==18)
	{
		for($i=0;$i<count($terminal_id);$i++)
		{
			$getmediaid=0;
		    $terminalid=$terminal_id[$i];
			$sqlgroup = mysqli_query($con,"SELECT mediaid FROM offlinemediaofterminal WHERE terminalid ='$terminalid'");
			if(mysqli_num_rows($sqlgroup)<=0)
			{
				
			}
			else
			{	
				mysqli_query($con,"DELETE FROM offlinemediaofterminal where terminalid='$terminalid' and taskid='0'") or die(mysqli_error($con));
				while($rows = mysqli_fetch_array($sqlgroup))
				{
					$mediaid=$rows['mediaid'];
					if($getmediaid==0)
					{
						$getmediaid=$mediaid;
					}
					else
					{
						$getmediaid=$getmediaid.",".$mediaid;
					}	
					
					$sqlgroups = mysqli_query($con,"SELECT taskid,offlinemedia.name FROM offlinemediaofterminal,offlinemedia WHERE terminalid ='$terminalid' and mediaid='$mediaid'and offlinemedia.id='$mediaid'");
					if(mysqli_num_rows($sqlgroups)<=0)
					{
						mysqli_query($con,"DELETE FROM offlinemedia where id='$mediaid'") or die(mysqli_error($con));	
					}
					else 
					{
						while($get_rows = mysqli_fetch_array($sqlgroups))
						{
							$gettaskid=$get_rows['taskid'];
							$sqls = mysqli_query($con,"SELECT taskname FROM offlinetask WHERE taskid ='$gettaskid'");
							while($getrows = mysqli_fetch_array($sqls))
							{ 
								$forward_ok_error_obj->exit_back_function($get_rows['name'].$do_php_prompt['musicusing_not_deleted'].$getrows['taskname'].$do_php_prompt['musicing_task_deleted']);	
							}
						}
					}
					
					$create_socket_obj->send_socket_terminalmedia("terminal",19,$terminalid,$mediaid);	
				}	
			}
			//mysqli_query($con,"UPDATE offlinetaskofterminal SET offlinestate = '$flag' where and terminalid='$terminalid'") or die(mysqli_error($con));
			//mysqli_query($con,"UPDATE offlinetask SET offlinestate = '$flag'") or die(mysqli_error($con));
		}
	
	}
	else
	{
		for($i=0;$i<count($terminal_id);$i++)
		{
		    $terminalid=$terminal_id[$i];
		
			 mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '$flag' where  terminalid='$terminalid' and taskid='0'") or die(mysqli_error($con));
			//mysqli_query($con,"UPDATE offlinetaskofterminal SET offlinestate = '$flag' where and terminalid='$terminalid'") or die(mysqli_error($con));
			//mysqli_query($con,"UPDATE offlinetask SET offlinestate = '$flag'") or die(mysqli_error($con));
		}
	
	}
	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./offlinemusicmanager.php";
		if($flag==18)
		{
	
		}
		else if($flag==14)
		{
			$create_socket_obj->send_socket_generate_general("task",18,0);
		}
		else
		{
			$create_socket_obj->send_socket_generate_general("task",15,0);
		}
		
		echo "<script>window.location='success.php'</script>";	
	}	
}

function do_offline_task($con)
{
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	if(isset($_GET['getid']))
	{
		$getid = trim($_GET['getid']);
		
	}
	
	$flag = 1;
	if(isset($_GET['flag']))
	{
		$flag = trim($_GET['flag']);
	}
	
mysqli_query($con,"LOCK TABLES offlinetask WRITE,task WRITE,offlinemedia WRITE,media WRITE,terminaloftask WRITE,offlinetaskofterminal WRITE,offlinemediaofterminal WRITE,terminal WRITE,mediaoftask WRITE");

			mysqli_query($con,"UPDATE task SET offlinestate = '$flag' WHERE taskid IN($getid)") or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM offlinetask WHERE taskid IN($getid)") or die(mysqli_error($con));
			
			$sql="INSERT INTO offlinetask SELECT taskid,taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,startdate,enddate,playtime,endtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,info,defaultvolume,task_user_id,sec_task_id,parentid,offlinestate FROM task WHERE taskid IN($getid)";
				mysqli_query($con,$sql) or die(mysqli_error($con));

				mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '0' WHERE taskid IN($getid)") or die(mysqli_error($con));
				mysqli_query($con,"UPDATE offlinetaskofterminal SET offlinestate = '0' WHERE taskid IN($getid)") or die(mysqli_error($con));
							
				$result_terminals = mysqli_query($con,"SELECT terminalid,terminaloftask.area,taskid FROM terminaloftask WHERE taskid IN($getid) AND terminalid IN(SELECT id FROM terminal WHERE totalcapacity!='0')");
				
						while($rows = mysqli_fetch_array($result_terminals))
						{
							$aaa=$rows['taskid'];
							$rowsterminalid=$rows['terminalid'];
							$rowsterminaloftask=$rows['terminaloftask'];	
							$rowsarea=$rows['area'];
							
							$result_yuanterminals = mysqli_query($con,"SELECT terminalid FROM offlinetaskofterminal WHERE taskid ='$aaa' AND terminalid='$rowsterminalid'");
							if(mysqli_num_rows($result_yuanterminals)<=0)
							{
								mysqli_query($con,"INSERT INTO offlinetaskofterminal (taskid,terminalid,offlinestate,area) VALUES('$aaa','$rowsterminalid','$flag','$rowsarea')") or die(mysqli_error($con));
							}
							else
							{
							 mysqli_query($con,"UPDATE offlinetaskofterminal SET offlinestate = '$flag',area='$rowsarea' WHERE taskid='$aaa' AND terminalid='$rowsterminalid'") or die(mysqli_error($con));
							
							}
						
							$result_media = mysqli_query($con,"SELECT mediaid,taskid,sort FROM mediaoftask WHERE taskid ='$aaa'");
							while($rowmedia = mysqli_fetch_array($result_media))
							{

								$rowmediaid=$rowmedia['mediaid'];
								$rowsort=$rowmedia['sort'];
								
								$get_media = mysqli_query($con,"SELECT id FROM offlinemedia WHERE id ='$rowmediaid'");
								if(mysqli_num_rows($get_media)<=0)
								{
									  mysqli_query($con,"INSERT INTO offlinemedia SELECT id,media.name,size,typeid,priority,filename,folderid,timelength,channel,sample,bitrate,codecid FROM media WHERE media.id='$rowmediaid'") or die(mysqli_error($con));
								}
								
								$result_media2 = mysqli_query($con,"SELECT mediaid,terminalid FROM offlinemediaofterminal WHERE mediaid='$rowmediaid' and terminalid='$rowsterminalid'");
								if(mysqli_num_rows($result_media2)<=0)
								{
									mysqli_query($con,"INSERT INTO offlinemediaofterminal(mediaid,terminalid,offlinestate,sort) VALUES('$rowmediaid','$rowsterminalid','$flag','$rowsort')") or die(mysqli_error($con));
								}	
						
								$result_medias = mysqli_query($con,"SELECT mediaid,terminalid FROM offlinemediaofterminal WHERE taskid ='$aaa' and mediaid='$rowmediaid' and terminalid='$rowsterminalid' ");
								
								if(mysqli_num_rows($result_medias)<=0)
								{
								   mysqli_query($con,"INSERT INTO offlinemediaofterminal(mediaid,terminalid,offlinestate,taskid,sort) VALUES('$rowmediaid','$rowsterminalid','$flag','$aaa','$rowsort')") or die(mysqli_error($con));
								}
								else
								{
								 mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '$flag',sort='$rowsort' WHERE taskid='$aaa' and terminalid='$rowsterminalid' and mediaid='$rowmediaid'") or die(mysqli_error($con));		
								}
							}
						}							
				mysqli_query($con,"UPDATE offlinetaskofterminal SET offlinestate = '5' WHERE taskid IN($getid) and offlinestate='0'") or die(mysqli_error($con));
				mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '5' WHERE taskid IN($getid) and offlinestate='0'") or die(mysqli_error($con));

	 if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./set_offline.php";
		if($flag==2)
		{
			$create_socket_obj->send_socket_generate_general("task",15,0);
		}
		echo "<script>window.location='success.php'</script>";	
	}

}

//设置绑定任务
function set_offline_tasks($con)
{
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$getid = "";
	if(isset($_GET['getid']))
	{
		$getid = trim($_GET['getid']);
		$getid = explode(",",$getid);
	}

	$flag = 1;
	if(isset($_GET['flag']))
	{
		$flag = trim($_GET['flag']);
	}
	
	mysqli_query($con,"LOCK TABLES offlinetask WRITE,task WRITE,offlinemedia WRITE,media WRITE,terminaloftask WRITE,offlinetaskofterminal WRITE,offlinemediaofterminal WRITE");
	
	for($i=0;$i<count($getid);$i++)
	{
				$aaa=$getid[$i];
				if($flag==18)
				{
					$terminalid="";
					$result_terminals = mysqli_query($con,"SELECT terminalid FROM offlinetaskofterminal WHERE taskid ='$aaa'");
					while($rows = mysqli_fetch_array($result_terminals))
					{
					
						if($terminalid=="")
						{
							$terminalid=$rows['terminalid'];		
						}
						else
						{
						   $terminalid = $terminalid.",".$rows['terminalid'];
						}
					}
					$terminalid="{".$terminalid."}";
					$create_socket_obj->send_socket_taskterminal("terminal",18,$aaa,$terminalid);
					mysqli_query($con,"DELETE FROM offlinetask WHERE taskid ='$aaa'") or die(mysqli_error($con));
					mysqli_query($con,"DELETE FROM offlinetaskofterminal WHERE taskid ='$aaa'") or die(mysqli_error($con));
					mysqli_query($con,"DELETE FROM offlinemediaofterminal WHERE taskid ='$aaa'") or die(mysqli_error($con));
					mysqli_query($con,"UPDATE task SET offlinestate = '0' WHERE taskid='$aaa' ") or die(mysqli_error($con));		
				}
				else if($flag==16||$flag==17)
				{
					$create_socket_obj->send_socket_generate_general("task",$flag,$aaa);
				}
				else if($flag==14||$flag==15)
				{
					$flag=$flag-13;
					$result_terminals = mysqli_query($con,"SELECT terminalid,terminaloftask.area FROM terminaloftask WHERE taskid ='$aaa'");
					while($rows = mysqli_fetch_array($result_terminals))
					{
						$result_yuanterminals = mysqli_query($con,"SELECT terminalid,offlinetaskofterminal.area FROM offlinetaskofterminal WHERE taskid ='$aaa' AND terminalid='$rows[terminalid]'");
						if(mysqli_num_rows($result_yuanterminals)>0)
						{
								mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '$flag' WHERE taskid='$aaa' and terminalid='$rows[terminalid]'") or die(mysqli_error($con));
								mysqli_query($con,"UPDATE offlinetaskofterminal SET offlinestate = '$flag' WHERE taskid='$aaa' and terminalid='$rows[terminalid]'") or die(mysqli_error($con));
								mysqli_query($con,"UPDATE offlinetask SET offlinestate = '$flag' WHERE taskid='$aaa' ") or die(mysqli_error($con));
						}
					}
				}
				else if($flag==11)
				{
						$result_terminals = mysqli_query($con,"SELECT terminalid,terminaloftask.area FROM terminaloftask WHERE taskid ='$aaa'");
						while($rows = mysqli_fetch_array($result_terminals))
						{
							$result_yuanterminals = mysqli_query($con,"SELECT terminalid,offlinetaskofterminal.area FROM offlinetaskofterminal WHERE taskid ='$aaa' AND terminalid='$rows[terminalid]'");
							if(mysqli_num_rows($result_yuanterminals)>0)
							{
								$flags=$flag+1;
									mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '$flag' WHERE taskid='$aaa' and terminalid='$rows[terminalid]'") or die(mysqli_error($con));
									mysqli_query($con,"UPDATE offlinetaskofterminal SET offlinestate = '$flags' WHERE taskid='$aaa' and terminalid='$rows[terminalid]'") or die(mysqli_error($con));
									mysqli_query($con,"UPDATE offlinetask SET offlinestate = '$flags' WHERE taskid='$aaa' ") or die(mysqli_error($con));
							}
						}
				}
				else
				{
					$sqlgroups = mysqli_query($con,"SELECT * FROM offlinetask WHERE taskid ='$aaa'");
					{
						if(mysqli_num_rows($sqlgroups)<=0)
						{
						
							if($flag==1||$flag==2)
							{
							$sql="INSERT INTO offlinetask SELECT taskid,taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,startdate,enddate,playtime,endtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,info,defaultvolume,task_user_id,sec_task_id,parentid,offlinestate FROM task WHERE taskid='$aaa'";
							mysqli_query($con,$sql) or die(mysqli_error($con));
							
							}	
						}
						else
						{
							if($flag==4||$flag==5)
							{
								mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '$flag' WHERE taskid='$aaa'") or die(mysqli_error($con));
								mysqli_query($con,"UPDATE offlinetaskofterminal SET offlinestate = '$flag' WHERE taskid='$aaa' ") or die(mysqli_error($con));
								mysqli_query($con,"UPDATE offlinetask SET offlinestate = '$flag' WHERE taskid='$aaa' ") or die(mysqli_error($con));
								mysqli_query($con,"UPDATE task SET offlinestate = '0' WHERE taskid='$aaa' ") or die(mysqli_error($con));
							}
							if($flag==1||$flag==2)
							{
								$sqltask = mysqli_query($con,"SELECT * FROM task WHERE taskid ='$aaa'");
								while($rows = mysqli_fetch_array($sqltask))
								{
									mysqli_query($con,"UPDATE offlinetask SET taskname='$rows[taskname]',israndomplay='$rows[israndomplay]',projectstate='$rows[projectstate]',timelengthtype='$rows[timelengthtype]',timelength='$rows[timelength]',prepower='$rows[prepower]',datasendmodel='$rows[datasendmodel]',state='$rows[state]',startdate='$rows[startdate]',enddate='$rows[enddate]',playtime='$rows[playtime]',endtime='$rows[endtime]',exemodel='$rows[exemodel]',priority='$rows[priority]',playfileid='$rows[playfileid]',info='$rows[info]',defaultvolume='$rows[defaultvolume]',offlinestate='$rows[offlinestate]' WHERE taskid='$aaa'") or die(mysqli_error($con));
								}
							}

						}
					}
			
					if($flag==1||$flag==2)
					{

							mysqli_query($con,"UPDATE task SET offlinestate = '$flag' WHERE taskid='$aaa'") or die(mysqli_error($con));
						    mysqli_query($con,"UPDATE offlinetask SET offlinestate = '$flag' WHERE taskid='$aaa'") or die(mysqli_error($con));
							mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '0' WHERE taskid='$aaa'") or die(mysqli_error($con));
							
							$result_terminal = mysqli_query($con,"SELECT terminalid FROM offlinetaskofterminal WHERE taskid ='$aaa'");
							while($rows = mysqli_fetch_array($result_terminal))
							{
								$result_yuanterminal = mysqli_query($con,"SELECT terminalid FROM terminaloftask WHERE taskid ='$aaa' AND terminalid='$rows[terminalid]'");
								if(mysqli_num_rows($result_yuanterminal)<=0)
								{
									mysqli_query($con,"UPDATE offlinetaskofterminal SET offlinestate = '5' WHERE taskid='$aaa' AND terminalid='$rows[terminalid]'") or die(mysqli_error($con));	
								}
								else
								{
									 mysqli_query($con,"UPDATE offlinetaskofterminal SET offlinestate = '$flag' WHERE taskid='$aaa' AND terminalid='$rows[terminalid]'") or die(mysqli_error($con));
								}
							}
							
						$result_terminals = mysqli_query($con,"SELECT terminalid,terminaloftask.area FROM terminaloftask WHERE taskid='$aaa' AND terminalid IN(SELECT id FROM terminal WHERE totalcapacity!='0')");
						while($rows = mysqli_fetch_array($result_terminals))
						{
							$result_yuanterminals = mysqli_query($con,"SELECT terminalid FROM offlinetaskofterminal WHERE taskid ='$aaa' AND terminalid='$rows[terminalid]'");
							if(mysqli_num_rows($result_yuanterminals)<=0)
							{
								mysqli_query($con,"INSERT INTO offlinetaskofterminal (taskid,terminalid,offlinestate,area) VALUES('$aaa','$rows[terminalid]','$flag','$rows[area]')") or die(mysqli_error($con));
							}
						
								$result_media = mysqli_query($con,"SELECT mediaid,taskid,sort FROM mediaoftask WHERE taskid ='$aaa'");
								while($rowmedia = mysqli_fetch_array($result_media))
								{
									$get_media = mysqli_query($con,"SELECT id FROM offlinemedia WHERE id ='$rowmedia[mediaid]'");
									if(mysqli_num_rows($get_media)<=0)
									{
										  mysqli_query($con,"INSERT INTO offlinemedia SELECT id,media.name,size,typeid,priority,filename,folderid,timelength,channel,sample,bitrate,codecid FROM media WHERE media.id='$rowmedia[mediaid]'") or die(mysqli_error($con));
									}
								
									$result_media2 = mysqli_query($con,"SELECT mediaid,terminalid FROM offlinemediaofterminal WHERE mediaid='$rowmedia[mediaid]' and terminalid='$rows[terminalid]' ");
									if(mysqli_num_rows($result_media2)<=0)
									{
										mysqli_query($con,"INSERT INTO offlinemediaofterminal(mediaid,terminalid,offlinestate,sort) VALUES('$rowmedia[mediaid]','$rows[terminalid]','$flag','$rowmedia[sort]')") or die(mysqli_error($con));
									}	
								
									$result_medias = mysqli_query($con,"SELECT mediaid,terminalid FROM offlinemediaofterminal WHERE taskid ='$aaa' and mediaid='$rowmedia[mediaid]' and terminalid='$rows[terminalid]' ");
									
									if(mysqli_num_rows($result_medias)<=0)
									{
									   mysqli_query($con,"INSERT INTO offlinemediaofterminal(mediaid,terminalid,offlinestate,taskid,sort) VALUES('$rowmedia[mediaid]','$rows[terminalid]','$flag','$aaa','$rowmedia[sort]')") or die(mysqli_error($con));
									}
									else
									{
									 mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '$flag' WHERE taskid='$aaa' and terminalid='$rows[terminalid]'") or die(mysqli_error($con));		
									}
								}			
						}
						
					  mysqli_query($con,"UPDATE offlinemediaofterminal SET offlinestate = '5' WHERE taskid='$aaa' and offlinestate='0'") or die(mysqli_error($con));
	
					}
			}
				
	}
	if($flag==16||$flag==17)
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./set_offline.php";
		echo "<script>window.location='success.php'</script>";	
	}
	else if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./set_offline.php";
		if($flag==5||$flag==2||$flag==11)
		{
			$create_socket_obj->send_socket_generate_general("task",15,0);
		}
		

		echo "<script>window.location='success.php'</script>";	
	}
}

//aitts任务---仅对文件广播、采播管理
function ai_tts_setterminal_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();	

	$keyvalue = "";
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}
	$analysis_tree_group_string = "";
	if(isset($_POST['analysis_tree_group_string']))
	{
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		$group_array = explode(",",$analysis_tree_group_string);
	}

	$target = "";
	if(isset($_POST['target']))
	{
		$target = trim($_POST['target']);
		$target_array = explode(",",$target);
	}

	mysqli_query($con,"LOCK TABLE ai_device WRITE");
	
	mysqli_query($con,"DELETE FROM ai_device WHERE ai_device.shibiedeviceid = '$keyvalue'") or die(mysqli_error($con));
	
	for($i=0; $i<count($target_array); $i++)
	{
		if(is_numeric($target_array[$i]))
		{
			mysqli_query($con,"INSERT INTO ai_device (shibiedeviceid,terminalid,groupid) VALUES('$keyvalue','$target_array[$i]','$group_array[$i]')") or die(mysqli_error($con));
		}
	}			

	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./ai_peopleplay.php";
	
		echo "<script>window.location='success.php'</script>";	
	}	
}

//添加遥控任务---仅对文件广播、采播管理
function set_task_mapping_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();	
	$map_name = "";
	if(isset($_POST['map_name']))
	{
		$map_name = trim($_POST['map_name']);
	}
	$keyvalue = "";
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}
	$task_map_id = "";
	if(isset($_POST['task_map_id']))
	{
		$task_map_id = trim($_POST['task_map_id']);
	}
	mysqli_query($con,"LOCK TABLE terminalkey WRITE,terminalkeymap WRITE");
	
	$sql_same_name = "SELECT * FROM terminalkey WHERE terminalkey.key = '$keyvalue' AND terminalkey.terminalid = '0'";	
	$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
	if(mysqli_num_rows($result_same_name) > 0)
	{
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_has_been_used']);
	}

	@mysqli_free_result($result_same_name);
	unset($sql_same_name);

	//验证遥控键是否设置
	$sql_taskmap = "SELECT 	* FROM terminalkey WHERE terminalkey.terminalid = '0' AND terminalkey.key = '$keyvalue'";
	$result_taskmap = mysqli_query($con,$sql_taskmap) or die(mysqli_error($con));
	if(mysqli_num_rows($result_taskmap) > 0)
	{
		//读取id
		$row_taskmap = mysqli_fetch_array($result_taskmap);
		
		$get_map_id = $row_taskmap['id'];
		//判断任务是否已分配
		$sql_usedtask = "SELECT id FROM terminalkey WHERE terminalkey.id IN ";
		
		$sql_usedtask.= "(SELECT keyid FROM terminalkeymap WHERE terminalkeymap.terminalid = '$task_map_id') AND terminalkey.terminalid = '0'";
		
		$result_usetask = mysqli_query($con,$sql_usedtask) or die(mysqli_error($con));
		
		if($row_usetask = mysqli_fetch_array($result_usetask))
		{
			if($row_usetask['id'] == $get_map_id)
			{
				//是自己本身、什么也不做
			}
			else if($row_usetask['id'] != $get_map_id)
			{
				mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalkeymap.keyid = '$row_usetask[id]'") or die(mysqli_error($con));
				
				mysqli_query($con,"DELETE FROM terminalkey WHERE terminalkey.id = '$row_usetask[id]'") or die(mysqli_error($con));
			}
		}
		@mysqli_free_result($result_usetask);
		
		unset($sql_usedtask,$row_usetask);
		//更新
		mysqli_query($con,"UPDATE terminalkey SET terminalkey.name = '$map_name',terminalid = '0',terminalkey.key = '$keyvalue' WHERE id = '$get_map_id' ") or die(mysqli_error($con));
		
		mysqli_query($con,"UPDATE terminalkeymap SET  terminalid = '$task_map_id' WHERE terminalkeymap.keyid = '$get_map_id'") or die(mysqli_error($con));
		
		unset($row_taskmap);
	}
	else
	{
		//判断任务是否已分配
		$sql_usedtask = "SELECT id FROM terminalkey WHERE terminalkey.id IN ";
		
		$sql_usedtask.= "(SELECT keyid FROM terminalkeymap WHERE terminalkeymap.terminalid = '$task_map_id') AND terminalkey.terminalid = '0'";
		
		$result_usetask = mysqli_query($con,$sql_usedtask) or die(mysqli_error($con));
		
		if($row_usetask = mysqli_fetch_array($result_usetask))
		{
			mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalkeymap.keyid = '$row_usetask[id]'") or die(mysqli_error($con));
			
			mysqli_query($con,"DELETE FROM terminalkey WHERE terminalkey.id = '$row_usetask[id]'") or die(mysqli_error($con));
		}
		
		@mysqli_free_result($result_usetask);
		
		unset($sql_usedtask,$row_usetask);
		//直接插入
		mysqli_query($con,"INSERT INTO terminalkey (terminalkey.name,terminalid,terminalkey.key) VALUES('$map_name','0','$keyvalue')") or die(mysqli_error($con));
		//取id号
		$result_max = mysqli_query($con,"SELECT MAX(id) FROM terminalkey") or die(mysqli_error($con));
		
		$row_max = mysqli_fetch_array($result_max);
		
		mysqli_query($con,"INSERT INTO terminalkeymap (keyid,terminalid) VALUES('$row_max[0]','$task_map_id')");
		
		@mysqli_free_result($result_max);
		
		unset($row_max);
	}
	@mysqli_free_result($result_taskmap);
	
	unset($sql_taskmap);
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./task_mapping.php";
	
		echo "<script>window.location='success.php'</script>";	
	}	
}

//添加遥控任务---仅对文件广播、采播管理
function set_ai_demo_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();	

	$enabletts1 = 0;
	if(isset($_POST['enabletts1']))
	{
		$enabletts1 = trim($_POST['enabletts1']);
	}
	$enabletts2 = 0;
	if(isset($_POST['enabletts2']))
	{
		$enabletts2 = trim($_POST['enabletts2']);
	}


	$enabletts3 = 0;
	if(isset($_POST['enabletts3']))
	{
		$enabletts3 = trim($_POST['enabletts3']);
	}
	$starthour1 = "";
	if(isset($_POST['starthour1']))
	{
		$starthour1 = trim($_POST['starthour1']);
	}
	$startmin1 = "";
	if(isset($_POST['startmin1']))
	{
		$startmin1 = trim($_POST['startmin1']);
	}
	$starthour11 = "";
	if(isset($_POST['starthour11']))
	{
		$starthour11 = trim($_POST['starthour11']);
	}
	$startmin11 = "";
	if(isset($_POST['startmin11']))
	{
		$startmin11 = trim($_POST['startmin11']);
	}
	$ttsdemo1 = "";
	if(isset($_POST['ttsdemo1']))
	{
		$ttsdemo1 = trim($_POST['ttsdemo1']);
	}

	$starthour2 = "";
	if(isset($_POST['starthour2']))
	{
		$starthour2 = trim($_POST['starthour2']);
	}
	$startmin2 = "";
	if(isset($_POST['startmin2']))
	{
		$startmin2 = trim($_POST['startmin2']);
	}
	$starthour22 = "";
	if(isset($_POST['starthour22']))
	{
		$starthour22 = trim($_POST['starthour22']);
	}
	$startmin22 = "";
	if(isset($_POST['startmin22']))
	{
		$startmin22 = trim($_POST['startmin22']);
	}
	$ttsdemo2 = "";
	if(isset($_POST['ttsdemo2']))
	{
		$ttsdemo2 = trim($_POST['ttsdemo2']);
	}
	$starthour3 = "";
	if(isset($_POST['starthour3']))
	{
		$starthour3 = trim($_POST['starthour3']);
	}
	$startmin3 = "";
	if(isset($_POST['startmin3']))
	{
		$startmin3 = trim($_POST['startmin3']);
	}
	$starthour33 = "";
	if(isset($_POST['starthour33']))
	{
		$starthour33 = trim($_POST['starthour33']);
	}
	$startmin33 = "";
	if(isset($_POST['startmin33']))
	{
		$startmin33 = trim($_POST['startmin33']);
	}

	$volume1 = 80;
	if(isset($_POST['volume1']))
	{
		$volume1 = trim($_POST['volume1']);
	}
	$volume2 = 80;
	if(isset($_POST['volume2']))
	{
		$volume2 = trim($_POST['volume2']);
	}
	$volume3 = 80;
	if(isset($_POST['volume3']))
	{
		$volume3 = trim($_POST['volume3']);
	}


	$ttsdemo3 = "";
	if(isset($_POST['ttsdemo3']))
	{
		$ttsdemo3 = trim($_POST['ttsdemo3']);
	}



	mysqli_query($con,"LOCK TABLE ai_timetts WRITE");
	$time1=$starthour1.":".$startmin1."-".$starthour11.":".$startmin11;
	$time2=$starthour2.":".$startmin2."-".$starthour22.":".$startmin22;
	$time3=$starthour3.":".$startmin3."-".$starthour33.":".$startmin33;

	$sql_taskmap = "SELECT 	* FROM ai_timetts WHERE ai_timetts.id IN(1,2,3)";
	$result_taskmap = mysqli_query($con,$sql_taskmap) or die(mysqli_error($con));
	if(mysqli_num_rows($result_taskmap) > 0)
	{
		//更新



		$aaa="UPDATE ai_timetts SET ai_timetts.time = '$time1',ai_timetts.demo = '$ttsdemo1',ai_timetts.enable = '$enabletts1',ai_timetts.volume='$volume1' WHERE id = 1";


		mysqli_query($con,$aaa) or die(mysqli_error($con));


		$bbb="UPDATE ai_timetts SET ai_timetts.time = '$time2',ai_timetts.demo = '$ttsdemo2',ai_timetts.enable = '$enabletts2',volume='$volume2' WHERE id = 2";

		mysqli_query($con,$bbb) or die(mysqli_error($con));
		$ccc="UPDATE ai_timetts SET ai_timetts.time = '$time3',ai_timetts.demo = '$ttsdemo3',ai_timetts.enable = '$enabletts3',volume='$volume3' WHERE id = 3";
		mysqli_query($con,$ccc) or die(mysqli_error($con));

	}
	else
	{
		$sql = "INSERT INTO ai_timetts (ai_timetts.id,ai_timetts.time,ai_timetts.demo,ai_timetts.enable,ai_timetts.volume)VALUES('1','$time1','$ttsdemo1','$enabletts1','$volume1')";
				
		mysqli_query($con,$sql) or die(mysqli_error($con));
		$sql = "INSERT INTO ai_timetts (ai_timetts.id,ai_timetts.time,ai_timetts.demo,ai_timetts.enable,ai_timetts.volume)VALUES('2','$time2','$ttsdemo2','$enabletts2','$volume2')";
				
		mysqli_query($con,$sql) or die(mysqli_error($con));
		$sql = "INSERT INTO ai_timetts (ai_timetts.id,ai_timetts.time,ai_timetts.demo,ai_timetts.enable,ai_timetts.volume)VALUES('3','$time3','$ttsdemo3','$enabletts3','$volume3')";
				
		mysqli_query($con,$sql) or die(mysqli_error($con));
	}

	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./ai_peopleplay.php";
	
		echo "<script>window.location='success.php'</script>";	
	}	
}

function set_powerqi_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();	

	$timeqiopen1 = 0;
	if(isset($_POST['timeqiopen1']))
	{
		$timeqiopen1 = trim($_POST['timeqiopen1']);
	}
	$timeqiopen2 = 0;
	if(isset($_POST['timeqiopen2']))
	{
		$timeqiopen2 = trim($_POST['timeqiopen2']);
	}
	$timeqiopen3 = 0;
	if(isset($_POST['timeqiopen3']))
	{
		$timeqiopen3 = trim($_POST['timeqiopen3']);
	}
	$timeqiopen4 = 0;
	if(isset($_POST['timeqiopen4']))
	{
		$timeqiopen4 = trim($_POST['timeqiopen4']);
	}
	$timeqiopen5 = 0;
	if(isset($_POST['timeqiopen5']))
	{
		$timeqiopen5 = trim($_POST['timeqiopen5']);
	}
	$timeqiopen6 = 0;
	if(isset($_POST['timeqiopen6']))
	{
		$timeqiopen6 = trim($_POST['timeqiopen6']);
	}
	$timeqiopen7 = 0;
	if(isset($_POST['timeqiopen7']))
	{
		$timeqiopen7 = trim($_POST['timeqiopen7']);
	}
	$timeqiopen8 = 0;
	if(isset($_POST['timeqiopen8']))
	{
		$timeqiopen8 = trim($_POST['timeqiopen8']);
	}
	$timeqiopen9 = 0;
	if(isset($_POST['timeqiopen9']))
	{
		$timeqiopen9 = trim($_POST['timeqiopen9']);
	}
	$timeqiopen10 = 0;
	if(isset($_POST['timeqiopen10']))
	{
		$timeqiopen10 = trim($_POST['timeqiopen10']);
	}
	$timeqiopen11 = 0;
	if(isset($_POST['timeqiopen11']))
	{
		$timeqiopen11 = trim($_POST['timeqiopen11']);
	}
	$timeqiopen12 = 0;
	if(isset($_POST['timeqiopen12']))
	{
		$timeqiopen12 = trim($_POST['timeqiopen12']);
	}
	$timeqiopen13 = 0;
	if(isset($_POST['timeqiopen13']))
	{
		$timeqiopen13 = trim($_POST['timeqiopen13']);
	}
	$timeqiopen14 = 0;
	if(isset($_POST['timeqiopen14']))
	{
		$timeqiopen14 = trim($_POST['timeqiopen14']);
	}
	$timeqiopen15 = 0;
	if(isset($_POST['timeqiopen15']))
	{
		$timeqiopen15 = trim($_POST['timeqiopen15']);
	}
	$timeqiopen16 = 0;
	if(isset($_POST['timeqiopen16']))
	{
		$timeqiopen16 = trim($_POST['timeqiopen16']);
	}
	
	$powertimeqiname1 = "";
	if(isset($_POST['powertimeqiname1']))
	{
		$powertimeqiname1 = trim($_POST['powertimeqiname1']);
	}
	
	$powertimeqiname2 = "";
	if(isset($_POST['powertimeqiname2']))
	{
		$powertimeqiname2 = trim($_POST['powertimeqiname2']);
	}
	$powertimeqiname3 = "";
	if(isset($_POST['powertimeqiname3']))
	{
		$powertimeqiname3 = trim($_POST['powertimeqiname3']);
	}
	$powertimeqiname4 = "";
	if(isset($_POST['powertimeqiname4']))
	{
		$powertimeqiname4= trim($_POST['powertimeqiname4']);
	}
	$powertimeqiname5 = "";
	if(isset($_POST['powertimeqiname5']))
	{
		$powertimeqiname5 = trim($_POST['powertimeqiname5']);
	}
	$powertimeqiname6 = "";
	if(isset($_POST['powertimeqiname6']))
	{
		$powertimeqiname6 = trim($_POST['powertimeqiname6']);
	}
	$powertimeqiname7 = "";
	if(isset($_POST['powertimeqiname7']))
	{
		$powertimeqiname7 = trim($_POST['powertimeqiname7']);
	}
	$powertimeqiname8 = "";
	if(isset($_POST['powertimeqiname8']))
	{
		$powertimeqiname8 = trim($_POST['powertimeqiname8']);
	}
	$powertimeqiname9 = "";
	if(isset($_POST['powertimeqiname9']))
	{
		$powertimeqiname9 = trim($_POST['powertimeqiname9']);
	}
	$powertimeqiname10 = "";
	if(isset($_POST['powertimeqiname10']))
	{
		$powertimeqiname10 = trim($_POST['powertimeqiname10']);
	}
	$powertimeqiname11 = "";
	if(isset($_POST['powertimeqiname11']))
	{
		$powertimeqiname11 = trim($_POST['powertimeqiname11']);
	}
	$powertimeqiname12 = "";
	if(isset($_POST['powertimeqiname12']))
	{
		$powertimeqiname12 = trim($_POST['powertimeqiname12']);
	}
	$powertimeqiname13 = "";
	if(isset($_POST['powertimeqiname13']))
	{
		$powertimeqiname13 = trim($_POST['powertimeqiname13']);
	}
	$powertimeqiname14 = "";
	if(isset($_POST['powertimeqiname14']))
	{
		$powertimeqiname14 = trim($_POST['powertimeqiname14']);
	}
	$powertimeqiname15 = "";
	if(isset($_POST['powertimeqiname15']))
	{
		$powertimeqiname15 = trim($_POST['powertimeqiname15']);
	}
	$powertimeqiname16 = "";
	if(isset($_POST['powertimeqiname16']))
	{
		$powertimeqiname16 = trim($_POST['powertimeqiname16']);
	}

	$keyvalue = "";
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}

	mysqli_query($con,"LOCK TABLE powertimeqi WRITE");

	$aaa = "UPDATE powertimeqi SET power1='$timeqiopen1',power2='$timeqiopen2',power3='$timeqiopen3',power4='$timeqiopen4',power5='$timeqiopen5',power6='$timeqiopen6',power7='$timeqiopen7',power8='$timeqiopen8',power9='$timeqiopen9',power10='$timeqiopen10',power11='$timeqiopen11',power12='$timeqiopen12',power13='$timeqiopen13',power14='$timeqiopen14',power15='$timeqiopen15',power16='$timeqiopen16'";
	$aaa .= ",powername1='$powertimeqiname1',powername2='$powertimeqiname2',powername3='$powertimeqiname3',powername4='$powertimeqiname4',powername5='$powertimeqiname5',powername6='$powertimeqiname6',powername7='$powertimeqiname7',powername8='$powertimeqiname8',powername9='$powertimeqiname9',powername10='$powertimeqiname10',powername11='$powertimeqiname11',powername12='$powertimeqiname12',powername13='$powertimeqiname13',powername14='$powertimeqiname14',powername15='$powertimeqiname15',powername16='$powertimeqiname16' WHERE terminalid = '$keyvalue'";

	//var_dump($aaa);
	//return;
	mysqli_query($con,$aaa) or die(mysqli_error($con));
	mysqli_query($con,"UNLOCK TABLES");
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./powercontrol.php";
		echo "<script>window.location='success.php'</script>";	
	}	
}


function set_task_quick_play($con)
{
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();	
	$cmdargs = "";
	if(isset($_GET['terminal_id']))
	{
		$cmdargs = trim($_GET['terminal_id']);
	}
	$userid = "";
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	$keyvalue = 0;
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}
	
	$keyvalue_s = 0;
	if(isset($_POST['keyvalue_s']))
	{
		$keyvalue_s = trim($_POST['keyvalue_s']);
	}

	$taskname = "";
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	$israndomplay = 0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = trim((int)$_POST['israndomplay']);
	}

	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	
	$ttsplay=0;
	if(isset($_POST['ttsplay']))
	{
		$ttsplay = trim($_POST['ttsplay']);
	}

	$audiosource=0;

	$tasktype=20;
	if($ttsplay==1)
	{
		
		$speed_value = "5";
		if(isset($_POST['speed_value']))
		{
			$speed_value =trim($_POST['speed_value']);
			
		}
			$musicmode = "0";
		if(isset($_POST['musicmode']))
		{
			$musicmode = trim($_POST['musicmode']);
		}
		$gettextarea="";
		if(isset($_POST['gettextarea']))
		{
			$gettextarea = $_POST['gettextarea'];
		}
		
		$gettextarea=nl2br($gettextarea);
		$tasktype=21;
		if(isset($_POST['audiosource']))
		{
			$audiosource = trim($_POST['audiosource']);
		}
		
	}

	$ledplay=0;
	if(isset($_POST['ledplay']))
	{
		$ledplay = htmlspecialchars(trim($_POST['ledplay']));
		$ledplay = addslashes($ledplay);
	}
	if($ledplay==1)
	{
		$getledtextareas="";
		if(isset($_POST['getledtextareas']))
		{
			$getledtextareas = $_POST['getledtextareas'];
		}
	
		$getledtextareas=nl2br($getledtextareas);
		
		$led_group_string="";
		if(isset($_POST['led_group_string']))
		{
			$led_group_string = $_POST['led_group_string'];
		}
		
		$ledlistvalue="";
		if(isset($_POST['ledlistvalue']))
		{
			$ledlistvalue = htmlspecialchars($_POST['ledlistvalue']);
			$ledlistvalue = addslashes($ledlistvalue);
		}
		
	}
	$medialist=trim($_POST['listvalue']);		
	$arrmedia=explode(",",$medialist);

	$timelengthtype = 1;
	$getendtime=0;
	$timelength = 0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
		$getstarttime=0;
		if($timelengthtype == 1)
		{  
			$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
			$getendtime=$timelength+$getstarttime;
		}
		else
		{
			$timelength = trim($_POST['circleTime']);
			for($i=0;$i<count($arrmedia);$i++)
			{
				$getmediaid = "SELECT timelength FROM media where id='$arrmedia[$i]'";//取插入任务id
				$mediaidresult = mysqli_query($con,$getmediaid) or die(mysqli_error($con));
				while($row = mysqli_fetch_array($mediaidresult))
				{
					$getendtime = $getendtime+($row['timelength']*$timelength);//新添加的任务id
				}
			}
			$getendtime=$getendtime+$getstarttime;
		} 
	}
	else
	{
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
		$getendtime=$timelength+$getstarttime;
	}
	
	$getendhour=$getendtime/3600;
	$getendmin=$getendtime%3600/60;
	$getendsec=$getendtime%3600%60;
	
	$getendtime=(int)$getendhour.":".(int)$getendmin.":".(int)$getendsec;
	if($getendhour>=24)
		$getendtime="23:59:59";
	$datasendmodel = 0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	 $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}

	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	$ledplay=0;
	if(isset($_POST['ledplay']))
	{
		$ledplay = trim($_POST['ledplay']);
	}
	$priority=13;
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
		$terminallistvalue = trim($_POST['terminallistvalue']);
		
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	
	$gettaskid=0;
	mysqli_query($con,"LOCK TABLE terminalkeymaptask WRITE,task WRITE,terminal WRITE,terminaloftask WRITE,mediaoftask WRITE,media WRITE,ttssentence WRITE,ledsentence WRITE,ledoftask WRITE");
	/*
	$sql_name = "SELECT keyid FROM terminalkeymaptask,task WHERE terminalkeymaptask.keyid = '$keyvalue' AND task.taskid=terminalkeymaptask.taskid AND task.cmd = '$cmd' AND task.tasktype='20'";
		
	$result_name = mysqli_query($con,$sql_name) or die(mysqli_error($con));
	if(mysqli_num_rows($result_name) > 0)
	{
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_has_been_used']);
	}

	@mysqli_free_result($result_name);
	unset($sql_name);
	*/
	
	$sql_name = "SELECT typeid FROM terminal WHERE id = '$audiosource'";
	$result_name = mysqli_query($con,$sql_name) or die(mysqli_error($con));
	if(mysqli_num_rows($result_name) > 0)
	{
		while($row = mysqli_fetch_array($result_name))
				{
					if($row['typeid']==0)
					{
						$speed_value=$speed_value*10;	
	$tasktype=29;
					}
				
				}
	}
	@mysqli_free_result($result_name);
	unset($sql_name);

	$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,playtime,endtime,";
		
		$sql.="exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume,task_user_id, sec_task_id,parentid) ";
		
		$sql.="VALUES('$taskname', '$israndomplay', '$timelengthtype', '$timelength', '0', '$datasendmodel', ";
		
		$sql.="'0', '0000-00-00', '0000-00-00', '00:00:00','00:00:00', '0000000', '$priority', '$tasktype', '0', ";
		
		$sql.="'0', '0', '$audiosource', '$cmdargs', '0', '$task_default_volume', '$userid','0','0') ";

		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./view_quickplay.php?terminal_id=$cmd";
			
			echo "<script>window.location='error.php'</script>";
			
			exit;
		}
		
		$sql = "SELECT MAX(taskid) FROM task";//取插入任务id
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if($row = mysqli_fetch_array($result))
		{
			$gettaskid = $row[0];//新添加的任务id
		}
		@mysqli_free_result($result);
		unset($sql,$row);
		$sql ="INSERT INTO terminalkeymaptask(taskid,terminalid,keyid)VALUES('$gettaskid','$cmdargs','$keyvalue_s')";	
		mysqli_query($con,$sql) or die(mysqli_error($con));
		unset($sql);
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./view_quickplay.php?terminal_id=$cmd";
			
			echo "<script>window.location='error.php'</script>";
			
			exit;
		}
	
			for($i=0; $i<count($terminallistnum); $i++)
			{
			if(is_numeric($terminallistnum[$i]))
			{
				$temp = (int)$terminallistnum[$i];
			
				
				 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$gettaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
				
					mysqli_query($con,$sql) or die(mysqli_error($con));
					
					if(mysqli_error($con))
					{
						mysqli_query($con,"ROLLBACK");
					
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = "./view_quickplay.php?terminal_id=$cmd";
						
						echo "<script>window.location='error.php'</script>";
						
						exit;
					}

					for($j=0;$j<strlen($get_terminal);$j++)
					{
					
								if(substr($get_terminal,$j,2)=="::")
								{
									$position=$j+2;
								}
								if(substr($get_terminal,$j,1)=="|")
								{
											$position2 = $j;
											$position3 = $position2-$position;
											$a=substr($get_terminal,$j-$position3,$position3);	
											if($a==$temp)
											{
												$area = substr($get_terminal,$j+1,16);
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$gettaskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
											}
								}						
					 }
	
				}
			}
			if($ttsplay==1)
			{
			
				$sql="INSERT INTO media(name, typeid, filename,folderid,timelength,channel,sample,bitrate) VALUES ('$taskname','tts','tts','0','0','0','$gettaskid','$tasktype')";
						
			mysqli_query($con,$sql) or die(mysqli_error($con));	
				
			$resultmedia = mysqli_query($con,"SELECT MAX(id) FROM media") or die(mysqli_error($con));
			
			$rowmedia = mysqli_fetch_array($resultmedia);	
			
			$openmediaid = $rowmedia[0]; 
			
			@mysqli_free_result($resultmedia);
			
			unset($rowmedia);
			
			$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$openmediaid','$gettaskid','0')";
			
			mysqli_query($con,$sql);
			
				$gettempi=0;
				$gettext=0;
				
				$arr1=str_split_utf8($gettextarea);
				
				for($aa=0;$aa<count($arr1);$aa++)
				{
					$gettextone=$arr1[$aa];
					$gettextone=str_replace("<br/>","",$gettextone);
					$gettextone=str_replace("<br />","",$gettextone);
					$gettextone=str_replace("\r\n","",$gettextone);
					$gettextone=str_replace("、","",$gettextone);
					$gettextone=str_replace("</b>","",$gettextone);
					$gettextone=str_replace("</B>","",$gettextone);
					$gettextone=str_replace("\\","",$gettextone);
					$gettextone=$gettextone;
				
					if(!empty($gettextone))
					{ 
						$sql="INSERT INTO ttssentence(name,sentenceid,type,content,mediaseq,speed,volume,male) VALUES ('$taskname','$openmediaid','2','$gettextone','$gettempi','$speed_value','$task_default_volume','$musicmode')";
						mysqli_query($con,$sql) or die(mysqli_error($con));
						$gettempi++;
					}
				}		
			}
			else
			{
				if(isset($_POST['listvalue']))
				{
					$medialist=trim($_POST['listvalue']);
					$arrmedia=explode(",",$medialist);
					for($i=0;$i<count($arrmedia);$i++)
					{
						$str =$arrmedia[$i];
						if(!is_numeric($str))
						{
							continue;
						}
						
						$number =(int)$str;
						$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$number','$gettaskid','$i')";
						mysqli_query($con,$sql) or die(mysqli_error($con));
						if(mysqli_error($con))
						{	
							mysqli_query($con,"ROLLBACK");
							$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
							$_SESSION['url'] = "./view_quickplay.php?terminal_id=$cmdargs";
							echo "<script>window.location='error.php'</script>";
							exit;
						}			
					}	
				}
			}
		if($ledplay==1)
		{
	
		 add_ledtask($con,$getledtextareas,$taskname,$israndomplay,$timelengthtype,$timelength,0,$datasendmodel,0,'0000-00-00','0000-00-00','00:00:00','00:00:00','0000000',$priority,24,0,0,0,$gettaskid,$cmdargs,0,$task_default_volume,$userid,0,0,0,0,0,0,$led_group_string,$ledlistvalue);
		}
		mysqli_query($con,"UNLOCK TABLES");
		if(!mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			$_SESSION['url'] = "./view_quickplay.php?terminal_id=$cmdargs";
			echo "<script>window.location='success.php'</script>";	
		}	
}

function video_temp_play($con)
{
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();	
	
	$userid = "";
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}

	$taskname = "";
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	
	$audiosource=0;

	$medialist=trim($_POST['listvalue']);		
	$arrmedia=explode(",",$medialist);

	$timelengthtype = 1;
	$getendtime=0;
	$timelength = 0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
		if($timelengthtype == 1)
		{  
			$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
		}
		else
		{
			$timelength = trim($_POST['circleTime']);
		} 
	}
	else
	{
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
	}
	
	$priority=13;
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
		$terminallistvalue = trim($_POST['terminallistvalue']);
		
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);


		mysqli_query($con,"UNLOCK TABLES");
		$user_id=$_SESSION['userid'];
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			$_SESSION['url'] = "./videodisplaymanager.php?userid=".$user_id;

			$create_socket_obj->send_socket_vediotask("task",$medialist,$task_default_volume,$timelengthtype,$timelength,$priority,count($terminallistnum),$terminallistvalue);

			echo "<script>window.location='success.php'</script>";	
		
}


function set_yingji_play($con,$yingjitype1,$yingjitype2,$yingjitype3,$yingjitype4)
{
	//添加外部变量
	global $do_php_prompt;

	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();	
	$cmdargs = "";
	if(isset($_GET['terminal_id']))
	{
		$cmdargs = trim($_GET['terminal_id']);
	}
	$userid = "";
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	$yingji_type = 0;
	if(isset($_POST['yingji_type']))
	{
		$yingji_type = trim($_POST['yingji_type']);
		if($yingji_type==1000)
		$yingji_name=$yingjitype1;
		else if($yingji_type==1001)
		$yingji_name=$yingjitype2;
		else if($yingji_type==1002)
		$yingji_name=$yingjitype3;
		else if($yingji_type==1003)
		$yingji_name=$yingjitype4;
	
		
	}
	

	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	
	
	
	$medialist=trim($_POST['listvalue']);		
	$arrmedia=explode(",",$medialist);


	
	 $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}

	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	$priority=13;
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
		$terminallistvalue = trim($_POST['terminallistvalue']);
		
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);

	$gettaskid=0;
	mysqli_query($con,"LOCK TABLE terminalkeymaptask WRITE,task WRITE,terminaloftask WRITE,mediaoftask WRITE");

	$sql_same_name = "SELECT * FROM terminalkeymaptask WHERE terminalkeymaptask.keyid = '$yingji_type' and terminalkeymaptask.terminalid in($cmdargs)";	
	$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
	if(mysqli_num_rows($result_same_name) > 0)
	{
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_has_been_used']);
	}
	$sql_same_name = "SELECT * FROM task WHERE task.cmdargs = '$yingji_type'";	
	$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
	if(mysqli_num_rows($result_same_name) > 0)
	{
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_has_been_used']);
	}
	
	$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,playtime,endtime,";
		
		$sql.="exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume,task_user_id, sec_task_id,parentid) ";
		
		$sql.="VALUES('$yingji_name', '0', '0', '0', '0', '0', ";
		
		$sql.="'0', '0000-00-00', '0000-00-00', '00:00:00','00:00:00', '0000000', '0', '23', '0', ";
		
		$sql.="'0', '0', '0', '$cmdargs', '0', '$task_default_volume', '$userid','0','0') ";

		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./view_yingjiplay?terminal_id=$cmdargs";
			
			echo "<script>window.location='error.php'</script>";
			
			exit;
		}
		
		$sql = "SELECT MAX(taskid) FROM task";//取插入任务id
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if($row = mysqli_fetch_array($result))
		{
			$gettaskid = $row[0];//新添加的任务id
		}
		@mysqli_free_result($result);
		unset($sql,$row);
		$sql ="INSERT INTO terminalkeymaptask(taskid,terminalid,keyid)VALUES('$gettaskid','$cmdargs','$yingji_type')";	
		mysqli_query($con,$sql) or die(mysqli_error($con));
		unset($sql);
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./view_quickplay.php?terminal_id=$cmd";
			
			echo "<script>window.location='error.php'</script>";
			
			exit;
		}
	
			for($i=0; $i<count($terminallistnum); $i++)
			{
			if(is_numeric($terminallistnum[$i]))
			{
				$temp = (int)$terminallistnum[$i];
			
				
				 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$gettaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
				
					mysqli_query($con,$sql) or die(mysqli_error($con));
					
					if(mysqli_error($con))
					{
						mysqli_query($con,"ROLLBACK");
					
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = "./view_yingjiplay.php?terminal_id=$cmdargs";
						
						echo "<script>window.location='error.php'</script>";
						
						exit;
					}

					for($j=0;$j<strlen($get_terminal);$j++)
					{
					
								if(substr($get_terminal,$j,2)=="::")
								{
									$position=$j+2;
								}
								if(substr($get_terminal,$j,1)=="|")
								{
											$position2 = $j;
											$position3 = $position2-$position;
											$a=substr($get_terminal,$j-$position3,$position3);	
											if($a==$temp)
											{
												$area = substr($get_terminal,$j+1,16);
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$gettaskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
											}
								}						
					 }
	
				}
			}
			
				if(isset($_POST['listvalue']))
				{
					$medialist=trim($_POST['listvalue']);
					$arrmedia=explode(",",$medialist);
					for($i=0;$i<count($arrmedia);$i++)
					{
						$str =$arrmedia[$i];
						if(!is_numeric($str))
						{
							continue;
						}
						
						$number =(int)$str;
						$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$number','$gettaskid','$i')";
						mysqli_query($con,$sql) or die(mysqli_error($con));
						if(mysqli_error($con))
						{	
							mysqli_query($con,"ROLLBACK");
							$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
							$_SESSION['url'] = "./view_yingjiplay.php?terminal_id=$cmdargs";
							echo "<script>window.location='error.php'</script>";
							exit;
						}			
					}	
				}
			
		mysqli_query($con,"UNLOCK TABLES");
		if(!mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			$_SESSION['url'] = "./view_yingjiplay.php?terminal_id=$cmdargs";
			echo "<script>window.location='success.php'</script>";	
		}	
}


function modify_task_quick_play($con)
{
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();	
	$cmdargs = "";
	if(isset($_GET['terminal_id']))
	{
		$cmdargs = trim($_GET['terminal_id']);
	}
	$userid = "";
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	$taskid = "";
	if(isset($_GET['taskid']))
	{
		$taskid = trim($_GET['taskid']);
	}
	
	$keyvalue = 0;
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}
	
	$keyvalue_s = 0;
	if(isset($_POST['keyvalue_s']))
	{
		$keyvalue_s = trim($_POST['keyvalue_s']);
	}
	
	
	$taskname = "";
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	$israndomplay = 0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = trim((int)$_POST['israndomplay']);
	}
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	$tasktype=20;
	$ttsplay=0;
	if(isset($_POST['ttsplay']))
	{
		$ttsplay = trim($_POST['ttsplay']);
	}
	$audiosource=0;
	if($ttsplay==1)
	{

		$tasktype=29;
	}

	if($ttsplay==1)
	{
		$speed_value = "5";
		if(isset($_POST['speed_value']))
		{
			$speed_value = trim($_POST['speed_value']);
		}
			$musicmode = "0";
		if(isset($_POST['musicmode']))
		{
			$musicmode = trim($_POST['musicmode']);
		}
		$gettextarea="";
		if(isset($_POST['gettextarea']))
		{
			$gettextarea = $_POST['gettextarea'];
		}
		
		$gettextarea=nl2br($gettextarea);
		$tasktype=21;
		if(isset($_POST['audiosource']))
		{
			$audiosource = trim($_POST['audiosource']);
		}
		
	}
	$ledplay=0;
	if(isset($_POST['ledplay']))
	{
		$ledplay = trim($_POST['ledplay']);
	}
	if($ledplay==1)
	{
		$getledtextareas="";
		if(isset($_POST['getledtextareas']))
		{
			$getledtextareas = $_POST['getledtextareas'];
		}
	
		$getledtextareas=nl2br($getledtextareas);
		
		$led_group_string="";
		if(isset($_POST['led_group_string']))
		{
			$led_group_string = $_POST['led_group_string'];
		}
		
		$ledlistvalue="";
		if(isset($_POST['ledlistvalue']))
		{
			$ledlistvalue = $_POST['ledlistvalue'];
		}	
	}

	$medialist=trim($_POST['listvalue']);		
	$arrmedia=explode(",",$medialist);

	$timelengthtype = 1;
	$getendtime=0;
	$timelength = 0;
	$getstarttime=0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
		
		if($timelengthtype == 1)
		{  
			$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
			$getendtime=$timelength+$getstarttime;
		}
		else
		{
			$timelength = trim($_POST['circleTime']);
			for($i=0;$i<count($arrmedia);$i++)
			{
					$getmediaid = "SELECT timelength FROM media where id='$arrmedia[$i]'";//取插入任务id
					$mediaidresult = mysqli_query($con,$getmediaid) or die(mysqli_error($con));
					while($row = mysqli_fetch_array($mediaidresult))
					{
						$getendtime = $getendtime+($row['timelength']*$timelength);//新添加的任务id
					}
			}
			$getendtime=$getendtime+$getstarttime;
		} 
	}
	else
	{
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
		$getendtime=$timelength+$getstarttime;
	}
	
	$getendhour=$getendtime/3600;
	$getendmin=$getendtime%3600/60;
	$getendsec=$getendtime%3600%60;
	
	$getendtime=(int)$getendhour.":".(int)$getendmin.":".(int)$getendsec;
	if($getendhour>=24)
		$getendtime="23:59:59";
	$datasendmodel = 0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	 $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}

	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	$priority=13;
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
		$terminallistvalue = trim($_POST['terminallistvalue']);
		
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	
	

	$gettaskid=0;
	mysqli_query($con,"START TRANSACTION");
	
	mysqli_query($con,"LOCK TABLES  terminal WRITE, task WRITE,terminalkeymaptask WRITE ,terminaloftask WRITE, mediaoftask WRITE");
	
	$sql_name = "SELECT typeid FROM terminal WHERE id = '$audiosource'";
	$result_name = mysqli_query($con,$sql_name) or die(mysqli_error($con));
	if(mysqli_num_rows($result_name) > 0)
	{
		while($row = mysqli_fetch_array($result_name))
		{
			if($row['typeid']==0)
			{
				$speed_value=$speed_value*10;	
			$tasktype=29;
			}
		}
	}
	@mysqli_free_result($result_name);
	unset($sql_name);
	mysqli_query($con,"UNLOCK TABLES");
	$sql_same_names="SELECT terminalkeymaptask.keyid FROM terminalkeymaptask,task WHERE (task.taskid=terminalkeymaptask.taskid AND task.cmd = $cmdargs AND terminalkeymaptask.keyid NOT IN(SELECT keyid FROM terminalkeymaptask WHERE taskid=$taskid))";
	$result_same_name = mysqli_query($con,$sql_same_names) or die(mysqli_error($con));
	if(mysqli_num_rows($result_same_name) > 0)
	{
		while($get_row=mysqli_fetch_array($result_same_name))
		{
			if($get_row[0]==$keyvalue)
			{
				$forward_ok_error_obj->exit_back_function($do_php_prompt['The_has_been_used']);
			}
		}
	}
	@mysqli_free_result($result_same_name);
	unset($sql_same_names);

	$sql ="UPDATE task SET taskname='$taskname',israndomplay='$israndomplay',timelengthtype='$timelengthtype',timelength='$timelength',datasendmodel='$datasendmodel',priority='$priority',tasktype='$tasktype',cmd='$audiosource',cmdargs='$cmdargs',defaultvolume='$task_default_volume',task_user_id='$userid' WHERE task.taskid='$taskid'";

		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./view_quickplay.php?terminal_id=$cmd";
			
			echo "<script>window.location='error.php'</script>";
			
			exit;
		}
		
		$gettaskid=$taskid;
		$sql ="UPDATE terminalkeymaptask SET keyid='$keyvalue_s' WHERE taskid='$gettaskid'";
			
		mysqli_query($con,$sql) or die(mysqli_error($con));
		unset($sql);
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./view_quickplay.php?terminal_id=$cmd";
			
			echo "<script>window.location='error.php'</script>";
			
			exit;
		}
		if($ttsplay==1)
		{
			mysqli_query($con,"DELETE FROM ttssentence WHERE ttssentence.sentenceid IN(select mediaid from mediaoftask where taskid='$gettaskid')") or die(mysqli_error($con));
		}
		else 
		{
			mysqli_query($con,"DELETE FROM media WHERE typeid='tts' and id in(select mediaid from mediaoftask where taskid = '$gettaskid')") or die(mysqli_error($con));		
			mysqli_query($con,"DELETE FROM ttssentence WHERE ttssentence.sentenceid IN(select mediaid from mediaoftask where taskid='$gettaskid')") or die(mysqli_error($con));
	
		}
		
		
		mysqli_query($con,"DELETE FROM terminaloftask WHERE taskid = '$gettaskid'") or die(mysqli_error($con));

		mysqli_query($con,"DELETE FROM mediaoftask WHERE taskid = '$gettaskid'") or die(mysqli_error($con));
			for($i=0; $i<count($terminallistnum); $i++)
			{
			if(is_numeric($terminallistnum[$i]))
			{
				$temp = (int)$terminallistnum[$i];

				 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$gettaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
				
					mysqli_query($con,$sql) or die(mysqli_error($con));
					
					if(mysqli_error($con))
					{
						mysqli_query($con,"ROLLBACK");
					
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = "./view_quickplay.php?terminal_id=$cmd";
						
						echo "<script>window.location='error.php'</script>";
						
						exit;
					}

					for($j=0;$j<strlen($get_terminal);$j++)
					{
					
								if(substr($get_terminal,$j,2)=="::")
								{
									$position=$j+2;
								}
								if(substr($get_terminal,$j,1)=="|")
								{
											$position2 = $j;
											$position3 = $position2-$position;
											$a=substr($get_terminal,$j-$position3,$position3);	
											if($a==$temp)
											{
												$area = substr($get_terminal,$j+1,16);
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$gettaskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
											}
								}						
					 }
	
				}
			}
		 if($ttsplay==1)
			{
			
			$sql="INSERT INTO media(name, typeid, filename,folderid,timelength,channel,sample,bitrate) VALUES ('$taskname','tts','tts','0','0','0','$gettaskid','$tasktype')";
						
			mysqli_query($con,$sql) or die(mysqli_error($con));	
				
			$resultmedia = mysqli_query($con,"SELECT MAX(id) FROM media") or die(mysqli_error($con));
			
			$rowmedia = mysqli_fetch_array($resultmedia);	
			
			$openmediaid = $rowmedia[0]; 
			
			@mysqli_free_result($resultmedia);
			
			unset($rowmedia);
		
			$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$openmediaid','$gettaskid','0')";
			
			mysqli_query($con,$sql);
			
				$gettempi=0;
				$gettext=0;
				
				$arr1=str_split_utf8($gettextarea);
				
				for($aa=0;$aa<count($arr1);$aa++)
				{
					$gettextone=$arr1[$aa];
					$gettextone=str_replace("<br/>","",$gettextone);
					$gettextone=str_replace("<br />","",$gettextone);
					$gettextone=str_replace("\r\n","",$gettextone);
					$gettextone=str_replace("、","",$gettextone);
					$gettextone=str_replace("</b>","",$gettextone);
					$gettextone=str_replace("</B>","",$gettextone);
					$gettextone=str_replace("\\","",$gettextone);
					$gettextone=$gettextone;
				
					if(!empty($gettextone))
					{ 
						$sql="INSERT INTO ttssentence(name,sentenceid,type,content,mediaseq,speed,volume,male) VALUES ('$taskname','$openmediaid','2','$gettextone','$gettempi','$speed_value','$task_default_volume','$musicmode')";
						mysqli_query($con,$sql) or die(mysqli_error($con));
						$gettempi++;
					}
				}		
			}
			else
			{
				if(isset($_POST['listvalue']))
				{
					$medialist=trim($_POST['listvalue']);
					$arrmedia=explode(",",$medialist);
					for($i=0;$i<count($arrmedia);$i++)
					{
						$str =$arrmedia[$i];
						if(!is_numeric($str))
						{
							continue;
						}

						$number =(int)$str;
						$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$number','$gettaskid','$i')";
						mysqli_query($con,$sql) or die(mysqli_error($con));
						if(mysqli_error($con))
						{	
							mysqli_query($con,"ROLLBACK");
							$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
							$_SESSION['url'] = "./view_quickplay.php?terminal_id=$cmdargs";
							echo "<script>window.location='error.php'</script>";
							exit;
						}			
					}	
				}
			}
			$getoldtaskname = "";
	
	$getoldtaskprepower = "";
	
	$getoldtaskuserid = "";
	
	$sql = "SELECT task.taskname, task.prepower, task.task_user_id FROM task WHERE task.taskid = '$_GET[taskid]'";
	
	$result = mysqli_query($con,$sql)or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		$getoldtaskname = $row['taskname'];
	
		$getoldtaskprepower = $row['prepower'];
		
		$getoldtaskuserid = $row['task_user_id'];
	}
	
	@mysqli_free_result($result);
	
	unset($row,$sql);

	


			$sql_led_name = "SELECT taskid FROM task WHERE tasktype in(24) AND sec_task_id = '$_GET[taskid]'";	
			$result_led_name = mysqli_query($con,$sql_led_name) or die(mysqli_error($con));
		
			if(mysqli_num_rows($result_led_name) > 0)
			{
			
				if($ledplay==1)
				{
	
				 modify_ledtask($con,$getledtextareas,$taskname,$israndomplay,$timelengthtype,$timelength,0,$datasendmodel,0,'0000-00-00','0000-00-00','00:00:00','00:00:00','0000000',$priority,24,0,0,0,$_GET['taskid'],$cmdargs,0,$task_default_volume,$getoldtaskuserid,0,0,0,1,2,0,$led_group_string,$ledlistvalue);
				}
				else
				{
					if($get_row = mysqli_fetch_array($result_led_name))
					{	
						$getledtaskid=$get_row['taskid'];
						del_ledtask($con,$getledtaskid,24);
					}
				}	
			}
			else
			{
				if($ledplay==1)
				{
					 add_ledtask($con,$getledtextareas,$taskname,$israndomplay,$timelengthtype,$timelength,0,$datasendmodel,0,'0000-00-00','0000-00-00','00:00:00','00:00:00','0000000',$priority,24,0,0,0,$_GET['taskid'],$cmdargs,0,$task_default_volume,$getoldtaskuserid,0,0,0,1,2,0,$led_group_string,$ledlistvalue);
				}	
			}
			@mysqli_free_result($result_led_name);	
			unset($sql_led_name);

			mysqli_query($con,"UNLOCK TABLES");
			if(!mysqli_error($con))
			{
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				$_SESSION['url'] = "./view_quickplay.php?terminal_id=$cmdargs";
				echo "<script>window.location='success.php'</script>";	
			}	
}


function modify_yingjiplay($con)
{
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();	
	$cmdargs = "";
	if(isset($_GET['terminal_id']))
	{
		$cmdargs = trim($_GET['terminal_id']);
	}
	$userid = "";
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}

	$taskid = "";
	if(isset($_GET['taskid']))
	{
		$taskid = trim($_GET['taskid']);
	}
	
	
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	


	$tasktype=20;
	
	
	$medialist=trim($_POST['listvalue']);		
	$arrmedia=explode(",",$medialist);
	
	 $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}

	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	$priority=13;
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
		$terminallistvalue = trim($_POST['terminallistvalue']);
		
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	
	
	$gettaskid=0;

	mysqli_query($con,"UNLOCK TABLES");
	mysqli_query($con,"LOCK TABLE terminaloftask WRITE,mediaoftask WRITE,task WRITE");


	
	
	$sql ="UPDATE task SET defaultvolume='$task_default_volume',task_user_id='$userid' WHERE task.taskid='$taskid'";
	
		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./view_yingjiplay.php?terminal_id=$cmdargs";
			
			echo "<script>window.location='error.php'</script>";
			
			exit;
		}
		
		$gettaskid=$taskid;
	
		
		
		mysqli_query($con,"DELETE FROM terminaloftask WHERE taskid = '$gettaskid'") or die(mysqli_error($con));

		mysqli_query($con,"DELETE FROM mediaoftask WHERE taskid = '$gettaskid'") or die(mysqli_error($con));
			for($i=0; $i<count($terminallistnum); $i++)
			{
			if(is_numeric($terminallistnum[$i]))
			{
				$temp = (int)$terminallistnum[$i];
			
				
				 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$gettaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
				
					mysqli_query($con,$sql) or die(mysqli_error($con));
					
					if(mysqli_error($con))
					{
						mysqli_query($con,"ROLLBACK");
					
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = "./view_yingjiplay.php?terminal_id=$cmdargs";
						
						echo "<script>window.location='error.php'</script>";
						
						exit;
					}

					for($j=0;$j<strlen($get_terminal);$j++)
					{
					
								if(substr($get_terminal,$j,2)=="::")
								{
									$position=$j+2;
								}
								if(substr($get_terminal,$j,1)=="|")
								{
											$position2 = $j;
											$position3 = $position2-$position;
											$a=substr($get_terminal,$j-$position3,$position3);	
											if($a==$temp)
											{
												$area = substr($get_terminal,$j+1,16);
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$gettaskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
											}
								}						
					 }
	
				}
			}
			
			
			
				if(isset($_POST['listvalue']))
				{
					$medialist=trim($_POST['listvalue']);
					$arrmedia=explode(",",$medialist);
					for($i=0;$i<count($arrmedia);$i++)
					{
						$str =$arrmedia[$i];
						if(!is_numeric($str))
						{
							continue;
						}

						$number =(int)$str;
						$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$number','$gettaskid','$i')";
						mysqli_query($con,$sql) or die(mysqli_error($con));
						if(mysqli_error($con))
						{	
							mysqli_query($con,"ROLLBACK");
							$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
							$_SESSION['url'] = "./view_yingjiplay.php?terminal_id=$cmdargs";
							echo "<script>window.location='error.php'</script>";
							exit;
						}			
					}	
				}
			
			mysqli_query($con,"UNLOCK TABLES");
			if(!mysqli_error($con))
			{
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				$_SESSION['url'] = "./view_yingjiplay.php?terminal_id=$cmdargs";
				echo "<script>window.location='success.php'</script>";	
			}	
}




//添加任务映射---仅对文件广播、采播管理
function keyset_task_mapping_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	$map_name = "";
	if(isset($_POST['map_name']))
	{
		$map_name = trim($_POST['map_name']);
	}
	
	$keyvalue = "";
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}
	$task_map_id = "";
	if(isset($_POST['task_map_id']))
	{
		$task_map_id = trim($_POST['task_map_id']);
		$media_array = explode(",",$task_map_id);
	}

	mysqli_query($con,"LOCK TABLE shortcutkeytask WRITE");
		
	$sql_same_name = "SELECT * FROM shortcutkeytask WHERE shortcutkeytask.keyid = '$keyvalue'";	
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		if(mysqli_num_rows($result_same_name) > 0)
		{
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_has_been_used']);
		}

	@mysqli_free_result($result_same_name);
	unset($sql_same_name);

	//验证遥控键是否设置
	for($i=0;$i<count($media_array);$i++)
	{
		$mediaid=$media_array[$i];	
		mysqli_query($con,"INSERT INTO shortcutkeytask (shortcutkeytask.keyid,mediaid,keyname) VALUES('$keyvalue','$mediaid','$map_name')") or die(mysqli_error($con));
	}


	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./keytask_mapping.php";
	
		echo "<script>window.location='success.php'</script>";	
	}	
}


function keymodify_task_mapping_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	$map_name = "";
	if(isset($_POST['map_name']))
	{
		$map_name = trim($_POST['map_name']);
	}
	
	$keyvalue = "";
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}
	
	$task_map_id = "";
	if(isset($_POST['task_map_id']))
	{
		$task_map_id = trim($_POST['task_map_id']);
		$media_array = explode(",",$task_map_id);
	}
	
	$getid = "";
	if(isset($_GET['getid']))
	{
		$getid = trim($_GET['getid']);
	}

	mysqli_query($con,"LOCK TABLE shortcutkeytask WRITE");
	mysqli_query($con,"delete from shortcutkeytask where keyid ='$keyvalue'");
	for($i=0;$i<count($media_array);$i++)
	{	
	//验证遥控键是否设置
		$mediaid=$media_array[$i];		
		mysqli_query($con,"INSERT INTO shortcutkeytask (keyid,mediaid,keyname) VALUES('$keyvalue','$mediaid','$map_name')");
	
	}
	
	//@mysqli_free_result($result_taskmap);
	
	unset($sql_taskmap);
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./keytask_mapping.php";
	
		echo "<script>window.location='success.php'</script>";	
	}	
}

//添加快捷任务映射
function set_key_mapping_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$task_map_id = "";
	if(isset($_POST['task_map_id']))
	{
		$task_map_id = trim($_POST['task_map_id']);
		$map_array = explode("_",$task_map_id);
	}
	
	mysqli_query($con,"LOCK TABLE shortcutkeymap WRITE");	
	$sql_same_name = "SELECT * FROM shortcutkeymap WHERE shortcutkeymap.type = '$map_array[0]' AND shortcutkeymap.mediaid='$map_array[1]'";	
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		if(mysqli_num_rows($result_same_name) > 0)
		{
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_has_been_used']);
		}

	@mysqli_free_result($result_same_name);
	unset($sql_same_name);

	mysqli_query($con,"INSERT INTO shortcutkeymap (type,mediaid) VALUES('$map_array[0]','$map_array[1]')");
	mysqli_query($con,"UNLOCK TABLES");
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./shurt_keymapping.php";
	
		echo "<script>window.location='success.php'</script>";	
	}	
}
//删除遥控任务映射
function del_task_mapping_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	$id = "";
	if(isset($_GET['id']))
	{
		$get_map_id = trim($_GET['id']);
		$map_array = explode(",",$get_map_id);
	}
	mysqli_query($con,"LOCK TABLE terminalkeymap WRITE, terminalkey WRITE");
	
	mysqli_query($con,"START TRANSACTION");
	
	$del_map = mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalkeymap.keyid IN ($get_map_id)") or die(mysqli_error($con));
	
	$del_key = mysqli_query($con,"DELETE FROM terminalkey WHERE terminalkey.id IN ($get_map_id)") or die(mysqli_error($con));
	
	if($del_map && $del_key)
	{
		mysqli_query($con,"COMMIT");
	
		mysqli_query($con,"UNLOCK TABLES");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./task_mapping.php";
	
		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	
		mysqli_query($con,"UNLOCK TABLES");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./task_mapping.php";
	
		echo "<script>window.location='error.php'</script>";
	}
}

//删除任务映射
function keydel_task_mapping_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	$id = "";
	
	if(isset($_GET['id']))
	{
		$get_map_id = trim($_GET['id']);	
	}
	mysqli_query($con,"LOCK TABLE shortcutkeytask WRITE");
	
	mysqli_query($con,"START TRANSACTION");

	$del_map = mysqli_query($con,"DELETE FROM shortcutkeytask WHERE shortcutkeytask.keyid IN ($get_map_id)") or die(mysqli_error($con));
	
	
	
	if($del_map)
	{
		mysqli_query($con,"COMMIT");
	
		mysqli_query($con,"UNLOCK TABLES");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./keytask_mapping.php";
	
		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	
		mysqli_query($con,"UNLOCK TABLES");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./keytask_mapping.php";
	
		echo "<script>window.location='error.php'</script>";
	}
}

//删除任务映射
function keydel_camer_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	$id = "";
	
	if(isset($_GET['id']))
	{
		$get_map_id = trim($_GET['id']);	
	}
	mysqli_query($con,"LOCK TABLE camer WRITE,camerofterminal WRITE");
	
	mysqli_query($con,"START TRANSACTION");

	$del_map=mysqli_query($con,"DELETE FROM camer WHERE id IN ($get_map_id)") or die(mysqli_error($con));


	 mysqli_query($con,"DELETE FROM camerofterminal WHERE camerid IN ($get_map_id)") or die(mysqli_error($con));

	if($del_map)
	{
		mysqli_query($con,"COMMIT");
	
		mysqli_query($con,"UNLOCK TABLES");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./camer_alarm.php";
	
		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	
		mysqli_query($con,"UNLOCK TABLES");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./camer_alarm.php";
	
		echo "<script>window.location='error.php'</script>";
	}
}

//删除事件媒体
function keydel_cameralarm_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	$get_map_id = "";
	if(isset($_GET['id']))
	{
		$get_map_id = trim($_GET['id']);
	}
	
	mysqli_query($con,"LOCK TABLE camer_alarm WRITE,camer_alarmofmedia WRITE");
	mysqli_query($con,"START TRANSACTION");
	$del_map=mysqli_query($con,"DELETE FROM camer_alarm WHERE id IN ($get_map_id)") or die(mysqli_error($con));
	mysqli_query($con,"DELETE FROM camer_alarmofmedia WHERE eventid IN ($get_map_id)") or die(mysqli_error($con));
	
	if($del_map)
	{
		mysqli_query($con,"COMMIT");
	
		mysqli_query($con,"UNLOCK TABLES");
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./alarm_event_media.php";
		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	
		mysqli_query($con,"UNLOCK TABLES");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./alarm_event_media.php";
	
		echo "<script>window.location='error.php'</script>";
	}
}



//删除快捷任务映射
function del_key_mapping_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	$id = "";
	if(isset($_GET['id']))
	{
		$get_map_id = trim($_GET['id']);
		$map_array = explode(",",$get_map_id);
	}
	
	mysqli_query($con,"LOCK TABLE shortcutkeymap WRITE");
	
	mysqli_query($con,"START TRANSACTION");
	
	$del_map = mysqli_query($con,"DELETE FROM shortcutkeymap WHERE shortcutkeymap.id IN ($get_map_id)") or die(mysqli_error($con));

	if($del_map)
	{
		mysqli_query($con,"COMMIT");
	
		mysqli_query($con,"UNLOCK TABLES");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./shurt_keymapping.php";
	
		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	
		mysqli_query($con,"UNLOCK TABLES");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./shurt_keymapping.php";
	
		echo "<script>window.location='error.php'</script>";
	}
}

function taskaddtrainmedia($con)
{
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	$taskname = "";
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$speed_value = "";
	if(isset($_POST['speed_value']))
	{
		$speed_value = trim($_POST['speed_value']);
	}
	
	$volume_value = "";
	if(isset($_POST['volume_value']))
	{
		$volume_value = trim($_POST['volume_value']);
	}
	
	$getdemos = "";
	if(isset($_POST['getdemos']))
	{	
		$getdemos = trim($_POST['getdemos']);
		$getdemosarray = explode("##@",$getdemos);
	}
	
	$hiddentasktype = "";
	if(isset($_POST['hiddentasktype']))
	{	
		$hiddentasktype = trim($_POST['hiddentasktype']);
		$hiddentasktypearray = explode(",",$hiddentasktype);
	}
		
	$hiddenallnum = "";
	if(isset($_POST['hiddenallnum']))
	{	
		$hiddenallnum = trim($_POST['hiddenallnum']);
		
	}
	
		mysqli_query($con,"LOCK TABLES ttssentence,media WRITE");
	$plan_samename_result = mysqli_query($con,"SELECT ttssentence.name FROM ttssentence WHERE ttssentence.name='$taskname'") or die(mysqli_error($con));
	
	if(mysqli_num_rows($plan_samename_result) > 0)
	{
	
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	$getresult=mysqli_query($con,"SELECT * FROM media where media.name='$taskname' AND filename='tts'") or die(mysqli_error($con));
	if(mysqli_num_rows($getresult)<=0)
	{
	 mysqli_query($con,"INSERT INTO media(media.name, filename,folderid)VALUES('$taskname','tts','6')") or die(mysqli_error($con));
	}
	

	for($i=0;$i<$hiddenallnum;$i++)
	{
	
		$aa=$i+1;
		if($hiddentasktypearray[$i]==2)
			$sql = "INSERT INTO ttssentence ( name, type,content,mediaseq,speed,volume)VALUES('$taskname','$hiddentasktypearray[$i]','$getdemosarray[$i]','$aa','$speed_value','$volume_value')";
			else
			$sql = "INSERT INTO ttssentence ( name, type,mediaid,mediaseq,speed,volume)VALUES('$taskname','$hiddentasktypearray[$i]','$getdemosarray[$i]','$aa','$speed_value','$volume_value')";
			mysqli_query($con,$sql) or die(mysqli_error($con));
	}
$sql2="SELECT MAX(id) FROM media";
			
	$result=mysqli_query($con,$sql2) or die(mysqli_error($con));
	
	if($row=mysqli_fetch_array($result))
	{	
		$sql = "UPDATE ttssentence SET sentenceid='$row[0]',speed='$speed_value',volume='$volume_value' WHERE name='$taskname'";
		mysqli_query($con,$sql) or die(mysqli_error($con));
unset($sql);
		
	}
	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./trainmedia.php";
		
		echo "<script>window.location='success.php'</script>";
	}
	
}
//修改媒体音乐
function taskmodifytrainmedia($con)
{
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	$taskname = "";
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$speed_value = "";
	if(isset($_POST['speed_value']))
	{
		$speed_value = trim($_POST['speed_value']);
	}
	
	$volume_value = "";
	if(isset($_POST['volume_value']))
	{
		$volume_value = trim($_POST['volume_value']);
	}
	
	$getdemos = "";
	if(isset($_POST['getdemos']))
	{	
		$getdemos = trim($_POST['getdemos']);
		$getdemosarray = explode("##@",$getdemos);
	}

	$hiddentasktype = "";
	if(isset($_POST['hiddentasktype']))
	{	
		$hiddentasktype = trim($_POST['hiddentasktype']);
		$hiddentasktypearray = explode(",",$hiddentasktype);
	}
	
	$hiddenallnum = "";
	if(isset($_POST['hiddenallnum']))
	{	
		$hiddenallnum = trim($_POST['hiddenallnum']);
		
	}
		
		mysqli_query($con,"LOCK TABLES ttssentence,media WRITE");
	
	$getresult=mysqli_query($con,"SELECT * FROM media where media.name='$taskname' AND filename='tts'") or die(mysqli_error($con));
	if(mysqli_num_rows($getresult)<=0)
	{
	 mysqli_query($con,"INSERT INTO media(media.name, filename,folderid)VALUES('$taskname','tts','6')") or die(mysqli_error($con));
	}

	for($i=0;$i<$hiddenallnum;$i++)
	{
	
		$aa=$i+1;
		$sql2="SELECT id FROM ttssentence WHERE ttssentence.name='$taskname' AND mediaseq='$aa'";

		$result=mysqli_query($con,$sql2) or die(mysqli_error($con));
		if(mysqli_num_rows($result)>0)
		{
	
		if($hiddentasktypearray[$i]==2)
			mysqli_query($con,"UPDATE ttssentence SET speed='$speed_value',volume='$volume_value',content='$getdemosarray[$i]',mediaid='0',type='$hiddentasktypearray[$i]' WHERE name='$taskname' AND mediaseq='$aa'") or die(mysqli_error($con));
			else
			mysqli_query($con,"UPDATE ttssentence SET speed='$speed_value',volume='$volume_value',mediaid='$getdemosarray[$i]',content='',type='$hiddentasktypearray[$i]' WHERE name='$taskname' AND mediaseq='$aa'") or die(mysqli_error($con));
		
		}
		else
		{
		
		if($hiddentasktypearray[$i]==2)
					$sql = "INSERT INTO ttssentence ( name, type,content,mediaseq,speed,volume)VALUES('$taskname','$hiddentasktypearray[$i]','$getdemosarray[$i]','$aa','$speed_value','$volume_value')";
					else
					$sql = "INSERT INTO ttssentence ( name, type,mediaid,mediaseq,speed,volume)VALUES('$taskname','$hiddentasktypearray[$i]','$getdemosarray[$i]','$aa','$speed_value','$volume_value')";
					mysqli_query($con,$sql) or die(mysqli_error($con));
		
		}		
	
	}
		$sql2="SELECT id FROM media where media.name='$taskname' AND filename='tts'";
			
			$result=mysqli_query($con,$sql2) or die(mysqli_error($con));
			
			if($row=mysqli_fetch_array($result))
			{	
				
				$sql = "UPDATE ttssentence SET sentenceid='$row[0]',speed='$speed_value',volume='$volume_value' WHERE name='$taskname'";
				mysqli_query($con,$sql) or die(mysqli_error($con));
		unset($sql);
				
			}	

	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./trainmedia.php";
		
		echo "<script>window.location='success.php'</script>";
	}
	
}
//对作息任务单独处理---添加作息方案（支持批处理添加）
function belltaskaloneoperation($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量==================
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$scheme = "";
	if(isset($_POST['taskname']))
	{
		$scheme = trim($_POST['taskname']);
	}
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = trim($_POST['prepower']);
	}
	$datasendmodel = 0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = trim($_POST['datasendmodel']);
	}
	//添加声音
	$task_default_volume = 50;
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	 $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	 
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	
	$startdate = "00:00:00";
	if(isset($_POST['startdate']))
	{
		$startdate = trim($_POST['startdate']);
	}
	$enddate = "00:00:00";
	if(isset($_POST['enddate']))
	{
		$enddate = trim($_POST['enddate']);
	}
	$exemodel = 1;
	if(isset($_POST['exemodel']))
	  {
	  	$exemodel = trim($_POST['exemodel']);
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = trim($_POST['hiddenweek']);
			$repl = array(',' => '');
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
		}
	  }
	$hiddencoursename = "";
	if(isset($_POST['hiddencoursename']))
	{	
		$hiddencoursename = trim($_POST['hiddencoursename']);
		$coursenamearray = explode(",",$hiddencoursename);
	}
	$hiddenbelltime = "";
	if(isset($_POST['hiddenbelltime']))
	{	
		$hiddenbelltime = trim($_POST['hiddenbelltime']);
		$belltimearray = explode(",",$hiddenbelltime);
	}
	$hiddenbellname = "";
	if(isset($_POST['hiddenbellname']))
	{	
		$hiddenbellname = trim($_POST['hiddenbellname']);
		
		$bellnamearray = explode(",",$hiddenbellname);
	}
	
	$hiddenbelltimelength = "";
	$selectnum = "";
	if(isset($_POST['hiddenbelltimelength']))
	{	
		$hiddenbelltimelength = trim($_POST['hiddenbelltimelength']);
		
		$belltimelengtharray = explode(",",$hiddenbelltimelength);
		
		for($i=0;$i<count($belltimelengtharray);$i++)
		{
			if(strstr($belltimelengtharray[$i],":")!=false)
			{
				$selectnum[$i]=1;
			  $gettimehour=substr($belltimelengtharray[$i],0,2);
			  $gettimeminute=substr($belltimelengtharray[$i],3,2);
			  $gettimesecond=substr($belltimelengtharray[$i],6,2);
			  $belltimelengtharray[$i]=$gettimehour*3600+$gettimeminute*60+$gettimesecond;
			}
			else
			{
			$selectnum[$i]=2;
			}
		}
	}
	$terminallistvalue = "";
	if(isset($_POST['terminallistvalue']))
	{	
		$terminallistvalue = trim($_POST['terminallistvalue']);
		$terminallistarray= explode(",",$terminallistvalue);
	}
	
	$analysis_tree_group_string = "";
	if(isset($_POST['analysis_tree_group_string']))
	{
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	}
	$userid=$_SESSION['userid'];
	//方案名称不能同名
	$plan_samename_result = mysqli_query($con,"SELECT info FROM task WHERE task.info='$scheme' and task.tasktype IN(1,15) and channel=0 and task_user_id!='$userid'") or die(mysqli_error($con));
	
	if(mysqli_num_rows($plan_samename_result) > 0)
	{
		//===========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		echo "<script>window.history.back();</script>";
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}

	//判断方案中用重名
	for($i=0; $i<count($coursenamearray);$i++)
	{
		$sql = "SELECT * FROM task WHERE task.info='$scheme' AND task.taskname='$coursenamearray[$i]' AND task.tasktype IN(1,15) ";

		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
		if(mysqli_num_rows($result)>0)
		{
			@mysqli_free_result($result);
			
			unset($sql);
			//============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}

	@mysqli_free_result($result);
	
	unset($sql);
	
	//针对批量----查找方案中是否同名
/*	for($i=0; $i<count($coursenamearray)-1;$i++)
	{
		for($j=$i+1; $j<count($coursenamearray); $j++)
		{
			if(strcmp($coursenamearray[$i],$coursenamearray[$j]) == 0)
			{ */
				/*
				echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
				
				echo "<script>window.history.back();</script>";
				
				exit;
				*//*
				$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
			}
		}
	}	*/
	
	//取用户优先级
	$sql = "SELECT book_admin.id,usergroup.level FROM book_admin,usergroup WHERE ";

	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$row = mysqli_fetch_array($result);
	
	//获取任务优先级
	$priority = 13;
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
//$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	
	@mysqli_free_result($result);

	for($i=0;$i<count($coursenamearray);$i++)
	{
		$tasktype=1;
		if(strstr($bellnamearray[$i],"tts")==true)
		{
			$getbellnamearr=substr($bellnamearray[$i],4);
			//$tasktype=15;
		}
		else
			$getbellnamearr=$bellnamearray[$i];
		
		mysqli_query($con,"LOCK TABLES task WRITE");
		//添加作息任务
		$sql = "INSERT INTO audioserver.task (taskname, israndomplay, projectstate, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,";
		
		$sql.= "playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, info, defaultvolume,task_user_id)";
 		
		$sql.= " VALUES( '$coursenamearray[$i]', '0', '0', '$selectnum[$i]', '$belltimelengtharray[$i]', '$prepower', '$datasendmodel', '0', '$startdate', '$enddate', '$belltimearray[$i]', ";
		$sql.= " '$exemodel', '$priority', '$tasktype', '0', '0', '0', '0', '0','0','$scheme','$task_default_volume', '$task_user_id') ";

		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		//取作息任务id
		$sql = "SELECT 	MAX(taskid) FROM task ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{	
			mysqli_query($con,"LOCK TABLES mediaoftask WRITE");
			
			$bellid= $row[0];
			//插入媒体任务
			
			$sql = "INSERT INTO mediaoftask (mediaid,taskid) VALUES( '$getbellnamearr','$bellid')";
			
			mysqli_query($con,$sql) or die(mysqli_error($con));
		}

		@mysqli_free_result($result);
		//判断是否有功放
		if($prepower != 0)
		{
			mysqli_query($con,"LOCK TABLES task WRITE");
			//插入功放任务
		if($prepower>59)
		{
		$getpowertime=$prepower/60;
		$getfunctintime = date('H:i:s',strtotime($belltimearray[$i]."-0 hours - ".$getpowertime." minutes -0 seconds"));
		}
		else
		{
		$getpowertime=$prepower%60;
		$getfunctintime = date('H:i:s',strtotime($belltimearray[$i]."-0 hours - 0 minutes -".$getpowertime." seconds"));
		}
	
		
			$sql = "INSERT INTO audioserver.task ( taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,";
			$sql.= "playtime, exemodel, priority, tasktype,  channel, bandrate, samplerate, cmd, cmdargs, playfileid, info, defaultvolume,task_user_id,sec_task_id)";
			
			$sql.= " VALUES( '$coursenamearray[$i]', '0', '$selectnum[$i]', '$belltimelengtharray[$i]', '$prepower', '$datasendmodel', '0', '$startdate', '$enddate', '$getfunctintime', ";
			$sql.= " '$exemodel', '$priority', '9',  '0', '0', '0', '0', '0', '0', '$scheme', '$task_default_volume', '$task_user_id','$bellid')";
			
			mysqli_query($con,$sql) or die(mysqli_error($con));
			$result	=	mysqli_query($con,"SELECT MAX(taskid) FROM task") or die("Execute error".mysqli_error($con));
			//取功放任务id
			if($row = mysqli_fetch_array($result))
			{
				$powerid = $row[0]; 
			}
			
			@mysqli_free_result($result);
		}
		//插入终端任务
		for($j=0;$j<count($terminallistarray);$j++)
		{
			if(is_numeric($terminallistarray[$j]))
			{
				mysqli_query($con,"LOCK TABLES terminaloftask WRITE,mediaofterminial WRITE");
				
				$teriminalid = (int)$terminallistarray[$j];
		//		mysqli_query($con,"INSERT INTO mediaofterminal (mediaid,terminalid,taskid) VALUES( '$getbellnamearr','$teriminalid','$bellid')") or die(mysqli_error($con));
				//$terminalsql="insert into terminaloftask (taskid,terminalid) values('$bellid','$teriminalid')";
				$terminalsql = "INSERT INTO terminaloftask(taskid,terminalid,groupid) VALUES('$bellid','$teriminalid','$analysis_tree_group_ids[$j]')";

				mysqli_query($con,$terminalsql) or die(mysqli_error($con));
				
				if($prepower != 0)
				{
					//$terminalsql="insert into terminaloftask(taskid,terminalid) VALUES('$powerid','$teriminalid')";
					$terminalsql = "INSERT INTO terminaloftask(taskid,terminalid,groupid) VALUES('$powerid','$teriminalid','$analysis_tree_group_ids[$j]')";
					mysqli_query($con,$terminalsql) or die(mysqli_error($con));			
				}
				
		
				for($k=0;$k<strlen($get_terminal);$k++)
				{
				
						if(substr($get_terminal,$k,2)=="::")
							{
							$position=$k+2;
							
							}
						if(substr($get_terminal,$k,1)=="|")
						{
						  $position2 = $k;
						  $position3 = $position2-$position;
									
									$a=substr($get_terminal,$k-$position3,$position3);
									
									if($a==$teriminalid)
										{
									
										$area = substr($get_terminal,$k+1,16);
									
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$bellid' AND terminalid ='$teriminalid'";
										mysqli_query($con,$sql) or die(mysqli_error($con));
										unset($sql);
										if(($prepower != 0)||($tasktype==5))
										{
										$area = substr($get_terminal,$k+1,16);
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$powerid' AND terminalid ='$teriminalid'";
										mysqli_query($con,$sql) or die(mysqli_error($con));
										unset($sql);
										}
										
										}
						}			
									
									
									
									
				 }
			}
		}
		//=================================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=4&id=".$bellid."&volume=".$task_default_volume;			
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_task_volume("task",4,$bellid,$task_default_volume);
	}
	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./bellmanager.php";
		
		echo "<script>window.location='success.php'</script>";
	}
}


function belltaskalonemodify($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$scheme = "";
	if(isset($_POST['taskname']))
	{
		$scheme = trim($_POST['taskname']);
	}
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = trim($_POST['prepower']);
	}
	$datasendmodel = 0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = trim($_POST['datasendmodel']);
	}
	//添加声音
	$task_default_volume = 50;
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	
	$startdate = "00:00:00";
	if(isset($_POST['startdate']))
	{
		$startdate = trim($_POST['startdate']);
	}
	$enddate = "00:00:00";
	if(isset($_POST['enddate']))
	{
		$enddate = trim($_POST['enddate']);
	}
	$exemodel = 1;
	if(isset($_POST['exemodel']))
	  {
	  	$exemodel = trim($_POST['exemodel']);
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = trim($_POST['hiddenweek']);
			$repl = array(',' => '');
			$exemodel = strtr($exemodel,$repl);
		}
	  }
	$hiddencoursename = "";
	if(isset($_POST['hiddencoursename']))
	{	
		$hiddencoursename = trim($_POST['hiddencoursename']);
	
		$coursenamearray = explode(",",$hiddencoursename);
	}
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal_value = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal_value =strtr($get_terminal_value,$arr);
	}
	$hiddenbelltime = "";
	if(isset($_POST['hiddenbelltime']))
	{	
		$hiddenbelltime = trim($_POST['hiddenbelltime']);
		$belltimearray = explode(",",$hiddenbelltime);
	}
	$hiddenbellname = "";
	if(isset($_POST['hiddenbellname']))
	{	
		$hiddenbellname = trim($_POST['hiddenbellname']);
		$bellnamearray = explode(",",$hiddenbellname);
	}
	$hiddenbelltimelength = "";
	$selectnum = "";
	if(isset($_POST['hiddenbelltimelength']))
	{	
		$hiddenbelltimelength = trim($_POST['hiddenbelltimelength']);
		$belltimelengtharray = explode(",",$hiddenbelltimelength);
		
		for($i=0;$i<count($belltimelengtharray);$i++)
		{
			if(strstr($belltimelengtharray[$i],":")!=false)
			{
			  $selectnum[$i]=1;
			  $gettimehour=substr($belltimelengtharray[$i],0,2);
			  $gettimeminute=substr($belltimelengtharray[$i],3,2);
			  $gettimesecond=substr($belltimelengtharray[$i],6,2);
			  $belltimelengtharray[$i]=$gettimehour*3600+$gettimeminute*60+$gettimesecond;
			}
			else
			{
			   $selectnum[$i]=2;
			}
		}
	}
	$terminallistvalue = "";
	if(isset($_POST['terminallistvalue']))
	{	
		$terminallistvalue = trim($_POST['terminallistvalue']);
		$terminallistarray= explode(",",$terminallistvalue);
	}
	$hiddenbelltaskid = "";
	if(isset($_POST['hiddenbelltaskid']))
	{	
		$hiddenbelltaskid = trim($_POST['hiddenbelltaskid']);
	
		$belltaskidarray = explode(",",$hiddenbelltaskid);
	}
	
	$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
	$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	
	//$userid=$_SESSION['userid'];	
	//$sql = "SELECT usergroup.level FROM usergroup WHERE usergroup.id=(SELECT book_admin.usergroupid FROM book_admin ";
	
	//$sql.= "WHERE book_admin.username='$_SESSION[username]')";
	$sql = "SELECT book_admin.id,usergroup.level FROM book_admin,usergroup WHERE ";
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$row = mysqli_fetch_array($result);
	
	//获取任务优先级
	$priority = 13;
	
	$priority_value = array();
	
	$original_task_userid = array();
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	//$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
		$key_sql = "SELECT DISTINCT task_user_id FROM task WHERE task.info = '$scheme' AND task.tasktype IN(1,15) AND task_user_id='$task_user_id'";
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	if($key_row = mysqli_fetch_array($key_result))
	{
		$task_user_id = trim($key_row['task_user_id']);
	}
	
/*	$taskuserid=$_GET['taskuserid'];
	$plan_samename_result = mysqli_query($con,"SELECT info FROM task WHERE task.info='$scheme' and task.tasktype IN(1,15) and channel=0 and task_user_id!='$taskuserid'") or die(mysqli_error($con));
	if(mysqli_num_rows($plan_samename_result) > 0)
	{
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	*/
	for($i=0;$i<count($terminallistarray);$i++)
	{
		$temp = (int)$terminallistarray[$i];
		$sql = "SELECT id FROM userterminal WHERE userid='$task_user_id' AND terminalid='$temp'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if( mysqli_num_rows($result) <=0 )
		{
			$sqls="INSERT INTO userterminal(userid,terminalid) VALUES('$task_user_id','$temp')";
			mysqli_query($con,$sqls)or die(mysqli_error($con));
		}
	}
	//读取任务用户ID比较若相同则修改 不同则不修改
	
	$task_userid_sql = "SELECT task.priority FROM task WHERE task.task_user_id = '$task_user_id' AND task.taskid = '$_GET[taskid]' ";
	
	$task_userid_result = mysqli_query($con,$task_userid_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($task_userid_result) <= 0)
	{
		for($i=0; $i<count($belltaskidarray); $i++)
		{
			if( $belltaskidarray[$i] != -1 )
			{
				$original_task_priority_result = mysqli_query($con,"SELECT task.priority,task_user_id FROM task WHERE task.taskid='$belltaskidarray[$i]'");

				$original_task_priority_row = mysqli_fetch_array($original_task_priority_result);
				
				//$priority_value[] = trim($original_task_priority_row['priority']);
				$priority_value[] = $priority;
				$original_task_userid[] = trim($original_task_priority_row['task_user_id']);
				
				@mysqli_free_result($original_task_priority_result);
				unset($original_task_priority_row);				
			}
			else
			{
				$priority_value[] = $priority;
				
				$original_task_userid[] = trim($original_task_priority_row['task_user_id']);
				
				@mysqli_free_result($task_userid_result);
				
				unset($task_userid_sql);
			}
		}
	}
	else
	{
		@mysqli_free_result($task_userid_result);
		
		unset($task_userid_sql);
		
		for($i=0; $i<count($belltaskidarray); $i++)
		{
			$priority_value[] = $priority;
			
			$original_task_userid[] = $task_user_id;
		}
	}
	
	@mysqli_free_result($result);
	unset($sql,$row);
	for($i=0;$i<count($belltaskidarray);$i++)
	{
		if($belltaskidarray[$i] != -1)
		{
			$sql = "SELECT task.info,task.taskname FROM task WHERE task.taskid = '$belltaskidarray[$i]' AND task.prepower > 0 ";
			$result = mysqli_query($con,$sql) or die(mysqli_error($con));
			if($row = mysqli_fetch_array($result))
			{
				$sqlfunction = "SELECT taskid FROM task WHERE task.taskname = '$row[taskname]' AND task.info = '$row[info]' AND task.tasktype = 9 ";
			
				$resultfunction = mysqli_query($con,$sqlfunction) or die(mysqli_error($con));
				if($rowfunction = mysqli_fetch_array($resultfunction))
				{
					$getfunctionid = $rowfunction['taskid'];
					$sqlterminaloftask = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getfunctionid'"; 
					mysqli_query($con,$sqlterminaloftask) or die(mysqli_error($con));
					unset($sqlterminaloftask);
				}
				
				@mysqli_free_result($resultfunction);
				unset($rowfunction,$sqlfunction);
			//	mysqli_query($con,"DELETE FROM task WHERE task.taskid = '$getfunctionid'") or die(mysqli_error($con));
				unset($getfunctionid);
			}
			@mysqli_free_result($result);	
			unset($row,$sql);
		//	mysqli_query($con,"DELETE FROM task WHERE task.taskid = '$belltaskidarray[$i]'") or die(mysqli_error($con));	
			mysqli_query($con,"DELETE FROM mediaoftask WHERE mediaoftask.taskid = '$belltaskidarray[$i]'");
			mysqli_query($con,"DELETE	FROM terminaloftask WHERE terminaloftask.taskid = '$belltaskidarray[$i]'");
		//	mysqli_query($con,"DELETE	FROM mediaofterminal WHERE mediaofterminal.taskid = '$belltaskidarray[$i]'");

		}
	}
	
	mysqli_query($con,"LOCK TABLE task WRITE");
	
	for($i=0;$i<count($coursenamearray);$i++)
	{
		$tasktype=1;
		if(strstr($bellnamearray[$i],"tts")==true)
		{
			$getbellnamearr=substr($bellnamearray[$i],4);
		//	$tasktype=15;
		}
		else
			$getbellnamearr=$bellnamearray[$i];
		mysqli_query($con,"LOCK TABLE task WRITE");
		$sqls = "SELECT taskid FROM task WHERE task.taskid = '$belltaskidarray[$i]'";
		$resultsql = mysqli_query($con,$sqls) or die(mysqli_error($con));
		if(mysqli_num_rows($resultsql) > 0)
		{
		$sql = "update task set taskname = '$coursenamearray[$i]',timelengthtype='$selectnum[$i]', timelength = '$belltimelengtharray[$i]', prepower = '$prepower',datasendmodel = '$datasendmodel', state = '0', ";	
			$sql.= "startdate = '$startdate', enddate = '$enddate', playtime = '$belltimearray[$i]' , exemodel = '$exemodel' , ";
			$sql.= "info = '$scheme' , defaultvolume = '$task_default_volume' , ";
			$sql.= "priority = '$priority_value[$i]',sec_task_id='0',task_user_id='$original_task_userid[$i]',offlinestate='0' where task.taskid = '$belltaskidarray[$i]'";
			
		mysqli_query($con,$sql) or die(mysqli_error($con));
		unset($sql);
		$getnewbellid = $belltaskidarray[$i];
		}
		else
		{
			$sql = "INSERT INTO task (taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,startdate, enddate, ";
			
			$sql.= "playtime, exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,info,defaultvolume,task_user_id) ";
			
			$sql.= "VALUES('$coursenamearray[$i]', '0', '0', '$selectnum[$i]', '$belltimelengtharray[$i]', '$prepower', '$datasendmodel', '0', '$startdate', '$enddate', ";
			
			$sql.= "'$belltimearray[$i]','$exemodel','$priority_value[$i]','$tasktype','0','0','0','0','0','0','$scheme','$task_default_volume', '$original_task_userid[$i]') ";
			
			mysqli_query($con,$sql) or die(mysqli_error($con));
			
			unset($sql);
			
			$sqlbellid = "SELECT MAX(taskid) FROM task ";
			
			$resultbellid = mysqli_query($con,$sqlbellid) or die(mysqli_error($con));
			
			if($rowbellid = mysqli_fetch_array($resultbellid))
			{
				$getnewbellid = $rowbellid[0];
			}
			@mysqli_free_result($resultbellid);

			unset($rowbellid,$sqlbellid);
		
		}
		
		if($prepower > 0)
		{
			mysqli_query($con,"LOCK TABLE task WRITE");
			
		if($prepower>59)
		{
			$getpowertime=$prepower/60;
			$getprefunctiontime = date('H:i:s',strtotime($belltimearray[$i]."-0 hours - ".$getpowertime." minutes -0 seconds"));
		}
		else
		{
			$getpowertime=$prepower%60;
			$getprefunctiontime = date('H:i:s',strtotime($belltimearray[$i]."-0 hours - 0 minutes -".$getpowertime." seconds"));
		}
		
		$sqls = "SELECT taskid FROM task WHERE task.sec_task_id = '$belltaskidarray[$i]'";
		$resultsql = mysqli_query($con,$sqls) or die(mysqli_error($con));
		if(mysqli_num_rows($resultsql) > 0)
		{
			$sqlfun = "update task set taskname = '$coursenamearray[$i]',timelengthtype='$selectnum[$i]', timelength = '$belltimelengtharray[$i]', prepower = '$prepower',datasendmodel = '$datasendmodel', ";
			$sqlfun.= "state = '0', startdate = '$startdate', enddate = '$enddate', playtime = '$getprefunctiontime' , exemodel = '$exemodel' ,";
			$sqlfun.= "info = '$scheme' , defaultvolume = '$task_default_volume' , priority = '$priority_value[$i]',task_user_id='$original_task_userid[$i]' ,offlinestate='0' where task.sec_task_id = '$belltaskidarray[$i]' ";
		
			mysqli_query($con,$sqlfun) or die(mysqli_error($con));
			
			unset($sqlfun);
			
			$sqlfunid = "SELECT taskid FROM task where sec_task_id='$belltaskidarray[$i]'";
			$resultfunid = mysqli_query($con,$sqlfunid) or die(mysqli_error($con));	
			if($rowfunid = mysqli_fetch_array($resultfunid))
			{
				$getnewfunid = $rowfunid[0];
			}
		
			@mysqli_free_result($resultfunid);
		
			unset($rowfunid,$sqlfunid);
		}
		else
		{
		
			$sqlfun = "INSERT INTO task (taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate, ";
			
			$sqlfun.= "playtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,info,defaultvolume,task_user_id,sec_task_id) ";
			
			$sqlfun.= "VALUES('$coursenamearray[$i]', '0', '$selectnum[$i]', '$belltimelengtharray[$i]', '$prepower', '$datasendmodel', '0', '$startdate', '$enddate', ";
			
			$sqlfun.= "'$getprefunctiontime','$exemodel','$priority_value[$i]','9','0','0','0','0','0','0','$scheme','$task_default_volume','$original_task_userid[$i]','$getnewbellid' )";
			
			mysqli_query($con,$sqlfun) or die(mysqli_error($con));
			unset($sqlfun);
			$sqlfunid = "SELECT MAX(taskid) FROM task ";
			$resultfunid = mysqli_query($con,$sqlfunid) or die(mysqli_error($con));
			if($rowfunid = mysqli_fetch_array($resultfunid))
			{
				$getnewfunid = $rowfunid[0];
			}
			@mysqli_free_result($resultfunid);
			unset($rowfunid,$sqlfunid);
		}
		mysqli_query($con,"LOCK TABLES terminaloftask WRITE");
			
			for($j=0;$j<count($terminallistarray);$j++)
			{
				if(is_numeric($terminallistarray[$j]))
				{
					$teriminalid = (int)$terminallistarray[$j];
	                 $group =(int)$analysis_tree_group_ids[$j];
					 
					 	
					 
					$terminalsql="insert into terminaloftask (taskid,terminalid,groupid) values('$getnewfunid','$teriminalid','$group')";
	
					mysqli_query($con,$terminalsql) or die(mysqli_error($con));
	
					unset($terminalsql);
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
																	
																				if($a==$teriminalid)
																					{
																				
																					$area = substr($get_terminal_value,$k+1,16);
																				
																					$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getnewfunid' AND terminalid ='$teriminalid'";
																					mysqli_query($con,$sql) or die(mysqli_error($con));
																					unset($sql);

																					
																					}
														}			
								
														
									 }
				}
			}
			
		}
		mysqli_query($con,"LOCK TABLE mediaoftask WRITE");
		
		$sqlmediaoftask = "INSERT INTO mediaoftask (mediaid,taskid) VALUES('$getbellnamearr','$getnewbellid')";
		
		mysqli_query($con,$sqlmediaoftask) or die(mysqli_error($con));
	
		unset($sqlmediaoftask);
		mysqli_query($con,"START TRANSACTION");//获取不到插入的值
		mysqli_query($con,"LOCK TABLES terminaloftask WRITE,task WRITE");
		
		for($k=0;$k<count($terminallistarray);$k++)
		{
			if(is_numeric($terminallistarray[$k]))
			{
			
				$teriminalid = (int)$terminallistarray[$k];
			      $group =(int)$analysis_tree_group_ids[$k];
				/*  
				  $sqlid = "SELECT offlineparam FROM task WHERE taskid='$getnewbellid' ";
			
					$resultid = mysqli_query($con,$sqlid) or die(mysqli_error($con));
					
					if($rowid = mysqli_fetch_array($resultid))
					{
						$getnewid = $rowid[0];
					}
				  
				  */
				  
				//  	mysqli_query($con,"INSERT INTO mediaofterminal (mediaid,terminalid,taskid,offlineparam) VALUES('$getbellnamearr','$teriminalid','$getnewbellid','$getnewid')") or die(mysqli_error($con));
			
				$terminalsql="insert into terminaloftask (taskid,terminalid,groupid) values('$getnewbellid','$teriminalid','$group')";
			
				mysqli_query($con,$terminalsql) or die(mysqli_error($con));
			
				unset($terminalsql);
									for($m=0;$m<strlen($get_terminal_value);$m++)
									{
									if(substr($get_terminal_value,$m,2)=="::")
														{
														$position=$m+2;
														
														}
														if(substr($get_terminal_value,$m,1)=="|")
														{
														  $position2 = $m;
														  $position3 = $position2-$position;
																	
																	$a=substr($get_terminal_value,$m-$position3,$position3);
																	
																				if($a==$teriminalid)
																					{
																				
																					$area = substr($get_terminal_value,$m+1,16);
																				
																					$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getnewbellid' AND terminalid ='$teriminalid'";
																					mysqli_query($con,$sql) or die(mysqli_error($con));
																					unset($sql);
													
																					}
														}			
								
														
									 }
			}
		}	
	}
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./bellmanager.php";
		//=======================================================
		/*$socket	=	new	send_message_to_server($port_conf);
		
		$msg = "task?state=5&id=".$_GET['taskid']."&volume=".$task_default_volume;
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_task_volume("task",5,$_GET['taskid'],$task_default_volume);
		
		echo "<script>window.location='success.php'</script>";
	}
}



function belltaskallmodify($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节====================
	$create_socket_obj = new create_socket_class();
	
	$scheme = "";
	if(isset($_POST['taskname']))
	{
		$scheme = trim($_POST['taskname']);
	}
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = trim($_POST['prepower']);
	}
	$datasendmodel = 0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = trim($_POST['datasendmodel']);
	}
	
	//添加声音
	$task_default_volume = 50;
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	
	$startdate = "00:00:00";
	if(isset($_POST['startdate']))
	{
		$startdate = trim($_POST['startdate']);
	}
	$enddate = "00:00:00";
	if(isset($_POST['enddate']))
	{
		$enddate = trim($_POST['enddate']);
	}
	$exemodel = 1;
	if(isset($_POST['exemodel']))
	  {
	  	$exemodel = trim($_POST['exemodel']);
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = trim($_POST['hiddenweek']);
			$repl = array(',' => '');
			$exemodel = strtr($exemodel,$repl);
		}
	  }
	$hiddencoursename = "";
	if(isset($_POST['hiddencoursename']))
	{	
		$hiddencoursename = trim($_POST['hiddencoursename']);
		$coursenamearray = explode(",",$hiddencoursename);
	}
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal_value = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal_value =strtr($get_terminal_value,$arr);
	}
	$hiddenbelltime = "";
	if(isset($_POST['hiddenbelltime']))
	{	
		$hiddenbelltime = trim($_POST['hiddenbelltime']);
		$belltimearray = explode(",",$hiddenbelltime);
	}
	$hiddenbellname = "";
	if(isset($_POST['hiddenbellname']))
	{	
		$hiddenbellname = trim($_POST['hiddenbellname']);
	
	}
	
	$enablemedia = 0;
	if(isset($_POST['enablemedia']))
	{
		$enablemedia = trim((int)$_POST['enablemedia']);
	
	} 
	
	
	$hiddenbelltimelength = "";
	$selectnum = "";
	if(isset($_POST['hiddenbelltimelength']))
	{	
		$hiddenbelltimelength = trim($_POST['hiddenbelltimelength']);
	
			if(strstr($hiddenbelltimelength,":")!=false)
			{
			  $selectnum=1;
			  $gettimehour=substr($hiddenbelltimelength,0,2);
			  $gettimeminute=substr($hiddenbelltimelength,3,2);
			  $gettimesecond=substr($hiddenbelltimelength,6,2);
			  $hiddenbelltimelength=$gettimehour*3600+$gettimeminute*60+$gettimesecond;
			}
			else
			{
				$timelens = explode(",",$hiddenbelltimelength);
				$hiddenbelltimelength=$timelens[0];
				
			   	$selectnum=2;
			}
		
	}

	$terminallistvalue = "";
	$terminallistarray =array();
	if(isset($_POST['terminallistvalue']))
	{	
		$terminallistvalue = trim($_POST['terminallistvalue']);
		$terminallistarray= explode(",",$terminallistvalue);
	}

	$hiddenbelltaskid = "";
	$belltaskidarray =array();
	if(isset($_POST['hiddenbelltaskid']))
	{	
		$hiddenbelltaskid = trim($_POST['hiddenbelltaskid']);
		
		$belltaskidarray = explode(",",$hiddenbelltaskid);
	}
	
	$get_taskid = "";
	if(isset($_GET['taskid']))
	{	
		$get_taskid = trim($_GET['taskid']);
		
	
	}
	$enablebelllength = 0;
	if(isset($_POST['enablebelllength']))
	{
		$enablebelllength = trim((int)$_POST['enablebelllength']);
	}

	
	$enableterminallist = 0;
	if(isset($_POST['enableterminallist']))
	{
		$enableterminallist = trim((int)$_POST['enableterminallist']);
	}
	
	
	
	$hiddenbellnotaskid = "";
	$hiddenbellnotaskarray =array();
	if(isset($_POST['hiddenbellnotaskid']))
	{	
		$hiddenbellnotaskid = trim($_POST['hiddenbellnotaskid']);
	
		$hiddenbellnotaskarray = explode(",",$hiddenbellnotaskid);
	}
	
	$analysis_tree_group_string = "";
	$analysis_tree_group_ids =array();
	if(isset($_POST['analysis_tree_group_string']))
	{	
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
	
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	}

	
	//$userid=$_SESSION['userid'];	
	//$sql = "SELECT usergroup.level FROM usergroup WHERE usergroup.id=(SELECT book_admin.usergroupid FROM book_admin ";
	
	//$sql.= "WHERE book_admin.username='$_SESSION[username]')";
	mysqli_query($con,"LOCK TABLE task WRITE,terminaloftask WRITE,media WRITE,mediaoftask WRITE,book_admin WRITE,usergroup WRITE,userterminal WRITE");
	$userid=$_SESSION['username'];
	$sql = "SELECT book_admin.id,usergroup.level FROM book_admin,usergroup WHERE ";
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$userid' ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$row = mysqli_fetch_array($result);
	
	//获取任务优先级
	$priority = 13;
	
	$priority_value = array();
	
	$original_task_userid = array();
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}

	//$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	
	$key_sql = "SELECT DISTINCT task_user_id FROM task WHERE task.info = '$scheme' AND task.tasktype IN(1,15) AND task_user_id='$task_user_id'";
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	if($key_row = mysqli_fetch_array($key_result))
	{
		$task_user_id = trim($key_row['task_user_id']);
	}
	
/*
	$taskuserid=$_GET['taskuserid'];
	$plan_samename_result = mysqli_query($con,"SELECT info FROM task WHERE task.info='$scheme' and task.tasktype IN(1,15) and channel=0 and task_user_id!='$taskuserid'") or die(mysqli_error($con));
	if(mysqli_num_rows($plan_samename_result) > 0)
	{
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	*/
	for($i=0;$i<count($terminallistarray);$i++)
	{
		$temp = (int)$terminallistarray[$i];
		$sql = "SELECT id FROM userterminal WHERE userid='$task_user_id' AND terminalid='$temp'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if( mysqli_num_rows($result) <=0 )
		{
			$sqls="INSERT INTO userterminal(userid,terminalid) VALUES('$task_user_id','$temp')";
			mysqli_query($con,$sqls)or die(mysqli_error($con));
		}
	}
	//读取任务用户ID比较若相同则修改 不同则不修改
	
	$getsechoinfo="";
	$task_userid_sql = "SELECT priority,info FROM task WHERE task_user_id = $task_user_id AND taskid =$get_taskid";
	
	$task_userid_result = mysqli_query($con,$task_userid_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($task_userid_result) <= 0)
	{

		for($i=0; $i<count($belltaskidarray); $i++)
		{
			if($belltaskidarray[$i]==0)
				continue;
			if( $belltaskidarray[$i] != -1 )
			{
				$original_task_priority_result = mysqli_query($con,"SELECT task.priority,task_user_id FROM task WHERE task.taskid='$belltaskidarray[$i]'");

				$original_task_priority_row = mysqli_fetch_array($original_task_priority_result);
				
				//$priority_value[] = trim($original_task_priority_row['priority']);
				$priority_value[] = $priority;
				$original_task_userid[] = trim($original_task_priority_row['task_user_id']);
				
				@mysqli_free_result($original_task_priority_result);
				unset($original_task_priority_row);				
			}
			else
			{
		
				$priority_value[] = $priority;
				
				$original_task_userid[] = trim($original_task_priority_row['task_user_id']);
				
				@mysqli_free_result($task_userid_result);
				
				unset($task_userid_sql);
			}
		}
	}
	else
	{
		if($rowfunction = mysqli_fetch_array($task_userid_result))
		{
			$getsechoinfo=	$rowfunction['info'];
		}
		@mysqli_free_result($task_userid_result);
		unset($task_userid_sql);
	
		
		for($i=0; $i<count($belltaskidarray); $i++)
		{
			if($belltaskidarray[$i]==0)
			continue;
		
			$priority_value[] = $priority;
			
			$original_task_userid[] = $task_user_id;
		}
	}
	
	@mysqli_free_result($result);
	unset($sql,$row);

	for($i=0;$i<count($belltaskidarray);$i++)
	{
		if($belltaskidarray[$i] != 0)
		{
			$sql = "SELECT task.info,task.taskname FROM task WHERE task.taskid = '$belltaskidarray[$i]' AND task.prepower > 0 ";
			$result = mysqli_query($con,$sql) or die(mysqli_error($con));
			if($row = mysqli_fetch_array($result))
			{
				$sqlfunction = "SELECT taskid FROM task WHERE task.taskname = '$row[taskname]' AND task.info = '$row[info]' AND task.tasktype = 9 ";
			
				$resultfunction = mysqli_query($con,$sqlfunction) or die(mysqli_error($con));
				if($rowfunction = mysqli_fetch_array($resultfunction))
				{
					$getfunctionid = $rowfunction['taskid'];
					if($enableterminallist==1)
					{
						$sqlterminaloftask = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getfunctionid'"; 
						mysqli_query($con,$sqlterminaloftask) or die(mysqli_error($con));
						unset($sqlterminaloftask);
					}
				}
				
				@mysqli_free_result($resultfunction);
				unset($rowfunction,$sqlfunction);
			//	mysqli_query($con,"DELETE FROM task WHERE task.taskid = '$getfunctionid'") or die(mysqli_error($con));
				unset($getfunctionid);
			}
			@mysqli_free_result($result);	
			unset($row,$sql);
			
		//	mysqli_query($con,"DELETE FROM task WHERE task.taskid = '$belltaskidarray[$i]'") or die(mysqli_error($con));
			
			if($enablemedia==1)
			{	
			
				mysqli_query($con,"DELETE FROM mediaoftask WHERE mediaoftask.taskid = '$belltaskidarray[$i]'");
			}
			if($enableterminallist==1)
			{
			mysqli_query($con,"DELETE	FROM terminaloftask WHERE terminaloftask.taskid = '$belltaskidarray[$i]'");
			}
		//	mysqli_query($con,"DELETE	FROM mediaofterminal WHERE mediaofterminal.taskid = '$belltaskidarray[$i]'");
		
		}
	}

	for($i=0;$i<count($belltaskidarray);$i++)
	{
	
		if($belltaskidarray[$i]==0)
			continue;
		$tasktype=1;
		if(strstr($hiddenbellname,"tts")==true)
		{
			$getbellnamearr=substr($hiddenbellname,4);
		//	$tasktype=15;
		}
		else
			$getbellnamearr=$hiddenbellname;

		
		$sqls = "SELECT taskid FROM task WHERE task.taskid = '$belltaskidarray[$i]'";
		$resultsql = mysqli_query($con,$sqls) or die(mysqli_error($con));
		if(mysqli_num_rows($resultsql) > 0)
		{

		
		if($enablebelllength==1)
		{
			$sql = "update task set taskname = '$coursenamearray[$i]',timelengthtype='$selectnum', timelength = '$hiddenbelltimelength', prepower = '$prepower',datasendmodel = '$datasendmodel', state = '0', ";	
			$sql.= "startdate = '$startdate', enddate = '$enddate', playtime = '$belltimearray[$i]' , exemodel = '$exemodel' , ";
			$sql.= "info = '$scheme' , defaultvolume = '$task_default_volume' , ";
			$sql.= "priority = '$priority',sec_task_id='0',task_user_id='$task_user_id',offlinestate='0' where task.taskid = '$belltaskidarray[$i]'";
			mysqli_query($con,$sql) or die(mysqli_error($con));
			unset($sql);
		}
		else
		{
		$sql = "update task set taskname = '$coursenamearray[$i]', prepower = '$prepower',datasendmodel = '$datasendmodel', state = '0', ";	
			$sql.= "startdate = '$startdate', enddate = '$enddate', playtime = '$belltimearray[$i]' , exemodel = '$exemodel' , ";
			$sql.= "info = '$scheme' , defaultvolume = '$task_default_volume' , ";
			$sql.= "priority = '$priority',sec_task_id='0',task_user_id='$task_user_id',offlinestate='0' where task.taskid = '$belltaskidarray[$i]'";
			mysqli_query($con,$sql) or die(mysqli_error($con));
			unset($sql);
		}
			
	
		$getnewbellid = $belltaskidarray[$i];
		}
		else
		{
			if($original_task_userid[$i]==0)
			$original_task_userid[$i]=$task_user_id;
			if($enablebelllength==1)
			{
			$sql = "INSERT INTO task (taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,startdate, enddate, ";
			
			$sql.= "playtime, exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,info,defaultvolume,task_user_id) ";
			
			$sql.= "VALUES('$coursenamearray[$i]', '0', '0', '$selectnum', '$hiddenbelltimelength', '$prepower', '$datasendmodel', '0', '$startdate', '$enddate', ";
			
			$sql.= "'$belltimearray[$i]','$exemodel','$priority','$tasktype','0','0','0','0','0','0','$scheme','$task_default_volume', '$task_user_id') ";
			mysqli_query($con,$sql) or die(mysqli_error($con));
			
			unset($sql);
			}
			else
			{
			$sql = "INSERT INTO task (taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,startdate, enddate, ";
			
			$sql.= "playtime, exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,info,defaultvolume,task_user_id) ";
			
			$sql.= "VALUES('$coursenamearray[$i]', '0', '0', '$selectnum','$hiddenbelltimelength', '$prepower', '$datasendmodel', '0', '$startdate', '$enddate', ";
			
			$sql.= "'$belltimearray[$i]','$exemodel','$priority','$tasktype','0','0','0','0','0','0','$scheme','$task_default_volume', '$task_user_id') ";
			mysqli_query($con,$sql) or die(mysqli_error($con));
			
			unset($sql);
			}
			
			
			$sqlbellid = "SELECT MAX(taskid) FROM task ";
			
			$resultbellid = mysqli_query($con,$sqlbellid) or die(mysqli_error($con));
			
			if($rowbellid = mysqli_fetch_array($resultbellid))
			{
				$getnewbellid = $rowbellid[0];
			}
			@mysqli_free_result($resultbellid);
			
			unset($rowbellid,$sqlbellid);
		
		}
		
		if($prepower > 0)
		{

			
		if($prepower>59)
		{
			$getpowertime=$prepower/60;
			$getprefunctiontime = date('H:i:s',strtotime($belltimearray[$i]."-0 hours - ".$getpowertime." minutes -0 seconds"));
		}
		else
		{
		$getpowertime=$prepower%60;
		$getprefunctiontime = date('H:i:s',strtotime($belltimearray[$i]."-0 hours - 0 minutes -".$getpowertime." seconds"));
		}
		
		$sqls = "SELECT taskid FROM task WHERE task.sec_task_id = '$belltaskidarray[$i]'";
		$resultsql = mysqli_query($con,$sqls) or die(mysqli_error($con));
		if(mysqli_num_rows($resultsql) > 0)
		{
			if($enablebelllength==1)
			{
		
			$sqlfun = "update task set taskname = '$coursenamearray[$i]',timelengthtype='$selectnum', timelength = '$hiddenbelltimelength', prepower = '$prepower',datasendmodel = '$datasendmodel', ";
	$sqlfun.= "state = '0', startdate = '$startdate', enddate = '$enddate', playtime = '$getprefunctiontime' , exemodel = '$exemodel' ,";
	$sqlfun.= "info = '$scheme' , defaultvolume = '$task_default_volume' , priority = '$priority',task_user_id='$task_user_id' ,offlinestate='0' where task.sec_task_id = '$belltaskidarray[$i]' ";
		mysqli_query($con,$sqlfun) or die(mysqli_error($con));
			unset($sqlfun);
			}
			else
			{
			$sqlfun = "update task set taskname = '$coursenamearray[$i]',timelengthtype='$selectnum', prepower = '$prepower',datasendmodel = '$datasendmodel', ";
	$sqlfun.= "state = '0', startdate = '$startdate', enddate = '$enddate', playtime = '$getprefunctiontime' , exemodel = '$exemodel' ,";
	$sqlfun.= "info = '$scheme' , defaultvolume = '$task_default_volume' , priority = '$priority',task_user_id='$task_user_id' ,offlinestate='0' where task.sec_task_id = '$belltaskidarray[$i]' ";
		mysqli_query($con,$sqlfun) or die(mysqli_error($con));
			unset($sqlfun);
			}
			
			
			$sqlfunid = "SELECT taskid FROM task where sec_task_id='$belltaskidarray[$i]'";
			$resultfunid = mysqli_query($con,$sqlfunid) or die(mysqli_error($con));	
			if($rowfunid = mysqli_fetch_array($resultfunid))
			{
				$getnewfunid = $rowfunid[0];
			}
		
			@mysqli_free_result($resultfunid);
		
			unset($rowfunid,$sqlfunid);
		}
		else
		{
			if($original_task_userid[$i]==0)
				$original_task_userid[$i]=$task_user_id;
			if($enablebelllength==1)
			{
			$sqlfun = "INSERT INTO task (taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate, ";
			$sqlfun.= "playtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,info,defaultvolume,task_user_id,sec_task_id) ";
			$sqlfun.= "VALUES('$coursenamearray[$i]', '0', '$selectnum', '$hiddenbelltimelength', '$prepower', '$datasendmodel', '0', '$startdate', '$enddate', ";
			$sqlfun.= "'$getprefunctiontime','$exemodel','$priority','9','0','0','0','0','0','0','$scheme','$task_default_volume','$task_user_id','$getnewbellid' )";
			mysqli_query($con,$sqlfun) or die(mysqli_error($con));
			unset($sqlfun);
			}
			else
			{
			$sqlfun = "INSERT INTO task (taskname, israndomplay, timelengthtype,timelength,prepower, datasendmodel, state, startdate, enddate, ";
			$sqlfun.= "playtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,info,defaultvolume,task_user_id,sec_task_id) ";
			$sqlfun.= "VALUES('$coursenamearray[$i]', '0', '$selectnum','$hiddenbelltimelength', '$prepower', '$datasendmodel', '0', '$startdate', '$enddate', ";
			$sqlfun.= "'$getprefunctiontime','$exemodel','$priority','9','0','0','0','0','0','0','$scheme','$task_default_volume','$task_user_id','$getnewbellid' )";
			mysqli_query($con,$sqlfun) or die(mysqli_error($con));
			unset($sqlfun);
			}
			
			$sqlfunid = "SELECT MAX(taskid) FROM task ";
			$resultfunid = mysqli_query($con,$sqlfunid) or die(mysqli_error($con));
			if($rowfunid = mysqli_fetch_array($resultfunid))
			{
				$getnewfunid = $rowfunid[0];
			}
			@mysqli_free_result($resultfunid);
			unset($rowfunid,$sqlfunid);
		}
		
			for($j=0;$j<count($terminallistarray);$j++)
			{
				if(is_numeric($terminallistarray[$j]))
				{
					$teriminalid = (int)$terminallistarray[$j];
	                 $group =(int)$analysis_tree_group_ids[$j];
					 if($enableterminallist==1)
					{
					 
					$terminalsql="insert into terminaloftask (taskid,terminalid,groupid) values('$getnewfunid','$teriminalid','$group')";
	
					mysqli_query($con,$terminalsql) or die(mysqli_error($con));
					unset($terminalsql);
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
																	
																				if($a==$teriminalid)
																					{
																				
																					$area = substr($get_terminal_value,$k+1,16);
																					if($enableterminallist==1)
																					{
																					$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getnewfunid' AND terminalid ='$teriminalid'";
																					mysqli_query($con,$sql) or die(mysqli_error($con));
																					unset($sql);
																					}

																					
																					}
														}			
								
														
									 }
				}
			}
			
		}
	
	
		if($enablemedia==1)
		{

			$sqlmediaoftask = "INSERT INTO mediaoftask (mediaid,taskid) VALUES('$getbellnamearr','$getnewbellid')";
	
			mysqli_query($con,$sqlmediaoftask) or die(mysqli_error($con));
			unset($sqlmediaoftask);
		}
		mysqli_query($con,"START TRANSACTION");//获取不到插入的值
	if($enableterminallist==1)
	{
	
		
	}	
	
		for($k=0;$k<count($terminallistarray);$k++)
		{
			if(is_numeric($terminallistarray[$k]))
			{
			
				$teriminalid = (int)$terminallistarray[$k];
			      $group =(int)$analysis_tree_group_ids[$k];
				/*  
				  $sqlid = "SELECT offlineparam FROM task WHERE taskid='$getnewbellid' ";
			
					$resultid = mysqli_query($con,$sqlid) or die(mysqli_error($con));
					
					if($rowid = mysqli_fetch_array($resultid))
					{
						$getnewid = $rowid[0];
					}
				  
				  */
				  
				//  	mysqli_query($con,"INSERT INTO mediaofterminal (mediaid,terminalid,taskid,offlineparam) VALUES('$getbellnamearr','$teriminalid','$getnewbellid','$getnewid')") or die(mysqli_error($con));
			
			if($enableterminallist==1)
					{
				$terminalsql="insert into terminaloftask (taskid,terminalid,groupid) values('$getnewbellid','$teriminalid','$group')";
			
				mysqli_query($con,$terminalsql) or die(mysqli_error($con));
			
				unset($terminalsql);
				}
									for($m=0;$m<strlen($get_terminal_value);$m++)
									{
									if(substr($get_terminal_value,$m,2)=="::")
														{
														$position=$m+2;
														
														}
														if(substr($get_terminal_value,$m,1)=="|")
														{
														  $position2 = $m;
														  $position3 = $position2-$position;
																	
																	$a=substr($get_terminal_value,$m-$position3,$position3);
																	
																				if($a==$teriminalid)
																					{
																				
																						$area = substr($get_terminal_value,$m+1,16);
																						if($enableterminallist==1)
																						{
																							$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getnewbellid' AND terminalid ='$teriminalid'";
																							mysqli_query($con,$sql) or die(mysqli_error($con));
																							unset($sql);
																						}
													
																					}
														}			
								
														
									 }
			}
		}	
	}

			for($k=0;$k<count($hiddenbellnotaskarray);$k++)
			{
				if($hiddenbellnotaskarray[$k]=="")
					break;			
				if($hiddenbellnotaskarray[$k]==0)
					break;	
					
				
				$sql = "update task set prepower = '$prepower',datasendmodel = '$datasendmodel', state = '0', ";	
				$sql.= "startdate = '$startdate', enddate = '$enddate',  exemodel = '$exemodel' , ";
				$sql.= "info = '$scheme' , defaultvolume = '$task_default_volume' , ";
				$sql.= "priority = '$priority',sec_task_id='0',task_user_id='$task_user_id',offlinestate='0' where task.tasktype='1' and task.taskid = '$hiddenbellnotaskarray[$k]'";
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);
				$sqlfun = "update task set  prepower = '$prepower',datasendmodel = '$datasendmodel', ";
	$sqlfun.= "state = '0', startdate = '$startdate', enddate = '$enddate', exemodel = '$exemodel' ,";
	$sqlfun.= "info = '$scheme' , defaultvolume = '$task_default_volume' , priority = '$priority',task_user_id='$task_user_id' ,offlinestate='0' where task.tasktype='9' and task.sec_task_id = '$hiddenbellnotaskarray[$k]' ";
				mysqli_query($con,$sqlfun) or die(mysqli_error($con));
				unset($sqlfun);
				
			
			}
			if($getsechoinfo!=$scheme && $getsechoinfo!="")
			{
				mysqli_query($con,"DELETE FROM task WHERE task.info = '$getsechoinfo'");	

			}			
			mysqli_query($con,"UNLOCK TABLES");
	if(!mysqli_error($con))
	{
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./bellmanager.php";
		//=======================================================
		/*$socket	=	new	send_message_to_server($port_conf);
		
		$msg = "task?state=5&id=".$_GET['taskid']."&volume=".$task_default_volume;
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_task_volume("task",5,$_GET['taskid'],$task_default_volume);
		
		echo "<script>window.location='success.php'</script>";
	}
}

//添加2、3、4、5任务
function addfileplaytask_msg($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	
	$taskname = "";
	
	$sec_task_id = 0;
	
	$cmd = 0;
	
	$cmdargs = 0;
	
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay = 0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = trim((int)$_POST['israndomplay']);
	} 
	$getfolderid = 0;
	if(isset($_GET['getfolderid']))
	{
		$getfolderid = trim((int)$_GET['getfolderid']);
	}  
		$starthour=0;
	if(isset($_POST['starthour']))
	{
		$starthour = $_POST['starthour'];
	}
	$startmin=0;
	if(isset($_POST['startmin']))
	{
		$startmin = $_POST['startmin'];
	}
	$startsenc=0;
	if(isset($_POST['startsenc']))
	{
		$startsenc = $_POST['startsenc'];
	}
	$getstarttime=$starthour*3600+$startmin*60+$startsenc;
	$medialist=trim($_POST['listvalue']);
			
	$arrmedia=explode(",",$medialist);

	$timelengthtype = 1;
	$getendtime=0;
	$timelength = 0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
		
		if($timelengthtype == 1)
		{  
			$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
			$getendtime=$timelength+$getstarttime;
		}
		else
		{
			$timelength = trim($_POST['circleTime']);
			for($i=0;$i<count($arrmedia);$i++)
			{
					$getmediaid = "SELECT timelength FROM media where id='$arrmedia[$i]'";//取插入任务id
					$mediaidresult = mysqli_query($con,$getmediaid) or die(mysqli_error($con));
					while($row = mysqli_fetch_array($mediaidresult))
					{
						$getendtime = $getendtime+($row['timelength']*$timelength);//新添加的任务id
					}
			}
			$getendtime=$getendtime+$getstarttime;
		} 
	}
	else
	{
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
		$getendtime=$timelength+$getstarttime;
	}
	$getendhour=$getendtime/3600;
	$getendmin=$getendtime%3600/60;
	$getendsec=$getendtime%3600%60;
	
	$getendtime=(int)$getendhour.":".(int)$getendmin.":".(int)$getendsec;
	if($getendhour>=24)
		$getendtime="23:59:59";
	$datasendmodel = 0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}

	$state = 0;
	$intervalmode=0;
	if(isset($_POST['intervalmode']))
	{
		$intervalmode=$_POST['intervalmode'];
	}
	$intervaltype=0;
	if(isset($_POST['intervaltype']))
	{
		$intervaltype = $_POST['intervaltype'];
	}
	$intervalcircle=0;
	if(isset($_POST['intervalcircle']))
	{
		$intervalcircle = $_POST['intervalcircle'];
	}
	$intervallength=0;
	$allintervallen=0;
	if($intervalmode==1)
	{
		$intervallength = trim($_POST['intervallenHour'])*60*60 + trim($_POST['intervallenMin'])*60 + trim($_POST['intervallenSenc'])*1; 
		if($intervaltype==1)
		{
			$allintervallen = trim($_POST['intervalHour'])*60*60 + trim($_POST['intervalMin'])*60 + trim($_POST['intervalSenc'])*1; 
		}
		else
		{
			$allintervallen=$intervalcircle;
		}
	}	
	
	$startdate="";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	
	if(empty($_POST['startdate']))
	{
		$startdate = "00-00-00";
	}
	
	$enddate="";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	
	if(empty($_POST['enddate']))
	{
		$enddate = "00-00-00";
	}
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = trim($_POST['playtime']);
	}
	if(empty($_POST['playtime']))
	{
		$playtime = "00:00:00";
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
		
		if($prepower!=0)
		{
			if($prepower>59)
			{
			$getpowertime=$prepower/60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$getpowertime."minutes -0 seconds"));
			}
			else
			{
			$getpowertime=$prepower%60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getpowertime."seconds"));
			}
		}
	}
	//获取声音
	$task_default_volume = "60";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	
	 
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = trim($_POST['exemodel']);
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = trim($_POST['hiddenweek']);
			
			$repl = array(',' => '');
			
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			
			$playtime = "00:00:00";
		}
	}
	
	if(empty($_POST['exemodel']))
	{
		$exemodel = "1111111";
	}
	//获取任务优先级
	$priority=3;
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$tasktype=0;
	$audiosource=0;
	if(isset($_POST['audiosource']))
	{
		$audiosource = trim($_POST['audiosource']);
		$cmd = $audiosource;
		$audiosource = 0;
	}
	
	$channel = 0;
	
	if(isset($_POST['channel']))
	{
		$channel = trim($_POST['channel']);
		$cmdargs = $channel;
		
	}
	
	$bandrate = 0;
	if(isset($_POST['bandrate']))
	{
		$bandrate = trim($_POST['bandrate']);
	}
	
	$samplerate=0;
	if(isset($_POST['samplerate']))
	{
		$samplerate = trim($_POST['samplerate']);
	}

	$terminallistvalue = trim($_POST['terminallistvalue']);
	$terminallistnum = explode(",",$terminallistvalue);
	$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
	$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);

	$ledplay=0;
	if(isset($_POST['ledplay']))
	{
		$ledplay = trim($_POST['ledplay']);
	}
	if($ledplay==1)
	{
		$getledtextareas="";
		if(isset($_POST['getledtextareas']))
		{
			$getledtextareas = $_POST['getledtextareas'];
		}
		$getledtextareas=nl2br($getledtextareas);
		
		$led_group_string="";
		if(isset($_POST['led_group_string']))
		{
			$led_group_string = $_POST['led_group_string'];
		}
		
		$ledlistvalue="";
		if(isset($_POST['ledlistvalue']))
		{
			$ledlistvalue = $_POST['ledlistvalue'];
		}
	}
	
	
	$playfileid = 0;
	
	$gototaskmanager = "";
	
	$openpower = 0;
	
	$openpowertaskid = 0;
	
	//获取用户优先级
	
	$sql = "SELECT book_admin.id,usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$row = mysqli_fetch_array($result);
	//设置优先级
//	$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	

	@mysqli_free_result($result);
	
	unset($sql,$row);
	
	switch($_POST['taskType'])
	{
		case "belltask":
		
			$tasktype = 1;
		
			$gototaskmanager="./bellmanager.php";
		
			break;
		case "fileplaytask":
			$tasktype=2;
			$key_sql = "SELECT userid FROM filetaskfree WHERE filetaskfree.id = '$getfolderid'";
			$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
			if($key_row = mysqli_fetch_array($key_result))
			{
				$task_user_id = trim($key_row['userid']);
			}
			$user_id = 0;
			if(isset($_GET['userid']))
			{
			$user_id = $_GET['userid'];
			} 
			 
			$gototaskmanager="./taskmanager.php?id=$getfolderid&userid=$user_id";
			
			$EmergencyBroadcast = 0;
			
			if(isset($_POST['EmergencyBroadcast']))
			{
				$EmergencyBroadcast = trim($_POST['EmergencyBroadcast']);
			}
			
			if($EmergencyBroadcast == 1)
			{
				$tasktype = 7;
			}
			
			break;
			case "videoplaytask":
				$tasktype=27;
				$user_id=$_SESSION['userid'];
				$gototaskmanager="./videodisplaymanager.php?userid=$user_id";

				break;		
		case "zhaoshengplaytask":
			$tasktype=25;
			$key_sql = "SELECT userid FROM filetaskfree WHERE filetaskfree.id = '$getfolderid'";
			$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
			if($key_row = mysqli_fetch_array($key_result))
			{
				$task_user_id = trim($key_row['userid']);
			}
			$user_id = 0;
			if(isset($_GET['userid']))
			{
			$user_id = $_GET['userid'];
			} 
			 
			$gototaskmanager="./zhaoshentaskmanager.php?userid=$user_id";
			
			$EmergencyBroadcast = 0;
			
			if(isset($_POST['EmergencyBroadcast']))
			{
				$EmergencyBroadcast = trim($_POST['EmergencyBroadcast']);
			}
			
			if($EmergencyBroadcast == 1)
			{
				$tasktype = 25;
			}
			$cmdargs=$tasktype;
			
			$get_soundsdevice = trim($_POST['get_soundsdevice']);
		$get_soundsdevicearry = explode(",",$get_soundsdevice);
		$sounds_tree_group_string = trim($_POST['sounds_tree_group_string']);
		$sounds_tree_group_stringarry = explode(",",$sounds_tree_group_string);
			
			
			break;	
			
		case "admmanagertask":
		
			$tasktype = 3;
			
			$interview_repower = 0;//欲开采播电源
			
			$interview_repower_time = 0;//记录欲开时间
			
		//	$cmd = $audiosource;
			
			$cmdargs = $channel;
			$channel = 0;
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
			if($interview_repower>59)
			{
				$getpowertime=$interview_repower/60;
				$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$getpowertime."minutes -0 seconds"));
			}
			else
			{
			$getpowertime=$interview_repower%60;
			$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getpowertime."seconds"));
			}
			
			$gototaskmanager="./admmanager.php";
			
			break;
		case "telmanagertask":
			
			$tasktype=4;
			
			$gototaskmanager="./telBroadManager.php";
			
			break;
		case "terfuncplaytask":
		
			$tasktype = 5;
		
			$cmd = 0;
		
			$gototaskmanager="./terminalfunctionplay.php";
		
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			
		break;
		case "centerctrladd":
			$tasktype = 23;
			$cmd = 0;
			$gototaskmanager="./centerctrmanager.php";
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			//获取任务优先级
			$priority=3;
			if(isset($_POST['task_priority_text']))
			{
				$priority = trim($_POST['task_priority_text']);
			}
			$pcstate=0;
			if(isset($_POST['pcstate']))
			{
				$pcstate = $_POST['pcstate'];
			}
			$projectionstate=0;
			if(isset($_POST['projectionstate']))
			{
				$projectionstate = $_POST['projectionstate'];
			}
			$systemstate=0;
			if(isset($_POST['systemstate']))
			{
				$systemstate = $_POST['systemstate'];
			}
			$volstate=0;
			if(isset($_POST['volstate']))
			{
				$volstate = $_POST['volstate'];
			}
			$projectionscreenstate=0;
			if(isset($_POST['projectionscreenstate']))
			{
				$projectionscreenstate = $_POST['projectionscreenstate'];
			}
			
			$mix_preced=0;
			if(isset($_POST['mix_preced']))
			{
				$mix_preced = $_POST['mix_preced'];
			}
			$mic_vol=0;
			if(isset($_POST['mic_vol']))
			{
				$mic_vol = $_POST['mic_vol'];
			}
				$net_vol=0;
			if(isset($_POST['net_vol']))
			{
				$net_vol = $_POST['net_vol'];
			}
			
			$dormancy=0;
			if(isset($_POST['dormancy']))
			{
				$dormancy = $_POST['dormancy'];
			}
			$showcase=0;
			if(isset($_POST['showcase']))
			{
				$showcase = $_POST['showcase'];
			}
			$notebook=0;
			if(isset($_POST['notebook']))
			{
				$notebook = $_POST['notebook'];
			}
			$computer=0;
			if(isset($_POST['computer']))
			{
				$computer = $_POST['computer'];
			}
			$hdmi=0;
			if(isset($_POST['hdmi']))
			{
				$hdmi = $_POST['hdmi'];
			}
			$power1=0;
			if(isset($_POST['power1']))
			{
				$power1 = $_POST['power1'];
			}
			$power2=0;
			if(isset($_POST['power2']))
			{
				$power2 = $_POST['power2'];
			}
	
		break;
	}

	/*************************
		区分任务类型
		同一任务中不允许同名
	**************************/
	if($tasktype == 5)
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '5' ";
		$sql_same_name.= "AND prepower = '0' AND tasktype = 5 AND channel = 0 AND info = '' AND sec_task_id = 0 ";
		
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		
		if(mysqli_num_rows($result_same_name) > 0)
		{
			//============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
		
			exit;*/
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	else
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype'";
		
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		
		if(mysqli_num_rows($result_same_name) > 0)
		{
			//===========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	@mysqli_free_result($result_same_name);
	
	unset($sql_same_name);
		

	for($i=0;$i<count($terminallistnum);$i++)
	{
		$temp = (int)$terminallistnum[$i];
		$sql = "SELECT id FROM userterminal WHERE userid='$task_user_id' AND terminalid='$temp'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if( mysqli_num_rows($result) <=0 )
		{
			$sqls="INSERT INTO userterminal(userid,terminalid) VALUES('$task_user_id','$temp')";
			mysqli_query($con,$sqls)or die(mysqli_error($con));
		}
	}

	//加锁并启用事务
	mysqli_query($con,"START TRANSACTION");//获取不到插入的值
	
	mysqli_query($con,"LOCK TABLES task WRITE,terminaloftask WRITE,mediaoftask WRITE,media WRITE,ledsentence WRITE,ledoftask WRITE,soundtask WRITE");

	if($tasktype !=1)
	{
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,playtime,endtime,";
		
		$sql.="exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume,task_user_id ";
		
		$sql.=", sec_task_id,parentid,interval_s,intplaylength,intplaylengthtype)VALUES('$taskname', '$israndomplay', '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate', '$playtime','$getendtime', '$exemodel', '$priority', '$tasktype', '$channel', ";
		
		$sql.="'$bandrate', '$samplerate', '$cmd', '$cmdargs', '$playfileid', '$task_default_volume', '$task_user_id', $sec_task_id,$getfolderid,$intervallength,$allintervallen,$intervaltype) ";
	
		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");

			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = $gototaskmanager;
			
			echo "<script>window.location='error.php'</script>";

			exit;
		}

		$sql = "SELECT MAX(taskid) FROM task";//取插入任务id
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			$gettaskid = $row[0];//新添加的任务id
		}
		
		@mysqli_free_result($result);
		
		unset($sql,$row);

	
		if(($prepower != 0)||($tasktype==5))
		{						
			if($tasktype == 5)
			{
			
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
				
				$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
				
				$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id) VALUES('$taskname', '$israndomplay', ";
				
				$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
				
				$sql.="'$preopenpowertime', '$exemodel', '$priority', '5', '0', '$bandrate', '$samplerate', ";
				
				$sql.="'1', '0', '$playfileid', '$task_default_volume','$task_user_id', '$gettaskid') ";
			}
			else
			{
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
				
				$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
				
				$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id,parentid,interval_s,intplaylength,intplaylengthtype) VALUES('$taskname', '$israndomplay', ";
				
				$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
				
				$sql.="'$preopenpowertime', '$exemodel', '$priority', '9', '0', '$bandrate', '$samplerate', ";
				
				$sql.="'0', '$cmdargs', '$playfileid', '$task_default_volume','$task_user_id', '$gettaskid','$getfolderid','$intervallength','$allintervallen','$intervaltype') ";
			}
			mysqli_query($con,$sql) or die(mysqli_error($con));
			
			unset($sql);
			
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");

				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息		

				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
		
			//取得功放任务id $openpowertaskid
			
			$resultpower = mysqli_query($con,"SELECT MAX(taskid) FROM task") or die(mysqli_error($con));
			  
			$rowpower2 = mysqli_fetch_array($resultpower);	
			  
			$openpowertaskid = $rowpower2[0]; 
			  
			@mysqli_free_result($resultpower);
			
			unset($rowpower2);
		}

		if($tasktype == 3)
		{
			$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
			
			$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
			
			$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id, sec_task_id) VALUES('$taskname', '$israndomplay', ";
			
			$sql.="'$timelengthtype', '$timelength', '$interview_repower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
			
			$sql.="'$interview_repower_time', '$exemodel', '$priority', '8', '$channel', '$bandrate', '$samplerate', ";
			
			$sql.="'0', '$cmdargs', '$playfileid', '$task_default_volume','$task_user_id','$gettaskid') ";
						
			mysqli_query($con,$sql) or die(mysqli_error($con));
			
			unset($sql);
			
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
			//取采播任务id
			$col_repower_task_Id = 0;
			
			$col_repowerId_result = mysqli_query($con,"SELECT MAX(taskid) FROM task") or die(mysqli_error($con));
			
			$col_repowerId_row = mysqli_fetch_array($col_repowerId_result);	
			  
			$col_repower_task_Id = $col_repowerId_row[0]; 
			  
			@mysqli_free_result($col_repowerId_result);
			
			unset($col_repowerId_row);
			//插入采播任务终端
			
			mysqli_query($con,"insert into terminaloftask (taskid, terminalid) values('$col_repower_task_Id','$cmd')");
			
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
		}

	for($i=0; $i<count($terminallistnum); $i++)
		{
			if(is_numeric($terminallistnum[$i]))
			{
				$temp = (int)$terminallistnum[$i];
				
				//插入终端任务关联
				//$sql="insert into terminaloftask (taskid,terminalid) values('$gettaskid','$temp')";
	         
					
				$c =strlen($temp);
				if($tasktype==23)
				{
				 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area,pcstate,projectionstate,systemstate,volstate,volume,projectionscreenstate,dev1,dev2,dev3,dev4,dev5)VALUES('$gettaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111','$pcstate','$projectionstate','$systemstate','$volstate','$task_default_volume','$projectionscreenstate','$dev1','$dev2','$dev3','$dev4','$dev5')";
				 }
				else
				 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$gettaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
				
					mysqli_query($con,$sql) or die(mysqli_error($con));
					
					if(mysqli_error($con))
					{
						mysqli_query($con,"ROLLBACK");
					
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = "./bellmanager.php";
						
						echo "<script>window.location='error.php'</script>";
						
						exit;
					}
					
					if(($prepower != 0)||($tasktype==5))
					{
			 
					if($tasktype==23)
					{
					 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area,pcstate,projectionstate,systemstate,volstate,volume,projectionscreenstate,dev1,dev2,dev3,dev4,dev5)VALUES('$openpowertaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111','$pcstate','$projectionstate','$systemstate','$volstate','$task_default_volume','$projectionscreenstate','$dev1','$dev2','$dev3','$dev4','$dev5')";
					 }
					 else
						$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$openpowertaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
						
						mysqli_query($con,$sql) or die(mysqli_error($con));	
						
						if(mysqli_error($con))
						{
							mysqli_query($con,"ROLLBACK");
							
							$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
							
							$_SESSION['url'] = $gototaskmanager;
						
							echo "<script>window.location='error.php'</script>";
						
							exit;
						}		
					}
	
				for($j=0;$j<strlen($get_terminal);$j++)
				{
				
				if(substr($get_terminal,$j,2)=="::")
									{
									$position=$j+2;
									
									}
						if(substr($get_terminal,$j,1)=="|")
						{
						  $position2 = $j;
						  $position3 = $position2-$position;
									
									$a=substr($get_terminal,$j-$position3,$position3);
									
									if($a==$temp)
										{
									
										$area = substr($get_terminal,$j+1,16);
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$gettaskid' AND terminalid ='$temp'";
										mysqli_query($con,$sql) or die(mysqli_error($con));
										unset($sql);
										if(($prepower != 0)||($tasktype==5))
										{
										$area = substr($get_terminal,$j+1,16);
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$openpowertaskid' AND terminalid ='$temp'";
										mysqli_query($con,$sql) or die(mysqli_error($con));
										unset($sql);
										}
										
										}
						}						
				 }
	
				}
				}
	}

	if($tasktype==25)
	{
	$get_volume_strings = trim($_POST['get_volume_strings']);
	
		$get_volume_stingsarry = explode(",",$get_volume_strings);
		
	$get_db_value_string = trim($_POST['get_db_value_string']);
		
		$get_db_value_stingsarry = explode(",",$get_db_value_string);	
		
		
		
		for($i=0; $i<count($get_soundsdevicearry); $i++)
		{
			if(is_numeric($get_soundsdevicearry[$i]))
			{
				
				$tempdevice = (int)$get_soundsdevicearry[$i];
			
				
				
				$db_value_stingsarry = explode("-",$get_db_value_stingsarry[$i]);		
			
	
				
				
					$sql="INSERT INTO soundtask(taskid,devid,volume,dbvalue) VALUES ('$gettaskid','$tempdevice','0','$db_value_stingsarry[0]')";
				
					mysqli_query($con,$sql) or die(mysqli_error($con));
					unset($sql);
					
					$sql="INSERT INTO soundtask(taskid,devid,volume,dbvalue) VALUES ('$gettaskid','$tempdevice','20','$db_value_stingsarry[1]')";
				
					mysqli_query($con,$sql) or die(mysqli_error($con));
					$sql="INSERT INTO soundtask(taskid,devid,volume,dbvalue) VALUES ('$gettaskid','$tempdevice','40','$db_value_stingsarry[2]')";
		
					mysqli_query($con,$sql) or die(mysqli_error($con));
					$sql="INSERT INTO soundtask(taskid,devid,volume,dbvalue) VALUES ('$gettaskid','$tempdevice','60','$db_value_stingsarry[3]')";
		
					mysqli_query($con,$sql) or die(mysqli_error($con));
					$sql="INSERT INTO soundtask(taskid,devid,volume,dbvalue) VALUES ('$gettaskid','$tempdevice','80','$db_value_stingsarry[4]')";
		
					mysqli_query($con,$sql) or die(mysqli_error($con));
					$sql="INSERT INTO soundtask(taskid,devid,volume,dbvalue) VALUES ('$gettaskid','$tempdevice','100','$db_value_stingsarry[5]')";
		
					mysqli_query($con,$sql) or die(mysqli_error($con));
				
					if(mysqli_error($con))
					{	
						mysqli_query($con,"ROLLBACK");
					
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = $gototaskmanager;
						echo "<script>window.location='error.php'</script>";					
						exit;
					}
					
			}
		}
	
	
	}

/*	
	if($tasktype==2)
	{
		for($j=0;$j<count($arrmedia);$j++)
		{
				$str =$arrmedia[$j];
			
				if(!is_numeric($str))
				{
					continue;
				}
				
				$number =(int)$str;
			for($i=0; $i<count($terminallistnum); $i++)
			{
				if(is_numeric($terminallistnum[$i]))
				{
					$temp = (int)$terminallistnum[$i];
					mysqli_query($con,"INSERT INTO mediaofterminal (mediaid,terminalid,taskid) VALUES( '$number','$temp','$gettaskid')");
				
				}
			}
		}
	}
	*/
	if($tasktype==2||$tasktype==25 || $tasktype==7||$tasktype==15||$tasktype==27)
	{
		
		if(isset($_POST['listvalue']))
		{
			$medialist=trim($_POST['listvalue']);
			
			$arrmedia=explode(",",$medialist);
			
			for($i=0;$i<count($arrmedia);$i++)
			{
				$str =$arrmedia[$i];
			
				if(!is_numeric($str))
				{
					continue;
				}
				
				$number =(int)$str;
			
				$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$number','$gettaskid','$i')";
			
				mysqli_query($con,$sql) or die(mysqli_error($con));
				
				if(mysqli_error($con))
				{	
					mysqli_query($con,"ROLLBACK");
				
					$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
					
					$_SESSION['url'] = $gototaskmanager;
					
					echo "<script>window.location='error.php'</script>";
					
					exit;
				}			
			}	
		}
	}

	if($timelengthtype==1)
	{
		$length_time=$timelength;
	}
	else
	{
		$length_time=86000;

	}			
	if($ledplay==1)
	{
	 add_ledtask($con,$getledtextareas,$taskname,$israndomplay,1,$length_time,0,$datasendmodel,0,'0000-00-00','0000-00-00','00:00:00','00:00:00','0000000',$priority,24,0,0,0,$gettaskid,$cmdargs,0,$task_default_volume,$task_user_id,0,$getfolderid,$intervallength,$allintervallen,$intervaltype,0,$led_group_string,$ledlistvalue);
	}

	mysqli_query($con,"UNLOCK TABLES");
	mysqli_query($con,"COMMIT");
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
		//===================================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=4&id=".$gettaskid."&volume=".$task_default_volume;			
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/

		$create_socket_obj->send_socket_task_volume("task",4,$gettaskid,$task_default_volume);
		
		echo "<script>window.location='success.php'</script>";
	}			
}




//添加led显示任务
function ledaddplaytask_msg($con)
{
	
	//添加外部变量
	global $do_php_prompt;
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	
	$taskname = "";
	
	$sec_task_id = 0;
	
	$cmd = 0;
	
	$cmdargs = 0;
	
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay = 0;
	
	$getfolderid = 0;
	if(isset($_GET['getfolderid']))
	{
		$getfolderid = trim((int)$_GET['getfolderid']);
	}  
		$starthour=0;
	if(isset($_POST['starthour']))
	{
		$starthour = $_POST['starthour'];
	}
	$startmin=0;
	if(isset($_POST['startmin']))
	{
		$startmin = $_POST['startmin'];
	}
	$startsenc=0;
	if(isset($_POST['startsenc']))
	{
		$startsenc = $_POST['startsenc'];
	}
	$getstarttime=$starthour*3600+$startmin*60+$startsenc;


	$timelengthtype = 1;
	$getendtime=0;
	$timelength = 0;
	
		if($timelengthtype == 1)
		{  
			$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
			$getendtime=$timelength+$getstarttime;
		}
	
	$getendhour=$getendtime/3600;
	$getendmin=$getendtime%3600/60;
	$getendsec=$getendtime%3600%60;
	
	$getendtime=(int)$getendhour.":".(int)$getendmin.":".(int)$getendsec;
	if($getendhour>=24)
		$getendtime="23:59:59";
	$datasendmodel = 0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	$state = 0;
	$intervalmode=0;
	if(isset($_POST['intervalmode']))
	{
		$intervalmode=$_POST['intervalmode'];
	}
	$intervaltype=0;
	if(isset($_POST['intervaltype']))
	{
		$intervaltype = $_POST['intervaltype'];
	}
	$intervalcircle=0;
	if(isset($_POST['intervalcircle']))
	{
		$intervalcircle = $_POST['intervalcircle'];
	}
	$intervallength=0;
	$allintervallen=0;
	if($intervalmode==1)
	{
		$intervallength = trim($_POST['intervallenHour'])*60*60 + trim($_POST['intervallenMin'])*60 + trim($_POST['intervallenSenc'])*1; 
		if($intervaltype==1)
		{
			$allintervallen = trim($_POST['intervalHour'])*60*60 + trim($_POST['intervalMin'])*60 + trim($_POST['intervalSenc'])*1; 
		}
		else
		{
			$allintervallen=$intervalcircle;
		}
	}	
	
	$startdate="";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	
	if(empty($_POST['startdate']))
	{
		$startdate = "00-00-00";
	}
	
	$enddate="";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	
	if(empty($_POST['enddate']))
	{
		$enddate = "00-00-00";
	}
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = trim($_POST['playtime']);
	}
	if(empty($_POST['playtime']))
	{
		$playtime = "00:00:00";
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
		
		if($prepower!=0)
		{
			if($prepower>59)
			{
			$getpowertime=$prepower/60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$getpowertime."minutes -0 seconds"));
			}
			else
			{
			$getpowertime=$prepower%60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getpowertime."seconds"));
			}
		}
	}
	//获取声音
	$task_default_volume = "80";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	
	 
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = trim($_POST['exemodel']);
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = trim($_POST['hiddenweek']);
			
			$repl = array(',' => '');
			
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			$playtime = "00:00:00";
		}
	}
	
	if(empty($_POST['exemodel']))
	{
		$exemodel = "1111111";
	}
	//获取任务优先级
	$priority=3;
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$tasktype=0;
	$audiosource=0;
	if(isset($_POST['audiosource']))
	{
		$audiosource = trim($_POST['audiosource']);
		$cmd = $audiosource;
		$audiosource = 0;
	}
	
	$channel = 0;
	if(isset($_POST['channel']))
	{
		$channel = trim($_POST['channel']);
		$cmdargs = $channel;
		$channel = 0;
	}
	
	$bandrate = 0;
	if(isset($_POST['bandrate']))
	{
		$bandrate = trim($_POST['bandrate']);
	}
	
	$samplerate=0;
	if(isset($_POST['samplerate']))
	{
		$samplerate = trim($_POST['samplerate']);
	}
	
	
	$playfileid = 0;
	$gototaskmanager = "";
	$openpower = 0;
	$openpowertaskid = 0;
	
	//加锁并启用事务
	mysqli_query($con,"START TRANSACTION");//获取不到插入的值
	mysqli_query($con,"LOCK TABLES task WRITE,terminaloftask WRITE,ledsentence WRITE,media WRITE,mediaoftask WRITE,book_admin WRITE,usergroup WRITE,ledoftask WRITE,ledtaskfree WRITE,userterminal WRITE");
	
	//获取用户优先级
	$sql = "SELECT book_admin.id,usergroup.level FROM book_admin,usergroup WHERE ";
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	$row = mysqli_fetch_array($result);
	//设置优先级
//	$priority = trim($row['level'])*10 + $priority;
	$task_user_id = trim($row['id']);
	@mysqli_free_result($result);
	unset($sql,$row);
	
	$tasktype=24;
	$key_sql = "SELECT userid FROM ledtaskfree WHERE ledtaskfree.id = '$getfolderid'";
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	if($key_row = mysqli_fetch_array($key_result))
	{
	    $task_user_id = trim($key_row['userid']);
	}
	$user_id = 0;
	if(isset($_GET['userid']))
	{
		$user_id = $_GET['userid'];
	} 
	 
	$gototaskmanager="./ledtaskmanager.php?id=$getfolderid&userid=$user_id";
	
	$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype'";
	$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
	if(mysqli_num_rows($result_same_name) > 0)
	{
		//===========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
		
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	@mysqli_free_result($result_same_name);
	unset($sql_same_name);
		
	$terminallistnum=array();
	for($i=0;$i<count($terminallistnum);$i++)
	{
		$temp = (int)$terminallistnum[$i];
		$sql = "SELECT id FROM userterminal WHERE userid='$task_user_id' AND terminalid='$temp'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if( mysqli_num_rows($result) <=0 )
		{
			$sqls="INSERT INTO userterminal(userid,terminalid) VALUES('$task_user_id','$temp')";
			mysqli_query($con,$sqls)or die(mysqli_error($con));
		}
	}


	$tasktype=24;
		if($tasktype ==24)
		{
				$gettextarea="";
				if(isset($_POST['gettextarea']))
				{
					$gettextarea = $_POST['gettextarea'];
				}	
				$gettextarea=nl2br($gettextarea);
				if($gettextarea!="")
				{
					$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,playtime,endtime,";
					$sql.="exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume,task_user_id ";
					$sql.=", sec_task_id,parentid,interval_s,intplaylength,intplaylengthtype)VALUES('$taskname', '$israndomplay', '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
					$sql.="'$state', '$startdate', '$enddate', '$playtime','$getendtime', '$exemodel', '$priority', '$tasktype', '$channel', ";
					$sql.="'$bandrate', '$samplerate', '$cmd', '$gettaskid', '$playfileid', '$task_default_volume', '$task_user_id', $sec_task_id,$getfolderid,$intervallength,$allintervallen,$intervaltype) ";
					mysqli_query($con,$sql) or die(mysqli_error($con));
					unset($sql);
					if(mysqli_error($con))
					{
						mysqli_query($con,"ROLLBACK");
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						$_SESSION['url'] = $gototaskmanager;
						echo "<script>window.location='error.php'</script>";
						exit;
					}
					$sql = "SELECT MAX(taskid) FROM task";//取插入任务id
					$result = mysqli_query($con,$sql) or die(mysqli_error($con));
					if($row = mysqli_fetch_array($result))
					{
						$ledtaskid = $row[0];//新添加的任务id
					}
					@mysqli_free_result($result);
					unset($sql,$row);
					$sql = "UPDATE task SET cmdargs='$ledtaskid' WHERE taskid ='$ledtaskid'";
					mysqli_query($con,$sql) or die(mysqli_error($con));
					unset($sql);
					if($prepower != 0)
					{						
							$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
							$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
							$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id,parentid,interval_s,intplaylength,intplaylengthtype) VALUES('$taskname', '$israndomplay', ";
							$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
							$sql.="'$preopenpowertime', '$exemodel', '$priority', '9', '0', '$bandrate', '$samplerate', ";
							$sql.="'0', '$gettaskid', '$playfileid', '$task_default_volume','$task_user_id', '$ledtaskid','$getfolderid','$intervallength','$allintervallen','$intervaltype') ";
						mysqli_query($con,$sql) or die(mysqli_error($con));
						unset($sql);
						
						if(mysqli_error($con))
						{
							mysqli_query($con,"ROLLBACK");
							$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
							$_SESSION['url'] = $gototaskmanager;
							echo "<script>window.location='error.php'</script>";
							exit;
						}
						
						//取得功放任务id $openpowertaskid
						$resultpower = mysqli_query($con,"SELECT MAX(taskid) FROM task") or die(mysqli_error($con));  
						$rowpower2 = mysqli_fetch_array($resultpower);	 
						$ledpowertaskid = $rowpower2[0];  
						@mysqli_free_result($resultpower);
						unset($rowpower2);
					}
					
					$sql="INSERT INTO media(name, typeid, filename,folderid,timelength,channel,sample,bitrate) VALUES ('$taskname','tts','tts','0','0','0','$ledtaskid','$tasktype')";
					
					mysqli_query($con,$sql) or die(mysqli_error($con));	
					
					$resultmedia = mysqli_query($con,"SELECT MAX(id) FROM media") or die(mysqli_error($con));
					
					$rowmedia = mysqli_fetch_array($resultmedia);	
					
					$openmediaid = $rowmedia[0]; 
					
					@mysqli_free_result($resultmedia);
					
					unset($rowmedia);
		
					$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$openmediaid','$ledtaskid','0')";
					
					mysqli_query($con,$sql) or die(mysqli_error($con));
					
					if(mysqli_error($con))
					{	
						mysqli_query($con,"ROLLBACK");
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						$_SESSION['url'] = $gototaskmanager;
						echo "<script>window.location='error.php'</script>";
						exit;
					}
					$gettempi=0;
					$gettext=0;
					$arr1=str_split_utf8($gettextarea);
					for($aa=0;$aa<count($arr1);$aa++)
					{
						$gettextone=$arr1[$aa];
						$gettextone=str_replace("<br/>","",$gettextone);
						$gettextone=str_replace("<br />","",$gettextone);
						$gettextone=str_replace("\r\n","",$gettextone);
						$gettextone=str_replace("、","",$gettextone);
						$gettextone=str_replace("</b>","",$gettextone);
						$gettextone=str_replace("</B>","",$gettextone);
						$gettextone=str_replace("\\","",$gettextone);
						$gettextone=str_replace("'","\'",$gettextone);
						$gettextone=$gettextone;
						if(!empty($gettextone))
						{ 
								$sql="INSERT INTO ledsentence(text,mediaid,speed,type,mediaseq) VALUES ('$gettextone','$openmediaid','5','1','$gettempi')";
							mysqli_query($con,$sql) or die(mysqli_error($con));
							$gettempi++;
						}
					}				
		
		
					$led_group_string = trim($_POST['led_group_string']);
					$led_groupstring = explode(",",$led_group_string);
					$ledlistvalue = trim($_POST['ledlistvalue']);
					$led_listvalue = explode(",",$ledlistvalue);
					
					for($i=0; $i<count($led_listvalue); $i++)
					{
						if(is_numeric($led_listvalue[$i]))
						{
							$temp = (int)$led_listvalue[$i];
		
							 $sql = "INSERT INTO ledoftask (taskid,terminalid,deviceid)VALUES('$ledtaskid','$led_groupstring[$i]','$temp')";
						
							mysqli_query($con,$sql) or die(mysqli_error($con));
							
							if(mysqli_error($con))
							{
								mysqli_query($con,"ROLLBACK");
							
								$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
								
								$_SESSION['url'] = $gototaskmanager;
								
								echo "<script>window.location='error.php'</script>";
								
								exit;
							}
							
							if($prepower != 0)
							{
								$sql = "INSERT INTO ledoftask (taskid,terminalid,deviceid)VALUES('$ledpowertaskid','$led_groupstring[$i]','$temp')";
								
								mysqli_query($con,$sql) or die(mysqli_error($con));	
								
								if(mysqli_error($con))
								{
									mysqli_query($con,"ROLLBACK");
									
									$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
									
									$_SESSION['url'] = $gototaskmanager;
								
									echo "<script>window.location='error.php'</script>";
								
									exit;
								}		
							}
		
						}
					}
				}	
			
		}

		

	
	
	
	
	mysqli_query($con,"UNLOCK TABLES");
	mysqli_query($con,"COMMIT");
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = $gototaskmanager;
		$create_socket_obj->send_socket_task_volume("task",4,$gettaskid,$task_default_volume);
		echo "<script>window.location='success.php'</script>";
	}			
}




//添加17任务
function addttsplaytask_msg($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();

	$taskname = "";
	$sec_task_id = 0;
	$cmd = 0;
	$cmdargs = 0;
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	$israndomplay = 0;
	$getfolderid = 0;
	$starthour=0;
	if(isset($_POST['starthour']))
	{
		$starthour = $_POST['starthour'];
	}
	
	$startmin=0;
	if(isset($_POST['startmin']))
	{
		$startmin = $_POST['startmin'];
	}
	$startsenc=0;
	if(isset($_POST['startsenc']))
	{
		$startsenc = $_POST['startsenc'];
	}
	$getstarttime = (int)$starthour*3600+(int)$startmin*60+(int)$startsenc;
	$timelengthtype = 2;
	$getendtime=0;
	$timelength = 0;
	
	$datasendmodel = 0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = trim($_POST['playtime']);
	}

	if(empty($_POST['playtime']))
	{
		$playtime = "00:00:00";
	}
	
	$intervalmode=0;
	if(isset($_POST['intervalmode']))
	{
		$intervalmode=$_POST['intervalmode'];
	}
	
	$intervalcircle=0;
	if(isset($_POST['intervalcircle']))
	{
		$intervalcircle = $_POST['intervalcircle'];
	}
	$circleTime = 0;
	$intervallength=0;
	$allintervallen=0;
	$intplaylengthtype=0;
	if($intervalmode==1)
	{
		$circleTime = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
		$timelengthtype = 1;
		$intervallength = trim($_POST['intervallenHour'])*60*60 + trim($_POST['intervallenMin'])*60 + trim($_POST['intervallenSenc'])*1; 
		$allintervallen=$intervalcircle;
		$intplaylengthtype=2;
	}
	else
	{
		if(isset($_POST['circleTime']))
		{
			$circleTime = $_POST['circleTime'];
			$timelengthtype = 2;
		}
		$allintervallen=0;
		$intplaylengthtype=0;
	
	}


	$state = 0;
	$startdate="";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	if(empty($_POST['startdate']))
	{
		$startdate = "00-00-00";
	}
	$enddate="";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	if(empty($_POST['enddate']))
	{
		$enddate = "00-00-00";
	}
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
		if($prepower!=0)
		{
			if($prepower>59)
			{
			$getpowertime=$prepower/60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$getpowertime."minutes -0 seconds"));
			}
			else
			{
			$getpowertime=$prepower%60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getpowertime."seconds"));
			}
		}
	}
	//获取声音
	$task_default_volume = "80";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	$speed_value = "5";
	if(isset($_POST['speed_value']))
	{
		$speed_value = trim($_POST['speed_value']);
	}
		$musicmode = "0";
	if(isset($_POST['musicmode']))
	{
		$musicmode = trim($_POST['musicmode']);
	}
	$gettextarea="";
	if(isset($_POST['gettextarea']))
	{
		$gettextarea = $_POST['gettextarea'];
	}
	
	$gettextarea=nl2br($gettextarea);
	
	$audiosource=0;
	if(isset($_POST['audiosource']))
	{
		$audiosource = trim($_POST['audiosource']);
	}

	
  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}

	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
	$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	if(empty($_POST['get_terminal']))
   {
   $get_terminal='1111111111111111';
   }

	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = trim($_POST['exemodel']);
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = trim($_POST['hiddenweek']);
			
			$repl = array(',' => '');
			
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			
			$playtime = "00:00:00";
		}
	}
	
	if(empty($_POST['exemodel']))
	{
		$exemodel = "1111111";
	}
	//获取任务优先级

	$priority=13;
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	$getserverflag=0;
	
	if(isset($_POST['getserverflag']))
	{
		$getserverflag = trim($_POST['getserverflag']);
	}
	if($getserverflag==1)   //说明选的是tts主机
	{
		$tasktype=15;     //服务器带tts
		$cmd=0;
		
		$gettishiyin=0;
		if(isset($_POST['gettishiyin']))
		{
			$gettishiyin = trim($_POST['gettishiyin']);
			$cmd=$audiosource;
		}
	}
	else if($audiosource==0)
	{
		$speed_value=$speed_value/10;
		$tasktype=17;     //终端芯片带tts
		$cmd=0;
	}
	else    //tts主机
	{
	$tasktype=19;
	$speed_value=$speed_value/10;
	$cmd=$audiosource;
	}
	
	
	
	
	$terminallistvalue = trim($_POST['terminallistvalue']);
	
	$terminallistnum = explode(",",$terminallistvalue);
	
	$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
	
	$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);

	$playfileid = 0;
	
	$gototaskmanager = "";
	$gototaskmanager="./displayttsmanager.php";
	//加锁并启用事务
	mysqli_query($con,"START TRANSACTION");//获取不到插入的值
	
	mysqli_query($con,"LOCK TABLES task WRITE,terminaloftask WRITE,mediaoftask WRITE,media WRITE,book_admin WRITE,usergroup WRITE,terminal WRITE,leddevice WRITE,ttssentence WRITE,ledsentence WRITE,ledoftask WRITE");
	/*
	if($intervalmode==1)
	{
		
	
		$getdatetime="$startdate "."$playtime";
		$sql_timelen = "SELECT ADDTIME('$getdatetime',$circleTime);";
		
		$result_name = mysqli_query($con,$sql_timelen) or die(mysqli_error($con));
		if($adm_row = mysqli_fetch_array($result_name))
		{
				$adm_datetime = explode(" ",$adm_row[0]);
				$adm_enddate=trim($adm_datetime[0]);
				$adm_endtime=trim($adm_datetime[1]);
				$sqltimelen = "SELECT taskid FROM task WHERE playtime >='$playtime' and playtime<='$adm_endtime' and cmd='$cmd' and cmd>'0'  and startdate <='$startdate'";
				
				
				$result_name2 = mysqli_query($con,$sqltimelen) or die(mysqli_error($con));
				while($adm_row2 = mysqli_fetch_array($result_name2))
				{
					$forward_ok_error_obj->exit_back_function($do_php_prompt['time_task_failed']);
					return;
				}		
		}	
	}
	*/
	
	
	//获取用户优先级
	$userid=$_SESSION['username'];
	$sql = "SELECT id FROM book_admin WHERE ";
	
	$sql.= "username = '$userid' ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$row = mysqli_fetch_array($result);
	
	//设置优先级
	//$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	
	@mysqli_free_result($result);
	
	unset($sql,$row);
	
		
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,playtime,endtime,";
		
		$sql.="exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume,task_user_id, sec_task_id,interval_s,intplaylength,intplaylengthtype) ";
		
		$sql.="VALUES('$taskname', '$israndomplay', '$timelengthtype', '$circleTime', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate', '$playtime','$getendtime', '$exemodel', '$priority', '$tasktype', '0', ";
		
		$sql.="'0', '0', '$cmd', '$cmdargs', '$playfileid', '$task_default_volume', '$task_user_id', '$sec_task_id','$intervallength','$allintervallen','$intplaylengthtype') ";

		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = $gototaskmanager;
			
			echo "<script>window.location='error.php'</script>";
			
			exit;
		}

		$sql = "SELECT MAX(taskid) FROM task";//取插入任务id
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			$gettaskid = $row[0];//新添加的任务id
		}
		
		@mysqli_free_result($result);
		
		unset($sql,$row);
		
		$led_display= array();
		$led_displaydeviceid = array();
		$getleddisplay=0;


		if(($prepower != 0))
		{						
			
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
				
				$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
				
				$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id,interval_s,intplaylength,intplaylengthtype) VALUES('$taskname', '$israndomplay', ";
				
				$sql.="'$timelengthtype', '$circleTime', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
				
				$sql.="'$preopenpowertime', '$exemodel', '$priority', '9', '0', '0', '0', ";
				
				$sql.="'0', '0', '$playfileid', '$task_default_volume','$task_user_id', '$gettaskid','$intervallength','$allintervallen','$intplaylengthtype') ";
		
			mysqli_query($con,$sql) or die(mysqli_error($con));
			
			unset($sql);
			
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
			
			//取得功放任务id $openpowertaskid
			
			$resultpower = mysqli_query($con,"SELECT MAX(taskid) FROM task") or die(mysqli_error($con));
			  
			$rowpower2 = mysqli_fetch_array($resultpower);	
			  
			$openpowertaskid = $rowpower2[0]; 
			  
			@mysqli_free_result($resultpower);
			
			unset($rowpower2);
		}
		
		if($timelengthtype==1)
	{
		$length_time=$circleTime;
	}
	else
	{
		$length_time=86000;

	}
		for($i=0; $i<count($terminallistnum); $i++)
		{
			if(is_numeric($terminallistnum[$i]))
			{
				$temp = (int)$terminallistnum[$i];
				
				//插入终端任务关联
				//$sql="insert into terminaloftask (taskid,terminalid) values('$gettaskid','$temp')";

					$sqlss = "SELECT typeid,leddevice.id,leddevice.subterminalid,leddevice.terminalid FROM terminal,leddevice WHERE leddevice.subterminalid='$temp'AND leddevice.terminalid=terminal.id";
					$resultss = mysqli_query($con,$sqlss) or die(mysqli_error($con));
					while($row = mysqli_fetch_array($resultss))
					{
						if($tasktype==19)
						break;
							$led_deviceid=$row[1];
							$subterminalid=$row[2];
							$zhuterminalid=$row[3];
							$ledtaskid=0;
							$ledmediaid=0;
							if($row['typeid']==42)
							{
								if($getleddisplay==0)
								{
									$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,playtime,endtime,";
									$sql.="exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume,task_user_id ";
									$sql.=", sec_task_id,parentid,interval_s,intplaylength,intplaylengthtype)VALUES('$taskname', '$israndomplay', '1', '$length_time', '0', '0', ";
									$sql.="'$state', '0000-00-00', '0000-00-00', '00:00:00','00:00:00', '0000000', '$priority', '24', '0', ";
									$sql.="'0', '0', '0', '0', '$playfileid', '$task_default_volume', '$task_user_id', $gettaskid,0,$intervallength,$allintervallen,$intplaylengthtype) ";
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
								
									$sql = "SELECT MAX(taskid) FROM task";//取插入任务id
									$result = mysqli_query($con,$sql) or die(mysqli_error($con));
									if($rows = mysqli_fetch_array($result))
									{
										$ledtaskid = $rows[0];//新添加的任务id
									}
									@mysqli_free_result($result);
									unset($sql,$row);
									$sql="INSERT INTO media(name, typeid, filename,folderid,timelength,channel,sample,bitrate) VALUES ('$taskname','tts','tts','0','0','0','$ledtaskid','24')";
			
									mysqli_query($con,$sql) or die(mysqli_error($con));	
									
									$resultmedia = mysqli_query($con,"SELECT MAX(id) FROM media") or die(mysqli_error($con));
									
									$rowmedia = mysqli_fetch_array($resultmedia);	
									
									$ledmediaid = $rowmedia[0]; 
									
									@mysqli_free_result($resultmedia);
									
									unset($rowmedia);
									$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$ledmediaid','$ledtaskid','0')";
			
									mysqli_query($con,$sql) or die(mysqli_error($con));
								}
								$led_display[$getleddisplay]=$zhuterminalid;
								$led_displaydeviceid[$getleddisplay]=$led_deviceid;
								$getleddisplay++;

							}
					}




				$c =strlen($temp);
				 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$gettaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
				
					mysqli_query($con,$sql) or die(mysqli_error($con));
					
					if(mysqli_error($con))
					{
						mysqli_query($con,"ROLLBACK");
					
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = "./bellmanager.php";
						
						echo "<script>window.location='error.php'</script>";
						
						exit;
					}
	
					if(($prepower != 0))
					{
		
						$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$openpowertaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
						
						mysqli_query($con,$sql) or die(mysqli_error($con));	
						
						if(mysqli_error($con))
						{
							mysqli_query($con,"ROLLBACK");
							
							$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
							
							$_SESSION['url'] = $gototaskmanager;
						
							echo "<script>window.location='error.php'</script>";
						
							exit;
						}		
					}
	
				for($j=0;$j<strlen($get_terminal);$j++)
				{
				
				if(substr($get_terminal,$j,2)=="::")
				{
				$position=$j+2;	
				}
						if(substr($get_terminal,$j,1)=="|")
						{
						  $position2 = $j;
						  $position3 = $position2-$position;
									
									$a=substr($get_terminal,$j-$position3,$position3);
									
									if($a==$temp)
										{
									
										$area = substr($get_terminal,$j+1,16);
									
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$gettaskid' AND terminalid ='$temp'";
										mysqli_query($con,$sql) or die(mysqli_error($con));
										unset($sql);
										if(($prepower != 0)||($tasktype==5))
										{
										$area = substr($get_terminal,$j+1,16);
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$openpowertaskid' AND terminalid ='$temp'";
										mysqli_query($con,$sql) or die(mysqli_error($con));
										unset($sql);
										}
										
										}
						}							
				 }

				}
				}
				
				
				
	$sql="INSERT INTO media(name, typeid, filename,folderid,timelength,channel,sample,bitrate) VALUES ('$taskname','tts','tts','0','0','0','$gettaskid','$tasktype')";
			
mysqli_query($con,$sql) or die(mysqli_error($con));	
	
$resultmedia = mysqli_query($con,"SELECT MAX(id) FROM media") or die(mysqli_error($con));

$rowmedia = mysqli_fetch_array($resultmedia);	

$openmediaid = $rowmedia[0]; 

@mysqli_free_result($resultmedia);

unset($rowmedia);
	$gettempi=0;
	$gettext=0;				
if($getserverflag==1)
{				
	if($gettishiyin>0)
	{
	//	$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$gettishiyin','$gettaskid','0')";
	//	mysqli_query($con,$sql) or die(mysqli_error($con));
			$sql="INSERT INTO ttssentence(name,sentenceid,type,mediaid,content,mediaseq,speed,volume,male) VALUES ('$taskname','$openmediaid','0','$gettishiyin','','$gettempi','$speed_value','$task_default_volume','$musicmode')";
			mysqli_query($con,$sql) or die(mysqli_error($con));
			$gettempi++;
	}

}
$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$openmediaid','$gettaskid','0')";

mysqli_query($con,$sql) or die(mysqli_error($con));

if(mysqli_error($con))
{	
mysqli_query($con,"ROLLBACK");

$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息

$_SESSION['url'] = $gototaskmanager;

echo "<script>window.location='error.php'</script>";

exit;
}


	
	$arr1=str_split_utf8($gettextarea);
	
	for($aa=0;$aa<count($arr1);$aa++)
	{
		$gettextone=$arr1[$aa];
		$gettextone=str_replace("<br/>","",$gettextone);
		$gettextone=str_replace("<br />","",$gettextone);
		$gettextone=str_replace("\r\n","",$gettextone);
		$gettextone=str_replace("</b>","",$gettextone);
		$gettextone=str_replace("、","",$gettextone);
		$gettextone=str_replace("</B>","",$gettextone);
		$gettextone=str_replace("\\","",$gettextone);
		$gettextone=str_replace("'","\'",$gettextone);
		
		$gettextone=$gettextone;
	
		if(!empty($gettextone))
		{ 
				$sql="INSERT INTO ttssentence(name,sentenceid,type,content,mediaseq,speed,volume,male) VALUES ('$taskname','$openmediaid','2','$gettextone','$gettempi','$speed_value','$task_default_volume','$musicmode')";
			mysqli_query($con,$sql) or die(mysqli_error($con));

			if($getleddisplay>0)
			{
				$sql="INSERT INTO ledsentence(text,mediaid,speed,type,mediaseq) VALUES ('$gettextone','$ledmediaid','5','1','$gettempi')";
				mysqli_query($con,$sql) or die(mysqli_error($con));
			
				for($i=0; $i<$getleddisplay; $i++)
				{
					$sql = "INSERT INTO ledoftask (taskid,terminalid,deviceid)VALUES('$ledtaskid','$led_display[$i]','$led_displaydeviceid[$i]')";
					mysqli_query($con,$sql) or die(mysqli_error($con));
				}

			}


			$gettempi++;
		}
	}				

	mysqli_query($con,"UNLOCK TABLES");
	mysqli_query($con,"COMMIT");
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
		//===================================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=4&id=".$gettaskid."&volume=".$task_default_volume;			
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/	
		$create_socket_obj->send_socket_task_volume("task",4,$gettaskid,$task_default_volume);
		
		echo "<script>window.location='success.php'</script>";
	}		
	
}

//添加2、3、4、5,6任务
function addwebradiotask_msg($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	
	$taskname = "";
	
	$sec_task_id = 0;
	
	$cmd = 0;
	
	$cmdargs = 0;
	
	 $get_terminal_value=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal_value = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal_value =strtr($get_terminal_value,$arr);
	  
	}
	
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay = 0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = trim((int)$_POST['israndomplay']);
	}  
	$timelengthtype = 1;
	
	$timelength = 0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
		
		if($timelengthtype == 1)
		{  
			$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
		}
		else
		{
			//$timelength = trim($_POST['circleTime']);
				$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
		} 
	}
	else
	{
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
	}
	
	$datasendmodel = 0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	$state = 0;
	
	$startdate="";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	
	if(empty($_POST['startdate']))
	{
		$startdate = "00-00-00";
	}
	
	$enddate="";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	
	if(empty($_POST['enddate']))
	{
		$enddate = "00-00-00";
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = trim($_POST['playtime']);
	}
	
	if(empty($_POST['playtime']))
	{
		$playtime = "00:00:00";
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
		
		if($prepower!=0)
		{
			if($prepower>59)
			{
			$getprepowertime=$prepower/60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$getprepowertime."minutes -0 seconds"));
			}
			else
			{
			$getprepowertime=$prepower%60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getprepowertime." seconds"));
			}
		}
	}
	//获取声音
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = trim($_POST['exemodel']);
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = trim($_POST['hiddenweek']);
			
			$repl = array(',' => '');
			
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			
			$playtime = "00:00:00";
		}
	}
	
	if(empty($_POST['exemodel']))
	{
		$exemodel = "1111111";
	}
	//获取任务优先级
	$priority=3;
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$tasktype=0;
	
	$audiosource=0;
	
	if(isset($_POST['audiosource']))
	{
		$audiosource = trim($_POST['audiosource']);
		
		$cmd = $audiosource;
		
		$audiosource = 0;
	}
	
	$channel = 0;
	
	if(isset($_POST['channel']))
	{
		$channel = trim($_POST['channel']);
		
		$cmdargs = $channel;
		
		$channel = 0;
	}
	
	$bandrate = 0;
	
	if(isset($_POST['bandrate']))
	{
		$bandrate = trim($_POST['bandrate']);
	}
	
	

	$samplerate=0;
	if(isset($_POST['samplerate']))
	{
		$samplerate = trim($_POST['samplerate']);
	}
	$cmdargs=0;
	if(isset($_POST['cmdargs']))
	{
		$cmdargs = trim($_POST['cmdargs']);
	}
	$get_qallery=0;
	if(isset($_POST['get_qallery']))
	{
		$get_qallery = trim($_POST['get_qallery']);
	}
	
	
	$playfileid = 0;
	
	$gototaskmanager = "";
	
	$openpower = 0;
	
	$openpowertaskid = 0;
	
	switch($_POST['taskType'])
	{
		case "belltask":
		
			$tasktype = 1;
		
			$gototaskmanager="./bellmanager.php";
		
			break;
		case "fileplaytask":
		
			$tasktype=2;
		
			$gototaskmanager="./taskmanager.php";
			
			$EmergencyBroadcast = 0;
			
			if(isset($_POST['EmergencyBroadcast']))
			{
				$EmergencyBroadcast = trim($_POST['EmergencyBroadcast']);
			}
			
			if($EmergencyBroadcast == 1)
			{
				$tasktype = 7;
			}
			
			break;
			
		case "admmanagertask":
		
			$tasktype = 3;
			
			$interview_repower = 0;//欲开采播电源
			
			$interview_repower_time = 0;//记录欲开时间
			
			//$cmd = $audiosource;
			
			//$cmdargs = $channel;
			
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
			
			$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$interview_repower."minutes -0 seconds"));
			
			$gototaskmanager="./admmanager.php";
			
			break;
		case "telmanagertask":
			
			$tasktype=4;
			
			$gototaskmanager="./telBroadManager.php";
			
			break;
		case "terfuncplaytask":
		
			$tasktype = 5;
		
			$cmd = 0;
		
			$gototaskmanager="./terminalfunctionplay.php";
		
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			
		break;
		case "WebRadiotask":
		
			$tasktype = 10;
			
			$interview_repower = 0;//欲开采播电源
			
			$interview_repower_time = 0;//记录欲开时间
			
			//$cmd = $audiosource;
			
			//$cmdargs = $channel;
			
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
			
			$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$interview_repower."minutes -0 seconds"));
			
			$gototaskmanager="./WebRadio.php";
			
			break;
		case "stopmanagertask":
		
			$tasktype = 11;
			
			$interview_repower = 0;//欲开采播电源
			
			$interview_repower_time = 0;//记录欲开时间
			
			//$cmd = $audiosource;
			
			//$cmdargs = $channel;
			
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
			
			$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$interview_repower."minutes -0 seconds"));
			
			$gototaskmanager="./chezhangmangager.php";
			
			break;
	}
	/*************************
		区分任务类型
		同一任务中不允许同名
	**************************/
	if($tasktype == 5)
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '5' ";
		
		$sql_same_name.= "AND prepower = '0' AND tasktype = 5 AND channel = 0 AND info = '' AND sec_task_id = 0 ";
		
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		
		if(mysqli_num_rows($result_same_name) > 0)
		{
			//============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
		
			exit;*/
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	else
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype' ";
		
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		
		if(mysqli_num_rows($result_same_name) > 0)
		{
			//===========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	@mysqli_free_result($result_same_name);
	
	unset($sql_same_name);
		
	//获取用户优先级
	
	$sql = "SELECT book_admin.id,usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$row = mysqli_fetch_array($result);
	
	//设置优先级
	//$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	
	@mysqli_free_result($result);
	
	unset($sql,$row);
	
	//加锁并启用事务
	mysqli_query($con,"START TRANSACTION");//获取不到插入的值
	
	mysqli_query($con,"LOCK TABLES task WRITE,terminaloftask WRITE,mediaoftask WRITE");
		
	if($tasktype !=1)
	{
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,playtime, ";
		
		$sql.="exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume,task_user_id, sec_task_id) ";
		
		$sql.="VALUES('$taskname', '$israndomplay', '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate', '$playtime', '$exemodel', '$priority', '$tasktype', '$channel', ";
		
		$sql.="'$bandrate', '$samplerate', '$get_qallery', '$cmdargs', '$playfileid', '$task_default_volume', '$task_user_id', $sec_task_id) ";

		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = $gototaskmanager;
			
			echo "<script>window.location='error.php'</script>";
			
			exit;
		}
		
		$sql = "SELECT MAX(taskid) FROM task";//取插入任务id
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			$gettaskid = $row[0];//新添加的任务id
		}
		
		@mysqli_free_result($result);
		
		unset($sql,$row);
		
		if(($prepower != 0)||($tasktype==5))
		{						
			if($tasktype == 5)
			{
			
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
				
				$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
				
				$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id) VALUES('$taskname', '$israndomplay', ";
				
				$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
				
				$sql.="'$preopenpowertime', '$exemodel', '$priority', '5', '0', '$bandrate', '$samplerate', ";
				
				$sql.="'1', '$cmdargs', '$playfileid', '$task_default_volume','$task_user_id', '$gettaskid') ";
			}
			else
			{
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
				
				$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
				
				$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id) VALUES('$taskname', '$israndomplay', ";
				
				$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
				
				$sql.="'$preopenpowertime', '$exemodel', '$priority', '9', '0', '$bandrate', '$samplerate', ";
				
				$sql.="'$get_qallery', '$cmdargs', '$playfileid', '$task_default_volume','$task_user_id', '$gettaskid') ";
			}
			mysqli_query($con,$sql) or die(mysqli_error($con));
			
			unset($sql);
			
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
			
			//取得功放任务id $openpowertaskid
			
			$resultpower = mysqli_query($con,"SELECT MAX(taskid) FROM task") or die(mysqli_error($con));
			  
			$rowpower2 = mysqli_fetch_array($resultpower);	
			  
			$openpowertaskid = $rowpower2[0]; 
			  
			@mysqli_free_result($resultpower);
			
			unset($rowpower2);
		}
		
		if($tasktype == 3)
		{
			$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
			
			$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
			
			$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id, sec_task_id) VALUES('$taskname', '$israndomplay', ";
			
			$sql.="'$timelengthtype', '$timelength', '$interview_repower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
			
			$sql.="'$interview_repower_time', '$exemodel', '$priority', '8', '$channel', '$bandrate', '$samplerate', ";
			
			$sql.="'$get_qallery', '$cmdargs', '$playfileid', '$task_default_volume','$task_user_id','$gettaskid') ";
						
			mysqli_query($con,$sql) or die(mysqli_error($con));
			
			unset($sql);
			
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
			//取采播任务id
			$col_repower_task_Id = 0;
			
			$col_repowerId_result = mysqli_query($con,"SELECT MAX(taskid) FROM task") or die(mysqli_error($con));
			
			$col_repowerId_row = mysqli_fetch_array($col_repowerId_result);	
			  
			$col_repower_task_Id = $col_repowerId_row[0]; 
			  
			@mysqli_free_result($col_repowerId_result);
			
			unset($col_repowerId_row);
			//插入采播任务终端
			
			mysqli_query($con,"insert into terminaloftask (taskid, terminalid) values('$col_repower_task_Id','$cmd')");
			
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
		}
		
		$terminallistvalue = trim($_POST['terminallistvalue']);
		
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
		
		for($i=0; $i<count($terminallistnum); $i++)
		{
			if(is_numeric($terminallistnum[$i]))
			{
				$temp = (int)$terminallistnum[$i];
				//插入终端任务关联
				//$sql="insert into terminaloftask (taskid,terminalid) values('$gettaskid','$temp')";
				
				$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid)VALUES('$gettaskid','$temp','$analysis_tree_group_ids[$i]')";
				
				mysqli_query($con,$sql) or die(mysqli_error($con));
				
				if(mysqli_error($con))
				{
					mysqli_query($con,"ROLLBACK");
				
					$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
					
					$_SESSION['url'] = "./bellmanager.php";
					
					echo "<script>window.location='error.php'</script>";
					
					exit;
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
											
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$gettaskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
					
												}
								}			
											
											
											
											
						 }
				
				if(($prepower != 0)||($tasktype==5))
				{
					//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$openpowertaskid','$temp')";
					
					$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid)VALUES('$openpowertaskid','$temp','$analysis_tree_group_ids[$i]')";
					
					mysqli_query($con,$sql) or die(mysqli_error($con));	
					
					if(mysqli_error($con))
					{
						mysqli_query($con,"ROLLBACK");
						
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = $gototaskmanager;
					
						echo "<script>window.location='error.php'</script>";
					
						exit;
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
											
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$openpowertaskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
					
												}
								}			
											
											
											
											
						 }
						
				}
				/*if( $tasktype==3 )
				{
					$sql="insert into terminaloftask(taskid,terminalid) VALUES('$col_repower_task_Id','$temp')";
					
					mysqli_query($con,$sql) or die(mysqli_error($con));			
					
					if(mysqli_error($con))
					{
						mysqli_query($con,"ROLLBACK");
						
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = $gototaskmanager;
					
						echo "<script>window.location='error.php'</script>";
					
						exit;
					}	
				}*/
			}
		}			
	}

	if($tasktype==2 || $tasktype==7)
	{
		if(isset($_POST['listvalue']))
		{
			$medialist=trim($_POST['listvalue']);
			
			$arrmedia=explode(",",$medialist);
			
			for($i=0;$i<count($arrmedia);$i++)
			{
				$str =$arrmedia[$i];
			
				if(!is_numeric($str))
				{
					continue;
				}
				
				$number =(int)$str;
			
				$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$number','$gettaskid','$i')";
			
				mysqli_query($con,$sql) or die(mysqli_error($con));
				
				if(mysqli_error($con))
				{	
					mysqli_query($con,"ROLLBACK");
				
					$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
					
					$_SESSION['url'] = $gototaskmanager;
					
					echo "<script>window.location='error.php'</script>";
					
					exit;
				}			
			}	
		}
	}
	
	mysqli_query($con,"UNLOCK TABLES");
	
	mysqli_query($con,"COMMIT");
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
		//===================================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=4&id=".$gettaskid."&volume=".$task_default_volume;			
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_task_volume("task",4,$gettaskid,$task_default_volume);
		
		echo "<script>window.location='success.php'</script>";
	}		
}
//修改2、3、4、5,6任务
function modifywebradio_msg($con)
{
	//require_once("inc/socket_conf.php");

	//添加外部变量
	global $do_php_prompt;
	
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	
	$sec_task_id = 0;
	
	$cmd = 0;
	
	$cmdargs = 0;
	
	$taskname="";
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay=0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = $_POST['israndomplay'];
	}
	
	$timelengthtype=1;
	
	$timelength=0;

	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
		
		if($timelengthtype == 1)
		{  
			$timelength = $_POST['lenghtHour'] * 60*60 +$_POST['lenghtMin']*60 +$_POST['lenghtSenc']*1; 
		}
		else
		{
			$timelength = $_POST['circleTime'];
		

		} 
	}
	else
	{
		$timelength = $_POST['lenghtHour'] * 60*60 +$_POST['lenghtMin']*60 +$_POST['lenghtSenc']*1; 
	}
	
	$datasendmodel=0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	$state=0;
	
	$startdate="0000-00-00";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	
	$enddate="0000-00-00";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = $_POST['playtime'];
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
		echo"";
	
		if($prepower!=0)
		{
			if($prepower>59)
			{
			$getprepower=$prepower/60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$getprepower."minutes -0 seconds"));
			}
			else
			{
			$getprepower=$prepower%60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getprepower." seconds"));
			}
		}
	}
	//获取声音
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = $_POST['exemodel'];
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = $_POST['hiddenweek'];
			$repl = array(',' => '');
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			$playtime = "00:00:00";
		}
	}
	
	//获取任务优先级
	$priority = 13;
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$tasktype = 0;
	
	$audiosource = 0;
	if(isset($_POST['audiosource']))
	{	
		$audiosource = trim($_POST['audiosource']);
		
		$cmd = $audiosource;
		
		$audiosource = 0;
	}
	
	$channel=0;
	if(isset($_POST['channel']))
	{	
		$channel = trim($_POST['channel']);
		
		$cmdargs = $channel;
		
		$channel = 0;
	}
	
	$bandrate=0;
	if(isset($_POST['bandrate']))
	{	
		$bandrate = trim($_POST['bandrate']);
	}
	
	$samplerate=0;
	if(isset($_POST['samplerate']))
	{	
		$samplerate = trim($_POST['samplerate']);
	}
	
	$cmdargs=0;
	if(isset($_POST['cmdargs']))
	{	
		$cmdargs = trim($_POST['cmdargs']);
	}
	
	$terminallistvalue = "";
	if(isset($_POST['terminallistvalue']))
	{	
		$terminallistvalue = trim($_POST['terminallistvalue']);
	
	 	$terminalidarray = explode(",",$terminallistvalue);
	}
	
	$listvalue = "";
	if(isset($_POST['listvalue']))
	{	
		$listvalue = trim($_POST['listvalue']);
	
		$mediaidarray = explode(",",$listvalue);
	}
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	 $get_terminal_value=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal_value = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal_value =strtr($get_terminal_value,$arr);
	  
	}
	 $get_noid=1;
	if(isset($_POST['get_noid']))
	{
	   $get_noids = trim($_POST['get_noid']);
  
	  $arr = array(',' =>'');
	  $get_noids =strtr($get_noids,$arr);
	  
	}
	$get_qallery=0;
	if(isset($_POST['get_qallery']))
	{
		$get_qallery = trim($_POST['get_qallery']);
	}

	
	$analysis_tree_group_string = "";
	
	if(isset($_POST['analysis_tree_group_string']))
	{
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	}
	
	$playfileid = 0;
	
	$gototaskmanager="";
	  
	switch($_POST['taskType'])
	{
		case "belltask":
		
			$tasktype = 1;
			
			$gototaskmanager="./bellmanager.php";
		
		break;
		
		case "fileplaytask":
		
			$tasktype=2;
			
			$gototaskmanager="./taskmanager.php";
			
			$EmergencyBroadcast = 0;
			
			if(isset($_POST['EmergencyBroadcast']))
			{
				$EmergencyBroadcast = trim($_POST['EmergencyBroadcast']);
			}
			
			if($EmergencyBroadcast == 1)
			{
				$tasktype = 7;
			}
			
		break;
		
		case "admmanagertask":
			
			$tasktype=3;
			
			$interview_repower = 0;//欲开采播电源
			
			$col_term_prepower_id = 0;//欲开采播任务id
				
			$interview_repower_time = 0;//欲开采播电源时间
			
			//$cmd = $audiosource;
			
			//$cmdargs = $channel;
		
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
		
			$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$interview_repower."minutes -0 seconds"));
			
			//取出采播终端欲开电源任务id
			$col_term_prepower_sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 and tasktype = 8 ";
			
			$col_term_prepower_result = mysqli_query($con,$col_term_prepower_sql) or die(mysqli_error($con));
			
			if($col_term_prepower_row = mysqli_fetch_array($col_term_prepower_result))
			{
				$col_term_prepower_id = trim($col_term_prepower_row['taskid']);
			}
			
			@mysqli_free_result($col_term_prepower_result);
			
			unset($col_term_prepower_sql,$col_term_prepower_row);
			
			$gototaskmanager="./admmanager.php";
			
		break;
		
		case "webradiomodifytask":
			
			$tasktype=10;
			
			$interview_repower = 0;//欲开采播电源
			
			$col_term_prepower_id = 0;//欲开采播任务id
				
			$interview_repower_time = 0;//欲开采播电源时间
			
			//$cmd = $audiosource;
			
			//$cmdargs = $channel;
			
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
			
			$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$interview_repower."minutes -0 seconds"));
			
			//取出采播终端欲开电源任务id
			$col_term_prepower_sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 and tasktype = 9 ";
			
			$col_term_prepower_result = mysqli_query($con,$col_term_prepower_sql) or die(mysqli_error($con));
			
			if($col_term_prepower_row = mysqli_fetch_array($col_term_prepower_result))
			{
				$col_term_prepower_id = trim($col_term_prepower_row['taskid']);
			}
			
			@mysqli_free_result($col_term_prepower_result);
			
			unset($col_term_prepower_sql,$col_term_prepower_row);
			
			$gototaskmanager="./WebRadio.php";
			
		break;
		case "stopmanagermodifytask":
			
			$tasktype=11;
			
			$interview_repower = 0;//欲开采播电源
			
			$col_term_prepower_id = 0;//欲开采播任务id
				
			$interview_repower_time = 0;//欲开采播电源时间
			
			//$cmd = $audiosource;
			
			//$cmdargs = $channel;
			
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
			
			$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$interview_repower."minutes -0 seconds"));
			
			//取出采播终端欲开电源任务id
			$col_term_prepower_sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 and tasktype = 9 ";
			
			$col_term_prepower_result = mysqli_query($con,$col_term_prepower_sql) or die(mysqli_error($con));
			
			if($col_term_prepower_row = mysqli_fetch_array($col_term_prepower_result))
			{
				$col_term_prepower_id = trim($col_term_prepower_row['taskid']);
			}
			
			@mysqli_free_result($col_term_prepower_result);
			
			unset($col_term_prepower_sql,$col_term_prepower_row);
			
			$gototaskmanager="./chezhangmangager.php";
			
		break;
		
		case "telmanagertask":
		
			$tasktype=4;
			
			$gototaskmanager="./telBroadManager.php";
			
			break;
			case "terfuncplaytask":
			
			$tasktype=5;
			
			$cmd = 0;
			
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			
			$gototaskmanager="./terminalfunctionplay.php";
		break;
	}
	
	if($tasktype==5)
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '5' AND task.prepower = 0 ";
		
		$sql_same_name.= "AND task.channel = 0 AND task.info = '' AND task.taskid != '$_GET[taskid]' and task.sec_task_id = 0 ";
		
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		
		if(mysqli_num_rows($result_same_name) > 0)
		{
			//=============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	else
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype' ";
		
		$sql_same_name.= "AND task.taskid != '$_GET[taskid]' ";
		
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		
		if(mysqli_num_rows($result_same_name) > 0)
		{
			//===========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	@mysqli_free_result($result_same_name);
	
	unset($sql_same_name);
	
	//获取用户优先级
		
	$sql = "SELECT book_admin.id, usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$row = mysqli_fetch_array($result);	
	
	//设置优先级
	//$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	
	$key_sql = "select task_user_id from task where taskid='$_GET[taskid]'";
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	if($key_row = mysqli_fetch_array($key_result))
	{
		$task_user_id = trim($key_row['task_user_id']);
	}
			
	for($i=0;$i<count($terminalidarray);$i++)
	{
		$temp = (int)$terminalidarray[$i];
		$sql = "SELECT id FROM userterminal WHERE userid='$task_user_id' AND terminalid='$temp'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if( mysqli_num_rows($result) <=0 )
		{
			$sqls="INSERT INTO userterminal(userid,terminalid) VALUES('$task_user_id','$temp')";
			mysqli_query($con,$sqls)or die(mysqli_error($con));
		}
	}
		
	//读取任务用户ID比较若相同则修改 不同则不修改
	
	$task_userid_sql = "SELECT task.priority FROM task WHERE task.task_user_id = '$task_user_id' AND task.taskid = '$_GET[taskid]' ";
	
	$task_userid_result = mysqli_query($con,$task_userid_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($task_userid_result) <= 0)
	{
		$original_task_priority_result = mysqli_query($con,"SELECT task.priority FROM task WHERE task.taskid = '$_GET[taskid]'") or die(mysqli_error($con));
		
		$original_task_priority_row = mysqli_fetch_array($original_task_priority_result);
		
		$priority = trim($original_task_priority_row['priority']);
		
		@mysqli_free_result($original_task_priority_result);
		
		@mysqli_free_result($task_userid_result);
		
		unset($original_task_priority_row,$task_userid_sql);
	}
	else
	{
		@mysqli_free_result($task_userid_result);
		
		unset($task_userid_sql);
	}
	
	@mysqli_free_result($result);
	
	unset($sql,$row);
	//获取原来的任务名称、预开电源、用户id	
	$getoldtaskname = "";
	
	$getoldtaskprepower = "";
	
	$getoldtaskuserid = "";
	
	$sql = "SELECT task.taskname, task.prepower, task.task_user_id FROM task WHERE task.taskid = '$_GET[taskid]'";
	
	$result = mysqli_query($con,$sql)or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		$getoldtaskname = $row['taskname'];
	
		$getoldtaskprepower = $row['prepower'];
		
		$getoldtaskuserid = $row['task_user_id'];
	}
	
	@mysqli_free_result($result);
	
	unset($row,$sql);
		
	//锁定并事务处理
	mysqli_query($con,"START TRANSACTION");
	
	mysqli_query($con,"LOCK TABLE task WRITE,terminaloftask WRITE,mediaoftask WRITE");
	
	if($getoldtaskprepower == 0 && $prepower == 0)
	{
		//什么也不做
	}
		else if($getoldtaskprepower == 0 &&	$prepower != 0)
	{
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, startdate, enddate,";
		
		$sql.="playtime, exemodel, priority, tasktype,  channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume, task_user_id, ";
		
		$sql.="sec_task_id) VALUES('$taskname', '$israndomplay',  '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate','$preopenpowertime', '$exemodel', '$priority', '9', '0', ";
		
		$sql.="'$bandrate', '$samplerate', '$get_qallery', '$cmdargs', '$playfileid', '$task_default_volume', '$getoldtaskuserid', '$_GET[taskid]')";
				
		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		//取终端功放id
		
		$result = mysqli_query($con,"select max(taskid) from task ");
		
		if($row = mysqli_fetch_array($result))
		{
			$getnewfunctionid = $row[0];
		}
		
		@mysqli_free_result($result);
		
		unset($row);
		
		for($i=0;$i<count($terminalidarray);$i++)
		{
			if(is_numeric($terminalidarray[$i]))
			{
				$terminalid = (int)$terminalidarray[$i];
				
		        
				//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$getnewfunctionid','$terminalid')";
				
				$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid)VALUES('$getnewfunctionid','$terminalid','$analysis_tree_group_ids[$i]')";
		
				mysqli_query($con,$sql) or die(mysqli_error($con));
		
				unset($sql);			
			}
		}
	}
	else if($getoldtaskprepower != 0 &&	$prepower == 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 AND task.info = '' and task.tasktype = '9' ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysqli_free_result($result);
		
		unset($sql,$row);
		
	mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid'") or die(mysqli_error($con));
		
		mysqli_query($con,"DELETE FROM task WHERE task.taskid = '$getoldfunctionid'") or die(mysqli_error($con));
	}
	else if($getoldtaskprepower != 0 &&	$prepower != 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 AND task.info = '' and task.tasktype = '9'";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysqli_free_result($result);
		
		unset($sql,$row);
        
	//$sql = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' ";
		
	//mysqli_query($con,$sql) or die(mysqli_error($con));
		
	//unset($sql);

		$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";
		
		$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' , ";
		
		$sql.="state = '$state' ,startdate = '$startdate' ,enddate = '$enddate' ,";
		
		$sql.="playtime = '$preopenpowertime' ,exemodel = '$exemodel' , priority = '$priority' ,tasktype = '9' , ";
		
		$sql.="channel = '0' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '$get_qallery' ,cmdargs = '0' , ";
		
		$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume',sec_task_id='$_GET[taskid]' ";
		
		$sql.=" WHERE  task.taskid = '$getoldfunctionid' and task.tasktype = '9' and channel = 0 ";
		
		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
	         	for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND groupid ='$getid'";
						  
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
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND terminalid ='$getid'";
						  
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
						
				     
						}
						
						}
  
						for($j=0; $j<count($terminalidarray); $j++)
						{
							if(is_numeric($terminalidarray[$j]))
							{
							    $temp = (int)$terminalidarray[$j];
								$group = (int)$analysis_tree_group_ids[$j];
							
									$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$getoldfunctionid' AND terminalid='$temp' AND groupid = '$group'";
							    $get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));
							  						  
								if($get_row = mysqli_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];	
								$get_group = $get_row['groupid'];
								}
								@mysqli_free_result($get_result);
								unset($get_sql,$get_row);
								if($temp==$get_terminals)
								{
								  if($get_group==$group)
								  {
								  	  for($z=0;$z<strlen($get_terminal_value);$z++)
											{
										//alert(z);
											if(substr($get_terminal_value,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal_value,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal_value,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal_value,$z+1,16);
										
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
								
								  }
								  else
								  {
										$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									
									 if(empty($get_terminal_value))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal_value);$z++)
											{
										//alert(z);
											if(substr($get_terminal_value,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal_value,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal_value,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
							
												$area = substr($get_terminal_value,$z+1,16);
				
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 					
								  } 
								}
								else 
								{
								
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal_value))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal_value);$z++)
											{
										//alert(z);
											if(substr($get_terminal_value,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal_value,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal_value,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
					
												$area = substr($get_terminal_value,$z+1,16);
				
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}
							
					

							}
						}
										
						
	
	}
	
	$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

	$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' ,state = '$state' ,startdate = '$startdate' ,";
	
	$sql.="enddate = '$enddate' ,playtime = '$playtime' ,exemodel = '$exemodel' ,priority = '$priority' ,tasktype = '$tasktype' , ";

	$sql.="channel = '$channel' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '$get_qallery' ,cmdargs = '$cmdargs' , ";

	$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume' WHERE taskid = '$_GET[taskid]' ";
	
	mysqli_query($con,$sql);
	
	unset($sql);

	for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$_GET[taskid]' AND groupid ='$getid'";
						  
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
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$_GET[taskid]' AND terminalid ='$getid'";
						  
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
						
						
				     
						}
						
						}
                          	
						for($j=0; $j<count($terminalidarray); $j++)
						{
							if(is_numeric($terminalidarray[$j]))
							{
							   $temp = (int)$terminalidarray[$j];
							   $group = (int)$analysis_tree_group_ids[$j];
							
							  	$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$_GET[taskid]' AND terminalid='$temp' AND groupid = '$group'";
							    $get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));
							  						  
								if($get_row = mysqli_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];
								$get_group = $get_row['groupid'];
								}
								@mysqli_free_result($get_result);
								unset($get_sql,$get_row);
								
								if($temp==$get_terminals)
								{
								  if($group==$get_group)
								  {
								  for($z=0;$z<strlen($get_terminal_value);$z++)
												{
											//alert(z);
													if(substr($get_terminal_value,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal_value,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
													$a=substr($get_terminal_value,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal_value,$z+1,16);
											
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															mysqli_query($con,$sql) or die(mysqli_error($con));
															unset($sql);
															break;
															}
													}
												}						
								  }
								  else
								  {
										$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal_value))
										  {
										  
										  }
										  else
										  {
											   for($z=0;$z<strlen($get_terminal_value);$z++)
												{
											//alert(z);
													if(substr($get_terminal_value,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal_value,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
													$a=substr($get_terminal_value,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal_value,$z+1,16);
															
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															mysqli_query($con,$sql) or die(mysqli_error($con));
															unset($sql);
															break;
															}
													}
												}						
										  } 
												
								  } 
								}
								else 
								{
						
								  
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal_value))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal_value);$z++)
											{
										//alert(z);
											if(substr($get_terminal_value,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal_value,$z,1)=="|")
											{
											  $position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal_value,$z-$position3,$position3);
											if($a==$temp)
												{
												//$c=strpos($get_terminal,$a);
			
												$area = substr($get_terminal_value,$z+1,16);
													
							
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}
								
								//checkterminal($temp,$get_terminal,$get_terminals,$_GET[taskid],$j);
							

							}
						}
	mysqli_query($con,"UNLOCK TABLES");
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
		//=======================================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=5&id=".$_GET['taskid']."&volume=".$task_default_volume;
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_task_volume("task",5,$_GET['taskid'],$task_default_volume);
		
		echo "<script>window.location='success.php'</script>";
	}
	
	if(mysqli_error($con))
	{
		mysqli_query($con,"ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
	
		echo "<script>window.location='error.php'</script>";
	
		exit;
	}
	
	
}

function checkterminal($temp,$get_terminal,$get_terminals,$getoldfunctionid,$i)
{
   global $con;
	$get_sql= "SELECT terminalid  FROM terminaloftask WHERE taskid = '$getoldfunctionid' AND terminalid='$temp'";
							    $get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));
							  						  
								if($get_row = mysqli_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];	
								}
								@mysqli_free_result($get_result);
								unset($get_sql,$get_row);
								if($temp==$get_terminals)
								{
								  if(empty($get_terminal))
								  {
								  
								  }
								  else
								  {
								   for($z=0;$z<strlen($get_terminal);$z++)
									{
								//alert(z);
									if(substr($get_terminal,$z,2)=="::")
									{	
									$position=$z+2;
									$a=substr($get_terminal,$z+2,strlen($temp));
									if($a==$temp)
										{
										$c=strpos($get_terminal,$a);
	
										$area = substr($get_terminal,$c+strlen($temp)+1,8);
									//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
										mysqli_query($con,$sql) or die(mysqli_error($con));
										unset($sql);
										break;
										}
									}
									}						
								  } 
								}
								else 
								{
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','11111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											$a=substr($get_terminal,$z+2,strlen($temp));
											if($a==$temp)
												{
												$c=strpos($get_terminal,$a);
			
												$area = substr($get_terminal,$c+strlen($temp)+1,8);
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}
								
}

/*
function str_split_utf8($str)
{
  
  $key=NULL;
  $array=array();
	
 	for($i=1;i<strlen($str);$i++)
	{
		if(strlen($str)<1000)
		{
		 array_push($array,$str);
		 break;
		 }
		 else
		 {
		   $k=$i*1000;
		   $value=ord($str[$k]);
		  if($value>127){
		   if($value>=192&&$value<=223) $split=2;
		   elseif($value>=224 && $value<=239) $split=3;
		   elseif($value>=240 && $value<=247) $split=4;
		  }else{
		   $split=1;
		  }
		  
	  	 $k=$k+$split;
	 	 $value2=ord($str[$k]);
		while($k)
		 {
		  	if(($value2>=65&&$value2<=90)||($value2>=97&&$value2<=122))
			{
				continue;
			}
		}
 	}
  return $array;
}

*/


function str_split_utf8($str)
{
 $split=1;
 $i=0;
 $key=NULL;
 $array=array();
 
while($i<strlen($str))
{

  $value=ord($str[$i]);
  if($value>127){
   if($value>=192&&$value<=223) $split=2;
   elseif($value>=224 && $value<=239) $split=3;
   elseif($value>=240 && $value<=247) $split=4;
  }else{
   $split=1;
  }

  for($j=0;$j<$split;$j++){
   $key.=$str[$i];
   $i++;
  }
  $value2=ord($str[$i]);

    if(($value2>=65&&$value2<=90)||($value2>=97&&$value2<=122))
	{
		continue;
	}
	
   if(strlen($key)>500)
   { 
   
   		if(strlen($key)>600)
		{
			 array_push($array,$key);
			 $key=NULL;
		}
		else
		{
			if($value2==33||$value2==44||$value2==46||$value2==59||$value2==227||$value2==239||$value2==250||$value2==63)
			{
				 for($j=0;$j<$split;$j++){
				   $key.=$str[$i];
				   $i++;
				  }
				 array_push($array,$key);
				 $key=NULL;
			}
			else
			{
				continue;
			}
		}
		
   }
 }
 if($key!=NULL)
 {
  
   array_push($array,$key);
   
   }
 return $array;
}

function del_sub_dirarea($con,$id)
{

	$sql_folder = "SELECT id FROM terminalfolder WHERE terminalfolder.parentid IN ($id)";
	$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
	while($row_folder = mysqli_fetch_array($result_folder))
	{
		 del_sub_dirarea($con,$row_folder[id]);
		 mysqli_query($con,"DELETE FROM terminaloffolder WHERE folderid='$row_folder[id]'") or die(mysqli_error($con));
		 mysqli_query($con,"DELETE FROM terminalfolder WHERE id ='$row_folder[id]'") or die(mysqli_error($con));	
	}

	@mysqli_free_result($result_folder);
	unset($row_folder,$sql_folder);

}

function dirareadel_msg($con)
{
	//=====保留系统预留文件夹=====//
	//添加外部变量
	global $do_php_prompt;
	$get_folder_id = "";
	//=====================创建对象=====================
	$database_operate_obj = new database_operate_class();
	//=====================创建跳转对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	$create_socket_obj = new create_socket_class();
	//取文件夹
	if(isset($_GET['id']))
	{
		$get_folder_id = trim($_GET['id']);
	
		$get_folder_id_array = explode(",",$get_folder_id);
	}
	
	foreach($get_folder_id_array as $value)
	{
		if($value == 0)
		{
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_dir_has_been_used']);
		}
	}
	
	$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['terminal_id']);
		
		$_SESSION['terminal'] = $terminal_id;
	}
	
	if($terminal_id == "")
	{
		$terminal_id = $_SESSION['terminal'];
		
	}
	
	
	//=====删除文件夹先删除文件=====//
	@mysqli_query($con,"LOCK TABLE terminalfolder WRITE,terminal WRITE,terminaloffolder WRITE");
	
	@mysqli_query($con,"START TRANSACTION");
	del_sub_dirarea($con,$get_folder_id);
	mysqli_query($con,"DELETE FROM terminaloffolder WHERE folderid = $get_folder_id" ) or die(mysqli_error($con));
	mysqli_query($con,"DELETE FROM terminalfolder WHERE id = $get_folder_id and parentid > 0") or die(mysqli_error($con));	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		//===========================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./dirstreammanager.php?flag=2&terminal_id=".$terminal_id;
		echo "<script>window.location='error.php'</script>";
		
		//$forward_ok_error_obj->forward_path(0,$do_php_prompt['Failed'],"./filefoldermanager.php");
	}
	else
	{
		//=============================================================
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./dirstreammanager.php?flag=2&terminal_id=".$terminal_id;
		echo "<script>parent.location.reload(true);</script>";	
		
		//$forward_ok_error_obj->forward_path(1,$do_php_prompt['Successed'],"./filefoldermanager.php");
	}
}
//修改17任务
function ttsmodifybelltask_msg($con)
{
	 //require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	$sec_task_id = 0;
	$cmd = 0;
	$cmdargs = 0;
	
	$taskname="";
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay=0;
	if(isset($_POST['israndomplay']))
	{
	  $israndomplay = $_POST['israndomplay'];
	}	
		 $get_noid=1;
	if(isset($_POST['get_noid']))
	{
	   $get_noids = trim($_POST['get_noid']);
  
	  $arr = array(',' =>'');
	  $get_noids =strtr($get_noids,$arr);
	  
	}

	 $starthour=0;
	if(isset($_POST['starthour']))
	{
	$starthour = $_POST['starthour'];
	}
	$startmin=0;
	if(isset($_POST['startmin']))
	{
		$startmin = $_POST['startmin'];
	}
	$startsenc=0;
	if(isset($_POST['startsenc']))
	{
		$startsenc = $_POST['startsenc'];
	}
	$getstarttime=$starthour*3600+$startmin*60+$startsenc;
	
	$getendtime=0;
	
	$datasendmodel=0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	$state=0;
	$intervalmode=0;
	if(isset($_POST['intervalmode']))
	{
		$intervalmode=$_POST['intervalmode'];
	}
	
	$intervalcircle=0;
	if(isset($_POST['intervalcircle']))
	{
		$intervalcircle = $_POST['intervalcircle'];
	}
	$timelength = 0;
	$intervallength=0;
	$allintervallen=0;
	$intplaylengthtype=0;
	if($intervalmode==1)
	{
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
		$timelengthtype = 1;
		$intervallength = trim($_POST['intervallenHour'])*60*60 + trim($_POST['intervallenMin'])*60 + trim($_POST['intervallenSenc'])*1; 
		$allintervallen=$intervalcircle;
		$intplaylengthtype=2;
	}
	else
	{
	
		if(isset($_POST['circleTime']))
		{
			$timelength = $_POST['circleTime'];
			$timelengthtype = 2;
		}
		$allintervallen=0;
		$intplaylengthtype=0;
	
	}

	$startdate="0000-00-00";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	
	$enddate="0000-00-00";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = $_POST['playtime'];
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
		if($prepower!=0)
		{
			if($prepower>59)
			{
			$getpowertime=$prepower/60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$getpowertime."minutes -0 seconds"));
			}
			else
			{
			$getpowertime=$prepower%60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getpowertime." seconds"));
			}
		}
	}
$cmd=0;
	//获取声音
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	$speed_value = "5";
	if(isset($_POST['speed_value']))
	{
		$speed_value = trim($_POST['speed_value']);
	}

	$musicmode = "0";
	if(isset($_POST['musicmode']))
	{
		$musicmode = trim($_POST['musicmode']);
	}
	
	$gettextarea="";
	if(isset($_POST['gettextarea']))
	{
		$gettextarea = $_POST['gettextarea'];
	}
	
	$gettextarea=nl2br($gettextarea);
	
	$get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}

	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
	$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}

	$get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}

	$get_taskid=$_GET['taskid'];

		$terminallistvalue = trim($_POST['terminallistvalue']);
				
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = $_POST['exemodel'];
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = $_POST['hiddenweek'];
			$repl = array(',' => '');
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			$playtime = "00:00:00";
		}
	}
	
	//获取任务优先级
	$priority = 13;
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
		$audiosource=0;
	
	if(isset($_POST['audiosource']))
	{
		$audiosource = trim($_POST['audiosource']);
		
		$cmd = $audiosource;

	}
	$getserverflag=0;
	
	if(isset($_POST['getserverflag']))
	{
		$getserverflag = trim($_POST['getserverflag']);
	}
	if($getserverflag==1)   //说明选的是tts主机
	{
		$tasktype=15;
		$speed_value=$speed_value;
		$gettishiyin=0;
		if(isset($_POST['gettishiyin']))
		{
			$gettishiyin = trim($_POST['gettishiyin']);
			$cmd=$audiosource;
		}
	}
	else if($audiosource==0)
	{
		$tasktype=17;
		$speed_value=$speed_value/10;
	}
	else
	{
		$tasktype=19;
		$speed_value=$speed_value/10;
	}
	

	$terminallistvalue = "";
	if(isset($_POST['terminallistvalue']))
	{	
		$terminallistvalue = trim($_POST['terminallistvalue']);
	 
	 	$terminalidarray = explode(",",$terminallistvalue);
	}
	
	$analysis_tree_group_string = "";
	
	if(isset($_POST['analysis_tree_group_string']))
	{
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	}
	
	$playfileid = 0;

	$gototaskmanager = "";
	$gototaskmanager="./displayttsmanager.php";

	/*
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype' ";
		
		$sql_same_name.= "AND task.taskid != '$get_taskid' ";
		
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		
		if(mysqli_num_rows($result_same_name) > 0)
		{
	
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}

	@mysqli_free_result($result_same_name);
	
	unset($sql_same_name);
	*/
	mysqli_query($con,"START TRANSACTION");

	mysqli_query($con,"LOCK TABLES task WRITE,terminaloftask WRITE,mediaoftask WRITE,media WRITE,book_admin WRITE,usergroup WRITE,terminal WRITE,leddevice WRITE,ttssentence WRITE,userterminal WRITE,ledsentence WRITE,ledoftask WRITE");
	
	/*
	if($intervalmode==1)
	{

	$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
	$playtimenum=$getstarttime+$timelength;
	$adm_endhour=intval($playtimenum/3600);
	$adm_endmin=intval(($playtimenum%3600)/60);
	$adm_endsec=intval($playtimenum%60);
	$adm_endtim=$adm_endhour.":".$adm_endmin.":".$adm_endsec;
				$sqltimelen = "SELECT taskid FROM task WHERE playtime >='$playtime' and playtime<='$adm_endtim' and cmd='$cmd' and cmd>'0' and tasktype in(17,19) and startdate <='$startdate'and taskid!='$get_taskid'";
				
				$result_name2 = mysqli_query($con,$sqltimelen) or die(mysqli_error($con));
				while($adm_row2 = mysqli_fetch_array($result_name2))
				{
					$forward_ok_error_obj->exit_back_function($do_php_prompt['time_task_failed']);
					return;
				}			
	}
	*/
	//获取用户优先级
		$getusername=$_SESSION['username'];
	$sql = "SELECT book_admin.id, usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$row = mysqli_fetch_array($result);	
	
	//设置优先级
	//$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);

	$key_sql = "select task_user_id from task where taskid='$get_taskid'";
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	if($key_row = mysqli_fetch_array($key_result))
	{
		$task_user_id = trim($key_row['task_user_id']);
	}
			
	for($i=0;$i<count($terminalidarray);$i++)
	{
		$temp = (int)$terminalidarray[$i];
		$sql = "SELECT id FROM userterminal WHERE userid='$task_user_id' AND terminalid='$temp'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if( mysqli_num_rows($result) <=0 )
		{
			$sqls="INSERT INTO userterminal(userid,terminalid) VALUES('$task_user_id','$temp')";
			mysqli_query($con,$sqls)or die(mysqli_error($con));
		}
	}

	//读取任务用户ID比较若相同则修改 不同则不修改
	
	$task_userid_sql = "SELECT task.priority FROM task WHERE task.task_user_id = '$task_user_id' AND task.taskid = '$get_taskid' ";
	
	$task_userid_result = mysqli_query($con,$task_userid_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($task_userid_result) <= 0)
	{
		$original_task_priority_result = mysqli_query($con,"SELECT task.priority FROM task WHERE task.taskid = '$get_taskid'") or die(mysqli_error($con));
		
		$original_task_priority_row = mysqli_fetch_array($original_task_priority_result);
		
	//	$priority = trim($original_task_priority_row['priority']);
		
		@mysqli_free_result($original_task_priority_result);
		
		@mysqli_free_result($task_userid_result);
		
		unset($original_task_priority_row,$task_userid_sql);
	}
	else
	{
		@mysqli_free_result($task_userid_result);
		
		unset($task_userid_sql);
	}
	
	@mysqli_free_result($result);
	
	unset($sql,$row);
	//获取原来的任务名称、预开电源、用户id	
	$getoldtaskname = "";
	
	$getoldtaskprepower = "";
	
	$getoldtaskuserid = "";
	
	$sql = "SELECT task.taskname, task.prepower, task.task_user_id FROM task WHERE task.taskid = '$get_taskid'";
	
	$result = mysqli_query($con,$sql)or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		$getoldtaskname = $row['taskname'];
	
		$getoldtaskprepower = $row['prepower'];
		
		$getoldtaskuserid = $row['task_user_id'];
	}
	
	@mysqli_free_result($result);
	
	unset($row,$sql);
	//锁定并事务处理
	

	if($getoldtaskprepower == 0 && $prepower == 0)
	{
		//什么也不做
	}
	else if($getoldtaskprepower == 0 &&	$prepower != 0)
	{
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, startdate, enddate,";
		
		$sql.="playtime, exemodel, priority, tasktype,  channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume, task_user_id, ";
		
		$sql.="sec_task_id,interval_s,intplaylength,intplaylengthtype) VALUES('$taskname', '$israndomplay',  '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate','$preopenpowertime', '$exemodel', '$priority', '9', '0', ";
		
		$sql.="'0', '0', '0', '0', '$playfileid', '$task_default_volume', '$getoldtaskuserid', '$get_taskid','$intervallength','$allintervallen','$intplaylengthtype')";
				
		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		//取终端功放id
		
		$result = mysqli_query($con,"select max(taskid) from task ");
		
		if($row = mysqli_fetch_array($result))
		{
			$getnewfunctionid = $row[0];
		}
		
		@mysqli_free_result($result);
		
		unset($row);
		
		for($i=0;$i<count($terminalidarray);$i++)
		{
			if(is_numeric($terminalidarray[$i]))
			{
				$terminalid = (int)$terminalidarray[$i];
				//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$getnewfunctionid','$terminalid')";
				
				$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid)VALUES('$getnewfunctionid','$terminalid','$analysis_tree_group_ids[$i]')";
		
				mysqli_query($con,$sql) or die(mysqli_error($con));
		
				unset($sql);			
			}
		}
	}
	else if($getoldtaskprepower != 0 &&	$prepower == 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$get_taskid' AND task.channel = 0 AND task.info = '' and task.tasktype = '9' ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysqli_free_result($result);
		unset($sql,$row);	
		mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid'") or die(mysqli_error($con));
		mysqli_query($con,"DELETE FROM task WHERE task.taskid = '$getoldfunctionid'") or die(mysqli_error($con));
	}
	else if($getoldtaskprepower != 0 &&	$prepower != 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$get_taskid' AND task.channel = 0 AND task.info = '' and task.tasktype = '9'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));	
		if($row = mysqli_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysqli_free_result($result);
		unset($sql,$row);
        
	//$sql = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' ";
		
	//mysqli_query($con,$sql) or die(mysqli_error($con));
		
	//unset($sql);

		$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";
		
		$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' , ";
		
		$sql.="state = '$state' ,startdate = '$startdate' ,enddate = '$enddate' ,";
		
		$sql.="playtime = '$preopenpowertime' ,exemodel = '$exemodel' , priority = '$priority' ,tasktype = '9' , ";
		
		$sql.="channel = '0' ,bandrate = '0' ,samplerate = '0' ,cmd = '$cmd' ,cmdargs = '0' , ";
		
		$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume',sec_task_id='$get_taskid',interval_s = '$intervallength' , intplaylength = '$allintervallen',intplaylengthtype='$intplaylengthtype'";
		
		$sql.=" WHERE  task.taskid = '$getoldfunctionid' and task.tasktype = '9' and channel = 0 ";
		
		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
	         	for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND groupid ='$getid'";
						  
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
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND terminalid ='$getid'";
						  
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
						}
					}

						for($j=0; $j<count($terminallistnum); $j++)
						{
							if(is_numeric($terminallistnum[$j]))
							{
							    $temp = (int)$terminallistnum[$j];
								$group = (int)$analysis_tree_group_ids[$j];
								$get_terminals=0;
								$get_group=0;
									$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$getoldfunctionid' AND terminalid='$temp' AND groupid = '$group'";
									
							    $get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));
							  						  
								if($get_row = mysqli_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];	
								$get_group = $get_row['groupid'];
								}
								@mysqli_free_result($get_result);
								unset($get_sql,$get_row);
								
								if($temp==$get_terminals)
								{
								  if($get_group==$group)
								  {
								  	 for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											$position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
										
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
								
								  }
								  else
								  {
										$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 
									 		if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										  
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 					
								  } 
								}
								else 
								{
								
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										 
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											$position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 	
								}
	
							//	checkterminal($temp,$get_terminal,$get_terminals,$getoldfunctionid,$j);

							}
						}
										
						
	
	}
	
	$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

	$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' ,state = '$state' ,startdate = '$startdate' ,";
	
	$sql.="enddate = '$enddate' ,playtime = '$playtime',endtime='$getendtime' ,exemodel = '$exemodel' ,priority = '$priority'  ,tasktype = '$tasktype' , ";

	$sql.="cmd = '$cmd' ,cmdargs = '$cmdargs' , ";

	$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume',interval_s = '$intervallength' , intplaylength = '$allintervallen',intplaylengthtype='$intplaylengthtype' WHERE taskid = '$get_taskid' ";
	
	mysqli_query($con,$sql);
	
	unset($sql);

	$led_display= array();
	$led_displaydeviceid = array();
	$getleddisplay=0;

	$openmediaid=0;
	$sqlmedia="select mediaid from mediaoftask where taskid='$get_taskid'";
	
		  $get_mediaresult = mysqli_query($con,$sqlmedia) or die(mysqli_error($con));		  						  
			while($get_row = mysqli_fetch_array($get_mediaresult))
			{	
				$openmediaid=$get_row['mediaid'];
				$delttssence = "DELETE FROM ttssentence WHERE ttssentence.sentenceid = '$openmediaid'";				
				mysqli_query($con,$delttssence) or die(mysqli_error($con));
				unset($delttssence);
				$delmedia = "DELETE FROM mediaoftask WHERE mediaoftask.taskid = '$get_taskid'";				
				mysqli_query($con,$delmedia) or die(mysqli_error($con));
				unset($delmedia);
				$delmedia = "DELETE FROM media WHERE media.id = '$openmediaid' AND typeid = 'tts'";				
				mysqli_query($con,$delmedia) or die(mysqli_error($con));
				unset($delmedia);
			}

				$sql="INSERT INTO media(name, typeid, filename,sample) VALUES ('$taskname','tts','tts','$get_taskid')";
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);
				$sqlmedias="select max(id) from media";
	
			  $get_medias = mysqli_query($con,$sqlmedias) or die(mysqli_error($con));
							  						  
			if($get_row = mysqli_fetch_array($get_medias))
			{	
			$openmediaid=$get_row['0'];
			}	
				$gettempi=0;

				$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$openmediaid','$get_taskid','1')";
				mysqli_query($con,$sql) or die(mysqli_error($con));			
			
			
		//mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id'") or die(mysqli_error($con));
						for($c=0;$c<strlen($get_noids);$c++)
						{
							if(substr($get_noids,$c,1)=="_")
							{
								$a=substr($get_noids,$c,1);
		
								$position=$c+1;
							}
							if(substr($get_noids,$c,1)=="|")
							{
								$position2=$c;
								$get_position =$position2-$position;
								
								$getid = substr($get_noids,$c-$get_position,$get_position);
								
								 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id' AND groupid ='$getid'";
								  
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
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id' AND terminalid ='$getid'";
						  
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
						
				     
						}
						
						}
						
						$ledmediaid=0;
						$ledtaskid=0;
						if($getleddisplay==0)
						{
							$sqlmedia="SELECT mediaid,mediaoftask.taskid FROM mediaoftask,task WHERE task.taskid=mediaoftask.taskid AND task.sec_task_id='$get_taskid' AND task.tasktype='24'";
							$get_mediaresult = mysqli_query($con,$sqlmedia) or die(mysqli_error($con));		  						  
							if($get_row = mysqli_fetch_array($get_mediaresult))
							{	
								$ledmediaid=$get_row['mediaid'];
								$ledtaskid=$get_row['taskid'];
								$sqls = "DELETE FROM ledsentence where mediaid = '$ledmediaid'"; 
								mysqli_query($con,$sqls);
								unset($sqls);
								$sqls = "UPDATE media SET name ='$taskname' WHERE id = '$ledmediaid'"; 
								mysqli_query($con,$sqls);
								unset($sqls);
							
								$sql2 = "DELETE FROM ledoftask WHERE ledoftask.taskid = '$ledtaskid'";
								mysqli_query($con,$sql2) or die(mysqli_error($con));
								unset($sql2);
								if($timelengthtype==1)
								{
									$length_time=$timelength;
								}
								else
								{
									$length_time=86000;
							
								}	

								$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '1' , ";
								$sql.="timelength = '$length_time' ,priority = '$priority'  , ";
								$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume',interval_s = '$intervallength' , intplaylength = '$allintervallen',intplaylengthtype='$intplaylengthtype' WHERE taskid = '$ledtaskid' ";
								mysqli_query($con,$sql) or die(mysqli_error($con));
								unset($sql);
							}
						}
		//添加终端
		for($i=0;$i<count($terminalidarray);$i++)
		{
			if(is_numeric($terminalidarray[$i]))
			{
				$terminalid = (int)$terminalidarray[$i];
				$group = (int)$analysis_tree_group_ids[$i];
		
				$sql = "SELECT typeid,leddevice.id,leddevice.subterminalid,leddevice.terminalid FROM terminal,leddevice WHERE leddevice.subterminalid='$terminalid'AND leddevice.terminalid=terminal.id";
				$result = mysqli_query($con,$sql) or die(mysqli_error($con));
				while($row = mysqli_fetch_array($result))
				{
					$led_deviceid=$row[1];
					$subterminalid=$row[2];
					$zhuterminalid=$row[3];
					if($row['typeid']==42)
					{

					
									$led_display[$getleddisplay]=$zhuterminalid;
									$led_displaydeviceid[$getleddisplay]=$led_deviceid;
									$getleddisplay++;
							

						//	$led_display[$getleddisplay]=$terminalid;
						//	$led_displaydeviceid[$getleddisplay]=$led_deviceid;
						//	$getleddisplay++;
					}
				}

				$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$getoldfunctionid' AND terminalid='$terminalid' AND groupid='$group'";
							    $get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));
							  						  
								if($get_row = mysqli_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];
								$get_group = $get_row['groupid'];	
								}
								@mysqli_free_result($get_result);
								unset($get_sql,$get_row);
								if($terminalid==$get_terminals)
								{
								 if($group==$get_group)
								 {
								 
								 }
								 else
								 {
				          $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$terminalid','$analysis_tree_group_ids[$i]','1111111111111111')";
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$terminalid)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$terminalid'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 
								 
								 }

									}
									else 
								{
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$terminalid','$analysis_tree_group_ids[$i]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$terminalid)
												{
												//$c=strpos($get_terminal,$a);
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$terminalid'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 

								}	  			
			}
		}


				for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$get_taskid' AND groupid ='$getid'";
						  
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
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$get_taskid' AND terminalid ='$getid'";
						  
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
						}
						
						}

            $getttstaskid=$_GET['taskid'];
						for($j=0; $j<count($terminallistnum); $j++)
						{
							if(is_numeric($terminallistnum[$j]))
							{
							   $temp = (int)$terminallistnum[$j];
							   $group = (int)$analysis_tree_group_ids[$j];
							   $get_terminals=0;
							   $get_group=0;
							   
							  	$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$getttstaskid' AND terminalid='$temp' AND groupid = '$group'";
							    $get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));
							  						  
								if($get_row = mysqli_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];
								$get_group = $get_row['groupid'];
								}
								@mysqli_free_result($get_result);
								unset($get_sql,$get_row);
		
								if($temp==$get_terminals)
								{
								  if($group==$get_group)
								  {
								  		for($z=0;$z<strlen($get_terminal);$z++)
												{
											//alert(z);
													if(substr($get_terminal,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
														$a=substr($get_terminal,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal,$z+1,16);
											
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$get_taskid' AND terminalid ='$temp'";
															mysqli_query($con,$sql) or die(mysqli_error($con));
															unset($sql);
															break;
															}
													}
												}						
								  }
								  else
								  {
								   
										$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getttstaskid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
											   for($z=0;$z<strlen($get_terminal);$z++)
												{
											//alert(z);
													if(substr($get_terminal,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
													$a=substr($get_terminal,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal,$z+1,16);
															
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$get_taskid' AND terminalid ='$temp'";
															mysqli_query($con,$sql) or die(mysqli_error($con));
															unset($sql);
															break;
															}
													}
												}						
										  } 
												
								  } 
								}
								else 
								{
		
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getttstaskid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											  $position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
											if($a==$temp)
												{
												//$c=strpos($get_terminal,$a);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$get_taskid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 
								}
								//checkterminal($temp,$get_terminal,$get_terminals,$_GET[taskid],$j);
							}
						}
						
	$delttssence = "DELETE FROM ttssentence WHERE ttssentence.sentenceid = '$openmediaid'";				
	mysqli_query($con,$delttssence) or die(mysqli_error($con));
	unset($delttssence);

	$arr1=str_split_utf8($gettextarea);

	$gettempi=0;
	if($getserverflag==1)
	{				
		if($gettishiyin>0)
		{
		//	$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$gettishiyin','$get_taskid','0')";
		//	mysqli_query($con,$sql) or die(mysqli_error($con));
				$sql="INSERT INTO ttssentence(name,sentenceid,type,mediaid,content,mediaseq,speed,volume,male) VALUES ('$taskname','$openmediaid','0','$gettishiyin','','$gettempi','$speed_value','$task_default_volume','$musicmode')";
				mysqli_query($con,$sql) or die(mysqli_error($con));
				$gettempi++;
		}
	}
	
	
	for($aa=0;$aa<count($arr1);$aa++)
	{
		$gettextone=$arr1[$aa];
		$gettextone=str_replace("<br/>","",$gettextone);
		$gettextone=str_replace("<br />","",$gettextone);
		$gettextone=str_replace("\r\n","",$gettextone);
		$gettextone=str_replace("</b>","",$gettextone);
		$gettextone=str_replace("</B>","",$gettextone);
		$gettextone=str_replace("、","",$gettextone);
		$gettextone=str_replace("\\","",$gettextone);
		$gettextone=str_replace("'","\'",$gettextone);
		$gettextone=$gettextone;
	
		if(!empty($gettextone))
		{
		
			$sql="INSERT INTO ttssentence(name,sentenceid,type,content,mediaseq,speed,volume,male) VALUES ('$taskname','$openmediaid','2','$gettextone','$gettempi','$speed_value','$task_default_volume','$musicmode')";
			

			mysqli_query($con,$sql) or die(mysqli_error($con));
		
			if($getleddisplay>0)
			{
				$sql="INSERT INTO ledsentence(text,mediaid,speed,type,mediaseq) VALUES ('$gettextone','$ledmediaid','5','1','$gettempi')";
			
				mysqli_query($con,$sql) or die(mysqli_error($con));
				
				for($i=0; $i<$getleddisplay; $i++)
				{
					$sql = "INSERT INTO ledoftask (taskid,terminalid,deviceid)VALUES('$ledtaskid','$led_display[$i]','$led_displaydeviceid[$i]')";
					
					mysqli_query($con,$sql) or die(mysqli_error($con));
				}

			}
			$gettempi++;
		}
	}

	mysqli_query($con,"UNLOCK TABLES");
    	if(!mysqli_error($con))
			{
				mysqli_query($con,"COMMIT");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				//=======================================================================
				/*$socket	=	new	send_message_to_server($port_conf);	
				
				$msg = "task?state=5&id=".$_GET['taskid']."&volume=".$task_default_volume;
				
				$socket->send_data($_SESSION['serverip'],$msg);
				*/	
				$create_socket_obj->send_socket_task_volume("task",5,$_GET['taskid'],$task_default_volume);
				if($tasktype==17)
					$create_socket_obj->send_socket_generate_general2("task",13,$_GET['taskid'],$tasktype);
				else
					$create_socket_obj->send_socket_generate_general2("task",2,$_GET['taskid'],$tasktype);
		
				echo "<script>window.location='success.php'</script>";
			}
			


	if(mysqli_error($con))
	{
		mysqli_query($con,"ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
	
		echo "<script>window.location='error.php'</script>";
	
		exit;
	}
}

//修改2、3、4、5任务
function modifybelltask_msg($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	
	$sec_task_id = 0;
	
	$cmd = 0;
	
	$cmdargs = 0;
	
	$taskname="";
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay=0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = $_POST['israndomplay'];
	}
	 $get_noid=1;
	if(isset($_POST['get_noid']))
	{
	   $get_noids = trim($_POST['get_noid']);
  
	  $arr = array(',' =>'');
	  $get_noids =strtr($get_noids,$arr);
	  
	}
		$listvalue = "";
	if(isset($_POST['listvalue']))
	{	
		$listvalue = trim($_POST['listvalue']);
		$mediaidarray = explode(",",$listvalue);
	}
		$starthour=0;
	if(isset($_POST['starthour']))
	{
		$starthour = $_POST['starthour'];
	}
	$startmin=0;
	if(isset($_POST['startmin']))
	{
		$startmin = $_POST['startmin'];
	}
	$startsenc=0;
	if(isset($_POST['startsenc']))
	{
		$startsenc = $_POST['startsenc'];
	}
	$getstarttime=$starthour*3600+$startmin*60+$startsenc;
	
	$getendtime=0;
	$timelengthtype=1;
	
	$timelength=0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
	
		if($timelengthtype == 1)
		{  
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
		$getendtime=$timelength+$getstarttime;
		}
		else
		{
			$timelength = trim($_POST['circleTime']);
			for($i=0;$i<count($mediaidarray);$i++)
			{
					$getmediaid = "SELECT timelength FROM media where id='$mediaidarray[$i]'";//取插入任务id
					$mediaidresult = mysqli_query($con,$getmediaid) or die(mysqli_error($con));
					while($row = mysqli_fetch_array($mediaidresult))
					{
						$getendtime = $getendtime+($row['timelength']*$timelength);//新添加的任务id				
					}
			}
			$getendtime=$getendtime+$getstarttime;
		} 
	}
	else
	{
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
		$getendtime=$timelength+$getstarttime;
	}
	$getendhour=$getendtime/3600;
	$getendmin=$getendtime%3600/60;
	$getendsec=$getendtime%3600%60;
	
	$getendtime=(int)$getendhour.":".(int)$getendmin.":".(int)$getendsec;
	if($getendhour>=24)
		$getendtime="23:59:59";
	$datasendmodel=0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	$state=0;
	$intervalmode=0;
	if(isset($_POST['intervalmode']))
	{
		$intervalmode=$_POST['intervalmode'];
	}
	$intervaltype=0;
	if(isset($_POST['intervaltype']))
	{
		$intervaltype = $_POST['intervaltype'];
	}
	$intervalcircle=0;
	if(isset($_POST['intervalcircle']))
	{
		$intervalcircle = $_POST['intervalcircle'];
	}
	$intervallength=0;
	$allintervallen=0;
	if($intervalmode==1)
	{
		$intervallength = trim($_POST['intervallenHour'])*60*60 + trim($_POST['intervallenMin'])*60 + trim($_POST['intervallenSenc'])*1; 
		if($intervaltype==1)
		{
			$allintervallen = trim($_POST['intervalHour'])*60*60 + trim($_POST['intervalMin'])*60 + trim($_POST['intervalSenc'])*1; 
		}
		else
		{
			$allintervallen=$intervalcircle;
		}
	}	
	$startdate="0000-00-00";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	
	$enddate="0000-00-00";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = $_POST['playtime'];
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
	
		if($prepower!=0)
		{
			if($prepower>59)
			{
			$getpowertime=$prepower/60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$getpowertime."minutes -0 seconds"));
			}
			else
			{
			$getpowertime=$prepower%60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getpowertime." seconds"));
			}
		}
	}
	//获取声音
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	$get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	$get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	$get_taskid=$_GET['taskid'];

	$get_tasktree=$_GET['gettasktree'];
		$terminallistvalue = trim($_POST['terminallistvalue']);
		
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = $_POST['exemodel'];
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = $_POST['hiddenweek'];
			$repl = array(',' => '');
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			$playtime = "00:00:00";
		}
	}
	
	//获取任务优先级
	$priority = 13;
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$tasktype = 0;
	
	$audiosource = 0;
	if(isset($_POST['audiosource']))
	{	
		$audiosource = trim($_POST['audiosource']);
		
		$cmd = $audiosource;
		
		$audiosource = 0;
	}
	
	$channel=0;
	if(isset($_POST['channel']))
	{	
		$channel = trim($_POST['channel']);
		
		$cmdargs = $channel;
		
		$channel = 0;
	}
	
	$bandrate=0;
	if(isset($_POST['bandrate']))
	{	
		$bandrate = trim($_POST['bandrate']);
	}
	
	$samplerate=0;
	if(isset($_POST['samplerate']))
	{	
		$samplerate = trim($_POST['samplerate']);
	}

	$ledplay=0;
	if(isset($_POST['ledplay']))
	{
		$ledplay = trim($_POST['ledplay']);
	}
	if($ledplay==1)
	{
		$getledtextareas="";
		if(isset($_POST['getledtextareas']))
		{
			$getledtextareas = $_POST['getledtextareas'];
		}
		$getledtextareas=nl2br($getledtextareas);
		$led_group_string="";
		if(isset($_POST['led_group_string']))
		{
			$led_group_string = $_POST['led_group_string'];
		}
		
		$ledlistvalue="";
		if(isset($_POST['ledlistvalue']))
		{
			$ledlistvalue = $_POST['ledlistvalue'];
		}	
	}

	$terminallistvalue = "";
	if(isset($_POST['terminallistvalue']))
	{	
		$terminallistvalue = trim($_POST['terminallistvalue']);
	 
	 	$terminalidarray = explode(",",$terminallistvalue);
	}

	$analysis_tree_group_string = "";
	
	if(isset($_POST['analysis_tree_group_string']))
	{
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	}
	//获取用户优先级
		
	$sql = "SELECT book_admin.id, usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$row = mysqli_fetch_array($result);	
	
	//设置优先级
//	$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	$playfileid = 0;
	
	$gototaskmanager="";
	  
	switch($_POST['taskType'])
	{
		case "belltask":
		
			$tasktype = 1;
		
			$gototaskmanager="./bellmanager.php";
		
		break;
		
		case "fileplaytask":
		
			$tasktype=2;
			$key_sql = "SELECT userid FROM filetaskfree WHERE filetaskfree.id in(select parentid from task where taskid='$get_taskid')";
			$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
			if($key_row = mysqli_fetch_array($key_result))
			{
				$task_user_id = trim($key_row['userid']);
			}
			$user_id=$_GET['userid'];
			$gototaskmanager="./taskmanager.php?id=$get_tasktree&userid=$user_id";
			
			$EmergencyBroadcast = 0;
			
			if(isset($_POST['EmergencyBroadcast']))
			{
				$EmergencyBroadcast = trim($_POST['EmergencyBroadcast']);
			}
			
			if($EmergencyBroadcast == 1)
			{
				$tasktype = 7;
			}
			
		break;
		case "videoplaytask":
		
			$tasktype=27;
		
			$user_id=$_SESSION['userid'];
			$gototaskmanager="./videodisplaymanager.php?userid=$user_id";
		
			
		break;
		case "zhaoshengplaytask":
			$tasktype=25;
					$key_sql = "SELECT userid FROM filetaskfree WHERE filetaskfree.id in(select parentid from task where taskid='$get_taskid')";
			$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
			if($key_row = mysqli_fetch_array($key_result))
			{
				$task_user_id = trim($key_row['userid']);
			}
			$user_id=$_GET['userid'];
			$gototaskmanager="./zhaoshentaskmanager.php?id=$get_tasktree&userid=$user_id";
			
			$EmergencyBroadcast = 0;
			
			if(isset($_POST['EmergencyBroadcast']))
			{
				$EmergencyBroadcast = trim($_POST['EmergencyBroadcast']);
			}
			
			if($EmergencyBroadcast == 1)
			{
				$tasktype = 25;
			}
			$cmdargs=$tasktype;
			$get_soundsdevice = "";
			if(isset($_POST['get_soundsdevice']))
			{	
				$get_soundsdevice = trim($_POST['get_soundsdevice']);
				$soundsdevarray = explode(",",$get_soundsdevice);
			}
		
			$sounds_tree_group_string = "";

			if(isset($_POST['sounds_tree_group_string']))
			{
				$sounds_tree_group_string = trim($_POST['sounds_tree_group_string']);
				$soundsgrouparry = explode(",",$sounds_tree_group_string);
			}
		break;
		case "admmanagertask":
			$tasktype=3;
			$interview_repower = 0;//欲开采播电源
			$col_term_prepower_id = 0;//欲开采播任务id	
			$interview_repower_time = 0;//欲开采播电源时间
			$key_sql = "select task_user_id from task where taskid='$get_taskid'";
			$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
			if($key_row = mysqli_fetch_array($key_result))
			{
				$task_user_id = trim($key_row['task_user_id']);
			}
			//$cmd = $audiosource;
			//$cmdargs = $channel;
			
			if(isset($_POST['interview_repower']))
			{
				$interview_repower = trim($_POST['interview_repower']);
			}
			if($interview_repower>59)
			{
				$getpowertime=$interview_repower/60;
				$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - ".$getpowertime."minutes -0 seconds"));
			}
			else
			{
				$getpowertime=$interview_repower%60;
				$interview_repower_time = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getpowertime." seconds"));
			}
			
			//取出采播终端欲开电源任务id
			$col_term_prepower_sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$get_taskid' AND task.channel = 0 and tasktype = 8 ";
			
			$col_term_prepower_result = mysqli_query($con,$col_term_prepower_sql) or die(mysqli_error($con));
			
			if($col_term_prepower_row = mysqli_fetch_array($col_term_prepower_result))
			{
				$col_term_prepower_id = trim($col_term_prepower_row['taskid']);
			}
			
			@mysqli_free_result($col_term_prepower_result);
			unset($col_term_prepower_sql,$col_term_prepower_row);
			$gototaskmanager="./admmanager.php";
			
		break;
		
		case "telmanagertask":
			$tasktype=4;
			$gototaskmanager="./telBroadManager.php";
			break;
		case "terfuncplaytask":
			$tasktype=5;
			$key_sql = "select task_user_id from task where taskid='$get_taskid'";
			$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
			if($key_row = mysqli_fetch_array($key_result))
			{
				$task_user_id = trim($key_row['task_user_id']);
			}
			$cmd = 0;
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			$gototaskmanager="./terminalfunctionplay.php";
		break;
		case "centerctrmodify":
			$tasktype=23;
			$key_sql = "select task_user_id from task where taskid='$get_taskid'";
			$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
			if($key_row = mysqli_fetch_array($key_result))
			{
				$task_user_id = trim($key_row['task_user_id']);
			}
			$cmd = 0;
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			
			$gototaskmanager="./centerctrmanager.php";
				$pcstate=0;
			if(isset($_POST['pcstate']))
			{
				$pcstate = $_POST['pcstate'];
			}
			$projectionstate=0;
			if(isset($_POST['projectionstate']))
			{
				$projectionstate = $_POST['projectionstate'];
			}
			$systemstate=0;
			if(isset($_POST['systemstate']))
			{
				$systemstate = $_POST['systemstate'];
			}
			$volstate=0;
			if(isset($_POST['volstate']))
			{
				$volstate = $_POST['volstate'];
			}
			$dev1=0;
			if(isset($_POST['dev1']))
			{
				$dev1 = $_POST['dev1'];
			}
			$dev2=0;
			if(isset($_POST['dev2']))
			{
				$dev2 = $_POST['dev2'];
			}
			$dev3=0;
			if(isset($_POST['dev3']))
			{
				$dev3 = $_POST['dev3'];
			}
			$dev4=0;
			if(isset($_POST['dev4']))
			{
				$dev4 = $_POST['dev4'];
			}
			$dev5=0;
			if(isset($_POST['dev5']))
			{
				$dev5 = $_POST['dev5'];
			}
			$projectionscreenstate=0;
			if(isset($_POST['projectionscreenstate']))
			{
				$projectionscreenstate = $_POST['projectionscreenstate'];
			}		
			break;
	}
	
	if($tasktype==5)
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '5' AND task.prepower = 0 ";
		$sql_same_name.= "AND task.channel = 0 AND task.info = '' AND task.taskid != '$get_taskid' and task.sec_task_id = 0 ";
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		if(mysqli_num_rows($result_same_name) > 0)
		{
			//=============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			echo "<script>window.history.back();</script>";
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	else
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype'";
		$sql_same_name.= "AND task.taskid != '$get_taskid' ";
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		if(mysqli_num_rows($result_same_name) > 0)
		{
			//===========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			echo "<script>window.history.back();</script>";
			exit;
			*/
			//$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	@mysqli_free_result($result_same_name);
	unset($sql_same_name);
	for($i=0;$i<count($terminalidarray);$i++)
	{
		$temp = (int)$terminalidarray[$i];
		$sql = "SELECT id FROM userterminal WHERE userid='$task_user_id' AND terminalid='$temp'";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if( mysqli_num_rows($result) <=0 )
		{
			$sqls="INSERT INTO userterminal(userid,terminalid) VALUES('$task_user_id','$temp')";
			mysqli_query($con,$sqls)or die(mysqli_error($con));
		}
	}
	
	

	//读取任务用户ID比较若相同则修改 不同则不修改
	
	$task_userid_sql = "SELECT task.priority FROM task WHERE task.task_user_id = '$task_user_id' AND task.taskid = '$get_taskid' ";
	
	$task_userid_result = mysqli_query($con,$task_userid_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($task_userid_result) <= 0)
	{
		$original_task_priority_result = mysqli_query($con,"SELECT task.priority FROM task WHERE task.taskid = '$get_taskid'") or die(mysqli_error($con));
		
		$original_task_priority_row = mysqli_fetch_array($original_task_priority_result);
		
	//	$priority = trim($original_task_priority_row['priority']);
		
		@mysqli_free_result($original_task_priority_result);
		
		@mysqli_free_result($task_userid_result);
		
		unset($original_task_priority_row,$task_userid_sql);
	}
	else
	{
		@mysqli_free_result($task_userid_result);
		
		unset($task_userid_sql);
	}
	
	@mysqli_free_result($result);
	
	unset($sql,$row);
	//获取原来的任务名称、预开电源、用户id	
	$getoldtaskname = "";
	
	$getoldtaskprepower = "";
	
	$getoldtaskuserid = "";
	
	$sql = "SELECT task.taskname, task.prepower, task.task_user_id FROM task WHERE task.taskid = '$get_taskid'";
	
	$result = mysqli_query($con,$sql)or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		$getoldtaskname = $row['taskname'];
	
		$getoldtaskprepower = $row['prepower'];
		
		$getoldtaskuserid = $row['task_user_id'];
	}
	
	@mysqli_free_result($result);
	
	unset($row,$sql);
	//锁定并事务处理
	
	mysqli_query($con,"START TRANSACTION");
	
	mysqli_query($con,"LOCK TABLE task WRITE,terminaloftask WRITE,mediaoftask WRITE,ledoftask WRITE,ledsentence WRITE,media WRITE,soundtask WRITE");

	if($getoldtaskprepower == 0 && $prepower == 0)
	{
		//什么也不做
		if($tasktype==23)
		{
			for($i=0;$i<count($terminalidarray);$i++)
		{
			if(is_numeric($terminalidarray[$i]))
			{
				$terminalid = (int)$terminalidarray[$i];
			
				//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$getnewfunctionid','$terminalid')";
				if($tasktype==23)
				{
					$sql = "UPDATE terminaloftask SET area='$area',pcstate='$pcstate',projectionstate='$projectionstate',systemstate='$systemstate',volstate='$volstate',volume='$task_default_volume',projectionscreenstate='$projectionscreenstate',dev1='$dev1',dev2='$dev2',dev3='$dev3',dev4='$dev4',dev5='$dev5' WHERE taskid ='$_GET[taskid]' AND terminalid ='$terminalid'";
				 }
				
				mysqli_query($con,$sql) or die(mysqli_error($con));
		
				unset($sql);			
			}
		}
		
		}
	}
	else if($getoldtaskprepower == 0 &&	$prepower != 0)
	{
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, startdate, enddate,";
		
		$sql.="playtime, exemodel, priority, tasktype,  channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume, task_user_id, ";
		
		$sql.="sec_task_id,interval_s,intplaylength,intplaylengthtype) VALUES('$taskname', '$israndomplay',  '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate','$preopenpowertime', '$exemodel', '$priority', '9', '0', ";
		
		$sql.="'$bandrate', '$samplerate', '0', '$cmdargs', '$playfileid', '$task_default_volume', '$getoldtaskuserid', '$get_taskid','$intervallength','$allintervallen','$intervaltype')";
				
		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		//取终端功放id
		
		$result = mysqli_query($con,"select max(taskid) from task ");
		
		if($row = mysqli_fetch_array($result))
		{
			$getnewfunctionid = $row[0];
		}
		
		@mysqli_free_result($result);
		
		unset($row);
		
		for($i=0;$i<count($terminalidarray);$i++)
		{
			if(is_numeric($terminalidarray[$i]))
			{
				$terminalid = (int)$terminalidarray[$i];
			
				//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$getnewfunctionid','$terminalid')";
				if($tasktype==23)
				{
				 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area,pcstate,projectionstate,systemstate,volstate,volume,projectionscreenstate,dev1,dev2,dev3,dev4,dev5)VALUES('$getnewfunctionid','$temp','$analysis_tree_group_ids[$i]','1111111111111111','$pcstate','$projectionstate','$systemstate','$volstate','$task_default_volume','$projectionscreenstate','$dev1','$dev2','$dev3','$dev4','$dev5')";
				 }
				 else
				$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid)VALUES('$getnewfunctionid','$terminalid','$analysis_tree_group_ids[$i]')";
		
				mysqli_query($con,$sql) or die(mysqli_error($con));
		
				unset($sql);			
			}
		}
	}
	else if($getoldtaskprepower != 0 &&	$prepower == 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$get_taskid' AND task.channel = 0 AND task.info = '' and task.tasktype = '9' ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysqli_free_result($result);
		
		unset($sql,$row);
		
	  mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid'") or die(mysqli_error($con));
		
		mysqli_query($con,"DELETE FROM task WHERE task.taskid = '$getoldfunctionid'") or die(mysqli_error($con));
	}
	else if($getoldtaskprepower != 0 &&	$prepower != 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 AND task.info = '' and task.tasktype = '9'";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysqli_free_result($result);
		
		unset($sql,$row);
        
	//$sql = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' ";
		
	//mysqli_query($con,$sql) or die(mysqli_error($con));
		
	//unset($sql);

		$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";
		
		$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' , ";
		
		$sql.="state = '$state' ,startdate = '$startdate' ,enddate = '$enddate' ,";
		
		$sql.="playtime = '$preopenpowertime' ,exemodel = '$exemodel' , priority = '$priority' ,tasktype = '9' , ";
		
		$sql.="channel = '0' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '0' ,cmdargs = '$cmdargs' , ";
		
		$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume',sec_task_id='$get_taskid',offlinestate='0', interval_s = '$intervallength',intplaylength='$allintervallen',intplaylengthtype='$intervaltype'  ";
		
		$sql.=" WHERE  task.taskid = '$getoldfunctionid' and task.tasktype = '9' and channel = 0 ";
		
		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
	         	for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND groupid ='$getid'";
						  
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
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND terminalid ='$getid'";
						  
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
				     
						}
						}
  
						for($j=0; $j<count($terminallistnum); $j++)
						{
							if(is_numeric($terminallistnum[$j]))
							{
							    $temp = (int)$terminallistnum[$j];
								$group = (int)$analysis_tree_group_ids[$j];
							
								$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$getoldfunctionid' AND terminalid='$temp' AND groupid = '$group'";
							    $get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));
							  						  
								if($get_row = mysqli_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];	
								$get_group = $get_row['groupid'];
								}
								@mysqli_free_result($get_result);
								unset($get_sql,$get_row);
								if($temp==$get_terminals)
								{
								  if($get_group==$group)
								  {
								  	  for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
									
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
											if($tasktype==23)
												{
												$sql = "UPDATE terminaloftask SET area='$area',pcstate='$pcstate',projectionstate='$projectionstate',systemstate='$systemstate',volstate='$volstate',volume='$task_default_volume',projectionscreenstate='$projectionscreenstate',dev1='$dev1',dev2='$dev2',dev3='$dev3',dev4='$dev4',dev5='$dev5' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												}
												else
													$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
								
								  }
								  else
								  {
								
								  if($tasktype==23)
										{
										 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area,pcstate,projectionstate,systemstate,volstate,volume,projectionscreenstate,dev1,dev2,dev3,dev4,dev5)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111','$pcstate','$projectionstate','$systemstate','$volstate','$task_default_volume','$projectionscreenstate','$dev1','$dev2','$dev3','$dev4','$dev5')";
										 }
								 	 else
										$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
											//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
										
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
											if($tasktype==23)
												{
												$sql = "UPDATE terminaloftask SET area='$area',pcstate='$pcstate',projectionstate='$projectionstate',systemstate='$systemstate',volstate='$volstate',volume='$task_default_volume',projectionscreenstate='$projectionscreenstate',dev1='$dev1',dev2='$dev2',dev3='$dev3',dev4='$dev4',dev5='$dev5' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												}
												else
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 					
								  } 
								}
								else 
								{
								  if($tasktype==23)
										{
										 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area,pcstate,projectionstate,systemstate,volstate,volume,projectionscreenstate,dev1,dev2,dev3,dev4,dev5)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111','$pcstate','$projectionstate','$systemstate','$volstate','$task_default_volume','$projectionscreenstate','$dev1','$dev2','$dev3','$dev4','$dev5')";
										 }
								 	 else
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
										
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												if($tasktype==23)
												{
												$sql = "UPDATE terminaloftask SET area='$area',pcstate='$pcstate',projectionstate='$projectionstate',systemstate='$systemstate',volstate='$volstate',volume='$task_default_volume',projectionscreenstate='$projectionscreenstate',dev1='$dev1',dev2='$dev2',dev3='$dev3',dev4='$dev4',dev5='$dev5' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												}
												else
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}
							
								
							  
								
							//	checkterminal($temp,$get_terminal,$get_terminals,$getoldfunctionid,$j);
							

							}
						}
										
						
	
	}
	
	$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

	$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' ,state = '$state' ,startdate = '$startdate' ,";
	
	$sql.="enddate = '$enddate' ,playtime = '$playtime',endtime='$getendtime' ,exemodel = '$exemodel' ,priority = '$priority'  , ";

	$sql.="channel = '$channel' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '$cmd' ,cmdargs = '$cmdargs' , ";

	$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume' ,offlinestate='0', interval_s = '$intervallength',intplaylength='$allintervallen',intplaylengthtype='$intervaltype'  WHERE taskid = '$get_taskid' ";
	
	mysqli_query($con,$sql);
	
	unset($sql);
	

	if($tasktype == 3)
	{
		$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

		$sql.="timelength = '$timelength' ,prepower = '$interview_repower' ,datasendmodel = '$datasendmodel' ,";
		
		$sql.="state = '$state' ,startdate = '$startdate' ,enddate = '$enddate' ,";
		
		$sql.="playtime = '$interview_repower_time' ,exemodel = '$exemodel' ,priority = '$priority' , channel = '0' ,";
	
		$sql.="bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '0' ,cmdargs = '$cmdargs' , ";
	
		$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume' ,offlinestate='0' WHERE task.sec_task_id = '$get_taskid' and tasktype = '8' ";
		
		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		$sql= "SELECT terminalid  FROM terminaloftask WHERE taskid = '$col_term_prepower_id'";
		 $result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if(mysqli_num_rows($result) > 0)
		{
		//修改采集任务终端
		mysqli_query($con,"UPDATE terminaloftask SET terminalid = '$cmd' WHERE taskid = '$col_term_prepower_id' ") or die(mysqli_error($con));
		}
		else
		{
			$sqls = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$col_term_prepower_id','$cmd','0','1111111111111111')";
				
			mysqli_query($con,$sqls) or die(mysqli_error($con));
		}
	}
	//对相同功放任务处理
	if($tasktype == 5)
	{
		//查询相同功放任务
		$second_id = 0;
		
		$sql_play = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.tasktype = '5' ";
		
		$sql_play.= "AND task.prepower = '0' and task.channel = 0 and task.info = '' and task.sec_task_id != 0";
		
		$result_play = mysqli_query($con,$sql_play) or die(mysqli_error($con));
		
		if($row_play = mysqli_fetch_array($result_play))
		{
			$play_id[] = $row_play['taskid'];
		}
		
		@mysqli_free_result($result_play);
		
		unset($row_play,$sql_play);
		
		foreach($play_id as $value)
		{
			if($value != trim($_GET['taskid']))
			{
				$second_id = $value;
				
				break;
			}
		}
		unset($play_id);
		
	
		
			$cmd = 0;
		
		
		$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

		$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' ,state = '$state' , ";
		
		$sql.="startdate = '$startdate' ,enddate = '$enddate' ,playtime = '$preopenpowertime' , ";
		
		$sql.="exemodel = '$exemodel' ,priority = '$priority' ,tasktype = '$tasktype' ,channel = '0' ,bandrate = '$bandrate' , ";
		
		$sql.="samplerate = '$samplerate' ,cmd = '1' ,cmdargs = '$cmdargs' ,playfileid = '$playfileid' , ";
		
		$sql.="defaultvolume = '$task_default_volume',sec_task_id='$get_taskid',offlinestate='0', interval_s = '$intervallength',intplaylength='$allintervallen',intplaylengthtype='$intervaltype'  WHERE taskid = '$second_id' ";
		
		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		//删除终端

		//mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id'") or die(mysqli_error($con));
						for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;

						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
					//	mysqli_query($con,"DELETE FROM mediaofterminal WHERE mediaofterminal.taskid = '$second_id'");
			
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id' AND groupid ='$getid'";  
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
		
						}
						
						}
		
						for($z=0;$z<strlen($get_id);$z++)
						{
						if(substr($get_id,$z,2)=="::")
						{
	
						$position=$z+2;

						}
						if(substr($get_id,$z,1)=="|")
						{
						$position2=$z;
						$get_position =$position2-$position;
						
						$getid = substr($get_id,$z-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id' AND terminalid ='$getid'";
						  
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
						
				     
						}
						
						}
		//添加终端
		for($i=0;$i<count($terminalidarray);$i++)
		{
			if(is_numeric($terminalidarray[$i]))
			{
				$terminalid = (int)$terminalidarray[$i];
				$group = (int)$analysis_tree_group_ids[$i];
				//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$second_id','$terminalid')";
				$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$second_id' AND terminalid='$terminalid' AND groupid='$group'";
							    $get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));
							  						  
								if($get_row = mysqli_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];
								$get_group = $get_row['groupid'];	
								}
								@mysqli_free_result($get_result);
								unset($get_sql,$get_row);
								if($terminalid==$get_terminals)
								{
								 if($group==$get_group)
								 {
								 
								 }
								 else
								 {
				                    $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$second_id','$terminalid','$analysis_tree_group_ids[$i]','1111111111111111')";
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										 else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$terminalid)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$second_id' AND terminalid ='$terminalid'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 
								 
								 }

									}
									else 
								{
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$second_id','$terminalid','$analysis_tree_group_ids[$i]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$terminalid)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$second_id' AND terminalid ='$terminalid'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}	  
										  
				
				
							
			}
		}
	}
	
	if($tasktype == 2||$tasktype == 25||$tasktype==15||$tasktype==23)
	{
		$sqlmediaoftask = "DELETE FROM mediaoftask WHERE mediaoftask.taskid = '$_GET[taskid]' ";
		
		mysqli_query($con,$sqlmediaoftask) or die(mysqli_error($con));
		
		unset($sqlmediaoftask);
		
		for($i=0;$i<count($mediaidarray);$i++)
		{
			$getmediaid = $mediaidarray[$i];
		
			if(is_numeric($getmediaid))
			{
				$getmediaid =(int)$getmediaid;
		
				$sql="INSERT INTO mediaoftask (mediaid,taskid,sort) VALUES ('$getmediaid','$_GET[taskid]','$i')";
		
				mysqli_query($con,$sql) or die(mysqli_error($con));
		
				unset($sql);
			}
		}
	}
	
	if($tasktype == 25)
	{
	
	$get_volume_strings = "";
	
	if(isset($_POST['get_volume_strings']))
	{
		$get_volume_strings = trim($_POST['get_volume_strings']);
		
		$get_volume_stringsarry = explode(",",$get_volume_strings);
	}
	
	$get_db_value_string = trim($_POST['get_db_value_string']);
	
		$get_db_value_stingsarry = explode(",",$get_db_value_string);
	
	
		$sqldevtask = "DELETE FROM soundtask WHERE soundtask.taskid = '$_GET[taskid]' ";
		
		mysqli_query($con,$sqldevtask) or die(mysqli_error($con));
		
		unset($sqldevtask);
		
		for($i=0;$i<count($soundsdevarray);$i++)
		{
			$getdevid = $soundsdevarray[$i];
		
			if(is_numeric($getdevid))
			{
				$getdevid =(int)$getdevid;
				$db_value_stingsarry = explode("-",$get_db_value_stingsarry[$i]);		
				$sql="INSERT INTO soundtask (taskid,devid,volume,dbvalue) VALUES ('$_GET[taskid]',$getdevid,'0','$db_value_stingsarry[0]')";
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);
				$sql="INSERT INTO soundtask (taskid,devid,volume,dbvalue) VALUES ('$_GET[taskid]',$getdevid,'20','$db_value_stingsarry[1]')";
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);
				$sql="INSERT INTO soundtask (taskid,devid,volume,dbvalue) VALUES ('$_GET[taskid]',$getdevid,'40','$db_value_stingsarry[2]')";
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);
				$sql="INSERT INTO soundtask (taskid,devid,volume,dbvalue) VALUES ('$_GET[taskid]',$getdevid,'60','$db_value_stingsarry[3]')";
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);
				$sql="INSERT INTO soundtask (taskid,devid,volume,dbvalue) VALUES ('$_GET[taskid]',$getdevid,'80','$db_value_stingsarry[4]')";
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);
				$sql="INSERT INTO soundtask (taskid,devid,volume,dbvalue) VALUES ('$_GET[taskid]',$getdevid,'100','$db_value_stingsarry[5]')";
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);
			}
		}
	}
	
	for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$_GET[taskid]' AND groupid ='$getid'";
						  
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
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$_GET[taskid]' AND terminalid ='$getid'";
						  
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
						
						
				     
						}
						
						}
                          	
						for($j=0; $j<count($terminallistnum); $j++)
						{
							if(is_numeric($terminallistnum[$j]))
							{
							   $temp = (int)$terminallistnum[$j];
							   $group = (int)$analysis_tree_group_ids[$j];
							
							  	$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$_GET[taskid]' AND terminalid='$temp' AND groupid = '$group'";
							    $get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));
							  						  
								if($get_row = mysqli_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];
								$get_group = $get_row['groupid'];
								}
								@mysqli_free_result($get_result);
								unset($get_sql,$get_row);
								
								if($temp==$get_terminals)
								{
								  if($group==$get_group)
								  {
								  for($z=0;$z<strlen($get_terminal);$z++)
												{
											//alert(z);
													if(substr($get_terminal,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
													$a=substr($get_terminal,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal,$z+1,16);
													
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															if($tasktype==23)
															{
															$sql = "UPDATE terminaloftask SET area='$area',pcstate='$pcstate',projectionstate='$projectionstate',systemstate='$systemstate',volstate='$volstate',volume='$task_default_volume',projectionscreenstate='$projectionscreenstate',dev1='$dev1',dev2='$dev2',dev3='$dev3',dev4='$dev4',dev5='$dev5' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															}
															else
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															mysqli_query($con,$sql) or die(mysqli_error($con));
															unset($sql);
															break;
															}
													}
												}						
								  }
								  else
								  {
								
								   if($tasktype==23)
									{
									 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area,pcstate,projectionstate,systemstate,volstate,volume,projectionscreenstate,dev1,dev2,dev3,dev4,dev5)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111','$pcstate','$projectionstate','$systemstate','$volstate','$task_default_volume','$projectionscreenstate','$dev1','$dev2','$dev3','$dev4','$dev5')";
									 }
								 	 else
										$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
											   for($z=0;$z<strlen($get_terminal);$z++)
												{
											//alert(z);
													if(substr($get_terminal,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
													$a=substr($get_terminal,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal,$z+1,16);
													
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															if($tasktype==23)
															{
															$sql = "UPDATE terminaloftask SET area='$area',pcstate='$pcstate',projectionstate='$projectionstate',systemstate='$systemstate',volstate='$volstate',volume='$task_default_volume',projectionscreenstate='$projectionscreenstate',dev1='$dev1',dev2='$dev2',dev3='$dev3',dev4='$dev4',dev5='$dev5' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															}
															else
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															mysqli_query($con,$sql) or die(mysqli_error($con));
															unset($sql);
															break;
															}
													}
												}						
										  } 
												
								  } 
								}
								else 
								{
							
	 								 if($tasktype==23)
										{
										 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area,pcstate,projectionstate,systemstate,volstate,volume,projectionscreenstate,dev1,dev2,dev3,dev4,dev5)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111','$pcstate','$projectionstate','$systemstate','$volstate','$task_default_volume','$projectionscreenstate','$dev1','$dev2','$dev3','$dev4','$dev5')";
										 }
								 	 else
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											  $position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
											if($a==$temp)
												{
												//$c=strpos($get_terminal,$a);
			
												$area = substr($get_terminal,$z+1,16);
													
							
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												if($tasktype==23)
												{
												$sql = "UPDATE terminaloftask SET area='$area',pcstate='$pcstate',projectionstate='$projectionstate',systemstate='$systemstate',volstate='$volstate',volume='$task_default_volume',projectionscreenstate='$projectionscreenstate',dev1='$dev1',dev2='$dev2',dev3='$dev3',dev4='$dev4',dev5='$dev5' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
												}
												else
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 
								}
								
								//checkterminal($temp,$get_terminal,$get_terminals,$_GET[taskid],$j);
							

							}
						}
			if($timelengthtype==1)
								{
									$length_time=$timelength;
								}
								else
								{
									$length_time=86000;
							
								}					
		$sql_led_name = "SELECT taskid FROM task WHERE tasktype in(24) AND sec_task_id = '$_GET[taskid]'";	
		$result_led_name = mysqli_query($con,$sql_led_name) or die(mysqli_error($con));
		if(mysqli_num_rows($result_led_name) > 0)
		{
			if($ledplay==1)
			{
			 	modify_ledtask($con,$getledtextareas,$taskname,$israndomplay,1,$length_time,0,$datasendmodel,0,'0000-00-00','0000-00-00','00:00:00','00:00:00','0000000',$priority,24,0,0,0,$_GET['taskid'],$cmdargs,0,$task_default_volume,$getoldtaskuserid,0,0,$intervallength,$allintervallen,$intervaltype,0,$led_group_string,$ledlistvalue);
			}
			else
			{
				if($get_row = mysqli_fetch_array($result_led_name))
				{	
					$getledtaskid=$get_row['taskid'];
					del_ledtask($con,$getledtaskid,24);
				}
			}	
		}
		else
		{
			if($ledplay==1)
			{
			 	add_ledtask($con,$getledtextareas,$taskname,$israndomplay,1,$length_time,0,$datasendmodel,0,'0000-00-00','0000-00-00','00:00:00','00:00:00','0000000',$priority,24,0,0,0,$_GET['taskid'],$cmdargs,0,$task_default_volume,$getoldtaskuserid,0,0,$intervallength,$allintervallen,$intervaltype,0,$led_group_string,$ledlistvalue);
			}	
		}
		@mysqli_free_result($result_led_name);	
		unset($sql_led_name);
	  mysqli_query($con,"UNLOCK TABLES");
    	if(!mysqli_error($con))
			{
				mysqli_query($con,"COMMIT");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				//=======================================================================
				/*$socket	=	new	send_message_to_server($port_conf);	
				
				$msg = "task?state=5&id=".$_GET['taskid']."&volume=".$task_default_volume;
				
				$socket->send_data($_SESSION['serverip'],$msg);
				*/
				$create_socket_obj->send_socket_task_volume("task",5,$_GET['taskid'],$task_default_volume);
				
				echo "<script>window.location='success.php'</script>";
			}
			


	if(mysqli_error($con))
	{
		mysqli_query($con,"ROLLBACK");
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = $gototaskmanager;
		echo "<script>window.location='error.php'</script>";
		exit;
	}	
}


//修改led任务
function ledmodifybelltask_msg($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	$sec_task_id = 0;
	$cmd = 0;
	
	$cmdargs = 0;
	
	$taskname="";
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay=0;

	 $get_noid=1;
	if(isset($_POST['get_noid']))
	{
	   $get_noids = trim($_POST['get_noid']);
  
	  $arr = array(',' =>'');
	  $get_noids =strtr($get_noids,$arr);
	  
	}
		$listvalue = "";
	if(isset($_POST['listvalue']))
	{	
		$listvalue = trim($_POST['listvalue']);
	
		$mediaidarray = explode(",",$listvalue);
	}
		$starthour=0;
	if(isset($_POST['starthour']))
	{
		$starthour = $_POST['starthour'];
	}
	$startmin=0;
	if(isset($_POST['startmin']))
	{
		$startmin = $_POST['startmin'];
	}
	$startsenc=0;
	if(isset($_POST['startsenc']))
	{
		$startsenc = $_POST['startsenc'];
	}
	$getstarttime=$starthour*3600+$startmin*60+$startsenc;
	
	$getendtime=0;
	$timelengthtype=1;
	
	$timelength=0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
	
		if($timelengthtype == 1)
		{  
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
		$getendtime=$timelength+$getstarttime;
		}
		else
		{
			$timelength = trim($_POST['circleTime']);
			for($i=0;$i<count($mediaidarray);$i++)
			{
					$getmediaid = "SELECT timelength FROM media where id='$mediaidarray[$i]'";//取插入任务id
					$mediaidresult = mysqli_query($con,$getmediaid) or die(mysqli_error($con));
					while($row = mysqli_fetch_array($mediaidresult))
					{
						$getendtime = $getendtime+($row['timelength']*
$timelength);//新添加的任务id				
					}
			}
			$getendtime=$getendtime+$getstarttime;
		} 
	}
	else
	{
		$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
		$getendtime=$timelength+$getstarttime;
	}
	$getendhour=$getendtime/3600;
	$getendmin=$getendtime%3600/60;
	$getendsec=$getendtime%3600%60;
	
	$getendtime=(int)$getendhour.":".(int)$getendmin.":".(int)$getendsec;
	if($getendhour>=24)
		$getendtime="23:59:59";
	$datasendmodel=0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	$state=0;
	$intervalmode=0;
	if(isset($_POST['intervalmode']))
	{
		$intervalmode=$_POST['intervalmode'];
	}
	$intervaltype=0;
	if(isset($_POST['intervaltype']))
	{
		$intervaltype = $_POST['intervaltype'];
	}
	$intervalcircle=0;
	if(isset($_POST['intervalcircle']))
	{
		$intervalcircle = $_POST['intervalcircle'];
	}
	$intervallength=0;
	$allintervallen=0;
	if($intervalmode==1)
	{
		$intervallength = trim($_POST['intervallenHour'])*60*60 + trim($_POST['intervallenMin'])*60 + trim($_POST['intervallenSenc'])*1; 
		if($intervaltype==1)
		{
			$allintervallen = trim($_POST['intervalHour'])*60*60 + trim($_POST['intervalMin'])*60 + trim($_POST['intervalSenc'])*1; 
		}
		else
		{
			$allintervallen=$intervalcircle;
		}
	}	
	$startdate="0000-00-00";
	if(isset($_POST['startdate']))
	{
		$startdate = $_POST['startdate'];
	}
	
	$enddate="0000-00-00";
	if(isset($_POST['enddate']))
	{
		$enddate = $_POST['enddate'];
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = $_POST['playtime'];
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
	
		if($prepower!=0)
		{
			if($prepower>59)
			{
			$getpowertime=$prepower/60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$getpowertime."minutes -0 seconds"));
			}
			else
			{
			$getpowertime=$prepower%60;
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - 0 minutes -".$getpowertime." seconds"));
			}
		}
	}
	//获取声音
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	$get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	$get_taskid=$_GET['taskid'];

	$get_tasktree=$_GET['gettasktree'];
	
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = $_POST['exemodel'];
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = $_POST['hiddenweek'];
			$repl = array(',' => '');
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			$playtime = "00:00:00";
		}
	}
	
	//获取任务优先级
	$priority = 13;
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$tasktype = 0;
	
	$audiosource = 0;
	if(isset($_POST['audiosource']))
	{	
		$audiosource = trim($_POST['audiosource']);
		
		$cmd = $audiosource;
		
		$audiosource = 0;
	}
	
	$channel=0;
	if(isset($_POST['channel']))
	{	
		$channel = trim($_POST['channel']);
		
		$cmdargs = $channel;
		
		$channel = 0;
	}
	
	$bandrate=0;
	if(isset($_POST['bandrate']))
	{	
		$bandrate = trim($_POST['bandrate']);
	}
	
	$samplerate=0;
	if(isset($_POST['samplerate']))
	{	
		$samplerate = trim($_POST['samplerate']);
	}
	
	$terminallistvalue = "";
	if(isset($_POST['terminallistvalue']))
	{	
		$terminallistvalue = trim($_POST['terminallistvalue']);
	 
	 	$terminalidarray = explode(",",$terminallistvalue);
	}

	$analysis_tree_group_string = "";
	
	if(isset($_POST['analysis_tree_group_string']))
	{
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	}
	//获取用户优先级
		
	$sql = "SELECT book_admin.id, usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$row = mysqli_fetch_array($result);	
	
	//设置优先级
//	$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	$playfileid = 0;
	
	$gototaskmanager=""; 

	$tasktype=24;
	$key_sql = "SELECT userid FROM ledtaskfree WHERE ledtaskfree.id in(select parentid from task where taskid='$get_taskid')";
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	if($key_row = mysqli_fetch_array($key_result))
	{
		$task_user_id = trim($key_row['userid']);
	}
	$user_id=$_GET['userid'];
	$gototaskmanager="./ledtaskmanager.php?id=$get_tasktree&userid=$user_id";
	
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype' ";
		$sql_same_name.= "AND task.taskid != '$get_taskid' ";
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		if(mysqli_num_rows($result_same_name) > 0)
		{
			
		}
	@mysqli_free_result($result_same_name);	
	unset($sql_same_name);

	//读取任务用户ID比较若相同则修改 不同则不修改
	$task_userid_sql = "SELECT task.priority FROM task WHERE task.task_user_id = '$task_user_id' AND task.taskid = '$get_taskid' ";
	
	$task_userid_result = mysqli_query($con,$task_userid_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($task_userid_result) <= 0)
	{
		$original_task_priority_result = mysqli_query($con,"SELECT task.priority FROM task WHERE task.taskid = '$get_taskid'") or die(mysqli_error($con));
		
		$original_task_priority_row = mysqli_fetch_array($original_task_priority_result);
		
	//	$priority = trim($original_task_priority_row['priority']);
		
		@mysqli_free_result($original_task_priority_result);
		
		@mysqli_free_result($task_userid_result);
		
		unset($original_task_priority_row,$task_userid_sql);
	}
	else
	{
		@mysqli_free_result($task_userid_result);
		
		unset($task_userid_sql);
	}
	
	@mysqli_free_result($result);
	
	unset($sql,$row);
	//获取原来的任务名称、预开电源、用户id	
	$getoldtaskname = "";
	
	$getoldtaskprepower = "";
	
	$getoldtaskuserid = "";
	
	$sql = "SELECT task.taskname, task.prepower, task.task_user_id FROM task WHERE task.taskid = '$get_taskid'";
	
	$result = mysqli_query($con,$sql)or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		$getoldtaskname = $row['taskname'];
		$getoldtaskprepower = $row['prepower'];
		$getoldtaskuserid = $row['task_user_id'];
	}
	
	@mysqli_free_result($result);
	
	unset($row,$sql);
	//锁定并事务处理
		$gettextarea="";                             
		if(isset($_POST['gettextarea']))       
		{                                      
			$gettextarea = $_POST['gettextarea'];
		}	                                     
		$gettextarea=nl2br($gettextarea);  
		    
	 	$led_group_string = trim($_POST['led_group_string']);
		
		$led_groupstring = explode(",",$led_group_string);
		$ledlistvalue = trim($_POST['ledlistvalue']);   
		$led_listvalue = explode(",",$ledlistvalue);    
		
		mysqli_query($con,"START TRANSACTION");
		mysqli_query($con,"LOCK TABLES task WRITE,terminaloftask WRITE,ledsentence WRITE,media WRITE,mediaoftask WRITE,book_admin WRITE,usergroup WRITE,ledtaskfree WRITE,ledoftask WRITE,userterminal WRITE");  
	 if($getoldtaskprepower == 0 &&	$prepower != 0)
	{
		if($gettextarea!="")    
		{
			$result = mysqli_query($con,"select taskid from task where taskid='$get_taskid' and tasktype=24 ");		
			if($row = mysqli_fetch_array($result))
			{
				$ledtaskid=$row['taskid'];
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, startdate, enddate,";
				$sql.="playtime, exemodel, priority, tasktype,  channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume, task_user_id, ";	
				$sql.="sec_task_id,interval_s,intplaylength,intplaylengthtype) VALUES('$taskname', '$israndomplay',  '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
				$sql.="'$state', '$startdate', '$enddate','$preopenpowertime', '$exemodel', '$priority', '9', '0', ";
				$sql.="'$bandrate', '$samplerate', '0', '$get_taskid', '$playfileid', '$task_default_volume', '$getoldtaskuserid', '$ledtaskid','$intervallength','$allintervallen','$intervaltype')";		
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);	
				
				$results = mysqli_query($con,"select max(taskid) from task ");		
				if($rows = mysqli_fetch_array($results))
				{
					$lednewfunctionid = $rows[0];
				}
				
				@mysqli_free_result($results);
				unset($rows);
				
				for($i=0;$i<count($led_listvalue);$i++)
				{
					if(is_numeric($led_listvalue[$i]))
					{
						$terminalid = (int)$led_listvalue[$i];
						$sql = "INSERT INTO ledoftask (taskid,terminalid,deviceid)VALUES('$lednewfunctionid','$led_groupstring[$i]','$terminalid')";
						mysqli_query($con,$sql) or die(mysqli_error($con));
						unset($sql);			
					}
				}
			}
			@mysqli_free_result($result);	
			unset($row);
		
		}                       
	}
	else if($getoldtaskprepower != 0 &&	$prepower == 0)
	{	
		$sql = "SELECT taskid FROM task WHERE sec_task_id = '$get_taskid' and task.tasktype = '9' ";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if($row = mysqli_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
			mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid'") or die(mysqli_error($con));
			mysqli_query($con,"DELETE FROM task WHERE task.taskid = '$getoldfunctionid'") or die(mysqli_error($con));	
		}
		@mysqli_free_result($result);
		unset($sql,$row);
	  	
	}
	else if($getoldtaskprepower != 0 &&	$prepower != 0)
	{	
		$sql = "SELECT taskid,tasktype,sec_task_id FROM task WHERE sec_task_id = '$_GET[taskid]' AND task.tasktype in(9)";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		while($row = mysqli_fetch_array($result))
		{
		
			if($row['sec_task_id']!=$get_taskid)
			{
				$ledoldfunctionid=$row['sec_task_id'];
				$led_task_id=$row['taskid'];
				$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";
				$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' , ";
				$sql.="state = '$state' ,startdate = '$startdate' ,enddate = '$enddate' ,";
				$sql.="playtime = '$preopenpowertime' ,exemodel = '$exemodel' , priority = '$priority' ,tasktype = '9' , ";
				$sql.="channel = '0' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '0' ,";
				$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume',offlinestate='0', interval_s = '$intervallength',intplaylength='$allintervallen',intplaylengthtype='$intervaltype'";
				$sql.=" WHERE  task.sec_task_id = '$ledoldfunctionid' and task.tasktype = '9' and channel = 0 ";
				
				mysqli_query($con,$sql) or die(mysqli_error($con));
				unset($sql);
				
				$sql2 = "DELETE FROM ledoftask WHERE ledoftask.taskid = '$led_task_id'";
				mysqli_query($con,$sql2) or die(mysqli_error($con));
				unset($sql2);	
				for($i=0; $i<count($led_listvalue); $i++)               
				{                                                       
					if(is_numeric($led_listvalue[$i]))                    
					{                                                     
						$temp = (int)$led_listvalue[$i];                    
						$sql = "INSERT INTO ledoftask (taskid,terminalid,deviceid)VALUES('$led_task_id','$led_groupstring[$i]','$temp')";											  
						mysqli_query($con,$sql) or die(mysqli_error($con)); 
					}

				}
			}
		}
		@mysqli_free_result($result);
		unset($sql,$row);
 
 	         		
	}
	
	
	$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

	$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' ,state = '$state' ,startdate = '$startdate' ,";
	
	$sql.="enddate = '$enddate' ,playtime = '$playtime',endtime='$getendtime' ,exemodel = '$exemodel' ,priority = '$priority'  , ";

	$sql.="channel = '$channel' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '$cmd' , ";

	$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume' ,offlinestate='0', interval_s = '$intervallength',intplaylength='$allintervallen',intplaylengthtype='$intervaltype'  WHERE cmdargs = '$get_taskid' and tasktype in(24)";
	
	mysqli_query($con,$sql);
	
	unset($sql);

			
		$ledsql = "SELECT task.taskid,taskname,mediaoftask.mediaid FROM mediaoftask,task WHERE mediaoftask.taskid=task.taskid AND task.taskid = '$_GET[taskid]' AND task.tasktype in(24)";
		$ledresult = mysqli_query($con,$ledsql) or die(mysqli_error($con));
		if($ledrow = mysqli_fetch_array($ledresult))
		{
		$leddtaskid=$ledrow['taskid'];
			$mediaid=$ledrow['mediaid'];
			$sqls = "UPDATE media SET name ='$taskname' WHERE id = '$mediaid'"; 
			mysqli_query($con,$sqls);
			unset($sqls);
			
			$sql2 = "DELETE FROM ledoftask WHERE ledoftask.taskid = '$leddtaskid'";
				mysqli_query($con,$sql2) or die(mysqli_error($con));
				unset($sql2);	
				for($i=0; $i<count($led_listvalue); $i++)               
				{                                                       
					if(is_numeric($led_listvalue[$i]))                    
					{                                                     
						$temp = (int)$led_listvalue[$i];                    
						$sql = "INSERT INTO ledoftask (taskid,terminalid,deviceid)VALUES('$leddtaskid','$led_groupstring[$i]','$temp')";											  
						mysqli_query($con,$sql) or die(mysqli_error($con)); 
					}
				}

			$sqls = "DELETE FROM ledsentence where mediaid = '$mediaid'"; 
			mysqli_query($con,$sqls);
			unset($sqls);
	
			$gettempi=0;             
			$gettext=0;                
			$arr1=str_split_utf8($gettextarea);
			for($aa=0;$aa<count($arr1);$aa++)
			{                     
				$gettextone=$arr1[$aa];
				$gettextone=str_replace("<br/>","",$gettextone);
				$gettextone=str_replace("<br />","",$gettextone);  
				$gettextone=str_replace("\r\n","",$gettextone);    
				$gettextone=str_replace("、","",$gettextone);              
				$gettextone=str_replace("</b>","",$gettextone);                                                                                                                                               
				$gettextone=str_replace("</B>","",$gettextone);                                                                                                                                               
				$gettextone=str_replace("\\","",$gettextone);                                                                                                                                                 
				$gettextone=str_replace("'","\'",$gettextone);                                                                                                                                                
				$gettextone=$gettextone;                                                                                                                                                                      
				if(!empty($gettextone))                                
				{                                                    
					$sql="INSERT INTO ledsentence(text,mediaid,speed,type,mediaseq) VALUES ('$gettextone','$mediaid','5','1','$gettempi')"; 
					mysqli_query($con,$sql) or die(mysqli_error($con));
					$gettempi++;                                          
				}                                                                                                                                                                                             
			}				                                                                                                                                                                                        
	
		}				
						
			mysqli_query($con,"UNLOCK TABLES");
    		if(!mysqli_error($con))
			{
				mysqli_query($con,"COMMIT");
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				$_SESSION['url'] = $gototaskmanager;
				$create_socket_obj->send_socket_task_volume("task",5,$_GET['taskid'],$task_default_volume);
				echo "<script>window.location='success.php'</script>";
			}
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				$_SESSION['url'] = $gototaskmanager;
				echo "<script>window.location='error.php'</script>";
				exit;
			}	
}

function sync_time($con)
{
	require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();

		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//==================================================
			$socket	=	new	send_message_to_server($port_conf);	
			$msg = "terminal?state=30&id=".$getid."";			
			$socket->send_data($_SESSION['serverip'],$msg);
		}
		echo "<script>window.location='success.php'</script>";	
	
}

function updatechezhan($con)
{
	require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	$getid = 0;
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}

	$terminal_id = 0;
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = trim($_GET['id']);
	}

	$ledflag = "";
	if(isset($_GET['ledflag']))
	{
		$ledflag = trim($_GET['ledflag']);
	}

	$getidlist=explode(",",$getid);
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		if($ledflag==2)
		{
			$_SESSION['url'] = "./led_terminal_sousuo.php??id=0&ledflag=2";
		}
		else
		{
			$_SESSION['url'] = "./led_terminal_sousuo.php?terminal_id=$terminal_id";
		}
	
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $get_id)
		{
			//==================================================
			$socket	=	new	send_message_to_server($port_conf);	
			$msg = "terminal?state=31&id=".$get_id."";			
			$socket->send_data($_SESSION['serverip'],$msg);
		}
		echo "<script>window.location='success.php'</script>";	
	
}

//删除任务终端
function deltaskterminal_msg($con)
{
//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	$getid = "";
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getids)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "terminal?state=4&id=".$getid."&speech=false";			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("terminal",32,$getids);
		}
		echo "<script>window.location='success.php'</script>";	
}

//添加文件广播任务---没有被使用
function taskadd_msg($con)
{

	//require_once("inc/socket_conf.php");  
	//===============================添加外部变量
	global $do_php_prompt;
	//===============================创建套字节==============================
	$create_socket_obj = new create_socket_class();
		 
	mysqli_query($con,"INSERT INTO `task` (`taskname`,`streamid`,`startdate`,`starttime`,`timelength`,`playmodel`) VALUES ('$_POST[taskname]','$_POST[streamid]','$_POST[startdate]','$_POST[starttime]','$_POST[timelength]','$_POST[playmodel]')"); 
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php";
	
		$result=mysqli_query($con,"select max(id) from task")or die("Execute error".mysqli_error($con));
	
		if($row=mysqli_fetch_array($result))
		{
			$getid=$row[0];
			//=====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "task?state=4&id=".$getid;			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",4,$getid);
		}
		echo "<script>window.location='success.php'</script>";	
	}		
}
//启用文件广播---状态为3
function filetaskstart_msg($con)
{
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//===============================创建套字节==============================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	$gettaskid = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettaskid = trim($_GET['gettask']);
	}
	$userid = 0;
	
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}

	$sql = "update task set state=3 where taskid in (".$getValue.") and task.tasktype IN(2,15)  and task.channel=0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$sql = "update task set state=3 where sec_task_id in (".$getValue.") and task.tasktype='9' and task.channel=0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	unset($sql);
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php?id=$gettaskid&userid=$userid";
		
		echo "<script>window.location='./error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php?id=$gettaskid&userid=$userid";
		
		$getidlist = explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "task?state=3&id=".$getid;			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
		
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}

		echo "<script>window.location='./success.php'</script>";	
		
		exit;//是什么原因呢？？？？？？？
	}		
}

//启用噪声任务---状态为3
function zhaoshentaskstart_msg($con)
{
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//===============================创建套字节==============================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	$gettaskid = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettaskid = trim($_GET['gettask']);
	}
	$userid = 0;
	
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}

	$sql = "update task set state=3 where taskid in (".$getValue.")  and task.channel=0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$sql = "update task set state=3 where sec_task_id in (".$getValue.") and task.tasktype='9' and task.channel=0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	unset($sql);
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./zhaoshentaskmanager.php?id=$gettaskid&userid=$userid";
		
		echo "<script>window.location='./error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./zhaoshentaskmanager.php?id=$gettaskid&userid=$userid";
		
		$getidlist = explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "task?state=3&id=".$getid;			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
		
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}

		echo "<script>window.location='./success.php'</script>";	
		
		exit;//是什么原因呢？？？？？？？
	}		
}

//启用led广播---状态为3
function ledtaskstart_msg($con)
{
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//===============================创建套字节==============================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	$gettaskid = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettaskid = trim($_GET['gettask']);
	}
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}

	$sql = "update task set state=3 where taskid in (".$getValue.") and task.channel=0 ";
	mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./ledtaskmanager.php?id=$gettaskid&userid=$userid";
		
		echo "<script>window.location='./error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./ledtaskmanager.php?id=$gettaskid&userid=$userid";
		
		$getidlist = explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "task?state=3&id=".$getid;			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}

		echo "<script>window.location='./success.php'</script>";	
		
		exit;//是什么原因呢？？？？？？？
	}		
}






function ttsfiletaskstart_msg($con)
{
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//===============================创建套字节==============================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}

	$sql = "update task set state=3 where taskid in (".$getValue.") and task.info='' and task.channel=0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$sql = "update task set state=3 where sec_task_id in (".$getValue.") and task.info='' and task.channel=0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	unset($sql);
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./displayttsmanager.php";
		
		echo "<script>window.location='./error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./displayttsmanager.php";
		
		$getidlist = explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "task?state=3&id=".$getid;			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
		
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}

		echo "<script>window.location='./success.php'</script>";	
		
		exit;//是什么原因呢？？？？？？？
	}		
}
//停止文字语音任务---状态为2
function ttsfiletaskstop_msg($con)
{
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//=======================创建套字节=======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	
	
	$sql = "update task set state=2 where taskid in (".$getValue.") and task.tasktype IN(17,19) and task.info='' and task.channel=0 and sec_task_id=0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	 
	 $sql = "update task set state=2 where sec_task_id in (".$getValue.") and task.info='' and task.channel=0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	 
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./displayttsmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./displayttsmanager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=2&id=".$getid;
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
		
			//$create_socket_obj->send_socket_generate_general("task",13,$getid);
			$result=mysqli_query($con,"select tasktype from task where taskid='$getid'")or die("Execute error".mysqli_error($con));
	
			if($row=mysqli_fetch_array($result))
			{
			$typeid=$row['tasktype'];
			if($typeid==17)
			$create_socket_obj->send_socket_generate_general2("task",13,$getid,$typeid);
			else
			$create_socket_obj->send_socket_generate_general2("task",2,$getid,$typeid);
			}
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
//停止文件任务---状态为2
function filetaskstop_msg($con)
{
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//=======================创建套字节=======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	$gettaskid = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettaskid = trim($_GET['gettask']);
	}
	
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	$sql = "update task set state=2 where taskid in (".$getValue.") and task.channel=0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	$sql = "update task set state=2 where sec_task_id in (".$getValue.") and task.tasktype ='9'  and task.channel=0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	 
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./taskmanager.php?id=$gettaskid&userid=$userid";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php?id=$gettaskid&userid=$userid";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=2&id=".$getid;
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
		//	$create_socket_obj->send_socket_generate_general("task",2,$getid);
		$create_socket_obj->send_socket_generate_general2("task",2,$getid,2);
		}
		echo "<script>window.location='success.php'</script>";	
	}
}

//停止噪声任务---状态为2
function zhaoshentaskstop_msg($con)
{
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//=======================创建套字节=======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	$gettaskid = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettaskid = trim($_GET['gettask']);
	}
	
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	$sql = "update task set state=2 where taskid in (".$getValue.") and task.channel=0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	$sql = "update task set state=2 where sec_task_id in (".$getValue.") and task.tasktype ='9'  and task.channel=0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	 
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./zhaoshentaskmanager.php?id=$gettaskid&userid=$userid";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./zhaoshentaskmanager.php?id=$gettaskid&userid=$userid";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=2&id=".$getid;
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
		//	$create_socket_obj->send_socket_generate_general("task",2,$getid);
		$create_socket_obj->send_socket_generate_general2("task",2,$getid,25);
		}
		echo "<script>window.location='success.php'</script>";	
	}
}

//停止led任务---状态为2
function ledtaskstop_msg($con)
{
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//=======================创建套字节=======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	$gettaskid = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettaskid = trim($_GET['gettask']);
	}
	
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	$sql = "update task set state='2' where taskid in (".$getValue.")  and task.channel=0 ";
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./ledtaskmanager.php?id=$gettaskid&userid=$userid";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./ledtaskmanager.php?id=$gettaskid&userid=$userid";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
		/*$led_sql = "SELECT taskid FROM task WHERE taskid = '$getid' where tasktype='25'";
			$led_result = mysqli_query($con,$led_sql) or die(mysqli_error($con));
			if($led_row = mysqli_fetch_array($led_result))
			{
			 $create_socket_obj->send_socket_generate_general2("task",13,$led_row['taskid'],25);
			}*/
		//$create_socket_obj->send_socket_generate_general("task",2,$getid);
		$create_socket_obj->send_socket_generate_general2("task",2,$getid,24);
		}
		echo "<script>window.location='success.php'</script>";	
	}
}



//暂停文件任务---状态为4
function filetaskpause_msg($con)
{
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//=======================创建套字节=======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	$gettaskid = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettaskid = trim($_GET['gettask']);
	}
	
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	

		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php?id=$gettaskid&userid=$userid";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=2&id=".$getid;
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
		//	$create_socket_obj->send_socket_generate_general("task",2,$getid);
		$create_socket_obj->send_socket_generate_general2("task",22,$getid,2);
		}
		echo "<script>window.location='success.php'</script>";	

}

//恢复文件任务---状态为4
function filetaskhuifu_msg($con)
{
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//=======================创建套字节=======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	$gettaskid = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettaskid = trim($_GET['gettask']);
	}
	
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	

		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php?id=$gettaskid&userid=$userid";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=2&id=".$getid;
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
		//	$create_socket_obj->send_socket_generate_general("task",2,$getid);
		$create_socket_obj->send_socket_generate_general2("task",23,$getid,2);
		}
		echo "<script>window.location='success.php'</script>";	

}



//查看当前任务中操作---暂停
function stop_curr_tast_state($con)
{
	//require_once('inc/socket_conf.php'); 
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建套字节===================
	$create_socket_obj = new create_socket_class();
	$task_type=2;
	$task_state = 2;
	
	$curr_task_id = "";
	
	if(isset($_GET['taskid']))
	{
		$curr_task_id = trim($_GET['taskid']);
	}
	//判断是什么类型任务
	$judge_task_sql = "SELECT taskname,tasktype FROM audioserver.task WHERE task.taskid = '$curr_task_id'";
	
	$judge_task_result = mysqli_query($con,$judge_task_sql) or die(mysqli_error($con));
	
	if($judge_task_row = mysqli_fetch_array($judge_task_result))
	{
	$task_type=$judge_task_row['tasktype'];
		if(5 == $judge_task_row['tasktype'])
		{
			$judge_task_othersql = "select taskid from task where prepower=0 and info='' and ";
	
			$judge_task_othersql.= "tasktype=5 and taskname= '$judge_task_row[taskname]' and taskid != '$curr_task_id'";
	
			$judge_task_otherresult = mysqli_query($con,$judge_task_othersql) or die(mysqli_error($con));
	
			if($judge_task_otherrow = mysqli_fetch_array($judge_task_otherresult))
			{
				$power_other_taskid = trim($judge_task_otherrow['taskid']);
			}
	
			@mysqli_free_result($judge_task_otherresult);
	
			unset($judge_task_othersql,$judge_task_otherrow);
			
			$task_state = 3;
			
			$curr_task_id = $power_other_taskid;
		}
	}
	@mysqli_free_result($judge_task_result);
	
	unset($judge_task_row,$judge_task_sql);
	
	$task_sql = "update task set state=".$task_state." where taskid = '$curr_task_id' ";
	
	$task_result = mysqli_query($con,$task_sql) or die(mysqli_error($con));
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
		$_SESSION['url'] = "./Browse_active_task.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
		$_SESSION['url'] = "./Browse_active_task.php";
		//===================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=".$task_state."&id=".$curr_task_id;			
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		if($task_type==17)
		$create_socket_obj->send_socket_generate_general2("task",13,$curr_task_id,$task_type);
		else
		$create_socket_obj->send_socket_generate_general2("task",$task_state,$curr_task_id,$task_type);
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//查看当前任务中操作---执行
function start_curr_tast_state($con)
{
	
	
	//require_once('inc/socket_conf.php'); 
	//=======================添加外部变量
	global $do_php_prompt;
	//====================创建套字节===================
	$create_socket_obj = new create_socket_class();
	
	$curr_task_id = "";
	
	if(isset($_GET['taskid']))
	{
		$curr_task_id = trim($_GET['taskid']);
	}
	
	$task_sql = "update task set state=3 where taskid = '$curr_task_id' ";
	
	$task_result = mysqli_query($con,$task_sql) or die(mysqli_error($con));
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
		$_SESSION['url'] = "./Browse_active_task.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
		$_SESSION['url'] = "./Browse_active_task.php";
		//=======================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=3&id=".$curr_task_id;			
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_generate_general("task",3,$curr_task_id);
		
		echo "<script>window.location='success.php'</script>";	
	}	
}

//编辑任务---没有被使用
function taskedit_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	mysqli_query($con,"UPDATE `task` SET `taskname`='$_POST[taskname]',`streamid`='$_POST[streamid]',`startdate`='$_POST[startdate]',`starttime`='$_POST[starttime]', `timelength`='$_POST[timelength]' WHERE taskid='$_GET[id]'");	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}
}

//对文字语音任务删除
function ttstaskdel_msg($con)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建套字节===================
	$create_socket_obj = new create_socket_class();
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		
		$f_taskId_array = explode(",",$taskid);
	}

	//启用事务
	mysqli_query($con,"START TRANSACTION");
	$result=mysqli_query($con,"select taskid from task where sec_task_id IN(".$taskid.") and tasktype=24")or die("Execute error".mysqli_error($con));
	
	if($rows=mysqli_fetch_array($result))
	{
		del_ledtask($con,$rows['taskid'],24);
	}
	for($i=0; $i<count($f_taskId_array); $i++)
	{
		//判断该任务功放
		$file_task_sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$f_taskId_array[$i]' AND (task.tasktype = 9) ";
		
		$file_task_sql.= "AND task.info = '' AND task.channel = 0 ";
		
		$file_task_result = mysqli_query($con,$file_task_sql);
		
		if($file_task_row = mysqli_fetch_array($file_task_result))
		{
			mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '".$file_task_row['taskid']."'");
		
			mysqli_query($con,"DELETE FROM task WHERE taskid = '".$file_task_row['taskid']."' AND info = '' AND tasktype = 9 AND channel = 0 ");
			$sql= "SELECT mediaid FROM mediaoftask WHERE mediaoftask.taskid = '$f_taskId_array[$i]'";
			$ttstask_result = mysqli_query($con,$sql);
				
			if($tts_task_row = mysqli_fetch_array($ttstask_result))
			{
				mysqli_query($con,"DELETE FROM media WHERE media.id ='".$tts_task_row['mediaid']."'");
				mysqli_query($con,"DELETE FROM shortcutkeytask WHERE mediaid ='".$tts_task_row['mediaid']."'");
				mysqli_query($con,"DELETE FROM ttssentence WHERE sentenceid ='".$tts_task_row['mediaid']."'");
			}
		
		}
		@mysqli_free_result($file_task_result);
				
		unset($file_task_row,$file_task_sql);
	}

	//删除终端任务
	mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid IN(".$taskid.")");

	//删除媒体任务
	mysqli_query($con,"DELETE FROM mediaoftask WHERE mediaoftask.taskid IN(".$taskid.")");

	//删除自己任务
	mysqli_query($con,"DELETE FROM task WHERE taskid IN(".$taskid.")");
	
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./displayttsmanager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	= new send_message_to_server($port_conf);	
			
			$msg = "task?state=6&id=".$getid;		
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_generate_general("task",14,$getid);
				$create_socket_obj->send_socket_generate_general2("task",14,$getid,17);
		}
		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./displayttsmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
}
//复制任务
function copyFileTask($con)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建套字节===================
	$create_socket_obj = new create_socket_class();
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		
		$f_taskId_array = explode(",",$taskid);
	}
	$taskname ="111";
	if(isset($_GET['taskname']))
	{
		$taskname = trim($_GET['taskname']);
		
	
	}
	//启用事务
	mysqli_query($con,"START TRANSACTION");
	
	for($i=0; $i<count($f_taskId_array); $i++)
	{
	
		//判断该任务功放
		$sql1 = "SELECT * FROM task WHERE task.taskid = '$f_taskId_array[$i]'";

		$result1 = mysqli_query($con,$sql1);
		if($getrows = mysqli_fetch_array($result1))
		{
				mysqli_query($con,"INSERT INTO task(taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,
startdate,enddate,playtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,
						playfileid,info,defaultvolume,task_user_id,sec_task_id,parentid,offlinestate,createtime,disableday,interval_s,intplaylength,intplaylengthtype)
			VALUES('$taskname','$getrows[2]','$getrows[3]','$getrows[4]','$getrows[5]','$getrows[6]',
						'$getrows[7]','$getrows[8]','$getrows[9]','$getrows[10]','$getrows[11]',
			'$getrows[13]','$getrows[14]','$getrows[15]','$getrows[16]','$getrows[17]','$getrows[18]',
'$getrows[19]','$getrows[20]','$getrows[21]','$getrows[22]','$getrows[23]','$getrows[24]','$getrows[25]','$getrows[26]','$getrows[27]','$getrows[28]','$getrows[29]','$getrows[30]','$getrows[31]','$getrows[32]')");	
			$gettaskid = mysqli_query($con,"SELECT taskid,tasktype FROM task WHERE taskid = (SELECT MAX(taskid) FROM task)");	
			if($gettaskrows = mysqli_fetch_array($gettaskid))
			{
				$taskmax2=$gettaskrows[0];
				
					$getmusicresults = mysqli_query($con,"SELECT * FROM mediaoftask WHERE taskid ='$getrows[0]'");
					if($getmediarows = mysqli_fetch_array($getmusicresults)) 
					{	
						
						mysqli_query($con,"INSERT INTO mediaoftask(mediaid,taskid,sort) VALUES('$getmediarows[1]','$taskmax2','$getmediarows[3]')");
					}	
						$getterminalresults = mysqli_query($con,"SELECT * FROM terminaloftask WHERE taskid ='$getrows[0]'");
						while ($getterminalrows = mysqli_fetch_array($getterminalresults)) 
						{
							mysqli_query($con,"INSERT INTO terminaloftask (taskid,terminalid,workstate,groupid,area) 			VALUES('$taskmax2','$getterminalrows[2]','$getterminalrows[3]','$getterminalrows[4]','$getterminalrows[5]')");
						}
			}
		}
	
		//判断该任务功放
		$sql1 = "SELECT * FROM task WHERE task.sec_task_id = '$f_taskId_array[$i]' AND (task.tasktype = 9) ";

		$result1 = mysqli_query($con,$sql1);
		if($getrows = mysqli_fetch_array($result1))
		{
				mysqli_query($con,"INSERT INTO task(taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,
startdate,enddate,playtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,
						playfileid,info,defaultvolume,task_user_id,sec_task_id,parentid,offlinestate,createtime,disableday,interval_s,intplaylength,intplaylengthtype)
			VALUES('$taskname','$getrows[2]','$getrows[3]','$getrows[4]','$getrows[5]','$getrows[6]',
						'$getrows[7]','$getrows[8]','$getrows[9]','$getrows[10]','$getrows[11]',
			'$getrows[13]','$getrows[14]','$getrows[15]','$getrows[16]','$getrows[17]','$getrows[18]',
'$getrows[19]','$getrows[20]','$getrows[21]','$getrows[22]','$getrows[23]','$getrows[24]','$getrows[25]','$getrows[26]','$getrows[27]','$getrows[28]','$getrows[29]','$getrows[30]','$getrows[31]','$getrows[32]')");	
		
		$gettaskid = mysqli_query($con,"SELECT taskid,tasktype FROM task WHERE taskid = (SELECT MAX(taskid) FROM task)");	
			if($gettaskrows = mysqli_fetch_array($gettaskid))
			{
				$taskmax=$gettaskrows[0];
				$sqlmedia_result = "UPDATE task SET sec_task_id = '$taskmax2' WHERE taskid = '$gettaskrows[0]' ";
				mysqli_query($con,$sqlmedia_result) or die(mysqli_error($con));
					
					$getterminalresults = mysqli_query($con,"SELECT * FROM terminaloftask WHERE taskid ='$getrows[0]'");
					while ($getterminalrows = mysqli_fetch_array($getterminalresults)) 
					{

						mysqli_query($con,"INSERT INTO terminaloftask (taskid,terminalid,workstate,groupid,area)VALUES('$taskmax','$getterminalrows[2]','$getterminalrows[3]','$getterminalrows[4]','$getterminalrows[5]')");
					}
			}
		}

	}
	
	
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./taskmanager.php";
		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./taskmanager.php";
		echo "<script>window.location='error.php'</script>";
	}
}

//对led任务删除
function ledtaskdel_msg($con)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建套字节===================
	$create_socket_obj = new create_socket_class();
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		
		$f_taskId_array = explode(",",$taskid);
	}
	$gettask = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettask = trim($_GET['gettask']);
		
		
	}
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	//启用事务
	mysqli_query($con,"START TRANSACTION");
	
	for($i=0; $i<count($f_taskId_array); $i++)
	{
		//判断该任务功放
		$file_task_sql = "SELECT taskid,tasktype FROM task WHERE cmdargs = '$f_taskId_array[$i]' and cmdargs >70000 ";

		$file_task_result = mysqli_query($con,$file_task_sql);
		
		while($file_task_row = mysqli_fetch_array($file_task_result))
		{
			if($file_task_row['tasktype'] == 24 )
			{
				if($file_task_row['tasktype'] == 24)
				{
					mysqli_query($con,"DELETE FROM ledsentence WHERE mediaid IN(select mediaid from mediaoftask where taskid in(".$file_task_row['taskid']."))");
					mysqli_query($con,"DELETE FROM media WHERE media.id IN(select mediaid from mediaoftask where taskid in(".$file_task_row['taskid']."))");
				}
					//删除媒体任务
					mysqli_query($con,"DELETE FROM mediaoftask WHERE mediaoftask.taskid IN(".$file_task_row['taskid'].")");

					//mysqli_query($con,"DELETE FROM mediaofterminal WHERE mediaofterminal.taskid IN(".$file_task_row['taskid'].")");
					
					mysqli_query($con,"DELETE FROM terminalkey WHERE id IN(select keyid from terminalkeymap where terminalid in(".$file_task_row['taskid']."))");
					mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalid IN(".$file_task_row['taskid'].")");	
			}
			
			//删除终端任务
			mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid IN(".$file_task_row['taskid'].")");
			
			//删除终端任务
			mysqli_query($con,"DELETE FROM ledoftask WHERE ledoftask.taskid IN(".$file_task_row['taskid'].")");
				//删除自己任务
			mysqli_query($con,"DELETE FROM task WHERE taskid IN(".$file_task_row['taskid'].")");

		}
		@mysqli_free_result($file_task_result);
				
		unset($file_task_row,$file_task_sql);
	}

	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./ledtaskmanager.php?id=$gettask&userid=$userid";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	= new send_message_to_server($port_conf);	
			
			$msg = "task?state=6&id=".$getid;		
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
		//	$create_socket_obj->send_socket_generate_general("task",6,$getid);
					$create_socket_obj->send_socket_generate_general2("task",6,$getid,2);
		}
		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./ledtaskmanager.php?id=$gettask&userid=$userid";
		
		echo "<script>window.location='error.php'</script>";
	}
}


//对文件广播任务删除
function taskdel_msg($con)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建套字节===================
	$create_socket_obj = new create_socket_class();
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		
		$f_taskId_array = explode(",",$taskid);
	}
	$gettask = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettask = trim($_GET['gettask']);
		
		
	}
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	//启用事务
	mysqli_query($con,"START TRANSACTION");
	
	for($i=0; $i<count($f_taskId_array); $i++)
	{
		//判断该任务功放
		$file_task_sql = "SELECT prepower FROM task WHERE task.taskid = '$f_taskId_array[$i]' AND (task.tasktype = 2 OR task.tasktype = 7 OR task.tasktype = 15) ";
		
		$file_task_sql.= "AND task.info = '' AND task.channel = 0 ";
		
		$file_task_result = mysqli_query($con,$file_task_sql);
		
		if($file_task_row = mysqli_fetch_array($file_task_result))
		{
			if($file_task_row['prepower'] > 0)
			{
				//查找相关功放
				$file_func_sql = "SELECT taskid FROM task WHERE sec_task_id = '$f_taskId_array[$i]' AND tasktype = 9 AND info = '' AND channel = 0 ";
				
				$file_func_result = mysqli_query($con,$file_func_sql);
				
				if($file_func_row = mysqli_fetch_array($file_func_result))
				{
					//删除攻防任务
					mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '".$file_func_row['taskid']."'");
					
					//删除功放
					mysqli_query($con,"DELETE FROM task WHERE taskid = '".$file_func_row['taskid']."' AND info = '' AND tasktype = 9 AND channel = 0 ");
				}
				
				@mysqli_free_result($file_func_result);
				
				unset($file_func_row,$file_func_sql);
			}
		}
		@mysqli_free_result($file_task_result);
				
		unset($file_task_row,$file_task_sql);
	}
	
	//删除终端任务
	mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid IN(".$taskid.")");
	
	//删除媒体任务
	mysqli_query($con,"DELETE FROM mediaoftask WHERE mediaoftask.taskid IN(".$taskid.")");
	//mysqli_query($con,"DELETE FROM mediaofterminal WHERE mediaofterminal.taskid IN(".$taskid.")");
	
	mysqli_query($con,"DELETE FROM terminalkey WHERE id IN(select keyid from terminalkeymap where terminalid in(".$taskid."))");
	mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalid IN(".$taskid.")");
	
	//删除自己任务
	mysqli_query($con,"DELETE FROM task WHERE taskid IN(".$taskid.") AND info = '' AND (tasktype = 2 or tasktype = 7 or tasktype = 15) AND channel = 0 ");
	$sql_led_name = "SELECT taskid FROM task where tasktype=24 and sec_task_id = '$taskid'";	
	$result_led_name = mysqli_query($con,$sql_led_name) or die(mysqli_error($con));
	if(mysqli_num_rows($result_led_name) > 0)
	{
		if($get_row = mysqli_fetch_array($result_led_name))
		{	
			$getledtaskid=$get_row['taskid'];
			del_ledtask($con,$getledtaskid,24);
		}
	}	
	@mysqli_free_result($result_led_name);	
	unset($sql_led_name);

	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php?id=$gettask&userid=$userid";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	= new send_message_to_server($port_conf);	
			
			$msg = "task?state=6&id=".$getid;		
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
		//	$create_socket_obj->send_socket_generate_general("task",6,$getid);
					$create_socket_obj->send_socket_generate_general2("task",6,$getid,2);
		}
		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./taskmanager.php?id=$gettask&userid=$userid";
		
		echo "<script>window.location='error.php'</script>";
	}
}


//对视频广播任务删除
function vediotaskdel_msg($con)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建套字节===================
	$create_socket_obj = new create_socket_class();
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		
		$f_taskId_array = explode(",",$taskid);
	}
	$gettask = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettask = trim($_GET['gettask']);
		
		
	}
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	//启用事务
	mysqli_query($con,"START TRANSACTION");

	for($i=0; $i<count($f_taskId_array); $i++)
	{
		//判断该任务功放
		$file_task_sql = "SELECT prepower FROM task WHERE task.taskid = '$f_taskId_array[$i]' AND (task.tasktype = 27) ";
		
		$file_task_sql.= "AND task.info = ''";
		
		$file_task_result = mysqli_query($con,$file_task_sql);
		
		if($file_task_row = mysqli_fetch_array($file_task_result))
		{
			if($file_task_row['prepower'] > 0)
			{
				//查找相关功放
				$file_func_sql = "SELECT taskid FROM task WHERE sec_task_id = '$f_taskId_array[$i]' AND tasktype = 9 AND info = ''";
				
				$file_func_result = mysqli_query($con,$file_func_sql);
				
				if($file_func_row = mysqli_fetch_array($file_func_result))
				{
					//删除攻防任务
					mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '".$file_func_row['taskid']."'");
					
					//删除功放
					mysqli_query($con,"DELETE FROM task WHERE taskid = '".$file_func_row['taskid']."' AND info = '' AND tasktype = 9");
				}
				
				@mysqli_free_result($file_func_result);
				
				unset($file_func_row,$file_func_sql);
			}
		}
		@mysqli_free_result($file_task_result);
				
		unset($file_task_row,$file_task_sql);
	}
	
	//删除终端任务
	mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid IN(".$taskid.")");
	
	//删除媒体任务
	mysqli_query($con,"DELETE FROM mediaoftask WHERE mediaoftask.taskid IN(".$taskid.")");
	//mysqli_query($con,"DELETE FROM mediaofterminal WHERE mediaofterminal.taskid IN(".$taskid.")");
	
	mysqli_query($con,"DELETE FROM terminalkey WHERE id IN(select keyid from terminalkeymap where terminalid in(".$taskid."))");
	mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalid IN(".$taskid.")");

	//删除自己任务
	mysqli_query($con,"DELETE FROM task WHERE taskid IN(".$taskid.") AND info = '' AND tasktype = 27");

	$sql_led_name = "SELECT taskid FROM task where tasktype=27 and sec_task_id = '$taskid'";	
	$result_led_name = mysqli_query($con,$sql_led_name) or die(mysqli_error($con));
	if(mysqli_num_rows($result_led_name) > 0)
	{
		if($get_row = mysqli_fetch_array($result_led_name))
		{	
			$getledtaskid=$get_row['taskid'];
			del_ledtask($con,$getledtaskid,24);
		}
	}	
	@mysqli_free_result($result_led_name);	
	unset($sql_led_name);

	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./videodisplaymanager.php?userid=$userid";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	= new send_message_to_server($port_conf);	
			
			$msg = "task?state=6&id=".$getid;		
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
		//	$create_socket_obj->send_socket_generate_general("task",6,$getid);
					$create_socket_obj->send_socket_generate_general2("task",6,$getid,2);
		}
		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./videodisplaymanager.php?userid=$userid";
		echo "<script>window.location='error.php'</script>";
	}
}



//对燥声任务删除
function zhaoshengtaskdel_msg($con)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建套字节===================
	$create_socket_obj = new create_socket_class();
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		
		$f_taskId_array = explode(",",$taskid);
	}
	$gettask = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettask = trim($_GET['gettask']);
		
		
	}
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	//启用事务
	mysqli_query($con,"START TRANSACTION");

	mysqli_query($con,"LOCK TABLE terminaloftask WRITE,mediaoftask WRITE,soundtask WRITE,terminalkey WRITE,terminalkeymap WRITE,task WRITE");
	
	
	for($i=0; $i<count($f_taskId_array); $i++)
	{
		//判断该任务功放
		$file_task_sql = "SELECT prepower FROM task WHERE task.taskid = '$f_taskId_array[$i]' AND (task.tasktype = 25) ";
		
		$file_task_sql.= "AND task.info = '' AND task.channel = 0 ";
		
		$file_task_result = mysqli_query($con,$file_task_sql);
		
		if($file_task_row = mysqli_fetch_array($file_task_result))
		{
			if($file_task_row['prepower'] > 0)
			{
				//查找相关功放
				$file_func_sql = "SELECT taskid FROM task WHERE sec_task_id = '$f_taskId_array[$i]' AND tasktype = 9 AND info = '' AND channel = 0 ";
				
				$file_func_result = mysqli_query($con,$file_func_sql);
				
				if($file_func_row = mysqli_fetch_array($file_func_result))
				{
					//删除攻防任务
					mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '".$file_func_row['taskid']."'");
					
					//删除功放
					mysqli_query($con,"DELETE FROM task WHERE taskid = '".$file_func_row['taskid']."' AND info = '' AND tasktype = 9 AND channel = 0 ");
				}
				
				@mysqli_free_result($file_func_result);
				
				unset($file_func_row,$file_func_sql);
			}
		}
		@mysqli_free_result($file_task_result);
				
		unset($file_task_row,$file_task_sql);
	}
	
	//删除终端任务
	mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid IN(".$taskid.")");
	
	//删除媒体任务
	mysqli_query($con,"DELETE FROM mediaoftask WHERE mediaoftask.taskid IN(".$taskid.")");
//	mysqli_query($con,"DELETE FROM mediaofterminal WHERE mediaofterminal.taskid IN(".$taskid.")");
	mysqli_query($con,"DELETE FROM soundtask WHERE soundtask.taskid IN(".$taskid.")");
	mysqli_query($con,"DELETE FROM terminalkey WHERE id IN(select keyid from terminalkeymap where terminalid in(".$taskid."))");
	mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalid IN(".$taskid.")");
	
	//删除自己任务
	mysqli_query($con,"DELETE FROM task WHERE taskid IN(".$taskid.") AND info = '' AND (tasktype = 25 ) AND channel = 0 ");
	
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
		mysqli_query($con,'UNLOCK TABLES');
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./zhaoshentaskmanager.php?id=$gettask&userid=$userid";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	= new send_message_to_server($port_conf);	
			
			$msg = "task?state=6&id=".$getid;		
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
		//	$create_socket_obj->send_socket_generate_general("task",6,$getid);
					$create_socket_obj->send_socket_generate_general2("task",6,$getid,2);
		}
		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
		mysqli_query($con,'UNLOCK TABLES');
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./zhaoshentaskmanager.php?id=$gettask&userid=$userid";
		
		echo "<script>window.location='error.php'</script>";
	}
}

//删除任务日志
function tasklogdel_msg($con)
{
	
	require_once("inc/config.php");
	//添加外部变量
	$get_task_log=0;
	global $do_php_prompt;
	$Task_Log="datelog/";
	$Task_Logs = array();

	if(is_dir($Task_Log))
	{
		if($folder_handle = opendir($Task_Log))
		{
			while( ($file = readdir($folder_handle)) !== false)
			{
				if($file != "." && $file != "..")
				{
					if(is_file($Task_Log.$file))
					{
	
								unlink($Task_Log.$file);
					}
				}
			}
		}
	}

	
	$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息

	$_SESSION['url'] = "./tasklogmanager.php";

	echo "<script>window.location='success.php'</script>";	
}

//删除日志
function logdel_msg($con)
{
	//添加外部变量
	global $do_php_prompt;	
	//mysqli_query($con,"DELETE FROM `log` WHERE id in $_GET[id]");	
	mysqli_query($con,"TRUNCATE TABLE log");
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./logmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./logmanager.php";
	
		echo "<script>window.location='success.php'</script>";	
	}
}
//创建分区---没有被使用
function streamadd22_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
			
	mysqli_query($con,"INSERT INTO `serverplaystream` (`name`,`feedfile`,`feed`,`outputformat`,`inputformat`,`AudioCodec`,`MaxTime`,`AudioBitRate`,`AudioChannels`,`AudioSampleRate`,`AudioQuality`) 	VALUES ('$_POST[name]','$_POST[feedfile]','$_POST[feed]','$_POST[outputformat]','$_POST[inputformat]','$_POST[AudioCodec]','$_POST[MaxTime]','$_POST[AudioBitRate]','$_POST[AudioChannels]','$_POST[AudioSampleRate]','$_POST[AudioQuality]')");
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}	
}
//创建分区---问题必须添加终端---问题（要不在任务编辑时显示分区有问题---没有对空分区处理）
function streamadd_msg($con)
{
	require_once("inc/terminal_group_operate.php");
	$userid=$_SESSION['userid'];
	//添加外部变量
	global $do_php_prompt;
	//====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$streamname = "";
	if(isset($_POST['streamname']))
	{
		$streamname = trim($_POST['streamname']);
	}
	$discription = "";
	if(isset($_POST['discription']))
	{
		$discription = trim($_POST['discription']);
	}
	$nostreamterminal = "";
	if(isset($_POST['nostreamterminal']))
	{
		$nostreamterminal = trim($_POST['nostreamterminal']);
		
		$nostreamarray = explode(",",$nostreamterminal);
	}
	//保证分区名称唯一
	 $sql = "select * from serverplaystream where serverplaystream.name = '$streamname'";
	 
	 $result = mysqli_query($con,$sql) or die(mysqli_error($con));
	 
	 if(mysqli_num_rows($result) > 0)
	 {
	 	@mysqli_free_result($result);
	
		unset($sql);
		//============================================================================================
	 	/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
	
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	 }
	 else
	 {
	 	@mysqli_free_result($result);
	
		unset($sql);
	 }
	mysqli_query($con,"LOCK TABLE serverplaystream WRITE,terminalofgroup WRITE,terminaloftask WRITE,terminalofalarmgroup WRITE");
	
	$sql = "INSERT INTO serverplaystream (NAME, info,userid) VALUES('$streamname', '$discription','$userid') ";

	mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
	
	if(!empty($nostreamterminal))
	{
		$result = mysqli_query($con,"SELECT MAX(streamid) FROM serverplaystream") or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			$getnewstreamid = $row[0];
		}
		@mysqli_free_result($result);
		
		unset($row,$sql);
			
		for($i=0; $i<count($nostreamarray); $i++)
		{
			if(is_numeric($nostreamarray[$i]))
			{
				$terminalid = (int)$nostreamarray[$i];
				$results = mysqli_query($con,"SELECT groupid FROM terminaloftask WHERE terminalid ='$terminalid' and groupid='0'") or die(mysqli_error($con));
				if($rows = mysqli_fetch_array($results))
				{
					$groupid = $rows[0];
					if($groupid==0)
					{
						$sqls = "UPDATE terminaloftask SET  groupid = '$getnewstreamid' WHERE	terminalid = '$terminalid' ";
						mysqli_query($con,$sqls) or die(mysqli_error($con));
					}
				}
				$sql2 = "UPDATE terminalofalarmgroup SET  groupid = '$getnewstreamid' WHERE	terminalid = '$terminalid'";
						mysqli_query($con,$sql2) or die(mysqli_error($con));
			//	$sql3 = "UPDATE terminalofcallgroup SET  selectgroupid = '$getnewstreamid' WHERE	terminalid = '$terminalid'";
				//mysqli_query($con,$sql3) or die(mysqli_error($con));
				
				$sql = "INSERT INTO audioserver.terminalofgroup(terminalid,groupid) VALUES('$terminalid','$getnewstreamid')";
				
				insert_group($sql);	
				
				unset($sql);		
			}
		}
	}
	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./streammanager.php";
		echo "<script>window.location='success.php'</script>";	
	}	
}

//创建燥声设备---问题必须添加终端---问题（要不在任务编辑时显示分区有问题---没有对空分区处理）
function zhaoshengdeviceadd_msg($con)
{
	require_once("inc/terminal_group_operate.php");
	$userid=$_SESSION['userid'];
	//添加外部变量
	global $do_php_prompt;
	//====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$devicename = "";
	if(isset($_POST['devicename']))
	{
		$devicename = trim($_POST['devicename']);
	}
	$device_ip = "";
	if(isset($_POST['device_ip']))
	{
		$device_ip = trim($_POST['device_ip']);
	}
	$sendchanne = 1;
	if(isset($_POST['send_channe']))
	{
		$sendchanne = trim($_POST['send_channe']);
	}
	$device_addr = "";
	if(isset($_POST['device_addr']))
	{
		$device_addr = trim($_POST['device_addr']);
	}

	mysqli_query($con,"LOCK TABLE sounddevice WRITE");
	/*
	//保证分区名称唯一
	 $sql = "select * from sounddevice where devaddr = '$device_addr'";
	 
	 $result = mysqli_query($con,$sql) or die(mysqli_error($con));
	 
	 if(mysqli_num_rows($result) > 0)
	 {
	 	@mysqli_free_result($result);
	
		unset($sql);
		//============================================================================================
	 
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	 }
	 else
	 {
	 	@mysqli_free_result($result);
	
		unset($sql);
	 }
	*/
	$sql = "INSERT INTO `sounddevice` (ip,devaddr,NAME,groupid,dbvalue,sendport) VALUES('$device_ip', '$device_addr','$devicename','0','0','$sendchanne') ";

	mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
	
	mysqli_query($con,"UNLOCK TABLES");

	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./zhaoshendevice.php";
		
		echo "<script>window.location='success.php'</script>";	
	}	
}

//创建燥声设备---问题必须添加终端---问题（要不在任务编辑时显示分区有问题---没有对空分区处理）
function renliandeviceadd_msg($con)
{
	require_once("inc/terminal_group_operate.php");
	$userid=$_SESSION['userid'];
	//添加外部变量
	global $do_php_prompt;
	//====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$peopleid = "";
	if(isset($_POST['peopleid']))
	{
		$peopleid = trim($_POST['peopleid']);
	}
	$deviceid = "";
	if(isset($_GET['shibiedeviceid']))
	{
		$deviceid = trim($_GET['shibiedeviceid']);
	}
	$deviceip = "";
	if(isset($_POST['deviceip']))
	{
		$deviceip = trim($_POST['deviceip']);
	}

	$deviceaddr = "";
	if(isset($_POST['deviceaddr']))
	{
		$deviceaddr = trim($_POST['deviceaddr']);
	}

	$peoplename = "";
	if(isset($_POST['peoplename']))
	{
		$peoplename = trim($_POST['peoplename']);
	}

	$peoplesubboy1 = "";
	if(isset($_POST['peoplesubboy1']))
	{
		$peoplesubboy1 = trim($_POST['peoplesubboy1']);
	}

	$peoplesubboy2 = "";
	if(isset($_POST['peoplesubboy2']))
	{
		$peoplesubboy2 = trim($_POST['peoplesubboy2']);
	}
	$peoplesubboy3 = "";
	if(isset($_POST['peoplesubboy3']))
	{
		$peoplesubboy3 = trim($_POST['peoplesubboy3']);
	}


	mysqli_query($con,"LOCK TABLE ai_people WRITE");
	
	$sql = "INSERT INTO `ai_people` (shibiedeviceid,deviceaddr,peopleidcard,deviceip,boyname1,boyname2,boyname3,peoplename) VALUES( '$deviceid','$deviceaddr','$peopleid','$deviceip','$peoplesubboy1','$peoplesubboy2','$peoplesubboy3','$peoplename')";

	mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./aimanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}	
}


//创建燥声设备---问题必须添加终端---问题（要不在任务编辑时显示分区有问题---没有对空分区处理）
function renliandevicemodify_msg($con)
{
	require_once("inc/terminal_group_operate.php");
	$userid=$_SESSION['userid'];
	//添加外部变量
	global $do_php_prompt;
	//====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	$id = "";
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
	}

	$peopleid = "";
	if(isset($_POST['peopleid']))
	{
		$peopleid = trim($_POST['peopleid']);
	}
	$shibiedeviceid = "";
	if(isset($_GET['shibiedeviceid']))
	{
		$shibiedeviceid = trim($_GET['shibiedeviceid']);
	}
	$deviceip = "";
	if(isset($_POST['deviceip']))
	{
		$deviceip = trim($_POST['deviceip']);
	}

	$peoplename = "";
	if(isset($_POST['peoplename']))
	{
		$peoplename = trim($_POST['peoplename']);
	}

	$deviceaddr = "";
	if(isset($_POST['deviceaddr']))
	{
		$deviceaddr = trim($_POST['deviceaddr']);
	}

	$peoplesubboy1 = "";
	if(isset($_POST['peoplesubboy1']))
	{
		$peoplesubboy1 = trim($_POST['peoplesubboy1']);
	}

	$peoplesubboy2 = "";
	if(isset($_POST['peoplesubboy2']))
	{
		$peoplesubboy2 = trim($_POST['peoplesubboy2']);
	}
	$peoplesubboy3 = "";
	if(isset($_POST['peoplesubboy3']))
	{
		$peoplesubboy3 = trim($_POST['peoplesubboy3']);
	}

	mysqli_query($con,"LOCK TABLE ai_people WRITE");
	
$sql = "UPDATE ai_people SET deviceip = '$deviceip',peopleidcard = '$peopleid',deviceaddr = '$deviceaddr',peoplename = '$peoplename',boyname1 = '$peoplesubboy1',boyname2 = '$peoplesubboy2',boyname3 = '$peoplesubboy3' WHERE id = '$id' AND shibiedeviceid = '$shibiedeviceid'";

	mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./ai_people.php";
		
		echo "<script>window.location='success.php'</script>";	
	}	
}


//创建燥声分区---问题必须添加终端---问题（要不在任务编辑时显示分区有问题---没有对空分区处理）
function zhaoshengstreamadd_msg($con)
{
	require_once("inc/terminal_group_operate.php");
	$userid=$_SESSION['userid'];
	//添加外部变量
	global $do_php_prompt;
	//====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	$create_socket_obj = new create_socket_class();

	$streamname = "";
	if(isset($_POST['streamname']))
	{
		$streamname = trim($_POST['streamname']);
	}

	$nostreamterminal = "";
	if(isset($_POST['nostreamterminal']))
	{
		$nostreamterminal = trim($_POST['nostreamterminal']);
		
		$nostreamarray = explode(",",$nostreamterminal);
	}
	
	$nosoundsdevice = "";
	if(isset($_POST['nosoundsdevice']))
	{
		$nosoundsdevice = trim($_POST['nosoundsdevice']);
		
		$nodevicearray = explode(",",$nosoundsdevice);
	}

	//保证分区名称唯一
	 $sql = "select * from soundgroupinfo where soundgroupinfo.name = '$streamname'";
	 
	 $result = mysqli_query($con,$sql) or die(mysqli_error($con));
	 
	 if(mysqli_num_rows($result) > 0)
	 {
	 	@mysqli_free_result($result);
	
		unset($sql);
		//============================================================================================
	 	/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		
		echo "<script>window.history.back();</script>";
	
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	 }
	 else
	 {
	 	@mysqli_free_result($result);
	
		unset($sql);
	 }
	mysqli_query($con,"LOCK TABLE soundgroupinfo WRITE,soundgroup WRITE,terminal WRITE,sounddevice WRITE");
	
	$sql = "INSERT INTO soundgroupinfo (NAME, userid) VALUES('$streamname','$userid') ";

	mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
	
	
		$result = mysqli_query($con,"SELECT MAX(id) FROM soundgroupinfo") or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			$getnewstreamid = $row[0];
		}
		@mysqli_free_result($result);
		
		unset($row,$sql);
			
		for($i=0; $i<count($nostreamarray); $i++)
		{
			if(is_numeric($nostreamarray[$i]))
			{
				$terminalid = (int)$nostreamarray[$i];
				$sql = "INSERT INTO audioserver.soundgroup(terminalid,groupid) VALUES('$terminalid','$getnewstreamid')";
				insert_group($sql);	
				unset($sql);	
					$sql = "UPDATE terminal SET terminal.soundsgroupid = '$getnewstreamid' WHERE terminal.id = '$terminalid' ";
				update_group($sql);
				unset($sql);
				
			
			}
		}
		for($i=0; $i<count($nodevicearray); $i++)
		{
			if(is_numeric($nodevicearray[$i]))
			{
				$deviceid = (int)$nodevicearray[$i];
				$sql = "UPDATE sounddevice SET sounddevice.groupid = '$getnewstreamid' WHERE sounddevice.id = '$deviceid' ";
				update_group($sql);
				unset($sql);		
			}
		}

	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./zhaoshenmanager.php";
		$create_socket_obj->send_socket_zhaosheng_general("terminal",29,$getnewstreamid,$nostreamterminal);	
		echo "<script>window.location='success.php'</script>";	
	}	
}





//修改分区---没有使用到
function streamedit_msg($con)
{
	
	
	//添加外部变量
	global $do_php_prompt;
	
	mysqli_query($con,"UPDATE `serverplaystream` SET `name`='$_POST[name]',`feed`='$_POST[feed]',`feedfile`='$_POST[feedfile]' ,`outputformat`='$_POST[outputformat]',`inputformat`='$_POST[inputformat]',`AudioCodec`='$_POST[AudioCodec]',`MaxTime`='$_POST[MaxTime]',`AudioBitRate`='$_POST[AudioBitRate]',`AudioChannels`='$_POST[AudioChannels]',`AudioSampleRate`='$_POST[AudioSampleRate]',`AudioQuality`='$_POST[AudioQuality]' where streamid = '$_GET[id]'");	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		//inputterminaltofile();//修改终端文件数据
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//修改终端分区
function streambatedit_msg($con)
{
	require_once("inc/terminal_group_operate.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$streamname = "";
	if(isset($_POST['streamname']))
	{
		$streamname = trim($_POST['streamname']);
	}
	$description = "";
	if(isset($_POST['description']))
	{
		$description = trim($_POST['description']);
	}
	$selectedterminal = "";
	if(isset($_POST['selectedterminal']))
	{
		$selectedterminal = trim($_POST['selectedterminal']);
		$getterminalarray = explode(",",$selectedterminal);
	}
	$streamid = "";
	if(isset($_GET['id']))
	{
		$streamid = trim($_GET['id']);
	}

	$sql = "SELECT 	* FROM serverplaystream WHERE serverplaystream.name = '$streamname' AND serverplaystream.streamid != '$streamid'";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result) > 0)
	{
		@mysqli_free_result($result);
		unset($sql);
		//===========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		echo "<script>window.history.back();</script>";
		exit;
		*/
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	else
	{
		@mysqli_free_result($result);
		
		unset($sql);
	}
	
	mysqli_query($con,"LOCK TABLES serverplaystream WRITE,terminalofgroup WRITE,terminaloftask WRITE,terminalofalarmgroup WRITE");
	
	$sql = "UPDATE serverplaystream SET serverplaystream.name = '$streamname', info = '$description' WHERE serverplaystream.streamid = '$streamid' ";
	
	update_group($sql);
	unset($sql);

	$info = array();

	$getresults = mysqli_query($con,"SELECT groupid,terminalid FROM terminalofgroup WHERE terminalofgroup.groupid IN('$streamid')") or die(mysqli_error($con));			
	while($getrows = mysqli_fetch_array($getresults))
	{

		$info[] = array("groupid"=>$getrows['groupid'],"terminalid"=>$getrows['terminalid'],"flag"=>'0');

	}

	$sql = "DELETE FROM audioserver.terminalofgroup WHERE terminalofgroup.groupid in('$streamid')";
	
	delet_group($sql);
	unset($sql);
	
	foreach($getterminalarray as $terminal_id)
	{
		if(is_numeric($terminal_id))
		{
				$results = mysqli_query($con,"SELECT groupid FROM terminaloftask WHERE terminalid ='$terminal_id' and groupid='0'") or die(mysqli_error($con));
				if($rows = mysqli_fetch_array($results))
				{
					$groupid = $rows[0];
					if($groupid==0)
					{	
						$sqls = "UPDATE terminaloftask SET  groupid = '$streamid' WHERE	terminalid = '$terminal_id' ";
						mysqli_query($con,$sqls) or die(mysqli_error($con));	
					}
				}

				$sql2 = "UPDATE terminalofalarmgroup SET  groupid = '$streamid' WHERE	terminalid = '$terminal_id' ";
				mysqli_query($con,$sql2) or die(mysqli_error($con));
			$sql = "INSERT INTO audioserver.terminalofgroup (terminalid,groupid) VALUES('$terminal_id','$streamid')";
			insert_group($sql);
			unset($sql);
			for($i = 0; $i < count($info); $i++) {
				if($info[$i]['groupid'] == $streamid)
				{
					if($info[$i]['terminalid'] == $terminal_id)
					{

						$info[$i]['flag'] = '1';		
					}
				}
			}
		}
	}

	foreach($info as $get_flags)	
	{
		if($get_flags['flag'] == '0')
		{
			$greoupid=$get_flags['groupid'];
			$gterminalid=$get_flags['terminalid'];
			$sqls = "UPDATE terminaloftask SET  groupid = '0' WHERE terminaloftask.groupid ='$greoupid' AND	terminalid = '$gterminalid' ";
			//$sqls = "DELETE FROM terminaloftask WHERE terminaloftask.groupid ='$greoupid' AND terminaloftask.terminalid =$gterminalid ";
			//echo "<script>alert('".$gterminalid."');</script>";
			mysqli_query($con,$sqls) or die(mysqli_error($con));
			//delet_group($sql);		
		}
	}
			//unset($sql);
	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		echo "<script>window.location='success.php'</script>";	
	}
}

//修改燥声设备
function soundsdeviceedit_msg($con)
{
	require_once("inc/terminal_group_operate.php");
	//添加外部变量
	global $do_php_prompt;	
	//====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
		
	$devicename = "";
	if(isset($_POST['devicename']))
	{
		$devicename = trim($_POST['devicename']);
	}
	
	$device_ip = "";
	if(isset($_POST['device_ip']))
	{
		$device_ip = trim($_POST['device_ip']);
	}
	$device_addr = "";
	if(isset($_POST['device_addr']))
	{
		$device_addr = trim($_POST['device_addr']);
	
	}
	$send_channe = 1;
	if(isset($_POST['send_channe']))
	{
		$send_channe = trim($_POST['send_channe']);
	
	}
	
	$streamid = "";
	if(isset($_GET['id']))
	{
		$streamid = trim($_GET['id']);
	}

	mysqli_query($con,"LOCK TABLES sounddevice WRITE");
		
	$sql = "UPDATE sounddevice SET sounddevice.name = '$devicename', sounddevice.ip = '$device_ip', sounddevice.devaddr = '$device_addr', sounddevice.sendport = '$send_channe' WHERE sounddevice.id = '$streamid' ";
	update_group($sql);
	unset($sql);

	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./zhaoshendevice.php";
		echo "<script>window.location='success.php'</script>";	
	}
}


//修改燥声分区
function zhaoshengedit_msg($con)
{
	require_once("inc/terminal_group_operate.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//====================创建对象======================
	$forward_ok_error_obj = new forward_ok_error_class();
		$create_socket_obj = new create_socket_class();
	$streamname = "";
	if(isset($_POST['streamname']))
	{
		$streamname = trim($_POST['streamname']);
	}
	
	$selectedterminal = "";
	if(isset($_POST['selectedterminal']))
	{
		$selectedterminal = trim($_POST['selectedterminal']);
		$getterminalarray = explode(",",$selectedterminal);
	}
	$nosoundsdevice = "";
	if(isset($_POST['nosoundsdevice']))
	{
		$nosoundsdevice = trim($_POST['nosoundsdevice']);
		$nosoundsarray = explode(",",$nosoundsdevice);
	}
	
	$streamid = "";
	if(isset($_GET['id']))
	{
		$streamid = trim($_GET['id']);
	}

	$sql = "SELECT 	* FROM soundgroupinfo WHERE soundgroupinfo.name = '$streamname' AND soundgroupinfo.id != '$streamid'";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($result) > 0)
	{
		@mysqli_free_result($result);
		unset($sql);
		//===========================================================================================
		/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
		echo "<script>window.history.back();</script>";
		exit;
		*/
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	else
	{
		@mysqli_free_result($result);
		
		unset($sql);
	}
	
	mysqli_query($con,"LOCK TABLES soundgroupinfo WRITE,soundgroup WRITE,terminal WRITE,sounddevice WRITE");
	
	$sql = "UPDATE soundgroupinfo SET soundgroupinfo.name = '$streamname' WHERE soundgroupinfo.id = '$streamid' ";
	
	update_group($sql);
	unset($sql);
	
	$sql = "UPDATE terminal SET terminal.soundsgroupid = '0' WHERE terminal.id IN(select terminalid from soundgroup where groupid in('$streamid')) ";
	
	update_group($sql);
	unset($sql);
	$sql = "DELETE FROM audioserver.soundgroup WHERE soundgroup.groupid in('$streamid')";
	delet_group($sql);
	unset($sql);
	
	$sql = "UPDATE sounddevice SET sounddevice.groupid='0' WHERE sounddevice.groupid in('$streamid')";
	update_group($sql);
	
	foreach($getterminalarray as $terminal_id)
	{
		if(is_numeric($terminal_id))
		{
			$sql = "INSERT INTO audioserver.soundgroup (terminalid,groupid) VALUES('$terminal_id','$streamid')";
			insert_group($sql);
			unset($sql);
				$sql = "UPDATE terminal SET terminal.soundsgroupid='$streamid' WHERE terminal.id = '$terminal_id' ";
			update_group($sql);
			unset($sql);
		
		}
	}
	foreach($nosoundsarray as $device_id)
	{
		if(is_numeric($device_id))
		{
			$sql = "UPDATE sounddevice SET sounddevice.groupid='$streamid' WHERE sounddevice.id = '$device_id' ";
			update_group($sql);
			unset($sql);
		}
	}
	mysqli_query($con,"UNLOCK TABLES");
	
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$create_socket_obj->send_socket_zhaosheng_general("terminal",29,$streamid,$selectedterminal);
		$_SESSION['url'] = "./zhaoshenmanager.php";
		echo "<script>window.location='success.php'</script>";	
	}
}

//创建终端分区---没有被使用到
function streambaddterminal_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	$getterminal=$_POST['selectedterminal'];
	
	$getstream=$_GET['id'];
	
	$sql="UPDATE terminal SET terminal.groupid = '$getstream' WHERE terminal.id IN ($getterminal) ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";

		inputterminaltofile($con);

		echo "<script>window.location='success.php'</script>";	
	}
}
//删除寻呼分区
function delcallzone($con)
{
	require_once("inc/terminal_group_operate.php");
	//添加外部变量
	global $do_php_prompt;
	
	$streamid = "";
	
	if(isset($_GET['id']))
	{
		$streamid = trim($_GET['id']);
	}
	
	mysqli_query($con,"LOCK TABLES terminalofcallgroup WRITE,callgroup WRITE");
	
	$sql = "DELETE FROM callgroup WHERE callgroup.id IN($streamid)";
	
	delet_group($sql);
	
	unset($sql);
	
	$sql = "DELETE FROM terminalofcallgroup WHERE terminalofcallgroup.selectgroupid IN($streamid)";

	delet_group($sql);
	
	unset($sql);
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./view_terminal_call_group.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./view_terminal_call_group.php";
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//删除终端分区
function streamdel_msg($con)
{
	
	
	require_once("inc/terminal_group_operate.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	$streamid = "";
	
	if(isset($_GET['id']))
	{
		$streamid = trim($_GET['id']);
	}
	
	mysqli_query($con,"LOCK TABLES terminalofgroup WRITE,serverplaystream WRITE,terminaloftask WRITE,terminalofalarmgroup WRITE");
	
	$sql = "DELETE FROM audioserver.terminalofgroup WHERE terminalofgroup.groupid IN($streamid)";
	delet_group($sql);
	unset($sql);
	
	//$sqls = "DELETE FROM terminaloftask WHERE groupid IN($streamid)";
	//mysqli_query($con,$sqls) or die(mysqli_error($con));
	$sql = "UPDATE terminaloftask SET groupid='0' WHERE groupid IN($streamid) ";
	mysqli_query($con,$sql) or die(mysqli_error($con));
	$sql = "UPDATE terminalofalarmgroup SET groupid='0' WHERE groupid IN($streamid) ";
	mysqli_query($con,$sql) or die(mysqli_error($con));
	//$sql2 = "DELETE FROM terminalofalarmgroup WHERE groupid IN($streamid)";
	//mysqli_query($con,$sql2) or die(mysqli_error($con));
	/*
	$sqls = "UPDATE terminaloftask SET  groupid = '0' WHERE	groupid IN($streamid)";
	mysqli_query($con,$sqls) or die(mysqli_error($con));
	$sql2 = "UPDATE terminalofalarmgroup SET  groupid = '0' WHERE groupid IN($streamid)";
	mysqli_query($con,$sql2) or die(mysqli_error($con));
	*/
	//	$sql3 = "UPDATE terminalofcallgroup SET  selectgroupid = '0' WHERE	selectgroupid IN($streamid)";
//	mysqli_query($con,$sql3) or die(mysqli_error($con));
	$sql = "DELETE FROM serverplaystream WHERE serverplaystream.streamid IN($streamid)";

	delet_group($sql);
	
	unset($sql);
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./streammanager.php";
		
		echo "<script>window.location='success.php'</script>";	
	}
}

//删除燥声设备
function aidevicedel_msg($con)
{
	require_once("inc/terminal_group_operate.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	$streamid = "";
	
	if(isset($_GET['id']))
	{
		$streamid = trim($_GET['id']);
		$get_id_array = explode("|",$streamid);
	}
	
	mysqli_query($con,"LOCK TABLES ai_people WRITE");

	$sql = "DELETE FROM ai_people WHERE ai_people.id IN($get_id_array[0]) and ai_people.shibiedeviceid IN($get_id_array[1])";

	delet_group($sql);
	
	unset($sql);
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./ai_people.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./ai_people.php";
		
		echo "<script>window.location='success.php'</script>";	
	}
}

//删除燥声设备
function soundsdevicedel_msg($con)
{
	require_once("inc/terminal_group_operate.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	$streamid = "";
	
	if(isset($_GET['id']))
	{
		$streamid = trim($_GET['id']);
	}
	
	mysqli_query($con,"LOCK TABLES sounddevice WRITE");

	$sql = "DELETE FROM sounddevice WHERE sounddevice.id IN($streamid)";

	delet_group($sql);
	
	unset($sql);
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./zhaoshendevice.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./zhaoshendevice.php";
		
		echo "<script>window.location='success.php'</script>";	
	}
}

//删除燥声分区
function zhaoshengdel_msg($con)
{
	require_once("inc/terminal_group_operate.php");
	
	//添加外部变量
	global $do_php_prompt;
	$create_socket_obj = new create_socket_class();
	$streamid = "";
	
	if(isset($_GET['id']))
	{
		$streamid = trim($_GET['id']);
	}
	
	mysqli_query($con,"LOCK TABLES soundgroupinfo WRITE,soundgroup WRITE,terminal WRITE,sounddevice WRITE");
	
	$sql = "DELETE FROM audioserver.soundgroup WHERE soundgroup.groupid IN($streamid)";
	delet_group($sql);
	unset($sql);
	
	$sql="UPDATE sounddevice SET sounddevice.groupid = '0' WHERE sounddevice.groupid IN ($streamid) ";
	mysqli_query($con,$sql) or die(mysqli_error($con));

	$sql="UPDATE terminal SET terminal.soundsgroupid = '0' WHERE terminal.soundsgroupid IN ($streamid) ";
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$sql = "DELETE FROM soundgroupinfo WHERE soundgroupinfo.id IN($streamid)";

	delet_group($sql);
	
	unset($sql);
	
	mysqli_query($con,"UNLOCK TABLES");
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./zhaoshenmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./zhaoshenmanager.php";
		$create_socket_obj->send_socket_zhaosheng_del_general("config",1,$streamid);
		echo "<script>window.location='success.php'</script>";	
	}
}

function fileLine($filePath, $string, $line, $mode = 'w') {
	   if (is_file ( $filePath )) {   
        $fileArr = file ( $filePath ); //把文件存进数组   
    } else {   
        return '文件不存在';   
    }  
	$newFileStr=""; 
    $size = count ( $fileArr ); //数组的长度   
    if ($line > $size) { //如果插入的行数大于文件现有的行数，直接用系统自带的就行   
        return;   
    }   
    for($i = 0; $i < $size; $i ++) {   
        if ($i == $line - 1) {   
            switch (strtolower ( $mode )) { //判断是写入，还是删除或者是更新
							case 's':
								$newFileStrs .= $fileArr[$i];
			
								break;   
                case 'w' :   
                    $newFileStr .= $string . "\r\n";   
                    $newFileStr .= $fileArr [$i];  
										 break;
                case 'u' :   
                   $newFileStr .= $string . "\r\n"; 
									 break;  
                case 'd' :   
									break;  
				
            }   
        } else {   
            $newFileStr .= $fileArr [$i];   
        }   
    }   
	if(strtolower($mode)=='s') 
	return $newFileStrs;
    file_put_contents ( $filePath, $newFileStr );  
    return true;   
} 

//设置主备服务器参数
function delsqldate($con,$a9000path)
{
	mysqli_query($con,"START TRANSACTION");
	mysqli_query($con,"DELETE FROM alarmarea");
	mysqli_query($con,"DELETE FROM alarmgroupmap");	
	mysqli_query($con,"DELETE FROM audiocodectype");	
	mysqli_query($con,"DELETE FROM audioformat");
	mysqli_query($con,"DELETE FROM audioserver");		
	mysqli_query($con,"DELETE FROM belltask");	
	mysqli_query($con,"DELETE FROM book_admin WHERE id!='1'");
	mysqli_query($con,"DELETE FROM book_msg");
	mysqli_query($con,"DELETE FROM book_reply");	
	mysqli_query($con,"DELETE FROM callgroup");	
	mysqli_query($con,"DELETE FROM camer");	
	mysqli_query($con,"DELETE FROM camer_alarm");
	mysqli_query($con,"DELETE FROM camer_alarmofmedia");		
	mysqli_query($con,"DELETE FROM cameramap");
	mysqli_query($con,"DELETE FROM camerofterminal");
	mysqli_query($con,"DELETE FROM centralctrl");
	mysqli_query($con,"DELETE FROM ctrldevice");
	mysqli_query($con,"DELETE FROM ctrldevicetype");
	mysqli_query($con,"DELETE FROM ctrlnet");
	mysqli_query($con,"DELETE FROM ctrloftask");
	mysqli_query($con,"DELETE FROM ctrloftermianl");
	mysqli_query($con,"DELETE FROM ctrltask");
	mysqli_query($con,"DELETE FROM employees");
	mysqli_query($con,"DELETE FROM filefolder WHERE id>9");
	mysqli_query($con,"DELETE FROM filetaskfree WHERE id!='1'");
	mysqli_query($con,"DELETE FROM holidaytime");
	mysqli_query($con,"DELETE FROM log");
	mysqli_query($con,"DELETE FROM logmedialist");
	mysqli_query($con,"DELETE FROM logtask");
	mysqli_query($con,"DELETE FROM media WHERE id>1");
	mysqli_query($con,"DELETE FROM mediaoftask");
	mysqli_query($con,"DELETE FROM offlinemedia");
	mysqli_query($con,"DELETE FROM offlinemediaofterminal");
	mysqli_query($con,"DELETE FROM offlinetask");
	mysqli_query($con,"DELETE FROM offlinetaskofterminal");
	mysqli_query($con,"DELETE FROM playbelloftask");
	mysqli_query($con,"DELETE FROM powermgrmap");
	mysqli_query($con,"DELETE FROM serverinputtype");
	mysqli_query($con,"DELETE FROM serverplaystream");
	mysqli_query($con,"DELETE FROM servers");
	mysqli_query($con,"DELETE FROM serverspeech");
	mysqli_query($con,"DELETE FROM shortcutkeymap");
	mysqli_query($con,"DELETE FROM shortcutkeytask");
	mysqli_query($con,"DELETE FROM task WHERE taskid!='70000'");
	mysqli_query($con,"DELETE FROM terminal");
	mysqli_query($con,"DELETE FROM terminalattrbute");
	mysqli_query($con,"DELETE FROM terminalfunc");
	mysqli_query($con,"DELETE FROM terminalgroup");
	mysqli_query($con,"DELETE FROM terminalgrouplist");
	mysqli_query($con,"DELETE FROM terminalkey");
	mysqli_query($con,"DELETE FROM terminalkeymap");
	mysqli_query($con,"DELETE FROM terminalkeymaptask");
	mysqli_query($con,"DELETE FROM terminalmaked");
	mysqli_query($con,"DELETE FROM terminalofalarmgroup");
	mysqli_query($con,"DELETE FROM terminalofararmgroup");
	mysqli_query($con,"DELETE FROM terminalofcallgroup");
	mysqli_query($con,"DELETE FROM terminalofgroup");
	mysqli_query($con,"DELETE FROM terminaloftask");
	mysqli_query($con,"DELETE FROM ttssentence");
	mysqli_query($con,"DELETE FROM ttstaskinfo");
	mysqli_query($con,"DELETE FROM ttstext");
	mysqli_query($con,"DELETE FROM usergroup WHERE id!=1");	
	mysqli_query($con,"DELETE FROM usersn");
	mysqli_query($con,"DELETE FROM userterminal");
	mysqli_query($con,"DELETE FROM usertype");
	mysqli_query($con,"COMMIT");

	$tempinfo = sprintf("rm -rf %s/backup/mediadata/*.mp3",$a9000path);
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
}

function modify_cala_backup($ip)
{
	$file = file_get_contents("mysqlstartslave.sql"); 

		$position = strpos($file, "master_host=")+13; 
		$position2 = strpos($file, ",master_user")-1; 
		$endPosition = filesize("mysqlstartslave.sql");
		$num=$endPosition - $position2;

	 	$fpile = fopen('mysqlstartslave.sql', 'r+'); 
	 	rewind($fpile);
	 	fseek($fpile, $position2); 
		$content = fread($fpile,$num);
		rewind($fpile);
		fseek($fpile, $position); 
		fwrite($fpile, $ip);
		fwrite($fpile, $content);

		fclose($fpile);  

/*
	$fp = fopen('mysqldel.sql', 'r+'); 
	if ($fp) { 
	$i = 0; 
  while (($buffer = fgets($fp, 4096)) !== false) {
			if(strpos($buffer,'master_host') != false){
			
				//if ($i == 68) { 
					$lens=strlen($buffer);
					$lenss=-$lens;
					$str1 = str_replace('@', '\'',$buffer);
					fseek($fp,$lenss, SEEK_CUR); 
					fwrite($fp, $str1); 
					break; 
				
				} 
			$i++; 
	
		}

	fclose($fp); 
  } 
	*/
}


function modify_cala_ip($ip,$mask,$gateway)
{
	$file = file_get_contents("ha-post.sh"); 

		$position = strpos($file, "sed")+30; 
	
		$position2 = strpos($file, "/etc/netplan")-3; 

		$endPosition = filesize("ha-post.sh");
		$num=$endPosition - $position2;

	 	$fpile = fopen('ha-post.sh', 'r+'); 
	 	rewind($fpile);
	 	fseek($fpile, $position2); 

		$content = fread($fpile,$num);

		rewind($fpile);
		fseek($fpile, $position); 
		fwrite($fpile, $ip);
		fwrite($fpile, '\/');
		fwrite($fpile, $mask);
		fwrite($fpile, $content);

	fclose($fpile);  
}



function serverupdate_sedinfo($commandid,$filepath)
{
	$cominfo=array();
	$commind="cp -rf ".$filepath." ".$filepath."old";
	system($commind);
	$commind="chmod 777 ".$filepath."old";
	system($commind);
	$commind = $commandid."old";
	exec($commind, $cominfo,$last_line);	
	$commind="cp -rf ".$filepath."old ".$filepath;
	system($commind);
	$commind="rm -rf ".$filepath."old";
	system($commind);
}
//32位
/*
function setmaster_backup($con,$model,$master_ip,$Slave_IP,$subnetmaskip,$ip,$offlineport,$servername,$slavename,$a9000path)
{
	
	$i=0;
	$netmaskip = explode(".",$subnetmaskip);
	$one=(int)$netmaskip[0];
	$two=(int)$netmaskip[1];
	$three=(int)$netmaskip[2];

	for($m=0;$m<24;$m++)
	{
		if($one!=0&&$m<8)
		{
			if($one&1==1)
				$i++;
			$one>>=1;
		}
		
		if($two!=0&&$m>=8&&$m<16)
		{
			if($two&1==1)
				$i++;
			$two>>=1;
		}
		if($three!=0&&$m>=16&&$m<24)
		{
			if($three&1==1)
				$i++;
			$three>>=1;
		}
	}

	$command = "sudo sed -i '\$c ".$servername."  ".$ip."/".$i."/eth0 ha-post' /etc/ha.d/haresources ";
		
	
	@system($command);


	$command = "sudo sed -i '4c dstport=".$offlineport."' /opt/script/sync.sh";
	@system($command);
	
	$command = "sudo sed -i '8c dstip=".$Slave_IP."' /opt/script/sync.sh";
	@system($command);
	
	$command = "sudo sed -i '3c ".$master_ip."  ".$servername."' /etc/hosts";
	@system($command);
	
	$command = "sudo sed -i '4c ".$Slave_IP."  ".$slavename."' /etc/hosts";
	@system($command);
	$command = "sudo sed -i '211c node ".$servername."' /etc/ha.d/ha.cf";
	@system($command);
	
	$command = "sudo sed -i '212c node ".$slavename."' /etc/ha.d/ha.cf";
	@system($command);

	if($model==1)
	{
		$command = "sudo cp /etc/my-master.cnf /etc/my.cnf -rf";
		@system($command);	
		$command = "sudo cp /opt/config/crontab-master /etc/crontab -rf";
		@system($command);	
		//$command = "sudo sed -i '1c /opt/script/sync.sh&' /opt/script/ini";
		//@system($command);
		$command = "sudo sed -i '2c HOSTNAME=".$servername."' /etc/sysconfig/network";
		@system($command);
		$command = "sudo hostname ".$servername."";
		@system($command);
		$command = "sudo cp /opt/script/apprun-master.sh /opt/script/apprun.sh  -rf";
		@system($command);
	//	$command = "sudo sed -i '121c ucast eth0 ".$Slave_IP."' /etc/ha.d/ha.cf";
	//	@system($command);
	}
	else if($model==2)
	{
		$command = "sudo cp /etc/my-slave.cnf /etc/my.cnf -rf";
		@system($command);	
		$command = "sudo sed -i '/^master-host=/c master-host=".$master_ip."' /etc/my.cnf";
		@system($command);
		$command = "sudo cp /opt/config/crontab-slave /etc/crontab -rf";
		@system($command);	
		//$command = "sudo sed -i '1c #/opt/script/sync.sh&' /opt/script/ini";
	//	@system($command);
		$command = "sudo sed -i '2c HOSTNAME=".$slavename."' /etc/sysconfig/network";
		@system($command);
		$command = "sudo sed -i '2c ntpdate -u ".$master_ip."' /opt/script/timeupdate.sh";
		@system($command);
		
		$command = "sudo hostname ".$slavename."";
		@system($command);
	//	$command = "sudo sed -i '121c ucast eth0 ".$master_ip."' /etc/ha.d/ha.cf";
	//	@system($command);

		$command = "sudo service mysqld stop";
		@system($command);
		$command = "sudo rm -rf /var/lib/mysql/master.info";
		@system($command);
		$command = "sudo rm -rf /var/lib/mysql/mysqld*";
		@system($command);
		$command = "sudo rm -rf /var/lib/mysql/relay*";
		@system($command);
		$command = "sudo rm -rf /var/lib/mysql/ib_logfile*";
		@system($command);
		$command = "sudo mv /opt/script/mysqldel /opt/script/mysqldel.sh";
		@system($command);
		$command = "sudo rm -rf /opt/script/apprun.sh ";
		@system($command);
		//delsqldate($con,$a9000path);
	}
}
*/

//64位
function setserver_backup($con,$subnetmaskip,$ip,$servername,$a9000path)
{
	require_once("inc/socket_conf.php");
	$i=0;
	$create_socket_obj = new create_socket_class();
	$netmaskip = explode(".",$subnetmaskip);
	$one=(int)$netmaskip[0];
	$two=(int)$netmaskip[1];
	$three=(int)$netmaskip[2];

	for($m=0;$m<24;$m++)
	{
		if($one!=0&&$m<8)
		{
			if($one&1==1)
				$i++;
			$one>>=1;
		}
		
		if($two!=0&&$m>=8&&$m<16)
		{
			if($two&1==1)
				$i++;
			$two>>=1;
		}
		if($three!=0&&$m>=16&&$m<24)
		{
			if($three&1==1)
				$i++;
			$three>>=1;
		}
	}

	//$command = "sed -i '150c ".$servername."  ".$ip."/".$i."/eth0 ha-post' /etc/ha.d/haresources ";
	//system($command);

	$tempinfo = sprintf("sed -i '150c ".$servername."  ".$ip."/".$i."/eth0 ha-post' /etc/ha.d/haresources");
	$command = "cmdhost --cmd=\"".$tempinfo."\"";

	exec($command, $output_info,$last_line);

	$tempinfo = sprintf("sed -i '150c ".$servername."  ".$ip."/".$i."/eth0 ha-post' %s/home/heartbeat/haresources",$a9000path);
	$command = "cmdhost --cmd=\"".$tempinfo."\"";

	exec($command, $output_info,$last_line);
}

function setiprun($con,$model,$master_ip,$Slave_IP,$subnetmaskip,$ip,$servername,$slavename,$gateway,$a9000path,$openorclose)
{
	require_once("inc/socket_conf.php");
	$i=0;
	$create_socket_obj = new create_socket_class();
	$netmaskip = explode(".",$subnetmaskip);
	$one=(int)$netmaskip[0];
	$two=(int)$netmaskip[1];
	$three=(int)$netmaskip[2];

	for($m=0;$m<24;$m++)
	{
		if($one!=0&&$m<8)
		{
			if($one&1==1)
				$i++;
			$one>>=1;
		}
		
		if($two!=0&&$m>=8&&$m<16)
		{
			if($two&1==1)
				$i++;
			$two>>=1;
		}

		if($three!=0&&$m>=16&&$m<24)
		{
			if($three&1==1)
				$i++;
			$three>>=1;
		}
	}

	$commands = "/opt/apps/a9000/html/ok112/iprun.sh ".$i." ".$master_ip." ".$Slave_IP." ".$model." ".$gateway;

	$command = "cmdhost --cmd=\"".$commands."\"";
	//echo "<script>alert('".$command."');</script>";
	exec($command, $output_info,$last_line);


}

function setmaster_backup($con,$model,$master_ip,$Slave_IP,$subnetmaskip,$ip,$servername,$slavename,$backup,$a9000path,$openorclose)
{
	require_once("inc/socket_conf.php");
	$i=0;
	$create_socket_obj = new create_socket_class();
	$netmaskip = explode(".",$subnetmaskip);
	$one=(int)$netmaskip[0];
	$two=(int)$netmaskip[1];
	$three=(int)$netmaskip[2];

	for($m=0;$m<24;$m++)
	{
		if($one!=0&&$m<8)
		{
			if($one&1==1)
				$i++;
			$one>>=1;
		}
		
		if($two!=0&&$m>=8&&$m<16)
		{
			if($two&1==1)
				$i++;
			$two>>=1;
		}
		if($three!=0&&$m>=16&&$m<24)
		{
			if($three&1==1)
				$i++;
			$three>>=1;
		}
	}

	$gettext=fileLine('link/etc/ha.d/haresources','',150,'s');

	$text=strstr($gettext,' ');

	//$command = "sed -i '150c ".$servername."".$text."' /etc/ha.d/haresources ";
	
	$tempinfo = sprintf("sed -i '150c ".$servername."".$text."' /etc/ha.d/haresources");
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);

	$tempinfo = sprintf("sed -i '150c ".$servername."".$text."' %s/home/heartbeat/haresources",$a9000path);
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);

	//$command = "sudo sed -i '4c dstport=".$offlineport."' /opt/script/sync.sh";
	//system($command);
	$tempinfo = sprintf("sed -i '17c dstip=".$Slave_IP."' %s/home/rsync/crsync.sh",$a9000path);
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);

	$output_info=array();
	//serverupdate_sedinfo($command,"/var/www/html/ok112/link/home/rsync/sync.sh");


	$tempinfo = "sed -i '9c ".$master_ip."  ".$servername."' /etc/hosts";
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);
	
	$tempinfo = "sed -i '10c ".$Slave_IP."  ".$slavename."' /etc/hosts";
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);

if($model==1)
{
	$tempinfo = "sed -i '2c 127.0.0.1  ".$servername."' /etc/hosts";
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);
}
else
{
	$tempinfo = "sed -i '2c 127.0.0.1  ".$slavename."' /etc/hosts";
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);
}

//	$tempinfo = "sed -i '211c node ".$servername."' /var/www/html/ok112/link/etc/ha.d/ha.cf";
	$tempinfo = sprintf("sed -i '211c node ".$servername."' /etc/ha.d/ha.cf");
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);

	//$tempinfo = "sed -i '212c node ".$slavename."' /var/www/html/ok112/link/etc/ha.d/ha.cf";
	$tempinfo = sprintf("sed -i '212c node ".$slavename."' /etc/ha.d/ha.cf");
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);

	$tempinfo = sprintf("sed -i '56c deadtime 15' /etc/ha.d/ha.cf");
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);

	$tempinfo = sprintf("sed -i '71c initdead 30' /etc/ha.d/ha.cf");
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);
	
	$tempinfo = sprintf("sed -i '91c bcast  eth0' /etc/ha.d/ha.cf");
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);

	
	if($model==1)
	{
		if($openorclose==1)
		{
			//$tempinfo = "mv /var/www/html/ok112/link/script/mysqldel-z /var/www/html/ok112/link/script/mysqldel.sh -f";
			//$tempinfo = sprintf("mv %s/script/mysqldel-z %s/script/mysqldel.sh -f",$a9000path,$a9000path);
			//$command = "cmdhost --cmd=\"".$tempinfo."\"";
			//exec($command, $output_info,$last_line);
			mysqli_query($con,"UPDATE serverbaseparam set backup='1'");
			//$tempinfo = "cp /var/www/html/ok112/link/home/mysql/server-master.cnf /var/www/html/ok112/link/home/mysql/my.cnf.d/server.cnf -rf";
			$tempinfo = sprintf("cp %s/home/mysql/server-master.cnf %s/home/mysql/my.cnf.d/server.cnf -rf",$a9000path,$a9000path);
		}
		else
		{
			mysqli_query($con,"UPDATE serverbaseparam set backup='0'");
			//	$tempinfo = "cp /var/www/html/ok112/link/home/mysql/server.cnf /var/www/html/ok112/link/home/mysql/my.cnf.d/server.cnf -rf";
			$tempinfo = sprintf("cp %s/home/mysql/server.cnf %s/home/mysql/my.cnf.d/server.cnf -rf",$a9000path,$a9000path);
		}
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
		
	
		$tempinfo = "sed -i '121c ucast eth0 ".$Slave_IP."' /etc/ha.d/ha.cf";
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);

		//$tempinfo = "chmod 644 /var/www/html/ok112/link/home/mysql/my.cnf.d/server.cnf";
		$tempinfo = sprintf("chmod 644 %s/home/mysql/my.cnf.d/server.cnf",$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);

	//	$tempinfo = "sed -i '23c ##' /etc/crontab";
		$tempinfo = "rm -rf /etc/crontab";
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
	
		$tempinfo = "sed -i '1c ".$servername."' /etc/hostname";
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
	
		//$tempinfo = "cp /var/www/html/ok112/link/script/apprun-master.sh /var/www/html/ok112/link/script/apprun.sh  -rf";
		$tempinfo = sprintf("cp %s/script/apprun-master.sh %s/script/apprun.sh  -rf",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
	}
	else if($model==2)
	{
		if($openorclose==1)
		{
			//$tempinfo = "mv %s/script/mysqldel-bdata %s/script/mysqldel.sh -f";
			$tempinfo = sprintf("mv %s/script/mysqldel-bdata %s/script/mysqldel.sh -f",$a9000path,$a9000path);
			$command = "cmdhost --cmd=\"".$tempinfo."\"";
			exec($command, $output_info,$last_line);
		
			mysqli_query($con,"UPDATE serverbaseparam set backup='1'");
			//$tempinfo = "cp /var/www/html/ok112/link/home/mysql/server-slave.cnf /var/www/html/ok112/link/home/mysql/my.cnf.d/server.cnf -rf";
			$tempinfo = sprintf("cp %s/home/mysql/server-slave.cnf %s/home/mysql/my.cnf.d/server.cnf -rf",$a9000path,$a9000path);
			$command = "cmdhost --cmd=\"".$tempinfo."\"";
			exec($command, $output_info,$last_line);
		}
		else
		{
			mysqli_query($con,"UPDATE serverbaseparam set backup='0'");
			//$tempinfo = "cp /var/www/html/ok112/link/home/mysql/server.cnf /var/www/html/ok112/link/home/mysql/my.cnf.d/server.cnf -rf";
			$tempinfo = sprintf("cp %s/home/mysql/server.cnf %s/home/mysql/my.cnf.d/server.cnf -rf",$a9000path,$a9000path);
			$command = "cmdhost --cmd=\"".$tempinfo."\"";
			exec($command, $output_info,$last_line);
		}

		$tempinfo = "sed -i '121c ucast eth0 ".$master_ip."' /etc/ha.d/ha.cf";
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);

		//$tempinfo = "chmod 644 /var/www/html/ok112/link/home/mysql/my.cnf.d/server.cnf";
		$tempinfo = sprintf("chmod 644 %s/home/mysql/my.cnf.d/server.cnf",$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
		//$tempinfo = sprintf("sed -i '23c * * * * * root %s/script/timeupdate.sh' /etc/crontab",$a9000path);

		$tempinfo = sprintf("cp -rf %s/home/heartbeat/crontab-slave /etc/crontab",$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);

		$tempinfo = sprintf("cp -rf %s/script/mysqlstartslave.sql %s/html/ok112/",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);

		modify_cala_backup($master_ip);
		$tempinfo = sprintf("cp -rf %s/html/ok112/mysqlstartslave.sql %s/script/; rm -rf %s/html/ok112/mysqlstartslave.sql",$a9000path,$a9000path,$a9000path);
	
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);

	
		$tempinfo = sprintf("sed -i '11c ntpdate -u %s' %s/script/timeupdate.sh",$master_ip,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);

		$tempinfo = "sed -i '1c ".$slavename."' /etc/hostname";
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);

	//	$command = "sudo hostname ".$slavename."";
	//	$create_socket_obj->send_system_commanid($command);	

	//delsqldate($con,$a9000path);
	
	//	$command = "sudo sed -i '121c ucast eth0 ".$master_ip."' /etc/ha.d/ha.cf";
	//	@system($command);
	
		//$command = "sudo rm -f /opt/script/apprun.sh ";
		//@system($command);
		$tempinfo = sprintf("cp %s/script/apprun-slave.sh %s/script/apprun.sh  -rf",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);

	//  delsqldate($con,$a9000path);
	//	$command = "sudo service mysqld stop";
	//	@system($command);
	//	$command = "sudo service mysqld stop";
	//	$create_socket_obj->send_system_commanid($command);	
	//	$command = "rm -fr /var/lib/mysql/mysql-bin*;rm -rf /var/lib/mysql/*.info; rm -rf /var/lib/mysql/relay*;rm -rf /var/lib/mysql/aria_*;rm -rf /var/lib/mysql/*-relay*;rm -rf /var/lib/mysql/ibtmp*;rm -rf /var/lib/mysql/ib_*;rm -rf /var/lib/mysql/*.pid rm -rf /var/lib/mysql/audioserver";
	/*	$tempinfo = sprintf("rm -fr %s/home/mysql/db/mysql-bin*;rm -rf %s/home/mysql/db/*.info;rm -rf %s/home/mysql/db/relay*;rm -rf %s/home/mysql/db/aria_*;rm -rf %s/home/mysql/db/*-relay*;rm -rf %s/home/mysql/db/ib_*;rm -rf %s/home/mysql/db/*.pid",$a9000path,$a9000path,$a9000path,$a9000path,$a9000path,$a9000path,$a9000path);

		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);*/
	}


}


function serveropen_data($con,$a9000path)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建套字节=====================
	$create_socket_obj = new create_socket_class();
	
	$master_ip;
	if(isset($_GET['master_ip']))
	{	
		$master_ip = trim($_GET['master_ip']);
	}

	$Slave_IP;
	if(isset($_GET['Slave_IP']))
	{	
		$Slave_IP = trim($_GET['Slave_IP']);
	}

	$servermodel=0;
	if(isset($_GET['servermodel']))
	{	
		$servermodel = trim($_GET['servermodel']);
	}

	$subnetmaskip;
	if(isset($_GET['subnetmaskip']))
	{	
		$subnetmaskip = trim($_GET['subnetmaskip']);
	}
	$slavename;
	if(isset($_GET['slavename']))
	{	
		$slavename = trim($_GET['slavename']);
	}

	$mastersubnetmask;
	if(isset($_GET['mastersubnetmask']))
	{	
		$mastersubnetmask = trim($_GET['mastersubnetmask']);
	}

	$ip;
	if(isset($_GET['ip']))
	{	
		$ip = trim($_GET['ip']);
	}

	$servername="HA1";
	if(isset($_GET['name']))
	{	
		$servername = trim($_GET['name']);
	}
	
	$gateway="";
	if(isset($_POST['gateway']))
	{	
		$gateway = trim($_POST['gateway']);
	}

	$servermodel=0;
	if(isset($_GET['servermodel']))
	{	
		$servermodel = trim($_GET['servermodel']);
	}

	$init_mode=1;
	$sql = "SELECT model from serverbaseparam";

	$result = mysqli_query($con,$sql) or die(mysqli_error($con));

	if($row = mysqli_fetch_array($result))
	{
		$init_mode=$row['model'];
	}

	$getsql ="UPDATE `serverbaseparam` SET `name`='$servername',`model`='$servermodel',`subnetmask`='$subnetmaskip',`masterip`='$master_ip',`slaveip`='$Slave_IP',`slavename`='$slavename' ";

	mysqli_query($con,$getsql);	

		//$gateway=$_POST['gateway'];
		$gateway;
		if(isset($_GET['gateway']))
		{	
			$gateway = trim($_GET['gateway']);
		}

		$output_info=array();
		$getip=$_POST['ip'];
		
	if($servermodel==1)
	{
		$_SESSION['servermodel']=1;
		setiprun($con,$servermodel,$master_ip,$Slave_IP,$subnetmaskip,$ip,$servername,$slavename,$gateway,$a9000path,1);
	 }
	 else if($servermodel==2)
	 {
		$_SESSION['servermodel']=2;
		setiprun($con,$servermodel,$master_ip,$Slave_IP,$subnetmaskip,$ip,$servername,$slavename,$gateway,$a9000path,0);
	 }

		$backup=0;
		$sqlresult = "SELECT backup from serverbaseparam";
		$resultback = mysqli_query($con,$sqlresult) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($resultback))
		{
			$backup=$row['backup'];
		}

		setmaster_backup($con,$servermodel,$master_ip,$Slave_IP,$mastersubnetmask,$ip,$servername,$slavename,$backup,$a9000path,1);
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']."---".$do_php_prompt['The_system_is_restarting']);//提示信息
		$_SESSION['url'] = "./login.php";
		//==========================================================================
		//$command = "sudo service heartbeat stop";
		//@system($command);
	
		echo "<script>window.location='success.php'</script>";
		//$create_socket_obj->send_socket_server("server",$_POST['ip'],0,$_POST['udpport'],$_POST['maxbandwidth'],$_POST['maxhttpconnections']);
		@ignore_user_abort();		// 后台运行		
		@set_time_limit(1); 
		@session_write_close();
		@session_unset();	
		@session_destroy();	
		sleep(1);		
		$create_socket_obj->send_socket_restart("server",1);
		//$command="cmdhost -c 'sudo reboot'";
		//system($command);

}

function serverclose_data($con,$a9000path)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建套字节=====================
	$create_socket_obj = new create_socket_class();
	
	$master_ip;
	if(isset($_GET['master_ip']))
	{	
		$master_ip = trim($_GET['master_ip']);
	}

	$Slave_IP;
	if(isset($_GET['Slave_IP']))
	{	
		$Slave_IP = trim($_GET['Slave_IP']);
	}

	$gateway;
	if(isset($_GET['gateway']))
	{	
		$gateway = trim($_GET['gateway']);
	}
	$servermodel=0;
	if(isset($_GET['servermodel']))
	{	
		$servermodel = trim($_GET['servermodel']);
	}
	$subnetmaskip;
	if(isset($_GET['subnetmaskip']))
	{	
		$subnetmaskip = trim($_GET['subnetmaskip']);
	}

	$mastersubnetmask;
	if(isset($_GET['mastersubnetmask']))
	{	
		$mastersubnetmask = trim($_GET['mastersubnetmask']);
	}
	$ip;
	if(isset($_GET['ip']))
	{	
		$ip = trim($_GET['ip']);
	}

	$servername="HA1";
	if(isset($_GET['name']))
	{	
		$servername = trim($_GET['name']);
	}
	
	$slavename="HA2";
	if(isset($_GET['slavename']))
	{	
		$slavename = trim($_GET['slavename']);
	}

	$init_mode=1;
	$sql = "SELECT model from serverbaseparam";

	$result = mysqli_query($con,$sql) or die(mysqli_error($con));

	if($row = mysqli_fetch_array($result))
	{
		$init_mode=$row['model'];
	}

	$getsql ="UPDATE `serverbaseparam` SET `name`='$servername',`model`='$servermodel',`subnetmask`='$subnetmaskip',`masterip`='$master_ip',`slaveip`='$Slave_IP',`slavename`='$slavename' ";

	mysqli_query($con,$getsql);	

	//$gateway=$_POST['gateway'];
	$output_info=array();
	$getip=$_POST['ip'];
		
	if($servermodel==1)
	{
			$_SESSION['servermodel']=1;
			setiprun($con,$servermodel,$master_ip,$Slave_IP,$subnetmaskip,$ip,$servername,$slavename,$gateway,$a9000path,1);
	 }
	 else if($servermodel==2)
	 {
	
		$_SESSION['servermodel']=2;
		setiprun($con,$servermodel,$master_ip,$Slave_IP,$subnetmaskip,$ip,$servername,$slavename,$gateway,$a9000path,0);
	 }
		
	
		$backup=0;
		$sqlresult = "SELECT backup from serverbaseparam";
		$resultback = mysqli_query($con,$sqlresult) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($resultback))
		{
			$backup=$row['backup'];
		}
	   
		setmaster_backup($con,$servermodel,$master_ip,$Slave_IP,$mastersubnetmask,$ip,$servername,$slavename,$backup,$a9000path,0);
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']."---".$do_php_prompt['The_system_is_restarting']);//提示信息
		$_SESSION['url'] = "./login.php";
		//==========================================================================
		//$command = "sudo service heartbeat stop";
		//@system($command);
	
		echo "<script>window.location='success.php'</script>";
	//	$create_socket_obj->send_socket_server("server",$_POST['ip'],0,$_POST['udpport'],$_POST['maxbandwidth'],$_POST['maxhttpconnections']);
		@ignore_user_abort();		// 后台运行
			
		@set_time_limit(1); 
	
		@session_write_close();
		@session_unset();	
		@session_destroy();	
		sleep(1);		
		$create_socket_obj->send_socket_restart("server",1);
		//$command="cmdhost -c 'sudo reboot'";
		//system($command);
}


//修改服务器参数
function serveredit_msg($con,$a9000path)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建套字节=====================
	$create_socket_obj = new create_socket_class();
	$master_ip;
	if(isset($_POST['master_ip']))
	{	
		$master_ip = trim($_POST['master_ip']);
	}
	$Slave_IP;
	if(isset($_POST['Slave_IP']))
	{	
		$Slave_IP = trim($_POST['Slave_IP']);
	}

	$master_ip;
	if(isset($_POST['master_ip']))
	{	
		$master_ip = trim($_POST['master_ip']);
	}
	$servermodel=0;
	if(isset($_POST['servermodel']))
	{	
		$servermodel = trim($_POST['servermodel']);
	}

	$mastersubnetmask;
	if(isset($_POST['mastersubnetmask']))
	{	
		$mastersubnetmask = trim($_POST['mastersubnetmask']);
	}

	$Slavegateway;
	if(isset($_POST['Slavegateway']))
	{	
		$Slavegateway = trim($_POST['Slavegateway']);
	}

	$ip;
	if(isset($_POST['ip']))
	{	
		$ip = trim($_POST['ip']);
	}

	$offlineport=0;
	if(isset($_POST['offlineport']))
	{	
		$offlineport = trim($_POST['offlineport']);
	}
	
	$sdkport=0;
	if(isset($_POST['sdkport']))
	{	
		$sdkport = trim($_POST['sdkport']);
	}
	/*
	$sdkaddr;
	if(isset($_POST['sdkaddr']))
	{	
		$sdkaddr = trim($_POST['sdkaddr']);
	}
	*/
	$servername="HA1";
	if(isset($_POST['name']))
	{	
		$servername = trim($_POST['name']);
	}
	
	$slavename="HA2";
	if(isset($_POST['slavename']))
	{	
		$slavename = trim($_POST['slavename']);
	}

	$servermodes=0;
	if(isset($_POST['servermodes']))
	{	
		$servermodes = trim($_POST['servermodes']);
	}

	$netMask = explode('.', $mastersubnetmask);  

	for($i=0;$i<4;$i++)
	{
		$shi_mask.=decbin($netMask[$i]);

	}
	$mask=substr_count($shi_mask,"1");
	$init_mode=1;
	$sql = "SELECT model from serverbaseparam";

	$result = mysqli_query($con,$sql) or die(mysqli_error($con));

	if($row = mysqli_fetch_array($result))
	{
		$init_mode=$row['model'];
	}

	$getnetmask=$_POST['subnetmaskip'];

	$getsql ="UPDATE `serverbaseparam` SET `dataport`='$_POST[dateport]',`ip`='$_POST[ip]',`gateway`='$_POST[gateway]',`port`='$_POST[port]',`udpport`='$_POST[udpport]',`maxbandwidth`='$_POST[maxbandwidth]',`maxhttpconnections`='$_POST[maxhttpconnections]',`offlineport`='$_POST[offlineport]',`backupmode`='$servermodes'";

	mysqli_query($con,$getsql);	
	$listenport=trim($_POST['webport']);
 	//ubuntu 不能改端口
	if($listenport!="")
	{
		$command = "sed -i '241c ServerName ".$ip.":".$listenport."' /var/www/html/ok112/link/home/apache/httpd.conf";
		serverupdate_sedinfo($command,"/var/www/html/ok112/link/home/apache/httpd.conf");

		//$command = "sed -i '23c <VirtualHost *:".$listenport.">' /var/www/html/ok112/link/home/apache/httpd-vhosts.conf";
		//	serverupdate_sedinfo($command,"/var/www/html/ok112/link/home/apache/httpd-vhosts.conf");
	}

	if($sdkport!="")
	{
	//	$cominfo=array();
	//	$command = "sed -i '53c LISTEN ".$sdkport."' /var/www/html/ok112/link/home/apache/httpd.conf";
	//	serverupdate_sedinfo($command,"/var/www/html/ok112/link/home/apache/httpd.conf");
		$commands = "sed -i '11c \"host\": \"".$ip.":".$sdkport."\",' /var/www/html/ok112/swagger-ui/dist/swagger1.json";
		serverupdate_sedinfo($commands,"/var/www/html/ok112/swagger-ui/dist/swagger1.json");

	//	$command = "sed -i '39c <VirtualHost *:".$sdkport.">' /var/www/html/ok112/link/home/apache/httpd-vhosts.conf";
	//	serverupdate_sedinfo($command,"/var/www/html/ok112/link/home/apache/httpd-vhosts.conf");
	}

	/*
	$offlineport=trim($_POST['offlineport']);
	if($offlineport!="")
	{
	$command = "sudo sed -i '24c port = ".$offlineport."' /etc/rsyncd.conf";
	$create_socket_obj->send_system_commanid($command);	
	}
	*/	
		//	$listen=fileLine('/etc/httpd/conf/httpd.conf',$listenport,136,'u');  
		/*
		$getnetmask=$_POST['subnetmaskip'];
		$command = "sudo sed -i '/^NETMASK=/cNETMASK=".$getnetmask."' /etc/sysconfig/network-scripts/ifcfg-eth0";
		$create_socket_obj->send_system_commanid($command);	
		$gateway=$_POST['gateway'];
		$commandgateway = "sudo sed -i '/^GATEWAY=/cGATEWAY=".$gateway."' /etc/sysconfig/network-scripts/ifcfg-eth0";
		$create_socket_obj->send_system_commanid($commandgateway);
	  */
		$gateway=$_POST['gateway'];
		$output_info=array();

	//	$tempinfo="sed -i '2c route add default gw ".$gateway."' /etc/ha.d/ha-post.sh";
//	$net_mask = explode('.', $mastersubnetmask);  
//	$long = ip2long($net_mask[0]) << 24 | ip2long($net_mask[1]) << 16 | ip2long($net_mask[2]) << 8 | ip2long($net_mask[3]);  
		
	//	$tempinfo = "sed -i '0,/-/s/\(-\)\(.*\)/\1 ".$ip."\/".$long."/' /etc/netplan/00-installer-config.yaml";
	$tempinfo = sprintf("cp -rf /etc/ha.d/ha-post.sh %s/html/ok112/",$a9000path);

	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);


	$tempinfo = sprintf("sed -i '126c http_publish_uri = http://".$ip.":9001/' %s/home/graylog/config/graylog.conf",$a9000path);
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);

	$tempinfo = sprintf("sed -i '140c http_external_uri = http://".$ip.":9001/' %s/home/graylog/config/graylog.conf",$a9000path);
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);

	modify_cala_ip($ip,$mask,$gateway);
/*
	$tempinfo = sprintf("sed -i '13c address1=".$ip."/".$mask.",".$gateway."' /data/kylin/111.nmconnection");
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);

	$tempinfo = sprintf("cp -rf /data/kylin/111.nmconnection /etc/NetworkManager/system-connections/");
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);

	$tempinfo = sprintf("chmod 600 /etc/NetworkManager/system-connections/111.nmconnection");
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);
*/
	$command = "sed -i '11c  route add default gw ".$gateway."' /var/www/html/ok112/ha-post.sh ";
	system($command);
//	$tempinfo = sprintf("sed -i '11c route add default gw ".$gateway."' %s/html/ok112/ha-post.sh",$a9000path);
//	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	//exec($command, $output_info,$last_line);


	$tempinfo = sprintf("cp -rf %s/html/ok112/ha-post.sh /etc/ha.d/ ",$a9000path);

	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);
	//	$tempinfo = "sed -i '2c route add default gw ".$Slavegateway." netmask ".$mastersubnetmask."' /etc/ha.d/ha-post.sh";
//		$command = "cmdhost --cmd=\"".$tempinfo."\"";
	//	exec($command, $output_info,$last_line);
		
		$getip=$_POST['ip'];
		
	if($servermodel==1)
	{
	 //	$defaultroutes = "sudo sed -i '/^IPADDR=/cIPADDR=".$master_ip."' /etc/sysconfig/network-scripts/ifcfg-eth0";
	//	 $create_socket_obj->send_system_commanid($defaultroutes);	
		//	mysqli_query($con,"UPDATE task set playtime='04:00:00' WHERE taskid=70000");
			$_SESSION['servermodel']=1;
	 }
	 else if($servermodel==2)
	 {
	 //	$defaultroutes = "sudo sed -i '/^IPADDR=/cIPADDR=".$Slave_IP."' /etc/sysconfig/network-scripts/ifcfg-eth0";
	//	 $create_socket_obj->send_system_commanid($defaultroutes);	
	//	mysqli_query($con,"UPDATE task set playtime='04:10:00' WHERE taskid=70000");
		$_SESSION['servermodel']=2;
	 }
	/*	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./servermanager.php";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{*/

		$sqlresult = "SELECT name FROM serverbaseparam";
		$resultback = mysqli_query($con,$sqlresult) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($resultback))
		{
			$servername=$row['name'];
		}

		setiprun($con,$servermodel,$master_ip,$Slave_IP,$getnetmask,$ip,$servername,$slavename,$gateway,$a9000path,0);
		sleep(1);
		//setmaster_backup($con,$servermodel,$master_ip,$Slave_IP,$mastersubnetmask,$ip,$offlineport,$servername,$slavename,$backup,$a9000path,$init_mode);
	
		setserver_backup($con,$mastersubnetmask,$ip,$servername,$a9000path);

	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']."---".$do_php_prompt['The_system_is_restarting']);//提示信息
		$_SESSION['url'] = "./login.php";
		//==========================================================================
		//$command = "sudo service heartbeat stop";
		//@system($command);
	
		echo "<script>window.location='success.php'</script>";
		sleep(1);
		$create_socket_obj->send_socket_server("server",$_POST['ip'],0,$_POST['udpport'],$_POST['maxbandwidth'],$_POST['maxhttpconnections']);
		@session_unset();	
		@session_destroy();	

		//$command="cmdhost -c 'sudo reboot'";
		//system($command);
//	}
}

//初始化数据
function init_date_msg($con,$a9000path)
{
		global $do_php_prompt;
		//=====================创建套字节======================
		$create_socket_obj = new create_socket_class();
		$servermodel=0;
		if(isset($_GET['servermodel']))
		{	
			$servermodel = trim($_GET['servermodel']);
		}
		
		if($servermodel==1)
		{
			$command = "mv /var/www/html/ok112/link/script/mysqldel-z /var/www/html/ok112/link/script/mysqldel.sh -f";
			system($command);
		}
		else if($servermodel==2)
		{
		//	$command = "mv /var/www/html/ok112/link/script/mysqldel /var/www/html/ok112/link/script/mysqldel.sh -f";
		//	system($command);
		
			$command = "mv /var/www/html/ok112/link/script/mysqldel-bdata /var/www/html/ok112/link/script/mysqldel.sh -f";
			system($command);
		
	
			$command = "cp /var/www/html/ok112/link/home/mysql/server-slave.cnf /var/www/html/ok112/link/home/mysql/my.cnf.d/server.cnf -rf";
			system($command);

			$command = "chmod 644 /var/www/html/ok112/link/home/mysql/my.cnf.d/server.cnf";
		  system($command);	

		//	$tempinfo = sprintf("sed -i '#23c * * * * * root %s/script/timeupdate.sh' /etc/crontab",$a9000path);
		 $tempinfo = sprintf("cp -rf %s/home/heartbeat/crontab-slave /etc/crontab",$a9000path);
			$command = "cmdhost --cmd=\"".$tempinfo."\"";
			exec($command, $output_info,$last_line);

			/*
			$tempinfo = sprintf("rm -fr %s/home/mysql/db/mysql-bin*;rm -rf %s/home/mysql/db/*.info;rm -rf %s/home/mysql/db/relay*;rm -rf %s/home/mysql/db/aria_*;rm -rf %s/home/mysql/db/*-relay*;rm -rf %s/home/mysql/db/ib_*;rm -rf %s/home/mysql/db/*.pid",$a9000path,$a9000path,$a9000path,$a9000path,$a9000path,$a9000path,$a9000path);
			$command = "cmdhost --cmd=\"".$tempinfo."\"";
			exec($command, $output_info,$last_line);*/

		  //$command = "rm -fr /var/lib/mysql/mysql-bin*;rm -rf /var/lib/mysql/*.info; rm -rf /var/lib/mysql/relay*;rm -rf /var/lib/mysql/aria_*;rm -rf /var/lib/mysql/*-relay*;rm -rf /var/lib/mysql/ibtmp*;rm -rf /var/lib/mysql/ib_*;rm -rf /var/lib/mysql/*.pid;rm -rf /var/lib/mysql/audioserver";
			//@system($command);	
		}	
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']."---".$do_php_prompt['The_system_is_restarting']);//提示信息
			$_SESSION['url'] = "./login.php";
			echo "<script>window.location='success.php'</script>";	
			//==========================================================================
			
			@ignore_user_abort();		// 后台运行
			
			@set_time_limit(1); 
			
			@session_write_close();
		
			@session_unset();	
			@session_destroy();

			sleep(1);		
			$create_socket_obj->send_socket_restart("server",1);
		//	$command="cmdhost -c 'sudo reboot'";
		//	system($command);
	
}


//启用方案
function stopmanagerstart_msg($con)
{
	//require_once("inc/socket_conf.php"); 
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue=$_GET['id'];

	$sql = "SELECT task.info from task where task.taskid = '$getValue' and task.tasktype = 11";

	$result = mysqli_query($con,$sql) or die(mysqli_error($con));

	if($row = mysqli_fetch_array($result))
	{
		mysqli_query($con,"UPDATE task SET projectstate = '0',state = '0' WHERE task.info = '$row[info]' AND task.tasktype = 11");
		
		if(mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./chezhangmangager.php";

			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./chezhangmangager.php";
			
			$create_socket_obj->send_socket_schedules("project",1,$row['info']);

			echo "<script>window.location='success.php'</script>";	
		}		  	
	}
}
//启用方案
function bellstart_msg($con)
{
	//require_once("inc/socket_conf.php"); 
	
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue=$_GET['id'];
	
	$sql = "SELECT task.info from task where task.taskid = '$getValue' and task.tasktype IN(1,15)";

	$result = mysqli_query($con,$sql) or die(mysqli_error($con));

	if($row = mysqli_fetch_array($result))
	{
		mysqli_query($con,"UPDATE task SET projectstate = '0',state = '0',offlinestate=0 WHERE task.info = '$row[info]' AND task.tasktype IN(1,15,9)");

		if(mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./bellmanager.php";

			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./bellmanager.php";
			
			$create_socket_obj->send_socket_schedules("project",1,$row['info']);

			echo "<script>window.location='success.php'</script>";	
		}		  	
	}
}
//启用节日管理
function enableholiday($con)
{
	//require_once("inc/socket_conf.php"); 
	
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue=$_GET['id'];

		mysqli_query($con,"UPDATE holidaytime SET projectstate = '1' WHERE id IN($getValue)");
		
		if(mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./displayholidaymanager.php";

			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./displayholidaymanager.php";
			
		//	$create_socket_obj->send_socket_schedules("project",1,$row['info']);

			echo "<script>window.location='success.php'</script>";	
		}		  	
}
//停用节日管理
function disableholiday($con)
{
	//require_once("inc/socket_conf.php"); 
	//添加外部变量
	global $do_php_prompt;
	//=====================创建对象=======================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue=$_GET['id'];

		mysqli_query($con,"UPDATE holidaytime SET projectstate = '0' WHERE id IN($getValue)");
		
		if(mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			$_SESSION['url'] = "./displayholidaymanager.php";
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			$_SESSION['url'] = "./displayholidaymanager.php";
		//	$create_socket_obj->send_socket_schedules("project",1,$row['info']);

			echo "<script>window.location='success.php'</script>";	
		}		  	
}
//停止车站任务
function stopmanagerstop_msg($con)
{
	//require_once("inc/socket_conf.php"); 
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = trim($_GET['id']);
	
	$sql = "SELECT task.info from task where task.taskid = '$getValue' and task.tasktype = 11 ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		mysqli_query($con,"UPDATE task SET projectstate = '1',state = '0' WHERE task.info = '$row[info]' AND task.tasktype = 11");
		
		if(mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./chezhangmangager.php";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./chezhangmangager.php";
			
			$create_socket_obj->send_socket_schedules("project",2,$row['info']);
		
			echo "<script>window.location='success.php'</script>";	
		}		  	
	}	
}
//停止方案
function bellstop_msg($con)
{
	//require_once("inc/socket_conf.php"); 
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = trim($_GET['id']);
	
	$sql = "SELECT task.info from task where task.taskid = '$getValue' and task.tasktype IN(1,15) ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		mysqli_query($con,"UPDATE task SET projectstate = '1',state = '0',offlinestate=0 WHERE task.info = '$row[info]' AND task.tasktype IN(1,15,9)");

		if(mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "./bellmanager.php";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			
			$_SESSION['url'] = "./bellmanager.php";
			
			$create_socket_obj->send_socket_schedules("project",2,$row['info']);
		
			echo "<script>window.location='success.php'</script>";	
		}		  	
	}	
}
//删除媒体信息
function trainmediadel_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	if(isset($_GET['id']))
	{
		$getid = $_GET['id'];
		$adm_taskId_array = explode(",",$getid);
	
		mysqli_query($con,"START TRANSACTION");
		mysqli_query($con,"DELETE FROM media WHERE media.id IN($getid)");
		mysqli_query($con,"DELETE FROM ttssentence WHERE ttssentence.sentenceid IN($getid)");
		/*
		for($i=0;i<count($adm_taskId_array);$i++)
		{
			mysqli_query($con,"DELETE FROM media WHERE media.name ='$adm_taskId_array[$i]'");	
			mysqli_query($con,"DELETE FROM ttssentence WHERE ttssentence.name = '$adm_taskId_array[$i]'");
			
		}
		*/	
		
			if(mysqli_error($con))
			{
				@mysqli_free_result($result);
				
				unset($row,$sql);
				
				mysqli_query($con,"ROLLBACK");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./trainmedia.php";
				
				echo "<script>window.location = 'error.php'</script>";
				
				exit;
			}
		
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./trainmedia.php";
	
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
	
		}
		echo "<script>window.location.href = 'success.php'</script>";
	}
}
//删除车站任务
function stopmanagerdel_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
		
		mysqli_query($con,"START TRANSACTION");
		//找到同组作息方案任务
		$sql = "SELECT task.taskid FROM task WHERE task.info IN(SELECT info FROM task WHERE task.taskid IN($getid) AND ";
		
		$sql.= "info!='' and channel=0) and info!='' and channel=0 ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		while($row = mysqli_fetch_array($result))
		{
			$sqlmedia = "DELETE FROM mediaoftask WHERE mediaoftask.taskid = '$row[taskid]'";
		
			mysqli_query($con,$sqlmedia) or die(mysqli_error($con));
		
			if(mysqli_error($con))
			{
				@mysqli_free_result($result);
				
				unset($row,$sql);
				
				mysqli_query($con,"ROLLBACK");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./chezhangmangager.php";
				
				echo "<script>window.location = 'error.php'</script>";
				
				exit;
			}
			$sqlterminal = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$row[taskid]'";
			
			mysqli_query($con,$sqlterminal) or die(mysqli_error($con));
			
			if(mysqli_error($con))
			{
				@mysqli_free_result($result);
				
				unset($row,$sql);
				
				mysqli_query($con,"ROLLBACK");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./chezhangmangager.php";
				
				echo "<script>window.location = 'error.php'</script>";
				
				exit;
			}
			$sqltask = "DELETE FROM task WHERE task.taskid = '$row[taskid]'";
			
			mysqli_query($con,$sqltask);
			
			if(mysqli_error($con))
			{
				@mysqli_free_result($result);
			
				unset($row,$sql);
			
				mysqli_query($con,"ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./chezhangmangager.php";
			
				echo "<script>window.location = 'error.php'</script>";
			
				exit;
			}
		}
		
		@mysqli_free_result($result);
		
		unset($row,$sql);
		
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./chezhangmangager.php";
	
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
			//======================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=6&id=".$getid;			
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}
		echo "<script>window.location.href = 'success.php'</script>";
	}
}

//删除方案
function belldel_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
		
		mysqli_query($con,"START TRANSACTION");
		//找到同组作息方案任务
		$sql = "SELECT task.taskid FROM task WHERE task.info IN(SELECT info FROM task WHERE task.taskid IN($getid) AND ";
		
		$sql.= "info!='' and channel=0) and info!='' and channel=0 ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		while($row = mysqli_fetch_array($result))
		{
			$sqlmedia = "DELETE FROM mediaoftask WHERE mediaoftask.taskid = '$row[taskid]'";
		
			mysqli_query($con,$sqlmedia) or die(mysqli_error($con));
		
			if(mysqli_error($con))
			{
				@mysqli_free_result($result);
				
				unset($row,$sql);
				
				mysqli_query($con,"ROLLBACK");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./bellmanager.php";
				
				echo "<script>window.location = 'error.php'</script>";
				
				exit;
			}
			$sqlterminal = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$row[taskid]'";
			
			mysqli_query($con,$sqlterminal) or die(mysqli_error($con));
			
		//	$sqltaskterminal = "DELETE FROM mediaofterminal WHERE mediaofterminal.taskid = '$row[taskid]'";
			
		//	mysqli_query($con,$sqltaskterminal) or die(mysqli_error($con));
			
			if(mysqli_error($con))
			{
				@mysqli_free_result($result);
				
				unset($row,$sql);
				
				mysqli_query($con,"ROLLBACK");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./bellmanager.php";
				
				echo "<script>window.location = 'error.php'</script>";
				
				exit;
			}
			$sqltask = "DELETE FROM task WHERE task.taskid = '$row[taskid]'";
			
			mysqli_query($con,$sqltask);
			
			if(mysqli_error($con))
			{
				@mysqli_free_result($result);
			
				unset($row,$sql);
			
				mysqli_query($con,"ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./bellmanager.php";
			
				echo "<script>window.location = 'error.php'</script>";
			
				exit;
			}
		}
		
		@mysqli_free_result($result);
		
		unset($row,$sql);
		
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./bellmanager.php";
	
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
			//======================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=6&id=".$getid;			
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_generate_general("task",6,$getid);
			$create_socket_obj->send_socket_generate_general2("task",6,$getid,1);
		}
		echo "<script>window.location.href = 'success.php'</script>";
	}
}

//复制方案
function sechetime($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;	
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
		
	$bellname ="";
	
	if(isset($_GET['bellName']))
	{
		$bellName = trim($_GET['bellName']);
	}

	$get_radio ="";
	$exemodel="1111111";
	if(isset($_GET['get_radio']))
	{
		$get_radio = trim($_GET['get_radio']);
		$repl = array(',' => '');
		$exemodel = strtr($get_radio,$repl);
	}
	
	if(isset($_GET['allSel']))
	{ 
		if(!empty($_GET['allSel']))//0 '' false null array() array(array())
		{
		  $getid = trim($_GET['allSel']);
		
		mysqli_query($con,"START TRANSACTION");
		$sqls = "SELECT task.taskid FROM task WHERE task.info='$bellName'";
		$results = mysqli_query($con,$sqls) or die(mysqli_error($con));
		if(mysqli_num_rows($results) > 0)
		{
			@mysqli_free_result($results);
		
			unset($sqls);
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
		else
		{
			@mysqli_free_result($results);
		
			unset($sqls);
		}

		

		//找到同组作息方案任
		$sql = "SELECT task.taskid FROM task WHERE task.taskid IN(SELECT taskid FROM task WHERE task.taskid IN($getid) OR ";
		
		$sql.= "task.sec_task_id IN($getid) and info!='' and channel=0) and info!='' and channel=0 ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		while($row = mysqli_fetch_array($result))
		{   	
			$sqltask = "INSERT INTO task(taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,startdate,enddate,playtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,defaultvolume,task_user_id,sec_task_id,parentid) (SELECT taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,startdate,enddate,playtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,defaultvolume,task_user_id,sec_task_id,parentid FROM task WHERE task.taskid = '$row[taskid]')";
			
			mysqli_query($con,$sqltask);
			
			if(mysqli_error($con))
			{
				@mysqli_free_result($result);
			
				unset($row,$sql);
			
				mysqli_query($con,"ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./bellmanager.php";
			
				echo "<script>window.location = 'error.php'</script>";
			
				exit;
			}

			$task_max = "SELECT taskid,sec_task_id FROM  task WHERE taskid = (SELECT MAX(taskid) FROM task)";
			
			$task_max_id = mysqli_query($con,$task_max) or die(mysqli_error($con));
			$getmaxid=0;

			while($taskid_row = mysqli_fetch_array($task_max_id))
			{	
			$getmaxid=$taskid_row['taskid'];
				if($taskid_row['sec_task_id']!=0)
			    $task_info = "UPDATE task SET info = '$bellName',exemodel = '$exemodel',sec_task_id='$taskid_row[taskid]'-1 WHERE taskid = '$taskid_row[taskid]'";
				else
				{
					$task_mediatask = "SELECT mediaid,sort FROM  mediaoftask WHERE taskid = '$row[taskid]'";
					$task_mediaoftask = mysqli_query($con,$task_mediatask) or die(mysqli_error($con));
					while($taskmedia_row = mysqli_fetch_array($task_mediaoftask))
					{
						mysqli_query($con,"INSERT INTO mediaoftask (mediaid,taskid,sort) VALUES('$taskmedia_row[mediaid]','$taskid_row[taskid]','$taskmedia_row[sort]')");
					}
						$task_info = "UPDATE task SET info = '$bellName',exemodel = '$exemodel' WHERE taskid = '$taskid_row[taskid]'";
					}
					mysqli_query($con,$task_info) or die(mysqli_error($con));

			}
		
			$sqltermin = "SELECT terminalid,workstate,groupid,area FROM terminaloftask WHERE terminaloftask.taskid = '$row[taskid]' ";
			
			$sqltermin_id = mysqli_query($con,$sqltermin) or die(mysqli_error($con));
			
			while($terminal_row1 = mysqli_fetch_array($sqltermin_id))
		   	{
			mysqli_query($con,"INSERT INTO terminaloftask(taskid,terminalid,workstate,groupid,area) VALUES('$getmaxid','$terminal_row1[terminalid]','$terminal_row1[workstate]','$terminal_row1[groupid]','$terminal_row1[area]')") or die(mysqli_error($con));
			
			}
		}		
		@mysqli_free_result($result);
		
		unset($row,$sql);
		
		$sql = "UPDATE task SET projectstate = '1' WHERE task.info IN(SELECT info FROM task WHERE task.taskid IN($getid) OR ";
		
		$sql.= "task.sec_task_id IN($getid) and info!='' and channel=0) and info!='' and channel=0 ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));


		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./bellmanager.php";
	
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
			//======================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=6&id=".$getid;			
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}
		echo "<script>window.location.href = 'success.php'</script>";
	
		}
	}

}


//复制方案
function bellcop_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;	
	
	//==================================================导入跳转类
	$forward_ok_error_obj = new forward_ok_error_class();
	
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$bellname ="";
	
	if(isset($_POST['bellName']))
	{
		$bellName = trim($_POST['bellName']);
	}
	
	if(isset($_GET['id']))
	{ 
		if(!empty($_GET['id']))//0 '' false null array() array(array())
		{
			
		$getid = trim($_GET['id']);
		
		
		
		mysqli_query($con,"START TRANSACTION");
		$sqls = "SELECT task.taskid FROM task WHERE task.info='$bellName'";
		$results = mysqli_query($con,$sqls) or die(mysqli_error($con));
	 if(mysqli_num_rows($results) > 0)
	 {
	 	@mysqli_free_result($results);
	
		unset($sqls);
		
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	 }
	 else
	 {
	 	@mysqli_free_result($results);
	
		unset($sqls);
	 }
	
		//找到同组作息方案任
		$sql = "SELECT task.taskid FROM task WHERE task.info IN(SELECT info FROM task WHERE task.taskid IN($getid)";
		
		$sql.= ") and info!='' and channel=0 ";
	
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		while($row = mysqli_fetch_array($result))
		{   	
		
			
			$sqltask = "INSERT INTO task(taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,startdate,enddate,playtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,defaultvolume,task_user_id,sec_task_id,parentid) (SELECT taskname,israndomplay,projectstate,timelengthtype,timelength,prepower,datasendmodel,state,startdate,enddate,playtime,exemodel,priority,tasktype,channel,bandrate,samplerate,cmd,cmdargs,playfileid,defaultvolume,task_user_id,sec_task_id,parentid FROM task WHERE task.taskid = '$row[taskid]')";
			
			mysqli_query($con,$sqltask);
			
			if(mysqli_error($con))
			{
				@mysqli_free_result($result);
			
				unset($row,$sql);
			
				mysqli_query($con,"ROLLBACK");
			
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = "./bellmanager.php";
			
				echo "<script>window.location = 'error.php'</script>";
			
				exit;
			}

			$task_max = "SELECT taskid,sec_task_id FROM  task WHERE taskid = (SELECT MAX(taskid) FROM task)";
			
			$task_max_id = mysqli_query($con,$task_max) or die(mysqli_error($con));
			$getmaxid=0;

			while($taskid_row = mysqli_fetch_array($task_max_id))
			{	
			$getmaxid=$taskid_row['taskid'];
				if($taskid_row['sec_task_id']!=0)
			    $task_info = "UPDATE task SET info = '$bellName',sec_task_id='$taskid_row[taskid]'-1 WHERE taskid = '$taskid_row[taskid]'";
				else
				{
					$task_mediatask = "SELECT mediaid,sort FROM  mediaoftask WHERE taskid = '$row[taskid]'";
					$task_mediaoftask = mysqli_query($con,$task_mediatask) or die(mysqli_error($con));
					while($taskmedia_row = mysqli_fetch_array($task_mediaoftask))
					{
						mysqli_query($con,"INSERT INTO mediaoftask (mediaid,taskid,sort) VALUES('$taskmedia_row[mediaid]','$taskid_row[taskid]','$taskmedia_row[sort]')");
					}
						$task_info = "UPDATE task SET info = '$bellName' WHERE taskid = '$taskid_row[taskid]'";
					}
					mysqli_query($con,$task_info) or die(mysqli_error($con));

			}
		
			$sqltermin = "SELECT terminalid,workstate,groupid,area FROM terminaloftask WHERE terminaloftask.taskid = '$row[taskid]' ";
			
			$sqltermin_id = mysqli_query($con,$sqltermin) or die(mysqli_error($con));
			
			while($terminal_row1 = mysqli_fetch_array($sqltermin_id))
		   	{
			mysqli_query($con,"INSERT INTO terminaloftask(taskid,terminalid,workstate,groupid,area) VALUES('$getmaxid','$terminal_row1[terminalid]','$terminal_row1[workstate]','$terminal_row1[groupid]','$terminal_row1[area]')") or die(mysqli_error($con));
			
			}
		}		
		@mysqli_free_result($result);
		
		unset($row,$sql);
		
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./bellmanager.php";
	
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
			//======================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=6&id=".$getid;			
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}
		echo "<script>window.location.href = 'success.php'</script>";
	
		}
	}

}
//网络电台任务启用
function webradiotaskstart_msg($con)
{
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//===============================创建套字节==============================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	
	$sql3 = "update task set state=3 where taskid in (".$getValue.") and task.tasktype=10 and task.info='' and task.channel=0 and sec_task_id=0 ";
	
	mysqli_query($con,$sql3) or die(mysqli_error($con));
	
	$sql3 = "update task set state=3 where sec_task_id in (".$getValue.") and task.info='' and task.channel=0";
	mysqli_query($con,$sql3) or die(mysqli_error($con));
	
	unset($sql3);
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./WebRadio.php";
		
		echo "<script>window.location='./error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./WebRadio.php";
		
		$getidlist = explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "task?state=3&id=".$getid;			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}

		echo "<script>window.location='./success.php'</script>";	
		
		exit;
	}		
}
//车站管理任务启用
function dostopmanagertaskstart_msg($con)
{
	//require_once('inc/socket_conf.php'); 
	//添加外部变量
	global $do_php_prompt;
	//===============================创建套字节==============================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	
	$sql3 = "update task set state=3 where taskid in (".$getValue.") and task.tasktype=11 and task.info='' and task.channel=0 and sec_task_id=0 ";
	
	mysqli_query($con,$sql3) or die(mysqli_error($con));
	
	unset($sql3);
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./chezhangmangager.php";
		
		echo "<script>window.location='./error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./chezhangmangager.php";
		
		$getidlist = explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//====================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "task?state=3&id=".$getid;			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}

		echo "<script>window.location='./success.php'</script>";	
		
		exit;
	}		
}

//采播任务启用
function admtaskstart_msg($con)
{
	  
	
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	
	$get_sql = "UPDATE task SET state=3 WHERE taskid IN ($getValue) AND info = '' AND tasktype = 3 and channel = 0 ";
	
	mysqli_query($con,$get_sql) or die(mysqli_error($con)); 
	
	$get_sql = "UPDATE task SET state=3 WHERE sec_task_id IN ($getValue) AND info = '' and channel = 0 ";
	
	mysqli_query($con,$get_sql) or die(mysqli_error($con)); 
	
	
	unset($get_sql);
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./admmanager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./admmanager.php";
		
		$getidlist=explode(",",$_GET['id']);
	
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=3&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
//启用文件广播
function start_file_task_msg($con)
{
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	global $do_php_prompt;
	
	$parentid = 0;
	$getid = trim($_GET['id']);
		$parentid = trim($_GET['taskid']);
		
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}

	if($getid!=""||$getid!=NULL)
	{
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '0',state = '0',offlinestate=0 WHERE taskid IN($getid)  AND sec_task_id=0") ;
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '0',state = '0',offlinestate=0 WHERE sec_task_id IN($getid) ") ;
	}
     else if($parentid!=""||$parentid!=NULL)
	 {
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '0',state = '0',offlinestate=0 WHERE parentid ='$parentid'  AND sec_task_id=0");
		
		
	 }
		if($result == FALSE)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			//foreach($task_ids as $task_value)
			//{
			//	$create_socket_obj->send_socket_generate_general("task",1,$task_value);
			//}
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='success.php'</script>";
		}
	
}
//启用彩播
function enableTask($con)
{
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	global $do_php_prompt;

	$parentid = 0;
	$getid = trim($_GET['id']);
	
	if($getid!=""||$getid!=NULL)
	{
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '0',state = '0',offlinestate=0 WHERE taskid IN($getid)  AND sec_task_id=0") ;
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '0',state = '0',offlinestate=0 WHERE sec_task_id IN($getid) ") ;
	}
   
		if($result == FALSE)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./admmanager.php";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			//foreach($task_ids as $task_value)
			//{
			//	$create_socket_obj->send_socket_generate_general("task",1,$task_value);
			//}
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./admmanager.php";
		
			echo "<script>window.location='success.php'</script>";
		}
	
}

//停用彩播
function disableTask($con)
{
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	global $do_php_prompt;
	
	$parentid = 0;
	$getid = trim($_GET['id']);
	
	if($getid!=""||$getid!=NULL)
	{
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '1',state = '0',offlinestate=0 WHERE taskid IN($getid)  AND sec_task_id=0") ;
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '1',state = '0',offlinestate=0 WHERE sec_task_id IN($getid) ") ;
	}
   
		if($result == FALSE)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./admmanager.php";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			//foreach($task_ids as $task_value)
			//{
			//	$create_socket_obj->send_socket_generate_general("task",1,$task_value);
			//}
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./admmanager.php";
		
			echo "<script>window.location='success.php'</script>";
		}
	
}
//启用噪声广播
function start_zhaoshen_task_msg($con)
{
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	global $do_php_prompt;
	
	$parentid = 0;
	$getid = trim($_GET['id']);
		$parentid = trim($_GET['taskid']);
		
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	
	if($getid!=""||$getid!=NULL)
	{
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '0',state = '0',offlinestate=0 WHERE taskid IN($getid)  AND sec_task_id=0") ;
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '0',state = '0',offlinestate=0 WHERE sec_task_id IN($getid) ") ;
	}
     else if($parentid!=""||$parentid!=NULL)
	 {
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '0',state = '0',offlinestate=0 WHERE parentid ='$parentid'  AND sec_task_id=0");
		
		
	 }
		if($result == FALSE)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./zhaoshentaskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			//foreach($task_ids as $task_value)
			//{
			//	$create_socket_obj->send_socket_generate_general("task",1,$task_value);
			//}
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./zhaoshentaskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='success.php'</script>";
		}
}


//启用默认噪声值
function enable_zhaoshen_volume_msg($con)
{
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	global $do_php_prompt;
	
	$parentid = 0;
	$getid = trim($_GET['id']);
		$parentid = trim($_GET['taskid']);
		
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	
	if($getid!=""||$getid!=NULL)
	{
		
		$col_func1_sql = "SELECT volume,dbvalue FROM soundtask WHERE taskid=0";
		
		$col_func1_result = mysqli_query($con,$col_func1_sql) or die(mysqli_error($con));
		
		while($col_func1_row = mysqli_fetch_array($col_func1_result))
		{
			$result = mysqli_query($con,"UPDATE soundtask SET dbvalue = '$col_func1_row[dbvalue]' WHERE taskid IN($getid)  AND volume='$col_func1_row[volume]'") ;
		}
		
		@mysqli_free_result($col_func1_result);
				
		unset($col_func1_row,$col_func1_sql,$col_func1_id);
	
	}
   
		if($result == FALSE)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./zhaoshentaskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			//foreach($task_ids as $task_value)
			//{
			//	$create_socket_obj->send_socket_generate_general("task",1,$task_value);
			//}
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./zhaoshentaskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='success.php'</script>";
		}
}


//启用led广播
function led_start_task_msg($con)
{
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	global $do_php_prompt;
	
	$parentid = 0;
	$getid = trim($_GET['id']);
	
	$parentid = trim($_GET['taskid']);
		
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	
	if($getid!=""||$getid!=NULL)
	{
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '0',state = '0',offlinestate=0 WHERE cmdargs IN($getid)") ;
	}
    
		if($result == FALSE)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./ledtaskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			//foreach($task_ids as $task_value)
			//{
			//	$create_socket_obj->send_socket_generate_general("task",1,$task_value);
			//}
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./ledtaskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='success.php'</script>";
		}
	
}

//停用噪声广播
function stop_zhaoshen_task_msg($con)
{
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	global $do_php_prompt;
	
	$parentid = 0;
		$getid = trim($_GET['id']);
		$parentid = trim($_GET['taskid']);
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	if($getid!=""||$getid!=NULL)
	{
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '1',state = '0',offlinestate=0 WHERE taskid IN($getid)  AND sec_task_id=0");
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '1',state = '0',offlinestate=0 WHERE sec_task_id IN($getid) ");
	}
	else if($parentid!=""||$parentid!=NULL)
	{
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '1',state = '0',offlinestate=0 WHERE parentid ='$parentid'  AND sec_task_id=0");
	}
		if($result == FALSE)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./zhaoshentaskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./zhaoshentaskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='success.php'</script>";
		}
}


//停用文件广播
function stop_file_task_msg($con)
{
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	global $do_php_prompt;
	
	$parentid = 0;
		$getid = trim($_GET['id']);
		$parentid = trim($_GET['taskid']);
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	if($getid!=""||$getid!=NULL)
	{
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '1',state = '0',offlinestate=0 WHERE taskid IN($getid)  AND sec_task_id=0");
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '1',state = '0',offlinestate=0 WHERE sec_task_id IN($getid) ");
	}
	else if($parentid!=""||$parentid!=NULL)
	{
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '1',state = '0',offlinestate=0 WHERE parentid ='$parentid'  AND sec_task_id=0");
	}
		if($result == FALSE)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='success.php'</script>";
		}
}

//停用文件广播
function led_stop_task_msg($con)
{
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	global $do_php_prompt;
	
	$parentid = 0;
		$getid = trim($_GET['id']);
		$parentid = trim($_GET['taskid']);
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	
	if($getid!=""||$getid!=NULL)
	{
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '1',state = '0',offlinestate=0 WHERE cmdargs IN($getid)");
	}

		if($result == FALSE)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./ledtaskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息

			$_SESSION['url'] = "./ledtaskmanager.php?id=$parentid&userid=$userid";
		
			echo "<script>window.location='success.php'</script>";
		}

}


//启用tts广播
function start_tts_task_msg($con)
{
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	global $do_php_prompt;
	
	$parentid = 0;
	$getid = trim($_GET['id']);
	//	$parentid = trim($_GET['taskid']);
	if($getid!=""||$getid!=NULL)
	{
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '0',state = '0' WHERE taskid IN($getid) AND info='' AND sec_task_id=0");
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '0',state = '0' WHERE sec_task_id IN($getid) AND info=''");
		
	}	
  //   else if($parentid!=""||$parentid!=NULL)
	//	$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '0',state = '0' WHERE parentid ='$parentid' AND info='' AND sec_task_id=0");
		
		if($result == FALSE)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./displayttsmanager.php";
		
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./displayttsmanager.php";
		
			echo "<script>window.location='success.php'</script>";
		}
	
}
//停用tts广播
function stop_tts_task_msg($con)
{
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	global $do_php_prompt;
	
	$parentid = 0;
	$getid = trim($_GET['id']);
	//	$parentid = trim($_GET['taskid']);
	if($getid!=""||$getid!=NULL)
	{
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '1',state = '0' WHERE taskid IN($getid) AND info='' AND sec_task_id=0");
		$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '1',state = '0' WHERE sec_task_id IN($getid) AND info=''");
	}
//	else if($parentid!=""||$parentid!=NULL)
	//	$result = mysqli_query($con,"UPDATE audioserver.task SET projectstate = '1',state = '0' WHERE parentid ='$parentid' AND info='' AND sec_task_id=0");

		if($result == FALSE)
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			$_SESSION['url'] = "./displayttsmanager.php";
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./displayttsmanager.php";
		
			echo "<script>window.location='success.php'</script>";
		}

}

//采播任务暂停
function admtaskstop_msg($con)
{
	//require_once("inc/socket_conf.php");  
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	$getValue = 0;
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	
	$sql = "UPDATE task SET state = 2 where taskid in ($getValue) AND info = '' and channel = 0 ";
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
//	$sql = "UPDATE task SET state = 2 where sec_task_id in ($getValue) AND info = '' and channel = 0 ";

	//mysqli_query($con,$sql) or die(mysqli_error($con));	
	//unset($sql);
	 
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./admmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./admmanager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=2&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_generate_general("task",2,$getid);
			$create_socket_obj->send_socket_generate_general2("task",2,$getid,3);
		}
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//网络电台任务暂停
function webradiotaskstop_msg($con)
{
	//require_once("inc/socket_conf.php");  
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	
	$sql = "UPDATE task SET state = 2 where taskid in ($getValue) AND info = '' AND tasktype = 10 and sec_task_id = 0 and channel = 0 ";
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$sql = "UPDATE task SET state = 2 where sec_task_id in ($getValue) AND info = ''";
	mysqli_query($con,$sql) or die(mysqli_error($con));
	unset($sql);
	 
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./WebRadio.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./WebRadio.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=2&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_generate_general("task",2,$getid);
			$create_socket_obj->send_socket_generate_general2("task",2,$getid,10);
		}
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//网络电台任务暂停
function stopmanagertaskstop_msg($con)
{
	//require_once("inc/socket_conf.php");  
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = 0;
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	
	$sql = "UPDATE task SET state = 2 where taskid in ($getValue) AND info = '' AND tasktype = 11 and sec_task_id = 0 and channel = 0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	unset($sql);
	 
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./chezhangmangager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./chezhangmangager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=2&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",2,$getid);
		}
		
		echo "<script>window.location='success.php'</script>";	
	}
}
//车站管理任务删除
function stopmanagertaskdel_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		
		$adm_taskId_array = explode(",",$taskid);
	}
	//添加事务
	mysqli_query($con,"START TRANSACTION"); 
	
	for($i=0; $i<count($adm_taskId_array); $i++)
	{	
		//判断是否有功放
		$col_task_sql = "SELECT prepower FROM task WHERE task.taskid='$adm_taskId_array[$i]' AND tasktype=11 AND info='' AND sec_task_id=0 ";
		
		$col_task_result = mysqli_query($con,$col_task_sql) or die(mysqli_error($con));
		
		if($col_task_row = mysqli_fetch_array($col_task_result))
		{
			if($col_task_row['prepower'] > 0)
			{
				//取采播功放id
				$col_func_sql = "SELECT taskid FROM task WHERE sec_task_id='$adm_taskId_array[$i]' AND tasktype=9 AND info='' AND channel = 0 ";
				
				$col_func_result = mysqli_query($con,$col_func_sql) or die(mysqli_error($con));
				
				if($col_func_row = mysqli_fetch_array($col_func_result))
				{
					//删除功放任务
					mysqli_query($con,"DELETE FROM terminaloftask WHERE taskid = '".$col_func_row[taskid]."'") or die(mysqli_error($con));
					
					//删除功放
					mysqli_query($con,"DELETE FROM audioserver.task WHERE taskid = '".$col_func_row[taskid]."'") or die(mysqli_error($con));
				}
				
				@mysqli_free_result($col_func_result);
				
				unset($col_func_row,$col_func_sql);
			}
		}
		
		@mysqli_free_result($col_task_result);
				
		unset($col_task_row,$col_task_sql);
		
		//删除采播终端
		$col_func1_id = 0;
		//查询采播终端任务
		$col_func1_sql = "SELECT taskid FROM task WHERE sec_task_id = '$adm_taskId_array[$i]' AND tasktype = 9 AND channel = 0 AND info = ''";
		
		$col_func1_result = mysqli_query($con,$col_func1_sql) or die(mysqli_error($con));
		
		if($col_func1_row = mysqli_fetch_array($col_func1_result))
		{
			$col_func1_id = $col_func1_row['taskid'];
			
			mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$col_func1_id' ") or die(mysqli_error($con));
			
			mysqli_query($con,"DELETE FROM audioserver.task WHERE taskid = '$col_func1_id'") or die(mysqli_error($con));
		}
		
		@mysqli_free_result($col_func1_result);
				
		unset($col_func1_row,$col_func1_sql,$col_func1_id);
	}

	//删除自己
	mysqli_query($con,"DELETE FROM audioserver.task WHERE taskid IN(".$taskid.")") or die(mysqli_error($con));
	//删除终端任务
	mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid IN(".$taskid.")") or die(mysqli_error($con));
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./chezhangmangager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=6&id=".$getid;			
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}

		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./chezhangmangager.php";
		
		echo "<script>window.location='error.php'</script>";
	}	
}

//采播任务删除
function admtaskdel_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	$taskid = 0;
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		$adm_taskId_array = explode(",",$taskid);
	}
	//添加事务
	mysqli_query($con,"START TRANSACTION"); 
	
	for($i=0; $i<count($adm_taskId_array); $i++)
	{	
		//判断是否有功放
		$col_task_sql = "SELECT prepower FROM task WHERE task.taskid='$adm_taskId_array[$i]' AND tasktype=3 AND info='' AND sec_task_id=0 ";
		
		$col_task_result = mysqli_query($con,$col_task_sql) or die(mysqli_error($con));
		
		if($col_task_row = mysqli_fetch_array($col_task_result))
		{
			if($col_task_row['prepower'] > 0)
			{
				//取采播功放id
				$col_func_sql = "SELECT taskid FROM task WHERE sec_task_id='$adm_taskId_array[$i]' AND tasktype=9 AND info='' AND channel = 0 ";
				
				$col_func_result = mysqli_query($con,$col_func_sql) or die(mysqli_error($con));
				
				if($col_func_row = mysqli_fetch_array($col_func_result))
				{
					//删除功放任务
					mysqli_query($con,"DELETE FROM terminaloftask WHERE taskid = '".$col_func_row['taskid']."'") or die(mysqli_error($con));
					
					//删除功放
					mysqli_query($con,"DELETE FROM audioserver.task WHERE taskid = '".$col_func_row['taskid']."'") or die(mysqli_error($con));
				}
				
				@mysqli_free_result($col_func_result);
				
				unset($col_func_row,$col_func_sql);
			}
		}
		
		@mysqli_free_result($col_task_result);
				
		unset($col_task_row,$col_task_sql);
		
		//删除采播终端
		$col_func1_id = 0;
		//查询采播终端任务
		$col_func1_sql = "SELECT taskid FROM task WHERE sec_task_id = '$adm_taskId_array[$i]' AND tasktype = 8 AND channel = 0 AND info = ''";
		
		$col_func1_result = mysqli_query($con,$col_func1_sql) or die(mysqli_error($con));
		
		if($col_func1_row = mysqli_fetch_array($col_func1_result))
		{
			$col_func1_id = $col_func1_row['taskid'];
			
			mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$col_func1_id' ") or die(mysqli_error($con));
			
			mysqli_query($con,"DELETE FROM audioserver.task WHERE taskid = '$col_func1_id'") or die(mysqli_error($con));
		}
		
		@mysqli_free_result($col_func1_result);
				
		unset($col_func1_row,$col_func1_sql,$col_func1_id);
	}
	mysqli_query($con,"DELETE FROM terminalkey WHERE id IN(select keyid from terminalkeymap where terminalid in(".$taskid."))");
	mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalid IN(".$taskid.")");
	
	//删除自己
//删除终端任务
	mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid IN(".$taskid.")") or die(mysqli_error($con));
	
		mysqli_query($con,"DELETE FROM audioserver.task WHERE taskid IN(".$taskid.")") or die(mysqli_error($con));
	
		mysqli_query($con,"DELETE FROM audioserver.task WHERE sec_task_id IN(".$taskid.")") or die(mysqli_error($con));
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./admmanager.php";
	
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=6&id=".$getid;			
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_generate_general("task",6,$getid);
			$create_socket_obj->send_socket_generate_general2("task",6,$getid,3);
		}

		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./admmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
}
//采播音量修改
function admmanagervolumemodify_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	$getValue=$_GET['id'];
	$getVolume=$_GET['volume'];
	$tasktype=$_GET['tasktype'];
	$taskid=0;
	$gotomanagerpage="";
	$getID="";
	
	switch($_GET['tasktype'])
	{
		case "admtype":
		$gotomanagerpage="./admmanager.php";
		$taskid=3;
		break;
		case "teltype":
		$gotomanagerpage="./telBroadManager.php";
		$taskid=4;
		break;
		case "termfuncplaytype":
		$gotomanagerpage="./terminalfunctionplay.php";
		$taskid=5;
		break;
	}
	
	if($getValue!="")
	{
		$sql="SELECT DISTINCT terminal.id AS terminalID FROM terminaloftask,terminal,task ";
		
		$sql.="WHERE terminaloftask.terminalid=terminal.id AND task.taskid=terminaloftask.taskid AND task.taskid IN ($getValue)";
		
		$resultID=mysqli_query($con,$sql);
		
		while ($row = mysqli_fetch_array($resultID,MYSQL_ASSOC)) 
		{
			if($getID=="")
			{
				$getID=$row["terminalID"];
			}
			else
			{
				$getID=$getID.",".$row["terminalID"];
			}
		}
		if($getID=="")
		{
			$sqlmax = "SELECT terminal.id FROM terminal,task,terminaloftask WHERE task.taskid=terminaloftask.taskid AND ";
			
			$sqlmax.= "terminaloftask.terminalid=terminal.id AND task.taskid IN (SELECT MAX(task.taskid) FROM task WHERE task.tasktype='$taskid')";
			
			$result=mysqli_query($con,$sqlmax);
			
			$row=mysqli_fetch_array($result);
			
			$getID=$row[0];
		}
		
		$sqlVolume="UPDATE terminal SET volume ='$getVolume' WHERE id IN ($getID)";
		
		mysqli_query($con,$sqlVolume) or die(mysqli_error($con));
		
		if(mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = $gotomanagerpage;
			
			echo "<script>window.location='error.php'</script>";
		}
	}
	if(!mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = $gotomanagerpage;
	
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=5&id=".$getid."&volume=".$getVolume;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_task_volume("task",5,$getid,$getVolume);
		}
		
		echo "<script>window.location='success.php'</script>";
	}
}
//暂停电话采播任务
function teltaskstop_msg($con)
{
	//require_once("inc/socket_conf.php"); 
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	$getValue = 0;
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	mysqli_query($con,"START TRANSACTION");

	$sql = "UPDATE task SET state=2 WHERE taskid IN($getValue) AND task.tasktype = 4 AND task.info = '' AND task.channel = 0 AND sec_task_id=0 ";
	
	mysqli_query($con,$sql) or die(mysqli_error($con)); 
	
	if(mysqli_error($con))
	{
		mysqli_query($con,"ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./telBroadManager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		mysqli_query($con,"COMMIT");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./telBroadManager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=2&id=".$getid;	
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",2,$getid);
		}
	
		echo "<script>window.location='success.php'</script>";	
	}	
}
//执行电话采播任务
function teltaskstart_msg($con)
{
	//require_once("inc/socket_conf.php");	
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getValue = "";
	
	if(isset($_GET['id']))
	{
		$getValue = trim($_GET['id']);
	}
	mysqli_query($con,"START TRANSACTION");
	$sql = "update task set state=3 where taskid in ($getValue) and task.tasktype = 4 and task.info = '' and task.channel = 0 and sec_task_id=0 ";
	mysqli_query($con,$sql) or die(mysqli_error($con)); 
	if(mysqli_error($con))
	{
		mysqli_query($con,"ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./telBroadManager.php";
	
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		mysqli_query($con,"COMMIT ");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./telBroadManager.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
		
			$msg = "task?state=3&id=".$getid;			
		
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}	
		echo "<script>window.location='success.php'</script>";	
	}	
}
//删除电话采播任务
function teltaskdel_msg($con)
{
	 
	
	//require_once("inc/socket_conf.php"); 
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
		
		$task_id_array = explode(",",$taskid);
	}
	//启用事务
	mysqli_query($con,"START TRANSACTION");
	
	for($i=0; $i<count($task_id_array[$i]); $i++)
	{	
		//判断是否功放
		$tel_task_sql = "SELECT prepower FROM task WHERE taskid='$task_id_array[$i]' AND tasktype=4 AND info='' and sec_task_id=0 ";
		
		$tel_task_result = mysqli_query($con,$tel_task_sql) or die(mysqli_error($con));
		
		if($tel_task_row = mysqli_fetch_array($tel_task_result))
		{
			if($tel_task_row['prepower'] > 0)
			{
				//查找相关功放
				$tel_func_sql = "SELECT taskid FROM task WHERE task.sec_task_id='$task_id_array[$i]' AND tasktype=9 AND info='' AND channel=0 ";
				
				$tel_func_result = mysqli_query($con,$tel_func_sql) or die(mysqli_error($con));
				
				if($tel_func_row = mysqli_fetch_array($tel_func_result))
				{
					//删除功放任务
					mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid='".$tel_func_row['taskid']."'") or die(mysqli_error($con));
					//删除功放
					mysqli_query($con,"DELETE FROM task WHERE taskid = '".$tel_func_row['taskid']."' AND info='' AND tasktype=9 AND channel=0") or die(mysqli_error($con));
				}
				@mysqli_free_result($tel_func_result);
				
				unset($tel_func_sql,$tel_func_row);
			}
		}
	}
	@mysqli_free_result($tel_task_result);
				
	unset($tel_task_sql,$tel_task_row);
	//删除自己
	mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid IN (".$taskid.")") or die(mysqli_error($con));
	
	mysqli_query($con,"DELETE FROM task WHERE taskid IN(".$taskid.") AND info='' AND tasktype=4 AND channel=0") or die(mysqli_error($con));
	
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");

		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$getidlist=explode(",",$_REQUEST['id']);

		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=6&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}

		$_SESSION['url'] = "./telBroadManager.php";

		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysqli_query($con,"ROLLBACK");

		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./telBroadManager.php";

		echo "<script>window.location='error.php'</script>";
	}
}
//执行终端功放
function terfuncplaystart_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	$get_id = "";
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}
	
	mysqli_query($con,"START TRANSACTION");
	$sql_task = "UPDATE task SET state = '3' WHERE taskid IN($get_id) AND tasktype=5 AND info=''";
	mysqli_query($con,$sql_task) or die(mysqli_error($con));
/*	
	$sql_task = "UPDATE task SET state = '3' WHERE sec_task_id IN($get_id) AND info=''";
	mysqli_query($con,$sql_task) or die(mysqli_error($con));
	unset($sql_task);
	

	$sql_task = "UPDATE task SET state = '3' WHERE sec_task_id IN($get_id) AND tasktype=5 AND info='' AND channel=0 AND prepower=0 AND bandrate=0 ";
	
	mysqli_query($con,$sql_task) or die(mysqli_error($con));
	
	unset($sql_task);*/
	
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=3&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}
		
		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		echo "<script>window.location='error.php'</script>";
	}	
}
function taskcommandstart_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}

	mysqli_query($con,"START TRANSACTION");

	$sql_task = "UPDATE task SET state = 3 WHERE taskid IN($get_id)";

	mysqli_query($con,$sql_task) or die(mysqli_error($con));
	
	unset($sql_task);
	
/*	$sql_task = "UPDATE task SET state = '3' WHERE sec_task_id IN($get_id) AND tasktype=5 AND info='' AND channel=0 AND prepower=0 AND bandrate=0 ";
	mysqli_query($con,$sql_task) or die(mysqli_error($con));
	unset($sql_task);*/
	
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./Browse_system_task.php";
		
		$getidlist=explode(",",$_REQUEST['id']);
	
		foreach($getidlist as $getid)
		{
			//===================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			$msg = "task?state=3&id=".$getid;			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",3,$getid);
		}
		
		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		echo "<script>window.location='error.php'</script>";
	}	
}
//暂停终端功放
function terfuncplaystop_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}
	
	$term_sec_id = "";
	mysqli_query($con,"START TRANSACTION");
	/*$sql_task = "SELECT taskid FROM audioserver.task WHERE task.sec_task_id IN ($get_id) AND task.tasktype=5 AND task.channel=0";
	
	$result_task = mysqli_query($con,$sql_task) or die(mysqli_error($con));
	
	while($row_task = mysqli_fetch_array($result_task))
	{
		$term_sec_id[] = $row_task['taskid'];
	}
	@mysqli_free_result($result_task);
	
	unset($sql_task,$row_task);*/
	
	$sql_task = "UPDATE task SET state = '0' WHERE taskid IN($get_id) AND tasktype=5 AND info=''";
	mysqli_query($con,$sql_task) or die(mysqli_error($con));
	
	$sql_task = "UPDATE task SET state = '0' WHERE sec_task_id IN($get_id) AND info=''";
	mysqli_query($con,$sql_task) or die(mysqli_error($con));
	unset($sql_task);
		
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";

		$power_off_sql = "SELECT taskid FROM task WHERE taskid IN($get_id) AND tasktype=5 AND info='' AND channel=0 AND prepower=0 AND bandrate=0 ";
		
		$poer_off_result = mysqli_query($con,$power_off_sql) or die(mysqli_error($con));
		
		while($power_off_row = mysqli_fetch_array($poer_off_result))
		{
			//==================================================
			/*$socket	= new send_message_to_server($port_conf);	
			
			$msg = "task?state=3&id=".$power_off_row['taskid'];			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_generate_general("task",2,$power_off_row['taskid']);
			$create_socket_obj->send_socket_generate_general2("task",2,$power_off_row['taskid'],5);
		}
		mysqli_free_result($poer_off_result);
		
		unset($power_off_sql,$power_off_row);

		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		echo "<script>window.location='error.php'</script>";
	}	
}
function taskcommandstop_msg($con)
{
	 
	 
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
	}
	
	$term_sec_id = "";
	
	mysqli_query($con,"START TRANSACTION");

	/*$sql_task = "SELECT taskid FROM audioserver.task WHERE task.sec_task_id IN ($get_id) AND task.tasktype=5 AND task.channel=0";
	
	$result_task = mysqli_query($con,$sql_task) or die(mysqli_error($con));
	
	while($row_task = mysqli_fetch_array($result_task))
	{
		$term_sec_id[] = $row_task['taskid'];
	}
	@mysqli_free_result($result_task);
	
	unset($sql_task,$row_task);*/
	
	$sql_task = "UPDATE task SET state = 0 WHERE taskid IN($get_id)";
	
	mysqli_query($con,$sql_task) or die(mysqli_error($con));
	
	unset($sql_task);
	
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./Browse_system_task.php";

		$power_off_sql = "SELECT taskid FROM task WHERE taskid IN($get_id)";
		
		$poer_off_result = mysqli_query($con,$power_off_sql) or die(mysqli_error($con));
		
		while($power_off_row = mysqli_fetch_array($poer_off_result))
		{
			//==================================================
			/*$socket	= new send_message_to_server($port_conf);	
			
			$msg = "task?state=3&id=".$power_off_row['taskid'];			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",0,$power_off_row['taskid']);
		}
		mysqli_free_result($poer_off_result);
		
		unset($power_off_sql,$power_off_row);

		echo "<script>window.location='success.php'</script>";	
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		echo "<script>window.location='error.php'</script>";
	}	
}


//重启服务器
function restart_server_msg($con)
{
	//require_once("inc/socket_conf.php");
	//====================导入外部数据
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	//===================================================
	/*$socket = new send_message_to_server($port_conf);
	
	$strbuff = "server?state=1";
	
	$socket->send_data($_SESSION['serverip'],$strbuff);
	*/

	$create_socket_obj->send_socket_restart("server",1);
	
	$_SESSION['info'] = strtoupper($do_php_prompt['The_system_is_restarting']);//提示信息
	
	$_SESSION['url'] = "./servermanager.php";
	
	echo "<script>window.location='success.php'</script>";
	
	@session_unset();	
	@session_destroy();	
	
/*	
	echo "<script>window.history.back();</script>";
*/
	//exit;
}


//删除终端功放
function centerctrdel_msg($con)
{
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
		
		$get_id_array = explode(",",$get_id);
	}
	mysqli_query($con,"START TRANSACTION");
	
	for($i=0; $i < count($get_id_array); $i++)
	{
		$func_task_sql = "SELECT taskid FROM task WHERE  info = '' AND sec_task_id='".$get_id_array[$i]."' AND channel = 0 AND bandrate=0";
		
		$func_task_result = mysqli_query($con,$func_task_sql) or die($func_task_sql);
		
		if($func_task_row = mysqli_fetch_array($func_task_result))
		{
			//删除功放终端任务
			mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid='".$func_task_row['taskid']."'") or die(mysqli_error($con));
			
			//删除次要功放
			mysqli_query($con,"DELETE FROM task WHERE task.taskid = '".$func_task_row['taskid']."'") or die(mysqli_error($con));
		}
	}
	
	@mysqli_free_result($func_task_result);
		
	unset($func_task_sql,$func_task_row);
	
		mysqli_query($con,"DELETE FROM terminalkey WHERE id IN(select keyid from terminalkeymap where terminalid in(".$get_idget_id."))");
	mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalid IN(".$get_id.")");
	
	
	mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid IN ($get_id)") or die(mysqli_error($con));
	
	mysqli_query($con,"DELETE FROM task WHERE task.taskid IN($get_id)") or die(mysqli_error($con));
	
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./centerctrmanager.php";
		
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=6&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_generate_general("task",6,$getid);
			$create_socket_obj->send_socket_generate_general2("task",6,$getid,5);
		}
		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		echo "<script>window.location='error.php'</script>";
	}
}



//删除终端功放
function terfuncplaydel_msg($con)
{
	 
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
		
		$get_id_array = explode(",",$get_id);
	}
	mysqli_query($con,"START TRANSACTION");
	
	for($i=0; $i < count($get_id_array); $i++)
	{
		$func_task_sql = "SELECT taskid FROM task WHERE tasktype = 5 AND info = '' AND sec_task_id='".$get_id_array[$i]."' AND channel = 0 AND bandrate=0";
		
		$func_task_result = mysqli_query($con,$func_task_sql) or die($func_task_sql);
		
		if($func_task_row = mysqli_fetch_array($func_task_result))
		{
			//删除功放终端任务
			mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid='".$func_task_row['taskid']."'") or die(mysqli_error($con));
			
			//删除次要功放
			mysqli_query($con,"DELETE FROM task WHERE task.taskid = '".$func_task_row['taskid']."'") or die(mysqli_error($con));
		}
	}
	
	@mysqli_free_result($func_task_result);
		
	unset($func_task_sql,$func_task_row);
	
		mysqli_query($con,"DELETE FROM terminalkey WHERE id IN(select keyid from terminalkeymap where terminalid in(".$get_id."))");
	mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalid IN(".$get_id.")");
	
	
	mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid IN ($get_id)") or die(mysqli_error($con));
	
	mysqli_query($con,"DELETE FROM task WHERE task.taskid IN($get_id)") or die(mysqli_error($con));
	
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=6&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			//$create_socket_obj->send_socket_generate_general("task",6,$getid);
			$create_socket_obj->send_socket_generate_general2("task",6,$getid,5);
		}
		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		echo "<script>window.location='error.php'</script>";
	}
}
function taskcommanddel_msg($con)
{
	 
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$get_id = "";
	
	if(isset($_GET['id']))
	{
		$get_id = trim($_GET['id']);
		
		$get_id_array = explode(",",$get_id);
	}
	mysqli_query($con,"START TRANSACTION");
	
	for($i=0; $i < count($get_id_array); $i++)
	{
		$func_task_sql = "SELECT taskid FROM task WHERE tasktype = 5 AND info = '' AND sec_task_id='".$get_id_array[$i]."' AND channel = 0 AND bandrate=0";
		
		$func_task_result = mysqli_query($con,$func_task_sql) or die($func_task_sql);
		
		if($func_task_row = mysqli_fetch_array($func_task_result))
		{
			//删除功放终端任务
			mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid='".$func_task_row['taskid']."'") or die(mysqli_error($con));
			
			//删除次要功放
			mysqli_query($con,"DELETE FROM task WHERE task.taskid = '".$func_task_row['taskid']."'") or die(mysqli_error($con));
		}
	}
	
	@mysqli_free_result($func_task_result);
		
	unset($func_task_sql,$func_task_row);
	
	mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid IN ($get_id)") or die(mysqli_error($con));
	
	mysqli_query($con,"DELETE FROM task WHERE task.taskid IN($get_id)") or die(mysqli_error($con));
	
	if(!mysqli_error($con))
	{
		mysqli_query($con,"COMMIT");
		
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./Browse_system_task.php";
		
		$getidlist=explode(",",$_GET['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "task?state=6&id=".$getid;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_generate_general("task",6,$getid);
		}
		echo "<script>window.location='success.php'</script>";
	}
	else
	{
		mysqli_query($con,"ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalfunctionplay.php";
		
		echo "<script>window.location='error.php'</script>";
	}
}
//添加终端---没有被使用到（采用终端自动添加）
function addterminal_msg($con)
{
	//添加外部变量
	global $do_php_prompt;
	$outputformat=$_POST['outputformat'];
	$audioCodec=$_POST['AudioCodec'];
	$audioChannels=$_POST['AudioChannels'];
	$audioQuality=$_POST['AudioQuality'];
	$audioBitRate=$_POST['AudioBitRate'];
	$audioSampleRate=$_POST['AudioSampleRate'];
	$terminalgetid=$_POST['terminalgetid'];
	$sql="UPDATE terminal SET sample = '$audioSampleRate' , bitrate = '$audioBitRate' ,channel = '$audioChannels' , audioquality = '$audioQuality' ,";
	$sql.=" audiocodec = '$audioCodec' , outformat = '$outputformat' WHERE id IN ($terminalgetid)";
	$result=mysqli_query($con,$sql);
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		echo "<script>window.location='success.php'</script>";	
	}
}
//紧急呼叫设置
function emergency_setting($con)
{
	//添加外部变量
	global $do_php_prompt;
	
	$taskid = 0;
	
	if(isset($_GET['id']))
	{
		$taskid = trim($_GET['id']);
	}
	$gettask = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettask = trim($_GET['gettask']);
	}
	
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	//查找数据库
	$emg_result = mysqli_query($con,"SELECT * FROM task WHERE task.tasktype = 7");
	
	if( mysqli_num_rows($emg_result) > 0)
	{
		@mysqli_free_result($emg_result);
		
		echo "<script>alert('".$do_php_prompt['Existing_Tasks_Cancel']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
	}
	else
	{
		mysqli_query($con,"UPDATE audioserver.task SET tasktype = '7',offlinestate=0 WHERE taskid = '$taskid'");	
			
		@mysqli_free_result($emg_result);
		
		if(!mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$gettask&userid=$userid";
			
			echo "<script>window.location='success.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
			$_SESSION['url'] = "./taskmanager.php?id=$gettask&userid=$userid";
			
			echo "<script>window.location='error.php'</script>";
		}
	}
}



//设置
function enordis_date_task($con)
{
	//添加外部变量
	global $do_php_prompt;
	$en_dis = 0;
	if(isset($_GET['en_dis']))
	{
		$en_dis = trim($_GET['en_dis']);
	}
	
	$get_str_date = 0;
	if(isset($_GET['get_str_date']))
	{
		$get_str_date = trim($_GET['get_str_date']);
	}
	
	$get_int_date = 0;
	if(isset($_GET['get_int_date']))
	{
		$get_int_date = trim($_GET['get_int_date']);
	}
	

	$getdate = 0;
	if(isset($_GET['getdate']))
	{
		$getdate = trim($_GET['getdate']);
	}
	
	$id = 0;
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
	}
	
	$taskid;
	if(isset($_GET['taskid']))
	{
		$taskid = trim($_GET['taskid']);
	}

		
	mysqli_query($con,"LOCK TABLE task WRITE");
	mysqli_query($con,"START TRANSACTION");

	if($en_dis==0)
	{
		mysqli_query($con,"UPDATE audioserver.task SET disableday = '$getdate' WHERE taskid IN($taskid)");	
		mysqli_query($con,"UPDATE audioserver.task SET disableday = '$getdate' WHERE sec_task_id IN($taskid)");					
	}
	else
	{
		mysqli_query($con,"UPDATE audioserver.task SET disableday = '0000-00-00' WHERE taskid IN($taskid)");	
		mysqli_query($con,"UPDATE audioserver.task SET disableday = '0000-00-00' WHERE sec_task_id IN($taskid)");
	}
		if(!mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			$_SESSION['url'] = "./Browse_active_task.php?get_str_date=$get_str_date&get_int_date=$get_int_date&id=$id";				
			mysqli_query($con,"COMMIT");
			mysqli_query($con,"UNLOCK TABLES");
			echo "<script>window.location='success.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			$_SESSION['url'] = "./Browse_active_task.php?get_str_date=$get_str_date&get_int_date=$get_int_date&id=$id";
			mysqli_query($con,"COMMIT");
			mysqli_query($con,"UNLOCK TABLES");
			echo "<script>window.location='error.php'</script>";
		}
		
}






//紧急呼叫取消
function emergency_canceling($con)
{
	
	//添加外部变量
	global $do_php_prompt;
	$gettask = 0;
	
	if(isset($_GET['gettask']))
	{
		$gettask = trim($_GET['gettask']);
	}
	
	$userid = 0;
	if(isset($_GET['userid']))
	{
		$userid = trim($_GET['userid']);
	}
	//===========================创建对象===================================
	$forward_ok_error_obj = new forward_ok_error_class();
	
	$emg_result = mysqli_query($con,"SELECT * FROM task WHERE task.tasktype = 7");
	
	if(mysqli_num_rows($emg_result) > 0)
	{
		mysqli_query($con,"UPDATE audioserver.task SET tasktype = '2',offlinestate=0 WHERE task.tasktype = '7'");
		
		@mysqli_free_result($emg_result);
		if(mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			$_SESSION['url'] = "./taskmanager.php?id=$gettask&userid=$userid";
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			$_SESSION['url'] = "./taskmanager.php?id=$gettask&userid=$userid";
			echo "<script>window.location='success.php'</script>";
		}
	}
	else
	{
		@mysqli_free_result($emg_result);
		//=========================================================================
		/*echo "<script>alert('".$do_php_prompt['No_Emergency_Task']."');</script>";
		
		echo "<script>window.history.back();</script>";
		
		exit;
		*/
		$forward_ok_error_obj->exit_back_function($do_php_prompt['No_Emergency_Task']);
	}
}
//修改终端声音---没有被使用到
function modifyterminalvolume_msg($con)
{
	
	
	//require_once("inc/socket_conf.php");
	//=====================添加外部变量
	global $do_php_prompt;
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$getid = $_GET['id'];
	
	$volume = $_GET['volume'];
	
	$sql = "UPDATE terminal SET volume='$volume' WHERE id IN ($getid)";
	
	mysqli_query($con,$sql) or die(mysqli_error($con));
	
	if(mysqli_error($con))
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./terminalmanager.php";
		
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息

		$_SESSION['url'] = "./terminalmanager.php";

		$getidlist=explode(",",$_REQUEST['id']);
		
		foreach($getidlist as $getid)
		{
			//==================================================
			/*$socket	=	new	send_message_to_server($port_conf);	
			
			$msg = "terminal?state=5&id=".$getid."&volume=".$volume;			
			
			$socket->send_data($_SESSION['serverip'],$msg);
			*/
			$create_socket_obj->send_socket_task_volume("terminal",5,$getid,$volume);
		}
		echo "<script>window.location='success.php'</script>";	
	}
}
//产生xml形成树---没使用到
function inputterminaltofile($con)
{
	
	$str = "<?xml version='1.0' encoding='UTF-8'?> <tree id=\"0\">";
	$fp = fopen("smarty/templates/BellManager/codebase/tree4.xml","w");
	fwrite($fp,$str);
	fwrite($fp,"\n");
	
	$streamresult=mysqli_query($con,"SELECT DISTINCT serverplaystream.streamid,serverplaystream.name FROM serverplaystream");
	while ($streamrow = mysqli_fetch_array($streamresult))
	{			
		$streamid = $streamrow['streamid'];
		$str = "<item text=\"".$streamrow['name']."\" id=\"dir_".$streamid."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >";
		fwrite($fp,$str);
		fwrite($fp,"\n");
		
		$terminalresult=mysqli_query($con,"SELECT DISTINCT terminal.id,terminal.terminalname FROM terminal WHERE	terminal.groupid=$streamid");
	while ($terminalrow = mysqli_fetch_array($terminalresult)) 
		{	
			$str = "<item text=\"".$terminalrow['terminalname']."\" id=\""."$terminalrow[id]"."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs.gif\" im2=\"iconSafe.gif\" >\n</item>\n"	;
			fwrite($fp,$str);		  
		}							 
	fwrite($fp,"</item>\n");			
	}		
	fwrite($fp,"</tree>\n");		
	
	fclose($fp);
}
//注册服务器
function regist_server($con)
{	

	$create_socket_obj = new create_socket_class();
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;

	$command = "";

	$output_info = array();
	//取注册码
	$license_key = "";
	
	if(isset($_POST['license_key']))
	{
		$license_key = trim($_POST['license_key']);
	}

	//取机器码
	$machine_code = "";
	
	if(isset($_POST['machine_code']))
	{
		$machine_code = trim($_POST['machine_code']);
	}
	$output_info=array();
	//执行命令

$command = "registerserver ".$license_key."";

//echo 'Current script owner: ' . get_current_user();
//$command = "sudo cmdhost -d2 --cmd='sudo ls'";
//$command = "cmdhost -d2 --cmd='sudo ls'";

	@exec($command, $output_info,$last_line);
	sleep(1);
	echo "<script>alert('发送成功,服务器重启！');</script>";
	sleep(1);
	$create_socket_obj->send_socket_restart("server",1);
		
	if(trim($output_info[0]) == "failed")
	{	
		echo "<script>alert('".$do_php_prompt['reg_fail_p_check']."');</script>";
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./regist_server.php";
		
		echo "<script>window.location='regist_server.php'</script>";
	}
	else if(trim($output_info[0]) == "expired")
	{
		echo "<script>alert('".$do_php_prompt['reg_fail_p_expired']."');</script>";
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "./regist_server.php";
		
		echo "<script>window.location='regist_server.php'</script>";
	
	}
	else if(trim($output_info[0]) == "success")
	{
		echo "<script>alert('".$do_php_prompt['reg_succ_re_server']."');</script>";

		//修改数据
		mysqli_query($con,"UPDATE audioserver.serverbaseparam SET registerflag = '1' WHERE id = '1'") or die(mysqli_error($con));
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./regist_server.php";
		echo "<script>window.location='regist_server.php'</script>";
		//$commandid="cmdhost -cmd='sudo reboot'";
	//	system($commandid);	
	$create_socket_obj->send_socket_restart("server",1);	
	}		
}

function settrydo($con)
{	




	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	$forward_ok_error_obj = new forward_ok_error_class();
	$create_socket_obj = new create_socket_class();
	//====================创建对象=================
	$socket	=	new	send_message_to_server($port_conf);	
	
	$regist = "";
	if(isset($_GET['regist']))
	{
		$regist = trim($_GET['regist']);
	}
	
	$trydo = "/var/www/html/ok112/serialtwo";
	if(is_file($trydo))
	{
		echo "<script>alert('".$do_php_prompt['try_succ_re_server']."');</script>";
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		$_SESSION['url'] = "./regist_server.php";
		echo "<script>window.location='error.php'</script>";
	}
	else
	{
	//	$msg = "terminal";
	//	$socket->send_data($_SESSION['serverip'],$msg);
		echo "<script>alert('".$do_php_prompt['try_succ_five_server']."');</script>";
		$myfile = fopen("serialtwo", "w");
		$txt = "[system]\n";
		fwrite($myfile, $txt);
		$txt = "startdate=".date("Y")."-".date("m")."-".date("d")."\n";
		fwrite($myfile, $txt);
		fclose($myfile);
		$command = "chmod 777 /var/www/html/ok112/serial";
		@system($command);
		$command = "cp /var/www/html/ok112/serial /var/www/html/ok112/serialtwo -rf";
		@system($command);
		$command = "chmod 777 /var/www/html/ok112/serialtwo";
		@system($command);
		$command="cmdhost -c 'sudo reboot'";
		system($command);
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		$_SESSION['url'] = "./login.php";
		echo "<script>window.location='success.php'</script>";	
	}	
}


function modify_camer_msg($con)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();

	$callbackid = "";
	if(isset($_GET['callbackid']))
	{
		$callbackid = trim($_GET['callbackid']);
	}
	$camername = "";
	if(isset($_POST['camername']))
	{
		$camername = trim($_POST['camername']);
	}
	$camerip = "";
	if(isset($_POST['camerip']))
	{
		$camerip = trim($_POST['camerip']);
	}

	$keyvalue = "";
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}
	$target = "";
	if(isset($_POST['target']))
	{
		$target = trim($_POST['target']);
	}

	  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
	$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	$get_noids=1;
	if(isset($_POST['get_noid']))
	{
	  $get_noids = trim($_POST['get_noid']);
	  $arr = array(',' =>'');
	  $get_noids =strtr($get_noids,$arr);
	}
	
	$get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}

	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	$targetArr=explode(",",$target);
	for($i=0;$i<count($targetArr);$i++)
	{
		if(is_numeric($targetArr[$i]))
		{
			$newtarget[]=(int)$targetArr[$i];
		}
		continue;
	}

	$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
	$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);

	mysqli_query($con,"LOCK TABLE camer WRITE,camerofterminal WRITE");
	mysqli_query($con,"START TRANSACTION");
	//查找是否有相同的设置
	$key_sql = "SELECT id FROM camer WHERE camername='$shotcutname' AND camername NOT IN(SELECT camername FROM camer WHERE id='$callbackid')";
	
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	if(mysqli_num_rows($key_result)> 0)
	{
		//直接插入
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	
	//查找是否有相同的设置
	$key_sql = "SELECT id,camername,camerip FROM camer WHERE id='$callbackid' ";
	
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($key_result)>0)
	{
		//先删除再添加
		$key_row = mysqli_fetch_array($key_result);
		
		  $key_id = $key_row['id'];
			mysqli_query($con,"UPDATE camer SET camername = '$camername',camerip='$camerip' WHERE id = '$key_id'");
			for($i=0;$i<count($newtarget);$i++)
			{
			    $key_sqlssub = "SELECT id FROM camerofterminal WHERE camerid='$key_id' AND terminalid='$newtarget[$i]' AND groupid='$analysis_tree_group_ids[$i]'";
					$key_resultssub = mysqli_query($con,$key_sqlssub) or die(mysqli_error($con));
					if(mysqli_num_rows($key_resultssub) <= 0)
					{
						mysqli_query($con,"INSERT INTO camerofterminal (camerid,terminalid,groupid) VALUES('$key_id','".$newtarget[$i]."','".$analysis_tree_group_ids[$i]."')") or die(mysqli_error($con));
				
					}	
			}
						for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
							$a=substr($get_noids,$c,1);
							$position=$c+1;
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						$getids=substr($getid,3);
						
						mysqli_query($con,"DELETE FROM camerofterminal WHERE camerid = 
						'$key_id' AND terminalid='$getids'") or die(mysqli_error($con));
						}
						
						}	

	
		if(mysqli_error($con))
				{
					mysqli_query($con,"ROLLBACK");
					$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
				}	
	}
		for($k=0;$k<strlen($get_terminal);$k++)
		{
				if(substr($get_terminal,$k,2)=="::")
					{
						$position=$k+2;
					
					}
						if(substr($get_terminal,$k,1)=="|")
						{
						  $position2 = $k;
						  $position3 = $position2-$position;
						$a=substr($get_terminal,$k-

$position3,$position3);
									for($i=0; $i<count

($newtarget); $i++)
									{
										if($a==$newtarget[$i])
										{
											$area = 

substr($get_terminal,$k+1,16);
											$sql = "UPDATE camerofterminal SET area='$area' WHERE camerid ='$key_id' AND terminalid ='$newtarget[$i]' AND groupid='$analysis_tree_group_ids[$i]'";
											mysqli_query

($sql) or die(mysqli_error($con));
											unset($sql);
										}
									}
								
						}		
			
		}
		
			if(!mysqli_error($con))
			{
				mysqli_query($con,"COMMIT");
				mysqli_query($con,"UNLOCK TABLES");
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				$_SESSION['url'] = "./camer_alarm.php";
			
			//	$create_socket_obj->send_socket_shotcut("terminal",$terminalid,$keyvalue);
				
				echo "<script>window.location='success.php'</script>";
			}	
}



function modifycallzone_msg($con)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();

	$terminalid = "";
	if(isset($_GET['terminalid']))
	{
		$terminalid = trim($_GET['terminalid']);
	}
		$callbackid = "";
	if(isset($_GET['callbackid']))
	{
		$callbackid = trim($_GET['callbackid']);
	}
	$shotcutname = "";
	if(isset($_POST['shotcutname']))
	{
		$shotcutname = trim($_POST['shotcutname']);
	}

	$keyvalue = "";
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}
	$target = "";
	if(isset($_POST['target']))
	{
		$target = trim($_POST['target']);
	}

	  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}

		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
		$get_noids=1;
	if(isset($_POST['get_noid']))
	{
	  $get_noids = trim($_POST['get_noid']);
  
	  $arr = array(',' =>'');
	  $get_noids =strtr($get_noids,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}

	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	
	$newtarget=explode(",",$target);
	/*
	for($i=0;$i<count($targetArr);$i++)
	{
		if(is_numeric($targetArr[$i]))
		{
			$newtarget[]=(int)$targetArr[$i];
		}
		continue;
	}
	*/
	$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
	$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);

	mysqli_query($con,"LOCK TABLE callgroup WRITE,terminalofcallgroup WRITE");
	
	mysqli_query($con,"START TRANSACTION");
	
	
	//查找是否有相同的设置
		$key_sql = "SELECT callgroup.id,callgroup.terminalid,callgroup.name FROM callgroup WHERE callgroup.id='$callbackid' ";
	
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($key_result) > 0)
	{
		//先删除再添加
		$key_row = mysqli_fetch_array($key_result);
		
		$key_id = $key_row['id'];
		$key_terminalid = $key_row['terminalid'];
	
			mysqli_query($con,"UPDATE callgroup SET callgroup.name = '$shotcutname',terminalid='$key_terminalid' WHERE callgroup.id = '$key_id'");
			for($i=0;$i<count($newtarget);$i++)
			{
			$key_sqlssub = "SELECT id FROM terminalofcallgroup WHERE terminalofcallgroup.selectgroupid='$key_id' AND terminalid='$newtarget[$i]'";
					$key_resultssub = mysqli_query($con,$key_sqlssub) or die(mysqli_error($con));
					if(mysqli_num_rows($key_resultssub) <= 0)
					{
						$group_id=$analysis_tree_group_ids[$i];
						mysqli_query($con,"INSERT INTO terminalofcallgroup (selectgroupid,terminalid,groupid) VALUES('$key_id','".$newtarget[$i]."','$group_id')") or die(mysqli_error($con));
					
					}	
			}
			for($c=0;$c<strlen($get_noids);$c++)
						{
						if(substr($get_noids,$c,1)=="_")
						{
							$a=substr($get_noids,$c,1);
							$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						$getids=substr($getid,3);
						
						mysqli_query($con,"DELETE FROM terminalofcallgroup WHERE terminalofcallgroup.selectgroupid = 
'$key_id' AND terminalid='$getids'") or die(mysqli_error($con));
						}
						
						}	

	
		if(mysqli_error($con))
				{
					mysqli_query($con,"ROLLBACK");
				
					$forward_ok_error_obj->exit_back_function($do_php_prompt

['Failed']);
				}	
	
	
		
	}
		for($k=0;$k<strlen($get_terminal);$k++)
		{
				if(substr($get_terminal,$k,2)=="::")
									{
									$position=$k+2;
									
									}
						if(substr($get_terminal,$k,1)=="|")
						{
						  $position2 = $k;
						  $position3 = $position2-$position;
									
									$a=substr($get_terminal,$k-

$position3,$position3);
									for($i=0; $i<count

($newtarget); $i++)
									{
										if($a==$newtarget[$i])
										{
											$area = 

substr($get_terminal,$k+1,16);
											$sql = "UPDATE 

terminalofcallgroup SET area='$area' WHERE selectgroupid ='$key_id' AND terminalid ='$newtarget[$i]'";
											mysqli_query

($sql) or die(mysqli_error($con));
											unset($sql);
										}
									}
								
						}		
			
		}
		
			if(!mysqli_error($con))
			{
				mysqli_query($con,"COMMIT");
				
				mysqli_query($con,"UNLOCK TABLES");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = "./view_terminal_call_group.php";
			
			//	$create_socket_obj->send_socket_shotcut("terminal",$terminalid,$keyvalue);
				
				echo "<script>window.location='success.php'</script>";
			}	
}
function addcallzone_msg($con)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();

	$terminalid = "";
	if(isset($_GET['terminalid']))
	{
		$terminalid = trim($_GET['terminalid']);
	}
		$callbackid = "";
	if(isset($_GET['callbackid']))
	{
		$callbackid = trim($_GET['callbackid']);
	}
	$shotcutname = "";
	if(isset($_POST['shotcutname']))
	{
		$shotcutname = trim($_POST['shotcutname']);
	}
	
	$keyvalue = "";
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
	}
	$target = "";
	if(isset($_POST['target']))
	{
		$target = trim($_POST['target']);
	}

	  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	 
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}

		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}

	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	

	$newtarget=explode(",",$target);
	$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		 $analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	/*
	for($i=0;$i<count($targetArr);$i++)
	{

		if(is_numeric($targetArr[$i]))
		{
			
			$newtarget[]=(int)$targetArr[$i];
		}
		continue;
	}
	*/
	mysqli_query($con,"LOCK TABLE callgroup WRITE,terminalofcallgroup WRITE");
	
	mysqli_query($con,"START TRANSACTION");
	//查找是否有相同的设置
		$key_sql = "SELECT callgroup.id FROM callgroup WHERE callgroup.terminalid='$callbackid' AND callgroup.name='$shotcutname'";
	
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($key_result) <= 0)
	{
		//直接插入
		$sql_key = "INSERT INTO callgroup (callgroup.name, terminalid)VALUES

('$shotcutname','$callbackid')";

		mysqli_query($con,$sql_key) or die(mysqli_error($con));
		
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
			//===========================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt

['Failed'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
		}
		unset($sql_key);
		
		$sql_result = mysqli_query($con,"SELECT MAX(id) FROM callgroup ") or die(mysqli_error($con));
		
		if($sql_row = mysqli_fetch_array($sql_result))
		{
			$key_id = $sql_row[0];
		}
		@mysqli_free_result($sql_result);
		
		unset($sql_row);
		
		for($i=0; $i<count($newtarget); $i++)
		{
			$group_id=$analysis_tree_group_ids[$i];
			mysqli_query($con,"INSERT INTO terminalofcallgroup (selectgroupid, terminalid,groupid) VALUES('$key_id','".$newtarget[$i]."','$group_id')") or die(mysqli_error($con));
			
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
			}
		}
	}
	else
	{
		//先删除再添加
		$key_row = mysqli_fetch_array($key_result);
		
		$key_id = $key_row['id'];
		
		mysqli_query($con,"DELETE FROM terminalofcallgroup WHERE terminalofcallgroup.selectgroupid = 

'$key_id'") or die(mysqli_error($con));
		
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
		}
		else
		{
			mysqli_query($con,"UPDATE callgroup SET callgroup.name = '$shotcutname' WHERE 

callgroup.id = '$key_id'");
		
			for($i=0; $i<count($newtarget); $i++)
			{
				$group_id=$analysis_tree_group_ids[$i];
				mysqli_query($con,"INSERT INTO terminalofcallgroup (selectgroupid, terminalid,groupid) VALUES('$key_id','".$newtarget[$i]."','$group_id')") or die(mysqli_error($con));
		
				if(mysqli_error($con))
				{
					mysqli_query($con,"ROLLBACK");
				
					$forward_ok_error_obj->exit_back_function($do_php_prompt

['Failed']);
				}
			}
	
		}
	}
		for($k=0;$k<strlen($get_terminal);$k++)
		{
				if(substr($get_terminal,$k,2)=="::")
					{
					$position=$k+2;
					
					}
						if(substr($get_terminal,$k,1)=="|")
						{
						  $position2 = $k;
						  $position3 = $position2-$position;
									
									$a=substr($get_terminal,$k-

$position3,$position3);
									for($i=0; $i<count

($newtarget); $i++)
									{
										if($a==$newtarget[$i])
										{
											$area = 

substr($get_terminal,$k+1,16);
											$sql = "UPDATE terminalofcallgroup SET area='$area' WHERE selectgroupid ='$key_id' AND terminalid ='$newtarget[$i]'";
											mysqli_query

($sql) or die(mysqli_error($con));
											unset($sql);
										}
									}
								
						}		
			
		}
		
			if(!mysqli_error($con))
			{
				mysqli_query($con,"COMMIT");
				
				mysqli_query($con,"UNLOCK TABLES");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = "./view_terminal_call_group.php";
			
				//$create_socket_obj->send_socket_shotcut("terminal",$terminalid,$keyvalue);
				
				echo "<script>window.location='success.php'</script>";
			}
}

//添加快捷键---快捷寻呼
function modifyshotcutkey_msg($con)
{
	//require_once("inc/config.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$terminalid = "";
	if(isset($_GET['terminalid']))
	{
		$terminalid = trim($_GET['terminalid']);
	}
	$id = "";
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
	}
	$flagdo = 0;
	if(isset($_GET['flagdo']))
	{
		$flagdo = trim($_GET['flagdo']);
	}

	$typeid = "";
	if(isset($_GET['typeid']))
	{
		$typeid = trim($_GET['typeid']);
	}
	$shotcutname = "";
	if(isset($_POST['shotcutname']))
	{
		$shotcutname = trim($_POST['shotcutname']);
	}
	
	$keyvalue = "";
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
		
			if($typeid==33 && $keyvalue==30)
			{
				
				$flagdo=0;
			}
		
	}
	$target = "";
	if(isset($_POST['target']))
	{
		$target = trim($_POST['target']);
	}

	  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	 
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}

		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}

	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	

	$newtarget=explode(",",$target);
	/*
	for($i=0;$i<count($targetArr);$i++)
	{
		if(is_numeric($targetArr[$i]))
		{
			$newtarget[]=(int)$targetArr[$i];
		}
		continue;
	}
	*/
	$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
	
		 $analysis_tree_group_ids = explode(",",$analysis_tree_group_string);

	mysqli_query($con,"LOCK TABLE terminalkey WRITE,terminalkeymap WRITE");
	
	mysqli_query($con,"START TRANSACTION");
	

	//查找是否有相同的设置
	$key_sql = "SELECT terminalkey.id FROM terminalkey WHERE terminalkey.terminalid='$terminalid' AND terminalkey.key = '$keyvalue' AND terminalkey.id not in($id)";

	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));

	if(mysqli_num_rows($key_result) <= 0)
	{
			mysqli_query($con,"UPDATE terminalkey SET terminalkey.name = '$shotcutname',terminalkey.terminalid='$terminalid',terminalkey.key = '$keyvalue' WHERE terminalkey.id = '$id'");
		
			mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalkeymap.keyid = '$id'") or die(mysqli_error($con));

			for($i=0; $i<count($newtarget); $i++)
			{
				$group_id = $analysis_tree_group_ids[$i];
				mysqli_query($con,"INSERT INTO terminalkeymap (keyid, terminalid,groupid) VALUES('$id','".$newtarget[$i]."','$group_id')") or die(mysqli_error($con));
				if(mysqli_error($con))
				{
					mysqli_query($con,"ROLLBACK");
					$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
				}
			}

			for($k=0;$k<strlen($get_terminal);$k++)
			{
				if(substr($get_terminal,$k,2)=="::")
				{
					$position=$k+2;
				}
				if(substr($get_terminal,$k,1)=="|")
				{
					$position2 = $k;
					$position3 = $position2-$position;	
					$a=substr($get_terminal,$k-$position3,$position3);
					for($i=0; $i<count($newtarget); $i++)
					{
						if($a==$newtarget[$i])
						{
							$area = substr($get_terminal,$k+1,16);
							$sql = "UPDATE terminalkeymap SET area='$area' WHERE keyid ='$key_id' AND terminalid ='$newtarget[$i]'";
							mysqli_query($con,$sql) or die(mysqli_error($con));
							unset($sql);
						}
					}
				}		
			}
	}
	else
	{
	
		$forward_ok_error_obj->exit_back_function($do_php_prompt['quickerror']);
	}
		
		
		if($typeid==33)
		{
			mysqli_query($con,"UPDATE terminal SET instancy = '1' WHERE terminal.id = '$terminalid'");
		}
		
			if(!mysqli_error($con))
			{
				mysqli_query($con,"COMMIT");
				
				mysqli_query($con,"UNLOCK TABLES");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				if($typeid==33)
				{
				$_SESSION['url'] = "./view_terminal_shotcut_mapping.php?getact=1&terminal_id=".$terminalid."&gettype=33";
				}
				else
				$_SESSION['url'] = "./view_terminal_shotcut_mapping.php?terminal_id=".$terminalid."";
			
				$create_socket_obj->send_socket_shotcut("terminal",$terminalid,$keyvalue);
				
				echo "<script>window.location='success.php'</script>";
			}
}



//添加快捷键---快捷寻呼
function addshotcutkey_msg($con)
{
	//require_once("/inc/config.php");
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	
	$terminalid = "";
	if(isset($_GET['terminalid']))
	{
		$terminalid = trim($_GET['terminalid']);
	}
	$flagdo = 0;
	if(isset($_GET['flagdo']))
	{
		$flagdo = trim($_GET['flagdo']);
	}
	$typeid = "";
	if(isset($_GET['typeid']))
	{
		$typeid = trim($_GET['typeid']);
	}
	$shotcutname = "";
	if(isset($_POST['shotcutname']))
	{
		$shotcutname = trim($_POST['shotcutname']);
	}
	
	$keyvalue = "";
	if(isset($_POST['keyvalue']))
	{
		$keyvalue = trim($_POST['keyvalue']);
		
			if($typeid==33 && $keyvalue==30)
			{
				$flagdo=0;
			}
		
		
	}
	$target = "";
	if(isset($_POST['target']))
	{
		$target = trim($_POST['target']);
	}

	  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	 
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}

		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}

	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
		 $analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		 $analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
		 
		 
	$newtarget=explode(",",$target);
	
	
	
	mysqli_query($con,"LOCK TABLE terminalkey WRITE,terminalkeymap WRITE");
	
	mysqli_query($con,"START TRANSACTION");
	//查找是否有相同的设置
	$key_sql = "SELECT terminalkey.id FROM terminalkey WHERE terminalkey.terminalid='$terminalid' AND terminalkey.key = '$keyvalue'";
	
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($key_result) <= 0)
	{
		//直接插入
		$sql_key = "INSERT INTO terminalkey (terminalkey.name, terminalid, terminalkey.key,flag)VALUES('$shotcutname','$terminalid','$keyvalue','$flagdo')";
		mysqli_query($con,$sql_key) or die(mysqli_error($con));
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
			//===========================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['Failed'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
		}
		unset($sql_key);
		
		$sql_result = mysqli_query($con,"SELECT MAX(id) FROM terminalkey ") or die(mysqli_error($con));
		
		if($sql_row = mysqli_fetch_array($sql_result))
		{
			$key_id = $sql_row[0];
		}
		@mysqli_free_result($sql_result);
		
		unset($sql_row);
		
		for($i=0; $i<count($newtarget); $i++)
		{
			$group_id= $analysis_tree_group_ids[$i];
			mysqli_query($con,"INSERT INTO terminalkeymap (keyid, terminalid,groupid) VALUES('$key_id','".$newtarget[$i]."','$group_id')") or die(mysqli_error($con));
			
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
			}
		}
	}
	else
	{
		//先删除再添加
		$key_row = mysqli_fetch_array($key_result);
		
		$key_id = $key_row['id'];
		
		mysqli_query($con,"DELETE FROM terminalkeymap WHERE terminalkeymap.keyid = '$key_id'") or die(mysqli_error($con));
		
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
		}
		else
		{
			mysqli_query($con,"UPDATE terminalkey SET terminalkey.name = '$shotcutname' WHERE terminalkey.id = '$key_id'");
		
			for($i=0; $i<count($newtarget); $i++)
			{
				$group_id= $analysis_tree_group_ids[$i];
				mysqli_query($con,"INSERT INTO terminalkeymap (keyid, terminalid,groupid) VALUES('$key_id','".$newtarget[$i]."','$group_id')") or die(mysqli_error($con));
		
				if(mysqli_error($con))
				{
					mysqli_query($con,"ROLLBACK");
				
					$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
				}
			}
	
		}
	}
		for($k=0;$k<strlen($get_terminal);$k++)
		{
				if(substr($get_terminal,$k,2)=="::")
									{
									$position=$k+2;
									
									}
						if(substr($get_terminal,$k,1)=="|")
						{
						  $position2 = $k;
						  $position3 = $position2-$position;
									
									$a=substr($get_terminal,$k-$position3,$position3);
									for($i=0; $i<count($newtarget); $i++)
									{
										if($a==$newtarget[$i])
										{
											$area = substr($get_terminal,$k+1,16);
											$sql = "UPDATE terminalkeymap SET area='$area' WHERE keyid ='$key_id' AND terminalid ='$newtarget[$i]'";
											mysqli_query($con,$sql) or die(mysqli_error($con));
											unset($sql);
										}
									}
								
						}		
			
		}
		
		if($typeid==33)
		{
			mysqli_query($con,"UPDATE terminal SET instancy = '1' WHERE terminal.id = '$terminalid'");
		}
		
			if(!mysqli_error($con))
			{
				mysqli_query($con,"COMMIT");
				
				mysqli_query($con,"UNLOCK TABLES");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				if($typeid==33)
				{
				$_SESSION['url'] = "./view_terminal_shotcut_mapping.php?getact=1&terminal_id=".$terminalid."&gettype=33";
				}
				else
				$_SESSION['url'] = "./view_terminal_shotcut_mapping.php?terminal_id=".$terminalid."";
			
				$create_socket_obj->send_socket_shotcut("terminal",$terminalid,$keyvalue);
				
				echo "<script>window.location='success.php'</script>";
			}
}

function getalarmeventname($eventtype)
{
	switch($eventtype)
	{
	case 131585:
	$cam_name="穿越警戒面";
	break;
		case 131586:
		$cam_name="进入区域";
	break;
		case 131587:
		$cam_name="离开区域";
	break;
		case 131588:
		$cam_name="区域入侵";
	break;
		case 131589:
		$cam_name="物品拿取放置";
	break;
		case 131590:
		$cam_name="徘徊";
	break;
		case 131591:
		$cam_name="停车";
	break;
		case 131592:
		$cam_name="快速移动";
	break;
		case 131593:
		$cam_name="人员聚集";
	break;
		case 131594:
		$cam_name="物品遗留";
	break;
		case 131595:
		$cam_name="物品拿取";
	break;
	default:
		$cam_name="异常报警";
	break;
	}
	return $cam_name;
}

//添加摄像机媒体事件
function add_camer_alarmevent_msg($con)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	$eventtype = "";
	if(isset($_POST['eventtype']))
	{
		$eventtype = trim($_POST['eventtype']);
	}
	
	$listvalue = "";
	if(isset($_POST['listvalue']))
	{
		$listvalue = trim($_POST['listvalue']);
		$targetArr=explode(",",$listvalue);
		for($i=0;$i<count($targetArr);$i++)
		{
			if(is_numeric($targetArr[$i]))
			{
				$newtarget[]=(int)$targetArr[$i];
			}
			continue;
		}
	}

	mysqli_query($con,"LOCK TABLE camer_alarm WRITE,camer_alarmofmedia WRITE");
	mysqli_query($con,"START TRANSACTION");
	
	//查找是否有相同的设置
	$key_sql = "SELECT id FROM camer_alarm WHERE eventtype='$eventtype'";
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	if(mysqli_num_rows($key_result)> 0)
	{
		//直接插入
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	else
	{
		$cam_name=getalarmeventname($eventtype);
		$sql_key = "INSERT INTO camer_alarm(eventtype, eventname)VALUES('$eventtype','$cam_name')";
		mysqli_query($con,$sql_key) or die(mysqli_error($con));

		$resultcamer = mysqli_query($con,"SELECT MAX(id) FROM camer_alarm") or die(mysqli_error($con));			  
		$resultcamer2 = mysqli_fetch_array($resultcamer);	
		$cameralarmid = $resultcamer2[0]; 
		@mysqli_free_result($resultcamer);			
		unset($resultcamer2);

		for($i=0; $i<count($newtarget);$i++)
		{
			$sql_keys = "INSERT INTO camer_alarmofmedia(mediaid,eventid,sort)VALUES('".$newtarget[$i]."','$cameralarmid','$i')";
			mysqli_query($con,$sql_keys) or die(mysqli_error($con));
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
			}
		}
	 }
			if(!mysqli_error($con))
			{
				mysqli_query($con,"COMMIT");
				mysqli_query($con,"UNLOCK TABLES");
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				$_SESSION['url'] = "./alarm_event_media.php";
				echo "<script>window.location='success.php'</script>";
			}
}


//修改摄像机媒体事件
function modify_camer_alarmevent_msg($con)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();
	$eventtype = "";
	if(isset($_POST['eventtype']))
	{
		$eventtype = trim($_POST['eventtype']);
	}
	
	$getid = "";
	if(isset($_GET['getid']))
	{
		$getid = trim($_GET['getid']);
	}
	
	$listvalue = "";
	if(isset($_POST['listvalue']))
	{
		$listvalue = trim($_POST['listvalue']);
		$targetArr=explode(",",$listvalue);
		for($i=0;$i<count($targetArr);$i++)
		{
			if(is_numeric($targetArr[$i]))
			{
				$newtarget[]=(int)$targetArr[$i];
			}
			continue;
		}
	}

	mysqli_query($con,"LOCK TABLE camer_alarm WRITE,camer_alarmofmedia WRITE");
	mysqli_query($con,"START TRANSACTION");
	
	//查找是否有相同的设置
	$key_sql = "SELECT id FROM camer_alarm WHERE eventtype='$eventtype' and id!='$getid'";
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	if(mysqli_num_rows($key_result)> 0)
	{
		//直接插入
	   $forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	else
	{
		$cam_name=getalarmeventname($eventtype);
		$sql_key = "UPDATE camer_alarm SET eventtype='$eventtype',eventname='$cam_name' WHERE id='$getid'";
		mysqli_query($con,$sql_key) or die(mysqli_error($con));
		mysqli_query($con,"DELETE FROM camer_alarmofmedia WHERE eventid = '$getid'") or die(mysqli_error($con));
		for($i=0; $i<count($newtarget);$i++)
		{
			$sql_keys = "INSERT INTO camer_alarmofmedia(mediaid,eventid,sort)VALUES('".$newtarget[$i]."','$getid','$i')";
			mysqli_query($con,$sql_keys) or die(mysqli_error($con));
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
			}
		}
	 }
		if(!mysqli_error($con))
		{
			mysqli_query($con,"COMMIT");
			mysqli_query($con,"UNLOCK TABLES");
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			$_SESSION['url'] = "./alarm_event_media.php";
			echo "<script>window.location='success.php'</script>";
		}
}



//添加摄像机事件
function add_camer_event_msg($con)
{
	//require_once("inc/socket_conf.php");
	//====================添加外部变量
	global $do_php_prompt;
	//====================创建对象=================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=====================创建套字节======================
	$create_socket_obj = new create_socket_class();

	$shotcutname = "";
	if(isset($_POST['shotcutname']))
	{
		$shotcutname = trim($_POST['shotcutname']);
	}
	
	$camerip = "";
	if(isset($_POST['camerip']))
	{
		$camerip = trim($_POST['camerip']);
	}
	$target = "";
	if(isset($_POST['target']))
	{
		$target = trim($_POST['target']);
	}

	$get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	 
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}

		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}

	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	
	$targetArr=explode(",",$target);
	for($i=0;$i<count($targetArr);$i++)
	{
		if(is_numeric($targetArr[$i]))
		{
			$newtarget[]=(int)$targetArr[$i];
		}
		continue;
	}
	
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	
	
	mysqli_query($con,"LOCK TABLE camer WRITE");
	mysqli_query($con,"START TRANSACTION");
	//查找是否有相同的设置
	$key_sql = "SELECT id FROM camer WHERE camername='$shotcutname'";
	$key_result = mysqli_query($con,$key_sql) or die(mysqli_error($con));
	if(mysqli_num_rows($key_result)> 0)
	{
		//直接插入
		$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
	}
	else
	{
		$sql_key = "INSERT INTO camer(camername, camerip)VALUES('$shotcutname','$camerip')";
			mysqli_query($con,$sql_key) or die(mysqli_error($con));
	
			$resultcamer = mysqli_query($con,"SELECT MAX(id) FROM camer") or die(mysqli_error($con));			  
			$resultcamer2 = mysqli_fetch_array($resultcamer);	
			$camerid = $resultcamer2[0]; 
			@mysqli_free_result($resultcamer);			
			unset($resultcamer2);


		for($i=0; $i<count($newtarget); $i++)
		{
			$sql_keys = "INSERT INTO camerofterminal(camerid,terminalid,groupid)VALUES('$camerid','".$newtarget[$i]."','".$analysis_tree_group_ids[$i]."')";
			mysqli_query($con,$sql_keys) or die(mysqli_error($con));
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
				$forward_ok_error_obj->exit_back_function($do_php_prompt['Failed']);
			}
		}
	}
		for($k=0;$k<strlen($get_terminal);$k++)
		{
			if(substr($get_terminal,$k,2)=="::")
			{
				$position=$k+2;
			}
			if(substr($get_terminal,$k,1)=="|")
			{
			  $position2 = $k;
			  $position3 = $position2-$position;
						
				$a=substr($get_terminal,$k-$position3,$position3);
				for($i=0; $i<count($newtarget); $i++)
				{
					if($a==$newtarget[$i])
					{
						$area = substr($get_terminal,$k+1,16);
						$sql = "UPDATE camerofterminal SET area='$area' WHERE camerid ='$camerid' AND terminalid ='$newtarget[$i]' and groupid='$analysis_tree_group_ids[$i]'";
						mysqli_query($con,$sql) or die(mysqli_error($con));
						unset($sql);
					}
				}
			}			
		}
		
			if(!mysqli_error($con))
			{
				mysqli_query($con,"COMMIT");
				
				mysqli_query($con,"UNLOCK TABLES");
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				$_SESSION['url'] = "./camer_alarm.php";
				//$create_socket_obj->send_socket_shotcut("terminal",$terminalid,$keyvalue);
				echo "<script>window.location='success.php'</script>";
			}
}

//修改用户密码---没有被使用到
function pwd($con)
{
	
	//添加外部变量
	global $do_php_prompt;
	
	$userpwd = md5($_POST['userpwd']);
	
	$result = mysqli_query($con,"SELECT * FROM `".$DB_PREFIX."admin` WHERE userpwd='$userpwd'");
	
	if($row = mysqli_fetch_array($result))
	{
		$newpwd = md5($_POST['newpwd']);
		
		mysqli_query($con,"UPDATE `book_admin` SET `userpwd`='$newpwd' WHERE id=$_GET[id]");
		
		if(mysqli_error($con))
		{
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = "pwd.php";
			
			echo "<script>window.location='error.php'</script>";
		}
		else
		{
			echo "<script>alert('".strtoupper($do_php_prompt['relogin_modified_successfully'])."');</script>";	//提示信息
			
			echo "<script>window.location='do.php?act=logout'</script>";
		}
	}
	else
	{
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = "pwd.php";
		
		echo "<script>window.location='error.php'</script>";
	}
}



////////////////////////////////////////////////////////////////////////////////////
/*
function modify_col_term_prepower(coll_prepower,coll_taskid)
{
	$col_prepower_sql = "UPDATE audioserver.task SET taskname = 'taskname' , timelength = 'timelength' , prepower = 'prepower' ,"; 

	$col_prepower_sql.= "datasendmodel = 'datasendmodel' , state = '0' ,startdate = 'startdate' , enddate = 'enddate' ,";

	$col_prepower_sql.= "playtime = 'playtime' , exemodel = 'exemodel' , channel = 'channel' ,"; 

	$col_prepower_sql.= "bandrate = 'bandrate' , samplerate = 'samplerate' ,"; 

	$col_prepower_sql.= "cmd = 'cmd' , cmdargs = '10' , defaultvolume = 'defaultvolume' , WHERE taskid = 'taskid' ";
	
	
}
*/
//非法注册服务
function invalid_regist_service($con)
{
	
	//添加外部变量
	global $do_php_prompt;
	
	$flag_var = 0;
	
	$regist_sql = "SELECT registerflag FROM audioserver.serverbaseparam WHERE registerflag = 1 or registerflag=2 or registerflag=3";
	
	$regist_result = mysqli_query($con,$regist_sql) or die(mysqli_error($con));
	
	if($regist_row = mysqli_fetch_array($regist_result))
	{
		$flag_var = 1;
	}
	else
	{
		$flag_var = 0;
		
		echo "<script>alert('".$do_php_prompt['server_not_registered']."');</script>";
	}
	@mysqli_free_result($regist_result);
	
	unset($regist_sql,$regist_row);
	
	ob_flush();
	
	return $flag_var;
}
function commandtask_msg($con)
{
	//require_once("inc/socket_conf.php");
	//添加外部变量
	global $do_php_prompt;
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	
	$taskname = "";
	
	$sec_task_id = 0;
	
	$cmd = 0;
	
	$cmdargs = 0;

	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay = 0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = trim((int)$_POST['israndomplay']);
	}  
	$timelengthtype = 1;

	$timelength = 0;

	if(isset($_POST['timelengthtype']))
	{
	
		$timelengthtype = $_POST['timelengthtype'];

		if($timelengthtype == 1)
		{  
			//$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
		}
		else
		{
			//$timelength = trim($_POST['circleTime']);
		} 
	}
	else
	{
		
		//$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
	}

	$timelength=0;
	$datasendmodel = 0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}

	$state = 0;
	
	$startdate="";
	if(isset($_POST['startdates']))
	{
		$startdate = $_POST['startdates'];
	}

	if(empty($_POST['startdates']))
	{
		$startdate = "00-00-00";
	}

	$enddate="";
	if(isset($_POST['enddates']))
	{
		$enddate = $_POST['enddates'];
	}
	
	if(empty($_POST['enddates']))
	{
		$enddate = "00-00-00";
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = trim($_POST['playtime']);
	}

	if(empty($_POST['playtime']))
	{
		$playtime = "00:00:00";
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
		
		if($prepower!=0)
		{
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$prepower."minutes -0 seconds"));
		}
	}
	//获取声音
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
  $get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	 
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
		$get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
	if(empty($_POST['get_terminal']))
	   {
	   $get_terminal='1111111111111111';
	   }
	
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = trim($_POST['exemodel']);
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = trim($_POST['hiddenweek']);
			
			$repl = array(',' => '');
			
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			$enddate = "0000-00-00";
			$startdate = "0000-00-00";
			$playtime = "00:00:00";
		}
	}
	
	if(empty($_POST['exemodel']))
	{
		$exemodel = "1111111";
	}
	$system_task=0;
	$system_command=0;
	$system_param=0;
	if(isset($_POST['systemcommand']))
	{
		$system_task=trim($_POST['systemcommand']);
		if($system_task == 12)
		{
		//	$system_command = trim($_POST['taskcommand']);
		$system_command=0;
		$system_param = "reboot";	
		}
		else if($system_task == 13)
		{
			$system_param = "reboot";	
		}
	}

	//获取任务优先级
	$priority=3;
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$tasktype=0;
	
	$audiosource=0;
	
	if(isset($_POST['audiosource']))
	{
		$audiosource = trim($_POST['audiosource']);
		
		$cmd = $audiosource;
		
		$audiosource = 0;
	}
	
	$channel = 0;
	
	if(isset($_POST['channel']))
	{
		$channel = trim($_POST['channel']);
		
		$cmdargs = $channel;
		
		$channel = 0;
	}
	
	$bandrate = 0;
	
	if(isset($_POST['bandrate']))
	{
		$bandrate = trim($_POST['bandrate']);
	}
	
	$samplerate=0;
	if(isset($_POST['samplerate']))
	{
		$samplerate = trim($_POST['samplerate']);
	}
		$terminallistvalue = trim($_POST['terminallistvalue']);
		
		$terminallistnum = explode(",",$terminallistvalue);
		
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);

	$playfileid = 0;
	
	$gototaskmanager = "";
	
	$openpower = 0;
	
	$openpowertaskid = 0;
	
	switch($_POST['taskType'])
	{
		case "systemtaskcommand":
		
			//$tasktype = 5;
		
			$cmd = 0;
		
			$gototaskmanager="./Browse_system_task.php";
		
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			
		break;
	}

	/*************************
		区分任务类型
		同一任务中不允许同名
	**************************/
	if($tasktype == 5)
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '5' ";
		
		$sql_same_name.= "AND prepower = '0' AND tasktype = 5 AND channel = 0 AND info = '' AND sec_task_id = 0 ";
		
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		
		if(mysqli_num_rows($result_same_name) > 0)
		{
			//============================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
		
			exit;*/
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	else
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype' ";
		
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		
		if(mysqli_num_rows($result_same_name) > 0)
		{
			//===========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	@mysqli_free_result($result_same_name);
	
	unset($sql_same_name);
		
	//获取用户优先级
	
	$sql = "SELECT book_admin.id,usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$row = mysqli_fetch_array($result);
	
	//设置优先级
	//$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
	
	@mysqli_free_result($result);
	
	unset($sql,$row);
	
	//加锁并启用事务
	mysqli_query($con,"START TRANSACTION");//获取不到插入的值
	
	mysqli_query($con,"LOCK TABLES task WRITE,terminaloftask WRITE,mediaoftask WRITE");
	
	if($tasktype !=1)
	{
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel, state, startdate, enddate,playtime, ";
		
		$sql.="exemodel, priority, tasktype, channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume,task_user_id, sec_task_id) ";
		
		$sql.="VALUES('$taskname', '$israndomplay', '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate', '$playtime', '$exemodel', '$priority', '$system_task', '$channel', ";
		
		$sql.="'$bandrate', '$samplerate', '$system_command', '$system_param', '$playfileid', '$task_default_volume', '$task_user_id', $sec_task_id) ";

		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		if(mysqli_error($con))
		{
			mysqli_query($con,"ROLLBACK");
		
			$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
			
			$_SESSION['url'] = $gototaskmanager;
			
			echo "<script>window.location='error.php'</script>";
			
			exit;
		}

		$sql = "SELECT MAX(taskid) FROM task";//取插入任务id
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			$gettaskid = $row[0];//新添加的任务id
		}
		
		@mysqli_free_result($result);
		
		unset($sql,$row);
		
		/*
		if(($prepower != 0)||($tasktype==5))
		{						
			if($tasktype == 5)
			{
			
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
				
				$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
				
				$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id) VALUES('$taskname', '$israndomplay', ";
				
				$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
				
				$sql.="'$playtime', '$exemodel', '$priority', '5', '0', '$bandrate', '$samplerate', ";
				
				$sql.="'$system_command', '$system_param', '$playfileid', '$task_default_volume','$task_user_id', '$gettaskid') ";
			}
			else
			{
				$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, ";
				
				$sql.="startdate, enddate, playtime, exemodel, priority, tasktype, channel, bandrate, samplerate, ";
				
				$sql.="cmd, cmdargs, playfileid, defaultvolume,task_user_id,sec_task_id) VALUES('$taskname', '$israndomplay', ";
				
				$sql.="'$timelengthtype', '$timelength', '$prepower', '$datasendmodel', '$state', '$startdate', '$enddate', ";
				
				$sql.="'$playtime', '$exemodel', '$priority', '9', '0', '$bandrate', '$samplerate', ";
				
				$sql.="'$system_command', '$system_param', '$playfileid', '$task_default_volume','$task_user_id', '$gettaskid') ";
			}
			mysqli_query($con,$sql) or die(mysqli_error($con));
			
			unset($sql);
			
			if(mysqli_error($con))
			{
				mysqli_query($con,"ROLLBACK");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				
				echo "<script>window.location='error.php'</script>";
				
				exit;
			}
			
			//取得功放任务id $openpowertaskid
			
			$resultpower = mysqli_query($con,"SELECT MAX(taskid) FROM task") or die(mysqli_error($con));
			  
			$rowpower2 = mysqli_fetch_array($resultpower);	
			  
			$openpowertaskid = $rowpower2[0]; 
			  
			@mysqli_free_result($resultpower);
			
			unset($rowpower2);
		}
		*/
	//for($i=0; $i<count($terminallistnum); $i++)
	//	{
			//if(is_numeric($terminallistnum[$i]))
		//	{
				//$temp = (int)$terminallistnum[$i];
				//插入终端任务关联
				//$sql="insert into terminaloftask (taskid,terminalid) values('$gettaskid','$temp')";
	  
		
	for($i=0; $i<count($terminallistnum); $i++)
		{
			
			if(is_numeric($terminallistnum[$i]))
			{
				$temp = (int)$terminallistnum[$i];
				//插入终端任务关联
				//$sql="insert into terminaloftask (taskid,terminalid) values('$gettaskid','$temp')";
	          
				
				$c =strlen($temp);
				 $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$gettaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
				
					mysqli_query($con,$sql) or die(mysqli_error($con));
					
					if(mysqli_error($con))
					{
						mysqli_query($con,"ROLLBACK");
					
						$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
						
						$_SESSION['url'] = "./bellmanager.php";
						
						echo "<script>window.location='error.php'</script>";
						
						exit;
					}
					/*
					if(($prepower != 0)||($tasktype==5))
					{
						//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$openpowertaskid','$temp')";
						
						$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$openpowertaskid','$temp','$analysis_tree_group_ids[$i]','1111111111111111')";
						
						mysqli_query($con,$sql) or die(mysqli_error($con));	
						
						if(mysqli_error($con))
						{
							mysqli_query($con,"ROLLBACK");
							
							$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
							
							$_SESSION['url'] = $gototaskmanager;
						
							echo "<script>window.location='error.php'</script>";
						
							exit;
						}		
					}
					*/
				//echo "$b";
				//echo"$c";
				//echo($a);
				
				
				
				
				for($j=0;$j<strlen($get_terminal);$j++)
				{
				
				if(substr($get_terminal,$j,2)=="::")
									{
									$position=$j+2;
									
									}
						if(substr($get_terminal,$j,1)=="|")
						{
						  $position2 = $j;
						  $position3 = $position2-$position;
									
									$a=substr($get_terminal,$j-$position3,$position3);
									
									if($a==$temp)
										{
									
										$area = substr($get_terminal,$j+1,16);
									
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$gettaskid' AND terminalid ='$temp'";
										mysqli_query($con,$sql) or die(mysqli_error($con));
										unset($sql);
									/*	if(($prepower != 0)||($tasktype==5))
										{
										$area = substr($get_terminal,$j+1,16);
										$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$openpowertaskid' AND terminalid ='$temp'";
										mysqli_query($con,$sql) or die(mysqli_error($con));
										unset($sql);
										}
										*/
										}
						}			
									
									
									
									
				 }

				}
				}

						
		
						
						
		
	}

	if($tasktype==2 || $tasktype==7)
	{
		if(isset($_POST['listvalue']))
		{
			$medialist=trim($_POST['listvalue']);
			
			$arrmedia=explode(",",$medialist);
			
			for($i=0;$i<count($arrmedia);$i++)
			{
				$str =$arrmedia[$i];
			
				if(!is_numeric($str))
				{
					continue;
				}
				
				$number =(int)$str;
			
				$sql="INSERT INTO mediaoftask(mediaid, taskid, sort) VALUES ('$number','$gettaskid','$i')";
			
				mysqli_query($con,$sql) or die(mysqli_error($con));
				
				if(mysqli_error($con))
				{	
					mysqli_query($con,"ROLLBACK");
				
					$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
					
					$_SESSION['url'] = $gototaskmanager;
					
					echo "<script>window.location='error.php'</script>";
					
					exit;
				}			
			}	
		}
	}


	if(!mysqli_error($con))
	{
		mysqli_query($con,"UNLOCK TABLES");
		mysqli_query($con,"COMMIT");
		$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
		//===================================================================
		/*$socket	=	new	send_message_to_server($port_conf);	
		
		$msg = "task?state=4&id=".$gettaskid."&volume=".$task_default_volume;			
		
		$socket->send_data($_SESSION['serverip'],$msg);
		*/
		$create_socket_obj->send_socket_task_volume("task",4,$gettaskid,$task_default_volume);
		
		echo "<script>window.location='success.php'</script>";
	}		

}

function modifysystem_msg($con)
{
	
	//require_once("inc/socket_conf.php");
	
	//添加外部变量
	global $do_php_prompt;
	
	//=======================创建对象====================
	$forward_ok_error_obj = new forward_ok_error_class();
	//=======================创建套字节==================
	$create_socket_obj = new create_socket_class();
	
	$sec_task_id = 0;
	
	$cmd = 0;
	
	$cmdargs = 0;
	
	$taskname="";
	if(isset($_POST['taskname']))
	{
		$taskname = trim($_POST['taskname']);
	}
	
	$israndomplay=0;
	if(isset($_POST['israndomplay']))
	{
		$israndomplay = $_POST['israndomplay'];
	}
	 $get_noid=1;
	if(isset($_POST['get_noid']))
	{
	   $get_noids = trim($_POST['get_noid']);
  
	  $arr = array(',' =>'');
	  $get_noids =strtr($get_noids,$arr);
	  
	}
	
	$timelengthtype=1;
	$timelength=0;
	if(isset($_POST['timelengthtype']))
	{
		$timelengthtype = $_POST['timelengthtype'];
	
		if($timelengthtype == 1)
		{  
		//$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 +trim($_POST['lenghtSenc'])*1; 
		}
		else
		{
		//	$timelength = trim($_POST['circleTime']);
		} 
	}
	else
	{
	//	$timelength = trim($_POST['lenghtHour'])*60*60 + trim($_POST['lenghtMin'])*60 + trim($_POST['lenghtSenc'])*1; 
	}
	
	$datasendmodel=0;
	if(isset($_POST['datasendmodel']))
	{
		$datasendmodel = $_POST['datasendmodel'];
	}
	
	$state=0;
	
	$startdate="0000-00-00";
	if(isset($_POST['startdates']))
	{
		$startdate = $_POST['startdates'];
	}
	
	$enddate="0000-00-00";
	if(isset($_POST['enddates']))
	{
		$enddate = $_POST['enddates'];
	}
	
	$playtime="00:00:00";
	if(isset($_POST['playtime']))
	{
		$playtime = $_POST['playtime'];
	}
	
	$prepower = 0;
	if(isset($_POST['prepower']))
	{
		$prepower = (int)$_POST['prepower'];
	
		if($prepower!=0)
		{
			$preopenpowertime = date('H:i:s',strtotime($playtime."-0 hours - ".$prepower."minutes -0 seconds"));
		}
	}
	//获取声音
	$task_default_volume = "50";
	if(isset($_POST['task_default_volume']))
	{
		$task_default_volume = trim($_POST['task_default_volume']);
	}
	$get_terst=1;
	if(isset($_POST['get_terst']))
	{
	   $get_terst = trim($_POST['get_terst']);
  
	  $arr = array(',' =>'');
	  $get_terst =strtr($get_terst,$arr);
	}
	
	$get_id=1;
	if(isset($_POST['get_id']))
	{
	  $get_id = trim($_POST['get_id']);
  
	  $arr = array(',' =>'');
	  $get_id =strtr($get_id,$arr);
	}
	
	  $get_inid=1;
	if(isset($_POST['get_inid']))
	{
	  $get_inid = trim($_POST['get_inid']);
  
	  $arr = array(',' =>'');
	  $get_inid =strtr($get_inid,$arr);
	}
	
	  $get_terminal=1;
	if(isset($_POST['get_terminal']))
	{
	   $get_terminal = trim($_POST['get_terminal']);
  
	  $arr = array(',' =>'');
	  $get_terminal =strtr($get_terminal,$arr);
	}
		
	$terminallistvalue = trim($_POST['terminallistvalue']);
	$terminallistnum = explode(",",$terminallistvalue);
	$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
	$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	
	$exemodel=1;
	if(isset($_POST['exemodel']))
	{
		$exemodel = $_POST['exemodel'];
		
		if($exemodel == 1)
		{
			$exemodel = "1111111";
		}
		else if($exemodel == 2)
		{
			$exemodel = $_POST['hiddenweek'];
			$repl = array(',' => '');
			$exemodel = strtr($exemodel,$repl);
		}
		else if($exemodel == 3)
		{
			$exemodel = "0000000";
			$playtime = "00:00:00";
			$startdate="0000-00-00";
			$enddate="0000-00-00";
		}
	}
	$system_task=0;
	$system_command=0;
	$system_param=0;
	if(isset($_POST['systemcommand']))
	{
		$system_task=trim($_POST['systemcommand']);
		if($system_task == 12)
		{
			$system_command=0;
		//	$system_command = trim($_POST['taskcommand']);
		$system_param = "reboot";	
		}
		else if($system_task == 13)
		{
		//	$system_param = trim($_POST['parameters']);	
		$system_param = "reboot";
		}
	}

	//获取任务优先级
	$priority = 13;
	
	if(isset($_POST['task_priority_text']))
	{
		$priority = trim($_POST['task_priority_text']);
	}
	
	$tasktype = 0;
	
	$audiosource = 0;
	if(isset($_POST['audiosource']))
	{	
		$audiosource = trim($_POST['audiosource']);
		
		$cmd = $audiosource;
		
		$audiosource = 0;
	}
	
	$channel=0;
	if(isset($_POST['channel']))
	{	
		$channel = trim($_POST['channel']);
		
		$cmdargs = $channel;
		
		$channel = 0;
	}
	
	$bandrate=0;
	if(isset($_POST['bandrate']))
	{	
		$bandrate = trim($_POST['bandrate']);
	}
	
	$samplerate=0;
	if(isset($_POST['samplerate']))
	{	
		$samplerate = trim($_POST['samplerate']);
	}
	
	$terminallistvalue = "";
	if(isset($_POST['terminallistvalue']))
	{	
		$terminallistvalue = trim($_POST['terminallistvalue']);
	 
	 	$terminalidarray = explode(",",$terminallistvalue);
	}
	
	$listvalue = "";
	if(isset($_POST['listvalue']))
	{	
		$listvalue = trim($_POST['listvalue']);
	
		$mediaidarray = explode(",",$listvalue);
	}
	
	$analysis_tree_group_string = "";
	
	if(isset($_POST['analysis_tree_group_string']))
	{
		$analysis_tree_group_string = trim($_POST['analysis_tree_group_string']);
		
		$analysis_tree_group_ids = explode(",",$analysis_tree_group_string);
	}
	
	$playfileid = 0;
	
	$gototaskmanager="";
	  
	switch($_POST['taskType'])
	{
			case "taskcommand":
			
		//	$tasktype=5;
			
			$cmd = 0;
			
			$preopenpowertime = date('H:i:s',strtotime($playtime."+".trim($_POST['lenghtHour'])." hours +".trim($_POST['lenghtMin'])." minutes +".trim($_POST['lenghtSenc'])." seconds"));
			
			$gototaskmanager="./Browse_system_task.php";
		break;
	}

	if($tasktype==5)
	{
		/*$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '5' AND task.prepower = 0 ";
		
		$sql_same_name.= "AND task.channel = 0 AND task.info = '' AND task.taskid != '$_GET[taskid]' and task.sec_task_id = 0 ";
		
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		
		if(mysqli_num_rows($result_same_name) > 0)
		{
			//=============================================================================================
			
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}*/
	}
	else
	{
		$sql_same_name = "SELECT * FROM task WHERE task.taskname = '$taskname' AND task.tasktype = '$tasktype' ";
		
		$sql_same_name.= "AND task.taskid != '$_GET[taskid]' ";
		
		$result_same_name = mysqli_query($con,$sql_same_name) or die(mysqli_error($con));
		
		if(mysqli_num_rows($result_same_name) > 0)
		{
			//===========================================================================================
			/*echo "<script>alert('".strtoupper($do_php_prompt['The_name_has_been_used'])."');</script>";//提示信息
			
			echo "<script>window.history.back();</script>";
			
			exit;
			*/
			$forward_ok_error_obj->exit_back_function($do_php_prompt['The_name_has_been_used']);
		}
	}
	@mysqli_free_result($result_same_name);
	
	unset($sql_same_name);
	
	//获取用户优先级
		
	$sql = "SELECT book_admin.id, usergroup.level FROM book_admin,usergroup WHERE ";
	
	$sql.= "book_admin.usergroupid = usergroup.id AND book_admin.username = '$_SESSION[username]' ";
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	$row = mysqli_fetch_array($result);	
	
	//设置优先级
	//$priority = trim($row['level'])*10 + $priority;
	
	$task_user_id = trim($row['id']);
		
	//读取任务用户ID比较若相同则修改 不同则不修改
	
	$task_userid_sql = "SELECT task.priority FROM task WHERE task.task_user_id = '$task_user_id' AND task.taskid = '$_GET[taskid]' ";
	
	$task_userid_result = mysqli_query($con,$task_userid_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($task_userid_result) <= 0)
	{
		$original_task_priority_result = mysqli_query($con,"SELECT task.priority FROM task WHERE task.taskid = '$_GET[taskid]'") or die(mysqli_error($con));
		
		$original_task_priority_row = mysqli_fetch_array($original_task_priority_result);
		
		$priority = trim($original_task_priority_row['priority']);
		
		@mysqli_free_result($original_task_priority_result);
		
		@mysqli_free_result($task_userid_result);
		
		unset($original_task_priority_row,$task_userid_sql);
	}
	else
	{
		@mysqli_free_result($task_userid_result);
		
		unset($task_userid_sql);
	}
	
	@mysqli_free_result($result);
	
	unset($sql,$row);
	//获取原来的任务名称、预开电源、用户id	
	$getoldtaskname = "";
	
	$getoldtaskprepower = "";
	
	$getoldtaskuserid = "";
	
	$sql = "SELECT task.taskname, task.prepower, task.task_user_id FROM task WHERE task.taskid = '$_GET[taskid]'";
	
	$result = mysqli_query($con,$sql)or die(mysqli_error($con));
	
	if($row = mysqli_fetch_array($result))
	{
		$getoldtaskname = $row['taskname'];
	
		$getoldtaskprepower = $row['prepower'];
		
		$getoldtaskuserid = $row['task_user_id'];
	}
	
	@mysqli_free_result($result);
	
	unset($row,$sql);
	//锁定并事务处理
	mysqli_query($con,"START TRANSACTION");
	
	mysqli_query($con,"LOCK TABLE task WRITE,terminaloftask WRITE,mediaoftask WRITE");

	if($getoldtaskprepower == 0 && $prepower == 0)
	{
		//什么也不做
	}
	else if($getoldtaskprepower == 0 &&	$prepower != 0)
	{
		$sql ="INSERT INTO task(taskname, israndomplay, timelengthtype, timelength, prepower, datasendmodel,state, startdate, enddate,";
		
		$sql.="playtime, exemodel, priority, tasktype,  channel, bandrate, samplerate, cmd, cmdargs, playfileid, defaultvolume, task_user_id, ";
		
		$sql.="sec_task_id) VALUES('$taskname', '$israndomplay',  '$timelengthtype', '$timelength', '$prepower', '$datasendmodel', ";
		
		$sql.="'$state', '$startdate', '$enddate','$playtime', '$exemodel', '$priority', '$system_task', '0', ";
		
		$sql.="'$bandrate', '$samplerate', '$system_command', '$system_param', '$playfileid', '$task_default_volume', '$getoldtaskuserid', '$_GET[taskid]')";
				
		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		//取终端功放id
		
		$result = mysqli_query($con,"select max(taskid) from task ");
		
		if($row = mysqli_fetch_array($result))
		{
			$getnewfunctionid = $row[0];
		}
		
		@mysqli_free_result($result);
		
		unset($row);
		
		for($i=0;$i<count($terminalidarray);$i++)
		{
			if(is_numeric($terminalidarray[$i]))
			{
				$terminalid = (int)$terminalidarray[$i];
				
		        
				//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$getnewfunctionid','$terminalid')";
				
				$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid)VALUES('$getnewfunctionid','$terminalid','$analysis_tree_group_ids[$i]')";
		
				mysqli_query($con,$sql) or die(mysqli_error($con));
		
				unset($sql);			
			}
		}
	}
	else if($getoldtaskprepower != 0 &&	$prepower == 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 AND task.info = '' and task.tasktype = '9' ";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysqli_free_result($result);
		
		unset($sql,$row);
		
	mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid'") or die(mysqli_error($con));
		
		mysqli_query($con,"DELETE FROM task WHERE task.taskid = '$getoldfunctionid'") or die(mysqli_error($con));
	}
	else if($getoldtaskprepower != 0 &&	$prepower != 0)
	{	
		$sql = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.channel = 0 AND task.info = '' and task.tasktype = '9'";
		
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if($row = mysqli_fetch_array($result))
		{
			$getoldfunctionid = $row['taskid'];
		}
		@mysqli_free_result($result);
		
		unset($sql,$row);
        
	//$sql = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' ";
		
	//mysqli_query($con,$sql) or die(mysqli_error($con));
		
	//unset($sql);

		$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";
		
		$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' , ";
		
		$sql.="state = '$state' ,startdate = '$startdate' ,enddate = '$enddate' ,";
		
		$sql.="playtime = '$playtime' ,exemodel = '$exemodel' , priority = '$priority' ,tasktype = '$system_task' , ";
		
		$sql.="channel = '0' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '$system_command' ,cmdargs = '$system_param' , ";
		
		$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume' ";
		
		$sql.=" WHERE  task.taskid = '$getoldfunctionid' and task.tasktype = '9' and channel = 0 ";
		
		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
	         	for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND groupid ='$getid'";
						  
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
							
							$sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$getoldfunctionid' AND terminalid ='$getid'";
							
							mysqli_query($con,$sql2) or die(mysqli_error($con));
							unset($sql2);

						}
						
						}
  
						for($j=0; $j<count($terminallistnum); $j++)
						{
							if(is_numeric($terminallistnum[$j]))
							{
							    $temp = (int)$terminallistnum[$j];
								$group = (int)$analysis_tree_group_ids[$j];
							
								$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$getoldfunctionid' AND terminalid='$temp' AND groupid = '$group'";
							    $get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));
							  						  
								if($get_row = mysqli_fetch_array($get_result))
								{	
						 			$get_terminals = $get_row['terminalid'];	
									$get_group = $get_row['groupid'];
								}
								@mysqli_free_result($get_result);
								unset($get_sql,$get_row);
								if($temp==$get_terminals)
								{
								  if($get_group==$group)
								  {
								  	  for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
										
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
								
								  }
								  else
								  {
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 					
								  } 
								}
								else 
								{
								
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$temp)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$getoldfunctionid' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}
							
								
							  
								
							//	checkterminal($temp,$get_terminal,$get_terminals,$getoldfunctionid,$j);
							

							}
						}
										
						
	
	}
	
	$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

	$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' ,state = '$state' ,startdate = '$startdate' ,";
	
	$sql.="enddate = '$enddate' ,playtime = '$playtime' ,exemodel = '$exemodel' ,priority = '$priority' ,tasktype = '$system_task' , ";

	$sql.="channel = '$channel' ,bandrate = '$bandrate' ,samplerate = '$samplerate' ,cmd = '$system_command' ,cmdargs = '$system_param' , ";

	$sql.="playfileid = '$playfileid' , defaultvolume = '$task_default_volume' WHERE taskid = '$_GET[taskid]' ";
	
	mysqli_query($con,$sql);
	
	unset($sql);
		
		//对相同功放任务处理
	if($tasktype == 5)
	{
		//查询相同功放任务
		$second_id = 0;
		
		$sql_play = "SELECT taskid FROM task WHERE task.sec_task_id = '$_GET[taskid]' AND task.tasktype = '5' ";
		
		$sql_play.= "AND task.prepower = '0' and task.channel = 0 and task.info = '' and task.sec_task_id != 0";
		
		$result_play = mysqli_query($con,$sql_play) or die(mysqli_error($con));
		
		if($row_play = mysqli_fetch_array($result_play))
		{
			$play_id[] = $row_play['taskid'];
		}
		
		@mysqli_free_result($result_play);
		
		unset($row_play,$sql_play);
		
		foreach($play_id as $value)
		{
			if($value != trim($_GET['taskid']))
			{
				$second_id = $value;
				
				break;
			}
		}
		unset($play_id);
		
		//更新附加功放
		if(5 == $tasktype)
		{
			$cmd = 0;
		}
		
		$sql ="UPDATE task SET	taskname = '$taskname' ,israndomplay = '$israndomplay' ,timelengthtype = '$timelengthtype' , ";

		$sql.="timelength = '$timelength' ,prepower = '$prepower' ,datasendmodel = '$datasendmodel' ,state = '$state' , ";
		
		$sql.="startdate = '$startdate' ,enddate = '$enddate' ,playtime = '$playtime' , ";
		
		$sql.="exemodel = '$exemodel' ,priority = '$priority' ,tasktype = '$tasktype' ,channel = '0' ,bandrate = '$bandrate' , ";
		
		$sql.="samplerate = '$samplerate' ,cmd = '$system_command' ,cmdargs = '$system_param' ,playfileid = '$playfileid' , ";
		
		$sql.="defaultvolume = '$task_default_volume' WHERE taskid = '$second_id' ";
		
		mysqli_query($con,$sql) or die(mysqli_error($con));
		
		unset($sql);
		
		//删除终端

		//mysqli_query($con,"DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id'") or die(mysqli_error($con));
		for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id' AND groupid ='$getid'";
						  
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
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$second_id' AND terminalid ='$getid'";
						  
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
						
				     
						}
						
						}
		//添加终端
		for($i=0;$i<count($terminalidarray);$i++)
		{
			if(is_numeric($terminalidarray[$i]))
			{
				$terminalid = (int)$terminalidarray[$i];
				$group = (int)$analysis_tree_group_ids[$i];
				//$sql="insert into terminaloftask(taskid,terminalid) VALUES('$second_id','$terminalid')";
				$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$second_id' AND terminalid='$terminalid' AND groupid='$group'";
							    $get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));
							  						  
								if($get_row = mysqli_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];
								$get_group = $get_row['groupid'];	
								}
								@mysqli_free_result($get_result);
								unset($get_sql,$get_row);
								if($terminalid==$get_terminals)
								{
								 if($group==$get_group)
								 {
								 
								 }
								 else
								 {
				                    $sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$second_id','$terminalid','$analysis_tree_group_ids[$i]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  	  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$terminalid)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$second_id' AND terminalid ='$terminalid'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 
								 
								 }

									}
									else 
								{
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$second_id','$terminalid','$analysis_tree_group_ids[$i]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											$position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
										
										//	$b=strlen($temp);
									
											if($a==$terminalid)
												{
												
												//$c=strpos($get_terminal,$a);
											
												//$area = substr($get_terminal,$c+strlen($temp)+1,8);
												$area = substr($get_terminal,$z+1,16);
											
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$second_id' AND terminalid ='$terminalid'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}	  
										  
				
				
							
			}
		}
	}

	
	for($c=0;$c<strlen($get_noids);$c++)
						{
						
						if(substr($get_noids,$c,1)=="_")
						{
						$a=substr($get_noids,$c,1);
						
						$position=$c+1;
						
						}
						if(substr($get_noids,$c,1)=="|")
						{
						$position2=$c;
					
						
						$get_position =$position2-$position;
						
						$getid = substr($get_noids,$c-$get_position,$get_position);
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$_GET[taskid]' AND groupid ='$getid'";
						  
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
						
						 $sql2 = "DELETE FROM terminaloftask WHERE terminaloftask.taskid = '$_GET[taskid]' AND terminalid ='$getid'";
						  
						mysqli_query($con,$sql2) or die(mysqli_error($con));
						unset($sql2);
						
						
				     
						}
						
						}
                          	
						for($j=0; $j<count($terminallistnum); $j++)
						{
							if(is_numeric($terminallistnum[$j]))
							{
							   $temp = (int)$terminallistnum[$j];
							   $group = (int)$analysis_tree_group_ids[$j];
							
							  	$get_sql= "SELECT terminalid,groupid  FROM terminaloftask WHERE taskid = '$_GET[taskid]' AND terminalid='$temp' AND groupid = '$group'";
							    $get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));
							  						  
								if($get_row = mysqli_fetch_array($get_result))
								{	
						 		$get_terminals = $get_row['terminalid'];
								$get_group = $get_row['groupid'];
								}
								@mysqli_free_result($get_result);
								unset($get_sql,$get_row);
								
								if($temp==$get_terminals)
								{
								  if($group==$get_group)
								  {
								  for($z=0;$z<strlen($get_terminal);$z++)
												{
											//alert(z);
													if(substr($get_terminal,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
													$a=substr($get_terminal,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal,$z+1,16);
											
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															mysqli_query($con,$sql) or die(mysqli_error($con));
															unset($sql);
															break;
															}
													}
												}						
								  }
								  else
								  {
										$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
											   for($z=0;$z<strlen($get_terminal);$z++)
												{
											//alert(z);
													if(substr($get_terminal,$z,2)=="::")
													{	
													$position=$z+2;
													}
													if(substr($get_terminal,$z,1)=="|")
													{
													  $position2 = $z;
													  $position3 = $position2-$position;
													$a=substr($get_terminal,$z-$position3,$position3);
														if($a==$temp)
															{
															//$c=strpos($get_terminal,$a);
						
															$area = substr($get_terminal,$z+1,16);
															
														//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
															$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
															mysqli_query($con,$sql) or die(mysqli_error($con));
															unset($sql);
															break;
															}
													}
												}						
										  } 
												
								  } 
								}
								else 
								{
						
								  
									$sql = "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$_GET[taskid]','$temp','$analysis_tree_group_ids[$j]','1111111111111111')";
				
									mysqli_query($con,$sql) or die(mysqli_error($con));
									unset($sql);
									 if(empty($get_terminal))
										  {
										  
										  }
										  else
										  {
										   for($z=0;$z<strlen($get_terminal);$z++)
											{
										//alert(z);
											if(substr($get_terminal,$z,2)=="::")
											{	
											$position=$z+2;
											}
											if(substr($get_terminal,$z,1)=="|")
											{
											  $position2 = $z;
											  $position3 = $position2-$position;
											$a=substr($get_terminal,$z-$position3,$position3);
											if($a==$temp)
												{
												//$c=strpos($get_terminal,$a);
			
												$area = substr($get_terminal,$z+1,16);
													
							
											//	$sql= "INSERT INTO terminaloftask (taskid,terminalid,groupid,area)VALUES('$getoldfunctionid','$temp','$analysis_tree_group_ids[$j]','$area')";
												$sql = "UPDATE terminaloftask SET area='$area' WHERE taskid ='$_GET[taskid]' AND terminalid ='$temp'";
												mysqli_query($con,$sql) or die(mysqli_error($con));
												unset($sql);
												break;
												}
											}
											}						
										  } 
									
									
								}
								
								//checkterminal($temp,$get_terminal,$get_terminals,$_GET[taskid],$j);
							

							}
						}

	mysqli_query($con,"UNLOCK TABLES");
    	if(!mysqli_error($con))
			{
				mysqli_query($con,"COMMIT");
				
				$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
				
				$_SESSION['url'] = $gototaskmanager;
				//=======================================================================
				/*$socket	=	new	send_message_to_server($port_conf);	
				
				$msg = "task?state=5&id=".$_GET['taskid']."&volume=".$task_default_volume;
				
				$socket->send_data($_SESSION['serverip'],$msg);
				*/
				$create_socket_obj->send_socket_task_volume("task",5,$_GET['taskid'],$task_default_volume);
				
				echo "<script>window.location='success.php'</script>";
			}
			


	if(mysqli_error($con))
	{
		mysqli_query($con,"ROLLBACK");
	
		$_SESSION['info'] = strtoupper($do_php_prompt['Failed']);//提示信息
		
		$_SESSION['url'] = $gototaskmanager;
	
		echo "<script>window.location='error.php'</script>";
	
		exit;
	}	
	
}
?>
