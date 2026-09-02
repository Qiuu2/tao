<?php
/* Smarty version 3.1.30, created on 2026-07-06 14:06:31
  from "/var/www/html/ok112/smarty/templates/servermanager_form.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a4b45e730e397_82735247',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '62f162bde6174fcc89ed51e8ee865cce0313df25' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/servermanager_form.html',
      1 => 1778116082,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a4b45e730e397_82735247 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<div class="server_div">
<table width="800px" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td>
		<img src="<?php echo $_smarty_tpl->tpl_vars['server_manager']->value['server_image'];?>
" />
	</td>
  </tr>
  <tr>
  	<td class="table_horizontal_line">	
	</td>
  </tr>
  <tr>
    <td align="center" valign="top">
		<div class="server_subdiv1">
			<table class="server_table" border="0">
				<tr>
					<td colspan="6" class="server_table_row1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Server_information']);?>

					</td>
				</tr>
				<tr>
					<td nowrap="nowrap" style="text-align:center; width:15%">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Server_status']);?>

					</td>
					<td style="text-align:center; width:15%"></td>
					
					<td nowrap="nowrap" style="text-align:center; width:15%">
						<?php if ($_smarty_tpl->tpl_vars['workstate']->value == 0) {?>
							<img src="../../skin/images/frame/stop.gif" width="20" height="20" alt="&lt;{$server_manager.Server_Termination}&gt;" align="absbottom"/>
						<?php } elseif ($_smarty_tpl->tpl_vars['workstate']->value == 1) {?>
							<img src="../../skin/images/frame/start.gif" width="20" height="20" alt="&lt;{$server_manager.Server_running}&gt;" align="absbottom"/>
						<?php }?>					</td>
					<td style="text-align:center; width:15%"></td>

					<td nowrap="nowrap" style="text-align:center; width:15%">
						<input type="button" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Restart_server']);?>
" name="restart_server" id="restart_server" onclick="restart_server()" class="server_restart_button"/>
						<?php if ($_smarty_tpl->tpl_vars['workstate']->value == 0) {?>
							<?php echo '<script'; ?>
>
								document.getElementById('restart_server').disabled = false;
							<?php echo '</script'; ?>
>	
						<?php } elseif ($_smarty_tpl->tpl_vars['workstate']->value == 1) {?>
							<?php echo '<script'; ?>
>
								//document.getElementById('restart_server').disabled = true;
							<?php echo '</script'; ?>
>	
						<?php }?>				  
					</td>
					
					<td style="text-align:center; width:15%"></td>
				</tr>
				
				<tr><td colspan="6" height="10px"></td></tr>
				
				<tr>
					<td style="text-align:center; width:15%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['linking_number']);?>
:<?php echo $_smarty_tpl->tpl_vars['currectconnectcount']->value;?>
</td>
		
					<td style="text-align:center; width:15%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Running_tasks']);?>
:<?php echo $_smarty_tpl->tpl_vars['taskcount']->value;?>
</td>
					
					<td style="text-align:center; width:15%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Current_Bandwidth']);?>
:<?php echo $_smarty_tpl->tpl_vars['currentbandwidth']->value;?>
KB/s</td>
					
					<td style="text-align:center; width:15%"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['version']);?>
:</td>
					
					<td style="text-align:left;  width:30%" align="left">
					<?php echo $_smarty_tpl->tpl_vars['version']->value;?>

					</td>
				</tr>
			</table>
		</div>
	</td>
  </tr>
  <tr>
  	<td align="center" valign="top">
	<form name="serverform" method="post" action="do.php?act=serveredit_msg&id=<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
" onsubmit="return checkform();">
		<div class="server_subdiv1">
			<table class="server_table">
				<tr>
					<td colspan="4" class="server_table_row1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Server_configuration']);?>

					</td>
				</tr>
				<tr>
					<td width="10%" nowrap="nowrap" class="server_table_col1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Server_IP']);?>
				
					</td>
					<td width="30%" nowrap="nowrap" class="server_table_col2">
						<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['inputServer_IP']);?>
" class="server_input" name="ip" type="text" id="ip" value="<?php echo $_smarty_tpl->tpl_vars['ip']->value;?>
" />
						<span id="ip_s"><font class="server_star" size="-1">*</font></span>				  
					</td>
					<td width="10%" nowrap="nowrap" class="server_table_col1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['HTTP_Interface']);?>
					
					</td>
					<td width="30%" nowrap="nowrap" class="server_table_col2">
						<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['inputHTTP_Interface']);?>
" class="server_input" name="port" type="text"  maxlength="4" id="port" readonly="readonly" value="<?php echo $_smarty_tpl->tpl_vars['port']->value;?>
"/>
						<span id="port_s"><font class="server_star" size="-1">*</font></span>				
					</td>
				</tr>
				<tr>
					<td width="10%" nowrap="nowrap" class="server_table_col1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['subnetmask']);?>

					</td>
					<td width="30%" nowrap="nowrap" class="server_table_col2">
					<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['mastersubnetmask']);?>
" class="server_input" type="text" id="mastersubnetmask"  name="mastersubnetmask" value="<?php echo $_smarty_tpl->tpl_vars['heartnetmaskip']->value;?>
" />
				   <span id="mastersubnetmasks"><font class="server_star" size="-1">*</font></span>	
						
					</td>
					<td width="10%" nowrap="nowrap" class="server_table_col1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['RTSP_interface']);?>
				
					</td>
					<td width="30%" nowrap="nowrap" class="server_table_col2">
						<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['inputRTSP_interface']);?>
" class="server_input" maxlength="4" name="udpport" type="text" id="udpport" readonly="readonly" value="<?php echo $_smarty_tpl->tpl_vars['udpport']->value;?>
"/>                      
					<span id="rtspport_s"><font class="server_star" size="-1">*</font></span>				  
					</td>
				</tr>		
				<tr>	
					<td width="10%" nowrap="nowrap" class="server_table_col1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['gateway']);?>
						
					</td>
					
					<td width="30%" nowrap="nowrap" class="server_table_col2">
			
						<input  class="server_input" name="gateway" type="text" id="gateway" value="<?php echo $_smarty_tpl->tpl_vars['gateway']->value;?>
" />
						<span id="gateway_s"><font class="server_star" size="-1">*</font></span>
					</td>
						<td width="10%" nowrap="nowrap" class="server_table_col1">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['date_port']);?>
			
					</td>
					<td width="30%" nowrap="nowrap" class="server_table_col2">
						<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['dateport']);?>
" class="server_input" type="text" id="dateport"  name="dateport" readonly="readonly" value="<?php echo $_smarty_tpl->tpl_vars['dateport']->value;?>
" />
							<span id="dateport_s"><font class="server_star" size="-1">*</font></span>				
				<!--
				 <span id="saveserverip"><font class="server_star" size="-1">*</font></span>
				 -->	
					</td>
				</tr>
				<tr id="max_bandwidth">
				<td width="10%" nowrap="nowrap" class="server_table_col1">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Maximum_bandwidth']);?>
					
					</td>
					<td width="30%" nowrap="nowrap" class="server_table_col2">
				<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['inputMaximum_bandwidth']);?>
" class="server_input" name="maxbandwidth" maxlength="10"  type="text" id="maxbandwidth" value="<?php echo $_smarty_tpl->tpl_vars['maxbandwidth']->value;?>
"/>
						<span id="maxbandwidth_s"><font class="server_star" size="-1">*(<?php echo $_smarty_tpl->tpl_vars['server_manager']->value['Units'];?>
：KB/s)</font></span>			 
					</td>
	
				<td  width="10%" nowrap="nowrap" class="server_table_col1">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Maximum_Connections']);?>
	
				
				</td>
				<td width="30%" nowrap="nowrap" class="server_table_col2" >
				<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['inputMaximum_Connections']);?>
" class="server_input" name="maxhttpconnections" maxlength="4" type="text" id="maxhttpconnections" value="<?php echo $_smarty_tpl->tpl_vars['maxhttpconnections']->value;?>
" />
						<span id="maxhttpconnections_s"><font class="server_star" size="-1">*</font></span>				   
				</td>
				</tr>
				<tr>
			
				<td  width="10%" nowrap="nowrap" class="server_table_col1">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['webport']);?>
		
				
				</td>
				<td width="30%" nowrap="nowrap" class="server_table_col2" >
				 <input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['webportdemo']);?>
" class="server_input" type="text" id="webport"  name="webport" readonly="readonly" value="<?php echo $_smarty_tpl->tpl_vars['listenport']->value;?>
" />
				<?php echo '<script'; ?>
 language="javascript">
				if(<?php echo $_smarty_tpl->tpl_vars['listenport']->value;?>
==1)
				{
					document.getElementById('webport').disabled="enable";
				}
				<?php echo '</script'; ?>
>
				 <span id="webport_s"><font class="server_star" size="-1">*</font></span>
				</td>
					<td width="10%" nowrap="nowrap" class="server_table_col1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['offline_port']);?>
				
					</td>
					<td width="30%" nowrap="nowrap" class="server_table_col2">
					<input  class="server_input" maxlength="4" name="offlineport" type="text" id="offlineport" readonly="readonly" value="<?php echo $_smarty_tpl->tpl_vars['offlineport']->value;?>
"/>                      
					<span id="offlinepport_s"><font class="server_star" size="-1">*</font></span>				  
					</td>
				</tr>
			
				<tr>
				<!--<td  width="10%" nowrap="nowrap" class="server_table_col1">
				<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['sdkaddr']);?>
		
				</td>
				<td width="30%" nowrap="nowrap" class="server_table_col2" >
				 <input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['sdkaddrdemo']);?>
" class="server_input" type="text" id="sdkaddr"  name="sdkaddr" value="<?php echo $_smarty_tpl->tpl_vars['sdkaddr']->value;?>
"/>
				<span id="sdkaddr_s"><font class="server_star" size="-1">*</font></span>	
				</td>-->
					<td width="10%" nowrap="nowrap" class="server_table_col1"><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['sdkport']);?>
					</td>
					<td width="30%" nowrap="nowrap" class="server_table_col2"><input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['sdkportdemo']);?>
" class="server_input" type="text" id="sdkport"  name="sdkport" readonly="readonly" value="<?php echo $_smarty_tpl->tpl_vars['sdkport']->value;?>
" />
                      <span id="sdkport_s"><font class="server_star" size="-1">*</font></span>					</td>
				</tr>
			
				<input type="hidden"  id="setwebport" name="setwebport" readonly="readonly" value=""/>
				<tr id='server_modes'>
					<!--<td width="10%" nowrap="nowrap" class="server_table_col1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['doublebackup']);?>
	
					</td>
					<td width="30%" nowrap="nowrap" class="server_table_col2" align="center">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['enablebackup']);?>
	
					<input name="servermodes" id="servermodes" type="radio" value=1  />	
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['disablebackup']);?>
	
					<input  name="servermodes"  type="radio" value=0 checked="checked"/>
					<?php echo '<script'; ?>
 language="javascript" defer="true">
					if(<?php echo $_smarty_tpl->tpl_vars['servermodes']->value;?>
 == 1)
					{
					  document.getElementsByName('servermodes')[0].checked = true;
					}
					else
					{
					   document.getElementsByName('servermodes')[1].checked = true;
					}
					<?php echo '</script'; ?>
>
					</td>
			
					<td width="10%" nowrap="nowrap" class="server_table_col1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Server_mode1s']);?>
	
					</td>
					<td width="30%" nowrap="nowrap" class="server_table_col2" align="center">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Server_mode1']);?>
	
					<input name="servermodes" id="servermodes" type="radio" value=1  />	
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Server_mode2']);?>
	
					<input  name="servermodes"  type="radio" value=0 checked="checked"/>
					<?php echo '<script'; ?>
 language="javascript" defer="true">
					if(<?php echo $_smarty_tpl->tpl_vars['servermodes']->value;?>
 == 1)
					{
						 document.getElementsByName('servermodes')[0].checked = true;
					 }
					 else
					 {
					  document.getElementsByName('servermodes')[1].checked = true;
					 }
					<?php echo '</script'; ?>
>
					</td>
					-->
				</tr>
			</table>
			<table class="server_table">
				<tr>
					<td colspan="4" style="text-align:center;padding:5px;">
						<input  type="submit" name="submit" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Sumbit']);?>
" class="server_button"/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="reset" name="reset" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Cancel']);?>
" class="server_button"/>
					</td>
				</tr>
		</table>
		</div>
		<div class="server_subdiv1">
		<table class="server_table"  id="server_tables">
			<tr>
				<td colspan="4" class="server_table_row1">
				
				<?php if ($_smarty_tpl->tpl_vars['registerflag']->value == 1 || $_smarty_tpl->tpl_vars['registerflag']->value == 2) {?>
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['machine_backup']);?>

				<?php } else { ?>
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['server_set']);?>

				<?php }?>
			
				</td>
				
			</tr>
				<tr>
					<td width="10%" nowrap="nowrap" id="mastip" class="server_table_col1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Master_IP']);?>
				
					</td>
					
					<td width="30%" nowrap="nowrap"  id="mast_ip" class="server_table_col2">
						<input  class="server_input" name="master_ip" type="text" id="master_ip" value="<?php echo $_smarty_tpl->tpl_vars['masterip']->value;?>
"/>
						<span id="master_s"><font class="server_star" size="-1">*</font></span>				  
					</td>
					
					<td width="10%" nowrap="nowrap" id="slaveip" class="server_table_col1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Slave_IP']);?>
				
					</td>
					
					<td width="30%" nowrap="nowrap" id="slave_ip" class="server_table_col2">
						<input  class="server_input" name="Slave_IP" type="text" id="Slave_IP" value="<?php echo $_smarty_tpl->tpl_vars['slaveip']->value;?>
" />
						<span id="slave_s"><font class="server_star" size="-1">*</font></span>				  
					</td>
				</tr>

				<tr>
					<td width="10%" nowrap="nowrap" class="server_table_col1">
					<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['subnetmask']);?>

					</td>
					
					<td width="30%" nowrap="nowrap" class="server_table_col2">
						<input  class="server_input" name="subnetmaskip" type="text" id="subnetmaskip" value="<?php echo $_smarty_tpl->tpl_vars['subnetmask']->value;?>
"/>
						<span id="mastersubnetmask_s"><font class="server_star" size="-1">*</font></span>				  
					</td>
					
					<td width="10%" nowrap="nowrap" class="server_table_col1">
							
					</td>
					
					<td width="30%" nowrap="nowrap"  class="server_table_col2">
								  
					</td>
				</tr>
				<tr>
					<td width="10%" nowrap="nowrap" id="mname" class="server_table_col1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['master_name']);?>
				
					</td>
					
					<td width="30%" nowrap="nowrap" id="m_name"  class="server_table_col2">
						<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['enter_server_name']);?>
" class="server_input" name="name" type="text" id="name" value="<?php echo $_smarty_tpl->tpl_vars['name']->value;?>
"/>
						<span id="name_s"><font class="server_star" size="-1">*</font></span>				  
					</td>
					<td width="10%" nowrap="nowrap" id="sname" class="server_table_col1">
						<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['slave_name']);?>
				
					</td>
					
					<td width="30%" nowrap="nowrap" id="s_name" class="server_table_col2">
						<input title="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['enter_server_name']);?>
" class="server_input" name="slavename" type="text" id="slavename" value="<?php echo $_smarty_tpl->tpl_vars['slavename']->value;?>
"/>
						<span id="slavename_s"><font class="server_star" size="-1">*</font></span>				  
					</td>
					
				</tr>
				<tr id="display_mode">
						<td width="10%" nowrap="nowrap" class="server_table_col1">
							<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Server_mode1s']);?>
				
						</td>
						<td width="30%" nowrap="nowrap" class="server_table_col2" align="center">
								
								<input name="servermodel" id="servermodel" type="radio" value=1 checked="checked" onClick="showdomodel(1);"/>	
								<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Master_server']);?>
&nbsp;&nbsp;&nbsp;
								<input  name="servermodel"  type="radio" value=2 onClick="showdomodel(2);"/>
								<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Slave_server']);?>
&nbsp;&nbsp;	
								<span id="textdemo"><font class="server_star" size="-1">
										<?php if ($_smarty_tpl->tpl_vars['backup']->value == 1 && $_smarty_tpl->tpl_vars['model']->value == 1) {?>
									(<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['zhuopen']);?>
)
									<?php } elseif ($_smarty_tpl->tpl_vars['backup']->value == 1 && $_smarty_tpl->tpl_vars['model']->value == 2) {?>
									(<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['beiopen']);?>
)
									<?php } elseif ($_smarty_tpl->tpl_vars['backup']->value == 0 && $_smarty_tpl->tpl_vars['model']->value == 2) {?>
									(<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['zhuclose']);?>
)
									<?php } elseif ($_smarty_tpl->tpl_vars['backup']->value == 0 && $_smarty_tpl->tpl_vars['model']->value == 1) {?>
									(<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['zhuclose']);?>
)
									<?php }?></font></span>		
								
							
							<?php echo '<script'; ?>
 language="javascript" defer="true">
								
							function showdomodel(flag)
							{
								if(flag==1)
								{
									document.getElementById('slave_s').innerHTML="<font class='server_star'></font>";
									document.getElementById('slaveip').style.color ="black";
									document.getElementById('sname').style.color ="black";
									document.getElementById('mastip').style.color ="red";
									document.getElementById('mname').style.color ="red";
								//	document.getElementById("default_setting").value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['del_log']);?>
";
									if(<?php echo $_smarty_tpl->tpl_vars['model']->value;?>
 == 1)
									{
										document.getElementById('master_ip').value="<?php echo $_smarty_tpl->tpl_vars['masterip']->value;?>
";
										document.getElementById('name').value="<?php echo $_smarty_tpl->tpl_vars['name']->value;?>
";	
										document.getElementById('Slave_IP').value="<?php echo $_smarty_tpl->tpl_vars['slaveip']->value;?>
";
										document.getElementById('slavename').value="<?php echo $_smarty_tpl->tpl_vars['slavename']->value;?>
";
									}	
									else
									{
										document.getElementById('master_ip').value="<?php echo $_smarty_tpl->tpl_vars['slaveip']->value;?>
";
										document.getElementById('name').value="<?php echo $_smarty_tpl->tpl_vars['slavename']->value;?>
";
										document.getElementById('Slave_IP').value="<?php echo $_smarty_tpl->tpl_vars['masterip']->value;?>
";
										document.getElementById('slavename').value="<?php echo $_smarty_tpl->tpl_vars['name']->value;?>
";
									}

									/*
									alert("<?php echo $_smarty_tpl->tpl_vars['slaveip']->value;?>
");
									if(document.getElementById('master_ip').value=="<?php echo $_smarty_tpl->tpl_vars['slaveip']->value;?>
")
									{
										document.getElementById('master_ip').value="<?php echo $_smarty_tpl->tpl_vars['masterip']->value;?>
";
										document.getElementById('name').value="<?php echo $_smarty_tpl->tpl_vars['name']->value;?>
";
										alert('1');
									}
								else
									{
									document.getElementById('master_ip').value="<?php echo $_smarty_tpl->tpl_vars['slaveip']->value;?>
";
									document.getElementById('name').value="<?php echo $_smarty_tpl->tpl_vars['slavename']->value;?>
";
									alert('2');
									}
								
									if(document.getElementById('Slave_IP').value=="<?php echo $_smarty_tpl->tpl_vars['masterip']->value;?>
")
									{
										document.getElementById('Slave_IP').value="<?php echo $_smarty_tpl->tpl_vars['slaveip']->value;?>
";
										document.getElementById('slavename').value="<?php echo $_smarty_tpl->tpl_vars['slavename']->value;?>
";
										alert('3');
									}
									else
									{
									document.getElementById('Slave_IP').value="<?php echo $_smarty_tpl->tpl_vars['masterip']->value;?>
";
									document.getElementById('slavename').value="<?php echo $_smarty_tpl->tpl_vars['name']->value;?>
";
									alert('4');
									}
									*/
								}
								else
								{
									document.getElementById('master_s').innerHTML="<font class='server_star'></font>";
									document.getElementById('slaveip').style.color ="red";
									document.getElementById('sname').style.color ="red";
									document.getElementById('mastip').style.color ="black";
									document.getElementById('mname').style.color ="black";

								//	document.getElementById("default_setting").value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['default_set']);?>
";
									if(<?php echo $_smarty_tpl->tpl_vars['model']->value;?>
 == 1)
									{
										document.getElementById('master_ip').value="<?php echo $_smarty_tpl->tpl_vars['slaveip']->value;?>
";
										document.getElementById('name').value="<?php echo $_smarty_tpl->tpl_vars['slavename']->value;?>
";
										document.getElementById('Slave_IP').value="<?php echo $_smarty_tpl->tpl_vars['masterip']->value;?>
";
										document.getElementById('slavename').value="<?php echo $_smarty_tpl->tpl_vars['name']->value;?>
";
									
									}	
									else
									{
										document.getElementById('master_ip').value="<?php echo $_smarty_tpl->tpl_vars['masterip']->value;?>
";
										document.getElementById('name').value="<?php echo $_smarty_tpl->tpl_vars['name']->value;?>
";	
										document.getElementById('Slave_IP').value="<?php echo $_smarty_tpl->tpl_vars['slaveip']->value;?>
";
										document.getElementById('slavename').value="<?php echo $_smarty_tpl->tpl_vars['slavename']->value;?>
";
									}

									/*
									if(document.getElementById('master_ip').value=="<?php echo $_smarty_tpl->tpl_vars['slaveip']->value;?>
")
									{
									//	document.getElementById('master_ip').value="<?php echo $_smarty_tpl->tpl_vars['masterip']->value;?>
";
									//	document.getElementById('name').value="<?php echo $_smarty_tpl->tpl_vars['name']->value;?>
";	
										alert('5');
									}
								else
									{
									document.getElementById('master_ip').value="<?php echo $_smarty_tpl->tpl_vars['slaveip']->value;?>
";
									document.getElementById('name').value="<?php echo $_smarty_tpl->tpl_vars['slavename']->value;?>
";
									alert('6');
									}
									
									if(document.getElementById('Slave_IP').value=="<?php echo $_smarty_tpl->tpl_vars['masterip']->value;?>
")
									{
								//	document.getElementById('Slave_IP').value="<?php echo $_smarty_tpl->tpl_vars['slaveip']->value;?>
";
								//	document.getElementById('slavename').value="<?php echo $_smarty_tpl->tpl_vars['slavename']->value;?>
";
									alert('7');
									}
								else
									{
									document.getElementById('Slave_IP').value="<?php echo $_smarty_tpl->tpl_vars['masterip']->value;?>
";
									document.getElementById('slavename').value="<?php echo $_smarty_tpl->tpl_vars['name']->value;?>
";
									alert('8');
									}
									*/	
								}
							}
							if(<?php echo $_smarty_tpl->tpl_vars['model']->value;?>
 == 1)
							{
							 	document.getElementsByName('servermodel')[0].checked = true;
								document.getElementById('mastip').style.color ="red";
								document.getElementById('mname').style.color ="red";
							//	document.getElementsByTagName('initdate').disabled=true;
							//	document.getElementById('master_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['address_do_net']);?>
</font>";
							}
							else
							{
							 	document.getElementsByName('servermodel')[1].checked = true;
								document.getElementById('slaveip').style.color ="red";
								document.getElementById('sname').style.color ="red";
							//	document.getElementsByTagName('initdate').disabled=false;
								//document.getElementById('slave_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['address_do_net']);?>
</font>";
							}
					
						<?php echo '</script'; ?>
