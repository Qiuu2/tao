<?php
/* Smarty version 3.1.30, created on 2026-05-25 13:24:33
  from "/var/www/html/ok112/smarty/templates/AdmManger/AddAdmManger.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a13dd118ccc79_60143334',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'aa9f2798837499186714fd2582ce4b0606abd4e7' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/AdmManger/AddAdmManger.html',
      1 => 1778116039,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a13dd118ccc79_60143334 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<html>
<head>
<META http-equiv=Content-Type content="text/html; charset=utf-8">
<title>AddAdmManager</title>

<link href="skin/css/main_page_style.css" rel="stylesheet" type="text/css" />

<!--添加文件列表开始-->

<link href="smarty/templates/BellManager/codebase/dhtmlxtree.css" rel="stylesheet" type="text/css">
<?php echo '<script'; ?>
 language="JavaScript" src="smarty/templates/BellManager/codebase/dhtmlxtree.js" type"text/JavaScript"><?php echo '</script'; ?>
>	
<?php echo '<script'; ?>
 language="JavaScript" src="smarty/templates/BellManager/codebase/dhtmlxcommon.js" type"text/JavaScript"><?php echo '</script'; ?>
>
	<?php echo '<script'; ?>
  src="smarty/templates/ajax/changeselect1.js"><?php echo '</script'; ?>
>
	<?php echo '<script'; ?>
 src="skin/js/frame/analysis_tree_terminal_group_string.js"><?php echo '</script'; ?>
>
	<?php echo '<script'; ?>
  src="smarty/templates/ajax/get_terminaltype.js" ><?php echo '</script'; ?>
>
<!--时间代码开始-->
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
}
/*图片路径可以改成自己的*/
/*Date*/
</style>

<?php echo '<script'; ?>
 language="javascript">

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

//验证表单 
function checkform()
{

	//验证任务名
	if(isNull(document.getElementById('taskname').value))
	{
		document.getElementById('taskname_text').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['enter_task_name']);?>
</font>";
		document.getElementById('taskname').focus();
		return false;
	}
	else
	{
		if(!isChinaOrNumbOrLett(document.getElementById('taskname').value))
		{
			document.getElementById('taskname_text').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['letter_number_Chinese']);?>
</font>";
			document.getElementById('taskname').select();
			return false;
		}
	}
	document.getElementById('taskname_text').innerHTML="<font class='terminal_star'></font>";
	//获取音量
	document.getElementById('task_default_volume').value = trim(document.getElementById('volume_value').value);
	
	//验证播放时长
	if(document.getElementById('timelength').value==0)
	{
		document.getElementById('timelength_s').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['not_zero']);?>
</font>";
		document.getElementById('lenghtHour').focus();
		return false;
	}
	document.getElementById('timelength_s').innerHTML="<font class='terminal_star'></font>";
	//对起始时间赋值
	
	var playtime_hour = "";
	var playtime_minute = "";
	var playtime_second = "";

	playtime_hour = document.getElementById('starthour').value + ":";
	playtime_minute = document.getElementById('startmin').value + ":";
	playtime_second = document.getElementById('startsenc').value;
	document.getElementById('playtime').value = playtime_hour + playtime_minute + playtime_second;
	
	//验证开始时间和结束时间
	if(document.getElementById('startdate').value>document.getElementById('enddate').value)
	{
		document.getElementById('timedatecompare').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['smaller_than_end_date']);?>
</font>";
		document.getElementById('startdate').select();
		return false;
	}
	document.getElementById('timedatecompare').innerHTML="<font class='terminal_star'></font>";
	
	//验证执行模式
	var obj = document.getElementById('exemodel');
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
			document.getElementById('exemodel_text').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['select_the_date']);?>
</font>";
			document.getElementById('exemodel').focus();
			return false;
		}
		document.getElementById('hiddenweek').value = trim(strnum); 
	}
	document.getElementById('exeModel_text').innerHTML="<font class='terminal_star'></font>";


	//验证终端是否有值
	var str=trim(tree3.getAllChecked());
	
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
	
	if(isNull(document.getElementById('terminallistvalue').value))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['select_terminals_tasks']);?>
