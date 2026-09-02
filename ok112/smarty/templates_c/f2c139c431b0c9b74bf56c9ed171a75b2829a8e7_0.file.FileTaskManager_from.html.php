<?php
/* Smarty version 3.1.30, created on 2026-05-25 16:17:28
  from "/var/www/html/ok112/smarty/templates/zhaoshengtask/FileTaskManager_from.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a140598f27cf5_13979348',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'f2c139c431b0c9b74bf56c9ed171a75b2829a8e7' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/zhaoshengtask/FileTaskManager_from.html',
      1 => 1778116120,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a140598f27cf5_13979348 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
if (!is_callable('smarty_modifier_truncate')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.truncate.php';
?>
<form name="form2" class="terminal_form_to_body" style="width:100%">
<tbody>
<div id="divTest" style="width:100%;overflow-x:hidden;overflow-y:scroll">
<table width="98%" border="0" cellpadding="2" cellspacing="1"  align="center" id= "tasklist" style="font-size:12px">
	<thead> 
    <tr align='center' class="terminal_table_row_bg">
	
		<th width="5%" nowrap>
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Select']);?>

		</th>       	
		<th width="8%" nowrap onclick="sortTable('tasklist', 1,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['zhaoshen_Task']);?>
↑↓
		</th> 
		
		<th width="8%" nowrap onclick="sortTable('tasklist', 2,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Play_Cycle']);?>
↑↓
		</th>  
		   
		<th width="8%" nowrap onclick="sortTable('tasklist', 3,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Start_Date']);?>
↑↓
		</th>   

		<th width="8%" nowrap onclick="sortTable('tasklist', 4,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['End_Date']);?>
↑↓
		</th>  
		
		<th width="5%" nowrap onclick="sortTable('tasklist', 5,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Run_Time']);?>
↑↓
		</th>	
		          	    
		<th width="5%" nowrap onclick="sortTable('tasklist', 6,'1')" class="sort_data_table_sequence"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Duration']);?>
↑↓</th>
		
		<th width="5%" nowrap onclick="sortTable('tasklist', 7,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Status']);?>
↑↓
		</th>
		
		<th width="5%" nowrap onclick="sortTable('tasklist', 8,'1')" class="sort_data_table_sequence" ><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Play_Mode']);?>
↑↓</th>
		
		<th width="5%" nowrap onclick="sortTable('tasklist', 9,'1')" class="sort_data_table_sequence" ><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Volume']);?>
↑↓</th>    
		
		<th width="5%" nowrap onclick="sortTable('tasklist', 10,'1')" class="sort_data_table_sequence" ><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Task_level']);?>
↑↓</th>  
		<th width="5%" nowrap onclick="sortTable('tasklist', 11,'1')" class="sort_data_table_sequence" ><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['belonguser']);?>
↑↓</th>  
		<th width="10%" nowrap onclick="sortTable('tasklist', 12,'1')" class="sort_data_table_sequence" ><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['On_Playing']);?>
↑↓</th>
		<th width="8%" nowrap><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Terminal_Attribute']);?>
</th> 
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
	<tr align="center" >
		<td nowrap="nowrap">
			<?php if ($_smarty_tpl->tpl_vars['is_right']->value == 1 || $_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>
				<input name="id" type="checkbox" id="id" value="<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
">
			<?php } else { ?>
				<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>

			<?php }?>
		</td>		
		<td nowrap="nowrap">
			<label title="<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskname'];?>
"><?php echo smarty_modifier_truncate($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskname'],30,"..");?>
</label>
		</td> 
		
		<td nowrap="nowrap">
			<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 7) {?>
				<span style="color:#FF0000" id="emergency_mark" name="emergency_mark"><?php echo $_smarty_tpl->tpl_vars['task_manager']->value['Emergency_Broadcast'];?>
</span>
				
			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 25) {?>
			
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'] == "0000000") {?>
				
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Manual']);?>

				
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'] != "0000000") {?>
				
					<?php echo '<script'; ?>
 language="javascript">document.write(getdayofweek("<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'];?>
"))<?php echo '</script'; ?>
> 
				
				<?php }?>
			<?php }?>
		</td>
		    
		<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['startdate'];?>
 </td>              	
		<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['enddate'];?>
 </td> 
		
		<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['playtime'];?>
 </td>   
		
		<td nowrap="nowrap">
		
		
				<?php echo '<script'; ?>
 language="javascript">
				var gethour;
				var getmin;
				var getsen;
				var gettime=<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['timelength'];?>
;
				var gettimetype=<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['timelengthtype'];?>
;
				if(gettimetype==1)
				{
					gethour=parseInt(gettime/3600);
					getmin=parseInt((gettime-gethour*3600)/60);
					getsen=gettime-gethour*3600-getmin*60;
					if(gethour>0)
						document.write(gethour,'<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['hour'];?>
');
					if(getmin>0)
						document.write(getmin,'<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['min'];?>
');
					if(getsen>0)
						document.write(getsen,'<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['sec'];?>
');
				}
			else
				document.write(gettime,'<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Times'];?>
');
				<?php echo '</script'; ?>
>
		</td>
		
		<td nowrap="nowrap">
			<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 0 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['projectstate'] == 0) {?>	
			<span style="color:#ff0000;">
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Enable'];?>
●</span>
			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 0 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['projectstate'] == 1) {?>	
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Disable'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 3) {?> 
				<span style="color:#0f6b24;">       
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Execution'];?>
 </span> 
			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 2) {?> 
			<span style="color:#0f6b24;">  
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Pause'];?>
 </span>
			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 1) {?>
				<span style="color:#0f6b24;">  
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Execution'];?>
 </span>
			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 4) {?>
				<span style="color:#0f6b24;">  
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['pauses'];?>
</span> 
			<?php }?> 
		</td>
		
		<td nowrap="nowrap">
			<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['israndomplay'] == 0) {?> 
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Order'];?>

			<?php } else { ?>      
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Random'];?>
  
			<?php }?>  
		</td>
		
		<td nowrap="nowrap">
			<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['defaultvolume'];?>

		</td>


		<td nowrap="nowrap">
			<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['priority'];?>

		</td>
	<td nowrap="nowrap">
			<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['username'];?>

		</td>


		<td nowrap="nowrap">
		  <?php
$__section_media_1_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_media']) ? $_smarty_tpl->tpl_vars['__smarty_section_media'] : false;
$__section_media_1_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['medialist']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_media_1_total = $__section_media_1_loop;
$_smarty_tpl->tpl_vars['__smarty_section_media'] = new Smarty_Variable(array());
if ($__section_media_1_total != 0) {
for ($__section_media_1_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_media']->value['index'] = 0; $__section_media_1_iteration <= $__section_media_1_total; $__section_media_1_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_media']->value['index']++){
?>
			  <?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['playfileid'] == $_smarty_tpl->tpl_vars['medialist']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_media']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_media']->value['index'] : null)]['id']) {?>
				 <label title="<?php echo $_smarty_tpl->tpl_vars['medialist']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_media']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_media']->value['index'] : null)]['name'];?>
"> 
				 	<?php echo smarty_modifier_truncate($_smarty_tpl->tpl_vars['medialist']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_media']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_media']->value['index'] : null)]['name'],15,"...");?>

				 </label>		  	  
			  <?php }?>
		  <?php
}
}
if ($__section_media_1_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_media'] = $__section_media_1_saved;
}
?>
		</td>

		<td nowrap="nowrap">
			<a  name="link_view" id="link_view" href="soundstaskdisplayterminal.php?term_id=<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Terminal']);?>

			</a>

			<a  name="link_look" id="link_look" href="displaymedia.php?id=<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Media']);?>

			</a> 
		</td>
	</tr>
<?php
}
}
if ($__section_loop_0_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = $__section_loop_0_saved;
}
} else { ?>
<tr class="tablestyle" style="text-align:center" >
	<td colspan="12">
		<strong style="font-size:12px;"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['No_Record']);?>
</font></strong>
	</td>
</tr>
<?php }?>
</table>
</div>
</tbody>
<table cellpadding="0" cellspacing="0">
<tr>
<?php echo '<script'; ?>
 src="smarty/templates/UserAccessControl/CheckUserRights.js" type="text/javascript" language="javascript"><?php echo '</script'; ?>
>
	<td colspan="12" class="tablestyle">
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Select_Alltask']);?>
" href="javascript:selAll(0)" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Select_All']);?>

		</a>&nbsp;
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['noSelect_Alltask']);?>
" href="javascript:noSelAll(0)" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Cancel']);?>

		</a>&nbsp;
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Solution_runtask']);?>
" href="javascript:startFileTask()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Run']);?>

		</a>&nbsp;
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Solution_pausetask']);?>
" href="javascript:stopFileTask()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Pause']);?>

		</a>&nbsp;
	
		<!--启用文件广播-->
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Solution_enabletask']);?>
" href="javascript:start_file_task()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Enable']);?>

		</a>&nbsp;
		<!--停用文件广播-->
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Solution_disabletask']);?>
" href="javascript:stop_file_task()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Disable']);?>

		</a>&nbsp;
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Add_taskplay']);?>
" href="zhaoshengtaskadd.php?userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
" target='_self' >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Add']);?>

		</a>&nbsp;
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Modify_taskplay']);?>
" href="javascript:modifyFileTask()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Modify']);?>

		</a>&nbsp;
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Delete_taskplay']);?>
" href="javascript:delTask()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Delete']);?>

		</a>&nbsp;
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['defaultvolume']);?>
" href="javascript:void(0)" id="volume_handle" onclick="mouse_click_position(event)">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['defaultvolume']);?>

		</a>
		<a  href="javascript:enabledefaultvolume()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['enabledefaultvolume']);?>

		</a>&nbsp;
	</td>
</tr>
</table>
</form>
<table border="0" width="100%" align="center">
	<tr>
		<td width="50%"></td>
		<td nowrap="nowrap">
    		<div class="link_style"><?php echo $_smarty_tpl->tpl_vars['pagestr']->value;?>
</div>
		</td>
		<td width="50%"></td>
	</tr>
</table>
<!--  搜索表单  -->

<form style="z-index:-1;" name='form3' action="taskmanager.php?id=<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
" method='post'>

<input type='hidden' name='dopost' value='' />

<table width='98%'  border='0' cellpadding='1' cellspacing='1' align="center">
  <tr>
    <td background='skin/images/wbg.gif' align='center'>
      <table border='0' cellpadding='0' cellspacing='0' class="fileadm_font_style">
        <tr>
          <td width='90' align='center'><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Search_conditions'];?>
</td>
          <td width='160'>
          <select name='searchkey' id="searchkey">
            <option value=""><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Select_type'];?>
</option>
            <option value="taskname"><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Task_name'];?>
</option>
            <option value="playtime"><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Execution_time'];?>
</option>
          </select>
        </td>
        <td width='70'>
          <?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Keyword'];?>

        </td>
        <td width='160'>
          	<input name='searchvalue' type='text' id="searchvalue" style='width:150px' value='' />
        </td>
        <td width='110'>
    		<select name='searchsequence' id="searchsequence">
    		  <option value=""><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Sort'];?>
</option>
    		  <option value="playtime"><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Time'];?>
</option>
      	    </select>
        </td>
        <td>
          <input name="imageField" type="image" src="<?php echo $_smarty_tpl->tpl_vars['task_manager']->value['search_image'];?>
" width="45px" height="20px" border="0px"/>
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

	<table border="0" cellpadding="0" cellspacing="0" width="150" style="background-color:#EEFFEE">
			<tr>
			<td nowrap="nowrap" align="center">
			&nbsp;&nbsp;  <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['volumedb']);?>
0-<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['dbvalue']);?>
<input  name="db_value0" id="db_value0" align="left" value="<?php echo $_smarty_tpl->tpl_vars['db_value0']->value;?>
" type="Text" size="2">
			</td>
			</tr>
			<tr>
			<td nowrap="nowrap" align="center">
			&nbsp;	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['volumedb']);?>
20-<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['dbvalue']);?>
<input  name="db_value1" id="db_value1" align="left" value="<?php echo $_smarty_tpl->tpl_vars['db_value1']->value;?>
" type="Text" size="2">
			</td>
			</tr>
			<tr>
			<td nowrap="nowrap" align="center">
			&nbsp;	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['volumedb']);?>
40-<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['dbvalue']);?>
<input  name="db_value2" id="db_value2" align="left" value="<?php echo $_smarty_tpl->tpl_vars['db_value2']->value;?>
" type="Text" size="2">
			</td>
			</tr>
			<tr>
			<td nowrap="nowrap" align="center">
			&nbsp;	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['volumedb']);?>
60-<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['dbvalue']);?>
<input  name="db_value3" id="db_value3" align="left" value="<?php echo $_smarty_tpl->tpl_vars['db_value3']->value;?>
" type="Text" size="2">
			</td>
			</tr>
			<tr>
			<td nowrap="nowrap" align="center">
			&nbsp;	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['volumedb']);?>
80-<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['dbvalue']);?>
<input  name="db_value4" id="db_value4" align="left" value="<?php echo $_smarty_tpl->tpl_vars['db_value4']->value;?>
" type="Text" size="2">
			</td>
			</tr>
			<tr>
			<td nowrap="nowrap" align="center">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['volumedb']);?>
100-<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['dbvalue']);?>
<input  name="db_value5" id="db_value5" align="left" value="<?php echo $_smarty_tpl->tpl_vars['db_value5']->value;?>
" type="Text" size="2">
			</td>
			</tr>
		<tr>
			<td nowrap="nowrap" align="center">
				<a href="javascript:void(0)" onclick="set_task_volume()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Sumbit']);?>

				</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="javascript:void(0)" onclick="disappear_volume_div()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Cancel']);?>

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

function ajax_set_task_volume(set_volume_server,db_value0,db_value1,db_value2,db_value3,db_value4,db_value5,task_id)
{
   createXMLHttpRequest();
   
  
   xmlhttp.open( "get","set_task_volume_server.php?tasktype="+set_volume_server+"&db_value0="+db_value0+"&db_value1="+db_value1+"&db_value2="+db_value2+"&db_value3="+db_value3+"&db_value4="+db_value4+"&db_value5="+db_value5+"&task_id="+task_id+"",true );
   
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

<?php echo '</script'; ?>
>
</body>
</html><?php }
}
