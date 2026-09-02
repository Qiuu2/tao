<?php
/* Smarty version 3.1.30, created on 2026-05-25 16:17:28
  from "/var/www/html/ok112/smarty/templates/zhaoshengtask/FileTaskManager.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a140598eda107_99457341',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '8fc8cc9fd2218728edced4bdc4395b6cef97847e' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/zhaoshengtask/FileTaskManager.html',
      1 => 1778116119,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:zhaoshengtask/FileTaskManager_from.html' => 1,
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a140598eda107_99457341 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<html>
<head>
<title>FileAdmManager</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<?php echo '<script'; ?>
 src="skin/js/frame/sort_data_table.js" type="text/javascript"><?php echo '</script'; ?>
>
<link href="skin/css/main_page_style.css" rel="stylesheet" type="text/css" />
<?php echo '<script'; ?>
 src="skin/js/frame/jzdd.js" type="text/javascript"><?php echo '</script'; ?>
>
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

function trim(str)
{
   str=str.replace(/(^\s*)|(\s*$)/g,""); 
   return str;
}
function startFileTask()
{
	var qstr=getCheckboxItem();
	var gettask=<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
;
	var userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
;
	if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['select_broadcast_task']);?>
");
		return void(0);	
	}
	else
	{
		location="do.php?act=zhaoshentaskstart_msg&id="+qstr+"&gettask="+gettask+"&userid="+userid+"";
	}	
}
function stopFileTask()
{
	var qstr=getCheckboxItem();
	var gettask=<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
;
	var userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
;
	if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['select_broadcast_task']);?>
");
		return void(0);	
	}
	else
	{
		if(!window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['suspending_task']);?>
"))
		{
			return void(0);
		}	
		else
		{
			location="do.php?act=zhaoshentaskstop_msg&id="+qstr+"&gettask="+gettask+"&userid="+userid+"";
		}
	}
}


function pauseFileTask()
{
	var qstr=getCheckboxItem();
	var gettask=<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
;
	var userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
;
	if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['select_broadcast_task']);?>
");
		return void(0);	
	}
	else
	{
		if(!window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['pausesing_task']);?>
"))
		{
			return void(0);
		}	
		else
		{
			location="do.php?act=filetaskpause_msg&id="+qstr+"&gettask="+gettask+"&userid="+userid+"";
		}
	}
}

function huifuFileTask()
{
	var qstr=getCheckboxItem();
	var gettask=<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
;
	var userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
;
	if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['select_broadcast_task']);?>
");
		return void(0);	
	}
	else
	{
		if(!window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['huifuing_task']);?>
"))
		{
			return void(0);
		}	
		else
		{
			location="do.php?act=filetaskhuifu_msg&id="+qstr+"&gettask="+gettask+"&userid="+userid+"";
		}
	}
}


function modifyFileTask()
{
	var getid=getCheckboxItem();
	var gettask=<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
;
	var userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
;
	var count=0;
	for(i=0;i<document.form2.id.length;i++)
	{
		if(document.form2.id[i].checked)
		{
			count++;
			if(count>=2)
			{
				alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['only_select_one']);?>
");
				return void(0);
			}
		}
	}
	if(getid==""||getid==null)
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['select_broadcast_task']);?>
");
	}
	else
	{
		window.location.href = "zhaoshengtaskmodify.php?id="+getid+"&gettask="+gettask+"&userid="+userid+"";
	}
}
function delTask()
{
	var userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
;
	var qstr = getCheckboxItem();
	var gettask=<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
;

	if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['select_broadcast_task']);?>
");
		return void(0);	
	}
	else
	{
		if(!window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['confirm_deleting']);?>
"))
		{
				return void(0);
		}	
		else
		{
				window.location.href = "do.php?act=zhaoshengtaskdel_msg&id="+qstr+"&gettask="+gettask+"&userid="+userid+"";
		}
	}
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
function getdayofweek(str)
{
   var dayofweek="";
   var count=0;
   for(i=0;i<str.length;i++)
   {
        if(str.charAt(i)=="1")
        {
			count++;
            switch(i)
            {
                case 0:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Sunday'];?>
";
                
                break;
                case 1:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Monday'];?>
";
                
                break;
                case 2:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Tuesday'];?>
";
                
                break;
                case 3:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Wednesday'];?>
";
                
                break;
                case 4:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Thursday'];?>
";
                
                break;
                case 5:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Friday'];?>
";
                
                break;
                case 6:
                dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Saturday'];?>
";
                break;  
            }
        }
   }
   if(count==7)
   {
   		return "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['every_day']);?>
";
   }
   return dayofweek;
}



function set_task_volume()
{
	 var task_id = 0;
	 var db_value0 = document.getElementById('db_value0').value;
	 var db_value1 = document.getElementById('db_value1').value;
	 var db_value2 = document.getElementById('db_value2').value;
	 var db_value3 = document.getElementById('db_value3').value;
	 var db_value4 = document.getElementById('db_value4').value;
	 var db_value5 = document.getElementById('db_value5').value;
	ajax_set_task_volume("25",db_value0,db_value1,db_value2,db_value3,db_value4,db_value5,task_id);
}

function disappear_volume_div()
{
	if(document.getElementById('change_volume').style.display == "block")
	{
		document.getElementById('change_volume').style.display = "none";
	}
}

/******************
	启用文件广播
******************/
function start_file_task()
{
	var taskid = <?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
;
	var getid=getCheckboxItem();
	var userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
;
	if(getid==null||getid=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['select_broadcast_task']);?>
");
		return void(0);	
	}
	else
	{
		if(window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['enterenable']);?>
"))
			{
				location="do.php?act=start_zhaoshen_task_msg&id="+getid+"&taskid="+taskid+"&userid="+userid+"";
			}
			else
			{
				return void(0);
			}
	}	
}

/******************
	应用默认噪声值
******************/
function enabledefaultvolume()
{
	var taskid = <?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
;
	var getid=getCheckboxItem();
	var userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
;
	if(getid==null||getid=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['select_broadcast_task']);?>
");
		return void(0);	
	}
	else
	{
		location="do.php?act=enable_zhaoshen_volume_msg&id="+getid+"&taskid="+taskid+"&userid="+userid+"";
	}	
}


/*****************
	停用文件广播
*****************/
function stop_file_task()
{
	var taskid = <?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
;
	var getid=getCheckboxItem();
	var userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
;
	if(getid==null||getid=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['select_broadcast_task']);?>
");
		return void(0);	
	}
	else
	{	
		if(window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['enterdisable']);?>
"))
			{
		location = "do.php?act=stop_zhaoshen_task_msg&id="+getid+"&taskid="+taskid+"&userid="+userid+"";
			}
			else
			{
				return void(0);
			}
	}
}


function sortTables(col, dataType)
{
	window.location.href="taskmanager.php?col="+col+"&dataType="+dataType+"&id="+<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
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
<?php $_smarty_tpl->_subTemplateRender("file:zhaoshengtask/FileTaskManager_from.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>

<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>


</body>
</html>

















<?php }
}
