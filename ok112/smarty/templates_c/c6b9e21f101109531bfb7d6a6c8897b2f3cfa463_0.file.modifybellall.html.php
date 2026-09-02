<?php
/* Smarty version 3.1.30, created on 2026-05-25 14:54:04
  from "/var/www/html/ok112/smarty/templates/BellManager/modifybellall.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a13f20cacc140_16187512',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'c6b9e21f101109531bfb7d6a6c8897b2f3cfa463' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/BellManager/modifybellall.html',
      1 => 1778116057,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a13f20cacc140_16187512 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<html>
<head>
<META http-equiv=Content-Type content="text/html; charset=utf-8">
<title>ModifyBellTask</title>

<link href="skin/css/main_page_style.css" rel="stylesheet" type="text/css" />

<!--添加文件列表开始-->
<link href="smarty/templates/BellManager/codebase/dhtmlxtree.css" rel="stylesheet" type="text/css">
<?php echo '<script'; ?>
 language="JavaScript" src="smarty/templates/BellManager/codebase/dhtmlxtree.js" type"text/JavaScript"><?php echo '</script'; ?>
>	
<?php echo '<script'; ?>
 language="JavaScript" src="smarty/templates/BellManager/codebase/dhtmlxcommon.js" type"text/JavaScript"><?php echo '</script'; ?>
>
<!--导入ajax-->
<?php echo '<script'; ?>
  src="smarty/templates/ajax/get_terminaltype.js" ><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="smarty/templates/ajax/popintobellinfo.js" type="text/javascript"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="smarty/templates/ajax/delintobellinfo.js" type="text/javascript"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="smarty/templates/ajax/get_media_play_length.js" type="text/javascript"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="skin/js/frame/analysis_tree_terminal_group_string.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="skin/js/frame/jzdd.js" type="text/javascript"><?php echo '</script'; ?>
>
<!--时间代码开始-->
<style>
/*想要改输入日历控件的样子就改下面的CSS样式就可以了*/
.header
{
	font-weight: bold;
	font-weight: bold;
	color: #000000;
	background:#C2DEED;
	height: 25px;
	padding-left: 10px;
	font-family: Arial, Tahoma;
	font-size: 12px;
}
.header td
{
   padding-left: 10px;
}
.header a
{
   color: #154BA0;
}
.header input
{
   background:none;
   vertical-align: middle;
   height: 16px;
}
.category
{
   font: 12px Arial, Tahoma;
   font: 11px Arial, Tahoma;
   color: #92A05A;
   height:20px;
   background-color: #FFFFD9;
}
.category td
{
   border-bottom: 1px solid #DEDEB8;
}
.expire, .expire a:link, .expire a:visited
{
   color: #999999;
}
.default, .default a:link, .default a:visited
{
   color: #000000;
}
.checked, .checked a:link, .checked a:visited
{
   color: #FF0000;
}
.today, .today a:link, .today a:visited
{
   color: #00BB00;
}
#calendar_year
{
   display: none;
   line-height: 130%;
   background: #FFFFFF;
   position: absolute;
   z-index: 10;
}
#calendar_year .col
{
   float: left;
   background: #FFFFFF;
   margin-left: 1px;
   border: 1px solid #86B9D6;
   padding: 4px;
}
#calendar_month
{
   display: none;
   background: #FFFFFF;
   line-height: 130%;
   border: 1px solid #86B9D6;
   padding: 4px;
   position: absolute;
   z-index: 11;
}
.tableborder
{
   background: white;
   border: 1px solid #86B9D6;
}
#year, #month
{
   padding-right:10px;
   background:url(onbottom.gif) no-repeat center right;
}/*图片路径可以改成自己的*/
/*Date*/
</style>

<?php echo '<script'; ?>
 language="javascript">
//这段脚本如果你的页面里有，就可以去掉它们了
var ie =navigator.appName=="Microsoft Internet Explorer"?true:false;
function $(objID)
{
	return document.getElementById(objID);
}
<?php echo '</script'; ?>
>
<!--时间代码结束-->
<!--添加弹出时间-->
<?php echo '<script'; ?>
 language="javascript">
var str = "<iframe name='iframe' id='iframe' frameborder='0' scrolling='no' style='position:absolute;z-index:-1;margin:0px;width:250; height:80px;'></iframe>";
document.writeln("<div id=\"_contents\" style=\"font-size: 12px; background-color:#cccccc; margin:0px; z-index:99; text-align:center; position:absolute; width:250px; height:80px;visibility:hidden\">");
str += "<table width='100%' height = '100%' style='border: 1 solid #C2DEED;'><tr align='center' valign='middle'><td><select name=\"_hour\">";
for (h = 0; h <= 9; h++) 
{
	str += "<option value=\"0" + h + "\">0" + h + "</option>";
}
for (h = 10; h <= 23; h++) 
{
	str += "<option value=\"" + h + "\">" + h + "</option>";
}
str += "</select>&nbsp;<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Hour']);?>
&nbsp;<select name=\"_minute\">";
for (m = 0; m <= 9; m++) 
{
	str += "<option value=\"0" + m + "\">0" + m + "</option>";
}
for (m = 10; m <= 59; m++) 
{
	str += "<option value=\"" + m + "\">" + m + "</option>";
}
str += "</select>&nbsp;<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Minute']);?>
&nbsp;<select name=\"_second\">";
for (s = 0; s <= 9; s++) 
{
	str += "<option value=\"0" + s + "\">0" + s + "</option>";
}
for (s = 10; s <= 59; s++) 
{
	str += "<option value=\"" + s + "\">" + s + "</option>";
}
str += "</select> &nbsp;<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Second']);?>
<p><input type=\"button\" onclick=\"_select()\" value=\"<?php echo $_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Confirm'];?>
\" style=\"font-size:12px\" class=\"terminal_button\"/>&nbsp;&nbsp;<input type=\"button\" onclick=\"_cancel()\" value=\"<?php echo $_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Cancel'];?>
\" style=\"font-size:12px\" class=\"terminal_button\"/></td></tr></table></div>";
document.writeln(str);
var _fieldname;
function Timeselect(tt) 
{
	_fieldname = tt;
	var ttop = tt.offsetTop;   
	var thei = tt.clientHeight;   
	var tleft = tt.offsetLeft;   
	while (tt = tt.offsetParent) 
	{
		ttop += tt.offsetTop;
		tleft += tt.offsetLeft;
	}
	document.all._contents.style.top = ttop + thei + 4-document.getElementById( "divTest").scrollTop;
	document.all._contents.style.left = tleft;
	document.all._contents.style.visibility = "visible";
}

function _select() 
{ //确定按钮触发的事件
	_fieldname.value = document.all._hour.value + ":" + document.all._minute.value + ":" + document.all._second.value;
	document.all._contents.style.visibility = "hidden"; 
}
function _cancel()
{ //取消按钮触发的事件
	document.all._contents.style.visibility = "hidden";
}

document.getElementById("iframe").document.body.onload=function addGgColor()
{
	  iframe.document.body.style.backgroundColor="#cccccc";
}
<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 language="javascript">
var lenstr = "<iframe name='iframelen' id='iframelen' frameborder='0' scrolling='no' style='position:absolute;z-index:-1;margin:0px;width:260px; height:80px;'></iframe>";
document.writeln("<div id=\"_lenthcontents\" style=\"font-size: 12px; background-color:#cccccc; z-index:98; margin:0px; text-align:center; position:absolute; width:260px; height:80px;visibility:hidden\">");
lenstr += "<table width='100%' height = '100%' style='border: 1 solid #C2DEED;  font-size: 12px;'><tr><td align='center'><INPUT type=radio name='radio' onclick='_clickbt()' CHECKED></td><td><select name=\"_hourlen\">";
for (h = 0; h <= 9; h++) 
{
	lenstr += "<option value=\"0" + h + "\">0" + h + "</option>";
}
for (h = 10; h <= 23; h++) 
{
	lenstr += "<option value=\"" + h + "\">" + h + "</option>";
}
lenstr += "</select>&nbsp; <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Hour']);?>
 &nbsp;<select name=\"_minutelen\">";
for (m = 0; m <= 9; m++) 
{
	lenstr += "<option value=\"0" + m + "\">0" + m + "</option>";
}
for (m = 10; m <= 59; m++) 
{
	lenstr += "<option value=\"" + m + "\">" + m + "</option>";
}
lenstr += "</select>&nbsp;<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Minute']);?>
 &nbsp;<select name=\"_secondlen\">";
for (s = 0; s <= 9; s++) 
{
	lenstr += "<option value=\"0" + s + "\">0" + s + "</option>";
}
for (s = 10; s <= 59; s++) 
{
	lenstr += "<option value=\"" + s + "\">" + s + "</option>";
}
lenstr += "</select> &nbsp; <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Second']);?>
</td></tr><tr><td align='center'><INPUT type=radio name='radio' onclick='_clickbt()'></td><td><select name=\"_times\" disabled>";
for (h = 0; h <= 9; h++) 
{
	lenstr += "<option value=\"0" + h + "\">0" + h + "</option>";
}
for (h = 10; h <= 99; h++)
{
	lenstr += "<option value=\"" + h + "\">" + h + "</option>";
}
lenstr += "</select> &nbsp;<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['number']);?>
</td></tr><tr><td align='center' colspan = 2><input type=\"button\" onclick=\"_lenselect()\" value=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Confirm']);?>
\" style=\"font-size:12px\"/>&nbsp;&nbsp;<input type=\"button\" onclick=\"_lencancel()\" value=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Cancel']);?>
\" style=\"font-size:12px\"/></td></tr></table></div>";
document.writeln(lenstr);


var _fieldnames;
var getlen;
var _get_row;
var get_timelength;
function Playlenthselect(tts) 
{
	_fieldnames = tts;
	var ttops = tts.offsetTop;   
	var theis = tts.clientHeight;   
	var tlefts = tts.offsetLeft;   
	while (tts = tts.offsetParent) 
	{
		ttops += tts.offsetTop;
		tlefts += tts.offsetLeft;
	}
	document.all._lenthcontents.style.top = ttops + theis + 4-document.getElementById( "divTest").scrollTop;
	document.all._lenthcontents.style.left = tlefts;
	document.all._contents.style.visibility = "hidden";
	document.all._lenthcontents.style.visibility = "visible";
}
function _clickbt()
{
    if(document.all.radio[0].checked)
    {
        document.all._hourlen.disabled = false;
        document.all._minutelen.disabled = false;
        document.all._secondlen.disabled = false;
        document.all._times.disabled = true;
    }
    else if(document.all.radio[1].checked)
    {
        document.all._hourlen.disabled = true;
        document.all._minutelen.disabled = true;
        document.all._secondlen.disabled = true;
        document.all._times.disabled = false;
    }
}

function _lenselect() 
{ 
    if(document.all.radio[0].checked)
    {
		
        _fieldnames.value = document.all._hourlen.value + ":" + document.all._minutelen.value + ":" + document.all._secondlen.value;
    }
    else if(document.all.radio[1].checked)
    {
        _fieldnames.value = document.all._times.value;
    }
    document.all._lenthcontents.style.visibility = "hidden"; 
}
function _lencancel()
{
	document.all._lenthcontents.style.visibility = "hidden";
}

document.getElementById("iframelen").document.body.onload=function addGgColor()
{
	  iframelen.document.body.style.backgroundColor="#fffffc";
}

<?php echo '</script'; ?>
>
<!--验证代码-->
<?php echo '<script'; ?>
 language="javascript">
