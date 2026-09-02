<?php
function chinese_big5_english($curr_language,$txt_msg)
{
    if($curr_language == "big5")
    {
        switch($txt_msg)
        {
            case "共享媒体库":
            $txt_msg= "共享媒體庫";
            break;
            case "铃声媒体库":
            $txt_msg= "鈴聲媒體庫";
            break;
            case "点播媒体库":
            $txt_msg= "點播媒體庫";
            break;
            case "报警媒体库":
            $txt_msg= "報警媒體庫";
            break;
            case "录音媒体库":
            $txt_msg= "錄音媒體庫";
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
            case "共享媒体库":
            $txt_msg= "Sharing Media";
            break;
            case "铃声媒体库":
            $txt_msg= "Ring Music";
            break;
            case "点播媒体库":
            $txt_msg= "AOD Media";
            break;
            case "报警媒体库":
            $txt_msg= "Alarm Media";
            break;
            case "录音媒体库":
            $txt_msg= "Recording Media";
            break;
			 case "文件管理":
            $txt_msg= "file manager";
            break;
			case "语音合成媒体库":
            $txt_msg= "tts media";
            break;
        }
    }
return $txt_msg;
}

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

$result_folder = get_folder_info('0');
$faname = chinese_big5_english($_SESSION['language'],'文件管理');
$str_js = "<html xmlns=\"http://www.w3.org/1999/xhtml\"><head><meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\"/>
			<title>media_tree_view</title>
			<head>
				<link rel=\"stylesheet\"href=\"skin/css/main_page_style.css\"/>
				<link rel=\"stylesheet\"href=\"mediafolder_tree/jquery.treeview.css\"/>

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
						<span class=\"folder\">&nbsp;&nbsp;".$faname."</span>";

		while($row_folder = mysqli_fetch_array($result_folder))
		{
			if($_SESSION['registerflag']==1||$_SESSION['registerflag']==2)
			{

			}
			else
			{
				if($row_folder['id']==5)
					continue;
			}
		   $faname = chinese_big5_english($_SESSION['language'],$row_folder['name']);
		   
		   $str_js.="<ul class=\"filetree\">
					   
					   <li class=\"closed\">
						
					   <span class=\"folder\">
						
						  <a href=\"media_file.php?id=".$row_folder['id']."&userinfoid=".$row_folder['id']."\"  target=\"mediafile\" >".$faname."</a>
						
					   </span>";
						
		             $result_1folder = get_folder_info($row_folder['id']);
					 
					 while($row_1folder = mysqli_fetch_array($result_1folder))
					 {
						$str_js.= "<ul id=\"\" class=\"filetree\">
								      
									  <li class=\"closed\">
								
								      <span class=\"folder\">
								           
										   <a href=\"media_file.php?id=".$row_1folder['id']."&userinfoid=".$row_folder['id']."\" target=\"mediafile\">".
										   
										   chinese_big5_english($_SESSION['language'],$row_1folder['name'])."</a>
								      </span>";
						
								      $result_2folder = get_folder_info($row_1folder['id']);
						   
						              while($row_2folder = mysqli_fetch_array($result_2folder))
						              {
							               $str_js.= "<ul id=\"\" class=\"filetree\">
								          <li class=\"closed\">
								          <span class=\"folder\">
								            
										  <a href=\"media_file.php?id=".$row_2folder['id']."&userinfoid=".$row_folder['id']."\"  target=\"mediafile\">".chinese_big5_english
										  
										  ($_SESSION['language'],$row_2folder['name'])."</a>
								
								          </span>";
										 /*
										  $result_3folder = get_folder_info($row_2folder['id']);
										  while($row_4folder = mysqli_fetch_array($result_3folder))
										  {
										  //	$sql_folder = "DELETE FROM filefolder WHERE filefolder.id IN ($row_4folder[id])";
											//$del_folder = mysqli_query($con,$sql_folder) or die(mysqli_error($con));	
										  
										  }
										  mysqli_free_result($result_3folder);
										  */
								          $str_js.="</li>	</ul>";
								
						             }
									mysqli_free_result($result_2folder);
						           $str_js.="</li>	</ul>";
						
					       }
					
					mysqli_free_result($result_1folder);
					         $str_js.="</li>	</ul>";
	           }
	  
mysqli_free_result($result_folder);
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

unset($row_file,$sql_file,$row_folder,$sql_folder);



?>
