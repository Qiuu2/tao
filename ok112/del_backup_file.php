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
if(is_dir($Backup_Path))
{
	if( $handle = opendir($Backup_Path) )
	{
		while( ($file = readdir($handle)) != false)
		{
			if($file != "." && $file != "..")
			{
				if(is_file($Backup_Path."/".$file))
				{
					if( get_suffix($file) == 'tar' )
					{
						if($file_name == trim(basename($file,".tar")))
						{
							if(unlink($Backup_Path."/".$file))
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
function get_suffix($file_name)
{
	$patn_temp = pathinfo($file_name);
	return $patn_temp['extension'];
}
?>