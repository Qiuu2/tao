<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require_once('inc/common.php');
require_once('inc/config.php');
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
	$smarty->assign("set_shotcut",$set_shotcut);
	$smarty->assign("Setterminalkey",$Setterminalkey);
	//终端类型
	$type_id = -1;
	//获取终端id
	$id = "";  //  快捷键id
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
	}
	$getact = "";  //  快捷键id
	if(isset($_GET['getact']))
	{
		$getact = trim($_GET['getact']);
	}
	
	$flag = 0;
	if(isset($_GET['flag']))
	{
	  $flag = trim($_GET['flag']);
	}
	$smarty->assign("flag",$flag);
	$get_terminal_id = "";   //终端id
	if(isset($_GET['terminal_id']))
	{
		$get_terminal_id = trim($_GET['terminal_id']);
	}
	
	$shotcutname = "";
	if(isset($_GET['shotcutname']))
	{
	  $shotcutname = trim($_GET['shotcutname']);
	}
	$smarty->assign("shotcutname",$shotcutname);
	//判断终端类型号
	$type_result = mysqli_query($con,"SELECT typeid FROM terminal WHERE terminal.id = '$get_terminal_id'");
	
	if( $type_row = mysqli_fetch_array($type_result) )
	{
		if($type_row['typeid'] == -1)
		{
			echo "<script>alert('".$set_shotcut['Unknown_terminal_t​​ype']."');</script>";
			echo "<script>window.history.back();</script>";
			exit;
		}
		else
		{
			$type_id  = $type_row['typeid'];
		}
	}
	
	@mysqli_free_result($type_result);
	
	unset($type_row);
	
	//判断终端类型、获取终端快捷键数
	$sql_type = "SELECT isdecode, isencode FROM terminaltype WHERE terminaltype.id = '$type_id'";	
	$result_type = mysqli_query($con,$sql_type) or die(mysqli_error($con));
	$row_type = mysqli_fetch_array($result_type);
	if($row_type['isencode'] == 1)//编码
	{
		//获取该终端的快捷键值
	/*$sql_key = "SELECT name FROM audioserver.key WHERE key.terminaltype = '$type_id' order by name desc";
		$result_key = mysqli_query($con,"$sql_key") or die(mysqli_error($con));
		while($row_key = mysqli_fetch_array($result_key))
		{
			$key_num[] = $row_key['name'];
		}
		*/
		if($type_id==8||$type_id==25||$type_id==31||$type_id==32)
		{
			$gg=11;	
		}
		else if($type_id==26)
		 	$gg=10;
		else if($type_id==28||$type_id==30||$type_id==5||$type_id==11)
			$gg=10;	
		else if($type_id==33)
			$gg=3;
		else if($type_id==44&&$getact==1)
			$gg=3;	
		else if($type_id==40)
			$gg=2;	
		else if($type_id==2)
			$gg=31;		
		else
		   $gg=9;
		   
		for($k=0;$k<$gg;$k++)
		{
			if($type_id==8||$type_id==25||$type_id==26||$type_id==2||$type_id==31||$type_id==32||$type_id==22)
			{
				$key_num[] =$k;
			}
			else if($type_id==28||$type_id==30||$type_id==5||$type_id==11)	
			{
				if($k==9)
				$key_num[] =30;
				else
				$key_num[] =$k+1;
			}
			else if($type_id==40)
			{
				if($k==0)
					$key_num[]=0;
				else
					$key_num[]=30;
			}
			else if($type_id==33)
			{
				if($k==2)
				$key_num[] =30;
				else
				$key_num[] =$k+1;
			}
			else
				$key_num[]=$k+1;
		}
		
		$smarty->assign("key_num",$key_num);
		
		//@mysqli_free_result($result_key);
		
		unset($sql_key,$row_key,$key_num);
		if($PAGGING_FLAG==1)
		{
			if($type_id ==44||$flag==1||$flag==2)
			{
				$type="2,3,12,13,14,17,21,28,33,35,40,41,44";
			}	
  		//	$type="1,2,3,4,5,12,13,14,17,19,23,26,28,30,33,34,35,36,37,38,39,40,44";
			else
			{
				$type=get_terminal_type(3,$set_shotcut['Unknown_terminal_t​​ype'],0,0);
			}
		}
		else
		{
			//读取解码终端
		if($type_id==33||$type_id ==44)
			$type="2,3,12,13,14,17,21,28,33,35,40,41,44";
  			//$type="1,2,3,4,5,12,13,14,17,19,23,26,28,30,33,34,35,36,37,38,39,40,44";
			else
			{
				$type=get_terminal_type(3,$set_shotcut['Unknown_terminal_t​​ype'],0,0);
			}
		}
		
		$decode_terminal = get_terminallistoggroup2($type, $get_terminal_id);
		$smarty->assign("type_id",$type_id);
	//	$decode_terminal = get_terminallist5($type, $get_terminal_id);
	}
	else if($row_type['isencode'] == 0)
	{
		//不能编码、则不能输入
		echo "<script>alert('".$set_shotcut['does_not_support_shortcut_paging']."');</script>";
		echo "<script>window.history.back();</script>";
		exit;
	}

	@mysqli_free_result($result_type);
	
	unset($sql_type,$row_type);
	
	
	$shotcut_sql = "select terminalkey.id,terminalkey.name,terminalkey.terminalid,terminalkey.key from terminalkey where id='$id'";
	
	$shotcut_result	=	mysqli_query($con,$shotcut_sql) or die("Execute error".mysqli_error($con));

	if($shotcut_row = mysqli_fetch_array($shotcut_result))
	{
		
		$shotcut_info = array(
									"id"=>$shotcut_row['id'],"name"=>$shotcut_row['name'],
									"terminalid"=>$shotcut_row['terminalid'],"key"=>$shotcut_row['key']
							   );
	}
	$smarty->assign("terminal_info",$shotcut_info);
	//@mysqli_free_result($shotcut_row);
	unset($shotcut_sql,$shotcut_row);	
	
	$sql = "select terminalid,groupid from terminalkeymap where keyid='$id'";
	$result	=	mysqli_query($con,$sql) or die("Execute error".mysqli_error($con));
	while($row = mysqli_fetch_array($result))
	{
		$shotcutinfo[] = array(
									"terminalid"=>$row['terminalid'],"groupid"=>$row['groupid']
									
							   );

	}

	$smarty->assign("terminalinfo",$shotcutinfo);
	//@mysqli_free_result($row);
	unset($sql,$row);
	

	$smarty->assign("decode_terminal",$decode_terminal);
	$smarty->assign("getact",$getact);
	$smarty->assign("id",$id);
	$smarty->assign("get_terminal_id",$get_terminal_id);
	
	unset($decode_terminal,$get_terminal_id);
	
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	
	$smarty->display("TerminalManager/modifyterminalkey.html");
}
?>