function isNull( str )
{
	if ( str == "" || str==null) 
	return true;
	var regu = "^[ ]+$";
	var re = new RegExp(regu);
	return re.test(str);
}
function isNumber( s )
{ 
	var regu = "^[0-9]+$"; 
	var re = new RegExp(regu); 
	if (s.search(re) != -1) 
	{ 
		return true; 
	}
	else 
	{ 
		return false; 
	} 
} 
function isChinaOrNumbOrLett(s)
{
	var regu = "^[0-9a-zA-Z\u4e00-\u9fa5]+$"; 
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
function trim(str)
{
   str=str.replace(/(^\s*)|(\s*$)/g,""); 
   return str;
}
<?php echo '</script'; ?>
>
<!--动态添加表格-->
<?php echo '<script'; ?>
 language="javascript">


var row=1;//产生序号
var sign=0;//判断单击次数
function insertRowInTable(gettable)
{
	
	var table = document.getElementById(gettable);
	var newRow = table.insertRow(row);
	newRow.style.textAlign = "center";
	newRow.insertCell(0).innerHTML = "<input title=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['modify_task']);?>
\" type=\"checkbox\" name=\"belltaskid\" id=\"belltaskid\" value=\"-1\"  onclick=\"getonebelltaskterminal(this)\"/>";
	newRow.insertCell(1).innerHTML = "<input title=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Task_Name_task']);?>
\" type=\"text\"  style=\"width:110px\" name=\"coursename\" id=\"coursename\" maxlength=\"8\" onmousedown=\"getoneinput(this)\" value=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Please_Input']);?>
\"/><span class=\"terminal_star\" id=\"coursename_text\" name=\"coursename_text\">*</span>";
	newRow.insertCell(2).innerHTML = "<input title=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Start_timetask']);?>
\" type='text' value='00:00:00' name='bellstarttime'  id='bellstarttime' readonly='true' size='10' onclick='Timeselect(this)'/>";
	newRow.insertCell(3).innerHTML = "<input title=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['select_musictask']);?>
\" type='text' style=\"width:130px\"  value=\"\" name='setmusicname' id='setmusicname' readonly='true' size='10' />";
	newRow.insertCell(4).innerHTML = "<input title=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['select_musictimetask']);?>
\" type='text' style=\"width:60px\" value='00:00:00' name='belltiemlengthonly' id='belltiemlengthonly' readonly='true' size='10' /><span class=\"terminal_star\" id=\"bell_playlen\" name=\"bell_playlen\"></span>";
	newRow.insertCell(-1).innerHTML = "<input title=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['add_singletask']);?>
\" type=\"button\" style=\"font-family: Arial, Helvetica, sans-serif;font-size: 12px;color: #3366cc;border: 1px solid #336699;\" class=\"0\" value=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Add']);?>
\" name=\"add\" id=\"add\" onclick=\"getcurrentrowindex(this);getresultvalue == 1?((this.className==0?this.value='<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Modify']);?>
':this.value='<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Finish']);?>
'),this.className='1'):(this.className==0?this.value='<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Add']);?>
':this.value='<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Modify']);?>
');getresultvalue = 0;\">&nbsp;<input title=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['del_singletask']);?>
\" type=\"button\" style=\"font-family: Arial, Helvetica, sans-serif;font-size: 12px;color: #3366cc;border: 1px solid #336699;\" value=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Delete']);?>
\" id=\"delrow\" name=\"delrow\" onclick=\"deleteRow(this);\">";

	row++;
	sign++;
	
	var   obj   =   document.getElementById( "divTest").offsetHeight; 

	 if(obj>=250)
	 {
	 	document.getElementById("divTest").style.height=250+"px"; 
	 	document.getElementById( "divTest").scrollTop+= document.getElementById('coursetable').clientHeight+30;
	 }
	 else
	 {
	
	  document.getElementById("divTest").style.height=document.getElementById('coursetable').clientHeight+30;
	 }
}
function deleteRow(obj)
{
	var getcurrenrownum=obj.parentNode.parentNode.rowIndex;
	var totalrownum = obj.parentNode.parentNode.parentNode.rows.length-1;
	var dispatchid = -1;
	if(totalrownum == 1)
	{
		dispatchid = document.getElementById('belltaskid').value;
	}
	if(totalrownum > 1)
	{
		dispatchid = document.getElementsByName('belltaskid')[getcurrenrownum-1].value;
	}
	if(dispatchid  == -1)
	{
		document.getElementById('coursetable').deleteRow(getcurrenrownum);
		row--;
		sign--;
	}
	else if(dispatchid  != -1)
	{
		var getreturnvalue = deldata("delonebellplan.php?dispatchid="+dispatchid+"");
		
		if(getreturnvalue == 1)
		{
			document.getElementById('coursetable').deleteRow(getcurrenrownum);
			row--;
			sign--;
		}
		var bbb="<?php echo $_smarty_tpl->tpl_vars['bellinfo']->value['info'];?>
";
		window.location.href="bellmodifyall.php?infoname="+bbb+"";
		//location.reload(true);
	}
}

//获取单个任务信息
function getoneinput(obj)
{
 	 var get_belltaskid =obj.value;
	var getcurrenrownum = obj.parentNode.parentNode.rowIndex;
	var totalrownum = obj.parentNode.parentNode.parentNode.rows.length-1;

	if(totalrownum == 1)
	{
		if(document.getElementById('coursename').value=="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Please_Input']);?>
")
		{
			document.getElementById('coursename').value="";
		}

	}
	else if(totalrownum>1)
	{
		if(document.getElementsByName('coursename')[getcurrenrownum-1].value=="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Please_Input']);?>
")
		{
			document.getElementsByName('coursename')[getcurrenrownum-1].value = "";
		}
	}
}

function getcurrentrowindex(obj)
{
	var judge_mark = 1;
	var getschemename = document.getElementById('taskname').value;
	if(isNull(getschemename))
	{
		document.getElementById('taskname_text').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['enter_scheme_name']);?>
</font>";
		document.getElementById('taskname').focus();
		judge_mark = 0;
		return void(0);
	}
	else if(!isChinaOrNumbOrLett(getschemename))
	{
		document.getElementById('taskname_text').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['letter_number_Chinese']);?>
</font>";
		document.getElementById('taskname').focus();
		judge_mark = 0;
		return void(0);
	}
	document.getElementById('taskname_text').innerHTML="<font class='terminal_star'></font>";
	//获取声音
	document.getElementById('task_default_volume').value = trim(document.getElementById('volume_value').value);
	task_default_volume = document.getElementById('task_default_volume').value;
	
	//获取优先级
	var get_task_prority_str = document.getElementById('task_priority_text').value;

	var getprepower = document.getElementById('prepower').value;
	
	var getstartdate = document.getElementById('startdate').value;
	var getenddate = document.getElementById('enddate').value;
	var sendmode =document.getElementById('datasendmodel').value;
	if(getstartdate > getenddate)
	{
		document.getElementById('timecompare_text').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['smaller_than_end_date']);?>
</font>";
		document.getElementById('startdate').focus();
		judge_mark = 0;
		return void(0);
	}
	document.getElementById('timecompare_text').innerHTML="<font class='terminal_star'></font>";
	
	var getexemodel = "";
	
	var objexemodel = document.getElementById('exemodel');
	
	if(objexemodel.options[objexemodel.selectedIndex].value == 1)
	{
		getexemodel = "1111111";
	}
	else if(objexemodel.options[objexemodel.selectedIndex].value == 2)
	{
		for(var i=0;i<document.bellform.week.length;i++)
		{
			if(document.bellform.week[i].checked)
			{
				if(getexemodel=="")
				{getexemodel="1";}
				else
				{getexemodel+="1";}
			}
			else
			{
				if(getexemodel=="")
				{getexemodel="0";}
				else
				{getexemodel+="0";}
			}
		}
		if(getexemodel*1 == 0)
		{
			document.getElementById('exeModel_text').innerHTML = "<font style='color:red;'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['select_the_date']);?>
</font>";
			judge_mark = 0;
			return void(0);
		}
	}
	
	var getonebelltaskid = "";	
	var getonelessonname = "";
	var getonebelltime = "";
	var getonebellname = "";
	var getonetimelength = "";
	var totalrownum = obj.parentNode.parentNode.parentNode.rows.length-1;//获取总行数
	var getcurrentrownum = obj.parentNode.parentNode.rowIndex;//获取当前行数
		getonebellname = document.getElementById('setbellname').value;
		var getonetimelengthtemp = document.getElementById('belltiemlength').value;
	if(totalrownum == 1)
	{
		getonebelltaskid = document.getElementById('belltaskid').value;
		getonelessonname = document.getElementById('coursename').value;
		if(isNull(getonelessonname))
		{
			document.getElementById('coursename_text').innerHTML="<font style='color:red;font-size:180%;'>*</font>";
			return void(0);
			judge_mark = 0;
		}
		else if(!isChinaOrNumbOrLett(getonelessonname))
		{
			document.getElementById('coursename_text').innerHTML="<font style='color:red;font-size:180%;'>*</font>";
			return void(0);
			judge_mark = 0;
		}
		//document.getElementById('coursename_text').innerHTML="<font></font>";
		getonebelltime = document.getElementById('bellstarttime').value;
	
			//getonetimelength = getonetimelengthtemp[0]*60*60 + getonetimelengthtemp[1]*60 + getonetimelengthtemp[2]*1;
	}
	else if(totalrownum >1)
	{
		getonebelltaskid = document.getElementsByName('belltaskid')[getcurrentrownum-1].value;
		getonelessonname = document.getElementsByName('coursename')[getcurrentrownum-1].value;
		
		if(isNull(getonelessonname))
		{
			document.getElementsByName('coursename_text')[getcurrentrownum-1].innerHTML="<font style='color:red;font-size:180%;'>*</font>";
			return void(0);
			judge_mark = 0;
		}
		else if(!isChinaOrNumbOrLett(getonelessonname))
		{
			document.getElementsByName('coursename_text')[getcurrentrownum-1].innerHTML="<font style='color:red;font-size:180%;'>*</font>";
			return void(0);
			judge_mark = 0;
		}
	//	document.getElementsByName('coursename_text')[getcurrentrownum-1].innerHTML="<font></font>";
		
		getonebelltime = document.getElementsByName('bellstarttime')[getcurrentrownum-1].value;

	
			//getonetimelength = getonetimelengthtemp[0]*60*60 + getonetimelengthtemp[1]*60 + getonetimelengthtemp[2]*1;
		
	}
	/////////////////////////////////////////////////////////////////////////
	var str = trim(tree3.getAllChecked());
	
	analysis_tree_terminal_group_string(str);
	
	var analysis_tree_group_strings = trim(analysis_tree_group_string.toString()); 
	
	analysis_tree_group_string = new Array();

	var getterminalid = trim(analysis_tree_terminal_string.toString());
	
	analysis_tree_terminal_string = new Array();
	
	var get_terminal_value = trim(document.getElementById('get_terminal').value);
	var get_id = trim(document.getElementById('get_id').value);
	var get_noid = trim(document.getElementById('get_noid').value);

	
	if(getterminalid == "" || getterminalid == null)
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['select_terminals']);?>
');
		
		judge_mark = 0;
	}
	if(judge_mark == 1)
	{ 
	
	var selectnum=0;
		if(getonetimelengthtemp.indexOf(":")==-1)
		{
		selectnum=2;
		getonetimelength=getonetimelengthtemp;
		}
		else
		{
		selectnum=1;
		getonetimelengthtemp=getonetimelengthtemp.split(":");
		getonetimelength = parseInt(getonetimelengthtemp[0])*60*60 + parseInt(getonetimelengthtemp[1])*60 + parseInt(getonetimelengthtemp[2])*1;
		}
		
		var seturldata ="modifyonebellplan.php?getonebelltaskid="+getonebelltaskid+"&getschemename="+getschemename+"&getprepower="+getprepower+"&getstartdate="+getstartdate+"&getenddate="+getenddate+"&getexemodel="+getexemodel+"&getonelessonname="+getonelessonname+"&getonebelltime="+getonebelltime+"&getonebellname="+getonebellname+"&getonetimelength="+getonetimelength+"&getterminalid="+getterminalid+"&task_default_volume="+task_default_volume+"&task_priority_text="+get_task_prority_str+"&analysis_tree_group_strings="+analysis_tree_group_strings+"&get_terminal_value="+get_terminal_value+"&get_id="+get_id+"&get_noid="+get_noid+"&selectnum="+selectnum+"&sendmode="+sendmode+"";
		
		/****************************************
			当数据库auto_increatement_id为0会出现问题
		****************************************/
		var getnewtaskid = modifydata(seturldata,"<?php echo $_smarty_tpl->tpl_vars['language']->value;?>
");
		var bbb="<?php echo $_smarty_tpl->tpl_vars['bellinfo']->value['info'];?>
";

		window.location.href="bellmodifyall.php?infoname="+bbb+"";
		var getobj=document.getElementById('setbellname');
		
		if(getnewtaskid == 1)
		{
			if(totalrownum == 1)
			{
				document.getElementById('setmusicname').value =getobj.options[getobj.selectedIndex].text;
				document.getElementById('belltiemlengthonly').value = document.getElementById('belltiemlength').value;
				
				if(selectnum==1)
				{
				
					document.getElementById('bell_playlen').value=get_timelength;
					document.getElementById('bell_playlen').style.display = "none";
				
				}
				else if(selectnum==2)
				{
				var counttimes = get_timelength.split(":");
				var temptimes = (parseInt(counttimes[0])*60*60 + parseInt(counttimes[1])*60 + parseInt(counttimes[2])*1);
				document.getElementById('bell_playlen').value=get_timelength;
				getlen=time_tran_format(temptimes*document.getElementById('belltiemlengthonly').value);
					document.getElementById('bell_playlen').innerHTML="<font class='terminal_star'>"+getlen+"</font>";
					document.getElementById('bell_playlen').style.display = "";
					
				}
			}
			else if(totalrownum >1)
			{
				document.getElementsByName('setmusicname')[getcurrentrownum-1].value = getobj.options[getobj.selectedIndex].text;
				document.getElementsByName('belltiemlengthonly')[getcurrentrownum-1].value = document.getElementById('belltiemlength').value;
				if(selectnum==1)
				{
				
					document.getElementsByName('bell_playlen')[getcurrentrownum-1].value=get_timelength;
					document.getElementsByName('bell_playlen')[getcurrentrownum-1].style.display = "none";
				
				}
				else if(selectnum==2)
				{
				var counttimes = get_timelength.split(":");
				var temptimes = (parseInt(counttimes[0])*60*60 + parseInt(counttimes[1])*60 + parseInt(counttimes[2])*1);
		
				getlen=time_tran_format(temptimes*document.getElementsByName('belltiemlengthonly')[getcurrentrownum-1].value);
					document.getElementsByName('bell_playlen')[getcurrentrownum-1].value=get_timelength;
				document.getElementsByName('bell_playlen')[getcurrentrownum-1].innerHTML="<font class='terminal_star'>"+getlen+"</font>";
				document.getElementsByName('bell_playlen')[getcurrentrownum-1].style.display = "";
					
				}
			}
		}
		if(getnewtaskid > 1)
		{
			if(totalrownum == 1)
			{
				document.getElementById('belltaskid').value = getnewtaskid;
				document.getElementById('setmusicname').value =getobj.options[getobj.selectedIndex].text;
				document.getElementById('belltiemlengthonly').value = document.getElementById('belltiemlength').value;
				if(selectnum==1)
				{
				
					document.getElementById('bell_playlen').value=get_timelength;
					document.getElementById('bell_playlen').style.display = "none";
				
				}
				else if(selectnum==2)
				{
				var counttimes = get_timelength.split(":");
				var temptimes = (parseInt(counttimes[0])*60*60 + parseInt(counttimes[1])*60 + parseInt(counttimes[2])*1);
				document.getElementById('bell_playlen').value=get_timelength;
				getlen=time_tran_format(temptimes*document.getElementById('belltiemlengthonly').value);
					document.getElementById('bell_playlen').innerHTML="<font class='terminal_star'>"+getlen+"</font>";
					document.getElementById('bell_playlen').style.display = "";
				}
			
			}
			else if(totalrownum >1)
			{
				
				document.getElementsByName('belltaskid')[getcurrentrownum-1].value = getnewtaskid;
				document.getElementsByName('setmusicname')[getcurrentrownum-1].value = getobj.options[getobj.selectedIndex].text;
				document.getElementsByName('belltiemlengthonly')[getcurrentrownum-1].value = document.getElementById('belltiemlength').value;
					if(selectnum==1)
				{
				
					document.getElementsByName('belltiemlengthonly')[getcurrentrownum-1].value=get_timelength;
					document.getElementsByName('belltiemlengthonly')[getcurrentrownum-1].style.display = "none";
				
				}
				else if(selectnum==2)
				{
				var counttimes = get_timelength.split(":");
				var temptimes = (parseInt(counttimes[0])*60*60 + parseInt(counttimes[1])*60 + parseInt(counttimes[2])*1);
		
				getlen=time_tran_format(temptimes*document.getElementsByName('belltiemlengthonly')[getcurrentrownum-1].value);
						document.getElementsByName('bell_playlen')[getcurrentrownum-1].value=get_timelength;
				document.getElementsByName('bell_playlen')[getcurrentrownum-1].innerHTML="<font class='terminal_star'>"+getlen+"</font>";
				document.getElementsByName('bell_playlen')[getcurrentrownum-1].style.display = "";
					
				}
			}
		}
	
		
		//location.reload(true);
	}
}
var get_belltaskid="";