");
		return false;
	}
	
}
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
		newcell.innerHTML ="<div class=\"bell_div\"><input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Sunday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Monday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Tuesday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Wednesday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Thursday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Friday']);?>
<input type=\"checkbox\" value=\"1\" id=\"week\" name=\"week\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Saturday']);?>
";
		
		document.getElementById('starthour').disabled = false;
		document.getElementById('startmin').disabled = false;
		document.getElementById('startsenc').disabled = false;
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
		
		document.getElementById('starthour').disabled = false;
		document.getElementById('startmin').disabled = false;
		document.getElementById('startsenc').disabled = false;
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
<form name="frmSearch2" method="post" action="do.php?act=addfileplaytask_msg" onSubmit="return checkform();" class="terminal_form_to_body">
  <table width="780" border="0" align="center" cellpadding="0" cellspacing="0" class="terminal_table_border">
  
    <tr>
      <td colspan="2" align="left" valign="middle">
	  	<img src="<?php echo $_smarty_tpl->tpl_vars['collect_task_add']->value['collect_task_add_image'];?>
"/>
	  </td>
    </tr>
  
    <tr>
      <td width="50%" align="left" valign="top" class="fileadm_frame_border">
	  
		  <table width="100%" border="0" cellspacing="0" cellpadding="0">
		  
			  <tr>
				<td colspan="2" class="fileadm_table_title"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Task_attribute']);?>
</td>
			  </tr>
			  
			  <tr>
				<td nowrap class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Task_Name']);?>
</td>
				<td nowrap class="bell_talbe_col_leftalign">
					<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Task_Name_task']);?>
" class="terminal_input_font" name="taskname" type="text" id="taskname">
					<span class="terminal_star" id="taskname_text">*</span>
				</td>
			  </tr>
			  
			  <tr>
				<td nowrap class="belll_table_col_rightalign">			
				  <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Duration']);?>

				</td>
			   <td nowrap class="bell_talbe_col_leftalign">
			   <select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['playtimehour']);?>
" class="terminal_select_style" name="lenghtHour" id="lenghtHour" onChange="showlength();" style="width:53px;">
				<?php echo '<script'; ?>
 language="javascript">
				for(var i=0;i<=23;i++)
				{
					document.write("<option value='"+i+"'>"+i+"</option>");
				}
				<?php echo '</script'; ?>
>
				</select>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Hour']);?>

				<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['playtimemin']);?>
" class="terminal_select_style" name="lenghtMin" id="lenghtMin" onChange="showlength();" style="width:53px;">
				<?php echo '<script'; ?>
 language="javascript">
					for(var i=0;i<=59;i++)
					{
						document.write("<option value='"+i+"'>"+i+"</option>");
					}
				<?php echo '</script'; ?>
>
				</select>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Minute']);?>

				<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['playtimesec']);?>
" class="terminal_select_style" name="lenghtSenc" id="lenghtSenc" onChange="showlength();" style="width:53px;">
				<?php echo '<script'; ?>
 language="javascript">
					for(var i=0;i<=59;i++)
					{
						document.write("<option value='"+i+"'>"+i+"</option>");
					}
				<?php echo '</script'; ?>
>
				</select>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Second']);?>

				<?php echo '<script'; ?>
 language="javascript">
					function showlength()
					{
						document.getElementById("timelength").value=parseInt(document.getElementById("lenghtHour").value)*60*60+parseInt(document.getElementById("lenghtMin").value)*60+parseInt(document.getElementById("lenghtSenc").value*1);
					}
				<?php echo '</script'; ?>
>
				<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['playtimetypetask']);?>
" name="timelength" type="text" id="timelength" readonly="readonly" value="0" style="display:none" >
				<span class="terminal_star" id="timelength_s">*</span> 
				</td>
			  </tr>        
			  <tr>
				<td nowrap class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Pre_power_on']);?>
</td>
				<td nowrap class="bell_talbe_col_leftalign">
					<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Pre_power_on_task']);?>
