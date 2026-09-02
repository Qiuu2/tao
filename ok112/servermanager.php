<?php
if (!session_id()) session_start();

header("content-type:text/html;charset=utf-8");

require_once('inc/smarty.inc.php');
require_once('inc/config.inc.php');
require_once("inc/config.php");

//验证是否失效
require_once("verify_user_sessionin_valid.php");

			
verifysessionvalid();

if(empty($_SESSION['admin_id']))
{
	header("location:login.php");	
}
else
{
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	$smarty->assign("language",$_SESSION['language']);
	$smarty->assign("server_manager",$server_manager);
	$smarty->assign("admin_id",$_SESSION['admin_id']);
	$smarty->assign("registerflag",$_SESSION['registerflag']);
	//获取上传路径
	$mediapath=$_SERVER['DOCUMENT_ROOT']."/".$FILE_PATH; 
	function fileLine($filePath, $string, $line, $mode = 'w') {
		

	   if (is_file(trim($filePath))) 
	   {  

        $fileArr = file($filePath); //把文件存进数组   
				
  } else {   
        return '文件不存在';   
    } 
	
	$newFileStr=""; 
	$newFileStrs="";
    $size = count ($fileArr); //数组的长度   

    if ($line > $size) { //如果插入的行数大于文件现有的行数，直接用系统自带的就行   
        return;   
    }   
		
    for($i = 0; $i < $size; $i ++) {   
        if ($i == $line - 1) {   
            switch ($mode) { //判断是写入，还是删除或者是更新
				case 's':
				  $newFileStrs .= $fileArr[$i];

				  break;   
                case 'w' :   
                    $newFileStr .= $string . "\r\n";   
                    $newFileStr .= $fileArr[$i];   
                case 'u' :   
                   $newFileStr .= $string . "\r\n";   
                case 'd' :   
                    break;  
				
            }   
        } else {   
            $newFileStr .= $fileArr [$i];   
        }   
    }  

	if($mode=='s') 
	{
		return $newFileStrs;
	}
	
    file_put_contents ( $filePath, $newFileStr );  
    return true;   
} 

function mask_change($mask){
	$net_mask = "";
	for($i = 0; $i < 32; $i++){
	if($i < $mask)
	$net_mask.="1";
	else
	$net_mask.="0";
	}
	$src_ip_mask = "";
	$ten_hex_num = bindec($net_mask);
	for($i=3; $i>=0;$i--){
	$src_ip_mask.= ($ten_hex_num>>8*$i)&0xff;
	if($i!=0)
	$src_ip_mask.=".";
	}
	return $src_ip_mask;
	}


if($_SESSION['webport']==1)
{
	$smarty->assign("setwebport",1);	
}
else
	$smarty->assign("setwebport",0);	

	$sdkaddrlisten=fileLine('swagger-ui/dist/swagger1.json','',11,'s');  

	$adkip=substr($sdkaddrlisten,9,20);
	$sdk_addr = explode(":",$adkip);

	$smarty->assign("sdkaddr",$sdk_addr[0]);	
	
	$aaa=is_dir('link/home/apache/ssl');

		$listen=fileLine('link/home/apache/httpd.conf','',52,'s'); 
		$sdklisten=fileLine('link/home/apache/httpd.conf','',54,'s');  
	
		$heartnetmaskip=fileLine('link/etc/ha.d/ha-post.sh','',12,'s'); 

		$delimiter = "\/";
		$delimiterone = '/';
		$parts = explode($delimiter, $heartnetmaskip);

		$partsone=explode($delimiterone, $parts[1]);

		$mask=mask_change($partsone[0]);  
	//	$smarty->assign("heartgateway",$parts[4]);
		$smarty->assign("heartnetmaskip",$mask);
		
  	$smarty->assign("listenport",substr($listen,7,5));	
  	$smarty->assign("sdkport",substr($sdklisten,7,5));	
	  $smarty->assign("Version_Item",$Version_Item);
	$result	=	mysqli_query($con,"SELECT * FROM serverbaseparam");
	
	if($row = mysqli_fetch_array($result))
	{
		$smarty->assign("id",$row['id']);	
		$smarty->assign("name",$row['name']);	
		$smarty->assign("port",$row['port']);	
		$smarty->assign("serverip",$row['serverip']);
		$smarty->assign("ip",$row['ip']);	
		$smarty->assign("nodaemon",$row['nodaemon']);		
		$smarty->assign("udpport",$row['udpport']);	
		$smarty->assign("workstate",$row['workstate']);	
		$smarty->assign("rtspport",$row['rtspport']);	
		$smarty->assign("maxhttpconnections",$row['maxhttpconnections']);		
		$smarty->assign("maxbandwidth",$row['maxbandwidth']);		
		$smarty->assign("currectconnectcount",$row['currectconnectcount']);	
		$smarty->assign("currentbandwidth",$row['currentbandwidth']);	
		$smarty->assign("mediapath",$mediapath);
		$smarty->assign("customlog",$row['customlog']);	
	  $smarty->assign("gateway",$row['gateway']);
		$smarty->assign("taskcount",$row['taskcount']);
		$smarty->assign("dateport",$row['dataport']);
		$smarty->assign("subnetmask",$row['subnetmask']);
		$smarty->assign("offlineport",$row['offlineport']);
		$smarty->assign("servermodes",$row['backupmode']);	
		$smarty->assign("model",$row['model']);	
		$smarty->assign("backup",$row['backup']);	
		$smarty->assign("masterip",$row['masterip']);	
		$smarty->assign("slaveip",$row['slaveip']);	
		$smarty->assign("slavename",$row['slavename']);	
		$getversion=explode("-",$row['version']);
		$version='server-'.$getversion[0].' Status&nbsp;&nbsp;'.$getversion[1];
		$smarty->assign("version",$version);	
		$smarty->display("servermanager.html");
	}
	
}
?>