function getonebelltaskterminal(obj)
{
	
    var get_belltaskid =obj.value;
	if(get_belltaskid==-1)
		return true;
	var getcurrenrownum = obj.parentNode.parentNode.rowIndex;
	
	var totalrownum = obj.parentNode.parentNode.parentNode.rows.length-1;
	getonetaskterminals("getonetaskterminal.php?taskid="+obj.value+"",getcurrenrownum);
	document.getElementById('get_belltaskid').value = trim(get_belltaskid.toString());
	
}

function get_allmedia_length(obj,url)
{
   createXMLHttpRequest();
   	
   xmlhttp.open("GET",url,false);
   
   xmlhttp.onreadystatechange = function()
   { 
      if( xmlhttp.readyState == 4 )
      { 
         if( xmlhttp.status == 200 )
         {
			var get_media_play_time_format = time_tran_format(xmlhttp.responseText);
			get_timelength=get_media_play_time_format;
			document.getElementById('belltiemlength').value = get_media_play_time_format;
         }
		 else
		 {
			alert('Failed'); 
		 }
      }
   }
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	
	xmlhttp.send(null);
}
function get_allmedia_time_length(obj)
{
	var media_id = obj.value;
	var url = "get_media_play_length.php?id=" + media_id;
	
	get_allmedia_length(obj,url);
}


<?php echo '</script'; ?>
>
<!--添加日期-->
<?php echo '<script'; ?>
 language="javascript">
var controlid = null;
var currdate = null;
var startdate = null;
var enddate  = null;
var yy = null;
var mm = null;
var hh = null;
var ii = null;
var currday = null;
var addtime = false;
var today = new Date();
var lastcheckedyear = false;
var lastcheckedmonth = false;
//取消事件冒泡
function _cancelBubble(event) 
{
	e = event ? event : window.event ;
	if(ie) {
		e.cancelBubble = true;
	} else {
		e.stopPropagation();
	}
}
//获取控件坐标
function getposition(obj) 
{
	var r = new Array();
	r['x'] = obj.offsetLeft;
	r['y'] = obj.offsetTop;
	while(obj = obj.offsetParent) 
	{
		r['x'] += obj.offsetLeft;
		r['y'] += obj.offsetTop;
	}
	return r;
}
//加载日期div
function loadcalendar() 
{
	//构建一个日历框架
	s = '';
	s += '<div id="calendar" style="display:none; position:absolute; z-index:9;" onclick="_cancelBubble(event)">';
	if (ie)
	{
		s += '<iframe width="200" height="160" src="about:blank" style="position: absolute;z-index:-1;"></iframe>';
	}
	s += '<div style="width: 200px;"><table class="tableborder" cellspacing="0" cellpadding="0" width="100%" style="text-align: center">';
	s += '<tr align="center" class="header"><td class="header"><a href="#" onclick="refreshcalendar(yy, mm-1);return false" title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['lastmonth']);?>
">&lt;&lt;</a></td><td colspan="5" style="text-align: center" class="header"><a href="#" onclick="showdiv(\'year\');_cancelBubble(event);return false" title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['pleaseclickyear']);?>
" id="year"></a>&nbsp; - &nbsp;<a id="month" title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['pleaseclickmonth']);?>
" href="#" onclick="showdiv(\'month\');_cancelBubble(event);return false"></a></td><td class="header"><A href="#" onclick="refreshcalendar(yy, mm+1);return false" title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['nextmonth']);?>
">&gt;&gt;</A></td></tr>';
	s += '<tr class="category"><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['day']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['one']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['two']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['three']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['four']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['five']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['six']);?>
</td></tr>';
	for(var i = 0; i < 6; i++) 
	{
		s += '<tr class="altbg2">';
		for(var j = 1; j <= 7; j++)
			s += "<td id=d" + (i * 7 + j) + " height=\"19\">0</td>";
		s += "</tr>";
	}
	s += '<tr class="colors" id="hourminute"><td colspan="7" align="center"><input type="text" size="1" value="" id="hour" onKeyUp=\'this.value=this.value > 23 ? 23 : zerofill(this.value);controlid.value=controlid.value.replace(/\\d+(\:\\d+)/ig, this.value+"$1")\'> <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['point']);?>
 <input type="text" size="1" value="" id="minute" onKeyUp=\'this.value=this.value > 59 ? 59 : zerofill(this.value);controlid.value=controlid.value.replace(/(\\d+\:)\\d+/ig, "$1"+this.value)\'> <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['part']);?>
</td></tr>';
	s += '</table></div></div>';
	s += '<div id="calendar_year" onclick="_cancelBubble(event)"><div class="col">';
	for(var k = 2010; k <= 2039; k++) 
	{
		s += k != 1930 && k % 10 == 0 ? '</div><div class="col">' : '';
		s += '<a href="#" onclick="refreshcalendar(' + k + ', mm);$(\'calendar_year\').style.display=\'none\';return false"><span' + (today.getFullYear() == k ? ' class="today"' : '') + ' id="calendar_year_' + k + '">' + k + '</span></a><br />';
	}
	s += '</div></div>';
	s += '<div id="calendar_month" onclick="_cancelBubble(event)">';
	for(var k = 1; k <= 12; k++) 
	{
		s += '<a href="#" onclick="refreshcalendar(yy, ' + (k - 1) + ');$(\'calendar_month\').style.display=\'none\';return false"><span' + (today.getMonth()+1 == k ? ' class="today"' : '') + ' id="calendar_month_' + k + '">' + k + ( k < 10 ? '&nbsp;' : '') + ' <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['month']);?>
</span></a><br />';
	}
	s += '</div>';

	var nElement = document.createElement("div");
	nElement.innerHTML=s;
	document.getElementsByTagName("body")[0].appendChild(nElement);
	//单击文档则隐藏日历
	document.onclick = function(event) 
	{
		$('calendar').style.display = 'none';
		$('calendar_year').style.display = 'none';
		$('calendar_month').style.display = 'none';
	}
	//单击日历时取消事件冒泡
	$('calendar').onclick = function(event) 
	{
		_cancelBubble(event);
		$('calendar_year').style.display = 'none';
		$('calendar_month').style.display = 'none';
	}
}

function parsedate(s) 
{
	/(\d+)\-(\d+)\-(\d+)\s*(\d*):?(\d*)/.exec(s);
	var m1 = (RegExp.$1 && RegExp.$1 > 1899 && RegExp.$1 < 2101) ? parseFloat(RegExp.$1) : today.getFullYear();
	var m2 = (RegExp.$2 && (RegExp.$2 > 0 && RegExp.$2 < 13)) ? parseFloat(RegExp.$2) : today.getMonth() + 1;
	var m3 = (RegExp.$3 && (RegExp.$3 > 0 && RegExp.$3 < 32)) ? parseFloat(RegExp.$3) : today.getDate();
	var m4 = (RegExp.$4 && (RegExp.$4 > -1 && RegExp.$4 < 24)) ? parseFloat(RegExp.$4) : 0;
	var m5 = (RegExp.$5 && (RegExp.$5 > -1 && RegExp.$5 < 60)) ? parseFloat(RegExp.$5) : 0;
	/(\d+)\-(\d+)\-(\d+)\s*(\d*):?(\d*)/.exec("0000-00-00 00\:00");
	return new Date(m1, m2 - 1, m3, m4, m5);
}

