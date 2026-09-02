<?php
/* Smarty version 3.1.30, created on 2026-05-25 11:41:00
  from "/var/www/html/ok112/smarty/templates/FileAd/FileTaskManager_from.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a13c4cc4801f1_54637887',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'be8409dc936ff9da85db3fc03f30fa9ff55e5bc8' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/FileAd/FileTaskManager_from.html',
      1 => 1778116067,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a13c4cc4801f1_54637887 (Smarty_Internal_Template $_smarty_tpl) {
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
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Broadcast_Task']);?>
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
	<tr align="center">
		<td nowrap="nowrap">
			<?php if ($_smarty_tpl->tpl_vars['is_right']->value == 1 || $_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>
				<input name="id" type="checkbox" id="id" value="<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>

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
				
			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 2) {?>
			
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
			<a  name="link_view" id="link_view" href="displayterminal.php?term_id=<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
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
<tr class="tablestyle" style="text-align:center">
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
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Solution_pause_task']);?>
" href="javascript:pauseFileTask()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Pauses']);?>

		</a>&nbsp;
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Solution_huifu_task']);?>
" href="javascript:huifuFileTask()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['huifu']);?>

		</a>&nbsp;
	
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Add_taskplay']);?>
" href="taskadd.php?folderid=<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
&userid=<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
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
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Emergency_Setting_play']);?>
" href="javascript:emergency_set()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Emergency_Setting']);?>

		</a>&nbsp;
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Emergency_Cancel_play']);?>
" href="javascript:emergency_cancel()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Emergency_Cancel']);?>

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
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Adjust_taskVolume']);?>
" href="javascript:void(0)" id="volume_handle" onclick="mouse_click_position(event)">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Adjust_Volume']);?>

		</a>
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['addtaskfolder']);?>
" name="creatfolder" href="javascript:addfolder(this,'<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
')" >
		<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['addfolder']);?>

		</a>&nbsp;
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['modifytaskfolder']);?>
"  href="javascript:modifyfolder(this,'<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
')" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['modifyfolder']);?>

		</a>&nbsp;
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['deltaskfolder']);?>
"  href="javascript:delfolder(this,'<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
')" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['delfolder']);?>

		</a>&nbsp;
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['copytaskfolder']);?>
" href="javascript:void(0)" id="setcopytask" onclick="task_copy_position(event)">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['copytask']);?>

		</a>&nbsp;
		<a  href="javascript:void(0)" id="setcopytask" onclick="taskfile_copy_position(event)">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['copyfiletask']);?>

		</a>&nbsp;
	
	</td>
</tr>
<?php echo '<script'; ?>
>
	rights_control(<?php echo $_smarty_tpl->tpl_vars['is_right']->value;?>
,"<?php echo $_smarty_tpl->tpl_vars['is_admin']->value;?>
","link_view","link_look");
/*	
	if(<?php echo $_smarty_tpl->tpl_vars['get_task_id']->value;?>
==0)
	{
			var a_obj = document.getElementsByTagName("a");
			for(var i=0; i<a_obj.length; i++)
			{
				if(a_obj[i].name == "creatfolder")
				{
					continue;
				}
		
				a_obj[i].href = "javascript:void(0)";
			
				
				a_obj[i].onclick = null;
				a_obj[i].style.color="#787878";
			}
	
	
	}*/
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
if(objwidth<=1200)
 {
  document.getElementById("divTest").style.width=1200+"px"; 
 }
 else
 {
  document.getElementById("divTest").style.width=document.getElementById( "divTest").offsetWidth;
 }
<?php echo '</script'; ?>
>

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
 <div id="copytask" class="r-displayVolume">
 <iframe style="position:absolute; width:200;height:110px;left:0px; top:0px;filter:alpha(opacity=0);-moz-opacity:0;border:0;z-index:-1"></iframe>
<div style="position:absolute;border:0;width:200; left:0px; top:0px; height:110px;z-index:100">
	<table border="1" cellpadding="10" cellspacing="0" width="200" style="background-color:#EEFFEE">
		
		<tr>
			<td nowrap="nowrap" align="right">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['from']);?>

			<select class="terminal_select_style" name="getcopytaskfrom" id="getcopytaskfrom" style="width:100px">
					<?php
