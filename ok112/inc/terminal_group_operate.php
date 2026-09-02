<?php
function free_data_resource($data_result,$data_row="",$data_sql="")
{
	@mysql_result($data_result);
	
	unset($data_row,$data_sql);
}

function insert_group($sql)
{
	global $con;
	mysqli_query($con,$sql) or die(mysqli_error($con));
}
//更新分区
function update_group($sql)
{
	global $con;
	mysqli_query($con,$sql) or die(mysqli_error($con));
}
//删除分区并删除分区终端
function delet_group($sql)
{
	//删除分区
		global $con;
	mysqli_query($con,$sql) or die(mysqli_error($con));
}
//获取分区信息
function select_group($group_sql)
{
	global $con;
	$group_info = "";
	
	$group_result = mysqli_query($con,$group_sql) or die(mysqli_error($con));
	
	while($group_row = mysqli_fetch_array($group_result))
	{
		$group_info[] = array("streamid"=>$group_row['streamid'],"NAME"=>$group_row['NAME'],
								"info"=>$group_row['info'],"createtime"=>$group_row['createtime']);
	}
	
	return $group_info;
}
//获取分区的终端ID
function sel_g_of_t_id($group_id)
{
	global $con;
	$g_of_t_ids = "";
	
	$t_of_g_sql = "SELECT terminalid FROM terminalofgroup WHERE terminalofgroup.groupid = '$group_id'";
	
	$group_result = mysqli_query($con,$t_of_g_sql) or die(mysqli_error($con));
	
	while($group_row = mysqli_fetch_array($group_result))
	{
		$g_of_t_ids[] = $group_row['terminalid'];
	}
	
	return $g_of_t_ids;
}
?>