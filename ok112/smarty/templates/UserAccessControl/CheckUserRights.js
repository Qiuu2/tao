//
function rights_control(is_right,is_admin,a_name,b_name)
{

//	if(is_right == 1 || is_admin == "administrator")
	if(is_right == 1)
	{
			//
	}
	else
	{
		var a_obj = document.getElementsByTagName("a");
		
		for(var i=0; i<a_obj.length; i++)
		{
			if(a_obj[i].name == a_name)
			{
				continue;
			}
			if(a_obj[i].name == b_name)
			{
				continue;	
			}
			a_obj[i].href = "javascript:void(0)";
			a_obj[i].style.color="#787878";
			a_obj[i].onclick = null;
		}
	} 
}
