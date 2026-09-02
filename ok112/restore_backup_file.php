<?php
header("content-type:text/html;charset=utf-8");
require_once("inc/config.php");
require_once("inc/config.inc.php");

require_once("inc/socket_conf.php");

//error_reporting(E_ALL);
set_time_limit(0);
$file_name = "";
if(isset($_GET['file_name']))
{
	$file_name = trim($_GET['file_name']);
}



//查找文件夹下压缩包并读取
function search_foloder_file($file_name,$folder_path)
{

	if(is_dir($folder_path))
	{
	
		if( ($handle = opendir($folder_path)) != false )
		{

			while( ($file = readdir($handle)) !== false)
			{
				if( $file != "." && $file != ".." )
				{
			
						if(basename($file,".tar") == $file_name)
						{	
	
							//读取压缩包中数据
							if(!get_zip_file_content($folder_path.$file))
							{
				
			
								return false;
							}
						}
					}
				
			}
		}
	}

	return true;
}
function search_foloder_filemedia($file_name,$folder_path)
{
	if(is_dir($folder_path))
	{
		if( ($handle = opendir($folder_path)) != false )
		{
			while( ($file = readdir($handle)) !== false)
			{
				if( $file != "." && $file != ".." )
				{
					//if(preg_match("\.tar$",$file))
					//{
						if(basename($file,".tar") == $file_name)
						{
							//读取压缩包中数据
							if(!get_zip_file_contentmedia($folder_path.$file))
							{
								return false;
							}
						}
					//}
				}
				
			}
		}
		
	}
	return true;
}
function get_zip_file_contentmedia($zip_file_path)
{	
	global $con;
	if(is_file($zip_file_path))
	{
	
		if(is_readable($zip_file_path))
		{
			$zip_handle = zip_open($zip_file_path);	
			if( is_resource($zip_handle) )
			{
				while( ($file_handle = zip_read($zip_handle)) !== false )
				{	
					if(zip_entry_name($file_handle))
					{			
						if(zip_entry_open($zip_handle,$file_handle,"r"))
						{
							
								$zip_file_conten = zip_entry_read($file_handle,zip_entry_filesize($file_handle));
								//过滤字符串
								mysqli_query($con,"START TRANSACTION");
								if(!analyze_sql(trim($zip_file_conten)))
								{
									mysqli_query($con,"ROLLBACK");
									return false;
								}
							zip_entry_close($file_handle);
						}
					}
				
				}
				mysqli_query($con,"COMMIT");
			}
			zip_close($zip_handle);
		}
	}
	
	return true;
}
function get_zip_file_content($zip_file_path)
{	
	global $con;
//	$fh=fopen("tianyaluzhang.txt","a"); 	
	
	if(is_file($zip_file_path))
	{
		if(is_readable($zip_file_path))
		{
			//fwrite($fh,"11\r\n");
			$zip_handle = zip_open($zip_file_path);

			if( is_resource($zip_handle) )
			{
				//fwrite($fh,"22\r\n");
				while( ($file_handle = zip_read($zip_handle)) !== false )
				{	
					
					if("mp3"!=extend_1(zip_entry_name($file_handle)))
					{	
						//fwrite($fh,zip_entry_name($file_handle));
						//fwrite($fh,"\r\n");
						if(zip_entry_open($zip_handle,$file_handle,"r"))
						{
								$zip_file_conten = zip_entry_read($file_handle,zip_entry_filesize($file_handle));
								//过滤字符串
								mysqli_query($con,"START TRANSACTION");

								if(!analyze_sql(trim($zip_file_conten)))
								{
									mysqli_query($con,"ROLLBACK");
								//	fwrite($fh,"9999\r\n");
									return false;
								}
							//	fwrite($fh,"1111\r\n");
							zip_entry_close($file_handle);
							
						}
					}
					else
						{
							if(zip_entry_open($zip_handle,$file_handle,"r"))
							{
								$buf = zip_entry_read($file_handle,zip_entry_filesize($file_handle));
								//fwrite($fh,zip_entry_name($file_handle));
								//fwrite($fh,"\r\n");
								$fout   =   fopen(zip_entry_name($file_handle), "w "); 
								fwrite($fout,$buf); 
								fclose($fout);
								//fwrite($fh,zip_entry_name($file_handle));
								zip_entry_close($file_handle);	
							}
						}
						
				}
				mysqli_query($con,"COMMIT");
			}
		//	fwrite($fh,"33\r\n");
			zip_close($zip_handle);
		}
	}
//	fwrite($fh,"44\r\n");
//	fclose($fh);
	return true;
}
function extend_1($file_name)    
	{    
	$retval="";    
	$pt=strrpos($file_name, ".");    
	if ($pt) $retval=substr($file_name, $pt+1, strlen($file_name) - $pt);    
	return ($retval);    
	} 
//分解sql语句

function analyze_sql($sql_str)
{	
	global $con;
//$fh=fopen("tianyaluzhang.txt","a"); 

	$sub_sqls = explode(";",$sql_str);
	foreach($sub_sqls as $sql)
	{
		if(!empty($sql))
		{
			$temp = "";
			$temp = trim($sql);
			$aaa=mysqli_query($con,$temp);
			if(mysqli_error($con))
			{
				return false;
			}
		}
	}

	return true;	
}


if(empty($file_name))
{
	echo "0";
	exit;
}
else
{
	
	//search_foloder_filemedia($file_name,$FILE_PATH);

	if(search_foloder_file($file_name,$Backup_Path))
	{
	
		$socket	=	new	send_message_to_server($port_conf);	
		$msg = "server?state=1";
		$socket->send_data($_SESSION['serverip'],$msg);
		echo "1";
	}
	else
	{
		echo "0";
	}
}

?>