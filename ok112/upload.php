<?php
header('Content-Type:text/html;charset=utf-8');
require_once('inc/config.inc.php');
require_once('inc/config.php');
require_once('inc/socket_conf.php');
require_once("inc/get_mp3_info_class.php");

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
//$fp = fopen("get_backup.log","w");
$oldName_f=str_replace("'","",$_GET['oldName']);
//fwrite($fp,$oldName_f);
//fclose($fp);
$newName_f=$_GET['newName'];

$haha=$_GET['haha'];

$hehe=$_GET['hehe'];

$f=$_FILES['Filedata'];

$dir='upload';

$tmpName=$f['tmp_name'];

$get_mp3_obj = new get_mp3_info_class();
	
$mp3_path = $tmpName;

$mp3_path = mb_convert_encoding($mp3_path,"gb2312","utf-8");

$get_mp3_info = $get_mp3_obj->mp3info($mp3_path);

$get_mp3_time = $get_mp3_info['seconds']+1;

$get_mp3_info['bitrate']=128000;
$get_mp3_bitrate = $get_mp3_info['bitrate'];
if($get_mp3_info['sample']=="")
$get_mp3_info['sample']=44100;
$get_mp3_sample = $get_mp3_info['sample'];

$get_mp3_channel = $get_mp3_info['cmode'];

$extname=substr(strrchr($_GET['newName'],"."),1);

$filesizenum = round($_FILES['Filedata']['size']);
$folderid=$_GET[folderid];
$filesizenum = ($filesizenum/1024);
//system("ln -s /backup/mediadata /var/www/html/ok112/upload");
$filename = $_FILES['Filedata']['name'];
$oldName_fs = mb_convert_encoding($oldName_f,"UTF-8","gb2312");
$oldName_f=strFilter($oldName_fs);
		$sql="SELECT media.name,filename,media.id FROM media WHERE media.name='$oldName_f' AND media.folderid='$folderid'";
		$results	=mysqli_query($con,$sql);
		if(($upload_verify = move_uploaded_file($f['tmp_name'],$FILE_PATH.$newName_f))== false)
		{
			//移动失败
		}
	
 		$aaa=	substr(strrchr($newName_f, '.'), 1); 
		if(strcmp($aaa,"wav")==0)
		{
			$oldfilepath = $FILE_PATH.$newName_f;
			$value = basename($newName_f,".wav");
			$newfilepath = $FILE_PATH.$value.".mp3";
			$newName_f=$value.".mp3";
		//	$command = "ffmpeg -i ".$oldfilepath." -b:a 128k -y -ar 44100 -ac 2 ".$newfilepath;
			//$command = "ffmpeg -f lavfi -t 2 -i anullsrc=r=44100:cl=stereo -i ".$oldfilepath." -filter_complex '[0:0] [1:0] concat=n=2:v=0:a=1 [a]' -map '[a]' -b:a 128k -ar 44100 -ac 2 -y ".$newfilepath;
			if($_GET[folderid]==9)
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
			$extname="mp3";
		}
		else
		{
			$oldfilepath = $FILE_PATH.$newName_f;
			$newfilepath= $FILE_PATH."0".$newName_f;
		//	ffmpeg -i /backup/mediadata/1.mp3 -f lavfi -t 2 -i anullsrc=r=44100:cl=stereo  -filter_complex '[0:0] [1:0] concat=n=2:v=0:a=1 [a]' -map '[a]' -b:a 128k -ar 44100 -ac 2 -y /backup/mediadata/2.mp3
			if($_GET[folderid]==9)
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
		$commands = "chmod 777 ".$newfilepath;
		@system($commands); 
		$userid=$_SESSION['userid'];
		//$newfilepath = $FILE_PATH.$newName_f;
		if(mysqli_num_rows($results)<=0)
		{
			$insert_media_sql = "INSERT INTO media (media.name,size,typeid,priority,filename,folderid,timelength,channel,sample,bitrate,userid) ";
			$insert_media_sql.= "VALUES('$oldName_f',$filesizenum,'$extname','0', ";
			$insert_media_sql.= "'$newfilepath',$_GET[folderid],'0',$get_mp3_channel,$get_mp3_sample,$get_mp3_bitrate,$userid)";
			mysqli_query($con,$insert_media_sql);
		}
		else
		{
			if($row=mysqli_fetch_array($results))
			{
				/*if(file_exists($row['filename']))
				{
					unlink($row["filename"]);	
				}*/
				$getid=$row['id'];
			//	$getfilename=$row['filename'];
			//	$commandid="mv  ".$newfilepath." ".$getfilename;
			//	@system($commandid); 
				mysqli_query($con, "UPDATE media SET size='$filesizenum',timelength=0,sample='$get_mp3_sample',bitrate='$get_mp3_bitrate',filename='$newfilepath'  WHERE media.id = '$getid'" ) or dir(mysqli_error($con));
			}	
		}

if(mysqli_error($con))
{

}

	$socket	=	new	send_message_to_server($port_conf);	
	$msg = "file?state=1&id=".$_GET[folderid];
	$socket->send_data($_SESSION['serverip'],$msg);

?>
