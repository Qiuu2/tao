<?php
/*********************************
	ʵ��ý������
	����һ�δ���һ��
���ֲ�ˢ�� --ÿ�ζ�Ҫѡ��һ�κ��鷳��
*********************************/
//ý��
$media_id = "";
//����
$task_id = "";

$get_sort_id = "";

if(isset($_GET['id']))
{
	$get_sort_id = trim($_GET['id']);
	$get_sort_array = explode(",",$get_sort_id);
	
	$task_id = $get_sort_array[0];
	$media_id = $get_sort_array[1];
}
//��ȡ���ֵ
function read_max_value($task_id)
{
	require_once("inc/config.inc.php");
	mysqli_query($con,"LOCK TABLE mediaoftask WRITE");
	
	$sql_max = "SELECT max(sort) FROM mediaoftask WHERE mediaoftask.taskid = '$task_id' ORDER BY sort ASC";
	$result_max = mysqli_query($con,$sql_max) or die(mysqli_error($con));
	$row_max = mysqli_fetch_array($result_max);
	$max_sort = $row_max[0];
	
	mysqli_query($con,"UNLOCK TABLES");
	
	@mysqli_free_result($result_max);
	unset($row_max,$sql_max);
	return $max_sort;
}
//��ȡ��Сֵ
function read_min($task_id)
{
	require_once("inc/config.inc.php");
	mysqli_query($con,"LOCK TABLE mediaoftask WRITE");
	
	$sql_min = "SELECT min(sort) FROM mediaoftask WHERE mediaoftask.taskid = '$task_id' ORDER BY sort ASC";
	$result_min = mysqli_query($con,$sql_min) or die(mysqli_error($con));
	$row_min = mysqli_fetch_array($result_min);
	$min_sort = $row_min[0];
	
	mysqli_query($con,"UNLOCK TABLES");
	
	@mysqli_free_result($result_min);
	unset($row_min,$sql_min);
	return $min_sort;
}
//����
function up_move($task_id,$media_id)
{
	require_once("inc/config.inc.php");
	mysqli_query($con,"LOCK TABLES mediaoftask WRITE");
	
	$sql_up = "SELECT sort FROM mediaoftask WHERE mediaoftask.taskid = '$task_id' AND mediaoftask.mediaid = '$media_id'";
	$result_up = mysqli_query($con,$sql_up) or die(mysqli_error($con));
	$row_up = mysqli_fetch_array($result_up);
	$curr_sort = $row_up['sort'];
	
	if($curr_sort <= read_min($task_id))
	{
		//ʲô������
	}
	else
	{
		//��С����
		$sql_sort = "SELECT taskid,mediaid,sort FROM mediaoftask WHERE mediaoftask.taskid = '$task_id' ORDER BY sort ASC";
		$result_sort = mysqli_query($con,$sql_sort) or die(mysqli_error($con));
		for($i=0; $i<mysqli_num_rows($result_sort); $i++)
		{
			if(mysqli_data_seek($result_sort,$i))
			{
				$curr_info = mysql_fetch_row($result_sort);
				if($curr_info[0] == $task_id && $curr_info[1] == $media_id)
				{
					mysqli_data_seek($result_sort,$i-1);
					$pre_info = mysql_fetch_row($result_sort);
					$pre_taskid = $pre_info[0];
					$pre_mediaid = $pre_info[1];
					$pre_sort = $pre_info[2];
					//���µ�ǰ��
					mysqli_query($con,"UPDATE mediaoftask SET sort = '$pre_sort' WHERE mediaoftask.taskid = '$task_id' AND mediaoftask.mediaid = '$media_id'") or die(mysqli_error($con));
					//����ǰһ��
					mysqli_query($con,"UPDATE mediaoftask SET sort = '$curr_sort' WHERE mediaoftask.taskid = '$pre_taskid' AND mediaoftask.mediaid = '$pre_mediaid'") or die(mysqli_error($con));
					
					break;
				}
			}
		}
		@mysqli_free_result($result_sort);
		unset($sql_sort,$curr_info,$pre_info,$pre_sort);
	}
	
	mysqli_query($con,"UNLOCK TABLES");
	
	@mysqli_free_result($result_up);
	unset($sql_up,$row_up,$curr_sort);
}
//����
function down_move($task_id,$media_id)
{
	require_once("inc/config.inc.php");
	mysqli_query($con,"LOCK TABLES  mediaoftask WRITE");
	
	$sql_up = "SELECT sort FROM mediaoftask WHERE mediaoftask.taskid = '$task_id' AND mediaoftask.mediaid = '$media_id'";
	$result_up = mysqli_query($con,$sql_up) or die(mysqli_error($con));
	$row_up = mysqli_fetch_array($result_up);
	$curr_sort = $row_up['sort'];
	
	if($curr_sort >= read_max($task_id))
	{
		//ʲô������
	}
	else
	{
		//��С����
		$sql_sort = "SELECT taskid,mediaid,sort FROM mediaoftask WHERE mediaoftask.taskid = '$task_id' ORDER BY sort ASC";
		$result_sort = mysqli_query($con,$sql_sort) or die(mysqli_error($con));
		for($i=0; $i<mysqli_num_rows($result_sort); $i++)
		{
			if(mysqli_data_seek($result_sort,$i))
			{
				$curr_info = mysql_fetch_row($result_sort);
				if($curr_info[0] == $task_id && $curr_info[1] == $media_id)
				{
					mysqli_data_seek($result_sort,$i+1);
					$next_info = mysql_fetch_row($result_sort);
					$next_taskid = $pre_info[0];
					$next_mediaid = $pre_info[1];
					$next_sort = $pre_info[2];
					//���µ�ǰ��
					mysqli_query($con,"UPDATE mediaoftask SET sort = '$next_sort' WHERE mediaoftask.taskid = '$task_id' AND mediaoftask.mediaid = '$media_id'") or die(mysqli_error($con));
					//����ǰһ��
					mysqli_query($con,"UPDATE mediaoftask SET sort = '$curr_sort' WHERE mediaoftask.taskid = '$next_taskid' AND mediaoftask.mediaid = '$next_mediaid'") or die(mysqli_error($con));
					
					break;
				}
			}
		}
		@mysqli_free_result($result_sort);
		unset($sql_sort,$curr_info,$pre_info,$next_sort);
	}
	
	mysqli_query($con,"UNLOCK TABLES");
	
	@mysqli_free_result($result_up);
	unset($sql_up,$row_up,$curr_sort);	
}
//�ö�
function first_move($task_id,$media_id)
{
	require_once("inc/config.inc.php");
	mysqli_query($con,"LOCK TABLES  mediaoftask WRITE");
	$sql_curr = "SELECT sort FROM mediaoftask WHERE mediaoftask.taskid = '$task_id' AND mediaoftask.mediaid = '$media_id'";
	$result_curr = mysqli_query($con,$sql_curr) or die(mysqli_error($con));
	$row_curr = mysqli_fetch_array($result_curr);
	$curr_sort = $row_curr['sort'];
	@mysqli_free_result($result_curr);
	unset($row_curr,$sql_curr);
	
	$min_sort = read_min($task_id);
	
	if($curr_sort > $min_sort)
	{
		$sql_first = "select mediaid, taskid from mediaoftask where mediaoftask.taskid = '$task_id' and mediaoftask.sort = '$min_sort'";
		$result_first = mysqli_query($con,$sql_first) or die(mysqli_error($con));
		$row_first = mysqli_fetch_array($result_first);
		$first_mediaid = $row_first['mediaid'];
		$first_taskid = $row_first['taskid'];
		@mysqli_free_result($result_first);
		unset($row_first,$sql_first);
		//���µ�ǰ
		mysqli_query($con,"UPDATE mediaoftask SET sort = '$min_sort' WHERE mediaoftask.taskid = '$task_id' AND mediaoftask.mediaid = '$media_id'");
		//������ǰ
		mysqli_query($con,"UPDATE mediaoftask SET sort = '$curr_sort' WHERE mediaoftask.taskid = '$first_taskid' AND mediaoftask.mediaid = '$first_mediaid'");
	}
	mysqli_query($con,"UNLOCK TABLES");
}
//ֵβ
function end_move($task_id,$media_id)
{
	require_once("inc/config.inc.php");
	mysqli_query($con,"LOCK TABLES  mediaoftask WRITE");
	$sql_curr = "SELECT sort FROM mediaoftask WHERE mediaoftask.taskid = '$task_id' AND mediaoftask.mediaid = '$media_id'";
	$result_curr = mysqli_query($con,$sql_curr) or die(mysqli_error($con));
	$row_curr = mysqli_fetch_array($result_curr);
	$curr_sort = $row_curr['sort'];
	@mysqli_free_result($result_curr);
	unset($row_curr,$sql_curr);
	
	$max_sort = read_max_value($task_id);
	
	if($curr_sort < $max_sort)
	{
		$sql_first = "select mediaid, taskid from mediaoftask where mediaoftask.taskid = '$task_id' and mediaoftask.sort = '$max_sort'";
		$result_first = mysqli_query($con,$sql_first) or die(mysqli_error($con));
		$row_first = mysqli_fetch_array($result_first);
		$last_mediaid = $row_first['mediaid'];
		$last_taskid = $row_first['taskid'];
		@mysqli_free_result($result_first);
		unset($row_first,$sql_first);
		//���µ�ǰ
		mysqli_query($con,"UPDATE mediaoftask SET sort = '$max_sort' WHERE mediaoftask.taskid = '$task_id' AND mediaoftask.mediaid = '$media_id'");
		//������ǰ
		mysqli_query($con,"UPDATE mediaoftask SET sort = '$curr_sort' WHERE mediaoftask.taskid = '$last_taskid' AND mediaoftask.mediaid = '$last_mediaid'");
	}
	mysqli_query($con,"UNLOCK TABLES");
}
//�����������
function output_html($task_id)
{
	require_once("inc/config.inc.php");
	mysqli_query($con,"LOCK TABLES mediaoftask WRITE,media WRITE,filefolder WRITE");
	
	$output_html = "<table width=\"98%\" border=\"0\" cellpadding=\"2\" cellspacing=\"1\"  align=\"center\" name=\"sorttable\" id=\"sorttable\">";
	$output_html.= "<tr align=\"center\" class=\"pagetablestyle\" height=\"22\">";
  $output_html.= "<td width=\"5%\" align=\"center\" valign=\"middle\" nowrap=\"nowrap\"><strong>���</strong></td>";
  $output_html.= "<td width=\"15%\" align=\"center\" valign=\"middle\" nowrap=\"nowrap\"><strong>ý������</strong></td>";
  $output_html.= "<td width=\"10%\" align=\"center\" valign=\"middle\" nowrap=\"nowrap\"><strong>ý���С</strong></td>";
  $output_html.= "<td width=\"10%\" align=\"center\" valign=\"middle\" nowrap=\"nowrap\"><strong>ý������</strong></td>";
  $output_html.= "<td width=\"15%\" align=\"center\" valign=\"middle\" nowrap=\"nowrap\"><strong>�ļ���</strong></td></tr>";

	//��С����
	$sql_html = "SELECT mediaid FROM mediaoftask WHERE mediaoftask.taskid = '$task_id' ORDER BY sort ASC";
	$result_html = mysqli_query($con,$sql_html) or die(mysqli_error($con));
	while($row_html = mysqli_fetch_array($result_html))
	{
		$output_html.= "<tr>";
		$sql_media = "select media.id, media.name medianame, size, typeid, filefolder.name foldername from media,filefolder ";
		$sql_media.= "where media.id = '$row_html[mediaid]' and filefolder.id = media.folderid ";
		$result_media = mysqli_query($con,$sql_media) or die(mysqli_error($con));
		if($row_media = mysqli_fetch_array($result_media))
		{
			$output_html.= "<td><INPUT type=radio name=\"id\" id=\"id\" value=\"".$task_id.",".$row_media['id']."\"></td><td>".$row_media['medianame']."</td><td>".$row_media['size']."</td><td>".$row_media['typeid']."</td><td>".$row_media['foldername']."</td>";
		}
		$output_html.= "</tr>";
		@mysqli_free_result($result_media);
		unset($row_media,$sql_media);
	}
	$output_html.= "</table>";
	@mysqli_free_result($result_html);
	unset($row_html,$sql_html);
	mysqli_query($con,"UNLOCK TABLES");
	echo $output_html;
	unset($output_html);
}
?>