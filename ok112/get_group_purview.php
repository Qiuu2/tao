<?php
	header("content-type:text/html;charset=utf-8");
	
	require_once("inc/config.php");
	
	require_once("inc/config.inc.php");
	
	//显示多语言
	require_once("language/".$_SESSION['language'].".php");
	
	$group_id = "";
	
	$group_rights = "";
	
	$show_purview = "<table border='0' cellpadding='5' cellspacing='5' style='background-color:#EEFEEF;'><tr>";
	if(isset($_GET['group_id']))
	{
		$group_id = trim($_GET['group_id']);
	}
	if(trim($group_id) == "")
	{
		echo "<table width='100%' border='0' cellpadding='5' cellspacing='5' style='background-color:#EEFEEF;'>
					<tr><td nowrap='nowrap'>".$user_group_manager['select_user_groups']."</td></tr></tbody>";
	}
	else
	{
		$group_sql = " select 	taskpriv,terminalpriv,mediapriv,userpriv,serverpriv,";
		$group_sql.= " folderpriv,terminalgrouppriv,alarmgrouppriv,bellpriv,admpriv,";
		$group_sql.= " telephonepriv, powerplay from usergroup where usergroup.id = $group_id";

		$group_result = mysqli_query($con,$group_sql) or die(mysqli_error($con));
		if($group_row = mysqli_fetch_array($group_result))
		{
			if($group_row['taskpriv'] == 1)
			{
				$group_rights.= $user_group_manager['Task_Management']."|"; 
			}
			if($group_row['terminalpriv'] == 1)
			{
				$group_rights.= $user_group_manager['Terminal_Management']."|"; 
			}
			if($group_row['mediapriv'] == 1)
			{
				$group_rights.= $user_group_manager['Media_Management']."|"; 
			}
			if($group_row['userpriv'] == 1)
			{
				$group_rights.= $user_group_manager['User_Management']."|"; 
			}
			if($group_row['serverpriv'] == 1)
			{
				$group_rights.= $user_group_manager['Server_Management']."|"; 
			}
			if($group_row['folderpriv'] == 1)
			{
				$group_rights.= $user_group_manager['Folder_Management']."|"; 
			}
			if($group_row['terminalgrouppriv'] == 1)
			{
				$group_rights.= $user_group_manager['Zone_Management']."|"; 
			}
			if($group_row['alarmgrouppriv'] == 1)
			{
				$group_rights.= $user_group_manager['Alarm_Management']."|"; 
			}
			if($group_row['bellpriv'] == 1)
			{
				$group_rights.= $user_group_manager['Bell_Management']."|"; 
			}
			if($group_row['admpriv'] == 1)
			{
				$group_rights.= $user_group_manager['Collection_Management']."|"; 
			}
			if($group_row['telephonepriv'] == 1)
			{
				$group_rights.= $user_group_manager['Phone_Management']."|"; 
			}
			if($group_row['powerplay'] == 1)
			{
				$group_rights.= $user_group_manager['Power_Management']."|"; 
			}
			if(empty($group_rights))
			{
				echo "<table width='100%' border='0' cellpadding='5' cellspacing='5' style='background-color:#EEFEEF;'>
						<tr><td nowrap='nowrap'>".$user_group_manager['No_permission']."</td></tr></tbody>";
			}
			else
			{
				$group_rights = substr($group_rights,0,strlen($group_rights)-1);
				$str_purview = explode("|",$group_rights);
				for($i=0; $i<count($str_purview); $i++)
				{
					$show_purview.= "<td nowrap='nowrap'>$str_purview[$i]</td>";
					if(count($str_purview) < 4)
					{
						$show_purview.= "</tr>";
					}
					if(count($str_purview) >=4)
					{
						if(($i+1)%4 == 0 && $i<count($str_purview)-1)
						{
							$show_purview.= "</tr><tr>";
						}
						if($i == count($str_purview)-1)
						{
							$show_purview.= "</tr>";
						}
					}
				}
				echo $show_purview."</table>";
			}
		}
		mysqli_free_result($group_result);
		unset($group_sql,$group_row,$group_rights);
	}
?>