" class="terminal_select_style" name="prepower" id="prepower" style="width:80px;">
						<?php echo '<script'; ?>
>
					var i=0;
						while(1)
						{
							document.write("<option value='"+i+"'>"+i+"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Second']);?>
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
					<option value="60">1 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Minute']);?>
</option>
					<option value="120" >2 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Minute']);?>
</option>
					<option value="180">3 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Minute']);?>
</option>
					<option value="240">4 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Minute']);?>
</option>
					<option value="300">5 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Minute']);?>
</option>
					
						
					</select>			
<!--添加优先级开始-->					

<div class="change_task_priority_div" >

<?php echo $_smarty_tpl->tpl_vars['collect_task_add']->value['Task_level'];?>


<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Task_level_task']);?>
" name="task_priority_text" id="task_priority_text" style="width:60px;">
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

<!--
<img src="skin/images/frame/priority_left1.gif" id="left_image" class="task_left_image" onClick="Lower_priority_tasks()"/>

<input type="text" value="0" name="task_priority_text" id="task_priority_text" class="task_priority_input" readonly="true"/>

<img src="skin/images/frame/priority_right.gif" id="right_image" class="task_right_image" onClick="Increase_task_priority()"/>



<?php echo '<script'; ?>
 src="skin/js/frame/change_task_priority.js"><?php echo '</script'; ?>
>

-->
</div>
<!--添加优先级结束-->					
				</td>
			  </tr>
			  <tr>
				<td nowrap class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Send_mode']);?>
</td>
				<td nowrap class="bell_talbe_col_leftalign">
					<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Task_send_task']);?>
" class="terminal_select_style" name="datasendmodel" id="datasendmodel" style="width:69px;">
						<option value="0"><?php echo $_smarty_tpl->tpl_vars['collect_task_add']->value['unicast'];?>
</option>
						<option value="1"><?php echo $_smarty_tpl->tpl_vars['collect_task_add']->value['multicast'];?>
</option>
					</select>
				</td>
			  </tr>
			   <?php echo '<script'; ?>
 type="text/javascript" src="skin/js/frame/slider.js"><?php echo '</script'; ?>
>
			<tr>
				<td class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Volume']);?>
</td>
				<input  type="hidden" name="task_default_volume" id="task_default_volume" value="0"/>
				<td nowrap="nowrap"> 
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
					<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['task_Volumetask']);?>
" name="volume_value" id="volume_value" value="80" align="right" readonly="true" type="Text" size="3" onChange="A_SLIDERS[5].f_setValue(this.value)">
				</td>
			</tr>	
		  </table>
	  </td>
	  
      <td width="50%" align="left" valign="top" class="fileadm_frame_border">
	  
	  <table id="timetable" name="timetable" width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td colspan="2" class="fileadm_table_title"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Run_time']);?>
</td>
          </tr>
 
          <tr>
            <td nowrap class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Play_time']);?>
</td>
            <td nowrap class="bell_talbe_col_leftalign">
			<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Start_timetask']);?>
" class="terminal_select_style" name="starthour" id="starthour" style="width:60px;">
			<?php echo '<script'; ?>
 language="javascript">
				for(var i=0;i<=23;i++)
				{
				 document.write("<option value='"+i+"'>"+i+"</option>");
				}
			<?php echo '</script'; ?>
>
			</select>
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Hour']);?>

			<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Start_timetask']);?>
" class="terminal_select_style" name="startmin" id="startmin" style="width:60px;">
			<?php echo '<script'; ?>
 language="javascript">
				for(var i=0;i<=59;i++)
				{
				 document.write("<option value='"+i+"'>"+i+"</option>");
				}
			<?php echo '</script'; ?>
>
			</select>
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Minute']);?>

			<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Start_timetask']);?>
" class="terminal_select_style" name="startsenc" id="startsenc" style="width:60px;">
			<?php echo '<script'; ?>
 language="javascript">
				for(var i=0;i<=59;i++)
				{
				 document.write("<option value='"+i+"'>"+i+"</option>");
				}
			<?php echo '</script'; ?>
>
			
			<?php echo '<script'; ?>
 language="javascript">
				var times = new Date();
				var hour_obj = document.getElementById('starthour');
				var minute_obj = document.getElementById('startmin');
				var second_obj = document.getElementById('startsenc');
				for(var i=0; i<hour_obj.options.length; i++)
				{
					if(hour_obj.options[i].value == times.getHours())
					{
						hour_obj.options[i].selected = true;
					}
				}
				for(var i=0; i<minute_obj.options.length; i++)
				{
					if(minute_obj.options[i].value == times.getMinutes())
					{
						minute_obj.options[i].selected = true;
					}
				}
			//	for(var i=0; i<second_obj.options.length; i++)
				//{
				//	if(second_obj.options[i].value == times.getSeconds())
				//	{
				//		second_obj.options[i].selected = true;
				//	}
				//}
			<?php echo '</script'; ?>
>
</select>
<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Second']);?>

<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Start_timetask']);?>
" name="playtime" type="text" id="playtime" readonly="readonly" value="00:00:00"  style="display:none;" >

<span id="starttime_s" class="terminal_star"></span> 

			  </td>
          </tr>
          <tr>
            <td nowrap class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Start_date']);?>
</td>
            <td nowrap class="bell_talbe_col_leftalign">

                <!--添加日期-->
				<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Start_Datetask']);?>
" class="bell_input_style" name="startdate" id="startdate" type="text" value="" size="14" readonly="readonly" onClick="showcalendar(event, this);" onFocus="showcalendar(event, this);if(this.value=='0000-00-00')this.value=''" />
            </td>
          </tr>
          <tr>
            <td class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['End_date']);?>
