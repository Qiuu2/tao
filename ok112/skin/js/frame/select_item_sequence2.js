var sel_item_seq3 = new Array();
				var get_all_item_id3 = "";
				var get_all_item_txt3 = new Array();
				var get_text = "";
				var get_text2 = "<{$media_task_add.Server|capitalize}>";
				var get_text3 = new RegExp(get_text2);
				var get_amplifier = "<{$media_task_add.amplifier|capitalize}>";
				var get_amplifier2 = new RegExp(get_amplifier);
				function get_mouse_coordinates()
				{
				   var eve = event||window.event;
				   if(eve.pageX)
				   {
					return {x:eve.pageX,y:eve.pageY};
				   }
				   else
				   {
					return {
								x:eve.clientX+document.body.scrollLeft - document.body.clientLeft,
								y:eve.clientY+document.body.scrollTop - document.body.clientTop
							};
				   }
				}
				function get_div_obj(str_id)
				{
					return document.getElementById(str_id);   
				}
				function push_item_sequence(item_id)
				{
					sel_item_seq3.push(item_id);
				}
				function pop_item_sequence(start_pos)
				{
				   sel_item_seq3.splice(start_pos,1);
				}	
				function get_select_item_check3(id,state,type)
				{
					if(state == 1)
					{
						var switch_con = 0;
						if(tree3.hasChildren(id)> 0)
						{
							  var child_id =  tree3.getSubItems(id);
							  
							  var ch_id_array = child_id.split(",");
							  
							  for(var x=0; x<ch_id_array.length; x++)
							  {
								for(var y=0; y<sel_item_seq3.length; y++)
								{
									if(sel_item_seq3[y] == ch_id_array[x])
									{
										switch_con = 1;
										continue;
									}
								}
								if(switch_con == 1)
								{
								  switch_con = 0;
								  continue;  
								} 
								push_item_sequence(ch_id_array[x]);
							  }
						}
						else if(tree3.hasChildren(id)== 0)
						{
							push_item_sequence(id);
						}
					}
					else
					{
						if(tree3.hasChildren(id)> 0)
						{
						  var child_id = tree3.getSubItems(id);
						  
						  var ch_id1_array = child_id.split(",");
				
						  for(var y1=0; y1<ch_id1_array.length; y1++)
						  {
							 for(var x1=0; x1<sel_item_seq3.length; x1++)
							  {
								if(sel_item_seq3[x1] == ch_id1_array[y1])
								{
									pop_item_sequence(x1); 
									x1--;
								}
							  }
						  }
						}
						else if(tree3.hasChildren(id)== 0)
						{
							for(var x2=0; x2<sel_item_seq3.length; x2++)
							{
							   if(sel_item_seq3[x2] == id)
							   {
									pop_item_sequence(x2); 
							   }
							}
						}
					}
			
				//	if(tree3.hasChildren(type)==3)
				//	{
					for(var x in sel_item_seq3)
					{
					  get_text=tree3.getItemText(sel_item_seq3[x]);
					  if(get_text.search(get_text3)!= -1)
					  {
					//	alert(get_text);		
						//tree3.setItemText(sel_item_seq[x],tree3.getItemText(sel_item_seq[x])+"<font color='red'><b>"+get_text+"</b>-|</font>","");    
						   if(document.all)
							{
								window.event.cancelBubble = true;   
							}
							else
							{
								event.stopPropagation();
							}
							var mouse_obj_xy = get_mouse_coordinates();
							
							get_div_obj('prepose').style.left = mouse_obj_xy.x+180;
							get_div_obj('prepose').style.top = mouse_obj_xy.y-30;
							get_div_obj('prepose').style.display = "block";
						    
					  }
					  
					    if(get_text.search(get_amplifier2)!= -1)
					  {
						//alert(get_text);		
						//tree3.setItemText(sel_item_seq[x],tree3.getItemText(sel_item_seq[x])+"<font color='red'><b>"+get_text+"</b>-|</font>","");    
						   if(document.all)
							{
								window.event.cancelBubble = true;   
							}
							else
							{
								event.stopPropagation();
							}
							var mouse_obj_xy = get_mouse_coordinates();
							
							get_div_obj('change_volume').style.left = mouse_obj_xy.x+180;
							get_div_obj('change_volume').style.top = mouse_obj_xy.y-30;
							get_div_obj('change_volume').style.display = "block";
						    
					  }
					  
					}
				//	}
				};