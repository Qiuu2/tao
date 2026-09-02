<?php
/* Smarty version 3.1.30, created on 2026-05-25 14:01:00
  from "/var/www/html/ok112/smarty/templates/FileManager/display_folder_and_media.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a13e59c55e038_62825930',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'f7d4cbf1b71b8594521726089738e6da1b4cf4e1' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/FileManager/display_folder_and_media.html',
      1 => 1778116067,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a13e59c55e038_62825930 (Smarty_Internal_Template $_smarty_tpl) {
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
<title>mediaoffolder</title>
<style>
body
{
  scrollbar-base-color:#C0D586;
  scrollbar-arrow-color:#FFFFFF;
  scrollbar-shadow-color:DEEFC6;
}
</style>

</head>

<frameset cols="200,*" name="btFrame" frameborder="yes" border="1" framespacing="1">
    <frame src="media_folder_tree.php" name="mediafolder" frameborder="no" scrolling="auto"  marginwidth="0" id="mediafolder">
    <frame src="media_file.php" name="mediafile" id="mediafile" scrolling="auto" marginwidth="0">
</frameset>

<noframes>
	<body>NOT Support FrameSet</body>
</noframes>
</html><?php }
}
