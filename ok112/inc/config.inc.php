<?php
if (!session_id()) session_start();

$language_path = $_SERVER['DOCUMENT_ROOT']."/language/".$_SESSION['language'].".php";
require_once("config.php");
date_default_timezone_set('Asia/Shanghai');
$con = mysqli_connect($DB_HOST,$DB_USER,$DB_PWD,$DB_NAME);
mysqli_query($con,"SET NAMES UTF8");
mysqli_select_db($con,$DB_NAME);

$FUZA_PASS	=0;     //改为复杂密码 

$fuza_sql = "SELECT fuzamima from serverconfig ";    //channel用于复杂密码修改
$fuza_result = mysqli_query($con,$fuza_sql) or die(mysqli_error($con));
while($fuza_row = mysqli_fetch_array($fuza_result))
{
	if($fuza_row['fuzamima']!=0)
	{
		$FUZA_PASS=1;
	}
	else
	{
		$FUZA_PASS=0;
	}
}

$NumOfPage = 18;

function xml_str_analyze($str_xml)
{
	$first_arror = 0;
	$second_arror = 0;
	
	$sub_len = 0;
	
	$sub_str = "";
	
	$first_arror = stripos($str_xml,">")+1;
	
	$second_arror = stripos($str_xml,"</");
	
	$sub_len = $second_arror- $first_arror;
	
	$sub_str = substr($str_xml,$first_arror,$sub_len);
	
	return $sub_str;
}

function get_terminal_type($flag,$error,$id,$single)
{
	global $con;
	$type="";
	
	switch($flag)
	{
		case 1:   //Ѱ�������ʾ
		if($id==0)
		$sql="SELECT id FROM terminaltype WHERE isencode='1' AND isdecode='1' AND id  not in(0,26,2,6,7,8,9,10,12,15,16,17,18,21,22,25,28,29,30,31,32)";
		else
		$sql="SELECT id FROM terminal WHERE id IN ($id) AND typeid IN(SELECT id FROM terminaltype WHERE isencode='1' AND isdecode='1' AND id not in(0,26,2,6,7,8,9,10,12,15,16,17,18,21,22,25,28,29,30,31.32))";
		break;
		case 2:  //�Խ�������ʾ
		if($id==0)
		$sql="SELECT id FROM terminaltype WHERE isencode='1' AND id  not in(0,1,6,7,8,9,10,12,11,13,15,16,18,19,20,21,22,23,24,25,27,29,31,32,36,37,38,39)";
		else
		$sql="SELECT id FROM terminal WHERE id IN ($id) AND typeid IN(SELECT id FROM terminaltype WHERE isencode='1' AND id  not in(0,1,6,7,8,9,10,12,11,13,15,16,18,19,20,21,22,23,24,25,27,29,31,32,36,37,38,39))";
		break;
		case 3:
		if($id==0)
		$sql="SELECT id FROM terminaltype WHERE isdecode='1' AND id not in(0,26,2,7,8,9,10,12,15,16,17,21,22,25,28,29,30,31,32,36,37,40,41,42)";
		else
		$sql="SELECT id FROM terminal WHERE id IN ($id) AND typeid IN(SELECT id FROM terminaltype WHERE isdecode='1' AND id not in(0,26,2,7,8,9,10,12,15,16,17,21,22,25,28,29,30,31,32,36,37,40,41,42))";
		break;
		case 4:
		if($id==0)
		$sql="select id from terminaltype where shortkeycount>='1'";
		else
		$sql="SELECT id FROM terminal WHERE id IN ($id) AND typeid IN (select id from terminaltype where shortkeycount>='1')";
		break;
		case 5:
		if($id==0)
		$sql="select id from terminaltype where isLCD>='1' and id NOT IN (34,35,36,37,28)";
		else
		$sql="SELECT id FROM terminal WHERE id IN ($id) AND typeid IN (select id from terminaltype where isLCD>='1' and id NOT IN (34,35,36,37,28))";
		break;
		case 6:
		if($id==0)
		$sql="select id from terminaltype where switchcount>'1'";
		else
		$sql="SELECT id FROM terminal WHERE id IN ($id) AND typeid IN (select id from terminaltype where switchcount>'1')";
		break;
		case 7:
		$sql="SELECT id FROM terminaltype WHERE id not in(0,6,7,8,9,16,18,22,25,29,31,32,43,12,16)";
		break;
		case 8:
		$sql="SELECT id FROM terminal WHERE id IN ($id) AND typeid IN (SELECT id FROM terminaltype WHERE isspeech ='1')";
		break;
		case 9:
		$sql="SELECT id FROM terminal WHERE id IN ($id) AND typeid IN(SELECT id FROM terminaltype WHERE  id in(1,2,5,8,11,17,23,24,26,30,38,34,41,44))";
		break;
		case 10:
		$sql="SELECT id FROM terminal WHERE id IN ($id) AND typeid IN(SELECT id FROM terminaltype WHERE  id  not in(0,1,6,7,9,10,12,13,15,16,19,20,21,22,23,27,29,33,36,37,38,39))";
		break;
		case 11:  
		$sql="SELECT id FROM terminaltype WHERE id IN(33)";
		break;
		case 12:
		$sql="SELECT id FROM terminal WHERE id IN ($id) AND typeid IN(SELECT id FROM terminaltype WHERE  id  not in(0,1,6,7,9,10,12,13,15,16,19,20,21,22,23,27,29,36,37,39))";
		break;
		case 13:
		$sql="SELECT id FROM terminal WHERE id IN ($id) AND typeid IN(41)";
		break;	
		case 14:
		$sql="SELECT id FROM terminaltype WHERE  id in(42)";		
		break;
		case 15:
		if($id==0)
		$sql="SELECT id FROM terminaltype WHERE isdecode='1' AND id not in(0,26,2,7,8,9,10,12,15,16,17,18,21,22,25,29,30,31,32,36,37,40,41)";
		else
		$sql="SELECT id FROM terminal WHERE id IN ($id) AND typeid IN(SELECT id FROM terminaltype WHERE isdecode='1' AND id not in(0,26,2,7,8,9,10,12,15,16,17,18,21,22,25,29,30,31,32,36,37,40,41))";
		break;
		case 16:
			if($id==0)
			$sql="SELECT id FROM terminaltype WHERE isdecode='1' AND id not in(0,26,2,7,8,9,10,12,15,16,17,18,21,22,25,28,29,30,31,32,36,37,40,41,42)";
			else
			$sql="SELECT id FROM terminal WHERE id IN ($id) AND typeid IN(SELECT id FROM terminaltype WHERE isdecode='1' AND id not in(0,26,2,7,8,9,10,12,15,16,17,18,21,22,25,28,29,30,31,32,36,37,40,41,42))";
			break;
	}
		$get_result = mysqli_query($con,$sql) or die(mysqli_error($con));
		if(mysqli_num_rows($get_result) <=0)
		{
			if($id!=0)
			{
				echo "<script>alert('".$error."');</script>";
				if($single==1)
				  echo "<script>window.history.back();</script>";
			}
		}
				while($result_row = mysqli_fetch_array($get_result))
				{
				
					if($type=="")
						$type=$result_row['id'];
					else
					$type=$type.','.$result_row['id'];
				}
				@mysqli_free_result($get_result);
				
				unset($sql,$result_row);
	
	return $type;
}

