<?php
/* Smarty version 3.1.30, created on 2026-05-25 14:01:00
  from "/var/www/html/ok112/smarty/templates/FileManager/group_by_media_folder.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a13e59c67a2c5_56663302',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '5dc65856cd1a33c185234b91e755f91916af68fc' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/FileManager/group_by_media_folder.html',
      1 => 1778116068,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a13e59c67a2c5_56663302 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
if (!is_callable('smarty_function_math')) require_once '/var/www/html/ok112/smarty/libs/plugins/function.math.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FileManager</title>
<link rel="stylesheet" type="text/css" href="skin/css/main_page_style.css" />
<style>
 

	
	/* 奇数行样式 */
	tr:nth-child(odd) {
		background-color: #ffffff;
	}
	
	/* 偶数行样式 */
	tr:nth-child(even) {
		background-color: #c1cfe0;
	}
</style>
<?php echo '<script'; ?>
 type="text/javascript">
function getCheckboxItem()
{
	var allSel="";
	if(document.form2.id.checked)
	{
	 	allSel=document.form2.id.value;
		if(allSel==undefined)
		{
			allSel="";
		}
	 }
	for(i=0;i<document.form2.id.length;i++)
	{
		if(document.form2.id[i].checked)
		{
			if(allSel=="")
				allSel=document.form2.id[i].value;
			else
				allSel=allSel+","+document.form2.id[i].value;
		}
	}
	return allSel;
}

function getOneItem()
{
	var folder_id = "";
	var obj = document.getElementsByName('id');
	for(var i= 0; i<obj.length; i++ )
	{
		if(obj[i].checked == true)
		{
			if(isNull(folder_id))
			{
				folder_id = obj[i].value;
			}
			else
			{
				folder_id +=","+obj[i].value;
			}
			
		}
	}
	
	return folder_id;
}

function selAll(aid)
{
	if(aid==0)
	{
		document.form2.id.checked=true;
	}
	for(i=0;i<document.form2.id.length;i++)
	{
		if(!document.form2.id[i].checked)
		{
			document.form2.id[i].checked=true;
		}
	}
}
function noSelAll(aid)
{
	if(aid==0)
	{
		document.form2.id.checked=false;
	}
	for(i=0;i<document.form2.id.length;i++)
	{
		if(document.form2.id[i].checked)
		{
			document.form2.id[i].checked=false;
		}
	}
}
function delfile()
{
	var getItem=getCheckboxItem();
	
	if(getItem==""||getItem==null)
	{
		if(<?php echo $_smarty_tpl->tpl_vars['get_folder_id']->value;?>
==""||<?php echo $_smarty_tpl->tpl_vars['get_folder_id']->value;?>
==null)
		{
			alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['select_folder']);?>
");
			return void(0);
		}
		if(window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['confirm_alldeleting']);?>
"))
		{
			window.location="do.php?act=delallfiletask_msg&fordid="+<?php echo $_smarty_tpl->tpl_vars['get_folder_id']->value;?>
;
		}
		else
		{
			return void(0);
		}
	}
	else
	{
		if(window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['confirm_deleting']);?>
"))
		{
			window.location="do.php?act=delfiletask_msg&id="+getItem+"";
		}
		else
		{
			return void(0);
		}
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
                x:eve.clientX+document.body.scrollLeft - document.body.clientLeft,
                y:eve.clientY+document.body.scrollTop - document.body.clientTop
           };
   }
}
function get_div_obj(str_id)
{
 	return document.getElementById(str_id);   
}

function disappear_task_div()
{
	if(document.getElementById('copytask').style.display == "block")
	{
		document.getElementById('copytask').style.display = "none";
	}
}

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
		alert('Not Support AJAX');
	}
} 

function ajax_set_task_copy(getinitid,getmediaid)
{
   createXMLHttpRequest();
	 var getterminalid=0;
   xmlhttp.open( "get","getterminalid.php?getterminalid="+getterminalid+"&getinitid="+getinitid+"&getmediaid="+getmediaid,true );
   xmlhttp.onreadystatechange = function()
   {
      if( xmlhttp.readyState == 4 )
      {
         if( xmlhttp.status == 200 )
         {
			
					alert("<?php echo $_smarty_tpl->tpl_vars['file_manager']->value['success'];?>
");
					self.location.reload();
					get_div_obj('copytask').style.display = "none";
				}
      }
   }
  xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	xmlhttp.send(null);
}

function set_terminal(event)
{

	var obj_arr = document.getElementsByName('id');
	
	var count = 0;
	
	var terminal_id = "";
	
    for (var i=0; i<obj_arr.length; i++)
    {
		if(obj_arr[i].checked  == true)
		{
			count ++;
			
			terminal_id = obj_arr[i].value;
		}
    }
	if(count > 1 || count < 1)
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['selectonlyone']);?>
');
		
		return void(0);
	}
	if(count == 1)
	{
		document.getElementById('get_initid').value =terminal_id; 
		 if(document.all)
		{
			window.event.cancelBubble = true;   
		}
		else
		{
			event.stopPropagation();
		}
		var mouse_obj_xy = get_mouse_coordinates(event);
		get_div_obj('copytask').style.left = mouse_obj_xy.x+'px';
		get_div_obj('copytask').style.top = mouse_obj_xy.y-50+'px';
		get_div_obj('copytask').style.display = "block";	
	}
	
}



