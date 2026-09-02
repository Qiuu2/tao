<?php
/* Smarty version 3.1.30, created on 2026-05-25 14:00:56
  from "/var/www/html/ok112/smarty/templates/StreamManager/streammanager_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a13e598bd6b44_76139001',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'ba6510f0f25ec761d43c8e4057fa3594e5dda5b2' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/StreamManager/streammanager_form.html',
      1 => 1778116097,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a13e598bd6b44_76139001 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>

<form name="form2" class="terminal_form_to_body">
 <td>
  <div id="divTest" style="width:100%;overflow-x:hidden;overflow-y:scroll">
 <table width="98%" border="0" cellpadding="2" cellspacing="1"  align="center" id="displayttable" >

 <thead>
    <tr align='center' class="terminal_table_row_bg">   
		<th width="10%" nowrap="nowrap">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Select']);?>

		</th>     
		<th width="15%" nowrap="nowrap" onclick="sortTable('displayttable', 1)" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Zone_Name']);?>
↑↓
	  </th>       
		<th width="15%" nowrap="nowrap" onclick="sortTable('displayttable', 2)" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Zone_Description']);?>
↑↓
			</th> 
		<th width="10%" nowrap="nowrap">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Created_Time']);?>

			</th> 
		<th width="10%" nowrap="nowrap">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['View_Terminal']);?>

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
	<tr align='center' class="terminal_per_row" >   
	             	
		<td nowrap="nowrap">
	
		<?php if ($_smarty_tpl->tpl_vars['is_right']->value == 1 || $_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>
		<input name="id" type="checkbox" id="id" value="<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['streamid'];?>
" class="np">
		<?php } else { ?>
			<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>

		<?php }?>
		</td>

		<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['name'];?>
</td>  

		<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['info'];?>
</td>

		<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['createtime'];?>
</td>  

		<td nowrap="nowrap">
			<a  name="link_view" id="link_view" href="streamdisplayterminal.php?id=<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['streamid'];?>
">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Browse']);?>

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
<tr align='center' class="tablestyle" >
<td colspan="5"><strong><?php echo $_smarty_tpl->tpl_vars['Revise']->value['No_data'];?>
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
<table width="98%" border="0" cellpadding="2" cellspacing="1"  align="center" id="tabledo">
	
<tr style="background-color: #FFFFFF;">
		<td height="28" colspan="5">
			<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Select_all_Zone']);?>
" href="javascript:selAll(0)"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Select_All']);?>
</a>&nbsp;
			<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['noSelect_all_Zone']);?>
" href="javascript:noSelAll(0)"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Cancel']);?>
</a>&nbsp;
			<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Add_selectZone']);?>
" href='streamadd.php' target='main'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Add_Zone']);?>
</a>&nbsp;
			<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Modify_selectZone']);?>
" href="javascript:modifyStream()"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Modify_Zone']);?>
</a>&nbsp;
			<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Delete_selectZone']);?>
" href="javascript:delStream()"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['stream_manager']->value['Delete_Zone']);?>
</a>
		</td>
	</tr>
	
 </table>
	</td>
  </tr>
  <tr>
    <td><div class="link_style" align="center"><?php echo $_smarty_tpl->tpl_vars['pagestr']->value;?>
</div></td>
  </tr>
<!--  搜索表单  -->
<form name='form3' action='' method='get'>
<input type='hidden' name='dopost' value='' />
<table width='98%'  border='0' cellpadding='1' cellspacing='1' class="middle" align="center" style="margin-top:8px">
  <tr>
    <td background='skin/images/wbg.gif' align='center'>
      <table border='0' cellpadding='0' cellspacing='0'>
        <tr>
			<td width='90' align='center'><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Search_conditions'];?>
</td>
			<td width='160'>
				<select class="colors" name='searchkey' id="searchkey">
					<option value=''><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Select_type'];?>
</option>
					<option value='name'><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Regional_Name'];?>
 </option>
				</select>
			</td>
			<td width='70'>
				<?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Keyword'];?>
        
			</td>
			<td width='160'>
				<input class="colors" type='text' name='keyvalue' id="keyvalue" value='' />       
			</td>
			
			<td width='110'>
				<select class="colors" name='orderby' id="orderby">
					<option value=''><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Sort'];?>
</option>
					<option value='name'><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Stream_name'];?>
</option>
				</select>        
			</td>
			
			<td>
				<input name="imageField" type="image" src="<?php echo $_smarty_tpl->tpl_vars['stream_manager']->value['search_image'];?>
" width="45" height="20" border="0" class="np" />
			</td>
       </tr>
      </table>
    </td>
  </tr>
</table>
</form>

<?php echo '<script'; ?>
>
	var get_user_right = "<?php echo $_smarty_tpl->tpl_vars['is_right']->value;?>
";
	if(get_user_right == 1)
	{
		//什么也不做
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
