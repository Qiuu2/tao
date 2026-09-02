
function stat()
{ 
	var a = pageYOffset+window.innerHeight-document.getElementById('bar').document.height-0 ;
	document.getElementById('bar').top = a ;
	setTimeout('stat()',2) ;
} 
function fix()
{ 
	nome=navigator.appName ;
	if(nome=='Netscape')
	{ 
		stat() ;
	} 
	else
	{ 
		var a=document.body.scrollTop+document.body.clientHeight-document.getElementById('bar').offsetHeight+0 
		document.getElementById('bar').style.top = a ;
	}
} 
window.attachEvent("onload",fix);
window.attachEvent("onresize",fix);
window.attachEvent("onscroll",fix);
