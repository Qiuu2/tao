<?php
header("content-type:text/html;charset=utf-8");
require_once("inc/config.php");
require_once("inc/config.inc.php");

require_once("inc/socket_conf.php");
//error_reporting(E_ALL);
//$fp = fopen("get_backup.log","a+");

 //system("ln -s /backup/backup /var/www/html/ok112/backup_restore");
if (is_uploaded_file($_FILES["upfile"]['tmp_name']))
{ 

 $portrait_type_array = array('tar','TAR');
 
 $file = $_FILES["upfile"];   

 $type = pathinfo($file['name']);
 
 if(!in_array($type['extension'], $portrait_type_array)) 
 {
  echo "<script>alert('bell error');</script>";
 }
 else
 {
	/*
	$commandid = "sudo mount /dev/sdc1 /mnt/usb";
	@exec($commandid);

	$command = "sudo tar -xvf /mnt/usb/mediabackup.tar -C /backup/";
	@exec($command);
*/
 $destination_folder = "link/backup/backup/";
 if(!file_exists($destination_folder)) 
 {  

    mkdir($destination_folder,0777);
    chmod($destination_folder,0777); 
 
 }else
 {

  $filename=$file["tmp_name"];
  //fwrite($fp,"file2 name is\n".$filename); 
	$yuanfile=$file['name'];
  $pinfo=pathinfo($file["name"]);
  $ftype=$pinfo['extension'];
  // $destination = $destination_folder.time().".".$ftype;
    $destination = $destination_folder.$yuanfile;
  /*if (file_exists($destination)){
   echo "<script>alert('same name');</script>";
 
   }
    */
   if(!$notice){
    if(move_uploaded_file ($filename, $destination)==true){
   //  exec("unzip -o ".$destination." -d ".$destination_folder);  
     echo "<script>alert('成功');</script>";   
	/*
     $file_name = basename($destination);
	 
	 $file_name = basename($file_name,".tar");
	
	 if(search_foloder_file($file_name,$Backup_Path))
	{
		$socket	=	new	send_message_to_server($port_conf);	
		$msg = "server?state=1";
		$socket->send_data($_SESSION['serverip'],$msg);
		 $file_name = basename($destination);
		
	     unlink($Backup_Path."/".$file_name);
		 

	}
	else
	{
	echo "<script>alert('failed');</script>";	
	}
     */
    }else{
     echo "<script>alert('失败');</script>";
     
    }
         
   }
 }
 }
}
 echo "<script>window.history.back();</script>"; 
//echo $notice."<br />";
//�����ļ�����ѹ��������ȡ

function search_foloder_file($file_name,$Backup_Path)
{

    
	if(is_dir($Backup_Path))
	{
	     
		if( ($handle = opendir($Backup_Path)) != false )
		{
			
			while( ($file = readdir($handle)) !== false)
			{
				 
				if( $file != "." && $file != ".." )
				{
					
					if(eregi("\.tar$",$file))
					{
						 
						if(basename($file,".tar") == basename($file_name,".tar"))
						{
							//��ȡѹ����������
							 
							if(!get_zip_file_content($Backup_Path."/".$file))
							{
								
								return false;
							}
						}
					}
				}
			}
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

function get_zip_file_contents($zip_file_path)
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
					if(zip_entry_open($zip_handle,$file_handle,"r"))
					{
						$zip_file_conten = zip_entry_read($file_handle,zip_entry_filesize($file_handle));
						//�����ַ���
						mysqli_query($con,"START TRANSACTION");
						if(!analyze_sql(trim($zip_file_conten)))
						{
							mysqli_query($con,"ROLLBACK");

							return false;
						}
						zip_entry_close($file_handle);
					}
				}
				mysqli_query($con,"COMMIT");
			}
			zip_close($zip_handle);
		}
	}
	return true;
}

//�ֽ�sql���
function analyze_sql($sql_str)
{	
global $con;
	$sub_sqls = explode(";",$sql_str);
	foreach($sub_sqls as $sql)
	{
		if(!empty($sql))
		{
			$temp = "";
			$temp = trim($sql);
			mysqli_query($con,$temp);
			if(mysqli_error($con) != '')
			{
				return false;
			}
		}
	}
	return true;	
}

?>