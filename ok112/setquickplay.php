<?php
if (!session_id()) session_start();

header("content-type:text/html; charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require_once('inc/common.php');
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
	$smarty->assign("media_task_add",$media_task_add);
	$smarty->assign("Belladdtask",$Belladdtask);
	/*动态显示页面文本内容*/
	$terminal_id = "";
	if(isset($_GET['id']))
	{
		$terminal_id = trim($_GET['id']);
		$_SESSION['setterminalid'] = $terminal_id;
	}
	
	if($terminal_id == "")
	{
		$terminal_id = $_SESSION['setterminalid'];
	}

	$type=get_terminal_type(3,$do_php_prompt['Terminal_not_support'],0,0);
	
	$tsql = "select typeid from terminal where id = '$terminal_id'";
	$tresult = mysqli_query($con,$tsql) or die(mysqli_error($con));
	if($row = mysqli_fetch_array($tresult))
	{
		$smarty->assign("typeid",$row['typeid']);
	}
	
	$terminalist = get_quick_terminal($type,$terminal_id);
	$userid=$_SESSION['userid'];
	$results = mysqli_query($con,"SELECT usergroup.level FROM usergroup WHERE id IN(SELECT usergroupid FROM book_admin WHERE id IN($userid))");
	if($row = mysqli_fetch_array($results))
	{
		$getlevel=$row['level'];
	}
		
	$res = mysqli_query($con,"SELECT typeid FROM terminal WHERE id=$terminal_id");
	if($row = mysqli_fetch_array($res))
	{
		$get_result=0;
		$typeid=$row['typeid'];
		$re = mysqli_query($con,"SELECT count(keyid) FROM terminalkeymaptask WHERE terminalid=$terminal_id");
		if($rows = mysqli_fetch_array($re))
		{
			if($typeid==17||$typeid==41)
			{
				if($rows['0']>=200)
				{
					echo "<script>alert('".$do_php_prompt['Max_num']."');</script>";
					echo "<script>window.history.back();</script>";
				}
				else 
				{
					for($i=1;$i<=200;$i++)
					{
						$res = mysqli_query($con,"SELECT keyid FROM terminalkeymaptask WHERE terminalid=$terminal_id AND keyid=$i");
							if(mysqli_num_rows($res)<=0)
							{
								$get_result=$i;
								break;
							}
							@mysqli_free_result($res);
								
					}
				}				
			}
			else if($typeid==38||$typeid==34 ||$typeid==1 || $typeid==8 ||$typeid==23||$typeid==11||$typeid==24||$typeid==5)
			{
				if($rows['0']>=1)
				{
					echo "<script>alert('".$do_php_prompt['Max_num']."');</script>";
					echo "<script>window.history.back();</script>";
				}
				else
				{
				
					$get_result=0;
				}
			}
			else if($typeid==2||$typeid==11)
			{
				if($rows['0']>=31)
				{
					echo "<script>alert('".$do_php_prompt['Max_num']."');</script>";
					echo "<script>window.history.back();</script>";
				}
				else
				{
				
					for($i=0;$i<=30;$i++)
					{
						$res = mysqli_query($con,"SELECT keyid FROM terminalkeymaptask WHERE terminalid=$terminal_id AND keyid=$i");
							if(mysqli_num_rows($res)<=0)
							{
								$get_result=$i;
								break;
							}
							@mysqli_free_result($res);
								
					}
				}
			}
			else
			{
			
				if($rows['0']>=9)
				{
					echo "<script>alert('".$do_php_prompt['Max_num']."');</script>";
					echo "<script>window.history.back();</script>";
				}
				else 
				{
					for($i=1;$i<=9;$i++)
					{
						$res = mysqli_query($con,"SELECT keyid FROM terminalkeymaptask WHERE terminalid=$terminal_id AND keyid=$i");
							if(mysqli_num_rows($res)<=0)
							{
								$get_result=$i;
								break;
							}
							@mysqli_free_result($res);
								
					}
				}			
			}
	
		}
	}
	
	$adm_terminal_sql = "select id, terminalname,typeid from terminal where terminal.typeid in(22,32,0)";
	$adm_terminal_result = mysqli_query($con,$adm_terminal_sql) or die(mysqli_error($con));
	if(mysqli_num_rows($adm_terminal_result) > 0)
	{
		while($adm_terminal_row = mysqli_fetch_array($adm_terminal_result))
		{
			$terminal_info[] = array("id"=>$adm_terminal_row['id'],"typeid"=>$adm_terminal_row['typeid'],"terminalname"=>$adm_terminal_row['terminalname']);	
		}
		$smarty->assign("terminal_info",$terminal_info);
		@mysqli_free_result($adm_terminal_result);
		unset($adm_terminal_sql,$adm_terminal_row,$terminal_info);
	}
	else
	{
	
	}
	$ledtype=get_terminal_type(14,$do_php_prompt['Terminal_not_support'],0,0);
	$ledlist = create_led_tree_str($ledtype);
	
	$smarty->assign("ledlist",$ledlist);
	$smarty->assign("get_result",$get_result);
	$smarty->assign("userid",$userid);
	$smarty->assign("getlevel",$getlevel);
    $smarty->assign("terminal_id",$terminal_id);
	$smarty->assign("terminalist",$terminalist);
	$filelist = get_filelist($_SESSION['username']);
	$smarty->assign("filelist",$filelist);	

	$smarty->display("TerminalManager/set_task_quickplay.html");
}
?>