function settime(d) 
{
	$('calendar').style.display = 'none';
	controlid.value = yy + "-" + zerofill(mm + 1) + "-" + zerofill(d) + (addtime ? ' ' + zerofill($('hour').value) + ':' + zerofill($('minute').value) : '');
}

function showcalendar(event,controlid1, addtime1, startdate1, enddate1) 
{
	controlid = controlid1;
	addtime = addtime1;
	startdate = startdate1 ? parsedate(startdate1) : false;
	enddate = enddate1 ? parsedate(enddate1) : false;
	currday = controlid.value ? parsedate(controlid.value) : today;
	hh = currday.getHours();
	ii = currday.getMinutes();
	var p = getposition(controlid);
	$('calendar').style.display = 'block';
	$('calendar').style.left = p['x']+'px';
	$('calendar').style.top	= (p['y'] + 20)+'px';
	_cancelBubble(event);
	refreshcalendar(currday.getFullYear(), currday.getMonth());
	if(lastcheckedyear != false) {
		$('calendar_year_' + lastcheckedyear).className = 'default';
		$('calendar_year_' + today.getFullYear()).className = 'today';
	}
	if(lastcheckedmonth != false) {
		$('calendar_month_' + lastcheckedmonth).className = 'default';
		$('calendar_month_' + (today.getMonth() + 1)).className = 'today';
	}
	$('calendar_year_' + currday.getFullYear()).className = 'checked';
	$('calendar_month_' + (currday.getMonth() + 1)).className = 'checked';
	$('hourminute').style.display = addtime ? '' : 'none';
	lastcheckedyear = currday.getFullYear();
	lastcheckedmonth = currday.getMonth() + 1;
}

function refreshcalendar(y, m) 
{
	var x = new Date(y, m, 1);
	var mv = x.getDay();
	var d = x.getDate();
	var dd = null;
	yy = x.getFullYear();
	mm = x.getMonth();
	$("year").innerHTML = yy;
	$("month").innerHTML = mm + 1 > 9  ? (mm + 1) : '0' + (mm + 1);

	for(var i = 1; i <= mv; i++) 
	{
		dd = $("d" + i);
		dd.innerHTML = "&nbsp;";
		dd.className = "";
	}

	while(x.getMonth() == mm) 
	{
		dd = $("d" + (d + mv));
		dd.innerHTML = '<a href="###" onclick="settime(' + d + ');return false">' + d + '</a>';
		if(x.getTime() < today.getTime() || (enddate && x.getTime() > enddate.getTime()) || (startdate && x.getTime() < startdate.getTime())) {
			dd.className = 'expire';
		} else {
			dd.className = 'default';
		}
		if(x.getFullYear() == today.getFullYear() && x.getMonth() == today.getMonth() && x.getDate() == today.getDate()) {
			dd.className = 'today';
			dd.firstChild.title = '<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['today']);?>
';
		}
		if(x.getFullYear() == currday.getFullYear() && x.getMonth() == currday.getMonth() && x.getDate() == currday.getDate()) {
			dd.className = 'checked';
		}
		x.setDate(++d);
	}

	while(d + mv <= 42) 
	{
		dd = $("d" + (d + mv));
		dd.innerHTML = "&nbsp;";
		d++;
	}

	if(addtime) 
	{
		$('hour').value = zerofill(hh);
		$('minute').value = zerofill(ii);
	}
}

function showdiv(id) 
{

	var p = getposition($(id));
	$('calendar_' + id).style.left = p['x']+'px';
	$('calendar_' + id).style.top = (p['y'] + 16)+'px';
	$('calendar_' + id).style.display = 'block';
}

function zerofill(s) 
{
	var s = parseFloat(s.toString().replace(/(^[\s0]+)|(\s+$)/g, ''));
	s = isNaN(s) ? 0 : s;
	return (s < 10 ? '0' : '') + s.toString();
}
loadcalendar();
<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 language="javascript">
var rownum=0;
function displayweek(obj)
{
	var table = document.getElementById("timetable");
	if(obj.options[obj.selectedIndex ].value==2)
	{
		var newRow = table.insertRow(-1);
		rownum = newRow.rowIndex;
		newRow.style.textAlign = "center";
		var newcell = newRow.insertCell(0);
		newcell.colSpan = 2;
		newcell.innerHTML ="<div class=\"bell_div\"><input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Sunday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Monday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Tuesday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Wednesday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Thursday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Friday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Saturday']);?>
";
		
		document.getElementById('startdate').disabled = false;
		document.getElementById('enddate').disabled = false;
	}
	else if(obj.options[obj.selectedIndex ].value==1)
	{
		if(rownum!=0)
		{
			table.deleteRow(rownum);
			rownum = 0;
		}
		
		document.getElementById('startdate').disabled = false;
		document.getElementById('enddate').disabled = false;
	}
	else if(obj.options[obj.selectedIndex ].value==3)
	{
		if(rownum!=0)
		{
			table.deleteRow(rownum);
			rownum = 0;
		}
		
		document.getElementById('startdate').disabled = true;
		document.getElementById('enddate').disabled = true;
	}
}
<?php echo '</script'; ?>
>
<!--对表单验证-->
<?php echo '<script'; ?>
 language="javascript">
var submitcount=0; 
function checkform()
{	
		if(!window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['enter_modify_sech']);?>
"))
		{
			return false;
		}	



	if (submitcount == 0){ 
    	 submitcount++; 
	} 
	else{
	 
		alert("<?php echo $_smarty_tpl->tpl_vars['modify_bell_scheme']->value['notsubmit'];?>
"); 
		return false; 
	} 
	//验证任务名
	if(isNull(document.getElementById('taskname').value))
	{
		document.getElementById('taskname_text').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['enter_scheme_name']);?>
</font>";
		document.getElementById('taskname').focus();
		submitcount=0;
		return false;
	}
	else
	{
		if(!isChinaOrNumbOrLett(document.getElementById('taskname').value))
		{
			document.getElementById('taskname_text').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['letter_number_Chinese']);?>
</font>";
			document.getElementById('taskname').focus();
			submitcount=0;
			return false;
		}
	}
	document.getElementById('taskname_text').innerHTML="<font class='terminal_star'></font>";  
	//获取声音
	document.getElementById('task_default_volume').value = trim(document.getElementById('volume_value').value);
	
	//验证开始时间和结束时间
	if(document.getElementById('startdate').value > document.getElementById('enddate').value)
	{
		document.getElementById('timecompare_text').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['smaller_than_end_date']);?>
</font>";
		document.getElementById('startdate').focus();
		submitcount=0;
		return false;
	}
	document.getElementById('timecompare_text').innerHTML="<font class='terminal_star'></font>";
	//验证执行模式
	
	var obj = document.getElementById('exemodel');
	var strnum="";

	if(obj.options[obj.selectedIndex].value==2)
	{
		for(var i=0;i<document.bellform.week.length;i++)
		{
			if(document.bellform.week[i].checked)
			{
				if(strnum=="")
				{
					strnum+=1;
				}
				else
				{
					strnum+=","+1;
				}
			}
			else
			{
				if(strnum=="")
				{
					strnum+=0;
				}
				else
				{
					strnum+=","+0;
				}
			}
		}
	
		var weektemp = strnum.split(",");
		var weektempcount = 0;
		for(var j=0; j<weektemp.length;j++)
		{
			weektempcount += weektemp[j]*1;
		}
		
		if(weektempcount == 0)
		{
			document.getElementById('exeModel_text').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['select_the_date']);?>
</font>";
			document.getElementById('exemodel').focus();
			submitcount=0;
			return false;
		}
		
		document.getElementById('hiddenweek').value = trim(strnum); 
		//alert(trim(document.getElementById('hiddenweek').value));
	}
	
	
	document.getElementById('exeModel_text').innerHTML="<font class='terminal_star'></font>";
	var selecttaskid="";
	var noselecttaskid="";
	var selecttaskidflag=0;
		for(var i=0;i<sign;i++)
		{
				if(sign==1)
				{			
					if(document.getElementById('belltaskid').checked)
					{
					selecttaskidflag++;
						if(selecttaskid=="")
						{
							selecttaskid+=document.getElementById('belltaskid').value;
						}
						else
						{
							selecttaskid+=","+document.getElementById('belltaskid').value;
						}
					}
					else
					{
						
						if(selecttaskid=="")
						{
							selecttaskid+=0;
						}
						else
						{
							selecttaskid+=","+0;
						}
					}
				}
				else
				{
				
					
					if(document.getElementsByName('belltaskid')[i].checked)
					{
						selecttaskidflag++;
						if(selecttaskid=="")
						{
							selecttaskid+=document.getElementsByName('belltaskid')[i].value;
						}
						else
						{
							selecttaskid+=","+document.getElementsByName('belltaskid')[i].value;
						}
					}
					else
					{
					
						if(selecttaskid=="")
						{
							selecttaskid+=0;
						}
						else
						{
							selecttaskid+=","+0;
						}
					}
				
				}
		}
		if(selecttaskidflag==0)
		{
			for(var m=0;m<sign;m++)
			{
					if(sign==1)
					{
						if(noselecttaskid=="")
						{
							noselecttaskid+=document.getElementById('belltaskid').value;
						}
						else
						{
							noselecttaskid+=","+document.getElementById('belltaskid').value;
						}
					}
					else
					{
						if(noselecttaskid=="")
						{
							noselecttaskid+=document.getElementsByName('belltaskid')[m].value;
						}
						else
						{
							noselecttaskid+=","+document.getElementsByName('belltaskid')[m].value;
						}
					}
			}
		}
		

	document.getElementById('hiddenbelltaskid').value = trim(selecttaskid); 
	document.getElementById('hiddenbellnotaskid').value = trim(noselecttaskid); 
	//验证动态添加的行中"课时名称"输入
	var getclasstimename="";
	if(sign==1)
	{
		if(document.getElementById('add').className == 1)
		{
			alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['add_task_bell_scheme']);?>
");
			submitcount=0;
			return false;
		}
		else
		{
		
			if(isNull(document.bellform.coursename.value))
			{
				document.getElementById('coursename_text').innerHTML="<font style='color:red;font-size:180%;'>*</font>";
				document.getElementById('coursename').focus();
				submitcount=0;
				return false;
			}
			if(!isChinaOrNumbOrLett(document.bellform.coursename.value))
			{
				document.getElementById('coursename_text').innerHTML="<font style='color:red;font-size:180%;'>*</font>";
				document.getElementById('coursename').focus();
				submitcount=0;
				return false;
			}
		//	document.getElementById('coursename_text').innerHTML="<font></font>";
			
			getclasstimename = trim(document.bellform.coursename.value);
		}
	}
	if(sign>1)
	{
		var countnum = 0;
		
		for(var i=0; i<document.getElementsByName('coursename').length;i++)
		{
		
				if(isNull(trim(document.bellform.coursename[i].value)))
				{
					document.getElementsByName('coursename_text')[i].innerHTML="<font style='color:red;font-size:180%;'>*</font>";
					document.bellform.coursename[i].focus();
					submitcount=0;
					return false;
				}
				else if(!isChinaOrNumbOrLett(trim(document.bellform.coursename[i].value)))
				{
					document.getElementsByName('coursename_text')[i].innerHTML="<font style='color:red;font-size:180%;'>*</font>";
					document.bellform.coursename[i].focus();
					submitcount=0;
					return false;
				}
			//	document.getElementsByName('coursename_text')[i].innerHTML="<font></font>";
				
				if(getclasstimename == "")
				{
					getclasstimename =trim(document.bellform.coursename[i].value);
				}
				else
				{
					getclasstimename += ","+trim(document.bellform.coursename[i].value);
				}
			}
		
	}
	else if(sign<=0)
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['add_task_bell_scheme']);?>
');
		submitcount=0;
		return false;
	}
	
	document.getElementById('hiddencoursename').value = trim(getclasstimename); 
	//alert(document.getElementById('hiddencoursename').value);
	//判断是否有重复的任务名称
	for(var k=0; k<document.getElementsByName('coursename').length-1; k++)
	{
		for(var z=k+1; z<document.getElementsByName('coursename').length; z++)
		{
		
			if(trim(document.getElementsByName('coursename')[k].value) == trim(document.getElementsByName('coursename')[z].value))
			{				
				alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['task_name_used']);?>
');
				submitcount=0;
				return false;
			}
		}
	}

	//获取打铃时间  获取铃声	获取播放时长
	var getbelltime = "";
	var getsetbellname = "";
	var getbelltimelength = "";
	var getbelltaskid = "";
	getsetbellname = document.getElementById('setbellname').value;
		
	var counttime = document.getElementById('belltiemlength').value;
	if(sign==1)
	{
		if(document.getElementById('add').className == 1)
		{
			alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['add_task_bell_scheme']);?>
');
			submitcount=0;
			return false;
		}
		else
		{
			getbelltime = document.getElementById('bellstarttime').value;
		
				//var temptime = (counttime[0]*60*60 + counttime[1]*60 + counttime[2]*1);
				getbelltimelength = counttime;
		
			getbelltaskid = document.getElementById('belltaskid').value;
		}
	}
	else if(sign >1)
	{
		var countnum = 0;
		for(var i=0;i<document.getElementsByName('bellstarttime').length;i++)
		{
		
				if(getbelltime=="" || getsetbellname=="" || getbelltimelength=="" || getbelltaskid=="")
				{
					getbelltime = trim(document.getElementsByName('bellstarttime')[i].value);
					
						//var temptime = (counttime[0]*60*60 + counttime[1]*60 + counttime[2]*1);
						getbelltimelength = counttime;
				
					getbelltaskid = document.getElementsByName('belltaskid')[i].value;				
				}
				else
				{
					getbelltime +=","+ trim(document.getElementsByName('bellstarttime')[i].value);
					//var temptime = (counttime[0]*60*60 + counttime[1]*60 + counttime[2]*1);
					getbelltimelength +=","+ counttime;
					getbelltaskid +=","+ document.getElementsByName('belltaskid')[i].value;
				}
			
		}
	}
	
	document.getElementById('hiddenbelltime').value = getbelltime;
	document.getElementById('hiddenbellname').value = getsetbellname;
	document.getElementById('hiddenbelltimelength').value = getbelltimelength;


	
	//验证终端是否有值
	var str = trim(tree3.getAllChecked());
	analysis_tree_terminal_group_string(str);
	document.getElementById('analysis_tree_group_string').value = trim(analysis_tree_group_string.toString()); 
	analysis_tree_group_string = new Array();
	
	//var strarray=str.split(",");
	//var temp=0;
	//var storearray=new Array();
	//for(var i=0;i<strarray.length;i++)
	//{
	//	if(isNumber(strarray[i]))
	//	{
	//		storearray[temp]=strarray[i];
	//		temp++;
	//	}
	//	continue;
	//}
	//document.getElementById('terminallistvalue').value=trim(storearray.toString());
	document.getElementById('terminallistvalue').value = trim(analysis_tree_terminal_string.toString());
	
	analysis_tree_terminal_string = new Array();
	/*
	if(isNull(document.getElementById('terminallistvalue').value))
	{
		if(noselecttaskid=="")
		{
			alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['select_terminals']);?>
");
			submitcount=0;
			return false;
		}
	}
	*/
	
	document.getElementById("submit").disabled=true;
	 setTimeout("djdd();", 100);
	//alert("terminallistvalue="+document.getElementById('terminallistvalue').value);
}
<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 language="javascript">
//整型转换时间
function timeconversion(timesnum)
{
 var timenum = timesnum;
 var hours = parseInt(timenum/(60*60));
 if(hours<10)
 {
  hours = "0"+hours+"";  
 }
 var minutes = parseInt((timenum - hours*60*60)/60);
 if(minutes<10)
 {
  minutes = "0"+minutes+"";  
 }
 var second = (timenum - hours*60*60 - minutes*60);
 if(second<10)
 {
  second = "0"+second+"";  
 }
 return (hours+":"+minutes+":"+second);
}
<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 language="javascript">
/*****************************
关联文件getonetaskterminal.php
*****************************/
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
		alert('Not Supported AJAX');
	}
} 

