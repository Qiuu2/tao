<?php
/* Smarty version 3.1.30, created on 2026-05-26 16:00:48
  from "/var/www/html/ok112/smarty/templates/ledmanager/AddFileTask_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a15533013fd48_18022384',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '7580a4bf038b674221d04c76a2f39373ca2f27e2' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/ledmanager/AddFileTask_form.html',
      1 => 1778116077,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a15533013fd48_18022384 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<html>
<head>
<META http-equiv=Content-Type content="text/html; charset=utf-8">
<title>AddFileTask</title>

<link href="skin/css/main_page_style.css" rel="stylesheet" type="text/css" />

<!--添加文件列表开始-->
<style>
/*想要改输入日历控件的样子就改下面的CSS样式就可以了*/
/*Date*/
.header {
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
 language="javascript" defer="defer">
	//这段脚本如果你的页面里有，就可以去掉它们了
	var ie=true;
	//var ie =navigator.appName=="Microsoft Internet Explorer"?true:false;
 if (navigator.appName.indexOf("Microsoft")!= -1)
  {   
	  ie=true;
   } 
  else 
  {   
	 ie=false;
    }

	function $(objID)
	{
		return document.getElementById(objID);
	}
<?php echo '</script'; ?>
>
	<link href="smarty/templates/BellManager/codebase/dhtmlxtree.css" rel="stylesheet" type="text/css">
	<?php echo '<script'; ?>
 language="JavaScript" src="smarty/templates/BellManager/codebase/dhtmlxtree.js" type"text/JavaScript"><?php echo '</script'; ?>
>	
	<?php echo '<script'; ?>
 language="JavaScript" src="smarty/templates/BellManager/codebase/dhtmlxcommon.js" type"text/JavaScript"><?php echo '</script'; ?>
>

	
	<?php echo '<script'; ?>
 src="skin/js/frame/analysis_tree_terminal_group_string.js"><?php echo '</script'; ?>
>
	<?php echo '<script'; ?>
  src="smarty/templates/ajax/get_terminaltype.js" ><?php echo '</script'; ?>
>

<!--添加文件列表结束-->
<!--修改脚本2010/5/6-->
<?php echo '<script'; ?>
>
//获取当前日期
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
<?php echo '</script'; ?>
>

<SCRIPT language="javascript" defer="true">

function isNull( str )
{
	if ( str == "" || str==null) 
	return true;
	var regu = "^[ ]+$";
	var re = new RegExp(regu);
	return re.test(str);
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

function lenReg(str){
　　return str.replace(/[^x00-xFF]/g,'***').length;
}; 

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
function trim(str)
{
   str=str.replace(/(^\s*)|(\s*$)/g,""); 
   return str;
}

//验证每次时长
function checkintervallen()
{
	var objexemodel = document.getElementById('intervalmode');
	
		var alllenhour=document.getElementById('lenghtHour').value;
		var alllenmin=document.getElementById('lenghtMin').value;
		var alllensenc=document.getElementById('lenghtSenc').value;
		var alltime=parseInt(alllenhour)*3600+parseInt(alllenmin)*60+parseInt(alllensenc);
		if(alltime==0)
		{
			document.getElementById('intervallen').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['not_zero']);?>
</font>";
			document.getElementById('intervallen').focus();
			return false
		}
	
	return true; 
}


//验证表单 
function checkform()
{
	//验证任务名
	if(isNull(trim(document.getElementById('taskname').value)))
	{
		document.getElementById('taskname_text').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['enter_task_name']);?>
</font>";
		document.getElementById('taskname').focus();
		return false;
	}
	else
	{
		if(!isChinaOrNumbOrLett(trim(document.getElementById('taskname').value)))
		{
			document.getElementById('taskname_text').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['letter_number_Chinese']);?>
</font>";
			document.getElementById('taskname').select();
			return false;
		}
	}
	document.getElementById('taskname_text').innerHTML="<font class='terminal_star'></font>";

	if(document.getElementById('timelength').value==0)
	{
		document.getElementById('timelength_s').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['not_zero']);?>
</font>";
		document.getElementById('lenghtHour').focus();
		return false;
	}
	
	document.getElementById('timelength_s').innerHTML="<font class='terminal_star'></font>";

	//获取执行时间
	document.getElementById("playtime").value=document.getElementById("starthour").value+":"+document.getElementById("startmin").value+":"+document.getElementById("startsenc").value;

	//验证开始时间和结束时间
	if(document.getElementById('startdate').value>document.getElementById('enddate').value)
	{
		document.getElementById('timedatecompare').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['smaller_than_end_date']);?>
