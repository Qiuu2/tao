var analysis_tree_group_string = new Array();

var analysis_tree_terminal_string = new Array();

function analysis_tree_terminal_group_string(string_array)
{
	var string_arrays = string_array.split(",");
	
	for(var i=0; i<string_arrays.length; i++)
	{
		if(string_arrays[i].indexOf("::") != -1)
		{
		
			var string_arrays_temp = string_arrays[i].split("::");
			
			analysis_tree_group_string.push(string_arrays_temp[0].split("_")[1]);
			
			analysis_tree_terminal_string.push(string_arrays_temp[1]);
		}
	}
}

function merge_tree_terminal_group_string(group_id,terminal_id)
{
	return "stream_"+group_id+"::"+terminal_id;
}
/*
function analysis_tree_media_file_string()
{
		
}*/