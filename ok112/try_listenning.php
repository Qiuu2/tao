<?php
	if (!session_id()) session_start();
	
	header("content-type:text/html;charset=utf-8");
	
	if(empty($_SESSION['admin_id']))
	{
		header("location:login.php");
	}
	else
	{
		require_once('inc/smarty.inc.php');
		
		require_once('inc/config.inc.php');
		require_once('inc/config.php');
		
		//判断会话是否失效
		require_once("verify_user_sessionin_valid.php");
		
		verifysessionvalid();
//		//设置语言
//		require_once("language/".$_SESSION['language'].".php");
//		$smarty->assign("language",$_SESSION['language']);

		$get_media_id = "";
		if(isset($_GET['id']))
		{
			$get_media_id = trim($_GET['id']);
			if(is_numeric($get_media_id))
			{

			}
			else
			{
				echo "<script>alert('格式错误');</script>";
				return;
			}
		//	echo $get_media_id;
		}
		//要构造路径
		$sql_media = "SELECT media.name, media.filename FROM media WHERE media.id = '$get_media_id'";
		
		$result_media = mysqli_query($con,$sql_media) or die(mysqli_error($con));
		
		$row_media = mysqli_fetch_array($result_media);
		
		$media_name = $row_media['name'];
		
		$media_path = "link/".$row_media['filename'];
		
		@mysqli_free_result($result_media);
		
		unset($row_media,$sql_media);

		//取服务器地址（不加密）
		if ((int)$_SERVER['SERVER_PORT'] === 80) {
			$host = $_SERVER['SERVER_NAME'];
	} else {
			$host = $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
	}
		//$host = isset($_SERVER['HTTP_X_FORWARDED_HOST'])?$_SERVER['HTTP_X_FORWARDED_HOST']:(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'');
		//判断文件是否存在
		
		//system("ln -s /usr/date /var/www/html/ok112/upload/");
	//	system($SRY_LISTENNING);
	
		if(!file_exists($media_path))
		{
			$media_name = "媒体文件不存在(ERROR)";
			$smarty->assign("media_name",$media_name);
			$media_to_server_path = "";
			$smarty->assign("media_to_server_path",$media_to_server_path);
		}
		else
		{
			$smarty->assign("media_name",$media_name);
			$media_path = basename($media_path); 
			$media_server_path = "link/backup/mediadata/".$media_path."";
			if(file_exists($media_server_path))
			{
				$media_to_server_paths = "/link/backup/mediadata/".$media_path."";
				$smarty->assign("media_to_server_path",$media_to_server_paths);
			}
		else
			{
				$media_to_server_paths = "/link/backup/mediadata1/".$media_path."";
				$smarty->assign("media_to_server_path",$media_to_server_paths);
			}
		}
		$smarty->assign("admin_id",$_SESSION['admin_id']);	
		$smarty->display("FileManager/try_listenning.html");
	}
?>
