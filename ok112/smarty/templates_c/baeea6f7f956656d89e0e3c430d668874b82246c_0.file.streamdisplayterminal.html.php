<?php
/* Smarty version 3.1.30, created on 2026-05-25 16:17:31
  from "/var/www/html/ok112/smarty/templates/zhaoshengManager/streamdisplayterminal.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a14059b9b68f8_22835846',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'baeea6f7f956656d89e0e3c430d668874b82246c' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/zhaoshengManager/streamdisplayterminal.html',
      1 => 1778116118,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:zhaoshengManager/streamdisplayterminal_form.html' => 1,
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a14059b9b68f8_22835846 (Smarty_Internal_Template $_smarty_tpl) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>DisplayTerminal</title>

<link href="skin/css/main_page_style.css" rel="stylesheet" type="text/css" />
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
  language="javascript">

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
	//Ա
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

		if ((tbody.sortCol == col)&&(col==1)) 
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
	
function getCheckboxItem()
{
	var allSel="";
	if(document.bellForm.id.checked)
	{
		allSel=document.bellForm.id.value;
		if(allSel==undefined)
		allSel="";
	}
	for(i=0;i<document.bellForm.id.length;i++)
	{
		if(document.bellForm.id[i].checked)
		{
			if(allSel=="")
			{
				allSel=document.bellForm.id[i].value;
			}
			else
			{
				allSel=allSel+","+document.bellForm.id[i].value;
			}	
		}
	}
	return allSel;
}

function selAll(aid)
{
	if(aid==0)
	{
		document.bellForm.id.checked=true;
	}
	for(i=0;i<document.bellForm.id.length;i++)
	{
		if(!document.bellForm.id[i].checked)
		{
			document.bellForm.id[i].checked=true;
		}
	}
}

function noSelAll(aid)
{
	if(aid==0)
	{
		document.bellForm.id.checked=false;
	}
	for(i=0;i<document.bellForm.id.length;i++)
	{
		if(document.bellForm.id[i].checked)
		{
			document.bellForm.id[i].checked=false;
		}
	}
}
<?php echo '</script'; ?>
>
</head>
<body>	
<?php $_smarty_tpl->_subTemplateRender("file:zhaoshengManager/streamdisplayterminal_form.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
 
<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

</body>
</html><?php }
}
