var sel_item_seq = new Array();
var get_all_item_id = "";
var dir ="dir";
var get_all_item_txt = new Array();
function push_item_sequence(item_id)
{
    sel_item_seq.push(item_id);
}
function pop_item_sequence(start_pos)
{
   sel_item_seq.splice(start_pos,1);
}	
function get_select_item_check(id,state)
{
    if(state == 1)
    {
        var switch_con = 0;
        if(myTree.hasChildren(id)> 0)
        {
              var child_id =  myTree.getAllSubItems(id);
            
              var ch_id_array = child_id.split(",");
              
              for(var x=0; x<ch_id_array.length; x++)
              {
                  if(ch_id_array[x].search(dir)!=0)
				{
				for(var y=0; y<sel_item_seq.length; y++)
                {
                    if(sel_item_seq[y] == ch_id_array[x])
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
        else if(myTree.hasChildren(id)== 0)
        {
            push_item_sequence(id);
        }
    }
    else
    {
        if(myTree.hasChildren(id)> 0)
        {
          var child_id = myTree.getAllSubItems(id);
          
          var ch_id1_array = child_id.split(",");

          for(var y1=0; y1<ch_id1_array.length; y1++)
          {
             for(var x1=0; x1<sel_item_seq.length; x1++)
              {
                if(sel_item_seq[x1] == ch_id1_array[y1])
                {
                    pop_item_sequence(x1); 
                    x1--;
                }
              }
          }
        }
        else if(myTree.hasChildren(id)== 0)
        {
            for(var x2=0; x2<sel_item_seq.length; x2++)
            {
               if(sel_item_seq[x2] == id)
               {
                    pop_item_sequence(x2); 
               }
            }
        }
    }
    for(var x in get_all_item_id)
    {
        myTree.setItemText(get_all_item_id[x],get_all_item_txt[x],"");   
    }
    for(var x in sel_item_seq)
    {
       myTree.setItemText(sel_item_seq[x],"<font color='red'><b>"+(parseInt(x)+parseInt(1))+"</b>-|</font>"+myTree.getItemText(sel_item_seq[x]),"");        
    }
};
		
var getmediafileid="";
function tonclick(id) {

	if(id.substring(0,4)=="dir_")
	{
		getmediafileid=id.replace("dir_","");
	}

};




