<?php
/* Smarty version 3.1.30, created on 2026-07-06 14:06:31
  from "/var/www/html/ok112/smarty/templates/servermanager.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a4b45e72e01c4_44202615',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'fbe51bc5039e472b96f5119c13ecfa765608f110' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/servermanager.html',
      1 => 1778116082,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:servermanager_form.html' => 1,
    'file:language/".((string)$_smarty_tpl->tpl_vars[\'language\']->value)."_foot.php' => 1,
  ),
),false)) {
function content_6a4b45e72e01c4_44202615 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>ServerManager</title>

<link href="skin/css/main_page_style.css" rel="stylesheet" type="text/css"/>

<?php echo '<script'; ?>
 type="text/javascript">
function trim(str)
{
   str=str.replace(/(^\s*)|(\s*$)/g,""); 
   return str;
}

function isNull( str )
{
	if ( str == "" || str==null) 
	return true;
	var regu = "^[ ]+$";
	var re = new RegExp(regu);
	return re.test(str);
}
function isIP(strIP) 
{ 
	if (isNull(strIP)) 
	return false; 
	var re=/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/g; 
	if(re.test(strIP)) 
	{ 
		if(RegExp.$1 <256 & RegExp.$2<256 && RegExp.$3<256 && RegExp.$4<256)
		return true; 
	} 
	return false; 
} 

function isIPduan(strIP,Ip,netmask,slaveip) 
{ 
	var IP_1 = strIP.split(".");
	var IP_1_0=parseInt(IP_1[0]);
	var IP_1_1=parseInt(IP_1[1]);
	var IP_1_2=parseInt(IP_1[2]);
	var IP_2 = Ip.split(".");
	var IP_2_0=parseInt(IP_2[0]);
	var IP_2_1=parseInt(IP_2[1]);
	var IP_2_2=parseInt(IP_2[2]);
	var netmask_1 = netmask.split(".");
	var netmask_1_0=parseInt(netmask_1[0]);
	var netmask_1_1=parseInt(netmask_1[1]);
	var netmask_1_2=parseInt(netmask_1[2]);
	var slaveip_0 = slaveip.split(".");
	var slaveip_0_0=parseInt(slaveip_0[0]);
	var slaveip_0_1=parseInt(slaveip_0[1]);
	var slaveip_0_2=parseInt(slaveip_0[2]);
	var m=0,n=0;
	if(netmask_1_0!=0)
	{
		m++;
		if((IP_1_0&netmask_1_0)==(IP_2_0&netmask_1_0))
		{
			n++;
		}
		else
		{
		
		}
		if(IP_1_0==slaveip_0_0)
		{
		
		}
		else
		{
			document.getElementById('slave_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['address_same_net']);?>
</font>";
			document.getElementById('Slave_IP').select();
			document.serverform.Slave_IP.focus();
			return false; 
		}
	}
	if(netmask_1_1!=0)
	{
		m++;
		if((IP_1_1&netmask_1_1)==(IP_2_1&netmask_1_1))
		{
			n++;
		}
		else
		{
		
		}
		if(IP_1_1==slaveip_0_1)
		{
		
		}
		else
		{
			document.getElementById('slave_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['address_same_net']);?>
</font>";
			document.getElementById('Slave_IP').select();
			document.serverform.Slave_IP.focus();
			return false; 
		}
	}
	
	if(netmask_1_2!=0)
	{
		m++;
		if((IP_1_2&netmask_1_2)==(IP_2_2&netmask_1_2))
		{
			n++;
		}
		else
		{
		
		}
		
		if(IP_1_2==slaveip_0_2)
		{
		
		}
		else
		{
			document.getElementById('slave_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['address_same_net']);?>
</font>";
			document.getElementById('Slave_IP').select();
			document.serverform.Slave_IP.focus();
			return false; 
		}
	}
	
	if(m==n)
	{
		document.getElementById('master_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['address_not_net']);?>
</font>";
		document.getElementById('master_ip').select();
		document.serverform.master_ip.focus();
		return false; 
	}	
	
	return true; 
} 

function isChinaOrNumbOrLett(s)
{ 
	var regu = "^[0-9a-z]+$"; 
	var re = new RegExp(regu); 
	if (re.test(s)) 
	{ 
		return true; 
	}
	else
	{ 
		return false; 
	} 
} 


function isNumber(s)
{ 
	var regu = "^[0-9]+$"; 
	var re=new RegExp(regu);
	if(re.test(s))
	{
		return true;
	}
	else
	{
		return false;
	}
}

function isPort( str )
{ 
    return (isNumber(str) & str<65536); 
}

function pathverify(str)
{
	var regu = "(^\\.|^/|^[a-zA-Z])?:?/.+(/$)?"; 
	var re=new RegExp(regu);
	if(re.test(str))
	{
		return true;
	}
	else
	{
		return false;
	}
}

function checkform()
{
	

	//验证ip
	if(isNull(document.serverform.ip.value))
	{
		document.getElementById('ip_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['enter_IP_address']);?>
</font>";
		document.serverform.ip.focus();
		return false;
	}
	else
	{
		if(!isIP(document.serverform.ip.value))
		{
			document.getElementById('ip_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['address_format_error']);?>
</font>";
			document.getElementById('ip').select();
			document.serverform.ip.focus();
			return false;
		}
	}
	document.getElementById('ip_s').innerHTML="<font class='server_star'></font>";



	//验证主服务器地址
	if(isNull(document.serverform.dateport.value))
	{
		document.getElementById('dateport_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Only_number']);?>
</font>";
		document.serverform.dateport.focus();
		return false;
	}
	else
	{
		if(!isPort(document.serverform.dateport.value))
		{
			document.getElementById('dateport_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Port_error']);?>
</font>";
			document.getElementById('dateport').select();
			document.serverform.dateport.focus();
			return false;
		}
	}
	document.getElementById('dateport_s').innerHTML="<font class='server_star'></font>";

	if(isNull(document.serverform.webport.value))
	{
		document.getElementById('webport_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Only_number']);?>
</font>";
		document.serverform.webport.focus();
		return false;
	}
	else
	{
		if(!isPort(document.serverform.webport.value))
		{
			document.getElementById('webport_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Port_error']);?>
</font>";
			document.serverform.webport.focus();
			
			return false;
		}
		document.getElementById('setwebport').value=trim(document.getElementById('webport').value);
	}
		
	document.getElementById('webport_s').innerHTML="<font class='server_star'></font>";
	
	if(isNull(document.serverform.sdkport.value))
	{
		document.getElementById('sdkport_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Only_number']);?>
</font>";
		document.serverform.sdkport.focus();
		return false;
	}
	else
	{
		if(!isPort(document.serverform.sdkport.value))
		{
			document.getElementById('sdkport_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Port_error']);?>
</font>";
			document.serverform.sdkport.focus();
			return false;
		}
	}
		
	document.getElementById('sdkport_s').innerHTML="<font class='server_star'></font>";

	/*//sdk地址
	if(isNull(document.serverform.sdkaddr.value))
	{
		document.getElementById('sdkaddr_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['inputSalve_Server']);?>
</font>";
		document.serverform.sdkaddr.focus();
		return false;
	}
	else
	{
		if(!isIP(document.serverform.sdkaddr.value))
		{
			document.getElementById('sdkaddr_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['address_format_error']);?>
</font>";
			document.getElementById('sdkaddr').select();
			document.serverform.sdkaddr.focus();
			return false;
		}
	}
	
	document.getElementById('sdkaddr_s').innerHTML="<font class='server_star'></font>";*/
	//验证heartbeat子网掩码

	if(isNull(document.serverform.mastersubnetmask.value))
	{
		document.getElementById('mastersubnetmasks').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['inputSalve_Server']);?>
</font>";
		document.serverform.mastersubnetmask.focus();
		return false;
	}
	else
	{
		if(!isIP(document.serverform.mastersubnetmask.value))
		{
			document.getElementById('mastersubnetmasks').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['address_format_error']);?>
</font>";
			document.getElementById('mastersubnetmask').select();
			document.serverform.mastersubnetmask.focus();
			return false;
		}
	}
	document.getElementById('mastersubnetmasks').innerHTML="<font class='server_star'></font>";

