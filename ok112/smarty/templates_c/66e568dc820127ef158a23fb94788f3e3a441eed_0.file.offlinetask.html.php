<?php
/* Smarty version 3.1.30, created on 2026-05-26 16:00:44
  from "/var/www/html/ok112/smarty/templates/offlinetask/offlinetask.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a15532c8b6956_47131202',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '66e568dc820127ef158a23fb94788f3e3a441eed' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/offlinetask/offlinetask.html',
      1 => 1778116080,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:offlinetask/offlinetask_form.html' => 1,
    'file:language/chinese_foot.php' => 1,
  ),
),false)) {
function content_6a15532c8b6956_47131202 (Smarty_Internal_Template $_smarty_tpl) {
?>
<html>
<head>
<title>FileAdmManager</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<?php echo '<script'; ?>
 src="skin/js/frame/sort_data_table.js" type="text/javascript"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="skin/js/frame/jzdd.js" type="text/javascript"><?php echo '</script'; ?>
>
<link href="skin/css/main_page_style.css" rel="stylesheet" type="text/css" />
<style>
 
	table {
		width: 100%;
		border-collapse: collapse;
		margin-top: 20px;
	}
	
	
	
	td {
		padding: 5px;
		
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

function trim(str)
{
   str=str.replace(/(^\s*)|(\s*$)/g,""); 
   return str;
}

//获得选中文件的文件名
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
	document.form2.id.checked=false;
	
	for(i=0;i<document.form2.id.length;i++)
	{
		if(document.form2.id[i].checked)
		{
			document.form2.id[i].checked=false;
		}
	}
}

function sortTables(col, dataType)
{
//	window.location.href="taskmanager.php?col="+col+"&dataType="+dataType+"&id="+<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
+"&page="+<?php echo $_smarty_tpl->tpl_vars['start']->value;?>
+"";
}

<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 language="javascript" src="smarty/templates/ajax/synchronization.js"><?php echo '</script'; ?>
>

</head>
<body onLoad="reloadpage()"> 
<?php $_smarty_tpl->_subTemplateRender("file:offlinetask/offlinetask_form.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>

<?php $_smarty_tpl->_subTemplateRender("file:language/chinese_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>

</body>
</html>

















<?php }
}