function merge_tree_terminal_group_string_s(group_id,terminal_id)
{

	return "stream_"+group_id+"::"+terminal_id;
}

function getonetaskterminals(url,getcurrenrownum)
{
   createXMLHttpRequest();
   
   xmlhttp.open("GET",url,true);
   
   xmlhttp.setRequestHeader('charset','utf-8'); 
  
   xmlhttp.onreadystatechange = function()
   { 
      if( xmlhttp.readyState == 4 )
      { 
         if( xmlhttp.status == 200 )
         {	
			 return displayonetaskterminal(trim(xmlhttp.responseText),getcurrenrownum);
         }
		 else
		 {
			alert('FAILED'); 
		 }
      }
   }
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	xmlhttp.setRequestHeader('Content-Type', "text/xml");
	xmlhttp.send(null);
}
var task_volume=80;
function displayonetaskterminal(strterminal,getcurrenrownum)
{
		
		var objroot=strterminal.split("#");
		var taskname = objroot[0];
		var prepower = objroot[1];
		task_volume = objroot[2];
		var startdate = objroot[3];
		var enddate = objroot[4];
		var info = objroot[5];
		var exemodel = objroot[6];
		
		var get_task_prority_str = objroot[7];
		var sendmode = objroot[8];
		
		var getterminalid = objroot[9];
	
		var getgroupid = objroot[10];
		var timelengthtype = objroot[12];
		var timelength = objroot[13];
		var mediaid = objroot[14];
		
	var mediainfo = document.getElementById('setbellname');
				
	for(var i=0; i<mediainfo.options.length; i++)
	{
		if(mediainfo.options[i].value == mediaid)
		{
			mediainfo.options[i].selected = true;	
		}
	}	

	if(timelengthtype==2)
	document.getElementById('belltiemlength').value=timelength;	
	else
	document.getElementById('belltiemlength').value=time_tran_format(timelength);	
	if(getterminalid == "")
	{
		if(tree3 != null)
		{
			var preobj = document.getElementById('prepower');
			
			for(var i=0; i<preobj.options.length; i++)
			{
				if(preobj.options[i].value == prepower)
				{
					preobj.options[i].selected = true;	
				}
			}
			document.getElementById('task_default_volume').value = task_volume;
			
			document.getElementById('volume_value').value = task_volume;
			//document.getElementById('belltiemlength').value = gettimelength;
			document.getElementById('startdate').value = startdate;
			
			document.getElementById('enddate').value = enddate;
			document.getElementById('datasendmodel').options[sendmode].selected =true ;
			
			var get_bell_modify_select_priority_obj = document.getElementById('task_priority_text');
				
			for(var priority_index_value = 0; priority_index_value < get_bell_modify_select_priority_obj.options.length; priority_index_value++)
			{
					if(get_bell_modify_select_priority_obj.options[priority_index_value].value == get_task_prority_str)
					{
						get_bell_modify_select_priority_obj.options[priority_index_value].selected = true;
						break;
					}
			}
			
			var count = 0;
			var modelobj = document.getElementById('exemodel');
			if(modelobj.options[1].selected == true)
			{
				var tableobj = document.getElementById("timetable");
				var totalrow = tableobj.rows.length;
				tableobj.deleteRow(totalrow-1);

				for(var i=0;i<exemodel.length;i++)
				{
					 if(exemodel.charAt(i)=="1")
					 {
						count++;
					 }
				}
				if(count == 7)
				{
					modelobj.options[0].selected = true;
				}
				if(count != 7)
				{
					modelobj.options[1].selected = true;
					displayweek(modelobj);
					for(var i=0;i<exemodel.length;i++)
					{
						if(exemodel.charAt(i)=="1")
						{
							document.getElementsByName('week')[i].checked = true;
						}
					}
				}
			}
			tree3.destructor();			
			tree3=new dhtmlXTreeObject("terminallist","100%","100%",0);
			tree3.setSkin('dhx_skyblue');
			tree3.setImagePath("smarty/templates/BellManager/codebase/csh_bluebooks/");
			tree3.enableCheckBoxes(1);
			tree3.enableThreeStateCheckboxes(true);
            tree3.setOnCheckHandler(toncheck);
			tree3.loadXMLString(treedata);
		}
	}
	else if(getterminalid != "")
	{
		var selectterminalid = getterminalid.split(",");
		
		var getgroup_ids = getgroupid.split(",");
		
		if(tree3 != null)
		{
			tree3.destructor();
			tree3=new dhtmlXTreeObject("terminallist","100%","100%",0);
			tree3.setSkin('dhx_skyblue');
			tree3.setImagePath("smarty/templates/BellManager/codebase/csh_bluebooks/");
			tree3.enableCheckBoxes(1);
			tree3.enableThreeStateCheckboxes(true);
            tree3.setOnCheckHandler(toncheck);
			tree3.loadXMLString(treedata);
	
				document.getElementById('taskname').value = info;
				
				var preobj = document.getElementById('prepower');
				
				for(var i=0; i<preobj.options.length; i++)
				{
					if(preobj.options[i].value == prepower)
					{
						preobj.options[i].selected = true;	
					}
				}
				document.getElementById('task_default_volume').value = task_volume;	
				document.getElementById('volume_value').value = task_volume;
				document.getElementById('startdate').value = startdate;
				document.getElementById('enddate').value = enddate;
				document.getElementById('datasendmodel').options[sendmode].selected =true ;
				var get_bell_modify_select_priority_obj = document.getElementById('task_priority_text');
				
				for(var priority_index_value = 0; priority_index_value < get_bell_modify_select_priority_obj.options.length; priority_index_value++)
				{
						if(get_bell_modify_select_priority_obj.options[priority_index_value].value == get_task_prority_str)
						{
							get_bell_modify_select_priority_obj.options[priority_index_value].selected = true;
							break;
						}
				}

				var count = 0;
				
				var modelobj = document.getElementById('exemodel');
				
				if(modelobj.options[1].selected == true)
				{
					var tableobj = document.getElementById("timetable");
					var totalrow = tableobj.rows.length;
					tableobj.deleteRow(totalrow-1);
					
					for(var i=0;i<exemodel.length;i++)
					{
						 if(exemodel.charAt(i)=="1")
						 {
							count++;
						 }
					}
					if(count == 7)
					{
						modelobj.options[0].selected = true;
					}
					if(count != 7)
					{
						modelobj.options[1].selected = true;
						displayweek(modelobj);
						for(var i=0;i<exemodel.length;i++)
						{
							if(exemodel.charAt(i)=="1")
							{
								document.getElementsByName('week')[i].checked = true;
							}
						}
					}
				}
				else if(modelobj.options[0].selected == true)
				{
					
					for(var i=0;i<exemodel.length;i++)
					{
						 if(exemodel.charAt(i)=="1")
						 {
							count++;
						 }
					}
					if(count == 7)
					{
						modelobj.options[0].selected = true;
					}
					if(count != 7)
					{
						modelobj.options[1].selected = true;
						
						displayweek(modelobj);
						
						for(var i=0;i<exemodel.length;i++)
						{
							if(exemodel.charAt(i)=="1")
							{
								document.getElementsByName('week')[i].checked = true;
							}
						}
					}
					
				}
				
				document.getElementsByName('add')[getcurrenrownum-1].value = "修改";
				
				for(var i=0; i<selectterminalid.length; i++)
				{
					//tree3.setCheck(""+selectterminalid[i]+"",true);

					tree3.setCheck(merge_tree_terminal_group_string_s(getgroup_ids[i],selectterminalid[i]),true);
				}
		//	document.getElementsByName('belltiemlength')[getcurrenrownum-1].value=gettimelength;			
			
			
			document.getElementsByName('coursename')[getcurrenrownum-1].disabled = false;
			
			document.getElementsByName('bellstarttime')[getcurrenrownum-1].disabled = false;
			
			
			
			document.getElementsByName('add')[getcurrenrownum-1].disabled = false;
		}
	}
}

