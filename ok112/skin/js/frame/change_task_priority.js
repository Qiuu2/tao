document.getElementById('left_image').onmouseover = function ()
													{ 
															document.getElementById('left_image').src  = "skin/images/frame/priority_left.gif"; 
																
															document.getElementById('left_image').style.cursor = "hand";	
													};

document.getElementById('left_image').onmouseout = function ()
													{ 
															document.getElementById('left_image').src  = "skin/images/frame/priority_left1.gif";
																
															document.getElementById('left_image').style.cursor = "";
													};

document.getElementById('right_image').onmouseover = function ()
													{ 
															document.getElementById('right_image').src  = "skin/images/frame/priority_right1.gif"; 
															
															document.getElementById('right_image').style.cursor = "hand";
													};

document.getElementById('right_image').onmouseout = function ()
													{ 
															document.getElementById('right_image').src  = "skin/images/frame/priority_right.gif"; 
															
															document.getElementById('right_image').style.cursor = "";
													};

function Increase_task_priority()
{
    var task_priority_value = document.getElementById('task_priority_text').value;
    
    task_priority_value = task_priority_value.replace(/(^\s*)|(\s*$)/g,"");
    
    var regu = /^[-]{0,1}[0-9]{1,}$/; 
    
   if(regu.test(task_priority_value) )
   {
       if(task_priority_value == 9)
        {
            document.getElementById('task_priority_text').value = 9 ;
        }
        else
        {
            task_priority_value++ ;
        
            document.getElementById('task_priority_text').value = task_priority_value;
        } 
   }
}

function Lower_priority_tasks()
{
    var task_priority_value = document.getElementById('task_priority_text').value;
    
    task_priority_value = task_priority_value.replace(/(^\s*)|(\s*$)/g,"");
    
    var regu = /^[-]{0,1}[0-9]{1,}$/; 
    
   if(regu.test(task_priority_value) )
   {
        if(task_priority_value == 0)
        {
            document.getElementById('task_priority_text').value = 0 ;
        }
        else
        {
            task_priority_value--;
        
            document.getElementById('task_priority_text').value = task_priority_value;
        }
   } 
}