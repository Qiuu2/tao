<?php 
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

//显示多语言
require_once("language/".$_SESSION['language'].".php");

function get_terminallist1($type, $getterminalid)
{
	global $do_php_prompt;
	global $con;
	$nostream = $do_php_prompt['unzoned_terminal'];	
	
	$sql="SELECT DISTINCT * FROM serverplaystream";
	
	$selectterminalstr = "<tree id=\"0\">";
	
	$str= "";	
	
	//$fp = fopen("get_terminallist.log","w");		
	
	$resultstream=	mysqli_query($con,$sql);
	
	while ($rowstream = mysqli_fetch_array($resultstream))
	{			
		$streamid = $rowstream['streamid'];		
	
		$str= "";
		
	
		$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.typeid in $type and terminal.groupid=$streamid AND terminal.id != '$getterminalid' ORDER BY CONVERT(terminal.terminalname USING utf8)");
		//fwrite($fp,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.typeid in $type and terminal.groupid=$streamid AND terminal.id != '$getterminalid'");		
		//fwrite($fp,"\n");		
		
		while ($rowterminal = mysqli_fetch_array($resultterminal)) 
		{	
			$str .= "<item text=\"".$rowterminal['terminalname']."\" id=\""."$rowterminal[id]"."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs_open.gif\" im2=\"iconSafe.gif\" > </item>"	;
		}
		
		if(!empty($str))
		{
				$selectterminalstr .="<item text=\"".$rowstream['name']."".$do_php_prompt['terminal_group']."\" id=\"stream_".$streamid."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs_open.gif\" im2=\"iconSafe.gif\" >";
		
				$selectterminalstr .=$str;
		
				$selectterminalstr .= "</item>"; 
		}		
		@mysqli_free_result($resultterminal);				
	}
	
	@mysqli_free_result($resultstream);
	//输出未分区终端terminal.groupid = 0 为未分区
	
	$str = "";
	
	$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE  terminal.typeid in $type and terminal.groupid = '0' AND terminal.id != '$getterminalid' ORDER BY CONVERT(terminal.terminalname USING utf8)");
	
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		$str .= "<item text=\"".$rowterminal['terminalname']."\" id=\""."$rowterminal[id]"."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs_open.gif\" im2=\"iconSafe.gif\" >  </item>"	;
	}
	
	if(!empty($str))
	{
			$selectterminalstr .= "<item text=\"".$nostream."\" id=\"".$nostream."\" open=\"1\" im0=\"tombs.gif\" im1=\"tombs_open.gif\" im2=\"iconSafe.gif\" >";
	
			$selectterminalstr .=$str;
	
			$selectterminalstr .= "</item>"; 
	}	
	
	//$selectterminalstr .="</item>"; 
	$selectterminalstr .="</tree>"; 
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	@mysqli_free_result($resultterminal);
	
	@mysqli_free_result($resultstream);
	
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	
	unset($rowterminal,$rowstream,$sql);	
	
	return $selectterminalstr;
}

function get_terminallist($type, $getterminalid)
{
	

	return get_grouped_terminal($type);


	global $do_php_prompt;
	global $con;
	//读取用户终端
	$user_terminal_array = array();
	
	$user_terminal_str = "";
	
	$user_sql = "";
	$user_isornot_admin = trim($_SESSION['admin_id']);
	$user_name = trim($_SESSION['username']);
	
	if($user_isornot_admin == 'administrator')
	{
		//读取所有终端ID
		$user_result = mysqli_query($con,'SELECT id FROM terminal') or die(mysqli_error($con));
		
		if(mysqli_num_rows($user_result) > 0)
		{
			while($user_row = mysqli_fetch_array($user_result))
			{
				$user_terminal_array[] = $user_row['id'];
			}
			$user_terminal_str = implode(",",$user_terminal_array);
			
			unset($user_terminal_array);
		}
		else
		{
			echo "<script>alert('".$do_php_prompt['system_not_check_termials']."');</script>";
			
			echo "<script>window.history.back();</script>";
			
			exit;
		}
	}
	else
	{
		$user_sql = "SELECT terminalid FROM userterminal WHERE userterminal.userid = ";
		
		$user_sql.= "(SELECT id FROM book_admin WHERE book_admin.username = '$user_name') ";
		
		$user_result = mysqli_query($con,$user_sql) or die(mysqli_error($con));
		
		if(mysqli_num_rows($user_result) <= 0)
		{
			echo "<script>alert('".$do_php_prompt['user_not_operate_terminals']."');</script>";
			
			echo "<script>window.history.back();</script>";
			
			exit;
		}
		else
		{
			while($user_row = mysqli_fetch_array($user_result))
			{
				$user_terminal_array[] = $user_row['terminalid'];
			}
			
			$user_terminal_str = implode(",",$user_terminal_array);
			
			unset($user_terminal_array);
		}
	}
	
	@mysqli_free_result($user_result);
	unset($user_sql,$user_row);
	//echo $user_terminal_str;

	$nostream = $do_php_prompt['unzoned_terminal'];	
		
	$sql="SELECT DISTINCT * FROM serverplaystream";
	
	$selectterminalstr = "<tree id=\\\"0\\\">";
	
	$str= "";
		
	//$fp = fopen("get_terminallist.log","w");		
	
	$resultstream=	mysqli_query($con,$sql);

	while ($rowstream = mysqli_fetch_array($resultstream))
	{			
		$streamid = $rowstream['streamid'];		
		
		$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.typeid IN $type and terminal.groupid=$streamid AND terminal.id != '$getterminalid' AND terminal.id IN ($user_terminal_str) ORDER BY CONVERT(terminal.terminalname USING utf8)");
		//fwrite($fp,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.typeid in $type and terminal.groupid=$streamid AND terminal.id != '$getterminalid'");		
		//fwrite($fp,"\n");	
	
		while ($rowterminal = mysqli_fetch_array($resultterminal)) 
		{	
			$str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\"".$rowterminal['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>"	;
		}
		
		if(!empty($str))
		{
				$selectterminalstr .="<item text=\\\"".$rowstream['name']."".$do_php_prompt['terminal_group']."\\\" id=\\\"stream_".$streamid."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
				
				$selectterminalstr .=$str;
				
				$selectterminalstr .= "</item>"; 
		}		
		@mysqli_free_result($resultterminal);				
	}
	
	@mysqli_free_result($resultstream);
	
	//输出未分区终端terminal.groupid = 0 为未分区
	
	$str = "";
	
	$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE  terminal.typeid in $type and terminal.groupid = '0' AND terminal.id != '$getterminalid' AND terminal.id IN ($user_terminal_str) ORDER BY CONVERT(terminal.terminalname USING utf8)");
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		$str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\"".$rowterminal['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>"	;
	}
	
	if(!empty($str))
	{
		$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"".$nostream."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		$selectterminalstr .=$str;
		$selectterminalstr .= "</item>"; 
	}	
	
	//$selectterminalstr .="</item>"; 
	$selectterminalstr .="</tree>"; 
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	@mysqli_free_result($resultterminal);
	
	
	
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	
	unset($rowterminal,$rowstream,$sql,$user_terminal_str);	
	
	return $selectterminalstr;
}
function get_terminallist5($type, $getterminalid)
{
	global $do_php_prompt;
	global $con;
	//读取用户终端
	$user_terminal_array = array();
	
	$user_terminal_str = "";
	
	$user_sql = "";
	
	$user_isornot_admin = trim($_SESSION['admin_id']);
	
	$user_name = trim($_SESSION['username']);
	$userid=$_SESSION['userid'];		
	$user_terminal_array=array();
	if($user_name == 'admin')
	{
		//读取所有终端ID
		$sql="SELECT id FROM terminal WHERE typeid IN ($type)";
		$user_result = mysqli_query($con,$sql) or die(mysqli_error($con));
		
		if(mysqli_num_rows($user_result) > 0)
		{
			while($user_row = mysqli_fetch_array($user_result))
			{
				$user_terminal_array[] = $user_row['id'];
			}
			
			$user_terminal_str = implode(",",$user_terminal_array);
			
			unset($user_terminal_array);
		}
		else
		{
			echo "<script>alert('".$do_php_prompt['system_not_check_termials']."');</script>";
			
			echo "<script>window.history.back();</script>";
			
			exit;
		}
	}
	else
	{
		$user_sql = "SELECT terminalid FROM userterminal WHERE userterminal.userid ='$userid' ";
		$user_result = mysqli_query($con,$user_sql) or die(mysqli_error($con));
		if(mysqli_num_rows($user_result) <= 0)
		{
			echo "<script>alert('".$do_php_prompt['user_not_operate_terminals']."');</script>";
			
			echo "<script>window.history.back();</script>";
			
			exit;
		}
		else
		{

			while($user_row = mysqli_fetch_array($user_result))
			{
				
				$user_terminal_array[] = $user_row['terminalid'];
			}
			
			$user_terminal_str = implode(",",$user_terminal_array);
			
			unset($user_terminal_array);
		}
	}
	
	mysqli_free_result($user_result);
	
	unset($user_sql,$user_row);
	
	//echo $user_terminal_str;

	$nostream = $do_php_prompt['unzoned_terminal'];	
	if($user_name == 'admin')
	{
	$sql="SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream";
	}
	else
	{	
	$sql="SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream WHERE userid='$userid' ";
	}
	$tree_str  = "<tree id=\\\"0\\\">";
	
	$str= "";
		
	//$fp = fopen("get_terminallist.log","w");		
	
	$resultstream=	mysqli_query($con,$sql);
	
	
	if(mysqli_num_rows($resultstream) > 0)
	{
	while ($rowstream = mysqli_fetch_array($resultstream))
	{			
		$streamid = $rowstream['streamid'];		
		
		$str= "";
		$terminal_sql = "SELECT terminal.id,terminal.terminalname,terminaltype.name,terminal.typeid FROM terminal ,terminaltype WHERE terminal.typeid IN ($type) AND terminal.id IN ";
			
			$terminal_sql.= "(SELECT terminalid FROM terminalofgroup WHERE terminalofgroup.groupid='".$rowstream['streamid']."') AND terminal.id != '$getterminalid'  AND terminaltype.id=terminal.typeid  ORDER BY CONVERT(terminal.terminalname USING utf8)";
			
			$resultterminal = mysqli_query($con,$terminal_sql) or die(mysqli_error($con));
		//$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.typeid in $type and terminal.groupid=$streamid AND terminal.id != '$getterminalid' AND terminal.id IN ($user_terminal_str)");
		$tree_str.="<item text=\\\"".$rowstream['name']."\\\" id=\\\"stream_".$rowstream['streamid']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
			
		//fwrite($fp,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.typeid in $type and terminal.groupid=$streamid AND terminal.id != '$getterminalid'");		
		//fwrite($fp,"\n");		
		while ($rowterminal = mysqli_fetch_array($resultterminal)) 
		{	
			// $faname = chinese_big5_english_terminal($_SESSION['language'],$rowterminal['name']);
			$tree_str.= "<item  text=\\\"".$rowterminal['terminalname']."\\\" id=\\\"stream_".$rowstream['streamid']."::".$rowterminal['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				
		}
		$tree_str.= "</item>";
		if(!empty($str))
		{
				$str .="<item text=\\\"".$rowstream['name']."".$do_php_prompt['terminal_group']."\\\" id=\\\"stream_".$streamid."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
				
				
				
				$str .= "</item>"; 
		}		
		mysqli_free_result($resultterminal);				
	}
	 
}

	mysqli_free_result($resultstream);
	
	//输出未分区终端terminal.groupid = 0 为未分区
	$no_group_name = $do_php_prompt['No_group_terminal'];
	$str = "";
	if($user_name == 'admin')
	{
		$getstr="SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.typeid IN($type) AND terminal.id NOT IN (SELECT DISTINCT terminalofgroup.terminalid FROM terminalofgroup) AND terminal.id != '$getterminalid' AND terminal.id IN($user_terminal_str) ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
	else
	{
	$getstr="SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.typeid IN($type) AND terminal.id NOT IN (SELECT DISTINCT terminalofgroup.terminalid FROM terminalofgroup WHERE groupid IN(SELECT streamid FROM serverplaystream WHERE userid='$userid')) AND terminal.id != '$getterminalid' AND terminal.id IN($user_terminal_str) ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}	
	$resultterminal = mysqli_query($con,$getstr)or die(mysqli_error($con));

	if(mysqli_num_rows($resultterminal) > 0)
	{
	$tree_str.="<item  text=\\\"".$no_group_name."\\\" id=\\\"stream_0\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		
		$tree_str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\"stream_0::"."$rowterminal[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>"	;
	}
	$tree_str.= "</item>";
	}
	mysqli_free_result($resultterminal);
	
	$tree_str .="</tree>";
	if(!empty($str))
	{
		$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"".$nostream."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		$selectterminalstr .=$str;
		$selectterminalstr .= "</item>"; 
	}	
	
	//$selectterminalstr .="</item>"; 
	$selectterminalstr .="</tree>"; 
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);

		
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);

	unset($rowterminal,$rowstream,$sql,$user_terminal_str);	
	
	return $tree_str;
}

function get_dirareasub($con,$id,$type,$streamid)
{
	$sql2="SELECT DISTINCT * FROM terminalfolder WHERE parentid = $id";
	$get_result2 = mysqli_query($con,$sql2) or die(mysqli_error($con));	
	while($get_row2 = mysqli_fetch_array($get_result2))
	{
		$get_id=$get_row2['id'];
		$get_sql="SELECT terminal.id,terminal.terminalname FROM terminal,terminaloffolder WHERE terminaloffolder.terminalid=terminal.id AND terminaloffolder.folderid=$get_id  AND terminal.id not in($streamid) AND terminal.typeid in ($type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
		
		$resultterminal = mysqli_query($con,$get_sql);
		while ($rowterminal = mysqli_fetch_array($resultterminal)) 
		{
		
			$str2 .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\"stream_".$get_id."::"."$rowterminal[id]"."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" ></item>";
		}	
		$str .= "<item text=\\\"".$get_row2['name']."\\\" id=\\\"stream_".$get_row2['id']."\\\" open=\\\"1\\\" im0=\\\"tombs_open.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		$str .= $str2;
	
		$str .= get_dirareasub($con,$get_row2['id'],$type,$streamid);
		$str .="</item>";	
	}	
	return $str;
}

function get_dirarea($type,$streamid)
{
	global $do_php_prompt;
	global $con;
	$nostream = $do_php_prompt['unzoned_terminal'];	
	$selectterminalstr = "<tree id=\\\"0\\\">";
	
	$selectterminalstr .= get_dirareasub($con,0,$type,$streamid);
	/*
	$sql="SELECT DISTINCT * FROM terminalfolder WHERE parentid=0";
	$str = "";
	$username= trim($_SESSION['username']);
	$get_result = mysqli_query($con,$sql) or die(mysqli_error($con));	
	if(mysqli_num_rows($get_result) > 0)
	{
		while($get_row = mysqli_fetch_array($get_result))
		{	
			
		}
	}
	*/
	/*

	$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.groupid = '0' AND terminal.id not in($streamid) AND terminal.typeid in ($type) ORDER BY CONVERT(terminal.terminalname USING utf8)");
	
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		$str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\"stream_0::"."$rowterminal[id]"."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		$str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\"stream_0::"."$rowterminal[id]"."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" ></item>";
		$str .="</item>";
	}

	if(!empty($str))
	{
		$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"".$nostream."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		$selectterminalstr .=$str;
		$selectterminalstr .= "</item>"; 
	}	
	*/	
	//$selectterminalstr .="</item>"; 
	$selectterminalstr .="</tree>"; 
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	@mysqli_free_result($resultterminal);
	@mysqli_free_result($resultstream);		
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	unset($rowterminal,$rowstream,$sql);	
	return $selectterminalstr;
}


//没有分区的终端
/*
function get_terminallistoggroup2($type,$streamid)
{
	global $do_php_prompt;
	global $con;
	$nostream = $do_php_prompt['unzoned_terminal'];	
	
	$sql="SELECT DISTINCT * FROM serverplaystream";
	$selectterminalstr = "<tree id=\\\"0\\\">";
	$str = "";
	$username= trim($_SESSION['username']);

	if($username=='admin')
	{
	$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.groupid = '0' AND terminal.id not in($streamid) AND terminal.typeid in ($type) ORDER BY CONVERT(terminal.terminalname USING utf8)");
	}
	else
	{
	$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.groupid = '0' AND terminal.id not in($streamid)  AND terminal.id IN(SELECT terminalid FROM userterminal WHERE userterminal.userid IN(SELECT id FROM book_admin WHERE book_admin.username = '$username')) AND terminal.typeid in ($type) ORDER BY CONVERT(terminal.terminalname USING utf8)");
	}
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		$str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\"stream_0::"."$rowterminal[id]"."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" ></item>";
	}
	
	if(!empty($str))
	{
		$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"".$nostream."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		$selectterminalstr .=$str;
		$selectterminalstr .= "</item>"; 
	}	
	
	//$selectterminalstr .="</item>"; 
	$selectterminalstr .="</tree>"; 
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	@mysqli_free_result($resultterminal);
	@mysqli_free_result($resultstream);		
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	unset($rowterminal,$rowstream,$sql);	
	return $selectterminalstr;
}*/
function get_terminallistoggroup2($terminal_type,$streamid)
{

	global $con;
	global $do_php_prompt;
	$user_name = trim($_SESSION['username']);
	$userid = trim($_SESSION['userid']);
	$getflag=0;
	$tree_str = "<tree id=\\\"0\\\">";
	
	if($user_name=="admin")
		$group_sql = "SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream ";
	else
		$group_sql = "SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream where userid='$userid' ";
	$group_result = mysqli_query($con,$group_sql) or die(mysqli_error($con));

	if(mysqli_num_rows($group_result) > 0)
	{

	$get_sql = "SELECT count(id) FROM terminal where terminal.typeid IN($terminal_type)";
	$get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));	
		while($get_row = mysqli_fetch_array($get_result))
		{
		$get_number=$get_row[0];
		
		}
	
	
		
		while($group_row = mysqli_fetch_array($group_result))
		{
			
			if($user_name=="admin")
			{
				$terminal_sql = "SELECT terminal.id,terminal.terminalname,terminaltype.name,terminal.typeid FROM terminal ,terminaltype WHERE terminal.id IN ";
				$terminal_sql.= "(SELECT terminalid FROM terminalofgroup WHERE terminalofgroup.groupid='".$group_row['streamid']."') AND terminaltype.id=terminal.typeid AND terminal.typeid IN($terminal_type) AND terminal.id not in($streamid) ORDER BY CONVERT(terminal.terminalname USING utf8)";
			}
			else
			{
			$terminal_sql = "SELECT terminal.id,terminal.terminalname,terminaltype.name,terminal.typeid FROM terminal ,terminaltype WHERE terminal.id IN ";
				$terminal_sql.= "(SELECT terminalid FROM terminalofgroup WHERE terminalofgroup.groupid='".$group_row['streamid']."' and terminalid IN(select terminalid from userterminal where userid='$userid')) AND terminaltype.id=terminal.typeid AND terminal.typeid IN($terminal_type) AND terminal.id not in($streamid) ORDER BY CONVERT(terminal.terminalname USING utf8)";
			
			}
			$terminal_result = mysqli_query($con,$terminal_sql) or die(mysqli_error($con));
			if(mysqli_num_rows($terminal_result) > 0)
			{
					if($get_number>35)
			   		{
					$tree_str.="<item text=\\\"".$group_row['name']."\\\" id=\\\"stream_".$group_row['streamid']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
					}
					else
					{
					$tree_str.="<item text=\\\"".$group_row['name']."\\\" id=\\\"stream_".$group_row['streamid']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
					}
			
			while($terminal_row = mysqli_fetch_array($terminal_result))
			{
				
			   $faname = chinese_big5_english_terminal($_SESSION['language'],$terminal_row['name']);
			   if($get_number>35)
			   {
				$tree_str.= "<item  text=\\\"".$terminal_row['terminalname']."-".$faname."\\\" id=\\\"stream_".$group_row['streamid']."::".$terminal_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				}
				else
				{
				$tree_str.= "<item  text=\\\"".$terminal_row['terminalname']."-".$faname."\\\" id=\\\"stream_".$group_row['streamid']."::".$terminal_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				}
				
			}
			
			$tree_str.= "</item>";
			}
			@mysqli_free_result($terminal_result);
			
			unset($terminal_sql,$terminal_row);
		}
	}
	@mysqli_free_result($group_result);
		
	unset($group_sql,$group_row);
