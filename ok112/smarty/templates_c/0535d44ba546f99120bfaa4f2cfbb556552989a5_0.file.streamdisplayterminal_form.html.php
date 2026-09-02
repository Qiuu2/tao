<?php
/* Smarty version 3.1.30, created on 2026-05-25 16:17:31
  from "/var/www/html/ok112/smarty/templates/zhaoshengManager/streamdisplayterminal_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a14059b9f4429_12818707',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '0535d44ba546f99120bfaa4f2cfbb556552989a5' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/zhaoshengManager/streamdisplayterminal_form.html',
      1 => 1778116119,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a14059b9f4429_12818707 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<div id="divTest" style="width:100%;overflow-x:hidden;overflow-y:scroll">
<table width="98%" id="displayttable" border="0" cellpadding="2" cellspacing="1"  align="center" class="terminal_form_to_body">
 <thead>
<tr align='center' class="terminal_table_row_bg">
  <th width="5%"  nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['No']);?>
</th>
  <th width="15%" onclick="sortTable('displayttable', 1)" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Terminal_Name']);?>
↑↓</th>
  <th width="10%" onclick="sortTable('displayttable', 2)"  nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Terminal_Type']);?>
↑↓</th>
  <th width="10%"  onclick="sortTable('displayttable', 3)" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Network_Status']);?>
↑↓</th>
  <th width="10%" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Device_Status']);?>
</th>
  <th width="10%" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Task_Status']);?>
</th>
  <th width="10%" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['IP_Address']);?>
</th>
  <th width="10%" nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['stream_terminal']->value['Volume'];?>
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
      
      <tr align='center' class="tablestyle"  height="22"> 
	   	<td><?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>
</td>
      	 <td><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['terminalname'];?>
</td>  
		<td><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typename'];?>
</td> 
		<td>
			<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Connected']);?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 0) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Disconnected']);?>

			<?php }?>
		</td>
		
		<td>
			<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 0) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Disconnected']);?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['devicestate'] == 0) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Idle']);?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['devicestate'] == 1) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Running']);?>

			<?php }?>
		</td>

		<td>
			<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 0) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Disconnected']);?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 0) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Ready']);?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 1) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Timing_Play']);?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 2) {?>
				<?php echo $_smarty_tpl->tpl_vars['stream_terminal']->value['On_Talkback'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 3) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['AOD']);?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 4) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Selected_Play']);?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 5) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Paging']);?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 6) {?>
				<?php echo $_smarty_tpl->tpl_vars['stream_terminal']->value['Talkback_Ready'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 7) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Local_Amplifying']);?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 8) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Play_from_USB']);?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 9) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Request_Talkback']);?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 10) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Requested_Talkback']);?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 11) {?>
				<?php echo $_smarty_tpl->tpl_vars['stream_terminal']->value['Paging_Play'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['netstate'] == 1 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskstate'] == 12) {?>
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['Timing_Play']);?>

			<?php }?>
		</td>	
		<td><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['ip'];?>
</td>
		<td><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['volume'];?>
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
<td colspan="8"><strong><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['No_Record']);?>
</strong></td>
</tr>
<?php }?>
</tbody>
</table>
</div>


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

<div id="divTest2" style="width:100%;overflow-x:hidden;overflow-y:scroll">
<table width="98%" id="devicetable" border="0" cellpadding="2" cellspacing="1"  align="center" class="terminal_form_to_body">
 <thead>
<tr align='center' class="terminal_table_row_bg">
  <th width="5%"  nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['No']);?>
</th>
  <th width="25%" onclick="sortTable('displayttable', 1)" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['devicename']);?>
↑↓</th>

  <th width="20%" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['deviceaddr']);?>
</th>
  <th width="30%" nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['IP_Address']);?>
</th>
  <th width="10%" nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['stream_terminal']->value['devicenum'];?>
</th>
</tr>
</thead>
<tbody>
<?php if (count($_smarty_tpl->tpl_vars['deviceinfo']->value) != 0) {?>
 <?php
$__section_loop_1_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_loop']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop'] : false;
$__section_loop_1_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['deviceinfo']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_loop_1_total = $__section_loop_1_loop;
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = new Smarty_Variable(array());
if ($__section_loop_1_total != 0) {
for ($__section_loop_1_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] = 0; $__section_loop_1_iteration <= $__section_loop_1_total; $__section_loop_1_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']++){
?>
      
      <tr align='center' class="tablestyle" height="22"> 
	   	<td><?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>
</td>
      	 <td><?php echo $_smarty_tpl->tpl_vars['deviceinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['name'];?>
</td>  
		<td><?php echo $_smarty_tpl->tpl_vars['deviceinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['devaddr'];?>
</td> 
		
		<td><?php echo $_smarty_tpl->tpl_vars['deviceinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['ip'];?>
</td>
		<td><?php echo $_smarty_tpl->tpl_vars['deviceinfo']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['dbvalue'];?>
</td>
      </tr>         
    <?php
}
}
if ($__section_loop_1_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = $__section_loop_1_saved;
}
} else { ?>
<tr align='center' onmouseover="this.style.backgroundColor = #EEEEFF" onmouseout="this.style.backgroundColor = #FFFFFF">
<td colspan="8"><strong><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_terminal']->value['No_Record']);?>
</strong></td>
</tr>
<?php }?>
</tbody>
</table>
</div>
<?php }
}
