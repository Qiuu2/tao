/*********************************
	与addonebellplan.php关联
**********************************/



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
function getmediataskname(url)
{
 
   createXMLHttpRequest();
   xmlhttp.open("GET",url,false);
   
   xmlhttp.onreadystatechange = function()
   { 
   	
      if( xmlhttp.readyState == 4 )
      { 
         if( xmlhttp.status == 200 )
         {
			return xmlhttp.responseText;
		 }
		 else
		 {
			alert('fail'); 
			
			
		 }
      }
   }
  
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
    xmlhttp.send(null);
	return xmlhttp.responseText;
}
function gettasknames(url,language)
{
 
   createXMLHttpRequest();
   
   xmlhttp.open("GET",url,false);
   
   xmlhttp.onreadystatechange = function()
   { 
   	
      if( xmlhttp.readyState == 4 )
      { 
         if( xmlhttp.status == 200 )
         {
			 if(xmlhttp.responseText == 1)
			 {
				  if(language=="english")
				 	alert('the same name');
				 else
				alert('有同名');
			 }
			
		 }
		 else
		 {
			   if(language=="english")
				 alert('fail');
			 else
				alert('失败'); 
		 }
      }
   }
  
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
    xmlhttp.send(null);
	return xmlhttp.responseText;
}

function senddata(url,language)
{
   getresultvalue = 0;
  // var geturl=encodeURI(encodeURI(url));
   createXMLHttpRequest();
   xmlhttp.open("GET",url,false);
   xmlhttp.onreadystatechange = function()
   { 
      if( xmlhttp.readyState == 4 )
      { 
	  
         if( xmlhttp.status == 200 )
         {
			 if(xmlhttp.responseText == 1)
			 {
				 if(language=="english")
				 	alert('success');
				 else
					alert('成功');
			 	getresultvalue = 1;
			 }
			 else if(xmlhttp.responseText == 0)
			 {
				 if(language=="english")
				 	alert('the same name');
				 else
					alert('有同名');
			 	getresultvalue = 0;
			 }
		 }
		 else
		 {
			  if(language=="english")
				 alert('fail');
			 else
				alert('失败'); 
			getresultvalue = 0;
		 }
      }
   }
  
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
    xmlhttp.send(null);

	return getresultvalue;
}