>
						</td>			  
				
				</tr>
		</table>
		<table class="server_table">
				<tr>
					<td colspan="6" style="text-align:center;padding:5px;">
						<input  type="button" name="zhubeiopen" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['zhubeiopen']);?>
" id="zhubeiopen" onclick="serveropen_data()" class="server_restart_button"/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" name="zhubeiclose" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['zhubeiclose']);?>
" id="zhubeiclose" onclick="serverclose_data()" class="server_restart_button"/>
						
					<!--	<input type="button" name="initdate" value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['default_set']);?>
" name="default_setting" id="default_setting" onclick="init_date()" class="server_restart_button"/>-->
					</td>
				</tr>
		</table>
		</div>		
	</form>
	</td>
  </tr>
</table>
</div>
<?php if ($_smarty_tpl->tpl_vars['admin_id']->value == "administrator") {?>
	<?php echo '<script'; ?>
>
		var Version_Item = "<?php echo $_smarty_tpl->tpl_vars['masterip']->value;?>
";	
		var input_array = document.getElementsByTagName("input");
		
		for(var i=0; i<input_array.length; i++)
		{
			if(Version_Item==1)
			{
				
			}
			else
			{	
				if(input_array[i].name=="restart_server"||input_array[i].name=="ip"||input_array[i].name=="subnetmaskip"||input_array[i].name=="gateway"
				||input_array[i].name=="master_ip"||input_array[i].name=="Slave_IP"||input_array[i].name=="name"||input_array[i].name=="slavename"
				||input_array[i].name=="servermodel"||input_array[i].name=="submit"||input_array[i].name=="reset"||input_array[i].name=="initdate")
				{

				}
				else
				{
				//	input_array[i].disabled = true;
				}
			}
		}
	<?php echo '</script'; ?>
>
<?php } else { ?>
	<?php echo '<script'; ?>
>
		
	//	var input_array = document.all.tags("input");
	var input_array = document.getElementsByTagName("input");
		for(var i=0; i<input_array.length; i++)
		{
			input_array[i].disabled = true;
		}
	<?php echo '</script'; ?>
>
<?php }?>

<?php echo '<script'; ?>
 language="javascript">
	
if(document.getElementsByName('servermodel')[0].checked==true)
{
//	document.getElementsByTagName('initdate').disabled=true;
//	document.getElementById("default_setting").value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['del_log']);?>
";
}
else
{
//	document.getElementsByTagName('initdate').disabled=false;
//	document.getElementById("default_setting").value="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['default_set']);?>
";
}
	
var registerflag="<?php echo $_smarty_tpl->tpl_vars['registerflag']->value;?>
";

if(registerflag==1||registerflag==2)
{

}
else
{

document.getElementById("max_bandwidth").style.display="none";	
document.getElementById("server_modes").style.display="none";	
//document.getElementById("server_tables").style.display="none";	
//document.getElementById("default_setting").style.display="none";
	
document.getElementById("slaveip").style.display="none";	
document.getElementById("slave_ip").style.display="none";	
document.getElementById("display_mode").style.display="none";
document.getElementById("sname").style.display="none";
document.getElementById("s_name").style.display="none";
document.getElementById("mastip").style.align="left";
document.getElementById("mast_ip").style.align="left";
document.getElementById("mname").style.align="left"	
document.getElementById("m_name").style.align="left"	
}
<?php echo '</script'; ?>
>
<?php }
}
