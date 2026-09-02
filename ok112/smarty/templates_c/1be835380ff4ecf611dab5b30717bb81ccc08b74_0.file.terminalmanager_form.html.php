<?php
/* Smarty version 3.1.30, created on 2026-07-06 15:50:22
  from "/var/www/html/ok112/smarty/templates/TerminalManager/terminalmanager_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a4b5e3e9208b1_81732448',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '1be835380ff4ecf611dab5b30717bb81ccc08b74' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/TerminalManager/terminalmanager_form.html',
      1 => 1778116105,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a4b5e3e9208b1_81732448 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
if (!is_callable('smarty_modifier_truncate')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.truncate.php';
?>
<form name="form2" >
<tbody>
<div id="divTest"  style="width:100%;overflow-x:scroll;overflow-y:scroll">
	<table width="98%" align='center' border="0" cellpadding="3" cellspacing="2"   id="tableSort">
	<thead>
		<tr align='center' class="terminal_table_row_bg">
			<th width="3%" nowrap="nowrap"  class="change_thead_style">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['xuhaoSelect']);?>

			</th> 
			<th width="8%" nowrap="nowrap" onclick="sortTable('tableSort', 1,'1')" class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Terminal_Name']);?>
↑↓
			</th>  
			<?php echo '<script'; ?>
 language="javascript">
			window.onload = function ()
			{
				sortTable('tableSort', 4,'1');
				var xunhuan=0;
			}
			<?php echo '</script'; ?>
>
		<!--  
			<td width="12%" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Zone']);?>
</td> 
		-->      
			<th width="4%" nowrap="nowrap" onclick="sortTable('tableSort', 2,'1')" class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Terminal_Type']);?>
↑↓
			</th>  
			
			<th width="4%" nowrap="nowrap" onclick="sortTable('tableSort',3,'1')" class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Task_Status']);?>
↑↓
			</th>  
			
			<th width="4%" nowrap="nowrap" onclick="sortTable('tableSort',4,'1')" class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Network_Status']);?>
↑↓
			</th>
			
			<th width="4%" nowrap="nowrap" onclick="sortTable('tableSort',5,'1')" class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Device_Status']);?>
↑↓
			</th>
			
			<th width="4%" nowrap="nowrap" onclick="sortTable('tableSort',6,'1')" class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['IP_Address']);?>
↑↓
			</th>    
				<!--   
			<th width="4%" nowrap="nowrap" onclick="sortTable('tableSort',7,'1')" class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['skaddress']);?>
↑↓
			</th>
			<th width="4%" nowrap="nowrap"  onclick="sortTable('tableSort',8,'1')" class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['skdevtype']);?>
↑↓
			</th>
			-->		
			<th width="4%" nowrap="nowrap" onclick="sortTable('tableSort',7,'1')" class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Volume']);?>
↑↓
			</th>
			
			<th width="4%" nowrap="nowrap"  class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Talkback_Status']);?>

			</th>
			<th width="4%" nowrap="nowrap"  class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Termianl_manager_Shortcut']->value);?>

			</th>
			
			<th width="4%" nowrap="nowrap"  class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Termianl_manager_SInstancy']->value);?>

			</th>
			<th width="4%" nowrap="nowrap"  class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Termianl_manager_Record']->value);?>

			</th>
			<!--<th width="4%" nowrap="nowrap" onclick="sortTable('tableSort', 14,'1')" class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Get_Talkback']);?>
↑↓
			</th>-->
			<th width="4%" nowrap="nowrap"  class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['posoner']);?>

			</th>
		<!--	<th width="4%" nowrap="nowrap" onclick="sortTable('tableSort', 16,'1')" class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['shortcircuit']);?>

			</th>-->
			<th width="4%" nowrap="nowrap"  class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['lopencircuit']);?>

			</th>
			<th width="4%" nowrap="nowrap"  class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['ropencircuit']);?>

			</th>
			<th width="4%" nowrap="nowrap"  class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['temperature']);?>

			</th>
			<th width="4%" nowrap="nowrap"  class="sort_data_table_sequence">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['humidity']);?>

			</th>
			<th width="4%" nowrap="nowrap"  class="change_thead_style">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Browse']);?>

			</th>
		</tr> 
	</thead>

		<?php if (count($_smarty_tpl->tpl_vars['terminal_info']->value) != 0) {?>        
		<?php
$__section_loop_0_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_loop']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop'] : false;
$__section_loop_0_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['terminal_info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_loop_0_total = $__section_loop_0_loop;
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = new Smarty_Variable(array());
if ($__section_loop_0_total != 0) {
for ($__section_loop_0_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] = 0; $__section_loop_0_iteration <= $__section_loop_0_total; $__section_loop_0_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']++){
?>   
		   	<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 0) {?>
					<?php echo '<script'; ?>
 language="javascript">
					//setrowcolor('tableSort',xunhuan);
					<?php echo '</script'; ?>
>	
			<?php }?>
		  <tr align='center' class="terminal_per_row"  style="width:100%;height:400;overflow:scroll">          	
			<td nowrap="nowrap" align="right">
			<?php if ($_smarty_tpl->tpl_vars['is_right']->value == 1 || $_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>
				<?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['id'];?>

				
				<input name="id" type="checkbox" id="id" value="<?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['id'];?>
">
			<?php } else { ?>
				<?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['id'];?>

				<!--
				<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>

				-->
			<?php }?>
			</td>	
			<td nowrap="nowrap">
				<label title="<?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['terminalname'];?>
">
					<?php echo smarty_modifier_truncate($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['terminalname'],18,"...");?>

				</label>
			</td>	
	<!--
			 <td nowrap="nowrap">
				<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['groupid'] != 0) {?>
				 <?php
$__section_stream_1_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_stream']) ? $_smarty_tpl->tpl_vars['__smarty_section_stream'] : false;
$__section_stream_1_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['stream_info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_stream_1_total = $__section_stream_1_loop;
$_smarty_tpl->tpl_vars['__smarty_section_stream'] = new Smarty_Variable(array());
if ($__section_stream_1_total != 0) {
for ($__section_stream_1_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index'] = 0; $__section_stream_1_iteration <= $__section_stream_1_total; $__section_stream_1_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index']++){
?>
						 <?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['groupid'] == $_smarty_tpl->tpl_vars['stream_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index'] : null)]['id']) {?>
							 <?php echo $_smarty_tpl->tpl_vars['stream_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index'] : null)]['name'];?>

						 <?php }?>
				 <?php
}
}
if ($__section_stream_1_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_stream'] = $__section_stream_1_saved;
}
?> 
				 <?php } else { ?>
				 	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Unknown_area']);?>

				 <?php }?>
			 </td> 
	-->
			 <td nowrap="nowrap">
			 	<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'] != -1) {?>
					 <?php
$__section_type_2_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_type']) ? $_smarty_tpl->tpl_vars['__smarty_section_type'] : false;
$__section_type_2_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['type_info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_type_2_total = $__section_type_2_loop;
$_smarty_tpl->tpl_vars['__smarty_section_type'] = new Smarty_Variable(array());
if ($__section_type_2_total != 0) {
for ($__section_type_2_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] = 0; $__section_type_2_iteration <= $__section_type_2_total; $__section_type_2_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']++){
?>
						<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'] == $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['id']) {?>        	 		
							<!--<?php echo $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['name'];?>
-->		
							<?php echo '<script'; ?>
 language="javascript">
								document.write(chinese_big5_english("<?php echo $_smarty_tpl->tpl_vars['chinese_big5_english']->value;?>
","<?php echo $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['name'];?>
"));	
							<?php echo '</script'; ?>
>		      			  
						<?php }?>
					 <?php
}
}
if ($__section_type_2_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_type'] = $__section_type_2_saved;
}
?> 
				 <?php } else { ?>
				 	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Unknown_type']);?>

				 <?php }?> 	     
			 </td> 
			 <td nowrap="nowrap">
					<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 0) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Disconnected']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 0) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Idle']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 1) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Timing_Play']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 2) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['On_Talkback']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 3) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['AOD']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 4) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Selected_Play']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 5) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Paging']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 6) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Paging']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 7) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Local_Amplifying']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 8) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Play_from_USB']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 9) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Request_Talkback']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 10) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Requested_Talkback']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 11) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Paging_Play']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 12) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Timing_Play']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 13) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['livestate']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 25) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['ttsmusic']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 15) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['live_do']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 19) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['jianting']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 28) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['quickplay']);?>

					<?php }?>
			</td>
			 <td nowrap="nowrap">
				 <?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 0) {?>
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Disconnected']);?>

				 <?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1) {?>
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Connected']);?>

				 <?php }?>
			 </td>	
			 	 
			 <td nowrap="nowrap">
				 <?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 0) {?>
				 <span style="color:#ff0000;">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Disconnected']);?>
</span>
					
				 <?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['devicestate'] == 0) {?>
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Disabled']);?>

				 <?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['devicestate'] == 1) {?>
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Enabled']);?>

				 <?php }?>
			 </td>
			 
			 <td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['ip'];?>
</td> 	             
			<!--  <td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['skaddress'];?>
</td> 	  
			  		  <td nowrap="nowrap">
					  	<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['skdevtype'] == 11) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['sksend']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['skdevtype'] == 22) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['skreceive']);?>

						<?php }?>
						</td> 	
			-->      
			 <td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['volume'];?>
</td> 
			 <td nowrap="nowrap">			 
			 <?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'] != -1) {?>
			 
				<?php
$__section_type_3_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_type']) ? $_smarty_tpl->tpl_vars['__smarty_section_type'] : false;
$__section_type_3_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['type_info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_type_3_total = $__section_type_3_loop;
$_smarty_tpl->tpl_vars['__smarty_section_type'] = new Smarty_Variable(array());
if ($__section_type_3_total != 0) {
for ($__section_type_3_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] = 0; $__section_type_3_iteration <= $__section_type_3_total; $__section_type_3_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']++){
?>
				
					<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['isspeech'] == 1 && $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['isspeech'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'] == $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['id'] && $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['isdecode'] == 1 && $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['isencode'] == 1) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Talkback']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['isspeech'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'] == $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['id'] && $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['isdecode'] == 1 && $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['isencode'] == 0) {?>
					
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Unavailable']);?>

					
					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['isspeech'] == 0 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'] == $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['id'] && $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['isdecode'] == 1 && $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['isencode'] == 1) {?>
					
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Closed']);?>

					
					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['isspeech'] == 0 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'] == $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['id'] && $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['isdecode'] == 0 && $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['isencode'] == 1) {?>
					
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Closed']);?>

					
					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['isspeech'] == 0 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'] == $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['id'] && $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['isdecode'] == 0 && $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['isencode'] == 1) {?>
					
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Closed']);?>

					
					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'] == $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['id'] && $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['isdecode'] == 0 && $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['isencode'] == 0) {?>
					
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Unavailable']);?>

					<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'] == $_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['id']) {?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Unavailable']);?>
	
						
					 <?php }?>
				 
				 <?php
}
}
if ($__section_type_3_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_type'] = $__section_type_3_saved;
}
?>
				 
				<?php } else { ?>
				
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Unknown']);?>

					
				<?php }?>
			 </td>
			  
			 <td nowrap="nowrap">
		<?php echo '<script'; ?>
 language="javascript">
		
			var a=0;
	
			<?php echo '</script'; ?>
>	 
	<?php
$__section_get_ter_4_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_get_ter']) ? $_smarty_tpl->tpl_vars['__smarty_section_get_ter'] : false;
$__section_get_ter_4_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['get_terminal']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_get_ter_4_total = $__section_get_ter_4_loop;
$_smarty_tpl->tpl_vars['__smarty_section_get_ter'] = new Smarty_Variable(array());
if ($__section_get_ter_4_total != 0) {
for ($__section_get_ter_4_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_get_ter']->value['index'] = 0; $__section_get_ter_4_iteration <= $__section_get_ter_4_total; $__section_get_ter_4_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_get_ter']->value['index']++){
?>
			<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['id'] == $_smarty_tpl->tpl_vars['get_terminal']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_get_ter']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_get_ter']->value['index'] : null)]['terminalid'] && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'] != 33) {?>
				
			<?php echo '<script'; ?>
 language="javascript">
		
			a=1;
			
			<?php echo '</script'; ?>
>
			<?php } else { ?>		
      
			<?php }?>
					
			<?php
}
}
if ($__section_get_ter_4_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_get_ter'] = $__section_get_ter_4_saved;
}
?>
		<?php echo '<script'; ?>
 language="javascript">
		
		if(a==1)
		{
	
		
		document.write('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Termianl_manager_shortcuthave']->value);?>
');
			
			
		}
		else
		{
		document.write('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Termianl_manager_shortcutno']->value);?>
');
		}
	
			<?php echo '</script'; ?>
>
			</td>
			 
		    <td nowrap="nowrap">
			
			<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['instancy'] != 0) {?>			
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Termianl_manager_SInstancy']->value);?>
		
					<?php } else { ?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Termianl_manager_NInstancy']->value);?>

					<?php }?>
			
			
			</td>
		<td nowrap="nowrap">
			
			<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['isrecord'] != 0) {?>			
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Termianl_manager_SRecord']->value);?>
		
					<?php } else { ?>
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Termianl_manager_TRecord']->value);?>

					<?php }?>
			
			
			</td>	
			<!--	 <td nowrap="nowrap">	
				  <?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['isselectcall'] != 0) {?>
				 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Get_Talkback']);?>

				 <?php } else { ?>
				  <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Closed']);?>

				 <?php }?>
				</td>  -->
				 <td nowrap="nowrap">	
				  <?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['issponsor'] != 0) {?>
				 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Enabled']);?>

				 <?php } else { ?>
				  <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Disabled']);?>

				 <?php }?>
				</td>
			<!--	<td nowrap="nowrap">	
				 <?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['shortcircuit'] != 0) {?>
				 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['shortcircuit']);?>

				 <?php } else { ?>
				  <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['noshortcircuit']);?>

				 <?php }?>
				</td> -->
				<td nowrap="nowrap">	
				 <?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['shortcircuit'] != 0) {?>
				  <span style="color:#ff0000;">
				 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['shortcircuit']);?>
</span>
				 <?php } else { ?>
					 <?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['lopencircuit'] != 0) {?>
					  <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['noshortcircuit']);?>

					 <?php } else { ?>
					  <span style="color:#ff0000;">
					   <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['opencircuit']);?>

					   </span>
					 <?php }?>
				 <?php }?>
				</td>
				<td nowrap="nowrap">
				<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['shortcircuit'] != 0) {?>
				 <span style="color:#ff0000;">
				 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['shortcircuit']);?>
</span>
				 <?php } else { ?>
					 <?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['ropencircuit'] != 0) {?>
					 <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['noshortcircuit']);?>

					 <?php } else { ?>
					  <span style="color:#ff0000;">
					   <?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['opencircuit']);?>
</span>
					 <?php }?>	
				 <?php }?>	
				</td>
				<td nowrap="nowrap">	
					<?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['temperature'];?>

				</td>
				<td nowrap="nowrap">	
					<?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['humidity'];?>

				</td>
			 <td nowrap="nowrap">
			  <?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'] != 0) {?>
				 <?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1) {?>
					 <a  name="link_view" id="link_view" href="http://<?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['ip'];?>
" target="_blank"  >
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Browse']);?>

					 </a>
				 <?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 0) {?>
					 <a  name="link_view" id="link_view" href="#" onclick="alert('<?php echo $_smarty_tpl->tpl_vars['Revise']->value['Disconnected'];?>
');return void(0);">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Browse']);?>

					 </a> 
				 <?php }?>
			<else>
			<?php }?>
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
		<tr class="tablestyle"  onmouseover="this.style.backgroundColor = '#EEEEFF'" onmouseout="this.style.backgroundColor = '#FFFFFF'">
		<td colspan="10" style="text-align:center"><strong><?php echo $_smarty_tpl->tpl_vars['Revise']->value['No_data'];?>
</strong></td>
		</tr>
		<?php }?>
		 
		</table>
</div>
</tbody> 
		<table cellpadding="0" cellspacing="0" width="98%">
		<tr style="background-color: #FFFFFF;">
			<td height="28" colspan="9">		
				<tr>
					<td>			  
						<table width="100%" border="0" >
						<tr>
							<?php echo '<script'; ?>
 src="smarty/templates/UserAccessControl/CheckUserRights.js" type="text/javascript" language="javascript">
							<?php echo '</script'; ?>
>
							<td width="20%"  align="left" nowrap="nowrap">
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_allterminal']);?>
" href="javascript:selAll(0)" name="select_all_yes" id="select_all_yes">
									<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Select_All']);?>

								</a>&nbsp;

								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['noselect_allterminal']);?>
" href="javascript:noSelAll(0)" name="select_all_no" id="select_all_no">
									<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Cancel']);?>

								</a>&nbsp;
							
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['start_terminaldo']);?>
" href="javascript:startTerminal()" >
									<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Start_Terminal']);?>

								</a>&nbsp;
							
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['start_terminalnodo']);?>
" href="javascript:stopTerminal()">
									<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Disable_Terminal']);?>

								</a>&nbsp;
								
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['setStart_Talkback']);?>
"  id="start_speech" href="javascript:startspeech()" >
									<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Start_Talkback']);?>

									&nbsp;
								</a>
								
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['nosetStart_Talkback']);?>
"  id="stop_speech" href="javascript:closespeech()">
									<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Disable_Talkback']);?>

									&nbsp;
								</a>
								
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Re_registrationterminal']);?>
" href="javascript:delterminal(0)" >
									<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Re_registration']);?>

								</a>&nbsp;

								<a  title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['set_selectkey']);?>
" href="javascript:view_shotcut(0)" >
									<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['View_Shortcut_Key']);?>

								</a>&nbsp;

								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['noset_selectkey']);?>
" href="javascript:cancel_shotcut()">
									<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Delete_Shortcut_Key']);?>

								</a>&nbsp;
								
								<a  title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['set_quickplay']);?>
" href="javascript:view_shotcut(1)" >
									<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['quickplay']);?>

								</a>&nbsp;
								
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Settings_terminal_password']);?>
" href="javascript:showDiv()" >
									<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['Set_terminal_password'];?>

								</a>&nbsp;
								
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['setInstancy_Add_Termial']);?>
" id="add_instancy"  href="javascript:set_terminal_Sinstancy()" >
									<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['Instancy_Add_Termial'];?>

									&nbsp;
								</a>
								
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['nosetInstancy_Add_Termial']);?>
" id="stop_instancy" href="javascript:set_terminal_Dinstancy()">
									<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['Instancy_Del_Termial'];?>

									&nbsp;
								</a>
								
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['setInstancy_Add_Record']);?>
" id="add_record" href="javascript:set_terminal_record()" >
									<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['Instancy_Add_Record'];?>

									&nbsp;
								</a>
								
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['nosetInstancy_Stop_Record']);?>
" id="stop_record" href="javascript:set_terminal_stoprecord()">
									<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['Instancy_Stop_Record'];?>

									&nbsp;
								</a>
							
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['setvolume']);?>
" href="javascript:void(0)" id="volume_handle" onclick="mouse_click_position(event)">
									<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['Adjust_Volume'];?>

								</a>
							
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['setselect_Talkback']);?>
"  href="javascript:set_terminal_backcall()">
									<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['select_Talkback'];?>

								</a>
								
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['setterminal_task']);?>
" href="javascript:void(0)"  id="setcopytask" onclick="set_terminal(event)">
		<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['setterminal'];?>

								</a>
							<br><br>
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['synch_terminaltask']);?>
" id="add_synchtask" href="javascript:set_synchtask()" >
		<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['synch_task'];?>

								</a>
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['doposoner']);?>
" id="add_synchtask" href="javascript:startsponsor()" >
		<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['doposoner'];?>

								</a>
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['stopposoner']);?>
" id="add_sponsor" href="javascript:closesponsor()" >
		<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['stopposoner'];?>

								</a>
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['checkstate']);?>
" id="add_checkstate" href="javascript:check_state()" >
		<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['checkstate'];?>

								</a>
							<a  title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['yingji']);?>
" href="javascript:view_shotcut(2)" >
									<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['yingji']);?>

							</a>
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['ledsousuo']);?>
" id="led_sousuo" href="javascript:ledsousuo(1)" >
		<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['ledsousuo'];?>

								</a>
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['ledterminaldisplay']);?>
" id="led_sousuo" href="javascript:ledsousuo(2)" >
									<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['ledterminaldisplay'];?>

								</a>
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['sync_time']);?>
" id="led_sousuo" href="javascript:sync_time()" >
									<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['sync_time'];?>

								</a>
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['setselect_Talkback']);?>
"  href="javascript:set_terminal_backcall_dir()">
									<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['select_Talkback_dir'];?>

								</a>
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['deltaskterminal']);?>
" id="del_taskterminal" href="javascript:deltaskterminal()" >
									<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['deltaskterminal'];?>

								</a>
							<!--	
								<a  title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['yingji']);?>
" href="javascript:view_shotcut(3)" >
									<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['shengji']);?>

							</a>
							&nbsp;
								<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['tv_terminal']);?>
" href="javascript:tv_map()" >
		<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['tv_map'];?>

							</a>
							
								<a href="javascript:stop_terminal_backcall()">
									<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['stop_select_Talkback'];?>

								</a>
							-->
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
					</table>	
				</td>
			</tr>
	
		
	</td>
  </tr>
<?php echo '<script'; ?>
 language="javascript">
var obj =document.getElementById( "divTest").offsetHeight; 
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
  <tr>
    <td align="center">
		<table align="center"><tr><td><div class="link_style" align="center"><?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['sum'];
echo $_smarty_tpl->tpl_vars['pagestr']->value;
echo $_smarty_tpl->tpl_vars['terminal_manager']->value['device'];?>
</div></td></tr></table>
	</td>
  </tr>
<!--  搜索表单  -->

<input type='hidden' name='dopost' value='' />
<input type="hidden"  id="get_initid" name="get_initid" value=""/>
<table width='98%'  border='0' cellpadding='1' cellspacing='1' class="middle" align="center" style="margin-top:8px">
  <tr align="center">
    <td background='skin/images/wbg.gif' align='center'>
		<table border='0' cellpadding='0' cellspacing='0'>
			<tr>
				<td width='90' align='center'><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Search_conditions'];?>
</td>
				<td width='160'>
					<select name='searchkey' id="searchkey" style='width:150px'>
						<option value=""><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Select_type'];?>
</option>

						<option value="terminalname"><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Terminal_name'];?>
</option>
						
						<option value="ip"><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['IP_Address'];?>
</option>
						
					</select>
				</td>
				<td width='70'>
				<?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Keyword'];?>

				</td>
				<td width='160'>
					<input type='text' name='searchvalue' id="searchvalue" value=''/>        
				</td>
				<td width='110'>
					<select name='searchsequence' id="searchsequence" style='width:80px'>
					  <option value=""><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Sort'];?>
</option>
					  
					  <option value="id"><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Published'];?>
</option>
					</select>        
				</td>
				<td>
					<input name="imageField" type="image" src="<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['search_image'];?>
" onclick="actionform()" width="45px" height="20px" border="0px"/>        
				</td>       
			</tr>
		</table>
    </td>
  </tr>
</table>

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
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Sumbit']);?>

				</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="javascript:void(0)" onclick="disappear_volume_div()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Cancel']);?>

				</a>
			</td>
		</tr>
		
	</table>
</div>
</div>
<?php if ($_smarty_tpl->tpl_vars['is_right']->value == 1) {?>
<!--什么也不做-->
<?php } else {
echo '<script'; ?>
>
	
	var input_objs = document.getElementsByTagName("a");
	for(var i=0; i< input_objs.length; i++)
	{
			input_objs[i].href = "javascript:void(0);";
			input_objs[i].onclick = null;
			input_objs[i].style.color="#787878";
	}
	
	for(var i=0; i< input_objs.length; i++)
	{
		<?php
$__section_ter_id_5_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_ter_id']) ? $_smarty_tpl->tpl_vars['__smarty_section_ter_id'] : false;
$__section_ter_id_5_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['user_terminal']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_ter_id_5_total = $__section_ter_id_5_loop;
$_smarty_tpl->tpl_vars['__smarty_section_ter_id'] = new Smarty_Variable(array());
if ($__section_ter_id_5_total != 0) {
for ($__section_ter_id_5_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_ter_id']->value['index'] = 0; $__section_ter_id_5_iteration <= $__section_ter_id_5_total; $__section_ter_id_5_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_ter_id']->value['index']++){
?>
		if(input_objs[i].value == "<?php echo $_smarty_tpl->tpl_vars['user_terminal']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_ter_id']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_ter_id']->value['index'] : null)];?>
")
		{
			input_objs[i].disabled = false;
		}
		<?php
}
}
if ($__section_ter_id_5_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_ter_id'] = $__section_ter_id_5_saved;
}
?>
	}
	
	document.getElementById('select_all_yes').style.display = 'none';
	document.getElementById('select_all_no').style.display = 'none';
<?php echo '</script'; ?>
>

<?php }
echo '<script'; ?>
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
		alert('Not Support AJAX');
	}
} 
function ajax_set_task_volume(set_volume_server,volume_value,task_id)
{
   createXMLHttpRequest();
   
   if(task_id == "")
   {
		alert('Please select options'); 
	
		return void(0);
   }

   xmlhttp.open( "get","getterminalvolume.php?task_id="+task_id+"&volume="+volume_value+"",true );
   
   
   xmlhttp.onreadystatechange = function()
   {
      if( xmlhttp.readyState == 4 )
      {
         if( xmlhttp.status == 200 )
         {
		 
            if( xmlhttp.responseText == 0)
            {
				var info=trim("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['failure']);?>
");
		
            }
			else if(xmlhttp.responseText == 1)
			{
				var info=trim("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['success']);?>
");

			
				
				get_div_obj('change_volume').style.display = "none";
				
				self.location.reload();
			}
         }
		 else
		 {
			alert('Access Failure');	 
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
function ajax_set_task_copy(getinitid,getterminalid)
{
   createXMLHttpRequest();
   xmlhttp.open( "get","getterminalid.php?getterminalid="+getterminalid+"&getinitid="+getinitid+"",true );
   xmlhttp.onreadystatechange = function()
   {
      if( xmlhttp.readyState == 4 )
      {
         if( xmlhttp.status == 200 )
         {
				if(xmlhttp.responseText==1)
				{
					alert("<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['terminalone'];?>
");
					self.location.reload();
				}
				else if(xmlhttp.responseText==2)
					alert("<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['terminaltwo'];?>
");
				else if(xmlhttp.responseText==3)
					alert("<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['terminalthree'];?>
");
				else if(xmlhttp.responseText==4)
				{
					alert("<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['terminalfour'];?>
");
					self.location.reload();
				}	
				get_div_obj('copytask').style.display = "none";
		}
        
      }
   }
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	xmlhttp.send(null);
}
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

function set_task_copy()
{
	var getinitid=document.getElementById('get_initid').value;
	var getterminalid=document.getElementById('terminalid').value;
	if(isNull(getterminalid))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['terminaliderror']);?>
");

		return false;
	}
	if(isNumber(getterminalid))
	{
	ajax_set_task_copy(getinitid,getterminalid);
	return true;
	}
else
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['terminaliderror']);?>
");

		return false;
	}
}
<?php echo '</script'; ?>
>
 <div id="copytask" class="r-displayVolume">
 <iframe style="position:absolute; width:150;height:110px;left:0px; top:0px;filter:alpha(opacity=0);-moz-opacity:0;border:0;z-index:-1"></iframe>
<div style="position:absolute;border:0;width:150; left:0px; top:0px; height:110px;z-index:100">
	<table border="0" cellpadding="10" cellspacing="0" width="150" style="background-color:#EEFFEE">
		
		<tr>
			<td nowrap="nowrap" align="right">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['terminalid']);?>

			<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['terminal_id']);?>
" class="terminal_input_font" name="terminalid" type="text" id="terminalid"/>
			</td>
			</tr>
		<tr>
			<td nowrap="nowrap" align="center">
				<a href="javascript:void(0)" onclick="set_task_copy()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Sumbit']);?>

				</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="javascript:void(0)" onclick="disappear_task_div()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Cancel']);?>

				</a>
			</td>
		</tr>
		
	</table>
</div>
</div>
<!-----------------------添加隐藏层--------------------------------------------->
<div id="popDiv" class="mydiv" style="display:none;">
<iframe style="position:absolute; width:300;height:150px;left:0px; top:0px;filter:alpha(opacity=0);-moz-opacity:0;border:0;z-index:-1"></iframe>
<div style="position:absolute;border:0;width:300; left:0px; top:0px; height:150px;z-index:100">
<table border="0" width="300" height="150" align="center" cellpadding="0" cellspacing="0" >
    <tr height="20%">
        <td colspan="2" align="left" valign="middle" style="background-color:#6699DD;color:#FFFFFF">
            <?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['Batch_Settings_password'];?>

        </td>
    </tr>
    
    <tr height="20%">
        <td colspan="2" align="left" valign="middle">
           <?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['Note_password_only_number'];?>

        </td>
    </tr>
    
    <tr height="30%">
        <td align="right" width="30%">
            <?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['password'];?>

        </td>
        
        <td align="left" width="70%">
        
            <input type="password" value="" id="terminal_password" name="terminal_password" maxlength="6" size="20"/>
            
            <span id="terminal_password_msg" style="color:#FF0000;vertical-align:text-top;"></span>
        </td>
    </tr>
    
    <tr height="20%">
		<td></td>
        <td valign="middle" align="left">
            <input type="button" onclick="setupDiv()" style="border:1px solid #aaaaaa;" value="<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['setup'];?>
">
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="button" onclick="closeDiv()" style="border:1px solid #aaaaaa;" value="<?php echo $_smarty_tpl->tpl_vars['terminal_manager']->value['close'];?>
">
        </td>
    </tr>
</table>
</div>
</div>
<!----------------------添加隐藏背景层---------------------------------------------->
<div id="bg" class="bg" style="display:none;"></div>
<!----------------------防止层的漏出------------------------------------------------>
<iframe id='popIframe' class='popIframe' frameborder='0' ></iframe>

<?php echo '<script'; ?>
 language="javascript">
var registerflag="<?php echo $_smarty_tpl->tpl_vars['registerflag']->value;?>
";
if(registerflag==1||registerflag==2)
{
	
}
else
{
	document.getElementById("start_speech").style.display="none";	
	document.getElementById("stop_speech").style.display="none";	
	document.getElementById("add_record").style.display="none";
	document.getElementById("stop_record").style.display="none";
	document.getElementById("stop_instancy").style.display="none";
	document.getElementById("add_instancy").style.display="none";
	document.getElementById("setcopytask").style.display="none";
	document.getElementById("add_synchtask").style.display="none";
}
<?php echo '</script'; ?>
>
<?php }
}
