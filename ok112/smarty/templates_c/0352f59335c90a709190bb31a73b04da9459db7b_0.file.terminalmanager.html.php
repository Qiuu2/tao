<?php
/* Smarty version 3.1.30, created on 2026-07-06 15:50:22
  from "/var/www/html/ok112/smarty/templates/TerminalManager/terminalmanager.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a4b5e3e8990b4_74189423',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '0352f59335c90a709190bb31a73b04da9459db7b' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/TerminalManager/terminalmanager.html',
      1 => 1778116105,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:TerminalManager/terminalmanager_form.html' => 1,
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a4b5e3e8990b4_74189423 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>TerminalManager</title>
<link href="skin/css/main_page_style.css" rel="stylesheet" type="text/css"/>

<link href="skin/css/pop_div_style.css" rel="stylesheet" type="text/css"/>
	<?php echo '<script'; ?>
  src="smarty/templates/ajax/get_terminaltype.js" ><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 language="javascript" src="skin/js/frame/public.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="skin/js/frame/jzdd.js" type="text/javascript"><?php echo '</script'; ?>
>
<style>
  
      
      
        /* 奇数行样式 */
        tr:nth-child(odd) {
            background-color: #f8f9fa;
        }
        
        /* 偶数行样式 */
        tr:nth-child(even) {
            background-color: #c1cfe0;
        }
   </style>
<?php echo '<script'; ?>
 type="text/javascript">
	function convert(value, dataType) 
	{

		switch(dataType) 
		{
			case "int":
				return parseInt(value);
				break
			case "float":
				return parseFloat(value);
				break
			case "date":
				return Date.parse(value);
				break
			default:
				return value.toString();
		}
	}
	//sortȽַ
	function compareCols(col, dataType) 
	{
		return function compareTrs(tr1, tr2) 
		{
			value1 = convert(tr1.cells[col].innerHTML, dataType);
			value2 = convert(tr2.cells[col].innerHTML, dataType);
			if (value1 < value2) 
			{
				return -1;
			} 
			else if (value1 > value2) 
			{
		
				return 1;
			} 
			else 
			{
				return 0;
			}
		};
	}
	//Ա

	function setrowcolor(tableId,xunhuan) 
	{
		var table = document.getElementById(tableId);
		var tbody = table.tBodies[0];
		var tr = tbody.rows; 
		
		tr[xunhuan].style.backgroundColor = '#EEEEFF';
	}
	


function trim(str)
{
   str=str.replace(/(^\s*)|(\s*$)/g,""); 
   return str;
}
	
	function sortTable(tableId, col, dataType) 
	{
		var table = document.getElementById(tableId);
		var tbody = table.tBodies[0];
		var tr = tbody.rows; 
		var trValue = new Array();
		for (var i=0; i<tr.length; i++ ) 
		{
			trValue[i] = tr[i];  //иеϢ洢½
		}

		if ((tbody.sortCol == col)&&(dataType=='1')) 
		{
			trValue.reverse(); //ѾˣֱӶ䷴
		} 
		else 
		{
			trValue.sort(compareCols(col, dataType));  //
		}
		
		var fragment = document.createDocumentFragment();  //½һƬΣڱĽ
		for (var i=0; i<trValue.length; i++ ) 
		{
			fragment.appendChild(trValue[i]);
		}
		tbody.appendChild(fragment); //Ľ滻֮ǰֵ
		tbody.sortCol = col;
	}
	
function delterminal(aid)
{
	var del_terminal_count = 0;
	var qstr=getCheckboxItem();
	if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);	
	}
	else
	{
		for(; del_terminal_count<2; del_terminal_count++)
		{
			if( window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Determine_remove']);?>
"+(del_terminal_count+1)+"<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['Times'];?>
" ) )
			{
				if(del_terminal_count == 1)
				{
					location="do.php?act=terminaldel_msg&id="+qstr+"";
				}
			}
			else
			{
				return void(0);
			}
		}
	}
}
function getCheckboxItem()
{
	var allSel="";
	
	if(document.form2.id.checked)
	{
		allSel=document.form2.id.value;
	
		if(allSel==undefined)
	
		allSel="";
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

function getCheckboxtypeItem()
{
	var allSel="";
	
	if(document.form2.id.checked)
	{
		allSel=document.form2.id.value;
	
		if(allSel==undefined)
	
		allSel="";
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

function startTerminal()
{
	var qstr=getCheckboxItem();
	
	if(qstr==null||qstr=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);	
	}
	else
	{
			location="do.php?act=terminalStart_msg&id="+qstr+"";		
	}
}
function stopTerminal()
{	
	var qstr=getCheckboxItem();
	
	if(isNull(qstr))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);	
	}
	else
	{
			location="do.php?act=terminalStop_msg&id="+qstr+"";		
	}	
}
function getterminalid()
{
	var getItem=getCheckboxItem();
	
	if(getItem==""||getItem==null)
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return false;	
	}
	else
	{
		setterminalform.terminalgetid.value=getItem;
	}
}

