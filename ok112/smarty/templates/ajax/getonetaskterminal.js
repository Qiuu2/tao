/*****************************
关联文件getonetaskterminal.php
*****************************/
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

function merge_tree_terminal_group_string_s(group_id,terminal_id)
{
	return "stream_"+group_id+"::"+terminal_id;
}

function getonetaskterminals(url,getcurrenrownum)
{
   createXMLHttpRequest();
   
   xmlhttp.open("GET",url,false);
   
   xmlhttp.setRequestHeader('charset','utf-8'); 
  
   xmlhttp.onreadystatechange = function()
   { 
      if( xmlhttp.readyState == 4 )
      { 
         if( xmlhttp.status == 200 )
         {	
		 
			 return displayonetaskterminal(xmlhttp.responseXML,getcurrenrownum);
         }
		 else
		 {
			alert('FAILED'); 
		 }
      }
   }
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	
	xmlhttp.setRequestHeader('Content-Type', "text/xml");
	
	xmlhttp.send(null);
}
function displayonetaskterminal(strterminal,getcurrenrownum)
{
	
	var objroot = strterminal.documentElement;
	if(navigator.appName.indexOf("Explorer") > -1)   
	{
		var taskname = objroot.childNodes[0].text;
		var prepower = objroot.childNodes[1].text;
		var task_volume = objroot.childNodes[2].text;
		var startdate = objroot.childNodes[3].text;
		var enddate = objroot.childNodes[4].text;
		var info = objroot.childNodes[5].text;
		var exemodel = objroot.childNodes[6].text;
		var getterminalid = objroot.childNodes[8].text;
		var get_task_prority_str = objroot.childNodes[7].text;
		
		var getgroupid = objroot.childNodes[9].text;
		alert(getterminalid);
	}
else
	{

		//var taskname = objroot.childNodes[0].firstChild.nodeValue;
		  var taskname = objroot.childNodes[1].firstChild.nodeValue;
	
		var prepower = objroot.childNodes[3].firstChild.nodeValue;
		var task_volume = objroot.childNodes[5].firstChild.nodeValue;
		var startdate = objroot.childNodes[7].firstChild.nodeValue;
		var enddate = objroot.childNodes[9].firstChild.nodeValue;
		var info = objroot.childNodes[11].firstChild.nodeValue;
		var exemodel = objroot.childNodes[13].firstChild.nodeValue;
		var getterminalid = objroot.childNodes[17].firstChild.nodeValue;
		var get_task_prority_str = objroot.childNodes[15].firstChild.nodeValue;
	
		var getgroupid = objroot.childNodes[19].firstChild.nodeValue;
	}

	if(getterminalid == "")
	{
		if(tree3 != null)
		{
			var preobj = document.getElementById('prepower');
			
			for(var i=0; i<preobj.options.length; i++)
			{
				if(preobj.options[i].value == prepower)
				{
					preobj.options[i].selected = true;	
				}
			}
			document.getElementById('task_default_volume').value = task_volume;
			
			document.getElementById('volume_value').value = task_volume;
			
			document.getElementById('startdate').value = startdate;
			
			document.getElementById('enddate').value = enddate;
			
			var get_bell_modify_select_priority_obj = document.getElementById('task_priority_text');
				
			for(var priority_index_value = 0; priority_index_value < get_bell_modify_select_priority_obj.options.length; priority_index_value++)
			{
					if(get_bell_modify_select_priority_obj.options[priority_index_value].value == get_task_prority_str.substr(get_task_prority_str.length-1,1))
					{
						get_bell_modify_select_priority_obj.options[priority_index_value].selected = true;
						break;
					}
			}
			
			var count = 0;
			var modelobj = document.getElementById('exemodel');
			if(modelobj.options[1].selected == true)
			{
				var tableobj = document.getElementById("timetable");
				var totalrow = tableobj.rows.length;
				tableobj.deleteRow(totalrow-1);

				for(var i=0;i<exemodel.length;i++)
				{
					 if(exemodel.charAt(i)=="1")
					 {
						count++;
					 }
				}
				if(count == 7)
				{
					modelobj.options[0].selected = true;
				}
				if(count != 7)
				{
					modelobj.options[1].selected = true;
					displayweek(modelobj);
					for(var i=0;i<exemodel.length;i++)
					{
						if(exemodel.charAt(i)=="1")
						{
							document.getElementsByName('week')[i].checked = true;
						}
					}
				}
			}
			tree3.destructor();			
			tree3=new dhtmlXTreeObject("terminallist","100%","100%",0);
			tree3.setSkin('dhx_skyblue');
			tree3.setImagePath("smarty/templates/BellManager/codebase/csh_bluebooks/");
			tree3.enableCheckBoxes(1);
			tree3.enableThreeStateCheckboxes(true);
            tree3.setOnCheckHandler(toncheck);
			tree3.loadXMLString(treedata);
		}
	}
	else if(getterminalid != "")
	{
		var selectterminalid = getterminalid.split(",");
		
		var getgroup_ids = getgroupid.split(",");
		
		if(tree3 != null)
		{
			tree3.destructor();
			tree3=new dhtmlXTreeObject("terminallist","100%","100%",0);
			tree3.setSkin('dhx_skyblue');
			tree3.setImagePath("smarty/templates/BellManager/codebase/csh_bluebooks/");
			tree3.enableCheckBoxes(1);
			tree3.enableThreeStateCheckboxes(true);
            tree3.setOnCheckHandler(toncheck);
			tree3.loadXMLString(treedata);
	
				document.getElementById('taskname').value = info;
				
				var preobj = document.getElementById('prepower');
				
				for(var i=0; i<preobj.options.length; i++)
				{
					if(preobj.options[i].value == prepower)
					{
						preobj.options[i].selected = true;	
					}
				}
				document.getElementById('task_default_volume').value = task_volume;
				
				document.getElementById('volume_value').value = task_volume;

				document.getElementById('startdate').value = startdate;
				
				document.getElementById('enddate').value = enddate;
				
				var get_bell_modify_select_priority_obj = document.getElementById('task_priority_text');
				
				for(var priority_index_value = 0; priority_index_value < get_bell_modify_select_priority_obj.options.length; priority_index_value++)
				{
						if(get_bell_modify_select_priority_obj.options[priority_index_value].value == get_task_prority_str.substr(get_task_prority_str.length-1,1))
						{
							get_bell_modify_select_priority_obj.options[priority_index_value].selected = true;
							break;
						}
				}

				var count = 0;
				
				var modelobj = document.getElementById('exemodel');
				
				if(modelobj.options[1].selected == true)
				{
					var tableobj = document.getElementById("timetable");
					var totalrow = tableobj.rows.length;
					tableobj.deleteRow(totalrow-1);
					
					for(var i=0;i<exemodel.length;i++)
					{
						 if(exemodel.charAt(i)=="1")
						 {
							count++;
						 }
					}
					if(count == 7)
					{
						modelobj.options[0].selected = true;
					}
					if(count != 7)
					{
						modelobj.options[1].selected = true;
						displayweek(modelobj);
						for(var i=0;i<exemodel.length;i++)
						{
							if(exemodel.charAt(i)=="1")
							{
								document.getElementsByName('week')[i].checked = true;
							}
						}
					}
				}
				else if(modelobj.options[0].selected == true)
				{
					
					for(var i=0;i<exemodel.length;i++)
					{
						 if(exemodel.charAt(i)=="1")
						 {
							count++;
						 }
					}
					if(count == 7)
					{
						modelobj.options[0].selected = true;
					}
					if(count != 7)
					{
						modelobj.options[1].selected = true;
						
						displayweek(modelobj);
						
						for(var i=0;i<exemodel.length;i++)
						{
							if(exemodel.charAt(i)=="1")
							{
								document.getElementsByName('week')[i].checked = true;
							}
						}
					}
					
				}
				
				document.getElementsByName('add')[getcurrenrownum-1].value = "修改";
				
				for(var i=0; i<selectterminalid.length; i++)
				{
					//tree3.setCheck(""+selectterminalid[i]+"",true);

					tree3.setCheck(merge_tree_terminal_group_string_s(getgroup_ids[i],selectterminalid[i]),true);
				}
								
			document.getElementsByName('belltaskid')[getcurrenrownum-1].checked = true;
			
			document.getElementsByName('coursename')[getcurrenrownum-1].disabled = false;
			
			document.getElementsByName('bellstarttime')[getcurrenrownum-1].disabled = false;
			
			document.getElementsByName('setbellname')[getcurrenrownum-1].disabled = false;
			
			document.getElementsByName('belltiemlength')[getcurrenrownum-1].disabled = false;
			
			document.getElementsByName('add')[getcurrenrownum-1].disabled = false;
		}
	}
}