</font>";
		document.getElementById('startdate').select();
		return false;
	}
	document.getElementById('timedatecompare').innerHTML="<font class='terminal_star'></font>";
	//验证执行模式
	var obj = document.getElementById('exemodel')
	var strnum="";
	if(obj.options[obj.selectedIndex].value==2)
	{
		for(var i=0;i<document.getElementsByName('week').length;i++)
		{
		
			if(document.getElementsByName('week')[i].checked)
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
		var temp = strnum.split(",");
		var flag = 0;
		for(var i=0;i<temp.length;i++)
		{
			if(temp[i]=="1")
			{
				var flag =1;
			}
		}
		if(flag==0)
		{
			document.getElementById('exemodel_text').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['select_the_date']);?>
</font>";
			document.getElementById('exemodel').focus();
			return false;
		}
		document.getElementById('hiddenweek').value = trim(strnum); 
	}
	//alert(document.getElementById('exeModel_text').innerHTML);
	//document.getElementById('exeModel_text').innerHTML="<font class='terminal_star'></font>";
	//验证媒体文件是否有值
	//var str=trim(tree2.getAllChecked());
/*
	var str = trim(sel_item_seq.toString());

	//alert(str);
	
	var strarray=str.split(",");
	
	var temp=0;
	
	var storearray=new Array();
	
	for(var i=0;i<strarray.length;i++)
	{
		if(isNumber(strarray[i]))
		{
			storearray[temp]=strarray[i];
			temp++;
		}
		continue;
	}
	document.getElementById('listvalue').value=trim(storearray.toString());*/
	/*
	if(isNull(document.getElementById('listvalue').value))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['select_media_files']);?>
");
		return false;
	}
	*/
	
	var haha=document.getElementById("gettextarea").value;
	if(512<=lenReg(haha))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['charbig']);?>
");
		return false;
	}
	if(lenReg(haha)==0)
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['noempoty']);?>
");
		return false;
	
	
	}
	
	//验证终端是否有值
	/*
	var str=trim(tree3.getAllChecked());
	analysis_tree_terminal_group_string(str);

	document.getElementById('analysis_tree_group_string').value = trim(analysis_tree_group_string.toString()); 

	analysis_tree_group_string = new Array();
	
	document.getElementById('terminallistvalue').value = trim(analysis_tree_terminal_string.toString());
	*/
	analysis_tree_terminal_string = new Array();

	if(false==checkintervallen())
	{
		return false;
	}
	
	var ledstr=trim(tree.getAllChecked());
	analysis_tree_terminal_group_string(ledstr);

	document.getElementById('led_group_string').value = trim(analysis_tree_group_string.toString()); 
	analysis_tree_group_string = new Array();
	document.getElementById('ledlistvalue').value = trim(analysis_tree_terminal_string.toString());

	
	if(isNull(trim(document.getElementById('ledlistvalue').value)))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['selectterminal']);?>
");
		return false;
	
	
	}
	

	analysis_tree_terminal_string = new Array();


