<?php
if (!session_id()) session_start();
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
//验证是否失效
require_once("verify_user_sessionin_valid.php");
verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	require_once('login.php');	
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("log_manager",$log_manager);

	$smarty->assign("Logmanager",$Logmanager);
	$smarty->assign("Searchform",$Searchform);
	//超级用户可以查看
	require_once("User_Rights_Manage/verify_user_rights_class.php");
	if(!is_admin($con,$_SESSION['username']))
	{
		echo "<div style=\"position:absolute;left:expression((body.clientWidth-300)/2);top:expression((body.clientHeight-200)/2);width:300px;height:50;text-align:center;color:#3366ff;font-size:12px;font-family: Arial, Helvetica, sans-serif, \"宋体\";\">
			<p style=\"font-size:110%;\">权限不够 请与管理员申请</p>
			<a href=\"#\" onclick=\"window.history.back();\" onmouseover=\"this.style.color='#ff9933'\" onmouseout=\"this.style.color=''\">返回</a>
		</div>";
		exit;
	}
	//导入分页
	require('editor.php');
	$smarty->assign('descriptionarea',$descriptionarea);
	
	//查询
	$sql="";
	
	if($_GET['searchkey']!="")
	{
		if($_GET['searchvalue']!="" && $_GET['searchsequence']!="")
		{	
			$searchkey=$_GET['searchkey'];
			$searchvalue=$_GET['searchvalue'];
			$searchsequence=$_GET['searchsequence'];
			if($searchkey=="user")
			{
				$sql="SELECT * FROM audioserver.log WHERE log.user LIKE '%".$searchvalue."%' ORDER BY ".$searchsequence." DESC";
			}
			else if($searchkey=="time")
			{
				$sql="SELECT * FROM audioserver.log WHERE log.time LIKE '%".$searchvalue."%' ORDER BY ".$searchsequence." DESC";
			}
			else if($searchkey=="ip")
			{
				$sql="SELECT * FROM audioserver.log WHERE log.ip LIKE '%".$searchvalue."%' ORDER BY ".$searchsequence." DESC";
			}
		}
		else if($_GET['searchvalue']!="")
		{
			$sql="SELECT * FROM audioserver.log where log.".$_GET['searchkey']." like '%".$_GET['searchvalue']."%' ORDER BY log.id DESC";
		}
	}
	else
	{
		$sql="SELECT * FROM audioserver.log ORDER BY log.id DESC";
	}
	//读取数据
	if(!isset($_GET['page']))
	{
	    $page=1;
	    $start=0;
	}
	else 
	{
	    $page=$_GET['page'];
	    $start=($_GET['page']-1)*$NumOfPage;
	}
	
	if(!isset($_SESSION['admin_id']))
	{
		$result	=	mysqli_query($con,$sql);
		$Num	=	mysqli_num_rows($result);
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	}
	else
	{
		$result	=	mysqli_query($con,$sql);
		$Num	=	mysqli_num_rows($result);
		$result = mysqli_query($con,$sql." LIMIT $start,$NumOfPage");
	}
	
	while ($row = mysqli_fetch_array($result)) {		
		$array[]	=	 array("id"=>$row['id'],"user"=>$row['user'],"operate"=>$row['operate'],"ip"=>$row['ip'],"time"=>$row['time'],);
	}

	$smarty->assign("info",$array);
	//分页
	if($Num != 0)
	{
		require_once("pagination.class.php");
		$p = new pagination;
		$p->Items($Num);
		$p->limit($NumOfPage);
		$p->target("?id=".$_GET['id']."&");
		$p->currentPage($_GET['page']);
		$p->adjacents(3);
		$smarty->assign("pagestr",$p->show());
	}
	//输出session
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->display("LogManager/logManager.html");
}
?>