function startspeech()
{
	var getitem=getCheckboxItem();
	
	if(isNull(getitem))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{
		window.location="do.php?act=terminalspeech_msg&id="+getitem+"";
	}
}
function closespeech()
{
	var getitem=getCheckboxItem();
	
	if(isNull(getitem))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{
		window.location="do.php?act=terminalnospeech_msg&id="+getitem+"";
	}
}
    
function setshotcut()
{
	var getterminalid = getCheckboxItem();

	if(isNull(getterminalid))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{
		var temp = getterminalid.split(",");
		
		if(temp.length > 1)
		{
			alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Only_select_one']);?>
');
			
			return void(0);
		}
		else if(temp.length == 1)
		{
			window.location.href = "setterminalkeyoption.php?id="+getterminalid+"";
		}
	}
}
function display_terminal_user()
{
	//保留
}

function tv_map()
{
	var obj_arr = document.getElementsByName('id');
	
	var count = 0;
	
	var terminal_id = "";
	
    for (var i=0; i<obj_arr.length; i++)
    {
		if(obj_arr[i].checked  == true)
		{
			count ++;
			
			terminal_id = obj_arr[i].value;
		}
    }
	if(count > 1 || count < 1)
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Only_select_one']);?>
');
		
		return void(0);
	}
	if(count == 1)
	{
		window.location.href = "view_terminal_tv_mapping.php?terminal_id="+terminal_id+"";
	}
}

function view_shotcut(flag)
{
	var obj_arr = document.getElementsByName('id');
	
	var count = 0;
	
	var terminal_id = "";
	
    for (var i=0; i<obj_arr.length; i++)
    {
		if(obj_arr[i].checked  == true)
		{
			count ++;
			
			terminal_id = obj_arr[i].value;
		}
    }
	if(count > 1 || count < 1)
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Only_select_one']);?>
');
		
		return void(0);
	}
	if(count == 1)
	{
		if(flag==0)
		{
			window.location.href = "view_terminal_shotcut_mapping.php?act=0&terminal_id="+terminal_id+"";
		}
		else if(flag==1)
		{

			window.location.href = "view_quickplay.php?terminal_id="+terminal_id+"";
		}
		else if(flag==2)
		{
			window.location.href = "view_yingjiplay.php?terminal_id="+terminal_id+"";
		}
		else if(flag==3)
		{
			window.location.href = "view_shengji.php?terminal_id="+terminal_id+"";
		}
	}
}

function cancel_shotcut()
{
	var obj_arr = document.getElementsByName('id');
	
	var count = 0;
	
	var terminal_id = "";
	
    for (var i=0; i<obj_arr.length; i++)
    {
		if(obj_arr[i].checked  == true)
		{
			count ++;
			
			terminal_id = obj_arr[i].value;
		}
    }
	if(count > 1 || count < 1)
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Only_select_one']);?>
');
		
		return void(0);
	}
	else
	{
		if(window.confirm('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Determine_remove']);?>
'))
		{
			window.location.href = "do.php?act=cancel_terminal_shotcut&terminal_id="+terminal_id+"";
		}
		else
		{
			return void(0);
		}
	}
}

function deltaskterminal()
{
	var getitem=getCheckboxItem();
	if(isNull(getitem))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{
		window.location="do.php?act=deltaskterminal_msg&id="+getitem+"";
	}
}

