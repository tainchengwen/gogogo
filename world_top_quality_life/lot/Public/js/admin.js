
function confirmFuc()
{
	if(!confirm("确认要删除吗?"))
		return false;
	return true;
}
function OpenWin(htmlurl,w,h) 
{
	if (w==undefined)
	{
		w=window.screen.availWidth;
		h=window.screen.availHeight;
	}
	var nLeft=(window.screen.availWidth-w)/2; 
	var nTop=(window.screen.availHeight-h)/2; 
	window.open(htmlurl,'_blank','width=' + w + ',height=' + h + ',top=' + nTop + ',left=' + nLeft + ',toolbar=no,menubar=no,scrollbars=yes,resizable=yes,location=no,status=no');
} 


var con=null;
function set(id)
{
	con=document.getElementById(id);
}
function upload_callback(fpath)
{
	con.value=fpath;
	if (con.fireEvent) {
		con.fireEvent('onChange'); 
	}else if (document.createEvent) { 
		var evt = document.createEvent("MouseEvents");  
		evt.initMouseEvent("change", true, true, window,  
		0, 0, 0, 0, 0, false, false, false, false, 0, null);  
		con.dispatchEvent(evt);  
	} 
}

function resetUrl(u1,u2){
	window.location=u1.replace('%25%25%25',u2);
}

