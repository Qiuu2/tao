<?php
	header("content-type:text/html; charset=utf-8");
	require_once('inc/config.inc.php');
	require_once('inc/config.php');
	mysqli_query($con,"set names 'utf8'");
	//date_default_timezone_set('Asia/Shanghai');
	set_time_limit(0);//����ʱ��Ϊ������
	//error_reporting(E_ALL); 
	$backup_name = "";
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
	 
	create_table_and_data_file($backup_full_name,$DB_NAME);
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
	
	//获取数据库中表名并返回数组
	function get_database_tables_name($database_name)
	{
		$table_result = mysql_list_tables($database_name) or die(mysqli_error($con));
		if(!$table_result)
		{
			echo "3";
			return false;
		}
		else
		{
			$table_num = mysqli_num_rows($table_result);
			for($i=0; $i<$table_num; $i++)
			{
				$table_name_array[] = $database_name.".".mysql_tablename($table_result,$i);
			}
			return $table_name_array;
		}
	}
	//获取每个表的创建属性
	function get_per_table_property($table_name)
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
	function get_per_table_record($table_name)
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
			$table_cols = mysql_num_fields($table_result);
			if($table_rows == 0)
			{
				return false;
			}
			else
			{
				while($table_row = mysqli_fetch_array($table_result))
				{
					$insert_str.= " (";
					$field_str= "";
					for($i=0; $i<$table_cols; $i++)
					{
					
						$temp_field_type = mysql_field_type($table_result,$i);

						if( (trim($table_row[$i]) =="") && ($temp_field_type == "int") )
						{
							$field_str.= "0,";
						}
						else if ( is_null($table_row[$i]) && ($temp_field_type == "string") )
						{
							$field_str.= "'',";
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
	/*******************************
		连接创建及数据内容字符串
	********************************/
	function create_table_and_data_file($zip_path_name,$database_name)
	{
		$table_array = get_database_tables_name($database_name);
		foreach($table_array as $table_name)
		{
			$temp_link_str = "";
			$create_table_str = get_per_table_property($table_name);
			
			$temp_link_str = $create_table_str;
			
			$insert_table_str = get_per_table_record($table_name);
			
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
			$field_num = mysql_num_fields($field_result);
			for($i=0; $i<$field_num; $i++)
			{
				$table_field_array[] = mysql_field_type($field_result,$i);
			}
			return $table_field_array;
		}
	}
?>