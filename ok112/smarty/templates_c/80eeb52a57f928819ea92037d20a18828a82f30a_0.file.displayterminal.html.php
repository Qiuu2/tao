<?php
/* Smarty version 3.1.30, created on 2026-05-25 16:11:01
  from "/var/www/html/ok112/smarty/templates/displayproperty/displayterminal.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a140415dd3e46_27545863',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '80eeb52a57f928819ea92037d20a18828a82f30a' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/displayproperty/displayterminal.html',
      1 => 1778116065,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:displayproperty/displayterminal_form.html' => 1,
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a140415dd3e46_27545863 (Smarty_Internal_Template $_smarty_tpl) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>DisplayTerminal</title>

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
<?php echo '</script'; ?>
>
</head>
<body>	
<?php $_smarty_tpl->_subTemplateRender("file:displayproperty/displayterminal_form.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
 
<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

</body>
</html><?php }
}
