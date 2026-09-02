
var hidden_display = true;//默认隐藏
var xmlhttp=null; 

function createXMLHttpRequest()
{ 
	if(window.ActiveXObject)
	{ 
		xmlhttp = new ActiveXObject("microsoft.XMLHTTP"); 
	} 
	else if(window.XMLHttpRequest)
	{ 
		xmlhttp = new XMLHttpRequest(); 
	}
	else
	{
		alert('不支持');
	}
} 

function ajax_set_task_volume(set_volume_server,volume_value,task_id)
{
   createXMLHttpRequest();
   
   if(task_id == "")
   {
		alert('请选择任务'); 
	
		return void(0);
   }
   xmlhttp.open( "get","set_task_volume_server.php?tasktype="+set_volume_server+"&volume="+volume_value+"&task_id="+task_id+"",true );
   
   
   xmlhttp.onreadystatechange = function()
   {
      if( xmlhttp.readyState == 4 )
      {
         if( xmlhttp.status == 200 )
         {
            if( xmlhttp.responseText == 0)
            {
				alert('失败');
            }
			else if(xmlhttp.responseText == 1)
			{
				alert('成功');
				
				get_div_obj('change_volume').style.display = "none";
				
				self.location.reload();
			}
         }
		 else
		 {
			alert('访问失败');	 
		 }
      }
   }
    xmlhttp.setRequestHeader( "If-Modified-Since", "0");
	xmlhttp.send(null);
}

function trim(str)
{
   str=str.replace(/(^\s*)|(\s*$)/g,""); 
   return str;
}

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

function mouse_click_position()
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
	
    get_div_obj('change_volume').style.left = mouse_obj_xy.x+10;
    get_div_obj('change_volume').style.top = mouse_obj_xy.y;
	get_div_obj('change_volume').style.display = "block";
}

function neverSliderBar(id, callback)
{
   with(this)
   {
      this.$            = document.getElementById || document.all;
      this.sldID        = id;
      this.sldObj       = null;
      this.instance     = this;
      this.barStyle     = "sliderBar";
      this.objStyle     = "sliderObj";
      this.btnStyle     = "sliderBtn";
      this.sldBar       = null;
      this.sldBtnL      = null;
      this.sldBtnR      = null;
      this.sldPoint     = null;
      this.sldMoved     = false;
      this.sldClicked   = false;
      this.callback     = callback;
      this.sldObjOffset = null;
      this.sldBarOffset = null;
      this.callbackArg  = Array.prototype.slice.call(arguments, 2);
      this.sldMax       = 100;
      this.sldIncrement = 5;
      this.sldPoint     = 0;
   }
}
;
neverSliderBar.prototype.setObjStyle=function(classname)
{
   with(this)
   {
      objStyle=classname;
   }
}
;
neverSliderBar.prototype.setMaxPoint=function(maxpoint)
{
   with(this)
   {
      sldMax=maxpoint;
   }
}
;
neverSliderBar.prototype.setBtnStyle=function(classname)
{
   with(this)
   {
      btnStyle=classname;
   }
}
;
neverSliderBar.prototype.setBarStyle=function(classname)
{
   with(this)
   {
      barStyle=classname;
   }
}
;
neverSliderBar.prototype.setStyle=function()
{
   with(this)
   {
      if (arguments[0]) barStyle=arguments[0];
      if (arguments[1]) btnStyle=arguments[1];
      if (arguments[2]) objStyle=arguments[2];
   }
}
;
neverSliderBar.prototype.setIncrement=function(increment)
{
   with(this)
   {
      if (isNaN(parseInt(increment))) return;
      sldIncrement = parseInt(increment);
   }
}
;
neverSliderBar.prototype.getSldPoint=function()
{
   with(this)
   {
      sldBarOffset = Offset(sldBar);
      sldObjOffset = Offset(sldObj);
      var sldObjwidth = sldObjOffset.w-sldBarOffset.w;
      var sldBarwidth = sldBarOffset.l-sldObjOffset.l;
      var sldLocation = parseInt(sldBarwidth/sldObjwidth*sldMax);
      return sldLocation;
   }
}
;
neverSliderBar.prototype.setSldPoint=function(point)
{
   with(this)
   {
      if (isNaN(parseInt(point))) return;
      if (point<0) point=0;
      if (point>sldMax) point=sldMax;
      var sldObjwidth  = sldObjOffset.w-sldBarOffset.w;
      var sldBarwidth  = sldBarOffset.l-sldObjOffset.l;
      sldPoint  = parseInt(point);
      var p = parseInt(sldPoint*sldObjwidth/sldMax)+sldObjOffset.l+1;
      sldBar.style.left = p;
      instance.getSldPoint();
   }
}
;