return true;
}
<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 language="javascript" defer="true">
var rownum=0;
function get_length()
{

document.getElementById('intervallength_s').innerHTML="<font class='terminal_star'>*</font>";
document.getElementById('intervalTime_s').innerHTML="<font class='terminal_star'>*</font>";
}
function displayintervalmode(obj)
{
	var table = document.getElementById("lefttable");
	if(obj.options[obj.selectedIndex ].value==0)
	{
	
		if(rownum!=0)
		{
			table.deleteRow(rownum);
			rownum = 0;
		}
		var newRow = table.insertRow(-1);
		var h=0,m=0,s=0;
		rownum = newRow.rowIndex;
		newRow.style.textAlign = "left";
		var newcell = newRow.insertCell(0);
		newcell.colSpan = 2;
		var str ="<div class=\"bell_div\">";
			str +="	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			 str +=" <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Duration']);?>
<select class=\"terminal_select_style\" name=\"lenghtHour\" id=\"lenghtHour\" onChange=\"showlength();\"  style=\"width:58px;\">";	
	 for (h = 0; h <= 23; h++) 
		{
			str += "<option value=\"" + h + "\">" + h + "</option>";
		}
		str +="</select><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Hour']);?>
";
		str +="<select class=\"terminal_select_style\" name=\"lenghtMin\" id=\"lenghtMin\" onChange=\"showlength();\" style=\"width:58px;\">";
		for (m = 0; m <= 59; m++) 
		{
			str += "<option value=\"" + m + "\">" + m + "</option>";
		}
		str +="</select><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Minute']);?>
";
		str +="<select class=\"terminal_select_style\" name=\"lenghtSenc\" id=\"lenghtSenc\" onChange=\"showlength();\" style=\"width:58px;\">";
		for (s = 0; s <= 59; s++) 
		{
			str += "<option value=\"" + s + "\">" + s + "</option>";
		}
		str +="</select><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Second']);?>
 <span class=\"terminal_star\" id=\"timelength_s\">*</span><br>";

		str+=" </div>";
		newcell.innerHTML =str;
		
	
	}
	else if(obj.options[obj.selectedIndex ].value==1)
	{
		if(rownum!=0)
		{
			table.deleteRow(rownum);
			rownum = 0;
		}
		var newRow = table.insertRow(-1);
		var h=0,m=0,s=0;
		rownum = newRow.rowIndex;
		newRow.style.textAlign = "left";
		var newcell = newRow.insertCell(0);
		newcell.colSpan = 2;
		var str ="<div class=\"bell_div\">";
			str +="	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input  name=\"timelengthtype\" id=\"timelengthtype\" type=\"radio\" value=\"1\" style=\"visibility :hidden;\" checked=\"checked\" onClick=\"javascript:timelengthtype.disabled=true;circleTime.disabled=true;lenghtHour.disabled=false;lenghtMin.disabled=false;lenghtSenc.disabled=false;\"/>";
            
			 str +=" <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Duration']);?>
:<select class=\"terminal_select_style\" name=\"lenghtHour\" id=\"lenghtHour\" onChange=\"showlength();\"  style=\"width:58px;\">";	
	 for (h = 0; h <= 23; h++) 
		{
			str += "<option value=\"" + h + "\">" + h + "</option>";
		}
		str +="</select><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Hour']);?>
";
		str +="<select class=\"terminal_select_style\" name=\"lenghtMin\" id=\"lenghtMin\" onChange=\"showlength();\" style=\"width:58px;\">";
		for (m = 0; m <= 59; m++) 
		{
			str += "<option value=\"" + m + "\">" + m + "</option>";
		}
		str +="</select><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Minute']);?>
";
		str +="<select class=\"terminal_select_style\" name=\"lenghtSenc\" id=\"lenghtSenc\" onChange=\"showlength();\"  style=\"width:58px;\">";
		for (s = 0; s <= 59; s++) 
		{
			str += "<option value=\"" + s + "\">" + s + "</option>";
		}
		str +="</select><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Second']);?>
 <span class=\"terminal_star\" id=\"timelength_s\">*</span><br>";
		str +="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['intervallength']);?>
:<select class=\"terminal_select_style\" name=\"intervallenHour\" id=\"intervallenHour\"   style=\"width:58px;\">";
	
		for (h = 0; h <= 23; h++) 
		{
			str += "<option value=\"" + h + "\">" + h + "</option>";
		}
		str +="</select><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Hour']);?>
";
		str +="<select class=\"terminal_select_style\" name=\"intervallenMin\" id=\"intervallenMin\"  style=\"width:58px;\">";
		for (m = 0; m <= 59; m++) 
		{
			str += "<option value=\"" + m + "\">" + m + "</option>";
		}
		str +="</select><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Minute']);?>
";
		str +="<select class=\"terminal_select_style\" name=\"intervallenSenc\" id=\"intervallenSenc\"  style=\"width:58px;\">";
		for (s = 0; s <= 59; s++) 
		{
			str += "<option value=\"" + s + "\">" + s + "</option>";
		}
		str +="</select><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Second']);?>
 <span class=\"terminal_star\" id=\"intervallen\">*</span> <br>";
		str+="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" value=\"1\" id=\"intervaltype\" name=\"intervaltype\" checked=\"checked\" onchange=\"get_length(this)\" onClick=\"javascript:intervaltype.disabled=true;intervalcircle.disabled=true;intervalHour.disabled=false;intervalMin.disabled=false;intervalSenc.disabled=false;\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['intervalDuration']);?>
:<select class=\"terminal_select_style\" name=\"intervalHour\" id=\"intervalHour\"  style=\"width:58px;\">"
		for (h = 0; h <= 23; h++) 
		{
			str += "<option value=\"" + h + "\">" + h + "</option>";
		}
		str +="</select><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Hour']);?>
";
		str +="<select class=\"terminal_select_style\" name=\"intervalMin\" id=\"intervalMin\"  style=\"width:58px;\">";
		for (m = 0; m <= 59; m++) 
		{
			str += "<option value=\"" + m + "\">" + m + "</option>";
		}
		str +="</select><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Minute']);?>
";
		str +="<select class=\"terminal_select_style\" name=\"intervalSenc\" id=\"intervalSenc\"  style=\"width:58px;\">";
		for (s = 0; s <= 59; s++) 
		{
			str += "<option value=\"" + s + "\">" + s + "</option>";
		}
		str +="</select><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Second']);?>
 <span class=\"terminal_star\" id=\"intervallength_s\">*</span> <br>";
		str+="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" value=\"2\" id=\"intervaltype\" name=\"intervaltype\" onchange=\"get_length(this)\" onClick=\"javascript:intervaltype.disabled=true;intervalcircle.disabled=false;intervalHour.disabled=true;intervalMin.disabled=true;intervalSenc.disabled=true;\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['intervalcishu']);?>
:";
	str+="<input class=\"terminal_input_font\" name=\"intervalcircle\"  type=\"text\" id=\"intervalcircle\" size=\"48\" value=\"1\" disabled=\"disabled\"/><span class=\"terminal_star\" id=\"intervalTime_s\"> *</span> ";	
		
		str+=" </div>";
		
		
		newcell.innerHTML =str;
		
		if( document.getElementsByName('timelengthtype')[0].checked==true)
		{
			document.getElementsByName('timelengthtype')[1].disabled=true;
			document.getElementsByName('timelengthtype')[0].disabled=true;
			 document.getElementById("lenghtHour").disabled=false;
			  document.getElementById("lenghtMin").disabled=false;
			   document.getElementById("lenghtSenc").disabled=false;
			 
		}
		else
		{
			document.getElementsByName('timelengthtype')[0].checked=true;
			document.getElementsByName('timelengthtype')[1].disabled=true;
			document.getElementsByName('timelengthtype')[0].disabled=false;
				 document.getElementById("lenghtHour").disabled=false;
			  document.getElementById("lenghtMin").disabled=false;
			   document.getElementById("lenghtSenc").disabled=false;
		
		}
		
		
	}
}


