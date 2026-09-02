<?
header('Content-Type:text/html;charset=utf-8');

require_once("inc/socket_conf.php");

//注意这里获取到包含所有文件新旧名称的字符串

$oldName=$_POST['oldNameArr'];

$newName=$_POST['newNameArr'];
//把字符串拆成数组

$oldNameArr=explode(",",$oldName);

$newNameArr=explode(",",$newName);

$len=count($oldNameArr);

$str = "";
//根据获取到的数组 循环写入数据
for($i=0;$i<$len;$i++)
{
	if($str == "")
	{
		$str = $oldNameArr[$i]."=".$newNameArr[$i];
	}
	else
	{
		$str.=",".$oldNameArr[$i]."=".$newNameArr[$i];
	}
}
//发送数据

$socket = new send_message_to_server($port_conf);

	$folderid=$_POST['folderid'];
	
	$sql= "SELECT parentid FROM filefolder WHERE id='$folderid'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	if ($row = mysqli_fetch_array($result))
	{
		if($row['parentid']==0)
		{
			$msg = "file?state=1&id=".$_POST['folderid']."";
		}
		else if($row['parentid']==1||$row['parentid']==2||$row['parentid']==3||$row['parentid']==4||$row['parentid']==5)
		{
			$msg = "file?state=1&id=".$row['parentid']."";
		}
		else
		{
			$id=$row['parentid'];
			$sqls= "SELECT parentid FROM filefolder WHERE id='$id'";
			$results = mysqli_query($con,$sqls) or die(mysqli_error($con));
			if ($rows = mysqli_fetch_array($results))
			{
				if($rows['parentid']==1||$rows['parentid']==2||$rows['parentid']==3||$rows['parentid']==4||$rows['parentid']==5)
				{
					$ids=$rows['parentid'];
					$msg = "file?state=1&id=".$ids."";
				}
			}
		}
	}



$temp = $socket->send_data($_SESSION['serverip'],$msg);

//$fp = fopen("test_meiti_wenjian.xml","a");
//
//fwrite($fp,$_POST['folderid']."........".$msg);
//
//fclose($fp);
?>