neverSliderBar.prototype.init=function()
{
   with(this)
   {
      if ($(sldID + '__BtnL') && $(sldID + '__BtnR') && $(sldID + '__Bar'))
      {
         sldBtnL = $(sldID + '__BtnL');
         sldBar  = $(sldID + '__Bar');
         sldBtnR = $(sldID + '__BtnR');
      }
      else
      {

         sldBtnL    = document.createElement("hidden");
         //sldBtnL.src = "skin/images/frame/leftbtn.jpg";
         sldBtnL.id = sldID + '__BtnL';
         
         sldBar     = document.createElement("image");
         sldBar.src = "skin/images/frame/bar.gif";
         sldBar.id  = sldID + '__Bar';
         
         sldBtnR    = document.createElement("hidden");
         //sldBtnR.src = "skin/images/frame/rightbtn.jpg";
         sldBtnR.id = sldID + '__BtnR';
         
         document.body.appendChild(sldBtnL);
         document.body.appendChild(sldBar);
         document.body.appendChild(sldBtnR);
      }
      //-------------------------------------------------------------------
      sldObj           = $(sldID);
      sldObj.className = objStyle;
      sldBarOffset     = Offset(sldBar);
      sldObjOffset     = Offset(sldObj);
      //-------------------------------------------------------------------
      sldBtnL.value          = "<<";
      sldBtnL.className      = btnStyle;
      sldBtnL.style.position = "absolute";
      //-------------------------------------------------------------------
      sldBtnR.value          = ">";
      sldBtnR.className      = btnStyle;
      sldBtnR.style.position = "absolute";
      //-------------------------------------------------------------------
      sldBar.className       = barStyle;
      sldBar.style.position  = "absolute";
      sldBar.style.top       = sldObjOffset.t;
      sldBar.style.height    = sldObjOffset.h;
      sldBar.style.left      = sldObjOffset.l;
      instance.fixed();
      //-------------------------------------------------------------------
      sldObj.onmousedown = function()
      {
         instance.handleObjBefore()
      }
      ;
      sldObj.onmouseup   = function()
      {
         instance.handleObjAfter()
      }
      ;
      //-------------------------------------------------------------------
      sldBtnL.onmousedown = function()
      {
         instance.handleBtnClick('l')
      }
      ;
      sldBtnR.onmousedown = function()
      {
         instance.handleBtnClick('r')
      }
      ;
      sldBtnL.onfocus     = function()
      {
         this.blur()
      }
      ;
      sldBtnR.onfocus     = function()
      {
         this.blur()
      }
      ;
      //-------------------------------------------------------------------
      sldBar.onmousedown = function()
      {
         instance.handleSldDragStart()
      }
      ;
      sldBar.onmousemove = function()
      {
         instance.handleSldDrag()
      }
      ;
      sldBar.onmouseup   = function()
      {
         instance.handleSldDragEnd()
      }
      ;
   }
}
;
neverSliderBar.prototype.fixed=function()
{
   with(this)
   {
      sldBarOffset = Offset(sldBar);
      sldObjOffset = Offset(sldObj);

      var sldBtnLOffset      = Offset(sldBtnL);
      sldBtnL.style.left     = sldObjOffset.l-sldBtnLOffset.w;
      sldBtnL.style.top      = sldObjOffset.t;
      sldBtnL.style.height   = sldObjOffset.h;
      //-------------------------------------------------------------------
      sldBtnR.style.left     = sldObjOffset.l+sldObjOffset.w;
      sldBtnR.style.top      = sldObjOffset.t;
      sldBtnR.style.height   = sldObjOffset.h;
      //-------------------------------------------------------------------
      sldBar.style.top       = sldObjOffset.t;
      sldBar.style.height    = sldObjOffset.h;
      //-------------------------------------------------------------------
      var p = sldBarOffset.l;
      if (p < sldObjOffset.l) sldBar.style.left=sldObjOffset.l;
      var w = sldObjOffset.l+sldObjOffset.w-sldBarOffset.w;
      if (p > w) sldBar.style.left = w;
      window.setTimeout(function()
      {
         instance.fixed()
      }
      , 10)
   }
}
;
neverSliderBar.prototype.applyArg=function()
{
   with(this)
   {
      if (typeof(callback)=='string') callback=eval(callback);
      if (typeof(callback)=='function')
      {
         var callbackArguments = [];
         for(var i=0; i<callbackArg.length; i++)
         callbackArguments[i] = callbackArg[i];
         callbackArguments.push(instance.getSldPoint());
         callback.apply(this, callbackArguments);
      }
      else
      {
         return;

      }
   }
}
;
neverSliderBar.prototype.handleObjBefore=function()
{
   with(this)
   {

   }
}
;
neverSliderBar.prototype.handleObjAfter=function()
{
   with(this)
   {

   }
}
;
neverSliderBar.prototype.handleBtnClick=function(direction)
{
   with(this)
   {
      direction = direction.toLowerCase();
      sldPoint=instance.getSldPoint();
      if(direction == 'l')
      {
         instance.setSldPoint(this.sldPoint-sldIncrement);
      }
      else if (direction=='r')
      {
         instance.setSldPoint(this.sldPoint+sldIncrement);
      }
      else
      {
         return alert('not valid argument ' +direction);
      }
      instance.applyArg();
      instance.getSldPoint();
   }
}
;
neverSliderBar.prototype.handleSldDragStart=function()
{
   with(this)
   {
      sldBar.setCapture();
      sldMoved = true;
      sldBar.onlosecapture = function()
      {
         sldMoved=false;
      }
      ;
      sldPoint = event.clientX-sldBarOffset.l;
   }
}
;
neverSliderBar.prototype.handleSldDrag=function()
{
   with(this)
   {
      if(!sldMoved) return;
      var p = event.clientX-sldPoint;
      if (p <= sldObjOffset.l)
      {
         sldBar.style.left = sldObjOffset.l;
      }
      else if (p >= (sldObjOffset.l+sldObjOffset.w-sldBarOffset.w))
      {
         sldBar.style.left = sldObjOffset.l+sldObjOffset.w-sldBarOffset.w;
      }
      else sldBar.style.left = p;
      instance.applyArg();
      instance.getSldPoint();
   }
}
;
neverSliderBar.prototype.handleSldDragEnd=function()
{
   with(this)
   {
      sldBar.releaseCapture();
      sldMoved=false;
   }
}
;
function Offset(e)
{
   var t = e.offsetTop;
   var l = e.offsetLeft;
   var w = e.offsetWidth;
   var h = e.offsetHeight;
   while(e=e.offsetParent)
   {
      t+=e.offsetTop;
      l+=e.offsetLeft;
   }
   return{t:t, l:l, w:w, h:h};
}

function callback(s)
{
   var $=document.getElementById;
   var color_r=r.getSldPoint();
   $("volume_value").innerHTML=s+color_r;  
}
r=new neverSliderBar("s1", callback,'');
r.sldMax=100;
r.setBtnStyle("r-sliderBtn");
r.setBarStyle("r-sliderBar");
r.setObjStyle("r-sliderObj");
r.init();

r.setIncrement(1);
r.setSldPoint(0);