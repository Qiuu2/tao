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
function restart_server(url)
{
   createXMLHttpRequest();
   
   xmlhttp.open( "get",url, false );
   
   xmlhttp.onreadystatechange = function()
   {
      if( xmlhttp.readyState == 4 )
      {
         if( xmlhttp.status == 200 )
         {
			set_restart_success();
         }
		 else
		 {
			set_restart_fail(); 
		 }
      }
   }
   
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	
	xmlhttp.send(null);
}

function set_restart_success()
{
	var restart_obj = document.getElementById('restart_server');
	
	restart_obj.disabled  = false;

	alert('Server is restarting');
	//window.location.reload();
}
function set_restart_fail()
{
	var restart_obj = document.getElementById('restart_server');
	
	restart_obj.disabled  = false;
	
	alert('Reset the failure');
	//window.location.reload();
}