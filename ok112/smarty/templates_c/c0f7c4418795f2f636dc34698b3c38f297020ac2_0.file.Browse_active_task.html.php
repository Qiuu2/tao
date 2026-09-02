<?php
/* Smarty version 3.1.30, created on 2026-07-06 15:51:21
  from "/var/www/html/ok112/smarty/templates/Browse_active_task/Browse_active_task.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a4b5e79881623_31406438',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'c0f7c4418795f2f636dc34698b3c38f297020ac2' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/Browse_active_task/Browse_active_task.html',
      1 => 1778116057,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:Browse_active_task/Browse_active_task_form.html' => 1,
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a4b5e79881623_31406438 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<title>TerminalFunctionPlayManager</title>

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



//获得选中文件的文件名
function getCheckboxItem()
{
	var allSel="";
	if(document.terminalfunctionplayform.id.checked)
	{
		allSel=document.terminalfunctionplayform.id.value;
		if(allSel==undefined)
		{
			allSel="";
		}
	}
	for(i=0;i<document.terminalfunctionplayform.id.length;i++)
	{
		if(document.terminalfunctionplayform.id[i].checked)
		{
			if(allSel=="")
				allSel=document.terminalfunctionplayform.id[i].value;
			else
				allSel=allSel+","+document.terminalfunctionplayform.id[i].value;
		}
	}
	return allSel;
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
				case 8:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['NextMonday'];?>
";
                break;
				case 9:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['NextTuesday'];?>
";
                break;
				case 10:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['NextWednesday'];?>
";
                break;
				case 11:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['NextThursday'];?>
";
                break;
				case 12:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['NextFriday'];?>
";
                break;
				case 13:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['NextSaturday'];?>
";
                break;
				case 14:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Admmanager']->value['NextSunday'];?>
";
                break;
            }
        }
   }
   if(count>6)
   {
   		return "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['every_day']);?>
";
   }
   return dayofweek;
}

function pause_curr_active_task(curr_active_task_id)
{
	window.location.href = "do.php?act=stop_curr_tast_state&taskid="+curr_active_task_id+"";
}

function start_curr_active_task(curr_active_task_id)
{

	window.location.href = "do.php?act=start_curr_tast_state&taskid="+curr_active_task_id+"";
}


function enordis_week_date_task(int_date)
{
 var getymd = "<?php echo $_smarty_tpl->tpl_vars['get_ymd']->value;?>
";
var qstr=getCheckboxItem();

if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['select_task']);?>
");
		
		return void(0);	
	}
	else
	{
			if(int_date=='0')
			{
			
				window.location.href = "do.php?act=enordis_date_task&get_str_date="+<?php echo $_smarty_tpl->tpl_vars['get_str_date']->value;?>
+"&get_int_date="+<?php echo $_smarty_tpl->tpl_vars['get_int_date']->value;?>
+"&en_dis=0&getdate="+getymd+"&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
+"&taskid="+qstr;
			}
			else if(int_date=='1')
			{	
				window.location.href = "do.php?act=enordis_date_task&get_str_date="+<?php echo $_smarty_tpl->tpl_vars['get_str_date']->value;?>
+"&get_int_date="+<?php echo $_smarty_tpl->tpl_vars['get_int_date']->value;?>
+"&en_dis=1&getdate="+getymd+"&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
+"&taskid="+qstr;
			}
	}
}


<?php echo '</script'; ?>
>

</head>

<body >

<?php $_smarty_tpl->_subTemplateRender("file:Browse_active_task/Browse_active_task_form.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
 

<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>
 

</body>
</html>
<?php }
}