$__section_sourceid_2_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid'] : false;
$__section_sourceid_2_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['gettreeid']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_sourceid_2_total = $__section_sourceid_2_loop;
$_smarty_tpl->tpl_vars['__smarty_section_sourceid'] = new Smarty_Variable(array());
if ($__section_sourceid_2_total != 0) {
for ($__section_sourceid_2_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] = 0; $__section_sourceid_2_iteration <= $__section_sourceid_2_total; $__section_sourceid_2_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']++){
?>
							<option title="<?php echo $_smarty_tpl->tpl_vars['gettreeid']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] : null)]['name'];?>
" value="<?php echo $_smarty_tpl->tpl_vars['gettreeid']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] : null)]['id'];?>
"><?php echo smarty_modifier_truncate($_smarty_tpl->tpl_vars['gettreeid']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] : null)]['name'],12,"...");?>
</option>
			
					<?php
}
}
if ($__section_sourceid_2_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_sourceid'] = $__section_sourceid_2_saved;
}
?>
					</select>
			</td>
		</tr>
		<tr>
			<td nowrap="nowrap" align="right">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['to']);?>

			<select class="terminal_select_style" name="getcopytaskto" id="getcopytaskto" style="width:100px">
					<?php
$__section_sourceid_3_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid'] : false;
$__section_sourceid_3_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['gettreeid']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_sourceid_3_total = $__section_sourceid_3_loop;
$_smarty_tpl->tpl_vars['__smarty_section_sourceid'] = new Smarty_Variable(array());
if ($__section_sourceid_3_total != 0) {
for ($__section_sourceid_3_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] = 0; $__section_sourceid_3_iteration <= $__section_sourceid_3_total; $__section_sourceid_3_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']++){
?>
							<option title="<?php echo $_smarty_tpl->tpl_vars['gettreeid']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] : null)]['name'];?>
" value="<?php echo $_smarty_tpl->tpl_vars['gettreeid']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] : null)]['id'];?>
"><?php echo smarty_modifier_truncate($_smarty_tpl->tpl_vars['gettreeid']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] : null)]['name'],12,"...");?>
</option>
					<?php
}
}
if ($__section_sourceid_3_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_sourceid'] = $__section_sourceid_3_saved;
}
?>
					</select>
			</td>
		<tr>
			<td  id="enterbutton" nowrap="nowrap" align="center">
				<a href="javascript:void(0)" onclick="set_task_copy()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Sumbit']);?>

				</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="javascript:void(0)" onclick="disappear_task_div()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Cancel']);?>

				</a>
			</td>
		</tr>
		
	</table>
</div>
</div>

<div id="copytaskfile" class="r-displayVolume">
 <iframe style="position:absolute; width:200;height:110px;left:0px; top:0px;filter:alpha(opacity=0);-moz-opacity:0;border:0;z-index:-1"></iframe>
<div style="position:absolute;border:0;width:200; left:0px; top:0px; height:110px;z-index:100">
	<table border="1" cellpadding="10" cellspacing="0" width="200" style="background-color:#EEFFEE">
		
		<tr>
			<td nowrap="nowrap" align="right">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Task_name']);?>

			 <input  maxlength="18" style="width:100px" name="task_name" type="text" id="task_name"/>
			</td>
		</tr>
		<tr>
			<td  id="enterbutton" nowrap="nowrap" align="center">
				<a href="javascript:void(0)" onclick="copyFileTask()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Sumbit']);?>

				</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="javascript:void(0)" onclick="disappear_taskfile_div()"> 
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

function ajax_set_task_volume(set_volume_server,volume_value,task_id)
{
   createXMLHttpRequest();
   
   if(task_id == "")
   {
   	if("<?php echo $_smarty_tpl->tpl_vars['language']->value;?>
"=="english")
			alert("<?php echo $_smarty_tpl->tpl_vars['task_manager']->value['select_task'];?>
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
function task_copy_position(event)
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
    get_div_obj('copytask').style.left = mouse_obj_xy.x+20+'px';
    get_div_obj('copytask').style.top = mouse_obj_xy.y+'px';
	get_div_obj('copytask').style.display = "block";
	document.getElementById('enterbutton').disabled=false;
}

function taskfile_copy_position(event)
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
    get_div_obj('copytaskfile').style.left = mouse_obj_xy.x+20+'px';
    get_div_obj('copytaskfile').style.top = mouse_obj_xy.y+'px';
	get_div_obj('copytaskfile').style.display = "block";
	document.getElementById('enterbutton').disabled=false;
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
<?php echo '</script'; ?>
>
</body>
</html><?php }
}
