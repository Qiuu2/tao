<?php
   // $shell ="ls / -la";
 /*  $shell="sed -i '1c 10.10.0.170 HA170s' /etc/hostname";
    echo "<pre>";
    system($shell,$status);
    echo "</pre>";
    //注意shell命令的执行结果和执行返回的状态值的对应关系
    $shell ="<font color='red'>$shell</font>";
    if($status ){
        echo "shell命令{$shell}执行失败";
    }else {
        echo "shell命令{$shell}成功执行";
    }*/
	
	 define("UNIX_DOMAIN","/opt/soundsdk/tt");
// echo UNIX_DOMAIN;
  
 $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);  //第三个参数为0
 if ($socket < 0) 
 { 
      echo "socket_create() failed: reason: " . socket_strerror($socket) . "\n"; 
 } 
 else
 { 
      echo "create OK.\n"; 
 }  
   
  $result = socket_connect($socket, UNIX_DOMAIN);  //这里只要两个参数即可
 if ($result < 0) 
 { 
      echo "socket_connect() failed.\nReason: ($result) " . socket_strerror($result) . "\n"; 
 } 
 else
 { 
      echo "connect OK"; 
 }
   
  //下面的代码和普通php socket通信一致
 $in = "测试IPC通信\n";  
 $serverip="192.168.2.50";  
 $temp = socket_sendto($socket,$in,strlen($in),0x0,$serverip); 
 echo $temp; 
 /*if(!socket_write($socket, $in, strlen($in))) 
 { 
      echo "socket_write() failed: reason: " . socket_strerror($socket) . "\n"; 
 } 
 else
 
 { 
      echo "send message ok！\n";  
      echo "\naccept message=".socket_read($socket, 8192)."\n";
 }  
 */
 
 echo "Close SOCKET...\n"; 
 socket_close($socket); 
 echo "Close OK\n"; 
	
?>