var row_num=0;
function displayweek(obj)
{
	var tables = document.getElementById("timetable");
	
	if(obj.options[obj.selectedIndex ].value==2)
	{
	
		var newRows = tables.insertRow(-1);
		row_num = newRows.rowIndex;
		newRows.style.textAlign = "center";
		var newcells = newRows.insertCell(0);
		newcells.colSpan = 2;
		newcells.innerHTML ="<div class=\"bell_div\"><input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Sunday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Monday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Tuesday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Wednesday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Thursday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Friday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Saturday']);?>
";
		
		document.getElementById('starthour').disabled = false;
		document.getElementById('startmin').disabled = false;
		document.getElementById('startsenc').disabled = false;
		document.getElementById('startdate').disabled = false;
		document.getElementById('enddate').disabled = false;
	}
	else if(obj.options[obj.selectedIndex ].value==1)
	{
		if(row_num!=0)
		{
			tables.deleteRow(row_num);
			row_num = 0;
		}
		document.getElementById('starthour').disabled = false;
		document.getElementById('startmin').disabled = false;
		document.getElementById('startsenc').disabled = false;
		document.getElementById('startdate').disabled = false;
		document.getElementById('enddate').disabled = false;
	}
	else if(obj.options[obj.selectedIndex ].value==3)
	{
	
		if(row_num!=0)
		{
			table.deleteRow(row_num);
			row_num = 0;
		}
		
		document.getElementById('starthour').disabled = true;
		document.getElementById('startmin').disabled = true;
		document.getElementById('startsenc').disabled = true;
		document.getElementById('startdate').disabled = true;
		document.getElementById('enddate').disabled = true;
	}
}

<?php echo '</script'; ?>
>
</head>
<body>
<form name="frmSearch2" method="post" action="do.php?act=ledaddplaytask_msg&getfolderid=<?php echo $_smarty_tpl->tpl_vars['getfolderid']->value;?>
&userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
" onSubmit="return checkform();" class="terminal_form_to_body">
  <table width="780" align="center" border="0" cellpadding="0" cellspacing="0"  class="terminal_table_border">
    <tr>
      <td colspan="2">
	  	<img src="<?php echo $_smarty_tpl->tpl_vars['media_task_add']->value['led_task_add'];?>
"/>
	  </td>
    </tr>
    <tr>
      <td rowspan="2"  width="390" align="left" valign="top" class="fileadm_frame_border">
	  <table width="100%" border="0" cellspacing="0" cellpadding="0" id="lefttable">
          <tr>
           <td colspan="2" class="fileadm_table_title"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Task_attribute']);?>
</td>
          </tr>
          <tr>
            <td nowrap class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Task_Name']);?>
</td>
			
            <td nowrap class="bell_talbe_col_leftalign">
				<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Task_Name_task']);?>
" maxlength="8" class="terminal_input_font" name="taskname" type="text" id="taskname"/>
				<span class="terminal_star" id="taskname_text">*</span>
			</td>
          </tr>
	
          <tr>
            <td class="belll_table_col_rightalign" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Pre_power_on']);?>
</td>
            <td nowrap class="bell_talbe_col_leftalign">
			<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Pre_power_on_task']);?>
" class="terminal_select_style" name="prepower" id="prepower" style="width:80px;"> 
					<?php echo '<script'; ?>