function getenablemedia()
{
 var s = document.getElementById("setbellname");
	if(document.getElementById('enablemedia').checked==true)
	{
		s.disabled = false;
		
	}
	else
	{
		 s.disabled = true;
		
	}
}

function getenablemedialengh()
{
 var s = document.getElementById("belltiemlength");
	if(document.getElementById('enablebelllength').checked==true)
	{
		s.disabled = false;
		
	}
	else
	{
		 s.disabled = true;
		
	}
}

function getenableterminallist()
{
	if(document.getElementById('enableterminallist').checked!=true)
	{
		disabletree(true);
	}
}

<?php echo '</script'; ?>
>
</head>
<body>

<form class="terminal_form_to_body" name="bellform" method="post" action="do.php?act=belltaskallmodify&taskid=<?php echo $_smarty_tpl->tpl_vars['taskid']->value;?>
" onSubmit="return checkform();">
  <table width="780" border="0" align="center" cellpadding="0" cellspacing="0" class="terminal_table_border">
  
    <tr>
      <td colspan="2">
	  	<img src="<?php echo $_smarty_tpl->tpl_vars['modify_bell_scheme']->value['modify_bell_imageall'];?>
"/>
	  </td>
    </tr>
	
	<tr>
		<td colspan="2" class="bell_basic_param">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Task_configuration']);?>

		</td>
	</tr>
	
    <tr>
      <td  width="50%" align="left" valign="top" class="fileadm_frame_border">
	  
	  <table width="100%" border="0">
	  
          <tr>
            <td nowrap class="belll_table_col_rightalign">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Solution_name']);?>

			</td>
			
            <td nowrap class="bell_talbe_col_leftalign">
				<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Task_Name_task']);?>
" name="taskname" maxlength="8" type="text" id="taskname"  value="<?php echo $_smarty_tpl->tpl_vars['bellinfo']->value['info'];?>
"/>
				<span class="terminal_star" id="taskname_text">*</span>			
			</td>
          </tr>
		 <!-- 
			<tr>
				<td class="belll_table_col_rightalign">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Solution_task']);?>

				</td>
				<td class="bell_talbe_col_leftalign">
<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['add_schemetask']);?>
" type="button" class="bell_button_style" onClick="insertRowInTable('coursetable')" id="addrow" name="addrow" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Add_Task']);?>
"/>
				</td>
			</tr>
			-->
			<tr>
			<td class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Pre_power_on']);?>
</td>
			<td class="bell_talbe_col_leftalign">
			<p>
				<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Pre_power_on_task']);?>
" class="terminal_select_style" name="prepower" id="prepower" style="width:75px;">
				<?php echo '<script'; ?>
>
					var i=0;
						while(1)
						{
							document.write("<option value='"+i+"'>"+i+"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Second']);?>
</option>");
							i+=5;
							if(i>=59)
							break;
						}
								<?php echo '</script'; ?>
>	
				
					<option value="60">1 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Minute']);?>
</option>
					<option value="120" >2 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Minute']);?>
</option>
					<option value="180">3 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Minute']);?>
</option>
					<option value="240">4 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Minute']);?>
</option>
					<option value="300">5 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Minute']);?>
</option>	
				</select>
				 <?php echo '<script'; ?>
 language="javascript" defer="true">
				 	var obj = document.getElementById('prepower');
					for(var i=0;i<obj.options.length;i++)
					{
						if(obj.options[i].value ==<?php echo $_smarty_tpl->tpl_vars['bellinfo']->value['prepower'];?>
)
						{
							obj.options[i].selected = true;
						}
					}
	
							 <?php echo '</script'; ?>
>	
<!--添加优先级开始-->					

<?php echo $_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Task_level'];?>



<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Task_level_task']);?>
" name="task_priority_text" id="task_priority_text" style="width:60px;">
	 <?php echo '<script'; ?>
 language="javascript">
			var level=0;
			for(level=<?php echo $_smarty_tpl->tpl_vars['getlevel']->value;?>
;level<=109;level++)
			{
			  
			   document.write("<option value='"+level+"'>"+level+"</option>");
			}
				 var obj = document.getElementById('task_priority_text');
					for(var i=0;i<obj.options.length;i++)
					{
						if(obj.options[i].value == <?php echo $_smarty_tpl->tpl_vars['bellinfo']->value['priority'];?>
)
						{
							obj.options[i].selected = true;
						}
					}
		/*var level=0,level2=0;
		var getlevel=parseInt(<?php echo $_smarty_tpl->tpl_vars['bellinfo']->value['priority'];?>
/10);
		if(getlevel==null||getlevel=="")
		{
			for(level=0;level<10;level++)
			{
			 getlevel=<?php echo $_smarty_tpl->tpl_vars['getlevel']->value;?>
+level;
			document.write("<option value='"+getlevel+"'>"+getlevel+"</option>");
			}
			
		}
		else
		{
			for(level2=getlevel*10;level2<=(getlevel*10+9);level2++)
			{
			   document.write("<option value='"+level2+"'>"+level2+"</option>");
			}
		}
	*/
  <?php echo '</script'; ?>
>
</select>
	
</p>
				
			
			</td>
			  <td align="left" style="font-size:12px"><?php echo $_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Task_levelhigh'];?>
</td>
          </tr>
		     <?php echo '<script'; ?>
 type="text/javascript" src="skin/js/frame/slider.js"><?php echo '</script'; ?>
>
		  <tr>
		  	<td class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Volume']);?>
</td>
				<input type="hidden" name="task_default_volume" id="task_default_volume" value="0"/>
		
				<td  class="bell_talbe_col_leftalign">
					<?php echo '<script'; ?>
 language="JavaScript">
						var A_TPL6h = {
							'b_vertical' : false,
							'b_watch': true,
							'n_controlWidth': 149,
							'n_controlHeight': 17,
							'n_sliderWidth': 9,
							'n_sliderHeight': 17,
							'n_pathLeft' : 1,
							'n_pathTop' : 0,
							'n_pathLength' : 138,
							's_imgControl': 'skin/images/frame/sldr5h_bg.gif',
							's_imgSlider': 'skin/images/frame/sldr5h_sl.gif',
							'n_zIndex': 1
						}
						var A_INIT6h = {
							's_form' : 0,
							's_name': 'volume_value',
							'n_minValue' : 1,
							'n_maxValue' : 100,
							'n_value' : 80,
							'n_step' : 1
						}
					
						new slider(A_INIT6h, A_TPL6h);
					<?php echo '</script'; ?>
>
					<td>
					<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['task_Volumetask']);?>
" name="volume_value" id="volume_value" value="80" readonly="true" type="Text" size="3" onChange="A_SLIDERS[5].f_setValue(this.value)">
					
					</td>
	
		  </tr>
		 	<tr>
			<td nowrap class="belll_table_col_rightalign">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['sendmode']);?>

			</td>
			  <td nowrap class="bell_talbe_col_leftalign">
			<select class="terminal_select_style" name="datasendmodel" id="datasendmodel" style="width:65px;">
                <option value="0"><?php echo $_smarty_tpl->tpl_vars['modify_bell_scheme']->value['oneplay'];?>
</option>
                <option value="1"><?php echo $_smarty_tpl->tpl_vars['modify_bell_scheme']->value['twoplay'];?>
</option>
            </select>
			 <?php echo '<script'; ?>
 language="javascript" defer="true">
				 var obj = document.getElementById('datasendmodel');
					for(var i=0;i<obj.options.length;i++)
					{
						if(obj.options[i].value == <?php echo $_smarty_tpl->tpl_vars['bellinfo']->value['datasendmodel'];?>
)
						{
							obj.options[i].selected = true;
						}
					}
			 <?php echo '</script'; ?>
>	
            </td>
			</tr>
			

      </table>
</td> 
<td width="50%" align="left" valign="top" class="fileadm_frame_border">
<table width="100%"  id="timetable" name="timetable" border="0" cellspacing="0" cellpadding="0">

<tr>
	<td nowrap class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Start_Date']);?>
</td>
	<td nowrap class="bell_talbe_col_leftalign">
<!--添加日期-->
<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Start_Datetask']);?>
" class="bell_input_style" name="startdate" id="startdate"  value="<?php echo $_smarty_tpl->tpl_vars['bellinfo']->value['startdate'];?>
" type="text" value="" size="14" readonly="readonly" onClick="showcalendar(event, this);" onFocus="showcalendar(event, this);if(this.value=='0000-00-00')this.value=''" />
	</td>
</tr>

<tr>
	<td nowrap class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['End_Date']);?>
</td>
	<td nowrap class="bell_talbe_col_leftalign">

<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['End_Datetask']);?>
" class="bell_input_style" name="enddate" id="enddate"  type="text" value="<?php echo $_smarty_tpl->tpl_vars['bellinfo']->value['enddate'];?>
" size="14" readonly="readonly" onClick="showcalendar(event, this);" onFocus="showcalendar(event, this);if(this.value=='0000-00-00')this.value=''" />
<span class="terminal_star" id="timecompare_text"><font size="-1">*</font></span>	
<?php echo '<script'; ?>
 language="javascript">
function getNowFormatDate()
{
   var day = new Date();
   var Year = 0;
   var Month = 0;
   var Day = 0;
   var CurrentDate = "";
   //初始化时间
   Year= day.getFullYear();
   Month= day.getMonth()+1;
   Day = day.getDate();
   CurrentDate += Year + "-";
   if (Month >= 10 )
   {
      CurrentDate += Month + "-";
   }
   else
   {
      CurrentDate += "0" + Month + "-";
   }
   if (Day >= 10 )
   {
      CurrentDate += Day ;
   }
   else
   {
      CurrentDate += "0" + Day ;
   }
   return CurrentDate;
}
//document.getElementById('startdate').value = getNowFormatDate();
//document.getElementById('enddate').value = getNowFormatDate();
<?php echo '</script'; ?>
>
	</td>
</tr>

<tr>
	<td nowrap class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Run_mode']);?>
</td>

	<td nowrap class="bell_talbe_col_leftalign">
		<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Run_modetask']);?>
" class="terminal_select_style" name="exemodel" id="exemodel" onChange="displayweek(this)">
			<option value="1" selected="selected"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Every_day']);?>
</option>
			<option value="2"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Every_week']);?>
</option>
			
		</select>
		 
		<?php echo '<script'; ?>
 language="javascript" defer="true">
	var getstr = "<?php echo $_smarty_tpl->tpl_vars['bellinfo']->value['exemodel'];?>
";
	
	var count = 0;
	var count_i = 0;
	var obj = document.getElementById('exemodel');
	for(var i=0;i<getstr.length;i++)
	{
		 if(getstr.charAt(i)=="1")
		 {
			count++;
		 }
		if(count == 7)
		{
			obj.options[0].selected = true;
		}
		if(getstr.charAt(i)=="0")
		{
			count_i++;
		}
		if(count_i == 7)
		{
			obj.options[2].selected = true;
			document.getElementById('starthour').disabled = true;
			document.getElementById('startmin').disabled = true;
			document.getElementById('startsenc').disabled = true;
			document.getElementById('startdate').disabled = true;
			document.getElementById('enddate').disabled = true;
		}
	}
	if( count != 7 && count_i != 7 )
	{
		obj.options[1].selected = true;
		displayweek(obj);
		for(var i=0;i<getstr.length;i++)
		{
			if(getstr.charAt(i)=="1")
			{
				document.getElementsByName('week')[i].checked = true;
			}
		}
	}
<?php echo '</script'; ?>
>

		<span class="terminal_star" id="exeModel_text"><font size="-1">*</font></span>	
	</td>
</tr>
</table>
	  
	</td>
</tr>
<tr>
	<td colspan="2">
	<table class="bell_sub_table">
		<tr class="bell_sub_table_row">
			<td width="5%" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['No']);?>
</td>
			<td width="15%" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Lesson_name']);?>
</td>
			<td width="15%" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Bell_time']);?>
</td>
			<td width="25%" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Bell_music']);?>
<select class="terminal_select_style"  name="setbellname" id="setbellname" disabled="disabled" style="width:125px; color:#000000" onChange="get_allmedia_time_length(this)">
			  <?php
