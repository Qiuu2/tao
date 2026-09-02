<?php
/* Smarty version 3.1.30, created on 2026-05-25 14:53:45
  from "/var/www/html/ok112/smarty/templates/BellManager/displayplantask_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a13f1f9ef83b0_51163971',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'bac4ca69780269bd43b0268378b02bee06668faf' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/BellManager/displayplantask_form.html',
      1 => 1778116057,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a13f1f9ef83b0_51163971 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<form name="bellForm" class="terminal_form_to_body">
<tbody>
<div id="divTest" style="width:100%;overflow-x:hidden;overflow-y:scroll">
<table width="98%" border="0" cellpadding="2" cellspacing="1"  align="center"  id="tableplan" class="terminal_form_to_body">
	<thead> 
		<tr align='center' class="terminal_table_row_bg">
		  <th width="8%" nowrap="nowrap">
		  	<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['No_'];?>

		  </th >
		
		  <th  width="10%" nowrap="nowrap" onclick="sortTable('tableplan', 1,'1')" class="sort_data_table_sequence">
		  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Bellmanager']->value['Task_name']);?>

			</th>  
		  
		  <th  width="15%" nowrap="nowrap" onclick="sortTable('tableplan', 2,'1')" class="sort_data_table_sequence">
		  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Bellmanager']->value['Run_Mode']);?>

			</th>  
		
		  <th  width="15%" nowrap="nowrap" onclick="sortTable('tableplan', 3,'1')" class="sort_data_table_sequence">
		  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Bellmanager']->value['Ring_Name']);?>

			</th>  
		  
		  <th  width="8%" nowrap="nowrap">
		  	<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Ring_Volume'];?>

		  </th >
		
		  <th  width="8%" nowrap="nowrap" onclick="sortTable('tableplan', 5,'1')" class="sort_data_table_sequence">
		  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Bellmanager']->value['Play_Time']);?>

			</th>  
		
		  <th  width="8%" nowrap="nowrap" onclick="sortTable('tableplan', 6,'1')" class="sort_data_table_sequence">
		  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Bellmanager']->value['Start_date']);?>

			</th>  
		
		  <th  width="8%" nowrap="nowrap" onclick="sortTable('tableplan', 7,'1')" class="sort_data_table_sequence">
		  	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Bellmanager']->value['End_Date']);?>

			</th>  
		  
		  <th  width="8%" nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Duration'];?>
</th >
		
		  <th  width="10%" nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Task_Terminal'];?>
</th >
		</tr>
	</thead>
	<tbody>
	<?php if (count($_smarty_tpl->tpl_vars['getplantaskinfo']->value) != 0) {?>
	 <?php
$__section_loop_0_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_loop']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop'] : false;
$__section_loop_0_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['getplantaskinfo']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_loop_0_total = $__section_loop_0_loop;
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = new Smarty_Variable(array());
if ($__section_loop_0_total != 0) {
for ($__section_loop_0_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] = 0; $__section_loop_0_iteration <= $__section_loop_0_total; $__section_loop_0_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']++){
?>        
	      <tr align='center' class="tablestyle"> 
		   
			<td nowrap="nowrap"><?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>
</td>
			
			<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['getplantaskinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskname'];?>
 </td>
			
			<td nowrap="nowrap">
				<?php if ($_smarty_tpl->tpl_vars['getplantaskinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'] == "0000000") {?>
					<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Manual'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['getplantaskinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'] != "0000000") {?>
					<?php echo '<script'; ?>
>document.write(getdayofweek("<?php echo $_smarty_tpl->tpl_vars['getplantaskinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'];?>
"))<?php echo '</script'; ?>
>
				<?php }?>
			</td>
	
			<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['getplantaskinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['name'];?>
 </td>
			
			<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['getplantaskinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['defaultvolume'];?>
 </td>
			
			<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['getplantaskinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['playtime'];?>
</td>
			
			<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['getplantaskinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['startdate'];?>
</td> 
			
			<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['getplantaskinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['enddate'];?>
 </td>
	
			<td nowrap="nowrap">
			<?php echo '<script'; ?>
 language="javascript">
			var gettimetype=<?php echo $_smarty_tpl->tpl_vars['getplantaskinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['timelengthtype'];?>
;
			var gettime=<?php echo $_smarty_tpl->tpl_vars['getplantaskinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['timelength'];?>
;
	
			if(gettimetype<2)
			{
				var gethour;
				var getmin;
				var getsen;
				
				gethour=parseInt(gettime/3600);
				getmin=parseInt((gettime-gethour*3600)/60);
				getsen=gettime-gethour*3600-getmin*60;
				if(gethour>0)
					document.write(gethour,'<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['hour'];?>
');
				if(getmin>0)
					document.write(getmin,'<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['min'];?>
');
				if(getsen>0)
					document.write(getsen,'<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Second'];?>
');
			}
			else
			{
			document.write(gettime,'<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['num'];?>
');
			}
				<?php echo '</script'; ?>
>
		
			</td>
	
			<td nowrap="nowrap"><a href="displayplanonetaskterminal.php?taskid=<?php echo $_smarty_tpl->tpl_vars['getplantaskinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
"><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['View_Terminal'];?>
</a></td>
	
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
	 	<td colspan="9">
			<strong style="font-size:12px"><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['No_data'];?>
</strong>
		</td>
	 </tr>
	<?php }?>
	</table>
</div>
</tbody>
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
<div class="link_style" style="float:none; margin-top:5px; text-align:center;"><?php echo $_smarty_tpl->tpl_vars['pagestr']->value;?>
</div><?php }
}
