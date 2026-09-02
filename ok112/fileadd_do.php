<?
  require_once("inc/config.inc.php"); 
  
	#if 1
  //$FILE_PATH = "/usr/data/";
	$result = mysqli_query($con,"SELECT * FROM `media` WHERE name='$_POST[filename]' ");
	if(!$row = mysqli_fetch_array($result))
	{         
              
    if (file_exists($FILE_PATH.$newfile_name)) 
		{
    		$_SESSION['info'] = "文件名已存在！".mysqli_error($con);
		$_SESSION['url'] = "./filemanager.php";
		echo "<script>window.location='error.php'</script>";
 		}	
   	else
		{		
	
  	  copy($newfile, $FILE_PATH.$newfile_name);	
	 		mysqli_query($con,"INSERT INTO `media` (`name`,`filename`,`size`) VALUES ('$_POST[filename]','$newfile_name','$newfile_size')");	        
			if(mysqli_error($con))
			{
				$_SESSION['info'] = "添加失败！".mysqli_error($con);
				$_SESSION['url'] = "./filemanager.php";
				echo "<script>window.location='error.php'</script>";
			}else{
				$_SESSION['info'] = "添加节目成功！".$FILE_PATH.$newfile_name;
				$_SESSION['url'] = "./filemanager.php";
				echo "<script>window.location='success.php'</script>";	
			}
		}
	}
	else
	{
		$_SESSION['info'] = "文件名已存在！".mysqli_error($con);
		$_SESSION['url'] = "./filemanager.php";
		echo "<script>window.location='error.php'</script>";
	}
	#endif
?>
