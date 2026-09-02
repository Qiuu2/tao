<?php
	header("content-type:text/html;charset=utf-8");
	
	require_once('inc/config.inc.php');

	function fileLine($filePath, $string, $line, $mode = 'w') {
	   if (is_file ( $filePath )) {   
        $fileArr = file ( $filePath ); //把文件存进数组   
    } else {   
        return '文件不存在';   
    }  
	$newFileStr=""; 
    $size = count ( $fileArr ); //数组的长度   
    if ($line > $size) { //如果插入的行数大于文件现有的行数，直接用系统自带的就行   
        return;   
    }   
    for($i = 0; $i < $size; $i ++) {   
        if ($i == $line - 1) {   
            switch (strtolower ( $mode )) { //判断是写入，还是删除或者是更新
				case 's':
				  $newFileStrs .= $fileArr [$i];
				  break;   
                case 'w' :   
                    $newFileStr .= $string . "\r\n";   
                    $newFileStr .= $fileArr [$i];   
                case 'u' :   
                   $newFileStr .= $string . "\r\n";   
                case 'd' :   
                    continue;  
				
            }   
        } else {   
            $newFileStr .= $fileArr [$i];   
        }   
    }   
	if(strtolower($mode)=='s') 
	return $newFileStrs;
    file_put_contents ( $filePath, $newFileStr );  
    return true;   
	} 


	
	$listen=fileLine('/etc/sysconfig/clock','',4,'s');  
	$content=substr($listen,6,-2);
	$time=date('Y')."-".date('m')."-".date("d")." ".date("G").":".date("i").":".date("s");
	$timezone=str_replace(" ","_",$content);
	$date = date_create($time, timezone_open($timezone));
//	echo date_format($date, 'Y-m-d H:i:s') . "\n";
	echo $timezone;
	//date_timezone_set($date, timezone_open($timezone));
	//echo date_format($date, 'Y-m-d H:i:s') . "\n";;

?>