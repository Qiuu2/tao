<?php
if(is_file('install.php')) {
header("Location: install.php"); 
exit;
}
if (!session_id()) session_start();
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');

if(empty($_SESSION['admin_id']))
{
	require_once('login.php');	
}
else
{
	//读取数据
	
	if(!isset($_GET['page']))
	{
	    $page=1;
	    $start=0;
	}else {
	    $page=$_GET['page'];
	    $start=($_GET['page']-1)*$NumOfPage;
	}
	
	{
	
		$result2	=	mysqli_query($con,"SELECT streamid,name FROM `serverplaystream` ");
		while ($row2 = mysqli_fetch_array($result2)) {
			
			$array2[]	=	 array("streamid"=>$row2['streamid'],"name"=>$row2['name']);
		}
		
		$smarty->assign("stream",$array2);
	}
	
	{	
		$result3	=	mysqli_query($con,"SELECT id,name FROM `terminaltype` ");
		while ($row3 = mysqli_fetch_array($result3)) {
			
			$array3[]	=	 array("id"=>$row3['id'],"name"=>$row3['name']);
		}
		
		$smarty->assign("terminaltype",$array3);
	}	
	
	//状态分页
	if($Num != 0){
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
	$smarty->display("TerminalManager/terminaladd.html");
}
?>