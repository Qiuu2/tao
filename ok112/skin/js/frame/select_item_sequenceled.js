var sel_item_led = new Array();
var get_all_led_id = "";
var leddir ="dir";
var get_all_led_txt = new Array();
function push_item_sequence(item_id)
{
    sel_item_led.push(item_id);
}
function pop_item_sequence(start_pos)
{
   sel_item_led.splice(start_pos,1);
}	
function get_select_item_check(id,state)
{
	
    if(state == 1)
    {
        var switch_con = 0;
        if(tree1.hasChildren(id)> 0)
        {
              var child_id =  tree1.getAllSubItems(id);
            
              var ch_id_array = child_id.split(",");
              
              for(var x=0; x<ch_id_array.length; x++)
              {
                  if(ch_id_array[x].search(leddir)!=0)
				{
				for(var y=0; y<sel_item_led.length; y++)
                {
                    if(sel_item_led[y] == ch_id_array[x])
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
        }
        else if(tree1.hasChildren(id)== 0)
        {
            push_item_sequence(id);
        }
    }
    else
    {
        if(tree1.hasChildren(id)> 0)
        {
          var child_id = tree1.getAllSubItems(id);
          
          var ch_id1_array = child_id.split(",");

          for(var y1=0; y1<ch_id1_array.length; y1++)
          {
             for(var x1=0; x1<sel_item_led.length; x1++)
              {
                if(sel_item_led[x1] == ch_id1_array[y1])
                {
                    pop_item_sequence(x1); 
                    x1--;
                }
              }
          }
        }
        else if(tree1.hasChildren(id)== 0)
        {
            for(var x2=0; x2<sel_item_led.length; x2++)
            {
               if(sel_item_led[x2] == id)
               {
                    pop_item_sequence(x2); 
               }
            }
        }
    }
    for(var x in get_all_led_id)
    {
        tree1.setItemText(get_all_led_id[x],get_all_led_txt[x],"");   
    }
    for(var x in sel_item_led)
    {
       tree1.setItemText(sel_item_led[x],"<font color='red'><b>"+(parseInt(x)+parseInt(1))+"</b>-|</font>"+tree1.getItemText(sel_item_led[x]),"");        
    }
};
