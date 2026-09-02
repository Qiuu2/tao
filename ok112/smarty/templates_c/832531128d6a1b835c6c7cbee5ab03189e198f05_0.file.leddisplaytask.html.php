<?php
/* Smarty version 3.1.30, created on 2026-05-26 12:40:39
  from "/var/www/html/ok112/smarty/templates/ledmanager/leddisplaytask.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a1524479e7867_61678734',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '832531128d6a1b835c6c7cbee5ab03189e198f05' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/ledmanager/leddisplaytask.html',
      1 => 1778116078,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a1524479e7867_61678734 (Smarty_Internal_Template $_smarty_tpl) {
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
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

<frameset cols="140,*" name="btFrame" frameborder="yes" border="1" framespacing="1">
    <frame src="ledmanagertree.php" name="mediafolder" frameborder="no" scrolling="auto"  marginwidth="0" id="mediafolder">
    <frame src="ledtaskmanager.php" name="mediafile" id="mediafile" scrolling="auto">
</frameset>

<noframes>
	<body>NOT Support FrameSet</body>
</noframes>
</html><?php }
}