//涓嬭浇濯掍綋
function download_file(file_name,get_name,get_typeid)
{
	window.location.href = "./download_media_file.php?file_name="+file_name+"&get_name="+get_name+"&get_typeid="+get_typeid;
}
//鑾峰彇妗嗘灦涓彉閲忕殑鍊?

 function get_next_frame_var(obj,get_folder_id)
 {
 	if(get_folder_id==0)
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['select_upload_folder']);?>
");
		return void(0);
	}
	else
	{
		if(<?php echo $_smarty_tpl->tpl_vars['model']->value;?>
==2)
		{
			alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['The_alave_error']);?>
");
			return void(0);
		}
	 	else
		{
		window.location = "fileadd.php?folder_id="+get_folder_id+"";
		}
	}
	

 } 
function delfolder(obj,get_folder_id)
{   
	if(!window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['confirm_deleting']);?>
"))
	{
		return void(0);
	}	
	else
	{
		location="do.php?act=folderdel_msg&id="+get_folder_id+"";
	}
	//window.location ="do.php?act=folderdel_msg&id="+get_folder_id+"";
}

function modifyfolder(obj,get_folder_id)
{
	window.location.href="filefoldermodiry.php?id="+get_folder_id+"";
}
function isNull( str )
{
	if ( str == "" || str==null) 
	return true;
	var regu = "^[ ]+$";
	var re = new RegExp(regu);
	return re.test(str);
}
function isNumber( s )
{ 
	var regu = "^[0-9]+$"; 
	var re = new RegExp(regu); 
	if (s.search(re) != -1) 
	{ 
		return true; 
	}
	else 
	{ 
		return false; 
	} 
}

function set_media_copy()
{
	var getinitid=document.getElementById('get_initid').value;

	var getmediaid=document.getElementById('mediaid').value;
	if(isNull(getmediaid))
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['meidiaerror']);?>
");

		return false;
	}
	if(isNumber(getmediaid))
	{
		ajax_set_task_copy(getinitid,getmediaid);
		return true;
	}
else
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['terminaliderror']);?>
");

		return false;
	}
}


<?php echo '</script'; ?>
>
</head>
<body>
<form name="form2" id="form2" class="terminal_form_to_body"  style="width:100%">
<tbody>
<table width="98%" border="0" cellpadding="2" cellspacing="1"  align="center" style="font-size:12px">
<thead>
	 <tr align='center' class="terminal_table_row_bg">
	
		<th width="8%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Selection']);?>
</th>     		             	
		<th width="15%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Name']);?>
</th>    
		<th width="8%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Size']);?>

		<!--
		(<?php if ($_smarty_tpl->tpl_vars['getsize']->value >= 1024) {?>
					<?php echo smarty_function_math(array('equation'=>"x / 1024",'x'=>$_smarty_tpl->tpl_vars['getsize']->value,'format'=>"%.2f"),$_smarty_tpl);?>
M
				<?php } else { ?>
					<?php echo $_smarty_tpl->tpl_vars['getsize']->value;?>
K				
				<?php }?>
		)
		-->
		</th>          
		<th width="8%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Format']);?>
</th>
		<th width="8%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Bit_rate']);?>
</th>
		
		<th width="8%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Play_Length']);?>
