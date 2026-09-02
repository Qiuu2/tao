<?php
	if (!session_id()) session_start();
	header("content-type:text/html;charset=utf-8");
	require_once("inc/config.inc.php");
	require_once("inc/socket_conf.php");
	$taskmax="";
	$taskmax2="";
	$superpass = "";
	if(isset($_GET['superpass']))
	{
		$superpass = trim($_GET['superpass']);
	}
	if(isset($_GET['setversion']))
	{
		$setsersion = trim($_GET['setversion']);
	}
	$flagg=0;
	if(isset($_GET['flagg']))
	{
		$flagg = trim($_GET['flagg']);
	}

	if($flagg==1)
	{
		$pass_word = md5($superpass);
		$user_word = $_SESSION['username'];
		mysqli_query($con,"update book_admin set userpwd='$pass_word' where username='$user_word'");	
		echo 0;	
		return;
	}

	$system="";
	$pagecharacter='utf-8';
	if(isset($_GET['sysname']))
	{
		$sysname = trim($_GET['sysname']);
		$codes=strtolower(mb_detect_encoding($sysname, array('GB2312','UTF-8','GBK','ASCII')));
	if(($codes=='gb2312' || $codes=='utf-8' || $codes=='euc-cn') && $codes!=$pagecharacter)
	{
		$sysname=iconv($codes,$pagecharacter,$sysname);  
		//$getonelessonname = mb_convert_encoding($_GET['getonelessonname'],"utf-8", $code);
	}
		mysqli_query($con,"update serverbaseparam set projectname='$sysname'");	
		echo "1";	

	}
	$output_info=array();
	//$fh=fopen("tianyaluzhang.txt","a"); 

	//fwrite($fh,$setsersion); 
	if($setsersion==1)
	{
		$tempinfo = sprintf("tar -zxvf %s/sounds/audioserver/audioserver-v2.2.tar.gz -C %s/sounds/audioserver/",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";

	//	fwrite($fh,$command); 

		exec($command, $output_info,$last_line);
		$tempinfo = sprintf("tar -zxvf %s/sounds/serverprogram/serverprogram-v2.4.tar.gz -C %s/sounds/serverprogram/",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
			$tempinfo = sprintf("rm -rf %s/sounds/serverprogram/etc/*",$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
		$command="cmdhost -c 'sudo reboot'";
		system($command);
		echo "1";
	}
	else if($setsersion==2)
	{
		$tempinfo = sprintf("tar -zxvf %s/sounds/audioserver/audioserver-v2.3.tar.gz -C %s/sounds/audioserver/",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
	//	fwrite($fh,$command); 
		exec($command, $output_info,$last_line);
			$tempinfo = sprintf("tar -zxvf %s/sounds/serverprogram/serverprogram-v2.4.tar.gz -C %s/sounds/serverprogram/",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);

	
		$tempinfo = sprintf("rm -rf %s/sounds/serverprogram/etc/*",$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);

		$command="cmdhost -c 'sudo reboot'";
		system($command);
		echo "1";
	}
	else if($setsersion==3)
	{
		$tempinfo = sprintf("tar -zxvf %s/sounds/audioserver/audioserver-v2.4.tar.gz -C %s/sounds/audioserver/",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";

		//fwrite($fh,$command);
		exec($command, $output_info,$last_line);
		$tempinfo = sprintf("tar -zxvf %s/sounds/serverprogram/serverprogram-v2.4.tar.gz -C %s/sounds/serverprogram/",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
			$tempinfo = sprintf("rm -rf %s/sounds/serverprogram/etc/*",$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
		$command="cmdhost -c 'sudo reboot'";
		system($command);
		echo "1";
	}
	else if($setsersion==4)
	{
		$tempinfo = sprintf("tar -zxvf %s/sounds/audioserver/audioserver-v2.5.tar.gz -C %s/sounds/audioserver/",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";

		//fwrite($fh,$command);

		exec($command, $output_info,$last_line);
		$tempinfo = sprintf("tar -zxvf %s/sounds/serverprogram/serverprogram-v2.4.tar.gz -C %s/sounds/serverprogram/",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
			$tempinfo = sprintf("rm -rf %s/sounds/serverprogram/etc/*",$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
		$command="cmdhost -c 'sudo reboot'";
		system($command);
		echo "1";
	}
	else if($setsersion==5)
	{
		$tempinfo = sprintf("tar -zxvf %s/sounds/audioserver/audioserver-v1.6.tar.gz -C %s/sounds/audioserver/",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		$tempinfo = sprintf("tar -zxvf %s/sounds/serverprogram/serverprogram-v2.4.tar.gz -C %s/sounds/serverprogram/",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
		$tempinfo = sprintf("rm -rf %s/sounds/serverprogram/etc/*",$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
		//fwrite($fh,$command);
		exec($command, $output_info,$last_line);
		$command="cmdhost -c 'sudo reboot'";
		system($command);
		echo "1";
	}
	else if($setsersion==6)
	{
		$tempinfo = sprintf("tar -zxvf %s/sounds/audioserver/audioserver-tw1.0.tar.gz -C %s/sounds/audioserver/",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
		//fwrite($fh,$command);

		$tempinfo = sprintf("tar -zxvf %s/sounds/serverprogram/serverprogram-tw1.0.tar.gz -C %s/sounds/serverprogram/",$a9000path,$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
		$tempinfo = sprintf("rm -rf %s/sounds/serverprogram/etc/*",$a9000path);
		$command = "cmdhost --cmd=\"".$tempinfo."\"";
		exec($command, $output_info,$last_line);
		$command="cmdhost -c 'sudo reboot'";
		system($command);
		echo "1";
	}
	//fclose($fh); 
	if($superpass=="001")
	{
		echo "1";
	}
	else if($superpass=="010")
	{
		echo "2";
	}
	else if($superpass=="100")
	{
		$_SESSION['webport']=1;
		echo "3";
	}
	else if($superpass=="adminht")
	{
		echo "3";
	}
	else if($superpass=="terminal")
	{
	/*	$i=0;
		$get_result="";
		$sql = "SELECT sn FROM usersn where userid=0";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		while($row = mysqli_fetch_array($result))
		{
			if($i==0)
			{
			  $get_result=$row['sn'];
			}
			else
			{
			 $get_result= $get_result.','.$row['sn'];
			}
			if($row['sn']!=NULL&&$row['sn']!="")
				$i++;
		}
		echo  $get_result;
		*/
		echo "4";
	}
	else if($superpass=="set_mac")  //对应set_mac.php
	{
		$id = "";
		if(isset($_GET['id']))
		{
			$id = trim($_GET['id']);
		}
		$get_mac = "";
		if(isset($_GET['get_mac']))
		{
			$get_mac = trim($_GET['get_mac']);
		}

		$sql3 = "SELECT sn FROM usersn where sn='$get_mac'";
		$result3 = mysqli_query($con,$sql3) or die(mysqli_error($con));
		if(mysqli_num_rows($result3) <= 0)
		{
			$sql = "SELECT sn FROM usersn where id='$id'";
			$result = mysqli_query($con,$sql) or die(mysqli_error($con));
			if(mysqli_num_rows($result) <= 0)
			{
				mysqli_query($con,"INSERT INTO usersn (sn,userid)VALUES('$get_mac',0)") or die(mysqli_error($con));
				$sql2 = "SELECT max(id) FROM usersn";
				$result2 = mysqli_query($con,$sql2) or die(mysqli_error($con));
				if($row = mysqli_fetch_array($result2))
				{
					echo $row[0];	
				}
			}
			else
			{
				mysqli_query($con,"update usersn set sn='$get_mac' where id='$id' and userid='0'");	
				echo $id;	
			}
		}
		else
			echo "0";
		
		
	}
	else if($superpass=="del_mac")
	{
		$id = "";
		if(isset($_GET['id']))
		{
			$id = trim($_GET['id']);
		}
		mysqli_query($con,"DELETE FROM usersn WHERE id = '$id' and userid='0'") or die(mysqli_error($con));
		echo "1";
	}
	else
	{
		echo "0";
	}
			
?>