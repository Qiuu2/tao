<?php
if (!session_id()) session_start();
header('Content-Type:text/html;charset=utf-8');
require_once('inc/config.inc.php');
require_once('inc/config.php');
require_once('inc/socket_conf.php');

$terminal_id = "";
	if(isset($_GET['terminal_id']))
	{
		$terminal_id = $_GET['terminal_id'];
	}  
	$terminalids = "";
	if(isset($_POST['terminalids']))
	{
		$terminalids = $_POST['terminalids'];
	} 
	
	$terminalnum ="";
	if(isset($_POST['terminalnum']))
	{
		$terminalnum = $_POST['terminalnum'];
	}
	
	if($_FILES["file"]["name"]=="public.crt")
	{
		$command = "chmod 777 link/apache/public.crt";
	@system($command);
	$command = "cp -rf link/apache/public.crt",$_FILES["file"]["name"];
	@system($command);
	$hrefaddr="view_shengji.php?terminal_id=".$terminal_id;
	$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
	$_SESSION['url'] = $hrefaddr;
	echo "<script>window.location='success.php'</script>";

	}
 else	if($_FILES["file"]["name"]=="server_ca.crt")
	{
		$command = "chmod 777 link/apache/server_ca.crt";
	@system($command);
	$command = "cp -rf link/apache/server_ca.crt",$_FILES["file"]["name"];
	@system($command);
	$hrefaddr="view_shengji.php?terminal_id=".$terminal_id;
	$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
	$_SESSION['url'] = $hrefaddr;
	echo "<script>window.location='success.php'</script>";

	}
 else	if($_FILES["file"]["name"]=="server.crt")
	{
		$command = "chmod 777 link/apache/server.crt";
	@system($command);
	$command = "cp -rf link/apache/server.crt",$_FILES["file"]["name"];
	@system($command);
	$hrefaddr="view_shengji.php?terminal_id=".$terminal_id;
	$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
	$_SESSION['url'] = $hrefaddr;
	echo "<script>window.location='success.php'</script>";

	} 
	else
	{
	$command = "rm -rf link/backup/upgradefile/".$_FILES["file"]["name"];
	@system($command);
  if (file_exists("link/backup/upgradefile/" . $_FILES["file"]["name"]))
      {
		if($terminal_id=="")
		{	
			$hrefaddr="terminalupgrade.php?terminalnum=".$terminalnum."&terminalids=".$terminalids;
			$socket	=	new	send_message_to_server($sdkport_conf);	
			$getmedianame="link/backup/upgradefile/". $_FILES["file"]["name"];
			
			$medianame=$socket->toStr($socket->getallBytes($getmedianame,256));
			$terminalcount=$socket->toStr($socket->integerToBytes($terminalnum));
			$terminallistid = explode(",",$terminalids);
			for($i=0;$i<256;$i++)
			{
				if($i<$terminalnum)
				{
					if($i==0)
					{
						$terminalid=$socket->toStr($socket->integerToBytes($terminallistid[$i]));
					
					}
					else
					{
					 $terminalid=$terminalid.$socket->toStr($socket->integerToBytes($terminallistid[$i]));
					
					}
				}
				else
				{
				 $terminalid=$terminalid.$socket->toStr($socket->integerToBytes(0));
				}
			}
			$msg = $medianame.$terminalcount.$terminalid;	
			
			$socket->send_datatosdk($_SESSION['serverip'],$msg,5,1284,0);

			echo "<script>alert('上传完成，确定后更新！');</script>";

			$tiaozhuan="<script>window.location='".$hrefaddr."'</script>";
			echo $tiaozhuan;
		}
		else
		{
		 	$hrefaddr="view_shengji.php?terminal_id=".$terminal_id;
			$_SESSION['info'] ='already exists';//提示信息
			$_SESSION['url'] = $hrefaddr;
			echo "<script>window.location='success.php'</script>";
		}
      	
	  }
	  else
	  {
	  	
	 	 move_uploaded_file($_FILES["file"]["tmp_name"], "link/backup/upgradefile/" . $_FILES["file"]["name"]);
		 if($terminal_id=="")
		{
		    $hrefaddr="terminalupgrade.php?terminalnum=".$terminalnum."&terminalids=".$terminalids;
			$socket	=	new	send_message_to_server($sdkport_conf);	
			$getmedianame="link/backup/upgradefile/". $_FILES["file"]["name"];
			
			$medianame=$socket->toStr($socket->getallBytes($getmedianame,256));
			$terminalcount=$socket->toStr($socket->integerToBytes($terminalnum));
			$terminallistid = explode(",",$terminalids);
			for($i=0;$i<256;$i++)
			{
				if($i<$terminalnum)
				{
					if($i==0)
					{
						$terminalid=$socket->toStr($socket->integerToBytes($terminallistid[$i]));
					
					}
					else
					{
					 $terminalid=$terminalid.$socket->toStr($socket->integerToBytes($terminallistid[$i]));
					
					}
				}
				else
				{
				 $terminalid=$terminalid.$socket->toStr($socket->integerToBytes(0));
				}
			}
			$msg = $medianame.$terminalcount.$terminalid;	
			$socket->send_datatosdk($_SESSION['serverip'],$msg,5,1284,0);
			
			echo "<script>alert('上传完成，确定后更新！');</script>";

			$tiaozhuan="<script>window.location='".$hrefaddr."'</script>";
			echo $tiaozhuan;
		}
		else
		{
			$hrefaddr="view_shengji.php?terminal_id=".$terminal_id;
			$_SESSION['info'] = strtoupper($do_php_prompt['Successed']);//提示信息
			$_SESSION['url'] = $hrefaddr;
			echo "<script>window.location='success.php'</script>";
		}
		
      	
	  }
	}
 		
?>
