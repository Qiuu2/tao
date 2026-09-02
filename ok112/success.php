<?php 
	if (!session_id()) session_start();
	header("content-type:text/html;charset=utf-8");
	//require_once("language/".$_SESSION['language'].".php");
	require_once("language/chinese.php");
	
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo strtoupper($operate_prompt['Successed']); ?></title>
<meta http-equiv="refresh" content="5;URL=<?=$_SESSION['url']?>">
<link href="skin/css/main_page_style.css" rel="stylesheet" type="text/css" />
</head>

<body>
<table width="400" border="0" align="center" cellpadding="5" cellspacing="1" bgcolor="#E8E8E8" style="margin-top:50px;border:#7E98C0 solid 1px;" >
  <tr>
    <td background="images/xxbg.jpg" style="border-bottom:#7E98C0 solid 1px;">
		<strong><?php echo strtoupper($operate_prompt['Note']); ?></strong>
	</td>
  </tr>
  <tr>
    <td bgcolor="#FFFFFF"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="50" align="center">
			<img src="skin/images/frame/success.jpg" width="32" height="30" align="absmiddle">
			<?=$_SESSION['info']?></td>
        </tr>
		<tr>
			<td height="20" align="center">
				<p>
				<?php 
					if(empty($_SESSION['url']))
					{
				?>
					<A href="login.php" target="_self"><?php echo $operate_prompt['Click_here']; ?></A>
				<?php
					}
					else
					{
				?>	
					<A href="<?=$_SESSION['url']?>" target="_self"><?php echo $operate_prompt['Click_here']; ?></A>
				<?php
					}
				?>
				</p>
			</td>
		</tr>
    </table>

	</td>
  </tr>
</table>
</body>
</html>
