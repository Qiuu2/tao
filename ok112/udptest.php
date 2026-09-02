<?php
$server_ip="192.168.1.68";
$port = 8888;
$sock=@socket_create(AF_INET,SOCK_DGRAM,SOL_UDP);
if(!$sock)
{
    echo "socket create failure<br>\n";
}
if($buf=="")
{
    $buf="hello,how are you!<br>\n";
}
$cmd = "\x02\x00\x00\x00\x00\x00\x00\x00\x04\x03\x02\x01\x08\x07";


//if(($error = socket_bind($sock, gethostbyname("localhost"), 8888)) < 0)
//  {
  //    print("Couldn't bind socket: " . socket_strerror(socket_last_error()) . "\n");
  //}

//echo "5555sfgdsgddsfgdsgdf<ar>\ff";
if(!@socket_sendto($sock,$cmd,strlen($cmd),0,$server_ip,8888))
{
    echo "send error<br>\n";
    socket_close($sock);
    exit();
}
//echo "6666sfgdsgddsfgdsgdf<br>\n".$buf;
$buf="";
$msg="";
//if(!@socket_recvfrom($sock,$msg,256,0,&$server_ip,&$port))
//{
 //   echo "recvieve error!";
 //   socket_close($sock);
  //  exit();
//}

echo trim($msg)."<br>\n";
echo "OK<br>\n";
socket_close($sock);
?>
