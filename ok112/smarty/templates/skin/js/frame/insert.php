<?
require_once("inc/config.inc.php");
/*=======================================================================*\
	��ҳ��ɻ�ȡ���Ĳ���
	$_POST['oldNameArr']; //�ַ��� ��Ҫ�ֽ������
	$_POST['newNameArr']; //ͬ��
	�Լ���������
	$_POST['������'];
\*=======================================================================*/

  $folderid=$_POST['folderid'];

	//ע�������ȡ�����������ļ��¾����Ƶ��ַ���
	$oldName=$_POST['oldNameArr'];
	$newName=$_POST['newNameArr'];

	//���ַ����������
	$oldNameArr=explode(",",$oldName);
	$newNameArr=explode(",",$newName);
	$len=count($oldNameArr);
	$error = 0

	//���ݻ�ȡ�������� ѭ��д������
	for($i=0;$i<$len;$i++)
	{
		//ѭ��д�����ݿ�  ��������Լ�����Ҫ�޸�
	  $str=$oldNameArr[$i]."|".$newNameArr[$i]."|".$folderid."\n";
	  $fp = fopen("name.txt","a");
	  fwrite($fp,$str);
	  fclose($fp);
	  
		//Ϊ�˷������  ��ֱ����׷�ӵķ�ʽд�����±�
		$str=$oldNameArr[$i]."|".$newNameArr[$i]."|".$haha."|".$hehe."\n";
		mysqli_query($con,"INSERT INTO `media` (`name`,`filename`,`folderid`,`size`) VALUES ('$oldNameArr[$i]','$newNameArr[$i]','$folderid',0)");	        
		
		if(mysqli_error($con))
		{
			$error=1;
		  break;
		}
	}
	
	if($error ==1)
	{
		$error=1;
		$_SESSION['info'] = "failed��".mysqli_error($con);
		$_SESSION['url'] = "./filemanager.php";
		echo "<script>window.location='error.php'</script>";
	}else{
		$_SESSION['info'] = "success��".$FILE_PATH.$newfile_name;
		$_SESSION['url'] = "./filemanager.php";
		echo "<script>window.location='success.php'</script>";	
	}
?>