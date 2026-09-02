<?
/*=======================================================================*\
	此页面可获取到的参数
	$_POST['oldNameArr']; //字符串 需要分解成数组
	$_POST['newNameArr']; //同上
	以及其他参数
	$_POST['参数名'];
\*=======================================================================*/

$haha=$_POST['haha'];
$hehe=$_POST['hehe'];

//注意这里获取到包含所有文件新旧名称的字符串
$oldName=$_POST['oldNameArr'];
$newName=$_POST['newNameArr'];

//把字符串拆成数组
$oldNameArr=explode(",",$oldName);
$newNameArr=explode(",",$newName);
$len=count($oldNameArr);

//根据获取到的数组 循环写入数据
for($i=0;$i<$len;$i++){
	//循环写入数据库  具体根据自己的需要修改
	
	//为了方便测试  我直接以追加的方式写到记事本
	$str=$oldNameArr[$i]."|".$newNameArr[$i]."|".$haha."|".$hehe."\n";
	$fp = fopen("name.txt","a");
	fwrite($fp,$str);
	fclose($fp);
}
?>