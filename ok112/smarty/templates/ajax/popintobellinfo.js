/***************************
关联文件modifyonebellplan.php
***************************/
var getresultvalue = -1;

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
function modifydata(url,language)
{
   getresultvalue = 0;
   
   var getnewtaskid = 0;
   
   createXMLHttpRequest();
   
   xmlhttp.open("GET",url,false);
   
   xmlhttp.setRequestHeader('charset','utf-8'); 
   
   xmlhttp.onreadystatechange = function()
   { 
      if(xmlhttp.readyState == 4 )
      { 
         if(xmlhttp.status == 200 )
         {
	
			 if(xmlhttp.responseText == 0 )
			 {
				 if(language=="english")
					alert('the same name');
				 else
					alert('有同名');
				
				getresultvalue = 0;
			 }
			 if(xmlhttp.responseText == 1)
			 {
				  if(language=="english")
					 alert('success');
				  else
					alert('成功');
				
				getresultvalue = 1; 
				getnewtaskid = trim(xmlhttp.responseText);
			 }
			 if( xmlhttp.responseText > 1 )
			 {
				   if(language=="english")
					 alert('success');
				   else
					alert('成功');
				
				getresultvalue = 1;
				getnewtaskid = trim(xmlhttp.responseText);
			 }
         }
		 else
		 {
			   if(language=="english")
					 alert('success');
				 else
					alert('成功'); 
			
			getresultvalue = 0;
		 }
      }
   }
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");

	xmlhttp.send(null);
	
	return trim(getnewtaskid);
}