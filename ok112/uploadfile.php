<?php
/**
 * upload.php
 *
 * Copyright 2013, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */

#!! IMPORTANT: 
#!! this file is just an example, it doesn't incorporate any security checks and 
#!! is not recommended to be used in production environment as it is. Be sure to 
#!! revise it and customize to your needs.


// Make sure file is not cached (as it happens for example on iOS devices)

require_once('inc/config.inc.php');
require_once('inc/config.php');
require_once('inc/socket_conf.php');

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type:text/html;charset=utf-8');

/* 
// Support CORS
header("Access-Control-Allow-Origin: *");
// other CORS headers if any...
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	exit; // finish preflight CORS requests here
}
*/

function strFilter($str){
    $str = str_replace('`', '', $str);
    $str = str_replace('·', '', $str);
    $str = str_replace('~', '', $str);
    $str = str_replace('!', '', $str);
    $str = str_replace('！', '', $str);
    $str = str_replace('@', '', $str);
    $str = str_replace('#', '', $str);
    $str = str_replace('$', '', $str);
    $str = str_replace('￥', '', $str);
    $str = str_replace('%', '', $str);
    $str = str_replace('^', '', $str);
    $str = str_replace('……', '', $str);
    $str = str_replace('&', '', $str);
    $str = str_replace('*', '', $str);
    $str = str_replace('(', '', $str);
    $str = str_replace(')', '', $str);
    $str = str_replace('（', '', $str);
    $str = str_replace('）', '', $str);
    $str = str_replace('-', '', $str);
    $str = str_replace('_', '', $str);
    $str = str_replace('——', '', $str);
    $str = str_replace('+', '', $str);
    $str = str_replace('=', '', $str);
    $str = str_replace('|', '', $str);
    $str = str_replace('\\', '', $str);
    $str = str_replace('[', '', $str);
    $str = str_replace(']', '', $str);
    $str = str_replace('【', '', $str);
    $str = str_replace('】', '', $str);
    $str = str_replace('{', '', $str);
    $str = str_replace('}', '', $str);
    $str = str_replace(';', '', $str);
    $str = str_replace('；', '', $str);
    $str = str_replace(':', '', $str);
    $str = str_replace('：', '', $str);
    $str = str_replace('\'', '', $str);
    $str = str_replace('"', '', $str);
    $str = str_replace('“', '', $str);
    $str = str_replace('”', '', $str);
    $str = str_replace(',', '', $str);
    $str = str_replace('，', '', $str);
    $str = str_replace('<', '', $str);
    $str = str_replace('>', '', $str);
    $str = str_replace('《', '', $str);
    $str = str_replace('》', '', $str);
    $str = str_replace('.', '', $str);
    $str = str_replace('。', '', $str);
    $str = str_replace('/', '', $str);
    $str = str_replace('、', '', $str);
    $str = str_replace('?', '', $str);
    $str = str_replace('？', '', $str);
    return trim($str);
}

// 5 minutes execution time
@set_time_limit(15 * 60);

// Uncomment this one to fake upload time
// usleep(5000);

// Settings
//$targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";
$targetDir = 'upload';
$cleanupTargetDir = true; // Remove old files
$maxFileAge = 5 * 3600; // Temp file age in seconds


// Create target dir
if (!file_exists($targetDir)) {
	@mkdir($targetDir);
}

$folderid=$_GET['folderid'];

// Get a file name
if (isset($_REQUEST["name"])) {
	$fileName = $_REQUEST["name"];
} elseif (!empty($_FILES)) {
	$fileName = $_FILES["file"]["name"];
} else {
	$fileName = uniqid("file_");
}
  $valuename=time().mt_rand(1,1000000);

//$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
$filePath = "link/backup/mediadata/".$valuename.".mp3";
// Chunking might be enabled
$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
/*
// Remove old temp files	
if ($cleanupTargetDir) {
	if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
		die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
	}

	while (($file = readdir($dir)) !== false) {
		$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

		// If temp file is current file proceed to the next
		if ($tmpfilePath == "{$filePath}.part") {
			continue;
		}

		// Remove temp file if it is older than the max age and is not the current file
		if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
			@unlink($tmpfilePath);
		}
	}
	closedir($dir);
}	
*/

// Open temp file
if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
	die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
}


if (!empty($_FILES)) {
	if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
		die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
	}

	// Read binary input stream and append it to temp file
	if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
		die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
	}
} else {	
	if (!$in = @fopen("php://input", "rb")) {
		die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
	}
}
$filesizenum = round($_FILES['file']['size']);
$filesize = ($filesizenum/1024);
while ($buff = fread($in, 4096)) {
	fwrite($out, $buff);
}

