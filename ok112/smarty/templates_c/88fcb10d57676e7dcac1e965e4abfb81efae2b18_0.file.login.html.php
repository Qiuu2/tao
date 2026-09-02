<?php
/* Smarty version 3.1.30, created on 2026-07-01 10:17:51
  from "/var/www/html/ok112/smarty/templates/login.html" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_6a4478cf7eedd6_10468884',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '88fcb10d57676e7dcac1e965e4abfb81efae2b18' => 
    array (
      0 => '/var/www/html/ok112/smarty/templates/login.html',
      1 => 1778116076,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:login_form.html' => 1,
    'file:language/chinese_foot.php' => 1,
  ),
),false)) {
function content_6a4478cf7eedd6_10468884 (Smarty_Internal_Template $_smarty_tpl) {
if (!is_callable('smarty_modifier_capitalize')) require_once '/var/www/html/ok112/smarty/libs/plugins/modifier.capitalize.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>LoginManager</title>

<link href="skin/css/main_page_style.css" rel="stylesheet" type="text/css" />
<?php echo '<script'; ?>
 src="skin/js/frame/actiondo.js"><?php echo '</script'; ?>
>
<style type="text/css">
span
{
	color:#FF0000;
}

.regist_button
{
	border:1px solid #aaaaaa;
	height:20px; 
}
</style>

<?php echo '<script'; ?>
 type="text/javascript">
	
function trim(str)
{
   str=str.replace(/(^\s*)|(\s*$)/g,""); 
   return str;
}
function isNull( str )
{
   if ( str == "" ) return true;
   var regu = "^[ ]+$";
   var re = new RegExp(regu);
   return re.test(str);
}
function isNumberOr_Letter( s )
{
   //判断是否是数字或字母
   var regu = "^[0-9a-zA-Z\_]+$";
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

function str_encde_demo(str)
{
	var get_demo="A"+str_encode(str)+"t1";
	return get_demo;

}


function actionform()
{
	var getaction,getaction2;
	var d = new Date();
	var get_t=d.getTime();
	var user_name=document.getElementById('username').value;
	var user_pwd=document.getElementById('userpwd').value;
	var checknum=document.getElementById('checknum').value;

	if(document.form1.username.value.length>20)
	{
		document.getElementById('username_s').innerHTML="<font color='#ff0000'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['username_lens_max']);?>
</font>";
		document.getElementById('username').value = "";
		document.form1.username.focus();
		return false;
	}

	if(document.form1.userpwd.value.length>20)
	{
		document.getElementById('userpwd_s').innerHTML="<font color='#ff0000'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['passwd_lens_max']);?>
</font>";
		document.getElementById('userpwd').value = "";
		document.form1.userpwd.focus();
		return false;
	}
	var passflag=<?php echo $_smarty_tpl->tpl_vars['FUZA_PASS']->value;?>
;
	if(passflag==0)
	{
		if( isNull(document.getElementById('userpwd').value) )
		{
			document.getElementById('userpwd_s').innerHTML="<font color='#ff0000'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['enter_password']);?>
</font>";
			
			document.form1.userpwd.focus();
			return false;
		}
		/*
		else if(!isNumberOr_Letter(document.getElementById('userpwd').value))
		{
			document.getElementById('userpwd_s').innerHTML="<font color='#ff0000'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['only_number_letter']);?>
</font>";
			alert('密码只能是字母和数字！');
			document.getElementById('userpwd').select();
			document.form1.userpwd.focus();
			return false;	
		}*/
		else
		{
			document.getElementById('userpwd_s').innerHTML="";
		}
			getaction="time="+get_t+"&user_name="+user_name+"&user_pwd="+user_pwd+"&checknum="+checknum+"&timeaa="+get_t;
			getaction2="do.php?act=aaa&abc=haC"+str_encode(getaction);
		
			document.getElementById('username').value=str_encde_demo(user_name);
			document.getElementById('userpwd').value=str_encde_demo(user_pwd);
			document.getElementById('checknum').value=str_encde_demo(checknum);
			document.form1.action = getaction2;
			document.form1.submit();
	}
	else
	{
		var pwdRegex = new RegExp('(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[^a-zA-Z0-9]).{8,30}');	
		if (!pwdRegex.test(document.getElementById('userpwd').value)) {
			//document.getElementById('userpwd_s').innerHTML="<font color='#ff0000'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['password_noerror']);?>
</font>";
			alert('密码中必须包含大小写字母、数字、特殊字符,长度最低8位！');
			document.form1.userpwd.focus();
			return false;
		}
		else
		{
			getaction="time="+get_t+"&user_name="+user_name+"&user_pwd="+user_pwd+"&checknum="+checknum+"&timeaa="+get_t;
			getaction2="do.php?act=aaa&abc=haC"+str_encode(getaction);
		
			document.getElementById('username').value=str_encde_demo(user_name);
			document.getElementById('userpwd').value=str_encde_demo(user_pwd);
			document.getElementById('checknum').value=str_encde_demo(checknum);
			document.form1.action = getaction2;
			document.form1.submit();
		}
	}
}