</td>
            <td nowrap class="bell_talbe_col_leftalign">
				<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['End_Datetask']);?>
" class="bell_input_style" name="enddate" id="enddate"  type="text" value="" size="14" readonly="readonly" onClick="showcalendar(event, this);" onFocus="showcalendar(event, this);if(this.value=='0000-00-00')this.value=''" />
				<span class="terminal_star" id="timedatecompare">*</span>
			</td>
          </tr>
			<?php echo '<script'; ?>
 language="javascript">
				document.getElementById('startdate').value = getNowFormatDate();
				
				document.getElementById('enddate').value = getNowFormatDate();
			<?php echo '</script'; ?>
>
          <tr>
            <td class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Run_mode']);?>
</td>
            <td nowrap class="bell_talbe_col_leftalign">
				<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Run_modetask']);?>
" class="terminal_select_style" name="exemodel" id="exemodel" onChange="displayweek(this)">
					<option value=1><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Every_day']);?>
</option>
					<option value=2><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Every_week']);?>
</option>
					<option value="3"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Manual']);?>
</option>
				</select>
				<span class="terminal_star" id="exeModel_text"></span> 
				<input type="hidden" id="hiddenweek" name="hiddenweek" value=""/>
			</td>
          </tr>
      </table>
	  </td>
    </tr>
	
    <tr>
      <td colspan="2" align="left" valign="top" class="fileadm_frame_border">
		  <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
			  <tr>
				<td colspan="4" class="fileadm_table_title"><?php echo $_smarty_tpl->tpl_vars['Belladdtask']->value['Audio_Settings'];?>
</td>
			  </tr>
			  
			  <tr>
			  
				<td class="belll_table_col_rightalign">
				
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Collection_terminal']);?>

				</td>
				
				<td nowrap class="bell_talbe_col_leftalign">

					<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['audiosourcetask']);?>
" class="terminal_select_style" name="audiosource" id="audiosource" style="width:120px" onChange="changeselect1(this)">
					<option  value=""><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['select_terminal']);?>
</option>
					<?php
