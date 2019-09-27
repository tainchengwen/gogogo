var bPlay=false,scd=5;
setInterval(function(){
	dis--;
	if(dis<0){
		if(isMoile==1){
			scd--;
			$('.time span').text(scd + ' 正在抽取奖项...');
			if(scd==0){
				window.location.reload();
			}
		}else{
			$('.time span').text('00:00:00');
			window.location.reload();
		}
		return;
	}
	var h=parseInt(dis/(60*60));
	var t=dis- h * 60* 60;
	var m=parseInt(t/60);
	var s=t-m * 60;
	if(h<10) h='0' + h;
	if(m<10) m='0' + m;
	if(s<10) s='0' + s;
	
	$('.time span').text(h + ':' + m + ':' + s);
	if(dis<21 && !bPlay){
		bPlay=true;
		document.getElementById('sound').play();
	}
},1000);

$(function(){
	function getUserList(){
		$.get(url,function(rtn){
			var r=rtn; //eval('(' + rtn + ')');
			dis=r.dis;
			$('.user-list ul').html(r.html);
		});
	}
	setInterval(getUserList,10000);
	getUserList();
});

