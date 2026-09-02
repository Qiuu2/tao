<?php
/* Smarty version 3.1.30, created on 2026-07-06 15:51:20
  from "/var/www/html/ok112/smarty/templates/holidaymanager/holidayManager_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a4b5e789fd992_16883974',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '9533a9817606ff953c39010ff25159a874ebf0c9' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/holidaymanager/holidayManager_form.html',
      1 => 1778116071,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a4b5e789fd992_16883974 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
if (!is_callable('smarty_modifier_truncate')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.truncate.php';
?>
<form name="bellForm" class="terminal_form_to_body">
<tbody>
<div id="divTest" style="width:100%;overflow-x:hidden;overflow-y:scroll">
<table width="98%" border="0" cellpadding="2" cellspacing="1"  align="center" id="tablebell">
	<thead>
<tr align='center' class="terminal_table_row_bg">

	<th width="15%" nowrap="nowrap">
		<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Select']);?>
</th>
	
	<th width="30%" nowrap="nowrap" onclick="sortTable('tablebell', 1,'1')" class="sort_data_table_sequence">
		<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['holiday_Name']);?>
↑↓</th>
		<th width="10%" nowrap="nowrap" onclick="sortTable('tablebell', 2,'1')" class="sort_data_table_sequence">
		<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Status']);?>
↑↓</th>
	<th width="25%" nowrap="nowrap" onclick="sortTable('tablebell', 3,'1')" class="sort_data_table_sequence">
		<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Start_Date']);?>
↑↓</th>
	<th width="30%" nowrap="nowrap" onclick="sortTable('tablebell', 4,'1')" class="sort_data_table_sequence">
		<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['End_Date']);?>
↑↓</th>

</tr>
	</thead>
	<tbody>
<?php if (count($_smarty_tpl->tpl_vars['info']->value) != 0) {?>
 <?php
$__section_loop_0_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_loop']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop'] : false;
$__section_loop_0_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_loop_0_total = $__section_loop_0_loop;
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = new Smarty_Variable(array());
if ($__section_loop_0_total != 0) {
for ($__section_loop_0_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] = 0; $__section_loop_0_iteration <= $__section_loop_0_total; $__section_loop_0_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']++){
?>        
      <tr align='center' >
	   
		<td>
			<?php if ($_smarty_tpl->tpl_vars['is_right']->value == 1 || $_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>
				<input name="id" type="checkbox" id="id" value="<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['id'];?>
">
			<?php } else { ?>
				<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>

			<?php }?>
		</td>	
		
		<td>
			<label title="<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['name'];?>
">
				<?php echo smarty_modifier_truncate($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['name'],18,"...");?>

			</label>
		</td> 
		<td>
		<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['projectstate'] == 1) {?>
		<span style="color:#ff0000;">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Enabled']);?>
●	</span>
			<?php } else { ?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Disenabled']);?>
	
		<?php }?>
		</td>
		<td><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['startdate'];?>
</td>
		<td><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['enddate'];?>
</td>

      </tr>         
    <?php
}
}
if ($__section_loop_0_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = $__section_loop_0_saved;
}
?>	 
<?php } else { ?>
<tr align='center'>
	<td colspan="7">
		<strong><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['No_Record']);?>
</strong>
	</td>
</tr>
<?php }?>	
</table>
</div>
</tbody>
<table cellpadding="0" cellspacing="0">
<tr align="right">
<?php echo '<script'; ?>
 src="smarty/templates/UserAccessControl/CheckUserRights.js" language="javascript" type="text/javascript"><?php echo '</script'; ?>
>
	<td colspan="7" align="left" class="tablestyle">

		<a  href="javascript:selAll(0)" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Select_All']);?>

		</a>&nbsp;
		
		<a  href="javascript:noSelAll(0)" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Cancel']);?>

		</a>&nbsp;
		<a  href="javascript:enableholiday()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Enabled']);?>

		</a>&nbsp;
		<a  href="javascript:disableholiday()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Disenabled']);?>

		</a>&nbsp;
		<a  href="javascript:addholiday()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Add_holiday']);?>

		</a>&nbsp;
		
		<a  href="javascript:modifyholiday()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['modify_holiday']);?>

		</a>&nbsp;
		 
		<a href="javascript:delholiday()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['del_holiday']);?>

		</a>&nbsp;
		
	</td>
</tr>

<?php echo '<script'; ?>
>
	rights_control(<?php echo $_smarty_tpl->tpl_vars['is_right']->value;?>
,"<?php echo $_smarty_tpl->tpl_vars['is_admin']->value;?>
","link_view");
<?php echo '</script'; ?>
>
</table>
<?php echo '<script'; ?>
 language="javascript">

var   obj   =   document.getElementById( "divTest").offsetHeight; 
 if(obj>=600)
 {
 document.getElementById("divTest").style.height=600+"px"; 
 }
 else
 {
  document.getElementById("divTest").style.height=document.getElementById( "divTest").offsetHeight;
 }
 var objwidth = document.getElementById( "divTest").offsetWidth;
if(objwidth<=1000)
 {
 document.getElementById("divTest").style.width=1000+"px"; 
 }
 else
 {
  document.getElementById("divTest").style.width=document.getElementById( "divTest").offsetWidth;
 }
 
<?php echo '</script'; ?>
>

</form>
<table align="center">
 <tr>
    <td><div class="link_style" align="center"><?php echo $_smarty_tpl->tpl_vars['pagestr']->value;?>
</div></td>
 </tr>
</table>

<p>&nbsp;</p>
<form name="form1" action="displayholidaymanager.php" method="get">
<input type='hidden' name='dopost' value='' />
<table width='98%'  border='0' cellpadding='1' cellspacing='1' class="middle" align="center" style="margin-top:8px">
  <tr>
    <td background='skin/images/wbg.gif' align='center'>
      <table border='0' cellpadding='0' cellspacing='0'>
        <tr>
          <td width='90' align='center'><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Search_conditions'];?>
</td>
          <td width='160'>
          <select name='searchkey' id="searchkey" style='width:150px'>
            <option value=""><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Select_type'];?>
</option>
            <option value="startdate"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Start_Date']);?>
</option>
          </select>
        </td>
        <td width='70'>
          <?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Keyword'];?>

        </td>
        <td width='160'>
          	<input type='text' name='searchvalue' value='' style='width:150px' />
        </td>
        <td width='110'>
    		<select name='searchsequence' id="searchsequence" style='width:80px'>
    		  <option value=""><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Sort'];?>
</option>
    		  <option value="startdate"><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Time'];?>
</option>
      	    </select>
        </td>
        <td>
          <input name="imageField" type="image" src="<?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['search_image'];?>
" style="width:45px; height:20px; border-bottom-width:0px;"/>
        </td>       
       </tr>
      </table>
    </td>
  </tr>
</table>
</form>
<!--修改声音-->
  <?php echo '<script'; ?>
 type="text/javascript" src="skin/js/frame/jquery.min.js"><?php echo '</script'; ?>
>
 <?php echo '<script'; ?>
 type="text/javascript" src="skin/js/frame/rating.min.js"><?php echo '</script'; ?>
>
<div id="change_volume" class="r-displayVolume">
 <iframe style="position:absolute; width:145;height:105px;left:0px; top:0px;filter:alpha(opacity=0);-moz-opacity:0; border:0;z-index:-1"></iframe>
<div style="position:absolute;border:0;width:150; left:0px; top:0px; height:110px;z-index:100">

	<table border="0" cellpadding="10" cellspacing="0" width="150" style="background-color:#EEFFEE">
		
		<tr>
			<td nowrap="nowrap" align="center">
			<div id="s1"></div>
			<div id="d1"></div>
			</td>
		</tr>
		<tr>
			<td nowrap="nowrap" align="center">
				<a href="javascript:void(0)" onclick="set_task_volume()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Submit']);?>

				</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="javascript:void(0)" onclick="disappear_volume_div()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['bell_manager']->value['Cancel']);?>

				</a>
			</td>
		</tr>
		
	</table>
</div>
</div>

<?php echo '<script'; ?>
 language="javascript">

var hidden_display = true;//默认隐藏
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

function ajax_set_task_volume(set_volume_server,volume_value,task_id)
{
   createXMLHttpRequest();
   
   if(task_id == "")
   {
 	if("<?php echo $_smarty_tpl->tpl_vars['language']->value;?>
"=="english")
			alert("<?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['select_task'];?>
");
		else
		alert('请选择任务'); 
	
		return void(0);
   }
  
   xmlhttp.open( "get","set_task_volume_server.php?tasktype="+set_volume_server+"&volume="+volume_value+"&task_id="+task_id+"",true );
   
   
   xmlhttp.onreadystatechange = function()
   {
      if( xmlhttp.readyState == 4 )
      {
         if( xmlhttp.status == 200 )
         {
            if( xmlhttp.responseText == 0)
            {
			if("<?php echo $_smarty_tpl->tpl_vars['language']->value;?>
"=="english")
					alert("fail");
				else
				alert('失败');
            }
			else if(xmlhttp.responseText == 1)
			{
			if("<?php echo $_smarty_tpl->tpl_vars['language']->value;?>
"=="english")
					alert("success");
				else
				alert('成功');
				
				get_div_obj('change_volume').style.display = "none";
				
				self.location.reload();
			}
         }
		 else
		 {
		 if("<?php echo $_smarty_tpl->tpl_vars['language']->value;?>
"=="english")
					alert("fail");
				else
			alert('访问失败');	 
		 }
      }
   }
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	xmlhttp.send(null);
}

function get_mouse_coordinates(event)
{
   var eve = event||window.event;
   if(eve.pageX)
   {

    return {x:eve.pageX,y:eve.pageY};
   }
   else
   {

    return {
                x:eve.clientX+document.body.scrollLeft - document.body.clientLeft,
                y:eve.clientY+document.body.scrollTop - document.body.clientTop
            };
   }
}
function get_div_obj(str_id)
{
 	return document.getElementById(str_id);   
}
function mouse_click_position(event)
{
    if(document.all)
    {
        window.event.cancelBubble = true;   
    }
    else
    {
        event.stopPropagation();
    }
    var mouse_obj_xy = get_mouse_coordinates(event);
    get_div_obj('change_volume').style.left = mouse_obj_xy.x+20+'px';
    get_div_obj('change_volume').style.top = mouse_obj_xy.y+'px';
	get_div_obj('change_volume').style.display = "block";
}
 $(document).ready(function () {
          $('#s1').slidy({
            maxval: 100, 
            interval: 1,
            defaultValue: 50,
           
            moveCallback: function (value) {
              $('#d1').html('<strong>' + value + '</strong>');
            }
          });

        })
function animation_text_start()
{
   if(str_temp_count <= 3)
   {
      str_temp_txt = str_temp_txt+".";
      document.getElementById('prompt').innerHTML = temp_txt+""+str_temp_txt;
      str_temp_count++;
      inver_time_handle = setTimeout("animation_text_start()", 500);
   }
   else
   {
      str_temp_count = 0;
	  str_temp_txt = "";
	  document.getElementById('prompt').innerHTML = temp_txt;
   }
}
function animation_text_start1()
{
	if(str_temp_count <= 3)
   {
      str_temp_txt = str_temp_txt+".";
      document.getElementById('prompt_restore').innerHTML = temp_txt+""+str_temp_txt;
      str_temp_count++;
      inver_time_handle = setTimeout("animation_text_start1()", 500);
   }
   else
   {
      str_temp_count = 0;
	  str_temp_txt = "";
	  document.getElementById('prompt_restore').innerHTML = temp_txt;
   }
}


<?php echo '</script'; ?>
>


<?php if ($_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>

<?php } else { ?>
	<?php echo '<script'; ?>
>
		var input_array = document.all.tags("a");
		for(var i=0; i<input_array.length; i++)
		{
			input_array[i].href = "javascript:void(0);";
			input_array[i].onclick = null;
			input_array[i].disabled = true;
		}
	<?php echo '</script'; ?>
>
<?php }?>
<!--备份数据-->
<div id="light" class="backup_content">

<table width="80%" height="50%" border="0" align="center" cellpadding="5" cellspacing="0" class="backup_table">
    <tr>
        <td colspan="2" class="backup_title">
            <?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['Backup_data'];?>

        </td>
    </tr>
	<tr>
		<td colspan="2" nowrap="nowrap">
			<span id="prompt" style="font-size:12px">
				<?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['Please_wait_being_backed_up'];?>

				<img src=\'skin/images/frame/please_wait.gif\'/>
			</span>
		</td>
	</tr>
	
	<tr>
		<td colspan="2">
			
		</td>
	</tr>
	
    <tr>
        <td align='right' nowrap="nowrap">
            <?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['Backup_Name'];?>

        </td>
        <td align='left' nowrap="nowrap">
            <input type="text" name="backup_name" id="backup_name"/>
        </td>
    </tr>
    <tr>
          <td colspan="2" align="center" nowrap="nowrap">
            <input type="button" value="<?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['Backup'];?>
" class="backup_button" onclick= "validate_backup_name('backup_name')"/>
            	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="reset" value="<?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['close'];?>
" onclick="colose_backup();" class="backup_button"/>
        </td>  
    </tr>
</table>

</div> 
<div id="fade" class="backup_overlay"></div> 
<!--还原数据-->
<div id="restore_light" class="backup_content">
<form  action="bell_manager_restore.php" onSubmit="return checkform();" method="post" enctype="multipart/form-data">

<table width="80%" height="50%" border="0" align="center" cellpadding="5" cellspacing="0" class="backup_table">
    <tr>
        <td colspan="2" class="backup_title">
            <?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['restore_data'];?>

        </td>
    </tr>
	<tr>
		<td colspan="2" nowrap="nowrap">
			<span id="prompt1" style="font-size:12px">
				<?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['Please_wait_being_restore_up'];?>

				<img src=\'skin/images/frame/please_wait.gif\'/>
			</span>
		</td>
	</tr>
	
	<tr>
		<td colspan="2">
			
		</td>
	</tr>
	
    <tr>
        <td align='right' nowrap="nowrap">
            <?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['restore_name'];?>

        </td>
        <td align='left' nowrap="nowrap">
            <input type="file" name="upfile" id="upfile"/>
        </td>
    </tr>
    <tr>
          <td colspan="2" align="center" nowrap="nowrap">
            <input type="submit" name="submit" value="<?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['restore'];?>
" class="backup_button"/>
            	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="reset" name="reset" value="<?php echo $_smarty_tpl->tpl_vars['bell_manager']->value['close'];?>
" onclick="colose_restore();" class="backup_button"/>
        </td>  
    </tr>
</table>
</form>
</div> 
<div id="restore_fade" class="backup_overlay"></div> 

</body>
</html><?php }
}
