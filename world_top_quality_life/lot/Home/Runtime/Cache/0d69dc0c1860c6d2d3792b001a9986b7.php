<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta content="width=device-width, minimum-scale=1,initial-scale=1, maximum-scale=1, user-scalable=1;" id="viewport" name="viewport" />
<meta content="yes" name="apple-mobile-web-app-capable" />
<meta content="black" name="apple-mobile-web-app-status-bar-style" />
<meta content="telephone=no" name="format-detection" />
<title><?php echo ($config["web_name"]); ?></title>
<script src="__TMPL__js/rempublic.min.js" language="javascript"></script>
<link href="__TMPL__css/css.css?v=1.2" rel="stylesheet" type="text/css"/>
<script src="__TMPL__js/jquery-1.7.2.min.js" language="javascript"></script> 
<script src="__TMPL__js/jquery.rotate.min.js" language="javascript"></script>
<script src="__PUBLIC__/js/qrcode.min.js" language="javascript"></script> 
<?php
function sctonum($num, $double = 2) { if (false !== stripos((string)$num, "E")) { $a = explode("e", strtolower((string)$num)); $b=number_format($a[0],2); }else{ $b=$num; } return $b; } $len= sctonum(2.4/tan(deg2rad((180-360/count($prize))/2)))-0.01; ?>
<style>
<?php
echo '.rotary ul li{ margin-left:-' . $len . 'rem;border-left:' . $len . 'rem solid transparent; border-right:' . $len . 'rem solid transparent;border-top:2.4rem solid red;transform-origin:' . $len . 'rem 2.4rem;-webkit-transform-origin:' . $len . 'rem 2.4rem;-moz-transform-origin:' . $len . 'rem 2.4rem;-o-transform-origin:' . $len . 'rem 2.4rem;}'; ?>
#result ul{ display:none}
#result dl{ height:auto; overflow:hidden; margin-bottom:0.1rem; padding-bottom:0.1rem; border-bottom:0.01rem dashed rgba(255,255,255,0.5);}
#result dt{ width:30%; float:left}
#result dt img{ width:100%; display:block;}
#result dd{ float:right; width:67%; line-height:1.8; font-size:0.16rem;}
#result dd p{ margin-top:0.1rem}
#result dd p a{ width:1rem; text-align:center; line-height:0.3rem;height:0.3rem; background:#f6e112; border-radius:0.05rem; color:#d8261c; display:block;}

.ewm-win{ text-align:center; top:20%;}
.ewm-win img{ max-width:100%;}
</style>
</head>

<body>

