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


//判断是系统管理员
if($_SESSION['language']=='english')
{
	$title="task manager";
	$result_folder = array("schedule solution","media broadcast","live collection","terminal amplifier","network radio","tts manager");
}
else
{
	$title="任务管理";
	$result_folder = array("作息方案","文件广播","采播管理","终端功放","网络电台","文字语音");
}
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
						<span class=\"folder\">&nbsp;&nbsp;".$title."</span>";

			for($ii=0;$ii<count($result_folder);$ii++)
			{
			$bb=$ii+1;
			  $str_js.="<ul class=\"filetree\">  
						   <li class=\"closed\">
						   <span class=\"folder\">
							  <a href=\"Browse_active_task.php?id=".$bb."\" target=\"mediafile\" >".$result_folder[$ii]."</a>
						   </span>";
			  $str_js.="</li></ul>";
			}
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
