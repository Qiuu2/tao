<?php
/* Smarty version 3.1.30, created on 2026-05-25 16:17:25
  from "/var/www/html/ok112/smarty/templates/zhaoshengManager/sounddevice.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a140595a1bda3_02257292',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'f3a782ba98c6aecf23c7844ed43f1f84a6d3e989' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/zhaoshengManager/sounddevice.html',
      1 => 1778116117,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:zhaoshengManager/sounddevice_form.html' => 1,
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a140595a1bda3_02257292 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $_smarty_tpl->tpl_vars['Streammanager']->value['Partition_Manager'];?>
</title>
<?php echo '<script'; ?>
 src="skin/js/frame/sort_data_table.js" type="text/javascript"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="skin/js/frame/jzdd.js" type="text/javascript"><?php echo '</script'; ?>
>
<link href="skin/css/main_page_style.css" rel="stylesheet" type="text/css" />
<style>
 
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
 type="text/javascript">
function getCheckboxItem()
{
	var allSel="";
	if(document.form2.id.checked)
	{
		allSel=document.form2.id.value;
		if(allSel==undefined)
		{
			allSel="";
		}
	}
	for(i=0;i<document.form2.id.length;i++)
	{
		if(document.form2.id[i].checked)
		{
			if(allSel=="")
				allSel=document.form2.id[i].value;
			else
				allSel=allSel+","+document.form2.id[i].value;
		}
	}
	return allSel;
}
function getoneitem()
{
	var allSel="";
	var count=0;
	if(document.form2.id.checked)
	{
		allSel=document.form2.id.value;
		if(allSel==undefined)
		{
			allSel="";
		}
	}
	for(var i=0;i<document.form2.id.length;i++)
	{
		if(document.form2.id[i].checked)
		{	
			count++;
			if(count>=2)
			{
				alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Only_select_one']);?>
");
				return void(0);
			}
		}
	}
	
	for(var i=0;i<document.form2.id.length;i++)
	{
		if(document.form2.id[i].checked)
		{	
			allSel=document.form2.id[i].value;
			break;
		}
	}
	if(allSel=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['select_area']);?>
");
		return void(0);
	}
	return allSel;	
}

function selAll(aid)
{
	if(aid==0)
	{
		document.form2.id.checked=true;
	}
	for(i=0;i<document.form2.id.length;i++)
	{
		if(!document.form2.id[i].checked)
		{
			document.form2.id[i].checked=true;
		}
	}
}

function noSelAll(aid)
{
	if(aid==0)
	{
		document.form2.id.checked=false;
	}
	for(i=0;i<document.form2.id.length;i++)
	{
		if(document.form2.id[i].checked)
		{
			document.form2.id[i].checked=false;
		}
	}
}

function delStream()
{
	var qstr=getCheckboxItem();

	if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['select_area']);?>
");
		return void(0);	
	}
	else
	{
		if(!window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Determine_remove']);?>
"))
		{
			return void(0);
		}	
		else
		{
			location="do.php?act=soundsdevicedel_msg&id="+qstr+"";
		}
	}
}

function modifyStream()
{
	var qstr=getoneitem();
	if(qstr==null||qstr=="")
	{
		return void(0);	
	}
	else
	{
		location.href="zhaoshengdeviceedit.php?id="+qstr+"";
	}
}
<?php echo '</script'; ?>
>
</head>
<body>
<?php $_smarty_tpl->_subTemplateRender("file:zhaoshengManager/sounddevice_form.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
 
<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>
 
</body>
</html><?php }
}