>
					var i=0;
						while(1)
						{
							document.write("<option value='"+i+"'>"+i+"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Second']);?>
</option>");
							i+=5;
							if(i>=59)
							break;
						}
						
						var obj = document.getElementById('prepower');
						for(var i=0;i<obj.options.length;i++)
						{
							if(obj.options[i].value == 15)
							{
								obj.options[i].selected = true;
							}
						}
					<?php echo '</script'; ?>
>
					<option value="60">1 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Minute']);?>
</option>
					<option value="120" >2 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Minute']);?>
</option>
					<option value="180">3 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Minute']);?>
</option>
					<option value="240">4 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Minute']);?>
</option>
					<option value="300">5 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Minute']);?>
</option>	
              </select>
 <!--添加优先级开始-->					


<?php echo $_smarty_tpl->tpl_vars['media_task_add']->value['Task_level'];?>


<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Task_level_task']);?>
" name="task_priority_text" id="task_priority_text" style="width:80px;">
	 <?php echo '<script'; ?>
>
  	var level=0;
  	for(level=<?php echo $_smarty_tpl->tpl_vars['getlevel']->value;?>
;level<=109;level++)
	{
		document.write("<option value='"+level+"'>"+level+"</option>");
	}
  <?php echo '</script'; ?>
>
</select>


<!--添加优先级结束-->	
		  
            </td>
          </tr>
		    <tr>
            <td nowrap class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Send_mode']);?>
</td>
            <td nowrap class="bell_talbe_col_leftalign">
			<select   name="datasendmodel" id="datasendmodel" style="width:69px;">
                <option value="0"><?php echo $_smarty_tpl->tpl_vars['media_task_add']->value['unicast'];?>
</option>
                <option value="1"><?php echo $_smarty_tpl->tpl_vars['media_task_add']->value['multicast'];?>
</option>
            </select>
		
		

            </td>
          </tr> 
		
		     <tr>
            <td nowrap class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['intervalmode']);?>
</td>
            <td nowrap class="terminal_select_style">
			<select  class="terminal_select_style" name="intervalmode" id="intervalmode" style="width:89px;" onChange="displayintervalmode(this)">
                <option value="0"><?php echo $_smarty_tpl->tpl_vars['media_task_add']->value['normalmode'];?>
</option>
              
            </select>
			 <?php echo '<script'; ?>
 language="javascript" defer="true">
			 function showlength()
					{
					document.getElementById("timelength").value=parseInt(document.getElementById("lenghtHour").value)*60*60+parseInt(document.getElementById("lenghtMin").value)*60+parseInt(document.getElementById("lenghtSenc").value);
					}
				var obj = document.getElementById('intervalmode');
				displayintervalmode(obj);
			
//用来判断播放时间长度不能为零
			 <?php echo '</script'; ?>
>	
			 <input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['playtimenum']);?>
" name="timelength" type="text" id="timelength" readonly="readonly" value="0" style="display:none" >
            </td>
          </tr>
      </table>
	  </td>
      <td width="390" align="left" valign="top" class="fileadm_frame_border">
	  <table width="100%"  border="0" id="timetable" name="timetable" cellspacing="0" cellpadding="0">
          <tr>
            <td colspan="2" class="fileadm_table_title"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Run_time']);?>
</td>
          </tr>
		  
          <tr>
            <td class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Play_time']);?>
</td>
            <td nowrap class="bell_talbe_col_leftalign">
			<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Start_timetask']);?>
" class="terminal_select_style" name="starthour" id="starthour" style="width:55px;">
				<?php echo '<script'; ?>
>
					for(var i=0;i<=23;i++)
					{
						var obj = new Date();
						var gethours = obj.getHours();
						if(i== gethours)
						{
							document.write("<option value='"+i+"' selected='selected'>"+i+"</option>");
						}
						else
						{
							document.write("<option value='"+i+"'>"+i+"</option>");
						}
					}
				<?php echo '</script'; ?>
>
			</select>
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Hour']);?>

			<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Start_timetask']);?>
" class="terminal_select_style" name="startmin" id="startmin" style="width:55px;">
			<?php echo '<script'; ?>
>
				for(var i=0;i<=59;i++)
				{
					var obj = new Date();
					var getminutes = obj.getMinutes();
					if(i == getminutes)
					{
						document.write("<option value='"+i+"' selected='selected'>"+i+"</option>");
					}
					else
					{
						document.write("<option value='"+i+"'>"+i+"</option>");
					}
				}
			<?php echo '</script'; ?>
>
			</select>
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Minute']);?>

			
			<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Start_timetask']);?>
" class="terminal_select_style" name="startsenc" id="startsenc" style="width:55px;">
			<?php echo '<script'; ?>
