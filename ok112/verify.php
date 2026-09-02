<?
if (!session_id()) session_start();
require_once('inc/config.php');
require_once('inc/config.inc.php');
$authnum ='';

$str = 'abcdefghijkmnpqrstuvwxyz123456789';

$l = strlen($str); 
if($FUZA_PASS!=0)
{
	$getverify=6;
}
else
{
	$getverify=4;
}
for($i=1;$i<=$getverify;$i++)
{ 
	$num=rand(0,$l); 
	
	$authnum.= $str[$num]; 
}

$_SESSION['code']=$authnum;
ob_clean();
Header("Content-type: image/PNG");

srand((double)microtime()*1000000);

$im = imagecreate(70,20);

$black = ImageColorAllocate($im, 12,12,0);

$white = ImageColorAllocate($im, 0,255,0);

$gray = ImageColorAllocate($im, 200,200,200);

imagefill($im,78,30,$gray);

imagestring($im, 5, 6, 3, $authnum, $white);

for($i=0;$i<90;$i++) 
{
	imagesetpixel($im, rand()%70 , rand()%30 , $gray);
}

ImagePNG($im);
ImageDestroy($im);

?>

