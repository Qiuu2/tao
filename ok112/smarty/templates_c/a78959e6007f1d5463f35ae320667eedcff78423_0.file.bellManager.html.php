<?php
/* Smarty version 3.1.30, created on 2026-07-06 15:50:19
  from "/var/www/html/ok112/smarty/templates/BellManager/bellManager.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a4b5e3b8d3b17_28286630',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'a78959e6007f1d5463f35ae320667eedcff78423' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/BellManager/bellManager.html',
      1 => 1778116047,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:BellManager/bellManager_form.html' => 1,
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a4b5e3b8d3b17_28286630 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
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
  font-family:Georgia, "Times New Roman", Times, serif, "宋体"; 
  background-color : #6699dd; 
  text-align:left;
  color:#FFFFFF;
}
.backup_table
{
   font-size:12px;
   font-family:Georgia, "Times New Roman", Times, serif, "宋体";
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
>
function checkform()
{
	

}
var str_temp_txt = "";//保存字符
var str_temp_count = 0;//计数
var inver_time_handle = "";//返回句柄
var temp_txt = "";//设置备份和还原

function colose_backup()
{
    document.getElementById('light').style.display='none';
    document.getElementById('fade').style.display='none';
}
function open_backup()
{
	document.getElementById('backup_name').disabled = false;
	document.getElementById('prompt').innerHTML = "<?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['backup_name_default'];?>
";
    document.getElementById('light').style.display='block';
    document.getElementById('fade').style.display='block';
}
//还原
function colose_restore()
{
    document.getElementById('restore_light').style.display='none';
    document.getElementById('restore_fade').style.display='none';
}
function open_restore()
{
    document.getElementById('upfile').disabled = false;
	document.getElementById('prompt1').innerHTML = "<?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['restore_name_default'];?>
";
    document.getElementById('restore_light').style.display='block';
    document.getElementById('restore_fade').style.display='block';
}
function isNumberOr_Letter( s )
{
   //判断是否是数字或字母
   var regu = "^[0-9a-zA-Z\_]+$";
   var re = new RegExp(regu);
   if (re.test(s))
   {
      return true;
   }
   else
   {
      return false;
   }
}
function isNull( str )
{
   if ( str == "" ) return true;
   var regu = "^[ ]+$";
   var re = new RegExp(regu);
   return re.test(str);
}
function trim(str)
{
   str=str.replace(/(^\s*)|(\s*$)/g,""); 
   return str;
}

//备份数据
function validate_backup_name(input_str)
{
	var input_obj = document.getElementById(input_str);
	var str = trim(input_obj.value);
	var url = "";
	if(isNull(str))
	{
		 window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['backup_name']);?>
");
		 return void(0);
	
	}
	else
	{
		if(!isNumberOr_Letter(str))
		{
			document.getElementById('prompt').innerHTML = "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['correct_name']);?>
";
			return void(0);
		}
		else
		{
			temp_txt = "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['backuping']);?>
";
			animation_text_start();
			var backup_name = str;
			url = "backup_restore_form.php?backup_name="+str+"";
			
			backup_data(url);
			input_obj.disabled = true;
	        document.getElementById('light').style.display='none';
            document.getElementById('fade').style.display='none';
			alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['back_success']);?>
");
			
			window.location.href = "download_bellManager.php?backup_name="+str+"";

			
		}
	}
	
	//self.location.reload();
	

	
	
}

//还原数据

function restore_backup_data(url)
{
	createXMLHttpRequest();
	xmlhttp.open( "get",url, true );
	xmlhttp.onreadystatechange = function()
	{
		if( xmlhttp.readyState == 4 )
		{
			if( xmlhttp.status == 200 )
			{
				switch(xmlhttp.responseText)
				{
					case "0":
						clearInterval(inver_time_handle);
						alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['restore_failed']);?>
");
						colose_restore();
					break;
					
					case "1":
						clearInterval(inver_time_handle);
						alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['restore_success']);?>
");
						colose_restore();
					break;
					case "2":
						clearInterval(inver_time_handle);
						alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['restore_failed']);?>
");
						colose_restore();
					break;
					
				}
			}
		}
	}
	
	xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	xmlhttp.send(null);
}

function restore_data(restore_name)
{
	var input_obj = document.getElementById(restore_name);
	var str = trim(input_obj.value);
	var url = "";
    if(isNull(str))
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['select_files']);?>
');
		return void(0);
	}
	else
	{
	       
			temp_txt = "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['backuping']);?>
";
			
			animation_text_start();
			var backup_name = str;
		
			url = "bell_manager_restore.php?backup_name="+str+"";
		    
			restore_backup_data(url);
			input_obj.disabled = true;
}
	
}



<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
>
var xmlhttp=null; 
function createXMLHttpRequest()
{ 
	if(window.ActiveXObject)
	{ 
		xmlhttp = new ActiveXObject("microsoft.XMLHTTP"); 
	} 
	else if(window.XMLHttpRequest)
	{ 
		xmlhttp = new XMLHttpRequest(); 
	}
	else
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['Not_support_ajax']);?>
');
	}
} 
//备份数据
function backup_data(url)
{
	createXMLHttpRequest();
	xmlhttp.open( "get",url, true );
	xmlhttp.onreadystatechange = function()
	{
	  if( xmlhttp.readyState == 4 )
	  {
		 if( xmlhttp.status == 200 )
		 {
			switch(xmlhttp.responseText)
			{
				case "0":
					document.getElementById('prompt').innerHTML = "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['backup_failed']);?>
";
					document.getElementById('backup_name').disabled = false;
					clearInterval(inver_time_handle);
				break;
				
				case "1":
					document.getElementById('prompt').innerHTML = "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Backup_success']);?>
";
					document.getElementById('backup_name').disabled = false;
					clearInterval(inver_time_handle);
					//self.location.reload();
					
				break;
				
				case "2":
					document.getElementById('prompt').innerHTML = "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['same_name']);?>
";
					document.getElementById('backup_name').disabled = false;
					clearInterval(inver_time_handle);
				break;
				
				case "3":
					document.getElementById('prompt').innerHTML = "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Not_Data']);?>
";
					document.getElementById('backup_name').disabled = false;
					clearInterval(inver_time_handle);
				break;
			}
		 }
	  }
	}
	xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	xmlhttp.send(null);
}
<?php echo '</script'; ?>
>


<?php echo '<script'; ?>
  language="javascript">
function addbell()
{
	location="belladd.php";
}

function startbelling()
{
	var getItem = "";
	getItem=getCheckboxItem();
	if(getItem==null||getItem=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['select_Bell_Scheme']);?>
");
		return void(0);
	}
	else
	{
		var getitemarray = getItem.split(",");
		if(getitemarray.length >= 2)
		{
			alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['only_select_one']);?>
");
			return void(0);
		}
		else
		{
			if(window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['enterenable']);?>
"))
			{
				location="do.php?act=bellstart_msg&id="+getItem+"";
			}
			else
			{
				return void(0);
			}

		}
	}
}

function stopbell()
{
	var getItem;
	getItem=getCheckboxItem();
	if(getItem==null||getItem=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['select_Bell_Scheme']);?>
");
	}
	else
	{
		var getitemarray = getItem.split(",");
		if(getitemarray.length >= 2)
		{
			alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['only_select_one']);?>
");
			return void(0);
		}
		else
		{
			if(window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['enterdisable']);?>
"))
			{
				location="do.php?act=bellstop_msg&id="+getItem+"";
			}
			else
			{
				return void(0);
			}

		}
	
	}
}

function modifybell()
{
	var getItem;
	var count=0;
	for(var i=0;i<document.bellForm.id.length;i++)
	{
		if(document.bellForm.id[i].checked)
		{
			count++;
			if(count>=2)
			{
				alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['only_select_one']);?>
");
				return void(0);
			}
		}
	}
	getItem=getCheckboxItem();
	if(getItem==null||getItem=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['select_Bell_Scheme']);?>
");
		return void(0);
	}
	else
	{
		location="bellmodify.php?id="+getItem+"";
	}
}


function allmodify()
{
	var getItem;
	var count=0;
	for(var i=0;i<document.bellForm.id.length;i++)
	{
		if(document.bellForm.id[i].checked)
		{
			count++;
			if(count>=2)
			{
				alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['only_select_one']);?>
");
				return void(0);
			}
		}
	}
	getItem=getCheckboxItem();
	/*if(getItem==null||getItem=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['select_Bell_Scheme']);?>
");
		return void(0);
	}
	else
	{*/
		location="bellmodifyall.php?id="+getItem+"";
	//}
}

