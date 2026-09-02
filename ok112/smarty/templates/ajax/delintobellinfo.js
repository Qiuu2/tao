/***************************
关联文件modifyonebellplan.php
***************************/
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
		alert('Not Supported AJAX');
	}
} 
function deldata(url)
{
   var getresultvalue = 0;
   
   createXMLHttpRequest();
   
   xmlhttp.open("GET",url,false);
   
   xmlhttp.setRequestHeader('charset','utf-8'); 
   
   xmlhttp.onreadystatechange = function()
   { 
      if( xmlhttp.readyState == 4 )
      { 
         if( xmlhttp.status == 200 )
         {
			 if(xmlhttp.responseText == 1)
			 {
				alert('sucess');
				
				getresultvalue = 1;
			 }
			 if(xmlhttp.responseText == 0)
			 {
				alert('fail');
				
				getresultvalue = 0;	 
			 }
         }
		 else
		 {
			alert('fail');
			
			getresultvalue = 0;
		 }
      }
   }
    
	xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	
	xmlhttp.send(null);
	
	return getresultvalue;
}