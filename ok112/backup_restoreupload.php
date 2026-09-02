<?php

header("content-type:text/html;charset=utf-8");

require_once("inc/config.php");
require_once("inc/config.inc.php");

require_once("inc/socket_conf.php");


$getdemo="上传成功！";

if (is_uploaded_file($_FILES["upfile"][tmp_name]))
{ 

 $file =$_FILES['upfile']['name'];

 $destination=$Backup_Path.$file;

 if(@move_uploaded_file ($_FILES["upfile"][tmp_name], $destination)){

echo "<script>alert('".$getdemo."');</script>";
}
}
echo "<script>window.history.back();</script>";

?>