</th>
			
		<th width="8%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Listen']);?>
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
		<tr align='center' >
			<td nowrap="nowrap">
			<?php if ($_smarty_tpl->tpl_vars['is_right']->value == 1 || $_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>
				<input name="id" type="checkbox" id="id" value="<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['id'];?>
" class="np"/>
				<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['id'];?>

			<?php } else { ?>
				<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>

			<?php }?>
			</td>	
			<td nowrap="nowrap">	
				<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['name'];?>

			</td>
			
			<td nowrap="nowrap">
				
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['size'] >= 1024) {?>
					<?php echo smarty_function_math(array('equation'=>"x / 1024",'x'=>$_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['size'],'format'=>"%.2f"),$_smarty_tpl);?>
M
				<?php } else { ?>
					<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['size'];?>
K				
				<?php }?>

			</td>
					
			<td nowrap="nowrap">
				<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'];?>

			</td>
			<td nowrap="nowrap">
				<?php if ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['bitrate'] >= 1000000) {?>
					<?php echo smarty_function_math(array('equation'=>"x / 1000000",'x'=>$_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['bitrate']),$_smarty_tpl);?>
Mbps
				<?php } elseif ($_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['bitrate'] >= 1000) {?>
					<?php echo smarty_function_math(array('equation'=>"x / 1000",'x'=>$_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['bitrate']),$_smarty_tpl);?>
kbps
				<?php } else { ?>
					<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['bitrate'];?>
bps
				<?php }?>
			</td>
			
			<td>
				<?php echo '<script'; ?>
 language="javascript">
					var timelen=<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['timelength'];?>
;
					var getmin=parseInt(timelen/60);
					var getsec=parseInt(timelen%60);
					document.write(getmin+"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['minutes']);?>
"+getsec+"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['second']);?>
");
				<?php echo '</script'; ?>
>
			</td>
		
			<td id="listen_download" >
			<a title="<?php echo $_smarty_tpl->tpl_vars['file_manager']->value['file_listenmusic'];?>
"  id="shiting" name="shiting" href="#" onclick="window.open('try_listenning.php?id=<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['id'];?>
				','_blank','height=65,width=300,status=no,toolbar=no,location=no')">
			<?php echo $_smarty_tpl->tpl_vars['file_manager']->value['file_listen'];?>
	
			</a>
			<a title="<?php echo $_smarty_tpl->tpl_vars['file_manager']->value['file_downloadmusic'];?>
" id="xiazai" name="xiazai" href="javascript:download_file('<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['filename'];?>
','<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['name'];?>
','<?php echo $_smarty_tpl->tpl_vars['info']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['typeid'];?>
')">
			<?php echo $_smarty_tpl->tpl_vars['file_manager']->value['file_download'];?>

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
	<tr align='center' class="tablestyle">
		<td colspan="6" height="30"><strong><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['No_data']);?>
</strong></td>
	</tr>
<?php }
echo '<script'; ?>
 src="smarty/templates/UserAccessControl/CheckUserRights.js" type="text/javascript" ><?php echo '</script'; ?>
>

<tr style="background-color: #FFFFFF;">
	
		<td colspan="6">
			<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Select_allmediadata']);?>
" id="allsel" name="allsel" href="javascript:selAll(0)" >
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Select_all']);?>

			</a>&nbsp;&nbsp;&nbsp;
			
			<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['noSelect_allmediadata']);?>
" id="nosel" name="nosel" href="javascript:noSelAll(0)" >
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Cancel']);?>

			</a>&nbsp;&nbsp;&nbsp;
			
			<a href="javascript:get_next_frame_var(this,'<?php echo $_smarty_tpl->tpl_vars['get_folder_id']->value;?>
')" target='mediafile' >
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Upload_Media']);?>

			</a>&nbsp;&nbsp;&nbsp;
			
			<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['del_selectmedia']);?>
" id="delsel" name="delsel" href="javascript:delfile()" >
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Delete']);?>

			</a>&nbsp;&nbsp;&nbsp;
			
			<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Create_subfolder']);?>
" href="javascript:addfolder(this,'<?php echo $_smarty_tpl->tpl_vars['get_folder_id']->value;?>
')" class="coolbg" >
			<?php echo $_smarty_tpl->tpl_vars['file_manager']->value['Create_folder'];?>
	
			</a>&nbsp;&nbsp;&nbsp;
			
			<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Revise_subfolder']);?>
" href="javascript:modifyfolder(this,'<?php echo $_smarty_tpl->tpl_vars['get_folder_id']->value;?>
')" class="coolbg" >
				<?php echo $_smarty_tpl->tpl_vars['file_manager']->value['Revise_folder'];?>
	
			</a>&nbsp;&nbsp;&nbsp;
			
			<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['del_subfolder']);?>
" href="javascript:delfolder(this,'<?php echo $_smarty_tpl->tpl_vars['get_folder_id']->value;?>
')" class="coolbg" >
			<?php echo $_smarty_tpl->tpl_vars['file_manager']->value['Delete_folder'];?>
	
			</a>&nbsp;&nbsp;&nbsp;
			<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['setterminal']);?>
" href="javascript:void(0)"  id="setcopytask" onclick="set_terminal(event)">
				<?php echo $_smarty_tpl->tpl_vars['file_manager']->value['setterminal'];?>

			</a>&nbsp;&nbsp;&nbsp;
		<!--	<a title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Voice_media']);?>
" href="trainmedia.php?folderid=<?php echo $_smarty_tpl->tpl_vars['get_folder_id']->value;?>
" class="coolbg" >
			<?php echo $_smarty_tpl->tpl_vars['file_manager']->value['Voice_media'];?>
	
			</a>&nbsp;&nbsp;&nbsp;	
		-->	
		<input type="hidden"  id="get_initid" name="get_initid" value=""/>
		</td>
	</tr>
</table>

<div id="copytask" class="r-displayVolume">
 <iframe style="position:absolute; width:150;height:110px;left:0px; top:0px;filter:alpha(opacity=0);-moz-opacity:0;border:0;z-index:-1"></iframe>
<div style="position:absolute;border:0;width:150; left:0px; top:0px; height:110px;z-index:100">
	<table border="0" cellpadding="10" cellspacing="0" width="150" style="background-color:#EEFFEE">
		
		<tr>
			<td nowrap="nowrap" align="right">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['media_id']);?>

			<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['media_id']);?>