$__section_medialist_0_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_medialist']) ? $_smarty_tpl->tpl_vars['__smarty_section_medialist'] : false;
$__section_medialist_0_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['medialist']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_medialist_0_total = $__section_medialist_0_loop;
$_smarty_tpl->tpl_vars['__smarty_section_medialist'] = new Smarty_Variable(array());
if ($__section_medialist_0_total != 0) {
for ($__section_medialist_0_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_medialist']->value['index'] = 0; $__section_medialist_0_iteration <= $__section_medialist_0_total; $__section_medialist_0_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_medialist']->value['index']++){
?>
                <option value="<?php echo $_smarty_tpl->tpl_vars['medialist']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_medialist']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_medialist']->value['index'] : null)]['id'];?>
"  label="<?php echo $_smarty_tpl->tpl_vars['medialist']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_medialist']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_medialist']->value['index'] : null)]['name'];?>
" style="color:#000000"><?php echo $_smarty_tpl->tpl_vars['medialist']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_medialist']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_medialist']->value['index'] : null)]['name'];?>
</option>
             <?php
}
}
if ($__section_medialist_0_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_medialist'] = $__section_medialist_0_saved;
}
?>
              </select><input  name="enablemedia" type="checkbox" id="enablemedia" value="1" onChange="getenablemedia()"/></td>
			<td width="15%" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Duration']);?>
<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['select_musictimetask']);?>
" name="belltiemlength" value='00:00:00' type="text" id="belltiemlength" readonly="true" size="10" onClick="Playlenthselect(this)"/><input  name="enablebelllength" type="checkbox" id="enablebelllength" value="1" onChange="getenablemedialengh();"/> </td>
			<td width="15%" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Compile']);?>
</td>
			
		</tr>
	</table>
	</td>
	</tr>
<tr>
	<td colspan="2">
	<div id="divTest" style="width:100%;overflow-x:scroll;overflow-y:scroll">
	<table id="coursetable" name="coursetable" class="bell_sub_table">
		<tr >
			<td width="5%" height="1" nowrap="nowrap"></td>
			<td width="15%"  height="1" nowrap="nowrap" align="center"></td>
			<td width="15%"  height="1" nowrap="nowrap" align="center"></td>
			<td width="25%"  height="1" nowrap="nowrap" align="center"></td>
			<td width="15%"  height="1" nowrap="nowrap" align="center"></td>
			<td width="15%"  height="1" nowrap="nowrap" align="center"></td>
		</tr>
	</table>
	</div>
	</td>
</tr>
<tr class="colors">
<td height="" colspan="2" align="left" valign="top">       
<div class="bell_tree_div"><?php echo $_smarty_tpl->tpl_vars['Belladdtask']->value['Terminal_List'];?>
<input  name="enableterminallist" type="checkbox" id="enableterminallist" value="1" onChange="getenableterminallist()"/></div>

<div id="terminallist" name="terminallist" class="bell_tree"  style="overflow-y:auto;overflow-x:auto;z-index:1;"></div>
	<input type="hidden"  id="selecttaskid" name="selecttaskid" value=""/>
	<input type="hidden"  id="terminallistvalue" name="terminallistvalue" value=""/>
	<input type="hidden"  id="hiddenweek"       name="hiddenweek" value=""/>
	<input type="hidden"  id="hiddencoursename" name="hiddencoursename" value=""/>
	<input type="hidden"  id="hiddenbelltime"   name="hiddenbelltime" value=""/>
	<input type="hidden"  id="hiddenbellname" name="hiddenbellname" value="">
	<input type="hidden"  id="hiddenbelltimelength" name="hiddenbelltimelength" value="">
	<input type="hidden"  id="hiddenbelltaskid" name="hiddenbelltaskid" value=""/>
	<input type="hidden"  id="hiddenbellnotaskid" name="hiddenbellnotaskid" value=""/>
	<input type="hidden"  id="taskType" name="taskType" value="belltask"/>
	<input type="hidden"  id="get_terst" name="get_terst" value=""/>
	<input type="hidden"  id="get_belltaskid" name="get_belltaskid" value=""/>
	<input type="hidden"  id="get_id" name="get_id" value=""/>
	<input type="hidden"  id="selectnum" name="selectnum" value=""/>
	<input type="hidden"  id="get_inid" name="get_inid" value=""/>
	<input type="hidden"  id="get_noid" name="get_noid" value=""/>
	<input type="hidden"  id="get_terminal" name="get_terminal" value=""/>
	<input type="hidden" id="analysis_tree_group_string" name="analysis_tree_group_string" value=""/>
	
	<!--在此保存媒体列表的值-->
	  <?php echo '<script'; ?>
 language="javascript" defer="true">
                       
					var te = 0;
					var get_noid="";
					var states = 0;
					var get_terst ="";
					var get_inid ="";
					var get_id ="";
					var x=200;
					var y=300;
					 var get_position ="";
					  var get_position2 ="";
					    var treeItemText = "";
					//  var get_text2 = "IP功放";
					 var get_text2 = trim("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['amplifier']);?>
");
				      var get_text3 = new RegExp(get_text2);
					   // var get_amplifier = "IP前置";
					   var get_amplifier = trim("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['lead']);?>
");
				      var get_amplifier2 = new RegExp(get_amplifier);
					    var terminal = trim("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['terminal']);?>
");
				      var terminal2 = new RegExp(terminal);
					    function mouseMove(ev) 
						{ 
							ev= ev || window.event; 
							var mousePos = mouseCoords(ev); 
							x=mousePos.x;
							y=mousePos.y;
						} 
						
						function mouseCoords(ev) 
						{ 
						if(ev.pageX || ev.pageY){ 
						return {x:ev.pageX, y:ev.pageY}; 
						} 
						}

						function getareavalues(urls)
							{
							createXMLHttpRequest();
							xmlhttp.open( "get",urls, false );
							xmlhttp.onreadystatechange = function()
							{
								if( xmlhttp.readyState == 4 )
								{
									if( xmlhttp.status == 200 )
									{
										if(!isNull(xmlhttp.responseText))
										{
											
											areanum = xmlhttp.responseText; 
										}
													
										
									}
								}
							}
								xmlhttp.setRequestHeader( "If-Modified-Since", "0");
								xmlhttp.send(null);
								return areanum;
							}	
					function toncheck(id, state) 
					{
					 te = id;
				     var get_ids="";
					 if(document.getElementById('enableterminallist').checked!=true)
					 {
					 	disabletree(true);
						return;
					 }
					
					  treeItemText = tree3.getItemText(id);
					
					if(id.length==8||id.length==9||id.length==10)
					{
					if(state==0)
					{
					 get_noid=get_noid+(te+"|");
					
					 }
					 else
					 {
					 get_noid = get_noid.replace(te+"|","");
					 }
					 document.getElementById('get_noid').value = trim(get_noid.toString());
					}
					else
					{
					 if(state ==1)
					 {
					    document.getElementById('lead').style.display = "none";
						  get_id = get_id.replace(te+"|","");
					//   document.getElementById('get_id').value = trim(get_id.toString()); 
						get_inid+=te+'|';
						var position =new Array;
						var position2 =new Array;
						var position3 =new Array;
						for(z=0;z<get_inid.length;z++)
						{
						//alert(z);
						if(get_inid.substring(z,z+2)=="::")
						{
							position=z+2;
		
						}
						if(get_inid.substring(z,z+1)=="|")
						{
						position2=z;
						if(position2-position==1)
						{
					     position3+=0;
						 }
						 else
						 {
						position3+=position2-position;
						}
						}
						
						}
						get_position =position3;
					
							//alert(get_position);
							//document.getElementById('get_inid').value = trim(get_inid.toString());
						
						//alert(document.getElementById('get_inid').value);	
						
					//  alert(get_inid.split("stream_"));
					 }
					 else if(state==0)
					 {
						var get_te="";
						
						get_id+=te+"|";
						   document.getElementById('lead').style.display = "none";
					 
					 for(i=0;i<get_position.length;)
						{
						get_te =te.substring(10).length;
					 //   alert(get_te);
						if(get_te ==1)
						{
						get_te =0;
						}
                       
						if(get_position.substring(i,i+1)==get_te)
						{
						var get_ids =  get_position.substring(i,i+1);
							
						get_position = get_position.replace(get_ids,"");
						
						}
						  i+=1;  
					}
					//	get_id +=te;
					document.getElementById('get_id').value = trim(get_id.toString());
			
						 get_inid = get_inid.replace(te+'|',"");
					  // document.getElementById('get_inid').value = trim(get_inid.toString()); 
					
				
					 }
					  }
				  var str_text=new Array();
				  str_text=id.split("::");
				 var strs_text ="";
				for (var i=1;i<2 ;i++ )   
    			{   
      			 strs_text=str_text[i]; 
			
				var url = "get_terminaltype.php?id="+strs_text+"";
	
   				}
				getchannelvalue(url);
				var urls = "get_terminalarea.php?id="+document.getElementById('get_belltaskid').value+"&terminalid="+strs_text+"";
				
				getareavalues(urls);	
			 
				if(state == 1)
				 {
				  if(channelnum%2==0 &&channelnum!=0)
				 {
				  	display();
					if(navigator.appName.indexOf("Explorer") > -1)   
						{
							var mouse_obj_xy = get_mouse_coordinates();
							get_div_obj('lead').style.left = mouse_obj_xy.x+120+'px';
							get_div_obj('lead').style.top = mouse_obj_xy.y-20+'px';
							get_div_obj('lead').style.display = "block";
						}
					else
						{
							document.onclick = mouseMove;
							get_div_obj('lead').style.left = x+120+'px';
							get_div_obj('lead').style.top = y-20+'px';
							get_div_obj('lead').style.display = "block";
						}
					}
					var b="";
						b=trim(areanum);
						for(var a=0;a<16;a++)
							{
								if(b.charAt(a)=="1")
								{
									document.getElementsByName('lead2')[a].checked = true;
								}
							}
					
					     states = state ;
						get_terst+=te+'|';
						
						
				 }
				 else if(state==0)
				 {
			       get_terst = get_inid.replace(te+'|',"");
			     
				// alert(get_terminal);
				   if(document.getElementById('lead').style.display == "block")
							{
								document.getElementById('lead').style.display = "none";
							}
				  states = state ;
				 // alert(get_terminal);
				  var bit_position =new Array;
				  var bit_position2 =new Array;
				  var bit_position3 =new Array;
				 for(z=0;z<get_terminal.length;z++)
				 {
				   if(get_terminal.substring(z,z+2)=="::")
						{
						bit_position=z+2;
						}
					if(get_terminal.substring(z,z+1)=="|")
					{
					
					bit_position2=z;
				
				
					bit_position3+=bit_position2-bit_position;
				
					

					}
					
				
				 }
			     var te_len="";
				 var get_te="";
				 var te_len2="";
				 //  alert(bit_position3);
				get_te="["+te+"|";
					  
				for(l=0;l<get_terminal.length;)
				{	
				//	alert(l);
						
						for(i=0;i<bit_position3.length;)
						{
							
						  	//  alert(get_te);
						 	te_len = bit_position3.substring(i,i+1);
							//  te_len = te.substring(10).length;
							// alert(te_len);
							// alert(get_terminal);
							//alert(l+12+parseInt(te_len));
							te_len2 =get_terminal.substring(l,l+12+parseInt(te_len));
							// alert(te_len2);
							// alert(get_te);
							if(get_terminal.substring(l,l+12+parseInt(te_len))==get_te)
							{
						   
								
								var get_terminals =  get_terminal.substring(l,l+12+parseInt(te_len)+12);
								
								get_terminal = get_terminal.replace(get_terminals,"");
							//	 document.getElementById('get_terminal').value = trim(get_terminal.toString());
								
								l = get_terminal.length;
								break;
							}
							i+=1;
						}
						for(j =0;j<get_terminal.length-l;j++)
						{
							//alert(get_terminal.substring(l+j,l+j+1));
					    	if(get_terminal.substring(l+j,l+j+1)==']')
							{
								l+=j+1;
								break;
							}
						}
						if(j>=get_terminal.length-l)
							break;
				}


				 } 

			};
					

			var get_terminal="";
			function set_task_volume_prepose()
			{
            
			   var get_prepose ="";
			
	                var getItem=te;
				
					if(getItem==null||getItem=="")
					{
						//alert(states);
						alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['select_broadcast_task']);?>
");
						return void(0);
					}
					else
					{
					
							
							for(i=0;i<document.getElementsByName('lead2').length;i++)
							{
							   if(document.getElementsByName('lead2')[i].checked)
							   {
								  if(get_prepose=="")
									{
										get_prepose+=1;
									}
									else
									{
										get_prepose+=","+1;
									}
									

							   }
							   else
							   {
							   if(get_prepose=="")
									{
										get_prepose+=0;
									}
									else
									{
										get_prepose+=","+0;
									}
							   }
							   
							//  if(document.getElementsByName('IP_amplifier')[i].checked)
							 // {
							  
							//  }
							}
						if(16-document.getElementsByName('lead2').length>0)
						{
							for(i=0;i<16-document.getElementsByName('lead2').length;i++)
							{
								get_prepose+=","+0;
							}
						}
						
						var get_ter = [[getItem]+'|'+[get_prepose]];
								 
					 get_terminal = get_terminal +'['+ [[getItem]+'|'+[get_prepose]] +']';
				    // alert(get_ter);
						 //  alert(get_terst);
							//alert(get_terminal);

                          document.getElementById('get_terminal').value = trim(get_terminal.toString());
						  // alert(get_terminal);
						//  alert(document.getElementById('get_terminal').value);
						   document.getElementById('lead').style.display = "none";
						
											
					
					}
				
			
             };
		
				
		 function disappear_volume_div_prepose()
				{
					if(document.getElementById('lead').style.display == "block")
					{
						document.getElementById('lead').style.display = "none";
					}
				};
				
		function disabletree(flag)
		{
			var trees=	tree3.getAllChildless();
			var terarry=trees.split(",");
			for(var k=0;k<terarry.length;k++)
			{
			 tree3.setCheck(terarry[k],false);		
			}
			disableCheckbox(tree3.getSelectedItemId(),true);
		}		
				
		tree3=new dhtmlXTreeObject("terminallist","100%","100%",0);
		tree3.setSkin('dhx_skyblue');
	
		tree3.setImagePath("smarty/templates/BellManager/codebase/csh_bluebooks/");
		tree3.enableCheckBoxes(1);

		tree3.enableThreeStateCheckboxes(true);
		tree3.setOnCheckHandler(toncheck);
	
		var treedata = "<?php echo $_smarty_tpl->tpl_vars['terminalist']->value;?>
";	
		tree3.loadXMLString(treedata);
		
		
		function getselectall(obj)
		{
			for(var i=0;i<document.bellform.belltaskid.length;i++)
			{
				document.bellform.belltaskid[i].checked =true;
			
			}
		
		}
		
		function getcancelall(obj)
		{
			for(var i=0;i<document.bellform.belltaskid.length;i++)
			{
				document.bellform.belltaskid[i].checked =false;
			
			}
		
		
		}
		
	<?php echo '</script'; ?>
>
  </td>
</tr>
<tr>
  <td colspan="2" align="center" valign="middle">
  <div style=" margin-top:5px; margin-bottom:5px; text-align:center; width:780px">
	<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['submit_task']);?>
" name="submit" id="submit" type="submit" class="terminal_button" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Modify']);?>
"/>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input name="selectall" type="button" class="terminal_button" id="selectall" onClick="getselectall(this);" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Select_All']);?>
"/>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input name="selectall" type="button" class="terminal_button" id="cancelall" onClick="getcancelall(this);" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Cancel']);?>
"/>
<!--	
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input name="reset" type="reset" class="terminal_button" id="reset" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Cancel']);?>
"/>
-->
  </div>	
  </td>
</tr>
</table>

<?php echo '<script'; ?>
 language="javascript">
			document.getElementById('belltiemlength').disabled = true;
			document.getElementById('setbellname').disabled = true;
		//  document.getElementById("terminallist").style.display="none";
			disabletree(false);
			<?php echo '</script'; ?>
>


<?php echo '<script'; ?>
 language="javascript">

 var row3=0;
function display()
{
   
	var get_tables = document.getElementById("get_areas");
    
	for(var get_num = get_tables.rows.length-1;get_num>=0;get_num--)
	{
	
	   get_tables.deleteRow(get_num);
	   
	}  
	
	var get_newRow;
	var get_newcell;
	var getcell="";
    var row;
	var i=0;
		get_newRow = get_tables.insertRow(row);
		get_newRow.style.textAlign = "left";
		for( i=0;i<channelnum;i++)
		{
			switch(i)
			{
				case 0:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_1']);?>
";
				break;
				case 1:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_2']);?>
";
				break;
				case 2:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_3']);?>
";
				break;
				case 3:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_4']);?>
";
				break;
				case 4:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_5']);?>
";
				break;
				case 5:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_6']);?>
";
				break;
				case 6:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_7']);?>
";
				break;
				case 7:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_8']);?>
";
				break;
				case 8:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_9']);?>
";
				break;
				case 9:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_10']);?>
";
				break;
				case 10:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_11']);?>
";
				break;
				case 11:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_12']);?>
";
				break;
				case 12:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_13']);?>
";
				break;
				case 13:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_14']);?>
";
				break;
				case 14:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_15']);?>
";
				break;
				case 15:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['zone_16']);?>
";
				break;
			
			}
			if(i%2!=0)
				getcell+="\n";
		}
		if(i==channelnum)
		getcell+="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Sumbit']);?>
\"  onclick=\"set_task_volume_prepose();\"class=\"bell_button_style\"><input type=\"button\" value=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Cancel']);?>
\"  onclick=\"disappear_volume_div_prepose();\"class=\"bell_button_style\">";
		get_newRow.insertCell(0).innerHTML=getcell;

}
  <?php echo '</script'; ?>
