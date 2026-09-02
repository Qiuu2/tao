<?php
/* Smarty version 3.1.30, created on 2026-05-25 16:12:20
  from "/var/www/html/ok112/smarty/templates/LogManager/tasklogmanager.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a140464d9b3b1_62841304',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '08f064545686e38bd4f2c895c922e36038f2cdd8' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/LogManager/tasklogmanager.html',
      1 => 1778116079,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:LogManager/tasklogmanager_form.html' => 1,
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a140464d9b3b1_62841304 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
<title>DisplayTerminal</title>
<link href="skin/css/main_page_style.css" rel="stylesheet" type="text/css" />
<style>
.backup_overlay
{
   display: none;
   position: absolute;
   top: 0%;
   left: 0%;
   width: 100%;
   height: 100%;
   background-color: #eeeeff;
   z-index:1001;
   filter: alpha(opacity=90);
}
.backup_content
{
   display: none;
   position: absolute;
   top: 25%;
   left: 25%;
   width: 50%;
   height: 50%;
   padding: 0px;
   z-index:1002;
   overflow: hidden;
   text-align : center ;
}
.backup_title
{
  font-size:13px;
  font-family:Georgia, "Times New Roman", Times, serif, "����"; 
  background-color : #6699dd; 
  text-align:left;
  color:#FFFFFF;
}
.backup_table
{
   font-size:12px;
   font-family:Georgia, "Times New Roman", Times, serif, "����";
   background-color:#FFFFFF;
   border:1px solid #336699;
   margin:0px;
   padding:0;
}
.backup_button
{
    border:1px solid #ddddff
}

 
 /* 奇数行样式 */
 tr:nth-child(odd) {
    background-color: #ffffff;
 }
 
 /* 偶数行样式 */
 tr:nth-child(even) {
    background-color: #c1cfe0;
 }

</style>
<?php echo '<script'; ?>
 language="javascript">

function delLog()
{
/*
	var qstr=getCheckboxItem();
	if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['select_log']);?>
");
		return void(0);	
	}
	else
	{
		if(!window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['del_log']);?>
"))
		{
				return void(0);
		}	
		else
		{
				location="do.php?act=tasklogdel_msg&filename="+qstr+"";
		}
	}
	*/
	if(!window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['del_log']);?>
"))
		{
				return void(0);
		}	
		else
		{
				location="do.php?act=tasklogdel_msg";
		}
}
<?php echo '</script'; ?>
>


</head>
<body>	
<?php $_smarty_tpl->_subTemplateRender("file:LogManager/tasklogmanager_form.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
 
<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

</body>
</html><?php }
}
