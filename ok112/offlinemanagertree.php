<?php


//=====实现文件以树的形式显示=====//
if (!session_id()) session_start();

require_once('inc/smarty.inc.php');

require_once('inc/config.inc.php');

require_once('inc/common.php');
//获取选择文件夹
$get_selected_folder_id = 1;

if( empty($_SESSION['tran_mid_value']) )
{
	$get_selected_folder_id = 1;
}
else
{
	$get_selected_folder_id = trim($_SESSION['tran_mid_value']);
}


function chinese_big5_english($curr_language,$txt_msg)
{
    if($curr_language == "big5")
    {
        switch($txt_msg)
        {
            case "任务管理":
            $txt_msg= "任务管理";
            break;
			 case "服务器任务":
            $txt_msg= "服务器任务";
            break;
			 case "云广播任务":
            $txt_msg= "云广播任务";
            break;
        }
    }
    else if($curr_language == "chinese") 
    {
        //不做处理
    }
    else if($curr_language == "english")
    {
        switch($txt_msg)
        {
            case "任务管理":
            $txt_msg= "task management";
            break;
			 case "服务器任务":
            $txt_msg= "service task";
            break;
			 case "云广播任务":
            $txt_msg= "offline task";
            break;
        }
    }
return $txt_msg;
}

//判断是系统管理员
$result_folder = get_folder_info('0');
$faname =chinese_big5_english('chinese','任务管理');
$str_js = "<html xmlns=\"http://www.w3.org/1999/xhtml\"><head><meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\"/>
			<title>media_tree_view</title>
			<head>
				<link rel=\"stylesheet\"href=\"skin/css/main_page_style.css\"/>
				<link rel=\"stylesheet\"href=\"mediafolder_tree/jquery.treeview.css\"/>
				<link rel=\"stylesheet\" href=\"mediafolder_tree/red-treeview.css\"/>
				<link rel=\"stylesheet\" href=\"mediafolder_tree/screen.css\" />
				<script src=\"mediafolder_tree/jquery.js\" type=\"text/javascript\"></script>
				<script src=\"mediafolder_tree/jquery.cookie.js\" type=\"text/javascript\"></script>
				<script src=\"mediafolder_tree/jquery.treeview.js\" type=\"text/javascript\"></script>
				<script type=\"text/javascript\">\$(function() {\$(\"\#browser\").treeview();});</script>
				<script language=\"javascript\">
				
					var is_or_not_selected = 0;

					function change_li(obj)
					{
						var li_objects = document.all.tags('li');
						
						for(var i=1; i<li_objects.length; i++)
						{
							li_objects[i].className = \"closed expandable lastExpandable\";
						}
						obj.parentNode.parentNode.className  = \"\";
					}
					
					function set_is_or_not_selected()
					{
						is_or_not_selected = 1;
					}
					
				</script>
			</head>
			<body style=\"margin:0\" onload=\"get_clicked_folder()\">
			<div id=\"main\">
				<ul id=\"browser\" class=\"filetree\">
					<li>
						<span class=\"folder\"> ".$faname."</a>
						</span>";
			$faname = chinese_big5_english('chinese','服务器任务');
			 $str_js.="<ul class=\"filetree\"><li class=\"closed\"> <span class=\"folder\">
						<a href=\"set_offline.php?id=1\" target=\"mediafile\" >".$faname."</a>
						</span>";
			 $str_js.="</li></ul>";
	 		 $faname = chinese_big5_english('chinese','云广播任务');
	  		$str_js.="<ul class=\"filetree\"><li class=\"closed\"> <span class=\"folder\">
						<a href=\"set_offline.php?id=2\" target=\"mediafile\" >".$faname."</a>
						</span>";
			 $str_js.="</li></ul>";
	              $str_js.="</li>
		</ul>
	</div>
</body>

<script language=\"javascript\">
	function get_clicked_folder()
	{
		var a_objects = document.all.tags('a');

		for(var i=0; i<a_objects.length; i++)
		{
			if( analyze_href(a_objects[i].href) == ".$get_selected_folder_id.")
			{
				a_objects[i].parentNode.parentNode.className  = \"\";
				
				is_or_not_selected = 1;
			}
		}
	}
	function analyze_href(str_href)
	{
		var str_array = str_href.split(\"?\");
		
		var str_name = str_array[1].split(\"=\");
		
		return str_name[1];
	}
</script>

</html>";

echo $str_js;

//@mysqli_free_result($result_folder);

//@mysqli_free_result($result_file);

unset($row_file,$sql_file,$row_folder,$sql_folder);
?>