function checkform()
{

	if(document.form1.username.value.length==0)
	{
		document.getElementById('username_s').innerHTML="<font color='#ff0000'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['fill_in_account']);?>
</font>";
		document.getElementById('username').value = "";
		document.form1.username.focus();
		return false;
	}
	else if( isNull(document.getElementById('username').value) )
	{
		document.getElementById('username_s').innerHTML="<font color='#ff0000'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['fill_in_account']);?>
</font>";
		
		document.getElementById('username').value = "";
		document.form1.username.focus();
		return false;
	}
	else
	{
		document.getElementById('username_s').innerHTML="";
	}

	/*
	if( isNull(document.getElementById('userpwd').value) )
	{
		document.getElementById('userpwd_s').innerHTML="<font color='#ff0000'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['enter_password']);?>
</font>";
		
		document.form1.userpwd.focus();
		return false;
	}
	else if(!isNumberOr_Letter(document.getElementById('userpwd').value))
	{
		document.getElementById('userpwd_s').innerHTML="<font color='#ff0000'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['only_number_letter']);?>
</font>";
		document.getElementById('userpwd').select();
		document.form1.userpwd.focus();
		return false;	
	}
	else
	{
		document.getElementById('userpwd_s').innerHTML="";
	}
	*/

	if( isNull(document.getElementById('checknum').value ) )
	{
		document.getElementById('num_s').innerHTML="<font color='#ff0000'><?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['enter_code']);?>
</font>";
		
		document.form1.checknum.focus();
		
		return false;
	}
	else
	{
		document.getElementById('num_s').innerHTML="";
	}
	return true;
}
//刷新图片
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
		alert('<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['not_support_AJAX']);?>
');
	}
} 
function refreshcode(url)
{
   createXMLHttpRequest();

   xmlhttp.open( "get",url,false);
   xmlhttp.onreadystatechange = function()
   {
      if( xmlhttp.readyState == 4 )
      {
         if( xmlhttp.status == 200 )
         {
			var obj = document.getElementById("verify");
			
			obj.removeAttribute("src");
			
			obj.setAttribute("src","verify.php");
         }
      }
   }
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
    xmlhttp.send(null);
}

function regist_server()
{
	//跳转
	window.location.href = "regist_server.php";
}

function eyeopenorclose()
{
	var mi =  window.document.getElementById('userpwd');
	var	ima = window.document.getElementById('imagoc');
	if (mi.type === 'password'){
					mi.type = 'text';
					ima.src="<?php echo $_smarty_tpl->tpl_vars['user_login']->value['eyeopen'];?>
";
			}else{
					mi.type = 'password';
					ima.src="<?php echo $_smarty_tpl->tpl_vars['user_login']->value['eyeclose'];?>
";
			}
	
}

window.onload=function()
{
	var getday=<?php echo $_smarty_tpl->tpl_vars['Days']->value;?>
;
	var days="<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['enddate']);?>
"+"<?php echo $_smarty_tpl->tpl_vars['Days']->value;?>
"+"<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['enddate2']);?>
";
	
	if(getday<=30&&getday>0)
	{
		alert(days);
	}
	else if(getday <0)
	{
		alert("<?php echo smarty_modifier_capitalize($_smarty_tpl->tpl_vars['user_login']->value['endpast']);?>
");
	}
}
<?php echo '</script'; ?>
>
</head>

<body>
	<?php $_smarty_tpl->_subTemplateRender("file:login_form.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
 
	<?php $_smarty_tpl->_subTemplateRender("file:language/chinese_foot.php", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
  
</body>
</html>
<?php }
}