function chinese_big5_english(curr_language,txt_msg)
{
    if(curr_language == "big5")
    {
        switch(txt_msg)
        {
           
            case "服务器":
            txt_msg= "服務器";
            break;
            case "普通IP终端":
            txt_msg= "普通IP";
            break;
            case "话筒":
            txt_msg= "話筒";
            break;
            case "双向寻呼终端":
            txt_msg= "雙向尋呼";
            break;
            case "IP前置":
            txt_msg= "IP前置";
            break;
            
            case "IP功放":
            txt_msg= "IP功放";
            break;
            case "电源管理器":
            txt_msg= "電源管理器";
            break;
            case "报警主机":
            txt_msg= "報警主機";
            break;
            case "采样终端":
            txt_msg= "採樣";
            break;
            case "普通电脑":
            txt_msg= "普通電腦";
            break;
			case "一体化音箱":
            txt_msg= "Integrated Speakers";
            break;
        }
    }
    else if(curr_language == "chinese") 
    {
        //不做处理
    }
    else if(curr_language == "english")
    {
        switch(txt_msg)
        {
            case "服务器":
            txt_msg= "Server";
            break;
            case "普通IP终端":
            txt_msg= "IP";
            break;
            case "话筒":
            txt_msg= "Microphone";
            break;
            case "双向寻呼终端":
            txt_msg= "Paging";
            break;
            case "IP前置":
            txt_msg= "IP Pre-amplifier";
            break;
            case "简版IP终端":
            txt_msg= "jane ip terminal";
            break;
			 case "手机终端":
            txt_msg= "Mobile terminal";
            break;
			case "分控软件":
            txt_msg= "control software";
            break;
			case "透传终端":
            txt_msg= "Passthrough terminal";
            break;
			case "一键寻呼终端":
            txt_msg= "A key paging terminal";
            break;
            case "IP功放":
            txt_msg= "IP Amplifier";
            break;
            case "电源管理器":
            txt_msg= "Power Controller";
            break;
            case "报警主机":
            txt_msg= "Emergency Centre";
            break;
            case "采样终端":
            txt_msg= "Sampling";
            break;
            case "普通电脑":
            txt_msg= "Computer";
            break;
			case "一体化音箱":
            txt_msg= "Integrated Speakers";
            break;
			case "分控软件":
            txt_msg= "subcontrol software";
            break;
			case "一键寻呼终端":
            txt_msg= "one-click paging terminal";
            break;
			case "分控前置":
            txt_msg= "subcontrol front";
            break;
			case "背景音乐":
            txt_msg= "background music";
            break;
			case "事话接口":
            txt_msg= "telephone interface";
            break;
			case "手机终端":
            txt_msg= "moblie terminal";
            break;
			case "9970分控工作站":
            txt_msg= "subcontrol workstation";
            break;
			case "透传终端":
            txt_msg= "transparent terminal";
            break;
			case "普通IP终端":
            txt_msg= "ordinary ip terminal";
            break;
			case "监控主机":
            txt_msg= "monitor host";
            break;
			case "TTS主机":
            txt_msg= "TTS host";
            break;
			case "离线终端":
            txt_msg= "offline terminal";
            break;
			case "简版网络功放":
            txt_msg= "simple network amplifier";
            break;
			case "简版采样终端":
            txt_msg= "simple sampling terminal";
            break;
			case "网络调音台":
            txt_msg= "network mixer";
            break;
			case "线阵音柱":
            txt_msg= "network audio collector";
            break;
			case "网络调音台":
            txt_msg= "network mixer";
            break;
			case "网络音频采集器":
            txt_msg= "network audio collector";
            break;
			case "网络音频采集器":
            txt_msg= "network audio collector";
            break;
			case "网络前置":
            txt_msg= "network preposition";
            break;
			case "网络分区前置":
            txt_msg= "network area preposition";
            break;
			case "网络功放":
            txt_msg= "network amplifier";
            break;
			case "简版网络前置":
            txt_msg= "simple network preposition";
            break;
			case "寻呼终端":
            txt_msg= "paging terminal";
            break;
			
        }
    }
	return txt_msg;
}
function disappear_volume_div()
{
	if(document.getElementById('change_volume').style.display == "block")
	{
		document.getElementById('change_volume').style.display = "none";
	}
}
function set_task_volume()
{
	var task_id = getCheckboxItem();
	if(isNull(task_id))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{
	if(navigator.appName.indexOf("Explorer") > -1)        
	 var volume_value = document.getElementById('d1').innerText;
	 else
	 var volume_value = document.getElementById('d1').textContent;
	ajax_set_task_volume("2",volume_value,task_id);
	}
}

function set_terminal_password()
{
	var get_terminal_id = getCheckboxItem();

	if(isNull(get_terminal_id))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{
		window.location.href = "set_terminal_password.php?terminal_id="+get_terminal_id+"";
	}
}
function set_synchtask()
{
	var get_terminal_id = getCheckboxItem();

	if(isNull(get_terminal_id))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		return void(0);
	}
	else
	{
		window.location.href = "set_synch_task.php?terminal_id="+get_terminal_id+"";
	}
}
function set_terminal_Dinstancy()
{
/*	var get_terminal_id = getCheckboxItem();
	if(isNull(get_terminal_id))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{
		window.location.href = "set_terminal_Instancy.php?type=0&terminal_id="+get_terminal_id+"";
	}
	*/
	var obj_arr = document.getElementsByName('id');
	var count = 0;
	var terminal_id = "";
    for (var i=0; i<obj_arr.length; i++)
    {
		if(obj_arr[i].checked  == true)
		{
			count ++;
			
			terminal_id = obj_arr[i].value;
		}
    }
	if(count > 1 || count < 1)
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Only_select_one']);?>
');
		return void(0);
	}
	if(count == 1)
	{
	if(window.confirm('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Determine_clear']);?>
'))
		{
		var url = "get_xunhutype.php?id="+terminal_id+"";
		var flag=getchannelvalue(url);

		if(flag==33)
		{
			window.location.href = "do.php?act=cancel_terminal_shotcut&terminal_id="+terminal_id+"";
		}
		else 
		{
			window.location.href = "set_terminal_Instancy.php?type=0&terminal_id="+terminal_id+"";
		}
		}
	}
}

