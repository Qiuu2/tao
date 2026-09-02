<?php
/* Smarty version 3.1.30, created on 2026-07-01 10:17:51
  from "/var/www/html/ok112/smarty/templates/login_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a4478cf7fc7d6_38083133',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '17cd57d5987ad96c2cd7dd2bd95961214688d0bb' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/login_form.html',
      1 => 1778116078,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a4478cf7fc7d6_38083133 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<form name="form1" method="post" action="" onSubmit="return checkform();" class="terminal_form_to_body">
  <table width="500" border="0" align="center" cellpadding="0" cellspacing="0" class="terminal_table_border">
	<tr>       
	  <td>
		<img src="<?php echo $_smarty_tpl->tpl_vars['user_login']->value['user_login_image'];?>
"/>
      </td>
    </tr>
	<tr>
		<td>
		
			<table width="100%" border="0" cellpadding="10" cellspacing="0"> 
				<tr>
				  <td width="197" align="right"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['Username']);?>
</td>
				  <td width="297">
				  	<input type="text" name="username" id="username"  maxlength="18" style="width:120px">
				  	<span id="username_s">*</span>
				  </td>
				</tr>
			  
				<tr>
					<td align="right"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['Password']);?>
</td>
					<td>
						<input type="password" name="userpwd" id="userpwd" maxlength="16"  style="width:120px"/>
						<span id="userpwd_s">*</span><img id="imagoc" onmousedown="eyeopenorclose()" onmouseup="eyeopenorclose()" src="<?php echo $_smarty_tpl->tpl_vars['user_login']->value['eyeclose'];?>
"/>
					</td>
				</tr>
				<tr>
				  <td align="right"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['Code']);?>
</td>
				  <td>
					<input name="checknum" type="text" id="checknum" size="7" maxlength="7"> 
					<img src="verify.php" id="verify" name="verify"  align="absmiddle"/>
					
					<a href="javascript:void(0)" onclick="refreshcode('verify.php');">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['Refresh']);?>

					</a>
					
					<span id="num_s">*</span>
				  </td>
				</tr>
				<tr>
					<td align="center">
					</td>
					<td align="left">
						<input type="submit" name="button" id="button" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['Sumbit']);?>
" onclick="actionform()" class="regist_button">
						<!--
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" name="regist" id="regist" onclick="regist_server()" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['Cancel']);?>
" class="regist_button">-->
					</td>
				</tr>
		  </table>
  		</td>
  	</tr>
  </table>
</form>
<?php }
}
