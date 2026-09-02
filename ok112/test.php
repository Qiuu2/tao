<?php
header("content-type:text/html; charset=utf-8");
class read_win_info
{
	function get_processor_id()
	{
		try
		{
			$win_obj=new COM("Winmgmts:");
			
			$win_obj_pro = $win_obj->InstancesOf("Win32_Processor");
			
			$processor_id = array();
			
			foreach($win_obj_pro as $pro_id)
			{
				$processor_id[] = $pro_id->ProcessorId;
			}
		}
		catch(Exception $e)
		{
			return 0;
		}
	return $processor_id;
	}
	
	//读硬盘序列号
	function get_hd_id()
	{
		try{
			$win_obj = new COM("Winmgmts:");
			
			$win_obj_pro = $win_obj->InstancesOf("Win32_DiskDrive");
			
			$hd_id = array();
			
			foreach($win_obj_pro as $str)
			{
				$hd_id[] = $str->Model;
			}
		}
		catch(Exception $e)
		{
			return 0;
		}
	return $hd_id;
	}
	//读网卡物理地址
	function get_eth_id()
	{
		try
		{
			@exec("ipconfig /all",$array);
			
			$eth_id = array();
			 
			for($i=0; $i<count($array); $i++)
			{ 
				if(eregi("Physical",$array[$i]))
				{ 
					$mac = explode(":",$array[$i]);
					 
					$eth_id[]= $mac[1]; 
				} 
			}
		}
		catch(Exception $e)
		{
			return 0;
		}
	return $eth_id;
	}
}

class read_linux_info
{
	function get_processor_id()
	{
		
	}
	function get_hd_id()
	{
		
	}
	
	function get_eth_id()
	{
		$first_pos = 0;
		
		$str_len = 0;
		try
		{
			@exec("ifconfig -a",$array);
			
			$eth_id = array();
			 
			for($i=0; $i<count($array); $i++)
			{ 
				if(eregi("HWaddr",$array[$i]))
				{ 
					
					$first_pos = strpos($array,"HWaddr");
					
					echo $first_pos;
					//$mac = explode(":",$array[$i]);
					 
					//$eth_id[]= $mac[1]; 
				} 
			}
		}
		catch(Exception $e)
		{
			return 0;
		}
	return $eth_id;
	}
}
  
phpinfo();
?>