$__section_sourceid_0_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid'] : false;
$__section_sourceid_0_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['terminal_info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_sourceid_0_total = $__section_sourceid_0_loop;
$_smarty_tpl->tpl_vars['__smarty_section_sourceid'] = new Smarty_Variable(array());
if ($__section_sourceid_0_total != 0) {
for ($__section_sourceid_0_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] = 0; $__section_sourceid_0_iteration <= $__section_sourceid_0_total; $__section_sourceid_0_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']++){
?>
							<option  title="<?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] : null)]['terminalname'];?>
" value="<?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] : null)]['id'];?>
"><?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] : null)]['terminalname'];?>
</option>
					<?php
}
}
if ($__section_sourceid_0_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_sourceid'] = $__section_sourceid_0_saved;
}
?>
					</select>
					
					<span class="change_coll_repower_div"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Pre_power_on']);?>

                    <select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Pre_power_task']);?>
" class="terminal_select_style" name="interview_repower" id="interview_repower" style="width:50px;">
					<?php echo '<script'; ?>
>
						var i=0;
						while(1)
						{
							document.write("<option value='"+i+"'>"+i+"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Second']);?>
</option>");
							i+=5;
							if(i>=59)
							break;
						}
					<?php echo '</script'; ?>
>
					<option value="60">1 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Minute']);?>
</option>
					<option value="120" selected="selected">2 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Minute']);?>
</option>
					<option value="180">3 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Minute']);?>
</option>
					<option value="240">4 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Minute']);?>
</option>
					<option value="300">5 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Minute']);?>
</option>
				
                    </select>
                    </span>
					<div class="change_coll_repower_div" ></div>

				</td>
				
				<td class="belll_table_col_rightalign">
				
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Sampling_rate']);?>

				
				</td>
				
				<td nowrap class="bell_talbe_col_leftalign">
					
					<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['samplerate_task']);?>
"  class="terminal_select_style" name="samplerate" id="samplerate" style="width:80px;">	
						<option value="8000">8000Hz</option>
						<option value="11025">11025Hz</option>
						<option value="16000">16000Hz</option>
						<option value="44100">44100Hz</option>
						<option value="48000">48000Hz</option>
						<option value="64000">64000Hz</option>
						<option value="88200">88200Hz</option>
						<option value="96000">96000Hz</option>
						<option value="128000">128000Hz</option>
						<option value="256000">256000Hz</option>
						<option value="320000">320000Hz</option>
					</select>
				</td>
			  </tr>
			  	
			  <tr>
				<td class="belll_table_col_rightalign"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Terminal_channel']);?>
</td>
				
				<td nowrap class="bell_talbe_col_leftalign">
				<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['audioterminaltask']);?>
" class="terminal_select_style" name="channel" id="channel" style="width:80px;">
				
				</select>
				<?php echo '<script'; ?>
 language="javascript">
				function changeselect1(obj)
				{
					var terminal_id = obj.value;
				 	//   var pid=document.frmSearch2.audiosource.value;
					//alert(terminal_id);
					var url = "get_changeselect1.php?id=" + terminal_id;
					var obj = document.getElementById('channel');
				
					for(i=obj.length-1;i>=0;i--)
					{
						obj.remove(i);
					}
					 get_media_length1(obj,url);
     
              			for(i=0;i<ret;i++)
						  {
							var oOption = document.createElement("OPTION");	
							obj.options.add(oOption,i);
							if(i==0)
							{
								oOption.innerHTML = "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['mp3channe']);?>
";
							}
							else
							{
								oOption.innerHTML = i;
							}
							oOption.value = i+1;
						  }
					     // document.write("<option value="+(i+1)+"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Channel']);?>
_00"+(i+1)+"</option>");
					 	// document.frmSearch2.channe1.innerHTML = xmlhttp.r+esponseText;
				}
								

					<?php echo '</script'; ?>
>
				</td>
				
				<td class="belll_table_col_rightalign">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Bit_rate']);?>

				</td>
				
				<td nowrap class="bell_talbe_col_leftalign">
					<select title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['bitrate_task']);?>
" class="terminal_select_style" name="bandrate" id="bandrate" style="width:80px;">
					
					<?php echo '<script'; ?>
 language="javascript">
						for(var i=8; i<=128; i=i*2)
						{
							document.write("<option value='"+(i)+"'>"+(i)+"Kbp/s</option>");
						}
					<?php echo '</script'; ?>