function modifytimeplay()
{
	var getItem;
	var count=0;
	for(var i=0;i<document.bellForm.id.length;i++)
	{
		if(document.bellForm.id[i].checked)
		{
			count++;
			if(count>=2)
			{
				alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['only_select_one']);?>
");
				return void(0);
			}
		}
	}
	getItem=getCheckboxItem();
	/*if(getItem==null||getItem=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['select_Bell_Scheme']);?>
");
		return void(0);
	}
	else
	{*/
		location="sechotime.php?id="+getItem+"";
	//}
}



function delbell()
{
	var getItem;
	getItem=getCheckboxItem();
	if(getItem==null||getItem=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['select_Bell_Scheme']);?>
");
	}
	else
	{
		if(window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['confirm_deleting']);?>
"))
		{
			location="do.php?act=belldel_msg&id="+getItem+"";
		}
		else
		{
			return void(0);
		}
	}
}





function copybell()
{
	var getItem;
	var count=0;
	for(var i=0;i<document.bellForm.id.length;i++)
	{
		if(document.bellForm.id[i].checked)
		{
			count++;
			if(count>=2)
			{
				alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['only_select_one']);?>
");
				return void(0);
			}
		}
	}
	
	getItem=getCheckboxItem();
	if(getItem==null||getItem=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['select_Bell_Scheme']);?>
