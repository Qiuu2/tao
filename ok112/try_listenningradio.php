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
		
		//�жϻỰ�Ƿ�ʧЧ
		require_once("verify_user_sessionin_valid.php");
		
		verifysessionvalid();
//		//��������
//		require_once("language/".$_SESSION['language'].".php");
//		$smarty->assign("language",$_SESSION['language']);

		$get_task_id = "";
		if(isset($_GET['id']))
		{
			$get_task_id = trim($_GET['id']);
			echo $get_task_id;
            
		}
		//Ҫ����·��
		$sql_task = "SELECT task.taskname, task.state, task.cmdargs FROM task WHERE task.taskid = '$get_task_id'";
		
		$result_task = mysqli_query($con,$sql_task) or die(mysqli_error($con));
		
	    $row_task = mysqli_fetch_array($result_task);
		
		$task_name = $row_task['taskname'];
		
		$cmdargs = $row_task['cmdargs'];
		$smarty->assign("task_name",$task_name);
		$smarty->assign("cmdargs",$cmdargs);
		
		
		$smarty->assign("admin_id",$_SESSION['admin_id']);
		
		$smarty->display("WebRadio/try_listenningradio.html");
	}
?>