>
				 </select>
				</td>
			  </tr>  
		  </table>
	  </td>
    </tr>
	
    <tr>
      <td colspan="2" align="left" valign="top">
	  <div class="fileadm_left_div"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Terminal_list']);?>
</div>
      <div id="terminallist" name="terminallist" class="bell_tree" style="overflow-y:auto;overflow-x:auto;"></div>
          <input type="hidden"  id="terminallistvalue" name="terminallistvalue" value=""/>
		 <input type="hidden"  id="get_terst" name="get_terst" value=""/>
		<input type="hidden"  id="get_position" name="get_position" value=""/>
		<input type="hidden"  id="get_inid" name="get_inid" value=""/>
				
		<input type="hidden"  id="get_terminal" name="get_terminal" value=""/>
		  <input type="hidden" id="analysis_tree_group_string" name="analysis_tree_group_string" value=""/>
		  
          <!--在此保存媒体列表的值-->
		
			  <?php echo '<script'; ?>
 language="javascript" defer="true">
				var te = 0;
				var states = 0;
				var get_terst ="";
				var get_inid ="";
				var get_id ="";
				var get_position ="";
				var get_position2 ="";
				var treeItemText = "";
				var x=200;
				var y=380;
				//  var get_text2 = "IP功放";
				var get_text2=trim("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['amplifier']);?>
");
				var get_text3 = new RegExp(get_text2);
				var get_amplifier = trim("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['lead']);?>
");
				var get_amplifier2 = new RegExp(get_amplifier);
				var terminal = trim("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['terminal']);?>
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
					function toncheck(id, state) 
					{
					
					 te = id;
				     var get_ids="";
                    //  var get_position4="";
					
					  treeItemText = tree3.getItemText(id);
					 // var get_amplifier = "IP前置";
					 
					if(id.length==8||id.length==9||id.length==10)
					{
					
					}
					else
					{
					
					
					 
					 if(state ==1)
					 {
					 
					    document.getElementById('lead').style.display = "none";
						//  get_id = get_id.replace(te,"");
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
						//document.getElementById('get_id').value = trim(get_id.toString());
					
						 get_inid = get_inid.replace(te+'|',"");
					  // document.getElementById('get_inid').value = trim(get_inid.toString()); 
					
				
					 }
					  }
					document.getElementById('get_inid').value = trim(get_inid.toString()); 
					document.getElementById('get_position').value = trim(get_position.toString());
			  var str_text=new Array();
				  str_text=id.split("::");
				 var strs_text ="";
				for (var i=1;i<2 ;i++ )   
    			{   
      			 strs_text=str_text[i]; 
			
				var url = "get_terminaltype.php?id="+strs_text+"";
				
				
				
   				}
				getchannelvalue(url);
               
			
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
					     states = state ;
						get_terst+=te+'|';
						
					}	
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
				
						 	te_len = bit_position3.substring(i,i+1);
					
							te_len2 =get_terminal.substring(l,l+12+parseInt(te_len));
						
							if(get_terminal.substring(l,l+12+parseInt(te_len))==get_te)
							{
						   
								
								var get_terminals =  get_terminal.substring(l,l+12+parseInt(te_len)+12);
								
								get_terminal = get_terminal.replace(get_terminals,"");
								 document.getElementById('get_terminal').value = trim(get_terminal.toString());
								 
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
						alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['select_broadcast_task']);?>
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
	
                          document.getElementById('get_terminal').value = trim(get_terminal.toString());
				
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
				var treedata = "<?php echo $_smarty_tpl->tpl_vars['terminalist']->value;?>
";
				tree3=new dhtmlXTreeObject("terminallist","100%","100%",0);
				tree3.setSkin('dhx_skyblue');
				tree3.setImagePath("smarty/templates/BellManager/codebase/csh_bluebooks/");
				tree3.enableCheckBoxes(1);
				tree3.enableThreeStateCheckboxes(true);
				tree3.setOnCheckHandler(toncheck);
				tree3.loadXMLString(treedata);	
			<?php echo '</script'; ?>
>
      </td>
    </tr>
    <input type="hidden" name="taskType" value="admmanagertask"/>
    <tr>
      <td height="28" colspan="2" align="center" valign="middle">
        <input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['submit_task']);?>
" name="Submit" type="submit" class="terminal_button" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Sumbit']);?>
"/>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <!--<input name="reset" type="reset" class="terminal_button" id="reset" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Cancel']);?>
"/>
		-->
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
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_1']);?>
";
				break;
				case 1:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_2']);?>
";
				break;
				case 2:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_3']);?>
";
				break;
				case 3:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_4']);?>
";
				break;
				case 4:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_5']);?>
";
				break;
				case 5:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_6']);?>
";
				break;
				case 6:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_7']);?>
";
				break;
				case 7:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_8']);?>
";
				break;
				case 8:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_9']);?>
";
				break;
				case 9:
				getcell+="&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_10']);?>
";
				break;
				case 10:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_11']);?>
";
				break;
				case 11:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_12']);?>
";
				break;
				case 12:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_13']);?>
";
				break;
				case 13:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_14']);?>
";
				break;
				case 14:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_15']);?>
";
				break;
				case 15:
				getcell+="<input type=\"checkbox\" value=\"1\" id=\"lead2\" name=\"lead2\"/><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['zone_16']);?>
";
				break;
			
			}
			if(i%2!=0)
				getcell+="\n";
		}
		if(i==channelnum)
		getcell+="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Sumbit']);?>
