<?php
/* Smarty version 3.1.30, created on 2026-05-25 16:11:01
  from "/var/www/html/ok112/smarty/templates/displayproperty/displayterminal_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a140415e171d1_37811219',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'f82d481a096ac56241c07f08a978c912a6569bf9' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/displayproperty/displayterminal_form.html',
      1 => 1778116065,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a140415e171d1_37811219 (Smarty_Internal_Template $_smarty_tpl) {
?>
<table width="98%" border="0" cellpadding="2" cellspacing="1"  align="center" class="terminal_form_to_body">
<tr align='center' class="terminal_table_row_bg">
  <td width="5%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Serial_number'];?>
</td>
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Terminal_name'];?>
</td>
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Terminal_Type'];?>
</td>
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Network_Status'];?>
</td>
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Device_Status'];?>
</td>
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Task_Status'];?>
</td>
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Terminal_IP'];?>
</td>
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Terminal_volume'];?>
</td>
<!--
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Zone'];?>
</td>
-->
</tr>
<tr></tr>
<?php if (count($_smarty_tpl->tpl_vars['info']->value) != 0) {?>
 <?php
$__section_loop_0_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_loop']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop'] : false;
$__section_loop_0_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_loop_0_total = $__section_loop_0_loop;
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = new Smarty_Variable(array());
if ($__section_loop_0_total != 0) {
for ($__section_loop_0_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] = 0; $__section_loop_0_iteration <= $__section_loop_0_total; $__section_loop_0_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']++){
?>        
      <tr align='center' class="tablestyle" > 
	   	<td><?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>
</td>
      	 <td><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['terminalname'];?>
</td>  
		<td>
			<?php
$__section_type_1_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_type']) ? $_smarty_tpl->tpl_vars['__smarty_section_type'] : false;
$__section_type_1_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['terminaltype_info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_type_1_total = $__section_type_1_loop;
$_smarty_tpl->tpl_vars['__smarty_section_type'] = new Smarty_Variable(array());
if ($__section_type_1_total != 0) {
for ($__section_type_1_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] = 0; $__section_type_1_iteration <= $__section_type_1_total; $__section_type_1_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']++){
?>
				<?php if ($_smarty_tpl->tpl_vars['terminaltype_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['id'] == $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid']) {?>
				
				<?php echo '<script'; ?>
 language="javascript">
					document.write(chinese_big5_english("<?php echo $_smarty_tpl->tpl_vars['chinese_big5_english']->value;?>
","<?php echo $_smarty_tpl->tpl_vars['terminaltype_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['name'];?>
"));
				<?php echo '</script'; ?>
>
					
				<?php }?>
			<?php
}
}
if ($__section_type_1_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_type'] = $__section_type_1_saved;
}
?>
		</td> 
		<td>
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Connected'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 0) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Interrupted'];?>

				<?php }?>
		</td>
		<td>
			
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 0) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Interrupted'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['devicestate'] == 0) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Idle'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['devicestate'] == 1) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Run'];?>

				<?php }?>
			
		</td>
		<td>
			
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 0) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Interrupted'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 0) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Ready'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 1) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Timing_Play'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 2) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['On_Talkback'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 3) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['AOD'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 4) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Selected_Play'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 5) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Paging'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 6) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Talkback_Ready'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 7) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Local_Amplifying'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 8) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Play_from_USB'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 9) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Request_Talkback'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 10) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Request_Talkback'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 11) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Paging_Play'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 12) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Timing_Play'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 25) {?>
					<?php echo $_smarty_tpl->tpl_vars['displayterminal']->value['Timing_Play'];?>

				<?php }?>
			
		</td>	
		<td><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['ip'];?>
</td>
		<td><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['volume'];?>
</td>
<!--	
		<td>
		<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['groupid'] != 0) {?>
			<?php
$__section_stream_2_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_stream']) ? $_smarty_tpl->tpl_vars['__smarty_section_stream'] : false;
$__section_stream_2_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['stream_info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_stream_2_total = $__section_stream_2_loop;
$_smarty_tpl->tpl_vars['__smarty_section_stream'] = new Smarty_Variable(array());
if ($__section_stream_2_total != 0) {
for ($__section_stream_2_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index'] = 0; $__section_stream_2_iteration <= $__section_stream_2_total; $__section_stream_2_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index']++){
?>
				<?php if ($_smarty_tpl->tpl_vars['stream_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index'] : null)]['id'] == $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['groupid']) {?>
					<?php echo $_smarty_tpl->tpl_vars['stream_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index'] : null)]['name'];?>

				<?php }?>
			<?php
}
}
if ($__section_stream_2_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_stream'] = $__section_stream_2_saved;
}
?>
		<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['groupid'] == 0) {?>
			......
		<?php }?>
-->	
		</td>
      </tr>         
    <?php
}
}
if ($__section_loop_0_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = $__section_loop_0_saved;
}
} else { ?>
<tr align='center' >
<td colspan="8"><strong><?php echo $_smarty_tpl->tpl_vars['Revise']->value['No_data'];?>
</strong></td>
</tr>
<?php }?>
</table>

<div class="link_style" style="float:none; margin-top:5px; text-align:center;"><?php echo $_smarty_tpl->tpl_vars['pagestr']->value;?>
</div><?php }
}
