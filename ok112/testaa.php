<?php 
$file_path = 'link/backup/backup/000.tar';
$file_name = '000.tar';

if (!file_exists($file_path)) {
  echo '文件不存在';
}
else
{
  header('Content-Type: application/tar');
  header('Content-Disposition: attachment; filename='.$file_name); 
  readfile($file_path);
}

?>
