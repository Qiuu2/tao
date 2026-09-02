// JavaScript Documentfunction trim(str)
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
function isNumber( s )
{
   var regu = "^[0-9]+$";
   var re = new RegExp(regu);
   if(s.search(re) != -1)
   {
      return true;
   }
   else
   {
      return false;
   }
} 

function lengthNEQFour(str)
{
	if(trim(str).length !=4)
	{
		return true;
	}
	return false;
}

function lengthbigTwo(str)
{
	if(trim(str).length >2)
	{
		return true;
	}
	return false;
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
		alert('<{$backup_restore.Not_support_ajax|capitalize}>');
	}
} 