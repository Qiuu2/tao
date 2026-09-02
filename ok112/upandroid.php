<?php
header("Content-Type: text/html; charset=UTF-8");
require_once('inc/config.inc.php');
require_once('inc/config.php');
$store_dir = "upload/";// 上传文件的储存位置  
if (!is_dir($store_dir)) {  
    mkdir($store_dir,0777,true);  
}  
function detect_encoding($string){            
	$is_utf8 =  preg_match('%^(?:[\x09\x0A\x0D\x20-\x7E]| [\xC2-\xDF][\x80-\xBF]|  \xE0[\xA0-\xBF][\x80-\xBF] | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}    |  \xED[\x80-\x9F][\x80-\xBF] |  \xF0[\x90-\xBF][\x80-\xBF]{2}  | [\xF1-\xF3][\x80-\xBF]{3}  |  \xF4[\x80-\x8F][\x80-\xBF]{2} )*$%xs', $string);            
	if($is_utf8 ){                
		return $string;            	              
	}else{                
		return mb_convert_encoding($string, "UTF-8", "GBK,GB2312,BIG5");              
	}        
}  
	
$time = date("YmdHis"); 
$newfile=$time.".mp3";
$upload_file=isset($_FILES['upload_file']['tmp_name'])?$_FILES['upload_file']['tmp_name']:'';  
$upload_file_name=isset($_FILES['upload_file']['name'])?$_FILES['upload_file']['name']:'';  
$upload_file_size=isset($_FILES['upload_file']['size'])?$_FILES['upload_file']['size']:'';  
if($upload_file){  
    $file_size_max = 1000*1000*20;// 200M限制文件上传最大容量(bytes)  
    if (!is_dir($store_dir)) {  
        mkdir($store_dir,0777,true);  
    }  
    $accept_overwrite = 1;//是否允许覆盖相同文件  
    // 检查文件大小  
    if ($upload_file_size > $file_size_max) {  
        echo "file size max limite";  
        exit;  
    }  
     $filenamestr=$_POST['filename'];
     if(0==strcmp($filenamestr,"tmprecord.mp3"))
     {
     		if (file_exists($store_dir . $filenamestr) ) {  
       		system("rm -f ".$store_dir . $filenamestr);
    	 } 
    	 //复制文件到指定目录  
	     if (!move_uploaded_file($upload_file,$store_dir.$filenamestr)) {  
	        echo "cpy faile".$upload_file."to:".$store_dir.$filenamestr;  
	        exit;  
	     }
	     echo "file is alread";  
       exit;          
     }    
     else
     {
			if (file_exists($store_dir . $upload_file_name) && !$accept_overwrite) {  // 检查读写文件  
				echo "file is alread";  
				exit;  
			}  
			//复制文件到指定目录  
			if (!move_uploaded_file($upload_file,$store_dir.$newfile)) {  
				echo "cpy faile".$upload_file."to:".$store_dir.$newfile;  
				exit;  
			}   
			
			$filenamestr=$_POST['filename'];
			$upload_file_size = ($upload_file_size/1024);
			$newfilepath = $FILE_PATH.$newfile;
			//$filenamestr=detect_encoding($upload_file_name);
			$results	=mysql_query("SELECT media.filename FROM media WHERE media.name='$filenamestr' AND media.folderid=3");
			if(mysql_num_rows($results)>=0)
			{
				while ($row = mysql_fetch_array($results)) 
				{	
					system("rm -f ".$row['media.filename']);
				}
				
				$del_media_sql = "DELETE FROM media WHERE media.name='$filenamestr' AND media.folderid=3";
				mysql_query($del_media_sql);  
			}
			
			$insert_media_sql = "INSERT INTO media (media.name,size,typeid,priority,filename,folderid,timelength,channel,sample,bitrate) ";
			$insert_media_sql.= "VALUES('$filenamestr',$upload_file_size,'mp3','0', ";
			$insert_media_sql.= "'$newfilepath',3,'0',1,16000,256000)";		
			mysql_query($insert_media_sql);  
  }
    
}  


if (isset($_FILES['upload_file'])) {  
    //echo "<p>upload :";  
    //echo isset($_FILES['upload_file']['name'])?$_FILES['upload_file']['name']:'';  
    //echo "<br>";  
    //客户端机器文件的原名称。  

    //echo "file type:";  
    //echo isset($_FILES['upload_file']['type'])?$_FILES['upload_file']['type']:'';  
    //文件的 MIME 类型，需要浏览器提供该信息的支持，例如“image/gif”。  
    //echo "<br>";  

    //echo "file size:";  
    //echo isset($_FILES['upload_file']['size'])?$_FILES['upload_file']['size']:'';  
    //已上传文件的大小，单位为字节。  
    //echo "<br>";  

    //echo "file load:";  
    //echo isset($_FILES['upload_file']['tmp_name'])?$_FILES['upload_file']['tmp_name']:'';  
    //文件被上传后在服务端储存的临时文件名。  
    $erroe = isset($_FILES['upload_file']['error'])?$_FILES['upload_file']['error']:'';  
    switch($erroe){  
    case 0:  
        echo "HTTP/1.1 200 OK result:success"; break;  
    case 1:  
        echo "upload ok php.ini  upload_max_filesize "; break;  
    case 2:  
        echo "upload ok is  HTML MAX_FILE_SIZE "; break;  
    case 3:  
        echo "onlupload ok"; break;  
    case 4:  
        echo "no upload ok"; break;  
    case 6:  
        echo "upload ok dir"; break;  
    case 7:  
        echo "upload dir no write"; break;  
    case 8:  
        echo "upload stop"; break;  
    default :  
        echo "upload ok"; break;  
    }  
  
}  
?>