function set_terminal_Sinstancy()
{
/*	var get_terminal_id = getCheckboxItem();
	if(isNull(get_terminal_id))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{
		window.location.href = "set_terminal_Instancy.php?type=1&terminal_id="+get_terminal_id+"";
	}
	*/
	var obj_arr = document.getElementsByName('id');
	var count = 0;
	var terminal_id = "";
    for (var i=0; i<obj_arr.length; i++)
    {
		if(obj_arr[i].checked  == true)
		{
			count ++;
			
			terminal_id = obj_arr[i].value;
		}
    }
	if(count > 1 || count < 1)
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Only_select_one']);?>
');
		return void(0);
	}
	if(count == 1)
	{
		var url = "get_xunhutype.php?id="+terminal_id+"";
		var flag=trim(getchannelvalue(url));

		if(flag==33 || flag==44)
		{
		
			window.location.href = "view_terminal_shotcut_mapping.php?getact=1&terminal_id="+terminal_id+"&gettype="+ flag;
		}
		else 
		{
			window.location.href = "set_terminal_Instancy.php?type=1&terminal_id="+terminal_id+"";
		}
	}
}
function set_terminal_record()
{
	var getitem=getCheckboxItem();
	if(isNull(getitem))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{

			window.location="do.php?act=set_terminal_record&id="+getitem+"";

	}
}
function disappear_task_div()
{
	if(document.getElementById('copytask').style.display == "block")
	{
		document.getElementById('copytask').style.display = "none";
	}
}
function set_terminal_stoprecord()
{
	var getitem=getCheckboxItem();
	
	if(isNull(getitem))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{
	
			window.location="do.php?act=set_terminal_stoprecord&id="+getitem+"";

	}
}
function set_terminal(event)
{
	var obj_arr = document.getElementsByName('id');
	
	var count = 0;
	
	var terminal_id = "";
	
    for (var i=0; i<obj_arr.length; i++)
    {
		if(obj_arr[i].checked  == true)
		{
			count ++;
			
			terminal_id = obj_arr[i].value;
		}
    }
	if(count > 1 || count < 1)
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Only_select_one']);?>
');
		
		return void(0);
	}
	if(count == 1)
	{
		document.getElementById('get_initid').value =terminal_id; 
		 if(document.all)
		{
			window.event.cancelBubble = true;   
		}
		else
		{
			event.stopPropagation();
		}
		var mouse_obj_xy = get_mouse_coordinates(event);
		get_div_obj('copytask').style.left = mouse_obj_xy.x+'px';
		get_div_obj('copytask').style.top = mouse_obj_xy.y-50+'px';
		get_div_obj('copytask').style.display = "block";	
	}
}
function set_terminal_backcall()
{
	var obj_arr = document.getElementsByName('id');
	
	var count = 0;
	
	var terminal_id = "";
	
    for (var i=0; i<obj_arr.length; i++)
    {
		if(obj_arr[i].checked  == true)
		{
			count ++;
			
			terminal_id = obj_arr[i].value;
		}
    }
	if(count > 1 || count < 1)
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Only_select_one']);?>
');
		
		return void(0);
	}
	if(count == 1)
	{
		
		window.location.href = "view_terminal_call_group.php?terminal_id="+terminal_id+"&flag=1";
	}
}

function set_terminal_backcall_dir()
{
	var obj_arr = document.getElementsByName('id');
	
	var count = 0;
	
	var terminal_id = "";
	
    for (var i=0; i<obj_arr.length; i++)
    {
		if(obj_arr[i].checked  == true)
		{
			count ++;
			
			terminal_id = obj_arr[i].value;
		}
    }
	if(count > 1 || count < 1)
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Only_select_one']);?>
');
		
		return void(0);
	}
	if(count == 1)
	{
		
	//	window.location.href = "view_terminal_call_group.php?terminal_id="+terminal_id+"&flag=2";
		window.location.href = "dirstreammanager.php?terminal_id="+terminal_id+"&flag=2";
	
	}
}


