<?php
/* Smarty version 3.1.30, created on 2026-05-25 16:12:20
  from "/var/www/html/ok112/smarty/templates/LogManager/tasklogmanager_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a140464db3546_98293946',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '9636b873f86f1dd77484b8b1a31d5a7613e4950c' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/LogManager/tasklogmanager_form.html',
      1 => 1778116079,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a140464db3546_98293946 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<form name="form2" class="terminal_form_to_body">
<table width="98%" border="0" cellpadding="2" cellspacing="1"  align="center" class="terminal_form_to_body">
<tr align='center' class="terminal_table_row_bg">
  <td width="5%"  nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['Number']);?>
</td>
  <td width="15%"  nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['File_name']);?>
</td>
  <td width="8%"  nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['File_Size']);?>
</td>
  <td width="8%"  nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['create_time']);?>
</td>
  <td width="5%"  nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['File_format']);?>
</td>
  <td width="5%"  nowrap="nowrap"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['Operation']);?>
</td>
</tr>
<tr></tr>
 <tr align='center' class="tablestyle"> 
		<td>
			<input type="checkbox" value="<?php echo $_smarty_tpl->tpl_vars['backup_files']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_file']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_file']->value['index'] : null)]['filename'];?>
" id="id" name="id"/>
		</td>
		
		<td id="filename">
			<?php echo $_smarty_tpl->tpl_vars['get_time']->value;?>

		</td> 
		 	
		<td>
			6.9KB
		</td> 
		
		<td>
			<?php echo $_smarty_tpl->tpl_vars['get_time']->value;?>

		</td>
		
		<td>
			html
		</td>
		
		<td>
	
			 <a   name="link_view" id="link_view" href="http://<?php echo $_smarty_tpl->tpl_vars['get_ipaddr']->value;?>
:<?php echo $_smarty_tpl->tpl_vars['port']->value;?>
/debug.html" target="main"  >
								<?php echo $_smarty_tpl->tpl_vars['backup_restore']->value['lookup'];?>

							</a>
		</td>
	  </tr>    
<?php if (count($_smarty_tpl->tpl_vars['backup_files']->value) != 0) {?>
	<?php
$__section_file_0_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_file']) ? $_smarty_tpl->tpl_vars['__smarty_section_file'] : false;
$__section_file_0_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['backup_files']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_file_0_total = $__section_file_0_loop;
$_smarty_tpl->tpl_vars['__smarty_section_file'] = new Smarty_Variable(array());
if ($__section_file_0_total != 0) {
for ($__section_file_0_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_file']->value['index'] = 0; $__section_file_0_iteration <= $__section_file_0_total; $__section_file_0_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_file']->value['index']++){
?>        
	  <tr align='center' class="tablestyle"> 
		<td>
			<input type="checkbox" value="<?php echo $_smarty_tpl->tpl_vars['backup_files']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_file']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_file']->value['index'] : null)]['filename'];?>
" id="id" name="id"/>
		</td>
		
		<td id="filename">
			<?php echo $_smarty_tpl->tpl_vars['backup_files']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_file']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_file']->value['index'] : null)]['filename'];?>

		</td> 
		 	
		<td>
			<?php echo $_smarty_tpl->tpl_vars['backup_files']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_file']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_file']->value['index'] : null)]['filesize'];?>

		</td> 
		
		<td>
			<?php echo $_smarty_tpl->tpl_vars['backup_files']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_file']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_file']->value['index'] : null)]['filetime'];?>

		</td>
		
		<td>
			<?php echo $_smarty_tpl->tpl_vars['backup_files']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_file']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_file']->value['index'] : null)]['filetype'];?>

		</td>
		
		<td>
	
			 <a charset="gb2312"  name="link_view" id="link_view"  href="javascript:setlog('<?php echo $_smarty_tpl->tpl_vars['backup_files']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_file']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_file']->value['index'] : null)]['filename'];?>
')">
								<?php echo $_smarty_tpl->tpl_vars['backup_restore']->value['lookup'];?>

							</a>
				
			<?php echo '<script'; ?>
 language="javascript">
		
function setlog(filename)
{

location="setlog.php?act="+filename+".html";
}
			<?php echo '</script'; ?>
>				
		
		</td>
	  </tr>         
	<?php
}
}
if ($__section_file_0_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_file'] = $__section_file_0_saved;
}
} else { ?>

<tr align='center' onmouseover="this.style.backgroundColor = '#EEEEFF'" onmouseout="this.style.backgroundColor = '#FFFFFF'">
	<td colspan="6"><strong><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Revise']->value['No_data']);?>
</strong></td>
</tr>

<?php }?>
 <tr style="background-color: #FFFFFF;">
	 <td  colspan="5">
	 <!--
		<a href="javascript:selAll(0)" class="coolbg"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['Select_all']);?>
</a>&nbsp;
		<a href="javascript:noSelAll(0)" class="coolbg"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['Cancel']);?>
</a>&nbsp;
		-->
		<?php if ($_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['Deletelogtask']);?>
" href="javascript:delLog()" class="coolbg"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['backup_restore']->value['Deletelog']);?>
</a>&nbsp;
		<?php }?>
	</td>
</tr>

</table>

 <tr>
   <td>


</td>
  </tr>

</form>

<?php }
}