@fclose($out);
@fclose($in);

// Check if file has been uploaded
if (!$chunks || $chunk == $chunks - 1) {
	// Strip the temp .part suffix off 
	rename("{$filePath}.part", $filePath);
}

$aaa=	substr(strrchr($fileName, '.'), 1); 

	if(strcmp($aaa,"wav")==0)
		{
			$oldfilepath = $filePath;
		 $value=time().mt_rand(1,1000000);
			$newfilepath = $FILE_PATH.$value.".mp3";
			$newfilepaths = "/backup/mediadata/".$value.".mp3";
			if($folderid==9)
			{
			$command = "ffmpeg -i ".$oldfilepath." -f lavfi -t 2 -i anullsrc=r=16000:cl=stereo  -filter_complex '[0:0] [1:0] concat=n=2:v=0:a=1 [a]' -map '[a]' -b:a 128k -ar 16000 -ac 2 -y ".$newfilepath;
			}
			else
			{
			$command = "ffmpeg -i ".$oldfilepath." -f lavfi -t 2 -i anullsrc=r=44100:cl=stereo  -filter_complex '[0:0] [1:0] concat=n=2:v=0:a=1 [a]' -map '[a]' -b:a 128k -ar 44100 -ac 2 -y ".$newfilepath;
			}
			system($command); 
			$commands = "rm -rf ".$oldfilepath;
			@system($commands); 

		}
	else 
		{
			$oldfilepath = $filePath;
			$value=time().mt_rand(1,1000000);
		
			$newfilepath = $FILE_PATH.$value.".mp3";
			$newfilepaths = "/backup/mediadata/".$value.".mp3";
			if($folderid==9)
			{
				$command = "ffmpeg -i ".$oldfilepath." -f lavfi -t 2 -i anullsrc=r=16000:cl=stereo  -filter_complex '[0:0] [1:0] concat=n=2:v=0:a=1 [a]' -map '[a]' -b:a 128k -ar 16000 -ac 2 -y ".$newfilepath;
			}
			else
			{ 
				$command = "ffmpeg -i ".$oldfilepath." -f lavfi -t 2 -i anullsrc=r=44100:cl=stereo  -filter_complex '[0:0] [1:0] concat=n=2:v=0:a=1 [a]' -map '[a]' -b:a 128k -ar 44100 -ac 2 -y ".$newfilepath;
			}
			system($command); 	
			
			$commands = "rm -rf ".$oldfilepath;
			@system($commands); 
	
		}
//	$fp = fopen("get_backup.log","w");
//	fwrite($fp,$command);
//  fclose($fp);
	$oldName_f = substr($fileName, 0, strrpos($fileName, "."));
	//$oldName_fs = mb_convert_encoding($oldName_f,"UTF-8","gb2312");
	//$oldName_f=strFilter($oldName_fs);
	//$commands = "sudo chmod 777 ".$newfilepaths;
	//@system($commands); 

	$tempinfo = "chmod 777 /backup/mediadata/".$value.".mp3";
	$command = "cmdhost --cmd=\"".$tempinfo."\"";
	exec($command, $output_info,$last_line);
		
	$extname="mp3";
	$userid=$_SESSION['userid'];
	$sql="SELECT media.name,filename,media.id FROM media WHERE media.name='$oldName_f' AND media.folderid='$folderid'";
		$results	=mysqli_query($con,$sql);
		if(mysqli_num_rows($results)<=0)
		{
			$insert_media_sql = "INSERT INTO media (media.name,size,typeid,priority,filename,folderid,timelength,channel,sample,bitrate,userid) ";
			$insert_media_sql.= "VALUES('$oldName_f','$filesize','$extname','0', ";
			$insert_media_sql.= "'$newfilepaths',$folderid,'0',0,0,0,$userid)";
			mysqli_query($con,$insert_media_sql);
		}
		else
		{
			if($row=mysqli_fetch_array($results))
			{
			
				$getid=$row['id'];
				mysqli_query($con, "UPDATE media SET size='$filesize',timelength=0,sample='0',bitrate='0',filename='$newfilepaths'  WHERE media.id = '$getid'" ) or dir(mysqli_error($con));
			}
		}

if(mysqli_error($con))
{

}
$_SESSION['serverip'] = "audioserver";
$socket	=	new	send_message_to_server($port_conf);	
$msg = "file?state=1&id=".$folderid;
$socket->send_data($_SESSION['serverip'],$msg);
// Return Success JSON-RPC response
die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
