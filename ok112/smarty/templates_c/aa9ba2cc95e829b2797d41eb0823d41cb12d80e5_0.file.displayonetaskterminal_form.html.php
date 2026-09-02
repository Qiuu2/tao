<?php
/* Smarty version 3.1.30, created on 2026-05-25 14:53:57
  from "/var/www/html/ok112/smarty/templates/BellManager/displayonetaskterminal_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a13f205da18f2_02693335',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'aa9ba2cc95e829b2797d41eb0823d41cb12d80e5' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/BellManager/displayonetaskterminal_form.html',
      1 => 1778116056,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a13f205da18f2_02693335 (Smarty_Internal_Template $_smarty_tpl) {
?>
<table width="98%" border="0" cellpadding="2" cellspacing="1" align="center" class="terminal_form_to_body">
<tr align='center' class="terminal_table_row_bg">
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['No_'];?>
</td>
  <td width="15%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Terminal_Name'];?>
</td>
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Terminal_Type'];?>
</td>
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Network_Status'];?>
</td>
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Device_Status'];?>
</td>
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Task_Status'];?>
</td>
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['IP_Address'];?>
</td>
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Volume'];?>
</td>
 
  <td width="10%"  nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Zone'];?>
</td>
 
</tr>
<?php if (count($_smarty_tpl->tpl_vars['terminal_info']->value) != 0) {
$__section_terminal_0_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal'] : false;
$__section_terminal_0_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['terminal_info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_terminal_0_total = $__section_terminal_0_loop;
$_smarty_tpl->tpl_vars['__smarty_section_terminal'] = new Smarty_Variable(array());
if ($__section_terminal_0_total != 0) {
for ($__section_terminal_0_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] = 0; $__section_terminal_0_iteration <= $__section_terminal_0_total; $__section_terminal_0_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']++){
?>        
<tr align='center' onmouseover="this.style.backgroundColor ='#EEEEFF';" onmouseout="this.style.backgroundColor='#FFFFFF';">
	<td><?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)+1;?>
</td>
	
	<td nowrap="nowrap"> <?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['terminalname'];?>
 </td>
	
	<td nowrap="nowrap">
	
		<?php
$__section_type_1_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_type']) ? $_smarty_tpl->tpl_vars['__smarty_section_type'] : false;
$__section_type_1_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['type_info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_type_1_total = $__section_type_1_loop;
$_smarty_tpl->tpl_vars['__smarty_section_type'] = new Smarty_Variable(array());
if ($__section_type_1_total != 0) {
for ($__section_type_1_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] = 0; $__section_type_1_iteration <= $__section_type_1_total; $__section_type_1_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']++){
?>
			<?php if ($_smarty_tpl->tpl_vars['type_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_type']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_type']->value['index'] : null)]['id'] == $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['typeid']) {?>
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
if ($__section_type_1_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_type'] = $__section_type_1_saved;
}
?>
	
	</td> 
	
	<td>
			<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 0) {?>
					<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Disconnected'];?>

				<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1) {?>
					<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Connected'];?>

			<?php }?>
	</td>
	<td>
			<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 0) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Interrupted'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['devicestate'] == 0) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Idle'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['devicestate'] == 1) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Run'];?>

			<?php }?>
	</td>
	<td>
			<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 0) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Interrupted'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['taskstate'] == 0) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Ready'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['taskstate'] == 1) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Timing_Play'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['taskstate'] == 2) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['On_Talkback'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['taskstate'] == 3) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['AOD'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['taskstate'] == 4) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Selected_Play'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['taskstate'] == 5) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Paging'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['taskstate'] == 6) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Talkback_Ready'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['taskstate'] == 7) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Local_Amplifying'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['taskstate'] == 8) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Play_from_USB'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['taskstate'] == 9) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Request_Talkback'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['taskstate'] == 10) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Requested_Talkback'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['taskstate'] == 11) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Paging_Play'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['taskstate'] == 12) {?>
				<?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['Timing_Play'];?>

			<?php }?>
		
	</td> 
	<td><?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['ip'];?>
</td>
	<td><?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['volume'];?>
</td>

	<td nowrap="nowrap">
		<?php if ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['groupid'] != 0) {?>
			<?php
$__section_stream_2_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_stream']) ? $_smarty_tpl->tpl_vars['__smarty_section_stream'] : false;
$__section_stream_2_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['stream_info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_stream_2_total = $__section_stream_2_loop;
$_smarty_tpl->tpl_vars['__smarty_section_stream'] = new Smarty_Variable(array());
if ($__section_stream_2_total != 0) {
for ($__section_stream_2_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index'] = 0; $__section_stream_2_iteration <= $__section_stream_2_total; $__section_stream_2_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index']++){
?>
				<?php if ($_smarty_tpl->tpl_vars['stream_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index'] : null)]['id'] == $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['groupid']) {?>
					<?php echo $_smarty_tpl->tpl_vars['stream_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_stream']->value['index'] : null)]['name'];?>

				<?php }?>
			<?php
}
}
if ($__section_stream_2_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_stream'] = $__section_stream_2_saved;
}
?>
		<?php } elseif ($_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_terminal']->value['index'] : null)]['groupid'] == 0) {?>
			......
		<?php }?>
	</td>

</tr>         
<?php
}
}
if ($__section_terminal_0_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_terminal'] = $__section_terminal_0_saved;
}
} else { ?>
<tr align='center' onmouseover="this.style.backgroundColor ='#EEEEFF';" onmouseout="this.style.backgroundColor='#FFFFFF';"> 
 	<td colspan="9">
		<strong><?php echo $_smarty_tpl->tpl_vars['Bellmanager']->value['No_data'];?>
</strong>
	</td>
 </tr>
<?php }?>
</table>

<div class="link_style" style="float:none; margin-top:5px; text-align:center;"><?php echo $_smarty_tpl->tpl_vars['pagestr']->value;?>
</div><?php }
}
