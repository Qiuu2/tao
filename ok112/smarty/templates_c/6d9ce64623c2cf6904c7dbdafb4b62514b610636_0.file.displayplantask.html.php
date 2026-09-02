<?php
/* Smarty version 3.1.30, created on 2026-05-25 14:53:45
  from "/var/www/html/ok112/smarty/templates/BellManager/displayplantask.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a13f1f9edb9f5_85645923',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '6d9ce64623c2cf6904c7dbdafb4b62514b610636' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/BellManager/displayplantask.html',
      1 => 1778116056,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:BellManager/displayplantask_form.html' => 1,
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a13f1f9edb9f5_85645923 (Smarty_Internal_Template $_smarty_tpl) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>BellManager</title>
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
                dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Sunday'];?>
&nbsp;";
                break;
                case 1:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Monday'];?>
&nbsp;";
                break;
                case 2:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Tuesday'];?>
&nbsp;";
                break;
                case 3:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Wednesday'];?>
&nbsp;";
                break;
                case 4:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Thursday'];?>
&nbsp;";
                break;
                case 5:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Friday'];?>
&nbsp;";
                break;
                case 6:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Saturday'];?>
&nbsp;";
                break;
                  
            }
        }
   }
   if(count==7)
   {
   		return "<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Every_day'];?>
";
   }
 return dayofweek;
}
function displayonetaskterminalinfo(taskid)
{
	window.location.href = "displayplanonetaskterminal.php?taskid="+taskid+"";
}
<?php echo '</script'; ?>
>
</head>
<body>	
<?php $_smarty_tpl->_subTemplateRender("file:BellManager/displayplantask_form.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
 
<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

</body>
</html>
<?php }
}
