<?php
	if (!session_id()) session_start();
	header("content-type:text/html;charset=utf-8");
	require_once("inc/config.inc.php");
	require_once("inc/socket_conf.php");

	$getid=0;
	if(isset($_GET['id']))
	{
		$getid = trim($_GET['id']);
	}


	$method = 'DES-ECB';//加密方法
 	$passwd = '12312312';//加密密钥
	 $options = 0;//数据格式选项（可选）
   $iv = '';//加密初始化向量（可选）
   
   
	$sdkaddrlisten=fileLines('/var/www/html/ok112/swagger-ui/dist/swagger1.json','',11,'s');  

	$adkip=substr(trim($sdkaddrlisten),9,20);

	$sdk_addr = explode(":",trim($adkip));
	$sdkaddr=trim($sdk_addr[0]);
	$sdkport=trim($sdk_addr[1]);
	$port=str_replace('",','',$sdkport);
		$sql3 = "SELECT username,userpwd FROM book_admin where id='$getid'";
	$result2 = mysqli_query($con,$sql3) or die(mysqli_error($con));
	if($row = mysqli_fetch_array($result2))
	{
			/*$result[] = [
				'username' => $row[0],
				'userpwd' => $row[1],
				'serverip' => $sdkaddr,
				'serverport' => $port,
			];*/
			
			$results=$row[0].",".$row[1].",".$sdkaddr.",".$port;
			$result = openssl_encrypt($results, $method, $passwd, $options);
			
			
			//$bbb=openssl_decrypt($result, $method, $passwd, 0);
			
			echo trim($result);
	}
	
?>