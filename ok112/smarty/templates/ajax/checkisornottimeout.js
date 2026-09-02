/***********************
	刷新页面
	关联application.php
***********************/
//XMLHttpRequest
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
/************************

************************/
function reloadpage()
{
   createXMLHttpRequest();
   xmlhttp.open( "get","application.php",true );
   xmlhttp.onreadystatechange = function()
   {
      if( xmlhttp.readyState == 4 )
      {
         if( xmlhttp.status == 200 )
         {
            if( xmlhttp.responseText == 0)//
            {
				alert("");
            }
         }
      }
   }
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	xmlhttp.send(null);
}
setInterval( "reloadpage()", 10000 );