<div class="main"> 
    <div class="lot-num">已有<?php echo ($lotNum); ?>人参与</div>
    <div class="core-w">
        <div class="core">
            <div class="rotary"><ul></ul><p></p></div>
            <div class="hand"><a href="javascript:;"></a></div>
            <div class="finger finger_rainbow"></div>
        </div>
    </div>
    <div class="tab">
        <div class="tab-nav"><span class="on">活动奖品</span><span>抽奖规则</span><span>中奖记录</span><span>中奖查询</span></div>

        <div class="tab-ct" style="display:block">
            <div class="prize">
                <?php if(is_array($prize)): $i = 0; $__LIST__ = $prize;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><dl <?php if($vo["nTag"] == 1): ?>style="display:none;"<?php endif; ?>>
                    <dt><img src="__PUBLIC__/<?php echo ($vo['pic']); ?>"></dt>
                    <dd><b><?php echo ($vo['title2']); ?></b><br><span><?php echo ($vo['title']); ?></span></dd>
                </dl><?php endforeach; endif; else: echo "" ;endif; ?>
            </div>
        </div>
        <div class="tab-ct"><?php echo (str_replace(chr(13),'<br>',$config["txt2"])); ?></div>
        <div class="tab-ct">
        <marquee direction="up" scrolldelay="150" height="200">
            <?php if(is_array($userList)): $i = 0; $__LIST__ = $userList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i; echo ($vo["username"]); ?>于<?php echo ($vo["subtime"]); ?>抽中<?php echo ($vo["jiangPin"]); ?><br><?php endforeach; endif; else: echo "" ;endif; ?>
        </marquee>
        </div>
        <div class="tab-ct">
        	<?php if($config['nCheckModel'] == 1): ?><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td style="border-bottom:1px solid #d8261c"><input id="cjm2" class="txt" type="text" value="" maxlength="14" placeholder="抽奖码"></td>
              </tr>
              <tr>
                <td><input id="btn-search" class="f-btn" type="button" value="查 询"></td>
              </tr>
            </table>
            <br><?php endif; ?>
            <div id="result">
            <?php if(is_array($myprize)): $i = 0; $__LIST__ = $myprize;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><ul id="ewm<?php echo ($vo["id"]); ?>"></ul>
            <dl>
            <dt><img src="__PUBLIC__/<?php echo ($vo['pic']); ?>"></dt>
            <script>
			var qrcode = new QRCode("ewm<?php echo ($vo["id"]); ?>");
			qrcode.makeCode("http://<?php echo ($_SERVER['SERVER_NAME']); ?>__ROOT__/index.php/cash/?rid=<?php echo ($vo['id']); ?>&uid=<?php echo ($config['uid']); ?>");
			</script>
            <dd>
            奖品名称：<?php echo ($vo['jiangPin']); ?> <br>中奖时间： <?php echo ($vo['subtime']); ?><br>奖品状态：<?php if($vo["nStatus"] == 0): ?>未领取<?php else: ?>已兑奖<?php endif; ?>
            <?php if($vo["nStatus"] == 0): ?><p><a href="javascript:;" class="btn-ewm" data-id="ewm<?php echo ($vo["id"]); ?>">兑奖</a></p><?php endif; ?>
            </dd></dl><?php endforeach; endif; else: echo "" ;endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mask"></div>
<div class="opwin login">
    <div>
    <h1>- 信息登记 -</h1>
    <ul class="form">
        <li id="tr1"><input id="sname" class="txt" type="text" placeholder="请输入姓名" maxlength="10" data-rq="1" data-type="t"></li>
        <li id="tr2"><input id="mobile" type="text" class="txt" placeholder="请输入手机号码" maxlength="11" data-rq="1" data-type="m"></li>
        <li id="tr3"><input id="cjm" type="text" class="txt" placeholder="请输入抽奖码" maxlength="20" data-rq="1" data-type="t"></li>
        <li id="tr4"><input id="address" type="text" class="txt" placeholder="请输入联系地址" maxlength="60" data-rq="1" data-type="t"></li>
        <li class="c3"><input id="btnok"  class="btn" type="button" value="提 交"></li>
    </ul>
    </div>
    <a class="btn-close w-btn">×</a>
</div>

<div class="opwin result">
    <div>
    	<h1>中奖结果</h1>
        <h3></h3>

        <a href="javascript:;" class="btn-close a-btn" onClick="window.location.reloade();">确认</a>
    </div>
    <a class="btn-close w-btn">×</a>
</div>

<div class="opwin tip" style="position:fixed">
    <div>
    	<h1>- 温馨提示 -</h1>
        <h3></h3>

        <a href="javascript:;" class="btn-close a-btn">确认</a>
    </div>
    <a class="btn-close w-btn">×</a>
</div>

<div class="opwin ewm-win" style="position:fixed">
    <div>
    	<h1>- 兑奖 -</h1>
        <ul></ul>
        <p><br>请将此二维码展示给相关客服人员</p>
    </div>
    <a class="btn-close w-btn">×</a>
</div>

<?php if($config['diff_start'] > 0): ?><div class="mask" style="display:block;"></div>
<div class="opwin tip" style="position:fixed; display:block;">
    <div>
    	<h1>- 温馨提示 -</h1>
        <h3>活动还未开始！<br>活动时间：<?php echo ($config['startTime']); ?> 至 <?php echo ($config['endTime']); ?></h3>
    </div>