>
			for(var i=0;i<=59;i++)
			{
				var obj = new Date();
				var getsecond = obj.getSeconds();
				if(i==getsecond)
				{
					document.write("<option value='"+i+"' >"+i+"</option>");
				}
				else
				{
					document.write("<option value='"+i+"'>"+i+"</option>");
				}	 
			}
			<?php echo '</script'; ?>
>
			</select>
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Second']);?>

			
			<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Start_timetask']);?>
" name="playtime" type="text" id="playtime" readonly="readonly" value=""  style="display:none;" >
			<span id="starttime_s" class="terminal_star"></span>
</td>
  </tr>
     <tr>
       <td class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Start_date']);?>
</td>
        <td nowrap class="bell_talbe_col_leftalign">

                <input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Start_Datetask']);?>
" class="bell_input_style" name="startdate" id="startdate" type="text" value="" size="14" readonly="readonly" onClick="showcalendar(event, this);" onFocus="showcalendar(event, this);if(this.value=='0000-00-00')this.value=''" /></td>
          </tr>
          <tr>
            <td class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['End_date']);?>
</td>
            <td nowrap class="bell_talbe_col_leftalign">
			<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['ent_datetask']);?>
" class="bell_input_style" name="enddate" id="enddate" type="text" value="" size="14" readonly="readonly" onClick="showcalendar(event, this);" onFocus="showcalendar(event, this);if(this.value=='0000-00-00')this.value=''" />
                <span class="terminal_star" id="timedatecompare">*</span> 
			</td>
				<?php echo '<script'; ?>
>
				
				document.getElementById('startdate').value = getNowFormatDate();
				
				document.getElementById('enddate').value = getNowFormatDate();
				<?php echo '</script'; ?>
>
          </tr>

          <tr>
            <td class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Run_mode']);?>
</td>
            <td nowrap class="bell_talbe_col_leftalign">
			<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Run_modetask']);?>
" class="terminal_select_style" name="exemodel" id="exemodel" onChange="displayweek(this)">
                <option value="1"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Every_day']);?>
</option>
                <option value="2"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Every_week']);?>
</option>
				<option value="3"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Manual']);?>
</option>
            </select>
            <span class="terminal_star" id="exemodel_text">*</span> 
            </td>
          </tr>
		 
      </table>
	  </td>
    </tr>
    <tr>
      <td height="0" align="left" valign="top">
	  <input type="hidden" id="hiddenweek" name="hiddenweek" value=""/></td>
    </tr>
    <tr >
      <td height="" colspan="2" align="left" valign="top">
	  <table width="100%" border="0" cellspacing="0" cellpadding="0" >
          <tr>
           
            <td ><div class="fileadm_right_div"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['LEDtext']);?>
</div></td>
          </tr>
          <tr>
          
            <td width="100%" align="left">
				<textarea rows="20" cols="108" id="gettextarea" name="gettextarea" ></textarea>
            </td>
          </tr>
		    <tr>
           
            <td ><div class="fileadm_right_div"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['LEDTerminal_list']);?>
</div></td>
          </tr>
          <tr>
        
            <td width="100%" align="left">
			<div id="ledlists" name="ledlists"  style="width:100%; height:200px; "></div>
				<input type="hidden"  id="ledlistvalue" name="ledlistvalue" value=""/>
				<input type="hidden" id="led_group_string" name="led_group_string" value=""/>
				<?php echo '<script'; ?>
 language="javascript" defer="true">
			  
					var leddata = "<?php echo $_smarty_tpl->tpl_vars['ledlist']->value;?>
";
					tree=new dhtmlXTreeObject("ledlists","100%","100%",0);
					tree.setSkin('dhx_skyblue');
					tree.setImagePath("smarty/templates/BellManager/codebase/csh_bluebooks/");
					tree.enableCheckBoxes(1);
					tree.enableThreeStateCheckboxes(true);
				
					
					tree.loadXMLString(leddata);
					
				//	var get_led_id = tree.getAllChildless();
					
				//	alert(get_led_id);
				
				<?php echo '</script'; ?>
>
	
            </td>
          </tr>
      </table></td>
    </tr>

    <tr>
      <td height="28" colspan="2" align="center" valign="middle">
	  <input type="hidden" name="taskType" value="ledplaytask"/>
          <input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['submit_task']);?>
" name="Submit" type="submit" class="terminal_button" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Submit']);?>
"/>
        	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      <!--    <input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['cannel_task']);?>
" name="reset" type="reset" class="terminal_button" id="reset" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Cancel']);?>
" />
	  -->
	  <p><?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

</p>
	  </td>
    </tr>
  </table>
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
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_1']);?>
";
				break;
				case 1:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_2']);?>
";
				break;
				case 2:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_3']);?>
";
				break;
				case 3:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_4']);?>
