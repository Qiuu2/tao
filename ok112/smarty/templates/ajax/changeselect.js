/*********************************
�����ļ�get_changeselect1.php
*********************************/
	var xml_http=null; 
	var ret;
				function createXMLHttpRequestobject()
				{ 
					
					if(window.ActiveXObject)
					{ 
						xml_http = new ActiveXObject("microsoft.XMLHTTP"); 
					} 
					else if(window.XMLHttpRequest)
					{ 
						xml_http = new XMLHttpRequest(); 
					}
					else
					{
						alert('Not Supported AJAX');
					}
				}
				
				function updateterminaldate(url)
				{
					
				   createXMLHttpRequestobject();
				 
				   xml_http.open("GET",url,false);
				  
				   xml_http.onreadystatechange = function()
				   { 
					  if( xml_http.readyState == 4 )
					  { 
						 if( xml_http.status == 200 )
						 {
						
						
							 ret = xml_http.responseText;
						
						 }
						
						 
					  }
				   }
			
					xml_http.setRequestHeader( "If-Modified-Since", "0");
					xml_http.send(null);
				//	alert(ret);
					return ret;
				}
				function delterminaldate(url)
				{
					
				   createXMLHttpRequestobject();
				 
				   xml_http.open("GET",url,false);
				  
				   xml_http.onreadystatechange = function()
				   { 
					  if( xml_http.readyState == 4 )
					  { 
						 if( xml_http.status == 200 )
						 {
						
						 }
						
						 
					  }
				   }
			
					xml_http.setRequestHeader( "If-Modified-Since", "0");
					xml_http.send(null);
				//	alert(ret);
				
				}
				function select_terminal(url)
				{
					
				   createXMLHttpRequestobject();
				 
				   xml_http.open("GET",url,false);
				  
				   xml_http.onreadystatechange = function()
				   { 
					  if( xml_http.readyState == 4 )
					  { 
						 if( xml_http.status == 200 )
						 {
						
						 }
						
						 
					  }
				   }
			
					xml_http.setRequestHeader( "If-Modified-Since", "0");
					xml_http.send(null);
				//	alert(ret);
				
				}
				
				
				
				