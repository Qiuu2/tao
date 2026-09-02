<?php
	header("content-type:text/html;charset=utf-8");
	require_once('inc/config.inc.php');
	$device="";
	if(isset($_GET['device']))
	{
		$device = trim($_GET['device']);
	}

	$id=0;
	if(isset($_GET['id']))
	{
		$id = trim($_GET['id']);
	}
	
	$selected=0;
	if(isset($_GET['selected']))
	{
		$selected = trim($_GET['selected']);
	}
	
	
	$deviceflag=0;
	$sql = "SELECT * FROM centralctrl WHERE terminalid='$id'";
	$result = mysqli_query($con,$sql) or die(mysqli_error($con));
	if($row = mysqli_fetch_array($result))
	{
		$deviceflag=1;
		if($device=="one")  //һ
		{
			$sql="UPDATE centralctrl SET systemstate = '$selected' WHERE centralctrl.terminalid ='$id'";
		}
		else if($device=="two")
		{
		$sql="UPDATE centralctrl SET dormancy = '$selected' WHERE centralctrl.terminalid ='$id'";
		}
		else if($device=="three")
		{
		$sql="UPDATE centralctrl SET projectionstate = '$selected' WHERE centralctrl.terminalid ='$id'";
		}
		else if($device=="four")
		{
		$sql="UPDATE centralctrl SET pcstate = '$selected' WHERE centralctrl.terminalid ='$id'";
		}
		else if($device=="five")
		{
		$sql="UPDATE centralctrl SET volstate = '$selected' WHERE centralctrl.terminalid ='$id'";
		}
		else if($device=="six")
		{
		$sql="UPDATE centralctrl SET projectionscreenstate = '1' WHERE centralctrl.terminalid ='$id'";	
		}
		else if($device=="serven")
		{
			if($row['volume']<=95)
			{
				$setvolume=$row['volume']+5;
				$sql="UPDATE centralctrl SET volume = '$setvolume' WHERE centralctrl.terminalid ='$id'";	
			}
			else
				$deviceflag=2;
		}
		else if($device=="eight")
		{
			$sql="UPDATE centralctrl SET projectionscreenstate = '0' WHERE centralctrl.terminalid ='$id'";	
		}
		else if($device=="night")
		{
			if($row['volume']>=5)
			{
				$setvolume=$row['volume']-5;
				$sql="UPDATE centralctrl SET volume = '$setvolume' WHERE centralctrl.terminalid ='$id'";	
			}
			else
				$deviceflag=3;
		}
		else if($device=="ten")
		{
			$sql="UPDATE centralctrl SET showcase = '$selected' WHERE centralctrl.terminalid ='$id'";
		}
		else if($device=="ele")
		{
		$sql="UPDATE centralctrl SET notebook = '$selected' WHERE centralctrl.terminalid ='$id'";
		}
		else if($device=="twort")
		{
		$sql="UPDATE centralctrl SET computer = '$selected' WHERE centralctrl.terminalid ='$id'";
		}
		else if($device=="thirt")
		{
		$sql="UPDATE centralctrl SET hdmi = '$selected' WHERE centralctrl.terminalid ='$id'";
		}
		else if($device=="eighth")
		{
		$sql="UPDATE centralctrl SET power2 = '$selected' WHERE centralctrl.terminalid ='$id'";
		}
		else if($device=="nighth")
		{
		$sql="UPDATE centralctrl SET power1 = '$selected' WHERE centralctrl.terminalid ='$id'";
		}
		else if($device=="thothr")
		{
		$sql="UPDATE centralctrl SET mix_preced = '$selected' WHERE centralctrl.terminalid ='$id'";
		}
		else if($device=="fourth")
		{
			if($row['net_vol']<=95)
			{
				$netvolume=$row['net_vol']+5;
				$sql="UPDATE centralctrl SET net_vol = '$netvolume' WHERE centralctrl.terminalid ='$id'";	
			}
			else
				$deviceflag=2;
		}
		else if($device=="fiveth")
		{
			if($row['net_vol']>=5)
			{
				$netvolume=$row['net_vol']-5;
				$sql="UPDATE centralctrl SET net_vol = '$netvolume' WHERE centralctrl.terminalid ='$id'";	
			}
			else
				$deviceflag=3;
		}
		else if($device=="sixth")
		{
			if($row['mic_vol']<=95)
			{
				$micvolume=$row['mic_vol']+5;
				$sql="UPDATE centralctrl SET mic_vol = '$micvolume' WHERE centralctrl.terminalid ='$id'";	
			}
			else
				$deviceflag=2;
		}
		else if($device=="serventh")
		{
			if($row['mic_vol']>=5)
			{
				$micvolume=$row['mic_vol']-5;
				$sql="UPDATE centralctrl SET mic_vol = '$micvolume' WHERE centralctrl.terminalid ='$id'";	
			}
			else
				$deviceflag=3;
		}
	
		mysqli_query($con,$sql) or die(mysqli_error($con));
		unset($sql);
	}
	echo $deviceflag;
	exit;
	
?>