";
				break;
				case 4:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_5']);?>
";
				break;
				case 5:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_6']);?>
";
				break;
				case 6:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_7']);?>
";
				break;
				case 7:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_8']);?>
";
				break;
				case 8:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_9']);?>
";
				break;
				case 9:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_10']);?>
";
				break;
				case 10:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_11']);?>
";
				break;
				case 11:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_12']);?>
";
				break;
				case 12:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_13']);?>
";
				break;
				case 13:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_14']);?>
";
				break;
				case 14:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_15']);?>
";
				break;
				case 15:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['zone_16']);?>
";
				break;
			
			}
			if(i%2!=0)
				getcell+="\n";
		}
		if(i==channelnum)
		getcell+="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Sumbit']);?>
\"  onclick=\"set_task_volume_prepose();\"class=\"bell_button_style\"><input type=\"button\" value=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['Cancel']);?>
\"  onclick=\"disappear_volume_div_prepose();\"class=\"bell_button_style\">";
		get_newRow.insertCell(0).innerHTML=getcell;

}
  <?php echo '</script'; ?>
>
 <div id="lead" class="r-displayVolume">
	<table border="0" cellpadding="0" cellspacing="0" width="170" height="10" style="background-color:#EEFFEE"  id="get_areas">
	
	</table>
</div>

</form>
<p>
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
                x:document.getElementById( "terminallist").offsetWidth+200,
                y:document.getElementById( "terminallist").offsetHeight+120
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
 type="text/javascript" src="calendar.js" defer="true"><?php echo '</script'; ?>
>
</p>

</body>
</html>


<?php echo '<script'; ?>
 language="javascript" defer="true">
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

function _cancelBubble(event) {
	e = event ? event : window.event ;
	if(ie) {
		e.cancelBubble = true;
	} else {
		e.stopPropagation();
	}
}

function getposition(obj) {
	var r = new Array();
	r['x'] = obj.offsetLeft;
	r['y'] = obj.offsetTop;
	while(obj = obj.offsetParent) {
		r['x'] += obj.offsetLeft;
		r['y'] += obj.offsetTop;
	}
	return r;
}

function loadcalendar() {
	s = '';
	s += '<div id="calendar" style="display:none; position:absolute; z-index:9;" onclick="_cancelBubble(event)">';
	if (ie)
	{
		s += '<iframe width="200" height="160" src="about:blank" style="position: absolute;z-index:-1;"></iframe>';
	}
	s += '<div style="width: 200px;"><table class="tableborder" cellspacing="0" cellpadding="0" width="100%" style="text-align: center">';
	s += '<tr align="center" class="header"><td class="header"><a href="#" onclick="refreshcalendar(yy, mm-1);return false" title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['lastmonth']);?>
">&lt;&lt;</a></td><td colspan="5" style="text-align: center" class="header"><a href="#" onclick="showdiv(\'year\');_cancelBubble(event);return false" title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['pleaseclickyear']);?>
" id="year"></a>&nbsp; - &nbsp;<a id="month" title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['pleaseclickmonth']);?>
" href="#" onclick="showdiv(\'month\');_cancelBubble(event);return false"></a></td><td class="header"><A href="#" onclick="refreshcalendar(yy, mm+1);return false" title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['nextmonth']);?>
">&gt;&gt;</A></td></tr>';
	s += '<tr class="category"><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['day']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['one']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['two']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['three']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['four']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['five']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['six']);?>
</td></tr>';
	for(var i = 0; i < 6; i++) {
		s += '<tr class="altbg2">';
		for(var j = 1; j <= 7; j++)
			s += "<td id=d" + (i * 7 + j) + " height=\"19\">0</td>";
		s += "</tr>";
	}
	s += '<tr id="hourminute"><td colspan="7" align="center"><input type="text" size="1" value="" id="hour" onKeyUp=\'this.value=this.value > 23 ? 23 : zerofill(this.value);controlid.value=controlid.value.replace(/\\d+(\:\\d+)/ig, this.value+"$1")\'> <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['point']);?>
 <input type="text" size="1" value="" id="minute" onKeyUp=\'this.value=this.value > 59 ? 59 : zerofill(this.value);controlid.value=controlid.value.replace(/(\\d+\:)\\d+/ig, "$1"+this.value)\'> <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['part']);?>
</td></tr>';
	s += '</table></div></div>';
	s += '<div id="calendar_year" onclick="_cancelBubble(event)"><div class="col">';
	for(var k = 2010; k <= 2039; k++) {
		s += k != 1930 && k % 10 == 0 ? '</div><div class="col">' : '';
		s += '<a href="#" onclick="refreshcalendar(' + k + ', mm);$(\'calendar_year\').style.display=\'none\';return false"><span' + (today.getFullYear() == k ? ' class="today"' : '') + ' id="calendar_year_' + k + '">' + k + '</span></a><br />';
	}
	s += '</div></div>';
	s += '<div id="calendar_month" onclick="_cancelBubble(event)">';
	for(var k = 1; k <= 12; k++) {
		s += '<a href="#" onclick="refreshcalendar(yy, ' + (k - 1) + ');$(\'calendar_month\').style.display=\'none\';return false"><span' + (today.getMonth()+1 == k ? ' class="today"' : '') + ' id="calendar_month_' + k + '">' + k + ( k < 10 ? '&nbsp;' : '') + ' <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['month']);?>
</span></a><br />';
	}
	s += '</div>';

	var nElement = document.createElement("div");
	nElement.innerHTML=s;
	document.getElementsByTagName("body")[0].appendChild(nElement);

//	document.write(s);
	document.onclick = function(event) {
		$('calendar').style.display = 'none';
		$('calendar_year').style.display = 'none';
		$('calendar_month').style.display = 'none';
	}
	$('calendar').onclick = function(event) {
		_cancelBubble(event);
		$('calendar_year').style.display = 'none';
		$('calendar_month').style.display = 'none';
	}
}