" class="terminal_input_font" name="mediaid" type="text" id="mediaid"/>
			</td>
			</tr>
		<tr>
			<td nowrap="nowrap" align="center">
				<a href="javascript:void(0)" onclick="set_media_copy()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Sumbit']);?>

				</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="javascript:void(0)" onclick="disappear_task_div()"> 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['Cancel']);?>

				</a>
			</td>
		</tr>
		
	</table>
</div>
</div>


<?php echo '<script'; ?>
 language="javascript">

function addfolder(obj,get_folder_id)
{
	var sign = <?php echo $_smarty_tpl->tpl_vars['sign']->value;?>
;
	if(sign == 1)
	{
		window.location.href="filefolderadd.php?id="+get_folder_id+"";
	}
	else
	{
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['file_manager']->value['sign']);?>
');
	}	
}
	rights_control(<?php echo $_smarty_tpl->tpl_vars['is_right']->value;?>
,"<?php echo $_smarty_tpl->tpl_vars['admin_id']->value;?>
");

	if(<?php echo $_smarty_tpl->tpl_vars['userinfoid']->value;?>
==5)
	{
			var a_obj = document.getElementsByTagName("a");
			for(var i=0; i<a_obj.length; i++)
			{
				if(a_obj[i].name == "shiting"||a_obj[i].name == "xiazai"||a_obj[i].name == "allsel"||a_obj[i].name == "nosel"||a_obj[i].name == "delsel")
				{
					continue;
				}
	
				a_obj[i].href = "javascript:void(0)";
				a_obj[i].onclick = null;
				a_obj[i].style.color="#787878";
			}
	}

<?php echo '</script'; ?>
>

</tbody>
</form>	
<div class="link_style"><?php echo $_smarty_tpl->tpl_vars['pagestr']->value;?>
</div>
<!--  �� --> 
<form name="form3" target="_self" action="media_file.php?id=<?php echo $_smarty_tpl->tpl_vars['get_folder_id']->value;?>
&userinfoid=<?php echo $_smarty_tpl->tpl_vars['userinfoid']->value;?>
" method="post">
<table width='100%'  border='0' cellpadding='1' cellspacing='1' class="middle" align="center" style="margin-top:8px">
  <tr>
    <td background='skin/images/wbg.gif' align='center'>
      <table border='0' cellpadding='0' cellspacing='0'>
        <tr>
          <td width='90' align='center'><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Search_conditions'];?>
</td>
          <td width='160'>
          <select class="colors" name='searchkey'  id="searchkey" style='width:150'>
          	<option value=""><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Select_type'];?>
</option>
          	<option value='name'><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Media_Name'];?>
</option>
			<option value='typeid'><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Media_Types'];?>
</option>
          </select>        
		  </td>
        <td width='70'>
          <?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Keyword'];?>
        
		</td>
        <td width='160'>
          	<input type='text' name='searchvalue' id="searchvalue" value='' style='width:150px' /> </td>
        <td width='110'>
			<select class="colors" name='orderby' id="orderby" style='width:80px'>
			  <option value=''><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Sort'];?>
</option>
			  <option value="size"><?php echo $_smarty_tpl->tpl_vars['Searchform']->value['Media_Size'];?>
</option>
			</select>
		</td>
        <td>
          <input name="imageField" type="image" src="<?php echo $_smarty_tpl->tpl_vars['file_manager']->value['search_image'];?>
" width="45" height="20" border="0"/> 
		</td>
       </tr>
      </table>
    </td>
  </tr>
</table>
</form>
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
<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>
 
</body>
</html>

<?php }
}