//验证heartbeat网关
	



	//验证网关

	if(isNull(document.serverform.gateway.value))
	{
	//	document.getElementById('gateway_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['enter_gateway_address']);?>
</font>";
	//	document.serverform.gateway.focus();
		//return false;
	}
	else
	{
		if(!isIP(document.serverform.gateway.value))
		{
			document.getElementById('gateway_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['address_format_error']);?>
</font>";
			document.getElementById('gateway').select();
			document.serverform.gateway.focus();
			return false;
		}
	}
	document.getElementById('gateway_s').innerHTML="<font class='server_star'></font>";

	
	//验证端口
	if(isNull(document.serverform.udpport.value))
	{
		document.getElementById('rtspport_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['enter_port']);?>
</font>";
		document.serverform.udpport.focus();
		return false;
	}
	else
	{
		if(!isNumber(trim(document.getElementById('udpport').value) ) )
		{
			document.getElementById('rtspport_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Only_number']);?>
</font>";
			document.getElementById('udpport').select();
			document.serverform.udpport.focus();
			return false;
		}
		
		if(!isPort(document.serverform.udpport.value))
		{
			document.getElementById('rtspport_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Port_error']);?>
</font>";
			document.getElementById('udpport').select();
			document.serverform.udpport.focus();
			return false;
		}
	}
	document.getElementById('rtspport_s').innerHTML="<font class='server_star'></font>";
	//验证连接数是否是数字
	if(isNull(document.serverform.maxhttpconnections.value))
	{
		document.getElementById('maxhttpconnections_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['max_connections']);?>
</font>";
		document.serverform.maxhttpconnections.focus();
		return false;
	}
	else
	{
		if(!isNumber(document.serverform.maxhttpconnections.value))
		{
			document.getElementById('maxhttpconnections_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Only_number']);?>
</font>";
			document.getElementById('maxhttpconnections').select();
			document.serverform.maxhttpconnections.focus();
			return false;
		}
	}
	document.getElementById('maxhttpconnections_s').innerHTML="<font class='server_star'></font>";
	//验证连接端口	
	if(isNull(document.serverform.port.value))
	{
		document.getElementById('port_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['enter_port']);?>
</font>";
		document.serverform.port.focus();
		return false;
	}
	else
	{
		if( !isNumber( trim(document.getElementById('port').value) ) )
		{
			document.getElementById('port_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Only_number']);?>
</font>";
			document.getElementById('port').select();
			document.serverform.udpport.focus();
			return false;
		}
	
		if(!isPort(document.serverform.port.value))
		{
			document.getElementById('port_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Port_error']);?>
</font>";
			document.getElementById('port').select();
			document.serverform.port.focus();
			return false;
		}
	}
	document.getElementById('port_s').innerHTML="<font class='server_star'></font>";
	//验证最大连接最大带宽
	if(isNull(document.serverform.maxbandwidth.value))
	{
		document.getElementById('maxbandwidth_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['enter_bandwidth']);?>
</font>";
		document.serverform.port.focus();
		return false;
	}
	else
	{
		if(!isNumber(document.serverform.maxbandwidth.value))
		{
			document.getElementById('maxbandwidth_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Only_number']);?>
</font>";
			document.getElementById('maxbandwidth').select();
			document.serverform.port.focus();
			return false;
		}
	}
	document.getElementById('maxbandwidth_s').innerHTML="<font class='server_star'></font>";
	//验证离线端口
	if(isNull(document.serverform.offlineport.value))
	{
		document.getElementById('offlinepport_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['poffline_port']);?>
</font>";
		document.serverform.offlineport.focus();
		return false;
	}
	else
	{
		if(!isNumber(document.serverform.offlineport.value))
		{
			document.getElementById('offlinepport_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['Only_number']);?>
</font>";
			document.getElementById('offlineport').select();
			document.serverform.offlineport.focus();
			return false;
		}
	}
	document.getElementById('offlinepport_s').innerHTML="<font class='server_star'></font>";
  return true;
}


function zhubeicheck()
{
	//验证子网掩码
	if(isNull(document.serverform.subnetmaskip.value))
	{
		document.getElementById('mastersubnetmask_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['inputSalve_Server']);?>
</font>";
		document.serverform.subnetmaskip.focus();
		return false;
	}
	else
	{
		if(!isIP(document.serverform.subnetmaskip.value))
		{
			document.getElementById('mastersubnetmask_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['address_format_error']);?>
</font>";
			document.getElementById('subnetmaskip').select();
			document.serverform.subnetmaskip.focus();
			return false;
		}
	}
	document.getElementById('mastersubnetmask_s').innerHTML="<font class='server_star'></font>";	
//验证主服务器名
if(isNull(document.serverform.name.value))
	{  
		document.getElementById('name_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['enter_server_name']);?>
</font>";
		document.serverform.name.focus();	
		return false;
	}
	else
	{
		if(!isChinaOrNumbOrLett(document.serverform.name.value))
		{
			document.getElementById('name_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['letter_number_Chinese']);?>
</font>";
			document.getElementById('name').select();
			document.serverform.name.focus();
			return false;
		}
	}
	document.getElementById('name_s').innerHTML="<font class='server_star'></font>";

	//验证子服务器名
	if(isNull(document.serverform.slavename.value))
	{  
		document.getElementById('slavename_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['enter_server_name']);?>
</font>";
		document.serverform.slavename.focus();	
		return false;
	}
	else
	{
		if(!isChinaOrNumbOrLett(document.serverform.slavename.value))
		{
			document.getElementById('slavename_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['letter_number_Chinese']);?>
</font>";
			document.getElementById('slavename').select();
			document.serverform.slavename.focus();
			return false;
		}
	}
	document.getElementById('slavename_s').innerHTML="<font class='server_star'></font>";

	//验证主服务器ip
	if(isNull(document.serverform.master_ip.value))
	{
		document.getElementById('master_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['enter_IP_address']);?>
</font>";
		document.serverform.master_ip.focus();
		return false;
	}
	else
	{
		if(!isIP(document.serverform.master_ip.value))
		{
			document.getElementById('master_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['address_format_error']);?>
</font>";
			document.getElementById('master_ip').select();
			document.serverform.master_ip.focus();
			return false;
		}
	}

	document.getElementById('master_s').innerHTML="<font class='server_star'></font>";
	//验证子服务器ip
	if(isNull(document.serverform.Slave_IP.value))
	{
		document.getElementById('slave_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['enter_IP_address']);?>
</font>";
		document.serverform.Slave_IP.focus();
		return false;
	}
	else
	{
		if(!isIP(document.serverform.Slave_IP.value))
		{
			document.getElementById('slave_s').innerHTML="<font class='server_star'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['server_manager']->value['address_format_error']);?>
</font>";
			document.getElementById('Slave_IP').select();
			document.serverform.Slave_IP.focus();
			return false;
		}

	/*	
		if(!isIPduan(document.serverform.master_ip.value,document.serverform.ip.value,document.serverform.subnetmaskip.value,document.serverform.Slave_IP.value))
		{	

			return false;
		}
		*/
	}
	document.getElementById('slave_s').innerHTML="<font class='server_star'></font>";
}

function serveropen_data()
{
	var server_model=1;
	if(false==zhubeicheck())
		return false;
	var getip=document.serverform.ip.value;
	var subnetmaskip= document.serverform.subnetmaskip.value;
	var servername=document.serverform.name.value;
	var master_ip= document.serverform.master_ip.value;
	var slavename= document.serverform.slavename.value;
	var Slave_IP= document.serverform.Slave_IP.value;
	var mastersubnetmask= document.serverform.mastersubnetmask.value;
	var gateway= document.serverform.gateway.value;
	if(document.getElementsByName('servermodel')[0].checked==true)
	{
	server_model=1;
	}
	else
	{
 	server_model=2;
	}
	if(window.confirm("<?php echo $_smarty_tpl->tpl_vars['server_manager']->value['zhu_init_open'];?>
") == false)
		return void(0);
	else
	window.location.href = "do.php?act=serveropen_data&servermodel="+server_model+"&ip="+getip+"&subnetmaskip="+subnetmaskip+"&name="+servername+"&master_ip="+master_ip+"&slavename="+slavename+"&Slave_IP="+Slave_IP+"&mastersubnetmask="+mastersubnetmask+"&gateway="+gateway;
}

function serverclose_data()
{
	var server_model=1;
	if(false==zhubeicheck())
		return false;
	var getip=document.serverform.ip.value;
	var subnetmaskip= document.serverform.subnetmaskip.value;
	var servername=document.serverform.name.value;
	var master_ip= document.serverform.master_ip.value;
	var slavename= document.serverform.slavename.value;
	var Slave_IP= document.serverform.Slave_IP.value;
	var mastersubnetmask= document.serverform.mastersubnetmask.value;
	var gateway= document.serverform.gateway.value;
	if(document.getElementsByName('servermodel')[0].checked==true)
	{
	server_model=1;
	}
	else
	{
 	server_model=2;
	}

	if(window.confirm("<?php echo $_smarty_tpl->tpl_vars['server_manager']->value['zhu_init_close'];?>
") == false)
		return void(0);
	else
	window.location.href = "do.php?act=serverclose_data&servermodel="+server_model+"&ip="+getip+"&subnetmaskip="+subnetmaskip+"&name="+servername+"&master_ip="+master_ip+"&slavename="+slavename+"&Slave_IP="+Slave_IP+"&mastersubnetmask="+mastersubnetmask+"&gateway="+gateway;

}


function restart_server()
{
	if(window.confirm("<?php echo $_smarty_tpl->tpl_vars['server_manager']->value['sure_restart_server'];?>
") == false)
		return void(0);
	else
		window.location.href = "do.php?act=restart_server_msg";
		return true;
}
function init_date()
{
	var server_model=1;
	
	if(document.getElementsByName('servermodel')[0].checked==true)
	{
	server_model=1;
	}
	else
	{
	server_model=2;
	}
	if(window.confirm("<?php echo $_smarty_tpl->tpl_vars['server_manager']->value['init_dates'];?>
") == false)
		return void(0);
	else
		window.location.href = "do.php?act=init_date_msg&servermodel="+server_model;
}



<?php echo '</script'; ?>
>

<?php echo '<script'; ?>
 language="javascript" src="smarty/templates/ajax/synchronization.js"><?php echo '</script'; ?>
>
<!--
<?php echo '<script'; ?>
 language="javascript" src="smarty/templates/ajax/restart_server.js"><?php echo '</script'; ?>
>
-->
</head>
<body >
	<?php $_smarty_tpl->_subTemplateRender("file:servermanager_form.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
 
	<?php $_smarty_tpl->_subTemplateRender("file:language/".((string)$_smarty_tpl->tpl_vars['language']->value)."_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

</body>
</html><?php }
}