");
		return void(0);
	}
	else
	{

		location="bellcopy.php?id="+getItem+"";
	}
}

function getCheckboxItem()
{
	var allSel="";
	if(document.bellForm.id.checked)
	{
		allSel=document.bellForm.id.value;
		if(allSel==undefined)
		allSel="";
	}
	for(i=0;i<document.bellForm.id.length;i++)
	{
		if(document.bellForm.id[i].checked)
		{
			
			if(allSel=="")
				{
					allSel=document.bellForm.id[i].value;
				}
			else
				{
					allSel=allSel+","+document.bellForm.id[i].value;
				}	
		}
	}

	return allSel;
}

function getOneItem()
{
	var folder_id = "";
	
	var obj = document.getElementsByName('id');
	
	for(var i= 0; i<obj.length; i++ )
	{
		if(obj[i].checked == true)
		{
			if(isNull(folder_id))
			{
				folder_id = obj[i].value;
			}
			else
			{
				folder_id +=","+obj[i].value;
			}
		}
	}
	return folder_id;
}

function selAll(aid)
{
	if(aid==0)
	{
		document.bellForm.id.checked=true;
	}
	for(i=0;i<document.bellForm.id.length;i++)
	{
		if(!document.bellForm.id[i].checked)
		{
			document.bellForm.id[i].checked=true;
		}
	}
}

function noSelAll(aid)
{
	if(aid==0)
	{
		document.bellForm.id.checked=false;
	}
	for(i=0;i<document.bellForm.id.length;i++)
	{
		if(document.bellForm.id[i].checked)
		{
			document.bellForm.id[i].checked=false;
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
   		return "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['every_day']);?>
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
	ajax_set_task_volume("1",volume_value,task_id);
}
function disappear_volume_div()
{
	if(document.getElementById('change_volume').style.display == "block")
	{
		document.getElementById('change_volume').style.display = "none";
	}
}
<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 language="javascript" src="smarty/templates/ajax/synchronization.js"><?php echo '</script'; ?>
>
</head>
<body onload="reloadpage()">	
<?php $_smarty_tpl->_subTemplateRender("file:BellManager/bellManager_form.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
 
<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

</body>
</html>
<?php }
}
