<?php
/* Smarty version 3.1.30, created on 2026-05-25 11:51:01
  from "/var/www/html/ok112/smarty/templates/AdmManger/AdmManager.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a13c7257e79e2_22820697',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'd53328b35fb6d12faef4b35136561c1a93a4e0d8' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/AdmManger/AdmManager.html',
      1 => 1778116039,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:AdmManger/AdmManger_form.html' => 1,
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a13c7257e79e2_22820697 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>AdmManager</title>
<?php echo '<script'; ?>
 src="skin/js/frame/sort_data_table.js" type="text/javascript"><?php echo '</script'; ?>
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
 language="javascript">
function addTask()
{
	window.location.href="admaddtask.php";
}

function modifyAdmTask()
{
	var getItem=getCheckboxItem();
	var count=0;
	for(i=0;i<document.fileAdvForm.id.length;i++)
	{
		if(document.fileAdvForm.id[i].checked)
		{
			count++;
			if(count>=2)
			{
				alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['only_select_one']);?>
");
				return void(0);
			}
		}
	}
	if(getItem==null||getItem=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['select_broadcast_task']);?>
");
		return void(0);
	}
	else
	{
		window.location.href="admmodify.php?id="+getItem+"";
	}
}
function startTask(){
	var getItem;
	getItem=getCheckboxItem();
	if(getItem==null||getItem=="")
	{
		window.alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['select_broadcast_task']);?>
");
		return void(0);
	}
	else
	{
		window.location.href="do.php?act=admtaskstart_msg&id="+getItem+"";
	}
}

function delTask()
{
	var getItem = getCheckboxItem();
	
	if(getItem==null||getItem=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['select_broadcast_task']);?>
");
		return void(0);
	}
	else
	{
		if(confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['confirm_deleting']);?>
"))
		{
			window.location.href="do.php?act=admtaskdel_msg&id="+getItem+"";
		}
		else
		{
			return void(0);
		}
	}
}
function stopTask()
{
	var getItem=getCheckboxItem();
	if(getItem==null||getItem=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['select_broadcast_task']);?>
");
		return void(0);
	}
	else
	{
		if(window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['suspending_task']);?>
"))
		{
			window.location.href="do.php?act=admtaskstop_msg&id="+getItem+"";
		}
		else
		{
			return void(0);
		}
	}
}

function getCheckboxItem()
{
	var allSel="";
	if(document.fileAdvForm.id.checked)
	{
		allSel=document.fileAdvForm.id.value;
		if(allSel==undefined)
		{
			allSel="";
		}
	}
	for(i=0;i<document.fileAdvForm.id.length;i++)
	{
		if(document.fileAdvForm.id[i].checked)
		{
			if(allSel=="")
				allSel=document.fileAdvForm.id[i].value;
			else
				allSel=allSel+","+document.fileAdvForm.id[i].value;
		}
	}
	return allSel;
}

function selAll(aid)
{
	if(aid==0)
	{
		document.fileAdvForm.id.checked=true;
	}
	for(i=0;i<document.fileAdvForm.id.length;i++)
	{
		if(!document.fileAdvForm.id[i].checked)
		{
			document.fileAdvForm.id[i].checked=true;
		}
	}
}//全选
function noSelAll(aid)
{
	if(aid==0)
	{
		document.fileAdvForm.id.checked=false;
	}
	for(i=0;i<document.fileAdvForm.id.length;i++)
	{
		if(document.fileAdvForm.id[i].checked)
		{
			document.fileAdvForm.id[i].checked=false;
		}
	}
}

function getdayofweek(str)
{
   var dayofweek="";
   var count = 0;
   for(i=0;i<str.length;i++)
   {
        if(str.charAt(i)=="1")
        {
			count++;
            switch(i)
            {
                case 0:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['Sunday'];?>
";
                break;
                case 1:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['Monday'];?>
";
                break;
                case 2:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['Tuesday'];?>
";
                break;
                case 3:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['Wednesday'];?>
";
                break;
                case 4:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['Thursday'];?>
";
                break;
                case 5:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['Friday'];?>
";
                break;
                case 6:
                dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['Saturday'];?>
";
                break;
            }
        }
   }
   if(count==7)
   {
   		return "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['every_day']);?>
";
   }
   return dayofweek;
}

function set_task_volume()
{
	var task_id = getCheckboxItem();
	//var volume_value = trim(document.getElementById('d1').innerHTML);
	if(navigator.appName.indexOf("Explorer") > -1)        
	 var volume_value = document.getElementById('d1').innerText;
	 else
	 var volume_value = document.getElementById('d1').textContent;
	ajax_set_task_volume("3",volume_value,task_id);
}
function disappear_volume_div()
{
	if(document.getElementById('change_volume').style.display == "block")
	{
		document.getElementById('change_volume').style.display = "none";
	}
}
/******************
	启用广播
******************/
function enableTask()
{
	var getid=getCheckboxItem();

	if(getid==null)
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['select_broadcast_task']);?>
");
		return void(0);	
	}
	else
	{
		if(window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['enterenable']);?>
"))
		{
			window.location.href ="do.php?act=enableTask&id="+getid+"";
		}
		else
		{
			return void(0);
		}
	}	
}
/*****************
	停用广播
*****************/
function disableTask()
{
	var getid=getCheckboxItem();
	if(getid==null)
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['select_broadcast_task']);?>
");
		return void(0);	
	}
	else
	{	
		if(window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['enterdisable']);?>
"))
		{
			window.location.href = "do.php?act=disableTask&id="+getid+"";
		}
		else
		{
			return void(0);
		}
	}
}

<?php echo '</script'; ?>
>
</head>
<body>
  <?php $_smarty_tpl->_subTemplateRender("file:AdmManger/AdmManger_form.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>

  <?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

</body>
</html>
<?php }
}