function parsedate(s) {
	/(\d+)\-(\d+)\-(\d+)\s*(\d*):?(\d*)/.exec(s);
	var m1 = (RegExp.$1 && RegExp.$1 > 1899 && RegExp.$1 < 2101) ? parseFloat(RegExp.$1) : today.getFullYear();
	var m2 = (RegExp.$2 && (RegExp.$2 > 0 && RegExp.$2 < 13)) ? parseFloat(RegExp.$2) : today.getMonth() + 1;
	var m3 = (RegExp.$3 && (RegExp.$3 > 0 && RegExp.$3 < 32)) ? parseFloat(RegExp.$3) : today.getDate();
	var m4 = (RegExp.$4 && (RegExp.$4 > -1 && RegExp.$4 < 24)) ? parseFloat(RegExp.$4) : 0;
	var m5 = (RegExp.$5 && (RegExp.$5 > -1 && RegExp.$5 < 60)) ? parseFloat(RegExp.$5) : 0;
	/(\d+)\-(\d+)\-(\d+)\s*(\d*):?(\d*)/.exec("0000-00-00 00\:00");
	return new Date(m1, m2 - 1, m3, m4, m5);
}

function settime(d) {
	$('calendar').style.display = 'none';
	controlid.value = yy + "-" + zerofill(mm + 1) + "-" + zerofill(d) + (addtime ? ' ' + zerofill($('hour').value) + ':' + zerofill($('minute').value) : '');
}

function showcalendar(event,controlid1, addtime1, startdate1, enddate1) {
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

function refreshcalendar(y, m) {
	var x = new Date(y, m, 1);
	var mv = x.getDay();
	var d = x.getDate();
	var dd = null;
	yy = x.getFullYear();
	mm = x.getMonth();
	$("year").innerHTML = yy;
	$("month").innerHTML = mm + 1 > 9  ? (mm + 1) : '0' + (mm + 1);

	for(var i = 1; i <= mv; i++) {
		dd = $("d" + i);
		dd.innerHTML = "&nbsp;";
		dd.className = "";
	}

	while(x.getMonth() == mm) {
		dd = $("d" + (d + mv));
		dd.innerHTML = '<a href="###" onclick="settime(' + d + ');return false">' + d + '</a>';
		if(x.getTime() < today.getTime() || (enddate && x.getTime() > enddate.getTime()) || (startdate && x.getTime() < startdate.getTime())) {
			dd.className = 'expire';
		} else {
			dd.className = 'default';
		}
		if(x.getFullYear() == today.getFullYear() && x.getMonth() == today.getMonth() && x.getDate() == today.getDate()) {
			dd.className = 'today';
			dd.firstChild.title = '<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['media_task_add']->value['today']);?>
';
		}
		if(x.getFullYear() == currday.getFullYear() && x.getMonth() == currday.getMonth() && x.getDate() == currday.getDate()) {
			dd.className = 'checked';
		}
		x.setDate(++d);
	}

	while(d + mv <= 42) {
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

function showdiv(id) {

	var p = getposition($(id));
	$('calendar_' + id).style.left = p['x']+'px';
	$('calendar_' + id).style.top = (p['y'] + 16)+'px';
	$('calendar_' + id).style.display = 'block';
}

function zerofill(s) {
	var s = parseFloat(s.toString().replace(/(^[\s0]+)|(\s+$)/g, ''));
	s = isNaN(s) ? 0 : s;
	return (s < 10 ? '0' : '') + s.toString();
}

loadcalendar();

<?php echo '</script'; ?>
>

<?php }
}
