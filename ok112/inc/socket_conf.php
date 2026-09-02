<?php
require_once('config.php');

require_once('config.inc.php');
global $con;
$port_conf = 50000;
$sdkport_conf = 5000;
$server_sql = "select webport from serverbaseparam ";

$server_result = mysqli_query($con,$server_sql) or die(mysqli_error($con));

if($server_row = mysqli_fetch_array($server_result))
{
	$port_conf = $server_row['webport'];
}

class send_message_to_server
{
	private $port;
	
	function __construct($port)
	{
		//global $port_conf;
		//$port = $port_conf;
		$this->port = $port;
	}

	function send_data($serverip,$strbuff)
	{
		$sock=socket_create(AF_INET, SOCK_DGRAM, 0) or die("Could not create socket\n"); 
	
		$temp = socket_sendto($sock,$strbuff,strlen($strbuff),0x0,$serverip,$this->port) or die("Not Send Message");
		
	
		socket_close($sock);
	}
	
	
	
	  public static function getallBytes($string,$length) { 
        $bytes = array(); 
        for($i = 0; $i < strlen($string); $i++){ 
             $bytes[] = ord($string[$i]); 
        } 
 
		 for($j = 0; $j <($length-strlen($string)); $j++){ 
             $bytes[] ='' ; 
        } 
        return $bytes; 
 	}
	
	  public static function toStr($bytes) { 
        $str = ''; 
        foreach($bytes as $ch) { 
            $str .= chr($ch); 
        } 
           return $str; 
    } 


	  public static function shortToBytes($val) { 
			$byt = array(); 
			$byt[0] = ($val & 0xff); 
			$byt[1] = ($val >> 8 & 0xff); 
			return $byt; 
		} 

	  public static function integerToBytes($val) { 
        $byt = array(); 
        $byt[0] = ($val & 0xff); 
        $byt[1] = ($val >> 8 & 0xff); 
        $byt[2] = ($val >> 16 & 0xff); 
        $byt[3] = ($val >> 24 & 0xff); 
        return $byt; 
    }
	
	function send_systemdata($strbuff)
	{
		$path="./tt";
		$form='';
		$socket=socket_create(AF_UNIX, SOCK_DGRAM, 0) or die("Could not create socket\n"); 
 		$result = socket_connect($socket, $path);  //这里只要两个参数即可
		if($result<0)
		{
	
		}
		if(!socket_write($socket,$strbuff,strlen($strbuff)))
		{
		
			
		}
		//	$temp = socket_sendto($socket,$strbuff,strlen($strbuff),0x0,$form) or die("Not Send Message");
		socket_close($socket);
	}
	
	function send_datatosdk($serverip,$strbuff,int $id,int $len,int $state)
	{
		$randid=mt_rand(10000,99999);
		$heard=$this->toStr($this->shortToBytes(0xeeee));
		$getcmd=$this->toStr($this->shortToBytes($id));
		$lens=$this->toStr($this->shortToBytes($len));
		$statess=$this->toStr($this->shortToBytes($states));
		$serialid=$this->toStr($this->integerToBytes(0));
		$sessionid=$this->toStr($this->integerToBytes($randid));
		$senddemo=$heard.$getcmd.$lens.$statess.$serialid.$sessionid.$strbuff;	

		$sock=socket_create(AF_INET, SOCK_DGRAM, 0) or die("Could not create socket\n"); 
	
		$temp = socket_sendto($sock,$senddemo,strlen($senddemo),0x0,$serverip,8877) or die("Not Send Message");
		socket_close($sock);
	}

	function send_datacommandid($serverip,$strbuff)
	{
		$sock=socket_create(AF_INET, SOCK_DGRAM, 0) or die("Could not create socket\n"); 
		$temp = socket_sendto($sock,$strbuff,strlen($strbuff),0x0,$serverip,8811) or die("Not Send Message");
		socket_close($sock);
	}
}				
?>