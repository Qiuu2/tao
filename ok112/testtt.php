<?php
//header("content-type:text/html; charset=utf-8");

//人脸设备设置时间接口
/*
$today = date("Y-m-d h:i:s");
$curl = curl_init();
$postfile=array("Name" => "timeSynchronizationRequest",
								"UUID" =>"umphe5i3np6u",
							  "Data" => array("TimeMode" =>1,"LocalTime" =>$today));

//$postfile['Data']['LocalTime']=$today;
$httphead=array('UUID: umphe5i3np6u',
'Content-Type: application/json');
$httpurl='http://192.168.1.88:8011/Request';

$options = array( CURLOPT_URL => $httpurl,
									CURLOPT_RETURNTRANSFER => true,
									CURLOPT_ENCODING => '',
									CURLOPT_MAXREDIRS => 10,
									CURLOPT_TIMEOUT => 0,
									CURLOPT_FOLLOWLOCATION => true,
									CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
									CURLOPT_CUSTOMREQUEST => 'POST',
									CURLOPT_POSTFIELDS =>json_encode($postfile),
									CURLOPT_HTTPHEADER => $httphead,
                );
curl_setopt_array($curl,$options);
$response = curl_exec($curl);

curl_close($curl);
echo $response;
*/
//授权接口
/*
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => '192.168.1.151:99/api/authorizations',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
  "username": "admin",
  "userpwd": "123456"
}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Cookie: PHPSESSID=svv13pjjnuu6491l7k32ophhaf'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
*/
//获取终端信息接口
/*
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => '192.168.1.151:99/api/terminal/terminalinfo',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Accept: application/json',
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTkyLjE2OC4xLjE1MTo5OS9hcGkvYXV0aG9yaXphdGlvbnMiLCJpYXQiOjE2NDk0ODUyMzUsImV4cCI6MTY0OTQ4ODgzNSwibmJmIjoxNjQ5NDg1MjM1LCJqdGkiOiI4dGljOVVYa3N0SmhxU1R4Iiwic3ViIjoxfQ.vFMHtkvBAWQfyP1YkjDx4cDtVjfLKJayb9sj4Ivkk7k',
    'Cookie: PHPSESSID=svv13pjjnuu6491l7k32ophhaf'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
*/
//上传媒体接口
/*
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => '192.168.1.151:99/api/terminal/mediainfo?folderid=3',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => array('mediafile'=> new CURLFILE('/F:/music/32.mp3')),
  CURLOPT_HTTPHEADER => array(
    'Content-Type:  multipart/form-data',
    'Accept: application/json',
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTkyLjE2OC4xLjE1MTo5OS9hcGkvYXV0aG9yaXphdGlvbnMiLCJpYXQiOjE2NDk0ODUyMzUsImV4cCI6MTY0OTQ4ODgzNSwibmJmIjoxNjQ5NDg1MjM1LCJqdGkiOiI4dGljOVVYa3N0SmhxU1R4Iiwic3ViIjoxfQ.vFMHtkvBAWQfyP1YkjDx4cDtVjfLKJayb9sj4Ivkk7k',
    'Cookie: PHPSESSID=svv13pjjnuu6491l7k32ophhaf'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
*/
/*
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://192.168.1.151:99/api/mesplay',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
  "device":[
    {deviceCode:"6"},
    {deviceCode:"8"},
    {deviceCode:"7"},
    {deviceCode:"9"}
  ]
  "content":"同学们辛苦了同学们辛苦了同学们辛苦了同学们辛苦了",
  "voice":0,
  "system":"string",
  "time":"string"
}',
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
*/
function udpGet($sendMsg = '', $ip = 'audioserver', $port = '8886'){

  $handle = stream_socket_client("udp://{$ip}:{$port}", $errno, $errstr,30);

  if( !$handle ){

      die("ERROR: {$errno} - {$errstr}\n");
  }

  fwrite($handle, $sendMsg);

 // $result = fread($handle, 1024);

  fclose($handle);

  return 1;

}

$result = udpGet('terminal?state=20&id=99');

echo $result;
?>