</div><?php endif; ?>

<?php if($config['diff_end'] > 0): ?><div class="mask" style="display:block;"></div>
<div class="opwin tip" style="position:fixed;display:block;">
    <div>
    	<h1>- 温馨提示 -</h1>
        <h3>活动已经过期！<br>活动时间：<?php echo ($config['startTime']); ?> 至 <?php echo ($config['endTime']); ?></h3>
    </div>
</div><?php endif; ?>

<script>
var tag=0;
var uid="<?php echo ($config['uid']); ?>";
$(function(){
	var len=$('.prize dl').length;
	var deg=360/len;
	$('.prize dl').each(function(i, e) {
		$('<li style="transform:rotate(-' + deg * i + 'deg);-webkit-transform:rotate(-' + deg * i + 'deg);-ms-transform:rotate(-' + deg * i + 'deg);"><span>' + $(this).find('b').text() + '<i>' + $(this).find('span').text() + '</i><img src="' + $(this).find('img').attr('src') + '"></span></li>').appendTo($(".rotary ul"));
	});


    $('.hand a').click(function(){
		 if(tag==1) return;
		 lottery();

    });
	
	$(".btn-close").click(function(){
		$('.opwin').fadeOut();
		$('.mask').hide();
	});
	
	$('.btn-ewm').click(function(){
		$('.mask').show();
		$('.ewm-win ul').html($('#' + $(this).attr('data-id')).html());
		$('.ewm-win').show();
	});
	$('#btnok').click(function(){
		var chk=true;
		$('.txt').each(function() {
			if($(this).is(':visible') && $(this).attr('data-rq')=='1'){
				if($.trim($(this).val())==''){
					alert($(this).attr('placeholder'));
					$(this).focus();
					chk=false;
					return false;
				}				
				if($(this).attr('data-type')=='m' && !checkMobile($(this).val())){
					alert('请输入正确的手机号');
					$(this).focus();
					chk=false;
					return false;
				}
			}
        });
		if(chk){
			$('.mask').hide();
			$('.opwin').fadeOut(100,function(){
				lottery();
				tag=1;
			});	
		}
	});
	
	$(".tab-nav span").click(function(){
		var n=$(this).index();
		$(this).addClass('on').siblings().removeClass('on');
		$('.tab-ct').hide().eq(n).show();
	});
	
	$('#btn-search').click(function(){
		if($.trim($('#cjm2').val())==''){
			alert('请输入抽奖码');
			$('#cjm2').focus();
			return false;
		}
		$.get('<?php echo U("search");?>?cjm=' + $('#cjm2').val(),function(data){
			$('#result').html(data);
			$('#result').show();
		});
	});
}); 
function lottery(){ 
	if(tag==1) return;
	tag=1; 
    $.ajax({ 
        type: 'post', 
        url: '<?php echo U("lottery");?>', 
		data:{'mobile':$('#mobile').val(),'cjm':$('#cjm').val(),'sname':$('#sname').val(),'address':$('#address').val(),'uid':uid},
        cache: false, 
        error: function(){return false;}, 
        success:function(obj){
			var obj=eval('[' + obj + ']');
			obj=obj[0];
			var m=obj.isHasChance;

			if(m==3){
				$(".rotary").rotate({ 
					duration:3000, //转动时间 
					angle: 0, //默认角度
					animateTo:360*6 + obj.rotate, //转动角度 
					easing: $.easing.easeOutSine, 
					callback: function(){ 
						tag=0;
						$('.result h3').html('[' + obj.results + ']');
						$('.result').fadeIn();
						$('.mask').show();
					} 
				});
			}else{
				tag=0;
				$('.tip h3').html(obj.results);
				$('.tip').fadeIn();
				$('.mask').show();
			}
        } 
    }); 
};
function checkMobile(str){
	var r=/1[3-8]+\d{9}/;
	if(!r.test(str))
		return false;
	return true;
}
</script>
<?php if($user['subscribe'] == 0 and $config["nNeedGZ"] == 1): ?><div class="mask" style="display:block; z-index:998"></div>
<div class="opwin" style="display:block; top:15%; position:fixed; z-index:999">
    <div>
        <p style="font-size:0.18rem;">
        	 <span>长按下方二维码图片<br />关注微信公众号,即可参与活动</span><br><br>
            <img src="__PUBLIC__/<?php echo ($config['ewm_pic']); ?>" width="80%">
        </p>
    </div>
