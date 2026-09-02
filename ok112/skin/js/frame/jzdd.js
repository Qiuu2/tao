/*************************** BEGIN ***************************/
/**
 * @author http://kanaifeixiang.blog.sohu.com/
 * @version 1.0
 *
 * 首先，调用 createGcjDhccFullScreen() 生成 fullScreen(全屏覆盖层)
 * 然后，
 * 调用 showGcjDhccFullScreen() 显示 fullScreen
 * 调用 hideGcjDhccFullScreen() 隐藏 fullScreen
 * 
 * ********** 变量说明 **********
 * path : 工程路径（该变量需在页面定义或赋值） var path = "<%=path%>";
 * gcjDhccFullScreenLoadingText : 设置 fullScreen 层的加载时显示的文字（可以是HTML）
 * gcjDhccFullScreenLoadingTextAlign : 设置 fullScreen 层的加载时显示的文字位于加载图片的位置：图片上方 up，下 down，左 left，右 right
 * gcjDhccFullScreenLoadingImgHAlign : 设置 fullScreen 层的加载图片的水平位置：左 left，中 center，右 right
 * gcjDhccFullScreenLoadingImgVAlign : 设置 fullScreen 层的加载图片的竖直位置：上 top，中 middle，下 bottom
 * gcjDhccFullScreenBKImgPath : 设置 fullScreen 层的背景图片路径
 * gcjDhccFullScreenLoadingImgPath : 设置 fullScreen 层的加载图片路径
 * gcjDhccFullScreenDocumentObj : 设置 fullScreen 层在那个页面显示
 *     本页面：var gcjDhccFullScreenDocumentObj = window.document;
 *     父页面：var gcjDhccFullScreenDocumentObj = window.parent.document;
 *     父页面的父页面：var gcjDhccFullScreenDocumentObj = window.parent.parent.document;
 *     依此类推。
 * 
 * 程序缺陷：gcjDhccFullScreenDocumentObj 现在只能设置在本页面，设置在父页面时无法隐藏 fullScreen（ hideGcjDhccFullScreen() 隐藏时会报错 ）
 * 
 */
var gcjDhccFullScreenLoadingText = '<font color="#ff0000">加载中 。。。</font>';
var gcjDhccFullScreenLoadingTextAlign = "center";
var gcjDhccFullScreenLoadingImgPath = "skin/images/frame/jzdd.gif";
var gcjDhccFullScreenLoadingImgHAlign = "center";
var gcjDhccFullScreenLoadingImgVAlign = "middle";
var gcjDhccFullScreenBKImgPath = "skin/images/frame/tmbj.png";
var gcjDhccFullScreenDocumentObj = window;

/**
 * 生成 fullScreen(全屏覆盖层)
 * 
 * loadingText = gcjDhccFullScreenLoadingText
 * loadingTextAlign = gcjDhccFullScreenLoadingTextAlign
 * loadingImgPath = gcjDhccFullScreenLoadingImgPath
 * imgHAlign = gcjDhccFullScreenLoadingImgHAlign
 * imgVAlign = gcjDhccFullScreenLoadingImgVAlign
 * bKImgPath = gcjDhccFullScreenBKImgPath
 * documentObj = gcjDhccFullScreenDocumentObj
 * 
 * 不需要改变的参数置空（值为 null ，不是字符串 "null" ）
 * 
 */