\"  onclick=\"set_task_volume_prepose();\"class=\"bell_button_style\"><input type=\"button\" value=\"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['Cancel']);?>
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
                x:document.getElementById( "terminallist").offsetWidth/4,
                y:document.getElementById( "terminallist").offsetHeight+200
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
<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>
 
</body>
</html>

<!--添加时间-->
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
	s += '<tr align="center" class="header"><td class="header"><a href="#" onclick="refreshcalendar(yy, mm-1);return false" title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['lastmonth']);?>
">&lt;&lt;</a></td><td colspan="5" style="text-align: center" class="header"><a href="#" onclick="showdiv(\'year\');_cancelBubble(event);return false" title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['pleaseclickyear']);?>
" id="year"></a>&nbsp; - &nbsp;<a id="month" title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['pleaseclickmonth']);?>
" href="#" onclick="showdiv(\'month\');_cancelBubble(event);return false"></a></td><td class="header"><A href="#" onclick="refreshcalendar(yy, mm+1);return false" title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['nextmonth']);?>
">&gt;&gt;</A></td></tr>';
	s += '<tr class="category"><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['day']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['one']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['two']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['three']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['four']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['five']);?>
</td><td><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['six']);?>
</td></tr>';
	for(var i = 0; i < 6; i++) {
		s += '<tr class="altbg2">';
		for(var j = 1; j <= 7; j++)
			s += "<td id=d" + (i * 7 + j) + " height=\"19\">0</td>";
		s += "</tr>";
	}
	s += '<tr id="hourminute"><td colspan="7" align="center"><input type="text" size="1" value="" id="hour" onKeyUp=\'this.value=this.value > 23 ? 23 : zerofill(this.value);controlid.value=controlid.value.replace(/\\d+(\:\\d+)/ig, this.value+"$1")\'> <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['point']);?>
 <input type="text" size="1" value="" id="minute" onKeyUp=\'this.value=this.value > 59 ? 59 : zerofill(this.value);controlid.value=controlid.value.replace(/(\\d+\:)\\d+/ig, "$1"+this.value)\'> <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['part']);?>
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
		s += '<a href="#" onclick="refreshcalendar(yy, ' + (k - 1) + ');$(\'calendar_month\').style.display=\'none\';return false"><span' + (today.getMonth()+1 == k ? ' class="today"' : '') + ' id="calendar_month_' + k + '">' + k + ( k < 10 ? '&nbsp;' : '') + ' <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['month']);?>
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
			dd.firstChild.title = '<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['today']);?>
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

	if(addtime) {
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