</div><?php endif; ?>

<script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script type="text/javascript">
var shareConfig = {
	title: '<?php echo ($config["web_name"]); ?>',
	imgUrl: 'http://<?php echo $_SERVER['HTTP_HOST']; ?>/__PUBLIC__/<?php echo ($config["logo_path"]); ?>',
	link: 'http://<?php echo $_SERVER['HTTP_HOST']; ?>__ROOT__/index.php/index/?fid=<?php echo $user["id"];?>',
	desc: '<?php echo ($config["txt1"]); ?>',
	callback: ''
};
wx.config({
	debug: false,
	appId: '<?php echo $signPackage["appId"];?>',
	timestamp: <?php echo $signPackage["timestamp"];?>,
	nonceStr: '<?php echo $signPackage["nonceStr"];?>',
	signature: '<?php echo $signPackage["signature"];?>',
	jsApiList: [
		'onMenuShareTimeline',
		'onMenuShareAppMessage'
	  ]
});
wx.ready(function () {
	wx.onMenuShareAppMessage({
	  title:shareConfig.title,
	  desc: shareConfig.desc,
	  link: shareConfig.link,
	  imgUrl: shareConfig.imgUrl,
	  trigger: function (res) {
		//alert(JSON.stringify(res));
	  },
	  success: function (res) {
		$.get('<?php echo U("share");?>',function(rtn){
			if(rtn.status){
				alert(rtn.txt);
			}
		});
	  },
	  cancel: function (res) {
		//alert('已取消');
	  },
	  fail: function (res) {
		//alert(JSON.stringify(res));
	  }
	});
	wx.onMenuShareTimeline({
	  title:shareConfig.title,
	  desc: shareConfig.desc,
	  link: shareConfig.link,
	  imgUrl: shareConfig.imgUrl,
	  trigger: function (res) {
	  },
	  success: function (res) {
		$.get('<?php echo U("share");?>',function(rtn){
			if(rtn.status){
				alert(rtn.txt);
			}
		});
	  },
	  cancel: function (res) {
	  },
	  fail: function (res) {
	  }
	});
	wx.error(function(res){
		//alert(JSON.stringify(res));
	});
});

</script>

<?php if($config['nMusicTag'] == 1): ?><style>
.music-swicth{ background:url(__ROOT__/Home/Tpl/public/images/off.png) no-repeat center;background-size:100%; width:0.64rem; height:0.64rem; cursor:pointer; top:0.2rem; right:0.2rem; position:absolute; z-index:11;}
.on{background:url(__ROOT__/Home/Tpl/public/images/on.png) no-repeat center;background-size:100%;}
</style>
<div class="music-swicth on"></div>
<audio id="bgMuisc" autoplay="autoplay" loop src="<?php echo ($config['music_url']); ?>" preload="auto"></audio>
<script>
$(function(){
	var ms=document.getElementById('bgMuisc');
	$(".music-swicth").click(muisc);
	function muisc(){
		if(!ms.paused){
			ms.pause();
			$(".music-swicth").removeClass("on");
		}else{
			ms.play();
			$(".music-swicth").addClass("on");
		}
	}
});
function audioAutoPlay(id){
    var audio = document.getElementById(id);
    //audio.play();
    document.addEventListener("WeixinJSBridgeReady", function () {
    	audio.play();
    }, false);
}
audioAutoPlay('bgMuisc');
</script><?php endif; ?>

</body>
</html>