//=======================================================================没有分区的终端
	$no_group_name = $do_php_prompt['No_group_terminal'];
	if($user_name=="admin")
	{
		$no_group_sql = "SELECT terminal.id,terminal.terminalname,terminal.typeid,terminaltype.name FROM terminal,terminaltype WHERE terminal.id NOT IN ";
		$no_group_sql.= "(SELECT DISTINCT terminalofgroup.terminalid FROM terminalofgroup) AND terminal.typeid IN($terminal_type)  AND terminaltype.id=terminal.typeid AND terminal.id not in($streamid) ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
else
	{
		$no_group_sql = "SELECT DISTINCT  terminal.id,terminal.terminalname,terminaltype.name FROM terminal,terminaltype WHERE terminal.typeid=terminaltype.id AND terminal.id NOT IN(SELECT terminalid FROM terminalofgroup WHERE groupid IN (SELECT streamid FROM serverplaystream WHERE userid in(SELECT id FROM book_admin WHERE book_admin.username = '$user_name' )) ) AND terminal.id IN(SELECT terminalid FROM userterminal WHERE userterminal.userid IN(SELECT id FROM book_admin WHERE book_admin.username = '$user_name')) AND terminal.typeid IN($terminal_type) AND terminal.id not in($streamid) ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
	$no_group_result = mysqli_query($con,$no_group_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($no_group_result) > 0)
	{

				if($get_number>35)
				{
				$tree_str.="<item  text=\\\"".$no_group_name."\\\" id=\\\"stream_0\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
				}
				else
				{
				$tree_str.="<item  text=\\\"".$no_group_name."\\\" id=\\\"stream_0\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
				}
		while($no_group_row = mysqli_fetch_array($no_group_result))
		{
			
		
		
			$faname = chinese_big5_english_terminal($_SESSION['language'],$no_group_row['name']);
			if($get_number>35)
			{
			$tree_str.= "<item   text=\\\"".$no_group_row['terminalname']."-".$faname."\\\" id=\\\"stream_0::".$no_group_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
			}
			else
			{
			$tree_str.= "<item   text=\\\"".$no_group_row['terminalname']."-".$faname."\\\" id=\\\"stream_0::".$no_group_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" ></item>";
			}
		}
		
		$tree_str.= "</item>";
	}
	$tree_str .="</tree>"; 
	
	@mysqli_free_result($no_group_result);
	
	unset($no_group_sql,$no_group_row);

	return $tree_str;

	
}
function get_terminallistoggroup($type,$streamid)
{
	global $con;
	global $do_php_prompt;
	$username= trim($_SESSION['username']);
	$nostream = $do_php_prompt['unzoned_terminal'];	
	
	$sql="SELECT DISTINCT * FROM serverplaystream";
	$selectterminalstr = "<tree id=\\\"0\\\">";
			
	//$fp = fopen("get_terminallist.log","w");	

	$str= "";
	if($username=='admin')
	$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.id IN (SELECT terminalid FROM terminalofgroup WHERE groupid=$streamid) AND terminal.typeid IN $type ORDER BY CONVERT(terminal.terminalname USING utf8)");
	else
	 $resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.id IN (SELECT terminalid FROM terminalofgroup WHERE groupid=$streamid) AND terminal.id IN(SELECT terminalid FROM userterminal WHERE userterminal.userid IN(SELECT id FROM book_admin WHERE book_admin.username = '$username')) AND terminal.typeid in $type ORDER BY CONVERT(terminal.terminalname USING utf8)");
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		$str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\""."$rowterminal[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
	}
	
	if(!empty($str))
	{
		$selectterminalstr .="<item text=\\\"".$do_php_prompt['Selected_terminal']."\\\" id=\\\"stream_".$streamid."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		$selectterminalstr .=$str;
		$selectterminalstr .= "</item>"; 
	}		
	mysqli_free_result($resultterminal);				
	
	
	$str = "";

	if($username=='admin')
	$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.id NOT IN (SELECT terminalid FROM terminalofgroup WHERE groupid=$streamid) AND terminal.typeid in $type ORDER BY CONVERT(terminal.terminalname USING utf8)");
	else
	 $resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.id NOT IN (SELECT terminalid FROM terminalofgroup WHERE groupid=$streamid) AND terminal.id IN(SELECT terminalid FROM userterminal WHERE userterminal.userid IN(SELECT id FROM book_admin WHERE book_admin.username = '$username')) AND terminal.typeid in $type ORDER BY CONVERT(terminal.terminalname USING utf8)");
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		$str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\""."$rowterminal[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
	}
	
	if(!empty($str))
	{
		$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"".$nostream."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		$selectterminalstr .=$str;
		$selectterminalstr .= "</item>"; 
	}	
	
	//$selectterminalstr .="</item>"; 
	$selectterminalstr .="</tree>"; 
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
		
		unset($rowterminal,$rowstream,$sql);	
	mysqli_free_result($resultterminal);

	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);

	return $selectterminalstr;
}
function get_nogroupterminallist($type)
{
	global $do_php_prompt;
	global $con;
	$nostream = $do_php_prompt['unzoned_terminal'];	
	$username= trim($_SESSION['username']);
	$selectterminalstr = "";
	
	//$sql="SELECT DISTINCT * FROM serverplaystream ";
	$selectterminalstr = "<tree id=\\\"0\\\">";	
	//$str = "";
	
	$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"".$nostream."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
	if($username=="admin")
	  $resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.typeid IN $type ORDER BY CONVERT(terminal.terminalname USING utf8)");
	else
	  $resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.id IN(SELECT terminalid FROM userterminal WHERE userterminal.userid IN(SELECT id FROM book_admin WHERE book_admin.username = '$username')) AND terminal.typeid in $type ORDER BY CONVERT(terminal.terminalname USING utf8)");
	
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		//$str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\""."$rowterminal[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
		
		$selectterminalstr .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\""."$rowterminal[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
	}
	
	//if(!empty($str))
	//{
	//	$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"".$nostream."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		
	//	$selectterminalstr .=$str;
		
		$selectterminalstr .= "</item>"; 
	//}	
	
	//$selectterminalstr .="</item>"; 
	$selectterminalstr .="</tree>"; 
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	@mysqli_free_result($resultterminal);
	
	//@mysqli_free_result($resultstream);		
	
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	
	//unset($rowterminal,$rowstream,$sql);	
	
	unset($rowterminal);
	
	return $selectterminalstr;
}