function get_gpstime()
{
	return 1;
}

function get_gpsterminal()
{
	$sql = "SELECT 	terminalname,terminal.id from terminal,terminaltype where terminal.typeid=terminaltype.id and terminaltype.id='31'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	while($row = mysqli_fetch_array($result))
	{
		echo $row['terminalname'];
			$terminalinfo = array(
								"terminalname"=>$row['terminalname'],"id"=>$row['id']
							 ); 
	}	
	@mysqli_free_result($result);
	echo $terminalinfo;
}



function getarray($array,$start,$num,$get_task_id,$col,$dataType)
{
	$aa=array();
	
	$allnum=$start+$num;
	$kk=0;
	foreach ($array as $key => $row)   
   {  
      $vals[$key] = $row[$col];  
   }

   if($_SESSION['tempflagcol']==$col)
   {
 		// array_reverse($array);
 		if($dataType=='1')  
		array_multisort($vals,SORT_DESC,SORT_STRING,$array);
		else if($dataType=='2')
		array_multisort($vals,SORT_DESC,SORT_NUMERIC,$array);
		$_SESSION['tempflagcol']=0;
	}
	else
	{
	 	if($dataType=='1')  
		array_multisort($vals,SORT_ASC,SORT_STRING,$array);
		else if($dataType=='2')
		array_multisort($vals,SORT_ASC,SORT_NUMERIC,$array);
		$_SESSION['tempflagcol']=$col;
	}
	for($i=$start;$i<$allnum;$i++)
	{
		if($array[$i]==NULL)
			break;	
		$aa[$kk]=$array[$i];
		$kk++;
	}
	
return $aa;
}

function fileLines($filePath, $string, $line, $mode = 'w') {
	   if (is_file ( $filePath )) {   
        $fileArr = file ( $filePath ); //把文件存进数组   
    } else {   
        return '文件不存在';   
    }  
	$newFileStr=""; 
	$newFileStrs="";
    $size = count ( $fileArr ); //数组的长度   
    if ($line > $size) { //如果插入的行数大于文件现有的行数，直接用系统自带的就行   
        return;   
    }

    for($i = 0; $i < $size; $i ++) {   
        if ($i == $line - 1) {   
            switch (strtolower ( $mode )) { //判断是写入，还是删除或者是更新
				case 's':
				  $newFileStrs .= $fileArr[$i];
				case 'a':
						$newFileStrs .= $fileArr[$i];		
				  break;   
						case 'w' :   
								$newFileStr .= $string . "\r\n";   
								$newFileStr .= $fileArr [$i];  
								break;    
						case 'u' :   
								$newFileStr .= $string . "\r\n";  
								break;    
						case 'd' :   
								break;  
            }   
        } else {   
            $newFileStr .= $fileArr [$i];   
        }   
    }  

		if(strtolower($mode)=='a')
		{
			$substing=strstr($newFileStrs,$string);
			if($substing!==false)
			{
				$substr = substr($newFileStrs, strlen($string)+1);	
				return trim($substr);
			}
		} 

	if(strtolower($mode)=='s') 
	return $newFileStrs;
    file_put_contents ( $filePath, $newFileStr );  
    return true;   
}

function insert_logs($opt,$olduser)
{
	
	global $do_php_prompt;	
	global $con;
	$ip = $_SERVER['REMOTE_ADDR'];
	if(!empty($_SESSION['username']))
	{
		$user = $_SESSION['username'];
	}
	else
	{
		$user = $olduser;
	}
	
	$time = gmdate("Y-m-d H:i:s",time()+8*3600);
	
	$log_sql = "INSERT INTO audioserver.log (log.user, log.operate, log.ip, log.time)";
	
	$log_sql.= " VALUES ('$user','$opt','$ip','$time') ";

	mysqli_query($con,"START TRANSACTION");
		mysqli_query($con,"lock table log write");

	mysqli_query($con,$log_sql) or die(mysqli_error($con));
	
	unset($log_sql);
	
	mysqli_query($con, "UNLOCK TABLES" );

}

$a9000path=fileLines('link/script/a9000default','A9000PATH',1,'a');  

?>





















