<?php
/* Smarty version 3.1.30, created on 2026-05-26 15:41:24
  from "/var/www/html/ok112/smarty/templates/TerminalManager/led_sousuo_from.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a154ea4009e23_10719422',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '39f9b1964a3238ab4dd5bc71d482bbc55bbc234f' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/TerminalManager/led_sousuo_from.html',
      1 => 1778116102,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a154ea4009e23_10719422 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<form name="form2" class="terminal_form_to_body">
<tbody>
<div id="divTest" style="width:100%;overflow-x:scroll;overflow-y:scroll">
<table width="98%" border="0" cellpadding="2" cellspacing="1"  align="center" id="tableSort">
<thead>
	<tr align='center' class="terminal_table_row_bg">   
		<th width="7%" nowrap="nowrap" onclick="sortTable('tableSort', 1,'1')"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Select']);?>
</th>                 	
		<th width="18%" nowrap="nowrap" onclick="sortTable('tableSort', 2,'1')"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['devicename']);?>
↑↓</th>    
		<th width="13%" nowrap="nowrap" onclick="sortTable('tableSort', 3,'1')"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['IP_Address']);?>
↑↓</th>      
		<th width="13%" nowrap="nowrap" onclick="sortTable('tableSort', 4,'1')"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['pingwidthheight']);?>
↑↓</th>  
		<th width="12%" nowrap="nowrap" onclick="sortTable('tableSort', 5,'1')"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Terminal_Name']);?>
↑↓</th>  
		<th width="12%" nowrap="nowrap" onclick="sortTable('tableSort', 6,'1')"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['ledgateway']);?>
↑↓</th> 
		<th width="12%" nowrap="nowrap" onclick="sortTable('tableSort', 7,'1')"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['chezhannumber']);?>
↑↓</th> 
		<th width="12%" nowrap="nowrap" onclick="sortTable('tableSort', 8,'1')"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['checi']);?>
↑↓</th> 
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
		  <tr align='center' class="terminal_per_row" onmouseover="this.style.backgroundColor = '#EEEEFF'" onmouseout="this.style.backgroundColor = '#F1F4F5'">             	
			<td nowrap="nowrap">
				<?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['id'];?>

				<input name="id" type="checkbox" id="id" value="<?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['id'];?>
">
			</td>	
			<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['ledname'];?>
</td>	
		<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['ip'];?>
</td>	
			 <td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['ledwidth'];?>
*<?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['ledheight'];?>
</td>	
			 <td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['subterminalname'];?>
</td> 		 
			 <td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['parantterminalname'];?>
</td> 
			 <td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['chezhannumber'];?>
</td> 	
			 <td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['terminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['checi'];?>
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
		<td colspan="6" style="text-align:center"><strong><?php echo $_smarty_tpl->tpl_vars['Revise']->value['No_data'];?>
</strong></td>
	</tr>
	<?php }?>
	</table>
	</div>
	</tbody> 
	<table cellpadding="0" cellspacing="0">
<tr class="tablestyle">
	<td colspan="6" style="text-align:left">
		<a  href="javascript:selAll(0)" name="select_all_yes" id="select_all_yes">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Select_All']);?>

		</a>&nbsp;				
		<a  href="javascript:noSelAll(0)" name="select_all_no" id="select_all_no">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Cancel']);?>

		</a>&nbsp;
		<a href="javascript:del_terminal_shotcut()">
		<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['now_del']);?>

		</a>&nbsp;
		<a href="javascript:void(0)" id="chezhanset" onclick="modifychezhanset(event)">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['chezhanset']);?>

			</a>&nbsp;
			<a href="javascript:updatechezhan()">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['updatechezhan']);?>

				</a>&nbsp;
		<?php if ($_smarty_tpl->tpl_vars['ledflag']->value != 2) {?>        
		<a href="javascript:void(0)" id="systemname" onclick="modifyterminalname(event)">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['modifyname']);?>

			</a>
		<?php }?>	
	</td>
</tr>
</table>

</form>
<div id="change_volume" class="r-displayVolume" >

<div >
	<table border="0" cellpadding="3" cellspacing="0" width="150" style="background-color:#EEFFEE">
		<tr>
			<td nowrap="nowrap" align="center">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['modifyname']);?>

			 <input  maxlength="32" style="width:100px" name="terminalname" type="text" id="terminalname"/>
			</td>
			</tr>
		<tr>
			<tr>
				<td nowrap="nowrap" align="center">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['modifyname']);?>

				<select  class="terminal_select_style" name="subterminalid" id="subterminalid" style="width:120px">
					<option  value=""><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['collect_task_add']->value['select_terminal']);?>
</option>
					<?php
$__section_sourceid_1_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid'] : false;
$__section_sourceid_1_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['subterminal_info']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_sourceid_1_total = $__section_sourceid_1_loop;
$_smarty_tpl->tpl_vars['__smarty_section_sourceid'] = new Smarty_Variable(array());
if ($__section_sourceid_1_total != 0) {
for ($__section_sourceid_1_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] = 0; $__section_sourceid_1_iteration <= $__section_sourceid_1_total; $__section_sourceid_1_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']++){
?>
							<option value="<?php echo $_smarty_tpl->tpl_vars['subterminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] : null)]['id'];?>
"><?php echo $_smarty_tpl->tpl_vars['subterminal_info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_sourceid']->value['index'] : null)]['terminalname'];?>
</option>
					<?php
}
}
if ($__section_sourceid_1_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_sourceid'] = $__section_sourceid_1_saved;
}
?>
					</select>
				</td>
				</tr>
			<tr>
			<td nowrap="nowrap" align="center">
				<a href="javascript:void(0)" onclick="set_terminalname()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Sumbit']);?>
			
				</a>
				
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="javascript:void(0)" onclick="disappear_pass_div()"> 
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Cancel']);?>
		
				</a>
			</td>
		</tr>
		
	</table>
</div>
</div>


<div id="change_chezhan" class="r-displayVolume" >

	<div >
		<table border="0" cellpadding="3" cellspacing="0" width="150" style="background-color:#EEFFEE">
			<tr>
				<td nowrap="nowrap" align="center">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['chezhannumber']);?>

				 <input  maxlength="2" style="width:100px" name="chezhannumber" type="text" id="chezhannumber"/>
				</td>
				</tr>
			<tr>
				<tr>
					<td nowrap="nowrap" align="center">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['checi']);?>

						 <input  maxlength="6" style="width:100px" name="checi" type="text" id="checi"/>
						</td>
					</tr>
				<tr>
				<td nowrap="nowrap" align="center">
					<a href="javascript:void(0)" onclick="set_chezhan()"> 
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Sumbit']);?>
			
					</a>
					
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="javascript:void(0)" onclick="disappear_pass_div()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Cancel']);?>
		
					</a>
				</td>
			</tr>
			
		</table>
	</div>
	</div>



<?php echo '<script'; ?>
 language="javascript">

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
		alert('Not Supported AJAX');
	}
} 

function disappear_pass_div()
{
	if(document.getElementById('change_volume').style.display == "block")
	{
		document.getElementById('change_volume').style.display = "none";
	}
	if(document.getElementById('change_chezhan').style.display == "block")
	{
		document.getElementById('change_chezhan').style.display = "none";
	}
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
                x:eve.clientX,
                y:eve.clientY/3
            };
   }
}

function get_div_obj(str_id)
{
 	return document.getElementById(str_id);   
}




function modifyterminalname(event)
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

    get_div_obj('change_volume').style.left = mouse_obj_xy.x+100+'px';
    get_div_obj('change_volume').style.top = mouse_obj_xy.y-30+'px';
	

	get_div_obj('change_volume').style.display = "block";
}

function modifychezhanset(event)
{
	var getid=getCheckboxItem();
		if(getid==""||getid==null)
		{
			alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_task']);?>
");
		}
	else
	{
		var strarray=getid.split(",");
		if(strarray.length>1)
		{
			alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Only_select_one']);?>
');
			return void(0);
		}

		if(document.all)
    {
        window.event.cancelBubble = true;   
    }
    else
    {
        event.stopPropagation();
    }
    var mouse_obj_xy = get_mouse_coordinates(event);

    get_div_obj('change_chezhan').style.left = mouse_obj_xy.x+100+'px';
    get_div_obj('change_chezhan').style.top = mouse_obj_xy.y-30+'px';
	  get_div_obj('change_chezhan').style.display = "block";
	}
}

function set_chezhan()
{
	var getid=getCheckboxItem();
	
		if(getid==null||getid=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_terminal']);?>
");
		
		return void(0);	
	}
	else
	{
		var strarray=getid.split(",");
		if(strarray.length>1)
		{
			alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Only_select_one']);?>
');
			return void(0);
		}

		createXMLHttpRequest();
		var chezhannumber=document.getElementById('chezhannumber').value;
		var checi=document.getElementById('checi').value;
		
		xmlhttp.open( "get","ledsetterminalname.php?id="+getid+"&chezhannumber="+chezhannumber+"&checi="+checi+"",true);
	   xmlhttp.onreadystatechange = function()
	   {
		  if( xmlhttp.readyState == 4 )
		  {
			 if( xmlhttp.status == 200 )
			 {
					document.getElementById('change_chezhan').style.display = "none";
					if(xmlhttp.responseText == 3)
					{
						alert('信息设置错误!');
					}
					else
					{
						alert('设置成功!');
					}
				
				self.location.reload();
			}
		  }
	   }
		xmlhttp.setRequestHeader( "If-Modified-Since", "0");
		xmlhttp.send(null);	
		
	}
	
	
	
}


<?php echo '</script'; ?>
>
<table align="center"><tr><td><div class="link_style" align="center"><?php echo $_smarty_tpl->tpl_vars['pagestr']->value;?>
</div></td></tr></table>

<?php }
}
