/*********************************
关联文件get_changeselect1.php
*********************************/
	var xml_http=null; 
	var ret = 0;
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
				
				function get_media_length1(obj,url)
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
					function get_media_length2(urls)
				{
					
				   createXMLHttpRequestobject();
				   
				   xml_http.open("GET",urls,false);
				  
				   xml_http.onreadystatechange = function()
				   { 
					  if( xml_http.readyState == 4 )
					  { 
						 if( xml_http.status == 200 )
						 {

							 jet = xml_http.responseText;

						 }
					
						 
					  }
				   }
			
					xml_http.setRequestHeader( "If-Modified-Since", "0");
					xml_http.send(null);
				//	alert(ret);
					return jet;
				}
				
				
				