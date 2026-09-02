<?php
/* Smarty version 3.1.30, created on 2026-05-26 16:00:44
  from "/var/www/html/ok112/smarty/templates/offlinetask/offlinetask_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a15532c905f19_47760259',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '12e9a81b9cee2e5f1e1425cf58de344bfd25529c' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/offlinetask/offlinetask_form.html',
      1 => 1778116080,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a15532c905f19_47760259 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
if (!is_callable('smarty_modifier_truncate')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.truncate.php';
?>


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
		
		if (tbody.sortCol == col &&(dataType=='1')) 
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
	document.form2.id.checked=false;
	
	for(i=0;i<document.form2.id.length;i++)
	{
		if(document.form2.id[i].checked)
		{
			document.form2.id[i].checked=false;
		}
	}
}

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

function setofflinetask(flag)
{
	var getid=getCheckboxItem();
	if(getid==null||getid=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['select_broadcast_task']);?>
");
		
		return void(0);	
	}
	else
	{
		location="do.php?act=set_offline_tasks&getid="+getid+"&flag="+flag+"";
	}	
}

function selAll2(aid)
{
	if(aid==0)
	{
		document.form.id.checked=true;
	}
	for(i=0;i<document.form.id.length;i++)
	{
		if(!document.form.id[i].checked)
		{
			document.form.id[i].checked=true;
		}
	}
}

function noSelAll2(aid)
{
	document.form.id.checked=false;
	for(i=0;i<document.form.id.length;i++)
	{
		if(document.form.id[i].checked)
		{
			document.form.id[i].checked=false;
		}
	}
}

function getCheckboxItem2()
{
	var allSel="";

	if(document.form.id.checked)
	{
		allSel=document.form.id.value;
		if(allSel==undefined)
		{
			allSel="";
		}
	}
	for(i=0;i<document.form.id.length;i++)
	{
		if(document.form.id[i].checked)
		{
			if(allSel=="")
				allSel=document.form.id[i].value;
			else
				allSel=allSel+","+document.form.id[i].value;
		}
	}
	return allSel;
}

function setofflinetask2(flag)
{
	var getid=getCheckboxItem2();
	if(getid==null||getid=="")
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['select_broadcast_task']);?>
");
		return void(0);	
	}
	else
	{
		if(flag==1||flag==2)
		{
			location="do.php?act=do_offline_task&getid="+getid+"&flag="+flag+"";
			djdd(); 
		}
		else if(flag==4||flag==5)
		{
			if(window.confirm('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['confirm_deleting']);?>
'))
			{
				location="do.php?act=set_offline_tasks&getid="+getid+"&flag="+flag+"";
				djdd();
			}
		}
		else if(flag==18)
		{
			if(window.confirm('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['confirm_clear']);?>
'))
			{
				location="do.php?act=set_offline_tasks&getid="+getid+"&flag="+flag+"";
				djdd();
			}
		}
		else
		{
		  location="do.php?act=set_offline_tasks&getid="+getid+"&flag="+flag+"";
		   djdd(); 
		}
	}	
}

function getdayofweek(str)
{
   var dayofweek="";
   var count=0;
   for(i=0;i<str.length;i++)
   {
        if(str.charAt(i)=="1")
        {
			count++;
            switch(i)
            {
                case 0:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Sunday'];?>
";
                
                break;
                case 1:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Monday'];?>
";
                
                break;
                case 2:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Tuesday'];?>
";
                
                break;
                case 3:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Wednesday'];?>
";
                
                break;
                case 4:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Thursday'];?>
";
                
                break;
                case 5:
				dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Friday'];?>
";
                
                break;
                case 6:
                dayofweek+="<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Saturday'];?>
";
                break;  
            }
        }
   }
   if(count==7)
   {
   		return "<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['every_day']);?>
";
   }
   return dayofweek;
}
<?php echo '</script'; ?>
>



<form name="form" id="form" class="terminal_form_to_body" style="width:99%">
  <tbody>
<div id="divTest2" style="width:102%;overflow-x:scroll;overflow-y:scroll">
<table width="100%" border="0" cellpadding="1" cellspacing="1"  align="left" id= "tasklist2" style="font-size:12px">
	<thead> 
    <tr align='center' class="terminal_table_row_bg">
	
		<th width="3%" nowrap>
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Select']);?>

		</th>          	
		<th width="5%" nowrap onclick="sortTable('tasklist2', 1,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Task_name']);?>

		</th> 
		<th width="5%" nowrap onclick="sortTable('tasklist2', 3,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['The_category']);?>

		</th>  
		<th width="5%" nowrap onclick="sortTable('tasklist2', 2,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Play_Cycle']);?>

		</th>  
		   
		<th width="5%" nowrap onclick="sortTable('tasklist2', 3,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Start_Date']);?>

		</th>   

		<th width="5%" nowrap onclick="sortTable('tasklist2', 4,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['End_Date']);?>

		</th>  
		
		<th width="5%" nowrap onclick="sortTable('tasklist2', 5,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Run_Time']);?>

		</th>	
		          	    
		<th width="5%" nowrap onclick="sortTable('tasklist2', 6,'1')" class="sort_data_table_sequence"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Duration']);?>
↑↓</th>
		
		<th width="5%" nowrap onclick="sortTable('tasklist2', 7,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Status']);?>

		</th>
		<th width="5%" nowrap onclick="sortTable('tasklist2', 8,'1')" class="sort_data_table_sequence">
			<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['offlinestate']);?>

		</th>
		<th width="5%" nowrap><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Terminal_Attribute']);?>
</th> 
    </tr>  
  </thead>

	<?php if (count($_smarty_tpl->tpl_vars['info2']->value) != 0) {?> 
	<?php
$__section_loop_0_saved = isset($_smarty_tpl->tpl_vars['__smarty_section_loop']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop'] : false;
$__section_loop_0_loop = (is_array(@$_loop=$_smarty_tpl->tpl_vars['info2']->value) ? count($_loop) : max(0, (int) $_loop));
$__section_loop_0_total = $__section_loop_0_loop;
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = new Smarty_Variable(array());
if ($__section_loop_0_total != 0) {
for ($__section_loop_0_iteration = 1, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] = 0; $__section_loop_0_iteration <= $__section_loop_0_total; $__section_loop_0_iteration++, $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']++){
?>            
	<tr align="center">
		<td nowrap="nowrap">
			<?php if ($_smarty_tpl->tpl_vars['user_rights']->value == 1 || $_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>
				<input name="id" type="checkbox" id="id" value="<?php echo $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
">
			<?php } else { ?>
				<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)+1+$_smarty_tpl->tpl_vars['start']->value;?>

			<?php }?>
		</td>		
		<td nowrap="nowrap">
			<label title="<?php echo $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskname'];?>
"><?php echo smarty_modifier_truncate($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskname'],30,"..");?>
</label>
		</td> 
		<td nowrap="nowrap">
				<?php if ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 1) {?>
				 
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['oneache']);?>
(<?php echo $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['info'];?>
)
			
				<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 2 || $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 7) {?>		   	
			
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['twofile']);?>

			
				<?php }?>
		</td> 
		<td nowrap="nowrap">
			<?php if ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 7) {?>
				<span style="color:#FF0000" id="emergency_mark" name="emergency_mark"><?php echo $_smarty_tpl->tpl_vars['task_manager']->value['Emergency_Broadcast'];?>
</span>
				
			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 2 || $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['tasktype'] == 1) {?>
			
				<?php if ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'] == "0000000") {?>
				
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Manual']);?>

				
				<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'] != "0000000") {?>
				
					<?php echo '<script'; ?>
 language="javascript">document.write(getdayofweek("<?php echo $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['exemodel'];?>
"))<?php echo '</script'; ?>
> 
				
				<?php }?>
			<?php }?>
		</td>
		    
		<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['startdate'];?>
 </td>              	
		<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['enddate'];?>
 </td> 
		
		<td nowrap="nowrap"><?php echo $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['playtime'];?>
 </td>   
		
		<td nowrap="nowrap">
		
		
				<?php echo '<script'; ?>
 language="javascript">
				var gethour;
				var getmin;
				var getsen;
				var gettime=<?php echo $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['timelength'];?>
;
				var gettimetype=<?php echo $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['timelengthtype'];?>
;
				if(gettimetype==1)
				{
					gethour=parseInt(gettime/3600);
					getmin=parseInt((gettime-gethour*3600)/60);
					getsen=gettime-gethour*3600-getmin*60;
					if(gethour>0)
						document.write(gethour,'<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['hour'];?>
');
					if(getmin>0)
						document.write(getmin,'<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['min'];?>
');
					if(getsen>0)
						document.write(getsen,'<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['sec'];?>
');
				}
			else
				document.write(gettime,'<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Times'];?>
');
				<?php echo '</script'; ?>
>
		

		</td>
		
		<td nowrap="nowrap">
			<?php if ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 0 && $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['projectstate'] == 0) {?>	
			 <span style="color:#ff0000;">
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Enable'];?>
●</span>
			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 0 && $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['projectstate'] == 1) {?>	
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Disable'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 3) {?>  
			<span style="color:#0f6b24;">      
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Execution'];?>
  </span> 
			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 2) {?> 
				<span style="color:#0f6b24;">    
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Pause'];?>
  </span> 
			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 1) {?>
				<span style="color:#0f6b24;"> 
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Execution'];?>
  </span>
			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['state'] == 5) {?>
			<span style="color:#0f6b24;"> 
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['Execution'];?>
 </span>
			<?php }?> 
		
		</td>
		
			<td nowrap="nowrap">
			<?php if ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['offlinestate'] == 0) {?>	
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['no_offline'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['offlinestate'] == 1) {?>	
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['free_offline'];?>

			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['offlinestate'] == 2) {?>      
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['now_offline'];?>
 
			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['offlinestate'] == 3) {?> 
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['finlish_offline'];?>
 
			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['offlinestate'] == 4) {?>
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['free_delete'];?>
 
			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['offlinestate'] == 5) {?>
				<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['now_delete'];?>
 
			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['offlinestate'] == 6) {?>
			<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['doing_ofline'];?>
 
				<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['offlinestate'] == 7) {?>
			<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['doing_ofline2'];?>
 
				<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['offlinestate'] == 8) {?>
			<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['delfinlish'];?>
 
				<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['offlinestate'] == 9) {?>
			<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['pre_free_offline'];?>
 
			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['offlinestate'] == 10) {?>
			<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['pre_doing_offline'];?>
 
				<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['offlinestate'] == 11) {?>
			<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['offline_stop'];?>
 
			<?php } elseif ($_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['offlinestate'] == 12) {?>
			<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['offline_stoped'];?>

			<?php } else { ?> 
			<?php echo $_smarty_tpl->tpl_vars['Filetaskmanager']->value['no_offline'];?>

			<?php }?> 
		</td>
		
		<td nowrap="nowrap">
			<a  name="link_view" id="link_view" href="displayterminal.php?flag=<?php echo $_smarty_tpl->tpl_vars['getflag']->value;?>
&term_id=<?php echo $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Terminal']);?>

			</a>

			<a  name="link_look" id="link_look" href="displaymedia.php?flag=<?php echo $_smarty_tpl->tpl_vars['getflag']->value;?>
&id=<?php echo $_smarty_tpl->tpl_vars['info2']->value[(isset($_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_section_loop']->value['index'] : null)]['taskid'];?>
">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['task_manager']->value['Media']);?>

			</a> 
		</td>
	</tr>
<?php
}
}
if ($__section_loop_0_saved) {
$_smarty_tpl->tpl_vars['__smarty_section_loop'] = $__section_loop_0_saved;
}
} else {
}?>

</table>
</div>
</tbody>

<table width="100%" border="0" cellpadding="1" cellspacing="1" align="left" style="font-size:12px">
<tr>
<td>
<a  href="javascript:selAll2(0)" >
	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Filetaskmanager']->value['Select_All']);?>

</a>&nbsp;
<a  href="javascript:noSelAll2(0)" >
	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Filetaskmanager']->value['Cancel']);?>

</a>&nbsp;

	<?php if ($_smarty_tpl->tpl_vars['getflag']->value == 1) {?>
	<a href="javascript:setofflinetask2(1)" > 
	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Filetaskmanager']->value['free_offline']);?>

</a>&nbsp;

<a href="javascript:setofflinetask2(2)"> 
	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Filetaskmanager']->value['now_offline']);?>

</a>&nbsp;		 	
	<?php } elseif ($_smarty_tpl->tpl_vars['getflag']->value == 2) {?>		   	
	<a href="javascript:setofflinetask2(14)"> 
	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Filetaskmanager']->value['free_offline']);?>

</a>&nbsp;

<a href="javascript:setofflinetask2(15)" > 
	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Filetaskmanager']->value['now_offline']);?>

</a>&nbsp;
	<a href="javascript:setofflinetask2(4)" > 
	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Filetaskmanager']->value['free_delete']);?>

</a>&nbsp;
<a href="javascript:setofflinetask2(5)" > 
	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Filetaskmanager']->value['now_delete']);?>

</a>&nbsp;

<a href="javascript:setofflinetask2(11)"> 
	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Filetaskmanager']->value['offline_stop']);?>

</a>&nbsp;

<a href="javascript:setofflinetask2(16)"> 
	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Filetaskmanager']->value['offline_play']);?>

</a>&nbsp;

<a href="javascript:setofflinetask2(17)" > 
	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Filetaskmanager']->value['offlineplay_stop']);?>

</a>&nbsp;

<a href="javascript:setofflinetask2(18)"> 
	<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['Filetaskmanager']->value['delofflinemusic']);?>

</a>&nbsp;			
	<?php }?>



</td>
</tr>
</table>
</form>
<?php if ($_smarty_tpl->tpl_vars['user_rights']->value == 1) {?>

<!--什么也不做-->
<?php } else {
echo '<script'; ?>
>

	var input_objs = document.getElementsByTagName("a");
	for(var i=0; i< input_objs.length; i++)
	{
		input_objs[i].href = "javascript:void(0)";
		input_objs[i].onclick = null;
		input_objs[i].style.color="#787878";
	}
	
<?php echo '</script'; ?>
>

<?php }
echo '<script'; ?>
 language="javascript">

var obj2 = document.getElementById( "divTest2").offsetHeight; 
 if(obj2>=550)
 {
   document.getElementById("divTest2").style.height=550+"px"; 
 }
 else
 {
   document.getElementById("divTest2").style.height=document.getElementById( "divTest2").offsetHeight;
 }

<?php echo '</script'; ?>
><?php }
}
