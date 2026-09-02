<?php
	header("content-type:text/html; charset=utf-8");
	require_once('inc/config.inc.php');
	require_once('inc/config.php');
	//mysqli_query($con,"set names 'utf8'");
	//date_default_timezone_set('Asia/Shanghai');
	//set_time_limit(0);//设置时间为不限制
	//error_reporting(E_ALL); 
	$backup_name = "";
//	$fp = fopen("get_backup.log","x+");
	if(isset($_GET['backup_name']))
	{
		$backup_name = trim($_GET['backup_name']);
	}

	if(empty($backup_name))
	{
		$backup_name = "".date('Y-m-d')."-".(string)time()."";
	}
	
	$temp_folder = get_folder_name($Backup_Path);
	if(!empty($temp_folder))
	{
		foreach($temp_folder as $value)
		{
			$value = basename($value,".tar"); 
			if(trim($value) == $backup_name)
			{
				echo "2";
				exit;
			}
		}
	}
	
	//创建备份文件夹
	
	$backup_full_name = $Backup_Path."/".$backup_name.".tar";

//	$tempinfo ="rm -rf /var/www/html/ok112/link/backup/backup/mediabackup.tar";
//	$command = "cmdhost --cmd=\"".$tempinfo."\"";
//	exec($command, $output_info,$last_line);
	//@exec($command);
//	$tempinfo = "tar -cvf /var/www/html/ok112/link/backup/mediabackup.tar /var/www/html/ok112/link/backup/mediadata";
//	$command = "cmdhost --cmd=\"".$tempinfo."\"";
//	exec($command, $output_info,$last_line);

create_table_and_data_file($backup_full_name,$DB_NAME,$con);
	
$directory = 'link/backup/mediadata/'; // 替换为你的文件夹路径

// 获取所有 .mp3 文件（非递归，仅当前目录）
$mp3Files = glob($directory . '*.mp3');

if (!empty($mp3Files)) {
    foreach ($mp3Files as $file) {
       // echo basename($file) . "\n"; // 输出文件名（不含路径）
		add_file_to_zip($backup_full_name,$file);
    }
} else {
    echo "未找到 MP3 文件";
}

//add_file_to_zip($backup_full_name,"link/backup/mediadata/*.mp3");
	