function stop_terminal_backcall()
{
	var getitem=getCheckboxItem();
	
	if(isNull(getitem))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{
		window.location="do.php?act=stop_terminal_backcall&id="+getitem+"";
	}
}
function showDiv()
{
	var get_terminal_id = getCheckboxItem();

	if(isNull(get_terminal_id))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}

   document.getElementById('popDiv').style.display='block';
   
   document.getElementById('popIframe').style.display='block';
   
   document.getElementById('bg').style.display='block';
}


function closeDiv()
{
   document.getElementById('popDiv').style.display='none';
   
   document.getElementById('bg').style.display='none';
   
   document.getElementById('popIframe').style.display='none';

}
function setupDiv()
{
	var get_terminal_id = getCheckboxItem();

	var get_terminal_password = "";
	
	if(isNull(get_terminal_id))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		return void(0);
	}
	
	get_terminal_password = trim(document.getElementById('terminal_password').value);
	
	if(isNull(get_terminal_password))
	{
		document.getElementById('terminal_password_msg').innerHTML = "<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['Input_valid'];?>
";		
		document.getElementById('terminal_password_msg').style.color = "#00FF00";			
	window.location.href = "set_terminal_password.php?terminal_id="+get_terminal_id+"&terminal_password="+get_terminal_password+"";
	}
	else
	{
		if(isNumber(get_terminal_password))
		{
			if((get_terminal_password.length<6 || get_terminal_password.length > 6))
			{
				document.getElementById('terminal_password_msg').innerHTML = "<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['Input_errors'];?>
";
				return void(0);
			}
			else
			{
				document.getElementById('terminal_password_msg').innerHTML = "<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['Input_valid'];?>
";
				document.getElementById('terminal_password_msg').style.color = "#00FF00";
				window.location.href = "set_terminal_password.php?terminal_id="+get_terminal_id+"&terminal_password="+get_terminal_password+"";
			}
		}
		else
		{
			document.getElementById('terminal_password_msg').innerHTML = "<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['Input_errors'];?>
";
			return void(0);
		}
	}
}


function startsponsor()
{
	var getitem=getCheckboxItem();
	
	if(isNull(getitem))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		return void(0);
	}
	else
	{
		window.location="do.php?act=terminaldosponsor_msg&id="+getitem+"";
	}
}
function closesponsor()
{
	var getitem=getCheckboxItem();
	if(isNull(getitem))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{
		window.location="do.php?act=terminalstopsponsor_msg&id="+getitem+"";
	}
}


function ledsousuo(ledflag)
{
	var obj_arr = document.getElementsByName('id');
	var count = 0;
	var terminal_id = "";
    for (var i=0; i<obj_arr.length; i++)
    {
		if(obj_arr[i].checked  == true)
		{
			count ++;
			
			terminal_id = obj_arr[i].value;
		}
    }
	if(ledflag==2)	
	{
		window.location= "./led_terminal_sousuo.php?id=0&ledflag=2";

	}
	else
	{
		if(count > 1 || count < 1)
		{
			alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Only_select_one']);?>
');
			return void(0);
		}
		if(count == 1)
		{
			if(ledflag==1)
			{
				window.location="do.php?act=ledsousuo_msg&id="+terminal_id+"&ledflag="+ledflag+"";
				djdd();
			}
			
		}
	}

}

function check_state()
{
	var getitem=getCheckboxItem();
	if(isNull(getitem))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{
		window.location="do.php?act=check_circuit_state&id="+getitem+"";
	}
}

function sync_time()
{
	var getitem=getCheckboxItem();
	if(isNull(getitem))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);
	}
	else
	{
		window.location="do.php?act=sync_time&id="+getitem+"";
	}
}

function actionform()
{
	var getaction,getaction2;
	var searchvalue=document.getElementById('searchvalue').value;
	var searchkey=document.getElementById('searchkey').value;
	var searchsequence=document.getElementById('searchsequence').value;
	getaction="searchvalue="+searchvalue+"&searchkey="+searchkey+"&searchsequence="+searchsequence+"";
	getaction2="terminalmanager1.php?"+getaction;
	
	//window.parent.frames['main'].location.href = getaction2;
	window.location = getaction2;
}


<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 language="javascript" src="smarty/templates/ajax/synchronization.js"><?php echo '</script'; ?>
>
</head>
<body onload="reloadpage()">
<?php $_smarty_tpl->_subTemplateRender("file:TerminalManager/terminalmanager_form.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
 
<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>
 
</body>
</html>
<?php }
}
