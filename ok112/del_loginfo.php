<?php 
header("content-type:text/html;charset=utf-8");
require_once("inc/config.inc.php");
require_once("inc/config.php");
$file_name = "";
if(isset($_GET['file_name']))
{
	$file_name = trim($_GET['file_name']);
}
//查找该文件
//$fh=fopen("get_backup.log","a"); 
if(is_dir($Task_Log))
{
	if( $handle = opendir($Task_Log) )
	{
		while( ($file = readdir($handle)) != false)
		{
	
			if($file != "." && $file != "..")
			{
				if(is_file($Task_Log."/".$file))
				{
				
					if( get_suffix($file) == 'html' )
					{
					
						if($file_name == trim(basename($file,".html")))
						{
							
							if(unlink($Task_Log."/".$file))
							{
								echo "1";
							}
							else
							{
								echo "0";
							}
							break;
						}
					}
				}
			}
		}
	}
	else
	{
		echo "2";
	}
}
else
{
	echo "2";
}
//fclose($fh);
function get_suffix($file_name)
{
	$patn_temp = pathinfo($file_name);
	return $patn_temp['extension'];
}
?>