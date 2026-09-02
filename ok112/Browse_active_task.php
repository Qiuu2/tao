<?php
if (!session_id()) session_start();

//同步数据库时区
//mysqli_query($con,"SET time_zone = '-8:00'");

//设置PHP时区
//date_default_timezone_set("PRC");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

//验证是否失效
require_once("verify_user_sessionin_valid.php");

verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	header("location:login.php");
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");

	$smarty->assign("language",$_SESSION['language']);

	$smarty->assign("terminal_function",$terminal_function);

	$smarty->assign("Admmanager",$Admmanager);

	$smarty->assign("Searchform",$Searchform);

	$smarty->assign("Revise",$Revise);

	//require('editor.php');
	
	$smarty->assign('descriptionarea',$descriptionarea);//导入分页类
	
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(have_rights("folderpriv") || is_admin($con,$_SESSION['username']))
	{
		$smarty->assign("is_right",1);
	}
	else
	{
		$smarty->assign("is_right",0);
	}
	
	if(!isset($_GET['page']))
	{
	    $page=1;
	
	    $start=0;
	}
	else 
	{
	    $page=$_GET['page'];
	
	    $start=($_GET['page']-1)*$NumOfPage;
	}
		$getid=0;
		if(isset($_GET['id']))
		{
			$getid = trim($_GET['id']);
		}
	$gettasktype=get_current_tasktype($getid);

	$get_str_date = 0;//获取星期几
	$get_int_date = 0;//获取天数差
	$get_differ_week_day = 0;//返回与当前的差数
	$get_current_day = get_current_week(time());//返回当前星期几
	$get_y=date("Y");
	$get_m=date("m");
	$get_d=date("d");
		
	if(isset($_GET['get_str_date']))
	{
		$get_str_date = trim($_GET['get_str_date']);
		if($get_str_date!=0)
		$get_differ_week_day = ($get_str_date - $get_current_day);
	}
	
	if(isset($_GET['get_int_date']))
	{
		$get_int_date = trim($_GET['get_int_date']);
		if( !is_numeric($get_int_date) )
		{
			echo "<script>alert('".$terminal_function['enter_number']."');</script>";	
			echo "<script>window.history.back();</script>";
			exit;
		}
	}
	$temp_day=$get_d+$get_differ_week_day;
	
	if($temp_day<=0)
	{
		if($get_m==3)
		{
			$get_m=$get_m-1;
			 if ($get_y%4==0 && !$get_y%100==0 || $get_y%400==0)
      		{
			  $get_d=29+$temp_day;
			}
			else
			{
			$get_d=28+$temp_day;
			}
		}
		else if($get_m==1)
		{
			$get_m=12;
			$get_y=$get_y-1;
			$get_d=31+$temp_day;
		}
		else if($get_m==2||$get_m==4||$get_m==6||$get_m==8||$get_m==9||$get_m==11)
		{
		 $get_m=$get_m-1;
		 $get_d=31+$temp_day;
		}
		else if($get_m==5||$get_m==7||$get_m==10||$get_m==12)
		{
		 $get_m=$get_m-1;
		 $get_d=30+$temp_day;
		}
	}
	else
	{
		if($get_m==2)
		{
			 if ($get_y%4==0 && !$get_y%100==0 || $get_y%400==0)
      		{
				if($temp_day>29)
				{
					$get_m=$get_m+1;
					 $get_d=$temp_day-29;
				}
				else
				{
				$get_d=$temp_day;
				}
			}
			else
			{
				if($temp_day>28)
				{
					$get_m=$get_m+1;
					 $get_d=$temp_day-28;
				}
				else
				{
				$get_d=$temp_day;
				}
			}
		}
		else if($get_m==12)
		{
			if($temp_day>31)
			{
				$get_m=1;
				$get_y=$get_y+1;
				$get_d=$temp_day-31;
			}
			else
			{
			$get_d=$temp_day;
			}		
		}
		else if($get_m==4||$get_m==6||$get_m==9||$get_m==11)
		{
			if($temp_day>30)
			{
				$get_m=$get_m+1;
				$get_d=$temp_day-30;
			}
			else
				{
				$get_d=$temp_day;
				}
		}
		else if($get_m==1||$get_m==3||$get_m==5||$get_m==7||$get_m==8||$get_m==10)
		{
			if($temp_day>31)
			{
				 $get_m=$get_m+1;
				 $get_d=$temp_day-31;
		 	}
			else
			{
			$get_d=$temp_day;
			}
		}
	}
	if($get_m<10)
	$get_m='0'.intval($get_m);
	if($get_d<10)
	$get_d='0'.intval($get_d);
	
	$get_ymd=$get_y.'-'.$get_m.'-'.$get_d;
	
	$userid=$_SESSION['userid'];
	if( $get_str_date == 0 && $get_int_date == 0 )//表示查看当天
	{
		//读取所有任务
		if($_SESSION['username']=="admin")
		{
			$task_sql = "SELECT DISTINCT * FROM task WHERE task.projectstate=0 AND task.startdate <= DATE_ADD(CURDATE(),INTERVAL 0 DAY) AND 
		
			task.enddate >= DATE_ADD(CURDATE(),INTERVAL 0 DAY) AND 
			
			SUBSTRING(task.exemodel,CASE WHEN WEEKDAY(DATE_ADD(CURDATE(),INTERVAL 0 DAY))+2 = 8 THEN 1 
			
			ELSE WEEKDAY(DATE_ADD(CURDATE(),INTERVAL 0 DAY))+2 END ,1) 
			
			AND task.tasktype IN($gettasktype) AND sec_task_id=0 ORDER BY task.info,task.playtime";
		}
		else
		{
			$task_sql = "SELECT DISTINCT * FROM task WHERE task.projectstate=0 AND task.startdate <= DATE_ADD(CURDATE(),INTERVAL 0 DAY) AND 
		
			task.enddate >= DATE_ADD(CURDATE(),INTERVAL 0 DAY) AND 
			
			SUBSTRING(task.exemodel,CASE WHEN WEEKDAY(DATE_ADD(CURDATE(),INTERVAL 0 DAY))+2 = 8 THEN 1 
			
			ELSE WEEKDAY(DATE_ADD(CURDATE(),INTERVAL 0 DAY))+2 END ,1) 
			
			AND task.tasktype IN($gettasktype) AND sec_task_id=0  AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') ORDER BY task.info,task.playtime";
		}
	
					

	}
	else if( $get_str_date != 0 && $get_int_date == 0 )
	{
	
		if($_SESSION['username']=="admin")
		{
		$task_sql = "
					SELECT DISTINCT * FROM task WHERE task.projectstate='0' AND
	
					task.startdate <= DATE_ADD(CURDATE(),INTERVAL ".$get_differ_week_day." DAY) 
										
					AND 
										
					task.enddate >= DATE_ADD(CURDATE(),INTERVAL ".$get_differ_week_day." DAY) 
										
					AND 
										
					SUBSTRING(task.exemodel, 
										
					CASE 
										
					WHEN WEEKDAY(DATE_ADD(CURDATE(),INTERVAL ".$get_differ_week_day." DAY))+2 = 8 THEN 
										
					1 
										
					ELSE 
										
					WEEKDAY(DATE_ADD(CURDATE(),INTERVAL ".$get_differ_week_day." DAY))+2 
										
					END 
										
					,1) 
									
					AND  task.tasktype IN($gettasktype) AND sec_task_id='0'
					ORDER BY task.info,task.playtime  	
				";
		}
		else
		{
			$task_sql = "
			SELECT DISTINCT * FROM task WHERE task.projectstate='0' AND

			task.startdate <= DATE_ADD(CURDATE(),INTERVAL ".$get_differ_week_day." DAY) 
								
			AND 
								
			task.enddate >= DATE_ADD(CURDATE(),INTERVAL ".$get_differ_week_day." DAY) 
								
			AND 
								
			SUBSTRING(task.exemodel, 
								
			CASE 
								
			WHEN WEEKDAY(DATE_ADD(CURDATE(),INTERVAL ".$get_differ_week_day." DAY))+2 = 8 THEN 
								
			1 
								
			ELSE 
								
			WEEKDAY(DATE_ADD(CURDATE(),INTERVAL ".$get_differ_week_day." DAY))+2 
								
			END 
								
			,1) 
							
			AND  task.tasktype IN($gettasktype) AND sec_task_id='0' AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid')
			ORDER BY task.info,task.playtime  	
		";
		}
	}
	else if( $get_str_date == 0 && $get_int_date != 0 )
	{
		if( $get_int_date > 0 )
		{
			if($_SESSION['username']=="admin")
			{
				$task_sql = "SELECT DISTINCT * FROM task WHERE task.projectstate='0' AND
	
				task.startdate <= DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY) 
									
				AND 
									
				task.enddate >= DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY) 
									
				AND 
									
				SUBSTRING(task.exemodel, 
									
				CASE 
									
				WHEN WEEKDAY(DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY))+2 = 8 THEN 
									
				1 
									
				ELSE 
									
				WEEKDAY(DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY))+2 
									
				END 
									
				,1) 
									
				AND 
				(
					CASE 

					WHEN task.tasktype = 1 && task.projectstate =0 THEN
					
					task.tasktype = 1 OR task.tasktype IN($gettasktype) OR 

					(task.tasktype =5 AND task.prepower = 0 AND task.info = '' AND task.cmd = 0)
					
					WHEN task.tasktype != 1 THEN
					
					task.tasktype IN ($gettasktype) OR (task.tasktype =5 AND task.prepower = 0 AND task.info = '' AND task.cmd = 0)
					
					END
				)
				
				ORDER BY task.info,task.playtime  
				
			";
			}
			else
			{
				$task_sql = "SELECT DISTINCT * FROM task WHERE task.projectstate='0'  AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') AND
	
				task.startdate <= DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY) 
									
				AND 
									
				task.enddate >= DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY) 
									
				AND 
									
				SUBSTRING(task.exemodel, 
									
				CASE 
									
				WHEN WEEKDAY(DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY))+2 = 8 THEN 
									
				1 
									
				ELSE 
									
				WEEKDAY(DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY))+2 
									
				END 
									
				,1) 
									
				AND 
				(
					CASE 

					WHEN task.tasktype = 1 && task.projectstate =0 THEN
					
					task.tasktype = 1 OR task.tasktype IN($gettasktype) OR 

					(task.tasktype =5 AND task.prepower = 0 AND task.info = '' AND task.cmd = 0)
					
					WHEN task.tasktype != 1 THEN
					
					task.tasktype IN ($gettasktype) OR (task.tasktype =5 AND task.prepower = 0 AND task.info = '' AND task.cmd = 0)
					
					END
				)
				
				ORDER BY task.info,task.playtime  
				
			";
			}
		
		}
		else if( $get_int_date < 0 )
		{
			if($_SESSION['username']=="admin")
			{
				$task_sql = "
				SELECT DISTINCT * FROM task WHERE task.projectstate='0' AND

				task.startdate <= DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY) 
									
				AND 
									
				task.enddate >= DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY) 
									
				AND 
									
				SUBSTRING(task.exemodel, 
									
				CASE 
									
				WHEN WEEKDAY(DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY))+2 = 8 THEN 
									
				1 
									
				ELSE 
									
				WEEKDAY(DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY))+2 
									
				END 
									
				,1) 
									
				AND 
				(
					CASE 
					
					WHEN task.tasktype = 1 && task.projectstate =0 THEN
					
					task.tasktype = 1 OR task.tasktype IN($gettasktype) OR 

					(task.tasktype =5 AND task.prepower = 0 AND task.info = '' AND task.cmd = 0)
					
					WHEN task.tasktype != 1 THEN
					
					task.tasktype IN ($gettasktype) OR (task.tasktype =5 AND task.prepower = 0 AND task.info = '' AND task.cmd = 0)
					
					END
				)
				
				ORDER BY task.info,task.playtime  
				
			";
			}
			else
			{
				$task_sql = "
						SELECT DISTINCT * FROM task WHERE task.projectstate='0'  AND task_user_id IN (SELECT id FROM book_admin WHERE id='$userid') AND 
		
						task.startdate <= DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY) 
											
						AND 
											
						task.enddate >= DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY) 
											
						AND 
											
						SUBSTRING(task.exemodel, 
											
						CASE 
											
						WHEN WEEKDAY(DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY))+2 = 8 THEN 
											
						1 
											
						ELSE 
											
						WEEKDAY(DATE_ADD(CURDATE(),INTERVAL ".$get_int_date." DAY))+2 
											
						END 
											
						,1) 
											
						AND 
						(
							CASE 
							
							WHEN task.tasktype = 1 && task.projectstate =0 THEN
							
							task.tasktype = 1 OR task.tasktype IN($gettasktype) OR 

							(task.tasktype =5 AND task.prepower = 0 AND task.info = '' AND task.cmd = 0)
							
							WHEN task.tasktype != 1 THEN
							
							task.tasktype IN ($gettasktype) OR (task.tasktype =5 AND task.prepower = 0 AND task.info = '' AND task.cmd = 0)
							
							END
						)
						
						ORDER BY task.info,task.playtime  
						
					";
			}
			
		}
	}

	$task_result = mysqli_query($con,$task_sql) or die(mysqli_error($con));
	
	$Num	=	mysqli_num_rows($task_result);
	
	//$task_result = mysqli_query($con,$task_sql." LIMIT $start, $NumOfPage") or die(mysqli_error($con));
	$task_array=array();
	while( $task_row = mysqli_fetch_array($task_result) )
	{
	
		if($get_differ_week_day==0)
		{
			$state=$task_row['state'];
		}
		else if($get_differ_week_day<0)
		{
			$state=5;
		}
		else if($get_differ_week_day>0)
		{
			$state=6;	
		}
		
		$task_array[] = array(
								"taskid"=>$task_row['taskid'],"taskname"=>$task_row['taskname'],"projectstate"=>$task_row['projectstate'],
								
								"timelengthtype"=>$task_row['timelengthtype'],"timelength"=>$task_row['timelength'],"tasktype"=>$task_row['tasktype'],"info"=>$task_row['info'],
								
								"state"=>$state,"startdate"=>$task_row['startdate'],"enddate"=>$task_row['enddate'],
								
								"playtime"=>$task_row['playtime'],"exemodel"=>$task_row['exemodel'],"tasktype"=>$task_row['tasktype'],"taskuserid"=>$task_row['task_user_id'],"disableday"=>$task_row['disableday']
							 );
	}

	$smarty->assign("info",$task_array);

	$smarty->assign("getid",$getid);
	$smarty->assign("username",$_SESSION['username']);
	@mysqli_free_result($task_result);
	unset($task_array,$task_row,$task_sql);
	/*
	//状态分页
	if($Num != 0)
	{
		require_once("pagination.class.php");
		
		$p = new pagination;
		
		$p->Items($Num);
		
		$p->limit($NumOfPage);
		
		$p->target("?id=".$_GET['id']."&");
		
		$p->currentPage($_GET['page']);
		
		$p->adjacents(3);
		
		$smarty->assign("pagestr",$p->show());
	}
	*/
	//输出session
	//$sec=time()-strtotime(date("Y-m-d"));
	$hours=date("G");
	$minites=date("i");
	$sec=date("s");

	$smarty->assign("hours",$hours);
	$smarty->assign("minites",$minites);
	$smarty->assign("sec",$sec);
	$smarty->assign("start",$start);
	$smarty->assign("get_str_date",$get_str_date);
	$smarty->assign("get_int_date",$get_int_date);
	$smarty->assign("get_ymd",$get_ymd);
	$smarty->assign("current_week_day",$get_current_day);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("Browse_active_task/Browse_active_task.html");	
}
function get_current_tasktype($getid)
{
		switch($getid)
		{
			case 0:
			$task_type='1,2,3,5,10,15,17,19';
			break;
			case 1:
			$task_type='1';
			break;
			case 2:
			$task_type='2';
			break;
			case 3:
			$task_type='3';
			break;
			case 4:
			$task_type='5';
			break;
			case 5:
			$task_type='10';
			break;
			case 6:
			$task_type='15,17,19';
			break;
		}
	return $task_type;
}
function get_current_week($data)
{
	$week   =  date( "D",$data);
	
	switch($week)
	{
		case "Mon":
		   	$current = 1;
			break;
		case "Tue":
			 $current = 2;
			 break;
		case "Wed":
			$current = 3; 
			break;
		case "Thu":
			$current = 4;
			break;
		case "Fri":
			$current = 5; 
			break;
		case "Sat":
			$current = 6; 
			break;
		case "Sun":
			$current = 7;
			break;
	}
	return $current;
}  

?>