>
  <div id="lead" class="r-displayVolume">
  
	<table border="0" cellpadding="0" cellspacing="0" width="170" height="10" style="background-color:#EEFFEE" id="get_areas">
	
	</table>
</div>
</form>
<?php echo '<script'; ?>
 language="javascript">
function get_mouse_coordinates()
{
  // var eve = event ;
  	if(navigator.appName.indexOf("Explorer") > -1)   
	{
	 var eve = event||window.event;
	 return {
                x:eve.clientX+document.body.scrollLeft - document.body.clientLeft,
                y:eve.clientY+document.body.scrollTop - document.body.clientTop
            };
	
	}
else
	{
		
		
		 return {
                x:document.getElementById( "volume_value").offsetWidth,
                y:document.getElementById( "volume_value").offsetHeight+300
            };
	}
 
}
function get_div_obj(str_id)
{
 	return document.getElementById(str_id);   
}
<?php echo '</script'; ?>
>

<?php echo '<script'; ?>
 src="smarty/templates/ajax/putintobellinfo.js" type="text/javascript"><?php echo '</script'; ?>
>
<?php
$__section_lesson_1_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson'] : false;
$__section_lesson_1_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['lessoninfo']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_lesson_1_total = $__section_lesson_1_loop;
$_smarty_tpl->tpl_vars['__smarty_section_lesson'] = new Smarty_Variable(array());
if ($__section_lesson_1_total != 0) {
for ($__section_lesson_1_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] = 0; $__section_lesson_1_iteration <= $__section_lesson_1_total; $__section_lesson_1_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']++){
if (count($_smarty_tpl->tpl_vars['lessoninfo']->value) == 1) {
echo '<script'; ?>
 language="javascript">
	
	insertRowInTable("coursetable");
	document.getElementById('add').value = "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Modify']);?>
";
	document.getElementById('add').className = "2";
	document.getElementById('belltaskid').value = "<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['taskid'];?>
";
	document.getElementById('coursename').value = "<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['taskname'];?>
";
	document.getElementById('bellstarttime').value = "<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['playtime'];?>
";
	document.getElementById('setmusicname').value="<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['name'];?>
";
	
	var obj = document.getElementById('setbellname');
	//var getsetmedianame="addmediainfo.php?flag=4&id="+<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['mediaid'];?>
+"";
	
	//var getmedianames=getmediataskname(getsetmedianame);

	for(var i=0;i<obj.options.length;i++)
	{
		if(obj.options[i].value == <?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['mediaid'];?>
)
		{
			obj.options[i].selected = true;
			break;
		}
	}
	if(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelengthtype'];?>
==1)
	{
	document.getElementById('belltiemlength').value = timeconversion(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelength'];?>
);
	document.getElementById('belltiemlengthonly').value=timeconversion(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelength'];?>
);
	document.getElementById('bell_playlen').innerHTML="<font class='terminal_star'>"+document.getElementById('belltiemlength').value+"</font>";
	document.getElementById('bell_playlen').value=document.getElementById('belltiemlength').value;
	document.getElementById('bell_playlen').style.display = "none";
	}
	else
	{
	document.getElementById('belltiemlength').value = <?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelength'];?>
;
	document.getElementById('belltiemlengthonly').value=<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelength'];?>
;
	getlen=time_tran_format(parseInt(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelength'];?>
)*parseInt(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['mediatimelength'];?>
));
	document.getElementById('bell_playlen').value=time_tran_format(parseInt(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['mediatimelength'];?>
));
	document.getElementById('bell_playlen').innerHTML="<font class='terminal_star'>"+getlen+"</font>";
		document.getElementById('bell_playlen').style.display = "";
	}
<?php echo '</script'; ?>
>
<?php } else {
echo '<script'; ?>
 language="javascript">
	
	insertRowInTable("coursetable");
	document.getElementsByName('add')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].value = "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['modify_bell_scheme']->value['Modify']);?>
";
	document.getElementsByName('add')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].className = "2";
	document.getElementsByName('belltaskid')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].value = "<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['taskid'];?>
";
	document.getElementsByName('coursename')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].value = "<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['taskname'];?>
";
	document.getElementsByName('bellstarttime')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].value = "<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['playtime'];?>
";
	document.getElementsByName('setmusicname')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].value="<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['name'];?>
";
	var obj = document.getElementById('setbellname');
	//var getsetmedianame="addmediainfo.php?flag=4&id="+<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['mediaid'];?>
+"";
	
	//var getmedianames=getmediataskname(getsetmedianame);
	//	alert(getmedianames);
	for(var i=0;i<obj.options.length;i++)
	{
		if(obj.options[i].value == <?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['mediaid'];?>
)
		{
			obj.options[i].selected = true;
				if(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelengthtype'];?>
==1)
				{
					document.getElementById('belltiemlength').value = timeconversion(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelength'];?>
);
				}
				else
				{
					document.getElementById('belltiemlength').value = <?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelength'];?>
;
				}
			break;
		}
	}
	if(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelengthtype'];?>
==1)
	{
		document.getElementsByName('belltiemlengthonly')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].value=timeconversion(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelength'];?>
);
		document.getElementsByName('bell_playlen')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].innerHTML="<font class='terminal_star'>"+timeconversion(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelength'];?>
)+"</font>";
	document.getElementsByName('bell_playlen')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].value=timeconversion(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelength'];?>
);
	document.getElementsByName('bell_playlen')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].style.display = "none";
	}
	else
	{
		document.getElementsByName('belltiemlengthonly')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].value=<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelength'];?>
;
		document.getElementsByName('bell_playlen')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].innerHTML="<font class='terminal_star'>"+timeconversion(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['mediatimelength'];?>
*<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['timelength'];?>
)+"</font>";
	document.getElementsByName('bell_playlen')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].value=timeconversion(<?php echo $_smarty_tpl->tpl_vars['lessoninfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null)]['mediatimelength'];?>
);
	document.getElementsByName('bell_playlen')[<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_lesson']->value['index'] : null);?>
].style.display = "";
	
	}

<?php echo '</script'; ?>
>
<?php }
}
}
if ($__section_lesson_1_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_lesson'] = $__section_lesson_1_saved;
}
?>
	
<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>
 
</body>
</html>
<?php }
}
