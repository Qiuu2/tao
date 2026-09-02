					var te = 0;
					
					var states = 0;
					var get_terst ="";
					var get_inid ="";
					var get_id ="";
					 var get_position ="";
					  var get_position2 ="";
					function toncheck(id, state) 
					{
					
					 te = id;
				     var get_ids="";
                    //  var get_position4="";
					  var treeItemText = "";
					 // var get_text2 = "IPπ¶∑≈";
					     var get_text2=trim("<{$collect_task_add.amplifier|capitalize}>");
				      var get_text3 = new RegExp(get_text2);
					  treeItemText = tree3.getItemText(id);
					//  var get_amplifier = "IP«∞÷√";
					    var get_amplifier = trim("<{$collect_task_add.lead|capitalize}>");
				      var get_amplifier2 = new RegExp(get_amplifier);
					if(id.length==8||id.length==9||id.length==10)
					{
					
					}
					else
					{
					
					
					 
					 if(state ==1)
					 {
					 
					    document.getElementById('lead').style.display = "none";
						//  get_id = get_id.replace(te,"");
					//   document.getElementById('get_id').value = trim(get_id.toString()); 
				
					
					   
						
						get_inid+=te+'|';
						var position =new Array;
						var position2 =new Array;
						var position3 =new Array;
						for(z=0;z<get_inid.length;z++)
						{
						//alert(z);
						if(get_inid.substring(z,z+2)=="::")
						{
						
						
						position=z+2;
						
						
						
						}
						if(get_inid.substring(z,z+1)=="|")
						{
						position2=z;
						if(position2-position==1)
						{
					     position3+=0;
						 }
						 else
						 {
						position3+=position2-position;
						}
						}
						
						}
						get_position =position3;
					
							//alert(get_position);
							//document.getElementById('get_inid').value = trim(get_inid.toString());
						
						//alert(document.getElementById('get_inid').value);	
						
					//  alert(get_inid.split("stream_"));
					 }
					 else if(state==0)
					 {
						var get_te="";
						
						
						
					
					 for(i=0;i<get_position.length;)
						{
						get_te =te.substring(10).length;
					 //   alert(get_te);
						if(get_te ==1)
						{
						get_te =0;
						}
                       
						if(get_position.substring(i,i+1)==get_te)
						{
						var get_ids =  get_position.substring(i,i+1);
							
						get_position = get_position.replace(get_ids,"");
						
						}
						  i+=1;  
					}
					//	get_id +=te;
						//document.getElementById('get_id').value = trim(get_id.toString());
					
						 get_inid = get_inid.replace(te+'|',"");
					  // document.getElementById('get_inid').value = trim(get_inid.toString()); 
					
				
					 }
					  }
					document.getElementById('get_inid').value = trim(get_inid.toString()); 
					document.getElementById('get_position').value = trim(get_position.toString());
				  
			 if((treeItemText.search(get_text3)!= -1)||(treeItemText.search(get_amplifier2)!= -1))
				{
				if(state == 1)
				 {
				  if(document.all)
						{ 
							
							window.event.cancelBubble = true;   
						}
						else
						{
							event.stopPropagation();
						}
						var mouse_obj_xy = get_mouse_coordinates();
						
						get_div_obj('lead').style.left = mouse_obj_xy.x+180;
						get_div_obj('lead').style.top = mouse_obj_xy.y-20;
						get_div_obj('lead').style.display = "block";
					     states = state ;
						
						get_terst+=te+'|';
						
						
				 }
				 else if(state==0)
				 {
			       get_terst = get_inid.replace(te+'|',"");
			     
				// alert(get_terminal);
				   if(document.getElementById('lead').style.display == "block")
							{
								document.getElementById('lead').style.display = "none";

							}
				  states = state ;
				 // alert(get_terminal);
				  var bit_position =new Array;
				  var bit_position2 =new Array;
				  var bit_position3 =new Array;
				 for(z=0;z<get_terminal.length;z++)
				 {
				   if(get_terminal.substring(z,z+2)=="::")
						{
						bit_position=z+2;
						
						
						}
					if(get_terminal.substring(z,z+1)=="|")
					{
					
					bit_position2=z;
				
				
					bit_position3+=bit_position2-bit_position;
				
					
					}
					
				
				 }
			     var te_len="";
				 var get_te="";
				 var te_len2="";
				 //  alert(bit_position3);
				get_te="["+te+"|";
					  
				for(l=0;l<get_terminal.length;)
				{	
				//	alert(l);
						
						for(i=0;i<bit_position3.length;)
						{
							
						  //  alert(get_te);
						 	te_len = bit_position3.substring(i,i+1);
						//  te_len = te.substring(10).length;
						  // alert(te_len);
						  // alert(get_terminal);
						  
						  
							//alert(l+12+parseInt(te_len));
							te_len2 =get_terminal.substring(l,l+12+parseInt(te_len));
							// alert(te_len2);
							// alert(get_te);
							if(get_terminal.substring(l,l+12+parseInt(te_len))==get_te)
							{
						   
								
								var get_terminals =  get_terminal.substring(l,l+12+parseInt(te_len)+12);
								
								get_terminal = get_terminal.replace(get_terminals,"");
								 document.getElementById('get_terminal').value = trim(get_terminal.toString());
								 
								l = get_terminal.length;
								break;
							}
							i+=1;
						}
						for(j =0;j<get_terminal.length-l;j++)
						{
							//alert(get_terminal.substring(l+j,l+j+1));
					    	if(get_terminal.substring(l+j,l+j+1)==']')
							{
								l+=j+1;
								break;
							}
						}
						if(j>=get_terminal.length-l)
							break;
				}


				 } 
				
			  
				 }

			};
					

			var get_terminal="";
			function set_task_volume_prepose()
			{
            
			   var get_prepose ="";
				
	                var getItem=te;
				
					if(getItem==null||getItem=="")
					{
						//alert(states);
						alert("<{$collect_task_add.select_broadcast_task|capitalize}>");
						return void(0);
					}
					else
					{
						if(window.confirm("<{$collect_task_add.suspending_task|capitalize}>"))
						{
							
							for(i=0;i<document.getElementsByName('lead2').length;i++)
							{
							   if(document.getElementsByName('lead2')[i].checked)
							   {
								  if(get_prepose=="")
									{
										get_prepose+=1;
									}
									else
									{
										get_prepose+=","+1;
									}
									

							   }
							   else
							   {
							   if(get_prepose=="")
									{
										get_prepose+=0;
									}
									else
									{
										get_prepose+=","+0;
									}
							   }
							   
							//  if(document.getElementsByName('IP_amplifier')[i].checked)
							 // {
							  
							//  }
							}
						//	alert(getItem);
						//	alert(get_prepose);
						get_prepose = get_prepose.concat(0,0,0,0);
						
						var get_ter = [[getItem]+'|'+[get_prepose]];
								 
					 get_terminal = get_terminal +'['+ [[getItem]+'|'+[get_prepose]] +']';
				    // alert(get_ter);
						 //  alert(get_terst);
							alert(get_terminal);
                   
				      
					

                          document.getElementById('get_terminal').value = trim(get_terminal.toString());
						 //   alert(get_terminal);
						//  alert(document.getElementById('get_terminal').value);
						   document.getElementById('lead').style.display = "none";
							//alert(get_terminal);
											
						}
						else
						{
							return void(0);
						}
					}
					
			
             };
		
				
		 function disappear_volume_div_prepose()
				{
					if(document.getElementById('lead').style.display == "block")
					{
						document.getElementById('lead').style.display = "none";
					}
				};