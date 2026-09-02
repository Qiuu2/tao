<?php
header("content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');
//require_once('inc/config.inc.php');
require_once('inc/config.php');
//date_default_timezone_set('Asia/Shanghai');
set_time_limit(0);//

$backup_name = "";
if(isset($_GET['backup_name']))
{
	$backup_name = trim($_GET['backup_name']);
}

//

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
					
						if($backup_name == trim(basename($file,".tar")))
						{
							if ((int)$_SERVER['SERVER_PORT'] === 80) {
								$host = $_SERVER['SERVER_NAME'];
						} else {
								$host = $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
						}
						
				    		//	$host = isset($_SERVER['HTTP_X_FORWARDED_HOST'])?$_SERVER['HTTP_X_FORWARDED_HOST']:(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'');
			     			// system("ln /backup/backup /var/www/html/ok112/backup_restore/");
						    $backup_full_name = $Backup_Path.$backup_name.".tar"; 
							$file = basename($backup_full_name); 
							header("Content-Type:application/tar");
							header("Pragma: no-cache"); 
    						header("Expires: 0");
                			header ( "Content-Length:".filesize($backup_full_name));						 
							  header('Content-Disposition: attachment; filename='.$file);  
							  readfile($backup_full_name);
							 	//unlink($Backup_Path."/".$file);
						}
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

function get_suffix($file_name)
{
	$patn_temp = pathinfo($file_name);
	return $patn_temp['extension'];
}

?>
