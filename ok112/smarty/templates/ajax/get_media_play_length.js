/*********************************
关联文件get_media_play_length.php
*********************************/
var xmlhttp=null; 
	var ret = 0;
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




function get_media_length(obj,url)
{
   createXMLHttpRequest();

   xmlhttp.open("GET",url,false);
   
   xmlhttp.onreadystatechange = function()
   { 
      if( xmlhttp.readyState == 4 )
      { 
         if( xmlhttp.status == 200 )
         {
			
			var get_media_play_time_format = time_tran_format(xmlhttp.responseText);
			  ret=xmlhttp.responseText;
			 
			obj.parentNode.parentNode.cells[4].childNodes[0].value = get_media_play_time_format;
		
			 
         }
		 else
		 {
			alert('Failed'); 
		
		 }
      }
   }
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	
	xmlhttp.send(null);
	
	return ret;
}




function get_media_time_length(obj)
{
	var media_id = obj.value;
	var url = "get_media_play_length.php?id=" + media_id;
	 get_media_length(obj,url);

}



function time_tran_format(time_length)
{
	var timenum = time_length;
	var hours = parseInt(timenum/(60*60));
	
	if(hours<10)
	{
		hours = "0"+hours+"";  
	}
	
	var minutes = parseInt((timenum - hours*60*60)/60);
	
	if(minutes<10)
	{
		minutes = "0"+minutes+"";  
	}
	
	var second = (timenum - hours*60*60 - minutes*60);
	
	if(second<10)
	{
		second = "0"+second+"";  
	}
	
	return (hours+":"+minutes+":"+second);
}

function get_last_row_select_value(gettable)
{
	var table = document.getElementById(gettable);
	
	var last_row = table.rows[table.rows.length-1];
	
	var last_select_obj = last_row.childNodes[3].childNodes[0];
	
	var select_fist_value = last_select_obj.value ;
	
	var url = "get_media_play_length.php?id=" + select_fist_value;
	
	get_media_length(last_select_obj,url);
}

function click_task_button_get_select_first(gettable)
{
	var table = document.getElementById(gettable);
	
	var last_row = table.rows[table.rows.length-1];
	
	var last_select_obj = last_row.childNodes[3].childNodes[0];
	
	var select_fist_value = last_select_obj.value ;
	
	var url = "get_media_play_length.php?id=" + select_fist_value;
	
	get_media_length(last_select_obj,url);
}
