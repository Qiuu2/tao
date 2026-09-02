<?php
header("content-type:text/html; charset=utf-8");

class get_mp3_info_class
{
	public function js_alert($msg)
	{
	   echo (
				   "\n<script language='javascript'>\n" .
				   "    <!-- \n" .
				   "    alert (\"$msg\");\n" .
				   "    // --> \n" .
				   "</script>\n"
			);
	}
	public function bstr2bin($input)
	{
	  if (!is_string($input)) return null; // Sanity check

	  $value = unpack('H*', $input);
	  
	  // Output binary representation
	  $value = str_split($value[1], 1);
	  $bin = '';
	  foreach ($value as $v)
	  {
	   $b = str_pad(base_convert($v, 16, 2), 4, '0', STR_PAD_LEFT);
	   
	   $bin .= $b;
	  }
	  
	  return $bin;
	}
	public function mp3info($filename)
	{
		$getsampleframe= array(
			"11"=>array("11"=>"384","10"=>"384","01"=>"0","00"=>"384"),
			"10"=>array("11"=>"1152","10"=>"1152","01"=>"0","00"=>"1152"),
			"01"=>array("11"=>"1152","10"=>"576","01"=>"0","00"=>"576")
		);
		$getsample= array(
		
			"00"=>array("11"=>"44100","10"=>"22050","01"=>"0","00"=>"11025"),
			"01"=>array("11"=>"48000","10"=>"24000","01"=>"0","00"=>"12000"),
			"10"=>array("11"=>"32000","10"=>"16000","01"=>"0","00"=>"8000"),
			"11"=>array("11"=>"0","10"=>"0","01"=>"0","00"=>"0")
		);
		$getbitrate=array(
		
			"0000"=>array("11"=>array("11"=>"0","10"=>"0","01"=>"0"),
						  "10"=>array("11"=>"0","10"=>"0","01"=>"0"),
						  "00"=>array("11"=>"0","10"=>"0","01"=>"0")
			),
			"0001"=>array(
							"11"=>array("11"=>"32","10"=>"32","01"=>"32"),
							"10"=>array("11"=>"32","10"=>"8","01"=>"8"),
							"00"=>array("11"=>"32","10"=>"8","01"=>"8")
			),
			"0010"=>array(
							"11"=>array("11"=>"64","10"=>"48","01"=>"40"),
							"10"=>array("11"=>"48","10"=>"16","01"=>"16"),
							"00"=>array("11"=>"48","10"=>"16","01"=>"16")
			),
			"0011"=>array(
							"11"=>array("11"=>"96","10"=>"56","01"=>"48"),
							"10"=>array("11"=>"56","10"=>"24","01"=>"24"),
							"00"=>array("11"=>"56","10"=>"24","01"=>"24")
			),
			"0100"=>array(
							"11"=>array("11"=>"128","10"=>"64","01"=>"56"),
							"10"=>array("11"=>"64","10"=>"32","01"=>"32"),
							"00"=>array("11"=>"64","10"=>"32","01"=>"32")
		
			),
			"0101"=>array(
							"11"=>array("11"=>"160","10"=>"80","01"=>"64"),
							"10"=>array("11"=>"80","10"=>"40","01"=>"40"),
							"00"=>array("11"=>"80","10"=>"40","01"=>"40")
		
			),
			"0110"=>array(
							"11"=>array("11"=>"192","10"=>"96","01"=>"80"),
							"10"=>array("11"=>"96","10"=>"48","01"=>"48"),
							"00"=>array("11"=>"96","10"=>"48","01"=>"48")
		
			),
			"0111"=>array(
							"11"=>array("11"=>"224","10"=>"112","01"=>"96"),
							"10"=>array("11"=>"112","10"=>"56","01"=>"56"),
							"00"=>array("11"=>"112","10"=>"56","01"=>"56")
		
			),
			"1000"=>array(
							"11"=>array("11"=>"256","10"=>"128","01"=>"112"),
							"10"=>array("11"=>"128","10"=>"64","01"=>"64"),
							"00"=>array("11"=>"128","10"=>"64","01"=>"64")
			),
			"1001"=>array(
							"11"=>array("11"=>"288","10"=>"160","01"=>"128"),
							"10"=>array("11"=>"144","10"=>"80","01"=>"80"),
							"00"=>array("11"=>"144","10"=>"80","01"=>"80")
			),
			"1010"=>array(
							"11"=>array("11"=>"320","10"=>"192","01"=>"160"),
							"10"=>array("11"=>"160","10"=>"96","01"=>"96"),
							"00"=>array("11"=>"160","10"=>"96","01"=>"96")
		
			),
			"1011"=>array(
							"11"=>array("11"=>"352","10"=>"224","01"=>"192"),
							"10"=>array("11"=>"176","10"=>"112","01"=>"112"),
							"00"=>array("11"=>"176","10"=>"112","01"=>"112")
		
			),
			"1100"=>array(
							"11"=>array("11"=>"384","10"=>"256","01"=>"224"),
							"10"=>array("11"=>"192","10"=>"128","01"=>"128"),
							"00"=>array("11"=>"192","10"=>"128","01"=>"128")
		
			),
			"1101"=>array(
							"11"=>array("11"=>"416","10"=>"320","01"=>"256"),
							"10"=>array("11"=>"224","10"=>"144","01"=>"144"),
							"00"=>array("11"=>"192","10"=>"144","01"=>"144")
		
			),
			"1110"=>array(
							"11"=>array("11"=>"448","10"=>"384","01"=>"320"),
							"10"=>array("11"=>"256","10"=>"160","01"=>"160"),
							"00"=>array("11"=>"256","10"=>"160","01"=>"160")
		
			),
			"1111"=>array(	
							"11"=>array("11"=>"128","10"=>"128","01"=>"128","00"=>"64"),
							"10"=>array("11"=>"128","10"=>"64","01"=>"64","00"=>"64"),
							"01"=>array("11"=>"128","10"=>"64","01"=>"64","00"=>"64"),
							"00"=>array("11"=>"128","10"=>"64","01"=>"64","00"=>"64")
			),
		
		
		);
		$getfilesize=filesize($filename);
		if (!$file = fopen($filename, "rb"))
	   {
		  $this->js_alert('Yo I cant find\\n$filename');
		  exit;
	   }
	   
		  while (!feof($file))
		  {
			$tmp=fread($file,"1");
			if(ord($tmp)==255)
			{
				$tmp=fread($file,"4");
				$tmp=$this->bstr2bin($tmp);
				 if (substr($tmp,0, 3)=="111")
				 {
					 if(substr($tmp,3, 2)!="01")
					 {
					
						if(substr($tmp, 12, 2)!="11")
						  break;
					  }
				 }
			}
		  
		  }

	
	   	$version = substr($tmp,3,2);
		
		$layer = substr($tmp,5,2);

		$crc = substr($tmp,7,1);
		$bitrateheader=substr($tmp,8,4);
		$sampleheader=substr($tmp,12,2);
		$padding=substr($tmp,14,1);
		
		if($layer=="11")
			$padding=4;
		$privatebit=substr($tmp,15,1);
		$channel=substr($tmp,16,2);

		$sample=$getsample[$sampleheader][$version];

		$bitrate=$getbitrate[$bitrateheader][$version][$layer];

		$sampleframe=$getsampleframe[$layer][$version];
		
		$getplaylength=floor($getfilesize*8/($bitrate*1000));
		
		if($getplaylength==0)
		{
			while(!feof($file))
			{
			
				$xing=fread($file,"1000");
		
				if(strpos($xing,"Xing")!=false)
				{
					
					$framelength=bindec($this->bstr2bin(substr($xing,strpos($xing,"Xing")+8,4)));
					$getplaylength=(1152*1/44100*$framelength);
					break;
				}
			//else
				//fseek($file,1000,SEEK_CUR);
			}
		}
		
		fclose($file);
		
			
			
	   $fred['filename']=$filename;
	   $fred['filesize']=$getfilesize;
	   $fred['seconds']=$getplaylength;
	   $fred['bitrate']=$bitrate;
	   $fred['sample']=$sample;
	   $fred['cmode']=$channel;
	   $fred['version']=$version;
	   $fred['layer']=$layer;
	   $fred['crc']=$crc;

	   return $fred;
	
	}

}


?>