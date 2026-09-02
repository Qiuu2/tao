<?php
	header("content-type:text/html;charset=utf-8");
	//require_once('inc/config.inc.php');
	$media_path = "";
	if(isset($_GET['file_name']))
	{
		$media_path = "link".trim($_GET['file_name']);
	}
	$file_name = "";
	if(isset($_GET['get_name']))
	{
		$file_name = trim($_GET['get_name']);
	}
	
	$get_typeid = "";
	if(isset($_GET['get_typeid']))
	{
		$get_typeid = trim($_GET['get_typeid']);
	}
	
	$file_name=iconv("utf-8","gb2312",$file_name);
	//if($get_typeid=="mp3")
	$file_name=$file_name.".".$get_typeid;

	if(!file_exists($media_path))
	{
		echo "<script>alert('FILE NOT EXISTS');</script>";
		echo "<script>window.history.back();</script>";
		exit;
	}
	$media_to_server = "link/backup/mediadata";
	$media_name = basename($media_path); 

	if(is_dir($media_to_server))
	{
			if( $handle = opendir($media_to_server))
			{
				while( ($file = readdir($handle)) != false)
				{
					if($file != "." && $file != "..")
					{
						if(is_file($media_to_server."/".$file))
						{
							if($media_name == $file)
							{
								if ((int)$_SERVER['SERVER_PORT'] === 80) {
									$host = $_SERVER['SERVER_NAME'];
							} else {
									$host = $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
							}
								//$host = isset($_SERVER['HTTP_X_FORWARDED_HOST'])?$_SERVER['HTTP_X_FORWARDED_HOST']:(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'');
								// system("ln /backup/backup /var/www/html/ok112/backup_restore/");
								header("Content-type: application/octet-stream");
								header("Pragma: no-cache");	
								header("Expires: 0");
								header("Accept-Ranges: bytes");
								header("Cache-Component: must-revalidate, post-check=0, pre-check=0");
								header ("Content-Length:".filesize($media_path));
								header("Content-Disposition: attachment; filename=\"".$file_name."\""); 
							//	header("Content-Transfer-Encoding: binary");
								readfile("link/backup/mediadata/".$media_name);	
							}
						}
					}
				}
			}
			else
			{
				//echo "2";
			}
	
	}
	else
	{
		//echo "2";
	}
	
	$apk_to_server = "link/backup/upgradefile";
	
	$apk_name = basename($media_path); 
	//system("ln -s /backup/upgradefile/ /var/www/html/ok112/upgrade");
	if(is_dir($apk_to_server))
	{
			if( $handle = opendir($apk_to_server))
			{
				while( ($file = readdir($handle)) != false)
				{
					if($file != "." && $file != "..")
					{
						if(is_file($apk_to_server."/".$file))
						{
							if($apk_name == $file)
							{
								if ((int)$_SERVER['SERVER_PORT'] === 80) {
									$host = $_SERVER['SERVER_NAME'];
							} else {
									$host = $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
							}
							//	$host = isset($_SERVER['HTTP_X_FORWARDED_HOST'])?$_SERVER['HTTP_X_FORWARDED_HOST']:(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'');	
								header("Content-type: application/octet-stream");
								header("Pragma: no-cache");	
								header("Expires: 0");
								header("Accept-Ranges: bytes");
								header("Cache-Component: must-revalidate, post-check=0, pre-check=0");
								header ("Content-Length:".filesize($media_path));
								header("Content-Disposition: attachment; filename=\"".$file_name."\""); 
							//	header("Content-Transfer-Encoding: binary");
								readfile(apk_to_server."/".$apk_name);	
							}
						}
					}
				}
			}
			else
			{
				//echo "2";
			}
	
	}
	else
	{
		//echo "2";
	}
	
	
	
	

?>