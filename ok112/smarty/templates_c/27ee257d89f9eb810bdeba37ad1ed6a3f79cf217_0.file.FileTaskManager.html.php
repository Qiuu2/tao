<?php
/* Smarty version 3.1.30, created on 2026-05-25 11:41:00
  from "/var/www/html/ok112/smarty/templates/FileAd/FileTaskManager.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a13c4cc369f40_78361951',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '27ee257d89f9eb810bdeba37ad1ed6a3f79cf217' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/FileAd/FileTaskManager.html',
      1 => 1778116067,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:FileAd/FileTaskManager_from.html' => 1,
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a13c4cc369f40_78361951 (Smarty_Internal_Template $_smarty_tpl) {
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
		location="do.php?act=filetaskstart_msg&id="+qstr+"&gettask="+gettask+"&userid="+userid+"";
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
			location="do.php?act=filetaskstop_msg&id="+qstr+"&gettask="+gettask+"&userid="+userid+"";
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
		window.location.href = "taskmodify.php?id="+getid+"&gettask="+gettask+"&userid="+userid+"";
	}
}

function copyFileTask()
{
	var userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
;
	var qstr = getCheckboxItem();
	var gettask=<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
;
	var taskname=document.getElementById('task_name').value;

	if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['select_broadcast_task']);?>
");
		return void(0);	
	}
	else
	{
	window.location.href = "do.php?act=copyFileTasks&id="+qstr+"&taskname="+taskname+"";

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
				window.location.href = "do.php?act=taskdel_msg&id="+qstr+"&gettask="+gettask+"&userid="+userid+"";
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

function set_copy()
{
	
	get_div_obj('copytask').style.display = "none";
	self.location.reload();	
}

function ajax_set_task_copy(taskcopyfrom,taskcopyto)
{
   djdd();
   createXMLHttpRequest();
   xmlhttp.open( "get","set_task_copy.php?taskcopyfrom="+taskcopyfrom+"&taskcopyto="+taskcopyto+"",true );
   xmlhttp.onreadystatechange = function()
   {

     // if( xmlhttp.readyState >1 )
    //  {
			
      //   if( xmlhttp.status == 200 )
       //  {
				
				 setTimeout("set_copy()", 3000);
				 //alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['success']);?>
");	
	//	}
    //  }
   }
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	xmlhttp.send(null);
}
function set_task_copy()
{
	document.getElementById('enterbutton').disabled=true;
	
	var taskcopyfrom=document.getElementById('getcopytaskfrom').value;
	var taskcopyto=document.getElementById('getcopytaskto').value;
	if(taskcopyfrom!=""&&taskcopyto!="")
	ajax_set_task_copy(taskcopyfrom,taskcopyto);
	else
	alert('Setting failed');
}

function set_task_volume()
{
	var task_id = getCheckboxItem();
	if(navigator.appName.indexOf("Explorer") > -1)        
	 var volume_value = document.getElementById('d1').innerText;
	 else
	 var volume_value = document.getElementById('d1').textContent;
	ajax_set_task_volume("2",volume_value,task_id);
}
function disappear_task_div()
{
	if(document.getElementById('copytask').style.display == "block")
	{
		document.getElementById('copytask').style.display = "none";
	}
}

function disappear_taskfile_div()
{
	if(document.getElementById('copytaskfile').style.display == "block")
	{
		document.getElementById('copytaskfile').style.display = "none";
	}
}

function disappear_volume_div()
{
	if(document.getElementById('change_volume').style.display == "block")
	{
		document.getElementById('change_volume').style.display = "none";
	}
}

function emergency_set()
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
		
		window.location.href = "do.php?act=emergency_setting&id="+getid+"&gettask="+gettask+"&userid="+userid+"";
	}	
}

function emergency_cancel()
{
	var gettask=<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
;
	var userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
;
	window.location.href = "do.php?act=emergency_canceling&gettask="+gettask+"&userid="+userid+"";
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
	if(taskid==null&&getid==null)
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
			location="do.php?act=start_file_task_msg&id="+getid+"&taskid="+taskid+"&userid="+userid+"";
			}
			else
			{
				return void(0);
			}
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
	if(taskid==null&&getid==null)
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
			location = "do.php?act=stop_file_task_msg&id="+getid+"&taskid="+taskid+"&userid="+userid+"";
		}
		else
		{
			return void(0);
		}
	}
}
function addfolder(obj,get_folder_id)
{
	var sign = "<?php echo $_smarty_tpl->tpl_vars['sign']->value;?>
";
	var userid = "<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
";
	
	if(sign == 1)
	{
		window.location.href="filefolderdo.php?sign=add&id="+get_folder_id+"&userid="+userid+"";
	}
	else if(sign==200)
	{
		window.location.href="filefolderdo.php?sign=add&id="+get_folder_id+"&userid="+userid+"";
	}
	else
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['sign']);?>
");
	}
}
function delfolder(obj,get_folder_id)
{ 
   if(!window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['confirm_deleting']);?>
"))
	{
		return void(0);
	}	
	else
	{
		location="do.php?act=taskfolderdel_msg&id="+get_folder_id+"";
	}
}

function modifyfolder(obj,get_folder_id)
{
	
	window.location.href="filefolderdo.php?sign=modify&id="+get_folder_id+"";
		
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
<?php $_smarty_tpl->_subTemplateRender("file:FileAd/FileTaskManager_from.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>

<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

</body>
</html>

















<?php }
}
