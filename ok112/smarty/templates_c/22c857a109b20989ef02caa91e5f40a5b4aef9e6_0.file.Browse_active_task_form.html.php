<?php
/* Smarty version 3.1.30, created on 2026-07-06 15:51:21
  from "/var/www/html/ok112/smarty/templates/Browse_active_task/Browse_active_task_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a4b5e798cc844_23027550',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '22c857a109b20989ef02caa91e5f40a5b4aef9e6' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/Browse_active_task/Browse_active_task_form.html',
      1 => 1778116057,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a4b5e798cc844_23027550 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>

<form name="terminalfunctionplayform" class="terminal_form_to_body">
<tbody>
<div id="divTest" style="width:100%;overflow-x:hidden;overflow-y:scroll">
<table width="98%" border="0" cellpadding="2" cellspacing="1" align="center" id="tabletask">
<thead>
<tr align='center' class="terminal_table_row_bg">

  <th width="5%"  nowrap="nowrap" class="change_thead_style">
  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Select']);?>

  </th>
  
  <th width="10%" nowrap="nowrap"  onclick="sortTable('tabletask', 1,'1')" class="sort_data_table_sequence">
  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['task_name']);?>

  </th>
    <th width="10%" nowrap="nowrap"  onclick="sortTable('tabletask', 2,'1')" class="sort_data_table_sequence">
  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['thecagegary']);?>

  </th>
  <th width="15%" nowrap="nowrap" onclick="sortTable('tabletask', 3,'1')" class="sort_data_table_sequence">
  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Play_Cycle']);?>

  </th>

  <th width="8%" nowrap="nowrap" onclick="sortTable('tabletask', 4,'1')" class="sort_data_table_sequence">
  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Run_Time']);?>

  </th>
  
  <th width="8%"  nowrap="nowrap" onclick="sortTable('tabletask', 5,'1')" class="sort_data_table_sequence">
  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Status']);?>

  </th>
  
  <th width="8%" nowrap="nowrap" onclick="sortTable('tabletask', 6,'1')" class="sort_data_table_sequence">
  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Start_Date']);?>

  </th>
  
  <th width="8%" nowrap="nowrap" onclick="sortTable('tabletask', 7,'1')" class="sort_data_table_sequence">
  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['End_Date']);?>

  </th>
  
    <th width="8%" nowrap="nowrap" onclick="sortTable('tabletask', 8,'1')" class="sort_data_table_sequence">
  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['enableday']);?>

  </th>
  
  <th width="8%" nowrap="nowrap" class="change_thead_style">
  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Terminal_Attribute']);?>

  </th>
  
  <th width="8%" nowrap="nowrap">
  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Operating_tasks']);?>

  </th>
  
</tr>
	</thead>
<tbody>

<?php if ($_smarty_tpl->tpl_vars['is_right']->value == 1 || $_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>
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
			<input name="id" type="checkbox" id="id" value="<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
">
				<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>

			</td>
		
			<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskname'];?>
</td> 
			<td nowrap="nowrap">  
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 1) {?>
				 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['oneache']);?>
(<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['info'];?>
)
			
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 2) {?>		   	
			
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['twofile']);?>

			
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 3) {?>	
			
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['threefile']);?>
	

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 10) {?>

					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['four']);?>
	
					
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 5) {?>	
			
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['five']);?>
	
					
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 4) {?>

					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['telphone']);?>
	
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 17) {?>

					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['six']);?>
	
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 19) {?>
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['six']);?>
	
					<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 15) {?>
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['six']);?>
	
				<?php }?>
			</td>
			<td>
			<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'] == "0000000") {?>
				<?php echo $_smarty_tpl->tpl_vars['terminal_function']->value['Manual'];?>

			<?php } else { ?>
				<?php echo '<script'; ?>
 language="javascript">document.write(getdayofweek("<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'];?>
"))<?php echo '</script'; ?>
>
			<?php }?>
		</td>

		 	<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['playtime'];?>
</td>
		 
		 	<td  id="dostate"  nowrap="nowrap">  
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 3) {?>
				<span style="color:#0f6b24;">  
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Run']);?>

				</span> 
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 0) {?>		   	
				<?php echo '<script'; ?>
 language="javascript">
				var getstr = "<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['playtime'];?>
";
			
				var gettime = getstr.split(":");
			 	var getcurtime=Number(gettime[0])*3600+Number(gettime[1])*60+Number(gettime[2]);
				
				var sec=<?php echo $_smarty_tpl->tpl_vars['sec']->value;?>
+<?php echo $_smarty_tpl->tpl_vars['hours']->value;?>
*3600+<?php echo $_smarty_tpl->tpl_vars['minites']->value;?>
*60;

			
				if(getcurtime>sec)
				{
					document.write("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Ready']);?>
●");
					document.getElementById('dostate').style.color = "#ff0000";
				//	document.getElementById('dostate').innerHTML="<font class='terminal_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Ready']);?>
</font>";
				}
				else
				{
					document.write("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['doed']);?>
");	
				//	document.getElementById('dostate').style.color = "#FE0505";
					
				}
				
				<?php echo '</script'; ?>
>	
			
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 2) {?>	
				<span style="color:#0f6b24;">  
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Pause']);?>
	
				</span> 
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 1) {?>
				<span style="color:#0f6b24;">   
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Ready_play']);?>
●</span>	
 			   <?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 5) {?>
				<span style="color:#0f6b24;">    
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['doed']);?>
	 </span> 
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 6) {?>
				<span style="color:#ff0000;"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Ready']);?>
●	
				</span> 
				<?php }?>
			</td>

		 	<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['startdate'];?>
</td>

		 	<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['enddate'];?>
</td>
			<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['disableday'];?>
</td>
			<td nowrap="nowrap">
		
				<a name="link_view" id="link_view" href="displayterminal.php?term_id=<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
">
			
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Browse_Terminal']);?>

				
				</a>
			</td> 
		
			<td nowrap="nowrap">
			<?php if ($_smarty_tpl->tpl_vars['username']->value == "admin") {?>
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 1) {?>
					<a href="javascript:pause_curr_active_task(<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
)">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Pause']);?>
	
					</a>
				
					<a href="javascript:void(0)" style="color:#666666" onclick="null">
						
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Run']);?>

					</a>
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 0) {?>
					<a href="javascript:void(0)" style="color:#666666" onclick="null">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Pause']);?>
	
					</a>
						<a href="javascript:start_curr_active_task(<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
)">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Run']);?>

						</a>				
				<?php } else { ?>
					<a href="javascript:pause_curr_active_task(<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
)">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Pause']);?>
	
					</a>
				
					<a href="javascript:void(0)" style="color:#666666" onclick="null">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Run']);?>

					</a>
				<?php }?> 
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
	<tr align="center">
		<td colspan="8">
			<strong><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['No_Record']);?>
</strong>
		</td>
	</tr>
	<?php }
} else { ?>

	<?php if (count($_smarty_tpl->tpl_vars['info']->value) != 0) {?>

 	<?php
$__section_loop_1_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_loop']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop'] : false;
$__section_loop_1_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_loop_1_total = $__section_loop_1_loop;
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = new Smarty_Variable(array());
if ($__section_loop_1_total != 0) {
for ($__section_loop_1_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] = 0; $__section_loop_1_iteration <= $__section_loop_1_total; $__section_loop_1_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']++){
?>
      
      	<tr align="center"  style= "color:   #8E8E8E " height="30" >
	  
			<td nowrap="nowrap">
		
				<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>

			
			</td>
		
			<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskname'];?>
</td> 

				<td>
			<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'] == "0000000") {?>
				<?php echo $_smarty_tpl->tpl_vars['terminal_function']->value['Manual'];?>

			<?php } else { ?>
				<?php echo '<script'; ?>
 language="javascript">document.write(getdayofweek("<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'];?>
"))<?php echo '</script'; ?>
>
			<?php }?>
		</td>

		 	<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['playtime'];?>
</td>
		 
		 	<td nowrap="nowrap">  
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 3) {?>
				<span style="color:#0f6b24;">  
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Run']);?>

				</span> 
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 0) {?>	
					<?php echo '<script'; ?>
 language="javascript">
				
					var getstr = "<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['playtime'];?>
";
					var gettime = getstr.split(":");
					var getcurtime=gettime[0]*3600+gettime[1]*60+gettime[2];
					var sec=<?php echo $_smarty_tpl->tpl_vars['sec']->value;?>
+<?php echo $_smarty_tpl->tpl_vars['hours']->value;?>
*3600+<?php echo $_smarty_tpl->tpl_vars['minites']->value;?>
*60;
				
					if(getcurtime>sec)
					{
						document.write("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Ready']);?>
");
					}
					else
					{
						document.write("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['doed']);?>
");	
					}
					
					<?php echo '</script'; ?>
>	   	
			
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 2) {?>	
				<span style="color:#0f6b24;">  
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Pause']);?>
	
				</span>
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 1) {?>
				<span style="color:#0f6b24;">  
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Ready_play']);?>
●</span>	
			    <?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 5) {?>
				<span style="color:#0f6b24;">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['doed']);?>
	 </span> 
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 6) {?>
				<span style="color:#ff0000;"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Ready']);?>
		
					●	
				</span> 
				<?php }?>
			</td>

		 	<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['startdate'];?>
</td>

		 	<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['enddate'];?>
</td>

			<td nowrap="nowrap">
		
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Browse_Terminal']);?>

				
			</td> 
		
			<td nowrap="nowrap">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Pause']);?>
	
			
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Run']);?>

			</td>
		       
		</tr>         
    
	<?php
}
}
if ($__section_loop_1_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = $__section_loop_1_saved;
}
?>
	
	<?php } else { ?>
	
	<tr align="center" >
		
		<td colspan="8">
			
			<strong><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['No_Record']);?>
</strong>
		
		</td>
	
	</tr>
	
	<?php }?>

<?php }?>

</table>
</div>
</tbody>
<table cellpadding="0" cellspacing="0">	 

<tr align="right" >

	<?php if ($_smarty_tpl->tpl_vars['is_right']->value == 1 || $_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>
	
		<td  colspan="3" align="left" nowrap="nowrap" valign="bottom" width="50%">

			<a href="javascript:get_week_date_task('1');" id="1">
			<!--
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Select_All']);?>

			-->
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Monday']);?>

			</a> &nbsp;
			
			<a href="javascript:get_week_date_task('2');" id="2">
			<!--
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Cancel']);?>

			-->
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Tuesday']);?>

			</a>&nbsp;
			
			<a href="javascript:get_week_date_task('3');" id="3">
			<!--	
				<?php echo $_smarty_tpl->tpl_vars['terminal_function']->value['Run'];?>

			-->
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Wednesday']);?>

			</a>&nbsp;
				
			<a href="javascript:get_week_date_task('4');" id="4">
			<!--
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Pause']);?>

			-->
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Thursday']);?>

			</a>&nbsp;
			
			<a  href="javascript:get_week_date_task('5');" id="5">
			<!--
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Add']);?>

			-->
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Friday']);?>

			</a>&nbsp;
			
			<a href="javascript:get_week_date_task('6');" id="6">
			<!--
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Update']);?>

			-->
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Saturday']);?>

			</a>&nbsp;
			
			<a href="javascript:get_week_date_task('0');" id="0">
			<!--
				<?php echo $_smarty_tpl->tpl_vars['terminal_function']->value['Delete'];?>

			-->
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Sunday']);?>

			</a>&nbsp;
			
			
			<!--
			<a href="javascript:get_week_date_task('8');" id="8">
		
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextMonday']);?>

			</a>&nbsp;
			<a href="javascript:get_week_date_task('9');" id="9">
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextTuesday']);?>

			</a>&nbsp;
			<a href="javascript:get_week_date_task('10');" id="10">
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextWednesday']);?>

			</a>&nbsp;
			<a href="javascript:get_week_date_task('11');" id="11">
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextThursday']);?>

			</a>&nbsp;
			<a href="javascript:get_week_date_task('12');" id="12">
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextFriday']);?>

			</a>&nbsp;
		   <a href="javascript:get_week_date_task('13');" id="13">
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextSaturday']);?>

			</a>&nbsp;
			<a href="javascript:get_week_date_task('14');" id="14">
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextSunday']);?>

			</a>&nbsp;
			-->
		</td>
			
		<td colspan="3" align="right" nowrap="nowrap" valign="bottom" width="30%">
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['No']);?>
&nbsp;
				
				<input type="text" value="0" name="get_int_date" id="get_int_date" style="border:#CCCCCC solid 1px; width:40px; height:15; text-align:center" title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Negative_Positive_day']);?>
"/>
			
				&nbsp;<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Day']);?>
&nbsp;
				<img src="skin/images/frame/search_task.gif" name="search_task_image" id="search_task_image" align="bottom" onclick="get_interval_day();" onmouseover="change_image()" onmouseout="reset_image()"/>
 		
		</td>
		<td colspan="2" align="right" nowrap="nowrap" valign="bottom" width="20%">
	<!--	<div id="divenable" style="width:100%"> -->
			<a href="javascript:enordis_week_date_task('0');" id="enableday">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['enableday']);?>

			</a>&nbsp;
					
			<a href="javascript:enordis_week_date_task('1');" id="disableday">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['disableday']);?>

			</a>&nbsp;
		<!--	</div> -->
		</td>
	
	<?php } else { ?>
	
	<tr align="right" style= "color:   #8E8E8E "> 
	
		<td  colspan="4" align="left" nowrap="nowrap" valign="bottom" width="50%">

		
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Monday']);?>

			&nbsp;
	
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Tuesday']);?>

			&nbsp;
			
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Wednesday']);?>

			&nbsp;
				
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Thursday']);?>

			&nbsp;
			
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Friday']);?>

			&nbsp;
			
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Saturday']);?>

			&nbsp;
			
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['Sunday']);?>

			&nbsp;
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextMonday']);?>

			&nbsp;
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextTuesday']);?>

			&nbsp;
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextWednesday']);?>

			&nbsp;
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextThursday']);?>

			&nbsp;
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextFriday']);?>

			&nbsp;
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextSaturday']);?>

			&nbsp;
			
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['NextSunday']);?>

			&nbsp;
			
		</td>
 </tr>	
		
<?php }?>	
		
		
	</tr>
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
 



 /*
 
 var getweekday=<?php echo $_smarty_tpl->tpl_vars['current_week_day']->value;?>
;
 var get_str_date=<?php echo $_smarty_tpl->tpl_vars['get_str_date']->value;?>
;

 if((getweekday==get_str_date)||(get_str_date==0))
 {
 document.getElementById("divenable").style.display= "block";
 }
 else
{
document.getElementById("divenable").style.display= "none";
}
 
 */
 
 window.onload = function ()
{
	var a_array_objs = document.all.tags('a');
	
	for(var i=0; i<a_array_objs.length; i++)
	{
		if( a_array_objs[i].id == '<?php echo $_smarty_tpl->tpl_vars['current_week_day']->value;?>
' )
		{
			a_array_objs[i].innerHTML = "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['today']);?>
";
			
			break;
		}
	}

}

 
<?php echo '</script'; ?>
>
</form>

<table align="center"><tr><td><div class="link_style" align="center"><?php echo $_smarty_tpl->tpl_vars['pagestr']->value;?>
</div></td></tr></table>

</body>

<?php echo '<script'; ?>
>

function get_week_date_task(int_date)
{
	switch(int_date)
	{
		case "0":
		
		window.location.href = "./Browse_active_task.php?get_str_date=7&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;	
		
		case "1":
		
		window.location.href = "./Browse_active_task.php?get_str_date=1&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;
		
		case "2":
		
		window.location.href = "./Browse_active_task.php?get_str_date=2&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;
		
		case "3":
		
		window.location.href = "./Browse_active_task.php?get_str_date=3&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;
		
		case "4":
		
		window.location.href = "./Browse_active_task.php?get_str_date=4&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;
		
		case "5":
		
		window.location.href = "./Browse_active_task.php?get_str_date=5&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;
		
		case "6":
		
		window.location.href = "./Browse_active_task.php?get_str_date=6&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;
		
		case "8":
		
		window.location.href = "./Browse_active_task.php?get_str_date=8&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;
		
		case "9":
		
		window.location.href = "./Browse_active_task.php?get_str_date=9&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;
		
		case "10":
		
		window.location.href = "./Browse_active_task.php?get_str_date=10&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;
		
		case "11":
		
		window.location.href = "./Browse_active_task.php?get_str_date=11&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;
		
		case "12":
		
		window.location.href = "./Browse_active_task.php?get_str_date=12&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;
		
		case "13":
		
		window.location.href = "./Browse_active_task.php?get_str_date=13&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;
		
		case "14":
		
		window.location.href = "./Browse_active_task.php?get_str_date=14&get_int_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		
		break;
		

	}
}




function get_interval_day()
{
	var get_day_num = document.getElementById('get_int_date').value;
	
	get_day_num = get_day_num.replace(/(^\s*)|(\s*$)/g,""); 
	
	var regu = "^[-]{0,1}[0-9]+$"; 
	
	var re = new RegExp(regu);
	
	if (get_day_num.search(re) != -1) 
	{
		if( get_day_num > 30 || get_day_num < -30 )
		{
			alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['enter_number_between']);?>
');
		}
		else
		{
			window.location.href = "./Browse_active_task.php?get_int_date="+get_day_num+"&get_str_date=0&id="+<?php echo $_smarty_tpl->tpl_vars['getid']->value;?>
;
		}
	} 
	else 
	{ 
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_function']->value['enter_number']);?>
');
	}
}

function change_image()
{
	document.getElementById('search_task_image').src = "skin/images/frame/search_task1.gif";
	
	document.getElementById('search_task_image').style.cursor = 'hand';
}

function reset_image()
{
	document.getElementById('search_task_image').src = "skin/images/frame/search_task.gif";
}




<?php echo '</script'; ?>
>
</html>
<?php echo '<script'; ?>
 language="javascript">
	var get_user_right = "<?php echo $_smarty_tpl->tpl_vars['is_right']->value;?>
";

	if(get_user_right == 1)
	{
		//ʲôҲ����
	}
	else
	{
		var get_a_objects = document.getElementsByTagName("a");
		for(var i=0; i<get_a_objects.length; i++)
		{
			get_a_objects[i].href = "javascript:void(0);";
			get_a_objects[i].onclick = null;
			get_a_objects[i].style.color="#787878";
		//	get_a_objects[i].disabled = true;
		}
	}
<?php echo '</script'; ?>
><?php }
}
