<?php
/* Smarty version 3.1.30, created on 2026-05-25 11:51:01
  from "/var/www/html/ok112/smarty/templates/AdmManger/AdmManger_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a13c72582b1a2_53558547',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '22f9a428da6f50433cd6de5b000ebd60504e03c1' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/AdmManger/AdmManger_form.html',
      1 => 1778116039,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a13c72582b1a2_53558547 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
if (!is_callable('smarty_modifier_truncate')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.truncate.php';
?>
<form name="fileAdvForm" class="terminal_form_to_body">
<tbody>
<div id="divTest" style="width:100%;overflow-x:hidden;overflow-y:scroll">
<table width="98%" border="0" cellpadding="2" cellspacing="1"  align="center" id="tabletask">
	<thead>
    <tr align='center' class="terminal_table_row_bg">
	
		<th width="8%" nowrap="nowrap" class="change_thead_style">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Select']);?>

		</th>
		
    <th width="10%" nowrap="nowrap" onclick="sortTable('tabletask', 1,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Collection_Task']);?>
↑↓
		</th>
		
   	<th width="10%" nowrap="nowrap" onclick="sortTable('tabletask', 2,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Play_Cycle']);?>
↑↓
		</th>
		
		<th width="5%" nowrap="nowrap" onclick="sortTable('tabletask', 3,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Duration']);?>
↑↓
		</th>
		
		<th width="5%" nowrap="nowrap" onclick="sortTable('tabletask', 4,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Start_Date']);?>
↑↓
		</th>
		
		<th width="5%" nowrap="nowrap" onclick="sortTable('tabletask', 5,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['End_Date']);?>
↑↓
		</th>

		<th width="5%" nowrap="nowrap" onclick="sortTable('tabletask', 6,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Run_Time']);?>
↑↓
		</th>
		
		<th width="5%" nowrap="nowrap" onclick="sortTable('tabletask', 7,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Status']);?>
↑↓
		</th>
		<th width="5%" nowrap="nowrap" onclick="sortTable('tabletask', 8,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Collection_Channel']);?>
↑↓
		</th>
		
		<th width="5%" nowrap="nowrap" onclick="sortTable('tabletask', 9,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Sampling_Rate']);?>
↑↓
		</th>
		
		<th width="5%" nowrap="nowrap" onclick="sortTable('tabletask', 10,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Bit_Rate']);?>
↑↓
		</th>
		
		<th width="5%" nowrap="nowrap" onclick="sortTable('tabletask', 11,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Task_level']);?>
↑↓
		</th>
		<th width="5%" nowrap="nowrap" onclick="sortTable('tabletask', 12,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Volume']);?>
↑↓
		</th>
		<th width="5%" nowrap="nowrap" onclick="sortTable('tabletask', 13,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['belonguser']);?>
↑↓
		</th>
		<th width="8%" nowrap="nowrap">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Terminal_Attribute']);?>

		</th>
    </tr>
	</thead>

	<?php if (count($_smarty_tpl->tpl_vars['info']->value) != 0) {?>
	  <?php
$__section_loop_0_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_loop']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop'] : false;
$__section_loop_0_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_loop_0_total = $__section_loop_0_loop;
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = new Smarty_Variable(array());
if ($__section_loop_0_total != 0) {
for ($__section_loop_0_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] = 0; $__section_loop_0_iteration <= $__section_loop_0_total; $__section_loop_0_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']++){
?>            
	   <tr align='center'>
			<td nowrap="nowrap">
				<?php if ($_smarty_tpl->tpl_vars['is_right']->value == 1 || $_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>
					<input name="id" type="checkbox" id="id" value="<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
">
				<?php } else { ?>
					<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>

				<?php }?>
			</td>
			
			<td nowrap="nowrap">
				<label title="<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskname'];?>
">
					<?php echo smarty_modifier_truncate($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskname'],24,"...");?>

				</label> 
			</td>  
			
			<td nowrap="nowrap">
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'] == "0000000") {?>
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Manual']);?>

				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'] != "0000000") {?>
					<?php echo '<script'; ?>
 language="javascript">document.write(getdayofweek("<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'];?>
"))<?php echo '</script'; ?>
>
				<?php }?>
			</td> 
			
			<td nowrap="nowrap">
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['timelengthtype'] == 1) {?>            	
				<?php echo '<script'; ?>
 language="javascript">
				var gethour;
				var getmin;
				var getsen;
				var gettime=<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['timelength'];?>
;
				gethour=parseInt(gettime/3600);
				getmin=parseInt((gettime-gethour*3600)/60);
				getsen=gettime-gethour*3600-getmin*60;
				if(gethour>0)
					document.write(gethour,'<?php echo $_smarty_tpl->tpl_vars['collect_manager']->value['hour'];?>
');
				if(getmin>0)
					document.write(getmin,'<?php echo $_smarty_tpl->tpl_vars['collect_manager']->value['min'];?>
');
				if(getsen>0)
					document.write(getsen,'<?php echo $_smarty_tpl->tpl_vars['collect_manager']->value['sec'];?>
');
				<?php echo '</script'; ?>
>
				<?php } else { ?>      
					<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['timelength'];
echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Times']);?>
 
				<?php }?>  
			</td> 

			<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['startdate'];?>
 </td> 	
			
			<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['enddate'];?>
 </td> 
			
			<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['playtime'];?>
 </td>
			
			<td nowrap="nowrap">
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 0 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['projectstate'] == 0) {?>	
				<span style="color:#ff0000;">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Ready']);?>
●</span> 
			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 0 && $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['projectstate'] == 1) {?>	
				<?php echo $_smarty_tpl->tpl_vars['collect_manager']->value['disable'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 3) {?>     
			<span style="color:#0f6b24;">     
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Run']);?>
 </span> 
			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 2) {?> 
			<span style="color:#0f6b24;">  
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Pause']);?>
 </span> 
			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 1) {?>
			<span style="color:#0f6b24;">  
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Ready_play']);?>
 </span> 
			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 4) {?>
			<span style="color:#0f6b24;">  
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Pause']);?>
 </span> 
			<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 5) {?>
			<span style="color:#0f6b24;">  
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['failed']);?>
  </span> 
			<?php }?> 


			
			</td> 
			<?php echo '<script'; ?>
  src="smarty/templates/ajax/changeselect1.js"><?php echo '</script'; ?>
>			
			
			
			 <td nowrap="nowrap">
			 	<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['cmdargs'];?>

			 </td> 
			  
			 <td nowrap="nowrap">
				 <?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['samplerate'];?>
Hz
			 </td> 
			 
			 <td nowrap="nowrap">
			 	<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['bandrate'];?>
Kbp/s
			 </td>
			 
			  <td nowrap="nowrap">
			 	<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['priority'];?>

			 </td>
			 
			 <td nowrap="nowrap">
			 	<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['defaultvolume'];?>

			 </td>
			  <td nowrap="nowrap">
			 	<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['username'];?>

			 </td>
			 
			<td nowrap="nowrap">
				<a name="link_view" id="link_view" href="displayterminal.php?term_id=<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Terminal']);?>

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
<tr align='center'>
	<td colspan="14">
		<strong><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['No_Record']);?>
</strong>
	</td>
</tr>
<?php }?> 
</table>
</div>
</tbody>
<table cellpadding="0" cellspacing="0">
	<tr>
	<td colspan="12" nowrap="nowrap">	
<table border="0">
<?php echo '<script'; ?>
 src="smarty/templates/UserAccessControl/CheckUserRights.js" type="text/javascript" language="javascript"><?php echo '</script'; ?>
>
<tr>
	<td align="left" nowrap="nowrap">
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Select_All_task']);?>
" href="javascript:selAll(0)" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Select_All']);?>

		</a> &nbsp;
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Cancel_all_task']);?>
" href="javascript:noSelAll(0)" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Cancel']);?>

		</a>&nbsp;
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Run_task']);?>
" href="javascript:startTask()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Run']);?>

		</a>&nbsp;
			
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Pause_task']);?>
" href="javascript:stopTask()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Pause']);?>

		</a>&nbsp;
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Add_task']);?>
" href="addadmtask.php" target='main' >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Add']);?>

		</a>&nbsp;
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Update_task']);?>
" href="javascript:modifyAdmTask()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Update']);?>

		</a>&nbsp;
		
		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Delete_task']);?>
" href="javascript:delTask()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Delete']);?>

		</a>&nbsp;
		
		<a  href="javascript:enableTask()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['enable']);?>

		</a>&nbsp;
			
		<a  href="javascript:disableTask()" >
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['disable']);?>

		</a>&nbsp;

		<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Volume_task']);?>
" href="javascript:void(0)" id="volume_handle" onclick="mouse_click_position(event)">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Volume']);?>

		</a>
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
	</td>
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
<?php echo '</script'; ?>
>
</form>
 <table align="center">
 	<tr>
		<td>
 			<div class="link_style" align="center"><?php echo $_smarty_tpl->tpl_vars['pagestr']->value;?>
</div>
		</td>
	</tr>
 </table>
 <!--  搜索表单  -->
<form name='form3' action='admmanager.php' method='get'>
<input type='hidden' name='dopost' value='' />
<table class="middle" width='98%'  border='0' cellpadding='0' cellspacing='0' align="center">
  <tr>
    <td background='skin/images/wbg.gif' align='center'>
      <table border='0' cellpadding='0' cellspacing='0'>
        <tr>
          <td width='90' align='center'><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Search_conditions'];?>
</td>
          <td width='160'>
          <select class="colors" name='searchkey' id="searchkey">
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
          	<input class="colors" name='searchvalue' type='text' id="searchvalue" style='width:150px' value='' />
        </td>
        <td width='110'>
    		<select class="colors" name='orderby' id="orderby">
    		  <option value=""><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Sort'];?>
</option>
    		  <option value="playtime"><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Time'];?>
</option>
      	    </select>
        </td>
        <td>
          <input name="imageField" type="image" src="<?php echo $_smarty_tpl->tpl_vars['collect_manager']->value['search_image'];?>
" style="width:45px; height:20px; border-bottom-width:0px;"/>
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
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_manager']->value['Sumbit']);?>

				</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="javascript:void(0)" onclick="disappear_volume_div()">
					<?php echo $_smarty_tpl->tpl_vars['collect_manager']->value['Cancel'];?>

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
		alert("<?php echo $_smarty_tpl->tpl_vars['collect_manager']->value['no_zhichi'];?>
");
	}
} 

function ajax_set_task_volume(set_volume_server,volume_value,task_id)
{
   createXMLHttpRequest();
   
   if(task_id == "")
   {
		
		alert("<?php echo $_smarty_tpl->tpl_vars['collect_manager']->value['select_webradio_task'];?>
");
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
				alert("<?php echo $_smarty_tpl->tpl_vars['collect_manager']->value['failed'];?>
");
            }
			else if(xmlhttp.responseText == 1)
			{
				alert("<?php echo $_smarty_tpl->tpl_vars['collect_manager']->value['success'];?>
");
				
				get_div_obj('change_volume').style.display = "none";
				
				self.location.reload();
			}
         }
		 else
		 {
			alert("<?php echo $_smarty_tpl->tpl_vars['collect_manager']->value['failed'];?>
");
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
<?php echo '</script'; ?>
>
</body>
</html><?php }
}
