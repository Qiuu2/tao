<?php
if(is_file('install.php')) {
header("Location: install.php"); 
exit;
}
if (!session_id()) session_start();
require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require('editor.php');
$smarty->assign('descriptionarea',$descriptionarea);
//读取数据

if(!isset($_GET['page']))
{
    $page=1;
    $start=0;
}else {
    $page=$_GET['page'];
    $start=($_GET['page']-1)*$NumOfPage;
}
if(!isset($_SESSION['admin_id']))
{
	$result	=	mysqli_query($con,"SELECT * FROM `".$DB_PREFIX."msg` WHERE sh=1");
	$result = mysqli_query($con,"SELECT * FROM `".$DB_PREFIX."msg` WHERE sh=1 ORDER BY id DESC LIMIT $start,$NumOfPage");
}else{
	$result	=	mysqli_query($con,"SELECT * FROM `".$DB_PREFIX."msg`");
	$result = mysqli_query($con,"SELECT * FROM `".$DB_PREFIX."msg` ORDER BY id DESC LIMIT $start,$NumOfPage");
}
$Num	=	mysqli_num_rows($result);
while ($row = mysqli_fetch_array($result)) {
	$type ="";
	for($i=1;$i<=$row['type'];$i++)
	{
		$type .= "<img src=images/Star_On.gif width=12 height=11 alignabsmiddle>";
	}
	for($i=1;$i<=5-$row['type'];$i++)
	{
		$type .= "<img src=images/Star_Off.gif width=12 height=11 alignabsmiddle>";
	}
	
	$result1	=	mysqli_query($con,"SELECT * FROM `".$DB_PREFIX."reply` WHERE m_id='$row[id]'");
	$r_content = "";
	$r_time = "";
	$r_ok = "";
	if($row1 = mysqli_fetch_array($result1)){
		$r_content = $row1['content'];
		$r_time = $row1['time'];
		$r_ok = 1;
	}
	
	$array[]	=	 array("id"=>$row['id'],"title"=>$row['title'],"content"=>$row['content'],"type"=>$type,"time"=>$row['time'],"ip"=>$row['ip'],"sh"=>$row['sh'],"r_content"=>$r_content,"r_time"=>$r_time,"r_ok"=>$r_ok);
}
$smarty->assign("info",$array);
//留言分页
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
$smarty->display("msgmanager.html");
?>