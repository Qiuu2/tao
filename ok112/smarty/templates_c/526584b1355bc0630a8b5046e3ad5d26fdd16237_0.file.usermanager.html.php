<?php
/* Smarty version 3.1.30, created on 2026-07-06 14:06:31
  from "/var/www/html/ok112/smarty/templates/UserManager/usermanager.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a4b45e709d7e1_06016888',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '526584b1355bc0630a8b5046e3ad5d26fdd16237' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/UserManager/usermanager.html',
      1 => 1778116113,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:UserManager/usermanager_form.html' => 1,
    'file:language/chinese_foot.php' => 1,
  ),
),false)) {
function content_6a4b45e709d7e1_06016888 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<title>Usermanager</title>

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
 type="text/javascript" src="skin/erweima/jquery.min.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 type="text/javascript" src="skin/erweima/qrcode.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 type="text/javascript">

function isNull( str )
{
	if ( str == "" || str==null) 
	return true;
	var regu = "^[ ]+$";
	var re = new RegExp(regu);
	return re.test(str);
}

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
				alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_manager']->value['select_one']);?>
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
	if( isNull(allSel) )
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_manager']->value['Select_Users']);?>
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
function EnableUser()
{
	var qstr=getCheckboxItem();
	if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_manager']->value['Select_Users']);?>
");
		return void(0);	
	}
	else
	{ 
			location="do.php?act=enable_msg&id="+qstr+"";
	}
}
function DisableUser()
{
	var qstr=getCheckboxItem();
	if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_manager']->value['Select_Users']);?>
");
		return void(0);	
	}
	else
	{ 
			location="do.php?act=disable_msg&id="+qstr+"";
	}

}
function delUser()
{
	var qstr=getCheckboxItem();
	if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_manager']->value['Select_Users']);?>
");
		return void(0);	
	}
	else
	{ 
		if(!window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_manager']->value['confirm_deleting']);?>
"))
		{
			return void(0);
		}	
		else
		{
			location="do.php?act=userdel_msg&id="+qstr+"";
		}
	}
}

function usermodify()
{
	var getid = getoneitem();
	if(isNull(getid))
	{
		return void(0);
	}
	else
	{
		window.location.href="useredit.php?id="+getid+"";
	}
}
function view_user_terminal()
{
	var user_id = getoneitem();
	if(isNull(user_id))
	{
		return void(0);
	}
	else
	{
		window.location.href = "view_user_terminal.php?id="+user_id+"";
	}
}


<?php echo '</script'; ?>
>
</head>
<body>
<?php $_smarty_tpl->_subTemplateRender("file:UserManager/usermanager_form.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>

<?php $_smarty_tpl->_subTemplateRender("file:language/chinese_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
   
</body>
</html>
<?php }
}