function createGcjDhccFullScreen(loadingText, loadingTextAlign, loadingImgPath, imgHAlign, imgVAlign, bKImgPath, documentObj)
{
 //初始化变量
 if(loadingText == null){ loadingText = gcjDhccFullScreenLoadingText; }
 if(loadingTextAlign == null){ loadingTextAlign = gcjDhccFullScreenLoadingTextAlign; }
 if(loadingImgPath == null){ loadingImgPath = gcjDhccFullScreenLoadingImgPath; }
 if(imgHAlign == null){ imgHAlign = gcjDhccFullScreenLoadingImgHAlign; }
 if(imgVAlign == null){ imgVAlign = gcjDhccFullScreenLoadingImgVAlign; }
 if(bKImgPath == null){ bKImgPath = gcjDhccFullScreenBKImgPath; }
 if(documentObj == null){ documentObj = gcjDhccFullScreenDocumentObj; }
 else{gcjDhccFullScreenDocumentObj = documentObj;}
 
 //判断 fullScreen 是否存在
 try{
  if(documentObj.document.getElementById("gcjDhccFullScreen")){ return null; }
 }
 catch(e){ }
 //开始生成 fullScreen
 var divObj = documentObj.document.createElement("div");
 divObj.setAttribute("id", "gcjDhccFullScreen");
 divObj.style.display = "none";  //不可见
 divObj.style.position = "absolute";  //浮动
 divObj.style.top = documentObj.document.body.scrollTop;
 //divObj.style.left = 0;
 divObj.style.align = "center";
 divObj.style.width = "100%";
 divObj.style.height = documentObj.document.body.clientHeight;
 divObj.style.backgroundImage = "url("+ bKImgPath +")";
 divObj.style.zIndex = 999;
 
 var loadingHtml = '<table width="100%" height="100%" align="'+ imgHAlign +'" valign="'+ imgVAlign +'" border="0" cellpadding="0" cellspacing="0">';
 loadingHtml += '<tr><td  align="'+ imgHAlign +'" valign="'+ imgVAlign +'">';
 //图片上方 up，下 down，左 left，右 right,中 center
  if(loadingTextAlign == "center"){
  loadingHtml +='<div  align="center" style="width:206px;height:60px;">';
  loadingHtml += '&nbsp;&nbsp;'+'<img src="skin/images/frame/loading.gif" height="60" />'+'&nbsp;&nbsp;';
  loadingHtml += '<div>';
 }
 else if(loadingTextAlign == "up"){
  loadingHtml += '<div align="'+ imgHAlign +'">'+ loadingText +'<div>';
  loadingHtml += '<img src="'+ loadingImgPath +'" />';
 }
 else if(loadingTextAlign == "down"){
  loadingHtml += '<img src="'+ loadingImgPath +'" />';
  loadingHtml += '<div align="'+ imgHAlign +'">'+ loadingText +'<div>';
 }
 else if(loadingTextAlign == "left"){
  loadingHtml += loadingText +'&nbsp;&nbsp;';
  loadingHtml += '<img src="'+ loadingImgPath +'" />';
 }
 else if(loadingTextAlign == "right"){
  loadingHtml += '<img src="'+ loadingImgPath +'" />';
  loadingHtml += '&nbsp;&nbsp;'+ loadingText;
 }
 loadingHtml += '</td></tr>';
 loadingHtml += '</table>';
 
 divObj.innerHTML = loadingHtml;
 
 //开始生成 iframe
 /*
 var iframeObj = documentObj.document.createElement("iframe");
 iframeObj.setAttribute("id", "gcjDhccFullScreenIframe");
 iframeObj.style.filter = "progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=0)";
 
 iframeObj.style.display = "none";  //不可见
 iframeObj.style.position = "absolute";  //浮动
 iframeObj.style.top = document.body.scrollTop;
 iframeObj.style.left = 0;
 iframeObj.style.width = document.body.clientWidth;
 iframeObj.style.height = document.body.clientHeight;
 iframeObj.style.allowtransparency ="true";
 iframeObj.style.backgroundcolor ="transparent";
 iframeObj.style.zIndex = 998;
 
 // body 里加入 iframe
 documentObj.document.body.appendChild(iframeObj);
 */
 // body 里加入 div
 documentObj.document.body.appendChild(divObj);
 documentObj.onscroll = function(){
				var scrollTop = documentObj.document.body.scrollTop;
				documentObj.document.getElementById("gcjDhccFullScreen").style.top = scrollTop + "px";
				//document.getElementById("gcjDhccFullScreenIframe").style.top = scrollTop + "px";
			}
}
//显示 fullScreen

function showGcjDhccFullScreen()
{
 try{
  gcjDhccFullScreenDocumentObj.document.getElementById("gcjDhccFullScreen").style.display = "block";
  //gcjDhccFullScreenDocumentObj.document.getElementById("gcjDhccFullScreenIframe").style.display = "block";
 }
 catch(e){ window.alert('showGcjDhccFullScreen'); }
}
//隐藏 fullScreen
function hideGcjDhccFullScreen()
{
 try{
  gcjDhccFullScreenDocumentObj.document.getElementById("gcjDhccFullScreen").style.display = "none";
  //gcjDhccFullScreenDocumentObj.document.getElementById("gcjDhccFullScreenIframe").style.display = "none";
 }
 catch(e){ window.alert('hideGcjDhccFullScreen'); }
}
function hideGcjDhccFullScreen1(ycdiv)
{
 try{
  ycdiv.document.getElementById("gcjDhccFullScreen").style.display = "none";
  //gcjDhccFullScreenDocumentObj.document.getElementById("gcjDhccFullScreenIframe").style.display = "none";
 }
 catch(e){ window.alert('hideGcjDhccFullScreen'); }
}
/**************************** END ****************************/
  
  //测试方法 -- 调用 test(); 进行测试

  function djdd()
  {
   createGcjDhccFullScreen('<font style="font-size:18px; color:#ff0000;">加载数据中 。。。</font>', null, null, null, null, null, null);
   showGcjDhccFullScreen();
  }
//左边框架点击，右边显示等待
function jzdd()
  {
   createGcjDhccFullScreen('<font style="font-size:18px; color:#ff0000;">加载数据中 。。。</font>', null, null, null, null, null,parent.parent.mainFrame);
   showGcjDhccFullScreen();
  }
/**
 * 备注，如果是在form.submit();时显示，可能会出现图片加载不出来。
 * 解决方法：可以延时调用form.submit();
 *
 * 延时调用例： *
 */
function formdd(form)
{
 createGcjDhccFullScreen('<font style="font-size:18px; color:#ff0000;">加载数据中 。。。</font>', null, null, null, null, null, null);
 showGcjDhccFullScreen();
 setTimeout(formSubmit(form),3000);  //延时调用formSubmit()方法
}
function formSubmit(a)
{
   //action如果在form中设置，这行可以不写
 a.submit();
}


