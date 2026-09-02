<?php
/* Smarty version 3.1.30, created on 2026-05-26 15:41:23
  from "/var/www/html/ok112/smarty/templates/TerminalManager/led_sousuo.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a154ea3f278c4_61397748',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '1d867331a3543cbeb4532a1c0d0161c61204f43d' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/TerminalManager/led_sousuo.html',
      1 => 1778116102,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:TerminalManager/led_sousuo_from.html' => 1,
  ),
),false)) {
function content_6a154ea3f278c4_61397748 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>view_terminal_shotcut</title>
<link href="skin/css/main_page_style.css" rel="stylesheet" type="text/css"/>
<?php echo '<script'; ?>
 src="skin/js/frame/jzdd.js" type="text/javascript"><?php echo '</script'; ?>
>

<?php echo '<script'; ?>
 type="text/javascript">
function getCheckboxItem()
{
	var allSel="";
	if(document.form2.id.checked)
	{
		allSel=document.form2.id.value;
		if(allSel==undefined)
		allSel="";
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

function view_terminal(str)
{
	window.location.href = "displayterminal.php?key_id="+str+"";
}

function convert(value, dataType) 
{
	switch(dataType) 
	{
		case "int":
			return parseInt(value);
			break
		case "float":
			return parseFloat(value);
			break
		case "date":
			return Date.parse(value);
			break
		default:
			return value.toString();
	}
}

//sortȽַ
function compareCols(col, dataType) 
{
	return function compareTrs(tr1, tr2) 
	{
		value1 = convert(tr1.cells[col].innerHTML, dataType);
		value2 = convert(tr2.cells[col].innerHTML, dataType);
		if (value1 < value2) 
		{
			return -1;
		} 
		else if (value1 > value2) 
		{
	
			return 1;
		} 
		else 
		{
			return 0;
		}
	};
}

function sortTable(tableId, col, dataType) 
{
	var table = document.getElementById(tableId);
	var tbody = table.tBodies[0];
	var tr = tbody.rows; 
	var trValue = new Array();

	for (var i=0; i<tr.length; i++ ) 
	{
		trValue[i] = tr[i];  //иеϢ洢½
	}

	if ((tbody.sortCol == col)&&(dataType=='1')) 
	{
		trValue.reverse(); //ѾˣֱӶ䷴
	} 
	else 
	{
		trValue.sort(compareCols(col, dataType));  //
	}

	var fragment = document.createDocumentFragment();  //½һƬΣڱĽ
	for (var i=0; i<trValue.length; i++ ) 
	{
		fragment.appendChild(trValue[i]);
	}
	tbody.appendChild(fragment); //Ľ滻֮ǰֵ
	tbody.sortCol = col;
}

function del_terminal_shotcut()
{
	var terminal_id = "<?php echo $_smarty_tpl->tpl_vars['terminal_id']->value;?>
";
	var ledflag = "<?php echo $_smarty_tpl->tpl_vars['ledflag']->value;?>
";

		var getid=getCheckboxItem();
		if(getid==""||getid==null)
		{
			alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_task']);?>
");
		}
	else
	{
		if(!window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['Determine_remove']);?>
"))
		{
			return void(0);
		}
		else
		{

		window.location.href = "do.php?act=del_leddevice&ledflag="+ledflag+"&id="+getid+"&terminal_id="+terminal_id+"";
		}
	}
	
}

function updatechezhan()
{
	var terminal_id = "<?php echo $_smarty_tpl->tpl_vars['terminal_id']->value;?>
";
	var ledflag = "<?php echo $_smarty_tpl->tpl_vars['ledflag']->value;?>
";
		var getid=getCheckboxItem();
		if(getid==""||getid==null)
		{
			alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['select_task']);?>
");
		}
	else
	{
		if(!window.confirm("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['terminal_manager']->value['updatechezhan']);?>
"))
		{
			return void(0);
		}
		else
		{

		window.location.href = "do.php?act=updatechezhan&ledflag="+ledflag+"&id="+getid+"&terminal_id="+terminal_id+"";
		}
	}	
}

function set_terminalname()
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
		var devicename=document.getElementById('terminalname').value;
		var subterminal=document.getElementById('subterminalid').value;
		
		xmlhttp.open( "get","ledsetterminalname.php?id="+getid+"&devicename="+devicename+"&subterminalid="+subterminal+"",true);
	   xmlhttp.onreadystatechange = function()
	   {
		  if( xmlhttp.readyState == 4 )
		  {
			 if( xmlhttp.status == 200 )
			 {
					document.getElementById('change_volume').style.display = "none";
					alert('设置成功!');
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
</head>
<body>
<?php $_smarty_tpl->_subTemplateRender("file:TerminalManager/led_sousuo_from.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
 

</body>
</html>
<?php }
}