//	direct_add_to_zip($backup_full_name,$DB_NAME);
//	if(mkdir($backup_full_name,0777))
//	{
//		echo "创建成功";
//	}
//	else
//	{
//		echo "创建失败";
//	}
	
	//直接备份到.zip文件中
	//direct_add_to_zip($zip_path_name,$entry_file_name,$entry_file_content,$database_name);
	
	//写入数据文件
	//write_create_table_file($backup_full_name,$DB_NAME);

	//遍历文件夹下的文件夹得名称
	function get_folder_name($backup_root_path)
	{
		$folder_array = array();
		if(is_dir($backup_root_path))
		{
			$folder_array = scandir($backup_root_path);
			$folder_array = array_slice($folder_array,2);
		}
		else
		{
			mkdir($backup_root_path,0777);
			$folder_array = scandir($backup_root_path);
			$folder_array = array_slice($folder_array,2);
			//return false;
		}
		return $folder_array;
	}
	
	function list_tables($con,$database)  
	{  
	
		$rs = mysqli_query($con,"SHOW TABLES FROM $database");  
		$tables = array();  
		while ($row = mysqli_fetch_row($rs)) {   
		$tables[] = $row[0];  
		}  
		mysqli_free_result($rs);  
		return $tables;  
	} 
	
	
	//获取数据库中表名并返回数组
	function get_database_tables_name($database_name,$con)
	{
		
		$rs = mysqli_query($con,"SHOW TABLES FROM $database_name");  
	 
		if(!$rs)
		{
			echo "3";
			return false;
		}
		else
		{
		while ($row = mysqli_fetch_row($rs)) {   
			$table_result[] = $database_name.".".$row[0]; 
		
		} 
			return $table_result;
		}
		
	mysqli_free_result($rs);  
		return $table_name_array;
	}
	//获取每个表的创建属性
	function get_per_table_property($table_name,$con)
	{
		$table_sql = "show create table ".$table_name."";
		$table_result = mysqli_query($con,$table_sql);
		if(!$table_result)
		{
			return false;
		}
		else
		{
			if(!$table_row = mysqli_fetch_array($table_result))
			{
				return false;
			}
			else
			{
				$table_row[1] = preg_replace("/\`/i","audioserver.",$table_row[1],1);
				$table_row[1] = preg_replace("/\`/i","",$table_row[1],1);
				return "DROP TABLE IF EXISTS ".$table_name.";\r\n".$table_row[1].";\r\n";
			}
		}
	}
	
	//获取每个表中插入记录
	function get_per_table_record($table_name,$con)
	{
		$table_sql = "select * from ".$table_name."";
		$table_result = mysqli_query($con,$table_sql);
		$insert_str = "INSERT INTO ".$table_name." VALUES ";
		if(!$table_result)
		{
			return false;
		}
		else
		{
			//获取行数
			$table_rows = mysqli_num_rows($table_result);
			//获取列数
			$table_cols = mysqli_num_fields($table_result);
			if($table_rows == 0)
			{
				return false;
			}
			else
			{
			//	$fh=fopen("tianyaluzhang.txt","a"); 
			//	fwrite($fh,"$table_name\r\n");
				while($table_row = mysqli_fetch_array($table_result))
				{
					$insert_str.= " (";
					$field_str= "";
					for($i=0; $i<$table_cols; $i++)
					{
						$temp_field_type = mysqli_fetch_field_direct($table_result,$i);
					
						if( (trim($table_row[$i]) =="") && ($temp_field_type->type == "int") )
						{
							$field_str.= "0,";
						}
						else if( (trim($table_row[$i]) =="") && ($temp_field_type->type == 3) )
						{
							$field_str.= "0,";
						}
						else if ( is_null($table_row[$i]) && ($temp_field_type->type == "string") )
						{
							$field_str.= "'',";
						}
						else if ( is_null($table_row[$i]) && ($temp_field_type->type == "int") )
						{
							$field_str.= "0,";	
						}
						else if ( is_null($table_row[$i]) && ($temp_field_type->type == "unsigned int") )
						{
							$field_str.= "0,";	
						}
						else if ( is_null($table_row[$i]) && ($temp_field_type->type == "float") )
						{
							$field_str.= "0,";	
						}
						else if ( is_null($table_row[$i]) && ($temp_field_type->type == 253) )
						{
							$field_str.= "0,";	
						}
						else
						{
						
							$field_str.= "'".$table_row[$i]."',";
						}
					}
					$field_str = substr($field_str,0,-1);
					$insert_str.= $field_str."),";
				}
				$insert_str = substr($insert_str,0,-1);
				return $insert_str.";\r\n";
			}
		}
		
	}
	
	/***********************************
		写入创建表的文件（一个文件一个表）
	***********************************/
	
	function write_create_table_file($create_table_path,$database_name)
	{
		$tables_array = get_database_tables_name($database_name);
		foreach($tables_array as $table_name)
		{
			$temp_table_path = $create_table_path."/create_".$table_name.".sql";
			$create_table_content = get_per_table_property($table_name);
			write_data_to_file($temp_table_path,$create_table_content);
		}
	}
	
	//写入数据到文件中
	function write_data_to_file($create_table_path,$data_content)
	{		
		if(!$handle = fopen($create_table_path,"w"))
		{
			echo "faild";
			return false;
		}
		else
		{
			flock($handle, 3);
			fwrite($handle,$data_content);
			fclose($handle);
			return true;
		}
	}
	/**********************************
		直接写入文件在压缩包中
	**********************************/
	function add_file_to_zip($zip_path_name,$entry_file_name)
	{
		$zip = new ZipArchive();
		$temp_vlaue = $zip->open($zip_path_name,ZipArchive::CREATE);
		
		if($temp_vlaue === TRUE)
		{
			$zip->addFile($entry_file_name);
			$zip->close();
			return true;
		}
		else
		{
			return false;
		}
	}


	function write_file_to_zip($zip_path_name,$entry_file_name,$entry_file_content)
	{
		$zip = new ZipArchive();
		$temp_vlaue = $zip->open($zip_path_name,ZipArchive::CREATE);
		
		if($temp_vlaue === TRUE)
		{
			$zip->addFromString($entry_file_name,$entry_file_content);
			$zip->close();
			return true;
		}
		else
		{
			return false;
		}
	}
	/*
	//直接读取数据库中表名及表的内容
	function direct_add_to_zip($zip_path_name,$database_name)
	{
		$table_name_array = get_database_tables_name($database_name);
		foreach($table_name_array as $table_name)
		{
			$per_table_content = get_per_table_property($table_name);
			write_file_to_zip($zip_path_name,$table_name,$per_table_content);
		}
	}
	*/
	/*******************************
		连接创建及数据内容字符串
	********************************/
	function create_table_and_data_file($zip_path_name,$database_name,$con)
	{
		
		$table_array = get_database_tables_name($database_name,$con);
		foreach($table_array as $table_name)
		{
		
			//fwrite($fp,"2_".$table_name."\r\n");
			$temp_link_str = "";
			$create_table_str = get_per_table_property($table_name,$con);
			
			$temp_link_str = $create_table_str;
			
			$insert_table_str = get_per_table_record($table_name,$con);
			
			$temp_link_str.= $insert_table_str;
			//压缩到.zip包下
			write_file_to_zip($zip_path_name,$table_name.".sql",$temp_link_str);
		}
		echo "1";
	}
	/*****************************
		获取每个表字段类型
	*****************************/
	function get_per_table_field_type($table_name)
	{
		$table_field_array = array();
		$field_result = mysqli_query($con,"select * from ".$table_name."") or die(mysqli_error($con));
		if($field_result != false)
		{
			$field_num = mysqli_num_fields($field_result);
			for($i=0; $i<$field_num; $i++)
			{
				$field_type=mysqli_fetch_field_direct($field_result,$i);
				$table_field_array[] = $field_type->type;
			}
			return $table_field_array;
		}
	}
?>