function get_soundsnogrouplist($type)
{
	global $do_php_prompt;
	global $con;
	$nostream = $do_php_prompt['unzoned_terminal'];	
	$nosound = $do_php_prompt['unzoned_soundsterminal'];	
	$selectareaed = $do_php_prompt['selected_sounddevice'];
	$username= trim($_SESSION['username']);
	$selectterminalstr = "";
	//$sql="SELECT DISTINCT * FROM serverplaystream ";
	$selectterminalstr = "<tree id=\\\"0\\\">";	
	//$str = "";
	$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"stream_".$nostream."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
	if($username=="admin")
	  $resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname,terminal.soundsgroupid FROM terminal WHERE terminal.typeid IN $type ORDER BY CONVERT(terminal.terminalname USING utf8)");
	else
	  $resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname,terminal.soundsgroupid FROM terminal WHERE terminal.id IN(SELECT terminalid FROM userterminal WHERE userterminal.userid IN(SELECT id FROM book_admin WHERE book_admin.username = '$username')) AND terminal.typeid in $type ORDER BY CONVERT(terminal.terminalname USING utf8)");
	
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		//$str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\""."$rowterminal[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
			if($rowterminal['soundsgroupid']==0)
			{
			$terminalinfo=$rowterminal['terminalname'];
			$streamname="stream::";
			}
			else 
			{
			$streamname="stream@@::";
			$terminalinfo=$rowterminal['terminalname']."----".$selectareaed;
			}
		$selectterminalstr .= "<item text=\\\"".$terminalinfo."\\\" id=\\\"".$streamname.$rowterminal['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
	}
	
	//if(!empty($str))
	//{
	//	$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"".$nostream."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		
	//	$selectterminalstr .=$str;
		
		$selectterminalstr .= "</item>"; 
	//}	
		$selectterminalstr .= "<item text=\\\"".$nosound."\\\" id=\\\"sounds_".$nosound."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
	if($username=="admin")
	  $resultterminal = mysqli_query($con,"SELECT sounddevice.id,sounddevice.name,sounddevice.groupid FROM sounddevice");
	else
	  $resultterminal = mysqli_query($con,"SELECT sounddevice.id,sounddevice.name,sounddevice.groupid FROM sounddevice");
	
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		//$str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\""."$rowterminal[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
		if($rowterminal['groupid']==0)
		$selectterminalstr .= "<item text=\\\"".$rowterminal['name']."\\\" id=\\\"sounds::".$rowterminal['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
		else
		$selectterminalstr .= "<item text=\\\"".$rowterminal['name']."----".$selectareaed."\\\" id=\\\"sounds@@::".$rowterminal['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
	}
	
	//if(!empty($str))
	//{
	//	$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"".$nostream."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		
	//	$selectterminalstr .=$str;
		
		$selectterminalstr .= "</item>"; 
	//$selectterminalstr .="</item>"; 
	$selectterminalstr .="</tree>"; 
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	@mysqli_free_result($resultterminal);
	
	//@mysqli_free_result($resultstream);		
	
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	
	//unset($rowterminal,$rowstream,$sql);	
	
	unset($rowterminal);
	
	return $selectterminalstr;
}

function update_soundsnogrouplist($type,$id)
{
	global $do_php_prompt;
	global $con;
	$nostream = $do_php_prompt['unzoned_terminal'];	
	$nosound = $do_php_prompt['unzoned_soundsterminal'];	
	$selectareaed = $do_php_prompt['selected_sounddevice'];
	$username= trim($_SESSION['username']);
	$selectterminalstr = "";
	//$sql="SELECT DISTINCT * FROM serverplaystream ";
	$selectterminalstr = "<tree id=\\\"0\\\">";	
	//$str = "";
	$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"stream_".$nostream."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
	if($username=="admin")
	  $resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname,terminal.soundsgroupid FROM terminal WHERE terminal.typeid IN $type ORDER BY CONVERT(terminal.terminalname USING utf8)");
	else
	  $resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname,terminal.soundsgroupid FROM terminal WHERE terminal.id IN(SELECT terminalid FROM userterminal WHERE userterminal.userid IN(SELECT id FROM book_admin WHERE book_admin.username = '$username')) AND terminal.typeid in $type ORDER BY CONVERT(terminal.terminalname USING utf8)");
	
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		//$str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\""."$rowterminal[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
		if($rowterminal['soundsgroupid']==0||$rowterminal['soundsgroupid']==$id)
			{
			$terminalinfo=$rowterminal['terminalname'];
			$streamname="stream::";
			}
			else 
			{
			$streamname="stream@@::";
			$terminalinfo=$rowterminal['terminalname']."----".$selectareaed;
			}
		
		$selectterminalstr .= "<item text=\\\"".$terminalinfo."\\\" id=\\\"".$streamname.$rowterminal['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
	}
	
	//if(!empty($str))
	//{
	//	$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"".$nostream."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		
	//	$selectterminalstr .=$str;
		
		$selectterminalstr .= "</item>"; 
	//}	
		$selectterminalstr .= "<item text=\\\"".$nosound."\\\" id=\\\"sounds_".$nosound."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
	if($username=="admin")
	  $resultterminal = mysqli_query($con,"SELECT sounddevice.id,sounddevice.name,sounddevice.groupid FROM sounddevice");
	else
	  $resultterminal = mysqli_query($con,"SELECT sounddevice.id,sounddevice.name,sounddevice.groupid FROM sounddevice");
	
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		//$str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\""."$rowterminal[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
		if($rowterminal['groupid']==0||$rowterminal['groupid']==$id)
		$selectterminalstr .= "<item text=\\\"".$rowterminal['name']."\\\" id=\\\"sounds::".$rowterminal['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
		else
		$selectterminalstr .= "<item text=\\\"".$rowterminal['name']."----".$selectareaed."\\\" id=\\\"sounds@@::".$rowterminal['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
	}
	
	//if(!empty($str))
	//{
	//	$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"".$nostream."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		
	//	$selectterminalstr .=$str;
		
		$selectterminalstr .= "</item>"; 
	//$selectterminalstr .="</item>"; 
	$selectterminalstr .="</tree>"; 
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	@mysqli_free_result($resultterminal);
	
	//@mysqli_free_result($resultstream);		
	
	//fwrite($fp,$selectterminalstr);		
	//fclose($fp);
	
	//unset($rowterminal,$rowstream,$sql);	
	unset($rowterminal);
	
	return $selectterminalstr;
}



function get_filelist($username)
{
	global $con;
	$filelist =  "<tree id=\\\"0\\\">";
	
	//$resultfolder = get_folder_info(0);
	$resultfolder=	mysqli_query($con,"SELECT filefolder.id,filefolder.name FROM filefolder WHERE id!='6' AND parentid = '0' and (filefolder.priority='1' OR filefolder.userid IN(SELECT book_admin.id FROM book_admin WHERE book_admin.username='$username'))");
	while ($rowfolder = mysqli_fetch_array($resultfolder))
	{		
		$folderid = $rowfolder['id'];	
		if($_SESSION['registerflag']==1||$_SESSION['registerflag']==2)
		{
				
		}
		else
		{
			if($folderid==5)
				continue;
		}
		
		$str = "";		
		if($username=="admin")
		{	
		  $resultfolder1 = mysqli_query($con,"SELECT * FROM filefolder where id!='6' AND parentid = $folderid ");
		}
		else
		{
		$resultfolder1 = mysqli_query($con,"SELECT * FROM filefolder where id!='6' AND parentid = $folderid AND filefolder.userid IN(SELECT book_admin.id FROM book_admin WHERE book_admin.username='$username')");
		}
		$aaa = mysqli_query($con,"SELECT count(*) FROM `media` where folderid = $folderid and typeid!='tts'");	
			 
		if ($rowms = mysqli_fetch_array($aaa)) 
		{	
			$getnum=$rowms[0];
									  
		}	
				
		$resultmedia = mysqli_query($con,"SELECT * FROM `media` where folderid = $folderid and typeid!='tts'");	
			 
		while ($rowmedia = mysqli_fetch_array($resultmedia)) 
		{	
			if($getnum>5)
			{
			$str .= "<item text=\\\"".htmlspecialchars($rowmedia['name'])."\\\" id=\\\"".$rowmedia['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
			}
			else
			{
			$str .= "<item text=\\\"".htmlspecialchars($rowmedia['name'])."\\\" id=\\\"".$rowmedia['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
			}
									  
		}
		
		while ($rowfolder1 = mysqli_fetch_array($resultfolder1)) 
		{	
		    $folderid1 = $rowfolder1['id'];
			
			$str1 = "";
			$bbb = mysqli_query($con,"SELECT count(*) FROM `media` where folderid = $folderid1 and typeid!='tts'");	
			 
			if ($rowmb = mysqli_fetch_array($bbb)) 
			{	
				$getnumb=$rowmb[0];
										  
			}	
		
			$resultmedia1=	mysqli_query($con,"SELECT * FROM `media` where folderid = $folderid1 and typeid!='tts'");
			
			 while ($rowmedia1 = mysqli_fetch_array($resultmedia1)) 
			{	
				if($getnumb>5)
				{
				$str1.= "<item text=\\\"".htmlspecialchars($rowmedia1['name'])."\\\" id=\\\"".$rowmedia1['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
				}
				else
				{
				$str1.= "<item text=\\\"".htmlspecialchars($rowmedia1['name'])."\\\" id=\\\"".$rowmedia1['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
				}
									  
			}
			if($username=="admin")
			{
				$resultfolder2 = mysqli_query($con,"SELECT * FROM filefolder where id!='6' AND parentid = $folderid1");
			}
			else
			{
			$resultfolder2 = mysqli_query($con,"SELECT * FROM filefolder where id!='6' AND parentid = $folderid1 AND filefolder.userid IN(SELECT book_admin.id FROM book_admin WHERE book_admin.username='$username')");
			}
			while ($rowfolder2 = mysqli_fetch_array($resultfolder2)) 
			{
			
				 $folderid2 = $rowfolder2['id'];
			
				 $str2 = "";
		       $ccc = mysqli_query($con,"SELECT count(*) FROM `media` where folderid = $folderid2 and typeid!='tts'");	
			 
				if ($rowmc = mysqli_fetch_array($ccc)) 
				{	
					$getnumc=$rowmc[0];
											  
				}	
				 $resultmedia2=	mysqli_query($con,"SELECT * FROM `media` where folderid = $folderid2 and typeid!='tts'");
				 
				 while ($rowmedia2 = mysqli_fetch_array($resultmedia2)) 
				{		
					if($getnumc>5)
					{
					$str2.= "<item text=\\\"".$rowmedia2['name']."\\\" id=\\\"".$rowmedia2['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
					}
					else 
					{
					$str2.= "<item text=\\\"".$rowmedia2['name']."\\\" id=\\\"".$rowmedia2['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
					}				  
				}
				
				if(!empty($str2))
				{
				
					$str1.= "<item text=\\\"".$rowfolder2['name']."\\\" id=\\\"dir_".$rowfolder2['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > ";
				
					$str1 .=$str2;
				
					$str1 .= "</item>";
				}	
			}
			if(!empty($str1))
			{
				
				$str.= "<item text=\\\"".$rowfolder1['name']."\\\" id=\\\"dir_".$rowfolder1['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > ";
				
				$str .=$str1;
				
				$str .= "</item>";
			}				  
		}
		
		if(!empty($str))
		{
		
			$filelist .= "<item text=\\\"".chinese_big5_english_tree($_SESSION['language'],$rowfolder['name'])."\\\" id=\\\"dir_".$folderid."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
		
			$filelist .=$str;
			
			$filelist .= "</item>"; 
		}				 					
	}		
	$filelist .="</tree>"; 

	@mysqli_free_result($resultmedia);		
	@mysqli_free_result($resultfolder);			
	
	return $filelist;
}

function judge_suffix($file_name)
{
	return substr($file_name,strrpos($file_name,".")+1);
}

function trans_time($timestamp)
{
	return date("Y-m-d H:i:s",$timestamp);
}

function chinese_big5_english_video($curr_language,$txt_msg)
{
    if($curr_language == "big5")
    {
        switch($txt_msg)
        {
            case "视频媒体":
            $txt_msg= "视频媒体";
            break;
						case "U盘视频":
							$txt_msg= "U盘视频";
						break;
        }
    }
    else if($curr_language == "chinese") 
    {
        //不做处理
    }
    else if($curr_language == "english")
    {
        switch($txt_msg)
        {
            case "video":
            $txt_msg= "video media";
            break;
						case "udisk video":
							$txt_msg= "udisk video ";
							break;
        }
    }
return $txt_msg;
}


function get_vediolist($username,$flag)
{
	global $con;
	if($flag==0)  //默认视频任务
	{
		$vedio_Path="link/backup/backup/";
	}
	else  //临时播放视频任务
	{
		$vedio_Path="link/backup/backup/";

	}

	$filelist =  "<tree id=\\\"0\\\">";
	
	//读取备份文件夹下的文件
	$vedio_files = array();

	$str = "";
	$getid=1;
	if($flag==0) 
	{
		$str .= "<item text=\\\"".chinese_big5_english_video($_SESSION['language'],'视频媒体')."\\\" id=\\\"".$getid."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
	}
	else 
	{
		$str .= "<item text=\\\"".chinese_big5_english_video($_SESSION['language'],'U盘视频')."\\\" id=\\\"".$getid."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";

	}
		
if(is_dir($vedio_Path))
{
	if($folder_handle = opendir($vedio_Path))
	{
		while( ($file = readdir($folder_handle)) !== false)
		{
			if($file != "." && $file != "..")
			{
				if(is_file($vedio_Path."/".$file))
				{
					$geshitype=judge_suffix($file);
					if($geshitype == "mp4"||$geshitype == "wmv"||$geshitype == "avi"||$geshitype == "tar")
					{
						$filename = basename($file,".".$geshitype);
						$filetype = pathinfo($vedio_Path."/".$file);	
						$filesize = filesize($vedio_Path.$file);
						$rootpath=$vedio_Path.$file;
						$filetime = filemtime("$rootpath");
						$filetime = trans_time($filetime);
						$filetype = $filetype['extension'];
						if($flag==0) 
						{
							$sql2="SELECT media.filename FROM media WHERE media.filename='$rootpath'";
							$result=mysqli_query($con,$sql2) or die(mysqli_error($con));
	
							if(mysqli_num_rows($result)<=0)
							{
								$sql = "INSERT INTO media ( name,size,typeid,priority,filename,folderid,timelength,channel,sample,bitrate,codecid,offlinestate,userid)VALUES('$filename','$filesize','$geshitype','0','$rootpath','0','0','2','0','0','0','0','0')";
								mysqli_query($con,$sql) or die(mysqli_error($con));
							}
						}
						else
						{
						
							$str2 = "<item text=\\\"".$filename."\\\" id=\\\"".$rootpath."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";				
							$str.=$str2;

						}
					
					}
				}
			}
		}
	}	
}
	if($flag==0) 
	{
		$resultfolder=	mysqli_query($con,"SELECT id,name,media.filename FROM media WHERE sample=0 AND bitrate=0");
		while ($rowfolder = mysqli_fetch_array($resultfolder))
		{		
			$str2 = "<item text=\\\"".$rowfolder['name']."\\\" id=\\\"".$rowfolder['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";				
			$str.=$str2;

		}	
		
	
	}
	
	$str .= "</item>";
	$filelist .=$str;
	$filelist .="</tree>"; 
	if($flag==0) 
	{
		@mysqli_free_result($resultfolder);	
	}
		
	
	return $filelist;
}


function get_baojingfilelist($con,$userid)
{
	
	$filelist =  "<tree id=\\\"0\\\">";
	
	//$resultfolder = get_folder_info(0);
	$resultfolder=	mysqli_query($con,"SELECT filefolder.id,filefolder.name FROM filefolder WHERE id =4 AND parentid = 0 and (filefolder.priority=1 OR filefolder.userid IN($userid))");
	while ($rowfolder = mysqli_fetch_array($resultfolder))
	{			
		$folderid = $rowfolder['id'];
		
		$str = "";		
		if($username=="admin")
		{	
		  $resultfolder1 = mysqli_query($con,"SELECT * FROM filefolder where  parentid = $folderid ");
		  }
		  else
		  {
	    $resultfolder1 = mysqli_query($con,"SELECT * FROM filefolder where parentid = $folderid AND filefolder.userid IN(SELECT book_admin.id FROM book_admin WHERE book_admin.username='$username')");
			}
		$resultmedia = mysqli_query($con,"SELECT * FROM `media` where folderid = $folderid and typeid!='tts'");	
			 
		while ($rowmedia = mysqli_fetch_array($resultmedia)) 
		{	
			$str .= "<item text=\\\"".htmlspecialchars($rowmedia['name'])."\\\" id=\\\"".$rowmedia['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" 	im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
									  
		}
		
		while ($rowfolder1 = mysqli_fetch_array($resultfolder1)) 
		{	
		    $folderid1 = $rowfolder1['id'];
			
			$str1 = "";
		
			$resultmedia1=	mysqli_query($con,"SELECT * FROM `media` where folderid = $folderid1 and typeid!='tts'");
			
			 while ($rowmedia1 = mysqli_fetch_array($resultmedia1)) 
			{	
				$str1.= "<item text=\\\"".htmlspecialchars($rowmedia1['name'])."\\\" id=\\\"".$rowmedia1['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
									  
			}
			if($username=="admin")
			{
				$resultfolder2 = mysqli_query($con,"SELECT * FROM filefolder where  parentid = $folderid1");
			}
			else
			{
			$resultfolder2 = mysqli_query($con,"SELECT * FROM filefolder where parentid = $folderid1 AND filefolder.userid IN(SELECT book_admin.id FROM book_admin WHERE book_admin.username='$username')");
			}
			while ($rowfolder2 = mysqli_fetch_array($resultfolder2)) 
			{
			
				 $folderid2 = $rowfolder2['id'];
			
				 $str2 = "";
		
				 $resultmedia2=	mysqli_query($con,"SELECT * FROM `media` where folderid = $folderid2 and typeid!='tts'");
				 
				 while ($rowmedia2 = mysqli_fetch_array($resultmedia2)) 
				{	
					$str2.= "<item text=\\\"".$rowmedia2['name']."\\\" id=\\\"".$rowmedia2['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
									  
				}
				
				if(!empty($str2))
				{
			
					$str1.= "<item text=\\\"".$rowfolder2['name']."\\\" id=\\\"dir_".$rowfolder2['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > ";
				
					$str1 .=$str2;
				
					$str1 .= "</item>";
				}	
			}
			if(!empty($str1))
			{
			
				$str.= "<item text=\\\"".$rowfolder1['name']."\\\" id=\\\"dir_".$rowfolder1['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > ";
				
				$str .=$str1;
				
				$str .= "</item>";
			}				  
		}
		
		if(!empty($str))
		{
			$filelist .= "<item text=\\\"".chinese_big5_english_tree($_SESSION['language'],$rowfolder['name'])."\\\" id=\\\"dir_".$folderid."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
			
			$filelist .=$str;
			
			$filelist .= "</item>"; 
		}				 					
	}		
	$filelist .="</tree>"; 
	mysqli_free_result($resultmedia);		
	mysqli_free_result($resultfolder);			
	
	return $filelist;
}



function get_filelists($username)
{
	global $con;
	$filelist =  "<tree id=\\\"0\\\">";
	
	//$resultfolder = get_folder_info(0);
	$resultfolder=	mysqli_query($con,"SELECT filefolder.id,filefolder.name FROM filefolder WHERE id IN(1,2,3,4,5) AND parentid = '0' and (filefolder.priority='1' OR filefolder.userid=(SELECT book_admin.id FROM book_admin WHERE book_admin.username='$username'))");
	while ($rowfolder = mysqli_fetch_array($resultfolder))
	{			
		$folderid = $rowfolder['id'];
		
		$str = "";		
	
	    $resultfolder1 = mysqli_query($con,"SELECT * FROM filefolder parentid = $folderid ");
		
		$resultmedia = mysqli_query($con,"SELECT * FROM `media` where folderid = $folderid and typeid!='tts'");	
			 
		while ($rowmedia = mysqli_fetch_array($resultmedia)) 
		{	
			$str .= "<item text=\\\"".htmlspecialchars($rowmedia['name'])."\\\" id=\\\""."$rowmedia[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" 	im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
									  
		}
		
		while ($rowfolder1 = mysqli_fetch_array($resultfolder1)) 
		{	
		    $folderid1 = $rowfolder1['id'];
			
			$str1 = "";
		
			$resultmedia1=	mysqli_query($con,"SELECT * FROM `media` where folderid = $folderid1 and typeid!='tts'");
			
			 while ($rowmedia1 = mysqli_fetch_array($resultmedia1)) 
			{	
				$str1.= "<item text=\\\"".htmlspecialchars($rowmedia1['name'])."\\\" id=\\\""."$rowmedia1[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
									  
			}
			
			$resultfolder2 = mysqli_query($con,"SELECT * FROM filefolder where parentid = $folderid1");
			
			while ($rowfolder2 = mysqli_fetch_array($resultfolder2)) 
			{
			
				 $folderid2 = $rowfolder2['id'];
			
				 $str2 = "";
		
				 $resultmedia2=	mysqli_query($con,"SELECT * FROM `media` where folderid = $folderid2 and typeid!='tts'");
				 
				 while ($rowmedia2 = mysqli_fetch_array($resultmedia2)) 
				{	
					$str2.= "<item text=\\\"".$rowmedia2['name']."\\\" id=\\\""."$rowmedia2[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
									  
				}
				
				if(!empty($str2))
				{
			
					$str1.= "<item text=\\\"".$rowfolder2['name']."\\\" id=\\\"dir_"."$rowfolder2[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > ";
				
					$str1 .=$str2;
				
					$str1 .= "</item>";
				}	
			}
			if(!empty($str1))
			{
			
				$str.= "<item text=\\\"".$rowfolder1['name']."\\\" id=\\\"dir_"."$rowfolder1[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > ";
				
				$str .=$str1;
				
				$str .= "</item>";
			}	
							  
		}
		
		if(!empty($str))
		{
			$filelist .= "<item text=\\\"".chinese_big5_english_tree($_SESSION['language'],$rowfolder['name'])."\\\" id=\\\"dir_".$folderid."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
			
			$filelist .=$str;
			
			$filelist .= "</item>"; 
		}				 					
	}		
	$filelist .="</tree>"; 
	@mysqli_free_result($resultmedia);		
	@mysqli_free_result($resultfolder);			
	return $filelist;
}

function get_filettslist($con,$userid)
{

	$filelist =  "<tree id=\\\"0\\\">";
	
	//$resultfolder = get_folder_info(0);
	$sqlss="SELECT id,name FROM filefolder WHERE  parentid = 0 and id in(3,6) and priority=1 OR userid = $userid";
	
	
	$resultfolder=	mysqli_query($con,$sqlss);
	

	while ($rowfolder = mysqli_fetch_array($resultfolder))
	{	
	
		$folderid = $rowfolder['id'];
		$str = "";		
		
		if($folderid==3)
		{
			$gettemp="";
		}
		else if($folderid==6)
		{
			$gettemp="@@"; 
		}
		$sqlget = "SELECT * FROM media WHERE folderid = $folderid ";
		$resultmedia = mysqli_query($con,$sqlget);	
		
		while ($rowmedia = mysqli_fetch_array($resultmedia)) 
		{	
	
			$str .= "<item text=\\\"".htmlspecialchars($rowmedia['name'])."\\\" id=\\\"".$gettemp."".$rowmedia['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" 	im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";	
		  
		}
	
	   $resultfolder1 = mysqli_query($con,"SELECT * FROM filefolder where  parentid = $folderid ");
		while ($rowfolder1 = mysqli_fetch_array($resultfolder1)) 
		{	
		    $folderid1 = $rowfolder1['id'];
			
			$str1 = "";
		
			$resultmedia1=	mysqli_query($con,"SELECT * FROM `media` where folderid = $folderid1 ");
			
			 while ($rowmedia1 = mysqli_fetch_array($resultmedia1)) 
			{	
				$str1.= "<item text=\\\"".htmlspecialchars($rowmedia1['name'])."\\\" id=\\\"".$gettemp."".$rowmedia1['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
									  
			}
			
			$resultfolder2 = mysqli_query($con,"SELECT * FROM filefolder where  parentid = $folderid1");
			
			while ($rowfolder2 = mysqli_fetch_array($resultfolder2)) 
			{
			
				 $folderid2 = $rowfolder2['id'];
			
				 $str2 = "";
		
				 $resultmedia2=	mysqli_query($con,"SELECT * FROM `media` where folderid = $folderid2 ");
				 
				 while ($rowmedia2 = mysqli_fetch_array($resultmedia2)) 
				{	
					$str2.= "<item text=\\\"".$rowmedia2['name']."\\\" id=\\\"".$gettemp."".$rowmedia2['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > </item>";
									  
				}
				
				if(!empty($str2))
				{
			
					$str1.= "<item text=\\\"".$rowfolder2['name']."\\\" id=\\\"dir_"."$rowfolder2[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > ";
				
					$str1 .=$str2;
				
					$str1 .= "</item>";
				}	
			}
			if(!empty($str1))
			{
			
				$str.= "<item text=\\\"".$rowfolder1['name']."\\\" id=\\\"dir_"."$rowfolder1[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" > ";
				
				$str .=$str1;
				
				$str .= "</item>";
			}	
							  
		}
		
		if(!empty($str))
		{
			$filelist .= "<item text=\\\"".chinese_big5_english_tree($_SESSION['language'],$rowfolder['name'])."\\\" id=\\\"dir_".$folderid."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\">";
			
			$filelist .=$str;
			
			$filelist .= "</item>"; 
		}				 					
	}	
	
	$filelist .="</tree>"; 
	mysqli_free_result($resultmedia);		
	mysqli_free_result($resultfolder);			
	
	return $filelist;
}

function get_selectTerminalid($taskid)
{
global $con;
	$sql = "select	terminalid,area from terminaloftask where terminaloftask.taskid = '$taskid'";

	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result))
	{
		$terminalidlist[] =array("terminalid"=>$row['terminalid'],"area"=>$row['area']);
	}
	return $terminalidlist;
}

function get_audiosource()
{
global $con;
	$sql = "select	id,name from audiosource";	
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	
	while($row = mysqli_fetch_array($result))
	{
	$name = chinese_big5_english_play($_SESSION['language'],$row['name']);
		$audiosourcelist[] =array("id"=>$row['id'],"name"=>$name);
	}	
	return $audiosourcelist;
}
function get_powerlist()
{
global $con;
	$sql = "select	id,name from powermgrmap";	
	
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));	
	
	$selectterminalstr = "<tree id=\\\"0\\\">";	
	
	$str = "";
	
	$resultterminal = mysqli_query($con,"SELECT terminal.id,terminal.terminalname FROM terminal WHERE terminal.groupid = '0' AND terminal.typeid in $type ORDER BY CONVERT(terminal.terminalname USING utf8)");
	
	while ($rowterminal = mysqli_fetch_array($resultterminal)) 
	{	
		$str .= "<item text=\\\"".$rowterminal['terminalname']."\\\" id=\\\""."$rowterminal[id]"."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
	}
	
	if(!empty($str))
	{
		//$selectterminalstr .= "<item text=\\\"".$nostream."\\\" id=\\\"".$nostream."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		$selectterminalstr .=$str;
		//$selectterminalstr .= "</item>"; 
	}	
  
	//$selectterminalstr .="</item>"; 
	
	$selectterminalstr .="</tree>"; 
	
	//fwrite($fp,$selectterminalstr);		
	
	//fclose($fp);
	
	@mysqli_free_result($resultterminal);	
	
	//fwrite($fp,$selectterminalstr);		
	
	//fclose($fp);
	
	unset($rowterminal,$rowstream,$sql);	
	
	return $selectterminalstr;
	
	return $audiosourcelist;
}
function chinese_big5_english_terminal($curr_language,$txt_msg)
{
 if($curr_language == "big5")
    {
        switch($txt_msg)
        {
            case "服务器":
            $txt_msg= "服務器";
            break;
            case "普通IP终端":
            $txt_msg= "普通IP終端";
            break;
            case "话筒":
            $txt_msg= "話筒";
            break;
            case "双向寻呼终端":
            $txt_msg= "雙向尋呼終端";
            break;
            case "IP前置":
            $txt_msg= "IP前置";
            break;
			 case "IP功放":
            $txt_msg= "IP功放";
            break;
			 case "电源管理器":
            $txt_msg= "電源管理器";
            break;
			 case "报警主机":
            $txt_msg= "報警主機";
            break;
			 case "采样终端":
            $txt_msg= "采樣終端";
            break;
			 case "普通电脑":
            $txt_msg= "普通電腦";
            break;
			 case "MP3":
            $txt_msg= "MP3";
            break;
		    case "一体化音箱":
            $txt_msg= "一體化音箱";
            break;
			case "分控软件":
            $txt_msg= "分控軟件";
            break;
			case "一键寻呼终端":
            $txt_msg= "一鍵尋呼終端";
            break;
			case "分控前置":
            $txt_msg= "分控前置";
            break;
			
        }
    }
    else if($curr_language == "chinese") 
    {
        //不做处理
    }
    else if($curr_language == "english")
    {
        switch($txt_msg)
        {
          case "服务器":
            $txt_msg= "Server";
            break;
            case "普通IP终端":
            $txt_msg= "Ordinary IP Terminal";
            break;
            case "话筒":
            $txt_msg= "Microphone";
            break;
            case "双向寻呼终端":
            $txt_msg= "Two-way Paging Terminal";
            break;
            case "IP前置":
            $txt_msg= "IP Lead";
            break;
			 case "IP功放":
            $txt_msg= "IP Amplifier";
            break;
			 case "电源管理器":
            $txt_msg= "Power Management";
            break;
			 case "报警主机":
            $txt_msg= "The Mainframe";
            break;
			 case "采样终端":
            $txt_msg= "Sampling Terminal";
            break;
			 case "普通电脑":
            $txt_msg= "Computer";
            break;
			 case "MP3":
            $txt_msg= "MP3";
            break;
		    case "一体化音箱":
            $txt_msg= "Integrated Speakers";
            break;
			case "分控软件":
            $txt_msg= "Points Control Software";
            break;
			case "一键寻呼终端":
            $txt_msg= "A Key Paging Terminal";
            break;
			case "分控前置":
            $txt_msg= "Points Lead Control";
            break;
			case "背景音乐":
            $txt_msg= "background music";
            break;
			case "事话接口":
            $txt_msg= "telephone interface";
            break;
			case "手机终端":
            $txt_msg= "moblie terminal";
            break;
			case "9970分控工作站":
            $txt_msg= "subcontrol workstation";
            break;
			case "透传终端":
            $txt_msg= "transparent terminal";
            break;
			case "普通IP终端":
            $txt_msg= "ordinary ip terminal";
            break;
			case "监控主机":
            $txt_msg= "monitor host";
            break;
			case "TTS主机":
            $txt_msg= "TTS host";
            break;
			case "离线终端":
            $txt_msg= "offline terminal";
            break;
			case "简版网络功放":
            $txt_msg= "simple network amplifier";
            break;
			case "简版采样终端":
            $txt_msg= "simple sampling terminal";
            break;
			case "网络调音台":
            $txt_msg= "network mixer";
            break;
			case "线阵音柱":
            $txt_msg= "network audio collector";
            break;
			case "网络调音台":
            $txt_msg= "network mixer";
            break;
			case "网络音频采集器":
            $txt_msg= "network audio collector";
            break;
			case "网络音频采集器":
            $txt_msg= "network audio collector";
            break;
			case "网络前置":
            $txt_msg= "network preposition";
            break;
			case "网络分区前置":
            $txt_msg= "network area preposition";
            break;
			case "网络功放":
            $txt_msg= "network amplifier";
            break;
			case "简版网络前置":
            $txt_msg= "simple network preposition";
            break;
			case "寻呼终端":
            $txt_msg= "paging terminal";
            break;
        }
    }
return $txt_msg;



}

function chinese_big5_english_play($curr_language,$txt_msg)
{
    if($curr_language == "big5")
    {
        switch($txt_msg)
        {
            case "VCD":
            $txt_msg= "VCD";
            break;
            case "DVR":
            $txt_msg= "DVR";
            break;
            case "广播":
            $txt_msg= "广播";
            break;
            case "话筒":
            $txt_msg= "話筒";
            break;
        }
    }
    else if($curr_language == "chinese") 
    {
        //不做处理
    }
    else if($curr_language == "english")
    {
        switch($txt_msg)
        {
            case "VCD":
            $txt_msg= "VCD";
            break;
            case "DVR":
            $txt_msg= "DVR";
            break;
            case "广播":
            $txt_msg= "Broadcast";
            break;
            case "话筒":
            $txt_msg= "Microphone";
            break;
        }
    }
return $txt_msg;
}


function chinese_big5_english_tree($curr_language,$txt_msg)
{
    if($curr_language == "big5")
    {
        switch($txt_msg)
        {
            case "共享媒体库":
            $txt_msg= "共享媒體庫";
            break;
            case "铃声媒体库":
            $txt_msg= "鈴聲媒體庫";
            break;
            case "点播媒体库":
            $txt_msg= "點播媒體庫";
            break;
            case "报警媒体库":
            $txt_msg= "報警媒體庫";
            break;
            case "录音媒体库":
            $txt_msg= "錄音媒體庫";
            break;
        }
    }
    else if($curr_language == "chinese") 
    {
        //不做处理
    }
    else if($curr_language == "english")
    {
        switch($txt_msg)
        {
            case "共享媒体库":
            $txt_msg= "Sharing Media";
            break;
            case "铃声媒体库":
            $txt_msg= "Ring Music";
            break;
            case "点播媒体库":
            $txt_msg= "AOD Media";
            break;
            case "报警媒体库":
            $txt_msg= "Alarm Media";
            break;
            case "录音媒体库":
            $txt_msg= "Recording Media";
            break;
			case "语音合成媒体库":
            $txt_msg= "tts media";
            break;
        }
    }
return $txt_msg;
}
/************************************************************
	判断是否有终端可用
*************************************************************/
function check_user_operation_terminal($admin_id,$user_name,$terminal_type)
{
	global $do_php_prompt;
	global $con;
	if($user_name == 'admin')
	{
		//读取所有终端ID
		$user_result = mysqli_query($con,"SELECT 	* FROM terminal WHERE terminal.typeid IN(".$terminal_type.") ORDER BY CONVERT(terminal.terminalname USING utf8)") or die(mysqli_error($con));
		if(mysqli_num_rows($user_result) > 0)
		{
			//什么也不做
		}
		else
		{
			echo "<script>alert('".$do_php_prompt['system_not_check_termials']."');</script>";
			
			echo "<script>window.history.back();</script>";
			
			exit;
		}
	}
	else
	{
		$user_sql = "SELECT * from terminal,userterminal,book_admin ";

		$user_sql.= "where terminal.id = userterminal.terminalid ";

		$user_sql.= "and userterminal.userid = book_admin.id and ";

		$user_sql.= "book_admin.id = ";

		$user_sql.= "(SELECT book_admin.id from book_admin where book_admin.username = '$user_name') ";

		$user_sql.= "and terminal.typeid in (".$terminal_type.") ORDER BY CONVERT(terminal.terminalname USING utf8)";
		
		$user_result = mysqli_query($con,$user_sql) or die(mysqli_error($con));
		
		if(mysqli_num_rows($user_result) < 0)
		{
			echo "<script>alert('".$do_php_prompt['user_not_operate_terminals']."');</script>";
			
			echo "<script>window.history.back();</script>";
			
			exit;
		}
		else
		{
			//什么也不做
		}
	}
	
	@mysqli_free_result($user_result);
	
	unset($user_sql,$user_row);
}

/************************************************************
	显示已分区中终端和未分区终端---只针对超级管理员
************************************************************/
function get_terminal_num($terminal_type)
{
   global $con;
   global $do_php_prompt;
   $count=0;
   $terminal_sql = "SELECT count(terminal.id) FROM terminal ,terminaltype WHERE ";
				$terminal_sql.= "terminaltype.id=terminal.typeid AND terminal.typeid IN($terminal_type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
				$terminal_result = mysqli_query($con,$terminal_sql) or die(mysqli_error($con));
			while($group_row = mysqli_fetch_array($terminal_result))
			{
				$count=$group_row[0];
			
			}
			return $count;
}



function get_zhaoshenggrouped_terminal($terminal_type)
{	
	global $con;
	global $do_php_prompt;
	$user_name = trim($_SESSION['username']);
	$userid = trim($_SESSION['userid']);
	$getflag=0;
	$tree_str = "<tree id=\\\"0\\\">";

	//if($user_name=="admin")
	$group_sql = "SELECT soundgroupinfo.id,soundgroupinfo.name FROM soundgroupinfo ";
	//else
	//	$group_sql = "SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream where userid='$userid' ";
	$group_result = mysqli_query($con,$group_sql) or die(mysqli_error($con));

	if(mysqli_num_rows($group_result) > 0)
	{
		$get_sql = "SELECT count(id) FROM terminal where terminal.typeid IN($terminal_type)";
		$get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));	
		while($get_row = mysqli_fetch_array($get_result))
		{
		$get_number=$get_row['0'];
		
		}

		while($group_row = mysqli_fetch_array($group_result))
		{
				if($get_number>35)
			   		{
					$tree_str.="<item text=\\\"".$group_row['name']."\\\" id=\\\"stream_".$group_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
					}
					else
					{
					$tree_str.="<item text=\\\"".$group_row['name']."\\\" id=\\\"stream_".$group_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
					}
			if($user_name=="admin")
			{
				$terminal_sql = "SELECT terminal.id,terminal.terminalname,terminaltype.name,terminal.typeid FROM terminal ,terminaltype WHERE terminal.id IN ";
				$terminal_sql.= "(SELECT terminalid FROM soundgroup WHERE soundgroup.groupid='".$group_row['id']."') AND terminaltype.id=terminal.typeid AND terminal.typeid IN($terminal_type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
			}
			else
			{
			$terminal_sql = "SELECT terminal.id,terminal.terminalname,terminaltype.name,terminal.typeid FROM terminal ,terminaltype WHERE terminal.id IN ";
				$terminal_sql.= "(SELECT terminalid FROM soundgroup WHERE soundgroup.groupid='".$group_row['id']."' and terminalid IN(select terminalid from userterminal where userid='$userid')) AND terminaltype.id=terminal.typeid AND terminal.typeid IN($terminal_type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
			
			}
	
			$terminal_result = mysqli_query($con,$terminal_sql) or die(mysqli_error($con));
			if(mysqli_num_rows($terminal_result) > 0)
			{
			while($terminal_row = mysqli_fetch_array($terminal_result))
			{
			   $faname = chinese_big5_english_terminal($_SESSION['language'],$terminal_row['name']);
			   if($get_number>35)
			   {
				$tree_str.= "<item  text=\\\"".$terminal_row['terminalname']."-".$faname."\\\" id=\\\"stream_".$group_row['id']."::".$terminal_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				}
				else
				{
				$tree_str.= "<item  text=\\\"".$terminal_row['terminalname']."-".$faname."\\\" id=\\\"stream_".$group_row['id']."::".$terminal_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				}	
			}
			}
			@mysqli_free_result($terminal_result);
			
			unset($terminal_sql,$terminal_row);
			$sounds_sql = "SELECT sounddevice.id,sounddevice.name FROM sounddevice WHERE  sounddevice.groupid='".$group_row['id']."'";
		
			$sounds_result = mysqli_query($con,$sounds_sql) or die(mysqli_error($con));
			while($sounds_row = mysqli_fetch_array($sounds_result))
			{
			   if($get_number>35)
			   {
				$tree_str.= "<item  text=\\\"".$sounds_row['name']."-噪声检测设备\\\" id=\\\"sounds_".$group_row['id']."::".$sounds_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				}
				else
				{
				$tree_str.= "<item  text=\\\"".$sounds_row['name']."-噪声检测设备\\\" id=\\\"sounds_".$group_row['id']."::".$sounds_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				}
			}
			$tree_str.= "</item>";
		}
	}
	@mysqli_free_result($group_result);
		
	unset($group_sql,$group_row);
//=======================================================================没有分区的终端
	/*$no_group_name = $do_php_prompt['No_group_terminal'];
		$no_group_sql = "SELECT terminal.id,terminal.terminalname,terminal.typeid,terminaltype.name FROM terminal,terminaltype WHERE terminal.id NOT IN ";
		$no_group_sql.= "(SELECT DISTINCT soundgroup.terminalid FROM soundgroup) AND terminal.typeid IN($terminal_type)  AND terminaltype.id=terminal.typeid ORDER BY CONVERT(terminal.terminalname USING utf8)";

	$no_group_result = mysqli_query($con,$no_group_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($no_group_result) > 0)
	{

				if($get_number>35)
				{
				$tree_str.="<item  text=\\\"".$no_group_name."\\\" id=\\\"stream_0\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
				}
				else
				{
				$tree_str.="<item  text=\\\"".$no_group_name."\\\" id=\\\"stream_0\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
				}
		while($no_group_row = mysqli_fetch_array($no_group_result))
		{
			
		
		
			$faname = chinese_big5_english_terminal($_SESSION['language'],$no_group_row['name']);
			if($get_number>35)
			{
			$tree_str.= "<item   text=\\\"".$no_group_row['terminalname']."-".$faname."\\\" id=\\\"stream_0::".$no_group_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
			}
			else
			{
			$tree_str.= "<item   text=\\\"".$no_group_row['terminalname']."-".$faname."\\\" id=\\\"stream_0::".$no_group_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" ></item>";
			}
		}
			$sounds_sql = "SELECT sounddevice.id,sounddevice.name FROM sounddevice WHERE sounddevice.groupid=0";
			$sounds_result = mysqli_query($con,$sounds_sql) or die(mysqli_error($con));
			while($sounds_row = mysqli_fetch_array($sounds_result))
			{
			 
			   if($get_number>35)
			   {
				$tree_str.= "<item  text=\\\"".$sounds_row['name']."-噪声检测设备\\\" id=\\\"sounds_0::".$sounds_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				}
				else
				{
				$tree_str.= "<item  text=\\\"".$sounds_row['name']."-噪声检测设备\\\" id=\\\"sounds_0::".$sounds_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				}
				}
		$tree_str.= "</item>";
	}
	*/
	$tree_str .="</tree>"; 

	//@mysqli_free_result($no_group_result);
	
	//unset($no_group_sql,$no_group_row);

	return $tree_str;
}



function get_grouped_terminal($terminal_type)
{	
	global $con;
	global $do_php_prompt;
	$user_name = trim($_SESSION['username']);
	$userid = trim($_SESSION['userid']);
	$getflag=0;
	$tree_str = "<tree id=\\\"0\\\">";
	
	if($user_name=="admin")
		$group_sql = "SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream ";
	else
		$group_sql = "SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream where userid='$userid' ";
	$group_result = mysqli_query($con,$group_sql) or die(mysqli_error($con));
	$get_number=0;
	if(mysqli_num_rows($group_result) > 0)
	{
	$get_sql = "SELECT count(id) FROM terminal where terminal.typeid IN($terminal_type)";
	$get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));	
		while($get_row = mysqli_fetch_array($get_result))
		{
		$get_number=$get_row[0];
		
		}
	
	
		
		while($group_row = mysqli_fetch_array($group_result))
		{
			
			if($user_name=="admin")
			{
				$terminal_sql = "SELECT terminal.id,terminal.terminalname,terminaltype.name,terminal.typeid FROM terminal ,terminaltype WHERE terminal.id IN ";
				$terminal_sql.= "(SELECT terminalid FROM terminalofgroup WHERE terminalofgroup.groupid='".$group_row['streamid']."') AND terminaltype.id=terminal.typeid AND terminal.typeid IN($terminal_type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
			}
			else
			{
			$terminal_sql = "SELECT terminal.id,terminal.terminalname,terminaltype.name,terminal.typeid FROM terminal ,terminaltype WHERE terminal.id IN ";
				$terminal_sql.= "(SELECT terminalid FROM terminalofgroup WHERE terminalofgroup.groupid='".$group_row['streamid']."' and terminalid IN(select terminalid from userterminal where userid='$userid')) AND terminaltype.id=terminal.typeid AND terminal.typeid IN($terminal_type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
			
			}
			$terminal_result = mysqli_query($con,$terminal_sql) or die(mysqli_error($con));
			if(mysqli_num_rows($terminal_result) > 0)
			{
					if($get_number>35)
			   		{
					$tree_str.="<item text=\\\"".$group_row['name']."\\\" id=\\\"stream_".$group_row['streamid']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
					}
					else
					{
					$tree_str.="<item text=\\\"".$group_row['name']."\\\" id=\\\"stream_".$group_row['streamid']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
					}
			
			while($terminal_row = mysqli_fetch_array($terminal_result))
			{
				
			   $faname = chinese_big5_english_terminal($_SESSION['language'],$terminal_row['name']);
			   if($get_number>35)
			   {
				$tree_str.= "<item  text=\\\"".$terminal_row['terminalname']."-".$faname."\\\" id=\\\"stream_".$group_row['streamid']."::".$terminal_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				}
				else
				{
				$tree_str.= "<item  text=\\\"".$terminal_row['terminalname']."-".$faname."\\\" id=\\\"stream_".$group_row['streamid']."::".$terminal_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				}
				
			}
			
			$tree_str.= "</item>";
			}
			@mysqli_free_result($terminal_result);
			
			unset($terminal_sql,$terminal_row);
		}
	}
	@mysqli_free_result($group_result);
		
	unset($group_sql,$group_row);
//=======================================================================没有分区的终端
	$no_group_name = $do_php_prompt['No_group_terminal'];
	if($user_name=="admin")
	{
		$no_group_sql = "SELECT terminal.id,terminal.terminalname,terminal.typeid,terminaltype.name FROM terminal,terminaltype WHERE terminal.id NOT IN ";
		$no_group_sql.= "(SELECT DISTINCT terminalofgroup.terminalid FROM terminalofgroup) AND terminal.typeid IN($terminal_type)  AND terminaltype.id=terminal.typeid ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
else
	{
		$no_group_sql = "SELECT DISTINCT  terminal.id,terminal.terminalname,terminaltype.name FROM terminal,terminaltype WHERE terminal.typeid=terminaltype.id AND terminal.id NOT IN(SELECT terminalid FROM terminalofgroup WHERE groupid IN (SELECT streamid FROM serverplaystream WHERE userid in(SELECT id FROM book_admin WHERE book_admin.username = '$user_name' )) ) AND terminal.id IN(SELECT terminalid FROM userterminal WHERE userterminal.userid IN(SELECT id FROM book_admin WHERE book_admin.username = '$user_name')) AND terminal.typeid IN($terminal_type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
	$no_group_result = mysqli_query($con,$no_group_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($no_group_result) > 0)
	{

				if($get_number>35)
				{
				$tree_str.="<item  text=\\\"".$no_group_name."\\\" id=\\\"stream_0\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
				}
				else
				{
				$tree_str.="<item  text=\\\"".$no_group_name."\\\" id=\\\"stream_0\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
				}
		while($no_group_row = mysqli_fetch_array($no_group_result))
		{
			
		
		
			$faname = chinese_big5_english_terminal($_SESSION['language'],$no_group_row['name']);
			if($get_number>35)
			{
			$tree_str.= "<item   text=\\\"".$no_group_row['terminalname']."-".$faname."\\\" id=\\\"stream_0::".$no_group_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
			}
			else
			{
			$tree_str.= "<item   text=\\\"".$no_group_row['terminalname']."-".$faname."\\\" id=\\\"stream_0::".$no_group_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" ></item>";
			}
		}
		
		$tree_str.= "</item>";
	}
	$tree_str .="</tree>"; 
	
	@mysqli_free_result($no_group_result);
	
	unset($no_group_sql,$no_group_row);

	return $tree_str;
}



function get_grouped_led_terminal($terminal_type)
{	
	global $con;
	global $do_php_prompt;
	$user_name = trim($_SESSION['username']);
	$userid = trim($_SESSION['userid']);
	$getflag=0;
	if($terminal_type=="")
	{
		$tree_str = "<tree id=\\\"0\\\">";
		$tree_str .="</tree>"; 
		return $tree_str;
	}
	$tree_str = "<tree id=\\\"0\\\">";
	
	if($user_name=="admin")
		$group_sql = "SELECT terminal.id,terminal.terminalname FROM terminal where typeid IN($terminal_type) ";
	else
		$group_sql = "SELECT terminal.id,terminal.terminalname FROM terminal,userterminal where typeid IN($terminal_type) and userterminal.terminalid=terminal.id and userterminal.userid=$userid ";
	$group_result = mysqli_query($con,$group_sql) or die(mysqli_error($con));

	if(mysqli_num_rows($group_result) > 0)
	{
	$get_sql = "SELECT count(id) FROM terminal where terminal.typeid IN($terminal_type)";
	$get_result = mysqli_query($con,$get_sql) or die(mysqli_error($con));	
		while($get_row = mysqli_fetch_array($get_result))
		{
		$get_number=$get_row[0];		
		}
		while($group_row = mysqli_fetch_array($group_result))
		{
			if($user_name=="admin")
			{
				$terminal_sql = "SELECT terminal.id,terminal.terminalname,leddevice.id,leddevice.name,leddevice.ip,terminaltype.name FROM terminal,leddevice,terminaltype WHERE terminaltype.id=terminal.typeid AND terminal.id=leddevice.terminalid and terminal.id='$group_row[0]'  AND terminal.typeid IN($terminal_type)";
			}
			else
			{
			$terminal_sql = "SELECT terminal.id,terminal.terminalname,leddevice.id,leddevice.name,leddevice.ip,terminaltype.name FROM terminal,leddevice,userterminal,terminaltype WHERE terminaltype.id=terminal.typeid AND terminal.id=leddevice.terminalid and terminal.id='$group_row[0]' AND userterminal.terminalid=terminal.id AND userterminal.userid='$userid' AND terminal.typeid IN($terminal_type)";
			
			}
			$terminal_result = mysqli_query($con,$terminal_sql) or die(mysqli_error($con));
			if(mysqli_num_rows($terminal_result) > 0)
			{
					if($get_number>35)
			   		{
					$tree_str.="<item text=\\\"".$group_row['terminalname']."\\\" id=\\\"stream_".$group_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
					}
					else
					{
					$tree_str.="<item text=\\\"".$group_row['terminalname']."\\\" id=\\\"stream_".$group_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
					}
			
			while($terminal_row = mysqli_fetch_array($terminal_result))
			{
				if($terminal_row[3]=="")
				continue;
			  // $faname = chinese_big5_english_terminal($_SESSION['language'],$terminal_row['4']);
			   if($get_number>35)
			   {
				$tree_str.= "<item  text=\\\"".$terminal_row[3]."-".$terminal_row[4]."\\\" id=\\\"stream_".$group_row[0]."::".$terminal_row[2]."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				}
				else
				{
				$tree_str.= "<item  text=\\\"".$terminal_row[3]."-".$terminal_row[4]."\\\" id=\\\"stream_".$group_row[0]."::".$terminal_row[2]."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				}
				
			}
			
			$tree_str.= "</item>";
			}
			@mysqli_free_result($terminal_result);
			
			unset($terminal_sql,$terminal_row);
		}
	}
	@mysqli_free_result($group_result);
		
	unset($group_sql,$group_row);
	
//=======================================================================没有分区的终端
	/*$no_group_name = $do_php_prompt['No_group_terminal'];
	if($user_name=="admin")
	{
		$no_group_sql = "SELECT terminal.id,terminal.terminalname,terminal.typeid,terminaltype.name FROM terminal,terminaltype WHERE terminal.id NOT IN ";
		$no_group_sql.= "(SELECT DISTINCT terminalofgroup.terminalid FROM terminalofgroup) AND terminal.typeid IN($terminal_type)  AND terminaltype.id=terminal.typeid ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
else
	{
		$no_group_sql = "SELECT DISTINCT  terminal.id,terminal.terminalname,terminaltype.name FROM terminal,terminaltype WHERE terminal.typeid=terminaltype.id AND terminal.id NOT IN(SELECT terminalid FROM terminalofgroup WHERE groupid IN (SELECT streamid FROM serverplaystream WHERE userid in(SELECT id FROM book_admin WHERE book_admin.username = '$user_name' )) ) AND terminal.id IN(SELECT terminalid FROM userterminal WHERE userterminal.userid IN(SELECT id FROM book_admin WHERE book_admin.username = '$user_name')) AND terminal.typeid IN($terminal_type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
	$no_group_result = mysqli_query($con,$no_group_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($no_group_result) > 0)
	{

				if($get_number>35)
				{
				$tree_str.="<item  text=\\\"".$no_group_name."\\\" id=\\\"stream_0\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
				}
				else
				{
				$tree_str.="<item  text=\\\"".$no_group_name."\\\" id=\\\"stream_0\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
				}
		while($no_group_row = mysqli_fetch_array($no_group_result))
		{
			
		
		
			$faname = chinese_big5_english_terminal($_SESSION['language'],$no_group_row['name']);
			if($get_number>35)
			{
			$tree_str.= "<item   text=\\\"".$no_group_row['terminalname']."-".$faname."\\\" id=\\\"stream_0::".$no_group_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
			}
			else
			{
			$tree_str.= "<item   text=\\\"".$no_group_row['terminalname']."-".$faname."\\\" id=\\\"stream_0::".$no_group_row['id']."\\\" close=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" ></item>";
			}
		}
		
		$tree_str.= "</item>";
	}
	*/
	$tree_str .="</tree>"; 
	
//	@mysqli_free_result($no_group_result);
	
//	unset($no_group_sql,$no_group_row);

	return $tree_str;
}


function get_quick_terminal($terminal_type,$terminalid)
{	
	global $con;
	global $do_php_prompt;
	$user_name = trim($_SESSION['username']);
	$userid = trim($_SESSION['userid']);
	$tree_str = "<tree id=\\\"0\\\">";
	
	if($user_name=="admin")
		$group_sql = "SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream ";
	else
		$group_sql = "SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream where userid='$userid' ";
	$group_result = mysqli_query($con,$group_sql) or die(mysqli_error($con));

	if(mysqli_num_rows($group_result) > 0)
	{	
		while($group_row = mysqli_fetch_array($group_result))
		{
			if($user_name=="admin")
			{
				$terminal_sql = "SELECT terminal.id,terminal.terminalname,terminaltype.name,terminal.typeid FROM terminal ,terminaltype WHERE terminal.id !='$terminalid' AND terminal.id IN ";
				$terminal_sql.= "(SELECT terminalid FROM terminalofgroup WHERE terminalofgroup.groupid='".$group_row['streamid']."') AND terminaltype.id=terminal.typeid AND terminal.typeid IN($terminal_type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
			}
			else
			{
			$terminal_sql = "SELECT terminal.id,terminal.terminalname,terminaltype.name,terminal.typeid FROM terminal ,terminaltype WHERE terminal.id !='$terminalid' AND terminal.id IN ";
			$terminal_sql.= "(SELECT terminalid FROM terminalofgroup WHERE terminalofgroup.groupid='".$group_row['streamid']."' and terminalid IN(select terminalid from userterminal where userid='$userid')) AND terminaltype.id=terminal.typeid AND terminal.typeid IN($terminal_type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
			}
			$terminal_result = mysqli_query($con,$terminal_sql) or die(mysqli_error($con));
			if(mysqli_num_rows($terminal_result) > 0)
			{
			$tree_str.="<item text=\\\"".$group_row['name']."\\\" id=\\\"stream_".$group_row['streamid']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
			while($terminal_row = mysqli_fetch_array($terminal_result))
			{
			   $faname = chinese_big5_english_terminal($_SESSION['language'],$terminal_row['name']);
				$tree_str.= "<item  text=\\\"".$terminal_row['terminalname']."-".$faname."\\\" id=\\\"stream_".$group_row['streamid']."::".$terminal_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
				
			}
			
			$tree_str.= "</item>";
			}
			@mysqli_free_result($terminal_result);
			
			unset($terminal_sql,$terminal_row);
		}
	}
	@mysqli_free_result($group_result);
		
	unset($group_sql,$group_row);
//=======================================================================没有分区的终端
	$no_group_name = $do_php_prompt['No_group_terminal'];
	if($user_name=="admin")
	{
		$no_group_sql = "SELECT terminal.id,terminal.terminalname,terminal.typeid,terminaltype.name FROM terminal,terminaltype WHERE terminal.id !='$terminalid' AND terminal.id NOT IN ";
		$no_group_sql.= "(SELECT DISTINCT terminalofgroup.terminalid FROM terminalofgroup) AND terminal.typeid IN($terminal_type)  AND terminaltype.id=terminal.typeid ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
else
	{
		$no_group_sql = "SELECT DISTINCT terminal.id,terminal.terminalname,terminaltype.name FROM terminal,terminaltype WHERE terminal.id !='$terminalid' AND terminal.typeid=terminaltype.id AND terminal.id NOT IN(SELECT terminalid FROM terminalofgroup WHERE groupid IN (SELECT streamid FROM serverplaystream WHERE userid in(SELECT id FROM book_admin WHERE book_admin.username = '$user_name' )) ) AND terminal.id IN(SELECT terminalid FROM userterminal WHERE userterminal.userid IN(SELECT id FROM book_admin WHERE book_admin.username = '$user_name')) AND terminal.typeid IN($terminal_type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
	$no_group_result = mysqli_query($con,$no_group_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($no_group_result) > 0)
	{
		$tree_str.="<item  text=\\\"".$no_group_name."\\\" id=\\\"stream_0\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		
		while($no_group_row = mysqli_fetch_array($no_group_result))
		{
			$faname = chinese_big5_english_terminal($_SESSION['language'],$no_group_row['name']);
			$tree_str.= "<item   text=\\\"".$no_group_row['terminalname']."-".$faname."\\\" id=\\\"stream_0::".$no_group_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
		}
		
		$tree_str.= "</item>";
	}
	$tree_str .="</tree>"; 
	
	@mysqli_free_result($no_group_result);
	
	unset($no_group_sql,$no_group_row);

	return $tree_str;
}





function createofflineterminal($terminal_type)
{	
	global $con;
	global $do_php_prompt;
	$user_name = trim($_SESSION['username']);
	$userid = trim($_SESSION['userid']);
	$tree_str = "<tree id=\\\"0\\\">";
	
	if($user_name=="admin")
		$group_sql = "SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream ";
	else
		$group_sql = "SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream where userid=$userid ";
	$group_result = mysqli_query($con,$group_sql) or die(mysqli_error($con));

	if(mysqli_num_rows($group_result) > 0)
	{
		while($group_row = mysqli_fetch_array($group_result))
		{
			if($user_name=="admin")
			{
				$terminal_sql = "SELECT terminal.id,terminal.terminalname,terminaltype.name,terminal.typeid FROM terminal ,terminaltype WHERE terminal.id IN ";
				$terminal_sql.= "(SELECT terminalid FROM terminalofgroup WHERE  terminalofgroup.groupid='".$group_row['streamid']."') AND terminaltype.id=terminal.typeid AND totalcapacity!=0 AND terminal.typeid IN($terminal_type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
			}
			else
			{
			$terminal_sql = "SELECT terminal.id,terminal.terminalname,terminaltype.name,terminal.typeid FROM terminal ,terminaltype WHERE terminal.id IN ";
				$terminal_sql.= "(SELECT terminalid FROM terminalofgroup WHERE terminalofgroup.groupid='".$group_row['streamid']."' and terminalid IN(select terminalid from userterminal where userid='$userid')) AND totalcapacity!=0 AND terminaltype.id=terminal.typeid AND terminal.typeid IN($terminal_type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
			
			}
			$terminal_result = mysqli_query($con,$terminal_sql) or die(mysqli_error($con));
			if(mysqli_num_rows($terminal_result) > 0)
			{
				$tree_str.="<item text=\\\"".$group_row['name']."\\\" id=\\\"stream_".$group_row['streamid']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
				
				while($terminal_row = mysqli_fetch_array($terminal_result))
				{
				   $faname = chinese_big5_english_terminal($_SESSION['language'],$terminal_row['name']);
					$tree_str.= "<item  text=\\\"".$terminal_row['terminalname']."-".$faname."\\\" id=\\\"stream_".$group_row['streamid']."::".$terminal_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
					
				}
				$tree_str.= "</item>";
			}
			@mysqli_free_result($terminal_result);
			
			unset($terminal_sql,$terminal_row);
		}
	}
	@mysqli_free_result($group_result);
		
	unset($group_sql,$group_row);
//=======================================================================没有分区的终端
	$no_group_name = $do_php_prompt['No_group_terminal'];
	if($user_name=="admin")
	{
		$no_group_sql = "SELECT terminal.id,terminal.terminalname,terminal.typeid,terminaltype.name FROM terminal,terminaltype WHERE terminal.id NOT IN ";
		$no_group_sql.= "(SELECT DISTINCT terminalofgroup.terminalid FROM terminalofgroup) AND totalcapacity!=0 AND terminal.typeid IN($terminal_type)  AND terminaltype.id=terminal.typeid ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
else
	{
		$no_group_sql = "SELECT DISTINCT terminal.id,terminal.terminalname,terminaltype.name FROM terminal,terminaltype WHERE terminal.typeid=terminaltype.id AND terminal.id NOT IN(SELECT terminalid FROM terminalofgroup WHERE groupid IN (SELECT streamid FROM serverplaystream WHERE userid in(SELECT id FROM book_admin WHERE book_admin.username = '$user_name' )) ) AND terminal.id IN(SELECT terminalid FROM userterminal WHERE userterminal.userid IN(SELECT id FROM book_admin WHERE book_admin.username = '$user_name')) AND totalcapacity!=0 AND terminal.typeid IN($terminal_type) ORDER BY CONVERT(terminal.terminalname USING utf8)";
	}
	$no_group_result = mysqli_query($con,$no_group_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($no_group_result) > 0)
	{
		$tree_str.="<item  text=\\\"".$no_group_name."\\\" id=\\\"stream_0\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
		
		while($no_group_row = mysqli_fetch_array($no_group_result))
		{
			$faname = chinese_big5_english_terminal($_SESSION['language'],$no_group_row['name']);
			$tree_str.= "<item   text=\\\"".$no_group_row['terminalname']."-".$faname."\\\" id=\\\"stream_0::".$no_group_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
		}
		
		$tree_str.= "</item>";
	}
	$tree_str .="</tree>"; 
	
	@mysqli_free_result($no_group_result);
	
	unset($no_group_sql,$no_group_row);

	return $tree_str;
}



function get_grouped_termina2($terminal_type,$get_terminal_id)
{	
	
}


//创建非超级用户树
function create_grouped_OrNo($terminal_type,$user_name)
{
	global $do_php_prompt;
	global $con;
	$grouped_ids = "";
	
	$grouped_info = "";
	
	$grouped_flag = 0;

	$tree_str = "<tree id=\\\"0\\\">";

	$grouped_sql = "SELECT serverplaystream.streamid,serverplaystream.name, ";

	$grouped_sql.= "terminal.id,terminal.terminalname,terminal.typeid ";

	$grouped_sql.= "FROM serverplaystream, terminalofgroup, terminal, userterminal ";

	$grouped_sql.= "WHERE ";

	$grouped_sql.= "serverplaystream.streamid = terminalofgroup.groupid ";

	$grouped_sql.= "AND terminalofgroup.terminalid = userterminal.terminalid ";

	$grouped_sql.= "AND terminal.id = terminalofgroup.terminalid AND userterminal.userid = ";

	$grouped_sql.= "(SELECT id FROM book_admin WHERE book_admin.username = '$user_name') ";

	$grouped_sql.= "AND terminal.typeid IN($terminal_type) ORDER BY serverplaystream.name ";
	
	$grouped_result = mysqli_query($con,$grouped_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($grouped_result) > 0 )
	{
		while($grouped_row = mysqli_fetch_array($grouped_result))
		{
			//取组和组与终端
			if($grouped_flag == 0)
			{
				$grouped_ids[] = $grouped_row['streamid'];
				
				$grouped_flag++;
				
				$tree_str.="<item  text=\\\"".$grouped_row['name']."\\\" id=\\\"stream_".$grouped_row['streamid']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
			}
			
			
			
			if($grouped_ids[count($grouped_ids)-1] != $grouped_row['streamid'])
			{
				
				
				$tree_str.="</item><item  text=\\\"".$grouped_row['name']."\\\" id=\\\"stream_".$grouped_row['streamid']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
				
				$grouped_ids[] = $grouped_row['streamid'];
			}
			
			$tree_str.= "<item  text=\\\"".$grouped_row['terminalname']."\\\" id=\\\"stream_".$grouped_row['streamid']."::".$grouped_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
		}
		
		$tree_str.="</item>";
	}
	@mysqli_free_result($grouped_result);
	
	unset($grouped_sql,$grouped_row);
	//============================================================================
	$no_group_name = $do_php_prompt['No_group_terminal'];
	
	$no_grouped_sql = "SELECT terminal.id,terminal.terminalname, terminal.typeid FROM terminal ";

	$no_grouped_sql.= "WHERE terminal.typeid IN ($terminal_type) ";

	$no_grouped_sql.= "AND terminal.id IN(SELECT userterminal.terminalid FROM userterminal WHERE userterminal.terminalid NOT IN ";

	$no_grouped_sql.= "(SELECT terminalofgroup.terminalid FROM terminalofgroup) AND userterminal.userid = ";

	$no_grouped_sql.= "(SELECT book_admin.id FROM book_admin WHERE book_admin.username='$user_name') )";
	
	$no_grouped_result = mysqli_query($con,$no_grouped_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($no_grouped_result) > 0)
	{
		$tree_str.="<item  text=\\\"".$no_group_name."\\\" id=\\\"stream_0\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
	
		while($no_grouped_row = mysqli_fetch_array($no_grouped_result))
		{
			$tree_str.= "<item   text=\\\"".$no_grouped_row['terminalname']."\\\" id=\\\"stream_0::".$no_grouped_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
		}
		$tree_str.= "</item>";
	}
	
	$tree_str .="</tree>";
	
	@mysqli_free_result($no_grouped_result);
	
	unset($no_grouped_sql,$no_grouped_row);

	return $tree_str; 
}
function create_grouped_OrNo2($terminal_type,$user_name,$get_terminal_id)
{
	global $do_php_prompt;
	global $con;
	$grouped_ids = "";
	
	$grouped_info = "";
	
	$grouped_flag = 0;

	$tree_str = "<tree id=\\\"0\\\">";

	$grouped_sql = "SELECT serverplaystream.streamid,serverplaystream.name, ";

	$grouped_sql.= "terminal.id,terminal.terminalname,terminal.typeid ";

	$grouped_sql.= "FROM serverplaystream, terminalofgroup, terminal, userterminal ";

	$grouped_sql.= "WHERE ";

	$grouped_sql.= "serverplaystream.streamid = terminalofgroup.groupid ";

	$grouped_sql.= "AND terminalofgroup.terminalid = userterminal.terminalid ";

	$grouped_sql.= "AND terminal.id = terminalofgroup.terminalid AND userterminal.userid = ";

	$grouped_sql.= "(SELECT id FROM book_admin WHERE book_admin.username = '$user_name') ";

	$grouped_sql.= "AND terminal.typeid IN($terminal_type) AND terminal.id !='$get_terminal_id' ORDER BY serverplaystream.name ";
	
	$grouped_result = mysqli_query($con,$grouped_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($grouped_result) > 0 )
	{
		while($grouped_row = mysqli_fetch_array($grouped_result))
		{
			//取组和组与终端
			if($grouped_flag == 0)
			{
				$grouped_ids[] = $grouped_row['streamid'];
				
				$grouped_flag++;
				
				$tree_str.="<item  text=\\\"".$grouped_row['name']."\\\" id=\\\"stream_".$grouped_row['streamid']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
			}

			if($grouped_ids[count($grouped_ids)-1] != $grouped_row['streamid'])
			{
				
				
				$tree_str.="</item><item  text=\\\"".$grouped_row['name']."\\\" id=\\\"stream_".$grouped_row['streamid']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
				
				$grouped_ids[] = $grouped_row['streamid'];
			}
			
			$tree_str.= "<item  text=\\\"".$grouped_row['terminalname']."\\\" id=\\\"stream_".$grouped_row['streamid']."::".$grouped_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
		}
		
		$tree_str.="</item>";
	}
	@mysqli_free_result($grouped_result);
	
	unset($grouped_sql,$grouped_row);
	//============================================================================
	$no_group_name = $do_php_prompt['No_group_terminal'];
	
	$no_grouped_sql = "SELECT terminal.id,terminal.terminalname, terminal.typeid FROM terminal ";

	$no_grouped_sql.= "WHERE terminal.typeid IN ($terminal_type) ";

	$no_grouped_sql.= "AND terminal.id IN(SELECT userterminal.terminalid FROM userterminal WHERE userterminal.terminalid NOT IN ";

	$no_grouped_sql.= "(SELECT terminalofgroup.terminalid FROM terminalofgroup) AND terminal.id !='$get_terminal_id' AND userterminal.userid = ";

	$no_grouped_sql.= "(SELECT book_admin.id FROM book_admin WHERE book_admin.username='$user_name') )";
	
	$no_grouped_result = mysqli_query($con,$no_grouped_sql) or die(mysqli_error($con));
	
	if(mysqli_num_rows($no_grouped_result) > 0)
	{
		$tree_str.="<item  text=\\\"".$no_group_name."\\\" id=\\\"stream_0\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >";
	
		while($no_grouped_row = mysqli_fetch_array($no_grouped_result))
		{
			$tree_str.= "<item   text=\\\"".$no_grouped_row['terminalname']."\\\" id=\\\"stream_0::".$no_grouped_row['id']."\\\" open=\\\"1\\\" im0=\\\"tombs.gif\\\" im1=\\\"tombs_open.gif\\\" im2=\\\"iconSafe.gif\\\" >  </item>";
		}
		$tree_str.= "</item>";
	}
	
	$tree_str .="</tree>";
	
	@mysqli_free_result($no_grouped_result);
	
	unset($no_grouped_sql,$no_grouped_row);

	return $tree_str; 
}
//==================================================================
//	通过SQL生成已分组的终端
//==================================================================
function create_tree_str($terminal_type)
{	
	//check_user_operation_terminal($admin_id,$user_name,$terminal_type);
	//if($admin_id == "administrator")
	//{
		return get_grouped_terminal($terminal_type);
	//}
	//else
	//{
		//return create_grouped_OrNo($terminal_type,$user_name);	
	//}
}

function create_zhaoshengtree_str($terminal_type)
{	
	//check_user_operation_terminal($admin_id,$user_name,$terminal_type);
	//if($admin_id == "administrator")
	//{
		return get_zhaoshenggrouped_terminal($terminal_type);
	//}
	//else
	//{
		//return create_grouped_OrNo($terminal_type,$user_name);	
	//}
}

function create_led_tree_str($terminal_type)
{	
	//check_user_operation_terminal($admin_id,$user_name,$terminal_type);
	//if($admin_id == "administrator")
	//{
	
		return get_grouped_led_terminal($terminal_type);
	//}
	//else
	//{
		//return create_grouped_OrNo($terminal_type,$user_name);	
	//}
}


function get_terminallist3($type,$get_terminal_id)
{
	$admin_id = trim($_SESSION['admin_id']);
	
	$user_name = trim($_SESSION['username']);
	
	check_user_operation_terminal($admin_id,$user_name,$type);
	
	if($admin_id == "administrator")
	{
		return get_grouped_termina2($type,$get_terminal_id);
	}
	
}

/*******************************************************************
	读取任务终端id及分区id
*******************************************************************/
function get_current_task_termianl_id($task_id)
{
	global $con;
	$task_termianl_sql = "SELECT terminalid,groupid,area FROM terminaloftask WHERE terminaloftask.taskid = $task_id ORDER BY groupid ";
	
	$task_termianl_result = mysqli_query($con,$task_termianl_sql) or die(mysqli_error($con));
	$task_termianl_group_ids=array();
	while($task_termianl_row = mysqli_fetch_array($task_termianl_result))
	{
		$task_termianl_group_ids[] = array("terminal_id"=>$task_termianl_row['terminalid'],"group_id"=>$task_termianl_row['groupid'],"area"=>$task_termianl_row['area']);
	}
	
	mysqli_free_result($task_termianl_result);
	
	unset($task_termianl_sql,$task_termianl_row);
	
	return $task_termianl_group_ids;
}



function get_current_task_termianl_id3($con,$task_id)
{

	$task_termianl_sql = "SELECT terminalid,groupid FROM terminalofalarmgroup WHERE terminalofalarmgroup.alarmgroupid = $task_id ORDER BY groupid ";

	$task_termianl_group_ids=array();
	$task_termianl_result = mysqli_query($con,$task_termianl_sql) or die(mysqli_error($con));
	
	while($task_termianl_row = mysqli_fetch_array($task_termianl_result))
	{
		$task_termianl_group_ids[] = array("terminal_id"=>$task_termianl_row['terminalid'],"group_id"=>$task_termianl_row['groupid']);
	}
	
	mysqli_free_result($task_termianl_result);
	
	unset($task_termianl_sql,$task_termianl_row);
	
	return $task_termianl_group_ids;
}

function get_current_offlinetask()
{
	global $con;
	$task_termianl_group_ids = "";
	
	$task_termianl_sql = "SELECT taskid, taskname FROM task WHERE task.tasktype IN (2,1) AND taskid IN (SELECT taskid FROM terminaloftask WHERE offlineparam IN(2,1,3))";
	
	$task_termianl_result = mysqli_query($con,$task_termianl_sql) or die(mysqli_error($con));
	
	while($task_termianl_row = mysqli_fetch_array($task_termianl_result))
	{
		$task_termianl_group_ids[] = array("taskid"=>$task_termianl_row['taskid'],"taskname"=>$task_termianl_row['taskname']);
	}
	
	@mysqli_free_result($task_termianl_result);
	
	unset($task_termianl_sql,$task_termianl_row);
	
	return $task_termianl_group_ids;
}


//========================================================================
function get_task_termianl_group_id($task_id)
{
	global $con;
	$task_of_info = "";

	$sql_task = "SELECT terminal.id,terminaloftask.groupid,terminaloftask.area, terminal.terminalname, ";
	$sql_task.= "terminal.typeid, terminal.netstate, terminal.devicestate,terminaloftask.area ";

	$sql_task.= "terminal.taskstate, terminal.ip, terminal.volume ";

	$sql_task.= "FROM terminal,terminaloftask ";

	$sql_task.= "WHERE terminal.id = terminaloftask.terminalid ";

	$sql_task.= "AND terminaloftask.taskid = '$task_id' ORDER BY CONVERT(terminal.terminalname USING utf8) ";
	
	$result_task = mysqli_query($con,$sql_task) or die(mysqli_error($con));
	
	while($row_task = mysqli_fetch_array($result_task))
	{
		$task_of_info[] = array("terminal_id"=>$row_task['id'],"group_id"=>$row_task['groupid'],"area"=>$row_task['area'],"terminalname"=>$row_task['terminalname'],
									
								"typeid"=>$row_task['typeid'],"netstate"=>$row_task['netstate'],"devicestate"=>$row_task['devicestate'],
								
								"taskstate"=>$row_task['taskstate'],"ip"=>$row_task['ip'],"volume"=>$row_task['volume']
							);
	}
	
	@mysqli_free_result($result_task);
	
	unset($sql_task,$row_task);
	
	return $task_of_info;
}

//===================================================================================

function get_all_group_info()
{
	global $con;
	$group_all_info = "";
	
	$group_result = mysqli_query($con,"SELECT serverplaystream.streamid,serverplaystream.name FROM serverplaystream") or die(mysqli_error($con));
	
	while($group_row = mysqli_fetch_array($group_result))
	{
		$group_all_info[] = array("streamid"=>$group_row['streamid'],"streamname"=>$group_row['name']);
	}
	
	@mysqli_free_result($group_result);
	
	unset($group_row);
	
	return $group_all_info;
}

function get_folder_info($parentid )
{
	global $con;
/*	$username = trim($_SESSION['username']);
	$user_sql = "SELECT id FROM book_admin WHERE book_admin.username = '$username'";
	$user_result = mysqli_query($con,$user_sql) or die(mysqli_error($con));
	if($user_row = mysqli_fetch_array($user_result))
	{
		$group_id = $user_row['id'];
	}

	if($group_id == 1)
	{
	*/	//获取所有文件夹
		$userid=$_SESSION['userid'];
		if($parentid==5)
		{
			$username = chinese_big5_english_tree($_SESSION['language'],'录音媒体库');
			$sqls="SELECT id FROM filefolder WHERE filefolder.name ='$username' AND parentid='5'";
			$results = mysqli_query($con,$sqls) or die(mysqli_error($con));
			if(mysqli_num_rows($results) <= 0)
			{
				mysqli_query($con,"insert into filefolder (name,userid,parentid) values('$username','1', '5')") or die(mysqli_error($con));
			}
			
			$sql3="SELECT id FROM filefolder WHERE filefolder.name ='$username' AND parentid='5'";
			$result3 = mysqli_query($con,$sql3) or die(mysqli_error($con));
			if($row3 = mysqli_fetch_array($result3))
			{
				mysqli_query($con,"UPDATE media SET folderid ='$row3[id]' WHERE folderid='5'");	
			}	
		
			$sql="SELECT DISTINCT id,username as name FROM book_admin WHERE id IN(SELECT userid FROM media)";
			$result = mysqli_query($con,$sql) or die(mysqli_error($con));
			while($row = mysqli_fetch_array($result))
			{
				$username=$row['name'];
				$sqls="SELECT id FROM filefolder WHERE filefolder.name ='$username' AND parentid='5'";
				$results = mysqli_query($con,$sqls) or die(mysqli_error($con));
				if(mysqli_num_rows($results) <= 0 )
				{
					mysqli_query($con,"insert into filefolder (name,userid,parentid) values('$username','$row[id]', '5')") or die(mysqli_error($con));
				}
				/*
				$sql2="SELECT id FROM filefolder WHERE filefolder.name ='$username' AND parentid='5'";
				$result2 = mysqli_query($con,$sql2) or die(mysqli_error($con));
				while($row2 = mysqli_fetch_array($result2))
				{
					mysqli_query($con,"UPDATE media SET folderid ='$row2[id]' WHERE media.userid = '$row[id]'");	
				}	
				*/
			}
		}
	

		if($_SESSION['username']=="admin")
		{
			$sql_folder = "SELECT filefolder.id,filefolder.name FROM filefolder where parentid = $parentid  order by id";
		}
		else
		{
				if($parentid==0)
				{
					$sql_folder = "SELECT filefolder.id,filefolder.name FROM filefolder where parentid = $parentid  order by id";
				}
				else
				{
				
				$sql_folder = "SELECT filefolder.id,filefolder.name FROM filefolder where parentid = $parentid  and userid='$userid' order by id";
				}
		}
	/*}
	else
	{
		//获取该用户文件夹和共享及其它用户共享的文件夹
		$sql_folder = "SELECT DISTINCT  filefolder.id,filefolder.name FROM filefolder ";
		$sql_folder.= " where parentid = $parentid and( filefolder.id IN (1,2,3,4,5) OR ";
		$sql_folder.= " filefolder.userid = '$group_id') ORDER BY id ";
		
		//$sql_folder = "SELECT filefolder.id,filefolder.name FROM filefolder where parentid = $parentid order by id";
	}
	 */ 
	$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
	
	return $result_folder;
}



function terminal_area_info($parentid )
{
	global $con;

		$userid=$_SESSION['userid'];
	
		if($_SESSION['username']=="admin")
		{
			$sql_folder = "SELECT streamid,serverplaystream.name FROM serverplaystream order by streamid";
		}
		else
		{
				$sql_folder = "SELECT streamid,serverplaystream.name FROM serverplaystream where userid='$userid' order by streamid";
		}

	$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
	
	return $result_folder;
}


function get_area_dir_info($parentid,$terminalid)
{
	global $con;
	if($parentid==-1)
	{
	$sql_dirarea = "SELECT id,name FROM terminalfolder where parentid = 0 and terminalid=$terminalid order by id";
	}
	else if($parentid==0)
	{
		$sql_dirarea = "SELECT id,name FROM terminalfolder where terminalid=$terminalid and parentid in(select id from terminalfolder where parentid = $parentid and terminalid=$terminalid) order by id";
	}
	else
	{
		$sql_dirarea = "SELECT id,name FROM terminalfolder where parentid = $parentid and terminalid=$terminalid order by id";
	}
	
	$result_dirarea = mysqli_query($con,$sql_dirarea) or die(mysqli_error($con));
	return $result_dirarea;
}

function get_filetask_userinfo()
{
	global $con;
	$userid=$_SESSION['userid'];	
	if($_SESSION['username']=="admin")
	{
		//$sql_folder = "SELECT book_admin.id,book_admin.username FROM book_admin WHERE id IN(SELECT userid FROM filetaskfree) order by id";
		$sql_folder = "SELECT book_admin.id,book_admin.username FROM book_admin order by id";
		$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
		/*while($rowfunid = mysqli_fetch_array($result_folder))
		{
				$sql_fss = "SELECT userid FROM filetaskfree WHERE userid = '$rowfunid[0]'";
				$resultss = mysqli_query($con,$sql_fss) or die(mysqli_error($con));	
				if( mysqli_num_rows($resultss) <=0 )
				{
					$terminalsql="insert into filetaskfree (name,parentid,userid) values('$rowfunid[1]','0','$rowfunid[0]')";
			
					mysqli_query($con,$terminalsql) or die(mysqli_error($con));

				}

		}*/
		
	 }
	 else
	 {

	 	$sql_folder = "SELECT book_admin.id,book_admin.username FROM book_admin WHERE id ='$userid' order by id";
		$result_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));
	 } 
	

	return $result_folder;
}



?>