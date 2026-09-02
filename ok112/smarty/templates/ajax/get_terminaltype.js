// JavaScript Document
var xmlhttp=null; 
var channelnum="";
var areanum="";
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
		alert('<{$alarm_setting.Not_support_AJAX|capitalize}>');
	}
} 
function getchannelvalue(url)
{
   createXMLHttpRequest();
   xmlhttp.open( "get",url, false );
   xmlhttp.onreadystatechange = function()
   {
      if( xmlhttp.readyState == 4 )
      {
         if( xmlhttp.status == 200 )
         {
            if(!isNull(xmlhttp.responseText))
            {
				
				channelnum = xmlhttp.responseText; 
            }
						
			
         }
      }
   }
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	xmlhttp.send(null);
	return channelnum;
}


