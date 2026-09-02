<?php
/* Smarty version 3.1.30, created on 2026-07-06 14:06:31
  from "/var/www/html/ok112/smarty/templates/UserManager/usermanager_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a4b45e70bcd39_55059911',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '5837ef7686cb827195412587e203be196ce568e0' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/UserManager/usermanager_form.html',
      1 => 1778116113,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a4b45e70bcd39_55059911 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>

<form name="form2" class="terminal_form_to_body">
<table width="98%" border="0" cellpadding="2" cellspacing="1" align="center">
	<tr align='center' class="terminal_table_row_bg">   
		<td width="8%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_manager']->value['Selection']);?>
</td>                 	
		<td width="15%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_manager']->value['User_Name']);?>
</td>  
		<td width="10%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_manager']->value['user_state']);?>
</td>      
		<td width="10%"><?php echo $_smarty_tpl->tpl_vars['user_manager']->value['User_Group'];?>
</td> 
		<td width="15%"><?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Description'];?>
</td>  
		<td width="10%"><?php echo $_smarty_tpl->tpl_vars['user_manager']->value['userterminal'];?>
</td>   
		<td width="10%"><?php echo $_smarty_tpl->tpl_vars['user_manager']->value['erweima'];?>
</td>     
		       	
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
		<tr align='center' >          	
			<td>
				<?php if ($_smarty_tpl->tpl_vars['is_right']->value == 1 || $_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>
					<input name="id" type="checkbox" id="id" value="<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['id'];?>
" class="np">	
				<?php } else { ?>
					<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>

				<?php }?>	 
			</td>
			<td nowrap="nowrap">
				<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['username'];?>
 
			</td> 
			<td nowrap="nowrap">
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['enable'] == 1) {?>
				<span style="color:#ff0000;">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_manager']->value['enable']);?>
●</span>
				<?php } else { ?>
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_manager']->value['disable']);?>

				<?php }?>	 
			</td> 
			<td nowrap="nowrap"> 
				<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['groupname'];?>
 
			</td> 	
			<td nowrap="nowrap"> 
				<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['info'];?>
 
			</td> 
			<td nowrap="nowrap">
			<a name="link_view" id="link_view" href="view_user_terminal.php?id=<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['id'];?>
">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_manager']->value['browseterminal']);?>

			</a>
		</td> 	
		<td nowrap="nowrap">
			<a name="erweima" id="erweima" href="#"   onmousemove="mouse_click_position(event,<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['id'];?>
)" onmouseout="mouse_out(event)">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_manager']->value['weima']);?>

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
<tr align='center' >
<td colspan="4"><strong><?php echo $_smarty_tpl->tpl_vars['Revise']->value['No_data'];?>
</strong></td>
</tr>
<?php }?>
	<tr style="background-color: #FFFFFF;">
		<td colspan="4">
			<a title="<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Select_All_user'];?>
" href="javascript:selAll(0)" name="all" id="all">
				<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Select_All'];?>

			</a>&nbsp;
			
			<a title="<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Cancel_all_user'];?>
" href="javascript:noSelAll(0)" name="cancel" id="cancel">
				<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Cancel'];?>

			</a>&nbsp;
			<a title="<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Enable_User'];?>
" href="javascript:EnableUser()"  name="enableuser" id="enableuser">
				<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Enable_User'];?>

			</a>&nbsp;
			<a title="<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Disable_User'];?>
" href="javascript:DisableUser()" name="disableuser" id="disableuser">
				<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Disable_User'];?>

			</a>&nbsp;
			
			<a title="<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Add_user'];?>
" href='useradd.php' target='main' name="add" id="add">
				<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Add_User'];?>

			</a>&nbsp;
			
			<a title="<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Update_user'];?>
" href="javascript:usermodify()" name="modify" id="modify" target='main'>
				<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Modify_User'];?>

			</a>&nbsp;
			
			<a title="<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Delete_user'];?>
" href="javascript:delUser()" name="delete" id="delete">
				<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['Delete_User'];?>

			</a>&nbsp;
			
			<!--
			<a title="<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['user_terminal'];?>
" href="javascript:view_user_terminal()" name="userterminal" id="userterminal">
				<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['User_Terminal'];?>

			</a>
			-->
		<?php echo '<script'; ?>
 language="javascript">
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
>
		</td>
	</tr>
</table>

<div class="link_style" style="margin-top:10px; margin-bottom:15px; margin-right:auto; margin-left:auto;" align="center"><?php echo $_smarty_tpl->tpl_vars['pagestr']->value;?>
</div>

<!--  搜索表单  -->
<form name='form3' action='usermanager.php' method='get'>
<input type='hidden' name='dopost' value='' />
<table class="middle" width='98%'  border='0' cellpadding='1' cellspacing='1'  align="center">
  <tr>
    <td align='center'>
		<table border='0' cellpadding='0' cellspacing='0'>
			<tr>
				<td width='90' align='center'><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Search_conditions'];?>
</td>
				<td width='160'>
					<select name='searchkey' id="searchkey">
					<option  value="" selected="selected"><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Select_type'];?>
</option>
					<option value="username"><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['User_Name'];?>
</option>
					</select>
				</td>
				
				<td width='70'><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Keyword'];?>
</td>
				
				<td width='160'>
					<input name='searchvalue' type='text' id="searchvalue" style='width:150px' value='' />        
				</td>
				
				<td width='110'>
					<select name='searchsequence' id="searchsequence">
					<option value="" selected="selected"><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Sort'];?>
</option>
					<option value="id"><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Serial_number'];?>
</option>
					</select>        
				</td>
				
				<td>
				<input name="imageField" type="image" src="<?php echo $_smarty_tpl->tpl_vars['user_manager']->value['search_image'];?>
" style="width:45px; height:20px; border-bottom-width:0px;"/>
				</td>
			</tr>
		</table>
    </td>
  </tr>
</table>
</form>
<div id="qrcod"></div>
<div id="er_weima" class="r-displayVolume">
 <iframe style="position:absolute;width:200px;height:200px;left:0px; top:0px;filter:alpha(opacity=0);-moz-opacity:0; border:0;z-index:-1"></iframe>
<div style="position:absolute;border:0;width:200; left:0px; top:0px; height:200px;z-index:100">

	<table border="0" cellpadding="10" cellspacing="0" width="200" height="200" style="background-color:#EEFFEE">
		<tr>
			<td nowrap="nowrap" align="center">
			<div id="qrcodes"></div>

			</td>
			</tr>
		
		
	</table>
</div>
</div>

<?php echo '<script'; ?>
 language="javascript">
var registerflag="<?php echo $_smarty_tpl->tpl_vars['registerflag']->value;?>
";
if(registerflag==1||registerflag==2)
{
	
}
else
{
document.getElementById("add").style.display="none";	
	document.getElementById("delete").style.display="none";	

}

var xmlhttps=null; 

//new QRCode(document.getElementById("qrcode"), "http://www.runoob.com");  // 设置要生成二维码的链接


var qrcode = new QRCode(document.getElementById("qrcodes"), {
	width : 190,
	height : 190
});

function makeCode (elText) {		
	
	qrcode.makeCode(elText);
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

function trim(str)
{   
    return str.replace(/(^\s*)|(\s*$)/ig, "");   
}

function createXMLHttpRequest()
{ 
	if(window.ActiveXObject)
	{ 
		xmlhttps = new ActiveXObject("microsoft.XMLHTTP"); 
	} 
	else if(window.XMLHttpRequest)
	{ 
		xmlhttps = new XMLHttpRequest(); 
	}
	else
	{
		alert('Not Support AJAX');
	}
} 


function ajax_set_erweima(url)
{
   createXMLHttpRequest();
   xmlhttps.open( "get",url, true );
   xmlhttps.onreadystatechange = function()
   {
	
      if( xmlhttps.readyState == 4 )
      {
		
         if( xmlhttps.status == 200 )
         {
		// document.getElementById("qrcod").innerText = trim(xmlhttps.responseText);
				
			qrcode.clear(); // 清除代码
			makeCode(trim(xmlhttps.responseText));	
		}
      }
   }
    xmlhttps.setRequestHeader( "If-Modified-Since", "0");
	xmlhttps.send(null);
}
	
	function mouse_click_position(event,id)
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
		get_div_obj('er_weima').style.left = mouse_obj_xy.x-250+'px';
		get_div_obj('er_weima').style.top = mouse_obj_xy.y+'px';
		get_div_obj('er_weima').style.display = "block";
		var url = "set_er_weima.php?id="+id+"";
		ajax_set_erweima(url);
		
	}
	
	function mouse_out(event)
	{
		if(document.getElementById('er_weima').style.display == "block")
		{
			document.getElementById('er_weima').style.display = "none";
		}
	
	}




<?php echo '</script'; ?>
><?php }
}
