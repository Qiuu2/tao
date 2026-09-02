<?php
	header("content-type:text/html;charset=utf-8");
	
	require_once('inc/config.inc.php');

	$flag = 0;
	if(isset($_GET['flag']))
	{
		$flag = trim($_GET['flag']);
	}
	$searchtype ="";
	if(isset($_GET['searchtype']))
	{
		$searchtype = trim($_GET['searchtype']);
	}
	$searchvalue = 0;
	if(isset($_GET['searchvalue']))
	{
		$searchvalue = trim($_GET['searchvalue']);
	}
	
	
	
	if($flag==0)
	{
		if($searchtype=="terminalname")
		{
			$sql = "SELECT a.terminalid,b.terminalname,b.typeid,b.netstate,a.pcstate,a.projectionstate,a.volume,a.volstate,a.systemstate,a.projectionscreenstate,a.mix_preced,a.mic_vol,a.net_vol,a.dormancy,a.showcase ,a.notebook,a.computer,a.hdmi,a.power1,a.power2 FROM centralctrl as a,terminal as b WHERE b.id=a.terminalid AND b.typeid NOT IN(0) AND b.terminalname LIKE '%".trim($searchvalue)."%'";
		}
		else
			$sql = "SELECT a.terminalid,b.terminalname,b.typeid,b.netstate,a.pcstate,a.projectionstate,a.volume,a.volstate,a.systemstate,a.projectionscreenstate,a.mix_preced,a.mic_vol,a.net_vol,a.dormancy,a.showcase ,a.notebook,a.computer,a.hdmi,a.power1,a.power2 FROM centralctrl as a,terminal as b WHERE b.id=a.terminalid AND b.typeid NOT IN(0)";
		$result = mysqli_query($con,$sql) or die(mysqli_error($con));
		while($row = mysqli_fetch_array($result))
		{
	
				$array[]=array(
								"terminalid" => $row['terminalid'],
								"terminalname" => $row['terminalname'],	
								"typeid" => $row['typeid'],
								"netstate" => $row['netstate'],	   
								"pcstate" => $row['pcstate'],	//电脑开关
								"projectionstate" => $row['projectionstate'],	   //投影机开关
								"volume" => $row['volume'],				//音量
								"volstate" => $row['volstate'],	//音量开关
								"systemstate" => $row['systemstate'],	//系统开关
								"projectionscreenstate" => $row['projectionscreenstate'],	//投影幕升降
								"mix_preced" => $row['mix_preced'],       //混音优先
								"mic_vol" => $row['mic_vol'],         //话筒音量
								"net_vol" => $row['net_vol'],         //网络音量
								"dormancy" => $row['dormancy'],       //休眠
								"showcase" => $row['showcase'],       //展台
								"notebook" => $row['notebook'],       //笔记本
								"computer" => $row['computer'],         //电脑
								"hdmi" => $row['hdmi'],         //高清
								"power1" => $row['power1'],       //外控电源1
								"power2" => $row['power2'],       //外控电源2
						  );
			
		}
		echo json_encode($array);
		exit;
	}
	else if($flag==1)
	{
	
	
	}
?>