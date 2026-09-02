/****************************************
	ļoutput_static_page.php
****************************************/
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
		alert("NOT Support AJAX");
	}
} 
function output_static_page(url)
{
   createXMLHttpRequest();
   xmlhttp.open( "get",url, true );
   xmlhttp.onreadystatechange = function()
   {
      if( xmlhttp.readyState == 4 )
      {
         if( xmlhttp.status == 200 )
         {
           var str_html = xmlhttp.responseText;
           dynamic_html(str_html);
         }
      }
   }
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	xmlhttp.send(null);
}
//
function dynamic_html(str_html)
{
	parent_obj.innerHTML = str_html;
}